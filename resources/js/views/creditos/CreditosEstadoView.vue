<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">Estado de crédito</h1>

        <!-- Panel de búsqueda -->
        <div
            class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-3"
        >
            <FormSelectAsync
                label="Buscar cliente"
                v-model="clienteId"
                @update:modelValue="verEstadoCredito"
                :fetch-options="opcionesStore.fetchClientesCredits"
                placeholder="Seleccione un cliente"
                class="xl:w-[50%]"
            />
        </div>

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
    </div>
</template>

<script setup>
import { ref } from 'vue'

// -- Componentes --------------------------------------------
import EstadoCreditoModal from '@/components/modals/EstadoCreditoModal.vue'
import FormSelectAsync from '@/components/form/FormSelectAsync.vue'

// -- Composables --------------------------------------------
import { useEstadoCredito } from '@/composables/useEstadoCredito'

// -- Store ----------------------------------------------------
import { useOpcionesStore } from '@/stores/opciones'

// -- Opciones de selects -------------------------------------
const opcionesStore = useOpcionesStore()

// -- Estado ----------------------------------------------------
const clienteId = ref('')

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
</script>
