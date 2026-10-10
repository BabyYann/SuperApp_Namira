<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    SparklesIcon,
    ArrowLeftIcon,
    CalendarDaysIcon,
    ClockIcon,
    MapPinIcon,
    UserGroupIcon,
    AcademicCapIcon,
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    PhotoIcon,
    CheckCircleIcon,
    XMarkIcon,
    MagnifyingGlassIcon,
    ChevronDownIcon,
    ChatBubbleLeftRightIcon,
    DocumentTextIcon,
    ShieldCheckIcon,
    UserPlusIcon,
    CameraIcon,
    CheckBadgeIcon,
    FunnelIcon,
} from '@heroicons/vue/24/outline';
import { 
    SparklesIcon as SparklesIconSolid,
    CheckCircleIcon as CheckCircleIconSolid 
} from '@heroicons/vue/24/solid';

const props = defineProps({
    activity: { type: Object, required: true },
    classrooms: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] },
    availableInstructors: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
    isInstructor: { type: Boolean, default: false },
});

// Active Tab ('overview' | 'members' | 'sessions' | 'grades')
const activeTab = ref('sessions');

// Search & filter members
const memberSearch = ref('');
const memberClassFilter = ref('all');

// Modals
const showAddMemberModal = ref(false);
const showAddCoachModal = ref(false);
const showSessionModal = ref(false);
const showZoomPhotoModal = ref(false);
const zoomedPhotoUrl = ref('');

// Forms
const memberForm = useForm({
    mode: 'single', // 'single' | 'classroom_bulk'
    student_id: '',
    classroom_id: '',
    notes: '',
});

const coachForm = useForm({
    mode: 'existing', // 'existing' | 'create_new'
    user_id: '',
    name: '',
    email: '',
    phone: '',
    password: '',
    role_title: 'Pelatih Utama',
    institution: '',
    notes: '',
});

const sessionForm = useForm({
    date: new Date().toISOString().substr(0, 10),
    start_time: props.activity.start_time || '14:30',
    end_time: props.activity.end_time || '16:00',
    topic: '',
    notes: '',
    photos: [],
    attendances: [],
    send_wa: true,
});

const gradesForm = useForm({
    grades: [],
});

// Photo upload state
const isCompressing = ref(false);
const sessionPhotoPreviews = ref([]);

// Image compression
const compressImage = (file) => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const maxWidth = 1280;
                const maxHeight = 1280;
                let width = img.width;
                let height = img.height;

                if (width > height) {
                    if (width > maxWidth) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = Math.round((width * maxHeight) / height);
                        height = maxHeight;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                const compressedBase64 = canvas.toDataURL('image/jpeg', 0.72);
                resolve(compressedBase64);
            };
            img.onerror = (err) => reject(err);
        };
        reader.onerror = (err) => reject(err);
    });
};

const handleSessionPhotos = async (e) => {
    const files = Array.from(e.target.files);
    if (!files.length) return;

    isCompressing.value = true;
    try {
        for (const file of files) {
            const compressedBase64 = await compressImage(file);
            sessionPhotoPreviews.value.push(compressedBase64);
            sessionForm.photos.push(compressedBase64);
        }
    } catch (err) {
        console.error('Failed to compress session photo:', err);
    } finally {
        isCompressing.value = false;
    }
};

const removeSessionPhoto = (idx) => {
    sessionPhotoPreviews.value.splice(idx, 1);
    sessionForm.photos.splice(idx, 1);
};

// Filtered members
const filteredMembers = computed(() => {
    const list = props.activity.active_members || [];
    return list.filter((m) => {
        const student = m.student;
        if (!student) return false;

        const q = memberSearch.value.toLowerCase().trim();
        const matchesQuery = !q || 
            student.full_name?.toLowerCase().includes(q) || 
            student.nisn?.includes(q) || 
            student.nis?.includes(q);

        const matchesClass = memberClassFilter.value === 'all' || 
            (student.classroom_id && String(student.classroom_id) === String(memberClassFilter.value));

        return matchesQuery && matchesClass;
    });
});

// Open Add Session Modal & Prepopulate Attendance list
const openNewSessionModal = () => {
    sessionForm.reset();
    sessionForm.clearErrors();
    sessionForm.date = new Date().toISOString().substr(0, 10);
    sessionForm.start_time = props.activity.start_time || '14:30';
    sessionForm.end_time = props.activity.end_time || '16:00';
    sessionPhotoPreviews.value = [];

    // Prepopulate attendance from active members
    const members = props.activity.active_members || [];
    sessionForm.attendances = members.map((m) => ({
        student_id: m.student_id,
        student_name: m.student?.full_name || 'Siswa',
        classroom_name: m.student?.classroom?.name || '-',
        status: 'hadir', // Default hadir
        notes: '',
    }));

    showSessionModal.value = true;
};

// Quick mark all as Hadir
const markAllPresent = () => {
    sessionForm.attendances.forEach((a) => {
        a.status = 'hadir';
    });
};

// Submit Session
const submitSession = () => {
    sessionForm.post(route('extracurricular.sessions.store', props.activity.id), {
        preserveScroll: true,
        onSuccess: () => {
            showSessionModal.value = false;
            sessionForm.reset();
        },
    });
};

// Submit Member
const submitMember = () => {
    memberForm.post(route('extracurricular.members.store', props.activity.id), {
        preserveScroll: true,
        onSuccess: () => {
            showAddMemberModal.value = false;
            memberForm.reset();
        },
    });
};

// Submit Coach
const submitCoach = () => {
    coachForm.post(route('extracurricular.instructors.store', props.activity.id), {
        preserveScroll: true,
        onSuccess: () => {
            showAddCoachModal.value = false;
            coachForm.reset();
        },
    });
};

// Delete Member
const deleteMember = (member) => {
    if (confirm(`Hapus ${member.student?.full_name} dari anggota kegiatan ini?`)) {
        router.delete(route('extracurricular.members.destroy', [props.activity.id, member.id]), {
            preserveScroll: true,
        });
    }
};

// Unassign Coach
const unassignCoach = (instructor) => {
    if (confirm(`Nonaktifkan pelatih ${instructor.user?.name} dari kegiatan ini?`)) {
        router.delete(route('extracurricular.instructors.destroy', [props.activity.id, instructor.id]), {
            preserveScroll: true,
        });
    }
};

// Init Grades Form
const initGradesForm = () => {
    const members = props.activity.active_members || [];
    gradesForm.grades = members.map((m) => ({
        student_id: m.student_id,
        student_name: m.student?.full_name || 'Siswa',
        classroom_name: m.student?.classroom?.name || '-',
        predicate: m.grade?.predicate || 'A',
        description: m.grade?.description || `Ananda sangat aktif dan antusias dalam mengikuti kegiatan ${props.activity.name}.`,
    }));
};

// Apply Auto Description Template based on Predicate
const applyDescriptionTemplate = (item) => {
    const actName = props.activity.name;
    switch (item.predicate) {
        case 'A':
        case 'Sangat Baik':
            item.description = `Ananda sangat aktif, disiplin, dan menguasai seluruh teknik dasar serta capaian dalam kegiatan ${actName} dengan sangat baik.`;
            break;
        case 'B':
        case 'Baik':
            item.description = `Ananda aktif mengikuti pembinaan ${actName} dan menunjukkan perkembangan teknik serta kerjasama yang baik.`;
            break;
        case 'C':
        case 'Cukup':
            item.description = `Ananda cukup konsisten dalam kegiatan ${actName}, perlu sedikit peningkatan dalam kedisiplinan dan penguasaan teknik.`;
            break;
        case 'D':
        case 'Kurang':
            item.description = `Ananda perlu bimbingan dan motivasi tambahan untuk lebih aktif dalam kegiatan ${actName}.`;
            break;
    }
};

// Submit Grades
const submitGrades = () => {
    gradesForm.post(route('extracurricular.grades.store', props.activity.id), {
        preserveScroll: true,
    });
};

// Photo Zoom Modal
const zoomPhoto = (url) => {
    zoomedPhotoUrl.value = url;
    showZoomPhotoModal.value = true;
};
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="`${activity.name} - Ekstrakurikuler`" />

        <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            
            <!-- Back & Breadcrumb -->
            <div class="flex items-center gap-3">
                <Link
                    :href="route('extracurricular.index')"
                    class="p-2 rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-teal-700 hover:bg-teal-50 transition-colors shadow-2xs"
                >
                    <ArrowLeftIcon class="w-5 h-5 stroke-[2.2]" />
                </Link>
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-500 font-semibold">
                        <Link :href="route('extracurricular.index')" class="hover:underline">Ekstrakurikuler</Link>
                        <span>/</span>
                        <span class="text-teal-800 font-bold">{{ activity.name }}</span>
                    </div>
                </div>
            </div>

            <!-- Header Card -->
            <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-6 sm:p-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="flex items-start sm:items-center gap-4">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-teal-50 border border-teal-100 flex items-center justify-center shrink-0 overflow-hidden shadow-xs">
                        <img v-if="activity.cover_image_url" :src="activity.cover_image_url" class="w-full h-full object-cover" />
                        <SparklesIcon v-else class="w-10 h-10 text-teal-600" />
                    </div>
                    <div class="space-y-1.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-black uppercase tracking-wider bg-teal-50 text-teal-800 border border-teal-200">
                                {{ activity.category_label }}
                            </span>
                            <span v-if="activity.is_mandatory" class="px-2.5 py-0.5 rounded-full text-xs font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                                Wajib
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                {{ activity.unit?.name || 'Unit Sekolah' }}
                            </span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                            {{ activity.name }}
                        </h1>
                        <div class="flex flex-wrap items-center gap-4 text-xs text-slate-600 pt-1">
                            <span class="flex items-center gap-1.5 font-semibold">
                                <ClockIcon class="w-4 h-4 text-teal-600" />
                                {{ activity.formatted_schedule }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <MapPinIcon class="w-4 h-4 text-rose-500" />
                                {{ activity.room?.name || activity.location_name || 'Kampus' }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <UserGroupIcon class="w-4 h-4 text-sky-600" />
                                {{ activity.active_members?.length || 0 }} / {{ activity.max_quota }} Siswa
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Primary Action Button: Mulai Sesi -->
                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <button
                        v-if="canManage || isInstructor"
                        @click="openNewSessionModal"
                        type="button"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-sm shadow-md shadow-teal-800/20 transition-all active:scale-95"
                    >
                        <CameraIcon class="w-5 h-5 stroke-[2.2]" />
                        <span>Buka Sesi Latihan Hari Ini</span>
                    </button>
                </div>
            </div>

            <!-- Tab Navigation Bar -->
            <div class="flex border-b border-slate-200 overflow-x-auto scrollbar-none bg-white rounded-2xl p-1.5 shadow-2xs">
                <button
                    @click="activeTab = 'sessions'"
                    type="button"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all"
                    :class="activeTab === 'sessions' ? 'bg-teal-700 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'"
                >
                    <CalendarDaysIcon class="w-4 h-4" />
                    <span>Jurnal Sesi & Presensi ({{ activity.sessions?.length || 0 }})</span>
                </button>

                <button
                    @click="activeTab = 'members'"
                    type="button"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all"
                    :class="activeTab === 'members' ? 'bg-teal-700 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'"
                >
                    <UserGroupIcon class="w-4 h-4" />
                    <span>Anggota Siswa ({{ activity.active_members?.length || 0 }})</span>
                </button>

                <button
                    @click="activeTab = 'overview'"
                    type="button"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all"
                    :class="activeTab === 'overview' ? 'bg-teal-700 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'"
                >
                    <ShieldCheckIcon class="w-4 h-4" />
                    <span>Profil & Pelatih ({{ activity.instructors?.length || 0 }})</span>
                </button>

                <button
                    @click="activeTab = 'grades'; initGradesForm()"
                    type="button"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all"
                    :class="activeTab === 'grades' ? 'bg-teal-700 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'"
                >
                    <DocumentTextIcon class="w-4 h-4" />
                    <span>Penilaian Rapor Semester</span>
                </button>
            </div>

            <!-- TAB 1: JURNAL SESI & PRESENSI -->
            <div v-if="activeTab === 'sessions'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-black text-slate-800">Riwayat Latihan & Pertemuan</h2>
                        <p class="text-xs text-slate-500">Seluruh sesi latihan yang telah diselesaikan beserta dokumentasi dan absensi siswa.</p>
                    </div>

                    <button
                        v-if="canManage || isInstructor"
                        @click="openNewSessionModal"
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-teal-50 text-teal-800 hover:bg-teal-100 font-bold text-xs border border-teal-200 transition-colors"
                    >
                        <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                        <span>Sesi Baru</span>
                    </button>
                </div>

                <!-- Session Cards -->
                <div v-if="activity.sessions?.length > 0" class="space-y-4">
                    <div
                        v-for="session in activity.sessions"
                        :key="session.id"
                        class="bg-white rounded-3xl border border-slate-200/90 shadow-xs p-5 sm:p-6 space-y-4"
                    >
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                            <div>
                                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                                    <CalendarDaysIcon class="w-4 h-4 text-teal-600" />
                                    <span>{{ new Date(session.date).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</span>
                                    <span>•</span>
                                    <span>{{ session.start_time }} - {{ session.end_time }} WIB</span>
                                </div>
                                <h3 class="font-black text-lg text-slate-800 mt-1">
                                    {{ session.topic }}
                                </h3>
                                <p class="text-xs text-slate-500">
                                    Dipandu oleh: <span class="font-bold text-slate-700">{{ session.instructor?.name || 'Pelatih' }}</span>
                                </p>
                            </div>

                            <!-- Attendance summary pills -->
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    {{ session.attendees_summary?.hadir || 0 }} Hadir
                                </span>
                                <span v-if="session.attendees_summary?.izin > 0" class="px-2.5 py-1 rounded-xl text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                    {{ session.attendees_summary.izin }} Izin
                                </span>
                                <span v-if="session.attendees_summary?.sakit > 0" class="px-2.5 py-1 rounded-xl text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    {{ session.attendees_summary.sakit }} Sakit
                                </span>
                                <span v-if="session.attendees_summary?.alpha > 0" class="px-2.5 py-1 rounded-xl text-xs font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                    {{ session.attendees_summary.alpha }} Alpha
                                </span>
                            </div>
                        </div>

                        <!-- Notes if any -->
                        <div v-if="session.notes" class="text-xs text-slate-600 bg-slate-50 rounded-2xl p-3 border border-slate-100 leading-relaxed">
                            <span class="font-bold text-slate-800">Catatan Evaluasi:</span> {{ session.notes }}
                        </div>

                        <!-- Photos Gallery -->
                        <div v-if="session.photo_urls?.length > 0" class="space-y-2">
                            <div class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <PhotoIcon class="w-4 h-4 text-teal-600" />
                                <span>Dokumentasi Kegiatan ({{ session.photo_urls.length }} Foto)</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div
                                    v-for="(photo, pidx) in session.photo_urls"
                                    :key="pidx"
                                    @click="zoomPhoto(photo)"
                                    class="relative h-28 rounded-2xl overflow-hidden border border-slate-200 cursor-pointer group bg-slate-100"
                                >
                                    <img :src="photo" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                    <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold">
                                        Perbesar
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Student Attendance List Accordion / Preview -->
                        <details class="text-xs group">
                            <summary class="font-bold text-teal-700 cursor-pointer hover:underline select-none flex items-center gap-1">
                                <span>Lihat Rincian Kehadiran Siswa</span>
                                <ChevronDownIcon class="w-3.5 h-3.5 transition-transform group-open:rotate-180" />
                            </summary>
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 pt-2 border-t border-slate-100">
                                <div
                                    v-for="att in session.attendances"
                                    :key="att.id"
                                    class="p-2 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between gap-2"
                                >
                                    <div class="truncate">
                                        <div class="font-bold text-slate-800 truncate">{{ att.student?.full_name || 'Siswa' }}</div>
                                        <div v-if="att.notes" class="text-[10px] text-slate-500 italic truncate">{{ att.notes }}</div>
                                    </div>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider shrink-0"
                                        :class="{
                                            'bg-emerald-100 text-emerald-800': att.status === 'hadir',
                                            'bg-sky-100 text-sky-800': att.status === 'izin',
                                            'bg-amber-100 text-amber-800': att.status === 'sakit',
                                            'bg-rose-100 text-rose-800': att.status === 'alpha',
                                        }"
                                    >
                                        {{ att.status }}
                                    </span>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>

                <!-- Empty Sessions -->
                <div v-else class="bg-white rounded-3xl p-10 text-center border border-slate-200/80 shadow-xs space-y-3">
                    <CalendarDaysIcon class="w-12 h-12 text-slate-300 mx-auto" />
                    <h3 class="font-black text-slate-800 text-base">Belum Ada Sesi Latihan</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">
                        Pelatih dapat mencatat pertemuan perdana, presensi anggota, dan dokumentasi foto lapangan sekarang.
                    </p>
                    <button
                        v-if="canManage || isInstructor"
                        @click="openNewSessionModal"
                        type="button"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-teal-700 text-white font-bold text-xs hover:bg-teal-800 transition-all shadow-xs"
                    >
                        <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                        <span>Mulai Sesi Pertama</span>
                    </button>
                </div>
            </div>

            <!-- TAB 2: ANGGOTA SISWA -->
            <div v-else-if="activeTab === 'members'" class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-black text-slate-800">Daftar Anggota Siswa</h2>
                        <p class="text-xs text-slate-500">
                            Terisi {{ activity.active_members?.length || 0 }} dari kuota {{ activity.max_quota }} siswa.
                        </p>
                    </div>

                    <button
                        v-if="canManage || isInstructor"
                        @click="showAddMemberModal = true"
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs shadow-xs transition-all active:scale-95"
                    >
                        <UserPlusIcon class="w-4 h-4 stroke-[2.5]" />
                        <span>Tambah Anggota Siswa</span>
                    </button>
                </div>

                <!-- Search & Class Filter -->
                <div class="bg-white rounded-2xl p-3 border border-slate-200 flex flex-col sm:flex-row gap-3 items-center justify-between">
                    <div class="relative w-full sm:w-80">
                        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="memberSearch"
                            type="text"
                            placeholder="Cari nama siswa atau NISN..."
                            class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500 focus:border-teal-500"
                        />
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <label class="text-xs font-semibold text-slate-600 shrink-0">Filter Kelas:</label>
                        <select
                            v-model="memberClassFilter"
                            class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-teal-500 bg-white"
                        >
                            <option value="all">Semua Kelas</option>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Members Table / Cards -->
                <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden">
                    <div v-if="filteredMembers.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/80 border-b border-slate-100 text-slate-500 uppercase tracking-wider font-extrabold text-[10px]">
                                <tr>
                                    <th class="py-3 px-4">Nama Siswa</th>
                                    <th class="py-3 px-4">NISN / NIS</th>
                                    <th class="py-3 px-4">Kelas</th>
                                    <th class="py-3 px-4">Tanggal Gabung</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th v-if="canManage || isInstructor" class="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <tr v-for="member in filteredMembers" :key="member.id" class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-3.5 px-4 font-bold text-slate-900 flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-xs shrink-0">
                                            {{ member.student?.full_name?.charAt(0) || 'S' }}
                                        </div>
                                        <span>{{ member.student?.full_name }}</span>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono text-slate-500">
                                        {{ member.student?.nisn || member.student?.nis || '-' }}
                                    </td>
                                    <td class="py-3.5 px-4 font-semibold text-slate-800">
                                        {{ member.student?.classroom?.name || '-' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-500">
                                        {{ member.joined_date || '-' }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800">
                                            {{ member.status }}
                                        </span>
                                    </td>
                                    <td v-if="canManage || isInstructor" class="py-3.5 px-4 text-right">
                                        <button
                                            @click="deleteMember(member)"
                                            type="button"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                            title="Hapus dari Ekskul"
                                        >
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="p-8 text-center text-slate-400 text-xs">
                        Tidak ada siswa yang sesuai dengan filter pencarian.
                    </div>
                </div>
            </div>

            <!-- TAB 3: PROFIL & PELATIH -->
            <div v-else-if="activeTab === 'overview'" class="space-y-5">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Left: Detail Ekskul -->
                    <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200/90 shadow-xs p-6 space-y-4">
                        <h2 class="text-base font-black text-slate-800">Tentang Ekstrakurikuler</h2>
                        <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">
                            {{ activity.description || 'Belum ada deskripsi rinci untuk kegiatan ekstrakurikuler ini.' }}
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-slate-100 text-xs">
                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 space-y-1">
                                <span class="text-slate-400 font-semibold">Jadwal Rutin:</span>
                                <div class="font-black text-slate-800">{{ activity.formatted_schedule }}</div>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 space-y-1">
                                <span class="text-slate-400 font-semibold">Tempat / Ruangan:</span>
                                <div class="font-black text-slate-800">{{ activity.room?.name || activity.location_name || 'Kampus Sekolah' }}</div>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 space-y-1">
                                <span class="text-slate-400 font-semibold">Batasan Gender:</span>
                                <div class="font-black text-slate-800">
                                    {{ activity.gender_restriction === 'all' ? 'Semua Siswa' : (activity.gender_restriction === 'male_only' ? 'Khusus Putra' : 'Khusus Putri') }}
                                </div>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 space-y-1">
                                <span class="text-slate-400 font-semibold">Kapasitas Maksimal:</span>
                                <div class="font-black text-slate-800">{{ activity.max_quota }} Siswa</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Daftar Pelatih / Pembina -->
                    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-black text-slate-800">Pelatih / Pembina</h2>
                                <p class="text-xs text-slate-500">Pengampu resmi kegiatan.</p>
                            </div>

                            <button
                                v-if="canManage"
                                @click="showAddCoachModal = true"
                                type="button"
                                class="p-2 rounded-xl bg-teal-50 text-teal-800 hover:bg-teal-100 border border-teal-200 transition-colors"
                                title="Tugaskan / Buat Akun Pelatih"
                            >
                                <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                            </button>
                        </div>

                        <div v-if="activity.instructors?.length > 0" class="space-y-3">
                            <div
                                v-for="inst in activity.instructors"
                                :key="inst.id"
                                class="p-3.5 rounded-2xl bg-slate-50/80 border border-slate-200/80 flex items-center justify-between gap-3"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-teal-700 text-white flex items-center justify-center font-black text-xs shrink-0">
                                        {{ inst.user?.name?.charAt(0) || 'P' }}
                                    </div>
                                    <div>
                                        <div class="font-black text-slate-800 text-xs">{{ inst.user?.name }}</div>
                                        <div class="text-[11px] font-semibold text-teal-700">{{ inst.role_title }}</div>
                                        <div v-if="inst.institution" class="text-[10px] text-slate-400">{{ inst.institution }}</div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1">
                                    <a
                                        v-if="inst.user?.phone"
                                        :href="`https://wa.me/${inst.user.phone.replace(/[^0-9]/g, '')}`"
                                        target="_blank"
                                        class="p-2 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors"
                                        title="Chat WhatsApp"
                                    >
                                        <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                    </a>
                                    <button
                                        v-if="canManage"
                                        @click="unassignCoach(inst)"
                                        type="button"
                                        class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                        title="Hapus Penugasan"
                                    >
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-6 text-slate-400 text-xs">
                            Belum ada pelatih yang ditugaskan.
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: PENILAIAN RAPOR SEMESTER -->
            <div v-else-if="activeTab === 'grades'" class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-black text-slate-800">Penilaian Capaian Rapor Siswa</h2>
                        <p class="text-xs text-slate-500">
                            Masukkan predikat dan deskripsi naratif untuk dicetak pada Buku Rapor Semester.
                        </p>
                    </div>

                    <button
                        v-if="canManage || isInstructor"
                        @click="submitGrades"
                        :disabled="gradesForm.processing"
                        type="button"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs shadow-xs transition-all active:scale-95 disabled:opacity-50"
                    >
                        <CheckBadgeIcon class="w-4 h-4 stroke-[2.2]" />
                        <span>Simpan Seluruh Nilai</span>
                    </button>
                </div>

                <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden">
                    <div v-if="gradesForm.grades.length > 0" class="divide-y divide-slate-100">
                        <div
                            v-for="(item, idx) in gradesForm.grades"
                            :key="idx"
                            class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-slate-50/50 transition-colors"
                        >
                            <div class="w-full md:w-1/4">
                                <div class="font-black text-slate-800 text-sm">{{ item.student_name }}</div>
                                <div class="text-xs text-slate-400 font-semibold">{{ item.classroom_name }}</div>
                            </div>

                            <div class="w-full md:w-1/6">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Predikat</label>
                                <select
                                    v-model="item.predicate"
                                    @change="applyDescriptionTemplate(item)"
                                    class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold focus:ring-teal-500 bg-white"
                                >
                                    <option value="A">A - Sangat Baik</option>
                                    <option value="B">B - Baik</option>
                                    <option value="C">C - Cukup</option>
                                    <option value="D">D - Kurang</option>
                                </select>
                            </div>

                            <div class="flex-1 w-full">
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Deskripsi Capaian Rapor</label>
                                    <button
                                        @click="applyDescriptionTemplate(item)"
                                        type="button"
                                        class="text-[10px] text-teal-700 font-bold hover:underline"
                                    >
                                        Gunakan Template
                                    </button>
                                </div>
                                <input
                                    v-model="item.description"
                                    type="text"
                                    placeholder="Deskripsi capaian narasi..."
                                    class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                        </div>
                    </div>

                    <div v-else class="p-10 text-center text-slate-400 text-xs">
                        Tidak ada siswa terdaftar untuk dinilai.
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL 1: BUKA SESI LATIHAN & PRESENSI BARU -->
        <Teleport to="body">
            <div v-if="showSessionModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-3xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    
                    <div class="px-6 py-4 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-teal-100 text-teal-800 flex items-center justify-center font-bold">
                                <CameraIcon class="w-5 h-5 stroke-[2.2]" />
                            </div>
                            <div>
                                <h3 class="font-black text-slate-800 text-base sm:text-lg">
                                    Catat Pertemuan Latihan & Presensi
                                </h3>
                                <p class="text-xs text-slate-500">{{ activity.name }} - Sesi Kegiatan Lapangan</p>
                            </div>
                        </div>
                        <button @click="showSessionModal = false" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitSession" class="p-6 space-y-5 max-h-[78vh] overflow-y-auto">
                        
                        <!-- Tanggal & Jam -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Sesi *</label>
                                <input
                                    v-model="sessionForm.date"
                                    type="date"
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jam Mulai *</label>
                                <input
                                    v-model="sessionForm.start_time"
                                    type="time"
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jam Selesai *</label>
                                <input
                                    v-model="sessionForm.end_time"
                                    type="time"
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                        </div>

                        <!-- Topik Materi -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Topik / Materi Latihan *</label>
                            <input
                                v-model="sessionForm.topic"
                                type="text"
                                required
                                placeholder="Misal: Latihan Passing Cepat, Gerak Dasar Tari Piring, Merakit Sensor Ultrasonik..."
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                            />
                        </div>

                        <!-- Catatan Evaluasi -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Evaluasi / Hambatan Latihan</label>
                            <textarea
                                v-model="sessionForm.notes"
                                rows="2"
                                placeholder="Catatan capaian siswa atau kondisi latihan hari ini..."
                                class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                            ></textarea>
                        </div>

                        <!-- Upload Foto Dokumentasi (Canvas Auto-Compression) -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700">Foto Dokumentasi Lapangan (Otomatis Terkompresi)</label>
                            <div class="flex items-center gap-3">
                                <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-teal-50 text-teal-800 hover:bg-teal-100 font-bold text-xs border border-teal-200 transition-colors">
                                    <CameraIcon class="w-4 h-4" />
                                    <span>Ambil Foto Kamera / Galeri</span>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        multiple
                                        @change="handleSessionPhotos"
                                        class="hidden"
                                    />
                                </label>
                                <span v-if="isCompressing" class="text-xs text-teal-600 font-semibold animate-pulse">
                                    Mengompresi foto...
                                </span>
                            </div>

                            <!-- Preview Previews -->
                            <div v-if="sessionPhotoPreviews.length > 0" class="grid grid-cols-3 sm:grid-cols-4 gap-2 pt-2">
                                <div
                                    v-for="(p, pidx) in sessionPhotoPreviews"
                                    :key="pidx"
                                    class="relative h-20 rounded-xl overflow-hidden border border-slate-200 group"
                                >
                                    <img :src="p" class="w-full h-full object-cover" />
                                    <button
                                        @click="removeSessionPhoto(pidx)"
                                        type="button"
                                        class="absolute top-1 right-1 p-1 rounded-full bg-black/60 text-white hover:bg-rose-600 transition-colors"
                                    >
                                        <XMarkIcon class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Presensi Kehadiran Siswa Cepat -->
                        <div class="space-y-3 pt-3 border-t border-slate-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-black text-slate-800 text-xs uppercase tracking-wider">Presensi Kehadiran Siswa</h4>
                                    <p class="text-[11px] text-slate-400">Total {{ sessionForm.attendances.length }} siswa peserta.</p>
                                </div>
                                <button
                                    @click="markAllPresent"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-800 hover:bg-emerald-100 font-bold text-xs border border-emerald-200 transition-colors"
                                >
                                    <CheckBadgeIcon class="w-4 h-4" />
                                    <span>Tandai Semua Hadir</span>
                                </button>
                            </div>

                            <div class="max-h-60 overflow-y-auto space-y-2 divide-y divide-slate-100 pr-1">
                                <div
                                    v-for="att in sessionForm.attendances"
                                    :key="att.student_id"
                                    class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs"
                                >
                                    <div>
                                        <div class="font-bold text-slate-800">{{ att.student_name }}</div>
                                        <div class="text-[10px] text-slate-400">{{ att.classroom_name }}</div>
                                    </div>

                                    <div class="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="att.status = 'hadir'"
                                            class="px-2.5 py-1 rounded-lg font-black text-xs transition-colors border"
                                            :class="att.status === 'hadir' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-50 text-slate-600 border-slate-200'"
                                        >
                                            Hadir
                                        </button>
                                        <button
                                            type="button"
                                            @click="att.status = 'izin'"
                                            class="px-2.5 py-1 rounded-lg font-black text-xs transition-colors border"
                                            :class="att.status === 'izin' ? 'bg-sky-600 text-white border-sky-600' : 'bg-slate-50 text-slate-600 border-slate-200'"
                                        >
                                            Izin
                                        </button>
                                        <button
                                            type="button"
                                            @click="att.status = 'sakit'"
                                            class="px-2.5 py-1 rounded-lg font-black text-xs transition-colors border"
                                            :class="att.status === 'sakit' ? 'bg-amber-500 text-white border-amber-500' : 'bg-slate-50 text-slate-600 border-slate-200'"
                                        >
                                            Sakit
                                        </button>
                                        <button
                                            type="button"
                                            @click="att.status = 'alpha'"
                                            class="px-2.5 py-1 rounded-lg font-black text-xs transition-colors border"
                                            :class="att.status === 'alpha' ? 'bg-rose-600 text-white border-rose-600' : 'bg-slate-50 text-slate-600 border-slate-200'"
                                        >
                                            Alpha
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- WhatsApp Notification Option -->
                        <div class="p-3 rounded-2xl bg-teal-50/70 border border-teal-200/60 flex items-center gap-3">
                            <input
                                v-model="sessionForm.send_wa"
                                type="checkbox"
                                id="send_wa_cb"
                                class="w-4 h-4 text-teal-600 rounded-sm border-slate-300 focus:ring-teal-500"
                            />
                            <label for="send_wa_cb" class="text-xs font-semibold text-slate-700 cursor-pointer">
                                Kirimkan ringkasan notifikasi presensi otomatis melalui WhatsApp ke Orang Tua
                            </label>
                        </div>

                        <!-- Modal Actions -->
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                            <button
                                @click="showSessionModal = false"
                                type="button"
                                class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="sessionForm.processing || isCompressing"
                                class="px-6 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs shadow-xs disabled:opacity-50"
                            >
                                Simpan Sesi & Presensi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- MODAL 2: TAMBAH ANGGOTA SISWA -->
        <Teleport to="body">
            <div v-if="showAddMemberModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-black text-slate-800 text-base">Tambah Anggota Siswa</h3>
                        <button @click="showAddMemberModal = false" class="p-2 text-slate-400 hover:text-slate-600">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitMember" class="p-6 space-y-4">
                        <!-- Mode Selection -->
                        <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-2xl text-xs font-bold">
                            <button
                                type="button"
                                @click="memberForm.mode = 'single'"
                                class="py-2 rounded-xl transition-all"
                                :class="memberForm.mode === 'single' ? 'bg-white text-teal-800 shadow-xs' : 'text-slate-500'"
                            >
                                Pilih 1 Siswa
                            </button>
                            <button
                                type="button"
                                @click="memberForm.mode = 'classroom_bulk'"
                                class="py-2 rounded-xl transition-all"
                                :class="memberForm.mode === 'classroom_bulk' ? 'bg-white text-teal-800 shadow-xs' : 'text-slate-500'"
                            >
                                Masal 1 Kelas
                            </button>
                        </div>

                        <!-- Mode Classroom Bulk -->
                        <div v-if="memberForm.mode === 'classroom_bulk'">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Kelas *</label>
                            <select
                                v-model="memberForm.classroom_id"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-teal-500 bg-white"
                            >
                                <option value="">-- Pilih Kelas --</option>
                                <option v-for="c in classrooms" :key="c.id" :value="c.id">
                                    Kelas {{ c.name }}
                                </option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">
                                Seluruh siswa dalam kelas ini akan didaftarkan sekaligus ke ekstrakurikuler.
                            </p>
                        </div>

                        <!-- Mode Single Student -->
                        <div v-else>
                            <label class="block text-xs font-bold text-slate-700 mb-1">ID / Nama Siswa *</label>
                            <input
                                v-model="memberForm.student_id"
                                type="number"
                                required
                                placeholder="Masukkan ID Siswa..."
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                            />
                            <p class="text-[11px] text-slate-400 mt-1">
                                Untuk kemudahan pendaftaran perorangan atau gunakan mode masal kelas di atas.
                            </p>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                            <button @click="showAddMemberModal = false" type="button" class="px-5 py-2 rounded-xl border border-slate-200 text-xs font-bold">
                                Batal
                            </button>
                            <button type="submit" :disabled="memberForm.processing" class="px-6 py-2 rounded-xl bg-teal-700 text-white text-xs font-bold">
                                Tambahkan Anggota
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- MODAL 3: PENUGASAN / PEMBUATAN AKUN PELATIH -->
        <Teleport to="body">
            <div v-if="showAddCoachModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-black text-slate-800 text-base">Tugaskan / Buat Akun Pelatih</h3>
                        <button @click="showAddCoachModal = false" class="p-2 text-slate-400 hover:text-slate-600">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitCoach" class="p-6 space-y-4">
                        <!-- Mode Selection -->
                        <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-2xl text-xs font-bold">
                            <button
                                type="button"
                                @click="coachForm.mode = 'existing'"
                                class="py-2 rounded-xl transition-all"
                                :class="coachForm.mode === 'existing' ? 'bg-white text-teal-800 shadow-xs' : 'text-slate-500'"
                            >
                                Dari Guru / Pengguna Ada
                            </button>
                            <button
                                type="button"
                                @click="coachForm.mode = 'create_new'"
                                class="py-2 rounded-xl transition-all"
                                :class="coachForm.mode === 'create_new' ? 'bg-white text-teal-800 shadow-xs' : 'text-slate-500'"
                            >
                                Buat Akun Pelatih Baru
                            </button>
                        </div>

                        <!-- Mode Existing -->
                        <div v-if="coachForm.mode === 'existing'">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Guru / Pengguna *</label>
                            <select
                                v-model="coachForm.user_id"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-teal-500 bg-white"
                            >
                                <option value="">-- Pilih Guru / Staf --</option>
                                <option v-for="c in availableInstructors" :key="c.id" :value="c.id">
                                    {{ c.name }} ({{ c.phone || 'No HP -' }})
                                </option>
                            </select>
                        </div>

                        <!-- Mode Create New -->
                        <div v-else class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Pelatih *</label>
                                <input
                                    v-model="coachForm.name"
                                    type="text"
                                    required
                                    placeholder="Nama pelatih luar..."
                                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Email Akun Login *</label>
                                <input
                                    v-model="coachForm.email"
                                    type="email"
                                    required
                                    placeholder="pelatih@namira.school"
                                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp *</label>
                                    <input
                                        v-model="coachForm.phone"
                                        type="text"
                                        required
                                        placeholder="081234567890"
                                        class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Password Baru *</label>
                                    <input
                                        v-model="coachForm.password"
                                        type="password"
                                        required
                                        placeholder="Minimal 6 karakter"
                                        class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Common fields -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jabatan Penugasan</label>
                                <input
                                    v-model="coachForm.role_title"
                                    type="text"
                                    required
                                    placeholder="Pelatih Utama / Asisten"
                                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Asal Klub / Instansi</label>
                                <input
                                    v-model="coachForm.institution"
                                    type="text"
                                    placeholder="Sanggar Seni / Klub Futsal"
                                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                            <button @click="showAddCoachModal = false" type="button" class="px-5 py-2 rounded-xl border border-slate-200 text-xs font-bold">
                                Batal
                            </button>
                            <button type="submit" :disabled="coachForm.processing" class="px-6 py-2 rounded-xl bg-teal-700 text-white text-xs font-bold">
                                Tugaskan Pelatih
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- MODAL 4: ZOOM FOTO DOKUMENTASI -->
        <Teleport to="body">
            <div v-if="showZoomPhotoModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4" @click="showZoomPhotoModal = false">
                <div class="relative max-w-4xl max-h-[90vh] overflow-hidden rounded-2xl">
                    <img :src="zoomedPhotoUrl" class="w-full h-full object-contain max-h-[85vh] rounded-2xl" />
                    <button @click="showZoomPhotoModal = false" class="absolute top-3 right-3 p-2 rounded-full bg-black/60 text-white hover:bg-black">
                        <XMarkIcon class="w-6 h-6" />
                    </button>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
