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
                        class="w-9 h-9 rounded-full bg-gray-200 shrink-0 overflow-hidden"
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
                    class="flex items-center justify-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity"
                >
                    <button
                        @click.stop="abrirModalEditar(row)"
                        class="btn-table"
                        title="Editar"
                    >
                        <i class="fa fa-pencil"></i>
                    </button>
                    <button
                        @click.stop="eliminarUsuario(row)"
                        class="btn-table"
                        title="Eliminar"
                    >
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- ── Modal crear / editar ── -->
        <AppModal
            v-model="modal.open"
            :title="modal.mode === 'crear' ? 'Nuevo usuario' : 'Editar usuario'"
            size="lg"
            :show-footer="true"
            :cancel-label="'Cancelar'"
            :confirm-label="
                modal.mode === 'crear' ? 'Crear usuario' : 'Guardar cambios'
            "
            :confirm-loading="modal.loading"
            :close-on-overlay="true"
            @confirm="guardarUsuario"
            @update:modelValue="cerrarModal"
        >
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

                <!-- Contraseña (solo en crear) -->
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
                            :model-value="modal.form.roles.includes(rol.value)"
                            @update:model-value="toggleRol(rol.value)"
                        />
                    </div>
                </div>
            </div>

            <transition name="fade">
                <div
                    v-if="modal.error"
                    class="px-3 py-2.5 mt-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"
                >
                    {{ modal.error }}
                </div>
            </transition>
        </AppModal>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'

// -- Componentes ------------------------------------------------
import FormCheckbox from '@/components/form/FormCheckbox.vue'
import FormInput from '@/components/form/FormInput.vue'
import AppModal from '@/components/AppModal.vue'

import DataTable from '@/components/table/DataTable.vue'

// -- Loader -------------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

// -- DataTable ---------------------------------------------------
import { useDataTable } from '@/composables/useDataTable'

// -- Utils -------------------------------------------------------
import { formatDateYmd } from '../../utils/format'
import { confirmAlert } from '../../utils/alert'

// -- API ---------------------------------------------------------
import api from '@/services/api'

// -- Columnas ----------------------------------------------------
const columns = [
    { key: 'nombre', label: 'Nombre', sortable: false },
    { key: 'tipoUsuario', label: 'Tipo Usuario', sortable: false },
    { key: 'fechaCreacion', label: 'Fecha de creación', sortable: false },
    { key: 'ultimoAcceso', label: 'Último inicio de sesión', sortable: false },
    { key: 'fechaCaducidad', label: 'Fecha de caducidad', sortable: false },
    { key: 'acciones', label: 'Acciones', sortable: false, align: 'center' },
]

// -- Estado ------------------------------------------------------
const usuarios = ref([])
const loading = ref(false)

// -- Opciones Select ---------------------------------------------
const rolesOpts = ref([])

// -- Helpers -----------------------------------------------------
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

// -- Backend -------------------------------------------------------
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
        const { data } = await api.get('/api/usuarios/listMyUsers', { params })

        const { data: usuariosData, total, current_page } = data.usuarios

        transformarUsuarios(usuariosData)
        transformarRoles(data.roles)

        pagination.total = total
        pagination.currentPage = current_page
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
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
        fechaCaducidad: row.fechaCaducidad
            ? formatDateYmd(row.fechaCaducidad.split('/').reverse().join('-'))
            : '',
        roles: row.roles ?? [],
    })
    modal.mode = 'editar'
    modal.error = ''
    modal.showPass = false
    modal.open = true
}

function cerrarModal() {
    modal.open = false
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

async function eliminarUsuario(row) {
    try {
        const confirmado = await confirmAlert({
            title: 'Eliminar usuario',
            text: `¿Está seguro(a) de eliminar el usuario ${row.nombre}?`,
            icon: 'warning',
        })

        if (!confirmado) return

        await api.delete('/api/usuarios/eliminarUsuario', {
            data: { id: row.id },
        })

        await fetchUsuarios()
    } catch (err) {
        console.error(err)
    }
}

function transformarUsuarios(data) {
    usuarios.value = data.map(u => {
        const roles = []
        const nombres = []

        for (const t of u.tipo || []) {
            roles.push(t.id)
            nombres.push(t.nombre)
        }

        return {
            roles: roles,
            avatar: u.image,
            email: u.correo,
            nombre: u.nombre,
            fechaCaducidad: u.fecha_vence,
            fechaCreacion: u.fecha_creacion,
            ultimoAcceso: u.ult_inicio_sesion,
            tipoUsuario: nombres.join(', '),
        }
    })
}

function transformarRoles(data) {
    rolesOpts.value = data.map(c => ({
        value: c.id,
        label: `${c.nombre}`,
    }))
}

const {
    search,
    pagination,
    sort,
    onPageChange,
    onPerPageChange,
    onSearch,
    onSort,
} = useDataTable(fetchUsuarios)

onMounted(async () => {
    start()

    try {
        await fetchUsuarios()
    } finally {
        stop()
    }
})
</script>
