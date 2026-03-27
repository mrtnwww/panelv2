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
                        class="btn-table"
                        @click.stop="validateCliente(row)"
                    >
                        Validar
                        <i class="fa-solid fa-check"></i>
                    </button>
                    <button class="btn-table" @click.stop="editCliente(row)">
                        Editar
                        <i class="fa-solid fa-pencil"></i>
                    </button>
                    <button
                        class="btn-table"
                        @click.stop="sendAutorizacion(row)"
                    >
                        Reenviar autorización
                        <i class="fa-solid fa-envelope"></i>
                    </button>
                </div>
            </template>
        </DataTable>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

// -- Componentes --------------------------------------------
import DataTable from '@/components/table/DataTable.vue'

import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

import api from '@/services/api'

const router = useRouter()

// -- Columnas -------------------------------------------------
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

// -- Datos y estado -----------------------------------------------------------
const clientes = ref([])
const loading = ref(false)
const search = ref('')
let searchTimeout = null

const pagination = reactive({
    currentPage: 1,
    perPage: 10,
    total: 0,
})

const sort = reactive({
    key: 'cliente.id',
    dir: 'desc',
})

// -- Llamada al backend -------------------------------------------------------
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

// -- Handlers de eventos del DataTable --------------------------------------
function onPageChange(page) {
    pagination.currentPage = page
    fetchClientes()
}

function onPerPageChange(val) {
    pagination.perPage = val
    pagination.currentPage = 1
    fetchClientes()
}

function onSearch(val) {
    search.value = val
    // Debounce: espera 400ms antes de llamar al backend
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        pagination.currentPage = 1
        fetchClientes()
    }, 400)
}

function onSort({ key, dir }) {
    sort.key = key
    sort.dir = dir
    pagination.currentPage = 1
    fetchClientes()
}

// -- Transformar clientes ----------------------------------------------------
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

function validateCliente(row) {
    router.push(`/clientes/${row.id}/editar`)
}

// -- Carga inicial -----------------------------------------------------------
onMounted(async () => {
    start()

    try {
        await fetchClientes()
    } finally {
        stop()
    }
})
</script>
