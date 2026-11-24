<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import { ref, watch } from 'vue';
import {
    BriefcaseIcon,
    BuildingOfficeIcon,
    ClockIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowRightIcon,
    UserGroupIcon,
    MagnifyingGlassPlusIcon,
    XMarkIcon,
    MapPinIcon,
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    vacancies: Object,
    units: Array,
    filters: Object,
    categories: Object,
});

const search = ref(props.filters?.search || '');
const selectedUnit = ref(props.filters?.unit || 'all');
const selectedCategory = ref(props.filters?.category || '');
const lightboxPoster = ref(null);

const applyFilters = () => {
    router.get(
        route('careers.index'),
        {
            search: search.value || undefined,
            unit: selectedUnit.value === 'all' ? undefined : selectedUnit.value,
            category: selectedCategory.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

const formatDate = (dateStr) => {
    if (!dateStr) return 'Tanpa Batas Waktu';
    return dayjs(dateStr).format('DD MMMM YYYY');
};
</script>

<template>
    <Head title="Portal Karir & Rekrutmen - Namira School" />

    <div class="min-h-screen bg-slate-50 text-slate-800 font-sans selection:bg-teal-500 selection:text-white flex flex-col">
        <!-- Top Navbar (Mobile-Friendly) -->
        <nav class="bg-white/95 backdrop-blur-md sticky top-0 z-40 border-b border-slate-100 shadow-2xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between gap-3">
                <Link href="/" class="flex items-center gap-2.5 shrink-0">
                    <div class="bg-white p-1 rounded-xl shadow-xs border border-slate-100 flex items-center justify-center shrink-0 w-9 h-9 sm:w-10 sm:h-10 overflow-hidden">
                        <img :src="$page.props.app_settings?.app_logo || '/images/landing/logo-yayasan.webp'" alt="Logo Namira" class="w-full h-full object-contain" />
                    </div>
                    <div class="flex items-baseline gap-1 select-none whitespace-nowrap">
                        <span class="font-namira text-xl sm:text-2xl text-[#1a4373] tracking-tight lowercase leading-none">namira</span>
                        <span class="font-school text-xs sm:text-base text-slate-500 tracking-wider uppercase font-extrabold leading-none">SCHOOL</span>
                    </div>
                </Link>

                <div class="flex items-center gap-3 sm:gap-6 text-xs sm:text-sm font-bold">
                    <Link href="/" class="hidden md:inline text-slate-600 hover:text-namira-teal transition-colors">Beranda</Link>
                    <Link :href="route('news.index')" class="hidden md:inline text-slate-600 hover:text-namira-teal transition-colors">Berita</Link>
                    <Link :href="route('events.index')" class="hidden md:inline text-slate-600 hover:text-namira-teal transition-colors">Kegiatan</Link>
                    <Link :href="route('careers.index')" class="text-namira-teal font-extrabold flex items-center gap-1">
                        <span>Karir</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                    </Link>
                    <Link :href="route('login')" class="px-3.5 sm:px-5 py-2 sm:py-2.5 bg-gradient-to-r from-slate-900 to-slate-800 text-white rounded-2xl shadow-xs hover:shadow-md transition-all text-[11px] sm:text-xs whitespace-nowrap">
                        Masuk Portal
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-1 space-y-10 pb-16">
            <!-- Hero Banner Section -->
            <div class="relative bg-slate-900 text-white overflow-hidden py-14 sm:py-20">
                <div class="absolute inset-0 bg-gradient-to-r from-teal-900/90 to-slate-900/95 z-10"></div>
                <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-teal-500/20 blur-3xl z-0"></div>

                <div class="relative z-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4">
                    <h1 class="text-2xl sm:text-5xl font-black text-white leading-tight tracking-tight max-w-4xl mx-auto">
                        Bergabung Bersama Yayasan Namira <br class="hidden sm:inline" />
                        <span class="bg-gradient-to-r from-teal-300 to-emerald-400 bg-clip-text text-transparent">Wujudkan Pendidikan Islami &amp; Modern</span>
                    </h1>

                    <p class="text-xs sm:text-base text-slate-300 max-w-2xl mx-auto leading-relaxed">
                        Kami mengundang para talenta profesional, pendidik berdedikasi, dan staf operasional terbaik untuk tumbuh dan berkontribusi membina generasi unggul di Yayasan Namira.
                    </p>
                </div>
            </div>

            <!-- Filter & Vacancy List Section -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                <!-- Search & Filters Bar -->
                <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200 shadow-sm space-y-3.5 -mt-12 relative z-30">
                    <div class="flex items-center gap-2 text-xs font-extrabold text-slate-800 uppercase tracking-wider">
                        <FunnelIcon class="w-4 h-4 text-namira-teal" />
                        <span>Filter Lowongan Pekerjaan</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="relative">
                            <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari posisi, kata kunci..."
                                class="w-full pl-10 rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                            />
                        </div>

                        <div>
                            <select
                                v-model="selectedUnit"
                                @change="applyFilters"
                                class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                            >
                                <option value="all">Semua Unit Sekolah / Yayasan</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                    {{ unit.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <select
                                v-model="selectedCategory"
                                @change="applyFilters"
                                class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                            >
                                <option value="">Semua Kategori Posisi</option>
                                <option v-for="(label, key) in categories" :key="key" :value="key">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Flash Warning -->
                <div v-if="$page.props.flash?.warning || $page.props.flash?.error" class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-bold">
                    {{ $page.props.flash.warning || $page.props.flash.error }}
                </div>

                <!-- Vacancies Grid -->
                <div>
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg sm:text-xl font-extrabold text-slate-900">Lowongan Kerja Dibuka</h2>
                        <span class="text-xs font-bold text-slate-500">Menampilkan {{ vacancies.total }} Lowongan</span>
                    </div>

                    <!-- Empty State -->
                    <div v-if="!vacancies.data || vacancies.data.length === 0" class="bg-white rounded-3xl border border-dashed border-slate-200 p-12 sm:p-16 text-center space-y-3">
                        <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                            <BriefcaseIcon class="w-8 h-8" />
                        </div>
                        <h3 class="font-extrabold text-slate-800 text-base">Belum Ada Lowongan Dibuka</h3>
                        <p class="text-xs text-slate-500 max-w-md mx-auto">Saat ini belum ada lowongan kerja yang dibuka untuk filter ini. Silakan cek secara berkala.</p>
                    </div>

                    <!-- Grid Cards -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div
                            v-for="vacancy in vacancies.data"
                            :key="vacancy.id"
                            class="bg-white rounded-3xl border border-slate-200 shadow-xs hover:shadow-lg hover:border-namira-teal transition-all flex flex-col justify-between overflow-hidden"
                        >
                            <div>
                                <!-- Poster Showcase Banner (Handles Portrait 2:3 / 9:16 Flyers Cleanly Without Cropping Text!) -->
                                <div
                                    v-if="vacancy.poster_url"
                                    class="relative h-64 bg-slate-900 overflow-hidden flex items-center justify-center p-2.5 group"
                                >
                                    <img
                                        :src="vacancy.poster_url"
                                        alt=""
                                        aria-hidden="true"
                                        class="absolute inset-0 w-full h-full object-cover blur-xl opacity-35 scale-110 pointer-events-none"
                                    />
                                    <img
                                        :src="vacancy.poster_url"
                                        :alt="`Poster ${vacancy.title}`"
                                        class="relative z-10 max-h-full max-w-full object-contain rounded-xl shadow-md transition-transform duration-300 group-hover:scale-[1.02]"
                                    />
                                    <button
                                        type="button"
                                        @click="lightboxPoster = { url: vacancy.poster_url, title: vacancy.title }"
                                        class="absolute bottom-3 right-3 z-20 px-2.5 py-1.5 rounded-xl bg-slate-900/80 hover:bg-slate-900 text-white text-[10px] font-bold inline-flex items-center gap-1 backdrop-blur-xs transition cursor-pointer"
                                    >
                                        <MagnifyingGlassPlusIcon class="w-3.5 h-3.5" />
                                        <span>Perbesar</span>
                                    </button>
                                </div>

                                <div class="p-6 space-y-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                                        <span class="px-3 py-1 bg-teal-50 text-teal-700 border border-teal-100 rounded-full font-extrabold text-[10px] uppercase tracking-wide">
                                            {{ vacancy.unit?.name || 'Yayasan Namira' }}
                                        </span>
                                        <span class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-full font-bold text-[10px]">
                                            {{ vacancy.type_label }}
                                        </span>
                                    </div>

                                    <h3 class="font-black text-slate-900 text-lg leading-snug hover:text-namira-teal transition-colors">
                                        <Link :href="route('careers.show', vacancy.slug)">
                                            {{ vacancy.title }}
                                        </Link>
                                    </h3>

                                    <div class="space-y-1.5 text-xs text-slate-500">
                                        <div class="flex items-center gap-2 font-medium">
                                            <BuildingOfficeIcon class="w-4 h-4 text-slate-400 shrink-0" />
                                            <span>Kategori: <strong class="text-slate-800">{{ vacancy.category_label }}</strong></span>
                                        </div>
                                        <div class="flex items-center gap-2 font-medium">
                                            <UserGroupIcon class="w-4 h-4 text-slate-400 shrink-0" />
                                            <span>Kuota Kebutuhan: <strong class="text-slate-800">{{ vacancy.quota }} Orang</strong></span>
                                        </div>
                                        <div v-if="vacancy.placement_address" class="flex items-center gap-2 font-medium">
                                            <MapPinIcon class="w-4 h-4 text-teal-600 shrink-0" />
                                            <span class="truncate">{{ vacancy.placement_address }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 font-mono">
                                            <ClockIcon class="w-4 h-4 text-amber-500 shrink-0" />
                                            <span>Batas Akhir: <strong class="text-amber-700 font-extrabold">{{ formatDate(vacancy.deadline) }}</strong></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="px-6 pb-6 pt-1">
                                <Link
                                    :href="route('careers.show', vacancy.slug)"
                                    class="w-full py-3 bg-slate-900 hover:bg-namira-teal text-white rounded-2xl font-bold text-xs shadow-xs transition-all flex items-center justify-center gap-2 group cursor-pointer"
                                >
                                    <span>Lihat Detail &amp; Lamar Online</span>
                                    <ArrowRightIcon class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="vacancies.links && vacancies.links.length > 3" class="pt-8">
                        <Pagination :links="vacancies.links" />
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-slate-900 text-white border-t border-slate-800 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="space-y-3">
                    <span class="font-black text-xl text-white tracking-tight">NAMIRA <span class="text-namira-teal">SCHOOL</span></span>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Lembaga Pendidikan Islam Terpadu &amp; Modern dari Yayasan Namira (PAUD, Daycare, TK, SD, SMP).
                    </p>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-white mb-3">Tautan Cepat</h4>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li><Link href="/" class="hover:text-white transition-colors">Beranda</Link></li>
                        <li><Link :href="route('news.index')" class="hover:text-white transition-colors">Berita &amp; Informasi</Link></li>
                        <li><Link :href="route('events.index')" class="hover:text-white transition-colors">Kegiatan Sekolah</Link></li>
                        <li><Link :href="route('careers.index')" class="hover:text-white transition-colors">Portal Karir &amp; Rekrutmen</Link></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-white mb-3">Unit Pendidikan</h4>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li>SD Namira Kraksaan &amp; Kota</li>
                        <li>SMP Namira</li>
                        <li>TK &amp; KB Namira</li>
                        <li>Pavlov Daycare &amp; PAUD</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-white mb-3">Kontak Rekrutmen</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Email: hr@namiraschool.com<br />
                        Alamat: Kraksaan &amp; Kota Probolinggo, Jawa Timur
                    </p>
                </div>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-xs text-slate-500 mt-8 pt-8 border-t border-slate-800">
                © {{ new Date().getFullYear() }} Yayasan Namira School. All rights reserved.
            </div>
        </footer>

        <!-- Poster Lightbox -->
        <Teleport to="body">
            <div
                v-if="lightboxPoster"
                class="fixed inset-0 z-[120] bg-slate-950/90 backdrop-blur-sm flex flex-col items-center justify-center p-4"
                @click.self="lightboxPoster = null"
            >
                <div class="w-full max-w-2xl flex items-center justify-between text-white mb-3">
                    <h4 class="font-extrabold text-sm truncate pr-4">{{ lightboxPoster.title }}</h4>
                    <button
                        type="button"
                        @click="lightboxPoster = null"
                        class="p-2 rounded-full bg-white/15 hover:bg-white/25 text-white transition cursor-pointer"
                    >
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
                <img
                    :src="lightboxPoster.url"
                    :alt="lightboxPoster.title"
                    class="max-h-[82vh] max-w-full object-contain rounded-2xl shadow-2xl border border-white/15"
                />
            </div>
        </Teleport>
    </div>
</template>
