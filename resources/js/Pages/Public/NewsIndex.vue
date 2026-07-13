<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    ArrowLeftIcon, 
    CalendarIcon, 
    MagnifyingGlassIcon, 
    ClockIcon, 
    XMarkIcon,
    ArrowRightIcon,
    FireIcon,
    EyeIcon,
} from '@heroicons/vue/24/outline';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    news:     Object,
    units:    Array,
    trending: Array,
    popular:  Array,
    filters:  Object,
});

const search        = ref(props.filters?.search || '');
const selectedUnit  = ref(props.filters?.unit ? Number(props.filters.unit) : '');
const selectedSort  = ref(props.filters?.sort || 'terbaru');

const isDefaultHome = computed(() => !search.value && !selectedUnit.value);

const headlineNews = computed(() =>
    props.news?.data?.length > 0 ? props.news.data[0] : null
);

const popularNews = computed(() =>
    props.popular && props.popular.length > 0 ? props.popular : (props.news?.data?.length > 1 ? props.news.data.slice(1, 5) : [])
);

const secondaryNews = computed(() => {
    if (!props.news?.data) return [];
    return isDefaultHome.value ? props.news.data.slice(5) : props.news.data;
});

let debounceTimeout = null;
watch(search, () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(performSearch, 450);
});

const selectUnit = (unitId) => {
    selectedUnit.value = unitId === '' ? '' : Number(unitId);
    performSearch();
};

const setSort = (sort) => {
    selectedSort.value = sort;
    performSearch();
};

const clearFilters = () => {
    clearTimeout(debounceTimeout);
    search.value       = '';
    selectedUnit.value = '';
    selectedSort.value = 'terbaru';
    performSearch();
};

const performSearch = () => {
    router.get(
        route('news.index'),
        { search: search.value, unit: selectedUnit.value, sort: selectedSort.value },
        { preserveState: true, replace: true }
    );
};

const formatDateStr = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric'
    });
};

const formatDateShort = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric'
    });
};

const calculateReadTime = (htmlContent) => {
    if (!htmlContent) return '1 mnt';
    const wordCount = htmlContent.replace(/<[^>]*>/g, '').trim().split(/\s+/).length;
    return `${Math.ceil(wordCount / 200)} mnt baca`;
};

const getExcerpt = (htmlContent, limit = 150) => {
    if (!htmlContent) return '';
    const text = htmlContent.replace(/<[^>]*>/g, '');
    return text.length > limit ? text.substring(0, limit) + '...' : text;
};

const formatViews = (views) => {
    if (!views) return '0';
    if (views >= 1000) return (views / 1000).toFixed(1) + 'k';
    return views.toString();
};
</script>

<template>
    <Head>
        <title>Portal Berita - Yayasan Namira</title>
        <meta name="description" content="Portal Berita Resmi Yayasan Namira. Temukan informasi terbaru, kegiatan, prestasi, dan pengumuman dari seluruh unit sekolah Namira." head-key="description" />
        <meta property="og:title" content="Portal Berita - Yayasan Namira" head-key="og:title" />
        <meta property="og:description" content="Portal Berita Resmi Yayasan Namira. Temukan informasi terbaru, kegiatan, prestasi, dan pengumuman dari seluruh unit sekolah Namira." head-key="og:description" />
        <meta property="og:type" content="website" head-key="og:type" />
    </Head>
    
    <div class="min-h-screen bg-[#fafafb] font-sans text-gray-900 selection:bg-[#fbbf24] selection:text-[#064e3b]">

        <!-- NAVBAR -->
        <header class="bg-[#064e3b] py-4 shadow-sm sticky top-0 z-50 border-b border-[#053c2d]">
            <div class="max-w-[1400px] mx-auto px-6 flex justify-between items-center">
                <Link href="/" class="text-white hover:text-[#fbbf24] transition-colors flex items-center gap-2 text-xs font-semibold uppercase tracking-wider">
                    <ArrowLeftIcon class="w-4 h-4 stroke-[2.5]" /> Kembali
                </Link>
                <Link href="/berita" class="text-white font-black text-xl tracking-wider">
                    NAMIRA<span class="text-[#fbbf24]">NEWS</span>
                </Link>
                <div class="text-[#fbbf24] text-xs font-bold uppercase tracking-widest hidden md:block">
                    Yayasan Namira School
                </div>
            </div>
        </header>

        <main class="max-w-[1400px] mx-auto px-6 py-8">

            <!-- PORTAL BANNER -->
            <div class="border-b-4 border-gray-900 pb-4 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-3">
                    <div>
                        <h1 class="text-4xl md:text-5xl font-black tracking-tight text-gray-900 uppercase leading-none">
                            Namira Portal News
                        </h1>
                        <p class="text-sm text-gray-500 font-medium mt-1.5">
                            Pusat informasi kegiatan, prestasi &amp; berita seluruh unit Yayasan Namira.
                        </p>
                    </div>
                    <div class="text-xs font-bold text-gray-600 bg-gray-100 px-3 py-1.5 rounded uppercase tracking-wider shrink-0">
                        {{ new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
                    </div>
                </div>
            </div>

            <!-- TRENDING BAR -->
            <div v-if="trending && trending.length > 0" class="bg-gray-900 rounded-xl px-4 py-2.5 mb-7 flex items-center gap-3 overflow-hidden">
                <div class="flex items-center gap-1.5 shrink-0">
                    <FireIcon class="w-4 h-4 text-orange-400" />
                    <span class="text-orange-400 text-[11px] font-black uppercase tracking-widest">Trending</span>
                </div>
                <div class="h-4 w-px bg-gray-600 shrink-0"></div>
                <div class="flex gap-5 overflow-x-auto no-scrollbar">
                    <Link 
                        v-for="(item, i) in trending" 
                        :key="item.id"
                        :href="route('news.show', item.slug)"
                        class="flex items-center gap-2 shrink-0 group"
                    >
                        <span class="text-gray-500 text-[10px] font-black">{{ String(i+1).padStart(2,'0') }}</span>
                        <span class="text-gray-200 text-xs font-semibold group-hover:text-[#fbbf24] transition-colors truncate max-w-[180px]">
                            {{ item.title }}
                        </span>
                    </Link>
                </div>
            </div>

            <!-- HERO GRID (default homepage only) -->
            <section v-if="isDefaultHome && headlineNews" class="grid grid-cols-1 lg:grid-cols-3 gap-7 mb-10">
                <!-- Feature Article (2/3 width) -->
                <div class="lg:col-span-2 group">
                    <Link :href="route('news.show', headlineNews.slug)" class="block overflow-hidden rounded-2xl relative shadow-sm border border-gray-100 bg-white">
                        <div class="relative h-[300px] sm:h-[400px] overflow-hidden">
                            <img 
                                :src="headlineNews.image_path ? '/' + headlineNews.image_path : '/images/landing/banner-1.jpg'" 
                                :alt="headlineNews.title" 
                                class="w-full h-full object-cover transform group-hover:scale-[1.02] transition duration-700 ease-out" 
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/25 to-transparent"></div>
                            <div class="absolute top-4 left-4 flex gap-2">
                                <span class="bg-[#064e3b] text-[#fbbf24] text-xs font-bold tracking-wider uppercase px-3 py-1.5 rounded shadow-md">
                                    {{ headlineNews.unit?.name || 'Yayasan' }}
                                </span>
                                <span class="bg-[#fbbf24] text-[#064e3b] text-xs font-bold tracking-wider uppercase px-3 py-1.5 rounded shadow-md">
                                    Berita Utama
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex flex-wrap items-center gap-3 text-[11px] text-gray-500 mb-2.5 font-semibold">
                                <span class="flex items-center gap-1.5"><CalendarIcon class="w-3.5 h-3.5" />{{ formatDateStr(headlineNews.published_at) }}</span>
                                <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                <span class="flex items-center gap-1.5"><ClockIcon class="w-3.5 h-3.5" />{{ calculateReadTime(headlineNews.content) }}</span>
                                <span v-if="headlineNews.views" class="flex items-center gap-1.5 ml-auto">
                                    <EyeIcon class="w-3.5 h-3.5" />{{ formatViews(headlineNews.views) }} dilihat
                                </span>
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight leading-tight group-hover:text-[#064e3b] transition duration-300 mb-2.5">
                                {{ headlineNews.title }}
                            </h2>
                            <p class="text-gray-600 text-sm line-clamp-2 leading-relaxed mb-4">
                                {{ getExcerpt(headlineNews.content, 200) }}
                            </p>
                            <span class="inline-flex items-center gap-1.5 text-[#064e3b] font-extrabold text-xs uppercase tracking-wider group-hover:text-[#fbbf24] transition duration-300">
                                Baca Selengkapnya <ArrowRightIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                            </span>
                        </div>
                    </Link>
                </div>

                <!-- Sidebar Terpopuler (1/3 width) -->
                <div class="flex flex-col">
                    <div class="border-b-2 border-gray-900 pb-2 mb-3">
                        <h3 class="font-extrabold text-base tracking-tight text-gray-900 uppercase">Terpopuler</h3>
                    </div>
                    <div class="flex flex-col">
                        <div v-for="(item, index) in popularNews" :key="item.id" class="group border-b border-gray-100 last:border-0">
                            <Link :href="route('news.show', item.slug)" class="flex gap-3 py-3.5 px-1 hover:bg-gray-50 rounded-lg transition-colors">
                                <div class="shrink-0 font-black text-2xl text-gray-200 group-hover:text-[#fbbf24] transition-colors w-8 pt-0.5 select-none leading-none">
                                    {{ String(index + 1).padStart(2, '0') }}
                                </div>
                                <div class="shrink-0 w-16 h-14 rounded-lg overflow-hidden bg-gray-100">
                                    <img 
                                        :src="item.image_path ? '/' + item.image_path : '/images/landing/banner-1.jpg'" 
                                        :alt="item.title"
                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                    />
                                </div>
                                <div class="flex-grow min-w-0">
                                    <span class="text-[10px] font-bold tracking-wider uppercase text-[#064e3b] bg-emerald-50 px-1.5 py-0.5 rounded">
                                        {{ item.unit?.name || 'Yayasan' }}
                                    </span>
                                    <h4 class="text-xs font-extrabold text-gray-900 leading-snug group-hover:text-[#064e3b] transition mt-1 mb-1 line-clamp-2">
                                        {{ item.title }}
                                    </h4>
                                    <div class="flex items-center gap-2 text-[10px] text-gray-400 font-medium">
                                        <span>{{ formatDateShort(item.published_at) }}</span>
                                        <span v-if="item.views" class="flex items-center gap-0.5">
                                            <EyeIcon class="w-3 h-3 inline" /> {{ formatViews(item.views) }}
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        </div>
                        <div v-if="popularNews.length === 0" class="text-sm text-gray-400 italic py-6 text-center">
                            Belum ada berita terpopuler.
                        </div>
                    </div>
                </div>
            </section>

            <!-- SEARCH + SORT + CATEGORY BAR -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-7 flex flex-col gap-3">
                <div class="flex gap-3 items-center">
                    <div class="relative flex-grow">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <MagnifyingGlassIcon class="h-4 w-4 text-gray-400" />
                        </span>
                        <input 
                            type="text" 
                            v-model="search"
                            placeholder="Cari berita, kegiatan, prestasi, atau pengumuman..." 
                            class="w-full pl-9 pr-8 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#064e3b] focus:border-transparent transition-all"
                        />
                        <button 
                            v-if="search" 
                            @click="clearFilters"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-700 cursor-pointer"
                        >
                            <XMarkIcon class="h-4 w-4" />
                        </button>
                    </div>
                    <div class="flex items-center gap-1 shrink-0 bg-gray-100 p-1 rounded-xl">
                        <button 
                            @click="setSort('terbaru')"
                            class="px-3 py-1.5 text-[11px] font-bold uppercase tracking-wide rounded-lg transition cursor-pointer"
                            :class="selectedSort === 'terbaru' ? 'bg-white text-[#064e3b] shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                        >
                            Terbaru
                        </button>
                        <button 
                            @click="setSort('terpopuler')"
                            class="px-3 py-1.5 text-[11px] font-bold uppercase tracking-wide rounded-lg transition cursor-pointer"
                            :class="selectedSort === 'terpopuler' ? 'bg-white text-[#064e3b] shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                        >
                            Terpopuler
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
                    <button 
                        @click="selectUnit('')"
                        class="px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-wider rounded-xl transition shrink-0 border cursor-pointer"
                        :class="selectedUnit === '' ? 'bg-[#064e3b] text-[#fbbf24] border-[#064e3b]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                    >
                        Semua
                    </button>
                    <button 
                        v-for="unit in units" 
                        :key="unit.id"
                        @click="selectUnit(unit.id)"
                        class="px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-wider rounded-xl transition shrink-0 border cursor-pointer whitespace-nowrap"
                        :class="selectedUnit === unit.id ? 'bg-[#064e3b] text-[#fbbf24] border-[#064e3b]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                    >
                        {{ unit.name }}<span v-if="unit.news_count !== undefined" class="ml-1 opacity-60">({{ unit.news_count }})</span>
                    </button>
                </div>
            </div>

            <!-- RESULTS INDICATOR -->
            <div v-if="!isDefaultHome" class="border-b border-gray-200 pb-3 mb-6 flex justify-between items-center">
                <h3 class="font-extrabold text-sm text-gray-500 uppercase tracking-wider">
                    {{ news.total || 0 }} berita ditemukan
                    <span v-if="search" class="normal-case font-normal text-gray-400"> untuk "<em class="text-gray-700">{{ search }}</em>"</span>
                </h3>
                <button @click="clearFilters" class="text-xs font-extrabold text-red-500 hover:text-red-700 uppercase flex items-center gap-1 cursor-pointer">
                    <XMarkIcon class="w-3.5 h-3.5" /> Bersihkan Filter
                </button>
            </div>

            <!-- ARTICLE GRID -->
            <div v-if="secondaryNews.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-7">
                <article 
                    v-for="item in secondaryNews" 
                    :key="item.id" 
                    class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 group flex flex-col hover:shadow-md transition duration-300"
                >
                    <Link :href="route('news.show', item.slug)" class="block relative h-48 overflow-hidden shrink-0">
                        <img 
                            :src="item.image_path ? '/' + item.image_path : '/images/landing/banner-1.jpg'" 
                            :alt="item.title" 
                            class="w-full h-full object-cover transform group-hover:scale-[1.03] transition duration-700 ease-out" 
                        />
                        <div class="absolute top-3 left-3 bg-white/95 backdrop-blur-sm text-[#064e3b] text-[10px] font-black tracking-wider uppercase px-2 py-0.5 rounded shadow-sm">
                            {{ item.unit?.name || 'Yayasan' }}
                        </div>
                    </Link>
                    
                    <div class="p-5 flex-grow flex flex-col">
                        <div class="flex items-center gap-2 text-[10px] text-gray-400 font-semibold mb-2.5">
                            <span class="flex items-center gap-1"><CalendarIcon class="w-3.5 h-3.5" />{{ formatDateShort(item.published_at) }}</span>
                            <span class="w-1 h-1 rounded-full bg-gray-300 shrink-0"></span>
                            <span class="flex items-center gap-1"><ClockIcon class="w-3.5 h-3.5" />{{ calculateReadTime(item.content) }}</span>
                            <span v-if="item.views" class="flex items-center gap-1 ml-auto shrink-0">
                                <EyeIcon class="w-3.5 h-3.5" />{{ formatViews(item.views) }}
                            </span>
                        </div>
                        
                        <Link :href="route('news.show', item.slug)">
                            <h3 class="text-sm font-extrabold text-gray-900 mb-2 line-clamp-3 leading-snug group-hover:text-[#064e3b] transition duration-200">
                                {{ item.title }}
                            </h3>
                        </Link>
                        
                        <p class="text-gray-500 text-xs line-clamp-2 leading-relaxed mb-4">
                            {{ getExcerpt(item.content, 100) }}
                        </p>
                        
                        <div class="mt-auto pt-3 border-t border-gray-50">
                            <Link 
                                :href="route('news.show', item.slug)" 
                                class="text-[#064e3b] font-bold text-[11px] uppercase tracking-wider group-hover:text-[#fbbf24] flex items-center gap-1 transition-colors"
                            >
                                Baca Selengkapnya <ArrowRightIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                            </Link>
                        </div>
                    </div>
                </article>
            </div>

            <!-- EMPTY STATE -->
            <div v-else-if="!isDefaultHome" class="text-center py-20 bg-white rounded-2xl border border-gray-100 shadow-sm">
                <div class="w-16 h-16 bg-gray-50 text-gray-300 rounded-full flex items-center justify-center mx-auto mb-4">
                    <MagnifyingGlassIcon class="w-8 h-8" />
                </div>
                <h4 class="text-lg font-bold text-gray-800 mb-1">Berita tidak ditemukan</h4>
                <p class="text-gray-500 text-sm max-w-sm mx-auto mb-6">
                    Tidak ada hasil untuk pencarian atau filter yang dipilih.
                </p>
                <button @click="clearFilters" class="bg-[#064e3b] text-white font-bold text-xs uppercase tracking-wider px-5 py-2.5 rounded-xl hover:bg-[#053c2d] transition cursor-pointer">
                    Reset Filter
                </button>
            </div>

            <!-- PAGINATION -->
            <div v-if="news.links && news.links.length > 3" class="mt-14 flex justify-center">
                <div class="flex flex-wrap justify-center gap-2">
                    <Link 
                        v-for="(link, k) in news.links" 
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

        </main>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
