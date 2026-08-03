<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MobileAppShell from '@/Layouts/MobileAppShell.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    ClipboardDocumentCheckIcon, PlusIcon, PhotoIcon, CameraIcon,
    TrashIcon, ClockIcon, MapPinIcon, CalendarIcon, FolderIcon,
    CheckCircleIcon, XMarkIcon, ExclamationCircleIcon, SparklesIcon,
    DocumentTextIcon, TagIcon, FingerPrintIcon, CalendarDaysIcon,
    MagnifyingGlassIcon, ArrowPathIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    todayActivities: Array,
    historyLogs: Array,
    stats: Object,
    selectedDate: String,
    categories: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const isMobile = computed(() => typeof window !== 'undefined' && window.innerWidth < 768);

// Active Tab for Main View: 'today' or 'history'
const activeTab = ref('today');

// Modal State for Add Activity
const showFormModal = ref(false);
const photoPreviewUrl = ref(null);
const fileInputRef = ref(null);

const form = useForm({
    title: '',
    category: 'tugas_tambahan',
    activity_date: props.selectedDate || new Date().toISOString().split('T')[0],
    activity_time: new Date().toTimeString().split(' ')[0].substring(0, 5),
    description: '',
    photo: null,
    location_name: '',
});

const openFormModal = () => {
    form.reset();
    form.activity_date = new Date().toISOString().split('T')[0];
    form.activity_time = new Date().toTimeString().split(' ')[0].substring(0, 5);
    photoPreviewUrl.value = null;
    showFormModal.value = true;
};

const closeFormModal = () => {
    showFormModal.value = false;
};

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        photoPreviewUrl.value = URL.createObjectURL(file);
    }
};

const submitForm = () => {
    form.post(route('employee.activity-logs.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            closeFormModal();
            form.reset();
            photoPreviewUrl.value = null;
        }
    });
};

// Search & Category Filter
const searchQuery = ref('');
const filterCategory = ref('');

const filteredTodayActivities = computed(() => {
    if (!props.todayActivities) return [];
    return props.todayActivities.filter(item => {
        const matchesSearch = !searchQuery.value || item.title.toLowerCase().includes(searchQuery.value.toLowerCase()) || (item.description && item.description.toLowerCase().includes(searchQuery.value.toLowerCase()));
        const matchesCategory = !filterCategory.value || item.category === filterCategory.value;
        return matchesSearch && matchesCategory;
    });
});

const filteredHistoryLogs = computed(() => {
    if (!props.historyLogs) return [];
    return props.historyLogs.filter(item => {
        const matchesSearch = !searchQuery.value || item.title.toLowerCase().includes(searchQuery.value.toLowerCase()) || (item.description && item.description.toLowerCase().includes(searchQuery.value.toLowerCase()));
        const matchesCategory = !filterCategory.value || item.category === filterCategory.value;
        return matchesSearch && matchesCategory;
    });
});

// Delete confirmation
const itemToDelete = ref(null);
const showDeleteConfirm = ref(false);
const deleteForm = useForm({});

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteConfirm.value = true;
};

const executeDelete = () => {
    if (!itemToDelete.value) return;
    deleteForm.delete(route('employee.activity-logs.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirm.value = false;
            itemToDelete.value = null;
        }
    });
};

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
    <Head title="Giat Tugas & Logbook Kerja Harian" />

    <component :is="isMobile ? MobileAppShell : AuthenticatedLayout">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-teal-50 text-teal-700 border border-teal-200">
                            Logbook SDM
                        </span>
                        <span class="text-xs text-slate-400">• {{ selectedDate }}</span>
                    </div>
                    <h2 class="font-extrabold text-2xl text-slate-800 leading-tight mt-1 flex items-center gap-2">
                        <ClipboardDocumentCheckIcon class="w-7 h-7 text-teal-600 stroke-[2]" />
                        Giat Tugas & Logbook Harian
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Catat dan dokumentasikan kegiatan operasional & tugas tambahan Anda secara harian.</p>
                </div>

                <button
                    @click="openFormModal"
                    type="button"
                    class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 active:scale-95 text-white rounded-xl font-bold shadow-md shadow-teal-600/20 transition text-xs sm:text-sm flex items-center justify-center gap-2 shrink-0"
                >
                    <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                    <span>Catat Giat Tugas</span>
                </button>
            </div>
        </template>

        <div class="py-4 sm:py-6 max-w-7xl mx-auto space-y-6 px-3 sm:px-6">

            <!-- STATS CARDS ROW (CLEAN HARMONIOUS LIGHT THEME) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <!-- Card 1: Giat Hari Ini -->
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] sm:text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Giat Hari Ini</span>
                        <div class="w-8 h-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center border border-teal-100">
                            <ClipboardDocumentCheckIcon class="w-4 h-4 stroke-[2]" />
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ stats?.total_today || 0 }}</span>
                        <span class="text-xs font-semibold text-slate-500">Kegiatan</span>
                    </div>
                    <p class="text-[11px] text-teal-600 font-medium flex items-center gap-1">
                        <CheckCircleIcon class="w-3.5 h-3.5" />
                        Terpublikasi otomatis
                    </p>
                </div>

                <!-- Card 2: Status Presensi -->
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] sm:text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Status Absensi</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                            <FingerPrintIcon class="w-4 h-4 stroke-[2]" />
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-xl font-black text-slate-900 tracking-tight">Terdata</span>
                    </div>
                    <p class="text-[11px] text-slate-500 font-medium">
                        Presensi & Giat Terhubung
                    </p>
                </div>

                <!-- Card 3: Riwayat Total -->
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] sm:text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Riwayat Logbook</span>
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100">
                            <CalendarDaysIcon class="w-4 h-4 stroke-[2]" />
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ historyLogs?.length || 0 }}</span>
                        <span class="text-xs font-semibold text-slate-500">Record</span>
                    </div>
                    <p class="text-[11px] text-indigo-600 font-medium">
                        Total Dokumentasi
                    </p>
                </div>

                <!-- Card 4: Jabatan / Role -->
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] sm:text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Jabatan / Role</span>
                        <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100">
                            <TagIcon class="w-4 h-4 stroke-[2]" />
                        </div>
                    </div>
                    <div class="truncate">
                        <span class="text-sm font-extrabold text-slate-800 capitalize truncate block">{{ user.roles[0]?.name?.replace('_', ' ') || 'SDM Namira' }}</span>
                    </div>
                    <p class="text-[11px] text-purple-600 font-medium truncate">
                        {{ user.name }}
                    </p>
                </div>
            </div>

            <!-- MAIN WORKSPACE CONTAINER CARD -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-4 sm:p-6 space-y-6">
                
                <!-- Toolbar & Navigation Tabs -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                    <!-- Tabs -->
                    <div class="flex items-center gap-2 bg-slate-100/80 p-1 rounded-xl w-fit">
                        <button
                            @click="activeTab = 'today'"
                            type="button"
                            class="px-4 py-2 rounded-lg text-xs font-bold transition-all"
                            :class="activeTab === 'today' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                        >
                            Giat Hari Ini ({{ todayActivities?.length || 0 }})
                        </button>
                        <button
                            @click="activeTab = 'history'"
                            type="button"
                            class="px-4 py-2 rounded-lg text-xs font-bold transition-all"
                            :class="activeTab === 'history' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                        >
                            Riwayat Sebelumnya ({{ historyLogs?.length || 0 }})
                        </button>
                    </div>

                    <!-- Search & Filter Controls -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="relative flex-1 sm:flex-initial">
                            <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari giat tugas..."
                                class="w-full sm:w-56 pl-9 pr-3 py-1.5 rounded-xl border-slate-200 text-xs focus:border-teal-600 focus:ring-teal-500/20"
                            />
                        </div>
                        <select
                            v-model="filterCategory"
                            class="py-1.5 rounded-xl border-slate-200 text-xs focus:border-teal-600 focus:ring-teal-500/20"
                        >
                            <option value="">Semua Kategori</option>
                            <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <button
                            @click="openFormModal"
                            type="button"
                            class="px-3 py-1.5 bg-teal-600 text-white rounded-xl text-xs font-bold hover:bg-teal-700 transition flex items-center gap-1.5 shrink-0"
                        >
                            <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                            <span>Tambah Giat</span>
                        </button>
                    </div>
                </div>

                <!-- TAB 1: TODAY'S ACTIVITIES -->
                <div v-if="activeTab === 'today'" class="space-y-4">
                    <!-- Empty State -->
                    <div v-if="filteredTodayActivities.length === 0" class="py-12 px-4 text-center space-y-3 bg-slate-50/60 rounded-2xl border border-dashed border-slate-200">
                        <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 border border-teal-100 mx-auto flex items-center justify-center">
                            <ClipboardDocumentCheckIcon class="w-6 h-6 stroke-[2]" />
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 text-sm">Belum Ada Giat Tugas Dicatat Hari Ini</h4>
                            <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">
                                Selesai mengerjakan tugas tambahan, pembersihan, perbaikan, atau tugas piket? Klik tombol di bawah untuk mencatat & mengunggah bukti foto.
                            </p>
                        </div>
                        <button
                            @click="openFormModal"
                            type="button"
                            class="px-4 py-2 bg-teal-600 text-white text-xs font-bold rounded-xl hover:bg-teal-700 transition inline-flex items-center gap-1.5 shadow-sm"
                        >
                            <PlusIcon class="w-4 h-4" />
                            <span>Catat Giat Sekarang</span>
                        </button>
                    </div>

                    <!-- Cards Grid -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="item in filteredTodayActivities"
                            :key="item.id"
                            class="bg-slate-50/50 rounded-2xl border border-slate-200/80 p-4 hover:bg-white hover:shadow-md transition-all duration-200 space-y-3 flex flex-col justify-between"
                        >
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border" :class="categoryBadgeClass(item.category)">
                                        {{ categories[item.category] || item.category }}
                                    </span>
                                    <span class="text-xs font-mono text-slate-400 flex items-center gap-1">
                                        <ClockIcon class="w-3.5 h-3.5" />
                                        {{ item.activity_time ? item.activity_time.substring(0, 5) : '-' }} WIB
                                    </span>
                                </div>

                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm leading-snug">{{ item.title }}</h4>
                                    <p v-if="item.description" class="text-xs text-slate-600 mt-1 line-clamp-2 leading-relaxed">{{ item.description }}</p>
                                </div>

                                <div v-if="item.photo_path" class="pt-1">
                                    <div
                                        @click="openPhotoLightbox('/' + item.photo_path)"
                                        class="relative h-44 rounded-xl overflow-hidden border border-slate-200 group cursor-pointer bg-slate-900"
                                    >
                                        <img :src="'/' + item.photo_path" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 opacity-90 group-hover:opacity-100" />
                                        <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-semibold gap-1.5">
                                            <PhotoIcon class="w-4 h-4" />
                                            <span>Lihat Foto Bukti</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                                <span v-if="item.location_name" class="text-slate-500 text-[11px] font-medium flex items-center gap-1">
                                    <MapPinIcon class="w-3.5 h-3.5 text-teal-600 shrink-0" />
                                    {{ item.location_name }}
                                </span>
                                <span v-else class="text-teal-700 font-bold text-[11px] flex items-center gap-1">
                                    <CheckCircleIcon class="w-3.5 h-3.5" />
                                    Terpublikasi
                                </span>

                                <button
                                    @click="confirmDelete(item)"
                                    type="button"
                                    class="p-1 text-slate-400 hover:text-rose-600 rounded-lg transition"
                                    title="Hapus"
                                >
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: HISTORY LOGS -->
                <div v-if="activeTab === 'history'" class="space-y-4">
                    <div v-if="filteredHistoryLogs.length === 0" class="py-12 text-center text-slate-400 text-xs bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                        Tidak ada riwayat giat tugas ditemukan.
                    </div>

                    <div v-else class="divide-y divide-slate-100 border border-slate-200/80 rounded-2xl overflow-hidden">
                        <div
                            v-for="item in filteredHistoryLogs"
                            :key="item.id"
                            class="p-4 hover:bg-slate-50/80 transition flex items-center justify-between gap-4"
                        >
                            <div class="flex items-center gap-3.5">
                                <div
                                    v-if="item.photo_path"
                                    @click="openPhotoLightbox('/' + item.photo_path)"
                                    class="w-12 h-12 rounded-xl overflow-hidden border border-slate-200 shrink-0 cursor-pointer"
                                >
                                    <img :src="'/' + item.photo_path" class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="w-12 h-12 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                                    <FolderIcon class="w-5 h-5 stroke-[1.8]" />
                                </div>

                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-slate-800 text-sm">{{ item.title }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase border" :class="categoryBadgeClass(item.category)">
                                            {{ categories[item.category] || item.category }}
                                        </span>
                                    </div>
                                    <p v-if="item.description" class="text-xs text-slate-500 line-clamp-1">{{ item.description }}</p>
                                    <p class="text-[11px] text-slate-400 font-mono">
                                        {{ item.activity_date }} • {{ item.activity_time ? item.activity_time.substring(0, 5) : '-' }} WIB
                                    </p>
                                </div>
                            </div>

                            <button
                                @click="confirmDelete(item)"
                                type="button"
                                class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg transition"
                            >
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- FLOATING ACTION BUTTON (FAB) FOR MOBILE USERS -->
        <div class="md:hidden fixed bottom-20 right-4 z-40">
            <button
                @click="openFormModal"
                type="button"
                class="w-14 h-14 bg-teal-600 text-white rounded-full shadow-2xl flex items-center justify-center active:scale-95 transition-all border-2 border-white"
                title="Catat Giat Tugas"
            >
                <PlusIcon class="w-7 h-7 stroke-[2.5]" />
            </button>
        </div>

        <!-- FORM MODAL (CLEAN UNIFIED ELEGANT LIGHT MODAL FOR BOTH WEB & MOBILE) -->
        <Teleport to="body">
            <div v-if="showFormModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-100 transform transition-all animate-in fade-in zoom-in-95 duration-200">
                    
                    <!-- Modal Header -->
                    <div class="px-6 py-4 bg-teal-700 text-white flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <ClipboardDocumentCheckIcon class="w-5 h-5 text-teal-200" />
                            <h3 class="font-bold text-base">Form Giat Tugas Harian</h3>
                        </div>
                        <button @click="closeFormModal" class="text-teal-200 hover:text-white p-1 rounded-lg">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form @submit.prevent="submitForm" class="p-6 space-y-4">
                        
                        <!-- Judul Kegiatan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Judul / Nama Kegiatan <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                placeholder="Contoh: Menata Buku Perpustakaan, Perbaikan Kunci Door..."
                                class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-teal-600 focus:ring-teal-500/20"
                            />
                            <p v-if="form.errors.title" class="text-xs text-rose-600 mt-1 font-medium">{{ form.errors.title }}</p>
                        </div>

                        <!-- Kategori & Waktu -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Kategori Giat <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="form.category"
                                    required
                                    class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-teal-600 focus:ring-teal-500/20"
                                >
                                    <option v-for="(label, key) in categories" :key="key" :value="key">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Jam Pelaksanaan
                                </label>
                                <input
                                    v-model="form.activity_time"
                                    type="time"
                                    class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-teal-600 focus:ring-teal-500/20"
                                />
                            </div>
                        </div>

                        <!-- Photo Input / Camera Dropzone -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Foto Bukti Dokumentasi
                            </label>
                            
                            <div
                                @click="fileInputRef.click()"
                                class="w-full h-32 rounded-2xl border-2 border-dashed border-slate-300 hover:border-teal-500 transition-colors bg-slate-50 flex flex-col items-center justify-center cursor-pointer overflow-hidden relative group"
                            >
                                <img v-if="photoPreviewUrl" :src="photoPreviewUrl" class="w-full h-full object-cover" />
                                <div v-else class="flex flex-col items-center justify-center text-slate-400 group-hover:text-teal-600 transition-colors">
                                    <CameraIcon class="w-8 h-8 stroke-[1.5]" />
                                    <span class="text-xs font-bold mt-1">Upload / Ambil Foto (Kamera atau Galeri)</span>
                                    <span class="text-[10px] text-slate-400">Pilih dari kamera atau galeri HP (Maks 5MB)</span>
                                </div>
                            </div>
                            <input
                                ref="fileInputRef"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handleFileChange"
                            />
                            <p v-if="form.errors.photo" class="text-xs text-rose-600 font-medium">{{ form.errors.photo }}</p>
                        </div>

                        <!-- Deskripsi -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Deskripsi / Catatan Ringkas
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                placeholder="Rincian hasil kegiatan (opsional)..."
                                class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-teal-600 focus:ring-teal-500/20 resize-none"
                            ></textarea>
                        </div>

                        <!-- Lokasi -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Lokasi Pelaksanaan
                            </label>
                            <input
                                v-model="form.location_name"
                                type="text"
                                placeholder="Contoh: Gedung SD Lt 2, Area Parkir..."
                                class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-teal-600 focus:ring-teal-500/20"
                            />
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="closeFormModal"
                                class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold hover:bg-slate-200 transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold shadow-md shadow-teal-600/30 transition disabled:opacity-50 flex items-center gap-1.5 active:scale-95"
                            >
                                <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan & Publikasikan' }}</span>
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </Teleport>

        <!-- DELETE CONFIRM MODAL -->
        <Teleport to="body">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-sm w-full p-6 text-center space-y-4 shadow-xl border border-slate-100">
                    <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 mx-auto flex items-center justify-center">
                        <ExclamationCircleIcon class="w-7 h-7" />
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm">Hapus Catatan Giat Tugas?</h4>
                        <p class="text-xs text-slate-500 mt-1">Data dan dokumentasi foto ini akan dihapus dari logbook pribadi Anda.</p>
                    </div>
                    <div class="flex items-center justify-center gap-2 pt-2">
                        <button @click="showDeleteConfirm = false" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold">Batal</button>
                        <button @click="executeDelete" :disabled="deleteForm.processing" class="px-5 py-2 bg-rose-600 text-white rounded-xl text-xs font-bold">Ya, Hapus</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- LIGHTBOX PHOTO MODAL -->
        <Teleport to="body">
            <div v-if="activePhotoUrl" @click="activePhotoUrl = null" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4 cursor-pointer">
                <div class="relative max-w-3xl max-h-[85vh] overflow-hidden rounded-3xl shadow-2xl">
                    <img :src="activePhotoUrl" class="w-full h-full object-contain max-h-[85vh]" />
                    <button @click.stop="activePhotoUrl = null" class="absolute top-3 right-3 p-2 bg-black/50 text-white rounded-full hover:bg-black">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </Teleport>

    </component>
</template>
