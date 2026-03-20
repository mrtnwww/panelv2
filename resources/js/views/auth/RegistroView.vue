<template>
    <div class="min-h-screen flex font-sans h-screen">
        <!-- ── Panel izquierdo ── -->
        <AuthPanelLeft />

        <!-- ── Panel derecho ── -->
        <div
            class="w-full lg:w-160 bg-white flex flex-col px-10 py-10 overflow-y-auto h-screen"
        >
            <!-- Logo móvil -->
            <div class="flex lg:hidden items-center gap-3 mb-8">
                <span
                    class="text-emerald-500 font-semibold text-2xl tracking-tight"
                >
                    Credi<span class="text-[#0A2540]">gital</span>
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
                        to="/login"
                        class="text-emerald-600 font-medium hover:text-emerald-700 transition-colors"
                    >
                        Iniciar sesión
                    </router-link>
                </p>
            </div>

            <!-- Alertas -->
            <transition name="fade">
                <div
                    v-if="errorMessage"
                    class="mb-5 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"
                >
                    {{ errorMessage }}
                </div>
            </transition>
            <transition name="fade">
                <div
                    v-if="successMessage"
                    class="mb-5 px-4 py-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm"
                >
                    {{ successMessage }}
                </div>
            </transition>

            <!-- ══ Formulario ══ -->
            <form @submit.prevent="handleRegister" class="flex flex-col gap-7">
                <!-- ─ Sección 1: Datos de la empresa ─ -->
                <section class="flex flex-col gap-4">
                    <SectionTitle title="Datos de tu empresa" />

                    <!-- Razón social -->
                    <FormField
                        label="Razón social"
                        :error="fieldErrors.business_name"
                    >
                        <input
                            v-model="form.business_name"
                            type="text"
                            placeholder="Mi Empresa S.A.S"
                            required
                            :class="inputClass(fieldErrors.business_name)"
                        />
                    </FormField>

                    <!-- NIT -->
                    <FormField label="NIT" :error="fieldErrors.nit">
                        <input
                            v-model="form.nit"
                            type="text"
                            placeholder="900.123.456-7"
                            required
                            :class="inputClass(fieldErrors.nit)"
                        />
                    </FormField>

                    <!-- Dirección -->
                    <FormField label="Dirección" :error="fieldErrors.address">
                        <input
                            v-model="form.address"
                            type="text"
                            placeholder="Calle 123 # 45-67"
                            required
                            :class="inputClass(fieldErrors.address)"
                        />
                    </FormField>

                    <!-- Municipio / Provincia -->
                    <FormField
                        label="Municipio / Provincia"
                        :error="fieldErrors.municipality"
                    >
                        <div class="relative">
                            <select
                                v-model="form.municipality"
                                required
                                :class="[
                                    inputClass(fieldErrors.municipality),
                                    'appearance-none pr-10 cursor-pointer',
                                ]"
                            >
                                <option value="" disabled>
                                    Seleccione municipio/provincia
                                </option>
                                <option
                                    v-for="m in municipalities"
                                    :key="m"
                                    :value="m"
                                >
                                    {{ m }}
                                </option>
                            </select>
                            <span
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"
                            >
                                <svg
                                    width="14"
                                    height="14"
                                    viewBox="0 0 16 16"
                                    fill="none"
                                >
                                    <path
                                        d="M4 6L8 10L12 6"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                    />
                                </svg>
                            </span>
                        </div>
                    </FormField>

                    <!-- Teléfono de contacto clientes y Email clientes en grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <FormField
                            label="Teléfono para clientes"
                            :error="fieldErrors.contact_phone"
                        >
                            <div class="flex gap-2">
                                <div
                                    class="flex items-center gap-1 h-11 px-3 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-400 shrink-0"
                                >
                                    🇨🇴 +57
                                </div>
                                <input
                                    v-model="form.contact_phone"
                                    type="tel"
                                    placeholder="300 123 4567"
                                    required
                                    :class="[
                                        inputClass(fieldErrors.contact_phone),
                                        'flex-1 min-w-0',
                                    ]"
                                />
                            </div>
                        </FormField>

                        <FormField
                            label="Correo para clientes"
                            :error="fieldErrors.contact_email"
                        >
                            <input
                                v-model="form.contact_email"
                                type="email"
                                placeholder="contacto@miempresa.com"
                                required
                                :class="inputClass(fieldErrors.contact_email)"
                            />
                        </FormField>
                    </div>
                </section>

                <!-- ─ Sección 2: Tipos de préstamo ─ -->
                <section class="flex flex-col gap-3">
                    <SectionTitle title="Tipos de préstamo a realizar" />

                    <div class="flex flex-col gap-2.5">
                        <label
                            v-for="loan in loanTypes"
                            :key="loan.value"
                            class="flex items-center gap-3 cursor-pointer group"
                        >
                            <div class="relative shrink-0">
                                <input
                                    v-model="form.loan_types"
                                    type="checkbox"
                                    :value="loan.value"
                                    class="peer sr-only"
                                />
                                <div
                                    class="w-5 h-5 rounded border-2 border-gray-200 bg-gray-50 peer-checked:bg-emerald-500 peer-checked:border-emerald-500 transition-all flex items-center justify-center"
                                >
                                    <svg
                                        v-if="
                                            form.loan_types.includes(loan.value)
                                        "
                                        width="10"
                                        height="10"
                                        viewBox="0 0 10 10"
                                        fill="none"
                                    >
                                        <path
                                            d="M1.5 5L4 7.5L8.5 2.5"
                                            stroke="white"
                                            stroke-width="1.5"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </div>
                            </div>
                            <span
                                class="text-sm text-gray-600 group-hover:text-gray-800 transition-colors leading-tight"
                            >
                                {{ loan.label }}
                            </span>
                        </label>
                    </div>
                    <p
                        v-if="fieldErrors.loan_types"
                        class="text-xs text-red-500 mt-1"
                    >
                        {{ fieldErrors.loan_types }}
                    </p>
                </section>

                <!-- ─ Sección 3: Logo de la empresa ─ -->
                <section class="flex flex-col gap-3">
                    <SectionTitle title="Logo de tu empresa" />

                    <label
                        class="flex flex-col items-center justify-center gap-2 w-full h-28 border-2 border-dashed border-gray-200 rounded-xl cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/40 transition-all group"
                        :class="{
                            'border-emerald-400 bg-emerald-50/40': logoPreview,
                        }"
                    >
                        <input
                            type="file"
                            accept="image/*"
                            class="sr-only"
                            @change="handleLogoUpload"
                        />

                        <!-- Preview -->
                        <template v-if="logoPreview">
                            <img
                                :src="logoPreview"
                                alt="Logo preview"
                                class="h-14 object-contain rounded"
                            />
                            <span
                                class="text-xs text-emerald-600 font-medium"
                                >{{ logoFileName }}</span
                            >
                        </template>

                        <!-- Placeholder -->
                        <template v-else>
                            <svg
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                class="text-gray-300 group-hover:text-emerald-400 transition-colors"
                            >
                                <path
                                    d="M21 15V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V15"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                />
                                <path
                                    d="M17 8L12 3L7 8"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="M12 3V15"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                />
                            </svg>
                            <span
                                class="text-xs text-gray-400 group-hover:text-emerald-500 transition-colors"
                            >
                                Haz clic para subir el logo
                            </span>
                            <span class="text-xs text-gray-300"
                                >PNG, JPG o SVG — máx. 2MB</span
                            >
                        </template>
                    </label>
                </section>

                <!-- ─ Sección 4: Representante legal ─ -->
                <section class="flex flex-col gap-4">
                    <SectionTitle title="Datos del representante legal" />

                    <FormField
                        label="Nombre completo"
                        :error="fieldErrors.legal_name"
                    >
                        <input
                            v-model="form.legal_name"
                            type="text"
                            placeholder="Nombre del representante legal"
                            required
                            :class="inputClass(fieldErrors.legal_name)"
                        />
                    </FormField>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <FormField
                            label="Teléfono de contacto"
                            :error="fieldErrors.legal_phone"
                        >
                            <input
                                v-model="form.legal_phone"
                                type="tel"
                                placeholder="300 123 4567"
                                required
                                :class="inputClass(fieldErrors.legal_phone)"
                            />
                        </FormField>

                        <FormField
                            label="Número de cédula"
                            :error="fieldErrors.legal_id"
                        >
                            <input
                                v-model="form.legal_id"
                                type="text"
                                placeholder="1.234.567.890"
                                required
                                :class="inputClass(fieldErrors.legal_id)"
                            />
                        </FormField>
                    </div>
                </section>

                <!-- ─ Sección 5: Acceso a la cuenta ─ -->
                <section class="flex flex-col gap-4">
                    <SectionTitle title="Acceso a tu cuenta" />

                    <FormField
                        label="Correo electrónico"
                        :error="fieldErrors.email"
                    >
                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="tucorreo@empresa.com"
                            autocomplete="email"
                            required
                            :class="inputClass(fieldErrors.email)"
                        />
                    </FormField>

                    <FormField label="Contraseña" :error="fieldErrors.password">
                        <div class="relative">
                            <input
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
                                <EyeIcon :open="!showPassword" />
                            </button>
                        </div>
                        <!-- Indicador de fortaleza -->
                        <template v-if="form.password.length > 0">
                            <div class="flex gap-1 mt-2">
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
                                class="text-xs mt-1"
                                :class="passwordStrength.textColor"
                            >
                                {{ passwordStrength.label }}
                            </p>
                        </template>
                    </FormField>

                    <FormField
                        label="Confirmar contraseña"
                        :error="fieldErrors.password_confirmation"
                    >
                        <div class="relative">
                            <input
                                v-model="form.password_confirmation"
                                :type="showConfirm ? 'text' : 'password'"
                                placeholder="Repite tu contraseña"
                                autocomplete="new-password"
                                required
                                :class="
                                    inputClass(
                                        fieldErrors.password_confirmation
                                    )
                                "
                            />
                            <button
                                type="button"
                                @click="showConfirm = !showConfirm"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500 transition-colors"
                            >
                                <EyeIcon :open="!showConfirm" />
                            </button>
                        </div>
                    </FormField>
                </section>

                <!-- Términos -->
                <label class="flex items-start gap-3 cursor-pointer -mt-1">
                    <input
                        v-model="form.terms"
                        type="checkbox"
                        required
                        class="mt-0.5 w-4 h-4 rounded border-gray-300 accent-emerald-600 shrink-0"
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
                            >Términos de uso</a
                        >
                        de Credigital
                    </span>
                </label>

                <!-- Botón submit -->
                <button
                    type="submit"
                    :disabled="loading || !form.terms"
                    class="w-full h-11 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg transition-all duration-150 flex items-center justify-center gap-2"
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
                        loading ? 'Creando cuenta...' : 'Crear mi cuenta'
                    }}</span>
                </button>
            </form>

            <p class="text-center text-xs text-gray-300 mt-6 mb-2">
                ¿Ya tienes cuenta?
                <router-link
                    to="/login"
                    class="text-emerald-600 hover:underline font-medium"
                >
                    Iniciar sesión
                </router-link>
            </p>
        </div>
    </div>
</template>

<script setup>
import AuthPanelLeft from '@/components/AuthPanelLeft.vue'
import SectionTitle from '@/components/form/SectionTitle.vue'
import FormField from '@/components/form/FormField.vue'

import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// ── Estado ──────────────────────────────────────────────────────────────────

const form = reactive({
    // Empresa
    business_name: '',
    nit: '',
    address: '',
    municipality: '',
    contact_phone: '',
    contact_email: '',
    // Préstamos
    loan_types: [],
    // Logo
    logo: null,
    // Representante legal
    legal_name: '',
    legal_phone: '',
    legal_id: '',
    // Acceso
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
})

const showPassword = ref(false)
const showConfirm = ref(false)
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const logoPreview = ref('')
const logoFileName = ref('')

const fieldErrors = reactive({
    business_name: '',
    nit: '',
    address: '',
    municipality: '',
    contact_phone: '',
    contact_email: '',
    loan_types: '',
    legal_name: '',
    legal_phone: '',
    legal_id: '',
    email: '',
    password: '',
    password_confirmation: '',
})

// ── Datos estáticos ─────────────────────────────────────────────────────────

const loanTypes = [
    { value: 'natural', label: 'Crédito a personas naturales' },
    {
        value: 'vivienda',
        label: 'Crédito con respaldo de vivienda (hipoteca o leaseback)',
    },
    { value: 'vehiculos', label: 'Crédito con respaldo de vehículos' },
]

const municipalities = [
    'Bogotá D.C.',
    'Medellín',
    'Cali',
    'Barranquilla',
    'Cartagena',
    'Cúcuta',
    'Bucaramanga',
    'Pereira',
    'Santa Marta',
    'Ibagué',
    'Pasto',
    'Manizales',
    'Neiva',
    'Villavicencio',
    'Armenia',
    'Valledupar',
    'Montería',
    'Sincelejo',
    'Popayán',
    'Tunja',
]

// ── Fortaleza de contraseña ──────────────────────────────────────────────────

const passwordStrength = computed(() => {
    const p = form.password
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

// ── Helpers ──────────────────────────────────────────────────────────────────

function inputClass(error) {
    return [
        'w-full h-11 pl-4 pr-3 rounded-lg border bg-gray-50 text-[#0A2540] text-sm outline-none transition-all',
        'placeholder:text-gray-300',
        'focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15',
        error ? 'border-red-400' : 'border-gray-200 hover:border-gray-300',
    ]
}

function handleLogoUpload(e) {
    const file = e.target.files[0]
    if (!file) return
    form.logo = file
    logoFileName.value = file.name
    logoPreview.value = URL.createObjectURL(file)
}

function getCookie(name) {
    const value = `; ${document.cookie}`
    const parts = value.split(`; ${name}=`)
    if (parts.length === 2)
        return decodeURIComponent(parts.pop().split(';').shift())
    return ''
}

// ── Submit ───────────────────────────────────────────────────────────────────

async function handleRegister() {
    errorMessage.value = ''
    successMessage.value = ''
    Object.keys(fieldErrors).forEach(k => (fieldErrors[k] = ''))

    if (form.loan_types.length === 0) {
        fieldErrors.loan_types = 'Selecciona al menos un tipo de préstamo.'
        return
    }

    if (form.password !== form.password_confirmation) {
        fieldErrors.password_confirmation = 'Las contraseñas no coinciden.'
        return
    }

    loading.value = true

    try {
        await fetch('/sanctum/csrf-cookie', { credentials: 'include' })

        // Usamos FormData para poder enviar el logo como archivo
        const payload = new FormData()
        Object.entries(form).forEach(([key, val]) => {
            if (key === 'loan_types') {
                val.forEach(v => payload.append('loan_types[]', v))
            } else if (key === 'logo' && val) {
                payload.append('logo', val)
            } else {
                payload.append(key, val)
            }
        })

        const response = await fetch('/api/register', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
            },
            credentials: 'include',
            body: payload,
        })

        const data = await response.json()

        if (response.status === 422 && data.errors) {
            Object.keys(data.errors).forEach(key => {
                const k = key.replace('[]', '')
                if (fieldErrors[k] !== undefined)
                    fieldErrors[k] = data.errors[key][0]
            })
            return
        }

        if (!response.ok) {
            errorMessage.value =
                data.message ||
                'No se pudo crear la cuenta. Intenta nuevamente.'
            return
        }

        if (data.token) localStorage.setItem('auth_token', data.token)

        successMessage.value = '¡Cuenta creada con éxito! Redirigiendo...'
        setTimeout(() => router.push('/dashboard'), 1500)
    } catch {
        errorMessage.value = 'Error de conexión. Intenta nuevamente.'
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
html,
body {
    height: 100%;
    overflow: hidden;
}

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
