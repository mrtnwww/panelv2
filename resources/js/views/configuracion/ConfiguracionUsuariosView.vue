<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-[#0A2540]">
                Gestión de usuarios
            </h1>
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
                Crear usuario
            </button>
        </div>

        <!-- DataTable -->
        <DataTable
            :rows="usuarios"
            :columns="columns"
            :loading="loading"
            :total="pagination.total"
            :current-page="pagination.currentPage"
            :per-page="pagination.perPage"
            :sort-key="sort.key"
            :sort-dir="sort.dir"
            :search="search"
            empty-message="No se encontraron usuarios."
            @update:current-page="onPageChange"
            @update:per-page="onPerPageChange"
            @update:search="onSearch"
            @sort="onSort"
        >
            <!-- Nombre + email con avatar -->
            <template #cell-nombre="{ row }">
                <div class="flex items-center gap-3">
                    <div
                        class="w-9 h-9 rounded-full bg-gray-200 flex-shrink-0 overflow-hidden"
                    >
                        <img
                            v-if="row.avatar"
                            :src="row.avatar"
                            :alt="row.nombre"
                            class="w-full h-full object-cover"
                        />
                        <svg
                            v-else
                            viewBox="0 0 36 36"
                            fill="none"
                            class="w-full h-full text-gray-400"
                        >
                            <circle cx="18" cy="13" r="7" fill="currentColor" />
                            <ellipse
                                cx="18"
                                cy="30"
                                rx="11"
                                ry="8"
                                fill="currentColor"
                            />
                        </svg>
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-sm font-semibold text-[#0A2540]">{{
                            row.nombre
                        }}</span>
                        <span class="text-xs text-gray-400">{{
                            row.email
                        }}</span>
                    </div>
                </div>
            </template>

            <!-- Tipo de usuario -->
            <template #cell-tipoUsuario="{ value }">
                <span class="text-sm text-gray-600">{{ value }}</span>
            </template>

            <!-- Fecha de caducidad -->
            <template #cell-fechaCaducidad="{ value }">
                <span :class="value ? caducidadClass(value) : 'text-gray-300'">
                    {{ value || '—' }}
                </span>
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
                    <button
                        @click.stop="confirmarEliminar(row)"
                        class="w-7 h-7 rounded-lg border border-gray-200 flex items-center justify-center text-gray-400 hover:text-red-500 hover:border-red-200 transition-all"
                        title="Eliminar"
                    >
                        <svg
                            width="13"
                            height="13"
                            viewBox="0 0 13 13"
                            fill="none"
                        >
                            <path
                                d="M2 3.5h9M5 3.5V2h3v1.5M5.5 6v3.5M7.5 6v3.5"
                                stroke="currentColor"
                                stroke-width="1.2"
                                stroke-linecap="round"
                            />
                            <path
                                d="M3 3.5l.5 7h6l.5-7"
                                stroke="currentColor"
                                stroke-width="1.2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>
                </div>
            </template>
        </DataTable>

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
                                    ? 'Nuevo usuario'
                                    : 'Editar usuario'
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
                        <!-- Nombre -->
                        <FormInput
                            label="Nombre"
                            v-model="modal.form.nombre"
                            placeholder="Nombre completo"
                            :required="true"
                        />

                        <!-- Email -->
                        <FormInput
                            label="Correo electrónico"
                            type="email"
                            v-model="modal.form.email"
                            placeholder="correo@empresa.com"
                            :required="true"
                        />

                        <!-- Contraseña (solo en crear) con toggle ojo -->
                        <FormInput
                            v-if="modal.mode === 'crear'"
                            label="Contraseña"
                            :type="modal.showPass ? 'text' : 'password'"
                            v-model="modal.form.password"
                            placeholder="Mínimo 8 caracteres"
                            :required="true"
                        >
                            <template #icon-right>
                                <button
                                    type="button"
                                    @click="modal.showPass = !modal.showPass"
                                    class="text-gray-300 hover:text-gray-500 transition-colors"
                                >
                                    <svg
                                        width="15"
                                        height="15"
                                        viewBox="0 0 16 16"
                                        fill="none"
                                    >
                                        <ellipse
                                            cx="8"
                                            cy="8"
                                            rx="6.5"
                                            ry="4"
                                            stroke="currentColor"
                                            stroke-width="1.2"
                                        />
                                        <circle
                                            cx="8"
                                            cy="8"
                                            r="2"
                                            stroke="currentColor"
                                            stroke-width="1.2"
                                        />
                                    </svg>
                                </button>
                            </template>
                        </FormInput>

                        <!-- Fecha caducidad -->
                        <FormInput
                            label="Fecha de caducidad"
                            type="date"
                            v-model="modal.form.fechaCaducidad"
                        />

                        <!-- Roles (checkboxes) -->
                        <div class="sm:col-span-2 flex flex-col gap-2">
                            <label
                                class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                            >
                                Tipo de usuario
                                <span class="text-red-400">*</span>
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <FormCheckbox
                                    v-for="rol in rolesOpts"
                                    :key="rol.value"
                                    :label="rol.label"
                                    :model-value="
                                        modal.form.roles.includes(rol.value)
                                    "
                                    @update:model-value="toggleRol(rol.value)"
                                />
                            </div>
                        </div>
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
                            @click="guardarUsuario"
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
                                    ? 'Crear usuario'
                                    : 'Guardar cambios'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ── Modal confirmar eliminar ── -->
        <transition name="modal">
            <div
                v-if="deleteModal.open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
                @click.self="deleteModal.open = false"
            >
                <div
                    class="bg-white rounded-2xl w-full max-w-sm p-6 flex flex-col gap-4 shadow-xl"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center flex-shrink-0"
                        >
                            <svg
                                width="16"
                                height="16"
                                viewBox="0 0 16 16"
                                fill="none"
                                class="text-red-500"
                            >
                                <path
                                    d="M2 4h12M5.5 4V2.5h5V4M6 7v5M10 7v5"
                                    stroke="currentColor"
                                    stroke-width="1.3"
                                    stroke-linecap="round"
                                />
                                <path
                                    d="M3 4l.667 9.333A1 1 0 0 0 4.662 14.5h6.676a1 1 0 0 0 .995-.667L13 4"
                                    stroke="currentColor"
                                    stroke-width="1.3"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-[#0A2540]">
                                Eliminar usuario
                            </p>
                            <p
                                class="text-sm text-gray-400 mt-0.5 leading-relaxed"
                            >
                                ¿Estás seguro de que deseas eliminar a
                                <span class="font-medium text-gray-600">{{
                                    deleteModal.usuario?.nombre
                                }}</span
                                >? Esta acción no se puede deshacer.
                            </p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button
                            @click="deleteModal.open = false"
                            class="h-9 px-4 rounded-lg border border-gray-200 text-sm text-gray-500 hover:bg-gray-50 transition-all"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="eliminarUsuario"
                            :disabled="deleteModal.loading"
                            class="h-9 px-4 rounded-lg bg-red-500 hover:bg-red-600 disabled:bg-gray-300 text-white text-sm font-medium transition-all flex items-center gap-2"
                        >
                            <svg
                                v-if="deleteModal.loading"
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
                            Eliminar
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
import FormInput from '@/components/form/FormInput.vue'
import FormCheckbox from '@/components/form/FormCheckbox.vue'

// ── Columnas ───────────────────────────────────────────────────────────────
const columns = [
    { key: 'nombre', label: 'Nombre', sortable: false },
    { key: 'tipoUsuario', label: 'Tipo Usuario', sortable: false },
    { key: 'fechaCreacion', label: 'Fecha de creación', sortable: false },
    { key: 'ultimoAcceso', label: 'Último inicio de sesión', sortable: false },
    { key: 'fechaCaducidad', label: 'Fecha de caducidad', sortable: false },
    { key: 'acciones', label: 'Acciones', sortable: false },
]

// ── Estado ─────────────────────────────────────────────────────────────────
const usuarios = ref([])
const loading = ref(false)
const search = ref('')
let searchTimeout = null

const pagination = reactive({ currentPage: 1, perPage: 10, total: 0 })
const sort = reactive({ key: 'fechaCreacion', dir: 'desc' })

// ── Opciones ───────────────────────────────────────────────────────────────
const rolesOpts = [
    { value: 'administrador', label: 'Administrador' },
    { value: 'asesor', label: 'Asesor' },
    { value: 'validador', label: 'Validador' },
    { value: 'analista', label: 'Analista' },
    { value: 'cobranza', label: 'Cobranza' },
]

// ── Helpers ────────────────────────────────────────────────────────────────
function caducidadClass(fecha) {
    if (!fecha) return 'text-gray-300'
    const dias = (new Date(fecha) - new Date()) / (1000 * 60 * 60 * 24)
    if (dias < 0) return 'text-red-500 font-medium'
    if (dias < 15) return 'text-orange-500 font-medium'
    return 'text-gray-600'
}

function authHeaders() {
    return {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    }
}

// ── Backend ────────────────────────────────────────────────────────────────
async function fetchUsuarios() {
    loading.value = true
    try {
        const params = new URLSearchParams({
            page: pagination.currentPage,
            per_page: pagination.perPage,
            sort_key: sort.key,
            sort_dir: sort.dir,
            search: search.value,
        })
        const response = await fetch(`/api/usuarios?${params}`, {
            headers: authHeaders(),
        })
        if (!response.ok) throw new Error()
        const data = await response.json()
        usuarios.value = data.data
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
    fetchUsuarios()
}
function onPerPageChange(val) {
    pagination.perPage = val
    pagination.currentPage = 1
    fetchUsuarios()
}
function onSort({ key, dir }) {
    sort.key = key
    sort.dir = dir
    pagination.currentPage = 1
    fetchUsuarios()
}
function onSearch(val) {
    search.value = val
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        pagination.currentPage = 1
        fetchUsuarios()
    }, 400)
}

// ── Modal crear / editar ───────────────────────────────────────────────────
const modal = reactive({
    open: false,
    mode: 'crear',
    loading: false,
    error: '',
    showPass: false,
    form: {
        id: null,
        nombre: '',
        email: '',
        password: '',
        fechaCaducidad: '',
        roles: [],
    },
})

function toggleRol(value) {
    const idx = modal.form.roles.indexOf(value)
    idx === -1 ? modal.form.roles.push(value) : modal.form.roles.splice(idx, 1)
}

function abrirModalCrear() {
    Object.assign(modal.form, {
        id: null,
        nombre: '',
        email: '',
        password: '',
        fechaCaducidad: '',
        roles: [],
    })
    modal.mode = 'crear'
    modal.error = ''
    modal.showPass = false
    modal.open = true
}

function abrirModalEditar(row) {
    Object.assign(modal.form, {
        id: row.id,
        nombre: row.nombre,
        email: row.email,
        password: '',
        fechaCaducidad: row.fechaCaducidad ?? '',
        roles: row.roles ?? [],
    })
    modal.mode = 'editar'
    modal.error = ''
    modal.showPass = false
    modal.open = true
}

async function guardarUsuario() {
    if (!modal.form.nombre || !modal.form.email) {
        modal.error = 'Nombre y correo son requeridos.'
        return
    }
    if (modal.mode === 'crear' && !modal.form.password) {
        modal.error = 'La contraseña es requerida.'
        return
    }
    if (modal.form.roles.length === 0) {
        modal.error = 'Selecciona al menos un tipo de usuario.'
        return
    }
    modal.loading = true
    modal.error = ''
    try {
        const isEditar = modal.mode === 'editar'
        const body = { ...modal.form }
        if (isEditar && !body.password) delete body.password

        const response = await fetch(
            isEditar ? `/api/usuarios/${modal.form.id}` : '/api/usuarios',
            {
                method: isEditar ? 'PUT' : 'POST',
                headers: {
                    ...authHeaders(),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(body),
            }
        )
        if (!response.ok) throw new Error()
        modal.open = false
        fetchUsuarios()
    } catch {
        modal.error = 'No se pudo guardar el usuario. Intenta nuevamente.'
    } finally {
        modal.loading = false
    }
}

// ── Modal eliminar ─────────────────────────────────────────────────────────
const deleteModal = reactive({ open: false, loading: false, usuario: null })

function confirmarEliminar(row) {
    deleteModal.usuario = row
    deleteModal.open = true
}

async function eliminarUsuario() {
    deleteModal.loading = true
    try {
        const response = await fetch(
            `/api/usuarios/${deleteModal.usuario.id}`,
            {
                method: 'DELETE',
                headers: authHeaders(),
            }
        )
        if (!response.ok) throw new Error()
        deleteModal.open = false
        fetchUsuarios()
    } catch {
        console.error('Error al eliminar usuario')
    } finally {
        deleteModal.loading = false
    }
}

onMounted(fetchUsuarios)
</script>
