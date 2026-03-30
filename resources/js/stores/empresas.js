import { defineStore } from 'pinia'
import api from '@/services/api'

export const useEmpresasStore = defineStore('empresas', {
    state: () => ({
        empresas: [],
    }),

    getters: {
        empresasSelect: state => {
            return state.empresas.map(e => ({
                label: e.razon_social,
                value: e.id,
            }))
        },
    },

    actions: {
        async obtenerEmpresas() {
            if (this.empresas.length) return

            try {
                const { data } = await api.get('/api/empresas')
                this.empresas = data.empresas
            } catch (err) {
                throw new err()
            }
        },
    },
})
