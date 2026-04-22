<template>
    <AppModal
        :model-value="modelValue"
        title="Nueva tarea"
        size="lg"
        :show-footer="true"
        cancel-label="Cancelar"
        confirm-label="Crear tarea"
        :confirm-loading="loading"
        :close-on-overlay="true"
        @update:modelValue="$emit('update:modelValue', $event)"
        @confirm="$emit('confirm')"
    >
        <div class="flex flex-col gap-4">
            <FormInput
                label="Titulo"
                v-model="modal.titulo"
                placeholder="Ej: Gestionar cartera vencida"
                required
            />

            <FormInput
                label="Tipo"
                type="select"
                v-model="modal.tipoTarea"
                :options="tipoTarea"
                placeholder="Seleccione el tipo de tarea"
                required
            />

            <FormInput
                label="Prioridad"
                type="select"
                v-model="modal.prioridad"
                :options="prioridad"
                placeholder="Seleccione el tipo de tarea"
                required
            />

            <FormInput
                label="Asignado a"
                type="select"
                v-model="modal.usuarioAsignado"
                :options="usuarios"
                placeholder="Seleccione el usuario"
                :searchable="true"
            />

            <FormInput
                label="Fecha vencimiento"
                type="date"
                v-model="modal.vencimiento"
                required
            />

            <FormInput
                label="Notas"
                type="textarea"
                v-model="modal.nota"
                required
            />

            <transition name="fade">
                <div
                    v-if="error"
                    class="px-3 py-2.5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"
                >
                    {{ error }}
                </div>
            </transition>
        </div>
    </AppModal>
</template>

<script setup>
// -- Componentes -----------------------------------
import FormSelectAsync from '@/components/form/FormSelectAsync.vue'
import FormInput from '@/components/form/FormInput.vue'
import AppModal from '@/components/AppModal.vue'

defineProps({
    modelValue: { type: Boolean, default: false },
    tipoTarea: { type: Array, default: () => [] },
    prioridad: { type: Array, default: () => [] },
    usuarios: { type: Array, default: () => [] },
    loading: { type: Boolean, required: false },
    modal: { type: Object, required: true },
    error: { type: String, default: '' },
})

defineEmits(['update:modelValue', 'confirm'])
</script>
