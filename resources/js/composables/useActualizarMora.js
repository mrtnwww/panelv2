// -- Loader ----------------------------------------------------------------
import { useLoader } from '@/composables/useLoader'

import api from '@/services/api'

export function useActualizarMora() {
    const { start, stop } = useLoader()

    const actualizarMora = async callback => {
        let moraActualizada = true
        let message = ''

        start()

        while (moraActualizada) {
            try {
                const response = await api.post('api/creditos/updateMora')

                if (!response.data.moraPendiente) {
                    message = response.data.message
                    moraActualizada = false
                }
            } catch {
                moraActualizada = false
            }
        }

        if (message) {
            if (
                message?.includes('Estado de la mora actualizado correctamente')
            ) {
                if (callback) await callback()
            }
        }
        stop()
    }

    return {
        actualizarMora,
    }
}
