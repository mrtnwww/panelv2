<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">Informe ventas</h1>

        <!-- Panel de filtros -->
        <div
            class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-5"
        >
            <!-- Fila 1 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <FormInput
                    label="Mes de reporte"
                    type="month"
                    v-model="filters.mesReporte"
                />
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
            </div>

            <!-- Botones de informe -->
            <div
                class="flex flex-col items-start justify-end gap-3 pt-1 border-t border-gray-100 xl:flex-row xl:items-end"
            >
                <button
                    @click="generarInforme('resumido')"
                    :disabled="loadingInforme === 'resumido'"
                    class="h-9 px-5 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] disabled:bg-gray-300 text-white text-sm font-medium transition-all flex items-center gap-2"
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
                    Generar informe de ventas
                </button>
                <button
                    @click="generarInforme('detallado')"
                    :disabled="loadingInforme === 'detallado'"
                    class="h-9 px-4 rounded-lg bg-emerald-500 hover:bg-emerald-600 disabled:bg-gray-300 text-white text-sm font-medium transition-all flex items-center gap-2"
                >
                    <svg
                        v-if="loadingInforme === 'detallado'"
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
                    Generar informe aliados
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue'

// -- Componentes -----------------------------------------------------------
import FormInput from '@/components/form/FormInput.vue'

// -- Loader ----------------------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

import api from '@/services/api'

// -- Estado ----------------------------------------------------------------
const abonos = ref([])
const loadingInforme = ref(null)

// -- Filtros --------------------------------------------------------------
const filters = reactive({
    fechaInicial: '',
    fechaFinal: '',
    mesReporte: '',
})

function authHeaders() {
    return {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    }
}

// -- Backend ----------------------------------------------------------
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
</script>
