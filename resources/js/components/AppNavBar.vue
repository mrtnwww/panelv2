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
        <button
            class="hidden md:flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#1a5c2a] transition-colors"
        >
            <i class="fa-regular fa-file-lines"></i>
            Fórmularios
        </button>

        <!-- Notificaciones -->
        <button
            class="relative text-gray-400 hover:text-gray-600 transition-colors"
        >
            <i class="fa-regular fa-bell"></i>
            <span
                v-if="notifications > 0"
                class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center"
            >
                {{ notifications > 9 ? '9+' : notifications }}
            </span>
        </button>

        <!-- Configuración -->
        <button class="text-gray-400 hover:text-gray-600 transition-colors">
            <i class="fa-solid fa-gear"></i>
        </button>

        <!-- Divisor -->
        <div class="w-px h-6 bg-gray-200" />

        <!-- Avatar + usuario -->
        <div class="relative" ref="userMenuRef">
            <button
                @click="userMenuOpen = !userMenuOpen"
                class="flex items-center gap-2.5 hover:bg-gray-50 rounded-lg px-2 py-1.5 transition-colors"
            >
                <div
                    class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center shrink-0 overflow-hidden"
                >
                    <img
                        v-if="user.avatar"
                        :src="user.avatar"
                        :alt="user.name"
                        class="w-full h-full object-cover"
                    />
                    <span v-else class="text-xs font-semibold text-gray-500">{{
                        userInitials
                    }}</span>
                </div>
                <div class="hidden md:flex flex-col items-start leading-tight">
                    <span class="text-sm font-medium text-gray-700">{{
                        user.name
                    }}</span>
                    <span class="text-xs text-gray-400 max-w-35 truncate">{{
                        user.company
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
                            {{ user.name }}
                        </p>
                        <p class="text-xs text-gray-400 truncate">
                            {{ user.email }}
                        </p>
                    </div>
                    <button
                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 transition-colors"
                        @click="goTo('/dashboard/configuracion')"
                    >
                        <i class="fa-regular fa-user"></i>
                        Mi perfil
                    </button>
                    <button
                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 transition-colors"
                        @click="goTo('/dashboard/tutoriales')"
                    >
                        <i class="fa-solid fa-video"></i>
                        Tutoriales
                    </button>
                    <button
                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 transition-colors"
                        @click="goTo('/dashboard/cuenta-facturacion')"
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
                        @click="goTo('/dashboard/cuenta-facturacion')"
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
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';

const props = defineProps({
    sidebarWidth: {
        type: String,
        default: '224px', // 56 * 4 = 224px (w-56)
    },
    user: {
        type: Object,
        default: () => ({
            name: 'Martín Desarrollo',
            email: 'martin@credigital.com',
            company: 'IMPULSA CORP SAS / CREDITRANSITO',
            avatar: null,
        }),
    },
    notifications: {
        type: Number,
        default: 0,
    },
    creditosDelMes: {
        type: Number,
        default: 0,
    },
});

defineEmits(['toggle-sidebar']);

const router = useRouter();
const userMenuOpen = ref(false);
const userMenuRef = ref(null);

const userInitials = computed(() => {
    return props.user.name
        .split(' ')
        .slice(0, 2)
        .map(n => n[0])
        .join('')
        .toUpperCase();
});

function goTo(path) {
    userMenuOpen.value = false;
    router.push(path);
}

function handleLogout() {
    localStorage.removeItem('auth_token');
    router.push('/login');
}

// Cerrar dropdown al hacer clic fuera
function handleClickOutside(e) {
    if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
        userMenuOpen.value = false;
    }
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onUnmounted(() =>
    document.removeEventListener('mousedown', handleClickOutside)
);
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
