<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">Informe comisiones</h1>

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
                    v-model="filters.aliado"
                    label="Aliado"
                    :fetch-options="opcionesStore.fetchEmpresas"
                    placeholder="Seleccione un aliado"
                />
                <FormSelectAsync
                    label="Cajera"
                    v-model="filters.cajero"
                    :fetch-options="opcionesStore.fetchCajeras"
                    placeholder="Seleccione una cajera"
                />
            </div>

            <!-- Botones de informe -->
            <div
                class="flex flex-col items-start justify-end gap-3 pt-1 border-t border-gray-100 xl:flex-row xl:items-end"
            >
                <button
                    @click="generarInforme('resumido')"
                    :disabled="loadingInforme === 'resumido'"
                    class="h-9 px-5 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] disabled:bg-gray-300 text-white text-sm font-medium transition-all flex items-center gap-2"
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
                    class="h-9 px-5 rounded-lg bg-emerald-500 hover:bg-emerald-600 disabled:bg-gray-300 text-white text-sm font-medium transition-all flex items-center gap-2"
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
                        {{ formatCurrency(valoresTotales.valorCredito) }}
                    </td>
                    <td colspan="1"></td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.valorComision) }}
                    </td>
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
    {
        key: 'valorCredito',
        label: 'Valor Crédito',
        sortable: false,
        align: 'right',
    },
    { key: 'cajera', label: 'Cajera', sortable: false },
    {
        key: 'valorComision',
        label: 'Comisión',
        sortable: false,
        align: 'right',
    },
]

// Columnas que renderizan como moneda via slot dinámico
const currencyCols = ['valorBase', 'valorCredito', 'valorComision']

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
    cajero: '',
    aliado: '',
})

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

function transformCreditos(data, sumaTotales) {
    const toNumber = v => Number(v) || 0

    const mapCredito = cr => ({
        id: cr.id,
        cliente: cr.nombre,
        valorBase: cr.valor_contado,
        valorCredito: cr.valor_credito,
        numCredito: cr.consecutivo,
        aliado: cr.empresa,
        fechaCredito: cr.fecha_credito,
        cajera: cr.usuario,
        valorComision: cr.comision_cajera,
    })

    creditos.value = data.map(mapCredito)

    const totalesMap = {
        valorBase: 'valor_contado',
        valorCredito: 'valor_credito',
        valorComision: 'valor_comisiones',
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
