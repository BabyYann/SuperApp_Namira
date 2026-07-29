<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    MagnifyingGlassIcon, PlusIcon, NewspaperIcon, PencilSquareIcon, TrashIcon, 
    ExclamationTriangleIcon, CheckCircleIcon, XCircleIcon, ChatBubbleBottomCenterTextIcon,
    FunnelIcon, ClockIcon, DocumentTextIcon, InformationCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    news: Object,
    counts: Object,
    is_approver: Boolean,
    filters: Object,
});

const searchForm = useForm({
    search: props.filters.search || '',
    status: props.filters.status || 'all',
});

const filterStatus = (statusKey) => {
    searchForm.status = statusKey;
    searchForm.get(route('public-relations.news.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const search = () => {
    searchForm.get(route('public-relations.news.index'), {
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

const approveNews = (item) => {
    if (confirm(`Setujui dan terbitkan berita "${item.title}"?`)) {
        actionForm.post(route('public-relations.news.approve', item.id), {
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
    actionForm.post(route('public-relations.news.reject', itemToReject.value.id), {
        onSuccess: () => {
            showRejectModal.value = false;
            itemToReject.value = null;
        }
    });
};

// Rejection note view modal for Humas
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
    deleteForm.delete(route('public-relations.news.destroy', itemToDelete.value.id), {
        onSuccess: () => closeModal(),
        onError: () => {
            closeModal();
            alert('Gagal menghapus berita.');
        }
    });
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
    <Head title="Manajemen Berita" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Manajemen Berita
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Kelola dan verifikasi liputan berita unit sekolah Anda.</p>
                </div>
            </div>
        </template>

        <div class="py-4 md:py-6 max-w-7xl mx-auto space-y-5 md:space-y-6">

            <!-- STATUS FILTER TABS -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                <button 
                    @click="filterStatus('all')"
                    class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2 shadow-sm"
                    :class="(searchForm.status === 'all' || !searchForm.status) ? 'bg-namira-teal text-white shadow-namira-teal/20' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'"
                >
                    <span>Semua Berita</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="(searchForm.status === 'all' || !searchForm.status) ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-700'">{{ counts?.all || 0 }}</span>
                </button>

                <button 
                    @click="filterStatus('pending')"
                    class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2 shadow-sm"
                    :class="searchForm.status === 'pending' ? 'bg-amber-500 text-white shadow-amber-500/20' : 'bg-white text-amber-700 hover:bg-amber-50 border border-amber-200'"
                >
                    <ClockIcon class="w-4 h-4" />
                    <span>Menunggu Verifikasi</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="searchForm.status === 'pending' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-800'">{{ counts?.pending || 0 }}</span>
                </button>

                <button 
                    @click="filterStatus('published')"
                    class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2 shadow-sm"
                    :class="searchForm.status === 'published' ? 'bg-emerald-600 text-white shadow-emerald-600/20' : 'bg-white text-emerald-700 hover:bg-emerald-50 border border-emerald-200'"
                >
                    <CheckCircleIcon class="w-4 h-4" />
                    <span>Terbit</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="searchForm.status === 'published' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800'">{{ counts?.published || 0 }}</span>
                </button>

                <button 
                    @click="filterStatus('rejected')"
                    class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2 shadow-sm"
                    :class="searchForm.status === 'rejected' ? 'bg-rose-600 text-white shadow-rose-600/20' : 'bg-white text-rose-700 hover:bg-rose-50 border border-rose-200'"
                >
                    <XCircleIcon class="w-4 h-4" />
                    <span>Perlu Revisi</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="searchForm.status === 'rejected' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-800'">{{ counts?.rejected || 0 }}</span>
                </button>

                <button 
                    @click="filterStatus('draft')"
                    class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2 shadow-sm"
                    :class="searchForm.status === 'draft' ? 'bg-slate-700 text-white shadow-slate-700/20' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'"
                >
                    <DocumentTextIcon class="w-4 h-4" />
                    <span>Draft</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="searchForm.status === 'draft' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'">{{ counts?.draft || 0 }}</span>
                </button>
            </div>
            
            <!-- 1A. DESKTOP TOOLBAR -->
            <div class="hidden md:flex flex-row items-center gap-4">
                <form @submit.prevent="search" class="relative group flex-1 w-full">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 group-focus-within:text-namira-teal transition-colors">
                        <MagnifyingGlassIcon class="w-5 h-5" />
                    </div>
                    <input 
                        v-model="searchForm.search"
                        type="text" 
                        placeholder="Cari Judul Berita..." 
                        class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                    >
                </form>

                <Link :href="route('public-relations.news.create')" class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 active:scale-95 h-[46px] whitespace-nowrap">
                    <PlusIcon class="w-5 h-5" />
                    <span>Tambah Berita</span>
                </Link>
            </div>

            <!-- 1B. MOBILE TOOLBAR -->
            <div class="block md:hidden bg-[#064e3b] text-white p-4 rounded-3xl border border-emerald-800/80 shadow-xl space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black tracking-widest text-teal-400 uppercase">Humas & Publikasi</span>
                        <h3 class="text-base font-extrabold text-white mt-0.5">Manajemen Berita</h3>
                    </div>
                    <Link :href="route('public-relations.news.create')" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl text-xs font-extrabold flex items-center gap-1.5 shadow-md">
                        <PlusIcon class="w-4 h-4" />
                        <span>Tambah</span>
                    </Link>
                </div>
                <form @submit.prevent="search" class="relative w-full">
                    <input 
                        v-model="searchForm.search"
                        type="text" 
                        placeholder="Cari berita..." 
                        class="w-full bg-slate-800 border border-slate-700 text-white rounded-2xl text-xs font-bold p-3 focus:ring-teal-500 focus:border-teal-500"
                    >
                </form>
            </div>

            <!-- 2A. DESKTOP DATA TABLE -->
            <div class="hidden md:block bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/50 border-b border-gray-100 text-xs uppercase text-gray-500 font-extrabold tracking-wider">
                                <th class="p-6">Judul Berita</th>
                                <th class="p-6">Unit</th>
                                <th class="p-6">Status Verifikasi</th>
                                <th class="p-6">Penulis</th>
                                <th class="p-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="news.data.length === 0">
                                <td colspan="5" class="p-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                            <NewspaperIcon class="w-10 h-10 text-gray-400" />
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900">Belum ada berita.</h3>
                                        <p class="text-sm">Tidak ada berita pada kategori atau pencarian ini.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="item in news.data" :key="'desk-'+item.id" class="hover:bg-teal-50/30 transition-colors group">
                                <td class="p-6">
                                    <div class="flex items-center gap-4">
                                        <img v-if="item.image_path" :src="'/' + item.image_path" class="w-16 h-12 object-cover rounded-lg border border-gray-200">
                                        <div v-else class="w-16 h-12 bg-gray-100 flex items-center justify-center rounded-lg border border-gray-200">
                                            <NewspaperIcon class="w-6 h-6 text-gray-400"/>
                                        </div>
                                        <div class="space-y-1">
                                            <div class="font-bold text-gray-800 text-sm md:text-base group-hover:text-namira-teal transition-colors line-clamp-2 max-w-xs">{{ item.title }}</div>
                                            <div v-if="item.status === 'rejected' && item.rejection_note" class="text-xs text-rose-600 font-semibold flex items-center gap-1">
                                                <span>Catatan: "{{ item.rejection_note }}"</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-6 text-sm text-gray-600 font-semibold">
                                    {{ item.unit?.name || 'Yayasan' }}
                                </td>
                                <td class="p-6">
                                    <span :class="getStatusBadge(item.status).class" class="px-3 py-1.5 rounded-xl text-xs font-extrabold uppercase tracking-wide border shadow-sm inline-flex items-center gap-1.5">
                                        {{ getStatusBadge(item.status).label }}
                                    </span>
                                </td>
                                <td class="p-6 text-sm text-gray-600">
                                    <div class="font-semibold">{{ item.author?.name || '-' }}</div>
                                    <div v-if="item.approver" class="text-[11px] text-emerald-700">Verifikator: {{ item.approver.name }}</div>
                                </td>
                                <td class="p-6 text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        <!-- Verifier Approve / Reject Buttons -->
                                        <template v-if="is_approver && item.status === 'pending'">
                                            <button 
                                                @click="approveNews(item)"
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

                                        <!-- View Rejection Note Button -->
                                        <button 
                                            v-if="item.status === 'rejected' && item.rejection_note"
                                            @click="viewNote(item.rejection_note)"
                                            class="p-2 text-rose-600 hover:bg-rose-50 rounded-xl transition-all border border-rose-200 cursor-pointer"
                                            title="Lihat Catatan Revisi"
                                        >
                                            <ChatBubbleBottomCenterTextIcon class="w-5 h-5" />
                                        </button>

                                        <Link :href="route('public-relations.news.edit', item.id)" class="p-2.5 text-gray-400 hover:text-namira-teal hover:bg-teal-50 rounded-xl transition-all duration-200 border border-transparent hover:border-teal-100" title="Edit Berita">
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </Link>
                                        <button 
                                            @click="confirmDelete(item)" 
                                            class="p-2.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all duration-200 border border-transparent hover:border-red-100 cursor-pointer" 
                                            title="Hapus Berita"
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

            <!-- 2B. MOBILE NATIVE CARDS -->
            <div class="grid md:hidden grid-cols-1 gap-3.5">
                <div v-if="news.data.length === 0" class="text-center py-12 bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                    <NewspaperIcon class="w-12 h-12 text-slate-400 mx-auto mb-3" />
                    <h3 class="text-base font-bold text-slate-900 mb-1">Belum ada berita</h3>
                    <p class="text-xs text-slate-500">Tidak ada berita pada filter ini.</p>
                </div>

                <div 
                    v-for="item in news.data" 
                    :key="'mob-'+item.id"
                    class="bg-white rounded-3xl p-4 border border-slate-200 shadow-sm flex flex-col space-y-3"
                >
                    <div class="flex items-start gap-3">
                        <img v-if="item.image_path" :src="'/' + item.image_path" class="w-20 h-16 object-cover rounded-2xl border border-slate-200 flex-shrink-0">
                        <div v-else class="w-20 h-16 bg-slate-100 flex items-center justify-center rounded-2xl border border-slate-200 flex-shrink-0">
                            <NewspaperIcon class="w-7 h-7 text-slate-400"/>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1 mb-1">
                                <span class="text-[10px] font-black text-teal-700 bg-teal-50 px-2 py-0.5 rounded-lg border border-teal-100">
                                    {{ item.unit?.name || 'Yayasan' }}
                                </span>
                                <span :class="getStatusBadge(item.status).class" class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-wider border">
                                    {{ getStatusBadge(item.status).label }}
                                </span>
                            </div>
                            <h4 class="font-extrabold text-sm text-slate-900 leading-snug line-clamp-2">{{ item.title }}</h4>
                        </div>
                    </div>

                    <div v-if="item.status === 'rejected' && item.rejection_note" class="p-2.5 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-800 font-medium">
                        <strong>Catatan Revisi:</strong> {{ item.rejection_note }}
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                        <span class="text-[11px] text-slate-400 font-bold">Oleh: {{ item.author?.name || 'Admin' }}</span>
                        
                        <div class="flex items-center gap-1.5">
                            <template v-if="is_approver && item.status === 'pending'">
                                <button @click="approveNews(item)" class="px-2.5 py-1.5 bg-emerald-600 text-white font-extrabold text-xs rounded-xl border border-emerald-700">
                                    Setujui
                                </button>
                                <button @click="openRejectModal(item)" class="px-2.5 py-1.5 bg-rose-600 text-white font-extrabold text-xs rounded-xl border border-rose-700">
                                    Tolak
                                </button>
                            </template>
                            <Link :href="route('public-relations.news.edit', item.id)" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-extrabold text-xs rounded-xl border border-slate-200">
                                Edit
                            </Link>
                            <button @click="confirmDelete(item)" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-extrabold text-xs rounded-xl border border-rose-200">
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pagination -->
            <div v-if="news.links && news.links.length > 3" class="mt-4 flex justify-center">
                <div class="flex gap-1 flex-wrap justify-center">
                    <Link v-for="(link, k) in news.links" :key="k" 
                        :href="link.url || '#'" 
                        v-html="link.label"
                        class="px-3.5 py-1.5 rounded-xl border text-xs font-bold"
                        :class="[
                            link.active ? 'bg-namira-teal text-white border-namira-teal' : 'bg-white text-gray-500 hover:bg-gray-50',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>
        </div>

        <!-- REJECT REASON MODAL -->
        <Teleport to="body">
            <div v-if="showRejectModal" class="fixed inset-0 z-[9999] overflow-y-auto flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showRejectModal = false"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl p-6 max-w-md w-full border border-gray-100 z-10 space-y-4">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <XCircleIcon class="w-6 h-6 text-rose-600" />
                        <span>Tolak & Minta Revisi Berita</span>
                    </h3>
                    <p class="text-xs text-gray-500">Berikan catatan revisi agar tim Humas dapat memperbaiki berita ini.</p>
                    <textarea 
                        v-model="rejectionNote"
                        rows="4" 
                        placeholder="Contoh: Foto kurang sesuai, mohon ganti foto kegiatan resmi..." 
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
                        <span>Catatan Revisi dari Verifikator</span>
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

        <!-- DELETE MODAL -->
        <Teleport to="body">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-[9999] overflow-y-auto flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl p-8 max-w-md w-full border border-gray-100 z-10 text-center space-y-6">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-50 text-red-600">
                        <ExclamationTriangleIcon class="w-8 h-8" />
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Hapus Berita?</h3>
                        <p class="text-sm text-gray-500 mt-1">Berita ini akan dihapus secara permanen.</p>
                    </div>
                    <div class="flex justify-center gap-3">
                        <button @click="closeModal" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-xl text-sm font-bold">Batal</button>
                        <button @click="deleteItem" :disabled="deleteForm.processing" class="px-5 py-2.5 bg-red-600 text-white rounded-xl text-sm font-bold">Hapus</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
