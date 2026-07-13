<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { 
    MagnifyingGlassIcon, ArrowPathIcon, PlusIcon, 
    DocumentTextIcon, ArrowRightIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    bills: Object, // Paginator
    filters: Object,
});

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const isLoading = ref(false);

// Debounce Search
const debounce = (func, wait) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
};

const performSearch = debounce(() => {
    isLoading.value = true;
    router.get(route('yayasan.finance.bills.index'), 
        { search: searchQuery.value, status: statusFilter.value }, 
        { preserveState: true, replace: true, onFinish: () => isLoading.value = false }
    );
}, 300);

watch([searchQuery, statusFilter], () => {
    performSearch();
});

const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (date) => new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });

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
</script>

<template>
    <Head title="Daftar Tagihan Siswa" />

    <AuthenticatedLayout>
        <template #header>
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                        Daftar Tagihan Siswa
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Total: <span class="font-bold text-namira-teal">{{ bills.total }} Tagihan</span>
                    </p>
                </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6">
            <!-- Toolbar -->
            <div class="flex flex-col md:flex-row items-center gap-4">
                <div class="relative flex-1 w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                            <MagnifyingGlassIcon v-if="!isLoading" class="w-5 h-5" />
                            <ArrowPathIcon v-else class="animate-spin h-5 w-5 text-namira-teal" />
                        </div>
                    <input v-model="searchQuery" type="text" placeholder="Cari Nama Siswa / No. Tagihan..." class="pl-10 pr-4 py-2.5 w-full bg-white border border-gray-200 rounded-2xl text-sm focus:border-namira-teal focus:ring-namira-teal shadow-sm transition-all h-[46px]">
                </div>

                    <select v-model="statusFilter" class="px-4 py-2.5 bg-white border border-gray-200 text-gray-600 rounded-2xl text-sm font-bold focus:border-namira-teal focus:ring-namira-teal cursor-pointer shadow-sm hover:bg-gray-50 transition-all h-[46px] min-w-[150px]">
                    <option value="">Semua Status</option>
                    <option value="unpaid">Belum Lunas</option>
                    <option value="partial">Cicilan</option>
                    <option value="paid">Lunas</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>

                <Link :href="route('yayasan.finance.bills.create')" class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all flex items-center gap-2 whitespace-nowrap active:scale-95 h-[46px]">
                    <PlusIcon class="w-5 h-5" />
                    Generate Tagihan
                </Link>
            </div>

            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-white/50 border-b border-white/50">
                            <tr>
                                <th class="px-6 py-4 font-bold tracking-wider">Siswa</th>
                                <th class="px-6 py-4 font-bold tracking-wider">Keterangan</th>
                                <th class="px-6 py-4 font-bold tracking-wider">Tagihan Utuh</th>
                                <th class="px-6 py-4 font-bold tracking-wider">Sisa Tagihan</th>
                                <th class="px-6 py-4 font-bold tracking-wider">Status</th>
                                <th class="px-6 py-4 font-bold tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                             <tr v-if="bills.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <DocumentTextIcon class="w-12 h-12 mb-3 opacity-50" />
                                        <p>Belum ada data tagihan.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="bill in bills.data" :key="bill.id" class="group hover:bg-teal-50/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500">
                                            {{ bill.student?.full_name.substring(0,1) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900 line-clamp-1">{{ bill.student?.full_name }}</div>
                                            <div class="text-xs text-gray-500 font-mono">{{ bill.bill_code }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-800">{{ bill.description }}</div>
                                    <div class="text-xs text-gray-500">Jatuh Tempo: {{ formatDate(bill.due_date) }}</div>
                                </td>
                                <td class="px-6 py-4 font-mono text-gray-700">
                                    {{ formatCurrency(bill.final_amount) }}
                                </td>
                                <td class="px-6 py-4 font-mono font-bold" :class="bill.final_amount - bill.paid_amount > 0 ? 'text-red-600' : 'text-green-600'">
                                    {{ formatCurrency(bill.final_amount - bill.paid_amount) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-lg border shadow-sm" :class="statusClass(bill.status)">
                                        {{ statusLabel(bill.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('yayasan.finance.bills.show', bill.id)" class="text-xs font-bold text-namira-teal hover:underline flex items-center justify-end gap-1">
                                        Detail
                                        <ArrowRightIcon class="w-4 h-4" />
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                 <!-- Pagination -->
                <div v-if="bills.links.length > 3" class="p-4 border-t border-white/50 flex justify-center bg-white/30">
                     <div class="flex gap-1 bg-white/50 backdrop-blur-md p-1 rounded-xl border border-white/50 shadow-sm">
                        <template v-for="(link, k) in bills.links" :key="k">
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
