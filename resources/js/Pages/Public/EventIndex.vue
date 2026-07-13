<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    ArrowLeftIcon, 
    MapPinIcon, 
    CalendarIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
    ClockIcon,
} from '@heroicons/vue/24/outline';
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    events:        Object,
    units:         Array,
    filters:       Object,
    featuredEvent: Object,  // dedicated prop dari backend — bukan dari pagination
});

// ---- Countdown Timer for Featured Event ----
const featuredCountdown = ref({ days: '00', hours: '00', minutes: '00' });
let featuredTimerInterval = null;

const startFeaturedCountdown = () => {
    const featured = featuredEvent.value;
    if (!featured || featured.computed_status !== 'upcoming') return;

    const targetDate = new Date(featured.start_date).getTime();

    const updateTimer = () => {
        const now = new Date().getTime();
        const difference = targetDate - now;

        if (difference <= 0) {
            featuredCountdown.value = { days: '00', hours: '00', minutes: '00' };
            clearInterval(featuredTimerInterval);
            return;
        }

        const d = Math.floor(difference / (1000 * 60 * 60 * 24));
        const h = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const m = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));

        featuredCountdown.value = {
            days: String(d).padStart(2, '0'),
            hours: String(h).padStart(2, '0'),
            minutes: String(m).padStart(2, '0'),
        };
    };

    updateTimer();
    featuredTimerInterval = setInterval(updateTimer, 60000); // update every minute to save resources
};

onMounted(() => {
    startFeaturedCountdown();
});

onUnmounted(() => {
    if (featuredTimerInterval) clearInterval(featuredTimerInterval);
});

const search = ref(props.filters?.search || '');
const selectedUnit = ref(props.filters?.unit || '');
const selectedStatus = ref(props.filters?.status || '');
const selectedSort = ref(props.filters?.sort || 'latest');

let debounceTimeout = null;
watch(search, () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(performSearch, 450);
});

const performSearch = () => {
    router.get(
        route('events.index'),
        { 
            search: search.value, 
            unit: selectedUnit.value, 
            status: selectedStatus.value, 
            sort: selectedSort.value 
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    clearTimeout(debounceTimeout);
    search.value = '';
    selectedUnit.value = '';
    selectedStatus.value = '';
    selectedSort.value = 'latest';
    performSearch();
};

const formatDateStr = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const getEventDateParts = (dateString) => {
    if (!dateString) return { day: '', month: '', year: '' };
    const date = new Date(dateString);
    return {
        day: date.toLocaleDateString('id-ID', { day: '2-digit' }),
        month: date.toLocaleDateString('id-ID', { month: 'short' }),
        year: date.getFullYear().toString().substring(2),
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
        return { label: 'Selesai', class: 'bg-gray-50 text-gray-500 border-gray-200' };
    }
    return { label: 'Dibatalkan', class: 'bg-red-50 text-red-700 border-red-200' };
};

// Featured Event diambil langsung dari prop backend (dedicated query, bukan pagination)
// Backend sudah menjamin: upcoming terdekat > ongoing > completed terbaru
const featuredEvent = computed(() => props.featuredEvent ?? null);

const secondaryEvents = computed(() => {
    if (!props.events?.data) return [];
    const featured = featuredEvent.value;
    // Exclude Featured Event dari grid sekunder berdasarkan id
    if (!featured) return props.events.data;
    return props.events.data.filter(e => e.id !== featured.id);
});

const pageUrl = computed(() => typeof window !== 'undefined' ? window.location.href : '');

const handleImageError = (e) => {
    e.target.onerror = null;
    e.target.src = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='800' height='450' viewBox='0 0 800 450'><rect width='100%' height='100%' fill='%23064e3b'/><circle cx='400' cy='225' r='120' fill='%23fbbf24' opacity='0.1'/><text x='50%' y='50%' dominant-baseline='middle' text-anchor='middle' fill='%23ffffff' font-family='sans-serif' font-size='24' font-weight='900' letter-spacing='2'>NAMIRA EVENTS</text></svg>";
};
</script>

<template>
    <Head>
        <title>Namira Event Center - Agenda & Kalender Kegiatan</title>
        <meta name="description" content="Pusat informasi resmi agenda kegiatan, seminar, perlombaan, dan workshop di lingkungan Yayasan Namira School." head-key="description" />
        <meta property="og:title" content="Namira Event Center - Agenda & Kalender Kegiatan" head-key="og:title" />
        <meta property="og:description" content="Pusat informasi resmi agenda kegiatan, seminar, perlombaan, dan workshop di lingkungan Yayasan Namira School." head-key="og:description" />
        <meta property="og:type" content="website" head-key="og:type" />
        <link rel="canonical" :href="pageUrl" />
    </Head>
    
    <div class="min-h-screen bg-[#fafafb] font-sans text-gray-900 selection:bg-[#fbbf24] selection:text-[#064e3b]">
        <!-- Navbar -->
        <header class="bg-[#064e3b] py-4 shadow-sm sticky top-0 z-50 border-b border-[#053c2d]">
            <div class="max-w-[1400px] mx-auto px-6 flex justify-between items-center">
                <Link href="/" class="text-white hover:text-[#fbbf24] transition flex items-center gap-2 text-xs font-semibold uppercase tracking-wider">
                    <ArrowLeftIcon class="w-4 h-4 stroke-[2.5]" /> Kembali ke Beranda
                </Link>
                <div class="text-white font-black text-xl tracking-wider">
                    NAMIRA<span class="text-[#fbbf24]">EVENTS</span>
                </div>
                <div class="text-[#fbbf24] text-xs font-bold uppercase tracking-widest hidden md:block">
                    Yayasan Namira School
                </div>
            </div>
        </header>

        <main class="max-w-[1400px] mx-auto px-6 py-8">
            <!-- HERO EVENT CENTER -->
            <div class="bg-gradient-to-br from-[#064e3b] to-[#043327] rounded-3xl p-8 md:p-12 text-white shadow-lg mb-8 relative overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(251,191,36,0.15),transparent_60%)]"></div>
                <div class="relative z-10 max-w-3xl">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/10 backdrop-blur-md text-[#fbbf24] text-[10px] font-black uppercase tracking-widest mb-4 border border-white/10 select-none">
                        Agenda Resmi Yayasan
                    </span>
                    <h1 class="text-3xl md:text-5xl font-black tracking-tight text-white mb-4 uppercase leading-none">
                        NAMIRA <span class="text-[#fbbf24]">EVENT CENTER</span>
                    </h1>
                    <p class="text-white/80 text-sm md:text-base leading-relaxed">
                        Agenda kegiatan, seminar, perlombaan, workshop, dan kegiatan resmi seluruh unit Yayasan Namira.
                    </p>
                </div>
            </div>

            <!-- SEARCH & FILTER BAR -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Search Input -->
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <MagnifyingGlassIcon class="h-4.5 w-4.5 text-gray-500" />
                        </span>
                        <input 
                            type="text" 
                            v-model="search"
                            placeholder="Cari seminar, lomba, kegiatan..." 
                            class="w-full pl-9 pr-8 py-2.5 text-xs bg-[#fdfdfd] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#064e3b] focus:border-transparent transition-all font-semibold"
                        />
                        <button 
                            v-if="search" 
                            @click="search = ''"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 cursor-pointer"
                            aria-label="Bersihkan pencarian"
                        >
                            <XMarkIcon class="h-4.5 w-4.5" />
                        </button>
                    </div>

                    <!-- Dropdown Unit -->
                    <div>
                        <select 
                            v-model="selectedUnit"
                            @change="performSearch"
                            class="w-full px-3 py-2.5 text-xs bg-[#fdfdfd] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#064e3b] focus:border-transparent transition-all font-semibold text-gray-700 cursor-pointer"
                        >
                            <option value="">Semua Unit</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                        </select>
                    </div>

                    <!-- Dropdown Status -->
                    <div>
                        <select 
                            v-model="selectedStatus"
                            @change="performSearch"
                            class="w-full px-3 py-2.5 text-xs bg-[#fdfdfd] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#064e3b] focus:border-transparent transition-all font-semibold text-gray-700 cursor-pointer"
                        >
                            <option value="">Semua Status</option>
                            <option value="upcoming">Akan Datang</option>
                            <option value="ongoing">Sedang Aktif</option>
                            <option value="completed">Selesai</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>

                    <!-- Dropdown Sort -->
                    <div>
                        <select 
                            v-model="selectedSort"
                            @change="performSearch"
                            class="w-full px-3 py-2.5 text-xs bg-[#fdfdfd] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#064e3b] focus:border-transparent transition-all font-semibold text-gray-700 cursor-pointer"
                        >
                            <option value="latest">Terbaru Dirilis</option>
                            <option value="nearest">Waktu Terdekat</option>
                            <option value="oldest">Waktu Terlama</option>
                            <option value="popular">Paling Populer</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 🔥 FEATURED EVENT (EVENT TERDEKAT) -->
            <section v-if="featuredEvent" class="mb-10">
                <div class="border-b-2 border-gray-900 pb-2 mb-6">
                    <h2 class="font-black text-lg text-gray-900 uppercase tracking-tight flex items-center gap-2 select-none">
                        <CalendarIcon class="w-5 h-5 text-[#064e3b] shrink-0" /> Event Terdekat
                    </h2>
                </div>
                <div class="bg-white rounded-3xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 grid grid-cols-1 lg:grid-cols-2 gap-0">
                    <!-- Cover Image -->
                    <!-- Image container: fixed height mobile/tablet, min-h-[480px] on desktop -->
                    <!-- lg:min-h-[480px] memastikan tinggi gambar tidak jatuh di bawah 480px meskipun konten teks singkat -->
                    <div class="relative h-[240px] sm:h-[340px] lg:h-full lg:min-h-[480px] bg-gray-50 overflow-hidden">
                        <img 
                            :src="featuredEvent.image_path ? '/' + featuredEvent.image_path : '/images/landing/banner-2.jpg'" 
                            :alt="featuredEvent.title" 
                            @error="handleImageError"
                            class="w-full h-full object-cover transform hover:scale-[1.01] transition duration-700" 
                        />
                        <div class="absolute top-4 left-4">
                            <span 
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-black border shadow-md bg-white select-none"
                                :class="getStatusBadge(featuredEvent.computed_status).class"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="{
                                    'bg-emerald-500': featuredEvent.computed_status === 'upcoming',
                                    'bg-amber-500': featuredEvent.computed_status === 'ongoing',
                                    'bg-gray-400': featuredEvent.computed_status === 'completed',
                                    'bg-red-500': featuredEvent.computed_status === 'cancelled',
                                }"></span>
                                {{ getStatusBadge(featuredEvent.computed_status).label }}
                            </span>
                        </div>
                    </div>
                    <!-- Details -->
                    <div class="p-6 md:p-8 flex flex-col justify-between">
                        <div>
                            <div class="flex flex-wrap gap-2 items-center mb-3">
                                <span class="bg-[#064e3b] text-[#fbbf24] text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded select-none">
                                    {{ featuredEvent.unit?.name || 'Yayasan' }}
                                </span>
                            </div>
                            <h3 class="text-xl md:text-3xl font-black text-gray-900 leading-tight mb-4 hover:text-[#064e3b] transition duration-300">
                                <Link :href="route('events.show', featuredEvent.slug)">
                                    {{ featuredEvent.title }}
                                </Link>
                            </h3>
                            <p class="text-gray-500 text-xs md:text-sm line-clamp-3 leading-relaxed mb-6">
                                {{ featuredEvent.description ? featuredEvent.description.replace(/<[^>]*>/g, '') : 'Tidak ada deskripsi untuk acara ini.' }}
                            </p>
                            
                            <!-- Countdown Live -->
                            <div v-if="featuredEvent.computed_status === 'upcoming'" class="bg-amber-50 border border-amber-100 rounded-2xl p-4 mb-6 max-w-sm">
                                <p class="text-[10px] font-black uppercase tracking-wider text-amber-800 mb-1.5 select-none">Acara Dimulai Dalam</p>
                                <div class="flex gap-4">
                                    <div class="text-center">
                                        <span class="text-lg font-black text-amber-900 block leading-none">{{ featuredCountdown.days }}</span>
                                        <span class="text-[9px] font-bold text-amber-700 uppercase">Hari</span>
                                    </div>
                                    <div class="text-center">
                                        <span class="text-lg font-black text-amber-900 block leading-none">{{ featuredCountdown.hours }}</span>
                                        <span class="text-[9px] font-bold text-amber-700 uppercase">Jam</span>
                                    </div>
                                    <div class="text-center">
                                        <span class="text-lg font-black text-amber-900 block leading-none">{{ featuredCountdown.minutes }}</span>
                                        <span class="text-[9px] font-bold text-amber-700 uppercase">Menit</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2 text-xs font-semibold text-gray-500">
                                    <CalendarIcon class="w-4 h-4 text-[#064e3b]" />
                                    <span>{{ formatDateStr(featuredEvent.start_date) }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs font-semibold text-gray-500">
                                    <MapPinIcon class="w-4 h-4 text-[#064e3b]" />
                                    <span>{{ featuredEvent.location || 'Kampus Namira' }}</span>
                                </div>
                            </div>
                            <Link 
                                :href="route('events.show', featuredEvent.slug)"
                                class="bg-[#064e3b] text-white hover:bg-[#053c2d] text-xs font-black uppercase tracking-widest px-5 py-3.5 rounded-xl transition duration-300 text-center w-full sm:w-auto shadow-sm cursor-pointer"
                            >
                                Lihat Detail
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SEMUA AGENDA KEGIATAN -->
            <section class="mb-10">
                <div class="border-b-2 border-gray-900 pb-2 mb-6">
                    <h2 class="font-black text-lg text-gray-900 uppercase tracking-tight">Semua Agenda Kegiatan</h2>
                </div>

                <div v-if="secondaryEvents.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <article 
                        v-for="event in secondaryEvents" 
                        :key="event.id" 
                        class="bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-md transition duration-300 flex flex-col group relative"
                    >
                        <!-- Top Image Banner -->
                        <!-- aspect-[16/9]: konsisten di semua ukuran gambar (landscape/portrait/square/poster) -->
                        <div class="aspect-[16/9] w-full overflow-hidden relative shrink-0">
                            <img 
                                :src="event.image_path ? '/' + event.image_path : '/images/landing/banner-2.jpg'" 
                                :alt="event.title" 
                                @error="handleImageError"
                                loading="lazy"
                                width="800"
                                height="450"
                                class="w-full h-full object-cover transform group-hover:scale-[1.03] transition duration-700 ease-out" 
                            />
                            
                            <!-- Overlapping Ticket-Style Calendar Sheet (Date) -->
                            <div class="absolute top-3 left-3 bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden flex flex-col items-center justify-center w-12 h-14 select-none z-10">
                                <span class="bg-[#064e3b] text-white text-[11px] font-black uppercase tracking-wider w-full text-center py-0.5 leading-none">
                                    {{ getEventDateParts(event.start_date).month }}
                                </span>
                                <span class="text-gray-900 text-lg font-black leading-none py-1">
                                    {{ getEventDateParts(event.start_date).day }}
                                </span>
                            </div>

                            <div class="absolute top-3 right-3 z-10">
                                <span 
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold border bg-white/95 backdrop-blur-sm shadow-sm select-none"
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
                        </div>

                        <!-- Ticket Body Details -->
                        <div class="p-5 flex-grow flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="bg-emerald-50 text-[#064e3b] text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded border border-emerald-100 select-none">
                                        {{ event.unit?.name || 'Yayasan' }}
                                    </span>
                                    <span v-if="event.views" class="text-xs text-gray-500 font-semibold select-none">
                                        {{ event.views }} dilihat
                                    </span>
                                </div>
                                
                                <Link :href="route('events.show', event.slug)">
                                    <h3 class="text-sm font-extrabold text-gray-900 mb-3 line-clamp-2 leading-snug group-hover:text-[#064e3b] transition duration-200">
                                        {{ event.title }}
                                    </h3>
                                </Link>
                                
                                <div class="space-y-1.5 mb-4">
                                    <div class="flex items-center gap-2 text-[10px] text-gray-500 font-semibold">
                                        <ClockIcon class="w-3.5 h-3.5 text-[#064e3b] shrink-0" />
                                        <span>{{ formatDateStr(event.start_date) }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-[10px] text-gray-500 font-semibold">
                                        <MapPinIcon class="w-3.5 h-3.5 text-[#064e3b] shrink-0" />
                                        <span class="truncate">{{ event.location || 'Kampus Namira' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-gray-50">
                                <Link 
                                    :href="route('events.show', event.slug)"
                                    class="text-[#064e3b] group-hover:text-[#fbbf24] font-black text-[11px] uppercase tracking-wider flex items-center gap-1 transition-colors cursor-pointer"
                                >
                                    Lihat Detail &rarr;
                                </Link>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-16 bg-white rounded-2xl border border-gray-100 shadow-sm max-w-xl mx-auto select-none">
                    <CalendarIcon class="w-12 h-12 mx-auto text-gray-300 mb-3" />
                    <h4 class="text-base font-extrabold text-gray-800 mb-1">Tidak ada acara ditemukan</h4>
                    <p class="text-gray-500 text-xs max-w-xs mx-auto mb-5">
                        Coba ubah kata kunci pencarian atau bersihkan filter yang dipilih.
                    </p>
                    <button 
                        @click="resetFilters" 
                        class="bg-[#064e3b] text-white font-bold text-xs uppercase tracking-wider px-5 py-2.5 rounded-xl hover:bg-[#053c2d] transition cursor-pointer"
                    >
                        Reset Filter
                    </button>
                </div>

                <!-- Pagination -->
                <div v-if="events.links && events.links.length > 3" class="mt-14 flex justify-center">
                    <div class="flex flex-wrap justify-center gap-2">
                        <Link 
                            v-for="(link, k) in events.links" 
                            :key="k" 
                            :href="link.url || '#'" 
                            v-html="link.label"
                            class="px-4 py-2 text-xs font-bold tracking-wider uppercase rounded-xl transition-all"
                            :class="[
                                link.active ? 'bg-[#064e3b] text-white shadow-md' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200',
                                !link.url ? 'opacity-40 cursor-not-allowed border-transparent' : ''
                            ]"
                        />
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
