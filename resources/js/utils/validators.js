/**
 * Valida si un string tiene un formato de correo electrónico válido.
 * * @param {string} email - El correo a validar.
 * @returns {boolean} - True si es válido, False de lo contrario.
 */
export const isValidEmail = email => {
    if (!email || typeof email !== 'string') return false

    // Eliminar espacios en blanco al inicio y final
    const cleanEmail = email.trim()

    // Regex robusta (RFC 5322 simplificada)
    // - Permite caracteres estándar, puntos y guiones.
    // - Valida que el dominio tenga al menos dos caracteres (ej. .com, .co).
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/

    // Validaciones adicionales de seguridad
    if (cleanEmail.length > 254) return false // Límite estándar de longitud de correo

    return emailRegex.test(cleanEmail)
}
