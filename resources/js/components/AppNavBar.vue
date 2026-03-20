<template>
    <header
        class="fixed top-0 right-0 z-20 bg-white border-b border-gray-100 flex items-center h-[60px] px-5 gap-4 transition-all duration-300"
        :style="{ left: sidebarWidth }"
    >
        <!-- Botón hamburguesa -->
        <button
            @click="$emit('toggle-sidebar')"
            class="text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0"
            aria-label="Toggle sidebar"
        >
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path
                    d="M3 5h14M3 10h14M3 15h14"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linecap="round"
                />
            </svg>
        </button>

        <!-- Chip: créditos del mes -->
        <div
            class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-500"
        >
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                <rect
                    x="1.5"
                    y="3.5"
                    width="13"
                    height="9"
                    rx="1.5"
                    stroke="currentColor"
                    stroke-width="1.2"
                />
                <path d="M1.5 7h13" stroke="currentColor" stroke-width="1.2" />
            </svg>
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
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path
                    d="M13 2H3a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1z"
                    stroke="currentColor"
                    stroke-width="1.2"
                />
                <path
                    d="M5 6h6M5 9h4"
                    stroke="currentColor"
                    stroke-width="1.2"
                    stroke-linecap="round"
                />
            </svg>
            Fórmularios
        </button>

        <!-- Notificaciones -->
        <button
            class="relative text-gray-400 hover:text-gray-600 transition-colors"
        >
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path
                    d="M10 2a6 6 0 0 0-6 6v3l-1.5 2.5h15L16 11V8a6 6 0 0 0-6-6z"
                    stroke="currentColor"
                    stroke-width="1.3"
                />
                <path
                    d="M8 15.5a2 2 0 0 0 4 0"
                    stroke="currentColor"
                    stroke-width="1.3"
                />
            </svg>
            <span
                v-if="notifications > 0"
                class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center"
            >
                {{ notifications > 9 ? "9+" : notifications }}
            </span>
        </button>

        <!-- Configuración -->
        <button class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path
                    d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"
                    stroke="currentColor"
                    stroke-width="1.3"
                />
                <path
                    d="M16.2 12.5a1.4 1.4 0 0 0 .28 1.54l.05.05a1.65 1.65 0 1 1-2.33 2.33l-.05-.05a1.4 1.4 0 0 0-1.54-.28 1.4 1.4 0 0 0-.85 1.28V17.5a1.65 1.65 0 1 1-3.3 0v-.08a1.4 1.4 0 0 0-.92-1.28 1.4 1.4 0 0 0-1.54.28l-.05.05a1.65 1.65 0 1 1-2.33-2.33l.05-.05a1.4 1.4 0 0 0 .28-1.54 1.4 1.4 0 0 0-1.28-.85H2.5a1.65 1.65 0 1 1 0-3.3h.08a1.4 1.4 0 0 0 1.28-.92 1.4 1.4 0 0 0-.28-1.54l-.05-.05a1.65 1.65 0 1 1 2.33-2.33l.05.05a1.4 1.4 0 0 0 1.54.28h.07a1.4 1.4 0 0 0 .85-1.28V2.5a1.65 1.65 0 1 1 3.3 0v.08a1.4 1.4 0 0 0 .85 1.28 1.4 1.4 0 0 0 1.54-.28l.05-.05a1.65 1.65 0 1 1 2.33 2.33l-.05.05a1.4 1.4 0 0 0-.28 1.54v.07a1.4 1.4 0 0 0 1.28.85h.08a1.65 1.65 0 1 1 0 3.3h-.08a1.4 1.4 0 0 0-1.28.85z"
                    stroke="currentColor"
                    stroke-width="1.3"
                />
            </svg>
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
                    class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0 overflow-hidden"
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
                    <span
                        class="text-xs text-gray-400 max-w-[140px] truncate"
                        >{{ user.company }}</span
                    >
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
                        <svg
                            width="15"
                            height="15"
                            viewBox="0 0 16 16"
                            fill="none"
                        >
                            <path
                                d="M8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"
                                stroke="currentColor"
                                stroke-width="1.2"
                            />
                        </svg>
                        Mi perfil
                    </button>
                    <button
                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition-colors rounded-b-xl"
                        @click="handleLogout"
                    >
                        <svg
                            width="15"
                            height="15"
                            viewBox="0 0 16 16"
                            fill="none"
                        >
                            <path
                                d="M6 14H2.667A.667.667 0 0 1 2 13.333V2.667A.667.667 0 0 1 2.667 2H6"
                                stroke="currentColor"
                                stroke-width="1.2"
                                stroke-linecap="round"
                            />
                            <path
                                d="M10.667 11.333L14 8l-3.333-3.333"
                                stroke="currentColor"
                                stroke-width="1.2"
                                stroke-linecap="round"
                            />
                            <path
                                d="M14 8H6"
                                stroke="currentColor"
                                stroke-width="1.2"
                                stroke-linecap="round"
                            />
                        </svg>
                        Cerrar sesión
                    </button>
                </div>
            </transition>
        </div>
    </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";

const props = defineProps({
    sidebarWidth: {
        type: String,
        default: "224px", // 56 * 4 = 224px (w-56)
    },
    user: {
        type: Object,
        default: () => ({
            name: "Martín Desarrollo",
            email: "martin@credigital.com",
            company: "IMPULSA CORP SAS / CREDITRANSITO",
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

defineEmits(["toggle-sidebar"]);

const router = useRouter();
const userMenuOpen = ref(false);
const userMenuRef = ref(null);

const userInitials = computed(() => {
    return props.user.name
        .split(" ")
        .slice(0, 2)
        .map((n) => n[0])
        .join("")
        .toUpperCase();
});

function goTo(path) {
    userMenuOpen.value = false;
    router.push(path);
}

function handleLogout() {
    localStorage.removeItem("auth_token");
    router.push("/login");
}

// Cerrar dropdown al hacer clic fuera
function handleClickOutside(e) {
    if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
        userMenuOpen.value = false;
    }
}

onMounted(() => document.addEventListener("mousedown", handleClickOutside));
onUnmounted(() =>
    document.removeEventListener("mousedown", handleClickOutside),
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
