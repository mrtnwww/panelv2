import dayjs from 'dayjs'

export function formatCurrency(value, options = {}) {
    if (value === null || value === undefined || value === '') return ''

    const {
        locale = 'es-CO',
        currency = 'COP',
        minimumFractionDigits = 0,
    } = options

    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        minimumFractionDigits,
    }).format(value)
}

export function formatDateToISO(date) {
    if (!date) return ''
    const [d, m, y] = date.split('/')
    return `${y}-${m}-${d}`
}

export function formatDateYmd(date) {
    return dayjs(date).format('YYYY-MM-DD')
}

export function formatDateYmdHms(date) {
    return dayjs(date).format('YYYY-MM-DD HH:mm:ss')
}