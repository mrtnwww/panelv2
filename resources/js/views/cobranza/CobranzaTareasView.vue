<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">Gestión de tareas</h1>

        <!-- Panel de filtros -->
        <div
            class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-4"
        >
            <!-- Fila 1: fechas creación + vencimiento -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label :class="labelClass"
                        >Fecha
                        <strong class="font-semibold text-gray-500"
                            >creación</strong
                        >
                        desde</label
                    >
                    <input
                        v-model="filters.creacionDesde"
                        type="date"
                        :class="inputClass"
                    />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label :class="labelClass"
                        >Fecha
                        <strong class="font-semibold text-gray-500"
                            >creación</strong
                        >
                        hasta</label
                    >
                    <input
                        v-model="filters.creacionHasta"
                        type="date"
                        :class="inputClass"
                    />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label :class="labelClass"
                        >Fecha
                        <strong class="font-semibold text-gray-500"
                            >vencimiento</strong
                        >
                        desde</label
                    >
                    <input
                        v-model="filters.vencimientoDesde"
                        type="date"
                        :class="inputClass"
                    />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label :class="labelClass"
                        >Fecha
                        <strong class="font-semibold text-gray-500"
                            >vencimiento</strong
                        >
                        hasta</label
                    >
                    <input
                        v-model="filters.vencimientoHasta"
                        type="date"
                        :class="inputClass"
                    />
                </div>
            </div>

            <!-- Fila 2: fechas completada + cliente + usuario -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label :class="labelClass"
                        >Fecha
                        <strong class="font-semibold text-gray-500"
                            >completada</strong
                        >
                        desde</label
                    >
                    <input
                        v-model="filters.completadaDesde"
                        type="date"
                        :class="inputClass"
                    />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label :class="labelClass"
                        >Fecha
                        <strong class="font-semibold text-gray-500"
                            >completada</strong
                        >
                        hasta</label
                    >
                    <input
                        v-model="filters.completadaHasta"
                        type="date"
                        :class="inputClass"
                    />
                </div>
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
                <div class="flex flex-col gap-1.5">
                    <label :class="labelClass">Usuario asignado</label>
                    <div class="relative">
                        <select
                            v-model="filters.usuarioAsignado"
                            :class="[
                                inputClass,
                                'appearance-none pr-8 cursor-pointer',
                            ]"
                        >
                            <option value="">Seleccione el usuario</option>
                            <option
                                v-for="u in usuariosOpts"
                                :key="u.value"
                                :value="u.value"
                            >
                                {{ u.label }}
                            </option>
                        </select>
                        <ChevronIcon />
                    </div>
                </div>
            </div>

            <!-- Fila 3: tipo de tarea + botones -->
            <div class="flex flex-wrap items-end gap-4">
                <div class="flex flex-col gap-1.5 min-w-[200px]">
                    <label :class="labelClass">Tipo de tarea</label>
                    <div class="relative">
                        <select
                            v-model="filters.tipoTarea"
                            :class="[
                                inputClass,
                                'appearance-none pr-8 cursor-pointer',
                            ]"
                        >
                            <option value="">Seleccionar tipo</option>
                            <option
                                v-for="t in tipoTareaOpts"
                                :key="t.value"
                                :value="t.value"
                            >
                                {{ t.label }}
                            </option>
                        </select>
                        <ChevronIcon />
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:ml-0">
                    <button
                        @click="abrirModalCrear"
                        class="h-9 px-4 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] text-white text-sm font-medium transition-all flex items-center gap-2"
                    >
                        <svg
                            width="13"
                            height="13"
                            viewBox="0 0 13 13"
                            fill="none"
                        >
                            <path
                                d="M6.5 1v11M1 6.5h11"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-linecap="round"
                            />
                        </svg>
                        Crear tarea
                    </button>
                    <button
                        @click="iniciarTarea"
                        :disabled="selected.length === 0"
                        class="h-9 px-4 rounded-lg bg-yellow-500 hover:bg-yellow-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-medium transition-all"
                    >
                        Iniciar tarea
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-xl border border-gray-200">
            <div
                class="flex border-b border-gray-100 overflow-x-auto overflow-y-hidden"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="cambiarTab(tab.key)"
                    :class="[
                        'flex items-center gap-2 px-5 py-3.5 text-sm font-medium whitespace-nowrap transition-all border-b-2 -mb-px',
                        activeTab === tab.key
                            ? 'border-[#1a5c2a] text-[#1a5c2a]'
                            : 'border-transparent text-gray-400 hover:text-gray-600',
                    ]"
                >
                    {{ tab.label }}
                    <span
                        :class="[
                            'text-xs px-1.5 py-0.5 rounded-full font-semibold',
                            activeTab === tab.key
                                ? 'bg-[#1a5c2a]/10 text-[#1a5c2a]'
                                : 'bg-gray-100 text-gray-400',
                        ]"
                    >
                        {{ tabCounts[tab.key] ?? 0 }}
                    </span>
                </button>
            </div>

            <!-- DataTable dentro de la tarjeta de tabs -->
            <DataTable
                :rows="tareas"
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
                empty-message="No se encontraron tareas con los filtros aplicados."
                @update:current-page="onPageChange"
                @update:per-page="onPerPageChange"
                @update:search="onSearch"
                @sort="onSort"
                @toggle-all="onToggleAll"
                @toggle-row="onToggleRow"
            >
                <!-- Estado badge -->
                <template #cell-estado="{ value }">
                    <span
                        :class="[
                            'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap',
                            estadoBadge(value),
                        ]"
                    >
                        {{ value }}
                    </span>
                </template>

                <!-- Título truncado -->
                <template #cell-titulo="{ value }">
                    <span
                        class="text-[#0A2540] font-medium max-w-[180px] truncate block"
                        :title="value"
                    >
                        {{ value }}
                    </span>
                </template>
            </DataTable>
        </div>

        <!-- ── Modal crear tarea ── -->
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
                            Nueva tarea
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
                            <label :class="modalLabelClass"
                                >Título
                                <span class="text-red-400">*</span></label
                            >
                            <input
                                v-model="modal.form.titulo"
                                type="text"
                                placeholder="Descripción de la tarea"
                                :class="inputClass"
                            />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label :class="modalLabelClass">Cliente</label>
                            <div class="relative">
                                <select
                                    v-model="modal.form.cliente"
                                    :class="[
                                        inputClass,
                                        'appearance-none pr-8 cursor-pointer',
                                    ]"
                                >
                                    <option value="">
                                        Seleccione un cliente
                                    </option>
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
                        <div class="flex flex-col gap-1.5">
                            <label :class="modalLabelClass">Asignar a</label>
                            <div class="relative">
                                <select
                                    v-model="modal.form.usuarioAsignado"
                                    :class="[
                                        inputClass,
                                        'appearance-none pr-8 cursor-pointer',
                                    ]"
                                >
                                    <option value="">
                                        Seleccione el usuario
                                    </option>
                                    <option
                                        v-for="u in usuariosOpts"
                                        :key="u.value"
                                        :value="u.value"
                                    >
                                        {{ u.label }}
                                    </option>
                                </select>
                                <ChevronIcon />
                            </div>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label :class="modalLabelClass"
                                >Tipo de tarea</label
                            >
                            <div class="relative">
                                <select
                                    v-model="modal.form.tipoTarea"
                                    :class="[
                                        inputClass,
                                        'appearance-none pr-8 cursor-pointer',
                                    ]"
                                >
                                    <option value="">Seleccionar tipo</option>
                                    <option
                                        v-for="t in tipoTareaOpts"
                                        :key="t.value"
                                        :value="t.value"
                                    >
                                        {{ t.label }}
                                    </option>
                                </select>
                                <ChevronIcon />
                            </div>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label :class="modalLabelClass"
                                >Fecha vencimiento</label
                            >
                            <input
                                v-model="modal.form.vencimiento"
                                type="date"
                                :class="inputClass"
                            />
                        </div>
                        <div class="sm:col-span-2 flex flex-col gap-1.5">
                            <label :class="modalLabelClass">Nota</label>
                            <textarea
                                v-model="modal.form.nota"
                                rows="3"
                                placeholder="Observaciones..."
                                class="w-full px-3 py-2.5 rounded-lg border border-gray-200 bg-gray-50 text-sm text-[#0A2540] resize-none outline-none transition-all placeholder:text-gray-300 focus:bg-white focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10"
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
                            @click="guardarTarea"
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
                            Crear tarea
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import DataTable from '@/components/table/DataTable.vue'

// ── Columnas ───────────────────────────────────────────────────────────────
const columns = [
    { key: 'estado', label: 'Estado', sortable: true },
    { key: 'titulo', label: 'Título', sortable: true },
    { key: 'cliente', label: 'Cliente', sortable: true },
    { key: 'asignadoA', label: 'Asignado a', sortable: true },
    { key: 'fechaCreacion', label: 'Fecha creación', sortable: true },
    { key: 'fechaVencimiento', label: 'Fecha vencimiento', sortable: true },
    { key: 'fechaCompletada', label: 'Fecha completada', sortable: true },
    { key: 'tipoTarea', label: 'Tipo tarea', sortable: false },
    { key: 'ultimaInteraccion', label: 'Última interacción', sortable: false },
]

// ── Tabs ───────────────────────────────────────────────────────────────────
const tabs = [
    { key: 'todas', label: 'Todas' },
    { key: 'completadas', label: 'Completadas' },
    { key: 'vencidas', label: 'Vencidas' },
    { key: 'vencen_hoy', label: 'Vencen hoy' },
    { key: 'proximas', label: 'Próximas' },
]

const activeTab = ref('todas')
const tabCounts = ref({
    todas: 0,
    completadas: 0,
    vencidas: 0,
    vencen_hoy: 0,
    proximas: 0,
})

// ── Estado ─────────────────────────────────────────────────────────────────
const tareas = ref([])
const loading = ref(false)
const search = ref('')
const selected = ref([])
let searchTimeout = null

const pagination = reactive({ currentPage: 1, perPage: 10, total: 0 })
const sort = reactive({ key: 'fechaCreacion', dir: 'desc' })

// ── Filtros ────────────────────────────────────────────────────────────────
const filters = reactive({
    creacionDesde: '',
    creacionHasta: '',
    vencimientoDesde: '',
    vencimientoHasta: '',
    completadaDesde: '',
    completadaHasta: '',
    cliente: '',
    usuarioAsignado: '',
    tipoTarea: '',
})

// ── Opciones ───────────────────────────────────────────────────────────────
const clientesOpts = [
    { value: '1', label: 'Jenifer Paola Alarcon Agudelo' },
    { value: '2', label: 'Roman Felipe Puerta Rodriguez' },
    { value: '3', label: 'Sonia Patricia Benavides Parra' },
]

const usuariosOpts = [
    { value: '1', label: 'Cartera Creditransito' },
    { value: '2', label: 'Martin Desarrollo' },
]

const tipoTareaOpts = [
    { value: 'llamada', label: 'Llamada' },
    { value: 'mensaje', label: 'Mensaje' },
    { value: 'visita', label: 'Visita' },
    { value: 'recordatorio', label: 'Recordatorio' },
    { value: 'otro', label: 'Otro' },
]

// ── Helpers ────────────────────────────────────────────────────────────────
const labelClass = 'text-xs font-medium text-gray-400 uppercase tracking-wide'
const modalLabelClass =
    'text-xs font-medium text-gray-500 uppercase tracking-wide'
const inputClass =
    'w-full h-9 px-3 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600 outline-none focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10 transition-all placeholder:text-gray-300'

function estadoBadge(estado) {
    if (!estado) return 'bg-gray-100 text-gray-500'
    const s = String(estado).toLowerCase()
    if (s.includes('complet')) return 'bg-emerald-50 text-emerald-700'
    if (s.includes('vencid')) return 'bg-red-50 text-red-600'
    if (s.includes('inici') || s.includes('progres'))
        return 'bg-blue-50 text-blue-600'
    if (s.includes('pendient')) return 'bg-yellow-50 text-yellow-700'
    return 'bg-gray-100 text-gray-500'
}

function authHeaders() {
    return {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    }
}

// ── Selección ──────────────────────────────────────────────────────────────
const allSelected = computed(
    () =>
        tareas.value.length > 0 &&
        tareas.value.every(t => selected.value.includes(t.id))
)

function onToggleAll(checked) {
    selected.value = checked ? tareas.value.map(t => t.id) : []
}

function onToggleRow(id) {
    const idx = selected.value.indexOf(id)
    idx === -1 ? selected.value.push(id) : selected.value.splice(idx, 1)
}

// ── Backend ────────────────────────────────────────────────────────────────
function cambiarTab(key) {
    activeTab.value = key
    pagination.currentPage = 1
    fetchTareas()
}

async function fetchTareas() {
    loading.value = true
    try {
        const params = new URLSearchParams({
            page: pagination.currentPage,
            per_page: pagination.perPage,
            sort_key: sort.key,
            sort_dir: sort.dir,
            search: search.value,
            tab: activeTab.value,
            ...(filters.creacionDesde && {
                creacion_desde: filters.creacionDesde,
            }),
            ...(filters.creacionHasta && {
                creacion_hasta: filters.creacionHasta,
            }),
            ...(filters.vencimientoDesde && {
                vencimiento_desde: filters.vencimientoDesde,
            }),
            ...(filters.vencimientoHasta && {
                vencimiento_hasta: filters.vencimientoHasta,
            }),
            ...(filters.completadaDesde && {
                completada_desde: filters.completadaDesde,
            }),
            ...(filters.completadaHasta && {
                completada_hasta: filters.completadaHasta,
            }),
            ...(filters.cliente && { cliente_id: filters.cliente }),
            ...(filters.usuarioAsignado && {
                usuario_id: filters.usuarioAsignado,
            }),
            ...(filters.tipoTarea && { tipo_tarea: filters.tipoTarea }),
        })

        const response = await fetch(`/api/cobranza/tareas?${params}`, {
            headers: authHeaders(),
        })
        if (!response.ok) throw new Error()

        const data = await response.json()
        tareas.value = data.data
        pagination.total = data.meta.total
        pagination.currentPage = data.meta.current_page

        // Conteos por tab si el backend los devuelve
        if (data.counts) Object.assign(tabCounts.value, data.counts)
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
}

// ── Handlers DataTable ─────────────────────────────────────────────────────
function onPageChange(page) {
    pagination.currentPage = page
    fetchTareas()
}
function onPerPageChange(val) {
    pagination.perPage = val
    pagination.currentPage = 1
    fetchTareas()
}
function onSort({ key, dir }) {
    sort.key = key
    sort.dir = dir
    pagination.currentPage = 1
    fetchTareas()
}
function onSearch(val) {
    search.value = val
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        pagination.currentPage = 1
        fetchTareas()
    }, 400)
}

// ── Modal crear ────────────────────────────────────────────────────────────
const modal = reactive({
    open: false,
    loading: false,
    error: '',
    form: {
        titulo: '',
        cliente: '',
        usuarioAsignado: '',
        tipoTarea: '',
        vencimiento: '',
        nota: '',
    },
})

function abrirModalCrear() {
    modal.form = {
        titulo: '',
        cliente: '',
        usuarioAsignado: '',
        tipoTarea: '',
        vencimiento: '',
        nota: '',
    }
    modal.error = ''
    modal.open = true
}

async function guardarTarea() {
    if (!modal.form.titulo) {
        modal.error = 'El título es requerido.'
        return
    }
    modal.loading = true
    modal.error = ''
    try {
        const response = await fetch('/api/cobranza/tareas', {
            method: 'POST',
            headers: { ...authHeaders(), 'Content-Type': 'application/json' },
            body: JSON.stringify(modal.form),
        })
        if (!response.ok) throw new Error()
        modal.open = false
        fetchTareas()
    } catch {
        modal.error = 'No se pudo crear la tarea. Intenta nuevamente.'
    } finally {
        modal.loading = false
    }
}

function iniciarTarea() {
    // Inicia las tareas seleccionadas
    console.log('Iniciar tareas:', selected.value)
}

// ── Componente inline chevron ──────────────────────────────────────────────
const ChevronIcon = {
    template: `
        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
            </svg>
        </span>
    `,
}

onMounted(fetchTareas)
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
