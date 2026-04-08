<template>
    <div class="p-6">
        <div class="mb-6 max-w-sm">
            <div class="relative">
                <input
                    type="text"
                    v-model="searchQuery"
                    placeholder="Buscar función..."
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm pl-10 focus:ring-2 focus:ring-green-500/20 focus:border-[#1a5c2a] outline-none transition-all"
                />
                <i
                    class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-gray-400 text-xs"
                ></i>
            </div>
        </div>

        <div class="border rounded-xl overflow-hidden shadow-sm bg-white">
            <div class="overflow-y-auto max-h-[600px]">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-gray-50 sticky top-0 z-10 shadow-sm">
                        <tr class="text-gray-600 font-bold border-b">
                            <th class="px-4 py-4 w-1/4">
                                Nombre de la función
                            </th>
                            <th class="px-4 py-4 text-center w-32">
                                Activar/Inactivar
                            </th>
                            <th class="px-4 py-4 text-center w-24">Acciones</th>
                            <th class="px-4 py-4">Descripción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="funcion in filteredFunciones"
                            :key="funcion.id"
                            class="hover:bg-gray-50 transition-colors"
                        >
                            <td
                                class="px-4 py-3 font-medium text-gray-700 uppercase tracking-tight"
                            >
                                {{ funcion.nombre }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <label
                                    class="relative inline-flex items-center cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="funcion.activa"
                                        class="sr-only peer"
                                    />
                                    <div
                                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1a5c2a]"
                                    ></div>
                                </label>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button
                                    v-if="funcion.hasConfig"
                                    class="text-green-700 hover:text-green-900"
                                >
                                    <i class="fa-solid fa-table-cells"></i>
                                </button>
                            </td>
                            <td class="px-4 py-3 text-gray-500 leading-relaxed">
                                {{ funcion.descripcion }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

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

<style scoped>
/* Estilo para el scrollbar para que sea más sutil */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}
.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 10px;
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #999;
}
</style>
