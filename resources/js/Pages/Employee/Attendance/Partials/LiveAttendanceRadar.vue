<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { 
    UsersIcon, CheckCircleIcon, ClockIcon, CalendarDaysIcon, 
    ExclamationCircleIcon, ArrowPathIcon, MagnifyingGlassIcon,
    ChatBubbleOvalLeftEllipsisIcon, BuildingOffice2Icon,
    ShareIcon, ClipboardDocumentCheckIcon, ChartBarIcon,
    SparklesIcon, ArrowTrendingUpIcon, FireIcon, ShieldCheckIcon,
    CheckBadgeIcon, BoltIcon, EyeIcon
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
const chartMode = ref('donut'); // 'donut' | 'timeline' | 'quorum'
const hoveredSlice = ref(null);
const currentTime = ref('');
const mobileView = ref('analytics'); // 'analytics' | 'employees' | 'both'

// Live Digital Clock (WIB)
let timer = null;
const updateClock = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
};

onMounted(() => {
    updateClock();
    timer = setInterval(updateClock, 1000);
    // On tablet / desktop, both columns are always visible via CSS md:flex
    if (typeof window !== 'undefined' && window.innerWidth >= 768) {
        mobileView.value = 'both';
    } else {
        mobileView.value = 'analytics';
    }
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

// SVG Donut Math (Compact Radius 64, Circumference ~402.12, ViewBox 160x160)
const RADIUS = 64;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;

// Computed Donut Slices
const donutSlices = computed(() => {
    const stats = props.data?.stats || {};
    const total = stats.total || 0;

    if (total === 0) return [];

    const categories = [
        { 
            key: 'present', 
            label: 'Tepat Waktu', 
            count: stats.present || 0, 
            color: '#10b981', 
            hoverColor: '#059669', 
            badgeClass: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' 
        },
        { 
            key: 'late', 
            label: 'Terlambat', 
            count: stats.late || 0, 
            color: '#f59e0b', 
            hoverColor: '#d97706', 
            badgeClass: 'bg-amber-500/15 text-amber-400 border-amber-500/30' 
        },
        { 
            key: 'permit', 
            label: 'Izin / Dinas', 
            count: stats.permit || 0, 
            color: '#8b5cf6', 
            hoverColor: '#7c3aed', 
            badgeClass: 'bg-purple-500/15 text-purple-400 border-purple-500/30' 
        },
        { 
            key: 'not_checked_in', 
            label: 'Belum Absen', 
            count: stats.not_checked_in || 0, 
            color: '#f43f5e', 
            hoverColor: '#e11d48', 
            badgeClass: 'bg-rose-500/15 text-rose-400 border-rose-500/30' 
        },
    ];

    let accumulatedOffset = 0;

    return categories.map((cat) => {
        const length = (cat.count / total) * CIRCUMFERENCE;
        const percentage = total > 0 ? Math.round((cat.count / total) * 100) : 0;
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

// Timeline / Hourly Inflow Distribution
const timelineBars = computed(() => {
    const h = props.data?.hourly_distribution || {};
    const totalHadir = (props.data?.stats?.present || 0) + (props.data?.stats?.late || 0);

    const calcPct = (count) => totalHadir > 0 ? Math.round((count / totalHadir) * 100) : 0;

    return [
        { 
            label: '< 06:30 WIB', 
            tag: 'Sangat Pagi', 
            desc: 'Early Birds',
            count: h.early || 0, 
            pct: calcPct(h.early || 0), 
            barClass: 'from-emerald-400 to-teal-500',
            dotClass: 'bg-emerald-500' 
        },
        { 
            label: '06:30 - 07:00 WIB', 
            tag: 'Jam Utama', 
            desc: 'Waktu Ideal',
            count: h.on_time || 0, 
            pct: calcPct(h.on_time || 0), 
            barClass: 'from-teal-400 to-emerald-600',
            dotClass: 'bg-teal-500' 
        },
        { 
            label: '07:01 - 07:15 WIB', 
            tag: 'Toleransi', 
            desc: 'Batas Akhir',
            count: h.grace || 0, 
            pct: calcPct(h.grace || 0), 
            barClass: 'from-amber-400 to-orange-500',
            dotClass: 'bg-amber-500' 
        },
        { 
            label: '> 07:15 WIB', 
            tag: 'Terlambat', 
            desc: 'Di Luar Toleransi',
            count: h.late || 0, 
            pct: calcPct(h.late || 0), 
            barClass: 'from-rose-400 to-red-600',
            dotClass: 'bg-rose-500' 
        },
    ];
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
            }, 300);
        },
    });
};

// Unit Switcher (Khusus Superadmin Yayasan)
const switchUnit = (newUnitId) => {
    router.get(route('attendance.index'), {
        unit_id: newUnitId,
        tab: 'live',
    }, {
        preserveState: true,
        replace: true,
    });
};

// KPI Card Click Handler
const handleKpiClick = (filterKey) => {
    activeFilter.value = filterKey;
    // On small screens, guide user directly to employee list
    if (typeof window !== 'undefined' && window.innerWidth < 768) {
        mobileView.value = 'employees';
    }
};

// Filter Toggle from Chart Slice or Legend
const setFilterFromChart = (key) => {
    activeFilter.value = activeFilter.value === key ? 'all' : key;
    // On small screens, guide user directly to employee list
    if (typeof window !== 'undefined' && window.innerWidth < 768) {
        mobileView.value = 'employees';
    }
};

// Copy WhatsApp Summary to Clipboard
const copyWhatsAppSummary = () => {
    const stats = props.data?.stats || {};
    const lists = props.data?.lists || {};
    const notCheckedInList = lists.not_checked_in || [];

    let text = `📋 *LIVE REKAP PRESENSI PEGAWAI*\n`;
    text += `🏫 *${props.data?.unit_name || 'Sekolah'}*\n`;
    text += `📅 *${props.data?.date_formatted}* (Update: ${currentTime.value})\n\n`;
    text += `📊 *Ringkasan Real-Time:*\n`;
    text += `• Total Pegawai: *${stats.total} Orang*\n`;
    text += `• Sudah Hadir: *${stats.attendance_count} Orang* (${stats.attendance_percentage}%)\n`;
    text += `  - Tepat Waktu: ${stats.present} Orang\n`;
    text += `  - Terlambat: ${stats.late} Orang\n`;
    text += `• Izin / Dinas / Sakit: *${stats.permit} Orang*\n`;
    text += `• Belum Absen: *${stats.not_checked_in} Orang*\n\n`;

    if (notCheckedInList.length > 0) {
        text += `🔴 *Daftar Belum Absen (${notCheckedInList.length} Orang):*\n`;
        notCheckedInList.forEach((emp, i) => {
            text += `${i + 1}. ${emp.name} (${emp.jabatan})\n`;
        });
        text += `\n_Mohon bapak/ibu yang belum presensi untuk segera melakukan presensi harian di SuperApp. Terima kasih._`;
    } else {
        text += `🎉 *Alhamdulillah! Seluruh pegawai telah melakukan presensi hari ini.*`;
    }

    navigator.clipboard.writeText(text).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Tersalin ke Clipboard!',
            text: 'Rekap presensi hari ini siap ditempel (paste) ke grup WhatsApp.',
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
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
};
</script>

<template>
    <div class="space-y-3 sm:space-y-3.5 pb-24 sm:pb-6">

        <!-- 1. COMPACT EXECUTIVE TOP BAR -->
        <div class="bg-gradient-to-r from-slate-900 via-emerald-950 to-slate-900 text-white rounded-2xl sm:rounded-3xl p-3 sm:p-4 shadow-md border border-emerald-800/40 relative overflow-hidden">
            <!-- Subtle accent glow -->
            <div class="absolute -right-8 -top-8 w-36 h-36 rounded-full bg-emerald-500/15 blur-2xl pointer-events-none"></div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 relative z-10">
                <!-- Left: Title, Live Status & Time -->
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl sm:rounded-2xl bg-emerald-500/20 border border-emerald-400/30 flex items-center justify-center flex-shrink-0 text-emerald-300 shadow-inner">
                        <SparklesIcon class="w-4 h-4 animate-pulse" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-ping"></span>
                                LIVE
                            </span>
                            <span class="text-[11px] font-mono font-bold text-emerald-300 tracking-tight">
                                {{ currentTime }}
                            </span>
                        </div>
                        <h2 class="text-sm sm:text-base font-black text-white tracking-tight truncate flex items-center gap-1.5 mt-0.5">
                            <span>{{ data?.unit_name }}</span>
                            <!-- Label Unit Saya untuk akun guru biasa -->
                            <span v-if="!data?.is_global_admin" class="text-[9px] font-bold text-emerald-300/90 bg-emerald-950/60 px-1.5 py-0.5 rounded border border-emerald-800/50">
                                Unit Anda
                            </span>
                        </h2>
                    </div>
                </div>

                <!-- Right: Executive Quick Actions -->
                <div class="flex items-center gap-1.5 self-end sm:self-auto">
                    <!-- Filter Unit: HANYA MUNCUL JIKA SUPERADMIN YAYASAN -->
                    <div v-if="data?.is_global_admin && data?.units?.length > 0" class="flex items-center gap-1.5 bg-slate-800/90 border border-emerald-500/40 rounded-xl px-2 py-1 text-[11px] font-bold text-slate-200 shadow-xs">
                        <BuildingOffice2Icon class="w-3 h-3 text-emerald-400" />
                        <select 
                            :value="data?.unit_id"
                            @change="switchUnit($event.target.value)"
                            class="bg-transparent text-[11px] font-black text-white border-none p-0 focus:ring-0 cursor-pointer pr-3"
                            title="Ganti Unit Pantauan (Khusus Superadmin)"
                        >
                            <option v-for="u in data.units" :key="u.id" :value="u.id" class="text-slate-900 font-bold">
                                Unit: {{ u.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Copy WhatsApp Summary -->
                    <button 
                        @click="copyWhatsAppSummary"
                        type="button"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs transition active:scale-95 cursor-pointer"
                        title="Salin rekap presensi hari ini untuk grup WhatsApp"
                    >
                        <ShareIcon class="w-3.5 h-3.5" />
                        <span>Rekap WA</span>
                    </button>

                    <!-- Refresh Button -->
                    <button 
                        @click="refreshData"
                        :disabled="isRefreshing"
                        type="button"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-black bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition active:scale-95 disabled:opacity-60 cursor-pointer"
                        title="Perbarui data detik ini"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': isRefreshing }" />
                        <span class="hidden sm:inline">Refresh</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. HIGH-DENSITY KPI STRIP (Ultra compact ~48px height, instant filter trigger) -->
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-2">
            <!-- 1. Total Pegawai -->
            <div 
                @click="handleKpiClick('all')"
                class="bg-white px-3 py-2 rounded-xl sm:rounded-2xl border transition-all cursor-pointer flex items-center justify-between shadow-xs hover:border-slate-300"
                :class="activeFilter === 'all' ? 'border-slate-800 bg-slate-50 ring-1 ring-slate-800' : 'border-slate-200/80'"
            >
                <div class="min-w-0">
                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-wider block leading-tight">Total</span>
                    <span class="text-base sm:text-lg font-black text-slate-800 font-mono">{{ data?.stats?.total || 0 }}</span>
                </div>
                <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600">
                    <UsersIcon class="w-3.5 h-3.5" />
                </div>
            </div>

            <!-- 2. Hadir Tepat Waktu -->
            <div 
                @click="handleKpiClick('present')"
                class="bg-white px-3 py-2 rounded-xl sm:rounded-2xl border transition-all cursor-pointer flex items-center justify-between shadow-xs hover:border-emerald-300"
                :class="activeFilter === 'present' ? 'border-emerald-600 bg-emerald-50/60 ring-1 ring-emerald-600' : 'border-slate-200/80'"
            >
                <div class="min-w-0">
                    <span class="text-[9px] font-black uppercase text-emerald-600 tracking-wider block leading-tight">Tepat Waktu</span>
                    <span class="text-base sm:text-lg font-black text-emerald-700 font-mono">{{ data?.stats?.present || 0 }}</span>
                </div>
                <div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <CheckCircleIcon class="w-3.5 h-3.5" />
                </div>
            </div>

            <!-- 3. Terlambat -->
            <div 
                @click="handleKpiClick('late')"
                class="bg-white px-3 py-2 rounded-xl sm:rounded-2xl border transition-all cursor-pointer flex items-center justify-between shadow-xs hover:border-amber-300"
                :class="activeFilter === 'late' ? 'border-amber-500 bg-amber-50/60 ring-1 ring-amber-500' : 'border-slate-200/80'"
            >
                <div class="min-w-0">
                    <span class="text-[9px] font-black uppercase text-amber-600 tracking-wider block leading-tight">Terlambat</span>
                    <span class="text-base sm:text-lg font-black text-amber-600 font-mono">{{ data?.stats?.late || 0 }}</span>
                </div>
                <div class="w-7 h-7 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
                    <ClockIcon class="w-3.5 h-3.5" />
                </div>
            </div>

            <!-- 4. Izin / Dinas -->
            <div 
                @click="handleKpiClick('permit')"
                class="bg-white px-3 py-2 rounded-xl sm:rounded-2xl border transition-all cursor-pointer flex items-center justify-between shadow-xs hover:border-purple-300"
                :class="activeFilter === 'permit' ? 'border-purple-600 bg-purple-50/60 ring-1 ring-purple-600' : 'border-slate-200/80'"
            >
                <div class="min-w-0">
                    <span class="text-[9px] font-black uppercase text-purple-600 tracking-wider block leading-tight">Izin / Dinas</span>
                    <span class="text-base sm:text-lg font-black text-purple-700 font-mono">{{ data?.stats?.permit || 0 }}</span>
                </div>
                <div class="w-7 h-7 rounded-lg bg-purple-50 flex items-center justify-center text-purple-600">
                    <CalendarDaysIcon class="w-3.5 h-3.5" />
                </div>
            </div>

            <!-- 5. Belum Absen (Pulsing Focus Point) -->
            <div 
                @click="handleKpiClick('not_checked_in')"
                class="col-span-2 sm:col-span-4 lg:col-span-1 bg-white px-3 py-2 rounded-xl sm:rounded-2xl border-2 transition-all cursor-pointer flex items-center justify-between shadow-xs hover:border-rose-400"
                :class="activeFilter === 'not_checked_in' ? 'border-rose-600 bg-rose-50/80 ring-1 ring-rose-600' : 'border-rose-200 bg-rose-50/30'"
            >
                <div class="min-w-0">
                    <div class="flex items-center gap-1">
                        <span class="text-[9px] font-black uppercase text-rose-600 tracking-wider block leading-tight">Belum Absen</span>
                        <span v-if="data?.stats?.not_checked_in > 0" class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                    </div>
                    <span class="text-base sm:text-lg font-black text-rose-600 font-mono">{{ data?.stats?.not_checked_in || 0 }}</span>
                </div>
                <div class="w-7 h-7 rounded-lg bg-rose-100 flex items-center justify-center text-rose-600">
                    <ExclamationCircleIcon class="w-3.5 h-3.5" />
                </div>
            </div>
        </div>

        <!-- MOBILE SEGMENTED VIEW SWITCHER (< md screens, eliminates endless scrolling!) -->
        <div class="flex md:hidden bg-slate-200/80 p-1 rounded-xl text-xs font-black">
            <button 
                @click="mobileView = 'analytics'"
                type="button"
                class="flex-1 py-1.5 px-2 rounded-lg text-center transition cursor-pointer"
                :class="mobileView === 'analytics' ? 'bg-white text-slate-800 shadow-xs' : 'text-slate-600'"
            >
                📊 Radar & Analitik
            </button>
            <button 
                @click="mobileView = 'employees'"
                type="button"
                class="flex-1 py-1.5 px-2 rounded-lg text-center transition cursor-pointer relative"
                :class="mobileView === 'employees' ? 'bg-white text-slate-800 shadow-xs' : 'text-slate-600'"
            >
                <span>👥 Daftar Pegawai</span>
                <span v-if="data?.stats?.not_checked_in > 0" class="ml-1 inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] bg-rose-500 text-white font-mono">
                    {{ data?.stats?.not_checked_in }}
                </span>
            </button>
            <button 
                @click="mobileView = 'both'"
                type="button"
                class="py-1.5 px-2.5 rounded-lg text-center transition cursor-pointer text-[10px]"
                :class="mobileView === 'both' ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-500'"
            >
                Semua
            </button>
        </div>

        <!-- 3. UNIFIED DENSE SPLIT VIEW -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3.5 items-stretch">

            <!-- ========================================== -->
            <!-- LEFT: SOPHISTICATED ANALYTICS HUB (5 COLS) -->
            <!-- ========================================== -->
            <div 
                class="md:col-span-5 bg-white rounded-2xl sm:rounded-3xl p-3.5 sm:p-4 border border-slate-200/80 shadow-xs flex-col justify-between"
                :class="{
                    'hidden md:flex': mobileView === 'employees',
                    'flex': mobileView === 'analytics' || mobileView === 'both'
                }"
            >
                <div>
                    <!-- Mode Switcher: Donut vs Jam Datang vs Kuorum Disiplin -->
                    <div class="flex items-center justify-between gap-1 pb-2.5 border-b border-slate-100">
                        <div class="min-w-0">
                            <h3 class="text-xs sm:text-sm font-black text-slate-800 truncate">Analisis Kehadiran</h3>
                            <p class="text-[10px] text-slate-400 font-medium truncate">Pantauan dinamis hari ini</p>
                        </div>

                        <!-- 3-Way Mode Segment Pills -->
                        <div class="flex p-0.5 bg-slate-100 rounded-xl border border-slate-200/80 text-[10px] font-black">
                            <button 
                                @click="chartMode = 'donut'"
                                type="button"
                                class="px-2 py-0.5 rounded-lg transition cursor-pointer"
                                :class="chartMode === 'donut' ? 'bg-white text-teal-800 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                                title="Diagram Rasio Donut"
                            >
                                Donut
                            </button>
                            <button 
                                @click="chartMode = 'timeline'"
                                type="button"
                                class="px-2 py-0.5 rounded-lg transition cursor-pointer"
                                :class="chartMode === 'timeline' ? 'bg-white text-teal-800 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                                title="Distribusi Jam Masuk"
                            >
                                Jam
                            </button>
                            <button 
                                @click="chartMode = 'quorum'"
                                type="button"
                                class="px-2 py-0.5 rounded-lg transition cursor-pointer"
                                :class="chartMode === 'quorum' ? 'bg-white text-teal-800 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                                title="Target Kuorum & Disiplin"
                            >
                                Kuorum
                            </button>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- MODE 1: DYNAMIC INSPECTABLE SVG DONUT RADAR     -->
                    <!-- ============================================== -->
                    <div v-if="chartMode === 'donut'" class="pt-1 pb-2">
                        <!-- SVG Ring Container with Dynamic Center Inspection -->
                        <div class="relative flex items-center justify-center my-1 sm:my-2">
                            <svg class="w-40 h-40 sm:w-44 sm:h-44 transform -rotate-90 drop-shadow-xs" viewBox="0 0 160 160">
                                <!-- Base Ring Track -->
                                <circle
                                    cx="80"
                                    cy="80"
                                    :r="RADIUS"
                                    stroke="#f1f5f9"
                                    stroke-width="14"
                                    fill="transparent"
                                />

                                <!-- Interactive Category Arcs -->
                                <template v-if="data?.stats?.total > 0">
                                    <circle
                                        v-for="slice in donutSlices"
                                        :key="slice.key"
                                        cx="80"
                                        cy="80"
                                        :r="RADIUS"
                                        :stroke="slice.color"
                                        :stroke-width="hoveredSlice?.key === slice.key || activeFilter === slice.key ? 18 : 14"
                                        fill="transparent"
                                        :stroke-dasharray="slice.dasharray"
                                        :stroke-dashoffset="slice.dashoffset"
                                        class="transition-all duration-300 cursor-pointer"
                                        :class="{
                                            'opacity-40': hoveredSlice && hoveredSlice.key !== slice.key,
                                            'opacity-100': !hoveredSlice || hoveredSlice.key === slice.key
                                        }"
                                        @mouseenter="hoveredSlice = slice"
                                        @mouseleave="hoveredSlice = null"
                                        @click="setFilterFromChart(slice.key)"
                                    />
                                </template>
                            </svg>

                            <!-- Centered Dynamic Inspection Dial -->
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center px-4">
                                <!-- Hovered Inspection State -->
                                <template v-if="hoveredSlice">
                                    <span class="text-[10px] font-black uppercase tracking-wider block" :style="{ color: hoveredSlice.color }">
                                        {{ hoveredSlice.label }}
                                    </span>
                                    <span class="text-2xl sm:text-3xl font-black font-mono text-slate-800 tracking-tight leading-none my-0.5">
                                        {{ hoveredSlice.count }}
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-400">
                                        {{ hoveredSlice.percentage }}% dari Total
                                    </span>
                                    <span class="text-[8px] font-bold text-slate-400 mt-1 uppercase tracking-widest">
                                        Klik untuk filter
                                    </span>
                                </template>

                                <!-- Default Overall State -->
                                <template v-else>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                        Tingkat Kehadiran
                                    </span>
                                    <span class="text-2xl sm:text-3xl font-black font-mono text-slate-800 tracking-tight leading-none my-0.5">
                                        {{ data?.stats?.attendance_percentage || 0 }}%
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-500">
                                        {{ data?.stats?.attendance_count || 0 }}/{{ data?.stats?.total || 0 }} Hadir
                                    </span>
                                    <span 
                                        class="text-[8px] font-black uppercase px-2 py-0.5 rounded-full mt-1 border"
                                        :class="data?.stats?.quorum_status === 'achieved' 
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                                            : 'bg-amber-50 text-amber-700 border-amber-200'"
                                    >
                                        {{ data?.stats?.quorum_status === 'achieved' ? 'Kuorum Tercapai' : 'Menuju Kuorum' }}
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- Micro Interactive Legend Pills -->
                        <div class="grid grid-cols-2 gap-1.5 mt-1.5">
                            <div 
                                v-for="slice in donutSlices" 
                                :key="'legend-'+slice.key"
                                @click="setFilterFromChart(slice.key)"
                                class="flex items-center justify-between px-2 py-1 rounded-xl transition cursor-pointer border text-xs"
                                :class="activeFilter === slice.key 
                                    ? 'bg-slate-50 border-slate-400 shadow-xs font-black ring-1 ring-slate-400' 
                                    : 'border-slate-100 hover:bg-slate-50 font-semibold'"
                            >
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ backgroundColor: slice.color }"></span>
                                    <span class="truncate text-slate-700 text-[10px] sm:text-[11px]">{{ slice.label }}</span>
                                </div>
                                <span class="font-mono text-[10px] sm:text-[11px] text-slate-800 font-bold ml-1">{{ slice.count }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- MODE 2: HOURLY INFLOW & TIME VELOCITY MATRIX   -->
                    <!-- ============================================== -->
                    <div v-else-if="chartMode === 'timeline'" class="pt-1 pb-1 space-y-2">
                        <!-- Peak Time Banner -->
                        <div class="px-2.5 py-1.5 bg-gradient-to-r from-teal-50 to-emerald-50 rounded-xl border border-teal-100 flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <FireIcon class="w-3.5 h-3.5 text-amber-600 animate-bounce" />
                                <span class="text-[11px] font-black text-teal-900">Puncak Masuk</span>
                            </div>
                            <span class="text-[11px] font-black font-mono text-teal-700">
                                {{ data?.insights?.peak_period || 'Belum ada data' }}
                            </span>
                        </div>

                        <!-- 4 Time Windows Progress Bars -->
                        <div class="space-y-1.5 pt-1">
                            <div v-for="bar in timelineBars" :key="bar.label" class="space-y-0.5">
                                <div class="flex items-center justify-between text-[11px] font-bold">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="bar.dotClass"></span>
                                        <span class="text-slate-700">{{ bar.label }}</span>
                                        <span class="text-[9px] text-slate-400 font-medium">({{ bar.tag }})</span>
                                    </div>
                                    <span class="font-mono text-slate-800 text-[10px]">
                                        {{ bar.count }} staf <span class="text-slate-400">({{ bar.pct }}%)</span>
                                    </span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div 
                                        :class="'bg-gradient-to-r ' + bar.barClass" 
                                        class="h-full rounded-full transition-all duration-700" 
                                        :style="{ width: bar.pct + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- MODE 3: QUORUM & DISCIPLINE COMPLIANCE GAUGE   -->
                    <!-- ============================================== -->
                    <div v-else-if="chartMode === 'quorum'" class="pt-1 pb-1 space-y-2.5">
                        <!-- Quorum Goal Progress -->
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100 space-y-1.5">
                            <div class="flex items-center justify-between text-[11px] font-black text-slate-700">
                                <span class="flex items-center gap-1">
                                    <ShieldCheckIcon class="w-3.5 h-3.5 text-emerald-600" />
                                    <span>Target Kuorum Unit ({{ data?.stats?.quorum_target || 95 }}%)</span>
                                </span>
                                <span class="font-mono text-emerald-700">{{ data?.stats?.attendance_percentage || 0 }}%</span>
                            </div>
                            <!-- Linear Progress Bar -->
                            <div class="w-full h-2 rounded-full bg-slate-200 overflow-hidden relative">
                                <div 
                                    class="h-full rounded-full bg-emerald-500 transition-all duration-700"
                                    :style="{ width: Math.min(data?.stats?.attendance_percentage || 0, 100) + '%' }"
                                ></div>
                            </div>
                            <p class="text-[10px] text-slate-500 font-medium">
                                <strong class="text-slate-700">{{ data?.stats?.attendance_count || 0 }}</strong> dari {{ data?.stats?.total || 0 }} staf telah hadir di lingkungan sekolah.
                            </p>
                        </div>

                        <!-- Punctuality Ratio Card -->
                        <div class="grid grid-cols-2 gap-2 text-center text-xs">
                            <div class="p-2 bg-emerald-50/70 border border-emerald-100 rounded-xl">
                                <span class="text-[9px] uppercase font-black text-emerald-600 block">Tingkat Ketepatan</span>
                                <span class="text-base font-black font-mono text-emerald-700">{{ data?.stats?.on_time_percentage || 0 }}%</span>
                                <span class="text-[9px] text-emerald-600/80 block">dari yang hadir</span>
                            </div>
                            <div class="p-2 bg-slate-50 border border-slate-100 rounded-xl">
                                <span class="text-[9px] uppercase font-black text-slate-500 block">Sisa Menuju 100%</span>
                                <span class="text-base font-black font-mono text-rose-600">{{ data?.stats?.not_checked_in || 0 }}</span>
                                <span class="text-[9px] text-slate-400 block">orang lagi</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Intelligence Highlights Micro-Strip -->
                <div class="mt-2 pt-2 border-t border-slate-100 grid grid-cols-2 gap-1.5 text-[10px]">
                    <div class="bg-slate-50 p-1.5 rounded-lg border border-slate-100">
                        <span class="text-slate-400 block text-[8px] uppercase font-black">Rata-rata Masuk</span>
                        <span class="font-mono font-bold text-slate-700 truncate block">{{ data?.insights?.average_check_in || '--:-- WIB' }}</span>
                    </div>
                    <div class="bg-slate-50 p-1.5 rounded-lg border border-slate-100">
                        <span class="text-slate-400 block text-[8px] uppercase font-black">Paling Awal Datang</span>
                        <span class="font-mono font-bold text-emerald-700 truncate block" :title="data?.insights?.earliest?.name">
                            {{ data?.insights?.earliest?.time || '--:--' }} · {{ data?.insights?.earliest?.name ? data.insights.earliest.name.split(' ')[0] : '-' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- RIGHT: COMPACT REAL-TIME EMPLOYEE LIST (7 COLS) -->
            <!-- ============================================== -->
            <div 
                class="md:col-span-7 bg-white rounded-2xl sm:rounded-3xl p-3.5 sm:p-4 border border-slate-200/80 shadow-xs flex-col justify-between"
                :class="{
                    'hidden md:flex': mobileView === 'analytics',
                    'flex': mobileView === 'employees' || mobileView === 'both'
                }"
            >
                <div>
                    <!-- Header: Count & Compact Search -->
                    <div class="flex items-center justify-between gap-2 pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-1.5">
                            <h3 class="text-xs sm:text-sm font-black text-slate-800">Daftar Pegawai</h3>
                            <span class="text-[9px] font-mono font-black px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                {{ filteredEmployees.length }}
                            </span>
                        </div>

                        <!-- Micro Search Bar -->
                        <div class="relative w-44 sm:w-52">
                            <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                            <input 
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari nama / NIP..."
                                class="w-full pl-7 pr-2.5 py-1 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition"
                            />
                        </div>
                    </div>

                    <!-- Micro Category Filter Tabs (Single row scrollable/wrap) -->
                    <div class="flex flex-wrap items-center gap-1 py-2 border-b border-slate-100 text-[10px] font-black">
                        <button 
                            @click="activeFilter = 'not_checked_in'"
                            type="button"
                            class="px-2 py-0.5 rounded-lg transition border flex items-center gap-1 cursor-pointer"
                            :class="activeFilter === 'not_checked_in' 
                                ? 'bg-rose-600 text-white border-rose-600 shadow-xs' 
                                : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100'"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            <span>Belum ({{ data?.stats?.not_checked_in || 0 }})</span>
                        </button>

                        <button 
                            @click="activeFilter = 'present'"
                            type="button"
                            class="px-2 py-0.5 rounded-lg transition border flex items-center gap-1 cursor-pointer"
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
                            class="px-2 py-0.5 rounded-lg transition border flex items-center gap-1 cursor-pointer"
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
                            class="px-2 py-0.5 rounded-lg transition border flex items-center gap-1 cursor-pointer"
                            :class="activeFilter === 'permit' 
                                ? 'bg-purple-600 text-white border-purple-600 shadow-xs' 
                                : 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100'"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            <span>Izin ({{ data?.stats?.permit || 0 }})</span>
                        </button>

                        <button 
                            @click="activeFilter = 'all'"
                            type="button"
                            class="px-2 py-0.5 rounded-lg transition border cursor-pointer"
                            :class="activeFilter === 'all' 
                                ? 'bg-slate-800 text-white border-slate-800' 
                                : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                        >
                            Semua ({{ data?.stats?.total || 0 }})
                        </button>
                    </div>

                    <!-- Calibrated Height Scroll Area -->
                    <div class="overflow-y-auto h-[290px] sm:h-[310px] divide-y divide-slate-100 pr-1">
                        <div 
                            v-for="emp in filteredEmployees" 
                            :key="emp.id"
                            class="py-1.5 px-1.5 rounded-xl transition hover:bg-slate-50/80 flex items-center justify-between gap-2"
                        >
                            <!-- Left: Micro Avatar & Name/Position -->
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="relative flex-shrink-0">
                                    <img 
                                        v-if="emp.photo" 
                                        :src="emp.photo" 
                                        :alt="emp.name" 
                                        class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg object-cover border border-slate-200" 
                                    />
                                    <div 
                                        v-else 
                                        class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center font-black text-[10px] text-teal-800 bg-teal-100/90 border border-teal-200/60"
                                    >
                                        {{ getInitials(emp.name) }}
                                    </div>
                                    <span 
                                        class="absolute -bottom-0.5 -right-0.5 w-2 h-2 rounded-full border border-white"
                                        :class="{
                                            'bg-rose-500': emp.status === 'not_checked_in',
                                            'bg-emerald-500': emp.status === 'present',
                                            'bg-amber-500': emp.status === 'late',
                                            'bg-purple-500': emp.status === 'permit'
                                        }"
                                    ></span>
                                </div>

                                <div class="min-w-0">
                                    <h4 class="text-xs font-black text-slate-800 truncate leading-tight">
                                        {{ emp.name }}
                                    </h4>
                                    <p class="text-[9px] sm:text-[10px] font-bold text-slate-400 truncate">
                                        {{ emp.jabatan }}
                                    </p>
                                </div>
                            </div>

                            <!-- Right: Status Badge & Instant WhatsApp Nudge -->
                            <div class="flex items-center gap-1.5 flex-shrink-0 text-right">
                                <!-- Belum Absen -->
                                <span 
                                    v-if="emp.status === 'not_checked_in'"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[9px] font-black bg-rose-50 text-rose-600 border border-rose-200"
                                >
                                    Belum Absen
                                </span>

                                <!-- Tepat Waktu -->
                                <span 
                                    v-else-if="emp.status === 'present'"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[9px] font-mono font-black bg-emerald-50 text-emerald-700 border border-emerald-200"
                                >
                                    <CheckCircleIcon class="w-3 h-3 text-emerald-600" />
                                    {{ emp.check_in_time }}
                                </span>

                                <!-- Terlambat -->
                                <span 
                                    v-else-if="emp.status === 'late'"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[9px] font-mono font-black bg-amber-50 text-amber-700 border border-amber-200"
                                >
                                    <ClockIcon class="w-3 h-3 text-amber-600" />
                                    {{ emp.check_in_time }} <span class="text-[8px] text-amber-600 font-bold">(+{{ emp.late_minutes }}m)</span>
                                </span>

                                <!-- Izin / Dinas -->
                                <span 
                                    v-else-if="emp.status === 'permit'"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[9px] font-black bg-purple-50 text-purple-700 border border-purple-200" 
                                    :title="emp.note || ''"
                                >
                                    {{ emp.status_label }}
                                </span>

                                <!-- Direct WhatsApp Reminder Button for Un-checked-in Employees -->
                                <a 
                                    v-if="emp.status === 'not_checked_in' && emp.phone"
                                    :href="getWaReminderLink(emp.phone, emp.name)"
                                    target="_blank"
                                    class="p-1 rounded-md text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition border border-emerald-200 flex items-center justify-center cursor-pointer active:scale-95"
                                    title="Kirim pengingat presensi ke WhatsApp staf ini"
                                >
                                    <ChatBubbleOvalLeftEllipsisIcon class="w-3.5 h-3.5" />
                                </a>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="filteredEmployees.length === 0" class="py-8 text-center text-slate-400">
                            <UsersIcon class="w-7 h-7 mx-auto mb-1 text-slate-300" />
                            <p class="text-xs font-bold text-slate-600">Tidak ada staf dalam filter ini</p>
                            <p class="text-[10px] text-slate-400">Pilih kategori lain atau hapus kata kunci pencarian.</p>
                        </div>
                    </div>
                </div>

                <!-- Footer Action Bar -->
                <div class="pt-2 mt-1 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400 font-bold">
                    <span>Filter: <strong class="text-slate-700 uppercase">{{ activeFilter.replace('_', ' ') }}</strong></span>
                    <button 
                        v-if="data?.stats?.not_checked_in > 0"
                        @click="copyWhatsAppSummary" 
                        type="button"
                        class="text-teal-700 hover:text-teal-800 text-[10px] font-black inline-flex items-center gap-1 cursor-pointer transition active:scale-95"
                    >
                        <ClipboardDocumentCheckIcon class="w-3 h-3" />
                        Salin Rekap WA
                    </button>
                </div>
            </div>

        </div>

    </div>
</template>
