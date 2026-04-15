<template>
    <div class="p-6">
        <div class="mb-6 max-w-sm">
            <div class="relative">
                <FormInput
                    v-model="searchQuery"
                    placeholder="Buscar función..."
                    size="lg"
                    icon-left="search"
                    wrapper-class="w-full sm:w-full"
                />
            </div>
        </div>

        <TableGrid :items="filteredFunciones" :columns="cols">
            <template #cell(activar)="{ item }">
                <FormToggle v-model="item.activa" />
            </template>

            <template #cell(acciones)="{ item }">
                <div class="flex justify-center">
                    <!-- TODO -->
                </div>
            </template>
        </TableGrid>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

// -- Componentes ---------------------------------------------------
import FormToggle from '@/components/form/FormToggle.vue'
import FormInput from '@/components/form/FormInput.vue'
import TableGrid from '@/components/TableGrid.vue'

// -- Loader ---------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

// -- API -------------------------------------------------------
import api from '@/services/api'

const searchQuery = ref('')
const funciones = ref([])

const cols = [
    { key: 'nombre', label: 'Nombre de la función', width: '1fr' },
    {
        key: 'activar',
        label: 'Activar/Inactivar',
        width: '1fr',
        headerClass: 'text-center',
        cellClass: 'flex justify-center',
    },
    {
        key: 'acciones',
        label: 'Acciones',
        width: '1fr',
        headerClass: 'text-center',
    },
    { key: 'descripcion', label: 'Descripción', width: '3fr' },
]

const filteredFunciones = computed(() => {
    const query = searchQuery.value.toLowerCase().trim()

    if (!query) return funciones.value

    return funciones.value.filter(f => {
        const nombre = f.nombre ? f.nombre.toLowerCase() : ''
        const descripcion = f.descripcion ? f.descripcion.toLowerCase() : ''

        return nombre.includes(query) || descripcion.includes(query)
    })
})

// -- Backend --------------------------------------------
async function fetchFunciones() {
    try {
        const { data } = await api.get('/api/cuentaFacturacion/getFunciones')

        funciones.value = data.funciones.map(f => ({
            id: f.id,
            nombre: f.nombre_funcion,
            activa: f.activa || false,
            descripcion: f.descripcion || '',
        }))
    } catch (err) {
        console.error(err)
    }
}

onMounted(async () => {
    start()

    try {
        await fetchFunciones()
    } finally {
        stop()
    }
})
</script>
