<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    AcademicCapIcon, 
    CheckCircleIcon, 
    ClockIcon, 
    CalendarDaysIcon, 
    ArrowDownTrayIcon,
    DocumentTextIcon,
    PhoneIcon,
    CreditCardIcon,
    ArrowRightOnRectangleIcon,
    ExclamationTriangleIcon,
    SparklesIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    applicant: Object,
    setting: Object,
});

const uploadForm = useForm({
    payment_proof: null,
});

const handleUploadProof = (e) => {
    const file = e.target.files[0];
    if (file) {
        uploadForm.payment_proof = file;
        uploadForm.post(route('spmb.applicant.upload-proof', props.applicant.id), {
            preserveScroll: true,
            onSuccess: () => {
                alert('Bukti pembayaran daftar ulang berhasil diunggah.');
            }
        });
    }
};

const formatRupiah = (amount) => {
    if (!amount) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);
};
</script>

<template>
    <Head title="Dashboard Pendaftar SPMB - SD Namira" />

    <div class="min-h-screen bg-slate-950 font-sans text-slate-100 flex flex-col justify-between selection:bg-[#fbbf24] selection:text-[#064e3b]">
        <!-- Top App Bar -->
        <header class="bg-slate-900/90 border-b border-slate-800 sticky top-0 z-30 backdrop-blur-md">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-900/80 border border-emerald-500/40 flex items-center justify-center text-[#fbbf24]">
                        <AcademicCapIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <span class="text-xs text-emerald-400 font-bold tracking-wider uppercase block">Portal SPMB Online</span>
                        <h1 class="text-sm sm:text-base font-extrabold text-white leading-tight">SD IT NAMIRA</h1>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a 
                        :href="`https://wa.me/${setting?.contact_whatsapp || '6282332922521'}?text=Halo%20Panitia%20SPMB,%20saya%20orang%20tua%20dari%20${applicant.full_name}%20(Reg:%20${applicant.registration_number})`"
                        target="_blank"
                        class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 text-xs font-semibold hover:bg-emerald-900/80 transition"
                    >
                        <PhoneIcon class="w-4 h-4 text-emerald-400" />
                        <span>Bantuan Panitia</span>
                    </a>

                    <Link 
                        :href="route('logout')" 
                        method="post" 
                        as="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-rose-950/50 hover:text-rose-300 border border-slate-700 text-slate-300 text-xs font-semibold transition"
                    >
                        <ArrowRightOnRectangleIcon class="w-4 h-4" />
                        <span>Keluar</span>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Dashboard Content -->
        <main class="max-w-6xl mx-auto w-full px-4 sm:px-6 py-8 flex-1 space-y-8">
            
            <!-- Hero Applicant Card -->
            <div class="bg-gradient-to-r from-emerald-950/90 via-slate-900 to-slate-900 border border-emerald-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
                <div class="absolute -top-12 -right-12 w-64 h-64 bg-emerald-600/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 relative z-10">
                    <div class="flex items-start sm:items-center gap-4">
                        <!-- Foto Anak -->
                        <div class="w-16 h-20 sm:w-20 sm:h-24 rounded-2xl bg-slate-800 border border-slate-700 overflow-hidden shrink-0 shadow-md">
                            <img v-if="applicant.photo_url" :src="applicant.photo_url" class="w-full h-full object-cover" alt="Foto Anak">
                            <div v-else class="w-full h-full flex items-center justify-center text-slate-500 text-xs">Foto</div>
                        </div>

                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold mb-1.5 border border-emerald-500/30">
                                <span>No. Registrasi: {{ applicant.registration_number }}</span>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-black text-white">
                                {{ applicant.full_name }}
                            </h2>
                            <p class="text-xs sm:text-sm text-slate-300 mt-1">
                                Asal: <span class="text-emerald-300 font-semibold">{{ applicant.previous_school || '-' }}</span> 
                                ({{ applicant.category === 'internal_tk' ? 'Alumni TK Namira' : 'Pendaftar Umum' }})
                            </p>
                        </div>
                    </div>

                    <!-- Status Pill -->
                    <div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-4 text-right min-w-[200px]">
                        <span class="text-[11px] text-slate-400 block font-medium">Status Pendaftaran Saat Ini:</span>
                        <span class="text-sm font-bold text-emerald-400 block mt-0.5">
                            {{ applicant.status_label }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Progress Tracker Stages -->
            <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-6 flex items-center gap-2">
                    <ClockIcon class="w-4 h-4 text-[#fbbf24]" />
                    <span>Tahapan Proses Penerimaan Murid Baru</span>
                </h3>

                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 text-center">
                    
                    <!-- 1. Formulir -->
                    <div class="p-3 rounded-2xl border bg-emerald-950/40 border-emerald-500/60 text-emerald-300 text-xs">
                        <CheckCircleIcon class="w-6 h-6 text-emerald-400 mx-auto mb-1.5" />
                        <span class="font-bold block">1. Form & Berkas</span>
                        <span class="text-[10px] text-emerald-400/80 block mt-0.5">Selesai</span>
                    </div>

                    <!-- 2. Verifikasi QRIS -->
                    <div 
                        class="p-3 rounded-2xl border text-xs transition"
                        :class="applicant.registration_payment_verified_at 
                            ? 'bg-emerald-950/40 border-emerald-500/60 text-emerald-300' 
                            : 'bg-slate-800/40 border-slate-700 text-slate-400'"
                    >
                        <CheckCircleIcon v-if="applicant.registration_payment_verified_at" class="w-6 h-6 text-emerald-400 mx-auto mb-1.5" />
                        <ClockIcon v-else class="w-6 h-6 text-amber-400 mx-auto mb-1.5" />
                        <span class="font-bold block">2. Bayar QRIS</span>
                        <span class="text-[10px] block mt-0.5" :class="applicant.registration_payment_verified_at ? 'text-emerald-400/80' : 'text-amber-400'">
                            {{ applicant.registration_payment_verified_at ? 'Terverifikasi' : 'Menunggu Cek' }}
                        </span>
                    </div>

                    <!-- 3. Jadwal Observasi & Psikotes -->
                    <div 
                        class="p-3 rounded-2xl border text-xs transition"
                        :class="applicant.observation_date 
                            ? 'bg-emerald-950/40 border-emerald-500/60 text-emerald-300' 
                            : 'bg-slate-800/40 border-slate-700 text-slate-400'"
                    >
                        <CheckCircleIcon v-if="applicant.observation_date" class="w-6 h-6 text-emerald-400 mx-auto mb-1.5" />
                        <CalendarDaysIcon v-else class="w-6 h-6 text-slate-500 mx-auto mb-1.5" />
                        <span class="font-bold block">3. Jadwal Seleksi</span>
                        <span class="text-[10px] block mt-0.5" :class="applicant.observation_date ? 'text-emerald-400/80' : 'text-slate-500'">
                            {{ applicant.observation_date ? 'Diterbitkan' : 'Belum Terbit' }}
                        </span>
                    </div>

                    <!-- 4. Hasil Seleksi -->
                    <div 
                        class="p-3 rounded-2xl border text-xs transition"
                        :class="applicant.evaluation_result 
                            ? (applicant.evaluation_result === 'accepted' ? 'bg-emerald-950/40 border-emerald-500/60 text-emerald-300' : 'bg-rose-950/40 border-rose-500/60 text-rose-300')
                            : 'bg-slate-800/40 border-slate-700 text-slate-400'"
                    >
                        <SparklesIcon class="w-6 h-6 mx-auto mb-1.5" :class="applicant.evaluation_result === 'accepted' ? 'text-amber-400' : 'text-slate-500'" />
                        <span class="font-bold block">4. Pengumuman</span>
                        <span class="text-[10px] block mt-0.5 font-semibold">
                            {{ applicant.evaluation_result ? (applicant.evaluation_result === 'accepted' ? 'Diterima' : 'Cadangan/Belum') : 'Menunggu Pleno' }}
                        </span>
                    </div>

                    <!-- 5. Daftar Ulang 60% -->
                    <div 
                        class="p-3 rounded-2xl border text-xs transition"
                        :class="['partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status)
                            ? 'bg-emerald-950/40 border-emerald-500/60 text-emerald-300' 
                            : 'bg-slate-800/40 border-slate-700 text-slate-400'"
                    >
                        <CreditCardIcon class="w-6 h-6 mx-auto mb-1.5" :class="['partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status) ? 'text-emerald-400' : 'text-slate-500'" />
                        <span class="font-bold block">5. Termin 60%</span>
                        <span class="text-[10px] block mt-0.5">
                            {{ ['partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status) ? 'Sudah Bayar' : 'Belum' }}
                        </span>
                    </div>

                    <!-- 6. Pelunasan 100% -->
                    <div 
                        class="p-3 rounded-2xl border text-xs transition"
                        :class="['fully_paid', 'enrolled'].includes(applicant.status)
                            ? 'bg-emerald-950/40 border-emerald-500/60 text-emerald-300' 
                            : 'bg-slate-800/40 border-slate-700 text-slate-400'"
                    >
                        <CheckCircleIcon class="w-6 h-6 mx-auto mb-1.5" :class="['fully_paid', 'enrolled'].includes(applicant.status) ? 'text-emerald-400' : 'text-slate-500'" />
                        <span class="font-bold block">6. Lunas 100%</span>
                        <span class="text-[10px] block mt-0.5">
                            {{ ['fully_paid', 'enrolled'].includes(applicant.status) ? 'Lunas Tuntas' : 'Menunggu' }}
                        </span>
                    </div>

                    <!-- 7. Siswa Resmi -->
                    <div 
                        class="p-3 rounded-2xl border text-xs transition col-span-2 sm:col-span-1"
                        :class="applicant.status === 'enrolled'
                            ? 'bg-emerald-950/40 border-emerald-500/60 text-emerald-300' 
                            : 'bg-slate-800/40 border-slate-700 text-slate-400'"
                    >
                        <AcademicCapIcon class="w-6 h-6 mx-auto mb-1.5" :class="applicant.status === 'enrolled' ? 'text-[#fbbf24]' : 'text-slate-500'" />
                        <span class="font-bold block">7. Siswa Aktif</span>
                        <span class="text-[10px] block mt-0.5">
                            {{ applicant.status === 'enrolled' ? 'Resmi Siswa' : 'Tahap Akhir' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Documents & Download Center -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- 1. Kartu Tanda Peserta -->
                <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 flex flex-col justify-between shadow-xl">
                    <div>
                        <div class="w-10 h-10 rounded-xl bg-emerald-900/40 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-4">
                            <DocumentTextIcon class="w-5 h-5" />
                        </div>
                        <h4 class="font-bold text-white text-base">Kartu Peserta SPMB</h4>
                        <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">
                            Wajib dicetak dan dibawa calon siswa saat mengikuti observasi dan psikotes di sekolah.
                        </p>
                    </div>

                    <div class="mt-6 pt-2">
                        <a 
                            :href="route('spmb.applicant.print-card', applicant.id)" 
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-2 bg-[#064e3b] hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition border border-emerald-500/30"
                        >
                            <ArrowDownTrayIcon class="w-4 h-4 text-[#fbbf24]" />
                            <span>Unduh Kartu Peserta (PDF)</span>
                        </a>
                    </div>
                </div>

                <!-- 2. Surat Keputusan Kelulusan -->
                <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 flex flex-col justify-between shadow-xl">
                    <div>
                        <div class="w-10 h-10 rounded-xl bg-amber-900/40 border border-amber-500/30 flex items-center justify-center text-amber-400 mb-4">
                            <SparklesIcon class="w-5 h-5" />
                        </div>
                        <h4 class="font-bold text-white text-base">Surat Keterangan Diterima</h4>
                        <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">
                            Surat Keputusan resmi berkop Yayasan Namira yang menerangkan ananda diterima.
                        </p>
                    </div>

                    <div class="mt-6 pt-2">
                        <a 
                            v-if="['accepted', 'partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status)"
                            :href="route('spmb.applicant.print-skl', applicant.id)" 
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-2 bg-amber-600 hover:bg-amber-500 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition shadow"
                        >
                            <ArrowDownTrayIcon class="w-4 h-4 text-white" />
                            <span>Unduh Surat Diterima (PDF)</span>
                        </a>
                        <button 
                            v-else 
                            disabled 
                            class="w-full inline-flex items-center justify-center gap-2 bg-slate-800 text-slate-500 font-semibold py-2.5 px-4 rounded-xl text-xs cursor-not-allowed"
                        >
                            <span>Belum Tersedia</span>
                        </button>
                    </div>
                </div>

                <!-- 3. Rapor Hasil Evaluasi / Psikotes -->
                <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 flex flex-col justify-between shadow-xl">
                    <div>
                        <div class="w-10 h-10 rounded-xl bg-blue-900/40 border border-blue-500/30 flex items-center justify-center text-blue-400 mb-4">
                            <AcademicCapIcon class="w-5 h-5" />
                        </div>
                        <h4 class="font-bold text-white text-base">Hasil Observasi & Psikotes</h4>
                        <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">
                            Rangkuman perkembangan anak dan catatan kesiapan belajar dari tim guru & psikolog.
                        </p>
                    </div>

                    <div class="mt-6 pt-2">
                        <a 
                            v-if="applicant.evaluation_document_url"
                            :href="applicant.evaluation_document_url" 
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-2 bg-blue-700 hover:bg-blue-600 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition"
                        >
                            <ArrowDownTrayIcon class="w-4 h-4 text-white" />
                            <span>Lihat Hasil Evaluasi (PDF)</span>
                        </a>
                        <div v-else class="text-center py-2 text-xs text-slate-500">
                            {{ applicant.evaluation_notes ? applicant.evaluation_notes : 'Hasil tes belum diunggah' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two Offline Schedule Cards -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-6 flex items-center gap-2">
                    <CalendarDaysIcon class="w-4 h-4 text-[#fbbf24]" />
                    <span>Jadwal Agenda Seleksi Offline di SD Namira</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Agenda 1: Observasi Dasar -->
                    <div class="p-5 rounded-2xl bg-slate-800/50 border border-slate-700 border-l-4 border-l-emerald-500 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-emerald-400 uppercase tracking-wide">Agenda 1: Observasi Dasar</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-950 text-emerald-300 text-[10px] font-semibold">Tatap Muka</span>
                        </div>
                        <div class="space-y-1.5 text-xs text-slate-300">
                            <div class="flex items-center justify-between py-1 border-b border-slate-700/60">
                                <span class="text-slate-400">Hari, Tanggal:</span>
                                <strong class="text-white">{{ applicant.observation_date || 'Menunggu Konfirmasi' }}</strong>
                            </div>
                            <div class="flex items-center justify-between py-1 border-b border-slate-700/60">
                                <span class="text-slate-400">Waktu Pelaksanaan:</span>
                                <strong class="text-white">{{ applicant.observation_time || 'Akan diinfokan via WA' }}</strong>
                            </div>
                            <div class="flex items-center justify-between py-1">
                                <span class="text-slate-400">Ruangan / Tempat:</span>
                                <strong class="text-white">{{ applicant.observation_location || 'Gedung SD Namira' }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Agenda 2: Psikotes -->
                    <div class="p-5 rounded-2xl bg-slate-800/50 border border-slate-700 border-l-4 border-l-amber-500 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-amber-400 uppercase tracking-wide">Agenda 2: Psikotes Calon Siswa</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-amber-950 text-amber-300 text-[10px] font-semibold">Tatap Muka</span>
                        </div>
                        <div class="space-y-1.5 text-xs text-slate-300">
                            <div class="flex items-center justify-between py-1 border-b border-slate-700/60">
                                <span class="text-slate-400">Hari, Tanggal:</span>
                                <strong class="text-white">{{ applicant.psychotest_date || 'Menunggu Konfirmasi' }}</strong>
                            </div>
                            <div class="flex items-center justify-between py-1 border-b border-slate-700/60">
                                <span class="text-slate-400">Waktu Pelaksanaan:</span>
                                <strong class="text-white">{{ applicant.psychotest_time || 'Akan diinfokan via WA' }}</strong>
                            </div>
                            <div class="flex items-center justify-between py-1">
                                <span class="text-slate-400">Ruangan / Tempat:</span>
                                <strong class="text-white">{{ applicant.psychotest_location || 'Gedung SD Namira' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="applicant.schedule_notes" class="mt-4 p-3.5 rounded-xl bg-slate-800/70 border border-slate-700 text-xs text-slate-300">
                    <strong class="text-[#fbbf24]">Catatan Panitia:</strong> {{ applicant.schedule_notes }}
                </div>
            </div>

            <!-- Virtual Account & Re-registration Section -->
            <div v-if="['accepted', 'partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status)" class="bg-slate-900/80 border border-emerald-500/40 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
                <div class="flex items-center gap-3">
                    <CreditCardIcon class="w-6 h-6 text-[#fbbf24]" />
                    <div>
                        <h3 class="text-base font-bold text-white">Informasi Daftar Ulang & Virtual Account (VA) Bank Jatim</h3>
                        <p class="text-xs text-slate-400">Selamat! Ananda dinyatakan lolos. Silakan lakukan pembayaran daftar ulang sesuai ketentuan di bawah ini.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- VA Detail Box -->
                    <div class="p-6 rounded-2xl bg-emerald-950/40 border border-emerald-500/40 space-y-3">
                        <span class="text-xs font-bold text-emerald-400 block uppercase">Nomor Virtual Account Bank Jatim:</span>
                        <div class="text-2xl sm:text-3xl font-black text-white tracking-wider">
                            {{ applicant.virtual_account_number || 'Akan segera diterbitkan oleh bagian Keuangan' }}
                        </div>

                        <div class="space-y-1 pt-3 border-t border-emerald-500/20 text-xs text-slate-300">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Total Biaya Masuk:</span>
                                <strong class="text-white">{{ formatRupiah(applicant.total_admission_fee) }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Termin 1 (Minimal 60%):</span>
                                <strong class="text-amber-400">{{ formatRupiah(applicant.min_down_payment) }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Total Sudah Dibayar:</span>
                                <strong class="text-emerald-400">{{ formatRupiah(applicant.paid_admission_amount) }}</strong>
                            </div>
                            <div v-if="applicant.down_payment_deadline" class="flex justify-between text-rose-400">
                                <span>Batas Waktu Termin 1:</span>
                                <strong>{{ applicant.down_payment_deadline }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Form for Re-registration Proof -->
                    <div class="p-6 rounded-2xl bg-slate-800/40 border border-slate-700 flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-bold text-white block">Konfirmasi Pembayaran Daftar Ulang</span>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Setelah melakukan transfer ke nomor VA di samping, silakan unggah bukti transfer di bawah ini agar diverifikasi oleh panitia keuangan.
                            </p>
                        </div>

                        <div class="mt-4">
                            <div v-if="applicant.re_registration_payment_proof" class="p-3 rounded-xl bg-emerald-950/60 border border-emerald-500/30 text-xs text-emerald-300 font-semibold mb-3">
                                ✓ Bukti transfer telah terkirim dan sedang diverifikasi oleh panitia.
                            </div>

                            <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#064e3b] hover:bg-emerald-700 border border-emerald-500/40 text-xs font-bold text-white transition">
                                <DocumentTextIcon class="w-4 h-4 text-[#fbbf24]" />
                                <span>Unggah Bukti Transfer Daftar Ulang</span>
                                <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="handleUploadProof">
                            </label>
                        </div>
                    </div>
                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer class="py-6 text-center text-xs text-slate-500 border-t border-slate-900">
            <p>&copy; {{ new Date().getFullYear() }} Yayasan Namira Probolinggo. Layanan Bantuan: {{ setting?.contact_whatsapp || '082332922521' }}</p>
        </footer>
    </div>
</template>
