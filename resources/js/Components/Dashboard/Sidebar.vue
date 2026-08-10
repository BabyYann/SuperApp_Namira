<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { ref, computed } from 'vue';
import { 
    ComputerDesktopIcon, UserGroupIcon, BuildingOffice2Icon, CalendarDaysIcon, 
    MapPinIcon, SunIcon, ChartBarSquareIcon, ChartBarIcon, BanknotesIcon, CreditCardIcon, 
    DocumentTextIcon, ArrowsRightLeftIcon, ExclamationTriangleIcon, IdentificationIcon, 
    FingerPrintIcon, CheckBadgeIcon, TableCellsIcon, UserIcon, HomeModernIcon, 
    BookOpenIcon, TrophyIcon, ClockIcon, PencilSquareIcon, AcademicCapIcon, 
    ClipboardDocumentCheckIcon, ChevronDownIcon, ChatBubbleLeftRightIcon,
    CubeIcon, WrenchScrewdriverIcon, TagIcon, ArrowPathIcon, ArrowUpCircleIcon,
    Cog6ToothIcon, ChartPieIcon, CameraIcon, GlobeAltIcon, SparklesIcon, BriefcaseIcon
} from '@heroicons/vue/24/outline';

defineProps({
    isSidebarOpen: Boolean,
});

const showUnitMenu = ref(false);
const expandedGroups = ref({
    'master': true,
    'academic': true,
    'finance': true,
    'employee': true,
    'counseling': true,
    'sarpar': true,
});

// Menu Structure
const menuGroups = [
    {
        title: 'Master Data',
        key: 'master',
        items: [
            { 
                label: 'Monitoring Center', 
                route: 'yayasan.monitoring.index', 
                active: 'yayasan.monitoring.*',
                icon: ComputerDesktopIcon 
            },
            { 
                label: 'System Monitoring (Pulse)', 
                route: '/pulse', 
                active: 'pulse',
                icon: ChartPieIcon 
            },
            { 
                label: 'Manajemen Pengguna', 
                route: 'yayasan.users.index', 
                active: 'yayasan.users.*',
                icon: UserGroupIcon 
            },
            { 
                label: 'Satuan Pendidikan', 
                route: 'yayasan.units.index', 
                active: 'yayasan.units.*',
                icon: BuildingOffice2Icon 
            },
            { 
                label: 'Tahun Akademik', 
                route: 'yayasan.academic-years.index', 
                active: 'yayasan.academic-years.*',
                icon: CalendarDaysIcon 
            },
            { 
                label: 'Lokasi Absensi', 
                route: 'yayasan.attendance-locations.index', 
                active: 'yayasan.attendance-locations.*',
                icon: MapPinIcon 
            },
            { 
                label: 'Kalender Akademik', 
                route: 'yayasan.holidays.index', 
                active: 'yayasan.holidays.*',
                icon: CalendarDaysIcon 
            },
        ]
    },
    {
        title: 'Keuangan',
        key: 'finance',
        items: [
            { 
                label: 'Dashboard Keuangan', 
                route: 'yayasan.finance.dashboard', 
                active: 'yayasan.finance.dashboard',
                icon: ChartBarSquareIcon 
            },
            { 
                label: 'Pos Bayar', 
                route: 'yayasan.finance.types.index', 
                active: 'yayasan.finance.types.*',
                icon: BanknotesIcon 
            },
            { 
                label: 'Rekening Bank', 
                route: 'yayasan.finance.accounts.index', 
                active: 'yayasan.finance.accounts.*',
                icon: CreditCardIcon 
            },
            { 
                label: 'Tagihan Siswa', 
                route: 'yayasan.finance.bills.index', 
                active: 'yayasan.finance.bills.*',
                icon: DocumentTextIcon 
            },
            { 
                label: 'Mutasi & Pembayaran', 
                route: 'yayasan.finance.transactions.index', 
                active: 'yayasan.finance.transactions.*',
                icon: ArrowsRightLeftIcon 
            },
            { 
                label: 'Laporan Tunggakan', 
                route: 'yayasan.finance.reports.arrears', 
                active: 'yayasan.finance.reports.arrears',
                icon: ExclamationTriangleIcon 
            },
        ]
    },
    {
        title: 'Kepegawaian',
        key: 'employee',
        items: [
            { 
                label: 'Data Staf (Tendik)', 
                route: 'yayasan.staff.index', 
                active: 'yayasan.staff.*',
                icon: IdentificationIcon 
            },
            { 
                label: 'Presensi', 
                route: 'employee.attendance.index', 
                active: 'employee.attendance.*',
                icon: FingerPrintIcon 
            },
            { 
                label: 'Giat Tugas Saya', 
                route: 'employee.activity-logs.index', 
                active: 'employee.activity-logs.index',
                icon: ClipboardDocumentCheckIcon 
            },
            { 
                label: 'Linimasa Giat SDM', 
                route: 'yayasan.activity-logs.feed', 
                active: 'yayasan.activity-logs.feed',
                icon: SparklesIcon 
            },
            { 
                label: 'Penyetujuan Absensi', 
                route: 'yayasan.attendance-approvals.index', 
                active: 'yayasan.attendance-approvals.*',
                icon: CheckBadgeIcon 
            },
            { 
                label: 'Data Absensi', 
                route: 'yayasan.attendance-data.index', 
                active: 'yayasan.attendance-data.*',
                icon: TableCellsIcon 
            },
            { 
                label: 'Kelola Lowongan Karir', 
                route: 'yayasan.job-vacancies.index', 
                active: 'yayasan.job-vacancies.*',
                icon: BriefcaseIcon 
            },
            { 
                label: 'Daftar Pelamar Kerja', 
                route: 'yayasan.applicants.index', 
                active: 'yayasan.applicants.*',
                icon: UserGroupIcon 
            },
        ]
    },


    {
        title: 'Academic',
        key: 'academic',
        items: [
            { 
                label: 'Data Guru', 
                route: 'yayasan.teachers.index', 
                active: 'yayasan.teachers.*',
                icon: UserIcon 
            },
            { 
                label: 'Kelas', 
                route: 'yayasan.classrooms.index', 
                active: 'yayasan.classrooms.*',
                icon: HomeModernIcon 
            },
            { 
                label: 'Mata Pelajaran', 
                route: 'yayasan.subjects.index', 
                active: 'yayasan.subjects.*',
                icon: BookOpenIcon 
            },
            { 
                label: 'Tujuan Pembelajaran', 
                route: 'yayasan.learning-objectives.index', 
                active: 'yayasan.learning-objectives.*',
                icon: TrophyIcon 
            },
            { 
                label: 'Jadwal Pelajaran', 
                route: 'yayasan.schedules.index', 
                active: 'yayasan.schedules.*',
                icon: ClockIcon 
            },
            { 
                label: 'Jurnal Mengajar', 
                route: 'yayasan.teaching-journal.index', 
                active: 'yayasan.teaching-journal.*',
                icon: PencilSquareIcon 
            },
            { 
                label: 'Siswa', 
                route: 'yayasan.students.index', 
                active: 'yayasan.students.*',
                icon: AcademicCapIcon 
            },
            { 
                label: 'Presensi Siswa', 
                route: 'yayasan.student-attendance.index', 
                active: 'yayasan.student-attendance.index',
                icon: ClipboardDocumentCheckIcon 
            },
            { 
                label: 'Scanner Absensi Gerbang', 
                route: 'yayasan.student-checkin.index', 
                active: 'yayasan.student-checkin.*',
                icon: CameraIcon 
            },
            { 
                label: 'Rekap Kehadiran', 
                route: 'yayasan.student-attendance.recap', 
                active: 'yayasan.student-attendance.recap',
                icon: ChartBarIcon 
            },
            { 
                label: 'Kenaikan Kelas', 
                route: 'yayasan.promotion.index', 
                active: 'yayasan.promotion.*',
                icon: ArrowUpCircleIcon 
            },
        ]
    },
];

const switchUnit = (unitId) => {
    router.post(route('yayasan.switch-unit'), {
        unit_id: unitId
    }, {
        onSuccess: () => showUnitMenu.value = false,
        preserveScroll: true,
    });
};

const toggleGroup = (key) => {
    expandedGroups.value[key] = !expandedGroups.value[key];
};

const isActive = (routeName) => {
    if (!routeName) return false;
    if (routeName === 'pulse') {
        return window.location.pathname.startsWith('/pulse');
    }
    try {
        return typeof route === 'function' ? route().current(routeName) : false;
    } catch (e) {
        return false;
    }
};

const resolveRoute = (routeName) => {
    if (!routeName) return '#';
    if (routeName.startsWith('/')) return routeName;
    try {
        if (typeof route === 'function' && route().has(routeName)) {
            return route(routeName);
        }
    } catch (e) {
        console.warn(`[Sidebar] Route "${routeName}" unavailable:`, e);
    }
    return '#';
};

// Role Check
// Role Check Helper (Available in Template)
const page = usePage();
// Helper to check role
// Use a computed getter or simple execution, but for template it needs to be reactive
const userRoles = computed(() => page.props.auth.user.roles || []);
const primaryRole = computed(() => page.props.auth.user.role);

const hasRole = (role) => userRoles.value.includes(role) || primaryRole.value === role;
const hasAnyRole = (roles) => roles.some(r => userRoles.value.includes(r)) || roles.includes(primaryRole.value);

// Filtered Menu
const filteredMenuGroups = computed(() => {
    
    const isTeacher = page.props.session?.is_teacher || hasRole('guru') || hasRole('teacher');
    const isSuperAdmin = hasRole('super_admin_yayasan');
    const isPembina = hasRole('pembina_yayasan');

    if (isPembina && !isSuperAdmin) {
        return [
            {
                title: 'Executive Portal',
                key: 'pembina_portal',
                items: [
                    { 
                        label: 'Executive Dashboard', 
                        route: 'yayasan.pembina.dashboard', 
                        active: 'yayasan.pembina.dashboard',
                        icon: ComputerDesktopIcon 
                    },
                    { 
                        label: 'Monitoring Center', 
                        route: 'yayasan.monitoring.index', 
                        active: 'yayasan.monitoring.*',
                        icon: ChartPieIcon 
                    },
                    { 
                        label: 'Destinasi Alumni & Kunjungan', 
                        route: 'public-relations.university-destinations.index', 
                        active: 'public-relations.university-destinations.*',
                        icon: GlobeAltIcon 
                    },
                    { 
                        label: 'Laporan Keuangan', 
                        route: 'yayasan.finance.reports.arrears', 
                        active: 'yayasan.finance.reports.*',
                        icon: BanknotesIcon 
                    },
                    { 
                        label: 'Rekap Presensi SDM', 
                        route: 'yayasan.attendance-data.index', 
                        active: 'yayasan.attendance-data.*',
                        icon: FingerPrintIcon 
                    },
                ]
            }
        ];
    }

    // Feature flag helper — Super Admin always sees everything
    const settings = page.props.app_settings || {};
    const isFeatureEnabled = (key) => isSuperAdmin || settings[key] !== '0';

    // --- 1. Menu Umum untuk Semua Pegawai (Guru & Staf) ---
    const commonEmployeeMenu = {
        title: 'Kepegawaian',
        key: 'employee_personal',
        items: [
            { 
                label: 'Presensi Saya', 
                route: 'employee.attendance.index', 
                active: 'employee.attendance.*',
                icon: FingerPrintIcon 
            },
        ]
    };

    const counselingMenu = {
        title: 'Bimbingan Konseling',
        key: 'counseling_staff',
        items: [
            { 
                label: 'Sesi Konseling', 
                route: 'counseling.sessions.index', 
                active: 'counseling.sessions.*',
                icon: ChatBubbleLeftRightIcon 
            },
            { 
                label: 'Rekap Pelanggaran', 
                route: 'counseling.violations.index', 
                active: 'counseling.violations.*',
                icon: ExclamationTriangleIcon 
            },
            { 
                label: 'Data Prestasi', 
                route: 'counseling.achievements.index', 
                active: 'counseling.achievements.*',
                icon: TrophyIcon 
            },
            { 
                label: 'Kategori Pelanggaran', 
                route: 'counseling.categories.index', 
                active: 'counseling.categories.*',
                icon: TableCellsIcon 
            },
        ]
    };

    // Sarpar Menu (for Admin & Koordinator)
    const sarparMenu = {
        title: 'Sarana Prasarana',
        key: 'sarpar',
        items: [
            { 
                label: 'Ringkasan', 
                route: 'sarpar.dashboard', 
                active: 'sarpar.dashboard',
                icon: ChartBarSquareIcon 
            },
            { 
                label: 'Data Inventaris', 
                route: 'sarpar.inventories.index', 
                active: 'sarpar.inventories.*',
                icon: CubeIcon 
            },
            { 
                label: 'Data Ruangan', 
                route: 'sarpar.rooms.index', 
                active: 'sarpar.rooms.*',
                icon: BuildingOffice2Icon 
            },
            { 
                label: 'Kategori', 
                route: 'sarpar.categories.index', 
                active: 'sarpar.categories.*',
                icon: TagIcon 
            },
            { 
                label: 'Perawatan', 
                route: 'sarpar.maintenance.index', 
                active: 'sarpar.maintenance.*',
                icon: WrenchScrewdriverIcon 
            },
            { 
                label: 'Peminjaman', 
                route: 'sarpar.loans.index', 
                active: 'sarpar.loans.*',
                icon: ArrowPathIcon 
            },
        ]
    };

    // Humas Menu
    const humasMenu = {
        title: 'Humas & Publikasi',
        key: 'humas',
        items: [
            { 
                label: 'Berita Sekolah', 
                route: 'public-relations.news.index', 
                active: 'public-relations.news.*',
                icon: DocumentTextIcon 
            },
            { 
                label: 'Acara & Kegiatan', 
                route: 'public-relations.events.index', 
                active: 'public-relations.events.*',
                icon: CalendarDaysIcon 
            },
            { 
                label: 'Mitra & Kerjasama', 
                route: 'public-relations.partners.index', 
                active: 'public-relations.partners.*',
                icon: BuildingOffice2Icon 
            },
            { 
                label: 'Destinasi Universitas', 
                route: 'public-relations.university-destinations.index', 
                active: 'public-relations.university-destinations.*',
                icon: GlobeAltIcon 
            },
            { 
                label: 'Testimoni', 
                route: 'public-relations.testimonials.index', 
                active: 'public-relations.testimonials.*',
                icon: ChatBubbleLeftRightIcon 
            },
        ]
    };

    // Daycare Menu
    const daycareMenu = {
        title: 'Daycare & Pengasuhan',
        key: 'daycare',
        items: [
            { 
                label: 'Data Ananda Daycare', 
                route: 'daycare.children.index', 
                active: 'daycare.children.*',
                icon: UserGroupIcon 
            },
            { 
                label: 'Presensi & Handover', 
                route: 'daycare.attendance.index', 
                active: 'daycare.attendance.*',
                icon: ClockIcon 
            },
        ]
    };

    if (hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
        humasMenu.items.push({ 
            label: 'Hero Banner Slider', 
            route: 'public-relations.banners.index', 
            active: 'public-relations.banners.*',
            icon: CameraIcon 
        });
    }

    // If Admin/Yayasan, show everything + Personal Employee Menu + Counseling + Sarpar
    if (page.props.auth.user.email === 'admin@namira.school' || 
        hasRole('admin') || 
        hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan', 'admin_unit', 'kepala_sekolah'])) {
        
        const isGlobalAdmin = hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan']);
        const isFinanceStaff = hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'finance', 'staff_admin_keuangan']);
        const isKepalaSekolahOrGlobal = hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'kepala_sekolah']);

        let adminGroups = menuGroups.map(group => {
            let items = [...group.items];
            if (group.key === 'master') {
                items = items.filter(item => {
                    if (item.route === '/pulse' && !hasRole('super_admin_yayasan')) return false;
                    if (item.route === 'yayasan.attendance-locations.index' && !isGlobalAdmin) return false;
                    return true;
                });
            }
            if (group.key === 'employee') {
                items = items.filter(item => {
                    if (item.route === 'yayasan.attendance-approvals.index' && !isKepalaSekolahOrGlobal) return false;
                    return true;
                });
            }
            return { ...group, items };
        });

        // Filter admin menu groups by feature flags & role permissions
        adminGroups = adminGroups.filter(g => {
            if (g.key === 'finance') {
                if (!isFeatureEnabled('feature_finance')) return false;
                if (!isFinanceStaff) return false; // Admin Unit has no access to Keuangan
            }
            if (g.key === 'employee' && !isFeatureEnabled('feature_employee')) return false;
            if (g.key === 'academic' && !isFeatureEnabled('feature_academic')) return false;
            return true;
        });

        if (isSuperAdmin || page.props.session?.is_daycare || page.props.session?.features?.daycare === true) {
            adminGroups.push(daycareMenu);
        }
        if (isFeatureEnabled('feature_sarpar')) adminGroups.push(sarparMenu);
        adminGroups.push(commonEmployeeMenu);
        if (isFeatureEnabled('feature_counseling')) adminGroups.push(counselingMenu);
        adminGroups.push(humasMenu);

        return adminGroups;
    }

    let groups = [];



    // --- 2. Menu Khusus Berdasarkan Role ---

    // A. GURU (Teacher)
    if (isTeacher) {
        groups.push({
            title: 'Akademik',
            key: 'academic_teacher',
            items: [
                { 
                    label: 'Jadwal Pelajaran', 
                    route: 'yayasan.schedules.index', 
                    active: 'yayasan.schedules.*',
                    icon: ClockIcon 
                },
                { 
                    label: 'Jurnal Mengajar', 
                    route: 'yayasan.teaching-journal.index', 
                    active: 'yayasan.teaching-journal.*',
                    icon: PencilSquareIcon 
                },
                { 
                    label: 'Tujuan Pembelajaran', 
                    route: 'yayasan.learning-objectives.index', 
                    active: 'yayasan.learning-objectives.*',
                    icon: TrophyIcon 
                },
                { 
                    label: 'Presensi Siswa', 
                    route: 'yayasan.student-attendance.index', 
                    active: 'yayasan.student-attendance.*',
                    icon: ClipboardDocumentCheckIcon 
                },
            ]
        });

        groups.push({
            title: 'LMS (E-Learning)',
            key: 'lms_teacher',
            items: [
                { 
                    label: 'Kelas Virtual', 
                    route: 'lms.teacher.classrooms.index', 
                    active: 'lms.teacher.classrooms.*',
                    icon: ComputerDesktopIcon 
                },
            ]
        });
    }

    // B. WALI KELAS (Homeroom Teacher)
    if (hasRole('wali_kelas')) {
        groups.push({
            title: 'Wali Kelas',
            key: 'homeroom',
            items: [
                { 
                    label: 'Data Kelas', 
                    route: 'yayasan.classrooms.index', 
                    active: 'yayasan.classrooms.*',
                    icon: HomeModernIcon 
                },
                { 
                    label: 'Data Siswa', 
                    route: 'yayasan.students.index', 
                    active: 'yayasan.students.*',
                    icon: AcademicCapIcon 
                },
                { 
                    label: 'Presensi Siswa', 
                    route: 'yayasan.student-attendance.index', 
                    active: 'yayasan.student-attendance.*',
                    icon: ClipboardDocumentCheckIcon 
                },
                { 
                    label: 'Jadwal Pelajaran', 
                    route: 'yayasan.schedules.index', 
                    active: 'yayasan.schedules.*',
                    icon: ClockIcon 
                },
            ]
        });
    }

    // C. KOORDINATOR KURIKULUM
    if (hasRole('koordinator_kurikulum')) {
        groups.push({
            title: 'Kurikulum',
            key: 'curriculum',
            items: [
                { 
                    label: 'Tahun Akademik', 
                    route: 'yayasan.academic-years.index', 
                    active: 'yayasan.academic-years.*',
                    icon: CalendarDaysIcon 
                },
                { 
                    label: 'Mata Pelajaran', 
                    route: 'yayasan.subjects.index', 
                    active: 'yayasan.subjects.*',
                    icon: BookOpenIcon 
                },
                { 
                    label: 'Jadwal Pelajaran', 
                    route: 'yayasan.schedules.index', 
                    active: 'yayasan.schedules.*',
                    icon: ClockIcon 
                },
                { 
                    label: 'Tujuan Pembelajaran', 
                    route: 'yayasan.learning-objectives.index', 
                    active: 'yayasan.learning-objectives.*',
                    icon: TrophyIcon 
                },
                { 
                    label: 'Jurnal Mengajar', 
                    route: 'yayasan.teaching-journal.index', 
                    active: 'yayasan.teaching-journal.*',
                    icon: PencilSquareIcon 
                },
            ]
        });
    }

    // D. HUMAS (Public Relations Staff)
    if (hasAnyRole(['humas_unit', 'humas_yayasan'])) {
        groups.push(humasMenu);
    }

    // E. KEUANGAN (Finance Staff)
    if ((hasRole('staff_admin_keuangan') || hasRole('finance')) && isFeatureEnabled('feature_finance')) {
        groups.push({
            title: 'Keuangan',
            key: 'finance_staff',
            items: [
                { 
                    label: 'Dashboard Keuangan', 
                    route: 'yayasan.finance.dashboard', 
                    active: 'yayasan.finance.dashboard',
                    icon: ChartBarSquareIcon 
                },
                { 
                    label: 'Pos Bayar', 
                    route: 'yayasan.finance.types.index', 
                    active: 'yayasan.finance.types.*',
                    icon: BanknotesIcon 
                },
                { 
                    label: 'Rekening Bank', 
                    route: 'yayasan.finance.accounts.index', 
                    active: 'yayasan.finance.accounts.*',
                    icon: CreditCardIcon 
                },
                { 
                    label: 'Tagihan Siswa', 
                    route: 'yayasan.finance.bills.index', 
                    active: 'yayasan.finance.bills.*',
                    icon: DocumentTextIcon 
                },
                { 
                    label: 'Mutasi & Pembayaran', 
                    route: 'yayasan.finance.transactions.index', 
                    active: 'yayasan.finance.transactions.*',
                    icon: ArrowsRightLeftIcon 
                },
                { 
                    label: 'Laporan Tunggakan', 
                    route: 'yayasan.finance.reports.arrears', 
                    active: 'yayasan.finance.reports.arrears',
                    icon: ExclamationTriangleIcon 
                },
            ]
        });
    }

    // E. BIMBINGAN KONSELING (Guru / BK / Wali Kelas / Admin)
    if ((hasRole('teacher') || hasRole('bk') || hasRole('wali_kelas') || hasRole('admin_unit') || hasRole('super_admin_yayasan') || hasRole('admin_yayasan')) && isFeatureEnabled('feature_counseling')) {
        groups.push({
            title: 'Bimbingan Konseling',
            key: 'counseling_staff',
            items: [
                { 
                    label: 'Sesi Konseling', 
                    route: 'counseling.sessions.index', 
                    active: 'counseling.sessions.*',
                    icon: ChatBubbleLeftRightIcon 
                },
                { 
                    label: 'Rekap Pelanggaran', 
                    route: 'counseling.violations.index', 
                    active: 'counseling.violations.*',
                    icon: ExclamationTriangleIcon 
                },
                { 
                    label: 'Data Prestasi', 
                    route: 'counseling.achievements.index', 
                    active: 'counseling.achievements.*',
                    icon: TrophyIcon 
                },
            ]
        });
        
        // BK Special Menu
        if (hasRole('bk')) {
             groups[groups.length - 1].items.push({ 
                label: 'Kategori Pelanggaran', 
                route: 'counseling.categories.index', 
                active: 'counseling.categories.*',
                icon: TableCellsIcon 
            });
        }
    }

    // F. KOORDINATOR SARPAR
    if (hasRole('koordinator_sarpar') && isFeatureEnabled('feature_sarpar')) {
        groups.push({
            title: 'Sarana Prasarana',
            key: 'sarpar_staff',
            items: [
                { 
                    label: 'Ringkasan', 
                    route: 'sarpar.dashboard', 
                    active: 'sarpar.dashboard',
                    icon: ChartBarSquareIcon 
                },
                { 
                    label: 'Data Inventaris', 
                    route: 'sarpar.inventories.index', 
                    active: 'sarpar.inventories.*',
                    icon: CubeIcon 
                },
                { 
                    label: 'Data Ruangan', 
                    route: 'sarpar.rooms.index', 
                    active: 'sarpar.rooms.*',
                    icon: BuildingOffice2Icon 
                },
                { 
                    label: 'Kategori', 
                    route: 'sarpar.categories.index', 
                    active: 'sarpar.categories.*',
                    icon: TagIcon 
                },
                { 
                    label: 'Perawatan', 
                    route: 'sarpar.maintenance.index', 
                    active: 'sarpar.maintenance.*',
                    icon: WrenchScrewdriverIcon 
                },
                { 
                    label: 'Peminjaman', 
                    route: 'sarpar.loans.index', 
                    active: 'sarpar.loans.*',
                    icon: ArrowPathIcon 
                },
            ]
        });
    }

    // LMS Siswa Menu
    if (hasRole('siswa') || hasRole('student')) {
        groups.push({
            title: 'LMS (E-Learning)',
            key: 'lms_student',
            items: [
                { 
                    label: 'Kelas Saya', 
                    route: 'lms.student.classrooms.index', 
                    active: 'lms.student.classrooms.*',
                    icon: AcademicCapIcon 
                },
                { 
                    label: 'Nilai Saya', 
                    route: 'lms.student.grades.index', 
                    active: 'lms.student.grades.index',
                    icon: TrophyIcon 
                },
            ]
        });
    }

    // Add Common Menu for All Employees (including logic flow)
    // Add Common Menu for All Employees (excluding students)
    if (!hasRole('siswa') && !hasRole('student')) {
         groups.push(commonEmployeeMenu);
    }
    
    return groups;
});
</script>

<template>
    <aside
        class="fixed inset-y-0 left-0 z-30 transform bg-white/80 backdrop-blur-xl transition-all duration-300 ease-[cubic-bezier(0.25,0.8,0.25,1)] border-r border-white/50 shadow-[4px_0_24px_rgba(0,0,0,0.02)] flex flex-col"
        :class="{ 
            'w-64': isSidebarOpen, 
            'w-20': !isSidebarOpen,
        }"
    >
        <!-- Logo Area -->
        <div class="flex-none h-16 flex items-center justify-center border-b border-slate-100/50 transition-all duration-300">
            <Link :href="hasRole('siswa') || hasRole('student') ? route('student.dashboard') : route('dashboard')" class="flex items-center gap-3 group transition-all" :class="{'gap-0 justify-center': !isSidebarOpen}">
                <div class="relative transition-transform duration-300 group-hover:scale-105 drop-shadow-sm h-8 w-auto flex items-center justify-center">
                     <img v-if="$page.props.app_settings?.app_logo" :src="$page.props.app_settings.app_logo" class="h-8 w-auto object-contain" />
                     <ApplicationLogo v-else class="h-8 w-auto" />
                </div>
                <div class="flex flex-col transition-all duration-300 overflow-hidden whitespace-nowrap" 
                    :class="{'w-0 opacity-0': !isSidebarOpen, 'w-auto opacity-100': isSidebarOpen}">
                    <span class="text-lg font-bold tracking-tight text-slate-900 font-sans">
                        {{ $page.props.app_settings?.app_name || 'Namira' }}
                    </span>
                </div>
            </Link>
        </div>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto py-6 px-3 scrollbar-hide space-y-8">
            
            <!-- Unit Switcher -->
            <div class="relative">
                <button 
                    @click="showUnitMenu = !showUnitMenu" 
                    class="w-full bg-white/60 border border-white/60 shadow-sm rounded-xl flex items-center transition-all duration-200 hover:bg-white hover:shadow-md group backdrop-blur-sm"
                    :class="{'p-2 pr-3': isSidebarOpen, 'p-2 justify-center': !isSidebarOpen}"
                >
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg bg-slate-900 text-white flex items-center justify-center shadow-sm min-w-[32px] overflow-hidden">
                            <img v-if="$page.props.session?.active_unit_logo" :src="$page.props.session.active_unit_logo" class="w-full h-full object-cover" />
                            <span v-else class="font-bold text-xs">{{ ($page.props.session?.active_unit_name || 'N').substring(0,1) }}</span>
                        </div>
                        <div class="text-left overflow-hidden transition-all duration-300" 
                            :class="{'w-full opacity-100': isSidebarOpen, 'w-0 opacity-0 hidden': !isSidebarOpen}">
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider leading-none mb-0.5">Unit Aktif</p>
                            <p class="text-xs font-semibold text-slate-700 truncate leading-tight">{{ $page.props.session?.active_unit_name || 'Loading...' }}</p>
                        </div>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-slate-400 group-hover:text-slate-600 transition-colors ml-auto"
                        :class="{'hidden': !isSidebarOpen}">
                         <ChevronDownIcon class="w-3 h-3 transition-transform duration-200" :class="{'rotate-180': showUnitMenu}" />
                    </div>
                </button>

                <!-- Dropdown Menu -->
                <div v-show="showUnitMenu && isSidebarOpen" class="absolute top-full left-0 w-full mt-2 bg-white/90 backdrop-blur-xl rounded-xl shadow-xl shadow-slate-200/50 overflow-hidden z-20 border border-white/50 p-1 animate-in fade-in zoom-in-95 duration-200 origin-top">
                    <div class="px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pilih Unit</div>
                    <div class="max-h-60 overflow-y-auto scrollbar-hide space-y-0.5">
                        <button 
                            v-for="unit in $page.props.session?.available_units || []" 
                            :key="unit.id"
                            @click="switchUnit(unit.id)"
                            class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition-all duration-200 flex items-center justify-between group gap-2"
                            :class="unit.id === $page.props.session?.active_unit_id ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-white/50'"
                        >
                            <div class="flex items-center gap-2 overflow-hidden">
                                <div class="w-6 h-6 rounded-md bg-slate-100 border border-slate-200 shrink-0 overflow-hidden flex items-center justify-center text-[10px] font-black text-slate-700">
                                    <img v-if="unit.logo_url" :src="unit.logo_url" class="w-full h-full object-cover" />
                                    <span v-else>{{ unit.name.substring(0,1) }}</span>
                                </div>
                                <span class="truncate">{{ unit.name }}</span>
                            </div>
                            <CheckBadgeIcon v-if="unit.id === $page.props.session?.active_unit_id" class="w-3.5 h-3.5 text-teal-600 shrink-0" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="space-y-6">
                <!-- Dashboard -->
                 <Link
                    :href="hasRole('siswa') || hasRole('student') ? route('student.dashboard') : route('yayasan.dashboard')"
                    class="flex items-center gap-3 rounded-xl py-2.5 text-sm font-medium transition-all duration-200 group relative"
                    :class="[
                        isActive('yayasan.dashboard') 
                            ? 'bg-white shadow-sm text-teal-700 ring-1 ring-slate-900/5' 
                            : 'text-slate-600 hover:bg-white/50 hover:text-slate-900',
                        isSidebarOpen ? 'px-3.5' : 'px-0 justify-center'
                    ]"
                >
                    <component :is="HomeModernIcon" class="h-5 w-5 min-w-[20px]" :class="isActive('yayasan.dashboard') ? 'text-teal-600' : 'text-slate-400 group-hover:text-slate-600'" />
                    <span class="transition-all duration-300" :class="{'w-auto opacity-100': isSidebarOpen, 'w-0 opacity-0 hidden': !isSidebarOpen}">Dashboard</span>
                </Link>

                <!-- Groups -->
                <div v-for="group in filteredMenuGroups" :key="group.key" class="space-y-2">
                    <!-- Group Header -->
                    <div 
                        class="flex items-center px-3 text-[10px] font-bold uppercase tracking-widest text-slate-400/80"
                        :class="[isSidebarOpen ? 'justify-between' : 'justify-center px-0']"
                    >
                        <span :class="{'hidden': !isSidebarOpen}">{{ group.title }}</span>
                        <div v-if="!isSidebarOpen" class="w-4 h-px bg-slate-200"></div>
                    </div>

                    <!-- Group Items -->
                    <div class="space-y-1">
                        <Link 
                            v-for="item in group.items" 
                            :key="item.route"
                            :href="resolveRoute(item.route)"
                             class="flex items-center gap-3 rounded-xl py-2.5 text-sm font-medium transition-all duration-200 group relative"
                            :class="[
                                isActive(item.active) 
                                    ? 'bg-white shadow-sm text-teal-700 ring-1 ring-slate-900/5' 
                                    : 'text-slate-600 hover:bg-white/50 hover:text-slate-900',
                                isSidebarOpen ? 'px-3.5' : 'px-0 justify-center'
                            ]"
                        >
                            <component :is="item.icon" class="h-5 w-5 min-w-[20px]" :class="isActive(item.active) ? 'text-teal-600' : 'text-slate-400 group-hover:text-slate-600'" />
                            
                            <span :class="{'hidden': !isSidebarOpen}">{{ item.label }}</span>
                        </Link>
                    </div>
                </div>
            </nav>
        </div>
        
        <!-- Footer / Version with Integrated Settings -->
        <div class="flex-none p-3.5 border-t border-slate-100/50 bg-white/40 backdrop-blur-sm">
             <div class="flex items-center justify-between gap-2" :class="{'justify-center': !isSidebarOpen}">
                <div class="flex items-center gap-2.5 overflow-hidden">
                    <div class="h-8 w-8 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs min-w-[32px] shadow-sm">
                        NF
                    </div>
                    <div class="transition-all duration-300 overflow-hidden" 
                        :class="{'w-auto opacity-100': isSidebarOpen, 'w-0 opacity-0 hidden': !isSidebarOpen}">
                       <p class="text-xs font-bold text-slate-800 leading-tight truncate">Namira Foundation</p>
                       <p class="text-[10px] text-slate-400 font-medium leading-tight">v1.2.0 (SaaS)</p>
                    </div>
                </div>

                <!-- Integrated System Settings Gear Icon (Super Admin Only) -->
                <Link
                    v-if="hasRole('super_admin_yayasan')"
                    :href="route('yayasan.settings.index')"
                    title="Pengaturan Sistem"
                    class="p-2 rounded-xl text-slate-400 hover:text-teal-700 hover:bg-white shadow-xs border border-transparent hover:border-slate-200/80 transition-all duration-200 group flex items-center justify-center shrink-0"
                    :class="[
                        isActive('yayasan.settings.*') ? 'bg-white text-teal-700 border-slate-200/80 shadow-xs' : '',
                        !isSidebarOpen ? 'hidden' : ''
                    ]"
                >
                    <Cog6ToothIcon class="h-5 w-5 transition-transform duration-300 group-hover:rotate-90" />
                </Link>
             </div>
        </div>
    </aside>
</template>
