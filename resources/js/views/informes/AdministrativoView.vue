<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">
            Informe administrativo
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
                <FormSelectAsync
                    label="Aliado"
                    v-model="filters.aliado"
                    :fetch-options="opcionesStore.fetchEmpresas"
                    placeholder="Seleccione un aliado"
                />
            </div>

            <!-- Fila 2: checkboxes + botones -->
            <div
                class="flex flex-wrap items-center gap-x-5 gap-y-3 pt-3 border-t border-gray-100"
            >
                <!-- Checkboxes -->
                <FormCheckbox
                    v-model="filters.abonosMes"
                    label="Abonos del mes"
                />
                <FormCheckbox
                    v-model="filters.consolidarEmpresas"
                    label="Consolidar empresas"
                />

                <!-- Botones -->
                <div class="flex flex-wrap items-center gap-2 sm:ml-auto">
                    <button
                        @click="generarInforme('resumido')"
                        :disabled="loadingInforme === 'resumido'"
                        class="h-9 px-4 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] disabled:bg-gray-300 text-white text-sm font-medium transition-all flex items-center gap-2"
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
        </div>

        <!-- DataTable -->
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
            empty-message="No se encontraron registros con los filtros aplicados."
            @update:current-page="onPageChange"
            @update:per-page="onPerPageChange"
            @update:search="onSearch"
            @sort="onSort"
        >
            <!-- Celdas de moneda -->
            <template
                v-for="col in currencyCols"
                #[`cell-${col}`]="{ value }"
                :key="col"
            >
                <span class="font-medium text-gray-600">{{
                    formatCurrency(value)
                }}</span>
            </template>

            <!-- Fila de totales -->
            <template #footer>
                <tr
                    v-if="creditos.length > 0 && !loading"
                    class="bg-gray-50 border-t-2 border-gray-200 font-semibold text-sm"
                >
                    <td
                        class="px-3 py-3 text-center text-gray-500 pr-6"
                        :colspan="2"
                    >
                        Total:
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.credito) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.compra) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.abono) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.saldoRecuperacion) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.saldoUtilidad) }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 text-right">
                        {{ formatCurrency(valoresTotales.saldo) }}
                    </td>
                </tr>
            </template>
        </DataTable>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'

// -- Componentes -----------------------------------------------------------
import FormSelectAsync from '@/components/form/FormSelectAsync.vue'
import FormCheckbox from '@/components/form/FormCheckbox.vue'
import FormInput from '@/components/form/FormInput.vue'

import DataTable from '@/components/table/DataTable.vue'

// -- Loader ----------------------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

// -- Composables ----------------------------------------------------------
// -- Datatable ------------------------------------------------------------
import { useDataTable } from '@/composables/useDataTable'

import { formatCurrency } from '@/utils/format'
import api from '@/services/api'

// -- Store -----------------------------------------------------------------
import { useOpcionesStore } from '@/stores/opciones'

// -- Columnas --------------------------------------------------------------
const columns = [
    { key: 'mes', label: 'mes', sortable: false },
    { key: 'empresa', label: 'Empresa', sortable: false, truncate: false },
    {
        key: 'valorCredito',
        label: 'Créditos',
        sortable: false,
        align: 'right',
    },
    {
        key: 'ventasBase',
        label: 'Ventas base',
        sortable: false,
        align: 'right',
    },
    { key: 'abonos', label: 'Abonos', sortable: false, align: 'right' },
    {
        key: 'saldoRecuperacion',
        label: 'Saldo (Recuperación)',
        sortable: false,
        align: 'right',
    },
    {
        key: 'saldoUtilidad',
        label: 'Saldo (Utilidad)',
        sortable: false,
        align: 'right',
    },
    {
        key: 'saldoTotal',
        label: 'Saldo (Total)',
        sortable: false,
        align: 'right',
    },
]

// Columnas que renderizan como moneda via slot dinámico
const currencyCols = [
    'valorCredito',
    'ventasBase',
    'abonos',
    'saldoRecuperacion',
    'saldoUtilidad',
    'saldoTotal',
]

const opcionesStore = useOpcionesStore()

// -- Estado ----------------------------------------------------------------
const creditos = ref([])
const loading = ref(false)
const loadingInforme = ref(null)

// -- Totales ----------------------------------------------------------------
const valoresTotales = ref({})

// -- Filtros ----------------------------------------------------------------
const filters = reactive({
    fechaInicial: '',
    fechaFinal: '',
    aliado: '',
    abonosMes: false,
    consolidarEmpresas: false,
})

let empresaPrincipal = ''

function authHeaders() {
    return {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    }
}

// -- Backend ----------------------------------------------------------
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
            ...(filters.recibidoEn && { recibido_en: filters.recibidoEn }),
            ...(filters.cliente && { cliente_id: filters.cliente }),
            ...(filters.cajero && { cajero_id: filters.cajero }),
            ...(filters.aliado && { aliado: filters.aliado }),
            ...(filters.diasMora && { dias_mora: 1 }),
            ...(filters.abonoAval && { abono_aval: 1 }),
        })

        const { data } = await api.get(
            '/api/creditos/listCreditsAdministrativo',
            { params }
        )

        const { data: creditosData, total, current_page } = data.creditos

        // Lista de creditos
        transformCreditos(creditosData, data.totales)

        pagination.total = total
        pagination.currentPage = current_page

        empresaPrincipal = data.empresaPrincipal
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
}

async function descargarArchivo(url, nombre) {
    const response = await fetch(url, { headers: authHeaders() })
    if (!response.ok) throw new Error('Error al generar archivo')
    const blob = await response.blob()
    const a = document.createElement('a')
    a.href = URL.createObjectURL(blob)
    a.download = nombre
    a.click()
    URL.revokeObjectURL(a.href)
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
        await descargarArchivo(
            `/api/abonos/informe?${params}`,
            `informe_abonos_${tipo}.xlsx`
        )
    } catch (err) {
        console.error(err)
    } finally {
        loadingInforme.value = null
    }
}

function transformCreditos(data, sumaTotales) {
    const toNumber = v => Number(v) || 0

    creditos.value = data.map(cr => ({
        mes: cr.fecha,
        empresa: cr.resumen.empresa || empresaPrincipal || '',
        valorCredito: cr.suma_credito,
        ventasBase: cr.suma_compra,
        abonos: cr.resumen.abonos,
        saldoRecuperacion: cr.resumen.saldo_recuperacion,
        saldoUtilidad: cr.resumen.saldo_utilidad,
        saldoTotal: cr.resumen.saldo_total,
    }))

    const totalesMap = {
        saldoRecuperacion: 'saldo_recuperacion',
        saldoUtilidad: 'saldo_utilidad',
        credito: 'total_credito',
        compra: 'total_compra',
        abono: 'total_abonos',
        saldo: 'saldo_total',
        abonoMes: 'total_abono_mes',
        saldoCaja: 'total_saldo_caja',
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
} = useDataTable(fetchCreditos)

onMounted(async () => {
    start()

    try {
        await fetchCreditos()
    } finally {
        stop()
    }
})
</script>
