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
                            v-model="filters.vencimientoPorRango"
                            label="Por rango"
                        />
                    </div>
                    <div
                        class="grid gap-2"
                        :class="
                            filters.vencimientoPorRango
                                ? 'grid-cols-2'
                                : 'grid-cols-1'
                        "
                    >
                        <FormInput
                            v-model="filters.vencimientoCuota"
                            type="date"
                        />
                        <FormInput
                            v-if="filters.vencimientoPorRango"
                            v-model="filters.vencimientoCuotaHasta"
                            type="date"
                        />
                    </div>
                </div>

                <FormInput
                    label="Destino"
                    type="select"
                    v-model="filters.destino"
                    :options="destinoOpts"
                    placeholder="Seleccione un destino"
                    :searchable="true"
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
                class="flex flex-col lg:flex-row lg:items-center justify-between gap-4"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        @click="generarInforme('resumido')"
                        :disabled="loadingInforme === 'resumido'"
                        class="btn btn-main"
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
                        class="btn btn-primary"
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

                <div class="flex items-center gap-2 sm:justify-end">
                    <button
                        @click="resetFilters"
                        class="btn flex-1 sm:flex-none border border-gray-200 text-sm text-gray-500 hover:bg-gray-50 hover:border-gray-300"
                    >
                        Limpiar
                    </button>
                    <button
                        @click="fetchCreditos"
                        class="btn btn-main flex-1 sm:flex-none"
                    >
                        Aplicar filtros
                    </button>
                </div>
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
                <UpdateMoraButton :onSuccess="fetchCreditos" />
            </template>

            <!-- Celdas de moneda -->
            <template
                v-for="col in currencyCols"
                #[`cell-${col}`]="{ value }"
                :key="col"
            >
                <span class="font-medium text-gray-600">{{
                    formatCurrency(value)
                }}</span>
            </template>

            <!-- Acciones -->
            <template #cell-acciones="{ row }">
                <div class="flex items-center gap-1.5">
                    <button
                        @click.stop="verEstadoCredito(null, row.id)"
                        class="btn btn-main"
                    >
                        Detalle
                    </button>
                    <button
                        @click.stop="abrirModalGenerarExtracto(row)"
                        class="btn btn-info"
                    >
                        Generar extracto
                    </button>
                    <button
                        v-if="!row.anulado && row.totalAbonado == 0"
                        @click.stop="abrirModalAnularCredito(row)"
                        class="btn btn-danger"
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

        <AppModal
            :model-value="modalAnular.open"
            :title="`Anular crédito ${modalAnular.credito} - ${modalAnular.cliente}`"
            size="lg"
            :show-footer="true"
            cancel-label="Cancelar"
            confirm-label="Anular"
            :confirm-loading="modalAnular.loading"
            :close-on-overlay="true"
            @confirm="anularCredito"
            @update:modelValue="cerrarModalAnularCredito"
        >
            <div>
                <FormInput
                    label="Motivo de anulación del crédito"
                    v-model="modalAnular.observacion"
                    type="textarea"
                />
            </div>
        </AppModal>

        <AppModal
            :model-value="modalExtractoCredito.open"
            :title="`Extracto crédito ${modalExtractoCredito.credito} - ${modalExtractoCredito.cliente}`"
            size="lg"
            :show-footer="false"
            :confirm-loading="modalExtractoCredito.loading"
            :close-on-overlay="true"
            @update:modelValue="cerrarModalGenerarExtracto"
        >
            <div>
                <div class="mb-4">
                    <FormInput
                        label="Fecha de corte"
                        v-model="modalExtractoCredito.fecha"
                        type="date"
                    />
                </div>

                <div class="flex gap-1.5 justify-content-center">
                    <button
                        @click="generarExtracto('pdf')"
                        :disabled="modalExtractoCredito.loading"
                        class="btn btn-main"
                    >
                        <i class="fa-regular fa-file-pdf text-lg"></i>

                        <span
                            v-if="modalExtractoCredito.loading"
                            class="flex items-center gap-1"
                        >
                            Descargando
                            <svg
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
                        </span>

                        <span v-else> Descargar </span>
                    </button>

                    <button
                        @click="generarExtracto('email')"
                        :disabled="modalExtractoCredito.loading"
                        class="btn btn-primary"
                    >
                        <i class="fa-regular fa-envelope text-lg"></i>
                        <span>Enviar</span>
                    </button>
                </div>
            </div>
        </AppModal>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'

// -- Componentes --------------------------------------------------
import EstadoCreditoModal from '@/components/modals/EstadoCreditoModal.vue'
import FormSelectAsync from '@/components/form/FormSelectAsync.vue'
import FormCheckbox from '@/components/form/FormCheckbox.vue'
import FormInput from '@/components/form/FormInput.vue'
import AppModal from '@/components/AppModal.vue'

import UpdateMoraButton from '@/components/table/UpdateMoraButton.vue'
import DataTable from '@/components/table/DataTable.vue'

// -- Composables --------------------------------------------------
import { useEstadoCredito } from '@/composables/useEstadoCredito'
// -- Datatable ----------------------------------------------------
import { useDataTable } from '@/composables/useDataTable'

// -- Loader -------------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

// -- Toaster ------------------------------------------------------
import { notify } from '@/composables/useNotify'

// -- Utils ----------------------------------------------------------
import { formatCurrency } from '@/utils/format'
import { confirmAlert } from '@/utils/alert'
import dayjs from 'dayjs'

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
    'valorCuota',
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

// -- Totales ----------------------------------------------------------------
const valoresTotales = ref({})

// -- Filtros ----------------------------------------------------------------
const filters = reactive({
    fechaInicial: '',
    fechaFinal: '',
    cliente: '',
    estado: '',
    vencimientoCuota: '',
    vencimientoCuotaHasta: '',
    vencimientoPorRango: false,
    destino: '',
    periodicidad: '',
    aliado: '',
    soloAliados: false,
})

// Modal anulación del crédito
const modalAnular = reactive({
    open: false,
    loading: false,
    observacion: '',
    cliente: '',
    credito: null,
    error: '',
})

const modalExtractoCredito = reactive({
    open: false,
    loading: false,
    cliente: '',
    fecha: '',
    credito: null,
    error: '',
})

// -- Opciones de selects ---------------------------------------------------
const destinoOpts = ref([])

const estadosCredito = [
    { value: 1, label: 'En mora' },
    { value: 2, label: 'Al día' },
    { value: 4, label: 'Finalizado' },
]

const periodicidades = [
    { value: 'Semanal', label: 'Semanal' },
    { value: 'Quincenal', label: 'Quincenal' },
    { value: 'Mensual', label: 'Mensual' },
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
            ...(filters.vencimientoPorRango &&
                filters.vencimientoCuotaHasta && {
                    vencimiento_cuota_hasta: filters.vencimientoCuotaHasta,
                }),
            ...(filters.destino && { destino: filters.destino }),
            ...(filters.periodicidad && { periodicidad: filters.periodicidad }),
            ...(filters.aliado && { aliado: filters.aliado }),
            ...(filters.vencimientoPorRango && { por_rango: 1 }),
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

async function resetFilters() {
    filters.fechaInicial = ''
    filters.fechaFinal = ''
    filters.cliente = ''
    filters.estado = ''
    filters.vencimientoCuota = ''
    filters.vencimientoCuotaHasta = ''
    filters.vencimientoPorRango = ''
    filters.destino = ''
    filters.periodicidad = ''
    filters.aliado = ''
    filters.soloAliados = ''

    await fetchCreditos()
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

// -- Acciones de fila --------------------------------------------------
function abrirModalGenerarExtracto(row) {
    modalExtractoCredito.fecha = dayjs().format('YYYY-MM-DD')
    modalExtractoCredito.cliente = row.cliente
    modalExtractoCredito.credito = row.id
    modalExtractoCredito.loading = false
    modalExtractoCredito.error = ''
    modalExtractoCredito.open = true
}

function cerrarModalGenerarExtracto() {
    modalExtractoCredito.open = false
}

function abrirModalAnularCredito(row) {
    modalAnular.cliente = row.cliente
    modalAnular.credito = row.id
    modalAnular.observacion = ''
    modalAnular.error = ''
    modalAnular.open = true
}

function cerrarModalAnularCredito() {
    modalAnular.open = false
}

async function generarExtracto(action, credito) {
    modalExtractoCredito.loading = true

    try {
        if (action === 'pdf') {
            const response = await api.get(
                `/api/extracto/generar/${modalExtractoCredito.credito}`,
                {
                    params: {
                        fecha_corte: modalExtractoCredito.fecha,
                    },
                }
            )

            window.open(response.data, '_blank')
        }
    } catch (err) {
        notify.error(
            err.response.data.message ||
                'Ocurrió un error al generar el extracto.'
        )
    } finally {
        modalExtractoCredito.loading = false
    }
}

async function anularCredito() {
    if (!modalAnular.observacion) {
        notify.error('No se ha especificado el motivo de la anulación.')
        return
    }

    const confirmado = await confirmAlert({
        title: 'Anular crédito',
        text: `¿Está seguro(a) de anular el crédito?`,
    })

    if (!confirmado) return

    start()

    try {
        await api.delete('/api/creditos/anularCredito', {
            data: {
                credito: modalAnular.credito,
                observacion: modalAnular.observacion,
            },
        })

        modalAnular.open = false

        await fetchCreditos()
        notify.success('Crédito anulado correctamente.')
    } catch (err) {
        notify.error(
            err.response?.data?.message ||
                'Ocurrió un error al realizar la anulación del abono.'
        )
    } finally {
        stop()
    }
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
        anulado: cr.anulado,
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

const {
    search,
    pagination,
    sort,
    onPageChange,
    onPerPageChange,
    onSearch,
    onSort,
} = useDataTable(fetchCreditos, {
    initialSortKey: 'numCredito',
})

onMounted(async () => {
    start()

    try {
        await Promise.all([fetchCreditos(), opcionesStore.fetchDestinos()])
        destinoOpts.value = opcionesStore.destinos
    } finally {
        stop()
    }
})
</script>
