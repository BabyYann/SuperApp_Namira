<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import QuranCollapse from '@/Pages/Quran/Components/QuranCollapse.vue';
import {
    ArrowLeftIcon,
    EnvelopeIcon,
    BuildingLibraryIcon,
    IdentificationIcon,
    AcademicCapIcon,
    CreditCardIcon,
    UserIcon,
    PhoneIcon,
    BookOpenIcon,
    ClockIcon,
    ArrowRightIcon,
    PrinterIcon,
    DocumentArrowDownIcon,
    UserGroupIcon,
    TrophyIcon,
    ShieldCheckIcon,
    ShieldExclamationIcon,
    BanknotesIcon,
    ChevronDownIcon,
    CheckBadgeIcon,
    LockClosedIcon,
    MapPinIcon,
    CalendarDaysIcon,
    ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    student: Object,
    permissions: {
        type: Object,
        default: () => ({
            can_view_finance: false,
            can_view_counseling_notes: false,
            can_print_quran_report: false,
            is_global_admin: false,
        }),
    },
    quranProfile: { type: Object, default: null },
    attendanceSummary: { type: Object, default: () => ({ present: 0, sick: 0, permission: 0, alpha: 0 }) },
    recentCheckins: { type: Array, default: () => [] },
    classPromotions: { type: Array, default: () => [] },
    extracurriculars: { type: Array, default: () => [] },
    counselingSummary: {
        type: Object,
        default: () => ({ total_points: 0, status: 'AMAN', violations: [], achievements: [], sessions: [] }),
    },
    finance: { type: Object, default: null },
});

const quranEnabled = computed(() => Boolean(props.quranProfile?.enabled));
const canViewFinance = computed(() => Boolean(props.permissions?.can_view_finance && props.finance));
const canViewCounselingNotes = computed(() => Boolean(props.permissions?.can_view_counseling_notes));

const tabs = computed(() => {
    const list = [
        { id: 'bio', label: 'Biodata & Keluarga', mobileLabel: 'Biodata', icon: IdentificationIcon },
        { id: 'academic', label: 'Akademik & Absensi', mobileLabel: 'Akademik', icon: AcademicCapIcon },
    ];
    if (quranEnabled.value) {
        list.push({ id: 'quran', label: "Al-Qur'an (Ummi)", mobileLabel: 'Ummi', icon: BookOpenIcon });
    }
    list.push({ id: 'activities', label: 'Ekskul & BK', mobileLabel: 'Ekskul & BK', icon: TrophyIcon });
    if (canViewFinance.value) {
        list.push({ id: 'finance', label: 'Keuangan', mobileLabel: 'Keuangan', icon: BanknotesIcon });
    }
    return list;
});

const activeTab = ref('bio');
const expandedAssessmentIds = ref(new Set(props.quranProfile?.assessments?.[0]?.id ? [props.quranProfile.assessments[0].id] : []));

const toggleAssessment = (id) => {
    const next = new Set(expandedAssessmentIds.value);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    expandedAssessmentIds.value = next;
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatPhoneWa = (phone) => {
    if (!phone) return '#';
    const digits = String(phone).replace(/\D/g, '');
    const normalized = digits.startsWith('0') ? '62' + digits.slice(1) : digits;
    return `https://wa.me/${normalized}`;
};

const homeroomName = computed(() => {
    const c = props.student?.classroom;
    const h = c?.homeroom_teacher || c?.homeroomTeacher;
    return h?.full_name || h?.user?.name || null;
});

const totalAttendanceDays = computed(() => {
    const a = props.attendanceSummary || {};
    return (a.present || 0) + (a.sick || 0) + (a.permission || 0) + (a.alpha || 0);
});

const attendanceRate = computed(() => {
    if (totalAttendanceDays.value === 0) return 0;
    return Math.round(((props.attendanceSummary?.present || 0) / totalAttendanceDays.value) * 100);
});

const counselingBadgeClass = computed(() => {
    const st = props.counselingSummary?.status;
    if (st === 'BAHAYA') return 'bg-rose-50 text-rose-700 border-rose-200/80';
    if (st === 'WASPADA') return 'bg-amber-50 text-amber-700 border-amber-200/80';
    return 'bg-emerald-50 text-emerald-700 border-emerald-200/80';
});

const changeTypeMeta = (type) => {
    if (type === 'promoted') return { text: 'Naik Jilid', dot: 'bg-[#00796B]', badge: 'bg-teal-50 text-[#00796B] border-teal-200/80' };
    if (type === 'transferred') return { text: 'Pindah Kelompok', dot: 'bg-amber-500', badge: 'bg-amber-50 text-amber-700 border-amber-200/80' };
    return { text: 'Penempatan Awal', dot: 'bg-sky-500', badge: 'bg-sky-50 text-sky-700 border-sky-200/80' };
};

const gradePillClass = (grade) => {
    if (grade === 'A') return 'bg-emerald-50 text-emerald-700 border-emerald-200/90';
    if (grade === 'B') return 'bg-sky-50 text-sky-700 border-sky-200/90';
    if (grade === 'C') return 'bg-amber-50 text-amber-700 border-amber-200/90';
    return 'bg-rose-50 text-rose-700 border-rose-200/90';
};

const openQuranReport = (routeName, assessment) => {
    const params = new URLSearchParams();
    if (props.permissions?.is_global_admin && props.student?.unit_id) {
        params.set('unit_id', props.student.unit_id);
    }
    params.set('academic_year_id', assessment.academic_year_id);
    params.set('semester', assessment.semester);
    params.set('student_ids', String(props.student.id));
    window.open(`${route(routeName)}?${params.toString()}`, '_blank');
};
</script>

<template>
    <Head :title="`Profil Siswa — ${student.full_name}`" />

    <AuthenticatedLayout>
        <div class="max-w-6xl mx-auto py-1 sm:py-2 pb-28 md:pb-8 space-y-4">
            <!-- Compact Top Breadcrumb Bar -->
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="hidden sm:flex items-center gap-1.5 text-xs text-slate-400">
                        <Link :href="route('yayasan.dashboard')" class="hover:text-[#00796B] transition-colors">Dashboard</Link>
                        <span>/</span>
                        <Link :href="route('yayasan.students.index')" class="hover:text-[#00796B] transition-colors">Data Siswa</Link>
                        <span>/</span>
                        <span class="text-slate-600 font-medium truncate">{{ student.full_name }}</span>
                    </div>
                    <h1 class="text-base sm:text-xl font-semibold tracking-tight text-slate-900 sm:mt-0.5 truncate">
                        Profil Peserta Didik
                    </h1>
                </div>

                <Link
                    :href="route('yayasan.students.index')"
                    class="h-9 sm:h-8 px-3 rounded-xl sm:rounded-lg bg-white border border-slate-200/90 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors inline-flex items-center gap-1.5 shrink-0"
                >
                    <ArrowLeftIcon class="w-3.5 h-3.5" />
                    <span>Kembali</span>
                </Link>
            </div>

            <!-- ==================== STUDENT 360° HEADER OVERVIEW CARD ==================== -->
            <div class="bg-white rounded-2xl sm:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] p-4 sm:p-5">
                <div class="flex items-start gap-3.5 sm:gap-5">
                    <!-- Avatar (Compact on mobile, clean on desktop) -->
                    <div class="relative shrink-0">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl border border-slate-200/80 overflow-hidden bg-slate-100 flex items-center justify-center">
                            <img v-if="student.photo" :src="`/storage/${student.photo}`" :alt="student.full_name" class="w-full h-full object-cover" />
                            <span v-else class="text-lg sm:text-2xl font-semibold text-slate-500">
                                {{ student.full_name?.substring(0, 2).toUpperCase() }}
                            </span>
                        </div>
                        <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full bg-emerald-500 ring-2 ring-white" title="Siswa Aktif" />
                    </div>

                    <!-- Identity + Quick Status Badges -->
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h2 class="text-base sm:text-lg font-semibold text-slate-900 tracking-tight leading-snug truncate">
                                    {{ student.full_name }}
                                </h2>
                                <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-slate-500">
                                    <span class="inline-flex items-center gap-1 truncate">
                                        <BuildingLibraryIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                        <span class="font-medium text-slate-700">{{ student.classroom?.name ? `Kelas ${student.classroom.name}` : 'Belum Ada Kelas' }}</span>
                                        <span v-if="student.unit?.name" class="text-slate-400">· {{ student.unit.name }}</span>
                                    </span>
                                    <span v-if="student.user?.email" class="hidden sm:inline-flex items-center gap-1 text-slate-400 truncate">
                                        <EnvelopeIcon class="w-3.5 h-3.5 shrink-0" />
                                        {{ student.user.email }}
                                    </span>
                                </div>
                            </div>

                            <!-- Quick Jump to Ummi if enabled -->
                            <button
                                v-if="quranEnabled"
                                type="button"
                                @click="activeTab = 'quran'"
                                class="hidden sm:inline-flex items-center gap-1.5 h-8 px-3 rounded-lg text-xs font-medium border transition-colors shrink-0"
                                :class="quranProfile?.current
                                    ? 'bg-teal-50/80 text-[#00796B] border-teal-200/80 hover:bg-teal-100/70'
                                    : 'bg-slate-50 text-slate-500 border-slate-200/80 hover:bg-slate-100'"
                            >
                                <BookOpenIcon class="w-3.5 h-3.5" />
                                <span>{{ quranProfile?.current ? `Ummi: ${quranProfile.current.level_name}` : 'Ummi: Belum Diplot' }}</span>
                            </button>
                        </div>

                        <!-- Quick Metadata Pills -->
                        <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium border"
                                :class="student.gender === 'L' ? 'bg-sky-50 text-sky-700 border-sky-200/70' : 'bg-pink-50 text-pink-700 border-pink-200/70'"
                            >
                                {{ student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}
                            </span>
                            <span v-if="student.nis" class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-50 text-slate-600 border border-slate-200/80 tabular-nums">
                                NIS {{ student.nis }}
                            </span>
                            <span v-if="student.nisn" class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-50 text-slate-600 border border-slate-200/80 tabular-nums">
                                NISN {{ student.nisn }}
                            </span>
                            <!-- Mobile Ummi Pill -->
                            <button
                                v-if="quranEnabled"
                                type="button"
                                @click="activeTab = 'quran'"
                                class="sm:hidden inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold border"
                                :class="quranProfile?.current ? 'bg-teal-50 text-[#00796B] border-teal-200/80' : 'bg-slate-50 text-slate-500 border-slate-200/80'"
                            >
                                <BookOpenIcon class="w-3 h-3" />
                                <span>{{ quranProfile?.current ? quranProfile.current.level_name : 'Belum Diplot' }}</span>
                            </button>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium border tabular-nums" :class="counselingBadgeClass">
                                <ShieldCheckIcon v-if="counselingSummary.status === 'AMAN'" class="w-3 h-3" />
                                <ShieldExclamationIcon v-else class="w-3 h-3" />
                                <span>BK: {{ counselingSummary.status }} ({{ counselingSummary.total_points }})</span>
                            </span>
                            <span
                                v-if="canViewFinance"
                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium border tabular-nums"
                                :class="finance.total_arrears > 0 ? 'bg-rose-50 text-rose-700 border-rose-200/80' : 'bg-emerald-50 text-emerald-700 border-emerald-200/80'"
                            >
                                {{ finance.total_arrears > 0 ? `Tunggakan Rp ${finance.total_arrears.toLocaleString('id-ID')}` : 'SPP Lunas' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== TAB NAVIGATION RAIL ==================== -->
            <nav
                aria-label="Tab Profil Siswa"
                class="flex items-center gap-1 overflow-x-auto scrollbar-none border-b border-slate-200/80 -mx-1 px-1"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    @click="activeTab = tab.id"
                    class="group relative inline-flex items-center gap-1.5 px-3 py-2.5 text-xs sm:text-[13px] font-medium whitespace-nowrap shrink-0 transition-colors"
                    :class="activeTab === tab.id ? 'text-[#00796B]' : 'text-slate-500 hover:text-slate-800'"
                >
                    <component
                        :is="tab.icon"
                        class="w-4 h-4 shrink-0"
                        :class="activeTab === tab.id ? 'text-[#00796B]' : 'text-slate-400 group-hover:text-slate-600'"
                    />
                    <span class="sm:hidden">{{ tab.mobileLabel }}</span>
                    <span class="hidden sm:inline">{{ tab.label }}</span>
                    <span
                        class="absolute inset-x-2 -bottom-px h-[2px] rounded-full transition-all duration-200"
                        :class="activeTab === tab.id ? 'bg-[#00796B] opacity-100 scale-x-100' : 'opacity-0 scale-x-50'"
                    />
                </button>
            </nav>

            <!-- ==================== TAB PANELS ==================== -->
            <Transition name="s-panel" mode="out-in">
                <!-- TAB 1: BIODATA & KELUARGA -->
                <div v-if="activeTab === 'bio'" key="bio" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <section class="bg-white rounded-2xl sm:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden">
                        <div class="px-4 sm:px-5 py-3 border-b border-slate-100 flex items-center gap-2">
                            <IdentificationIcon class="w-4 h-4 text-[#00796B]" />
                            <h3 class="text-sm font-semibold text-slate-900">Identitas Diri</h3>
                        </div>
                        <dl class="divide-y divide-slate-100 text-xs sm:text-[13px]">
                            <div class="px-4 sm:px-5 py-3 flex justify-between gap-4">
                                <dt class="text-slate-500 shrink-0">Nama Lengkap</dt>
                                <dd class="font-medium text-slate-900 text-right">{{ student.full_name }}</dd>
                            </div>
                            <div class="px-4 sm:px-5 py-3 flex justify-between gap-4">
                                <dt class="text-slate-500 shrink-0">Email Akun</dt>
                                <dd class="font-medium text-slate-700 text-right truncate">{{ student.user?.email || '-' }}</dd>
                            </div>
                            <div class="px-4 sm:px-5 py-3 flex justify-between gap-4">
                                <dt class="text-slate-500 shrink-0">Tempat, Tgl Lahir</dt>
                                <dd class="font-medium text-slate-700 text-right">{{ student.pob || '-' }}, {{ formatDate(student.dob) }}</dd>
                            </div>
                            <div class="px-4 sm:px-5 py-3 flex justify-between gap-4">
                                <dt class="text-slate-500 shrink-0">Jenis Kelamin</dt>
                                <dd class="font-medium text-slate-700 text-right">{{ student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd>
                            </div>
                            <div class="px-4 sm:px-5 py-3 space-y-1">
                                <dt class="text-slate-500">Alamat</dt>
                                <dd class="text-slate-700 leading-relaxed">{{ student.address || '-' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="bg-white rounded-2xl sm:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden">
                        <div class="px-4 sm:px-5 py-3 border-b border-slate-100 flex items-center gap-2">
                            <UserIcon class="w-4 h-4 text-[#00796B]" />
                            <h3 class="text-sm font-semibold text-slate-900">Orang Tua &amp; Wali</h3>
                        </div>
                        <div class="p-4 sm:p-5 space-y-4">
                            <div class="rounded-xl bg-slate-50/80 border border-slate-200/70 p-3.5 space-y-2.5">
                                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Orang Tua</div>
                                <div class="text-sm font-semibold text-slate-900">{{ student.parent_name || 'Belum diisi' }}</div>
                                <a
                                    v-if="student.parent_phone"
                                    :href="formatPhoneWa(student.parent_phone)"
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg bg-white border border-teal-200/80 text-xs font-medium text-[#00796B] hover:bg-teal-50 transition-colors tabular-nums"
                                >
                                    <PhoneIcon class="w-3.5 h-3.5" />
                                    <span>{{ student.parent_phone }} (WhatsApp)</span>
                                </a>
                                <div v-else class="text-xs text-slate-400">Nomor telepon belum terdaftar</div>
                            </div>

                            <div v-if="student.guardian_name || student.guardian_phone" class="rounded-xl bg-slate-50/80 border border-slate-200/70 p-3.5 space-y-2.5">
                                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Wali</div>
                                <div class="text-sm font-semibold text-slate-900">{{ student.guardian_name || '-' }}</div>
                                <a
                                    v-if="student.guardian_phone"
                                    :href="formatPhoneWa(student.guardian_phone)"
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg bg-white border border-teal-200/80 text-xs font-medium text-[#00796B] hover:bg-teal-50 transition-colors tabular-nums"
                                >
                                    <PhoneIcon class="w-3.5 h-3.5" />
                                    <span>{{ student.guardian_phone }} (WhatsApp)</span>
                                </a>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- TAB 2: AKADEMIK & KEHADIRAN -->
                <div v-else-if="activeTab === 'academic'" key="academic" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Academic Identity -->
                        <section class="bg-white rounded-2xl sm:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden">
                            <div class="px-4 sm:px-5 py-3 border-b border-slate-100 flex items-center gap-2">
                                <AcademicCapIcon class="w-4 h-4 text-[#00796B]" />
                                <h3 class="text-sm font-semibold text-slate-900">Informasi Akademik</h3>
                            </div>
                            <dl class="divide-y divide-slate-100 text-xs sm:text-[13px]">
                                <div class="px-4 sm:px-5 py-3 flex justify-between gap-4">
                                    <dt class="text-slate-500">NIS / NISN</dt>
                                    <dd class="font-medium text-slate-900 tabular-nums">{{ student.nis || '-' }} / {{ student.nisn || '-' }}</dd>
                                </div>
                                <div class="px-4 sm:px-5 py-3 flex justify-between gap-4">
                                    <dt class="text-slate-500">Kelas Saat Ini</dt>
                                    <dd class="font-semibold text-[#00796B]">{{ student.classroom?.name ? `Kelas ${student.classroom.name}` : 'Belum Masuk Kelas' }}</dd>
                                </div>
                                <div class="px-4 sm:px-5 py-3 flex justify-between gap-4">
                                    <dt class="text-slate-500">Wali Kelas</dt>
                                    <dd class="font-medium text-slate-800">{{ homeroomName || '-' }}</dd>
                                </div>
                                <div class="px-4 sm:px-5 py-3 flex justify-between gap-4">
                                    <dt class="text-slate-500">Tahun Ajaran</dt>
                                    <dd class="font-medium text-slate-800">{{ student.academic_year?.name || student.academicYear?.name || '-' }}</dd>
                                </div>
                                <div v-if="canViewFinance" class="px-4 sm:px-5 py-3 flex justify-between gap-4">
                                    <dt class="text-slate-500">Virtual Account (VA)</dt>
                                    <dd class="font-mono text-xs font-semibold text-[#00796B]">{{ finance?.va_number || 'Belum Digenerate' }}</dd>
                                </div>
                            </dl>
                        </section>

                        <!-- Attendance & Gate Check-in -->
                        <section class="bg-white rounded-2xl sm:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden">
                            <div class="px-4 sm:px-5 py-3 border-b border-slate-100 flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <CalendarDaysIcon class="w-4 h-4 text-[#00796B]" />
                                    <h3 class="text-sm font-semibold text-slate-900">Rekap Kehadiran</h3>
                                </div>
                                <span v-if="totalAttendanceDays > 0" class="text-xs font-semibold text-[#00796B] tabular-nums">{{ attendanceRate }}% hadir</span>
                            </div>

                            <div class="p-4 sm:p-5 space-y-4">
                                <div class="grid grid-cols-4 gap-2 text-center">
                                    <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200/60">
                                        <div class="text-base font-semibold text-emerald-700 tabular-nums">{{ attendanceSummary.present }}</div>
                                        <div class="text-[10px] font-medium text-emerald-700/80 mt-0.5">Hadir</div>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-amber-50/70 border border-amber-200/60">
                                        <div class="text-base font-semibold text-amber-700 tabular-nums">{{ attendanceSummary.sick }}</div>
                                        <div class="text-[10px] font-medium text-amber-700/80 mt-0.5">Sakit</div>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-sky-50/70 border border-sky-200/60">
                                        <div class="text-base font-semibold text-sky-700 tabular-nums">{{ attendanceSummary.permission }}</div>
                                        <div class="text-[10px] font-medium text-sky-700/80 mt-0.5">Izin</div>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-rose-50/70 border border-rose-200/60">
                                        <div class="text-base font-semibold text-rose-700 tabular-nums">{{ attendanceSummary.alpha }}</div>
                                        <div class="text-[10px] font-medium text-rose-700/80 mt-0.5">Alpha</div>
                                    </div>
                                </div>

                                <div v-if="recentCheckins.length > 0" class="space-y-1.5">
                                    <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Check-in Gerbang Terakhir</div>
                                    <div class="divide-y divide-slate-100 rounded-xl border border-slate-100 overflow-hidden">
                                        <div v-for="c in recentCheckins" :key="c.id" class="px-3 py-2 flex items-center justify-between text-xs bg-slate-50/40">
                                            <span class="text-slate-600 tabular-nums">{{ c.date }}</span>
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium text-slate-800 tabular-nums">{{ c.time }} WIB</span>
                                                <span
                                                    class="px-1.5 py-0.5 rounded text-[10px] font-medium capitalize"
                                                    :class="c.status === 'hadir' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                                                >
                                                    {{ c.status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- Class Promotion History -->
                    <section class="bg-white rounded-2xl sm:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden">
                        <div class="px-4 sm:px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-900">Riwayat Kenaikan Kelas</h3>
                            <span class="text-xs text-slate-400 tabular-nums">{{ classPromotions.length }} catatan</span>
                        </div>
                        <div v-if="classPromotions.length === 0" class="p-8 text-center text-xs text-slate-400">
                            Belum ada riwayat kenaikan kelas yang tercatat.
                        </div>
                        <div v-else class="divide-y divide-slate-100">
                            <div v-for="p in classPromotions" :key="p.id" class="px-4 sm:px-5 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-medium bg-teal-50 text-[#00796B] border border-teal-200/70">
                                        {{ p.status_label }}
                                    </span>
                                    <span class="font-semibold text-slate-800">
                                        Kelas {{ p.from_classroom }}
                                        <template v-if="p.to_classroom"> → Kelas {{ p.to_classroom }}</template>
                                    </span>
                                    <span class="text-slate-400">({{ p.from_year }} → {{ p.to_year }})</span>
                                </div>
                                <span class="text-[11px] text-slate-400 tabular-nums shrink-0">{{ p.promoted_at }}</span>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- ==================== TAB 3: PROGRAM AL-QUR'AN (UMMI) ==================== -->
                <div v-else-if="activeTab === 'quran' && quranEnabled" key="quran" class="space-y-4">
                    <!-- Current Ummi Status Card -->
                    <section class="bg-white rounded-2xl sm:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] p-4 sm:p-5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-teal-50 border border-teal-200/70 text-[#00796B] flex items-center justify-center shrink-0">
                                    <BookOpenIcon class="w-5 h-5" />
                                </div>
                                <div>
                                    <div class="text-xs text-slate-500">Status Program Al-Qur'an (Metode Ummi)</div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-base font-semibold text-slate-900">
                                            {{ quranProfile?.current ? quranProfile.current.level_name : 'Belum Diplot ke Kelompok' }}
                                        </span>
                                        <span
                                            v-if="quranProfile?.stats?.promotions_total > 0"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/70 tabular-nums"
                                        >
                                            <CheckBadgeIcon class="w-3.5 h-3.5" />
                                            Naik {{ quranProfile.stats.promotions_total }}×
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <Link
                                v-if="permissions.can_print_quran_report"
                                :href="route('quran.students.index', { search: student.full_name })"
                                class="h-9 sm:h-8 px-3 rounded-xl sm:rounded-lg bg-white border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors inline-flex items-center justify-center gap-1.5 shrink-0"
                            >
                                <span>Kelola di Modul Ummi</span>
                                <ArrowRightIcon class="w-3.5 h-3.5" />
                            </Link>
                        </div>

                        <div v-if="quranProfile?.current" class="grid grid-cols-2 lg:grid-cols-4 gap-3 pt-4 text-xs">
                            <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100">
                                <div class="text-[11px] text-slate-400">Kelompok / Halaqah</div>
                                <div class="font-semibold text-slate-900 mt-0.5">{{ quranProfile.current.group_name }}</div>
                                <div v-if="quranProfile.current.room_name" class="text-[11px] text-slate-500 mt-0.5 inline-flex items-center gap-1">
                                    <MapPinIcon class="w-3 h-3" />{{ quranProfile.current.room_name }}
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100">
                                <div class="text-[11px] text-slate-400">Guru Pengampu</div>
                                <div class="font-semibold text-slate-900 mt-0.5">{{ quranProfile.current.teacher_name }}</div>
                                <div v-if="quranProfile.current.assistant_name" class="text-[11px] text-slate-500 mt-0.5">Asisten: {{ quranProfile.current.assistant_name }}</div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100">
                                <div class="text-[11px] text-slate-400">Sesi &amp; Jadwal</div>
                                <div class="font-semibold text-slate-900 mt-0.5">{{ quranProfile.current.session_name || 'Reguler' }}</div>
                                <div v-if="quranProfile.current.session_time" class="text-[11px] text-[#00796B] font-medium tabular-nums mt-0.5">
                                    {{ quranProfile.current.session_time }} WIB
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100">
                                <div class="text-[11px] text-slate-400">Mulai di Jilid Ini</div>
                                <div class="font-semibold text-slate-900 mt-0.5 tabular-nums">{{ quranProfile.current.started_at || '-' }}</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">TA {{ quranProfile.current.academic_year }}</div>
                            </div>
                        </div>
                        <div v-else class="pt-4 text-xs text-slate-500">
                            Siswa ini belum dimasukkan ke kelompok halaqah Al-Qur'an pada tahun ajaran aktif.
                        </div>
                    </section>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                        <!-- Timeline Riwayat Naik Jilid -->
                        <section class="lg:col-span-5 bg-white rounded-2xl sm:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden">
                            <div class="px-4 sm:px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <ClockIcon class="w-4 h-4 text-[#00796B]" />
                                    <h3 class="text-sm font-semibold text-slate-900">Timeline Kenaikan Jilid</h3>
                                </div>
                                <span class="text-xs text-slate-400 tabular-nums">{{ quranProfile?.histories?.length || 0 }} log</span>
                            </div>

                            <div class="p-4 sm:p-5">
                                <div v-if="!quranProfile?.histories?.length" class="py-8 text-center text-xs text-slate-400">
                                    Belum ada riwayat penempatan atau kenaikan jilid.
                                </div>
                                <ol v-else class="relative border-l border-slate-200 ml-2 space-y-4">
                                    <li v-for="h in quranProfile.histories" :key="h.id" class="ml-5 relative">
                                        <span
                                            class="absolute -left-[25px] top-1.5 w-2.5 h-2.5 rounded-full ring-4 ring-white"
                                            :class="changeTypeMeta(h.change_type).dot"
                                        />
                                        <div class="rounded-xl border border-slate-200/70 bg-slate-50/40 p-3 space-y-1">
                                            <div class="flex flex-wrap items-center justify-between gap-1.5">
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-medium border" :class="changeTypeMeta(h.change_type).badge">
                                                    {{ changeTypeMeta(h.change_type).text }}
                                                </span>
                                                <span class="text-[11px] text-slate-400 tabular-nums">{{ h.changed_at }}</span>
                                            </div>
                                            <div class="text-xs sm:text-[13px] font-semibold text-slate-900 flex items-center gap-1.5">
                                                <template v-if="h.from_level">
                                                    <span class="text-slate-400 font-normal">{{ h.from_level }}</span>
                                                    <ArrowRightIcon class="w-3 h-3 text-[#00796B]" />
                                                    <span>{{ h.to_level }}</span>
                                                </template>
                                                <span v-else>{{ h.to_level }}</span>
                                            </div>
                                            <div class="text-[11px] text-slate-600 space-y-0.5">
                                                <div v-if="h.to_group">{{ h.to_group }}<span v-if="h.to_teacher" class="text-slate-400"> · {{ h.to_teacher }}</span></div>
                                                <div v-if="h.exam_score" class="text-[#00796B] font-medium">Nilai Tes: {{ h.exam_score }}</div>
                                                <div v-if="h.notes" class="text-slate-500 italic">"{{ h.notes }}"</div>
                                            </div>
                                        </div>
                                    </li>
                                </ol>
                            </div>
                        </section>

                        <!-- Riwayat Nilai & Rapor Ummi -->
                        <section class="lg:col-span-7 bg-white rounded-2xl sm:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden">
                            <div class="px-4 sm:px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <BookOpenIcon class="w-4 h-4 text-[#00796B]" />
                                    <h3 class="text-sm font-semibold text-slate-900">Riwayat Penilaian &amp; Rapor Ummi</h3>
                                </div>
                                <span class="text-xs text-slate-400 tabular-nums">{{ quranProfile?.assessments?.length || 0 }} semester</span>
                            </div>

                            <div v-if="!quranProfile?.assessments?.length" class="p-8 text-center text-xs text-slate-400">
                                Belum ada penilaian rapor Al-Qur'an yang tersimpan untuk siswa ini.
                            </div>

                            <div v-else class="divide-y divide-slate-100">
                                <div v-for="a in quranProfile.assessments" :key="a.id" class="p-4">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                                        <button
                                            type="button"
                                            @click="toggleAssessment(a.id)"
                                            class="flex items-center gap-2.5 text-left min-w-0 flex-1"
                                        >
                                            <ChevronDownIcon
                                                class="w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0"
                                                :class="expandedAssessmentIds.has(a.id) ? 'rotate-180 text-[#00796B]' : ''"
                                            />
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-1.5">
                                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-teal-50 text-[#00796B] border border-teal-200/70">
                                                        {{ a.level_name }}
                                                    </span>
                                                    <span class="text-xs sm:text-[13px] font-semibold text-slate-900">
                                                        {{ a.academic_year }} · {{ a.semester_label }}
                                                    </span>
                                                </div>
                                                <div class="text-[11px] text-slate-400 mt-0.5">
                                                    {{ a.group_name }} · {{ a.teacher_name }} · {{ a.assessed_count }} indikator
                                                </div>
                                            </div>
                                        </button>

                                        <div v-if="permissions.can_print_quran_report" class="flex items-center gap-1.5 shrink-0 pl-6 sm:pl-0">
                                            <button
                                                type="button"
                                                @click="openQuranReport('quran.reports.print', a)"
                                                class="h-8 sm:h-7 px-2.5 rounded-lg text-xs font-medium text-[#00796B] bg-teal-50/80 border border-teal-200/70 hover:bg-teal-100/70 inline-flex items-center gap-1"
                                            >
                                                <PrinterIcon class="w-3.5 h-3.5" />
                                                <span>Print F4</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="openQuranReport('quran.reports.pdf', a)"
                                                class="h-8 sm:h-7 px-2.5 rounded-lg text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 inline-flex items-center gap-1"
                                            >
                                                <DocumentArrowDownIcon class="w-3.5 h-3.5" />
                                                <span>PDF</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Grade Distribution Summary Badges -->
                                    <div v-if="a.grade_counts" class="flex flex-wrap items-center gap-1.5 mt-2.5 pl-6">
                                        <span v-for="g in ['A', 'B', 'C', 'D']" :key="g" v-show="a.grade_counts[g] > 0" class="px-2 py-0.5 rounded-md text-[11px] font-semibold border tabular-nums" :class="gradePillClass(g)">
                                            {{ g }}: {{ a.grade_counts[g] }}
                                        </span>
                                    </div>

                                    <!-- Expandable Itemized Scores -->
                                    <QuranCollapse :open="expandedAssessmentIds.has(a.id)">
                                        <div class="mt-3 pt-3 border-t border-slate-100 space-y-3 pl-0 sm:pl-6">
                                            <div v-for="cat in a.categories" :key="cat.key" class="space-y-1.5">
                                                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ cat.label }}</div>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                                    <div
                                                        v-for="(item, idx) in cat.items"
                                                        :key="idx"
                                                        class="flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-lg bg-slate-50/80 border border-slate-100 text-xs"
                                                    >
                                                        <span class="text-slate-700 truncate">{{ item.title }}</span>
                                                        <span class="px-1.5 py-0.5 rounded text-[11px] font-semibold border shrink-0" :class="gradePillClass(item.grade)">
                                                            {{ item.grade }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="a.teacher_note" class="rounded-lg bg-teal-50/50 border border-teal-200/60 px-3 py-2 text-xs text-slate-700">
                                                <span class="font-semibold text-[#00796B]">Catatan Guru:</span> {{ a.teacher_note }}
                                            </div>
                                        </div>
                                    </QuranCollapse>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <!-- ==================== TAB 4: EKSTRAKURIKULER & BK ==================== -->
                <div v-else-if="activeTab === 'activities'" key="activities" class="space-y-4">
                    <!-- Extracurriculars -->
                    <section class="bg-white rounded-2xl sm:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden">
                        <div class="px-4 sm:px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <UserGroupIcon class="w-4 h-4 text-[#00796B]" />
                                <h3 class="text-sm font-semibold text-slate-900">Ekstrakurikuler Aktif</h3>
                            </div>
                            <span class="text-xs text-slate-400 tabular-nums">{{ extracurriculars.length }} ekskul</span>
                        </div>
                        <div v-if="extracurriculars.length === 0" class="p-8 text-center text-xs text-slate-400">
                            Siswa belum terdaftar pada kegiatan ekstrakurikuler aktif.
                        </div>
                        <div v-else class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div v-for="ex in extracurriculars" :key="ex.id" class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/40 space-y-1.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <span class="text-[10px] font-semibold text-[#00796B] uppercase tracking-wider">{{ ex.category }}</span>
                                        <div class="text-sm font-semibold text-slate-900">{{ ex.name }}</div>
                                    </div>
                                    <span v-if="ex.grade" class="px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                        Nilai {{ ex.grade }}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-500">{{ ex.schedule }} · {{ ex.location }}</div>
                                <div class="text-xs text-slate-500">Pelatih: <span class="font-medium text-slate-700">{{ ex.coach }}</span></div>
                                <p v-if="ex.grade_description" class="text-[11px] text-slate-500 italic pt-1 border-t border-slate-200/60">{{ ex.grade_description }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Achievements & Violations -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <section class="bg-white rounded-2xl sm:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden">
                            <div class="px-4 sm:px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <TrophyIcon class="w-4 h-4 text-amber-500" />
                                    <h3 class="text-sm font-semibold text-slate-900">Prestasi Siswa</h3>
                                </div>
                                <span class="text-xs text-slate-400 tabular-nums">{{ counselingSummary.achievements.length }}</span>
                            </div>
                            <div v-if="!counselingSummary.achievements.length" class="p-8 text-center text-xs text-slate-400">
                                Belum ada data prestasi yang tercatat.
                            </div>
                            <div v-else class="divide-y divide-slate-100">
                                <div v-for="a in counselingSummary.achievements" :key="a.id" class="px-4 py-3 space-y-0.5">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs sm:text-[13px] font-semibold text-slate-900">{{ a.title }}</span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200/70 shrink-0">{{ a.level }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 tabular-nums">{{ a.date }}</div>
                                    <p v-if="a.description" class="text-xs text-slate-600">{{ a.description }}</p>
                                </div>
                            </div>
                        </section>

                        <section class="bg-white rounded-2xl sm:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden">
                            <div class="px-4 sm:px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <ShieldCheckIcon class="w-4 h-4 text-[#00796B]" />
                                    <h3 class="text-sm font-semibold text-slate-900">Catatan Tata Tertib</h3>
                                </div>
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold border tabular-nums" :class="counselingBadgeClass">
                                    {{ counselingSummary.total_points }} poin
                                </span>
                            </div>
                            <div v-if="!counselingSummary.violations.length" class="p-8 text-center text-xs text-slate-400">
                                Tidak ada catatan pelanggaran. Alhamdulillah!
                            </div>
                            <div v-else class="divide-y divide-slate-100">
                                <div v-for="v in counselingSummary.violations" :key="v.id" class="px-4 py-3 space-y-0.5">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs sm:text-[13px] font-semibold text-slate-900">{{ v.category }}</span>
                                        <span class="text-xs font-semibold text-rose-600 tabular-nums">+{{ v.points }} poin</span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 tabular-nums">{{ v.date }}</div>
                                    <p v-if="v.description" class="text-xs text-slate-600">{{ v.description }}</p>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- Private Counseling Sessions (STRICTLY ROLE-GATED) -->
                    <section v-if="canViewCounselingNotes" class="bg-white rounded-2xl sm:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden">
                        <div class="px-4 sm:px-5 py-3 border-b border-slate-100 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <ChatBubbleLeftRightIcon class="w-4 h-4 text-indigo-600" />
                                <h3 class="text-sm font-semibold text-slate-900">Riwayat Sesi Konseling BK</h3>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-600">
                                <LockClosedIcon class="w-3 h-3" />
                                Terbatas (BK / Wali Kelas / Pimpinan)
                            </span>
                        </div>
                        <div v-if="!counselingSummary.sessions.length" class="p-8 text-center text-xs text-slate-400">
                            Belum ada catatan sesi konseling BK.
                        </div>
                        <div v-else class="divide-y divide-slate-100">
                            <div v-for="cs in counselingSummary.sessions" :key="cs.id" class="px-4 sm:px-5 py-3.5 space-y-1.5 text-xs">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-slate-900">{{ cs.counselor }}</span>
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-medium">{{ cs.method }}</span>
                                        <span class="px-1.5 py-0.5 rounded bg-teal-50 text-[#00796B] text-[10px] font-medium">{{ cs.status }}</span>
                                    </div>
                                    <span class="text-[11px] text-slate-400 tabular-nums">{{ cs.date }}</span>
                                </div>
                                <p v-if="cs.notes" class="text-slate-700 leading-relaxed">{{ cs.notes }}</p>
                                <p v-if="cs.follow_up_action" class="text-[11px] text-slate-500">
                                    <span class="font-semibold text-slate-700">Tindak lanjut:</span> {{ cs.follow_up_action }}
                                </p>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- ==================== TAB 5: KEUANGAN (STRICTLY ROLE-GATED) ==================== -->
                <div v-else-if="activeTab === 'finance' && canViewFinance" key="finance" class="space-y-4">
                    <div class="bg-white rounded-2xl sm:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-slate-200/70">
                        <div class="p-4">
                            <div class="text-xs text-slate-500 font-medium">Total Tunggakan</div>
                            <div class="text-lg font-semibold mt-1 tabular-nums" :class="finance.total_arrears > 0 ? 'text-rose-600' : 'text-emerald-600'">
                                Rp {{ (finance.total_arrears || 0).toLocaleString('id-ID') }}
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="text-xs text-slate-500 font-medium">Total Terbayar</div>
                            <div class="text-lg font-semibold text-[#00796B] mt-1 tabular-nums">
                                Rp {{ (finance.total_paid || 0).toLocaleString('id-ID') }}
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="text-xs text-slate-500 font-medium">Virtual Account</div>
                            <div class="text-sm font-mono font-semibold text-slate-900 mt-1.5 flex items-center gap-1.5">
                                <CreditCardIcon class="w-4 h-4 text-[#00796B]" />
                                <span>{{ finance.va_number || 'Belum Digenerate' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <section class="bg-white rounded-2xl sm:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden">
                            <div class="px-4 sm:px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-slate-900">Tagihan Belum Lunas</h3>
                                <span class="text-xs font-medium text-rose-600 tabular-nums">{{ finance.bills?.length || 0 }} item</span>
                            </div>
                            <div class="divide-y divide-slate-100">
                                <div v-if="!finance.bills?.length" class="p-8 text-center text-xs text-slate-400">
                                    Tidak ada tagihan tertunggak. Alhamdulillah!
                                </div>
                                <div v-for="bill in finance.bills" :key="bill.id" class="px-4 py-3 flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-xs sm:text-[13px] font-medium text-slate-900 truncate">{{ bill.finance_type?.name || bill.description }}</div>
                                        <div class="text-[11px] text-slate-400">Jatuh tempo: {{ formatDate(bill.due_date) }}</div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-xs sm:text-[13px] font-semibold text-rose-600 tabular-nums">Rp {{ (bill.final_amount - bill.paid_amount).toLocaleString('id-ID') }}</div>
                                        <span class="text-[10px] font-medium text-slate-500">{{ bill.paid_amount > 0 ? 'Sebagian' : 'Belum Bayar' }}</span>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="bg-white rounded-2xl sm:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden">
                            <div class="px-4 sm:px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-slate-900">Riwayat Pembayaran</h3>
                                <span class="text-xs text-slate-400">10 terakhir</span>
                            </div>
                            <div class="divide-y divide-slate-100">
                                <div v-if="!finance.transactions?.length" class="p-8 text-center text-xs text-slate-400">
                                    Belum ada riwayat transaksi.
                                </div>
                                <div v-for="trx in finance.transactions" :key="trx.id" class="px-4 py-3 flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-xs sm:text-[13px] font-medium text-slate-900 truncate">{{ trx.transaction_code }}</div>
                                        <div class="text-[11px] text-slate-400">{{ formatDate(trx.transaction_date) }} · {{ trx.payment_method }}</div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-xs sm:text-[13px] font-semibold text-[#00796B] tabular-nums">+ Rp {{ Number(trx.amount).toLocaleString('id-ID') }}</div>
                                        <span class="text-[10px] text-slate-400">Sukses</span>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </Transition>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.s-panel-enter-active {
    transition: opacity 180ms cubic-bezier(0.16, 1, 0.3, 1), transform 200ms cubic-bezier(0.16, 1, 0.3, 1);
}
.s-panel-leave-active {
    transition: opacity 110ms ease-in;
}
.s-panel-enter-from {
    opacity: 0;
    transform: translateY(4px);
}
.s-panel-leave-to {
    opacity: 0;
}
</style>
