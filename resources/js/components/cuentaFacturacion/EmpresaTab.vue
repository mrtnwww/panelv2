<template>
    <div class="flex flex-col lg:flex-row">
        <!-- Ilustración lateral -->
        <div
            class="hidden lg:flex items-center justify-center w-64 shrink-0 bg-gray-50/60 border-r border-gray-100 p-10"
        >
            <svg
                viewBox="0 0 160 180"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                class="w-full max-w-40"
            >
                <rect
                    x="30"
                    y="60"
                    width="100"
                    height="110"
                    rx="4"
                    fill="#e6f4ea"
                    stroke="#1a5c2a"
                    stroke-width="2"
                />
                <rect
                    x="20"
                    y="80"
                    width="40"
                    height="90"
                    rx="3"
                    fill="#d1ead8"
                    stroke="#1a5c2a"
                    stroke-width="1.5"
                />
                <rect
                    x="100"
                    y="80"
                    width="40"
                    height="90"
                    rx="3"
                    fill="#d1ead8"
                    stroke="#1a5c2a"
                    stroke-width="1.5"
                />
                <rect
                    x="50"
                    y="80"
                    width="18"
                    height="14"
                    rx="2"
                    fill="#1a5c2a"
                    opacity=".2"
                />
                <rect
                    x="76"
                    y="80"
                    width="18"
                    height="14"
                    rx="2"
                    fill="#1a5c2a"
                    opacity=".2"
                />
                <rect
                    x="50"
                    y="102"
                    width="18"
                    height="14"
                    rx="2"
                    fill="#1a5c2a"
                    opacity=".2"
                />
                <rect
                    x="76"
                    y="102"
                    width="18"
                    height="14"
                    rx="2"
                    fill="#1a5c2a"
                    opacity=".2"
                />
                <rect
                    x="50"
                    y="124"
                    width="18"
                    height="14"
                    rx="2"
                    fill="#1a5c2a"
                    opacity=".2"
                />
                <rect
                    x="76"
                    y="124"
                    width="18"
                    height="14"
                    rx="2"
                    fill="#1a5c2a"
                    opacity=".2"
                />
                <rect
                    x="28"
                    y="96"
                    width="12"
                    height="10"
                    rx="1.5"
                    fill="#1a5c2a"
                    opacity=".15"
                />
                <rect
                    x="28"
                    y="114"
                    width="12"
                    height="10"
                    rx="1.5"
                    fill="#1a5c2a"
                    opacity=".15"
                />
                <rect
                    x="28"
                    y="132"
                    width="12"
                    height="10"
                    rx="1.5"
                    fill="#1a5c2a"
                    opacity=".15"
                />
                <rect
                    x="118"
                    y="96"
                    width="12"
                    height="10"
                    rx="1.5"
                    fill="#1a5c2a"
                    opacity=".15"
                />
                <rect
                    x="118"
                    y="114"
                    width="12"
                    height="10"
                    rx="1.5"
                    fill="#1a5c2a"
                    opacity=".15"
                />
                <rect
                    x="118"
                    y="132"
                    width="12"
                    height="10"
                    rx="1.5"
                    fill="#1a5c2a"
                    opacity=".15"
                />
                <rect
                    x="62"
                    y="148"
                    width="36"
                    height="22"
                    rx="18"
                    fill="#1a5c2a"
                    opacity=".12"
                />
                <rect
                    x="68"
                    y="152"
                    width="24"
                    height="18"
                    rx="12"
                    fill="#1a5c2a"
                    opacity=".25"
                />
                <rect
                    x="25"
                    y="55"
                    width="110"
                    height="10"
                    rx="3"
                    fill="#1a5c2a"
                    opacity=".12"
                    stroke="#1a5c2a"
                    stroke-width="1.5"
                />
                <rect
                    x="10"
                    y="168"
                    width="140"
                    height="4"
                    rx="2"
                    fill="#1a5c2a"
                    opacity=".08"
                />
                <circle cx="130" cy="30" r="12" fill="#bbf7d0" opacity=".6" />
                <circle cx="130" cy="30" r="7" fill="#1a5c2a" opacity=".15" />
            </svg>
        </div>

        <!-- Formulario -->
        <div class="flex-1 p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <FormInput
                        label="Nombre de la empresa"
                        v-model="form.razon_social"
                        placeholder="Mi Empresa S.A.S"
                        :error="errors.razon_social"
                    />
                </div>

                <FormInput
                    label="Número de identificación tributaria"
                    v-model="form.nit"
                    placeholder="900.123.456-7"
                    :error="errors.nit"
                />

                <FormSelectAsync
                    label="Ciudad de la empresa"
                    v-model="form.ciudad_id"
                    :fetch-options="opcionesStore.fetchCiudades"
                    :initial-option="ciudadInicial"
                    placeholder="Selecciona la ciudad"
                    :error="errors.ciudad_id"
                />

                <FormInput
                    label="Dirección principal de la empresa"
                    v-model="form.direccion"
                    placeholder="Calle 123 # 45-67"
                    :error="errors.direccion"
                />

                <FormInput
                    label="Número de contacto de clientes"
                    type="tel"
                    v-model="form.telefono"
                    placeholder="300 123 4567"
                    :error="errors.telefono"
                />

                <div class="sm:col-span-2">
                    <FormInput
                        label="Correo de contacto de clientes"
                        type="email"
                        v-model="form.correo"
                        placeholder="contacto@miempresa.com"
                        :error="errors.correo"
                    />
                </div>

                <!-- Logo -->
                <div
                    class="sm:col-span-2 flex flex-col sm:flex-row gap-5 items-start pt-1"
                >
                    <div class="flex flex-col gap-1.5">
                        <span class="text-xs font-medium text-gray-500"
                            >Logo actual</span
                        >
                        <div
                            class="w-16 h-16 rounded-lg border border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden"
                        >
                            <img
                                v-if="form.logo_url"
                                :src="form.logo_url"
                                alt="Logo"
                                class="w-full h-full object-contain"
                            />
                            <svg
                                v-else
                                width="22"
                                height="22"
                                viewBox="0 0 24 24"
                                fill="none"
                                class="text-gray-300"
                            >
                                <rect
                                    x="3"
                                    y="3"
                                    width="18"
                                    height="18"
                                    rx="3"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                />
                                <circle
                                    cx="8.5"
                                    cy="8.5"
                                    r="1.5"
                                    fill="currentColor"
                                />
                                <path
                                    d="M21 15l-5-5L5 21"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5 flex-1">
                        <span class="text-xs font-medium text-gray-500"
                            >Sube el logo de tu empresa</span
                        >
                        <FileUpload
                            v-model="form.logo"
                            placeholder="Haz clic para subir el logo"
                            accept="image/*"
                            accept-label="PNG, JPG o SVG — máx. 2MB"
                        />
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div
                class="flex items-center gap-3 mt-6 pt-5 border-t border-gray-100"
            >
                <button
                    @click="saveEmpresa"
                    :disabled="loading"
                    class="btn btn-main"
                >
                    <svg
                        v-if="loading"
                        class="animate-spin w-3.5 h-3.5"
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
                    {{ loading ? 'Guardando...' : 'Guardar información' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'

// -- Componentes -----------------------------------------------------
import FormSelectAsync from '@/components/form/FormSelectAsync.vue'
import FileUpload from '@/components/form/FileUpload.vue'
import FormInput from '@/components/form/FormInput.vue'

// -- Loader ----------------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

import api from '@/services/api'

// -- Store --------------------------------------------------------
import { useOpcionesStore } from '@/stores/opciones'

// -- Formulario ------------------------------------------------------
const form = reactive({
    razon_social: '',
    nit: '',
    ciudad_id: '',
    direccion: '',
    telefono: '',
    correo: '',
    logo: null,
    logo_url: null,
})

const errors = reactive({
    razon_social: '',
    nit: '',
    ciudad_id: '',
    direccion: '',
    telefono: '',
    correo: '',
})

const opcionesStore = useOpcionesStore()

const loading = ref(false)
const ciudadInicial = ref(null)

// -- Backend ---------------------------------------------------
async function fetchEmpresa() {
    try {
        const { data } = await api.get('/api/empresas/infoEmpresa')

        const empresa = data.resultado.datosEmpresa

        ciudadInicial.value = empresa.ciudad_nombre

        Object.assign(form, {
            razon_social: empresa.razon_social ?? '',
            nit: empresa.nit ?? '',
            ciudad_id: empresa.ciudad_id ?? '',
            direccion: empresa.direccion ?? '',
            telefono: empresa.telefonoComercial ?? '',
            correo: empresa.correo_comercial ?? '',
            logo_url: empresa.logo ?? null,
        })
    } catch (err) {
        console.error(err)
    }
}

async function saveEmpresa() {
    Object.keys(errors).forEach(k => (errors[k] = ''))
    loading.value = true

    try {
        const payload = new FormData()
        payload.append('razon_social', form.razon_social)
        payload.append('ciudad_id', form.ciudad_id)
        payload.append('direccion', form.direccion)
        payload.append('telefono', form.telefono)
        payload.append('correo', form.correo)
        payload.append('nit', form.nit)

        payload.append('_method', 'PUT')

        if (form.logo) payload.append('logo', form.logo)

        await api.post('/api/empresas/udpateInfoEmpresa', payload, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })
    } catch (err) {
        if (err.response?.status === 422 && err.response.data?.errors) {
            const map = {
                razon_social: 'razon_social',
                nit: 'nit',
                ciudad_id: 'ciudad_id',
                direccion: 'direccion',
                telefono: 'telefono',
                correo: 'correo',
            }
            Object.entries(err.response.data.errors).forEach(([k, v]) => {
                const key = map[k] ?? k
                if (errors[key] !== undefined) errors[key] = v[0]
            })
        }
    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    start()

    try {
        await fetchEmpresa()
    } finally {
        stop()
    }
})
</script>
