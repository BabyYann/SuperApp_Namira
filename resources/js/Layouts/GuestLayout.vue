<template>
    <div class="min-h-screen w-full flex items-center justify-center bg-slate-950 relative font-body selection:bg-teal-500 selection:text-white p-4">
        <!-- Vibrant Background Image -->
        <div class="absolute inset-0 z-0 overflow-hidden">
            <img src="/images/login_branding.webp" alt="Background" class="w-full h-full object-cover opacity-75" loading="lazy" />
            <div class="absolute inset-0 bg-gradient-to-br from-slate-950/50 via-slate-900/30 to-teal-950/40"></div>
        </div>

        <!-- Top-Left Branding Logo -->
        <div class="absolute top-6 left-6 z-20 hidden md:flex items-center gap-3">
            <ApplicationLogo class="w-9 h-9 object-contain" />
            <div>
                <h1 class="text-white font-bold text-sm tracking-tight leading-none">
                    Namira School Foundation
                </h1>
                <span class="text-[10px] text-teal-300 font-semibold tracking-wider uppercase">Portal Akademik</span>
            </div>
        </div>

        <!-- Top-Right Back to Home Button -->
        <div class="absolute top-6 right-6 z-20">
            <Link href="/" class="flex items-center gap-1.5 px-4 py-2 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 text-white rounded-full text-xs font-bold transition-all shadow-md active:scale-95">
                <ArrowLeftIcon class="w-4 h-4" />
                <span>Kembali ke Beranda</span>
            </Link>
        </div>

        <!-- Compact Glassmorphic Card Container (Light Transparent Teal) -->
        <div class="relative z-10 w-full max-w-3xl bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-2xl overflow-hidden animate-fade-in-up">
            <div class="flex flex-col md:flex-row">
                
                <!-- Left Side: Light Translucent Teal -->
                <div class="w-full md:w-5/12 p-8 sm:p-10 bg-teal-950/40 backdrop-blur-sm border-b md:border-b-0 md:border-r border-white/10 flex flex-col justify-between">
                    <!-- Mobile Logo -->
                    <div class="flex md:hidden items-center gap-3 mb-6">
                        <ApplicationLogo class="w-8 h-8 object-contain" />
                        <div>
                            <h2 class="text-white font-bold text-sm tracking-tight leading-none">Namira School</h2>
                            <span class="text-[9px] text-teal-300 font-semibold tracking-wider uppercase">Portal Akademik</span>
                        </div>
                    </div>

                    <div class="my-auto py-4">
                        <span class="text-[10px] font-bold text-teal-300 tracking-widest uppercase">Yayasan Namira</span>
                        <h3 class="text-white font-heading font-extrabold text-2xl leading-tight mt-2 mb-4">
                            Namira School
                        </h3>
                        <p class="text-slate-200 text-xs leading-relaxed">
                            Mewujudkan generasi cerdas, berakhlak mulia, dan berwawasan global. Portal terpadu untuk menyelaraskan manajemen akademik, keuangan, kepegawaian, sarpras, dan konseling dalam satu ekosistem digital.
                        </p>
                    </div>

                    <div class="mt-6 md:mt-0 text-[10px] text-slate-300">
                        <span>© {{ new Date().getFullYear() }} Yayasan Namira</span>
                    </div>
                </div>

                <!-- Right Side: Light Translucent Teal Login Form -->
                <div class="w-full md:w-7/12 p-8 sm:p-10 bg-teal-900/20 backdrop-blur-sm flex flex-col justify-center">
                    <slot />
                </div>

            </div>
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
