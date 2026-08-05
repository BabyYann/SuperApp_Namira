<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import {
    SparklesIcon, FunnelIcon, MagnifyingGlassIcon, PhotoIcon,
    CalendarIcon, UserIcon, ClockIcon, BuildingOfficeIcon,
    XMarkIcon, FolderIcon, CheckCircleIcon, Squares2X2Icon,
    Bars3Icon, QueueListIcon, EyeIcon, DocumentTextIcon,
    UserGroupIcon, ClipboardDocumentCheckIcon
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    feedItems: Object,
    units: Array,
    filters: Object,
    categories: Object,
    summaryStats: Object,
});

const search = ref(props.filters.search || '');
const selectedUnit = ref(props.filters.unit_id || 'all');
const selectedCategory = ref(props.filters.category || '');
const selectedDate = ref(props.filters.date || '');

// View Mode: 'compact' (Daftar Ringkas), 'grid' (Grid 2/3 Kolom), 'feed' (Feed Linimasa)
const viewMode = ref(localStorage.getItem('namira_feed_view_mode') || 'compact');

const setViewMode = (mode) => {
    viewMode.value = mode;
    localStorage.setItem('namira_feed_view_mode', mode);
};

const applyFilters = () => {
    router.get(route('yayasan.activity-logs.feed'), {
        search: search.value,
        unit_id: selectedUnit.value,
        category: selectedCategory.value,
        date: selectedDate.value,
    }, { preserveState: true, replace: true });
};

const resetFilters = () => {
    search.value = '';
    selectedUnit.value = 'all';
    selectedCategory.value = '';
    selectedDate.value = '';
    applyFilters();
};

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

// Lightbox & Detail Modal
const activePhotoUrl = ref(null);
const selectedLogDetail = ref(null);

const openPhotoLightbox = (url) => {
    activePhotoUrl.value = url;
};

const openLogDetail = (log) => {
    selectedLogDetail.value = log;
};

const categoryBadgeClass = (catKey) => {
    switch (catKey) {
        case 'kebersihan': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'keamanan': return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'pemeliharaan': return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'layanan_admin': return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'piket': return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        case 'tugas_tambahan': return 'bg-teal-50 text-teal-700 border-teal-200';
        default: return 'bg-slate-100 text-slate-700 border-slate-200';
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const cleanDate = dateStr.includes('T') ? dateStr.split('T')[0] : dateStr;
    return dayjs(cleanDate).format('DD MMM YYYY');
};

const formatTime = (timeStr) => {
    if (!timeStr) return '';
    return timeStr.substring(0, 5);
};
</script>

<template>
    <Head title="Executive Feed - Giat Tugas Pegawai" />

    <AuthenticatedLayout>
        <div class="py-6 max-w-7xl mx-auto space-y-6 px-4 sm:px-6">
            
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="font-extrabold text-2xl text-gray-900 leading-tight flex items-center gap-2.5">
                        <SparklesIcon class="w-7 h-7 text-namira-teal" />
                        <span>Executive Feed — Giat Tugas Pegawai</span>
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Linimasa pemantauan kegiatan operasional & dokumentasi harian SDM Yayasan Namira.
                    </p>
                </div>
                <Link :href="route('yayasan.dashboard')" class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-namira-teal transition-colors">
                    Kembali ke Dashboard
                </Link>
            </div>

            <!-- Top Executive Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-3xl p-5 border border-gray-150 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                        <ClipboardDocumentCheckIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Giat Dicatat</div>
                        <div class="text-2xl font-black text-gray-900 mt-0.5">{{ summaryStats?.total_logs || 0 }} <span class="text-xs font-semibold text-gray-500">Kegiatan</span></div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-5 border border-gray-150 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <UserGroupIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">SDM Aktif Bergiat</div>
                        <div class="text-2xl font-black text-gray-900 mt-0.5">{{ summaryStats?.total_sdm || 0 }} <span class="text-xs font-semibold text-gray-500">Pegawai</span></div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-5 border border-gray-150 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <PhotoIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">Dokumentasi Foto</div>
                        <div class="text-2xl font-black text-gray-900 mt-0.5">{{ summaryStats?.total_photos || 0 }} <span class="text-xs font-semibold text-gray-500">Bukti Foto</span></div>
                    </div>
                </div>
            </div>

            <!-- Filter Controls & View Mode Toggle -->
            <div class="bg-white rounded-3xl border border-gray-150 p-5 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-2 text-xs font-extrabold text-gray-800 uppercase tracking-wider">
                        <FunnelIcon class="w-4 h-4 text-namira-teal" />
                        <span>Filter & Pencarian Linimasa</span>
                    </div>

                    <!-- View Mode Selector Buttons -->
                    <div class="flex items-center bg-gray-100 p-1 rounded-2xl shrink-0 self-start sm:self-auto">
                        <button
                            @click="setViewMode('compact')"
                            :class="[
                                'px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer',
                                viewMode === 'compact' ? 'bg-white text-gray-900 shadow-xs font-extrabold' : 'text-gray-500 hover:text-gray-800'
                            ]"
                            title="Tampilan Daftar Ringkas (Tabel Compact)"
                        >
                            <QueueListIcon class="w-4 h-4" />
                            <span class="hidden md:inline">Ringkas (Tabel)</span>
                        </button>

                        <button
                            @click="setViewMode('grid')"
                            :class="[
                                'px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer',
                                viewMode === 'grid' ? 'bg-white text-gray-900 shadow-xs font-extrabold' : 'text-gray-500 hover:text-gray-800'
                            ]"
                            title="Tampilan Grid Cards (2/3 Kolom)"
                        >
                            <Squares2X2Icon class="w-4 h-4" />
                            <span class="hidden md:inline">Grid Card</span>
                        </button>

                        <button
                            @click="setViewMode('feed')"
                            :class="[
                                'px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer',
                                viewMode === 'feed' ? 'bg-white text-gray-900 shadow-xs font-extrabold' : 'text-gray-500 hover:text-gray-800'
                            ]"
                            title="Tampilan Feed Linimasa (Single Column Besar)"
                        >
                            <Bars3Icon class="w-4 h-4" />
                            <span class="hidden md:inline">Feed Besar</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Search Input -->
                    <div class="relative">
                        <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 absolute left-3 top-3" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari nama pegawai, kegiatan..."
                            class="w-full pl-9 rounded-xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                        />
                    </div>

                    <!-- Unit Filter -->
                    <div>
                        <select
                            v-model="selectedUnit"
                            @change="applyFilters"
                            class="w-full rounded-xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                        >
                            <option value="all">Semua Unit Sekolah</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                {{ unit.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <select
                            v-model="selectedCategory"
                            @change="applyFilters"
                            class="w-full rounded-xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                        >
                            <option value="">Semua Kategori Giat</option>
                            <option v-for="(label, key) in categories" :key="key" :value="key">
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <!-- Date Filter -->
                    <div class="flex items-center gap-2">
                        <input
                            v-model="selectedDate"
                            type="date"
                            @change="applyFilters"
                            class="w-full rounded-xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                        />
                        <button
                            v-if="search || selectedUnit !== 'all' || selectedCategory || selectedDate"
                            @click="resetFilters"
                            class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl transition text-xs shrink-0 cursor-pointer"
                            title="Reset Filter"
                        >
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!feedItems.data || feedItems.data.length === 0" class="bg-white rounded-3xl border border-dashed border-gray-200 p-16 text-center space-y-3">
                <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                    <FolderIcon class="w-8 h-8" />
                </div>
                <h4 class="font-extrabold text-gray-800 text-base">Tidak Ada Giat Tugas Ditemukan</h4>
                <p class="text-xs text-gray-500 max-w-md mx-auto">Belum ada catatan giat tugas pegawai yang sesuai dengan filter pencarian di atas.</p>
            </div>

            <!-- VIEW MODE 1: COMPACT TABLE ROW (Ideal for Fast Monitoring) -->
            <div v-else-if="viewMode === 'compact'" class="bg-white rounded-3xl border border-gray-150 overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left text-gray-600">
                        <thead class="text-xs text-gray-700 uppercase bg-slate-50 border-b border-gray-150 font-bold">
                            <tr>
                                <th class="px-5 py-3.5">Pegawai & Unit</th>
                                <th class="px-5 py-3.5">Tanggal & Waktu</th>
                                <th class="px-5 py-3.5">Kategori</th>
                                <th class="px-5 py-3.5">Judul Kegiatan & Ringkasan</th>
                                <th class="px-5 py-3.5 text-center">Foto Bukti</th>
                                <th class="px-5 py-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="item in feedItems.data" :key="item.id" class="hover:bg-slate-50/70 transition-colors">
                                <!-- Employee & Unit -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <img v-if="item.user?.profile_photo_url" :src="item.user.profile_photo_url" class="w-8 h-8 rounded-full object-cover border border-gray-100 shadow-xs" />
                                        <div v-else class="w-8 h-8 rounded-full bg-teal-50 border border-teal-100 flex items-center justify-center text-teal-700 font-bold text-xs">
                                            {{ item.user?.name?.charAt(0) || 'P' }}
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-gray-900 text-xs">{{ item.user?.name || 'Pegawai' }}</div>
                                            <div class="text-[10px] text-teal-700 font-semibold">{{ item.unit?.name || '-' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Date & Time -->
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="font-bold text-gray-800">{{ formatDate(item.activity_date) }}</div>
                                    <div class="text-[10px] text-gray-400 font-mono">{{ formatTime(item.activity_time) }} WIB</div>
                                </td>

                                <!-- Category Badge -->
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border" :class="categoryBadgeClass(item.category)">
                                        {{ categories[item.category] || item.category }}
                                    </span>
                                </td>

                                <!-- Title & Summary -->
                                <td class="px-5 py-3.5">
                                    <div class="font-extrabold text-gray-900 text-xs line-clamp-1">{{ item.title }}</div>
                                    <div v-if="item.description" class="text-[11px] text-gray-500 line-clamp-1 mt-0.5">{{ item.description }}</div>
                                </td>

                                <!-- Photo Thumbnail -->
                                <td class="px-5 py-3.5 text-center">
                                    <button 
                                        v-if="item.photo_path" 
                                        @click="openPhotoLightbox('/' + item.photo_path)"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-teal-50 text-teal-700 border border-teal-200 rounded-xl hover:bg-teal-100 transition-colors font-bold text-[10px] shadow-xs cursor-pointer"
                                    >
                                        <PhotoIcon class="w-3.5 h-3.5 text-teal-600" />
                                        <span>Foto</span>
                                    </button>
                                    <span v-else class="text-gray-300 font-mono text-[10px]">-</span>
                                </td>

                                <!-- Action (Detail Modal) -->
                                <td class="px-5 py-3.5 text-center">
                                    <button 
                                        @click="openLogDetail(item)"
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-colors cursor-pointer inline-flex items-center justify-center"
                                        title="Lihat Detail Lengkap"
                                    >
                                        <EyeIcon class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW MODE 2: GRID CARDS (2/3 Column Layout) -->
            <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="item in feedItems.data"
                    :key="item.id"
                    class="bg-white rounded-3xl border border-gray-150 p-5 shadow-xs hover:shadow-md transition-all flex flex-col justify-between space-y-4"
                >
                    <div class="space-y-3">
                        <!-- Top Bar: Employee & Category -->
                        <div class="flex items-start justify-between gap-2 border-b border-gray-100 pb-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <img v-if="item.user?.profile_photo_url" :src="item.user.profile_photo_url" class="w-8 h-8 rounded-full object-cover border border-gray-100 shrink-0" />
                                <div v-else class="w-8 h-8 rounded-full bg-teal-50 border border-teal-100 flex items-center justify-center text-teal-700 font-bold text-xs shrink-0">
                                    {{ item.user?.name?.charAt(0) || 'P' }}
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-extrabold text-gray-900 text-xs truncate">{{ item.user?.name || 'Pegawai' }}</h4>
                                    <div class="text-[10px] text-teal-700 font-semibold truncate">{{ item.unit?.name || '-' }}</div>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase border shrink-0" :class="categoryBadgeClass(item.category)">
                                {{ categories[item.category] || item.category }}
                            </span>
                        </div>

                        <!-- Date & Time -->
                        <div class="flex items-center gap-1.5 text-[10px] text-gray-400 font-mono">
                            <ClockIcon class="w-3.5 h-3.5 text-gray-400" />
                            <span>{{ formatDate(item.activity_date) }} • {{ formatTime(item.activity_time) }} WIB</span>
                        </div>

                        <!-- Title & Description -->
                        <div class="space-y-1">
                            <h3 class="font-extrabold text-gray-900 text-sm leading-snug line-clamp-2">{{ item.title }}</h3>
                            <p v-if="item.description" class="text-xs text-gray-500 line-clamp-3 leading-relaxed">{{ item.description }}</p>
                        </div>
                    </div>

                    <!-- Photo Preview Footer -->
                    <div v-if="item.photo_path" class="pt-2">
                        <div
                            @click="openPhotoLightbox('/' + item.photo_path)"
                            class="relative h-40 rounded-2xl overflow-hidden border border-gray-200 group cursor-pointer bg-slate-50 shadow-xs"
                        >
                            <img :src="'/' + item.photo_path" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                            <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold gap-1">
                                <PhotoIcon class="w-4 h-4" />
                                <span>Lihat Foto</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW MODE 3: FEED LINIMASA (Single Large Column) -->
            <div v-else class="space-y-5 max-w-4xl mx-auto">
                <div
                    v-for="item in feedItems.data"
                    :key="item.id"
                    class="bg-white rounded-3xl border border-gray-150 shadow-xs p-6 hover:shadow-md transition-all space-y-4"
                >
                    <!-- Header -->
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-teal-50 border border-teal-100 flex items-center justify-center overflow-hidden shrink-0">
                                <img v-if="item.user?.profile_photo_url" :src="item.user.profile_photo_url" class="w-full h-full object-cover" />
                                <UserIcon v-else class="w-5 h-5 text-teal-600" />
                            </div>
                            <div>
                                <h4 class="font-extrabold text-gray-900 text-sm">{{ item.user?.name || 'Pegawai' }}</h4>
                                <div class="flex items-center gap-2 text-xs text-gray-400 mt-0.5">
                                    <span v-if="item.unit?.name" class="font-bold text-teal-700 bg-teal-50 px-2.5 py-0.5 rounded-full border border-teal-100">
                                        {{ item.unit.name }}
                                    </span>
                                    <span>•</span>
                                    <span class="flex items-center gap-1 font-mono">
                                        <ClockIcon class="w-3.5 h-3.5" />
                                        {{ formatDate(item.activity_date) }} {{ formatTime(item.activity_time) }} WIB
                                    </span>
                                </div>
                            </div>
                        </div>

                        <span class="px-3 py-1 rounded-full text-xs font-extrabold uppercase border" :class="categoryBadgeClass(item.category)">
                            {{ categories[item.category] || item.category }}
                        </span>
                    </div>

                    <!-- Content -->
                    <div class="space-y-1.5">
                        <h3 class="font-extrabold text-gray-900 text-base leading-snug">{{ item.title }}</h3>
                        <p v-if="item.description" class="text-xs text-gray-600 whitespace-pre-line leading-relaxed">{{ item.description }}</p>
                    </div>

                    <!-- Photo Preview -->
                    <div v-if="item.photo_path">
                        <div
                            @click="openPhotoLightbox('/' + item.photo_path)"
                            class="relative max-w-lg h-64 rounded-2xl overflow-hidden border border-gray-200 group cursor-pointer bg-slate-50 shadow-xs"
                        >
                            <img :src="'/' + item.photo_path" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                            <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold gap-1.5">
                                <PhotoIcon class="w-5 h-5" />
                                <span>Lihat Dokumentasi Bukti Foto</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination Bar -->
            <div v-if="feedItems.links && feedItems.links.length > 3" class="flex justify-center pt-4">
                <div class="flex items-center gap-1 bg-white p-2 rounded-2xl border border-gray-150 shadow-xs">
                    <component
                        v-for="(link, key) in feedItems.links"
                        :key="key"
                        :is="link.url ? 'Link' : 'span'"
                        :href="link.url"
                        v-html="link.label"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all"
                        :class="{
                            'bg-namira-teal text-white shadow-xs': link.active,
                            'text-gray-600 hover:bg-gray-100': link.url && !link.active,
                            'text-gray-300': !link.url
                        }"
                    />
                </div>
            </div>

        </div>

        <!-- LIGHTBOX PHOTO MODAL -->
        <Teleport to="body">
            <div v-if="activePhotoUrl" @click="activePhotoUrl = null" class="fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-md flex items-center justify-center p-4 cursor-pointer">
                <div class="relative max-w-3xl max-h-[85vh] overflow-hidden rounded-3xl shadow-2xl">
                    <img :src="activePhotoUrl" class="w-full h-full object-contain max-h-[85vh]" />
                    <button @click.stop="activePhotoUrl = null" class="absolute top-4 right-4 p-2 bg-black/60 text-white rounded-full hover:bg-black transition-colors cursor-pointer">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </Teleport>

        <!-- LOG DETAIL MODAL (For Compact Mode View) -->
        <Teleport to="body">
            <div v-if="selectedLogDetail" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div class="flex items-center gap-3">
                            <img v-if="selectedLogDetail.user?.profile_photo_url" :src="selectedLogDetail.user.profile_photo_url" class="w-9 h-9 rounded-full object-cover border border-gray-100" />
                            <div>
                                <h3 class="text-sm font-extrabold text-gray-900">{{ selectedLogDetail.user?.name || 'Pegawai' }}</h3>
                                <div class="text-[10px] text-teal-700 font-semibold">{{ selectedLogDetail.unit?.name || '-' }}</div>
                            </div>
                        </div>
                        <button @click="selectedLogDetail = null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border" :class="categoryBadgeClass(selectedLogDetail.category)">
                                {{ categories[selectedLogDetail.category] || selectedLogDetail.category }}
                            </span>
                            <div class="text-[11px] text-gray-400 font-mono">
                                {{ formatDate(selectedLogDetail.activity_date) }} • {{ formatTime(selectedLogDetail.activity_time) }} WIB
                            </div>
                        </div>

                        <div>
                            <h4 class="text-base font-extrabold text-gray-900">{{ selectedLogDetail.title }}</h4>
                            <p v-if="selectedLogDetail.description" class="text-xs text-gray-600 mt-2 whitespace-pre-line leading-relaxed bg-slate-50 p-3 rounded-2xl border border-gray-100">
                                {{ selectedLogDetail.description }}
                            </p>
                        </div>

                        <div v-if="selectedLogDetail.photo_path" class="pt-2">
                            <div class="text-xs font-bold text-gray-700 mb-2">Dokumentasi Bukti Foto:</div>
                            <img :src="'/' + selectedLogDetail.photo_path" class="w-full max-h-72 object-cover rounded-2xl border border-gray-200 shadow-xs cursor-pointer" @click="openPhotoLightbox('/' + selectedLogDetail.photo_path)" />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2 border-t border-gray-100">
                        <button @click="selectedLogDetail = null" class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors cursor-pointer">Tutup</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
