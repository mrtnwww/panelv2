<template>
    <button
        @click="handleClick"
        class="h-8 px-3 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium transition-all flex items-center gap-1.5"
    >
        <i class="fa-solid fa-rotate"></i>

        <span>Actualizar Mora</span>
    </button>
</template>

<script setup>
import { useActualizarMora } from '@/composables/useActualizarMora'

const { actualizarMora } = useActualizarMora()

const props = defineProps({
    onSuccess: Function,
})

const handleClick = () => {
    actualizarMora(props.onSuccess)
}
</script>
