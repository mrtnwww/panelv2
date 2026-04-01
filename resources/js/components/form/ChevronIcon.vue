<template>
    <span
        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none transition-transform duration-200"
        :class="{ 'rotate-180': dropdownOpen }"
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
</template>

<script setup>
defineProps({
    dropdownOpen: {
        type: Boolean,
        default: false,
    },
})
</script>
