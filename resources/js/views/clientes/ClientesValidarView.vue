<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <div class="flex items-center gap-3">
            <h1 class="text-lg font-semibold text-[#0A2540]">
                Validar cliente
            </h1>
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
            <!-- Acciones personalizadas en la barra superior -->
        </DataTable>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from '@/components/table/DataTable.vue'

const router = useRouter()

// ── Vista ──────────────────────────────────────────────────────────────────
const viewMode = ref('list')

// ── Columnas ───────────────────────────────────────────────────────────────
const columns = [
    { key: 'nombre', label: 'Nombre', sortable: false },
    { key: 'identificacion', label: 'Identificación', sortable: false },
    {
        key: 'autorizacionConsulta',
        label: 'Autorización consulta en centrales',
        sortable: false,
    },
    { key: 'pagoConsulta', label: 'Pago consulta', sortable: false },
    { key: 'correo', label: 'Correo', sortable: false },
    { key: 'telefono', label: 'Teléfono', sortable: false },
    {
        key: 'fechaRegistro',
        label: 'Fecha registro',
        sortable: false,
        type: 'date',
    },
    { key: 'acciones', label: 'Acciones', sortable: false },
]

// ── Datos y estado ─────────────────────────────────────────────────────────
const clientes = ref([])
const loading = ref(false)
const search = ref('')
let searchTimeout = null

const pagination = reactive({
    currentPage: 1,
    perPage: 10,
    total: 0,
})

const sort = reactive({
    key: 'fechaRegistro',
    dir: 'desc',
})

// ── Filtros ────────────────────────────────────────────────────────────────
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

// ── Selección ──────────────────────────────────────────────────────────────
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
    if (idx === -1) selected.value.push(id)
    else selected.value.splice(idx, 1)
}

// ── Llamada al backend ─────────────────────────────────────────────────────
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

        const response = await fetch(`/api/clientes?${params}`, {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
            },
        })

        if (!response.ok) throw new Error('Error al cargar clientes')

        const data = await response.json()

        // Estructura esperada del backend:
        // { data: [...], meta: { total, current_page, per_page } }
        clientes.value = data.data
        pagination.total = data.meta.total
        pagination.currentPage = data.meta.current_page
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
}

// ── Handlers de eventos del DataTable ─────────────────────────────────────
function onPageChange(page) {
    pagination.currentPage = page
    fetchClientes()
}

function onPerPageChange(val) {
    pagination.perPage = val
    pagination.currentPage = 1
    fetchClientes()
}

function onSearch(val) {
    search.value = val
    // Debounce: espera 400ms antes de llamar al backend
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        pagination.currentPage = 1
        fetchClientes()
    }, 400)
}

function onSort({ key, dir }) {
    sort.key = key
    sort.dir = dir
    pagination.currentPage = 1
    fetchClientes()
}

// ── Navegación ─────────────────────────────────────────────────────────────
function editCliente(row) {
    router.push(`/dashboard/clientes/${row.id}/editar`)
}

function viewCliente(row) {
    router.push(`/dashboard/clientes/${row.id}`)
}

// ── Carga inicial ──────────────────────────────────────────────────────────
onMounted(fetchClientes)
</script>
