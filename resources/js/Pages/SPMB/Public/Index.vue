<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';
import 'lenis/dist/lenis.css';
import { Motion, AnimatePresence } from 'motion-v';
import {
    ArrowRightIcon,
    ArrowUpRightIcon,
    ArrowUpIcon,
    PhoneIcon,
    UserCircleIcon,
    LockClosedIcon,
    MapPinIcon,
    ClockIcon,
    PlusIcon,
} from '@heroicons/vue/24/outline';

gsap.registerPlugin(ScrollTrigger);

const props = defineProps({
    units: Array,
    sdUnit: Object,
    sdSetting: Object,
    sdYearOptions: {
        type: Array,
        default: () => [],
    },
    sdKotaUnit: Object,
    sdKotaSetting: Object,
    sdKotaYearOptions: {
        type: Array,
        default: () => [],
    },
    smpUnit: Object,
    smpSetting: Object,
    smpYearOptions: {
        type: Array,
        default: () => [],
    },
    tkUnits: {
        type: Array,
        default: () => [],
    },
    tkUnlocked: {
        type: Boolean,
        default: false,
    },
    tkYearOptions: {
        type: Array,
        default: () => [],
    },
    canPreviewLockedUnits: {
        type: Boolean,
        default: false,
    },
    isLockedForPublic: Boolean,
});

const academicYearLabel = computed(() => props.sdSetting?.academic_year || '2027/2028');
const sdFutureYears = computed(() => (props.sdYearOptions || []).filter((y) => y.is_future_inden && !y.is_closed));
const sdKotaFutureYears = computed(() => (props.sdKotaYearOptions || []).filter((y) => y.is_future_inden && !y.is_closed));
const smpFutureYears = computed(() => (props.smpYearOptions || []).filter((y) => y.is_future_inden && !y.is_closed));
const tkFutureYears = computed(() => (props.tkYearOptions || []).filter((y) => y.is_future_inden && !y.is_closed));

const sdPrimaryOption = computed(() => (props.sdYearOptions || []).find((y) => !y.is_future_inden) || null);
const sdPrimaryClosed = computed(() => Boolean(sdPrimaryOption.value?.is_closed));
const sdDefaultOpenYear = computed(() => (props.sdYearOptions || []).find((y) => !y.is_closed)?.value || null);

const sdKotaPrimaryOption = computed(() => (props.sdKotaYearOptions || []).find((y) => !y.is_future_inden) || null);
const sdKotaPrimaryClosed = computed(() => Boolean(sdKotaPrimaryOption.value?.is_closed));
const sdKotaDefaultOpenYear = computed(() => (props.sdKotaYearOptions || []).find((y) => !y.is_closed)?.value || null);

const smpPrimaryOption = computed(() => (props.smpYearOptions || []).find((y) => !y.is_future_inden) || null);
const smpPrimaryClosed = computed(() => Boolean(smpPrimaryOption.value?.is_closed));
const smpDefaultOpenYear = computed(() => (props.smpYearOptions || []).find((y) => !y.is_closed)?.value || null);

const sdQuota = computed(() => props.sdSetting?.quota || 60);
const sdKotaQuota = computed(() => props.sdKotaSetting?.quota || 60);
const waNumber = computed(() => {
    const raw = String(props.sdSetting?.contact_whatsapp || '082332922521').replace(/[^0-9]/g, '');
    return raw.startsWith('0') ? '62' + raw.slice(1) : raw;
});

const waGeneralHref = computed(
    () => `https://wa.me/${waNumber.value}?text=${encodeURIComponent("Assalamu'alaikum Panitia SPMB Namira, saya ingin bertanya informasi pendaftaran.")}`
);
const waTkHref = computed(
    () => `https://wa.me/${waNumber.value}?text=${encodeURIComponent("Assalamu'alaikum Admin TK/KB Namira, saya ingin berkonsultasi pendaftaran anak.")}`
);

// Interactive flow tabs powered by Motion spring layoutId
const activeFlow = ref('sd');
const flowTabs = [
    { key: 'sd', label: 'Alur SD Kraksaan' },
    { key: 'sd_kota', label: 'Alur SD Kota' },
    { key: 'smp', label: 'Alur SMP Namira' },
];

const stepsByUnit = {
    sd: [
        {
            num: '01',
            tag: 'Online · 5 Menit',
            title: 'Pilih TA, Biodata & QRIS',
            desc: 'Pilih tahun ajaran masuk (TA berjalan atau Inden 2028/2029+), lengkapi biodata, lalu unggah bukti bayar QRIS.',
        },
        {
            num: '02',
            tag: 'Fleksibel di Portal',
            title: 'Lengkapi Berkas Digital',
            desc: 'Unggah Pas Foto, Kartu Keluarga, Akta Lahir, dan KTP orang tua langsung dari Portal Calon Siswa.',
        },
        {
            num: '03',
            tag: 'Tatap Muka',
            title: 'Observasi & Psikotes',
            desc: 'Hadir ke kampus SD Namira Kraksaan sesuai jadwal tahun ajaran pilihan untuk observasi & psikotes ananda.',
        },
        {
            num: '04',
            tag: 'Transparan',
            title: 'Pengumuman Kelulusan',
            desc: 'Pantau hasil seleksi secara real-time di portal dan unduh Surat Keterangan Penerimaan resmi.',
        },
        {
            num: '05',
            tag: 'Termin 60% VA',
            title: 'Daftar Ulang Bank Jatim',
            desc: 'Selesaikan biaya masuk secara bertahap (minimal termin 60%) melalui Virtual Account Bank Jatim.',
        },
    ],
    sd_kota: [
        {
            num: '01',
            tag: 'Jalur Inden',
            title: 'Pilih Tahun Ajaran Inden',
            desc: 'Pilih target tahun ajaran masuk SD Namira Kota Probolinggo (TA berjalan maupun Inden tahun mendatang) dan isi biodata.',
        },
        {
            num: '02',
            tag: 'Konfirmasi Bayar',
            title: 'Unggah Bukti Pendaftaran',
            desc: 'Lampirkan bukti pembayaran inden atau kuitansi resmi Anda untuk pembuatan akun Portal Calon Siswa.',
        },
        {
            num: '03',
            tag: 'Fleksibel',
            title: 'Lengkapi Dokumen',
            desc: 'Pas Foto, KK, Akta Kelahiran, dan KTP Orang Tua dapat dilengkapi kapan saja melalui Portal Calon Siswa.',
        },
        {
            num: '04',
            tag: 'Prioritas Kursi',
            title: 'Observasi & Pengumuman',
            desc: 'Pendaftar Inden diprioritaskan pada agenda observasi sesuai tahun ajaran yang dipilih dengan hasil transparan.',
        },
        {
            num: '05',
            tag: 'Resmi Siswa',
            title: 'Daftar Ulang & Penetapan',
            desc: 'Selesaikan administrasi daftar ulang melalui Virtual Account resmi dan terima status siswa SD Namira Kota.',
        },
    ],
    smp: [
        {
            num: '01',
            tag: 'Jalur Inden',
            title: 'Registrasi Calon Santri',
            desc: 'Pilih tahun ajaran masuk (TA berjalan atau Inden 2028/2029+), lengkapi biodata calon santri SMP Namira.',
        },
        {
            num: '02',
            tag: 'Verifikasi Berkas',
            title: 'Konfirmasi & Portal Akun',
            desc: 'Unggah bukti pembayaran pendaftaran dan pantau status verifikasi secara langsung melalui Portal Calon Siswa.',
        },
        {
            num: '03',
            tag: 'Seleksi Terpadu',
            title: 'Tes Akademik & Mengaji',
            desc: 'Ikuti asesmen akademik dasar, observasi baca Al-Qur’an, serta wawancara komitmen bersama orang tua.',
        },
        {
            num: '04',
            tag: 'Surat Resmi',
            title: 'Hasil & Rincian Biaya',
            desc: 'Akses pengumuman kelulusan beserta rincian daftar ulang transparan di akun portal Anda.',
        },
        {
            num: '05',
            tag: 'Konfirmasi Kursi',
            title: 'Daftar Ulang & Orientasi',
            desc: 'Amankan kursi Jalur Inden melalui transfer resmi Bank Jatim dan terima jadwal pra-orientasi santri.',
        },
    ],
};

const currentSteps = computed(() => stepsByUnit[activeFlow.value]);

// Subtle interactive FAQ accordion powered by Motion AnimatePresence
const openFaq = ref(0);
const faqs = [
    {
        q: 'Apakah bisa mendaftar Inden untuk Tahun Ajaran mendatang (2028/2029 atau 2029/2030)?',
        a: 'Bisa! Orang tua kini leluasa memesan kursi lebih awal melalui Jalur Inden multi-tahun ajaran (misalnya TA 2028/2029, 2029/2030, hingga 2030/2031) untuk SD Namira Kraksaan, SD Namira Kota Probolinggo, maupun SMP Namira langsung dari pilihan Tahun Ajaran di formulir pendaftaran.',
    },
    {
        q: 'Apakah pendaftaran bisa dilakukan sepenuhnya dari rumah?',
        a: 'Ya. Pengisian formulir, pembayaran QRIS/transfer, unggah dokumen, hingga pantauan pengumuman seluruhnya berjalan melalui Portal Calon Siswa. Kehadiran ke sekolah hanya dibutuhkan pada jadwal observasi & psikotes tatap muka.',
    },
    {
        q: 'Bagaimana skema pembayaran biaya daftar ulang?',
        a: 'Yayasan Namira menyediakan skema pembayaran daftar ulang resmi melalui Virtual Account Bank Jatim dengan ketentuan termin sesuai masing-masing satuan pendidikan.',
    },
    {
        q: 'Apakah lulusan TK Namira mendapat prioritas ke SD Namira?',
        a: 'Benar. Alumni TK Namira mendapatkan jalur prioritas / konfirmasi inden khusus saat melanjutkan ke jenjang SD Namira sesuai kuota gelombang berjalan.',
    },
];

// Marquee items
const marqueeItems = [
    'Akreditasi Unggul',
    'Metode Ummi & Tahfidz',
    'SD Namira Kraksaan',
    'SD Namira Kota Probolinggo',
    'SMP Namira Bilingual',
    'Inden Multi-Tahun Ajaran',
    `TA ${props.sdSetting?.academic_year || '2027/2028'} · 2028/2029 · 2029/2030`,
];

// Spotlight cursor state on dark flagship SD card
const flagshipRef = ref(null);
const spotlight = ref({ x: 50, y: 50, active: false });
const onFlagshipMove = (e) => {
    const el = flagshipRef.value;
    if (!el) return;
    const rect = el.getBoundingClientRect();
    spotlight.value = {
        x: ((e.clientX - rect.left) / rect.width) * 100,
        y: ((e.clientY - rect.top) / rect.height) * 100,
        active: true,
    };
};
const onFlagshipLeave = () => {
    spotlight.value.active = false;
};

// Magnetic CTA button helper (GSAP quickTo)
const attachMagnetic = (el) => {
    if (!el || window.matchMedia('(pointer: coarse)').matches) return () => {};
    const xTo = gsap.quickTo(el, 'x', { duration: 0.45, ease: 'power3.out' });
    const yTo = gsap.quickTo(el, 'y', { duration: 0.45, ease: 'power3.out' });
    const onMove = (e) => {
        const r = el.getBoundingClientRect();
        const dx = (e.clientX - (r.left + r.width / 2)) * 0.22;
        const dy = (e.clientY - (r.top + r.height / 2)) * 0.28;
        xTo(dx);
        yTo(dy);
    };
    const onLeave = () => {
        xTo(0);
        yTo(0);
    };
    el.addEventListener('mousemove', onMove);
    el.addEventListener('mouseleave', onLeave);
    return () => {
        el.removeEventListener('mousemove', onMove);
        el.removeEventListener('mouseleave', onLeave);
    };
};

// DOM refs & lifecycle instances
const pageRoot = ref(null);
const progressBarRef = ref(null);
const marqueeTrackRef = ref(null);
let lenisInstance = null;
let lenisRaf = null;
let gsapCtx = null;
let marqueeTween = null;
let cleanups = [];

const smoothScrollTo = (target) => {
    if (lenisInstance) {
        lenisInstance.scrollTo(target, { offset: -76, duration: 1.25 });
    } else if (typeof target === 'number') {
        window.scrollTo({ top: target, behavior: 'smooth' });
    } else {
        document.querySelector(target)?.scrollIntoView({ behavior: 'smooth' });
    }
};

const pauseMarquee = () => marqueeTween && gsap.to(marqueeTween, { timeScale: 0.15, duration: 0.5, ease: 'power2.out' });
const resumeMarquee = () => marqueeTween && gsap.to(marqueeTween, { timeScale: 1, duration: 0.6, ease: 'power2.out' });

onMounted(() => {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // 1. Lenis Smooth Scroll synced 1:1 with GSAP ticker + ScrollTrigger
    if (!prefersReduced) {
        lenisInstance = new Lenis({
            duration: 1.15,
            easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            smoothWheel: true,
            wheelMultiplier: 0.95,
            touchMultiplier: 1.4,
        });

        lenisInstance.on('scroll', ScrollTrigger.update);
        lenisRaf = (time) => {
            lenisInstance?.raf(time * 1000);
        };
        gsap.ticker.add(lenisRaf);
        gsap.ticker.lagSmoothing(0);
    }

    // 2. GSAP Master Choreography scoped to pageRoot
    gsapCtx = gsap.context(() => {
        if (prefersReduced) return;

        // Top scroll progress hairline
        if (progressBarRef.value) {
            gsap.to(progressBarRef.value, {
                scaleX: 1,
                ease: 'none',
                scrollTrigger: {
                    trigger: pageRoot.value,
                    start: 'top top',
                    end: 'bottom bottom',
                    scrub: 0.15,
                },
            });
        }

        // Hero above-the-fold editorial entrance timeline
        const heroTl = gsap.timeline({ defaults: { ease: 'power4.out' } });

        heroTl
            .from('.js-hero-eyebrow', { y: 18, opacity: 0, duration: 0.7 }, 0.05)
            .from('.js-hero-line', {
                yPercent: 115,
                rotate: 1.4,
                duration: 1.05,
                stagger: 0.11,
            }, 0.12)
            .from('.js-hero-copy', { y: 22, opacity: 0, duration: 0.8 }, 0.48)
            .from('.js-hero-cta', { y: 20, opacity: 0, duration: 0.75, stagger: 0.08 }, 0.58)
            .from('.js-hero-stat', { y: 18, opacity: 0, duration: 0.7, stagger: 0.07 }, 0.68)
            .fromTo(
                '.js-hero-frame',
                { clipPath: 'inset(14% 0% 0% 0% round 28px)', opacity: 0.4 },
                { clipPath: 'inset(0% 0% 0% 0% round 28px)', opacity: 1, duration: 1.25, ease: 'expo.out' },
                0.18
            )
            .from('.js-hero-photo', { scale: 1.16, duration: 1.45, ease: 'expo.out' }, 0.18)
            .from('.js-hero-float', { y: 26, opacity: 0, duration: 0.85, ease: 'power3.out' }, 0.68);

        // Hero photo scroll-scrubbed parallax
        gsap.to('.js-hero-photo', {
            yPercent: 10,
            ease: 'none',
            scrollTrigger: {
                trigger: '.js-hero-section',
                start: 'top top',
                end: 'bottom top',
                scrub: true,
            },
        });

        // Slow rotating architectural seal
        gsap.to('.js-seal-spin', {
            rotation: 360,
            duration: 30,
            repeat: -1,
            ease: 'none',
        });

        // Seamless GSAP marquee ticker
        if (marqueeTrackRef.value) {
            marqueeTween = gsap.to(marqueeTrackRef.value, {
                xPercent: -50,
                duration: 34,
                repeat: -1,
                ease: 'none',
            });
        }

        // Editorial section headings reveal
        gsap.utils.toArray('.js-reveal-heading').forEach((el) => {
            gsap.from(el, {
                y: 34,
                opacity: 0,
                duration: 0.9,
                ease: 'power3.out',
                scrollTrigger: {
                    trigger: el,
                    start: 'top 86%',
                    once: true,
                },
            });
        });

        // Asymmetric unit cards staggered entrance
        gsap.utils.toArray('.js-unit-card').forEach((card, i) => {
            gsap.from(card, {
                y: 48,
                opacity: 0,
                duration: 0.95,
                delay: i === 0 ? 0 : (i - 1) * 0.12,
                ease: 'power3.out',
                scrollTrigger: {
                    trigger: card,
                    start: 'top 85%',
                    once: true,
                },
            });

            const img = card.querySelector('.js-unit-photo');
            if (img) {
                gsap.fromTo(
                    img,
                    { scale: 1.12 },
                    {
                        scale: 1.0,
                        ease: 'none',
                        scrollTrigger: {
                            trigger: card,
                            start: 'top bottom',
                            end: 'bottom top',
                            scrub: true,
                        },
                    }
                );
            }
        });

        // Scroll-drawn golden progress spine along the 5-step Alur section
        gsap.fromTo(
            '.js-timeline-spine',
            { scaleX: 0 },
            {
                scaleX: 1,
                ease: 'none',
                scrollTrigger: {
                    trigger: '.js-timeline-section',
                    start: 'top 72%',
                    end: 'bottom 68%',
                    scrub: 0.4,
                },
            }
        );

        // Concierge CTA band entrance
        gsap.from('.js-concierge-band', {
            y: 36,
            opacity: 0,
            duration: 0.9,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: '.js-concierge-band',
                start: 'top 86%',
                once: true,
            },
        });

        // Magnetic pull on key CTA buttons
        pageRoot.value?.querySelectorAll('.js-magnetic').forEach((btn) => {
            cleanups.push(attachMagnetic(btn));
        });
    }, pageRoot.value);
});

onUnmounted(() => {
    cleanups.forEach((fn) => fn());
    cleanups = [];
    if (gsapCtx) gsapCtx.revert();
    if (lenisRaf) gsap.ticker.remove(lenisRaf);
    if (lenisInstance) {
        lenisInstance.destroy();
        lenisInstance = null;
    }
});
</script>

<template>
    <Head title="Penerimaan Siswa Baru (SPMB) — Sekolah Islam Namira" />

    <div
        id="top"
        ref="pageRoot"
        class="spmb-editorial min-h-screen bg-[#F6F2E9] text-[#0B2820] relative selection:bg-[#0B2820] selection:text-[#F6F2E9] overflow-x-clip"
    >
        <!-- Subtle architectural film grain texture -->
        <div class="pointer-events-none fixed inset-0 z-50 opacity-[0.03] spmb-grain" aria-hidden="true" />

        <!-- Admin Notice Banner (When locked for public) -->
        <div
            v-if="isLockedForPublic"
            class="relative z-40 bg-[#C4933F] text-[#0B2820] font-bold px-4 py-2 text-center text-xs border-b border-[#0B2820]/15 flex items-center justify-center gap-2"
        >
            <LockClosedIcon class="w-4 h-4 shrink-0" />
            <span>MODE PRATINJAU ADMINISTRATOR: Halaman pendaftaran ini sedang DIKUNCI untuk akses publik.</span>
        </div>

        <!-- Sticky Editorial Masthead -->
        <header class="sticky top-0 z-40 bg-[#F6F2E9]/88 backdrop-blur-xl border-b border-[#0B2820]/10">
            <!-- GSAP Scroll-Scrubbed Progress Hairline -->
            <div
                ref="progressBarRef"
                class="h-[2px] w-full bg-gradient-to-r from-[#0B2820] via-[#C4933F] to-[#DFB86B] origin-left scale-x-0"
            />

            <div class="max-w-[1320px] mx-auto px-5 sm:px-8 lg:px-10 h-[68px] flex items-center justify-between gap-4">
                <!-- Brand Crest -->
                <Link href="/" class="flex items-center gap-3 group">
                    <img
                        src="/images/landing/logo-yayasan.webp"
                        alt="Logo Yayasan Namira"
                        class="w-9 h-9 object-contain transition-transform duration-300 group-hover:scale-105"
                        onerror="this.style.display='none'"
                    />
                    <div class="border-l border-[#0B2820]/15 pl-3">
                        <span class="block text-[11px] font-extrabold tracking-[0.18em] uppercase text-[#0B2820] leading-tight">
                            Yayasan Namira
                        </span>
                        <span class="block text-[11px] text-[#0B2820]/60 font-medium leading-tight mt-0.5">
                            SPMB {{ academicYearLabel }}
                        </span>
                    </div>
                </Link>

                <!-- Editorial Anchor Nav (Lenis smooth jump) -->
                <nav class="hidden md:flex items-center gap-8 text-xs font-semibold tracking-wide text-[#0B2820]/70">
                    <button
                        type="button"
                        @click="smoothScrollTo('#jenjang')"
                        class="hover:text-[#0B2820] transition-colors cursor-pointer"
                    >
                        Pilih Jenjang
                    </button>
                    <button
                        type="button"
                        @click="smoothScrollTo('#alur')"
                        class="hover:text-[#0B2820] transition-colors cursor-pointer"
                    >
                        Alur Pendaftaran
                    </button>
                    <button
                        type="button"
                        @click="smoothScrollTo('#faq')"
                        class="hover:text-[#0B2820] transition-colors cursor-pointer"
                    >
                        Informasi
                    </button>
                    <button
                        type="button"
                        @click="smoothScrollTo('#concierge')"
                        class="hover:text-[#0B2820] transition-colors cursor-pointer"
                    >
                        Bantuan
                    </button>
                </nav>

                <!-- Portal Login -->
                <Link
                    :href="route('login')"
                    class="js-magnetic inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#0B2820] hover:bg-[#123D31] text-[#F6F2E9] text-xs font-semibold tracking-wide transition-colors"
                >
                    <UserCircleIcon class="w-4 h-4 text-[#DFB86B]" />
                    <span class="hidden sm:inline">Portal Calon Siswa</span>
                    <span class="sm:hidden">Portal</span>
                </Link>
            </div>
        </header>

        <!-- Flash Messages -->
        <div v-if="$page.props.flash?.error || $page.props.flash?.info" class="max-w-[1320px] mx-auto px-5 sm:px-8 lg:px-10 pt-5">
            <div class="p-4 rounded-2xl bg-[#C4933F] text-[#0B2820] font-bold text-xs flex items-center justify-between">
                <span>{{ $page.props.flash?.error || $page.props.flash?.info }}</span>
            </div>
        </div>

        <!-- ==================== SECTION 1: ASYMMETRIC ARCHITECTURAL HERO ==================== -->
        <section class="js-hero-section relative pt-10 sm:pt-14 lg:pt-20 pb-14 sm:pb-20 lg:pb-24">
            <div class="max-w-[1320px] mx-auto px-5 sm:px-8 lg:px-10">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
                    <!-- Left 7 Cols: Editorial Typography & Actions -->
                    <div class="lg:col-span-7 space-y-7">
                        <!-- Eyebrow -->
                        <div class="js-hero-eyebrow inline-flex items-center gap-3 text-[11px] font-mono-code uppercase tracking-[0.2em] text-[#0B2820]/75">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#C4933F] opacity-75" />
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-[#C4933F]" />
                            </span>
                            <span>Penerimaan Murid Baru · TA {{ academicYearLabel }}</span>
                        </div>

                        <!-- Monumental Masked-Line Editorial Headline -->
                        <h1 class="text-[clamp(2.55rem,5.9vw,5.15rem)] leading-[0.98] tracking-[-0.035em] text-[#0B2820]">
                            <span class="block overflow-hidden pb-1">
                                <span class="js-hero-line block font-extrabold">
                                    Menempa <em class="font-editorial font-normal italic text-[#9C7026] tracking-normal">fitrah,</em>
                                </span>
                            </span>
                            <span class="block overflow-hidden pb-1">
                                <span class="js-hero-line block font-extrabold">
                                    menumbuhkan
                                </span>
                            </span>
                            <span class="block overflow-hidden pb-2">
                                <span class="js-hero-line block font-editorial italic font-normal text-[#0B2820] tracking-[-0.015em]">
                                    generasi Qur’ani.
                                </span>
                            </span>
                        </h1>

                        <!-- Grounded Editorial Copy -->
                        <p class="js-hero-copy text-sm sm:text-base text-[#0B2820]/70 max-w-xl leading-[1.75]">
                            Sekolah Islam Terpadu Namira menghadirkan pendidikan berjenjang untuk
                            <strong class="font-semibold text-[#0B2820]">SD Kraksaan, SD Kota Probolinggo, SMP, dan TK/KB</strong>
                            — kini terbuka pula pemesanan kursi <strong class="font-semibold text-[#0B2820]">Jalur Inden Tahun Ajaran Mendatang</strong> (2028/2029, 2029/2030, dst.).
                        </p>

                        <!-- Magnetic Primary + Hairline Secondary Actions -->
                        <div class="flex flex-wrap items-center gap-3.5 pt-1">
                            <button
                                type="button"
                                @click="smoothScrollTo('#jenjang')"
                                class="js-hero-cta js-magnetic group inline-flex items-center gap-3 px-6 sm:px-7 py-3.5 rounded-full bg-[#0B2820] hover:bg-[#133E32] text-[#F6F2E9] text-xs sm:text-sm font-semibold tracking-wide transition-colors cursor-pointer shadow-[0_14px_30px_-12px_rgba(11,40,32,0.45)]"
                            >
                                <span>Pilih Jenjang &amp; Daftar</span>
                                <span class="w-6 h-6 rounded-full bg-[#DFB86B]/20 text-[#DFB86B] flex items-center justify-center transition-transform duration-300 group-hover:translate-x-0.5">
                                    <ArrowRightIcon class="w-3.5 h-3.5" />
                                </span>
                            </button>

                            <button
                                type="button"
                                @click="smoothScrollTo('#alur')"
                                class="js-hero-cta inline-flex items-center gap-2 px-5 py-3.5 rounded-full border border-[#0B2820]/20 hover:border-[#0B2820] text-[#0B2820] text-xs sm:text-sm font-semibold transition-colors cursor-pointer"
                            >
                                <span>Lihat 5 Tahapan</span>
                            </button>
                        </div>

                        <!-- Architectural Hairline Metrics Strip -->
                        <div class="pt-6 mt-2 border-t border-[#0B2820]/12 grid grid-cols-3 gap-4 sm:gap-8 max-w-xl">
                            <div class="js-hero-stat">
                                <span class="block font-mono-code text-[10px] uppercase tracking-[0.16em] text-[#9C7026] font-semibold">01 / Mutu</span>
                                <span class="block text-sm sm:text-base font-extrabold text-[#0B2820] mt-1">Akreditasi Unggul</span>
                            </div>
                            <div class="js-hero-stat border-l border-[#0B2820]/12 pl-4 sm:pl-6">
                                <span class="block font-mono-code text-[10px] uppercase tracking-[0.16em] text-[#9C7026] font-semibold">02 / Qur’an</span>
                                <span class="block text-sm sm:text-base font-extrabold text-[#0B2820] mt-1">Metode Ummi</span>
                            </div>
                            <div class="js-hero-stat border-l border-[#0B2820]/12 pl-4 sm:pl-6">
                                <span class="block font-mono-code text-[10px] uppercase tracking-[0.16em] text-[#9C7026] font-semibold">03 / Kuota SD</span>
                                <span class="block text-sm sm:text-base font-extrabold text-[#0B2820] mt-1">{{ sdQuota }} Kursi</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right 5 Cols: Framed Campus Photography with Scrub Parallax & Editorial Seal -->
                    <div class="lg:col-span-5 relative">
                        <div class="js-hero-frame relative rounded-[28px] overflow-hidden aspect-[4/4.6] bg-[#0B2820] shadow-[0_30px_70px_-24px_rgba(11,40,32,0.35)] border border-[#0B2820]/10">
                            <img
                                src="/images/landing/banner-2.webp"
                                alt="Lingkungan Kampus Sekolah Islam Namira"
                                class="js-hero-photo w-full h-[116%] -mt-[8%] object-cover"
                                onerror="this.onerror=null; this.src='/images/landing/banner-2.jpg';"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-[#0B2820]/80 via-[#0B2820]/15 to-transparent" />

                            <!-- Top-left coordinate tag -->
                            <div class="absolute top-4 left-4 px-3 py-1.5 rounded-full bg-[#F6F2E9]/90 backdrop-blur-md text-[10px] font-mono-code font-semibold tracking-[0.14em] uppercase text-[#0B2820]">
                                Probolinggo · Jawa Timur
                            </div>

                            <!-- Bottom Editorial Caption Overlay -->
                            <div class="js-hero-float absolute inset-x-4 bottom-4 rounded-2xl bg-[#F6F2E9]/92 backdrop-blur-md border border-white/60 p-4 flex items-center justify-between gap-3">
                                <div>
                                    <span class="block font-mono-code text-[10px] uppercase tracking-[0.16em] text-[#9C7026] font-semibold">
                                        Gelombang Aktif &amp; Inden
                                    </span>
                                    <p class="text-xs sm:text-sm font-extrabold text-[#0B2820] mt-0.5">
                                        SD Kraksaan, SD Kota &amp; SMP
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="smoothScrollTo('#jenjang')"
                                    class="w-9 h-9 rounded-full bg-[#0B2820] text-[#DFB86B] flex items-center justify-center shrink-0 hover:scale-105 transition-transform cursor-pointer"
                                    aria-label="Lanjut ke pilihan jenjang"
                                >
                                    <ArrowUpRightIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Rotating Circular Editorial Seal (Desktop accent) -->
                        <div class="hidden sm:flex items-center justify-center w-24 h-24 rounded-full bg-[#F6F2E9] border border-[#0B2820]/15 shadow-lg absolute -top-5 -right-4 z-10 pointer-events-none">
                            <svg class="js-seal-spin w-20 h-20" viewBox="0 0 100 100">
                                <defs>
                                    <path id="namiraSealCircle" d="M 50, 50 m -36, 0 a 36,36 0 1,1 72,0 a 36,36 0 1,1 -72,0" />
                                </defs>
                                <text class="fill-[#0B2820] text-[8.4px] font-mono-code uppercase tracking-[0.22em] font-semibold">
                                    <textPath href="#namiraSealCircle">
                                        SEKOLAH ISLAM NAMIRA • SPMB {{ academicYearLabel }} •
                                    </textPath>
                                </text>
                            </svg>
                            <span class="absolute w-2.5 h-2.5 rounded-full bg-[#C4933F]" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== SECTION 2: KINETIC EDITORIAL TICKER ==================== -->
        <div
            class="bg-[#0B2820] text-[#F6F2E9] py-3.5 border-y border-[#DFB86B]/20 overflow-hidden select-none"
            @mouseenter="pauseMarquee"
            @mouseleave="resumeMarquee"
        >
            <div ref="marqueeTrackRef" class="flex w-max items-center will-change-transform">
                <div v-for="copy in 2" :key="copy" class="flex items-center shrink-0">
                    <span
                        v-for="(item, idx) in marqueeItems"
                        :key="`${copy}-${idx}`"
                        class="inline-flex items-center gap-6 px-6 text-[11px] font-mono-code uppercase tracking-[0.22em] text-[#F6F2E9]/85 whitespace-nowrap"
                    >
                        <span>{{ item }}</span>
                        <span class="text-[#DFB86B] text-xs" aria-hidden="true">✦</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- ==================== SECTION 3: ASYMMETRIC EDITORIAL JENJANG SHOWCASE ==================== -->
        <section id="jenjang" class="py-20 sm:py-28">
            <div class="max-w-[1320px] mx-auto px-5 sm:px-8 lg:px-10">
                <!-- Editorial Section Header (Asymmetric Split) -->
                <div class="js-reveal-heading flex flex-col lg:flex-row lg:items-end justify-between gap-6 pb-10 mb-10 border-b border-[#0B2820]/12">
                    <div>
                        <span class="block font-mono-code text-[11px] uppercase tracking-[0.22em] text-[#9C7026] font-semibold">
                            01 / Pilihan Jenjang Pendidikan
                        </span>
                        <h2 class="text-3xl sm:text-5xl font-extrabold tracking-[-0.03em] text-[#0B2820] mt-2 leading-[1.05]">
                            Pilih satuan pendidikan <br class="hidden sm:inline" />
                            <em class="font-editorial font-normal italic text-[#9C7026] tracking-normal">sesuai tahap ananda.</em>
                        </h2>
                    </div>
                    <p class="text-xs sm:text-sm text-[#0B2820]/65 max-w-sm leading-relaxed">
                        Setiap unit memiliki kurikulum terintegrasi dengan jalur pendaftaran transparan serta pendampingan langsung panitia.
                    </p>
                </div>

                <!-- ROW 1: FULL-WIDTH FLAGSHIP CARD — SD NAMIRA KRAKSAAN -->
                <Motion
                    :while-hover="{ y: -4 }"
                    :transition="{ type: 'spring', stiffness: 320, damping: 28 }"
                    class="js-unit-card"
                >
                    <article
                        ref="flagshipRef"
                        @mousemove="onFlagshipMove"
                        @mouseleave="onFlagshipLeave"
                        class="relative rounded-[28px] bg-[#0B2820] text-[#F6F2E9] overflow-hidden border border-[#DFB86B]/20 shadow-[0_28px_70px_-28px_rgba(11,40,32,0.55)]"
                    >
                        <!-- Interactive Cursor Spotlight Glow -->
                        <div
                            class="pointer-events-none absolute inset-0 transition-opacity duration-500"
                            :style="{
                                opacity: spotlight.active ? 1 : 0,
                                background: `radial-gradient(520px circle at ${spotlight.x}% ${spotlight.y}%, rgba(223, 184, 107, 0.14), transparent 65%)`,
                            }"
                        />

                        <div class="grid grid-cols-1 lg:grid-cols-12 relative z-10">
                            <!-- Photo Wing (5 cols) -->
                            <div class="lg:col-span-5 relative min-h-[250px] sm:min-h-[320px] lg:min-h-full overflow-hidden bg-[#071D17]">
                                <img
                                    src="/images/landing/banner-1.webp"
                                    alt="Kampus SD Namira Kraksaan"
                                    class="js-unit-photo w-full h-full object-cover"
                                    onerror="this.onerror=null; this.src='/images/landing/banner-1.jpg';"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t lg:bg-gradient-to-r from-[#0B2820] via-[#0B2820]/25 to-transparent" />

                                <div class="absolute top-5 left-5 flex items-center gap-2.5">
                                    <div class="w-11 h-11 rounded-2xl bg-[#F6F2E9] p-1.5 shadow-md flex items-center justify-center">
                                        <img
                                            src="/images/landing/logo-sd.webp"
                                            alt="Logo SD Namira"
                                            class="w-full h-full object-contain"
                                            onerror="this.onerror=null; this.src='/images/landing/logo-sd.png';"
                                        />
                                    </div>
                                    <span
                                        v-if="sdPrimaryClosed"
                                        class="px-3 py-1 rounded-full bg-rose-600/95 text-white text-[10px] font-mono-code font-bold uppercase tracking-[0.14em]"
                                    >
                                        TA {{ sdSetting?.academic_year || academicYearLabel }} Penuh
                                    </span>
                                    <span class="px-3 py-1 rounded-full bg-[#DFB86B] text-[#0B2820] text-[10px] font-mono-code font-bold uppercase tracking-[0.14em]">
                                        {{ sdPrimaryClosed && sdDefaultOpenYear ? `Inden ${sdDefaultOpenYear}+ Dibuka` : 'Pendaftaran Dibuka' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Editorial Content Wing (7 cols) -->
                            <div class="lg:col-span-7 p-6 sm:p-9 lg:p-11 flex flex-col justify-between gap-7">
                                <div>
                                    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] font-mono-code uppercase tracking-[0.18em] text-[#DFB86B]">
                                        <span>Jenjang Dasar · Kraksaan</span>
                                        <span>Kuota {{ sdQuota }} Kursi</span>
                                    </div>

                                    <h3 class="text-2xl sm:text-4xl font-extrabold tracking-[-0.025em] text-white mt-3">
                                        SD Namira Kraksaan
                                    </h3>

                                    <p class="text-xs sm:text-sm text-[#F6F2E9]/75 mt-3 leading-relaxed max-w-xl">
                                        Sekolah Dasar Islam berstandar keunggulan akademik, pembelajaran Al-Qur’an Metode Ummi, serta pembiasaan adab islami mandiri dalam keseharian siswa.
                                    </p>

                                    <!-- 3-Column Hairline Spec Grid (Anti-Bullet-List) -->
                                    <div class="mt-7 pt-6 border-t border-white/12 grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5">
                                        <div>
                                            <span class="block font-mono-code text-[10px] uppercase tracking-[0.16em] text-[#DFB86B]">Gelombang</span>
                                            <p class="text-xs font-semibold text-white/95 mt-1 leading-snug">
                                                Jalur Umum &amp; Konfirmasi Inden
                                            </p>
                                        </div>
                                        <div class="sm:border-l sm:border-white/12 sm:pl-5">
                                            <span class="block font-mono-code text-[10px] uppercase tracking-[0.16em] text-[#DFB86B]">Asesmen</span>
                                            <p class="text-xs font-semibold text-white/95 mt-1 leading-snug">
                                                Observasi Dasar &amp; Psikotes Tatap Muka
                                            </p>
                                        </div>
                                        <div class="sm:border-l sm:border-white/12 sm:pl-5">
                                            <span class="block font-mono-code text-[10px] uppercase tracking-[0.16em] text-[#DFB86B]">Daftar Ulang</span>
                                            <p class="text-xs font-semibold text-white/95 mt-1 leading-snug">
                                                Termin Fleksibel 60% VA Bank Jatim
                                            </p>
                                        </div>
                                    </div>
                                    <!-- Multi-Year Inden Deep-Link Pills -->
                                    <div v-if="sdFutureYears.length > 0" class="mt-5 flex flex-wrap items-center gap-2">
                                        <span class="font-mono-code text-[10px] uppercase tracking-[0.16em] text-[#DFB86B]/90 mr-1">
                                            Inden Multi-TA:
                                        </span>
                                        <Link
                                            v-for="yr in sdFutureYears"
                                            :key="yr.value"
                                            :href="`/spmb/daftar/sd?ta=${encodeURIComponent(yr.value)}`"
                                            class="px-3 py-1 rounded-full bg-white/8 hover:bg-[#DFB86B] text-[#F6F2E9] hover:text-[#0B2820] border border-[#DFB86B]/30 text-[11px] font-mono-code font-semibold transition-colors"
                                        >
                                            Inden {{ yr.value }} &rarr;
                                        </Link>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-4 pt-2">
                                    <Link
                                        v-if="sdDefaultOpenYear"
                                        :href="sdPrimaryClosed ? `/spmb/daftar/sd?ta=${encodeURIComponent(sdDefaultOpenYear)}` : '/spmb/daftar/sd'"
                                        class="js-magnetic group inline-flex items-center gap-3 px-6 py-3.5 rounded-full bg-[#DFB86B] hover:bg-[#E7C782] text-[#0B2820] text-xs sm:text-sm font-extrabold tracking-wide transition-colors"
                                    >
                                        <span>
                                            {{ sdPrimaryClosed ? `Daftar Inden SD Kraksaan (${sdDefaultOpenYear})` : `Daftar SD Kraksaan (${sdSetting?.academic_year || academicYearLabel})` }}
                                        </span>
                                        <ArrowRightIcon class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1" />
                                    </Link>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-2 px-6 py-3.5 rounded-full bg-white/15 text-white/75 text-xs sm:text-sm font-bold cursor-not-allowed"
                                    >
                                        <LockClosedIcon class="w-4 h-4" />
                                        <span>Kuota Pendaftaran Penuh</span>
                                    </span>

                                    <span class="text-[11px] font-mono-code text-[#F6F2E9]/50">
                                        /spmb/daftar/sd
                                    </span>
                                </div>
                            </div>
                        </div>
                    </article>
                </Motion>

                <!-- ROW 2: ARCHITECTURAL 6/6 SPLIT — SD NAMIRA KOTA PROBOLINGGO + SMP NAMIRA -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mt-6">
                    <!-- SD NAMIRA KOTA PROBOLINGGO (Left 6 Cols) -->
                    <Motion
                        :while-hover="{ y: -5 }"
                        :transition="{ type: 'spring', stiffness: 320, damping: 28 }"
                        class="js-unit-card lg:col-span-6 flex"
                    >
                        <article class="w-full rounded-[28px] bg-white border border-[#0B2820]/10 overflow-hidden shadow-[0_18px_45px_-28px_rgba(11,40,32,0.22)] flex flex-col justify-between">
                            <div>
                                <!-- Editorial Media Strip -->
                                <div class="relative h-52 sm:h-60 overflow-hidden bg-[#0F3328]">
                                    <img
                                        src="/images/landing/banner-2.webp"
                                        alt="Kampus SD Namira Kota Probolinggo"
                                        class="js-unit-photo w-full h-full object-cover"
                                        onerror="this.onerror=null; this.src='/images/landing/banner-2.jpg';"
                                    />
                                    <div class="absolute inset-0 bg-gradient-to-t from-[#0B2820]/88 via-[#0B2820]/25 to-transparent" />
                                    <div class="absolute top-4 left-4 right-4 flex items-center justify-between gap-2">
                                        <div class="w-11 h-11 rounded-2xl bg-white p-1.5 shadow-sm flex items-center justify-center">
                                            <img
                                                src="/images/landing/logo-sd.webp"
                                                alt="Logo SD Namira Kota Probolinggo"
                                                class="w-full h-full object-contain"
                                                onerror="this.onerror=null; this.src='/images/landing/logo-sd.png';"
                                            />
                                        </div>
                                        <div class="flex flex-wrap items-center justify-end gap-1.5">
                                            <span
                                                v-if="sdKotaPrimaryClosed"
                                                class="px-2.5 py-1 rounded-full bg-rose-600/95 text-white text-[10px] font-mono-code font-bold uppercase tracking-[0.12em] shadow-xs"
                                            >
                                                TA {{ sdKotaSetting?.academic_year || academicYearLabel }} Penuh
                                            </span>
                                            <span
                                                v-if="sdKotaSetting?.is_active && sdKotaDefaultOpenYear"
                                                class="px-3 py-1 rounded-full bg-[#DFB86B] text-[#0B2820] text-[10px] font-mono-code font-bold uppercase tracking-[0.14em]"
                                            >
                                                {{ sdKotaPrimaryClosed ? `Inden ${sdKotaDefaultOpenYear}+ Dibuka` : 'Jalur Inden Dibuka' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="absolute bottom-4 left-5 right-5 flex items-end justify-between text-white">
                                        <div>
                                            <span class="block font-mono-code text-[10px] uppercase tracking-[0.18em] text-[#DFB86B]">
                                                Jenjang Dasar · Kota Probolinggo
                                            </span>
                                            <h3 class="text-2xl sm:text-3xl font-extrabold tracking-[-0.02em] mt-0.5">
                                                SD Namira Kota
                                            </h3>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-6 sm:p-8">
                                    <p class="text-xs sm:text-sm text-[#0B2820]/70 leading-relaxed">
                                        Satuan pendidikan Sekolah Dasar Islam terpadu di Kota Probolinggo dengan pembiasaan adab Qur’ani, Metode Ummi, dan kesiapan literasi global.
                                    </p>

                                    <div class="mt-6 pt-5 border-t border-[#0B2820]/10 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                                        <div>
                                            <span class="block font-mono-code text-[10px] uppercase tracking-[0.14em] text-[#9C7026] font-semibold">Jalur</span>
                                            <span class="block font-bold text-[#0B2820] mt-0.5">Inden SD Kota (SD-02)</span>
                                        </div>
                                        <div class="sm:border-l sm:border-[#0B2820]/10 sm:pl-4">
                                            <span class="block font-mono-code text-[10px] uppercase tracking-[0.14em] text-[#9C7026] font-semibold">Kuota</span>
                                            <span class="block font-bold text-[#0B2820] mt-0.5">{{ sdKotaQuota }} Kursi / TA</span>
                                        </div>
                                        <div class="sm:border-l sm:border-[#0B2820]/10 sm:pl-4">
                                            <span class="block font-mono-code text-[10px] uppercase tracking-[0.14em] text-[#9C7026] font-semibold">Berkas</span>
                                            <span class="block font-bold text-[#0B2820] mt-0.5">Unggah Fleksibel</span>
                                        </div>
                                    </div>

                                    <!-- Closed Primary Academic Year Notice for SD Kota -->
                                    <div
                                        v-if="sdKotaPrimaryClosed"
                                        class="mt-4 rounded-2xl bg-rose-50 border border-rose-200/90 px-3.5 py-2.5 text-[11px] text-rose-900 leading-snug"
                                    >
                                        <strong class="font-bold">TA {{ sdKotaSetting?.academic_year || academicYearLabel }} Tutup (Kuota Penuh):</strong>
                                        <span v-if="sdKotaDefaultOpenYear">
                                            Pendaftaran saat ini dibuka untuk <strong>Jalur Inden TA {{ sdKotaDefaultOpenYear }}</strong> dan tahun ajaran mendatang.
                                        </span>
                                        <span v-else>
                                            Seluruh kuota pendaftaran unit ini saat ini sudah penuh.
                                        </span>
                                    </div>

                                    <!-- Multi-Year Inden Deep-Link Pills -->
                                    <div v-if="sdKotaFutureYears.length > 0" class="mt-4 flex flex-wrap items-center gap-1.5">
                                        <span class="font-mono-code text-[10px] uppercase tracking-[0.14em] text-[#0B2820]/55 mr-1">
                                            Inden TA:
                                        </span>
                                        <Link
                                            v-for="yr in sdKotaFutureYears"
                                            :key="yr.value"
                                            :href="`/spmb/daftar/sd-kota?ta=${encodeURIComponent(yr.value)}`"
                                            class="px-2.5 py-1 rounded-full bg-[#F6F2E9] hover:bg-[#0B2820] text-[#0B2820] hover:text-[#DFB86B] border border-[#0B2820]/15 text-[11px] font-mono-code font-semibold transition-colors"
                                        >
                                            {{ yr.value }} &rarr;
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <div class="px-6 sm:px-8 pb-6 sm:pb-8 flex flex-wrap items-center justify-between gap-3">
                                <Link
                                    v-if="sdKotaDefaultOpenYear"
                                    :href="sdKotaPrimaryClosed ? `/spmb/daftar/sd-kota?ta=${encodeURIComponent(sdKotaDefaultOpenYear)}` : '/spmb/daftar/sd-kota'"
                                    class="group w-full sm:w-auto inline-flex items-center justify-center gap-3 px-6 py-3.5 rounded-full bg-[#0B2820] hover:bg-[#133E32] text-[#F6F2E9] text-xs sm:text-sm font-bold tracking-wide transition-colors"
                                >
                                    <span>
                                        {{ sdKotaPrimaryClosed ? `Daftar Inden SD Kota (${sdKotaDefaultOpenYear})` : `Daftar SD Kota (${sdKotaSetting?.academic_year || academicYearLabel})` }}
                                    </span>
                                    <ArrowRightIcon class="w-4 h-4 text-[#DFB86B] transition-transform duration-300 group-hover:translate-x-1" />
                                </Link>
                                <span
                                    v-else
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-full bg-slate-200 text-slate-600 text-xs sm:text-sm font-bold cursor-not-allowed"
                                >
                                    <LockClosedIcon class="w-4 h-4" />
                                    <span>Kuota Pendaftaran Penuh</span>
                                </span>
                            </div>
                        </article>
                    </Motion>

                    <!-- SMP NAMIRA (Right 6 Cols) -->
                    <Motion
                        :while-hover="{ y: -5 }"
                        :transition="{ type: 'spring', stiffness: 320, damping: 28 }"
                        class="js-unit-card lg:col-span-6 flex"
                    >
                        <article class="w-full rounded-[28px] bg-white border border-[#0B2820]/10 overflow-hidden shadow-[0_18px_45px_-28px_rgba(11,40,32,0.22)] flex flex-col justify-between">
                            <div>
                                <!-- Editorial Media Strip -->
                                <div class="relative h-52 sm:h-60 overflow-hidden bg-[#162C46]">
                                    <img
                                        src="/images/landing/photo-smp.webp"
                                        alt="Gedung SMP Namira"
                                        class="js-unit-photo w-full h-full object-cover"
                                        onerror="this.onerror=null; this.src='/images/landing/photo-smp.jpg';"
                                    />
                                    <div class="absolute inset-0 bg-gradient-to-t from-[#162C46]/85 via-[#162C46]/20 to-transparent" />
                                    <div class="absolute top-4 left-4 right-4 flex items-center justify-between gap-2">
                                        <div class="w-11 h-11 rounded-2xl bg-white p-1.5 shadow-sm flex items-center justify-center">
                                            <img
                                                src="/images/landing/logo-smp.webp"
                                                alt="Logo SMP Namira"
                                                class="w-full h-full object-contain"
                                                onerror="this.onerror=null; this.src='/images/landing/logo-smp.png';"
                                            />
                                        </div>
                                        <div class="flex flex-wrap items-center justify-end gap-1.5">
                                            <span
                                                v-if="smpPrimaryClosed"
                                                class="px-2.5 py-1 rounded-full bg-rose-600/95 text-white text-[10px] font-mono-code font-bold uppercase tracking-[0.12em]"
                                            >
                                                TA {{ smpSetting?.academic_year || academicYearLabel }} Penuh
                                            </span>
                                            <span
                                                v-if="smpSetting?.is_active && smpDefaultOpenYear"
                                                class="px-3 py-1 rounded-full bg-[#162C46] border border-white/25 text-white text-[10px] font-mono-code font-semibold uppercase tracking-[0.14em]"
                                            >
                                                {{ smpPrimaryClosed ? `Inden ${smpDefaultOpenYear}+ Dibuka` : 'Jalur Inden Dibuka' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="absolute bottom-4 left-5 right-5 flex items-end justify-between text-white">
                                        <div>
                                            <span class="block font-mono-code text-[10px] uppercase tracking-[0.18em] text-[#DFB86B]">
                                                Sekolah Menengah Pertama
                                            </span>
                                            <h3 class="text-2xl sm:text-3xl font-extrabold tracking-[-0.02em] mt-0.5">
                                                SMP Namira
                                            </h3>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-6 sm:p-8">
                                    <p class="text-xs sm:text-sm text-[#0B2820]/70 leading-relaxed">
                                        Sekolah Menengah Pertama Islam berwawasan global dengan penguasaan bilingual, budaya riset, dan program tahfidz intensif.
                                    </p>

                                    <div class="mt-6 pt-5 border-t border-[#0B2820]/10 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                                        <div>
                                            <span class="block font-mono-code text-[10px] uppercase tracking-[0.14em] text-[#162C46]/70 font-semibold">Jalur</span>
                                            <span class="block font-bold text-[#0B2820] mt-0.5">Calon Santri Inden</span>
                                        </div>
                                        <div class="sm:border-l sm:border-[#0B2820]/10 sm:pl-4">
                                            <span class="block font-mono-code text-[10px] uppercase tracking-[0.14em] text-[#162C46]/70 font-semibold">Seleksi</span>
                                            <span class="block font-bold text-[#0B2820] mt-0.5">Akademik &amp; Mengaji</span>
                                        </div>
                                        <div class="sm:border-l sm:border-[#0B2820]/10 sm:pl-4">
                                            <span class="block font-mono-code text-[10px] uppercase tracking-[0.14em] text-[#162C46]/70 font-semibold">Program</span>
                                            <span class="block font-bold text-[#0B2820] mt-0.5">Asrama &amp; Full Day</span>
                                        </div>
                                    </div>

                                    <div
                                        v-if="smpPrimaryClosed"
                                        class="mt-4 rounded-2xl bg-rose-50 border border-rose-200/90 px-3.5 py-2.5 text-[11px] text-rose-900 leading-snug"
                                    >
                                        <strong class="font-bold">TA {{ smpSetting?.academic_year || academicYearLabel }} Tutup (Kuota Penuh):</strong>
                                        <span v-if="smpDefaultOpenYear">
                                            Pendaftaran saat ini dibuka untuk <strong>Jalur Inden TA {{ smpDefaultOpenYear }}</strong> dan tahun ajaran mendatang.
                                        </span>
                                        <span v-else>
                                            Seluruh kuota pendaftaran unit ini saat ini sudah penuh.
                                        </span>
                                    </div>

                                    <!-- Multi-Year Inden Deep-Link Pills -->
                                    <div v-if="smpFutureYears.length > 0" class="mt-4 flex flex-wrap items-center gap-1.5">
                                        <span class="font-mono-code text-[10px] uppercase tracking-[0.14em] text-[#162C46]/60 mr-1">
                                            Inden TA:
                                        </span>
                                        <Link
                                            v-for="yr in smpFutureYears"
                                            :key="yr.value"
                                            :href="`/spmb/daftar/smp?ta=${encodeURIComponent(yr.value)}`"
                                            class="px-2.5 py-1 rounded-full bg-[#EEF2F7] hover:bg-[#162C46] text-[#162C46] hover:text-white border border-[#162C46]/15 text-[11px] font-mono-code font-semibold transition-colors"
                                        >
                                            {{ yr.value }} &rarr;
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <div class="px-6 sm:px-8 pb-6 sm:pb-8">
                                <Link
                                    v-if="smpDefaultOpenYear"
                                    :href="smpPrimaryClosed ? `/spmb/daftar/smp?ta=${encodeURIComponent(smpDefaultOpenYear)}` : '/spmb/daftar/smp'"
                                    class="group w-full sm:w-auto inline-flex items-center justify-center gap-3 px-6 py-3.5 rounded-full bg-[#162C46] hover:bg-[#1E3B5E] text-white text-xs sm:text-sm font-bold tracking-wide transition-colors"
                                >
                                    <span>
                                        {{ smpPrimaryClosed ? `Daftar Inden SMP (${smpDefaultOpenYear})` : `Daftar SMP (${smpSetting?.academic_year || academicYearLabel})` }}
                                    </span>
                                    <ArrowRightIcon class="w-4 h-4 text-[#DFB86B] transition-transform duration-300 group-hover:translate-x-1" />
                                </Link>
                                <span
                                    v-else
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-full bg-slate-200 text-slate-600 text-xs sm:text-sm font-bold cursor-not-allowed"
                                >
                                    <LockClosedIcon class="w-4 h-4" />
                                    <span>Kuota Pendaftaran Penuh</span>
                                </span>
                            </div>
                        </article>
                    </Motion>
                </div>

                <!-- ROW 3: WARM PARCHMENT ARCHITECTURAL BAND — TK & KB NAMIRA (UNLOCKABLE VIA ADMIN SETTINGS) -->
                <Motion
                    :while-hover="{ y: -4 }"
                    :transition="{ type: 'spring', stiffness: 320, damping: 28 }"
                    class="js-unit-card mt-6"
                >
                    <article class="rounded-[28px] bg-[#ECE5D6] border border-[#0B2820]/12 overflow-hidden">
                        <div class="grid grid-cols-1 lg:grid-cols-12">
                            <div class="lg:col-span-4 relative min-h-[220px] lg:min-h-full overflow-hidden bg-[#2C2418]">
                                <img
                                    src="/images/landing/banner-3.webp"
                                    alt="Lingkungan TK & KB Namira"
                                    class="js-unit-photo w-full h-full object-cover"
                                    onerror="this.onerror=null; this.src='/images/landing/banner-3.jpg';"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t lg:bg-gradient-to-r from-[#241D13]/85 via-[#241D13]/25 to-transparent" />
                                <div class="absolute top-4 left-4 right-4 flex items-center justify-between gap-2">
                                    <div class="w-11 h-11 rounded-2xl bg-[#F6F2E9] p-1.5 shadow-sm flex items-center justify-center">
                                        <img
                                            src="/images/landing/logo-tk.webp"
                                            alt="Logo TK & KB Namira"
                                            class="w-full h-full object-contain"
                                            onerror="this.onerror=null; this.src='/images/landing/logo-tk.png';"
                                        />
                                    </div>
                                    <span
                                        class="px-3 py-1 rounded-full text-[10px] font-mono-code font-semibold uppercase tracking-[0.14em]"
                                        :class="tkUnlocked ? 'bg-[#0B2820] text-[#DFB86B]' : 'bg-[#F6F2E9]/90 text-[#0B2820]'"
                                    >
                                        {{ tkUnlocked ? 'Pendaftaran Online Dibuka' : 'Kraksaan & Dringu' }}
                                    </span>
                                </div>
                            </div>

                            <div class="lg:col-span-8 p-6 sm:p-8 lg:p-9 flex flex-col justify-between gap-6">
                                <div>
                                    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] font-mono-code uppercase tracking-[0.18em] text-[#9C7026] font-semibold">
                                        <span>Pendidikan Usia Dini · TK &amp; KB Namira</span>
                                        <span>{{ tkUnlocked ? 'Tersedia Inden Multi-TA' : 'Pendaftaran Online Segera Dibuka' }}</span>
                                    </div>
                                    <h3 class="text-2xl sm:text-3xl font-extrabold tracking-[-0.02em] text-[#0B2820] mt-2">
                                        TK &amp; KB Namira Kraksaan &amp; Dringu
                                    </h3>
                                    <p class="text-xs sm:text-sm text-[#0B2820]/75 mt-2 leading-relaxed max-w-2xl">
                                        Fondasi karakter karimah, stimulasi motorik kreatif, dan kecintaan awal terhadap Al-Qur’an dalam ruang bermain yang aman — dengan prioritas khusus melanjutkan ke jenjang SD Namira.
                                    </p>

                                    <!-- Multi-Year Inden Pills (Shown publicly ONLY when TK is unlocked by Admin) -->
                                    <div v-if="tkUnlocked && tkFutureYears.length > 0" class="mt-4 flex flex-wrap items-center gap-1.5">
                                        <span class="font-mono-code text-[10px] uppercase tracking-[0.14em] text-[#9C7026] mr-1">
                                            Inden TA:
                                        </span>
                                        <Link
                                            v-for="yr in tkFutureYears"
                                            :key="yr.value"
                                            :href="`/spmb/daftar/tk?ta=${encodeURIComponent(yr.value)}`"
                                            class="px-2.5 py-1 rounded-full bg-[#F6F2E9] hover:bg-[#0B2820] text-[#0B2820] hover:text-[#DFB86B] border border-[#0B2820]/15 text-[11px] font-mono-code font-semibold transition-colors"
                                        >
                                            {{ yr.value }} &rarr;
                                        </Link>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <Link
                                        v-if="tkUnlocked"
                                        href="/spmb/daftar/tk"
                                        class="group inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-full bg-[#0B2820] hover:bg-[#133E32] text-[#F6F2E9] text-xs sm:text-sm font-bold tracking-wide transition-colors"
                                    >
                                        <span>Daftar TK &amp; KB Online</span>
                                        <ArrowRightIcon class="w-4 h-4 text-[#DFB86B] transition-transform duration-300 group-hover:translate-x-1" />
                                    </Link>

                                    <a
                                        :href="waTkHref"
                                        target="_blank"
                                        rel="noopener"
                                        class="group inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-full text-xs sm:text-sm font-bold tracking-wide transition-colors"
                                        :class="tkUnlocked
                                            ? 'border border-[#0B2820]/25 hover:border-[#0B2820] text-[#0B2820]'
                                            : 'bg-[#0B2820] hover:bg-[#133E32] text-[#F6F2E9]'"
                                    >
                                        <PhoneIcon class="w-4 h-4 text-[#DFB86B]" />
                                        <span>{{ tkUnlocked ? 'Konsultasi WA TK/KB' : 'Konsultasi & Info TK/KB' }}</span>
                                        <ArrowUpRightIcon class="w-4 h-4 opacity-70 transition-transform duration-300 group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                                    </a>

                                    <Link
                                        v-if="!tkUnlocked && canPreviewLockedUnits"
                                        href="/spmb/daftar/tk"
                                        class="inline-flex items-center gap-1.5 px-4 py-3 rounded-full bg-[#C4933F]/20 hover:bg-[#C4933F]/35 text-[#0B2820] border border-[#C4933F]/50 text-xs font-bold transition-colors"
                                    >
                                        <LockClosedIcon class="w-3.5 h-3.5" />
                                        <span>Pratinjau Form TK/KB (Admin)</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </article>
                </Motion>
            </div>
        </section>

        <!-- ==================== SECTION 4: SCROLL-DRIVEN 5-STEP ALUR + MOTION SPRING TABS ==================== -->
        <section id="alur" class="js-timeline-section py-20 sm:py-28 border-t border-[#0B2820]/10 bg-white/60">
            <div class="max-w-[1320px] mx-auto px-5 sm:px-8 lg:px-10">
                <div class="js-reveal-heading flex flex-col md:flex-row md:items-end justify-between gap-6 mb-14">
                    <div>
                        <span class="block font-mono-code text-[11px] uppercase tracking-[0.22em] text-[#9C7026] font-semibold">
                            02 / Alur Pendaftaran Transparan
                        </span>
                        <h2 class="text-3xl sm:text-5xl font-extrabold tracking-[-0.03em] text-[#0B2820] mt-2 leading-[1.06]">
                            Lima tahapan <em class="font-editorial font-normal italic text-[#9C7026] tracking-normal">terarah.</em>
                        </h2>
                    </div>

                    <!-- Motion Spring Pill Unit Switcher -->
                    <div class="inline-flex p-1.5 rounded-full bg-[#ECE5D6] border border-[#0B2820]/10 self-start md:self-auto">
                        <button
                            v-for="tab in flowTabs"
                            :key="tab.key"
                            type="button"
                            @click="activeFlow = tab.key"
                            class="relative px-4 sm:px-5 py-2 rounded-full text-xs font-bold transition-colors cursor-pointer"
                            :class="activeFlow === tab.key ? 'text-[#F6F2E9]' : 'text-[#0B2820]/70 hover:text-[#0B2820]'"
                        >
                            <Motion
                                v-if="activeFlow === tab.key"
                                layout-id="active-flow-pill"
                                :transition="{ type: 'spring', stiffness: 420, damping: 32 }"
                                class="absolute inset-0 rounded-full bg-[#0B2820]"
                            />
                            <span class="relative z-10 flex items-center gap-1.5">
                                <span>{{ tab.label }}</span>
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Scroll-Scrubbed Progress Spine (Desktop) -->
                <div class="hidden lg:block relative h-[2px] w-full bg-[#0B2820]/10 mb-10 overflow-hidden rounded-full">
                    <div class="js-timeline-spine h-full w-full bg-gradient-to-r from-[#0B2820] via-[#C4933F] to-[#DFB86B] origin-left" />
                </div>

                <!-- Animated 5-Step Grid with Motion AnimatePresence -->
                <AnimatePresence mode="wait">
                    <Motion
                        :key="activeFlow"
                        :initial="{ opacity: 0, y: 18 }"
                        :animate="{ opacity: 1, y: 0 }"
                        :exit="{ opacity: 0, y: -12 }"
                        :transition="{ duration: 0.35, ease: [0.22, 1, 0.36, 1] }"
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5"
                    >
                        <Motion
                            v-for="(step, idx) in currentSteps"
                            :key="step.num"
                            :initial="{ opacity: 0, y: 20 }"
                            :animate="{ opacity: 1, y: 0 }"
                            :while-hover="{ y: -5 }"
                            :transition="{ delay: idx * 0.055, type: 'spring', stiffness: 320, damping: 26 }"
                            class="group rounded-2xl bg-[#F6F2E9] hover:bg-white border border-[#0B2820]/10 hover:border-[#C4933F]/50 p-5 sm:p-6 flex flex-col justify-between transition-colors shadow-[0_10px_28px_-22px_rgba(11,40,32,0.28)]"
                        >
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-5">
                                    <span class="font-editorial italic text-4xl leading-none text-[#0B2820]/25 group-hover:text-[#C4933F] transition-colors">
                                        {{ step.num }}
                                    </span>
                                    <span class="font-mono-code text-[9.5px] uppercase tracking-[0.14em] px-2.5 py-1 rounded-full bg-white border border-[#0B2820]/10 text-[#0B2820]/70">
                                        {{ step.tag }}
                                    </span>
                                </div>
                                <h3 class="text-sm font-extrabold text-[#0B2820] tracking-tight leading-snug">
                                    {{ step.title }}
                                </h3>
                                <p class="text-xs text-[#0B2820]/65 leading-relaxed mt-2">
                                    {{ step.desc }}
                                </p>
                            </div>

                            <div class="mt-6 pt-3 border-t border-[#0B2820]/8 flex items-center justify-between text-[10px] font-mono-code text-[#9C7026] uppercase tracking-[0.15em]">
                                <span>Tahap {{ step.num }}</span>
                                <span class="w-1.5 h-1.5 rounded-full bg-[#C4933F]/60 group-hover:bg-[#C4933F]" />
                            </div>
                        </Motion>
                    </Motion>
                </AnimatePresence>
            </div>
        </section>

        <!-- ==================== SECTION 5: EDITORIAL FAQ + CONCIERGE ==================== -->
        <section id="faq" class="py-20 sm:py-24 border-t border-[#0B2820]/10">
            <div class="max-w-[1320px] mx-auto px-5 sm:px-8 lg:px-10">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-start">
                    <!-- Left 5 Cols -->
                    <div class="js-reveal-heading lg:col-span-5">
                        <span class="block font-mono-code text-[11px] uppercase tracking-[0.22em] text-[#9C7026] font-semibold">
                            03 / Pertanyaan Umum
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-extrabold tracking-[-0.03em] text-[#0B2820] mt-2 leading-[1.08]">
                            Hal yang sering ditanyakan <em class="font-editorial font-normal italic text-[#9C7026] tracking-normal">orang tua.</em>
                        </h2>
                        <p class="text-xs sm:text-sm text-[#0B2820]/65 mt-3 leading-relaxed">
                            Masih ada pertanyaan mengenai kuota, jadwal observasi, atau berkas? Tim panitia kami siap mendampingi Anda.
                        </p>
                    </div>

                    <!-- Right 7 Cols: Motion Accordion -->
                    <div class="lg:col-span-7 divide-y divide-[#0B2820]/12 border-y border-[#0B2820]/12">
                        <div v-for="(item, idx) in faqs" :key="idx" class="py-5">
                            <button
                                type="button"
                                @click="openFaq = openFaq === idx ? -1 : idx"
                                class="w-full flex items-center justify-between gap-4 text-left cursor-pointer group"
                            >
                                <span class="text-sm sm:text-base font-bold text-[#0B2820] group-hover:text-[#9C7026] transition-colors">
                                    {{ item.q }}
                                </span>
                                <span
                                    class="w-7 h-7 rounded-full border border-[#0B2820]/20 flex items-center justify-center shrink-0 transition-transform duration-300"
                                    :class="openFaq === idx ? 'rotate-45 bg-[#0B2820] text-[#DFB86B] border-[#0B2820]' : 'text-[#0B2820]'"
                                >
                                    <PlusIcon class="w-3.5 h-3.5" />
                                </span>
                            </button>

                            <AnimatePresence :initial="false">
                                <Motion
                                    v-if="openFaq === idx"
                                    :initial="{ height: 0, opacity: 0 }"
                                    :animate="{ height: 'auto', opacity: 1 }"
                                    :exit="{ height: 0, opacity: 0 }"
                                    :transition="{ duration: 0.28, ease: [0.22, 1, 0.36, 1] }"
                                    class="overflow-hidden"
                                >
                                    <p class="pt-3 pr-10 text-xs sm:text-sm text-[#0B2820]/70 leading-relaxed">
                                        {{ item.a }}
                                    </p>
                                </Motion>
                            </AnimatePresence>
                        </div>
                    </div>
                </div>

                <!-- Architectural Concierge Band -->
                <div
                    id="concierge"
                    class="js-concierge-band mt-16 sm:mt-20 rounded-[28px] bg-[#0B2820] text-[#F6F2E9] p-7 sm:p-11 lg:p-14 relative overflow-hidden border border-[#DFB86B]/20"
                >
                    <div class="pointer-events-none absolute -right-24 -top-24 w-72 h-72 rounded-full bg-[#DFB86B]/10 blur-3xl" />

                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-end justify-between gap-8">
                        <div class="max-w-xl">
                            <span class="block font-mono-code text-[11px] uppercase tracking-[0.2em] text-[#DFB86B] font-semibold">
                                Pusat Layanan Informasi SPMB
                            </span>
                            <h3 class="text-2xl sm:text-4xl font-extrabold tracking-[-0.025em] text-white mt-2 leading-tight">
                                Siap mendampingi langkah awal <em class="font-editorial font-normal italic text-[#DFB86B] tracking-normal">putra-putri Anda.</em>
                            </h3>
                            <div class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-[#F6F2E9]/70">
                                <span class="inline-flex items-center gap-2">
                                    <ClockIcon class="w-4 h-4 text-[#DFB86B]" />
                                    Senin – Sabtu, 07.30 – 15.00 WIB
                                </span>
                                <span class="inline-flex items-center gap-2">
                                    <MapPinIcon class="w-4 h-4 text-[#DFB86B]" />
                                    Kantor Yayasan Namira, Probolinggo
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 shrink-0">
                            <a
                                :href="waGeneralHref"
                                target="_blank"
                                rel="noopener"
                                class="js-magnetic inline-flex items-center gap-2.5 px-6 py-3.5 rounded-full bg-[#DFB86B] hover:bg-[#E7C782] text-[#0B2820] text-xs sm:text-sm font-extrabold tracking-wide transition-colors"
                            >
                                <PhoneIcon class="w-4 h-4" />
                                <span>Chat Panitia (WhatsApp)</span>
                            </a>
                            <Link
                                :href="route('login')"
                                class="inline-flex items-center gap-2 px-5 py-3.5 rounded-full border border-white/20 hover:border-white/50 text-white text-xs sm:text-sm font-semibold transition-colors"
                            >
                                <span>Login Portal</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Minimal Editorial Footer -->
        <footer class="border-t border-[#0B2820]/10 py-8">
            <div class="max-w-[1320px] mx-auto px-5 sm:px-8 lg:px-10 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-[#0B2820]/60">
                <p>&copy; {{ new Date().getFullYear() }} Yayasan Namira Kota Probolinggo. Seluruh hak cipta dilindungi.</p>
                <button
                    type="button"
                    @click="smoothScrollTo(0)"
                    class="inline-flex items-center gap-1.5 font-mono-code text-[11px] uppercase tracking-[0.16em] text-[#0B2820] hover:text-[#9C7026] transition-colors cursor-pointer"
                >
                    <span>Kembali ke Atas</span>
                    <ArrowUpIcon class="w-3.5 h-3.5" />
                </button>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.spmb-grain {
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    background-repeat: repeat;
    background-size: 180px 180px;
}
</style>
