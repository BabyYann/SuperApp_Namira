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
    FunnelIcon, MagnifyingGlassIcon, ArrowPathIcon
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

// Desktop Fast-Form / Mobile Form
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
            form.reset();
            form.activity_date = new Date().toISOString().split('T')[0];
            form.activity_time = new Date().toTimeString().split(' ')[0].substring(0, 5);
            photoPreviewUrl.value = null;
            if (showMobileModal.value) showMobileModal.value = false;
        }
    });
};

// Mobile Modal State
const showMobileModal = ref(false);

// Filter & Search inside personal page
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
        case 'kebersihan': return 'bg-emerald-50 text-emerald-700 border-emerald-200/80';
        case 'keamanan': return 'bg-blue-50 text-blue-700 border-blue-200/80';
        case 'pemeliharaan': return 'bg-amber-50 text-amber-700 border-amber-200/80';
        case 'layanan_admin': return 'bg-purple-50 text-purple-700 border-purple-200/80';
        case 'piket': return 'bg-indigo-50 text-indigo-700 border-indigo-200/80';
        case 'tugas_tambahan': return 'bg-teal-50 text-teal-700 border-teal-200/80';
        default: return 'bg-slate-100 text-slate-700 border-slate-200/80';
    }
};

const categoryIconBg = (catKey) => {
    switch (catKey) {
        case 'kebersihan': return 'bg-emerald-600';
        case 'keamanan': return 'bg-blue-600';
        case 'pemeliharaan': return 'bg-amber-600';
        case 'layanan_admin': return 'bg-purple-600';
        case 'piket': return 'bg-indigo-600';
        case 'tugas_tambahan': return 'bg-teal-600';
        default: return 'bg-slate-600';
    }
};
</script>

<template>
    <Head title="Giat Tugas & Logbook Kerja Harian" />

    <component :is="isMobile ? MobileAppShell : AuthenticatedLayout">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-teal-50 text-teal-700 border border-teal-200">
                            Logbook SDM
                        </span>
                        <span class="text-xs text-slate-400">• {{ selectedDate }}</span>
                    </div>
                    <h2 class="font-extrabold text-2xl text-slate-800 tracking-tight mt-1 flex items-center gap-2">
                        <ClipboardDocumentCheckIcon class="w-7 h-7 text-emerald-600 stroke-[2]" />
                        Giat Tugas & Logbook Harian
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Dokumentasikan seluruh tugas tambahan, pemeliharaan, dan operasional kerja Anda.</p>
                </div>

                <div v-if="isMobile">
                    <button
                        @click="showMobileModal = true"
                        type="button"
                        class="w-full py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold rounded-2xl shadow-lg shadow-emerald-600/20 text-xs flex items-center justify-center gap-2 active:scale-95 transition"
                    >
                        <PlusIcon class="w-4 h-4 stroke-[3]" />
                        <span>Catat Giat Tugas Sekarang</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6 px-4 sm:px-6">

            <!-- KPI STATS CARDS ROW (DESKTOP & MOBILE RESPONSIVE) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Giat Hari Ini -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-all space-y-2 relative overflow-hidden group">
                    <div class="absolute -right-3 -bottom-3 w-16 h-16 bg-emerald-500/5 rounded-full group-hover:scale-150 transition-transform"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Giat Hari Ini</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                            <ClipboardDocumentCheckIcon class="w-5 h-5 stroke-[2]" />
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900 tracking-tight">{{ stats?.total_today || 0 }}</span>
                        <span class="text-xs font-semibold text-slate-500">Kegiatan</span>
                    </div>
                    <p class="text-[11px] text-emerald-600 font-medium flex items-center gap-1">
                        <CheckCircleIcon class="w-3.5 h-3.5" />
                        Terpublikasi otomatis
                    </p>
                </div>

                <!-- Card 2: Status Presensi -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-all space-y-2 relative overflow-hidden group">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Status Absensi</span>
                        <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center border border-teal-100">
                            <FingerPrintIcon class="w-5 h-5 stroke-[2]" />
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-xl font-black text-slate-900 tracking-tight">Terdata</span>
                    </div>
                    <p class="text-[11px] text-slate-500 font-medium">
                        Presensi & Giat Sinkron
                    </p>
                </div>

                <!-- Card 3: Total Bulan Ini -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-all space-y-2 relative overflow-hidden group">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Riwayat Logbook</span>
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100">
                            <CalendarDaysIcon class="w-5 h-5 stroke-[2]" />
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900 tracking-tight">{{ historyLogs?.length || 0 }}</span>
                        <span class="text-xs font-semibold text-slate-500">Record</span>
                    </div>
                    <p class="text-[11px] text-indigo-600 font-medium">
                        Riwayat Terbaru
                    </p>
                </div>

                <!-- Card 4: Kategori Pegawai -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-all space-y-2 relative overflow-hidden group">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Jabatan / Role</span>
                        <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100">
                            <TagIcon class="w-5 h-5 stroke-[2]" />
                        </div>
                    </div>
                    <div class="truncate">
                        <span class="text-base font-extrabold text-slate-800 capitalize truncate block">{{ user.roles[0]?.name?.replace('_', ' ') || 'SDM Namira' }}</span>
                    </div>
                    <p class="text-[11px] text-purple-600 font-medium truncate">
                        {{ user.name }}
                    </p>
                </div>
            </div>

            <!-- TWO-COLUMN MAIN DESKTOP DASHBOARD -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- MAIN CONTENT (COL 8): Today's Activity Stream & History -->
                <div class="lg:col-span-8 space-y-6">
                    
                    <!-- Today's Activity Container Card -->
                    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                        
                        <!-- Header & Search Toolbar -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                            <div>
                                <h3 class="font-extrabold text-lg text-slate-900 tracking-tight flex items-center gap-2">
                                    <CalendarIcon class="w-5 h-5 text-emerald-600" />
                                    Linimasa Giat Tugas Hari Ini
                                </h3>
                                <p class="text-xs text-slate-400">Daftar kegiatan yang telah Anda publikasikan pada {{ selectedDate }}</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <div class="relative">
                                    <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Cari giat hari ini..."
                                        class="pl-8 pr-3 py-1.5 rounded-xl border-slate-200 text-xs focus:border-emerald-600 focus:ring-emerald-500/20 w-44"
                                    />
                                </div>
                                <select
                                    v-model="filterCategory"
                                    class="py-1.5 rounded-xl border-slate-200 text-xs focus:border-emerald-600 focus:ring-emerald-500/20"
                                >
                                    <option value="">Semua Kategori</option>
                                    <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="filteredTodayActivities.length === 0" class="py-12 px-4 text-center space-y-3 bg-slate-50/60 rounded-2xl border border-dashed border-slate-200">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 mx-auto flex items-center justify-center">
                                <ClipboardDocumentCheckIcon class="w-6 h-6 stroke-[2]" />
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-800 text-sm">Belum Ada Giat Tugas Dicatat Hari Ini</h4>
                                <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">
                                    Setelah menyelesaikan tugas tambahan, pembersihan area, perbaikan sarana, atau tugas piket, langsung simpan dokumentasinya melalui form di sebelah kanan.
                                </p>
                            </div>
                        </div>

                        <!-- Activity Cards Stream -->
                        <div v-else class="space-y-4">
                            <div
                                v-for="item in filteredTodayActivities"
                                :key="item.id"
                                class="bg-slate-50/40 rounded-2xl border border-slate-200/70 p-5 hover:bg-white hover:shadow-md transition-all duration-200 space-y-4 group"
                            >
                                <!-- Top Bar: Category Pill & Time -->
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full" :class="categoryIconBg(item.category)"></span>
                                        <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider border" :class="categoryBadgeClass(item.category)">
                                            {{ categories[item.category] || item.category }}
                                        </span>
                                    </div>
                                    <span class="text-xs font-mono text-slate-500 bg-white px-2.5 py-1 rounded-lg border border-slate-200/60 flex items-center gap-1.5 shadow-2xs">
                                        <ClockIcon class="w-3.5 h-3.5 text-slate-400" />
                                        {{ item.activity_time ? item.activity_time.substring(0, 5) : '-' }} WIB
                                    </span>
                                </div>

                                <!-- Activity Content -->
                                <div class="space-y-1.5">
                                    <h4 class="font-extrabold text-slate-900 text-base leading-snug group-hover:text-emerald-700 transition-colors">
                                        {{ item.title }}
                                    </h4>
                                    <p v-if="item.description" class="text-xs text-slate-600 whitespace-pre-line leading-relaxed">
                                        {{ item.description }}
                                    </p>
                                </div>

                                <!-- Photo Documentation Large Preview -->
                                <div v-if="item.photo_path" class="pt-1">
                                    <div
                                        @click="openPhotoLightbox('/' + item.photo_path)"
                                        class="relative max-w-lg h-60 rounded-2xl overflow-hidden border border-slate-200/80 group/photo cursor-pointer bg-slate-900 shadow-sm"
                                    >
                                        <img :src="'/' + item.photo_path" class="w-full h-full object-cover group-hover/photo:scale-105 transition-transform duration-300 opacity-95 group-hover/photo:opacity-100" />
                                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent opacity-0 group-hover/photo:opacity-100 transition-opacity flex items-end p-4">
                                            <div class="text-white text-xs font-bold flex items-center gap-2">
                                                <PhotoIcon class="w-4 h-4 text-emerald-400" />
                                                <span>Klik untuk Pratinjau Foto Penuh</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Footer Actions & Meta -->
                                <div class="pt-3 border-t border-slate-200/60 flex items-center justify-between text-xs">
                                    <span v-if="item.location_name" class="text-slate-500 font-medium flex items-center gap-1.5 bg-white px-2.5 py-1 rounded-lg border border-slate-200/60">
                                        <MapPinIcon class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                        {{ item.location_name }}
                                    </span>
                                    <span v-else class="text-emerald-700 font-extrabold text-[11px] uppercase tracking-wider flex items-center gap-1">
                                        <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                                        Terpublikasi ke Linimasa
                                    </span>

                                    <button
                                        @click="confirmDelete(item)"
                                        type="button"
                                        class="px-2.5 py-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors flex items-center gap-1 font-medium text-xs"
                                    >
                                        <TrashIcon class="w-4 h-4" />
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- History Section Card -->
                    <div v-if="historyLogs && historyLogs.length > 0" class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="font-extrabold text-base text-slate-800 flex items-center gap-2">
                                <DocumentTextIcon class="w-5 h-5 text-slate-500" />
                                Riwayat Giat Tugas Terakhir
                            </h3>
                            <span class="text-xs text-slate-400 font-semibold">{{ historyLogs.length }} catatan</span>
                        </div>

                        <div class="divide-y divide-slate-100">
                            <div
                                v-for="item in historyLogs"
                                :key="item.id"
                                class="py-3.5 hover:bg-slate-50/80 px-2 rounded-xl transition-colors flex items-center justify-between gap-4"
                            >
                                <div class="flex items-center gap-3.5">
                                    <div
                                        v-if="item.photo_path"
                                        @click="openPhotoLightbox('/' + item.photo_path)"
                                        class="w-11 h-11 rounded-xl overflow-hidden border border-slate-200 shrink-0 cursor-pointer shadow-2xs hover:border-emerald-500 transition-colors"
                                    >
                                        <img :src="'/' + item.photo_path" class="w-full h-full object-cover" />
                                    </div>
                                    <div v-else class="w-11 h-11 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                                        <FolderIcon class="w-5 h-5 stroke-[1.8]" />
                                    </div>

                                    <div class="space-y-0.5">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 text-sm hover:text-emerald-700 transition-colors">{{ item.title }}</span>
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase border" :class="categoryBadgeClass(item.category)">
                                                {{ categories[item.category] || item.category }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-400 font-mono">
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

                <!-- SIDE CONTENT (COL 4): Embedded Fast-Form & Guidelines (DESKTOP EMBEDDED) -->
                <div class="lg:col-span-4 hidden lg:block sticky top-6 space-y-5">
                    
                    <!-- Fast Form Card -->
                    <div class="bg-slate-900 text-white rounded-3xl p-6 shadow-xl border border-slate-800 space-y-5 relative overflow-hidden">
                        <div class="absolute -right-8 -top-8 w-28 h-28 bg-emerald-500/10 rounded-full blur-xl pointer-events-none"></div>

                        <!-- Form Title -->
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center">
                                    <PlusIcon class="w-4 h-4 stroke-[3]" />
                                </div>
                                <h3 class="font-extrabold text-base text-white">Catat Giat Baru</h3>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
                                Instan
                            </span>
                        </div>

                        <!-- Form Content -->
                        <form @submit.prevent="submitForm" class="space-y-4">
                            
                            <!-- Judul -->
                            <div>
                                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-300 mb-1">
                                    Judul Kegiatan <span class="text-emerald-400">*</span>
                                </label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    required
                                    placeholder="Misal: Penataan Perpustakaan..."
                                    class="w-full rounded-xl bg-slate-800/90 border-slate-700 text-white placeholder-slate-500 text-xs focus:border-emerald-500 focus:ring-emerald-500/20"
                                />
                                <p v-if="form.errors.title" class="text-xs text-rose-400 mt-1 font-medium">{{ form.errors.title }}</p>
                            </div>

                            <!-- Kategori & Waktu -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-black uppercase tracking-wider text-slate-300 mb-1">
                                        Kategori <span class="text-emerald-400">*</span>
                                    </label>
                                    <select
                                        v-model="form.category"
                                        required
                                        class="w-full rounded-xl bg-slate-800/90 border-slate-700 text-white text-xs focus:border-emerald-500 focus:ring-emerald-500/20"
                                    >
                                        <option v-for="(label, key) in categories" :key="key" :value="key">
                                            {{ label }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-black uppercase tracking-wider text-slate-300 mb-1">
                                        Jam Waktu
                                    </label>
                                    <input
                                        v-model="form.activity_time"
                                        type="time"
                                        class="w-full rounded-xl bg-slate-800/90 border-slate-700 text-white text-xs focus:border-emerald-500 focus:ring-emerald-500/20"
                                    />
                                </div>
                            </div>

                            <!-- Photo Dropzone / Upload -->
                            <div class="space-y-1">
                                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-300">
                                    Dokumentasi Foto Bukti
                                </label>
                                
                                <div
                                    @click="$refs.fileInputRef.click()"
                                    class="w-full h-28 rounded-2xl border-2 border-dashed border-slate-700 hover:border-emerald-500 transition-colors bg-slate-800/50 hover:bg-slate-800 flex flex-col items-center justify-center cursor-pointer overflow-hidden relative group"
                                >
                                    <img v-if="photoPreviewUrl" :src="photoPreviewUrl" class="w-full h-full object-cover" />
                                    <div v-else class="flex flex-col items-center justify-center text-slate-400 group-hover:text-emerald-400 transition-colors">
                                        <CameraIcon class="w-6 h-6 stroke-[1.8]" />
                                        <span class="text-xs font-bold mt-1">Upload / Ambil Foto</span>
                                        <span class="text-[10px] text-slate-500">Maksimal file 5MB</span>
                                    </div>
                                </div>
                                <input
                                    ref="fileInputRef"
                                    type="file"
                                    accept="image/*"
                                    capture="environment"
                                    class="hidden"
                                    @change="handleFileChange"
                                />
                                <p v-if="form.errors.photo" class="text-xs text-rose-400 font-medium">{{ form.errors.photo }}</p>
                            </div>

                            <!-- Deskripsi -->
                            <div>
                                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-300 mb-1">
                                    Catatan / Detail Pekerjaan
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="2"
                                    placeholder="Rincian hasil kegiatan (opsional)..."
                                    class="w-full rounded-xl bg-slate-800/90 border-slate-700 text-white placeholder-slate-500 text-xs focus:border-emerald-500 focus:ring-emerald-500/20 resize-none"
                                ></textarea>
                            </div>

                            <!-- Lokasi -->
                            <div>
                                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-300 mb-1">
                                    Lokasi Pelaksanaan
                                </label>
                                <input
                                    v-model="form.location_name"
                                    type="text"
                                    placeholder="Contoh: Gedung SD Lt 2..."
                                    class="w-full rounded-xl bg-slate-800/90 border-slate-700 text-white placeholder-slate-500 text-xs focus:border-emerald-500 focus:ring-emerald-500/20"
                                />
                            </div>

                            <!-- Submit Button -->
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full py-3 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-xl font-bold text-xs shadow-lg shadow-emerald-500/25 hover:from-emerald-600 hover:to-teal-600 transition disabled:opacity-50 flex items-center justify-center gap-2 active:scale-95"
                            >
                                <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan & Publikasikan' }}</span>
                            </button>

                        </form>
                    </div>

                    <!-- Guidance Info Box -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-2">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-800">
                            <SparklesIcon class="w-4 h-4 text-emerald-600" />
                            <span>Mengapa Mengisi Giat Tugas?</span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Setiap kegiatan yang Anda tuliskan akan langsung masuk ke portofolio kinerja bulanan dan terekam dalam Laporan Rekapitulasi SDM Yayasan Namira.
                        </p>
                    </div>

                </div>

            </div>

        </div>

        <!-- MOBILE SLIDE-UP MODAL FORM -->
        <Teleport to="body">
            <div v-if="showMobileModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-end justify-center p-0 sm:p-4">
                <div class="bg-white rounded-t-3xl sm:rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-100 transform transition-all animate-in slide-in-from-bottom duration-200">
                    
                    <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <ClipboardDocumentCheckIcon class="w-5 h-5 text-emerald-400" />
                            <h3 class="font-bold text-sm">Form Giat Tugas Harian</h3>
                        </div>
                        <button @click="showMobileModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Judul Kegiatan *</label>
                            <input v-model="form.title" type="text" required placeholder="Contoh: Menata Buku Perpustakaan..." class="w-full rounded-xl border-slate-200 text-xs" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kategori *</label>
                                <select v-model="form.category" required class="w-full rounded-xl border-slate-200 text-xs">
                                    <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Waktu</label>
                                <input v-model="form.activity_time" type="time" class="w-full rounded-xl border-slate-200 text-xs" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Foto Dokumentasi</label>
                            <div @click="$refs.fileInputRefMobile.click()" class="w-full h-28 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 flex flex-col items-center justify-center cursor-pointer overflow-hidden relative">
                                <img v-if="photoPreviewUrl" :src="photoPreviewUrl" class="w-full h-full object-cover" />
                                <div v-else class="flex flex-col items-center text-slate-400">
                                    <CameraIcon class="w-6 h-6" />
                                    <span class="text-xs font-bold mt-1">Ambil Foto / Galeri</span>
                                </div>
                            </div>
                            <input ref="fileInputRefMobile" type="file" accept="image/*" capture="environment" class="hidden" @change="handleFileChange" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Detail Pekerjaan</label>
                            <textarea v-model="form.description" rows="2" placeholder="Detail pekerjaan..." class="w-full rounded-xl border-slate-200 text-xs resize-none"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Lokasi</label>
                            <input v-model="form.location_name" type="text" placeholder="Gedung SD..." class="w-full rounded-xl border-slate-200 text-xs" />
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="showMobileModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold">Batal</button>
                            <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-emerald-600 text-white rounded-xl text-xs font-bold">Simpan & Publikasikan</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- DELETE CONFIRM MODAL -->
        <Teleport to="body">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
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
