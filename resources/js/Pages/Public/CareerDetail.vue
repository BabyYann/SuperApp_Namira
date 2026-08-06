<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    BriefcaseIcon, SparklesIcon, BuildingOfficeIcon, ClockIcon, 
    AcademicCapIcon, CheckCircleIcon, UserGroupIcon, MapPinIcon,
    ArrowLeftIcon, DocumentTextIcon, PaperClipIcon, CheckIcon,
    ExclamationCircleIcon, UserIcon, EnvelopeIcon, PhoneIcon
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    vacancy: Object,
    otherVacancies: Array,
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    gender: 'L',
    birth_place: '',
    birth_date: '',
    address: '',
    last_education: 'S1',
    major: '',
    institution: '',
    gpa: '',
    cv: null,
    cover_letter: null,
    certificate: null,
    ktp: null,
    photo: null,
    notes: '',
});

const cvFileName = ref('');
const coverLetterFileName = ref('');
const certificateFileName = ref('');
const ktpFileName = ref('');
const photoFileName = ref('');

const handleCvChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.cv = file;
        cvFileName.value = file.name;
    }
};

const handleCoverLetterChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.cover_letter = file;
        coverLetterFileName.value = file.name;
    }
};

const handleCertificateChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.certificate = file;
        certificateFileName.value = file.name;
    }
};

const handleKtpChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.ktp = file;
        ktpFileName.value = file.name;
    }
};

const handlePhotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        photoFileName.value = file.name;
    }
};

const submitApplication = () => {
    form.post(route('careers.apply', props.vacancy.slug), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            cvFileName.value = '';
            coverLetterFileName.value = '';
            certificateFileName.value = '';
            ktpFileName.value = '';
            photoFileName.value = '';
        }
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'Tanpa Batas Waktu';
    return dayjs(dateStr).format('DD MMMM YYYY');
};
</script>

<template>
    <Head :title="`${vacancy.title} - Karir Yayasan Namira`" />

    <div class="min-h-screen bg-slate-50 text-slate-800 font-sans selection:bg-teal-500 selection:text-white flex flex-col">
        <!-- Top Navbar -->
        <nav class="bg-white/90 backdrop-blur-md sticky top-0 z-40 border-b border-slate-100 shadow-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-3">
                    <div class="bg-white p-1 rounded-xl shadow-xs border border-slate-100 flex items-center justify-center shrink-0 w-10 h-10 overflow-hidden">
                        <img :src="$page.props.app_settings?.app_logo || '/images/landing/logo-yayasan.webp'" alt="Logo Namira" class="w-full h-full object-contain" />
                    </div>
                    <div class="flex flex-col select-none leading-none">
                        <span class="font-namira text-2xl text-[#1a4373] tracking-tight lowercase text-stroke-white drop-shadow-xs">namira</span>
                        <span class="font-school text-xs text-slate-500 tracking-wider uppercase text-stroke-white-sm -mt-1 font-bold">SCHOOL</span>
                    </div>
                </Link>

                <div class="flex items-center gap-6 text-sm font-bold">
                    <Link :href="route('careers.index')" class="text-slate-600 hover:text-namira-teal transition-colors flex items-center gap-1.5">
                        <ArrowLeftIcon class="w-4 h-4" />
                        <span>Kembali ke Katalog Karir</span>
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">
            
            <!-- Success Alert Banner -->
            <div v-if="$page.props.flash.success" class="p-5 bg-emerald-50 border border-emerald-200 rounded-3xl text-emerald-900 flex items-start gap-3 shadow-xs">
                <CheckCircleIcon class="w-6 h-6 text-emerald-600 shrink-0 mt-0.5" />
                <div class="space-y-1">
                    <h4 class="font-extrabold text-sm text-emerald-950">Pendaftaran Lamaran Berhasil Terkirim!</h4>
                    <p class="text-xs text-emerald-800 leading-relaxed">{{ $page.props.flash.success }}</p>
                </div>
            </div>

            <!-- Header Info -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-xs space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-6">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="px-3.5 py-1 bg-teal-50 text-teal-700 border border-teal-100 rounded-full font-extrabold text-xs uppercase tracking-wide">
                                {{ vacancy.unit?.name || 'Yayasan Namira' }}
                            </span>
                            <span class="px-3 py-1 bg-slate-100 text-slate-700 rounded-full font-bold text-xs">
                                {{ vacancy.type_label }}
                            </span>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                            {{ vacancy.title }}
                        </h1>
                    </div>

                    <div class="text-left sm:text-right shrink-0">
                        <div class="text-xs text-slate-400 font-bold uppercase tracking-wider">Batas Akhir Pendaftaran</div>
                        <div class="text-sm font-extrabold text-amber-700 font-mono mt-0.5">{{ formatDate(vacancy.deadline) }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs font-semibold">
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <div class="text-slate-400 text-[10px] uppercase font-bold">Kategori Posisi</div>
                        <div class="text-slate-900 font-extrabold mt-1">{{ vacancy.category_label }}</div>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <div class="text-slate-400 text-[10px] uppercase font-bold">Kuota Diterima</div>
                        <div class="text-slate-900 font-extrabold mt-1">{{ vacancy.quota }} Orang</div>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <div class="text-slate-400 text-[10px] uppercase font-bold">Tipe Pekerjaan</div>
                        <div class="text-slate-900 font-extrabold mt-1">{{ vacancy.type_label }}</div>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <div class="text-slate-400 text-[10px] uppercase font-bold">Dilihat Pelamar</div>
                        <div class="text-slate-900 font-extrabold mt-1">{{ vacancy.views_count }}x Dilihat</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Details & Requirements -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-6">
                        <div>
                            <h3 class="font-extrabold text-base text-slate-900 border-b border-slate-100 pb-3 mb-3">Deskripsi Pekerjaan</h3>
                            <div class="text-xs text-slate-600 whitespace-pre-line leading-relaxed">{{ vacancy.description }}</div>
                        </div>

                        <div>
                            <h3 class="font-extrabold text-base text-slate-900 border-b border-slate-100 pb-3 mb-3">Kualifikasi & Persyaratan</h3>
                            <div class="text-xs text-slate-600 whitespace-pre-line leading-relaxed">{{ vacancy.requirements }}</div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Online Application Form -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-xs space-y-6">
                        <div class="border-b border-slate-100 pb-4">
                            <h3 class="text-xl font-black text-slate-900">Formulir Pendaftaran Pelamar</h3>
                            <p class="text-xs text-slate-500 mt-1">Lengkapi data diri dan berkas persyaratan lamaran kerja Anda dengan benar.</p>
                        </div>

                        <form @submit.prevent="submitApplication" class="space-y-6">
                            <!-- Data Diri -->
                            <div class="space-y-4">
                                <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider bg-slate-100 px-3 py-1.5 rounded-xl inline-block">1. Data Diri Pelamar</h4>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                                        <input v-model="form.name" type="text" required placeholder="Sesuai KTP & Ijazah" class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20" />
                                        <div v-if="form.errors.name" class="text-[10px] text-rose-500 mt-1 font-bold">{{ form.errors.name }}</div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Kelamin <span class="text-rose-500">*</span></label>
                                        <select v-model="form.gender" class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20">
                                            <option value="L">Laki-Laki</option>
                                            <option value="P">Perempuan</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Email Aktif <span class="text-rose-500">*</span></label>
                                        <input v-model="form.email" type="email" required placeholder="contoh@gmail.com" class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20" />
                                        <div v-if="form.errors.email" class="text-[10px] text-rose-500 mt-1 font-bold">{{ form.errors.email }}</div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp <span class="text-rose-500">*</span></label>
                                        <input v-model="form.phone" type="text" required placeholder="081234567890" class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20" />
                                        <div v-if="form.errors.phone" class="text-[10px] text-rose-500 mt-1 font-bold">{{ form.errors.phone }}</div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Tempat Lahir <span class="text-rose-500">*</span></label>
                                        <input v-model="form.birth_place" type="text" required placeholder="Kota Kelahiran" class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20" />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Lahir <span class="text-rose-500">*</span></label>
                                        <input v-model="form.birth_date" type="date" required class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20" />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Lengkap <span class="text-rose-500">*</span></label>
                                    <textarea v-model="form.address" rows="2" required placeholder="Alamat domisili lengkap..." class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"></textarea>
                                </div>
                            </div>

                            <!-- Pendidikan -->
                            <div class="space-y-4 pt-2">
                                <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider bg-slate-100 px-3 py-1.5 rounded-xl inline-block">2. Pendidikan Terakhir</h4>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Jenjang Pendidikan <span class="text-rose-500">*</span></label>
                                        <select v-model="form.last_education" class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20">
                                            <option value="SMA">SMA / SMK / MA</option>
                                            <option value="D3">Diploma (D3)</option>
                                            <option value="S1">Sarjana (S1)</option>
                                            <option value="S2">Magister (S2)</option>
                                            <option value="S3">Doktor (S3)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Jurusan / Program Studi <span class="text-rose-500">*</span></label>
                                        <input v-model="form.major" type="text" required placeholder="Contoh: Pendidikan Bahasa Inggris" class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20" />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Universitas / Sekolah <span class="text-rose-500">*</span></label>
                                        <input v-model="form.institution" type="text" required placeholder="Nama Kampus / Sekolah" class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20" />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">IPK / Nilai Rata-Rata</label>
                                        <input v-model="form.gpa" type="text" placeholder="Contoh: 3.75" class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20" />
                                    </div>
                                </div>
                            </div>

                            <!-- Unggah Berkas PDF -->
                            <div class="space-y-4 pt-2">
                                <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider bg-slate-100 px-3 py-1.5 rounded-xl inline-block">3. Unggah Berkas Requirements</h4>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- CV -->
                                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-2">
                                        <label class="block text-xs font-extrabold text-slate-900">File CV / Resume (PDF) <span class="text-rose-500">*</span></label>
                                        <input type="file" @change="handleCvChange" accept=".pdf" class="hidden" id="cv-upload" />
                                        <label for="cv-upload" class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer w-full justify-center">
                                            <PaperClipIcon class="w-4 h-4 text-teal-600" />
                                            <span>{{ cvFileName || 'Pilih File CV (PDF max 5MB)' }}</span>
                                        </label>
                                        <div v-if="form.errors.cv" class="text-[10px] text-rose-500 font-bold">{{ form.errors.cv }}</div>
                                    </div>

                                    <!-- Surat Lamaran -->
                                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-2">
                                        <label class="block text-xs font-extrabold text-slate-900">Surat Lamaran (PDF)</label>
                                        <input type="file" @change="handleCoverLetterChange" accept=".pdf" class="hidden" id="cover-upload" />
                                        <label for="cover-upload" class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer w-full justify-center">
                                            <PaperClipIcon class="w-4 h-4 text-teal-600" />
                                            <span>{{ coverLetterFileName || 'Pilih Surat Lamaran (PDF)' }}</span>
                                        </label>
                                    </div>

                                    <!-- Ijazah -->
                                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-2">
                                        <label class="block text-xs font-extrabold text-slate-900">Ijazah & Transkrip (PDF)</label>
                                        <input type="file" @change="handleCertificateChange" accept=".pdf" class="hidden" id="cert-upload" />
                                        <label for="cert-upload" class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer w-full justify-center">
                                            <PaperClipIcon class="w-4 h-4 text-teal-600" />
                                            <span>{{ certificateFileName || 'Pilih Ijazah/Transkrip (PDF)' }}</span>
                                        </label>
                                    </div>

                                    <!-- KTP -->
                                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-2">
                                        <label class="block text-xs font-extrabold text-slate-900">Scan KTP (PDF/Gambar)</label>
                                        <input type="file" @change="handleKtpChange" accept=".pdf,image/*" class="hidden" id="ktp-upload" />
                                        <label for="ktp-upload" class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer w-full justify-center">
                                            <PaperClipIcon class="w-4 h-4 text-teal-600" />
                                            <span>{{ ktpFileName || 'Pilih KTP (PDF/Gambar)' }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Catatan tambahan -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan / Portofolio (Opsional)</label>
                                <textarea v-model="form.notes" rows="3" placeholder="Tuliskan pengalaman relevan atau tautan portofolio Anda..." class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"></textarea>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-4">
                                <button 
                                    type="submit" 
                                    :disabled="form.processing"
                                    class="w-full py-4 bg-gradient-to-r from-namira-teal to-teal-700 text-white rounded-2xl font-black text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                                >
                                    <CheckIcon class="w-5 h-5" />
                                    <span>{{ form.processing ? 'Mengirim Pendaftaran...' : 'Kirim Pendaftaran Lamaran Kerja' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
