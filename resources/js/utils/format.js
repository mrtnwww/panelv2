export function formatCurrency(value, options = {}) {
    if (value === null || value === undefined || value === '') return ''

    const {
        locale = 'es-CO',
        currency = 'COP',
        minimumFractionDigits = 0
    } = options

    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        minimumFractionDigits
    }).format(value)
}