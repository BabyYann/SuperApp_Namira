<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { 
    MagnifyingGlassIcon, WrenchScrewdriverIcon, CheckCircleIcon,
    ClockIcon, XCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    logs: Object,
    filters: Object,
});

const searchQuery = ref(props.filters.search || '');
const filterStatus = ref(props.filters.status || '');
const showHandleModal = ref(false);
const selectedLog = ref(null);

const handleForm = useForm({
    action_taken: '',
    cost: '',
    resolved: false,
    inventory_condition: 'baik',
});

const applyFilters = () => {
    router.get(route('sarpar.maintenance.index'), {
        search: searchQuery.value || undefined,
        status: filterStatus.value || undefined,
    }, { preserveState: true, replace: true });
};

let searchTimeout = null;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 500);
});

const openHandleModal = (log) => {
    selectedLog.value = log;
    handleForm.reset();
    handleForm.inventory_condition = log.inventory?.condition || 'baik';
    showHandleModal.value = true;
};

const submitHandle = () => {
    handleForm.post(route('sarpar.maintenance.handle', selectedLog.value.id), {
        onSuccess: () => { showHandleModal.value = false; selectedLog.value = null; },
    });
};

const cancelLog = (log) => {
    if (confirm('Batalkan laporan ini?')) {
        router.post(route('sarpar.maintenance.cancel', log.id));
    }
};

const getStatusBadge = (status) => {
    const badges = { 'pending': 'bg-amber-100 text-amber-700', 'in_progress': 'bg-blue-100 text-blue-700', 'resolved': 'bg-green-100 text-green-700', 'cancelled': 'bg-gray-100 text-gray-500' };
    return badges[status] || 'bg-gray-100';
};

const getStatusLabel = (status) => {
    const labels = { 'pending': 'Menunggu', 'in_progress': 'Ditangani', 'resolved': 'Selesai', 'cancelled': 'Dibatalkan' };
    return labels[status] || status;
};
</script>

<template>
    <Head title="Perawatan & Perbaikan" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                    Perawatan & Perbaikan
                </h2>
                <p class="text-sm text-gray-500 mt-1">Laporan kerusakan dan riwayat perbaikan</p>
            </div>
        </template>

        <div class="py-4 md:py-6 max-w-7xl mx-auto pb-20 space-y-5 md:space-y-6">
            
            <!-- 1A. DESKTOP TOOLBAR (Unchanged Desktop Layout) -->
            <div class="hidden md:flex flex-row items-center gap-4">
                <div class="relative flex-1 w-full">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                        <MagnifyingGlassIcon class="w-5 h-5" />
                    </div>
                    <input v-model="searchQuery" type="text" placeholder="Cari nama barang atau masalah..." class="pl-10 pr-4 py-2.5 w-full bg-white/50 border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 shadow-sm h-[46px]" />
                </div>
                
                <select v-model="filterStatus" @change="applyFilters" class="px-4 py-2.5 bg-white border border-gray-200 rounded-2xl text-sm focus:ring-namira-teal focus:border-namira-teal h-[46px]">
                    <option value="">Semua Status</option>
                    <option value="pending">Menunggu</option>
                    <option value="in_progress">Ditangani</option>
                    <option value="resolved">Selesai</option>
                </select>
            </div>

            <!-- 1B. MOBILE TOOLBAR (Executive Namira Teal-to-Slate Header Card with Stats) -->
            <div class="block md:hidden -mx-4 -mt-4 space-y-4">
                <div class="bg-gradient-to-br from-[#009688] to-[#0f172a] px-4 pt-5 pb-6 text-white">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-[10px] font-extrabold tracking-widest uppercase text-teal-300">Modul Sarpar</p>
                            <h1 class="text-xl font-black leading-tight">Perawatan & Perbaikan</h1>
                        </div>
                        <div class="p-2 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20">
                            <WrenchScrewdriverIcon class="w-6 h-6 text-amber-300" />
                        </div>
                    </div>

                    <!-- Quick Stats Grid (3 Columns) -->
                    <div class="grid grid-cols-3 gap-2 text-center mt-3">
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-2 py-2">
                            <p class="text-lg font-black text-amber-300 leading-none">
                                {{ logs.data.filter(l => l.status === 'pending').length }}
                            </p>
                            <p class="text-[8px] text-amber-200 font-bold mt-1 uppercase">Menunggu</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-2 py-2">
                            <p class="text-lg font-black text-blue-300 leading-none">
                                {{ logs.data.filter(l => l.status === 'in_progress').length }}
                            </p>
                            <p class="text-[8px] text-blue-200 font-bold mt-1 uppercase">Ditangani</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-2 py-2">
                            <p class="text-lg font-black text-emerald-300 leading-none">
                                {{ logs.data.filter(l => l.status === 'resolved').length }}
                            </p>
                            <p class="text-[8px] text-emerald-200 font-bold mt-1 uppercase">Selesai</p>
                        </div>
                    </div>
                </div>

                <!-- Mobile Search & Filter Row -->
                <div class="px-4 space-y-2">
                    <div class="relative">
                        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-3.5 text-slate-400" />
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Cari barang / masalah..." 
                            class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:ring-teal-500 focus:border-teal-500 shadow-sm" 
                        />
                    </div>
                    <select v-model="filterStatus" @change="applyFilters" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 shadow-sm">
                        <option value="">Semua Status Laporan</option>
                        <option value="pending">Status: Menunggu</option>
                        <option value="in_progress">Status: Ditangani</option>
                        <option value="resolved">Status: Selesai</option>
                    </select>
                </div>
            </div>

            <!-- 2A. DESKTOP TABLE (Unchanged Desktop Layout) -->
            <div class="hidden md:block bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/50 text-xs uppercase text-gray-500 font-extrabold tracking-wider border-b border-gray-100">
                                <th class="p-4">Barang</th>
                                <th class="p-4">Masalah</th>
                                <th class="p-4">Pelapor</th>
                                <th class="p-4">Tanggal</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="logs.data.length === 0">
                                <td colspan="6" class="p-12 text-center text-gray-400">
                                    <CheckCircleIcon class="w-12 h-12 mx-auto mb-2 opacity-50" />
                                    <p class="font-bold">Tidak ada laporan perawatan</p>
                                </td>
                            </tr>
                            <tr v-for="log in logs.data" :key="'desk-'+log.id" class="hover:bg-amber-50/30 transition-colors">
                                <td class="p-4">
                                    <Link :href="route('sarpar.inventories.show', log.inventory?.id)" class="font-bold text-gray-800 hover:text-namira-teal">{{ log.inventory?.name }}</Link>
                                    <div class="text-xs text-gray-400">{{ log.inventory?.code }}</div>
                                </td>
                                <td class="p-4">
                                    <p class="text-sm text-gray-700 line-clamp-2">{{ log.issue }}</p>
                                </td>
                                <td class="p-4 text-sm text-gray-600">{{ log.reporter?.name }}</td>
                                <td class="p-4 text-sm text-gray-600">{{ new Date(log.reported_date).toLocaleDateString('id-ID') }}</td>
                                <td class="p-4">
                                    <span :class="['px-2 py-1 text-xs font-bold rounded-lg', getStatusBadge(log.status)]">{{ getStatusLabel(log.status) }}</span>
                                </td>
                                <td class="p-4">
                                    <div class="flex justify-end gap-2">
                                        <button v-if="log.status === 'pending' || log.status === 'in_progress'" @click="openHandleModal(log)" class="px-3 py-1.5 bg-namira-teal text-white text-xs font-bold rounded-lg hover:bg-teal-600 flex items-center gap-1">
                                            <WrenchScrewdriverIcon class="w-3 h-3" />
                                            Tangani
                                        </button>
                                        <button v-if="log.status === 'pending'" @click="cancelLog(log)" class="px-3 py-1.5 bg-gray-100 text-gray-600 text-xs font-bold rounded-lg hover:bg-gray-200 flex items-center gap-1">
                                            <XCircleIcon class="w-3 h-3" />
                                            Batal
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="logs.last_page > 1" class="p-4 border-t border-gray-100 flex justify-center gap-2">
                    <Link v-for="link in logs.links" :key="link.label" :href="link.url || '#'" :class="['px-3 py-1.5 rounded-xl text-sm', link.active ? 'bg-namira-teal text-white' : 'text-gray-600 hover:bg-gray-100']" v-html="link.label" />
                </div>
            </div>

            <!-- 2B. MOBILE NATIVE MAINTENANCE CARDS -->
            <div class="grid md:hidden grid-cols-1 gap-3.5">
                <div v-if="logs.data.length === 0" class="text-center py-12 bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                    <CheckCircleIcon class="w-12 h-12 text-slate-400 mx-auto mb-2 opacity-50" />
                    <p class="font-extrabold text-sm text-slate-900">Tidak ada laporan perawatan</p>
                </div>

                <div 
                    v-for="log in logs.data" 
                    :key="'mob-'+log.id" 
                    class="bg-white rounded-3xl p-4 border border-slate-200 shadow-sm flex flex-col space-y-3"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="text-[10px] font-mono text-slate-400">{{ log.inventory?.code }}</span>
                            <h4 class="font-extrabold text-base text-slate-900 leading-snug">{{ log.inventory?.name }}</h4>
                        </div>
                        <span :class="['px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-xl border', getStatusBadge(log.status)]">
                            {{ getStatusLabel(log.status) }}
                        </span>
                    </div>

                    <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100">
                        <p class="text-xs text-slate-700 font-medium leading-relaxed">{{ log.issue }}</p>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs text-slate-500">
                        <span>Pelapor: <strong>{{ log.reporter?.name || '-' }}</strong></span>
                        <span>{{ new Date(log.reported_date).toLocaleDateString('id-ID') }}</span>
                    </div>

                    <div v-if="log.status === 'pending' || log.status === 'in_progress'" class="flex items-center gap-2 pt-1">
                        <button @click="openHandleModal(log)" class="flex-1 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-extrabold rounded-2xl shadow-md flex items-center justify-center gap-1.5 active:scale-95">
                            <WrenchScrewdriverIcon class="w-4 h-4" />
                            <span>Tangani Laporan</span>
                        </button>
                        <button v-if="log.status === 'pending'" @click="cancelLog(log)" class="px-4 py-2.5 bg-rose-50 text-rose-700 text-xs font-extrabold rounded-2xl border border-rose-200 flex items-center justify-center gap-1 active:scale-95">
                            <XCircleIcon class="w-4 h-4" />
                            <span>Batal</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <!-- Handle Modal -->
        <Teleport to="body">
            <div v-if="showHandleModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showHandleModal = false"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-8 border border-gray-100">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Tangani Laporan</h3>
                        <p class="text-sm text-gray-500 mb-6">{{ selectedLog?.inventory?.name }}: {{ selectedLog?.issue }}</p>
                        <form @submit.prevent="submitHandle" class="space-y-5">
                            <div>
                                <InputLabel value="Tindakan yang Dilakukan *" class="text-sm font-bold text-gray-700" />
                                <textarea v-model="handleForm.action_taken" rows="3" class="w-full mt-1.5 px-4 py-3 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" placeholder="Jelaskan perbaikan yang dilakukan..." required></textarea>
                            </div>
                            <div>
                                <InputLabel value="Biaya Perbaikan (Rp)" class="text-sm font-bold text-gray-700" />
                                <input v-model="handleForm.cost" type="number" min="0" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" placeholder="0" />
                            </div>
                            <div>
                                <InputLabel value="Kondisi Barang Sekarang" class="text-sm font-bold text-gray-700" />
                                <select v-model="handleForm.inventory_condition" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50">
                                    <option value="baik">Baik</option>
                                    <option value="rusak_ringan">Rusak Ringan</option>
                                    <option value="rusak_berat">Rusak Berat</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-3">
                                <input type="checkbox" v-model="handleForm.resolved" id="resolved" class="rounded text-namira-teal focus:ring-namira-teal" />
                                <label for="resolved" class="text-sm font-bold text-gray-700">Tandai sebagai selesai</label>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-50">
                                <button type="button" @click="showHandleModal = false" class="px-5 py-2.5 text-gray-500 font-bold hover:bg-gray-50 rounded-xl">Batal</button>
                                <PrimaryButton :disabled="handleForm.processing" class="rounded-xl px-6 shadow-lg shadow-namira-teal/30">Simpan</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
