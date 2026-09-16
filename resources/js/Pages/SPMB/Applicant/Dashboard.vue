<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    AcademicCapIcon, 
    CheckCircleIcon, 
    ClockIcon, 
    CalendarDaysIcon, 
    ArrowDownTrayIcon,
    CheckBadgeIcon,
    ExclamationTriangleIcon,
    CreditCardIcon,
    ArrowRightOnRectangleIcon,
    UserIcon,
    MapPinIcon
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
                alert('Bukti transfer daftar ulang berhasil diunggah.');
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
    <Head title="Portal Calon Siswa - SPMB SD Namira" />

    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 flex flex-col justify-between selection:bg-emerald-100 selection:text-emerald-900">
        
        <!-- Top Institutional App Bar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-800 text-white flex items-center justify-center font-bold">
                        <AcademicCapIcon class="w-5 h-5 text-amber-300" />
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Portal Calon Siswa SPMB</span>
                        <h1 class="text-xs sm:text-sm font-black text-slate-900 leading-tight">SD IT NAMIRA KOTA PROBOLINGGO</h1>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <a 
                        :href="`https://wa.me/${setting?.contact_whatsapp || '6282332922521'}?text=Assalamu'alaikum%20Panitia%20SPMB,%20saya%20orang%20tua%20dari%20${applicant.full_name}%20(Reg:%20${applicant.registration_number})`"
                        target="_blank"
                        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold transition border border-emerald-200"
                    >
                        <PhoneIcon class="w-3.5 h-3.5 text-emerald-700" />
                        <span>Bantuan Panitia</span>
                    </a>

                    <Link 
                        :href="route('logout')" 
                        method="post" 
                        as="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-rose-50 hover:text-rose-700 border border-slate-200 text-slate-700 text-xs font-bold transition"
                    >
                        <ArrowRightOnRectangleIcon class="w-3.5 h-3.5" />
                        <span>Keluar</span>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Portal Container -->
        <main class="max-w-6xl mx-auto w-full px-4 sm:px-6 py-8 flex-1 space-y-6">
            
            <!-- Applicant Profile Card -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-7 shadow-xs">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                    <div class="flex items-start sm:items-center gap-4">
                        <!-- Pas Foto 3x4 -->
                        <div class="w-16 h-20 sm:w-20 sm:h-24 rounded-xl bg-slate-100 border border-slate-300 overflow-hidden shrink-0 shadow-xs">
                            <img v-if="applicant.photo_url" :src="applicant.photo_url" class="w-full h-full object-cover" alt="Pas Foto">
                            <div v-else class="w-full h-full flex items-center justify-center text-slate-400 text-xs font-bold">3x4</div>
                        </div>

                        <div>
                            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-[11px] font-bold mb-1">
                                <span class="font-mono">{{ applicant.registration_number }}</span>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-black text-slate-900">
                                {{ applicant.full_name }}
                            </h2>
                            <p class="text-xs text-slate-600 mt-1">
                                Asal Sekolah: <strong>{{ applicant.previous_school || '-' }}</strong> 
                                ({{ applicant.category === 'internal_tk' ? 'Alumni TK Namira' : 'Pendaftar Umum' }})
                            </p>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Orang Tua: {{ applicant.father_name || applicant.mother_name }} | No. WA: {{ applicant.parent_phone }}
                            </p>
                        </div>
                    </div>

                    <!-- Status Highlight -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-left sm:text-right shrink-0 min-w-[220px]">
                        <span class="text-[11px] font-semibold text-slate-500 block uppercase">Status Pendaftaran:</span>
                        <strong class="text-sm font-extrabold text-emerald-700 block mt-0.5">
                            {{ applicant.status_label }}
                        </strong>
                        <span class="text-[10px] text-slate-400 block mt-1">Jalur Inden TA {{ setting?.academic_year || '2026/2027' }}</span>
                    </div>
                </div>
            </div>

            <!-- Milestone Progress Tracker -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-5 flex items-center gap-2">
                    <ClockIcon class="w-4 h-4 text-emerald-700" />
                    <span>Perjalanan Pendaftaran Peserta Didik Baru</span>
                </h3>

                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5 text-center">
                    
                    <!-- 1. Form & Berkas -->
                    <div class="p-3 rounded-xl border bg-emerald-50 border-emerald-300 text-emerald-900 text-xs">
                        <CheckCircleIcon class="w-5 h-5 text-emerald-700 mx-auto mb-1" />
                        <strong class="block text-[11px]">1. Formulir</strong>
                        <span class="text-[10px] text-emerald-700 font-semibold block mt-0.5">Selesai</span>
                    </div>

                    <!-- 2. QRIS -->
                    <div 
                        class="p-3 rounded-xl border text-xs transition"
                        :class="applicant.registration_payment_verified_at 
                            ? 'bg-emerald-50 border-emerald-300 text-emerald-900' 
                            : 'bg-slate-50 border-slate-200 text-slate-500'"
                    >
                        <CheckCircleIcon v-if="applicant.registration_payment_verified_at" class="w-5 h-5 text-emerald-700 mx-auto mb-1" />
                        <ClockIcon v-else class="w-5 h-5 text-amber-600 mx-auto mb-1" />
                        <strong class="block text-[11px]">2. Cek QRIS</strong>
                        <span class="text-[10px] font-semibold block mt-0.5" :class="applicant.registration_payment_verified_at ? 'text-emerald-700' : 'text-amber-600'">
                            {{ applicant.registration_payment_verified_at ? 'Terverifikasi' : 'Menunggu Cek' }}
                        </span>
                    </div>

                    <!-- 3. Jadwal Seleksi -->
                    <div 
                        class="p-3 rounded-xl border text-xs transition"
                        :class="applicant.observation_date 
                            ? 'bg-emerald-50 border-emerald-300 text-emerald-900' 
                            : 'bg-slate-50 border-slate-200 text-slate-500'"
                    >
                        <CheckCircleIcon v-if="applicant.observation_date" class="w-5 h-5 text-emerald-700 mx-auto mb-1" />
                        <CalendarDaysIcon v-else class="w-5 h-5 text-slate-400 mx-auto mb-1" />
                        <strong class="block text-[11px]">3. Jadwal Tes</strong>
                        <span class="text-[10px] font-semibold block mt-0.5" :class="applicant.observation_date ? 'text-emerald-700' : 'text-slate-400'">
                            {{ applicant.observation_date ? 'Diterbitkan' : 'Belum' }}
                        </span>
                    </div>

                    <!-- 4. Pengumuman -->
                    <div 
                        class="p-3 rounded-xl border text-xs transition"
                        :class="applicant.evaluation_result 
                            ? (applicant.evaluation_result === 'accepted' ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : 'bg-rose-50 border-rose-300 text-rose-900') 
                            : 'bg-slate-50 border-slate-200 text-slate-500'"
                    >
                        <CheckBadgeIcon class="w-5 h-5 mx-auto mb-1" :class="applicant.evaluation_result === 'accepted' ? 'text-emerald-700' : 'text-slate-400'" />
                        <strong class="block text-[11px]">4. Keputusan</strong>
                        <span class="text-[10px] font-semibold block mt-0.5">
                            {{ applicant.evaluation_result ? (applicant.evaluation_result === 'accepted' ? 'Diterima' : 'Cadangan/Belum') : 'Menunggu' }}
                        </span>
                    </div>

                    <!-- 5. Termin 60% -->
                    <div 
                        class="p-3 rounded-xl border text-xs transition"
                        :class="['partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status)
                            ? 'bg-emerald-50 border-emerald-300 text-emerald-900' 
                            : 'bg-slate-50 border-slate-200 text-slate-500'"
                    >
                        <CreditCardIcon class="w-5 h-5 mx-auto mb-1" :class="['partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status) ? 'text-emerald-700' : 'text-slate-400'" />
                        <strong class="block text-[11px]">5. Termin 60%</strong>
                        <span class="text-[10px] font-semibold block mt-0.5">
                            {{ ['partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status) ? 'Lunas 60%' : 'Menunggu' }}
                        </span>
                    </div>

                    <!-- 6. Lunas 100% -->
                    <div 
                        class="p-3 rounded-xl border text-xs transition"
                        :class="['fully_paid', 'enrolled'].includes(applicant.status)
                            ? 'bg-emerald-50 border-emerald-300 text-emerald-900' 
                            : 'bg-slate-50 border-slate-200 text-slate-500'"
                    >
                        <CheckCircleIcon class="w-5 h-5 mx-auto mb-1" :class="['fully_paid', 'enrolled'].includes(applicant.status) ? 'text-emerald-700' : 'text-slate-400'" />
                        <strong class="block text-[11px]">6. Lunas 100%</strong>
                        <span class="text-[10px] font-semibold block mt-0.5">
                            {{ ['fully_paid', 'enrolled'].includes(applicant.status) ? 'Lunas Tuntas' : 'Menunggu' }}
                        </span>
                    </div>

                    <!-- 7. Siswa Aktif -->
                    <div 
                        class="p-3 rounded-xl border text-xs transition col-span-2 sm:col-span-1"
                        :class="applicant.status === 'enrolled'
                            ? 'bg-emerald-100 border-emerald-400 text-emerald-900 font-bold' 
                            : 'bg-slate-50 border-slate-200 text-slate-500'"
                    >
                        <AcademicCapIcon class="w-5 h-5 mx-auto mb-1" :class="applicant.status === 'enrolled' ? 'text-emerald-800' : 'text-slate-400'" />
                        <strong class="block text-[11px]">7. Siswa Aktif</strong>
                        <span class="text-[10px] font-semibold block mt-0.5">
                            {{ applicant.status === 'enrolled' ? 'Resmi Siswa' : 'Tahap Akhir' }}
                        </span>
                    </div>

                </div>
            </div>

            <!-- Documents Download Center -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                
                <!-- 1. Kartu Peserta -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between shadow-xs">
                    <div>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold mb-3">
                            <DocumentTextIcon class="w-5 h-5" />
                        </div>
                        <h4 class="font-extrabold text-sm text-slate-900">Kartu Tanda Peserta SPMB</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Cetak dan bawa kartu ini saat hadir untuk observasi dan psikotes di SD Namira.
                        </p>
                    </div>

                    <div class="mt-5 pt-3 border-t border-slate-100">
                        <a 
                            :href="route('spmb.applicant.print-card', applicant.id)" 
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-2 bg-[#064e3b] hover:bg-emerald-800 text-white font-bold py-2 px-3 rounded-xl text-xs transition"
                        >
                            <ArrowDownTrayIcon class="w-3.5 h-3.5 text-amber-300" />
                            <span>Unduh Kartu Peserta (PDF)</span>
                        </a>
                    </div>
                </div>

                <!-- 2. Surat Keputusan Diterima -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between shadow-xs">
                    <div>
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold mb-3">
                            <CheckBadgeIcon class="w-5 h-5" />
                        </div>
                        <h4 class="font-extrabold text-sm text-slate-900">Surat Keterangan Diterima</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Surat keputusan resmi berkop sekolah bagi ananda yang dinyatakan lulus seleksi.
                        </p>
                    </div>

                    <div class="mt-5 pt-3 border-t border-slate-100">
                        <a 
                            v-if="['accepted', 'partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status)"
                            :href="route('spmb.applicant.print-skl', applicant.id)" 
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 px-3 rounded-xl text-xs transition"
                        >
                            <ArrowDownTrayIcon class="w-3.5 h-3.5 text-white" />
                            <span>Unduh Surat Diterima (PDF)</span>
                        </a>
                        <button 
                            v-else 
                            disabled 
                            class="w-full inline-flex items-center justify-center gap-1 bg-slate-100 text-slate-400 font-bold py-2 px-3 rounded-xl text-xs cursor-not-allowed"
                        >
                            <span>Belum Tersedia</span>
                        </button>
                    </div>
                </div>

                <!-- 3. Rapor Hasil Observasi -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between shadow-xs">
                    <div>
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold mb-3">
                            <AcademicCapIcon class="w-5 h-5" />
                        </div>
                        <h4 class="font-extrabold text-sm text-slate-900">Hasil Observasi & Psikotes</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Rangkuman evaluasi kesiapan belajar anak dari tim psikolog & guru penguji.
                        </p>
                    </div>

                    <div class="mt-5 pt-3 border-t border-slate-100">
                        <a 
                            v-if="applicant.evaluation_document_url"
                            :href="applicant.evaluation_document_url" 
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-2 bg-blue-700 hover:bg-blue-800 text-white font-bold py-2 px-3 rounded-xl text-xs transition"
                        >
                            <ArrowDownTrayIcon class="w-3.5 h-3.5 text-white" />
                            <span>Buka Dokumen Hasil (PDF)</span>
                        </a>
                        <div v-else class="text-center py-1 text-xs text-slate-400 italic">
                            {{ applicant.evaluation_notes ? applicant.evaluation_notes : 'Dokumen belum diunggah' }}
                        </div>
                    </div>
                </div>

            </div>

            <!-- Two Offline Schedule Cards -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-4 flex items-center gap-2">
                    <CalendarDaysIcon class="w-4 h-4 text-emerald-700" />
                    <span>Jadwal Kegiatan Seleksi Tatap Muka di Sekolah</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Agenda 1 -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 border-l-4 border-l-emerald-600 text-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <strong class="text-emerald-900 font-bold">1. Agenda Observasi Dasar</strong>
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">Offline</span>
                        </div>
                        <div class="space-y-1 text-slate-600 pt-1">
                            <div>Hari/Tgl: <strong class="text-slate-900">{{ applicant.observation_date || 'Menunggu Penetapan' }}</strong></div>
                            <div>Waktu: <strong class="text-slate-900">{{ applicant.observation_time || 'Akan diinfokan via WA' }}</strong></div>
                            <div>Lokasi: <strong class="text-slate-900">{{ applicant.observation_location || 'Gedung SD IT Namira' }}</strong></div>
                        </div>
                    </div>

                    <!-- Agenda 2 -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 border-l-4 border-l-amber-600 text-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <strong class="text-amber-900 font-bold">2. Agenda Psikotes Calon Siswa</strong>
                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold">Offline</span>
                        </div>
                        <div class="space-y-1 text-slate-600 pt-1">
                            <div>Hari/Tgl: <strong class="text-slate-900">{{ applicant.psychotest_date || 'Menunggu Penetapan' }}</strong></div>
                            <div>Waktu: <strong class="text-slate-900">{{ applicant.psychotest_time || 'Akan diinfokan via WA' }}</strong></div>
                            <div>Lokasi: <strong class="text-slate-900">{{ applicant.psychotest_location || 'Gedung SD IT Namira' }}</strong></div>
                        </div>
                    </div>
                </div>

                <div v-if="applicant.schedule_notes" class="mt-4 p-3 rounded-xl bg-amber-50/70 border border-amber-200 text-xs text-amber-900">
                    <strong>Catatan Panitia:</strong> {{ applicant.schedule_notes }}
                </div>
            </div>

            <!-- Virtual Account & Re-registration Section -->
            <div v-if="['accepted', 'partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status)" class="bg-white border-2 border-emerald-600/70 rounded-2xl p-6 sm:p-7 shadow-sm space-y-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-800 flex items-center justify-center">
                        <CreditCardIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Ketentuan Daftar Ulang (Virtual Account Bank Jatim)</h3>
                        <p class="text-xs text-slate-500">Selamat! Ananda dinyatakan diterima. Silakan selesaikan pembayaran daftar ulang.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Banking Receipt Card -->
                    <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                        <span class="text-[11px] font-bold text-slate-500 block uppercase">Nomor Virtual Account (VA) Bank Jatim:</span>
                        <div class="text-xl sm:text-2xl font-black text-emerald-800 font-mono tracking-wider">
                            {{ applicant.virtual_account_number || 'Sedang Diterbitkan oleh Keuangan' }}
                        </div>

                        <div class="space-y-1.5 pt-3 border-t border-slate-200 text-xs text-slate-600">
                            <div class="flex justify-between">
                                <span>Total Biaya Masuk:</span>
                                <strong class="text-slate-900">{{ formatRupiah(applicant.total_admission_fee) }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Termin 1 (Minimal 60%):</span>
                                <strong class="text-amber-700">{{ formatRupiah(applicant.min_down_payment) }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Total Sudah Masuk:</span>
                                <strong class="text-emerald-700">{{ formatRupiah(applicant.paid_admission_amount) }}</strong>
                            </div>
                            <div v-if="applicant.down_payment_deadline" class="flex justify-between text-rose-600">
                                <span>Batas Bayar Termin 1:</span>
                                <strong>{{ applicant.down_payment_deadline }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Proof Box -->
                    <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-bold text-slate-800 block">Konfirmasi Bukti Transfer VA</span>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Setelah transfer ke nomor Virtual Account di samping, silakan unggah bukti transfer agar panitia memvalidasi pelunasan.
                            </p>
                        </div>

                        <div class="mt-4">
                            <div v-if="applicant.re_registration_payment_proof" class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 font-bold mb-3">
                                ✓ Bukti pembayaran sudah terkirim ke panitia.
                            </div>

                            <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#064e3b] hover:bg-emerald-800 text-white text-xs font-bold transition">
                                <DocumentTextIcon class="w-4 h-4 text-amber-300" />
                                <span>Unggah Bukti Transfer Daftar Ulang</span>
                                <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="handleUploadProof">
                            </label>
                        </div>
                    </div>

                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
            <div class="max-w-6xl mx-auto px-4">
                <p>&copy; {{ new Date().getFullYear() }} Yayasan Namira Kota Probolinggo. Bantuan SPMB: {{ setting?.contact_whatsapp || '082332922521' }}</p>
            </div>
        </footer>

    </div>
</template>
