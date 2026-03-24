import { ref } from 'vue'

const isLoading = ref(false)

export function useLoader() {
    function start() {
        isLoading.value = true
    }

    function stop() {
        isLoading.value = false
    }

    return {
        isLoading,
        start,
        stop,
    }
}
