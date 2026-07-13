<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { 
    BanknotesIcon, UserGroupIcon, MagnifyingGlassIcon, ArrowPathIcon, 
    PrinterIcon, InboxIcon, ChatBubbleLeftRightIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    students: Object,
    classrooms: Array,
    filters: Object,
    total_arrears_sum: Number, // Total of all arrears in current view
    student_count: Number
});

const search = ref(props.filters.search || '');
const classroom_id = ref(props.filters.classroom_id || '');
const isLoading = ref(false);

const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

const debounce = (func, wait) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
};

const performSearch = debounce(() => {
    isLoading.value = true;
    router.get(route('yayasan.finance.reports.arrears'), {
        search: search.value,
        classroom_id: classroom_id.value,
    }, { 
        preserveState: true, 
        preserveScroll: true, 
        replace: true,
        onFinish: () => isLoading.value = false
    });
}, 500);

watch([search, classroom_id], () => performSearch());

const printLetter = (studentId) => {
    // FIX: Use correct route name
    const url = route('yayasan.finance.reports.arrears.print', studentId);
    window.open(url, '_blank');
};

const printRecap = () => {
    const url = route('yayasan.finance.reports.arrears.recap', {
        search: search.value,
        classroom_id: classroom_id.value
    });
    window.open(url, '_blank');
};
</script>

<template>
    <Head title="Laporan Tunggakan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                     <h2 class="font-extrabold text-3xl bg-gradient-to-r from-rose-700 to-pink-600 bg-clip-text text-transparent leading-tight tracking-tight">
                        Laporan Tunggakan
                    </h2>
                    <p class="text-sm text-gray-500 mt-1 font-medium">Monitoring kewajiban administrasi siswa.</p>
                </div>
            </div>
        </template>

        <div class="py-8 max-w-7xl mx-auto space-y-6">
            
            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-3xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07)] border border-gray-100 flex items-center gap-4 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-rose-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                    <div class="w-12 h-12 bg-rose-100 rounded-2xl flex items-center justify-center text-rose-600 z-10">
                        <BanknotesIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Total Tunggakan</p>
                        <h3 class="text-2xl font-black text-gray-800">{{ formatCurrency(total_arrears_sum || 0) }}</h3>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07)] border border-gray-100 flex items-center gap-4 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                     <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-600 z-10">
                        <UserGroupIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Siswa Menunggak</p>
                        <h3 class="text-2xl font-black text-gray-800">{{ student_count || 0 }} Siswa</h3>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col md:flex-row items-center gap-4">
                <div class="relative flex-1 w-full">
                     <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                         <MagnifyingGlassIcon v-if="!isLoading" class="w-5 h-5" />
                         <ArrowPathIcon v-else class="animate-spin h-5 w-5 text-rose-500" />
                    </div>
                    <input v-model="search" type="text" placeholder="Cari Nama Siswa atau NIS..." class="pl-10 pr-4 py-2.5 w-full bg-white border-white/50 rounded-2xl text-sm focus:border-rose-500 focus:ring-rose-500/20 shadow-sm transition-all h-[46px]">
                </div>
                
                <select v-model="classroom_id" class="px-4 py-2.5 bg-white border border-white/50 text-gray-600 rounded-2xl text-sm font-bold focus:border-rose-500 focus:ring-rose-500 cursor-pointer shadow-sm hover:bg-gray-50 transition-all h-[46px] min-w-[200px]">
                    <option value="">Semua Kelas</option>
                    <option v-for="cls in classrooms" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                </select>

                <button @click="printRecap" class="px-6 py-2.5 bg-white border border-rose-200 text-rose-600 rounded-2xl text-sm font-bold shadow-sm hover:bg-rose-50 hover:border-rose-300 transition-all flex items-center gap-2 h-[46px] whitespace-nowrap">
                    <PrinterIcon class="w-5 h-5" />
                    Cetak Rekap
                </button>
            </div>

            <!-- List -->
            <div class="space-y-4">
                <div v-if="students.data.length === 0" class="bg-white rounded-3xl shadow-sm border border-gray-100 p-12 text-center">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <InboxIcon class="w-10 h-10 text-gray-300" />
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Tidak ada tunggakan</h3>
                    <p class="text-gray-500 mt-1">Alhamdulillah, tidak ada data siswa menunggak untuk filter ini.</p>
                </div>

                <div v-else class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50/50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-5 font-bold tracking-wider">Siswa</th>
                                <th class="px-6 py-5 font-bold tracking-wider">Kelas</th>
                                <th class="px-6 py-5 font-bold tracking-wider text-right">Total Tunggakan</th>
                                <th class="px-6 py-5 font-bold tracking-wider">Rincian</th>
                                <th class="px-6 py-5 font-bold tracking-wider text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50/50">
                            <tr v-for="student in students.data" :key="student.id" class="hover:bg-rose-50/5 transition-colors group">
                                <td class="px-6 py-4 align-top">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-xs font-bold text-gray-600 border border-white shadow-sm">
                                            {{ student.name.substring(0, 2).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-800 text-base">{{ student.name }}</div>
                                            <div class="text-xs text-gray-500 font-mono tracking-wide">{{ student.nis }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ student.classroom }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-top text-right">
                                    <div class="font-black text-rose-600 text-base">
                                        {{ formatCurrency(student.total_arrears) }}
                                    </div>
                                    <div class="text-[10px] text-gray-400 font-medium mt-1">{{ student.bill_count }} Tagihan Belum Lunas</div>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <div class="flex flex-col gap-2">
                                        <div v-for="(bill, i) in student.bills" :key="i" class="flex justify-between items-center w-full max-w-sm bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100">
                                            <span class="text-xs text-gray-600 font-medium truncate max-w-[150px]" :title="bill.name">{{ bill.name }}</span>
                                            <div class="flex items-center gap-2">
                                                 <span class="text-[10px] text-gray-400 bg-white px-1.5 rounded border border-gray-100">{{ bill.month }}</span>
                                                 <span class="font-mono text-xs text-gray-700 font-bold">{{ formatCurrency(bill.amount) }}</span>
                                            </div>
                                        </div>
                                        <div v-if="student.bill_count > 3" class="text-[10px] text-rose-500 font-medium italic pl-1">
                                            + {{ student.bill_count - 3 }} tagihan lainnya...
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top text-center">
                                    <div class="flex flex-col gap-2 justify-center">
                                        <a :href="student.wa_link" target="_blank" class="w-full px-3 py-2 bg-[#25D366]/10 text-[#25D366] border border-[#25D366]/20 rounded-xl text-xs font-bold hover:bg-[#25D366] hover:text-white transition-all flex items-center justify-center gap-2 group-btn">
                                            <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                            Kirim WA
                                        </a>
                                        <button @click="printLetter(student.id)" class="w-full px-3 py-2 bg-rose-50 text-rose-600 border border-rose-200 rounded-xl text-xs font-bold hover:bg-rose-600 hover:text-white transition-all flex items-center justify-center gap-2">
                                            <PrinterIcon class="w-4 h-4" />
                                            Cetak Surat
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                     <div v-if="students.links.length > 3" class="p-4 border-t border-gray-100 flex justify-center bg-gray-50/30">
                         <div class="flex gap-1 justify-center">
                            <Link v-for="(link, k) in students.links" :key="k" :href="link.url" v-html="link.label" class="px-3 py-1.5 border rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-rose-600 text-white border-rose-600 shadow-sm' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'" />
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
