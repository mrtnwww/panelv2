<template>
    <div class="min-h-screen flex font-sans">
        <!-- ── Panel izquierdo ── -->
        <div
            class="hidden lg:flex lg:flex-1 bg-[#0A2540] flex-col justify-between px-14 py-14 relative overflow-hidden"
        >
            <!-- Círculos decorativos -->
            <div
                class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-emerald-500/10 pointer-events-none"
            />
            <div
                class="absolute -bottom-20 -left-20 w-64 h-64 rounded-full bg-emerald-500/6 pointer-events-none"
            />

            <!-- Logo -->
            <div class="flex items-center gap-3 relative z-10">
                <span class="text-emerald-400 font-semibold text-4xl tracking-tight 2xl:text-8xl">
                    Credi<span class="text-white">gital</span>
                </span>
            </div>

            <!-- Contenido central -->
            <div class="relative z-10 flex flex-col gap-8">
                <h2 class="text-white text-3xl font-light leading-snug 2xl:text-5xl">
                    Administra tus clientes
                    <span class="text-emerald-400 font-semibold"
                        >de forma fácil y rápida</span
                    >
                </h2>

                <ul class="flex flex-col gap-4">
                    <li
                        v-for="feature in features"
                        :key="feature"
                        class="flex items-start gap-3"
                    >
                        <span
                            class="mt-2 w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"
                        />
                        <span class="text-white/60 text-sm leading-relaxed 2xl:text-xl">{{
                            feature
                        }}</span>
                    </li>
                </ul>
            </div>

            <!-- Footer izquierdo -->
            <p class="text-white/25 text-xs relative z-10">
                &copy; Todos los derechos reservados
            </p>
        </div>

        <!-- ── Panel derecho: formulario ── -->
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
                        to="/register"
                        class="text-emerald-600 font-medium hover:text-emerald-700 transition-colors"
                    >
                        Crear cuenta
                    </router-link>
                </p>
            </div>

            <!-- Alerta de error -->
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
                <div class="flex flex-col gap-1.5">
                    <label
                        for="email"
                        class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                    >
                        Correo electrónico
                    </label>
                    <div class="relative">
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="tucorreo@mail.com"
                            autocomplete="email"
                            required
                            :class="[
                                'w-full h-11 pl-4 pr-10 rounded-lg border bg-gray-50 text-[#0A2540] text-sm outline-none transition-all',
                                'placeholder:text-gray-300',
                                'focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15',
                                fieldErrors.email
                                    ? 'border-red-400'
                                    : 'border-gray-200 hover:border-gray-300',
                            ]"
                        />
                        <span
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"
                        >
                            <svg
                                width="16"
                                height="16"
                                viewBox="0 0 16 16"
                                fill="none"
                            >
                                <rect
                                    x="1.5"
                                    y="3.5"
                                    width="13"
                                    height="9"
                                    rx="1.5"
                                    stroke="currentColor"
                                    stroke-width="1.2"
                                />
                                <path
                                    d="M1.5 5L8 9L14.5 5"
                                    stroke="currentColor"
                                    stroke-width="1.2"
                                />
                            </svg>
                        </span>
                    </div>
                    <p v-if="fieldErrors.email" class="text-xs text-red-500">
                        {{ fieldErrors.email }}
                    </p>
                </div>

                <!-- Contraseña -->
                <div class="flex flex-col gap-1.5">
                    <label
                        for="password"
                        class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                    >
                        Contraseña
                    </label>
                    <div class="relative">
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                            :class="[
                                'w-full h-11 pl-4 pr-10 rounded-lg border bg-gray-50 text-[#0A2540] text-sm outline-none transition-all',
                                'placeholder:text-gray-300',
                                'focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15',
                                fieldErrors.password
                                    ? 'border-red-400'
                                    : 'border-gray-200 hover:border-gray-300',
                            ]"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500 transition-colors"
                            :aria-label="
                                showPassword
                                    ? 'Ocultar contraseña'
                                    : 'Mostrar contraseña'
                            "
                        >
                            <svg
                                v-if="!showPassword"
                                width="16"
                                height="16"
                                viewBox="0 0 16 16"
                                fill="none"
                            >
                                <ellipse
                                    cx="8"
                                    cy="8"
                                    rx="6.5"
                                    ry="4"
                                    stroke="currentColor"
                                    stroke-width="1.2"
                                />
                                <circle
                                    cx="8"
                                    cy="8"
                                    r="2"
                                    stroke="currentColor"
                                    stroke-width="1.2"
                                />
                            </svg>
                            <svg
                                v-else
                                width="16"
                                height="16"
                                viewBox="0 0 16 16"
                                fill="none"
                            >
                                <path
                                    d="M2 2L14 14"
                                    stroke="currentColor"
                                    stroke-width="1.2"
                                    stroke-linecap="round"
                                />
                                <path
                                    d="M6.5 4.5C7 4.2 7.5 4 8 4c3 0 5.5 4 5.5 4s-.7 1.4-2 2.5"
                                    stroke="currentColor"
                                    stroke-width="1.2"
                                    stroke-linecap="round"
                                />
                                <path
                                    d="M3.5 6C2.5 7 1.5 8 1.5 8s2.5 4 6.5 4c.8 0 1.6-.2 2.3-.5"
                                    stroke="currentColor"
                                    stroke-width="1.2"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </button>
                    </div>
                    <p v-if="fieldErrors.password" class="text-xs text-red-500">
                        {{ fieldErrors.password }}
                    </p>

                    <div class="flex justify-end mt-0.5">
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
                    <span>{{
                        loading ? "Ingresando..." : "Ingresar"
                    }}</span>
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
                to="/register"
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
import { ref, reactive } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();

// ── Estado del formulario ──
const form = reactive({
    email: "",
    password: "",
});

const showPassword = ref(false);
const loading = ref(false);
const errorMessage = ref("");
const fieldErrors = reactive({
    email: "",
    password: "",
});

// ── Features del panel izquierdo ──
const features = [
    "Crea créditos y registra abonos en segundos",
    "Configura recordatorios de pago automatizados",
    "Gestiona vencimientos y descarga informes detallados",
    "Tus clientes consultan sus cuotas desde la App",
];

// ── Lógica de login ──
async function handleLogin() {
    errorMessage.value = "";
    fieldErrors.email = "";
    fieldErrors.password = "";
    loading.value = true;

    try {
        // 1. CSRF cookie de Laravel Sanctum
        await fetch("/sanctum/csrf-cookie", { credentials: "include" });

        // 2. Petición de login
        const response = await fetch("/api/login", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-XSRF-TOKEN": getCookie("XSRF-TOKEN"),
            },
            credentials: "include",
            body: JSON.stringify({
                email: form.email,
                password: form.password,
            }),
        });

        const data = await response.json();

        // 3. Errores de validación (422)
        if (response.status === 422 && data.errors) {
            if (data.errors.email) fieldErrors.email = data.errors.email[0];
            if (data.errors.password)
                fieldErrors.password = data.errors.password[0];
            return;
        }

        // 4. Credenciales incorrectas
        if (!response.ok) {
            errorMessage.value =
                data.message || "Correo o contraseña incorrectos.";
            return;
        }

        // 5. Guardar token (si usas Sanctum con tokens)
        if (data.token) {
            localStorage.setItem("auth_token", data.token);
        }

        // 6. Redirigir
        router.push("/dashboard");
    } catch {
        errorMessage.value = "Error de conexión. Intenta nuevamente.";
    } finally {
        loading.value = false;
    }
}

// ── Leer cookie por nombre ──
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2)
        return decodeURIComponent(parts.pop().split(";").shift());
    return "";
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
