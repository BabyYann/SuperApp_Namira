<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { 
    UsersIcon, CheckCircleIcon, ClockIcon, CalendarDaysIcon, 
    ExclamationCircleIcon, ArrowPathIcon, MagnifyingGlassIcon,
    ChatBubbleOvalLeftEllipsisIcon, BuildingOffice2Icon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    data: {
        type: Object,
        required: true,
    },
});

// State
const isRefreshing = ref(false);
const searchQuery = ref('');
const activeFilter = ref(props.data?.stats?.not_checked_in > 0 ? 'not_checked_in' : 'all');
const hoveredSegment = ref(null);

// Circumference of SVG circle with radius 70
const RADIUS = 70;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS; // ~439.82

// Computed Donut Slices
const donutSlices = computed(() => {
    const stats = props.data?.stats || {};
    const total = stats.total || 0;

    if (total === 0) return [];

    const categories = [
        { key: 'present', label: 'Tepat Waktu', count: stats.present || 0, color: '#10b981', hoverColor: '#059669' },
        { key: 'late', label: 'Terlambat', count: stats.late || 0, color: '#f59e0b', hoverColor: '#d97706' },
        { key: 'permit', label: 'Izin / Dinas', count: stats.permit || 0, color: '#8b5cf6', hoverColor: '#7c3aed' },
        { key: 'not_checked_in', label: 'Belum Absen', count: stats.not_checked_in || 0, color: '#f43f5e', hoverColor: '#e11d48' },
    ];

    let accumulatedOffset = 0;

    return categories.map((cat) => {
        const length = (cat.count / total) * CIRCUMFERENCE;
        const percentage = Math.round((cat.count / total) * 100);
        const dasharray = `${length} ${CIRCUMFERENCE - length}`;
        const dashoffset = -accumulatedOffset;
        accumulatedOffset += length;

        return {
            ...cat,
            length,
            percentage,
            dasharray,
            dashoffset,
        };
    });
});

// Filtered Employees
const filteredEmployees = computed(() => {
    const lists = props.data?.lists || {};
    let employees = [];

    if (activeFilter.value === 'all') {
        employees = lists.all || [];
    } else {
        employees = lists[activeFilter.value] || [];
    }

    if (!searchQuery.value.trim()) {
        return employees;
    }

    const q = searchQuery.value.toLowerCase().trim();
    return employees.filter(emp => 
        (emp.name && emp.name.toLowerCase().includes(q)) ||
        (emp.jabatan && emp.jabatan.toLowerCase().includes(q)) ||
        (emp.nip && emp.nip.toLowerCase().includes(q))
    );
});

// Refresh Action
const refreshData = () => {
    isRefreshing.value = true;
    router.reload({
        only: ['liveAttendance'],
        onFinish: () => {
            setTimeout(() => {
                isRefreshing.value = false;
            }, 400);
        },
    });
};

// Unit Switcher (Global Admin)
const switchUnit = (newUnitId) => {
    router.get(route('attendance.index'), {
        unit_id: newUnitId,
        tab: 'live',
    }, {
        preserveState: true,
        replace: true,
    });
};

// WhatsApp Link Generator
const getWaReminderLink = (phone, name) => {
    if (!phone) return '#';
    let clean = String(phone).replace(/[^0-9]/g, '');
    if (clean.startsWith('0')) {
        clean = '62' + clean.slice(1);
    } else if (!clean.startsWith('62')) {
        clean = '62' + clean;
    }
    const message = encodeURIComponent(`Assalamu'alaikum Warahmatullahi Wabarakatuh, Yth. Bapak/Ibu ${name}.\n\nSekadar mengingatkan untuk melakukan presensi harian di SuperApp Namira hari ini. Terima kasih! 🙏`);
    return `https://wa.me/${clean}?text=${message}`;
};

// Helper for initials
const getInitials = (name) => {
    if (!name) return 'N';
    const parts = name.trim().split(' ');
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
};
</script>

<template>
    <div class="space-y-6">

        <!-- 1. HEADER & LIVE BADGE -->
        <div class="bg-white/90 backdrop-blur-xl rounded-3xl p-5 md:p-6 shadow-sm border border-slate-200/70">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-rose-50 text-rose-600 border border-rose-200">
                            <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                            <span>Live Radar Presensi</span>
                        </span>
                        <span class="text-xs font-bold text-slate-400">
                            {{ data?.date_formatted }}
                        </span>
                    </div>
                    <h2 class="text-xl md:text-2xl font-black text-slate-800 tracking-tight">
                        Pantauan Kehadiran: <span class="text-teal-700">{{ data?.unit_name }}</span>
                    </h2>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">
                        Transparansi kehadiran real-time seluruh guru dan staf di lingkungan unit hari ini.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Unit Selector for Global Admins -->
                    <div v-if="data?.is_global_admin && data?.units?.length > 0" class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-2xl px-3 py-1.5">
                        <BuildingOffice2Icon class="w-4 h-4 text-slate-500" />
                        <select 
                            :value="data?.unit_id"
                            @change="switchUnit($event.target.value)"
                            class="bg-transparent text-xs font-bold text-slate-700 border-none p-0 focus:ring-0 cursor-pointer"
                        >
                            <option v-for="u in data.units" :key="u.id" :value="u.id">
                                Unit {{ u.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Refresh Button -->
                    <button 
                        @click="refreshData"
                        :disabled="isRefreshing"
                        type="button"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-2xl text-xs font-black bg-slate-100 hover:bg-slate-200 text-slate-700 transition active:scale-95 disabled:opacity-60 cursor-pointer"
                        title="Segarkan data terbaru"
                    >
                        <ArrowPathIcon class="w-4 h-4 transition-transform duration-500" :class="{ 'animate-spin': isRefreshing }" />
                        <span>Segarkan</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. QUICK STAT CARDS -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
            <!-- Total Pegawai -->
            <div class="bg-white p-4 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-slate-400">
                    <span class="text-[11px] font-black uppercase tracking-wider">Total Pegawai</span>
                    <div class="p-2 bg-slate-50 rounded-xl text-slate-600">
                        <UsersIcon class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl sm:text-3xl font-black text-slate-800">{{ data?.stats?.total || 0 }}</div>
                    <p class="text-[11px] font-bold text-slate-400 mt-0.5">Guru & Tenaga Pendidik</p>
                </div>
            </div>

            <!-- Hadir (Tepat Waktu + Terlambat) -->
            <div class="bg-white p-4 rounded-3xl border border-emerald-200/80 shadow-xs flex flex-col justify-between bg-gradient-to-br from-white to-emerald-50/30">
                <div class="flex items-center justify-between text-emerald-600">
                    <span class="text-[11px] font-black uppercase tracking-wider">Sudah Hadir</span>
                    <div class="p-2 bg-emerald-50 rounded-xl text-emerald-600">
                        <CheckCircleIcon class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl sm:text-3xl font-black text-emerald-700">
                        {{ data?.stats?.attendance_count || 0 }}
                        <span class="text-xs font-bold text-emerald-600 ml-1">({{ data?.stats?.attendance_percentage || 0 }}%)</span>
                    </div>
                    <p class="text-[11px] font-bold text-emerald-600/80 mt-0.5">
                        {{ data?.stats?.present || 0 }} Tepat Waktu · {{ data?.stats?.late || 0 }} Terlambat
                    </p>
                </div>
            </div>

            <!-- Izin / Sakit / Dinas -->
            <div class="bg-white p-4 rounded-3xl border border-purple-200/80 shadow-xs flex flex-col justify-between bg-gradient-to-br from-white to-purple-50/30">
                <div class="flex items-center justify-between text-purple-600">
                    <span class="text-[11px] font-black uppercase tracking-wider">Izin / Dinas</span>
                    <div class="p-2 bg-purple-50 rounded-xl text-purple-600">
                        <CalendarDaysIcon class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl sm:text-3xl font-black text-purple-700">{{ data?.stats?.permit || 0 }}</div>
                    <p class="text-[11px] font-bold text-purple-600/80 mt-0.5">Izin, Sakit, Cuti, Dinas Luar</p>
                </div>
            </div>

            <!-- Belum Absen (The Focus!) -->
            <div 
                @click="activeFilter = 'not_checked_in'"
                class="bg-white p-4 rounded-3xl border-2 shadow-xs flex flex-col justify-between cursor-pointer transition hover:shadow-md"
                :class="activeFilter === 'not_checked_in' ? 'border-rose-500 bg-rose-50/40' : 'border-rose-200/80 bg-gradient-to-br from-white to-rose-50/30'"
            >
                <div class="flex items-center justify-between text-rose-600">
                    <span class="text-[11px] font-black uppercase tracking-wider">Belum Absen</span>
                    <div class="p-2 bg-rose-100 rounded-xl text-rose-600">
                        <ExclamationCircleIcon class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl sm:text-3xl font-black text-rose-600 flex items-center justify-between">
                        <span>{{ data?.stats?.not_checked_in || 0 }}</span>
                        <span v-if="data?.stats?.not_checked_in > 0" class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-rose-600 text-white animate-pulse">
                            Perlu Perhatian
                        </span>
                    </div>
                    <p class="text-[11px] font-bold text-rose-500 mt-0.5">Klik untuk lihat nama</p>
                </div>
            </div>
        </div>

        <!-- 3. MAIN SECTION: DONUT CHART & EMPLOYEE LIST -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- LEFT: DONUT CHART & INTERACTIVE LEGEND (5 COLS) -->
            <div class="lg:col-span-5 bg-white/90 backdrop-blur-xl rounded-3xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-black text-slate-800">Rasio Kehadiran Hari Ini</h3>
                            <p class="text-xs text-slate-400 font-medium">Visualisasi persentase kehadiran pegawai</p>
                        </div>
                    </div>

                    <!-- SVG Donut Chart Container -->
                    <div class="relative flex items-center justify-center my-4">
                        <svg class="w-56 h-56 transform -rotate-90" viewBox="0 0 200 200">
                            <!-- Background Track Circle -->
                            <circle
                                cx="100"
                                cy="100"
                                :r="RADIUS"
                                stroke="#f1f5f9"
                                stroke-width="22"
                                fill="transparent"
                            />

                            <!-- Segments -->
                            <template v-if="data?.stats?.total > 0">
                                <circle
                                    v-for="slice in donutSlices"
                                    :key="slice.key"
                                    cx="100"
                                    cy="100"
                                    :r="RADIUS"
                                    :stroke="slice.color"
                                    :stroke-width="hoveredSegment === slice.key || activeFilter === slice.key ? 26 : 22"
                                    fill="transparent"
                                    :stroke-dasharray="slice.dasharray"
                                    :stroke-dashoffset="slice.dashoffset"
                                    class="transition-all duration-500 cursor-pointer"
                                    @mouseenter="hoveredSegment = slice.key"
                                    @mouseleave="hoveredSegment = null"
                                    @click="activeFilter = slice.key"
                                />
                            </template>
                        </svg>

                        <!-- Centered Stats in Donut -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center">
                            <span class="text-3xl font-black text-slate-800 tracking-tight">
                                {{ data?.stats?.attendance_percentage || 0 }}%
                            </span>
                            <span class="text-xs font-bold text-slate-500 mt-0.5">
                                {{ data?.stats?.attendance_count || 0 }} dari {{ data?.stats?.total || 0 }} Hadir
                            </span>
                            <span class="text-[10px] font-bold text-teal-600 bg-teal-50 px-2 py-0.5 rounded-full mt-1.5 border border-teal-100">
                                Real-Time Unit
                            </span>
                        </div>
                    </div>

                    <!-- Interactive Legend -->
                    <div class="space-y-2 mt-6 pt-4 border-t border-slate-100">
                        <div 
                            v-for="slice in donutSlices" 
                            :key="'legend-'+slice.key"
                            @click="activeFilter = slice.key"
                            class="flex items-center justify-between p-2.5 rounded-2xl transition cursor-pointer border"
                            :class="activeFilter === slice.key ? 'bg-slate-50 border-slate-300 font-black' : 'border-transparent hover:bg-slate-50/80 font-bold'"
                        >
                            <div class="flex items-center gap-2.5">
                                <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: slice.color }"></span>
                                <span class="text-xs text-slate-700">{{ slice.label }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs">
                                <span class="font-mono text-slate-800">{{ slice.count }} Pegawai</span>
                                <span class="text-slate-400 font-medium">({{ slice.percentage }}%)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] text-slate-400 text-center font-medium">
                    💡 Klik salah satu status di legenda untuk memfilter daftar nama.
                </div>
            </div>

            <!-- RIGHT: EMPLOYEE LIST REAL-TIME (7 COLS) -->
            <div class="lg:col-span-7 bg-white/90 backdrop-blur-xl rounded-3xl p-5 md:p-6 border border-slate-200/80 shadow-sm flex flex-col">
                
                <!-- Filter Pills Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-black text-slate-800">Daftar Pegawai Hari Ini</h3>
                        <p class="text-xs text-slate-400 font-medium">
                            Menampilkan {{ filteredEmployees.length }} pegawai
                        </p>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input 
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama atau jabatan..."
                            class="w-full pl-9 pr-3 py-2 text-xs font-bold rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
                        />
                    </div>
                </div>

                <!-- Status Tabs Navigation -->
                <div class="flex flex-wrap gap-1.5 py-3 border-b border-slate-100">
                    <button 
                        @click="activeFilter = 'not_checked_in'"
                        type="button"
                        class="px-3 py-1.5 rounded-xl text-xs font-black transition border flex items-center gap-1.5"
                        :class="activeFilter === 'not_checked_in' 
                            ? 'bg-rose-600 text-white border-rose-600 shadow-xs' 
                            : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100'"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                        <span>Belum Absen ({{ data?.stats?.not_checked_in || 0 }})</span>
                    </button>

                    <button 
                        @click="activeFilter = 'present'"
                        type="button"
                        class="px-3 py-1.5 rounded-xl text-xs font-black transition border flex items-center gap-1.5"
                        :class="activeFilter === 'present' 
                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs' 
                            : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                        <span>Tepat Waktu ({{ data?.stats?.present || 0 }})</span>
                    </button>

                    <button 
                        @click="activeFilter = 'late'"
                        type="button"
                        class="px-3 py-1.5 rounded-xl text-xs font-black transition border flex items-center gap-1.5"
                        :class="activeFilter === 'late' 
                            ? 'bg-amber-600 text-white border-amber-600 shadow-xs' 
                            : 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                        <span>Terlambat ({{ data?.stats?.late || 0 }})</span>
                    </button>

                    <button 
                        @click="activeFilter = 'permit'"
                        type="button"
                        class="px-3 py-1.5 rounded-xl text-xs font-black transition border flex items-center gap-1.5"
                        :class="activeFilter === 'permit' 
                            ? 'bg-purple-600 text-white border-purple-600 shadow-xs' 
                            : 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100'"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                        <span>Izin / Dinas ({{ data?.stats?.permit || 0 }})</span>
                    </button>

                    <button 
                        @click="activeFilter = 'all'"
                        type="button"
                        class="px-3 py-1.5 rounded-xl text-xs font-black transition border"
                        :class="activeFilter === 'all' 
                            ? 'bg-slate-800 text-white border-slate-800' 
                            : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                    >
                        Semua ({{ data?.stats?.total || 0 }})
                    </button>
                </div>

                <!-- Scrollable Employee Cards List -->
                <div class="flex-1 overflow-y-auto max-h-[460px] pr-1 divide-y divide-slate-100 mt-2">
                    <div 
                        v-for="emp in filteredEmployees" 
                        :key="emp.id"
                        class="py-3 px-2 rounded-2xl transition hover:bg-slate-50/90 flex items-center justify-between gap-3"
                    >
                        <!-- Employee Info -->
                        <div class="flex items-center gap-3 min-w-0">
                            <!-- Avatar -->
                            <div class="relative flex-shrink-0">
                                <img 
                                    v-if="emp.photo" 
                                    :src="emp.photo" 
                                    :alt="emp.name" 
                                    class="w-10 h-10 rounded-2xl object-cover border border-slate-200" 
                                />
                                <div 
                                    v-else 
                                    class="w-10 h-10 rounded-2xl flex items-center justify-center font-black text-xs text-teal-800 bg-teal-100/80 border border-teal-200/60"
                                >
                                    {{ getInitials(emp.name) }}
                                </div>
                                <span 
                                    class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white"
                                    :class="{
                                        'bg-rose-500': emp.status === 'not_checked_in',
                                        'bg-emerald-500': emp.status === 'present',
                                        'bg-amber-500': emp.status === 'late',
                                        'bg-purple-500': emp.status === 'permit'
                                    }"
                                ></span>
                            </div>

                            <!-- Name & Position -->
                            <div class="min-w-0">
                                <h4 class="text-xs sm:text-sm font-black text-slate-800 truncate">
                                    {{ emp.name }}
                                </h4>
                                <p class="text-[11px] font-bold text-slate-400 truncate">
                                    {{ emp.jabatan }} <span v-if="emp.nip" class="text-slate-300">· NIP {{ emp.nip }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Status & Action Pill -->
                        <div class="flex items-center gap-2 flex-shrink-0 text-right">
                            <!-- Status Badge -->
                            <div>
                                <!-- Belum Absen -->
                                <span 
                                    v-if="emp.status === 'not_checked_in'"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-black bg-rose-50 text-rose-600 border border-rose-200"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    Belum Absen
                                </span>

                                <!-- Hadir Tepat Waktu -->
                                <div v-else-if="emp.status === 'present'" class="text-right">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <CheckCircleIcon class="w-3.5 h-3.5" />
                                        Masuk {{ emp.check_in_time }}
                                    </span>
                                    <p v-if="emp.check_out_time" class="text-[10px] font-mono font-bold text-slate-400 mt-0.5">
                                        Pulang {{ emp.check_out_time }}
                                    </p>
                                </div>

                                <!-- Terlambat -->
                                <div v-else-if="emp.status === 'late'" class="text-right">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-black bg-amber-50 text-amber-700 border border-amber-200">
                                        <ClockIcon class="w-3.5 h-3.5" />
                                        Masuk {{ emp.check_in_time }}
                                    </span>
                                    <p class="text-[10px] font-black text-amber-600 mt-0.5">
                                        +{{ emp.late_minutes }} mnt
                                    </p>
                                </div>

                                <!-- Izin / Dinas -->
                                <div v-else-if="emp.status === 'permit'" class="text-right">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-black bg-purple-50 text-purple-700 border border-purple-200">
                                        <CalendarDaysIcon class="w-3.5 h-3.5" />
                                        {{ emp.status_label }}
                                    </span>
                                    <p v-if="emp.note" class="text-[10px] font-medium text-slate-400 italic mt-0.5 max-w-[120px] truncate" :title="emp.note">
                                        "{{ emp.note }}"
                                    </p>
                                </div>
                            </div>

                            <!-- WhatsApp Reminder Button (for Belum Absen with phone) -->
                            <a 
                                v-if="emp.status === 'not_checked_in' && emp.phone"
                                :href="getWaReminderLink(emp.phone, emp.name)"
                                target="_blank"
                                class="p-2 rounded-xl text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition border border-emerald-200 flex items-center justify-center"
                                title="Ingatkan via WhatsApp"
                            >
                                <ChatBubbleOvalLeftEllipsisIcon class="w-4 h-4" />
                            </a>
                        </div>

                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredEmployees.length === 0" class="py-12 text-center text-slate-400">
                        <UsersIcon class="w-10 h-10 mx-auto mb-2 text-slate-300" />
                        <p class="text-xs font-bold">Tidak ada data pegawai yang cocok</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Coba ubah kata kunci pencarian atau tab filter.</p>
                    </div>
                </div>

            </div>
        </div>

    </div>
</template>
