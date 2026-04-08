<template>
    <button @click="handleClick" class="btn btn-secondary">
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
