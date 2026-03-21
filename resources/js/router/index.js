import { createRouter, createWebHistory } from "vue-router";

// Vistas
import LoginView from "@/views/auth/LoginView.vue";
import RegisterView from "@/views/auth/RegistroView.vue";

// Layouts
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
    // -- Rutas autenticadas ----------------------------------------------------------------
    {
        path: '/',
        component: AuthenticatedLayout,
        children: [
            {
                path: 'dashboard',
                name: 'dashboard',
                component: () => import('@/views/dashboard/DashboardView.vue'),
            },
            // -- CLIENTES --------------------------------------------------------------------
            {
                path: 'clientes/nuevo',
                name: 'clientes.nuevo',
                component: () => import('@/views/clientes/ClienteCrearView.vue'),
            },
            {
                path: 'clientes/validar',
                name: 'clientes.validar',
                component: () => import('@/views/clientes/ClientesValidarView.vue'),
            },
            {
                path: 'clientes/analisis',
                name: 'clientes.analisis',
                component: () => import('@/views/clientes/ClientesAnalisisView.vue'),
            },
            {
                path: 'clientes',
                name: 'clientes',
                component: () => import('@/views/clientes/ClientesListaView.vue'),
            },
            // -- INFORMES --------------------------------------------------------------------
            {
                path: 'informes/creditos',
                name: 'informes.creditos',
                component: () => import('@/views/informes/CreditosListaView.vue'),
            },
            {
                path: 'informes/abonos',
                name: 'informes.abonos',
                component: () => import('@/views/informes/AbonosListaView.vue'),
            },
            {
                path: 'informes/cartera',
                name: 'informes.cartera',
                component: () => import('@/views/informes/CarteraListaView.vue'),
            },
            // -- USUARIO --------------------------------------------------------------------
            {
                path: 'usuario/perfil',
                name: 'usuario.perfil',
                component: () => import('@/views/usuario/UsuarioPerfilView.vue'),
            },
            {
                path: 'usuario/cambiar-clave',
                name: 'usuario.cambiarClave',
                component: () => import('@/views/usuario/CambiarContraseñaView.vue'),
            },
        ],
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'notFound',
        component: () => import('@/views/NotFoundView.vue'),
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// -- Helper: comprueba si hay sesión activa ----------------------------------
function isAuthenticated() {
    return !!localStorage.getItem('auth_token')
}

// -- Guard global ------------------------------------------------------------
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