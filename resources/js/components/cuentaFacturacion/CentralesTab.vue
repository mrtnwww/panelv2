<template>
    <div class="p-6">
        <div class="mb-6">
            <p class="text-xs text-gray-500 italic">
                * Establece los parámetros a tener en cuenta al momento de
                realizar el análisis y consulta en centrales de riesgo de un
                nuevo cliente.
            </p>
        </div>

        <div class="border border-[#1a5c2a] rounded-xl p-5 bg-white">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="bg-gray-100/80 rounded-xl p-4 flex flex-col gap-2">
                    <label class="text-sm font-bold text-gray-700"
                        >Score - puntaje</label
                    >
                    <p class="text-[11px] text-gray-500">
                        Digita un número de 0 a 950 que defina el puntaje mínimo
                        que debe tener el cliente.
                    </p>
                    <FormInput
                        type="number"
                        v-model="form.min_score"
                        placeholder="Digita el puntaje mínimo"
                    />
                </div>

                <div class="bg-gray-100/80 rounded-xl p-4 flex flex-col gap-2">
                    <label class="text-sm font-bold text-gray-700"
                        >Hábito de pago</label
                    >
                    <p class="text-[11px] text-gray-500">
                        El número de meses hacia atrás en los que el cliente no
                        debió haber incurrido EN MORA.
                    </p>
                    <FormInput
                        type="number"
                        v-model="form.meses_al_dia"
                        placeholder="Número de meses al día"
                    />
                </div>

                <div class="bg-gray-100/80 rounded-xl p-4 flex flex-col gap-2">
                    <label class="text-sm font-bold text-gray-700"
                        >Capacidad de endeudamiento</label
                    >
                    <p class="text-[11px] text-gray-500">
                        El porcentaje de capacidad de endeudamiento que debe
                        tener el cliente.
                    </p>
                    <FormInput
                        type="number"
                        v-model="form.capacidad_endeudamiento"
                        placeholder="Digita el porcentaje"
                    />
                </div>

                <div class="bg-gray-100/80 rounded-xl p-4 flex flex-col gap-2">
                    <label class="text-sm font-bold text-gray-700"
                        >Ingresos</label
                    >
                    <p class="text-[11px] text-gray-500">
                        Los ingresos mínimos que el cliente debe devengar al
                        mes.
                    </p>
                    <FormInput
                        type="number"
                        v-model="form.ingresos_minimos"
                        placeholder="Digita los ingresos mínimos"
                    />
                </div>

                <div
                    class="bg-gray-100/80 rounded-xl p-4 flex flex-col gap-3 md:col-span-1"
                >
                    <div>
                        <label class="text-sm font-bold text-gray-700"
                            >Huellas de consulta</label
                        >
                        <p class="text-[11px] text-gray-500 leading-tight">
                            El número máximo de huellas de consulta que el
                            cliente debe tener en los últimos meses
                            establecidos.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center gap-4">
                            <FormInput
                                type="number"
                                label="Máximo"
                                v-model="form.huellas_maximas"
                                placeholder="Número de huellas"
                                :wrapper-class="'w-full'"
                            />
                            <FormInput
                                type="number"
                                label="En los últimos"
                                v-model="form.huellas_meses"
                                placeholder="Número de meses"
                                :wrapper-class="'w-full'"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <button @click="saveConfig" class="btn btn-main" disabled="true">
                Actualizar parámetros
            </button>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue'

// -- Componentes ----------------------------------------------
import FormInput from '@/components/form/FormInput.vue'

import api from '@/services/api'

const form = reactive({
    min_score: null,
    meses_al_dia: null,
    capacidad_endeudamiento: null,
    ingresos_minimos: null,
    huellas_maximas: null,
    huellas_meses: null,
})

async function saveConfig() {
    try {
        // TODO
    } catch (err) {
        console.error(err)
    }
}
</script>
