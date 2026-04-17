<template>
    <div class="inline-block mt-2">
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform scale-90 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
        >
            <div v-if="source">
                <div
                    v-if="isImage"
                    @click="isOpen = true"
                    class="relative w-fit overflow-hidden rounded-xl border border-gray-100 bg-white p-1 shadow-sm ring-1 ring-gray-950/5 cursor-zoom-in hover:ring-emerald-500/50 transition-all group"
                >
                    <img
                        :src="displayUrl"
                        class="h-16 w-16 rounded-lg object-cover bg-gray-50 group-hover:opacity-90"
                    />
                </div>

                <a
                    v-else
                    :href="displayUrl"
                    target="_blank"
                    download
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-gray-100 bg-white shadow-sm ring-1 ring-gray-950/5 hover:bg-gray-50 transition-all text-gray-600 group"
                >
                    <div
                        class="p-2 bg-emerald-50 rounded-lg group-hover:bg-emerald-50 transition-colors"
                    >
                        <i class="fa-solid fa-download"></i>
                    </div>
                    <div class="flex flex-col items-start pr-2">
                        <span
                            class="text-[10px] font-bold uppercase tracking-wider text-gray-400 leading-none mb-1"
                            >Documento</span
                        >
                        <span
                            class="text-xs font-semibold text-gray-700 leading-none"
                            >Descargar archivo</span
                        >
                    </div>
                </a>
            </div>
        </transition>

        <Teleport to="body">
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
            >
                <div
                    v-if="isOpen && isImage"
                    class="fixed inset-0 z-999 flex flex-col items-center justify-center bg-gray-950/95 backdrop-blur-md p-4 md:p-10"
                    @click="isOpen = false"
                >
                    <button
                        @click="isOpen = false"
                        class="absolute top-6 right-6 text-white/50 hover:text-white transition-colors flex items-center gap-2"
                    >
                        <span
                            class="text-xs font-bold uppercase tracking-[0.2em]"
                            >Cerrar [Esc]</span
                        >
                    </button>

                    <div
                        class="relative w-full h-full flex items-center justify-center"
                        @click="isOpen = false"
                    >
                        <img
                            :src="displayUrl"
                            class="w-auto max-w-full rounded-lg shadow-2xl object-contain border border-white/10"
                            @click.stop
                        />
                    </div>
                </div>
            </transition>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, ref, onUnmounted, onMounted } from 'vue'

const props = defineProps({
    source: { type: [Object, String], default: null },
})

const isOpen = ref(false)
const objectUrl = ref(null)

// Detectar si el archivo es una imagen basándose en la extensión o el tipo MIME
const isImage = computed(() => {
    if (!props.source) return false

    // 1. Si es un objeto File (subida reciente), confiamos en su tipo MIME
    if (props.source instanceof File) {
        return props.source.type.startsWith('image/')
    }

    // 2. Si es un String (URL de S3)
    if (typeof props.source === 'string') {
        // Limpiamos la URL por si tiene parámetros de AWS (?X-Amz-Algorithm...)
        const cleanUrl = props.source.split('?')[0].toLowerCase()

        // Lista completa de extensiones de imagen
        const imageExtensions = [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
            'svg',
            'avif',
        ]

        // Verificamos si la URL limpia termina en alguna de esas extensiones
        return imageExtensions.some(ext => cleanUrl.endsWith(`.${ext}`))
    }

    return false
})

const displayUrl = computed(() => {
    if (!props.source) return null
    if (typeof props.source === 'string') return props.source
    if (props.source instanceof File) {
        if (objectUrl.value) URL.revokeObjectURL(objectUrl.value)
        objectUrl.value = URL.createObjectURL(props.source)
        return objectUrl.value
    }
    return null
})

const handleEsc = e => {
    if (e.key === 'Escape') isOpen.value = false
}
onMounted(() => window.addEventListener('keydown', handleEsc))
onUnmounted(() => {
    window.removeEventListener('keydown', handleEsc)
    if (objectUrl.value) URL.revokeObjectURL(objectUrl.value)
})
</script>
