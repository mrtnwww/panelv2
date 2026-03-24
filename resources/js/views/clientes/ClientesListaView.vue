<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <div class="flex items-center gap-3">
            <h1 class="text-lg font-semibold text-[#0A2540]">
                Lista de clientes
            </h1>
            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                <button
                    @click="viewMode = 'list'"
                    :class="[
                        'w-7 h-7 rounded-md flex items-center justify-center transition-all',
                        viewMode === 'list'
                            ? 'bg-[#1a5c2a] text-white shadow-sm'
                            : 'text-gray-400 hover:text-gray-600',
                    ]"
                >
                    <i class="fa-solid fa-bars text-xs"></i>
                </button>
                <button
                    @click="viewMode = 'grid'"
                    :class="[
                        'w-7 h-7 rounded-md flex items-center justify-center transition-all',
                        viewMode === 'grid'
                            ? 'bg-[#1a5c2a] text-white shadow-sm'
                            : 'text-gray-400 hover:text-gray-600',
                    ]"
                >
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                        <rect
                            x="1"
                            y="1"
                            width="5"
                            height="5"
                            rx="1"
                            stroke="currentColor"
                            stroke-width="1.5"
                        />
                        <rect
                            x="8"
                            y="1"
                            width="5"
                            height="5"
                            rx="1"
                            stroke="currentColor"
                            stroke-width="1.5"
                        />
                        <rect
                            x="1"
                            y="8"
                            width="5"
                            height="5"
                            rx="1"
                            stroke="currentColor"
                            stroke-width="1.5"
                        />
                        <rect
                            x="8"
                            y="8"
                            width="5"
                            height="5"
                            rx="1"
                            stroke="currentColor"
                            stroke-width="1.5"
                        />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Panel de filtros -->
        <div
            class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-5"
        >
            <p class="text-sm font-medium text-gray-500">Filtro de clientes</p>

            <!-- Grupos de checkboxes -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Estado -->
                <div class="flex flex-col gap-2">
                    <p
                        class="text-xs font-semibold text-gray-400 uppercase tracking-wide"
                    >
                        Estado
                    </p>
                    <label
                        v-for="opt in estadoOpts"
                        :key="opt.value"
                        class="flex items-start gap-2.5 cursor-pointer group"
                    >
                        <FormCheckbox
                            v-model="filters.estado"
                            :value="opt.value"
                            wrapper-class="mt-0.5"
                        />
                        <span
                            class="text-sm text-gray-600 group-hover:text-gray-800 transition-colors leading-tight"
                        >
                            {{ opt.label }}
                        </span>
                    </label>
                </div>

                <!-- Origen -->
                <div class="flex flex-col gap-2">
                    <p
                        class="text-xs font-semibold text-gray-400 uppercase tracking-wide"
                    >
                        Origen
                    </p>
                    <label
                        v-for="opt in origenOpts"
                        :key="opt.value"
                        class="flex items-start gap-2.5 cursor-pointer group"
                    >
                        <FormCheckbox
                            v-model="filters.origen"
                            :value="opt.value"
                            wrapper-class="mt-0.5"
                        />
                        <span
                            class="text-sm text-gray-600 group-hover:text-gray-800 transition-colors leading-tight"
                        >
                            {{ opt.label }}
                        </span>
                    </label>
                </div>

                <!-- Resultado -->
                <div class="flex flex-col gap-2">
                    <p
                        class="text-xs font-semibold text-gray-400 uppercase tracking-wide"
                    >
                        Resultado
                    </p>
                    <label
                        v-for="opt in resultadoOpts"
                        :key="opt.value"
                        class="flex items-start gap-2.5 cursor-pointer group"
                    >
                        <FormCheckbox
                            v-model="filters.resultado"
                            :value="opt.value"
                            wrapper-class="mt-0.5"
                        />
                        <span
                            class="text-sm text-gray-600 group-hover:text-gray-800 transition-colors leading-tight"
                        >
                            {{ opt.label }}
                        </span>
                    </label>
                </div>
            </div>

            <!-- Fila inferior -->
            <div
                class="flex flex-wrap items-end gap-4 pt-3 border-t border-gray-100"
            >
                <FormInput
                    label="Aliado"
                    type="select"
                    v-model="filters.aliado"
                    :options="aliados"
                    placeholder="Seleccione un aliado"
                    wrapper-class="min-w-[200px]"
                />

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

                <div class="flex items-center gap-2 ml-auto">
                    <button
                        @click="resetFilters"
                        class="h-9 px-4 rounded-lg border border-gray-200 text-sm text-gray-500 hover:bg-gray-50 hover:border-gray-300 transition-all"
                    >
                        Limpiar
                    </button>
                    <button
                        @click="fetchClientes"
                        class="h-9 px-4 rounded-lg bg-[#1a5c2a] text-white text-sm font-medium hover:bg-[#154d22] transition-all"
                    >
                        Aplicar filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- DataTable -->
        <DataTable
            :rows="clientes"
            :columns="columns"
            :loading="loading"
            :total="pagination.total"
            :current-page="pagination.currentPage"
            :per-page="pagination.perPage"
            :sort-key="sort.key"
            :sort-dir="sort.dir"
            :search="search"
            :selectable="true"
            :selected-rows="selected"
            :all-selected="allSelected"
            empty-message="No se encontraron clientes con los filtros aplicados."
            @update:current-page="onPageChange"
            @update:per-page="onPerPageChange"
            @update:search="onSearch"
            @sort="onSort"
            @toggle-all="onToggleAll"
            @toggle-row="onToggleRow"
        >
            <!-- Celda acciones -->
            <template #cell-acciones="{ row }">
                <div
                    class="flex items-center gap-2 justify-end opacity-0 group-hover:opacity-100 transition-opacity"
                >
                    <button
                        @click.stop="editCliente(row)"
                        class="w-7 h-7 rounded-lg border border-gray-200 flex items-center justify-center text-gray-400 hover:text-[#1a5c2a] hover:border-[#1a5c2a]/30 transition-all"
                        title="Editar"
                    >
                        <i class="fa-solid fa-pencil"></i>
                    </button>
                    <button
                        @click.stop="viewCliente(row)"
                        class="w-7 h-7 rounded-lg border border-gray-200 flex items-center justify-center text-gray-400 hover:text-blue-500 hover:border-blue-200 transition-all"
                        title="Ver historico"
                    >
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </template>
        </DataTable>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

// -- Componentes -------------------------------------------
import FormCheckbox from '@/components/form/FormCheckbox.vue'
import DataTable from '@/components/table/DataTable.vue'
import FormInput from '@/components/form/FormInput.vue'

import { formatCurrency } from '@/utils/format'
import api from '@/services/api'

const router = useRouter()

// -- Vista ------------------------------------------------------------------
const viewMode = ref('list')

// -- Columnas ------------------------------------------------------------------
const columns = [
    { key: 'nombre', label: 'Nombre', sortable: false },
    { key: 'identificacion', label: 'Identificación', sortable: false },
    { key: 'aliado', label: 'Aliado / Sede', sortable: false, truncate: true },
    { key: 'correo', label: 'Correo', sortable: false },
    { key: 'telefono', label: 'Teléfono', sortable: false },
    {
        key: 'fechaRegistro',
        label: 'Fecha registro',
        sortable: false,
        type: 'date',
    },
    {
        key: 'autorizacionCentrales',
        label: 'Autorización centrales',
        sortable: false,
        type: 'boolean',
        align: 'center',
    },
    {
        key: 'validacionDatos',
        label: 'Validación datos',
        sortable: false,
        type: 'boolean',
        align: 'center',
    },
    {
        key: 'resultado',
        label: 'Resultado',
        sortable: false,
        type: 'boolean',
        align: 'center',
    },
    {
        key: 'fotoCliente',
        label: 'Foto cliente',
        sortable: false,
        type: 'boolean',
        align: 'center',
    },
    { key: 'valorCredito', label: 'Valor ult. crédito', sortable: false },
    { key: 'acciones', label: 'Acciones', sortable: false },
]

// -- Estado ------------------------------------------------------------------
const clientes = ref([])
const loading = ref(false)
const search = ref('')
let searchTimeout = null

const pagination = reactive({ currentPage: 1, perPage: 10, total: 0 })
const sort = reactive({ key: 'fechaRegistro', dir: 'desc' })

// --  Filtros ------------------------------------------------------------------
const filters = reactive({
    estado: [],
    origen: [],
    resultado: [],
    aliado: '',
    fechaInicial: '',
    fechaFinal: '',
})

const estadoOpts = [
    {
        value: 'pendiente_identidad',
        label: 'Pendiente por validación identidad',
    },
    {
        value: 'pendiente_autorizacion',
        label: 'Pendiente por autorización consulta en centrales',
    },
    {
        value: 'pendiente_centrales',
        label: 'Pendiente por consulta centrales de riesgo',
    },
    { value: 'pendiente_foto', label: 'Pendiente foto del cliente' },
]

const origenOpts = [
    { value: 'formulario_web', label: 'Registro desde formulario web' },
    {
        value: 'validacion_automatica',
        label: 'Validación identidad automática',
    },
]

const resultadoOpts = [
    {
        value: 'credito_aprobado',
        label: 'Crédito aprobado (Pendiente desembolso)',
    },
    { value: 'proceso_finalizado', label: 'Proceso finalizado' },
]

const aliados = [
    { value: 'impulsa', label: 'IMPULSA CORP SAS / CREDITRANSITO' },
    { value: 'cda', label: 'CDA LEBRIJA' },
    { value: 'ampara', label: 'AMPARA SEGUROS Y SERVICIOS S.A.S.' },
]

function resetFilters() {
    filters.estado = []
    filters.origen = []
    filters.resultado = []
    filters.aliado = ''
    filters.fechaInicial = ''
    filters.fechaFinal = ''
    pagination.currentPage = 1
    fetchClientes()
}

// -- Selección -------------------------------------------------------
const selected = ref([])

const allSelected = computed(
    () =>
        clientes.value.length > 0 &&
        clientes.value.every(c => selected.value.includes(c.id))
)

function onToggleAll(checked) {
    selected.value = checked ? clientes.value.map(c => c.id) : []
}

function onToggleRow(id) {
    const idx = selected.value.indexOf(id)
    idx === -1 ? selected.value.push(id) : selected.value.splice(idx, 1)
}

// -- Backend ----------------------------------------------------------------
async function fetchClientes() {
    loading.value = true
    try {
        const params = new URLSearchParams({
            page: pagination.currentPage,
            per_page: pagination.perPage,
            sort_key: sort.key,
            sort_dir: sort.dir,
            search: search.value,
            ...(filters.aliado && { aliado: filters.aliado }),
            ...(filters.fechaInicial && {
                fecha_inicial: filters.fechaInicial,
            }),
            ...(filters.fechaFinal && { fecha_final: filters.fechaFinal }),
        })

        filters.estado.forEach(v => params.append('estado[]', v))
        filters.origen.forEach(v => params.append('origen[]', v))
        filters.resultado.forEach(v => params.append('resultado[]', v))

        const { data } = await api.get('/api/clientes', { params })

        const { data: clientesData, total, current_page } = data.clients

        // Lista de clientes
        transformClients(clientesData)

        // Datos de paginación
        pagination.currentPage = current_page
        pagination.total = total
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
}

// -- Handlers DataTable ------------------------------------------------
function onPageChange(page) {
    pagination.currentPage = page
    fetchClientes()
}
function onPerPageChange(val) {
    pagination.perPage = val
    pagination.currentPage = 1
    fetchClientes()
}
function onSort({ key, dir }) {
    sort.key = key
    sort.dir = dir
    pagination.currentPage = 1
    fetchClientes()
}
function onSearch(val) {
    search.value = val
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        pagination.currentPage = 1
        fetchClientes()
    }, 400)
}

// -- Navegación -----------------------------------------------------
function editCliente(row) {
    router.push(`/dashboard/clientes/${row.id}/editar`)
}
function viewCliente(row) {
    router.push(`/dashboard/clientes/${row.id}`)
}

// -- Transformar clientes
function transformClients(data) {
    clientes.value = data.map(({ cliente, empresa }) => ({
        id: cliente.id,
        nombre: cliente.nombre,
        identificacion: cliente.cedula,
        aliado: empresa?.razon_social,
        correo: cliente.email,
        telefono: cliente.telefono,
        fechaRegistro: cliente.fecha_creacion,
        autorizacionCentrales: cliente.autorizacion,
        validacionDatos: cliente.cliente_validado,
        resultado: cliente.estado_aval,
        fotoCliente: cliente.comprobar_cliente,
        valorCredito: formatCurrency(cliente.ult_credito_valor),
    }))
}

onMounted(fetchClientes)
</script>
