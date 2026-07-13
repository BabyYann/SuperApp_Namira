<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUpTrayIcon } from '@heroicons/vue/24/outline';
defineProps({
    transactions: Object,
});

const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (date) => new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit' });
</script>

<template>
    <Head title="Riwayat Transaksi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                     <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                        Riwayat Transaksi
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Daftar pembayaran masuk & alokasi dana.</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6">
            <!-- Actions Toolbar -->
            <div class="flex justify-end">
                <Link :href="route('yayasan.finance.transactions.import')" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-2xl text-sm font-bold shadow-sm hover:bg-gray-50 hover:border-gray-300 transition-all flex items-center gap-2 group">
                     <div class="p-1 bg-emerald-100 rounded-lg group-hover:bg-emerald-200 transition-colors">
                        <ArrowUpTrayIcon class="w-4 h-4 text-emerald-700" />
                     </div>
                    Import Mutasi Bank
                </Link>
            </div>

            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-500 uppercase bg-white/50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 font-bold tracking-wider">Kode TRX</th>
                            <th class="px-6 py-4 font-bold tracking-wider">Tanggal</th>
                            <th class="px-6 py-4 font-bold tracking-wider">Siswa</th>
                            <th class="px-6 py-4 font-bold tracking-wider">Total Masuk</th>
                            <th class="px-6 py-4 font-bold tracking-wider">Terbayar</th>
                            <th class="px-6 py-4 font-bold tracking-wider">Deposit</th>
                            <th class="px-6 py-4 font-bold tracking-wider">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-if="transactions.data.length === 0">
                             <td colspan="7" class="px-6 py-12 text-center text-gray-400">Belum ada transaksi.</td>
                        </tr>
                        <tr v-for="trx in transactions.data" :key="trx.id" class="hover:bg-teal-50/30 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs text-gray-500">{{ trx.transaction_code }}</td>
                            <td class="px-6 py-4">{{ formatDate(trx.transaction_date) }}</td>
                            <td class="px-6 py-4 font-bold text-gray-700">{{ trx.student?.name || 'Unknown' }}</td>
                            <td class="px-6 py-4 font-mono font-bold text-green-600">+ {{ formatCurrency(trx.amount) }}</td>
                            <td class="px-6 py-4 font-mono text-gray-600">{{ formatCurrency(trx.allocated_amount) }}</td>
                            <td class="px-6 py-4 font-mono font-bold" :class="trx.excess_amount > 0 ? 'text-blue-600' : 'text-gray-400'">
                                {{ formatCurrency(trx.excess_amount) }}
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500 max-w-xs truncate" :title="trx.notes">{{ trx.notes }}</td>
                        </tr>
                    </tbody>
                </table>
                 <div v-if="transactions.links.length > 3" class="p-4 border-t border-white/50 flex justify-center bg-white/30">
                     <div class="flex gap-1 bg-white/50 backdrop-blur-md p-1 rounded-xl border border-white/50 shadow-sm">
                        <template v-for="(link, k) in transactions.links" :key="k">
                            <Link 
                                v-if="link.url" 
                                :href="link.url" 
                                v-html="link.label"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                                :class="link.active ? 'bg-namira-teal text-white shadow-md' : 'text-gray-500 hover:bg-white hover:text-namira-teal'"
                            />
                             <span v-else v-html="link.label" class="px-3 py-1.5 text-gray-300 text-xs font-bold"></span>
                        </template>
                     </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
