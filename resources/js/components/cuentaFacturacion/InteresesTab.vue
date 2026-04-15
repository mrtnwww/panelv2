<template>
    <div class="p-6">
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
            <div
                class="relative p-5 bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm"
            >
                <fieldset
                    :disabled="!formularioHabilitado"
                    :class="{
                        'opacity-70 pointer-events-none': !formularioHabilitado,
                    }"
                    class="space-y-8 max-w-full transition-opacity duration-300"
                >
                    <div class="border-b border-gray-100 mb-6 pb-4">
                        <h2
                            class="text-center text-xl font-bold text-gray-800 tracking-tight uppercase"
                        >
                            {{ form.nombreLinea }}
                        </h2>
                        <p
                            class="text-center text-xs text-gray-400 font-medium mt-1"
                        >
                            Configuración de parámetros de la línea de crédito
                        </p>
                    </div>
                    <div class="space-y-8 max-w-full">
                        <div class="pb-6 border-b border-gray-100">
                            <div class="flex flex-col gap-4">
                                <FormCheckbox
                                    v-model="form.firma_electronica_enabled"
                                    label="Firma electrónica"
                                />

                                <div
                                    v-if="form.firma_electronica_enabled"
                                    class="ml-0 sm:ml-6 space-y-4"
                                >
                                    <div
                                        class="grid grid-cols-1 md:grid-cols-3 gap-4"
                                    >
                                        <FormInput
                                            label="Valor firma electrónica"
                                            type="number"
                                            v-model="form.firma_electronica"
                                            placeholder="$0"
                                        />
                                        <FormInput
                                            label="% Firma electrónica"
                                            type="number"
                                            v-model="
                                                form.porcentaje_firma_electronica
                                            "
                                            placeholder="14%"
                                        />
                                        <FormInput
                                            label="% IVA Firma electrónica"
                                            type="number"
                                            v-model="form.iva_firma_electronica"
                                            placeholder="19%"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pb-6 border-b border-gray-100">
                            <div class="flex flex-col lg:flex-row gap-6">
                                <div class="flex-1 space-y-3">
                                    <FormCheckbox
                                        v-model="form.intereses_enabled"
                                        label="Intereses"
                                    />
                                    <p
                                        v-if="form.intereses_enabled"
                                        class="text-xs text-justify text-gray-400 pl-6 border-l-2 border-gray-50"
                                    >
                                        Corrobore y verifique que los intereses
                                        correspondan al mes y no superen la
                                        máxima tasa de usura permitida. En
                                        Colombia la Superintendencia Financiera
                                        es la entidad encargada de certificar la
                                        tasa.
                                    </p>
                                </div>

                                <div
                                    v-if="form.intereses_enabled"
                                    class="flex-1 flex flex-col gap-4 bg-gray-50/50 p-4 rounded-lg"
                                >
                                    <FormRadioGroup
                                        v-model="form.tipo_interes"
                                        :options="tipoIntereses"
                                        :vertical="false"
                                    />
                                    <div
                                        class="grid grid-cols-2 gap-3"
                                        v-if="form.tipo_interes === 'general'"
                                    >
                                        <FormInput
                                            label="% E.A."
                                            type="number"
                                            v-model="form.ea_intereses"
                                            placeholder="26.76%"
                                        />
                                        <FormInput
                                            label="% N.M."
                                            type="number"
                                            v-model="form.nm_intereses"
                                            placeholder="2.00%"
                                        />
                                        <div>
                                            <FormCheckbox
                                                v-model="
                                                    form.interes_automatico_enabled
                                                "
                                                label="Cálculo Automático"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pb-6 border-b border-gray-100">
                            <div class="flex flex-col lg:flex-row gap-6">
                                <div class="flex-1 space-y-3">
                                    <FormCheckbox
                                        v-model="form.otros_intereses_enabled"
                                        label="Otros intereses"
                                    />
                                    <div
                                        v-if="form.otros_intereses_enabled"
                                        class="pl-6 space-y-3"
                                    >
                                        <p class="text-xs text-gray-400 italic">
                                            Digite un valor efectivo anual o
                                            nominal mensual que será calculado y
                                            sumado al valor del crédito.
                                        </p>
                                        <FormInput
                                            v-model="
                                                form.otros_intereses_concepto
                                            "
                                            placeholder="Definir concepto otros intereses"
                                        />
                                    </div>
                                </div>

                                <div
                                    v-if="form.otros_intereses_enabled"
                                    class="flex-1"
                                >
                                    <div class="grid grid-cols-2 gap-3">
                                        <FormInput
                                            label="% E.A."
                                            type="number"
                                            v-model="form.ea_otros_intereses"
                                            placeholder="26.76%"
                                        />
                                        <FormInput
                                            label="% N.M."
                                            type="number"
                                            v-model="form.nm_otros_intereses"
                                            placeholder="2.00%"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pb-6 border-b border-gray-100">
                            <div class="space-y-4">
                                <FormCheckbox
                                    v-model="form.aval_enabled"
                                    label="Aval"
                                />

                                <div
                                    v-if="form.aval_enabled"
                                    class="ml-0 sm:ml-6 space-y-6"
                                >
                                    <div
                                        class="bg-yellow-50 border-l-4 border-yellow-400 p-3 rounded-r-md"
                                    >
                                        <p
                                            class="text-xs text-yellow-800 font-bold mb-1"
                                        >
                                            ⚠️ Advertencia
                                        </p>
                                        <p
                                            class="text-xs text-yellow-700 leading-relaxed"
                                        >
                                            Usted debe contratar este servicio y
                                            adjuntar el documento. El valor a
                                            cobrar será el mismo que usted
                                            pagará.
                                        </p>
                                    </div>

                                    <FileUpload
                                        label="Adjuntar documento"
                                        v-model="form.documento_aval"
                                    />

                                    <div
                                        class="grid grid-cols-1 md:grid-cols-3 gap-4"
                                    >
                                        <FormInput
                                            label="Valor aval"
                                            type="number"
                                            v-model="form.aval"
                                            placeholder="$0"
                                        />
                                        <FormInput
                                            label="% Aval"
                                            type="number"
                                            v-model="form.porcentaje_aval"
                                            placeholder="14%"
                                        />
                                        <FormInput
                                            label="% IVA Aval"
                                            type="number"
                                            v-model="form.iva_aval"
                                            placeholder="19%"
                                        />
                                    </div>

                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                                    >
                                        <FormInput
                                            label="Empresa avalista"
                                            v-model="form.empresa_avalista"
                                            placeholder="Mi empresa S.A"
                                        />
                                        <FormInput
                                            label="NIT"
                                            v-model="form.empresa_avalista_nit"
                                            placeholder="123456789-0"
                                        />
                                    </div>

                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2"
                                    >
                                        <FormCheckbox
                                            v-model="form.mostrar_aval_total"
                                            label="Totalizado"
                                        />
                                        <FormCheckbox
                                            v-model="form.mostrar_aval_columnas"
                                            label="En columnas"
                                        />
                                        <FormCheckbox
                                            v-model="form.restar_aval"
                                            label="Restar Aval"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pb-6">
                            <div class="flex flex-col lg:flex-row gap-6">
                                <div class="flex-1 space-y-3">
                                    <FormCheckbox
                                        v-model="form.otros_enabled"
                                        label="Otros"
                                    />
                                    <div
                                        v-if="form.otros_enabled"
                                        class="pl-6 space-y-3"
                                    >
                                        <p class="text-xs text-gray-400 italic">
                                            Describa los valores adicionales en
                                            el concepto del plan de pagos.
                                            Emitiendo factura + IVA.
                                        </p>
                                        <FormInput
                                            v-model="form.otros_concepto"
                                            placeholder="Concepto (Ej: Otros)"
                                        />
                                    </div>
                                </div>

                                <div v-if="form.otros_enabled" class="flex-1">
                                    <div class="grid grid-cols-2 gap-3">
                                        <FormInput
                                            label="Valor otros"
                                            type="number"
                                            v-model="form.otros"
                                            placeholder="$0"
                                        />
                                        <FormInput
                                            label="% Otros"
                                            type="number"
                                            v-model="form.porcentaje_otros"
                                            placeholder="0%"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>

            <div class="space-y-6">
                <div>
                    <h3
                        class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2"
                    >
                        Funciones crédito
                        <i class="fa-solid fa-table-cells text-green-700"></i>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <FormCheckbox
                            v-for="opt in adicionalesDestinosOpts"
                            :label="opt.label"
                            :value="opt.value"
                            v-model="form.adicionalesDestinos"
                        />
                    </div>
                </div>

                <TableGrid
                    title="Líneas de crédito"
                    :items="lineasCredito"
                    :columns="cols"
                    @row-click="showParametros"
                    class="cursor-pointer"
                >
                    <template
                        #insertion-row
                        v-if="creandoNuevaLinea || editandoLinea"
                    >
                        <div
                            ref="insertionRowRef"
                            class="grid gap-4 px-4 py-4 items-center animate-new-row shadow-xl z-10 relative rounded-lg my-1"
                        >
                            <div
                                class="col-span-full mb-2 flex items-center gap-2"
                            >
                                <span
                                    class="flex h-2 w-2 rounded-full bg-[#1a5c2a] animate-ping"
                                ></span>
                                <span
                                    class="text-xs font-bold text-[#1a5c2a] uppercase tracking-widest"
                                >
                                    {{
                                        editandoLinea
                                            ? `Editando: ${lineaEditando?.nombre}`
                                            : 'Nueva Línea de Crédito'
                                    }}
                                </span>
                            </div>

                            <FormInput
                                v-model="nuevaLinea.nombre"
                                placeholder="Línea de crédito"
                            />
                            <FormInput
                                v-model="nuevaLinea.periodicidad"
                                type="number"
                                placeholder="Periodicidad"
                            />
                            <FormInput
                                v-model="nuevaLinea.valor_minimo"
                                type="number"
                                placeholder="Valor mínimo"
                            />
                            <FormInput
                                v-model="nuevaLinea.valor_maximo"
                                type="number"
                                placeholder="Valor máximo"
                            />

                            <div class="flex gap-2 justify-center">
                                <button
                                    @click="guardarLinea"
                                    class="bg-[#10b981] text-white p-2 rounded-lg hover:bg-[#059669] transition-all"
                                >
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button
                                    @click="cancelarEdicionCreacion"
                                    class="bg-gray-100 text-gray-500 p-2 rounded-lg hover:bg-gray-200 transition-all"
                                >
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    </template>

                    <template #cell(acciones)="{ item }">
                        <div class="flex gap-1.5 justify-center">
                            <button
                                @click="
                                    habilitarCreacionEdicion({
                                        modo: 'editar',
                                        row: item,
                                    })
                                "
                                :disabled="creandoNuevaLinea || editandoLinea"
                                class="bg-[#10b981] text-white p-1.5 rounded-lg hover:bg-[#059669] disabled:bg-gray-300 disabled:pointer-events-none"
                            >
                                <i class="fa-solid fa-pencil"></i>
                            </button>
                            <button
                                @click="eliminarLineaCredito(item)"
                                :disabled="creandoNuevaLinea || editandoLinea"
                                class="bg-red-500 text-white p-1.5 rounded-lg hover:bg-red-600 disabled:bg-gray-300 disabled:pointer-events-none"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </template>
                </TableGrid>
            </div>
        </div>

        <button
            class="btn btn-primary mt-8"
            :disabled="creandoNuevaLinea || editandoLinea"
            @click="habilitarCreacionEdicion"
        >
            Crear línea de crédito
        </button>
    </div>
</template>

<script setup>
import { reactive, ref, nextTick, computed, onMounted, watch } from 'vue'

// -- Componentes --------------------------------------------------
import FormRadioGroup from '@/components/form/FormRadioGroup.vue'
import FormCheckbox from '@/components/form/FormCheckbox.vue'
import FileUpload from '@/components/form/FileUpload.vue'
import FormInput from '@/components/form/FormInput.vue'
import TableGrid from '@/components/TableGrid.vue'

// -- Loader -------------------------------------------------------
import { useLoader } from '@/composables/useLoader'
const { start, stop } = useLoader()

// -- Utils --------------------------------------------------------
import { formatCurrency, toNumber } from '@/utils/format'
import { confirmAlert } from '@/utils/alert'

// -- API ----------------------------------------------------------
import api from '@/services/api'

const getDefaultForm = () => ({
    id: null,
    nombreLinea: '',

    // Firma electrónica
    firma_electronica_enabled: false,
    firma_electronica: null,
    porcentaje_firma_electronica: null,
    iva_firma_electronica: null,

    // Intereses
    intereses_enabled: false,
    tipo_interes: 'general',
    ea_intereses: null,
    nm_intereses: null,
    interes_automatico_enabled: false,

    // Otros intereses
    otros_intereses_enabled: false,
    otros_intereses_concepto: '',
    ea_otros_intereses: null,
    nm_otros_intereses: null,

    // Aval
    aval_enabled: false,
    documento_aval: null,
    aval: null,
    porcentaje_aval: null,
    iva_aval: null,
    empresa_avalista: '',
    empresa_avalista_nit: '',
    mostrar_aval_total: false,
    mostrar_aval_columnas: false,
    restar_aval: false,

    // Otros
    otros_enabled: false,
    otros_concepto: '',
    otros: null,
    porcentaje_otros: null,

    adicionalesDestinos: [],
})

const form = reactive(getDefaultForm())

const creandoNuevaLinea = ref(false)
const editandoLinea = ref(false)
const lineaEditando = ref(null)

const insertionRowRef = ref(null)
const nuevaLinea = ref({
    nombre: '',
    valor_minimo: null,
    valor_maximo: null,
    periodicidad: null,
    parametros: {},
})

// -- TableGrid -------------------------------------------------
const cols = [
    { key: 'nombre', label: 'Nombre', width: '1.5fr' },
    {
        key: 'periodicidad',
        label: 'Periodicidad',
        width: '80px',
        cellClass: 'text-center',
    },
    {
        key: 'valor_minimo',
        label: 'Valor mínimo',
        width: '1.5fr',
        cellClass: 'text-right',
        headerClass: 'text-right',
    },
    {
        key: 'valor_maximo',
        label: 'Valor máximo',
        width: '1.5fr',
        cellClass: 'text-right',
        headerClass: 'text-right',
    },
    {
        key: 'acciones',
        label: 'Acciones',
        width: '80px',
        headerClass: 'text-center',
    },
]

const tipoIntereses = [
    { value: 'general', label: 'General' },
    { value: 'individual', label: 'Individual' },
]

const adicionalesDestinosOpts = [
    { label: 'Redondear valores intereses', value: 'redondear_intereses' },
    { label: 'Exención de interés y otros', value: 'isexention' },
    { label: 'Línea de liquidez', value: 'isexentionGracia' },
    { label: 'Valor de consulta a centrales', value: 'valor_consulta' },
]

const lineasCredito = ref([])

// -- Backend ------------------------------------------------
async function fetchAccountInfo(targetId = null, force = false) {
    try {
        const { data } = await api.get('/api/cuentaFacturacion/getParametros')
        const lineas = (data?.lineasCredito || []).map(mapLineaCredito)
        lineasCredito.value = lineas

        if (!lineas.length) return

        let lineaSeleccionada

        if (targetId === 'last') {
            // Nueva línea
            lineaSeleccionada = lineas.at(-1)
        } else if (targetId) {
            // Edición línea
            lineaSeleccionada = lineas.find(l => l.id === targetId) || lineas[0]
        } else {
            lineaSeleccionada = lineas[0]
        }

        showParametros(lineaSeleccionada, force)
    } catch (err) {
        console.error(err)
    }
}

async function guardarLinea() {
    if (!nuevaLinea.value.nombre || !nuevaLinea.value.periodicidad) {
        return
    }

    // Reglas de validación parámetros intereses
    const reglas = [
        {
            check: form.firma_electronica_enabled,
            validateSome: [
                form.firma_electronica,
                form.porcentaje_firma_electronica,
            ],
        },
        {
            check: form.intereses_enabled,
            validateSome: [form.ea_intereses, form.nm_intereses],
        },
        {
            check: form.otros_intereses_enabled,
            validateSome: [form.ea_otros_intereses, form.nm_otros_intereses],
            validateObliga: [form.otros_intereses_concepto],
        },
        {
            check: form.aval_enabled,
            validateSome: [form.aval, form.porcentaje_aval],
            validateObliga: [form.empresa_avalista, form.empresa_avalista_nit],
        },
        {
            check: form.otros_enabled,
            validateSome: [form.otros, form.porcentaje_otros],
            validateObliga: [form.otros_concepto],
        },
    ]

    const errorValidacion = reglas.some(regla => {
        if (!regla.check) return false

        const validateSome = regla.validateSome
            ? regla.validateSome.every(campo => !campo)
            : false

        const validateObliga = regla.validateObliga
            ? regla.validateObliga.some(campo => !campo)
            : false

        return validateSome || validateObliga
    })

    if (errorValidacion) {
        return
    }

    try {
        start()

        const idLinea = form.id || nuevaLinea.value.id

        const esEdicion = !!idLinea

        const payload = {
            ...nuevaLinea.value,
            parametros: { ...form },
            id: esEdicion ? idLinea : null,
        }

        const url = esEdicion
            ? `/api/cuentaFacturacion/updateParametros`
            : '/api/cuentaFacturacion/saveParametros'

        const metodo = esEdicion ? 'put' : 'post'

        const { data: responseData } = await api[metodo](url, payload)

        const idParaSeleccionar = editandoLinea.value
            ? responseData.data.id
            : 'last'

        creandoNuevaLinea.value = false
        editandoLinea.value = false

        await fetchAccountInfo(idParaSeleccionar, true)
    } catch (err) {
        console.error('Error al guardar:', err)
    } finally {
        stop()
    }
}

async function eliminarLineaCredito(row) {
    const confirmado = await confirmAlert({
        title: 'Eliminar línea de crédito',
        text: `¿Está seguro(a) de eliminar la línea de crédito ${row.nombre}?`,
    })

    if (!confirmado) return

    start()

    try {
        await api.delete('/api/cuentaFacturacion/deleteParametros', {
            data: { id: row.id },
        })

        await fetchAccountInfo()
    } catch (err) {
        console.error(err)
    } finally {
        stop()
    }
}

function mapLineaCredito(l) {
    return {
        id: l.id,
        nombre: l.tipo_credito,
        parametros: l.parametros,
        valor_minimo: l.valor_minimo ? formatCurrency(l.valor_minimo) : '- -',
        valor_maximo: l.valor_maximo ? formatCurrency(l.valor_maximo) : '- -',
        valor_minimo_raw: l.valor_minimo ?? null,
        valor_maximo_raw: l.valor_maximo ?? null,
        empresa_avalista: l.empresa_avalista,
        periodicidad: l.parametros?.periodicidad,
    }
}

function showParametros(data, force = false) {
    if (!data || typeof data !== 'object' || creandoNuevaLinea.value) return

    // Bloquear acciones sobre la línea si ya se encuentra seleccionada
    if (!force && form?.id == data?.id) return

    const parametros = data.parametros || {}

    // Limpiar formulario de parámetros
    Object.assign(form, getDefaultForm())

    form.nombreLinea = data.nombre
    form.id = data.id

    // ========================
    // Firma electrónica
    // ========================
    const firmaFija = toNumber(parametros.firma_elec)
    const firmaPorcentual = toNumber(parametros.firma_elec_porcentual)
    const ivaFirma = toNumber(parametros.firma_elec_iva)

    if (firmaFija || firmaPorcentual) {
        form.firma_electronica_enabled = true
        form.iva_firma_electronica = ivaFirma

        if (firmaFija) form.firma_electronica = firmaFija
        if (firmaPorcentual) form.porcentaje_firma_electronica = firmaPorcentual
    }

    // ========================
    // Intereses
    // ========================
    if (parametros.interes_mode === 'gen') {
        const interesEa = toNumber(parametros.interes_ea)
        const interesNm = toNumber(parametros.interes_nm)

        if (interesEa || interesNm) {
            form.ea_intereses = interesEa
            form.nm_intereses = interesNm

            form.intereses_enabled = true
            form.tipo_interes = 'general'
        }
    } else {
        form.intereses_enabled = true
        form.tipo_interes = 'individual'
    }

    // ========================
    // Otros intereses
    // ========================
    const otroEa = toNumber(parametros.otro_por_ea)
    const otroNm = toNumber(parametros.otro_por_nm)

    if (otroEa || otroNm) {
        form.otros_intereses_enabled = true
        form.otros_intereses_concepto = parametros.otro_por_observacion ?? ''

        if (otroEa) form.ea_otros_intereses = otroEa
        if (otroNm) form.nm_otros_intereses = otroNm
    }

    // ========================
    // Aval
    // ========================
    const avalFija = toNumber(parametros.aval_nominal)
    const avalPorcentual = toNumber(parametros.aval_porcentual)
    const ivaAval = toNumber(parametros.aval_iva)

    if (avalFija || avalPorcentual) {
        form.aval_enabled = true

        form.empresa_avalista = data?.empresa_avalista?.nombre_empresa ?? ''
        form.empresa_avalista_nit = data?.empresa_avalista?.nit_empresa ?? ''

        if (avalFija) form.aval = avalFija
        if (avalPorcentual) form.porcentaje_aval = avalPorcentual

        if (ivaAval) form.iva_aval = ivaAval

        form.mostrar_aval_columnas = !!parametros.aval_columnas
        form.mostrar_aval_total = !parametros.aval_columnas

        form.restar_aval = !!parametros.restar_aval
    }

    // ========================
    // Otros
    // ========================
    const otrosFija = toNumber(parametros.otros_nominal)
    const otrosPorcentual = toNumber(parametros.otros_porcentual)

    if (otrosFija || otrosPorcentual) {
        form.otros_enabled = true
        form.otros_concepto = parametros.otros_observacion ?? ''

        if (otrosFija) form.otros = otrosFija
        if (otrosPorcentual) form.porcentaje_otros = otrosPorcentual
    }

    // Adicionales destinos
    const keys = [
        'redondear_intereses',
        'isexentionGracia',
        'valor_consulta',
        'isexention',
    ]

    form.adicionalesDestinos = keys.filter(key => Number(parametros[key]) != 0)
}

// -- Crear/Editar linea de credito -------------------------------------------
async function habilitarCreacionEdicion({ modo = 'crear', row = null } = {}) {
    const esEdicion = modo === 'editar'

    nuevaLinea.value = esEdicion
        ? {
              id: row.id,
              nombre: row.nombre,
              valor_minimo: row.valor_minimo_raw ?? null,
              valor_maximo: row.valor_maximo_raw ?? null,
              periodicidad: row.periodicidad,
              parametros: row.parametros ?? {},
          }
        : {
              nombre: '',
              valor_minimo: null,
              valor_maximo: null,
              periodicidad: null,
              parametros: {},
          }

    creandoNuevaLinea.value = !esEdicion

    if (esEdicion) {
        lineaEditando.value = row
        editandoLinea.value = true
    } else {
        Object.assign(form, getDefaultForm())
    }

    await nextTick()

    insertionRowRef.value?.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
    })
}

function cancelarEdicionCreacion() {
    creandoNuevaLinea.value = false
    editandoLinea.value = false
    lineaEditando.value = null

    showParametros(lineasCredito.value[0], true)
}

// -- Computadas ----------------------------------------------
const formularioHabilitado = computed(() => {
    return creandoNuevaLinea.value || editandoLinea.value
})

// Reglas de exclusión
const syncFields = (changedField, fieldToClear) => {
    if (form[changedField] !== null && form[changedField] !== '') {
        form[fieldToClear] = null
    }
}

watch(
    () => [
        form.firma_electronica,
        form.porcentaje_firma_electronica,
        form.ea_intereses,
        form.nm_intereses,
        form.ea_otros_intereses,
        form.nm_otros_intereses,
        form.aval,
        form.porcentaje_aval,
        form.otros,
        form.porcentaje_otros,
    ],
    (newValues, oldValues) => {
        const [
            firma,
            porcentajeFirma,
            ea,
            nm,
            eaOtros,
            nmOtros,
            aval,
            porcentajeAval,
            otros,
            porcentajeOtros,
        ] = newValues
        const [
            oldFirma,
            oldPorcentajeFirma,
            oldEa,
            oldNm,
            oldEaOtros,
            oldNmOtros,
            oldAval,
            oldPorcentajeAval,
            oldOtros,
            oldPorcentajeOtros,
        ] = oldValues

        // Limpiar valores firma electrónica
        if (firma !== oldFirma)
            syncFields('firma_electronica', 'porcentaje_firma_electronica')
        if (porcentajeFirma !== oldPorcentajeFirma)
            syncFields('porcentaje_firma_electronica', 'firma_electronica')

        // Limpiar valores intereses
        if (ea !== oldEa) syncFields('ea_intereses', 'nm_intereses')
        if (nm !== oldNm) syncFields('nm_intereses', 'ea_intereses')

        // Limpiar otros intereses
        if (eaOtros !== oldEaOtros)
            syncFields('ea_otros_intereses', 'nm_otros_intereses')
        if (nmOtros !== oldNmOtros)
            syncFields('nm_otros_intereses', 'ea_otros_intereses')

        // Limpiar Aval
        if (aval !== oldAval) syncFields('aval', 'porcentaje_aval')
        if (porcentajeAval !== oldPorcentajeAval)
            syncFields('porcentaje_aval', 'aval')

        // Limpiar otros
        if (otros !== oldOtros) syncFields('otros', 'porcentaje_otros')
        if (porcentajeOtros !== oldPorcentajeOtros)
            syncFields('porcentaje_otros', 'otros')
    }
)

// Control checkbox aval totalizado/en columnas
watch(
    () => [form.mostrar_aval_total, form.mostrar_aval_columnas],
    ([newTotal, newColumnas], [oldTotal, oldColumnas]) => {
        if (!newTotal && !newColumnas) {
            if (oldTotal) form.mostrar_aval_total = true
            if (oldColumnas) form.mostrar_aval_columnas = true
            return
        }

        // Si se activa "Totalizado", desmarcar "Columnas"
        if (newTotal && newTotal !== oldTotal) {
            form.mostrar_aval_columnas = false
        }

        // Si activa "Columnas", desmarcar "Totalizado"
        if (newColumnas && newColumnas !== oldColumnas) {
            form.mostrar_aval_total = false
        }
    }
)

onMounted(async () => {
    start()

    try {
        await fetchAccountInfo()
    } finally {
        stop()
    }
})
</script>

<style scoped>
@keyframes highlight-fade {
    0% {
        background-color: rgba(59, 130, 246, 0.2);
        transform: scale(0.99);
    }
    50% {
        background-color: rgba(59, 130, 246, 0.1);
        transform: scale(1);
    }
    100% {
        background-color: rgba(249, 250, 251, 1);
    }
}

.animate-new-row {
    animation: highlight-fade 1.5s ease-out forwards;
    border: 2px solid var(--color-emerald-500);
}
</style>
