<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">Productos</h1>

        <!-- DataTable -->
        <DataTable
            :rows="productos"
            :columns="columns"
            :loading="loading"
            :total="pagination.total"
            :current-page="pagination.currentPage"
            :per-page="pagination.perPage"
            :sort-key="sort.key"
            :sort-dir="sort.dir"
            :search="search"
            empty-message="No se encontraron productos."
            @update:current-page="onPageChange"
            @update:per-page="onPerPageChange"
            @update:search="onSearch"
            @sort="onSort"
        >
            <!-- Acciones en la barra superior -->
            <template #actions>
                <button
                    @click="abrirModalCrear"
                    class="h-8 px-3 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] text-white text-xs font-medium transition-all flex items-center gap-1.5"
                >
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                        <path
                            d="M6 1v10M1 6h10"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                        />
                    </svg>
                    Crear producto
                </button>

                <button
                    @click="descargarPlantilla"
                    :disabled="loadingPlantilla"
                    class="h-8 px-3 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] disabled:bg-gray-300 text-white text-xs font-medium transition-all flex items-center gap-1.5"
                >
                    <svg
                        v-if="loadingPlantilla"
                        class="animate-spin w-3 h-3"
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
                    <svg
                        v-else
                        width="12"
                        height="12"
                        viewBox="0 0 12 12"
                        fill="none"
                    >
                        <path
                            d="M6 1v7M3.5 5.5L6 8l2.5-2.5"
                            stroke="currentColor"
                            stroke-width="1.4"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <path
                            d="M1 9v.5A1.5 1.5 0 0 0 2.5 11h7A1.5 1.5 0 0 0 11 9.5V9"
                            stroke="currentColor"
                            stroke-width="1.4"
                            stroke-linecap="round"
                        />
                    </svg>
                    Descargar plantilla
                </button>

                <label
                    class="h-8 px-3 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] text-white text-xs font-medium transition-all flex items-center gap-1.5 cursor-pointer"
                >
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                        <path
                            d="M6 8V1M3.5 3.5L6 1l2.5 2.5"
                            stroke="currentColor"
                            stroke-width="1.4"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <path
                            d="M1 9v.5A1.5 1.5 0 0 0 2.5 11h7A1.5 1.5 0 0 0 11 9.5V9"
                            stroke="currentColor"
                            stroke-width="1.4"
                            stroke-linecap="round"
                        />
                    </svg>
                    Subir plantilla
                    <input
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        class="sr-only"
                        @change="subirPlantilla"
                    />
                </label>
            </template>

            <!-- Celda precio -->
            <template #cell-precio="{ value }">
                <span class="font-medium text-gray-700 tabular-nums">{{
                    formatCurrency(value)
                }}</span>
            </template>

            <!-- Celda referencia -->
            <template #cell-referencia="{ value }">
                <span class="font-mono text-xs text-gray-500">{{ value }}</span>
            </template>

            <!-- Celda acciones -->
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
                        @click.stop="confirmarEliminar(row)"
                        class="btn-table"
                        title="Eliminar"
                    >
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- ── Modal crear / editar ── -->
        <transition name="modal">
            <div
                v-if="modal.open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
                @click.self="cerrarModal"
            >
                <div
                    class="bg-white rounded-2xl w-full max-w-md p-6 flex flex-col gap-5 shadow-xl"
                >
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold text-[#0A2540]">
                            {{
                                modal.mode === 'crear'
                                    ? 'Nuevo producto'
                                    : 'Editar producto'
                            }}
                        </h2>
                        <button
                            @click="cerrarModal"
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

                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label :class="labelClass"
                                >Referencia
                                <span class="text-red-400">*</span></label
                            >
                            <input
                                v-model="modal.form.referencia"
                                type="text"
                                placeholder="Ej: SOAT"
                                :class="inputClass"
                            />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label :class="labelClass"
                                >Nombre del producto
                                <span class="text-red-400">*</span></label
                            >
                            <input
                                v-model="modal.form.nombre"
                                type="text"
                                placeholder="Descripción del producto"
                                :class="inputClass"
                            />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label :class="labelClass">Precio</label>
                            <input
                                v-model="modal.form.precio"
                                type="number"
                                placeholder="0"
                                min="0"
                                :class="inputClass"
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
                            @click="cerrarModal"
                            class="h-9 px-4 rounded-lg border border-gray-200 text-sm text-gray-500 hover:bg-gray-50 transition-all"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="guardarProducto"
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
                                    ? 'Crear producto'
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
                            class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0"
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
                                Eliminar producto
                            </p>
                            <p
                                class="text-sm text-gray-400 mt-0.5 leading-relaxed"
                            >
                                ¿Estás seguro de que deseas eliminar
                                <span class="font-medium text-gray-600">{{
                                    deleteModal.producto?.nombre
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
                            @click="eliminarProducto"
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

// -- Componentes --------------------------------------------------------------
import DataTable from '@/components/table/DataTable.vue'

// -- Loader -------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

import { formatCurrency } from '@/utils/format'
import api from '@/services/api'

// -- Columnas -----------------------------------------------------------------
const columns = [
    {
        key: 'referencia',
        label: 'Referencia',
        sortable: false,
        align: 'center',
    },
    { key: 'nombre', label: 'Producto', sortable: false },
    { key: 'precio', label: 'Precio', sortable: false, align: 'right' },
    { key: 'acciones', label: 'Acciones', sortable: false, align: 'center' },
]

// ── Estado ─────────────────────────────────────────────────────────────────
const productos = ref([])
const loading = ref(false)
const loadingPlantilla = ref(false)
const search = ref('')
let searchTimeout = null

const pagination = reactive({ currentPage: 1, perPage: 10, total: 0 })
const sort = reactive({ key: 'referencia', dir: 'asc' })

// ── Estilos ────────────────────────────────────────────────────────────────
const labelClass = 'text-xs font-medium text-gray-500 uppercase tracking-wide'
const inputClass =
    'w-full h-10 px-3 rounded-lg border border-gray-200 bg-gray-50 text-[#0A2540] text-sm outline-none transition-all placeholder:text-gray-300 focus:bg-white focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10'

function authHeaders() {
    return {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    }
}

// -- Backend -------------------------------------------------------
async function fetchProductos() {
    loading.value = true
    try {
        const params = new URLSearchParams({
            page: pagination.currentPage,
            per_page: pagination.perPage,
            sort_key: sort.key,
            sort_dir: sort.dir,
            search: search.value,
        })

        const { data } = await api.get('/api/productos', { params })

        const { data: productosData, total, current_page } = data.productos

        // Lista de productos
        transformProductos(productosData)

        // Datos de paginación
        pagination.currentPage = current_page
        pagination.total = total
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
}

// ── Handlers DataTable ─────────────────────────────────────────────────────
function onPageChange(page) {
    pagination.currentPage = page
    fetchProductos()
}

function onPerPageChange(val) {
    pagination.perPage = val
    pagination.currentPage = 1
    fetchProductos()
}

function onSearch(val) {
    search.value = val
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        pagination.currentPage = 1
        fetchProductos()
    }, 400)
}

function onSort({ key, dir }) {
    sort.key = key
    sort.dir = dir
    pagination.currentPage = 1
    fetchProductos()
}

// ── Modal crear / editar ───────────────────────────────────────────────────
const modal = reactive({
    open: false,
    mode: 'crear',
    loading: false,
    error: '',
    form: { id: null, referencia: '', nombre: '', precio: '' },
})

function abrirModalCrear() {
    modal.form = { id: null, referencia: '', nombre: '', precio: '' }
    modal.mode = 'crear'
    modal.error = ''
    modal.open = true
}

function abrirModalEditar(row) {
    modal.form = { ...row }
    modal.mode = 'editar'
    modal.error = ''
    modal.open = true
}

function cerrarModal() {
    modal.open = false
}

async function guardarProducto() {
    if (!modal.form.nombre || !modal.form.referencia) {
        modal.error = 'Referencia y nombre son requeridos.'
        return
    }
    modal.loading = true
    modal.error = ''
    try {
        const isEditar = modal.mode === 'editar'
        const response = await fetch(
            isEditar ? `/api/productos/${modal.form.id}` : '/api/productos',
            {
                method: isEditar ? 'PUT' : 'POST',
                headers: {
                    ...authHeaders(),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    referencia: modal.form.referencia,
                    nombre: modal.form.nombre,
                    precio: Number(modal.form.precio),
                }),
            }
        )
        if (!response.ok) throw new Error()
        cerrarModal()
        fetchProductos()
    } catch {
        modal.error = 'No se pudo guardar el producto. Intenta nuevamente.'
    } finally {
        modal.loading = false
    }
}

// ── Modal eliminar ─────────────────────────────────────────────────────────
const deleteModal = reactive({ open: false, loading: false, producto: null })

function confirmarEliminar(row) {
    deleteModal.producto = row
    deleteModal.open = true
}

async function eliminarProducto() {
    deleteModal.loading = true
    try {
        const response = await fetch(
            `/api/productos/${deleteModal.producto.id}`,
            {
                method: 'DELETE',
                headers: authHeaders(),
            }
        )
        if (!response.ok) throw new Error()
        deleteModal.open = false
        fetchProductos()
    } catch {
        console.error('Error al eliminar')
    } finally {
        deleteModal.loading = false
    }
}

// ── Plantilla ──────────────────────────────────────────────────────────────
async function descargarPlantilla() {
    loadingPlantilla.value = true
    try {
        const response = await fetch('/api/productos/plantilla', {
            headers: authHeaders(),
        })
        if (!response.ok) throw new Error()
        const blob = await response.blob()
        const a = document.createElement('a')
        a.href = URL.createObjectURL(blob)
        a.download = 'plantilla_productos.xlsx'
        a.click()
        URL.revokeObjectURL(a.href)
    } catch (err) {
        console.error(err)
    } finally {
        loadingPlantilla.value = false
    }
}

async function subirPlantilla(e) {
    const file = e.target.files[0]
    if (!file) return
    loading.value = true
    try {
        const payload = new FormData()
        payload.append('archivo', file)
        const response = await fetch('/api/productos/importar', {
            method: 'POST',
            headers: authHeaders(),
            body: payload,
        })
        if (!response.ok) throw new Error()
        fetchProductos()
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
        e.target.value = ''
    }
}

function transformProductos(data) {
    productos.value = data.map(producto => ({
        referencia: producto.referencia,
        nombre: producto.nombre,
        precio: producto.precio,
    }))
}

onMounted(async () => {
    start()

    try {
        await fetchProductos()
    } finally {
        stop()
    }
})
</script>
