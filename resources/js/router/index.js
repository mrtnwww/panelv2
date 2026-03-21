import { createRouter, createWebHistory } from 'vue-router'

// Vistas
import LoginView from '@/views/auth/LoginView.vue'
import RegisterView from '@/views/auth/RegistroView.vue'

// Layouts
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'

const routes = [
    {
        path: '/',
        redirect: () => {
            return isAuthenticated() ? '/dashboard' : '/login'
        },
    },
    {
        path: '/login',
        name: 'login',
        component: LoginView,
    },
    {
        path: '/registro',
        name: 'registro',
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
                component: () =>
                    import('@/views/clientes/ClienteCrearView.vue'),
            },
            {
                path: 'clientes/validar',
                name: 'clientes.validar',
                component: () =>
                    import('@/views/clientes/ClientesValidarView.vue'),
            },
            {
                path: 'clientes/analisis',
                name: 'clientes.analisis',
                component: () =>
                    import('@/views/clientes/ClientesAnalisisView.vue'),
            },
            {
                path: 'clientes',
                name: 'clientes',
                component: () =>
                    import('@/views/clientes/ClientesListaView.vue'),
            },
            // -- CREDITOS --------------------------------------------------------------------
            {
                path: 'creditos/nuevo',
                name: 'creditos.nuevo',
                component: () =>
                    import('@/views/creditos/CreditosCrearView.vue'),
            },
            {
                path: 'creditos/estado',
                name: 'creditos.estado',
                component: () =>
                    import('@/views/creditos/CreditosEstadoView.vue'),
            },
            // -- ABONOS --------------------------------------------------------------------
            {
                path: 'abonos/nuevo',
                name: 'abonos.nuevo',
                component: () => import('@/views/abonos/AbonosCrearView.vue'),
            },
            // -- PRODUCTOS --------------------------------------------------------------------
            {
                path: 'productos/nuevo',
                name: 'productos.nuevo',
                component: () =>
                    import('@/views/productos/ProductosCrearView.vue'),
            },
            // -- COBRANZA --------------------------------------------------------------------
            {
                path: 'cobranza/creditos',
                name: 'cobranza.creditos',
                component: () =>
                    import('@/views/cobranza/CobranzaCreditosView.vue'),
            },
            {
                path: 'cobranza/tareas',
                name: 'cobranza.tareas',
                component: () =>
                    import('@/views/cobranza/CobranzaTareasView.vue'),
            },
            // -- CONTABILIDAD --------------------------------------------------------------------
            {
                path: 'contabilidad/recibo-cxc',
                name: 'cobranza.reciboCXC',
                component: () =>
                    import('@/views/contabilidad/ContabilidadCXCView.vue'),
            },
            // -- INFORMES --------------------------------------------------------------------
            {
                path: 'informes/creditos',
                name: 'informes.creditos',
                component: () =>
                    import('@/views/informes/CreditosListaView.vue'),
            },
            {
                path: 'informes/abonos',
                name: 'informes.abonos',
                component: () => import('@/views/informes/AbonosListaView.vue'),
            },
            {
                path: 'informes/cartera',
                name: 'informes.cartera',
                component: () =>
                    import('@/views/informes/CarteraListaView.vue'),
            },
            // -- CONFIGURACION --------------------------------------------------------------
            {
                path: 'configuracion/usuarios',
                name: 'configuracion.usuarios',
                component: () =>
                    import('@/views/configuracion/ConfiguracionUsuariosView.vue'),
            },
            {
                path: 'configuracion/plantillas-correo',
                name: 'configuracion.plantillasCorreo',
                component: () =>
                    import('@/views/configuracion/ConfiguracionPlantillasCorreoView.vue'),
            },
            {
                path: 'configuracion/sedes-aliados',
                name: 'configuracion.sedesAliados',
                component: () =>
                    import('@/views/configuracion/ConfiguracionSedesAliadosView.vue'),
            },
            // -- USUARIO --------------------------------------------------------------------
            {
                path: 'usuario/perfil',
                name: 'usuario.perfil',
                component: () =>
                    import('@/views/usuario/UsuarioPerfilView.vue'),
            },
            {
                path: 'usuario/cambiar-clave',
                name: 'usuario.cambiarClave',
                component: () =>
                    import('@/views/usuario/CambiarContraseñaView.vue'),
            },
        ],
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'notFound',
        component: () => import('@/views/NotFoundView.vue'),
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

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
