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
    BookOpenIcon,
    PaperAirplaneIcon,
    ComputerDesktopIcon,
    BeakerIcon,
    CalculatorIcon,
    PaintBrushIcon
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

const todayGregorian = computed(() => {
    try {
        return new Intl.DateTimeFormat('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        }).format(new Date());
    } catch (e) {
        return '';
    }
});

const todayHijri = computed(() => {
    try {
        return new Intl.DateTimeFormat('id-ID-u-ca-islamic-umalqura', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        }).format(new Date());
    } catch (e) {
        return '';
    }
});

const todaySchedules = computed(() => props.teacherData?.schedules || []);

const nowTimeStr = computed(() => {
    return liveTime.value || '07:00';
});

const activeSchedule = computed(() => {
    if (props.teacherData?.current_schedule) return props.teacherData.current_schedule;
    if (!todaySchedules.value.length) return null;
    
    const now = nowTimeStr.value;
    const ongoingOrUpcoming = todaySchedules.value.find(s => s.end_time >= now);
    return ongoingOrUpcoming || todaySchedules.value[0];
});

const isCurrentlyTeaching = computed(() => {
    if (!activeSchedule.value) return false;
    const now = nowTimeStr.value;
    return now >= activeSchedule.value.start_time && now <= activeSchedule.value.end_time;
});

const activeScheduleDuration = computed(() => {
    if (!activeSchedule.value?.start_time || !activeSchedule.value?.end_time) return '';
    try {
        const [sh, sm] = activeSchedule.value.start_time.split(':').map(Number);
        const [eh, em] = activeSchedule.value.end_time.split(':').map(Number);
        const diffMinutes = (eh * 60 + em) - (sh * 60 + sm);
        if (diffMinutes <= 0) return '';
        const hours = Math.floor(diffMinutes / 60);
        const mins = diffMinutes % 60;
        if (hours > 0 && mins > 0) return `± ${hours} jam ${mins} menit`;
        if (hours > 0) return `± ${hours} jam`;
        return `± ${mins} menit`;
    } catch (e) {
        return '';
    }
});

const nextSchedule = computed(() => {
    if (!activeSchedule.value) return null;
    return todaySchedules.value.find(s => s.id !== activeSchedule.value.id && s.start_time >= activeSchedule.value.end_time);
});

const completedSchedule = computed(() => {
    if (!activeSchedule.value) return null;
    const now = nowTimeStr.value;
    const completed = todaySchedules.value.filter(s => s.id !== activeSchedule.value.id && s.end_time <= now);
    return completed.length ? completed[completed.length - 1] : null;
});

// ============================================================
// 🎨 SMART SUBJECT BANNER MATCHING SYSTEM (14 Banners Pack)
// ============================================================
const subjectBanners = [
    // 1. Komputer, Informatika & Teknologi (namira_banner_mixed_02)
    {
        keywords: ['komputer', 'informatika', 'tik', 'coding', 'pemrograman', 'it', 'teknologi', 'multimedia', 'software', 'hardware', 'jaringan'],
        file: 'namira_banner_mixed_02',
        borderClass: 'border-blue-700/40',
        overlayClass: 'bg-gradient-to-r from-[#071946]/95 via-[#071946]/60 to-transparent',
        badgeClass: 'bg-blue-500/20 text-blue-300 border-blue-400/30',
        badgeDotClass: 'bg-blue-400',
        iconBgClass: 'bg-blue-600/30 border-blue-400/30 text-white',
        textAccent: 'text-blue-200/90',
        subtextAccent: 'text-blue-100',
        btnIconColor: 'text-blue-700',
        iconType: 'computer',
        previewBadgeClass: 'bg-blue-50 text-blue-600',
    },
    // 2. Sains & IPA (namira_banner_mixed_08)
    {
        keywords: ['ipa', 'sains', 'fisika', 'kimia', 'biologi', 'laboratorium', 'praktikum', 'eksperimen', 'science'],
        file: 'namira_banner_mixed_08',
        borderClass: 'border-emerald-700/40',
        overlayClass: 'bg-gradient-to-r from-[#022c22]/95 via-[#022c22]/60 to-transparent',
        badgeClass: 'bg-emerald-500/20 text-emerald-300 border-emerald-400/30',
        badgeDotClass: 'bg-emerald-400',
        iconBgClass: 'bg-emerald-600/30 border-emerald-400/30 text-white',
        textAccent: 'text-emerald-200/90',
        subtextAccent: 'text-emerald-100',
        btnIconColor: 'text-emerald-700',
        iconType: 'beaker',
        previewBadgeClass: 'bg-emerald-50 text-emerald-600',
    },
    // 3. Matematika & Statistika (namira_banner_mixed_04)
    {
        keywords: ['matematika', 'mtk', 'math', 'statistika', 'ekonomi', 'akuntansi', 'berhitung', 'aljabar', 'geometri', 'kalkulus'],
        file: 'namira_banner_mixed_04',
        borderClass: 'border-purple-700/40',
        overlayClass: 'bg-gradient-to-r from-[#240e54]/95 via-[#240e54]/60 to-transparent',
        badgeClass: 'bg-purple-500/20 text-purple-300 border-purple-400/30',
        badgeDotClass: 'bg-purple-400',
        iconBgClass: 'bg-purple-600/30 border-purple-400/30 text-white',
        textAccent: 'text-purple-200/90',
        subtextAccent: 'text-purple-100',
        btnIconColor: 'text-purple-700',
        iconType: 'calculator',
        previewBadgeClass: 'bg-purple-50 text-purple-600',
    },
    // 4. Pendidikan Agama Islam / PAI / Fiqih / Arab (namira_banner_mixed_09)
    {
        keywords: ['pai', 'pendidikan agama', 'islam', 'fiqih', 'fikih', 'aqidah', 'akhlak', 'ski', 'sejarah kebudayaan islam', 'al-qur\'an', 'qur\'an', 'quran', 'hadits', 'hadis', 'arab', 'tahfidz', 'tahsin', 'tajwid'],
        file: 'namira_banner_mixed_09',
        borderClass: 'border-teal-700/40',
        overlayClass: 'bg-gradient-to-r from-[#042f2e]/95 via-[#042f2e]/60 to-transparent',
        badgeClass: 'bg-teal-500/20 text-teal-300 border-teal-400/30',
        badgeDotClass: 'bg-teal-400',
        iconBgClass: 'bg-teal-600/30 border-teal-400/30 text-white',
        textAccent: 'text-teal-200/90',
        subtextAccent: 'text-teal-100',
        btnIconColor: 'text-teal-700',
        iconType: 'book',
        previewBadgeClass: 'bg-teal-50 text-teal-700',
    },
    // 5. Bahasa & Literasi (namira_banner_mixed_03)
    {
        keywords: ['bahasa indonesia', 'indonesia', 'bahasa inggris', 'inggris', 'english', 'literasi', 'baca', 'membaca', 'perpustakaan', 'novel', 'sastra', 'puisi', 'menulis'],
        file: 'namira_banner_mixed_03',
        borderClass: 'border-amber-800/40',
        overlayClass: 'bg-gradient-to-r from-[#3f1906]/95 via-[#3f1906]/60 to-transparent',
        badgeClass: 'bg-amber-500/20 text-amber-300 border-amber-400/30',
        badgeDotClass: 'bg-amber-400',
        iconBgClass: 'bg-amber-600/30 border-amber-400/30 text-white',
        textAccent: 'text-amber-200/90',
        subtextAccent: 'text-amber-100',
        btnIconColor: 'text-amber-700',
        iconType: 'book',
        previewBadgeClass: 'bg-amber-50 text-amber-700',
    },
    // 6. Seni Budaya & Keterampilan (namira_banner_mixed_10)
    {
        keywords: ['seni', 'budaya', 'sbdp', 'seni rupa', 'seni musik', 'seni tari', 'gambar', 'menggambar', 'desain', 'kriya', 'lukis'],
        file: 'namira_banner_mixed_10',
        borderClass: 'border-cyan-800/40',
        overlayClass: 'bg-gradient-to-r from-[#083344]/95 via-[#083344]/60 to-transparent',
        badgeClass: 'bg-cyan-500/20 text-cyan-300 border-cyan-400/30',
        badgeDotClass: 'bg-cyan-400',
        iconBgClass: 'bg-cyan-600/30 border-cyan-400/30 text-white',
        textAccent: 'text-cyan-200/90',
        subtextAccent: 'text-cyan-100',
        btnIconColor: 'text-cyan-700',
        iconType: 'paint',
        previewBadgeClass: 'bg-cyan-50 text-cyan-700',
    },
    // 7. Bimbingan Konseling & E-Learning (namira_banner_mixed_11)
    {
        keywords: ['bk', 'konseling', 'bimbingan', 'literasi digital', 'e-learning', 'psikologi', 'pengembangan diri'],
        file: 'namira_banner_mixed_11',
        borderClass: 'border-purple-800/40',
        overlayClass: 'bg-gradient-to-r from-[#300a52]/95 via-[#300a52]/60 to-transparent',
        badgeClass: 'bg-purple-500/20 text-purple-300 border-purple-400/30',
        badgeDotClass: 'bg-purple-400',
        iconBgClass: 'bg-purple-600/30 border-purple-400/30 text-white',
        textAccent: 'text-purple-200/90',
        subtextAccent: 'text-purple-100',
        btnIconColor: 'text-purple-700',
        iconType: 'book',
        previewBadgeClass: 'bg-purple-50 text-purple-600',
    },
    // 8. Prakarya, PKWU & Kerja Kelompok (namira_banner_mixed_12)
    {
        keywords: ['prakarya', 'pkwu', 'kewirausahaan', 'proyek', 'kelompok', 'diskusi', 'keterampilan hidup', 'sosiologi'],
        file: 'namira_banner_mixed_12',
        borderClass: 'border-orange-800/40',
        overlayClass: 'bg-gradient-to-r from-[#3d1306]/95 via-[#3d1306]/60 to-transparent',
        badgeClass: 'bg-orange-500/20 text-orange-300 border-orange-400/30',
        badgeDotClass: 'bg-orange-400',
        iconBgClass: 'bg-orange-600/30 border-orange-400/30 text-white',
        textAccent: 'text-orange-200/90',
        subtextAccent: 'text-orange-100',
        btnIconColor: 'text-orange-700',
        iconType: 'book',
        previewBadgeClass: 'bg-orange-50 text-orange-700',
    },
    // 9. Ilmu Pengetahuan Sosial & Geografi (namira_banner_mixed_07)
    {
        keywords: ['ips', 'geografi', 'sejarah', 'alam', 'lingkungan', 'bumi', 'antropologi'],
        file: 'namira_banner_mixed_07',
        borderClass: 'border-orange-900/40',
        overlayClass: 'bg-gradient-to-r from-[#3d1306]/95 via-[#3d1306]/60 to-transparent',
        badgeClass: 'bg-orange-500/20 text-orange-300 border-orange-400/30',
        badgeDotClass: 'bg-orange-400',
        iconBgClass: 'bg-orange-600/30 border-orange-400/30 text-white',
        textAccent: 'text-orange-200/90',
        subtextAccent: 'text-orange-100',
        btnIconColor: 'text-orange-700',
        iconType: 'book',
        previewBadgeClass: 'bg-amber-50 text-amber-700',
    },
    // 10. PPKn & Tematik Kelas (namira_banner_mixed_06)
    {
        keywords: ['pkn', 'pancasila', 'pendidikan pancasila', 'kewarganegaraan', 'tematik', 'tema', 'guru kelas', 'wali kelas'],
        file: 'namira_banner_mixed_06',
        borderClass: 'border-sky-800/40',
        overlayClass: 'bg-gradient-to-r from-[#062438]/95 via-[#062438]/60 to-transparent',
        badgeClass: 'bg-sky-500/20 text-sky-300 border-sky-400/30',
        badgeDotClass: 'bg-sky-400',
        iconBgClass: 'bg-sky-600/30 border-sky-400/30 text-white',
        textAccent: 'text-sky-200/90',
        subtextAccent: 'text-sky-100',
        btnIconColor: 'text-sky-700',
        iconType: 'book',
        previewBadgeClass: 'bg-sky-50 text-sky-600',
    },
    // 11. P5, Ekskul & Belajar Mandiri (namira_banner_mixed_13)
    {
        keywords: ['p5', 'profil pelajar', 'ekskul', 'ekstrakurikuler', 'studi', 'bimbingan belajar'],
        file: 'namira_banner_mixed_13',
        borderClass: 'border-blue-900/40',
        overlayClass: 'bg-gradient-to-r from-[#0b162f]/95 via-[#0b162f]/60 to-transparent',
        badgeClass: 'bg-blue-500/20 text-blue-300 border-blue-400/30',
        badgeDotClass: 'bg-blue-400',
        iconBgClass: 'bg-blue-600/30 border-blue-400/30 text-white',
        textAccent: 'text-blue-200/90',
        subtextAccent: 'text-blue-100',
        btnIconColor: 'text-blue-700',
        iconType: 'book',
        previewBadgeClass: 'bg-blue-50 text-blue-600',
    },
    // 12. Diniyah & Kajian Malam (namira_banner_mixed_14)
    {
        keywords: ['diniyah', 'kajian', 'malam', 'pesantren malam', 'asrama', 'halaqah'],
        file: 'namira_banner_mixed_14',
        borderClass: 'border-indigo-950/40',
        overlayClass: 'bg-gradient-to-r from-[#030712]/95 via-[#030712]/60 to-transparent',
        badgeClass: 'bg-indigo-500/20 text-indigo-300 border-indigo-400/30',
        badgeDotClass: 'bg-indigo-400',
        iconBgClass: 'bg-indigo-600/30 border-indigo-400/30 text-white',
        textAccent: 'text-indigo-200/90',
        subtextAccent: 'text-indigo-100',
        btnIconColor: 'text-indigo-700',
        iconType: 'book',
        previewBadgeClass: 'bg-indigo-50 text-indigo-600',
    },
    // 13. Kampus Sekolah & Olahraga (namira_banner_mixed_05)
    {
        keywords: ['olahraga', 'pjok', 'penjaskes', 'upacara', 'lapangan', 'pembiasaan', 'senam'],
        file: 'namira_banner_mixed_05',
        borderClass: 'border-emerald-800/40',
        overlayClass: 'bg-gradient-to-r from-[#043327]/95 via-[#043327]/60 to-transparent',
        badgeClass: 'bg-emerald-500/20 text-emerald-300 border-emerald-400/30',
        badgeDotClass: 'bg-emerald-400',
        iconBgClass: 'bg-emerald-600/30 border-emerald-400/30 text-white',
        textAccent: 'text-emerald-200/90',
        subtextAccent: 'text-emerald-100',
        btnIconColor: 'text-emerald-700',
        iconType: 'book',
        previewBadgeClass: 'bg-emerald-50 text-emerald-600',
    },
    // 14. Default Pembelajaran Namira (namira_banner_mixed_01)
    {
        keywords: [],
        file: 'namira_banner_mixed_01',
        borderClass: 'border-teal-700/40',
        overlayClass: 'bg-gradient-to-r from-[#004d40]/95 via-[#004d40]/60 to-transparent',
        badgeClass: 'bg-emerald-500/20 text-emerald-300 border-emerald-400/30',
        badgeDotClass: 'bg-emerald-400',
        iconBgClass: 'bg-emerald-600/30 border-emerald-400/30 text-white',
        textAccent: 'text-teal-200/90',
        subtextAccent: 'text-teal-100',
        btnIconColor: 'text-teal-700',
        iconType: 'book',
        previewBadgeClass: 'bg-teal-50 text-teal-700',
    }
];

const getSubjectBanner = (subjectName) => {
    if (!subjectName) return subjectBanners[subjectBanners.length - 1];
    const nameLower = subjectName.toLowerCase();
    for (const item of subjectBanners) {
        if (item.keywords.some(kw => nameLower.includes(kw))) {
            return item;
        }
    }
    // Fallback hash selector among good general banners: 01, 06, 10, 05
    const hash = nameLower.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
    const fallbacks = [
        subjectBanners.find(b => b.file === 'namira_banner_mixed_01'),
        subjectBanners.find(b => b.file === 'namira_banner_mixed_06'),
        subjectBanners.find(b => b.file === 'namira_banner_mixed_10'),
        subjectBanners.find(b => b.file === 'namira_banner_mixed_05'),
    ].filter(Boolean);
    return fallbacks[hash % fallbacks.length] || subjectBanners[subjectBanners.length - 1];
};

const activeScheduleBanner = computed(() => {
    const config = getSubjectBanner(activeSchedule.value?.subject_name);
    return {
        ...config,
        webp: `/images/banners/subjects/${config.file}.webp`,
        png: `/images/banners/subjects/${config.file}.png`,
    };
});

const getScheduleIconType = (subjectName) => {
    return getSubjectBanner(subjectName)?.iconType || 'book';
};

const getScheduleIconStyle = (subjectName) => {
    return getSubjectBanner(subjectName)?.previewBadgeClass || 'bg-blue-50 text-blue-600';
};

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

const openAllMenu = () => {
    window.dispatchEvent(new CustomEvent('open-mobile-drawer'));
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

            <!-- 1. STANDARD HERO CARD FOR TEACHER & STAFF (Option A: Hierarchical 2-Row Layout with Large Avatar & Anti-Clipping) -->
            <div v-else class="relative pt-2">
                <!-- Main Gradient Card Body with Mosque Illustration Background -->
                <div class="relative overflow-hidden rounded-3xl p-4 sm:p-5 border border-teal-600/30 shadow-xl flex flex-col justify-between gap-3.5 sm:gap-4">
                    <!-- Mosque Background Image (WebP with PNG fallback) -->
                    <picture class="absolute inset-0 w-full h-full pointer-events-none select-none">
                        <source srcset="/images/banner_dashboard_bg.webp" type="image/webp">
                        <img 
                            src="/images/banner_dashboard_bg.png" 
                            alt="Namira Mosque" 
                            class="w-full h-full object-cover object-right"
                        />
                    </picture>

                    <!-- Gradient Contrast Overlay: Stronger on the left for text readability, soft on the right to show mosque architecture -->
                    <div class="absolute inset-0 bg-gradient-to-r from-[#004d40]/95 via-[#00695c]/85 to-[#004d40]/25 pointer-events-none"></div>

                    <!-- Inner Content (Z-10) -->
                    <div class="relative z-10 flex flex-col justify-between gap-3.5 sm:gap-4 h-full">
                        <!-- TOP ROW: Large Teacher Avatar & Greeting/Name (100% Full Width Available - Anti-Clipping!) -->
                        <div class="flex items-center gap-3.5 sm:gap-4">
                            <!-- Large Avatar Squircle (w-16 h-16 sm:w-20 sm:h-20) -->
                            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl sm:rounded-3xl overflow-hidden ring-2 ring-white/40 shadow-lg shrink-0 bg-teal-950/60 flex items-center justify-center">
                                <img
                                    v-if="user?.profile_photo_url"
                                    :src="user.profile_photo_url"
                                    :alt="user?.name"
                                    class="w-full h-full object-cover object-top"
                                />
                                <div v-else class="w-full h-full bg-gradient-to-br from-teal-400 to-teal-800 flex items-center justify-center font-black text-2xl text-white">
                                    {{ userInitials }}
                                </div>
                            </div>

                            <!-- Greeting & Name: Full space, bold and crisp -->
                            <div class="min-w-0 flex-1 text-left">
                                <p class="text-xs sm:text-sm font-semibold text-teal-100/90 leading-tight">Selamat Datang,</p>
                                <h3 class="font-black text-lg sm:text-xl text-white tracking-tight leading-snug drop-shadow-sm break-words line-clamp-2">
                                    {{ user?.name }}
                                </h3>
                                <p class="text-xs sm:text-sm font-bold text-teal-300 truncate mt-0.5">
                                    {{ userData?.role_title || (teacherData?.homeroom_class ? 'Wali Kelas ' + teacherData.homeroom_class : (teacherData?.title || 'Guru Pengajar')) }}
                                </p>
                            </div>
                        </div>

                        <!-- BOTTOM ROW: Date Badge (Left) & Status Presensi Pill (Right) -->
                        <div class="flex items-center justify-between gap-2 pt-2 border-t border-white/10">
                            <!-- 1. Date Card: Clean White Badge with Calendar Icon & 2-Line Date -->
                            <div class="inline-flex items-center gap-2 px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-2xl bg-white/95 backdrop-blur-xs text-slate-800 shadow-xs border border-white/80 shrink-0">
                                <CalendarDaysIcon class="w-4 h-4 text-teal-700 stroke-[2] shrink-0" />
                                <div class="leading-none text-left min-w-0">
                                    <p class="font-black text-[10px] sm:text-[11px] text-slate-800 whitespace-nowrap">{{ todayGregorian }}</p>
                                    <p class="font-semibold text-[8px] sm:text-[9px] text-slate-400 whitespace-nowrap mt-0.5">{{ todayHijri }}</p>
                                </div>
                            </div>

                            <!-- 2. Status Presensi Pill Button -->
                            <Link
                                :href="safeRoute('attendance.index')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-900 font-extrabold text-[11px] sm:text-xs rounded-full shadow-xs border border-white/80 transition-all active:scale-95 shrink-0"
                            >
                                <template v-if="userData?.attendance_status?.checked_in">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                                    <span class="text-emerald-800 font-black whitespace-nowrap">Masuk {{ userData.attendance_status.time }}</span>
                                    <ChevronRightIcon class="w-3.5 h-3.5 stroke-[2.5] text-emerald-600 shrink-0" />
                                </template>
                                <template v-else>
                                    <span class="whitespace-nowrap">Status Presensi</span>
                                    <ChevronRightIcon class="w-3.5 h-3.5 stroke-[2.5] text-slate-400 shrink-0" />
                                </template>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- 🌟 3 MENU CEPAT PRESENSI: WFO, IZIN, DINAS LUAR (Bebas Truncation Tanpa Chevron Sesak) -->
                <div v-if="!userData?.attendance_status?.checked_in" class="bg-white rounded-3xl p-2 sm:p-2.5 border border-slate-200/80 shadow-xs mt-3">
                    <div class="grid grid-cols-3 gap-1.5 sm:gap-2">
                        <!-- 1. WFO (Hadir) -->
                        <Link 
                            :href="safeRoute('attendance.index', { tab: 'present' })"
                            class="bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white rounded-2xl p-2 sm:p-2.5 flex items-center gap-1.5 sm:gap-2 shadow-xs shadow-emerald-700/20 active:scale-95 transition-all border border-emerald-400/30 text-left min-w-0"
                        >
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
                                <BuildingOffice2Icon class="w-4 h-4 text-white stroke-[2.2]" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-black text-xs text-white leading-tight">WFO</p>
                                <p class="text-[9px] font-medium text-emerald-100 leading-tight mt-0.5">Hadir</p>
                            </div>
                        </Link>

                        <!-- 2. Izin / Sakit -->
                        <Link 
                            :href="safeRoute('attendance.index', { tab: 'permit' })"
                            class="bg-pink-50/60 hover:bg-pink-50 text-slate-800 rounded-2xl p-2 sm:p-2.5 flex items-center gap-1.5 sm:gap-2 border border-pink-100 active:scale-95 transition-all text-left min-w-0"
                        >
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-pink-100 text-pink-600 flex items-center justify-center shrink-0">
                                <ClipboardDocumentCheckIcon class="w-4 h-4 stroke-[2.2]" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-black text-xs text-slate-800 leading-tight">Izin</p>
                                <p class="text-[9px] font-medium text-slate-500 leading-tight mt-0.5">Sakit</p>
                            </div>
                        </Link>

                        <!-- 3. Dinas Luar -->
                        <Link 
                            :href="safeRoute('attendance.index', { tab: 'business_trip' })"
                            class="bg-blue-50/60 hover:bg-blue-50 text-slate-800 rounded-2xl p-2 sm:p-2.5 flex items-center gap-1.5 sm:gap-2 border border-blue-100 active:scale-95 transition-all text-left min-w-0"
                        >
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                                <PaperAirplaneIcon class="w-4 h-4 stroke-[2.2]" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-black text-xs text-slate-800 leading-tight">Dinas</p>
                                <p class="text-[9px] font-medium text-slate-500 leading-tight mt-0.5">Luar</p>
                            </div>
                        </Link>
                    </div>
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

            <!-- 4. MODUL MENGAJAR GURU (Unified Smart Widget: Sesuai Mockup Referensi + Background Gambar Kelas Aktif + Anti-Sesak Pintasan Cepat) -->
            <div 
                v-else-if="isTeacher || teacherData"
                class="bg-white rounded-3xl p-3.5 sm:p-4 border border-slate-200/80 shadow-xs space-y-3"
            >
                <!-- Header Widget: Jadwal & Jurnal Mengajar -->
                <div class="flex items-center justify-between px-0.5">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-teal-50 text-teal-700 flex items-center justify-center shrink-0">
                            <ClockIcon class="w-3.5 h-3.5 stroke-[2.2]" />
                        </div>
                        <h4 class="font-extrabold text-sm sm:text-base text-slate-800 tracking-tight">Jadwal & Jurnal Mengajar</h4>
                        <span 
                            v-if="todaySchedules.length > 0"
                            class="text-[10px] sm:text-[11px] font-extrabold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-full border border-teal-100"
                        >
                            {{ todaySchedules.length }} Sesi
                        </span>
                    </div>
                    <Link 
                        :href="safeRoute('yayasan.schedules.index')"
                        class="text-xs font-extrabold text-teal-700 hover:text-teal-800 flex items-center gap-0.5 active:scale-95 transition-all"
                    >
                        <span>Semua</span>
                        <ChevronRightIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                    </Link>
                </div>

                <!-- A. Sesi Aktif Saat Ini / Highlight Card (Smart Dynamic Banner Sesuai Mapel) -->
                <div 
                    v-if="activeSchedule"
                    class="relative overflow-hidden rounded-2xl p-3.5 sm:p-4 shadow-sm min-h-[145px] flex flex-col justify-between transition-colors duration-300"
                    :class="activeScheduleBanner.borderClass"
                >
                    <!-- Classroom Background Image (WebP with PNG fallback) -->
                    <picture class="absolute inset-0 w-full h-full pointer-events-none select-none">
                        <source :srcset="activeScheduleBanner.webp" type="image/webp">
                        <img 
                            :src="activeScheduleBanner.png" 
                            :alt="activeSchedule.subject_name || 'Active Lesson Illustration'" 
                            class="w-full h-full object-cover object-right"
                        />
                    </picture>

                    <!-- Gradient Contrast Overlay: Matches subject theme palette, strong on left for crisp readability, transparent on right to showcase illustration -->
                    <div 
                        class="absolute inset-0 pointer-events-none transition-all duration-300"
                        :class="activeScheduleBanner.overlayClass"
                    ></div>

                    <!-- Inner Content (Z-10) -->
                    <div class="relative z-10 flex flex-col justify-between gap-3 h-full">
                        <!-- Top Row: Status Badge & Time -->
                        <div class="flex items-center justify-between gap-2">
                            <!-- Status Badge -->
                            <div 
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider backdrop-blur-xs transition-colors"
                                :class="activeScheduleBanner.badgeClass"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="[activeScheduleBanner.badgeDotClass, isCurrentlyTeaching ? 'animate-ping' : '']"></span>
                                <span>{{ isCurrentlyTeaching ? 'Sedang Mengajar' : 'Sesi Aktif' }}</span>
                            </div>

                            <!-- Time & Duration Info -->
                            <div class="text-right leading-tight">
                                <p class="text-xs sm:text-sm font-black text-white whitespace-nowrap drop-shadow-xs">
                                    {{ activeSchedule.start_time }} – {{ activeSchedule.end_time }}
                                </p>
                                <p v-if="activeScheduleDuration" class="text-[10px] font-semibold whitespace-nowrap mt-0.5 flex items-center justify-end gap-1" :class="activeScheduleBanner.textAccent">
                                    <ClockIcon class="w-3 h-3 stroke-[2]" />
                                    <span>{{ activeScheduleDuration }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Main Row: Dynamic Subject Icon + Subject + Location & Button Isi Jurnal -->
                        <div class="flex items-end justify-between gap-3 pt-1">
                            <!-- Left: Subject Details -->
                            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                <div 
                                    class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl backdrop-blur-xs flex items-center justify-center shrink-0 shadow-inner border transition-colors"
                                    :class="activeScheduleBanner.iconBgClass"
                                >
                                    <ComputerDesktopIcon v-if="activeScheduleBanner.iconType === 'computer'" class="w-5 h-5 sm:w-6 sm:h-6 stroke-[2]" />
                                    <BeakerIcon v-else-if="activeScheduleBanner.iconType === 'beaker'" class="w-5 h-5 sm:w-6 sm:h-6 stroke-[2]" />
                                    <CalculatorIcon v-else-if="activeScheduleBanner.iconType === 'calculator'" class="w-5 h-5 sm:w-6 sm:h-6 stroke-[2]" />
                                    <PaintBrushIcon v-else-if="activeScheduleBanner.iconType === 'paint'" class="w-5 h-5 sm:w-6 sm:h-6 stroke-[2]" />
                                    <BookOpenIcon v-else class="w-5 h-5 sm:w-6 sm:h-6 stroke-[2]" />
                                </div>
                                <div class="min-w-0 flex-1 text-left">
                                    <h4 class="font-black text-sm sm:text-base text-white tracking-tight leading-snug drop-shadow-xs line-clamp-2 break-words">
                                        {{ activeSchedule.subject_name }}
                                    </h4>
                                    <p class="text-xs font-semibold flex items-center gap-1 mt-0.5 truncate" :class="activeScheduleBanner.subtextAccent">
                                        <UsersIcon class="w-3.5 h-3.5 shrink-0 stroke-[2] opacity-80" />
                                        <span>Kelas {{ activeSchedule.classroom_name }}</span>
                                    </p>
                                    <p class="text-[11px] font-medium flex items-center gap-1 mt-0.5 truncate" :class="activeScheduleBanner.textAccent">
                                        <MapPinIcon class="w-3 h-3 stroke-[2] opacity-80" />
                                        <span>Ruang Kelas</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Right: Button Isi Jurnal (White Pill) -->
                            <Link 
                                :href="safeRoute('yayasan.teaching-journal.create', { schedule_id: activeSchedule.id })" 
                                class="shrink-0 inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-800 font-extrabold text-xs rounded-full shadow-md active:scale-95 transition-all border border-slate-100"
                            >
                                <template v-if="activeSchedule.has_journal">
                                    <CheckCircleIcon class="w-3.5 h-3.5 text-emerald-600 stroke-[2.5]" />
                                    <span class="text-emerald-700">Jurnal Terisi</span>
                                </template>
                                <template v-else>
                                    <PencilSquareIcon class="w-3.5 h-3.5 stroke-[2.2]" :class="activeScheduleBanner.btnIconColor" />
                                    <span>Isi Jurnal</span>
                                    <ChevronRightIcon class="w-3 h-3 stroke-[2.5] text-slate-400" />
                                </template>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- B. SESI BERIKUTNYA (Compact Single-Row Preview: Anti-Mengganggu Pintasan Cepat!) -->
                <div v-if="nextSchedule" class="space-y-1.5">
                    <div class="flex items-center justify-between px-0.5">
                        <span class="text-xs font-bold text-slate-500">Sesi Berikutnya</span>
                        <Link 
                            :href="safeRoute('yayasan.schedules.index')" 
                            class="text-xs font-bold text-teal-700 hover:underline flex items-center gap-0.5"
                        >
                            <span>Lihat Semua</span>
                            <ChevronRightIcon class="w-3 h-3 stroke-[2.5]" />
                        </Link>
                    </div>

                    <!-- Next Schedule Item -->
                    <div class="bg-white hover:bg-slate-50/80 rounded-2xl p-2.5 border border-slate-100 shadow-2xs flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="text-right leading-tight pr-3 border-r border-teal-200/80 shrink-0">
                                <p class="font-extrabold text-xs text-slate-800">{{ nextSchedule.start_time }}</p>
                                <p class="font-semibold text-[11px] text-slate-500 mt-0.5">{{ nextSchedule.end_time }}</p>
                            </div>
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" :class="getScheduleIconStyle(nextSchedule.subject_name)">
                                <ComputerDesktopIcon v-if="getScheduleIconType(nextSchedule.subject_name) === 'computer'" class="w-4 h-4 stroke-[2]" />
                                <BeakerIcon v-else-if="getScheduleIconType(nextSchedule.subject_name) === 'beaker'" class="w-4 h-4 stroke-[2]" />
                                <CalculatorIcon v-else-if="getScheduleIconType(nextSchedule.subject_name) === 'calculator'" class="w-4 h-4 stroke-[2]" />
                                <PaintBrushIcon v-else-if="getScheduleIconType(nextSchedule.subject_name) === 'paint'" class="w-4 h-4 stroke-[2]" />
                                <BookOpenIcon v-else class="w-4 h-4 stroke-[2]" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-extrabold text-xs text-slate-800 truncate">{{ nextSchedule.subject_name }}</p>
                                <p class="text-[11px] font-medium text-slate-500 truncate mt-0.5">
                                    <span>Kelas {{ nextSchedule.classroom_name }}</span>
                                </p>
                            </div>
                        </div>
                        <Link 
                            :href="safeRoute('yayasan.teaching-journal.create', { schedule_id: nextSchedule.id })"
                            class="w-7 h-7 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center shrink-0 hover:bg-teal-50 hover:text-teal-700 transition-all active:scale-95"
                        >
                            <ChevronRightIcon class="w-4 h-4 stroke-[2.5]" />
                        </Link>
                    </div>
                </div>

                <!-- C. SESI SELESAI (Hanya Tampil Maksimal 1 Sesi Selesai Terakhir agar Ringkas) -->
                <div v-if="completedSchedule" class="space-y-1.5">
                    <div class="flex items-center justify-between px-0.5">
                        <span class="text-xs font-bold text-slate-400">Selesai</span>
                    </div>

                    <!-- Completed Schedule Item -->
                    <div class="bg-slate-50/60 rounded-2xl p-2.5 border border-slate-100 flex items-center justify-between gap-3 opacity-80">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="text-right leading-tight pr-3 border-r border-slate-200 shrink-0">
                                <p class="font-bold text-xs text-slate-500">{{ completedSchedule.start_time }}</p>
                                <p class="font-medium text-[11px] text-slate-400 mt-0.5">{{ completedSchedule.end_time }}</p>
                            </div>
                            <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center shrink-0">
                                <ComputerDesktopIcon v-if="getScheduleIconType(completedSchedule.subject_name) === 'computer'" class="w-4 h-4 stroke-[2]" />
                                <BeakerIcon v-else-if="getScheduleIconType(completedSchedule.subject_name) === 'beaker'" class="w-4 h-4 stroke-[2]" />
                                <CalculatorIcon v-else-if="getScheduleIconType(completedSchedule.subject_name) === 'calculator'" class="w-4 h-4 stroke-[2]" />
                                <PaintBrushIcon v-else-if="getScheduleIconType(completedSchedule.subject_name) === 'paint'" class="w-4 h-4 stroke-[2]" />
                                <BookOpenIcon v-else class="w-4 h-4 stroke-[2]" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-xs text-slate-600 truncate">{{ completedSchedule.subject_name }}</p>
                                <p class="text-[11px] font-medium text-slate-400 truncate mt-0.5">
                                    <span>Kelas {{ completedSchedule.classroom_name }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="w-7 h-7 rounded-full bg-slate-100 text-emerald-600 flex items-center justify-center shrink-0">
                            <CheckCircleIcon class="w-4 h-4 stroke-[2.5]" />
                        </div>
                    </div>
                </div>

                <!-- D. Notice jika tidak ada jadwal mengajar hari ini sama sekali -->
                <div 
                    v-if="todaySchedules.length === 0"
                    class="p-3 rounded-2xl bg-slate-50 border border-dashed border-slate-200 flex items-center justify-between gap-2"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center shrink-0">
                            <CalendarDaysIcon class="w-4 h-4 stroke-[2]" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-700 truncate">Tidak ada jadwal mengajar hari ini</p>
                            <p class="text-[10px] text-slate-400 truncate">Gunakan waktu untuk persiapan materi & administrasi</p>
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

            <!-- 5. PINTASAN MENU CEPAT (Glassmorphism Square Modern Grid) -->
            <div v-if="!isPengawas" class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/70 shadow-xs space-y-4">
                <div class="flex items-center justify-between px-1">
                    <h4 class="font-black text-xs sm:text-sm uppercase tracking-wider text-slate-800 flex items-center gap-2">
                        <Squares2X2Icon class="w-4 h-4 sm:w-4.5 sm:h-4.5 text-teal-600 stroke-[2.5]" />
                        <span>Pintasan Cepat</span>
                    </h4>
                    <button 
                        type="button" 
                        @click="openAllMenu"
                        class="text-xs font-extrabold text-teal-700 hover:text-teal-800 flex items-center gap-0.5 active:scale-95 transition-all"
                    >
                        <span>Semua Menu</span>
                        <ChevronRightIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                    </button>
                </div>

                <div class="grid grid-cols-4 gap-y-4 gap-x-2 text-center">
                    <!-- 1. Presensi Pegawai -->
                    <Link 
                        :href="safeRoute('attendance.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-teal-50/70 border border-white ring-1 ring-teal-400/25 shadow-[0_8px_20px_-3px_rgba(20,184,166,0.30)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <FingerPrintIcon class="w-6 h-6 sm:w-7 sm:h-7 text-teal-600 stroke-[2.3]" />
                        </div>
                        <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-1 group-hover:text-teal-700 transition-colors">Presensi</span>
                    </Link>

                    <!-- 2. Jurnal Guru -->
                    <Link 
                        v-if="isTeacher || hasRole('teacher')"
                        :href="safeRoute('yayasan.teaching-journal.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-amber-50/70 border border-white ring-1 ring-amber-400/25 shadow-[0_8px_20px_-3px_rgba(245,158,11,0.30)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <PencilSquareIcon class="w-6 h-6 sm:w-7 sm:h-7 text-amber-500 stroke-[2.3]" />
                        </div>
                        <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-1 group-hover:text-amber-600 transition-colors">Jurnal Guru</span>
                    </Link>

                    <!-- 3. Absensi Siswa (Wali Kelas / Guru) -->
                    <Link 
                        v-if="isTeacher || hasRole(['teacher', 'wali_kelas']) || teacherData?.homeroom_class"
                        :href="safeRoute('yayasan.student-attendance.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-blue-50/70 border border-white ring-1 ring-blue-400/25 shadow-[0_8px_20px_-3px_rgba(37,99,235,0.30)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <ClipboardDocumentCheckIcon class="w-6 h-6 sm:w-7 sm:h-7 text-blue-600 stroke-[2.3]" />
                        </div>
                        <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-1 group-hover:text-blue-600 transition-colors">Absen Siswa</span>
                    </Link>

                    <!-- 4. Jadwal Mengajar -->
                    <Link 
                        v-if="isTeacher || hasRole('teacher')"
                        :href="safeRoute('yayasan.schedules.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-purple-50/70 border border-white ring-1 ring-purple-400/25 shadow-[0_8px_20px_-3px_rgba(147,51,234,0.30)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <CalendarDaysIcon class="w-6 h-6 sm:w-7 sm:h-7 text-purple-600 stroke-[2.3]" />
                        </div>
                        <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-1 group-hover:text-purple-600 transition-colors">Jadwal</span>
                    </Link>

                    <!-- 5. Scan QR Gerbang -->
                    <Link 
                        :href="safeRoute('yayasan.student-checkin.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-cyan-50/70 border border-white ring-1 ring-cyan-400/25 shadow-[0_8px_20px_-3px_rgba(6,182,212,0.30)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <QrCodeIcon class="w-6 h-6 sm:w-7 sm:h-7 text-cyan-600 stroke-[2.3]" />
                        </div>
                        <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-1 group-hover:text-cyan-600 transition-colors">Scan QR</span>
                    </Link>

                    <!-- 6. Agenda & Kalender Event -->
                    <Link 
                        :href="safeRoute('public-relations.events.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-rose-50/70 border border-white ring-1 ring-rose-400/25 shadow-[0_8px_20px_-3px_rgba(239,68,68,0.30)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <CalendarIcon class="w-6 h-6 sm:w-7 sm:h-7 text-rose-600 stroke-[2.3]" />
                        </div>
                        <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-1 group-hover:text-rose-600 transition-colors">Agenda</span>
                    </Link>

                    <!-- 7. Data Siswa -->
                    <Link 
                        v-if="isTeacher || hasRole(['teacher', 'wali_kelas', 'admin_unit', 'kepala_sekolah'])"
                        :href="safeRoute('yayasan.students.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-sky-50/70 border border-white ring-1 ring-sky-400/25 shadow-[0_8px_20px_-3px_rgba(2,132,199,0.30)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <AcademicCapIcon class="w-6 h-6 sm:w-7 sm:h-7 text-sky-600 stroke-[2.3]" />
                        </div>
                        <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-1 group-hover:text-sky-600 transition-colors">Data Siswa</span>
                    </Link>

                    <!-- 8. Giat Tugas Saya -->
                    <Link 
                        :href="safeRoute('employee.activity-logs.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-teal-50/70 border border-white ring-1 ring-teal-400/25 shadow-[0_8px_20px_-3px_rgba(20,184,166,0.30)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <ClipboardDocumentCheckIcon class="w-6 h-6 sm:w-7 sm:h-7 text-teal-600 stroke-[2.3]" />
                        </div>
                        <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-1 group-hover:text-teal-700 transition-colors">Giat Tugas</span>
                    </Link>

                    <!-- 9. Persetujuan Absen (Kepsek / Admin) -->
                    <Link 
                        v-if="isGlobalAdmin || hasRole(['kepala_sekolah', 'admin_unit', 'pembina_yayasan', 'pengawas_yayasan', 'staff_yayasan'])"
                        :href="safeRoute('attendance-approvals.index', {}, safeRoute('yayasan.attendance-approvals.index'))"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-emerald-50/70 border border-white ring-1 ring-emerald-400/25 shadow-[0_8px_20px_-3px_rgba(16,185,129,0.30)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <CheckCircleIcon class="w-6 h-6 sm:w-7 sm:h-7 text-emerald-600 stroke-[2.3]" />
                        </div>
                        <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-1 group-hover:text-emerald-600 transition-colors">ACC Izin</span>
                    </Link>

                    <!-- 10. Sarpras (Pemeliharaan / Fasilitas) -->
                    <Link 
                        v-if="hasRole(['koordinator_sarpar', 'admin_unit'])"
                        :href="safeRoute('sarpar.maintenance.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-orange-50/70 border border-white ring-1 ring-orange-400/25 shadow-[0_8px_20px_-3px_rgba(249,115,22,0.30)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <WrenchScrewdriverIcon class="w-6 h-6 sm:w-7 sm:h-7 text-orange-600 stroke-[2.3]" />
                        </div>
                        <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-1 group-hover:text-orange-600 transition-colors">Sarpras</span>
                    </Link>

                    <!-- 11. Berita Humas -->
                    <Link 
                        v-if="hasRole(['humas_unit', 'admin_unit'])"
                        :href="safeRoute('public-relations.news.index')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-slate-100/70 border border-white ring-1 ring-slate-400/25 shadow-[0_8px_20px_-3px_rgba(100,116,139,0.30)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <NewspaperIcon class="w-6 h-6 sm:w-7 sm:h-7 text-slate-700 stroke-[2.3]" />
                        </div>
                        <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-1 group-hover:text-slate-700 transition-colors">Berita</span>
                    </Link>

                    <!-- 12. Keuangan Unit (Finance) -->
                    <Link 
                        v-if="hasRole('finance')"
                        :href="safeRoute('finance.dashboard')"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-teal-50/70 border border-white ring-1 ring-teal-500/25 shadow-[0_8px_20px_-3px_rgba(13,148,136,0.30)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <BanknotesIcon class="w-6 h-6 sm:w-7 sm:h-7 text-teal-700 stroke-[2.3]" />
                        </div>
                        <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-1 group-hover:text-teal-700 transition-colors">Keuangan</span>
                    </Link>

                    <!-- 13. Semua Menu (Lainnya) -->
                    <button 
                        type="button"
                        @click="openAllMenu"
                        class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                    >
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-slate-100/70 border border-white ring-1 ring-slate-400/25 shadow-[0_8px_20px_-3px_rgba(100,116,139,0.30)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                            <Squares2X2Icon class="w-6 h-6 sm:w-7 sm:h-7 text-slate-700 stroke-[2.3]" />
                        </div>
                        <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-1 group-hover:text-slate-900 transition-colors">Semua Menu</span>
                    </button>
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

