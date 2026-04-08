<template>
    <div class="p-6">
        <div class="mb-6">
            <h2 class="text-sm font-bold text-gray-700 uppercase">
                Configuración de centrales de riesgo
            </h2>
            <p class="text-[11px] text-gray-500 italic">
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
                    <input
                        type="number"
                        v-model="form.min_score"
                        placeholder="Digita el puntaje mínimo"
                        class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2 text-sm text-center outline-none focus:border-[#1a5c2a]"
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
                    <input
                        type="number"
                        v-model="form.meses_al_dia"
                        placeholder="Número de meses al día"
                        class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2 text-sm text-center outline-none focus:border-[#1a5c2a]"
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
                    <input
                        type="number"
                        v-model="form.capacidad_endeudamiento"
                        placeholder="Digita el porcentaje"
                        class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2 text-sm text-center outline-none focus:border-[#1a5c2a]"
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
                    <input
                        type="number"
                        v-model="form.ingresos_minimos"
                        placeholder="Digita los ingresos mínimos"
                        class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2 text-sm text-center outline-none focus:border-[#1a5c2a]"
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
                            <span class="text-xs text-gray-600 w-24"
                                >Máximo</span
                            >
                            <input
                                type="number"
                                v-model="form.huellas_maximas"
                                placeholder="Número de huellas"
                                class="flex-1 bg-white border border-gray-200 rounded-lg px-4 py-1.5 text-sm text-center outline-none focus:border-[#1a5c2a]"
                            />
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-xs text-gray-600 w-24"
                                >En los últimos</span
                            >
                            <input
                                type="number"
                                v-model="form.huellas_meses"
                                placeholder="Número de meses"
                                class="flex-1 bg-white border border-gray-200 rounded-lg px-4 py-1.5 text-sm text-center outline-none focus:border-[#1a5c2a]"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <button @click="saveConfig" class="btn btn-main">
                Actualizar parámetros
            </button>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue'
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
        // Aquí iría tu llamada a la API
        // await api.post('/api/config-centrales', form)
        alert('Configuración actualizada correctamente')
    } catch (err) {
        console.error(err)
    }
}
</script>

<style scoped>
/* Para quitar las flechas del input type number en Chrome/Safari/Edge */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Firefox */
input[type='number'] {
    -moz-appearance: textfield;
}
</style>
