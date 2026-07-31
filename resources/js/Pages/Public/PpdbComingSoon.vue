<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { 
    AcademicCapIcon, 
    SparklesIcon, 
    ArrowLeftIcon, 
    PhoneIcon, 
    BuildingLibraryIcon,
    CalendarDaysIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';

const units = [
    { name: 'Day Care Namira', desc: 'Penitipan anak usia dini dengan pengasuhan islami & penuh kasih sayang' },
    { name: 'KB (Kelompok Bermain) Namira', desc: 'Pengembangan motorik, karakter, dan nilai-nilai keislaman' },
    { name: 'TK Namira (Kraksaan & Dringu)', desc: 'Pendidikan anak usia dini berbasis pembentukan akhlakul karimah' },
    { name: 'SD Namira Elementary School', desc: 'Sekolah Dasar Islam Terpadu berstandar keunggulan akademik & Al-Qur\'an' },
    { name: 'SMP Namira Kota Probolinggo', desc: 'Sekolah Menengah Pertama Islam Terpadu berwawasan global & tahfidz' },
];
</script>

<template>
    <Head title="PPDB / SPMB Online - Yayasan Namira" />

    <div class="min-h-screen bg-slate-900 font-sans text-white flex flex-col justify-between relative overflow-hidden selection:bg-[#fbbf24] selection:text-[#064e3b]">
        <!-- Decorative Background Gradients -->
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-[#064e3b] rounded-full blur-3xl opacity-50"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-[#fbbf24] rounded-full blur-3xl opacity-20"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-emerald-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Top Header Navigation -->
        <header class="relative z-10 max-w-7xl mx-auto w-full px-6 py-6 flex items-center justify-between">
            <Link href="/" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-emerald-800/60 border border-emerald-500/30 flex items-center justify-center group-hover:bg-emerald-700 transition">
                    <ArrowLeftIcon class="w-5 h-5 text-emerald-300 group-hover:-translate-x-0.5 transition-transform" />
                </div>
                <span class="text-sm font-medium text-emerald-200 group-hover:text-white transition">Kembali ke Beranda</span>
            </Link>
            
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-semibold tracking-wider uppercase">
                <SparklesIcon class="w-4 h-4 animate-spin" />
                <span>SPMB 2026/2027</span>
            </div>
        </header>

        <!-- Main Content Section -->
        <main class="relative z-10 max-w-4xl mx-auto px-6 py-12 text-center flex-1 flex flex-col justify-center items-center">
            <!-- Icon Badge -->
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-gradient-to-br from-[#064e3b] to-emerald-800 border border-emerald-400/30 shadow-2xl shadow-emerald-900/50 mb-8">
                <AcademicCapIcon class="w-10 h-10 text-[#fbbf24]" />
            </div>

            <!-- Title & Subtitle -->
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-white leading-tight mb-4">
                Penerimaan Peserta Didik Baru <br class="hidden sm:inline"/>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#fbbf24] via-amber-200 to-yellow-400">
                    Yayasan Namira
                </span>
            </h1>

            <p class="text-base sm:text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed mb-8">
                Sistem Pendaftaran Murid Baru (SPMB Online) sedang dalam persiapan akhir. Kami siap menyambut putra-putri Anda dalam lingkungan belajar islami, unggul, dan berkarakter.
            </p>

            <!-- Status Banner Badge -->
            <div class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-slate-800/80 border border-slate-700/80 backdrop-blur-md mb-12 shadow-xl">
                <CalendarDaysIcon class="w-5 h-5 text-amber-400" />
                <span class="text-sm font-medium text-slate-200">Segera Dibuka untuk Tahun Ajaran Baru</span>
            </div>

            <!-- Open Units Grid -->
            <div class="w-full text-left bg-slate-800/40 border border-slate-700/60 rounded-3xl p-6 sm:p-8 backdrop-blur-md shadow-2xl mb-10">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-700/60">
                    <BuildingLibraryIcon class="w-6 h-6 text-[#fbbf24]" />
                    <h2 class="text-lg font-bold text-white uppercase tracking-wider">Unit Pendidikan Yang Dibuka</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div 
                        v-for="(u, idx) in units" 
                        :key="idx" 
                        class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-emerald-500/40 transition group"
                    >
                        <div class="flex items-start gap-3">
                            <CheckCircleIcon class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" />
                            <div>
                                <h3 class="font-bold text-white group-hover:text-[#fbbf24] transition text-sm sm:text-base">{{ u.name }}</h3>
                                <p class="text-xs text-slate-400 mt-1 leading-relaxed">{{ u.desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center gap-4">
                <a 
                    href="https://wa.me/6282332922521?text=Halo%20Admin%20Yayasan%20Namira,%20saya%20ingin%20bertanya%20informasi%20PPDB/SPMB" 
                    target="_blank"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-3 bg-[#064e3b] hover:bg-emerald-800 text-white font-bold px-8 py-4 rounded-2xl transition shadow-lg shadow-emerald-950/60 border border-emerald-400/20 text-sm"
                >
                    <PhoneIcon class="w-5 h-5 text-[#fbbf24]" />
                    <span>Hubungi Panitia PPDB (WhatsApp)</span>
                </a>

                <Link 
                    href="/" 
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold px-8 py-4 rounded-2xl transition border border-slate-700 text-sm"
                >
                    <span>Kembali ke Beranda</span>
                </Link>
            </div>
        </main>

        <!-- Footer -->
        <footer class="relative z-10 py-6 text-center text-xs text-slate-500 border-t border-slate-800/60">
            <p>&copy; {{ new Date().getFullYear() }} Yayasan Namira. All Rights Reserved.</p>
        </footer>
    </div>
</template>
