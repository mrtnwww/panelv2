<template>
    <div class="flex flex-col gap-1.5" :class="wrapperClass">
        <label
            v-if="label"
            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
        >
            {{ label }}
        </label>

        <div
            class="flex gap-3"
            :class="vertical ? 'flex-col' : 'flex-row flex-wrap'"
        >
            <label
                v-for="opt in options"
                :key="opt.value"
                class="flex items-center gap-2.5 cursor-pointer group"
                :class="{ 'opacity-50 cursor-not-allowed': disabled }"
            >
                <div class="relative shrink-0">
                    <input
                        type="radio"
                        :value="opt.value"
                        :checked="modelValue === opt.value"
                        @change="$emit('update:modelValue', opt.value)"
                        :required="required"
                        :disabled="disabled"
                        class="peer sr-only"
                    />
                    <div
                        class="w-4 h-4 rounded-full border-2 border-gray-200 bg-gray-50 peer-checked:border-[#1a5c2a] transition-all flex items-center justify-center"
                    >
                        <div
                            v-if="modelValue === opt.value"
                            class="w-2 h-2 rounded-full bg-[#1a5c2a]"
                        />
                    </div>
                </div>
                <span
                    class="text-sm text-gray-600 group-hover:text-gray-800 transition-colors"
                >
                    {{ opt.label }}
                </span>
            </label>
        </div>

        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
        <p v-if="hint" class="text-xs text-gray-400">{{ hint }}</p>
    </div>
</template>

<script setup>
defineProps({
    label: { type: String, default: '' },
    modelValue: { default: '' },
    options: { type: Array, default: () => [] },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    vertical: { type: Boolean, default: true },
    wrapperClass: { type: String, default: '' },
})

defineEmits(['update:modelValue'])
</script>
