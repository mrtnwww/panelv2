<template>
    <div
        class="bg-white rounded-xl border border-gray-200 overflow-hidden transition-all duration-200"
    >
        <!-- Header -->
        <button
            type="button"
            @click="toggle"
            class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50/60 transition-colors"
        >
            <div class="w-full flex items-center justify-between gap-3">
                <div class="flex items-center justify-between gap-3">
                    <!-- Número de paso -->
                    <span
                        class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-semibold shrink-0"
                        :class="
                            isOpen
                                ? 'bg-[#1a5c2a] text-white'
                                : 'bg-gray-100 text-gray-400'
                        "
                    >
                        {{ step }}
                    </span>
                    <span
                        class="text-md font-semibold text-[#0A2540] xl:text-lg"
                        >{{ title }}</span
                    >
                </div>

                <!-- Badge completado -->
                <span
                    v-if="completed"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700"
                >
                    <i class="fa-solid fa-check"></i>
                    Completado
                </span>
            </div>

            <!-- Chevron -->
            <svg
                width="16"
                height="16"
                viewBox="0 0 16 16"
                fill="none"
                :class="[
                    'transition-transform duration-200 text-gray-400 shrink-0',
                    isOpen ? 'rotate-180' : '',
                ]"
            >
                <path
                    d="M4 6L8 10L12 6"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linecap="round"
                />
            </svg>
        </button>

        <!-- Contenido -->
        <div v-show="isOpen" class="border-t border-gray-100">
            <div class="px-5 py-5">
                <slot />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
    title: { type: String, required: true },
    step: { type: Number, required: true },
    completed: { type: Boolean, default: false },
    open: { type: Boolean, default: false },
})

const isOpen = ref(props.open)

function toggle() {
    isOpen.value = !isOpen.value
}

defineExpose({ isOpen })
</script>
