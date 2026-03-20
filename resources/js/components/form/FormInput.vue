<template>
    <div class="flex flex-col gap-1.5">
        <label
            v-if="label"
            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
        >
            {{ label }} <span v-if="required" class="text-red-400">*</span>
        </label>

        <slot>
            <!-- Select -->
            <div v-if="type === 'select'" class="relative">
                <select
                    :value="modelValue"
                    @change="$emit('update:modelValue', $event.target.value)"
                    :required="required"
                    :disabled="disabled"
                    :class="[fieldClass, 'appearance-none pr-8 cursor-pointer']"
                >
                    <option value="">
                        {{ placeholder || 'Seleccione...' }}
                    </option>
                    <option
                        v-for="opt in options"
                        :key="opt.value"
                        :value="opt.value"
                    >
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

            <!-- Textarea -->
            <textarea
                v-else-if="type === 'textarea'"
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                :rows="rows"
                :class="[fieldClass, 'resize-none h-auto py-2.5']"
            />

            <!-- Input normal -->
            <input
                v-else
                :type="type"
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                :class="fieldClass"
            />
        </slot>

        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
        <p v-if="hint" class="text-xs text-gray-400">{{ hint }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    label: { type: String, default: '' },
    type: { type: String, default: 'text' },
    modelValue: { default: '' },
    placeholder: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    options: { type: Array, default: () => [] },
    rows: { type: Number, default: 3 },
})

defineEmits(['update:modelValue'])

const fieldClass = computed(() => [
    'w-full h-10 px-3 rounded-lg border bg-gray-50 text-[#0A2540] text-sm outline-none transition-all',
    'placeholder:text-gray-300',
    'focus:bg-white focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10',
    props.error ? 'border-red-400' : 'border-gray-200 hover:border-gray-300',
    props.disabled ? 'opacity-50 cursor-not-allowed bg-gray-100' : '',
])
</script>
