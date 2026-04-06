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
                <FormSelectAsync
                    label="Cliente"
                    v-model="filters.cliente"
                    :fetch-options="opcionesStore.fetchClientesCredits"
                    placeholder="Seleccione un cliente"
                />
                <FormInput
                    label="Estado crédito"
                    type="select"
                    v-model="filters.estado"
                    :options="estadosCredito"
                    placeholder="Seleccione un estado"
                />
            </div>

            <!-- Fila 2 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Vencimiento de cuota + checkbox Por rango -->
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between">
                        <label :class="labelClass">
                            Vencimiento de cuota
                        </label>
                        <FormCheckbox
                            v-model="filters.porRango"
                            label="Por rango"
                        />
                    </div>
                    <FormInput v-model="filters.vencimientoCuota" type="date" />
                </div>

                <FormInput
                    label="Destino"
                    type="select"
                    v-model="filters.destino"
                    :options="destinoOpts"
                    placeholder="Seleccione un destino"
                />
                <FormInput
                    label="Periodicidad"
                    type="select"
                    v-model="filters.periodicidad"
                    :options="periodicidades"
                    placeholder="Seleccione la periodicidad"
                />

                <!-- Aliado + checkbox Solo aliados -->
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between">
                        <label :class="labelClass"> Aliado </label>
                        <FormCheckbox
                            v-model="filters.soloAliados"
                            label="Solo aliados"
                        />
                    </div>
                    <FormSelectAsync
                        v-model="filters.aliado"
                        :fetch-options="opcionesStore.fetchEmpresas"
                        placeholder="Seleccione un aliado"
                    />
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
                    <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                        <path
                            d="M11 6.5A4.5 4.5 0 1 1 6.5 2"
                            stroke="currentColor"
                            stroke-width="1.3"
                            stroke-linecap="round"
                        />
                        <path
                            d="M9 2h2.5V4.5"
                            stroke="currentColor"
                            stroke-width="1.3"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
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
                        @click.stop="verEstadoCredito(row.id)"
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
                    v-if="creditos.length > 0 && !loading"
                    class="bg-gray-50 border-t-2 border-gray-200 font-semibold text-sm"
                >
                    <td
                        class="px-3 py-3 text-center text-gray-500 pr-6"
                        :colspan="3"
                    >
                        Total:
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.valorBase) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.valorAval) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.ivaAval) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.intereses) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.valorCredito) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.totalAbonado) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.totalPendiente) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.intMoratorio) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.gastosCobranza) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.pendienteMora) }}
                    </td>
                    <td class="px-3 py-3" colspan="5"></td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.cxcImpulsa) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.cxpAliados) }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </template>
        </DataTable>

        <transition name="modal">
            <EstadoCreditoModal
                v-model="modalOpen"
                :loading="loadingCredito"
                :credito="credito"
                @ver-historico="verHistorico"
                @liquidar="liquidarCredito"
                @ver-plan-pagos="verPlanPagos"
                @descargar-paz-salvo="descargarPazSalvo"
                @imprimir="imprimirCredito"
                @imprimir-abono="imprimirAbono"
            />
        </transition>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'

// -- Componentes --------------------------------------------------
import EstadoCreditoModal from '@/components/modals/EstadoCreditoModal.vue'
import FormSelectAsync from '@/components/form/FormSelectAsync.vue'
import FormCheckbox from '@/components/form/FormCheckbox.vue'
import DataTable from '@/components/table/DataTable.vue'
import FormInput from '@/components/form/FormInput.vue'

import { useEstadoCredito } from '@/composables/useEstadoCredito'
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

import { formatCurrency } from '@/utils/format'
import api from '@/services/api'

// -- Store --------------------------------------------------------
import { useOpcionesStore } from '@/stores/opciones'

// -- Columnas -----------------------------------------------------
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
    {
        key: 'cxcImpulsa',
        label: 'CXC Impulsa',
        sortable: false,
        align: 'right',
    },
    {
        key: 'cxpAliados',
        label: 'CXP Aliados',
        sortable: false,
        align: 'right',
    },
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
    'cxcImpulsa',
    'cxpAliados',
]

const opcionesStore = useOpcionesStore()

// -- Estado ------------------------------------------------------------------
const creditos = ref([])
const loading = ref(false)
const loadingInforme = ref(null)
const search = ref('')
let searchTimeout = null

// -- Totales ----------------------------------------------------------------
const valoresTotales = ref({})

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

// -- Opciones de selects ---------------------------------------------------
const clienteOpts = ref([])
const destinoOpts = ref([])
const aliadoOpts = ref([])

const estadosCredito = [
    { value: 'vigente', label: 'Al día' },
    { value: 'finalizado', label: 'Finalizado' },
    { value: 'mora', label: 'En mora' },
]

const periodicidades = [
    { value: 'semanal', label: 'Semanal' },
    { value: 'quincenal', label: 'Quincenal' },
    { value: 'mensual', label: 'Mensual' },
]

// -- Helpers ----------------------------------------------------------------
const labelClass = 'text-xs font-medium text-gray-400 uppercase tracking-wide'

// -- Backend ----------------------------------------------------------------
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

        const { data } = await api.get('/api/creditos/listCredits', { params })

        const { data: creditosData, total, current_page } = data.creditos

        // Lista de créditos
        transformCreditos(creditosData, data.totales)

        pagination.total = total
        pagination.currentPage = current_page
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

        // const response = await fetch(`/api/creditos/informe?${params}`, {
        //     headers: authHeaders(),
        // })
        // if (!response.ok) throw new Error('Error al generar informe')

        // const blob = await response.blob()
        // const a = document.createElement('a')
        // a.href = URL.createObjectURL(blob)
        // a.download = `informe_creditos_${tipo}.xlsx`
        // a.click()
        // URL.revokeObjectURL(a.href)
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
function generarExtracto(row) {
    console.log('Generar extracto:', row.id)
}
function anularCredito(row) {
    console.log('Anular crédito:', row.id)
}

function transformCreditos(data, sumaTotales) {
    const toNumber = v => Number(v) || 0

    const estadoCredito = cr => {
        if (cr.anulado) return 'Anulado'
        if (cr.enmora) return 'En mora'
        if (cr.finalizado == 1) return 'Finalizado'
        return 'Normal'
    }

    const mapCredito = cr => ({
        id: cr.id,
        cliente: cr.nombre,
        numCuotas: cr.num_cuotas,
        valorCuota: cr.val_cuotas,
        valorBase: cr.valor_contado,
        valorAval: cr.valor_aval,
        ivaAval: cr.valor_iva_aval,
        intereses: cr.intereses,
        valorCredito: cr.valor_credito,
        totalAbonado: cr.total_abonado,
        totalPendiente: cr.saldo,
        intMoratorio: cr.abono_int_mora,
        gastosCobranza: cr.abono_gas_cobranza,
        pendienteMora: cr.valor_mora,
        numCredito: cr.consecutivo,
        aliado: cr.empresa,
        fechaCredito: cr.fecha_credito,
        vencimiento: cr.vencimiento,
        plazo: cr.plazo,
        cxcImpulsa: cr.valor_cxc,
        cxpAliados: cr.valor_cxc_aliado,
        destino: cr.destino,
        estadoCredito: estadoCredito(cr),
    })

    creditos.value = data.map(mapCredito)

    const totalesMap = {
        valorBase: 'valor_contado',
        valorAval: 'total_aval',
        ivaAval: 'total_iva_aval',
        intereses: 'total_credito_intereses',
        valorCredito: 'valor_credito',
        totalAbonado: 'total_abonado',
        totalPendiente: 'saldo',
        intMoratorio: 'total_abono_int_mora',
        gastosCobranza: 'total_abono_gas_cobranza',
        pendienteMora: 'total_credito_mora',
        cxcImpulsa: 'valor_cxc',
        cxpAliados: 'total_cxc_aliado',
    }

    valoresTotales.value = Object.fromEntries(
        Object.entries(totalesMap).map(([key, source]) => [
            key,
            toNumber(sumaTotales[source]),
        ])
    )
}

// -- Modal estado credito --------------------------------------
const {
    modalOpen,
    loadingCredito,
    credito,
    verEstadoCredito,
    verHistorico,
    liquidarCredito,
    verPlanPagos,
    descargarPazSalvo,
    imprimirCredito,
    imprimirAbono,
} = useEstadoCredito()

onMounted(async () => {
    start()

    try {
        await fetchCreditos()
    } finally {
        stop()
    }
})
</script>
