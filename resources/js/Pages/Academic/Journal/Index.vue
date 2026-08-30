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
    XMarkIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const props = defineProps({
    schedules: Array,
    date: String,
    stats: Object,
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

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'full' }).format(date);
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

        <div class="py-4 md:py-6 max-w-7xl mx-auto space-y-5 md:space-y-6">
            
            <!-- ======================================================== -->
            <!-- 1. EXECUTIVE MONITORING VIEW (Super Admin, Kepsek, Pengawas) -->
            <!-- ======================================================== -->
            <template v-if="viewMode === 'monitoring'">
                
                <!-- A. STATS RIBBON (Executive Cards) -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
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

                <!-- B. CONTROL BAR & FILTERS -->
                <div class="bg-white p-4 md:p-5 rounded-3xl border border-slate-100 shadow-sm space-y-3">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                        
                        <!-- Left Group: Date & Unit Selector -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <!-- Date Picker -->
                            <div class="relative">
                                <input 
                                    type="date" 
                                    v-model="selectedDate" 
                                    class="pl-3 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-extrabold text-slate-800 focus:ring-teal-500 focus:border-teal-500"
                                >
                            </div>

                            <!-- Unit Selector for Global Roles -->
                            <div v-if="isGlobalAdmin && units && units.length > 0">
                                <select 
                                    v-model="selectedUnit" 
                                    class="py-2 pl-3 pr-8 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-extrabold text-slate-800 focus:ring-teal-500 focus:border-teal-500"
                                >
                                    <option value="all">Semua Unit Sekolah</option>
                                    <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </select>
                            </div>

                            <!-- Date Display Pill -->
                            <span class="hidden sm:inline-block px-3 py-2 bg-teal-50 text-teal-800 rounded-2xl text-xs font-extrabold border border-teal-100">
                                📅 {{ formatDate(date) }}
                            </span>
                        </div>

                        <!-- Right Group: Status Filter Tabs & Export -->
                        <div class="flex items-center gap-2 flex-wrap justify-between lg:justify-end">
                            <!-- Status Filter Buttons -->
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

                            <!-- Export Monthly Report Button -->
                            <a 
                                :href="route('yayasan.teaching-journal.export', { month: new Date(date).getMonth() + 1, year: new Date(date).getFullYear() })" 
                                class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-2xl text-xs font-extrabold flex items-center gap-1.5 shadow-sm transition active:scale-95"
                            >
                                <ArrowDownTrayIcon class="w-4 h-4" />
                                <span class="hidden sm:inline">Export Rekap Bulanan</span>
                                <span class="sm:hidden">Rekap</span>
                            </a>
                        </div>

                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full">
                        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Cari guru, mata pelajaran, kelas, atau unit..." 
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:ring-teal-500 focus:border-teal-500 placeholder-slate-400"
                        >
                    </div>
                </div>

                <!-- C. EMPTY STATE -->
                <div v-if="filteredSchedules.length === 0" class="text-center py-12 md:py-16 bg-white rounded-3xl border border-slate-100 shadow-sm flex flex-col items-center justify-center p-6">
                    <div class="bg-slate-50 p-5 rounded-full mb-3">
                        <CalendarIcon class="w-12 h-12 text-slate-400" />
                    </div>
                    <h3 class="text-base md:text-lg font-extrabold text-slate-900 mb-1">Tidak Ada Jadwal KBM</h3>
                    <p class="text-xs text-slate-500 max-w-sm">Tidak ditemukan jadwal pembelajaran yang sesuai filter pada tanggal ini.</p>
                </div>

                <!-- D. LIVE KBM MONITORING CARDS GRID (Responsive) -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
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
                <!-- Desktop & Mobile Toolbar -->
                <div class="flex items-center justify-between bg-white p-4 rounded-3xl border border-slate-100 shadow-sm flex-wrap gap-3">
                    <div class="flex items-center gap-2">
                        <input 
                            type="date" 
                            v-model="selectedDate" 
                            class="py-2 px-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:ring-teal-500 focus:border-teal-500"
                        >
                        <span class="text-xs font-extrabold text-teal-800 bg-teal-50 px-3 py-2 rounded-2xl border border-teal-100">
                            📅 {{ formatDate(date) }}
                        </span>
                    </div>

                    <a 
                        :href="route('yayasan.teaching-journal.export', { month: new Date(date).getMonth() + 1, year: new Date(date).getFullYear() })" 
                        class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-2xl font-extrabold text-xs flex items-center gap-1.5 shadow-sm transition active:scale-95"
                    >
                        <ArrowDownTrayIcon class="w-4 h-4" />
                        <span>Rekap Bulan Ini</span>
                    </a>
                </div>

                <!-- Empty State -->
                <div v-if="schedules.length === 0" class="text-center py-12 md:py-16 bg-white rounded-3xl border border-slate-100 shadow-sm flex flex-col items-center justify-center p-6">
                    <div class="bg-slate-50 p-5 rounded-full mb-3">
                        <CalendarIcon class="w-12 h-12 text-slate-400" />
                    </div>
                    <h3 class="text-base md:text-lg font-extrabold text-slate-900 mb-1">Libur Mengajar? 🎉</h3>
                    <p class="text-xs text-slate-500 max-w-sm">Tidak ada jadwal mengajar pada tanggal ini. Silakan pilih tanggal lain di atas.</p>
                </div>

                <!-- Teacher Schedule Cards -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div 
                        v-for="item in schedules" 
                        :key="'teach-'+item.id"
                        class="bg-white rounded-3xl p-5 border shadow-sm hover:shadow-md transition-all flex flex-col justify-between relative overflow-hidden"
                        :class="item.is_filled ? 'border-emerald-100' : 'border-slate-200'"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1.5" :class="item.is_filled ? 'bg-emerald-500' : 'bg-slate-900'"></div>

                        <div>
                            <div class="flex items-center justify-between mb-3 pt-1">
                                <span class="px-2.5 py-1 bg-slate-100 rounded-xl text-xs font-black text-slate-700">
                                    {{ item.start_time.substring(0, 5) }} - {{ item.end_time.substring(0, 5) }} WIB
                                </span>
                                <span 
                                    class="px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider border"
                                    :class="item.is_filled ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                >
                                    {{ item.is_filled ? '🟢 SUDAH DIISI' : '🔴 BELUM DIISI' }}
                                </span>
                            </div>

                            <div class="mb-4">
                                <h4 class="font-extrabold text-base text-slate-900 leading-snug">{{ item.subject }}</h4>
                                <p class="text-xs font-bold text-teal-700 mt-0.5">{{ item.classroom }}</p>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100">
                            <Link 
                                v-if="!item.is_filled"
                                :href="route('yayasan.teaching-journal.create', { schedule_id: item.id, date: date })" 
                                class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-2xl shadow-md text-center block transition-all active:scale-95"
                            >
                                Isi Jurnal Mengajar
                            </Link>
                            <Link 
                                v-else 
                                :href="route('yayasan.teaching-journal.show', item.journal_id)"
                                class="w-full py-3 bg-slate-100 hover:bg-slate-200 text-slate-800 font-extrabold text-xs rounded-2xl border border-slate-200 text-center block transition-all active:scale-95"
                            >
                                Lihat Laporan Jurnal
                            </Link>
                        </div>
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

