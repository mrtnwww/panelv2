<template>
    <div class="flex flex-col gap-6">
        <!-- Encabezado -->
        <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
        >
            <div>
                <h1 class="text-xl font-semibold text-[#0A2540]">Dashboard</h1>
                <p class="text-sm text-gray-400 mt-0.5">Resumen de gestión</p>
            </div>

            <!-- Rango de fechas -->
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400">Desde</span>
                    <input
                        type="date"
                        v-model="dateFrom"
                        class="h-9 px-3 text-sm border border-gray-200 rounded-lg bg-white text-gray-600 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15 transition-all"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400">Hasta</span>
                    <input
                        type="date"
                        v-model="dateTo"
                        class="h-9 px-3 text-sm border border-gray-200 rounded-lg bg-white text-gray-600 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15 transition-all"
                    />
                </div>
            </div>
        </div>

        <!-- Próximamente -->
        <div
            class="flex flex-col items-center justify-center py-24 text-center"
        >
            <div
                class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center mb-4"
            >
                <svg
                    width="32"
                    height="32"
                    viewBox="0 0 24 24"
                    fill="none"
                    class="text-emerald-500"
                >
                    <rect
                        x="3"
                        y="3"
                        width="7"
                        height="7"
                        rx="1"
                        stroke="currentColor"
                        stroke-width="1.5"
                    />
                    <rect
                        x="14"
                        y="3"
                        width="7"
                        height="7"
                        rx="1"
                        stroke="currentColor"
                        stroke-width="1.5"
                    />
                    <rect
                        x="3"
                        y="14"
                        width="7"
                        height="7"
                        rx="1"
                        stroke="currentColor"
                        stroke-width="1.5"
                    />
                    <path
                        d="M14 14h7M14 17.5h7M14 21h7"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                    />
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-[#0A2540] mb-1">
                Dashboard en construcción
            </h2>
            <p class="text-sm text-gray-400 max-w-xs">
                Aquí se mostrará el resumen de gestión, métricas de clientes,
                créditos y abonos.
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";

const dateFrom = ref("");
const dateTo = ref("");
</script>
