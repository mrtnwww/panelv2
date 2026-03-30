<template>
    <div class="flex flex-col gap-5 w-full">
        <!-- Encabezado -->
        <h1 class="text-lg font-semibold text-[#0A2540]">General</h1>
        <div class="bg-white rounded-xl border border-gray-200 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row gap-8">
                <!-- Descripción -->
                <div class="sm:w-56 shrink-0">
                    <p class="text-base font-semibold text-[#0A2540]">
                        Seguridad
                    </p>
                    <p class="text-sm text-gray-400 mt-1 leading-snug">
                        Actualiza tu contraseña de acceso
                    </p>
                </div>

                <div class="hidden sm:block w-px bg-gray-100 self-stretch" />
                <div class="block sm:hidden h-px bg-gray-100 w-full" />

                <!-- Campos contraseña -->
                <div class="flex-1 flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label :class="labelClass">Contraseña actual</label>
                        <div class="relative">
                            <input
                                v-model="password.actual"
                                :type="showPass.actual ? 'text' : 'password'"
                                placeholder="••••••••"
                                :class="inputClass"
                            />
                            <button
                                type="button"
                                @click="showPass.actual = !showPass.actual"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500 transition-colors"
                            >
                                <EyeIcon :open="!showPass.actual" />
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label :class="labelClass">Nueva contraseña</label>
                            <div class="relative">
                                <input
                                    v-model="password.nueva"
                                    :type="showPass.nueva ? 'text' : 'password'"
                                    placeholder="Mínimo 8 caracteres"
                                    :class="inputClass"
                                />
                                <button
                                    type="button"
                                    @click="showPass.nueva = !showPass.nueva"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500 transition-colors"
                                >
                                    <EyeIcon :open="!showPass.nueva" />
                                </button>
                            </div>
                            <!-- Indicador fortaleza -->
                            <template v-if="password.nueva.length > 0">
                                <div class="flex gap-1 mt-1">
                                    <div
                                        v-for="n in 4"
                                        :key="n"
                                        class="h-1 flex-1 rounded-full transition-all duration-300"
                                        :class="
                                            n <= passwordStrength.score
                                                ? passwordStrength.color
                                                : 'bg-gray-100'
                                        "
                                    />
                                </div>
                                <p
                                    class="text-xs"
                                    :class="passwordStrength.textColor"
                                >
                                    {{ passwordStrength.label }}
                                </p>
                            </template>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label :class="labelClass"
                                >Confirmar contraseña</label
                            >
                            <div class="relative">
                                <input
                                    v-model="password.confirmar"
                                    :type="
                                        showPass.confirmar ? 'text' : 'password'
                                    "
                                    placeholder="Repite la contraseña"
                                    :class="[
                                        inputClass,
                                        password.confirmar &&
                                        password.nueva !== password.confirmar
                                            ? 'border-red-400'
                                            : '',
                                    ]"
                                />
                                <button
                                    type="button"
                                    @click="
                                        showPass.confirmar = !showPass.confirmar
                                    "
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500 transition-colors"
                                >
                                    <EyeIcon :open="!showPass.confirmar" />
                                </button>
                            </div>
                            <p
                                v-if="
                                    password.confirmar &&
                                    password.nueva !== password.confirmar
                                "
                                class="text-xs text-red-500"
                            >
                                Las contraseñas no coinciden
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="button"
                            @click="handleChangePassword"
                            :disabled="loadingPass || !canChangePass"
                            class="h-10 px-6 rounded-lg bg-[#1a5c2a] hover:bg-[#154d22] disabled:bg-gray-300 text-white text-sm font-semibold transition-all flex items-center gap-2"
                        >
                            <svg
                                v-if="loadingPass"
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
                            Cambiar contraseña
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

// -- Estilos compartidos ---------------------------
const labelClass = 'text-xs font-medium text-gray-500 uppercase tracking-wide'
const inputClass =
    'w-full h-10 px-3 rounded-lg border border-gray-200 bg-gray-50 text-[#0A2540] text-sm outline-none transition-all placeholder:text-gray-300 focus:bg-white focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10'

const feedback = reactive({ message: '', type: '' })

// -- Cambio de contraseña ----------------------------------------
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
