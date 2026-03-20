<template>
    <div
        :class="[
            'px-3 mb-1 transition-all duration-300 overflow-hidden',
            collapsed ? 'h-0 opacity-0' : 'h-5 opacity-100',
        ]"
    >
        <span
            class="text-white/30 text-[13px] font-semibold uppercase tracking-widest"
        >
            {{ label }}
        </span>
    </div>
</template>

<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    collapsed: {
        type: Boolean,
        default: false,
    },
});
</script>
