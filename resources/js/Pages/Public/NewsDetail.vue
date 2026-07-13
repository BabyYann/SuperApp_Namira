<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    ArrowLeftIcon, 
    CalendarIcon, 
    UserIcon, 
    ClockIcon, 
    EyeIcon,
    ArrowRightIcon,
    LinkIcon,
    CheckIcon,
} from '@heroicons/vue/24/outline';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    news:    Object,
    related: Array,
    latest:  Array,
});

// ---- Meta & SEO ----
const metaDescription = computed(() => {
    const text = props.news?.content?.replace(/<[^>]*>/g, '') || '';
    return text.length > 155 ? text.substring(0, 155).trim() + '...' : text.trim();
});

const absoluteImageUrl = computed(() => {
    if (!props.news?.image_path) return '';
    if (props.news.image_path.startsWith('http://') || props.news.image_path.startsWith('https://')) {
        return props.news.image_path;
    }
    const origin = typeof window !== 'undefined' ? window.location.origin : '';
    return `${origin}/${props.news.image_path.replace(/^\//, '')}`;
});

const pageUrl = computed(() => {
    return typeof window !== 'undefined' ? window.location.href : '';
});

// ---- Scroll & UI Helpers ----
const showBackToTop = ref(false);
const scrollProgress = ref(0);
const searchQuery = ref('');

const handleScroll = () => {
    const scrollY = window.scrollY;
    showBackToTop.value = scrollY > 400;

    const documentHeight = document.documentElement.scrollHeight;
    const windowHeight = window.innerHeight;
    const scrollableHeight = documentHeight - windowHeight;
    
    if (scrollableHeight > 0) {
        scrollProgress.value = (scrollY / scrollableHeight) * 100;
    } else {
        scrollProgress.value = 0;
    }
};

const scrollToTop = () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
};

const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.get(route('news.index'), { search: searchQuery.value.trim() });
    }
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

// ---- Helpers ----
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

const getExcerpt = (htmlContent, limit = 100) => {
    if (!htmlContent) return '';
    const text = htmlContent.replace(/<[^>]*>/g, '');
    return text.length > limit ? text.substring(0, limit) + '...' : text;
};

const formatViews = (views) => {
    if (!views) return '0';
    if (views >= 1000) return (views / 1000).toFixed(1) + 'k';
    return views.toString();
};

// ---- Back navigation ----
const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '/berita';
    }
};

// ---- Share tools ----
const copied = ref(false);

const shareWhatsApp = () => {
    const url  = encodeURIComponent(window.location.href);
    const text = encodeURIComponent(props.news.title + ' — Namira News\n');
    window.open(`https://wa.me/?text=${text}${url}`, '_blank');
};

const shareFacebook = () => {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`, '_blank');
};

const shareX = () => {
    const text = encodeURIComponent(props.news.title + ' — Namira News');
    window.open(`https://twitter.com/intent/tweet?text=${text}&url=${encodeURIComponent(window.location.href)}`, '_blank');
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
        // Fallback for older browsers
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
</script>

<template>
    <Head>
        <title>{{ news.title }} - Namira News</title>
        <meta name="description" :content="metaDescription" head-key="description" />
        
        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="article" head-key="og:type" />
        <meta property="og:title" :content="news.title + ' - Namira News'" head-key="og:title" />
        <meta property="og:description" :content="metaDescription" head-key="og:description" />
        <meta property="og:url" :content="pageUrl" head-key="og:url" />
        <meta v-if="absoluteImageUrl" property="og:image" :content="absoluteImageUrl" head-key="og:image" />
        <meta property="og:site_name" content="Namira News" head-key="og:site_name" />
        
        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" head-key="twitter:card" />
        <meta name="twitter:title" :content="news.title + ' - Namira News'" head-key="twitter:title" />
        <meta name="twitter:description" :content="metaDescription" head-key="twitter:description" />
        <meta v-if="absoluteImageUrl" name="twitter:image" :content="absoluteImageUrl" head-key="twitter:image" />
    </Head>

    <!-- Reading Progress Bar -->
    <div 
        class="fixed top-0 left-0 h-1 bg-[#fbbf24] z-[60] transition-all duration-100 ease-out" 
        :style="{ width: `${scrollProgress}%` }"
    ></div>
    
    <div class="min-h-screen bg-[#fafafb] font-sans text-gray-900 selection:bg-[#fbbf24] selection:text-[#064e3b]">

        <!-- NAVBAR -->
        <header class="bg-[#064e3b] py-4 shadow-md sticky top-0 z-50 border-b border-[#053c2d]">
            <div class="max-w-[1200px] mx-auto px-6 flex justify-between items-center">
                <button 
                    @click="goBack"
                    class="text-[#fbbf24] hover:text-white transition flex items-center gap-2 text-xs font-bold uppercase tracking-widest cursor-pointer"
                >
                    <ArrowLeftIcon class="w-4 h-4" /> Kembali
                </button>
                <Link href="/berita" class="text-white font-black text-xl tracking-wider">
                    NAMIRA<span class="text-[#fbbf24]">NEWS</span>
                </Link>
                <Link href="/berita" class="text-[#fbbf24] text-xs font-bold uppercase tracking-widest hidden md:block hover:text-white transition">
                    Portal Berita
                </Link>
            </div>
        </header>

        <main class="max-w-[1200px] mx-auto px-6 py-10">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

                <!-- ════════════════════════ MAIN ARTICLE COLUMN ════ -->
                <div class="lg:col-span-2">

                    <!-- Breadcrumb -->
                    <nav class="flex items-center gap-2 text-xs font-semibold text-gray-400 mb-7 flex-wrap">
                        <Link href="/" class="hover:text-[#064e3b] transition">Beranda</Link>
                        <span>/</span>
                        <Link href="/berita" class="hover:text-[#064e3b] transition">Berita</Link>
                        <span>/</span>
                        <Link :href="route('news.index', { unit: news.unit_id })" class="hover:text-[#064e3b] transition">
                            {{ news.unit?.name || 'Yayasan' }}
                        </Link>
                        <span>/</span>
                        <span class="text-[#064e3b] font-bold truncate max-w-[200px]">{{ news.title }}</span>
                    </nav>

                    <!-- Category Badge -->
                    <div class="mb-3">
                        <Link 
                            :href="route('news.index', { unit: news.unit_id })"
                            class="inline-block bg-[#064e3b] text-[#fbbf24] text-[11px] font-black uppercase tracking-widest px-3.5 py-1.5 rounded-lg hover:bg-[#053c2d] transition"
                        >
                            {{ news.unit?.name || 'Yayasan Namira' }}
                        </Link>
                    </div>

                    <!-- Article Title -->
                    <h1 class="text-3xl md:text-4xl font-black text-gray-900 leading-tight mb-5 tracking-tight">
                        {{ news.title }}
                    </h1>

                    <!-- Meta Bar -->
                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 border-y border-gray-200 py-4 mb-8">
                        <div class="flex items-center gap-1.5">
                            <CalendarIcon class="w-4 h-4 text-[#064e3b]" />
                            <span>{{ formatDateStr(news.published_at) }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <UserIcon class="w-4 h-4 text-[#064e3b]" />
                            <span>Oleh <strong class="text-gray-800">{{ news.author?.name || 'Admin' }}</strong></span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <ClockIcon class="w-4 h-4 text-[#064e3b]" />
                            <span>{{ calculateReadTime(news.content) }}</span>
                        </div>
                        <div v-if="news.views" class="flex items-center gap-1.5 ml-auto">
                            <EyeIcon class="w-4 h-4 text-gray-400" />
                            <span>{{ formatViews(news.views) }} kali dilihat</span>
                        </div>
                    </div>

                    <!-- Cover Image -->
                    <div v-if="news.image_path" class="w-full aspect-video rounded-2xl overflow-hidden shadow-lg mb-10">
                        <img :src="'/' + news.image_path" :alt="news.title" class="w-full h-full object-cover" />
                    </div>

                    <!-- Article Content -->
                    <article class="prose prose-lg max-w-none text-gray-700 leading-relaxed ql-editor px-0 mb-12" v-html="news.content"></article>

                    <!-- ═══════════════════════════════ SHARE TOOLS ══ -->
                    <div class="bg-gray-50 rounded-2xl p-6 mb-10 border border-gray-100">
                        <p class="text-xs font-black uppercase tracking-widest text-gray-500 mb-4 text-center">Bagikan Artikel Ini</p>
                        <div class="flex justify-center gap-3 flex-wrap">
                            <!-- WhatsApp -->
                            <button 
                                @click="shareWhatsApp"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wide transition hover:scale-105 cursor-pointer"
                                style="background:#25D366;color:white;"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                WhatsApp
                            </button>
                            <!-- Facebook -->
                            <button 
                                @click="shareFacebook"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wide transition hover:scale-105 cursor-pointer"
                                style="background:#1877F2;color:white;"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                Facebook
                            </button>
                            <!-- X / Twitter -->
                            <button 
                                @click="shareX"
                                class="flex items-center gap-2 px-4 py-2.5 bg-black text-white rounded-xl font-bold text-xs uppercase tracking-wide transition hover:bg-gray-800 hover:scale-105 cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.748l7.73-8.835L1.254 2.25H8.08l4.26 5.632zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                X
                            </button>
                            <!-- LinkedIn -->
                            <button 
                                @click="shareLinkedIn"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wide transition hover:scale-105 cursor-pointer"
                                style="background:#0A66C2;color:white;"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.779-1.75-1.75s.784-1.75 1.75-1.75 1.75.779 1.75 1.75-.784 1.75-1.75 1.75zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                                LinkedIn
                            </button>
                            <!-- Copy Link -->
                            <button 
                                @click="copyLink"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wide transition hover:scale-105 cursor-pointer border"
                                :class="copied ? 'bg-emerald-50 text-[#064e3b] border-emerald-200' : 'bg-white text-gray-700 border-gray-200 hover:border-gray-300'"
                            >
                                <CheckIcon v-if="copied" class="w-4 h-4" />
                                <LinkIcon v-else class="w-4 h-4" />
                                {{ copied ? 'Tersalin!' : 'Salin Link' }}
                            </button>
                        </div>
                    </div>

                    <!-- Search Bar (Mobile only, above Related News) -->
                    <div class="lg:hidden bg-white rounded-2xl p-5 border border-gray-100 shadow-sm mb-10">
                        <h3 class="font-extrabold text-xs tracking-wider text-gray-400 uppercase mb-3">Cari Berita Lain</h3>
                        <form @submit.prevent="handleSearch" class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="h-4.5 w-4.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.604 10.604z" />
                                </svg>
                            </span>
                            <input 
                                type="text" 
                                v-model="searchQuery"
                                placeholder="Ketik kata kunci..." 
                                class="w-full pl-10 pr-4 py-2.5 text-xs bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#064e3b] focus:border-transparent transition-all font-semibold"
                            />
                        </form>
                    </div>

                    <!-- ══════════════════════════ RELATED NEWS ══ -->
                    <section v-if="related && related.length > 0">
                        <div class="border-b-2 border-gray-900 pb-2 mb-5">
                            <h2 class="font-black text-xl tracking-tight text-gray-900 uppercase">Berita Terkait</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Artikel lain dari unit {{ news.unit?.name || 'yang sama' }}</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
                            <Link 
                                v-for="item in related" 
                                :key="item.id"
                                :href="route('news.show', item.slug)"
                                class="group bg-white rounded-xl overflow-hidden border border-gray-100 hover:shadow-md transition duration-300 flex flex-col"
                            >
                                <div class="h-36 overflow-hidden shrink-0">
                                    <img 
                                        :src="item.image_path ? '/' + item.image_path : '/images/landing/banner-1.jpg'"
                                        :alt="item.title"
                                        class="w-full h-full object-cover group-hover:scale-[1.04] transition duration-500"
                                    />
                                </div>
                                <div class="p-4 flex-grow">
                                    <p class="text-[10px] text-gray-400 font-semibold mb-1.5">{{ formatDateShort(item.published_at) }}</p>
                                    <h3 class="text-sm font-bold text-gray-900 line-clamp-2 leading-snug group-hover:text-[#064e3b] transition">
                                        {{ item.title }}
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-1.5 line-clamp-2 leading-relaxed">{{ getExcerpt(item.content, 80) }}</p>
                                </div>
                                <div class="px-4 pb-4">
                                    <span class="text-[#064e3b] text-[11px] font-bold uppercase tracking-wide flex items-center gap-1 group-hover:text-[#fbbf24] transition">
                                        Baca <ArrowRightIcon class="w-3 h-3 stroke-[2.5]" />
                                    </span>
                                </div>
                            </Link>
                        </div>
                    </section>

                    <!-- Latest News for Tablet & Mobile (Non-lg) -->
                    <section v-if="latest && latest.length > 0" class="lg:hidden mt-10">
                        <div class="border-b-2 border-gray-900 pb-2 mb-5">
                            <h2 class="font-black text-xl tracking-tight text-gray-900 uppercase">Berita Terbaru</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Berita terhangat dari seluruh unit Yayasan Namira</p>
                        </div>
                        <div class="flex gap-4 overflow-x-auto no-scrollbar pb-4 -mx-2 px-2">
                            <Link 
                                v-for="item in latest" 
                                :key="item.id"
                                :href="route('news.show', item.slug)"
                                class="group bg-white rounded-xl overflow-hidden border border-gray-100 hover:shadow-md transition duration-300 flex flex-col shrink-0 w-[260px]"
                            >
                                <div class="h-28 overflow-hidden shrink-0">
                                    <img 
                                        :src="item.image_path ? '/' + item.image_path : '/images/landing/banner-1.jpg'"
                                        :alt="item.title"
                                        class="w-full h-full object-cover group-hover:scale-[1.04] transition duration-500"
                                    />
                                </div>
                                <div class="p-3.5 flex-grow flex flex-col justify-between">
                                    <div>
                                        <span class="text-[9px] font-bold tracking-wider uppercase text-[#064e3b] bg-emerald-50 px-1.5 py-0.5 rounded">
                                            {{ item.unit?.name || 'Yayasan' }}
                                        </span>
                                        <h3 class="text-xs font-bold text-gray-900 line-clamp-2 leading-snug group-hover:text-[#064e3b] transition mt-1.5">
                                            {{ item.title }}
                                        </h3>
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-2 font-medium">{{ formatDateShort(item.published_at) }}</p>
                                </div>
                            </Link>
                        </div>
                    </section>
                </div>

                <!-- ════════════════════════ SIDEBAR (Latest) ════ -->
                <aside class="hidden lg:block">
                    <div class="sticky top-24 space-y-8">
                        <!-- Search Bar -->
                        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                            <h3 class="font-extrabold text-xs tracking-wider text-gray-400 uppercase mb-3">Cari Berita</h3>
                            <form @submit.prevent="handleSearch" class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="h-4.5 w-4.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.604 10.604z" />
                                    </svg>
                                </span>
                                <input 
                                    type="text" 
                                    v-model="searchQuery"
                                    placeholder="Ketik kata kunci..." 
                                    class="w-full pl-10 pr-4 py-2.5 text-xs bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#064e3b] focus:border-transparent transition-all font-semibold"
                                />
                            </form>
                        </div>

                        <!-- Latest News -->
                        <div v-if="latest && latest.length > 0">
                            <div class="border-b-2 border-gray-900 pb-2 mb-4">
                                <h3 class="font-extrabold text-base tracking-tight text-gray-900 uppercase">Berita Terbaru</h3>
                            </div>
                            <div class="flex flex-col gap-0">
                                <div v-for="item in latest" :key="item.id" class="group border-b border-gray-100 last:border-0">
                                    <Link :href="route('news.show', item.slug)" class="flex gap-3 py-3.5 hover:bg-gray-50 rounded-lg px-1 transition-colors">
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
                                            <h4 class="text-xs font-extrabold text-gray-900 leading-snug group-hover:text-[#064e3b] transition mt-1 line-clamp-2">
                                                {{ item.title }}
                                            </h4>
                                            <p class="text-[10px] text-gray-400 mt-1">{{ formatDateShort(item.published_at) }}</p>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                            <Link 
                                href="/berita"
                                class="mt-4 flex items-center justify-center gap-1.5 w-full py-2.5 border border-gray-200 rounded-xl text-xs font-bold uppercase tracking-wide text-gray-600 hover:border-[#064e3b] hover:text-[#064e3b] transition"
                            >
                                Lihat Semua Berita <ArrowRightIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                            </Link>
                        </div>

                        <!-- Back to Portal -->
                        <div class="bg-[#064e3b] rounded-2xl p-5 text-center">
                            <p class="text-[#fbbf24] font-black text-xs uppercase tracking-widest mb-1">Namira News</p>
                            <p class="text-white/70 text-xs mb-4">Portal berita resmi Yayasan Namira</p>
                            <Link 
                                href="/berita"
                                class="block w-full bg-[#fbbf24] text-[#064e3b] font-black text-xs uppercase tracking-wider py-2.5 rounded-xl hover:bg-yellow-400 transition"
                            >
                                Jelajahi Semua Berita
                            </Link>
                        </div>
                    </div>
                </aside>

            </div><!-- end grid -->
        </main>

        <!-- Back To Top Button -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <button 
                v-if="showBackToTop"
                @click="scrollToTop"
                class="fixed bottom-20 right-5 z-40 bg-[#064e3b] text-[#fbbf24] hover:bg-[#053c2d] hover:text-white p-3 rounded-full shadow-xl transition-all duration-300 transform hover:scale-110 focus:outline-none cursor-pointer flex items-center justify-center border border-[#053c2d]"
                aria-label="Kembali ke atas"
            >
                <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                </svg>
            </button>
        </transition>

        <!-- FLOATING SHARE (Mobile) -->
        <div class="fixed bottom-5 right-5 z-40 lg:hidden flex flex-col gap-2 items-end">
            <div class="flex gap-2">
                <button @click="shareWhatsApp" class="w-10 h-10 rounded-full shadow-lg flex items-center justify-center transition hover:scale-110 cursor-pointer" style="background:#25D366;color:white;">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </button>
                <button @click="shareLinkedIn" class="w-10 h-10 rounded-full shadow-lg flex items-center justify-center transition hover:scale-110 cursor-pointer" style="background:#0A66C2;color:white;">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.779-1.75-1.75s.784-1.75 1.75-1.75 1.75.779 1.75 1.75-.784 1.75-1.75 1.75zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                </button>
                <button @click="copyLink" class="w-10 h-10 rounded-full shadow-lg flex items-center justify-center transition hover:scale-110 cursor-pointer" :class="copied ? 'bg-[#064e3b]' : 'bg-white border border-gray-200'">
                    <CheckIcon v-if="copied" class="w-5 h-5 text-white" />
                    <LinkIcon v-else class="w-4 h-4 text-gray-600" />
                </button>
            </div>
        </div>

    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Quill editor content styles */
:deep(.ql-editor) {
    padding: 0;
    font-size: 1rem;
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
    margin-top: 2rem;
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
