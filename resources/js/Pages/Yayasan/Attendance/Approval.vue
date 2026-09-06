<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { 
    InboxIcon, PaperClipIcon, CameraIcon, CheckCircleIcon, XCircleIcon, 
    ClockIcon, MagnifyingGlassIcon
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

const approvalsList = computed(() => {
    if (Array.isArray(props.approvals)) return props.approvals;
    if (props.approvals?.data && Array.isArray(props.approvals.data)) return props.approvals.data;
    return [];
});

const activeTab = ref(props.activeTab || 'pending');
const search = ref(props.filters?.search || '');

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

const switchTab = (tab) => {
    activeTab.value = tab;
    selectedIds.value = [];
    router.get(route('yayasan.attendance-approvals.index'), {
        status: tab,
        search: search.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const handleSearch = () => {
    selectedIds.value = [];
    router.get(route('yayasan.attendance-approvals.index'), {
        status: activeTab.value,
        search: search.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const form = useForm({
    status: null,
    reason: null,
});

const rejectModalOpen = ref(false);
const selectedAttendance = ref(null);
const rejectionReason = ref('');

// Bulk Rejection Modal State
const bulkRejectModalOpen = ref(false);
const bulkRejectionReason = ref('');
const isBulkProcessing = ref(false);

const approve = (attendance) => {
    if (!confirm(`Setujui pengajuan ${typeLabel(attendance.status)} dari ${attendance.user?.name}?`)) return;
    
    form.status = 'approved';
    form.reason = null;
    form.put(route('yayasan.attendance-approvals.update', attendance.id));
};

const openRejectModal = (attendance) => {
    selectedAttendance.value = attendance;
    rejectionReason.value = '';
    rejectModalOpen.value = true;
};

const submitReject = () => {
    if (!rejectionReason.value) {
        alert('Mohon isi alasan penolakan.');
        return;
    }

    form.status = 'rejected';
    form.reason = rejectionReason.value;
    form.put(route('yayasan.attendance-approvals.update', selectedAttendance.value.id), {
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
    return dayjs(dateStr).format('DD MMMM YYYY');
};
</script>

<template>
    <Head title="Penyetujuan Absensi" />

    <AuthenticatedLayout>
        <div class="py-6 max-w-7xl mx-auto space-y-6">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="font-extrabold text-2xl text-gray-900 leading-tight">
                        Penyetujuan Absensi & Izin
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Kelola persetujuan dan riwayat pengajuan izin/sakit/dinas luar pegawai.
                    </p>
                </div>
                <Link :href="route('yayasan.dashboard')" class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-namira-teal transition-colors">
                    Kembali ke Dashboard
                </Link>
            </div>

            <!-- Tab Navigation & Search Bar -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-150 p-4 flex flex-col md:flex-row items-center justify-between gap-4">
                <!-- Tabs -->
                <div class="flex items-center bg-gray-100 p-1.5 rounded-2xl w-full md:w-auto">
                    <button 
                        @click="switchTab('pending')"
                        :class="[
                            'flex-1 md:flex-none px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-2 cursor-pointer',
                            activeTab === 'pending' 
                                ? 'bg-white text-gray-900 shadow-sm font-extrabold' 
                                : 'text-gray-500 hover:text-gray-800'
                        ]"
                    >
                        <ClockIcon class="w-4 h-4 text-amber-500" />
                        <span>Menunggu Persetujuan</span>
                        <span v-if="pendingCount > 0" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-500 text-white shadow-xs">
                            {{ pendingCount }}
                        </span>
                    </button>

                    <button 
                        @click="switchTab('history')"
                        :class="[
                            'flex-1 md:flex-none px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-2 cursor-pointer',
                            activeTab === 'history' 
                                ? 'bg-white text-gray-900 shadow-sm font-extrabold' 
                                : 'text-gray-500 hover:text-gray-800'
                        ]"
                    >
                        <CheckCircleIcon class="w-4 h-4 text-emerald-500" />
                        <span>Riwayat Persetujuan</span>
                    </button>
                </div>

                <!-- Search Input -->
                <div class="relative w-full md:w-72">
                    <input 
                        v-model="search"
                        @keyup.enter="handleSearch"
                        type="text" 
                        placeholder="Cari nama pegawai..." 
                        class="w-full bg-slate-50 border border-gray-200 rounded-xl text-xs font-semibold pl-9 pr-4 py-2.5 focus:bg-white focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all"
                    />
                    <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 absolute left-3 top-3" />
                </div>
            </div>

            <!-- Bulk Actions Bar (When items are selected in pending tab) -->
            <transition 
                enter-active-class="transition duration-200 ease-out" 
                enter-from-class="opacity-0 -translate-y-2 scale-98" 
                enter-to-class="opacity-100 translate-y-0 scale-100" 
                leave-active-class="transition duration-150 ease-in" 
                leave-from-class="opacity-100 translate-y-0 scale-100" 
                leave-to-class="opacity-0 -translate-y-2 scale-98"
            >
                <div v-if="activeTab === 'pending' && selectedIds.length > 0" class="bg-slate-900 text-white p-4 rounded-3xl shadow-xl border border-slate-800 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1 rounded-full bg-namira-teal/20 text-teal-300 font-extrabold text-xs border border-namira-teal/30">
                            {{ selectedIds.length }} Pengajuan Dipilih
                        </span>
                        <span class="text-xs text-slate-300 font-medium hidden sm:inline">
                            Pilih aksi persetujuan atau penolakan massal untuk data terpilih
                        </span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <button 
                            @click="bulkApprove" 
                            :disabled="isBulkProcessing"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs flex items-center gap-1.5 shadow-md active:scale-95 transition-all cursor-pointer disabled:opacity-50"
                        >
                            <CheckCircleIcon class="w-4 h-4" />
                            <span>Setujui Semua ({{ selectedIds.length }})</span>
                        </button>
                        <button 
                            @click="openBulkRejectModal" 
                            :disabled="isBulkProcessing"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl text-xs flex items-center gap-1.5 shadow-md active:scale-95 transition-all cursor-pointer disabled:opacity-50"
                        >
                            <XCircleIcon class="w-4 h-4" />
                            <span>Tolak Semua ({{ selectedIds.length }})</span>
                        </button>
                        <button 
                            @click="selectedIds = []" 
                            class="px-3 py-2 text-slate-400 hover:text-white text-xs font-semibold transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                    </div>
                </div>
            </transition>

            <!-- Content Card -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-150 overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-base font-extrabold text-gray-900">
                            {{ activeTab === 'pending' ? 'Daftar Pengajuan Pending' : 'Riwayat Keputusan' }}
                        </h3>
                        <div v-if="activeTab === 'pending' && approvalsList.length > 0" class="text-xs text-gray-400 font-medium">
                            Menampilkan {{ approvalsList.length }} data di halaman ini
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="approvalsList.length === 0" class="text-center py-16 text-gray-400 border-2 border-dashed border-gray-100 rounded-2xl bg-gray-50/50">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white shadow-sm mb-4">
                            <InboxIcon class="w-8 h-8 text-gray-300" />
                        </div>
                        <h4 class="text-base font-bold text-gray-800">
                            {{ activeTab === 'pending' ? 'Tidak Ada Pengajuan Pending' : 'Belum Ada Riwayat Persetujuan' }}
                        </h4>
                        <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
                            {{ activeTab === 'pending' 
                                ? 'Semua pengajuan izin/sakit/dinas luar pegawai saat ini telah selesai diproses.' 
                                : 'Belum ada catatan persetujuan atau penolakan pengajuan untuk kriteria ini.' }}
                        </p>
                    </div>

                    <!-- Table Content (Desktop View) -->
                    <div v-else class="hidden md:block overflow-x-auto rounded-2xl border border-gray-150">
                        <table class="w-full text-xs text-left text-gray-600">
                            <thead class="text-xs text-gray-700 uppercase bg-slate-50 border-b border-gray-150 font-bold">
                                <tr>
                                    <!-- Select All Checkbox -->
                                    <th v-if="activeTab === 'pending'" class="w-12 px-4 py-4 text-center">
                                        <input 
                                            type="checkbox" 
                                            :checked="isAllSelected" 
                                            @change="toggleSelectAll" 
                                            class="rounded border-gray-300 text-namira-teal focus:ring-namira-teal/30 cursor-pointer w-4 h-4" 
                                            title="Pilih Semua Halaman Ini"
                                        />
                                    </th>
                                    <th class="px-5 py-4">Nama Pegawai</th>
                                    <th class="px-5 py-4">Tanggal Presensi</th>
                                    <th class="px-5 py-4">Jenis Pengajuan</th>
                                    <th class="px-5 py-4">Catatan Keterangan</th>
                                    <th class="px-5 py-4">Bukti Lampiran</th>
                                    <th v-if="activeTab === 'history'" class="px-5 py-4">Status & Keputusan</th>
                                    <th class="px-5 py-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr 
                                    v-for="att in approvalsList" 
                                    :key="att.id" 
                                    class="hover:bg-slate-50/60 transition-colors"
                                    :class="{ 'bg-teal-50/40': selectedIds.includes(att.id) }"
                                >
                                    <!-- Row Checkbox -->
                                    <td v-if="activeTab === 'pending'" class="w-12 px-4 py-4 text-center">
                                        <input 
                                            type="checkbox" 
                                            :value="att.id" 
                                            v-model="selectedIds" 
                                            class="rounded border-gray-300 text-namira-teal focus:ring-namira-teal/30 cursor-pointer w-4 h-4" 
                                        />
                                    </td>

                                    <!-- Employee Info -->
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <img v-if="att.user?.profile_photo_url" :src="att.user.profile_photo_url" class="w-8 h-8 rounded-full object-cover border border-gray-100 shadow-xs" />
                                            <div>
                                                <div class="font-extrabold text-gray-900 text-sm leading-snug">{{ att.user?.name || '-' }}</div>
                                                <div class="text-[10px] text-gray-400 font-medium">{{ att.user?.email || '-' }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Date -->
                                    <td class="px-5 py-4 font-bold text-gray-800">
                                        {{ formatDate(att.date) }}
                                    </td>

                                    <!-- Status Type -->
                                    <td class="px-5 py-4">
                                        <span :class="[
                                            'px-2.5 py-1 rounded-full font-extrabold text-[10px] inline-block shadow-xs',
                                            att.status === 'business_trip' ? 'bg-blue-100 text-blue-800' :
                                            att.status === 'sick' ? 'bg-purple-100 text-purple-800' : 'bg-amber-100 text-amber-800'
                                        ]">
                                            {{ typeLabel(att.status) }}
                                        </span>
                                    </td>

                                    <!-- Note -->
                                    <td class="px-5 py-4 italic text-gray-600 max-w-[200px]">
                                        "{{ att.note || '-' }}"
                                    </td>

                                    <!-- Attachment -->
                                    <td class="px-5 py-4">
                                        <a v-if="att.permit_file" :href="`/storage/${att.permit_file}`" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-100 transition-colors font-bold text-xs shadow-xs">
                                            <PaperClipIcon class="w-3.5 h-3.5" />
                                            Dokumen
                                        </a>
                                        <a v-else-if="att.check_in_photo" :href="`/storage/${att.check_in_photo}`" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-100 transition-colors font-bold text-xs shadow-xs">
                                            <CameraIcon class="w-3.5 h-3.5" />
                                            Foto Selfie
                                        </a>
                                        <span v-else class="text-gray-400 font-mono text-[10px]">-</span>
                                    </td>

                                    <!-- History Decision (History Tab) -->
                                    <td v-if="activeTab === 'history'" class="px-5 py-4">
                                        <div class="space-y-1">
                                            <span v-if="att.approval_status === 'approved'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">
                                                <CheckCircleIcon class="w-3.5 h-3.5 text-emerald-600" />
                                                Disetujui
                                            </span>
                                            <span v-else-if="att.approval_status === 'rejected'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800">
                                                <XCircleIcon class="w-3.5 h-3.5 text-rose-600" />
                                                Ditolak
                                            </span>

                                            <div v-if="att.approver" class="text-[10px] text-gray-500 font-medium">
                                                Oleh: <span class="font-bold text-gray-700">{{ att.approver.name }}</span>
                                            </div>
                                            <div v-if="att.rejection_reason" class="text-[10px] text-rose-600 italic">
                                                Catatan: {{ att.rejection_reason }}
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Action -->
                                    <td class="px-5 py-4 text-center">
                                        <div v-if="activeTab === 'pending'" class="flex items-center justify-center gap-2">
                                            <button 
                                                @click="approve(att)" 
                                                class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-xs transition-all flex items-center gap-1 cursor-pointer"
                                            >
                                                Setujui
                                            </button>
                                            <button 
                                                @click="openRejectModal(att)" 
                                                class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-xs transition-all flex items-center gap-1 cursor-pointer"
                                            >
                                                Tolak
                                            </button>
                                        </div>
                                        <span v-else class="text-[11px] text-gray-400 font-mono">Selesai</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card List (Android / Mobile View) -->
                    <div v-if="approvalsList.length > 0" class="block md:hidden space-y-3">
                        <div 
                            v-for="att in approvalsList" 
                            :key="'mobile-' + att.id" 
                            class="p-4 bg-white rounded-2xl border border-gray-200 shadow-sm space-y-3"
                            :class="{ 'border-namira-teal ring-2 ring-namira-teal/20 bg-teal-50/20': selectedIds.includes(att.id) }"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <input 
                                        v-if="activeTab === 'pending'" 
                                        type="checkbox" 
                                        :value="att.id" 
                                        v-model="selectedIds" 
                                        class="rounded border-gray-300 text-namira-teal focus:ring-namira-teal/30 cursor-pointer w-4 h-4 shrink-0" 
                                    />
                                    <img v-if="att.user?.profile_photo_url" :src="att.user.profile_photo_url" class="w-10 h-10 rounded-full object-cover border border-gray-100 shadow-xs" />
                                    <div v-else class="w-10 h-10 rounded-full bg-slate-100 text-gray-500 font-bold flex items-center justify-center text-sm">
                                        {{ (att.user?.name || '-').charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="font-extrabold text-gray-900 text-sm leading-snug">{{ att.user?.name || '-' }}</div>
                                        <div class="text-[11px] text-gray-400 font-medium">{{ formatDate(att.date) }}</div>
                                    </div>
                                </div>
                                <span :class="[
                                    'px-2.5 py-1 rounded-full font-extrabold text-[10px] inline-block shadow-xs shrink-0',
                                    att.status === 'business_trip' ? 'bg-blue-100 text-blue-800' :
                                    att.status === 'sick' ? 'bg-purple-100 text-purple-800' : 'bg-amber-100 text-amber-800'
                                ]">
                                    {{ typeLabel(att.status) }}
                                </span>
                            </div>

                            <div v-if="att.note" class="p-2.5 bg-slate-50 rounded-xl text-xs text-gray-600 italic border border-gray-100">
                                "{{ att.note }}"
                            </div>

                            <div class="flex items-center justify-between pt-1">
                                <div>
                                    <a v-if="att.permit_file" :href="`/storage/${att.permit_file}`" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-600 rounded-xl font-bold text-xs">
                                        <PaperClipIcon class="w-3.5 h-3.5" /> Dokumen
                                    </a>
                                    <a v-else-if="att.check_in_photo" :href="`/storage/${att.check_in_photo}`" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-xl font-bold text-xs">
                                        <CameraIcon class="w-3.5 h-3.5" /> Foto Selfie
                                    </a>
                                </div>

                                <div v-if="activeTab === 'pending'" class="flex items-center gap-2">
                                    <button 
                                        @click="approve(att)" 
                                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs active:scale-95 transition-all shadow-xs"
                                    >
                                        Setujui
                                    </button>
                                    <button 
                                        @click="openRejectModal(att)" 
                                        class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs active:scale-95 transition-all shadow-xs"
                                    >
                                        Tolak
                                    </button>
                                </div>
                                <div v-else-if="activeTab === 'history'">
                                    <span v-if="att.approval_status === 'approved'" class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">Disetujui</span>
                                    <span v-else-if="att.approval_status === 'rejected'" class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-100 text-rose-800">Ditolak</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination Bar -->
                    <div v-if="approvals?.links && approvals.links.length > 3" class="mt-6 flex justify-center pt-2">
                        <Pagination :links="approvals.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Single Rejection Modal -->
        <div v-if="rejectModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-base font-extrabold text-gray-900">Konfirmasi Penolakan Pengajuan</h3>
                    <button @click="rejectModalOpen = false" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-2">
                        Mohon masukkan catatan atau alasan penolakan untuk <span class="font-bold text-gray-800">{{ selectedAttendance?.user?.name }}</span>:
                    </p>
                    <textarea 
                        v-model="rejectionReason" 
                        rows="4" 
                        placeholder="Tuliskan alasan penolakan..."
                        class="w-full border-gray-200 rounded-2xl shadow-xs text-xs font-semibold focus:border-rose-500 focus:ring focus:ring-rose-500/20"
                    ></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button @click="rejectModalOpen = false" class="px-4 py-2 border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl text-xs font-bold transition-colors cursor-pointer">Batal</button>
                    <button @click="submitReject" class="px-5 py-2 bg-rose-600 text-white hover:bg-rose-700 rounded-xl text-xs font-bold shadow-xs transition-colors cursor-pointer">Konfirmasi Tolak</button>
                </div>
            </div>
        </div>

        <!-- Bulk Rejection Modal -->
        <div v-if="bulkRejectModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-base font-extrabold text-gray-900">Tolak {{ selectedIds.length }} Pengajuan Massal</h3>
                    <button @click="bulkRejectModalOpen = false" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-2">
                        Mohon masukkan catatan atau alasan penolakan untuk <span class="font-bold text-gray-800">{{ selectedIds.length }} pengajuan terpilih</span>:
                    </p>
                    <textarea 
                        v-model="bulkRejectionReason" 
                        rows="4" 
                        placeholder="Tuliskan alasan penolakan massal..."
                        class="w-full border-gray-200 rounded-2xl shadow-xs text-xs font-semibold focus:border-rose-500 focus:ring focus:ring-rose-500/20"
                    ></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button @click="bulkRejectModalOpen = false" class="px-4 py-2 border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl text-xs font-bold transition-colors cursor-pointer">Batal</button>
                    <button @click="submitBulkReject" :disabled="isBulkProcessing" class="px-5 py-2 bg-rose-600 text-white hover:bg-rose-700 rounded-xl text-xs font-bold shadow-xs transition-colors cursor-pointer disabled:opacity-50">Konfirmasi Tolak Massal</button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
