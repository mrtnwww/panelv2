<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">Estado de crédito</h1>

        <!-- Panel de búsqueda -->
        <div
            class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-3"
        >
            <FormInput
                label="Buscar cliente"
                type="select"
                v-model="clienteId"
                @update:modelValue="verEstadoCredito"
                :options="clientesOpts"
                placeholder="Seleccione un cliente"
                :searchable="true"
            />
        </div>

        <transition name="modal">
            <EstadoCreditoModal
                v-model="modalOpen"
                :loading="loadingCredito"
                :credito="credito"
                @ver-historico="verHistorico"
                @liquidar="liquidarCredito"
                @ver-plan-pagos="verPlanPagos"
                @descargar-paz-salvo="descargarPazSalvo"
                @imprimir="imprimirCredito"
                @imprimir-abono="imprimirAbono"
            />
        </transition>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

// -- Componentes --------------------------------------------
import EstadoCreditoModal from '@/components/modals/EstadoCreditoModal.vue'
import FormInput from '@/components/form/FormInput.vue'

import { useEstadoCredito } from '@/composables/useEstadoCredito'
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

import api from '@/services/api'

// -- Estado ----------------------------------------------------
const clienteId = ref('')

// -- Opciones de clientes --------------------------------------
const clientesOpts = ref([])
const clientes = ref([])

async function fetchClientes() {
    try {
        const { data } = await api.get('/api/clientes/listCreditsClients')
        clientes.value = data.clientes

        // Opciones formateadas para FormInput type="select"
        clientesOpts.value = clientes.value.map(c => ({
            value: c.credito_id,
            label: `${c.nombre} (${c.cedula}) - Crédito ${c.credito_id}`,
        }))
    } catch (err) {
        console.error(err)
    }
}

// -- Modal estado credito --------------------------------------
const {
    modalOpen,
    loadingCredito,
    credito,
    verEstadoCredito,
    verHistorico,
    liquidarCredito,
    verPlanPagos,
    descargarPazSalvo,
    imprimirCredito,
    imprimirAbono,
} = useEstadoCredito()

onMounted(async () => {
    start()

    try {
        await fetchClientes()
    } finally {
        stop()
    }
})
</script>
