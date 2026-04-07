<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-[#0A2540]">Sedes/aliados</h1>
            <button
                @click="abrirModalCrear"
                class="h-9 px-4 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] text-white text-sm font-medium transition-all flex items-center gap-2"
            >
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                    <path
                        d="M6.5 1v11M1 6.5h11"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                    />
                </svg>
                Crear sedes/aliados
            </button>
        </div>

        <!-- Contenedor con tabs + tabla -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <!-- Tabs -->
            <div
                class="flex border-b border-gray-100 overflow-x-auto overflow-y-hidden"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="cambiarTab(tab.key)"
                    :class="[
                        'px-5 py-3.5 text-sm font-medium whitespace-nowrap transition-all border-b-2 -mb-px',
                        activeTab === tab.key
                            ? 'border-[#1a5c2a] text-[#1a5c2a]'
                            : 'border-transparent text-gray-400 hover:text-gray-600',
                    ]"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- DataTable -->
            <DataTable
                :rows="registros"
                :columns="activeColumns"
                :loading="loading"
                :total="pagination.total"
                :current-page="pagination.currentPage"
                :per-page="pagination.perPage"
                :sort-key="sort.key"
                :sort-dir="sort.dir"
                :search="search"
                empty-message="No se encontraron registros."
                @update:current-page="onPageChange"
                @update:per-page="onPerPageChange"
                @update:search="onSearch"
                @sort="onSort"
            >
                <!-- Establecimiento con logo + email -->
                <template #cell-establecimiento="{ row }">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-lg bg-gray-100 shrink-0 overflow-hidden border border-gray-200"
                        >
                            <img
                                v-if="row.logo"
                                :src="row.logo"
                                :alt="row.establecimiento"
                                class="w-full h-full object-cover"
                            />
                            <div
                                v-else
                                class="w-full h-full flex items-center justify-center"
                            >
                                <svg
                                    width="18"
                                    height="18"
                                    viewBox="0 0 18 18"
                                    fill="none"
                                    class="text-gray-300"
                                >
                                    <path
                                        d="M2 7L9 2L16 7V16H12V11H6V16H2V7Z"
                                        stroke="currentColor"
                                        stroke-width="1.3"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>
                        </div>
                        <div class="flex flex-col leading-tight min-w-0">
                            <span
                                class="text-sm font-semibold text-[#0A2540] truncate"
                                >{{ row.establecimiento }}</span
                            >
                            <span class="text-xs text-gray-400 truncate">{{
                                row.email
                            }}</span>
                        </div>
                    </div>
                </template>

                <!-- Acciones aliados/sedes -->
                <template #cell-acciones="{ row }">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button
                            @click.stop="gestionarAliado(row)"
                            class="btn btn-main"
                        >
                            Gestionar empresa
                        </button>
                        <button
                            @click.stop="formularioCliente(row)"
                            class="btn btn-primary"
                        >
                            Formulario cliente
                        </button>
                        <button
                            @click.stop="vigenciaUsuarios(row)"
                            class="btn btn-secondary"
                        >
                            Vigencia usuarios
                        </button>
                        <button
                            @click.stop="panelFunciones(row)"
                            class="btn btn-primary"
                            title="Panel de funciones"
                        >
                            <i class="fa-solid fa-gear"></i>
                        </button>
                    </div>
                </template>

                <!-- Periodicidad con editar inline -->
                <template #cell-periodicidad="{ row }">
                    <div class="flex items-center gap-1.5">
                        <template v-if="editandoId === row.id">
                            <FormInput
                                v-model="periodicidadTemp"
                                type="select"
                                :options="periodicidadOpts"
                            />

                            <button
                                @click.stop="guardarPeriodicidad(row)"
                                class="text-green-600 hover:text-green-700"
                            >
                                <i class="fa-solid fa-floppy-disk"></i>
                            </button>

                            <button
                                @click.stop="editandoId = null"
                                class="text-red-500 hover:text-red-600"
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </template>

                        <template v-else>
                            <span class="text-sm text-gray-600">{{
                                row.periodicidad || 'Semanal'
                            }}</span>
                            <button
                                @click.stop="editarPeriodicidad(row)"
                                class="text-gray-300 hover:text-[#1a5c2a] transition-colors"
                                title="Editar periodicidad"
                            >
                                <i class="fa-solid fa-pencil"></i>
                            </button>
                        </template>
                    </div>
                </template>

                <!-- Vigencia días -->
                <template #cell-vigenciaDias="{ value }">
                    <span class="text-sm text-gray-400">{{
                        value ?? '—'
                    }}</span>
                </template>
            </DataTable>
        </div>

        <!-- ── Modal crear / editar ── -->
        <transition name="modal">
            <div
                v-if="modal.open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
                @click.self="modal.open = false"
            >
                <div
                    class="bg-white rounded-2xl w-full max-w-lg p-6 flex flex-col gap-5 shadow-xl"
                >
                    <!-- Cabecera modal -->
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold text-[#0A2540]">
                            {{
                                modal.mode === 'crear'
                                    ? 'Nueva sede / aliado'
                                    : 'Editar sede / aliado'
                            }}
                        </h2>
                        <button
                            @click="modal.open = false"
                            class="text-gray-300 hover:text-gray-500 transition-colors"
                        >
                            <svg
                                width="18"
                                height="18"
                                viewBox="0 0 18 18"
                                fill="none"
                            >
                                <path
                                    d="M3 3L15 15M15 3L3 15"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Campos -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <FormInput
                            label="Nombre del establecimiento"
                            v-model="modal.form.establecimiento"
                            placeholder="Nombre del aliado o sede"
                            :required="true"
                            wrapper-class="sm:col-span-2"
                        />
                        <FormInput
                            label="Correo electrónico"
                            type="email"
                            v-model="modal.form.email"
                            placeholder="contacto@empresa.com"
                        />
                        <FormInput
                            label="Periodicidad"
                            type="select"
                            v-model="modal.form.periodicidad"
                            :options="periodicidadOpts"
                            placeholder="Seleccione"
                        />
                        <FormInput
                            label="Tipo"
                            type="select"
                            v-model="modal.form.tipo"
                            :options="tipoOpts"
                            placeholder="Aliado o Sede"
                        />
                        <FormInput
                            label="Vigencia usuarios (días)"
                            type="number"
                            v-model="modal.form.vigenciaDias"
                            placeholder="—"
                        />
                        <FormInput
                            label="Logo del establecimiento"
                            type="file"
                            v-model="modal.form.logo"
                            accept="image/*"
                            button-label="Subir logo"
                            placeholder="PNG o JPG — máx. 2MB"
                            wrapper-class="sm:col-span-2"
                        />
                    </div>

                    <!-- Error -->
                    <transition name="fade">
                        <div
                            v-if="modal.error"
                            class="px-3 py-2.5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"
                        >
                            {{ modal.error }}
                        </div>
                    </transition>

                    <!-- Acciones modal -->
                    <div class="flex justify-end gap-2 pt-1">
                        <button
                            @click="modal.open = false"
                            class="h-9 px-4 rounded-lg border border-gray-200 text-sm text-gray-500 hover:bg-gray-50 transition-all"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="guardar"
                            :disabled="modal.loading"
                            class="h-9 px-5 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] disabled:bg-gray-300 text-white text-sm font-medium transition-all flex items-center gap-2"
                        >
                            <svg
                                v-if="modal.loading"
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
                            {{
                                modal.mode === 'crear'
                                    ? 'Crear'
                                    : 'Guardar cambios'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

// -- Componentes ------------------------------------------------------
import FormInput from '@/components/form/FormInput.vue'

import DataTable from '@/components/table/DataTable.vue'

// -- Loader -----------------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

// -- DataTable --------------------------------------------------------
import { useDataTable } from '@/composables/useDataTable'

import api from '@/services/api'

const router = useRouter()

// -- Tabs --------------------------------------------------------------
const tabs = [
    { key: 'alianzas_firmadas', label: 'Alianzas firmadas' },
    { key: 'alianzas_pendientes', label: 'Alianzas pendientes por firmar' },
    { key: 'aliados', label: 'Aliados' },
    { key: 'sedes', label: 'Sedes' },
]

const activeTab = ref('aliados')

// -- Columnas según tab ------------------------------------------------
const columnsAliados = [
    { key: 'establecimiento', label: 'Establecimiento', sortable: false },
    { key: 'acciones', label: 'Acciones', sortable: false },
    { key: 'periodicidad', label: 'Periodicidad', sortable: false },
    { key: 'vigenciaDias', label: 'Vigencia usuarios (días)', sortable: false },
]

const columnsAlianzas = [
    { key: 'establecimiento', label: 'Establecimiento', sortable: false },
    { key: 'contacto', label: 'Contacto', sortable: false },
    { key: 'registro', label: 'Registro', sortable: false },
    { key: 'fechaFirma', label: 'Fecha de firma', sortable: false },
]

const activeColumns = computed(() =>
    activeTab.value === 'aliados' || activeTab.value === 'sedes'
        ? columnsAliados
        : columnsAlianzas
)

// -- Estado ----------------------------------------------------------
const registros = ref([])
const loading = ref(false)

const editandoId = ref(null)
const periodicidadTemp = ref(null)

// -- Opciones ---------------------------------------------------------
const periodicidadOpts = [
    { value: 'Semanal', label: 'Semanal' },
    { value: 'Quincenal', label: 'Quincenal' },
    { value: 'Mensual', label: 'Mensual' },
]

const tipoOpts = [
    { value: 'aliado', label: 'Aliado' },
    { value: 'sede', label: 'Sede' },
]

// -- Backend -----------------------------------------------------------
async function fetchRegistros() {
    loading.value = true
    try {
        const params = new URLSearchParams({
            page: pagination.currentPage,
            per_page: pagination.perPage,
            sort_key: sort.key,
            sort_dir: sort.dir,
            search: search.value,
            tab: activeTab.value,
        })

        const { data } = await api.get('/api/empresas', { params })

        const { data: empresasData, total, current_page } = data.empresas

        // Lista de empresas
        transformEmpresas(empresasData)

        pagination.total = total
        pagination.currentPage = current_page
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
}

function cambiarTab(key) {
    activeTab.value = key
    pagination.currentPage = 1
    fetchRegistros()
}

// -- Acciones de fila ----------------------------------------------
function gestionarAliado(row) {
    router.push(`/dashboard/configuracion/sedes-aliados/${row.id}`)
}
function formularioCliente(row) {
    window.open(`/formulario-cliente/${row.id}`, '_blank')
}
function vigenciaUsuarios(row) {
    console.log('Vigencia usuarios:', row.id)
}
function editarPeriodicidad(row) {
    editandoId.value = row.id
    periodicidadTemp.value = row.periodicidad || 'Semanal'
}

// -- Modal crear / editar ------------------------------------------
const modal = reactive({
    open: false,
    mode: 'crear',
    loading: false,
    error: '',
    form: {
        id: null,
        establecimiento: '',
        email: '',
        periodicidad: '',
        tipo: '',
        vigenciaDias: '',
        logo: null,
    },
})

function abrirModalCrear() {
    Object.assign(modal.form, {
        id: null,
        establecimiento: '',
        email: '',
        periodicidad: '',
        tipo: '',
        vigenciaDias: '',
        logo: null,
    })
    modal.mode = 'crear'
    modal.error = ''
    modal.open = true
}

function panelFunciones(row) {
    // TODO
}

async function guardar() {
    if (!modal.form.establecimiento) {
        modal.error = 'El nombre del establecimiento es requerido.'
        return
    }
    modal.loading = true
    modal.error = ''
    try {
        const isEditar = modal.mode === 'editar'
        const payload = new FormData()
        Object.entries(modal.form).forEach(([k, v]) => {
            if (k === 'logo' && v) payload.append('logo', v)
            else if (k !== 'logo') payload.append(k, v ?? '')
        })

        //

        modal.open = false
        fetchRegistros()
    } catch {
        modal.error = 'No se pudo guardar. Intenta nuevamente.'
    } finally {
        modal.loading = false
    }
}

function transformEmpresas(data) {
    if (activeTab.value == 'sedes' || activeTab.value == 'aliados') {
        registros.value = data.map(r => ({
            id: r.id,
            establecimiento: r.razon_social,
            email: r.correo ?? '',
            periodicidad: r.periodicidad_empresa ?? 'Semanal',
        }))
    } else {
        registros.value = data.map(r => ({
            establecimiento: `${r.nombre} ( ${r.nit} )`,
            contacto:
                r.nombreContacto && r.numContacto
                    ? `${r.nombreContacto} - ${r.numContacto}`
                    : '- -',
            registro: r.created_at,
            fechaFirma: r.firmado ? r.firmado : '',
        }))
    }
}

const {
    search,
    pagination,
    sort,
    onPageChange,
    onPerPageChange,
    onSearch,
    onSort,
} = useDataTable(fetchRegistros)

onMounted(async () => {
    start()

    try {
        await fetchRegistros()
    } finally {
        stop()
    }
})
</script>
