<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    AcademicCapIcon, 
    ArrowLeftIcon, 
    ArrowRightIcon, 
    CheckCircleIcon,
    CameraIcon,
    DocumentTextIcon,
    QrCodeIcon,
    PhoneIcon,
    LockClosedIcon,
    UserIcon,
    HomeIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline';

import { compressImageFile } from '@/Utils/imageCompressor';

const props = defineProps({
    unit: Object,
    setting: Object,
    totalApplicants: Number,
});

const currentStep = ref(1);
const isCompressing = ref(false);

const form = useForm({
    // Step 1: Biodata Santri
    full_name: '',
    nickname: '',
    gender: 'L',
    nik: '',
    nisn: '',
    birth_place: '',
    birth_date: '',
    religion: 'Islam',
    child_order: 1,
    total_siblings: 1,
    category: 'eksternal_umum',
    previous_school: '',
    special_notes: '',

    // Data Orang Tua / Wali
    father_name: '',
    father_nik: '',
    father_phone: '',
    father_job: '',
    father_education: '',

    mother_name: '',
    mother_nik: '',
    mother_phone: '',
    mother_job: '',
    mother_education: '',

    parent_phone: '',
    password: '',
    password_confirmation: '',

    address: '',
    rt: '',
    rw: '',
    village: '',
    district: '',
    city: 'Probolinggo',
    postal_code: '',

    // Step 2: Bukti Pembayaran Pendaftaran (Wajib Fase Awal)
    registration_payment_proof: null,
});

const fileNamePaymentProof = ref('');

const onPaymentProofChange = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    if (file.type && file.type.startsWith('image/')) {
        try {
            isCompressing.value = true;
            const compressed = await compressImageFile(file, 1280, 0.75);
            form.registration_payment_proof = compressed;
            fileNamePaymentProof.value = compressed.name;
        } catch (err) {
            console.error('Kompresi bukti pembayaran gagal:', err);
            form.registration_payment_proof = file;
            fileNamePaymentProof.value = file.name;
        } finally {
            isCompressing.value = false;
        }
    } else {
        form.registration_payment_proof = file;
        fileNamePaymentProof.value = file.name;
    }
};

const validateStep1 = () => {
    if (!form.full_name || !form.birth_place || !form.birth_date || !form.gender) {
        alert('Mohon lengkapi data wajib calon santri (Nama Lengkap, Tempat & Tanggal Lahir, Jenis Kelamin).');
        return false;
    }
    if (!form.previous_school) {
        alert('Mohon isi Asal Sekolah Dasar (SD/MI).');
        return false;
    }
    if (!form.father_name && !form.mother_name) {
        alert('Mohon isi nama orang tua (Ayah atau Ibu).');
        return false;
    }
    if (!form.parent_phone) {
        alert('Mohon isi Nomor WhatsApp aktif sebagai akun login Anda.');
        return false;
    }
    if (!form.password || form.password.length < 6) {
        alert('Kata sandi akun minimal 6 karakter.');
        return false;
    }
    if (form.password !== form.password_confirmation) {
        alert('Konfirmasi kata sandi tidak cocok.');
        return false;
    }
    if (!form.address) {
        alert('Mohon isi alamat domisili tempat tinggal.');
        return false;
    }
    return true;
};

const goToStep2 = () => {
    if (validateStep1()) {
        currentStep.value = 2;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const submitRegistration = () => {
    if (!form.registration_payment_proof) {
        alert('Mohon unggah bukti pembayaran pendaftaran.');
        return;
    }

    form.post(route('spmb.store.smp'), {
        preserveScroll: true,
        onError: () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
};

const formatRupiah = (amount) => {
    if (!amount) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);
};
</script>

<template>
    <Head title="Formulir Pendaftaran - SMP Namira Kota Probolinggo" />

    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 pb-20 selection:bg-blue-100 selection:text-blue-900">
        
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
            <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
                <Link :href="route('spmb.index')" class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                    <ArrowLeftIcon class="w-4 h-4" />
                    <span>Kembali ke Beranda SPMB</span>
                </Link>

                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-700 text-white flex items-center justify-center font-bold text-xs">
                        SMP
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 font-bold uppercase block leading-none">Formulir Pendaftaran</span>
                        <span class="text-xs font-black text-slate-900">SMP NAMIRA KOTA PROBOLINGGO</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Stepper Indicator -->
        <div class="max-w-4xl mx-auto px-4 pt-8 pb-4">
            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                <div 
                    class="flex items-center gap-3 cursor-pointer"
                    @click="currentStep = 1"
                >
                    <div 
                        class="w-9 h-9 rounded-xl flex items-center justify-center font-mono font-bold text-xs transition"
                        :class="currentStep === 1 ? 'bg-blue-700 text-white shadow-sm' : 'bg-slate-100 text-slate-600'"
                    >
                        1
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Langkah 1</span>
                        <strong class="text-xs text-slate-900">Biodata Santri & Orang Tua</strong>
                    </div>
                </div>

                <div class="h-0.5 flex-1 mx-4 bg-slate-200"></div>

                <div 
                    class="flex items-center gap-3 cursor-pointer"
                    @click="goToStep2"
                >
                    <div 
                        class="w-9 h-9 rounded-xl flex items-center justify-center font-mono font-bold text-xs transition"
                        :class="currentStep === 2 ? 'bg-blue-700 text-white shadow-sm' : 'bg-slate-100 text-slate-600'"
                    >
                        2
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Langkah 2</span>
                        <strong class="text-xs text-slate-900">Bukti Pembayaran Pendaftaran</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Container -->
        <main class="max-w-4xl mx-auto px-4 mt-4">
            
            <!-- General Alert / Errors -->
            <div v-if="Object.keys(form.errors).length > 0" class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 space-y-1">
                <strong class="block font-bold">Mohon periksa kembali formulir Anda:</strong>
                <ul class="list-disc list-inside space-y-0.5">
                    <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                </ul>
            </div>

            <!-- STEP 1: BIODATA -->
            <div v-show="currentStep === 1" class="space-y-6">
                
                <!-- Notice Section -->
                <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 sm:p-5 flex items-start gap-3 text-xs text-blue-900">
                    <InformationCircleIcon class="w-5 h-5 text-blue-700 shrink-0 mt-0.5" />
                    <div>
                        <strong class="font-extrabold block mb-0.5">Kemudahan Pendaftaran Tahap Awal:</strong>
                        <p class="text-blue-800 leading-relaxed">
                            Formulir ini hanya memerlukan kelengkapan biodata dan unggahan bukti pembayaran biaya pendaftaran. Berkas fisik pendukung (seperti Kartu Keluarga, Akta Kelahiran, dan Ijazah/SKL SD) <strong>tidak perlu diunggah sekarang</strong> dan dapat dikumpulkan saat wawancara komitmen orang tua sesuai jadwal panitia.
                        </p>
                    </div>
                </div>

                <!-- Bagian 1: Data Calon Santri -->
                <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-7 shadow-xs space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider block">Bagian 1</span>
                            <h2 class="text-sm font-black text-slate-900">Data Calon Murid / Santri</h2>
                        </div>
                        <span class="text-[11px] text-slate-400">* Kolom wajib</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Calon Murid *</label>
                            <input 
                                v-model="form.full_name" 
                                type="text" 
                                placeholder="Contoh: Muhammad Rayhan Pratama"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
                                required
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Panggilan</label>
                            <input 
                                v-model="form.nickname" 
                                type="text" 
                                placeholder="Contoh: Rayhan"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Jenis Kelamin *</label>
                            <div class="flex items-center gap-4 pt-1.5">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" v-model="form.gender" value="L" class="text-blue-700 focus:ring-blue-600" />
                                    <span>Laki-laki</span>
                                </label>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" v-model="form.gender" value="P" class="text-blue-700 focus:ring-blue-600" />
                                    <span>Perempuan</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tempat Lahir *</label>
                            <input 
                                v-model="form.birth_place" 
                                type="text" 
                                placeholder="Contoh: Probolinggo"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
                                required
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tanggal Lahir *</label>
                            <input 
                                v-model="form.birth_date" 
                                type="date" 
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
                                required
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">NIK Santri (16 digit)</label>
                            <input 
                                v-model="form.nik" 
                                type="text" 
                                maxlength="16"
                                placeholder="Sesuai Kartu Keluarga"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">NISN (Nomor Induk Siswa Nasional)</label>
                            <input 
                                v-model="form.nisn" 
                                type="text" 
                                maxlength="20"
                                placeholder="Nomor NISN dari SD/MI"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Asal Sekolah Dasar (SD/MI) *</label>
                            <input 
                                v-model="form.previous_school" 
                                type="text" 
                                placeholder="Contoh: SD Namira Kota Probolinggo / SDN Sukabumi 1"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
                                required
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Anak ke-</label>
                            <input 
                                v-model="form.child_order" 
                                type="number" 
                                min="1"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Dari jumlah saudara</label>
                            <input 
                                v-model="form.total_siblings" 
                                type="number" 
                                min="1"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Catatan Khusus / Riwayat Kesehatan / Prestasi</label>
                            <textarea 
                                v-model="form.special_notes" 
                                rows="2"
                                placeholder="Misal: Riwayat alergi, hafalan Al-Qur'an (Juz 30), dll."
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Bagian 2: Data Orang Tua & Akun Login -->
                <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-7 shadow-xs space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider block">Bagian 2</span>
                            <h2 class="text-sm font-black text-slate-900">Data Orang Tua / Wali & Akun Portal</h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Ayah</label>
                            <input 
                                v-model="form.father_name" 
                                type="text" 
                                placeholder="Nama lengkap Ayah"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Pekerjaan Ayah</label>
                            <input 
                                v-model="form.father_job" 
                                type="text" 
                                placeholder="Contoh: PNS / Wiraswasta / BUMN"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Ibu</label>
                            <input 
                                v-model="form.mother_name" 
                                type="text" 
                                placeholder="Nama lengkap Ibu"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Pekerjaan Ibu</label>
                            <input 
                                v-model="form.mother_job" 
                                type="text" 
                                placeholder="Contoh: Ibu Rumah Tangga / Guru"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
                            />
                        </div>

                        <div class="sm:col-span-2 pt-2 border-t border-slate-100">
                            <label class="block font-bold text-slate-700 mb-1">Nomor WhatsApp Aktif (Akun Utama Portal) *</label>
                            <input 
                                v-model="form.parent_phone" 
                                type="text" 
                                placeholder="Contoh: 081234567890"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
                                required
                            />
                            <p class="text-[11px] text-slate-500 mt-1">Nomor ini digunakan sebagai identitas login untuk memeriksa pengumuman dan mengunduh berkas.</p>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kata Sandi Akun (Password) *</label>
                            <input 
                                v-model="form.password" 
                                type="password" 
                                placeholder="Minimal 6 karakter"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
                                required
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Konfirmasi Kata Sandi *</label>
                            <input 
                                v-model="form.password_confirmation" 
                                type="password" 
                                placeholder="Ketik ulang kata sandi"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
                                required
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Alamat Tempat Tinggal Lengkap *</label>
                            <textarea 
                                v-model="form.address" 
                                rows="2"
                                placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
                                required
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="flex items-center justify-end">
                    <button 
                        type="button" 
                        @click="goToStep2"
                        class="inline-flex items-center gap-2 bg-blue-700 hover:bg-blue-800 text-white font-bold py-3 px-6 rounded-xl text-xs transition shadow-sm"
                    >
                        <span>Lanjut ke Langkah 2 (Pembayaran)</span>
                        <ArrowRightIcon class="w-4 h-4 text-amber-300" />
                    </button>
                </div>

            </div>

            <!-- STEP 2: PEMBAYARAN BUKTI DAFTAR -->
            <div v-show="currentStep === 2" class="space-y-6">
                
                <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-7 shadow-xs space-y-5">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider block">Langkah 2</span>
                            <h2 class="text-sm font-black text-slate-900">Pembayaran Biaya Pendaftaran SMP</h2>
                        </div>
                    </div>

                    <!-- Payment Details Card -->
                    <div class="p-4 sm:p-5 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-3">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                            <span class="text-slate-600">Biaya Formulir & Seleksi:</span>
                            <strong class="text-base font-black text-blue-700">
                                {{ setting?.registration_fee ? formatRupiah(setting.registration_fee) : 'Sesuai Konfigurasi Panitia' }}
                            </strong>
                        </div>

                        <div class="space-y-1.5 text-slate-700">
                            <div>Bank Tujuan: <strong>{{ setting?.bank_name || 'Bank Jatim' }}</strong></div>
                            <div>Nomor Rekening: <strong class="font-mono text-slate-900">{{ setting?.bank_account_number || '0291008899' }}</strong></div>
                            <div>Atas Nama: <strong>{{ setting?.bank_account_holder || 'YAYASAN NAMIRA PROBOLINGGO' }}</strong></div>
                            <div v-if="setting?.contact_whatsapp" class="pt-1 text-[11px] text-slate-500">
                                Konfirmasi WhatsApp Panitia: <strong>{{ setting.contact_whatsapp }}</strong>
                            </div>
                        </div>

                        <div v-if="setting?.qris_image_url" class="pt-3 border-t border-slate-200 flex flex-col items-center">
                            <span class="text-[11px] font-bold text-slate-500 uppercase mb-2">Scan QRIS Pendaftaran:</span>
                            <img :src="setting.qris_image_url" alt="QRIS Pendaftaran" class="w-48 h-48 object-contain border rounded-xl p-2 bg-white" />
                        </div>
                    </div>

                    <!-- Upload Box -->
                    <div class="space-y-2">
                        <label class="block font-bold text-slate-800 text-xs">
                            Unggah Bukti Transfer / Pembayaran Pendaftaran *
                        </label>
                        <p class="text-[11px] text-slate-500">
                            Format yang didukung: JPG, PNG, atau PDF (maksimal 10MB).
                        </p>

                        <div class="mt-2 border-2 border-dashed border-slate-300 rounded-2xl p-6 text-center hover:border-blue-500 transition bg-slate-50/50">
                            <input 
                                type="file" 
                                id="payment_proof_input"
                                accept="image/jpeg,image/png,application/pdf"
                                class="hidden"
                                @change="onPaymentProofChange"
                            />
                            <label for="payment_proof_input" class="cursor-pointer flex flex-col items-center justify-center">
                                <DocumentTextIcon class="w-10 h-10 text-blue-600 mb-2" />
                                <span class="text-xs font-bold text-blue-700 hover:underline">
                                    {{ fileNamePaymentProof ? 'Ganti Bukti Bayar' : 'Klik untuk Memilih File Bukti Bayar' }}
                                </span>
                                <span v-if="fileNamePaymentProof" class="text-xs font-mono font-bold text-slate-800 mt-1">
                                    {{ fileNamePaymentProof }}
                                </span>
                                <span v-else class="text-[11px] text-slate-400 mt-0.5">
                                    Pastikan struk transfer atau bukti QRIS terlihat jelas
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Navigation Controls -->
                <div class="flex items-center justify-between">
                    <button 
                        type="button" 
                        @click="currentStep = 1"
                        class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3 px-5 rounded-xl text-xs transition border border-slate-200"
                    >
                        <ArrowLeftIcon class="w-4 h-4" />
                        <span>Kembali ke Biodata</span>
                    </button>

                    <button 
                        type="button" 
                        :disabled="form.processing || isCompressing"
                        @click="submitRegistration"
                        class="inline-flex items-center gap-2 bg-blue-700 hover:bg-blue-800 text-white font-bold py-3 px-6 rounded-xl text-xs transition shadow-sm disabled:opacity-50"
                    >
                        <CheckCircleIcon class="w-4 h-4 text-amber-300" />
                        <span>{{ form.processing ? 'Menyimpan Pendaftaran...' : 'Kirim Pendaftaran Sekarang' }}</span>
                    </button>
                </div>

            </div>

        </main>
    </div>
</template>
