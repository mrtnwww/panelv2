<template>
    <div class="flex items-center justify-between gap-3" :class="wrapperClass">
        <!-- Label lado izquierdo -->
        <div v-if="label || hint" class="flex flex-col gap-0.5">
            <span
                class="text-xs font-medium text-gray-500 uppercase tracking-wide"
            >
                {{ label }}
                <span v-if="required" class="text-red-400">*</span>
            </span>
            <span v-if="hint" class="text-xs text-gray-400">{{ hint }}</span>
        </div>

        <!-- Toggle -->
        <button
            type="button"
            role="switch"
            :aria-checked="modelValue"
            :disabled="disabled"
            @click="toggle"
            :class="[
                'relative inline-flex shrink-0 rounded-full border-2 border-transparent',
                'transition-colors duration-200 ease-in-out focus:outline-none',
                'focus:ring-2 focus:ring-[#1a5c2a]/20 focus:ring-offset-1',
                sizeTrack,
                modelValue ? activeColor : 'bg-gray-200',
                disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
            ]"
        >
            <!-- Círculo deslizante -->
            <span
                :class="[
                    'pointer-events-none inline-block rounded-full bg-white shadow-sm',
                    'transform transition-transform duration-200 ease-in-out',
                    sizeThumb,
                    modelValue ? translateOn : 'translate-x-0',
                ]"
            />
        </button>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    modelValue: { type: Boolean, default: undefined },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    wrapperClass: { type: String, default: '' },
    // 'sm' | 'md' (default) | 'lg'
    size: { type: String, default: 'md' },
    // Color activo personalizable
    color: { type: String, default: 'green' },
})

const emit = defineEmits(['update:modelValue', 'change'])

const internalValue = ref(false)

function toggle() {
    if (props.disabled) return
    isOn.value = !isOn.value
}

// Tamaños del track (fondo)
const sizeTrack = computed(
    () =>
        ({
            sm: 'w-8 h-4',
            md: 'w-11 h-6',
            lg: 'w-14 h-7',
        })[props.size] ?? 'w-11 h-6'
)

// Tamaños del thumb (círculo)
const sizeThumb = computed(
    () =>
        ({
            sm: 'h-3 w-3',
            md: 'h-5 w-5',
            lg: 'h-6 w-6',
        })[props.size] ?? 'h-5 w-5'
)

// Desplazamiento cuando está activo
const translateOn = computed(
    () =>
        ({
            sm: 'translate-x-4',
            md: 'translate-x-5',
            lg: 'translate-x-7',
        })[props.size] ?? 'translate-x-5'
)

// Color del track activo
const activeColor = computed(
    () =>
        ({
            green: 'bg-[#1a5c2a]',
            blue: 'bg-blue-500',
            red: 'bg-red-500',
        })[props.color] ?? 'bg-[#1a5c2a]'
)

const isOn = computed({
    get: () =>
        props.modelValue !== undefined ? props.modelValue : internalValue.value,
    set: val => {
        if (props.modelValue !== undefined) {
            emit('update:modelValue', val)
        } else {
            internalValue.value = val
        }
        emit('change', val)
    },
})
</script>
