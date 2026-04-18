<template>
    <header
        class="fixed top-0 right-0 z-20 bg-white border-b border-gray-100 flex items-center h-15 px-5 gap-4 transition-all duration-300"
        :style="{ left: sidebarWidth }"
    >
        <!-- Botón hamburguesa -->
        <button
            @click="$emit('toggle-sidebar')"
            class="text-gray-400 hover:text-gray-600 transition-colors shrink-0"
            aria-label="Toggle sidebar"
        >
            <i class="fa-solid fa-bars"></i>
        </button>

        <!-- Chip: créditos del mes -->
        <div
            class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-500"
        >
            <i class="fa-solid fa-credit-card"></i>
            <span>Créditos colocados este mes:</span>
            <span class="font-semibold text-[#1a5c2a]">{{
                creditosDelMes
            }}</span>
        </div>

        <!-- Spacer -->
        <div class="flex-1" />

        <!-- Fórmularios -->
        <div class="relative" ref="formulariosRef">
            <button
                @click="toggleMenu('forms')"
                class="hidden md:flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#1a5c2a] transition-colors"
            >
                <i class="fa-regular fa-file-lines"></i>
                Fórmularios
            </button>

            <!-- Dropdown formularios -->
            <transition name="dropdown">
                <div
                    v-if="formulariosMenuOpen"
                    class="absolute right-0 top-full mt-2 w-56 bg-white border border-gray-100 rounded-xl shadow-lg shadow-gray-200/60 py-2 z-50"
                >
                    <p
                        class="px-4 py-1.5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest"
                    >
                        Formularios
                    </p>

                    <button
                        v-for="item in formularios"
                        :key="item.route"
                        @click="quickNav(item.route)"
                        class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition-colors"
                    >
                        <div
                            class="w-8 h-8 rounded-full bg-[#1a5c2a] text-white flex items-center justify-center shrink-0"
                        >
                            <i :class="item.icon"></i>
                        </div>
                        <span class="text-sm text-gray-700">{{
                            item.label
                        }}</span>
                    </button>
                </div>
            </transition>
        </div>

        <!-- Notificaciones -->
        <div class="relative" ref="notificationsRef">
            <button
                @click="toggleMenu('notis')"
                class="relative text-gray-400 hover:text-gray-600 transition-colors"
            >
                <i class="fa-regular fa-bell"></i>
                <span
                    v-if="notifications > 0"
                    class="absolute -top-2 -right-2 w-5.5 h-5.5 bg-red-500 text-white text-[12px] font-bold rounded-full flex items-center justify-center border-2 border-white"
                >
                    {{ notifications > 9 ? '9+' : notifications }}
                </span>
            </button>

            <transition name="dropdown">
                <div
                    v-if="notificationsMenuOpen"
                    class="fixed inset-x-4 top-16 mx-auto w-auto max-w-[calc(100vw-2rem)] md:absolute md:inset-auto md:right-0 md:top-full md:mt-2 md:w-80 bg-white border border-gray-100 rounded-xl shadow-lg z-50 overflow-hidden"
                >
                    <div
                        class="px-4 py-3 border-b border-gray-50 flex justify-between items-center bg-gray-50/50"
                    >
                        <span class="text-xs font-bold text-gray-500 uppercase"
                            >Notificaciones</span
                        >
                        <button
                            @click="handleMarkAllAsRead"
                            v-if="
                                notificationStore.unreadCount > 0 &&
                                !notificationStore.loading
                            "
                            class="text-xs text-emerald-500 hover:text-emerald-600 hover:underline font-medium"
                        >
                            Marcar todo como leído
                        </button>
                    </div>

                    <div class="max-h-75 min-h-37.5 overflow-y-auto relative">
                        <div
                            v-if="notificationStore.loading"
                            class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 flex items-center justify-center"
                        >
                            <div class="flex flex-col items-center gap-2">
                                <div
                                    class="w-8 h-8 border-2 border-gray-200 border-t-emerald-500 rounded-full animate-spin"
                                ></div>
                                <span class="text-sm text-gray-400 font-medium"
                                    >Cargando...</span
                                >
                            </div>
                        </div>

                        <div
                            v-if="
                                !itemsNotifications.length &&
                                !notificationStore.loading
                            "
                            class="p-8 text-center"
                        >
                            <i
                                class="fa-regular fa-bell-slash text-gray-200 text-3xl mb-2 block"
                            ></i>
                            <p class="text-xs text-gray-400">
                                No tienes notificaciones pendientes
                            </p>
                        </div>

                        <div v-if="!notificationStore.loading">
                            <div
                                v-for="noti in itemsNotifications"
                                :key="noti.id"
                                class="relative px-4 py-3 transition-all border-b border-gray-50 last:border-0 cursor-pointer"
                                :class="
                                    !noti.visualized_at
                                        ? 'bg-emerald-50/50 hover:bg-emerald-50 border-l-4 border-l-emerald-500'
                                        : 'hover:bg-gray-50 border-l-4 border-l-transparent'
                                "
                            >
                                <div
                                    class="flex items-start justify-between gap-2"
                                >
                                    <div class="flex-1 min-w-0">
                                        <p
                                            class="text-sm font-semibold mb-1 truncate"
                                            :class="
                                                !noti.visualized_at
                                                    ? 'text-gray-900'
                                                    : 'text-gray-500'
                                            "
                                        >
                                            {{ noti.title }}
                                        </p>
                                        <p
                                            class="text-xs mb-1 line-clamp-2"
                                            :class="
                                                !noti.visualized_at
                                                    ? 'text-gray-700'
                                                    : 'text-gray-400'
                                            "
                                        >
                                            {{ noti.content }}
                                        </p>
                                        <p
                                            class="text-xs text-emerald-500 font-medium italic"
                                        >
                                            {{
                                                formatDate(
                                                    noti.created_at
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <span
                                        v-if="!noti.visualized_at"
                                        class="shrink-0 text-[9px] font-bold uppercase tracking-wide text-emerald-600 bg-emerald-100 px-1.5 py-0.5 rounded-md mt-0.5"
                                    >
                                        Nueva
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </div>

        <!-- Accesos rápidos -->
        <div class="relative" ref="quickMenuRef">
            <button
                @click="toggleMenu('quick')"
                :class="[
                    'w-8 h-8 rounded-lg flex items-center justify-center transition-all',
                    quickMenuOpen
                        ? 'bg-[#1a5c2a] text-white'
                        : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100',
                ]"
            >
                <i class="fa-solid fa-gear"></i>
            </button>

            <!-- Dropdown accesos rápidos -->
            <transition name="dropdown">
                <div
                    v-if="quickMenuOpen"
                    class="absolute right-0 top-full mt-2 w-56 bg-white border border-gray-100 rounded-xl shadow-lg shadow-gray-200/60 py-2 z-50"
                >
                    <p
                        class="px-4 py-1.5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest"
                    >
                        Accesos rápidos
                    </p>

                    <button
                        v-for="item in quickActions"
                        :key="item.route"
                        @click="quickNav(item.route)"
                        class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition-colors"
                    >
                        <div
                            class="w-8 h-8 rounded-full bg-[#1a5c2a] text-white flex items-center justify-center shrink-0"
                        >
                            <i :class="item.icon"></i>
                        </div>
                        <span class="text-sm text-gray-700">{{
                            item.label
                        }}</span>
                    </button>
                </div>
            </transition>
        </div>

        <!-- Divisor -->
        <div class="w-px h-6 bg-gray-200" />

        <!-- Avatar + usuario -->
        <div class="relative" ref="userMenuRef">
            <button
                @click="toggleMenu('user')"
                class="flex items-center gap-2.5 hover:bg-gray-50 rounded-lg px-2 py-1.5 transition-colors"
            >
                <div
                    class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center shrink-0 overflow-hidden"
                >
                    <img
                        v-if="user?.image"
                        :src="user.image"
                        :alt="user?.persona?.nombre"
                        class="w-full h-full object-contain"
                    />
                    <span v-else class="text-xs font-semibold text-gray-500">{{
                        userInitials
                    }}</span>
                </div>
                <div class="hidden md:flex flex-col items-start leading-tight">
                    <span class="text-sm font-medium text-gray-700">{{
                        user?.persona?.nombre
                    }}</span>
                    <span class="text-xs text-gray-400 max-w-35 truncate">{{
                        user?.empresa?.razon_social
                    }}</span>
                </div>
                <svg
                    width="14"
                    height="14"
                    viewBox="0 0 16 16"
                    fill="none"
                    class="text-gray-400 hidden md:block"
                >
                    <path
                        d="M4 6L8 10L12 6"
                        stroke="currentColor"
                        stroke-width="1.3"
                        stroke-linecap="round"
                    />
                </svg>
            </button>

            <!-- Dropdown usuario -->
            <transition name="dropdown">
                <div
                    v-if="userMenuOpen"
                    class="absolute right-0 top-full mt-2 w-52 bg-white border border-gray-100 rounded-xl shadow-lg shadow-gray-200/60 py-1 z-50"
                >
                    <div class="px-4 py-2.5 border-b border-gray-100">
                        <p class="text-sm font-medium text-gray-700">
                            {{ user?.persona?.nombre }}
                        </p>
                        <p class="text-xs text-gray-400 truncate">
                            {{ user?.correo }}
                        </p>
                    </div>
                    <button
                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 transition-colors"
                        @click="goTo('/usuario/perfil')"
                    >
                        <i class="fa-regular fa-user"></i>
                        Mi perfil
                    </button>
                    <button
                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 transition-colors"
                        @click="openTutorials"
                    >
                        <i class="fa-solid fa-video"></i>
                        Tutoriales
                    </button>
                    <button
                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 transition-colors"
                        @click="goTo('/usuario/cuenta-facturacion')"
                    >
                        <i class="fa-solid fa-gears"></i>
                        Cuenta y facturación
                    </button>
                    <button
                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 transition-colors"
                        @click="goTo('/dashboard/cuenta-facturacion')"
                    >
                        <i class="fa-solid fa-file-signature"></i>
                        Contrato y reglamento
                    </button>
                    <button
                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 transition-colors"
                        @click="goTo('/usuario/cambiar-clave')"
                    >
                        <i class="fa-solid fa-key"></i>
                        Cambio de clave
                    </button>
                    <button
                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 transition-colors"
                        @click="goTo('/dashboard/cuenta-facturacion')"
                    >
                        <i class="fa-solid fa-phone"></i>
                        Línea de soporte
                    </button>
                    <button
                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition-colors rounded-b-xl"
                        @click="handleLogout"
                    >
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        Cerrar sesión
                    </button>
                </div>
            </transition>
        </div>
    </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

// -- Store -------------------------------------------------
import { useNotificationStore } from '@/stores/notifications'
import { useAuthStore } from '@/stores/auth'

// -- Router -----------------------------------------------
import { useRouter } from 'vue-router'

// -- Utils ------------------------------------------------
import { formatDate } from '@/utils/format'

const props = defineProps({
    sidebarWidth: {
        type: String,
        default: '224px', // 56 * 4 = 224px (w-56)
    },
    user: {
        type: Object,
        default: () => ({}),
    },
    notifications: {
        type: Number,
        default: 0,
    },
    itemsNotifications: {
        type: Array,
        default: () => [],
    },
    creditosDelMes: {
        type: Number,
        default: 0,
    },
})

defineEmits(['toggle-sidebar'])

const notificationStore = useNotificationStore()
const auth = useAuthStore()

const router = useRouter()

const userMenuRef = ref(null)
const quickMenuRef = ref(null)
const formulariosRef = ref(null)
const notificationsRef = ref(null)

const userMenuOpen = ref(false)
const quickMenuOpen = ref(false)
const formulariosMenuOpen = ref(false)
const notificationsMenuOpen = ref(false)

const quickActions = [
    {
        label: 'Nuevo cliente',
        route: '/clientes/nuevo',
        icon: 'fa-solid fa-user',
    },
    {
        label: 'Crear crédito',
        route: '/creditos/nuevo',
        icon: 'fa-solid fa-wallet',
    },
    {
        label: 'Generar abono',
        route: '/abonos/nuevo',
        icon: 'fa-solid fa-dollar-sign',
    },
]

const formularios = [
    {
        label: 'Clientes',
        route: '/46/solicitud-credito',
        icon: 'fa-solid fa-user',
    },
    {
        label: 'Libranza',
        route: '/46/solicitud-credito',
        icon: 'fa-solid fa-user-group',
    },
    {
        label: 'Vehiculo',
        route: '/46/solicitud-credito',
        icon: 'fa-solid fa-car',
    },
    {
        label: 'Vivienda',
        route: '/46/solicitud-credito',
        icon: 'fa-solid fa-home',
    },
]

function quickNav(route) {
    quickMenuOpen.value = false
    router.push(route)
}

const userInitials = computed(() => {
    return props.user?.persona?.nombre
        .split(' ')
        .slice(0, 2)
        .map(n => n[0])
        .join('')
        .toUpperCase()
})

function goTo(path) {
    userMenuOpen.value = false
    router.push(path)
}

function openTutorials() {
    window.open(
        'https://www.youtube.com/playlist?list=PLpgxgJMebF-3qrxBqaS9O356BHs74mARb',
        '_blank'
    )
}

async function handleLogout() {
    await auth.logout()
    router.push('/login')
}

// Marcar notificaciones como leídas
const handleMarkAllAsRead = async () => {
    await notificationStore.visualizeNotifications()
}

// Cerrar dropdown al hacer clic fuera
function handleClickOutside(e) {
    const isInsideMenu =
        userMenuRef.value?.contains(e.target) ||
        quickMenuRef.value?.contains(e.target) ||
        formulariosRef.value?.contains(e.target) ||
        notificationsRef.value?.contains(e.target)

    if (!isInsideMenu) {
        userMenuOpen.value = false
        quickMenuOpen.value = false
        formulariosMenuOpen.value = false
        notificationsMenuOpen.value = false
    }
}

function toggleMenu(menu) {
    closeAllMenusExcept(menu)

    if (menu === 'user') userMenuOpen.value = !userMenuOpen.value
    if (menu === 'quick') quickMenuOpen.value = !quickMenuOpen.value
    if (menu === 'forms') formulariosMenuOpen.value = !formulariosMenuOpen.value
    if (menu === 'notis')
        notificationsMenuOpen.value = !notificationsMenuOpen.value
}

function closeAllMenusExcept(menuRef) {
    if (menuRef !== 'user') userMenuOpen.value = false
    if (menuRef !== 'quick') quickMenuOpen.value = false
    if (menuRef !== 'forms') formulariosMenuOpen.value = false
    if (menuRef !== 'notis') notificationsMenuOpen.value = false
}

onMounted(async () =>
    document.addEventListener('mousedown', handleClickOutside)
)
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside))
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition:
        opacity 0.15s ease,
        transform 0.15s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
