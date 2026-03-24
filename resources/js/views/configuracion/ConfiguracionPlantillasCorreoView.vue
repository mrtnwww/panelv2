<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">
            Plantillas de correo
        </h1>

        <!-- DataTable -->
        <DataTable
            :rows="plantillas"
            :columns="columns"
            :loading="loading"
            :total="pagination.total"
            :current-page="pagination.currentPage"
            :per-page="pagination.perPage"
            :sort-key="sort.key"
            :sort-dir="sort.dir"
            :search="search"
            empty-message="No se encontraron plantillas."
            @update:current-page="onPageChange"
            @update:per-page="onPerPageChange"
            @update:search="onSearch"
            @sort="onSort"
        >
            <!-- Botón crear en la barra -->
            <template #actions>
                <button
                    @click="abrirModalCrear"
                    class="h-8 px-4 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-medium transition-all flex items-center gap-1.5"
                >
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                        <path
                            d="M6 1v10M1 6h10"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                        />
                    </svg>
                    Crear plantilla
                </button>
            </template>

            <!-- ID -->
            <template #cell-id="{ value }">
                <span class="text-gray-400 text-xs font-mono">{{ value }}</span>
            </template>

            <!-- Nombre -->
            <template #cell-nombre="{ value }">
                <span
                    class="font-medium text-[#0A2540] text-xs tracking-wide"
                    >{{ value }}</span
                >
            </template>

            <!-- Usuario creó -->
            <template #cell-usuarioCreo="{ value }">
                <span class="text-xs text-gray-500">{{ value }}</span>
            </template>

            <!-- Asunto truncado -->
            <template #cell-asunto="{ value }">
                <span
                    class="text-xs text-gray-600 max-w-xs truncate block"
                    :title="value"
                    >{{ value }}</span
                >
            </template>

            <!-- Botón visualizar -->
            <template #cell-correo="{ row }">
                <button
                    @click.stop="abrirPreview(row)"
                    class="h-7 px-3 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] text-white text-xs font-medium transition-all flex items-center gap-1.5 whitespace-nowrap"
                >
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                        <ellipse
                            cx="6"
                            cy="6"
                            rx="5"
                            ry="3"
                            stroke="currentColor"
                            stroke-width="1.2"
                        />
                        <circle
                            cx="6"
                            cy="6"
                            r="1.5"
                            stroke="currentColor"
                            stroke-width="1.2"
                        />
                    </svg>
                    Visualizar plantilla
                </button>
            </template>

            <!-- Acciones -->
            <template #cell-acciones="{ row }">
                <div
                    class="flex items-center gap-1.5 justify-end opacity-0 group-hover:opacity-100 transition-opacity"
                >
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

        <!-- ── Modal preview ── -->
        <transition name="modal">
            <div
                v-if="preview.open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                @click.self="preview.open = false"
            >
                <div
                    class="bg-white rounded-2xl w-full max-w-2xl max-h-[85vh] flex flex-col shadow-2xl"
                >
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0"
                    >
                        <div>
                            <p class="text-base font-semibold text-[#0A2540]">
                                {{ preview.plantilla?.nombre }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ preview.plantilla?.asunto }}
                            </p>
                        </div>
                        <button
                            @click="preview.open = false"
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

                    <!-- Contenido del correo -->
                    <div class="flex-1 overflow-y-auto px-6 py-5">
                        <div
                            v-if="preview.plantilla?.html"
                            class="prose prose-sm max-w-none text-gray-700 border border-gray-100 rounded-xl p-4 bg-gray-50"
                            v-html="preview.plantilla.html"
                        />
                        <div
                            v-else
                            class="text-sm text-gray-400 text-center py-10"
                        >
                            Sin contenido disponible para previsualizar.
                        </div>
                    </div>

                    <div
                        class="px-6 py-4 border-t border-gray-100 flex justify-end flex-shrink-0"
                    >
                        <button
                            @click="preview.open = false"
                            class="h-9 px-4 rounded-lg border border-gray-200 text-sm text-gray-500 hover:bg-gray-50 transition-all"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ── Modal crear / editar ── -->
        <transition name="modal">
            <div
                v-if="modal.open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                @click.self="modal.open = false"
            >
                <div
                    class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] flex flex-col shadow-2xl"
                >
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0"
                    >
                        <h2 class="text-base font-semibold text-[#0A2540]">
                            {{
                                modal.mode === 'crear'
                                    ? 'Nueva plantilla'
                                    : 'Editar plantilla'
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

                    <!-- Formulario -->
                    <div
                        class="flex-1 overflow-y-auto px-6 py-5 flex flex-col gap-4"
                    >
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label :class="labelClass"
                                    >Nombre de la plantilla
                                    <span class="text-red-400">*</span></label
                                >
                                <input
                                    v-model="modal.form.nombre"
                                    type="text"
                                    placeholder="Ej: Recordatorio de pago"
                                    :class="inputClass"
                                />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label :class="labelClass"
                                    >Asunto
                                    <span class="text-red-400">*</span></label
                                >
                                <input
                                    v-model="modal.form.asunto"
                                    type="text"
                                    placeholder="Asunto del correo"
                                    :class="inputClass"
                                />
                            </div>
                        </div>

                        <!-- Editor de contenido HTML -->
                        <div class="flex flex-col gap-1.5 flex-1">
                            <div class="flex items-center justify-between">
                                <label :class="labelClass"
                                    >Contenido del correo
                                    <span class="text-red-400">*</span></label
                                >
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="
                                            modal.vistaPrevia =
                                                !modal.vistaPrevia
                                        "
                                        :class="[
                                            'text-xs px-2.5 py-1 rounded-lg border transition-all',
                                            modal.vistaPrevia
                                                ? 'bg-[#1a5c2a] text-white border-[#1a5c2a]'
                                                : 'border-gray-200 text-gray-500 hover:border-gray-300',
                                        ]"
                                    >
                                        {{
                                            modal.vistaPrevia
                                                ? 'Editar'
                                                : 'Vista previa'
                                        }}
                                    </button>
                                </div>
                            </div>

                            <!-- Editor -->
                            <textarea
                                v-if="!modal.vistaPrevia"
                                v-model="modal.form.html"
                                rows="14"
                                placeholder="Escribe el contenido HTML del correo..."
                                class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-sm text-[#0A2540] font-mono resize-none outline-none transition-all focus:bg-white focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10"
                            />

                            <!-- Vista previa -->
                            <div
                                v-else
                                class="min-h-[200px] p-4 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-700 overflow-auto"
                                v-html="
                                    modal.form.html ||
                                    '<p class=\'text-gray-300\'>Sin contenido</p>'
                                "
                            />
                        </div>

                        <transition name="fade">
                            <div
                                v-if="modal.error"
                                class="px-3 py-2.5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"
                            >
                                {{ modal.error }}
                            </div>
                        </transition>
                    </div>

                    <!-- Footer -->
                    <div
                        class="px-6 py-4 border-t border-gray-100 flex justify-end gap-2 flex-shrink-0"
                    >
                        <button
                            @click="modal.open = false"
                            class="h-9 px-4 rounded-lg border border-gray-200 text-sm text-gray-500 hover:bg-gray-50 transition-all"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="guardarPlantilla"
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
                                    ? 'Crear plantilla'
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
import { ref, reactive, onMounted } from 'vue'
import DataTable from '@/components/table/DataTable.vue'

// ── Columnas ───────────────────────────────────────────────────────────────
const columns = [
    { key: 'id', label: 'Id', sortable: false, align: 'center' },
    { key: 'nombre', label: 'Nombre de la plantilla', sortable: false },
    { key: 'usuarioCreo', label: 'Usuario', sortable: false },
    { key: 'asunto', label: 'Asunto', sortable: false },
    { key: 'correo', label: 'Correo', sortable: false },
    { key: 'fechaCreacion', label: 'Fecha de creación', sortable: false },
    { key: 'acciones', label: 'Acciones', sortable: false },
]

// ── Estado ─────────────────────────────────────────────────────────────────
const plantillas = ref([])
const loading = ref(false)
const search = ref('')
let searchTimeout = null

const pagination = reactive({ currentPage: 1, perPage: 10, total: 0 })
const sort = reactive({ key: 'id', dir: 'asc' })

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
async function fetchPlantillas() {
    loading.value = true
    try {
        const params = new URLSearchParams({
            page: pagination.currentPage,
            per_page: pagination.perPage,
            sort_key: sort.key,
            sort_dir: sort.dir,
            search: search.value,
        })
        const response = await fetch(`/api/plantillas-correo?${params}`, {
            headers: authHeaders(),
        })
        if (!response.ok) throw new Error()
        const data = await response.json()
        plantillas.value = data.data
        pagination.total = data.meta.total
        pagination.currentPage = data.meta.current_page
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
}

// ── Handlers DataTable ─────────────────────────────────────────────────────
function onPageChange(page) {
    pagination.currentPage = page
    fetchPlantillas()
}
function onPerPageChange(val) {
    pagination.perPage = val
    pagination.currentPage = 1
    fetchPlantillas()
}
function onSort({ key, dir }) {
    sort.key = key
    sort.dir = dir
    pagination.currentPage = 1
    fetchPlantillas()
}
function onSearch(val) {
    search.value = val
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        pagination.currentPage = 1
        fetchPlantillas()
    }, 400)
}

// ── Preview ────────────────────────────────────────────────────────────────
const preview = reactive({ open: false, plantilla: null })

function abrirPreview(row) {
    preview.plantilla = row
    preview.open = true
}

// ── Modal crear / editar ───────────────────────────────────────────────────
const modal = reactive({
    open: false,
    mode: 'crear',
    loading: false,
    error: '',
    vistaPrevia: false,
    form: { id: null, nombre: '', asunto: '', html: '' },
})

function abrirModalCrear() {
    modal.form = { id: null, nombre: '', asunto: '', html: '' }
    modal.mode = 'crear'
    modal.error = ''
    modal.vistaPrevia = false
    modal.open = true
}

function abrirModalEditar(row) {
    modal.form = {
        id: row.id,
        nombre: row.nombre,
        asunto: row.asunto,
        html: row.html ?? '',
    }
    modal.mode = 'editar'
    modal.error = ''
    modal.vistaPrevia = false
    modal.open = true
}

async function guardarPlantilla() {
    if (!modal.form.nombre || !modal.form.asunto) {
        modal.error = 'Nombre y asunto son requeridos.'
        return
    }
    modal.loading = true
    modal.error = ''
    try {
        const isEditar = modal.mode === 'editar'
        const response = await fetch(
            isEditar
                ? `/api/plantillas-correo/${modal.form.id}`
                : '/api/plantillas-correo',
            {
                method: isEditar ? 'PUT' : 'POST',
                headers: {
                    ...authHeaders(),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    nombre: modal.form.nombre,
                    asunto: modal.form.asunto,
                    html: modal.form.html,
                }),
            }
        )
        if (!response.ok) throw new Error()
        modal.open = false
        fetchPlantillas()
    } catch {
        modal.error = 'No se pudo guardar la plantilla. Intenta nuevamente.'
    } finally {
        modal.loading = false
    }
}

onMounted(fetchPlantillas)
</script>
