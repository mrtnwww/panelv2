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
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                        <path
                            d="M1 2.5h12M1 7h12M1 11.5h12"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                        />
                    </svg>
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
                        <div class="relative shrink-0 mt-0.5">
                            <input
                                v-model="filters.estado"
                                type="checkbox"
                                :value="opt.value"
                                class="peer sr-only"
                            />
                            <div
                                class="w-4 h-4 rounded border-2 border-gray-200 bg-gray-50 peer-checked:bg-[#1a5c2a] peer-checked:border-[#1a5c2a] transition-all flex items-center justify-center"
                            >
                                <svg
                                    v-if="filters.estado.includes(opt.value)"
                                    width="8"
                                    height="8"
                                    viewBox="0 0 8 8"
                                    fill="none"
                                >
                                    <path
                                        d="M1 4L3 6.5L7 1.5"
                                        stroke="white"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>
                        </div>
                        <span
                            class="text-sm text-gray-600 group-hover:text-gray-800 transition-colors leading-tight"
                            >{{ opt.label }}</span
                        >
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
                        <div class="relative shrink-0 mt-0.5">
                            <input
                                v-model="filters.origen"
                                type="checkbox"
                                :value="opt.value"
                                class="peer sr-only"
                            />
                            <div
                                class="w-4 h-4 rounded border-2 border-gray-200 bg-gray-50 peer-checked:bg-[#1a5c2a] peer-checked:border-[#1a5c2a] transition-all flex items-center justify-center"
                            >
                                <svg
                                    v-if="filters.origen.includes(opt.value)"
                                    width="8"
                                    height="8"
                                    viewBox="0 0 8 8"
                                    fill="none"
                                >
                                    <path
                                        d="M1 4L3 6.5L7 1.5"
                                        stroke="white"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>
                        </div>
                        <span
                            class="text-sm text-gray-600 group-hover:text-gray-800 transition-colors leading-tight"
                            >{{ opt.label }}</span
                        >
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
                        <div class="relative shrink-0 mt-0.5">
                            <input
                                v-model="filters.resultado"
                                type="checkbox"
                                :value="opt.value"
                                class="peer sr-only"
                            />
                            <div
                                class="w-4 h-4 rounded border-2 border-gray-200 bg-gray-50 peer-checked:bg-[#1a5c2a] peer-checked:border-[#1a5c2a] transition-all flex items-center justify-center"
                            >
                                <svg
                                    v-if="filters.resultado.includes(opt.value)"
                                    width="8"
                                    height="8"
                                    viewBox="0 0 8 8"
                                    fill="none"
                                >
                                    <path
                                        d="M1 4L3 6.5L7 1.5"
                                        stroke="white"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>
                        </div>
                        <span
                            class="text-sm text-gray-600 group-hover:text-gray-800 transition-colors leading-tight"
                            >{{ opt.label }}</span
                        >
                    </label>
                </div>
            </div>

            <!-- Fila inferior -->
            <div
                class="flex flex-wrap items-end gap-4 pt-3 border-t border-gray-100"
            >
                <div class="flex flex-col gap-1.5 min-w-[200px]">
                    <label
                        class="text-xs font-medium text-gray-400 uppercase tracking-wide"
                        >Aliado</label
                    >
                    <div class="relative">
                        <select
                            v-model="filters.aliado"
                            class="w-full h-9 pl-3 pr-8 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600 outline-none appearance-none focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10 transition-all"
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
                        <span
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"
                        >
                            <svg
                                width="12"
                                height="12"
                                viewBox="0 0 12 12"
                                fill="none"
                            >
                                <path
                                    d="M3 4.5L6 7.5L9 4.5"
                                    stroke="currentColor"
                                    stroke-width="1.3"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label
                        class="text-xs font-medium text-gray-400 uppercase tracking-wide"
                        >Fecha inicial</label
                    >
                    <input
                        v-model="filters.fechaInicial"
                        type="date"
                        class="h-9 px-3 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600 outline-none focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10 transition-all"
                    />
                </div>

                <div class="flex flex-col gap-1.5">
                    <label
                        class="text-xs font-medium text-gray-400 uppercase tracking-wide"
                        >Fecha final</label
                    >
                    <input
                        v-model="filters.fechaFinal"
                        type="date"
                        class="h-9 px-3 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600 outline-none focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10 transition-all"
                    />
                </div>

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
            <!-- Acciones personalizadas en la barra superior -->
        </DataTable>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import DataTable from '@/components/table/DataTable.vue';

const router = useRouter();

// ── Vista ──────────────────────────────────────────────────────────────────
const viewMode = ref('list');

// ── Columnas ───────────────────────────────────────────────────────────────
const columns = [
    { key: 'nombre', label: 'Nombre', sortable: true },
    { key: 'identificacion', label: 'Identificación', sortable: true },
    { key: 'aliado', label: 'Aliado / Sede', sortable: true, truncate: true },
    { key: 'correo', label: 'Correo', sortable: false },
    { key: 'telefono', label: 'Teléfono', sortable: false },
    {
        key: 'fechaRegistro',
        label: 'Fecha registro',
        sortable: true,
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
    { key: 'valorCredito', label: 'Valor crédito', sortable: false },
    { key: 'acciones', label: 'Acciones', sortable: false },
];

// ── Datos y estado ─────────────────────────────────────────────────────────
const clientes = ref([]);
const loading = ref(false);
const search = ref('');
let searchTimeout = null;

const pagination = reactive({
    currentPage: 1,
    perPage: 10,
    total: 0,
});

const sort = reactive({
    key: 'fechaRegistro',
    dir: 'desc',
});

// ── Filtros ────────────────────────────────────────────────────────────────
const filters = reactive({
    estado: [],
    origen: [],
    resultado: [],
    aliado: '',
    fechaInicial: '',
    fechaFinal: '',
});

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
];

const origenOpts = [
    { value: 'formulario_web', label: 'Registro desde formulario web' },
    {
        value: 'validacion_automatica',
        label: 'Validación identidad automática',
    },
];

const resultadoOpts = [
    {
        value: 'credito_aprobado',
        label: 'Crédito aprobado (Pendiente desembolso)',
    },
    { value: 'proceso_finalizado', label: 'Proceso finalizado' },
];

const aliados = [
    { value: 'impulsa', label: 'IMPULSA CORP SAS / CREDITRANSITO' },
    { value: 'cda', label: 'CDA LEBRIJA' },
    { value: 'ampara', label: 'AMPARA SEGUROS Y SERVICIOS S.A.S.' },
];

function resetFilters() {
    filters.estado = [];
    filters.origen = [];
    filters.resultado = [];
    filters.aliado = '';
    filters.fechaInicial = '';
    filters.fechaFinal = '';
    pagination.currentPage = 1;
    fetchClientes();
}

// ── Selección ──────────────────────────────────────────────────────────────
const selected = ref([]);

const allSelected = computed(
    () =>
        clientes.value.length > 0 &&
        clientes.value.every(c => selected.value.includes(c.id))
);

function onToggleAll(checked) {
    selected.value = checked ? clientes.value.map(c => c.id) : [];
}

function onToggleRow(id) {
    const idx = selected.value.indexOf(id);
    if (idx === -1) selected.value.push(id);
    else selected.value.splice(idx, 1);
}

// ── Llamada al backend ─────────────────────────────────────────────────────
async function fetchClientes() {
    loading.value = true;

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
        });

        filters.estado.forEach(v => params.append('estado[]', v));
        filters.origen.forEach(v => params.append('origen[]', v));
        filters.resultado.forEach(v => params.append('resultado[]', v));

        const response = await fetch(`/api/clientes?${params}`, {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
            },
        });

        if (!response.ok) throw new Error('Error al cargar clientes');

        const data = await response.json();

        // Estructura esperada del backend:
        // { data: [...], meta: { total, current_page, per_page } }
        clientes.value = data.data;
        pagination.total = data.meta.total;
        pagination.currentPage = data.meta.current_page;
    } catch (err) {
        console.error(err);
    } finally {
        loading.value = false;
    }
}

// ── Handlers de eventos del DataTable ─────────────────────────────────────
function onPageChange(page) {
    pagination.currentPage = page;
    fetchClientes();
}

function onPerPageChange(val) {
    pagination.perPage = val;
    pagination.currentPage = 1;
    fetchClientes();
}

function onSearch(val) {
    search.value = val;
    // Debounce: espera 400ms antes de llamar al backend
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        pagination.currentPage = 1;
        fetchClientes();
    }, 400);
}

function onSort({ key, dir }) {
    sort.key = key;
    sort.dir = dir;
    pagination.currentPage = 1;
    fetchClientes();
}

// ── Navegación ─────────────────────────────────────────────────────────────
function editCliente(row) {
    router.push(`/dashboard/clientes/${row.id}/editar`);
}

function viewCliente(row) {
    router.push(`/dashboard/clientes/${row.id}`);
}

// ── Carga inicial ──────────────────────────────────────────────────────────
onMounted(fetchClientes);
</script>
