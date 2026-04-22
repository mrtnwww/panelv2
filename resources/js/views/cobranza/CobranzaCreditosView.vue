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
                <FormInput
                    label="Estado de crédito"
                    type="select"
                    v-model="filters.estadoCredito"
                    :options="estadoCreditoOpts"
                    placeholder="Seleccionar estado de crédito"
                />

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
                        <FormInput
                            v-model="filters.mesesPagados"
                            type="number"
                            placeholder="Digite el número de meses"
                        />
                        <FormInput
                            v-if="filters.mesesPorRango"
                            v-model="filters.mesesPagadosHasta"
                            type="number"
                            placeholder="Hasta"
                        />
                    </div>
                </div>

                <!-- Notificación -->
                <FormInput
                    label="Notificación"
                    type="select"
                    v-model="filters.notificacion"
                    :options="notificacionOpts"
                    placeholder="Seleccionar notificación"
                />
            </div>

            <!-- Fila 2 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Reporte -->
                <FormInput
                    label="Reporte"
                    type="select"
                    v-model="filters.reporte"
                    :options="reporteOpts"
                    placeholder="Seleccionar reporte"
                    :searchable="true"
                />

                <!-- Aliado -->
                <FormSelectAsync
                    label="Aliado"
                    v-model="filters.aliado"
                    :fetch-options="opcionesStore.fetchEmpresas"
                    placeholder="Seleccione un aliado"
                />

                <!-- Mes de corte -->
                <FormInput
                    label="Mes de corte"
                    type="month"
                    v-model="filters.mesCorte"
                />
            </div>

            <!-- Fila 3 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Estado de cliente -->
                <FormInput
                    label="Estado de cliente"
                    type="select"
                    v-model="filters.estadoCliente"
                    :options="estadoClienteOpts"
                    placeholder="Seleccione un estado"
                    :searchable="true"
                />
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
                    <span class="text-sm font-medium text-gray-600">
                        Búsqueda avanzada
                    </span>
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
                                <FormInput
                                    v-model="filters.cuotasPagadas"
                                    type="number"
                                    placeholder="Digite el número de cuotas"
                                />
                                <FormInput
                                    v-if="filters.cuotasPorRango"
                                    v-model="filters.cuotasPagadasHasta"
                                    type="number"
                                    placeholder="Hasta"
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
                                <FormInput
                                    v-model="filters.numeroDias"
                                    type="number"
                                    placeholder="Digite el número de días"
                                />
                                <FormInput
                                    v-if="filters.diasPorRango"
                                    v-model="filters.numeroDiasHasta"
                                    type="number"
                                    placeholder="Hasta"
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
                                <FormInput
                                    v-model="filters.vencimientoCuota"
                                    type="date"
                                />
                                <FormInput
                                    v-if="filters.vencimientoPorRango"
                                    v-model="filters.vencimientoCuotaHasta"
                                    type="date"
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
                <FormRadioGroup
                    v-model="filters.tipoInforme"
                    :options="tiposInforme"
                    :vertical="true"
                />
            </div>

            <!-- Botones descarga -->
            <div
                class="flex flex-col lg:flex-row lg:items-center justify-between gap-4"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        @click="descargarInforme"
                        :disabled="loadingInforme === 'informe'"
                        class="btn btn-main w-full sm:w-auto"
                    >
                        <svg
                            v-if="loadingInforme === 'informe'"
                            class="animate-spin w-3.5 h-3.5 mr-2"
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
                        class="btn btn-primary w-full sm:w-auto"
                    >
                        <svg
                            v-if="loadingInforme === 'habito'"
                            class="animate-spin w-3.5 h-3.5 mr-2"
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

                    <div
                        v-if="selected.length"
                        class="flex flex-wrap items-center gap-2 mt-2 lg:mt-0 lg:ml-4 border-l-0 lg:border-l lg:pl-4 border-gray-200"
                    >
                        <button
                            @click="abrirModalCrear"
                            class="btn btn-main flex-1"
                        >
                            Crear tarea
                        </button>
                        <button class="btn btn-warning flex-1">
                            Notificar
                        </button>
                        <button class="btn btn-secondary flex-1">
                            Negociación
                        </button>
                        <button class="btn btn-danger flex-1">Reportar</button>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:justify-end">
                    <button
                        @click="resetFilters"
                        class="btn flex-1 sm:flex-none border border-gray-200 text-sm text-gray-500 hover:bg-gray-50 hover:border-gray-300"
                    >
                        Limpiar
                    </button>
                    <button
                        @click="fetchCreditos"
                        class="btn btn-main flex-1 sm:flex-none"
                    >
                        Aplicar filtros
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
                <UpdateMoraButton :onSuccess="fetchCreditos" />
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

            <!-- Columnas de moneda -->
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
            <template #cell-totalAbonado="{ value }">
                <span class="tabular-nums text-gray-600">{{
                    formatCurrency(value)
                }}</span>
            </template>
            <template #cell-saldoMora="{ value }">
                <span class="tabular-nums text-gray-600">{{
                    formatCurrency(value)
                }}</span>
            </template>
            <template #cell-intMoratorio="{ value }">
                <span class="tabular-nums text-gray-600">{{
                    formatCurrency(value)
                }}</span>
            </template>
            <template #cell-gasCobranza="{ value }">
                <span class="tabular-nums text-gray-600">{{
                    formatCurrency(value)
                }}</span>
            </template>
            <template #cell-valorPago="{ value }">
                <span class="tabular-nums text-gray-600">{{
                    formatCurrency(value)
                }}</span>
            </template>

            <!-- Acciones -->
            <template #cell-acciones="{ row }">
                <div class="flex items-center gap-1.5">
                    <button
                        @click.stop="verEstadoCredito(null, row.id)"
                        class="btn btn-main"
                    >
                        Detalle
                    </button>
                    <button
                        @click.stop="habitoPago(row)"
                        class="btn btn-primary"
                    >
                        Hábito de pago
                    </button>
                </div>
            </template>
        </DataTable>

        <transition name="modal">
            <EstadoCreditoModal
                v-model="modalOpen"
                :loading="loadingCredito"
                :credito="credito"
                @ver-historico="verHistorico"
                @liquidar="liquidarCredito"
                @ver-plan-pagos="verPlanPagos"
                @descargar-paz-salvo="descargarPazSalvo"
                @imprimir="imprimirCredito"
                @imprimir-abono="imprimirAbono"
            />
        </transition>

        <!-- ── Modal hábito de pago ── -->
        <AppModal
            v-model="modalHabito.open"
            :title="`Hábito de pago - ${nombreClienteHabito}`"
            size="xl"
            :show-footer="false"
            :close-on-overlay="true"
            @update:modelValue="cerrarModalHabito"
        >
            <TableGrid :items="habitoPagoCredito" :columns="cols" />
        </AppModal>

        <!-- ── Modal crear tarea ── -->
        <CrearTareaModal
            v-model="modal.open"
            :tipoTarea="tipoTareaOpts"
            :prioridad="prioridadOpts"
            :usuarios="usuariosOpts"
            :loading="modal.loading"
            :error="modal.error"
            :modal="modal.form"
            @confirm="crearTarea"
        />
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'

import dayjs from 'dayjs'

// -- Componentes -----------------------------------------------------
import EstadoCreditoModal from '@/components/modals/EstadoCreditoModal.vue'
import CrearTareaModal from '@/components/modals/CrearTareaModal.vue'
import FormSelectAsync from '@/components/form/FormSelectAsync.vue'
import FormRadioGroup from '@/components/form/FormRadioGroup.vue'
import FormCheckbox from '@/components/form/FormCheckbox.vue'
import FormInput from '@/components/form/FormInput.vue'
import TableGrid from '@/components/TableGrid.vue'
import AppModal from '@/components/AppModal.vue'

import UpdateMoraButton from '@/components/table/UpdateMoraButton.vue'
import DataTable from '@/components/table/DataTable.vue'

// -- Loader ---------------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

// -- Toaster -----------------------------------------------
import { notify } from '@/composables/useNotify'

// -- Composables ----------------------------------------------------
import { useEstadoCredito } from '@/composables/useEstadoCredito'
// -- DataTable -------------------------------------------------------
import { useDataTable } from '@/composables/useDataTable'

import { formatCurrency, formatDateYmd } from '@/utils/format'
import api from '@/services/api'

// -- Store -----------------------------------------------------------
import { useOpcionesStore } from '@/stores/opciones'

// -- Columnas ---------------------------------------------------------
const columns = [
    {
        key: 'numCredito',
        label: 'Núm. Crédito',
        sortable: false,
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

// -- Modal hábito de pago -------------------------------------------------
const cols = [
    { key: 'fecha_cuota', label: 'Fecha de cuota' },
    {
        key: 'fecha_pago',
        label: 'Fecha de pago',
    },
    {
        key: 'estado_pago',
        label: 'Estado de pago',
    },
    {
        key: 'dias_mora',
        label: 'Días de mora',
        headerClass: 'text-center',
        cellClass: 'text-center',
    },
]

const habitoPagoCredito = ref([])

// -- Estado ----------------------------------------------------------------
const creditos = ref([])
const loading = ref(false)
const loadingInforme = ref(null)
const selected = ref([])
const busquedaAvanzada = ref(true)
const nombreClienteHabito = ref('')

const opcionesStore = useOpcionesStore()

// Definir las fechas de corte (inicial y final) para el estado de créditos
let fechaCorte = {
    desde: false,
    hasta: dayjs().format('DD/MM/YYYY HH:mm:ss'),
}

let NumCuotasValidarEn = 'cuotas_canceladas'

// -- Filtros ----------------------------------------------------------
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

// -- Opciones ------------------------------------------------
const reporteOpts = ref([])
const usuariosOpts = ref([])

const estadoCreditoOpts = [
    { value: '2', label: 'Al día' },
    { value: '1', label: 'En mora' },
    { value: '4', label: 'Finalizado' },
]
const notificacionOpts = [
    { value: '1', label: 'Si' },
    { value: '2', label: 'No' },
]

const estadoClienteOpts = [
    { value: 'al_dia', label: 'Cliente al día' },
    { value: 'acuerdo_pago', label: 'Acuerdo de pago' },
    { value: 'intension_pago', label: 'Intensión de pago' },
    { value: 'acuerdo_incumplido', label: 'Acuerdo incumplido' },
    { value: 'dificultad_pago', label: 'Dificultad de pago' },
    { value: 'renuente', label: 'Renuente' },
    { value: 'no_contesta', label: 'No contesta/Mensaje con refererencias' },
    { value: 'ilocalizado', label: 'Ilocalizado' },
    { value: 'paz_y_salvo', label: 'A paz y salvo' },
]

const tiposInforme = [
    { value: 'cobranza', label: 'Informe Cobranza' },
    { value: 'datacredito', label: 'Informe DataCrédito' },
    { value: 'cifin', label: 'Informe CIFIN' },
]

const tipoTareaOpts = [
    { value: '2', label: 'Llamada' },
    { value: '3', label: 'Correo' },
    { value: '1', label: 'Otro' },
]

const prioridadOpts = [
    { value: '1', label: 'Alta' },
    { value: '2', label: 'Media' },
    { value: '3', label: 'Baja' },
]

// -- Selección ---------------------------------------------------------
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
const labelClass = 'text-xs font-medium text-gray-500 uppercase tracking-wide'

function estadoClienteBadge(estado) {
    const s = String(estado).toLowerCase().trim()

    const colores = {
        ok: 'bg-emerald-50 text-emerald-700',
        warn: 'bg-blue-50 text-blue-600',
        bad: 'bg-red-50 text-red-600',
    }

    const estados = {
        'cliente al día': colores.ok,
        'a paz y salvo': colores.ok,

        'acuerdo de pago': colores.warn,
        'intensión de pago': colores.warn,
        'no contesta/mensaje con refererencias': colores.warn,
        ilocalizado: colores.warn,

        'acuerdo incumplido': colores.bad,
        'dificultad de pago': colores.bad,
        renuente: colores.bad,
    }

    return estados[s] || 'bg-gray-100 text-gray-500'
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

async function resetFilters() {
    ;((filters.estadoCredito = ''),
        (filters.mesesPagados = ''),
        (filters.mesesPagadosHasta = ''),
        (filters.mesesPorRango = false))
    ;((filters.notificacion = ''),
        (filters.reporte = ''),
        (filters.aliado = ''),
        (filters.mesCorte = ''),
        (filters.estadoCliente = ''),
        (filters.cuotasPagadas = ''),
        (filters.cuotasPagadasHasta = ''),
        (filters.cuotasPorRango = false))
    ;((filters.numeroDias = ''),
        (filters.numeroDiasHasta = ''),
        (filters.diasPorRango = false))
    ;((filters.vencimientoCuota = ''),
        (filters.vencimientoCuotaHasta = ''),
        (filters.vencimientoPorRango = false))
    filters.tipoInforme = 'cobranza'
    pagination.currentPage = 1
    await fetchCreditos()
}

// -- Backend --------------------------------------------------------------
async function fetchCreditos() {
    loading.value = true
    try {
        const params = buildParams()

        const { data } = await api.get('/api/creditos/creditsCobranza', {
            params,
        })

        const { data: creditosData, total, current_page } = data.creditos

        // Lista de créditos
        transformCreditos(creditosData)

        // Datos de paginación
        pagination.currentPage = current_page
        pagination.total = total
    } catch (err) {
        notify.error(
            'Ocurrió un error al obtener la información de los créditos.'
        )
    } finally {
        loading.value = false
    }
}

async function fetchReportesTipos() {
    const reportesCentralesTipos = localStorage.getItem(
        'reportesCentralesTipos'
    )

    if (reportesCentralesTipos) {
        reporteOpts.value = JSON.parse(reportesCentralesTipos).map(r => ({
            value: r.id,
            label: r.tipo_reporte,
        }))
        return
    }

    try {
        const { data } = await axios.get('/api/reportes')

        localStorage.setItem(
            'reportesCentralesTipos',
            JSON.stringify(data.reportes)
        )
    } catch (err) {
        console.error(err)
    }
}

async function fetchUsuarios() {
    try {
        const { data } = await api.get('/api/usuarios/listMyUsers')

        // Opciones formateadas para FormInput type="select"
        usuariosOpts.value = data.usuarios.data.map(c => ({
            value: c.idUsuario,
            label: c.nombre,
        }))
    } catch (err) {
        console.error(err)
    }
}

// -- Modal hábito de pago -------------------------------------------
const modalHabito = reactive({
    open: false,
})

function abrirModalHabito() {
    modalHabito.open = true
}

function cerrarModalHabito() {
    modalHabito.open = false
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

async function crearTarea() {
    const camposRequeridos = [
        modal.form.titulo,
        modal.form.tipoTarea,
        modal.form.prioridad,
        modal.form.usuarioAsignado,
        modal.form.vencimiento,
        modal.form.nota,
    ]

    if (camposRequeridos.some(campo => !campo)) {
        modal.error =
            'Es necesario completar todos los campos antes de continuar.'
        return
    }
    modal.loading = true
    modal.error = ''

    const payload = {
        ...modal.form,
        creditosId: JSON.stringify(selected.value),
    }

    try {
        const { data } = await api.post('/api/tareas/createTarea', {
            tarea: payload,
        })

        await fetchCreditos()

        notify.success(data.message || 'Tareas creadas exitósamente.')

        cerrarModal()
        selected.value = []
    } catch (err) {
        notify.error(
            err.response?.data?.message ||
                'Ocurrió un error al crear las tareas.'
        )
    } finally {
        modal.loading = false
    }
}

// -- Modal crear tarea ---------------------------------------------------
const modal = reactive({
    open: false,
    loading: false,
    error: '',
    form: {
        titulo: '',
        tipoTarea: '',
        prioridad: '',
        usuarioAsignado: '',
        vencimiento: '',
        nota: '',
    },
})

function abrirModalCrear() {
    Object.assign(modal.form, {
        titulo: '',
        prioridad: '',
        usuarioAsignado: '',
        tipoTarea: '',
        vencimiento: '',
        nota: '',
    })
    modal.error = ''
    modal.open = true
}

function cerrarModal() {
    modal.open = false
}

// -- Acciones de fila ----------------------------------------
function habitoPago(row) {
    const proyecciones = row.proyecciones
    const abonos = row.abonos

    habitoPagoCredito.value = proyecciones.map((proy, index) => {
        const fecha = new Date(proy.fecha)

        // Fecha de cuota
        const fechaCuota = `${index + 1} - ${fecha.toISOString().split('T')[0]}`

        const abonoPago = abonos.find(
            abono =>
                abono.credito_proyeccion_cuotas_pagadas &&
                JSON.parse(abono.credito_proyeccion_cuotas_pagadas).includes(
                    proy.id
                )
        )

        // Fecha de pago
        const fechaPago = abonoPago ? abonoPago.created_at : null

        let estadoPago = 'Pendiente'
        let fechaForm = '- -'
        let diasMora = '- -'

        // si al cuota tiene fecha de pago
        if (fechaPago) {
            const pago = new Date(fechaPago)

            // limpiar las horas de las fechas a calcular
            pago.setHours(0, 0, 0, 0)
            fecha.setHours(0, 0, 0, 0)

            fechaForm = new Date(fechaPago).toISOString().split('T')[0]

            if (pago <= fecha) {
                estadoPago = 'Al día'
            } else {
                estadoPago = 'En mora'
                const diffMs = pago - fecha
                diasMora = Math.ceil(diffMs / (1000 * 60 * 60 * 24)) // milisegundos a dias y redondear
            }
        }

        return {
            fecha_cuota: fechaCuota,
            fecha_pago: fechaForm, // fecha formateada en la que el cliente realiza el pago
            estado_pago: estadoPago,
            dias_mora: diasMora,
        }
    })

    nombreClienteHabito.value = row.cliente
    abrirModalHabito()
}

function transformCreditos(data) {
    const infoCreditos = creditosAFechaCorte(data)

    creditos.value = infoCreditos.map(cr => ({
        id: cr.id,
        numCredito: cr.id,
        cliente: cr.cliente,
        ultReporte: cr.ult_reporte.concatted,
        fechaNotificado: cr.fecha_notificado
            ? formatDateYmd(cr.fecha_notificado)
            : '',
        estadoCliente: cr.estado_cliente,
        fechaAcuerdoPago: cr.fecha_acuerdo,
        fechaCredito: formatDateYmd(cr.fecha_credito),
        vencimiento: cr.vencimiento,
        plazo: cr.plazo,
        valorCompra: cr.valor_compra,
        valorCredito: cr.valor_credito,
        totalAbonado: cr.abono,
        saldoMora: cr.valor_saldo_mora,
        intMoratorio: cr.valor_int_mora,
        gasCobranza: cr.valor_gastos_cobranza,
        valorPago: cr.valor_min_pago,
        numCuotaPago: cr.cuota_pago == 0 ? '- -' : cr.cuota_pago,
        fechaFinalizado: cr.estaFinalizadoHoy
            ? cr.fecha_cierre
                ? formatDateYmd(cr.fecha_cierre)
                : ''
            : '',
        estadoCredito: cr.estaFinalizadoHoy
            ? 'Finalizado'
            : cr.en_mora
              ? 'En mora'
              : 'Normal',
        numCuotasPagadas: cr[NumCuotasValidarEn],
        numDiasMora: cr.dias_mora,
        aliado: cr.razon_social,
        proyecciones: cr.proyecciones,
        abonos: cr.abonos,
    }))
}

function creditosAFechaCorte(creditosSTR) {
    let creditosStruct = []
    creditosSTR.map(credito => {
        const createdAtCredito = formatDateYmd(credito.created_at)
        const validationFCorteDesde = fechaCorte.desde
            ? createdAtCredito > formatDateYmd(fechaCorte.desde)
            : true

        /**
         * Se realizarán los cálculos necesarios siempre y cuando la fecha de
         * creación del crédito sea menor o igual a la fecha de corte
         */
        if (
            validationFCorteDesde &&
            createdAtCredito < formatDateYmd(fechaCorte.hasta)
        ) {
            /**
             * Almacenar las proyecciones a la fecha de corte, las cuales corresponden
             * a aquellas cuya fecha es menor o igual a la fecha de corte.
             */
            const proyeccionesFechaCorte = credito.proyecciones.filter(
                proy => formatDateYmd(proy.fecha) < fechaCorte.hasta
            )

            // numero de cuota a pagar
            credito.cuotaPagar =
                credito.proyecciones.findIndex(cuota => cuota.pagado == 0) + 1

            /**
             * Almacenar los abonos a la fecha de corte, los cuales corresponden
             * a aquellos cuya fecha de creación es menor o igual a la fecha de corte.
             */
            const abonosFechaCorte = credito.abonos.filter(
                abono => formatDateYmd(abono.created_at) < fechaCorte.hasta
            )

            /**
             * Definir el abono esperado, que corresponde al abono que el cliente debió haber
             * realizado a la fecha de corte.
             */

            let abonoEsperado = 0
            if (
                proyeccionesFechaCorte.length > 0 &&
                proyeccionesFechaCorte[0].valor_cuota != null
            ) {
                abonoEsperado = proyeccionesFechaCorte.reduce(
                    (acumulador, proyeccion) =>
                        acumulador + proyeccion.valor_cuota,
                    0
                )
            } else {
                abonoEsperado =
                    proyeccionesFechaCorte.length * credito.val_cuotas
            }

            /**
             * Definir el abono realizado, que corresponde al abono real realizado por el
             * cliente => sumatoria de todos los abonos realizados hasta la fecha de corte.
             */
            const abonoRealizado =
                (credito.valorCondonadoCredito ?? 0) +
                abonosFechaCorte.reduce(
                    (acumulador, abono) => acumulador + abono.valor,
                    0
                )

            /**
             * Definir el último abono realizado por el cliente, el cual corresponde al último ítem
             * del listado de abonos a la fecha de corte.
             */
            const ultAbono = abonosFechaCorte.length
                ? formatDateYmd(
                      abonosFechaCorte[abonosFechaCorte.length - 1].created_at
                  )
                : ''

            /**
             * Definir la fecha de vencimiento del crédito, la cual corresponde a la fecha
             * de la última cuota a pagar.
             */
            const vencimiento = formatDateYmd(
                credito.proyecciones[credito.proyecciones.length - 1].fecha
            )

            /**
             * El saldo de la deuda corresponde al valor del crédito menos el abono realizado
             * a la fecha de corte.
             */
            let valorSaldoDeuda = credito.valor_credito - abonoRealizado

            /**
             * Determinar si el crédito fue pagado antes de la fecha de corte.
             */
            credito.pagadoFechaCorte =
                credito.fecha_cierre != '' &&
                credito.fecha_cierre != null &&
                formatDateYmd(credito.fecha_cierre) <= fechaCorte.hasta
                    ? true
                    : false

            /**
             * Recorrer las proyecciones a la fecha de corte para definir
             * los ítems morosos (dias en mora, cuotas en mora, etc.).
             */
            let resultMora = {
                enMora: false,
                cuotasMora: 0,
                diasMora: 0,
                valorSaldoMora: 0,
            }
            let cuotasCanceladas = 0
            let iteradorNoPagado = 0
            let restaAbonado = abonoRealizado

            proyeccionesFechaCorte.map(proyFCorte => {
                proyFCorte.pagado = 1

                /**
                 * El recorrido consiste en restar el valor de las cuotas por cada cuota
                 * de la proyección al valor abonado por el cliente. En el momento en que
                 * el RESTANTE DEL ABONADO sea menor al VALOR DE LAS CUOTAS la cuota estará
                 * como "No pagada" y por ende sumará cuotas en mora y días en mora.
                 */

                if (
                    restaAbonado <
                    (proyFCorte.valor_cuota ?? credito.val_cuotas)
                ) {
                    resultMora.cuotasMora++
                    resultMora.enMora = true
                    proyFCorte.pagado = 0

                    /**
                     * Si es la primera cuota no pagada, se calculará los días en mora a la
                     * fecha de corte.
                     */
                    if (iteradorNoPagado == 0) {
                        resultMora.diasMora = proyFCorte.diasMora
                    }

                    iteradorNoPagado++
                }
                restaAbonado -= proyFCorte.valor_cuota ?? credito.val_cuotas
            })

            cuotasCanceladas = proyeccionesFechaCorte.filter(
                subItem => subItem.pagado == 1
            ).length

            resultMora.cuotasMoraMensual =
                credito.periocidad == 1
                    ? resultMora.cuotasMora
                    : Math.trunc(resultMora.cuotasMora / 2)

            /**
             * Calcular el saldo moroso, el cual corresponde al abono esperado menos
             * el abono realizado por el cliente.
             */
            let sumatoriaIntMora = 0
            let sumatoriaGasCobranza = 0

            // Calculo de gastos de cobranza e intereses moratorios
            if (proyeccionesFechaCorte.length > 0) {
                sumatoriaIntMora = proyeccionesFechaCorte.reduce(
                    (acumulador, proyeccion) => {
                        return proyeccion.pagado == 0
                            ? acumulador +
                                  Math.round(proyeccion.intereses_moratorios)
                            : acumulador
                    },
                    0
                )
                sumatoriaGasCobranza = proyeccionesFechaCorte.reduce(
                    (acumulador, proyeccion) => {
                        return proyeccion.pagado == 0
                            ? acumulador +
                                  Math.round(proyeccion.gastos_cobranza)
                            : acumulador
                    },
                    0
                )
            }

            let sumatoriaMora = credito.valorMinPago ?? 0
            let sumatoriaMinPago = credito.valorMinPago ?? 0

            /**
             * Si el número de días en mora es menor a cero no estará en mora.
             */
            if (resultMora.diasMora < 1) {
                sumatoriaMora = 0
                resultMora.enMora = false
            }

            /**
             * Calcular la fecha límite de pago, la cual será la fecha más próxima
             * dentro de las proyecciones a la fecha corte, o tomará el
             *
             * Toma la última cuota de las proyecciones a la fecha de corte.
             *
             * SINO
             *
             * Toma la última cuota de las proyecciones entre la fecha de corte y
             * el mes inmediatamente despues de la fecha de corte.
             *
             * SINO
             *
             * Toma la última cuota de las proyecciones entre la fecha de corte y
             * dos meses inmediatamente despues de la fecha de corte
             */
            const proyEvalLimPago = {
                fechaCorte: proyeccionesFechaCorte,
                unMesDespues: credito.proyecciones.filter(
                    item =>
                        formatDateYmd(item.fecha) <=
                        dayjs(fechaCorte.hasta)
                            .add(1, 'months')
                            .format('YYYY-MM-DD')
                ),
                dosMesesDespues: credito.proyecciones.filter(
                    item =>
                        formatDateYmd(item.fecha) <=
                        dayjs(fechaCorte.hasta)
                            .add(2, 'months')
                            .format('YYYY-MM-DD')
                ),
            }

            const fechaLimitePago = proyEvalLimPago.fechaCorte.length
                ? formatDateYmd(
                      proyEvalLimPago.fechaCorte[
                          proyEvalLimPago.fechaCorte.length - 1
                      ].fecha
                  )
                : proyEvalLimPago.unMesDespues.length
                  ? formatDateYmd(
                        proyEvalLimPago.unMesDespues[
                            proyEvalLimPago.unMesDespues.length - 1
                        ].fecha
                    )
                  : proyEvalLimPago.dosMesesDespues.length
                    ? formatDateYmd(
                          proyEvalLimPago.dosMesesDespues[
                              proyEvalLimPago.dosMesesDespues.length - 1
                          ].fecha
                      )
                    : ''

            /**
             * Definir la novedad de centrales de riesgo que debe ser definida respecto
             * a las cuotas en mora a la fecha de corte (calculado anteriormente).
             */
            let centralRiesgo = {
                idNovedad: '',
                novedad: '',
            }

            //mirar acá vieja logica

            if (resultMora.cuotasMoraMensual == 0) {
                centralRiesgo.idNovedad = '01'
                centralRiesgo.novedad = 'AL DÍA'
            } else if (resultMora.diasMora >= 30 && resultMora.diasMora < 60) {
                centralRiesgo.idNovedad = '06'
                centralRiesgo.novedad = 'MORA 30'
            } else if (resultMora.diasMora >= 60 && resultMora.diasMora < 90) {
                centralRiesgo.idNovedad = '07'
                centralRiesgo.novedad = 'MORA 60'
            } else if (resultMora.diasMora >= 90 && resultMora.diasMora < 120) {
                centralRiesgo.idNovedad = '08'
                centralRiesgo.novedad = 'MORA 90'
            } else if (resultMora.diasMora >= 120) {
                centralRiesgo.idNovedad = '09'
                centralRiesgo.novedad = 'MORA 120 O MÁS'
            }

            /**
             * Si el saldo de la deuda es 0 o menos que cero (el crédito fue pagado)
             * se asigna el tipo de reporte de centrales correspondiente.
             */
            if (
                valorSaldoDeuda <= 0 ||
                credito.pagadoFechaCorte ||
                credito.estaFinalizadoHoy
            ) {
                resultMora.cuotasMora = 0
                resultMora.cuotasMoraMensual = 0
                resultMora.enMora = false
                resultMora.diasMora = 0
                valorSaldoDeuda = 0
                sumatoriaMora = 0
                sumatoriaIntMora = 0
                sumatoriaGasCobranza = 0
                centralRiesgo.idNovedad = '05'
                centralRiesgo.novedad = 'PAGO VOLUNTARIO O TOTAL'
                cuotasCanceladas =
                    credito.periocidad == 1
                        ? credito.num_cuotas
                        : credito.num_cuotas * 2
            }

            let fechaVencimiento = credito.proyecciones.find(
                item => item.pagado === 0
            )
            fechaVencimiento = fechaVencimiento
                ? formatDateYmd(fechaVencimiento.fecha)
                : ''

            const mapEstadoCliente = {
                al_dia: 'Cliente al día',
                acuerdo_pago: 'Acuerdo de pago',
                intension_pago: 'Intensión de pago',
                acuerdo_incumplido: 'Acuerdo incumplido',
                dificultad_pago: 'Dificultad de pago',
                renuente: 'Renuente',
                no_contesta: 'No contesta/Mensaje con refererencias',
                ilocalizado: 'Ilocalizado',
                paz_y_salvo: 'A paz y salvo',
            }

            const creditoAdd = {
                id: credito.id,
                consecutivo: credito.consecutivo,
                valor_compra: credito.valor_compra,
                valor_credito: credito.valor_credito,
                plazo: credito.periocidad == 1 ? 'Mensual' : 'Quincenal',
                periocidad: credito.periocidad,
                en_mora: resultMora.enMora,
                cliente: credito.cliente.nombre,
                abono: abonoRealizado ? `${abonoRealizado}` : 0,
                vencimiento: vencimiento,
                cuotas_mora: resultMora.cuotasMora,
                dias_mora: resultMora.diasMora,
                fecha_credito: formatDateYmd(credito.created_at),
                num_cuotas: credito.num_cuotas,
                notificado: credito.notificacion.notificado,
                fecha_notificado: credito.notificacion.fecha,
                num_identificacion: credito.cliente.cedula,
                ciudad_correspondencia: credito.cliente.ciudad_nombre,
                direccion_correspondencia: credito.cliente.direccion,
                correo: credito.cliente.email,
                celular: credito.cliente.telefono,
                val_cuota_mensual:
                    credito.periocidad == 1
                        ? credito.val_cuotas
                        : credito.val_cuotas * 2,
                valor_saldo_mora: sumatoriaMora,
                valor_min_pago: sumatoriaMinPago,
                valor_int_mora: sumatoriaIntMora,
                valor_gastos_cobranza: sumatoriaGasCobranza,
                novedad_central_riesgo: centralRiesgo.novedad,
                id_novedad_central_riesgo: centralRiesgo.idNovedad,
                cuotas_canceladas: cuotasCanceladas,
                fecha_pago: ultAbono,
                fecha_limite_pago: fechaLimitePago,
                valor_saldo_deuda: valorSaldoDeuda,
                cuotas_mora_mensual: resultMora.cuotasMoraMensual,
                empresa_id: credito.empresa_id,
                razon_social: credito.razon_social,
                ult_reporte: {
                    fecha: credito.infoUltReporte.fecha,
                    tipo: credito.infoUltReporte.tipo,
                    tipo_id: credito.infoUltReporte.tipo_id,
                    concatted:
                        credito.infoUltReporte.fecha != '' &&
                        credito.infoUltReporte.tipo != ''
                            ? `${formatDateYmd(credito.infoUltReporte.fecha)} (${credito.infoUltReporte.tipo})`
                            : '',
                },
                fecha_cierre: credito.fecha_cierre,
                fecha_vencimiento: fechaVencimiento,
                cuota_pago: credito.cuotaPagar,
                estaFinalizadoHoy: credito.estaFinalizadoHoy,
                estado_cliente: credito?.cliente?.estado_cliente_tarea
                    ? mapEstadoCliente[credito.cliente.estado_cliente_tarea]
                    : '',
                fecha_acuerdo: credito?.cliente?.fecha_fin_acuerdo_pago
                    ? formatDateYmd(credito.cliente.fecha_fin_acuerdo_pago)
                    : '',
                proyecciones: credito.proyecciones,
                abonos: credito.abonos,
            }

            creditosStruct.push(creditoAdd)
        }
    })

    return creditosStruct
}

// -- Modal estado credito --------------------------------------
const {
    modalOpen,
    loadingCredito,
    credito,
    verEstadoCredito,
    verHistorico,
    liquidarCredito,
    verPlanPagos,
    descargarPazSalvo,
    imprimirCredito,
    imprimirAbono,
} = useEstadoCredito()

const {
    search,
    pagination,
    sort,
    onPageChange,
    onPerPageChange,
    onSearch,
    onSort,
} = useDataTable(fetchCreditos, {
    initialSortKey: 'credito.id',
})

onMounted(async () => {
    start()

    try {
        await Promise.all([
            fetchCreditos(),
            fetchReportesTipos(),
            fetchUsuarios(),
        ])
    } finally {
        stop()
    }
})
</script>
