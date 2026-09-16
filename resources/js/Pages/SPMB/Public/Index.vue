<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { 
    AcademicCapIcon, 
    SparklesIcon, 
    ArrowRightIcon, 
    PhoneIcon, 
    BuildingLibraryIcon,
    CalendarDaysIcon,
    CheckCircleIcon,
    ArrowTopRightOnSquareIcon,
    UserCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    units: Array,
    sdUnit: Object,
    sdSetting: Object,
});
</script>

<template>
    <Head title="SPMB & PPDB Online - Yayasan Namira" />

    <div class="min-h-screen bg-slate-950 font-sans text-white flex flex-col justify-between relative overflow-hidden selection:bg-[#fbbf24] selection:text-[#064e3b]">
        <!-- Decorative Background Glows -->
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-[#064e3b] rounded-full blur-3xl opacity-50"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-[#fbbf24] rounded-full blur-3xl opacity-20"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[700px] bg-emerald-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Top Header Navigation -->
        <header class="relative z-10 max-w-7xl mx-auto w-full px-6 py-6 flex items-center justify-between">
            <Link href="/" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-emerald-800/60 border border-emerald-500/30 flex items-center justify-center group-hover:bg-emerald-700 transition">
                    <AcademicCapIcon class="w-6 h-6 text-[#fbbf24]" />
                </div>
                <div>
                    <span class="text-sm font-bold tracking-wide text-white block leading-tight">YAYASAN NAMIRA</span>
                    <span class="text-[11px] text-emerald-300 block">Sistem Penerimaan Siswa Baru</span>
                </div>
            </Link>
            
            <div class="flex items-center gap-3">
                <Link 
                    :href="route('login')" 
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-800/80 hover:bg-slate-700 border border-slate-700 text-slate-200 text-xs sm:text-sm font-semibold transition"
                >
                    <UserCircleIcon class="w-4 h-4 text-emerald-400" />
                    <span>Login Calon Siswa / Ortu</span>
                </Link>
            </div>
        </header>

        <!-- Main Hero & Unit Selection -->
        <main class="relative z-10 max-w-6xl mx-auto px-6 py-8 flex-1 flex flex-col justify-center items-center text-center">
            <!-- Badge Gelombang -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-semibold tracking-wider uppercase mb-6">
                <SparklesIcon class="w-4 h-4 text-amber-400" />
                <span>Penerimaan Siswa Baru Tahun Ajaran 2026/2027</span>
            </div>

            <!-- Main Heading -->
            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-tight mb-4">
                Pilih Unit Pendidikan <br class="hidden sm:inline"/>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#fbbf24] via-amber-200 to-yellow-400">
                    Keluarga Besar Namira
                </span>
            </h1>

            <p class="text-sm sm:text-base text-slate-300 max-w-2xl mx-auto leading-relaxed mb-10">
                Wujudkan masa depan ananda yang berakhlakul karimah, unggul dalam keilmuan, dan berjiwa Al-Qur'an. Silakan pilih jenjang pendidikan yang dituju di bawah ini:
            </p>

            <!-- Grid Pilihan Unit -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full text-left mb-10">
                
                <!-- 1. SD NAMIRA (INTERNAL FORMS - FOCUS) -->
                <div class="relative bg-gradient-to-b from-slate-900 to-slate-900/90 border-2 border-emerald-500/60 rounded-3xl p-6 sm:p-7 backdrop-blur-md shadow-2xl flex flex-col justify-between group hover:border-[#fbbf24] transition duration-300">
                    <div class="absolute -top-3 right-6 px-3 py-1 bg-emerald-600 text-white text-[11px] font-bold rounded-full uppercase tracking-wider shadow">
                        Jalur Inden Dibuka
                    </div>

                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-900/50 border border-emerald-500/40 flex items-center justify-center text-[#fbbf24] mb-5">
                            <BuildingLibraryIcon class="w-7 h-7" />
                        </div>
                        <h2 class="text-xl font-bold text-white group-hover:text-[#fbbf24] transition">
                            SD Namira (Elementary)
                        </h2>
                        <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                            Sekolah Dasar Islam Terpadu berstandar keunggulan akademik, tahfidz Al-Qur'an, dan pembiasaan adab islami.
                        </p>

                        <div class="mt-4 space-y-2 border-t border-slate-800 pt-4">
                            <div class="flex items-center gap-2 text-xs text-slate-300">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-400" />
                                <span>Observasi Dasar & Psikotes Offline</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-300">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-400" />
                                <span>Biaya Pendaftaran QRIS Bank Jatim</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-300">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-400" />
                                <span>Daftar Ulang Bertahap (Termin 60%)</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-2">
                        <Link 
                            :href="route('spmb.register.sd')"
                            class="w-full inline-flex items-center justify-center gap-2 bg-[#064e3b] hover:bg-emerald-700 text-white font-bold py-3 px-5 rounded-2xl transition shadow-lg shadow-emerald-950/60 border border-emerald-400/30 text-sm"
                        >
                            <span>Daftar SD Jalur Inden</span>
                            <ArrowRightIcon class="w-4 h-4 text-[#fbbf24] group-hover:translate-x-1 transition-transform" />
                        </Link>
                    </div>
                </div>

                <!-- 2. TK & KB NAMIRA (INTERNAL FORMS / INFO) -->
                <div class="relative bg-slate-900/70 border border-slate-800 hover:border-emerald-500/40 rounded-3xl p-6 sm:p-7 backdrop-blur-md shadow-xl flex flex-col justify-between group transition duration-300">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center text-amber-400 mb-5">
                            <SparklesIcon class="w-7 h-7" />
                        </div>
                        <h2 class="text-xl font-bold text-white group-hover:text-amber-300 transition">
                            TK & KB Namira
                        </h2>
                        <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                            Pendidikan anak usia dini berbasis pembiasaan karakter karimah, motorik kreatif, dan fondasi Al-Qur'an.
                        </p>

                        <div class="mt-4 space-y-2 border-t border-slate-800 pt-4">
                            <div class="flex items-center gap-2 text-xs text-slate-300">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-400" />
                                <span>Tersedia di Kraksaan & Dringu</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-300">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-400" />
                                <span>Lingkungan Bermain Aman & Ramah Anak</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-2">
                        <a 
                            href="https://wa.me/6282332922521?text=Halo%20Admin%20TK/KB%20Namira,%20saya%20ingin%20mendaftar%20SPMB%20TK%20dan%20Kelompok%20Bermain" 
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold py-3 px-5 rounded-2xl transition border border-slate-700 text-sm"
                        >
                            <PhoneIcon class="w-4 h-4 text-emerald-400" />
                            <span>Informasi & Pendaftaran TK/KB</span>
                        </a>
                    </div>
                </div>

                <!-- 3. SMP NAMIRA (EXTERNAL DIRECT LINK FORM) -->
                <div class="relative bg-slate-900/70 border border-slate-800 hover:border-emerald-500/40 rounded-3xl p-6 sm:p-7 backdrop-blur-md shadow-xl flex flex-col justify-between group transition duration-300">
                    <div class="absolute -top-3 right-6 px-3 py-1 bg-amber-600/90 text-white text-[11px] font-bold rounded-full uppercase tracking-wider shadow">
                        Formulir Resmi SMP
                    </div>

                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center text-blue-400 mb-5">
                            <AcademicCapIcon class="w-7 h-7" />
                        </div>
                        <h2 class="text-xl font-bold text-white group-hover:text-blue-300 transition">
                            SMP Namira (Kota)
                        </h2>
                        <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                            Sekolah Menengah Pertama Islam Terpadu berwawasan global, bilingual, riset, dan program tahfidz intensif.
                        </p>

                        <div class="mt-4 space-y-2 border-t border-slate-800 pt-4">
                            <div class="flex items-center gap-2 text-xs text-slate-300">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-400" />
                                <span>Pendaftaran Mandiri Unit SMP</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-300">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-400" />
                                <span>Terhubung Langsung ke Tim SPMB SMP</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-2">
                        <a 
                            href="https://wa.me/6282332922521?text=Halo%20Panitia%20SPMB%20SMP%20Namira,%20saya%20ingin%20meminta%20link%20formulir%20pendaftaran%20SMP" 
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-2 bg-blue-900/40 hover:bg-blue-800/60 text-blue-200 font-semibold py-3 px-5 rounded-2xl transition border border-blue-500/30 text-sm"
                        >
                            <span>Buka Form SPMB SMP</span>
                            <ArrowTopRightOnSquareIcon class="w-4 h-4 text-blue-300" />
                        </a>
                    </div>
                </div>

            </div>

            <!-- Bottom Assistance Banner -->
            <div class="w-full max-w-2xl bg-slate-900/50 border border-slate-800 rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
                <div class="flex items-center gap-3">
                    <PhoneIcon class="w-5 h-5 text-emerald-400 shrink-0" />
                    <span>Butuh bantuan atau informasi kuota pendaftaran? Hubungi Panitia SPMB Yayasan Namira.</span>
                </div>
                <a 
                    href="https://wa.me/6282332922521?text=Halo%20Panitia%20SPMB%20Namira,%20saya%20ingin%20bertanya%20tentang%20pendaftaran" 
                    target="_blank"
                    class="text-emerald-300 hover:text-emerald-200 font-semibold whitespace-nowrap"
                >
                    Chat WhatsApp &rarr;
                </a>
            </div>
        </main>

        <!-- Footer -->
        <footer class="relative z-10 py-6 text-center text-xs text-slate-500 border-t border-slate-800/60">
            <p>&copy; {{ new Date().getFullYear() }} Yayasan Namira Probolinggo. All Rights Reserved.</p>
        </footer>
    </div>
</template>
