<template>
    <div class="min-h-screen flex font-sans">
        <!-- ── Panel izquierdo ── -->
        <AuthPanelLeft />

        <!-- ── Panel derecho ── -->
        <div
            class="w-full lg:w-160 bg-white flex flex-col justify-center px-10 py-14"
        >
            <!-- Logo móvil -->
            <div class="flex lg:hidden items-center gap-3 mb-10">
                <span
                    class="text-emerald-400 font-semibold text-3xl tracking-tight"
                >
                    Credi<span class="text-[#0A2540]">gital</span>
                </span>
            </div>

            <!-- Encabezado -->
            <div class="mb-8">
                <h1 class="text-[#0A2540] text-2xl font-semibold mb-1">
                    Iniciar sesión
                </h1>
                <p class="text-gray-400 text-sm">
                    ¿Aún no tienes cuenta?
                    <router-link
                        to="/registro"
                        class="text-emerald-600 font-medium hover:text-emerald-700 transition-colors"
                    >
                        Crear cuenta
                    </router-link>
                </p>
            </div>

            <!-- Alerta de error general -->
            <transition name="fade">
                <div
                    v-if="errorMessage"
                    class="mb-5 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"
                >
                    {{ errorMessage }}
                </div>
            </transition>

            <!-- Formulario -->
            <form @submit.prevent="handleLogin" class="flex flex-col gap-5">
                <!-- Email -->
                <FormInput
                    label="Correo electrónico"
                    type="email"
                    v-model="form.email"
                    placeholder="tucorreo@mail.com"
                    size="lg"
                    :error="fieldErrors.email"
                    autocomplete="email"
                    required
                >
                    <template #icon-right>
                        <i class="fa-regular fa-envelope text-gray-300" />
                    </template>
                </FormInput>

                <!-- Contraseña -->
                <div class="flex flex-col gap-1.5">
                    <FormInput
                        label="Contraseña"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="form.password"
                        placeholder="••••••••"
                        size="lg"
                        :error="fieldErrors.password"
                        autocomplete="current-password"
                        required
                    >
                        <template #icon-right>
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="text-gray-300 hover:text-gray-500 transition-colors"
                                :aria-label="
                                    showPassword
                                        ? 'Ocultar contraseña'
                                        : 'Mostrar contraseña'
                                "
                            >
                                <EyeIcon :open="!showPassword" />
                            </button>
                        </template>
                    </FormInput>

                    <div class="flex justify-end">
                        <router-link
                            to="/forgot-password"
                            class="text-xs text-emerald-600 hover:text-emerald-700 font-medium transition-colors"
                        >
                            ¿Olvidaste tu contraseña?
                        </router-link>
                    </div>
                </div>

                <!-- Botón principal -->
                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full h-11 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg transition-all duration-150 flex items-center justify-center gap-2 mt-1 cursor-pointer"
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
                    <span>{{ loading ? 'Ingresando...' : 'Ingresar' }}</span>
                </button>
            </form>

            <!-- Divisor -->
            <div class="flex items-center gap-3 my-5">
                <div class="flex-1 h-px bg-gray-100" />
                <span class="text-xs text-gray-300">o</span>
                <div class="flex-1 h-px bg-gray-100" />
            </div>

            <!-- Botón registrarse -->
            <router-link
                to="/registro"
                class="w-full h-11 flex items-center justify-center border border-gray-200 hover:border-gray-300 hover:bg-gray-50 text-[#0A2540] text-sm font-medium rounded-lg transition-all"
            >
                Crear una cuenta nueva
            </router-link>

            <!-- Política de privacidad -->
            <p class="text-center text-xs text-gray-400 mt-6">
                Al acceder, aceptas la
                <a
                    href="/politica-privacidad.pdf"
                    target="_blank"
                    class="text-emerald-600 hover:underline"
                >
                    Política de privacidad
                </a>
                de Credigital
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'

// -- Componentes ----------------------------------------
import AuthPanelLeft from '@/components/AuthPanelLeft.vue'
import FormInput from '@/components/form/FormInput.vue'
import EyeIcon from '@/components/form/EyeIcon.vue'

const router = useRouter()

const form = reactive({ email: '', password: '' })
const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')
const fieldErrors = reactive({ email: '', password: '' })

async function handleLogin() {
    errorMessage.value = ''
    fieldErrors.email = ''
    fieldErrors.password = ''
    loading.value = true

    try {
        // await fetch('/sanctum/csrf-cookie', { credentials: 'include' })

        // const response = await fetch('/api/login', {
        //     method: 'POST',
        //     headers: {
        //         'Content-Type': 'application/json',
        //         Accept: 'application/json',
        //         'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
        //     },
        //     credentials: 'include',
        //     body: JSON.stringify({
        //         email: form.email,
        //         password: form.password,
        //     }),
        // })

        // const data = await response.json()

        // if (response.status === 422 && data.errors) {
        //     if (data.errors.email) fieldErrors.email = data.errors.email[0]
        //     if (data.errors.password)
        //         fieldErrors.password = data.errors.password[0]
        //     return
        // }

        // if (!response.ok) {
        //     errorMessage.value =
        //         data.message || 'Correo o contraseña incorrectos.'
        //     return
        // }

        // if (data.token) localStorage.setItem('auth_token', data.token)

        router.push('/dashboard')
    } catch {
        errorMessage.value = 'Error de conexión. Intenta nuevamente.'
    } finally {
        loading.value = false
    }
}

function getCookie(name) {
    const value = `; ${document.cookie}`
    const parts = value.split(`; ${name}=`)
    if (parts.length === 2)
        return decodeURIComponent(parts.pop().split(';').shift())
    return ''
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
