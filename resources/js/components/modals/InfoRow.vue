<template>
    <div class="flex items-center px-4 py-2.5 gap-4">
        <span class="text-xs text-gray-400 w-36 shrink-0">{{ label }}</span>
        <span
            :class="[
                'text-sm flex-1',
                bold ? 'font-semibold text-[#0A2540]' : 'text-gray-700',
                valueClass,
            ]"
        >
            {{ value || '—' }}
        </span>
    </div>
</template>

<script setup>
defineProps({
    label: { type: String, default: '' },
    value: { type: String, default: '' },
    bold: { type: Boolean, default: false },
    valueClass: { type: String, default: '' },
})
</script>
