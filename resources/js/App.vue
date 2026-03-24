<template>
    <Loader :visible="isLoading" />
    <router-view />
</template>

<script setup>
import Loader from '@/components/Loader.vue'
import { useLoader } from '@/composables/useLoader'

const { isLoading } = useLoader()
</script>

<style>
html,
body,
#app {
    height: 100%;
    margin: 0;
}
</style>
