<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { 
    CalendarDaysIcon, BuildingOffice2Icon, ChevronDownIcon,
    UsersIcon, ClockIcon, DocumentTextIcon, ExclamationCircleIcon,
    MagnifyingGlassIcon, ShareIcon, ChatBubbleOvalLeftEllipsisIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    data: {
        type: Object,
        required: true,
    },
});

// State
const searchQuery = ref('');
const activeFilter = ref('all');
const activePeriod = ref('today'); // 'today' | 'week' | 'month'

// Unit Switcher (Khusus Superadmin Yayasan)
const switchUnit = (newUnitId) => {
    router.get(route('attendance.index'), {
        unit_id: newUnitId,
        date: props.data?.date,
        tab: 'live',
    }, {
        preserveState: true,
        replace: true,
    });
};

// Date Switcher (Interactive Dropdown / Native Datepicker)
const switchDate = (newDate) => {
    if (!newDate) return;
    router.get(route('attendance.index'), {
        unit_id: props.data?.unit_id,
        date: newDate,
        tab: 'live',
    }, {
        preserveState: true,
        replace: true,
    });
};

const setPeriod = (period) => {
    activePeriod.value = period;
    if (period === 'today' && !props.data?.is_today) {
        const todayStr = new Date().toISOString().split('T')[0];
        switchDate(todayStr);
    }
};

// Percentage Calculator (1 decimal place e.g. 67.4%)
const calcPct = (count) => {
    const total = props.data?.stats?.total || 0;
    if (total === 0) return '0.0';
    return ((count / total) * 100).toFixed(1);
};

// SVG Donut Math (Radius 58, Circumference ~364.42, ViewBox 160x160)
const RADIUS = 58;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;

// Computed Donut Slices (Matches exact reference arc order: Green, Orange, Purple, Red)
const donutSlices = computed(() => {
    const stats = props.data?.stats || {};
    const total = stats.total || 0;

    if (total === 0) return [];

    const categories = [
        { key: 'present', label: 'Tepat Waktu', count: stats.present || 0, color: '#10b981' },
        { key: 'late', label: 'Terlambat', count: stats.late || 0, color: '#f59e0b' },
        { key: 'permit', label: 'Izin / Dinas', count: stats.permit || 0, color: '#a855f7' },
        { key: 'not_checked_in', label: 'Belum Absen', count: stats.not_checked_in || 0, color: '#f43f5e' },
    ];

    let accumulatedOffset = 0;

    return categories.map((cat) => {
        const length = (cat.count / total) * CIRCUMFERENCE;
        const percentage = calcPct(cat.count);
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

// Breakdown Table Rows on Right side of Donut
const breakdownItems = computed(() => {
    const stats = props.data?.stats || {};
    return [
        { key: 'present', label: 'Tepat Waktu', count: stats.present || 0, percentage: calcPct(stats.present || 0), color: '#10b981' },
        { key: 'late', label: 'Terlambat', count: stats.late || 0, percentage: calcPct(stats.late || 0), color: '#f59e0b' },
        { key: 'permit', label: 'Izin / Dinas', count: stats.permit || 0, percentage: calcPct(stats.permit || 0), color: '#a855f7' },
        { key: 'not_checked_in', label: 'Belum Absen', count: stats.not_checked_in || 0, percentage: calcPct(stats.not_checked_in || 0), color: '#f43f5e' },
    ];
});

// Filtered Employees List
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

// Copy WhatsApp Summary to Clipboard
const copyWhatsAppSummary = () => {
    const stats = props.data?.stats || {};
    const lists = props.data?.lists || {};
    const notCheckedInList = lists.not_checked_in || [];

    let text = `📋 *REKAP PRESENSI KARYAWAN*\n`;
    text += `🏫 *${props.data?.unit_name || 'Sekolah'}*\n`;
    text += `📅 *${props.data?.date_formatted}*\n\n`;
    text += `📊 *Statistik Kehadiran:*\n`;
    text += `• Total Karyawan: *${stats.total} Orang*\n`;
    text += `• Tepat Waktu: *${stats.present} Orang* (${calcPct(stats.present)}%)\n`;
    text += `• Terlambat: *${stats.late} Orang* (${calcPct(stats.late)}%)\n`;
    text += `• Izin / Dinas: *${stats.permit} Orang* (${calcPct(stats.permit)}%)\n`;
    text += `• Belum Absen: *${stats.not_checked_in} Orang* (${calcPct(stats.not_checked_in)}%)\n\n`;

    if (notCheckedInList.length > 0) {
        text += `🔴 *Daftar Belum Absen (${notCheckedInList.length} Orang):*\n`;
        notCheckedInList.forEach((emp, i) => {
            text += `${i + 1}. ${emp.name} (${emp.jabatan})\n`;
        });
        text += `\n_Mohon bapak/ibu yang belum presensi untuk segera melakukan presensi di SuperApp. Terima kasih._`;
    } else {
        text += `🎉 *Alhamdulillah! Seluruh karyawan telah melakukan presensi hari ini.*`;
    }

    navigator.clipboard.writeText(text).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Tersalin ke Clipboard!',
            text: 'Rekap presensi siap ditempel (paste) ke grup WhatsApp.',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end',
        });
    }).catch(() => {
        alert('Gagal menyalin teks. Silakan coba lagi.');
    });
};

// WhatsApp Link Generator for direct reminder
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
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
};
</script>

<template>
    <div class="space-y-3 sm:space-y-4 pb-32 sm:pb-8">

        <!-- 1. DATE SELECTOR CARD (Interactive Datepicker Dropdown) -->
        <div class="bg-white rounded-2xl p-3.5 sm:p-4 border border-gray-100 shadow-sm flex items-center justify-between relative hover:border-gray-300 transition cursor-pointer">
            <div class="flex items-center gap-3 min-w-0 pointer-events-none">
                <CalendarDaysIcon class="w-6 h-6 text-gray-800 flex-shrink-0" />
                <div class="min-w-0">
                    <p class="text-[11px] font-medium text-gray-400">
                        {{ data?.is_today ? 'Hari ini' : 'Tanggal Terpilih' }}
                    </p>
                    <h4 class="text-sm sm:text-base font-bold text-gray-900 truncate">
                        {{ data?.date_formatted }}
                    </h4>
                </div>
            </div>
            <ChevronDownIcon class="w-4 h-4 text-gray-700 flex-shrink-0 pointer-events-none" />

            <!-- Invisible native date input overlay for instant native dropdown calendar -->
            <input 
                type="date" 
                :value="data?.date"
                @change="switchDate($event.target.value)"
                class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10"
                title="Pilih tanggal presensi"
            />
        </div>

        <!-- 2. UNIT SELECTOR CARD (Dropdown for Superadmin, Static for Guru Biasa) -->
        <!-- A. Untuk Super Admin Yayasan: Dropdown Aktif -->
        <div v-if="data?.is_global_admin && data?.units?.length > 0" class="bg-white rounded-2xl p-3.5 sm:p-4 border border-gray-100 shadow-sm flex items-center justify-between relative">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <BuildingOffice2Icon class="w-6 h-6 text-gray-800 flex-shrink-0" />
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-medium text-gray-400">Unit / Sekolah</p>
                    <select 
                        :value="data?.unit_id"
                        @change="switchUnit($event.target.value)"
                        class="w-full bg-transparent text-sm sm:text-base font-bold text-gray-900 border-none p-0 focus:ring-0 cursor-pointer pr-6 truncate"
                    >
                        <option v-for="u in data.units" :key="u.id" :value="u.id" class="text-gray-900">
                            {{ u.name }}
                        </option>
                    </select>
                </div>
            </div>
            <ChevronDownIcon class="w-4 h-4 text-gray-700 pointer-events-none flex-shrink-0" />
        </div>

        <!-- B. Untuk Guru Biasa / Pegawai Unit: Statis Tanpa Dropdown -->
        <div v-else class="bg-white rounded-2xl p-3.5 sm:p-4 border border-gray-100 shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-3">
                <BuildingOffice2Icon class="w-6 h-6 text-gray-800 flex-shrink-0" />
                <div>
                    <p class="text-[11px] font-medium text-gray-400">Unit / Sekolah</p>
                    <h4 class="text-sm sm:text-base font-bold text-gray-900">{{ data?.unit_name }}</h4>
                </div>
            </div>
        </div>

        <!-- 3. KPI METRIC CARDS (2x2 + 1 FULL WIDTH, Exact Match Reference) -->
        <div class="grid grid-cols-2 gap-2.5 sm:gap-3">
            <!-- 1. Total Karyawan -->
            <div 
                @click="activeFilter = 'all'"
                class="bg-white p-3.5 sm:p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between cursor-pointer hover:border-gray-200 transition"
                :class="{ 'ring-2 ring-gray-400': activeFilter === 'all' && searchQuery }"
            >
                <div>
                    <p class="text-xs font-semibold text-gray-500">Total Karyawan</p>
                    <p class="text-2xl font-black text-gray-900 mt-1 font-mono">{{ data?.stats?.total || 0 }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-500 flex-shrink-0">
                    <UsersIcon class="w-5 h-5" />
                </div>
            </div>

            <!-- 2. Tepat Waktu -->
            <div 
                @click="activeFilter = activeFilter === 'present' ? 'all' : 'present'"
                class="bg-white p-3.5 sm:p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between cursor-pointer hover:border-emerald-200 transition"
                :class="{ 'ring-2 ring-emerald-500 border-transparent': activeFilter === 'present' }"
            >
                <div>
                    <p class="text-xs font-bold text-[#00584b]">Tepat Waktu</p>
                    <p class="text-2xl font-black text-[#00584b] mt-1 font-mono">{{ data?.stats?.present || 0 }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 flex-shrink-0">
                    <ClockIcon class="w-5 h-5" />
                </div>
            </div>

            <!-- 3. Terlambat -->
            <div 
                @click="activeFilter = activeFilter === 'late' ? 'all' : 'late'"
                class="bg-white p-3.5 sm:p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between cursor-pointer hover:border-amber-200 transition"
                :class="{ 'ring-2 ring-amber-500 border-transparent': activeFilter === 'late' }"
            >
                <div>
                    <p class="text-xs font-bold text-amber-500">Terlambat</p>
                    <p class="text-2xl font-black text-amber-500 mt-1 font-mono">{{ data?.stats?.late || 0 }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center text-amber-500 flex-shrink-0">
                    <ClockIcon class="w-5 h-5" />
                </div>
            </div>

            <!-- 4. Izin / Dinas -->
            <div 
                @click="activeFilter = activeFilter === 'permit' ? 'all' : 'permit'"
                class="bg-white p-3.5 sm:p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between cursor-pointer hover:border-purple-200 transition"
                :class="{ 'ring-2 ring-purple-500 border-transparent': activeFilter === 'permit' }"
            >
                <div>
                    <p class="text-xs font-bold text-purple-600">Izin / Dinas</p>
                    <p class="text-2xl font-black text-purple-600 mt-1 font-mono">{{ data?.stats?.permit || 0 }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-purple-50 flex items-center justify-center text-purple-500 flex-shrink-0">
                    <DocumentTextIcon class="w-5 h-5" />
                </div>
            </div>

            <!-- 5. Belum Absen (Full Width Card with Red Accent Border) -->
            <div 
                @click="activeFilter = activeFilter === 'not_checked_in' ? 'all' : 'not_checked_in'"
                class="col-span-2 bg-white p-3.5 sm:p-4 rounded-2xl border border-rose-200 shadow-xs flex items-center justify-between cursor-pointer hover:border-rose-300 transition"
                :class="{ 'ring-2 ring-rose-500 border-transparent': activeFilter === 'not_checked_in' }"
            >
                <div>
                    <p class="text-xs font-bold text-rose-500">Belum Absen</p>
                    <p class="text-2xl font-black text-rose-500 mt-1 font-mono">{{ data?.stats?.not_checked_in || 0 }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-rose-50 flex items-center justify-center text-rose-500 flex-shrink-0">
                    <ExclamationCircleIcon class="w-6 h-6 stroke-2" />
                </div>
            </div>
        </div>

        <!-- 4. STATISTIK KEHADIRAN (Donut Chart & Breakdown Table, Menyamping Side-by-Side) -->
        <div class="bg-white rounded-2xl p-3.5 sm:p-5 border border-gray-100 shadow-sm space-y-4">
            <!-- Header with Timeframe Pills -->
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-gray-900">Statistik Kehadiran</h3>
                <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden text-xs">
                    <button 
                        @click="setPeriod('today')"
                        type="button" 
                        class="px-2.5 sm:px-3 py-1 sm:py-1.5 font-bold transition cursor-pointer text-xs"
                        :class="activePeriod === 'today' ? 'bg-[#e6f4f1] text-[#00695c]' : 'text-gray-500 hover:text-gray-700 bg-white'"
                    >
                        Hari Ini
                    </button>
                    <button 
                        @click="setPeriod('week')"
                        type="button" 
                        class="px-2.5 sm:px-3 py-1 sm:py-1.5 transition cursor-pointer border-l border-gray-200 text-xs"
                        :class="activePeriod === 'week' ? 'bg-[#e6f4f1] text-[#00695c] font-bold' : 'text-gray-500 hover:text-gray-700 bg-white'"
                    >
                        Minggu
                    </button>
                    <button 
                        @click="setPeriod('month')"
                        type="button" 
                        class="px-2.5 sm:px-3 py-1 sm:py-1.5 transition cursor-pointer border-l border-gray-200 text-xs"
                        :class="activePeriod === 'month' ? 'bg-[#e6f4f1] text-[#00695c] font-bold' : 'text-gray-500 hover:text-gray-700 bg-white'"
                    >
                        Bulan
                    </button>
                </div>
            </div>

            <!-- Content: Donut Chart on Left, Breakdown Rows on Right (Always Side-by-Side "Menyamping") -->
            <div class="flex flex-row items-center gap-2.5 sm:gap-6 pt-1">
                <!-- SVG Donut Chart (Always on the Left) -->
                <div class="relative w-32 h-32 sm:w-44 sm:h-44 flex-shrink-0 flex items-center justify-center">
                    <svg class="w-32 h-32 sm:w-44 sm:h-44 transform -rotate-90 drop-shadow-xs" viewBox="0 0 160 160">
                        <circle
                            cx="80"
                            cy="80"
                            :r="RADIUS"
                            stroke="#f1f5f9"
                            stroke-width="18"
                            fill="transparent"
                        />
                        <template v-if="data?.stats?.total > 0">
                            <circle
                                v-for="slice in donutSlices"
                                :key="slice.key"
                                cx="80"
                                cy="80"
                                :r="RADIUS"
                                :stroke="slice.color"
                                stroke-width="18"
                                fill="transparent"
                                :stroke-dasharray="slice.dasharray"
                                :stroke-dashoffset="slice.dashoffset"
                                class="transition-all duration-300 cursor-pointer hover:opacity-80"
                                @click="activeFilter = activeFilter === slice.key ? 'all' : slice.key"
                            />
                        </template>
                    </svg>

                    <!-- Donut Center Text -->
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                        <span class="text-lg sm:text-2xl font-black text-gray-900 tracking-tight leading-none font-mono">
                            {{ data?.stats?.attendance_percentage || 0 }}%
                        </span>
                        <span class="text-[10px] sm:text-xs font-semibold text-gray-500 mt-0.5 sm:mt-1">Kehadiran</span>
                        <span class="text-[9px] sm:text-[11px] font-normal text-gray-400 mt-0.5">
                            {{ data?.stats?.attendance_count || 0 }} dari {{ data?.stats?.total || 0 }}
                        </span>
                    </div>
                </div>

                <!-- 4 Breakdown Rows (Always on the Right) -->
                <div class="w-full flex-1 space-y-2 sm:space-y-3 min-w-0">
                    <div 
                        v-for="item in breakdownItems" 
                        :key="item.key"
                        @click="activeFilter = activeFilter === item.key ? 'all' : item.key"
                        class="flex items-center justify-between text-xs cursor-pointer hover:bg-slate-50 p-1 sm:p-1.5 rounded-lg transition"
                        :class="{ 'bg-slate-50 ring-1 ring-slate-200 font-bold': activeFilter === item.key }"
                    >
                        <div class="flex items-center gap-1.5 sm:gap-2.5 min-w-0">
                            <span class="w-2 sm:w-2.5 h-2 sm:h-2.5 rounded-full flex-shrink-0" :style="{ backgroundColor: item.color }"></span>
                            <span class="text-gray-700 font-medium truncate text-[11px] sm:text-xs">{{ item.label }}</span>
                        </div>
                        <div class="flex items-center gap-2 sm:gap-4 flex-shrink-0">
                            <span class="font-bold text-gray-900 font-mono text-[11px] sm:text-xs w-4 sm:w-6 text-right">{{ item.count }}</span>
                            <span class="text-gray-400 font-medium font-mono text-[11px] sm:text-xs w-9 sm:w-12 text-right">{{ item.percentage }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. DAFTAR KARYAWAN (Employee Directory, Exact Match Reference) -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-gray-100 shadow-sm space-y-3.5">
            <!-- Header with Search Input -->
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-base font-bold text-gray-900 flex-shrink-0">Daftar Karyawan</h3>
                <div class="relative flex-1 max-w-[190px] sm:max-w-xs">
                    <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input 
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama karyawan..."
                        class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border border-gray-200 bg-gray-50/50 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition"
                    />
                </div>
            </div>

            <!-- Active Filter Badge (if a KPI card is clicked) -->
            <div v-if="activeFilter !== 'all'" class="flex items-center justify-between bg-slate-50 px-3 py-1.5 rounded-xl text-xs">
                <span class="text-gray-500">
                    Menampilkan filter: <strong class="text-gray-800 uppercase">{{ activeFilter.replace('_', ' ') }}</strong> ({{ filteredEmployees.length }} orang)
                </span>
                <button @click="activeFilter = 'all'" class="text-emerald-700 font-bold hover:underline cursor-pointer">
                    Tampilkan Semua
                </button>
            </div>

            <!-- Employee List Rows -->
            <div class="divide-y divide-gray-100 max-h-[460px] overflow-y-auto pr-0.5">
                <div 
                    v-for="emp in filteredEmployees" 
                    :key="emp.id"
                    class="py-2.5 sm:py-3 flex items-center justify-between gap-3 transition hover:bg-slate-50/60 px-1 rounded-xl"
                >
                    <!-- Left: Avatar, Name, Role -->
                    <div class="flex items-center gap-3 min-w-0">
                        <img 
                            v-if="emp.photo" 
                            :src="emp.photo" 
                            :alt="emp.name" 
                            class="w-10 h-10 rounded-xl object-cover border border-gray-100 flex-shrink-0"
                        />
                        <div 
                            v-else 
                            class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center flex-shrink-0 border border-slate-200/60"
                        >
                            {{ getInitials(emp.name) }}
                        </div>

                        <div class="min-w-0">
                            <h4 class="text-sm font-bold text-gray-900 truncate leading-snug">{{ emp.name }}</h4>
                            <p class="text-xs text-gray-400 truncate mt-0.5">{{ emp.jabatan }}</p>
                        </div>
                    </div>

                    <!-- Right: Status Badge & WhatsApp Action -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span 
                            v-if="emp.status === 'not_checked_in'"
                            class="px-3 py-1 rounded-full text-xs font-semibold text-rose-500 bg-rose-50 border border-rose-100"
                        >
                            Belum Absen
                        </span>
                        <span 
                            v-else-if="emp.status === 'present'"
                            class="px-3 py-1 rounded-full text-xs font-semibold text-emerald-600 bg-emerald-50 border border-emerald-100"
                        >
                            Tepat Waktu {{ emp.check_in_time ? '(' + emp.check_in_time + ')' : '' }}
                        </span>
                        <span 
                            v-else-if="emp.status === 'late'"
                            class="px-3 py-1 rounded-full text-xs font-semibold text-amber-600 bg-amber-50 border border-amber-100"
                        >
                            Terlambat {{ emp.check_in_time ? '(' + emp.check_in_time + ')' : '' }}
                        </span>
                        <span 
                            v-else-if="emp.status === 'permit'"
                            class="px-3 py-1 rounded-full text-xs font-semibold text-purple-600 bg-purple-50 border border-purple-100"
                        >
                            {{ emp.status_label }}
                        </span>

                        <!-- WhatsApp Reminder Button for un-checked-in employees -->
                        <a 
                            v-if="emp.status === 'not_checked_in' && emp.phone"
                            :href="getWaReminderLink(emp.phone, emp.name)"
                            target="_blank"
                            class="p-1 rounded-lg text-emerald-600 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition"
                            title="Kirim pengingat WhatsApp"
                        >
                            <ChatBubbleOvalLeftEllipsisIcon class="w-4 h-4" />
                        </a>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredEmployees.length === 0" class="py-10 text-center text-gray-400">
                    <UsersIcon class="w-8 h-8 mx-auto mb-1 text-gray-300" />
                    <p class="text-xs font-bold text-gray-600">Tidak ada karyawan</p>
                    <p class="text-[11px] text-gray-400">Coba ganti filter atau periksa kata kunci pencarian.</p>
                </div>
            </div>

            <!-- Footer: Total & Copy WhatsApp Broadcast -->
            <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-xs">
                <span class="text-gray-400">Total Karyawan: <strong class="text-gray-700 font-mono">{{ data?.stats?.total || 0 }}</strong></span>
                <button 
                    @click="copyWhatsAppSummary" 
                    type="button"
                    class="text-emerald-700 hover:text-emerald-800 font-bold inline-flex items-center gap-1.5 cursor-pointer"
                >
                    <ShareIcon class="w-3.5 h-3.5" />
                    Salin Rekap WA
                </button>
            </div>
        </div>

    </div>
</template>
