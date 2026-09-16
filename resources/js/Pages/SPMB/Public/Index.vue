<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { 
    AcademicCapIcon, 
    ArrowRightIcon, 
    PhoneIcon, 
    BuildingLibraryIcon,
    CalendarDaysIcon,
    CheckCircleIcon,
    ArrowTopRightOnSquareIcon,
    UserCircleIcon,
    DocumentCheckIcon,
    CreditCardIcon,
    SunIcon,
    ChatBubbleLeftRightIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    units: Array,
    sdUnit: Object,
    sdSetting: Object,
});

const steps = [
    { num: '01', title: 'Isi Formulir Online', desc: 'Lengkapi biodata calon siswa, data orang tua, dan buat kata sandi akun pendaftar.' },
    { num: '02', title: 'Unggah Berkas & QRIS', desc: 'Upload pas foto latar merah, KK, Akta Lahir, KTP ortu, dan bukti pembayaran QRIS.' },
    { num: '03', title: 'Observasi & Psikotes', desc: 'Hadir ke sekolah sesuai jadwal tatap muka untuk observasi dasar dan psikotes anak.' },
    { num: '04', title: 'Pengumuman Hasil', desc: 'Cek status kelulusan di portal dan unduh Surat Keterangan Penerimaan resmi.' },
    { num: '05', title: 'Daftar Ulang (VA)', desc: 'Lakukan pembayaran biaya masuk bertahap (Termin 60%) melalui VA Bank Jatim.' },
];
</script>

<template>
    <Head title="Penerimaan Siswa Baru (SPMB) - Yayasan Namira" />

    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 flex flex-col justify-between selection:bg-emerald-100 selection:text-emerald-900">
        
        <!-- Top Institutional Header -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-3 group">
                    <img 
                        src="/images/landing/logo-yayasan.webp" 
                        alt="Logo Yayasan Namira" 
                        class="w-10 h-10 object-contain drop-shadow-xs"
                        onerror="this.style.display='none'"
                    />
                    <div class="border-l border-slate-200 pl-3">
                        <span class="text-xs font-extrabold tracking-wider uppercase text-emerald-900 block leading-tight">YAYASAN NAMIRA</span>
                        <span class="text-[11px] font-medium text-slate-500 block">Sistem Penerimaan Murid Baru (SPMB)</span>
                    </div>
                </Link>

                <div class="flex items-center gap-3">
                    <Link 
                        :href="route('login')"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition border border-slate-200"
                    >
                        <UserCircleIcon class="w-4 h-4 text-emerald-700" />
                        <span>Login Calon Siswa / Ortu</span>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="bg-gradient-to-b from-emerald-900 to-[#064e3b] text-white py-14 sm:py-20 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
            <div class="max-w-4xl mx-auto text-center relative z-10">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-emerald-800/80 border border-emerald-400/30 text-emerald-200 text-xs font-semibold uppercase tracking-wider mb-5">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>Tahun Ajaran 2026 / 2027</span>
                </div>

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight mb-4 text-white">
                    Penerimaan Peserta Didik Baru <br class="hidden sm:inline"/>
                    <span class="text-amber-300">Sekolah Islam Namira</span>
                </h1>

                <p class="text-sm sm:text-base text-emerald-100/90 max-w-2xl mx-auto leading-relaxed mb-8">
                    Menumbuhkan potensi fitrah, adab islami, dan keunggulan akademik putra-putri Anda dalam lingkungan belajar yang aman, nyaman, dan berkarakter Qur'ani.
                </p>

                <div class="flex flex-wrap items-center justify-center gap-3 text-xs text-emerald-100">
                    <div class="flex items-center gap-1.5 bg-emerald-800/60 px-3 py-1.5 rounded-lg border border-emerald-700/50">
                        <CheckCircleIcon class="w-4 h-4 text-amber-300" />
                        <span>Akreditasi Unggul</span>
                    </div>
                    <div class="flex items-center gap-1.5 bg-emerald-800/60 px-3 py-1.5 rounded-lg border border-emerald-700/50">
                        <CheckCircleIcon class="w-4 h-4 text-amber-300" />
                        <span>Tahfidz & Pembiasaan Sunnah</span>
                    </div>
                    <div class="flex items-center gap-1.5 bg-emerald-800/60 px-3 py-1.5 rounded-lg border border-emerald-700/50">
                        <CheckCircleIcon class="w-4 h-4 text-amber-300" />
                        <span>Pendidikan Berbasis Fitrah</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Unit Selection Section -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8 relative z-20 pb-16 flex-1">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- 1. SD NAMIRA (PRIMARY FEATURED UNIT) -->
                <div class="bg-white rounded-2xl border-2 border-emerald-600/80 shadow-lg p-6 sm:p-7 flex flex-col justify-between hover:shadow-xl transition relative">
                    <div class="absolute -top-3 right-6 px-3 py-0.5 bg-emerald-700 text-white text-[11px] font-bold rounded-full uppercase tracking-wider shadow-sm">
                        Jalur Inden Dibuka
                    </div>

                    <div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-800 mb-4">
                            <BuildingLibraryIcon class="w-6 h-6" />
                        </div>

                        <span class="text-xs font-bold text-emerald-700 uppercase tracking-wide">Jenjang Dasar</span>
                        <h2 class="text-xl font-extrabold text-slate-900 mt-1">SD Namira</h2>
                        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                            Sekolah Dasar Islam berstandar keunggulan akademik, tahfidz Al-Qur'an, dan pembentukan adab islami mandiri.
                        </p>

                        <div class="mt-5 space-y-2.5 pt-4 border-t border-slate-100 text-xs text-slate-600">
                            <div class="flex items-start gap-2">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                                <span>Kuota Jalur Inden: <strong>60 Kursi</strong></span>
                            </div>
                            <div class="flex items-start gap-2">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                                <span>Observasi Dasar & Psikotes Tatap Muka</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                                <span>Daftar Ulang Fleksibel (Termin 60% VA Bank Jatim)</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-3">
                        <Link 
                            :href="route('spmb.register.sd')"
                            class="w-full inline-flex items-center justify-center gap-2 bg-[#064e3b] hover:bg-emerald-800 text-white font-bold py-3 px-4 rounded-xl text-xs transition shadow-sm"
                        >
                            <span>Daftar SD Jalur Inden</span>
                            <ArrowRightIcon class="w-4 h-4 text-amber-300" />
                        </Link>
                    </div>
                </div>

                <!-- 2. TK & KB NAMIRA -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-7 flex flex-col justify-between hover:shadow-md hover:border-slate-300 transition">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-700 mb-4">
                            <SunIcon class="w-6 h-6" />
                        </div>

                        <span class="text-xs font-bold text-amber-700 uppercase tracking-wide">Pendidikan Usia Dini</span>
                        <h2 class="text-xl font-extrabold text-slate-900 mt-1">TK & KB Namira</h2>
                        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                            Pendidikan anak usia dini berbasis pembiasaan karakter karimah, stimulasi motorik kreatif, dan fondasi awal Al-Qur'an.
                        </p>

                        <div class="mt-5 space-y-2.5 pt-4 border-t border-slate-100 text-xs text-slate-600">
                            <div class="flex items-start gap-2">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                                <span>Tersedia Unit Kraksaan & Dringu</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                                <span>Prioritas Khusus Lanjut ke SD Namira</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                                <span>Fasilitas Bermain Aman & Edukatif</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-3">
                        <a 
                            href="https://wa.me/6282332922521?text=Assalamu'alaikum%20Admin%20TK/KB%20Namira,%20saya%20ingin%20berkonsultasi%20pendaftaran%20anak"
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold py-3 px-4 rounded-xl text-xs transition border border-slate-200"
                        >
                            <PhoneIcon class="w-4 h-4 text-emerald-700" />
                            <span>Konsultasi & Pendaftaran TK/KB</span>
                        </a>
                    </div>
                </div>

                <!-- 3. SMP NAMIRA (DIRECT LINK EKSTERNAL) -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-7 flex flex-col justify-between hover:shadow-md hover:border-slate-300 transition">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-700 mb-4">
                            <AcademicCapIcon class="w-6 h-6" />
                        </div>

                        <span class="text-xs font-bold text-blue-700 uppercase tracking-wide">Sekolah Menengah</span>
                        <h2 class="text-xl font-extrabold text-slate-900 mt-1">SMP Namira</h2>
                        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                            Sekolah Menengah Pertama Islam berwawasan global, penguasaan bilingual, budaya riset, dan program tahfidz intensif.
                        </p>

                        <div class="mt-5 space-y-2.5 pt-4 border-t border-slate-100 text-xs text-slate-600">
                            <div class="flex items-start gap-2">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                                <span>Formulir Pendaftaran Resmi Unit SMP</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                                <span>Langsung Ditangani Tim SPMB SMP</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                                <span>Asrama & Full Day School</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-3">
                        <a 
                            href="https://wa.me/6282332922521?text=Assalamu'alaikum%20Panitia%20SPMB%20SMP%20Namira,%20mohon%20kirimkan%20link%20formulir%20pendaftaran%20SMP"
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-2 bg-blue-50 hover:bg-blue-100 text-blue-900 font-bold py-3 px-4 rounded-xl text-xs transition border border-blue-200"
                        >
                            <span>Buka Formulir Resmi SMP</span>
                            <ArrowTopRightOnSquareIcon class="w-4 h-4 text-blue-700" />
                        </a>
                    </div>
                </div>

            </div>

            <!-- Workflow 5 Langkah -->
            <div class="mt-16 bg-white border border-slate-200 rounded-2xl p-6 sm:p-10 shadow-xs">
                <div class="text-center max-w-xl mx-auto mb-10">
                    <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider block">Alur Pendaftaran</span>
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">5 Tahapan Mudah Mendaftar di SD Namira</h3>
                    <p class="text-xs text-slate-500 mt-1.5">Seluruh proses transparan dan dapat dipantau langsung melalui portal calon siswa.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                    <div v-for="s in steps" :key="s.num" class="relative">
                        <div class="text-2xl font-black text-emerald-100 mb-1 font-mono">{{ s.num }}</div>
                        <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wide mb-1">{{ s.title }}</h4>
                        <p class="text-[11px] text-slate-500 leading-relaxed">{{ s.desc }}</p>
                    </div>
                </div>
            </div>

            <!-- Help Contact Box -->
            <div class="mt-8 bg-emerald-50 border border-emerald-200/80 rounded-2xl p-5 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-emerald-900">
                <div class="flex items-center gap-3 text-center sm:text-left">
                    <div class="w-9 h-9 rounded-xl bg-emerald-700 text-white flex items-center justify-center shrink-0">
                        <ChatBubbleLeftRightIcon class="w-5 h-5" />
                    </div>
                    <div>
                        <strong class="font-bold block text-emerald-950 text-sm">Pusat Layanan Informasi SPMB</strong>
                        <span class="text-emerald-800">Senin - Sabtu pukul 07.30 - 15.00 WIB | Kantor Yayasan Namira Kota Probolinggo</span>
                    </div>
                </div>

                <a 
                    href="https://wa.me/6282332922521?text=Assalamu'alaikum%20Panitia%20SPMB%20Namira,%20saya%20ingin%20bertanya%20informasi%20pendaftaran" 
                    target="_blank"
                    class="inline-flex items-center gap-2 bg-[#064e3b] hover:bg-emerald-800 text-white px-4 py-2.5 rounded-xl font-bold transition shrink-0"
                >
                    <PhoneIcon class="w-4 h-4 text-amber-300" />
                    <span>Chat Panitia (WhatsApp)</span>
                </a>
            </div>
        </section>

        <!-- Institutional Footer -->
        <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
            <div class="max-w-7xl mx-auto px-4">
                <p>&copy; {{ new Date().getFullYear() }} Yayasan Namira Kota Probolinggo. Seluruh hak cipta dilindungi.</p>
            </div>
        </footer>

    </div>
</template>
