<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { 
    LockClosedIcon, 
    PhoneIcon, 
    ArrowLeftIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    contactWhatsapp: {
        type: String,
        default: '082332922521'
    },
    academicYear: {
        type: String,
        default: '2026/2027'
    }
});

const waLink = `https://wa.me/${props.contactWhatsapp ? props.contactWhatsapp.replace(/^0/, '62') : '6282332922521'}?text=Assalamu'alaikum%20Panitia%20SPMB%20Namira,%20saya%20ingin%20menanyakan%20informasi%20pendaftaran%20siswa%20baru`;
</script>

<template>
    <Head title="Pemberitahuan SPMB - Yayasan Namira" />

    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 flex flex-col justify-between selection:bg-emerald-100 selection:text-emerald-900">
        
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between">
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

                <Link 
                    :href="route('login')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition border border-slate-200"
                >
                    <span>Login Petugas</span>
                </Link>
            </div>
        </header>

        <!-- Main Announcement Card -->
        <main class="max-w-2xl mx-auto px-4 sm:px-6 py-12 flex-1 flex flex-col justify-center items-center text-center">
            
            <div class="bg-white border border-slate-200 rounded-3xl p-8 sm:p-12 shadow-sm relative overflow-hidden w-full">
                
                <!-- Lock Icon Badge -->
                <div class="w-20 h-20 rounded-3xl bg-emerald-50 border border-emerald-200 flex items-center justify-center mx-auto mb-6 text-emerald-800 shadow-xs">
                    <LockClosedIcon class="w-10 h-10 text-[#064e3b]" />
                </div>

                <!-- Status Pill -->
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-amber-50 border border-amber-300 text-amber-900 text-xs font-bold uppercase tracking-wider mb-4">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>Dalam Penyesuaian Sistem</span>
                </div>

                <!-- Main Title -->
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-snug mb-3">
                    Layanan Pendaftaran SPMB <br/>
                    <span class="text-emerald-800">Sedang Ditutup Sementara</span>
                </h1>

                <!-- Body Explanation -->
                <div class="text-xs sm:text-sm text-slate-600 leading-relaxed max-w-lg mx-auto space-y-3 mb-8">
                    <p>
                        Assalamu’alaikum Warahmatullahi Wabarakatuh.
                    </p>
                    <p>
                        Saat ini portal Penerimaan Murid Baru (Jalur Inden SD Namira dan unit lainnya) untuk Tahun Ajaran <strong>{{ academicYear }}</strong> sedang dalam tahap finalisasi dan penyesuaian sistem teknis agar pelayanan pendaftaran dapat berjalan lebih optimal.
                    </p>
                    <p class="font-medium text-slate-700">
                        Pendaftaran online akan segera kami buka kembali. Mohon kesediaannya untuk menunggu informasi resmi pembukaan formulir.
                    </p>
                </div>

                <!-- Info Box Panitia -->
                <div class="bg-emerald-50/70 border border-emerald-200 rounded-2xl p-5 mb-8 text-left flex items-start gap-3.5">
                    <InformationCircleIcon class="w-5 h-5 text-emerald-700 shrink-0 mt-0.5" />
                    <div class="text-xs">
                        <strong class="font-bold text-emerald-950 block mb-0.5">Butuh Informasi Lebih Lanjut?</strong>
                        <p class="text-emerald-800 leading-relaxed">
                            Bagi Bapak/Ibu orang tua calon siswa yang ingin berkonsultasi atau menanyakan ketentuan pendaftaran, silakan hubungi Panitia SPMB Sekolah Islam Namira.
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a 
                        :href="waLink"
                        target="_blank"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-[#064e3b] hover:bg-emerald-800 text-white font-bold px-6 py-3 rounded-xl text-xs transition shadow-xs"
                    >
                        <PhoneIcon class="w-4 h-4 text-amber-300" />
                        <span>Hubungi Panitia (WhatsApp)</span>
                    </a>

                    <Link 
                        href="/"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-5 py-3 rounded-xl text-xs transition border border-slate-200"
                    >
                        <ArrowLeftIcon class="w-4 h-4" />
                        <span>Kembali ke Beranda</span>
                    </Link>
                </div>

            </div>

        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
            <div class="max-w-4xl mx-auto px-4">
                <p>&copy; {{ new Date().getFullYear() }} Yayasan Namira Kota Probolinggo. Seluruh hak cipta dilindungi.</p>
            </div>
        </footer>

    </div>
</template>
