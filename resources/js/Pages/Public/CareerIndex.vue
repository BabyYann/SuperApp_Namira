<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';
import 'lenis/dist/lenis.css';
import { Motion, AnimatePresence } from 'motion-v';
import {
    BriefcaseIcon,
    BuildingOffice2Icon,
    ClockIcon,
    MagnifyingGlassIcon,
    ArrowRightIcon,
    UserGroupIcon,
    MagnifyingGlassPlusIcon,
    XMarkIcon,
    MapPinIcon,
    ChevronDownIcon,
    AcademicCapIcon,
    HeartIcon,
    SparklesIcon,
    CpuChipIcon,
    ShieldCheckIcon,
    LightBulbIcon,
    HandRaisedIcon,
    ChatBubbleLeftRightIcon,
    ArrowDownTrayIcon,
    Bars3Icon,
    CheckCircleIcon,
    DocumentTextIcon,
    UserPlusIcon,
    PresentationChartBarIcon,
    AdjustmentsHorizontalIcon,
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

gsap.registerPlugin(ScrollTrigger);
dayjs.locale('id');

const props = defineProps({
    vacancies: Object,
    units: Array,
    filters: Object,
    categories: Object,
    stats: {
        type: Object,
        default: () => ({}),
    },
    campusMedia: {
        type: Array,
        default: () => [],
    },
    testimonials: {
        type: Array,
        default: () => [],
    },
});

// Filters & Search State
const search = ref(props.filters?.search || '');
const selectedUnit = ref(props.filters?.unit || 'all');
const selectedCategory = ref(props.filters?.category || '');
const lightboxPoster = ref(null);
const mobileMenuOpen = ref(false);
const mobileFilterOpen = ref(false);

const activeFilterCount = computed(() => {
    let count = 0;
    if (selectedUnit.value && selectedUnit.value !== 'all') count++;
    if (selectedCategory.value) count++;
    return count;
});

const hasActiveFilters = computed(
    () => Boolean(search.value?.trim()) || activeFilterCount.value > 0
);

const applyFilters = (shouldScroll = false) => {
    mobileFilterOpen.value = false;
    router.get(
        route('careers.index'),
        {
            search: search.value?.trim() || undefined,
            unit: selectedUnit.value === 'all' ? undefined : selectedUnit.value,
            category: selectedCategory.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                if (shouldScroll) {
                    smoothScrollTo('#lowongan');
                }
            },
        }
    );
};

const resetFilters = () => {
    search.value = '';
    selectedUnit.value = 'all';
    selectedCategory.value = '';
    applyFilters(false);
};

const selectQuickCategory = (catKey) => {
    selectedCategory.value = catKey;
    applyFilters(false);
};

let searchTimeout;
watch(search, (newVal, oldVal) => {
    if (newVal === oldVal) return;
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters(false);
    }, 420);
});

const formatDate = (dateStr) => {
    if (!dateStr) return 'Tanpa Batas Waktu';
    return dayjs(dateStr).format('DD MMM YYYY');
};

const formatWhatsappHref = (vacancy) => {
    const raw = String(vacancy?.contact_whatsapp || '').replace(/[^0-9]/g, '');
    if (!raw) return null;
    const intl = raw.startsWith('0') ? '62' + raw.slice(1) : raw;
    const text = `Assalamu'alaikum Admin ${vacancy?.unit?.name || 'Yayasan Namira'}, saya ingin bertanya terkait lowongan *${vacancy?.title}*.`;
    return `https://wa.me/${intl}?text=${encodeURIComponent(text)}`;
};

const excerptText = (text, max = 190) => {
    if (!text) return '';
    const clean = String(text).replace(/\s+/g, ' ').trim();
    if (clean.length <= max) return clean;
    return clean.slice(0, max).trimEnd() + '...';
};

// Real campus media resolver (uses live DB Banner images with verified local static fallbacks)
const findMediaUrl = (keywords, fallback) => {
    const list = props.campusMedia || [];
    const match = list.find((item) => {
        const title = String(item.title || '').toLowerCase();
        return keywords.some((kw) => title.includes(kw));
    });
    return match?.url || fallback;
};

const heroPhotos = computed(() => ({
    sd: findMediaUrl(['sd ', 'sd namira', 'gedung'], '/images/landing/banner-1.webp'),
    smp: findMediaUrl(['smp'], '/images/landing/photo-smp.webp'),
    tk: findMediaUrl(['tk', 'kb', 'paud'], '/images/landing/banner-3.webp'),
    daycare: findMediaUrl(['day care', 'daycare', 'pavlov'], '/images/landing/banner-2.webp'),
}));

// Real institutional metrics
const displayStats = computed(() => {
    const unitsCount = Number(props.stats?.units_count || 0) > 0 ? Number(props.stats.units_count) : 10;
    const educatorsCount = Number(props.stats?.educators_count || 0) > 0 ? Number(props.stats.educators_count) : 180;
    const studentsCount = Number(props.stats?.students_count || 0) > 0 ? Number(props.stats.students_count) : 900;

    return [
        {
            target: unitsCount,
            suffix: '+',
            label: 'Unit Pendidikan',
            desc: 'Daycare, KB, TK, SD, hingga SMP dalam satu naungan Yayasan Namira.',
        },
        {
            target: educatorsCount,
            suffix: '+',
            label: 'Pendidik & Staf',
            desc: 'Kolaborasi guru profesional, pengajar Metode Ummi, dan staf berpengalaman.',
        },
        {
            target: studentsCount,
            suffix: '+',
            label: 'Peserta Didik',
            desc: 'Generasi Qur’ani yang bertumbuh setiap hari di seluruh kampus Namira.',
        },
        {
            target: 2,
            suffix: ' Wilayah',
            label: 'Kab. & Kota Probolinggo',
            desc: 'Melayani masyarakat di Kraksaan, Dringu, dan Kota Probolinggo.',
        },
    ];
});

// Quick category tabs
const quickCategories = computed(() => [
    { key: '', label: 'Semua' },
    { key: 'teacher', label: 'Pendidik (Guru)' },
    { key: 'staff', label: 'Staf & Admin' },
    { key: 'operational', label: 'Operasional' },
]);

// Adaptive vacancy layout helpers
const vacancyList = computed(() => props.vacancies?.data || []);
const isSingleSpotlight = computed(() => vacancyList.value.length === 1);
const isTwoUpLayout = computed(() => vacancyList.value.length === 2);

// Interactive Ecosystem Accordion
const activeEcosystemIndex = ref(0);
const ecosystems = computed(() => [
    {
        id: 'sd',
        name: 'SD Namira (Kraksaan & Kota)',
        tagline: 'Jenjang Sekolah Dasar Islam Modern',
        logo: '/images/landing/logo-sd.webp',
        photo: heroPhotos.value.sd,
        location: 'Kraksaan & Kota Probolinggo',
        summary:
            'Menghadirkan pembelajaran aktif berbasis fitrah, pembiasaan adab Islami, program tahfidz & Metode Ummi, serta literasi kreatif bagi peserta didik tingkat dasar.',
        highlights: ['Metode Ummi & Tahfidz', 'Pembelajaran Aktif', '2 Kampus: Kraksaan & Kota'],
    },
    {
        id: 'smp',
        name: 'SMP Namira Kota Probolinggo',
        tagline: 'Jenjang Menengah Berwawasan Global',
        logo: '/images/landing/logo-smp.webp',
        photo: heroPhotos.value.smp,
        location: 'Kota Probolinggo',
        summary:
            'Memadukan penguatan karakter Qur’ani, penguasaan bilingual, budaya riset, dan pembinaan kepemimpinan remaja dalam ekosistem sekolah menengah yang terstruktur.',
        highlights: ['Program Bilingual & Riset', 'Tahfidz & Adab Remaja', 'Full Day School'],
    },
    {
        id: 'tk',
        name: 'TK & KB Namira (Kraksaan & Dringu)',
        tagline: 'Pendidikan Anak Usia Dini Berbasis Fitrah',
        logo: '/images/landing/logo-tk.webp',
        photo: heroPhotos.value.tk,
        location: 'Kraksaan & Dringu',
        summary:
            'Lingkungan belajar penuh kehangatan untuk menstimulasi motorik, kemandirian, kecintaan pada Al-Qur’an, serta kegembiraan belajar anak usia dini.',
        highlights: ['Stimulasi Motorik', 'Pembiasaan Adab', '2 Lokasi: Kraksaan & Dringu'],
    },
    {
        id: 'daycare',
        name: 'Daycare & Pavlov Namira',
        tagline: 'Layanan Pengasuhan & Tumbuh Kembang',
        logo: '/images/landing/logo-daycare.webp',
        photo: heroPhotos.value.daycare,
        location: 'Kraksaan & Dringu',
        summary:
            'Pendampingan pengasuhan ramah keluarga dengan laporan harian digital terpantau serta layanan tumbuh kembang yang aman dan terpercaya.',
        highlights: ['Laporan Digital Harian', 'Pengasuh Terlatih', 'Ramah Keluarga'],
    },
]);

const activeEcosystem = computed(() => ecosystems.value[activeEcosystemIndex.value] || ecosystems.value[0]);

// Benefits
const benefits = [
    {
        icon: HeartIcon,
        tint: 'bg-[#E6F7F1] text-[#008C7E]',
        title: 'Lingkungan Islami & Hangat',
        desc: 'Budaya kekeluargaan yang menjunjung adab, saling menghargai, dan memberi ketenangan dalam berkarya.',
    },
    {
        icon: AcademicCapIcon,
        tint: 'bg-sky-50 text-sky-600',
        title: 'Pengembangan Kompetensi',
        desc: 'Pelatihan berkala, sertifikasi Metode Ummi, hingga pembinaan pedagogik dan kepemimpinan unit.',
    },
    {
        icon: CpuChipIcon,
        tint: 'bg-amber-50 text-amber-600',
        title: 'Sistem Kerja Digital Terpadu',
        desc: 'Didukung SuperApp Namira untuk jurnal kelas, presensi, dan kolaborasi antar-unit yang rapi.',
    },
    {
        icon: SparklesIcon,
        tint: 'bg-indigo-50 text-indigo-600',
        title: 'Karya yang Berdampak Luas',
        desc: 'Setiap peran — pendidik maupun staf — mengambil bagian langsung membina generasi masa depan.',
    },
];

// Values
const values = [
    {
        icon: ShieldCheckIcon,
        tint: 'bg-[#E6F7F1] text-[#008C7E]',
        title: 'Amanah & Keteladanan',
        desc: 'Menjaga integritas dan akhlak mulia dalam setiap ucapan, keputusan, serta pendampingan siswa.',
    },
    {
        icon: HeartIcon,
        tint: 'bg-rose-50 text-rose-600',
        title: 'Berpusat pada Fitrah Anak',
        desc: 'Merancang pembelajaran dan layanan dengan empati sesuai tahap tumbuh kembang peserta didik.',
    },
    {
        icon: LightBulbIcon,
        tint: 'bg-amber-50 text-amber-600',
        title: 'Bertumbuh Berkelanjutan',
        desc: 'Terbuka terhadap inovasi pendidikan, umpan balik konstruktif, dan peningkatan kapasitas diri.',
    },
    {
        icon: HandRaisedIcon,
        tint: 'bg-sky-50 text-sky-600',
        title: 'Kolaborasi Lintas Unit',
        desc: 'Bekerja bersama antar guru, staf, orang tua, dan yayasan dalam satu visi pendidikan yang selaras.',
    },
];

// Recruitment Journey Steps
const selectionSteps = [
    {
        num: '01',
        icon: DocumentTextIcon,
        title: 'Lamar Online',
        desc: 'Pilih posisi yang sesuai, isi formulir daring, dan unggah CV beserta berkas langsung di portal ini.',
    },
    {
        num: '02',
        icon: CheckCircleIcon,
        title: 'Seleksi Administrasi',
        desc: 'Tim Humas & Rekrutmen meninjau kesesuaian kualifikasi, latar belakang pendidikan, dan berkas Anda.',
    },
    {
        num: '03',
        icon: PresentationChartBarIcon,
        title: 'Microteaching & Wawancara',
        desc: 'Kandidat terpilih mengikuti praktik mengajar / uji bidang, observasi baca Al-Qur’an, serta wawancara.',
    },
    {
        num: '04',
        icon: UserPlusIcon,
        title: 'Bergabung & Onboarding',
        desc: 'Kandidat yang lulus menerima penawaran resmi dan mengikuti masa orientasi budaya kerja Namira.',
    },
];

// Tabbed FAQ
const activeFaqTab = ref('pendaftaran');
const faqTabs = [
    { key: 'pendaftaran', label: 'Pendaftaran' },
    { key: 'seleksi', label: 'Tahap Seleksi' },
    { key: 'penempatan', label: 'Penempatan & Karir' },
];

const faqItemsByTab = {
    pendaftaran: [
        {
            q: 'Bagaimana cara melamar lowongan kerja di Yayasan Namira?',
            a: 'Klik tombol "Lihat Detail & Lamar Online" pada posisi yang tersedia, lengkapi data diri dan riwayat pendidikan, lalu unggah CV beserta berkas pendukung (PDF atau foto) secara langsung.',
        },
        {
            q: 'Apakah fresh graduate diperbolehkan melamar?',
            a: 'Ya, lulusan baru yang memiliki semangat belajar tinggi, komunikatif, dan siap dibina sangat terbuka untuk melamar sesuai kualifikasi pada poster atau deskripsi lowongan.',
        },
        {
            q: 'Dokumen apa saja yang sebaiknya disiapkan sebelum mendaftar?',
            a: 'Siapkan CV terbaru (wajib), surat lamaran, scan ijazah terakhir, pas foto 3x4, KTP, Kartu Keluarga, serta surat keterangan sehat apabila tercantum pada persyaratan unit.',
        },
    ],
    seleksi: [
        {
            q: 'Berapa lama proses peninjauan lamaran berlangsung?',
            a: 'Tim rekrutmen unit dan yayasan meninjau berkas secara berkala. Kandidat yang memenuhi kualifikasi akan dihubungi melalui nomor WhatsApp atau email terdaftar.',
        },
        {
            q: 'Apakah pelamar guru harus sudah bersertifikat Metode Ummi?',
            a: 'Belum wajib saat melamar. Kemampuan membaca Al-Qur’an yang baik menjadi nilai tambah, dan Yayasan Namira menyediakan pembinaan serta sertifikasi Metode Ummi bagi pendidik.',
        },
        {
            q: 'Apakah proses rekrutmen Yayasan Namira dipungut biaya?',
            a: 'Tidak. Seluruh tahapan pendaftaran dan seleksi rekrutmen resmi Yayasan Namira sepenuhnya gratis dan tidak dipungut biaya apa pun.',
        },
    ],
    penempatan: [
        {
            q: 'Di mana saja lokasi kampus penempatan kerja Yayasan Namira?',
            a: 'Unit pendidikan Yayasan Namira tersebar di Kraksaan (Kab. Probolinggo), Dringu (Kab. Probolinggo), dan Kota Probolinggo. Lokasi penempatan tercantum di tiap lowongan.',
        },
        {
            q: 'Apakah tersedia jenjang pengembangan karir bagi guru dan staf?',
            a: 'Ya. Dengan ekosistem lebih dari 10 unit layanan pendidikan, pendidik dan staf berprestasi memiliki ruang pertumbuhan menuju koordinator, wakil kepala sekolah, hingga manajemen yayasan.',
        },
        {
            q: 'Siapa yang dapat dihubungi jika mengalami kendala saat mengunggah berkas?',
            a: 'Hubungi nomor WhatsApp Admin Unit yang tertera pada halaman detail lowongan atau kirim pesan ke email resmi hr@namiraschool.com.',
        },
    ],
};

const currentFaqs = computed(() => faqItemsByTab[activeFaqTab.value] || []);

// GSAP + Lenis (Lenis on desktop fine-pointer only so touch devices keep silky native momentum!)
const pageRoot = ref(null);
const progressBarRef = ref(null);
const heroCollageRef = ref(null);
let lenisInstance = null;
let lenisRaf = null;
let gsapCtx = null;
let keydownHandler = null;
let parallaxCleanup = null;

const smoothScrollTo = (target) => {
    mobileMenuOpen.value = false;
    if (lenisInstance) {
        lenisInstance.scrollTo(target, { offset: -80, duration: 1.0 });
    } else if (typeof target === 'number') {
        window.scrollTo({ top: target, behavior: 'smooth' });
    } else {
        const el = document.querySelector(target);
        if (el) {
            const y = el.getBoundingClientRect().top + window.scrollY - 76;
            window.scrollTo({ top: y, behavior: 'smooth' });
        }
    }
};

watch(lightboxPoster, (val) => {
    if (val) {
        lenisInstance?.stop();
        document.body.style.overflow = 'hidden';
    } else {
        lenisInstance?.start();
        document.body.style.overflow = '';
    }
});

onMounted(() => {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const isFinePointer = window.matchMedia('(pointer: fine)').matches;

    keydownHandler = (e) => {
        if (e.key === 'Escape' && lightboxPoster.value) {
            lightboxPoster.value = null;
        }
    };
    window.addEventListener('keydown', keydownHandler);

    // Smooth inertia on desktop; 100% native silky touch scroll on mobile phones
    if (!prefersReduced && isFinePointer) {
        lenisInstance = new Lenis({
            duration: 1.05,
            easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            smoothWheel: true,
            wheelMultiplier: 0.95,
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

        gsap.from('.hero-stagger', {
            y: 14,
            opacity: 0,
            duration: 0.65,
            stagger: 0.08,
            ease: 'power3.out',
            clearProps: 'transform,opacity',
        });

        if (isFinePointer) {
            gsap.from('.hero-collage-item', {
                scale: 0.94,
                opacity: 0,
                y: 14,
                duration: 0.75,
                stagger: 0.09,
                delay: 0.1,
                ease: 'power3.out',
                clearProps: 'transform,opacity',
            });

            gsap.to('.float-slow-a', { y: -6, duration: 4.2, repeat: -1, yoyo: true, ease: 'sine.inOut' });
            gsap.to('.float-slow-b', { y: 6, duration: 5.0, repeat: -1, yoyo: true, ease: 'sine.inOut', delay: 0.6 });
            gsap.to('.float-slow-c', { y: -4, duration: 5.6, repeat: -1, yoyo: true, ease: 'sine.inOut', delay: 1.1 });
        }

        gsap.utils.toArray('.reveal-up').forEach((el) => {
            gsap.from(el, {
                y: 18,
                opacity: 0,
                duration: 0.6,
                ease: 'power2.out',
                clearProps: 'transform,opacity',
                scrollTrigger: {
                    trigger: el,
                    start: 'top 88%',
                    once: true,
                },
            });
        });

        gsap.utils.toArray('.stagger-group').forEach((group) => {
            const items = group.querySelectorAll('.stagger-card');
            if (!items.length) return;
            gsap.from(items, {
                y: 16,
                opacity: 0,
                duration: 0.55,
                stagger: 0.07,
                ease: 'power2.out',
                clearProps: 'transform,opacity',
                scrollTrigger: {
                    trigger: group,
                    start: 'top 86%',
                    once: true,
                },
            });
        });

        gsap.utils.toArray('.stat-counter').forEach((el) => {
            const target = Number(el.dataset.target || 0);
            const suffix = el.dataset.suffix || '';
            const proxy = { val: 0 };
            ScrollTrigger.create({
                trigger: el,
                start: 'top 90%',
                once: true,
                onEnter: () => {
                    gsap.to(proxy, {
                        val: target,
                        duration: 1.15,
                        ease: 'power2.out',
                        onUpdate: () => {
                            el.textContent = Math.round(proxy.val).toLocaleString('id-ID') + suffix;
                        },
                    });
                },
            });
        });

        if (heroCollageRef.value && isFinePointer) {
            const layers = heroCollageRef.value.querySelectorAll('[data-depth]');
            const quickSetters = Array.from(layers).map((layer) => ({
                depth: Number(layer.dataset.depth || 1),
                xTo: gsap.quickTo(layer, 'x', { duration: 0.8, ease: 'power3.out' }),
                yTo: gsap.quickTo(layer, 'y', { duration: 0.8, ease: 'power3.out' }),
            }));
            const onMove = (e) => {
                const rect = heroCollageRef.value.getBoundingClientRect();
                const nx = (e.clientX - rect.left) / rect.width - 0.5;
                const ny = (e.clientY - rect.top) / rect.height - 0.5;
                quickSetters.forEach(({ depth, xTo, yTo }) => {
                    xTo(nx * depth * 12);
                    yTo(ny * depth * 10);
                });
            };
            const onLeave = () => {
                quickSetters.forEach(({ xTo, yTo }) => {
                    xTo(0);
                    yTo(0);
                });
            };
            heroCollageRef.value.addEventListener('mousemove', onMove);
            heroCollageRef.value.addEventListener('mouseleave', onLeave);
            parallaxCleanup = () => {
                heroCollageRef.value?.removeEventListener('mousemove', onMove);
                heroCollageRef.value?.removeEventListener('mouseleave', onLeave);
            };
        }
    }, pageRoot.value);
});

onUnmounted(() => {
    document.body.style.overflow = '';
    if (keydownHandler) window.removeEventListener('keydown', keydownHandler);
    if (parallaxCleanup) parallaxCleanup();
    if (gsapCtx) gsapCtx.revert();
    if (lenisRaf) gsap.ticker.remove(lenisRaf);
    if (lenisInstance) lenisInstance.destroy();
});
</script>

<template>
    <Head title="Karir & Rekrutmen — Yayasan Sekolah Islam Namira" />

    <div
        ref="pageRoot"
        class="min-h-screen bg-white text-slate-900 font-sans antialiased selection:bg-[#009688]/15 selection:text-[#006B60] flex flex-col overflow-x-clip"
    >
        <!-- Minimal Scroll Progress Hairline -->
        <div
            ref="progressBarRef"
            aria-hidden="true"
            class="fixed top-0 left-0 right-0 h-[2.5px] bg-[#009688] origin-left scale-x-0 z-50 pointer-events-none"
        ></div>

        <!-- Minimal Airy White Navbar -->
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-100">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-[72px] flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
                    <Link href="/" class="flex items-center gap-2 sm:gap-2.5">
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-white border border-slate-100 shadow-2xs p-1 flex items-center justify-center shrink-0 overflow-hidden">
                            <img
                                :src="$page.props.app_settings?.app_logo || '/images/landing/logo-yayasan.webp'"
                                alt="Logo Yayasan Namira"
                                class="w-full h-full object-contain"
                            />
                        </div>
                        <div class="flex items-baseline gap-1 select-none whitespace-nowrap">
                            <span class="font-namira text-lg sm:text-xl text-[#1a4373] tracking-tight lowercase leading-none">namira</span>
                            <span class="font-school text-[11px] sm:text-xs text-slate-500 tracking-wider uppercase font-extrabold leading-none">SCHOOL</span>
                        </div>
                    </Link>

                    <span class="h-4 sm:h-5 w-px bg-slate-200"></span>
                    <span class="text-xs sm:text-[13px] font-semibold text-slate-500 tracking-tight">Karir</span>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex items-center gap-7 text-[13px] font-semibold text-slate-600">
                    <button type="button" @click="smoothScrollTo('#lowongan')" class="hover:text-[#009688] transition-colors cursor-pointer">
                        Lowongan
                    </button>
                    <button type="button" @click="smoothScrollTo('#ekosistem')" class="hover:text-[#009688] transition-colors cursor-pointer">
                        Ekosistem
                    </button>
                    <button type="button" @click="smoothScrollTo('#budaya')" class="hover:text-[#009688] transition-colors cursor-pointer">
                        Budaya Kerja
                    </button>
                    <button type="button" @click="smoothScrollTo('#seleksi')" class="hover:text-[#009688] transition-colors cursor-pointer">
                        Alur Seleksi
                    </button>
                    <Link href="/" class="hover:text-[#009688] transition-colors">Beranda</Link>
                    <Link
                        :href="route('login')"
                        class="px-4 py-2 rounded-xl border border-slate-200 hover:border-[#009688] hover:text-[#009688] text-slate-700 font-bold text-xs transition-colors whitespace-nowrap"
                    >
                        Masuk Portal
                    </Link>
                </nav>

                <!-- Mobile Actions -->
                <div class="flex md:hidden items-center gap-2">
                    <button
                        type="button"
                        @click="smoothScrollTo('#lowongan')"
                        class="px-3.5 py-2 min-h-[38px] rounded-xl bg-[#009688] text-white text-xs font-bold cursor-pointer"
                    >
                        Lowongan
                    </button>
                    <button
                        type="button"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        aria-label="Toggle Menu"
                        class="p-2 min-w-[38px] min-h-[38px] flex items-center justify-center rounded-xl border border-slate-200 text-slate-700 active:bg-slate-50 transition cursor-pointer"
                    >
                        <XMarkIcon v-if="mobileMenuOpen" class="w-5 h-5" />
                        <Bars3Icon v-else class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Mobile Dropdown Menu -->
            <AnimatePresence>
                <Motion
                    v-if="mobileMenuOpen"
                    :initial="{ opacity: 0, height: 0 }"
                    :animate="{ opacity: 1, height: 'auto' }"
                    :exit="{ opacity: 0, height: 0 }"
                    :transition="{ duration: 0.2 }"
                    class="md:hidden border-t border-slate-100 bg-white overflow-hidden shadow-lg"
                >
                    <div class="px-4 py-3 flex flex-col gap-1 text-sm font-semibold text-slate-700">
                        <button type="button" @click="smoothScrollTo('#lowongan')" class="text-left px-3 py-2.5 rounded-xl active:bg-[#ECF9F4] hover:text-[#009688]">Lowongan Dibuka</button>
                        <button type="button" @click="smoothScrollTo('#ekosistem')" class="text-left px-3 py-2.5 rounded-xl active:bg-[#ECF9F4] hover:text-[#009688]">Ekosistem Pendidikan</button>
                        <button type="button" @click="smoothScrollTo('#budaya')" class="text-left px-3 py-2.5 rounded-xl active:bg-[#ECF9F4] hover:text-[#009688]">Budaya &amp; Nilai Kerja</button>
                        <button type="button" @click="smoothScrollTo('#seleksi')" class="text-left px-3 py-2.5 rounded-xl active:bg-[#ECF9F4] hover:text-[#009688]">Alur Seleksi &amp; FAQ</button>
                        <div class="mt-2 pt-3 border-t border-slate-100 flex items-center justify-between gap-3 px-2 pb-1">
                            <Link href="/" class="text-xs font-bold text-slate-500">Kembali ke Beranda</Link>
                            <Link :href="route('login')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800">
                                Masuk Portal
                            </Link>
                        </div>
                    </div>
                </Motion>
            </AnimatePresence>
        </header>

        <main class="flex-1">
            <!-- =========================================================
                 HERO SECTION — Clean Mobile-First + Stockbit Desktop Collage
            ========================================================== -->
            <section class="relative overflow-hidden pt-7 pb-10 sm:pt-12 sm:pb-16 lg:pt-16 lg:pb-32">
                <!-- Organic Soft Pastel Mint Curved Backdrop -->
                <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
                    <div class="lg:hidden absolute inset-0 bg-gradient-to-b from-[#ECF9F4] via-[#F4FBF8] to-white"></div>
                    <svg
                        viewBox="0 0 1440 620"
                        fill="none"
                        preserveAspectRatio="none"
                        class="hidden lg:block absolute top-0 left-0 w-full h-full"
                    >
                        <path
                            d="M0 0H720C690 190 610 475 405 540C215 600 85 520 0 420V0Z"
                            fill="#ECF9F4"
                        />
                        <circle cx="1180" cy="110" r="220" fill="#F4FBF8" opacity="0.7" />
                    </svg>
                </div>

                <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-7 lg:gap-8 items-center">
                        <!-- Left Typography Column -->
                        <div class="lg:col-span-6 space-y-4 sm:space-y-6">
                            <div
                                v-if="(stats?.open_vacancies_count || vacancies?.total || 0) > 0"
                                class="hero-stagger inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white border border-[#009688]/20 shadow-2xs text-xs font-semibold text-[#007468]"
                            >
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#009688] opacity-60"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-[#009688]"></span>
                                </span>
                                <span>{{ stats?.open_vacancies_count || vacancies?.total }} Posisi Sedang Dibuka</span>
                            </div>

                            <h1 class="hero-stagger text-[1.95rem] sm:text-[2.85rem] lg:text-[3.35rem] leading-[1.13] tracking-[-0.03em] text-slate-900">
                                <span class="font-normal text-slate-800 block">Tumbuh &amp;</span>
                                <span class="font-extrabold text-slate-950 block">Berkarya Bersama</span>
                                <span class="font-normal text-slate-800 block">Yayasan Namira</span>
                            </h1>

                            <p class="hero-stagger text-[13.5px] sm:text-[15px] text-slate-600 leading-relaxed max-w-[470px]">
                                Bergabung bersama pendidik, pembina Al-Qur’an, dan tenaga profesional untuk menghadirkan pendidikan Islam modern yang hangat dan berdampak di Probolinggo.
                            </p>

                            <div class="hero-stagger flex flex-wrap items-center gap-4 sm:gap-5 pt-0.5">
                                <button
                                    type="button"
                                    @click="smoothScrollTo('#lowongan')"
                                    class="inline-flex items-center gap-2 text-sm font-bold text-[#008C7E] hover:text-[#006B60] transition-colors group cursor-pointer"
                                >
                                    <span>Lihat Lowongan Terbuka</span>
                                    <ArrowRightIcon class="w-4 h-4 transition-transform group-hover:translate-x-1" />
                                </button>

                                <span class="h-4 w-px bg-slate-200 hidden sm:inline-block"></span>

                                <button
                                    type="button"
                                    @click="smoothScrollTo('#budaya')"
                                    class="hidden sm:inline text-sm font-semibold text-slate-500 hover:text-slate-900 transition-colors cursor-pointer"
                                >
                                    Kenali Budaya Kami
                                </button>
                            </div>

                            <!-- Compact Mobile Campus Strip (Zero overlapping bubbles on narrow phones!) -->
                            <div class="hero-stagger lg:hidden grid grid-cols-3 gap-2 pt-2">
                                <div class="h-20 sm:h-28 rounded-2xl overflow-hidden bg-white p-1 shadow-xs ring-1 ring-slate-100">
                                    <img :src="heroPhotos.sd" alt="Kampus SD Namira" class="w-full h-full object-cover rounded-xl" />
                                </div>
                                <div class="h-20 sm:h-28 rounded-2xl overflow-hidden bg-white p-1 shadow-xs ring-1 ring-slate-100">
                                    <img :src="heroPhotos.smp" alt="Kampus SMP Namira" class="w-full h-full object-cover rounded-xl" />
                                </div>
                                <div class="h-20 sm:h-28 rounded-2xl overflow-hidden bg-white p-1 shadow-xs ring-1 ring-slate-100">
                                    <img :src="heroPhotos.tk" alt="Kampus TK & Daycare Namira" class="w-full h-full object-cover rounded-xl" />
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Right Organic Floating Campus Collage -->
                        <div ref="heroCollageRef" class="hidden lg:block lg:col-span-6 relative">
                            <div class="relative mx-auto max-w-[500px] h-[385px]">
                                <svg
                                    viewBox="0 0 500 385"
                                    fill="none"
                                    aria-hidden="true"
                                    class="absolute inset-0 w-full h-full pointer-events-none"
                                >
                                    <path d="M80 220 C 95 100, 210 65, 315 80" stroke="#A7DFD2" stroke-width="1.5" stroke-dasharray="5 5" />
                                    <path d="M145 310 C 230 345, 345 320, 410 230" stroke="#CBD5E1" stroke-width="1.5" stroke-dasharray="5 5" />
                                    <circle cx="430" cy="88" r="14" fill="#FEF3C7" />
                                    <circle cx="430" cy="88" r="5" fill="#F59E0B" />
                                    <circle cx="62" cy="148" r="16" fill="#E6F7F1" />
                                    <circle cx="62" cy="148" r="6" fill="#009688" />
                                    <circle cx="290" cy="340" r="10" fill="#E0F2FE" />
                                    <circle cx="290" cy="340" r="4" fill="#0EA5E9" />
                                </svg>

                                <div
                                    data-depth="1.1"
                                    class="hero-collage-item float-slow-a absolute left-[14%] top-[6%] w-48 h-48 rounded-full p-1.5 bg-white shadow-[0_18px_40px_-14px_rgba(15,23,42,0.16)] ring-1 ring-slate-100"
                                >
                                    <img :src="heroPhotos.sd" alt="Lingkungan Kampus SD Namira" class="w-full h-full object-cover rounded-full" />
                                </div>

                                <div
                                    data-depth="1.5"
                                    class="hero-collage-item float-slow-b absolute right-[7%] top-[4%] w-32 h-32 rounded-full p-1.5 bg-white shadow-[0_16px_34px_-14px_rgba(15,23,42,0.14)] ring-1 ring-slate-100"
                                >
                                    <img :src="heroPhotos.smp" alt="Kampus SMP Namira" class="w-full h-full object-cover rounded-full" />
                                </div>

                                <div
                                    data-depth="1.3"
                                    class="hero-collage-item float-slow-c absolute right-[5%] bottom-[8%] w-52 h-36 rounded-[28px] p-1.5 bg-white shadow-[0_18px_42px_-16px_rgba(15,23,42,0.16)] ring-1 ring-slate-100"
                                >
                                    <img :src="heroPhotos.tk" alt="Kampus TK, KB & Daycare Namira" class="w-full h-full object-cover rounded-[22px]" />
                                </div>

                                <div
                                    data-depth="1.8"
                                    class="hero-collage-item float-slow-b absolute left-[0%] top-[18%] px-3.5 py-2 rounded-full bg-slate-900 text-white text-[11px] font-bold shadow-md flex items-center gap-1.5 whitespace-nowrap"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    <span>Lingkungan Qur’ani</span>
                                </div>

                                <div
                                    data-depth="1.4"
                                    class="hero-collage-item float-slow-a absolute left-[6%] bottom-[12%] bg-white/95 backdrop-blur-xs border border-slate-100 rounded-2xl px-3.5 py-2.5 shadow-[0_12px_30px_-10px_rgba(15,23,42,0.12)] flex items-center gap-2.5"
                                >
                                    <img src="/images/landing/logo-yayasan.webp" alt="Yayasan Namira" class="w-8 h-8 object-contain shrink-0" />
                                    <div class="leading-tight">
                                        <div class="text-xs font-extrabold text-slate-900">{{ displayStats[0].target }}+ Unit Pendidikan</div>
                                        <div class="text-[11px] text-slate-500 font-medium">Kraksaan · Dringu · Kota</div>
                                    </div>
                                </div>

                                <div
                                    data-depth="1.9"
                                    class="hero-collage-item float-slow-c inline-flex absolute right-[2%] top-[44%] px-3 py-1.5 rounded-full bg-[#009688] text-white text-[11px] font-bold shadow-md items-center gap-1.5 whitespace-nowrap"
                                >
                                    <span>Tumbuh Bersama</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 SEARCH & FILTER BAR (Compact Mobile Drawer + Desktop Unified Bar)
            ========================================================== -->
            <section class="relative z-20 -mt-3 lg:-mt-14 px-4 sm:px-6 lg:px-8">
                <div class="max-w-5xl mx-auto">
                    <form
                        @submit.prevent="applyFilters(true)"
                        class="bg-white rounded-2xl sm:rounded-[20px] border border-slate-200/80 lg:border-slate-100 shadow-[0_12px_36px_-14px_rgba(15,23,42,0.10)] p-2.5 sm:p-4"
                    >
                        <!-- MOBILE COMPACT SEARCH ROW -->
                        <div class="md:hidden space-y-2.5">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 flex items-center gap-2 px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200/80 focus-within:border-[#009688] focus-within:bg-white transition-colors">
                                    <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 shrink-0" />
                                    <input
                                        v-model="search"
                                        type="text"
                                        placeholder="Cari posisi, guru, staf..."
                                        class="w-full border-0 p-0 text-[15px] font-medium text-slate-800 placeholder:text-slate-400 focus:ring-0 bg-transparent"
                                    />
                                    <button
                                        v-if="search"
                                        type="button"
                                        @click="search = ''"
                                        aria-label="Hapus pencarian"
                                        class="text-slate-400 p-0.5"
                                    >
                                        <XMarkIcon class="w-4 h-4" />
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    @click="mobileFilterOpen = !mobileFilterOpen"
                                    class="relative px-3.5 py-2.5 min-h-[44px] rounded-xl border text-xs font-bold inline-flex items-center gap-1.5 shrink-0 transition-colors cursor-pointer"
                                    :class="
                                        mobileFilterOpen || activeFilterCount > 0
                                            ? 'bg-[#ECF9F4] border-[#009688]/40 text-[#007468]'
                                            : 'bg-white border-slate-200 text-slate-700'
                                    "
                                >
                                    <AdjustmentsHorizontalIcon class="w-4 h-4" />
                                    <span>Filter</span>
                                    <span
                                        v-if="activeFilterCount > 0"
                                        class="w-4 h-4 rounded-full bg-[#009688] text-white text-[10px] font-extrabold inline-flex items-center justify-center"
                                    >
                                        {{ activeFilterCount }}
                                    </span>
                                </button>
                            </div>

                            <!-- Collapsible Mobile Unit & Category Selectors -->
                            <AnimatePresence>
                                <Motion
                                    v-if="mobileFilterOpen"
                                    :initial="{ opacity: 0, height: 0 }"
                                    :animate="{ opacity: 1, height: 'auto' }"
                                    :exit="{ opacity: 0, height: 0 }"
                                    :transition="{ duration: 0.2 }"
                                    class="overflow-hidden"
                                >
                                    <div class="pt-2 pb-1 space-y-2.5 border-t border-slate-100">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Unit Pendidikan</label>
                                                <select
                                                    v-model="selectedUnit"
                                                    class="w-full rounded-xl border-slate-200 text-[15px] font-medium text-slate-800 py-2.5 focus:border-[#009688] focus:ring-[#009688]/20"
                                                >
                                                    <option value="all">Semua Unit</option>
                                                    <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                                        {{ unit.name }}
                                                    </option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Kategori</label>
                                                <select
                                                    v-model="selectedCategory"
                                                    class="w-full rounded-xl border-slate-200 text-[15px] font-medium text-slate-800 py-2.5 focus:border-[#009688] focus:ring-[#009688]/20"
                                                >
                                                    <option value="">Semua Kategori</option>
                                                    <option v-for="(label, key) in categories" :key="key" :value="key">
                                                        {{ label }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="submit"
                                                class="flex-1 py-2.5 min-h-[44px] rounded-xl bg-[#009688] text-white text-xs font-bold cursor-pointer"
                                            >
                                                Terapkan Filter
                                            </button>
                                            <button
                                                v-if="hasActiveFilters"
                                                type="button"
                                                @click="resetFilters"
                                                class="px-4 py-2.5 min-h-[44px] rounded-xl border border-slate-200 text-slate-600 text-xs font-bold cursor-pointer"
                                            >
                                                Reset
                                            </button>
                                        </div>
                                    </div>
                                </Motion>
                            </AnimatePresence>
                        </div>

                        <!-- DESKTOP UNIFIED 4-COLUMN STOCKBIT BAR -->
                        <div class="hidden md:grid md:grid-cols-12 gap-0 items-center">
                            <div class="md:col-span-4 px-3 border-r border-slate-100">
                                <label for="career-search" class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                                    Cari Posisi
                                </label>
                                <div class="relative flex items-center">
                                    <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 shrink-0 mr-2" />
                                    <input
                                        id="career-search"
                                        v-model="search"
                                        type="text"
                                        placeholder="Guru, Staf, Humas..."
                                        class="w-full border-0 p-0 text-sm font-semibold text-slate-800 placeholder:text-slate-400 focus:ring-0 bg-transparent"
                                    />
                                </div>
                            </div>

                            <div class="md:col-span-3 px-4 border-r border-slate-100">
                                <label for="career-unit" class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                                    Unit Pendidikan
                                </label>
                                <select
                                    id="career-unit"
                                    v-model="selectedUnit"
                                    @change="applyFilters(false)"
                                    class="w-full border-0 p-0 pr-6 text-sm font-semibold text-slate-800 focus:ring-0 bg-transparent cursor-pointer"
                                >
                                    <option value="all">Semua Unit</option>
                                    <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                        {{ unit.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="md:col-span-3 px-4">
                                <label for="career-category" class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                                    Kategori
                                </label>
                                <select
                                    id="career-category"
                                    v-model="selectedCategory"
                                    @change="applyFilters(false)"
                                    class="w-full border-0 p-0 pr-6 text-sm font-semibold text-slate-800 focus:ring-0 bg-transparent cursor-pointer"
                                >
                                    <option value="">Semua Kategori</option>
                                    <option v-for="(label, key) in categories" :key="key" :value="key">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>

                            <div class="md:col-span-2 pl-2">
                                <Motion :while-tap="{ scale: 0.98 }">
                                    <button
                                        type="submit"
                                        class="w-full py-3 px-4 rounded-xl bg-[#009688] hover:bg-[#007D71] text-white text-[13px] font-bold shadow-xs transition-colors cursor-pointer whitespace-nowrap"
                                    >
                                        Cari Lowongan
                                    </button>
                                </Motion>
                            </div>
                        </div>
                    </form>

                    <!-- Active Filters Hint -->
                    <div v-if="hasActiveFilters" class="mt-2.5 flex items-center justify-between px-2 text-xs text-slate-500">
                        <span>Filter aktif diterapkan.</span>
                        <button type="button" @click="resetFilters" class="font-bold text-[#008C7E] hover:underline cursor-pointer">
                            Reset Filter
                        </button>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 OPEN VACANCIES — Smart Adaptive Layout + Thumb-Friendly Mobile
            ========================================================== -->
            <section id="lowongan" class="pt-9 pb-14 sm:pt-16 sm:pb-24">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
                    <!-- Flash Notice -->
                    <div
                        v-if="$page.props.flash?.warning || $page.props.flash?.error"
                        class="p-4 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs font-semibold"
                    >
                        {{ $page.props.flash.warning || $page.props.flash.error }}
                    </div>

                    <!-- Section Header + Swipeable Quick Category Pills -->
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2.5">
                                <h2 class="text-xl sm:text-[1.75rem] tracking-tight text-slate-900">
                                    <span class="font-normal">Lowongan</span>
                                    <span class="font-extrabold"> Kerja Dibuka</span>
                                </h2>
                                <span class="px-2.5 py-0.5 rounded-full bg-[#E6F7F1] text-[#007468] text-xs font-bold">
                                    {{ vacancies.total }} Posisi
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-500">
                                Pilih posisi yang sesuai keahlian Anda dan kirim lamaran secara online.
                            </p>
                        </div>

                        <!-- Single-Line Horizontal Scroll Pills on Mobile -->
                        <div class="-mx-4 px-4 sm:mx-0 sm:px-0 overflow-x-auto no-scrollbar">
                            <div class="inline-flex flex-nowrap items-center gap-1 p-1 rounded-xl bg-slate-100/85 border border-slate-200/60">
                                <button
                                    v-for="tab in quickCategories"
                                    :key="tab.key"
                                    type="button"
                                    @click="selectQuickCategory(tab.key)"
                                    class="relative px-3.5 py-2 sm:py-1.5 text-xs font-bold rounded-lg transition-colors whitespace-nowrap cursor-pointer"
                                    :class="selectedCategory === tab.key ? 'text-slate-900' : 'text-slate-500 hover:text-slate-800'"
                                >
                                    <Motion
                                        v-if="selectedCategory === tab.key"
                                        layout-id="career-cat-pill"
                                        :transition="{ type: 'spring', stiffness: 420, damping: 32 }"
                                        class="absolute inset-0 bg-white rounded-lg shadow-2xs ring-1 ring-slate-200/60"
                                    />
                                    <span class="relative z-10">{{ tab.label }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- CASE 1: Empty State -->
                    <div
                        v-if="vacancyList.length === 0"
                        class="rounded-3xl border border-dashed border-slate-200 bg-[#FAFCFB] p-10 sm:p-16 text-center space-y-4"
                    >
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-[#E6F7F1] text-[#008C7E] mx-auto flex items-center justify-center">
                            <BriefcaseIcon class="w-6 h-6 sm:w-7 sm:h-7" />
                        </div>
                        <div class="space-y-1.5">
                            <h3 class="text-base sm:text-lg font-extrabold text-slate-900">Belum Ada Lowongan yang Cocok</h3>
                            <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto leading-relaxed">
                                {{ hasActiveFilters
                                    ? 'Tidak ditemukan posisi dengan kriteria filter saat ini. Coba ubah kata kunci atau reset filter.'
                                    : 'Saat ini belum ada lowongan terbuka. Pantau halaman ini secara berkala untuk kesempatan bergabung berikutnya.' }}
                            </p>
                        </div>
                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            @click="resetFilters"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#009688] hover:bg-[#007D71] text-white text-xs font-bold transition cursor-pointer"
                        >
                            Tampilkan Semua Lowongan
                        </button>
                    </div>

                    <!-- CASE 2: Single Active Vacancy — Editorial Spotlight Split Card -->
                    <div v-else-if="isSingleSpotlight" class="reveal-up">
                        <article
                            v-for="vacancy in vacancyList"
                            :key="vacancy.id"
                            class="rounded-3xl bg-white border border-slate-200/80 shadow-[0_12px_36px_-18px_rgba(15,23,42,0.09)] hover:border-[#009688]/40 transition-all overflow-hidden grid grid-cols-1 lg:grid-cols-12"
                        >
                            <!-- Left Visual: Compact Poster Preview on Mobile, Full Studio on Desktop -->
                            <div
                                v-if="vacancy.poster_url"
                                class="lg:col-span-5 relative bg-[#F2FAF7] p-4 sm:p-8 flex items-center justify-center border-b lg:border-b-0 lg:border-r border-slate-100 group"
                            >
                                <img
                                    :src="vacancy.poster_url"
                                    :alt="`Poster ${vacancy.title}`"
                                    @click="lightboxPoster = { url: vacancy.poster_url, title: vacancy.title }"
                                    class="max-h-[280px] sm:max-h-[430px] w-auto max-w-full object-contain rounded-2xl shadow-[0_14px_34px_-14px_rgba(15,23,42,0.22)] ring-1 ring-slate-900/5 transition-transform duration-300 group-hover:scale-[1.015] cursor-zoom-in"
                                />
                                <button
                                    type="button"
                                    @click="lightboxPoster = { url: vacancy.poster_url, title: vacancy.title }"
                                    class="absolute bottom-3 right-3 sm:bottom-4 sm:right-4 px-3 py-1.5 rounded-xl bg-white/95 hover:bg-white text-slate-800 border border-slate-200/80 shadow-sm text-[11px] font-bold inline-flex items-center gap-1.5 transition cursor-pointer"
                                >
                                    <MagnifyingGlassPlusIcon class="w-3.5 h-3.5 text-[#008C7E]" />
                                    <span>Perbesar Poster</span>
                                </button>
                            </div>

                            <div
                                v-else
                                class="lg:col-span-5 bg-[#EDF9F4] p-6 sm:p-8 flex flex-col justify-between border-b lg:border-b-0 lg:border-r border-teal-100/80"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-2xl bg-white p-2 shadow-2xs border border-teal-100 flex items-center justify-center">
                                        <img src="/images/landing/logo-yayasan.webp" alt="Namira" class="w-full h-full object-contain" />
                                    </div>
                                    <div>
                                        <div class="text-[11px] font-bold uppercase tracking-wider text-[#007468]">Rekrutmen Resmi</div>
                                        <div class="text-sm font-extrabold text-slate-900">{{ vacancy.unit?.name || 'Yayasan Namira' }}</div>
                                    </div>
                                </div>
                                <div class="my-5 rounded-2xl overflow-hidden shadow-sm border border-white hidden sm:block">
                                    <img :src="heroPhotos.sd" alt="Kampus Namira" class="w-full h-44 object-cover" />
                                </div>
                                <p class="text-xs text-slate-600 leading-relaxed mt-3 sm:mt-0">
                                    Seluruh berkas lamaran dikirim langsung melalui portal resmi tanpa dipungut biaya.
                                </p>
                            </div>

                            <!-- Right Editorial Content -->
                            <div class="lg:col-span-7 p-5 sm:p-9 lg:p-10 flex flex-col justify-between gap-5 sm:gap-7">
                                <div class="space-y-4 sm:space-y-5">
                                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                                        <span class="px-2.5 sm:px-3 py-1 rounded-full bg-[#E6F7F1] text-[#007468] text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wide">
                                            {{ vacancy.unit?.name || 'Yayasan Namira' }}
                                        </span>
                                        <span class="px-2.5 sm:px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-[10px] sm:text-[11px] font-bold">
                                            {{ vacancy.type_label }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] sm:text-[11px] font-semibold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Aktif
                                        </span>
                                    </div>

                                    <div class="space-y-2">
                                        <h3 class="text-xl sm:text-[1.85rem] font-extrabold text-slate-950 tracking-tight leading-snug hover:text-[#008C7E] transition-colors">
                                            <Link :href="route('careers.show', vacancy.slug)">
                                                {{ vacancy.title }}
                                            </Link>
                                        </h3>
                                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                            {{
                                                excerptText(vacancy.description || vacancy.requirements, 190)
                                                || 'Terbuka bagi talenta terbaik yang berdedikasi mendidik dan bertumbuh bersama keluarga besar Sekolah Islam Namira. Periksa kualifikasi pada poster atau langsung lamar online.'
                                            }}
                                        </p>
                                    </div>

                                    <!-- Compact 2x2 Key Meta Grid (Even on Mobile!) -->
                                    <div class="grid grid-cols-2 gap-2.5 sm:gap-3 pt-0.5">
                                        <div class="p-3 sm:p-3.5 rounded-2xl bg-[#F8FAFC] border border-slate-100">
                                            <div class="flex items-center gap-1.5 text-[10px] sm:text-[11px] font-semibold text-slate-400">
                                                <BuildingOffice2Icon class="w-3.5 h-3.5 text-[#008C7E] shrink-0" />
                                                <span>Kategori</span>
                                            </div>
                                            <div class="text-xs font-bold text-slate-800 mt-1 truncate">{{ vacancy.category_label }}</div>
                                        </div>

                                        <div class="p-3 sm:p-3.5 rounded-2xl bg-[#F8FAFC] border border-slate-100">
                                            <div class="flex items-center gap-1.5 text-[10px] sm:text-[11px] font-semibold text-slate-400">
                                                <UserGroupIcon class="w-3.5 h-3.5 text-[#008C7E] shrink-0" />
                                                <span>Kuota</span>
                                            </div>
                                            <div class="text-xs font-bold text-slate-800 mt-1">{{ vacancy.quota }} Orang</div>
                                        </div>

                                        <div class="p-3 sm:p-3.5 rounded-2xl bg-[#F8FAFC] border border-slate-100">
                                            <div class="flex items-center gap-1.5 text-[10px] sm:text-[11px] font-semibold text-slate-400">
                                                <ClockIcon class="w-3.5 h-3.5 text-amber-500 shrink-0" />
                                                <span>Batas Akhir</span>
                                            </div>
                                            <div class="text-xs font-bold text-slate-800 mt-1 truncate">{{ formatDate(vacancy.deadline) }}</div>
                                        </div>

                                        <div class="p-3 sm:p-3.5 rounded-2xl bg-[#F8FAFC] border border-slate-100">
                                            <div class="flex items-center gap-1.5 text-[10px] sm:text-[11px] font-semibold text-slate-400">
                                                <MapPinIcon class="w-3.5 h-3.5 text-[#008C7E] shrink-0" />
                                                <span>Penempatan</span>
                                            </div>
                                            <div class="text-xs font-bold text-slate-800 mt-1 truncate">
                                                {{ vacancy.placement_address || 'Probolinggo, Jatim' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Primary Actions (Full-Width Clean Stack on Mobile) -->
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2.5 sm:gap-3 pt-3 border-t border-slate-100">
                                    <Link
                                        :href="route('careers.show', vacancy.slug)"
                                        class="w-full sm:w-auto px-6 py-3.5 min-h-[48px] rounded-xl bg-[#009688] hover:bg-[#007D71] text-white text-xs sm:text-sm font-bold shadow-xs transition-all inline-flex items-center justify-center gap-2 group"
                                    >
                                        <span>Lihat Detail &amp; Lamar Online</span>
                                        <ArrowRightIcon class="w-4 h-4 transition-transform group-hover:translate-x-1" />
                                    </Link>

                                    <a
                                        v-if="formatWhatsappHref(vacancy)"
                                        :href="formatWhatsappHref(vacancy)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="w-full sm:w-auto px-4 py-3 min-h-[46px] rounded-xl border border-slate-200 hover:border-[#009688] hover:text-[#008C7E] text-slate-700 text-xs sm:text-sm font-bold inline-flex items-center justify-center gap-2 transition-colors"
                                    >
                                        <ChatBubbleLeftRightIcon class="w-4 h-4 text-[#009688]" />
                                        <span>Tanya Admin Unit</span>
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>

                    <!-- CASE 3: Multiple Vacancies -->
                    <div
                        v-else
                        class="stagger-group grid gap-5 sm:gap-6"
                        :class="isTwoUpLayout ? 'grid-cols-1 lg:grid-cols-2' : 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'"
                    >
                        <article
                            v-for="vacancy in vacancyList"
                            :key="vacancy.id"
                            class="stagger-card bg-white rounded-3xl border border-slate-200/80 shadow-[0_8px_28px_-14px_rgba(15,23,42,0.07)] hover:shadow-[0_18px_40px_-16px_rgba(0,150,136,0.16)] hover:border-[#009688]/45 transition-all flex flex-col justify-between overflow-hidden"
                        >
                            <div>
                                <div
                                    v-if="vacancy.poster_url"
                                    class="relative h-56 sm:h-64 bg-[#F2FAF7] border-b border-slate-100 overflow-hidden flex items-center justify-center p-4 group"
                                >
                                    <img
                                        :src="vacancy.poster_url"
                                        :alt="`Poster ${vacancy.title}`"
                                        @click="lightboxPoster = { url: vacancy.poster_url, title: vacancy.title }"
                                        class="max-h-full max-w-full object-contain rounded-xl shadow-md ring-1 ring-slate-900/5 transition-transform duration-300 group-hover:scale-[1.02] cursor-zoom-in"
                                    />
                                    <button
                                        type="button"
                                        @click="lightboxPoster = { url: vacancy.poster_url, title: vacancy.title }"
                                        class="absolute bottom-3 right-3 px-2.5 py-1.5 rounded-xl bg-white/95 hover:bg-white text-slate-800 border border-slate-200 text-[10px] font-bold inline-flex items-center gap-1 shadow-xs transition cursor-pointer"
                                    >
                                        <MagnifyingGlassPlusIcon class="w-3.5 h-3.5 text-[#008C7E]" />
                                        <span>Perbesar</span>
                                    </button>
                                </div>

                                <div class="p-5 sm:p-6 space-y-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2 pb-2.5 border-b border-slate-100">
                                        <span class="px-2.5 py-1 rounded-full bg-[#E6F7F1] text-[#007468] text-[10px] font-extrabold uppercase tracking-wide">
                                            {{ vacancy.unit?.name || 'Yayasan Namira' }}
                                        </span>
                                        <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold">
                                            {{ vacancy.type_label }}
                                        </span>
                                    </div>

                                    <h3 class="text-base sm:text-lg font-extrabold text-slate-950 leading-snug tracking-tight hover:text-[#008C7E] transition-colors">
                                        <Link :href="route('careers.show', vacancy.slug)">
                                            {{ vacancy.title }}
                                        </Link>
                                    </h3>

                                    <div class="grid grid-cols-2 sm:grid-cols-1 gap-1.5 text-xs text-slate-500">
                                        <div class="flex items-center gap-1.5 truncate">
                                            <BuildingOffice2Icon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                            <span class="truncate">{{ vacancy.category_label }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <UserGroupIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                            <span>Kuota: <strong class="text-slate-800">{{ vacancy.quota }}</strong></span>
                                        </div>
                                        <div v-if="vacancy.placement_address" class="col-span-2 sm:col-span-1 flex items-center gap-1.5">
                                            <MapPinIcon class="w-3.5 h-3.5 text-[#008C7E] shrink-0" />
                                            <span class="truncate">{{ vacancy.placement_address }}</span>
                                        </div>
                                        <div class="col-span-2 sm:col-span-1 flex items-center gap-1.5">
                                            <ClockIcon class="w-3.5 h-3.5 text-amber-500 shrink-0" />
                                            <span>Batas: <strong class="text-slate-800">{{ formatDate(vacancy.deadline) }}</strong></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="px-5 sm:px-6 pb-5 sm:pb-6 pt-1">
                                <Link
                                    :href="route('careers.show', vacancy.slug)"
                                    class="w-full py-3 min-h-[44px] rounded-xl bg-[#009688] hover:bg-[#007D71] text-white font-bold text-xs shadow-2xs transition-colors flex items-center justify-center gap-2 group"
                                >
                                    <span>Lihat Detail &amp; Lamar Online</span>
                                    <ArrowRightIcon class="w-4 h-4 transition-transform group-hover:translate-x-1" />
                                </Link>
                            </div>
                        </article>
                    </div>

                    <!-- Pagination -->
                    <div v-if="vacancies.links && vacancies.links.length > 3" class="pt-4">
                        <Pagination :links="vacancies.links" />
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 ECOSYSTEM — Inline Photo on Mobile + 2-Col Preview on Desktop
            ========================================================== -->
            <section id="ekosistem" class="py-12 sm:py-20 lg:py-24 border-t border-slate-100">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-14 items-center">
                        <!-- Desktop-Only Left Dynamic Campus Visual Showcase -->
                        <div class="hidden lg:block lg:col-span-6 reveal-up">
                            <div class="relative mx-auto max-w-[480px]">
                                <div aria-hidden="true" class="absolute -inset-5 rounded-[36px] bg-[#ECF9F4] -rotate-2"></div>

                                <AnimatePresence mode="wait">
                                    <Motion
                                        :key="activeEcosystem.id"
                                        :initial="{ opacity: 0, y: 12, scale: 0.98 }"
                                        :animate="{ opacity: 1, y: 0, scale: 1 }"
                                        :exit="{ opacity: 0, y: -10, scale: 0.98 }"
                                        :transition="{ duration: 0.28, ease: 'easeOut' }"
                                        class="relative bg-white rounded-[28px] border border-slate-100 shadow-[0_20px_50px_-20px_rgba(15,23,42,0.14)] overflow-hidden"
                                    >
                                        <div class="relative h-64 overflow-hidden bg-slate-100">
                                            <img :src="activeEcosystem.photo" :alt="activeEcosystem.name" class="w-full h-full object-cover" />
                                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/55 via-slate-900/10 to-transparent"></div>
                                            <div class="absolute top-4 left-4 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/95 backdrop-blur-xs text-[11px] font-bold text-slate-800 shadow-xs">
                                                <MapPinIcon class="w-3.5 h-3.5 text-[#008C7E]" />
                                                <span>{{ activeEcosystem.location }}</span>
                                            </div>
                                            <div class="absolute bottom-4 left-4 right-4 flex items-center gap-3">
                                                <div class="w-11 h-11 rounded-2xl bg-white p-1.5 shadow-md shrink-0 flex items-center justify-center">
                                                    <img :src="activeEcosystem.logo" :alt="activeEcosystem.name" class="w-full h-full object-contain" />
                                                </div>
                                                <div class="text-white min-w-0">
                                                    <div class="text-sm font-extrabold truncate">{{ activeEcosystem.name }}</div>
                                                    <div class="text-[11px] text-white/85 truncate">{{ activeEcosystem.tagline }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="p-6">
                                            <div class="flex flex-wrap gap-2">
                                                <span
                                                    v-for="tag in activeEcosystem.highlights"
                                                    :key="tag"
                                                    class="px-2.5 py-1 rounded-lg bg-[#F2FAF7] text-[#007468] text-[11px] font-bold"
                                                >
                                                    {{ tag }}
                                                </span>
                                            </div>
                                        </div>
                                    </Motion>
                                </AnimatePresence>
                            </div>
                        </div>

                        <!-- Right Accordion (With Inline Photo Reveal on Mobile!) -->
                        <div class="lg:col-span-6 space-y-5 sm:space-y-6 reveal-up">
                            <div class="space-y-2">
                                <h2 class="text-xl sm:text-[2rem] tracking-tight text-slate-900 leading-tight">
                                    <span class="font-normal">Ekosistem </span>
                                    <span class="font-extrabold">Pendidikan Kami</span>
                                </h2>
                                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                    Yayasan Namira menaungi jenjang pendidikan terintegrasi yang membuka ruang kolaborasi terbaik bagi setiap pendidik.
                                </p>
                            </div>

                            <div class="space-y-2.5">
                                <div
                                    v-for="(item, idx) in ecosystems"
                                    :key="item.id"
                                    class="rounded-2xl border transition-all overflow-hidden"
                                    :class="
                                        activeEcosystemIndex === idx
                                            ? 'bg-white border-[#009688]/35 shadow-[0_10px_28px_-14px_rgba(0,150,136,0.18)]'
                                            : 'bg-[#FAFCFB] border-slate-100 hover:border-slate-200'
                                    "
                                >
                                    <button
                                        type="button"
                                        @click="activeEcosystemIndex = idx"
                                        class="w-full px-4 sm:px-5 py-3.5 sm:py-4 flex items-center justify-between gap-3 text-left cursor-pointer"
                                    >
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-8 h-8 rounded-xl bg-white border border-slate-100 p-1 flex items-center justify-center shrink-0">
                                                <img :src="item.logo" :alt="item.name" class="w-full h-full object-contain" />
                                            </div>
                                            <span
                                                class="text-[13px] sm:text-sm font-bold truncate"
                                                :class="activeEcosystemIndex === idx ? 'text-slate-950' : 'text-slate-700'"
                                            >
                                                {{ item.name }}
                                            </span>
                                        </div>
                                        <ChevronDownIcon
                                            class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-300"
                                            :class="activeEcosystemIndex === idx ? 'rotate-180 text-[#008C7E]' : ''"
                                        />
                                    </button>

                                    <AnimatePresence initial="false">
                                        <Motion
                                            v-if="activeEcosystemIndex === idx"
                                            :initial="{ opacity: 0, height: 0 }"
                                            :animate="{ opacity: 1, height: 'auto' }"
                                            :exit="{ opacity: 0, height: 0 }"
                                            :transition="{ duration: 0.24, ease: 'easeOut' }"
                                            class="overflow-hidden"
                                        >
                                            <div class="px-4 sm:px-5 pb-4 lg:pl-16 space-y-3">
                                                <!-- Mobile-Only Inline Campus Preview directly inside tapped accordion! -->
                                                <div class="lg:hidden relative h-40 rounded-xl overflow-hidden bg-slate-100">
                                                    <img :src="item.photo" :alt="item.name" class="w-full h-full object-cover" />
                                                    <div class="absolute bottom-2 left-2 px-2.5 py-1 rounded-full bg-white/95 text-[10px] font-bold text-slate-800 inline-flex items-center gap-1 shadow-xs">
                                                        <MapPinIcon class="w-3 h-3 text-[#008C7E]" />
                                                        <span>{{ item.location }}</span>
                                                    </div>
                                                </div>

                                                <p class="text-xs sm:text-[13px] text-slate-600 leading-relaxed">
                                                    {{ item.summary }}
                                                </p>

                                                <div class="lg:hidden flex flex-wrap gap-1.5">
                                                    <span
                                                        v-for="tag in item.highlights"
                                                        :key="tag"
                                                        class="px-2 py-0.5 rounded-md bg-[#F2FAF7] text-[#007468] text-[10px] font-bold"
                                                    >
                                                        {{ tag }}
                                                    </span>
                                                </div>
                                            </div>
                                        </Motion>
                                    </AnimatePresence>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 HOW WE GROW TOGETHER — Snap-Scroll Carousel on Mobile, 4-Col Desktop
            ========================================================== -->
            <section class="py-12 sm:py-20">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-7 sm:space-y-12">
                    <div class="flex items-end justify-between gap-4 sm:block sm:text-center max-w-2xl sm:mx-auto reveal-up">
                        <div class="space-y-1.5">
                            <h2 class="text-xl sm:text-[2rem] tracking-tight text-slate-900">
                                <span class="font-normal">Bagaimana Kami </span>
                                <span class="font-extrabold">Tumbuh Bersama</span>
                            </h2>
                            <p class="text-xs sm:text-sm text-slate-500">
                                Ruang bertumbuh yang sehat agar setiap pendidik dan staf memberi karya terbaiknya.
                            </p>
                        </div>
                        <span class="sm:hidden text-[11px] font-bold text-[#008C7E] shrink-0">Geser →</span>
                    </div>

                    <div class="stagger-group flex sm:grid sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-5 overflow-x-auto sm:overflow-visible snap-x snap-mandatory -mx-4 px-4 sm:mx-0 sm:px-0 pb-2 sm:pb-0 no-scrollbar">
                        <div
                            v-for="item in benefits"
                            :key="item.title"
                            class="stagger-card w-[76vw] sm:w-auto shrink-0 snap-start rounded-2xl bg-white border border-slate-100 p-5 sm:p-6 shadow-[0_8px_24px_-16px_rgba(15,23,42,0.08)] hover:shadow-[0_16px_38px_-16px_rgba(0,150,136,0.14)] hover:border-[#009688]/30 transition-all space-y-3.5 sm:space-y-4"
                        >
                            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl flex items-center justify-center" :class="item.tint">
                                <component :is="item.icon" class="w-5 h-5 sm:w-6 sm:h-6" />
                            </div>
                            <div class="space-y-1.5">
                                <h3 class="text-sm sm:text-[15px] font-extrabold text-slate-900">{{ item.title }}</h3>
                                <p class="text-xs sm:text-[13px] text-slate-500 leading-relaxed">{{ item.desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 REACHING FOR NEW HEIGHTS — 2x2 Scannable Mobile Grid + Mint Wave
            ========================================================== -->
            <section class="relative my-4 bg-[#EDF9F4] py-14 sm:py-24 overflow-hidden">
                <svg
                    aria-hidden="true"
                    viewBox="0 0 1440 54"
                    fill="none"
                    preserveAspectRatio="none"
                    class="absolute top-0 left-0 w-full h-6 sm:h-12 text-white"
                >
                    <path d="M0 0H1440V18C1080 58 360 58 0 18V0Z" fill="currentColor" />
                </svg>

                <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 sm:space-y-12">
                    <div class="text-center max-w-2xl mx-auto space-y-2 reveal-up">
                        <h2 class="text-xl sm:text-[2rem] tracking-tight text-slate-900">
                            <span class="font-normal">Melangkah Menuju </span>
                            <span class="font-extrabold">Dampak Lebih Luas</span>
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-600">
                            Kepercayaan ribuan keluarga menjadi amanah yang kami jaga bersama setiap hari.
                        </p>
                    </div>

                    <div class="stagger-group grid grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-6 sm:gap-8">
                        <div
                            v-for="metric in displayStats"
                            :key="metric.label"
                            class="stagger-card pl-3.5 sm:pl-5 border-l-2 border-[#009688]/25 space-y-1.5 sm:space-y-2"
                        >
                            <div
                                class="stat-counter text-2xl sm:text-[2.2rem] font-extrabold tracking-tight text-slate-950"
                                :data-target="metric.target"
                                :data-suffix="metric.suffix"
                            >
                                {{ metric.target.toLocaleString('id-ID') }}{{ metric.suffix }}
                            </div>
                            <div class="text-[11px] sm:text-xs font-extrabold uppercase tracking-wider text-[#007468]">
                                {{ metric.label }}
                            </div>
                            <p class="text-[11px] sm:text-xs text-slate-600 leading-relaxed">
                                {{ metric.desc }}
                            </p>
                        </div>
                    </div>
                </div>

                <svg
                    aria-hidden="true"
                    viewBox="0 0 1440 54"
                    fill="none"
                    preserveAspectRatio="none"
                    class="absolute bottom-0 left-0 w-full h-6 sm:h-12 text-white"
                >
                    <path d="M0 54H1440V36C1080 -4 360 -4 0 36V54Z" fill="currentColor" />
                </svg>
            </section>

            <!-- =========================================================
                 WE EMBRACE OUR VALUES
            ========================================================== -->
            <section id="budaya" class="py-12 sm:py-24">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10 sm:space-y-14">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8 items-center reveal-up">
                        <div class="lg:col-span-6 space-y-2.5">
                            <h2 class="text-xl sm:text-[2rem] tracking-tight text-slate-900">
                                <span class="font-normal">Kami Menjunjung </span>
                                <span class="font-extrabold">Nilai NAMIRA</span>
                            </h2>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed max-w-lg">
                                Budaya kerja kami berakar pada keteladanan, kehangatan, dan profesionalisme — menciptakan lingkungan sekolah yang menyenangkan bagi siswa sekaligus bermakna bagi pendidik.
                            </p>
                        </div>

                        <div class="lg:col-span-6 flex items-center lg:justify-end">
                            <div class="relative flex items-center gap-3 max-w-md w-full">
                                <div class="w-1/2 h-28 sm:h-40 rounded-2xl overflow-hidden shadow-md ring-1 ring-slate-100">
                                    <img :src="heroPhotos.sd" alt="Suasana Kampus SD Namira" class="w-full h-full object-cover" />
                                </div>
                                <div class="w-1/2 h-28 sm:h-40 rounded-2xl overflow-hidden shadow-md ring-1 ring-slate-100 sm:translate-y-3">
                                    <img :src="heroPhotos.daycare" alt="Lingkungan Belajar Namira" class="w-full h-full object-cover" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="stagger-group grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 sm:gap-8 pt-1">
                        <div
                            v-for="val in values"
                            :key="val.title"
                            class="stagger-card flex sm:block items-start gap-3.5 sm:space-y-3.5"
                        >
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" :class="val.tint">
                                <component :is="val.icon" class="w-5 h-5" />
                            </div>
                            <div class="space-y-1">
                                <h3 class="text-sm sm:text-[15px] font-extrabold text-slate-900">{{ val.title }}</h3>
                                <p class="text-xs sm:text-[13px] text-slate-500 leading-relaxed">{{ val.desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 TRANSPARENT SELECTION JOURNEY & TESTIMONIALS
            ========================================================== -->
            <section id="seleksi" class="bg-[#F4FBF8] py-12 sm:py-24 border-y border-[#E1F3EC]">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12 sm:space-y-16">
                    <!-- 4-Step Transparent Selection Journey -->
                    <div class="space-y-7 sm:space-y-10">
                        <div class="flex items-end justify-between gap-4 sm:block sm:text-center max-w-2xl sm:mx-auto reveal-up">
                            <div class="space-y-1.5">
                                <h2 class="text-xl sm:text-[2rem] tracking-tight text-slate-900">
                                    <span class="font-normal">Alur Seleksi </span>
                                    <span class="font-extrabold">Transparan</span>
                                </h2>
                                <p class="text-xs sm:text-sm text-slate-600">
                                    Empat tahapan ringkas mulai dari pengiriman lamaran hingga onboarding di unit Namira.
                                </p>
                            </div>
                            <span class="sm:hidden text-[11px] font-bold text-[#008C7E] shrink-0">Geser →</span>
                        </div>

                        <div class="stagger-group flex sm:grid sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-5 overflow-x-auto sm:overflow-visible snap-x snap-mandatory -mx-4 px-4 sm:mx-0 sm:px-0 pb-2 sm:pb-0 no-scrollbar">
                            <div
                                v-for="step in selectionSteps"
                                :key="step.num"
                                class="stagger-card w-[76vw] sm:w-auto shrink-0 snap-start bg-white rounded-2xl border border-slate-100 p-5 sm:p-6 shadow-[0_8px_24px_-16px_rgba(15,23,42,0.08)] space-y-3.5"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="px-2.5 py-1 rounded-lg bg-[#E6F7F1] text-[#007468] text-xs font-extrabold font-mono">
                                        {{ step.num }}
                                    </span>
                                    <component :is="step.icon" class="w-5 h-5 text-slate-400" />
                                </div>
                                <div class="space-y-1.5">
                                    <h3 class="text-sm sm:text-[15px] font-extrabold text-slate-900">{{ step.title }}</h3>
                                    <p class="text-xs text-slate-500 leading-relaxed">{{ step.desc }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Live Published Testimonials Strip -->
                    <div v-if="testimonials && testimonials.length > 0" class="space-y-6 sm:space-y-8 pt-2">
                        <div class="flex items-end justify-between gap-4 sm:block sm:text-center max-w-xl sm:mx-auto reveal-up">
                            <h3 class="text-lg sm:text-2xl tracking-tight text-slate-900">
                                <span class="font-normal">Kata </span>
                                <span class="font-extrabold">Keluarga Besar Namira</span>
                            </h3>
                            <span class="sm:hidden text-[11px] font-bold text-[#008C7E] shrink-0">Geser →</span>
                        </div>

                        <div class="flex md:grid md:grid-cols-3 gap-3.5 sm:gap-5 overflow-x-auto md:overflow-visible snap-x snap-mandatory -mx-4 px-4 md:mx-0 md:px-0 pb-2 md:pb-0 no-scrollbar">
                            <div
                                v-for="t in testimonials.slice(0, 3)"
                                :key="t.id"
                                class="w-[82vw] md:w-auto shrink-0 snap-start bg-white rounded-2xl border border-slate-100 p-5 sm:p-6 shadow-2xs flex flex-col justify-between gap-4"
                            >
                                <p class="text-xs sm:text-[13px] text-slate-600 leading-relaxed line-clamp-5">
                                    “{{ t.quote }}”
                                </p>
                                <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
                                    <img
                                        v-if="t.photo_url"
                                        :src="t.photo_url"
                                        :alt="t.name"
                                        class="w-9 h-9 rounded-full object-cover border border-slate-100 shrink-0"
                                    />
                                    <div v-else class="w-9 h-9 rounded-full bg-[#E6F7F1] text-[#007468] font-extrabold text-xs flex items-center justify-center shrink-0">
                                        {{ t.name?.charAt(0) || 'N' }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-extrabold text-slate-900 truncate">{{ t.name }}</div>
                                        <div class="text-[11px] text-slate-500 truncate">
                                            {{ t.role_or_title }}<span v-if="t.unit_name"> · {{ t.unit_name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 FAQ REKRUTMEN
            ========================================================== -->
            <section class="py-12 sm:py-24">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-7 sm:space-y-10">
                    <div class="text-center space-y-1.5 reveal-up">
                        <h2 class="text-xl sm:text-[1.85rem] tracking-tight text-slate-900">
                            <span class="font-normal">Informasi &amp; </span>
                            <span class="font-extrabold">Pertanyaan Umum</span>
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500">
                            Hal-hal yang paling sering ditanyakan calon pelamar sebelum mengirimkan berkas.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-5 sm:gap-8 items-start">
                        <div class="md:col-span-3 -mx-4 px-4 md:mx-0 md:px-0 flex md:flex-col gap-2 overflow-x-auto no-scrollbar">
                            <button
                                v-for="tab in faqTabs"
                                :key="tab.key"
                                type="button"
                                @click="activeFaqTab = tab.key"
                                class="relative px-4 py-2.5 min-h-[42px] text-left text-xs sm:text-sm font-bold rounded-xl transition-colors whitespace-nowrap shrink-0 cursor-pointer"
                                :class="
                                    activeFaqTab === tab.key
                                        ? 'text-[#007468] bg-[#ECF9F4]'
                                        : 'text-slate-500 bg-slate-50 md:bg-transparent hover:text-slate-900 hover:bg-slate-50'
                                "
                            >
                                <Motion
                                    v-if="activeFaqTab === tab.key"
                                    layout-id="faq-tab-indicator"
                                    :transition="{ type: 'spring', stiffness: 450, damping: 34 }"
                                    class="hidden md:block absolute left-0 top-2 bottom-2 w-1 rounded-full bg-[#009688]"
                                />
                                <span>{{ tab.label }}</span>
                            </button>
                        </div>

                        <div class="md:col-span-9">
                            <AnimatePresence mode="wait">
                                <Motion
                                    :key="activeFaqTab"
                                    :initial="{ opacity: 0, y: 8 }"
                                    :animate="{ opacity: 1, y: 0 }"
                                    :exit="{ opacity: 0, y: -6 }"
                                    :transition="{ duration: 0.2 }"
                                    class="space-y-3 sm:space-y-4"
                                >
                                    <div
                                        v-for="item in currentFaqs"
                                        :key="item.q"
                                        class="rounded-2xl bg-white border border-slate-100 p-4 sm:p-6 shadow-2xs space-y-1.5"
                                    >
                                        <h3 class="text-[13.5px] sm:text-sm font-extrabold text-slate-900">{{ item.q }}</h3>
                                        <p class="text-xs sm:text-[13px] text-slate-600 leading-relaxed">{{ item.a }}</p>
                                    </div>
                                </Motion>
                            </AnimatePresence>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Airy Minimal White Editorial Footer -->
        <footer class="bg-white border-t border-slate-100 py-10 sm:py-14">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8 sm:gap-10 pb-8 sm:pb-10 border-b border-slate-100">
                    <div class="md:col-span-5 space-y-3">
                        <div class="flex items-center gap-2.5">
                            <img
                                :src="$page.props.app_settings?.app_logo || '/images/landing/logo-yayasan.webp'"
                                alt="Logo Yayasan Namira"
                                class="w-8 h-8 object-contain"
                            />
                            <div class="flex items-baseline gap-1">
                                <span class="font-namira text-xl text-[#1a4373] lowercase leading-none">namira</span>
                                <span class="font-school text-xs text-slate-500 uppercase font-extrabold leading-none">SCHOOL</span>
                            </div>
                            <span class="h-4 w-px bg-slate-200 mx-1"></span>
                            <span class="text-xs font-semibold text-slate-400">Karir</span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed max-w-sm">
                            Lembaga Pendidikan Islam Terpadu &amp; Modern di bawah naungan Yayasan Namira — melayani Daycare, KB, TK, SD, dan SMP di Probolinggo, Jawa Timur.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 md:contents gap-6">
                        <div class="md:col-span-2 space-y-2.5">
                            <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Tautan</h4>
                            <ul class="space-y-2 text-xs text-slate-500">
                                <li><Link href="/" class="hover:text-[#008C7E] transition-colors">Beranda</Link></li>
                                <li><Link :href="route('spmb.index')" class="hover:text-[#008C7E] transition-colors">SPMB Online</Link></li>
                                <li><Link :href="route('news.index')" class="hover:text-[#008C7E] transition-colors">Berita &amp; Artikel</Link></li>
                                <li><Link :href="route('events.index')" class="hover:text-[#008C7E] transition-colors">Kegiatan Sekolah</Link></li>
                            </ul>
                        </div>

                        <div class="md:col-span-2 space-y-2.5">
                            <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Unit Kami</h4>
                            <ul class="space-y-2 text-xs text-slate-500">
                                <li>SD Namira Kraksaan &amp; Kota</li>
                                <li>SMP Namira</li>
                                <li>TK &amp; KB Namira</li>
                                <li>Daycare &amp; Pavlov</li>
                            </ul>
                        </div>
                    </div>

                    <div class="md:col-span-3 space-y-2.5">
                        <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Kontak Rekrutmen</h4>
                        <div class="space-y-1 text-xs text-slate-500 leading-relaxed">
                            <p>Email: <a href="mailto:hr@namiraschool.com" class="font-semibold text-slate-700 hover:text-[#008C7E]">hr@namiraschool.com</a></p>
                            <p>Kraksaan, Dringu &amp; Kota Probolinggo, Jawa Timur</p>
                        </div>
                    </div>
                </div>

                <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px] sm:text-xs text-slate-400 text-center sm:text-left">
                    <span>© {{ new Date().getFullYear() }} Yayasan Sekolah Islam Namira. Seluruh hak cipta dilindungi.</span>
                    <span>Proses rekrutmen resmi tidak dipungut biaya apa pun.</span>
                </div>
            </div>
        </footer>

        <!-- Animated Poster Lightbox -->
        <Teleport to="body">
            <AnimatePresence>
                <Motion
                    v-if="lightboxPoster"
                    :initial="{ opacity: 0 }"
                    :animate="{ opacity: 1 }"
                    :exit="{ opacity: 0 }"
                    :transition="{ duration: 0.2 }"
                    class="fixed inset-0 z-[120] bg-slate-950/90 backdrop-blur-sm flex flex-col items-center justify-center p-3 sm:p-4"
                    @click.self="lightboxPoster = null"
                >
                    <Motion
                        :initial="{ opacity: 0, scale: 0.94, y: 12 }"
                        :animate="{ opacity: 1, scale: 1, y: 0 }"
                        :exit="{ opacity: 0, scale: 0.95 }"
                        :transition="{ duration: 0.22, ease: 'easeOut' }"
                        class="w-full max-w-2xl flex flex-col items-center"
                    >
                        <div class="w-full flex items-center justify-between text-white mb-3 gap-3">
                            <h4 class="font-bold text-xs sm:text-sm truncate">{{ lightboxPoster.title }}</h4>
                            <div class="flex items-center gap-2 shrink-0">
                                <a
                                    :href="lightboxPoster.url"
                                    download
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="px-3 py-2 rounded-xl bg-white/15 hover:bg-white/25 text-white text-xs font-bold inline-flex items-center gap-1.5 transition"
                                >
                                    <ArrowDownTrayIcon class="w-4 h-4" />
                                    <span>Unduh</span>
                                </a>
                                <button
                                    type="button"
                                    @click="lightboxPoster = null"
                                    aria-label="Tutup poster"
                                    class="p-2 rounded-xl bg-white/15 hover:bg-white/25 text-white transition cursor-pointer"
                                >
                                    <XMarkIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                        <img
                            :src="lightboxPoster.url"
                            :alt="lightboxPoster.title"
                            class="max-h-[82vh] max-w-full object-contain rounded-2xl shadow-2xl border border-white/15"
                        />
                    </Motion>
                </Motion>
            </AnimatePresence>
        </Teleport>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
