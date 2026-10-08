<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const liveTime = ref('');
let timeInterval = null;

const updateLiveTime = () => {
    const now = new Date();
    liveTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':');
};

onMounted(() => {
    updateLiveTime();
    timeInterval = setInterval(updateLiveTime, 1000);
});

onUnmounted(() => {
    if (timeInterval) clearInterval(timeInterval);
});
import { 
    BuildingOffice2Icon, 
    CalendarDaysIcon, 
    UsersIcon, 
    ChartBarIcon, 
    ClockIcon, 
    AcademicCapIcon, 
    BellAlertIcon,
    PencilSquareIcon,
    ClipboardDocumentCheckIcon,
    QrCodeIcon,
    ChatBubbleLeftRightIcon,
    MapPinIcon,
    ArrowRightOnRectangleIcon,
    ChevronRightIcon,
    CheckCircleIcon,
    SparklesIcon,
    WrenchScrewdriverIcon,
    FingerPrintIcon,
    NewspaperIcon,
    GlobeAltIcon,
    BanknotesIcon,
    ShieldCheckIcon,
    HeartIcon,
    Squares2X2Icon,
    CalendarIcon,
    BookOpenIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    unitsCount: Number,
    studentsCount: Number,
    activeYear: Object,
    upcomingEvents: Array,
    teacherData: Object,
    userData: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const userRoles = computed(() => user.value?.roles || []);
const isTeacher = computed(() => user.value?.is_teacher || userRoles.value.includes('teacher'));

const hasRole = (roles) => {
    if (!Array.isArray(roles)) roles = [roles];
    if (userRoles.value.includes('super_admin_yayasan') || userRoles.value.includes('admin_yayasan')) return true;
    return roles.some(role => userRoles.value.includes(role));
};

const isGlobalAdmin = computed(() => userRoles.value.some(r => ['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan'].includes(r)));
const isPengawas = computed(() => userRoles.value.includes('pengawas_yayasan') && !userRoles.value.includes('super_admin_yayasan') && !userRoles.value.includes('admin_yayasan'));

const isDaycare = computed(() => {
    const activeUnitName = (page.props.session?.active_unit_name || '').toLowerCase();
    const userUnitName = (page.props.auth?.user?.unit?.name || '').toLowerCase();
    return page.props.session?.is_daycare === true 
        || page.props.session?.features?.daycare === true 
        || activeUnitName.includes('daycare')
        || activeUnitName.includes('day care')
        || activeUnitName.includes('pavlov')
        || userUnitName.includes('daycare')
        || userUnitName.includes('day care')
        || userUnitName.includes('pavlov');
});

const userInitials = computed(() => {
    const name = user.value?.name || 'U';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return name.substring(0, 2).toUpperCase();
});

const safeRoute = (name, params = {}, fallback = '#') => {
    try {
        if (typeof route === 'function') {
            if (route().has(name)) return route(name, params);
            if (name === 'attendance.index' && route().has('employee.attendance.index')) return route('employee.attendance.index', params);
            if (name.indexOf('employee.') !== 0 && route().has('employee.' + name)) return route('employee.' + name, params);
        }
    } catch (e) {
        console.warn(`Route ${name} missing:`, e);
    }
    return fallback;
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' });
};

const getDaysFromNow = (date) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const eventDate = new Date(date);
    eventDate.setHours(0, 0, 0, 0);
    const diff = Math.ceil((eventDate - today) / (1000 * 1000 * 60 * 60 * 24));
    if (diff === 0) return 'Hari ini';
    if (diff === 1) return 'Besok';
    return `${diff} hari lagi`;
};

const eventTypeLabels = {
    'libur': 'Libur',
    'ujian': 'Ujian',
    'event': 'Event',
    'rapat': 'Rapat',
};
</script>

<template>
    <Head title="Beranda Utama" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-extrabold text-2xl text-slate-800 leading-tight">
                Dashboard
            </h2>
            <p class="text-sm text-slate-500 mt-1">Selamat datang kembali, {{ user?.name }}</p>
        </template>

        <!-- ============================================================ -->
        <!-- 📱 NATIVE MOBILE HOME VIEW (Role-Aware for Teacher & Non-Teacher) -->
        <!-- ============================================================ -->
        <!-- ============================================================ -->
        <!-- 📱 NATIVE MOBILE HOME VIEW (Role-Aware for Teacher & Non-Teacher) -->
        <!-- ============================================================ -->
        <div class="block md:hidden space-y-3.5 pb-6">

            <!-- 1. EXECUTIVE HERO CARD FOR PENGAWAS YAYASAN -->
            <div 
                v-if="isPengawas"
                class="rounded-2xl bg-gradient-to-br from-[#0f172a] via-[#1e3a5f] to-[#0f172a] p-4 text-white shadow-sm border border-blue-900/60 relative overflow-hidden"
            >
                <div class="absolute -right-10 -top-10 w-36 h-36 bg-blue-500/10 rounded-full blur-2xl pointer-events-none"></div>

                <!-- Top Row: Avatar + Info -->
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-11 h-11 rounded-full bg-gradient-to-br from-teal-400 to-blue-600 flex items-center justify-center ring-2 ring-white/30 shadow-xs shrink-0 overflow-hidden">
                        <img v-if="user?.profile_photo_url" :src="user.profile_photo_url" :alt="user?.name" class="w-full h-full object-cover">
                        <span v-else class="text-sm font-black text-white tracking-tight">{{ userInitials }}</span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 mb-0.5">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wider bg-blue-500/20 text-blue-300 border border-blue-400/30 flex items-center gap-1">
                                <ShieldCheckIcon class="w-3 h-3 text-blue-300" />
                                <span>Pengawas Yayasan</span>
                            </span>
                        </div>
                        <h3 class="font-extrabold text-sm text-white tracking-tight leading-tight truncate">
                            {{ user?.name }}
                        </h3>
                    </div>
                </div>

                <!-- Middle Row: Metrics Pills -->
                <div class="grid grid-cols-3 gap-2 pt-2 border-t border-white/10">
                    <div class="bg-white/5 rounded-xl p-1.5 text-center border border-white/5">
                        <span class="block text-[9px] uppercase font-bold text-slate-400">Total Unit</span>
                        <span class="text-xs font-black text-white">{{ unitsCount || 5 }} Unit</span>
                    </div>
                    <div class="bg-white/5 rounded-xl p-1.5 text-center border border-white/5">
                        <span class="block text-[9px] uppercase font-bold text-slate-400">Total Siswa</span>
                        <span class="text-xs font-black text-teal-300">{{ studentsCount || 0 }}</span>
                    </div>
                    <div class="bg-white/5 rounded-xl p-1.5 text-center border border-white/5">
                        <span class="block text-[9px] uppercase font-bold text-slate-400">Mode</span>
                        <span class="text-xs font-black text-blue-300">Read-Only</span>
                    </div>
                </div>
            </div>

            <!-- 1. STANDARD HERO CARD FOR TEACHER & STAFF (Floating Portrait Pop-Out) -->
            <div v-else class="relative pt-5">

                <!-- Main Gradient Card Body -->
                <div class="relative overflow-visible rounded-3xl bg-gradient-to-br from-[#009688] to-[#0f172a] p-5 sm:p-6 border border-teal-800/60 shadow-xl min-h-[155px] flex flex-col justify-center">

                    <!-- Background Glow Accent -->
                    <div class="absolute -left-6 -bottom-6 w-40 h-40 bg-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>

                    <!-- Right Text Info (65% width to prevent name clipping) -->
                    <div class="z-20 w-[65%] ml-auto pl-2 my-0.5 text-left">
                        <p class="text-xs font-bold text-slate-300">Selamat Datang,</p>
                        <h3 class="font-black text-xl sm:text-2xl text-white tracking-tight leading-snug mt-0.5 drop-shadow-sm">
                            {{ user?.name }}
                        </h3>
                        <p class="text-xs font-bold text-teal-300 mt-1 truncate">
                            {{ userData?.role_title || (teacherData?.homeroom_class ? 'Wali Kelas ' + teacherData.homeroom_class : (teacherData?.title || 'Pegawai Yayasan')) }}
                        </p>
                    </div>

                    <!-- Action Button -->
                    <div class="pt-2.5 z-20 w-[65%] ml-auto pl-2">
                        <Link
                            :href="safeRoute('attendance.index')"
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-white hover:bg-teal-50 text-slate-950 font-extrabold text-xs rounded-full shadow-md transition-all active:scale-95 border border-slate-200"
                        >
                            <span v-if="userData?.attendance_status?.checked_in" class="flex items-center gap-1.5 text-emerald-800 font-extrabold">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Masuk {{ userData.attendance_status.time }} WIB</span>
                            </span>
                            <span v-else class="flex items-center gap-1">
                                <span>Status Presensi</span>
                                <ChevronRightIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                            </span>
                        </Link>
                    </div>

                    <!-- 🌟 KONDISI 1: ADA FOTO → Portrait Melayang Keluar Atas Card (Ukuran Pas & Proporsional) -->
                    <div
                        v-if="user?.profile_photo_url"
                        class="absolute left-2.5 bottom-0 h-[110%] w-[32%] flex items-end justify-center pointer-events-none z-30"
                    >
                        <div class="w-full max-w-[96px] h-[100%] rounded-t-3xl rounded-b-2xl overflow-hidden ring-4 ring-white/20 shadow-2xl drop-shadow-[0_15px_30px_rgba(0,0,0,0.5)]">
                            <img
                                :src="user.profile_photo_url"
                                :alt="user?.name"
                                class="w-full h-full object-cover object-top"
                            />
                        </div>
                    </div>

                    <!-- 🌟 KONDISI 2: TIDAK ADA FOTO → Circle dengan Inisial (tetap di dalam card) -->
                    <div
                        v-else
                        class="absolute left-2 bottom-0 h-[105%] w-[33%] flex items-end justify-center pointer-events-none z-10"
                    >
                        <div class="w-24 h-24 mb-2 rounded-full bg-gradient-to-br from-teal-400 via-teal-600 to-slate-800 flex items-center justify-center ring-4 ring-white/20 shadow-2xl drop-shadow-[0_10px_20px_rgba(0,0,0,0.5)]">
                            <span class="text-3xl font-black text-white tracking-tight select-none" style="text-shadow: 0 2px 8px rgba(0,0,0,0.4)">
                                {{ userInitials }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 🌟 3 MENU CEPAT PRESENSI: WFO, IZIN, DINAS LUAR (Hanya Tampil Jika Belum Absen Hari Ini) -->
                <div v-if="!userData?.attendance_status?.checked_in" class="grid grid-cols-3 gap-2 mt-2.5">
                    <!-- 1. WFO (Hadir) -->
                    <Link 
                        :href="safeRoute('attendance.index', { tab: 'present' })"
                        class="bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white rounded-2xl py-2 px-2 shadow-sm shadow-emerald-700/20 flex flex-col items-center justify-center gap-1 active:scale-95 transition-all border border-emerald-400/30 text-center"
                    >
                        <div class="w-7 h-7 rounded-xl bg-white/20 flex items-center justify-center">
                            <BuildingOffice2Icon class="w-4 h-4 text-white stroke-[2.2]" />
                        </div>
                        <span class="text-[11px] font-extrabold tracking-tight">WFO (Hadir)</span>
                    </Link>

                    <!-- 2. Izin / Sakit -->
                    <Link 
                        :href="safeRoute('attendance.index', { tab: 'permit' })"
                        class="bg-white hover:bg-purple-50 text-purple-900 rounded-2xl py-2 px-2 shadow-2xs border border-purple-200/80 flex flex-col items-center justify-center gap-1 active:scale-95 transition-all text-center"
                    >
                        <div class="w-7 h-7 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center">
                            <ClipboardDocumentCheckIcon class="w-4 h-4 stroke-[2.2]" />
                        </div>
                        <span class="text-[11px] font-extrabold tracking-tight">Izin / Sakit</span>
                    </Link>

                    <!-- 3. Dinas Luar -->
                    <Link 
                        :href="safeRoute('attendance.index', { tab: 'business_trip' })"
                        class="bg-white hover:bg-blue-50 text-blue-900 rounded-2xl py-2 px-2 shadow-2xs border border-blue-200/80 flex flex-col items-center justify-center gap-1 active:scale-95 transition-all text-center"
                    >
                        <div class="w-7 h-7 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center">
                            <GlobeAltIcon class="w-4 h-4 stroke-[2.2]" />
                        </div>
                        <span class="text-[11px] font-extrabold tracking-tight">Dinas Luar</span>
                    </Link>
                </div>
            </div>

            <!-- 2. SPECIAL: APP GRID KHUSUS PENGAWAS YAYASAN (10 Modul) -->
            <div v-if="isPengawas" class="space-y-2 pt-1">
                <div class="flex items-center justify-between px-1">
                    <h4 class="font-black text-xs uppercase tracking-wider text-slate-600 flex items-center gap-1.5">
                        <SparklesIcon class="w-4 h-4 text-amber-500" />
                        <span>Pintasan Modul Pengawas</span>
                    </h4>
                    <span class="text-[10px] font-bold text-slate-400">10 Modul</span>
                </div>

                <div class="bg-white rounded-2xl p-3 border border-slate-200/80 shadow-xs grid grid-cols-5 gap-y-3.5 gap-x-1 text-center">
                    <Link :href="safeRoute('yayasan.monitoring.index', {}, '#')" class="flex flex-col items-center gap-1 group active:scale-90 transition-transform">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shadow-2xs group-hover:scale-105 transition-transform"><ChartBarIcon class="w-5 h-5 stroke-[2.2]" /></div>
                        <span class="text-[10px] font-bold text-slate-700 tracking-tight leading-tight">Monitoring</span>
                    </Link>
                    <Link :href="safeRoute('yayasan.classrooms.index', {}, '#')" class="flex flex-col items-center gap-1 group active:scale-90 transition-transform">
                        <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center shadow-2xs group-hover:scale-105 transition-transform"><AcademicCapIcon class="w-5 h-5 stroke-[2.2]" /></div>
                        <span class="text-[10px] font-bold text-slate-700 tracking-tight leading-tight">Akademik</span>
                    </Link>
                    <Link :href="safeRoute('yayasan.employee.index', {}, '#')" class="flex flex-col items-center gap-1 group active:scale-90 transition-transform">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shadow-2xs group-hover:scale-105 transition-transform"><UsersIcon class="w-5 h-5 stroke-[2.2]" /></div>
                        <span class="text-[10px] font-bold text-slate-700 tracking-tight leading-tight">Pegawai</span>
                    </Link>
                    <Link :href="safeRoute('finance.dashboard', {}, '#')" class="flex flex-col items-center gap-1 group active:scale-90 transition-transform">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center shadow-2xs group-hover:scale-105 transition-transform"><BanknotesIcon class="w-5 h-5 stroke-[2.2]" /></div>
                        <span class="text-[10px] font-bold text-slate-700 tracking-tight leading-tight">Keuangan</span>
                    </Link>
                    <Link :href="safeRoute('public-relations.news.index')" class="flex flex-col items-center gap-1 group active:scale-90 transition-transform">
                        <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center shadow-2xs group-hover:scale-105 transition-transform"><NewspaperIcon class="w-5 h-5 stroke-[2.2]" /></div>
                        <span class="text-[10px] font-bold text-slate-700 tracking-tight leading-tight">Berita</span>
                    </Link>
                    <Link :href="safeRoute('public-relations.events.index')" class="flex flex-col items-center gap-1 group active:scale-90 transition-transform">
                        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center shadow-2xs group-hover:scale-105 transition-transform"><CalendarDaysIcon class="w-5 h-5 stroke-[2.2]" /></div>
                        <span class="text-[10px] font-bold text-slate-700 tracking-tight leading-tight">Agenda</span>
                    </Link>
                    <Link :href="safeRoute('counseling.sessions.index', {}, '#')" class="flex flex-col items-center gap-1 group active:scale-90 transition-transform">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center shadow-2xs group-hover:scale-105 transition-transform"><ChatBubbleLeftRightIcon class="w-5 h-5 stroke-[2.2]" /></div>
                        <span class="text-[10px] font-bold text-slate-700 tracking-tight leading-tight">Konseling</span>
                    </Link>
                    <Link :href="safeRoute('sarpar.dashboard', {}, '#')" class="flex flex-col items-center gap-1 group active:scale-90 transition-transform">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center shadow-2xs group-hover:scale-105 transition-transform"><BuildingOffice2Icon class="w-5 h-5 stroke-[2.2]" /></div>
                        <span class="text-[10px] font-bold text-slate-700 tracking-tight leading-tight">Sarpras</span>
                    </Link>
                    <Link :href="safeRoute('public-relations.university-destinations.index')" class="flex flex-col items-center gap-1 group active:scale-90 transition-transform">
                        <div class="w-10 h-10 rounded-xl bg-violet-50 text-violet-600 border border-violet-100 flex items-center justify-center shadow-2xs group-hover:scale-105 transition-transform"><GlobeAltIcon class="w-5 h-5 stroke-[2.2]" /></div>
                        <span class="text-[10px] font-bold text-slate-700 tracking-tight leading-tight">Kampus</span>
                    </Link>
                    <Link :href="safeRoute('yayasan.users.index')" class="flex flex-col items-center gap-1 group active:scale-90 transition-transform">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center shadow-2xs group-hover:scale-105 transition-transform"><WrenchScrewdriverIcon class="w-5 h-5 stroke-[2.2]" /></div>
                        <span class="text-[10px] font-bold text-slate-700 tracking-tight leading-tight">Pengguna</span>
                    </Link>
                </div>
            </div>

            <!-- 3. SPECIAL: DAYCARE CARE MODULE (Jika Unit Daycare) -->
            <div 
                v-else-if="isDaycare" 
                class="rounded-2xl bg-gradient-to-br from-amber-500 via-orange-500 to-amber-600 p-4 text-white shadow-sm border border-amber-400/40 space-y-3"
            >
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-0.5 rounded-full bg-white/20 text-white font-extrabold text-[10px] uppercase tracking-wider backdrop-blur-xs flex items-center gap-1.5">
                        <HeartIcon class="w-3.5 h-3.5 text-amber-200" />
                        <span>Modul Pengasuhan Daycare</span>
                    </span>
                    <span class="text-[11px] font-bold text-amber-100">Unit Aktif</span>
                </div>
                <div>
                    <h3 class="font-black text-lg tracking-tight leading-tight">
                        Pengasuhan & Care Log Ananda
                    </h3>
                    <p class="text-xs text-amber-100/90 font-medium leading-relaxed mt-0.5">
                        Presensi kedatangan/kepulangan anak, catat makan, tidur, & susu ananda.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-2.5 pt-1">
                    <Link 
                        :href="safeRoute('daycare.children.index')" 
                        class="py-2.5 px-3 bg-white hover:bg-amber-50 text-amber-900 font-extrabold text-xs rounded-xl shadow-xs flex items-center justify-center gap-2 active:scale-95 transition-all"
                    >
                        <UsersIcon class="w-4 h-4 text-amber-600" />
                        <span>Data Ananda</span>
                    </Link>
                    <Link 
                        :href="safeRoute('daycare.attendance.index')" 
                        class="py-2.5 px-3 bg-amber-900/40 hover:bg-amber-900/60 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center justify-center gap-2 border border-white/20 active:scale-95 transition-all"
                    >
                        <ClockIcon class="w-4 h-4 text-amber-200" />
                        <span>Handover</span>
                    </Link>
                </div>
            </div>

            <!-- 4. MODUL MENGAJAR GURU (Unified Smart Widget: Ringkas, Terpadu & Bebas Redundansi) -->
            <div 
                v-else-if="isTeacher || teacherData"
                class="bg-white rounded-2xl p-3.5 border border-slate-200/80 shadow-xs space-y-2.5"
            >
                <!-- Header Widget -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <ClockIcon class="w-4 h-4 text-teal-700" />
                        <h4 class="font-extrabold text-xs text-slate-800">Jadwal & Jurnal Mengajar</h4>
                        <span 
                            v-if="teacherData?.schedules && teacherData.schedules.length > 0"
                            class="text-[10px] font-extrabold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-full border border-teal-100"
                        >
                            {{ teacherData.schedules.length }} Sesi
                        </span>
                    </div>
                    <Link 
                        :href="safeRoute('yayasan.schedules.index')"
                        class="text-[11px] font-bold text-teal-700 hover:underline flex items-center gap-0.5"
                    >
                        <span>Semua</span>
                        <ChevronRightIcon class="w-3.5 h-3.5" />
                    </Link>
                </div>

                <!-- A. Sesi Aktif Saat Ini (Highlight Banner Ringkas + Tombol Cepat Isi Jurnal) -->
                <div 
                    v-if="teacherData?.current_schedule"
                    class="rounded-xl bg-gradient-to-r from-teal-800 to-emerald-900 p-3 text-white shadow-xs border border-teal-700/60 flex items-center justify-between gap-3"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 mb-0.5">
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded text-[9px] font-black uppercase tracking-wider bg-emerald-400 text-slate-950">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-950 animate-ping"></span>
                                Mengajar
                            </span>
                            <span class="text-[11px] font-bold text-teal-200">
                                {{ teacherData.current_schedule.start_time }} - {{ teacherData.current_schedule.end_time }} WIB
                            </span>
                        </div>
                        <h4 class="font-extrabold text-sm text-white tracking-tight leading-snug truncate">
                            {{ teacherData.current_schedule.subject_name }}
                        </h4>
                        <p class="text-[11px] text-teal-200 font-medium flex items-center gap-1 truncate mt-0.5">
                            <MapPinIcon class="w-3 h-3 text-teal-300 shrink-0" />
                            <span>Kelas {{ teacherData.current_schedule.classroom_name }}</span>
                        </p>
                    </div>

                    <Link 
                        :href="safeRoute('yayasan.teaching-journal.create', { schedule_id: teacherData.current_schedule.id })" 
                        class="shrink-0 py-2 px-3 bg-white hover:bg-teal-50 text-teal-900 font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 active:scale-95 transition-all"
                    >
                        <PencilSquareIcon class="w-4 h-4 text-teal-700" />
                        <span>Isi Jurnal</span>
                    </Link>
                </div>

                <!-- B. Timeline Sesi Hari Ini (Horizontal Compact Pills) -->
                <div 
                    v-if="teacherData?.schedules && teacherData.schedules.length > 0"
                    class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none -mx-1 px-1"
                >
                    <div 
                        v-for="s in teacherData.schedules" 
                        :key="s.id"
                        class="shrink-0 rounded-xl p-2 border transition-all text-left min-w-[135px] max-w-[155px]"
                        :class="teacherData.current_schedule?.id === s.id 
                            ? 'bg-teal-50/90 border-teal-300 ring-1 ring-teal-400 shadow-2xs' 
                            : 'bg-slate-50/80 border-slate-200/80 hover:bg-white'"
                    >
                        <div class="flex items-center justify-between text-[10px] font-bold text-slate-500 mb-0.5">
                            <span>{{ s.start_time }} - {{ s.end_time }}</span>
                            <span v-if="teacherData.current_schedule?.id === s.id" class="w-1.5 h-1.5 rounded-full bg-teal-600"></span>
                        </div>
                        <p class="font-extrabold text-xs text-slate-800 truncate" :title="s.subject_name">
                            {{ s.subject_name }}
                        </p>
                        <div class="flex items-center justify-between mt-1 text-[10px]">
                            <span class="font-bold text-slate-600 bg-white px-1.5 py-0.5 rounded border border-slate-200/70 truncate text-[9px]">
                                Kelas {{ s.classroom_name }}
                            </span>
                            <Link 
                                :href="safeRoute('yayasan.teaching-journal.create', { schedule_id: s.id })"
                                class="text-teal-700 font-extrabold hover:underline"
                            >
                                Jurnal →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- C. Notice jika tidak ada jadwal mengajar hari ini -->
                <div 
                    v-else
                    class="p-2.5 rounded-xl bg-slate-50 border border-dashed border-slate-200 flex items-center justify-between gap-2"
                >
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center shrink-0">
                            <CalendarDaysIcon class="w-4 h-4" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-700 truncate">Tidak ada jadwal mengajar hari ini</p>
                            <p class="text-[10px] text-slate-400 truncate">Gunakan waktu untuk persiapan materi / administrasi</p>
                        </div>
                    </div>
                    <Link 
                        :href="safeRoute('yayasan.schedules.index')" 
                        class="shrink-0 text-[11px] font-extrabold text-teal-700 hover:underline"
                    >
                        Cek Jadwal →
                    </Link>
                </div>
            </div>

            <!-- 5. PINTASAN MENU CEPAT (Luxury SuperApp 4-Column Grid) -->
            <div v-if="!isPengawas" class="bg-white rounded-2xl p-3.5 border border-slate-200/80 shadow-xs space-y-3">
                <div class="flex items-center justify-between px-0.5">
                    <h4 class="font-extrabold text-xs uppercase tracking-wider text-slate-600 flex items-center gap-1.5">
                        <Squares2X2Icon class="w-3.5 h-3.5 text-teal-700" />
                        <span>Pintasan Cepat</span>
                    </h4>
                    <span class="text-[10px] font-bold text-slate-400">Layanan Harian</span>
                </div>

                <div class="grid grid-cols-4 gap-y-3.5 gap-x-2 text-center">
                    <!-- 1. Presensi Pegawai -->
                    <Link 
                        :href="safeRoute('attendance.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-teal-600 to-emerald-500 text-white flex items-center justify-center shadow-md shadow-teal-600/30 ring-1 ring-white/25 group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <FingerPrintIcon class="w-6 h-6 stroke-[2.2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Presensi</span>
                    </Link>

                    <!-- 2. Jurnal Guru -->
                    <Link 
                        v-if="isTeacher || hasRole('teacher')"
                        :href="safeRoute('yayasan.teaching-journal.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white flex items-center justify-center shadow-md shadow-emerald-600/30 ring-1 ring-white/25 group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <PencilSquareIcon class="w-6 h-6 stroke-[2.2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Jurnal Guru</span>
                    </Link>

                    <!-- 3. Absensi Siswa (Wali Kelas / Guru) -->
                    <Link 
                        v-if="isTeacher || hasRole(['teacher', 'wali_kelas']) || teacherData?.homeroom_class"
                        :href="safeRoute('yayasan.student-attendance.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-md shadow-blue-600/30 ring-1 ring-white/25 group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <ClipboardDocumentCheckIcon class="w-6 h-6 stroke-[2.2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Absen Siswa</span>
                    </Link>

                    <!-- 4. Jadwal Mengajar -->
                    <Link 
                        v-if="isTeacher || hasRole('teacher')"
                        :href="safeRoute('yayasan.schedules.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white flex items-center justify-center shadow-md shadow-indigo-600/30 ring-1 ring-white/25 group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <CalendarDaysIcon class="w-6 h-6 stroke-[2.2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Jadwal</span>
                    </Link>

                    <!-- 5. Scan QR Gerbang -->
                    <Link 
                        :href="safeRoute('yayasan.student-checkin.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-cyan-600 to-blue-600 text-white flex items-center justify-center shadow-md shadow-cyan-600/30 ring-1 ring-white/25 group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <QrCodeIcon class="w-6 h-6 stroke-[2.2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Scan QR</span>
                    </Link>

                    <!-- 6. Agenda & Kalender Event -->
                    <Link 
                        :href="safeRoute('public-relations.events.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-violet-600 to-fuchsia-600 text-white flex items-center justify-center shadow-md shadow-violet-600/30 ring-1 ring-white/25 group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <CalendarIcon class="w-6 h-6 stroke-[2.2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Agenda</span>
                    </Link>

                    <!-- 7. Data Siswa -->
                    <Link 
                        v-if="isTeacher || hasRole(['teacher', 'wali_kelas', 'admin_unit', 'kepala_sekolah'])"
                        :href="safeRoute('yayasan.students.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-sky-600 to-cyan-500 text-white flex items-center justify-center shadow-md shadow-sky-600/30 ring-1 ring-white/25 group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <AcademicCapIcon class="w-6 h-6 stroke-[2.2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Data Siswa</span>
                    </Link>

                    <!-- 8. Konseling BK -->
                    <Link 
                        v-if="isTeacher || hasRole(['bk', 'counseling', 'wali_kelas', 'teacher'])"
                        :href="safeRoute('counseling.sessions.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-purple-600 to-pink-500 text-white flex items-center justify-center shadow-md shadow-purple-600/30 ring-1 ring-white/25 group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <ChatBubbleLeftRightIcon class="w-6 h-6 stroke-[2.2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Konseling</span>
                    </Link>

                    <!-- 9. Persetujuan Absen (Kepsek / Admin) -->
                    <Link 
                        v-if="isGlobalAdmin || hasRole(['kepala_sekolah', 'admin_unit', 'pembina_yayasan', 'pengawas_yayasan', 'staff_yayasan'])"
                        :href="safeRoute('attendance-approvals.index', {}, safeRoute('yayasan.attendance-approvals.index'))"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-500 text-white flex items-center justify-center shadow-md shadow-amber-500/30 ring-1 ring-white/25 group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <CheckCircleIcon class="w-6 h-6 stroke-[2.2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">ACC Izin</span>
                    </Link>

                    <!-- 10. Sarpras (Pemeliharaan / Fasilitas) -->
                    <Link 
                        v-if="hasRole(['koordinator_sarpar', 'admin_unit'])"
                        :href="safeRoute('sarpar.maintenance.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-rose-600 to-red-500 text-white flex items-center justify-center shadow-md shadow-rose-600/30 ring-1 ring-white/25 group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <WrenchScrewdriverIcon class="w-6 h-6 stroke-[2.2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Sarpras</span>
                    </Link>

                    <!-- 11. Berita Humas -->
                    <Link 
                        v-if="hasRole(['humas_unit', 'admin_unit'])"
                        :href="safeRoute('public-relations.news.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-slate-700 to-slate-900 text-white flex items-center justify-center shadow-md shadow-slate-700/30 ring-1 ring-white/25 group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <NewspaperIcon class="w-6 h-6 stroke-[2.2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Berita</span>
                    </Link>

                    <!-- 12. Keuangan Unit (Finance) -->
                    <Link 
                        v-if="hasRole('finance')"
                        :href="safeRoute('finance.dashboard')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-700 to-teal-800 text-white flex items-center justify-center shadow-md shadow-emerald-700/30 ring-1 ring-white/25 group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <BanknotesIcon class="w-6 h-6 stroke-[2.2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Keuangan</span>
                    </Link>
                </div>
            </div>

            <!-- 6. WIDGET RINGKASAN KEHADIRAN KELAS (Jika Wali Kelas) -->
            <div v-if="teacherData?.homeroom_stats" class="bg-white rounded-2xl p-3.5 border border-slate-200/80 shadow-xs space-y-2.5">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-extrabold text-xs text-slate-800">
                            Presensi Kelas {{ teacherData.homeroom_stats.class_name }}
                        </h4>
                        <p class="text-[10px] text-slate-400 font-medium">
                            Total {{ teacherData.homeroom_stats.total_students }} Siswa • {{ teacherData.homeroom_stats.rate }}% Hadir
                        </p>
                    </div>

                    <Link 
                        v-if="!teacherData.homeroom_stats.has_attendance"
                        :href="safeRoute('yayasan.student-attendance.show', teacherData.homeroom_stats.classroom_id)"
                        class="text-[11px] font-extrabold px-2.5 py-1 rounded-lg bg-teal-50 text-teal-800 border border-teal-200/80 hover:bg-teal-100 transition-colors"
                    >
                        Mulai Absen
                    </Link>
                </div>

                <!-- Progress Bar Mini -->
                <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div 
                        class="h-full bg-teal-700 rounded-full transition-all duration-500" 
                        :style="{ width: `${teacherData.homeroom_stats.rate}%` }"
                    ></div>
                </div>

                <!-- 4 Stats in 1 row compact -->
                <div class="grid grid-cols-4 gap-1.5 text-center pt-0.5">
                    <div class="bg-emerald-50/70 border border-emerald-100 rounded-xl py-1.5 px-1">
                        <span class="block font-black text-sm text-emerald-800 leading-none">{{ teacherData.homeroom_stats.present }}</span>
                        <span class="text-[9px] font-bold text-emerald-600">Hadir</span>
                    </div>
                    <div class="bg-blue-50/70 border border-blue-100 rounded-xl py-1.5 px-1">
                        <span class="block font-black text-sm text-blue-800 leading-none">{{ teacherData.homeroom_stats.sick }}</span>
                        <span class="text-[9px] font-bold text-blue-600">Sakit</span>
                    </div>
                    <div class="bg-amber-50/70 border border-amber-100 rounded-xl py-1.5 px-1">
                        <span class="block font-black text-sm text-amber-800 leading-none">{{ teacherData.homeroom_stats.permission }}</span>
                        <span class="text-[9px] font-bold text-amber-600">Izin</span>
                    </div>
                    <div class="bg-rose-50/70 border border-rose-100 rounded-xl py-1.5 px-1">
                        <span class="block font-black text-sm text-rose-800 leading-none">{{ teacherData.homeroom_stats.alpha }}</span>
                        <span class="text-[9px] font-bold text-rose-600">Alpa</span>
                    </div>
                </div>
            </div>

            <!-- 7. AGENDA TERDEKAT (Ringkas & Rapi) -->
            <div class="space-y-2">
                <div class="flex items-center justify-between px-0.5">
                    <h4 class="font-extrabold text-xs uppercase tracking-wider text-slate-600 flex items-center gap-1.5">
                        <CalendarDaysIcon class="w-3.5 h-3.5 text-teal-700" />
                        <span>Agenda Terdekat</span>
                    </h4>
                    <Link :href="safeRoute('yayasan.holidays.index')" class="text-[11px] font-bold text-teal-700 hover:underline">
                        Lihat Semua →
                    </Link>
                </div>

                <div v-if="upcomingEvents && upcomingEvents.length > 0" class="space-y-1.5">
                    <div 
                        v-for="event in upcomingEvents.slice(0, 3)" 
                        :key="event.id"
                        class="bg-white rounded-xl p-2.5 border border-slate-200/80 shadow-xs flex items-center justify-between gap-2.5"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-xl bg-teal-50 border border-teal-100 flex flex-col items-center justify-center text-teal-800 font-black leading-none shrink-0">
                                <span class="text-[8px] font-bold uppercase text-teal-600">{{ new Date(event.date).toLocaleDateString('id-ID', { month: 'short' }) }}</span>
                                <span class="text-xs font-black mt-0.5">{{ new Date(event.date).getDate() }}</span>
                            </div>

                            <div class="min-w-0">
                                <h5 class="font-bold text-xs text-slate-800 truncate">{{ event.description }}</h5>
                                <p class="text-[10px] text-slate-400 font-medium">
                                    {{ getDaysFromNow(event.date) }} • {{ formatDate(event.date) }}
                                </p>
                            </div>
                        </div>

                        <ChevronRightIcon class="w-3.5 h-3.5 text-slate-300 shrink-0" />
                    </div>
                </div>

                <div v-else class="bg-white rounded-xl p-4 text-center text-slate-400 border border-slate-200/80">
                    <p class="text-[11px] font-bold">Tidak ada agenda dalam 30 hari ke depan</p>
                </div>
            </div>

        </div>

        <!-- ============================================================ -->
        <!-- 💻 ORIGINAL DESKTOP DASHBOARD VIEW (Tampilan khusus Komputer/Laptop) -->
        <!-- ============================================================ -->
        <div class="hidden md:block space-y-8">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Units Stats -->
                <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] transition-all duration-300 hover:-translate-y-1 hover:shadow-lg dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-namira-teal/10 transition-all duration-500 group-hover:scale-150"></div>
                    <div class="relative z-10">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-namira-teal/10 text-namira-teal">
                            <BuildingOffice2Icon class="h-6 w-6" />
                        </div>
                        <h3 class="text-sm font-medium text-gray-500">Total Units</h3>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ unitsCount }}</p>
                    </div>
                </div>

                <!-- Active Year Stats -->
                <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] transition-all duration-300 hover:-translate-y-1 hover:shadow-lg dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-namira-blue/10 transition-all duration-500 group-hover:scale-150"></div>
                    <div class="relative z-10">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-namira-blue/10 text-namira-blue">
                            <CalendarDaysIcon class="h-6 w-6" />
                        </div>
                        <h3 class="text-sm font-medium text-gray-500">Active Academic Year</h3>
                        <div class="mt-2 flex items-baseline gap-2">
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ activeYear ? activeYear.name : 'NOT SET' }}
                            </p>
                            <span v-if="activeYear" class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                {{ activeYear.semester.toUpperCase() }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Students Stats -->
                <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] transition-all duration-300 hover:-translate-y-1 hover:shadow-lg dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-orange-500/10 transition-all duration-500 group-hover:scale-150"></div>
                    <div class="relative z-10">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-orange-500/10 text-orange-500">
                            <AcademicCapIcon class="h-6 w-6" />
                        </div>
                        <h3 class="text-sm font-medium text-gray-500">Total Siswa</h3>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ studentsCount }}</p>
                    </div>
                </div>
            </div>

            <!-- 2-Column Main Desktop Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Quick Actions -->
                <div class="lg:col-span-2 rounded-2xl bg-white p-6 shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <Link :href="safeRoute('yayasan.users.index')" class="flex flex-col items-center gap-3 p-4 bg-gray-50 hover:bg-namira-teal/10 rounded-xl transition-colors group border border-gray-100 hover:border-namira-teal/30">
                            <div class="p-3 bg-indigo-100 rounded-xl text-indigo-600 group-hover:bg-namira-teal group-hover:text-white transition-colors">
                                <UsersIcon class="w-6 h-6" />
                            </div>
                            <span class="text-sm font-bold text-gray-700 group-hover:text-namira-teal">Manajemen User</span>
                        </Link>
                        <Link :href="safeRoute('yayasan.academic-years.index')" class="flex flex-col items-center gap-3 p-4 bg-gray-50 hover:bg-namira-teal/10 rounded-xl transition-colors group border border-gray-100 hover:border-namira-teal/30">
                            <div class="p-3 bg-amber-100 rounded-xl text-amber-600 group-hover:bg-namira-teal group-hover:text-white transition-colors">
                                <CalendarDaysIcon class="w-6 h-6" />
                            </div>
                            <span class="text-sm font-bold text-gray-700 group-hover:text-namira-teal">Tahun Akademik</span>
                        </Link>
                        <Link :href="safeRoute('yayasan.monitoring.index')" class="flex flex-col items-center gap-3 p-4 bg-gray-50 hover:bg-namira-teal/10 rounded-xl transition-colors group border border-gray-100 hover:border-namira-teal/30">
                            <div class="p-3 bg-emerald-100 rounded-xl text-emerald-600 group-hover:bg-namira-teal group-hover:text-white transition-colors">
                                <ChartBarIcon class="w-6 h-6" />
                            </div>
                            <span class="text-sm font-bold text-gray-700 group-hover:text-namira-teal">Monitoring</span>
                        </Link>
                        <Link :href="safeRoute('yayasan.attendance-data.index')" class="flex flex-col items-center gap-3 p-4 bg-gray-50 hover:bg-namira-teal/10 rounded-xl transition-colors group border border-gray-100 hover:border-namira-teal/30">
                            <div class="p-3 bg-rose-100 rounded-xl text-rose-600 group-hover:bg-namira-teal group-hover:text-white transition-colors">
                                <ClockIcon class="w-6 h-6" />
                            </div>
                            <span class="text-sm font-bold text-gray-700 group-hover:text-namira-teal">Data Absensi</span>
                        </Link>
                    </div>
                </div>

                <!-- Upcoming Events Widget -->
                <div class="rounded-2xl bg-white p-6 shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <BellAlertIcon class="w-5 h-5 text-amber-500" />
                            Agenda Mendatang
                        </h3>
                        <Link :href="safeRoute('yayasan.holidays.index')" class="text-xs text-namira-teal hover:underline font-medium">
                            Lihat Semua →
                        </Link>
                    </div>
                    
                    <div v-if="upcomingEvents && upcomingEvents.length > 0" class="space-y-3">
                        <div v-for="event in upcomingEvents" :key="event.id" class="flex items-start gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors">
                            <div class="w-1 h-10 rounded-full flex-shrink-0" :style="{ backgroundColor: event.display_color }"></div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-sm text-gray-800 truncate">{{ event.description }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-gray-500">{{ formatDate(event.date) }}</span>
                                    <span class="px-1.5 py-0.5 text-[10px] rounded font-bold" 
                                          :style="{ backgroundColor: event.display_color + '20', color: event.display_color }">
                                        {{ eventTypeLabels[event.event_type] || event.event_type }}
                                    </span>
                                </div>
                            </div>
                            <span class="text-xs text-gray-400 whitespace-nowrap">{{ getDaysFromNow(event.date) }}</span>
                        </div>
                    </div>
                    
                    <div v-else class="text-center py-8 text-gray-400">
                        <CalendarDaysIcon class="w-12 h-12 mx-auto mb-2 opacity-50" />
                        <p class="text-sm">Tidak ada agenda dalam 30 hari ke depan</p>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

