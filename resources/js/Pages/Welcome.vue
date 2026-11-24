<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';
import 'lenis/dist/lenis.css';
import { Motion, AnimatePresence } from 'motion-v';
import {
    Bars3Icon,
    XMarkIcon,
    ArrowLeftIcon,
    ArrowRightIcon,
    ClockIcon,
    MapPinIcon,
    ChevronDownIcon,
    CalendarDaysIcon,
    BriefcaseIcon,
    AcademicCapIcon,
    PhoneIcon,
    GlobeAltIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';

gsap.registerPlugin(ScrollTrigger);

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    latestNews: {
        type: Array,
        default: () => [],
    },
    upcomingEvents: {
        type: Array,
        default: () => [],
    },
    partners: {
        type: Array,
        default: () => [],
    },
    destinations: {
        type: Array,
        default: () => [],
    },
    testimonials: {
        type: Array,
        default: () => [],
    },
    bannersList: {
        type: Array,
        default: () => [],
    },
    homeStats: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const appLogo = computed(() => page.props.app_settings?.app_logo || '/images/landing/logo-yayasan.webp');

// Navbar & Mobile Menu state
const isScrolled = ref(false);
const isMobileMenuOpen = ref(false);
const showUnitsDropdown = ref(false);

const handleDropdownFocusOut = (event, setter) => {
    if (!event.currentTarget.contains(event.relatedTarget)) {
        setter(false);
    }
};

// Banner Slider (uses real DB banners with titles + verified fallbacks)
const bannersCollection = computed(() => {
    if (props.bannersList && props.bannersList.length > 0) {
        return props.bannersList.map((b) => {
            const cleanPath = String(b.image_path || '').replace(/^\/+/, '');
            return {
                url: cleanPath.startsWith('http') ? cleanPath : '/' + cleanPath,
                title: b.title || 'Sekolah Islam Namira',
            };
        });
    }
    return [
        { url: '/images/landing/banner-1.webp', title: 'SD Namira Kraksaan' },
        { url: '/images/landing/photo-smp.webp', title: 'SMP Namira Kota Probolinggo' },
        { url: '/images/landing/banner-3.webp', title: 'TK, KB & Daycare Namira' },
    ];
});

const currentBanner = ref(0);
const bannerProgress = ref(0);
let bannerInterval = null;
let bannerRafId = null;

const startBannerInterval = () => {
    clearInterval(bannerInterval);
    if (bannerRafId) {
        cancelAnimationFrame(bannerRafId);
        bannerRafId = null;
    }
    bannerProgress.value = 0;
    const DURATION = 6000;
    let start = Date.now();

    bannerInterval = setInterval(() => {
        currentBanner.value = (currentBanner.value + 1) % bannersCollection.value.length;
        bannerProgress.value = 0;
        start = Date.now();
    }, DURATION);

    const tick = () => {
        if (!bannerInterval) return;
        bannerProgress.value = Math.min(((Date.now() - start) / DURATION) * 100, 100);
        if (bannerProgress.value < 100) {
            bannerRafId = requestAnimationFrame(tick);
        }
    };
    bannerRafId = requestAnimationFrame(tick);
};

const goToBanner = (index) => {
    clearInterval(bannerInterval);
    bannerInterval = null;
    currentBanner.value = index;
    startBannerInterval();
};

// Smart Banner Photo Matcher for Unit Cards
const findBannerPhoto = (keywords, fallback) => {
    const match = bannersCollection.value.find((b) => {
        const t = String(b.title || '').toLowerCase();
        return keywords.some((kw) => t.includes(kw));
    });
    return match?.url || fallback;
};

// 6 Symmetrical Flagship Units — Expanding Architectural Strip (Desktop) + Clean Tap Accordion (Mobile)
const hoveredCampus = ref(null);
const mobileExpandedCampus = ref(null);

const toggleMobileCampus = (idx) => {
    mobileExpandedCampus.value = mobileExpandedCampus.value === idx ? null : idx;
};

const jumpToCampus = (idx) => {
    showUnitsDropdown.value = false;
    mobileExpandedCampus.value = idx;
    hoveredCampus.value = idx;
    smoothScrollTo('#campuses');
};

const campuses = computed(() => [
    {
        id: 'daycare',
        name: 'Daycare Namira',
        shortName: 'Daycare',
        level: 'Pengasuhan Anak',
        logo: '/images/landing/logo-daycare.webp',
        photo: findBannerPhoto(['day care', 'daycare'], '/images/landing/banner-3.webp'),
        address: 'Kampus Kraksaan & Dringu',
        summary: 'Layanan pengasuhan aman, hangat, dan edukatif bagi keluarga aktif dengan laporan tumbuh kembang harian digital.',
        tags: ['Laporan Digital', 'Pengasuh Terlatih'],
        primaryCta: {
            label: 'Konsultasi WhatsApp',
            href: 'https://wa.me/6282332922521?text=' + encodeURIComponent("Assalamu'alaikum Admin Daycare Namira, saya ingin bertanya informasi layanan Daycare."),
            external: true,
        },
    },
    {
        id: 'pavlov',
        name: 'Pavlov Center',
        shortName: 'Pavlov',
        level: 'Tumbuh Kembang',
        logo: '/images/landing/logo-pavlov.webp',
        photo: findBannerPhoto(['kb', 'day care'], '/images/landing/banner-2.webp'),
        address: 'Gedung Pavlov Center, Probolinggo',
        summary: 'Pusat layanan pendampingan dan terapi tumbuh kembang ramah anak bersama praktisi berpengalaman.',
        tags: ['Pendampingan Khusus', 'Ramah Anak'],
        primaryCta: {
            label: 'Konsultasi Pavlov',
            href: 'https://wa.me/6282332922521?text=' + encodeURIComponent("Assalamu'alaikum Admin Pavlov Center Namira, saya ingin berkonsultasi."),
            external: true,
        },
    },
    {
        id: 'tk-kb',
        name: 'TK & KB Namira',
        shortName: 'TK & KB',
        level: 'PAUD & TK',
        logo: '/images/landing/logo-tk.webp',
        logo2: '/images/landing/logo-kb.webp',
        photo: findBannerPhoto(['kb - tk', 'kb', 'tk'], '/images/landing/banner-3.webp'),
        address: 'Kampus Kraksaan & Dringu',
        summary: 'Pendidikan anak usia dini berbasis fitrah dengan stimulasi motorik kreatif, pembiasaan adab, dan fondasi Al-Qur’an.',
        tags: ['Kraksaan & Dringu', 'Prioritas Lanjut SD'],
        primaryCta: {
            label: 'Konsultasi TK & KB',
            href: 'https://wa.me/6282332922521?text=' + encodeURIComponent("Assalamu'alaikum Admin TK & KB Namira, saya ingin bertanya informasi pendaftaran."),
            external: true,
        },
    },
    {
        id: 'sd-namira',
        name: 'SD Namira',
        shortName: 'SD Namira',
        level: 'Sekolah Dasar · 2 Kampus',
        spmbBadge: 'SPMB Buka',
        logo: '/images/landing/logo-sd.webp',
        photo: findBannerPhoto(['sd ', 'sd namira', 'gedung'], '/images/landing/banner-1.webp'),
        address: 'Kraksaan & Kota Probolinggo',
        summary: 'Sekolah Dasar Islam modern berakreditasi unggul berbasis karakter Qur’ani, Metode Ummi, dan literasi aktif. Tersedia jalur reguler & Inden multi-tahun.',
        tags: ['Kraksaan & Kota', 'Metode Ummi'],
        primaryCta: {
            label: 'SPMB SD Kraksaan',
            href: route('spmb.register.sd'),
            external: false,
        },
        secondaryCta: {
            label: 'Inden SD Kota',
            href: route('spmb.register.sd-kota'),
            external: false,
        },
    },
    {
        id: 'smp-namira',
        name: 'SMP Namira',
        shortName: 'SMP Namira',
        level: 'Sekolah Menengah',
        spmbBadge: 'SPMB Buka',
        logo: '/images/landing/logo-smp.webp',
        photo: findBannerPhoto(['smp'], '/images/landing/photo-smp.webp'),
        address: 'Jl. KH. Ahmad Dahlan, Kota Probolinggo',
        summary: 'Sekolah menengah Islam berwawasan global yang memadukan penguasaan bilingual, budaya riset, dan program tahfidz intensif.',
        tags: ['Bilingual & Riset', 'Full Day School'],
        primaryCta: {
            label: 'Daftar SPMB SMP',
            href: route('spmb.register.smp'),
            external: false,
        },
        website: 'https://www.smpnamiraprob.sch.id/',
        wa: 'https://wa.me/6285336778880',
        instagram: 'https://www.instagram.com/namirajuniorhighschool',
        youtube: 'https://www.youtube.com/@smpnamiraprobolinggo9462',
    },
    {
        id: 'tahfidz',
        name: 'Rumah Tahfidz',
        shortName: 'Rumah Tahfidz',
        level: 'Program Al-Qur’an',
        logo: '/images/landing/logo-tahfidz.webp',
        photo: findBannerPhoto(['sd namira', 'smp'], '/images/landing/banner-2.webp'),
        address: 'Lingkungan Yayasan Namira',
        summary: 'Pembinaan tahsin dan hafalan Al-Qur’an terstruktur bersama asatidz bersertifikat Metode Ummi dalam suasana kondusif.',
        tags: ['Tahsin & Tahfidz', 'Metode Ummi'],
        primaryCta: {
            label: 'Info Rumah Tahfidz',
            href: 'https://wa.me/6282332922521?text=' + encodeURIComponent("Assalamu'alaikum Admin Yayasan Namira, saya ingin bertanya tentang Rumah Tahfidz."),
            external: true,
        },
    },
]);

// Genuine Live Metrics from DB (Replaces hardcoded 3D yellow flip-cards!)
const impactMetrics = computed(() => {
    const units = Number(props.homeStats?.units_count || 0) > 0 ? Number(props.homeStats.units_count) : 10;
    const students = Number(props.homeStats?.students_count || 0) > 0 ? Number(props.homeStats.students_count) : 900;
    const educators = Number(props.homeStats?.educators_count || 0) > 0 ? Number(props.homeStats.educators_count) : 180;
    const partners = Number(props.homeStats?.partners_count || 0) > 0 ? Number(props.homeStats.partners_count) : 10;

    return [
        { target: units, suffix: '+', label: 'Unit Pendidikan', sub: 'Daycare, PAUD, TK, SD & SMP' },
        { target: students, suffix: '+', label: 'Peserta Didik Aktif', sub: 'Tersebar di Probolinggo' },
        { target: educators, suffix: '+', label: 'Pendidik & Staf', sub: 'Guru & pembina profesional' },
        { target: partners, suffix: '+', label: 'Mitra & Instansi', sub: 'Kolaborasi pendidikan & kesehatan' },
    ];
});

const openVacanciesCount = computed(() => Number(props.homeStats?.open_vacancies_count || 0));

// Deduplicated Partners Marquee (Removes duplicate names across units cleanly!)
const uniquePartners = computed(() => {
    const seen = new Set();
    const clean = [];
    for (const item of props.partners || []) {
        const key = String(item.name || '').trim().toLowerCase();
        if (!key || seen.has(key)) continue;
        seen.add(key);
        const path = String(item.logo_path || '').replace(/^\/+/, '');
        clean.push({
            id: item.id,
            name: item.name,
            logo: path ? (path.startsWith('http') ? path : '/' + path) : null,
        });
    }
    return clean;
});

// Formatting Helpers
const formatDateStr = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const getEventDateParts = (dateString) => {
    if (!dateString) return { day: '', month: '' };
    const date = new Date(dateString);
    return {
        day: date.toLocaleDateString('id-ID', { day: '2-digit' }),
        month: date.toLocaleDateString('id-ID', { month: 'short' }),
    };
};

const stripHtmlExcerpt = (news, limit = 105) => {
    const raw = news.excerpt || String(news.content || '').replace(/<[^>]*>/g, '');
    const clean = raw.replace(/\s+/g, ' ').trim();
    if (clean.length <= limit) return clean;
    return clean.slice(0, limit).trimEnd() + '...';
};

const normalizeAssetUrl = (path, fallback) => {
    if (!path) return fallback;
    const clean = String(path).replace(/^\/+/, '');
    return clean.startsWith('http') ? clean : '/' + clean;
};

// Outing Class & Lazy-Loaded Leaflet Map
const universitiesByType = computed(() => {
    const grouped = { indonesia: [], overseas: [] };
    (props.destinations || []).forEach((d) => {
        if (d.lat && d.lng) {
            const bucket = d.type === 'overseas' ? 'overseas' : 'indonesia';
            grouped[bucket].push({
                id: d.id,
                name: d.name,
                city: d.city + (d.country && d.country !== 'Indonesia' ? ', ' + d.country : ''),
                lat: parseFloat(d.lat),
                lng: parseFloat(d.lng),
                typeLabel: d.type === 'overseas' ? 'Internasional' : 'Nasional',
                unit: d.unit?.name || 'Yayasan Namira',
                visit_type_label: d.visit_type === 'alumni' ? 'Alumni' : 'Kunjungan',
                description: d.description || '',
            });
        }
    });
    return grouped;
});

const hasOverseasDestinations = computed(() => universitiesByType.value.overseas.length > 0);
const mapView = ref('indonesia');
const currentDestinations = computed(() => universitiesByType.value[mapView.value] || []);
const mapSectionRef = ref(null);
const mapContainer = ref(null);
let leafletLib = null;
let leafletMap = null;
let markersById = new Map();
let mapVisible = false;

const renderMap = async () => {
    if (!mapContainer.value || !mapVisible) return;

    if (!leafletLib) {
        const [{ default: L }] = await Promise.all([
            import('leaflet'),
            import('leaflet/dist/leaflet.css'),
        ]);
        leafletLib = L;
    }

    const L = leafletLib;
    if (leafletMap) {
        leafletMap.remove();
        leafletMap = null;
    }
    markersById.clear();

    const list = currentDestinations.value;
    const isMobile = window.innerWidth < 768;
    const defaultCenter =
        list.length === 1
            ? [list[0].lat, list[0].lng]
            : mapView.value === 'overseas'
              ? [20, 30]
              : [-4.5, 114];
    const defaultZoom =
        list.length === 1
            ? 10
            : mapView.value === 'overseas'
              ? isMobile ? 1 : 2
              : isMobile ? 4 : 5;

    leafletMap = L.map(mapContainer.value, {
        center: defaultCenter,
        zoom: defaultZoom,
        zoomControl: false,
        scrollWheelZoom: false,
        attributionControl: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
    }).addTo(leafletMap);

    const makeIcon = () =>
        L.divIcon({
            html: `<div style="width:14px;height:14px;background:#009688;border:2.5px solid #ffffff;border-radius:50%;box-shadow:0 0 0 4px rgba(0,150,136,0.25),0 4px 10px rgba(0,0,0,0.2);"></div>`,
            className: '',
            iconSize: [14, 14],
            iconAnchor: [7, 7],
        });

    list.forEach((dest) => {
        const marker = L.marker([dest.lat, dest.lng], { icon: makeIcon() })
            .bindPopup(
                `<div style="font-family:sans-serif;min-width:180px;">
                    <div style="font-size:10px;color:#64748b;font-weight:600;margin-bottom:2px;">${dest.city}</div>
                    <div style="font-size:13px;font-weight:800;color:#0f172a;margin-bottom:6px;">${dest.name}</div>
                    <div style="display:flex;gap:4px;flex-wrap:wrap;">
                        <span style="font-size:10px;background:#E6F7F1;color:#007468;padding:2px 7px;border-radius:999px;font-weight:700;">${dest.visit_type_label}</span>
                        <span style="font-size:10px;background:#f1f5f9;color:#475569;padding:2px 7px;border-radius:999px;font-weight:700;">${dest.unit}</span>
                    </div>
                </div>`,
                { className: 'namira-map-popup' }
            )
            .addTo(leafletMap);
        markersById.set(dest.id, marker);
    });

    L.control.zoom({ position: 'bottomright' }).addTo(leafletMap);
};

const focusDestinationOnMap = (dest) => {
    if (!leafletMap) return;
    leafletMap.flyTo([dest.lat, dest.lng], 11, { duration: 0.8 });
    const marker = markersById.get(dest.id);
    if (marker) marker.openPopup();
};

watch([mapView, () => props.destinations], async () => {
    await nextTick();
    renderMap();
});

// Testimonials (Clean 3-Card Pager on Desktop, Smooth Horizontal Swipe on Mobile!)
const testimonialsList = computed(() => {
    if (props.testimonials && props.testimonials.length > 0) {
        return props.testimonials.map((t) => ({
            id: t.id,
            name: t.name,
            quote: String(t.quote || '').replace(/^["“]+|["”]+$/g, '').trim(),
            photo: t.photo_path ? normalizeAssetUrl(t.photo_path, null) : null,
            role: t.role_or_title || 'Wali Murid',
            unit: t.unit?.name || '',
        }));
    }
    return [
        {
            id: 1,
            name: 'Ibu Ratna',
            quote: 'Kami bangga menyekolahkan anak kami di Namira. Sistem pendidikannya terpadu dan guru-gurunya sangat sabar membimbing.',
            photo: null,
            role: 'Wali Murid',
            unit: 'SD Namira',
        },
        {
            id: 2,
            name: 'Bapak Budi',
            quote: 'Karakter Islami dan kemandirian anak saya bertumbuh sangat baik sejak belajar di SMP Namira.',
            photo: null,
            role: 'Wali Murid',
            unit: 'SMP Namira',
        },
        {
            id: 3,
            name: 'Faurizatul Firdausi',
            quote: 'Bagi kami, Namira School bukan sekadar sekolah, tetapi mitra keluarga dalam mendidik generasi Qur’ani.',
            photo: null,
            role: 'Wali Murid',
            unit: 'TK Namira',
        },
    ];
});

const testiPage = ref(0);
const testiPageCount = computed(() => Math.max(1, Math.ceil(testimonialsList.value.length / 3)));
const desktopVisibleTestimonials = computed(() => {
    const start = testiPage.value * 3;
    return testimonialsList.value.slice(start, start + 3);
});

const nextTestiPage = () => {
    testiPage.value = (testiPage.value + 1) % testiPageCount.value;
};
const prevTestiPage = () => {
    testiPage.value = (testiPage.value - 1 + testiPageCount.value) % testiPageCount.value;
};

// Scroll, GSAP & Desktop-Only Lenis Setup
const pageRoot = ref(null);
const progressBarRef = ref(null);
let lenisInstance = null;
let lenisRaf = null;
let gsapCtx = null;
let mapObserver = null;
let scrollRafPending = false;

const handleScroll = () => {
    if (scrollRafPending) return;
    scrollRafPending = true;
    requestAnimationFrame(() => {
        isScrolled.value = window.scrollY > 40;
        scrollRafPending = false;
    });
};

const smoothScrollTo = (target) => {
    isMobileMenuOpen.value = false;
    if (lenisInstance) {
        lenisInstance.scrollTo(target, { offset: -76, duration: 1.0 });
    } else if (typeof target === 'number') {
        window.scrollTo({ top: target, behavior: 'smooth' });
    } else {
        const el = document.querySelector(target);
        if (el) {
            const y = el.getBoundingClientRect().top + window.scrollY - 72;
            window.scrollTo({ top: y, behavior: 'smooth' });
        }
    }
};

onMounted(() => {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const isFinePointer = window.matchMedia('(pointer: fine)').matches;

    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();
    startBannerInterval();

    // Lazy-init Leaflet map only when scrolled near
    if (mapSectionRef.value && 'IntersectionObserver' in window) {
        mapObserver = new IntersectionObserver(
            (entries) => {
                if (entries[0].isIntersecting) {
                    mapVisible = true;
                    renderMap();
                    mapObserver?.disconnect();
                }
            },
            { rootMargin: '240px 0px' }
        );
        mapObserver.observe(mapSectionRef.value);
    } else {
        mapVisible = true;
        renderMap();
    }

    // Smooth inertia on desktop; keep native silky touch momentum on mobile
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

        gsap.from('.hero-enter', {
            y: 16,
            opacity: 0,
            duration: 0.72,
            stagger: 0.09,
            ease: 'power3.out',
            clearProps: 'transform,opacity',
        });

        gsap.utils.toArray('.reveal-up').forEach((el) => {
            gsap.from(el, {
                y: 20,
                opacity: 0,
                duration: 0.62,
                ease: 'power2.out',
                clearProps: 'transform,opacity',
                scrollTrigger: {
                    trigger: el,
                    start: 'top 88%',
                    once: true,
                },
            });
        });

        gsap.utils.toArray('.home-stat-counter').forEach((el) => {
            const target = Number(el.dataset.target || 0);
            const suffix = el.dataset.suffix || '';
            const proxy = { val: 0 };
            ScrollTrigger.create({
                trigger: el,
                start: 'top 92%',
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
    }, pageRoot.value);
});

onUnmounted(() => {
    clearInterval(bannerInterval);
    bannerInterval = null;
    if (bannerRafId) cancelAnimationFrame(bannerRafId);
    window.removeEventListener('scroll', handleScroll);
    if (mapObserver) mapObserver.disconnect();
    if (leafletMap) {
        leafletMap.remove();
        leafletMap = null;
    }
    if (gsapCtx) gsapCtx.revert();
    if (lenisRaf) gsap.ticker.remove(lenisRaf);
    if (lenisInstance) lenisInstance.destroy();
});
</script>

<template>
    <Head title="Namira School — Sekolah Islam Terpadu Probolinggo (Daycare, PAUD, TK, SD, SMP)" />

    <div
        ref="pageRoot"
        class="min-h-screen bg-white text-slate-900 font-sans antialiased selection:bg-[#009688]/15 selection:text-[#006B60] flex flex-col overflow-x-clip"
    >
        <!-- Subtle Top Scroll Progress Hairline -->
        <div
            ref="progressBarRef"
            aria-hidden="true"
            class="fixed top-0 left-0 right-0 h-[2.5px] bg-[#009688] origin-left scale-x-0 z-50 pointer-events-none"
        ></div>

        <!-- =========================================================
             ADAPTIVE STICKY NAVBAR (100% Consistent Bahasa Indonesia)
        ========================================================== -->
        <header
            class="fixed top-0 left-0 right-0 z-40 transition-all duration-300"
            :class="
                isScrolled
                    ? 'bg-white/95 backdrop-blur-md border-b border-slate-100 shadow-[0_4px_20px_-10px_rgba(15,23,42,0.08)]'
                    : 'bg-gradient-to-b from-slate-950/70 via-slate-950/35 to-transparent'
            "
        >
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-[74px] flex items-center justify-between gap-4">
                <!-- Brand -->
                <button type="button" @click="smoothScrollTo('#home')" class="flex items-center gap-2.5 shrink-0 cursor-pointer">
                    <div class="w-9 h-9 rounded-xl bg-white p-1 shadow-xs border border-slate-100 flex items-center justify-center shrink-0 overflow-hidden">
                        <img :src="appLogo" alt="Logo Yayasan Namira" class="w-full h-full object-contain" />
                    </div>
                    <div class="flex items-baseline gap-1 select-none whitespace-nowrap">
                        <span
                            class="font-namira text-xl sm:text-2xl tracking-tight lowercase leading-none transition-colors"
                            :class="isScrolled ? 'text-[#1a4373]' : 'text-white drop-shadow-xs'"
                        >
                            namira
                        </span>
                        <span
                            class="font-school text-[11px] sm:text-xs tracking-wider uppercase font-extrabold leading-none transition-colors"
                            :class="isScrolled ? 'text-slate-500' : 'text-white/85'"
                        >
                            SCHOOL
                        </span>
                    </div>
                </button>

                <!-- Desktop Links -->
                <nav aria-label="Navigasi Utama" class="hidden lg:flex items-center gap-6 text-[13px] font-semibold">
                    <button
                        type="button"
                        @click="smoothScrollTo('#home')"
                        class="transition-colors cursor-pointer"
                        :class="isScrolled ? 'text-slate-600 hover:text-[#008C7E]' : 'text-white/90 hover:text-white'"
                    >
                        Beranda
                    </button>

                    <!-- Unit Sekolah Dropdown -->
                    <div
                        class="relative"
                        @mouseenter="showUnitsDropdown = true"
                        @mouseleave="showUnitsDropdown = false"
                        @focusin="showUnitsDropdown = true"
                        @focusout="handleDropdownFocusOut($event, (v) => (showUnitsDropdown = v))"
                    >
                        <button
                            type="button"
                            @click="smoothScrollTo('#campuses')"
                            class="inline-flex items-center gap-1 py-2 transition-colors cursor-pointer"
                            :class="isScrolled ? 'text-slate-600 hover:text-[#008C7E]' : 'text-white/90 hover:text-white'"
                        >
                            <span>Unit Sekolah</span>
                            <ChevronDownIcon class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': showUnitsDropdown }" />
                        </button>

                        <AnimatePresence>
                            <Motion
                                v-if="showUnitsDropdown"
                                :initial="{ opacity: 0, y: 6 }"
                                :animate="{ opacity: 1, y: 0 }"
                                :exit="{ opacity: 0, y: 4 }"
                                :transition="{ duration: 0.16 }"
                                class="absolute left-0 top-full pt-2 w-72 z-50"
                            >
                                <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-2 space-y-0.5">
                                    <button
                                        v-for="(unit, idx) in campuses"
                                        :key="unit.id"
                                        type="button"
                                        @click="jumpToCampus(idx)"
                                        class="w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-[#F2FAF7] text-left transition-colors cursor-pointer"
                                    >
                                        <div class="w-7 h-7 rounded-lg bg-slate-50 border border-slate-100 p-1 shrink-0 flex items-center justify-center">
                                            <img :src="unit.logo" :alt="unit.name" class="w-full h-full object-contain" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-slate-900 truncate">{{ unit.name }}</div>
                                            <div class="text-[10px] text-slate-500 truncate">{{ unit.address }}</div>
                                        </div>
                                    </button>
                                </div>
                            </Motion>
                        </AnimatePresence>
                    </div>

                    <Link
                        :href="route('spmb.index')"
                        class="inline-flex items-center gap-1.5 transition-colors"
                        :class="isScrolled ? 'text-slate-700 hover:text-[#008C7E]' : 'text-white hover:text-emerald-200'"
                    >
                        <span>SPMB Online</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    </Link>

                    <button
                        type="button"
                        @click="smoothScrollTo('#news')"
                        class="transition-colors cursor-pointer"
                        :class="isScrolled ? 'text-slate-600 hover:text-[#008C7E]' : 'text-white/90 hover:text-white'"
                    >
                        Berita &amp; Agenda
                    </button>

                    <Link
                        :href="route('careers.index')"
                        class="inline-flex items-center gap-1.5 transition-colors"
                        :class="isScrolled ? 'text-slate-600 hover:text-[#008C7E]' : 'text-white/90 hover:text-white'"
                    >
                        <span>Karir</span>
                        <span
                            v-if="openVacanciesCount > 0"
                            class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold leading-none"
                            :class="isScrolled ? 'bg-[#E6F7F1] text-[#007468]' : 'bg-white/20 text-white'"
                        >
                            {{ openVacanciesCount }}
                        </span>
                    </Link>

                    <button
                        type="button"
                        @click="smoothScrollTo('#footer')"
                        class="transition-colors cursor-pointer"
                        :class="isScrolled ? 'text-slate-600 hover:text-[#008C7E]' : 'text-white/90 hover:text-white'"
                    >
                        Kontak
                    </button>

                    <template v-if="canLogin">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="px-4 py-2 rounded-xl bg-[#009688] hover:bg-[#007D71] text-white text-xs font-bold shadow-2xs transition-colors"
                        >
                            Dashboard
                        </Link>
                        <Link
                            v-else
                            :href="route('login')"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-colors"
                            :class="
                                isScrolled
                                    ? 'border border-slate-200 hover:border-[#009688] hover:text-[#008C7E] text-slate-700'
                                    : 'bg-white text-slate-900 hover:bg-slate-100 shadow-xs'
                            "
                        >
                            Masuk Portal
                        </Link>
                    </template>
                </nav>

                <!-- Mobile Header Right -->
                <div class="flex lg:hidden items-center gap-2">
                    <Link
                        :href="route('spmb.index')"
                        class="px-3.5 py-2 min-h-[38px] rounded-xl bg-[#009688] text-white text-xs font-bold shadow-2xs inline-flex items-center"
                    >
                        SPMB
                    </Link>
                    <button
                        type="button"
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                        aria-label="Menu Navigasi"
                        class="p-2 min-w-[38px] min-h-[38px] rounded-xl flex items-center justify-center transition-colors cursor-pointer"
                        :class="isScrolled ? 'border border-slate-200 text-slate-800 bg-white' : 'bg-white/15 border border-white/25 text-white backdrop-blur-xs'"
                    >
                        <XMarkIcon v-if="isMobileMenuOpen" class="w-5 h-5" />
                        <Bars3Icon v-else class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Clean Light Mobile Menu Drawer -->
            <AnimatePresence>
                <Motion
                    v-if="isMobileMenuOpen"
                    :initial="{ opacity: 0, height: 0 }"
                    :animate="{ opacity: 1, height: 'auto' }"
                    :exit="{ opacity: 0, height: 0 }"
                    :transition="{ duration: 0.2 }"
                    class="lg:hidden bg-white border-t border-slate-100 shadow-xl overflow-hidden"
                >
                    <div class="px-4 py-4 space-y-1 text-sm font-semibold text-slate-800">
                        <button type="button" @click="smoothScrollTo('#home')" class="w-full text-left px-3 py-2.5 rounded-xl active:bg-[#ECF9F4]">Beranda</button>
                        <button type="button" @click="smoothScrollTo('#campuses')" class="w-full text-left px-3 py-2.5 rounded-xl active:bg-[#ECF9F4]">Unit Sekolah</button>
                        <Link :href="route('spmb.index')" @click="isMobileMenuOpen = false" class="flex items-center justify-between px-3 py-2.5 rounded-xl bg-[#ECF9F4] text-[#007468] font-bold">
                            <span>SPMB Online &amp; Inden</span>
                            <span class="px-2 py-0.5 rounded-full bg-[#009688] text-white text-[10px]">Dibuka</span>
                        </Link>
                        <button type="button" @click="smoothScrollTo('#news')" class="w-full text-left px-3 py-2.5 rounded-xl active:bg-[#ECF9F4]">Berita &amp; Artikel</button>
                        <button type="button" @click="smoothScrollTo('#events')" class="w-full text-left px-3 py-2.5 rounded-xl active:bg-[#ECF9F4]">Agenda &amp; Kegiatan</button>
                        <Link :href="route('careers.index')" @click="isMobileMenuOpen = false" class="flex items-center justify-between px-3 py-2.5 rounded-xl active:bg-[#ECF9F4]">
                            <span>Portal Karir &amp; Rekrutmen</span>
                            <span v-if="openVacanciesCount > 0" class="px-2 py-0.5 rounded-full bg-[#E6F7F1] text-[#007468] text-[10px] font-extrabold">
                                {{ openVacanciesCount }} Lowongan
                            </span>
                        </Link>
                        <button type="button" @click="smoothScrollTo('#footer')" class="w-full text-left px-3 py-2.5 rounded-xl active:bg-[#ECF9F4]">Kontak &amp; Alamat</button>

                        <div v-if="canLogin" class="pt-3 mt-2 border-t border-slate-100">
                            <Link
                                v-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                class="block w-full py-3 rounded-xl bg-[#009688] text-white text-center text-xs font-bold"
                            >
                                Buka Dashboard
                            </Link>
                            <Link
                                v-else
                                :href="route('login')"
                                class="block w-full py-3 rounded-xl border border-slate-200 text-slate-800 text-center text-xs font-bold"
                            >
                                Masuk Portal Siswa / Guru
                            </Link>
                        </div>
                    </div>
                </Motion>
            </AnimatePresence>
        </header>

        <main class="flex-1">
            <!-- =========================================================
                 HERO — Full-Bleed Real Campus Slider (Refined Editorial Craft)
            ========================================================== -->
            <section id="home" class="relative min-h-[82svh] sm:min-h-[88svh] lg:min-h-[92vh] flex items-center overflow-hidden bg-slate-900">
                <!-- Real Campus Photo Slideshow -->
                <div class="absolute inset-0 z-0">
                    <div
                        v-for="(slide, index) in bannersCollection"
                        :key="index"
                        class="absolute inset-0 transition-opacity duration-1000 ease-in-out"
                        :class="currentBanner === index ? 'opacity-100 z-10' : 'opacity-0 z-0'"
                    >
                        <img
                            :src="slide.url"
                            :alt="slide.title"
                            class="w-full h-full object-cover object-center transition-transform duration-[6000ms] ease-out"
                            :class="currentBanner === index ? 'scale-100' : 'scale-105'"
                            :loading="index === 0 ? 'eager' : 'lazy'"
                        />
                    </div>
                    <!-- Clean Directional Scrim for High Readability -->
                    <div
                        class="absolute inset-0 z-20"
                        style="background: linear-gradient(100deg, rgba(10,36,44,0.84) 0%, rgba(10,36,44,0.62) 48%, rgba(10,36,44,0.22) 100%);"
                    ></div>
                </div>

                <!-- Hero Foreground Content -->
                <div class="relative z-30 w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-20 sm:pt-28 sm:pb-24">
                    <div class="max-w-xl space-y-5 sm:space-y-6">
                        <div class="hero-enter inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/12 backdrop-blur-xs border border-white/20 text-[11px] sm:text-xs font-semibold text-emerald-100">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            <span>Yayasan Sekolah Islam Namira · Probolinggo</span>
                        </div>

                        <h1 class="hero-enter text-white tracking-[-0.025em] leading-[1.13]" style="font-size: clamp(2rem, 4.4vw, 3.45rem);">
                            <span class="font-normal text-white/90 block">Membentuk Generasi</span>
                            <span class="font-extrabold text-white block">Qur’ani, Cerdas &amp; Berakhlak</span>
                        </h1>

                        <p class="hero-enter text-sm sm:text-base text-white/80 leading-relaxed max-w-lg">
                            Ekosistem pendidikan Islam modern mulai dari Daycare, KB, TK, SD, hingga SMP yang memadukan keunggulan akademik, Metode Ummi, dan pembinaan karakter.
                        </p>

                        <div class="hero-enter flex flex-col sm:flex-row sm:items-center gap-3 pt-1">
                            <Link
                                :href="route('spmb.index')"
                                class="px-6 py-3.5 min-h-[48px] rounded-xl bg-[#009688] hover:bg-[#007D71] text-white text-xs sm:text-sm font-bold shadow-lg shadow-black/15 transition-all inline-flex items-center justify-center gap-2 group"
                            >
                                <span>Daftar SPMB Online</span>
                                <ArrowRightIcon class="w-4 h-4 transition-transform group-hover:translate-x-1" />
                            </Link>

                            <button
                                type="button"
                                @click="smoothScrollTo('#campuses')"
                                class="px-6 py-3.5 min-h-[48px] rounded-xl bg-white/12 hover:bg-white/20 text-white border border-white/30 backdrop-blur-xs text-xs sm:text-sm font-bold transition-all inline-flex items-center justify-center cursor-pointer"
                            >
                                Jelajahi Unit Sekolah
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Bottom Slider Caption + Progress Controls -->
                <div class="absolute bottom-12 sm:bottom-16 left-0 right-0 z-30">
                    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
                        <div class="hidden sm:inline-flex items-center gap-2 px-3 py-1 rounded-full bg-black/30 backdrop-blur-xs text-[11px] font-semibold text-white/90">
                            <MapPinIcon class="w-3.5 h-3.5 text-emerald-300" />
                            <span>{{ bannersCollection[currentBanner]?.title }}</span>
                        </div>

                        <div class="flex items-center gap-3 sm:gap-5 ml-auto">
                            <button
                                v-for="(_, i) in bannersCollection"
                                :key="i"
                                type="button"
                                @click="goToBanner(i)"
                                class="flex items-center gap-2 py-1 group cursor-pointer"
                                :aria-label="`Banner ${i + 1}`"
                            >
                                <span
                                    class="text-[11px] font-bold tabular-nums transition-colors"
                                    :class="currentBanner === i ? 'text-white' : 'text-white/45 group-hover:text-white/75'"
                                >
                                    0{{ i + 1 }}
                                </span>
                                <div
                                    class="h-[2px] rounded-full overflow-hidden transition-all duration-300"
                                    :class="currentBanner === i ? 'w-14 sm:w-20 bg-white/30' : 'w-6 sm:w-10 bg-white/15'"
                                >
                                    <div
                                        v-if="currentBanner === i"
                                        class="h-full bg-emerald-400 rounded-full"
                                        :style="{ width: bannerProgress + '%' }"
                                    ></div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 REAL IMPACT METRICS BAR (Replaces Yellow 3D Flip-Cards!)
            ========================================================== -->
            <section class="relative z-30 -mt-8 sm:-mt-12 px-4 sm:px-6 lg:px-8">
                <div class="max-w-6xl mx-auto bg-white rounded-2xl sm:rounded-3xl border border-slate-100 shadow-[0_18px_46px_-18px_rgba(15,23,42,0.12)] p-5 sm:p-7">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-5 lg:gap-0">
                        <div
                            v-for="(metric, idx) in impactMetrics"
                            :key="metric.label"
                            class="lg:px-6 first:lg:pl-2 last:lg:pr-2"
                            :class="idx > 0 ? 'lg:border-l lg:border-slate-100' : ''"
                        >
                            <div
                                class="home-stat-counter text-2xl sm:text-3xl font-extrabold text-slate-950 tracking-tight"
                                :data-target="metric.target"
                                :data-suffix="metric.suffix"
                            >
                                {{ metric.target.toLocaleString('id-ID') }}{{ metric.suffix }}
                            </div>
                            <div class="text-xs font-extrabold text-[#007468] mt-0.5">{{ metric.label }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">{{ metric.sub }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 UNIT PENDIDIKAN NAMIRA — Expanding Architectural Strip (Opsi 1)
                 Desktop: 6-Col Full-Width Expanding Panel Strip (Iconic Logo-First!)
                 Mobile: Compact Logo List w/ Smooth Inline Photo & CTA Reveal
            ========================================================== -->
            <section id="campuses" class="py-14 sm:py-20 space-y-7 sm:space-y-9">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-3 reveal-up">
                        <div class="space-y-1.5">
                            <h2 class="text-2xl sm:text-[2rem] tracking-tight text-slate-900">
                                <span class="font-normal">Unit Pendidikan </span>
                                <span class="font-extrabold">Sekolah Islam Namira</span>
                            </h2>
                            <p class="text-xs sm:text-sm text-slate-500 max-w-xl">
                                Ekosistem pendidikan berkesinambungan dari usia dini hingga menengah pertama di Kabupaten &amp; Kota Probolinggo.
                            </p>
                        </div>

                        <p class="hidden lg:inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#009688]"></span>
                            <span>Arahkan kursor pada unit untuk melihat detail</span>
                        </p>
                        <p class="lg:hidden text-[11px] font-semibold text-[#008C7E]">
                            Ketuk unit untuk melihat foto &amp; pendaftaran
                        </p>
                    </div>
                </div>

                <!-- DESKTOP: Iconic Full-Width 6-Column Expanding Architectural Strip -->
                <div
                    class="hidden lg:flex w-full h-[380px] xl:h-[415px] border-y border-slate-200/80 bg-[#F7FAF9] overflow-hidden"
                    @mouseleave="hoveredCampus = null"
                >
                    <div
                        v-for="(unit, idx) in campuses"
                        :key="unit.id"
                        @mouseenter="hoveredCampus = idx"
                        @focusin="hoveredCampus = idx"
                        tabindex="0"
                        class="relative min-w-0 h-full overflow-hidden border-r border-slate-200/80 last:border-r-0 cursor-pointer outline-none transition-[flex-grow] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)]"
                        :style="{
                            flexGrow: hoveredCampus === null ? 1 : hoveredCampus === idx ? 2.6 : 0.72,
                            flexBasis: '0%',
                        }"
                    >
                        <!-- RESTING STATE: Clean Proud Unit Logo Showcase -->
                        <div
                            class="absolute inset-0 z-10 flex flex-col items-center justify-center p-5 text-center transition-all duration-300"
                            :class="
                                hoveredCampus === idx
                                    ? 'opacity-0 scale-95 pointer-events-none'
                                    : hoveredCampus !== null
                                      ? 'opacity-70 scale-95 bg-[#F4F8F7]'
                                      : 'opacity-100 scale-100 bg-[#F7FAF9]'
                            "
                        >
                            <!-- Subtle SPMB Live Dot on SD & SMP -->
                            <span
                                v-if="unit.spmbBadge"
                                class="absolute top-5 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#E6F7F1] text-[#007468] text-[10px] font-bold tracking-wide transition-opacity duration-300"
                                :class="hoveredCampus !== null ? 'opacity-0' : 'opacity-100'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-[#009688]"></span>
                                <span>{{ unit.spmbBadge }}</span>
                            </span>

                            <!-- Single or Dual Unit Logo -->
                            <div v-if="!unit.logo2" class="h-16 xl:h-20 flex items-center justify-center mb-4">
                                <img :src="unit.logo" :alt="unit.name" class="max-h-full w-auto object-contain drop-shadow-xs" loading="lazy" />
                            </div>
                            <div v-else class="h-16 xl:h-20 flex items-center justify-center gap-2 mb-4">
                                <img :src="unit.logo" :alt="unit.name" class="h-12 xl:h-14 w-auto object-contain drop-shadow-xs" loading="lazy" />
                                <span class="w-px h-9 bg-slate-200"></span>
                                <img :src="unit.logo2" :alt="unit.name" class="h-12 xl:h-14 w-auto object-contain drop-shadow-xs" loading="lazy" />
                            </div>

                            <h3 class="text-xs xl:text-[13px] font-extrabold text-slate-900 tracking-wide uppercase px-1">
                                {{ unit.name }}
                            </h3>
                            <p
                                class="text-[11px] font-medium text-slate-500 mt-1 px-2 line-clamp-1 transition-opacity duration-300"
                                :class="hoveredCampus !== null ? 'opacity-0' : 'opacity-100'"
                            >
                                {{ unit.level }}
                            </p>
                            <span class="w-6 h-[2px] rounded-full bg-[#009688]/60 mt-3.5"></span>
                        </div>

                        <!-- EXPANDED HOVER STATE: Real Campus Photography + Editorial Scrim + Actions -->
                        <div
                            class="absolute inset-0 z-20 transition-opacity duration-300"
                            :class="hoveredCampus === idx ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'"
                        >
                            <img
                                :src="unit.photo"
                                :alt="unit.name"
                                loading="lazy"
                                class="w-full h-full object-cover transition-transform duration-700 ease-out"
                                :class="hoveredCampus === idx ? 'scale-100' : 'scale-[1.06]'"
                            />
                            <div
                                class="absolute inset-0"
                                style="background: linear-gradient(180deg, rgba(7,38,46,0.22) 0%, rgba(7,38,46,0.52) 48%, rgba(7,38,46,0.94) 100%);"
                            ></div>

                            <!-- Fixed-width Inner Stage prevents text reflow jitter while panel expands! -->
                            <div class="absolute inset-0 p-7 xl:p-8 flex flex-col justify-between min-w-[340px]">
                                <!-- Top Bar: Unit Logo Chip + Location -->
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-10 h-10 rounded-xl bg-white/95 p-1.5 shadow-md flex items-center justify-center">
                                            <img :src="unit.logo" :alt="unit.name" class="w-full h-full object-contain" />
                                        </div>
                                        <div v-if="unit.logo2" class="w-10 h-10 rounded-xl bg-white/95 p-1.5 shadow-md flex items-center justify-center">
                                            <img :src="unit.logo2" :alt="unit.name" class="w-full h-full object-contain" />
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 backdrop-blur-xs border border-white/20 text-[11px] font-semibold text-white">
                                        <MapPinIcon class="w-3.5 h-3.5 text-emerald-300 shrink-0" />
                                        <span class="truncate max-w-[200px]">{{ unit.address }}</span>
                                    </span>
                                </div>

                                <!-- Bottom Content & Direct CTAs -->
                                <div
                                    class="space-y-3.5 transition-all duration-500 delay-75"
                                    :class="hoveredCampus === idx ? 'translate-y-0 opacity-100' : 'translate-y-3 opacity-0'"
                                >
                                    <div>
                                        <div class="text-[11px] font-bold uppercase tracking-widest text-emerald-300">
                                            {{ unit.level }}
                                        </div>
                                        <h3 class="text-2xl font-extrabold text-white tracking-tight mt-0.5">
                                            {{ unit.name }}
                                        </h3>
                                    </div>

                                    <p class="text-xs xl:text-[13px] text-white/85 leading-relaxed max-w-md line-clamp-3">
                                        {{ unit.summary }}
                                    </p>

                                    <div class="flex flex-wrap items-center gap-2.5 pt-1">
                                        <component
                                            :is="unit.primaryCta.external ? 'a' : Link"
                                            :href="unit.primaryCta.href"
                                            :target="unit.primaryCta.external ? '_blank' : undefined"
                                            :rel="unit.primaryCta.external ? 'noopener noreferrer' : undefined"
                                            class="px-4 py-2.5 rounded-xl bg-[#009688] hover:bg-[#007D71] text-white text-xs font-bold shadow-md transition-colors inline-flex items-center gap-1.5"
                                        >
                                            <span>{{ unit.primaryCta.label }}</span>
                                            <ArrowRightIcon class="w-3.5 h-3.5" />
                                        </component>

                                        <component
                                            v-if="unit.secondaryCta"
                                            :is="unit.secondaryCta.external ? 'a' : Link"
                                            :href="unit.secondaryCta.href"
                                            class="px-4 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 text-white border border-white/30 backdrop-blur-xs text-xs font-bold transition-colors inline-flex items-center gap-1.5"
                                        >
                                            <span>{{ unit.secondaryCta.label }}</span>
                                            <ArrowRightIcon class="w-3.5 h-3.5" />
                                        </component>

                                        <!-- Quick External Icons (SMP Website / WA / IG / YT) -->
                                        <div v-if="unit.website || unit.wa || unit.instagram || unit.youtube" class="flex items-center gap-1.5 ml-auto">
                                            <a
                                                v-if="unit.website"
                                                :href="unit.website"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                title="Website Unit"
                                                class="w-8 h-8 rounded-lg bg-white/15 hover:bg-white hover:text-slate-900 text-white flex items-center justify-center transition-colors"
                                            >
                                                <GlobeAltIcon class="w-4 h-4" />
                                            </a>
                                            <a
                                                v-if="unit.wa"
                                                :href="unit.wa"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                title="WhatsApp Unit"
                                                class="w-8 h-8 rounded-lg bg-white/15 hover:bg-white hover:text-slate-900 text-white flex items-center justify-center transition-colors"
                                            >
                                                <PhoneIcon class="w-4 h-4" />
                                            </a>
                                            <a
                                                v-if="unit.instagram"
                                                :href="unit.instagram"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                title="Instagram"
                                                class="w-8 h-8 rounded-lg bg-white/15 hover:bg-white hover:text-slate-900 text-white flex items-center justify-center transition-colors"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MOBILE & TABLET: Clean Logo List with Smooth Inline Photo & CTA Expand -->
                <div class="lg:hidden max-w-6xl mx-auto px-4 sm:px-6 space-y-2.5">
                    <div
                        v-for="(unit, idx) in campuses"
                        :key="unit.id"
                        class="rounded-2xl border overflow-hidden transition-all"
                        :class="
                            mobileExpandedCampus === idx
                                ? 'bg-white border-[#009688]/40 shadow-[0_10px_28px_-14px_rgba(0,150,136,0.2)]'
                                : 'bg-[#F8FBFA] border-slate-200/75'
                        "
                    >
                        <button
                            type="button"
                            @click="toggleMobileCampus(idx)"
                            class="w-full px-4 py-3.5 flex items-center justify-between gap-3 text-left cursor-pointer"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-11 h-11 rounded-xl bg-white border border-slate-100 p-1.5 shadow-2xs shrink-0 flex items-center justify-center">
                                    <img :src="unit.logo" :alt="unit.name" class="w-full h-full object-contain" loading="lazy" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-extrabold text-slate-900 truncate">{{ unit.name }}</span>
                                        <span
                                            v-if="unit.spmbBadge"
                                            class="px-2 py-0.5 rounded-full bg-[#E6F7F1] text-[#007468] text-[9px] font-extrabold uppercase shrink-0"
                                        >
                                            {{ unit.spmbBadge }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] font-medium text-slate-500 truncate mt-0.5">{{ unit.level }} · {{ unit.address }}</div>
                                </div>
                            </div>
                            <ChevronDownIcon
                                class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-300"
                                :class="mobileExpandedCampus === idx ? 'rotate-180 text-[#008C7E]' : ''"
                            />
                        </button>

                        <AnimatePresence initial="false">
                            <Motion
                                v-if="mobileExpandedCampus === idx"
                                :initial="{ opacity: 0, height: 0 }"
                                :animate="{ opacity: 1, height: 'auto' }"
                                :exit="{ opacity: 0, height: 0 }"
                                :transition="{ duration: 0.24, ease: 'easeOut' }"
                                class="overflow-hidden"
                            >
                                <div class="px-4 pb-4 space-y-3.5 border-t border-slate-100 pt-3.5">
                                    <div class="relative h-44 rounded-xl overflow-hidden bg-slate-100">
                                        <img :src="unit.photo" :alt="unit.name" class="w-full h-full object-cover" />
                                        <div class="absolute bottom-2.5 left-2.5 inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-white/95 text-[10px] font-bold text-slate-800 shadow-xs">
                                            <MapPinIcon class="w-3 h-3 text-[#008C7E]" />
                                            <span>{{ unit.address }}</span>
                                        </div>
                                    </div>

                                    <p class="text-xs text-slate-600 leading-relaxed">{{ unit.summary }}</p>

                                    <div class="flex flex-wrap gap-2">
                                        <component
                                            :is="unit.primaryCta.external ? 'a' : Link"
                                            :href="unit.primaryCta.href"
                                            :target="unit.primaryCta.external ? '_blank' : undefined"
                                            :rel="unit.primaryCta.external ? 'noopener noreferrer' : undefined"
                                            class="flex-1 min-w-[140px] py-2.5 px-4 min-h-[44px] rounded-xl bg-[#009688] text-white text-xs font-bold inline-flex items-center justify-center gap-1.5"
                                        >
                                            <span>{{ unit.primaryCta.label }}</span>
                                            <ArrowRightIcon class="w-3.5 h-3.5" />
                                        </component>

                                        <component
                                            v-if="unit.secondaryCta"
                                            :is="unit.secondaryCta.external ? 'a' : Link"
                                            :href="unit.secondaryCta.href"
                                            class="flex-1 min-w-[130px] py-2.5 px-4 min-h-[44px] rounded-xl border border-[#009688]/30 bg-[#ECF9F4] text-[#007468] text-xs font-bold inline-flex items-center justify-center gap-1.5"
                                        >
                                            <span>{{ unit.secondaryCta.label }}</span>
                                            <ArrowRightIcon class="w-3.5 h-3.5" />
                                        </component>
                                    </div>
                                </div>
                            </Motion>
                        </AnimatePresence>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 SPMB MULTI-YEAR & INDEN HIGHLIGHT BAND (Clean Soft Mint)
            ========================================================== -->
            <section class="px-4 sm:px-6 lg:px-8 pb-14 sm:pb-20">
                <div class="max-w-6xl mx-auto reveal-up rounded-3xl bg-[#EDF9F4] border border-[#D4EFE5] p-6 sm:p-10 lg:p-12">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10 items-center">
                        <div class="lg:col-span-7 space-y-3">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white text-[#007468] text-[11px] font-bold shadow-2xs">
                                <AcademicCapIcon class="w-3.5 h-3.5" />
                                <span>SPMB Online &amp; Jalur Inden Multi-Tahun</span>
                            </div>
                            <h2 class="text-xl sm:text-[1.85rem] tracking-tight text-slate-950 leading-snug">
                                <span class="font-normal">Rencanakan Pendidikan Ananda </span>
                                <span class="font-extrabold">Lebih Awal Bersama Namira</span>
                            </h2>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed max-w-xl">
                                Pendaftaran online tersedia untuk Tahun Ajaran berjalan maupun <strong>Jalur Inden 2028/2029 &amp; 2029/2030</strong> pada unit SD Namira Kraksaan, SD Namira Kota Probolinggo, dan SMP Namira.
                            </p>
                        </div>

                        <div class="lg:col-span-5 flex flex-col sm:flex-row lg:flex-col gap-2.5">
                            <Link
                                :href="route('spmb.index')"
                                class="w-full py-3.5 px-6 min-h-[48px] rounded-xl bg-[#009688] hover:bg-[#007D71] text-white text-xs sm:text-sm font-bold shadow-xs transition-colors inline-flex items-center justify-center gap-2 group"
                            >
                                <span>Buka Portal SPMB Online</span>
                                <ArrowRightIcon class="w-4 h-4 transition-transform group-hover:translate-x-1" />
                            </Link>
                            <div class="grid grid-cols-3 gap-2">
                                <Link :href="route('spmb.register.sd')" class="py-2.5 px-2 rounded-xl bg-white hover:border-[#009688] border border-slate-200/80 text-center text-[11px] font-bold text-slate-700 transition-colors">
                                    SD Kraksaan
                                </Link>
                                <Link :href="route('spmb.register.sd-kota')" class="py-2.5 px-2 rounded-xl bg-white hover:border-[#009688] border border-slate-200/80 text-center text-[11px] font-bold text-slate-700 transition-colors">
                                    SD Kota
                                </Link>
                                <Link :href="route('spmb.register.smp')" class="py-2.5 px-2 rounded-xl bg-white hover:border-[#009688] border border-slate-200/80 text-center text-[11px] font-bold text-slate-700 transition-colors">
                                    SMP Namira
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 BERITA & INFORMASI TERBARU (Bright Editorial Cards — High Contrast!)
            ========================================================== -->
            <section id="news" class="py-12 sm:py-20 border-t border-slate-100">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-7 sm:space-y-10">
                    <div class="flex items-end justify-between gap-4 reveal-up">
                        <div class="space-y-1">
                            <h2 class="text-xl sm:text-[1.85rem] tracking-tight text-slate-900">
                                <span class="font-normal">Berita &amp; </span>
                                <span class="font-extrabold">Informasi Terbaru</span>
                            </h2>
                            <p class="text-xs sm:text-sm text-slate-500">Kabar kegiatan belajar, prestasi siswa, dan informasi resmi Yayasan Namira.</p>
                        </div>
                        <Link
                            :href="route('news.index')"
                            class="text-xs sm:text-sm font-bold text-[#008C7E] hover:text-[#006B60] inline-flex items-center gap-1 shrink-0 transition-colors"
                        >
                            <span class="hidden sm:inline">Lihat Semua Berita</span>
                            <span class="sm:hidden">Semua</span>
                            <ArrowRightIcon class="w-4 h-4" />
                        </Link>
                    </div>

                    <div
                        v-if="latestNews.length > 0"
                        class="flex md:grid md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5 overflow-x-auto md:overflow-visible snap-x snap-mandatory -mx-4 px-4 md:mx-0 md:px-0 pb-2 md:pb-0 no-scrollbar"
                    >
                        <Link
                            v-for="news in latestNews"
                            :key="news.id"
                            :href="news.slug ? route('news.show', news.slug) : route('news.index')"
                            class="w-[78vw] sm:w-[300px] md:w-auto shrink-0 snap-start bg-white rounded-2xl border border-slate-200/80 shadow-[0_6px_22px_-14px_rgba(15,23,42,0.08)] hover:shadow-[0_14px_34px_-16px_rgba(0,150,136,0.16)] hover:border-[#009688]/40 transition-all overflow-hidden flex flex-col justify-between group"
                        >
                            <div>
                                <div class="relative aspect-[16/10] bg-slate-100 overflow-hidden">
                                    <img
                                        :src="normalizeAssetUrl(news.image_path, '/images/landing/banner-1.webp')"
                                        :alt="news.title"
                                        loading="lazy"
                                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                    />
                                    <span
                                        v-if="news.unit?.name"
                                        class="absolute top-2.5 left-2.5 px-2.5 py-0.5 rounded-full bg-white/95 text-[#007468] text-[10px] font-bold shadow-2xs"
                                    >
                                        {{ news.unit.name }}
                                    </span>
                                </div>

                                <div class="p-4 sm:p-5 space-y-2">
                                    <div class="text-[11px] font-semibold text-slate-400">
                                        {{ formatDateStr(news.published_at) }}
                                    </div>
                                    <h3 class="text-sm font-extrabold text-slate-900 leading-snug line-clamp-2 group-hover:text-[#008C7E] transition-colors">
                                        {{ news.title }}
                                    </h3>
                                    <p class="text-xs text-slate-500 leading-relaxed line-clamp-2">
                                        {{ stripHtmlExcerpt(news, 95) }}
                                    </p>
                                </div>
                            </div>

                            <div class="px-4 sm:px-5 pb-4 pt-1">
                                <span class="text-xs font-bold text-[#008C7E] inline-flex items-center gap-1">
                                    <span>Baca Selengkapnya</span>
                                    <ArrowRightIcon class="w-3.5 h-3.5 transition-transform group-hover:translate-x-1" />
                                </span>
                            </div>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 AGENDA & KEGIATAN SEKOLAH (Airy Light Cards)
            ========================================================== -->
            <section id="events" class="py-12 sm:py-20 bg-[#F8FCFA] border-y border-slate-100">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-7 sm:space-y-10">
                    <div class="flex items-end justify-between gap-4 reveal-up">
                        <div class="space-y-1">
                            <h2 class="text-xl sm:text-[1.85rem] tracking-tight text-slate-900">
                                <span class="font-normal">Agenda &amp; </span>
                                <span class="font-extrabold">Kegiatan Sekolah</span>
                            </h2>
                            <p class="text-xs sm:text-sm text-slate-500">Jadwal kegiatan akademik, keagamaan, dan acara kebersamaan keluarga Namira.</p>
                        </div>
                        <Link
                            :href="route('events.index')"
                            class="text-xs sm:text-sm font-bold text-[#008C7E] hover:text-[#006B60] inline-flex items-center gap-1 shrink-0 transition-colors"
                        >
                            <span class="hidden sm:inline">Lihat Semua Agenda</span>
                            <span class="sm:hidden">Semua</span>
                            <ArrowRightIcon class="w-4 h-4" />
                        </Link>
                    </div>

                    <div
                        v-if="upcomingEvents.length > 0"
                        class="flex md:grid md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 overflow-x-auto md:overflow-visible snap-x snap-mandatory -mx-4 px-4 md:mx-0 md:px-0 pb-2 md:pb-0 no-scrollbar"
                    >
                        <Link
                            v-for="event in upcomingEvents.slice(0, 6)"
                            :key="event.id"
                            :href="event.slug ? route('events.show', event.slug) : route('events.index')"
                            class="w-[80vw] sm:w-[320px] md:w-auto shrink-0 snap-start bg-white rounded-2xl border border-slate-200/80 hover:border-[#009688]/40 hover:shadow-md transition-all overflow-hidden flex flex-col justify-between group"
                        >
                            <div>
                                <div class="relative aspect-[16/9] bg-slate-100 overflow-hidden">
                                    <img
                                        :src="normalizeAssetUrl(event.image_path, '/images/landing/banner-2.webp')"
                                        :alt="event.title"
                                        loading="lazy"
                                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                    />
                                    <div class="absolute top-3 left-3 px-2.5 py-1.5 rounded-xl bg-white/95 backdrop-blur-xs shadow-sm text-center leading-none min-w-[48px]">
                                        <div class="text-sm font-extrabold text-slate-950">{{ getEventDateParts(event.start_date).day }}</div>
                                        <div class="text-[10px] font-bold uppercase text-[#008C7E] mt-0.5">{{ getEventDateParts(event.start_date).month }}</div>
                                    </div>
                                </div>

                                <div class="p-4 sm:p-5 space-y-2.5">
                                    <h3 class="text-sm font-extrabold text-slate-900 leading-snug line-clamp-2 group-hover:text-[#008C7E] transition-colors">
                                        {{ event.title }}
                                    </h3>
                                    <div class="space-y-1 text-xs text-slate-500">
                                        <div class="flex items-center gap-1.5">
                                            <CalendarDaysIcon class="w-3.5 h-3.5 text-[#008C7E] shrink-0" />
                                            <span>{{ formatDateStr(event.start_date) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <MapPinIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                            <span class="truncate">{{ event.location || 'Kampus Namira' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="px-4 sm:px-5 pb-4 pt-1">
                                <span class="text-xs font-bold text-[#008C7E] inline-flex items-center gap-1">
                                    <span>Detail Kegiatan</span>
                                    <ArrowRightIcon class="w-3.5 h-3.5 transition-transform group-hover:translate-x-1" />
                                </span>
                            </div>
                        </Link>
                    </div>

                    <div v-else class="rounded-2xl bg-white border border-dashed border-slate-200 p-10 text-center space-y-2">
                        <h3 class="text-sm font-extrabold text-slate-800">Belum Ada Agenda Terdekat</h3>
                        <p class="text-xs text-slate-500 max-w-md mx-auto">Pantau halaman ini secara berkala untuk informasi kegiatan mendatang dari Sekolah Islam Namira.</p>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 JEJAK KUNJUNGAN & OUTING CLASS (Proportional & Lazy Map)
            ========================================================== -->
            <section v-if="destinations.length > 0" id="destinations" ref="mapSectionRef" class="py-12 sm:py-20">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-10 items-center">
                        <!-- Left Details & Location List -->
                        <div class="lg:col-span-5 space-y-5 reveal-up">
                            <div class="space-y-2">
                                <h2 class="text-xl sm:text-[1.85rem] tracking-tight text-slate-900">
                                    <span class="font-normal">Jejak Kunjungan &amp; </span>
                                    <span class="font-extrabold">Outing Class</span>
                                </h2>
                                <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                                    Pembelajaran kontekstual di luar kelas untuk memperluas wawasan siswa dan menumbuhkan keberanian bereksplorasi.
                                </p>
                            </div>

                            <!-- Toggle only if overseas pins also exist -->
                            <div v-if="hasOverseasDestinations" class="inline-flex p-1 rounded-xl bg-slate-100 border border-slate-200/60">
                                <button
                                    type="button"
                                    @click="mapView = 'indonesia'"
                                    class="px-4 py-1.5 rounded-lg text-xs font-bold transition-colors cursor-pointer"
                                    :class="mapView === 'indonesia' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500'"
                                >
                                    Nasional
                                </button>
                                <button
                                    type="button"
                                    @click="mapView = 'overseas'"
                                    class="px-4 py-1.5 rounded-lg text-xs font-bold transition-colors cursor-pointer"
                                    :class="mapView === 'overseas' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500'"
                                >
                                    Internasional
                                </button>
                            </div>

                            <!-- Interactive Destination Cards -->
                            <div class="space-y-2.5 max-h-[240px] overflow-y-auto pr-1">
                                <button
                                    v-for="dest in currentDestinations"
                                    :key="dest.id"
                                    type="button"
                                    @click="focusDestinationOnMap(dest)"
                                    class="w-full text-left p-4 rounded-2xl bg-[#F8FCFA] hover:bg-[#EDF9F4] border border-slate-100 hover:border-[#009688]/30 transition-all flex items-start gap-3 cursor-pointer"
                                >
                                    <div class="w-9 h-9 rounded-xl bg-white border border-slate-100 text-[#008C7E] flex items-center justify-center shrink-0">
                                        <MapPinIcon class="w-4 h-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs sm:text-sm font-extrabold text-slate-900 truncate">{{ dest.name }}</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">{{ dest.city }} · {{ dest.unit }}</div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Right Compact Lazy-Loaded Map -->
                        <div class="lg:col-span-7 reveal-up">
                            <div class="relative rounded-2xl sm:rounded-3xl overflow-hidden border border-slate-200/80 shadow-[0_14px_38px_-18px_rgba(15,23,42,0.14)] bg-slate-100">
                                <div ref="mapContainer" class="h-[260px] sm:h-[340px] w-full"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 KATA ORANG TUA & ALUMNI (Soft Pastel Mint Wave Band!)
            ========================================================== -->
            <section id="testimonials" class="relative bg-[#EDF9F4] py-16 sm:py-24 overflow-hidden">
                <svg
                    aria-hidden="true"
                    viewBox="0 0 1440 54"
                    fill="none"
                    preserveAspectRatio="none"
                    class="absolute top-0 left-0 w-full h-6 sm:h-12 text-white"
                >
                    <path d="M0 0H1440V18C1080 58 360 58 0 18V0Z" fill="currentColor" />
                </svg>

                <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 sm:space-y-10">
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 reveal-up">
                        <div class="space-y-1.5">
                            <h2 class="text-xl sm:text-[1.85rem] tracking-tight text-slate-900">
                                <span class="font-normal">Suara </span>
                                <span class="font-extrabold">Orang Tua &amp; Keluarga Namira</span>
                            </h2>
                            <p class="text-xs sm:text-sm text-slate-600">
                                Pengalaman nyata para wali murid yang mempercayakan tumbuh kembang ananda di Sekolah Islam Namira.
                            </p>
                        </div>

                        <div class="flex items-center gap-2 self-start sm:self-auto">
                            <button
                                v-if="testiPageCount > 1"
                                type="button"
                                @click="prevTestiPage"
                                aria-label="Testimoni sebelumnya"
                                class="hidden md:flex w-9 h-9 rounded-xl bg-white border border-slate-200 hover:border-[#009688] text-slate-700 items-center justify-center transition cursor-pointer"
                            >
                                <ArrowLeftIcon class="w-4 h-4" />
                            </button>
                            <button
                                v-if="testiPageCount > 1"
                                type="button"
                                @click="nextTestiPage"
                                aria-label="Testimoni berikutnya"
                                class="hidden md:flex w-9 h-9 rounded-xl bg-white border border-slate-200 hover:border-[#009688] text-slate-700 items-center justify-center transition cursor-pointer"
                            >
                                <ArrowRightIcon class="w-4 h-4" />
                            </button>
                            <Link
                                :href="route('testimonials.index')"
                                class="text-xs font-bold text-[#007468] hover:underline ml-1"
                            >
                                Lihat Semua →
                            </Link>
                        </div>
                    </div>

                    <!-- Mobile Horizontal Snap Cards (All Published Testimonials) -->
                    <div class="flex md:hidden gap-3.5 overflow-x-auto snap-x snap-mandatory -mx-4 px-4 pb-2 no-scrollbar">
                        <article
                            v-for="t in testimonialsList"
                            :key="t.id"
                            class="w-[84vw] shrink-0 snap-start bg-white rounded-2xl border border-white p-5 shadow-xs flex flex-col justify-between gap-4"
                        >
                            <p class="text-xs text-slate-600 leading-relaxed line-clamp-6">
                                “{{ t.quote }}”
                            </p>
                            <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
                                <img
                                    v-if="t.photo"
                                    :src="t.photo"
                                    :alt="t.name"
                                    loading="lazy"
                                    class="w-10 h-10 rounded-full object-cover border border-slate-100 shrink-0"
                                />
                                <div v-else class="w-10 h-10 rounded-full bg-[#E6F7F1] text-[#007468] font-extrabold text-xs flex items-center justify-center shrink-0">
                                    {{ t.name?.charAt(0) || 'N' }}
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-extrabold text-slate-900 truncate">{{ t.name }}</div>
                                    <div class="text-[11px] text-slate-500 truncate">{{ t.role }}<span v-if="t.unit"> · {{ t.unit }}</span></div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <!-- Desktop Animated 3-Card Pager -->
                    <div class="hidden md:block">
                        <AnimatePresence mode="wait">
                            <Motion
                                :key="testiPage"
                                :initial="{ opacity: 0, y: 10 }"
                                :animate="{ opacity: 1, y: 0 }"
                                :exit="{ opacity: 0, y: -8 }"
                                :transition="{ duration: 0.25 }"
                                class="grid grid-cols-3 gap-5"
                            >
                                <article
                                    v-for="t in desktopVisibleTestimonials"
                                    :key="t.id"
                                    class="bg-white rounded-2xl border border-white p-6 shadow-[0_10px_28px_-16px_rgba(15,23,42,0.10)] flex flex-col justify-between gap-5"
                                >
                                    <p class="text-[13px] text-slate-600 leading-relaxed line-clamp-6">
                                        “{{ t.quote }}”
                                    </p>
                                    <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                                        <img
                                            v-if="t.photo"
                                            :src="t.photo"
                                            :alt="t.name"
                                            loading="lazy"
                                            class="w-11 h-11 rounded-full object-cover border border-slate-100 shrink-0"
                                        />
                                        <div v-else class="w-11 h-11 rounded-full bg-[#E6F7F1] text-[#007468] font-extrabold text-xs flex items-center justify-center shrink-0">
                                            {{ t.name?.charAt(0) || 'N' }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-extrabold text-slate-900 truncate">{{ t.name }}</div>
                                            <div class="text-[11px] text-slate-500 truncate">{{ t.role }}<span v-if="t.unit"> · {{ t.unit }}</span></div>
                                        </div>
                                    </div>
                                </article>
                            </Motion>
                        </AnimatePresence>

                        <div v-if="testiPageCount > 1" class="flex items-center justify-center gap-2 pt-6">
                            <button
                                v-for="p in testiPageCount"
                                :key="p"
                                type="button"
                                @click="testiPage = p - 1"
                                :aria-label="`Halaman testimoni ${p}`"
                                class="h-1.5 rounded-full transition-all cursor-pointer"
                                :class="testiPage === p - 1 ? 'w-7 bg-[#009688]' : 'w-2 bg-[#009688]/25 hover:bg-[#009688]/50'"
                            ></button>
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
                 MITRA & KEMITRAAN (Deduplicated Clean Logo Strip)
            ========================================================== -->
            <section v-if="uniquePartners.length > 0" id="partners" class="py-12 sm:py-16 overflow-hidden">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <div class="text-center space-y-1 reveal-up">
                        <div class="text-[11px] font-extrabold uppercase tracking-widest text-[#007468]">Kerjasama &amp; Kemitraan</div>
                        <h2 class="text-lg sm:text-xl font-extrabold text-slate-900">Didukung Berbagai Mitra Pendidikan &amp; Instansi</h2>
                    </div>

                    <div class="relative overflow-hidden py-3 group">
                        <div class="absolute left-0 top-0 bottom-0 w-12 sm:w-20 bg-gradient-to-r from-white to-transparent z-10 pointer-events-none"></div>
                        <div class="absolute right-0 top-0 bottom-0 w-12 sm:w-20 bg-gradient-to-l from-white to-transparent z-10 pointer-events-none"></div>

                        <div class="flex w-max partner-marquee group-hover:[animation-play-state:paused]">
                            <div v-for="loop in 2" :key="loop" class="flex items-center gap-10 sm:gap-14 pr-10 sm:pr-14 shrink-0" :aria-hidden="loop === 2">
                                <div
                                    v-for="item in uniquePartners"
                                    :key="`${loop}-${item.id}`"
                                    class="flex flex-col items-center justify-center opacity-75 hover:opacity-100 transition-opacity select-none"
                                >
                                    <img
                                        v-if="item.logo"
                                        :src="item.logo"
                                        :alt="item.name"
                                        loading="lazy"
                                        class="h-10 sm:h-12 w-auto object-contain max-w-[140px] grayscale hover:grayscale-0 transition-all"
                                    />
                                    <span class="text-[10px] font-bold text-slate-400 mt-1.5 whitespace-nowrap">{{ item.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 DUAL PATHWAY SECTION: SPMB + PORTAL KARIR
            ========================================================== -->
            <section class="py-10 sm:py-16 px-4 sm:px-6 lg:px-8">
                <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 reveal-up">
                    <!-- Pathway 1: Orang Tua / SPMB -->
                    <div class="rounded-3xl bg-[#EDF9F4] border border-[#D4EFE5] p-6 sm:p-8 flex flex-col justify-between gap-6">
                        <div class="space-y-2.5">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white text-[#007468] text-[11px] font-bold">
                                <SparklesIcon class="w-3.5 h-3.5" />
                                Untuk Orang Tua &amp; Wali
                            </span>
                            <h3 class="text-xl sm:text-2xl font-extrabold text-slate-950 tracking-tight">
                                Daftarkan Putra-Putri Anda di Namira
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Proses pendaftaran 100% online dengan pilihan Tahun Ajaran berjalan maupun Jalur Inden tahun ajaran mendatang.
                            </p>
                        </div>
                        <div>
                            <Link
                                :href="route('spmb.index')"
                                class="w-full sm:w-auto px-5 py-3 min-h-[46px] rounded-xl bg-[#009688] hover:bg-[#007D71] text-white text-xs sm:text-sm font-bold inline-flex items-center justify-center gap-2 transition-colors"
                            >
                                <span>Daftar SPMB Sekarang</span>
                                <ArrowRightIcon class="w-4 h-4" />
                            </Link>
                        </div>
                    </div>

                    <!-- Pathway 2: Pendidik & Profesional / Karir -->
                    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-[0_10px_30px_-18px_rgba(15,23,42,0.08)] p-6 sm:p-8 flex flex-col justify-between gap-6">
                        <div class="space-y-2.5">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#F2FAF7] text-[#007468] text-[11px] font-bold">
                                <BriefcaseIcon class="w-3.5 h-3.5" />
                                <span>{{ openVacanciesCount > 0 ? `${openVacanciesCount} Posisi Sedang Dibuka` : 'Portal Karir Yayasan' }}</span>
                            </span>
                            <h3 class="text-xl sm:text-2xl font-extrabold text-slate-950 tracking-tight">
                                Berkarya Bersama Keluarga Besar Namira
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Kami mengundang guru, pembina tahfidz, dan staf profesional untuk bertumbuh bersama lebih dari 10 unit pendidikan Namira.
                            </p>
                        </div>
                        <div>
                            <Link
                                :href="route('careers.index')"
                                class="w-full sm:w-auto px-5 py-3 min-h-[46px] rounded-xl border border-slate-200 hover:border-[#009688] hover:text-[#008C7E] text-slate-800 text-xs sm:text-sm font-bold inline-flex items-center justify-center gap-2 transition-colors"
                            >
                                <span>Lihat Portal Karir</span>
                                <ArrowRightIcon class="w-4 h-4" />
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- =========================================================
             UNIFIED AIRY WHITE EDITORIAL FOOTER (100% Bahasa Indonesia)
        ========================================================== -->
        <footer id="footer" class="bg-white border-t border-slate-100 py-12 sm:py-14">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8 sm:gap-10 pb-8 sm:pb-10 border-b border-slate-100">
                    <!-- Brand & Address -->
                    <div class="md:col-span-4 space-y-3.5">
                        <div class="flex items-center gap-2.5">
                            <img :src="appLogo" alt="Logo Yayasan Namira" class="w-9 h-9 object-contain" />
                            <div class="flex items-baseline gap-1">
                                <span class="font-namira text-xl text-[#1a4373] lowercase leading-none">namira</span>
                                <span class="font-school text-xs text-slate-500 uppercase font-extrabold leading-none">SCHOOL</span>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Lembaga Pendidikan Islam Terpadu &amp; Modern di bawah naungan Yayasan Namira — melayani Daycare, KB, TK, SD, dan SMP di Probolinggo, Jawa Timur.
                        </p>
                        <p class="text-xs text-slate-500">
                            Jl. Pelita No. 8, Mayangan, Kota Probolinggo &amp; Kraksaan, Jawa Timur
                        </p>
                    </div>

                    <!-- Links (2-col on mobile!) -->
                    <div class="grid grid-cols-2 md:contents gap-6">
                        <div class="md:col-span-2 space-y-2.5">
                            <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Tautan</h4>
                            <ul class="space-y-2 text-xs text-slate-500">
                                <li><Link :href="route('spmb.index')" class="font-semibold text-[#008C7E] hover:underline">SPMB Online</Link></li>
                                <li><Link :href="route('careers.index')" class="hover:text-[#008C7E] transition-colors">Portal Karir</Link></li>
                                <li><Link :href="route('news.index')" class="hover:text-[#008C7E] transition-colors">Berita &amp; Artikel</Link></li>
                                <li><Link :href="route('events.index')" class="hover:text-[#008C7E] transition-colors">Agenda Sekolah</Link></li>
                                <li><Link :href="route('testimonials.index')" class="hover:text-[#008C7E] transition-colors">Testimoni</Link></li>
                            </ul>
                        </div>

                        <div class="md:col-span-3 space-y-2.5">
                            <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Jam Layanan Kantor</h4>
                            <ul class="space-y-1.5 text-xs text-slate-500">
                                <li>Senin – Jumat: 07.30 – 15.00 WIB</li>
                                <li>Sabtu: 07.30 – 12.00 WIB</li>
                                <li class="text-slate-400">Minggu &amp; Hari Libur: Tutup</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Contact -->
                    <div class="md:col-span-3 space-y-2.5">
                        <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Hubungi Kami</h4>
                        <ul class="space-y-2 text-xs text-slate-500">
                            <li>
                                WhatsApp:
                                <a href="https://wa.me/6282332922521" target="_blank" rel="noopener noreferrer" class="font-semibold text-slate-800 hover:text-[#008C7E] ml-1">
                                    +62 823-3292-2521
                                </a>
                            </li>
                            <li>
                                Email Umum:
                                <a href="mailto:info@namiraschool.com" class="font-semibold text-slate-800 hover:text-[#008C7E] ml-1">
                                    info@namiraschool.com
                                </a>
                            </li>
                            <li>
                                Rekrutmen:
                                <a href="mailto:hr@namiraschool.com" class="font-semibold text-slate-800 hover:text-[#008C7E] ml-1">
                                    hr@namiraschool.com
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px] sm:text-xs text-slate-400 text-center sm:text-left">
                    <span>© {{ new Date().getFullYear() }} Yayasan Sekolah Islam Namira. Seluruh hak cipta dilindungi.</span>
                    <span>Kraksaan · Dringu · Kota Probolinggo</span>
                </div>
            </div>
        </footer>
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

@keyframes partnerMarquee {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}

.partner-marquee {
    animation: partnerMarquee 32s linear infinite;
}

:deep(.namira-map-popup .leaflet-popup-content-wrapper) {
    border-radius: 14px;
    border-left: 3px solid #009688;
    box-shadow: 0 10px 26px rgba(15, 23, 42, 0.14);
}
</style>
