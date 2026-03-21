<template>
    <label class="flex items-center gap-1.5 cursor-pointer shrink-0 w-fit">
        <div class="relative shrink-0">
            <input
                :checked="modelValue"
                @change="$emit('update:modelValue', $event.target.checked)"
                type="checkbox"
                class="peer sr-only"
            />
            <div
                class="w-4 h-4 rounded border-2 border-gray-200 bg-gray-50 peer-checked:bg-[#1a5c2a] peer-checked:border-[#1a5c2a] transition-all flex items-center justify-center"
            >
                <svg
                    v-if="modelValue"
                    width="8"
                    height="8"
                    viewBox="0 0 8 8"
                    fill="none"
                >
                    <path
                        d="M1 4L3 6.5L7 1.5"
                        stroke="white"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </div>
        </div>
        <span class="text-xs text-gray-400 whitespace-nowrap select-none">{{
            label
        }}</span>
    </label>
</template>

<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    label: { type: String, default: '' },
})

defineEmits(['update:modelValue'])
</script>
