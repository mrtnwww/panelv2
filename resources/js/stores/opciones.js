import { defineStore } from 'pinia'
import api from '@/services/api'

export const useOpcionesStore = defineStore('opciones', {
    state: () => ({
        empresas: [],
        clientes: [],
        cajeras: [],
    }),

    getters: {
        empresasSelect: state => {
            return state.empresas.map(e => ({
                label: e.razon_social,
                value: e.id,
            }))
        },

        cajerasSelect: state => {
            return state.cajeras.map(e => ({
                label: e.nombre,
                value: e.id,
            }))
        },

        clientesSelect: state => {
            return state.clientes.map(e => ({
                label: e.nombre,
                value: e.id,
            }))
        },
    },

    actions: {
        async fetchEmpresas() {
            if (this.empresas.length) return

            try {
                const { data } = await api.get('/api/empresas')
                this.empresas = data.empresas
            } catch (err) {
                throw new err()
            }
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

        async fetchClientes() {
            if (this.clientes.length) return

            try {
                const { data } = await api.get('/api/clientes/listCreditsClients')
                this.clientes = data.clientes
            } catch (err) {
                throw new err()
            }
        }
    },
})
