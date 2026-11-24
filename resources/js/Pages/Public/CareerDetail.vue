<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';
import 'lenis/dist/lenis.css';
import { Motion, AnimatePresence } from 'motion-v';
import { compressImageFile } from '@/Utils/imageCompressor';
import {
    CheckCircleIcon,
    MapPinIcon,
    ArrowLeftIcon,
    ArrowRightIcon,
    PaperClipIcon,
    CheckIcon,
    MagnifyingGlassPlusIcon,
    XMarkIcon,
    ArrowDownTrayIcon,
    PhotoIcon,
    ChatBubbleLeftRightIcon,
    BuildingOffice2Icon,
    UserGroupIcon,
    ClockIcon,
    EyeIcon,
    ShieldCheckIcon,
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

gsap.registerPlugin(ScrollTrigger);
dayjs.locale('id');

const props = defineProps({
    vacancy: Object,
    otherVacancies: {
        type: Array,
        default: () => [],
    },
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

const uploadedDocsCount = computed(() => {
    return [
        cvFileName.value,
        coverLetterFileName.value,
        certificateFileName.value,
        healthCertFileName.value,
        familyCardFileName.value,
        ktpFileName.value,
        photoFileName.value,
    ].filter(Boolean).length;
});

const scrollToTopSmooth = () => {
    if (lenisInstance) {
        lenisInstance.scrollTo(0, { duration: 0.9 });
    } else {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const scrollToForm = () => {
    if (lenisInstance) {
        lenisInstance.scrollTo('#form-lamaran', { offset: -88, duration: 1.0 });
    } else {
        document.querySelector('#form-lamaran')?.scrollIntoView({ behavior: 'smooth' });
    }
};

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
            scrollToTopSmooth();
        },
        onError: () => {
            scrollToForm();
        },
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'Tanpa Batas Waktu';
    return dayjs(dateStr).format('DD MMMM YYYY');
};

// Restrained Lenis + GSAP choreography
const pageRoot = ref(null);
const progressBarRef = ref(null);
let lenisInstance = null;
let lenisRaf = null;
let gsapCtx = null;
let keydownHandler = null;

watch(lightboxOpen, (val) => {
    if (val) {
        lenisInstance?.stop();
    } else {
        lenisInstance?.start();
    }
});

onMounted(() => {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    keydownHandler = (e) => {
        if (e.key === 'Escape' && lightboxOpen.value) {
            lightboxOpen.value = false;
        }
    };
    window.addEventListener('keydown', keydownHandler);

    if (!prefersReduced) {
        lenisInstance = new Lenis({
            duration: 1.0,
            easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            smoothWheel: true,
            wheelMultiplier: 0.95,
            touchMultiplier: 1.35,
        });

        lenisInstance.on('scroll', ScrollTrigger.update);
        lenisRaf = (time) => {
            lenisInstance?.raf(time * 1000);
        };
        gsap.ticker.add(lenisRaf);
        gsap.ticker.lagSmoothing(0);
    }

    gsapCtx = gsap.context(() => {
        if (prefersReduced) return;

        if (progressBarRef.value) {
            gsap.to(progressBarRef.value, {
                scaleX: 1,
                ease: 'none',
                scrollTrigger: {
                    trigger: pageRoot.value,
                    start: 'top top',
                    end: 'bottom bottom',
                    scrub: 0.2,
                },
            });
        }

        gsap.from('.detail-enter', {
            y: 16,
            opacity: 0,
            duration: 0.65,
            stagger: 0.08,
            ease: 'power3.out',
            clearProps: 'transform,opacity',
        });
    }, pageRoot.value);
});

onUnmounted(() => {
    if (keydownHandler) window.removeEventListener('keydown', keydownHandler);
    if (gsapCtx) gsapCtx.revert();
    if (lenisRaf) gsap.ticker.remove(lenisRaf);
    if (lenisInstance) lenisInstance.destroy();
});
</script>

<template>
    <Head :title="`${vacancy.title} — Karir Namira School`" />

    <div
        ref="pageRoot"
        class="min-h-screen bg-[#FAFDFC] text-slate-900 font-sans antialiased selection:bg-[#009688]/15 selection:text-[#006B60] flex flex-col"
    >
        <!-- Subtle Top Scroll Progress Hairline -->
        <div
            ref="progressBarRef"
            aria-hidden="true"
            class="fixed top-0 left-0 right-0 h-[2.5px] bg-[#009688] origin-left scale-x-0 z-50 pointer-events-none"
        ></div>

        <!-- Matching Minimal White Top Navbar -->
        <header class="sticky top-0 z-40 bg-white/92 backdrop-blur-md border-b border-slate-100">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-[72px] flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 shrink-0">
                    <Link href="/" class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-white border border-slate-100 shadow-2xs p-1 flex items-center justify-center shrink-0 overflow-hidden">
                            <img
                                :src="$page.props.app_settings?.app_logo || '/images/landing/logo-yayasan.webp'"
                                alt="Logo Namira"
                                class="w-full h-full object-contain"
                            />
                        </div>
                        <div class="flex items-baseline gap-1 select-none whitespace-nowrap">
                            <span class="font-namira text-xl text-[#1a4373] tracking-tight lowercase leading-none">namira</span>
                            <span class="font-school text-xs text-slate-500 tracking-wider uppercase font-extrabold leading-none">SCHOOL</span>
                        </div>
                    </Link>

                    <span class="h-5 w-px bg-slate-200 hidden sm:inline-block"></span>
                    <Link :href="route('careers.index')" class="hidden sm:inline-block text-[13px] font-semibold text-slate-500 hover:text-[#008C7E] transition-colors">
                        Karir
                    </Link>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="scrollToForm"
                        class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#009688] hover:bg-[#007D71] text-white text-xs font-bold transition-colors cursor-pointer"
                    >
                        <span>Lamar Sekarang</span>
                    </button>
                    <Link
                        :href="route('careers.index')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 hover:border-[#009688] hover:text-[#008C7E] text-slate-700 text-xs font-bold transition-colors"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5" />
                        <span class="hidden sm:inline">Semua Lowongan</span>
                        <span class="sm:hidden">Lowongan</span>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Soft Pastel Mint Editorial Header Wash -->
        <section class="relative bg-[#EDF9F4] border-b border-[#DDF3EA] overflow-hidden">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-11 space-y-6">
                <!-- Breadcrumb -->
                <div class="detail-enter flex items-center gap-2 text-xs font-semibold text-slate-500">
                    <Link :href="route('careers.index')" class="hover:text-[#008C7E] transition-colors">Portal Karir</Link>
                    <span>/</span>
                    <span class="text-slate-800 truncate">{{ vacancy.title }}</span>
                </div>

                <!-- Main Header Row -->
                <div class="detail-enter flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                    <div class="space-y-3 max-w-3xl">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-3 py-1 rounded-full bg-white text-[#007468] border border-[#009688]/20 text-[11px] font-extrabold uppercase tracking-wide shadow-2xs">
                                {{ vacancy.unit?.name || 'Yayasan Namira' }}
                            </span>
                            <span class="px-3 py-1 rounded-full bg-white/80 text-slate-700 border border-slate-200/70 text-[11px] font-bold">
                                {{ vacancy.type_label }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200/70 text-[11px] font-semibold">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Lowongan Terbuka
                            </span>
                        </div>

                        <h1 class="text-2xl sm:text-4xl font-extrabold text-slate-950 tracking-tight leading-tight">
                            {{ vacancy.title }}
                        </h1>
                    </div>

                    <div class="bg-white/95 rounded-2xl border border-[#009688]/15 px-4 py-3 shadow-2xs shrink-0 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                            <ClockIcon class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Batas Pengumpulan</div>
                            <div class="text-sm font-extrabold text-slate-900">{{ formatDate(vacancy.deadline) }}</div>
                        </div>
                    </div>
                </div>

                <!-- 4 Clean Inline Stat Tiles -->
                <div class="detail-enter grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                    <div class="bg-white/90 rounded-2xl border border-white p-3.5 shadow-2xs flex items-start gap-2.5">
                        <BuildingOffice2Icon class="w-4 h-4 text-[#008C7E] shrink-0 mt-0.5" />
                        <div class="min-w-0">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Kategori</div>
                            <div class="text-xs font-extrabold text-slate-900 mt-0.5 truncate">{{ vacancy.category_label }}</div>
                        </div>
                    </div>

                    <div class="bg-white/90 rounded-2xl border border-white p-3.5 shadow-2xs flex items-start gap-2.5">
                        <UserGroupIcon class="w-4 h-4 text-[#008C7E] shrink-0 mt-0.5" />
                        <div class="min-w-0">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Kuota</div>
                            <div class="text-xs font-extrabold text-slate-900 mt-0.5">{{ vacancy.quota }} Orang</div>
                        </div>
                    </div>

                    <div class="bg-white/90 rounded-2xl border border-white p-3.5 shadow-2xs flex items-start gap-2.5">
                        <ShieldCheckIcon class="w-4 h-4 text-[#008C7E] shrink-0 mt-0.5" />
                        <div class="min-w-0">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Status Kerja</div>
                            <div class="text-xs font-extrabold text-slate-900 mt-0.5 truncate">{{ vacancy.type_label }}</div>
                        </div>
                    </div>

                    <div class="bg-white/90 rounded-2xl border border-white p-3.5 shadow-2xs flex items-start gap-2.5">
                        <EyeIcon class="w-4 h-4 text-[#008C7E] shrink-0 mt-0.5" />
                        <div class="min-w-0">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Dilihat</div>
                            <div class="text-xs font-extrabold text-slate-900 mt-0.5">{{ vacancy.views_count }}x Pelamar</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Split Workspace -->
        <main class="flex-1 max-w-6xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-12">
            <!-- Success Flash Banner -->
            <AnimatePresence>
                <Motion
                    v-if="$page.props.flash?.success"
                    :initial="{ opacity: 0, y: -10 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.3 }"
                    class="p-5 rounded-3xl bg-[#E6F7F1] border border-[#009688]/30 text-[#005B52] flex items-start gap-3.5 shadow-xs"
                >
                    <CheckCircleIcon class="w-6 h-6 text-[#008C7E] shrink-0 mt-0.5" />
                    <div class="space-y-1">
                        <h4 class="font-extrabold text-sm text-slate-950">Pendaftaran Lamaran Berhasil Terkirim!</h4>
                        <p class="text-xs sm:text-[13px] text-slate-700 leading-relaxed">{{ $page.props.flash.success }}</p>
                    </div>
                </Motion>
            </AnimatePresence>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- LEFT COLUMN: Poster Showcase, Unit Info, Deskripsi & Kualifikasi -->
                <div class="lg:col-span-5 space-y-5 lg:sticky lg:top-[92px]">
                    <!-- Clean Bright Studio Poster Showcase (Zero Dark Clutter) -->
                    <div
                        v-if="vacancy.poster_url"
                        class="detail-enter bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-[0_12px_34px_-18px_rgba(15,23,42,0.10)] space-y-3.5"
                    >
                        <div class="relative rounded-2xl overflow-hidden bg-[#F2FAF7] border border-slate-100 p-3 flex items-center justify-center group">
                            <img
                                :src="vacancy.poster_url"
                                :alt="`Poster ${vacancy.title}`"
                                @click="lightboxOpen = true"
                                class="w-full h-auto max-h-[600px] object-contain rounded-xl shadow-md ring-1 ring-slate-900/5 transition-transform duration-300 group-hover:scale-[1.01] cursor-zoom-in"
                            />
                        </div>
                        <div class="flex items-center gap-2.5">
                            <button
                                type="button"
                                @click="lightboxOpen = true"
                                class="flex-1 py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200/80 text-slate-800 text-xs font-bold inline-flex items-center justify-center gap-1.5 transition cursor-pointer"
                            >
                                <MagnifyingGlassPlusIcon class="w-4 h-4 text-[#008C7E]" />
                                <span>Perbesar Poster</span>
                            </button>
                            <a
                                :href="vacancy.poster_url"
                                download
                                target="_blank"
                                rel="noopener noreferrer"
                                class="py-2.5 px-4 rounded-xl bg-[#E6F7F1] hover:bg-[#D5F2E8] text-[#007468] border border-[#009688]/20 text-xs font-bold inline-flex items-center gap-1.5 transition"
                            >
                                <ArrowDownTrayIcon class="w-4 h-4" />
                                <span>Unduh</span>
                            </a>
                        </div>
                    </div>

                    <!-- Location & WhatsApp Contact Card -->
                    <div
                        v-if="vacancy.placement_address || unitWhatsappHref"
                        class="detail-enter bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-2xs space-y-4"
                    >
                        <div v-if="vacancy.placement_address" class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-[#E6F7F1] text-[#008C7E] flex items-center justify-center shrink-0">
                                <MapPinIcon class="w-4 h-4" />
                            </div>
                            <div>
                                <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Lokasi / Pengiriman Berkas</div>
                                <div class="text-xs sm:text-[13px] font-bold text-slate-800 mt-0.5 leading-relaxed">
                                    {{ vacancy.placement_address }}
                                </div>
                            </div>
                        </div>

                        <a
                            v-if="unitWhatsappHref"
                            :href="unitWhatsappHref"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="w-full py-3 px-4 rounded-xl bg-[#009688] hover:bg-[#007D71] text-white text-xs font-bold inline-flex items-center justify-center gap-2 transition shadow-2xs"
                        >
                            <ChatBubbleLeftRightIcon class="w-4 h-4" />
                            <span>Hubungi Admin Unit ({{ vacancy.contact_whatsapp }})</span>
                        </a>
                    </div>

                    <!-- Deskripsi & Kualifikasi -->
                    <div
                        v-if="vacancy.description || vacancy.requirements"
                        class="detail-enter bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-2xs space-y-6"
                    >
                        <div v-if="vacancy.description">
                            <h3 class="font-extrabold text-sm sm:text-base text-slate-900 border-b border-slate-100 pb-3 mb-3">
                                Deskripsi Pekerjaan
                            </h3>
                            <div class="text-xs sm:text-[13px] text-slate-600 whitespace-pre-line leading-relaxed">
                                {{ vacancy.description }}
                            </div>
                        </div>

                        <div v-if="vacancy.requirements">
                            <h3 class="font-extrabold text-sm sm:text-base text-slate-900 border-b border-slate-100 pb-3 mb-3">
                                Kualifikasi &amp; Persyaratan
                            </h3>
                            <div class="text-xs sm:text-[13px] text-slate-600 whitespace-pre-line leading-relaxed">
                                {{ vacancy.requirements }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: High-Craft Online Application Form -->
                <div id="form-lamaran" class="lg:col-span-7 detail-enter">
                    <div class="bg-white rounded-3xl p-5 sm:p-8 border border-slate-200/80 shadow-[0_14px_40px_-18px_rgba(15,23,42,0.08)] space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-5">
                            <div>
                                <h2 class="text-lg sm:text-xl font-extrabold text-slate-950 tracking-tight">
                                    Formulir Lamaran Online
                                </h2>
                                <p class="text-xs text-slate-500 mt-1">
                                    Lengkapi data diri dan unggah berkas persyaratan Anda secara daring. Gratis &amp; resmi.
                                </p>
                            </div>
                            <span class="self-start sm:self-auto px-3 py-1 rounded-full bg-[#F2FAF7] text-[#007468] text-[11px] font-bold whitespace-nowrap">
                                {{ uploadedDocsCount }}/7 Berkas Dipilih
                            </span>
                        </div>

                        <!-- Validation Error Summary -->
                        <div
                            v-if="Object.keys(form.errors).length > 0"
                            class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800 space-y-1.5"
                        >
                            <strong class="block font-extrabold">Mohon periksa kembali isian formulir berikut:</strong>
                            <ul class="list-disc list-inside space-y-0.5">
                                <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                            </ul>
                        </div>

                        <form @submit.prevent="submitApplication" class="space-y-7">
                            <!-- 1. Data Diri -->
                            <div class="space-y-4">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-6 h-6 rounded-lg bg-[#E6F7F1] text-[#007468] text-xs font-extrabold flex items-center justify-center">1</span>
                                    <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Data Diri Pelamar</h3>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap <span class="text-rose-500">*</span></label>
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            required
                                            placeholder="Sesuai KTP & Ijazah"
                                            class="w-full rounded-xl border-slate-200 text-xs sm:text-[13px] font-medium focus:border-[#009688] focus:ring-[#009688]/20"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Jenis Kelamin <span class="text-rose-500">*</span></label>
                                        <select
                                            v-model="form.gender"
                                            class="w-full rounded-xl border-slate-200 text-xs sm:text-[13px] font-medium focus:border-[#009688] focus:ring-[#009688]/20"
                                        >
                                            <option value="L">Laki-Laki</option>
                                            <option value="P">Perempuan</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Aktif <span class="text-rose-500">*</span></label>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            required
                                            placeholder="contoh@gmail.com"
                                            class="w-full rounded-xl border-slate-200 text-xs sm:text-[13px] font-medium focus:border-[#009688] focus:ring-[#009688]/20"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1.5">No. WhatsApp <span class="text-rose-500">*</span></label>
                                        <input
                                            v-model="form.phone"
                                            type="tel"
                                            required
                                            placeholder="081234567890"
                                            class="w-full rounded-xl border-slate-200 text-xs sm:text-[13px] font-mono font-medium focus:border-[#009688] focus:ring-[#009688]/20"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Tempat Lahir <span class="text-rose-500">*</span></label>
                                        <input
                                            v-model="form.birth_place"
                                            type="text"
                                            required
                                            placeholder="Kota Kelahiran"
                                            class="w-full rounded-xl border-slate-200 text-xs sm:text-[13px] font-medium focus:border-[#009688] focus:ring-[#009688]/20"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Tanggal Lahir <span class="text-rose-500">*</span></label>
                                        <input
                                            v-model="form.birth_date"
                                            type="date"
                                            required
                                            class="w-full rounded-xl border-slate-200 text-xs sm:text-[13px] font-medium focus:border-[#009688] focus:ring-[#009688]/20"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Lengkap <span class="text-rose-500">*</span></label>
                                    <textarea
                                        v-model="form.address"
                                        rows="2"
                                        required
                                        placeholder="Alamat domisili lengkap..."
                                        class="w-full rounded-xl border-slate-200 text-xs sm:text-[13px] font-medium focus:border-[#009688] focus:ring-[#009688]/20"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- 2. Pendidikan Terakhir -->
                            <div class="space-y-4 pt-3 border-t border-slate-100">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-6 h-6 rounded-lg bg-[#E6F7F1] text-[#007468] text-xs font-extrabold flex items-center justify-center">2</span>
                                    <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Pendidikan Terakhir</h3>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Jenjang Pendidikan <span class="text-rose-500">*</span></label>
                                        <select
                                            v-model="form.last_education"
                                            class="w-full rounded-xl border-slate-200 text-xs sm:text-[13px] font-medium focus:border-[#009688] focus:ring-[#009688]/20"
                                        >
                                            <option value="SMA">SMA / SMK / MA</option>
                                            <option value="D3">Diploma (D3)</option>
                                            <option value="S1">Sarjana (S1)</option>
                                            <option value="S2">Magister (S2)</option>
                                            <option value="S3">Doktor (S3)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Jurusan / Program Studi <span class="text-rose-500">*</span></label>
                                        <input
                                            v-model="form.major"
                                            type="text"
                                            required
                                            placeholder="Contoh: Pendidikan Jasmani / PGSD"
                                            class="w-full rounded-xl border-slate-200 text-xs sm:text-[13px] font-medium focus:border-[#009688] focus:ring-[#009688]/20"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Universitas / Sekolah <span class="text-rose-500">*</span></label>
                                        <input
                                            v-model="form.institution"
                                            type="text"
                                            required
                                            placeholder="Nama Kampus / Sekolah"
                                            class="w-full rounded-xl border-slate-200 text-xs sm:text-[13px] font-medium focus:border-[#009688] focus:ring-[#009688]/20"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1.5">IPK / Nilai Rata-Rata</label>
                                        <input
                                            v-model="form.gpa"
                                            type="text"
                                            placeholder="Contoh: 3.75"
                                            class="w-full rounded-xl border-slate-200 text-xs sm:text-[13px] font-medium focus:border-[#009688] focus:ring-[#009688]/20"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Unggah Berkas Persyaratan (All 7 Documents Intact!) -->
                            <div class="space-y-4 pt-3 border-t border-slate-100">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-6 h-6 rounded-lg bg-[#E6F7F1] text-[#007468] text-xs font-extrabold flex items-center justify-center">3</span>
                                    <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Unggah Berkas Persyaratan</h3>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                    <!-- CV -->
                                    <div
                                        class="p-3.5 rounded-2xl border transition-colors space-y-2"
                                        :class="cvFileName ? 'bg-[#F2FAF7] border-[#009688]/35' : 'bg-[#FAFCFB] border-slate-200/80'"
                                    >
                                        <label class="block text-xs font-bold text-slate-900">
                                            CV / Resume (PDF/Gambar) <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="file" @change="(e) => handleFileField(e, 'cv', cvFileName)" accept=".pdf,image/*" class="hidden" id="cv-upload" />
                                        <label
                                            for="cv-upload"
                                            class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 hover:border-[#009688] rounded-xl text-xs font-semibold text-slate-700 transition cursor-pointer w-full justify-center truncate"
                                        >
                                            <CheckCircleIcon v-if="cvFileName" class="w-4 h-4 text-[#008C7E] shrink-0" />
                                            <PaperClipIcon v-else class="w-4 h-4 text-[#008C7E] shrink-0" />
                                            <span class="truncate">{{ cvFileName || 'Pilih File CV (maks 5MB)' }}</span>
                                        </label>
                                    </div>

                                    <!-- Surat Lamaran -->
                                    <div
                                        class="p-3.5 rounded-2xl border transition-colors space-y-2"
                                        :class="coverLetterFileName ? 'bg-[#F2FAF7] border-[#009688]/35' : 'bg-[#FAFCFB] border-slate-200/80'"
                                    >
                                        <label class="block text-xs font-bold text-slate-900">Surat Lamaran (PDF/Gambar)</label>
                                        <input type="file" @change="(e) => handleFileField(e, 'cover_letter', coverLetterFileName)" accept=".pdf,image/*" class="hidden" id="cover-upload" />
                                        <label
                                            for="cover-upload"
                                            class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 hover:border-[#009688] rounded-xl text-xs font-semibold text-slate-700 transition cursor-pointer w-full justify-center truncate"
                                        >
                                            <CheckCircleIcon v-if="coverLetterFileName" class="w-4 h-4 text-[#008C7E] shrink-0" />
                                            <PaperClipIcon v-else class="w-4 h-4 text-[#008C7E] shrink-0" />
                                            <span class="truncate">{{ coverLetterFileName || 'Pilih Surat Lamaran' }}</span>
                                        </label>
                                    </div>

                                    <!-- Ijazah -->
                                    <div
                                        class="p-3.5 rounded-2xl border transition-colors space-y-2"
                                        :class="certificateFileName ? 'bg-[#F2FAF7] border-[#009688]/35' : 'bg-[#FAFCFB] border-slate-200/80'"
                                    >
                                        <label class="block text-xs font-bold text-slate-900">FC Ijazah &amp; Transkrip (PDF/Gambar)</label>
                                        <input type="file" @change="(e) => handleFileField(e, 'certificate', certificateFileName)" accept=".pdf,image/*" class="hidden" id="cert-upload" />
                                        <label
                                            for="cert-upload"
                                            class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 hover:border-[#009688] rounded-xl text-xs font-semibold text-slate-700 transition cursor-pointer w-full justify-center truncate"
                                        >
                                            <CheckCircleIcon v-if="certificateFileName" class="w-4 h-4 text-[#008C7E] shrink-0" />
                                            <PaperClipIcon v-else class="w-4 h-4 text-[#008C7E] shrink-0" />
                                            <span class="truncate">{{ certificateFileName || 'Pilih Ijazah Terakhir' }}</span>
                                        </label>
                                    </div>

                                    <!-- Surat Keterangan Sehat -->
                                    <div
                                        class="p-3.5 rounded-2xl border transition-colors space-y-2"
                                        :class="healthCertFileName ? 'bg-[#F2FAF7] border-[#009688]/35' : 'bg-[#FAFCFB] border-slate-200/80'"
                                    >
                                        <label class="block text-xs font-bold text-slate-900">Surat Keterangan Sehat (PDF/Gambar)</label>
                                        <input type="file" @change="(e) => handleFileField(e, 'health_cert', healthCertFileName)" accept=".pdf,image/*" class="hidden" id="health-upload" />
                                        <label
                                            for="health-upload"
                                            class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 hover:border-[#009688] rounded-xl text-xs font-semibold text-slate-700 transition cursor-pointer w-full justify-center truncate"
                                        >
                                            <CheckCircleIcon v-if="healthCertFileName" class="w-4 h-4 text-[#008C7E] shrink-0" />
                                            <PaperClipIcon v-else class="w-4 h-4 text-[#008C7E] shrink-0" />
                                            <span class="truncate">{{ healthCertFileName || 'Pilih Surat Sehat' }}</span>
                                        </label>
                                    </div>

                                    <!-- Kartu Keluarga (KK) -->
                                    <div
                                        class="p-3.5 rounded-2xl border transition-colors space-y-2"
                                        :class="familyCardFileName ? 'bg-[#F2FAF7] border-[#009688]/35' : 'bg-[#FAFCFB] border-slate-200/80'"
                                    >
                                        <label class="block text-xs font-bold text-slate-900">FC Kartu Keluarga / KK (PDF/Gambar)</label>
                                        <input type="file" @change="(e) => handleFileField(e, 'family_card', familyCardFileName, true)" accept=".pdf,image/*" class="hidden" id="kk-upload" />
                                        <label
                                            for="kk-upload"
                                            class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 hover:border-[#009688] rounded-xl text-xs font-semibold text-slate-700 transition cursor-pointer w-full justify-center truncate"
                                        >
                                            <CheckCircleIcon v-if="familyCardFileName" class="w-4 h-4 text-[#008C7E] shrink-0" />
                                            <PaperClipIcon v-else class="w-4 h-4 text-[#008C7E] shrink-0" />
                                            <span class="truncate">{{ familyCardFileName || 'Pilih Scan KK' }}</span>
                                        </label>
                                    </div>

                                    <!-- KTP -->
                                    <div
                                        class="p-3.5 rounded-2xl border transition-colors space-y-2"
                                        :class="ktpFileName ? 'bg-[#F2FAF7] border-[#009688]/35' : 'bg-[#FAFCFB] border-slate-200/80'"
                                    >
                                        <label class="block text-xs font-bold text-slate-900">Scan KTP (PDF/Gambar)</label>
                                        <input type="file" @change="(e) => handleFileField(e, 'ktp', ktpFileName, true)" accept=".pdf,image/*" class="hidden" id="ktp-upload" />
                                        <label
                                            for="ktp-upload"
                                            class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 hover:border-[#009688] rounded-xl text-xs font-semibold text-slate-700 transition cursor-pointer w-full justify-center truncate"
                                        >
                                            <CheckCircleIcon v-if="ktpFileName" class="w-4 h-4 text-[#008C7E] shrink-0" />
                                            <PaperClipIcon v-else class="w-4 h-4 text-[#008C7E] shrink-0" />
                                            <span class="truncate">{{ ktpFileName || 'Pilih Scan KTP' }}</span>
                                        </label>
                                    </div>

                                    <!-- Pas Foto 3x4 -->
                                    <div
                                        class="sm:col-span-2 p-4 rounded-2xl border transition-colors flex items-center gap-4"
                                        :class="photoFileName ? 'bg-[#F2FAF7] border-[#009688]/35' : 'bg-[#FAFCFB] border-slate-200/80'"
                                    >
                                        <div class="w-14 h-16 rounded-xl border border-slate-200 bg-white overflow-hidden flex items-center justify-center shrink-0">
                                            <img v-if="photoPreview" :src="photoPreview" alt="Preview Pas Foto" class="w-full h-full object-cover" />
                                            <PhotoIcon v-else class="w-6 h-6 text-slate-400" />
                                        </div>
                                        <div class="flex-1 min-w-0 space-y-1.5">
                                            <label class="block text-xs font-bold text-slate-900">Pas Foto 3x4 (JPG/PNG)</label>
                                            <input type="file" @change="(e) => handleFileField(e, 'photo', photoFileName, true)" accept="image/*" class="hidden" id="photo-upload" />
                                            <label
                                                for="photo-upload"
                                                class="inline-flex items-center gap-2 px-3.5 py-2 bg-white border border-slate-200 hover:border-[#009688] rounded-xl text-xs font-semibold text-slate-700 transition cursor-pointer max-w-full"
                                            >
                                                <PhotoIcon class="w-4 h-4 text-[#008C7E] shrink-0" />
                                                <span class="truncate">{{ isCompressingPhoto ? 'Mengompres...' : (photoFileName || 'Pilih Pas Foto 3x4') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Catatan Tambahan -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan Tambahan / Portofolio (Opsional)</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="3"
                                    placeholder="Tuliskan pengalaman mengajar/kerja relevan atau tautan portofolio Anda..."
                                    class="w-full rounded-xl border-slate-200 text-xs sm:text-[13px] font-medium focus:border-[#009688] focus:ring-[#009688]/20"
                                ></textarea>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-1">
                                <Motion :while-tap="{ scale: 0.99 }">
                                    <button
                                        type="submit"
                                        :disabled="form.processing || isCompressingPhoto"
                                        class="w-full py-3.5 px-6 rounded-xl bg-[#009688] hover:bg-[#007D71] text-white font-bold text-sm shadow-xs transition-colors flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                                    >
                                        <CheckIcon class="w-5 h-5" />
                                        <span>{{ form.processing ? 'Mengirim Lamaran...' : 'Kirim Pendaftaran Lamaran Kerja' }}</span>
                                    </button>
                                </Motion>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Other Open Vacancies Strip (When available) -->
            <section v-if="otherVacancies && otherVacancies.length > 0" class="pt-8 border-t border-slate-200/70 space-y-6">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg sm:text-xl tracking-tight text-slate-900">
                        <span class="font-normal">Lowongan </span>
                        <span class="font-extrabold">Terbuka Lainnya</span>
                    </h2>
                    <Link :href="route('careers.index')" class="text-xs font-bold text-[#008C7E] hover:underline inline-flex items-center gap-1">
                        <span>Lihat Semua</span>
                        <ArrowRightIcon class="w-3.5 h-3.5" />
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <Link
                        v-for="item in otherVacancies"
                        :key="item.id"
                        :href="route('careers.show', item.slug)"
                        class="bg-white rounded-2xl border border-slate-200/80 hover:border-[#009688]/50 hover:shadow-md transition-all p-5 flex flex-col justify-between gap-4 group"
                    >
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="px-2.5 py-0.5 rounded-full bg-[#E6F7F1] text-[#007468] text-[10px] font-extrabold uppercase">
                                    {{ item.unit?.name || 'Yayasan Namira' }}
                                </span>
                                <span class="text-[11px] font-semibold text-slate-400">{{ item.type_label }}</span>
                            </div>
                            <h3 class="text-sm font-extrabold text-slate-900 group-hover:text-[#008C7E] transition-colors">
                                {{ item.title }}
                            </h3>
                        </div>
                        <div class="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-100">
                            <span>Kuota {{ item.quota }} Orang</span>
                            <span class="font-bold text-[#008C7E] inline-flex items-center gap-1">
                                Detail <ArrowRightIcon class="w-3.5 h-3.5" />
                            </span>
                        </div>
                    </Link>
                </div>
            </section>
        </main>

        <!-- Matching Minimal White Footer -->
        <footer class="bg-white border-t border-slate-100 py-10 mt-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
                <div class="flex items-center gap-2">
                    <span class="font-namira text-base text-[#1a4373] lowercase">namira</span>
                    <span class="font-school text-[10px] text-slate-500 uppercase font-extrabold">SCHOOL</span>
                    <span class="text-slate-300">·</span>
                    <span>© {{ new Date().getFullYear() }} Yayasan Sekolah Islam Namira</span>
                </div>
                <span>Proses rekrutmen resmi Yayasan Namira tidak dipungut biaya apa pun.</span>
            </div>
        </footer>

        <!-- Animated Full-Screen Poster Lightbox -->
        <Teleport to="body">
            <AnimatePresence>
                <Motion
                    v-if="lightboxOpen && vacancy.poster_url"
                    :initial="{ opacity: 0 }"
                    :animate="{ opacity: 1 }"
                    :exit="{ opacity: 0 }"
                    :transition="{ duration: 0.2 }"
                    class="fixed inset-0 z-[120] bg-slate-950/88 backdrop-blur-sm flex flex-col items-center justify-center p-4"
                    @click.self="lightboxOpen = false"
                >
                    <Motion
                        :initial="{ opacity: 0, scale: 0.94, y: 12 }"
                        :animate="{ opacity: 1, scale: 1, y: 0 }"
                        :exit="{ opacity: 0, scale: 0.95 }"
                        :transition="{ duration: 0.24, ease: 'easeOut' }"
                        class="w-full max-w-2xl flex flex-col items-center"
                    >
                        <div class="w-full flex items-center justify-between text-white mb-3 gap-3">
                            <h4 class="font-bold text-sm truncate">{{ vacancy.title }}</h4>
                            <div class="flex items-center gap-2 shrink-0">
                                <a
                                    :href="vacancy.poster_url"
                                    download
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="px-3 py-1.5 rounded-xl bg-white/15 hover:bg-white/25 text-white text-xs font-bold inline-flex items-center gap-1.5 transition"
                                >
                                    <ArrowDownTrayIcon class="w-4 h-4" />
                                    <span>Unduh</span>
                                </a>
                                <button
                                    type="button"
                                    @click="lightboxOpen = false"
                                    aria-label="Tutup poster"
                                    class="p-2 rounded-xl bg-white/15 hover:bg-white/25 text-white transition cursor-pointer"
                                >
                                    <XMarkIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                        <img
                            :src="vacancy.poster_url"
                            :alt="vacancy.title"
                            class="max-h-[84vh] max-w-full object-contain rounded-2xl shadow-2xl border border-white/15"
                        />
                    </Motion>
                </Motion>
            </AnimatePresence>
        </Teleport>
    </div>
</template>
