<template>
    <AppModal
        :model-value="modelValue"
        size="xl"
        :no-padding="true"
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <template #header>
            <div class="flex items-center gap-3">
                <button
                    @click="$emit('ver-historico')"
                    class="h-8 px-3 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] text-white text-xs font-medium transition-all flex items-center gap-2"
                >
                    <i class="fa-regular fa-clock"></i>
                    Ver histórico
                </button>
            </div>
        </template>

        <div v-if="loading" class="flex items-center justify-center py-16">
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
        </div>

        <div v-else-if="credito" class="flex flex-col">
            <div class="px-5 pt-5 pb-4">
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <InfoRow
                        label="Cliente"
                        :value="credito.cliente.nombre"
                        bold
                    />
                    <InfoRow
                        label="Cédula"
                        :value="credito.cliente.cedula"
                        class="border-t border-gray-100"
                    />
                    <InfoRow
                        label="Correo"
                        :value="credito.cliente.correo"
                        class="border-t border-gray-100"
                        value-class="truncate"
                    />
                    <InfoRow
                        label="Contacto"
                        :value="credito.cliente.telefono"
                        class="border-t border-gray-100"
                    />
                </div>
            </div>

            <div class="px-5 pb-4">
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="grid grid-cols-1 sm:grid-cols-2">
                        <InfoRow
                            label="Valor de compra"
                            :value="formatCurrency(credito.valor_compra)"
                        />
                        <InfoRow
                            label="Crédito en mora"
                            :value="credito.en_mora ? 'SÍ' : 'NO'"
                            :value-class="
                                credito.en_mora
                                    ? 'text-red-500 font-semibold'
                                    : 'text-emerald-600 font-semibold'
                            "
                            class="border-t sm:border-t-0 sm:border-l border-gray-100"
                        />
                    </div>
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 border-t border-gray-100"
                    >
                        <InfoRow
                            label="Valor pendiente"
                            :value="formatCurrency(credito.valor_pendiente)"
                        />
                        <InfoRow
                            label="Número de cuotas"
                            :value="String(credito.num_cuotas)"
                            class="border-t sm:border-t-0 sm:border-l border-gray-100"
                        />
                    </div>
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 border-t border-gray-100"
                    >
                        <InfoRow
                            label="Valor pagado"
                            :value="formatCurrency(credito.valor_pagado)"
                        />
                        <InfoRow
                            label="Periodicidad"
                            :value="credito.periodicidad"
                            class="border-t sm:border-t-0 sm:border-l border-gray-100"
                        />
                    </div>
                    <div class="border-t border-gray-100">
                        <InfoRow
                            label="Valor cuotas"
                            :value="formatCurrency(credito.valor_cuotas)"
                        />
                    </div>
                </div>
            </div>

            <div class="px-5 pb-4">
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="grid grid-cols-1 sm:grid-cols-2">
                        <InfoRow
                            label="Interéses moratorios"
                            :value="
                                formatCurrency(credito.intereses_moratorios)
                            "
                        />
                        <InfoRow
                            label="Gastos cobranza"
                            :value="formatCurrency(credito.gastos_cobranza)"
                            class="border-t sm:border-t-0 sm:border-l border-gray-100"
                        />
                    </div>
                </div>
            </div>

            <div class="mx-5 pb-4 overflow-x-auto">
                <table
                    class="w-full text-sm border border-gray-200 rounded-xl overflow-hidden min-w-150"
                >
                    <thead>
                        <tr class="bg-[#1a5c2a] text-white">
                            <th
                                v-for="col in cuotasCols"
                                :key="col"
                                class="px-3 py-2.5 text-left text-xs font-semibold whitespace-nowrap"
                            >
                                {{ col }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(cuota, i) in credito.cuotas"
                            :key="i"
                            :class="[
                                'border-t border-gray-100 hover:bg-gray-50/60 transition-colors',
                                cuota.estado === 'Pagada'
                                    ? 'bg-emerald-50/40'
                                    : '',
                            ]"
                        >
                            <td class="px-3 py-2.5 font-medium text-gray-700">
                                {{ i + 1 }}
                            </td>
                            <td
                                class="px-3 py-2.5 text-gray-600 whitespace-nowrap"
                            >
                                {{ cuota.fecha }}
                            </td>
                            <td class="px-3 py-2.5 text-gray-600">
                                {{ formatCurrency(cuota.valor) }}
                            </td>
                            <td class="px-3 py-2.5 text-gray-600">
                                {{
                                    formatCurrency(
                                        cuota.valor_cuota - cuota.valor
                                    )
                                }}
                            </td>
                            <td class="px-3 py-2.5">
                                <span
                                    v-if="cuota.pagado === 1"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700"
                                    >Pagada</span
                                >
                                <span
                                    v-else-if="
                                        cuota.pagado === 0 && cuota.diasmora > 0
                                    "
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-600 whitespace-nowrap"
                                    >{{
                                        cuota.diasmora + ' días en mora'
                                    }}</span
                                >
                                <span
                                    v-else
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700"
                                    >Pendiente</span
                                >
                            </td>
                            <td class="px-3 py-2.5 text-gray-600">
                                {{ formatCurrency(cuota.intereses_moratorios) }}
                            </td>
                            <td class="px-3 py-2.5 text-gray-600">
                                {{ formatCurrency(cuota.gastos_cobranza) }}
                            </td>
                            <td
                                class="px-3 py-2.5 text-center text-gray-400 whitespace-nowrap"
                            >
                                {{ cuota.fecha_pago || '- -' }}
                                <button
                                    v-if="cuota.fecha_pago !== '- -'"
                                    class="btn-table ml-2"
                                    title="Imprimir abono"
                                    @click="$emit('imprimir-abono')"
                                >
                                    <i class="fa-solid fa-print"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-5 pb-5">
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="grid grid-cols-1 sm:grid-cols-2">
                        <InfoRow
                            label="Realizado por"
                            :value="credito.realizado_por"
                            bold
                        />
                        <InfoRow
                            label="Fecha"
                            :value="credito.fecha_creacion"
                            class="border-t sm:border-t-0 sm:border-l border-gray-100"
                        />
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div
                class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2 w-full"
            >
                <button
                    @click="$emit('liquidar')"
                    class="w-full sm:w-auto justify-center h-9 px-4 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] text-white text-xs font-medium transition-all flex items-center gap-2"
                >
                    <i class="fa-solid fa-dollar-sign"></i> Liquidar crédito
                </button>
                <button
                    @click="$emit('ver-plan-pagos')"
                    class="w-full sm:w-auto justify-center h-9 px-4 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-600 text-xs font-medium transition-all flex items-center gap-2"
                >
                    <i class="fa-regular fa-file-lines"></i> Ver plan de pagos
                </button>
                <button
                    @click="$emit('descargar-paz-salvo')"
                    class="w-full sm:w-auto justify-center h-9 px-4 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-600 text-xs font-medium transition-all flex items-center gap-2"
                >
                    <i class="fa-solid fa-download"></i> Descargar paz y salvo
                </button>
                <button
                    @click="$emit('imprimir')"
                    class="w-full sm:w-auto justify-center h-9 px-4 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-medium transition-all flex items-center gap-2 sm:ml-auto"
                >
                    <i class="fa-solid fa-print"></i> Imprimir
                </button>
            </div>
        </template>
    </AppModal>
</template>

<script setup>
// -- Componentes -----------------------------------
import InfoRow from '@/components/modals/InfoRow.vue'
import AppModal from '@/components/AppModal.vue'

import { formatCurrency } from '@/utils/format'

defineProps({
    modelValue: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    credito: { default: null },
})

defineEmits([
    'update:modelValue',
    'ver-historico',
    'liquidar',
    'ver-plan-pagos',
    'descargar-paz-salvo',
    'imprimir',
    'imprimir-abono',
])

const cuotasCols = [
    'Cuota',
    'Fecha límite cuota',
    'Valor abonado',
    'Saldo',
    'Estado',
    'Interés moratorio',
    'Gastos cobranza',
    'Fecha de pago',
]
</script>
