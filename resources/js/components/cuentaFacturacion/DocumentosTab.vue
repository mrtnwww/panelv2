<template>
    <div class="p-6">
        <div class="mb-8">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">
                Documentos del sistema
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="doc in docsSistema"
                    :key="doc.label"
                    class="flex items-center justify-between border border-[#1a5c2a] rounded-lg px-4 py-2 bg-white hover:bg-gray-50 transition-colors"
                >
                    <span class="text-xs text-gray-700 font-medium">{{
                        doc.label
                    }}</span>
                    <input
                        type="checkbox"
                        v-model="doc.active"
                        class="rounded text-[#1a5c2a] focus:ring-[#1a5c2a]"
                    />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <div class="space-y-4">
                <div
                    v-for="info in [
                        'Información recibo de caja',
                        'Información notificación e-mail',
                    ]"
                    :key="info"
                    class="flex items-center justify-between border border-[#1a5c2a] rounded-lg px-4 py-2 bg-white"
                >
                    <span class="text-xs text-gray-700">{{ info }}</span>
                    <button class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-pen text-xs"></i>
                    </button>
                </div>
            </div>
            <div class="flex items-start">
                <div
                    class="w-full flex items-center justify-between border border-[#1a5c2a] rounded-lg px-4 py-2 bg-white"
                >
                    <span class="text-xs text-gray-700"
                        >Información de extracto</span
                    >
                    <button class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-pen text-xs"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="border-t pt-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">
                Otros documentos
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="space-y-4">
                    <input
                        type="text"
                        v-model="newDoc.nombre"
                        placeholder="Nombre del documento"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm outline-none focus:border-[#1a5c2a]"
                    />

                    <select
                        v-model="newDoc.tipo"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm text-gray-500 outline-none focus:border-[#1a5c2a]"
                    >
                        <option value="" disabled>
                            Seleccione un tipo de documento
                        </option>
                        <option value="legal">Legal</option>
                        <option value="informativo">Informativo</option>
                    </select>

                    <div class="flex items-center gap-4">
                        <button
                            class="bg-[#007bff] hover:bg-[#0069d9] text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2 transition-colors"
                        >
                            <i class="fa-solid fa-paperclip"></i> Adjuntar
                            documento
                        </button>
                        <label
                            class="flex items-center gap-2 text-xs text-gray-600"
                        >
                            <input
                                type="checkbox"
                                v-model="newDoc.activo"
                                class="rounded"
                            />
                            Activo
                        </label>
                    </div>

                    <button
                        @click="saveOtherDoc"
                        class="bg-[#48bb78] hover:bg-[#38a169] text-white px-10 py-2 rounded-lg font-bold text-sm transition-all mt-4"
                    >
                        Aceptar
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-[11px]">
                        <thead class="text-gray-500 border-b">
                            <tr>
                                <th class="pb-2">Nombre</th>
                                <th class="pb-2">Tipo de documento</th>
                                <th class="pb-2 text-center">Archivo</th>
                                <th class="pb-2 text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-400">
                            <tr v-if="otrosDocs.length === 0">
                                <td
                                    colspan="4"
                                    class="py-8 text-center italic text-gray-300"
                                >
                                    No hay documentos adicionales cargados
                                </td>
                            </tr>
                            <tr v-for="(doc, i) in otrosDocs" :key="i"></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue'

const docsSistema = ref([
    { label: 'Contrato Credigital', active: true },
    { label: 'Términos & Condiciones', active: true },
    { label: 'Autorización tratamiento de datos personales', active: true },
    { label: 'Autorización centrales de riesgo', active: true },
    { label: 'Firma electrónica', active: false },
    { label: 'Aval', active: true },
    { label: 'Uso de datos biométricos', active: false },
])

const newDoc = reactive({
    nombre: '',
    tipo: '',
    activo: true,
})

const otrosDocs = ref([])

const saveOtherDoc = () => {
    console.log('Guardando documento...', newDoc)
}
</script>
