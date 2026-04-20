<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <div class="flex items-center gap-3">
            <h1 class="text-lg font-semibold text-[#0A2540]">
                Validar cliente
            </h1>
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
            :selectable="false"
            empty-message="No se encontraron clientes con los filtros aplicados."
            @update:current-page="onPageChange"
            @update:per-page="onPerPageChange"
            @update:search="onSearch"
            @sort="onSort"
        >
            <!-- Celda acciones -->
            <template #cell-acciones="{ row }">
                <div
                    class="flex items-center gap-2 justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                >
                    <button
                        v-if="row.autorizacionConsulta"
                        class="btn btn-default"
                        @click.stop="validateCliente(row)"
                    >
                        Validar
                        <i class="fa-solid fa-check"></i>
                    </button>
                    <button
                        class="btn btn-default"
                        @click.stop="abrirModal(row)"
                    >
                        Editar
                        <i class="fa-solid fa-pencil"></i>
                    </button>
                    <button
                        class="btn btn-default"
                        @click.stop="envioAutorizacion(row)"
                    >
                        Reenviar autorización
                        <i class="fa-solid fa-envelope"></i>
                    </button>
                </div>
            </template>
        </DataTable>

        <AppModal
            v-model="modal.open"
            title="Editar cliente"
            size="md"
            :show-footer="true"
            :cancel-label="'Cancelar'"
            confirm-label="Guardar"
            :confirm-loading="modal.loading"
            :close-on-overlay="true"
            @confirm="guardarCliente"
            @update:modelValue="cerrarModal"
        >
            <div class="flex flex-col gap-4">
                <FormInput
                    label="Nombre"
                    v-model="modal.form.nombre"
                    placeholder="Maria Perez"
                />

                <FormInput
                    label="Correo"
                    v-model="modal.form.correo"
                    placeholder="cliente@email.com"
                />

                <FormInput
                    label="Teléfono"
                    type="tel"
                    v-model="modal.form.telefono"
                    placeholder="300 123 4567"
                />

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
import { useRouter } from 'vue-router'

// -- Componentes -------------------------------------------
import DataTable from '@/components/table/DataTable.vue'
import FormInput from '@/components/form/FormInput.vue'
import AppModal from '@/components/AppModal.vue'

// -- Loader ------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

// -- Toaster -----------------------------------------------
import { notify } from '@/composables/useNotify'

// Reenviar autorización ------------------------------------
import { useReenviarAutorizacion } from '@/composables/useReenviarAutorizacion'

// -- DataTable ---------------------------------------------
import { useDataTable } from '@/composables/useDataTable'

import api from '@/services/api'

const { reenviarAutorizacion } = useReenviarAutorizacion()

const router = useRouter()

// -- Datos y estado -------------------------------------------
const clientes = ref([])
const loading = ref(false)

// -- Columnas ----------------------------------------------
const columns = [
    { key: 'nombre', label: 'Nombre', sortable: false },
    { key: 'identificacion', label: 'Identificación', sortable: false },
    {
        key: 'autorizacionConsulta',
        label: 'Autorización consulta en centrales',
        sortable: false,
        type: 'boolean',
        align: 'center',
    },
    {
        key: 'pagoConsulta',
        label: 'Pago consulta',
        sortable: false,
        type: 'boolean',
        align: 'center',
    },
    { key: 'correo', label: 'Correo', sortable: false },
    { key: 'telefono', label: 'Teléfono', sortable: false },
    {
        key: 'fechaRegistro',
        label: 'Fecha registro',
        sortable: false,
        type: 'date',
    },
    { key: 'acciones', label: 'Acciones', sortable: false, align: 'center' },
]

// -- Modal crear / editar ------------------------------------------------
const modal = reactive({
    open: false,
    loading: false,
    error: '',
    form: { id: null, nombre: '', correo: '', telefono: '' },
})

// -- BackEnd -------------------------------------------------
async function envioAutorizacion(row) {
    await reenviarAutorizacion(row.id, row.correo)
}

async function fetchClientes() {
    loading.value = true

    try {
        const params = new URLSearchParams({
            page: pagination.currentPage,
            per_page: pagination.perPage,
            sort_key: sort.key,
            sort_dir: sort.dir,
            search: search.value,
        })

        params.append('estado[]', 'pendiente_identidad')

        const { data } = await api.get('/api/clientes', { params })

        const { data: clientesData, total, current_page } = data.clients

        // Lista de clientes
        transformClientes(clientesData)

        // Datos de paginación
        pagination.currentPage = current_page
        pagination.total = total
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
}

// -- Transformar clientes --------------------------------------
function transformClientes(data) {
    clientes.value = data.map(({ cliente }) => ({
        id: cliente.id,
        nombre: cliente.nombre,
        identificacion: cliente.cedula,
        correo: cliente.email,
        telefono: cliente.telefono,
        fechaRegistro: cliente.fecha_creacion,
        autorizacionConsulta: cliente.autorizacion,
        pagoConsulta: false,
    }))
}

function abrirModal(row) {
    modal.form = {
        id: row.id,
        nombre: row.nombre,
        correo: row.correo,
        telefono: row.telefono,
    }
    modal.error = ''
    modal.open = true
}

async function guardarCliente() {
    if (!modal.form.nombre || !modal.form.correo || !modal.form.telefono) {
        notify.error('Los campos nombre, correo y teléfono son requeridos')
        return
    }

    start()

    try {
        await api.put('/api/clientes/actualizarClienteValidar', {
            cliente: modal.form,
        })

        modal.open = false

        notify.success(`${modal.form.nombre} actualizado correctamente.`)

        // Actualizar el listado de clientes
        await fetchClientes()
    } catch (err) {
        notify.error(
            err.response?.data?.message ||
                'Ocurrió un error al guardar la información del cliente'
        )
    } finally {
        stop()
    }
}

function validateCliente(row) {
    router.push(`/clientes/${row.id}/editar`)
}

function cerrarModal() {
    modal.open = false
}

const {
    search,
    pagination,
    sort,
    onPageChange,
    onPerPageChange,
    onSearch,
    onSort,
} = useDataTable(fetchClientes, {
    initialSortKey: 'cliente.id',
})

onMounted(async () => {
    start()

    try {
        await fetchClientes()
    } finally {
        stop()
    }
})
</script>
