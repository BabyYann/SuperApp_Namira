<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { 
    ChartBarIcon, ArrowDownTrayIcon, AcademicCapIcon,
    CheckCircleIcon, ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    classrooms: Array,
    recapData: Array,
    dates: Array,
    selectedClassroom: Object,
    filters: Object,
    monthName: String,
    daysInMonth: Number,
});

const selectedClassroom = ref(props.filters.classroom_id || '');
const selectedMonth = ref(props.filters.month || new Date().getMonth() + 1);
const selectedYear = ref(props.filters.year || new Date().getFullYear());

const months = [
    { value: 1, label: 'Januari' }, { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' }, { value: 4, label: 'April' },
    { value: 5, label: 'Mei' }, { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' }, { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' }, { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' }, { value: 12, label: 'Desember' },
];

const applyFilter = () => {
    if (!selectedClassroom.value) return;
    router.get(route('yayasan.student-attendance.recap'), {
        classroom_id: selectedClassroom.value,
        month: selectedMonth.value,
        year: selectedYear.value,
    }, { preserveState: true, preserveScroll: true });
};

// Realtime filter - watch all filter changes
watch([selectedClassroom, selectedMonth, selectedYear], () => {
    applyFilter();
});

const exportRecap = () => {
    window.location.href = route('yayasan.student-attendance.export', {
        classroom_id: selectedClassroom.value,
        month: selectedMonth.value,
        year: selectedYear.value,
    });
};

const getStatusColor = (percentage) => {
    if (percentage >= 90) return 'text-green-600';
    if (percentage >= 75) return 'text-amber-600';
    return 'text-red-600';
};

const getStatusBg = (status) => {
    if (status === 'H') return 'bg-green-500 text-white';
    if (status === 'S') return 'bg-blue-400 text-white';
    if (status === 'I') return 'bg-amber-400 text-white';
    if (status === 'A') return 'bg-red-500 text-white';
    return 'bg-gray-100 text-gray-300';
};

const getStatusLabel = (status) => {
    if (status === 'H') return 'H';
    if (status === 'S') return 'S';
    if (status === 'I') return 'I';
    if (status === 'A') return 'A';
    return '-';
};
</script>

<template>
    <Head title="Rekap Kehadiran Bulanan" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                    Rekap Kehadiran Bulanan
                </h2>
                <p class="text-sm text-gray-500 mt-1">Ringkasan kehadiran siswa per bulan dengan detail harian</p>
            </div>
        </template>

        <div class="py-6 max-w-full mx-auto pb-20 space-y-6 px-4">
            <!-- Filter -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="text-xs font-bold text-gray-500 mb-1 block">Kelas</label>
                        <select v-model="selectedClassroom" class="w-full border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-sm h-12">
                            <option value="">Pilih Kelas</option>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 mb-1 block">Bulan</label>
                        <select v-model="selectedMonth" class="w-full border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-sm h-12">
                            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 mb-1 block">Tahun</label>
                        <select v-model="selectedYear" class="w-full border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-sm h-12">
                            <option v-for="y in [2024, 2025, 2026]" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <span v-if="selectedClassroom" class="text-sm text-gray-500 italic">Otomatis update</span>
                        <button v-if="recapData.length > 0" @click="exportRecap" class="px-4 py-3 bg-green-500 text-white rounded-xl font-bold hover:bg-green-600 transition-colors flex items-center gap-2">
                            <ArrowDownTrayIcon class="w-5 h-5" />
                            <span class="hidden md:inline">Export</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!selectedClassroom" class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 p-12 text-center">
                <AcademicCapIcon class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                <p class="text-gray-400 font-bold">Pilih kelas untuk melihat rekap kehadiran</p>
            </div>

            <!-- Recap Table with Daily View -->
            <div v-else-if="recapData.length > 0" class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <ChartBarIcon class="w-5 h-5 text-namira-teal" />
                        {{ props.selectedClassroom?.name }} - {{ monthName }} {{ selectedYear }}
                    </h3>
                    <div class="flex items-center gap-4 text-xs">
                        <span class="flex items-center gap-1"><span class="w-4 h-4 rounded bg-green-500"></span> Hadir</span>
                        <span class="flex items-center gap-1"><span class="w-4 h-4 rounded bg-blue-400"></span> Sakit</span>
                        <span class="flex items-center gap-1"><span class="w-4 h-4 rounded bg-amber-400"></span> Izin</span>
                        <span class="flex items-center gap-1"><span class="w-4 h-4 rounded bg-red-500"></span> Alpha</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-3 py-2 text-left font-bold text-gray-600 sticky left-0 bg-gray-50 z-10 min-w-[180px]">Nama</th>
                                <th v-for="d in dates" :key="d.day" 
                                    class="px-1 py-2 text-center font-bold min-w-[32px]"
                                    :class="d.isHoliday ? 'text-red-400 bg-red-50' : 'text-gray-600'">
                                    <div class="text-[10px]">{{ d.dayName }}</div>
                                    <div>{{ d.day }}</div>
                                </th>
                                <th class="px-2 py-2 text-center font-bold text-green-600 min-w-[40px]">H</th>
                                <th class="px-2 py-2 text-center font-bold text-blue-600 min-w-[40px]">S</th>
                                <th class="px-2 py-2 text-center font-bold text-amber-600 min-w-[40px]">I</th>
                                <th class="px-2 py-2 text-center font-bold text-red-600 min-w-[40px]">A</th>
                                <th class="px-2 py-2 text-center font-bold text-gray-600 min-w-[50px]">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, i) in recapData" :key="row.student.id" class="border-b border-gray-50 hover:bg-gray-50/50">
                                <td class="px-3 py-2 font-medium text-gray-800 sticky left-0 bg-white z-10">
                                    <div class="truncate max-w-[170px]" :title="row.student.full_name">{{ row.student.full_name }}</div>
                                </td>
                                <td v-for="d in dates" :key="d.day" class="px-0.5 py-1 text-center" :class="d.isHoliday ? 'bg-red-50/50' : ''">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded text-[10px] font-bold" :class="getStatusBg(row.daily[d.day])">
                                        {{ getStatusLabel(row.daily[d.day]) }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-center font-bold text-green-700">{{ row.summary.H }}</td>
                                <td class="px-2 py-2 text-center font-bold text-blue-700">{{ row.summary.S }}</td>
                                <td class="px-2 py-2 text-center font-bold text-amber-700">{{ row.summary.I }}</td>
                                <td class="px-2 py-2 text-center font-bold text-red-700">{{ row.summary.A }}</td>
                                <td class="px-2 py-2 text-center">
                                    <span :class="[getStatusColor(row.percentage), 'font-bold']">{{ row.percentage }}%</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- No Data -->
            <div v-else class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 p-12 text-center">
                <ExclamationTriangleIcon class="w-16 h-16 mx-auto text-amber-400 mb-4" />
                <p class="text-gray-500 font-bold">Belum ada data kehadiran untuk periode ini</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
