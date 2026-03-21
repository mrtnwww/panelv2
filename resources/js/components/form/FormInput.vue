<template>
    <div class="flex flex-col gap-1.5" :class="wrapperClass">
        <label
            v-if="label"
            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
        >
            {{ label }} <span v-if="required" class="text-red-400">*</span>
        </label>

        <slot>
            <!-- ── Select ── -->
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

            <!-- ── Textarea ── -->
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

            <!-- ── File ── -->
            <div v-else-if="type === 'file'">
                <!-- Sin archivo: botón + placeholder -->
                <div v-if="!fileName" class="flex items-center gap-2">
                    <label
                        :class="[
                            'flex items-center gap-1.5 cursor-pointer rounded-lg border border-gray-200',
                            'bg-gray-50 hover:bg-gray-100 text-gray-600 transition-all select-none flex-shrink-0',
                            heightClass,
                            'px-3',
                        ]"
                    >
                        <svg
                            width="13"
                            height="13"
                            viewBox="0 0 13 13"
                            fill="none"
                        >
                            <path
                                d="M6.5 8.5V1M4 3.5L6.5 1L9 3.5"
                                stroke="currentColor"
                                stroke-width="1.3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M1 9.5v1A1.5 1.5 0 0 0 2.5 12h8A1.5 1.5 0 0 0 12 10.5v-1"
                                stroke="currentColor"
                                stroke-width="1.3"
                                stroke-linecap="round"
                            />
                        </svg>
                        <span class="text-xs font-medium whitespace-nowrap">{{
                            buttonLabel
                        }}</span>
                        <input
                            ref="fileRef"
                            type="file"
                            :accept="accept"
                            :multiple="multiple"
                            :disabled="disabled"
                            class="sr-only"
                            @change="onFileChange"
                        />
                    </label>
                    <span class="text-xs text-gray-400 truncate">
                        {{ placeholder || 'Ningún archivo seleccionado' }}
                    </span>
                </div>

                <!-- Con archivo: chip + botón quitar + cambiar -->
                <div v-else class="flex items-center gap-2">
                    <div
                        class="flex items-center gap-2 px-3 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 text-xs font-medium flex-1 min-w-0"
                        :class="heightClass"
                    >
                        <svg
                            width="13"
                            height="13"
                            viewBox="0 0 13 13"
                            fill="none"
                            class="flex-shrink-0"
                        >
                            <path
                                d="M8 1H3a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h7a1 1 0 0 0 1-1V4L8 1Z"
                                stroke="currentColor"
                                stroke-width="1.2"
                            />
                            <path
                                d="M8 1v3h3"
                                stroke="currentColor"
                                stroke-width="1.2"
                            />
                            <path
                                d="M4 7h5M4 9h3"
                                stroke="currentColor"
                                stroke-width="1.2"
                                stroke-linecap="round"
                            />
                        </svg>
                        <span class="truncate">{{ fileName }}</span>
                    </div>

                    <button
                        type="button"
                        @click="clearFile"
                        class="flex-shrink-0 w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-gray-400 hover:text-red-500 hover:border-red-200 transition-all"
                        title="Quitar archivo"
                    >
                        <svg
                            width="12"
                            height="12"
                            viewBox="0 0 12 12"
                            fill="none"
                        >
                            <path
                                d="M2 2L10 10M10 2L2 10"
                                stroke="currentColor"
                                stroke-width="1.3"
                                stroke-linecap="round"
                            />
                        </svg>
                    </button>

                    <label
                        class="flex-shrink-0 flex items-center gap-1 px-3 h-8 rounded-lg border border-gray-200 text-xs text-gray-500 hover:bg-gray-50 cursor-pointer transition-all"
                    >
                        <svg
                            width="11"
                            height="11"
                            viewBox="0 0 12 12"
                            fill="none"
                        >
                            <path
                                d="M6 8.5V1M3.5 3.5L6 1L8.5 3.5"
                                stroke="currentColor"
                                stroke-width="1.3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        Cambiar
                        <input
                            type="file"
                            :accept="accept"
                            :multiple="multiple"
                            :disabled="disabled"
                            class="sr-only"
                            @change="onFileChange"
                        />
                    </label>
                </div>
            </div>

            <!-- ── Input normal con íconos ── -->
            <div v-else class="relative">
                <span
                    v-if="$slots['icon-left'] || iconLeft"
                    class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"
                >
                    <slot name="icon-left">
                        <svg
                            v-if="iconLeft === 'search'"
                            width="13"
                            height="13"
                            viewBox="0 0 13 13"
                            fill="none"
                        >
                            <circle
                                cx="5.5"
                                cy="5.5"
                                r="4"
                                stroke="currentColor"
                                stroke-width="1.3"
                            />
                            <path
                                d="M10 10L8.5 8.5"
                                stroke="currentColor"
                                stroke-width="1.3"
                                stroke-linecap="round"
                            />
                        </svg>
                    </slot>
                </span>

                <input
                    :type="type"
                    :value="modelValue"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :placeholder="placeholder"
                    :required="required"
                    :disabled="disabled"
                    :class="[
                        fieldClass,
                        $slots['icon-left'] || iconLeft ? 'pl-8' : '',
                        $slots['icon-right'] || iconRight ? 'pr-10' : '',
                    ]"
                />

                <span
                    v-if="$slots['icon-right'] || iconRight"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300"
                    :class="$slots['icon-right'] ? '' : 'pointer-events-none'"
                >
                    <slot name="icon-right" />
                </span>
            </div>
        </slot>

        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
        <p v-if="hint" class="text-xs text-gray-400">{{ hint }}</p>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

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

    // Tamaño: 'sm' (h-8) | 'md' (h-10, default) | 'lg' (h-11)
    size: { type: String, default: 'md' },

    // Íconos predefinidos para inputs de texto
    iconLeft: { type: String, default: null },
    iconRight: { type: String, default: null },

    // Clase extra para el wrapper
    wrapperClass: { type: String, default: '' },

    // Props exclusivas de type="file"
    accept: { type: String, default: '*' },
    multiple: { type: Boolean, default: false },
    buttonLabel: { type: String, default: 'Seleccionar archivo' },
})

const emit = defineEmits(['update:modelValue', 'change'])

// ── File state ─────────────────────────────────────────────────────────────
const fileRef = ref(null)
const fileName = ref('')

function onFileChange(e) {
    const files = e.target.files
    if (!files?.length) return
    const file = props.multiple ? Array.from(files) : files[0]
    fileName.value = props.multiple
        ? Array.from(files)
              .map(f => f.name)
              .join(', ')
        : files[0].name
    emit('update:modelValue', file)
    emit('change', file)
}

function clearFile() {
    fileName.value = ''
    emit('update:modelValue', null)
    emit('change', null)
}

defineExpose({ clearFile })

// ── Clases ─────────────────────────────────────────────────────────────────
const heightClass = computed(
    () =>
        ({
            sm: 'h-8 text-xs',
            md: 'h-10 text-sm',
            lg: 'h-11 text-sm',
            xl: 'h-12 text-base',
        })[props.size] ?? 'h-10 text-sm'
)

const fieldClass = computed(() => [
    'w-full px-3 rounded-lg border bg-gray-50 text-[#0A2540] outline-none transition-all',
    heightClass.value,
    'placeholder:text-gray-300',
    'focus:bg-white focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10',
    props.error ? 'border-red-400' : 'border-gray-200 hover:border-gray-300',
    props.disabled ? 'opacity-50 cursor-not-allowed bg-gray-100' : '',
])
</script>
