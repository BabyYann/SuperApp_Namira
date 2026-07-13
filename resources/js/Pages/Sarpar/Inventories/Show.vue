<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { 
    ArrowLeftIcon, CubeIcon, WrenchScrewdriverIcon, ClockIcon,
    ExclamationCircleIcon, CheckCircleIcon, TagIcon, MapPinIcon,
    PhotoIcon, ArchiveBoxIcon, MinusCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    inventory: Object,
});

const showReportModal = ref(false);
const showUsageModal = ref(false);

const reportForm = useForm({
    inventory_id: props.inventory.id,
    issue: '',
});

const usageForm = useForm({
    inventory_id: props.inventory.id,
    quantity_used: 1,
    purpose: '',
    notes: '',
});

const submitReport = () => {
    reportForm.post(route('sarpar.maintenance.store'), {
        onSuccess: () => { showReportModal.value = false; reportForm.reset('issue'); },
    });
};

const submitUsage = () => {
    usageForm.post(route('sarpar.usage.store'), {
        onSuccess: () => { showUsageModal.value = false; usageForm.reset(); usageForm.quantity_used = 1; },
    });
};

const getConditionBadge = (condition) => {
    const badges = { 'baik': 'bg-green-100 text-green-700', 'rusak_ringan': 'bg-amber-100 text-amber-700', 'rusak_berat': 'bg-red-100 text-red-700' };
    return badges[condition] || 'bg-gray-100';
};
const getStatusBadge = (status) => {
    const badges = { 'tersedia': 'bg-green-100 text-green-700', 'dipinjam': 'bg-blue-100 text-blue-700', 'diperbaiki': 'bg-amber-100 text-amber-700', 'dihapus': 'bg-gray-100 text-gray-500' };
    return badges[status] || 'bg-gray-100';
};
const getSourceBadge = (source) => source === 'BOS' ? 'bg-purple-100 text-purple-700' : 'bg-teal-100 text-teal-700';
</script>

<template>
    <Head :title="`Inventaris: ${inventory.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('sarpar.inventories.index')" class="p-2 rounded-xl hover:bg-white/50 text-gray-500 transition-colors">
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h2 class="font-bold text-2xl text-gray-800">{{ inventory.name }}</h2>
                    <p class="text-sm font-mono text-gray-500">{{ inventory.code }}</p>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6 px-4 pb-12">
            <!-- Info Card -->
            <div class="lg:col-span-1 bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 p-6 space-y-6">
                <!-- Photo -->
                <div class="flex justify-center">
                    <div v-if="inventory.photo" class="w-32 h-32 rounded-2xl overflow-hidden">
                        <img :src="`/storage/${inventory.photo}`" class="w-full h-full object-cover" />
                    </div>
                    <div v-else class="w-32 h-32 bg-gradient-to-br from-namira-teal/10 to-teal-100 rounded-2xl flex items-center justify-center">
                        <PhotoIcon class="w-12 h-12 text-namira-teal/50" />
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Badges -->
                    <div class="flex flex-wrap justify-center gap-2">
                        <span :class="['px-3 py-1 text-xs font-bold rounded-full', getSourceBadge(inventory.funding_source)]">{{ inventory.funding_source === 'BOS' ? 'Dana BOS' : 'Dana Yayasan' }}</span>
                        <span :class="['px-3 py-1 text-xs font-bold rounded-full capitalize', getStatusBadge(inventory.status)]">{{ inventory.status }}</span>
                        <span :class="['px-3 py-1 text-xs font-bold rounded-full', inventory.item_type === 'consumable' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700']">
                            {{ inventory.item_type === 'consumable' ? '📦 Habis Pakai' : '🏢 Aset' }}
                        </span>
                    </div>

                    <!-- Stock -->
                    <div class="text-center border-t border-gray-100 pt-4">
                        <div class="text-4xl font-extrabold text-gray-800">{{ inventory.quantity }}</div>
                        <div class="text-xs text-gray-500">{{ inventory.item_type === 'consumable' ? 'stok tersisa' : 'unit' }}</div>
                        <div v-if="inventory.is_low_stock" class="mt-2 px-3 py-1 bg-red-100 text-red-600 text-xs font-bold rounded-full inline-flex items-center gap-1 animate-pulse">
                            <ExclamationCircleIcon class="w-3 h-3" />
                            Stok Menipis! (Min: {{ inventory.min_stock }})
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center gap-3">
                            <TagIcon class="w-4 h-4 text-gray-400" />
                            <span>{{ inventory.category?.name || '-' }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <MapPinIcon class="w-4 h-4 text-gray-400" />
                            <span>{{ inventory.location_name }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <ClockIcon class="w-4 h-4 text-gray-400" />
                            <span>Tahun {{ inventory.year_acquired }}</span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 text-sm text-gray-600 space-y-1">
                        <div v-if="inventory.brand"><strong>Merk:</strong> {{ inventory.brand }}</div>
                        <div v-if="inventory.model"><strong>Model:</strong> {{ inventory.model }}</div>
                        <div v-if="inventory.unit_price"><strong>Harga:</strong> Rp {{ Number(inventory.unit_price).toLocaleString('id-ID') }}</div>
                    </div>

                    <div class="pt-4 border-t border-gray-100">
                        <div class="text-xs text-gray-500 mb-2">Kondisi</div>
                        <span :class="['px-3 py-1.5 text-sm font-bold rounded-xl capitalize', getConditionBadge(inventory.condition)]">{{ inventory.condition.replace('_', ' ') }}</span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-4 space-y-2">
                        <button v-if="inventory.item_type === 'consumable'" @click="showUsageModal = true" class="w-full px-4 py-3 bg-orange-500 text-white rounded-xl font-bold flex items-center justify-center gap-2 hover:bg-orange-600 transition-colors shadow-lg shadow-orange-500/30">
                            <MinusCircleIcon class="w-5 h-5" />
                            Catat Penggunaan
                        </button>
                        <button @click="showReportModal = true" class="w-full px-4 py-3 bg-amber-500 text-white rounded-xl font-bold flex items-center justify-center gap-2 hover:bg-amber-600 transition-colors shadow-lg shadow-amber-500/30">
                            <WrenchScrewdriverIcon class="w-5 h-5" />
                            Laporkan Kerusakan
                        </button>
                    </div>
                </div>
            </div>

            <!-- History Tabs -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Usage History (for Consumables) -->
                <div v-if="inventory.item_type === 'consumable'" class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                            <ArchiveBoxIcon class="w-5 h-5 text-orange-500" />
                            Riwayat Penggunaan
                        </h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <div v-if="!inventory.usage_logs || inventory.usage_logs.length === 0" class="text-center text-gray-400 py-8">
                            <CheckCircleIcon class="w-12 h-12 mx-auto mb-2" />
                            <p>Belum ada penggunaan tercatat</p>
                        </div>
                        <div v-for="log in inventory.usage_logs" :key="log.id" class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-bold text-gray-800">-{{ log.quantity_used }} unit</p>
                                    <p v-if="log.purpose" class="text-sm text-gray-600 mt-1">{{ log.purpose }}</p>
                                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                                        <span>{{ new Date(log.used_date).toLocaleDateString('id-ID') }}</span>
                                        <span>oleh {{ log.user?.name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance History -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                            <WrenchScrewdriverIcon class="w-5 h-5 text-amber-500" />
                            Riwayat Perawatan
                        </h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <div v-if="inventory.maintenance_logs.length === 0" class="text-center text-gray-400 py-8">
                            <CheckCircleIcon class="w-12 h-12 mx-auto mb-2" />
                            <p>Tidak ada riwayat perawatan</p>
                        </div>
                        <div v-for="log in inventory.maintenance_logs" :key="log.id" class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                            <div class="flex items-start gap-3">
                                <div :class="['w-2 h-2 rounded-full mt-2', log.status === 'resolved' ? 'bg-green-500' : log.status === 'in_progress' ? 'bg-blue-500' : 'bg-amber-500']"></div>
                                <div class="flex-1">
                                    <p class="font-bold text-gray-800">{{ log.issue }}</p>
                                    <p v-if="log.action_taken" class="text-sm text-gray-600 mt-1">Tindakan: {{ log.action_taken }}</p>
                                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                                        <span>Dilaporkan: {{ new Date(log.reported_date).toLocaleDateString('id-ID') }}</span>
                                        <span v-if="log.resolved_date">Selesai: {{ new Date(log.resolved_date).toLocaleDateString('id-ID') }}</span>
                                        <span v-if="log.cost">Biaya: Rp {{ Number(log.cost).toLocaleString('id-ID') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loan History (for Assets) -->
                <div v-if="inventory.item_type === 'asset'" class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                            <ClockIcon class="w-5 h-5 text-blue-500" />
                            Riwayat Peminjaman
                        </h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <div v-if="inventory.loans.length === 0" class="text-center text-gray-400 py-8">
                            <CheckCircleIcon class="w-12 h-12 mx-auto mb-2" />
                            <p>Belum pernah dipinjam</p>
                        </div>
                        <div v-for="loan in inventory.loans" :key="loan.id" class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-bold text-gray-800">{{ loan.borrower?.name }}</p>
                                    <p class="text-xs text-gray-500">{{ new Date(loan.loan_date).toLocaleDateString('id-ID') }} - {{ loan.return_date ? new Date(loan.return_date).toLocaleDateString('id-ID') : 'Belum dikembalikan' }}</p>
                                </div>
                                <span :class="['px-2 py-1 text-xs font-bold rounded-lg', loan.status === 'returned' ? 'bg-green-100 text-green-700' : loan.status === 'borrowed' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700']">{{ loan.status }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Damage Modal -->
        <Teleport to="body">
            <div v-if="showReportModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showReportModal = false"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-8 border border-gray-100">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Laporkan Kerusakan</h3>
                        <p class="text-sm text-gray-500 mb-6">{{ inventory.name }}</p>
                        <form @submit.prevent="submitReport" class="space-y-5">
                            <div>
                                <InputLabel value="Deskripsi Kerusakan *" class="text-sm font-bold text-gray-700" />
                                <textarea v-model="reportForm.issue" rows="4" class="w-full mt-1.5 px-4 py-3 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" placeholder="Jelaskan kerusakannya..." required></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-50">
                                <button type="button" @click="showReportModal = false" class="px-5 py-2.5 text-gray-500 font-bold hover:bg-gray-50 rounded-xl">Batal</button>
                                <PrimaryButton :disabled="reportForm.processing" class="rounded-xl px-6 bg-amber-500 hover:bg-amber-600 shadow-lg shadow-amber-500/30">Kirim Laporan</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Usage Modal (for Consumables) -->
        <Teleport to="body">
            <div v-if="showUsageModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showUsageModal = false"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-8 border border-gray-100">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Catat Penggunaan</h3>
                        <p class="text-sm text-gray-500 mb-6">{{ inventory.name }} (Stok: {{ inventory.quantity }})</p>
                        <form @submit.prevent="submitUsage" class="space-y-5">
                            <div>
                                <InputLabel value="Jumlah Digunakan *" class="text-sm font-bold text-gray-700" />
                                <input v-model="usageForm.quantity_used" type="number" min="1" :max="inventory.quantity" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" required />
                            </div>
                            <div>
                                <InputLabel value="Tujuan Penggunaan" class="text-sm font-bold text-gray-700" />
                                <input v-model="usageForm.purpose" type="text" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" placeholder="Contoh: Untuk siswa sakit" />
                            </div>
                            <div>
                                <InputLabel value="Catatan" class="text-sm font-bold text-gray-700" />
                                <textarea v-model="usageForm.notes" rows="2" class="w-full mt-1.5 px-4 py-3 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" placeholder="Keterangan (opsional)"></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-50">
                                <button type="button" @click="showUsageModal = false" class="px-5 py-2.5 text-gray-500 font-bold hover:bg-gray-50 rounded-xl">Batal</button>
                                <PrimaryButton :disabled="usageForm.processing" class="rounded-xl px-6 bg-orange-500 hover:bg-orange-600 shadow-lg shadow-orange-500/30">Catat</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
