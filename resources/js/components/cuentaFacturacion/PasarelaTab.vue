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
                        :searchable="true"
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

        <TableGrid
            :items="pasarelasConfiguradas"
            :columns="cols"
            minWidth="min-w-max"
        >
            <template
                v-for="col in ['llave_publica', 'secreto', 'user_id']"
                #[`cell(${col})`]="{ item }"
            >
                <span v-if="item[col]">******</span>
                <span v-else>- -</span>
            </template>

            <template #cell(activa)="{ item }">
                <i
                    :class="
                        item.activa ? 'fa-solid fa-check text-[#154d22]' : ''
                    "
                ></i>
            </template>

            <template #cell(acciones)="{ item }">
                <div class="flex justify-end gap-1.5">
                    <button v-if="!item.activa" class="btn btn-primary">
                        Activar
                    </button>

                    <button class="btn btn-danger">
                        <i class="fa-solid fa-trash"></i>
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
    pasarela: '',
    enlace: '',
    llave_publica: '',
    observacion: '',
})

const pasarelasOpts = ref([])
const pasarelasConfiguradas = ref([])

const cols = [
    { key: 'pasarela', label: 'Pasarela', width: '100px' },
    { key: 'enlace', label: 'Enlace', width: 'minmax(250px, 2fr)' },
    {
        key: 'llave_publica',
        label: 'Llave pública',
        width: '100px',
        headerClass: 'text-center',
        cellClass: 'text-center text-xl!',
    },
    {
        key: 'secreto',
        label: 'Secreto',
        width: '100px',
        headerClass: 'text-center',
        cellClass: 'text-center text-xl!',
    },
    {
        key: 'user_id',
        label: 'User ID',
        width: '100px',
        headerClass: 'text-center',
        cellClass: 'text-center text-xl!',
    },
    { key: 'observacion', label: 'Observación', width: 'minmax(200px, 1.5fr)' },
    {
        key: 'activa',
        label: 'Activa',
        width: '80px',
        headerClass: 'text-center',
        cellClass: 'text-center text-xl!',
    },
    {
        key: 'acciones',
        label: 'Acciones',
        width: '1fr',
        headerClass: 'text-center',
    },
]

// -- Backend ----------------------------------------------
async function fetchPasarelas() {
    try {
        const { data } = await api.get('/api/cuentaFacturacion/getPasarelas')

        pasarelasOpts.value = data.pasarelas.map(p => ({
            value: p.id,
            label: p.nombre,
        }))
    } catch (err) {
        console.error(err)
    }
}

async function fetchPasarelasConfig() {
    try {
        const { data } = await api.get(
            '/api/cuentaFacturacion/getPasarelasConfig'
        )

        pasarelasConfiguradas.value = data.pasarelasEmpresa.map(p => ({
            pasarela: p.pasarela_nombre,
            enlace: p.enlace,
            llave_publica: p.public_api_key,
            secreto: p.secret_pasarela,
            user_id: p.user_id_pasarela,
            observacion: p.observacion,
            activa: p.activa,
        }))
    } catch (err) {
        console.error(err)
    }
}

const agregarPasarela = () => {
    if (!form.pasarela) return alert('Seleccione una pasarela')
}

const eliminarPasarela = index => {
    //
}

onMounted(async () => {
    start()

    try {
        await Promise.all([fetchPasarelas(), fetchPasarelasConfig()])
    } finally {
        stop()
    }
})
</script>
