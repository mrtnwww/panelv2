<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">Generar abonos</h1>

        <!-- Panel principal -->
        <div
            class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-5"
        >
            <!-- Acciones masivas -->
            <div
                class="flex flex-col gap-3 pb-5 border-b border-gray-100 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        @click="descargarPlantilla"
                        :disabled="loadingPlantilla"
                        class="h-9 px-4 rounded-lg bg-sky-500 hover:bg-sky-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-medium transition-all flex items-center gap-2"
                    >
                        <svg
                            v-if="loadingPlantilla"
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
                        <i class="fa-solid fa-download"></i>
                        Descargar plantilla
                    </button>

                    <!-- Cargar abonos usa FormInput type="file" -->
                    <FormInput
                        type="file"
                        accept=".xlsx,.csv"
                        button-label="Cargar abonos"
                        size="sm"
                        @change="onFileChange"
                    />

                    <div class="hidden sm:block w-px h-6 bg-gray-200 mx-1" />

                    <button
                        @click="condonacionMasiva"
                        class="h-9 px-4 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] text-white text-sm font-medium transition-all flex items-center gap-2"
                    >
                        <i class="fa-solid fa-plus"></i>
                        Condonación masiva
                    </button>

                    <button
                        @click="fetchData"
                        :disabled="loadingRefresh"
                        class="h-9 w-9 rounded-lg bg-orange-500 hover:bg-orange-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white transition-all flex items-center justify-center shrink-0"
                        title="Actualizar valores"
                    >
                        <i class="fa-solid fa-arrow-rotate-right"></i>
                    </button>
                </div>
            </div>

            <!-- Selección de cliente y crédito -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormInput
                    label="Cliente"
                    type="select"
                    v-model="form.cliente_id"
                    :options="clientes"
                    placeholder="Seleccione el cliente"
                    @update:model-value="onClienteChange"
                />
                <FormInput
                    label="Buscar crédito"
                    type="select"
                    v-model="form.credito_id"
                    :options="creditos"
                    placeholder="Seleccionar crédito"
                    :disabled="!form.cliente_id"
                    @update:model-value="onCreditoChange"
                />
            </div>
        </div>

        <!-- Panel detalle de crédito -->
        <transition name="fade">
            <div
                v-if="creditoInfo"
                class="grid grid-cols-1 xl:grid-cols-[1fr_45%] gap-5 items-start"
            >
                <!-- ── Columna izquierda: Formulario de abono ── -->
                <div
                    class="bg-white rounded-xl border border-gray-200 overflow-hidden"
                >
                    <!-- Cabecera -->
                    <div
                        class="grid grid-cols-2 bg-gray-50 border-b border-gray-200 px-5 py-2.5"
                    >
                        <span
                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                            >Descripción</span
                        >
                        <span
                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                            >Valores</span
                        >
                    </div>

                    <!-- Filas de solo lectura -->
                    <div
                        v-for="row in camposLectura"
                        :key="row.key"
                        class="grid grid-cols-2 border-b border-gray-100 last:border-b-0 px-5 py-2.5"
                    >
                        <span class="text-sm text-gray-500">{{
                            row.label
                        }}</span>
                        <span class="text-sm text-gray-600">{{
                            row.value
                        }}</span>
                    </div>

                    <!-- Número de meses -->
                    <div
                        class="grid grid-cols-2 items-center border-b border-gray-100 px-5 py-2"
                    >
                        <span class="text-sm text-gray-500"
                            >Número de meses</span
                        >
                        <FormInput
                            type="select"
                            v-model="form.num_meses"
                            :options="
                                mesesOpciones.map(m => ({
                                    value: m,
                                    label: String(m),
                                }))
                            "
                            size="sm"
                        />
                    </div>

                    <!-- Periodicidad -->
                    <div
                        class="grid grid-cols-2 items-center border-b border-gray-100 px-5 py-2"
                    >
                        <span class="text-sm text-gray-500"
                            >Periodicidad Cuotas</span
                        >
                        <FormInput
                            type="select"
                            v-model="form.periodicidad"
                            :options="periodicidadOpts"
                            size="sm"
                        />
                    </div>

                    <!-- Filas calculadas -->
                    <div
                        v-for="row in camposCalculados"
                        :key="row.key"
                        class="grid grid-cols-2 border-b border-gray-100 px-5 py-2.5"
                    >
                        <span class="text-sm text-gray-500">{{
                            row.label
                        }}</span>
                        <span class="text-sm text-gray-600">{{
                            row.value
                        }}</span>
                    </div>

                    <!-- Valor a pagar -->
                    <div
                        class="grid grid-cols-2 items-center border-b border-gray-100 px-5 py-2"
                    >
                        <span class="text-sm text-gray-500">Valor a pagar</span>
                        <FormInput
                            type="number"
                            v-model="form.valor_pagar"
                            placeholder="0"
                            size="sm"
                        />
                    </div>

                    <!-- Abono a crédito -->
                    <div
                        class="grid grid-cols-2 items-center border-b border-gray-100 px-5 py-2"
                    >
                        <span class="text-sm text-gray-500"
                            >Abono a crédito</span
                        >
                        <FormInput
                            type="number"
                            v-model="form.abono_credito"
                            placeholder="0"
                            size="sm"
                        />
                    </div>

                    <!-- Gastos de cobranza -->
                    <div
                        class="grid grid-cols-2 border-b border-gray-100 px-5 py-2.5"
                    >
                        <span class="text-sm text-gray-500"
                            >Gastos de cobranza</span
                        >
                        <span class="text-sm text-gray-600">{{
                            formatCurrency(creditoInfo.gastos_cobranza)
                        }}</span>
                    </div>

                    <!-- Intereses moratorios -->
                    <div
                        class="grid grid-cols-2 border-b border-gray-100 px-5 py-2.5"
                    >
                        <span class="text-sm text-gray-500"
                            >Intereses moratorios</span
                        >
                        <span class="text-sm text-gray-600">{{
                            formatCurrency(creditoInfo.intereses_moratorios)
                        }}</span>
                    </div>

                    <!-- Pago con -->
                    <div
                        class="grid grid-cols-2 items-center border-b border-gray-100 px-5 py-2"
                    >
                        <span class="text-sm text-gray-500">Pago con</span>
                        <FormInput
                            type="select"
                            v-model="form.pago_con"
                            :options="pagoConOpts"
                            size="sm"
                        />
                    </div>

                    <!-- Abono a capital -->
                    <div
                        class="grid grid-cols-2 border-b border-gray-100 px-5 py-2.5"
                    >
                        <span class="text-sm text-gray-500"
                            >Abono a capital</span
                        >
                        <span class="text-sm text-gray-600">{{
                            creditoInfo.abono_capital
                        }}</span>
                    </div>

                    <!-- Condonación o descuento -->
                    <div
                        class="grid grid-cols-2 items-center border-b border-gray-100 px-5 py-2"
                    >
                        <span class="text-sm text-gray-500"
                            >Condonación o descuento</span
                        >
                        <FormInput
                            type="number"
                            v-model="form.condonacion"
                            placeholder="0"
                            size="sm"
                        />
                    </div>

                    <!-- Observaciones -->
                    <div
                        class="grid grid-cols-2 items-start border-b border-gray-100 px-5 py-2.5"
                    >
                        <span class="text-sm text-gray-500 pt-1"
                            >Observaciones</span
                        >
                        <FormInput
                            type="textarea"
                            v-model="form.observaciones"
                            placeholder="Escribe una observación..."
                            :rows="3"
                        />
                    </div>

                    <!-- Botones -->
                    <div
                        class="flex items-center justify-center gap-3 px-5 py-4"
                    >
                        <button
                            @click="generarAbono"
                            :disabled="loadingAbono"
                            class="h-9 px-6 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-medium transition-all flex items-center gap-2"
                        >
                            <svg
                                v-if="loadingAbono"
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
                            Generar Abono
                        </button>
                        <button
                            @click="liquidarHoy"
                            :disabled="loadingLiquidar"
                            class="h-9 px-6 rounded-lg bg-emerald-500 hover:bg-emerald-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-medium transition-all flex items-center gap-2"
                        >
                            <svg
                                v-if="loadingLiquidar"
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
                            Liquidar a hoy
                        </button>
                    </div>
                </div>

                <!-- ── Columna derecha: Saldo y recibos ── -->
                <div class="flex flex-col gap-5">
                    <!-- Saldo pendiente -->
                    <div
                        class="bg-white rounded-xl border border-gray-200 overflow-hidden"
                    >
                        <div
                            class="grid grid-cols-[1fr_auto_auto] items-center border-b border-gray-100 px-4 py-3 gap-4"
                        >
                            <span class="text-sm font-semibold text-[#1a5c2a]"
                                >Saldo Pendiente</span
                            >
                            <span class="text-sm text-gray-600 font-medium">{{
                                formatCurrency(creditoInfo.saldo_pendiente)
                            }}</span>
                            <button
                                @click="verDetalle"
                                class="h-7 px-3 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-medium transition-all"
                            >
                                Detalle
                            </button>
                        </div>
                        <div
                            class="grid grid-cols-[1fr_auto] items-center px-4 py-3 gap-4"
                        >
                            <span class="text-sm font-semibold text-[#1a5c2a]"
                                >Mora pendiente</span
                            >
                            <span class="text-sm text-gray-600 font-medium">{{
                                formatCurrency(creditoInfo.mora_pendiente)
                            }}</span>
                        </div>
                    </div>

                    <!-- Recibos de pago -->
                    <div
                        class="bg-white rounded-xl border border-gray-200 overflow-hidden"
                    >
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-semibold text-[#1a5c2a]">
                                Recibos de pago
                            </p>
                        </div>

                        <div
                            class="grid grid-cols-[auto_1fr_1fr_1fr_auto] gap-2 px-4 py-2 bg-gray-50 border-b border-gray-100"
                        >
                            <span class="text-xs font-medium text-gray-500"
                                >Código</span
                            >
                            <span class="text-xs font-medium text-gray-500"
                                >Valor</span
                            >
                            <span class="text-xs font-medium text-gray-500"
                                >Fecha generación</span
                            >
                            <span class="text-xs font-medium text-gray-500"
                                >Generado por</span
                            >
                            <span class="text-xs font-medium text-gray-500"
                                >Acciones</span
                            >
                        </div>

                        <div
                            v-for="recibo in creditoInfo.recibos"
                            :key="recibo.codigo"
                            class="grid grid-cols-[auto_1fr_1fr_1fr_auto] gap-2 items-center px-4 py-2.5 border-b border-gray-50 last:border-b-0"
                        >
                            <span class="text-xs text-gray-600">{{
                                recibo.codigo
                            }}</span>
                            <span class="text-xs text-gray-600">{{
                                formatCurrency(recibo.valor)
                            }}</span>
                            <span class="text-xs text-gray-600">{{
                                recibo.fecha
                            }}</span>
                            <span class="text-xs text-gray-600">{{
                                recibo.generado_por
                            }}</span>
                            <button
                                @click="descargarRecibo(recibo)"
                                class="h-7 w-7 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white transition-all flex items-center justify-center"
                                title="Descargar recibo"
                            >
                                <svg
                                    width="12"
                                    height="12"
                                    viewBox="0 0 16 16"
                                    fill="none"
                                >
                                    <path
                                        d="M8 2v8M5 7l3 3 3-3M3 13h10"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </button>
                        </div>

                        <div
                            v-if="!creditoInfo.recibos?.length"
                            class="px-4 py-4 text-xs text-gray-300 text-center"
                        >
                            No hay recibos generados
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import FormInput from '@/components/form/FormInput.vue'

// ── Estado ─────────────────────────────────────────────────────────────────
const loadingPlantilla = ref(false)
const loadingCarga = ref(false)
const loadingRefresh = ref(false)
const loadingAbono = ref(false)
const loadingLiquidar = ref(false)
const creditoInfo = ref(null)

const form = reactive({
    cliente_id: '',
    credito_id: '',
    num_meses: 6,
    periodicidad: 'Mensual',
    valor_pagar: 0,
    abono_credito: 0,
    pago_con: 'Efectivo',
    condonacion: 0,
    observaciones: '',
})

// ── Opciones ───────────────────────────────────────────────────────────────
const clientes = [
    { value: '1', label: 'CAROLINA RINCON DELGADO (63555661)' },
    { value: '2', label: 'CARLOS ANDRES GOMEZ (10345678)' },
    { value: '3', label: 'MARIA FERNANDA LOPEZ (52345678)' },
]

const creditos = ref([{ value: '1', label: '5173' }])

const mesesOpciones = [1, 2, 3, 6, 12, 18, 24, 36]

const periodicidadOpts = [
    { value: 'Mensual', label: 'Mensual' },
    { value: 'Quincenal', label: 'Quincenal' },
    { value: 'Semanal', label: 'Semanal' },
]

const pagoConOpts = [
    { value: 'Efectivo', label: 'Efectivo' },
    { value: 'Transferencia', label: 'Transferencia' },
    { value: 'Cheque', label: 'Cheque' },
    { value: 'Datafono', label: 'Datáfono' },
]

// ── Computed ───────────────────────────────────────────────────────────────
const camposLectura = computed(() => {
    if (!creditoInfo.value) return []
    return [
        {
            key: 'num_credito',
            label: 'Crédito número',
            value: creditoInfo.value.num_credito,
        },
        {
            key: 'fecha_credito',
            label: 'Fecha de crédito',
            value: creditoInfo.value.fecha_credito,
        },
        {
            key: 'valor_compra',
            label: 'Valor de compra',
            value: formatCurrency(creditoInfo.value.valor_compra),
        },
        {
            key: 'valor_credito',
            label: 'Valor de crédito',
            value: formatCurrency(creditoInfo.value.valor_credito),
        },
    ]
})

const camposCalculados = computed(() => {
    if (!creditoInfo.value) return []
    return [
        {
            key: 'valor_cuotas',
            label: 'Valor de las cuotas',
            value: formatCurrency(creditoInfo.value.valor_cuotas),
        },
        {
            key: 'total_abonado',
            label: 'Total Abonado',
            value: formatCurrency(creditoInfo.value.total_abonado),
        },
    ]
})

// ── Helpers ────────────────────────────────────────────────────────────────
function formatCurrency(value) {
    if (value == null) return '$0'
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0,
    }).format(value)
}

// ── Handlers ───────────────────────────────────────────────────────────────
function onClienteChange() {
    form.credito_id = ''
    creditos.value = []
    creditoInfo.value = null
    // TODO: cargar créditos del cliente desde la API
}

function onCreditoChange() {
    if (!form.credito_id) {
        creditoInfo.value = null
        return
    }
    // TODO: reemplazar con llamada real a la API
    creditoInfo.value = {
        num_credito: 9449,
        fecha_credito: '2025-10-10 22:15:37',
        valor_compra: 650030,
        valor_credito: 700619,
        valor_cuotas: 115382,
        total_abonado: 123709,
        gastos_cobranza: 21922,
        intereses_moratorios: 10281,
        abono_capital: 'En mora',
        saldo_pendiente: 576910,
        mora_pendiente: 378349,
        recibos: [
            {
                codigo: 60428,
                valor: 123709,
                fecha: '2025-12-11 10:01:37',
                generado_por: 'CDA ITG',
            },
        ],
    }
}

async function onFileChange(file) {
    if (!file) return
    loadingCarga.value = true
    try {
        console.log('Archivo:', file.name)
        // TODO: enviar archivo al backend
    } catch (err) {
        console.error(err)
    } finally {
        loadingCarga.value = false
    }
}

async function descargarPlantilla() {
    loadingPlantilla.value = true
    try {
        console.log('Descargando plantilla...')
    } catch (err) {
        console.error(err)
    } finally {
        loadingPlantilla.value = false
    }
}

function condonacionMasiva() {
    console.log('Condonación masiva')
}

async function fetchData() {
    loadingRefresh.value = true
    try {
        await new Promise(r => setTimeout(r, 800))
    } catch (err) {
        console.error(err)
    } finally {
        loadingRefresh.value = false
    }
}

async function generarAbono() {
    loadingAbono.value = true
    try {
        console.log('Generando abono:', { ...form })
    } catch (err) {
        console.error(err)
    } finally {
        loadingAbono.value = false
    }
}

async function liquidarHoy() {
    loadingLiquidar.value = true
    try {
        console.log('Liquidando crédito:', form.credito_id)
    } catch (err) {
        console.error(err)
    } finally {
        loadingLiquidar.value = false
    }
}

function verDetalle() {
    console.log('Ver detalle:', form.credito_id)
}
function descargarRecibo(r) {
    console.log('Descargar recibo:', r.codigo)
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
