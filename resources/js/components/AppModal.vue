<template>
    <transition name="modal">
        <div
            v-if="modelValue"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
            @click.self="onOverlayClick"
        >
            <div
                :class="[
                    'bg-white rounded-2xl flex flex-col shadow-xl w-full max-h-[90vh]',
                    sizeClass,
                ]"
            >
                <!-- Header -->
                <div
                    v-if="title || $slots.header"
                    class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0"
                >
                    <slot name="header">
                        <div>
                            <p class="text-base font-semibold text-[#0A2540]">
                                {{ title }}
                            </p>
                            <p
                                v-if="subtitle"
                                class="text-xs text-gray-400 mt-0.5"
                            >
                                {{ subtitle }}
                            </p>
                        </div>
                    </slot>

                    <button
                        v-if="closable"
                        @click="$emit('update:modelValue', false)"
                        class="text-gray-300 hover:text-gray-500 transition-colors ml-4 shrink-0"
                        aria-label="Cerrar"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Body -->
                <div
                    :class="[
                        'flex-1 overflow-y-auto',
                        noPadding ? '' : 'px-6 py-5',
                    ]"
                >
                    <slot />
                </div>

                <!-- Footer -->
                <div
                    v-if="$slots.footer"
                    class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-100 shrink-0"
                >
                    <slot name="footer" />
                </div>

                <!-- Footer por defecto con botones confirm/cancel -->
                <div
                    v-else-if="showFooter"
                    class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-100 shrink-0"
                >
                    <button
                        @click="$emit('update:modelValue', false)"
                        class="btn btn-default"
                    >
                        {{ cancelLabel }}
                    </button>
                    <button
                        @click="$emit('confirm')"
                        :disabled="confirmLoading || confirmDisabled"
                        :class="[
                            'btn btn-main',
                            confirmVariant === 'danger'
                                ? 'bg-red-500 hover:bg-red-600 disabled:bg-gray-300'
                                : 'bg-[#1a5c2a] hover:bg-[#154d22] disabled:bg-gray-300',
                        ]"
                    >
                        <svg
                            v-if="confirmLoading"
                            class="animate-spin w-3.5 h-3.5"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                            />
                        </svg>
                        {{ confirmLabel }}
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    // Control de visibilidad
    modelValue: { type: Boolean, default: false },

    // Contenido del header
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },

    // Tamaño: 'sm' | 'md' | 'lg' | 'xl' | 'full'
    size: { type: String, default: 'md' },

    // Comportamiento
    closable: { type: Boolean, default: true },
    closeOnOverlay: { type: Boolean, default: true },
    noPadding: { type: Boolean, default: false },

    // Footer por defecto
    showFooter: { type: Boolean, default: false },
    cancelLabel: { type: String, default: 'Cancelar' },
    confirmLabel: { type: String, default: 'Confirmar' },
    confirmLoading: { type: Boolean, default: false },
    confirmDisabled: { type: Boolean, default: false },
    confirmVariant: { type: String, default: 'primary' }, // 'primary' | 'danger'
})

const emit = defineEmits(['update:modelValue', 'confirm', 'close'])

const sizeClass = computed(
    () =>
        ({
            sm: 'max-w-sm',
            md: 'max-w-md',
            lg: 'max-w-lg',
            xl: 'max-w-2xl',
            full: 'max-w-4xl',
        })[props.size] ?? 'max-w-md'
)

function onOverlayClick() {
    if (!props.closeOnOverlay) return
    emit('update:modelValue', false)
    emit('close')
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.15s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
