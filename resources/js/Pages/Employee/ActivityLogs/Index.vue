<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MobileAppShell from '@/Layouts/MobileAppShell.vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    ClipboardDocumentCheckIcon, PlusIcon, PhotoIcon, CameraIcon,
    TrashIcon, ClockIcon, MapPinIcon, CalendarIcon, FolderIcon,
    CheckCircleIcon, XMarkIcon, ExclamationCircleIcon, SparklesIcon,
    DocumentTextIcon, TagIcon, FingerPrintIcon, CalendarDaysIcon,
    MagnifyingGlassIcon, ArrowPathIcon, ChevronRightIcon, ChevronDownIcon,
    AcademicCapIcon, BookOpenIcon, UserGroupIcon, TrophyIcon,
    ShieldCheckIcon, WrenchScrewdriverIcon, CheckIcon, Squares2X2Icon
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

import Pagination from '@/Components/Pagination.vue';
import { compressImageFile } from '@/Utils/imageCompressor';

const props = defineProps({
    todayActivities: Array,
    historyLogs: [Array, Object],
    stats: Object,
    selectedDate: String,
    categories: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const isMobile = computed(() => typeof window !== 'undefined' && window.innerWidth < 768);

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const cleanDate = typeof dateStr === 'string' && dateStr.includes('T') ? dateStr.split('T')[0] : (typeof dateStr === 'string' && dateStr.includes(' ') ? dateStr.split(' ')[0] : dateStr);
    return dayjs(cleanDate).format('dddd, DD MMMM YYYY');
};

const historyTotalCount = computed(() => {
    if (!props.historyLogs) return 0;
    if (typeof props.historyLogs.total === 'number') return props.historyLogs.total;
    if (Array.isArray(props.historyLogs)) return props.historyLogs.length;
    if (Array.isArray(props.historyLogs.data)) return props.historyLogs.data.length;
    return 0;
});

const userRoleTitle = computed(() => {
    if (!user.value) return 'Super Admin';
    if (user.value.staff?.position) return user.value.staff.position;
    const roles = user.value.roles || [];
    if (roles.length > 0) {
        const firstRole = typeof roles[0] === 'string' ? roles[0] : roles[0]?.name;
        if (firstRole) {
            if (firstRole === 'super_admin_yayasan') return 'Super Admin';
            if (firstRole === 'admin_yayasan') return 'Admin Yayasan';
            if (firstRole === 'kepala_sekolah') return 'Kepala Sekolah';
            if (firstRole === 'teacher') return 'Guru';
            if (firstRole === 'staff_yayasan') return 'Staf Yayasan';
            return firstRole.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }
    }
    return 'Super Admin';
});

// Active Tab for Main View: 'today' or 'history'
const activeTab = ref('today');

// Modal State for Add Activity
const showFormModal = ref(false);
const photoPreviewUrl = ref(null);
const galleryInputRef = ref(null);
const cameraInputRef = ref(null);

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
    form.category = filterCategory.value || 'tugas_tambahan';
    form.activity_date = new Date().toISOString().split('T')[0];
    form.activity_time = new Date().toTimeString().split(' ')[0].substring(0, 5);
    photoPreviewUrl.value = null;
    showFormModal.value = true;
};

const closeFormModal = () => {
    showFormModal.value = false;
};

const isCompressing = ref(false);

const handleFileChange = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    try {
        isCompressing.value = true;
        const compressedFile = await compressImageFile(file, 1280, 0.75);
        form.photo = compressedFile;
        photoPreviewUrl.value = URL.createObjectURL(compressedFile);
    } catch (err) {
        console.error('Kompresi foto gagal, menggunakan file asli:', err);
        form.photo = file;
        photoPreviewUrl.value = URL.createObjectURL(file);
    } finally {
        isCompressing.value = false;
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
    const list = Array.isArray(props.historyLogs) ? props.historyLogs : (props.historyLogs?.data || []);
    return list.filter(item => {
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

const getCategoryIcon = (key) => {
    switch (key) {
        case 'akademik': return AcademicCapIcon;
        case 'keagamaan': return BookOpenIcon;
        case 'sosial': return UserGroupIcon;
        case 'olahraga': return TrophyIcon;
        case 'kebersihan': return SparklesIcon;
        case 'keamanan': return ShieldCheckIcon;
        case 'pemeliharaan': return WrenchScrewdriverIcon;
        case 'layanan_admin': return DocumentTextIcon;
        case 'piket': return ClockIcon;
        case 'tugas_tambahan': return ClipboardDocumentCheckIcon;
        default: return TagIcon;
    }
};

const categoryBadgeClass = (catKey) => {
    switch (catKey) {
        case 'akademik': return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'keagamaan': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'sosial': return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'olahraga': return 'bg-orange-50 text-orange-700 border-orange-200';
        case 'kebersihan': return 'bg-teal-50 text-teal-700 border-teal-200';
        case 'keamanan': return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        case 'pemeliharaan': return 'bg-yellow-50 text-yellow-700 border-yellow-200';
        case 'layanan_admin': return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'piket': return 'bg-rose-50 text-rose-700 border-rose-200';
        case 'tugas_tambahan': return 'bg-teal-50 text-teal-700 border-teal-200';
        default: return 'bg-slate-100 text-slate-700 border-slate-200';
    }
};
</script>

<template>
    <Head title="Giat - Aktivitas & Logbook Harian" />

    <component :is="isMobile ? MobileAppShell : AuthenticatedLayout">
        
        <!-- HEADER DESKTOP ONLY -->
        <template #header v-if="!isMobile">
            <div class="flex items-center justify-between w-full max-w-7xl mx-auto">
                <div>
                    <h2 class="font-black text-2xl text-slate-900 tracking-tight leading-tight">
                        Giat
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Kelola kegiatan dan aktivitas harian yayasan.</p>
                </div>

                <button
                    @click="openFormModal"
                    type="button"
                    class="px-4 py-2.5 bg-[#0B7068] hover:bg-[#095c55] active:scale-95 text-white rounded-xl font-bold shadow-sm shadow-teal-900/10 transition text-sm flex items-center justify-center gap-2 shrink-0 cursor-pointer"
                >
                    <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                    <span>Tambah Giat</span>
                </button>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-3.5 sm:space-y-4 md:space-y-5 px-1 sm:px-4 py-1 sm:py-2 md:py-3 w-full min-w-0 box-border">

            <!-- MOBILE TOP TITLE & ACTION HEADER (MOBILE ONLY) -->
            <div class="md:hidden flex items-center justify-between gap-3 pt-0.5 pb-0.5 w-full">
                <div class="min-w-0">
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Giat</h1>
                    <p class="text-[11px] sm:text-xs text-slate-500 mt-0.5 truncate">Kelola kegiatan dan aktivitas harian yayasan.</p>
                </div>
                <button
                    @click="openFormModal"
                    type="button"
                    class="px-3.5 py-2 bg-[#0B7068] hover:bg-[#095c55] active:scale-95 text-white rounded-xl font-bold text-xs shadow-sm flex items-center gap-1.5 shrink-0 transition-all cursor-pointer"
                >
                    <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                    <span>Tambah Giat</span>
                </button>
            </div>

            <!-- STATS CARDS (COMPACT ON MOBILE, RICH TILES ON DESKTOP) -->
            <div class="grid grid-cols-4 gap-1.5 sm:gap-2 md:gap-4 w-full">
                
                <!-- Card 1: 0 Kegiatan -->
                <div class="bg-white rounded-xl sm:rounded-2xl p-2 sm:p-2.5 md:p-4 border border-slate-100 shadow-2xs flex flex-col md:flex-row items-center md:items-center text-center md:text-left justify-between md:justify-start md:gap-3.5 min-w-0 w-full hover:shadow-xs transition-shadow">
                    <div class="w-6 h-6 sm:w-7 sm:h-7 md:w-11 md:h-11 rounded-lg md:rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0 mb-1 md:mb-0">
                        <CalendarDaysIcon class="w-3.5 h-3.5 md:w-5 md:h-5 stroke-[2]" />
                    </div>
                    <div class="min-w-0 w-full flex-1">
                        <div class="flex flex-col md:flex-col justify-center">
                            <span class="text-sm sm:text-base md:text-2xl font-black text-slate-900 tracking-tight leading-none block">
                                {{ stats?.total_today || 0 }}
                            </span>
                            <span class="text-[9px] sm:text-[10px] md:text-xs text-slate-400 font-medium block truncate mt-0.5 md:mt-1">Kegiatan</span>
                            <p class="text-[8px] sm:text-[9px] md:text-xs text-emerald-600 font-semibold flex items-center justify-center md:justify-start gap-1 mt-1 truncate w-full">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                <span class="truncate hidden md:inline">Terpublikasi otomatis</span>
                                <span class="truncate md:hidden">Otomatis</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Terdata (Status Absensi) -->
                <Link
                    :href="route('employee.attendance.index')"
                    class="bg-white rounded-xl sm:rounded-2xl p-2 sm:p-2.5 md:p-4 border border-slate-100 shadow-2xs flex flex-col md:flex-row items-center md:items-center text-center md:text-left justify-between md:justify-start md:gap-3.5 min-w-0 w-full group hover:border-teal-200 hover:shadow-xs transition-all active:scale-95"
                >
                    <div class="w-6 h-6 sm:w-7 sm:h-7 md:w-11 md:h-11 rounded-lg md:rounded-xl bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center shrink-0 mb-1 md:mb-0">
                        <FingerPrintIcon class="w-3.5 h-3.5 md:w-5 md:h-5 stroke-[2]" />
                    </div>
                    <div class="min-w-0 w-full flex-1">
                        <div class="flex flex-col md:flex-col justify-center">
                            <span class="text-xs sm:text-sm md:text-xl font-bold text-slate-800 leading-none block truncate">
                                Terdata
                            </span>
                            <span class="text-[9px] sm:text-[10px] md:text-xs text-slate-400 font-medium block truncate mt-0.5 md:mt-1">Absensi</span>
                            <div class="text-[8px] sm:text-[9px] md:text-xs text-sky-600 group-hover:text-teal-600 font-semibold flex items-center justify-center md:justify-start gap-1 mt-1 truncate w-full">
                                <span>Status</span>
                                <ChevronRightIcon class="w-3 h-3 shrink-0" />
                            </div>
                        </div>
                    </div>
                </Link>

                <!-- Card 3: 0 Logbook -->
                <div class="bg-white rounded-xl sm:rounded-2xl p-2 sm:p-2.5 md:p-4 border border-slate-100 shadow-2xs flex flex-col md:flex-row items-center md:items-center text-center md:text-left justify-between md:justify-start md:gap-3.5 min-w-0 w-full hover:shadow-xs transition-shadow">
                    <div class="w-6 h-6 sm:w-7 sm:h-7 md:w-11 md:h-11 rounded-lg md:rounded-xl bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center shrink-0 mb-1 md:mb-0">
                        <ClipboardDocumentCheckIcon class="w-3.5 h-3.5 md:w-5 md:h-5 stroke-[2]" />
                    </div>
                    <div class="min-w-0 w-full flex-1">
                        <div class="flex flex-col md:flex-col justify-center">
                            <span class="text-sm sm:text-base md:text-2xl font-black text-slate-900 tracking-tight leading-none block">
                                {{ historyTotalCount }}
                            </span>
                            <span class="text-[9px] sm:text-[10px] md:text-xs text-slate-400 font-medium block truncate mt-0.5 md:mt-1">Logbook</span>
                            <p class="text-[8px] sm:text-[9px] md:text-xs text-purple-600 font-semibold flex items-center justify-center md:justify-start gap-1 mt-1 truncate w-full">
                                <span class="w-1.5 h-1.5 rounded-full bg-purple-500 shrink-0"></span>
                                <span class="truncate hidden md:inline">Arsip Pribadi</span>
                                <span class="truncate md:hidden">Arsip</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Super Admin (Jabatan) -->
                <Link
                    :href="route('profile.edit')"
                    class="bg-white rounded-xl sm:rounded-2xl p-2 sm:p-2.5 md:p-4 border border-slate-100 shadow-2xs flex flex-col md:flex-row items-center md:items-center text-center md:text-left justify-between md:justify-start md:gap-3.5 min-w-0 w-full group hover:border-teal-200 hover:shadow-xs transition-all active:scale-95"
                >
                    <div class="w-6 h-6 sm:w-7 sm:h-7 md:w-11 md:h-11 rounded-lg md:rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center shrink-0 mb-1 md:mb-0">
                        <TagIcon class="w-3.5 h-3.5 md:w-5 md:h-5 stroke-[2]" />
                    </div>
                    <div class="min-w-0 w-full flex-1">
                        <div class="flex flex-col md:flex-col justify-center">
                            <span class="text-[10px] sm:text-xs md:text-base font-bold text-slate-800 leading-none block truncate">
                                {{ userRoleTitle }}
                            </span>
                            <span class="text-[9px] sm:text-[10px] md:text-xs text-slate-400 font-medium block truncate mt-0.5 md:mt-1">Jabatan</span>
                            <div class="text-[8px] sm:text-[9px] md:text-xs text-amber-600 group-hover:text-teal-600 font-semibold flex items-center justify-center md:justify-start gap-1 mt-1 truncate w-full">
                                <span>Role</span>
                                <ChevronRightIcon class="w-3 h-3 shrink-0" />
                            </div>
                        </div>
                    </div>
                </Link>

            </div>

            <!-- TABS (GIAT HARI INI vs RIWAYAT SEBELUMNYA - BALANCED WIDTH & CLEAR ACTIVE STATE) -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-2xs overflow-hidden w-full">
                <div class="grid grid-cols-2 border-b border-slate-100">
                    <button
                        @click="activeTab = 'today'"
                        type="button"
                        class="py-2.5 sm:py-3.5 px-2 text-[11px] sm:text-xs md:text-sm font-bold text-center relative transition-all cursor-pointer"
                        :class="activeTab === 'today' ? 'text-[#0B7068]' : 'text-slate-400 hover:text-slate-600'"
                    >
                        <span>Giat Hari Ini ({{ todayActivities?.length || 0 }})</span>
                        <div
                            v-if="activeTab === 'today'"
                            class="absolute bottom-0 left-4 right-4 sm:left-8 sm:right-8 h-0.5 bg-[#0B7068] rounded-full"
                        ></div>
                    </button>
                    <button
                        @click="activeTab = 'history'"
                        type="button"
                        class="py-2.5 sm:py-3.5 px-2 text-[11px] sm:text-xs md:text-sm font-bold text-center relative transition-all cursor-pointer"
                        :class="activeTab === 'history' ? 'text-[#0B7068]' : 'text-slate-400 hover:text-slate-600'"
                    >
                        <span>Riwayat Sebelumnya ({{ historyTotalCount }})</span>
                        <div
                            v-if="activeTab === 'history'"
                            class="absolute bottom-0 left-4 right-4 sm:left-8 sm:right-8 h-0.5 bg-[#0B7068] rounded-full"
                        ></div>
                    </button>
                </div>
            </div>

            <!-- SEARCH & FILTER SECTION (STACKED ON MOBILE, SIDE-BY-SIDE ON DESKTOP) -->
            <div class="space-y-2.5 sm:space-y-3 w-full">
                
                <!-- Row 1: Search & Category Dropdown -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 w-full">
                    
                    <!-- Search Input -->
                    <div class="relative w-full sm:flex-1">
                        <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari judul giat, lokasi, atau deskripsi..."
                            class="w-full pl-9 pr-8 py-2.5 bg-white rounded-xl sm:rounded-2xl border border-slate-200/90 text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:border-[#0B7068] focus:ring-1 focus:ring-[#0B7068]/30 shadow-2xs transition-all h-[42px] sm:h-[46px]"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            type="button"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1"
                        >
                            <XMarkIcon class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <!-- Category Dropdown -->
                    <div class="relative w-full sm:w-60 md:w-64 shrink-0">
                        <select
                            v-model="filterCategory"
                            class="w-full py-2.5 pl-3.5 pr-8 bg-white rounded-xl sm:rounded-2xl border border-slate-200/90 text-xs sm:text-sm text-slate-700 font-medium focus:border-[#0B7068] focus:ring-1 focus:ring-[#0B7068]/30 shadow-2xs appearance-none transition-all cursor-pointer truncate h-[42px] sm:h-[46px]"
                        >
                            <option value="">Semua Kategori</option>
                            <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <ChevronDownIcon class="w-3.5 h-3.5 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    </div>
                </div>

                <!-- Row 2: CATEGORY PILLS (Horizontal Scroll on Mobile, Wrapped cleanly on Desktop) -->
                <div class="w-full overflow-hidden">
                    <div class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto md:flex-wrap pb-1 scrollbar-none w-full">
                        <!-- Pill: Semua -->
                        <button
                            @click="filterCategory = ''"
                            type="button"
                            class="px-3.5 py-1.5 rounded-full text-xs font-semibold flex items-center gap-1.5 shrink-0 transition-all active:scale-95 cursor-pointer"
                            :class="filterCategory === '' ? 'bg-[#0B7068] text-white shadow-xs' : 'bg-white border border-slate-200/90 text-slate-700 hover:border-slate-300'"
                        >
                            <Squares2X2Icon class="w-3.5 h-3.5" />
                            <span>Semua</span>
                        </button>

                        <!-- Dynamic Category Pills from Backend -->
                        <button
                            v-for="(label, key) in categories"
                            :key="key"
                            @click="filterCategory = key"
                            type="button"
                            class="px-3 py-1.5 rounded-full text-xs font-medium flex items-center gap-1.5 shrink-0 transition-all active:scale-95 whitespace-nowrap cursor-pointer"
                            :class="filterCategory === key ? 'bg-[#0B7068] text-white font-semibold shadow-xs' : 'bg-white border border-slate-200/90 text-slate-700 hover:border-slate-300'"
                        >
                            <component :is="getCategoryIcon(key)" class="w-3.5 h-3.5 shrink-0" />
                            <span>{{ label }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- TAB 1: TODAY ACTIVITIES -->
            <div v-if="activeTab === 'today'" class="w-full">
                
                <!-- EMPTY STATE BOX (COMPACT & PROPORTIONAL, FITS MOBILE SCREENS) -->
                <div v-if="filteredTodayActivities.length === 0" class="bg-white rounded-2xl sm:rounded-3xl border border-slate-100 shadow-2xs p-6 sm:p-8 text-center my-1 w-full">
                    
                    <!-- Mint Clipboard Illustration with Leaves -->
                    <div class="w-24 h-24 sm:w-28 sm:h-28 mx-auto relative flex items-center justify-center mb-1">
                        <!-- Mint Glow Blob Background -->
                        <div class="absolute inset-0 bg-teal-50/80 rounded-full scale-95"></div>
                        <div class="absolute inset-1.5 bg-gradient-to-tr from-teal-100/60 to-emerald-50/50 rounded-full"></div>

                        <!-- Left Leaf Accent -->
                        <svg class="absolute w-5 h-5 text-teal-400/70 left-1.5 top-6 -rotate-12 pointer-events-none" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 008 20C19 20 22 3 22 3c-1 2-8 2.25-13 3.25S2 11.5 2 13.5s1.75 3.75 1.75 3.75C7 8 17 8 17 8z"/>
                        </svg>

                        <!-- Right Leaf Accent -->
                        <svg class="absolute w-5 h-5 text-emerald-400/70 right-1.5 top-8 rotate-45 pointer-events-none" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 008 20C19 20 22 3 22 3c-1 2-8 2.25-13 3.25S2 11.5 2 13.5s1.75 3.75 1.75 3.75C7 8 17 8 17 8z"/>
                        </svg>

                        <!-- Stylized Clipboard Document Card -->
                        <div class="relative z-10 w-16 h-20 bg-white rounded-xl shadow-xs border border-slate-100 flex flex-col items-center pt-1.5">
                            <!-- Clip Header -->
                            <div class="w-8 h-2.5 bg-[#0B7068] rounded-t-xs -mt-3 mb-1.5 shadow-2xs flex items-center justify-center">
                                <div class="w-3 h-0.5 bg-white/70 rounded-full"></div>
                            </div>
                            <!-- Document Line -->
                            <div class="w-10 h-0.5 bg-slate-100 rounded-full mb-1.5"></div>
                            <!-- Teal Checkmark Icon Circle -->
                            <div class="w-8 h-8 rounded-full bg-teal-50 border border-teal-200/80 flex items-center justify-center text-[#0B7068]">
                                <CheckIcon class="w-5 h-5 stroke-[3]" />
                            </div>
                            <!-- Bottom Line -->
                            <div class="w-8 h-0.5 bg-slate-100 rounded-full mt-1.5"></div>
                        </div>
                    </div>

                    <!-- Heading & Subtitle -->
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900 mt-3">Belum Ada Giat yang Dicatat Hari Ini</h3>
                    <p class="text-xs text-slate-400 mt-1.5 max-w-xs mx-auto leading-relaxed">
                        Mulai catat kegiatan yayasan seperti rapat, acara, pelatihan, atau aktivitas lainnya.
                    </p>

                    <!-- Primary Big Button -->
                    <button
                        @click="openFormModal"
                        type="button"
                        class="mt-4 px-6 py-2.5 bg-[#0B7068] hover:bg-[#095c55] active:scale-95 text-white rounded-xl font-bold text-xs sm:text-sm shadow-sm inline-flex items-center justify-center gap-2 transition-all w-full sm:w-auto cursor-pointer"
                    >
                        <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                        <span>Tambah Giat Pertama</span>
                    </button>
                </div>

                <!-- ACTIVITIES CARDS LIST (WHEN DATA EXISTS) -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5 sm:gap-4 w-full">
                    <div
                        v-for="item in filteredTodayActivities"
                        :key="item.id"
                        class="bg-white rounded-2xl border border-slate-100 shadow-2xs hover:shadow-md transition-all duration-200 p-4 space-y-3 flex flex-col justify-between w-full"
                    >
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border flex items-center gap-1" :class="categoryBadgeClass(item.category)">
                                    <component :is="getCategoryIcon(item.category)" class="w-3 h-3" />
                                    <span>{{ categories[item.category] || item.category }}</span>
                                </span>
                                <span class="text-xs font-mono text-slate-400 flex items-center gap-1">
                                    <ClockIcon class="w-3.5 h-3.5 text-slate-400" />
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
                            <span v-if="item.location_name" class="text-slate-500 text-[11px] font-medium flex items-center gap-1 truncate max-w-[220px]">
                                <MapPinIcon class="w-3.5 h-3.5 text-[#0B7068] shrink-0" />
                                <span class="truncate">{{ item.location_name }}</span>
                            </span>
                            <span v-else class="text-[#0B7068] font-semibold text-[11px] flex items-center gap-1">
                                <CheckCircleIcon class="w-3.5 h-3.5" />
                                Terpublikasi otomatis
                            </span>

                            <button
                                @click="confirmDelete(item)"
                                type="button"
                                class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition"
                                title="Hapus Giat"
                            >
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- TAB 2: HISTORY LOGS -->
            <div v-if="activeTab === 'history'" class="space-y-3 w-full">
                
                <!-- EMPTY STATE RIWAYAT -->
                <div v-if="filteredHistoryLogs.length === 0" class="bg-white rounded-2xl sm:rounded-3xl border border-slate-100 shadow-2xs p-6 sm:p-8 text-center my-1 space-y-2 w-full">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-[#0B7068] border border-teal-100/80 mx-auto flex items-center justify-center">
                        <FolderIcon class="w-5 h-5 stroke-[1.8]" />
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm">Tidak Ada Riwayat Giat</h4>
                        <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto leading-relaxed">
                            Belum ada riwayat kegiatan yang tercatat sebelumnya atau tidak ada yang sesuai dengan filter pencarian.
                        </p>
                    </div>
                </div>

                <div v-else class="divide-y divide-slate-100 bg-white border border-slate-100 shadow-2xs rounded-2xl overflow-hidden w-full">
                    <div
                        v-for="item in filteredHistoryLogs"
                        :key="item.id"
                        class="p-3.5 sm:p-4 hover:bg-slate-50/80 transition flex items-center justify-between gap-3 sm:gap-4 w-full"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <div
                                v-if="item.photo_path"
                                @click="openPhotoLightbox('/' + item.photo_path)"
                                class="w-12 h-12 rounded-xl overflow-hidden border border-slate-200 shrink-0 cursor-pointer group relative"
                            >
                                <img :src="'/' + item.photo_path" class="w-full h-full object-cover group-hover:scale-105 transition-transform" />
                            </div>
                            <div v-else class="w-12 h-12 rounded-xl bg-teal-50/60 text-[#0B7068] flex items-center justify-center shrink-0 border border-teal-100/50">
                                <FolderIcon class="w-5 h-5 stroke-[1.8]" />
                            </div>

                            <div class="space-y-0.5 min-w-0">
                                <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                                    <span class="font-bold text-slate-800 text-xs sm:text-sm truncate max-w-[170px] sm:max-w-none">{{ item.title }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase border" :class="categoryBadgeClass(item.category)">
                                        {{ categories[item.category] || item.category }}
                                    </span>
                                </div>
                                <p v-if="item.description" class="text-xs text-slate-500 line-clamp-1">{{ item.description }}</p>
                                <p class="text-[10px] sm:text-[11px] text-slate-400 font-medium">
                                    {{ formatDate(item.activity_date) }} • {{ item.activity_time ? item.activity_time.substring(0, 5) : '-' }} WIB
                                </p>
                            </div>
                        </div>

                        <button
                            @click="confirmDelete(item)"
                            type="button"
                            class="p-2 text-slate-400 hover:text-rose-600 rounded-xl hover:bg-rose-50 transition shrink-0"
                            title="Hapus Giat"
                        >
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Pagination for History Logs -->
                <div v-if="historyLogs?.links && historyLogs.links.length > 3" class="pt-2 flex justify-center">
                    <Pagination :links="historyLogs.links" />
                </div>
            </div>

        </div>

        <!-- FORM MODAL (TAMBAH GIAT) -->
        <Teleport to="body">
            <div v-if="showFormModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-100 transform transition-all animate-in fade-in zoom-in-95 duration-200">
                    
                    <!-- Modal Header -->
                    <div class="px-6 py-4 bg-[#0B7068] text-white flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center">
                                <ClipboardDocumentCheckIcon class="w-5 h-5 text-teal-200" />
                            </div>
                            <div>
                                <h3 class="font-bold text-sm sm:text-base leading-tight">Tambah Giat Tugas</h3>
                                <p class="text-[11px] text-teal-100 font-normal">Catat kegiatan harian & upload dokumentasi</p>
                            </div>
                        </div>
                        <button @click="closeFormModal" class="text-teal-200 hover:text-white p-1 rounded-xl hover:bg-white/10 transition">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form @submit.prevent="submitForm" class="p-5 sm:p-6 space-y-4">
                        
                        <!-- Judul Kegiatan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Judul / Nama Kegiatan <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                placeholder="Contoh: Rapat Koordinasi Kurikulum, Menata Buku..."
                                class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-[#0B7068] focus:ring-[#0B7068]/20"
                            />
                            <p v-if="form.errors.title" class="text-xs text-rose-600 mt-1 font-medium">{{ form.errors.title }}</p>
                        </div>

                        <!-- Kategori & Waktu -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Kategori Giat <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="form.category"
                                    required
                                    class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-[#0B7068] focus:ring-[#0B7068]/20"
                                >
                                    <option v-for="(label, key) in categories" :key="key" :value="key">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Jam Pelaksanaan
                                </label>
                                <input
                                    v-model="form.activity_time"
                                    type="time"
                                    class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-[#0B7068] focus:ring-[#0B7068]/20"
                                />
                            </div>
                        </div>

                        <!-- Photo Input / Camera Dropzone (With Auto-Compressor & Spinner) -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Foto Bukti Dokumentasi
                            </label>
                            
                            <div v-if="isCompressing" class="w-full h-32 rounded-2xl border-2 border-dashed border-teal-400 bg-teal-50 flex flex-col items-center justify-center text-[#0B7068]">
                                <svg class="animate-spin h-6 w-6 text-[#0B7068] mb-1.5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <span class="text-xs font-bold">Mengompres & Mengoptimalkan Foto...</span>
                            </div>

                            <div v-else-if="photoPreviewUrl" class="w-full h-36 rounded-2xl border-2 border-slate-200 overflow-hidden relative group">
                                <img :src="photoPreviewUrl" class="w-full h-full object-cover" />
                                <button
                                    type="button"
                                    @click="photoPreviewUrl = null; form.photo = null"
                                    class="absolute top-2 right-2 bg-rose-600 text-white p-1.5 rounded-full shadow-md hover:bg-rose-700 transition"
                                >
                                    <XMarkIcon class="w-4 h-4" />
                                </button>
                            </div>

                            <div v-else class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    @click="galleryInputRef.click()"
                                    class="h-28 rounded-2xl border-2 border-dashed border-teal-300 hover:border-teal-500 bg-teal-50/50 hover:bg-teal-50 transition-all flex flex-col items-center justify-center p-3 text-[#0B7068] active:scale-95"
                                >
                                    <PhotoIcon class="w-7 h-7 mb-1 stroke-[1.8]" />
                                    <span class="text-xs font-extrabold">Pilih dari Galeri</span>
                                    <span class="text-[9px] text-teal-700/70 font-medium">Foto / Album HP</span>
                                </button>

                                <button
                                    type="button"
                                    @click="cameraInputRef.click()"
                                    class="h-28 rounded-2xl border-2 border-dashed border-slate-300 hover:border-slate-400 bg-slate-50 hover:bg-slate-100 transition-all flex flex-col items-center justify-center p-3 text-slate-600 active:scale-95"
                                >
                                    <CameraIcon class="w-7 h-7 mb-1 stroke-[1.8]" />
                                    <span class="text-xs font-extrabold">Buka Kamera</span>
                                    <span class="text-[9px] text-slate-400 font-medium">Foto Langsung</span>
                                </button>
                            </div>

                            <input
                                ref="galleryInputRef"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handleFileChange"
                            />
                            <input
                                ref="cameraInputRef"
                                type="file"
                                accept="image/*"
                                capture="environment"
                                class="hidden"
                                @change="handleFileChange"
                            />
                            <p v-if="form.errors.photo" class="text-xs text-rose-600 font-medium">{{ form.errors.photo }}</p>
                        </div>

                        <!-- Deskripsi -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Deskripsi / Catatan Ringkas
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                placeholder="Rincian hasil kegiatan atau agenda rapat (opsional)..."
                                class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-[#0B7068] focus:ring-[#0B7068]/20 resize-none"
                            ></textarea>
                        </div>

                        <!-- Lokasi -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Lokasi Pelaksanaan
                            </label>
                            <input
                                v-model="form.location_name"
                                type="text"
                                placeholder="Contoh: Kantor Yayasan, Gedung SD Lt 2..."
                                class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-[#0B7068] focus:ring-[#0B7068]/20"
                            />
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="closeFormModal"
                                class="px-4 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold hover:bg-slate-200 transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing || isCompressing"
                                class="px-5 py-2.5 bg-[#0B7068] hover:bg-[#095c55] text-white rounded-xl text-xs font-bold shadow-md shadow-teal-900/15 transition disabled:opacity-50 flex items-center gap-1.5 active:scale-95"
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
