<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">
            Informe corresponsal
        </h1>

        <!-- Panel de filtros -->
        <div
            class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-5"
        >
            <!-- Fila 1 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
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
                <FormInput
                    label="Estado de crédito"
                    type="select"
                    v-model="filters.estadoCredito"
                    :options="estadoCreditoOpts"
                    placeholder="Seleccionar estado de crédito"
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
                    Generar informe
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
            <!-- Botón actualizar en barra -->
            <template #actions>
                <UpdateMoraButton :onSuccess="fetchCreditos" />
            </template>

            <!-- Estado del crédito con badge de color -->
            <template #cell-estadoCredito="{ value }">
                <span
                    :class="[
                        'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap',
                        estadoBadgeClass(value),
                    ]"
                >
                    {{ value }}
                </span>
            </template>

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
        </DataTable>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'

// -- Componentes --------------------------------------------------
import FormInput from '@/components/form/FormInput.vue'

import UpdateMoraButton from '@/components/table/UpdateMoraButton.vue'
import DataTable from '@/components/table/DataTable.vue'

// -- Composables --------------------------------------------------
// -- Datatable ----------------------------------------------------
import { useDataTable } from '@/composables/useDataTable'

// -- Loader -------------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

import { formatCurrency } from '@/utils/format'
import api from '@/services/api'

// -- Columnas -----------------------------------------------------
const columns = [
    { key: 'cedula', label: 'Identificador del pagador', sortable: false },
    { key: 'cliente', label: 'Cliente', sortable: false },
    {
        key: 'valorMinPago',
        label: 'Valor mínimo a pagar',
        sortable: false,
        align: 'right',
    },
    {
        key: 'referencia1',
        label: 'Referencia 1 (Identificador del pagador)',
        sortable: false,
    },
    {
        key: 'referencia2',
        label: 'Referencia 2 (Número de crédito)',
        sortable: false,
    },
    {
        key: 'fechaVencimiento',
        label: 'Fecha de vencimiento (Cuota pendiente)',
        sortable: false,
    },
    { key: 'fechaCredito', label: 'Fecha crédito', sortable: false },
    { key: 'estadoCredito', label: 'Estado', sortable: false },
]

// Columnas que renderizan como moneda via slot dinámico
const currencyCols = ['valorMinPago']

// -- Estado ---------------------------------------------------------
const creditos = ref([])
const loading = ref(false)
const loadingInforme = ref(null)

// -- Filtros --------------------------------------------------------
const filters = reactive({
    estadoCredito: '',
    fechaInicial: '',
    fechaFinal: '',
})

// -- Opciones select ------------------------------------------------
const estadoCreditoOpts = [
    { value: 'vigente', label: 'Al día' },
    { value: 'mora', label: 'En mora' },
]

function estadoBadgeClass(estado) {
    if (!estado) return 'bg-gray-100 text-gray-500'
    const s = estado.toLowerCase()
    if (s.includes('mora')) return 'bg-red-50 text-red-600'
    if (s.includes('al día')) return 'bg-emerald-50 text-emerald-700'
    return 'bg-blue-50 text-blue-600'
}

// -- Backend ---------------------------------------------------------
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

        const { data } = await api.get(
            '/api/creditos/listCreditsCorresponsal',
            { params }
        )

        const { data: creditosData, total, current_page } = data.creditos

        // Lista de créditos
        transformCreditos(creditosData)

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

function transformCreditos(data) {
    creditos.value = data.map(cr => ({
        cedula: cr.cliente.cedula,
        cliente: cr.cliente.nombre,
        valorMinPago: cr.cuotaMinPago,
        referencia1: cr.cliente.cedula.padStart(13, '0'),
        referencia2: cr.id,
        fechaVencimiento: cr.fecha_vencimiento,
        fechaCredito: cr.created_at,
        estadoCredito: cr.estado,
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
