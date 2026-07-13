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

        <div class="py-6 max-w-7xl mx-auto pb-20 space-y-6">
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
