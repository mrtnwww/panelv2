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
import { reactive, ref } from 'vue'

// -- Componentes -----------------------------------------------
import FormInput from '@/components/form/FormInput.vue'
import TableGrid from '@/components/TableGrid.vue'

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

const cols = [
    { key: 'nombre', label: 'Nombre' },
    { key: 'enlace', label: 'Enlace' },
    { key: 'acciones', label: 'Acciones' },
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
</script>
