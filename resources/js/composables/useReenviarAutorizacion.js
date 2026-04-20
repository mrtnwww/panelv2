// -- Loader ------------------------------------------------
import { useLoader } from '@/composables/useLoader'

// -- Toaster -----------------------------------------------
import { notify } from '@/composables/useNotify'

// -- Utils -------------------------------------------------
import { isValidEmail } from '@/utils/validators'

import api from '@/services/api'

export function useReenviarAutorizacion(clienteId, correo) {
    const { start, stop } = useLoader()

    async function reenviarAutorizacion(clienteId, correo) {
        const validations = [
            {
                condition: !clienteId,
                message: 'El cliente aún no se encuentra registrado.',
            },
            {
                condition: !isValidEmail(correo),
                message: 'El correo del cliente no es válido.',
            },
        ]

        for (const validation of validations) {
            if (validation.condition) {
                notify.error(validation.message)
                return
            }
        }

        start()

        try {
            await api.post('/api/clientes/reenviarAutorizacion', {
                id: clienteId,
                correo: correo,
            })

            notify.success(
                'La autorización ha sido enviada al correo del cliente.',
                `${correo}`
            )
        } catch (err) {
            notify.error(
                err.response?.data?.message ||
                    'Ocurrió un error al enviar la autorización al correo del cliente.',
                'Si el problema persiste, contacte con el administrador del sistema.'
            )
        } finally {
            stop()
        }
    }

    return {
        reenviarAutorizacion,
    }
}
