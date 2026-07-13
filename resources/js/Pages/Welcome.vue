<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, watch, nextTick, computed } from 'vue';
import { 
    Bars3Icon, 
    XMarkIcon, 
    ArrowLeftIcon,
    ArrowRightIcon,
    ClockIcon,
    MapPinIcon,
    AcademicCapIcon,
    ChevronDownIcon
} from '@heroicons/vue/24/outline';

// Import AOS
import AOS from 'aos';
import 'aos/dist/aos.css';

// Import Leaflet
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Mobile menu state
const isMobileMenuOpen = ref(false);

const props = defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    latestNews: {
        type: Array,
        default: () => []
    },
    upcomingEvents: {
        type: Array,
        default: () => []
    },
    partners: {
        type: Array,
        default: () => []
    },
    destinations: {
        type: Array,
        default: () => []
    },
    testimonials: {
        type: Array,
        default: () => []
    },
    bannersList: {
        type: Array,
        default: () => []
    }
});

const page = usePage();
const appSettings = page.props.app_settings || {};
const appName = appSettings.app_name || 'Yayasan Namira School';
const appLogo = appSettings.app_logo || '/images/landing/logo-yayasan.webp'; 

// Scroll effect for Navbar & Scroll Spy
const isScrolled = ref(false);
const activeHash = ref('#home');
const showCampusesDropdown = ref(false);
const handleScroll = () => {
    isScrolled.value = window.scrollY > 50;

    const sections = ['home', 'campuses', 'destinations', 'testimonials', 'news', 'events', 'partners', 'footer'];
    for (const section of sections) {
        const el = document.getElementById(section);
        if (el) {
            const rect = el.getBoundingClientRect();
            if (rect.top <= 200 && rect.bottom >= 200) {
                activeHash.value = '#' + section;
                break;
            }
        }
    }
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    
    // Initialize AOS
    AOS.init({
        duration: 1000,
        once: true, // whether animation should happen only once - while scrolling down
        offset: 100, // offset (in px) from the original trigger point
    });

    // Observer for Stats Counting Animation
    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) {
            startCounting();
            observer.disconnect();
        }
    }, { threshold: 0.3 });
    
    if (statsSection.value) {
        observer.observe(statsSection.value);
    }
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

// Banner Slider
const bannersCollection = computed(() => {
    if (props.bannersList && props.bannersList.length > 0) {
        return props.bannersList.map(b => '/' + b.image_path);
    }
    // Static Fallback
    return [
        '/images/landing/banner-1.webp',
        '/images/landing/banner-2.webp',
        '/images/landing/banner-3.webp'
    ];
});
const currentBanner = ref(0);
let bannerInterval = null;

const startBannerInterval = () => {
    clearInterval(bannerInterval);
    bannerProgress.value = 0;
    bannerInterval = setInterval(() => {
        nextBanner();
        bannerProgress.value = 0;
    }, 6000);

    // Animate progress bar
    let start = Date.now();
    const DURATION = 6000;
    const tick = () => {
        if (!bannerInterval) return;
        bannerProgress.value = Math.min(((Date.now() - start) / DURATION) * 100, 100);
        if (bannerProgress.value < 100) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
};

const nextBanner = () => {
    currentBanner.value = (currentBanner.value + 1) % bannersCollection.value.length;
};

const prevBanner = () => {
    currentBanner.value = (currentBanner.value - 1 + bannersCollection.value.length) % bannersCollection.value.length;
};

const manualChangeBanner = (direction) => {
    clearInterval(bannerInterval);
    bannerInterval = null;
    if (direction === 'next') nextBanner();
    else prevBanner();
    startBannerInterval();
};

const goToBanner = (index) => {
    clearInterval(bannerInterval);
    bannerInterval = null;
    currentBanner.value = index;
    startBannerInterval();
};

// Banner progress
const bannerProgress = ref(0);

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    startBannerInterval();
    
    // Initialize AOS
    AOS.init({
        duration: 1000,
        once: true,
        offset: 100,
    });

    // Observer for Stats Counting Animation
    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) {
            startCounting();
            observer.disconnect();
        }
    }, { threshold: 0.3 });
    
    if (statsSection.value) {
        observer.observe(statsSection.value);
    }

    // Init Leaflet Map after DOM ready
    setTimeout(() => { initMap(); }, 300);
});

onUnmounted(() => {
    clearInterval(bannerInterval);
    window.removeEventListener('scroll', handleScroll);
    if (leafletMap) { leafletMap.remove(); leafletMap = null; }
});

// Campuses Data
const campuses = [
    { name: 'Daycare Namira', logo: '/images/landing/logo-daycare.webp', photo: '/images/landing/banner-1.webp', address: 'Area Daycare Namira' },
    { name: 'Pavlov Center', logo: '/images/landing/logo-pavlov.webp', photo: '/images/landing/banner-2.webp', address: 'Gedung Pavlov Center' },
    { name: 'TK - KB Namira', logo: '/images/landing/logo-tk.webp', logo2: '/images/landing/logo-kb.webp', photo: '/images/landing/banner-3.webp', address: 'Area TK & PAUD' },
    { name: 'SD Namira', logo: '/images/landing/logo-sd.webp', photo: '/images/landing/banner-1.webp', address: 'Gedung SD Namira' },
    {
        name: 'SMP Namira',
        logo: '/images/landing/logo-smp.webp',
        photo: '/images/landing/photo-smp.webp',
        address: 'Jl. KH. Ahmad Dahlan, Probolinggo',
        website: 'https://www.smpnamiraprob.sch.id/',
        wa: 'https://wa.me/6285336778880',
        email: 'mailto:namirasmp@gmail.com',
        facebook: 'https://www.facebook.com/namirajuniorhighschool',
        instagram: 'https://www.instagram.com/namirajuniorhighschool',
        youtube: 'https://www.youtube.com/@smpnamiraprobolinggo9462',
    },
    { name: 'Rumah Tahfitz', logo: '/images/landing/logo-tahfidz.webp', photo: '/images/landing/banner-3.webp', address: 'Asrama Tahfitz Quran' },
];

// Stats Data
const stats = [
    { label: 'Tahun Berdiri', value: 15, suffix: '', duration: 2000 },
    { label: 'Alumni Sukses', value: 5700, suffix: '+', duration: 2000 },
    { label: 'Mitra Kerjasama', value: 75, suffix: '+', duration: 2000 },
    { label: 'Tenaga Pendidik', value: 150, suffix: '+', duration: 2000 },
    { label: 'Siswa Aktif', value: 3900, suffix: '+', duration: 2000 },
];

const displayedStats = ref([0, 0, 0, 0, 0]);
const statsSection = ref(null);
let hasCounted = false;

const startCounting = () => {
    if (hasCounted) return;
    hasCounted = true;
    
    stats.forEach((stat, index) => {
        const target = stat.value;
        const duration = stat.duration;
        const start = performance.now();
        
        const updateCount = (currentTime) => {
            const elapsed = currentTime - start;
            const progress = Math.min(elapsed / duration, 1);
            
            // easeOutQuad
            const easeOut = progress * (2 - progress);
            
            displayedStats.value[index] = Math.floor(easeOut * target);
            
            if (progress < 1) {
                requestAnimationFrame(updateCount);
            } else {
                displayedStats.value[index] = target;
            }
        };
        
        requestAnimationFrame(updateCount);
    });
};
// Static data replaced by dynamic props
const formatDateStr = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const getEventDateParts = (dateString) => {
    if (!dateString) return { day: '', month: '' };
    const date = new Date(dateString);
    return {
        day: date.toLocaleDateString('id-ID', { day: '2-digit' }),
        month: date.toLocaleDateString('id-ID', { month: 'short' })
    };
};

// Map Toggle
const mapView = ref('indonesia');
const mapContainer = ref(null);
let leafletMap = null;
let markersLayer = null;

// University data from DB
const universitiesByType = computed(() => {
    const grouped = { indonesia: [], overseas: [], lokal: [] };
    props.destinations.forEach(d => {
        if (d.lat && d.lng) {
            grouped[d.type]?.push({
                name: d.name,
                city: d.city + (d.country !== 'Indonesia' ? ', ' + d.country : ''),
                lat: parseFloat(d.lat),
                lng: parseFloat(d.lng),
                typeLabel: d.type === 'overseas' ? 'Internasional' : (d.type === 'lokal' ? 'Lokal' : 'Nasional'),
                unit: d.unit?.name || '',
                visit_type: d.visit_type,
                visit_type_label: d.visit_type === 'alumni' ? 'Alumni' : 'Kunjungan',
                visit_date: d.visit_date,
            });
        }
    });
    return grouped;
});

const initMap = () => {
    if (!mapContainer.value) return;
    
    const isMobile = window.innerWidth < 768;
    
    const centerConfig = {
        overseas: { center: [20, 30], zoom: isMobile ? 1 : 2 },
        indonesia: { center: [-2.5, 118], zoom: isMobile ? 4 : 5 },
    };
    
    if (leafletMap) {
        leafletMap.remove();
        leafletMap = null;
    }

    const config = centerConfig[mapView.value];
    leafletMap = L.map(mapContainer.value, {
        center: config.center,
        zoom: config.zoom,
        zoomControl: false,
        scrollWheelZoom: false,
        attributionControl: false,
    });

    // Custom dark-styled tile
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '© CartoDB',
        maxZoom: 19,
    }).addTo(leafletMap);

    // Custom yellow pulsing marker icon
    const makeIcon = () => L.divIcon({
        html: `<div style="
            width:14px;height:14px;
            background:#fbbf24;
            border:2px solid #064e3b;
            border-radius:50%;
            box-shadow:0 0 0 4px rgba(251,191,36,0.3), 0 0 12px rgba(251,191,36,0.6);
            animation: markerPulse 2s infinite;
        "></div>`,
        className: '',
        iconSize: [14, 14],
        iconAnchor: [7, 7],
    });
    
    // Add markers
    markersLayer = L.layerGroup().addTo(leafletMap);
    const list = universitiesByType.value[mapView.value] || [];
    list.forEach(univ => {
        const marker = L.marker([univ.lat, univ.lng], { icon: makeIcon() })
            .bindPopup(`
                <div style="font-family:sans-serif;min-width:200px;">
                    <div style="font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:2px;">${univ.city}</div>
                    <div style="font-size:13px;font-weight:700;color:#064e3b;margin-bottom:6px;">${univ.name}</div>
                    <div style="display:flex;gap:4px;flex-wrap:wrap;">
                        <span style="font-size:10px;background:#fbbf24;color:#064e3b;padding:2px 7px;border-radius:4px;font-weight:600;">${univ.visit_type_label}</span>
                        <span style="font-size:10px;background:#e0f2fe;color:#0369a1;padding:2px 7px;border-radius:4px;font-weight:600;">${univ.typeLabel}</span>
                    </div>
                    <div style="font-size:10px;color:#9ca3af;margin-top:5px;">${univ.unit}</div>
                </div>
            `, { className: 'namira-popup' })
            .addTo(markersLayer);
    });

    // Add zoom control at bottom-right
    L.control.zoom({ position: 'bottomright' }).addTo(leafletMap);
};

watch([mapView, () => props.destinations], async () => {
    await nextTick();
    initMap();
});

// Testimonial Data
const testimonialsList = computed(() => {
    if (props.testimonials && props.testimonials.length > 0) {
        return props.testimonials.map(t => ({
            name: t.name,
            quote: t.quote,
            photo: t.photo_path ? '/' + t.photo_path : '/images/landing/banner-1.jpg',
            role: t.role_or_title || '',
            unit: t.unit?.name || '',
        }));
    }
    // Static Fallback
    return [
        { name: 'Ibu Ratna', quote: "Kami bangga menyekolahkan anak kami di sini. Sistem pendidikannya sangat terpadu dan guru-gurunya profesional.", photo: '/images/landing/banner-1.webp', role: 'Wali Murid', unit: 'SD Namira' },
        { name: 'Bapak Budi', quote: "Karakter islami anak saya terbentuk sangat baik sejak masuk Namira. Fasilitasnya juga sangat mendukung.", photo: '/images/landing/banner-2.webp', role: 'Wali Murid', unit: 'SMP Namira' },
        { name: 'Ananda', quote: "Lulusan Namira sangat berdaya saing. Bekal agama dan ilmu umum yang seimbang sangat bermanfaat.", photo: '/images/landing/banner-3.webp', role: 'Alumni', unit: 'SMA Namira' }
    ];
});
const testimonialsCount = computed(() => testimonialsList.value.length);
const currentTesti = ref(0);
const nextTesti = () => {
    currentTesti.value = (currentTesti.value + 1) % testimonialsList.value.length;
};
const prevTesti = () => {
    currentTesti.value = (currentTesti.value - 1 + testimonialsList.value.length) % testimonialsList.value.length;
};

// Colors
// Dark Green: #064e3b (Tailwind: emerald-900 / namira-green)
// Yellow: #fbbf24 (Tailwind: amber-400 / namira-yellow)
</script>

<template>
    <Head :title="appName" />
    
    <div class="min-h-screen bg-[#f8f9fa] font-sans text-gray-900 selection:bg-[#fbbf24] selection:text-[#064e3b]">
        <!-- Header & Navigation -->
        <header :class="['fixed top-0 w-full z-50 transition-all duration-500', isScrolled ? 'bg-white shadow-lg py-3 border-b border-slate-100' : 'bg-transparent py-6']">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <!-- Logo Section -->
                    <div class="flex items-center gap-4">
                        <div class="bg-white p-1.5 rounded-full shadow-lg border border-slate-100">
                            <img :src="appLogo" alt="Logo" class="object-contain transition-all duration-300" :class="isScrolled ? 'h-9 w-9 md:h-11 md:w-11' : 'h-11 w-11 md:h-13 md:w-13'" />
                        </div>
                        <div class="flex flex-col">
                            <span class="font-extrabold text-base md:text-lg tracking-[0.05em] uppercase leading-none transition-colors duration-300" :class="isScrolled ? 'text-[#082a3a]' : 'text-white drop-shadow-md'">Yayasan Namira</span>
                            <span class="font-medium text-[10px] tracking-[0.1em] mt-1.5 leading-none transition-colors duration-300" :class="isScrolled ? 'text-[#00A99D]' : 'text-[#fbbf24] drop-shadow'">Fostering Islamic Generation</span>
                        </div>
                    </div>

                    <!-- Desktop Navigation Links -->
                    <nav class="hidden lg:flex items-center space-x-6 xl:space-x-8">
                        <a href="#home" @click="activeHash = '#home'" class="relative py-2 text-xs uppercase tracking-widest transition-all duration-300 group/nav" :class="[activeHash === '#home' ? 'text-[#fbbf24] font-semibold border-b border-[#fbbf24]' : (isScrolled ? 'text-slate-800 hover:text-[#fbbf24] font-medium' : 'text-white/90 hover:text-white font-medium')]">
                            Home
                            <span v-if="activeHash !== '#home'" class="absolute bottom-0 left-0 w-0 h-[1.5px] bg-[#fbbf24] transition-all duration-300 group-hover/nav:w-full"></span>
                        </a>
                        
                        <!-- Our Campuses with Dropdown -->
                        <div class="relative group" @mouseenter="showCampusesDropdown = true" @mouseleave="showCampusesDropdown = false">
                            <a href="#campuses" @click="activeHash = '#campuses'" class="relative py-4 text-xs uppercase tracking-widest transition-all duration-300 flex items-center gap-1.5 group/nav" :class="[activeHash === '#campuses' ? 'text-[#fbbf24] font-semibold border-b border-[#fbbf24]' : (isScrolled ? 'text-slate-800 hover:text-[#fbbf24] font-medium' : 'text-white/90 hover:text-white font-medium')]">
                                Campuses
                                <ChevronDownIcon class="w-3.5 h-3.5 transition-transform duration-300" :class="{'rotate-180': showCampusesDropdown}" />
                                <span v-if="activeHash !== '#campuses'" class="absolute bottom-0 left-0 w-0 h-[1.5px] bg-[#fbbf24] transition-all duration-300 group-hover/nav:w-full"></span>
                            </a>
                            
                            <!-- Dropdown Menu -->
                            <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-1">
                                <div v-show="showCampusesDropdown" class="absolute left-0 w-64 bg-white rounded-xl shadow-2xl py-3 mt-0 border border-slate-100 overflow-hidden z-50">
                                    <div class="px-4 py-2 border-b border-slate-50 mb-1">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Daftar Unit Sekolah</p>
                                    </div>
                                    <a v-for="(unit, i) in campuses" :key="i" :href="'#campus-' + i" class="flex items-center gap-3 px-4 py-3 hover:bg-[#f8f9fa] transition-colors group/item">
                                        <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center p-1 group-hover/item:bg-[#fbbf24]/10 transition-colors">
                                            <img :src="unit.logo" :alt="unit.name" class="w-full h-full object-contain" />
                                        </div>
                                        <span class="text-xs font-semibold text-[#082a3a] group-hover/item:translate-x-1 transition-transform">{{ unit.name }}</span>
                                    </a>
                                </div>
                            </transition>
                        </div>
                        <a href="#destinations" @click="activeHash = '#destinations'" class="relative py-2 text-xs uppercase tracking-widest transition-all duration-300 group/nav" :class="[activeHash === '#destinations' ? 'text-[#fbbf24] font-semibold border-b border-[#fbbf24]' : (isScrolled ? 'text-slate-800 hover:text-[#fbbf24] font-medium' : 'text-white/90 hover:text-white font-medium')]">
                            Destinations
                            <span v-if="activeHash !== '#destinations'" class="absolute bottom-0 left-0 w-0 h-[1.5px] bg-[#fbbf24] transition-all duration-300 group-hover/nav:w-full"></span>
                        </a>
                        <a href="#testimonials" @click="activeHash = '#testimonials'" class="relative py-2 text-xs uppercase tracking-widest transition-all duration-300 group/nav" :class="[activeHash === '#testimonials' ? 'text-[#fbbf24] font-semibold border-b border-[#fbbf24]' : (isScrolled ? 'text-slate-800 hover:text-[#fbbf24] font-medium' : 'text-white/90 hover:text-white font-medium')]">
                            Testimonials
                            <span v-if="activeHash !== '#testimonials'" class="absolute bottom-0 left-0 w-0 h-[1.5px] bg-[#fbbf24] transition-all duration-300 group-hover/nav:w-full"></span>
                        </a>
                        <a href="#news" @click="activeHash = '#news'" class="relative py-2 text-xs uppercase tracking-widest transition-all duration-300 group/nav" :class="[activeHash === '#news' ? 'text-[#fbbf24] font-semibold border-b border-[#fbbf24]' : (isScrolled ? 'text-slate-800 hover:text-[#fbbf24] font-medium' : 'text-white/90 hover:text-white font-medium')]">
                            News
                            <span v-if="activeHash !== '#news'" class="absolute bottom-0 left-0 w-0 h-[1.5px] bg-[#fbbf24] transition-all duration-300 group-hover/nav:w-full"></span>
                        </a>
                        <a href="#events" @click="activeHash = '#events'" class="relative py-2 text-xs uppercase tracking-widest transition-all duration-300 group/nav" :class="[activeHash === '#events' ? 'text-[#fbbf24] font-semibold border-b border-[#fbbf24]' : (isScrolled ? 'text-slate-800 hover:text-[#fbbf24] font-medium' : 'text-white/90 hover:text-white font-medium')]">
                            Events
                            <span v-if="activeHash !== '#events'" class="absolute bottom-0 left-0 w-0 h-[1.5px] bg-[#fbbf24] transition-all duration-300 group-hover/nav:w-full"></span>
                        </a>
                        <a href="#partners" @click="activeHash = '#partners'" class="relative py-2 text-xs uppercase tracking-widest transition-all duration-300 group/nav" :class="[activeHash === '#partners' ? 'text-[#fbbf24] font-semibold border-b border-[#fbbf24]' : (isScrolled ? 'text-slate-800 hover:text-[#fbbf24] font-medium' : 'text-white/90 hover:text-white font-medium')]">
                            Partners
                            <span v-if="activeHash !== '#partners'" class="absolute bottom-0 left-0 w-0 h-[1.5px] bg-[#fbbf24] transition-all duration-300 group-hover/nav:w-full"></span>
                        </a>
                        <a href="#footer" @click="activeHash = '#footer'" class="relative py-2 text-xs uppercase tracking-widest transition-all duration-300 group/nav" :class="[activeHash === '#footer' ? 'text-[#fbbf24] font-semibold border-b border-[#fbbf24]' : (isScrolled ? 'text-slate-800 hover:text-[#fbbf24] font-medium' : 'text-white/90 hover:text-white font-medium')]">
                            Contact
                            <span v-if="activeHash !== '#footer'" class="absolute bottom-0 left-0 w-0 h-[1.5px] bg-[#fbbf24] transition-all duration-300 group-hover/nav:w-full"></span>
                        </a>
                        
                        <div class="flex items-center gap-4 ml-6">
                            <template v-if="canLogin">
                                <Link v-if="$page.props.auth.user" :href="route('dashboard')" @click="activeHash = '#dashboard'" :class="isScrolled ? 'bg-[#00A99D]/10 border border-[#00A99D]/30 hover:bg-[#00A99D] hover:border-[#00A99D] text-[#00A99D] hover:text-white font-semibold text-xs tracking-widest uppercase px-6 py-2.5 rounded-full transition-all duration-300 shadow-sm active:scale-95 text-center' : 'bg-[#fbbf24]/10 border border-[#fbbf24]/30 hover:bg-[#fbbf24] hover:border-[#fbbf24] text-[#fbbf24] hover:text-[#082a3a] font-semibold text-xs tracking-widest uppercase px-6 py-2.5 rounded-full transition-all duration-300 active:scale-95 text-center'">
                                    Dashboard
                                </Link>
                                <template v-else>
                                    <Link :href="route('login')" :class="isScrolled ? 'bg-[#082a3a] hover:bg-[#00A99D] text-white font-semibold text-xs tracking-widest uppercase px-6 py-2.5 rounded-full transition-all duration-300 shadow-sm active:scale-95 text-center' : 'bg-[#fbbf24] hover:bg-yellow-400 text-[#082a3a] font-semibold text-xs tracking-widest uppercase px-8 py-3 rounded-full transition-all duration-300 shadow-lg shadow-yellow-500/10 hover:shadow-yellow-500/20 active:scale-95 text-center'">
                                        Login Portal
                                    </Link>
                                </template>
                            </template>
                        </div>
                    </nav>

                    <!-- Mobile Menu Hamburger Button -->
                    <button @click="isMobileMenuOpen = true" class="lg:hidden p-2 rounded-xl transition-colors" :class="isScrolled ? 'text-slate-800 hover:bg-slate-100' : 'text-white hover:bg-white/10'">
                        <Bars3Icon class="w-6.5 h-6.5" />
                    </button>
                </div>
            </div>
        </header>

        <!-- Mobile Fullscreen Drawer -->
        <transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 translate-x-full" enter-to-class="opacity-100 translate-x-0" leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100 translate-x-0" leave-to-class="opacity-0 translate-x-full">
            <div v-show="isMobileMenuOpen" class="fixed inset-0 z-[100] bg-[#082a3a] flex flex-col justify-between p-8 text-white">
                <!-- Header inside Drawer -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="bg-white p-1.5 rounded-full shadow-lg">
                            <img :src="appLogo" alt="Logo" class="h-8 w-8 object-contain" />
                        </div>
                        <div>
                            <span class="text-white font-bold text-sm tracking-widest uppercase leading-none block">Yayasan Namira</span>
                            <span class="text-[#fbbf24] font-medium text-[9px] tracking-wider uppercase block mt-0.5">Fostering Islamic Generation</span>
                        </div>
                    </div>
                    <button @click="isMobileMenuOpen = false" class="p-2 rounded-full bg-white/10 hover:bg-white/20 transition-colors">
                        <XMarkIcon class="w-6.5 h-6.5 text-white" />
                    </button>
                </div>

                <!-- Menu Links in Drawer -->
                <nav class="flex flex-col gap-6 my-auto text-left pl-4">
                    <a href="#home" @click="isMobileMenuOpen = false" class="text-2xl font-bold tracking-widest uppercase hover:text-[#fbbf24] transition-colors">Home</a>
                    <a href="#campuses" @click="isMobileMenuOpen = false" class="text-2xl font-bold tracking-widest uppercase hover:text-[#fbbf24] transition-colors">Campuses</a>
                    <a href="#destinations" @click="isMobileMenuOpen = false" class="text-2xl font-bold tracking-widest uppercase hover:text-[#fbbf24] transition-colors">Destinations</a>
                    <a href="#testimonials" @click="isMobileMenuOpen = false" class="text-2xl font-bold tracking-widest uppercase hover:text-[#fbbf24] transition-colors">Testimonials</a>
                    <a href="#news" @click="isMobileMenuOpen = false" class="text-2xl font-bold tracking-widest uppercase hover:text-[#fbbf24] transition-colors">News</a>
                    <a href="#events" @click="isMobileMenuOpen = false" class="text-2xl font-bold tracking-widest uppercase hover:text-[#fbbf24] transition-colors">Events</a>
                    <a href="#partners" @click="isMobileMenuOpen = false" class="text-2xl font-bold tracking-widest uppercase hover:text-[#fbbf24] transition-colors">Partners</a>
                    <a href="#footer" @click="isMobileMenuOpen = false" class="text-2xl font-bold tracking-widest uppercase hover:text-[#fbbf24] transition-colors">Contact</a>
                </nav>

                <!-- Footer/CTA in Drawer -->
                <div class="border-t border-white/10 pt-6">
                    <template v-if="canLogin">
                        <Link v-if="$page.props.auth.user" :href="route('dashboard')" @click="isMobileMenuOpen = false" class="block w-full py-4 text-center bg-[#00A99D] hover:bg-teal-700 text-white font-extrabold text-xs tracking-widest uppercase rounded-full transition-all">
                            Dashboard
                        </Link>
                        <Link v-else :href="route('login')" @click="isMobileMenuOpen = false" class="block w-full py-4 text-center bg-[#fbbf24] hover:bg-yellow-300 text-[#082a3a] font-extrabold text-xs tracking-widest uppercase rounded-full transition-all shadow-lg">
                            Login Portal
                        </Link>
                    </template>
                    <p class="text-center text-[10px] text-white/40 uppercase tracking-widest mt-6">© {{ new Date().getFullYear() }} Yayasan Namira</p>
                </div>
            </div>
        </transition>

        <!-- Hero Section -->
        <section id="home" class="relative h-screen overflow-hidden bg-[#082a3a]">
            <!-- Slider Background -->
            <div class="absolute inset-0 z-0">
                <div v-for="(img, index) in bannersCollection" :key="index"
                     class="absolute inset-0 transition-opacity duration-1000 ease-in-out"
                     :class="currentBanner === index ? 'opacity-100 z-10' : 'opacity-0 z-0'">
                    <img :src="img" alt="Banner" class="w-full h-full object-cover object-center" :loading="index === 0 ? 'eager' : 'lazy'" />
                </div>
                <!-- Directional gradient overlay: dark left → transparent right -->
                <div class="absolute inset-0 z-20" style="background: linear-gradient(to right, rgba(8,42,58,0.80) 0%, rgba(8,42,58,0.52) 42%, rgba(8,42,58,0.10) 100%);"></div>
            </div>

            <!-- Content: full height wrapper so items-center truly centers on visible area -->
            <div class="relative z-30 h-full flex items-center">
                <div class="w-full px-8 sm:px-16 lg:px-28 max-w-[1400px] mx-auto" style="padding-top: 80px;">
                    <div class="w-full lg:w-[46%]">

                        <!-- Eyebrow -->
                        <p class="text-[#fbbf24] font-semibold text-[10px] md:text-xs tracking-[0.28em] uppercase mb-6 animate-fade-in-up" style="animation-delay:0.10s;">
                            Membentuk Pemimpin Muslim Masa Depan
                        </p>

                        <!-- Headline — 2 lines, editorial, restrained weight -->
                        <h1 class="animate-fade-in-up font-semibold tracking-tight text-white mb-10"
                            style="animation-delay:0.20s; font-size: clamp(2rem, 4.2vw, 3.6rem); line-height: 1.17; max-width: 580px;">
                            Membina Generasi Islami,<br/>
                            <span class="text-[#fbbf24]">Siap Memimpin Masa Depan</span>
                        </h1>

                        <!-- Description -->
                        <p class="animate-fade-in-up text-white/70 font-normal leading-[1.75] mb-12"
                           style="animation-delay:0.30s; font-size: clamp(0.9rem, 1.2vw, 1.05rem); max-width: 500px;">
                            Mendidik dengan keunggulan akademik, integritas moral, dan kepemimpinan global yang berlandaskan nilai-nilai islami.
                        </p>

                        <!-- CTA Buttons -->
                        <div class="flex flex-wrap items-center gap-5 animate-fade-in-up" style="animation-delay:0.40s;">
                            <a href="#campuses"
                               class="inline-flex items-center px-8 py-[14px] bg-[#fbbf24] hover:bg-yellow-400 text-[#082a3a] font-bold text-[10px] tracking-[0.2em] uppercase rounded-full transition-all duration-300 active:scale-95 shadow-md shadow-yellow-500/10 hover:shadow-yellow-500/20">
                                Jelajahi Namira
                            </a>
                            <a href="#footer"
                               class="inline-flex items-center px-8 py-[14px] bg-transparent border border-white/25 hover:border-white/55 hover:bg-white/6 text-white font-bold text-[10px] tracking-[0.2em] uppercase rounded-full transition-all duration-300 active:scale-95">
                                Pendaftaran
                            </a>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Slider Indicators — bottom right, very subtle -->
            <div class="absolute bottom-10 right-8 sm:right-16 lg:right-28 z-30 flex items-center gap-7 animate-fade-in-up" style="animation-delay:0.50s;">
                <button
                    v-for="(_, i) in bannersCollection" :key="i"
                    @click="goToBanner(i)"
                    class="flex items-center gap-2.5 group cursor-pointer"
                >
                    <span class="text-[11px] font-semibold tabular-nums transition-all duration-400"
                          :class="currentBanner === i ? 'text-white' : 'text-white/35 group-hover:text-white/65'">
                        0{{ i + 1 }}
                    </span>
                    <div class="h-px rounded-full overflow-hidden transition-all duration-500"
                         :class="currentBanner === i ? 'w-28 bg-white/25' : 'w-16 bg-white/10'">
                        <div v-if="currentBanner === i"
                             class="h-full bg-[#fbbf24] rounded-full"
                             :style="{ width: bannerProgress + '%', transition: 'none' }"></div>
                    </div>
                </button>
            </div>

        </section>

        <!-- Our Campuses / Unit Sekolah -->
        <section id="campuses" class="w-full bg-white">
            <div class="w-full flex flex-col md:flex-row items-stretch justify-center m-0 p-0">
                <!-- Default State & Hover State Integration -->
                <div v-for="(unit, index) in campuses" :key="index" :id="'campus-' + index" class="group relative flex-1 h-72 md:h-[350px] bg-gray-100 cursor-pointer overflow-hidden border-r border-white/50 transition-all duration-500" data-aos="fade-up" :data-aos-delay="index * 100">
                    
                    <!-- Default View (Logo & Text) -->
                    <div class="absolute inset-0 flex flex-col items-center justify-center p-4 lg:p-6 bg-[#f0f2f5] transition-opacity duration-300 group-hover:opacity-0 z-10">
                        <!-- Single Logo -->
                        <img v-if="!unit.logo2" :src="unit.logo" :alt="unit.name" class="h-16 lg:h-20 w-auto object-contain mb-4 drop-shadow" loading="lazy" />
                        <!-- Dual Logo side by side -->
                        <div v-else class="flex items-center justify-center gap-2 mb-4">
                            <img :src="unit.logo" :alt="unit.name" class="h-14 lg:h-16 w-auto object-contain drop-shadow" loading="lazy" />
                            <div class="w-[1px] h-12 bg-gray-300 mx-1"></div>
                            <img :src="unit.logo2" :alt="unit.name" class="h-14 lg:h-16 w-auto object-contain drop-shadow" loading="lazy" />
                        </div>
                        <h3 class="text-[11px] lg:text-[13px] font-bold text-[#064e3b] text-center tracking-widest uppercase">{{ unit.name }}</h3>
                        <div class="w-6 h-[2px] bg-[#fbbf24] mt-3"></div>
                    </div>

                    <!-- Hover View (Photo Overlay & Socials) -->
                    <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-all duration-500 z-20">
                        <img :src="unit.photo" :alt="unit.name" class="w-full h-full object-cover transform scale-105 group-hover:scale-100 transition-transform duration-700" loading="lazy" />
                        <!-- Dark Gradient Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-[#064e3b]/70 to-[#064e3b]/30 mix-blend-multiply"></div>
                        
                        <!-- Content Structure -->
                        <div class="absolute inset-0 flex flex-col justify-end p-5 pb-6 transform translate-y-4 group-hover:translate-y-0 transition-transform duration-500">
                            <h3 class="text-sm lg:text-base font-bold text-white tracking-widest uppercase mb-1">{{ unit.name }}</h3>
                            <div class="w-full h-[1px] bg-[#fbbf24] mb-2 opacity-70"></div>
                            <p class="text-gray-300 text-[9px] lg:text-[10px] leading-tight mb-3">{{ unit.address }}</p>
                            
                            <!-- Social/Contact Icons: data-driven, clickable -->
                            <div class="flex flex-wrap gap-1.5">
                                <!-- Facebook -->
                                <a v-if="unit.facebook" :href="unit.facebook" target="_blank" rel="noopener" title="Facebook" class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center hover:bg-[#fbbf24] hover:text-[#064e3b] text-white transition">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/></svg>
                                </a>
                                <!-- Instagram -->
                                <a v-if="unit.instagram" :href="unit.instagram" target="_blank" rel="noopener" title="Instagram" class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center hover:bg-[#fbbf24] hover:text-[#064e3b] text-white transition">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                </a>
                                <!-- WhatsApp -->
                                <a v-if="unit.wa" :href="unit.wa" target="_blank" rel="noopener" title="WhatsApp" class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center hover:bg-[#fbbf24] hover:text-[#064e3b] text-white transition">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                </a>
                                <!-- YouTube -->
                                <a v-if="unit.youtube" :href="unit.youtube" target="_blank" rel="noopener" title="YouTube" class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center hover:bg-[#fbbf24] hover:text-[#064e3b] text-white transition">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M23.495 6.205a3.007 3.007 0 0 0-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 0 0 .527 6.205a31.247 31.247 0 0 0-.522 5.805 31.247 31.247 0 0 0 .522 5.783 3.007 3.007 0 0 0 2.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 0 0 2.088-2.088 31.247 31.247 0 0 0 .5-5.783 31.247 31.247 0 0 0-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/></svg>
                                </a>
                                <!-- Email -->
                                <a v-if="unit.email" :href="unit.email" title="Email" class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center hover:bg-[#fbbf24] hover:text-[#064e3b] text-white transition">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                </a>
                                <!-- Website -->
                                <a v-if="unit.website" :href="unit.website" target="_blank" rel="noopener" title="Website" class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center hover:bg-[#fbbf24] hover:text-[#064e3b] text-white transition">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Growing Community Stats -->
        <section class="bg-[#f8f9fa] py-16 border-b border-gray-200" ref="statsSection">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10" data-aos="fade-down">
                    <h2 class="text-2xl font-bold text-[#064e3b] uppercase tracking-widest">Growing NAMIRA Community</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6 px-4">
                    <div v-for="(stat, index) in stats" :key="index" class="group w-full h-[250px] [perspective:1000px] cursor-pointer" data-aos="zoom-in" :data-aos-delay="index * 100">
                        <div class="relative w-full h-full transition-all duration-700 [transform-style:preserve-3d] group-hover:[transform:rotateY(180deg)] shadow-lg rounded-2xl">
                            
                            <!-- Front Face (Stats) -->
                            <div class="absolute inset-0 w-full h-full [backface-visibility:hidden] bg-[#fbbf24] border border-yellow-300 rounded-2xl p-8 flex flex-col justify-center items-center">
                                <div class="w-10 h-[2px] bg-[#064e3b] mx-auto mb-6 opacity-50"></div>
                                <p class="text-4xl lg:text-5xl font-light text-[#064e3b] mb-4">{{ displayedStats[index].toLocaleString('id-ID') }}{{ stat.suffix }}</p>
                                <p class="text-[10px] lg:text-[11px] font-semibold text-[#064e3b] uppercase tracking-[0.15em] text-center">{{ stat.label }}</p>
                            </div>

                            <!-- Back Face (Learn More) -->
                            <div class="absolute inset-0 w-full h-full [backface-visibility:hidden] [transform:rotateY(180deg)] bg-[#064e3b] rounded-2xl p-8 flex flex-col justify-center items-center">
                                <!-- Decorative shapes -->
                                <div class="absolute top-4 right-4 w-6 h-6 border-2 border-white/10 rotate-45"></div>
                                <div class="absolute bottom-6 left-6 w-8 h-8 border-2 border-white/10 rotate-12"></div>
                                
                                <a href="#campuses" class="border border-white text-white hover:bg-white hover:text-[#064e3b] font-bold text-xs tracking-widest uppercase px-6 py-3 rounded transition duration-300 z-10 text-center">
                                    LEARN MORE
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Destination Map Section -->
        <section id="destinations" class="py-20 bg-[#f8f9fa] border-t border-gray-200">
            <div class="max-w-[1400px] mx-auto px-4">
                <div class="text-center mb-10" data-aos="fade-down">
                    <h2 class="text-3xl font-bold text-[#064e3b] uppercase tracking-wider mb-6">University Destination</h2>
                    
                    <!-- Toggle Buttons -->
                    <div class="inline-flex bg-gray-200 rounded-full p-1 shadow-inner">
                        <button @click="mapView = 'overseas'" :class="['px-6 py-2 rounded-full text-xs font-bold tracking-widest uppercase transition', mapView === 'overseas' ? 'bg-[#fbbf24] text-[#064e3b] shadow' : 'text-gray-500 hover:text-gray-700']">🌍 Overseas</button>
                        <button @click="mapView = 'indonesia'" :class="['px-6 py-2 rounded-full text-xs font-bold tracking-widest uppercase transition', mapView === 'indonesia' ? 'bg-[#fbbf24] text-[#064e3b] shadow' : 'text-gray-500 hover:text-gray-700']">🇮🇩 Indonesia</button>
                    </div>
                </div>

                <!-- Map Layout -->
                <div class="mt-8 max-w-5xl mx-auto relative group" data-aos="fade-up">
                    
                    <!-- Decorative Airplane & Spiral Path (Top Left) -->
                    <div class="absolute -top-12 -left-20 w-48 h-48 opacity-20 pointer-events-none hidden lg:block rotate-[-15deg]">
                        <svg viewBox="0 0 200 200" class="w-full h-full text-[#fbbf24] fill-none">
                            <path d="M20,150 Q60,50 150,80 T180,150" stroke="currentColor" stroke-width="2" stroke-dasharray="8,8" />
                            <g class="animate-bounce-slow">
                                <path d="M175,145 L185,150 L175,155 Z" fill="currentColor" />
                                <path d="M165,145 L175,150 L165,155 Z" fill="currentColor" />
                            </g>
                        </svg>
                        <div class="absolute top-20 left-32 rotate-[45deg] text-[#fbbf24]">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M21,16.5C21,16.88 20.79,17.21 20.47,17.38L12.57,21.82C12.41,21.94 12.21,22 12,22C11.79,22 11.59,21.94 11.43,21.82L3.53,17.38C3.21,17.21 3,16.88 3,16.5V7.5C3,7.12 3.21,6.79 3.53,6.62L11.43,2.18C11.59,2.06 11.79,2 12,2C12.21,2 12.41,2.06 12.57,2.18L20.47,6.62C20.79,6.79 21,7.12 21,7.5V16.5Z"/></svg>
                        </div>
                    </div>

                    <!-- Decorative Airplane & Spiral Path (Bottom Right) -->
                    <div class="absolute -bottom-16 -right-24 w-64 h-64 opacity-20 pointer-events-none hidden lg:block rotate-[165deg]">
                        <svg viewBox="0 0 200 200" class="w-full h-full text-[#fbbf24] fill-none">
                            <path d="M30,30 C80,30 50,150 170,170" stroke="currentColor" stroke-width="2" stroke-dasharray="10,5" />
                            <path d="M165,165 L175,170 L165,175 Z" fill="currentColor" />
                        </svg>
                    </div>

                    <!-- Floating Paper Plane Icons -->
                    <div class="absolute top-10 -right-16 text-[#fbbf24] opacity-30 animate-pulse hidden xl:block rotate-[15deg]">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10l19-7-7 19-3-9-9-3z"></path></svg>
                    </div>

                    <!-- Leaflet Map -->
                    <div class="relative rounded-2xl overflow-hidden shadow-2xl border border-gray-200 z-10">
                        <div ref="mapContainer" style="height:500px;width:100%;"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- News Section -->
        <section id="news" class="py-20 bg-white border-t border-gray-100">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12" data-aos="fade-down">
                    <h2 class="text-2xl font-bold text-[#064e3b] uppercase tracking-widest mb-4">News</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <article v-for="(news, index) in latestNews" :key="news.id" class="bg-[#064e3b] rounded-[1.5rem] overflow-hidden shadow-2xl group cursor-pointer flex flex-col h-full transition-all duration-300 hover:-translate-y-2" data-aos="fade-up" :data-aos-delay="index * 100">
                        <!-- Image Container -->
                        <div class="relative h-44 overflow-hidden shrink-0">
                            <img :src="news.image_path ? '/' + news.image_path : '/images/landing/banner-1.jpg'" :alt="news.title" class="w-full h-full object-cover transform group-hover:scale-110 transition duration-700" loading="lazy" />
                        </div>
                        
                        <!-- Content -->
                        <div class="p-5 flex-grow flex flex-col">
                            <!-- Date -->
                            <div class="text-white/60 text-[11px] font-medium mb-2 uppercase tracking-widest">
                                {{ formatDateStr(news.published_at) }}
                            </div>
                            
                            <!-- Title -->
                            <h3 class="text-[15px] font-bold text-white mb-3 line-clamp-2 leading-snug group-hover:text-[#fbbf24] transition-colors">
                                {{ news.title }}
                            </h3>
                            
                            <!-- Excerpt -->
                            <p class="text-white/70 text-[12px] line-clamp-2 mb-4 leading-relaxed font-light">
                                {{ news.excerpt || news.content?.replace(/<[^>]*>/g, '').substring(0, 90) + '...' }}
                            </p>
                            
                            <!-- Link -->
                            <Link :href="news.slug ? route('news.show', news.slug) : '#'" class="text-[#fbbf24] font-bold text-[11px] uppercase tracking-[0.2em] flex items-center mt-auto hover:brightness-125 transition-all">
                                READ MORE >
                            </Link>
                        </div>
                    </article>
                </div>
                
                <div class="text-center mt-10" data-aos="fade-up">
                    <Link :href="route('news.index')" class="text-[#fbbf24] font-bold text-sm tracking-widest uppercase cursor-pointer hover:underline">View All News</Link>
                </div>
            </div>
        </section>

        <!-- What Others Say (Testimonials) -->
        <section id="testimonials" class="py-24 bg-[#f8f9fa] border-t border-gray-200 overflow-hidden">
            <div class="max-w-4xl mx-auto px-4 text-center">
                <h2 class="text-2xl font-bold text-[#064e3b] uppercase tracking-widest mb-16" data-aos="fade-down">What Others Say</h2>
                
                <!-- Circular Photos Slider -->
                <div class="flex items-center justify-center mb-12 relative max-w-4xl mx-auto" data-aos="zoom-in">
                    <button v-if="testimonialsCount > 1" @click="prevTesti" class="absolute left-0 lg:left-8 w-12 h-12 rounded-full bg-[#fbbf24] text-[#064e3b] flex items-center justify-center shadow-md hover:bg-yellow-300 transition z-30">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    
                    <div class="relative w-full h-56 md:h-80 flex items-center justify-center overflow-hidden">
                        <div v-for="(testi, i) in testimonialsList" :key="i"
                             class="absolute rounded-full transition-all duration-700 ease-[cubic-bezier(0.25,1,0.5,1)]"
                             :class="{
                                 'w-40 h-40 md:w-64 md:h-64 border-2 border-[#064e3b] bg-white p-1.5 shadow-2xl z-20 scale-100 opacity-100 translate-x-0': i === currentTesti && testimonialsCount >= 3,
                                 'w-40 h-40 md:w-48 md:h-48 border-2 border-[#064e3b] bg-white p-1 z-10 opacity-100 scale-50 md:scale-75 -translate-x-[110%] md:-translate-x-[160%]': i === (currentTesti - 1 + testimonialsCount) % testimonialsCount && testimonialsCount >= 3,
                                 'w-40 h-40 md:w-48 md:h-48 border-2 border-[#064e3b] bg-white p-1 z-10 opacity-100 scale-50 md:scale-75 translate-x-[110%] md:translate-x-[160%]': i === (currentTesti + 1) % testimonialsCount && testimonialsCount >= 3,
                                 'w-40 h-40 md:w-48 md:h-48 opacity-0 scale-0 z-0': i !== currentTesti && i !== (currentTesti - 1 + testimonialsCount) % testimonialsCount && i !== (currentTesti + 1) % testimonialsCount && testimonialsCount >= 3,
                                 
                                 // Simple layout if 1 item
                                 'w-40 h-40 md:w-64 md:h-64 border-2 border-[#064e3b] bg-white p-1.5 shadow-2xl z-20 scale-100 opacity-100 translate-x-0': testimonialsCount === 1,
                                 
                                 // Simple layout if 2 items
                                 'w-40 h-40 md:w-64 md:h-64 border-2 border-[#064e3b] bg-white p-1.5 shadow-2xl z-20 scale-100 opacity-100 translate-x-0': testimonialsCount === 2 && i === currentTesti,
                                 'w-40 h-40 md:w-48 md:h-48 border-2 border-[#064e3b] bg-white p-1 z-10 opacity-50 scale-75 translate-x-[110%] md:translate-x-[160%]': testimonialsCount === 2 && i !== currentTesti
                             }">
                            <img :src="testi.photo" class="w-full h-full object-cover rounded-full" loading="lazy" />
                        </div>
                    </div>
                    
                    <button v-if="testimonialsCount > 1" @click="nextTesti" class="absolute right-0 lg:right-8 w-12 h-12 rounded-full bg-[#fbbf24] text-[#064e3b] flex items-center justify-center shadow-md hover:bg-yellow-300 transition z-30">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>

                <!-- Quote Text with Transition -->
                <div class="px-8 min-h-[180px] flex flex-col justify-start items-center">
                    <transition name="testi-fade" mode="out-in">
                        <div :key="currentTesti" class="w-full">
                            <h4 class="text-2xl font-bold text-[#064e3b] mb-1">{{ testimonialsList[currentTesti].name }}</h4>
                            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-4">
                                {{ testimonialsList[currentTesti].role }} <span v-if="testimonialsList[currentTesti].role && testimonialsList[currentTesti].unit">•</span> {{ testimonialsList[currentTesti].unit }}
                            </p>
                            <p class="text-gray-600 font-light text-lg md:text-xl leading-relaxed mb-6">
                                "{{ testimonialsList[currentTesti].quote }}"
                            </p>
                            <Link :href="route('testimonials.index')" class="text-[#064e3b] font-medium text-sm hover:text-[#fbbf24] transition-colors border-b border-[#064e3b] hover:border-[#fbbf24] pb-1">
                                Read More
                            </Link>
                        </div>
                    </transition>
                </div>
                <div class="w-12 h-[1px] bg-[#fbbf24] mx-auto mt-8"></div>
            </div>
        </section>

        <!-- Events Section -->
        <section id="events" class="py-20 bg-white border-t border-gray-100">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12" data-aos="fade-down">
                    <h2 class="text-2xl font-bold text-[#064e3b] uppercase tracking-widest mb-4">Events</h2>
                </div>

                <!-- Events Grid -->
                <div v-if="upcomingEvents.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <Link v-for="(event, index) in upcomingEvents" :key="event.id" :href="event.slug ? route('events.show', event.slug) : '#'" class="bg-transparent group cursor-pointer relative mt-8 flex flex-col h-full transition-all duration-300 hover:-translate-y-2" data-aos="fade-up" :data-aos-delay="index * 100">
                        
                        <!-- Circular Date Badge (Overlapping) -->
                        <div class="absolute -top-6 left-6 w-16 h-16 bg-white rounded-full flex flex-col items-center justify-center shadow-lg border-2 border-[#064e3b] z-20">
                            <span class="text-xl font-bold text-[#064e3b] leading-none">{{ getEventDateParts(event.start_date).day }}</span>
                            <span class="text-[10px] font-bold text-[#fbbf24] uppercase">{{ getEventDateParts(event.start_date).month }}</span>
                        </div>

                        <div class="h-44 overflow-hidden rounded-t-lg z-10 shadow-sm relative">
                            <img :src="event.image_path ? '/' + event.image_path : '/images/landing/banner-2.jpg'" :alt="event.title" class="w-full h-full object-cover transform group-hover:scale-110 transition duration-700" loading="lazy" />
                        </div>
                        
                        <!-- Footer Card Green -->
                        <div class="bg-[#064e3b] p-5 text-white rounded-b-lg shadow-md flex-grow flex flex-col justify-between z-10 relative">
                            <div>
                                <h3 class="text-[15px] font-bold mb-3 line-clamp-2 group-hover:text-[#fbbf24] transition-colors leading-snug">
                                    {{ event.title }}
                                </h3>
                                <div class="flex items-center text-[10px] text-gray-300 uppercase tracking-wider mb-2">
                                    <svg class="w-3 h-3 mr-2 text-[#fbbf24]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path></svg>
                                    {{ formatDateStr(event.start_date) }}
                                </div>
                                <div class="flex items-center text-[10px] text-gray-300 uppercase tracking-wider">
                                    <svg class="w-3 h-3 mr-2 text-[#fbbf24]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                                    {{ event.location || 'Kampus Namira' }}
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-white/20">
                                <span class="text-[#fbbf24] font-bold text-[11px] uppercase tracking-widest group-hover:underline flex items-center transition-colors">
                                    Read More &rarr;
                                </span>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Empty State Fallback -->
                <div v-else class="max-w-2xl mx-auto bg-emerald-50/40 border border-emerald-100/50 rounded-3xl p-10 text-center shadow-sm" data-aos="zoom-in">
                    <div class="mx-auto w-14 h-14 bg-[#064e3b]/5 rounded-2xl flex items-center justify-center mb-5 text-[#064e3b]/80">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z"></path>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-[#064e3b] mb-2 font-heading tracking-wide">Belum Ada Agenda Terdekat</h3>
                    <p class="text-xs text-slate-500 leading-relaxed max-w-md mx-auto">
                        Saat ini belum ada jadwal kegiatan atau acara terdaftar. Tetap pantau halaman ini untuk mendapatkan informasi agenda menarik dari Namira School!
                    </p>
                </div>

                <div v-if="upcomingEvents.length > 0" class="text-center mt-12" data-aos="fade-up">
                    <Link :href="route('events.index')" class="text-[#fbbf24] font-bold text-sm tracking-widest uppercase cursor-pointer hover:underline">View All Events</Link>
                </div>
            </div>
        </section>

        <!-- Partners / MOU Section -->
        <section id="partners" class="py-16 bg-[#f8f9fa] border-t border-b border-gray-200/50 overflow-hidden">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10" data-aos="fade-down">
                    <h2 class="text-2xl font-bold text-[#064e3b] uppercase tracking-widest mb-2 font-heading">Kerjasama & Kemitraan</h2>
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Lembaga & Instansi Partner Namira School</p>
                </div>

                <!-- Infinite Scroll Logo Ticker -->
                <div class="relative w-full flex items-center overflow-hidden py-4">
                    <!-- Faded Edges -->
                    <div class="absolute left-0 top-0 bottom-0 w-24 bg-gradient-to-r from-[#f8f9fa] to-transparent z-10 pointer-events-none"></div>
                    <div class="absolute right-0 top-0 bottom-0 w-24 bg-gradient-to-l from-[#f8f9fa] to-transparent z-10 pointer-events-none"></div>

                    <!-- Marquee Container -->
                    <div class="w-full flex overflow-hidden">
                        <!-- Ticker Row 1 -->
                        <div class="flex gap-16 items-center shrink-0 min-w-full justify-around animate-infinite-scroll">
                            <div v-for="item in partners" :key="'p1-' + item.id" class="flex flex-col items-center justify-center grayscale opacity-60 hover:grayscale-0 hover:opacity-100 transition-all duration-300 select-none">
                                <img :src="'/' + item.logo_path" :alt="item.name" class="h-10 md:h-12 w-auto object-contain max-w-[160px]" loading="lazy" />
                                <span class="text-[9px] text-gray-400 font-bold mt-2 tracking-wider uppercase">{{ item.name }}</span>
                            </div>
                            <!-- Fallback placeholders if empty -->
                            <div v-if="partners.length === 0" v-for="n in 6" :key="'f1-' + n" class="flex flex-col items-center justify-center grayscale opacity-30 select-none">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.905 0-5.64-.813-7.843-2.228m15.686 0a8.959 8.959 0 01-5.698 2.228m0 0A8.959 8.959 0 015.217 8.272"></path>
                                </svg>
                                <span class="text-[9px] text-gray-400 font-bold mt-1.5 uppercase tracking-wider">Mitra Pendidikan {{ n }}</span>
                            </div>
                        </div>

                        <!-- Ticker Row 2 (Duplicate) -->
                        <div class="flex gap-16 items-center shrink-0 min-w-full justify-around animate-infinite-scroll" aria-hidden="true">
                            <div v-for="item in partners" :key="'p2-' + item.id" class="flex flex-col items-center justify-center grayscale opacity-60 hover:grayscale-0 hover:opacity-100 transition-all duration-300 select-none">
                                <img :src="'/' + item.logo_path" :alt="item.name" class="h-10 md:h-12 w-auto object-contain max-w-[160px]" loading="lazy" />
                                <span class="text-[9px] text-gray-400 font-bold mt-2 tracking-wider uppercase">{{ item.name }}</span>
                            </div>
                            <div v-if="partners.length === 0" v-for="n in 6" :key="'f2-' + n" class="flex flex-col items-center justify-center grayscale opacity-30 select-none">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.905 0-5.64-.813-7.843-2.228m15.686 0a8.959 8.959 0 01-5.698 2.228m0 0A8.959 8.959 0 015.217 8.272"></path>
                                </svg>
                                <span class="text-[9px] text-gray-400 font-bold mt-1.5 uppercase tracking-wider">Mitra Pendidikan {{ n }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pre-Footer Banner -->
        <section class="relative py-24 bg-[#064e3b] overflow-hidden">
            <div class="absolute inset-0 opacity-20">
                <img :src="bannersCollection[0]" class="w-full h-full object-cover mix-blend-overlay" loading="lazy" />
            </div>
            <div class="relative z-10 max-w-4xl mx-auto px-4 text-center" data-aos="fade-up">
                <span class="text-white text-6xl font-serif leading-none opacity-50">&ldquo;</span>
                <h2 class="text-2xl md:text-4xl font-bold text-white leading-tight italic px-8">
                    Fostering and Empowering Society in Building and Serving the Nation
                </h2>
                <div class="mt-10">
                    <a href="#" class="bg-[#fbbf24] text-[#064e3b] font-bold text-sm tracking-widest uppercase px-10 py-4 rounded-full transition shadow-lg hover:bg-yellow-300">
                        Join Us Now
                    </a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer id="footer" class="bg-[#2b2b2b] text-gray-300 pt-16 pb-8 text-sm border-t-8 border-[#064e3b]">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                    
                    <!-- Branding -->
                    <div class="md:col-span-1">
                        <div class="flex flex-col gap-1 mb-6">
                            <span class="text-white font-bold text-lg tracking-widest uppercase">{{ appName }}</span>
                            <span class="text-[#fbbf24] text-xs uppercase tracking-widest">Education Group</span>
                        </div>
                        <ul class="space-y-3 text-[12px] text-gray-400">
                            <li>{{ appSettings.contact_phone || '0804 1500 501' }}</li>
                            <li>{{ appSettings.contact_email || 'info.school@namira.edu' }}</li>
                            <li class="mt-4">{{ appSettings.address || 'Headquarters Namira, Indonesia' }}</li>
                        </ul>
                    </div>

                    <div class="md:col-span-1">
                        <h4 class="text-white font-bold mb-6 tracking-widest uppercase text-xs">Quick Links</h4>
                        <ul class="space-y-2 text-[12px]">
                            <li><a href="#home" class="hover:text-[#fbbf24] transition">Home</a></li>
                            <li><a href="#campuses" class="hover:text-[#fbbf24] transition">Campuses</a></li>
                            <li><a href="#destinations" class="hover:text-[#fbbf24] transition">Destinations</a></li>
                            <li><a href="#testimonials" class="hover:text-[#fbbf24] transition">Testimonials</a></li>
                            <li><a href="#news" class="hover:text-[#fbbf24] transition">News</a></li>
                            <li><a href="#events" class="hover:text-[#fbbf24] transition">Events</a></li>
                            <li><a href="#partners" class="hover:text-[#fbbf24] transition">Partners</a></li>
                        </ul>
                    </div>
                    
                    <div class="md:col-span-1">
                        <h4 class="text-white font-bold mb-6 tracking-widest uppercase text-xs">Operational Hours</h4>
                        <ul class="space-y-2 text-[12px]">
                            <li>Mon-Fri: 09:00 - 17:00</li>
                            <li>Sat: 09:00 - 15:00</li>
                            <li class="text-gray-500">Sun & National Holiday: Closed</li>
                        </ul>
                    </div>

                    <div class="md:col-span-1 flex items-start justify-end">
                        <img :src="appLogo" alt="QR/Logo" class="h-24 w-24 object-contain bg-white p-2 rounded" loading="lazy" />
                    </div>
                </div>

                <div class="border-t border-gray-700 pt-8 flex flex-col md:flex-row justify-between items-center text-[10px] text-gray-500 uppercase tracking-wider">
                    <p>Copyright &copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Thin elegant scrollbar */
::-webkit-scrollbar {
    width: 8px;
}
::-webkit-scrollbar-track {
    background: #f1f1f1; 
}
::-webkit-scrollbar-thumb {
    background: #064e3b; 
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #043528; 
}

/* Testimonial Transition */
.testi-fade-enter-active,
.testi-fade-leave-active {
  transition: opacity 0.5s ease, transform 0.5s ease;
}
.testi-fade-enter-from,
.testi-fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

/* Leaflet marker pulse animation */
@keyframes markerPulse {
  0%, 100% { box-shadow: 0 0 0 4px rgba(251,191,36,0.3), 0 0 12px rgba(251,191,36,0.6); }
  50% { box-shadow: 0 0 0 8px rgba(251,191,36,0.1), 0 0 20px rgba(251,191,36,0.8); }
}

/* Custom Leaflet popup styling */
.namira-popup .leaflet-popup-content-wrapper {
  border-radius: 12px !important;
  border-left: 3px solid #fbbf24 !important;
  box-shadow: 0 8px 24px rgba(0,0,0,0.15) !important;
  padding: 0 !important;
}
.namira-popup .leaflet-popup-content {
  margin: 12px 16px !important;
}
.namira-popup .leaflet-popup-tip {
  background: white !important;
}

/* Infinite loop scroll animation */
@keyframes infiniteScroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-100%); }
}

.animate-infinite-scroll {
    animation: infiniteScroll 25s linear infinite;
}

/* Custom Fade In Up Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    opacity: 0;
    animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
