import { defineStore } from 'pinia'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        loading: false,
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
            this.loading = true

            try {
                await api.get('/sanctum/csrf-cookie')

                await api.post('/login', credentials)

                await this.fetchUser()

                return true
            } catch (error) {
                throw error
            } finally {
                this.loading = false
            }
        },

        async logout() {
            await api.post('/logout')
            this.user = null
        },
    },
})
