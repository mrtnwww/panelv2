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
                    <i class="fa-solid fa-plus"></i>
                    Crear producto
                </button>

                <button
                    @click="descargarPlantilla"
                    :disabled="loadingPlantilla"
                    class="h-8 px-3 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] disabled:bg-gray-300 text-white text-xs font-medium transition-all flex items-center gap-1.5"
                >
                    <i class="fa-solid fa-download"></i>
                    Descargar plantilla
                </button>

                <FormInput
                    type="file"
                    accept=".xlsx,xls,.csv"
                    button-label="Subir plantilla"
                    size="sm"
                    @change="subirPlantilla"
                />
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
                        @click.stop="abrirModalEditarProducto(row)"
                        class="btn-table"
                        title="Editar"
                    >
                        <i class="fa fa-pencil"></i>
                    </button>
                    <button
                        @click.stop="eliminarProducto(row)"
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
            :title="
                modal.mode === 'crear' ? 'Nuevo producto' : 'Editar producto'
            "
            size="md"
            :show-footer="true"
            :cancel-label="'Cancelar'"
            :confirm-label="
                modal.mode === 'crear' ? 'Crear producto' : 'Guardar cambios'
            "
            :confirm-loading="modal.loading"
            :close-on-overlay="true"
            @confirm="guardarProducto"
            @update:modelValue="cerrarModal"
        >
            <div class="flex flex-col gap-4">
                <FormInput
                    label="Referencia"
                    v-model="modal.form.referencia"
                    placeholder="Ej: SOAT"
                    required
                />

                <FormInput
                    label="Nombre del producto"
                    v-model="modal.form.nombre"
                    placeholder="Descripción del producto"
                    required
                />

                <FormInput
                    label="Precio"
                    type="number"
                    v-model="modal.form.precio"
                    placeholder="0"
                    required
                />

                <!-- El error va dentro del body, antes del footer -->
                <transition name="fade">
                    <div
                        v-if="modal.error"
                        class="px-3 py-2.5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"
                    >
                        {{ modal.error }}
                    </div>
                </transition>
            </div>
        </AppModal>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'

// -- Componentes -------------------------------------------------------
import DataTable from '@/components/table/DataTable.vue'
import FormInput from '@/components/form/FormInput.vue'
import AppModal from '@/components/AppModal.vue'

// -- Loader ------------------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

// -- DataTable ---------------------------------------------
import { useDataTable } from '@/composables/useDataTable'

import { formatCurrency } from '@/utils/format'
import api from '@/services/api'

import Swal from 'sweetalert2'

// -- Columnas ----------------------------------------------------------
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

// -- Estado --------------------------------------------------------------
const loadingPlantilla = ref(false)
const loading = ref(false)
const productos = ref([])

// -- Backend --------------------------------------------------------------
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

// -- Modal crear / editar ------------------------------------------------
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

function abrirModalEditarProducto(row) {
    modal.form = { ...row }
    modal.mode = 'editar'
    modal.error = ''
    modal.open = true
}

function cerrarModal() {
    modal.open = false
}

async function guardarProducto() {
    if (!modal.form.nombre || !modal.form.referencia || !modal.form.precio) {
        modal.error = 'Referencia, nombre y precio son requeridos.'
        return
    }
    modal.loading = true
    modal.error = ''
    try {
        const isEditar = modal.mode === 'editar'

        const url = '/api/productos'
        const method = isEditar ? 'put' : 'post'

        await api({
            method,
            url,
            data: {
                productos: modal.form,
            },
        })

        cerrarModal()
        fetchProductos()
    } catch (err) {
        console.error(err)
    } finally {
        modal.loading = false
    }
}

function eliminarProducto(row) {
    Swal.fire({
        title: 'Eliminar producto',
        text: `¿Esta seguro(a) de eliminar el producto ${row.nombre}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Aceptar',
        cancelButtonText: 'Cancelar',
    })
        .then(async result => {
            if (result.isConfirmed) {
                await api.delete('/api/productos', {
                    data: {
                        id: row.id,
                    },
                })

                await fetchProductos()
            }
        })
        .catch(err => {
            console.error(err)
        })
}

// -- Plantilla ---------------------------------------------------------
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
        id: producto.id,
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
} = useDataTable(fetchProductos)

onMounted(async () => {
    start()

    try {
        await fetchProductos()
    } finally {
        stop()
    }
})
</script>
