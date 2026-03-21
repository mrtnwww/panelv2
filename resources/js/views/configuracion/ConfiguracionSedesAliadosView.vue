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
                            class="w-10 h-10 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden border border-gray-200"
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

                <!-- Acciones: 3 botones + configuración (tab Aliados/Sedes) -->
                <template #cell-acciones="{ row }">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button
                            @click.stop="gestionarAliado(row)"
                            class="h-7 px-3 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] text-white text-xs font-medium transition-all whitespace-nowrap"
                        >
                            Gestionar aliado
                        </button>
                        <button
                            @click.stop="formularioCliente(row)"
                            class="h-7 px-3 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-medium transition-all whitespace-nowrap"
                        >
                            Formulario cliente
                        </button>
                        <button
                            @click.stop="vigenciaUsuarios(row)"
                            class="h-7 px-3 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium transition-all whitespace-nowrap"
                        >
                            Vigencia usuarios
                        </button>
                        <button
                            @click.stop="abrirModalEditar(row)"
                            class="w-7 h-7 rounded-lg bg-emerald-500 hover:bg-emerald-600 flex items-center justify-center text-white transition-all"
                            title="Configuración"
                        >
                            <svg
                                width="13"
                                height="13"
                                viewBox="0 0 14 14"
                                fill="none"
                            >
                                <path
                                    d="M7 8.5A1.5 1.5 0 1 0 7 5.5a1.5 1.5 0 0 0 0 3z"
                                    stroke="currentColor"
                                    stroke-width="1.2"
                                />
                                <path
                                    d="M11.2 8.5a.9.9 0 0 0 .18 1l.03.03a1.1 1.1 0 0 1-1.56 1.56l-.03-.03a.9.9 0 0 0-1-.18.9.9 0 0 0-.55.83V12a1.1 1.1 0 0 1-2.2 0v-.04a.9.9 0 0 0-.59-.83.9.9 0 0 0-1 .18l-.03.03A1.1 1.1 0 0 1 3.3 9.78l.03-.03a.9.9 0 0 0 .18-1 .9.9 0 0 0-.83-.55H2a1.1 1.1 0 0 1 0-2.2h.04a.9.9 0 0 0 .83-.59.9.9 0 0 0-.18-1l-.03-.03A1.1 1.1 0 0 1 4.22 2.8l.03.03a.9.9 0 0 0 1 .18h.04A.9.9 0 0 0 5.83 2V2a1.1 1.1 0 0 1 2.2 0v.04a.9.9 0 0 0 .55.83.9.9 0 0 0 1-.18l.03-.03A1.1 1.1 0 0 1 11.17 4.2l-.03.03a.9.9 0 0 0-.18 1v.04a.9.9 0 0 0 .83.55H12a1.1 1.1 0 0 1 0 2.2h-.04a.9.9 0 0 0-.83.55z"
                                    stroke="currentColor"
                                    stroke-width="1.2"
                                />
                            </svg>
                        </button>
                    </div>
                </template>

                <!-- Periodicidad con editar inline -->
                <template #cell-periodicidad="{ row }">
                    <div class="flex items-center gap-1.5">
                        <span class="text-sm text-gray-600">{{
                            row.periodicidad || 'Semanal'
                        }}</span>
                        <button
                            @click.stop="editarPeriodicidad(row)"
                            class="text-gray-300 hover:text-[#1a5c2a] transition-colors"
                            title="Editar periodicidad"
                        >
                            <svg
                                width="12"
                                height="12"
                                viewBox="0 0 12 12"
                                fill="none"
                            >
                                <path
                                    d="M8.5 1L11 3.5L4 10.5H1.5V8L8.5 1Z"
                                    stroke="currentColor"
                                    stroke-width="1.1"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </button>
                    </div>
                </template>

                <!-- Vigencia días -->
                <template #cell-vigenciaDias="{ value }">
                    <span class="text-sm text-gray-400">{{
                        value ?? '—'
                    }}</span>
                </template>

                <!-- Acciones para tabs de alianzas -->
                <template #cell-accionesAlianza="{ row }">
                    <div
                        class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity justify-end"
                    >
                        <button
                            @click.stop="verAlianza(row)"
                            class="w-7 h-7 rounded-lg border border-gray-200 flex items-center justify-center text-gray-400 hover:text-[#1a5c2a] hover:border-[#1a5c2a]/30 transition-all"
                            title="Ver"
                        >
                            <svg
                                width="13"
                                height="13"
                                viewBox="0 0 13 13"
                                fill="none"
                            >
                                <ellipse
                                    cx="6.5"
                                    cy="6.5"
                                    rx="5.5"
                                    ry="3.5"
                                    stroke="currentColor"
                                    stroke-width="1.2"
                                />
                                <circle
                                    cx="6.5"
                                    cy="6.5"
                                    r="1.5"
                                    stroke="currentColor"
                                    stroke-width="1.2"
                                />
                            </svg>
                        </button>
                        <button
                            @click.stop="abrirModalEditar(row)"
                            class="w-7 h-7 rounded-lg border border-gray-200 flex items-center justify-center text-gray-400 hover:text-[#1a5c2a] hover:border-[#1a5c2a]/30 transition-all"
                            title="Editar"
                        >
                            <svg
                                width="13"
                                height="13"
                                viewBox="0 0 13 13"
                                fill="none"
                            >
                                <path
                                    d="M9 1.5L11.5 4L4.5 11H2V8.5L9 1.5Z"
                                    stroke="currentColor"
                                    stroke-width="1.2"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </button>
                    </div>
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

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2 flex flex-col gap-1.5">
                            <label :class="labelClass"
                                >Nombre del establecimiento
                                <span class="text-red-400">*</span></label
                            >
                            <input
                                v-model="modal.form.establecimiento"
                                type="text"
                                placeholder="Nombre del aliado o sede"
                                :class="inputClass"
                            />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label :class="labelClass"
                                >Correo electrónico</label
                            >
                            <input
                                v-model="modal.form.email"
                                type="email"
                                placeholder="contacto@empresa.com"
                                :class="inputClass"
                            />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <FormInput
                                label="Periodicidad"
                                type="select"
                                v-model="modal.form.periodicidad"
                                :options="periodicidadOpts"
                                placeholder="Seleccione"
                            />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <FormInput
                                label="Tipo"
                                type="select"
                                v-model="modal.form.tipo"
                                :options="tipoOpts"
                                placeholder="Aliado o Sede"
                            />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label :class="labelClass"
                                >Vigencia usuarios (días)</label
                            >
                            <input
                                v-model="modal.form.vigenciaDias"
                                type="number"
                                min="0"
                                placeholder="—"
                                :class="inputClass"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <FileUpload
                                label="Logo del establecimiento"
                                v-model="modal.form.logo"
                                accept="image/*"
                                accept-label="PNG o JPG — máx. 2MB"
                                placeholder="Sube el logo"
                            />
                        </div>
                    </div>

                    <transition name="fade">
                        <div
                            v-if="modal.error"
                            class="px-3 py-2.5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"
                        >
                            {{ modal.error }}
                        </div>
                    </transition>

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

// -- Componentes -------------------------------------------
import FileUpload from '@/components/form/FileUpload.vue'
import DataTable from '@/components/table/DataTable.vue'
import FormInput from '@/components/form/FormInput.vue'

const router = useRouter()

// ── Tabs ───────────────────────────────────────────────────────────────────
const tabs = [
    { key: 'alianzas_firmadas', label: 'Alianzas firmadas' },
    { key: 'alianzas_pendientes', label: 'Alianzas pendientes por firmar' },
    { key: 'aliados', label: 'Aliados' },
    { key: 'sedes', label: 'Sedes' },
]

const activeTab = ref('aliados')

// ── Columnas según tab ─────────────────────────────────────────────────────
const columnsAliados = [
    { key: 'establecimiento', label: 'Establecimiento', sortable: false },
    { key: 'acciones', label: 'Acciones', sortable: false },
    { key: 'periodicidad', label: 'Periodicidad', sortable: false },
    { key: 'vigenciaDias', label: 'Vigencia usuarios (días)', sortable: false },
]

const columnsAlianzas = [
    { key: 'establecimiento', label: 'Establecimiento', sortable: false },
    { key: 'fechaEnvio', label: 'Fecha envío', sortable: false },
    { key: 'estado', label: 'Estado', sortable: false },
    { key: 'accionesAlianza', label: 'Acciones', sortable: false },
]

const activeColumns = computed(() => {
    if (activeTab.value === 'aliados' || activeTab.value === 'sedes')
        return columnsAliados
    return columnsAlianzas
})

// ── Estado ─────────────────────────────────────────────────────────────────
const registros = ref([])
const loading = ref(false)
const search = ref('')
let searchTimeout = null

const pagination = reactive({ currentPage: 1, perPage: 10, total: 0 })
const sort = reactive({ key: 'establecimiento', dir: 'asc' })

// ── Opciones ───────────────────────────────────────────────────────────────
const periodicidadOpts = [
    { value: 'semanal', label: 'Semanal' },
    { value: 'quincenal', label: 'Quincenal' },
    { value: 'mensual', label: 'Mensual' },
]

const tipoOpts = [
    { value: 'aliado', label: 'Aliado' },
    { value: 'sede', label: 'Sede' },
]

// ── Helpers ────────────────────────────────────────────────────────────────
const labelClass = 'text-xs font-medium text-gray-500 uppercase tracking-wide'
const inputClass =
    'w-full h-9 px-3 rounded-lg border border-gray-200 bg-gray-50 text-[#0A2540] text-sm outline-none transition-all placeholder:text-gray-300 focus:bg-white focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10'

function authHeaders() {
    return {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    }
}

// ── Backend ────────────────────────────────────────────────────────────────
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

        const response = await fetch(`/api/sedes-aliados?${params}`, {
            headers: authHeaders(),
        })
        if (!response.ok) throw new Error()

        const data = await response.json()
        registros.value = data.data
        pagination.total = data.meta.total
        pagination.currentPage = data.meta.current_page
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

// ── Handlers DataTable ─────────────────────────────────────────────────────
function onPageChange(page) {
    pagination.currentPage = page
    fetchRegistros()
}
function onPerPageChange(val) {
    pagination.perPage = val
    pagination.currentPage = 1
    fetchRegistros()
}
function onSort({ key, dir }) {
    sort.key = key
    sort.dir = dir
    pagination.currentPage = 1
    fetchRegistros()
}
function onSearch(val) {
    search.value = val
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        pagination.currentPage = 1
        fetchRegistros()
    }, 400)
}

// ── Acciones de fila ───────────────────────────────────────────────────────
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
    console.log('Editar periodicidad:', row.id)
}
function verAlianza(row) {
    console.log('Ver alianza:', row.id)
}

// ── Modal crear / editar ───────────────────────────────────────────────────
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
    modal.form = {
        id: null,
        establecimiento: '',
        email: '',
        periodicidad: '',
        tipo: '',
        vigenciaDias: '',
        logo: null,
    }
    modal.mode = 'crear'
    modal.error = ''
    modal.open = true
}

function abrirModalEditar(row) {
    modal.form = { ...row, logo: null }
    modal.mode = 'editar'
    modal.error = ''
    modal.open = true
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

        const response = await fetch(
            isEditar
                ? `/api/sedes-aliados/${modal.form.id}`
                : '/api/sedes-aliados',
            {
                method: isEditar ? 'POST' : 'POST',
                headers: authHeaders(),
                body: payload,
            }
        )
        if (!response.ok) throw new Error()
        modal.open = false
        fetchRegistros()
    } catch {
        modal.error = 'No se pudo guardar. Intenta nuevamente.'
    } finally {
        modal.loading = false
    }
}

onMounted(fetchRegistros)
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.15s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.fade-enter-active,
.fade-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
