import { ref } from 'vue'
import api from '@/services/api'

// -- Store ----------------------------------------------------
import { useOpcionesStore } from '@/stores/opciones'

export function useEstadoCredito() {
    const loadingCredito = ref(false)
    const modalOpen = ref(false)
    const credito = ref(null)

    const opcionesStore = useOpcionesStore()

    function verEstadoCredito(clienteId, creditoId) {
        if (clienteId) {
            // Consultar créditos por cliente
            const cliente = opcionesStore.clientesCreditos.find(
                c => c.id == clienteId
            )

            const creditos = cliente.credito

            if (creditos.length) fetchCreditoDetail(creditos[0].id)
        } else {
            fetchCreditoDetail(creditoId)
        }
    }

    function verEstadoCredito(clienteId, creditoId) {
        if (!clienteId) return fetchCreditoDetail(creditoId)

        const cliente = opcionesStore.clientesCreditos.find(
            c => c.id == clienteId
        )

        const clienteCreditoId = cliente?.credito?.[0]?.id
        if (clienteCreditoId) fetchCreditoDetail(clienteCreditoId)
    }

    async function fetchCreditoDetail(id) {
        loadingCredito.value = true
        modalOpen.value = true

        try {
            const { data } = await api.get(`/api/creditos/${id}/details`)
            credito.value = mapCredito(data.tabla)
        } finally {
            loadingCredito.value = false
        }
    }

    function verHistorico() {}
    function liquidarCredito() {}
    function verPlanPagos() {}
    function descargarPazSalvo() {}
    function imprimirCredito() {}
    function imprimirAbono() {}

    function mapCredito(tabla) {
        const { cliente } = tabla

        return {
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
    }

    return {
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
    }
}
