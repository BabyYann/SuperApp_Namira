<script setup>
import { ref, computed } from 'vue';
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
    ChevronRightIcon,
    XMarkIcon,
    PencilSquareIcon,
    TrashIcon,
    PhotoIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    ShieldCheckIcon,
    FunnelIcon,
} from '@heroicons/vue/24/outline';
import { SparklesIcon as SparklesIconSolid } from '@heroicons/vue/24/solid';

const props = defineProps({
    activities: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    rooms: { type: Array, default: () => [] },
    availableInstructors: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    canManage: { type: Boolean, default: false },
    isCoach: { type: Boolean, default: false },
    activeYear: { type: Object, default: () => null },
});

// Search & Filter State
const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || 'all');
const selectedDay = ref(props.filters.day || 'all');

// Modals
const showModal = ref(false);
const isEditing = ref(false);
const editingActivityId = ref(null);
const isCompressing = ref(false);
const photoPreview = ref(null);
const photoSizeInfo = ref('');
const fileInput = ref(null);

const categories = [
    { key: 'all', label: 'Semua Kategori' },
    { key: 'olahraga', label: 'Olahraga & Atletik' },
    { key: 'seni_budaya', label: 'Seni & Budaya' },
    { key: 'keagamaan', label: 'Keagamaan & Karakter' },
    { key: 'sains_teknologi', label: 'Sains & Teknologi' },
    { key: 'kepanduan', label: 'Kepanduan & Bela Diri' },
    { key: 'lainnya', label: 'Lainnya' },
];

const daysList = ['Semua Hari', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

// Form
const form = useForm({
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
    target_levels: [],
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
        const q = searchQuery.value.toLowerCase().trim();
        const matchesSearch = !q || 
            item.name.toLowerCase().includes(q) || 
            (item.description && item.description.toLowerCase().includes(q)) ||
            (item.location_name && item.location_name.toLowerCase().includes(q));

        return matchesCategory && matchesDay && matchesSearch;
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

                const compressedBase64 = canvas.toDataURL('image/jpeg', 0.72);
                resolve(compressedBase64);
            };
            img.onerror = (err) => reject(err);
        };
        reader.onerror = (err) => reject(err);
    });
};

const handlePhotoSelect = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    isCompressing.value = true;
    try {
        const originalSizeKB = Math.round(file.size / 1024);
        const compressedBase64 = await compressImage(file);
        
        // Approximate Base64 size in KB
        const compressedSizeKB = Math.round((compressedBase64.length * 3) / 4 / 1024);
        photoSizeInfo.value = `${compressedSizeKB} KB (dari ${originalSizeKB} KB)`;
        
        photoPreview.value = compressedBase64;
        form.cover_image = compressedBase64;
    } catch (err) {
        console.error('Image compression failed:', err);
        photoSizeInfo.value = 'Kompresi gagal, menggunakan file asli';
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

const openCreateModal = () => {
    isEditing.value = false;
    editingActivityId.value = null;
    form.reset();
    form.clearErrors();
    photoPreview.value = null;
    photoSizeInfo.value = '';
    showModal.value = true;
};

const openEditModal = (activity) => {
    isEditing.value = true;
    editingActivityId.value = activity.id;
    form.clearErrors();
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
    form.target_levels = activity.target_levels || [];
    form.description = activity.description || '';
    form.status = activity.status || 'active';
    form.cover_image = null;
    photoPreview.value = activity.cover_image_url || null;
    photoSizeInfo.value = activity.cover_image_url ? 'Foto Terpasang' : '';
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
    if (confirm(`Apakah Anda yakin ingin menghapus ekstrakurikuler "${activity.name}"? Data anggota dan sesi akan diarsipkan.`)) {
        router.delete(route('extracurricular.destroy', activity.id), {
            preserveScroll: true,
        });
    }
};

const getCategoryColor = (cat) => {
    switch (cat) {
        case 'olahraga': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'seni_budaya': return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'keagamaan': return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'sains_teknologi': return 'bg-sky-50 text-sky-700 border-sky-200';
        case 'kepanduan': return 'bg-orange-50 text-orange-700 border-orange-200';
        default: return 'bg-slate-50 text-slate-700 border-slate-200';
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Ekstrakurikuler & Pengembangan Bakat" />

        <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            
            <!-- Top Hero Banner -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-teal-800 via-teal-700 to-emerald-700 text-white p-6 sm:p-8 shadow-xl shadow-teal-900/10">
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-white/20 backdrop-blur-md text-teal-100 border border-white/20">
                                <SparklesIconSolid class="w-3.5 h-3.5 text-amber-300" />
                                Kesiswaan & Bakat Minat
                            </span>
                            <span v-if="activeYear" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-100 border border-emerald-400/20">
                                TA {{ activeYear.name }} ({{ activeYear.semester }})
                            </span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight">
                            Ekstrakurikuler Sekolah
                        </h1>
                        <p class="text-teal-100/90 text-sm sm:text-base max-w-2xl leading-relaxed">
                            Pusat manajemen kegiatan ekstrakurikuler, pembinaan pelatih, absensi kehadiran siswa, dan dokumentasi aktivitas bakat minat di unit satuan pendidikan.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            v-if="canManage"
                            @click="openCreateModal"
                            type="button"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-white text-teal-800 hover:bg-teal-50 font-bold text-sm shadow-md shadow-black/10 transition-all transform active:scale-95"
                        >
                            <PlusIcon class="w-5 h-5 stroke-[2.5]" />
                            <span>Tambah Ekskul</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center shrink-0">
                        <SparklesIcon class="w-6 h-6 stroke-[2.2]" />
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.total_activities || 0 }}</div>
                        <div class="text-xs font-semibold text-slate-500">Cabang Ekskul</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-700 flex items-center justify-center shrink-0">
                        <AcademicCapIcon class="w-6 h-6 stroke-[2.2]" />
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.total_members || 0 }}</div>
                        <div class="text-xs font-semibold text-slate-500">Siswa Terdaftar</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
                        <CalendarDaysIcon class="w-6 h-6 stroke-[2.2]" />
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.total_sessions_month || 0 }}</div>
                        <div class="text-xs font-semibold text-slate-500">Sesi Latihan Bulan Ini</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-700 flex items-center justify-center shrink-0">
                        <UserGroupIcon class="w-6 h-6 stroke-[2.2]" />
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.total_instructors || 0 }}</div>
                        <div class="text-xs font-semibold text-slate-500">Pelatih / Pembina</div>
                    </div>
                </div>
            </div>

            <!-- Filters & Search Bar -->
            <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 justify-between">
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <MagnifyingGlassIcon class="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama ekstrakurikuler, pelatih, atau lokasi..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all placeholder:text-slate-400"
                        />
                    </div>

                    <!-- Day Filter -->
                    <div class="flex items-center gap-2 shrink-0">
                        <label class="text-xs font-bold text-slate-600 hidden sm:inline">Hari:</label>
                        <select
                            v-model="selectedDay"
                            class="px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                        >
                            <option v-for="day in daysList" :key="day" :value="day === 'Semua Hari' ? 'all' : day">
                                {{ day }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Category Pills -->
                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                    <button
                        v-for="cat in categories"
                        :key="cat.key"
                        @click="selectedCategory = cat.key"
                        type="button"
                        class="px-3.5 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition-all duration-150 border"
                        :class="selectedCategory === cat.key
                            ? 'bg-teal-700 text-white border-teal-700 shadow-xs'
                            : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                    >
                        {{ cat.label }}
                    </button>
                </div>
            </div>

            <!-- Activity Cards Grid -->
            <div v-if="filteredActivities.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="item in filteredActivities"
                    :key="item.id"
                    class="bg-white rounded-3xl border border-slate-200/90 shadow-sm hover:shadow-md hover:border-teal-200 transition-all duration-200 overflow-hidden flex flex-col group"
                >
                    <!-- Card Top Image / Cover Area -->
                    <div class="relative h-44 bg-slate-100 overflow-hidden">
                        <img
                            v-if="item.cover_image_url && !imageErrors.has(item.id)"
                            :src="item.cover_image_url"
                            :alt="item.name"
                            @error="onImageError(item.id)"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        />
                        <div v-else class="w-full h-full bg-gradient-to-tr from-teal-800 to-emerald-600 flex flex-col items-center justify-center p-4 text-white">
                            <SparklesIcon class="w-12 h-12 text-white/50 mb-1" />
                            <span class="text-sm font-black tracking-wide uppercase text-white/80">{{ item.name }}</span>
                        </div>

                        <!-- Badges Overlay -->
                        <div class="absolute top-3 left-3 flex flex-wrap gap-1.5 items-center">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-white/95 backdrop-blur-md shadow-xs border" :class="getCategoryColor(item.category)">
                                {{ item.category_label }}
                            </span>
                            <span v-if="item.is_mandatory" class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-600 text-white shadow-xs">
                                Wajib
                            </span>
                        </div>

                        <!-- Status Badge -->
                        <div class="absolute top-3 right-3">
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border shadow-xs"
                                :class="item.status === 'active' 
                                    ? 'bg-emerald-500 text-white border-emerald-400' 
                                    : 'bg-slate-500 text-white border-slate-400'"
                            >
                                {{ item.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        <!-- Quota Progress Bar at Bottom of Image -->
                        <div class="absolute bottom-0 inset-x-0 bg-slate-900/60 backdrop-blur-xs px-3 py-1 flex items-center justify-between text-[11px] text-white font-semibold">
                            <div class="flex items-center gap-1.5">
                                <UserGroupIcon class="w-3.5 h-3.5 text-teal-300" />
                                <span>{{ item.active_members_count || 0 }} / {{ item.max_quota }} Siswa</span>
                            </div>
                            <span class="text-teal-200 text-[10px]">
                                {{ Math.round(((item.active_members_count || 0) / item.max_quota) * 100) }}% Kuota
                            </span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div class="space-y-2.5">
                            <h3 class="font-black text-lg text-slate-800 leading-snug group-hover:text-teal-700 transition-colors">
                                {{ item.name }}
                            </h3>
                            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                {{ item.description || 'Kegiatan ekstrakurikuler pembinaan minat bakat siswa secara berkala.' }}
                            </p>

                            <!-- Meta Info Grid -->
                            <div class="space-y-1.5 pt-2 border-t border-slate-100 text-xs">
                                <div class="flex items-center gap-2 text-slate-600">
                                    <ClockIcon class="w-4 h-4 text-teal-600 shrink-0" />
                                    <span class="font-semibold">{{ item.formatted_schedule }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-slate-600">
                                    <MapPinIcon class="w-4 h-4 text-rose-500 shrink-0" />
                                    <span class="truncate">{{ item.room?.name || item.location_name || 'Area Kampus Sekolah' }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-slate-600">
                                    <UserGroupIcon class="w-4 h-4 text-indigo-500 shrink-0" />
                                    <span class="truncate">
                                        Pelatih: 
                                        <span class="font-bold text-slate-800">
                                            {{ item.instructors?.length > 0 ? item.instructors.map(i => i.user?.name).join(', ') : 'Belum ditentukan' }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer Actions -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                            <Link
                                :href="route('extracurricular.show', item.id)"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs shadow-xs transition-all active:scale-95"
                            >
                                <span>Buka Sesi & Presensi</span>
                                <ChevronRightIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                            </Link>

                            <div v-if="canManage" class="flex items-center gap-1">
                                <button
                                    @click="openEditModal(item)"
                                    type="button"
                                    class="p-2 rounded-xl text-slate-500 hover:text-teal-700 hover:bg-teal-50 border border-slate-200 transition-colors"
                                    title="Edit Ekskul"
                                >
                                    <PencilSquareIcon class="w-4 h-4" />
                                </button>
                                <button
                                    @click="confirmDelete(item)"
                                    type="button"
                                    class="p-2 rounded-xl text-slate-500 hover:text-rose-600 hover:bg-rose-50 border border-slate-200 transition-colors"
                                    title="Hapus Ekskul"
                                >
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-3xl p-12 text-center border border-slate-200/80 shadow-xs space-y-4">
                <div class="w-16 h-16 rounded-full bg-teal-50 text-teal-600 mx-auto flex items-center justify-center">
                    <SparklesIcon class="w-8 h-8 stroke-[2]" />
                </div>
                <div class="space-y-1">
                    <h3 class="font-black text-lg text-slate-800">Tidak Ada Kegiatan Ekstrakurikuler</h3>
                    <p class="text-sm text-slate-500 max-w-md mx-auto">
                        Belum ada kegiatan ekstrakurikuler yang sesuai dengan kriteria filter atau pencarian Anda.
                    </p>
                </div>
                <button
                    v-if="canManage"
                    @click="openCreateModal"
                    type="button"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-teal-700 text-white font-bold text-xs hover:bg-teal-800 transition-all shadow-xs"
                >
                    <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                    <span>Buat Ekstrakurikuler Sekarang</span>
                </button>
            </div>
        </div>

        <!-- Modal Tambah / Edit Ekstrakurikuler -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    
                    <!-- Modal Header -->
                    <div class="px-6 py-4 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-teal-100/80 text-teal-800 flex items-center justify-center font-bold">
                                <SparklesIcon class="w-5 h-5 stroke-[2.2]" />
                            </div>
                            <div>
                                <h3 class="font-black text-slate-800 text-base sm:text-lg">
                                    {{ isEditing ? 'Edit Ekstrakurikuler' : 'Tambah Ekstrakurikuler Baru' }}
                                </h3>
                                <p class="text-xs text-slate-500">Lengkapi data cabang kegiatan dan jadwal pembinaan.</p>
                            </div>
                        </div>
                        <button
                            @click="showModal = false"
                            class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                        >
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <form @submit.prevent="submitForm" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                        
                        <!-- Nama & Kategori -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kegiatan *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Contoh: Futsal, Tari Tradisional, Robotika"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                />
                                <div v-if="form.errors.name" class="text-rose-500 text-xs mt-1">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kategori *</label>
                                <select
                                    v-model="form.category"
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                                >
                                    <option value="olahraga">Olahraga & Atletik</option>
                                    <option value="seni_budaya">Seni & Budaya</option>
                                    <option value="keagamaan">Keagamaan & Karakter</option>
                                    <option value="sains_teknologi">Sains & Teknologi</option>
                                    <option value="kepanduan">Kepanduan & Bela Diri</option>
                                    <option value="lainnya">Lainnya</option>
                                </select>
                            </div>
                        </div>

                        <!-- Jadwal: Hari & Jam -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Hari Pelaksanaan *</label>
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

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jam Mulai *</label>
                                <input
                                    v-model="form.start_time"
                                    type="time"
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jam Selesai *</label>
                                <input
                                    v-model="form.end_time"
                                    type="time"
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                />
                            </div>
                        </div>

                        <!-- Lokasi Sarpras & Kuota -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Ruangan / Lokasi Sarpras</label>
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
                                <label class="block text-xs font-bold text-slate-700 mb-1">Lokasi Keterangan (Opsional)</label>
                                <input
                                    v-model="form.location_name"
                                    type="text"
                                    placeholder="Misal: Lapangan Utama / Aula Al-Ikhlas"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                />
                            </div>
                        </div>

                        <!-- Kuota & Pembatasan -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Batas Kuota Siswa *</label>
                                <input
                                    v-model.number="form.max_quota"
                                    type="number"
                                    min="1"
                                    max="500"
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Batasan Gender</label>
                                <select
                                    v-model="form.gender_restriction"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                                >
                                    <option value="all">Semua Siswa</option>
                                    <option value="male_only">Khusus Putra (Ikhwan)</option>
                                    <option value="female_only">Khusus Putri (Akhwat)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Sifat Ekskul</label>
                                <div class="flex items-center gap-2 pt-2">
                                    <input
                                        v-model="form.is_mandatory"
                                        type="checkbox"
                                        id="mandatory_cb"
                                        class="w-4 h-4 text-teal-600 rounded-sm border-slate-300 focus:ring-teal-500"
                                    />
                                    <label for="mandatory_cb" class="text-xs font-semibold text-slate-700 cursor-pointer">
                                        Wajib (seperti Pramuka)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Penugasan Pelatih Awal (Jika Tambah Baru) -->
                        <div v-if="!isEditing">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Tugaskan Pelatih / Pembina Awal</label>
                            <select
                                v-model="form.instructor_ids"
                                multiple
                                class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white h-24"
                            >
                                <option v-for="coach in availableInstructors" :key="coach.id" :value="coach.id">
                                    {{ coach.name }} ({{ coach.phone || 'No HP -' }})
                                </option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">Tahan tombol Ctrl (Windows) untuk memilih lebih dari 1 pelatih. Akun pelatih luar juga dapat dibuat nanti di halaman detail.</p>
                        </div>

                        <!-- Deskripsi -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Kegiatan</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                placeholder="Jelaskan tujuan, silabus singkat, atau target kompetisi kegiatan ekstrakurikuler ini..."
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                            ></textarea>
                        </div>

                        <!-- Foto Sampul (Canvas Auto-Compression) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Foto Sampul Ekskul (Otomatis Terkompresi)</label>
                            <div class="flex items-center gap-4">
                                <div class="w-24 h-24 rounded-2xl border-2 border-dashed border-slate-200 overflow-hidden bg-slate-50 flex items-center justify-center shrink-0 relative">
                                    <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover" />
                                    <PhotoIcon v-else class="w-8 h-8 text-slate-300" />
                                    <div v-if="isCompressing" class="absolute inset-0 bg-white/80 flex items-center justify-center">
                                        <div class="w-5 h-5 border-2 border-teal-600 border-t-transparent rounded-full animate-spin"></div>
                                    </div>
                                </div>

                                <div class="space-y-1.5 flex-1">
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept="image/*"
                                        @change="handlePhotoSelect"
                                        class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100"
                                    />
                                    <div v-if="photoSizeInfo" class="flex items-center gap-1.5 text-xs text-emerald-700 font-semibold">
                                        <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                                        <span>{{ photoSizeInfo }}</span>
                                        <button @click="removePhoto" type="button" class="ml-2 text-rose-500 hover:underline">Hapus</button>
                                    </div>
                                    <p class="text-[11px] text-slate-400">
                                        Resolusi kamera HP otomatis dikompresi ke 1280px agar upload instan dan hemat memori server.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                            <button
                                @click="showModal = false"
                                type="button"
                                class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing || isCompressing"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-teal-700 text-white font-bold text-xs hover:bg-teal-800 shadow-xs transition-all disabled:opacity-50"
                            >
                                <span v-if="form.processing">Menyimpan...</span>
                                <span v-else>{{ isEditing ? 'Simpan Perubahan' : 'Buat Ekstrakurikuler' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
