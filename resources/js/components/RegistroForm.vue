<template>
    <form @submit.prevent="handleRegister" class="flex flex-col gap-7">
        <!-- Alertas -->
        <transition name="fade">
            <div
                v-if="errorMessage"
                class="px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"
            >
                {{ errorMessage }}
            </div>
        </transition>

        <!-- Todas las secciones del formulario igual que las tienes... -->
        <section class="flex flex-col gap-4">
            <SectionTitle title="Datos de la empresa" />

            <FormInput
                label="Razón social"
                v-model="form.business_name"
                placeholder="Mi Empresa S.A.S"
                size="lg"
                required
                :error="fieldErrors.business_name"
            />

            <FormInput
                label="NIT"
                v-model="form.nit"
                placeholder="900.123.456-7"
                size="lg"
                required
                :error="fieldErrors.nit"
            />

            <FormInput
                label="Dirección"
                v-model="form.address"
                placeholder="Calle 123 # 45-67"
                size="lg"
                required
                :error="fieldErrors.address"
            />

            <FormInput
                label="Municipio / Provincia"
                type="select"
                v-model="form.municipality"
                :options="municipalitiesOpts"
                placeholder="Seleccione municipio/provincia"
                size="lg"
                required
                :error="fieldErrors.municipality"
            />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Teléfono con prefijo -->
                <div class="flex flex-col gap-1.5">
                    <div class="flex gap-2">
                        <FormInput
                            label="Teléfono para clientes"
                            type="tel"
                            v-model="form.contact_phone"
                            placeholder="+57 300 123 4567"
                            size="lg"
                            required
                            wrapper-class="flex-1 min-w-0"
                            :error="fieldErrors.contact_phone"
                        />
                    </div>
                </div>

                <FormInput
                    label="Correo para clientes"
                    type="email"
                    v-model="form.contact_email"
                    placeholder="contacto@miempresa.com"
                    size="lg"
                    required
                    :error="fieldErrors.contact_email"
                />
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
                    <FormCheckbox
                        :model-value="form.loan_types.includes(loan.value)"
                        @update:model-value="toggleLoan(loan.value, $event)"
                    />
                    <span
                        class="text-sm text-gray-600 group-hover:text-gray-800 transition-colors leading-tight"
                    >
                        {{ loan.label }}
                    </span>
                </label>
            </div>
            <p v-if="fieldErrors.loan_types" class="text-xs text-red-500 mt-1">
                {{ fieldErrors.loan_types }}
            </p>
        </section>

        <!-- ─ Sección 3: Logo de la empresa ─ -->
        <section class="flex flex-col gap-3">
            <SectionTitle title="Logo de la empresa" />
            <FileUpload
                v-model="form.logo"
                placeholder="Haz clic para subir el logo"
                accept="image/*"
                accept-label="PNG, JPG o SVG — máx. 2MB"
            />
        </section>

        <!-- ─ Sección 4: Representante legal ─ -->
        <section class="flex flex-col gap-4">
            <SectionTitle title="Datos del representante legal" />

            <FormInput
                label="Nombre completo"
                v-model="form.legal_name"
                placeholder="Nombre del representante legal"
                size="lg"
                required
                :error="fieldErrors.legal_name"
            />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <FormInput
                    label="Teléfono de contacto"
                    type="tel"
                    v-model="form.legal_phone"
                    placeholder="300 123 4567"
                    size="lg"
                    required
                    :error="fieldErrors.legal_phone"
                />
                <FormInput
                    label="Número de cédula"
                    v-model="form.legal_id"
                    placeholder="1.234.567.890"
                    size="lg"
                    required
                    :error="fieldErrors.legal_id"
                />
            </div>
        </section>

        <!-- ─ Sección 5: Acceso a la cuenta ─ -->
        <section class="flex flex-col gap-4">
            <SectionTitle title="Acceso a la cuenta" />

            <FormInput
                label="Correo electrónico"
                type="email"
                v-model="form.email"
                placeholder="tucorreo@empresa.com"
                size="lg"
                required
                autocomplete="email"
                :error="fieldErrors.email"
            />

            <!-- Contraseña -->
            <div class="flex flex-col gap-1.5">
                <FormInput
                    label="Contraseña"
                    :type="showPassword ? 'text' : 'password'"
                    v-model="form.password"
                    placeholder="Mínimo 8 caracteres"
                    size="lg"
                    required
                    autocomplete="new-password"
                    :error="fieldErrors.password"
                >
                    <template #icon-right>
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="text-gray-300 hover:text-gray-500 transition-colors"
                        >
                            <EyeIcon :open="!showPassword" />
                        </button>
                    </template>
                </FormInput>

                <!-- Indicador de fortaleza -->
                <template v-if="form.password.length > 0">
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
                    <p class="text-xs mt-1" :class="passwordStrength.textColor">
                        {{ passwordStrength.label }}
                    </p>
                </template>
            </div>

            <!-- Confirmar contraseña -->
            <FormInput
                label="Confirmar contraseña"
                :type="showConfirm ? 'text' : 'password'"
                v-model="form.password_confirmation"
                placeholder="Repite tu contraseña"
                size="lg"
                required
                autocomplete="new-password"
                :error="fieldErrors.password_confirmation"
            >
                <template #icon-right>
                    <button
                        type="button"
                        @click="showConfirm = !showConfirm"
                        class="text-gray-300 hover:text-gray-500 transition-colors"
                    >
                        <EyeIcon :open="!showConfirm" />
                    </button>
                </template>
            </FormInput>
        </section>

        <!-- Términos -->
        <label class="flex items-start gap-3 cursor-pointer -mt-1">
            <FormCheckbox v-model="form.terms" wrapper-class="mt-0.5" />
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
                <a href="#" class="text-emerald-600 hover:underline font-medium"
                    >Términos de uso</a
                >
                de Credigital
            </span>
        </label>

        <!-- Botón submit -->
        <button
            type="submit"
            :disabled="loading || !form.terms"
            class="w-full h-11 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white text-sm font-semibold rounded-lg transition-all duration-150 flex items-center justify-center gap-2"
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
            <span>{{ loading ? 'Creando cuenta...' : 'Crear mi cuenta' }}</span>
        </button>
    </form>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'

// -- Componentes ----------------------------------------------
import FormCheckbox from '@/components/form/FormCheckbox.vue'
import SectionTitle from '@/components/form/SectionTitle.vue'
import FileUpload from '@/components/form/FileUpload.vue'
import FormInput from '@/components/form/FormInput.vue'
import EyeIcon from '@/components/form/EyeIcon.vue'

// -- Estado ---------------------------------------------------
const form = reactive({
    business_name: '',
    nit: '',
    address: '',
    municipality: '',
    contact_phone: '',
    contact_email: '',
    loan_types: [],
    logo: null,
    legal_name: '',
    legal_phone: '',
    legal_id: '',
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

// -- Datos estáticos -----------------------------------------
const loanTypes = [
    { value: 'natural', label: 'Crédito a personas naturales' },
    {
        value: 'vivienda',
        label: 'Crédito con respaldo de vivienda (hipoteca o leaseback)',
    },
    { value: 'vehiculos', label: 'Crédito con respaldo de vehículos' },
]

const municipalitiesOpts = [
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
].map(m => ({ value: m, label: m }))

// ── Fortaleza contraseña ───────────────────────────────────────────────────
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

// ── Helpers ────────────────────────────────────────────────────────────────
function toggleLoan(value, checked) {
    if (checked) form.loan_types.push(value)
    else form.loan_types = form.loan_types.filter(v => v !== value)
}

function getCookie(name) {
    const value = `; ${document.cookie}`
    const parts = value.split(`; ${name}=`)
    if (parts.length === 2)
        return decodeURIComponent(parts.pop().split(';').shift())
    return ''
}

// ── Submit ─────────────────────────────────────────────────────────────────
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

        const payload = new FormData()
        Object.entries(form).forEach(([key, val]) => {
            if (key === 'loan_types')
                val.forEach(v => payload.append('loan_types[]', v))
            else if (key === 'logo' && val) payload.append('logo', val)
            else payload.append(key, val)
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
        emit('success', data)
    } catch {
        errorMessage.value = 'Error de conexión. Intenta nuevamente.'
    } finally {
        loading.value = false
    }
}
</script>
