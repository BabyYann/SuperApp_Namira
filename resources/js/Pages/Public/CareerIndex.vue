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

const hasActiveFilters = computed(
    () => Boolean(search.value?.trim()) || (selectedUnit.value && selectedUnit.value !== 'all') || Boolean(selectedCategory.value)
);

const applyFilters = (shouldScroll = false) => {
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
    return dayjs(dateStr).format('DD MMMM YYYY');
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
            desc: 'Mulai dari Daycare, KB, TK, SD, hingga SMP dalam satu naungan Yayasan Namira.',
        },
        {
            target: educatorsCount,
            suffix: '+',
            label: 'Pendidik & Staf',
            desc: 'Kolaborasi guru profesional, pengajar Al-Qur’an Metode Ummi, dan tenaga kependidikan.',
        },
        {
            target: studentsCount,
            suffix: '+',
            label: 'Peserta Didik',
            desc: 'Generasi Qur’ani yang bertumbuh setiap hari di seluruh kampus Sekolah Islam Namira.',
        },
        {
            target: 2,
            suffix: ' Wilayah',
            label: 'Kab. & Kota Probolinggo',
            desc: 'Hadir melayani masyarakat di Kraksaan, Dringu, dan Kota Probolinggo, Jawa Timur.',
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

// Interactive Ecosystem Accordion (Stockbit "Our Growing Ecosystem" pattern)
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
        highlights: ['Metode Ummi & Tahfidz', 'Pembelajaran Aktif & Proyek', '2 Kampus: Kraksaan & Kota'],
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
        highlights: ['Program Bilingual & Riset', 'Tahfidz & Adab Remaja', 'Full Day & Pembinaan Karakter'],
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
        highlights: ['Stimulasi Motorik & Sensorik', 'Pembiasaan Doa & Adab', '2 Lokasi: Kraksaan & Dringu'],
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
        highlights: ['Laporan Tumbuh Kembang Digital', 'Pengasuh Terlatih & Hangat', 'Lingkungan Aman & Edukatif'],
    },
]);

const activeEcosystem = computed(() => ecosystems.value[activeEcosystemIndex.value] || ecosystems.value[0]);

// Benefits (Stockbit "How We Improve People's Lives" 4-column editorial grid)
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
        desc: 'Didukung SuperApp Namira untuk jurnal kelas, presensi, dan kolaborasi antar-unit yang rapi dan efisien.',
    },
    {
        icon: SparklesIcon,
        tint: 'bg-indigo-50 text-indigo-600',
        title: 'Karya yang Berdampak Luas',
        desc: 'Setiap peran — pendidik maupun staf — mengambil bagian langsung dalam membina generasi masa depan.',
    },
];

// Values (Stockbit "We Embrace Our Values" 4 minimal editorial pillars)
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
        desc: 'Pilih posisi yang sesuai, isi formulir daring, dan unggah CV beserta dokumen persyaratan langsung di portal ini.',
    },
    {
        num: '02',
        icon: CheckCircleIcon,
        title: 'Seleksi Administrasi',
        desc: 'Tim Humas & Rekrutmen meninjau kesesuaian kualifikasi, latar belakang pendidikan, dan kelengkapan berkas Anda.',
    },
    {
        num: '03',
        icon: PresentationChartBarIcon,
        title: 'Microteaching & Wawancara',
        desc: 'Kandidat terpilih mengikuti praktik mengajar / uji keterampilan bidang, observasi baca Al-Qur’an, serta wawancara.',
    },
    {
        num: '04',
        icon: UserPlusIcon,
        title: 'Bergabung & Onboarding',
        desc: 'Kandidat yang lulus menerima penawaran resmi dan mengikuti masa orientasi budaya kerja Sekolah Islam Namira.',
    },
];

// Tabbed FAQ (Stockbit bottom tabbed recognition/info structure)
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
            a: 'Klik tombol "Lihat Detail & Lamar Online" pada posisi yang tersedia, lengkapi data diri dan riwayat pendidikan, lalu unggah CV beserta berkas pendukung (PDF atau foto) secara langsung tanpa perlu datang ke kantor terlebih dahulu.',
        },
        {
            q: 'Apakah fresh graduate diperbolehkan melamar?',
            a: 'Ya, lulusan baru yang memiliki semangat belajar tinggi, komunikatif, dan siap dibina sangat terbuka untuk melamar pada posisi yang sesuai dengan kualifikasi pada poster atau deskripsi lowongan.',
        },
        {
            q: 'Dokumen apa saja yang sebaiknya disiapkan sebelum mendaftar?',
            a: 'Siapkan CV terbaru (wajib), surat lamaran, scan ijazah terakhir, pas foto 3x4, KTP, Kartu Keluarga, serta surat keterangan sehat apabila tercantum pada persyaratan unit.',
        },
    ],
    seleksi: [
        {
            q: 'Berapa lama proses peninjauan lamaran berlangsung?',
            a: 'Tim rekrutmen unit dan yayasan meninjau berkas secara berkala. Kandidat yang memenuhi kualifikasi akan dihubungi langsung melalui nomor WhatsApp atau email yang didaftarkan.',
        },
        {
            q: 'Apakah pelamar posisi guru harus sudah bersertifikat Metode Ummi?',
            a: 'Belum wajib saat melamar. Kemampuan membaca Al-Qur’an yang baik menjadi nilai tambah, dan Yayasan Namira menyediakan pembinaan serta sertifikasi Metode Ummi bagi pendidik yang bergabung.',
        },
        {
            q: 'Apakah proses rekrutmen Yayasan Namira dipungut biaya?',
            a: 'Tidak. Seluruh tahapan pendaftaran dan seleksi rekrutmen resmi Yayasan Namira sepenuhnya gratis dan tidak dipungut biaya apa pun.',
        },
    ],
    penempatan: [
        {
            q: 'Di mana saja lokasi kampus penempatan kerja Yayasan Namira?',
            a: 'Unit pendidikan Yayasan Namira tersebar di wilayah Kraksaan (Kabupaten Probolinggo), Dringu (Kabupaten Probolinggo), dan Kota Probolinggo. Lokasi penempatan tercantum pada masing-masing lowongan.',
        },
        {
            q: 'Apakah tersedia jenjang pengembangan karir bagi guru dan staf?',
            a: 'Ya. Dengan ekosistem lebih dari 10 unit layanan pendidikan, pendidik dan staf berprestasi memiliki ruang pertumbuhan menuju koordinator bidang, wakil kepala sekolah, hingga posisi manajerial yayasan.',
        },
        {
            q: 'Siapa yang dapat dihubungi jika mengalami kendala saat mengunggah berkas?',
            a: 'Anda dapat menghubungi nomor WhatsApp Admin Unit yang tertera pada halaman detail lowongan atau mengirim pesan ke email resmi hr@namiraschool.com.',
        },
    ],
};

const currentFaqs = computed(() => faqItemsByTab[activeFaqTab.value] || []);

// GSAP + Lenis smooth scroll choreography (alive yet restrained)
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
        lenisInstance.scrollTo(target, { offset: -80, duration: 1.05 });
    } else if (typeof target === 'number') {
        window.scrollTo({ top: target, behavior: 'smooth' });
    } else {
        document.querySelector(target)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

// Pause Lenis scroll while lightbox is open
watch(lightboxPoster, (val) => {
    if (val) {
        lenisInstance?.stop();
    } else {
        lenisInstance?.start();
    }
});

onMounted(() => {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    keydownHandler = (e) => {
        if (e.key === 'Escape' && lightboxPoster.value) {
            lightboxPoster.value = null;
        }
    };
    window.addEventListener('keydown', keydownHandler);

    if (!prefersReduced) {
        lenisInstance = new Lenis({
            duration: 1.05,
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

        // Subtle 2px top progress hairline
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

        // Crisp restrained hero entrance
        gsap.from('.hero-stagger', {
            y: 18,
            opacity: 0,
            duration: 0.72,
            stagger: 0.09,
            ease: 'power3.out',
            clearProps: 'transform,opacity',
        });

        gsap.from('.hero-collage-item', {
            scale: 0.92,
            opacity: 0,
            y: 16,
            duration: 0.8,
            stagger: 0.1,
            delay: 0.12,
            ease: 'power3.out',
            clearProps: 'transform,opacity',
        });

        // Subtle living ambient float on hero collage frames (slow & calm, never frantic)
        gsap.to('.float-slow-a', {
            y: -6,
            duration: 4.2,
            repeat: -1,
            yoyo: true,
            ease: 'sine.inOut',
        });
        gsap.to('.float-slow-b', {
            y: 6,
            duration: 5.0,
            repeat: -1,
            yoyo: true,
            ease: 'sine.inOut',
            delay: 0.6,
        });
        gsap.to('.float-slow-c', {
            y: -4,
            duration: 5.6,
            repeat: -1,
            yoyo: true,
            ease: 'sine.inOut',
            delay: 1.1,
        });

        // Restrained scroll-triggered reveals per section
        gsap.utils.toArray('.reveal-up').forEach((el) => {
            gsap.from(el, {
                y: 22,
                opacity: 0,
                duration: 0.68,
                ease: 'power2.out',
                clearProps: 'transform,opacity',
                scrollTrigger: {
                    trigger: el,
                    start: 'top 86%',
                    once: true,
                },
            });
        });

        // Staggered cards reveal
        gsap.utils.toArray('.stagger-group').forEach((group) => {
            const items = group.querySelectorAll('.stagger-card');
            if (!items.length) return;
            gsap.from(items, {
                y: 20,
                opacity: 0,
                duration: 0.6,
                stagger: 0.08,
                ease: 'power2.out',
                clearProps: 'transform,opacity',
                scrollTrigger: {
                    trigger: group,
                    start: 'top 84%',
                    once: true,
                },
            });
        });

        // Gentle count-up for genuine Yayasan impact metrics
        gsap.utils.toArray('.stat-counter').forEach((el) => {
            const target = Number(el.dataset.target || 0);
            const suffix = el.dataset.suffix || '';
            const proxy = { val: 0 };
            ScrollTrigger.create({
                trigger: el,
                start: 'top 88%',
                once: true,
                onEnter: () => {
                    gsap.to(proxy, {
                        val: target,
                        duration: 1.25,
                        ease: 'power2.out',
                        onUpdate: () => {
                            el.textContent = Math.round(proxy.val).toLocaleString('id-ID') + suffix;
                        },
                    });
                },
            });
        });

        // Very gentle desktop cursor parallax on the hero collage (max 8px shift)
        if (heroCollageRef.value && !window.matchMedia('(pointer: coarse)').matches) {
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
        <header class="sticky top-0 z-40 bg-white/92 backdrop-blur-md border-b border-slate-100">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-[72px] flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 shrink-0">
                    <Link href="/" class="flex items-center gap-2.5 group">
                        <div class="w-9 h-9 rounded-xl bg-white border border-slate-100 shadow-2xs p-1 flex items-center justify-center shrink-0 overflow-hidden">
                            <img
                                :src="$page.props.app_settings?.app_logo || '/images/landing/logo-yayasan.webp'"
                                alt="Logo Yayasan Namira"
                                class="w-full h-full object-contain"
                            />
                        </div>
                        <div class="flex items-baseline gap-1 select-none whitespace-nowrap">
                            <span class="font-namira text-xl text-[#1a4373] tracking-tight lowercase leading-none">namira</span>
                            <span class="font-school text-xs text-slate-500 tracking-wider uppercase font-extrabold leading-none">SCHOOL</span>
                        </div>
                    </Link>

                    <span class="h-5 w-px bg-slate-200 hidden sm:inline-block"></span>
                    <span class="hidden sm:inline-block text-[13px] font-semibold text-slate-500 tracking-tight">Karir</span>
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
                        class="px-3.5 py-2 rounded-xl bg-[#009688] text-white text-xs font-bold cursor-pointer"
                    >
                        Lowongan
                    </button>
                    <button
                        type="button"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        aria-label="Toggle Menu"
                        class="p-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 transition cursor-pointer"
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
                    :transition="{ duration: 0.22 }"
                    class="md:hidden border-t border-slate-100 bg-white overflow-hidden"
                >
                    <div class="px-5 py-4 flex flex-col gap-3 text-sm font-semibold text-slate-700">
                        <button type="button" @click="smoothScrollTo('#lowongan')" class="text-left py-1 hover:text-[#009688]">Lowongan Dibuka</button>
                        <button type="button" @click="smoothScrollTo('#ekosistem')" class="text-left py-1 hover:text-[#009688]">Ekosistem Pendidikan</button>
                        <button type="button" @click="smoothScrollTo('#budaya')" class="text-left py-1 hover:text-[#009688]">Budaya &amp; Nilai Kerja</button>
                        <button type="button" @click="smoothScrollTo('#seleksi')" class="text-left py-1 hover:text-[#009688]">Alur Seleksi &amp; FAQ</button>
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-3">
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
                 HERO SECTION — Stockbit-Inspired Bright Editorial Canvas
            ========================================================== -->
            <section class="relative overflow-hidden pt-10 pb-24 sm:pt-14 sm:pb-28 lg:pt-16 lg:pb-32">
                <!-- Organic Soft Pastel Mint Curved Backdrop (Left Sweep) -->
                <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
                    <svg
                        viewBox="0 0 1440 620"
                        fill="none"
                        preserveAspectRatio="none"
                        class="absolute top-0 left-0 w-full h-full"
                    >
                        <path
                            d="M0 0H720C690 190 610 475 405 540C215 600 85 520 0 420V0Z"
                            fill="#ECF9F4"
                        />
                        <circle cx="1180" cy="110" r="220" fill="#F4FBF8" opacity="0.7" />
                    </svg>
                </div>

                <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-8 items-center">
                        <!-- Left Typography Column -->
                        <div class="lg:col-span-6 space-y-6">
                            <div
                                v-if="(stats?.open_vacancies_count || vacancies?.total || 0) > 0"
                                class="hero-stagger inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/90 border border-[#009688]/20 shadow-2xs text-xs font-semibold text-[#007468]"
                            >
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#009688] opacity-60"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-[#009688]"></span>
                                </span>
                                <span>{{ stats?.open_vacancies_count || vacancies?.total }} Posisi Sedang Dibuka</span>
                            </div>

                            <h1 class="hero-stagger text-[2.2rem] sm:text-[3rem] lg:text-[3.35rem] leading-[1.12] tracking-[-0.03em] text-slate-900">
                                <span class="font-normal text-slate-800 block">Tumbuh &amp;</span>
                                <span class="font-extrabold text-slate-950 block">Berkarya Bersama</span>
                                <span class="font-normal text-slate-800 block">Yayasan Namira</span>
                            </h1>

                            <p class="hero-stagger text-sm sm:text-[15px] text-slate-600 leading-relaxed max-w-[470px]">
                                Bergabung bersama pendidik, pembina Al-Qur’an, dan tenaga profesional untuk menghadirkan pendidikan Islam modern yang hangat dan berdampak di Probolinggo.
                            </p>

                            <div class="hero-stagger flex flex-wrap items-center gap-5 pt-1">
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
                                    class="text-sm font-semibold text-slate-500 hover:text-slate-900 transition-colors cursor-pointer"
                                >
                                    Kenali Budaya Kami
                                </button>
                            </div>
                        </div>

                        <!-- Right Organic Floating Campus Collage (Alive yet Restrained) -->
                        <div ref="heroCollageRef" class="lg:col-span-6 relative">
                            <div class="relative mx-auto max-w-[500px] h-[330px] sm:h-[385px]">
                                <!-- Delicate Curved Dashed Connector Lines -->
                                <svg
                                    viewBox="0 0 500 385"
                                    fill="none"
                                    aria-hidden="true"
                                    class="absolute inset-0 w-full h-full pointer-events-none"
                                >
                                    <path
                                        d="M80 220 C 95 100, 210 65, 315 80"
                                        stroke="#A7DFD2"
                                        stroke-width="1.5"
                                        stroke-dasharray="5 5"
                                    />
                                    <path
                                        d="M145 310 C 230 345, 345 320, 410 230"
                                        stroke="#CBD5E1"
                                        stroke-width="1.5"
                                        stroke-dasharray="5 5"
                                    />
                                    <circle cx="430" cy="88" r="14" fill="#FEF3C7" />
                                    <circle cx="430" cy="88" r="5" fill="#F59E0B" />
                                    <circle cx="62" cy="148" r="16" fill="#E6F7F1" />
                                    <circle cx="62" cy="148" r="6" fill="#009688" />
                                    <circle cx="290" cy="340" r="10" fill="#E0F2FE" />
                                    <circle cx="290" cy="340" r="4" fill="#0EA5E9" />
                                </svg>

                                <!-- Main Circular Photo 1: SD Namira Campus -->
                                <div
                                    data-depth="1.1"
                                    class="hero-collage-item float-slow-a absolute left-[12%] sm:left-[14%] top-[6%] w-40 h-40 sm:w-48 sm:h-48 rounded-full p-1.5 bg-white shadow-[0_18px_40px_-14px_rgba(15,23,42,0.16)] ring-1 ring-slate-100"
                                >
                                    <img
                                        :src="heroPhotos.sd"
                                        alt="Lingkungan Kampus SD Namira"
                                        class="w-full h-full object-cover rounded-full"
                                    />
                                </div>

                                <!-- Circular Photo 2: SMP Namira Campus -->
                                <div
                                    data-depth="1.5"
                                    class="hero-collage-item float-slow-b absolute right-[8%] sm:right-[7%] top-[4%] w-28 h-28 sm:w-32 sm:h-32 rounded-full p-1.5 bg-white shadow-[0_16px_34px_-14px_rgba(15,23,42,0.14)] ring-1 ring-slate-100"
                                >
                                    <img
                                        :src="heroPhotos.smp"
                                        alt="Kampus SMP Namira"
                                        class="w-full h-full object-cover rounded-full"
                                    />
                                </div>

                                <!-- Rounded Feature Photo 3: TK / KB / Daycare Namira -->
                                <div
                                    data-depth="1.3"
                                    class="hero-collage-item float-slow-c absolute right-[4%] sm:right-[5%] bottom-[8%] w-44 h-32 sm:w-52 sm:h-36 rounded-[28px] p-1.5 bg-white shadow-[0_18px_42px_-16px_rgba(15,23,42,0.16)] ring-1 ring-slate-100"
                                >
                                    <img
                                        :src="heroPhotos.tk"
                                        alt="Kampus TK, KB & Daycare Namira"
                                        class="w-full h-full object-cover rounded-[22px]"
                                    />
                                </div>

                                <!-- Floating Micro Tag Pill 1 (Top Left, Stockbit-style dark speech tag) -->
                                <div
                                    data-depth="1.8"
                                    class="hero-collage-item float-slow-b absolute left-[2%] sm:left-[0%] top-[18%] px-3.5 py-2 rounded-full bg-slate-900 text-white text-[11px] font-bold shadow-md flex items-center gap-1.5 whitespace-nowrap"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    <span>Lingkungan Qur’ani</span>
                                </div>

                                <!-- Floating Micro Tag Pill 2 (Bottom Left Yayasan Card) -->
                                <div
                                    data-depth="1.4"
                                    class="hero-collage-item float-slow-a absolute left-[4%] sm:left-[6%] bottom-[12%] bg-white/95 backdrop-blur-xs border border-slate-100 rounded-2xl px-3.5 py-2.5 shadow-[0_12px_30px_-10px_rgba(15,23,42,0.12)] flex items-center gap-2.5"
                                >
                                    <img
                                        src="/images/landing/logo-yayasan.webp"
                                        alt="Yayasan Namira"
                                        class="w-8 h-8 object-contain shrink-0"
                                    />
                                    <div class="leading-tight">
                                        <div class="text-xs font-extrabold text-slate-900">{{ displayStats[0].target }}+ Unit Pendidikan</div>
                                        <div class="text-[11px] text-slate-500 font-medium">Kraksaan · Dringu · Kota</div>
                                    </div>
                                </div>

                                <!-- Floating Micro Tag Pill 3 (Mid Right) -->
                                <div
                                    data-depth="1.9"
                                    class="hero-collage-item float-slow-c hidden sm:inline-flex absolute right-[2%] top-[44%] px-3 py-1.5 rounded-full bg-[#009688] text-white text-[11px] font-bold shadow-md items-center gap-1.5 whitespace-nowrap"
                                >
                                    <span>Tumbuh Bersama</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 UNIFIED FLOATING SEARCH & FILTER BAR (Stockbit Signature)
            ========================================================== -->
            <section class="relative z-20 -mt-12 sm:-mt-14 px-4 sm:px-6 lg:px-8">
                <div class="max-w-5xl mx-auto">
                    <form
                        @submit.prevent="applyFilters(true)"
                        class="bg-white rounded-2xl sm:rounded-[20px] border border-slate-100 shadow-[0_16px_48px_-14px_rgba(15,23,42,0.10)] p-3 sm:p-4"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 md:gap-0 items-center">
                            <!-- Job Search -->
                            <div class="md:col-span-4 md:px-3 md:border-r border-slate-100">
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

                            <!-- Unit Pendidikan -->
                            <div class="md:col-span-3 md:px-4 md:border-r border-slate-100 pt-2 md:pt-0 border-t md:border-t-0">
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

                            <!-- Kategori -->
                            <div class="md:col-span-3 md:px-4 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
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

                            <!-- CTA Button -->
                            <div class="md:col-span-2 md:pl-2 pt-2 md:pt-0">
                                <Motion :while-tap="{ scale: 0.98 }">
                                    <button
                                        type="submit"
                                        class="w-full py-3 px-4 rounded-xl bg-[#009688] hover:bg-[#007D71] text-white text-xs sm:text-[13px] font-bold shadow-xs transition-colors cursor-pointer whitespace-nowrap"
                                    >
                                        Cari Lowongan
                                    </button>
                                </Motion>
                            </div>
                        </div>
                    </form>

                    <!-- Active Filters Hint -->
                    <div v-if="hasActiveFilters" class="mt-3 flex items-center justify-between px-2 text-xs text-slate-500">
                        <span>Menampilkan hasil pencarian sesuai filter aktif.</span>
                        <button
                            type="button"
                            @click="resetFilters"
                            class="font-bold text-[#008C7E] hover:underline cursor-pointer"
                        >
                            Reset Filter
                        </button>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 OPEN VACANCIES — Smart Adaptive Layout (Zero Dead Space!)
            ========================================================== -->
            <section id="lowongan" class="pt-14 pb-20 sm:pt-18 sm:pb-24">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                    <!-- Flash Notice -->
                    <div
                        v-if="$page.props.flash?.warning || $page.props.flash?.error"
                        class="p-4 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs font-semibold"
                    >
                        {{ $page.props.flash.warning || $page.props.flash.error }}
                    </div>

                    <!-- Section Header + Motion Spring Quick Filter Pills -->
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                        <div class="space-y-1.5">
                            <div class="flex items-center gap-2.5">
                                <h2 class="text-2xl sm:text-[1.75rem] tracking-tight text-slate-900">
                                    <span class="font-normal">Lowongan</span>
                                    <span class="font-extrabold"> Kerja Dibuka</span>
                                </h2>
                                <span class="px-2.5 py-0.5 rounded-full bg-[#E6F7F1] text-[#007468] text-xs font-bold">
                                    {{ vacancies.total }} Posisi
                                </span>
                            </div>
                            <p class="text-sm text-slate-500">
                                Pilih posisi yang sesuai minat dan keahlian Anda, lalu kirim lamaran secara online.
                            </p>
                        </div>

                        <!-- Motion Spring Category Pills -->
                        <div class="inline-flex flex-wrap items-center gap-1 p-1 rounded-xl bg-slate-100/80 border border-slate-200/60 self-start md:self-auto">
                            <button
                                v-for="tab in quickCategories"
                                :key="tab.key"
                                type="button"
                                @click="selectQuickCategory(tab.key)"
                                class="relative px-3.5 py-1.5 text-xs font-bold rounded-lg transition-colors cursor-pointer"
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

                    <!-- CASE 1: Empty State -->
                    <div
                        v-if="vacancyList.length === 0"
                        class="rounded-3xl border border-dashed border-slate-200 bg-[#FAFCFB] p-12 sm:p-16 text-center space-y-4"
                    >
                        <div class="w-14 h-14 rounded-2xl bg-[#E6F7F1] text-[#008C7E] mx-auto flex items-center justify-center">
                            <BriefcaseIcon class="w-7 h-7" />
                        </div>
                        <div class="space-y-1.5">
                            <h3 class="text-base sm:text-lg font-extrabold text-slate-900">
                                Belum Ada Lowongan yang Cocok
                            </h3>
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

                    <!-- CASE 2: Single Active Vacancy — Full-Width Editorial Spotlight Split Card (Eliminates Dead Whitespace!) -->
                    <div
                        v-else-if="isSingleSpotlight"
                        class="reveal-up"
                    >
                        <article
                            v-for="vacancy in vacancyList"
                            :key="vacancy.id"
                            class="rounded-3xl bg-white border border-slate-200/80 shadow-[0_14px_40px_-18px_rgba(15,23,42,0.09)] hover:border-[#009688]/40 transition-all overflow-hidden grid grid-cols-1 lg:grid-cols-12"
                        >
                            <!-- Left Visual: Poster Studio Showcase OR Branded Unit Summary -->
                            <div
                                v-if="vacancy.poster_url"
                                class="lg:col-span-5 relative bg-[#F2FAF7] p-6 sm:p-8 flex items-center justify-center border-b lg:border-b-0 lg:border-r border-slate-100 group"
                            >
                                <img
                                    :src="vacancy.poster_url"
                                    :alt="`Poster ${vacancy.title}`"
                                    @click="lightboxPoster = { url: vacancy.poster_url, title: vacancy.title }"
                                    class="max-h-[430px] w-auto max-w-full object-contain rounded-2xl shadow-[0_16px_38px_-14px_rgba(15,23,42,0.22)] ring-1 ring-slate-900/5 transition-transform duration-300 group-hover:scale-[1.015] cursor-zoom-in"
                                />
                                <button
                                    type="button"
                                    @click="lightboxPoster = { url: vacancy.poster_url, title: vacancy.title }"
                                    class="absolute bottom-4 right-4 px-3 py-1.5 rounded-xl bg-white/95 hover:bg-white text-slate-800 border border-slate-200/80 shadow-sm text-[11px] font-bold inline-flex items-center gap-1.5 transition cursor-pointer"
                                >
                                    <MagnifyingGlassPlusIcon class="w-3.5 h-3.5 text-[#008C7E]" />
                                    <span>Perbesar Poster</span>
                                </button>
                            </div>

                            <div
                                v-else
                                class="lg:col-span-5 bg-[#EDF9F4] p-8 flex flex-col justify-between border-b lg:border-b-0 lg:border-r border-teal-100/80"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-2xl bg-white p-2 shadow-2xs border border-teal-100 flex items-center justify-center">
                                        <img src="/images/landing/logo-yayasan.webp" alt="Namira" class="w-full h-full object-contain" />
                                    </div>
                                    <div>
                                        <div class="text-[11px] font-bold uppercase tracking-wider text-[#007468]">Rekrutmen Resmi</div>
                                        <div class="text-sm font-extrabold text-slate-900">{{ vacancy.unit?.name || 'Yayasan Namira' }}</div>
                                    </div>
                                </div>
                                <div class="my-6 rounded-2xl overflow-hidden shadow-sm border border-white">
                                    <img :src="heroPhotos.sd" alt="Kampus Namira" class="w-full h-44 object-cover" />
                                </div>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Seluruh berkas lamaran dikirim langsung melalui portal resmi tanpa dipungut biaya.
                                </p>
                            </div>

                            <!-- Right Editorial Content -->
                            <div class="lg:col-span-7 p-6 sm:p-9 lg:p-10 flex flex-col justify-between gap-7">
                                <div class="space-y-5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-3 py-1 rounded-full bg-[#E6F7F1] text-[#007468] text-[11px] font-extrabold uppercase tracking-wide">
                                            {{ vacancy.unit?.name || 'Yayasan Namira' }}
                                        </span>
                                        <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-[11px] font-bold">
                                            {{ vacancy.type_label }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-semibold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Aktif Menerima Lamaran
                                        </span>
                                    </div>

                                    <div class="space-y-2.5">
                                        <h3 class="text-2xl sm:text-[1.85rem] font-extrabold text-slate-950 tracking-tight leading-snug hover:text-[#008C7E] transition-colors">
                                            <Link :href="route('careers.show', vacancy.slug)">
                                                {{ vacancy.title }}
                                            </Link>
                                        </h3>
                                        <p class="text-sm text-slate-600 leading-relaxed">
                                            {{
                                                excerptText(vacancy.description || vacancy.requirements, 210)
                                                || 'Terbuka bagi talenta terbaik yang berdedikasi mendidik dan bertumbuh bersama keluarga besar Sekolah Islam Namira. Periksa rincian kualifikasi pada poster atau langsung lengkapi formulir lamaran daring.'
                                            }}
                                        </p>
                                    </div>

                                    <!-- Structured 2x2 Key Meta Grid -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                        <div class="p-3.5 rounded-2xl bg-[#F8FAFC] border border-slate-100 flex items-start gap-3">
                                            <BuildingOffice2Icon class="w-4 h-4 text-[#008C7E] shrink-0 mt-0.5" />
                                            <div class="min-w-0">
                                                <div class="text-[11px] font-semibold text-slate-400">Kategori Posisi</div>
                                                <div class="text-xs font-bold text-slate-800 mt-0.5">{{ vacancy.category_label }}</div>
                                            </div>
                                        </div>

                                        <div class="p-3.5 rounded-2xl bg-[#F8FAFC] border border-slate-100 flex items-start gap-3">
                                            <UserGroupIcon class="w-4 h-4 text-[#008C7E] shrink-0 mt-0.5" />
                                            <div class="min-w-0">
                                                <div class="text-[11px] font-semibold text-slate-400">Kuota Kebutuhan</div>
                                                <div class="text-xs font-bold text-slate-800 mt-0.5">{{ vacancy.quota }} Orang</div>
                                            </div>
                                        </div>

                                        <div class="p-3.5 rounded-2xl bg-[#F8FAFC] border border-slate-100 flex items-start gap-3">
                                            <ClockIcon class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" />
                                            <div class="min-w-0">
                                                <div class="text-[11px] font-semibold text-slate-400">Batas Pengumpulan</div>
                                                <div class="text-xs font-bold text-slate-800 mt-0.5">{{ formatDate(vacancy.deadline) }}</div>
                                            </div>
                                        </div>

                                        <div class="p-3.5 rounded-2xl bg-[#F8FAFC] border border-slate-100 flex items-start gap-3">
                                            <MapPinIcon class="w-4 h-4 text-[#008C7E] shrink-0 mt-0.5" />
                                            <div class="min-w-0">
                                                <div class="text-[11px] font-semibold text-slate-400">Lokasi Penempatan</div>
                                                <div class="text-xs font-bold text-slate-800 mt-0.5 truncate">
                                                    {{ vacancy.placement_address || 'Kraksaan / Probolinggo, Jawa Timur' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Primary Actions -->
                                <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-slate-100">
                                    <Link
                                        :href="route('careers.show', vacancy.slug)"
                                        class="flex-1 sm:flex-none px-6 py-3.5 rounded-xl bg-[#009688] hover:bg-[#007D71] text-white text-xs sm:text-sm font-bold shadow-xs transition-all inline-flex items-center justify-center gap-2 group"
                                    >
                                        <span>Lihat Detail &amp; Lamar Online</span>
                                        <ArrowRightIcon class="w-4 h-4 transition-transform group-hover:translate-x-1" />
                                    </Link>

                                    <a
                                        v-if="formatWhatsappHref(vacancy)"
                                        :href="formatWhatsappHref(vacancy)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="px-4 py-3.5 rounded-xl border border-slate-200 hover:border-[#009688] hover:text-[#008C7E] text-slate-700 text-xs sm:text-sm font-bold inline-flex items-center justify-center gap-2 transition-colors"
                                    >
                                        <ChatBubbleLeftRightIcon class="w-4 h-4 text-[#009688]" />
                                        <span>Tanya Admin Unit</span>
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>

                    <!-- CASE 3: Multiple Vacancies (Balanced 2-col or 3-col Grid) -->
                    <div
                        v-else
                        class="stagger-group grid gap-6"
                        :class="isTwoUpLayout ? 'grid-cols-1 lg:grid-cols-2' : 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'"
                    >
                        <article
                            v-for="vacancy in vacancyList"
                            :key="vacancy.id"
                            class="stagger-card bg-white rounded-3xl border border-slate-200/80 shadow-[0_8px_28px_-14px_rgba(15,23,42,0.07)] hover:shadow-[0_18px_40px_-16px_rgba(0,150,136,0.16)] hover:border-[#009688]/45 transition-all flex flex-col justify-between overflow-hidden"
                        >
                            <div>
                                <!-- Clean Light Studio Poster Banner -->
                                <div
                                    v-if="vacancy.poster_url"
                                    class="relative h-64 bg-[#F2FAF7] border-b border-slate-100 overflow-hidden flex items-center justify-center p-4 group"
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

                                <div class="p-6 space-y-3.5">
                                    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100">
                                        <span class="px-3 py-1 rounded-full bg-[#E6F7F1] text-[#007468] text-[10px] font-extrabold uppercase tracking-wide">
                                            {{ vacancy.unit?.name || 'Yayasan Namira' }}
                                        </span>
                                        <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold">
                                            {{ vacancy.type_label }}
                                        </span>
                                    </div>

                                    <h3 class="text-lg font-extrabold text-slate-950 leading-snug tracking-tight hover:text-[#008C7E] transition-colors">
                                        <Link :href="route('careers.show', vacancy.slug)">
                                            {{ vacancy.title }}
                                        </Link>
                                    </h3>

                                    <div class="space-y-1.5 text-xs text-slate-500">
                                        <div class="flex items-center gap-2">
                                            <BuildingOffice2Icon class="w-4 h-4 text-slate-400 shrink-0" />
                                            <span>{{ vacancy.category_label }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <UserGroupIcon class="w-4 h-4 text-slate-400 shrink-0" />
                                            <span>Kuota: <strong class="text-slate-800">{{ vacancy.quota }} Orang</strong></span>
                                        </div>
                                        <div v-if="vacancy.placement_address" class="flex items-center gap-2">
                                            <MapPinIcon class="w-4 h-4 text-[#008C7E] shrink-0" />
                                            <span class="truncate">{{ vacancy.placement_address }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <ClockIcon class="w-4 h-4 text-amber-500 shrink-0" />
                                            <span>Batas Akhir: <strong class="text-slate-800">{{ formatDate(vacancy.deadline) }}</strong></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="px-6 pb-6 pt-1">
                                <Link
                                    :href="route('careers.show', vacancy.slug)"
                                    class="w-full py-3 rounded-xl bg-[#009688] hover:bg-[#007D71] text-white font-bold text-xs shadow-2xs transition-colors flex items-center justify-center gap-2 group"
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
                 OUR GROWING ECOSYSTEM — Interactive Accordion + Live Preview
            ========================================================== -->
            <section id="ekosistem" class="py-16 sm:py-24 border-t border-slate-100">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-center">
                        <!-- Left Dynamic Campus Visual Showcase -->
                        <div class="lg:col-span-6 reveal-up">
                            <div class="relative mx-auto max-w-[480px]">
                                <!-- Soft Mint Backdrop Shape -->
                                <div
                                    aria-hidden="true"
                                    class="absolute -inset-3 sm:-inset-5 rounded-[36px] bg-[#ECF9F4] -rotate-2"
                                ></div>

                                <AnimatePresence mode="wait">
                                    <Motion
                                        :key="activeEcosystem.id"
                                        :initial="{ opacity: 0, y: 12, scale: 0.98 }"
                                        :animate="{ opacity: 1, y: 0, scale: 1 }"
                                        :exit="{ opacity: 0, y: -10, scale: 0.98 }"
                                        :transition="{ duration: 0.3, ease: 'easeOut' }"
                                        class="relative bg-white rounded-[28px] border border-slate-100 shadow-[0_20px_50px_-20px_rgba(15,23,42,0.14)] overflow-hidden"
                                    >
                                        <div class="relative h-56 sm:h-64 overflow-hidden bg-slate-100">
                                            <img
                                                :src="activeEcosystem.photo"
                                                :alt="activeEcosystem.name"
                                                class="w-full h-full object-cover"
                                            />
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

                                        <div class="p-5 sm:p-6 space-y-3">
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

                        <!-- Right Interactive Unit Accordion -->
                        <div class="lg:col-span-6 space-y-6 reveal-up">
                            <div class="space-y-2.5">
                                <h2 class="text-2xl sm:text-[2rem] tracking-tight text-slate-900 leading-tight">
                                    <span class="font-normal">Ekosistem </span>
                                    <span class="font-extrabold">Pendidikan Kami</span>
                                </h2>
                                <p class="text-sm text-slate-600 leading-relaxed">
                                    Yayasan Namira menaungi jenjang pendidikan terintegrasi yang terus bertumbuh untuk membuka ruang kolaborasi dan pengabdian terbaik bagi setiap pendidik.
                                </p>
                            </div>

                            <div class="space-y-2.5">
                                <div
                                    v-for="(item, idx) in ecosystems"
                                    :key="item.id"
                                    class="rounded-2xl border transition-all overflow-hidden"
                                    :class="
                                        activeEcosystemIndex === idx
                                            ? 'bg-white border-[#009688]/35 shadow-[0_10px_30px_-14px_rgba(0,150,136,0.18)]'
                                            : 'bg-[#FAFCFB] border-slate-100 hover:border-slate-200'
                                    "
                                >
                                    <button
                                        type="button"
                                        @click="activeEcosystemIndex = idx"
                                        class="w-full px-5 py-4 flex items-center justify-between gap-3 text-left cursor-pointer"
                                    >
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-8 h-8 rounded-xl bg-white border border-slate-100 p-1 flex items-center justify-center shrink-0">
                                                <img :src="item.logo" :alt="item.name" class="w-full h-full object-contain" />
                                            </div>
                                            <span
                                                class="text-sm font-bold truncate"
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
                                            :transition="{ duration: 0.25, ease: 'easeOut' }"
                                            class="overflow-hidden"
                                        >
                                            <div class="px-5 pb-4 pl-16 text-xs sm:text-[13px] text-slate-600 leading-relaxed">
                                                {{ item.summary }}
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
                 HOW WE GROW TOGETHER (Stockbit 4-Column Light Cards)
            ========================================================== -->
            <section class="py-16 sm:py-20">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                    <div class="text-center max-w-2xl mx-auto space-y-2.5 reveal-up">
                        <h2 class="text-2xl sm:text-[2rem] tracking-tight text-slate-900">
                            <span class="font-normal">Bagaimana Kami </span>
                            <span class="font-extrabold">Tumbuh Bersama</span>
                        </h2>
                        <p class="text-sm text-slate-500">
                            Ruang bertumbuh yang sehat agar setiap pendidik dan staf dapat memberikan karya terbaiknya.
                        </p>
                    </div>

                    <div class="stagger-group grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        <div
                            v-for="item in benefits"
                            :key="item.title"
                            class="stagger-card rounded-2xl bg-white border border-slate-100 p-6 shadow-[0_8px_28px_-16px_rgba(15,23,42,0.08)] hover:shadow-[0_16px_38px_-16px_rgba(0,150,136,0.14)] hover:border-[#009688]/30 transition-all space-y-4"
                        >
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center" :class="item.tint">
                                <component :is="item.icon" class="w-6 h-6" />
                            </div>
                            <div class="space-y-1.5">
                                <h3 class="text-[15px] font-extrabold text-slate-900">{{ item.title }}</h3>
                                <p class="text-xs sm:text-[13px] text-slate-500 leading-relaxed">{{ item.desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 REACHING FOR NEW HEIGHTS — Soft Mint Wave Metrics Band
            ========================================================== -->
            <section class="relative my-6 bg-[#EDF9F4] py-20 sm:py-24 overflow-hidden">
                <!-- Gentle Top Organic Wave -->
                <svg
                    aria-hidden="true"
                    viewBox="0 0 1440 54"
                    fill="none"
                    preserveAspectRatio="none"
                    class="absolute top-0 left-0 w-full h-8 sm:h-12 text-white"
                >
                    <path d="M0 0H1440V18C1080 58 360 58 0 18V0Z" fill="currentColor" />
                </svg>

                <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                    <div class="text-center max-w-2xl mx-auto space-y-2.5 reveal-up">
                        <h2 class="text-2xl sm:text-[2rem] tracking-tight text-slate-900">
                            <span class="font-normal">Melangkah Menuju </span>
                            <span class="font-extrabold">Dampak Lebih Luas</span>
                        </h2>
                        <p class="text-sm text-slate-600">
                            Kepercayaan ribuan keluarga menjadi amanah yang kami jaga bersama setiap hari.
                        </p>
                    </div>

                    <div class="stagger-group grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div
                            v-for="metric in displayStats"
                            :key="metric.label"
                            class="stagger-card pl-5 border-l-2 border-[#009688]/25 space-y-2"
                        >
                            <div class="flex items-baseline gap-1.5 flex-wrap">
                                <span
                                    class="stat-counter text-3xl sm:text-[2.2rem] font-extrabold tracking-tight text-slate-950"
                                    :data-target="metric.target"
                                    :data-suffix="metric.suffix"
                                >
                                    {{ metric.target.toLocaleString('id-ID') }}{{ metric.suffix }}
                                </span>
                            </div>
                            <div class="text-xs font-extrabold uppercase tracking-wider text-[#007468]">
                                {{ metric.label }}
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                {{ metric.desc }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Gentle Bottom Organic Wave -->
                <svg
                    aria-hidden="true"
                    viewBox="0 0 1440 54"
                    fill="none"
                    preserveAspectRatio="none"
                    class="absolute bottom-0 left-0 w-full h-8 sm:h-12 text-white"
                >
                    <path d="M0 54H1440V36C1080 -4 360 -4 0 36V54Z" fill="currentColor" />
                </svg>
            </section>

            <!-- =========================================================
                 WE EMBRACE OUR VALUES (Stockbit Split Header + 4 Pillars)
            ========================================================== -->
            <section id="budaya" class="py-16 sm:py-24">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-14">
                    <!-- Top Split Header -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center reveal-up">
                        <div class="lg:col-span-6 space-y-3">
                            <h2 class="text-2xl sm:text-[2rem] tracking-tight text-slate-900">
                                <span class="font-normal">Kami Menjunjung </span>
                                <span class="font-extrabold">Nilai NAMIRA</span>
                            </h2>
                            <p class="text-sm text-slate-600 leading-relaxed max-w-lg">
                                Budaya kerja kami berakar pada keteladanan, kehangatan, dan profesionalisme — menciptakan lingkungan sekolah yang menyenangkan bagi siswa sekaligus bermakna bagi pendidik.
                            </p>
                        </div>

                        <!-- Clean Overlapping Real Campus Photo Strip -->
                        <div class="lg:col-span-6 flex items-center lg:justify-end">
                            <div class="relative flex items-center gap-3.5 max-w-md w-full">
                                <div class="w-1/2 h-36 sm:h-40 rounded-2xl overflow-hidden shadow-md ring-1 ring-slate-100">
                                    <img :src="heroPhotos.sd" alt="Suasana Kampus SD Namira" class="w-full h-full object-cover" />
                                </div>
                                <div class="w-1/2 h-36 sm:h-40 rounded-2xl overflow-hidden shadow-md ring-1 ring-slate-100 translate-y-3">
                                    <img :src="heroPhotos.daycare" alt="Lingkungan Belajar Namira" class="w-full h-full object-cover" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4 Minimal Editorial Value Pillars -->
                    <div class="stagger-group grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 pt-2">
                        <div
                            v-for="val in values"
                            :key="val.title"
                            class="stagger-card space-y-3.5"
                        >
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="val.tint">
                                <component :is="val.icon" class="w-5 h-5" />
                            </div>
                            <h3 class="text-sm sm:text-[15px] font-extrabold text-slate-900">{{ val.title }}</h3>
                            <p class="text-xs sm:text-[13px] text-slate-500 leading-relaxed">{{ val.desc }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 TRANSPARENT SELECTION JOURNEY & TESTIMONIALS (Soft Mint Band)
            ========================================================== -->
            <section id="seleksi" class="bg-[#F4FBF8] py-16 sm:py-24 border-y border-[#E1F3EC]">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
                    <!-- 4-Step Transparent Selection Journey -->
                    <div class="space-y-10">
                        <div class="text-center max-w-2xl mx-auto space-y-2 reveal-up">
                            <h2 class="text-2xl sm:text-[2rem] tracking-tight text-slate-900">
                                <span class="font-normal">Alur Seleksi </span>
                                <span class="font-extrabold">Transparan &amp; Terstruktur</span>
                            </h2>
                            <p class="text-sm text-slate-600">
                                Empat tahapan ringkas mulai dari pengiriman lamaran hingga bergabung bersama unit pendidikan Namira.
                            </p>
                        </div>

                        <div class="stagger-group grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                            <div
                                v-for="step in selectionSteps"
                                :key="step.num"
                                class="stagger-card bg-white rounded-2xl border border-slate-100 p-6 shadow-[0_8px_26px_-16px_rgba(15,23,42,0.08)] space-y-4"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="px-2.5 py-1 rounded-lg bg-[#E6F7F1] text-[#007468] text-xs font-extrabold font-mono">
                                        {{ step.num }}
                                    </span>
                                    <component :is="step.icon" class="w-5 h-5 text-slate-400" />
                                </div>
                                <div class="space-y-1.5">
                                    <h3 class="text-[15px] font-extrabold text-slate-900">{{ step.title }}</h3>
                                    <p class="text-xs text-slate-500 leading-relaxed">{{ step.desc }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Optional Live Approved Testimonials Strip (Only shown when real approved testimonials exist in DB) -->
                    <div v-if="testimonials && testimonials.length > 0" class="space-y-8 pt-4">
                        <div class="text-center max-w-xl mx-auto space-y-1.5 reveal-up">
                            <h3 class="text-xl sm:text-2xl tracking-tight text-slate-900">
                                <span class="font-normal">Kata </span>
                                <span class="font-extrabold">Keluarga Besar Namira</span>
                            </h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div
                                v-for="t in testimonials.slice(0, 3)"
                                :key="t.id"
                                class="bg-white rounded-2xl border border-slate-100 p-6 shadow-2xs flex flex-col justify-between gap-4"
                            >
                                <p class="text-xs sm:text-[13px] text-slate-600 leading-relaxed italic">
                                    “{{ t.quote }}”
                                </p>
                                <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
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
                 FAQ REKRUTMEN — Stockbit Left-Tab / Right-Grid Structure
            ========================================================== -->
            <section class="py-16 sm:py-24">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
                    <div class="text-center space-y-2 reveal-up">
                        <h2 class="text-2xl sm:text-[1.85rem] tracking-tight text-slate-900">
                            <span class="font-normal">Informasi &amp; </span>
                            <span class="font-extrabold">Pertanyaan Umum</span>
                        </h2>
                        <p class="text-sm text-slate-500">
                            Hal-hal yang paling sering ditanyakan calon pelamar sebelum mengirimkan berkas.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
                        <!-- Left Vertical Category Tabs -->
                        <div class="md:col-span-3 flex md:flex-col gap-2 overflow-x-auto pb-1 md:pb-0">
                            <button
                                v-for="tab in faqTabs"
                                :key="tab.key"
                                type="button"
                                @click="activeFaqTab = tab.key"
                                class="relative px-4 py-2.5 text-left text-xs sm:text-sm font-bold rounded-xl transition-colors whitespace-nowrap cursor-pointer"
                                :class="
                                    activeFaqTab === tab.key
                                        ? 'text-[#007468] bg-[#ECF9F4]'
                                        : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'
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

                        <!-- Right FAQ Cards -->
                        <div class="md:col-span-9">
                            <AnimatePresence mode="wait">
                                <Motion
                                    :key="activeFaqTab"
                                    :initial="{ opacity: 0, y: 8 }"
                                    :animate="{ opacity: 1, y: 0 }"
                                    :exit="{ opacity: 0, y: -6 }"
                                    :transition="{ duration: 0.22 }"
                                    class="space-y-4"
                                >
                                    <div
                                        v-for="item in currentFaqs"
                                        :key="item.q"
                                        class="rounded-2xl bg-white border border-slate-100 p-5 sm:p-6 shadow-2xs space-y-2"
                                    >
                                        <h3 class="text-sm font-extrabold text-slate-900">{{ item.q }}</h3>
                                        <p class="text-xs sm:text-[13px] text-slate-600 leading-relaxed">{{ item.a }}</p>
                                    </div>
                                </Motion>
                            </AnimatePresence>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- =========================================================
             AIRY MINIMAL WHITE EDITORIAL FOOTER (Stockbit Style)
        ========================================================== -->
        <footer class="bg-white border-t border-slate-100 py-14">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-10 pb-10 border-b border-slate-100">
                    <!-- Brand Summary -->
                    <div class="md:col-span-5 space-y-3.5">
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

                    <!-- Quick Links -->
                    <div class="md:col-span-2 space-y-3">
                        <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Tautan</h4>
                        <ul class="space-y-2 text-xs text-slate-500">
                            <li><Link href="/" class="hover:text-[#008C7E] transition-colors">Beranda</Link></li>
                            <li><Link :href="route('news.index')" class="hover:text-[#008C7E] transition-colors">Berita &amp; Artikel</Link></li>
                            <li><Link :href="route('events.index')" class="hover:text-[#008C7E] transition-colors">Kegiatan Sekolah</Link></li>
                            <li><Link :href="route('careers.index')" class="hover:text-[#008C7E] transition-colors">Portal Karir</Link></li>
                        </ul>
                    </div>

                    <!-- Units -->
                    <div class="md:col-span-2 space-y-3">
                        <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Unit Kami</h4>
                        <ul class="space-y-2 text-xs text-slate-500">
                            <li>SD Namira Kraksaan &amp; Kota</li>
                            <li>SMP Namira</li>
                            <li>TK &amp; KB Namira</li>
                            <li>Daycare &amp; Pavlov</li>
                        </ul>
                    </div>

                    <!-- Contact -->
                    <div class="md:col-span-3 space-y-3">
                        <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Kontak Rekrutmen</h4>
                        <div class="space-y-1.5 text-xs text-slate-500 leading-relaxed">
                            <p>Email: <a href="mailto:hr@namiraschool.com" class="font-semibold text-slate-700 hover:text-[#008C7E]">hr@namiraschool.com</a></p>
                            <p>Kraksaan, Dringu &amp; Kota Probolinggo, Jawa Timur</p>
                        </div>
                    </div>
                </div>

                <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-400">
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
                    class="fixed inset-0 z-[120] bg-slate-950/88 backdrop-blur-sm flex flex-col items-center justify-center p-4"
                    @click.self="lightboxPoster = null"
                >
                    <Motion
                        :initial="{ opacity: 0, scale: 0.94, y: 12 }"
                        :animate="{ opacity: 1, scale: 1, y: 0 }"
                        :exit="{ opacity: 0, scale: 0.95 }"
                        :transition="{ duration: 0.24, ease: 'easeOut' }"
                        class="w-full max-w-2xl flex flex-col items-center"
                    >
                        <div class="w-full flex items-center justify-between text-white mb-3 gap-3">
                            <h4 class="font-bold text-sm truncate">{{ lightboxPoster.title }}</h4>
                            <div class="flex items-center gap-2 shrink-0">
                                <a
                                    :href="lightboxPoster.url"
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
