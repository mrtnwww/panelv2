<template>
    <div class="p-6 bg-white rounded-lg shadow-sm">
        <div class="mb-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                <div class="lg:col-span-6 space-y-4">
                    <FormInput
                        type="select"
                        v-model="form.pasarela"
                        :options="pasarelasOpts"
                        placeholder="Seleccione una pasarela"
                    />

                    <FormInput
                        type="textarea"
                        v-model="form.observacion"
                        placeholder="Digite la observación que verán sus clientes al momento de pagar"
                    />
                </div>

                <div class="lg:col-span-6 space-y-4">
                    <div class="relative">
                        <FormInput
                            v-model="form.enlace"
                            placeholder="Pago abierto: copie y pegue aquí el enlace"
                        />
                    </div>

                    <div class="relative">
                        <FormInput
                            v-model="form.llave_publica"
                            placeholder="Copie y pegue la llave pública (opcional)"
                        />
                    </div>

                    <div class="flex items-start justify-end">
                        <button @click="agregarPasarela" class="btn btn-main">
                            Aceptar
                        </button>
                    </div>
                </div>
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
import { reactive, ref } from 'vue'

// -- Componentes -----------------------------------------------
import FormInput from '@/components/form/FormInput.vue'
import TableGrid from '@/components/TableGrid.vue'

const form = reactive({
    pasarela: '',
    enlace: '',
    llave_publica: '',
    observacion: '',
})

const pasarelasOpts = ref([])

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

const cols = [
    { key: 'pasarela', label: 'Pasarela' },
    { key: 'enlace', label: 'Enlace' },
    { key: 'llave_publica', label: 'Llave pública' },
    { key: 'secreto', label: 'Secreto' },
    { key: 'user_id', label: 'User ID' },
    { key: 'observacion', label: 'Observación' },
    { key: 'activa', label: 'Activa' },
    { key: 'acciones', label: 'Acciones' },
]

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
