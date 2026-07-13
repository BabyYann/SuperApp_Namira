<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { 
    ArrowLeftIcon, 
    CalendarIcon, 
    MapPinIcon, 
    ClockIcon,
    UserIcon,
} from '@heroicons/vue/24/outline';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    event:   Object,
    related: Array,
});

// ---- Meta & SEO ----
const metaDescription = computed(() => {
    const text = props.event?.description?.replace(/<[^>]*>/g, '') || '';
    return text.length > 155 ? text.substring(0, 155).trim() + '...' : text.trim();
});

const absoluteImageUrl = computed(() => {
    if (!props.event?.image_path) return '';
    if (props.event.image_path.startsWith('http://') || props.event.image_path.startsWith('https://')) {
        return props.event.image_path;
    }
    const origin = typeof window !== 'undefined' ? window.location.origin : '';
    return `${origin}/${props.event.image_path.replace(/^\//, '')}`;
});

const pageUrl = computed(() => {
    return typeof window !== 'undefined' ? window.location.href : '';
});

const formatDateStr = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const formatTimeStr = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
};

const getEventDateParts = (dateString) => {
    if (!dateString) return { day: '', month: '', year: '' };
    const date = new Date(dateString);
    return {
        day: date.toLocaleDateString('id-ID', { day: '2-digit' }),
        month: date.toLocaleDateString('id-ID', { month: 'short' }),
    };
};

const getStatusBadge = (status) => {
    if (status === 'upcoming') {
        return { label: 'Akan Datang', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
    }
    if (status === 'ongoing') {
        return { label: 'Sedang Berlangsung', class: 'bg-amber-50 text-amber-700 border-amber-200' };
    }
    if (status === 'completed') {
        return { label: 'Selesai', class: 'bg-gray-100 text-gray-600 border-gray-200' };
    }
    return { label: 'Dibatalkan', class: 'bg-red-50 text-red-700 border-red-200' };
};

const getExcerpt = (htmlContent, limit = 100) => {
    if (!htmlContent) return '';
    const text = htmlContent.replace(/<[^>]*>/g, '');
    return text.length > limit ? text.substring(0, limit) + '...' : text;
};

// ---- Back Navigation ----
const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '/events';
    }
};

// ---- Live Countdown Timer ----
const countdown = ref({ days: '00', hours: '00', minutes: '00', seconds: '00' });
let timerInterval = null;

const startCountdown = () => {
    const targetDate = new Date(props.event.start_date).getTime();

    const updateTimer = () => {
        const now = new Date().getTime();
        const difference = targetDate - now;

        if (difference <= 0) {
            countdown.value = { days: '00', hours: '00', minutes: '00', seconds: '00' };
            clearInterval(timerInterval);
            return;
        }

        const d = Math.floor(difference / (1000 * 60 * 60 * 24));
        const h = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const m = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
        const s = Math.floor((difference % (1000 * 60)) / 1000);

        countdown.value = {
            days: String(d).padStart(2, '0'),
            hours: String(h).padStart(2, '0'),
            minutes: String(m).padStart(2, '0'),
            seconds: String(s).padStart(2, '0'),
        };
    };

    updateTimer();
    timerInterval = setInterval(updateTimer, 1000);
};

onMounted(() => {
    if (props.event.computed_status === 'upcoming') {
        startCountdown();
    }
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

// ---- Google Calendar Link Generator ----
const googleCalendarUrl = computed(() => {
    if (!props.event) return '';
    
    const formatCalendarDate = (dateString) => {
        const date = new Date(dateString);
        return date.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
    };

    const title = encodeURIComponent(props.event.title);
    const start = formatCalendarDate(props.event.start_date);
    const end = props.event.end_date 
        ? formatCalendarDate(props.event.end_date)
        : formatCalendarDate(new Date(new Date(props.event.start_date).getTime() + 2 * 60 * 60 * 1000)); // Default 2 hours
    
    const details = encodeURIComponent(props.event.description ? props.event.description.replace(/<[^>]*>/g, '') : '');
    const location = encodeURIComponent(props.event.location || 'Kampus Namira');

    return `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${start}/${end}&details=${details}&location=${location}`;
});

// ---- Google Maps Link Generator ----
const googleMapsUrl = computed(() => {
    if (!props.event?.location) return '';
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(props.event.location)}`;
});

// ---- WhatsApp Panitia Link ----
const getWhatsAppLink = (phone, title) => {
    if (!phone) return '';
    let cleaned = phone.replace(/[^0-9]/g, '');
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.substring(1);
    }
    const text = encodeURIComponent(`Halo, saya ingin bertanya mengenai acara "${title}" di Namira Event Center.`);
    return `https://wa.me/${cleaned}?text=${text}`;
};

// ---- Social Share Panel ----
const copied = ref(false);

const shareWhatsApp = () => {
    const url  = encodeURIComponent(window.location.href);
    const text = encodeURIComponent(props.event.title + ' — Namira Event Center\n');
    window.open(`https://wa.me/?text=${text}${url}`, '_blank');
};

const shareFacebook = () => {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`, '_blank');
};

const shareLinkedIn = () => {
    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(window.location.href)}`, '_blank');
};

const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(window.location.href);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2500);
    } catch (e) {
        const el = document.createElement('input');
        el.value = window.location.href;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2500);
    }
};

const eventSchemaJson = computed(() => {
    if (!props.event) return {};
    return {
        "@context": "https://schema.org",
        "@type": "Event",
        "name": props.event.title,
        "description": props.event.description ? props.event.description.replace(/<[^>]*>/g, '') : '',
        "startDate": props.event.start_date,
        "endDate": props.event.end_date || props.event.start_date,
        "eventStatus": props.event.computed_status === 'cancelled' ? 'https://schema.org/EventCancelled' : 'https://schema.org/EventScheduled',
        "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
        "location": {
            "@type": "Place",
            "name": props.event.location || 'Kampus Yayasan Namira',
            "address": {
                "@type": "PostalAddress",
                "streetAddress": props.event.location || 'Kampus Yayasan Namira',
                "addressLocality": "Medan",
                "addressRegion": "Sumatera Utara",
                "addressCountry": "ID"
            }
        },
        "image": absoluteImageUrl.value,
        "organizer": {
            "@type": "Organization",
            "name": props.event.unit?.name || 'Yayasan Namira School',
            "url": pageUrl.value
        },
        "url": pageUrl.value
    };
});

const handleImageError = (e) => {
    e.target.onerror = null;
    e.target.src = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='800' height='450' viewBox='0 0 800 450'><rect width='100%' height='100%' fill='%23064e3b'/><circle cx='400' cy='225' r='120' fill='%23fbbf24' opacity='0.1'/><text x='50%' y='50%' dominant-baseline='middle' text-anchor='middle' fill='%23ffffff' font-family='sans-serif' font-size='24' font-weight='900' letter-spacing='2'>NAMIRA EVENTS</text></svg>";
};
</script>

<template>
    <Head>
        <title>{{ event.title }} - Namira Event Center</title>
        <meta name="description" :content="metaDescription" head-key="description" />
        
        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="article" head-key="og:type" />
        <meta property="og:title" :content="event.title + ' - Namira Event Center'" head-key="og:title" />
        <meta property="og:description" :content="metaDescription" head-key="og:description" />
        <meta property="og:url" :content="pageUrl" head-key="og:url" />
        <meta v-if="absoluteImageUrl" property="og:image" :content="absoluteImageUrl" head-key="og:image" />
        <meta property="og:site_name" content="Namira Event Center" head-key="og:site_name" />
        
        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" head-key="twitter:card" />
        <meta name="twitter:title" :content="event.title + ' - Namira Event Center'" head-key="twitter:title" />
        <meta name="twitter:description" :content="metaDescription" head-key="twitter:description" />
        <meta v-if="absoluteImageUrl" name="twitter:image" :content="absoluteImageUrl" head-key="twitter:image" />

        <link rel="canonical" :href="pageUrl" />
        <component :is="'script'" type="application/ld+json" v-html="JSON.stringify(eventSchemaJson)"></component>
    </Head>
    
    <div class="min-h-screen bg-[#fafafb] font-sans text-gray-900 selection:bg-[#fbbf24] selection:text-[#064e3b]">
        <!-- Navbar -->
        <header class="bg-[#064e3b] py-4 shadow-sm sticky top-0 z-50 border-b border-[#053c2d]">
            <div class="max-w-[1200px] mx-auto px-6 flex justify-between items-center">
                <Link 
                    :href="route('events.index')"
                    class="text-white hover:text-[#fbbf24] transition flex items-center gap-2 text-xs font-bold uppercase tracking-widest cursor-pointer"
                    aria-label="Kembali ke halaman agenda acara"
                >
                    <ArrowLeftIcon class="w-4 h-4 stroke-[2.5]" /> Kembali
                </Link>
                <Link href="/events" class="text-white font-black text-xl tracking-wider">
                    NAMIRA<span class="text-[#fbbf24]">EVENTS</span>
                </Link>
                <Link href="/events" class="text-[#fbbf24] text-xs font-bold uppercase tracking-widest hidden md:block hover:text-white transition">
                    Agenda Center
                </Link>
            </div>
        </header>

        <main class="max-w-[1200px] mx-auto px-6 py-10">
            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-xs font-semibold text-gray-400 mb-6 flex-wrap select-none">
                <Link href="/" class="hover:text-[#064e3b] transition">Beranda</Link>
                <span>/</span>
                <Link href="/events" class="hover:text-[#064e3b] transition">Acara</Link>
                <span>/</span>
                <span class="hover:text-[#064e3b] transition">{{ event.unit?.name || 'Yayasan' }}</span>
                <span>/</span>
                <span class="text-[#064e3b] font-bold truncate max-w-[200px]">{{ event.title }}</span>
            </nav>

            <!-- Event Ticket-Style Header -->
            <header class="mb-8">
                <div class="flex flex-wrap items-center gap-2.5 mb-3">
                    <span class="bg-[#064e3b] text-[#fbbf24] text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-lg select-none">
                        {{ event.unit?.name || 'Yayasan Namira' }}
                    </span>
                    <span 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-black border bg-white select-none"
                        :class="getStatusBadge(event.computed_status).class"
                    >
                        <span class="w-1.5 h-1.5 rounded-full" :class="{
                            'bg-emerald-500': event.computed_status === 'upcoming',
                            'bg-amber-500': event.computed_status === 'ongoing',
                            'bg-gray-400': event.computed_status === 'completed',
                            'bg-red-500': event.computed_status === 'cancelled',
                        }"></span>
                        {{ getStatusBadge(event.computed_status).label }}
                    </span>
                </div>
                
                <h1 class="text-2xl md:text-4xl font-black text-gray-900 leading-tight mb-4 tracking-tight">
                    {{ event.title }}
                </h1>
                
                <!-- Countdown Live -->
                <div v-if="event.computed_status === 'upcoming'" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-amber-50 border border-amber-100 text-amber-800 text-xs font-extrabold select-none">
                    <ClockIcon class="w-4 h-4 text-amber-700 shrink-0" />
                    <span>Acara dimulai dalam:</span>
                    <span>{{ countdown.days }} Hari {{ countdown.hours }} Jam {{ countdown.minutes }} Menit {{ countdown.seconds }} Detik</span>
                </div>
            </header>

            <!-- Featured Banner Image -->
            <!-- Detail Banner: aspect-video (16:9) dipertahankan — sudah optimal -->
            <!-- width/height hint mencegah CLS sebelum gambar selesai dimuat -->
            <div class="w-full aspect-video rounded-3xl overflow-hidden shadow-sm border border-gray-100 mb-10 bg-gray-50">
                <img width="1200" height="675" :src="event.image_path ? '/' + event.image_path : '/images/landing/banner-2.jpg'" :alt="event.title" @error="handleImageError" class="w-full h-full object-cover" />
            </div>

            <!-- Two-Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
                <!-- Column Left (2/3): Content Deskripsi & Share Panel -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 md:p-8">
                        <h2 class="font-black text-lg text-[#064e3b] mb-4 uppercase tracking-tight border-b border-gray-100 pb-3 border-dashed">Deskripsi Acara</h2>
                        <article class="prose prose-lg max-w-none text-gray-700 leading-relaxed ql-editor px-0" v-html="event.description || 'Tidak ada rincian deskripsi untuk acara ini.'"></article>
                    </div>

                    <!-- Share Panel -->
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
                        <p class="text-xs font-black uppercase tracking-wider text-gray-500 mb-3 select-none">Bagikan Acara Ini</p>
                        <div class="flex flex-wrap gap-2.5">
                            <!-- WhatsApp -->
                            <button 
                                @click="shareWhatsApp"
                                class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white hover:opacity-95 transition cursor-pointer select-none focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                                style="background-color: #25D366;"
                                aria-label="Bagikan acara ini ke WhatsApp"
                            >
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                WhatsApp
                            </button>
                            <!-- Facebook -->
                            <button 
                                @click="shareFacebook"
                                class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white hover:opacity-95 transition cursor-pointer select-none focus:outline-none focus:ring-2 focus:ring-[#1877F2] focus:ring-offset-2"
                                style="background-color: #1877F2;"
                                aria-label="Bagikan acara ini ke Facebook"
                            >
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                Facebook
                            </button>
                            <!-- LinkedIn -->
                            <button 
                                @click="shareLinkedIn"
                                class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white hover:opacity-95 transition cursor-pointer select-none focus:outline-none focus:ring-2 focus:ring-[#0A66C2] focus:ring-offset-2"
                                style="background-color: #0A66C2;"
                                aria-label="Bagikan acara ini ke LinkedIn"
                            >
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.779-1.75-1.75s.784-1.75 1.75-1.75 1.75.779 1.75 1.75-.784 1.75-1.75 1.75zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                                LinkedIn
                            </button>
                            <!-- Copy Link -->
                            <button 
                                @click="copyLink"
                                class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold border transition cursor-pointer select-none focus:outline-none focus:ring-2 focus:ring-[#064e3b] focus:ring-offset-2"
                                :class="copied ? 'bg-emerald-50 text-[#064e3b] border-emerald-200' : 'bg-white text-gray-700 border-gray-200 hover:border-gray-300'"
                                aria-label="Salin tautan ke papan klip"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                                </svg>
                                {{ copied ? 'Tersalin!' : 'Salin Link' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Column Right (1/3): Side Info Panels -->
                <aside class="space-y-6">
                    <!-- Info Acara Panel -->
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
                        <h3 class="font-black text-sm text-[#064e3b] uppercase tracking-wider mb-4 border-b border-gray-50 pb-2 select-none">Detail Pelaksanaan</h3>
                        
                        <div class="space-y-4">
                            <!-- Tanggal Mulai -->
                            <div class="flex gap-3">
                                <CalendarIcon class="w-5 h-5 text-gray-500 shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase leading-none mb-1 select-none">Tanggal Pelaksanaan</p>
                                    <p class="text-xs font-bold text-gray-800">
                                        {{ formatDateStr(event.start_date) }}
                                        <span v-if="event.end_date && formatDateStr(event.start_date) !== formatDateStr(event.end_date)">
                                            s.d {{ formatDateStr(event.end_date) }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <!-- Waktu/Jam -->
                            <div class="flex gap-3">
                                <ClockIcon class="w-5 h-5 text-gray-500 shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase leading-none mb-1 select-none">Waktu Acara</p>
                                    <p class="text-xs font-bold text-gray-800">
                                        {{ formatTimeStr(event.start_date) }}
                                        <span v-if="event.end_date"> s.d {{ formatTimeStr(event.end_date) }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Lokasi -->
                            <div class="flex gap-3">
                                <MapPinIcon class="w-5 h-5 text-gray-500 shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase leading-none mb-1 select-none">Tempat / Lokasi</p>
                                    <p class="text-xs font-bold text-gray-800 mb-1">{{ event.location || 'Kampus Yayasan Namira' }}</p>
                                    <a 
                                        v-if="event.location"
                                        :href="googleMapsUrl"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-[11px] font-extrabold text-[#064e3b] hover:text-[#fbbf24] transition select-none cursor-pointer"
                                    >
                                        Lihat Rute di Maps &rarr;
                                    </a>
                                </div>
                            </div>

                            <!-- Penyelenggara -->
                            <div class="flex gap-3">
                                <UserIcon class="w-5 h-5 text-gray-500 shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase leading-none mb-1 select-none">Penyelenggara</p>
                                    <p class="text-xs font-bold text-gray-800">{{ event.unit?.name || 'Yayasan Namira School' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CTA Panel -->
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 space-y-3">
                        <h3 class="font-black text-sm text-[#064e3b] uppercase tracking-wider mb-2 border-b border-gray-50 pb-2 select-none">Pendaftaran & Narahubung</h3>
                        
                        <!-- CTA: Daftar Sekarang -->
                        <a 
                            v-if="event.registration_link && event.computed_status !== 'completed'"
                            :href="event.registration_link" 
                            target="_blank"
                            class="block w-full bg-[#064e3b] hover:bg-[#053c2d] text-white font-black text-xs uppercase tracking-widest text-center py-3.5 rounded-xl transition shadow-sm cursor-pointer select-none"
                        >
                            Daftar Sekarang
                        </a>
                        <button 
                            v-else 
                            disabled
                            class="w-full bg-gray-100 text-gray-500 font-black text-xs uppercase tracking-widest py-3.5 rounded-xl cursor-not-allowed border border-gray-200/50 select-none"
                        >
                            {{ event.computed_status === 'completed' ? 'Pendaftaran Selesai' : 'Pendaftaran Belum Tersedia' }}
                        </button>

                        <!-- CTA: Hubungi Panitia (WA Direct) -->
                        <a 
                            v-if="event.contact_person"
                            :href="getWhatsAppLink(event.contact_person, event.title)" 
                            target="_blank"
                            class="block w-full bg-[#fbbf24] hover:bg-yellow-400 text-[#064e3b] font-black text-xs uppercase tracking-widest text-center py-3.5 rounded-xl transition shadow-sm cursor-pointer select-none"
                        >
                            Hubungi Panitia (WA)
                        </a>
                        <button 
                            v-else 
                            disabled
                            class="w-full bg-gray-50 text-gray-400 font-bold text-xs uppercase tracking-widest py-3.5 rounded-xl cursor-not-allowed border border-gray-100 select-none"
                        >
                            Kontak Tidak Tersedia
                        </button>

                        <!-- CTA: Google Calendar Export -->
                        <a 
                            :href="googleCalendarUrl"
                            target="_blank"
                            class="block w-full bg-white text-gray-700 border border-gray-200 hover:border-gray-300 font-bold text-xs uppercase tracking-widest text-center py-3.5 rounded-xl transition cursor-pointer select-none"
                        >
                            + Google Calendar
                        </a>
                    </div>
                </aside>
            </div>

            <!-- Related Events Grid -->
            <section v-if="related && related.length > 0" class="border-t border-gray-100 pt-10">
                <div class="border-b-2 border-gray-900 pb-2 mb-6">
                    <h3 class="font-black text-lg text-gray-900 uppercase tracking-tight">Agenda Lainnya</h3>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    <article 
                        v-for="item in related" 
                        :key="item.id" 
                        class="bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-md transition duration-300 flex flex-col group relative"
                    >
                        <!-- Top Image Banner -->
                        <!-- aspect-[16/9]: konsisten dengan Grid Card di EventIndex -->
                        <div class="aspect-[16/9] w-full overflow-hidden relative shrink-0">
                            <img 
                                :src="item.image_path ? '/' + item.image_path : '/images/landing/banner-2.jpg'" 
                                :alt="item.title" 
                                @error="handleImageError"
                                loading="lazy"
                                width="800"
                                height="450"
                                class="w-full h-full object-cover transform group-hover:scale-[1.03] transition duration-700 ease-out" 
                            />
                            
                            <!-- Calendar Sheet -->
                            <div class="absolute top-3 left-3 bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden flex flex-col items-center justify-center w-12 h-14 select-none z-10">
                                <span class="bg-[#064e3b] text-white text-[11px] font-black uppercase tracking-wider w-full text-center py-0.5 leading-none">
                                    {{ getEventDateParts(item.start_date).month }}
                                </span>
                                <span class="text-gray-900 text-lg font-black leading-none py-1">
                                    {{ getEventDateParts(item.start_date).day }}
                                </span>
                            </div>

                            <div class="absolute top-3 right-3 z-10">
                                <span 
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[9.5px] font-bold border bg-white/95 backdrop-blur-sm shadow-sm select-none"
                                    :class="getStatusBadge(item.computed_status).class"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="{
                                        'bg-emerald-500': item.computed_status === 'upcoming',
                                        'bg-amber-500': item.computed_status === 'ongoing',
                                        'bg-gray-400': item.computed_status === 'completed',
                                        'bg-red-500': item.computed_status === 'cancelled',
                                    }"></span>
                                    {{ getStatusBadge(item.computed_status).label }}
                                </span>
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="p-4 flex-grow flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="bg-emerald-50 text-[#064e3b] text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded border border-emerald-100 select-none">
                                        {{ item.unit?.name || 'Yayasan' }}
                                    </span>
                                </div>
                                
                                <Link :href="route('events.show', item.slug)">
                                    <h4 class="text-xs font-extrabold text-gray-900 mb-2 line-clamp-2 leading-snug group-hover:text-[#064e3b] transition duration-200">
                                        {{ item.title }}
                                    </h4>
                                </Link>
                                
                                <p class="text-xs text-gray-500 font-bold mb-3 select-none">{{ formatDateStr(item.start_date) }}</p>
                            </div>

                            <div class="pt-3 border-t border-gray-50">
                                <Link 
                                    :href="route('events.show', item.slug)"
                                    class="text-[#064e3b] group-hover:text-[#fbbf24] font-black text-[10px] uppercase tracking-wider flex items-center gap-1 transition-colors cursor-pointer"
                                >
                                    Lihat Detail &rarr;
                                </Link>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </main>
    </div>
</template>

<style scoped>
/* Quill editor content styles */
:deep(.ql-editor) {
    padding: 0;
    font-size: 0.95rem;
    line-height: 1.8;
}
:deep(.ql-editor p) {
    margin-bottom: 1.25rem;
}
:deep(.ql-editor h1),
:deep(.ql-editor h2),
:deep(.ql-editor h3) {
    font-weight: 800;
    color: #111827;
    margin-top: 1.75rem;
    margin-bottom: 0.75rem;
}
:deep(.ql-editor img) {
    border-radius: 1rem;
    max-width: 100%;
    margin: 1.5rem auto;
}
:deep(.ql-editor blockquote) {
    border-left: 4px solid #064e3b;
    padding-left: 1rem;
    color: #6b7280;
    font-style: italic;
    margin: 1.5rem 0;
}
</style>
