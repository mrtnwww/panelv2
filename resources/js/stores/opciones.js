import { defineStore } from 'pinia'
import api from '@/services/api'

export const useOpcionesStore = defineStore('opciones', {
    state: () => ({
        clientesCreditos: [],
        cajeras: [],
    }),

    getters: {
        cajerasSelect: state => {
            return state.cajeras.map(e => ({
                label: e.nombre,
                value: e.id,
            }))
        },
    },

    actions: {
        async fetchEmpresas(query) {
            const { data } = await api.get('/api/empresas', {
                params: { search: query, perPage: 10 },
            })

            return data.empresas.data.map(empresa => ({
                value: empresa.id,
                label: empresa.razon_social,
            }))
        },

        async fetchClientesCredits(query) {
            const { data } = await api.get('/api/clientes/listClientsCredits', {
                params: { search: query, perPage: 30 },
            })

            this.clientesCreditos = data.clientes.data

            return this.clientesCreditos.map(c => ({
                value: c.id,
                label: `${c.nombre} (${c.cedula}) - ${c.empresa.razon_social}`,
            }))
        },

        async fetchClientesValidated(query) {
            const { data } = await api.get(
                '/api/clientes/listMyClientsValidated',
                {
                    params: { search: query, perPage: 30 },
                }
            )

            return data.clientes.data.map(c => ({
                value: c.id,
                label: `${c.nombre} (${c.cedula})`,
            }))
        },

        async fetchCajeras() {
            if (this.cajeras.length) return

            try {
                const { data } = await api.get('/api/cajeras')
                this.cajeras = data.cajeras
            } catch (err) {
                throw new err()
            }
        },
    },
})
