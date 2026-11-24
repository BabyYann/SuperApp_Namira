<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { compressImageFile } from '@/Utils/imageCompressor';
import {
    CheckCircleIcon,
    MapPinIcon,
    ArrowLeftIcon,
    PaperClipIcon,
    CheckIcon,
    PhoneIcon,
    MagnifyingGlassPlusIcon,
    XMarkIcon,
    ArrowDownTrayIcon,
    PhotoIcon,
    ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    vacancy: Object,
    otherVacancies: Array,
});

const lightboxOpen = ref(false);
const isCompressingPhoto = ref(false);
const photoPreview = ref(null);

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
    health_cert: null,
    ktp: null,
    family_card: null,
    photo: null,
    notes: '',
});

const cvFileName = ref('');
const coverLetterFileName = ref('');
const certificateFileName = ref('');
const healthCertFileName = ref('');
const ktpFileName = ref('');
const familyCardFileName = ref('');
const photoFileName = ref('');

const handleFileField = async (e, field, fileNameRef, compressIfImage = false) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (compressIfImage && file.type && file.type.startsWith('image/')) {
        try {
            if (field === 'photo') isCompressingPhoto.value = true;
            const compressed = await compressImageFile(file, 1280, 0.8);
            form[field] = compressed;
            fileNameRef.value = compressed.name;
            if (field === 'photo') {
                photoPreview.value = URL.createObjectURL(compressed);
            }
        } catch {
            form[field] = file;
            fileNameRef.value = file.name;
            if (field === 'photo') {
                photoPreview.value = URL.createObjectURL(file);
            }
        } finally {
            if (field === 'photo') isCompressingPhoto.value = false;
        }
    } else {
        form[field] = file;
        fileNameRef.value = file.name;
        if (field === 'photo') {
            photoPreview.value = URL.createObjectURL(file);
        }
    }
};

const unitWhatsappHref = computed(() => {
    const raw = String(props.vacancy?.contact_whatsapp || '').replace(/[^0-9]/g, '');
    if (!raw) return null;
    const intl = raw.startsWith('0') ? '62' + raw.slice(1) : raw;
    const text = `Assalamu'alaikum Admin ${props.vacancy?.unit?.name || 'Sekolah Namira'}, saya ingin bertanya terkait lowongan *${props.vacancy?.title}*.`;
    return `https://wa.me/${intl}?text=${encodeURIComponent(text)}`;
});

const submitApplication = () => {
    form.post(route('careers.apply', props.vacancy.slug), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            cvFileName.value = '';
            coverLetterFileName.value = '';
            certificateFileName.value = '';
            healthCertFileName.value = '';
            ktpFileName.value = '';
            familyCardFileName.value = '';
            photoFileName.value = '';
            photoPreview.value = null;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        onError: () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'Tanpa Batas Waktu';
    return dayjs(dateStr).format('DD MMMM YYYY');
};
</script>

<template>
    <Head :title="`${vacancy.title} - Karir Namira School`" />

    <div class="min-h-screen bg-slate-50 text-slate-800 font-sans selection:bg-teal-500 selection:text-white flex flex-col">
        <!-- Top Navbar -->
        <nav class="bg-white/95 backdrop-blur-md sticky top-0 z-40 border-b border-slate-100 shadow-2xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between gap-3">
                <Link href="/" class="flex items-center gap-2.5 shrink-0">
                    <div class="bg-white p-1 rounded-xl shadow-xs border border-slate-100 flex items-center justify-center shrink-0 w-9 h-9 sm:w-10 sm:h-10 overflow-hidden">
                        <img :src="$page.props.app_settings?.app_logo || '/images/landing/logo-yayasan.webp'" alt="Logo Namira" class="w-full h-full object-contain" />
                    </div>
                    <div class="flex items-baseline gap-1 select-none whitespace-nowrap">
                        <span class="font-namira text-xl sm:text-2xl text-[#1a4373] tracking-tight lowercase leading-none">namira</span>
                        <span class="font-school text-xs sm:text-base text-slate-500 tracking-wider uppercase font-extrabold leading-none">SCHOOL</span>
                    </div>
                </Link>

                <Link :href="route('careers.index')" class="text-slate-600 hover:text-namira-teal transition-colors flex items-center gap-1.5 text-xs sm:text-sm font-bold">
                    <ArrowLeftIcon class="w-4 h-4" />
                    <span class="hidden sm:inline">Kembali ke Katalog Karir</span>
                    <span class="sm:hidden">Katalog Karir</span>
                </Link>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-10 space-y-6 sm:space-y-8">
            <!-- Success Alert Banner -->
            <div v-if="$page.props.flash?.success" class="p-4 sm:p-5 bg-emerald-50 border border-emerald-200 rounded-3xl text-emerald-900 flex items-start gap-3 shadow-xs">
                <CheckCircleIcon class="w-6 h-6 text-emerald-600 shrink-0 mt-0.5" />
                <div class="space-y-1">
                    <h4 class="font-extrabold text-sm text-emerald-950">Pendaftaran Lamaran Berhasil Terkirim!</h4>
                    <p class="text-xs text-emerald-800 leading-relaxed">{{ $page.props.flash.success }}</p>
                </div>
            </div>

            <!-- Header Info -->
            <div class="bg-white rounded-3xl p-5 sm:p-8 border border-slate-200 shadow-2xs space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-3.5 py-1 bg-teal-50 text-teal-700 border border-teal-100 rounded-full font-extrabold text-[11px] uppercase tracking-wide">
                                {{ vacancy.unit?.name || 'Yayasan Namira' }}
                            </span>
                            <span class="px-3 py-1 bg-slate-100 text-slate-700 rounded-full font-bold text-[11px]">
                                {{ vacancy.type_label }}
                            </span>
                        </div>

                        <h1 class="text-xl sm:text-3xl font-black text-slate-900 leading-tight">
                            {{ vacancy.title }}
                        </h1>
                    </div>

                    <div class="text-left sm:text-right shrink-0">
                        <div class="text-[11px] text-slate-400 font-bold uppercase tracking-wider">Batas Akhir Pendaftaran</div>
                        <div class="text-sm font-extrabold text-amber-700 font-mono mt-0.5">{{ formatDate(vacancy.deadline) }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs font-semibold">
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100">
                        <div class="text-slate-400 text-[10px] uppercase font-bold">Kategori Posisi</div>
                        <div class="text-slate-900 font-extrabold mt-1">{{ vacancy.category_label }}</div>
                    </div>
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100">
                        <div class="text-slate-400 text-[10px] uppercase font-bold">Kuota Kebutuhan</div>
                        <div class="text-slate-900 font-extrabold mt-1">{{ vacancy.quota }} Orang</div>
                    </div>
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100">
                        <div class="text-slate-400 text-[10px] uppercase font-bold">Tipe Pekerjaan</div>
                        <div class="text-slate-900 font-extrabold mt-1">{{ vacancy.type_label }}</div>
                    </div>
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100">
                        <div class="text-slate-400 text-[10px] uppercase font-bold">Dilihat Pelamar</div>
                        <div class="text-slate-900 font-extrabold mt-1">{{ vacancy.views_count }}x Dilihat</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8 items-start">
                <!-- Left Column: Poster Showcase + Description + Unit Contact -->
                <div class="lg:col-span-5 space-y-5">
                    <!-- Poster Card (Full Portrait/Square/Landscape Support) -->
                    <div v-if="vacancy.poster_url" class="bg-white rounded-3xl p-4 border border-slate-200 shadow-2xs space-y-3">
                        <div class="relative rounded-2xl overflow-hidden bg-slate-900 flex items-center justify-center group">
                            <img
                                :src="vacancy.poster_url"
                                :alt="`Poster ${vacancy.title}`"
                                @click="lightboxOpen = true"
                                class="w-full h-auto max-h-[640px] object-contain cursor-zoom-in"
                            />
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="lightboxOpen = true"
                                class="flex-1 py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold inline-flex items-center justify-center gap-1.5 transition cursor-pointer"
                            >
                                <MagnifyingGlassPlusIcon class="w-4 h-4 text-teal-700" />
                                <span>Perbesar Poster</span>
                            </button>
                            <a
                                :href="vacancy.poster_url"
                                download
                                target="_blank"
                                class="py-2.5 px-3.5 rounded-xl bg-teal-50 hover:bg-teal-100 text-teal-800 border border-teal-200 text-xs font-bold inline-flex items-center gap-1.5 transition"
                            >
                                <ArrowDownTrayIcon class="w-4 h-4" />
                                <span>Unduh</span>
                            </a>
                        </div>
                    </div>

                    <!-- Location & Unit Admin WhatsApp -->
                    <div v-if="vacancy.placement_address || unitWhatsappHref" class="bg-white rounded-3xl p-5 border border-slate-200 shadow-2xs space-y-3">
                        <div v-if="vacancy.placement_address" class="flex items-start gap-2.5 text-xs">
                            <MapPinIcon class="w-4 h-4 text-teal-600 shrink-0 mt-0.5" />
                            <div>
                                <div class="text-[10px] font-extrabold uppercase text-slate-400">Lokasi / Pengiriman Berkas</div>
                                <div class="font-bold text-slate-800 mt-0.5">{{ vacancy.placement_address }}</div>
                            </div>
                        </div>

                        <a
                            v-if="unitWhatsappHref"
                            :href="unitWhatsappHref"
                            target="_blank"
                            class="w-full py-3 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold inline-flex items-center justify-center gap-2 transition shadow-xs"
                        >
                            <ChatBubbleLeftRightIcon class="w-4 h-4" />
                            <span>Hubungi Admin Unit ({{ vacancy.contact_whatsapp }})</span>
                        </a>
                    </div>

                    <!-- Text Description & Requirements (When Provided) -->
                    <div v-if="vacancy.description || vacancy.requirements" class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200 shadow-2xs space-y-6">
                        <div v-if="vacancy.description">
                            <h3 class="font-extrabold text-base text-slate-900 border-b border-slate-100 pb-3 mb-3">Deskripsi Pekerjaan</h3>
                            <div class="text-xs text-slate-600 whitespace-pre-line leading-relaxed">{{ vacancy.description }}</div>
                        </div>

                        <div v-if="vacancy.requirements">
                            <h3 class="font-extrabold text-base text-slate-900 border-b border-slate-100 pb-3 mb-3">Kualifikasi &amp; Persyaratan</h3>
                            <div class="text-xs text-slate-600 whitespace-pre-line leading-relaxed">{{ vacancy.requirements }}</div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Online Application Form -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="bg-white rounded-3xl p-5 sm:p-8 border border-slate-200 shadow-2xs space-y-6">
                        <div class="border-b border-slate-100 pb-4">
                            <h3 class="text-lg sm:text-xl font-black text-slate-900">Formulir Pendaftaran Pelamar</h3>
                            <p class="text-xs text-slate-500 mt-1">Lengkapi data diri dan unggah berkas persyaratan lamaran kerja Anda secara online.</p>
                        </div>

                        <!-- Validation Error Summary -->
                        <div v-if="Object.keys(form.errors).length > 0" class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800 space-y-1">
                            <strong class="block font-extrabold">Mohon periksa kembali isian formulir berikut:</strong>
                            <ul class="list-disc list-inside space-y-0.5">
                                <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                            </ul>
                        </div>

                        <form @submit.prevent="submitApplication" class="space-y-6">
                            <!-- 1. Data Diri -->
                            <div class="space-y-4">
                                <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider bg-slate-100 px-3 py-1.5 rounded-xl inline-block">1. Data Diri Pelamar</h4>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                                        <input v-model="form.name" type="text" required placeholder="Sesuai KTP & Ijazah" class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20" />
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
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp <span class="text-rose-500">*</span></label>
                                        <input v-model="form.phone" type="tel" required placeholder="081234567890" class="w-full rounded-2xl border-slate-200 text-xs font-mono font-semibold focus:border-namira-teal focus:ring-namira-teal/20" />
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

                            <!-- 2. Pendidikan -->
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
                                        <input v-model="form.major" type="text" required placeholder="Contoh: Pendidikan Jasmani / PGSD" class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20" />
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

                            <!-- 3. Unggah Berkas Persyaratan -->
                            <div class="space-y-4 pt-2">
                                <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider bg-slate-100 px-3 py-1.5 rounded-xl inline-block">3. Unggah Berkas Persyaratan</h4>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                    <!-- CV -->
                                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 space-y-2">
                                        <label class="block text-xs font-extrabold text-slate-900">CV / Resume (PDF/Gambar) <span class="text-rose-500">*</span></label>
                                        <input type="file" @change="(e) => handleFileField(e, 'cv', cvFileName)" accept=".pdf,image/*" class="hidden" id="cv-upload" />
                                        <label for="cv-upload" class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer w-full justify-center truncate">
                                            <PaperClipIcon class="w-4 h-4 text-teal-600 shrink-0" />
                                            <span class="truncate">{{ cvFileName || 'Pilih File CV (maks 5MB)' }}</span>
                                        </label>
                                    </div>

                                    <!-- Surat Lamaran -->
                                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 space-y-2">
                                        <label class="block text-xs font-extrabold text-slate-900">Surat Lamaran (PDF/Gambar)</label>
                                        <input type="file" @change="(e) => handleFileField(e, 'cover_letter', coverLetterFileName)" accept=".pdf,image/*" class="hidden" id="cover-upload" />
                                        <label for="cover-upload" class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer w-full justify-center truncate">
                                            <PaperClipIcon class="w-4 h-4 text-teal-600 shrink-0" />
                                            <span class="truncate">{{ coverLetterFileName || 'Pilih Surat Lamaran' }}</span>
                                        </label>
                                    </div>

                                    <!-- Ijazah -->
                                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 space-y-2">
                                        <label class="block text-xs font-extrabold text-slate-900">FC Ijazah &amp; Transkrip (PDF/Gambar)</label>
                                        <input type="file" @change="(e) => handleFileField(e, 'certificate', certificateFileName)" accept=".pdf,image/*" class="hidden" id="cert-upload" />
                                        <label for="cert-upload" class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer w-full justify-center truncate">
                                            <PaperClipIcon class="w-4 h-4 text-teal-600 shrink-0" />
                                            <span class="truncate">{{ certificateFileName || 'Pilih Ijazah Terakhir' }}</span>
                                        </label>
                                    </div>

                                    <!-- Surat Keterangan Sehat -->
                                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 space-y-2">
                                        <label class="block text-xs font-extrabold text-slate-900">Surat Keterangan Sehat (PDF/Gambar)</label>
                                        <input type="file" @change="(e) => handleFileField(e, 'health_cert', healthCertFileName)" accept=".pdf,image/*" class="hidden" id="health-upload" />
                                        <label for="health-upload" class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer w-full justify-center truncate">
                                            <PaperClipIcon class="w-4 h-4 text-teal-600 shrink-0" />
                                            <span class="truncate">{{ healthCertFileName || 'Pilih Surat Sehat' }}</span>
                                        </label>
                                    </div>

                                    <!-- Kartu Keluarga (KK) -->
                                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 space-y-2">
                                        <label class="block text-xs font-extrabold text-slate-900">FC Kartu Keluarga / KK (PDF/Gambar)</label>
                                        <input type="file" @change="(e) => handleFileField(e, 'family_card', familyCardFileName, true)" accept=".pdf,image/*" class="hidden" id="kk-upload" />
                                        <label for="kk-upload" class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer w-full justify-center truncate">
                                            <PaperClipIcon class="w-4 h-4 text-teal-600 shrink-0" />
                                            <span class="truncate">{{ familyCardFileName || 'Pilih Scan KK' }}</span>
                                        </label>
                                    </div>

                                    <!-- KTP -->
                                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 space-y-2">
                                        <label class="block text-xs font-extrabold text-slate-900">Scan KTP (PDF/Gambar)</label>
                                        <input type="file" @change="(e) => handleFileField(e, 'ktp', ktpFileName, true)" accept=".pdf,image/*" class="hidden" id="ktp-upload" />
                                        <label for="ktp-upload" class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer w-full justify-center truncate">
                                            <PaperClipIcon class="w-4 h-4 text-teal-600 shrink-0" />
                                            <span class="truncate">{{ ktpFileName || 'Pilih Scan KTP' }}</span>
                                        </label>
                                    </div>

                                    <!-- Pas Foto 3x4 -->
                                    <div class="sm:col-span-2 bg-slate-50 p-3.5 rounded-2xl border border-slate-200 flex items-center gap-3.5">
                                        <div class="w-14 h-16 rounded-xl border border-slate-300 bg-white overflow-hidden flex items-center justify-center shrink-0">
                                            <img v-if="photoPreview" :src="photoPreview" alt="Preview Pas Foto" class="w-full h-full object-cover" />
                                            <PhotoIcon v-else class="w-6 h-6 text-slate-400" />
                                        </div>
                                        <div class="flex-1 min-w-0 space-y-1.5">
                                            <label class="block text-xs font-extrabold text-slate-900">Pas Foto 3x4 (JPG/PNG)</label>
                                            <input type="file" @change="(e) => handleFileField(e, 'photo', photoFileName, true)" accept="image/*" class="hidden" id="photo-upload" />
                                            <label for="photo-upload" class="inline-flex items-center gap-2 px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer max-w-full">
                                                <PhotoIcon class="w-4 h-4 text-teal-600 shrink-0" />
                                                <span class="truncate">{{ isCompressingPhoto ? 'Mengompres...' : (photoFileName || 'Pilih Pas Foto 3x4') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Catatan tambahan -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan / Portofolio (Opsional)</label>
                                <textarea v-model="form.notes" rows="3" placeholder="Tuliskan pengalaman mengajar/kerja relevan atau tautan portofolio Anda..." class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"></textarea>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-2">
                                <button
                                    type="submit"
                                    :disabled="form.processing || isCompressingPhoto"
                                    class="w-full py-4 bg-gradient-to-r from-namira-teal to-teal-700 text-white rounded-2xl font-black text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                                >
                                    <CheckIcon class="w-5 h-5" />
                                    <span>{{ form.processing ? 'Mengirim Lamaran...' : 'Kirim Pendaftaran Lamaran Kerja' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>

        <!-- Full-Screen Poster Lightbox -->
        <Teleport to="body">
            <div
                v-if="lightboxOpen && vacancy.poster_url"
                class="fixed inset-0 z-[120] bg-slate-950/92 backdrop-blur-sm flex flex-col items-center justify-center p-4"
                @click.self="lightboxOpen = false"
            >
                <div class="w-full max-w-2xl flex items-center justify-between text-white mb-3">
                    <h4 class="font-extrabold text-sm truncate pr-4">{{ vacancy.title }}</h4>
                    <button
                        type="button"
                        @click="lightboxOpen = false"
                        class="p-2 rounded-full bg-white/15 hover:bg-white/25 text-white transition cursor-pointer"
                    >
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
                <img
                    :src="vacancy.poster_url"
                    :alt="vacancy.title"
                    class="max-h-[84vh] max-w-full object-contain rounded-2xl shadow-2xl border border-white/15"
                />
            </div>
        </Teleport>
    </div>
</template>
