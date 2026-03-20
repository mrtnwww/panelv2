<template>
    <div class="flex flex-col gap-1.5">
        <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">
            {{ label }}
        </label>
        <slot />
        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
    </div>
</template>

<script setup>
defineProps({
    label: String,
    error: String
});
</script>