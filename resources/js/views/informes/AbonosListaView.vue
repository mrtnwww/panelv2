<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">Informe abonos</h1>

        <!-- Panel de filtros -->
        <div
            class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-5"
        >
            <!-- Fila 1: fechas + selects -->
            <div
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4"
            >
                <!-- Fecha inicial -->
                <div class="flex flex-col gap-1.5">
                    <label :class="labelClass">Fecha inicial</label>
                    <input
                        v-model="filters.fechaInicial"
                        type="date"
                        :class="inputClass"
                    />
                </div>

                <!-- Fecha final -->
                <div class="flex flex-col gap-1.5">
                    <label :class="labelClass">Fecha final</label>
                    <input
                        v-model="filters.fechaFinal"
                        type="date"
                        :class="inputClass"
                    />
                </div>

                <!-- Recibido en -->
                <div class="flex flex-col gap-1.5">
                    <label :class="labelClass">Recibido en</label>
                    <div class="relative">
                        <select
                            v-model="filters.recibidoEn"
                            :class="[
                                inputClass,
                                'appearance-none pr-8 cursor-pointer',
                            ]"
                        >
                            <option value="">Seleccione un filtro</option>
                            <option
                                v-for="r in recibidoEnOpts"
                                :key="r.value"
                                :value="r.value"
                            >
                                {{ r.label }}
                            </option>
                        </select>
                        <ChevronIcon />
                    </div>
                </div>

                <!-- Cliente -->
                <div class="flex flex-col gap-1.5">
                    <label :class="labelClass">Cliente</label>
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
                                v-for="c in clientesOpts"
                                :key="c.value"
                                :value="c.value"
                            >
                                {{ c.label }}
                            </option>
                        </select>
                        <ChevronIcon />
                    </div>
                </div>

                <!-- Cajero(a) -->
                <div class="flex flex-col gap-1.5">
                    <label :class="labelClass">Cajera(o)</label>
                    <div class="relative">
                        <select
                            v-model="filters.cajero"
                            :class="[
                                inputClass,
                                'appearance-none pr-8 cursor-pointer',
                            ]"
                        >
                            <option value="">Seleccione una cajera(o)</option>
                            <option
                                v-for="c in cajerosOpts"
                                :key="c.value"
                                :value="c.value"
                            >
                                {{ c.label }}
                            </option>
                        </select>
                        <ChevronIcon />
                    </div>
                </div>

                <!-- Aliado -->
                <div class="flex flex-col gap-1.5">
                    <label :class="labelClass">Aliado</label>
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
                                v-for="a in aliadosOpts"
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

            <!-- Fila 2: checkboxes + botones -->
            <div
                class="flex flex-wrap items-center gap-x-5 gap-y-3 pt-3 border-t border-gray-100"
            >
                <!-- Checkboxes -->
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        v-model="filters.diasMora"
                        type="checkbox"
                        class="w-3.5 h-3.5 rounded border-gray-300 accent-[#1a5c2a] cursor-pointer"
                    />
                    <span class="text-xs text-gray-500">Días de mora</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        v-model="filters.abonoAval"
                        type="checkbox"
                        class="w-3.5 h-3.5 rounded border-gray-300 accent-[#1a5c2a] cursor-pointer"
                    />
                    <span class="text-xs text-gray-500">Abono aval</span>
                </label>

                <!-- Botones -->
                <div class="flex flex-wrap items-center gap-2 sm:ml-auto">
                    <button
                        @click="generarInforme('resumido')"
                        :disabled="loadingInforme === 'resumido'"
                        class="h-9 px-4 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] disabled:bg-gray-300 text-white text-sm font-medium transition-all flex items-center gap-2"
                    >
                        <SpinnerIcon v-if="loadingInforme === 'resumido'" />
                        Generar informe resumido
                    </button>

                    <button
                        @click="generarInforme('detallado')"
                        :disabled="loadingInforme === 'detallado'"
                        class="h-9 px-4 rounded-lg bg-emerald-500 hover:bg-emerald-600 disabled:bg-gray-300 text-white text-sm font-medium transition-all flex items-center gap-2"
                    >
                        <SpinnerIcon v-if="loadingInforme === 'detallado'" />
                        Generar informe detallado
                    </button>

                    <button
                        @click="generarFactura"
                        :disabled="loadingInforme === 'factura'"
                        class="h-9 px-4 rounded-lg bg-orange-500 hover:bg-orange-600 disabled:bg-gray-300 text-white text-sm font-medium transition-all flex items-center gap-2"
                    >
                        <SpinnerIcon v-if="loadingInforme === 'factura'" />
                        Generar factura
                    </button>
                </div>
            </div>
        </div>

        <!-- DataTable -->
        <DataTable
            :rows="abonos"
            :columns="columns"
            :loading="loading"
            :total="pagination.total"
            :current-page="pagination.currentPage"
            :per-page="pagination.perPage"
            :sort-key="sort.key"
            :sort-dir="sort.dir"
            :search="search"
            empty-message="No se encontraron abonos con los filtros aplicados."
            @update:current-page="onPageChange"
            @update:per-page="onPerPageChange"
            @update:search="onSearch"
            @sort="onSort"
        >
            <!-- Botón actualizar en barra -->
            <template #actions>
                <button
                    @click="fetchAbonos"
                    class="h-8 px-3 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium transition-all flex items-center gap-1.5"
                >
                    <i class="fa-solid fa-arrow-rotate-right"></i>
                    Actualizar adicionales
                </button>
            </template>

            <!-- Estado del crédito con badge de color -->
            <template #cell-estadoCredito="{ value }">
                <span
                    :class="[
                        'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap',
                        estadoBadgeClass(value),
                    ]"
                >
                    {{ value }}
                </span>
            </template>

            <!-- Valor total abonado como moneda -->
            <template #cell-vrTotalAbonado="{ value }">
                <span class="font-medium text-gray-700">{{
                    formatCurrency(value)
                }}</span>
            </template>
        </DataTable>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import DataTable from '@/components/table/DataTable.vue'

// ── Columnas ───────────────────────────────────────────────────────────────
const columns = [
    { key: 'fecha', label: 'Fecha', sortable: false },
    { key: 'cedula', label: 'Cédula', sortable: false },
    { key: 'nombre', label: 'Nombre', sortable: false },
    {
        key: 'numCredito',
        label: '# de crédito',
        sortable: false,
        align: 'center',
    },
    { key: 'numAbono', label: '# de abono', sortable: false, align: 'center' },
    { key: 'recibidoEn', label: 'Recibido en', sortable: false },
    { key: 'concepto', label: 'Concepto', sortable: false },
    { key: 'cajero', label: 'Cajera(o)', sortable: false, truncate: true },
    { key: 'estadoCredito', label: 'Estado del crédito', sortable: false },
    {
        key: 'vrTotalAbonado',
        label: 'Vr. total abonado',
        sortable: false,
        align: 'right',
    },
    { key: 'aval', label: 'Aval', sortable: false },
    { key: 'ivaAval', label: 'IVA Aval', sortable: false },
    { key: 'intereses', label: 'Intereses', sortable: false },
    { key: 'firmaElectronica', label: 'Firma electrónica', sortable: false },
    { key: 'capital', label: 'Capital', sortable: false },
    { key: 'intMoratorio', label: 'Int. moratorio', sortable: false },
    { key: 'gasCobranza', label: 'Gastos cobranza', sortable: false },
    { key: 'ivaGasCobranza', label: 'IVA gastos cobranza', sortable: false },
    {
        key: 'valorCondonacion',
        label: 'Valor condonación crédito',
        sortable: false,
    },
    {
        key: 'valorCondonacion',
        label: 'Valor condonación crédito',
        sortable: false,
    },
    { key: 'empresa', label: 'Empresa', sortable: false, truncate: true },
]

// ── Estado ─────────────────────────────────────────────────────────────────
const abonos = ref([])
const loading = ref(false)
const loadingInforme = ref(null)
const search = ref('')
let searchTimeout = null

const pagination = reactive({ currentPage: 1, perPage: 10, total: 0 })
const sort = reactive({ key: 'fecha', dir: 'desc' })

// ── Filtros ────────────────────────────────────────────────────────────────
const filters = reactive({
    fechaInicial: '',
    fechaFinal: '',
    recibidoEn: '',
    cliente: '',
    cajero: '',
    aliado: '',
    diasMora: false,
    abonoAval: false,
})

// ── Opciones ───────────────────────────────────────────────────────────────
const recibidoEnOpts = [
    { value: 'app', label: 'App Credigital' },
    { value: 'efectivo', label: 'Efectivo' },
    { value: 'transferencia', label: 'Transferencia' },
    { value: 'datafono', label: 'Datáfono' },
]

const clientesOpts = [
    { value: '1', label: 'Smith Rocio Silva Diaz' },
    { value: '2', label: 'Yuleidy Victoria Romero' },
    { value: '3', label: 'Lothar Andres Plata Perdomo' },
]

const cajerosOpts = [
    { value: '1', label: 'Maria Camila Ramirez' },
    { value: '2', label: 'CDA Puerta del Sol' },
]

const aliadosOpts = [
    { value: 'impulsa', label: 'IMPULSA CORP SAS / CREDITRANSITO' },
    { value: 'cda_moto', label: 'CDA MOTOCENTER RUTA 45A SAS' },
    { value: 'cda_prad', label: 'CDA LA PRADERA' },
    {
        value: 'solomotos',
        label: 'CENTRO DE DIAGNOSTICO AUTOMOTOR SOLOMOTOS ARMENIA',
    },
]

// ── Helpers ────────────────────────────────────────────────────────────────
const labelClass = 'text-xs font-medium text-gray-400 uppercase tracking-wide'
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

function estadoBadgeClass(estado) {
    if (!estado) return 'bg-gray-100 text-gray-500'
    const s = estado.toLowerCase()
    if (s.includes('mora')) return 'bg-red-50 text-red-600'
    if (s.includes('al día')) return 'bg-emerald-50 text-emerald-700'
    if (s.includes('finaliz')) return 'bg-gray-100 text-gray-500'
    return 'bg-blue-50 text-blue-600'
}

// ── Componentes inline ─────────────────────────────────────────────────────
const ChevronIcon = {
    template: `
        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
            </svg>
        </span>
    `,
}

const SpinnerIcon = {
    template: `
        <svg class="animate-spin w-3.5 h-3.5" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
        </svg>
    `,
}

// ── Backend ────────────────────────────────────────────────────────────────
async function fetchAbonos() {
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
            ...(filters.recibidoEn && { recibido_en: filters.recibidoEn }),
            ...(filters.cliente && { cliente_id: filters.cliente }),
            ...(filters.cajero && { cajero_id: filters.cajero }),
            ...(filters.aliado && { aliado: filters.aliado }),
            ...(filters.diasMora && { dias_mora: 1 }),
            ...(filters.abonoAval && { abono_aval: 1 }),
        })

        const response = await fetch(`/api/abonos?${params}`, {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
            },
        })

        if (!response.ok) throw new Error('Error al cargar abonos')

        const data = await response.json()
        abonos.value = data.data
        pagination.total = data.meta.total
        pagination.currentPage = data.meta.current_page
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
}

async function descargarArchivo(url, nombre) {
    const response = await fetch(url, {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
        },
    })
    if (!response.ok) throw new Error('Error al generar archivo')
    const blob = await response.blob()
    const a = document.createElement('a')
    a.href = URL.createObjectURL(blob)
    a.download = nombre
    a.click()
    URL.revokeObjectURL(a.href)
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
        await descargarArchivo(
            `/api/abonos/informe?${params}`,
            `informe_abonos_${tipo}.xlsx`
        )
    } catch (err) {
        console.error(err)
    } finally {
        loadingInforme.value = null
    }
}

async function generarFactura() {
    loadingInforme.value = 'factura'
    try {
        await descargarArchivo('/api/abonos/factura', 'factura_abonos.pdf')
    } catch (err) {
        console.error(err)
    } finally {
        loadingInforme.value = null
    }
}

// ── Handlers DataTable ─────────────────────────────────────────────────────
function onPageChange(page) {
    pagination.currentPage = page
    fetchAbonos()
}

function onPerPageChange(val) {
    pagination.perPage = val
    pagination.currentPage = 1
    fetchAbonos()
}

function onSearch(val) {
    search.value = val
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        pagination.currentPage = 1
        fetchAbonos()
    }, 400)
}

function onSort({ key, dir }) {
    sort.key = key
    sort.dir = dir
    pagination.currentPage = 1
    fetchAbonos()
}

onMounted(fetchAbonos)
</script>
