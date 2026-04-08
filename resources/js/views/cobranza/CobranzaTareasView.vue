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
                <FormInput
                    label="Fecha creación desde"
                    v-model="filters.creacionDesde"
                    type="date"
                />
                <FormInput
                    label="Fecha creación hasta"
                    v-model="filters.creacionHasta"
                    type="date"
                />
                <FormInput
                    label="Fecha vencimiento desde"
                    v-model="filters.vencimientoDesde"
                    type="date"
                />
                <FormInput
                    label="Fecha vencimiento hasta"
                    v-model="filters.vencimientoHasta"
                    type="date"
                />
            </div>

            <!-- Fila 2: fechas completada + cliente + usuario -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <FormInput
                    label="Fecha completada desde"
                    v-model="filters.completadaDesde"
                    type="date"
                />
                <FormInput
                    label="Fecha completada hasta"
                    v-model="filters.completadaHasta"
                    type="date"
                />
                <FormSelectAsync
                    label="Cliente"
                    v-model="filters.cliente"
                    :fetch-options="opcionesStore.fetchClientesValidated"
                    placeholder="Seleccione un cliente"
                />
                <FormInput
                    label="Usuario asignado"
                    type="select"
                    v-model="filters.usuarioAsignado"
                    :options="usuariosOpts"
                    placeholder="Seleccione el usuario"
                    :searchable="true"
                />
            </div>

            <!-- Fila 3: tipo de tarea + botones -->
            <div class="flex flex-wrap items-end gap-4">
                <FormInput
                    label="Tipo de tarea"
                    type="select"
                    v-model="filters.tipoTarea"
                    :options="tipoTareaOpts"
                    placeholder="Seleccionar tipo"
                    wrapper-class="min-w-[200px]"
                />

                <div class="flex items-center gap-2">
                    <button @click="abrirModalCrear" class="btn btn-main">
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
                        class="btn btn-warning disabled:bg-gray-300! disabled:cursor-not-allowed"
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
                        class="text-[#0A2540] font-medium max-w-45 truncate block"
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
                    <!-- Cabecera modal -->
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

                    <!-- Campos del modal -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <FormInput
                            label="Título"
                            v-model="modal.form.titulo"
                            placeholder="Descripción de la tarea"
                            :required="true"
                            wrapper-class="sm:col-span-2"
                        />
                        <FormSelectAsync
                            label="Cliente"
                            v-model="filters.cliente"
                            :fetch-options="
                                opcionesStore.fetchClientesValidated
                            "
                            placeholder="Seleccione un cliente"
                        />
                        <FormInput
                            label="Asignar a"
                            type="select"
                            v-model="modal.form.usuarioAsignado"
                            :options="usuariosOpts"
                            placeholder="Seleccione el usuario"
                            :searchable="true"
                        />
                        <FormInput
                            label="Tipo de tarea"
                            type="select"
                            v-model="modal.form.tipoTarea"
                            :options="tipoTareaOpts"
                            placeholder="Seleccionar tipo"
                        />
                        <FormInput
                            label="Fecha vencimiento"
                            type="date"
                            v-model="modal.form.vencimiento"
                        />
                        <FormInput
                            label="Nota"
                            type="textarea"
                            v-model="modal.form.nota"
                            placeholder="Observaciones..."
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

// -- Componentes --------------------------------------------------
import FormSelectAsync from '@/components/form/FormSelectAsync.vue'
import DataTable from '@/components/table/DataTable.vue'
import FormInput from '@/components/form/FormInput.vue'

// -- Loader -------------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

// -- Composables ----------------------------------------------------
// -- DataTable ------------------------------------------------------
import { useDataTable } from '@/composables/useDataTable'

// -- Store -------------------------------------------------------
import { useOpcionesStore } from '@/stores/opciones'

import api from '@/services/api'
import dayjs from 'dayjs'

// -- Opciones de selects -----------------------------------------
const opcionesStore = useOpcionesStore()

// -- Columnas -----------------------------------------------------
const columns = [
    { key: 'estado', label: 'Estado', sortable: false },
    { key: 'titulo', label: 'Título', sortable: false },
    { key: 'cliente', label: 'Cliente', sortable: false },
    { key: 'asignadoA', label: 'Asignado a', sortable: false },
    { key: 'fechaCreacion', label: 'Fecha creación', sortable: false },
    { key: 'fechaVencimiento', label: 'Fecha vencimiento', sortable: false },
    { key: 'fechaCompletada', label: 'Fecha completada', sortable: false },
    { key: 'tipoTarea', label: 'Tipo tarea', sortable: false },
    { key: 'ultimaInteraccion', label: 'Última interacción', sortable: false },
]

// -- Tabs ----------------------------------------------------------
const tabs = [
    { key: 'totales', label: 'Todas' },
    { key: 'completadas', label: 'Completadas' },
    { key: 'vencidos', label: 'Vencidas' },
    { key: 'vencenHoy', label: 'Vencen hoy' },
    { key: 'proximos', label: 'Próximas' },
]

const activeTab = ref('totales')
const tabCounts = ref({
    totales: 0,
    completadas: 0,
    vencidos: 0,
    vencenHy: 0,
    proximos: 0,
})

// -- Estado ----------------------------------------------------------
const tareas = ref([])
const loading = ref(false)
const selected = ref([])

// -- Filtros ----------------------------------------------------------
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

// -- Opciones ----------------------------------------------------------
const usuariosOpts = ref([])

const tipoTareaOpts = [
    { value: 'llamada', label: 'Llamada' },
    { value: 'correo', label: 'Correo' },
    { value: 'otro', label: 'Otro' },
]

// -- Selección --------------------------------------------------------
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

// -- Helpers ----------------------------------------------------------
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

// -- Backend --------------------------------------------------------
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

        const { data } = await api.get(`/api/tareas`, {
            params,
        })

        const { data: tareasData, total, current_page } = data.tareas

        // Lista de tareas
        transformTareas(tareasData)

        pagination.total = total
        pagination.currentPage = current_page

        // Conteos tabs
        if (data.totales) Object.assign(tabCounts.value, data.totales)
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
}

async function fetchUsuarios() {
    try {
        const { data } = await api.get('/api/usuarios/listMyUsers')

        // Opciones formateadas para FormInput type="select"
        usuariosOpts.value = data.usuarios.map(c => ({
            value: c.idUsuario,
            label: c.nombre,
        }))
    } catch (err) {
        console.error(err)
    }
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
    Object.assign(modal.form, {
        titulo: '',
        cliente: '',
        usuarioAsignado: '',
        tipoTarea: '',
        vencimiento: '',
        nota: '',
    })
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
    // TODO: iniciar las tareas seleccionadas
    console.log('Iniciar tareas:', selected.value)
}

function transformTareas(data) {
    const infoTareas = addPropertiesTasksList(data)

    tareas.value = infoTareas.map(tarea => ({
        id: tarea.id,
        estado: tarea.estado,
        titulo: tarea.titulo,
        cliente: tarea.cliente_nombre,
        asignadoA: tarea.nombre,
        fechaCreacion: dayjs(tarea.created_at).format('YYYY-MM-DD'),
        fechaVencimiento: dayjs(tarea.fecha_vencimiento).format('YYYY-MM-DD'),
        fechaCompletada: tarea.fecha_completado
            ? dayjs(tarea.fecha_completado).format('YYYY-MM-DD')
            : '',
        tipoTarea: tarea.tipo_nombre,
    }))
}

function addPropertiesTasksList(registros) {
    const hoy = dayjs()

    return registros.map(item => {
        const fechaVencimiento = dayjs(item.fecha_vencimiento)
        const diffDias = fechaVencimiento.diff(hoy, 'day')

        return {
            ...item,

            estado: item.completado
                ? 'Completado'
                : diffDias < 0
                  ? 'Vencido'
                  : 'Pendiente',

            tipo_nombre:
                {
                    1: 'Otro',
                    2: 'Llamada',
                    3: 'Correo',
                }[item.tipo] || '',
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
} = useDataTable(fetchTareas, {
    initialSortKey: 'fechaCreacion',
})

onMounted(async () => {
    start()

    try {
        await Promise.all([fetchTareas(), fetchUsuarios()])
    } finally {
        stop()
    }
})
</script>
