<template>
    <div class="relative">
        <select
            :value="modelValue"
            @change="$emit('update:modelValue', $event.target.value)"
            :disabled="disabled"
            :class="[
                'w-full h-9 pl-3 pr-8 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600',
                'outline-none appearance-none cursor-pointer',
                'focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10 transition-all',
                disabled ? 'opacity-50 cursor-not-allowed' : '',
            ]"
        >
            <option value="">{{ placeholder }}</option>
            <option v-for="opt in options" :key="opt.value" :value="opt.value">
                {{ opt.label }}
            </option>
        </select>
        <span
            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"
        >
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                <path
                    d="M3 4.5L6 7.5L9 4.5"
                    stroke="currentColor"
                    stroke-width="1.3"
                    stroke-linecap="round"
                />
            </svg>
        </span>
    </div>
</template>

<script setup>
defineProps({
    modelValue: { type: String, default: '' },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Seleccione' },
    disabled: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])
</script>
