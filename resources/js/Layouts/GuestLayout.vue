<template>
    <div class="min-h-screen w-full flex flex-col items-center justify-center bg-slate-950 relative font-body selection:bg-teal-500 selection:text-white p-4 py-8">
        <!-- Vibrant Background Image (Preserved from original design) -->
        <div class="absolute inset-0 z-0 overflow-hidden">
            <img src="/images/login_branding.webp" alt="Background" class="w-full h-full object-cover opacity-60 scale-105" loading="lazy" />
            <div class="absolute inset-0 bg-gradient-to-br from-slate-950/70 via-slate-900/50 to-teal-950/70"></div>
        </div>

        <!-- Top-Right Back to Home Button -->
        <div class="absolute top-5 right-5 z-20">
            <Link href="/" class="flex items-center gap-1.5 px-4 py-2 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 text-white rounded-full text-xs font-bold transition-all shadow-md active:scale-95">
                <ArrowLeftIcon class="w-4 h-4" />
                <span>Beranda</span>
            </Link>
        </div>

        <!-- Centered Card Container -->
        <div class="relative z-10 w-full max-w-md my-auto animate-fade-in-up">
            <slot />
        </div>
    </div>
</template>

<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
</script>

<style scoped>
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-fade-in-up {
    animation: fade-in-up 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
