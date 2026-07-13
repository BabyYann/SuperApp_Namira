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

        <div class="py-6 max-w-7xl mx-auto pb-20 space-y-6">
            <!-- Toolbar -->
            <div class="flex flex-col md:flex-row items-center gap-4">
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

            <!-- Table -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
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
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-amber-50/30 transition-colors">
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
