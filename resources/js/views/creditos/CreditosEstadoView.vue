<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">Estado de crédito</h1>

        <!-- Panel de búsqueda -->
        <div
            class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-3"
        >
            <label
                class="text-xs font-medium text-gray-400 uppercase tracking-wide"
            >
                Buscar cliente
            </label>
            <div class="relative">
                <select
                    v-model="clienteId"
                    :class="[inputClass, 'appearance-none pr-8 cursor-pointer']"
                >
                    <option value="">Seleccione el cliente a revisar</option>
                    <option
                        v-for="c in clientes"
                        :key="c.value"
                        :value="c.value"
                    >
                        {{ c.label }}
                    </option>
                </select>
                <ChevronIcon />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import ChevronIcon from '@/components/ChevronIcon.vue'

// ── Estado ─────────────────────────────────────────────────────────────────
const clienteId = ref('')

// ── Opciones de clientes ───────────────────────────────────────────────────
const clientes = [
    { value: '1', label: 'CAROLINA RINCON DELGADO (63555661)' },
    { value: '2', label: 'CARLOS ANDRES GOMEZ (10345678)' },
    { value: '3', label: 'MARIA FERNANDA LOPEZ (52345678)' },
]

// ── Helpers ────────────────────────────────────────────────────────────────
const inputClass =
    'w-full h-9 px-3 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600 outline-none focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10 transition-all'
</script>
