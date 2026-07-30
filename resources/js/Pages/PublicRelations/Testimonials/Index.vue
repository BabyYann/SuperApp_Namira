<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    MagnifyingGlassIcon, PlusIcon, ChatBubbleLeftRightIcon, PencilSquareIcon, TrashIcon,
    ExclamationTriangleIcon, UserIcon, CheckCircleIcon, XCircleIcon, ClockIcon,
    DocumentTextIcon, ChatBubbleBottomCenterTextIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    testimonials: Object,
    units: Array,
    counts: Object,
    is_approver: Boolean,
    filters: Object,
});

const searchForm = useForm({
    search: props.filters?.search || '',
    unit_id: props.filters?.unit_id || '',
    approval_status: props.filters?.approval_status || 'all',
});

const isAdminView = computed(() => props.units && props.units.length > 1);

const filterStatus = (statusKey) => {
    searchForm.approval_status = statusKey;
    searchForm.get(route('public-relations.testimonials.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const applyFilters = () => {
    searchForm.get(route('public-relations.testimonials.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

// Approval / Rejection Logic
const showRejectModal = ref(false);
const itemToReject = ref(null);
const rejectionNote = ref('');
const actionForm = useForm({
    rejection_note: '',
});

const approveItem = (item) => {
    if (confirm(`Setujui testimoni dari "${item.name}"?`)) {
        actionForm.post(route('public-relations.testimonials.approve', item.id), {
            preserveScroll: true,
        });
    }
};

const openRejectModal = (item) => {
    itemToReject.value = item;
    rejectionNote.value = '';
    showRejectModal.value = true;
};

const submitReject = () => {
    if (!rejectionNote.value.trim()) return;
    actionForm.rejection_note = rejectionNote.value;
    actionForm.post(route('public-relations.testimonials.reject', itemToReject.value.id), {
        onSuccess: () => {
            showRejectModal.value = false;
            itemToReject.value = null;
        }
    });
};

// Rejection Note Modal
const showNoteModal = ref(false);
const selectedNote = ref('');
const viewNote = (note) => {
    selectedNote.value = note;
    showNoteModal.value = true;
};

// Delete Logic
const showDeleteConfirm = ref(false);
const itemToDelete = ref(null);
const deleteForm = useForm({});

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteConfirm.value = true;
};

const closeModal = () => {
    showDeleteConfirm.value = false;
    setTimeout(() => { itemToDelete.value = null; }, 300);
};

const deleteItem = () => {
    if (!itemToDelete.value) return;
    deleteForm.delete(route('public-relations.testimonials.destroy', itemToDelete.value.id), {
        onSuccess: () => closeModal(),
        onError: () => { closeModal(); alert('Gagal menghapus data.'); }
    });
};

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);

const currentPage = computed(() => props.testimonials.current_page || props.testimonials.meta?.current_page || 1);
const perPage = computed(() => props.testimonials.per_page || props.testimonials.meta?.per_page || 10);

const getApprovalBadge = (status) => {
    switch (status) {
        case 'published':
            return { label: 'Terbit', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        case 'pending':
            return { label: 'Menunggu Verifikasi', class: 'bg-amber-50 text-amber-700 border-amber-200 animate-pulse' };
        case 'rejected':
            return { label: 'Perlu Revisi', class: 'bg-rose-50 text-rose-700 border-rose-200' };
        default:
            return { label: 'Draft', class: 'bg-slate-100 text-slate-600 border-slate-200' };
    }
};
</script>

<template>
    <Head title="Testimoni Sekolah" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight flex items-center gap-2">
                    <ChatBubbleLeftRightIcon class="w-6 h-6 text-namira-teal" />
                    Testimoni
                </h2>
                <p class="text-sm text-gray-500 mt-1">Kelola dan verifikasi testimoni wali murid, alumni, dan tokoh masyarakat.</p>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6">

            <!-- Flash Success -->
            <transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="successMessage" class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 rounded-2xl px-5 py-3 text-sm font-medium shadow-sm">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    {{ successMessage }}
                </div>
            </transition>

            <!-- STATUS FILTER TABS -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                <button 
                    @click="filterStatus('all')"
                    class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2 shadow-sm"
                    :class="(searchForm.approval_status === 'all' || !searchForm.approval_status) ? 'bg-namira-teal text-white shadow-namira-teal/20' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'"
                >
                    <span>Semua Testimoni</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="(searchForm.approval_status === 'all' || !searchForm.approval_status) ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-700'">{{ counts?.all || 0 }}</span>
                </button>

                <button 
                    @click="filterStatus('pending')"
                    class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2 shadow-sm"
                    :class="searchForm.approval_status === 'pending' ? 'bg-amber-500 text-white shadow-amber-500/20' : 'bg-white text-amber-700 hover:bg-amber-50 border border-amber-200'"
                >
                    <ClockIcon class="w-4 h-4" />
                    <span>Menunggu Verifikasi</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="searchForm.approval_status === 'pending' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-800'">{{ counts?.pending || 0 }}</span>
                </button>

                <button 
                    @click="filterStatus('published')"
                    class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2 shadow-sm"
                    :class="searchForm.approval_status === 'published' ? 'bg-emerald-600 text-white shadow-emerald-600/20' : 'bg-white text-emerald-700 hover:bg-emerald-50 border border-emerald-200'"
                >
                    <CheckCircleIcon class="w-4 h-4" />
                    <span>Terbit</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="searchForm.approval_status === 'published' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800'">{{ counts?.published || 0 }}</span>
                </button>

                <button 
                    @click="filterStatus('rejected')"
                    class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2 shadow-sm"
                    :class="searchForm.approval_status === 'rejected' ? 'bg-rose-600 text-white shadow-rose-600/20' : 'bg-white text-rose-700 hover:bg-rose-50 border border-rose-200'"
                >
                    <XCircleIcon class="w-4 h-4" />
                    <span>Perlu Revisi</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="searchForm.approval_status === 'rejected' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-800'">{{ counts?.rejected || 0 }}</span>
                </button>

                <button 
                    @click="filterStatus('draft')"
                    class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2 shadow-sm"
                    :class="searchForm.approval_status === 'draft' ? 'bg-slate-700 text-white shadow-slate-700/20' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'"
                >
                    <DocumentTextIcon class="w-4 h-4" />
                    <span>Draft</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="searchForm.approval_status === 'draft' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'">{{ counts?.draft || 0 }}</span>
                </button>
            </div>

            <!-- Toolbar -->
            <div class="flex flex-col md:flex-row items-stretch gap-3">
                <!-- Search -->
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                        <MagnifyingGlassIcon class="w-5 h-5" />
                    </div>
                    <input
                        v-model="searchForm.search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Cari nama atau isi kutipan..."
                        class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                    />
                </div>

                <!-- Filter Unit (admin only) -->
                <select v-if="isAdminView" v-model="searchForm.unit_id" @change="applyFilters" class="h-[46px] rounded-2xl border-white/50 bg-white/50 text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 shadow-sm px-3 pr-8">
                    <option value="">Semua Unit</option>
                    <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                </select>

                <Link :href="route('public-relations.testimonials.create')"
                    class="px-5 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 active:scale-95 h-[46px] whitespace-nowrap">
                    <PlusIcon class="w-5 h-5" />
                    <span>Tambah Testimoni</span>
                </Link>
            </div>

            <!-- Data Table -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/50 border-b border-gray-100 text-xs uppercase text-gray-500 font-extrabold tracking-wider">
                                <th class="p-5">No</th>
                                <th class="p-5">Unit</th>
                                <th class="p-5">Nama</th>
                                <th class="p-5">Peran / Jabatan</th>
                                <th class="p-5">Kutipan Testimoni</th>
                                <th class="p-5">Status Verifikasi</th>
                                <th class="p-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <!-- Empty State -->
                            <tr v-if="testimonials.data.length === 0">
                                <td colspan="7" class="p-14 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                            <ChatBubbleLeftRightIcon class="w-10 h-10 text-gray-300" />
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900 mb-1">Belum ada data testimoni.</h3>
                                        <p class="text-sm">Mulai tambahkan testimoni unit sekolah Anda.</p>
                                    </div>
                                </td>
                            </tr>

                            <!-- Rows -->
                            <tr v-for="(item, index) in testimonials.data" :key="item.id" class="hover:bg-teal-50/30 transition-colors group">
                                <td class="p-5 text-sm text-gray-400 font-mono">
                                    {{ (currentPage - 1) * perPage + index + 1 }}
                                </td>
                                <td class="p-5 text-sm text-gray-600 font-medium">
                                    {{ item.unit?.name || '-' }}
                                </td>
                                <td class="p-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-100 border border-gray-100 shrink-0 flex items-center justify-center">
                                            <img v-if="item.photo_path" :src="'/' + item.photo_path" class="w-full h-full object-cover" />
                                            <UserIcon v-else class="w-5 h-5 text-gray-400" />
                                        </div>
                                        <span class="font-bold text-gray-800 text-sm group-hover:text-namira-teal transition-colors line-clamp-1 max-w-[200px]">{{ item.name }}</span>
                                    </div>
                                </td>
                                <td class="p-5 text-sm text-gray-600">
                                    <span class="px-3 py-1 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700">
                                        {{ item.role_or_title }}
                                    </span>
                                </td>
                                <td class="p-5 text-sm text-gray-600 max-w-sm">
                                    <p class="line-clamp-2 italic">"{{ item.quote }}"</p>
                                </td>
                                <td class="p-5">
                                    <div class="flex flex-col gap-1">
                                        <span :class="getApprovalBadge(item.approval_status).class" class="px-3 py-1 rounded-xl text-xs font-bold border shadow-sm w-fit">
                                            {{ getApprovalBadge(item.approval_status).label }}
                                        </span>
                                        <button v-if="item.approval_status === 'rejected' && item.rejection_note" @click="viewNote(item.rejection_note)" class="text-[11px] text-rose-600 underline font-medium hover:text-rose-800 text-left">
                                            Lihat Catatan Revisi
                                        </button>
                                    </div>
                                </td>
                                <td class="p-5 text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        <!-- Verifier Quick Actions -->
                                        <template v-if="is_approver && item.approval_status === 'pending'">
                                            <button @click="approveItem(item)" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all flex items-center gap-1">
                                                <CheckCircleIcon class="w-4 h-4" />
                                                <span>Setujui</span>
                                            </button>
                                            <button @click="openRejectModal(item)" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all flex items-center gap-1">
                                                <XCircleIcon class="w-4 h-4" />
                                                <span>Tolak</span>
                                            </button>
                                        </template>

                                        <Link :href="route('public-relations.testimonials.edit', item.id)"
                                            class="p-2.5 text-gray-400 hover:text-namira-teal hover:bg-teal-50 rounded-xl transition-all duration-200 border border-transparent hover:border-teal-100"
                                            title="Edit">
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </Link>
                                        <button @click="confirmDelete(item)"
                                            class="p-2.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all duration-200 border border-transparent hover:border-red-100 cursor-pointer"
                                            title="Hapus">
                                            <TrashIcon class="w-5 h-5 pointer-events-none" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="testimonials.links && testimonials.links.length > 3" class="mt-4 flex justify-center">
                <div class="flex gap-1">
                    <Link v-for="(link, k) in testimonials.links" :key="k"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-4 py-2 rounded-lg border text-sm"
                        :class="[
                            link.active ? 'bg-namira-teal text-white border-namira-teal' : 'bg-white text-gray-500 hover:bg-gray-50',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>
        </div>

        <!-- Reject Note Input Modal -->
        <Teleport to="body">
            <div v-if="showRejectModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-gray-100 space-y-4">
                    <h3 class="font-bold text-lg text-gray-900 flex items-center gap-2">
                        <XCircleIcon class="w-6 h-6 text-rose-600" />
                        <span>Tolak & Minta Revisi Testimoni</span>
                    </h3>
                    <p class="text-xs text-gray-500">Berikan catatan perbaikan agar Humas dapat menyesuaikan testimoni ini.</p>
                    <textarea v-model="rejectionNote" rows="4" class="w-full rounded-2xl border-gray-200 text-sm focus:border-rose-500 focus:ring-rose-500/20" placeholder="Tuliskan alasan penolakan / instruksi revisi..."></textarea>
                    <div class="flex justify-end gap-3 pt-2">
                        <button @click="showRejectModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
                        <button @click="submitReject" :disabled="!rejectionNote.trim()" class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-lg shadow-rose-500/30 transition-all disabled:opacity-50">Kirim Revisi</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- View Rejection Note Modal -->
        <Teleport to="body">
            <div v-if="showNoteModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-gray-100 space-y-4">
                    <h3 class="font-bold text-lg text-rose-800 flex items-center gap-2">
                        <ChatBubbleBottomCenterTextIcon class="w-6 h-6 text-rose-600" />
                        <span>Catatan Revisi Verifikator</span>
                    </h3>
                    <div class="p-4 bg-rose-50 rounded-2xl border border-rose-100 text-sm text-rose-900">
                        {{ selectedNote }}
                    </div>
                    <div class="flex justify-end pt-2">
                        <button @click="showNoteModal = false" class="px-5 py-2 text-xs font-bold text-white bg-slate-700 hover:bg-slate-800 rounded-xl shadow-sm transition-colors">Tutup</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showDeleteConfirm" class="fixed inset-0 z-[9999] overflow-y-auto" role="dialog" aria-modal="true">
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal"></div>
                    <div class="flex min-h-full items-center justify-center p-4 text-center">
                        <Transition
                            enter-active-class="transition ease-out duration-300"
                            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                            leave-active-class="transition ease-in duration-200"
                            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        >
                            <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl sm:w-full sm:max-w-md border border-gray-100 p-8">
                                <div class="mx-auto flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-red-50 mb-6 animate-pulse">
                                    <ExclamationTriangleIcon class="h-10 w-10 text-red-600" />
                                </div>
                                <div class="text-center">
                                    <h3 class="text-xl font-bold text-gray-900 mb-2">Hapus Testimoni?</h3>
                                    <p class="text-sm text-gray-500">Anda akan menghapus testimoni dari <strong>{{ itemToDelete?.name }}</strong> secara permanen.</p>
                                </div>
                                <div class="mt-8 flex justify-center gap-3">
                                    <button type="button" @click="closeModal" :disabled="deleteForm.processing"
                                        class="inline-flex w-full justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:w-auto transition-all">
                                        Batal
                                    </button>
                                    <button type="button" @click="deleteItem" :disabled="deleteForm.processing"
                                        class="inline-flex w-full justify-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-red-500/30 hover:bg-red-700 sm:w-auto transition-all transform hover:scale-105">
                                        <span v-if="deleteForm.processing">Memproses...</span>
                                        <span v-else>Ya, Hapus</span>
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
