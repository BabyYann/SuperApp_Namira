<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { 
    BanknotesIcon, 
    CreditCardIcon, 
    DocumentTextIcon, 
    ArrowUpTrayIcon,
    ExclamationTriangleIcon,
    CheckBadgeIcon,
    ClockIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    student: Object,
    bills: Array,
    stats: Object,
    finance_accounts: Array,
});

const getStatusColor = (status) => {
    switch(status) {
        case 'paid': return 'text-green-600 bg-green-50 border-green-100';
        case 'unpaid': return 'text-red-600 bg-red-50 border-red-100';
        case 'pending': return 'text-yellow-600 bg-yellow-50 border-yellow-100';
        default: return 'text-gray-600 bg-gray-50 border-gray-100';
    }
};

const getStatusLabel = (status) => {
    switch(status) {
        case 'paid': return 'Lunas';
        case 'unpaid': return 'Belum Lunas';
        case 'pending': return 'Menunggu Konfirmasi';
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
                <div class="lg:col-span-2 bg-gradient-to-br from-namira-teal to-teal-700 rounded-3xl p-8 text-white relative overflow-hidden shadow-lg">
                    <!-- Decor -->
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                    
                    <div class="relative z-10">
                        <h2 class="text-3xl font-bold mb-1">Keuangan</h2>
                        <p class="text-teal-100 mb-8">Informasi tagihan dan pembayaran sekolah.</p>
                        
                        <div class="flex flex-col sm:flex-row gap-8">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-widest text-teal-200 mb-1">Total Tagihan Aktif</p>
                                <p class="text-4xl font-bold">{{ stats.unpaid_total }}</p>
                            </div>
                             <div class="h-auto w-px bg-white/20 hidden sm:block"></div>
                             <div class="flex gap-8">
                                 <div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-teal-200 mb-1">Belum Lunas</p>
                                    <p class="text-2xl font-bold">{{ stats.pending_count }} <span class="text-sm font-normal">Tagihan</span></p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-teal-200 mb-1">Lunas</p>
                                    <p class="text-2xl font-bold">{{ stats.paid_count }} <span class="text-sm font-normal">Riwayat</span></p>
                                </div>
                             </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Information (Only Show if Pending Bills Exist) -->
                <div v-if="stats.pending_count > 0" class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-6">
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                             <CreditCardIcon class="w-5 h-5 text-namira-teal" />
                             Metode Pembayaran
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">Gunakan Virtual Account agar pembayaran terverifikasi otomatis.</p>
                    </div>

                    <!-- 1. Virtual Account (Priority) -->
                    <div class="bg-teal-50 rounded-xl p-4 border border-teal-100">
                        <p class="text-xs font-bold text-teal-600 uppercase tracking-widest mb-1">Virtual Account (Otomatis)</p>
                        <div class="flex items-center justify-between">
                            <p class="font-mono text-2xl font-bold text-gray-800">
                                {{ student.va_number || 'Belum Tersedia' }}
                            </p>
                            <button v-if="student.va_number" 
                                class="text-xs bg-white py-1 px-3 rounded-lg border border-teal-200 text-teal-700 font-bold hover:bg-teal-50"
                                onclick="navigator.clipboard.writeText(this.previousElementSibling.innerText); alert('VA Salin!')"
                            >
                                Salin
                            </button>
                        </div>
                        <p class="text-xs text-teal-600 mt-2">a.n. {{ student.full_name }}</p>
                    </div>


                </div>
            </div>

            <!-- Bill History List -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                        <DocumentTextIcon class="w-5 h-5 text-namira-teal" />
                        Riwayat Tagihan
                    </h3>
                    <button class="text-sm text-namira-teal font-bold hover:underline">Download Laporan</button>
                </div>
                
                <div class="divide-y divide-gray-50">
                    <div v-for="bill in bills" :key="bill.id" class="p-6 hover:bg-gray-50 transition-colors group flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl shrink-0"
                                :class="bill.status === 'paid' ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400'">
                                <CheckBadgeIcon v-if="bill.status === 'paid'" class="w-6 h-6" />
                                <ClockIcon v-else class="w-6 h-6" />
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800 text-base group-hover:text-namira-teal transition-colors">{{ bill.title }}</h4>
                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                    <span class="text-xs px-2.5 py-0.5 rounded-full font-bold border" :class="getStatusColor(bill.status)">
                                        {{ getStatusLabel(bill.status) }}
                                    </span>
                                    <span class="text-xs text-gray-400 font-medium">Tenggat: {{ bill.due_date }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 w-full sm:w-auto mt-2 sm:mt-0">
                            <div class="text-right flex-1 sm:flex-none">
                                <p class="font-bold text-gray-900 text-lg">{{ bill.amount_formatted }}</p>
                                <p v-if="bill.status === 'paid'" class="text-xs text-gray-400">Dibayar: {{ bill.payment_date }}</p>
                            </div>
                            <button v-if="bill.status === 'unpaid'" class="px-4 py-2 bg-namira-teal text-white rounded-lg text-sm font-bold shadow-md shadow-namira-teal/20 hover:bg-teal-700 transition-all">
                                Bayar
                            </button>
                            <button v-else class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition-all">
                                Detail
                            </button>
                        </div>
                    </div>

                    <div v-if="bills.length === 0" class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                             <DocumentTextIcon class="w-8 h-8" />
                        </div>
                        <p class="text-gray-500 font-medium">Belum ada tagihan.</p>
                    </div>
                </div>
            </div>

        </div>
    </StudentLayout>
</template>
