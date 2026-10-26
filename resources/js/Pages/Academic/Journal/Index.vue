<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { 
    CalendarIcon, 
    CheckCircleIcon, 
    ExclamationCircleIcon, 
    BellAlertIcon, 
    FunnelIcon, 
    MagnifyingGlassIcon,
    ArrowDownTrayIcon,
    BuildingOfficeIcon,
    UserCircleIcon,
    EyeIcon,
    AcademicCapIcon,
    ClockIcon,
    XMarkIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    CalendarDaysIcon,
    BookOpenIcon,
    ChartBarIcon,
    DocumentTextIcon,
    LightBulbIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const props = defineProps({
    schedules: Array,
    date: String,
    stats: Object,
    monthlyStats: Object,
    viewMode: String,
    hasAdminRole: Boolean,
    isGlobalAdmin: Boolean,
    isTeacher: Boolean,
    units: Array,
    selectedUnitId: [Number, String],
    filters: Object,
});

const selectedDate = ref(props.date);
const selectedUnit = ref(props.selectedUnitId || 'all');
const activeStatusFilter = ref(props.filters?.status || 'all');
const searchQuery = ref('');
const sendingReminderId = ref(null);
const previewPhotoUrl = ref(null);

// Watchers for filtering
watch([selectedDate, selectedUnit, activeStatusFilter], ([newDate, newUnit, newStatus]) => {
    router.get(route('yayasan.teaching-journal.index'), { 
        date: newDate,
        unit_id: newUnit,
        status: newStatus,
        view: props.viewMode,
    }, { 
        preserveState: true, 
        preserveScroll: true 
    });
});

const switchView = (mode) => {
    router.get(route('yayasan.teaching-journal.index'), {
        date: selectedDate.value,
        unit_id: selectedUnit.value,
        status: activeStatusFilter.value,
        view: mode,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filteredSchedules = computed(() => {
    if (!props.schedules) return [];
    let list = props.schedules;

    if (searchQuery.value.trim() !== '') {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(item => 
            (item.classroom && item.classroom.toLowerCase().includes(q)) ||
            (item.subject && item.subject.toLowerCase().includes(q)) ||
            (item.teacher_name && item.teacher_name.toLowerCase().includes(q)) ||
            (item.unit_name && item.unit_name.toLowerCase().includes(q))
        );
    }
    return list;
});

const todayDateString = new Date().toISOString().split('T')[0];

const goToToday = () => {
    selectedDate.value = todayDateString;
};

const changeDateByDays = (days) => {
    const cur = new Date(selectedDate.value);
    cur.setDate(cur.getDate() + days);
    const y = cur.getFullYear();
    const m = String(cur.getMonth() + 1).padStart(2, '0');
    const d = String(cur.getDate()).padStart(2, '0');
    selectedDate.value = `${y}-${m}-${d}`;
};

const formatDate = (dateString) => {
    try {
        if (!dateString) return '';
        const parts = dateString.split('-');
        if (parts.length === 3) {
            const date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
            const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const monthNames = [
                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];
            const dayName = dayNames[date.getDay()];
            const monthName = monthNames[date.getMonth()];
            const paddedDay = String(parts[2]).padStart(2, '0');
            return `${dayName}, ${paddedDay} ${monthName} ${parts[0]}`;
        }
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('id-ID', { dateStyle: 'full' }).format(date);
    } catch (e) {
        return dateString;
    }
};

const getHijriDate = (dateString) => {
    try {
        if (!dateString) return '28 Rabiulakhir 1448 H';
        const parts = dateString.split('-');
        const d = parts.length === 3 
            ? new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2])) 
            : new Date(dateString);
        return new Intl.DateTimeFormat('id-ID-u-ca-islamic-umalqura', { 
            day: 'numeric', 
            month: 'long', 
            year: 'numeric' 
        }).format(d);
    } catch (e) {
        return '28 Rabiulakhir 1448 H';
    }
};

const getMonthYear = (dateString) => {
    try {
        if (!dateString) return 'Oktober 2026';
        const parts = dateString.split('-');
        if (parts.length === 3) {
            const monthNames = [
                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];
            return `${monthNames[Number(parts[1]) - 1]} ${parts[0]}`;
        }
        const d = new Date(dateString);
        return new Intl.DateTimeFormat('id-ID', { 
            month: 'long', 
            year: 'numeric' 
        }).format(d);
    } catch (e) {
        return 'Oktober 2026';
    }
};

// Send Reminder Function
const sendReminder = (schedule) => {
    Swal.fire({
        title: 'Kirim Pengingat?',
        text: `Kirimkan push notifikasi ke HP Guru (${schedule.teacher_name}) untuk mengisi jurnal ${schedule.subject} di kelas ${schedule.classroom}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Kirim Notifikasi',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#009688',
    }).then((result) => {
        if (result.isConfirmed) {
            sendingReminderId.value = schedule.id;
            router.post(route('yayasan.teaching-journal.send-reminder'), {
                schedule_id: schedule.id,
                date: props.date,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    sendingReminderId.value = null;
                    Swal.fire({
                        icon: 'success',
                        title: 'Notifikasi Terkirim! 🚀',
                        text: `Pengingat berhasil dikirim ke HP & akun ${schedule.teacher_name}.`,
                        confirmButtonColor: '#009688',
                    });
                },
                onError: (errors) => {
                    sendingReminderId.value = null;
                    const errorMsg = Object.values(errors).flat().join('<br>') || 'Gagal mengirim pengingat.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        html: errorMsg,
                        confirmButtonColor: '#009688',
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Jurnal Mengajar" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="font-extrabold text-2xl bg-gradient-to-r from-slate-900 via-teal-900 to-slate-800 bg-clip-text text-transparent leading-tight">
                        {{ viewMode === 'monitoring' ? 'Monitoring Jurnal Mengajar' : 'Jurnal Mengajar Saya' }}
                    </h2>
                    <p class="text-xs md:text-sm text-slate-500 font-medium mt-0.5">
                        {{ viewMode === 'monitoring' 
                            ? 'Pantau keterisian jurnal, kepatuhan KBM, dan dokumentasi pembelajaran guru secara real-time.' 
                            : 'Catat aktivitas mengajar, presensi siswa, dan dokumentasi materi harian Anda.' }}
                    </p>
                </div>

                <!-- Dual View Mode Switcher for Admin-Teachers -->
                <div v-if="hasAdminRole && isTeacher" class="flex bg-slate-100 p-1 rounded-2xl border border-slate-200 self-start md:self-auto">
                    <button 
                        @click="switchView('monitoring')" 
                        :class="viewMode === 'monitoring' ? 'bg-white text-teal-800 shadow-sm font-extrabold' : 'text-slate-600 font-bold hover:text-slate-900'"
                        class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-1.5"
                    >
                        <AcademicCapIcon class="w-4 h-4 text-teal-600" />
                        <span>Monitoring KBM</span>
                    </button>
                    <button 
                        @click="switchView('my')" 
                        :class="viewMode === 'my' ? 'bg-white text-teal-800 shadow-sm font-extrabold' : 'text-slate-600 font-bold hover:text-slate-900'"
                        class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-1.5"
                    >
                        <ClockIcon class="w-4 h-4 text-teal-600" />
                        <span>Jadwal Saya</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-2 md:py-6 max-w-7xl mx-auto space-y-3 md:space-y-6">
            
            <!-- 📱 MOBILE COMPACT TOP BAR + VIEW SWITCHER (md:hidden) -->
            <div class="flex md:hidden items-center justify-between gap-2 bg-white px-3 py-2.5 rounded-xl border border-slate-200 shadow-2xs">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center shrink-0 border border-teal-200">
                        <BookOpenIcon class="w-4 h-4 stroke-[2.2]" />
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-xs font-black text-slate-900 tracking-tight truncate">
                            {{ viewMode === 'monitoring' ? 'Monitoring Jurnal KBM' : 'Jurnal Mengajar Saya' }}
                        </h1>
                        <p class="text-[10px] text-slate-400 truncate leading-none mt-0.5">
                            {{ formatDate(date) }}
                        </p>
                    </div>
                </div>

                <!-- Switcher Monitoring vs Jadwal Saya (Jika Admin + Guru) atau Tombol Rekap -->
                <div v-if="hasAdminRole && isTeacher" class="flex bg-slate-100 p-0.5 rounded-lg border border-slate-200 shrink-0">
                    <button 
                        @click="switchView('monitoring')" 
                        :class="viewMode === 'monitoring' ? 'bg-white text-teal-800 shadow-2xs font-extrabold' : 'text-slate-500 font-bold'"
                        class="px-2 py-1 rounded-md text-[10px] transition-all"
                    >
                        Monitor
                    </button>
                    <button 
                        @click="switchView('my')" 
                        :class="viewMode === 'my' ? 'bg-white text-teal-800 shadow-2xs font-extrabold' : 'text-slate-500 font-bold'"
                        class="px-2 py-1 rounded-md text-[10px] transition-all"
                    >
                        Saya
                    </button>
                </div>
                <a 
                    v-else-if="viewMode === 'monitoring'"
                    :href="route('yayasan.teaching-journal.export', { month: new Date(date).getMonth() + 1, year: new Date(date).getFullYear() })" 
                    class="shrink-0 px-2.5 py-1.5 bg-slate-900 text-white rounded-lg text-[10px] font-extrabold flex items-center gap-1 shadow-2xs"
                >
                    <ArrowDownTrayIcon class="w-3.5 h-3.5" />
                    <span>Rekap</span>
                </a>
            </div>

            <!-- ======================================================== -->
            <!-- 1. EXECUTIVE MONITORING VIEW (Super Admin, Kepsek, Pengawas) -->
            <!-- ======================================================== -->
            <template v-if="viewMode === 'monitoring'">
                
                <!-- 📱 A1. MOBILE ULTRA-COMPACT STATS STRIP (4 Kolom Klik-Filter dalam 1 Baris, md:hidden) -->
                <div class="grid md:hidden grid-cols-4 gap-1.5 bg-white p-1.5 rounded-xl border border-slate-200 shadow-2xs text-center">
                    <button 
                        type="button"
                        @click="activeStatusFilter = 'all'"
                        class="py-1.5 px-1 rounded-lg transition"
                        :class="activeStatusFilter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-50 text-slate-700'"
                    >
                        <span class="text-sm font-black block leading-none">{{ stats?.total || 0 }}</span>
                        <span class="text-[8px] font-bold uppercase tracking-tighter block mt-0.5 opacity-80">Total Sesi</span>
                    </button>

                    <button 
                        type="button"
                        @click="activeStatusFilter = activeStatusFilter === 'filled' ? 'all' : 'filled'"
                        class="py-1.5 px-1 rounded-lg transition"
                        :class="activeStatusFilter === 'filled' ? 'bg-emerald-600 text-white' : 'bg-emerald-50/70 text-emerald-700'"
                    >
                        <span class="text-sm font-black block leading-none">{{ stats?.filled || 0 }}</span>
                        <span class="text-[8px] font-bold uppercase tracking-tighter block mt-0.5">Terisi</span>
                    </button>

                    <button 
                        type="button"
                        @click="activeStatusFilter = activeStatusFilter === 'unfilled' ? 'all' : 'unfilled'"
                        class="py-1.5 px-1 rounded-lg transition"
                        :class="activeStatusFilter === 'unfilled' ? 'bg-rose-600 text-white' : 'bg-rose-50/70 text-rose-700'"
                    >
                        <span class="text-sm font-black block leading-none">{{ stats?.unfilled || 0 }}</span>
                        <span class="text-[8px] font-bold uppercase tracking-tighter block mt-0.5">Belum</span>
                    </button>

                    <div class="py-1.5 px-1 rounded-lg bg-gradient-to-br from-slate-900 to-teal-950 text-white flex flex-col justify-center">
                        <span class="text-sm font-black block leading-none text-teal-300">{{ stats?.compliance_rate || 0 }}%</span>
                        <span class="text-[8px] font-bold uppercase tracking-tighter block mt-0.5 text-slate-300">Patuh</span>
                    </div>
                </div>

                <!-- 🖥️ A2. DESKTOP STATS RIBBON (hidden md:grid) -->
                <div class="hidden md:grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                    <!-- Total Sesi -->
                    <div class="bg-white p-4 md:p-5 rounded-3xl border border-slate-100 shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] md:text-xs font-extrabold text-slate-500 uppercase tracking-wider">Total Jadwal KBM</span>
                            <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                                <CalendarIcon class="w-4 h-4" />
                            </div>
                        </div>
                        <p class="text-2xl md:text-3xl font-black text-slate-900">{{ stats?.total || 0 }}</p>
                        <p class="text-[10px] md:text-xs text-slate-400 font-bold mt-1">Sesi pembelajaran hari ini</p>
                    </div>

                    <!-- Sudah Diisi (Green) -->
                    <div class="bg-white p-4 md:p-5 rounded-3xl border border-emerald-100 shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] md:text-xs font-extrabold text-emerald-700 uppercase tracking-wider">Jurnal Terisi</span>
                            <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                <CheckCircleIcon class="w-4 h-4 stroke-[2.5]" />
                            </div>
                        </div>
                        <p class="text-2xl md:text-3xl font-black text-emerald-700">{{ stats?.filled || 0 }}</p>
                        <p class="text-[10px] md:text-xs text-emerald-600 font-bold mt-1">Telah dilaporkan guru</p>
                    </div>

                    <!-- Belum Diisi (Rose) -->
                    <div class="bg-white p-4 md:p-5 rounded-3xl border border-rose-100 shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] md:text-xs font-extrabold text-rose-700 uppercase tracking-wider">Belum Terisi</span>
                            <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center">
                                <ExclamationCircleIcon class="w-4 h-4 stroke-[2.5]" />
                            </div>
                        </div>
                        <p class="text-2xl md:text-3xl font-black text-rose-600">{{ stats?.unfilled || 0 }}</p>
                        <p class="text-[10px] md:text-xs text-rose-500 font-bold mt-1">Menunggu pengisian guru</p>
                    </div>

                    <!-- Kepatuhan Disiplin (Progress Bar) -->
                    <div class="bg-gradient-to-br from-slate-900 to-teal-950 text-white p-4 md:p-5 rounded-3xl border border-teal-800 shadow-md">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[11px] md:text-xs font-extrabold text-teal-400 uppercase tracking-wider">Tingkat Kepatuhan</span>
                            <span class="text-base md:text-lg font-black text-white">{{ stats?.compliance_rate || 0 }}%</span>
                        </div>
                        <!-- Progress Bar Track -->
                        <div class="w-full bg-slate-800/80 rounded-full h-2.5 my-2.5 overflow-hidden border border-teal-700/50">
                            <div 
                                class="bg-gradient-to-r from-teal-400 to-emerald-400 h-2.5 rounded-full transition-all duration-500" 
                                :style="{ width: `${stats?.compliance_rate || 0}%` }"
                            ></div>
                        </div>
                        <p class="text-[10px] text-teal-300 font-medium">Keterisian jurnal seluruh guru</p>
                    </div>
                </div>

                <!-- B. CONTROL BAR & FILTERS (Compact di Mobile, Lengkap di Desktop) -->
                <div class="bg-white p-2.5 md:p-5 rounded-xl md:rounded-3xl border border-slate-200 md:border-slate-100 shadow-2xs md:shadow-sm space-y-2 md:space-y-3">
                    <!-- Mobile Filter Row (md:hidden) -->
                    <div class="grid md:hidden grid-cols-2 gap-1.5">
                        <div class="flex items-center gap-1 bg-slate-50 border border-slate-200 rounded-lg p-1">
                            <button @click="changeDateByDays(-1)" type="button" class="w-6 h-6 rounded bg-white text-slate-600 flex items-center justify-center border border-slate-200 shrink-0">
                                <ChevronLeftIcon class="w-3.5 h-3.5" />
                            </button>
                            <input 
                                type="date" 
                                v-model="selectedDate" 
                                class="w-full min-w-0 border-0 bg-transparent p-0 text-[10px] font-extrabold text-slate-800 text-center focus:ring-0"
                            >
                            <button @click="changeDateByDays(1)" type="button" class="w-6 h-6 rounded bg-white text-slate-600 flex items-center justify-center border border-slate-200 shrink-0">
                                <ChevronRightIcon class="w-3.5 h-3.5" />
                            </button>
                        </div>

                        <select 
                            v-if="isGlobalAdmin && units && units.length > 0"
                            v-model="selectedUnit" 
                            class="w-full min-w-0 truncate py-1.5 pl-2.5 pr-6 bg-slate-50 border border-slate-200 rounded-lg text-[11px] font-bold text-slate-800 focus:ring-teal-500 focus:border-teal-500"
                        >
                            <option value="all">Semua Unit</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                        <div v-else class="flex bg-slate-100 p-0.5 rounded-lg border border-slate-200 text-[10px] font-bold">
                            <button @click="activeStatusFilter = 'all'" :class="activeStatusFilter === 'all' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500'" class="flex-1 py-1 rounded-md">Semua</button>
                            <button @click="activeStatusFilter = 'filled'" :class="activeStatusFilter === 'filled' ? 'bg-emerald-600 text-white' : 'text-slate-500'" class="flex-1 py-1 rounded-md">Terisi</button>
                            <button @click="activeStatusFilter = 'unfilled'" :class="activeStatusFilter === 'unfilled' ? 'bg-rose-600 text-white' : 'text-slate-500'" class="flex-1 py-1 rounded-md">Belum</button>
                        </div>
                    </div>

                    <!-- Desktop Filter Row (hidden md:flex) -->
                    <div class="hidden md:flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                        <!-- Left Group: Date & Unit Selector -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="relative">
                                <input 
                                    type="date" 
                                    v-model="selectedDate" 
                                    class="pl-3 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-extrabold text-slate-800 focus:ring-teal-500 focus:border-teal-500"
                                >
                            </div>

                            <div v-if="isGlobalAdmin && units && units.length > 0">
                                <select 
                                    v-model="selectedUnit" 
                                    class="py-2 pl-3 pr-8 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-extrabold text-slate-800 focus:ring-teal-500 focus:border-teal-500"
                                >
                                    <option value="all">Semua Unit Sekolah</option>
                                    <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </select>
                            </div>

                            <span class="hidden sm:inline-block px-3 py-2 bg-teal-50 text-teal-800 rounded-2xl text-xs font-extrabold border border-teal-100">
                                📅 {{ formatDate(date) }}
                            </span>
                        </div>

                        <!-- Right Group: Status Filter Tabs & Export -->
                        <div class="flex items-center gap-2 flex-wrap justify-between lg:justify-end">
                            <div class="flex bg-slate-100 p-1 rounded-2xl border border-slate-200 text-xs font-bold">
                                <button 
                                    @click="activeStatusFilter = 'all'" 
                                    :class="activeStatusFilter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900'"
                                    class="px-3 py-1.5 rounded-xl transition"
                                >
                                    Semua ({{ stats?.total || 0 }})
                                </button>
                                <button 
                                    @click="activeStatusFilter = 'filled'" 
                                    :class="activeStatusFilter === 'filled' ? 'bg-emerald-600 text-white shadow-sm font-extrabold' : 'text-slate-500 hover:text-emerald-700'"
                                    class="px-3 py-1.5 rounded-xl transition"
                                >
                                    Terisi ({{ stats?.filled || 0 }})
                                </button>
                                <button 
                                    @click="activeStatusFilter = 'unfilled'" 
                                    :class="activeStatusFilter === 'unfilled' ? 'bg-rose-600 text-white shadow-sm font-extrabold' : 'text-slate-500 hover:text-rose-700'"
                                    class="px-3 py-1.5 rounded-xl transition"
                                >
                                    Belum ({{ stats?.unfilled || 0 }})
                                </button>
                            </div>

                            <a 
                                :href="route('yayasan.teaching-journal.export', { month: new Date(date).getMonth() + 1, year: new Date(date).getFullYear() })" 
                                class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-2xl text-xs font-extrabold flex items-center gap-1.5 shadow-sm transition active:scale-95"
                            >
                                <ArrowDownTrayIcon class="w-4 h-4" />
                                <span>Export Rekap Bulanan</span>
                            </a>
                        </div>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full">
                        <MagnifyingGlassIcon class="w-3.5 h-3.5 md:w-4 md:h-4 absolute left-3 md:left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Cari guru, mapel, kelas, atau unit..." 
                            class="w-full pl-8 md:pl-10 pr-3 md:pr-4 py-1.5 md:py-2 bg-slate-50 border border-slate-200 rounded-lg md:rounded-2xl text-[11px] md:text-xs font-bold text-slate-800 focus:ring-teal-500 focus:border-teal-500 placeholder-slate-400"
                        >
                    </div>
                </div>

                <!-- C. EMPTY STATE -->
                <div v-if="filteredSchedules.length === 0" class="text-center py-6 md:py-14 bg-white rounded-2xl md:rounded-3xl border border-slate-200 md:border-slate-100 shadow-2xs md:shadow-sm flex flex-col items-center justify-center p-4 md:p-6 relative overflow-hidden">
                    <div class="relative mb-2 flex items-center justify-center">
                        <img 
                            src="/images/empty_calendar_3d.png" 
                            alt="Tidak Ada Jadwal KBM" 
                            class="w-24 h-24 md:w-48 md:h-48 object-contain drop-shadow-xl select-none pointer-events-none"
                        />
                    </div>
                    <h3 class="text-sm md:text-lg font-black text-slate-900 mb-0.5">Tidak Ada Jadwal KBM</h3>
                    <p class="text-[11px] md:text-xs text-slate-500 max-w-sm mb-3">Tidak ditemukan jadwal pembelajaran yang sesuai filter pada tanggal ini.</p>
                    <button
                        v-if="selectedDate !== todayDateString"
                        @click="goToToday"
                        type="button"
                        class="px-3 py-1.5 bg-teal-50 hover:bg-teal-100 text-teal-800 rounded-xl text-[11px] font-black border border-teal-200/60 shadow-2xs transition active:scale-95 flex items-center gap-1.5"
                    >
                        <CalendarDaysIcon class="w-3.5 h-3.5 text-teal-600" />
                        <span>Kembali ke Hari Ini</span>
                    </button>
                </div>

                <!-- 📱 D1. MOBILE ULTRA-COMPACT MONITORING LIST (block md:hidden) -->
                <div v-else class="block md:hidden space-y-2">
                    <div 
                        v-for="item in filteredSchedules" 
                        :key="'mon-mob-'+item.id"
                        class="bg-white rounded-xl p-2.5 border shadow-2xs space-y-1.5 relative overflow-hidden"
                        :class="item.is_filled ? 'border-emerald-200/80' : 'border-rose-200/80'"
                    >
                        <!-- Left Accent Strip -->
                        <div 
                            class="absolute top-0 bottom-0 left-0 w-1" 
                            :class="item.is_filled ? 'bg-emerald-500' : 'bg-rose-500'"
                        ></div>

                        <!-- Row 1: Jam + Kelas + Unit & Status Badge -->
                        <div class="flex items-center justify-between gap-1.5 pl-1.5">
                            <div class="flex items-center gap-1.5 min-w-0 flex-wrap">
                                <span class="px-1.5 py-0.5 bg-slate-100 rounded text-[9px] font-black text-slate-700 font-mono">
                                    {{ item.start_time.substring(0, 5) }}-{{ item.end_time.substring(0, 5) }}
                                </span>
                                <span class="px-1.5 py-0.5 bg-teal-50 text-teal-800 border border-teal-200/70 rounded text-[9px] font-black">
                                    {{ item.classroom }}
                                </span>
                                <span v-if="isGlobalAdmin && item.unit_name" class="text-[9px] font-extrabold text-slate-400 truncate">
                                    • {{ item.unit_name }}
                                </span>
                            </div>

                            <span 
                                class="shrink-0 px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-wider border flex items-center gap-1 leading-none"
                                :class="item.is_filled 
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                                    : 'bg-rose-50 text-rose-700 border-rose-200'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="item.is_filled ? 'bg-emerald-500' : 'bg-rose-500 animate-pulse'"></span>
                                {{ item.is_filled ? 'Terisi' : 'Belum' }}
                            </span>
                        </div>

                        <!-- Row 2: Mapel + Guru + Foto Mini + Action Button dalam 1 Baris Padat -->
                        <div class="flex items-center justify-between gap-2 pl-1.5 pt-1 border-t border-slate-100">
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <!-- Jika ada foto dokumentasi, tampilkan thumbnail kecil 36x36 yang bisa di-tap -->
                                <div 
                                    v-if="item.is_filled && item.photo_path" 
                                    @click="previewPhotoUrl = '/storage/' + item.photo_path"
                                    class="w-9 h-9 rounded-lg overflow-hidden bg-slate-900 border border-slate-200 shrink-0 relative cursor-pointer"
                                >
                                    <img :src="'/storage/' + item.photo_path" class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="w-7 h-7 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center font-black text-[10px] shrink-0">
                                    {{ item.teacher_name.charAt(0).toUpperCase() }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <h3 class="font-extrabold text-xs text-slate-900 leading-tight truncate">{{ item.subject }}</h3>
                                    <p class="text-[10px] font-semibold text-slate-500 truncate leading-tight mt-0.5">
                                        {{ item.teacher_name }}
                                        <span v-if="item.is_filled && item.filled_at" class="text-[9px] text-emerald-600 font-bold"> • {{ item.filled_at }}</span>
                                    </p>
                                    <p v-if="item.is_filled && (item.custom_theme || item.notes)" class="text-[9px] text-slate-500 italic truncate mt-0.5">
                                        "{{ item.custom_theme || item.notes }}"
                                    </p>
                                </div>
                            </div>

                            <!-- Action Button Kanan -->
                            <div class="shrink-0">
                                <Link 
                                    v-if="item.is_filled"
                                    :href="route('yayasan.teaching-journal.show', item.journal_id)"
                                    class="h-7 px-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-extrabold text-[10px] rounded-lg border border-emerald-200 inline-flex items-center gap-1"
                                >
                                    <EyeIcon class="w-3.5 h-3.5 text-emerald-600" />
                                    <span>Detail</span>
                                </Link>

                                <button 
                                    v-else
                                    @click="sendReminder(item)"
                                    :disabled="sendingReminderId === item.id || !item.teacher_user_id"
                                    class="h-7 px-2.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-[10px] rounded-lg shadow-2xs inline-flex items-center gap-1 disabled:opacity-50"
                                >
                                    <BellAlertIcon class="w-3.5 h-3.5" />
                                    <span>{{ sendingReminderId === item.id ? '...' : 'Ingatkan' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 🖥️ D2. DESKTOP LIVE KBM MONITORING CARDS GRID (hidden md:grid) -->
                <div v-if="filteredSchedules.length > 0" class="hidden md:grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div 
                        v-for="item in filteredSchedules" 
                        :key="'mon-'+item.id"
                        class="bg-white rounded-3xl p-5 border shadow-sm hover:shadow-md transition-all flex flex-col justify-between relative overflow-hidden"
                        :class="item.is_filled ? 'border-emerald-100 hover:border-emerald-200' : 'border-rose-100 hover:border-rose-200'"
                    >
                        <!-- Top Accent Bar -->
                        <div 
                            class="absolute top-0 left-0 right-0 h-1.5" 
                            :class="item.is_filled ? 'bg-emerald-500' : 'bg-rose-500'"
                        ></div>

                        <div>
                            <!-- Header Time & Status Badge -->
                            <div class="flex items-center justify-between mb-3 pt-1">
                                <span class="px-2.5 py-1 bg-slate-100 rounded-xl text-[11px] font-black text-slate-700 flex items-center gap-1">
                                    <ClockIcon class="w-3.5 h-3.5 text-slate-500" />
                                    {{ item.start_time.substring(0, 5) }} - {{ item.end_time.substring(0, 5) }} WIB
                                </span>
                                
                                <span 
                                    class="px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider border flex items-center gap-1"
                                    :class="item.is_filled 
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                                        : 'bg-rose-50 text-rose-700 border-rose-200'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="item.is_filled ? 'bg-emerald-500' : 'bg-rose-500 animate-pulse'"></span>
                                    {{ item.is_filled ? 'SUDAH DIISI' : 'BELUM DIISI' }}
                                </span>
                            </div>

                            <!-- Subject & Class Info -->
                            <div class="mb-3">
                                <span v-if="isGlobalAdmin" class="text-[10px] font-extrabold text-teal-600 uppercase tracking-widest block mb-0.5">
                                    {{ item.unit_name }}
                                </span>
                                <h3 class="font-extrabold text-base text-slate-900 leading-snug">{{ item.subject }}</h3>
                                <p class="text-xs font-bold text-teal-700 mt-0.5">{{ item.classroom }}</p>
                            </div>

                            <!-- Teacher Profile Card -->
                            <div class="flex items-center gap-2.5 p-2.5 bg-slate-50 rounded-2xl border border-slate-100 mb-3">
                                <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-black text-xs shrink-0">
                                    {{ item.teacher_name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-black text-slate-800 truncate">{{ item.teacher_name }}</p>
                                    <p class="text-[10px] text-slate-400 font-semibold">Guru Pengampu</p>
                                </div>
                            </div>

                            <!-- Documentation Preview if Available -->
                            <div v-if="item.is_filled" class="mb-4 space-y-2">
                                <div v-if="item.photo_path" class="relative rounded-2xl overflow-hidden bg-slate-900 border border-slate-200 aspect-video max-h-36 group cursor-pointer" @click="previewPhotoUrl = '/storage/' + item.photo_path">
                                    <img :src="'/storage/' + item.photo_path" class="w-full h-full object-cover group-hover:scale-105 transition-transform" />
                                    <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold gap-1">
                                        <EyeIcon class="w-4 h-4" /> Pratinjau Foto
                                    </div>
                                </div>
                                <div v-if="item.custom_theme || item.notes" class="p-2.5 bg-emerald-50/60 rounded-xl text-[11px] text-slate-700 border border-emerald-100 italic line-clamp-2">
                                    "{{ item.custom_theme || item.notes }}"
                                </div>
                                <p v-if="item.filled_at" class="text-[10px] text-slate-400 font-bold text-right">
                                    Dilaporkan pukul {{ item.filled_at }} WIB
                                </p>
                            </div>
                        </div>

                        <!-- Actions Bar -->
                        <div class="pt-3 border-t border-slate-100 flex gap-2">
                            <!-- If Filled: View Journal Details -->
                            <Link 
                                v-if="item.is_filled"
                                :href="route('yayasan.teaching-journal.show', item.journal_id)"
                                class="w-full py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-extrabold text-xs rounded-xl border border-emerald-200 text-center block transition-all active:scale-95 flex items-center justify-center gap-1.5"
                            >
                                <EyeIcon class="w-4 h-4 text-emerald-600" />
                                <span>Lihat Laporan Jurnal</span>
                            </Link>

                            <!-- If Unfilled: Send Reminder Notification -->
                            <button 
                                v-else
                                @click="sendReminder(item)"
                                :disabled="sendingReminderId === item.id || !item.teacher_user_id"
                                class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-rose-600/20 text-center transition-all active:scale-95 flex items-center justify-center gap-1.5 disabled:opacity-50 cursor-pointer"
                            >
                                <BellAlertIcon class="w-4 h-4 animate-bounce" />
                                <span>{{ sendingReminderId === item.id ? 'Mengirim...' : 'Ingatkan Guru (Notif)' }}</span>
                            </button>
                        </div>

                    </div>
                </div>

            </template>


            <!-- ======================================================== -->
            <!-- 2. TEACHER PERSONAL SCHEDULE VIEW (Native Teacher View)   -->
            <!-- ======================================================== -->
            <template v-else>
                <!-- Header Desktop (hidden md:flex, karena di Mobile sudah ada Compact Top Bar di atas) -->
                <div class="hidden md:flex items-center gap-3 mb-1">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-600 flex items-center justify-center text-white shadow-md shadow-emerald-600/20 shrink-0">
                        <BookOpenIcon class="w-6 h-6 stroke-[2.2]" />
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-900 tracking-tight leading-tight">
                            Jurnal Mengajar
                        </h2>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">
                            Catat dan kelola aktivitas mengajar Anda.
                        </p>
                    </div>
                </div>

                <!-- Card 1: Date Selector (Compact di Mobile, Normal di Desktop) -->
                <div class="bg-white rounded-xl md:rounded-3xl p-2 md:p-4 border border-slate-200 md:border-slate-100 shadow-2xs md:shadow-sm flex items-center justify-between gap-2">
                    <!-- Quick Prev Day Button -->
                    <button 
                        @click="changeDateByDays(-1)" 
                        type="button" 
                        class="w-8 h-8 md:w-10 md:h-10 rounded-lg md:rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition active:scale-95 shrink-0"
                        title="Hari Sebelumnya"
                    >
                        <ChevronLeftIcon class="w-4 h-4 stroke-[2.5]" />
                    </button>

                    <!-- Center Date Info with Hidden Native Datepicker -->
                    <div class="relative flex items-center justify-center gap-2 py-0.5 px-2 cursor-pointer group flex-1 max-w-sm">
                        <input 
                            type="date" 
                            v-model="selectedDate" 
                            class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10"
                            title="Pilih Tanggal"
                        />
                        <div class="w-7 h-7 md:w-9 md:h-9 rounded-lg md:rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100/80">
                            <CalendarDaysIcon class="w-4 h-4 md:w-5 md:h-5 stroke-[2.2]" />
                        </div>
                        <div class="text-left">
                            <h4 class="font-black text-xs md:text-sm text-slate-900 leading-tight whitespace-nowrap">
                                {{ formatDate(date) }}
                            </h4>
                            <p class="text-[10px] md:text-[11px] font-bold text-slate-500 whitespace-nowrap leading-none mt-0.5">
                                {{ getHijriDate(date) }}
                            </p>
                        </div>
                    </div>

                    <!-- Quick Next Day Button -->
                    <button 
                        @click="changeDateByDays(1)" 
                        type="button" 
                        class="w-8 h-8 md:w-10 md:h-10 rounded-lg md:rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition active:scale-95 shrink-0"
                        title="Hari Berikutnya"
                    >
                        <ChevronRightIcon class="w-4 h-4 stroke-[2.5]" />
                    </button>
                </div>

                <!-- Card 2: Empty State (Compact di Mobile) -->
                <div v-if="schedules.length === 0" class="bg-white rounded-2xl md:rounded-3xl border border-slate-200 md:border-slate-100 shadow-2xs md:shadow-sm p-4 md:p-8 text-center flex flex-col items-center justify-center relative overflow-hidden">
                    <div class="relative mb-1.5 flex items-center justify-center">
                        <img 
                            src="/images/empty_calendar_3d.png" 
                            alt="Tidak Ada Jurnal Mengajar" 
                            class="w-24 h-24 md:w-52 md:h-52 object-contain drop-shadow-lg select-none pointer-events-none"
                        />
                    </div>

                    <h3 class="text-sm md:text-lg font-black text-slate-900 mb-0.5 tracking-tight">
                        Tidak Ada Jurnal Mengajar
                    </h3>
                    <p class="text-[11px] md:text-xs text-slate-500 max-w-xs mx-auto leading-snug font-medium">
                        Belum ada jadwal mengajar pada tanggal ini. Pilih tanggal lain di atas.
                    </p>
                </div>

                <!-- 📱 Teacher Schedule Micro-Cards Mobile (block md:hidden) -->
                <div v-else class="block md:hidden space-y-2">
                    <div 
                        v-for="item in schedules" 
                        :key="'teach-mob-'+item.id"
                        class="bg-white rounded-xl p-2.5 border shadow-2xs flex items-center justify-between gap-2.5 relative overflow-hidden"
                        :class="item.is_filled ? 'border-emerald-200/80' : 'border-slate-200'"
                    >
                        <div class="absolute top-0 bottom-0 left-0 w-1" :class="item.is_filled ? 'bg-emerald-500' : 'bg-teal-600'"></div>

                        <div class="min-w-0 flex-1 pl-1.5">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="px-1.5 py-0.5 bg-slate-100 rounded text-[9px] font-black text-slate-700 font-mono">
                                    {{ item.start_time.substring(0, 5) }}-{{ item.end_time.substring(0, 5) }}
                                </span>
                                <span class="px-1.5 py-0.5 rounded bg-teal-50 text-teal-800 text-[9px] font-black border border-teal-200/60">
                                    {{ item.classroom }}
                                </span>
                                <span 
                                    class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-wider border flex items-center gap-1 leading-none"
                                    :class="item.is_filled ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="item.is_filled ? 'bg-emerald-500' : 'bg-rose-500 animate-pulse'"></span>
                                    {{ item.is_filled ? 'Terisi' : 'Belum' }}
                                </span>
                            </div>

                            <h4 class="font-extrabold text-xs text-slate-900 leading-tight truncate mt-1">
                                {{ item.subject }}
                            </h4>
                        </div>

                        <div class="shrink-0">
                            <Link 
                                v-if="!item.is_filled"
                                :href="route('yayasan.teaching-journal.create', { schedule_id: item.id, date: date })" 
                                class="h-8 px-3 bg-gradient-to-r from-teal-700 to-emerald-700 text-white font-black text-[10px] rounded-lg shadow-2xs inline-flex items-center justify-center active:scale-95 transition"
                            >
                                Isi Jurnal
                            </Link>
                            <Link 
                                v-else 
                                :href="route('yayasan.teaching-journal.show', item.journal_id)" 
                                class="h-8 px-3 bg-slate-100 text-slate-800 font-black text-[10px] rounded-lg border border-slate-200 inline-flex items-center justify-center active:scale-95 transition"
                            >
                                Detail
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- 🖥️ Teacher Schedule Cards Desktop (hidden md:grid) -->
                <div v-if="schedules.length > 0" class="hidden md:grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div 
                        v-for="item in schedules" 
                        :key="'teach-'+item.id"
                        class="bg-white rounded-3xl p-5 border shadow-sm hover:shadow-md transition-all flex flex-col justify-between relative overflow-hidden"
                        :class="item.is_filled ? 'border-emerald-100 hover:border-emerald-200' : 'border-slate-200 hover:border-teal-200'"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1.5" :class="item.is_filled ? 'bg-emerald-500' : 'bg-gradient-to-r from-teal-600 to-emerald-600'"></div>

                        <div>
                            <div class="flex items-center justify-between mb-3 pt-1">
                                <span class="px-2.5 py-1 bg-slate-100 rounded-xl text-xs font-black text-slate-700 flex items-center gap-1.5">
                                    <ClockIcon class="w-3.5 h-3.5 text-slate-500" />
                                    {{ item.start_time.substring(0, 5) }} - {{ item.end_time.substring(0, 5) }} WIB
                                </span>
                                <span 
                                    class="px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider border flex items-center gap-1"
                                    :class="item.is_filled ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="item.is_filled ? 'bg-emerald-500' : 'bg-rose-500 animate-pulse'"></span>
                                    {{ item.is_filled ? 'SUDAH DIISI' : 'BELUM DIISI' }}
                                </span>
                            </div>

                            <div class="mb-4">
                                <h4 class="font-black text-base text-slate-900 leading-snug">{{ item.subject }}</h4>
                                <div class="mt-1 flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-md bg-teal-50 text-teal-800 text-[11px] font-black border border-teal-200/60">
                                        {{ item.classroom }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100">
                            <Link 
                                v-if="!item.is_filled"
                                :href="route('yayasan.teaching-journal.create', { schedule_id: item.id, date: date })" 
                                class="w-full py-3 bg-gradient-to-r from-teal-700 to-emerald-700 hover:from-teal-600 hover:to-emerald-600 text-white font-black text-xs rounded-2xl shadow-md shadow-teal-700/20 text-center block transition-all active:scale-95"
                            >
                                Isi Jurnal Mengajar
                            </Link>
                            <Link 
                                v-else 
                                :href="route('yayasan.teaching-journal.show', item.journal_id)" 
                                class="w-full py-3 bg-slate-100 hover:bg-slate-200 text-slate-800 font-black text-xs rounded-2xl border border-slate-200 text-center block transition-all active:scale-95"
                            >
                                Lihat Laporan Jurnal
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Rekap Bulan Ini (Compact di Mobile) -->
                <div class="bg-white rounded-xl md:rounded-3xl p-3 md:p-5 border border-slate-200 md:border-slate-100 shadow-2xs md:shadow-sm">
                    <div class="flex items-center justify-between pb-2 md:pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 md:w-9 md:h-9 rounded-lg md:rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100/80 shrink-0">
                                <ChartBarIcon class="w-4 h-4 md:w-5 md:h-5 stroke-[2.2]" />
                            </div>
                            <div>
                                <h3 class="font-black text-xs md:text-sm text-slate-900 leading-tight">
                                    Rekap {{ monthlyStats?.month_name || getMonthYear(date) }}
                                </h3>
                                <p class="text-[10px] font-medium text-slate-400 leading-none mt-0.5">
                                    Ringkasan aktivitas mengajar Anda
                                </p>
                            </div>
                        </div>

                        <Link 
                            :href="route('yayasan.teaching-journal.recap', { month: monthlyStats?.month || (new Date(date).getMonth() + 1), year: monthlyStats?.year || new Date(date).getFullYear() })"
                            class="text-[11px] md:text-xs font-black text-emerald-700 hover:text-emerald-800 flex items-center gap-0.5 transition active:scale-95 whitespace-nowrap shrink-0"
                        >
                            <span>Lihat Rekap</span>
                            <ArrowRightIcon class="w-3 h-3 stroke-[2.5]" />
                        </Link>
                    </div>

                    <!-- 3 Mini Stats Grid -->
                    <div class="grid grid-cols-3 gap-2 md:gap-3 mt-2.5 md:mt-3.5">
                        <div class="bg-slate-50 rounded-xl md:rounded-2xl p-2 md:p-3 border border-slate-100 flex flex-col items-center justify-center text-center">
                            <p class="text-sm md:text-lg font-black text-blue-700 leading-none">
                                {{ monthlyStats?.teaching_days || 18 }}
                            </p>
                            <p class="text-[9px] md:text-[11px] font-bold text-slate-500 mt-1">
                                Hari Mengajar
                            </p>
                        </div>

                        <div class="bg-slate-50 rounded-xl md:rounded-2xl p-2 md:p-3 border border-slate-100 flex flex-col items-center justify-center text-center">
                            <p class="text-sm md:text-lg font-black text-amber-600 leading-none">
                                {{ monthlyStats?.total_journals || 42 }}
                            </p>
                            <p class="text-[9px] md:text-[11px] font-bold text-slate-500 mt-1">
                                Total Jurnal
                            </p>
                        </div>

                        <div class="bg-slate-50 rounded-xl md:rounded-2xl p-2 md:p-3 border border-slate-100 flex flex-col items-center justify-center text-center">
                            <p class="text-sm md:text-lg font-black text-purple-600 leading-none">
                                {{ monthlyStats?.compliance_rate || 96 }}%
                            </p>
                            <p class="text-[9px] md:text-[11px] font-bold text-slate-500 mt-1">
                                Tingkat Terisi
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Tips (Compact 1 Baris di Mobile) -->
                <div class="bg-amber-50/70 border border-amber-200/70 rounded-xl md:rounded-3xl p-2.5 md:p-4 flex items-center justify-between gap-2.5 shadow-2xs">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 md:w-10 md:h-10 rounded-lg md:rounded-2xl bg-amber-500 text-white flex items-center justify-center shadow-2xs shrink-0">
                            <LightBulbIcon class="w-4 h-4 md:w-5 md:h-5 stroke-[2.2]" />
                        </div>
                        <p class="text-[10px] md:text-[11px] font-semibold text-amber-900/90 leading-snug">
                            <strong class="font-black text-amber-950">Tips:</strong> Isi jurnal setiap selesai mengajar untuk menjaga kelengkapan data KBM.
                        </p>
                    </div>
                </div>
            </template>

        </div>

        <!-- Lightbox / Photo Preview Modal -->
        <div v-if="previewPhotoUrl" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click.self="previewPhotoUrl = null">
            <div class="relative max-w-2xl w-full bg-slate-900 rounded-3xl overflow-hidden shadow-2xl p-2 border border-slate-700 animate-in fade-in zoom-in-95">
                <button @click="previewPhotoUrl = null" class="absolute top-4 right-4 p-2 bg-black/60 hover:bg-black/80 text-white rounded-full transition z-10">
                    <XMarkIcon class="w-5 h-5" />
                </button>
                <img :src="previewPhotoUrl" class="w-full max-h-[80vh] object-contain rounded-2xl" />
            </div>
        </div>

    </AuthenticatedLayout>
</template>

