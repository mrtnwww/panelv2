<template>
    <div class="p-6">
        <div class="mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div
                    v-for="doc in docsSistema"
                    :key="doc.label"
                    class="flex items-center justify-between border border-[#1a5c2a] rounded-lg px-4 py-2 bg-white hover:bg-gray-50 transition-colors"
                >
                    <span class="text-sm text-gray-700 font-medium">{{
                        doc.label
                    }}</span>
                    <FormCheckbox
                        v-model="doc.active"
                        @update:model-value="handleDocumentos(doc, $event)"
                    />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div
                v-for="info in [
                    'Información recibo de caja',
                    'Información notificación e-mail',
                    'Información de extracto',
                ]"
                :key="info"
                class="flex items-center justify-between border border-[#1a5c2a] rounded-lg px-4 py-2 bg-white"
            >
                <span class="text-sm text-gray-700 font-medium">{{
                    info
                }}</span>
                <button class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-pencil"></i>
                </button>
            </div>
        </div>

        <div class="border-t pt-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">
                Otros documentos
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="space-y-4">
                    <FormInput
                        v-model="newDoc.nombre"
                        placeholder="Nombre del documento"
                    />

                    <FormInput
                        type="select"
                        v-model="newDoc.tipo"
                        :options="tiposDocumentosOpts"
                        placeholder="Seleccione un tipo de documento"
                    />

                    <div class="flex items-center gap-4">
                        <button class="btn btn-info">
                            <i class="fa-solid fa-paperclip"></i> Adjuntar
                            documento
                        </button>
                        <label
                            class="flex items-center gap-2 text-xs text-gray-600"
                        >
                            <FormCheckbox v-model="newDoc.activo" />
                            Activo
                        </label>
                    </div>

                    <button @click="saveOtherDoc" class="btn btn-main">
                        Aceptar
                    </button>
                </div>

                <TableGrid :items="otrosDocs" :columns="cols" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'

// -- Componentes -----------------------------------------------
import FormCheckbox from '@/components/form/FormCheckbox.vue'
import FormInput from '@/components/form/FormInput.vue'
import TableGrid from '@/components/TableGrid.vue'

// -- Loader ---------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

// -- API -------------------------------------------------------
import api from '@/services/api'

const cols = [
    { key: 'nombre', label: 'Nombre' },
    {
        key: 'tipo_documento',
        label: 'Tipo de documento',
    },
    {
        key: 'archivo',
        label: 'Archivo',
        width: '100px',
        headerClass: 'text-center',
    },
    {
        key: 'estado',
        label: 'Estado',
        width: '100px',
        headerClass: 'text-center',
    },
]

const docsSistema = ref([])

const newDoc = reactive({
    nombre: '',
    tipo: '',
    activo: true,
})

const otrosDocs = ref([])
const tiposDocumentosOpts = ref([])

// -- Backend -------------------------------------------------
async function fetchDocumentos() {
    try {
        const { data } = await api.get('/api/cuentaFacturacion/getDocumentos')

        tiposDocumentosOpts.value = data.tiposDocumentos.map(d => ({
            value: d.id,
            label: d.nombre,
        }))

        docsSistema.value = data.documentos.map(d => ({
            id: d.id,
            label: d.nombre,
            active: d.active_to_current_company,
        }))
    } catch (err) {
        console.error(err)
    }
}

async function handleDocumentos(doc, checked) {
    start()

    try {
        await api.put('/api/cuentaFacturacion/updateDocumentos', {
            id: doc.id,
            estado: checked,
        })
    } catch (err) {
        console.error(err)
    } finally {
        stop()
    }
}

const saveOtherDoc = () => {
    // TODO
}

onMounted(async () => {
    start()

    try {
        await fetchDocumentos()
    } finally {
        stop()
    }
})
</script>
