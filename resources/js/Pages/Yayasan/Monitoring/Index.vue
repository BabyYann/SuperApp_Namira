<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import { 
    FunnelIcon, CheckCircleIcon, ClockIcon, PlusCircleIcon, XCircleIcon, 
    QuestionMarkCircleIcon, PresentationChartLineIcon, UserIcon, AcademicCapIcon, 
    ExclamationTriangleIcon, CalendarDaysIcon, CpuChipIcon, ShieldCheckIcon, ClipboardDocumentCheckIcon
} from '@heroicons/vue/24/outline';

dayjs.locale('id');

const props = defineProps({
    stats: Object,
    lateEmployees: Array,
    filters: Object,
    units: Array,
});

const unitFilter = ref(props.filters.unit_id ?? 'all');
const currentDate = ref(dayjs().format('dddd, D MMMM YYYY'));

const applyFilter = () => {
    router.get(route('yayasan.monitoring.index'), { 
        unit_id: unitFilter.value 
    }, { 
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const calcPercentage = (val, total) => {
    if (!total || total === 0) return 0;
    return Math.round((val / total) * 100);
}
</script>

<template>
    <Head title="Monitoring Center" />

    <AuthenticatedLayout>
        <div class="pb-12">

            <!-- ============================================================ -->
            <!-- 📱 MOBILE VERSION (EXCLUSIVE FOR HP VIEW - block md:hidden) -->
            <!-- ============================================================ -->
            <div class="block md:hidden space-y-5">
                
                <!-- 1. Executive Mobile Header Card -->
                <div class="rounded-3xl bg-gradient-to-br from-[#0f172a] via-[#1e3a5f] to-[#0f172a] p-5 text-white shadow-xl border border-blue-900/60 relative overflow-hidden">
                    <div class="absolute -right-8 -top-8 w-32 h-32 bg-blue-500/10 rounded-full blur-xl pointer-events-none"></div>

                    <div class="flex items-center justify-between mb-3">
                        <span class="px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-blue-500/20 text-blue-300 border border-blue-400/30 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                            Monitoring Center
                        </span>
                        <span class="text-[10px] font-bold text-slate-300 flex items-center gap-1">
                            <CalendarDaysIcon class="w-3.5 h-3.5 text-blue-300" />
                            {{ currentDate }}
                        </span>
                    </div>

                    <h1 class="text-xl font-black text-white tracking-tight leading-snug mb-1">
                        Ringkasan Operasional
                    </h1>
                    <p class="text-xs text-slate-300 font-medium mb-4">
                        Pemantauan presensi & aktivitas sekolah secara realtime.
                    </p>

                    <!-- Unit Selector Filter Bar -->
                    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-1.5 border border-white/15 flex items-center gap-2">
                        <div class="pl-2 text-blue-300">
                            <FunnelIcon class="w-4 h-4 stroke-[2.5]" />
                        </div>
                        <select 
                            v-model="unitFilter" 
                            @change="applyFilter"
                            class="bg-transparent border-0 text-white font-bold text-xs focus:ring-0 focus:outline-none w-full py-1.5 pr-3 cursor-pointer"
                        >
                            <option value="all" class="text-slate-900 font-bold">🌐 Semua Unit (Global)</option>
                            <option disabled class="text-slate-400">----------------</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id" class="text-slate-900 font-bold">
                                🏫 {{ unit.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- 2. Presensi Pegawai & Guru (Mobile Grid) -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between px-1">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                            Presensi Guru & Pegawai
                        </h3>
                        <span class="text-[10px] font-extrabold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-full border border-teal-100">
                            {{ calcPercentage(stats.employees.total_present, stats.employees.total_employees) }}% Hadir
                        </span>
                    </div>

                    <!-- Main Highlight Card: Total Hadir -->
                    <div class="bg-white rounded-3xl p-4 border border-slate-200/80 shadow-sm flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0">
                                <CheckCircleIcon class="w-6 h-6 stroke-[2.2]" />
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pegawai Hadir</span>
                                <div class="flex items-baseline gap-1 mt-0.5">
                                    <span class="text-2xl font-black text-slate-900">{{ stats.employees.total_present }}</span>
                                    <span class="text-xs font-bold text-slate-400">/ {{ stats.employees.total_employees }} Org</span>
                                </div>
                            </div>
                        </div>
                        <div class="w-16 bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="bg-emerald-500 h-full rounded-full" :style="{ width: calcPercentage(stats.employees.total_present, stats.employees.total_employees) + '%' }"></div>
                        </div>
                    </div>

                    <!-- 4 Sub Metrics Grid (2x2) -->
                    <div class="grid grid-cols-2 gap-2.5">
                        <!-- Terlambat -->
                        <div class="bg-white rounded-2xl p-3.5 border border-slate-200/80 shadow-xs flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center shrink-0">
                                <ClockIcon class="w-4 h-4 stroke-[2.2]" />
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold uppercase text-slate-400">Terlambat</span>
                                <span class="text-base font-black text-rose-600 leading-tight">{{ stats.employees.total_late }}</span>
                            </div>
                        </div>

                        <!-- Izin/Cuti -->
                        <div class="bg-white rounded-2xl p-3.5 border border-slate-200/80 shadow-xs flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center shrink-0">
                                <CalendarDaysIcon class="w-4 h-4 stroke-[2.2]" />
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold uppercase text-slate-400">Izin / Cuti</span>
                                <span class="text-base font-black text-amber-600 leading-tight">{{ stats.employees.total_permit }}</span>
                            </div>
                        </div>

                        <!-- Sakit -->
                        <div class="bg-white rounded-2xl p-3.5 border border-slate-200/80 shadow-xs flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0">
                                <PlusCircleIcon class="w-4 h-4 stroke-[2.2]" />
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold uppercase text-slate-400">Sakit</span>
                                <span class="text-base font-black text-blue-600 leading-tight">{{ stats.employees.total_sick }}</span>
                            </div>
                        </div>

                        <!-- Alpha -->
                        <div class="bg-white rounded-2xl p-3.5 border border-slate-200/80 shadow-xs flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 border border-red-100 flex items-center justify-center shrink-0">
                                <ExclamationTriangleIcon class="w-4 h-4 stroke-[2.2]" />
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold uppercase text-slate-400">Tanpa Kabar</span>
                                <span class="text-base font-black text-red-600 leading-tight">{{ stats.employees.total_alpha }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Presensi Siswa (Mobile Grid) -->
                <div class="space-y-2 pt-1">
                    <div class="flex items-center justify-between px-1">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            Kehadiran Siswa
                        </h3>
                        <span class="text-[10px] font-bold text-slate-400">Hari Ini</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <!-- Hadir Belajar -->
                        <div class="bg-white rounded-2xl p-3.5 border border-slate-200/80 shadow-xs flex items-center gap-2.5 col-span-2">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0">
                                <CheckCircleIcon class="w-5 h-5 stroke-[2.2]" />
                            </div>
                            <div class="flex-1">
                                <span class="block text-[9px] font-bold uppercase text-slate-400">Siswa Hadir Belajar</span>
                                <span class="text-lg font-black text-slate-900 leading-tight">{{ stats.students.present }} Siswa</span>
                            </div>
                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">Di Kelas</span>
                        </div>

                        <!-- Sakit -->
                        <div class="bg-white rounded-2xl p-3 border border-slate-200/80 shadow-xs flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0">
                                <PlusCircleIcon class="w-4 h-4" />
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold uppercase text-slate-400">Sakit</span>
                                <span class="text-sm font-black text-blue-600">{{ stats.students.sick }}</span>
                            </div>
                        </div>

                        <!-- Izin -->
                        <div class="bg-white rounded-2xl p-3 border border-slate-200/80 shadow-xs flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center shrink-0">
                                <ClockIcon class="w-4 h-4" />
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold uppercase text-slate-400">Izin OrtU</span>
                                <span class="text-sm font-black text-amber-600">{{ stats.students.permit }}</span>
                            </div>
                        </div>

                        <!-- Bolos / Alpha -->
                        <div class="bg-white rounded-2xl p-3 border border-slate-200/80 shadow-xs flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center shrink-0">
                                <XCircleIcon class="w-4 h-4" />
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold uppercase text-slate-400">Bolos / Alpha</span>
                                <span class="text-sm font-black text-rose-600">{{ stats.students.alpha }}</span>
                            </div>
                        </div>

                        <!-- Belum Absen -->
                        <div class="bg-white rounded-2xl p-3 border border-slate-200/80 shadow-xs flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 border border-slate-200 flex items-center justify-center shrink-0">
                                <QuestionMarkCircleIcon class="w-4 h-4" />
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold uppercase text-slate-400">Belum Diabsen</span>
                                <span class="text-sm font-black text-slate-600">{{ stats.students.unrecorded }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Laporan Jurnal Mengajar Guru (Academic Performance) -->
                <div class="bg-white rounded-3xl p-4 border border-slate-200/80 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[9px] font-bold uppercase tracking-wider text-teal-700">Akademik</span>
                            <h4 class="font-extrabold text-sm text-slate-900">Jurnal Mengajar Guru</h4>
                        </div>
                        <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-700 border border-teal-100 flex items-center justify-center">
                            <PresentationChartLineIcon class="w-5 h-5" />
                        </div>
                    </div>

                    <div class="flex items-baseline justify-between pt-1">
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-black text-slate-900">{{ stats.academic.journals_today }}</span>
                            <span class="text-xs font-bold text-slate-400">/ {{ stats.academic.schedules_today }} Jadwal</span>
                        </div>
                        <span class="text-xs font-black text-teal-700 bg-teal-50 px-2.5 py-1 rounded-full border border-teal-100">
                            {{ calcPercentage(stats.academic.journals_today, stats.academic.schedules_today) }}% Terisi
                        </span>
                    </div>

                    <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                        <div 
                            class="bg-teal-600 h-full rounded-full transition-all duration-700"
                            :style="{ width: calcPercentage(stats.academic.journals_today, stats.academic.schedules_today) + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- 5. Top 5 Keterlambatan Pegawai (Mobile Card List) -->
                <div class="bg-white rounded-3xl p-4 border border-slate-200/80 shadow-sm space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <h4 class="font-extrabold text-xs uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <span>Terlambat Hari Ini (Top 5)</span>
                        </h4>
                        <span class="text-[10px] font-bold text-slate-400">{{ lateEmployees.length }} Pegawai</span>
                    </div>

                    <div v-if="lateEmployees.length === 0" class="text-center py-4 text-xs font-bold text-slate-400 italic">
                        🎉 Tidak ada keterlambatan hari ini
                    </div>

                    <div v-else class="space-y-2">
                        <div 
                            v-for="emp in lateEmployees" 
                            :key="emp.name"
                            class="p-2.5 rounded-2xl bg-slate-50/80 border border-slate-150 flex items-center justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-slate-200 overflow-hidden ring-2 ring-white shadow-xs shrink-0">
                                    <img v-if="emp.photo" :src="'/storage/' + emp.photo" class="w-full h-full object-cover">
                                    <div v-else class="w-full h-full flex items-center justify-center text-xs font-black text-slate-500 bg-teal-100">
                                        {{ emp.name.charAt(0) }}
                                    </div>
                                </div>
                                <div>
                                    <div class="font-extrabold text-xs text-slate-800 truncate max-w-[150px]">{{ emp.name }}</div>
                                    <div class="text-[10px] font-bold text-slate-400">Guru / Staff</div>
                                </div>
                            </div>
                            <span class="text-xs font-black text-rose-600 bg-rose-50 px-2.5 py-1 rounded-full border border-rose-100">
                                ⏱️ {{ emp.time }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 6. Demografi & Status Sistem -->
                <div class="grid grid-cols-2 gap-2.5">
                    <div class="bg-white rounded-2xl p-3 border border-slate-200/80 shadow-xs flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center shrink-0">
                            <UserIcon class="w-4 h-4" />
                        </div>
                        <div>
                            <span class="block text-[9px] font-bold uppercase text-slate-400">Total Siswa</span>
                            <span class="text-sm font-black text-slate-900">{{ stats.users.students }}</span>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-3 border border-slate-200/80 shadow-xs flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center shrink-0">
                            <AcademicCapIcon class="w-4 h-4" />
                        </div>
                        <div>
                            <span class="block text-[9px] font-bold uppercase text-slate-400">Total Guru</span>
                            <span class="text-sm font-black text-slate-900">{{ stats.users.teachers }}</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ============================================================ -->
            <!-- 💻 WEB DESKTOP VERSION (UNTOUCHED PRESERVED - hidden md:block) -->
            <!-- ============================================================ -->
            <div class="hidden md:block space-y-8">
                
                <!-- Header Section -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-gray-100 pb-6">
                    <div>
                        <div class="text-xs font-bold text-namira-teal uppercase tracking-wider mb-1">
                            Monitoring Center
                        </div>
                        <h1 class="text-3xl font-extrabold text-gray-900 leading-tight">
                            Ringkasan Aktivitas
                        </h1>
                        <div class="flex items-center gap-1.5 text-gray-500 mt-1.5 text-sm font-medium">
                            <CalendarDaysIcon class="w-4 h-4 text-gray-400" />
                            <span>{{ currentDate }}</span>
                        </div>
                    </div>

                    <!-- Unit Filter -->
                    <div class="w-full md:w-64">
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Filter Unit</label>
                        <div class="relative">
                            <select 
                                v-model="unitFilter" 
                                @change="applyFilter"
                                class="appearance-none w-full pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-700 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md cursor-pointer"
                            >
                                <option value="all">Semua Unit (Global)</option>
                                <option disabled>----------------</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                    {{ unit.name }}
                                </option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-gray-500">
                                <FunnelIcon class="w-4 h-4" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 1. Kehadiran Guru & Karyawan -->
                <section>
                    <div class="flex items-center gap-3 mb-5">
                        <div class="h-6 w-1.5 bg-namira-teal rounded-full"></div>
                        <h3 class="text-lg font-bold text-gray-800">Kehadiran Guru & Karyawan</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- Hadir -->
                        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between h-32 relative">
                            <div class="absolute right-4 top-4 text-emerald-600">
                                <CheckCircleIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Hadir</div>
                                <div class="flex items-baseline gap-1 mt-2">
                                    <span class="text-3xl font-black text-gray-900">{{ stats.employees.total_present }}</span>
                                    <span class="text-xs font-bold text-gray-400">/ {{ stats.employees.total_employees }}</span>
                                </div>
                            </div>
                            <div class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md w-fit">
                                {{ calcPercentage(stats.employees.total_present, stats.employees.total_employees) }}% Terisi
                            </div>
                        </div>

                        <!-- Terlambat -->
                        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between h-32 relative">
                            <div class="absolute right-4 top-4 text-rose-600">
                                <ClockIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Terlambat</div>
                                <div class="text-3xl font-black text-rose-600 mt-2">{{ stats.employees.total_late }}</div>
                            </div>
                            <div class="text-[10px] font-bold text-gray-400">Jam Masuk</div>
                        </div>

                        <!-- Izin/Cuti -->
                        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between h-32 relative">
                            <div class="absolute right-4 top-4 text-amber-600">
                                <CalendarDaysIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Izin / Cuti</div>
                                <div class="text-3xl font-black text-amber-600 mt-2">{{ stats.employees.total_permit }}</div>
                            </div>
                            <div class="text-[10px] font-bold text-gray-400">Dengan Dokumen</div>
                        </div>

                        <!-- Sakit -->
                        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between h-32 relative">
                            <div class="absolute right-4 top-4 text-blue-600">
                                <PlusCircleIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Sakit</div>
                                <div class="text-3xl font-black text-blue-600 mt-2">{{ stats.employees.total_sick }}</div>
                            </div>
                            <div class="text-[10px] font-bold text-gray-400">Surat Dokter</div>
                        </div>

                        <!-- Alpha -->
                        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between h-32 relative">
                            <div class="absolute right-4 top-4 text-red-500">
                                <ExclamationTriangleIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Tanpa Kabar</div>
                                <div class="text-3xl font-black text-red-600 mt-2">{{ stats.employees.total_alpha }}</div>
                            </div>
                            <div class="text-[10px] font-bold text-gray-400">Belum Check-In</div>
                        </div>
                    </div>
                </section>

                <!-- 2. Kehadiran Siswa -->
                <section>
                    <div class="flex items-center gap-3 mb-5">
                        <div class="h-6 w-1.5 bg-indigo-500 rounded-full"></div>
                        <h3 class="text-lg font-bold text-gray-800">Kehadiran Siswa</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- Hadir -->
                        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between h-32 relative">
                            <div class="absolute right-4 top-4 text-emerald-600">
                                <CheckCircleIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Hadir Belajar</div>
                                <div class="text-3xl font-black text-gray-900 mt-2">{{ stats.students.present }}</div>
                            </div>
                            <div class="text-[10px] font-bold text-gray-400">Di Kelas</div>
                        </div>

                        <!-- Sakit -->
                        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between h-32 relative">
                            <div class="absolute right-4 top-4 text-blue-600">
                                <PlusCircleIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Sakit</div>
                                <div class="text-3xl font-black text-blue-600 mt-2">{{ stats.students.sick }}</div>
                            </div>
                            <div class="text-[10px] font-bold text-gray-400">Surat Keterangan</div>
                        </div>

                        <!-- Izin -->
                        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between h-32 relative">
                            <div class="absolute right-4 top-4 text-amber-600">
                                <ClockIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Izin</div>
                                <div class="text-3xl font-black text-amber-600 mt-2">{{ stats.students.permit }}</div>
                            </div>
                            <div class="text-[10px] font-bold text-gray-400">Pemberitahuan Orang Tua</div>
                        </div>

                        <!-- Alpha -->
                        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between h-32 relative">
                            <div class="absolute right-4 top-4 text-rose-600">
                                <XCircleIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Bolos / Alpha</div>
                                <div class="text-3xl font-black text-rose-600 mt-2">{{ stats.students.alpha }}</div>
                            </div>
                            <div class="text-[10px] font-bold text-gray-400">Tidak Ada Keterangan</div>
                        </div>

                        <!-- Unrecorded -->
                        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between h-32 relative">
                            <div class="absolute right-4 top-4 text-gray-400">
                                <QuestionMarkCircleIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Belum Absen</div>
                                <div class="text-3xl font-black text-gray-500 mt-2">{{ stats.students.unrecorded }}</div>
                            </div>
                            <div class="text-[10px] font-bold text-gray-400">Menunggu Input Guru</div>
                        </div>
                    </div>
                </section>

                <!-- 3. Academic & Users & Late List -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Col 1: Academic Performance & Late Table -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Academic Card -->
                        <div class="bg-white rounded-3xl border border-gray-150 p-8 shadow-sm flex flex-col md:flex-row gap-8 items-center relative overflow-hidden">
                            <div class="flex-1">
                                <h4 class="text-namira-teal font-bold uppercase tracking-wider text-xs mb-1">Performa Akademik</h4>
                                <h3 class="text-2xl font-extrabold text-gray-800 mb-1">Laporan Mengajar</h3>
                                <p class="text-gray-500 text-sm mb-6">Realisasi pengisian jurnal harian oleh guru.</p>
                                
                                <div class="flex items-baseline gap-1.5 mb-3">
                                    <span class="text-4xl font-black text-gray-900">{{ stats.academic.journals_today }}</span>
                                    <span class="text-sm font-bold text-gray-400">/ {{ stats.academic.schedules_today }} Jadwal</span>
                                </div>
                                
                                <!-- Progress Bar -->
                                <div class="w-full bg-gray-100 rounded-full h-2.5">
                                    <div 
                                        class="bg-namira-teal h-2.5 rounded-full transition-all duration-1000 ease-out"
                                        :style="{ width: calcPercentage(stats.academic.journals_today, stats.academic.schedules_today) + '%' }"
                                    ></div>
                                </div>
                                <div class="mt-2 text-right text-xs font-bold text-gray-500">
                                    {{ calcPercentage(stats.academic.journals_today, stats.academic.schedules_today) }}% Terisi
                                </div>
                            </div>
                            
                            <!-- Icon Badge -->
                            <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100 hidden md:block">
                                <PresentationChartLineIcon class="w-12 h-12 text-namira-teal" />
                            </div>
                        </div>

                        <!-- Late Employees Table -->
                        <div class="bg-white rounded-3xl border border-gray-150 p-6 shadow-sm">
                            <div class="flex items-center justify-between mb-6">
                                <h4 class="font-bold text-gray-800 flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                    Terlambat Hari Ini (Top 5)
                                </h4>
                                <span v-if="lateEmployees.length === 0" class="text-xs text-gray-400 italic">Tidak ada keterlambatan hari ini</span>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="text-xs text-gray-400 font-bold uppercase tracking-wider">
                                        <tr class="border-b border-gray-100">
                                            <th class="pb-3 pl-2">Nama Karyawan</th>
                                            <th class="pb-3 text-right pr-2">Waktu Check-In</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="emp in lateEmployees" :key="emp.name" class="group hover:bg-gray-50/50 transition-colors">
                                            <td class="py-3.5 px-2 flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-xl bg-gray-100 overflow-hidden shadow-sm border border-white">
                                                    <img v-if="emp.photo" :src="'/storage/' + emp.photo" class="h-full w-full object-cover">
                                                    <div v-else class="h-full w-full flex items-center justify-center text-xs font-black text-gray-400 bg-gray-100">{{ emp.name.charAt(0) }}</div>
                                                </div>
                                                <div>
                                                    <div class="font-bold text-gray-800">{{ emp.name }}</div>
                                                    <div class="text-[10px] text-gray-400">Guru / Staf</div>
                                                </div>
                                            </td>
                                            <td class="py-3.5 text-right font-bold text-rose-600 pr-2 tabular-nums">
                                                {{ emp.time }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Col 2: Demographics & Status -->
                    <div class="space-y-6">
                        <!-- User Demographics -->
                        <div class="bg-white rounded-3xl border border-gray-150 p-6 shadow-sm">
                            <h4 class="font-bold text-gray-800 mb-5">Demografi User</h4>
                            
                            <div class="space-y-4">
                                <!-- Students -->
                                <div class="p-4 rounded-2xl bg-gray-50 border border-gray-150 flex items-center justify-between">
                                    <div>
                                        <div class="text-xs font-bold text-gray-400 uppercase mb-1">Total Siswa</div>
                                        <div class="text-2xl font-black text-gray-900">{{ stats.users.students }}</div>
                                    </div>
                                    <div class="bg-white p-2.5 rounded-xl text-namira-teal border border-gray-150 shadow-sm">
                                        <UserIcon class="w-5 h-5" />
                                    </div>
                                </div>

                                <!-- Teachers -->
                                <div class="p-4 rounded-2xl bg-gray-50 border border-gray-150 flex items-center justify-between">
                                    <div>
                                        <div class="text-xs font-bold text-gray-400 uppercase mb-1">Total Guru</div>
                                        <div class="text-2xl font-black text-gray-900">{{ stats.users.teachers }}</div>
                                    </div>
                                    <div class="bg-white p-2.5 rounded-xl text-namira-teal border border-gray-150 shadow-sm">
                                        <AcademicCapIcon class="w-5 h-5" />
                                    </div>
                                </div>
                            </div>

                            <!-- Pending Users Alert -->
                            <div v-if="stats.users.pending > 0" class="mt-6 p-4 bg-amber-50 rounded-2xl border border-amber-100 flex items-start gap-3">
                                <div class="bg-white p-2 rounded-xl text-amber-600 border border-amber-100 shadow-sm flex-shrink-0">
                                    <ExclamationTriangleIcon class="w-5 h-5" />
                                </div>
                                <div>
                                    <div class="font-bold text-amber-800 text-sm">{{ stats.users.pending }} Karyawan Pending</div>
                                    <div class="text-xs text-amber-600/95 font-medium mt-0.5">Akun belum melakukan verifikasi email.</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Status Card -->
                        <div class="bg-white rounded-3xl border border-gray-150 p-6 shadow-sm flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-gray-400 text-xs uppercase tracking-wider mb-4">Status Sistem</h4>
                                <div class="flex items-center gap-2">
                                    <span class="relative flex h-3 w-3">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                                    </span>
                                    <span class="text-lg font-extrabold text-gray-800">Operational</span>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                                <span>Koneksi WebSocket</span>
                                <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 font-bold rounded-full border border-emerald-100">
                                    Connected
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
