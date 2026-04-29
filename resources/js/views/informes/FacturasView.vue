<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">Informe facturas</h1>

        <!-- Panel de filtros -->
        <div
            class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-5"
        >
            <!-- Fila 1: fechas + selects -->
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
                <FormInput
                    label="Recibido en"
                    type="select"
                    v-model="filters.recibidoEn"
                    :options="recibidoEnOpts"
                    placeholder="Seleccione un filtro"
                    :searchable="true"
                />
                <FormSelectAsync
                    label="Cajera"
                    v-model="filters.cajera"
                    :fetch-options="opcionesStore.fetchCajeras"
                    placeholder="Seleccione una cajera"
                />
            </div>

            <!-- Fila 2: botones -->
            <div
                class="flex flex-col lg:flex-row lg:items-center justify-between gap-4"
            >
                <!-- Botones -->
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
                    <button @click="abrirModal" class="btn btn-info">
                        Crear terceros (F.E)
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
                        @click="fetchAbonos"
                        class="btn btn-main flex-1 sm:flex-none"
                    >
                        Aplicar filtros
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
        </DataTable>

        <!-- ── Modal crear terceros ── -->
        <AppModal
            v-model="modal.open"
            title="Crear terceros (F.E)"
            size="md"
            :show-footer="true"
            cancel-label="Cancelar"
            confirm-label="Guardar"
            :confirm-loading="modal.loading"
            :close-on-overlay="true"
            @confirm="crearTerceros"
            @update:modelValue="cerrarModal"
        >
            <div class="flex flex-col gap-4">
                <FormSelectAsync
                    label="Cliente"
                    v-model="modal.form.id"
                    @update:modelValue="onClienteChange"
                    :fetch-options="fetchClientesWrapper"
                    placeholder="Seleccione un cliente"
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-1.5">
                    <FormInput
                        label="Nombre 1"
                        v-model="modal.form.nombre1"
                        placeholder="Mila"
                    />

                    <FormInput
                        label="Nombre 2 (Opcional)"
                        v-model="modal.form.nombre2"
                        placeholder="Zuri"
                    />

                    <FormInput
                        label="Apellido 1"
                        v-model="modal.form.apellido1"
                        placeholder="Torres"
                    />

                    <FormInput
                        label="Apellido 2 (Opcional)"
                        v-model="modal.form.apellido2"
                        placeholder="Caro"
                    />
                </div>

                <div class="flex justify-center">
                    <button @click="añadirClienteFE" class="btn btn-main">
                        Añadir
                    </button>
                </div>

                <TableGrid
                    :items="listaClientesFE"
                    :columns="colsClientesFE"
                    :showActions="true"
                    minWidth="auto"
                >
                    <template #actions="{ item, index }">
                        <i
                            @click="eliminarFila(index)"
                            class="fa-solid fa-trash-can cursor-pointer text-red-500"
                        ></i>
                    </template>
                </TableGrid>
            </div>
        </AppModal>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'

// -- Componentes -----------------------------------------------------------
import FormSelectAsync from '@/components/form/FormSelectAsync.vue'
import FormCheckbox from '@/components/form/FormCheckbox.vue'
import FormInput from '@/components/form/FormInput.vue'
import TableGrid from '@/components/TableGrid.vue'
import AppModal from '@/components/AppModal.vue'

import DataTable from '@/components/table/DataTable.vue'

// -- Loader ----------------------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

// -- Toaster ---------------------------------------------------------------
import { notify } from '@/composables/useNotify'

// -- Composables ----------------------------------------------------------
// -- Datatable ------------------------------------------------------------
import { useDataTable } from '@/composables/useDataTable'

// -- Utils ----------------------------------------------------------------
import { formatCurrency } from '@/utils/format'
import { confirmAlert } from '@/utils/alert'

// -- API ------------------------------------------------------------------
import api from '@/services/api'

// -- Store -----------------------------------------------------------------
import { useOpcionesStore } from '@/stores/opciones'

// -- Columnas --------------------------------------------------------------
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
    { key: 'cajera', label: 'Cajera', sortable: false, truncate: true },
    { key: 'estadoCredito', label: 'Estado del crédito', sortable: false },
    {
        key: 'vrTotalAbonado',
        label: 'Vr. total abonado',
        sortable: false,
        align: 'right',
    },
    {
        key: 'vrAbonado',
        label: 'Vr. abonado',
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
    { key: 'empresa', label: 'Empresa', sortable: false, truncate: false },
]

// Columnas que renderizan como moneda via slot dinámico
const currencyCols = [
    'vrTotalAbonado',
    'vrAbonado',
    'aval',
    'ivaAval',
    'intereses',
    'firmaElectronica',
    'capital',
    'intMoratorio',
    'gasCobranza',
    'ivaGasCobranza',
    'valorCondonacion',
]

const colsClientesFE = [
    {
        key: 'cliente',
        label: 'Cliente',
        headerClass: 'text-center',
        cellClass: 'text-center',
    },
]

const opcionesStore = useOpcionesStore()

// -- Estado ----------------------------------------------------------------
const abonos = ref([])
const loading = ref(false)
const loadingInforme = ref(null)
const opcionesClientes = ref([])
const listaClientesFE = ref([])

// -- Filtros --------------------------------------------------------------
const filters = reactive({
    fechaInicial: '',
    fechaFinal: '',
    recibidoEn: '',
    cajera: '',
})

// -- Opciones Select --------------------------------------------------
const recibidoEnOpts = ref([])

function estadoBadgeClass(estado) {
    if (!estado) return 'bg-gray-100 text-gray-500'
    const s = estado.toLowerCase()
    if (s.includes('mora')) return 'bg-red-50 text-red-600'
    if (s.includes('al día')) return 'bg-emerald-50 text-emerald-700'
    if (s.includes('finaliz')) return 'bg-gray-100 text-gray-500'
    return 'bg-blue-50 text-blue-600'
}

function authHeaders() {
    return {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    }
}

// -- Backend ----------------------------------------------------------
async function fetchAbonos() {
    loading.value = true
    try {
        const params = new URLSearchParams({
            factura: 1,
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
            ...(filters.cajera && { cajero_id: filters.cajera }),
        })

        const { data } = await api.get('/api/abonos/listAbonos', { params })

        const { data: abonosData, total, current_page } = data.abonos

        // Lista de abonos
        transformAbonos(abonosData, data.totales)

        pagination.total = total
        pagination.currentPage = current_page
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
}

async function crearTerceros() {
    if (!listaClientesFE.value.length) {
        notify.error('No se han añadido clientes.')
        return
    }

    const confirmado = await confirmAlert({
        title: 'Crear terceros F.E',
        text: `¿Está seguro(a) de registrar los clientes?`,
    })

    if (!confirmado) return

    modal.loading = true

    try {
        await api.post('/api/facturacion/registrarTerceros', {
            data: {
                ...listaClientesFE.value,
            },
        })

        notify.success('Clientes creados en F.E correctamente.')

        modal.open = false
    } catch (err) {
        notify.error(
            err.response?.data?.message ||
                'Ocurrió un error al realizar la creación de los clientes.'
        )
    } finally {
        modal.loading = false
    }
}

async function resetFilters() {
    filters.fechaInicial = ''
    filters.fechaFinal = ''
    filters.recibidoEn = ''
    filters.cajera = ''

    await fetchAbonos()
}

const fetchClientesWrapper = async query => {
    const opciones = await opcionesStore.fetchClientesPendientesFE(query)
    opcionesClientes.value = opciones
    return opciones
}

// -- Modal crear terceros ---------------------------------------------
const modal = reactive({
    open: false,
    loading: false,
    form: {
        id: null,
        cliente: '',
        nombre1: '',
        nombre2: '',
        apellido1: '',
        apellido2: '',
    },
})

function abrirModal() {
    listaClientesFE.value = []
    Object.assign(modal.form, {
        id: null,
        nombre1: '',
        nombre2: '',
        apellido1: '',
        apellido2: '',
    })

    modal.open = true
}

function cerrarModal() {
    modal.open = false
}

// -- Handlers ---------------------------------------------------
function onClienteChange(id) {
    const cliente = opcionesClientes.value.find(o => o.value === id)
    const nombreCliente = formatearNombre(cliente?.nombreCompleto)

    // * Nombre cliente desestructurado
    modal.form.id = id
    modal.form.nombre1 = nombreCliente?.nombre1
    modal.form.nombre2 = nombreCliente?.nombre2
    modal.form.apellido1 = nombreCliente?.apellido1
    modal.form.apellido2 = nombreCliente?.apellido2
}

function añadirClienteFE() {
    if (!modal.form.id) {
        notify.error('No se ha seleccionado ningún cliente.')
        return
    }

    if (listaClientesFE.value.find(c => c.id == modal.form.id)) {
        notify.error('El cliente ya ha sido agregado.')
        return
    }

    const { nombre1, nombre2, apellido1, apellido2 } = modal.form

    const cliente = [nombre1, nombre2, apellido1, apellido2]
        .filter(Boolean)
        .join(' ')

    listaClientesFE.value.push({
        ...modal.form,
        cliente,
        tiposId: 13,
        tiposRegimen: 6,
        clientesDirecciones: 1,
        clientesTipos: '5__Cliente',
    })

    modal.form = {
        id: null,
        nombre1: '',
        nombre2: '',
        apellido1: '',
        apellido2: '',
    }
}

function eliminarFila(index) {
    listaClientesFE.value.splice(index, 1)
}

async function descargarArchivo(url, nombre) {
    const response = await fetch(url, { headers: authHeaders() })
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

function formatearNombre(nombre) {
    const nombreCliente = nombre?.trim()
    const partes = nombreCliente.split(/\s+/)

    let nombre1,
        nombre2,
        apellido1,
        apellido2 = null
    if (partes.length >= 1) nombre1 = partes[0]

    if (partes.length >= 4) {
        nombre2 = partes[1]
        apellido1 = partes[2]
        apellido2 = partes[3]
    } else if (partes.length === 3) {
        nombre2 = partes[1]
        apellido1 = partes[2]
    } else if (partes.length === 2) {
        apellido1 = partes[1]
    }

    return {
        nombre1,
        nombre2,
        apellido1,
        apellido2,
    }
}

function transformAbonos(data) {
    abonos.value = data.map(abono => {
        const totalAbonado =
            Number(abono.abones ?? 0) +
            Number(abono.int_mora ?? 0) +
            Number(abono.gas_cobranza ?? 10)

        return {
            id: abono.abono_id,
            fecha: abono.fecha,
            cedula: abono.cedula,
            nombre: abono.name,
            numCredito: abono.numcredito,
            numAbono: abono.numabono,
            recibidoEn: abono.payed,
            concepto: abono.concept,
            cajera: abono.cajera,
            estadoCredito:
                abono.diasMora > 0
                    ? `En mora (${abono.diasMora} días)`
                    : 'Al día',
            vrTotalAbonado: totalAbonado,
            vrAbonado: abono.abones,
            aval: abono.aval,
            ivaAval: abono.iva_aval,
            intereses: abono.intereses,
            firmaElectronica: abono.firmaElectronica,
            capital: abono.capital,
            intMoratorio: abono.int_mora,
            gasCobranza: abono.gas_cobranza,
            ivaGasCobranza: abono.iva_gas_cobranza,
            valorCondonacion: abono.valorCondonacion,
            empresa: abono.empresa,
        }
    })
}

const {
    search,
    pagination,
    sort,
    onPageChange,
    onPerPageChange,
    onSearch,
    onSort,
} = useDataTable(fetchAbonos, {
    initialSortKey: 'fecha',
})

onMounted(async () => {
    start()

    try {
        await Promise.all([fetchAbonos(), opcionesStore.fetchTipoPago()])
        recibidoEnOpts.value = opcionesStore.tiposPago
    } finally {
        stop()
    }
})
</script>
