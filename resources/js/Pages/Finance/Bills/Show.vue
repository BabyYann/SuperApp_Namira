<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    ArrowLeftIcon, PencilSquareIcon, PlusIcon, BanknotesIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    bill: Object,
    transactions: Array
});

const formatCurrency = (val) => {
    if (val === undefined || val === null) return 'Rp -';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(val));
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const statusClass = (status) => {
    switch(status) {
        case 'paid': return 'bg-green-100 text-green-700 border-green-200';
        case 'partial': return 'bg-yellow-100 text-yellow-700 border-yellow-200';
        case 'cancelled': return 'bg-gray-100 text-gray-500 border-gray-200';
        default: return 'bg-red-50 text-red-600 border-red-100';
    }
};

const statusLabel = (status) => {
    switch(status) {
        case 'paid': return 'LUNAS';
        case 'partial': return 'CICIL';
        case 'cancelled': return 'BATAL';
        default: return 'BELUM LUNAS';
    }
};

// --- Edit Logic ---
const showEditModal = ref(false);
const editForm = useForm({
    final_amount: 0,
    description: '',
});

const openEditModal = () => {
    editForm.final_amount = props.bill.final_amount; // Use FINAL amount as the editable value
    editForm.description = props.bill.description;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editForm.reset();
    editForm.clearErrors();
};

const updateBill = () => {
    editForm.put(route('yayasan.finance.bills.update', props.bill.id), {
        onSuccess: () => closeEditModal(),
    });
};
</script>

<template>
    <Head title="Detail Tagihan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                        <Link :href="route('yayasan.finance.bills.index')" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <ArrowLeftIcon class="w-6 h-6" />
                        </Link>
                        Detail Tagihan
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-12 max-w-5xl mx-auto space-y-6">
            <!-- Bill Info -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-start bg-gray-50/50">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">{{ bill.finance_type?.name || 'Tagihan Lainnya' }}</h3>
                        <p class="text-sm text-gray-500">{{ bill.description }}</p>
                    </div>
                    
                    <div class="flex items-center gap-3">
                         <!-- Edit Button (Contextual) -->
                        <button 
                            v-if="bill.status !== 'paid'"
                            @click="openEditModal"
                            class="px-3 py-1.5 bg-white border border-gray-200 text-gray-600 font-bold text-xs rounded-lg shadow-sm hover:bg-gray-50 hover:text-namira-teal transition-all flex items-center gap-1.5"
                            title="Edit Tagihan"
                        >
                            <PencilSquareIcon class="w-3.5 h-3.5" />
                            <span>Edit</span>
                        </button>

                        <span class="px-3 py-1.5 text-xs font-bold uppercase rounded-lg border shadow-sm" :class="statusClass(bill.status)">
                            {{ statusLabel(bill.status) }}
                        </span>
                    </div>
                </div>
                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Nama Siswa</dt>
                            <dd class="text-base font-bold text-gray-900">{{ bill.student?.full_name || '-' }}</dd>
                            <dd class="text-sm text-gray-500">NIS: {{ bill.student?.nis || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Periode Tagihan</dt>
                            <dd class="text-base font-medium text-gray-700">{{ formatDate(bill.billing_date) }}</dd>
                        </div>
                        <div>
                             <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Jatuh Tempo</dt>
                             <dd class="text-base font-medium text-red-600">{{ formatDate(bill.due_date) }}</dd>
                        </div>
                    </dl>
                    
                    <div class="bg-gray-50 rounded-2xl p-6 space-y-3">
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Jumlah Asli</span>
                            <span class="font-mono">{{ formatCurrency(bill.original_amount) }}</span>
                        </div>
                        <div v-if="bill.discount_amount > 0" class="flex justify-between text-sm text-green-600">
                            <span>Diskon</span>
                            <span class="font-mono">- {{ formatCurrency(bill.discount_amount) }}</span>
                        </div>
                        <div class="pt-3 border-t border-gray-200 flex justify-between text-base font-bold text-gray-900">
                            <span>Total Tagihan</span>
                            <span class="font-mono">{{ formatCurrency(bill.final_amount) }}</span>
                        </div>
                         <div class="flex justify-between text-sm text-green-600">
                            <span>Sudah Dibayar</span>
                            <span class="font-mono">{{ formatCurrency(bill.paid_amount) }}</span>
                        </div>
                        <div class="pt-3 border-t border-gray-200 flex justify-between text-lg font-extrabold text-namira-teal">
                            <span>Sisa Pembayaran</span>
                            <span class="font-mono">{{ formatCurrency(bill.final_amount - bill.paid_amount) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800">Riwayat Pembayaran</h3>
                </div>
                
                <div v-if="transactions && transactions.length > 0" class="divide-y divide-gray-50">
                    <div v-for="trx in transactions" :key="trx.id" class="p-6 flex justify-between items-center hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-4">
                             <div class="w-10 h-10 rounded-full bg-teal-50 flex items-center justify-center text-teal-600">
                                    <PlusIcon class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="font-bold text-gray-900">{{ trx.transaction_code }}</p>
                                <p class="text-xs text-gray-500">{{ formatDate(trx.transaction_date) }} • {{ trx.payment_method }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                             <p class="font-bold text-teal-600 font-mono">+ {{ formatCurrency(trx.amount) }}</p>
                             <span class="text-[10px] uppercase font-bold text-gray-400">Sukses</span>
                        </div>
                    </div>
                </div>
                <div v-else class="p-12 text-center text-gray-400">
                    <div class="flex justify-center mb-4">
                        <BanknotesIcon class="w-12 h-12 opacity-30" />
                    </div>
                    <p>Belum ada pembayaran untuk tagihan ini.</p>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <Teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeEditModal"></div>
                
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-8 border border-gray-100 transform transition-all scale-100">
                        <h3 class="text-xl font-bold text-gray-900 mb-6">Edit Tagihan</h3>
                        
                        <form @submit.prevent="updateBill" class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Nominal Tagihan</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 font-bold">Rp</span>
                                    </div>
                                    <input 
                                        v-model="editForm.final_amount" 
                                        type="number" 
                                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border-gray-300 focus:border-namira-teal focus:ring-namira-teal"
                                        placeholder="Contoh: 150000"
                                    >
                                </div>
                                <div class="text-xs text-red-500 mt-1" v-if="editForm.errors.final_amount">{{ editForm.errors.final_amount }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Catatan / Keterangan</label>
                                <textarea 
                                    v-model="editForm.description" 
                                    class="w-full rounded-xl border-gray-300 focus:border-namira-teal focus:ring-namira-teal"
                                    rows="3"
                                    placeholder="Contoh: Koreksi nominal, tambahan biaya..."
                                ></textarea>
                                 <div class="text-xs text-red-500 mt-1" v-if="editForm.errors.description">{{ editForm.errors.description }}</div>
                            </div>

                            <div class="flex justify-end gap-3 mt-6">
                                <button type="button" @click="closeEditModal" class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-600 font-bold hover:bg-gray-50 transition-colors">Batal</button>
                                <button type="submit" :disabled="editForm.processing" class="px-5 py-2.5 rounded-xl bg-namira-teal text-white font-bold hover:bg-teal-700 shadow-lg shadow-namira-teal/30 transition-all flex items-center gap-2">
                                    <span v-if="editForm.processing" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
