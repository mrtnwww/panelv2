import { defineStore } from 'pinia'
import api from '@/services/api'

export const useNotificationStore = defineStore('notifications', {
    state: () => ({
        items: [],
        unreadCount: 0,
        loading: false,
    }),
    actions: {
        async fetchNotifications() {
            this.loading = true

            try {
                const { data } = await api.get('/api/notificaciones/getNotificaciones')

                this.items = data.notifications
                this.unreadCount = data.notifications.filter(
                    n => !n.visualized_at
                ).length
            } catch (err) {
                console.error(err)
            } finally {
                this.loading = false
            }
        },

        async visualizeNotifications() {
            this.loading = true

            try {
                await api.post('/api/notificaciones/visualizeNotificaciones')

                this.items.forEach(noti => {
                    if (!noti.visualized_at) {
                        noti.visualized_at = new Date().toISOString()
                    }
                })
                this.unreadCount = 0
            } catch (err) {
                console.error(err)
            } finally {
                this.loading = false
            }
        },
    },
})
