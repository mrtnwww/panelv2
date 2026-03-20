<template>
    <div
        class="hidden lg:flex lg:flex-1 bg-[#0A2540] flex-col justify-between px-14 py-14 relative overflow-hidden"
    >
        <!-- Círculos decorativos -->
        <div
            class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-emerald-500/10 pointer-events-none"
        />
        <div
            class="absolute -bottom-20 -left-20 w-64 h-64 rounded-full bg-emerald-500/6 pointer-events-none"
        />

        <!-- Logo -->
        <div class="flex items-center gap-3 relative z-10">
            <span
                class="text-emerald-400 font-semibold text-4xl tracking-tight 2xl:text-8xl"
            >
                Credi<span class="text-white">gital</span>
            </span>
        </div>

        <!-- Contenido central -->
        <div class="relative z-10 flex flex-col gap-8">
            <h2
                class="text-white text-3xl font-light leading-snug 2xl:text-5xl"
            >
                Administra tus clientes
                <span class="text-emerald-400 font-semibold"
                    >de forma fácil y rápida</span
                >
            </h2>

            <ul class="flex flex-col gap-4">
                <li
                    v-for="feature in features"
                    :key="feature"
                    class="flex items-center gap-3"
                >
                    <span
                        class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"
                    />
                    <span
                        class="text-white/60 text-sm leading-relaxed 2xl:text-xl"
                        >{{ feature }}</span
                    >
                </li>
            </ul>
        </div>

        <!-- Footer izquierdo -->
        <p class="text-white/25 text-xs relative z-10">
            &copy; Credigital | Todos los derechos reservados
        </p>
    </div>
</template>

<script setup>
const features = [
    'Crea créditos y registra abonos en segundos',
    'Configura recordatorios de pago automatizados',
    'Gestiona vencimientos y descarga informes detallados',
    'Tus clientes consultan sus cuotas desde la App',
];
</script>
