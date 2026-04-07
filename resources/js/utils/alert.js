import Swal from 'sweetalert2'

export async function confirmAlert(options = {}) {
    const {
        title = 'Confirmar',
        text = '¿Estás seguro?',
        icon = 'warning',
        confirmText = 'Aceptar',
        cancelText = 'Cancelar',
        showCancelButton = true,
        confirmButtonColor,
        cancelButtonColor,
    } = options

    const { isConfirmed } = await Swal.fire({
        title,
        text,
        icon,
        showCancelButton,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        confirmButtonColor,
        cancelButtonColor,
    })

    return isConfirmed
}
