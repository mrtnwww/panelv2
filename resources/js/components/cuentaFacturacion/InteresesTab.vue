<template>
    <div class="p-6">
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
            <div
                class="relative p-5 bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm"
            >
                <div class="space-y-6">
                    <div class="space-y-3 pb-4 border-b border-gray-100">
                        <div class="flex flex-col gap-1.5">
                            <FormCheckbox
                                v-model="form.firma_electronica_enabled"
                                label="Firma electrónica"
                            />
                        </div>

                        <div class="ml-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <FormInput
                                    label="Valor firma electrónica"
                                    type="number"
                                    v-model="form.firma_electronica"
                                    placeholder="$0"
                                />
                                <FormInput
                                    label="% Firma electrónica"
                                    type="number"
                                    v-model="form.porcentaje_firma_electronica"
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

                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex flex-1 flex-col gap-1.5">
                                <FormCheckbox
                                    v-model="form.intereses_enabled"
                                    label="Intereses"
                                />
                                <p
                                    class="text-xs text-justify text-gray-400 ml-4"
                                >
                                    Corrobore y verifique que los intereses
                                    correspondan al mes y no superen la máxima
                                    tasa de usura permitida. En Colombia la
                                    Superintendencia Financiera es la entidad
                                    encargada de calcular y certificar mediante
                                    resolución la tasa de interés.
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <FormRadioGroup
                                    v-model="form.tipo_interes"
                                    :options="tipoIntereses"
                                    :vertical="false"
                                />
                                <div class="flex gap-1.5">
                                    <FormInput
                                        label="% E.A."
                                        type="number"
                                        v-model="form.ea_intereses"
                                        placeholder="26.76%"
                                    />
                                    <FormInput
                                        label="% N.M."
                                        type="number"
                                        v-model="form.tnm"
                                        placeholder="2.00%"
                                    />
                                </div>
                                <FormCheckbox
                                    v-model="form.interes_automatico_enabled"
                                    label="Automático"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex flex-1 flex-col gap-1.5">
                                <FormCheckbox
                                    v-model="form.otros_intereses_enabled"
                                    label="Otros intereses"
                                />
                                <p
                                    class="text-xs text-justify text-gray-400 ml-4"
                                >
                                    Digite un valor efectivo anual o nominal
                                    mensual que será calculado y sumado al valor
                                    del crédito.
                                </p>
                                <FormInput
                                    v-model="form.otros_intereses_concepto"
                                    placeholder="Definir concepto otros intereses"
                                />
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <div class="flex gap-1.5">
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

                    <div class="space-y-3 pt-4 border-t border-gray-100">
                        <div class="flex flex-col gap-1.5">
                            <FormCheckbox
                                v-model="form.aval_enabled"
                                label="Aval"
                            />

                            <div
                                class="bg-yellow-50 border-l-4 border-yellow-400 p-2 ml-6"
                            >
                                <p class="text-xs text-yellow-800 font-bold">
                                    ⚠️ Advertencia
                                </p>
                                <p class="text-xs text-yellow-700">
                                    Usted debe contratar este servicio y
                                    adjuntar aquí el documento correspondiente.
                                    Tenga en cuenta que el valor a cobrar a sus
                                    clientes será el mismo que usted pagará por
                                    este servicio.
                                </p>
                            </div>
                        </div>

                        <div class="ml-6 space-y-4">
                            <FileUpload
                                label="Adjuntar documento"
                                v-model="form.documento_aval"
                            />

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <FormInput
                                    label="Nombre empresa avalista"
                                    v-model="form.empresa_avalista"
                                    placeholder="Mi empresa S.A"
                                />
                                <FormInput
                                    label="NIT empresa avalista"
                                    v-model="form.empresa_avalista_nit"
                                    placeholder="123456789-0"
                                />
                            </div>

                            <div class="flex justify-center items-center gap-4">
                                <FormCheckbox
                                    v-model="form.mostrar_aval_total"
                                    label="Mostrar Aval totalizado"
                                />
                                <FormCheckbox
                                    v-model="form.mostrar_aval_columnas"
                                    label="Mostrar Aval en columnas"
                                />
                                <FormCheckbox
                                    v-model="form.restar_aval"
                                    label="Restar Aval"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 pt-4 border-t border-gray-100">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex flex-1 flex-col gap-1.5">
                                <FormCheckbox
                                    v-model="form.otros_enabled"
                                    label="Otros"
                                />
                                <p
                                    class="text-xs text-justify text-gray-400 ml-4"
                                >
                                    Los valores adicionales que usted cobre a
                                    sus clientes, debe describirlos en la
                                    sección de concepto del plan de pagos.
                                    Emitiendo factura + IVA.
                                </p>
                                <FormInput
                                    v-model="form.otros_concepto"
                                    placeholder="Otros"
                                />
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <div class="flex gap-1.5">
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
                            v-model="form.funciones"
                        />
                    </div>
                </div>

                <TableGrid
                    title="Líneas de crédito"
                    :items="[]"
                    :columns="cols"
                >
                    <template #cell(acciones)="{ item }">
                        <div class="flex justify-center">
                            <button
                                class="bg-[#10b981] text-white p-1.5 rounded-lg hover:bg-[#059669]"
                            >
                                <i class="fa-solid fa-print"></i>
                            </button>
                        </div>
                    </template>
                </TableGrid>
            </div>
        </div>

        <div class="flex gap-3 mt-8">
            <button class="btn btn-main">Aceptar</button>
            <button class="btn btn-primary">Crear línea de crédito</button>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue'

// -- Componentes --------------------------------------------------
import FormRadioGroup from '@/components/form/FormRadioGroup.vue'
import FormCheckbox from '@/components/form/FormCheckbox.vue'
import FileUpload from '@/components/form/FileUpload.vue'
import FormInput from '@/components/form/FormInput.vue'
import TableGrid from '@/components/TableGrid.vue'

const form = reactive({
    firma_electronica_enabled: false,
    firma_electronica: null,
    porcentaje_firma_electronica: null,
    iva_firma_electronica: null,
    intereses_enabled: true,
    tipo_interes: 'general',
    ea_intereses: null,
    nm_intereses: null,
    interes_automatico_enabled: false,
    otros_intereses_enabled: false,
    otros_intereses_concepto: '',
    ea_otros_intereses: null,
    nm_otros_intereses: null,
    aval_enabled: true,
    documento_aval: null,
    aval: null,
    porcentaje_aval: null,
    iva_aval: null,
    empresa_avalista: '',
    empresa_avalista_nit: '',
    mostrar_aval_total: false,
    mostrar_aval_columnas: false,
    restar_aval: false,
    otros_enabled: false,
    otros_concepto: '',
    otros: null,
    porcentaje_otros: null,
    funciones: [],
})

const tipoIntereses = [
    { value: 'general', label: 'General' },
    { value: 'individual', label: 'Individual' },
]

const adicionalesDestinosOpts = [
    { label: 'Redondear valores intereses', value: 'redondear' },
    { label: 'Exención de interés y otros', value: 'exencion' },
    { label: 'Línea de liquidez', value: 'liquidez' },
    { label: 'Valor de consulta a centrales', value: 'consulta' },
]

const creditos = ref([
    { id: 1, nombre: 'TÉCNICO MECÁNICA', periodicidad: 6, maximo: '$600,000' },
    { id: 2, nombre: 'SOAT', periodicidad: 6, maximo: '$1,600,000' },
    {
        id: 3,
        nombre: 'SOAT Y TÉCNICO MECÁNICA',
        periodicidad: 6,
        maximo: '$2,000,000',
    },
])

const cols = [
    { key: 'nombre', label: 'Nombre', width: '1.5fr' },
    { key: 'periodicidad', label: 'Periodicidad', width: '80px' },
    { key: 'valor_minimo', label: 'Valor mínimo', width: '1.5fr' },
    { key: 'valor_maximo', label: 'Valor máximo', width: '1.5fr' },
    {
        key: 'acciones',
        label: 'Acciones',
        width: '80px',
        headerClass: 'text-center',
    },
]
</script>
