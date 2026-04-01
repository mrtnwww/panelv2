<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">Abonar cuota</h1>

        <!-- Panel principal -->
        <div
            class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-5"
        >
            <!-- Acciones masivas -->
            <div
                class="flex flex-col gap-3 pb-5 border-b border-gray-100 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <FormInput
                        type="file"
                        accept=".xlsx,.csv"
                        button-label="Cargar abonos"
                        size="sm"
                        @change="onFileChange"
                    />

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
                <FormSelectAsync
                    label="Buscar cliente"
                    v-model="form.cliente_id"
                    @update:modelValue="onClienteChange"
                    :fetch-options="opcionesStore.fetchClientesCredits"
                    placeholder="Seleccione un cliente"
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
                        v-if="false"
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
                                @click="verEstadoCredito(form.cliente_id)"
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
                            class="hidden sm:grid grid-cols-12 gap-4 px-4 py-2 bg-gray-50 border-b border-gray-100"
                        >
                            <span
                                class="col-span-2 text-xs font-medium text-gray-500"
                                >Código</span
                            >
                            <span
                                class="col-span-2 text-xs font-medium text-gray-500"
                                >Valor</span
                            >
                            <span
                                class="col-span-3 text-xs font-medium text-gray-500"
                                >Fecha generación</span
                            >
                            <span
                                class="col-span-4 text-xs font-medium text-gray-500"
                                >Generado por</span
                            >
                            <span
                                class="col-span-1 text-xs font-medium text-gray-500 text-center"
                                >Acciones</span
                            >
                        </div>

                        <div class="divide-y divide-gray-100">
                            <div
                                v-for="recibo in creditoInfo.recibos"
                                :key="recibo.codigo"
                                class="flex flex-col sm:grid sm:grid-cols-12 gap-2 sm:gap-4 sm:items-center px-4 py-3 sm:py-2.5 hover:bg-gray-50/50 transition-colors"
                            >
                                <div
                                    class="flex justify-between items-center sm:contents"
                                >
                                    <span
                                        class="text-sm font-medium sm:text-xs sm:font-normal text-gray-800 sm:text-gray-600 sm:col-span-2"
                                    >
                                        <span
                                            class="sm:hidden text-gray-400 font-normal mr-1"
                                            >Cód:</span
                                        >{{ recibo.codigo }}
                                    </span>

                                    <button
                                        @click="descargarRecibo(recibo)"
                                        class="sm:hidden h-8 w-8 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white transition-all flex items-center justify-center shadow-sm"
                                        title="Descargar recibo"
                                    >
                                        <i class="fa-solid fa-print"></i>
                                    </button>
                                </div>

                                <div
                                    class="flex flex-col gap-1 sm:contents text-xs text-gray-600"
                                >
                                    <div
                                        class="flex justify-between sm:block sm:col-span-2"
                                    >
                                        <span class="sm:hidden text-gray-400"
                                            >Valor:</span
                                        >
                                        <span
                                            class="font-medium sm:font-normal"
                                            >{{
                                                formatCurrency(recibo.valor)
                                            }}</span
                                        >
                                    </div>

                                    <div
                                        class="flex justify-between sm:block sm:col-span-3"
                                    >
                                        <span class="sm:hidden text-gray-400"
                                            >Fecha:</span
                                        >
                                        <span>{{ recibo.fecha }}</span>
                                    </div>

                                    <div
                                        class="flex justify-between items-center sm:block sm:col-span-4 min-w-0 gap-2"
                                    >
                                        <span
                                            class="sm:hidden text-gray-400 shrink-0"
                                            >Por:</span
                                        >
                                        <span class="truncate block">{{
                                            recibo.generado_por
                                        }}</span>
                                    </div>
                                </div>

                                <div
                                    class="hidden sm:flex sm:justify-center sm:col-span-1"
                                >
                                    <button
                                        @click="descargarRecibo(recibo)"
                                        class="h-7 w-7 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white transition-all flex items-center justify-center"
                                        title="Descargar recibo"
                                    >
                                        <i class="fa-solid fa-print"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="!creditoInfo.recibos?.length"
                            class="px-4 py-6 text-sm text-gray-400 text-center"
                        >
                            No hay recibos generados
                        </div>
                    </div>
                </div>
            </div>
        </transition>

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
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'

// -- Componentes -------------------------------------------
import EstadoCreditoModal from '@/components/modals/EstadoCreditoModal.vue'
import FormSelectAsync from '@/components/form/FormSelectAsync.vue'
import FormInput from '@/components/form/FormInput.vue'

// -- Composables -------------------------------------------
import { useEstadoCredito } from '@/composables/useEstadoCredito'

// -- Loader -------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

// -- Store ----------------------------------------------------
import { useOpcionesStore } from '@/stores/opciones'

import { formatCurrency } from '@/utils/format'
import api from '@/services/api'

// -- Estado ------------------------------------------------
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
    pago_con: '',
    condonacion: 0,
    observaciones: '',
})

// -- Opciones de selects -------------------------------------
const opcionesStore = useOpcionesStore()

// -- Opciones ----------------------------------------------------------
const creditos = ref([])
const pagoConOpts = ref([])

// -- Computed ----------------------------------------------------------
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
        {
            key: 'num_meses',
            label: 'Número de meses',
            value: creditoInfo.value.num_meses,
        },
        {
            key: 'periodicidad',
            label: 'Periodicidad',
            value: creditoInfo.value.periodicidad,
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

// -- Handlers ---------------------------------------------------------------
function onClienteChange() {
    creditoInfo.value = null
    form.credito_id = ''
    creditos.value = []

    const cliente = opcionesStore.clientesCreditos.find(
        c => c.id == form.cliente_id
    )

    creditos.value = cliente
        ? cliente.credito.map(cr => ({
              value: cr.id,
              label: `Crédito ${cr.id} (${formatCurrency(cr.valor_credito)})`,
          }))
        : []
}

async function onCreditoChange() {
    const id = form.credito_id

    if (!id) {
        creditoInfo.value = null
        pagoConOpts.value = []
        return
    }

    start()

    try {
        const { data } = await api.get('/api/creditos/detailCredit', {
            params: { credito_id: id },
        })

        setCreditoInfo(data.datos)
        setPagoOptions(data.datos.tipoPago)
    } catch (err) {
        console.error(err)
    } finally {
        stop()
    }
}

function setCreditoInfo(datos) {
    const c = datos.credito

    creditoInfo.value = {
        num_credito: c.id,
        fecha_credito: c.created_at,
        valor_compra: c.valor_compra,
        valor_credito: c.valor_credito,
        valor_cuotas: c.val_cuotas,
        total_abonado: datos.totalAbonado,
        gastos_cobranza: datos.total_gastos_c,
        intereses_moratorios: datos.total_intereses_m,
        abono_capital: datos.fechaAbonoCapital,
        saldo_pendiente: datos.saldo,
        mora_pendiente: datos.saldoMora,
        num_meses: c.num_cuotas,
        recibos: mapRecibos(datos.abonos),
        periodicidad: c.periocidad == 1 ? 'Mensual' : 'Quincenal',
    }
}

function mapRecibos(abonos = []) {
    return abonos.map(a => ({
        codigo: a.id,
        valor: a.valor,
        fecha: a.created_at,
        generado_por: a.hecho.toUpperCase(),
    }))
}

function setPagoOptions(tipos = []) {
    pagoConOpts.value = tipos.map(t => ({
        value: t.id,
        label: t.nombre,
    }))
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

function descargarRecibo(r) {
    console.log('Descargar recibo:', r.codigo)
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
</script>
