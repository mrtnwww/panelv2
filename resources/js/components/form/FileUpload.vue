<template>
    <div class="flex flex-col gap-2">
        <label
            v-if="label"
            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
        >
            {{ label }}
            <span v-if="required" class="text-red-400 ml-0.5">*</span>
        </label>

        <!-- Zona de upload -->
        <div
            class="relative flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed transition-all cursor-pointer group"
            :class="[
                hasError
                    ? 'border-red-300 bg-red-50/30'
                    : preview
                      ? 'border-emerald-300 bg-emerald-50/30 p-3'
                      : 'border-gray-200 hover:border-emerald-400 hover:bg-emerald-50/20 p-5',
                isDragging ? 'border-emerald-400 bg-emerald-50/30' : '',
                preview ? 'p-3' : 'p-5',
            ]"
            @dragover.prevent="isDragging = true"
            @dragleave="isDragging = false"
            @drop.prevent="onDrop"
            @click="triggerInput"
        >
            <!-- Preview imagen -->
            <template v-if="preview && isImage">
                <img
                    :src="preview"
                    :alt="label"
                    class="max-h-32 rounded-lg object-contain"
                />
                <span
                    class="text-xs text-emerald-600 font-medium truncate max-w-full"
                >
                    {{ fileName }}
                </span>
            </template>

            <!-- Preview archivo (PDF u otro) -->
            <template v-else-if="preview && !isImage">
                <div
                    class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-gray-200 w-full"
                >
                    <svg
                        width="20"
                        height="20"
                        viewBox="0 0 20 20"
                        fill="none"
                        class="text-red-400 shrink-0"
                    >
                        <path
                            d="M12 2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"
                            stroke="currentColor"
                            stroke-width="1.3"
                        />
                        <path
                            d="M12 2v6h6"
                            stroke="currentColor"
                            stroke-width="1.3"
                        />
                        <path
                            d="M7 13h6M7 10h3"
                            stroke="currentColor"
                            stroke-width="1.3"
                            stroke-linecap="round"
                        />
                    </svg>
                    <span class="text-xs text-gray-600 truncate flex-1">{{
                        fileName
                    }}</span>
                    <button
                        type="button"
                        @click.stop="clearFile"
                        class="text-gray-300 hover:text-red-400 transition-colors"
                    >
                        <svg
                            width="14"
                            height="14"
                            viewBox="0 0 14 14"
                            fill="none"
                        >
                            <path
                                d="M2 2L12 12M12 2L2 12"
                                stroke="currentColor"
                                stroke-width="1.3"
                                stroke-linecap="round"
                            />
                        </svg>
                    </button>
                </div>
            </template>

            <!-- Placeholder -->
            <template v-else>
                <i
                    class="fa-solid fa-arrow-up-from-bracket transition-colors"
                    :class="
                        hasError
                            ? 'text-red-400'
                            : 'text-gray-400 group-hover:text-emerald-500'
                    "
                ></i>
                <span
                    class="text-xs transition-colors text-center"
                    :class="
                        hasError
                            ? 'text-red-400'
                            : 'text-gray-400 group-hover:text-emerald-500'
                    "
                >
                    {{ placeholder }}
                </span>
                <span class="text-xs text-gray-300">{{ acceptLabel }}</span>
            </template>

            <!-- Input oculto -->
            <input
                ref="inputRef"
                type="file"
                :accept="accept"
                class="sr-only"
                @change="onFileChange"
            />
        </div>

        <!-- Acciones: cámara + limpiar -->
        <div v-if="preview || withCamera" class="flex items-center gap-2">
            <button
                v-if="withCamera"
                type="button"
                @click="openCamera"
                class="flex items-center gap-1.5 h-8 px-3 rounded-lg border border-gray-200 text-xs text-gray-500 hover:border-emerald-400 hover:text-emerald-600 transition-all"
            >
                <i class="fa-regular fa-camera"></i>
                Usar cámara
            </button>

            <button
                v-if="preview"
                type="button"
                @click="clearFile"
                class="flex items-center gap-1.5 h-8 px-3 rounded-lg border border-gray-200 text-xs text-gray-400 hover:border-red-300 hover:text-red-500 transition-all"
            >
                <i class="fa-solid fa-xmark"></i>
                Quitar
            </button>
        </div>

        <!-- Mensaje de error -->
        <Transition name="fade-down">
            <p
                v-if="hasError"
                class="flex items-center gap-1.5 text-xs text-red-500"
            >
                <i class="fa-regular fa-circle-question"></i>
                {{ errorMessage }}
            </p>
        </Transition>

        <!-- Modal cámara (sin cambios) -->
        <div
            v-if="cameraOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70"
            @click.self="closeCamera"
        >
            <div
                class="bg-white rounded-2xl p-5 flex flex-col gap-4 w-full max-w-sm mx-4"
            >
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-[#0A2540]">
                        Capturar foto
                    </p>
                    <button
                        type="button"
                        @click="closeCamera"
                        class="text-gray-300 hover:text-gray-500 transition-colors"
                    >
                        <svg
                            width="18"
                            height="18"
                            viewBox="0 0 18 18"
                            fill="none"
                        >
                            <path
                                d="M3 3L15 15M15 3L3 15"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-linecap="round"
                            />
                        </svg>
                    </button>
                </div>

                <video
                    ref="videoRef"
                    autoplay
                    playsinline
                    class="w-full rounded-xl bg-black aspect-video object-cover"
                />

                <div class="flex gap-2">
                    <button
                        type="button"
                        @click="capturePhoto"
                        class="flex-1 h-10 bg-[#1a5c2a] hover:bg-[#154d22] text-white text-sm font-medium rounded-lg transition-all"
                    >
                        Capturar
                    </button>
                    <button
                        type="button"
                        @click="closeCamera"
                        class="h-10 px-4 border border-gray-200 text-sm text-gray-500 rounded-lg hover:bg-gray-50 transition-all"
                    >
                        Cancelar
                    </button>
                </div>

                <canvas ref="canvasRef" class="hidden" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// -- Toaster -----------------------------------------------------
import { notify } from '@/composables/useNotify'

const props = defineProps({
    label: { type: String, default: '' },
    placeholder: { type: String, default: 'Haz clic o arrastra un archivo' },
    accept: { type: String, default: 'image/*,application/pdf' },
    acceptLabel: { type: String, default: 'PNG, JPG o PDF — máx. 1MB' },
    withCamera: { type: Boolean, default: false },
    modelValue: { default: null },
    required: { type: Boolean, default: false },
    maxSizeMb: { type: Number, default: 1 },
    error: { type: String, default: '' }, // error externo (padre / validador de formulario)
})

const emit = defineEmits(['update:modelValue', 'error'])

// ── Refs ────────────────────────────────────────────────────────────────
const inputRef = ref(null)
const videoRef = ref(null)
const canvasRef = ref(null)
const preview = ref(null)
const fileName = ref('')
const isDragging = ref(false)
const cameraOpen = ref(false)
const internalError = ref('') // error producido por validación interna
let stream = null

// ── Computed ─────────────────────────────────────────────────────────────
const isImage = computed(
    () =>
        preview.value?.startsWith('data:image') ||
        preview.value?.startsWith('blob:')
)

// El error visible prioriza el externo; si no hay, muestra el interno
const errorMessage = computed(() => props.error || internalError.value)
const hasError = computed(() => !!errorMessage.value)

// ── Validación ───────────────────────────────────────────────────────────
const TIPOS_PERMITIDOS = computed(() =>
    props.accept.split(',').map(t => t.trim())
)

function validarArchivo(file) {
    // Requerido
    if (props.required && !file) {
        return 'Este campo es obligatorio.'
    }

    // Tamaño máximo
    const maxBytes = props.maxSizeMb * 1024 * 1024
    if (file.size > maxBytes) {
        notify.error(
            `El archivo supera el tamaño máximo de ${props.maxSizeMb}MB.`
        )
        return
    }

    // Tipo MIME permitido
    const tipoValido = TIPOS_PERMITIDOS.value.some(tipo => {
        if (tipo.endsWith('/*')) {
            // ej: "image/*" → valida cualquier image/...
            return file.type.startsWith(tipo.replace('/*', '/'))
        }
        return file.type === tipo
    })

    if (!tipoValido) {
        notify.error(
            `Tipo de archivo no permitido. Acepta: ${props.acceptLabel}`
        )
        return
    }

    return null
}

// ── Acciones ─────────────────────────────────────────────────────────────
function triggerInput() {
    if (!cameraOpen.value) inputRef.value?.click()
}

function onFileChange(e) {
    const file = e.target.files[0]
    if (file) setFile(file)
}

function onDrop(e) {
    isDragging.value = false
    const file = e.dataTransfer.files[0]
    if (file) setFile(file)
}

function setFile(file) {
    const mensaje = validarArchivo(file)

    if (mensaje) {
        internalError.value = mensaje
        emit('error', mensaje)
        // Limpiamos cualquier valor previo para no dejar un estado inconsistente
        clearFile()
        return
    }

    internalError.value = ''
    fileName.value = file.name
    preview.value = URL.createObjectURL(file)
    emit('update:modelValue', file)
    emit('error', null)
}

function clearFile() {
    preview.value = null
    fileName.value = ''
    if (inputRef.value) inputRef.value.value = ''
    emit('update:modelValue', null)
}

// ── Cámara ────────────────────────────────────────────────────────────────
async function openCamera() {
    cameraOpen.value = true
    await new Promise(r => setTimeout(r, 100))
    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' },
        })
        if (videoRef.value) videoRef.value.srcObject = stream
    } catch {
        alert('No se pudo acceder a la cámara.')
        closeCamera()
    }
}

function capturePhoto() {
    const video = videoRef.value
    const canvas = canvasRef.value
    canvas.width = video.videoWidth
    canvas.height = video.videoHeight
    canvas.getContext('2d').drawImage(video, 0, 0)
    canvas.toBlob(
        blob => {
            const file = new File([blob], `foto_${Date.now()}.jpg`, {
                type: 'image/jpeg',
            })
            setFile(file)
            closeCamera()
        },
        'image/jpeg',
        0.9
    )
}

function closeCamera() {
    stream?.getTracks().forEach(t => t.stop())
    stream = null
    cameraOpen.value = false
}
</script>
