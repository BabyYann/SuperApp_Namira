<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MobileAppShell from '@/Layouts/MobileAppShell.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { 
    ClockIcon, 
    DocumentTextIcon, 
    MagnifyingGlassIcon, 
    FunnelIcon, 
    ChevronDownIcon, 
    CameraIcon, 
    PaperClipIcon, 
    XMarkIcon, 
    CheckIcon, 
    BriefcaseIcon, 
    HeartIcon, 
    CheckCircleIcon, 
    XCircleIcon, 
    InboxIcon, 
    ArrowLeftIcon,
    ArrowDownTrayIcon,
    ShieldExclamationIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    approvals: [Object, Array],
    pendingCount: Number,
    activeTab: String,
    filters: Object,
});

const isMobile = ref(typeof window !== 'undefined' ? window.innerWidth < 768 : false);
const updateMobile = () => {
    if (typeof window !== 'undefined') {
        isMobile.value = window.innerWidth < 768;
    }
};

onMounted(() => {
    updateMobile();
    window.addEventListener('resize', updateMobile);
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', updateMobile);
    }
});

const approvalsList = computed(() => {
    if (Array.isArray(props.approvals)) return props.approvals;
    if (props.approvals?.data && Array.isArray(props.approvals.data)) return props.approvals.data;
    return [];
});

const activeTab = ref(props.activeTab || 'pending');
const search = ref(props.filters?.search || '');
const selectedType = ref(props.filters?.type || 'all');

// Bulk Action Selection
const selectedIds = ref([]);

const isAllSelected = computed(() => {
    const list = approvalsList.value;
    if (!list.length) return false;
    return list.every(item => selectedIds.value.includes(item.id));
});

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = approvalsList.value.map(item => item.id);
    }
};

watch(activeTab, () => {
    selectedIds.value = [];
});

const applyFilters = (targetTab = activeTab.value) => {
    selectedIds.value = [];
    router.get(route('yayasan.attendance-approvals.index'), {
        status: targetTab,
        search: search.value,
        type: selectedType.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const switchTab = (tab) => {
    activeTab.value = tab;
    applyFilters(tab);
};

const handleSearch = () => {
    applyFilters();
};

const handleTypeChange = () => {
    applyFilters();
};

const form = useForm({
    status: null,
    reason: null,
});

// Single Rejection Modal State
const rejectModalOpen = ref(false);
const selectedAttendance = ref(null);
const rejectionReason = ref('');

// Bulk Rejection Modal State
const bulkRejectModalOpen = ref(false);
const bulkRejectionReason = ref('');
const isBulkProcessing = ref(false);

// Photo Preview Modal (Lightbox) State
const photoModalOpen = ref(false);
const photoModalData = ref(null);
const activePhotoIndex = ref(0);

// Rejection Detail Modal (History) State
const detailModalOpen = ref(false);
const selectedDetailAttendance = ref(null);

const approve = (attendance) => {
    if (!confirm(`Setujui pengajuan ${typeLabel(attendance.status)} dari ${attendance.user?.name}?`)) return;
    
    form.status = 'approved';
    form.reason = null;
    form.put(route('yayasan.attendance-approvals.update', attendance.id), {
        preserveScroll: true,
    });
};

const openRejectModal = (attendance) => {
    selectedAttendance.value = attendance;
    rejectionReason.value = '';
    rejectModalOpen.value = true;
};

const submitReject = () => {
    if (!rejectionReason.value.trim()) {
        alert('Mohon masukkan alasan penolakan.');
        return;
    }

    form.status = 'rejected';
    form.reason = rejectionReason.value;
    form.put(route('yayasan.attendance-approvals.update', selectedAttendance.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            rejectModalOpen.value = false;
        }
    });
};

// Bulk Actions Handlers
const bulkApprove = () => {
    if (!selectedIds.value.length) return;
    if (!confirm(`Apakah Anda yakin ingin menyetujui ${selectedIds.value.length} pengajuan absensi yang dipilih sekaligus?`)) return;

    isBulkProcessing.value = true;
    router.post(route('yayasan.attendance-approvals.bulk-action'), {
        ids: selectedIds.value,
        action: 'approve',
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
        },
        onFinish: () => {
            isBulkProcessing.value = false;
        }
    });
};

const openBulkRejectModal = () => {
    if (!selectedIds.value.length) return;
    bulkRejectionReason.value = '';
    bulkRejectModalOpen.value = true;
};

const submitBulkReject = () => {
    if (!bulkRejectionReason.value.trim()) {
        alert('Mohon masukkan alasan penolakan massal.');
        return;
    }

    isBulkProcessing.value = true;
    router.post(route('yayasan.attendance-approvals.bulk-action'), {
        ids: selectedIds.value,
        action: 'reject',
        reason: bulkRejectionReason.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            bulkRejectModalOpen.value = false;
            selectedIds.value = [];
        },
        onFinish: () => {
            isBulkProcessing.value = false;
        }
    });
};

// Photo & Attachment Helpers
const getAttachments = (att) => {
    const list = [];
    if (att.permit_file) {
        const isPdf = typeof att.permit_file === 'string' && att.permit_file.toLowerCase().endsWith('.pdf');
        list.push({
            url: `/storage/${att.permit_file}`,
            isPdf,
            label: isPdf ? 'Dokumen Surat / PDF' : 'Lampiran Surat / Bukti'
        });
    }
    if (att.check_in_photo) {
        list.push({
            url: `/storage/${att.check_in_photo}`,
            isPdf: false,
            label: 'Foto Presensi Masuk'
        });
    }
    if (att.check_out_photo) {
        list.push({
            url: `/storage/${att.check_out_photo}`,
            isPdf: false,
            label: 'Foto Presensi Pulang'
        });
    }
    return list;
};

const openPhotoModal = (att) => {
    const attachments = getAttachments(att);
    if (!attachments.length) return;
    photoModalData.value = {
        attendance: att,
        items: attachments
    };
    activePhotoIndex.value = 0;
    photoModalOpen.value = true;
};

const openRejectionDetail = (att) => {
    selectedDetailAttendance.value = att;
    detailModalOpen.value = true;
};

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(route('yayasan.staff.index'));
    }
};

const typeLabel = (type) => {
    const map = {
        'business_trip': 'Dinas Luar',
        'sick': 'Sakit',
        'permit': 'Izin',
        'present': 'Hadir',
    };
    return map[type] || type;
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const cleanDate = typeof dateStr === 'string' && dateStr.includes('T') ? dateStr.split('T')[0] : (typeof dateStr === 'string' && dateStr.includes(' ') ? dateStr.split(' ')[0] : dateStr);
    return dayjs(cleanDate).format('DD MMMM YYYY');
};

const getInitials = (name) => {
    if (!name) return '??';
    // Remove common academic and religious titles
    const clean = name
        .replace(/\b(Dr|dr|Prof|H|Hj|Ust|Usth|Ir|Dra|Drs)\b\.?/gi, '')
        .replace(/,\s*.*$/gi, '')
        .trim();
    const words = clean.split(/\s+/).filter(Boolean);
    if (words.length === 0) return name.substring(0, 2).toUpperCase();
    if (words.length === 1) return words[0].substring(0, 2).toUpperCase();
    return (words[0][0] + words[words.length - 1][0]).toUpperCase();
};

const getAvatarColor = (name) => {
    const colors = [
        'bg-sky-100 text-sky-700',
        'bg-lime-100 text-lime-700',
        'bg-emerald-100 text-emerald-700',
        'bg-purple-100 text-purple-700',
        'bg-amber-100 text-amber-700',
        'bg-rose-100 text-rose-700',
        'bg-indigo-100 text-indigo-700',
        'bg-teal-100 text-teal-700'
    ];
    let hash = 0;
    const str = name || 'user';
    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }
    return colors[Math.abs(hash) % colors.length];
};
</script>

<template>
    <Head title="Persetujuan Absensi & Izin" />

    <component :is="isMobile ? MobileAppShell : AuthenticatedLayout">
        
        <div class="max-w-4xl mx-auto px-4 py-3 pb-28 sm:py-6 w-full min-w-0 box-border">
            
            <!-- TOP BREADCRUMB / BACK NAVIGATION -->
            <div class="flex items-center gap-3">
                <button 
                    @click="goBack" 
                    type="button"
                    class="w-9 h-9 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-700 hover:bg-slate-50 transition-colors shadow-2xs cursor-pointer active:scale-95 shrink-0"
                    title="Kembali"
                >
                    <ArrowLeftIcon class="w-4 h-4 stroke-[2.5]" />
                </button>
                <Link 
                    :href="route('yayasan.staff.index')" 
                    class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors truncate"
                >
                    Manajemen Pegawai
                </Link>
            </div>

            <!-- TITLE & SUBTITLE -->
            <div class="mt-3">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-tight">
                    Persetujuan Absensi & Izin
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Kelola pengajuan izin, sakit, dan dinas luar pegawai.
                </p>
            </div>

            <!-- SEGMENTED TABS (MATCHING MOCKUP) -->
            <div class="bg-slate-100/90 p-1 rounded-2xl flex items-center gap-1 mt-4">
                <!-- Tab 1: Menunggu Persetujuan -->
                <button 
                    @click="switchTab('pending')"
                    type="button"
                    :class="[
                        'flex-1 py-2 px-3 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-2 cursor-pointer',
                        activeTab === 'pending' 
                            ? 'bg-white text-emerald-800 shadow-xs border border-slate-200/50' 
                            : 'text-slate-500 hover:text-slate-800'
                    ]"
                >
                    <ClockIcon :class="['w-4 h-4 stroke-[2.2]', activeTab === 'pending' ? 'text-emerald-600' : 'text-slate-400']" />
                    <span>Menunggu Persetujuan</span>
                    <span 
                        v-if="pendingCount > 0" 
                        class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-rose-500 text-white min-w-4 text-center leading-none"
                    >
                        {{ pendingCount }}
                    </span>
                </button>

                <!-- Tab 2: Riwayat Persetujuan -->
                <button 
                    @click="switchTab('history')"
                    type="button"
                    :class="[
                        'flex-1 py-2 px-3 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-2 cursor-pointer',
                        activeTab === 'history' 
                            ? 'bg-white text-emerald-800 shadow-xs border border-slate-200/50' 
                            : 'text-slate-500 hover:text-slate-800'
                    ]"
                >
                    <DocumentTextIcon :class="['w-4 h-4 stroke-[2.2]', activeTab === 'history' ? 'text-emerald-600' : 'text-slate-400']" />
                    <span>Riwayat Persetujuan</span>
                </button>
            </div>

            <!-- SEARCH & FILTER BAR (MATCHING MOCKUP) -->
            <div class="flex items-center gap-2 mt-3.5">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-0">
                    <input 
                        v-model="search"
                        @keyup.enter="handleSearch"
                        type="text" 
                        placeholder="Cari nama pegawai atau alasan..." 
                        class="w-full bg-white border border-slate-200/90 rounded-2xl pl-9 pr-7 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#0B7068] focus:ring-1 focus:ring-[#0B7068] shadow-2xs transition-all"
                    />
                    <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" />
                    <button 
                        v-if="search" 
                        @click="search = ''; handleSearch()"
                        type="button"
                        class="absolute right-2.5 top-3 text-slate-400 hover:text-slate-600"
                    >
                        <XMarkIcon class="w-3.5 h-3.5" />
                    </button>
                </div>

                <!-- Filter Dropdown (Semua Jenis) -->
                <div class="relative shrink-0">
                    <select
                        v-model="selectedType"
                        @change="handleTypeChange"
                        class="bg-white border border-slate-200/90 rounded-2xl pl-8 pr-7 py-2.5 text-xs font-semibold text-slate-700 shadow-2xs cursor-pointer appearance-none focus:outline-none focus:border-[#0B7068] focus:ring-1 focus:ring-[#0B7068] transition-all"
                    >
                        <option value="all">Semua Jenis</option>
                        <option value="business_trip">Dinas Luar</option>
                        <option value="sick">Sakit</option>
                        <option value="permit">Izin</option>
                    </select>
                    <FunnelIcon class="w-3.5 h-3.5 text-slate-600 absolute left-2.5 top-3.5 pointer-events-none" />
                    <ChevronDownIcon class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-3.5 pointer-events-none" />
                </div>
            </div>

            <!-- SUBHEADER: TITLE & DATA COUNT -->
            <div class="flex items-center justify-between mt-4 mb-2.5">
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-slate-900 text-sm sm:text-base">
                        {{ activeTab === 'pending' ? 'Pengajuan menunggu' : 'Riwayat persetujuan' }}
                    </h3>
                    <!-- Select All Checkbox (Only in Pending tab) -->
                    <label 
                        v-if="activeTab === 'pending' && approvalsList.length > 0" 
                        class="flex items-center gap-1.5 ml-2 cursor-pointer select-none"
                    >
                        <input 
                            type="checkbox" 
                            :checked="isAllSelected" 
                            @change="toggleSelectAll" 
                            class="rounded border-slate-300 text-[#0B7068] focus:ring-[#0B7068]/20 w-3.5 h-3.5 cursor-pointer"
                        />
                        <span class="text-[11px] text-slate-500 font-medium">Pilih semua</span>
                    </label>
                </div>
                <span class="text-xs text-slate-400 font-medium">
                    Menampilkan {{ approvalsList.length }} {{ activeTab === 'pending' ? 'pengajuan' : 'riwayat' }}
                </span>
            </div>

            <!-- EMPTY STATE -->
            <div 
                v-if="approvalsList.length === 0" 
                class="text-center py-12 px-4 border border-dashed border-slate-200 rounded-3xl bg-white shadow-2xs space-y-2"
            >
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-teal-50 text-[#0B7068] mb-1">
                    <InboxIcon class="w-7 h-7 stroke-[1.8]" />
                </div>
                <h4 class="text-sm font-bold text-slate-800">
                    {{ activeTab === 'pending' ? 'Tidak Ada Pengajuan Menunggu' : 'Belum Ada Riwayat Persetujuan' }}
                </h4>
                <p class="text-xs text-slate-400 max-w-sm mx-auto leading-relaxed">
                    {{ activeTab === 'pending' 
                        ? 'Semua pengajuan izin, sakit, dan dinas luar pegawai saat ini telah selesai diproses.' 
                        : 'Belum ada catatan persetujuan atau penolakan pengajuan untuk kriteria filter ini.' }}
                </p>
                <button
                    v-if="search || selectedType !== 'all'"
                    @click="search = ''; selectedType = 'all'; applyFilters()"
                    type="button"
                    class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-[#0B7068] hover:underline cursor-pointer"
                >
                    Reset Filter Pencarian
                </button>
            </div>

            <!-- APPROVAL CARDS LIST (PIXEL-PERFECT MATCH TO MOCKUP) -->
            <div v-else class="space-y-3">
                <div 
                    v-for="att in approvalsList" 
                    :key="att.id"
                    :class="[
                        'bg-white rounded-2xl p-4 border transition-all space-y-3 relative',
                        selectedIds.includes(att.id) 
                            ? 'border-[#0B7068]/50 ring-2 ring-[#0B7068]/20 bg-teal-50/15 shadow-sm' 
                            : 'border-slate-100 shadow-2xs hover:border-slate-200'
                    ]"
                >
                    <!-- ROW 1: CHECKBOX + AVATAR + USER INFO + TYPE BADGE -->
                    <div class="flex items-start justify-between gap-2.5">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <!-- Checkbox (Only in Pending tab) -->
                            <input 
                                v-if="activeTab === 'pending'"
                                type="checkbox" 
                                :value="att.id" 
                                v-model="selectedIds" 
                                class="rounded border-slate-300 text-[#0B7068] focus:ring-[#0B7068]/20 w-4 h-4 cursor-pointer shrink-0" 
                            />

                            <!-- Staff Avatar / Initials Circle -->
                            <img 
                                v-if="att.user?.profile_photo_url" 
                                :src="att.user.profile_photo_url" 
                                :alt="att.user?.name"
                                class="w-10 h-10 rounded-full object-cover border border-slate-100 shadow-2xs shrink-0" 
                            />
                            <div 
                                v-else 
                                :class="[
                                    'w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs',
                                    getAvatarColor(att.user?.name)
                                ]"
                            >
                                {{ getInitials(att.user?.name) }}
                            </div>

                            <!-- Name & Date -->
                            <div class="min-w-0">
                                <h4 class="font-bold text-slate-900 text-sm leading-tight truncate">
                                    {{ att.user?.name || '-' }}
                                </h4>
                                <p class="text-xs text-slate-400 mt-0.5 font-medium truncate">
                                    {{ formatDate(att.date) }}
                                </p>
                            </div>
                        </div>

                        <!-- Type Badge (Top Right) -->
                        <span 
                            :class="[
                                'px-2.5 py-1 rounded-xl font-bold text-[11px] flex items-center gap-1 shrink-0 border shadow-2xs',
                                att.status === 'business_trip' 
                                    ? 'bg-sky-50 text-sky-600 border-sky-100/70' 
                                    : att.status === 'sick' 
                                        ? 'bg-rose-50 text-rose-600 border-rose-100/70' 
                                        : att.status === 'permit'
                                            ? 'bg-amber-50 text-amber-700 border-amber-100/70'
                                            : 'bg-emerald-50 text-emerald-700 border-emerald-100/70'
                            ]"
                        >
                            <BriefcaseIcon v-if="att.status === 'business_trip'" class="w-3.5 h-3.5 stroke-[2]" />
                            <HeartIcon v-else-if="att.status === 'sick'" class="w-3.5 h-3.5 stroke-[2]" />
                            <DocumentTextIcon v-else-if="att.status === 'permit'" class="w-3.5 h-3.5 stroke-[2]" />
                            <CheckCircleIcon v-else class="w-3.5 h-3.5 stroke-[2]" />
                            <span>{{ typeLabel(att.status) }}</span>
                        </span>
                    </div>

                    <!-- ROW 2: INSET REASON CONTAINER (ALASAN) -->
                    <div class="bg-slate-50/80 rounded-xl p-2.5 border border-slate-100/80 flex items-start gap-2.5">
                        <DocumentTextIcon class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                        <div class="min-w-0 flex-1">
                            <span class="text-[10px] text-slate-400 font-medium block leading-none mb-1">Alasan</span>
                            <p class="text-xs text-slate-700 font-medium leading-relaxed break-words">
                                {{ att.note || '-' }}
                            </p>
                        </div>
                    </div>

                    <!-- ROW 3: ACTIONS ROW -->
                    <div class="flex items-center justify-between pt-0.5 gap-2">
                        <!-- Left: Attachment / Photo Button -->
                        <div>
                            <button 
                                v-if="getAttachments(att).length > 0"
                                @click="openPhotoModal(att)" 
                                type="button"
                                class="px-3 py-1.5 bg-indigo-50/70 hover:bg-indigo-100 text-indigo-600 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer active:scale-95 shrink-0"
                            >
                                <CameraIcon class="w-3.5 h-3.5 stroke-[2]" />
                                <span>Lihat foto ({{ getAttachments(att).length }})</span>
                            </button>
                            <span 
                                v-else 
                                class="text-[11px] text-slate-300 font-medium flex items-center gap-1 pl-1"
                            >
                                <CameraIcon class="w-3.5 h-3.5 text-slate-300" />
                                <span>Tidak ada foto</span>
                            </span>
                        </div>

                        <!-- Right: Action Buttons -->
                        <div class="flex items-center gap-2 shrink-0">
                            <!-- Pending Actions: Tolak & Setujui -->
                            <template v-if="activeTab === 'pending'">
                                <button 
                                    @click="openRejectModal(att)" 
                                    type="button"
                                    class="px-3.5 py-1.5 bg-white border border-rose-400 hover:bg-rose-50 text-rose-500 rounded-xl text-xs font-bold flex items-center gap-1 transition-all cursor-pointer active:scale-95 shadow-2xs shrink-0"
                                >
                                    <XMarkIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                                    <span>Tolak</span>
                                </button>
                                <button 
                                    @click="approve(att)" 
                                    type="button"
                                    class="px-4 py-1.5 bg-[#0B7068] hover:bg-[#095c55] active:scale-95 text-white rounded-xl text-xs font-bold flex items-center gap-1 shadow-2xs transition-all cursor-pointer shrink-0"
                                >
                                    <CheckIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                                    <span>Setujui</span>
                                </button>
                            </template>

                            <!-- History Badges -->
                            <template v-else-if="activeTab === 'history'">
                                <span 
                                    v-if="att.approval_status === 'approved'" 
                                    class="px-3 py-1 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1"
                                >
                                    <CheckCircleIcon class="w-3.5 h-3.5 text-emerald-600" />
                                    <span>Disetujui</span>
                                </span>
                                <button 
                                    v-else-if="att.approval_status === 'rejected'" 
                                    @click="openRejectionDetail(att)"
                                    type="button"
                                    class="px-3 py-1 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 flex items-center gap-1 hover:bg-rose-100 cursor-pointer transition-colors"
                                    title="Klik untuk melihat detail penolakan"
                                >
                                    <XCircleIcon class="w-3.5 h-3.5 text-rose-600" />
                                    <span>Ditolak</span>
                                    <span v-if="att.rejection_reason" class="text-[10px] text-rose-500 underline ml-0.5 font-normal">Detail</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAGINATION BAR -->
            <div v-if="approvals?.links && approvals.links.length > 3" class="mt-6 flex justify-center pb-4">
                <Pagination :links="approvals.links" />
            </div>
        </div>

        <!-- FLOATING BULK ACTIONS BAR (WHEN ITEMS SELECTED) -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div 
                v-if="activeTab === 'pending' && selectedIds.length > 0" 
                class="fixed bottom-20 left-4 right-4 z-40 bg-slate-900/95 backdrop-blur-md text-white px-4 py-3 rounded-2xl shadow-2xl border border-slate-800 flex items-center justify-between gap-3 max-w-lg mx-auto"
            >
                <div class="flex items-center gap-2 min-w-0">
                    <span class="px-2.5 py-0.5 rounded-full bg-teal-500/20 text-teal-300 font-extrabold text-xs border border-teal-500/30 shrink-0">
                        {{ selectedIds.length }} Dipilih
                    </span>
                    <button 
                        @click="selectedIds = []" 
                        type="button" 
                        class="text-[11px] text-slate-400 hover:text-white underline cursor-pointer"
                    >
                        Batal
                    </button>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button 
                        @click="openBulkRejectModal" 
                        :disabled="isBulkProcessing"
                        type="button"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-500 active:scale-95 text-white font-bold rounded-xl text-xs flex items-center gap-1 shadow-sm transition-all cursor-pointer disabled:opacity-50"
                    >
                        <XMarkIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Tolak Semua</span>
                    </button>
                    <button 
                        @click="bulkApprove" 
                        :disabled="isBulkProcessing"
                        type="button"
                        class="px-4 py-1.5 bg-[#0B7068] hover:bg-[#095c55] active:scale-95 text-white font-bold rounded-xl text-xs flex items-center gap-1 shadow-sm transition-all cursor-pointer disabled:opacity-50"
                    >
                        <CheckIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Setujui Semua</span>
                    </button>
                </div>
            </div>
        </transition>

        <!-- PHOTO / ATTACHMENT PREVIEW MODAL (LIGHTBOX) -->
        <div 
            v-if="photoModalOpen" 
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-sm p-4"
        >
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-slate-900 truncate">
                            {{ photoModalData?.attendance?.user?.name || 'Bukti Lampiran' }}
                        </h3>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">
                            {{ formatDate(photoModalData?.attendance?.date) }} • {{ typeLabel(photoModalData?.attendance?.status) }}
                        </p>
                    </div>
                    <button 
                        @click="photoModalOpen = false" 
                        type="button"
                        class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors cursor-pointer"
                    >
                        <XMarkIcon class="w-4 h-4 stroke-[2.5]" />
                    </button>
                </div>

                <!-- Modal Body: Current Attachment -->
                <div class="p-4 overflow-y-auto flex-1 flex flex-col items-center justify-center bg-slate-950/5">
                    <div 
                        v-if="photoModalData?.items?.[activePhotoIndex]?.isPdf" 
                        class="text-center py-12 px-6 space-y-4"
                    >
                        <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-500 mx-auto flex items-center justify-center">
                            <DocumentTextIcon class="w-8 h-8 stroke-[1.8]" />
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 text-sm">Dokumen Surat / PDF</h4>
                            <p class="text-xs text-slate-500 mt-1">Berkas lampiran ini berupa file dokumen format PDF.</p>
                        </div>
                        <a 
                            :href="photoModalData.items[activePhotoIndex].url" 
                            target="_blank" 
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#0B7068] hover:bg-[#095c55] text-white rounded-xl text-xs font-bold shadow-sm transition-all"
                        >
                            <ArrowDownTrayIcon class="w-4 h-4 stroke-[2.2]" />
                            <span>Buka / Unduh Dokumen PDF</span>
                        </a>
                    </div>
                    
                    <div v-else class="w-full flex flex-col items-center">
                        <img 
                            :src="photoModalData?.items?.[activePhotoIndex]?.url" 
                            :alt="photoModalData?.items?.[activePhotoIndex]?.label"
                            class="max-w-full max-h-[60vh] object-contain rounded-2xl shadow-sm border border-slate-200 bg-white"
                        />
                        <span class="text-xs text-slate-500 font-semibold mt-2.5">
                            {{ photoModalData?.items?.[activePhotoIndex]?.label }}
                        </span>
                    </div>

                    <!-- Multi-attachment Tabs / Switcher if > 1 -->
                    <div 
                        v-if="photoModalData?.items?.length > 1" 
                        class="flex items-center gap-2 mt-4 pt-2 border-t border-slate-100 w-full justify-center"
                    >
                        <button
                            v-for="(item, idx) in photoModalData.items"
                            :key="idx"
                            @click="activePhotoIndex = idx"
                            type="button"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer',
                                activePhotoIndex === idx 
                                    ? 'bg-[#0B7068] text-white shadow-2xs' 
                                    : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'
                            ]"
                        >
                            Foto {{ idx + 1 }}
                        </button>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between">
                    <a 
                        v-if="photoModalData?.items?.[activePhotoIndex]?.url" 
                        :href="photoModalData.items[activePhotoIndex].url" 
                        target="_blank" 
                        class="text-xs font-bold text-[#0B7068] hover:underline flex items-center gap-1"
                    >
                        <span>Buka ukuran penuh</span>
                    </a>
                    <button 
                        @click="photoModalOpen = false" 
                        type="button"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- SINGLE REJECTION MODAL -->
        <div 
            v-if="rejectModalOpen" 
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
        >
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-5 sm:p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <XCircleIcon class="w-5 h-5 stroke-[2]" />
                        </div>
                        <h3 class="text-sm font-bold text-slate-900">Konfirmasi Penolakan</h3>
                    </div>
                    <button 
                        @click="rejectModalOpen = false" 
                        type="button" 
                        class="text-slate-400 hover:text-slate-600 cursor-pointer"
                    >
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
                <div>
                    <p class="text-xs text-slate-500 mb-2 leading-relaxed">
                        Mohon masukkan catatan atau alasan penolakan untuk <span class="font-bold text-slate-800">{{ selectedAttendance?.user?.name }}</span>:
                    </p>
                    <textarea 
                        v-model="rejectionReason" 
                        rows="3" 
                        placeholder="Contoh: Lampiran surat dokter belum dilampirkan atau tanggal dinas luar tidak sesuai..."
                        class="w-full border-slate-200 rounded-2xl shadow-2xs text-xs font-medium focus:border-rose-500 focus:ring focus:ring-rose-500/20 p-3"
                    ></textarea>
                </div>
                <div class="flex justify-end gap-2.5 pt-1">
                    <button 
                        @click="rejectModalOpen = false" 
                        type="button" 
                        class="px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl text-xs font-bold transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        @click="submitReject" 
                        :disabled="form.processing"
                        type="button" 
                        class="px-4 py-2 bg-rose-600 text-white hover:bg-rose-700 rounded-xl text-xs font-bold shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
                    >
                        {{ form.processing ? 'Memproses...' : 'Konfirmasi Tolak' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- BULK REJECTION MODAL -->
        <div 
            v-if="bulkRejectModalOpen" 
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
        >
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-5 sm:p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <XCircleIcon class="w-5 h-5 stroke-[2]" />
                        </div>
                        <h3 class="text-sm font-bold text-slate-900">Tolak {{ selectedIds.length }} Pengajuan</h3>
                    </div>
                    <button 
                        @click="bulkRejectModalOpen = false" 
                        type="button" 
                        class="text-slate-400 hover:text-slate-600 cursor-pointer"
                    >
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
                <div>
                    <p class="text-xs text-slate-500 mb-2 leading-relaxed">
                        Masukkan alasan penolakan untuk <span class="font-bold text-slate-800">{{ selectedIds.length }} pengajuan terpilih</span>:
                    </p>
                    <textarea 
                        v-model="bulkRejectionReason" 
                        rows="3" 
                        placeholder="Tuliskan alasan penolakan massal..."
                        class="w-full border-slate-200 rounded-2xl shadow-2xs text-xs font-medium focus:border-rose-500 focus:ring focus:ring-rose-500/20 p-3"
                    ></textarea>
                </div>
                <div class="flex justify-end gap-2.5 pt-1">
                    <button 
                        @click="bulkRejectModalOpen = false" 
                        type="button" 
                        class="px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl text-xs font-bold transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        @click="submitBulkReject" 
                        :disabled="isBulkProcessing" 
                        type="button" 
                        class="px-4 py-2 bg-rose-600 text-white hover:bg-rose-700 rounded-xl text-xs font-bold shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
                    >
                        {{ isBulkProcessing ? 'Memproses...' : 'Tolak Massal' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- REJECTION DETAIL MODAL (HISTORY TAB) -->
        <div 
            v-if="detailModalOpen" 
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
        >
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-5 sm:p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <InformationCircleIcon class="w-5 h-5 stroke-[2]" />
                        </div>
                        <h3 class="text-sm font-bold text-slate-900">Detail Keputusan Penolakan</h3>
                    </div>
                    <button 
                        @click="detailModalOpen = false" 
                        type="button" 
                        class="text-slate-400 hover:text-slate-600 cursor-pointer"
                    >
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
                <div class="space-y-3">
                    <div>
                        <span class="text-[10px] text-slate-400 font-semibold block uppercase tracking-wider">Pegawai</span>
                        <p class="text-xs font-bold text-slate-900 mt-0.5">
                            {{ selectedDetailAttendance?.user?.name }} ({{ typeLabel(selectedDetailAttendance?.status) }})
                        </p>
                    </div>
                    <div v-if="selectedDetailAttendance?.approver">
                        <span class="text-[10px] text-slate-400 font-semibold block uppercase tracking-wider">Ditolak Oleh</span>
                        <p class="text-xs font-medium text-slate-800 mt-0.5">
                            {{ selectedDetailAttendance.approver.name }}
                        </p>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-semibold block uppercase tracking-wider">Alasan Penolakan</span>
                        <div class="mt-1 p-3 bg-rose-50/70 border border-rose-100 rounded-xl text-xs text-rose-800 leading-relaxed font-medium">
                            {{ selectedDetailAttendance?.rejection_reason || 'Tidak ada catatan alasan penolakan yang dicantumkan.' }}
                        </div>
                    </div>
                </div>
                <div class="flex justify-end pt-1">
                    <button 
                        @click="detailModalOpen = false" 
                        type="button" 
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </component>
</template>
