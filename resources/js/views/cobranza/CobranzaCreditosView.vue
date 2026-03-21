<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">Créditos</h1>

        <!-- Panel de filtros -->
        <div
            class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-5"
        >
            <!-- Fila 1 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Estado de crédito -->
                <div class="flex flex-col gap-1.5">
                    <FormInput
                        label="Estado de crédito"
                        type="select"
                        v-model="filters.estadoCredito"
                        :options="estadoCreditoOpts"
                        placeholder="Seleccionar estado de crédito"
                    />
                </div>

                <!-- Meses pagados + Por rango -->
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between">
                        <label :class="labelClass">Meses pagados</label>
                        <FormCheckbox
                            v-model="filters.mesesPorRango"
                            label="Por rango"
                        />
                    </div>
                    <div
                        class="grid gap-2"
                        :class="
                            filters.mesesPorRango
                                ? 'grid-cols-2'
                                : 'grid-cols-1'
                        "
                    >
                        <input
                            v-model="filters.mesesPagados"
                            type="number"
                            min="0"
                            placeholder="Digite el número de meses"
                            :class="inputClass"
                        />
                        <input
                            v-if="filters.mesesPorRango"
                            v-model="filters.mesesPagadosHasta"
                            type="number"
                            min="0"
                            placeholder="Hasta"
                            :class="inputClass"
                        />
                    </div>
                </div>

                <!-- Notificación -->
                <div class="flex flex-col gap-1.5">
                    <FormInput
                        label="Notificación"
                        type="select"
                        v-model="filters.notificacion"
                        :options="notificacionOpts"
                        placeholder="Seleccionar notificación"
                    />
                </div>
            </div>

            <!-- Fila 2 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Reporte -->
                <div class="flex flex-col gap-1.5">
                    <FormInput
                        label="Reporte"
                        type="select"
                        v-model="filters.reporte"
                        :options="reporteOpts"
                        placeholder="Seleccionar reporte"
                    />
                </div>

                <!-- Aliado -->
                <div class="flex flex-col gap-1.5">
                    <FormInput
                        label="Aliado"
                        type="select"
                        v-model="filters.aliado"
                        :options="aliadoOpts"
                        placeholder="Seleccione un aliado"
                    />
                </div>

                <!-- Mes de corte -->
                <div class="flex flex-col gap-1.5">
                    <label :class="labelClass">Mes de corte</label>
                    <input
                        v-model="filters.mesCorte"
                        type="month"
                        :class="inputClass"
                    />
                </div>
            </div>

            <!-- Fila 3 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Estado de cliente -->
                <div class="flex flex-col gap-1.5">
                    <label :class="labelClass">Estado de cliente</label>
                    <FormInput
                        label="Aliado"
                        type="select"
                        v-model="filters.estadoCliente"
                        :options="estadoClienteOpts"
                        placeholder="Seleccione un estado"
                    />
                </div>
            </div>

            <!-- Búsqueda avanzada toggle -->
            <div class="pt-1 border-t border-gray-100">
                <label class="flex items-center gap-2 cursor-pointer w-fit">
                    <div class="relative">
                        <input
                            v-model="busquedaAvanzada"
                            type="checkbox"
                            class="peer sr-only"
                        />
                        <div
                            class="w-4 h-4 rounded border-2 border-gray-200 bg-gray-50 peer-checked:bg-[#1a5c2a] peer-checked:border-[#1a5c2a] transition-all flex items-center justify-center"
                        >
                            <svg
                                v-if="busquedaAvanzada"
                                width="8"
                                height="8"
                                viewBox="0 0 8 8"
                                fill="none"
                            >
                                <path
                                    d="M1 4L3 6.5L7 1.5"
                                    stroke="white"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </div>
                    </div>
                    <span class="text-sm font-medium text-gray-600"
                        >Búsqueda avanzada</span
                    >
                </label>
            </div>

            <!-- Campos búsqueda avanzada -->
            <transition name="slide">
                <div v-if="busquedaAvanzada" class="flex flex-col gap-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Cuotas pagadas -->
                        <div class="flex flex-col gap-1.5">
                            <div class="flex items-center justify-between">
                                <label :class="labelClass"
                                    >Cuotas pagadas</label
                                >
                                <FormCheckbox
                                    v-model="filters.cuotasPorRango"
                                    label="Por rango"
                                />
                            </div>
                            <div
                                class="grid gap-2"
                                :class="
                                    filters.cuotasPorRango
                                        ? 'grid-cols-2'
                                        : 'grid-cols-1'
                                "
                            >
                                <input
                                    v-model="filters.cuotasPagadas"
                                    type="number"
                                    min="0"
                                    placeholder="Digite el número de cuotas"
                                    :class="inputClass"
                                />
                                <input
                                    v-if="filters.cuotasPorRango"
                                    v-model="filters.cuotasPagadasHasta"
                                    type="number"
                                    min="0"
                                    placeholder="Hasta"
                                    :class="inputClass"
                                />
                            </div>
                        </div>

                        <!-- Número de días -->
                        <div class="flex flex-col gap-1.5">
                            <div class="flex items-center justify-between">
                                <label :class="labelClass"
                                    >Número de días</label
                                >
                                <FormCheckbox
                                    v-model="filters.diasPorRango"
                                    label="Por rango"
                                />
                            </div>
                            <div
                                class="grid gap-2"
                                :class="
                                    filters.diasPorRango
                                        ? 'grid-cols-2'
                                        : 'grid-cols-1'
                                "
                            >
                                <input
                                    v-model="filters.numeroDias"
                                    type="number"
                                    min="0"
                                    placeholder="Digite el número de días"
                                    :class="inputClass"
                                />
                                <input
                                    v-if="filters.diasPorRango"
                                    v-model="filters.numeroDiasHasta"
                                    type="number"
                                    min="0"
                                    placeholder="Hasta"
                                    :class="inputClass"
                                />
                            </div>
                        </div>

                        <!-- Vencimiento de cuota -->
                        <div class="flex flex-col gap-1.5">
                            <div class="flex items-center justify-between">
                                <label :class="labelClass"
                                    >Vencimiento de cuota</label
                                >
                                <FormCheckbox
                                    v-model="filters.vencimientoPorRango"
                                    label="Por rango"
                                />
                            </div>
                            <div
                                class="grid gap-2"
                                :class="
                                    filters.vencimientoPorRango
                                        ? 'grid-cols-2'
                                        : 'grid-cols-1'
                                "
                            >
                                <input
                                    v-model="filters.vencimientoCuota"
                                    type="date"
                                    :class="inputClass"
                                />
                                <input
                                    v-if="filters.vencimientoPorRango"
                                    v-model="filters.vencimientoCuotaHasta"
                                    type="date"
                                    :class="inputClass"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Tipo de informe + botones -->
            <div
                class="flex flex-col sm:flex-row sm:items-end gap-4 pt-3 border-t border-gray-100"
            >
                <!-- Radio tipo informe -->
                <div class="flex flex-col gap-2">
                    <label
                        v-for="tipo in tiposInforme"
                        :key="tipo.value"
                        class="flex items-center gap-2.5 cursor-pointer group"
                    >
                        <div class="relative shrink-0">
                            <input
                                v-model="filters.tipoInforme"
                                type="radio"
                                :value="tipo.value"
                                class="peer sr-only"
                            />
                            <div
                                class="w-4 h-4 rounded-full border-2 border-gray-200 bg-gray-50 peer-checked:border-[#1a5c2a] transition-all flex items-center justify-center"
                            >
                                <div
                                    v-if="filters.tipoInforme === tipo.value"
                                    class="w-2 h-2 rounded-full bg-[#1a5c2a]"
                                />
                            </div>
                        </div>
                        <span
                            class="text-sm text-gray-600 group-hover:text-gray-800 transition-colors"
                        >
                            {{ tipo.label }}
                        </span>
                    </label>
                </div>

                <!-- Botones descarga -->
                <div class="flex flex-wrap gap-2 sm:ml-auto">
                    <button
                        @click="descargarInforme"
                        :disabled="loadingInforme === 'informe'"
                        class="h-9 px-4 rounded-lg bg-[#0A2540] hover:bg-[#0d2f50] disabled:bg-gray-300 text-white text-sm font-medium transition-all flex items-center gap-2"
                    >
                        <svg
                            v-if="loadingInforme === 'informe'"
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
                        Descargar informe
                    </button>
                    <button
                        @click="descargarHabitoPago"
                        :disabled="loadingInforme === 'habito'"
                        class="h-9 px-4 rounded-lg bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 text-white text-sm font-medium transition-all flex items-center gap-2"
                    >
                        <svg
                            v-if="loadingInforme === 'habito'"
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
                        Informe hábito de pago
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
            :selectable="true"
            :selected-rows="selected"
            :all-selected="allSelected"
            empty-message="No se encontraron créditos con los filtros aplicados."
            @update:current-page="onPageChange"
            @update:per-page="onPerPageChange"
            @update:search="onSearch"
            @sort="onSort"
            @toggle-all="onToggleAll"
            @toggle-row="onToggleRow"
        >
            <!-- Actualizar valores -->
            <template #actions>
                <button
                    @click="fetchCreditos"
                    class="h-8 px-3 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium transition-all flex items-center gap-1.5"
                >
                    <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                        <path
                            d="M11 6.5A4.5 4.5 0 1 1 6.5 2"
                            stroke="currentColor"
                            stroke-width="1.3"
                            stroke-linecap="round"
                        />
                        <path
                            d="M9 2h2.5V4.5"
                            stroke="currentColor"
                            stroke-width="1.3"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    Actualizar valores
                </button>
            </template>

            <!-- Estado cliente badge -->
            <template #cell-estadoCliente="{ value }">
                <span
                    v-if="value"
                    :class="[
                        'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap',
                        estadoClienteBadge(value),
                    ]"
                >
                    {{ value }}
                </span>
            </template>

            <!-- Valor compra + crédito como moneda -->
            <template #cell-valorCompra="{ value }">
                <span class="tabular-nums text-gray-600">{{
                    formatCurrency(value)
                }}</span>
            </template>
            <template #cell-valorCredito="{ value }">
                <span class="tabular-nums text-gray-600">{{
                    formatCurrency(value)
                }}</span>
            </template>

            <!-- Acciones -->
            <template #cell-acciones="{ row }">
                <div class="flex items-center gap-1.5">
                    <button
                        @click.stop="verDetalle(row)"
                        class="h-7 px-3 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] text-white text-xs font-medium transition-all"
                    >
                        Detalle
                    </button>
                    <button
                        @click.stop="habitoPago(row)"
                        class="h-7 px-3 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-medium transition-all whitespace-nowrap"
                    >
                        Hábito de pago
                    </button>
                </div>
            </template>
        </DataTable>
    </div>
</template>

<script setup>
// -- Componentes ----------------------------------------------
import FormCheckbox from '@/components/form/FormCheckbox.vue'
import DataTable from '@/components/table/DataTable.vue'
import FormInput from '@/components/form/FormInput.vue'

import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// -- Columnas ----------------------------------------------
const columns = [
    {
        key: 'numCredito',
        label: 'Núm. Crédito',
        sortable: true,
        align: 'center',
    },
    { key: 'cliente', label: 'Cliente', sortable: false },
    { key: 'ultReporte', label: 'Últ. Reporte', sortable: false },
    { key: 'fechaNotificado', label: 'Fecha notificado', sortable: false },
    { key: 'placa', label: 'Placa', sortable: false },
    { key: 'estadoCliente', label: 'Estado de cliente', sortable: false },
    {
        key: 'fechaAcuerdoPago',
        label: 'Fecha acuerdo de pago',
        sortable: false,
    },
    { key: 'fechaCredito', label: 'Fecha crédito', sortable: false },
    { key: 'vencimiento', label: 'Vencimiento', sortable: false },
    { key: 'plazo', label: 'Plazo', sortable: false },
    {
        key: 'valorCompra',
        label: 'Valor Compra',
        sortable: false,
        align: 'right',
    },
    {
        key: 'valorCredito',
        label: 'Valor Crédito',
        sortable: false,
        align: 'right',
    },
    {
        key: 'totalAbonado',
        label: 'Total abonado',
        sortable: false,
        align: 'right',
    },
    {
        key: 'saldoMora',
        label: 'Saldo en mora',
        sortable: false,
        align: 'right',
    },
    {
        key: 'intMoratorio',
        label: 'Int. moratorio',
        sortable: false,
        align: 'right',
    },
    {
        key: 'gasCobranza',
        label: 'Gastos cobranza',
        sortable: false,
        align: 'right',
    },
    {
        key: 'valorPago',
        label: 'Valor a pagar',
        sortable: false,
        align: 'right',
    },
    {
        key: 'numCuotaPago',
        label: 'Num. cuota a pagar',
        sortable: false,
        align: 'right',
    },
    {
        key: 'fechaFinalizado',
        label: 'Fecha finalizado',
        sortable: false,
        align: 'right',
    },
    {
        key: 'estadoCredito',
        label: 'Estado crédito',
        sortable: false,
        align: 'right',
    },
    {
        key: 'numCuotasPagadas',
        label: 'Num. cuotas pagadas',
        sortable: false,
        align: 'right',
    },
    {
        key: 'numDiasMora',
        label: 'Num. días en mora',
        sortable: false,
        align: 'right',
    },
    {
        key: 'aliado',
        label: 'Aliado',
        sortable: false,
        align: 'center',
        truncate: true,
    },
    { key: 'acciones', label: '', sortable: false },
]

// ── Estado ─────────────────────────────────────────────────────────────────
const creditos = ref([])
const loading = ref(false)
const loadingInforme = ref(null)
const search = ref('')
const selected = ref([])
const busquedaAvanzada = ref(true)
let searchTimeout = null

const pagination = reactive({ currentPage: 1, perPage: 10, total: 0 })
const sort = reactive({ key: 'numCredito', dir: 'desc' })

// ── Filtros ────────────────────────────────────────────────────────────────
const filters = reactive({
    estadoCredito: '',
    mesesPagados: '',
    mesesPagadosHasta: '',
    mesesPorRango: false,
    notificacion: '',
    reporte: '',
    aliado: '',
    mesCorte: '',
    estadoCliente: '',
    cuotasPagadas: '',
    cuotasPagadasHasta: '',
    cuotasPorRango: false,
    numeroDias: '',
    numeroDiasHasta: '',
    diasPorRango: false,
    vencimientoCuota: '',
    vencimientoCuotaHasta: '',
    vencimientoPorRango: false,
    tipoInforme: 'cobranza',
})

// ── Opciones ───────────────────────────────────────────────────────────────
const estadoCreditoOpts = [
    { value: 'vigente', label: 'Vigente' },
    { value: 'mora', label: 'En mora' },
    { value: 'finalizado', label: 'Finalizado' },
    { value: 'anulado', label: 'Anulado' },
]

const notificacionOpts = [
    { value: 'enviado', label: 'Enviado' },
    { value: 'pendiente', label: 'Pendiente' },
    { value: 'sin_envio', label: 'Sin envío' },
]

const reporteOpts = [
    { value: 'cobranza', label: 'Cobranza' },
    { value: 'datacredito', label: 'Datacrédito' },
    { value: 'cifin', label: 'CIFIN' },
]

const aliadoOpts = [
    { value: 'impulsa', label: 'IMPULSA CORP SAS / CREDITRANSITO' },
    { value: 'cda_moto', label: 'CDA MOTOCENTER RUTA 45A SAS' },
    { value: 'cda_prad', label: 'CDA LA PRADERA' },
]

const estadoClienteOpts = [
    { value: 'al_dia', label: 'Al día' },
    { value: 'mora', label: 'En mora' },
    { value: 'acuerdo', label: 'En acuerdo' },
]

const tiposInforme = [
    { value: 'cobranza', label: 'Informe Cobranza' },
    { value: 'datacredito', label: 'Informe DatosCrédito' },
    { value: 'cifin', label: 'Informe CIFIN' },
]

// ── Selección ──────────────────────────────────────────────────────────────
const allSelected = computed(
    () =>
        creditos.value.length > 0 &&
        creditos.value.every(c => selected.value.includes(c.id))
)

function onToggleAll(checked) {
    selected.value = checked ? creditos.value.map(c => c.id) : []
}

function onToggleRow(id) {
    const idx = selected.value.indexOf(id)
    idx === -1 ? selected.value.push(id) : selected.value.splice(idx, 1)
}

// ── Helpers ────────────────────────────────────────────────────────────────
const labelClass = 'text-xs font-medium text-gray-400 uppercase tracking-wide'
const inputClass =
    'w-full h-9 px-3 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600 outline-none focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10 transition-all placeholder:text-gray-300'

function formatCurrency(value) {
    if (value == null) return '—'
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0,
    }).format(value)
}

function estadoClienteBadge(estado) {
    const s = String(estado).toLowerCase()
    if (s.includes('mora')) return 'bg-red-50 text-red-600'
    if (s.includes('día') || s.includes('dia'))
        return 'bg-emerald-50 text-emerald-700'
    if (s.includes('acuerdo')) return 'bg-blue-50 text-blue-600'
    return 'bg-gray-100 text-gray-500'
}

function authHeaders() {
    return {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    }
}

function buildParams() {
    return new URLSearchParams({
        page: pagination.currentPage,
        per_page: pagination.perPage,
        sort_key: sort.key,
        sort_dir: sort.dir,
        search: search.value,
        ...(filters.estadoCredito && { estado_credito: filters.estadoCredito }),
        ...(filters.mesesPagados && { meses_pagados: filters.mesesPagados }),
        ...(filters.mesesPorRango &&
            filters.mesesPagadosHasta && {
                meses_pagados_hasta: filters.mesesPagadosHasta,
            }),
        ...(filters.notificacion && { notificacion: filters.notificacion }),
        ...(filters.reporte && { reporte: filters.reporte }),
        ...(filters.aliado && { aliado: filters.aliado }),
        ...(filters.mesCorte && { mes_corte: filters.mesCorte }),
        ...(filters.estadoCliente && { estado_cliente: filters.estadoCliente }),
        ...(filters.cuotasPagadas && { cuotas_pagadas: filters.cuotasPagadas }),
        ...(filters.cuotasPorRango &&
            filters.cuotasPagadasHasta && {
                cuotas_pagadas_hasta: filters.cuotasPagadasHasta,
            }),
        ...(filters.numeroDias && { numero_dias: filters.numeroDias }),
        ...(filters.diasPorRango &&
            filters.numeroDiasHasta && {
                numero_dias_hasta: filters.numeroDiasHasta,
            }),
        ...(filters.vencimientoCuota && {
            vencimiento_cuota: filters.vencimientoCuota,
        }),
        ...(filters.vencimientoPorRango &&
            filters.vencimientoCuotaHasta && {
                vencimiento_cuota_hasta: filters.vencimientoCuotaHasta,
            }),
    })
}

// ── Backend ────────────────────────────────────────────────────────────────
async function fetchCreditos() {
    loading.value = true
    try {
        const response = await fetch(
            `/api/cobranza/vencimientos?${buildParams()}`,
            {
                headers: authHeaders(),
            }
        )
        if (!response.ok) throw new Error()
        const data = await response.json()
        creditos.value = data.data
        pagination.total = data.meta.total
        pagination.currentPage = data.meta.current_page
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
}

async function descargarArchivo(url, nombre) {
    const response = await fetch(url, { headers: authHeaders() })
    if (!response.ok) throw new Error()
    const blob = await response.blob()
    const a = document.createElement('a')
    a.href = URL.createObjectURL(blob)
    a.download = nombre
    a.click()
    URL.revokeObjectURL(a.href)
}

async function descargarInforme() {
    loadingInforme.value = 'informe'
    try {
        await descargarArchivo(
            `/api/cobranza/vencimientos/informe?${buildParams()}&tipo=${filters.tipoInforme}`,
            `informe_vencimientos_${filters.tipoInforme}.xlsx`
        )
    } catch (err) {
        console.error(err)
    } finally {
        loadingInforme.value = null
    }
}

async function descargarHabitoPago() {
    loadingInforme.value = 'habito'
    try {
        await descargarArchivo(
            `/api/cobranza/vencimientos/habito-pago?${buildParams()}`,
            'informe_habito_pago.xlsx'
        )
    } catch (err) {
        console.error(err)
    } finally {
        loadingInforme.value = null
    }
}

// ── Handlers DataTable ─────────────────────────────────────────────────────
function onPageChange(page) {
    pagination.currentPage = page
    fetchCreditos()
}
function onPerPageChange(val) {
    pagination.perPage = val
    pagination.currentPage = 1
    fetchCreditos()
}
function onSort({ key, dir }) {
    sort.key = key
    sort.dir = dir
    pagination.currentPage = 1
    fetchCreditos()
}
function onSearch(val) {
    search.value = val
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        pagination.currentPage = 1
        fetchCreditos()
    }, 400)
}

// ── Acciones de fila ───────────────────────────────────────────────────────
function verDetalle(row) {
    router.push(`/dashboard/creditos/${row.id}`)
}
function habitoPago(row) {
    console.log('Hábito de pago:', row.id)
}

onMounted(fetchCreditos)
</script>

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease,
        max-height 0.3s ease;
    overflow: hidden;
    max-height: 300px;
}
.slide-enter-from,
.slide-leave-to {
    opacity: 0;
    transform: translateY(-6px);
    max-height: 0;
}
</style>
