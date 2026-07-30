<script setup>
import { ref, computed } from 'vue';
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
    CheckCircleIcon
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
                <!-- Pengawas: Monitoring -->
                <Link 
                    v-if="isPengawas"
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
                    :href="safeRoute('attendance.index')"
                    class="flex flex-col items-center gap-1 py-1 px-3 transition-all duration-200 active:scale-95 flex-1"
                    :class="isRouteActive('attendance.*') ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
                >
                    <component 
                        :is="isRouteActive('attendance.*') ? FingerPrintIconSolid : FingerPrintIcon" 
                        class="w-6 h-6 transition-transform" 
                    />
                    <span class="text-[10px] font-bold tracking-tight">Presensi</span>
                </Link>

                <!-- 3. CENTER FAB: Monitoring & Statistik untuk Pengawas / QR Scanner untuk semua -->
                <div class="flex-1 flex justify-center -mt-6">
                    <!-- Pengawas: Statistik & Monitoring -->
                    <Link 
                        v-if="isPengawas"
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
                <!-- Pengawas: Monitoring -->
                <Link 
                    v-if="isPengawas"
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

                <!-- BK: Konseling -->
                <Link 
                    v-else-if="hasRole(['bk', 'counseling'])"
                    :href="safeRoute('counseling.sessions.index')"
                    class="flex flex-col items-center gap-1 py-1 px-3 transition-all duration-200 active:scale-95 flex-1"
                    :class="isRouteActive('counseling.*') ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
                >
                    <ChatBubbleLeftRightIcon class="w-6 h-6 transition-transform" />
                    <span class="text-[10px] font-bold tracking-tight">Konseling</span>
                </Link>

                <!-- Staff biasa / Guru: Presensi Pegawai -->
                <Link 
                    v-else
                    :href="safeRoute('employee.attendance.index')"
                    class="flex flex-col items-center gap-1 py-1 px-3 transition-all duration-200 active:scale-95 flex-1"
                    :class="isRouteActive('employee.attendance.*') || isRouteActive('attendance.*') ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
                >
                    <ClipboardDocumentCheckIcon class="w-6 h-6 transition-transform" />
                    <span class="text-[10px] font-bold tracking-tight">Presensi</span>
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
                <div class="fixed inset-x-0 bottom-0 bg-white rounded-t-3xl max-h-[85vh] overflow-y-auto shadow-2xl p-6 border-t border-slate-100 flex flex-col gap-6 animate-in slide-in-from-bottom duration-300">
                    <!-- Drawer Header -->
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div>
                            <h3 class="font-extrabold text-lg text-slate-800">Menu Lengkap Saya</h3>
                            <p class="text-xs text-slate-400">Seluruh fitur layanan yang Anda miliki saat ini</p>
                        </div>
                        <button @click="showDrawer = false" class="p-2 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Dynamic Role-Based App Categorized Groups -->
                    <div class="space-y-6">

                        <!-- 1. AKADEMIK & MENGAJAR -->
                        <div v-if="isTeacher || isGlobalAdmin || hasRole(['teacher', 'wali_kelas', 'koordinator_kurikulum'])" class="space-y-2">
                            <p class="text-[11px] font-black uppercase text-teal-700 tracking-wider flex items-center gap-1.5">
                                <BookOpenIcon class="w-4 h-4" />
                                <span>Akademik & Kurikulum</span>
                            </p>
                            <div class="grid grid-cols-3 gap-3">
                                <Link 
                                    :href="safeRoute('yayasan.schedules.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-teal-50/60 border border-teal-100/80 hover:bg-teal-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-teal-600 text-white flex items-center justify-center shadow-md">
                                        <CalendarIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Jadwal Pelajaran</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('yayasan.teaching-journal.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-emerald-50/60 border border-emerald-100/80 hover:bg-emerald-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-md">
                                        <BookOpenIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Jurnal Mapel</span>
                                </Link>

                                <Link 
                                    v-if="hasRole('koordinator_kurikulum')"
                                    :href="safeRoute('yayasan.subjects.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-sky-50/60 border border-sky-100/80 hover:bg-sky-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-sky-600 text-white flex items-center justify-center shadow-md">
                                        <BookOpenIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Mata Pelajaran</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('yayasan.learning-objectives.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-amber-50/60 border border-amber-100/80 hover:bg-amber-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-amber-600 text-white flex items-center justify-center shadow-md">
                                        <TrophyIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Tujuan Pembelajaran</span>
                                </Link>

                                <Link 
                                    v-if="hasRole(['admin_unit', 'admin_yayasan', 'super_admin_yayasan'])"
                                    :href="safeRoute('yayasan.promotion.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-purple-50/60 border border-purple-100/80 hover:bg-purple-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center shadow-md">
                                        <SparklesIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Kenaikan Kelas</span>
                                </Link>

                                <Link 
                                    v-if="hasRole('wali_kelas')"
                                    :href="safeRoute('yayasan.student-attendance.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-cyan-50/60 border border-cyan-100/80 hover:bg-cyan-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-cyan-600 text-white flex items-center justify-center shadow-md">
                                        <ClipboardDocumentCheckIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Absensi Siswa Wali</span>
                                </Link>
                            </div>
                        </div>

                        <!-- 2. BIMBINGAN & KONSELING -->
                        <div v-if="isGlobalAdmin || hasRole(['bk', 'counseling', 'wali_kelas'])" class="space-y-2">
                            <p class="text-[11px] font-black uppercase text-indigo-700 tracking-wider flex items-center gap-1.5">
                                <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                <span>Bimbingan Konseling (BK)</span>
                            </p>
                            <div class="grid grid-cols-3 gap-3">
                                <Link 
                                    :href="safeRoute('counseling.sessions.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-indigo-50/60 border border-indigo-100/80 hover:bg-indigo-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-md">
                                        <ChatBubbleLeftRightIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Sesi Konseling</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('counseling.violations.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-rose-50/60 border border-rose-100/80 hover:bg-rose-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center shadow-md">
                                        <ExclamationTriangleIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Pelanggaran</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('counseling.achievements.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-amber-50/60 border border-amber-100/80 hover:bg-amber-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-amber-600 text-white flex items-center justify-center shadow-md">
                                        <TrophyIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Prestasi Siswa</span>
                                </Link>
                            </div>
                        </div>

                        <!-- 3. HUMAS & PUBLIKASI -->
                        <div v-if="isGlobalAdmin || hasRole('humas_unit')" class="space-y-2">
                            <p class="text-[11px] font-black uppercase text-blue-700 tracking-wider flex items-center gap-1.5">
                                <MegaphoneIcon class="w-4 h-4" />
                                <span>Humas & Hubungan Masyarakat</span>
                            </p>
                            <div class="grid grid-cols-3 gap-3">
                                <Link 
                                    :href="safeRoute('public-relations.news.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-blue-50/60 border border-blue-100/80 hover:bg-blue-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md">
                                        <NewspaperIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Berita Sekolah</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('public-relations.university-destinations.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-sky-50/60 border border-sky-100/80 hover:bg-sky-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-sky-600 text-white flex items-center justify-center shadow-md">
                                        <GlobeAltIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Destinasi Kampus</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('public-relations.events.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-violet-50/60 border border-violet-100/80 hover:bg-violet-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-violet-600 text-white flex items-center justify-center shadow-md">
                                        <SparklesIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Event Humas</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('public-relations.testimonials.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-pink-50/60 border border-pink-100/80 hover:bg-pink-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-pink-600 text-white flex items-center justify-center shadow-md">
                                        <ChatBubbleLeftRightIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Testimoni Alumni</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('public-relations.partners.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-indigo-50/60 border border-indigo-100/80 hover:bg-indigo-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-md">
                                        <BuildingOfficeIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Mitra Sekolah</span>
                                </Link>
                            </div>
                        </div>

                        <!-- 4. SARANA & PRASARANA -->
                        <div v-if="isGlobalAdmin || hasRole(['koordinator_sarpar', 'admin_unit', 'super_admin_yayasan', 'admin_yayasan', 'kepala_sekolah'])" class="space-y-2">
                            <p class="text-[11px] font-black uppercase text-amber-700 tracking-wider flex items-center gap-1.5">
                                <CubeIcon class="w-4 h-4" />
                                <span>Sarana & Prasarana (Sarpar)</span>
                            </p>
                            <div class="grid grid-cols-3 gap-3">
                                <Link 
                                    :href="safeRoute('sarpar.dashboard')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-slate-50 border border-slate-200/80 hover:bg-slate-100 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-slate-700 text-white flex items-center justify-center shadow-md">
                                        <HomeIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Dashboard</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('sarpar.inventories.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-teal-50/60 border border-teal-100/80 hover:bg-teal-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-teal-600 text-white flex items-center justify-center shadow-md">
                                        <CubeIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Inventaris</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('sarpar.loans.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-blue-50/60 border border-blue-100/80 hover:bg-blue-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md">
                                        <ArrowPathRoundedSquareIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Peminjaman</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('sarpar.maintenance.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-amber-50/60 border border-amber-100/80 hover:bg-amber-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-amber-600 text-white flex items-center justify-center shadow-md">
                                        <WrenchScrewdriverIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Pemeliharaan</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('sarpar.rooms.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-purple-50/60 border border-purple-100/80 hover:bg-purple-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center shadow-md">
                                        <BuildingOfficeIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Ruangan</span>
                                </Link>
                            </div>
                        </div>

                        <!-- 5. KEUANGAN -->
                        <div v-if="isGlobalAdmin || hasRole('finance')" class="space-y-2">
                            <p class="text-[11px] font-black uppercase text-emerald-700 tracking-wider flex items-center gap-1.5">
                                <BanknotesIcon class="w-4 h-4" />
                                <span>Keuangan & SPP</span>
                            </p>
                            <div class="grid grid-cols-3 gap-3">
                                <Link 
                                    :href="safeRoute('finance.dashboard')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-emerald-50/60 border border-emerald-100/80 hover:bg-emerald-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-md">
                                        <BanknotesIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Keuangan Unit</span>
                                </Link>
                            </div>
                        </div>

                        <!-- 6. UMUM & PEGAWAI (Semua User) -->
                        <div class="space-y-2 pt-2 border-t border-slate-100">
                            <p class="text-[11px] font-black uppercase text-slate-500 tracking-wider flex items-center gap-1.5">
                                <UserCircleIcon class="w-4 h-4" />
                                <span>Akun & Kepegawaian</span>
                            </p>
                            <div class="grid grid-cols-3 gap-3">
                                <Link 
                                    :href="safeRoute('attendance.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-teal-50/60 border border-teal-100/80 hover:bg-teal-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-teal-600 text-white flex items-center justify-center shadow-md">
                                        <FingerPrintIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Presensi Saya</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('employee.activity-logs.index')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-teal-50/60 border border-teal-100/80 hover:bg-teal-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-md">
                                        <ClipboardDocumentCheckIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Giat Tugas Saya</span>
                                </Link>

                                <Link 
                                    v-if="isGlobalAdmin || hasRole(['kepala_sekolah', 'admin_unit', 'staff_yayasan'])"
                                    :href="safeRoute('activity-logs.feed')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-purple-50/60 border border-purple-100/80 hover:bg-purple-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center shadow-md">
                                        <SparklesIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Linimasa SDM</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('profile.edit')" 
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-slate-50 border border-slate-200/80 hover:bg-slate-100 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-slate-700 text-white flex items-center justify-center shadow-md">
                                        <UserCircleIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">Profil Saya</span>
                                </Link>

                                <Link 
                                    :href="safeRoute('logout')" 
                                    method="post"
                                    as="button"
                                    @click="showDrawer = false"
                                    class="flex flex-col items-center p-3 rounded-2xl bg-rose-50/60 border border-rose-100/80 hover:bg-rose-100/80 text-center gap-2 transition-all active:scale-95"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center shadow-md">
                                        <ArrowRightOnRectangleIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-bold text-rose-700">Keluar</span>
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
                                    class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-xs"
                                    :class="u.id === activeUnitId ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600'"
                                >
                                    {{ u.name.substring(0, 2).toUpperCase() }}
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

