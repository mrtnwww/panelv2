<template>
    <div class="min-h-screen flex font-sans">
        <!-- ── Panel izquierdo ── -->
        <AuthPanelLeft />

        <!-- ── Panel derecho: formulario ── -->
        <div
            class="w-full lg:w-160 bg-white flex flex-col justify-center px-10 py-12 overflow-y-auto"
        >
            <!-- Logo móvil -->
            <div class="flex lg:hidden items-center gap-3 mb-8">
                <div
                    class="w-9 h-9 bg-emerald-500 rounded-xl flex items-center justify-center"
                >
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path
                            d="M10 2L17 6V14L10 18L3 14V6L10 2Z"
                            stroke="white"
                            stroke-width="1.5"
                            fill="none"
                        />
                        <path
                            d="M10 6L14 8.5V13L10 15.5L6 13V8.5L10 6Z"
                            fill="white"
                            opacity="0.9"
                        />
                    </svg>
                </div>
                <span
                    class="text-[#0A2540] font-semibold text-xl tracking-tight"
                >
                    Credi<span class="text-emerald-500">digital</span>
                </span>
            </div>

            <!-- Encabezado -->
            <div class="mb-7">
                <h1 class="text-[#0A2540] text-2xl font-semibold mb-1">
                    Crear cuenta
                </h1>
                <p class="text-gray-400 text-sm">
                    ¿Ya tienes cuenta?
                    <router-link
                        to="/"
                        class="text-emerald-600 font-medium hover:text-emerald-700 transition-colors"
                    >
                        Iniciar sesión
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

            <!-- Alerta de éxito -->
            <transition name="fade">
                <div
                    v-if="successMessage"
                    class="mb-5 px-4 py-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm"
                >
                    {{ successMessage }}
                </div>
            </transition>

            <!-- Formulario -->
            <form @submit.prevent="handleRegister" class="flex flex-col gap-4">
                <!-- Nombre y apellido -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col gap-1.5">
                        <label
                            for="first_name"
                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                        >
                            Nombre
                        </label>
                        <input
                            id="first_name"
                            v-model="form.first_name"
                            type="text"
                            placeholder="Juan"
                            autocomplete="given-name"
                            required
                            :class="inputClass(fieldErrors.first_name)"
                        />
                        <p
                            v-if="fieldErrors.first_name"
                            class="text-xs text-red-500"
                        >
                            {{ fieldErrors.first_name }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label
                            for="last_name"
                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                        >
                            Apellido
                        </label>
                        <input
                            id="last_name"
                            v-model="form.last_name"
                            type="text"
                            placeholder="Pérez"
                            autocomplete="family-name"
                            required
                            :class="inputClass(fieldErrors.last_name)"
                        />
                        <p
                            v-if="fieldErrors.last_name"
                            class="text-xs text-red-500"
                        >
                            {{ fieldErrors.last_name }}
                        </p>
                    </div>
                </div>

                <!-- Nombre del negocio -->
                <div class="flex flex-col gap-1.5">
                    <label
                        for="business_name"
                        class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                    >
                        Nombre del negocio
                    </label>
                    <div class="relative">
                        <input
                            id="business_name"
                            v-model="form.business_name"
                            type="text"
                            placeholder="Mi Tienda S.A.S"
                            required
                            :class="inputClass(fieldErrors.business_name)"
                        />
                        <span
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"
                        >
                            <svg
                                width="15"
                                height="15"
                                viewBox="0 0 16 16"
                                fill="none"
                            >
                                <path
                                    d="M2 6.5L8 2L14 6.5V14H10V10H6V14H2V6.5Z"
                                    stroke="currentColor"
                                    stroke-width="1.2"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>
                    </div>
                    <p
                        v-if="fieldErrors.business_name"
                        class="text-xs text-red-500"
                    >
                        {{ fieldErrors.business_name }}
                    </p>
                </div>

                <!-- Teléfono -->
                <div class="flex flex-col gap-1.5">
                    <label
                        for="phone"
                        class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                    >
                        Teléfono / WhatsApp
                    </label>
                    <div class="flex gap-2">
                        <div
                            class="flex items-center gap-1.5 h-11 px-3 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-500 flex-shrink-0"
                        >
                            <span>🇨🇴</span>
                            <span class="text-gray-400">+57</span>
                        </div>
                        <input
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            placeholder="300 123 4567"
                            autocomplete="tel"
                            required
                            :class="[inputClass(fieldErrors.phone), 'flex-1']"
                        />
                    </div>
                    <p v-if="fieldErrors.phone" class="text-xs text-red-500">
                        {{ fieldErrors.phone }}
                    </p>
                </div>

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
                            placeholder="tucorreo@empresa.com"
                            autocomplete="email"
                            required
                            :class="inputClass(fieldErrors.email)"
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
                            placeholder="Mínimo 8 caracteres"
                            autocomplete="new-password"
                            required
                            :class="inputClass(fieldErrors.password)"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500 transition-colors"
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

                    <!-- Indicador de fortaleza -->
                    <div
                        v-if="form.password.length > 0"
                        class="flex gap-1 mt-1"
                    >
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
                        v-if="form.password.length > 0"
                        class="text-xs"
                        :class="passwordStrength.textColor"
                    >
                        {{ passwordStrength.label }}
                    </p>
                    <p v-if="fieldErrors.password" class="text-xs text-red-500">
                        {{ fieldErrors.password }}
                    </p>
                </div>

                <!-- Confirmar contraseña -->
                <div class="flex flex-col gap-1.5">
                    <label
                        for="password_confirmation"
                        class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                    >
                        Confirmar contraseña
                    </label>
                    <div class="relative">
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :type="showConfirm ? 'text' : 'password'"
                            placeholder="Repite tu contraseña"
                            autocomplete="new-password"
                            required
                            :class="
                                inputClass(fieldErrors.password_confirmation)
                            "
                        />
                        <button
                            type="button"
                            @click="showConfirm = !showConfirm"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500 transition-colors"
                        >
                            <svg
                                v-if="!showConfirm"
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
                    <p
                        v-if="fieldErrors.password_confirmation"
                        class="text-xs text-red-500"
                    >
                        {{ fieldErrors.password_confirmation }}
                    </p>
                </div>

                <!-- Términos -->
                <label class="flex items-start gap-3 cursor-pointer mt-1">
                    <input
                        v-model="form.terms"
                        type="checkbox"
                        required
                        class="mt-0.5 w-4 h-4 rounded border-gray-300 text-emerald-600 accent-emerald-600 flex-shrink-0"
                    />
                    <span class="text-xs text-gray-400 leading-relaxed">
                        Acepto la
                        <a
                            href="/politica-privacidad.pdf"
                            target="_blank"
                            class="text-emerald-600 hover:underline font-medium"
                        >
                            Política de privacidad
                        </a>
                        y los
                        <a
                            href="#"
                            class="text-emerald-600 hover:underline font-medium"
                        >
                            Términos de uso
                        </a>
                        de Credigital
                    </span>
                </label>

                <!-- Botón principal -->
                <button
                    type="submit"
                    :disabled="loading || !form.terms"
                    class="w-full h-11 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg transition-all duration-150 flex items-center justify-center gap-2 mt-2"
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
                        loading ? "Creando cuenta..." : "Crear mi cuenta"
                    }}</span>
                </button>
            </form>

            <!-- Link login -->
            <p class="text-center text-xs text-gray-300 mt-6">
                ¿Ya tienes cuenta?
                <router-link
                    to="/"
                    class="text-emerald-600 hover:underline font-medium"
                >
                    Iniciar sesión
                </router-link>
            </p>
        </div>
    </div>
</template>

<script setup>
import AuthPanelLeft from "@/components/AuthPanelLeft.vue";

import { ref, reactive, computed } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();

// ── Estado del formulario ──
const form = reactive({
    first_name: "",
    last_name: "",
    business_name: "",
    phone: "",
    email: "",
    password: "",
    password_confirmation: "",
    terms: false,
});

const showPassword = ref(false);
const showConfirm = ref(false);
const loading = ref(false);
const errorMessage = ref("");
const successMessage = ref("");

const fieldErrors = reactive({
    first_name: "",
    last_name: "",
    business_name: "",
    phone: "",
    email: "",
    password: "",
    password_confirmation: "",
});

// ── Pasos del panel izquierdo ──
const steps = [
    {
        title: "Crea tu cuenta",
        desc: "Regístrate con tu correo y datos del negocio en segundos.",
    },
    {
        title: "Agrega tus clientes",
        desc: "Importa o crea tu cartera de clientes desde el panel.",
    },
    {
        title: "Crea créditos y abonos",
        desc: "Registra préstamos, cuotas y pagos de forma automática.",
    },
    {
        title: "Cobra con recordatorios",
        desc: "Notificaciones automáticas por WhatsApp y la App.",
    },
];

// ── Fortaleza de contraseña ──
const passwordStrength = computed(() => {
    const p = form.password;
    let score = 0;
    if (p.length >= 8) score++;
    if (/[A-Z]/.test(p)) score++;
    if (/[0-9]/.test(p)) score++;
    if (/[^A-Za-z0-9]/.test(p)) score++;

    const map = {
        0: {
            label: "Muy débil",
            color: "bg-red-400",
            textColor: "text-red-400",
        },
        1: {
            label: "Débil",
            color: "bg-orange-400",
            textColor: "text-orange-400",
        },
        2: {
            label: "Regular",
            color: "bg-yellow-400",
            textColor: "text-yellow-500",
        },
        3: {
            label: "Buena",
            color: "bg-emerald-400",
            textColor: "text-emerald-500",
        },
        4: {
            label: "Excelente",
            color: "bg-emerald-600",
            textColor: "text-emerald-600",
        },
    };
    return { score, ...map[score] };
});

// ── Helper clases de input ──
function inputClass(error) {
    return [
        "w-full h-11 pl-4 pr-10 rounded-lg border bg-gray-50 text-[#0A2540] text-sm outline-none transition-all",
        "placeholder:text-gray-300",
        "focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15",
        error ? "border-red-400" : "border-gray-200 hover:border-gray-300",
    ];
}

// ── Lógica de registro ──
async function handleRegister() {
    // Limpiar errores
    errorMessage.value = "";
    successMessage.value = "";
    Object.keys(fieldErrors).forEach((k) => (fieldErrors[k] = ""));

    // Validar contraseñas coincidan
    if (form.password !== form.password_confirmation) {
        fieldErrors.password_confirmation = "Las contraseñas no coinciden.";
        return;
    }

    loading.value = true;

    try {
        await fetch("/sanctum/csrf-cookie", { credentials: "include" });

        const response = await fetch("/api/register", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-XSRF-TOKEN": getCookie("XSRF-TOKEN"),
            },
            credentials: "include",
            body: JSON.stringify({
                first_name: form.first_name,
                last_name: form.last_name,
                business_name: form.business_name,
                phone: form.phone,
                email: form.email,
                password: form.password,
                password_confirmation: form.password_confirmation,
            }),
        });

        const data = await response.json();

        // Errores de validación (422)
        if (response.status === 422 && data.errors) {
            Object.keys(data.errors).forEach((key) => {
                if (fieldErrors[key] !== undefined) {
                    fieldErrors[key] = data.errors[key][0];
                }
            });
            return;
        }

        if (!response.ok) {
            errorMessage.value =
                data.message ||
                "No se pudo crear la cuenta. Intenta nuevamente.";
            return;
        }

        // Guardar token si viene en la respuesta
        if (data.token) {
            localStorage.setItem("auth_token", data.token);
        }

        successMessage.value = "¡Cuenta creada con éxito! Redirigiendo...";
        setTimeout(() => router.push("/dashboard"), 1500);
    } catch {
        errorMessage.value = "Error de conexión. Intenta nuevamente.";
    } finally {
        loading.value = false;
    }
}

// ── Leer cookie ──
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
