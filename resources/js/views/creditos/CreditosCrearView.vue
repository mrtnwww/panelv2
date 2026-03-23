<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">Crear crédito</h1>

        <!-- Barra de selección de cliente -->
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <!-- Selector -->
            <div
                class="p-3 border-b border-gray-100 sm:border-b-0 sm:border-r sm:flex-none"
            >
                <FormInput
                    label="Cliente"
                    type="select"
                    v-model="form.cliente_id"
                    @update:modelValue="onClienteChange"
                    :options="clientesOpts"
                    placeholder="Seleccione un cliente"
                />
            </div>

            <!-- Celdas informativas: apiladas en móvil, fila en desktop -->
            <div
                v-if="clienteInfo"
                class="flex flex-col divide-y divide-gray-100 sm:flex-row sm:divide-y-0 sm:divide-x sm:border-t sm:border-gray-100"
            >
                <div
                    v-for="cell in topCells"
                    :key="cell.key"
                    class="flex items-center px-4 py-2.5 text-sm text-gray-600 sm:flex-1 sm:py-0 sm:min-h-[56px] truncate"
                >
                    {{ clienteInfo[cell.key] }}
                </div>
            </div>

            <!-- Placeholder cuando no hay cliente (solo desktop) -->
            <div v-else class="hidden sm:flex border-t border-gray-100">
                <div
                    v-for="cell in topCells"
                    :key="cell.key"
                    class="flex-1 flex items-center px-5 min-h-[56px] text-sm text-gray-300 italic border-r border-gray-100 last:border-r-0"
                >
                    —
                </div>
            </div>
        </div>

        <!-- Layout principal (visible al seleccionar cliente) -->
        <transition name="fade">
            <div
                v-if="clienteInfo"
                class="grid grid-cols-1 xl:grid-cols-[1fr_45%] gap-5 items-start"
            >
                <!-- ── Panel izquierdo: Formulario ── -->
                <div
                    class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-5"
                >
                    <!-- Sección: Selección de producto -->
                    <div class="flex flex-col gap-3">
                        <div>
                            <p class="text-sm font-semibold text-[#1a5c2a]">
                                Selección
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                Seleccione los productos, servicios o digite el
                                valor de la compra
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <FormInput
                                label="Valor de compra"
                                type="number"
                                v-model="form.valor_compra"
                                @input="onValorCompra"
                                placeholder="Digite el valor"
                                hint="Mínimo — — Máximo $600.000"
                            />
                            <FormInput
                                label="Producto / servicio"
                                type="select"
                                v-model="form.producto"
                                :options="productos"
                                placeholder="Seleccione un producto o servicio"
                            />
                            <FormInput
                                label="Línea"
                                type="select"
                                v-model="form.linea"
                                :options="lineas"
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <FormInput
                                label="Observación (Opcional)"
                                v-model="form.observacion"
                            />
                            <FormInput
                                label="Nº de placa"
                                v-model="form.placa"
                                placeholder="ABC123"
                            />
                        </div>

                        <!-- Día preferible de pago — se mantiene manual por ser un control inline con texto a los lados -->
                        <div
                            class="flex items-center gap-2 text-sm text-gray-500"
                        >
                            <span>Día preferible de pago</span>
                            <select
                                v-model="form.dia_pago"
                                @change="calcPlan"
                                class="w-16 h-9 px-2 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600 outline-none focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10 transition-all"
                            >
                                <option
                                    v-for="d in diasPago"
                                    :key="d"
                                    :value="d"
                                >
                                    {{ d }}
                                </option>
                            </select>
                            <span>de cada mes</span>
                        </div>
                    </div>

                    <div class="border-t border-gray-100" />

                    <!-- Sección: Descripción / Valores -->
                    <div class="flex flex-col gap-1">
                        <p class="text-sm font-semibold text-[#1a5c2a]">
                            Descripción
                        </p>
                        <p class="text-sm font-medium text-[#0A2540] mb-2">
                            Valores
                        </p>

                        <div
                            v-for="row in descripcionRows"
                            :key="row.key"
                            class="flex items-center justify-between py-2.5 border-b border-gray-100 last:border-b-0"
                        >
                            <span class="text-sm text-gray-500">{{
                                row.label
                            }}</span>

                            <!-- Campo solo lectura -->
                            <input
                                v-if="row.readonly"
                                :value="row.value"
                                readonly
                                class="w-48 h-8 px-3 text-sm text-right rounded-lg border border-gray-100 bg-gray-50 text-gray-400 outline-none"
                            />

                            <!-- Select editable — se mantiene manual por el diseño de fila compacto (h-8, w-48 fijo) -->
                            <div v-else class="relative w-48">
                                <select
                                    v-model="form[row.key]"
                                    @change="calcPlan"
                                    class="w-full h-8 px-2 pr-7 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600 appearance-none cursor-pointer outline-none focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10 transition-all"
                                >
                                    <option
                                        v-for="opt in row.options"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </option>
                                </select>
                                <ChevronIcon />
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100" />

                    <!-- Sección: Observaciones del cliente -->
                    <div class="flex flex-col gap-2">
                        <p class="text-sm font-semibold text-[#1a5c2a]">
                            Observaciones del cliente
                        </p>
                        <span
                            class="inline-block self-start text-xs font-semibold px-3 py-1 rounded-full"
                            :class="
                                clienteInfo.obs === 'APROBADO'
                                    ? 'bg-emerald-100 text-emerald-800'
                                    : 'bg-yellow-100 text-yellow-800'
                            "
                        >
                            {{ clienteInfo.obs }}
                        </span>
                    </div>

                    <!-- Botón submit -->
                    <div
                        class="flex justify-center pt-1 border-t border-gray-100"
                    >
                        <button
                            @click="handleSubmit"
                            :disabled="loading || !form.valor_compra"
                            class="h-9 px-8 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-medium transition-all flex items-center gap-2"
                        >
                            <svg
                                v-if="loading"
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
                            Enviar código OTP
                        </button>
                    </div>
                </div>

                <!-- ── Panel derecho: Plan de pagos ── -->
                <div
                    class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-4"
                >
                    <!-- Header -->
                    <div class="flex items-baseline justify-between">
                        <p class="text-sm font-semibold text-[#1a5c2a]">
                            Plan de pagos
                        </p>
                        <p class="text-xs text-gray-400">
                            Valor aprobado:
                            <strong class="text-gray-600">{{
                                valorAprobadoFmt
                            }}</strong>
                        </p>
                    </div>
                    <p class="text-xs text-gray-400 -mt-2">
                        Aval + IVA:
                        <strong class="text-gray-600">{{ avalFmt }}</strong>
                    </p>

                    <!-- Tabla de cuotas -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th
                                        v-for="h in planHeaders"
                                        :key="h"
                                        class="pb-2 text-center font-medium text-gray-400 first:text-left"
                                    >
                                        {{ h }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="planRows.length === 0">
                                    <td
                                        :colspan="planHeaders.length"
                                        class="py-4 text-center text-gray-300"
                                    >
                                        Complete el formulario para ver el plan
                                    </td>
                                </tr>
                                <tr
                                    v-for="(row, i) in planRows"
                                    :key="i"
                                    class="border-b border-gray-50 last:border-b-0"
                                >
                                    <td
                                        v-for="(val, j) in row"
                                        :key="j"
                                        class="py-1.5 text-center text-gray-500 first:text-left"
                                    >
                                        {{ val }}
                                    </td>
                                </tr>
                                <tr
                                    v-if="
                                        planRows.length > 0 &&
                                        form.num_meses > 6
                                    "
                                    class="text-center text-gray-300"
                                >
                                    <td
                                        :colspan="planHeaders.length"
                                        class="py-1.5"
                                    >
                                        ... {{ form.num_meses - 6 }} cuotas más
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-gray-100" />

                    <!-- Producto -->
                    <div class="flex flex-col gap-1">
                        <p class="text-sm font-semibold text-[#1a5c2a]">
                            Producto
                        </p>
                        <p class="text-xs text-gray-400">
                            {{
                                lineas.find(l => l.value === form.linea)
                                    ?.label || '—'
                            }}
                        </p>
                    </div>

                    <!-- Vencimientos -->
                    <div class="flex flex-col gap-1">
                        <p class="text-sm font-semibold text-[#1a5c2a]">
                            Vencimientos
                        </p>
                        <p class="text-xs text-gray-400">
                            {{ vencimientosFmt || '—' }}
                        </p>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import FormInput from '@/components/form/FormInput.vue'
import ChevronIcon from '@/components/form/ChevronIcon.vue'

// ── Opciones de selects ────────────────────────────────────────────────────
const clientes = [
    {
        id: 1,
        nombre: 'CAROLINA RINCON DELGADO',
        cedula: '63555661',
        cupo: '$1.600.000 cupo aprobado',
        creditos: 'no tiene créditos vigentes',
        tipo: 'Tipo crédito - SOAT',
        obs: 'APROBADO',
        tasa: 1.91,
    },
    {
        id: 2,
        nombre: 'CARLOS ANDRES GOMEZ',
        cedula: '10345678',
        cupo: '$12.000.000 cupo aprobado',
        creditos: '2 créditos vigentes',
        tipo: 'Tipo crédito - Libre inversión',
        obs: 'APROBADO',
        tasa: 2.5,
    },
    {
        id: 3,
        nombre: 'MARIA FERNANDA LOPEZ',
        cedula: '52345678',
        cupo: '$8.500.000 cupo aprobado',
        creditos: '1 crédito vigente',
        tipo: 'Tipo crédito - Vehículo',
        obs: 'REQUIERE VERIFICACIÓN',
        tasa: 1.91,
    },
]

// Opciones formateadas para FormInput type="select"
const clientesOpts = clientes.map(c => ({
    value: c.id,
    label: `${c.nombre} (${c.cedula})`,
}))

const productos = [
    { value: 'soat', label: 'SOAT' },
    { value: 'tecno', label: 'Tecnomecánica' },
    { value: 'seguros', label: 'Seguro todo riesgo' },
]

const lineas = [
    { value: 'tecnico', label: 'TÉCNICO MECÁNICA' },
    { value: 'soat', label: 'SOAT' },
    { value: 'seguros', label: 'SEGUROS' },
]

const diasPago = [1, 5, 10, 15, 20, 25]
const mesesOpciones = [1, 2, 3, 6, 12, 18, 24, 36].map(v => ({
    value: v,
    label: String(v),
}))
const periodicidades = [
    { value: 'Mensual', label: 'Mensual' },
    { value: 'Quincenal', label: 'Quincenal' },
    { value: 'Semanal', label: 'Semanal' },
]

// Celdas informativas de la barra superior
const topCells = [{ key: 'cupo' }, { key: 'creditos' }, { key: 'tipo' }]
const planHeaders = ['Saldo', 'Capital', 'Interés (1.91% N.M.)', 'Valor cuotas']

// ── Estado ─────────────────────────────────────────────────────────────────
const loading = ref(false)
const clienteInfo = ref(null)
const planRows = ref([])
const vencimientosFmt = ref('')

const form = reactive({
    cliente_id: '',
    valor_compra: null,
    producto: '',
    linea: 'tecnico',
    observacion: '',
    placa: '',
    dia_pago: 15,
    periodicidad: 'Mensual',
    num_meses: 12,
})

// ── Computed ───────────────────────────────────────────────────────────────
const valorAprobadoFmt = computed(() =>
    form.valor_compra ? formatCurrency(form.valor_compra) : '$0'
)

const avalFmt = computed(() =>
    form.valor_compra ? formatCurrency(form.valor_compra * 0.02) : '$0'
)

const valorCreditoFmt = computed(() =>
    form.valor_compra ? formatCurrency(form.valor_compra) : ''
)

const valorCuotasFmt = computed(() => {
    if (!form.valor_compra || !clienteInfo.value) return ''
    return formatCurrency(
        calcCuota(
            form.valor_compra,
            clienteInfo.value.tasa / 100,
            form.num_meses
        )
    )
})

// Filas de la sección "Descripción / Valores"
const descripcionRows = computed(() => [
    {
        key: 'valor_compra',
        label: 'Valor del crédito',
        readonly: true,
        value: valorCreditoFmt.value,
    },
    {
        key: 'periodicidad',
        label: 'Periodicidad de cuotas',
        readonly: false,
        options: periodicidades,
    },
    {
        key: 'num_meses',
        label: 'Número de meses',
        readonly: false,
        options: mesesOpciones,
    },
    {
        key: 'val_cuotas',
        label: 'Valor de cuotas',
        readonly: true,
        value: valorCuotasFmt.value,
    },
])

// ── Helpers ────────────────────────────────────────────────────────────────
function formatCurrency(value) {
    if (value == null) return '$0'
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0,
    }).format(value)
}

function calcCuota(monto, tasa, meses) {
    if (!monto || !tasa || !meses) return 0
    return (
        (monto * (tasa * Math.pow(1 + tasa, meses))) /
        (Math.pow(1 + tasa, meses) - 1)
    )
}

// ── Handlers ───────────────────────────────────────────────────────────────
function onClienteChange() {
    clienteInfo.value =
        clientes.find(c => c.id === Number(form.cliente_id)) || null
    calcPlan()
}

function onValorCompra() {
    calcPlan()
}

function calcPlan() {
    if (!form.valor_compra || !clienteInfo.value) {
        planRows.value = []
        vencimientosFmt.value = ''
        return
    }

    const monto = form.valor_compra
    const meses = form.num_meses
    const tasa = clienteInfo.value.tasa / 100
    const dia = form.dia_pago
    const cuota = calcCuota(monto, tasa, meses)
    let saldo = monto
    const rows = []
    const venc = []
    const hoy = new Date()

    for (let i = 1; i <= Math.min(meses, 6); i++) {
        const interes = saldo * tasa
        const capital = cuota - interes
        saldo -= capital
        const fecha = new Date(hoy.getFullYear(), hoy.getMonth() + i, dia)
        venc.push(
            fecha.toLocaleDateString('es-CO', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            })
        )
        rows.push([
            formatCurrency(Math.max(saldo, 0)),
            formatCurrency(capital),
            formatCurrency(interes),
            formatCurrency(cuota),
        ])
    }

    planRows.value = rows
    vencimientosFmt.value =
        venc.slice(0, 3).join(' · ') + (meses > 3 ? ' ...' : '')
}

async function handleSubmit() {
    loading.value = true
    try {
        // TODO: llamar al endpoint de generación de crédito y envío de OTP
        console.log('Payload:', { ...form })
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
}
</script>
