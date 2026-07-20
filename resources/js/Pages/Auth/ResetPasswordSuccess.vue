<template>
    <GuestLayout>
        <Head title="Password Berhasil Direset" />

        <div class="text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-teal-500/20 mb-6 animate-fade-in-up">
                <CheckIcon class="w-8 h-8 text-teal-400" />
            </div>

            <h2 class="text-2xl font-black font-heading text-white tracking-tight animate-fade-in-up">
                Password Berhasil Direset
            </h2>
            <p class="text-slate-300 text-sm font-medium mt-3 leading-relaxed animate-fade-in-up" style="animation-delay: 0.1s;">
                Password akun Anda telah berhasil diperbarui.
            </p>

            <div class="mt-8 p-5 bg-white/5 rounded-xl border border-white/10 animate-fade-in-up" style="animation-delay: 0.15s;">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-slate-300 text-xs leading-relaxed">
                        Silakan kembali ke aplikasi <strong class="text-white">SuperApp Namira</strong> dan login menggunakan password baru Anda.
                    </p>
                </div>
            </div>

            <div class="mt-8 animate-fade-in-up" style="animation-delay: 0.2s;">
                <a
                    href="superappnamira://login"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-teal-500 hover:bg-teal-400 active:bg-teal-600 text-white font-bold text-sm rounded-xl transition-all duration-200 shadow-lg shadow-teal-500/25"
                >
                    <ArrowLeftIcon class="w-4 h-4" />
                    <span>Kembali ke Aplikasi</span>
                </a>
            </div>

            <p class="text-slate-500 text-xs mt-6 animate-fade-in-up" style="animation-delay: 0.25s;">
                Atau login melalui browser
                <Link :href="route('login')" class="text-teal-400 hover:text-teal-300 underline font-medium">di sini</Link>
            </p>
        </div>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CheckIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline';
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
