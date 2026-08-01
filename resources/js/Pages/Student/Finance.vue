<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import Modal from '@/Components/Modal.vue';
import { 
    BanknotesIcon, 
    CreditCardIcon, 
    DocumentTextIcon, 
    ArrowUpTrayIcon,
    ExclamationTriangleIcon,
    CheckBadgeIcon,
    ClockIcon,
    XMarkIcon,
    ArrowDownTrayIcon,
    EyeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    student: Object,
    bills: Array,
    stats: Object,
    finance_accounts: Array,
});

const selectedBill = ref(null);
const showUploadModal = ref(false);
const showProofModal = ref(false);
const previewProofUrl = ref(null);

const form = useForm({
    proof: null,
    notes: '',
});

const openUploadModal = (bill) => {
    selectedBill.value = bill;
    form.reset();
    showUploadModal.value = true;
};

const closeUploadModal = () => {
    showUploadModal.value = false;
    selectedBill.value = null;
    form.reset();
};

const openProofModal = (proofUrl) => {
    previewProofUrl.value = proofUrl;
    showProofModal.value = true;
};

const submitUploadProof = () => {
    if (!selectedBill.value) return;
    form.post(route('student.finance.upload-proof', selectedBill.value.id), {
        onSuccess: () => {
            closeUploadModal();
        },
    });
};

const getStatusColor = (status) => {
    switch(status) {
        case 'paid': return 'text-green-600 bg-green-50 border-green-100';
        case 'unpaid': return 'text-red-600 bg-red-50 border-red-100';
        case 'pending': return 'text-amber-700 bg-amber-50 border-amber-200';
        default: return 'text-gray-600 bg-gray-50 border-gray-100';
    }
};

const getStatusLabel = (status) => {
    switch(status) {
        case 'paid': return 'Lunas';
        case 'unpaid': return 'Belum Lunas';
        case 'pending': return 'Menunggu Konfirmasi Admin';
        default: return status;
    }
};
</script>

<template>
    <StudentLayout title="Keuangan">
        <div class="space-y-8">
            
            <!-- Header & Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Header Card -->
                <div class="lg:col-span-2 bg-gradient-to-br from-[#064e3b] to-emerald-800 rounded-3xl p-8 text-white relative overflow-hidden shadow-lg">
                    <!-- Decor -->
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                    
                    <div class="relative z-10">
                        <h2 class="text-3xl font-bold mb-1">Keuangan</h2>
                        <p class="text-emerald-100 mb-8">Informasi tagihan dan pembayaran sekolah.</p>
                        
                        <div class="flex flex-col sm:flex-row gap-8">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-widest text-emerald-200 mb-1">Total Tagihan Aktif</p>
                                <p class="text-4xl font-bold text-[#fbbf24]">{{ stats.unpaid_total }}</p>
                            </div>
                             <div class="h-auto w-px bg-white/20 hidden sm:block"></div>
                             <div class="flex gap-8">
                                 <div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-200 mb-1">Belum Lunas</p>
                                    <p class="text-2xl font-bold">{{ stats.pending_count }} <span class="text-sm font-normal">Tagihan</span></p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-200 mb-1">Lunas</p>
                                    <p class="text-2xl font-bold">{{ stats.paid_count }} <span class="text-sm font-normal">Riwayat</span></p>
                                </div>
                             </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <div v-if="stats.pending_count > 0" class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-6">
                    <div>
                        <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                             <CreditCardIcon class="w-5 h-5 text-[#064e3b]" />
                             Metode Pembayaran
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">Transfer Bank / Virtual Account (a.n. Yayasan Namira).</p>
                    </div>

                    <!-- Bank Account Info -->
                    <div class="bg-emerald-50/70 rounded-2xl p-4 border border-emerald-200/70">
                        <p class="text-[10px] font-bold text-emerald-800 uppercase tracking-widest mb-1">Rekening BCA Yayasan Namira</p>
                        <div class="flex items-center justify-between">
                            <p class="font-mono text-xl font-bold text-slate-900">0283 7491 02</p>
                        </div>
                        <p class="text-xs text-emerald-700 font-medium mt-1">a.n. Yayasan Namira Probolinggo</p>
                    </div>
                </div>
            </div>

            <!-- Bill History List -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-lg text-slate-800 flex items-center gap-2">
                        <DocumentTextIcon class="w-5 h-5 text-[#064e3b]" />
                        Daftar Tagihan Sekolah
                    </h3>
                </div>
                
                <div class="divide-y divide-slate-100">
                    <div v-for="bill in bills" :key="bill.id" class="p-6 hover:bg-slate-50/80 transition-colors group flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl shrink-0"
                                :class="bill.status === 'paid' ? 'bg-emerald-100 text-emerald-700' : (bill.status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-400')">
                                <CheckBadgeIcon v-if="bill.status === 'paid'" class="w-6 h-6" />
                                <ClockIcon v-else class="w-6 h-6" />
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 text-base group-hover:text-[#064e3b] transition-colors">{{ bill.title }}</h4>
                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                    <span class="text-xs px-2.5 py-0.5 rounded-full font-bold border" :class="getStatusColor(bill.status)">
                                        {{ getStatusLabel(bill.status) }}
                                    </span>
                                    <span class="text-xs text-slate-400 font-medium">Tenggat: {{ bill.due_date }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 w-full sm:w-auto mt-2 sm:mt-0">
                            <div class="text-right flex-1 sm:flex-none">
                                <p class="font-bold text-slate-900 text-lg">{{ bill.amount_formatted }}</p>
                                <p v-if="bill.status === 'paid'" class="text-xs text-emerald-600 font-medium">Lunas pada: {{ bill.payment_date }}</p>
                            </div>
                            
                            <!-- Action Buttons -->
                            <button 
                                v-if="bill.status === 'unpaid'" 
                                @click="openUploadModal(bill)"
                                class="px-5 py-2.5 bg-[#064e3b] hover:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-950/20 transition-all flex items-center gap-2"
                            >
                                <ArrowUpTrayIcon class="w-4 h-4 text-[#fbbf24]" />
                                <span>Bayar & Upload Bukti</span>
                            </button>
                            
                            <div v-else-if="bill.status === 'pending'" class="flex items-center gap-2">
                                <button 
                                    v-if="bill.payment_proof"
                                    @click="openProofModal(bill.payment_proof)"
                                    class="px-3.5 py-2 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-xs font-semibold hover:bg-amber-100 transition-all flex items-center gap-1.5"
                                >
                                    <EyeIcon class="w-4 h-4" />
                                    <span>Lihat Bukti</span>
                                </button>
                            </div>
                            
                            <span v-else class="px-4 py-2 bg-emerald-50 text-emerald-700 rounded-xl text-xs font-bold border border-emerald-200">
                                Selesai
                            </span>
                        </div>
                    </div>

                    <div v-if="bills.length === 0" class="p-12 text-center">
                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                             <DocumentTextIcon class="w-8 h-8" />
                        </div>
                        <p class="text-slate-500 font-medium">Belum ada tagihan.</p>
                    </div>
                </div>
            </div>

            <!-- Upload Proof Modal -->
            <Modal :show="showUploadModal" @close="closeUploadModal" maxWidth="lg">
                <div class="p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <h3 class="text-lg font-bold text-slate-900">Upload Bukti Transfer Bank</h3>
                        <button @click="closeUploadModal" class="text-slate-400 hover:text-slate-600">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitUploadProof" class="mt-6 space-y-5">
                        <div v-if="selectedBill" class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
                            <p class="text-xs text-slate-500">Tagihan Yang Dibayar:</p>
                            <p class="font-bold text-slate-900 text-base mt-0.5">{{ selectedBill.title }}</p>
                            <p class="font-mono font-bold text-[#064e3b] text-lg mt-1">{{ selectedBill.amount_formatted }}</p>
                        </div>

                        <!-- Quick Demo Download Receipt Button -->
                        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-amber-900">Demo Mode: Contoh Struk BCA</p>
                                <p class="text-[11px] text-amber-700 mt-0.5">Download bukti transfer BCA buatan untuk langsung di-upload.</p>
                            </div>
                            <a 
                                href="/images/demo/struk-transfer-bca.svg" 
                                download="struk-transfer-bca-demo.svg"
                                target="_blank"
                                class="shrink-0 px-3 py-1.5 bg-amber-400 hover:bg-amber-500 text-amber-950 font-bold rounded-xl text-xs flex items-center gap-1.5 transition"
                            >
                                <ArrowDownTrayIcon class="w-4 h-4" />
                                <span>Download Struk</span>
                            </a>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2">Pilih File Bukti Transfer (Gambar / SVG / PDF)</label>
                            <input 
                                type="file" 
                                @change="form.proof = $event.target.files[0]"
                                accept="image/*,.pdf,.svg"
                                required
                                class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#064e3b] file:text-white hover:file:bg-emerald-800"
                            />
                            <p v-if="form.errors.proof" class="text-xs text-red-500 mt-1">{{ form.errors.proof }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
                            <input 
                                v-model="form.notes"
                                type="text"
                                placeholder="Contoh: Transfer via m-BCA a.n. Ahmad Zaki"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-[#064e3b] focus:ring-[#064e3b]"
                            />
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                            <button type="button" @click="closeUploadModal" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50">
                                Batal
                            </button>
                            <button type="submit" :disabled="form.processing" class="px-6 py-2.5 rounded-xl bg-[#064e3b] hover:bg-emerald-800 text-white text-xs font-bold shadow-md shadow-emerald-950/20 disabled:opacity-50">
                                Kirim Bukti Pembayaran
                            </button>
                        </div>
                    </form>
                </div>
            </Modal>

            <!-- Preview Proof Modal -->
            <Modal :show="showProofModal" @close="showProofModal = false" maxWidth="md">
                <div class="p-6 text-center">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                        <h3 class="text-base font-bold text-slate-900">Bukti Pembayaran Ter-upload</h3>
                        <button @click="showProofModal = false" class="text-slate-400 hover:text-slate-600">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <img :src="previewProofUrl" alt="Bukti Transfer" class="max-h-96 mx-auto rounded-2xl border border-slate-200 shadow-md object-contain mb-4" />

                    <a :href="previewProofUrl" target="_blank" class="inline-flex items-center gap-1.5 text-xs text-[#064e3b] font-bold hover:underline">
                        <span>Buka Gambar Ukuran Penuh</span>
                    </a>
                </div>
            </Modal>

        </div>
    </StudentLayout>
</template>
