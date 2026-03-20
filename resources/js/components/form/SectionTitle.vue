<template>
    <div class="flex items-center gap-3">
        <span
            class="text-xs font-semibold text-emerald-600 uppercase tracking-widest whitespace-nowrap"
        >
            {{ title }}
        </span>
        <div class="flex-1 h-px bg-gray-100" />
    </div>
</template>

<script setup>
defineProps({
    title: String,
});
</script>
