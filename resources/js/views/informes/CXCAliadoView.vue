<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">Informe CXC Aliado</h1>

        <!-- Panel de filtros -->
        <div
            class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-5"
        >
            <!-- Fila 1 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <FormInput
                    label="Fecha inicial"
                    type="date"
                    v-model="filters.fechaInicial"
                />
                <FormInput
                    label="Fecha final"
                    type="date"
                    v-model="filters.fechaFinal"
                />
                <FormSelectAsync
                    label="Cliente"
                    v-model="filters.cliente"
                    :fetch-options="opcionesStore.fetchClientesCredits"
                    placeholder="Seleccione un cliente"
                />
                <FormSelectAsync
                    v-model="filters.aliado"
                    label="Aliado"
                    :fetch-options="opcionesStore.fetchEmpresas"
                    placeholder="Seleccione un aliado"
                />
            </div>

            <!-- Fila 2 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <FormInput
                    label="CXC Pendientes"
                    type="select"
                    :options="cxcPendientesOpts"
                    v-model="filters.cxcPendientes"
                    placeholder="Seleccionar CXC Pendiente"
                />
            </div>

            <!-- Botones de informe -->
            <div
                class="flex flex-col items-start justify-end gap-3 pt-1 border-t border-gray-100 xl:flex-row xl:items-end"
            >
                <button
                    @click="generarInforme('resumido')"
                    :disabled="loadingInforme === 'resumido'"
                    class="btn btn-main"
                >
                    <svg
                        v-if="loadingInforme === 'resumido'"
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
                    Generar informe resumido
                </button>
                <button
                    @click="generarInforme('detallado')"
                    :disabled="loadingInforme === 'detallado'"
                    class="btn btn-primary"
                >
                    <svg
                        v-if="loadingInforme === 'detallado'"
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
                    Generar informe detallado
                </button>
            </div>
        </div>

        <!-- DataTable de créditos -->
        <DataTable
            :rows="creditos"
            :columns="columns"
            :loading="loading"
            :total="pagination.total"
            :current-page="pagination.currentPage"
            :per-page="pagination.perPage"
            :sort-key="sort.key"
            :sort-dir="sort.dir"
            :search="search"
            empty-message="No se encontraron créditos con los filtros aplicados."
            @update:current-page="onPageChange"
            @update:per-page="onPerPageChange"
            @update:search="onSearch"
            @sort="onSort"
        >
            <!-- Celda de valor cuota -->
            <template #cell-valorCuota="{ value }">
                <span class="text-gray-700 font-medium">{{
                    formatCurrency(value)
                }}</span>
            </template>

            <!-- Celdas de moneda -->
            <template
                v-for="col in currencyCols"
                #[`cell-${col}`]="{ value }"
                :key="col"
            >
                <span class="text-gray-600">{{ formatCurrency(value) }}</span>
            </template>

            <!-- Acciones -->
            <template #cell-acciones="{ row }">
                <div class="flex items-center gap-1.5">
                    <button
                        @click.stop="editarCXC(row)"
                        class="h-7 px-3 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] text-white text-xs font-medium transition-all"
                    >
                        Editar CXC
                    </button>
                </div>
            </template>

            <!-- Fila de totales -->
            <template #footer>
                <tr
                    v-if="creditos.length > 0 && !loading"
                    class="bg-gray-50 border-t-2 border-gray-200 font-semibold text-sm"
                >
                    <td
                        class="px-3 py-3 text-center text-gray-500 pr-6"
                        :colspan="4"
                    >
                        Total:
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.valorBase) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.valorAval) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.ivaAval) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.valorCredito) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.cxcImpulsa) }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </template>
        </DataTable>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'

// -- Componentes --------------------------------------------------
import FormSelectAsync from '@/components/form/FormSelectAsync.vue'
import FormInput from '@/components/form/FormInput.vue'

import DataTable from '@/components/table/DataTable.vue'

// -- Composables --------------------------------------------------
// -- Datatable ----------------------------------------------------
import { useDataTable } from '@/composables/useDataTable'

// -- Loader -------------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

import { formatCurrency } from '@/utils/format'
import api from '@/services/api'

// -- Store --------------------------------------------------------
import { useOpcionesStore } from '@/stores/opciones'

// -- Columnas -----------------------------------------------------
const columns = [
    { key: 'numCredito', label: 'Núm. Crédito', sortable: false },
    { key: 'fechaCredito', label: 'Fecha crédito', sortable: false },
    { key: 'cliente', label: 'Cliente', sortable: false },
    { key: 'aliado', label: 'Aliado', sortable: false },
    { key: 'valorBase', label: 'Valor base', sortable: false, align: 'right' },
    { key: 'valorAval', label: 'Valor Aval', sortable: false, align: 'right' },
    { key: 'ivaAval', label: 'IVA Aval', sortable: false, align: 'right' },
    {
        key: 'valorCredito',
        label: 'Valor Crédito',
        sortable: false,
        align: 'right',
    },
    {
        key: 'cxcImpulsa',
        label: 'CXC Impulsa',
        sortable: false,
        align: 'right',
    },
    { key: 'placa', label: 'Placa', sortable: false },
    { key: 'producto', label: 'Producto', sortable: false },
    { key: 'referencia', label: 'Referencia', sortable: false },
]

// Columnas que renderizan como moneda via slot dinámico
const currencyCols = [
    'valorBase',
    'valorAval',
    'ivaAval',
    'intereses',
    'valorCredito',
    'totalAbonado',
    'totalPendiente',
    'intMoratorio',
    'gastosCobranza',
    'pendienteMora',
    'cxcImpulsa',
    'cxpAliados',
]

const opcionesStore = useOpcionesStore()

// -- Estado ------------------------------------------------------------------
const creditos = ref([])
const loading = ref(false)
const loadingInforme = ref(null)

// -- Totales ----------------------------------------------------------------
const valoresTotales = ref({})

// -- Filtros ----------------------------------------------------------------
const filters = reactive({
    fechaInicial: '',
    fechaFinal: '',
    cliente: '',
    aliado: '',
    cxcPendientes: '',
})

// -- Opciones de selects ---------------------------------------------------
const cxcPendientesOpts = [
    { value: 'si', label: 'Si' },
    { value: 'no', label: 'No' },
]

// -- Backend ----------------------------------------------------------------
async function fetchCreditos() {
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
            ...(filters.cliente && { cliente_id: filters.cliente }),
            ...(filters.estado && { estado: filters.estado }),
            ...(filters.vencimientoCuota && {
                vencimiento_cuota: filters.vencimientoCuota,
            }),
            ...(filters.destino && { destino: filters.destino }),
            ...(filters.periodicidad && { periodicidad: filters.periodicidad }),
            ...(filters.aliado && { aliado: filters.aliado }),
            ...(filters.porRango && { por_rango: 1 }),
            ...(filters.soloAliados && { solo_aliados: 1 }),
        })

        const { data } = await api.get('/api/creditos/listCredits', { params })

        const { data: creditosData, total, current_page } = data.creditos

        // Lista de créditos
        transformCreditos(creditosData, data.totales)

        pagination.total = total
        pagination.currentPage = current_page
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
}

async function generarInforme(tipo) {
    loadingInforme.value = tipo
    try {
        const params = new URLSearchParams({
            tipo,
            ...(filters.fechaInicial && {
                fecha_inicial: filters.fechaInicial,
            }),
            ...(filters.fechaFinal && { fecha_final: filters.fechaFinal }),
        })

        // const response = await fetch(`/api/creditos/informe?${params}`, {
        //     headers: authHeaders(),
        // })
        // if (!response.ok) throw new Error('Error al generar informe')

        // const blob = await response.blob()
        // const a = document.createElement('a')
        // a.href = URL.createObjectURL(blob)
        // a.download = `informe_creditos_${tipo}.xlsx`
        // a.click()
        // URL.revokeObjectURL(a.href)
    } catch (err) {
        console.error(err)
    } finally {
        loadingInforme.value = null
    }
}

// -- Acciones de fila --------------------------------------------------
function editarCXC(row) {
    console.log('Editar CXC:', row.id)
}

function transformCreditos(data, sumaTotales) {
    const toNumber = v => Number(v) || 0

    const mapCredito = cr => ({
        id: cr.id,
        cliente: cr.nombre,
        valorBase: cr.valor_contado,
        valorAval: cr.valor_aval,
        ivaAval: cr.valor_iva_aval,
        valorCredito: cr.valor_credito,
        numCredito: cr.consecutivo,
        aliado: cr.empresa,
        fechaCredito: cr.fecha_credito,
        cxcImpulsa: cr.valor_cxc,
        placa: cr.placa,
        producto: cr.producto,
        referencia: cr.observacion,
    })

    creditos.value = data.map(mapCredito)

    const totalesMap = {
        valorBase: 'valor_contado',
        valorAval: 'total_aval',
        ivaAval: 'total_iva_aval',
        valorCredito: 'valor_credito',
        cxcImpulsa: 'valor_cxc',
    }

    valoresTotales.value = Object.fromEntries(
        Object.entries(totalesMap).map(([key, source]) => [
            key,
            toNumber(sumaTotales[source]),
        ])
    )
}

const {
    search,
    pagination,
    sort,
    onPageChange,
    onPerPageChange,
    onSearch,
    onSort,
} = useDataTable(fetchCreditos, {
    initialSortKey: 'numCredito',
})

onMounted(async () => {
    start()

    try {
        await fetchCreditos()
    } finally {
        stop()
    }
})
</script>
