<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    MagnifyingGlassIcon, PlusIcon, BuildingOffice2Icon, PencilSquareIcon, TrashIcon, ExclamationTriangleIcon, LinkIcon,
    CheckCircleIcon, XCircleIcon, ClockIcon, DocumentTextIcon, ChatBubbleBottomCenterTextIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    partners: Object,
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
    searchForm.get(route('public-relations.partners.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const applyFilters = () => {
    searchForm.get(route('public-relations.partners.index'), {
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
    if (confirm(`Setujui mitra "${item.name}"?`)) {
        actionForm.post(route('public-relations.partners.approve', item.id), {
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
    actionForm.post(route('public-relations.partners.reject', itemToReject.value.id), {
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
    setTimeout(() => {
        itemToDelete.value = null;
    }, 300);
};

const deleteItem = () => {
    if (!itemToDelete.value) return;
    deleteForm.delete(route('public-relations.partners.destroy', itemToDelete.value.id), {
        onSuccess: () => closeModal(),
        onError: () => {
            closeModal();
            alert('Gagal menghapus mitra.');
        }
    });
};

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);

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
    <Head title="Manajemen Mitra Kemitraan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Manajemen Mitra & Kerjasama (MOU)
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Kelola dan verifikasi logo serta nama instansi partner unit sekolah Anda.</p>
                </div>
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
                    <span>Semua Mitra</span>
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

            <!-- Toolbar: Search & Actions -->
            <div class="flex flex-col md:flex-row items-center gap-4">
                <form @submit.prevent="applyFilters" class="relative group flex-1 w-full">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 group-focus-within:text-namira-teal transition-colors">
                        <MagnifyingGlassIcon class="w-5 h-5" />
                    </div>
                    <input 
                        v-model="searchForm.search"
                        type="text" 
                        placeholder="Cari nama instansi/mitra..." 
                        class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                    >
                </form>

                <select v-if="isAdminView" v-model="searchForm.unit_id" @change="applyFilters" class="h-[46px] rounded-2xl border-white/50 bg-white/50 text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 shadow-sm px-3 pr-8">
                    <option value="">Semua Unit</option>
                    <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                </select>

                <Link :href="route('public-relations.partners.create')" class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 active:scale-95 h-[46px] whitespace-nowrap">
                    <PlusIcon class="w-5 h-5" />
                    <span>Tambah Mitra</span>
                </Link>
            </div>

            <!-- Data Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/75 dark:bg-gray-800/50">
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider">Logo</th>
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider">Nama Instansi / Mitra</th>
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider">Website URL</th>
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider">Status Verifikasi</th>
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in partners.data" :key="item.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/25 transition-colors">
                                <td class="p-5">
                                    <div class="w-20 h-12 bg-slate-100 rounded-lg flex items-center justify-center p-1.5 overflow-hidden border border-gray-100">
                                        <img :src="'/' + item.logo_path" :alt="item.name" class="w-full h-full object-contain" />
                                    </div>
                                </td>
                                <td class="p-5">
                                    <div class="font-bold text-sm text-gray-900 dark:text-gray-100">{{ item.name }}</div>
                                    <div v-if="item.unit" class="text-xs text-gray-400 mt-0.5">{{ item.unit.name }}</div>
                                </td>
                                <td class="p-5">
                                    <a v-if="item.website_url" :href="item.website_url" target="_blank" class="inline-flex items-center gap-1.5 text-xs text-namira-teal hover:underline font-medium">
                                        <LinkIcon class="w-3.5 h-3.5" />
                                        <span>Kunjungi Situs</span>
                                    </a>
                                    <span v-else class="text-xs text-gray-400 font-light">-</span>
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
                                    <div class="flex items-center justify-end gap-2">
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

                                        <Link :href="route('public-relations.partners.edit', item.id)" class="p-2 text-gray-400 hover:text-namira-teal hover:bg-namira-teal/10 rounded-xl transition-all">
                                            <PencilSquareIcon class="w-4 h-4" />
                                        </Link>
                                        <button @click="confirmDelete(item)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all">
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="partners.data.length === 0">
                                <td colspan="5" class="p-10 text-center text-gray-400">
                                    <BuildingOffice2Icon class="w-12 h-12 mx-auto opacity-50 mb-3" />
                                    <p class="text-sm font-semibold">Belum ada data mitra.</p>
                                    <p class="text-xs mt-1">Silakan tambahkan data mitra untuk memulai.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="partners.links && partners.links.length > 3" class="flex justify-between items-center bg-white/50 backdrop-blur-sm px-6 py-4 rounded-2xl border border-white/50 shadow-sm">
                <div class="text-xs text-gray-500 font-medium">
                    Menampilkan {{ partners.from || 0 }} sampai {{ partners.to || 0 }} dari {{ partners.total || 0 }} data
                </div>
                <div class="flex items-center gap-1">
                    <template v-for="(link, i) in partners.links" :key="i">
                        <div v-if="link.url === null" 
                             class="px-3.5 py-1.5 text-xs text-gray-400 font-medium cursor-not-allowed bg-white/20 rounded-lg"
                             v-html="link.label"></div>
                        <Link v-else 
                              :href="link.url"
                              class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all"
                              :class="link.active ? 'bg-namira-teal text-white shadow-md shadow-namira-teal/20' : 'bg-white hover:bg-gray-50 text-gray-700'"
                              v-html="link.label"></Link>
                    </template>
                </div>
            </div>
        </div>

        <!-- Reject Note Input Modal -->
        <Teleport to="body">
            <div v-if="showRejectModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-gray-100 space-y-4">
                    <h3 class="font-bold text-lg text-gray-900 flex items-center gap-2">
                        <XCircleIcon class="w-6 h-6 text-rose-600" />
                        <span>Tolak & Minta Revisi Mitra</span>
                    </h3>
                    <p class="text-xs text-gray-500">Berikan catatan perbaikan agar Humas dapat menyesuaikan data mitra ini.</p>
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

        <!-- Delete Confirmation Modal -->
        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-4">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 overflow-y-auto bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 relative">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-red-50 rounded-2xl text-red-600">
                            <ExclamationTriangleIcon class="w-6 h-6" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-lg text-gray-900">Hapus Mitra</h3>
                            <p class="text-sm text-gray-500 mt-2">Apakah Anda yakin ingin menghapus mitra <strong class="text-gray-900">{{ itemToDelete?.name }}</strong>? Tindakan ini tidak dapat dibatalkan.</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                        <button @click="closeModal" class="px-5 py-2 text-xs font-bold text-gray-500 hover:bg-gray-50 rounded-xl transition-all">Batal</button>
                        <button @click="deleteItem" class="px-5 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 shadow-md shadow-red-600/20 rounded-xl transition-all" :disabled="deleteForm.processing">Hapus</button>
                    </div>
                </div>
            </div>
        </transition>
    </AuthenticatedLayout>
</template>
