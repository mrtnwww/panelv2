<template>
    <div class="p-6">
        <h2 class="text-sm font-bold text-gray-700 mb-6">
            Facturación Electrónica
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 items-end">
            <div class="space-y-2">
                <label class="text-xs font-semibold text-gray-600 ml-1"
                    >Nombre</label
                >
                <input
                    type="text"
                    v-model="form.nombre"
                    placeholder="Ejemplo: Ziur Software"
                    class="w-full border border-gray-300 rounded-full px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-green-500/20 focus:border-[#1a5c2a] transition-all"
                />
            </div>

            <div class="space-y-2">
                <label
                    class="text-xs font-semibold text-gray-600 ml-1 flex items-center gap-1"
                >
                    Enlace API
                    <i
                        class="fa-solid fa-circle-info text-[10px] text-gray-400 cursor-help"
                    ></i>
                </label>
                <input
                    type="text"
                    v-model="form.enlace"
                    placeholder="Copie y pegue aquí el enlace"
                    class="w-full border border-gray-300 rounded-full px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-green-500/20 focus:border-[#1a5c2a] transition-all"
                />
            </div>

            <div class="space-y-2">
                <label
                    class="text-xs font-semibold text-gray-600 ml-1 flex items-center gap-1"
                >
                    Token
                    <i
                        class="fa-solid fa-circle-info text-[10px] text-gray-400 cursor-help"
                    ></i>
                </label>
                <div class="flex gap-2">
                    <input
                        type="text"
                        v-model="form.token"
                        placeholder="Copie y pegue aquí el token de autorización"
                        class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-green-500/20 focus:border-[#1a5c2a] transition-all"
                    />
                    <button
                        @click="save"
                        class="bg-[#1a5c2a] text-white p-2 rounded-full w-10 h-10 hover:bg-[#144420] transition-colors shadow-sm"
                    >
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="border rounded-xl overflow-hidden shadow-sm">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 border-b text-gray-500 font-bold">
                    <tr>
                        <th class="px-6 py-4 w-1/4">Nombre</th>
                        <th class="px-6 py-4">Enlace</th>
                        <th class="px-6 py-4 w-16 text-center"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <tr
                        v-for="(item, index) in registros"
                        :key="index"
                        class="hover:bg-gray-50 transition-colors"
                    >
                        <td class="px-6 py-4 font-medium text-gray-700">
                            {{ item.nombre }}
                        </td>
                        <td class="px-6 py-4 text-gray-500 break-all">
                            {{ item.enlace }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button
                                @click="remove(index)"
                                class="text-red-500 hover:text-red-700 transition-colors"
                            >
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue'

const form = reactive({
    nombre: '',
    enlace: '',
    token: '',
})

const registros = ref([
    {
        nombre: 'Ziur Software',
        enlace: 'http://platinumserver.ziursoftware.com/FUSION_CORP_SAS/basedatos_02/ZiurServiceRest.svc/api/',
    },
])

const save = () => {
    if (form.nombre && form.enlace) {
        registros.value.push({ ...form })
        form.nombre = ''
        form.enlace = ''
        form.token = ''
    }
}

const remove = index => {
    if (confirm('¿Seguro que desea eliminar esta configuración?')) {
        registros.value.splice(index, 1)
    }
}
</script>
