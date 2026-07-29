<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    MagnifyingGlassIcon, PlusIcon, CalendarIcon, PencilSquareIcon, TrashIcon, 
    ExclamationTriangleIcon, CheckCircleIcon, XCircleIcon, ChatBubbleBottomCenterTextIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    events: Object,
    counts: Object,
    is_approver: Boolean,
    filters: Object,
});

const searchForm = useForm({
    search: props.filters.search || '',
    approval_status: props.filters.approval_status || 'all',
});

const filterStatus = (statusKey) => {
    searchForm.approval_status = statusKey;
    searchForm.get(route('public-relations.events.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const search = () => {
    searchForm.get(route('public-relations.events.index'), {
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

const approveEvent = (item) => {
    if (confirm(`Setujui dan terbitkan acara "${item.title}"?`)) {
        actionForm.post(route('public-relations.events.approve', item.id), {
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
    actionForm.post(route('public-relations.events.reject', itemToReject.value.id), {
        onSuccess: () => {
            showRejectModal.value = false;
            itemToReject.value = null;
        }
    });
};

// Rejection note view modal
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
    deleteForm.delete(route('public-relations.events.destroy', itemToDelete.value.id), {
        onSuccess: () => closeModal(),
        onError: () => {
            closeModal();
            alert('Gagal menghapus acara.');
        }
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const getStatusBadge = (status) => {
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
    <Head title="Manajemen Acara" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Manajemen Acara (Events)
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Kelola dan verifikasi agenda kegiatan unit sekolah.</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6">

            <!-- STATUS FILTER TABS -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                <button 
                    @click="filterStatus('all')"
                    class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2 shadow-sm"
                    :class="(searchForm.approval_status === 'all' || !searchForm.approval_status) ? 'bg-namira-teal text-white shadow-namira-teal/20' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'"
                >
                    <span>Semua Acara</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="(searchForm.approval_status === 'all' || !searchForm.approval_status) ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-700'">{{ counts?.all || 0 }}</span>
                </button>

                <button 
                    @click="filterStatus('pending')"
                    class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2 shadow-sm"
                    :class="searchForm.approval_status === 'pending' ? 'bg-amber-500 text-white shadow-amber-500/20' : 'bg-white text-amber-700 hover:bg-amber-50 border border-amber-200'"
                >
                    <span>⏳ Menunggu Verifikasi</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="searchForm.approval_status === 'pending' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-800'">{{ counts?.pending || 0 }}</span>
                </button>

                <button 
                    @click="filterStatus('published')"
                    class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2 shadow-sm"
                    :class="searchForm.approval_status === 'published' ? 'bg-emerald-600 text-white shadow-emerald-600/20' : 'bg-white text-emerald-700 hover:bg-emerald-50 border border-emerald-200'"
                >
                    <span>✅ Terbit</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="searchForm.approval_status === 'published' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800'">{{ counts?.published || 0 }}</span>
                </button>

                <button 
                    @click="filterStatus('rejected')"
                    class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2 shadow-sm"
                    :class="searchForm.approval_status === 'rejected' ? 'bg-rose-600 text-white shadow-rose-600/20' : 'bg-white text-rose-700 hover:bg-rose-50 border border-rose-200'"
                >
                    <span>🔴 Perlu Revisi</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="searchForm.approval_status === 'rejected' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-800'">{{ counts?.rejected || 0 }}</span>
                </button>

                <button 
                    @click="filterStatus('draft')"
                    class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2 shadow-sm"
                    :class="searchForm.approval_status === 'draft' ? 'bg-slate-700 text-white shadow-slate-700/20' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'"
                >
                    <span>📝 Draft</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="searchForm.approval_status === 'draft' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'">{{ counts?.draft || 0 }}</span>
                </button>
            </div>

            <!-- Toolbar: Search & Actions -->
            <div class="flex flex-col md:flex-row items-center gap-4">
                <form @submit.prevent="search" class="relative group flex-1 w-full">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 group-focus-within:text-namira-teal transition-colors">
                        <MagnifyingGlassIcon class="w-5 h-5" />
                    </div>
                    <input 
                        v-model="searchForm.search"
                        type="text" 
                        placeholder="Cari Nama Acara..." 
                        class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                    >
                </form>

                <Link :href="route('public-relations.events.create')" class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 active:scale-95 h-[46px] whitespace-nowrap">
                    <PlusIcon class="w-5 h-5" />
                    <span>Tambah Acara</span>
                </Link>
            </div>

            <!-- Data Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/50 border-b border-gray-100 text-xs uppercase text-gray-500 font-extrabold tracking-wider">
                                <th class="p-6">Judul Acara</th>
                                <th class="p-6">Unit</th>
                                <th class="p-6">Tanggal</th>
                                <th class="p-6">Status Verifikasi</th>
                                <th class="p-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="events.data.length === 0">
                                <td colspan="5" class="p-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                            <CalendarIcon class="w-10 h-10 text-gray-400" />
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900">Belum ada acara.</h3>
                                        <p class="text-sm">Silakan tambahkan agenda kegiatan.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="item in events.data" :key="item.id" class="hover:bg-teal-50/30 transition-colors group">
                                <td class="p-6">
                                    <div class="flex items-center gap-4">
                                        <img v-if="item.image_path" :src="'/' + item.image_path" class="w-16 h-12 object-cover rounded-lg border border-gray-200">
                                        <div v-else class="w-16 h-12 bg-gray-100 flex items-center justify-center rounded-lg border border-gray-200">
                                            <CalendarIcon class="w-6 h-6 text-gray-400"/>
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-800 text-sm md:text-base group-hover:text-namira-teal transition-colors line-clamp-2 max-w-xs">{{ item.title }}</div>
                                            <div v-if="item.approval_status === 'rejected' && item.rejection_note" class="text-xs text-rose-600 font-semibold mt-0.5">
                                                Catatan: "{{ item.rejection_note }}"
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-6 text-sm text-gray-600 font-semibold">
                                    {{ item.unit?.name || 'Yayasan' }}
                                </td>
                                <td class="p-6 text-sm text-gray-600">
                                    {{ formatDate(item.start_date) }}
                                </td>
                                <td class="p-6">
                                    <span :class="getStatusBadge(item.approval_status).class" class="px-3 py-1 rounded-xl text-xs font-extrabold uppercase tracking-wide border shadow-sm">
                                        {{ getStatusBadge(item.approval_status).label }}
                                    </span>
                                </td>
                                <td class="p-6 text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        <template v-if="is_approver && item.approval_status === 'pending'">
                                            <button 
                                                @click="approveEvent(item)"
                                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md flex items-center gap-1 transition-all active:scale-95 cursor-pointer"
                                                title="Setujui & Terbitkan"
                                            >
                                                <CheckCircleIcon class="w-4 h-4" />
                                                <span>Setujui</span>
                                            </button>
                                            <button 
                                                @click="openRejectModal(item)"
                                                class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-md flex items-center gap-1 transition-all active:scale-95 cursor-pointer"
                                                title="Tolak / Minta Revisi"
                                            >
                                                <XCircleIcon class="w-4 h-4" />
                                                <span>Tolak</span>
                                            </button>
                                        </template>

                                        <button 
                                            v-if="item.approval_status === 'rejected' && item.rejection_note"
                                            @click="viewNote(item.rejection_note)"
                                            class="p-2 text-rose-600 hover:bg-rose-50 rounded-xl transition-all border border-rose-200 cursor-pointer"
                                            title="Lihat Catatan Revisi"
                                        >
                                            <ChatBubbleBottomCenterTextIcon class="w-5 h-5" />
                                        </button>

                                        <Link :href="route('public-relations.events.edit', item.id)" class="p-2.5 text-gray-400 hover:text-namira-teal hover:bg-teal-50 rounded-xl transition-all duration-200 border border-transparent hover:border-teal-100" title="Edit Acara">
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </Link>
                                        <button 
                                            @click="confirmDelete(item)" 
                                            class="p-2.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all duration-200 border border-transparent hover:border-red-100 cursor-pointer" 
                                            title="Hapus Acara"
                                        >
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
            <div v-if="events.links && events.links.length > 3" class="mt-4 flex justify-center">
                <div class="flex gap-1">
                    <Link v-for="(link, k) in events.links" :key="k" 
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

        <!-- REJECT MODAL -->
        <Teleport to="body">
            <div v-if="showRejectModal" class="fixed inset-0 z-[9999] overflow-y-auto flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showRejectModal = false"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl p-6 max-w-md w-full border border-gray-100 z-10 space-y-4">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <XCircleIcon class="w-6 h-6 text-rose-600" />
                        <span>Tolak & Minta Revisi Acara</span>
                    </h3>
                    <p class="text-xs text-gray-500">Berikan catatan perbaikan acara untuk tim Humas.</p>
                    <textarea 
                        v-model="rejectionNote"
                        rows="4" 
                        placeholder="Contoh: Tanggal pelaksanaan bentrok dengan ujian..." 
                        class="w-full rounded-2xl border-gray-200 text-sm focus:border-rose-500 focus:ring-rose-500 p-3"
                    ></textarea>
                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="showRejectModal = false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl text-xs font-bold">Batal</button>
                        <button @click="submitReject" :disabled="!rejectionNote.trim() || actionForm.processing" class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-bold disabled:opacity-50">
                            Kirim Catatan Revisi
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- VIEW NOTE MODAL -->
        <Teleport to="body">
            <div v-if="showNoteModal" class="fixed inset-0 z-[9999] overflow-y-auto flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showNoteModal = false"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl p-6 max-w-md w-full border border-gray-100 z-10 space-y-4">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <ChatBubbleBottomCenterTextIcon class="w-6 h-6 text-amber-600" />
                        <span>Catatan Revisi Verifikator</span>
                    </h3>
                    <div class="p-4 bg-amber-50 rounded-2xl text-sm text-amber-900 border border-amber-200">
                        {{ selectedNote }}
                    </div>
                    <div class="flex justify-end">
                        <button @click="showNoteModal = false" class="px-4 py-2 bg-gray-900 text-white rounded-xl text-xs font-bold">Tutup</button>
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
                <div v-if="showDeleteConfirm" class="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

                    <div class="flex min-h-full items-center justify-center p-4 text-center">
                        <Transition
                            enter-active-class="transition ease-out duration-300"
                            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                            leave-active-class="transition ease-in duration-200"
                            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        >
                            <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:w-full sm:max-w-md border border-gray-100 p-8">
                                <div class="mx-auto flex h-20 w-20 flex-shrink-0 items-center justify-center rounded-full bg-red-50 mb-6 animate-pulse">
                                    <ExclamationTriangleIcon class="h-10 w-10 text-red-600" />
                                </div>
                                <div class="text-center">
                                    <h3 class="text-xl font-bold leading-6 text-gray-900 mb-2" id="modal-title">Hapus Acara?</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            Anda akan menghapus acara ini secara permanen.
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-8 flex justify-center gap-3">
                                    <button 
                                        type="button" 
                                        class="inline-flex w-full justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:w-auto transition-all"
                                        @click="closeModal"
                                        :disabled="deleteForm.processing"
                                    >
                                        Batal
                                    </button>
                                    <button 
                                        type="button" 
                                        class="inline-flex w-full justify-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-red-500/30 hover:bg-red-700 sm:w-auto transition-all transform hover:scale-105"
                                        @click="deleteItem"
                                        :disabled="deleteForm.processing"
                                    >
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
