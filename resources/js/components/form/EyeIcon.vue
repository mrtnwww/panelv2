<template>
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
        <template v-if="open">
            <ellipse
                cx="8"
                cy="8"
                rx="6.5"
                ry="4"
                stroke="currentColor"
                stroke-width="1.2"
            />
            <circle
                cx="8"
                cy="8"
                r="2"
                stroke="currentColor"
                stroke-width="1.2"
            />
        </template>
        <template v-else>
            <path
                d="M2 2L14 14"
                stroke="currentColor"
                stroke-width="1.2"
                stroke-linecap="round"
            />
            <path
                d="M6.5 4.5C7 4.2 7.5 4 8 4c3 0 5.5 4 5.5 4s-.7 1.4-2 2.5"
                stroke="currentColor"
                stroke-width="1.2"
                stroke-linecap="round"
            />
            <path
                d="M3.5 6C2.5 7 1.5 8 1.5 8s2.5 4 6.5 4c.8 0 1.6-.2 2.3-.5"
                stroke="currentColor"
                stroke-width="1.2"
                stroke-linecap="round"
            />
        </template>
    </svg>
</template>

<script setup>
defineProps({
    open: Boolean,
})
</script>
