import { createRouter, createWebHistory } from "vue-router";

// Vistas
import LoginView from "@/views/auth/LoginView.vue";
import RegisterView from "@/views/auth/RegisterView.vue";
import DashboardView from "@/views/dashboard/DashboardView.vue";

// Componentes
import AuthenticatedLayout from "@/layouts/AuthenticatedLayout.vue";

const routes = [
    {
        path: '/',
        redirect: () => {
            return isAuthenticated() ? '/dashboard' : '/login'
        },
    },
    {
        path: "/login",
        name: "login",
        component: LoginView,
    },
    {
        path: "/registro",
        name: "registro",
        component: RegisterView,
    },
    // Rutas autenticadas
    {
        path: "/dashboard",
        component: AuthenticatedLayout,
        children: [
            { path: "", component: DashboardView },
            // TODO
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// ── Helper: comprueba si hay sesión activa ──────────────────────────────────
function isAuthenticated() {
    return !!localStorage.getItem('auth_token')
}

// ── Guard global ────────────────────────────────────────────────────────────
router.beforeEach((to, from) => {
    const auth = isAuthenticated()

    // Ruta protegida y no autenticado → login
    if (to.meta.requiresAuth && !auth) {
        return { name: 'login' }
    }

    // Ruta solo para guests (login/registro) y ya autenticado → dashboard
    if (to.meta.guestOnly && auth) {
        return { name: 'dashboard' }
    }
})

export default router