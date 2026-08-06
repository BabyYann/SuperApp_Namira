<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { 
    BriefcaseIcon, SparklesIcon, BuildingOfficeIcon, ClockIcon, 
    AcademicCapIcon, MagnifyingGlassIcon, FunnelIcon, ArrowRightIcon,
    CheckCircleIcon, UserGroupIcon, MapPinIcon
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

const search = ref(props.filters.search || '');
const selectedUnit = ref(props.filters.unit || 'all');
const selectedCategory = ref(props.filters.category || '');

const applyFilters = () => {
    router.get(route('careers.index'), {
        search: search.value,
        unit: selectedUnit.value,
        category: selectedCategory.value,
    }, { preserveState: true, replace: true });
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
    <Head title="Portal Karir & Rekrutmen - Yayasan Namira" />

    <div class="min-h-screen bg-slate-50 text-slate-800 font-sans selection:bg-teal-500 selection:text-white flex flex-col">
        <!-- Top Navbar -->
        <nav class="bg-white/90 backdrop-blur-md sticky top-0 z-40 border-b border-slate-100 shadow-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-3">
                    <div class="bg-white p-1 rounded-xl shadow-xs border border-slate-100 flex items-center justify-center shrink-0 w-10 h-10 overflow-hidden">
                        <img :src="$page.props.app_settings?.app_logo || '/images/landing/logo-yayasan.webp'" alt="Logo Namira" class="w-full h-full object-contain" />
                    </div>
                    <div class="flex items-baseline gap-1.5 select-none whitespace-nowrap">
                        <span class="font-namira text-2xl text-[#1a4373] tracking-tight lowercase text-stroke-white drop-shadow-xs leading-none">namira</span>
                        <span class="font-school text-base text-slate-500 tracking-wider uppercase text-stroke-white-sm font-extrabold leading-none">SCHOOL</span>
                    </div>
                </Link>

                <div class="flex items-center gap-6 text-sm font-bold">
                    <Link href="/" class="text-slate-600 hover:text-namira-teal transition-colors">Beranda</Link>
                    <Link :href="route('news.index')" class="text-slate-600 hover:text-namira-teal transition-colors">Berita</Link>
                    <Link :href="route('events.index')" class="text-slate-600 hover:text-namira-teal transition-colors">Kegiatan</Link>
                    <Link :href="route('careers.index')" class="text-namira-teal font-extrabold flex items-center gap-1">
                        <span>Karir</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                    </Link>
                    <Link :href="route('login')" class="px-5 py-2.5 bg-gradient-to-r from-slate-900 to-slate-800 text-white rounded-2xl shadow-xs hover:shadow-md hover:scale-105 transition-all text-xs">
                        Masuk Portal
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-1 space-y-12 pb-16">
            <!-- Hero Banner Section -->
            <div class="relative bg-slate-900 text-white overflow-hidden py-16 sm:py-24">
                <div class="absolute inset-0 bg-gradient-to-r from-teal-900/90 to-slate-900/95 z-10"></div>
                <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-teal-500/20 blur-3xl z-0"></div>
                
                <div class="relative z-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
                    <h1 class="text-3xl sm:text-5xl font-black text-white leading-tight tracking-tight max-w-4xl mx-auto">
                        Bergabung Bersama Yayasan Namira <br class="hidden sm:inline" />
                        <span class="bg-gradient-to-r from-teal-300 to-emerald-400 bg-clip-text text-transparent">Wujudkan Pendidikan Islami & Modern</span>
                    </h1>

                    <p class="text-sm sm:text-base text-slate-300 max-w-2xl mx-auto leading-relaxed">
                        Kami mengundang para talenta profesional, pendidik berdedikasi, dan staf operasional terbaik untuk tumbuh dan berkontribusi membina generasi unggul di Yayasan Namira.
                    </p>
                </div>
            </div>

            <!-- Filter & Vacancy List Section -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                <!-- Search & Filters Bar -->
                <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm space-y-4 -mt-12 relative z-30">
                    <div class="flex items-center gap-2 text-xs font-extrabold text-slate-800 uppercase tracking-wider">
                        <FunnelIcon class="w-4 h-4 text-namira-teal" />
                        <span>Filter Lowongan Pekerjaan</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="relative">
                            <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" />
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

                <!-- Vacancies Grid -->
                <div>
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-extrabold text-slate-900">Lowongan Kerja Dibuka</h2>
                        <span class="text-xs font-bold text-slate-500">Menampilkan {{ vacancies.total }} Lowongan</span>
                    </div>

                    <!-- Empty State -->
                    <div v-if="!vacancies.data || vacancies.data.length === 0" class="bg-white rounded-3xl border border-dashed border-slate-200 p-16 text-center space-y-3">
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
                            class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs hover:shadow-lg hover:border-namira-teal transition-all flex flex-col justify-between space-y-5"
                        >
                            <div class="space-y-3">
                                <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-3">
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

                                <div class="space-y-1 text-xs text-slate-500">
                                    <div class="flex items-center gap-2 font-medium">
                                        <BuildingOfficeIcon class="w-4 h-4 text-slate-400 shrink-0" />
                                        <span>Kategori: <strong class="text-slate-800">{{ vacancy.category_label }}</strong></span>
                                    </div>
                                    <div class="flex items-center gap-2 font-medium">
                                        <UserGroupIcon class="w-4 h-4 text-slate-400 shrink-0" />
                                        <span>Kuota Diterima: <strong class="text-slate-800">{{ vacancy.quota }} Orang</strong></span>
                                    </div>
                                    <div class="flex items-center gap-2 font-mono">
                                        <ClockIcon class="w-4 h-4 text-amber-500 shrink-0" />
                                        <span>Batas Akhir: <strong class="text-amber-700 font-extrabold">{{ formatDate(vacancy.deadline) }}</strong></span>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-2">
                                <Link 
                                    :href="route('careers.show', vacancy.slug)"
                                    class="w-full py-3 bg-slate-900 hover:bg-namira-teal text-white rounded-2xl font-bold text-xs shadow-xs transition-all flex items-center justify-center gap-2 group cursor-pointer"
                                >
                                    <span>Lihat Detail & Lamar</span>
                                    <ArrowRightIcon class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="vacancies.links && vacancies.links.length > 3" class="flex justify-center pt-8">
                        <div class="flex items-center gap-1 bg-white p-2 rounded-2xl border border-slate-200 shadow-xs">
                            <component
                                v-for="(link, key) in vacancies.links"
                                :key="key"
                                :is="link.url ? 'Link' : 'span'"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all"
                                :class="{
                                    'bg-namira-teal text-white shadow-xs': link.active,
                                    'text-slate-600 hover:bg-slate-100': link.url && !link.active,
                                    'text-slate-300': !link.url
                                }"
                            />
                        </div>
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
                        Lembaga Pendidikan Islam Terpadu & Modern dari Yayasan Namira (PAUD, Daycare, SD, SMP, SMA).
                    </p>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-white mb-3">Tautan Cepat</h4>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li><Link href="/" class="hover:text-white transition-colors">Beranda</Link></li>
                        <li><Link :href="route('news.index')" class="hover:text-white transition-colors">Berita & Informasi</Link></li>
                        <li><Link :href="route('events.index')" class="hover:text-white transition-colors">Kegiatan Sekolah</Link></li>
                        <li><Link :href="route('careers.index')" class="hover:text-white transition-colors">Portal Karir & Rekrutmen</Link></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-white mb-3">Unit Pendidikan</h4>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li>SD Namira</li>
                        <li>SMP Namira</li>
                        <li>SMA Namira</li>
                        <li>Pavlov Daycare & PAUD</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-white mb-3">Kontak Rekrutmen</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Email: hr@namiraschool.com<br />
                        Alamat: Jl. Raya Namira, Kraksaan - Probolinggo
                    </p>
                </div>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-xs text-slate-500 mt-8 pt-8 border-t border-slate-800">
                © {{ new Date().getFullYear() }} Yayasan Namira School. All rights reserved.
            </div>
        </footer>
    </div>
</template>
