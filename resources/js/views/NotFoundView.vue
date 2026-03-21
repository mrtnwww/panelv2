<template>
    <div
        class="min-h-screen bg-[#0A2540] flex items-center justify-center px-6 overflow-hidden relative"
    >
        <!-- Círculos decorativos -->
        <div
            class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-emerald-500/10 pointer-events-none"
        />
        <div
            class="absolute -bottom-20 -left-20 w-64 h-64 rounded-full bg-emerald-500/6 pointer-events-none"
        />

        <!-- Contenido -->
        <div
            class="relative z-10 flex flex-col items-center text-center animate-fade-up"
        >
            <!-- Logo -->
            <span
                class="text-emerald-400 font-semibold text-2xl tracking-tight mb-12 xl:text-4xl"
            >
                Credi<span class="text-white">gital</span>
            </span>

            <!-- Número -->
            <h1
                class="text-[7rem] leading-none font-light text-white tracking-tighter mb-4"
            >
                404
            </h1>

            <!-- Divisor -->
            <div class="w-10 h-0.5 bg-emerald-500/40 rounded-full mb-6" />

            <!-- Texto -->
            <p class="text-white text-2xl font-medium mb-2 xl:text-4xl">
                Página no encontrada
            </p>
            <p
                class="text-white text-sm leading-relaxed max-w-xs mb-10 xl:text-base"
            >
                La ruta que buscas no existe o fue movida. Verifica la URL o
                regresa al inicio.
            </p>

            <!-- Botón -->
            <router-link
                to="/"
                class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white text-sm font-semibold px-6 py-3 rounded-lg transition-all duration-150 xl:text-base"
            >
                <i class="fa-solid fa-angle-left"></i>
                Volver al inicio
            </router-link>
        </div>
    </div>
</template>
