<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { 
    MagnifyingGlassIcon, PlusIcon, ArrowPathIcon, CheckCircleIcon,
    XCircleIcon, ClockIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    loans: Object,
    inventories: Array,
    teachers: Array,
    filters: Object,
});

const searchQuery = ref(props.filters.search || '');
const filterStatus = ref(props.filters.status || '');
const showCreateModal = ref(false);
const showReturnModal = ref(false);
const selectedLoan = ref(null);

const createForm = useForm({
    inventory_id: '',
    borrower_id: '',
    quantity: 1,
    due_date: '',
    notes: '',
});

const returnForm = useForm({
    condition: 'baik',
    notes: '',
});

const applyFilters = () => {
    router.get(route('sarpar.loans.index'), {
        search: searchQuery.value || undefined,
        status: filterStatus.value || undefined,
    }, { preserveState: true, replace: true });
};

let searchTimeout = null;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 500);
});

const openCreateModal = () => {
    createForm.reset();
    createForm.due_date = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]; // 7 days default
    showCreateModal.value = true;
};

const submitCreate = () => {
    createForm.post(route('sarpar.loans.store'), { onSuccess: () => showCreateModal.value = false });
};

const openReturnModal = (loan) => {
    selectedLoan.value = loan;
    returnForm.reset();
    returnForm.condition = loan.inventory?.condition || 'baik';
    showReturnModal.value = true;
};

const submitReturn = () => {
    returnForm.post(route('sarpar.loans.return', selectedLoan.value.id), {
        onSuccess: () => { showReturnModal.value = false; selectedLoan.value = null; },
    });
};

const markLost = (loan) => {
    if (confirm('Tandai barang sebagai hilang? Tindakan ini tidak dapat dibatalkan.')) {
        router.post(route('sarpar.loans.lost', loan.id));
    }
};

const getStatusBadge = (status) => {
    const badges = { 'borrowed': 'bg-blue-100 text-blue-700', 'returned': 'bg-green-100 text-green-700', 'overdue': 'bg-red-100 text-red-700', 'lost': 'bg-gray-100 text-gray-500' };
    return badges[status] || 'bg-gray-100';
};

const getStatusLabel = (status) => {
    const labels = { 'borrowed': 'Dipinjam', 'returned': 'Dikembalikan', 'overdue': 'Terlambat', 'lost': 'Hilang' };
    return labels[status] || status;
};
</script>

<template>
    <Head title="Peminjaman Barang" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                    Peminjaman Barang
                </h2>
                <p class="text-sm text-gray-500 mt-1">Kelola peminjaman inventaris oleh guru</p>
            </div>
        </template>

        <div class="py-4 md:py-6 max-w-7xl mx-auto pb-20 space-y-5 md:space-y-6">

            <!-- 📱 MOBILE PWA VIEW (block md:hidden) -->
            <div class="block md:hidden -mx-4 -mt-4 space-y-4">
                <!-- Header Card Gradient -->
                <div class="bg-gradient-to-br from-[#009688] to-[#0f172a] px-4 pt-5 pb-6 text-white">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-[10px] font-extrabold tracking-widest uppercase text-teal-300">Modul Sarpar</p>
                            <h1 class="text-xl font-black leading-tight">Peminjaman Barang</h1>
                        </div>
                        <button
                            @click="openCreateModal"
                            class="px-3.5 py-2 bg-teal-500 hover:bg-teal-600 text-white font-extrabold text-xs rounded-xl shadow-lg flex items-center gap-1.5 active:scale-95 transition"
                        >
                            <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                            <span>Catat Pinjam</span>
                        </button>
                    </div>

                    <!-- Quick Stats Grid (4 Column Grid) -->
                    <div class="grid grid-cols-4 gap-1.5 text-center mt-3">
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-2 py-2">
                            <p class="text-lg font-black text-white leading-none">{{ loans.total || loans.data.length }}</p>
                            <p class="text-[8px] text-teal-200 font-bold mt-1 uppercase">Total</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-2 py-2">
                            <p class="text-lg font-black text-blue-300 leading-none">
                                {{ loans.data.filter(l => l.status === 'borrowed').length }}
                            </p>
                            <p class="text-[8px] text-blue-200 font-bold mt-1 uppercase">Dipinjam</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-2 py-2">
                            <p class="text-lg font-black text-red-300 leading-none">
                                {{ loans.data.filter(l => l.status === 'overdue').length }}
                            </p>
                            <p class="text-[8px] text-red-200 font-bold mt-1 uppercase">Terlambat</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-2 py-2">
                            <p class="text-lg font-black text-emerald-300 leading-none">
                                {{ loans.data.filter(l => l.status === 'returned').length }}
                            </p>
                            <p class="text-[8px] text-emerald-200 font-bold mt-1 uppercase">Dikembalikan</p>
                        </div>
                    </div>
                </div>

                <!-- Search & Filters Toolbar -->
                <div class="px-4 space-y-2">
                    <div class="relative">
                        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-3.5 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari barang atau peminjam..."
                            class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:ring-teal-500 focus:border-teal-500 shadow-sm"
                        />
                    </div>
                    <select v-model="filterStatus" @change="applyFilters" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 shadow-sm">
                        <option value="">Semua Status Peminjaman</option>
                        <option value="borrowed">Status: Dipinjam</option>
                        <option value="returned">Status: Dikembalikan</option>
                        <option value="overdue">Status: Terlambat</option>
                    </select>
                </div>

                <!-- Loans Mobile Touch Cards List -->
                <div class="px-4 space-y-3">
                    <div v-if="loans.data.length === 0" class="bg-white rounded-2xl p-8 text-center border border-slate-100 shadow-sm">
                        <ClockIcon class="w-10 h-10 mx-auto text-teal-300 mb-2" />
                        <p class="font-extrabold text-sm text-slate-800">Belum ada data peminjaman</p>
                        <p class="text-xs text-slate-400 mt-1">Data peminjaman barang tidak ditemukan.</p>
                    </div>

                    <div
                        v-for="loan in loans.data"
                        :key="loan.id"
                        class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm space-y-3 relative overflow-hidden"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <Link :href="route('sarpar.inventories.show', loan.inventory?.id)" class="font-extrabold text-slate-900 text-sm leading-tight hover:text-teal-700 transition">
                                    {{ loan.inventory?.name || 'Barang' }}
                                </Link>
                                <p class="font-mono text-[10px] font-bold text-teal-700 mt-0.5">Kode: {{ loan.inventory?.code || '-' }}</p>
                            </div>
                            <span :class="['px-2.5 py-1 text-[10px] font-black uppercase rounded-xl border shrink-0', getStatusBadge(loan.status)]">
                                {{ getStatusLabel(loan.status) }}
                            </span>
                        </div>

                        <!-- Borrower Info & Dates Grid -->
                        <div class="grid grid-cols-2 gap-2 bg-slate-50 p-2.5 rounded-xl text-xs border border-slate-100">
                            <div>
                                <span class="text-[9px] font-extrabold text-slate-400 uppercase block">Peminjam (Guru)</span>
                                <span class="font-bold text-slate-800 text-[11px] truncate block">{{ loan.borrower?.name || '-' }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-extrabold text-slate-400 uppercase block">Jatuh Tempo</span>
                                <span class="font-bold text-slate-800 text-[11px] block">{{ loan.due_date ? new Date(loan.due_date).toLocaleDateString('id-ID') : '-' }}</span>
                            </div>
                        </div>

                        <!-- Touch Actions Footer -->
                        <div v-if="loan.status === 'borrowed' || loan.status === 'overdue'" class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                            <button @click="openReturnModal(loan)" class="px-3 py-1.5 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition flex items-center gap-1">
                                <ArrowPathIcon class="w-3.5 h-3.5" />
                                <span>Kembalikan</span>
                            </button>
                            <button @click="markLost(loan)" class="px-3 py-1.5 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition flex items-center gap-1">
                                <XCircleIcon class="w-3.5 h-3.5 text-slate-500" />
                                <span>Hilang</span>
                            </button>
                        </div>
                    </div>

                    <!-- Mobile Pagination -->
                    <div v-if="loans.last_page > 1" class="pt-2 flex justify-center gap-1">
                        <Link v-for="link in loans.links" :key="link.label" :href="link.url || '#'" :class="['px-3 py-1.5 rounded-xl text-xs font-bold', link.active ? 'bg-teal-600 text-white' : 'bg-white text-slate-600 border border-slate-200']" v-html="link.label" />
                    </div>
                </div>
            </div>
            <!-- END MOBILE VIEW -->

            <!-- 🖥️ DESKTOP VIEW (hidden md:block) -->
            <div class="hidden md:block space-y-6">
                <!-- Toolbar -->
                <div class="flex flex-col md:flex-row items-center gap-4">
                    <div class="relative flex-1 w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                            <MagnifyingGlassIcon class="w-5 h-5" />
                        </div>
                        <input v-model="searchQuery" type="text" placeholder="Cari nama barang atau peminjam..." class="pl-10 pr-4 py-2.5 w-full bg-white/50 border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 shadow-sm h-[46px]" />
                    </div>
                    
                    <select v-model="filterStatus" @change="applyFilters" class="px-4 py-2.5 bg-white border border-gray-200 rounded-2xl text-sm focus:ring-namira-teal focus:border-namira-teal h-[46px]">
                        <option value="">Semua Status</option>
                        <option value="borrowed">Dipinjam</option>
                        <option value="returned">Dikembalikan</option>
                        <option value="overdue">Terlambat</option>
                    </select>

                    <button @click="openCreateModal" class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all flex items-center gap-2 active:scale-95 h-[46px]">
                        <PlusIcon class="w-5 h-5" /><span>Catat Peminjaman</span>
                    </button>
                </div>

                <!-- Table -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-white/50 text-xs uppercase text-gray-500 font-extrabold tracking-wider border-b border-gray-100">
                                    <th class="p-4">Barang</th>
                                    <th class="p-4">Peminjam</th>
                                    <th class="p-4">Tgl Pinjam</th>
                                    <th class="p-4">Jatuh Tempo</th>
                                    <th class="p-4">Status</th>
                                    <th class="p-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="loans.data.length === 0">
                                    <td colspan="6" class="p-12 text-center text-gray-400">
                                        <ClockIcon class="w-12 h-12 mx-auto mb-2 opacity-50" />
                                        <p class="font-bold">Belum ada data peminjaman</p>
                                    </td>
                                </tr>
                                <tr v-for="loan in loans.data" :key="loan.id" class="hover:bg-blue-50/30 transition-colors">
                                    <td class="p-4">
                                        <Link :href="route('sarpar.inventories.show', loan.inventory?.id)" class="font-bold text-gray-800 hover:text-namira-teal">{{ loan.inventory?.name }}</Link>
                                        <div class="text-xs text-gray-400">{{ loan.inventory?.code }}</div>
                                    </td>
                                    <td class="p-4 text-sm text-gray-600">{{ loan.borrower?.name }}</td>
                                    <td class="p-4 text-sm text-gray-600">{{ new Date(loan.loan_date).toLocaleDateString('id-ID') }}</td>
                                    <td class="p-4 text-sm text-gray-600">{{ new Date(loan.due_date).toLocaleDateString('id-ID') }}</td>
                                    <td class="p-4">
                                        <span :class="['px-2 py-1 text-xs font-bold rounded-lg', getStatusBadge(loan.status)]">{{ getStatusLabel(loan.status) }}</span>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex justify-end gap-2">
                                            <button v-if="loan.status === 'borrowed' || loan.status === 'overdue'" @click="openReturnModal(loan)" class="px-3 py-1.5 bg-green-500 text-white text-xs font-bold rounded-lg hover:bg-green-600 flex items-center gap-1">
                                                <ArrowPathIcon class="w-3 h-3" />
                                                Kembalikan
                                            </button>
                                            <button v-if="loan.status === 'borrowed' || loan.status === 'overdue'" @click="markLost(loan)" class="px-3 py-1.5 bg-gray-100 text-gray-600 text-xs font-bold rounded-lg hover:bg-gray-200 flex items-center gap-1">
                                                <XCircleIcon class="w-3 h-3" />
                                                Hilang
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="loans.last_page > 1" class="p-4 border-t border-gray-100 flex justify-center gap-2">
                        <Link v-for="link in loans.links" :key="link.label" :href="link.url || '#'" :class="['px-3 py-1.5 rounded-xl text-sm', link.active ? 'bg-namira-teal text-white' : 'text-gray-600 hover:bg-gray-100']" v-html="link.label" />
                    </div>
                </div>
            </div>
            <!-- END DESKTOP VIEW -->
        </div>

        <!-- Create Modal -->
        <Teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showCreateModal = false"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-8 border border-gray-100">
                        <h3 class="text-xl font-bold text-gray-900 mb-6">Catat Peminjaman Baru</h3>
                        <form @submit.prevent="submitCreate" class="space-y-5">
                            <div>
                                <InputLabel value="Barang *" class="text-sm font-bold text-gray-700" />
                                <select v-model="createForm.inventory_id" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" required>
                                    <option value="">-- Pilih Barang --</option>
                                    <option v-for="item in inventories" :key="item.id" :value="item.id">{{ item.name }} ({{ item.code }})</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Peminjam (Guru) *" class="text-sm font-bold text-gray-700" />
                                <select v-model="createForm.borrower_id" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" required>
                                    <option value="">-- Pilih Guru --</option>
                                    <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.user_id">{{ teacher.full_name }}</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel value="Jumlah *" class="text-sm font-bold text-gray-700" />
                                    <input v-model="createForm.quantity" type="number" min="1" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" required />
                                </div>
                                <div>
                                    <InputLabel value="Jatuh Tempo *" class="text-sm font-bold text-gray-700" />
                                    <input v-model="createForm.due_date" type="date" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" required />
                                </div>
                            </div>
                            <div>
                                <InputLabel value="Catatan" class="text-sm font-bold text-gray-700" />
                                <textarea v-model="createForm.notes" rows="2" class="w-full mt-1.5 px-4 py-3 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" placeholder="Keterangan (opsional)"></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-50">
                                <button type="button" @click="showCreateModal = false" class="px-5 py-2.5 text-gray-500 font-bold hover:bg-gray-50 rounded-xl">Batal</button>
                                <PrimaryButton :disabled="createForm.processing" class="rounded-xl px-6 shadow-lg shadow-namira-teal/30">Simpan</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Return Modal -->
        <Teleport to="body">
            <div v-if="showReturnModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showReturnModal = false"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-8 border border-gray-100">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Kembalikan Barang</h3>
                        <p class="text-sm text-gray-500 mb-6">{{ selectedLoan?.inventory?.name }}</p>
                        <form @submit.prevent="submitReturn" class="space-y-5">
                            <div>
                                <InputLabel value="Kondisi Saat Dikembalikan" class="text-sm font-bold text-gray-700" />
                                <select v-model="returnForm.condition" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50">
                                    <option value="baik">Baik</option>
                                    <option value="rusak_ringan">Rusak Ringan</option>
                                    <option value="rusak_berat">Rusak Berat</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Catatan" class="text-sm font-bold text-gray-700" />
                                <textarea v-model="returnForm.notes" rows="2" class="w-full mt-1.5 px-4 py-3 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" placeholder="Keterangan (opsional)"></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-50">
                                <button type="button" @click="showReturnModal = false" class="px-5 py-2.5 text-gray-500 font-bold hover:bg-gray-50 rounded-xl">Batal</button>
                                <PrimaryButton :disabled="returnForm.processing" class="rounded-xl px-6 bg-green-500 hover:bg-green-600 shadow-lg shadow-green-500/30">Kembalikan</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
