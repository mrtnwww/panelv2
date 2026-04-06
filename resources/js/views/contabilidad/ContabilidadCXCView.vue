<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">Recibo de caja CXC</h1>

        <!-- Panel de filtros -->
        <div class="bg-white rounded-xl border border-gray-200 px-6 py-4">
            <div class="flex flex-wrap items-end gap-4">
                <FormInput
                    label="Fecha inicial"
                    type="date"
                    v-model="filters.fechaInicial"
                />

                <FormInput
                    label="Fecha final"
                    type="date"
                    v-model="filters.fechaFinal"
                />

                <div class="flex flex-col gap-1.5 xl:w-[30%]">
                    <FormSelectAsync
                        label="Aliado"
                        v-model="filters.establecimiento"
                        :fetch-options="opcionesStore.fetchEmpresas"
                        placeholder="Seleccione un aliado"
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
                        <i class="fa fa-print"></i>
                    </button>
                </div>
            </template>
        </DataTable>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'

// -- Componentes ------------------------------------------
import FormSelectAsync from '@/components/form/FormSelectAsync.vue'
import DataTable from '@/components/table/DataTable.vue'
import FormInput from '@/components/form/FormInput.vue'

// -- Loader -----------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

// -- Composables ------------------------------------------
// -- DataTable --------------------------------------------
import { useDataTable } from '@/composables/useDataTable'

import { formatCurrency } from '@/utils/format'
import api from '@/services/api'

// -- Store -------------------------------------------------
import { useOpcionesStore } from '@/stores/opciones'

// -- Columnas ----------------------------------------------
const columns = [
    { key: 'fecha', label: 'Fecha', sortable: false, align: 'center' },
    { key: 'establecimiento', label: 'Establecimiento', sortable: false },
    { key: 'valorCXC', label: 'Valor CXC', sortable: false, align: 'right' },
    { key: 'comisiones', label: 'Comisiones', sortable: false, align: 'right' },
    { key: 'acciones', label: 'Acciones', sortable: false, align: 'center' },
]

const opcionesStore = useOpcionesStore()

// -- Estado ------------------------------------------------
const recibos = ref([])
const loading = ref(false)

// -- Filtros ---------------------------------------------------------
const filters = reactive({
    fechaInicial: '',
    fechaFinal: '',
    establecimiento: '',
})

function authHeaders() {
    return {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    }
}

// -- Backend ---------------------------------------------------------------
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

        const { data } = await api.get('/api/contabilidad/listRecibosCXC', {
            params,
        })

        const { data: recibosData, total, current_page } = data.recibosCaja

        transformarRecibos(recibosData)

        pagination.total = total
        pagination.currentPage = current_page
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

function transformarRecibos(data) {
    recibos.value = data.map(recibo => ({
        establecimiento: recibo.empresa.razon_social,
        valorCXC: recibo.valor_cxc,
        fecha: recibo.fecha,
        comisiones: 30000, // Valor de comisiones por defecto
    }))
}

const {
    search,
    pagination,
    sort,
    onPageChange,
    onPerPageChange,
    onSearch,
    onSort,
} = useDataTable(fetchRecibos, {
    initialSortKey: 'fecha',
})

onMounted(async () => {
    start()

    try {
        await fetchRecibos()
    } finally {
        stop()
    }
})
</script>
