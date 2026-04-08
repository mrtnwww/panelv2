<template>
    <div class="p-6 bg-white rounded-lg shadow-sm">
        <div class="mb-6">
            <h2 class="text-sm font-bold text-gray-700 uppercase mb-4">
                Configuración de pasarela de pago
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                <div class="lg:col-span-5 space-y-4">
                    <select
                        v-model="form.pasarela"
                        class="w-full border border-gray-300 rounded-full px-4 py-2 text-sm focus:ring-2 focus:ring-green-500 outline-none transition-all"
                    >
                        <option value="" disabled>
                            Seleccione una pasarela
                        </option>
                        <option
                            v-for="p in opcionesPasarelas"
                            :key="p"
                            :value="p"
                        >
                            {{ p }}
                        </option>
                    </select>

                    <textarea
                        v-model="form.observacion"
                        placeholder="Digite la observación que verán sus clientes al momento de pagar"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm h-32 resize-none focus:ring-2 focus:ring-green-500 outline-none"
                    ></textarea>
                </div>

                <div class="lg:col-span-5 space-y-4">
                    <div class="relative">
                        <input
                            type="text"
                            v-model="form.enlace"
                            placeholder="Pago abierto: copie y pegue aquí el enlace"
                            class="w-full border border-gray-300 rounded-full px-4 py-2 text-sm pr-10 outline-none focus:border-green-500"
                        />
                        <i
                            class="fa-solid fa-circle-info absolute right-4 top-3 text-gray-400 text-xs"
                        ></i>
                    </div>

                    <div class="relative">
                        <input
                            type="text"
                            v-model="form.llave_publica"
                            placeholder="Copie y pegue la llave pública (opcional)"
                            class="w-full border border-gray-300 rounded-full px-4 py-2 text-sm pr-10 outline-none focus:border-green-500"
                        />
                        <i
                            class="fa-solid fa-circle-info absolute right-4 top-3 text-gray-400 text-xs"
                        ></i>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <button
                        @click="agregarPasarela"
                        class="w-full bg-[#36c27d] hover:bg-[#2da96b] text-white font-bold py-2 px-6 rounded-lg text-sm transition-all shadow-sm"
                    >
                        Aceptar
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-10 overflow-x-auto border rounded-xl">
            <table class="w-full text-left text-[12px]">
                <thead class="bg-gray-50 border-b text-gray-600 font-semibold">
                    <tr>
                        <th class="px-4 py-3">Pasarela</th>
                        <th class="px-4 py-3">Enlace</th>
                        <th class="px-4 py-3">Llave pública</th>
                        <th class="px-4 py-3">Secreto</th>
                        <th class="px-4 py-3">User ID</th>
                        <th class="px-4 py-3">Observación</th>
                        <th class="px-4 py-3 text-center">Activa</th>
                        <th class="px-4 py-3 text-center"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr
                        v-for="(item, index) in pasarelasConfiguradas"
                        :key="index"
                        class="hover:bg-gray-50 transition-colors"
                    >
                        <td class="px-4 py-3 font-bold text-gray-700">
                            {{ item.nombre }}
                        </td>
                        <td
                            class="px-4 py-3 text-blue-500 truncate max-w-[200px]"
                        >
                            {{ item.enlace }}
                        </td>
                        <td class="px-4 py-3 font-mono">
                            {{ item.llave || '--' }}
                        </td>
                        <td class="px-4 py-3 font-mono">
                            {{ item.secreto || '--' }}
                        </td>
                        <td class="px-4 py-3">{{ item.userId || '--' }}</td>
                        <td class="px-4 py-3 text-gray-500 italic max-w-xs">
                            {{ item.observacion }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <input
                                type="checkbox"
                                v-model="item.activa"
                                class="rounded text-green-600 focus:ring-green-500"
                            />
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button
                                @click="eliminarPasarela(index)"
                                class="text-red-400 hover:text-red-600"
                            >
                                <i class="fa-solid fa-trash"></i>
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
    pasarela: '',
    enlace: '',
    llave_publica: '',
    observacion: '',
})

const opcionesPasarelas = ['EPAYCO', 'PAYVALIDA', 'PSE', 'WOMPI']

const pasarelasConfiguradas = ref([
    {
        nombre: 'EPAYCO',
        enlace: 'https://secure.payco.co/checkoutopen/61614',
        llave: '******',
        secreto: '--',
        userId: '--',
        observacion: 'Para realizar el pago digite su número de cédula.',
        activa: false,
    },
    {
        nombre: 'PAYVALIDA',
        enlace: 'https://api.payvalida.com/api/v3',
        llave: '--',
        secreto: '******',
        userId: '--',
        observacion: '',
        activa: true,
    },
])

const agregarPasarela = () => {
    if (!form.pasarela) return alert('Seleccione una pasarela')
    // Lógica para guardar
    console.log('Guardando...', form)
}

const eliminarPasarela = index => {
    if (confirm('¿Desea eliminar esta configuración?')) {
        pasarelasConfiguradas.value.splice(index, 1)
    }
}
</script>
