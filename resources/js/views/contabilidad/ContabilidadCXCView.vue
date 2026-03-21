<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">Recibo de caja CXC</h1>

        <!-- Panel de filtros -->
        <div class="bg-white rounded-xl border border-gray-200 px-6 py-4">
            <div class="flex flex-wrap items-end gap-4">
                <div class="flex flex-col gap-1.5">
                    <label :class="labelClass">Fecha inicial</label>
                    <input
                        v-model="filters.fechaInicial"
                        type="date"
                        :class="inputClass"
                    />
                </div>

                <div class="flex flex-col gap-1.5">
                    <label :class="labelClass">Fecha final</label>
                    <input
                        v-model="filters.fechaFinal"
                        type="date"
                        :class="inputClass"
                    />
                </div>

                <div class="flex flex-col gap-1.5">
                    <label :class="labelClass">Establecimiento</label>
                    <FormSelect
                        v-model="filters.establecimiento"
                        :options="establecimientosOpts"
                        placeholder="Seleccione un aliado"
                        class="w-52"
                    />
                </div>

                <!-- Botón alineado al fondo junto a los inputs -->
                <button
                    @click="fetchRecibos"
                    :disabled="loading"
                    class="h-9 px-5 rounded-lg bg-emerald-500 hover:bg-emerald-600 disabled:bg-gray-300 text-white text-sm font-semibold transition-all flex items-center gap-2 self-end"
                >
                    <svg
                        v-if="loading"
                        class="animate-spin w-3.5 h-3.5"
                        viewBox="0 0 24 24"
                        fill="none"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                        />
                    </svg>
                    Generar
                </button>
            </div>
        </div>

        <!-- DataTable -->
        <DataTable
            :rows="recibos"
            :columns="columns"
            :loading="loading"
            :total="pagination.total"
            :current-page="pagination.currentPage"
            :per-page="pagination.perPage"
            :sort-key="sort.key"
            :sort-dir="sort.dir"
            :search="search"
            empty-message="No se encontraron recibos con los filtros aplicados."
            @update:current-page="onPageChange"
            @update:per-page="onPerPageChange"
            @update:search="onSearch"
            @sort="onSort"
        >
            <!-- Valor CXC -->
            <template #cell-valorCXC="{ value }">
                <span class="tabular-nums font-medium text-gray-700">{{
                    formatCurrency(value)
                }}</span>
            </template>

            <!-- Comisiones -->
            <template #cell-comisiones="{ value }">
                <span class="tabular-nums text-gray-600">{{
                    formatCurrency(value)
                }}</span>
            </template>

            <!-- Acciones: botón imprimir -->
            <template #cell-acciones="{ row }">
                <div class="flex justify-center">
                    <button
                        @click.stop="imprimirRecibo(row)"
                        class="w-8 h-8 rounded-lg bg-emerald-500 hover:bg-emerald-600 flex items-center justify-center text-white transition-all"
                        title="Imprimir recibo"
                    >
                        <svg
                            width="14"
                            height="14"
                            viewBox="0 0 14 14"
                            fill="none"
                        >
                            <path
                                d="M3.5 4.5V2h7v2.5"
                                stroke="currentColor"
                                stroke-width="1.2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M3.5 9.5H2A1 1 0 0 1 1 8.5v-3A1 1 0 0 1 2 4.5h10a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1.5"
                                stroke="currentColor"
                                stroke-width="1.2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <rect
                                x="3.5"
                                y="8"
                                width="7"
                                height="4"
                                rx="0.5"
                                stroke="currentColor"
                                stroke-width="1.2"
                            />
                            <path
                                d="M3.5 6.5h.5"
                                stroke="currentColor"
                                stroke-width="1.2"
                                stroke-linecap="round"
                            />
                        </svg>
                    </button>
                </div>
            </template>
        </DataTable>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import DataTable from '@/components/table/DataTable.vue'
import FormSelect from '@/components/form/FormSelect.vue'

// ── Columnas ───────────────────────────────────────────────────────────────
const columns = [
    { key: 'fecha', label: 'Fecha', sortable: false, align: 'center' },
    { key: 'establecimiento', label: 'Establecimiento', sortable: false },
    { key: 'valorCXC', label: 'Valor CXC', sortable: false, align: 'right' },
    { key: 'comisiones', label: 'Comisiones', sortable: false, align: 'right' },
    { key: 'acciones', label: 'Acciones', sortable: false, align: 'center' },
]

// ── Estado ─────────────────────────────────────────────────────────────────
const recibos = ref([])
const loading = ref(false)
const search = ref('')
let searchTimeout = null

const pagination = reactive({ currentPage: 1, perPage: 10, total: 0 })
const sort = reactive({ key: 'fecha', dir: 'desc' })

// ── Filtros ────────────────────────────────────────────────────────────────
const filters = reactive({
    fechaInicial: '',
    fechaFinal: '',
    establecimiento: '',
})

const establecimientosOpts = [
    { value: 'cda_boston', label: 'CDA EL BOSTON' },
    { value: 'cda_fenix', label: 'CDA FENIX SAS' },
    { value: 'impulsa', label: 'IMPULSA CORP SAS / CREDITRANSITO' },
    { value: 'cda_moto', label: 'CDA MOTOCENTER RUTA 45A SAS' },
]

// ── Helpers ────────────────────────────────────────────────────────────────
const labelClass = 'text-xs font-medium text-gray-400 uppercase tracking-wide'
const inputClass =
    'h-9 px-3 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600 outline-none focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10 transition-all'

function formatCurrency(value) {
    if (value == null) return '—'
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0,
    }).format(value)
}

function authHeaders() {
    return {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    }
}

// ── Backend ────────────────────────────────────────────────────────────────
async function fetchRecibos() {
    loading.value = true
    try {
        const params = new URLSearchParams({
            page: pagination.currentPage,
            per_page: pagination.perPage,
            sort_key: sort.key,
            sort_dir: sort.dir,
            search: search.value,
            ...(filters.fechaInicial && {
                fecha_inicial: filters.fechaInicial,
            }),
            ...(filters.fechaFinal && { fecha_final: filters.fechaFinal }),
            ...(filters.establecimiento && {
                establecimiento: filters.establecimiento,
            }),
        })

        const response = await fetch(
            `/api/contabilidad/recibo-caja-cxc?${params}`,
            {
                headers: authHeaders(),
            }
        )
        if (!response.ok) throw new Error()

        const data = await response.json()
        recibos.value = data.data
        pagination.total = data.meta.total
        pagination.currentPage = data.meta.current_page
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
}

async function imprimirRecibo(row) {
    try {
        const response = await fetch(
            `/api/contabilidad/recibo-caja-cxc/${row.id}/imprimir`,
            {
                headers: authHeaders(),
            }
        )
        if (!response.ok) throw new Error()
        const blob = await response.blob()
        const url = URL.createObjectURL(blob)
        window.open(url, '_blank')
        setTimeout(() => URL.revokeObjectURL(url), 10000)
    } catch (err) {
        console.error(err)
    }
}

// ── Handlers DataTable ─────────────────────────────────────────────────────
function onPageChange(page) {
    pagination.currentPage = page
    fetchRecibos()
}
function onPerPageChange(val) {
    pagination.perPage = val
    pagination.currentPage = 1
    fetchRecibos()
}
function onSort({ key, dir }) {
    sort.key = key
    sort.dir = dir
    pagination.currentPage = 1
    fetchRecibos()
}
function onSearch(val) {
    search.value = val
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        pagination.currentPage = 1
        fetchRecibos()
    }, 400)
}

onMounted(fetchRecibos)
</script>
