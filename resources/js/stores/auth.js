import { defineStore } from 'pinia'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null
    }),

    getters: {
        isAuthenticated: state => !!state.user,
    },

    actions: {
        async fetchUser() {
            if (this.user) return this.user

            try {
                const res = await api.get('/api/user')
                this.user = res.data
                return this.user
            } catch {
                this.user = null
                return null
            }
        },

        async login(credentials) {
            try {
                await api.get('/sanctum/csrf-cookie')

                await api.post('/login', credentials)

                await this.fetchUser()

                return true
            } catch (error) {
                throw error
            }
        },

        async logout() {
            await api.post('/logout')
            this.user = null
        },
    },
})
