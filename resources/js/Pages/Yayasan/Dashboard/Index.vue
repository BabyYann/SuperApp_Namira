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

            <!-- 1. COMPACT PROFILE & ATTENDANCE CARD (Guru & Pegawai Non-Pengawas) -->
            <div 
                v-else 
                class="rounded-2xl bg-gradient-to-br from-slate-900 via-teal-950 to-slate-900 p-4 text-white shadow-sm border border-teal-800/40 relative overflow-hidden"
            >
                <div class="absolute -right-8 -bottom-8 w-28 h-28 bg-teal-500/10 rounded-full blur-2xl pointer-events-none"></div>

                <!-- Baris 1: Foto Profil Mini, Salam & Role -->
                <div class="flex items-center gap-3 relative z-10">
                    <div class="relative shrink-0">
                        <div class="w-11 h-11 rounded-full overflow-hidden ring-2 ring-teal-400/40 shadow-xs bg-teal-800 flex items-center justify-center">
                            <img v-if="user?.profile_photo_url" :src="user.profile_photo_url" :alt="user?.name" class="w-full h-full object-cover">
                            <span v-else class="text-sm font-black text-white">{{ userInitials }}</span>
                        </div>
                        <span 
                            class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-slate-900" 
                            :class="userData?.attendance_status?.checked_in ? 'bg-emerald-400' : 'bg-amber-400'"
                        ></span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-teal-300 truncate">
                                {{ userData?.role_title || (teacherData?.homeroom_class ? 'Wali Kelas ' + teacherData.homeroom_class : (teacherData?.title || 'Pegawai Yayasan')) }}
                            </span>
                        </div>
                        <h3 class="font-extrabold text-base text-white tracking-tight leading-snug truncate">
                            {{ user?.name }}
                        </h3>
                        <p class="text-[11px] text-slate-400 font-medium truncate mt-0.5">
                            {{ userData?.today_date }}
                        </p>
                    </div>
                </div>

                <!-- Baris 2: Integrated Compact Attendance Status Bar (Nol Redundansi) -->
                <div class="mt-3 pt-2.5 border-t border-white/10 flex items-center justify-between gap-2 relative z-10">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-lg bg-teal-500/20 text-teal-300 flex items-center justify-center shrink-0">
                            <FingerPrintIcon class="w-4 h-4 stroke-[2.2]" />
                        </div>
                        <div class="text-xs truncate">
                            <template v-if="userData?.attendance_status?.checked_in">
                                <span class="text-slate-300 font-medium">Presensi: </span>
                                <span class="font-black text-emerald-400">{{ userData.attendance_status.time }} WIB</span>
                                <span class="text-[10px] text-emerald-300/80 ml-1 font-semibold">({{ userData.attendance_status.status }})</span>
                            </template>
                            <template v-else>
                                <span class="text-amber-300 font-black">Belum Presensi Masuk Hari Ini</span>
                            </template>
                        </div>
                    </div>

                    <Link 
                        :href="safeRoute('attendance.index')"
                        class="shrink-0 text-[11px] font-extrabold px-3 py-1 rounded-lg bg-teal-500/20 hover:bg-teal-500/30 text-teal-200 border border-teal-400/30 transition-all active:scale-95 flex items-center gap-1"
                    >
                        <span>{{ userData?.attendance_status?.checked_in ? 'Detail' : 'Absen Masuk' }}</span>
                        <ChevronRightIcon class="w-3 h-3 stroke-[2.5]" />
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

            <!-- 4. MODUL MENGAJAR GURU (Jika Guru / Teacher Data Tersedia) -->
            <template v-else-if="isTeacher || teacherData">
                <!-- A. Live Mengajar Sekarang (Jika ada sesi aktif saat ini) -->
                <div 
                    v-if="teacherData?.current_schedule" 
                    class="rounded-2xl bg-gradient-to-r from-teal-800 to-emerald-800 p-4 text-white shadow-sm border border-teal-600/50 relative overflow-hidden"
                >
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-white/20 text-teal-100 backdrop-blur-xs">
                            <span class="w-2 h-2 rounded-full bg-emerald-300 animate-ping"></span>
                            <span>Mengajar Sekarang</span>
                        </span>
                        <span class="text-[11px] font-bold text-teal-200">
                            {{ teacherData.current_schedule.start_time }} - {{ teacherData.current_schedule.end_time }} WIB
                        </span>
                    </div>

                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h4 class="font-extrabold text-base text-white tracking-tight leading-tight truncate">
                                {{ teacherData.current_schedule.subject_name }}
                            </h4>
                            <p class="text-xs text-teal-100/90 font-semibold mt-1 flex items-center gap-1">
                                <MapPinIcon class="w-3.5 h-3.5 text-teal-300 shrink-0" />
                                <span class="truncate">Kelas {{ teacherData.current_schedule.classroom_name }}</span>
                            </p>
                        </div>

                        <Link 
                            :href="safeRoute('yayasan.teaching-journal.create', { schedule_id: teacherData.current_schedule.id })" 
                            class="shrink-0 py-2 px-3.5 bg-white hover:bg-teal-50 text-teal-900 font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 active:scale-95 transition-all"
                        >
                            <PencilSquareIcon class="w-4 h-4 text-teal-700" />
                            <span>Isi Jurnal</span>
                        </Link>
                    </div>
                </div>

                <!-- B. Jadwal Hari Ini (Horizontal Scrollable Timeline) -->
                <div 
                    v-if="teacherData?.schedules && teacherData.schedules.length > 0"
                    class="bg-white rounded-2xl p-3.5 border border-slate-200/80 shadow-xs space-y-2.5"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <ClockIcon class="w-4 h-4 text-teal-700" />
                            <h4 class="font-extrabold text-xs text-slate-800">Jadwal Mengajar Hari Ini</h4>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] font-extrabold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-full border border-teal-100">
                                {{ teacherData.schedules.length }} Sesi
                            </span>
                            <Link 
                                :href="safeRoute('yayasan.schedules.index')"
                                class="text-[11px] font-bold text-teal-700 hover:underline"
                            >
                                Semua →
                            </Link>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none -mx-1 px-1">
                        <div 
                            v-for="s in teacherData.schedules" 
                            :key="s.id"
                            class="shrink-0 rounded-xl p-2.5 border transition-all text-left min-w-[140px] max-w-[160px]"
                            :class="teacherData.current_schedule?.id === s.id 
                                ? 'bg-teal-50/80 border-teal-300 ring-1 ring-teal-400' 
                                : 'bg-slate-50 border-slate-200/80 hover:bg-white'"
                        >
                            <div class="flex items-center justify-between text-[10px] font-bold text-slate-400 mb-1">
                                <span>{{ s.start_time }} - {{ s.end_time }}</span>
                            </div>
                            <p class="font-extrabold text-xs text-slate-800 truncate" :title="s.subject_name">
                                {{ s.subject_name }}
                            </p>
                            <div class="flex items-center justify-between mt-1 text-[10px]">
                                <span class="font-bold text-teal-800 bg-white px-1.5 py-0.5 rounded border border-slate-200 truncate">
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
                </div>

                <!-- C. Notice jika tidak ada jadwal mengajar hari ini -->
                <div 
                    v-else-if="!teacherData?.current_schedule" 
                    class="bg-white rounded-2xl p-3 border border-slate-200/80 shadow-xs flex items-center justify-between gap-2"
                >
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-8 h-8 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center shrink-0">
                            <CalendarDaysIcon class="w-4 h-4" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-700 truncate">Tidak ada jam mengajar hari ini</p>
                            <p class="text-[10px] text-slate-400 truncate">Cek agenda & persiapan materi</p>
                        </div>
                    </div>
                    <Link 
                        :href="safeRoute('yayasan.schedules.index')" 
                        class="shrink-0 text-[11px] font-extrabold text-teal-700 bg-teal-50 hover:bg-teal-100 px-2.5 py-1 rounded-lg border border-teal-200/60"
                    >
                        Jadwal Saya →
                    </Link>
                </div>
            </template>

            <!-- 5. PINTASAN MENU CEPAT (Modern 4-Column App Launcher Grid) -->
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
                        <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-700 border border-teal-100 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                            <FingerPrintIcon class="w-6 h-6 stroke-[2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Presensi</span>
                    </Link>

                    <!-- 2. Jurnal Mapel (Guru) -->
                    <Link 
                        v-if="isTeacher || hasRole('teacher')"
                        :href="safeRoute('yayasan.teaching-journal.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 border border-emerald-100 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                            <PencilSquareIcon class="w-6 h-6 stroke-[2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Jurnal Guru</span>
                    </Link>

                    <!-- 3. Absensi Siswa (Wali Kelas) -->
                    <Link 
                        v-if="hasRole('wali_kelas') || teacherData?.homeroom_class"
                        :href="safeRoute('yayasan.student-attendance.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-700 border border-blue-100 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                            <ClipboardDocumentCheckIcon class="w-6 h-6 stroke-[2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Absen Kelas</span>
                    </Link>

                    <!-- 4. Scan Gerbang -->
                    <Link 
                        :href="safeRoute('yayasan.student-checkin.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-cyan-50 text-cyan-700 border border-cyan-100 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                            <QrCodeIcon class="w-6 h-6 stroke-[2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Scan QR</span>
                    </Link>

                    <!-- 5. Jadwal Mengajar (Guru) -->
                    <Link 
                        v-if="isTeacher || hasRole('teacher')"
                        :href="safeRoute('yayasan.schedules.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-700 border border-indigo-100 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                            <CalendarDaysIcon class="w-6 h-6 stroke-[2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Jadwal</span>
                    </Link>

                    <!-- 6. Persetujuan Absen (Kepsek / Admin) -->
                    <Link 
                        v-if="isGlobalAdmin || hasRole(['kepala_sekolah', 'admin_unit', 'pembina_yayasan', 'pengawas_yayasan', 'staff_yayasan'])"
                        :href="safeRoute('attendance-approvals.index', {}, safeRoute('yayasan.attendance-approvals.index'))"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-700 border border-amber-100 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                            <CheckCircleIcon class="w-6 h-6 stroke-[2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">ACC Izin</span>
                    </Link>

                    <!-- 7. Konseling BK -->
                    <Link 
                        v-if="hasRole(['bk', 'counseling', 'wali_kelas'])"
                        :href="safeRoute('counseling.sessions.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-700 border border-purple-100 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                            <ChatBubbleLeftRightIcon class="w-6 h-6 stroke-[2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Konseling</span>
                    </Link>

                    <!-- 8. Berita Humas -->
                    <Link 
                        v-if="hasRole('humas_unit')"
                        :href="safeRoute('public-relations.news.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-700 border border-sky-100 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                            <NewspaperIcon class="w-6 h-6 stroke-[2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Berita</span>
                    </Link>

                    <!-- 9. Sarpras (Sarpar / Admin) -->
                    <Link 
                        v-if="hasRole(['koordinator_sarpar', 'admin_unit'])"
                        :href="safeRoute('sarpar.maintenance.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-700 border border-rose-100 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                            <WrenchScrewdriverIcon class="w-6 h-6 stroke-[2]" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 tracking-tight leading-tight">Sarpras</span>
                    </Link>

                    <!-- 10. Keuangan Unit (Finance) -->
                    <Link 
                        v-if="hasRole('finance')"
                        :href="safeRoute('finance.dashboard')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 border border-emerald-100 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                            <BanknotesIcon class="w-6 h-6 stroke-[2]" />
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

