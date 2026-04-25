<template>
    <div class="p-6">
        <div
            class="grid grid-cols-1 md:grid-cols-[1fr_2fr_2fr_auto] gap-6 mb-10 items-end"
        >
            <div class="space-y-2">
                <FormInput
                    label="Nombre"
                    v-model="form.nombre"
                    placeholder="Ejemplo: Ziur Software"
                />
            </div>

            <div class="space-y-2">
                <FormInput
                    label="Enlace API"
                    v-model="form.enlace"
                    placeholder="Copie y pegue aquí el enlace"
                />
            </div>

            <div class="space-y-2">
                <FormInput
                    label="Token"
                    v-model="form.token"
                    placeholder="Copie y pegue aquí el token de autorización"
                />
            </div>

            <button class="btn btn-main w-min" @click="save">Agregar</button>
        </div>

        <TableGrid :items="registros" :columns="cols">
            <template #cell(acciones)="{ item }">
                <div class="flex justify-center">
                    <button class="btn btn-danger">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </template>
        </TableGrid>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'

// -- Componentes -----------------------------------------------
import FormInput from '@/components/form/FormInput.vue'
import TableGrid from '@/components/TableGrid.vue'

// -- Loader ---------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

// -- API -------------------------------------------------------
import api from '@/services/api'

const form = reactive({
    nombre: '',
    enlace: '',
    token: '',
})

const registros = ref([])

const cols = [
    { key: 'nombre', label: 'Nombre', width: '100px' },
    {
        key: 'enlace',
        label: 'Enlace',
        width: '1.5fr',
        cellClass: 'text-sky-500',
    },
    {
        key: 'acciones',
        label: 'Acciones',
        width: '100px',
        headerClass: 'text-center',
    },
]

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

// -- Backend ----------------------------------------------
async function fetchServiciosFE() {
    try {
        const { data } = await api.get('/api/cuentaFacturacion/getServiciosFE')

        registros.value = data.configuracion.map(c => ({
            nombre: c.nombre,
            enlace: c.url,
        }))
    } catch (err) {
        console.error(err)
    }
}

onMounted(async () => {
    start()

    try {
        await fetchServiciosFE()
    } finally {
        stop()
    }
})
</script>
