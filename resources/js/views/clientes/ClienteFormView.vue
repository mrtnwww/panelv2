<template>
    <div class="flex flex-col gap-4 w-full">
        <!-- Alerta error -->
        <transition name="fade">
            <div
                v-if="errorMessage"
                class="px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"
            >
                {{ errorMessage }}
            </div>
        </transition>

        <!-- 1. Crear cliente -------------------------------------------------------------------- -->
        <CollapsibleCard
            :title="isEditing ? 'Editar cliente' : 'Nuevo cliente'"
            :step="1"
            :open="true"
            :completed="completado.cliente"
        >
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormInput
                    label="Cédula"
                    v-model="form.cedula"
                    placeholder="1.234.567.890"
                    required
                    :error="errors.cedula"
                />
                <FormInput
                    label="Cupo"
                    type="number"
                    v-model="form.cupo"
                    placeholder="0"
                    hint="Valor en pesos colombianos"
                />

                <!-- Lista de productos -->
                <div class="sm:col-span-2 flex flex-col gap-1.5">
                    <label
                        class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                    >
                        Productos o servicios
                    </label>
                    <div class="flex flex-col gap-2">
                        <label
                            v-for="prod in productosOpts"
                            :key="prod.value"
                            class="flex items-center gap-2.5 cursor-pointer group"
                        >
                            <div class="relative shrink-0">
                                <input
                                    v-model="form.productos"
                                    type="checkbox"
                                    :value="prod.value"
                                    class="peer sr-only"
                                />
                                <div
                                    class="w-4 h-4 rounded border-2 border-gray-200 bg-gray-50 peer-checked:bg-[#1a5c2a] peer-checked:border-[#1a5c2a] transition-all flex items-center justify-center"
                                >
                                    <svg
                                        v-if="
                                            form.productos.includes(prod.value)
                                        "
                                        width="8"
                                        height="8"
                                        viewBox="0 0 8 8"
                                        fill="none"
                                    >
                                        <path
                                            d="M1 4L3 6.5L7 1.5"
                                            stroke="white"
                                            stroke-width="1.5"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </div>
                            </div>
                            <span
                                class="text-sm text-gray-600 group-hover:text-gray-800 transition-colors"
                                >{{ prod.label }}</span
                            >
                        </label>
                    </div>
                </div>

                <!-- Foto del cliente -->
                <div class="sm:col-span-2">
                    <FileUpload
                        label="Foto del cliente"
                        v-model="form.fotoCliente"
                        placeholder="Sube o captura la foto del cliente"
                        accept="image/*"
                        accept-label="JPG o PNG — máx. 5MB"
                        :with-camera="true"
                    />
                </div>
            </div>
        </CollapsibleCard>

        <!-- 2. Datos personales -------------------------------------------------------------------- -->
        <CollapsibleCard
            title="Datos personales"
            :step="2"
            :completed="completado.personal"
        >
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormInput
                    label="Nombres"
                    v-model="form.nombre"
                    placeholder="Maria Perez"
                    required
                />
                <FormInput
                    label="Fecha de nacimiento"
                    type="date"
                    v-model="form.fechaNacimiento"
                />
                <FormInput
                    label="Teléfono"
                    type="tel"
                    v-model="form.telefono"
                    placeholder="300 123 4567"
                    required
                />
                <FormInput
                    label="Correo electrónico"
                    type="email"
                    v-model="form.correo"
                    placeholder="cliente@email.com"
                />
                <FormInput
                    label="Dirección"
                    v-model="form.direccion"
                    placeholder="Calle 123 # 45-67"
                />
                <FormInput
                    label="Barrio"
                    v-model="form.barrio"
                    placeholder="El Centro"
                />
                <FormInput
                    label="Ciudad"
                    type="select"
                    v-model="form.ciudad"
                    :options="ciudadesOpts"
                    placeholder="Seleccione una ciudad"
                    :searchable="true"
                />
            </div>
        </CollapsibleCard>

        <!-- 3. Información laboral -------------------------------------------------------------------- -->
        <CollapsibleCard
            title="Información laboral"
            :step="3"
            :completed="completado.laboral"
        >
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormInput
                    label="Salario mensual"
                    type="number"
                    v-model="form.salario"
                    placeholder="0"
                />
                <FormInput
                    label="Nombre del empleador"
                    v-model="form.nombreEmpleador"
                    placeholder="Empresa S.A.S"
                />
                <FormInput
                    label="Teléfono del empleador"
                    type="tel"
                    v-model="form.telefonoEmpleador"
                    placeholder="601 123 4567"
                />
                <FormInput
                    label="Dirección del empleador"
                    v-model="form.direccionEmpleador"
                    placeholder="Calle 10 # 20-30"
                />
                <FormInput
                    label="Tipo de cuenta bancaria"
                    type="select"
                    v-model="form.tipoCuenta"
                    :options="tipoCuentaOpts"
                    placeholder="Seleccione tipo de cuenta"
                />
                <FormInput
                    label="Número de cuenta"
                    v-model="form.numeroCuenta"
                    placeholder="0000000000000"
                />
                <FormInput
                    label="Banco"
                    v-model="form.banco"
                    placeholder="Nombre del banco"
                />
            </div>
        </CollapsibleCard>

        <!-- 4. Documentos -------------------------------------------------------------------- -->
        <CollapsibleCard
            title="Documentos"
            :step="4"
            :completed="completado.documentos"
        >
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <FileUpload
                    label="Cédula — frontal"
                    v-model="form.cedulaFrontal"
                    accept="image/*"
                    accept-label="JPG o PNG"
                    :with-camera="true"
                />
                <FileUpload
                    label="Cédula — posterior"
                    v-model="form.cedulaPosterior"
                    accept="image/*"
                    accept-label="JPG o PNG"
                    :with-camera="true"
                />
                <FileUpload
                    label="Tarjeta de propiedad — frontal"
                    v-model="form.tarjetaPropiedadFront"
                    accept="image/*"
                    accept-label="JPG o PNG"
                    :with-camera="true"
                />
                <FileUpload
                    label="Tarjeta de propiedad — posterior"
                    v-model="form.tarjetaPropiedadBack"
                    accept="image/*"
                    accept-label="JPG o PNG"
                    :with-camera="true"
                />
                <FileUpload
                    label="Certificación bancaria"
                    v-model="form.certBancaria"
                    accept="image/*,application/pdf"
                    accept-label="JPG, PNG o PDF"
                    class="sm:col-span-2"
                />
            </div>
        </CollapsibleCard>

        <!-- 5. Referencias -------------------------------------------------------------------- -->
        <CollapsibleCard
            title="Referencias"
            :step="5"
            :completed="completado.referencias"
        >
            <div class="flex flex-col gap-4">
                <p class="text-xs text-gray-400">
                    2 referencias personales y 2 referencias familiares
                </p>

                <ReferenciaCard
                    v-for="(ref, i) in form.referencias"
                    :key="i"
                    :index="i"
                    :type="ref.type"
                    :model-value="ref"
                    @update:model-value="form.referencias[i] = $event"
                />
            </div>
        </CollapsibleCard>

        <!-- 6. Autorización consulta centrales -------------------------------------------------------------------- -->
        <CollapsibleCard
            title="Autorización consulta centrales"
            :step="6"
            :completed="completado.autorizacionCentrales"
        >
            <div class="flex flex-col gap-4">
                <FileUpload
                    label="Documento de autorización"
                    v-model="form.autorizacionCentralesDoc"
                    accept="image/*,application/pdf"
                    accept-label="JPG, PNG o PDF — máx. 5MB"
                />

                <div class="flex items-center gap-3 pt-1">
                    <button
                        type="button"
                        @click="reenviarAutorizacion('centrales')"
                        :disabled="loadingReenvio === 'centrales'"
                        class="btn btn-main"
                    >
                        <svg
                            v-if="loadingReenvio === 'centrales'"
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
                        <i v-else class="fa-regular fa-envelope"></i>
                        Reenviar al correo del cliente
                    </button>
                </div>
            </div>
        </CollapsibleCard>

        <!-- 7. Autorización débito automático -------------------------------------------------------------------- -->
        <CollapsibleCard
            title="Autorización débito automático"
            :step="7"
            :completed="completado.autorizacionDebito"
        >
            <div class="flex flex-col gap-4">
                <FileUpload
                    label="Documento de autorización"
                    v-model="form.autorizacionDebitoDoc"
                    accept="image/*,application/pdf"
                    accept-label="JPG, PNG o PDF — máx. 5MB"
                />
            </div>
        </CollapsibleCard>

        <!-- 8. Análisis consulta centrales de riesgo -------------------------------------------------------------------- -->
        <CollapsibleCard
            title="Análisis de consulta en centrales de riesgo"
            :step="8"
            :completed="completado.analisis"
        >
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormInput
                    label="Número de consulta"
                    v-model="form.analisisNumeroConsulta"
                    placeholder="Ej: 123456789"
                />
                <FormInput
                    label="Estado de consulta"
                    type="select"
                    v-model="form.analisisEstado"
                    :options="estadoConsultaOpts"
                    placeholder="Seleccione estado"
                />

                <div class="sm:col-span-2">
                    <FormInput
                        label="Nota"
                        type="textarea"
                        v-model="form.analisisNota"
                        placeholder="Observaciones del análisis..."
                        :rows="3"
                    />
                </div>

                <div class="sm:col-span-2">
                    <FileUpload
                        label="Documento de consulta"
                        v-model="form.analisisDoc"
                        accept="image/*,application/pdf"
                        accept-label="JPG, PNG o PDF — máx. 5MB"
                    />
                </div>
            </div>
        </CollapsibleCard>

        <!-- 9. Envío y firma de documentos -------------------------------------------------------------------- -->
        <CollapsibleCard
            title="Envío y firma de documentos"
            :step="9"
            :completed="completado.firma"
        >
            <div class="flex flex-col gap-3">
                <p class="text-xs text-gray-400 mb-1">
                    Documentos enviados para firma
                </p>

                <!-- Lista dinámica de documentos -->
                <div
                    v-if="form.documentosFirma.length > 0"
                    class="flex flex-col gap-2"
                >
                    <div
                        v-for="(doc, i) in form.documentosFirma"
                        :key="i"
                        class="flex items-center gap-3 px-4 py-3 bg-gray-50 rounded-lg border border-gray-200"
                    >
                        <!-- Ícono según tipo -->
                        <svg
                            width="18"
                            height="18"
                            viewBox="0 0 18 18"
                            fill="none"
                            :class="
                                doc.estado === 'firmado'
                                    ? 'text-emerald-500'
                                    : 'text-gray-300'
                            "
                        >
                            <path
                                d="M14 2H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"
                                stroke="currentColor"
                                stroke-width="1.3"
                            />
                            <path
                                d="M6 9l2 2 4-4"
                                stroke="currentColor"
                                stroke-width="1.3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>

                        <div class="flex-1 min-w-0">
                            <p
                                class="text-sm text-[#0A2540] font-medium truncate"
                            >
                                {{ doc.nombre }}
                            </p>
                            <p class="text-xs text-gray-400">{{ doc.fecha }}</p>
                        </div>

                        <!-- Badge estado -->
                        <span
                            class="text-xs font-medium px-2 py-0.5 rounded-full shrink-0"
                            :class="
                                doc.estado === 'firmado'
                                    ? 'bg-emerald-50 text-emerald-700'
                                    : 'bg-yellow-50 text-yellow-700'
                            "
                        >
                            {{
                                doc.estado === 'firmado'
                                    ? 'Firmado'
                                    : 'Pendiente'
                            }}
                        </span>
                    </div>
                </div>

                <!-- Estado vacío -->
                <div
                    v-else
                    class="flex flex-col items-center justify-center py-10 text-center"
                >
                    <i class="fa-regular fa-file"></i>
                    <p class="text-sm text-gray-400">
                        No hay documentos enviados aún
                    </p>
                    <p class="text-xs text-gray-300 mt-0.5">
                        Los documentos aparecerán aquí una vez enviados al
                        cliente
                    </p>
                </div>
            </div>
        </CollapsibleCard>

        <!-- Botón guardar inferior -->
        <div class="flex justify-end gap-2 pt-2 pb-6">
            <button
                type="button"
                class="btn btn-default"
                @click="router.push('/clientes')"
            >
                Cancelar
            </button>
            <button
                type="button"
                @click="handleSubmit"
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
                Guardar cliente
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { formatDateYmd } from '@/utils/format'

const route = useRoute()

// -- Detectar el modo --------------------------------------------
const isEditing = computed(() => !!route.params.cliente_id)
const clienteId = computed(() => route.params.cliente_id)

// -- Componentes -------------------------------------------------
import CollapsibleCard from '@/components/cards/CollapsibleCard.vue'
import ReferenciaCard from '@/components/form/ReferenciaCard.vue'
import FileUpload from '@/components/form/FileUpload.vue'
import FormInput from '@/components/form/FormInput.vue'

import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

import api from '@/services/api'
import axios from 'axios'

const router = useRouter()

// -- Estado global ----------------------------------------------------------
const loading = ref(false)
const errorMessage = ref('')
const loadingReenvio = ref(null)
const errors = reactive({})

// -- Formulario ----------------------------------------------------------
const form = reactive({
    // 1. Crear cliente
    cedula: '',
    cupo: '',
    productos: [],
    fotoCliente: null,

    // 2. Datos personales
    nombre: '',
    fechaNacimiento: '',
    telefono: '',
    correo: '',
    direccion: '',
    barrio: '',
    ciudad: '',

    // 3. Información laboral
    salario: '',
    nombreEmpleador: '',
    telefonoEmpleador: '',
    direccionEmpleador: '',
    tipoCuenta: '',
    numeroCuenta: '',
    banco: '',

    // 4. Documentos
    cedulaFrontal: null,
    cedulaPosterior: null,
    tarjetaPropiedadFront: null,
    tarjetaPropiedadBack: null,
    certBancaria: null,

    // 5. Referencias
    referencias: [
        { type: 'personal', nombre: '', telefono: '', nota: '' },
        { type: 'personal', nombre: '', telefono: '', nota: '' },
        { type: 'familiar', nombre: '', telefono: '', nota: '' },
        { type: 'familiar', nombre: '', telefono: '', nota: '' },
    ],

    // 6. Autorización centrales
    autorizacionCentralesDoc: null,

    // 7. Autorización débito
    autorizacionDebitoDoc: null,

    // 8. Análisis centrales
    analisisNumeroConsulta: '',
    analisisEstado: '',
    analisisNota: '',
    analisisDoc: null,

    // 9. Documentos firma
    documentosFirma: [],
})

// -- Secciones completadas ----------------------------------------------------------
const completado = computed(() => ({
    cliente: !!form.cedula,
    personal: !!form.nombre && !!form.telefono,
    laboral: !!form.salario || !!form.nombreEmpleador,
    documentos: !!(form.cedulaFrontal && form.cedulaPosterior),
    referencias: form.referencias.every(r => r.nombre && r.telefono),
    autorizacionCentrales: !!form.autorizacionCentralesDoc,
    autorizacionDebito: !!form.autorizacionDebitoDoc,
    analisis: !!form.analisisNumeroConsulta,
    firma: form.documentosFirma.length > 0,
}))

// -- Opciones ----------------------------------------------------------
const productosOpts = ref([])
const ciudadesOpts = ref([])

const tipoCuentaOpts = [
    { value: 'ahorros', label: 'Ahorros' },
    { value: 'corriente', label: 'Corriente' },
]

const estadoConsultaOpts = [
    { value: 1, label: 'Aprobado' },
    { value: 0, label: 'Rechazado' },
]

// -- Acciones ----------------------------------------------------------
async function reenviarAutorizacion(tipo) {
    loadingReenvio.value = tipo
    try {
        await fetch(`/api/clientes/reenviar-autorizacion`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
            },
            body: JSON.stringify({ tipo, correo: form.correo }),
        })
    } catch (err) {
        console.error(err)
    } finally {
        loadingReenvio.value = null
    }
}

async function handleSubmit() {
    errorMessage.value = ''
    Object.keys(errors).forEach(k => delete errors[k])

    if (!form.cedula) {
        errors.cedula = 'La cédula es requerida.'
        return
    }

    loading.value = true

    try {
        const payload = new FormData()

        // Campos de texto
        const textFields = [
            'cedula',
            'cupo',
            'nombre',
            'fechaNacimiento',
            'telefono',
            'correo',
            'direccion',
            'barrio',
            'ciudad',
            'salario',
            'nombreEmpleador',
            'telefonoEmpleador',
            'direccionEmpleador',
            'tipoCuenta',
            'numeroCuenta',
            'banco',
            'analisisNumeroConsulta',
            'analisisEstado',
            'analisisNota',
        ]
        textFields.forEach(k => payload.append(k, form[k] ?? ''))

        // Arrays
        form.productos.forEach(v => payload.append('productos[]', v))
        form.referencias.forEach((r, i) => {
            payload.append(`referencias[${i}][type]`, r.type)
            payload.append(`referencias[${i}][nombre]`, r.nombre)
            payload.append(`referencias[${i}][telefono]`, r.telefono)
            payload.append(`referencias[${i}][nota]`, r.nota)
        })

        // Archivos
        const fileFields = [
            'fotoCliente',
            'cedulaFrontal',
            'cedulaPosterior',
            'tarjetaPropiedadFront',
            'tarjetaPropiedadBack',
            'certBancaria',
            'autorizacionCentralesDoc',
            'autorizacionDebitoDoc',
            'analisisDoc',
        ]
        fileFields.forEach(k => {
            if (form[k]) payload.append(k, form[k])
        })

        const response = await fetch('/api/clientes', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
            },
            body: payload,
        })

        const data = await response.json()

        if (response.status === 422 && data.errors) {
            Object.entries(data.errors).forEach(([k, v]) => (errors[k] = v[0]))
            return
        }

        if (!response.ok) {
            errorMessage.value = data.message || 'No se pudo crear el cliente.'
            return
        }

        router.push({ name: 'clientes.lista' })
    } catch {
        errorMessage.value = 'Error de conexión. Intenta nuevamente.'
    } finally {
        loading.value = false
    }
}

async function fetchCliente() {
    if (!isEditing.value) return
    loading.value = true

    try {
        const { data } = await api.get(`/api/clientes/${clienteId.value}`)

        // Mapea los campos del backend al formulario
        const cliente = data.resultado.cliente
        const referencia = data.resultado.referencia

        Object.assign(form, {
            cedula: cliente.cedula ?? '',
            cupo: cliente.cupo ?? '',

            // 1. Datos personales
            nombre: cliente.nombre ?? '',
            fechaNacimiento: formatDateYmd(cliente.fecha_nacimiento),
            telefono: cliente.telefono ?? '',
            correo: cliente.email ?? '',
            direccion: cliente.direccion ?? '',
            barrio: cliente.barrio ?? '',
            ciudad: cliente.ciudad ?? '',

            // 2. Información laboral
            salario: cliente.salario ?? '',
            nombreEmpleador: cliente.empresa_labora ?? '',
            telefonoEmpleador: cliente.telEmpresa ?? '',
            direccionEmpleador: cliente.direccionEmpresa ?? '',
            tipoCuenta: cliente.tipo_cuenta_bancaria ?? '',
            numeroCuenta: cliente.num_cuenta_bancaria ?? '',
            banco: cliente.nombre_banco ?? '',

            // 3. Análisis
            analisisNota: cliente.nota ?? '',
            analisisEstado: cliente.estado_aval ?? 0,
            analisisNumeroConsulta: cliente.no_aval ?? '',

            // 5. Referencias
            referencias: [
                {
                    type: 'personal',
                    nombre: referencia.ref_comecial_1 ?? '',
                    telefono: referencia.tel_1 ?? '',
                    nota: referencia.res_ref_comecial_1 ?? '',
                },
                {
                    type: 'personal',
                    nombre: referencia.ref_comecial_2 ?? '',
                    telefono: referencia.tel_2 ?? '',
                    nota: referencia.res_ref_comecial_2 ?? '',
                },
                {
                    type: 'familiar',
                    nombre: referencia.ref_familiar_1 ?? '',
                    telefono: referencia.tel_3 ?? '',
                    nota: referencia.res_ref_familiar_1 ?? '',
                },
                {
                    type: 'familiar',
                    nombre: referencia.ref_familiar_2 ?? '',
                    telefono: referencia.tel_4 ?? '',
                    nota: referencia.res_ref_familiar_2 ?? '',
                },
            ],
        })
    } catch (e) {
        router.back()
    } finally {
        loading.value = false
    }
}

async function fetchCiudades() {
    const ciudades = localStorage.getItem('ciudades')

    if (ciudades) {
        ciudadesOpts.value = JSON.parse(ciudades)
        return
    }

    try {
        const { data } = await axios.get('/api/ciudades')

        localStorage.setItem('ciudades', JSON.stringify(data.ciudades))
    } catch (err) {
        console.error(err)
    }
}

onMounted(async () => {
    start()

    try {
        await Promise.all([fetchCliente(), fetchCiudades()])
    } finally {
        stop()
    }
})
</script>
