<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">Recibo de caja CXC</h1>

        <!-- Panel de filtros -->
        <div
            class="bg-white rounded-xl border border-gray-200 px-4 py-4 sm:px-6"
        >
            <div
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 items-end gap-4"
            >
                <!-- Fecha Inicial -->
                <FormInput
                    label="Fecha inicial"
                    type="date"
                    v-model="filters.fechaInicial"
                    class="w-full"
                />

                <!-- Fecha Final -->
                <FormInput
                    label="Fecha final"
                    type="date"
                    v-model="filters.fechaFinal"
                    class="w-full"
                />

                <!-- Aliado: ocupa 1 columna en móvil/tablet y puede crecer en desktop si quieres -->
                <FormSelectAsync
                    label="Aliado"
                    v-model="filters.establecimiento"
                    :fetch-options="opcionesStore.fetchEmpresas"
                    placeholder="Seleccione un aliado"
                    wrapper-class="w-full"
                    @change="procesarAliados($event)"
                />

                <!-- Botón Generar CXC -->
                <button
                    @click="abrirModal"
                    class="btn btn-primary w-full h-10.5 flex justify-center items-center gap-2 sm:w-fit"
                >
                    Generar CXC
                </button>
            </div>

            <div
                class="flex flex-row items-center justify-end gap-2 pt-4 mt-4 border-t border-gray-100"
            >
                <button
                    @click="resetFilters"
                    class="btn flex-1 sm:flex-none border border-gray-200 text-sm text-gray-500 hover:bg-gray-50 hover:border-gray-300"
                >
                    Limpiar
                </button>
                <button
                    @click="fetchRecibos"
                    class="btn btn-main flex-1 sm:flex-none"
                >
                    Aplicar filtros
                </button>
            </div>
        </div>

        <!-- DataTable -->
        <DataTable
            :rows="recibos"
            :columns="columns"
            :loading="loading"
            :total="pagination.total"
            :current-page="pagination.currentPage"
            :per-page="pagination.perPage"
            :sort-key="sort.key"
            :sort-dir="sort.dir"
            :search="search"
            empty-message="No se encontraron recibos con los filtros aplicados."
            @update:current-page="onPageChange"
            @update:per-page="onPerPageChange"
            @update:search="onSearch"
            @sort="onSort"
        >
            <!-- Valor CXC -->
            <template #cell-valorCXC="{ value }">
                <span class="tabular-nums font-medium text-gray-700">{{
                    formatCurrency(value)
                }}</span>
            </template>

            <!-- Comisiones -->
            <template #cell-comisiones="{ value }">
                <span class="tabular-nums text-gray-600">{{
                    formatCurrency(value)
                }}</span>
            </template>

            <!-- Acciones: botón imprimir -->
            <template #cell-acciones="{ row }">
                <div class="flex justify-center">
                    <button
                        @click.stop="imprimirRecibo(row)"
                        class="w-8 h-8 rounded-lg bg-emerald-500 hover:bg-emerald-600 flex items-center justify-center text-white transition-all"
                        title="Imprimir recibo"
                    >
                        <i class="fa fa-print"></i>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Modal crear recibo de caja CXC -->
        <AppModal
            :model-value="modal.open"
            :title="`Recibo de caja - ${nombreAliadoCXC}`"
            size="lg"
            :show-footer="true"
            cancel-label="Cancelar"
            confirm-label="Guardar"
            :confirm-loading="modal.loading"
            :close-on-overlay="true"
            @update:modelValue="cerrarModal"
        >
            <div class="flex flex-col gap-4">
                <FormInput
                    label="Cliente"
                    type="select"
                    v-model="modal.form.cliente"
                    :options="clientesOpts"
                    placeholder="Seleccione un cliente"
                    :searchable="true"
                    @update:modelValue="procesarCliente"
                />

                <FormInput
                    label="Tipo"
                    type="select"
                    v-model="modal.form.tipoTarea"
                    :options="creditosOpts"
                    placeholder="Seleccione un crédito"
                    :searchable="true"
                />

                <FormSelectAsync
                    label="Productos"
                    v-model="modal.form.producto"
                    :fetch-options="opcionesStore.fetchProductos"
                    placeholder="Selecciona un producto"
                />

                <div class="flex justify-center">
                    <button class="btn btn-main">Añadir</button>
                </div>

                <TableGrid :items="[]" :columns="cols" />

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

// -- Componentes ------------------------------------------
import FormSelectAsync from '@/components/form/FormSelectAsync.vue'
import DataTable from '@/components/table/DataTable.vue'
import FormInput from '@/components/form/FormInput.vue'
import TableGrid from '@/components/TableGrid.vue'
import AppModal from '@/components/AppModal.vue'

// -- Loader -----------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

// -- Toaster -----------------------------------------------
import { notify } from '@/composables/useNotify'

// -- Composables ------------------------------------------
// -- DataTable --------------------------------------------
import { useDataTable } from '@/composables/useDataTable'

import { formatCurrency } from '@/utils/format'
import api from '@/services/api'

// -- Store -------------------------------------------------
import { useOpcionesStore } from '@/stores/opciones'

// -- Columnas ----------------------------------------------
const columns = [
    { key: 'fecha', label: 'Fecha', sortable: false, align: 'center' },
    { key: 'establecimiento', label: 'Establecimiento', sortable: false },
    { key: 'valorCXC', label: 'Valor CXC', sortable: false, align: 'right' },
    { key: 'comisiones', label: 'Comisiones', sortable: false, align: 'right' },
    { key: 'acciones', label: 'Acciones', sortable: false, align: 'center' },
]

const cols = [
    { key: 'cliente', label: 'Cliente', headerClass: 'text-center' },
    {
        key: 'credito',
        label: 'Crédito',
        headerClass: 'text-center',
    },
    {
        key: 'producto',
        label: 'Producto',
        headerClass: 'text-center',
    },
]

const opcionesStore = useOpcionesStore()

// -- Estado ------------------------------------------------
const recibos = ref([])
const clientes = ref([])
const productos = ref([])
const loading = ref(false)
const nombreAliadoCXC = ref('')

// -- Filtros -----------------------------------------------
const filters = reactive({
    fechaInicial: '',
    fechaFinal: '',
    establecimiento: '',
})

// -- Opciones ----------------------------------------------
const clientesOpts = ref([])
const creditosOpts = ref([])
const productosOpts = ref([])

function authHeaders() {
    return {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    }
}

// -- Modal crear recibo de caja CXC ----------------------------------
const modal = reactive({
    open: false,
    loading: false,
    error: '',
    form: {
        cliente: '',
        credito: '',
        producto: '',
    },
})

// -- Backend ---------------------------------------------------------------
async function fetchRecibos() {
    loading.value = true
    try {
        const params = new URLSearchParams({
            page: pagination.currentPage,
            per_page: pagination.perPage,
            sort_key: sort.key,
            sort_dir: sort.dir,
            search: search.value,
            ...(filters.fechaInicial && {
                fecha_inicial: filters.fechaInicial,
            }),
            ...(filters.fechaFinal && { fecha_final: filters.fechaFinal }),
            ...(filters.establecimiento && {
                establecimiento: filters.establecimiento,
            }),
        })

        const { data } = await api.get('/api/contabilidad/listRecibosCXC', {
            params,
        })

        const { data: recibosData, total, current_page } = data.recibosCaja

        transformarRecibos(recibosData)

        pagination.total = total
        pagination.currentPage = current_page
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
}

async function procesarAliados(id) {
    if (!id) {
        nombreAliadoCXC.value = ''
        clientes.value = []
        return
    }

    // Nombre empresas gestionada a mostrar en el encabezado del modal
    const aliado = opcionesStore.empresas.find(e => e.id == id)
    if (aliado) nombreAliadoCXC.value = aliado.razon_social

    start()

    try {
        const { data } = await api.get('api/clientes/getClientesAliado', {
            params: {
                idAliado: filters.establecimiento,
            },
        })

        clientes.value = data.clientes
    } catch (e) {
        notify.error('Ocurrió un error al obtener la información del aliado.')
    } finally {
        stop()
    }
}

function procesarCliente() {
    const clienteEncontrado = clientes.value.find(
        c => c.id == modal.form.cliente
    )

    if (clienteEncontrado && clienteEncontrado.credito) {
        creditosOpts.value = clienteEncontrado.credito.map(cr => ({
            value: cr.id,
            label: `Crédito ${cr.id} (${formatCurrency(cr.valor_compra)})`,
        }))
    } else {
        creditosOpts.value = []
    }
}

async function imprimirRecibo(row) {
    try {
        const response = await fetch(
            `/api/contabilidad/recibo-caja-cxc/${row.id}/imprimir`,
            {
                headers: authHeaders(),
            }
        )
        if (!response.ok) throw new Error()
        const blob = await response.blob()
        const url = URL.createObjectURL(blob)
        window.open(url, '_blank')
        setTimeout(() => URL.revokeObjectURL(url), 10000)
    } catch (err) {
        console.error(err)
    }
}

function abrirModal() {
    if (!filters.establecimiento) {
        notify.error('Es necesario seleccionar un aliado.')
        return
    }

    if (!clientes.value.length) {
        notify.error(
            'No se encontraron créditos vigentes para el aliado seleccionado.'
        )
        return
    }

    // Listado de clientes
    clientesOpts.value = clientes.value.map(cl => ({
        value: cl.id,
        label: `${cl.nombre} - (${cl.cedula})`,
    }))

    Object.assign(modal.form, {
        cliente: '',
        credito: '',
        producto: '',
    })
    modal.error = ''
    modal.open = true
}

function cerrarModal() {
    modal.open = false
}

async function resetFilters() {
    filters.fechaInicial = ''
    filters.fechaFinal = ''
    filters.establecimiento = ''
    await fetchRecibos()
}

function transformarRecibos(data) {
    recibos.value = data.map(recibo => ({
        establecimiento: recibo.empresa.razon_social,
        valorCXC: recibo.valor_cxc,
        fecha: recibo.fecha,
        comisiones: 30000, // Valor de comisiones por defecto
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
} = useDataTable(fetchRecibos, {
    initialSortKey: 'fecha',
})

onMounted(async () => {
    start()

    try {
        await fetchRecibos()
    } finally {
        stop()
    }
})
</script>
