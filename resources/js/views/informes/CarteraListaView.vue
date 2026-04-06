<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado + filtros en una línea -->
        <div class="flex flex-wrap items-end gap-4">
            <h1 class="text-lg font-semibold text-[#0A2540] mr-auto">
                Cartera por edades
            </h1>

            <!-- Fecha inicial -->
            <FormInput
                label="Fecha inicial"
                type="date"
                v-model="filters.fechaInicial"
            />

            <!-- Fecha final -->
            <FormInput
                label="Fecha inicial"
                type="date"
                v-model="filters.fechaFinal"
            />

            <!-- Aliado -->
            <FormSelectAsync
                label="Aliado"
                v-model="filters.aliado"
                :fetch-options="opcionesStore.fetchEmpresas"
                placeholder="Seleccione un aliado"
                wrapper-class="xl:w-[30%]"
            />

            <!-- Botón actualizar mora -->
            <UpdateMoraButton :onSuccess="fetchCartera" />
        </div>

        <!-- Tabla de cartera por edades -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <!-- Loading overlay -->
            <div v-if="loading" class="flex items-center justify-center py-20">
                <div class="flex flex-col items-center gap-3">
                    <svg
                        class="animate-spin w-6 h-6 text-[#1a5c2a]"
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
                    <span class="text-xs text-gray-400"
                        >Calculando cartera...</span
                    >
                </div>
            </div>

            <!-- Filas de mora -->
            <template v-else>
                <div
                    v-for="(tramo, i) in tramos"
                    :key="tramo.key"
                    :class="[
                        'flex items-center justify-between px-8 py-4 transition-colors hover:bg-gray-50/60',
                        i < tramos.length - 1 ? 'border-b border-gray-100' : '',
                        tramo.isTotal
                            ? 'bg-gray-50 border-t-2 border-gray-200'
                            : '',
                    ]"
                >
                    <!-- Label con color según antigüedad -->
                    <span
                        :class="[
                            'text-sm font-medium',
                            tramo.isTotal
                                ? 'text-[#0A2540] font-semibold'
                                : tramo.color,
                        ]"
                    >
                        {{ tramo.label }}
                    </span>

                    <!-- Valor -->
                    <div class="flex items-center gap-4">
                        <span
                            :class="[
                                'text-sm tabular-nums',
                                tramo.isTotal
                                    ? 'text-[#0A2540] font-semibold text-base'
                                    : 'text-gray-600',
                            ]"
                        >
                            {{ formatCurrency(cartera[tramo.key] ?? 0) }}
                        </span>

                        <!-- Barra visual proporcional (solo en filas normales) -->
                        <div v-if="!tramo.isTotal" class="hidden sm:block w-32">
                            <div
                                class="h-1.5 bg-gray-100 rounded-full overflow-hidden"
                            >
                                <div
                                    class="h-full rounded-full transition-all duration-700"
                                    :class="tramo.barColor"
                                    :style="{
                                        width:
                                            porcentaje(cartera[tramo.key]) +
                                            '%',
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Resumen de porcentajes -->
        <div
            v-if="!loading && totalCartera > 0"
            class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3"
        >
            <div
                v-for="tramo in tramos.filter(t => !t.isTotal)"
                :key="tramo.key"
                class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex flex-col gap-1"
            >
                <span class="text-xs font-medium" :class="tramo.color">{{
                    tramo.labelCorto
                }}</span>
                <span class="text-lg font-semibold text-[#0A2540] tabular-nums">
                    {{ porcentaje(cartera[tramo.key]).toFixed(1) }}%
                </span>
                <span class="text-xs text-gray-400 tabular-nums">
                    {{ formatCurrency(cartera[tramo.key] ?? 0) }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'

// -- Componentes ---------------------------------------------------------
import FormInput from '@/components/form/FormInput.vue'

import UpdateMoraButton from '@/components/table/UpdateMoraButton.vue'
import FormSelectAsync from '@/components/form/FormSelectAsync.vue'

// -- Loader ----------------------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

// -- Store ---------------------------------------------------------------
import { useOpcionesStore } from '@/stores/opciones'

import { formatCurrency } from '@/utils/format'
import api from '@/services/api'

// -- Definición de tramos ------------------------------------------------
const tramos = [
    {
        key: 'mora_1_10',
        label: 'Mora de 1 a 10 dias',
        labelCorto: '1-10 días',
        color: 'text-gray-600',
        barColor: 'bg-gray-400',
        isTotal: false,
    },
    {
        key: 'mora_11_30',
        label: 'Mora de 11 a 30 dias',
        labelCorto: '11-30 días',
        color: 'text-emerald-600',
        barColor: 'bg-emerald-400',
        isTotal: false,
    },
    {
        key: 'mora_31_60',
        label: 'Mora de 31 a 60 dias',
        labelCorto: '31-60 días',
        color: 'text-yellow-500',
        barColor: 'bg-yellow-400',
        isTotal: false,
    },
    {
        key: 'mora_61_90',
        label: 'Mora de 61 a 90 dias',
        labelCorto: '61-90 días',
        color: 'text-orange-500',
        barColor: 'bg-orange-400',
        isTotal: false,
    },
    {
        key: 'mora_91_120',
        label: 'Mora de 91 a 120 dias',
        labelCorto: '91-120 días',
        color: 'text-orange-600',
        barColor: 'bg-orange-500',
        isTotal: false,
    },
    {
        key: 'mora_120_mas',
        label: 'Mora de mas de 120 dias',
        labelCorto: '+120 días',
        color: 'text-red-500',
        barColor: 'bg-red-500',
        isTotal: false,
    },
    {
        key: 'total',
        label: 'Total',
        labelCorto: 'Total',
        color: 'text-[#0A2540]',
        barColor: '',
        isTotal: true,
    },
]

const opcionesStore = useOpcionesStore()

// -- Estado -------------------------------------------------------
const loading = ref(false)
const cartera = reactive({
    mora_1_10: 0,
    mora_11_30: 0,
    mora_31_60: 0,
    mora_61_90: 0,
    mora_91_120: 0,
    mora_120_mas: 0,
    total: 0,
})

const filters = reactive({
    fechaInicial: '',
    fechaFinal: '',
    aliado: '',
})

const totalCartera = computed(() =>
    tramos
        .filter(t => !t.isTotal)
        .reduce((sum, t) => sum + (cartera[t.key] ?? 0), 0)
)

function porcentaje(valor) {
    if (!totalCartera.value || !valor) return 0
    return (valor / totalCartera.value) * 100
}

// -- Backend -------------------------------------------------------------------
async function fetchCartera() {
    loading.value = true
    try {
        const params = new URLSearchParams({
            ...(filters.fechaInicial && {
                fecha_inicial: filters.fechaInicial,
            }),
            ...(filters.fechaFinal && { fecha_final: filters.fechaFinal }),
            ...(filters.aliado && { aliado: filters.aliado }),
        })

        const { data } = await api.get('/api/cartera', { params })
        Object.assign(cartera, data.resumenCartera)
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    start()

    try {
        await fetchCartera()
    } finally {
        stop()
    }
})
</script>
