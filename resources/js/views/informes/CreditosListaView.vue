<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">Informe créditos</h1>

        <!-- Panel de filtros -->
        <div
            class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-5"
        >
            <!-- Fila 1 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Fecha inicial -->
                <div class="flex flex-col gap-1.5">
                    <label
                        class="text-xs font-medium text-gray-400 uppercase tracking-wide"
                        >Fecha inicial</label
                    >
                    <input
                        v-model="filters.fechaInicial"
                        type="date"
                        :class="inputClass"
                    />
                </div>

                <!-- Fecha final -->
                <div class="flex flex-col gap-1.5">
                    <label
                        class="text-xs font-medium text-gray-400 uppercase tracking-wide"
                        >Fecha final</label
                    >
                    <input
                        v-model="filters.fechaFinal"
                        type="date"
                        :class="inputClass"
                    />
                </div>

                <!-- Cliente -->
                <div class="flex flex-col gap-1.5">
                    <label
                        class="text-xs font-medium text-gray-400 uppercase tracking-wide"
                        >Cliente</label
                    >
                    <div class="relative">
                        <select
                            v-model="filters.cliente"
                            :class="[
                                inputClass,
                                'appearance-none pr-8 cursor-pointer',
                            ]"
                        >
                            <option value="">Seleccione un cliente</option>
                            <option
                                v-for="c in clientes"
                                :key="c.value"
                                :value="c.value"
                            >
                                {{ c.label }}
                            </option>
                        </select>
                        <ChevronIcon />
                    </div>
                </div>

                <!-- Estado crédito -->
                <div class="flex flex-col gap-1.5">
                    <label
                        class="text-xs font-medium text-gray-400 uppercase tracking-wide"
                        >Estado crédito</label
                    >
                    <div class="relative">
                        <select
                            v-model="filters.estado"
                            :class="[
                                inputClass,
                                'appearance-none pr-8 cursor-pointer',
                            ]"
                        >
                            <option value="">Seleccione un estado</option>
                            <option
                                v-for="e in estadosCredito"
                                :key="e.value"
                                :value="e.value"
                            >
                                {{ e.label }}
                            </option>
                        </select>
                        <ChevronIcon />
                    </div>
                </div>
            </div>

            <!-- Fila 2 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Vencimiento de cuota -->
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between">
                        <label
                            class="text-xs font-medium text-gray-400 uppercase tracking-wide"
                            >Vencimiento de cuota</label
                        >
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input
                                v-model="filters.porRango"
                                type="checkbox"
                                class="w-3.5 h-3.5 rounded border-gray-300 accent-[#1a5c2a] cursor-pointer"
                            />
                            <span class="text-xs text-gray-400">Por rango</span>
                        </label>
                    </div>
                    <input
                        v-model="filters.vencimientoCuota"
                        type="date"
                        :class="inputClass"
                    />
                </div>

                <!-- Destino -->
                <div class="flex flex-col gap-1.5">
                    <label
                        class="text-xs font-medium text-gray-400 uppercase tracking-wide"
                        >Destino</label
                    >
                    <div class="relative">
                        <select
                            v-model="filters.destino"
                            :class="[
                                inputClass,
                                'appearance-none pr-8 cursor-pointer',
                            ]"
                        >
                            <option value="">Seleccione un destino</option>
                            <option
                                v-for="d in destinos"
                                :key="d.value"
                                :value="d.value"
                            >
                                {{ d.label }}
                            </option>
                        </select>
                        <ChevronIcon />
                    </div>
                </div>

                <!-- Periodicidad -->
                <div class="flex flex-col gap-1.5">
                    <label
                        class="text-xs font-medium text-gray-400 uppercase tracking-wide"
                        >Periodicidad</label
                    >
                    <div class="relative">
                        <select
                            v-model="filters.periodicidad"
                            :class="[
                                inputClass,
                                'appearance-none pr-8 cursor-pointer',
                            ]"
                        >
                            <option value="">Seleccione la periodicidad</option>
                            <option
                                v-for="p in periodicidades"
                                :key="p.value"
                                :value="p.value"
                            >
                                {{ p.label }}
                            </option>
                        </select>
                        <ChevronIcon />
                    </div>
                </div>

                <!-- Aliado -->
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between">
                        <label
                            class="text-xs font-medium text-gray-400 uppercase tracking-wide"
                            >Aliado</label
                        >
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input
                                v-model="filters.soloAliados"
                                type="checkbox"
                                class="w-3.5 h-3.5 rounded border-gray-300 accent-[#1a5c2a] cursor-pointer"
                            />
                            <span class="text-xs text-gray-400"
                                >Solo aliados</span
                            >
                        </label>
                    </div>
                    <div class="relative">
                        <select
                            v-model="filters.aliado"
                            :class="[
                                inputClass,
                                'appearance-none pr-8 cursor-pointer',
                            ]"
                        >
                            <option value="">Seleccione un aliado</option>
                            <option
                                v-for="a in aliados"
                                :key="a.value"
                                :value="a.value"
                            >
                                {{ a.label }}
                            </option>
                        </select>
                        <ChevronIcon />
                    </div>
                </div>
            </div>

            <!-- Botones de informe -->
            <div
                class="flex flex-col items-start justify-end gap-3 pt-1 border-t border-gray-100 xl:flex-row xl:items-end"
            >
                <button
                    @click="generarInforme('resumido')"
                    :disabled="loadingInforme === 'resumido'"
                    class="h-9 px-5 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] disabled:bg-gray-300 text-white text-sm font-medium transition-all flex items-center gap-2"
                >
                    <svg
                        v-if="loadingInforme === 'resumido'"
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
                    Generar informe resumido
                </button>

                <button
                    @click="generarInforme('detallado')"
                    :disabled="loadingInforme === 'detallado'"
                    class="h-9 px-5 rounded-lg bg-emerald-500 hover:bg-emerald-600 disabled:bg-gray-300 text-white text-sm font-medium transition-all flex items-center gap-2"
                >
                    <svg
                        v-if="loadingInforme === 'detallado'"
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
                    Generar informe detallado
                </button>
            </div>
        </div>

        <!-- DataTable de créditos -->
        <DataTable
            :rows="creditos"
            :columns="columns"
            :loading="loading"
            :total="pagination.total"
            :current-page="pagination.currentPage"
            :per-page="pagination.perPage"
            :sort-key="sort.key"
            :sort-dir="sort.dir"
            :search="search"
            empty-message="No se encontraron créditos con los filtros aplicados."
            @update:current-page="onPageChange"
            @update:per-page="onPerPageChange"
            @update:search="onSearch"
            @sort="onSort"
        >
            <!-- Controles extra en la barra -->
            <template #actions>
                <button
                    @click="fetchCreditos"
                    class="h-8 px-3 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium transition-all flex items-center gap-1.5"
                >
                    <i class="fa-solid fa-arrow-rotate-right"></i>
                    Actualizar valores
                </button>
            </template>

            <!-- Celda de valor cuota -->
            <template #cell-valorCuota="{ value }">
                <span class="text-gray-700 font-medium">{{
                    formatCurrency(value)
                }}</span>
            </template>

            <!-- Celdas de moneda -->
            <template
                v-for="col in currencyCols"
                #[`cell-${col}`]="{ value }"
                :key="col"
            >
                <span class="text-gray-600">{{ formatCurrency(value) }}</span>
            </template>

            <!-- Acciones -->
            <template #cell-acciones="{ row }">
                <div class="flex items-center gap-1.5">
                    <button
                        @click.stop="verDetalle(row)"
                        class="h-7 px-3 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] text-white text-xs font-medium transition-all"
                    >
                        Detalle
                    </button>
                    <button
                        @click.stop="generarExtracto(row)"
                        class="h-7 px-3 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-medium transition-all"
                    >
                        Generar extracto
                    </button>
                    <button
                        @click.stop="anularCredito(row)"
                        class="h-7 px-3 rounded-lg bg-red-500 hover:bg-red-600 text-white text-xs font-medium transition-all"
                    >
                        Anular
                    </button>
                </div>
            </template>

            <!-- Fila de totales -->
            <template #footer>
                <tr
                    v-if="creditos.length > 0"
                    class="bg-gray-50 border-t-2 border-gray-200 font-semibold text-sm"
                >
                    <td
                        class="px-3 py-3 text-right text-gray-500 pr-6"
                        :colspan="3"
                    >
                        Total:
                    </td>
                    <td class="px-3 py-3 text-gray-700">
                        {{ formatCurrency(totales.valorBase) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700">
                        {{ formatCurrency(totales.valorAval) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700">
                        {{ formatCurrency(totales.ivaAval) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700">
                        {{ formatCurrency(totales.intereses) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700">
                        {{ formatCurrency(totales.valorCredito) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700">
                        {{ formatCurrency(totales.totalAbonado) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700">
                        {{ formatCurrency(totales.totalPendiente) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700">
                        {{ formatCurrency(totales.intMoratorio) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700">
                        {{ formatCurrency(totales.gastosCobranza) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700">
                        {{ formatCurrency(totales.pendienteMora) }}
                    </td>
                    <td class="px-3 py-3" colspan="2"></td>
                </tr>
            </template>
        </DataTable>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import DataTable from '@/components/table/DataTable.vue'
import ChevronIcon from '@/components/ChevronIcon.vue'

const router = useRouter()

// ── Columnas ───────────────────────────────────────────────────────────────
const columns = [
    { key: 'cliente', label: 'Cliente', sortable: false },
    {
        key: 'numCuotas',
        label: 'Núm. cuotas',
        sortable: false,
        align: 'center',
    },
    {
        key: 'valorCuota',
        label: 'Valor cuota',
        sortable: false,
        align: 'right',
    },
    { key: 'valorBase', label: 'Valor base', sortable: false, align: 'right' },
    { key: 'valorAval', label: 'Valor Aval', sortable: false, align: 'right' },
    { key: 'ivaAval', label: 'IVA Aval', sortable: false, align: 'right' },
    { key: 'intereses', label: 'Intereses', sortable: false, align: 'right' },
    {
        key: 'valorCredito',
        label: 'Valor Crédito',
        sortable: false,
        align: 'right',
    },
    {
        key: 'totalAbonado',
        label: 'Tot. Abonado',
        sortable: false,
        align: 'right',
    },
    {
        key: 'totalPendiente',
        label: 'Tot. Pendiente',
        sortable: false,
        align: 'right',
    },
    {
        key: 'intMoratorio',
        label: 'Int. moratorio',
        sortable: false,
        align: 'right',
    },
    {
        key: 'gastosCobranza',
        label: 'Gastos cobranza',
        sortable: false,
        align: 'right',
    },
    {
        key: 'pendienteMora',
        label: 'Pendiente en mora',
        sortable: false,
        align: 'right',
    },
    { key: 'numCredito', label: 'Núm. Crédito', sortable: false },
    { key: 'aliado', label: 'Aliado', sortable: false },
    { key: 'fechaCredito', label: 'Fecha crédito', sortable: false },
    { key: 'vencimiento', label: 'Vencimiento', sortable: false },
    { key: 'plazo', label: 'Plazo', sortable: false },
    { key: 'cxcImpulsa', label: 'CXC Impulsa', sortable: false },
    { key: 'cxpAliados', label: 'CXP Aliados', sortable: false },
    { key: 'destino', label: 'Destino', sortable: false },
    { key: 'estadoCredito', label: 'Estado crédito', sortable: false },
    { key: 'acciones', label: '', sortable: false },
]

// Columnas que renderizan como moneda via slot dinámico
const currencyCols = [
    'valorBase',
    'valorAval',
    'ivaAval',
    'intereses',
    'valorCredito',
    'totalAbonado',
    'totalPendiente',
    'intMoratorio',
    'gastosCobranza',
    'pendienteMora',
]

// ── Estado ─────────────────────────────────────────────────────────────────
const creditos = ref([])
const loading = ref(false)
const loadingInforme = ref(null)
const search = ref('')
let searchTimeout = null

const pagination = reactive({ currentPage: 1, perPage: 10, total: 0 })
const sort = reactive({ key: 'numCredito', dir: 'desc' })

// ── Filtros ────────────────────────────────────────────────────────────────
const filters = reactive({
    fechaInicial: '',
    fechaFinal: '',
    cliente: '',
    estado: '',
    vencimientoCuota: '',
    porRango: false,
    destino: '',
    periodicidad: '',
    aliado: '',
    soloAliados: false,
})

// ── Opciones de selects ────────────────────────────────────────────────────
const clientes = [
    { value: '1', label: 'Juan David Alvarado' },
    { value: '2', label: 'Sara Michel Trujillo' },
]

const estadosCredito = [
    { value: 'vigente', label: 'Vigente' },
    { value: 'finalizado', label: 'Finalizado' },
    { value: 'anulado', label: 'Anulado' },
    { value: 'mora', label: 'En mora' },
]

const destinos = [
    { value: 'vehiculo', label: 'Vehículo' },
    { value: 'libre', label: 'Libre inversión' },
    { value: 'vivienda', label: 'Vivienda' },
]

const periodicidades = [
    { value: 'semanal', label: 'Semanal' },
    { value: 'quincenal', label: 'Quincenal' },
    { value: 'mensual', label: 'Mensual' },
]

const aliados = [
    { value: 'impulsa', label: 'IMPULSA CORP SAS / CREDITRANSITO' },
    { value: 'cda', label: 'CDA LEBRIJA' },
]

// ── Totales calculados ─────────────────────────────────────────────────────
const totales = computed(() => {
    const sum = key => creditos.value.reduce((acc, c) => acc + (c[key] ?? 0), 0)
    return {
        valorBase: sum('valorBase'),
        valorAval: sum('valorAval'),
        ivaAval: sum('ivaAval'),
        intereses: sum('intereses'),
        valorCredito: sum('valorCredito'),
        totalAbonado: sum('totalAbonado'),
        totalPendiente: sum('totalPendiente'),
        intMoratorio: sum('intMoratorio'),
        gastosCobranza: sum('gastosCobranza'),
        pendienteMora: sum('pendienteMora'),
    }
})

// ── Helpers ────────────────────────────────────────────────────────────────
const inputClass =
    'w-full h-9 px-3 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600 outline-none focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10 transition-all'

function formatCurrency(value) {
    if (value == null) return '$0'
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0,
    }).format(value)
}

// ── Llamada al backend ─────────────────────────────────────────────────────
async function fetchCreditos() {
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
            ...(filters.cliente && { cliente_id: filters.cliente }),
            ...(filters.estado && { estado: filters.estado }),
            ...(filters.vencimientoCuota && {
                vencimiento_cuota: filters.vencimientoCuota,
            }),
            ...(filters.destino && { destino: filters.destino }),
            ...(filters.periodicidad && { periodicidad: filters.periodicidad }),
            ...(filters.aliado && { aliado: filters.aliado }),
            ...(filters.porRango && { por_rango: 1 }),
            ...(filters.soloAliados && { solo_aliados: 1 }),
        })

        const response = await fetch(`/api/creditos?${params}`, {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
            },
        })

        if (!response.ok) throw new Error('Error al cargar créditos')

        const data = await response.json()
        creditos.value = data.data
        pagination.total = data.meta.total
        pagination.currentPage = data.meta.current_page
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
}

async function generarInforme(tipo) {
    loadingInforme.value = tipo
    try {
        const params = new URLSearchParams({
            tipo,
            ...(filters.fechaInicial && {
                fecha_inicial: filters.fechaInicial,
            }),
            ...(filters.fechaFinal && { fecha_final: filters.fechaFinal }),
        })

        const response = await fetch(`/api/creditos/informe?${params}`, {
            headers: {
                Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
            },
        })

        if (!response.ok) throw new Error('Error al generar informe')

        // Descarga el archivo
        const blob = await response.blob()
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `informe_creditos_${tipo}.xlsx`
        a.click()
        URL.revokeObjectURL(url)
    } catch (err) {
        console.error(err)
    } finally {
        loadingInforme.value = null
    }
}

// ── Handlers DataTable ─────────────────────────────────────────────────────
function onPageChange(page) {
    pagination.currentPage = page
    fetchCreditos()
}

function onPerPageChange(val) {
    pagination.perPage = val
    pagination.currentPage = 1
    fetchCreditos()
}

function onSearch(val) {
    search.value = val
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        pagination.currentPage = 1
        fetchCreditos()
    }, 400)
}

function onSort({ key, dir }) {
    sort.key = key
    sort.dir = dir
    pagination.currentPage = 1
    fetchCreditos()
}

// ── Acciones de fila ───────────────────────────────────────────────────────
function verDetalle(row) {
    router.push(`/dashboard/creditos/${row.id}`)
}

function generarExtracto(row) {
    // Llamada al backend para generar PDF del extracto
    console.log('Generar extracto:', row.id)
}

function anularCredito(row) {
    // Confirmar y anular
    console.log('Anular crédito:', row.id)
}

onMounted(fetchCreditos)
</script>
