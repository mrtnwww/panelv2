<template>
    <div class="flex flex-col gap-5 w-full">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">General</h1>

        <!-- Tarjeta de perfil -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row gap-8">
                <!-- Panel izquierdo: avatar -->
                <div class="flex flex-col items-center gap-3 sm:w-56 shrink-0">
                    <div class="flex flex-col items-start gap-1 w-full mb-2">
                        <p class="text-base font-semibold text-[#0A2540]">
                            Perfil
                        </p>
                        <p class="text-sm text-gray-400 leading-snug">
                            Estas preferencias solo se aplican a este usuario
                        </p>
                    </div>

                    <!-- Avatar -->
                    <div class="relative group">
                        <div
                            class="w-36 h-36 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden ring-4 ring-white shadow-md cursor-pointer"
                            @click="triggerAvatarInput"
                        >
                            <img
                                v-if="avatarPreview"
                                :src="avatarPreview"
                                alt="Foto de perfil"
                                class="w-full h-full object-cover"
                            />
                            <!-- Silueta por defecto -->
                            <svg
                                v-else
                                viewBox="0 0 100 100"
                                fill="none"
                                class="w-24 h-24 text-gray-400"
                            >
                                <circle
                                    cx="50"
                                    cy="35"
                                    r="20"
                                    fill="currentColor"
                                />
                                <ellipse
                                    cx="50"
                                    cy="85"
                                    rx="32"
                                    ry="22"
                                    fill="currentColor"
                                />
                            </svg>

                            <!-- Overlay hover -->
                            <div
                                class="absolute inset-0 rounded-full bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
                            >
                                <svg
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="text-white"
                                >
                                    <path
                                        d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    />
                                    <circle
                                        cx="12"
                                        cy="13"
                                        r="4"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    />
                                </svg>
                            </div>
                        </div>

                        <input
                            ref="avatarInputRef"
                            type="file"
                            accept="image/*"
                            class="sr-only"
                            @change="onAvatarChange"
                        />
                    </div>

                    <!-- Link editar -->
                    <button
                        type="button"
                        @click="triggerAvatarInput"
                        class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#1a5c2a] transition-colors"
                    >
                        Editar foto de perfil
                        <svg
                            width="13"
                            height="13"
                            viewBox="0 0 13 13"
                            fill="none"
                        >
                            <path
                                d="M9 1.5L11.5 4L4.5 11H2V8.5L9 1.5Z"
                                stroke="currentColor"
                                stroke-width="1.2"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>

                    <!-- Quitar foto -->
                    <button
                        v-if="avatarPreview"
                        type="button"
                        @click="clearAvatar"
                        class="text-xs text-red-400 hover:text-red-600 transition-colors"
                    >
                        Quitar foto
                    </button>
                </div>

                <!-- Divisor vertical -->
                <div class="hidden sm:block w-px bg-gray-100 self-stretch" />
                <div class="block sm:hidden h-px bg-gray-100 w-full" />

                <!-- Panel derecho: campos -->
                <div class="flex-1 flex flex-col gap-5">
                    <!-- Nombre + Apellidos -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label :class="labelClass">Nombre</label>
                            <input
                                v-model="form.nombre"
                                type="text"
                                placeholder="Tu nombre"
                                :class="inputClass"
                            />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label :class="labelClass">Apellidos</label>
                            <input
                                v-model="form.apellidos"
                                type="text"
                                placeholder="Tus apellidos"
                                :class="inputClass"
                            />
                        </div>
                    </div>

                    <!-- Empresa + Rol -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label :class="labelClass"
                                >Nombre de la empresa</label
                            >
                            <input
                                v-model="form.empresa"
                                type="text"
                                placeholder="Mi Empresa S.A.S"
                                :class="inputClass"
                            />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label :class="labelClass">Rol en la empresa</label>
                            <input
                                v-model="form.rol"
                                type="text"
                                placeholder="Administrador"
                                :class="inputClass"
                            />
                        </div>
                    </div>

                    <!-- Teléfono + Correo -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label :class="labelClass">Teléfono</label>
                            <input
                                v-model="form.telefono"
                                type="tel"
                                placeholder="300 123 4567"
                                :class="inputClass"
                            />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label :class="labelClass"
                                >Correo electrónico</label
                            >
                            <input
                                v-model="form.correo"
                                type="email"
                                placeholder="correo@empresa.com"
                                :class="[
                                    inputClass,
                                    'bg-gray-100 text-gray-400 cursor-not-allowed',
                                ]"
                                disabled
                            />
                        </div>
                    </div>

                    <!-- Alerta feedback -->
                    <transition name="fade">
                        <div
                            v-if="feedback.message"
                            :class="[
                                'px-4 py-3 rounded-lg text-sm border',
                                feedback.type === 'success'
                                    ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
                                    : 'bg-red-50 border-red-200 text-red-700',
                            ]"
                        >
                            {{ feedback.message }}
                        </div>
                    </transition>

                    <!-- Botón guardar -->
                    <div class="flex justify-end">
                        <button
                            type="button"
                            @click="handleSave"
                            :disabled="loading"
                            class="h-10 px-6 rounded-lg bg-emerald-500 hover:bg-emerald-600 disabled:bg-gray-300 text-white text-sm font-semibold transition-all flex items-center gap-2"
                        >
                            <svg
                                v-if="loading"
                                class="animate-spin w-4 h-4"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                />
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                />
                            </svg>
                            Guardar información
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import EyeIcon from '@/components/form/EyeIcon.vue'

import { ref, reactive, computed } from 'vue'

// ── Estilos compartidos ────────────────────────────────────────────────────
const labelClass = 'text-sm font-medium text-gray-500'
const inputClass =
    'w-full h-10 px-3 rounded-lg border border-gray-200 bg-gray-50 text-[#0A2540] text-sm outline-none transition-all placeholder:text-gray-300 focus:bg-white focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10'

// ── Avatar ─────────────────────────────────────────────────────────────────
const avatarInputRef = ref(null)
const avatarPreview = ref(null)
const avatarFile = ref(null)

function triggerAvatarInput() {
    avatarInputRef.value?.click()
}

function onAvatarChange(e) {
    const file = e.target.files[0]
    if (!file) return
    avatarFile.value = file
    avatarPreview.value = URL.createObjectURL(file)
}

function clearAvatar() {
    avatarPreview.value = null
    avatarFile.value = null
    if (avatarInputRef.value) avatarInputRef.value.value = ''
}

// ── Formulario perfil ──────────────────────────────────────────────────────
const form = reactive({
    nombre: 'Martin desarrollo',
    apellidos: '',
    empresa: '',
    rol: '',
    telefono: '',
    correo: 'martindesarrollo@creditransito.com',
})

const loading = ref(false)
const feedback = reactive({ message: '', type: '' })

async function handleSave() {
    loading.value = true
    feedback.message = ''
    try {
        const payload = new FormData()
        Object.entries(form).forEach(([k, v]) => payload.append(k, v ?? ''))
        if (avatarFile.value) payload.append('avatar', avatarFile.value)

        const response = await fetch('/api/perfil', {
            method: 'POST',
            headers: {
                Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
            },
            body: payload,
        })

        if (!response.ok) throw new Error()

        feedback.message = 'Información guardada correctamente.'
        feedback.type = 'success'
    } catch {
        feedback.message =
            'No se pudo guardar la información. Intenta nuevamente.'
        feedback.type = 'error'
    } finally {
        loading.value = false
        setTimeout(() => (feedback.message = ''), 4000)
    }
}

// ── Cambio de contraseña ───────────────────────────────────────────────────
const password = reactive({ actual: '', nueva: '', confirmar: '' })
const showPass = reactive({ actual: false, nueva: false, confirmar: false })
const loadingPass = ref(false)

const canChangePass = computed(
    () =>
        password.actual &&
        password.nueva.length >= 8 &&
        password.nueva === password.confirmar
)

const passwordStrength = computed(() => {
    const p = password.nueva
    let score = 0
    if (p.length >= 8) score++
    if (/[A-Z]/.test(p)) score++
    if (/[0-9]/.test(p)) score++
    if (/[^A-Za-z0-9]/.test(p)) score++
    const map = {
        0: {
            label: 'Muy débil',
            color: 'bg-red-400',
            textColor: 'text-red-400',
        },
        1: {
            label: 'Débil',
            color: 'bg-orange-400',
            textColor: 'text-orange-400',
        },
        2: {
            label: 'Regular',
            color: 'bg-yellow-400',
            textColor: 'text-yellow-500',
        },
        3: {
            label: 'Buena',
            color: 'bg-emerald-400',
            textColor: 'text-emerald-500',
        },
        4: {
            label: 'Excelente',
            color: 'bg-emerald-600',
            textColor: 'text-emerald-600',
        },
    }
    return { score, ...map[score] }
})

async function handleChangePassword() {
    loadingPass.value = true
    try {
        const response = await fetch('/api/perfil/password', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
            },
            body: JSON.stringify({
                current_password: password.actual,
                password: password.nueva,
                password_confirmation: password.confirmar,
            }),
        })
        if (!response.ok) throw new Error()
        password.actual = ''
        password.nueva = ''
        password.confirmar = ''
        feedback.message = 'Contraseña actualizada correctamente.'
        feedback.type = 'success'
    } catch {
        feedback.message = 'No se pudo cambiar la contraseña.'
        feedback.type = 'error'
    } finally {
        loadingPass.value = false
        setTimeout(() => (feedback.message = ''), 4000)
    }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
