<template>
    <div class="min-h-screen bg-gray-50 font-sans">
        <!-- Sidebar -->
        <AppSidebar
            :is-open="sidebarOpen"
            :is-mobile="isMobile"
            @close="sidebarOpen = false"
        />

        <!-- Navbar -->
        <AppNavbar
            :sidebar-width="navbarLeft"
            :user="currentUser"
            :notifications="7"
            :creditos-del-mes="0"
            @toggle-sidebar="toggleSidebar"
        />

        <!-- Contenido principal -->
        <main
            class="transition-all duration-300 pt-15 h-screen overflow-y-auto"
            :style="{ marginLeft: mainMargin }"
        >
            <div class="p-6">
                <router-view />
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import AppSidebar from '@/components/AppSideBar.vue'
import AppNavbar from '@/components/AppNavbar.vue'

// -- Estado del sidebar ----------------------------------------------------------

const BREAKPOINT = 1024 // lg
const SIDEBAR_W = 224 // w-56 = 224px
const SIDEBAR_COL = 64 // w-16  = 64px (colapsado)

const isMobile = ref(false)
const sidebarOpen = ref(true)

function checkMobile() {
    isMobile.value = window.innerWidth < BREAKPOINT
    // En móvil el sidebar empieza cerrado
    if (isMobile.value) sidebarOpen.value = false
    else sidebarOpen.value = true
}

function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value
}

onMounted(() => {
    checkMobile()
    window.addEventListener('resize', checkMobile)
})
onUnmounted(() => window.removeEventListener('resize', checkMobile))

// -- Medidas dinámicas ----------------------------------------------------------

const navbarLeft = computed(() => {
    if (isMobile.value) return '0px'
    return sidebarOpen.value ? `${SIDEBAR_W}px` : `${SIDEBAR_COL}px`
})

const mainMargin = computed(() => {
    if (isMobile.value) return '0px'
    return sidebarOpen.value ? `${SIDEBAR_W}px` : `${SIDEBAR_COL}px`
})

// -- Usuario actual ----------------------------------------------------------

const currentUser = ref({
    name: 'Martín Desarrollo',
    email: 'martin@credigital.com',
    company: 'IMPULSA CORP SAS / CREDITRANSITO',
    avatar: null,
})
</script>
