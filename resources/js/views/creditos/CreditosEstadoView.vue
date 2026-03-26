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
                @imprimir="imprimir"
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

import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

import api from '@/services/api'

// -- Estado ----------------------------------------------------
const clienteId = ref('')

// -- Opciones de clientes --------------------------------------
const clientesOpts = ref([])
const clientes = ref([])

// -- Modal estado credito --------------------------------------
const loadingCredito = ref(false)
const modalOpen = ref(false)
const credito = ref(null)

async function verEstadoCredito(id) {
    modalOpen.value = true
    loadingCredito.value = true

    try {
        const { data } = await api.get(`/api/creditos/${id}/details`)
        const { tabla } = data
        const { cliente } = tabla

        credito.value = {
            cliente: {
                nombre: cliente.nombre,
                cedula: cliente.cedula,
                correo: cliente.email,
                telefono: cliente.telefono,
            },
            valor_compra: tabla.valor_compra,
            valor_pendiente: tabla.ValorPendiente,
            valor_pagado: tabla.ValorPagado,
            valor_cuotas: tabla.val_cuotas,
            en_mora: tabla.enMora,
            num_cuotas: tabla.num_cuotas,
            periodicidad: tabla.periocidad,
            intereses_moratorios: tabla.total_intereses_m,
            gastos_cobranza: tabla.total_gastos_c,
            realizado_por: tabla.realizado_por,
            fecha_creacion: tabla.fecha_credito,
            cuotas: tabla.proyeccion,
        }
    } finally {
        loadingCredito.value = false
    }
}

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

function verHistorico() {}
function liquidarCredito() {}
function verPlanPagos() {}
function descargarPazSalvo() {}
function imprimir() {}
function imprimirAbono() {}

onMounted(async () => {
    start()

    try {
        await fetchClientes()
    } finally {
        stop()
    }
})
</script>
