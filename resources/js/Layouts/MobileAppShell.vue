<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { 
    HomeIcon, 
    BookOpenIcon, 
    QrCodeIcon, 
    ClipboardDocumentCheckIcon, 
    Squares2X2Icon,
    CalendarIcon,
    ChatBubbleLeftRightIcon,
    FingerPrintIcon,
    WrenchScrewdriverIcon,
    UserCircleIcon,
    XMarkIcon,
    ArrowRightOnRectangleIcon,
    NewspaperIcon,
    GlobeAltIcon,
    SparklesIcon,
    BanknotesIcon,
    MegaphoneIcon,
    ExclamationTriangleIcon,
    TrophyIcon,
    CubeIcon,
    BuildingOfficeIcon,
    ArrowPathRoundedSquareIcon,
    ClipboardDocumentListIcon,
    PresentationChartBarIcon,
    EyeIcon,
    ChevronDownIcon,
    CheckCircleIcon,
    UserGroupIcon,
    ClockIcon,
    HeartIcon,
    TagIcon,
    AcademicCapIcon,
    CalendarDaysIcon,
    PencilSquareIcon,
    TableCellsIcon
} from '@heroicons/vue/24/outline';
import { 
    HomeIcon as HomeIconSolid, 
    BookOpenIcon as BookOpenIconSolid,
    ClipboardDocumentCheckIcon as ClipboardDocumentCheckIconSolid,
    Squares2X2Icon as Squares2X2IconSolid,
    NewspaperIcon as NewspaperIconSolid,
    FingerPrintIcon as FingerPrintIconSolid,
    PresentationChartBarIcon as PresentationChartBarIconSolid
} from '@heroicons/vue/24/solid';

const page = usePage();
const user = computed(() => page.props.auth.user);
const activeUnit = computed(() => page.props.session.active_unit_name || 'Yayasan Namira');
const userRoles = computed(() => user.value?.roles || []);
const isTeacher = computed(() => user.value?.is_teacher || userRoles.value.includes('teacher'));
const isPengawas = computed(() => userRoles.value.includes('pengawas_yayasan') && !userRoles.value.includes('super_admin_yayasan') && !userRoles.value.includes('admin_yayasan'));
const isGlobalAdmin = computed(() => userRoles.value.some(r => ['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan'].includes(r)));

const showDrawer = ref(false);
const showUnitModal = ref(false);

const canSwitchUnit = computed(() => {
    return userRoles.value.some(r => ['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan', 'staff_yayasan'].includes(r));
});

const availableUnits = computed(() => page.props.session?.available_units || []);
const activeUnitId = computed(() => page.props.session?.active_unit_id);

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

const switchUnit = (unitId) => {
    router.post(route('yayasan.switch-unit'), {
        unit_id: unitId
    }, {
        onSuccess: () => showUnitModal.value = false,
        preserveScroll: true,
    });
};

const toggleDrawer = () => {
    showDrawer.value = !showDrawer.value;
};

const openDrawer = () => {
    showDrawer.value = true;
};

onMounted(() => {
    window.addEventListener('open-mobile-drawer', openDrawer);
});

onUnmounted(() => {
    window.removeEventListener('open-mobile-drawer', openDrawer);
});

// Role Check Helper Function
const hasRole = (roles) => {
    if (!Array.isArray(roles)) roles = [roles];
    if (userRoles.value.includes('super_admin_yayasan') || userRoles.value.includes('admin_yayasan')) return true;
    return roles.some(role => userRoles.value.includes(role));
};

// Safe Route helper to prevent Ziggy route crash on mobile
const safeRoute = (name, params = {}, fallback = '#') => {
    try {
        if (typeof route === 'function') {
            if (route().has(name)) return route(name, params);
            if (name === 'attendance.index' && route().has('employee.attendance.index')) return route('employee.attendance.index', params);
            if (name.indexOf('employee.') !== 0 && route().has('employee.' + name)) return route('employee.' + name, params);
        }
    } catch (e) {
        console.warn(`Route "${name}" is not registered:`, e);
    }
    return fallback;
};

// Check active routes safely
const isRouteActive = (pattern) => {
    try {
        if (typeof route === 'function') {
            if (route().current(pattern)) return true;
            if (pattern === 'attendance.*' || pattern === 'attendance.index') {
                return route().current('employee.attendance.*') || route().current('attendance.*');
            }
        }
    } catch (e) {
        return false;
    }
    return false;
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 font-sans pb-28 text-slate-800">
        <!-- Top Bar (Header HP Guru & Staff - White Clean Theme) -->
        <header class="bg-white/95 text-slate-900 sticky top-0 z-40 px-4 py-3 shadow-sm flex items-center justify-between border-b border-slate-200/80 backdrop-blur-xl">
            <div class="flex items-center gap-3">
                <Link :href="safeRoute('profile.edit')" class="relative group">
                    <div class="w-10 h-10 rounded-full bg-teal-600 p-0.5 ring-2 ring-teal-500/30 overflow-hidden shadow-sm">
                        <img 
                            v-if="user?.profile_photo_url" 
                            :src="user.profile_photo_url" 
                            class="w-full h-full object-cover rounded-full" 
                            alt="Foto Profil"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-white text-xs font-black bg-teal-700 rounded-full">
                            {{ user?.name?.charAt(0) || 'U' }}
                        </div>
                    </div>
                </Link>

                <div class="flex flex-col">
                    <button 
                        v-if="canSwitchUnit"
                        @click="showUnitModal = true" 
                        type="button"
                        class="text-[10px] font-extrabold tracking-wider uppercase text-teal-700 hover:text-teal-900 flex items-center gap-1 bg-teal-50 hover:bg-teal-100/80 px-2 py-0.5 rounded-full border border-teal-200/80 shadow-2xs transition-all active:scale-95 text-left"
                    >
                        <BuildingOfficeIcon class="w-3 h-3 text-teal-600 stroke-[2.2]" />
                        <span class="truncate max-w-[130px]">{{ activeUnit }}</span>
                        <ChevronDownIcon class="w-3 h-3 text-teal-600 stroke-[2.5]" />
                    </button>
                    <span v-else class="text-[10px] font-extrabold tracking-wider uppercase text-teal-700 flex items-center gap-1">
                        <BuildingOfficeIcon class="w-3 h-3 text-teal-600 stroke-[2.2]" />
                        {{ activeUnit }}
                    </span>
                    <h1 class="font-black text-sm text-slate-900 leading-tight truncate max-w-[170px]">
                        {{ user?.name || 'Pengguna Namira' }}
                    </h1>
                </div>
            </div>

            <!-- Profile & Quick Action -->
            <div class="flex items-center gap-2">
                <Link 
                    :href="safeRoute('profile.edit')" 
                    class="p-2 rounded-xl bg-slate-100 text-slate-600 hover:text-slate-900 hover:bg-slate-200 transition-colors border border-slate-200/80"
                    title="Profil Saya"
                >
                    <UserCircleIcon class="w-5 h-5" />
                </Link>
            </div>
        </header>

        <!-- Main Content View -->
        <main class="p-4 max-w-lg mx-auto">
            <slot />
        </main>

        <!-- Mobile Bottom Navigation Bar (Smart Dynamic Navigation) -->
        <nav class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-xl border-t border-slate-200/80 pb-safe pt-1.5 px-3 z-50 shadow-[0_-4px_25px_rgba(0,0,0,0.08)] rounded-t-3xl">
            <div class="flex justify-around items-center max-w-md mx-auto relative">
                
                <!-- 1. Beranda (Selalu Ada) -->
                <Link 
                    :href="safeRoute('dashboard')"
                    class="flex flex-col items-center gap-1 py-1 px-3 transition-all duration-200 active:scale-95 flex-1"
                    :class="isRouteActive('dashboard') ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
                >
                    <component 
                        :is="isRouteActive('dashboard') ? HomeIconSolid : HomeIcon" 
                        class="w-6 h-6 transition-transform" 
                    />
                    <span class="text-[10px] font-bold tracking-tight">Beranda</span>
                </Link>

                <!-- 2. Dynamic Primary Action -->
                <!-- Daycare: Data Ananda -->
                <Link 
                    v-if="isDaycare"
                    :href="safeRoute('daycare.children.index')"
                    class="flex flex-col items-center gap-1 py-1 px-3 transition-all duration-200 active:scale-95 flex-1"
                    :class="isRouteActive('daycare.children.*') ? 'text-amber-600 font-bold' : 'text-slate-400 hover:text-slate-600'"
                >
                    <UserGroupIcon class="w-6 h-6 transition-transform" />
                    <span class="text-[10px] font-bold tracking-tight">Ananda</span>
                </Link>

                <!-- Pengawas: Monitoring -->
                <Link 
                    v-else-if="isPengawas"
                    :href="safeRoute('yayasan.monitoring.index', {}, '#')"
                    class="flex flex-col items-center gap-1 py-1 px-3 transition-all duration-200 active:scale-95 flex-1"
                    :class="isRouteActive('yayasan.monitoring.*') ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
                >
                    <EyeIcon class="w-6 h-6 transition-transform" />
                    <span class="text-[10px] font-bold tracking-tight">Monitoring</span>
                </Link>

                <!-- Guru: Jurnal -->
                <Link 
                    v-else-if="isTeacher || hasRole('teacher')"
                    :href="safeRoute('yayasan.teaching-journal.index')"
                    class="flex flex-col items-center gap-1 py-1 px-3 transition-all duration-200 active:scale-95 flex-1"
                    :class="isRouteActive('yayasan.teaching-journal.*') ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
                >
                    <component 
                        :is="isRouteActive('yayasan.teaching-journal.*') ? BookOpenIconSolid : BookOpenIcon" 
                        class="w-6 h-6 transition-transform" 
                    />
                    <span class="text-[10px] font-bold tracking-tight">Jurnal</span>
                </Link>

                <!-- Humas: Berita -->
                <Link 
                    v-else-if="hasRole('humas_unit')"
                    :href="safeRoute('public-relations.news.index')"
                    class="flex flex-col items-center gap-1 py-1 px-3 transition-all duration-200 active:scale-95 flex-1"
                    :class="isRouteActive('public-relations.news.*') ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
                >
                    <component 
                        :is="isRouteActive('public-relations.news.*') ? NewspaperIconSolid : NewspaperIcon" 
                        class="w-6 h-6 transition-transform" 
                    />
                    <span class="text-[10px] font-bold tracking-tight">Berita</span>
                </Link>

                <!-- Staff biasa: Presensi -->
                <Link 
                    v-else
                    :href="safeRoute('attendance.index', { tab: 'personal' })"
                    class="flex flex-col items-center gap-1 py-1 px-3 transition-all duration-200 active:scale-95 flex-1"
                    :class="isRouteActive('attendance.*') ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
                >
                    <component 
                        :is="isRouteActive('attendance.*') ? FingerPrintIconSolid : FingerPrintIcon" 
                        class="w-6 h-6 transition-transform" 
                    />
                    <span class="text-[10px] font-bold tracking-tight">Presensi Saya</span>
                </Link>

                <!-- 3. CENTER FAB: Daycare -> Absensi Saya / Pengawas -> Monitoring / Other -> QR Scanner -->
                <div class="flex-1 flex justify-center -mt-6">
                    <!-- Daycare: Absensi Saya (Presensi Pegawai) -->
                    <Link 
                        v-if="isDaycare"
                        :href="safeRoute('employee.attendance.index')"
                        class="w-14 h-14 rounded-full bg-gradient-to-tr from-amber-500 to-orange-500 text-white flex items-center justify-center shadow-lg shadow-amber-500/40 ring-4 ring-white active:scale-90 transition-all transform hover:scale-105"
                        title="Absensi Saya"
                    >
                        <FingerPrintIcon class="w-7 h-7 stroke-[2.2]" />
                    </Link>
                    <!-- Pengawas: Statistik & Monitoring -->
                    <Link 
                        v-else-if="isPengawas"
                        :href="safeRoute('yayasan.monitoring.index')"
                        class="w-14 h-14 rounded-full bg-gradient-to-tr from-indigo-700 to-blue-500 text-white flex items-center justify-center shadow-lg shadow-indigo-700/40 ring-4 ring-white active:scale-90 transition-all transform hover:scale-105"
                        title="Statistik & Monitoring"
                    >
                        <component :is="PresentationChartBarIconSolid" class="w-7 h-7" />
                    </Link>
                    <!-- Semua user lain: QR Scanner -->
                    <Link 
                        v-else
                        :href="safeRoute('yayasan.student-checkin.index')"
                        class="w-14 h-14 rounded-full bg-gradient-to-tr from-teal-700 to-emerald-500 text-white flex items-center justify-center shadow-lg shadow-teal-700/40 ring-4 ring-white active:scale-90 transition-all transform hover:scale-105"
                        title="Scan QR Presensi"
                    >
                        <QrCodeIcon class="w-7 h-7 stroke-[2.2]" />
                    </Link>
                </div>

                <!-- 4. Dynamic Secondary Action -->
                <!-- Daycare: Handover -->
                <Link 
                    v-if="isDaycare"
                    :href="safeRoute('daycare.attendance.index')"
                    class="flex flex-col items-center gap-1 py-1 px-3 transition-all duration-200 active:scale-95 flex-1"
                    :class="isRouteActive('daycare.attendance.*') ? 'text-amber-600 font-bold' : 'text-slate-400 hover:text-slate-600'"
                >
                    <ClockIcon class="w-6 h-6 transition-transform" />
                    <span class="text-[10px] font-bold tracking-tight">Handover</span>
                </Link>

                <!-- Pengawas: Monitoring -->
                <Link 
                    v-else-if="isPengawas"
                    :href="safeRoute('yayasan.monitoring.index')"
                    class="flex flex-col items-center gap-1 py-1 px-3 transition-all duration-200 active:scale-95 flex-1"
                    :class="isRouteActive('yayasan.monitoring.*') ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
                >
                    <ChartBarIcon class="w-6 h-6 transition-transform" />
                    <span class="text-[10px] font-bold tracking-tight">Monitoring</span>
                </Link>

                <!-- Wali Kelas: Absensi Siswa -->
                <Link 
                    v-else-if="hasRole('wali_kelas')"
                    :href="safeRoute('yayasan.student-attendance.index')"
                    class="flex flex-col items-center gap-1 py-1 px-3 transition-all duration-200 active:scale-95 flex-1"
                    :class="isRouteActive('yayasan.student-attendance.*') ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
                >
                    <component 
                        :is="isRouteActive('yayasan.student-attendance.*') ? ClipboardDocumentCheckIconSolid : ClipboardDocumentCheckIcon" 
                        class="w-6 h-6 transition-transform" 
                    />
                    <span class="text-[10px] font-bold tracking-tight">Absensi</span>
                </Link>

                <!-- Humas: Kampus -->
                <Link 
                    v-else-if="hasRole('humas_unit')"
                    :href="safeRoute('public-relations.university-destinations.index')"
                    class="flex flex-col items-center gap-1 py-1 px-3 transition-all duration-200 active:scale-95 flex-1"
                    :class="isRouteActive('public-relations.university-destinations.*') ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
                >
                    <GlobeAltIcon class="w-6 h-6 transition-transform" />
                    <span class="text-[10px] font-bold tracking-tight">Kampus</span>
                </Link>

                <!-- BK / Kesiswaan: Konseling -->
                <Link 
                    v-else-if="hasRole(['bk', 'counseling', 'koordinator_kesiswaan'])"
                    :href="safeRoute('counseling.sessions.index')"
                    class="flex flex-col items-center gap-1 py-1 px-3 transition-all duration-200 active:scale-95 flex-1"
                    :class="isRouteActive('counseling.*') ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
                >
                    <ChatBubbleLeftRightIcon class="w-6 h-6 transition-transform" />
                    <span class="text-[10px] font-bold tracking-tight">Kesiswaan</span>
                </Link>

                <!-- Staff biasa / Guru: Presensi Pegawai -->
                <Link 
                    v-else
                    :href="safeRoute('employee.attendance.index', { tab: 'personal' })"
                    class="flex flex-col items-center gap-1 py-1 px-3 transition-all duration-200 active:scale-95 flex-1"
                    :class="isRouteActive('employee.attendance.*') || isRouteActive('attendance.*') ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
                >
                    <ClipboardDocumentCheckIcon class="w-6 h-6 transition-transform" />
                    <span class="text-[10px] font-bold tracking-tight">Presensi Saya</span>
                </Link>

                <!-- 5. Drawer Menu "Lainnya" -->
                <button 
                    @click="toggleDrawer"
                    type="button"
                    class="flex flex-col items-center gap-1 py-1 px-3 transition-all duration-200 active:scale-95 flex-1"
                    :class="showDrawer ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
                >
                    <component 
                        :is="showDrawer ? Squares2X2IconSolid : Squares2X2Icon" 
                        class="w-6 h-6 transition-transform" 
                    />
                    <span class="text-[10px] font-bold tracking-tight">Menu</span>
                </button>

            </div>
        </nav>

        <!-- Bottom Sheet Drawer (Full Dynamic Multi-Role App Menu Sheet) -->
        <Teleport to="body">
            <div v-if="showDrawer" class="fixed inset-0 z-[100] overflow-hidden">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm transition-opacity" @click="showDrawer = false"></div>
                
                <!-- Drawer Card -->
                <div class="fixed inset-x-0 bottom-0 bg-white rounded-t-3xl max-h-[85vh] overflow-y-auto shadow-2xl p-4 sm:p-6 border-t border-slate-100 flex flex-col gap-5 animate-in slide-in-from-bottom duration-300">
                    <!-- Drawer Handle Bar -->
                    <div class="w-10 h-1.5 rounded-full bg-slate-200 mx-auto -mt-1 shrink-0"></div>

                    <!-- Drawer Header -->
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h3 class="font-black text-base sm:text-lg text-slate-800 tracking-tight flex items-center gap-2">
                                <Squares2X2Icon class="w-5 h-5 text-teal-600 stroke-[2.3]" />
                                <span>Semua Menu & Layanan</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 font-medium">Seluruh fitur layanan yang Anda miliki saat ini</p>
                        </div>
                        <button @click="showDrawer = false" class="p-2 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 active:scale-95 transition-all">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Dynamic Role-Based App Categorized Groups -->
                    <div class="space-y-5 pb-6">

                        <!-- 1. AKADEMIK & KURIKULUM -->
                        <div v-if="!isDaycare && (isTeacher || isGlobalAdmin || hasRole(['teacher', 'wali_kelas', 'koordinator_kurikulum', 'admin_unit', 'kepala_sekolah']))" class="space-y-3 bg-slate-50/70 rounded-3xl p-3.5 sm:p-4 border border-slate-200/60">
                            <div class="flex items-center gap-2 px-1">
                                <div class="w-2 h-2 rounded-full bg-teal-500"></div>
                                <p class="text-[11px] font-black uppercase text-slate-700 tracking-wider">
                                    Akademik & Kurikulum
                                </p>
                            </div>
                            <div class="grid grid-cols-4 gap-y-4 gap-x-2 text-center">
                                <Link 
                                    v-if="hasRole(['admin_unit', 'admin_yayasan', 'super_admin_yayasan', 'wali_kelas', 'teacher'])"
                                    :href="safeRoute('yayasan.students.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-sky-50/70 border border-white ring-1 ring-sky-400/25 shadow-[0_8px_18px_-3px_rgba(2,132,199,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <AcademicCapIcon class="w-6 h-6 sm:w-7 sm:h-7 text-sky-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-sky-600 transition-colors">Data Siswa</span>
                                </Link>

                                <Link 
                                    v-if="hasRole(['admin_unit', 'admin_yayasan', 'super_admin_yayasan', 'wali_kelas'])"
                                    :href="safeRoute('yayasan.classrooms.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-emerald-50/70 border border-white ring-1 ring-emerald-400/25 shadow-[0_8px_18px_-3px_rgba(16,185,129,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <BuildingOfficeIcon class="w-6 h-6 sm:w-7 sm:h-7 text-emerald-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-emerald-600 transition-colors">Data Kelas</span>
                                </Link>

                                <Link 
                                    v-if="hasRole(['admin_unit', 'admin_yayasan', 'super_admin_yayasan'])"
                                    :href="safeRoute('yayasan.teachers.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-indigo-50/70 border border-white ring-1 ring-indigo-400/25 shadow-[0_8px_18px_-3px_rgba(99,102,241,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <UserGroupIcon class="w-6 h-6 sm:w-7 sm:h-7 text-indigo-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-indigo-600 transition-colors">Data Guru</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('yayasan.schedules.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-purple-50/70 border border-white ring-1 ring-purple-400/25 shadow-[0_8px_18px_-3px_rgba(147,51,234,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <CalendarDaysIcon class="w-6 h-6 sm:w-7 sm:h-7 text-purple-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-purple-600 transition-colors">Jadwal</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('yayasan.teaching-journal.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-amber-50/70 border border-white ring-1 ring-amber-400/25 shadow-[0_8px_18px_-3px_rgba(245,158,11,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <PencilSquareIcon class="w-6 h-6 sm:w-7 sm:h-7 text-amber-500 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-amber-600 transition-colors">Jurnal Mapel</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('yayasan.subjects.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-blue-50/70 border border-white ring-1 ring-blue-400/25 shadow-[0_8px_18px_-3px_rgba(37,99,235,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <BookOpenIcon class="w-6 h-6 sm:w-7 sm:h-7 text-blue-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-blue-600 transition-colors">Mata Pelajaran</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('yayasan.learning-objectives.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-orange-50/70 border border-white ring-1 ring-orange-400/25 shadow-[0_8px_18px_-3px_rgba(249,115,22,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <TrophyIcon class="w-6 h-6 sm:w-7 sm:h-7 text-orange-500 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-orange-600 transition-colors">Tujuan Belajar</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('yayasan.student-attendance.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-teal-50/70 border border-white ring-1 ring-teal-400/25 shadow-[0_8px_18px_-3px_rgba(20,184,166,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <ClipboardDocumentCheckIcon class="w-6 h-6 sm:w-7 sm:h-7 text-[#00584b] stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-teal-700 transition-colors">Presensi Siswa</span>
                                </Link>

                                <Link 
                                    v-if="hasRole(['admin_unit', 'admin_yayasan', 'super_admin_yayasan'])"
                                    :href="safeRoute('yayasan.promotion.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-violet-50/70 border border-white ring-1 ring-violet-400/25 shadow-[0_8px_18px_-3px_rgba(139,92,246,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <SparklesIcon class="w-6 h-6 sm:w-7 sm:h-7 text-violet-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-violet-600 transition-colors">Kenaikan Kelas</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('yayasan.holidays.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-rose-50/70 border border-white ring-1 ring-rose-400/25 shadow-[0_8px_18px_-3px_rgba(244,63,94,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <CalendarIcon class="w-6 h-6 sm:w-7 sm:h-7 text-rose-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-rose-600 transition-colors">Kalender</span>
                                </Link>
                            </div>
                        </div>

                        <!-- 2. KESISWAAN & BIMBINGAN KONSELING -->
                        <div v-if="!isDaycare && (isGlobalAdmin || hasRole(['bk', 'counseling', 'wali_kelas', 'kepala_sekolah', 'admin_unit', 'koordinator_kesiswaan']))" class="space-y-3 bg-slate-50/70 rounded-3xl p-3.5 sm:p-4 border border-slate-200/60">
                            <div class="flex items-center gap-2 px-1">
                                <div class="w-2 h-2 rounded-full bg-pink-500"></div>
                                <p class="text-[11px] font-black uppercase text-slate-700 tracking-wider">
                                    Kesiswaan & Bimbingan Konseling
                                </p>
                            </div>
                            <div class="grid grid-cols-4 gap-y-4 gap-x-2 text-center">
                                <Link 
                                    :href="safeRoute('counseling.sessions.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-pink-50/70 border border-white ring-1 ring-pink-400/25 shadow-[0_8px_18px_-3px_rgba(236,72,153,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <ChatBubbleLeftRightIcon class="w-6 h-6 sm:w-7 sm:h-7 text-pink-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-pink-600 transition-colors">Konseling</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('counseling.violations.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-rose-50/70 border border-white ring-1 ring-rose-400/25 shadow-[0_8px_18px_-3px_rgba(239,68,68,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <ExclamationTriangleIcon class="w-6 h-6 sm:w-7 sm:h-7 text-rose-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-rose-600 transition-colors">Pelanggaran</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('counseling.achievements.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-amber-50/70 border border-white ring-1 ring-amber-400/25 shadow-[0_8px_18px_-3px_rgba(245,158,11,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <TrophyIcon class="w-6 h-6 sm:w-7 sm:h-7 text-amber-500 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-amber-600 transition-colors">Prestasi</span>
                                </Link>

                                <Link 
                                    v-if="isGlobalAdmin || hasRole(['bk', 'koordinator_kesiswaan', 'admin_unit'])"
                                    :href="safeRoute('counseling.categories.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-indigo-50/70 border border-white ring-1 ring-indigo-400/25 shadow-[0_8px_18px_-3px_rgba(99,102,241,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <TableCellsIcon class="w-6 h-6 sm:w-7 sm:h-7 text-indigo-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-indigo-600 transition-colors">Kategori</span>
                                </Link>
                            </div>
                        </div>

                        <!-- 3. HUMAS & PUBLIKASI -->
                        <div v-if="isGlobalAdmin || hasRole(['humas_unit', 'kepala_sekolah', 'admin_unit'])" class="space-y-3 bg-slate-50/70 rounded-3xl p-3.5 sm:p-4 border border-slate-200/60">
                            <div class="flex items-center gap-2 px-1">
                                <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                <p class="text-[11px] font-black uppercase text-slate-700 tracking-wider">
                                    Humas & Hubungan Masyarakat
                                </p>
                            </div>
                            <div class="grid grid-cols-4 gap-y-4 gap-x-2 text-center">
                                <Link 
                                    :href="safeRoute('public-relations.news.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-slate-100/70 border border-white ring-1 ring-slate-400/25 shadow-[0_8px_18px_-3px_rgba(100,116,139,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <NewspaperIcon class="w-6 h-6 sm:w-7 sm:h-7 text-slate-700 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-slate-700 transition-colors">Berita</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('public-relations.university-destinations.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-sky-50/70 border border-white ring-1 ring-sky-400/25 shadow-[0_8px_18px_-3px_rgba(2,132,199,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <GlobeAltIcon class="w-6 h-6 sm:w-7 sm:h-7 text-sky-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-sky-600 transition-colors">Kampus</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('public-relations.events.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-violet-50/70 border border-white ring-1 ring-violet-400/25 shadow-[0_8px_18px_-3px_rgba(139,92,246,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <SparklesIcon class="w-6 h-6 sm:w-7 sm:h-7 text-violet-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-violet-600 transition-colors">Event</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('public-relations.testimonials.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-pink-50/70 border border-white ring-1 ring-pink-400/25 shadow-[0_8px_18px_-3px_rgba(236,72,153,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <ChatBubbleLeftRightIcon class="w-6 h-6 sm:w-7 sm:h-7 text-pink-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-pink-600 transition-colors">Testimoni</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('public-relations.partners.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-indigo-50/70 border border-white ring-1 ring-indigo-400/25 shadow-[0_8px_18px_-3px_rgba(99,102,241,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <BuildingOfficeIcon class="w-6 h-6 sm:w-7 sm:h-7 text-indigo-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-indigo-600 transition-colors">Mitra</span>
                                </Link>
                            </div>
                        </div>

                        <!-- 4. DAYCARE & PENGASUHAN -->
                        <div v-if="isGlobalAdmin || isDaycare" class="space-y-3 bg-slate-50/70 rounded-3xl p-3.5 sm:p-4 border border-slate-200/60">
                            <div class="flex items-center gap-2 px-1">
                                <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                                <p class="text-[11px] font-black uppercase text-slate-700 tracking-wider">
                                    Daycare & Pengasuhan
                                </p>
                            </div>
                            <div class="grid grid-cols-4 gap-y-4 gap-x-2 text-center">
                                <Link 
                                    :href="safeRoute('daycare.children.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-amber-50/70 border border-white ring-1 ring-amber-400/25 shadow-[0_8px_18px_-3px_rgba(245,158,11,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <UserGroupIcon class="w-6 h-6 sm:w-7 sm:h-7 text-amber-500 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-amber-600 transition-colors">Data Ananda</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('daycare.attendance.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-orange-50/70 border border-white ring-1 ring-orange-400/25 shadow-[0_8px_18px_-3px_rgba(249,115,22,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <ClockIcon class="w-6 h-6 sm:w-7 sm:h-7 text-orange-500 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-orange-600 transition-colors">Handover</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('yayasan.holidays.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-teal-50/70 border border-white ring-1 ring-teal-400/25 shadow-[0_8px_18px_-3px_rgba(20,184,166,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <CalendarIcon class="w-6 h-6 sm:w-7 sm:h-7 text-teal-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-teal-700 transition-colors">Kegiatan</span>
                                </Link>
                            </div>
                        </div>

                        <!-- 5. SARANA & PRASARANA (SARPAR) -->
                        <div v-if="isGlobalAdmin || hasRole(['koordinator_sarpar', 'admin_unit', 'super_admin_yayasan', 'admin_yayasan', 'kepala_sekolah'])" class="space-y-3 bg-slate-50/70 rounded-3xl p-3.5 sm:p-4 border border-slate-200/60">
                            <div class="flex items-center gap-2 px-1">
                                <div class="w-2 h-2 rounded-full bg-orange-500"></div>
                                <p class="text-[11px] font-black uppercase text-slate-700 tracking-wider">
                                    Sarana & Prasarana
                                </p>
                            </div>
                            <div class="grid grid-cols-4 gap-y-4 gap-x-2 text-center">
                                <Link 
                                    :href="safeRoute('sarpar.dashboard')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-slate-100/70 border border-white ring-1 ring-slate-400/25 shadow-[0_8px_18px_-3px_rgba(100,116,139,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <HomeIcon class="w-6 h-6 sm:w-7 sm:h-7 text-slate-700 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-slate-700 transition-colors">Dashboard</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('sarpar.inventories.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-teal-50/70 border border-white ring-1 ring-teal-400/25 shadow-[0_8px_18px_-3px_rgba(20,184,166,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <CubeIcon class="w-6 h-6 sm:w-7 sm:h-7 text-teal-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-teal-700 transition-colors">Inventaris</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('sarpar.loans.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-blue-50/70 border border-white ring-1 ring-blue-400/25 shadow-[0_8px_18px_-3px_rgba(37,99,235,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <ArrowPathRoundedSquareIcon class="w-6 h-6 sm:w-7 sm:h-7 text-blue-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-blue-600 transition-colors">Peminjaman</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('sarpar.maintenance.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-orange-50/70 border border-white ring-1 ring-orange-400/25 shadow-[0_8px_18px_-3px_rgba(249,115,22,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <WrenchScrewdriverIcon class="w-6 h-6 sm:w-7 sm:h-7 text-orange-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-orange-600 transition-colors">Pemeliharaan</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('sarpar.rooms.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-purple-50/70 border border-white ring-1 ring-purple-400/25 shadow-[0_8px_18px_-3px_rgba(147,51,234,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <BuildingOfficeIcon class="w-6 h-6 sm:w-7 sm:h-7 text-purple-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-purple-600 transition-colors">Ruangan</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('sarpar.categories.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-emerald-50/70 border border-white ring-1 ring-emerald-400/25 shadow-[0_8px_18px_-3px_rgba(16,185,129,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <TagIcon class="w-6 h-6 sm:w-7 sm:h-7 text-emerald-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-emerald-600 transition-colors">Kategori</span>
                                </Link>
                            </div>
                        </div>

                        <!-- 6. KEUANGAN & SPP -->
                        <div v-if="isGlobalAdmin || hasRole(['finance', 'staff_admin_keuangan'])" class="space-y-3 bg-slate-50/70 rounded-3xl p-3.5 sm:p-4 border border-slate-200/60">
                            <div class="flex items-center gap-2 px-1">
                                <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                <p class="text-[11px] font-black uppercase text-slate-700 tracking-wider">
                                    Keuangan & SPP
                                </p>
                            </div>
                            <div class="grid grid-cols-4 gap-y-4 gap-x-2 text-center">
                                <Link 
                                    :href="safeRoute('yayasan.finance.dashboard')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-teal-50/70 border border-white ring-1 ring-teal-500/25 shadow-[0_8px_18px_-3px_rgba(13,148,136,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <BanknotesIcon class="w-6 h-6 sm:w-7 sm:h-7 text-teal-700 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-teal-700 transition-colors">Keuangan</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('yayasan.finance.bills.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-emerald-50/70 border border-white ring-1 ring-emerald-400/25 shadow-[0_8px_18px_-3px_rgba(16,185,129,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <ClipboardDocumentListIcon class="w-6 h-6 sm:w-7 sm:h-7 text-emerald-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-emerald-600 transition-colors">Tagihan</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('yayasan.finance.transactions.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-sky-50/70 border border-white ring-1 ring-sky-400/25 shadow-[0_8px_18px_-3px_rgba(2,132,199,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <ArrowPathRoundedSquareIcon class="w-6 h-6 sm:w-7 sm:h-7 text-sky-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-sky-600 transition-colors">Pembayaran</span>
                                </Link>
                            </div>
                        </div>

                        <!-- 7. UMUM & KEPEGAWAIAN -->
                        <div class="space-y-3 bg-slate-50/70 rounded-3xl p-3.5 sm:p-4 border border-slate-200/60">
                            <div class="flex items-center gap-2 px-1">
                                <div class="w-2 h-2 rounded-full bg-teal-500"></div>
                                <p class="text-[11px] font-black uppercase text-slate-700 tracking-wider">
                                    Akun & Kepegawaian
                                </p>
                            </div>
                            <div class="grid grid-cols-4 gap-y-4 gap-x-2 text-center">
                                <Link 
                                    v-if="hasRole(['admin_unit', 'super_admin_yayasan', 'admin_yayasan'])"
                                    :href="safeRoute('yayasan.staff.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-teal-50/70 border border-white ring-1 ring-teal-400/25 shadow-[0_8px_18px_-3px_rgba(20,184,166,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <UserGroupIcon class="w-6 h-6 sm:w-7 sm:h-7 text-teal-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-teal-700 transition-colors">Data Staf</span>
                                </Link>

                                <Link 
                                    v-if="isGlobalAdmin || hasRole(['kepala_sekolah', 'admin_unit', 'pembina_yayasan', 'pengawas_yayasan', 'staff_yayasan'])"
                                    :href="safeRoute('attendance-approvals.index', {}, safeRoute('yayasan.attendance-approvals.index'))" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-amber-50/70 border border-white ring-1 ring-amber-400/25 shadow-[0_8px_18px_-3px_rgba(245,158,11,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <CheckCircleIcon class="w-6 h-6 sm:w-7 sm:h-7 text-amber-500 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-amber-600 transition-colors">ACC Izin</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('attendance.index', { tab: 'personal' })" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-emerald-50/70 border border-white ring-1 ring-emerald-400/25 shadow-[0_8px_18px_-3px_rgba(16,185,129,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <FingerPrintIcon class="w-6 h-6 sm:w-7 sm:h-7 text-emerald-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-emerald-600 transition-colors">Presensi Saya</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('employee.activity-logs.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-teal-50/70 border border-white ring-1 ring-teal-400/25 shadow-[0_8px_18px_-3px_rgba(20,184,166,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <ClipboardDocumentCheckIcon class="w-6 h-6 sm:w-7 sm:h-7 text-teal-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-teal-700 transition-colors">Giat Tugas</span>
                                </Link>

                                <Link 
                                    v-if="isGlobalAdmin || hasRole(['kepala_sekolah', 'admin_unit', 'staff_yayasan'])"
                                    :href="safeRoute('yayasan.activity-logs.feed')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-purple-50/70 border border-white ring-1 ring-purple-400/25 shadow-[0_8px_18px_-3px_rgba(147,51,234,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <SparklesIcon class="w-6 h-6 sm:w-7 sm:h-7 text-purple-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-purple-600 transition-colors">Linimasa SDM</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('profile.edit')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-slate-100/70 border border-white ring-1 ring-slate-400/25 shadow-[0_8px_18px_-3px_rgba(100,116,139,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <UserCircleIcon class="w-6 h-6 sm:w-7 sm:h-7 text-slate-700 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 tracking-tight leading-tight mt-0.5 group-hover:text-slate-700 transition-colors">Profil Saya</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('logout')" 
                                    method="post"
                                    as="button"
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center gap-1.5 group active:scale-95 transition-transform"
                                >
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl sm:rounded-[22px] bg-gradient-to-b from-white via-white/95 to-rose-50/70 border border-white ring-1 ring-rose-400/25 shadow-[0_8px_18px_-3px_rgba(239,68,68,0.28)] flex items-center justify-center group-hover:scale-105 active:scale-90 transition-all duration-150">
                                        <ArrowRightOnRectangleIcon class="w-6 h-6 sm:w-7 sm:h-7 text-rose-600 stroke-[2.3]" />
                                    </div>
                                    <span class="text-[11px] sm:text-xs font-bold text-rose-600 tracking-tight leading-tight mt-0.5 group-hover:text-rose-700 transition-colors">Keluar</span>
                                </Link>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Unit Switcher Bottom Sheet Modal for Mobile -->
        <Teleport to="body">
            <div v-if="showUnitModal" class="fixed inset-0 z-[110] overflow-hidden">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm transition-opacity" @click="showUnitModal = false"></div>
                
                <!-- Bottom Sheet Card -->
                <div class="fixed inset-x-0 bottom-0 bg-white rounded-t-3xl max-h-[80vh] overflow-y-auto shadow-2xl p-6 border-t border-slate-100 flex flex-col gap-4 animate-in slide-in-from-bottom duration-300">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h3 class="font-extrabold text-base text-slate-800 flex items-center gap-2">
                                <BuildingOfficeIcon class="w-5 h-5 text-teal-600" />
                                <span>Pilih Unit Monitoring</span>
                            </h3>
                            <p class="text-xs text-slate-400">Pilih unit sekolah untuk memantau data operasional</p>
                        </div>
                        <button @click="showUnitModal = false" class="p-2 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- List of Units -->
                    <div class="space-y-2.5 pt-1">
                        <button
                            v-for="u in availableUnits"
                            :key="u.id"
                            @click="switchUnit(u.id)"
                            type="button"
                            class="w-full p-4 rounded-2xl border text-left flex items-center justify-between transition-all active:scale-[0.98]"
                            :class="u.id === activeUnitId 
                                ? 'bg-teal-50/90 border-teal-500 text-teal-900 shadow-sm ring-1 ring-teal-500/30 font-extrabold' 
                                : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-700 font-bold'"
                        >
                            <div class="flex items-center gap-3">
                                <div 
                                    class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-xs overflow-hidden"
                                    :class="u.id === activeUnitId ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600'"
                                >
                                    <img v-if="u.logo_url" :src="u.logo_url" class="w-full h-full object-cover" />
                                    <span v-else>{{ u.name.substring(0, 2).toUpperCase() }}</span>
                                </div>
                                <div>
                                    <p class="text-sm tracking-tight leading-tight">{{ u.name }}</p>
                                    <span class="text-[10px] font-medium text-slate-400">
                                        {{ u.id === activeUnitId ? 'Unit Aktif Saat Ini' : 'Ketuk untuk beralih' }}
                                    </span>
                                </div>
                            </div>

                            <CheckCircleIcon v-if="u.id === activeUnitId" class="w-6 h-6 text-teal-600" />
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom, 20px);
}
</style>

<style scoped>
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom, 20px);
}
</style>

