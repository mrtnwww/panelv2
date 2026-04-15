<template>
    <div class="p-6">
        <div class="mb-10">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-[#28a745] font-bold text-lg">
                    Tu próximo pago
                </h2>
                <button class="btn btn-secondary">
                    Pagar <i class="fa-solid fa-dollar-sign"></i>
                </button>
            </div>

            <div class="space-y-3 max-w-4xl">
                <div
                    v-for="item in pagoFields"
                    :key="item.label"
                    class="grid grid-cols-1 md:grid-cols-3 items-center"
                >
                    <label class="text-xs text-gray-600">{{
                        item.label
                    }}</label>
                    <div class="md:col-span-2">
                        <input
                            type="text"
                            :value="item.value"
                            readonly
                            class="w-full bg-white border border-blue-100 rounded-xl px-4 py-2 text-sm shadow-sm focus:outline-none text-gray-700"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-10">
            <div
                @click="showHistory = !showHistory"
                class="w-full border border-gray-300 rounded-full py-2 px-6 flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors shadow-sm"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="bg-gray-800 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px]"
                    >
                        <i
                            :class="
                                showHistory
                                    ? 'fa-solid fa-chevron-up'
                                    : 'fa-solid fa-chevron-right'
                            "
                        ></i>
                    </div>
                    <span class="text-sm text-gray-700 font-medium"
                        >Historial de transacciones</span
                    >
                </div>
            </div>

            <div
                v-if="showHistory"
                class="mt-4 p-4 border rounded-xl bg-gray-50"
            >
                <p class="text-xs text-center text-gray-500">
                    No hay datos para mostrar
                </p>
            </div>

            <p class="text-xs text-gray-400 mt-2 text-center italic">
                *El costo mensual es del de acuerdo a los créditos otorgados en
                el mes y se liquida sobre el valor total mes vencido.
            </p>
        </div>

        <div>
            <h3 class="text-[#28a745] font-bold text-lg mb-4">
                Activar / inactivar módulos
            </h3>
            <div class="divide-y divide-gray-100 border-t border-gray-100">
                <div
                    v-for="modulo in modulos"
                    :key="modulo.id"
                    class="flex flex-col md:flex-row md:items-center justify-between py-3 px-2 hover:bg-gray-50 transition-colors"
                >
                    <div class="flex items-center gap-3">
                        <FormCheckbox
                            :label="modulo.nombre"
                            v-model="modulo.active"
                            @update:model-value="updateModulos(modulo.id)"
                        />
                    </div>
                    <span
                        class="text-xs text-gray-500 italic mt-1 select-none md:mt-0"
                    >
                        {{ modulo.descripcion }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'

// -- Componentes ----------------------------------------------
import FormCheckbox from '@/components/form/FormCheckbox.vue'

// -- Loader ---------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

// -- API ----------------------------------------------------------
import api from '@/services/api'

const showHistory = ref(false)

const pagoFields = [
    { label: 'Periodo', value: '' },
    { label: 'Valor total de créditos realizados', value: '' },
    { label: 'Valor comisión (total a pagar)', value: '' },
    { label: 'Porcentaje aplicado sobre comisión', value: '' },
]

const modulos = reactive([
    {
        id: 1,
        nombre: 'Modulo vehiculos',
        descripcion:
            'Activa este modulo para realizar créditos respaldados con vehiculos',
        active: false,
    },
    {
        id: 2,
        nombre: 'Modulo viviendas',
        descripcion:
            'Activa este modulo para realizar créditos respaldados con viviendas (hipoteca o lease back)',
        active: false,
    },
    {
        id: 3,
        nombre: 'Modulo aliados/sedes',
        descripcion:
            'Activa este modulo para poder gestionar tus aliados/sedes',
        active: false,
    },
    {
        id: 4,
        nombre: 'Módulo empresas convenio',
        descripcion: 'Activa este modulo para poder gestionar libranza',
        active: false,
    },
])

// -- Backend -------------------------------------------------
async function fetchModulos() {
    try {
        const { data } = await api.get('/api/cuentaFacturacion/getModulos')

        const empresa = data?.empresa || {}

        const mapa = {
            1: empresa.credivehiculo === 1,
            2: empresa.credihipoteca === 1,
            3: empresa.sedeAliado === 1,
            4: empresa.libranza === 1,
        }

        modulos.forEach(modulo => {
            if (mapa[modulo.id] !== undefined) {
                modulo.active = mapa[modulo.id]
            }
        })
    } catch (err) {
        console.error(err)
    }
}

async function updateModulos(id) {
    const mapa = {
        1: 'credivehiculo',
        2: 'credihipoteca',
        3: 'sedeAliado',
        4: 'libranza',
    }

    try {
        await api.put('/api/cuentaFacturacion/updateModulos', {
            modulo: mapa[id],
        })
    } catch (err) {
        console.log(err)
    }
}

onMounted(async () => {
    start()

    try {
        await fetchModulos()
    } finally {
        stop()
    }
})
</script>
