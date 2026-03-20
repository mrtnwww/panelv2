<template>
    <aside
        :class="[
            'fixed top-0 left-0 h-screen bg-[#1a5c2a] flex flex-col z-30 transition-all duration-300',
            isOpen ? 'w-56' : 'w-0 lg:w-16 overflow-hidden',
        ]"
    >
        <!-- Logo -->
        <div
            class="flex items-center gap-2 px-4 py-4 border-b border-white/10 flex-shrink-0 min-h-[60px]"
        >
            <span
                :class="[
                    'text-white font-bold text-xl tracking-tight transition-all duration-300 whitespace-nowrap overflow-hidden',
                    isOpen
                        ? 'opacity-100 max-w-xs'
                        : 'opacity-0 lg:opacity-0 max-w-0',
                ]"
            >
                Credi<span class="text-emerald-300">gital</span>
            </span>
            <!-- Ícono colapsado -->
            <span
                :class="[
                    'text-emerald-300 font-bold text-xl transition-all duration-300',
                    isOpen ? 'hidden' : 'hidden lg:block',
                ]"
                >C</span
            >
        </div>

        <!-- Navegación -->
        <nav class="flex-1 overflow-y-auto py-4 px-2 flex flex-col gap-1">
            <!-- Grupo: Gestión -->
            <NavGroup label="Gestión" :collapsed="!isOpen" />

            <NavItem
                v-for="item in gestionItems"
                :key="item.name"
                :item="item"
                :collapsed="!isOpen"
                :active-path="currentPath"
                @navigate="navigate"
            />

            <!-- Grupo: Administración -->
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

        <!-- Footer del sidebar -->
        <div class="px-2 py-3 border-t border-white/10 flex-shrink-0">
            <button
                @click="handleLogout"
                :class="[
                    'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/50 hover:text-white hover:bg-white/10 transition-all text-sm',
                    !isOpen && 'lg:justify-center',
                ]"
            >
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 16 16"
                    fill="none"
                    class="flex-shrink-0"
                >
                    <path
                        d="M6 14H2.667A.667.667 0 0 1 2 13.333V2.667A.667.667 0 0 1 2.667 2H6"
                        stroke="currentColor"
                        stroke-width="1.3"
                        stroke-linecap="round"
                    />
                    <path
                        d="M10.667 11.333L14 8l-3.333-3.333"
                        stroke="currentColor"
                        stroke-width="1.3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M14 8H6"
                        stroke="currentColor"
                        stroke-width="1.3"
                        stroke-linecap="round"
                    />
                </svg>
                <span
                    :class="[
                        'whitespace-nowrap transition-all duration-300 overflow-hidden',
                        isOpen
                            ? 'opacity-100 max-w-xs'
                            : 'opacity-0 max-w-0 lg:max-w-0',
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
import { computed } from "vue";
import { useRouter, useRoute } from "vue-router";

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: true,
    },
    isMobile: {
        type: Boolean,
        default: false,
    },
});

defineEmits(["close"]);

const router = useRouter();
const route = useRoute();
const currentPath = computed(() => route.path);

// ── Ítems de navegación ────────────────────────────────────────────────────

const gestionItems = [
    {
        name: "Clientes",
        path: "/dashboard/clientes",
        icon: `<path d="M10 9A3 3 0 1 0 10 3a3 3 0 0 0 0 6z" stroke="currentColor" stroke-width="1.3"/>
               <path d="M3 15a7 7 0 0 1 14 0" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>`,
        children: [
            { name: "Lista de clientes", path: "/dashboard/clientes" },
            { name: "Nuevo cliente", path: "/dashboard/clientes/nuevo" },
        ],
    },
    {
        name: "Créditos",
        path: "/dashboard/creditos",
        icon: `<rect x="2" y="4" width="16" height="12" rx="2" stroke="currentColor" stroke-width="1.3"/>
               <path d="M2 8h16" stroke="currentColor" stroke-width="1.3"/>
               <path d="M6 12h4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>`,
        children: [
            { name: "Ver créditos", path: "/dashboard/creditos" },
            { name: "Nuevo crédito", path: "/dashboard/creditos/nuevo" },
        ],
    },
    {
        name: "Abonos",
        path: "/dashboard/abonos",
        icon: `<path d="M12 2v20M17 5H9.5a3.5 3.5 0 1 0 0 7h5a3.5 3.5 0 1 1 0 7H6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>`,
        children: [
            { name: "Ver abonos", path: "/dashboard/abonos" },
            { name: "Nuevo abono", path: "/dashboard/abonos/nuevo" },
        ],
    },
    {
        name: "Productos",
        path: "/dashboard/productos",
        icon: `<path d="M3 3h2l.4 2M7 13h10l4-8H5.4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
               <circle cx="9" cy="16" r="1" stroke="currentColor" stroke-width="1.3"/>
               <circle cx="20" cy="16" r="1" stroke="currentColor" stroke-width="1.3"/>`,
        children: [
            { name: "Ver productos", path: "/dashboard/productos" },
            { name: "Nuevo producto", path: "/dashboard/productos/nuevo" },
        ],
    },
    {
        name: "Cobranza",
        path: "/dashboard/cobranza",
        icon: `<path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.5 19" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
               <path d="M14.05 2a9 9 0 0 1 8 7.94" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
               <path d="M14.05 6A5 5 0 0 1 18 9.95" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
               <path d="M2 2l20 20" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>`,
    },
];

const adminItems = [
    {
        name: "Contabilidad",
        path: "/dashboard/contabilidad",
        icon: `<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="1.3"/>
               <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>`,
    },
    {
        name: "Informes",
        path: "/dashboard/informes",
        icon: `<path d="M18 20V10M12 20V4M6 20v-6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>`,
    },
    {
        name: "Configuración",
        path: "/dashboard/configuracion",
        icon: `<path d="M12 15A3 3 0 1 0 12 9a3 3 0 0 0 0 6z" stroke="currentColor" stroke-width="1.3"/>
               <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" stroke="currentColor" stroke-width="1.3"/>`,
    },
];

// ── Métodos ────────────────────────────────────────────────────────────────

function navigate(path) {
    router.push(path);
}

function handleLogout() {
    localStorage.removeItem("auth_token");
    router.push("/login");
}

// ── Componentes internos ───────────────────────────────────────────────────

const NavGroup = {
    props: ["label", "collapsed"],
    template: `
        <div :class="['px-3 mb-1 transition-all overflow-hidden', collapsed ? 'h-0 opacity-0' : 'h-5 opacity-100']">
            <span class="text-white/30 text-[10px] font-semibold uppercase tracking-widest">{{ label }}</span>
        </div>
    `,
};

const NavItem = {
    props: ["item", "collapsed", "activePath"],
    emits: ["navigate"],
    data() {
        return { open: false };
    },
    computed: {
        isActive() {
            return this.activePath.startsWith(this.item.path);
        },
    },
    template: `
        <div>
            <button
                @click="item.children ? (open = !open) : $emit('navigate', item.path)"
                :class="[
                    'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all text-sm group',
                    collapsed ? 'justify-center' : '',
                    isActive
                        ? 'bg-white/15 text-white font-medium'
                        : 'text-white/60 hover:text-white hover:bg-white/10',
                ]"
                :title="collapsed ? item.name : ''"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="flex-shrink-0" v-html="item.icon" />

                <span :class="['flex-1 text-left whitespace-nowrap transition-all duration-300 overflow-hidden', collapsed ? 'opacity-0 max-w-0' : 'opacity-100 max-w-xs']">
                    {{ item.name }}
                </span>

                <!-- Chevron si tiene hijos -->
                <svg v-if="item.children && !collapsed" width="14" height="14" viewBox="0 0 16 16" fill="none"
                    :class="['transition-transform duration-200 flex-shrink-0', open ? 'rotate-180' : '']">
                    <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
                </svg>
            </button>

            <!-- Subitems -->
            <div v-if="item.children && !collapsed && open" class="ml-4 mt-0.5 flex flex-col gap-0.5 border-l border-white/10 pl-3">
                <button
                    v-for="child in item.children"
                    :key="child.path"
                    @click="$emit('navigate', child.path)"
                    :class="[
                        'w-full text-left px-2 py-1.5 rounded-md text-xs transition-all',
                        activePath === child.path
                            ? 'text-white font-medium'
                            : 'text-white/45 hover:text-white/80',
                    ]"
                >
                    {{ child.name }}
                </button>
            </div>
        </div>
    `,
};
</script>
