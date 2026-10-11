<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    SparklesIcon,
    PlusIcon,
    MagnifyingGlassIcon,
    CalendarDaysIcon,
    ClockIcon,
    MapPinIcon,
    UserGroupIcon,
    AcademicCapIcon,
    UserIcon,
    ChevronRightIcon,
    XMarkIcon,
    PencilSquareIcon,
    TrashIcon,
    PhotoIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    AdjustmentsHorizontalIcon,
    BuildingLibraryIcon,
    EllipsisVerticalIcon,
    ArrowRightIcon,
    CheckIcon,
} from '@heroicons/vue/24/outline';
import { 
    SparklesIcon as SparklesIconSolid, 
    HeartIcon as HeartIconSolid 
} from '@heroicons/vue/24/solid';
import { HeartIcon as HeartIconOutline } from '@heroicons/vue/24/outline';

const props = defineProps({
    activities: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    rooms: { type: Array, default: () => [] },
    availableInstructors: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    canManage: { type: Boolean, default: false },
    isGlobalAdmin: { type: Boolean, default: false },
    isCoach: { type: Boolean, default: false },
    activeYear: { type: Object, default: () => null },
    units: { type: Array, default: () => [] },
});

// Search & Filter State
const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || 'all');
const selectedDay = ref(props.filters.day || 'all');
const showOnlyFavorites = ref(false);
const activeMenuId = ref(null);

// Favorites persisted in localStorage
const favorites = ref(new Set());
onMounted(() => {
    try {
        const stored = localStorage.getItem('namira_ekskul_favorites');
        if (stored) {
            favorites.value = new Set(JSON.parse(stored));
        }
    } catch (e) {
        console.error('Failed to load favorites', e);
    }

    const handleClickOutside = (e) => {
        if (!e.target.closest('.card-action-menu-container')) {
            activeMenuId.value = null;
        }
    };
    window.addEventListener('click', handleClickOutside);
    onUnmounted(() => window.removeEventListener('click', handleClickOutside));
});

const toggleFavorite = (id) => {
    if (favorites.value.has(id)) {
        favorites.value.delete(id);
    } else {
        favorites.value.add(id);
    }
    try {
        localStorage.setItem('namira_ekskul_favorites', JSON.stringify([...favorites.value]));
    } catch (e) {
        console.error('Failed to save favorites', e);
    }
};

const toggleActionMenu = (id) => {
    activeMenuId.value = activeMenuId.value === id ? null : id;
};

// Modals
const showModal = ref(false);
const isEditing = ref(false);
const editingActivityId = ref(null);
const isCompressing = ref(false);
const photoPreview = ref(null);
const photoSizeInfo = ref('');
const fileInput = ref(null);

const categories = [
    { key: 'all', label: 'Semua' },
    { key: 'olahraga', label: 'Olahraga' },
    { key: 'seni_budaya', label: 'Seni & Budaya' },
    { key: 'keagamaan', label: 'Keagamaan' },
    { key: 'sains_teknologi', label: 'Sains & Teknologi' },
    { key: 'kepanduan', label: 'Bela Diri' },
    { key: 'lainnya', label: 'Lainnya' },
];

const daysList = ['Semua Hari', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

const availableGradeLevels = [
    { value: '1', label: 'Kelas 1' },
    { value: '2', label: 'Kelas 2' },
    { value: '3', label: 'Kelas 3' },
    { value: '4', label: 'Kelas 4' },
    { value: '5', label: 'Kelas 5' },
    { value: '6', label: 'Kelas 6' },
];

const genderOptions = [
    { value: 'all', title: 'Semua Siswa', subtitle: 'Putra & Putri' },
    { value: 'male_only', title: 'Khusus Ikhwan', subtitle: 'Putra Saja' },
    { value: 'female_only', title: 'Khusus Akhwat', subtitle: 'Putri Saja' },
];

// Form
const form = useForm({
    unit_id: '',
    name: '',
    category: 'olahraga',
    day_of_week: 'Jumat',
    start_time: '14:30',
    end_time: '16:00',
    room_id: '',
    location_name: '',
    max_quota: 30,
    is_mandatory: false,
    gender_restriction: 'all',
    target_levels: ['1', '2', '3', '4', '5', '6'],
    description: '',
    cover_image: null,
    instructor_ids: [],
    status: 'active',
});

// Image error handling fallback
const imageErrors = ref(new Set());
const onImageError = (id) => {
    imageErrors.value.add(id);
};

// Filtered activities computed
const filteredActivities = computed(() => {
    return props.activities.filter((item) => {
        const matchesCategory = selectedCategory.value === 'all' || item.category === selectedCategory.value;
        const matchesDay = selectedDay.value === 'all' || selectedDay.value === 'Semua Hari' || item.day_of_week === selectedDay.value;
        const matchesFav = !showOnlyFavorites.value || favorites.value.has(item.id);
        const q = searchQuery.value.toLowerCase().trim();
        const matchesSearch = !q || 
            item.name.toLowerCase().includes(q) || 
            (item.description && item.description.toLowerCase().includes(q)) ||
            (item.location_name && item.location_name.toLowerCase().includes(q)) ||
            (item.instructors && item.instructors.some(i => i.user?.name?.toLowerCase().includes(q)));

        return matchesCategory && matchesDay && matchesFav && matchesSearch;
    });
});

// Client-side HTML5 Canvas Auto-Compression
const compressImage = (file) => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const maxWidth = 1280;
                const maxHeight = 1280;
                let width = img.width;
                let height = img.height;

                if (width > height) {
                    if (width > maxWidth) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = Math.round((width * maxHeight) / height);
                        height = maxHeight;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                const compressedBase64 = canvas.toDataURL('image/jpeg', 0.75);
                resolve(compressedBase64);
            };
            img.onerror = (err) => reject(err);
        };
        reader.onerror = (err) => reject(err);
    });
};

const handlePhotoSelect = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    isCompressing.value = true;
    try {
        const originalSizeKB = Math.round(file.size / 1024);
        const compressedBase64 = await compressImage(file);
        
        const compressedSizeKB = Math.round((compressedBase64.length * 3) / 4 / 1024);
        photoSizeInfo.value = `${compressedSizeKB} KB (dihemat dari ${originalSizeKB} KB)`;
        
        photoPreview.value = compressedBase64;
        form.cover_image = compressedBase64;
    } catch (err) {
        console.error('Image compression failed:', err);
        photoSizeInfo.value = 'Kompresi otomatis gagal, memakai file asli';
        form.cover_image = file;
    } finally {
        isCompressing.value = false;
    }
};

const removePhoto = () => {
    photoPreview.value = null;
    photoSizeInfo.value = '';
    form.cover_image = null;
    if (fileInput.value) fileInput.value.value = '';
};

const toggleGradeLevel = (val) => {
    const idx = form.target_levels.indexOf(val);
    if (idx > -1) {
        form.target_levels.splice(idx, 1);
    } else {
        form.target_levels.push(val);
    }
};

const selectAllGradeLevels = () => {
    form.target_levels = availableGradeLevels.map(g => g.value);
};

const resetGradeLevels = () => {
    form.target_levels = [];
};

const openCreateModal = () => {
    isEditing.value = false;
    editingActivityId.value = null;
    form.reset();
    form.clearErrors();
    photoPreview.value = null;
    photoSizeInfo.value = '';
    form.target_levels = ['1', '2', '3', '4', '5', '6'];
    form.status = 'active';
    if (props.units && props.units.length > 0) {
        form.unit_id = props.units[0].id;
    }
    showModal.value = true;
};

const openEditModal = (activity) => {
    isEditing.value = true;
    editingActivityId.value = activity.id;
    activeMenuId.value = null;
    form.clearErrors();
    form.unit_id = activity.unit_id || '';
    form.name = activity.name;
    form.category = activity.category;
    form.day_of_week = activity.day_of_week || 'Jumat';
    form.start_time = activity.start_time || '14:30';
    form.end_time = activity.end_time || '16:00';
    form.room_id = activity.room_id || '';
    form.location_name = activity.location_name || '';
    form.max_quota = activity.max_quota || 30;
    form.is_mandatory = activity.is_mandatory || false;
    form.gender_restriction = activity.gender_restriction || 'all';
    form.target_levels = Array.isArray(activity.target_levels) ? activity.target_levels : ['1', '2', '3', '4', '5', '6'];
    form.description = activity.description || '';
    form.status = activity.status || 'active';
    form.cover_image = null;
    photoPreview.value = activity.cover_image_url || null;
    photoSizeInfo.value = activity.cover_image_url ? 'Foto sampul aktif' : '';
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('extracurricular.update', editingActivityId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('extracurricular.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const confirmDelete = (activity) => {
    activeMenuId.value = null;
    if (confirm(`Apakah Anda yakin ingin menghapus ekstrakurikuler "${activity.name}"? Data anggota dan riwayat presensi akan diarsipkan.`)) {
        router.delete(route('extracurricular.destroy', activity.id), {
            preserveScroll: true,
        });
    }
};

const getCategoryColor = (cat) => {
    switch (cat) {
        case 'olahraga': return 'bg-emerald-50 text-emerald-800 border-emerald-200/80';
        case 'seni_budaya': return 'bg-purple-50 text-purple-800 border-purple-200/80';
        case 'keagamaan': return 'bg-amber-50 text-amber-800 border-amber-200/80';
        case 'sains_teknologi': return 'bg-sky-50 text-sky-800 border-sky-200/80';
        case 'kepanduan': return 'bg-orange-50 text-orange-800 border-orange-200/80';
        default: return 'bg-slate-50 text-slate-800 border-slate-200/80';
    }
};

const getQuotaPercentage = (item) => {
    if (!item.max_quota || item.max_quota <= 0) return 0;
    return Math.min(100, Math.round(((item.active_members_count || 0) / item.max_quota) * 100));
};

const getProgressBarColor = (percentage) => {
    if (percentage >= 100) return 'bg-rose-500';
    if (percentage >= 85) return 'bg-amber-500';
    return 'bg-[#00796B]';
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Katalog Ekstrakurikuler" />

        <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 sm:py-7">
            
            <!-- TOP HERO BANNER (Pixel Perfect Matching Mockup 1 & 2) -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-50 via-teal-50/70 to-emerald-100/40 border border-emerald-100/80 p-5 sm:p-7 shadow-xs">
                <!-- Decorative Soft Wave/Blur -->
                <div class="absolute -right-12 -top-12 w-64 h-64 bg-teal-200/30 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-2.5 max-w-xl">
                        <!-- Pill Badge -->
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-teal-100/90 text-teal-800 border border-teal-200/60 shadow-xs">
                                <SparklesIconSolid class="w-3.5 h-3.5 text-teal-600" />
                                <span>Ekstrakurikuler</span>
                            </span>
                            <span v-if="activeYear" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100/80 text-emerald-800 border border-emerald-200">
                                TA {{ activeYear.name }} ({{ activeYear.semester }})
                            </span>
                        </div>

                        <!-- Banner Headline -->
                        <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-800 tracking-tight leading-tight">
                            Pusat Pembinaan Bakat & Minat Siswa
                        </h1>

                        <!-- Subtitle -->
                        <p class="text-slate-600 text-xs sm:text-sm leading-relaxed">
                            Kelola kegiatan, pantau perkembangan, dan dukung potensi terbaik mereka.
                        </p>
                    </div>

                    <!-- Banner Illustration (Optimized WebP) -->
                    <div class="shrink-0 flex justify-end md:justify-center">
                        <img
                            src="/images/banners/extracurricular_hero.webp"
                            alt="Ilustrasi Bakat Minat Siswa"
                            class="h-28 sm:h-36 md:h-40 w-auto object-contain select-none pointer-events-none drop-shadow-sm"
                            loading="eager"
                        />
                    </div>
                </div>
            </div>

            <!-- 4 STAT METRICS OVERVIEW CARDS (Matching Mockup 2) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
                <!-- Total Kegiatan -->
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center gap-3.5 hover:border-teal-200 transition-colors">
                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center shrink-0 border border-teal-100/60">
                        <SparklesIcon class="w-5 h-5 sm:w-6 sm:h-6 stroke-[2.2]" />
                    </div>
                    <div>
                        <div class="text-xl sm:text-2xl font-black text-slate-800">{{ stats.total_activities || 0 }}</div>
                        <div class="text-xs font-semibold text-slate-500">Total Kegiatan</div>
                    </div>
                </div>

                <!-- Anggota Terdaftar -->
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center gap-3.5 hover:border-sky-200 transition-colors">
                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-sky-50 text-sky-700 flex items-center justify-center shrink-0 border border-sky-100/60">
                        <AcademicCapIcon class="w-5 h-5 sm:w-6 sm:h-6 stroke-[2.2]" />
                    </div>
                    <div>
                        <div class="text-xl sm:text-2xl font-black text-slate-800">{{ stats.total_members || 0 }}</div>
                        <div class="text-xs font-semibold text-slate-500">Anggota Terdaftar</div>
                    </div>
                </div>

                <!-- Sesi Bulan Ini -->
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center gap-3.5 hover:border-amber-200 transition-colors">
                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 border border-amber-100/60">
                        <CalendarDaysIcon class="w-5 h-5 sm:w-6 sm:h-6 stroke-[2.2]" />
                    </div>
                    <div>
                        <div class="text-xl sm:text-2xl font-black text-slate-800">{{ stats.total_sessions_month || 0 }}</div>
                        <div class="text-xs font-semibold text-slate-500">Sesi Bulan Ini</div>
                    </div>
                </div>

                <!-- Pelatih & Pembina -->
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center gap-3.5 hover:border-purple-200 transition-colors">
                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-purple-50 text-purple-700 flex items-center justify-center shrink-0 border border-purple-100/60">
                        <UserIcon class="w-5 h-5 sm:w-6 sm:h-6 stroke-[2.2]" />
                    </div>
                    <div>
                        <div class="text-xl sm:text-2xl font-black text-slate-800">{{ stats.total_instructors || 0 }}</div>
                        <div class="text-xs font-semibold text-slate-500">Pelatih & Pembina</div>
                    </div>
                </div>
            </div>

            <!-- SEARCH, ACTION BUTTON & FILTER PILLS (Matching Mockup 2) -->
            <div class="space-y-3.5">
                <!-- Search & Add Ekskul Bar -->
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <MagnifyingGlassIcon class="w-4 h-4 sm:w-5 sm:h-5 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama ekskul, pelatih, atau lokasi..."
                            class="w-full pl-10 pr-9 py-2.5 rounded-xl border border-slate-200 bg-white text-xs sm:text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all placeholder:text-slate-400 shadow-xs"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        >
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Favorites Toggle Filter Button -->
                    <button
                        @click="showOnlyFavorites = !showOnlyFavorites"
                        type="button"
                        class="p-2.5 rounded-xl border transition-colors shrink-0 shadow-xs flex items-center justify-center"
                        :class="showOnlyFavorites 
                            ? 'bg-rose-50 border-rose-300 text-rose-600' 
                            : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'"
                        title="Tampilkan Favorit"
                    >
                        <HeartIconSolid v-if="showOnlyFavorites" class="w-5 h-5 text-rose-500" />
                        <HeartIconOutline v-else class="w-5 h-5 text-slate-500" />
                    </button>

                    <!-- Tambah Ekskul Baru Button -->
                    <button
                        v-if="canManage"
                        @click="openCreateModal"
                        type="button"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-[#00796B] hover:bg-[#00695C] text-white font-bold text-xs sm:text-sm shadow-xs transition-all active:scale-95 shrink-0"
                    >
                        <PlusIcon class="w-4 h-4 stroke-[3]" />
                        <span class="hidden sm:inline">Tambah Ekskul Baru</span>
                        <span class="sm:hidden">Tambah</span>
                    </button>
                </div>

                <!-- Category Pills (Horizontal Scrollable) -->
                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none select-none">
                    <button
                        v-for="cat in categories"
                        :key="cat.key"
                        @click="selectedCategory = cat.key"
                        type="button"
                        class="px-4 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition-all duration-150 border shrink-0"
                        :class="selectedCategory === cat.key
                            ? 'bg-[#00796B] text-white border-[#00796B] shadow-xs'
                            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                    >
                        {{ cat.label }}
                    </button>
                </div>

                <!-- Day Pills (Horizontal Scrollable) -->
                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none select-none">
                    <button
                        v-for="day in daysList"
                        :key="day"
                        @click="selectedDay = day === 'Semua Hari' ? 'all' : day"
                        type="button"
                        class="px-3.5 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-all duration-150 border shrink-0"
                        :class="(selectedDay === day || (selectedDay === 'all' && day === 'Semua Hari'))
                            ? 'bg-slate-800 text-white border-slate-800 shadow-xs font-bold'
                            : 'bg-white text-slate-500 border-slate-200 hover:bg-slate-50'"
                    >
                        {{ day }}
                    </button>
                </div>
            </div>

            <!-- ACTIVITY CARDS GRID (Matching Mockup 2 Card Structure) -->
            <div v-if="filteredActivities.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="item in filteredActivities"
                    :key="item.id"
                    class="bg-white rounded-3xl border border-slate-200/90 shadow-xs hover:shadow-md hover:border-teal-300 transition-all duration-200 overflow-hidden flex flex-col group relative"
                >
                    <!-- Card Top Media / Cover Area (16:9 Aspect) -->
                    <div class="relative h-44 bg-slate-100 overflow-hidden">
                        <img
                            v-if="item.cover_image_url && !imageErrors.has(item.id)"
                            :src="item.cover_image_url"
                            :alt="item.name"
                            @error="onImageError(item.id)"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        />
                        <div v-else class="w-full h-full bg-gradient-to-tr from-teal-900 via-teal-800 to-emerald-700 flex flex-col items-center justify-center p-4 text-white text-center">
                            <SparklesIcon class="w-10 h-10 text-teal-200/60 mb-1" />
                            <span class="text-sm font-black tracking-wide uppercase text-white/90">{{ item.name }}</span>
                            <span class="text-[11px] text-teal-200/80 mt-0.5">{{ item.category_label }}</span>
                        </div>

                        <!-- Top-Left Badges: Category & Mandatory -->
                        <div class="absolute top-3 left-3 flex flex-wrap gap-1.5 items-center z-10">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-white/95 backdrop-blur-md shadow-xs border" :class="getCategoryColor(item.category)">
                                {{ item.category_label }}
                            </span>
                            <span v-if="item.is_mandatory" class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-600 text-white shadow-xs">
                                Wajib
                            </span>
                        </div>

                        <!-- Top-Right Favorite Heart Button -->
                        <button
                            @click.stop="toggleFavorite(item.id)"
                            type="button"
                            class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white/95 backdrop-blur-md shadow-xs border border-slate-200/80 flex items-center justify-center transition-transform active:scale-90 z-10"
                            :title="favorites.has(item.id) ? 'Hapus dari favorit' : 'Tambah ke favorit'"
                        >
                            <HeartIconSolid v-if="favorites.has(item.id)" class="w-4 h-4 text-rose-500" />
                            <HeartIconOutline v-else class="w-4 h-4 text-slate-400 hover:text-rose-500" />
                        </button>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <!-- Title Row & 3-Dots Action Menu -->
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-black text-base sm:text-lg text-slate-800 leading-snug group-hover:text-teal-700 transition-colors line-clamp-1">
                                        {{ item.name }}
                                    </h3>
                                    
                                    <!-- School Unit Badge -->
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-100">
                                            <BuildingLibraryIcon class="w-3 h-3 text-emerald-600" />
                                            <span>{{ item.unit?.name || 'SD NAMIRA' }}</span>
                                        </span>
                                        <span v-if="item.status === 'inactive'" class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600">
                                            Nonaktif
                                        </span>
                                    </div>
                                </div>

                                <!-- 3-Dots Action Popover (Edit & Delete) -->
                                <div v-if="item.can_edit" class="relative card-action-menu-container shrink-0">
                                    <button
                                        @click.stop="toggleActionMenu(item.id)"
                                        type="button"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                                        title="Opsi Kegiatan"
                                    >
                                        <EllipsisVerticalIcon class="w-5 h-5" />
                                    </button>

                                    <!-- Action Dropdown Menu -->
                                    <div
                                        v-if="activeMenuId === item.id"
                                        class="absolute right-0 top-full mt-1 w-44 bg-white rounded-2xl shadow-xl border border-slate-100 py-1.5 z-30 animate-in fade-in zoom-in-95"
                                    >
                                        <button
                                            @click="openEditModal(item)"
                                            type="button"
                                            class="w-full px-3.5 py-2 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2"
                                        >
                                            <PencilSquareIcon class="w-4 h-4 text-teal-600" />
                                            <span>Edit Ekstrakurikuler</span>
                                        </button>
                                        <button
                                            v-if="canManage"
                                            @click="confirmDelete(item)"
                                            type="button"
                                            class="w-full px-3.5 py-2 text-left text-xs font-semibold text-rose-600 hover:bg-rose-50 flex items-center gap-2"
                                        >
                                            <TrashIcon class="w-4 h-4 text-rose-500" />
                                            <span>Hapus Kegiatan</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Meta Details (Jadwal & Lokasi) -->
                            <div class="space-y-1.5 text-xs text-slate-600 pt-1">
                                <!-- Jadwal Latihan -->
                                <div class="flex items-center gap-2">
                                    <ClockIcon class="w-4 h-4 text-[#00796B] shrink-0" />
                                    <span class="font-semibold text-slate-700">
                                        Setiap {{ item.day_of_week }}, {{ item.start_time }} - {{ item.end_time }} WIB
                                    </span>
                                </div>

                                <!-- Lokasi / Ruangan -->
                                <div class="flex items-center gap-2">
                                    <MapPinIcon class="w-4 h-4 text-rose-500 shrink-0" />
                                    <span class="truncate">
                                        {{ item.room?.name || item.location_name || 'Area Kampus Sekolah' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Coach / Instructor Row -->
                            <div class="flex items-center gap-2.5 pt-2 border-t border-slate-100">
                                <div class="w-7 h-7 rounded-full bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-xs shrink-0 border border-teal-200">
                                    {{ (item.instructors?.[0]?.user?.name || 'P').charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-bold text-slate-800 truncate">
                                        {{ item.instructors?.[0]?.user?.name || 'Belum ditugaskan' }}
                                    </div>
                                    <div class="text-[10px] text-slate-500">
                                        {{ item.instructors?.[0]?.role_title || 'Pelatih Utama' }}
                                    </div>
                                </div>
                            </div>

                            <!-- Quota Counter & Progress Bar -->
                            <div class="space-y-1.5 pt-1">
                                <div class="flex items-center justify-between text-xs font-semibold">
                                    <span class="text-slate-700 font-bold">
                                        {{ item.active_members_count || 0 }}/{{ item.max_quota }} Siswa
                                    </span>
                                    <span
                                        :class="(item.active_members_count || 0) >= item.max_quota ? 'text-rose-600 font-bold' : 'text-slate-500'"
                                    >
                                        {{ (item.active_members_count || 0) >= item.max_quota 
                                            ? 'Kuota Penuh' 
                                            : `Sisa ${Math.max(0, item.max_quota - (item.active_members_count || 0))} Kursi` }}
                                    </span>
                                </div>
                                
                                <!-- Visual Bar -->
                                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-300"
                                        :class="getProgressBarColor(getQuotaPercentage(item))"
                                        :style="{ width: `${getQuotaPercentage(item)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Action Button: Kelola Kegiatan → -->
                        <div class="pt-2">
                            <Link
                                :href="route('extracurricular.show', item.id)"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-[#00796B] hover:bg-[#00695C] text-white font-bold text-xs sm:text-sm shadow-xs transition-all active:scale-[0.98]"
                            >
                                <span>Kelola Kegiatan</span>
                                <ArrowRightIcon class="w-4 h-4 stroke-[2.5]" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-3xl p-12 text-center border border-slate-200/80 shadow-xs space-y-4">
                <div class="w-16 h-16 rounded-full bg-teal-50 text-teal-700 mx-auto flex items-center justify-center border border-teal-100">
                    <SparklesIcon class="w-8 h-8 stroke-[2]" />
                </div>
                <div class="space-y-1">
                    <h3 class="font-black text-lg text-slate-800">Tidak Ada Kegiatan Ekstrakurikuler</h3>
                    <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto">
                        Belum ada kegiatan ekstrakurikuler yang sesuai dengan kriteria filter atau pencarian Anda.
                    </p>
                </div>
                <button
                    v-if="canManage"
                    @click="openCreateModal"
                    type="button"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#00796B] text-white font-bold text-xs hover:bg-[#00695C] transition-all shadow-xs"
                >
                    <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                    <span>Tambah Ekskul Sekarang</span>
                </button>
            </div>
        </div>

        <!-- ========================================================================================= -->
        <!-- MODAL TAMBAH & EDIT EKSTRAKURIKULER (Pixel Perfect Matching Mockup 3 Segmented Sections) -->
        <!-- ========================================================================================= -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
                <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-2xl overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-200">
                    
                    <!-- Modal Header -->
                    <div class="px-6 py-4 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-teal-50 text-[#00796B] border border-teal-100 flex items-center justify-center font-bold">
                                <SparklesIcon class="w-5 h-5 stroke-[2.2]" />
                            </div>
                            <div>
                                <h3 class="font-black text-slate-800 text-base sm:text-lg">
                                    {{ isEditing ? 'Edit Ekstrakurikuler' : 'Tambah Ekstrakurikuler' }}
                                </h3>
                                <p class="text-xs text-slate-500">Lengkapi data kegiatan di bawah ini.</p>
                            </div>
                        </div>
                        <button
                            @click="showModal = false"
                            class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                        >
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Modal Body (4 Segmented Numbered Sections) -->
                    <form @submit.prevent="submitForm" class="p-6 space-y-7 overflow-y-auto flex-1 text-slate-800">
                        
                        <!-- ================= SECTION 1: INFORMASI DASAR ================= -->
                        <div class="space-y-4">
                            <!-- Section Title with Number Badge -->
                            <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                                <div class="w-7 h-7 rounded-full bg-[#00796B] text-white flex items-center justify-center text-xs font-black shrink-0">
                                    1
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm sm:text-base leading-tight">Informasi Dasar</h4>
                                    <p class="text-[11px] text-slate-500">Masukkan informasi umum kegiatan.</p>
                                </div>
                            </div>

                            <!-- Nama Kegiatan & Kategori -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Kegiatan *</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        placeholder="Contoh: Futsal SD"
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 placeholder:text-slate-400"
                                    />
                                    <div v-if="form.errors.name" class="text-rose-500 text-xs mt-1">{{ form.errors.name }}</div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Kategori Ekskul *</label>
                                    <select
                                        v-model="form.category"
                                        required
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                                    >
                                        <option value="olahraga">Olahraga</option>
                                        <option value="seni_budaya">Seni & Budaya</option>
                                        <option value="keagamaan">Keagamaan</option>
                                        <option value="sains_teknologi">Sains & Teknologi</option>
                                        <option value="kepanduan">Bela Diri</option>
                                        <option value="lainnya">Lainnya</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Deskripsi Kegiatan with Char Counter -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-slate-700">Deskripsi Kegiatan</label>
                                    <span class="text-[11px] text-slate-400">{{ (form.description || '').length }}/500</span>
                                </div>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    maxlength="500"
                                    placeholder="Jelaskan tentang kegiatan ini, target kemampuan, atau silabus singkat..."
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 placeholder:text-slate-400"
                                ></textarea>
                            </div>

                            <!-- Foto Cover Upload Dropzone -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Foto Cover</label>
                                
                                <div
                                    @click="fileInput?.click()"
                                    class="border-2 border-dashed border-slate-200 hover:border-teal-400 bg-slate-50/60 hover:bg-teal-50/20 rounded-2xl p-4 sm:p-5 text-center cursor-pointer transition-all group"
                                >
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="handlePhotoSelect"
                                    />

                                    <!-- If Preview Available -->
                                    <div v-if="photoPreview" class="flex flex-col sm:flex-row items-center gap-4 text-left">
                                        <div class="w-24 h-24 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 shrink-0">
                                            <img :src="photoPreview" alt="Cover Preview" class="w-full h-full object-cover" />
                                        </div>
                                        <div class="space-y-1 min-w-0 flex-1">
                                            <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                                <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                                                <span>Foto berhasil dipilih</span>
                                            </div>
                                            <p v-if="photoSizeInfo" class="text-[11px] text-emerald-700 font-medium">
                                                {{ photoSizeInfo }}
                                            </p>
                                            <div class="flex items-center gap-3 pt-1">
                                                <span class="text-xs text-teal-700 font-semibold hover:underline">Klik untuk ganti foto</span>
                                                <button
                                                    @click.stop="removePhoto"
                                                    type="button"
                                                    class="text-xs text-rose-500 font-semibold hover:underline"
                                                >
                                                    Hapus
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Empty Upload State -->
                                    <div v-else class="flex flex-col items-center justify-center py-2 space-y-1.5">
                                        <div class="w-10 h-10 rounded-full bg-teal-50 text-teal-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                                            <PhotoIcon class="w-5 h-5" />
                                        </div>
                                        <div class="text-xs font-bold text-slate-700">
                                            Klik untuk unggah foto <span class="font-normal text-slate-500">atau seret ke sini</span>
                                        </div>
                                        <div class="text-[11px] text-slate-400">
                                            PNG, JPG maks. 5 MB (otomatis terkompresi)
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ================= SECTION 2: UNIT & TARGET PESERTA ================= -->
                        <div class="space-y-4">
                            <!-- Section Title with Number Badge -->
                            <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                                <div class="w-7 h-7 rounded-full bg-[#00796B] text-white flex items-center justify-center text-xs font-black shrink-0">
                                    2
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm sm:text-base leading-tight">Unit & Target Peserta</h4>
                                    <p class="text-[11px] text-slate-500">Tentukan unit sekolah dan sasaran peserta yang dapat bergabung.</p>
                                </div>
                            </div>

                            <!-- Unit Sekolah -->
                            <div v-if="props.units && props.units.length > 0">
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Unit Sekolah *</label>
                                <div class="relative">
                                    <BuildingLibraryIcon class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                                    <select
                                        v-model="form.unit_id"
                                        required
                                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white font-medium"
                                    >
                                        <option v-for="u in units" :key="u.id" :value="u.id">
                                            {{ u.name }} ({{ u.code || 'Unit' }})
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Target Tingkat / Jenjang Kelas -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-slate-700">Target Tingkat / Jenjang Kelas *</label>
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="selectAllGradeLevels"
                                            type="button"
                                            class="text-[11px] text-[#00796B] font-semibold hover:underline"
                                        >
                                            Pilih Semua
                                        </button>
                                        <span class="text-slate-300 text-xs">|</span>
                                        <button
                                            @click="resetGradeLevels"
                                            type="button"
                                            class="text-[11px] text-slate-500 font-semibold hover:underline"
                                        >
                                            Reset
                                        </button>
                                    </div>
                                </div>

                                <!-- Kelas Checkbox Pills Grid -->
                                <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                                    <button
                                        v-for="g in availableGradeLevels"
                                        :key="g.value"
                                        @click="toggleGradeLevel(g.value)"
                                        type="button"
                                        class="px-3 py-2 rounded-xl text-xs font-bold border transition-all flex items-center justify-center gap-1.5"
                                        :class="form.target_levels.includes(g.value)
                                            ? 'bg-teal-50 text-teal-800 border-teal-600 shadow-xs ring-1 ring-teal-500'
                                            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                    >
                                        <CheckIcon v-if="form.target_levels.includes(g.value)" class="w-3.5 h-3.5 stroke-[3] text-[#00796B]" />
                                        <span>{{ g.label }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Batasan Gender (3 Card Selectors Matching Mockup 3) -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Batasan Gender</label>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                    <button
                                        v-for="opt in genderOptions"
                                        :key="opt.value"
                                        @click="form.gender_restriction = opt.value"
                                        type="button"
                                        class="p-3 rounded-2xl border text-left transition-all"
                                        :class="form.gender_restriction === opt.value
                                            ? 'border-teal-600 bg-teal-50/70 ring-1 ring-teal-500 shadow-xs'
                                            : 'border-slate-200 bg-white hover:border-slate-300'"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold" :class="form.gender_restriction === opt.value ? 'text-teal-900' : 'text-slate-800'">
                                                {{ opt.title }}
                                            </span>
                                            <span
                                                class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0"
                                                :class="form.gender_restriction === opt.value ? 'border-teal-600 bg-teal-600' : 'border-slate-300'"
                                            >
                                                <span v-if="form.gender_restriction === opt.value" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">{{ opt.subtitle }}</div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ================= SECTION 3: JADWAL & LOKASI ================= -->
                        <div class="space-y-4">
                            <!-- Section Title with Number Badge -->
                            <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                                <div class="w-7 h-7 rounded-full bg-[#00796B] text-white flex items-center justify-center text-xs font-black shrink-0">
                                    3
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm sm:text-base leading-tight">Jadwal & Lokasi</h4>
                                    <p class="text-[11px] text-slate-500">Atur jadwal latihan dan lokasi pelaksanaan.</p>
                                </div>
                            </div>

                            <!-- Hari Latihan -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Hari Latihan *</label>
                                <select
                                    v-model="form.day_of_week"
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                                >
                                    <option value="Senin">Senin</option>
                                    <option value="Selasa">Selasa</option>
                                    <option value="Rabu">Rabu</option>
                                    <option value="Kamis">Kamis</option>
                                    <option value="Jumat">Jumat</option>
                                    <option value="Sabtu">Sabtu</option>
                                    <option value="Minggu">Minggu</option>
                                </select>
                            </div>

                            <!-- Jam Mulai & Jam Selesai -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Jam Mulai *</label>
                                    <div class="relative">
                                        <ClockIcon class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                                        <input
                                            v-model="form.start_time"
                                            type="time"
                                            required
                                            class="w-full pl-10 pr-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Jam Selesai *</label>
                                    <div class="relative">
                                        <ClockIcon class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                                        <input
                                            v-model="form.end_time"
                                            type="time"
                                            required
                                            class="w-full pl-10 pr-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Lokasi / Ruangan Sarpras & Keterangan Tambahan -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Lokasi / Ruangan</label>
                                    <select
                                        v-model="form.room_id"
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                                    >
                                        <option value="">-- Pilih Ruangan / Sarpras --</option>
                                        <option v-for="room in rooms" :key="room.id" :value="room.id">
                                            {{ room.name }} ({{ room.building ? (room.building + (room.floor ? ' Lt.' + room.floor : '')) : 'Kampus' }})
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Keterangan Lokasi Tambahan</label>
                                    <input
                                        v-model="form.location_name"
                                        type="text"
                                        placeholder="Contoh: Lapangan Utama / Aula Lt. 2"
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 placeholder:text-slate-400"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- ================= SECTION 4: KUOTA & STATUS ================= -->
                        <div class="space-y-4">
                            <!-- Section Title with Number Badge -->
                            <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                                <div class="w-7 h-7 rounded-full bg-[#00796B] text-white flex items-center justify-center text-xs font-black shrink-0">
                                    4
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm sm:text-base leading-tight">Kuota & Status</h4>
                                    <p class="text-[11px] text-slate-500">Tentukan kapasitas peserta dan status kegiatan.</p>
                                </div>
                            </div>

                            <!-- Kuota Maksimal Siswa -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kuota Maksimal Siswa *</label>
                                <div class="relative max-w-xs">
                                    <input
                                        v-model.number="form.max_quota"
                                        type="number"
                                        min="1"
                                        max="500"
                                        required
                                        class="w-full px-3.5 py-2.5 pr-14 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 font-semibold text-slate-800"
                                    />
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">
                                        siswa
                                    </span>
                                </div>
                            </div>

                            <!-- Status Toggle (Aktif / Nonaktif) -->
                            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 flex items-center justify-between">
                                <div class="space-y-0.5">
                                    <div class="text-xs font-bold text-slate-800">Status Kegiatan</div>
                                    <div class="text-[11px] text-slate-500">
                                        Kegiatan aktif dapat menerima pendaftaran siswa baru.
                                    </div>
                                </div>

                                <button
                                    @click="form.status = form.status === 'active' ? 'inactive' : 'active'"
                                    type="button"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden"
                                    :class="form.status === 'active' ? 'bg-[#00796B]' : 'bg-slate-300'"
                                >
                                    <span
                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                        :class="form.status === 'active' ? 'translate-x-5' : 'translate-x-0'"
                                    />
                                </button>
                            </div>

                            <!-- Sifat Ekskul Wajib -->
                            <div class="p-3.5 rounded-2xl border border-slate-200 bg-white flex items-center gap-3">
                                <input
                                    v-model="form.is_mandatory"
                                    type="checkbox"
                                    id="modal_mandatory_cb"
                                    class="w-4 h-4 text-[#00796B] rounded-sm border-slate-300 focus:ring-teal-500"
                                />
                                <label for="modal_mandatory_cb" class="text-xs font-semibold text-slate-700 cursor-pointer">
                                    Wajib Diikuti Semua Siswa (seperti Pramuka)
                                </label>
                            </div>

                            <!-- Optional Initial Instructor Selection (Saat Tambah Baru) -->
                            <div v-if="!isEditing && availableInstructors && availableInstructors.length > 0">
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Tugaskan Pelatih Awal (Opsional)</label>
                                <select
                                    v-model="form.instructor_ids"
                                    multiple
                                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white h-24"
                                >
                                    <option v-for="coach in availableInstructors" :key="coach.id" :value="coach.id">
                                        {{ coach.name }} ({{ coach.phone || 'No HP -' }})
                                    </option>
                                </select>
                                <p class="text-[11px] text-slate-400 mt-1">Tahan tombol Ctrl untuk memilih lebih dari 1. Pelatih juga dapat ditugaskan kapan saja di halaman detail.</p>
                            </div>
                        </div>

                        <!-- Modal Actions Footer -->
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3 sticky bottom-0 bg-white pb-2">
                            <button
                                @click="showModal = false"
                                type="button"
                                class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs sm:text-sm"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing || isCompressing"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#00796B] hover:bg-[#00695C] text-white font-bold text-xs sm:text-sm shadow-xs transition-all disabled:opacity-50"
                            >
                                <span v-if="form.processing">Menyimpan...</span>
                                <span v-else>{{ isEditing ? 'Simpan Perubahan' : 'Simpan Kegiatan' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
