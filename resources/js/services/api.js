import axios from 'axios'

const api = axios.create({
    baseURL: 'http://localhost:8000',
    withCredentials: true,
    headers: {
        Accept: 'application/json',
    },
})

api.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 429) {
            console.error(
                'Demasiadas peticiones. Login bloqueado por 1 minuto.'
            )
        }
        return Promise.reject(error)
    }
)

export default api
