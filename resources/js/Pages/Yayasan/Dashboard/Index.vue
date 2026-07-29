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
    BanknotesIcon
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
        <div class="block md:hidden space-y-6 pb-6">

            <!-- 1. FULL CUTOUT PERSON HERO CARD -->
            <div class="relative pt-6">
                <!-- Main Gradient Card Body (Namira Teal to Deep Slate #0f172a) -->
                <div class="relative overflow-visible rounded-3xl bg-gradient-to-br from-[#009688] to-[#0f172a] p-6 border border-teal-800/60 shadow-xl min-h-[170px] flex flex-col justify-center">
                    
                    <!-- Right Text Info (50% width on the far right with clear spacing) -->
                    <div class="w-[50%] ml-auto pl-2 my-1 text-left z-20">
                        <p class="text-xs font-bold text-slate-400">Selamat Datang,</p>
                        <h3 class="font-black text-2xl text-white tracking-tight leading-tight mt-1 drop-shadow-sm truncate">
                            {{ user?.name }}
                        </h3>
                        <p class="text-xs font-bold text-teal-400 mt-1.5 truncate">
                            {{ userData?.role_title || (teacherData?.homeroom_class ? 'Wali Kelas ' + teacherData.homeroom_class : (teacherData?.title || 'Pegawai Yayasan')) }}
                        </p>
                    </div>

                    <!-- Right Action Button (Dynamic Status Button) -->
                    <div class="pt-3 w-[50%] ml-auto pl-2 z-20">
                        <Link 
                            :href="safeRoute('attendance.index')" 
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 bg-white hover:bg-teal-50 text-slate-950 font-extrabold text-xs rounded-full shadow-md transition-all active:scale-95 border border-slate-200"
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

                    <!-- 🌟 FULL CUTOUT PERSON PHOTO -->
                    <div class="absolute left-1 bottom-0 h-[125%] w-[42%] flex items-end justify-center pointer-events-none z-10">
                        <img 
                            :src="user?.profile_photo_url || user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'Pegawai')}&background=0d9488&color=fff&size=256`" 
                            :alt="user?.name" 
                            class="h-full max-h-[225px] w-auto object-contain object-bottom drop-shadow-[0_15px_15px_rgba(0,0,0,0.6)] filter"
                        />
                    </div>
                </div>
            </div>

            <!-- 2. Employee Attendance Status Banner (Works for ALL roles: Satpam, Staff, Teacher, Admin) -->
            <div class="bg-white border border-slate-200 rounded-3xl p-4 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-teal-50 text-teal-700 border border-teal-100 flex items-center justify-center">
                        <FingerPrintIcon class="w-5 h-5 stroke-[2.2]" />
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Presensi Masuk Saya</p>
                        <div v-if="userData?.attendance_status?.checked_in" class="flex items-center gap-2">
                            <span class="font-black text-base text-slate-800">
                                {{ userData.attendance_status.time }} WIB
                            </span>
                            <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                                • {{ userData.attendance_status.status }}
                            </span>
                        </div>
                        <div v-else class="flex items-center gap-2">
                            <span class="font-black text-sm text-amber-700">
                                Belum Presensi Masuk
                            </span>
                        </div>
                    </div>
                </div>

                <Link 
                    :href="safeRoute('attendance.index')" 
                    class="text-xs font-extrabold text-teal-800 bg-slate-50 hover:bg-teal-700 hover:text-white px-3.5 py-2 rounded-xl border border-slate-200 shadow-sm transition-colors"
                >
                    {{ userData?.attendance_status?.checked_in ? 'Detail Absensi' : 'Presensi Sekarang' }}
                </Link>
            </div>

            <!-- 3. LIVE HERO CARD (ROLE-AWARE) -->
            <!-- Case A: Teacher with active teaching schedule -->
            <div 
                v-if="teacherData?.current_schedule" 
                class="rounded-3xl bg-gradient-to-br from-[#009688] to-[#0f172a] p-6 text-white shadow-md border border-teal-800/60"
            >
                <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-teal-300 mb-3">
                    <span class="w-2 h-2 rounded-full bg-teal-400"></span>
                    <span>Mengajar Sekarang ({{ teacherData.current_schedule.start_time }} - {{ teacherData.current_schedule.end_time }} WIB)</span>
                </div>

                <h3 class="font-extrabold text-2xl tracking-tight leading-snug mb-1">
                    {{ teacherData.current_schedule.subject_name }} — Kelas {{ teacherData.current_schedule.classroom_name }}
                </h3>

                <p class="text-xs text-teal-200/90 font-medium flex items-center gap-1.5 mb-6">
                    <MapPinIcon class="w-4 h-4 text-teal-300" />
                    <span>Ruang Kelas {{ teacherData.current_schedule.classroom_name }} • Gedung Utama</span>
                </p>

                <Link 
                    :href="safeRoute('yayasan.teaching-journal.create', { schedule_id: teacherData.current_schedule.id })" 
                    class="w-full py-3.5 px-4 bg-white hover:bg-teal-50 text-slate-900 font-extrabold text-sm rounded-2xl shadow-sm flex items-center justify-center gap-2.5 transition-all active:scale-95 border border-slate-200"
                >
                    <PencilSquareIcon class="w-5 h-5 text-teal-700" />
                    <span>Isi Jurnal & Absensi Kelas Ini</span>
                </Link>
            </div>

            <!-- Case B: Teacher without active schedule today -->
            <div 
                v-else-if="teacherData" 
                class="rounded-3xl bg-gradient-to-br from-[#009688] to-[#0f172a] p-6 text-white shadow-md border border-teal-800/60"
            >
                <h3 class="font-black text-xl tracking-tight leading-snug mb-2">
                    Tidak Ada Jam Pelajaran Mengajar
                </h3>
                <p class="text-xs text-slate-400 font-medium leading-relaxed mb-6">
                    Selamat bertugas atau beristirahat, {{ user?.name }}. Anda dapat mengecek seluruh ringkasan jadwal mengajar Anda.
                </p>

                <Link 
                    :href="safeRoute('yayasan.schedules.index')" 
                    class="w-full py-3 px-4 bg-teal-600 hover:bg-teal-500 text-white font-bold text-xs rounded-2xl flex items-center justify-center gap-2 transition-all active:scale-95"
                >
                    <CalendarDaysIcon class="w-4 h-4" />
                    <span>Lihat Semua Jadwal Saya</span>
                </Link>
            </div>

            <!-- Case C: Non-Teacher (Satpam, Staf Administrasi, Admin, Sarpras, Finance) -->
            <div 
                v-else 
                class="rounded-3xl bg-gradient-to-br from-[#009688] to-[#0f172a] p-6 text-white shadow-md border border-teal-800/60"
            >
                <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-teal-300 mb-3">
                    <span class="w-2 h-2 rounded-full bg-teal-400"></span>
                    <span>Status Operasional • {{ userData?.today_date }}</span>
                </div>

                <h3 class="font-black text-xl sm:text-2xl tracking-tight leading-snug mb-2">
                    Portal Tugas {{ userData?.role_title || 'Pegawai' }}
                </h3>
                <p class="text-xs text-slate-300 font-medium leading-relaxed mb-6">
                    Selamat bertugas, {{ user?.name }}. Pastikan presensi masuk pegawai sudah tercatat dan gunakan pintasan menu di bawah untuk melaksanakan tugas operasional.
                </p>

                <Link 
                    :href="safeRoute('attendance.index')" 
                    class="w-full py-3.5 px-4 bg-white hover:bg-teal-50 text-slate-900 font-extrabold text-xs sm:text-sm rounded-2xl shadow-sm flex items-center justify-center gap-2.5 transition-all active:scale-95 border border-slate-200"
                >
                    <FingerPrintIcon class="w-5 h-5 text-teal-700" />
                    <span>{{ userData?.attendance_status?.checked_in ? 'Lihat Detail Absensi Pegawai' : 'Buka Form Presensi Pegawai' }}</span>
                </Link>
            </div>

            <!-- 4. QUICK ACTIONS GRID (Dynamic Multi-Role App Grid) -->
            <div class="grid grid-cols-2 gap-3.5">
                <!-- 0. Presensi Pegawai (All Employees) -->
                <Link 
                    :href="safeRoute('attendance.index')"
                    class="bg-white rounded-3xl p-4 border border-slate-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between h-32 group"
                >
                    <div class="w-10 h-10 rounded-2xl bg-teal-100/80 text-teal-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <FingerPrintIcon class="w-5 h-5 stroke-[2.2]" />
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-800">Presensi Pegawai</h4>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Clock-in / Clock-out HP</p>
                    </div>
                </Link>
                <!-- 1. Isi Jurnal Mapel (Guru Only) -->
                <Link 
                    v-if="isTeacher || hasRole('teacher')"
                    :href="safeRoute('yayasan.teaching-journal.index')"
                    class="bg-white rounded-3xl p-4 border border-slate-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between h-32 group"
                >
                    <div class="w-10 h-10 rounded-2xl bg-emerald-100/80 text-emerald-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <PencilSquareIcon class="w-5 h-5 stroke-[2.2]" />
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-800">Isi Jurnal Mapel</h4>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Update materi & presensi</p>
                    </div>
                </Link>

                <!-- 2. Absensi Kelas (Wali Kelas Only) -->
                <Link 
                    v-if="hasRole('wali_kelas')"
                    :href="safeRoute('yayasan.student-attendance.index')"
                    class="bg-white rounded-3xl p-4 border border-slate-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between h-32 group"
                >
                    <div class="w-10 h-10 rounded-2xl bg-blue-100/80 text-blue-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <ClipboardDocumentCheckIcon class="w-5 h-5 stroke-[2.2]" />
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-800">Absensi Kelas</h4>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Monitor siswa harian</p>
                    </div>
                </Link>

                <!-- 3. Scan Gerbang (All Users) -->
                <Link 
                    :href="safeRoute('yayasan.student-checkin.index')"
                    class="bg-white rounded-3xl p-4 border border-slate-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between h-32 group"
                >
                    <div class="w-10 h-10 rounded-2xl bg-teal-100/80 text-teal-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <QrCodeIcon class="w-5 h-5 stroke-[2.2]" />
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-800">Scan Gerbang</h4>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Akses scanner presensi</p>
                    </div>
                </Link>

                <!-- 4. Konseling BK (BK / Wali Kelas) -->
                <Link 
                    v-if="hasRole(['bk', 'counseling', 'wali_kelas'])"
                    :href="safeRoute('counseling.sessions.index')"
                    class="bg-white rounded-3xl p-4 border border-slate-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between h-32 group"
                >
                    <div class="w-10 h-10 rounded-2xl bg-indigo-100/80 text-indigo-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <ChatBubbleLeftRightIcon class="w-5 h-5 stroke-[2.2]" />
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-800">Konseling BK</h4>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Laporan & poin siswa</p>
                    </div>
                </Link>

                <!-- 5. Berita Humas (Humas Only) -->
                <Link 
                    v-if="hasRole('humas_unit')"
                    :href="safeRoute('public-relations.news.index')"
                    class="bg-white rounded-3xl p-4 border border-slate-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between h-32 group"
                >
                    <div class="w-10 h-10 rounded-2xl bg-blue-100/80 text-blue-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <NewspaperIcon class="w-5 h-5 stroke-[2.2]" />
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-800">Berita Sekolah</h4>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Publikasi & warta</p>
                    </div>
                </Link>

                <!-- 6. Destinasi Kampus (Humas Only) -->
                <Link 
                    v-if="hasRole('humas_unit')"
                    :href="safeRoute('public-relations.university-destinations.index')"
                    class="bg-white rounded-3xl p-4 border border-slate-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between h-32 group"
                >
                    <div class="w-10 h-10 rounded-2xl bg-sky-100/80 text-sky-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <GlobeAltIcon class="w-5 h-5 stroke-[2.2]" />
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-800">Destinasi Kampus</h4>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Kunjungan & alumni</p>
                    </div>
                </Link>

                <!-- 7. Lapor Sarpar (Sarpar / Admin Only) -->
                <Link 
                    v-if="hasRole(['koordinator_sarpar', 'admin_unit'])"
                    :href="safeRoute('sarpar.maintenance.index')"
                    class="bg-white rounded-3xl p-4 border border-slate-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between h-32 group"
                >
                    <div class="w-10 h-10 rounded-2xl bg-amber-100/80 text-amber-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <WrenchScrewdriverIcon class="w-5 h-5 stroke-[2.2]" />
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-800">Sarpras Unit</h4>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Pemeliharaan aset</p>
                    </div>
                </Link>

                <!-- 8. Keuangan Unit (Finance Only) -->
                <Link 
                    v-if="hasRole('finance')"
                    :href="safeRoute('finance.dashboard')"
                    class="bg-white rounded-3xl p-4 border border-slate-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between h-32 group"
                >
                    <div class="w-10 h-10 rounded-2xl bg-emerald-100/80 text-emerald-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <BanknotesIcon class="w-5 h-5 stroke-[2.2]" />
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-800">Keuangan Unit</h4>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">SPP & pembayaran</p>
                    </div>
                </Link>
            </div>

            <!-- 5. HOMEROOM CLASS ATTENDANCE SUMMARY WIDGET (Jika User = Wali Kelas) -->
            <div v-if="teacherData?.homeroom_stats" class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-extrabold text-base text-slate-800">
                            Kehadiran Kelas {{ teacherData.homeroom_stats.class_name }}
                        </h4>
                        <p class="text-xs text-slate-400 font-medium mt-0.5">
                            {{ teacherData.today_date }}
                        </p>
                    </div>

                    <div class="text-right">
                        <span class="text-2xl font-black text-teal-800 leading-none">
                            {{ teacherData.homeroom_stats.rate }}%
                        </span>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                    <div 
                        class="h-full bg-teal-700 rounded-full transition-all duration-500" 
                        :style="{ width: `${teacherData.homeroom_stats.rate}%` }"
                    ></div>
                </div>

                <!-- Stat Pills 4 Grid -->
                <div class="grid grid-cols-4 gap-2 pt-1">
                    <!-- Hadir -->
                    <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-2 text-center">
                        <span class="block font-black text-lg text-emerald-800 leading-none">
                            {{ teacherData.homeroom_stats.present }}
                        </span>
                        <span class="text-[9px] font-black uppercase text-emerald-700 tracking-wider">Hadir</span>
                    </div>

                    <!-- Sakit -->
                    <div class="bg-blue-50 border border-blue-100 rounded-2xl p-2 text-center">
                        <span class="block font-black text-lg text-blue-800 leading-none">
                            {{ teacherData.homeroom_stats.sick }}
                        </span>
                        <span class="text-[9px] font-black uppercase text-blue-700 tracking-wider">Sakit</span>
                    </div>

                    <!-- Izin -->
                    <div class="bg-amber-50 border border-amber-100 rounded-2xl p-2 text-center">
                        <span class="block font-black text-lg text-amber-800 leading-none">
                            {{ teacherData.homeroom_stats.permission }}
                        </span>
                        <span class="text-[9px] font-black uppercase text-amber-700 tracking-wider">Izin</span>
                    </div>

                    <!-- Alpha -->
                    <div class="bg-rose-50 border border-rose-100 rounded-2xl p-2 text-center">
                        <span class="block font-black text-lg text-rose-800 leading-none">
                            {{ teacherData.homeroom_stats.alpha }}
                        </span>
                        <span class="text-[9px] font-black uppercase text-rose-700 tracking-wider">Alpha</span>
                    </div>
                </div>

                <!-- CTA Action jika belum diabsen hari ini -->
                <div v-if="!teacherData.homeroom_stats.has_attendance" class="pt-2">
                    <Link 
                        :href="safeRoute('yayasan.student-attendance.show', teacherData.homeroom_stats.classroom_id)"
                        class="w-full py-2.5 px-3 bg-teal-50 hover:bg-teal-100 text-teal-800 font-extrabold text-xs rounded-xl flex items-center justify-center gap-2 transition-colors border border-teal-200/60"
                    >
                        <ClipboardDocumentCheckIcon class="w-4 h-4" />
                        <span>Mulai Absensi Kelas Sekarang</span>
                    </Link>
                </div>
            </div>

            <!-- 6. UPCOMING AGENDA FEED -->
            <div class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <h4 class="font-extrabold text-base text-slate-800 flex items-center gap-2">
                        <span>Agenda Terdekat</span>
                    </h4>
                    <Link :href="safeRoute('yayasan.holidays.index')" class="text-xs font-bold text-teal-700 hover:underline">
                        Lihat Semua →
                    </Link>
                </div>

                <div v-if="upcomingEvents && upcomingEvents.length > 0" class="space-y-2.5">
                    <div 
                        v-for="event in upcomingEvents.slice(0, 3)" 
                        :key="event.id"
                        class="bg-white rounded-2xl p-3.5 border border-slate-100 shadow-sm flex items-center justify-between gap-3 hover:border-teal-200 transition-all"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-2xl bg-teal-50 border border-teal-100 flex flex-col items-center justify-center text-teal-800 font-black leading-none">
                                <span class="text-[9px] font-bold uppercase text-teal-600">{{ new Date(event.date).toLocaleDateString('en-US', { month: 'short' }) }}</span>
                                <span class="text-sm mt-0.5">{{ new Date(event.date).getDate() }}</span>
                            </div>

                            <div>
                                <h5 class="font-extrabold text-sm text-slate-800 truncate max-w-[200px]">{{ event.description }}</h5>
                                <p class="text-[10px] text-slate-400 font-medium mt-0.5">
                                    {{ getDaysFromNow(event.date) }} • {{ formatDate(event.date) }}
                                </p>
                            </div>
                        </div>

                        <ChevronRightIcon class="w-4 h-4 text-slate-300" />
                    </div>
                </div>

                <div v-else class="bg-white rounded-2xl p-6 text-center text-slate-400 border border-slate-100">
                    <p class="text-xs font-bold">Tidak ada agenda dalam 30 hari ke depan</p>
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

