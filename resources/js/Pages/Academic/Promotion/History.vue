<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { ClockIcon, FunnelIcon, DocumentArrowDownIcon, ArrowUturnLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    promotions: Object,
    academicYears: Array,
    statusOptions: Object,
    filters: Object,
});

const selectedYear = ref(props.filters?.academic_year_id || '');
const selectedStatus = ref(props.filters?.status || '');

const applyFilters = () => {
    router.get(route('yayasan.promotion.history'), {
        academic_year_id: selectedYear.value || undefined,
        status: selectedStatus.value || undefined,
    }, { preserveState: true });
};

watch([selectedYear, selectedStatus], applyFilters);

// Computed list of latest promotion IDs for each student in the loaded data page
const latestPromotionIds = computed(() => {
    const map = {};
    if (props.promotions && props.promotions.data) {
        props.promotions.data.forEach(p => {
            if (!map[p.student_id]) {
                map[p.student_id] = p.id;
            }
        });
    }
    return Object.values(map);
});

const getStatusBadge = (status) => {
    const colors = {
        'naik': 'bg-green-100 text-green-700',
        'tinggal': 'bg-amber-100 text-amber-700',
        'lulus': 'bg-blue-100 text-blue-700',
        'pindah': 'bg-purple-100 text-purple-700',
        'keluar': 'bg-red-100 text-red-700',
    };
    return colors[status] || 'bg-gray-100 text-gray-700';
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const rollbackForm = useForm({});
const rollbackingId = ref(null);

const rollback = (promotion) => {
    if (!confirm(`Batalkan promosi untuk ${promotion.student?.full_name}? Siswa akan dikembalikan ke kelas sebelumnya.`)) return;
    
    rollbackingId.value = promotion.id;
    rollbackForm.post(route('yayasan.promotion.rollback', promotion.id), {
        onSuccess: () => rollbackingId.value = null,
        onError: () => rollbackingId.value = null,
    });
};
</script>

<template>
    <Head title="Riwayat Kenaikan Kelas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-3">
                        <ClockIcon class="w-8 h-8 text-namira-teal" />
                        Riwayat Kenaikan Kelas
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Log promosi siswa antar tahun ajaran</p>
                </div>
                <a :href="route('yayasan.promotion.index')" class="px-4 py-2 bg-namira-teal text-white rounded-xl font-bold text-sm hover:bg-teal-700">
                    + Proses Baru
                </a>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-sm border p-4 mb-6 flex flex-wrap gap-4 items-center">
                <FunnelIcon class="w-5 h-5 text-gray-400" />
                <select v-model="selectedYear" class="h-10 px-4 border-gray-200 rounded-xl text-sm">
                    <option value="">Semua Tahun Ajaran</option>
                    <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.name }}</option>
                </select>
                <select v-model="selectedStatus" class="h-10 px-4 border-gray-200 rounded-xl text-sm">
                    <option value="">Semua Status</option>
                    <option v-for="(label, key) in statusOptions" :key="key" :value="key">{{ label }}</option>
                </select>
                <span class="text-sm text-gray-500">{{ promotions.total }} data</span>
                
                <a :href="route('yayasan.promotion.export', { academic_year_id: selectedYear || undefined, status: selectedStatus || undefined })" 
                   class="ml-auto flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-xl font-bold text-sm hover:bg-green-700 transition-colors">
                    <DocumentArrowDownIcon class="w-4 h-4" />
                    Export PDF
                </a>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 font-bold uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase">Siswa</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase">Dari Kelas</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase">Ke Kelas</th>
                                <th class="px-4 py-3 text-center text-xs font-bold uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase">Catatan</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase">Waktu</th>
                                <th class="px-4 py-3 text-right text-xs font-bold uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="p in promotions.data" :key="p.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-800">{{ p.student?.full_name }}</div>
                                    <div class="text-xs text-gray-400">{{ p.student?.nis }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div>{{ p.from_classroom?.name }}</div>
                                    <div class="text-xs text-gray-400">{{ p.from_academic_year?.name }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div>{{ p.to_classroom?.name || '-' }}</div>
                                    <div class="text-xs text-gray-400">{{ p.to_academic_year?.name }}</div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs font-bold" :class="getStatusBadge(p.status)">
                                        {{ statusOptions[p.status] || p.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 max-w-[200px] truncate">
                                    {{ p.notes || '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-xs text-gray-500">{{ formatDate(p.promoted_at) }}</div>
                                    <div class="text-xs text-gray-400">oleh {{ p.promoted_by?.name }}</div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div v-if="p.is_rolled_back" class="text-right">
                                        <span class="inline-block px-2 py-1 text-xs font-bold text-red-700 bg-red-100 rounded-full">Dibatalkan</span>
                                        <div class="text-[10px] text-gray-400 mt-1">oleh {{ p.rolled_back_by?.name }}</div>
                                        <div class="text-[10px] text-gray-400">{{ formatDate(p.rolled_back_at) }}</div>
                                    </div>
                                    <div v-else-if="!latestPromotionIds.includes(p.id)" class="text-right">
                                        <button 
                                            disabled
                                            class="px-2 py-1 text-xs font-bold text-gray-300 cursor-not-allowed flex items-center gap-1 ml-auto"
                                            title="Hanya promosi terbaru yang dapat dibatalkan"
                                        >
                                            <ArrowUturnLeftIcon class="w-3.5 h-3.5" />
                                            <span>Batalkan</span>
                                        </button>
                                    </div>
                                    <div v-else class="text-right">
                                        <button 
                                            @click="rollback(p)"
                                            :disabled="rollbackingId === p.id"
                                            class="px-2 py-1 text-xs font-bold text-amber-600 hover:bg-amber-50 rounded-lg transition-colors flex items-center gap-1 ml-auto"
                                            title="Batalkan promosi ini"
                                        >
                                            <ArrowUturnLeftIcon class="w-3.5 h-3.5" />
                                            <span v-if="rollbackingId !== p.id">Batalkan</span>
                                            <span v-else>...</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="promotions.data.length === 0">
                                <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                                    Belum ada riwayat kenaikan kelas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="promotions.last_page > 1" class="p-4 border-t flex justify-center gap-2">
                    <a 
                        v-for="link in promotions.links" 
                        :key="link.label"
                        :href="link.url"
                        class="px-3 py-1 rounded text-sm"
                        :class="link.active ? 'bg-namira-teal text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        v-html="link.label"
                    ></a>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
