<template>
    <aside
        :class="[
            'fixed top-0 left-0 h-screen bg-[#1a5c2a] flex flex-col z-30 transition-all duration-300',
            isOpen ? 'w-56' : 'w-0 lg:w-16 overflow-hidden',
        ]"
    >
        <!-- Logo -->
        <div
            class="flex items-center gap-2 px-4 py-4 border-b border-white/10 shrink-0 min-h-15"
        >
            <router-link
                :class="[
                    'text-white font-bold text-xl tracking-tight transition-all duration-300 whitespace-nowrap xl:text-4xl',
                    isOpen ? 'opacity-100 max-w-xs' : 'opacity-0 max-w-0',
                ]"
                to="/dashboard"
            >
                Credi<span class="text-emerald-300">gital</span>
            </router-link>
            <router-link
                :class="[
                    'text-emerald-300 font-bold text-xl transition-all duration-300 xl:text-2xl',
                    isOpen ? 'hidden' : 'hidden lg:block',
                ]"
                to="/dashboard"
            >
                C
            </router-link>
        </div>

        <!-- Navegación -->
        <nav class="flex-1 overflow-y-auto py-4 px-2 flex flex-col gap-0.5">
            <NavGroup label="Gestión" :collapsed="!isOpen" />

            <NavItem
                v-for="item in gestionItems"
                :key="item.name"
                :item="item"
                :collapsed="!isOpen"
                :active-path="currentPath"
                @navigate="navigate"
            />

            <NavGroup
                label="Administración"
                :collapsed="!isOpen"
                class="mt-4"
            />

            <NavItem
                v-for="item in adminItems"
                :key="item.name"
                :item="item"
                :collapsed="!isOpen"
                :active-path="currentPath"
                @navigate="navigate"
            />
        </nav>

        <!-- Footer -->
        <div class="px-2 py-3 border-t border-white/10 shrink-0">
            <button
                @click="handleLogout"
                :class="[
                    'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/50',
                    'hover:text-white hover:bg-white/10 transition-all text-sm',
                    !isOpen && 'lg:justify-center',
                ]"
            >
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span
                    :class="[
                        'whitespace-nowrap transition-all duration-300 overflow-hidden',
                        isOpen ? 'opacity-100 max-w-xs' : 'opacity-0 max-w-0',
                    ]"
                >
                    Cerrar sesión
                </span>
            </button>
        </div>
    </aside>

    <!-- Overlay móvil -->
    <div
        v-if="isOpen && isMobile"
        class="fixed inset-0 bg-black/40 z-20 lg:hidden"
        @click="$emit('close')"
    />
</template>

<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import NavGroup from '@/components/sidebar/NavGroup.vue'
import NavItem from '@/components/sidebar/NavItem.vue'

defineProps({
    isOpen: {
        type: Boolean,
        default: true,
    },
    isMobile: {
        type: Boolean,
        default: false,
    },
})

defineEmits(['close'])

const router = useRouter()
const route = useRoute()
const currentPath = computed(() => route.path)

// ── Ítems de navegación ────────────────────────────────────────────────────

const gestionItems = [
    {
        name: 'Clientes',
        path: '/clientes',
        icon: 'fa-regular fa-user',
        children: [
            { name: 'Nuevo cliente', path: '/clientes/nuevo' },
            { name: 'Lista de clientes', path: '/clientes' },
            { name: 'Validar cliente', path: '/clientes/validar' },
            { name: 'Análisis cliente', path: '/clientes/analisis' },
        ],
    },
    {
        name: 'Créditos',
        path: '/creditos',
        icon: 'fa-solid fa-wallet',
        children: [
            { name: 'Crear crédito', path: '/creditos/nuevo' },
            { name: 'Estado de crédito', path: '/creditos/estado' },
        ],
    },
    {
        name: 'Abonos',
        path: '/abonos',
        icon: 'fa-solid fa-dollar-sign',
        children: [{ name: 'Abonar cuota', path: '/abonos/nuevo' }],
    },
    {
        name: 'Productos',
        path: '/productos',
        icon: 'fa-solid fa-cart-flatbed',
        children: [{ name: 'Crear producto', path: '/productos/nuevo' }],
    },
    {
        name: 'Cobranza',
        path: '/cobranza',
        icon: 'fa-solid fa-hand-holding-dollar',
        children: [
            { name: 'Créditos', path: '/cobranza/creditos' },
            { name: 'Tareas', path: '/cobranza/tareas' },
        ],
    },
]

const adminItems = [
    {
        name: 'Contabilidad',
        path: '/contabilidad',
        icon: 'fa-solid fa-calculator',
        children: [
            {
                name: 'Recibo de caja CXC',
                path: '/contabilidad/recibo-cxc',
            },
            {
                name: 'Costos y gastos',
                path: '/contabilidad/costos-gastos',
            },
        ],
    },
    {
        name: 'Informes',
        path: '/informes',
        icon: 'fa-solid fa-chart-line',
        children: [
            { name: 'Créditos', path: '/informes/creditos' },
            { name: 'Abonos', path: '/informes/abonos' },
            { name: 'Cartera por edades', path: '/informes/cartera' },
            { name: 'Consultas', path: '/informes/consultas' },
            { name: 'CXC aliado', path: '/informes/cxc-aliado' },
            { name: 'Comisiones', path: '/informes/comisiones' },
            {
                name: 'Administrativo',
                path: '/informes/administrativo',
            },
            { name: 'Corresponsal', path: '/informes/corresponsal' },
            { name: 'Ventas', path: '/informes/ventas' },
            { name: 'Facturas', path: '/informes/facturas' },
        ],
    },
    {
        name: 'Configuración',
        path: '/configuracion',
        icon: 'fa-solid fa-gear',
        children: [
            {
                name: 'Gestión de usuarios',
                path: '/configuracion/usuarios',
            },
            {
                name: 'Parametros correo',
                path: '/configuracion/parametros-correo',
            },
            {
                name: 'Plantillas de correo',
                path: '/configuracion/plantillas-correo',
            },
            {
                name: 'Sedes/Aliados',
                path: '/configuracion/sedes-aliados',
            },
        ],
    },
]

// ── Métodos ────────────────────────────────────────────────────────────────

function navigate(path) {
    router.push(path)
}

function handleLogout() {
    localStorage.removeItem('auth_token')
    router.push('/login')
}
</script>
