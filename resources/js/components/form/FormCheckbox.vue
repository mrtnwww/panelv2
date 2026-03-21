<template>
    <label
        :class="[
            'inline-flex items-center gap-2 cursor-pointer',
            disabled && 'opacity-50 cursor-not-allowed',
            wrapperClass,
        ]"
    >
        <div class="relative shrink-0">
            <input
                type="checkbox"
                :checked="isChecked"
                :disabled="disabled"
                :indeterminate="indeterminate"
                @change="onChange"
                class="peer sr-only"
            />
            <div
                :class="[
                    'flex items-center justify-center rounded border-2 transition-all',
                    sizeClass,
                    indeterminate
                        ? 'bg-[#1a5c2a] border-[#1a5c2a]'
                        : isChecked
                          ? 'bg-[#1a5c2a] border-[#1a5c2a]'
                          : 'bg-gray-50 border-gray-200 hover:border-gray-300',
                ]"
            >
                <!-- Check -->
                <svg
                    v-if="isChecked && !indeterminate"
                    viewBox="0 0 8 8"
                    fill="none"
                    :class="iconSizeClass"
                >
                    <path
                        d="M1 4L3 6.5L7 1.5"
                        stroke="white"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>

                <!-- Indeterminate (guión) -->
                <svg
                    v-else-if="indeterminate"
                    viewBox="0 0 8 8"
                    fill="none"
                    :class="iconSizeClass"
                >
                    <path
                        d="M1.5 4h5"
                        stroke="white"
                        stroke-width="1.5"
                        stroke-linecap="round"
                    />
                </svg>
            </div>
        </div>

        <span
            v-if="label"
            class="text-sm text-gray-600 select-none leading-tight"
        >
            {{ label }}
        </span>
    </label>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    // Sin type — acepta Boolean (modo simple) o Array (modo múltiple)
    // Modo booleano: v-model="form.activo"
    // Modo array:    v-model="form.roles" + :value="'admin'"
    modelValue: { default: false },
    value: { default: undefined },
    label: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    indeterminate: { type: Boolean, default: false },
    size: { type: String, default: 'md' },
    wrapperClass: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

// ── Modo ───────────────────────────────────────────────────────────────────
const isArrayMode = computed(() => Array.isArray(props.modelValue))

const isChecked = computed(() => {
    if (isArrayMode.value) return props.modelValue.includes(props.value)
    return !!props.modelValue
})

// ── onChange ───────────────────────────────────────────────────────────────
function onChange(e) {
    if (isArrayMode.value) {
        const next = [...props.modelValue]
        if (e.target.checked) {
            if (!next.includes(props.value)) next.push(props.value)
        } else {
            const idx = next.indexOf(props.value)
            if (idx !== -1) next.splice(idx, 1)
        }
        emit('update:modelValue', next)
    } else {
        emit('update:modelValue', e.target.checked)
    }
}

// ── Clases ─────────────────────────────────────────────────────────────────
const sizeClass = computed(
    () =>
        ({
            sm: 'w-3.5 h-3.5',
            md: 'w-4 h-4',
        })[props.size] ?? 'w-4 h-4'
)

const iconSizeClass = computed(
    () =>
        ({
            sm: 'w-2 h-2',
            md: 'w-2.5 h-2.5',
        })[props.size] ?? 'w-2.5 h-2.5'
)
</script>
