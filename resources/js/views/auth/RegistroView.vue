<template>
    <div class="min-h-screen flex font-sans h-screen">
        <!-- ── Panel izquierdo ── -->
        <AuthPanelLeft />

        <!-- ── Panel derecho ── -->
        <div
            class="w-full lg:w-160 bg-white flex flex-col px-10 py-10 overflow-y-auto h-screen"
        >
            <!-- Logo móvil -->
            <div class="flex lg:hidden items-center gap-3 mb-8">
                <span
                    class="text-emerald-500 font-semibold text-2xl tracking-tight"
                >
                    Credi<span class="text-[#0A2540]">gital</span>
                </span>
            </div>

            <!-- Encabezado -->
            <div class="mb-7">
                <h1 class="text-[#0A2540] text-2xl font-semibold mb-1">
                    Crear cuenta
                </h1>
                <p class="text-gray-400 text-sm">
                    ¿Ya tienes cuenta?
                    <router-link
                        to="/login"
                        class="text-emerald-600 font-medium hover:text-emerald-700 transition-colors"
                    >
                        Iniciar sesión
                    </router-link>
                </p>
            </div>

            <!-- Formulario de registro -->
            <RegistroForm />

            <p class="text-center text-xs text-gray-300 mt-6 mb-2">
                ¿Ya tienes cuenta?
                <router-link
                    to="/login"
                    class="text-emerald-600 hover:underline font-medium"
                >
                    Iniciar sesión
                </router-link>
            </p>
        </div>
    </div>
</template>

<script setup>
import AuthPanelLeft from '@/components/AuthPanelLeft.vue'
import RegistroForm from '@/components/RegistroForm.vue'
</script>
