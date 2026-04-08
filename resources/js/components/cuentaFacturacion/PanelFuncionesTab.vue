<template>
    <div class="p-6">
        <div class="mb-6 max-w-sm">
            <div class="relative">
                <FormInput
                    :model-value="searchQuery"
                    placeholder="Buscar función..."
                    size="lg"
                    icon-left="search"
                    wrapper-class="w-full sm:w-full"
                    @update:model-value="$emit('update:search', $event)"
                />
            </div>
        </div>

        <TableGrid :items="[]" :columns="cols">
            <template #cell(acciones)="{ item }">
                <div class="flex justify-center">
                    <button class="btn btn-danger">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </template>
        </TableGrid>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// -- Componentes ---------------------------------------------------
import FormInput from '@/components/form/FormInput.vue'
import TableGrid from '@/components/TableGrid.vue'

const searchQuery = ref('')

const funciones = ref([
    {
        id: 1,
        nombre: 'Comisiones asesores',
        activa: false,
        hasConfig: false,
        descripcion:
            'Activar esta función para que, al generar un crédito a través de un asesor, el sistema cargue automáticamente la comisión correspondiente a dicho asesor.',
    },
    {
        id: 2,
        nombre: 'Restringir pagos por App en caso de mora',
        activa: true,
        hasConfig: false,
        descripcion:
            'Se puede activar esta función para que los usuarios no puedan realizar pagos a través de la aplicación si presentan un saldo pendiente o están en situación de mora.',
    },
    {
        id: 3,
        nombre: 'Fotografía obligatoria',
        activa: true,
        hasConfig: false,
        descripcion:
            'Habilitar esta opción para que, al momento de diligenciar el formulario de crédito para un cliente, el sistema solicite una fotografía obligatoria.',
    },
    {
        id: 4,
        nombre: 'OTP crear crédito',
        activa: true,
        hasConfig: false,
        descripcion:
            'Activar esta función para que sea necesario confirmar el código OTP enviado al correo del cliente, cuando se vaya a generar un nuevo crédito',
    },
    {
        id: 5,
        nombre: 'Destino crédito/Crear cliente',
        activa: true,
        hasConfig: true,
        descripcion:
            'Activar esta función para que al momento de crear un cliente se permita seleccionar el destino del crédito que se vaya a solicitar...',
    },
    {
        id: 6,
        nombre: 'Actualización consulta en centrales',
        activa: true,
        hasConfig: true,
        descripcion:
            'Activa esta función para requerir una nueva evaluación del historial crediticio del cliente antes de colocar nuevos créditos.',
    },
    // ... añadir el resto según sea necesario
])

const cols = [
    { key: 'nombre', label: 'Nombre de la función' },
    { key: 'activar', label: 'Activar/Inactivar' },
    { key: 'acciones', label: 'Acciones' },
    { key: 'descripcion', label: 'Descripción' },
]

const filteredFunciones = computed(() => {
    return funciones.value.filter(
        f =>
            f.nombre.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            f.descripcion
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase())
    )
})
</script>
