<template>
    <div
        class="bg-gray-50 rounded-xl border border-gray-200 p-4 flex flex-col gap-4"
    >
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span
                    class="w-5 h-5 rounded-full bg-[#1a5c2a]/10 text-[#1a5c2a] text-xs font-semibold flex items-center justify-center"
                >
                    {{ index + 1 }}
                </span>
                <p class="text-sm font-medium text-[#0A2540]">
                    {{ typeLabel }}
                </p>
            </div>
            <span
                class="text-xs text-gray-400 bg-white border border-gray-200 px-2 py-0.5 rounded-full"
            >
                {{ type === 'personal' ? 'Personal' : 'Familiar' }}
            </span>
        </div>

        <!-- Campos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <FormInput
                label="Nombre completo"
                :model-value="modelValue.nombre"
                placeholder="Nombre de la referencia"
                required
                @update:model-value="update('nombre', $event)"
            />
            <FormInput
                label="Teléfono"
                type="tel"
                :model-value="modelValue.telefono"
                placeholder="300 123 4567"
                required
                @update:model-value="update('telefono', $event)"
            />
        </div>

        <FormInput
            label="Nota"
            type="textarea"
            :model-value="modelValue.nota"
            placeholder="Observaciones sobre la referencia..."
            :rows="2"
            @update:model-value="update('nota', $event)"
        />
    </div>
</template>

<script setup>
import FormInput from '@/components/form/FormInput.vue'

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({ nombre: '', telefono: '', nota: '' }),
    },
    index: { type: Number, required: true },
    type: { type: String, default: 'personal' }, // 'personal' | 'familiar'
})

const emit = defineEmits(['update:modelValue'])

const typeLabel =
    {
        personal: 'Referencia personal',
        familiar: 'Referencia familiar',
    }[props.type] ?? 'Referencia'

function update(field, value) {
    emit('update:modelValue', { ...props.modelValue, [field]: value })
}
</script>
