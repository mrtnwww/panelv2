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

async function verEstadoCredito(row) {
    modalOpen.value = true
    loadingCredito.value = true
    // try {
    //     const { data } = await api.get(`/api/creditos/${row.id}/estado`)
    //     credito.value = data
    // } finally {
    //     loadingCredito.value = false
    // }
    credito.value = {
        cliente: {
            nombre: 'FREDY ALEJANDRO REYES BARAJAS',
            cedula: '1098150702',
            correo: 'fredyrb17@gmail.com',
            telefono: '3172969033',
        },
        valor_compra: 689344,
        valor_pendiente: 732202,
        valor_pagado: 0,
        valor_cuotas: 122692,
        en_mora: false,
        num_cuotas: 6,
        periodicidad: 'Mensual',
        intereses_moratorios: 0,
        gastos_cobranza: 0,
        realizado_por: 'Maria Camila Ramirez',
        fecha_creacion: '2026-03-25 14:27:57',
        cuotas: [
            {
                numero: 1,
                fecha_limite: '2026-04-15',
                valor_abonado: 0,
                saldo: 118742,
                estado: 'Pendiente',
                interes_moratorio: 0,
                gastos_cobranza: 0,
                fecha_pago: null,
            },
            // ...
        ],
    }
    loadingCredito.value = false
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

onMounted(async () => {
    start()

    try {
        await fetchClientes()
    } finally {
        stop()
    }
})
</script>
