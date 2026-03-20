<template>
    <p
        v-show="!collapsed"
        class="px-3 pt-3 pb-1 text-white/30 text-[10px] font-semibold uppercase tracking-widest whitespace-nowrap xl:text-[13px]"
    >
        {{ label }}
    </p>
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
})
</script>
