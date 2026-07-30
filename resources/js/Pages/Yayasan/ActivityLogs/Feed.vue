<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import {
    SparklesIcon, FunnelIcon, MagnifyingGlassIcon, PhotoIcon,
    CalendarIcon, UserIcon, ClockIcon, BuildingOfficeIcon,
    XMarkIcon, FolderIcon, CheckCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    feedItems: Object,
    units: Array,
    filters: Object,
    categories: Object,
});

const search = ref(props.filters.search || '');
const selectedUnit = ref(props.filters.unit_id || 'all');
const selectedCategory = ref(props.filters.category || '');
const selectedDate = ref(props.filters.date || '');

const applyFilters = () => {
    router.get(route('activity-logs.feed'), {
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
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

// Lightbox
const activePhotoUrl = ref(null);
const openPhotoLightbox = (url) => {
    activePhotoUrl.value = url;
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
</script>

<template>
    <Head title="Executive Feed - Giat Tugas Pegawai" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight flex items-center gap-2">
                        <SparklesIcon class="w-6 h-6 text-namira-teal" />
                        Executive Feed — Giat Tugas Pegawai
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Linimasa pemantauan kegiatan operasional & dokumentasi harian SDM Yayasan Namira.</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-6xl mx-auto space-y-6 px-4 sm:px-6">

            <!-- Filter Controls -->
            <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm space-y-3">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <FunnelIcon class="w-4 h-4 text-teal-600" />
                    <span>Filter & Pencarian Linimasa</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Search Input -->
                    <div class="relative">
                        <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 absolute left-3 top-3" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari nama pegawai, kegiatan..."
                            class="w-full pl-9 rounded-xl border-gray-200 text-xs focus:border-teal-600 focus:ring-teal-500/20"
                        />
                    </div>

                    <!-- Unit Filter -->
                    <div>
                        <select
                            v-model="selectedUnit"
                            @change="applyFilters"
                            class="w-full rounded-xl border-gray-200 text-xs focus:border-teal-600 focus:ring-teal-500/20"
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
                            class="w-full rounded-xl border-gray-200 text-xs focus:border-teal-600 focus:ring-teal-500/20"
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
                            class="w-full rounded-xl border-gray-200 text-xs focus:border-teal-600 focus:ring-teal-500/20"
                        />
                        <button
                            v-if="search || selectedUnit !== 'all' || selectedCategory || selectedDate"
                            @click="resetFilters"
                            class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl transition text-xs shrink-0"
                            title="Reset Filter"
                        >
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Feed Timeline Stream -->
            <div v-if="!feedItems.data || feedItems.data.length === 0" class="bg-white rounded-2xl border border-dashed border-gray-200 p-12 text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                    <FolderIcon class="w-6 h-6" />
                </div>
                <p class="font-bold text-gray-700 text-sm">Tidak Ada Giat Tugas Ditemukan</p>
                <p class="text-xs text-gray-400">Belum ada catatan giat tugas pegawai yang sesuai dengan filter di atas.</p>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="item in feedItems.data"
                    :key="item.id"
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-all space-y-4"
                >
                    <!-- Header: Employee Info & Timestamp -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-teal-50 border border-teal-100 flex items-center justify-center overflow-hidden shrink-0">
                                <img v-if="item.user?.profile_photo_url" :src="item.user.profile_photo_url" class="w-full h-full object-cover" />
                                <UserIcon v-else class="w-5 h-5 text-teal-600" />
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800 text-sm">{{ item.user?.name || 'Pegawai' }}</h4>
                                <div class="flex items-center gap-2 text-[11px] text-gray-400">
                                    <span v-if="item.unit?.name" class="font-medium text-teal-700 bg-teal-50 px-2 py-0.5 rounded-full border border-teal-100">
                                        {{ item.unit.name }}
                                    </span>
                                    <span>•</span>
                                    <span class="flex items-center gap-1 font-mono">
                                        <ClockIcon class="w-3 h-3" />
                                        {{ item.activity_date }} {{ item.activity_time ? item.activity_time.substring(0, 5) : '' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <span class="px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wide border" :class="categoryBadgeClass(item.category)">
                            {{ categories[item.category] || item.category }}
                        </span>
                    </div>

                    <!-- Content: Title & Description -->
                    <div class="space-y-1 pl-13">
                        <h3 class="font-bold text-gray-900 text-base leading-snug">{{ item.title }}</h3>
                        <p v-if="item.description" class="text-xs text-gray-600 whitespace-pre-line leading-relaxed">{{ item.description }}</p>
                    </div>

                    <!-- Photo Documentation Preview -->
                    <div v-if="item.photo_path" class="pl-13">
                        <div
                            @click="openPhotoLightbox('/' + item.photo_path)"
                            class="relative max-w-md h-56 rounded-2xl overflow-hidden border border-gray-200 group cursor-pointer bg-slate-50 shadow-sm"
                        >
                            <img :src="'/' + item.photo_path" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                            <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold gap-1.5">
                                <PhotoIcon class="w-5 h-5" />
                                <span>Lihat Dokumentasi Bukti Foto</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="feedItems.links && feedItems.links.length > 3" class="flex justify-center pt-4">
                    <div class="flex items-center gap-1 bg-white p-2 rounded-2xl border border-gray-100 shadow-sm">
                        <component
                            v-for="(link, key) in feedItems.links"
                            :key="key"
                            :is="link.url ? 'Link' : 'span'"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                            :class="{
                                'bg-teal-600 text-white shadow-sm': link.active,
                                'text-gray-600 hover:bg-gray-100': link.url && !link.active,
                                'text-gray-300': !link.url
                            }"
                        />
                    </div>
                </div>
            </div>

        </div>

        <!-- LIGHTBOX PHOTO MODAL -->
        <Teleport to="body">
            <div v-if="activePhotoUrl" @click="activePhotoUrl = null" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4 cursor-pointer">
                <div class="relative max-w-3xl max-h-[85vh] overflow-hidden rounded-2xl shadow-2xl">
                    <img :src="activePhotoUrl" class="w-full h-full object-contain max-h-[85vh]" />
                    <button @click.stop="activePhotoUrl = null" class="absolute top-3 right-3 p-2 bg-black/50 text-white rounded-full hover:bg-black">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
