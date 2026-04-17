import { h } from 'vue'
import { toast } from 'vue3-toastify'

export const notify = {
    success(title, description) {
        toast.success(() =>
            h('div', { class: 'toast-content' }, [
                h('p', { class: 'toast-title' }, title),
                description
                    ? h('p', { class: 'toast-description' }, description)
                    : null,
            ])
        )
    },
    error(title, description) {
        toast.error(() =>
            h('div', { class: 'toast-content' }, [
                h('p', { class: 'toast-title' }, title),
                description
                    ? h('p', { class: 'toast-description' }, description)
                    : null,
            ])
        )
    },
}
