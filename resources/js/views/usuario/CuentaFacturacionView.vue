<template>
    <div class="flex flex-col gap-5">
        <!-- Encabezado -->
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-[#0A2540]">
                Cuenta y facturación
            </h1>
        </div>

        <!-- Contenedor con tabs + contenido -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <!-- Tabs -->
            <div
                class="flex border-b border-gray-100 overflow-x-auto overflow-y-hidden"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="[
                        'flex flex-1 flex-col items-center gap-1.5 px-5 py-3.5 text-sm font-medium whitespace-nowrap transition-all border-b-2 -mb-px',
                        activeTab === tab.key
                            ? 'border-[#1a5c2a] text-[#1a5c2a]'
                            : 'border-transparent text-gray-400 hover:text-gray-600',
                    ]"
                >
                    <i :class="[tab.icon, 'text-2xl']"></i>
                    {{ tab.label }}
                </button>
            </div>

            <!-- ── Tab: Información de la empresa ── -->
            <EmpresaTab v-if="activeTab === 'empresa'" />
            <!-- ── Tab: Pasarela de pago ── -->
            <PasarelaTab v-else-if="activeTab === 'pasarela'" />
            <!-- ── Tab: Intereses y otros ── -->
            <InteresesTab v-else-if="activeTab === 'intereses'" />
            <!-- ── Tab: Centrales de riesgo ── -->
            <CentralesTab v-else-if="activeTab === 'centrales'" />
            <!-- ── Tab: Documentos ── -->
            <DocumentosTab v-else-if="activeTab === 'documentos'" />
            <!-- ── Tab: Panel de funciones ── -->
            <PanelFuncionesTab v-else-if="activeTab === 'funciones'" />
            <!-- ── Tab: Suscripciones y transacciones ── -->
            <SuscripcionesTab v-else-if="activeTab === 'suscripcion'" />
            <!-- ── Tab: Facturación electrónica ── -->
            <FacturacionElectronicaTab
                v-else-if="activeTab === 'facturacion'"
            />

            <!-- ── Tabs pendientes ── -->
            <div
                v-else
                class="flex flex-col items-center justify-center py-20 gap-2 text-gray-300"
            >
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none">
                    <rect
                        x="3"
                        y="3"
                        width="18"
                        height="18"
                        rx="3"
                        stroke="currentColor"
                        stroke-width="1.5"
                    />
                    <path
                        d="M9 12h6M12 9v6"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                    />
                </svg>
                <p class="text-sm">Sección en construcción</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

// -- Componentes ------------------------------------------------------
import FacturacionElectronicaTab from '@/components/cuentaFacturacion/FacturacionElectronicaTab.vue'
import PanelFuncionesTab from '@/components/cuentaFacturacion/PanelFuncionesTab.vue'
import SuscripcionesTab from '@/components/cuentaFacturacion/SuscripcionesTab.vue'
import DocumentosTab from '@/components/cuentaFacturacion/DocumentosTab.vue'
import InteresesTab from '@/components/cuentaFacturacion/InteresesTab.vue'
import CentralesTab from '@/components/cuentaFacturacion/CentralesTab.vue'
import PasarelaTab from '@/components/cuentaFacturacion/PasarelaTab.vue'
import EmpresaTab from '@/components/cuentaFacturacion/EmpresaTab.vue'

// -- Tabs --------------------------------------------------------------
const tabs = [
    {
        key: 'empresa',
        label: 'Información de la empresa',
        icon: 'fa-regular fa-building',
    },
    {
        key: 'intereses',
        label: 'Intereses y otros',
        icon: 'fa-solid fa-percent',
    },
    {
        key: 'centrales',
        label: 'Centrales de riesgo',
        icon: 'fa-regular fa-clipboard',
    },
    {
        key: 'suscripcion',
        label: 'Suscripción y transacciones',
        icon: 'fa-solid fa-star',
    },
    {
        key: 'bancos',
        label: 'Lista de bancos',
        icon: 'fa-solid fa-building-columns',
    },
    {
        key: 'pasarela',
        label: 'Pasarela de pago',
        icon: 'fa-solid fa-credit-card',
    },
    {
        key: 'facturacion',
        label: 'Facturación electrónica',
        icon: 'fa-solid fa-file-invoice-dollar',
    },
    { key: 'funciones', label: 'Panel de funciones', icon: 'fa-solid fa-gear' },
    { key: 'documentos', label: 'Documentos', icon: 'fa-solid fa-file-pdf' },
]

const activeTab = ref('empresa')
</script>
