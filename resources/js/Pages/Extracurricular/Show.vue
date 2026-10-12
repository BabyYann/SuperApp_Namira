<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
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
    ChevronRightIcon,
    ChatBubbleLeftRightIcon,
    DocumentTextIcon,
    ShieldCheckIcon,
    UserPlusIcon,
    CameraIcon,
    CheckBadgeIcon,
    FunnelIcon,
    EllipsisVerticalIcon,
    UserIcon,
    ChartBarIcon,
    FlagIcon,
    BuildingOfficeIcon,
    ExclamationTriangleIcon,
    CheckIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    activity: { type: Object, required: true },
    classrooms: { type: Array, default: () => [] },
    students: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] },
    availableInstructors: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
    isInstructor: { type: Boolean, default: false },
});

// Active Tab ('sessions' | 'members' | 'overview' | 'grades') - Default 'sessions' per reference
const activeTab = ref('sessions');

// Dropdown Action Menu (⋮)
const showActionMenu = ref(false);

// Modals
const showEditModal = ref(false);
const showAddMemberModal = ref(false);
const showAddCoachModal = ref(false);
const showSessionModal = ref(false);
const showZoomPhotoModal = ref(false);
const zoomedPhotoUrl = ref('');

// Search & filter members
const memberSearch = ref('');
const memberClassFilter = ref('all');
const memberModalClassFilter = ref('all');
const memberModalSearch = ref('');

// Forms
const memberForm = useForm({
    student_ids: [],
    student_id: '',
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
    start_time: props.activity.start_time ? props.activity.start_time.substring(0, 5) : '14:30',
    end_time: props.activity.end_time ? props.activity.end_time.substring(0, 5) : '16:00',
    topic: '',
    notes: '',
    photos: [],
    attendances: [],
    send_wa: true,
});

const gradesForm = useForm({
    grades: [],
});

const editForm = useForm({
    name: props.activity.name,
    category: props.activity.category,
    day_of_week: props.activity.day_of_week || 'Jumat',
    start_time: props.activity.start_time ? props.activity.start_time.substring(0, 5) : '14:30',
    end_time: props.activity.end_time ? props.activity.end_time.substring(0, 5) : '16:00',
    room_id: props.activity.room_id,
    location_name: props.activity.location_name || '',
    max_quota: props.activity.max_quota || 30,
    is_mandatory: props.activity.is_mandatory || false,
    gender_restriction: props.activity.gender_restriction || 'all',
    target_levels: props.activity.target_levels || [],
    description: props.activity.description || '',
    status: props.activity.status || 'active',
    cover_image: null,
});

// Location Type Selector for Edit Modal
const editLocationType = ref('outdoor');
const editOutdoorPreset = ref('');
const editSelectedClassroomId = ref('');

const outdoorPresets = [
    'Lapangan Utama',
    'Lapangan Futsal',
    'Lapangan Basket',
    'Halaman Sekolah',
    'Masjid Sekolah',
];

// Display helpers
const formattedActivityName = computed(() => {
    if (!props.activity.name) return '';
    const str = props.activity.name.trim();
    // If all uppercase (e.g. "FUTSAL"), convert to Title Case "Futsal"
    if (str === str.toUpperCase() && str.length > 2) {
        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
    }
    return str;
});

const quotaPercentage = computed(() => {
    const max = props.activity.max_quota || 0;
    const current = (props.activity.active_members || []).length;
    if (!max || max === 0) return 0;
    return Math.min(100, Math.round((current / max) * 100));
});

// REAL INSTRUCTORS FROM DATABASE (NO DUMMY DATA)
const leadCoach = computed(() => {
    const list = props.activity.instructors || [];
    return list.find((i) => i.is_primary) || list[0] || null;
});

const otherCoaches = computed(() => {
    const list = props.activity.instructors || [];
    const lead = leadCoach.value;
    if (!lead) return [];
    return list.filter((i) => i.id !== lead.id);
});

const formatTime = (timeStr) => {
    if (!timeStr) return '';
    return timeStr.substring(0, 5);
};

// Real location from DB
const locationTitle = computed(() => {
    if (props.activity.location_name) return props.activity.location_name;
    if (props.activity.room?.name) return props.activity.room.name;
    return 'Belum diatur';
});

const locationSubtitle = computed(() => {
    if (props.activity.room?.building) return props.activity.room.building;
    if (props.activity.room) return 'Ruang Sarpras';
    if (props.activity.location_name) return 'Area Sekolah';
    return '-';
});

// Enrolled student set & remaining quota
const enrolledStudentIds = computed(() => {
    return new Set((props.activity.active_members || []).map((m) => m.student_id));
});

const remainingQuota = computed(() => {
    const max = props.activity.max_quota || 0;
    const current = (props.activity.active_members || []).length;
    return Math.max(0, max - current);
});

const isGenderEligible = (gender) => {
    const restriction = props.activity.gender_restriction;
    if (!restriction || restriction === 'all') return true;
    if (restriction === 'male_only') return String(gender).toLowerCase() === 'l';
    if (restriction === 'female_only') return String(gender).toLowerCase() === 'p';
    return true;
};

const availableStudentsForModal = computed(() => {
    const list = props.students || [];
    return list.filter((s) => {
        if (memberModalClassFilter.value !== 'all' && String(s.classroom_id) !== String(memberModalClassFilter.value)) {
            return false;
        }
        if (memberModalSearch.value) {
            const q = memberModalSearch.value.toLowerCase().trim();
            const matches = (s.full_name && s.full_name.toLowerCase().includes(q)) || 
                            (s.nis && String(s.nis).includes(q)) || 
                            (s.nisn && String(s.nisn).includes(q));
            if (!matches) return false;
        }
        return true;
    });
});

const selectableStudentsInModal = computed(() => {
    return availableStudentsForModal.value.filter((s) => {
        return !enrolledStudentIds.value.has(s.id) && isGenderEligible(s.gender);
    });
});

const isAllModalStudentsSelected = computed(() => {
    const selectable = selectableStudentsInModal.value;
    if (selectable.length === 0) return false;
    return selectable.every((s) => memberForm.student_ids.includes(s.id));
});

const toggleSelectAllStudents = () => {
    const selectable = selectableStudentsInModal.value;
    if (isAllModalStudentsSelected.value) {
        const visibleIds = new Set(selectable.map((s) => s.id));
        memberForm.student_ids = memberForm.student_ids.filter((id) => !visibleIds.has(id));
    } else {
        const currentSelected = new Set(memberForm.student_ids);
        selectable.forEach((s) => {
            currentSelected.add(s.id);
        });
        memberForm.student_ids = Array.from(currentSelected);
    }
};

const toggleStudentSelection = (studentId) => {
    const idx = memberForm.student_ids.indexOf(studentId);
    if (idx > -1) {
        memberForm.student_ids.splice(idx, 1);
    } else {
        memberForm.student_ids.push(studentId);
    }
};

const openAddMemberModal = () => {
    memberForm.reset();
    memberForm.clearErrors();
    memberModalClassFilter.value = 'all';
    memberModalSearch.value = '';
    showAddMemberModal.value = true;
};

// Image Compression
const isCompressing = ref(false);
const sessionPhotoPreviews = ref([]);

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
    sessionForm.start_time = props.activity.start_time ? props.activity.start_time.substring(0, 5) : '14:30';
    sessionForm.end_time = props.activity.end_time ? props.activity.end_time.substring(0, 5) : '16:00';
    sessionPhotoPreviews.value = [];

    const members = props.activity.active_members || [];
    sessionForm.attendances = members.map((m) => ({
        student_id: m.student_id,
        student_name: m.student?.full_name || 'Siswa',
        classroom_name: m.student?.classroom?.name || '-',
        status: 'hadir',
        notes: '',
    }));

    showSessionModal.value = true;
};

const markAllPresent = () => {
    sessionForm.attendances.forEach((a) => {
        a.status = 'hadir';
    });
};

const submitSession = () => {
    sessionForm.post(route('extracurricular.sessions.store', props.activity.id), {
        preserveScroll: true,
        onSuccess: () => {
            showSessionModal.value = false;
            sessionForm.reset();
        },
    });
};

const submitMember = () => {
    memberForm.post(route('extracurricular.members.store', props.activity.id), {
        preserveScroll: true,
        onSuccess: () => {
            showAddMemberModal.value = false;
            memberForm.reset();
        },
    });
};

const submitCoach = () => {
    coachForm.post(route('extracurricular.instructors.store', props.activity.id), {
        preserveScroll: true,
        onSuccess: () => {
            showAddCoachModal.value = false;
            coachForm.reset();
        },
    });
};

const deleteMember = (member) => {
    if (confirm(`Hapus ${member.student?.full_name} dari anggota kegiatan ini?`)) {
        router.delete(route('extracurricular.members.destroy', [props.activity.id, member.id]), {
            preserveScroll: true,
        });
    }
};

const deleteSession = (session) => {
    const sessionDate = session.date ? new Date(session.date).toLocaleDateString('id-ID') : '';
    if (confirm(`Yakin ingin menghapus sesi kegiatan tanggal ${sessionDate} (${session.topic})? Presensi dan foto dokumentasi pada sesi ini akan terhapus.`)) {
        router.delete(route('extracurricular.sessions.destroy', [props.activity.id, session.id]), {
            preserveScroll: true,
        });
    }
};

const unassignCoach = (instructor) => {
    if (confirm(`Nonaktifkan pelatih ${instructor.user?.name} dari kegiatan ini?`)) {
        router.delete(route('extracurricular.instructors.destroy', [props.activity.id, instructor.id]), {
            preserveScroll: true,
        });
    }
};

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

const submitGrades = () => {
    gradesForm.post(route('extracurricular.grades.store', props.activity.id), {
        preserveScroll: true,
    });
};

// Edit Activity Modal Handlers
const openEditModal = () => {
    editForm.name = props.activity.name;
    editForm.category = props.activity.category;
    editForm.day_of_week = props.activity.day_of_week || 'Jumat';
    editForm.start_time = props.activity.start_time ? props.activity.start_time.substring(0, 5) : '14:30';
    editForm.end_time = props.activity.end_time ? props.activity.end_time.substring(0, 5) : '16:00';
    editForm.room_id = props.activity.room_id;
    editForm.location_name = props.activity.location_name || '';
    editForm.max_quota = props.activity.max_quota || 30;
    editForm.is_mandatory = props.activity.is_mandatory || false;
    editForm.gender_restriction = props.activity.gender_restriction || 'all';
    editForm.target_levels = props.activity.target_levels || [];
    editForm.description = props.activity.description || '';
    editForm.status = props.activity.status || 'active';
    editForm.cover_image = null;

    if (props.activity.room_id) {
        editLocationType.value = 'sarpras';
    } else if (props.activity.location_name) {
        const loc = props.activity.location_name;
        const matchingClass = props.classrooms.find(c => `Kelas ${c.name}` === loc || c.name === loc);
        if (matchingClass) {
            editLocationType.value = 'classroom';
            editSelectedClassroomId.value = matchingClass.id;
        } else if (outdoorPresets.includes(loc) || loc.toLowerCase().includes('lapangan') || loc.toLowerCase().includes('masjid') || loc.toLowerCase().includes('halaman')) {
            editLocationType.value = 'outdoor';
            editOutdoorPreset.value = outdoorPresets.includes(loc) ? loc : 'Lainnya';
        } else {
            editLocationType.value = 'external';
        }
    } else {
        editLocationType.value = 'outdoor';
        editOutdoorPreset.value = 'Lapangan Utama';
        editForm.location_name = 'Lapangan Utama';
    }

    showEditModal.value = true;
    showActionMenu.value = false;
};

const setEditLocationType = (type) => {
    editLocationType.value = type;
    if (type === 'outdoor') {
        editForm.room_id = null;
        if (!editOutdoorPreset.value) editOutdoorPreset.value = outdoorPresets[0];
        if (editOutdoorPreset.value !== 'Lainnya') {
            editForm.location_name = editOutdoorPreset.value;
        }
    } else if (type === 'classroom') {
        editForm.room_id = null;
        if (editSelectedClassroomId.value) {
            const cls = props.classrooms.find(c => c.id === editSelectedClassroomId.value);
            if (cls) editForm.location_name = `Kelas ${cls.name}`;
        }
    } else if (type === 'sarpras') {
        if (editForm.room_id) {
            const rm = props.rooms.find(r => r.id === editForm.room_id);
            if (rm) editForm.location_name = rm.name;
        }
    } else if (type === 'external') {
        editForm.room_id = null;
    }
};

const onSelectEditOutdoorPreset = (preset) => {
    editOutdoorPreset.value = preset;
    if (preset !== 'Lainnya') {
        editForm.location_name = preset;
    } else {
        editForm.location_name = '';
    }
};

const onSelectEditClassroom = () => {
    const cls = props.classrooms.find(c => c.id === editSelectedClassroomId.value);
    if (cls) {
        editForm.location_name = `Kelas ${cls.name}`;
    }
};

const onSelectEditRoom = () => {
    const rm = props.rooms.find(r => r.id === editForm.room_id);
    if (rm) {
        editForm.location_name = rm.name;
    }
};

const submitEdit = () => {
    editForm.put(route('extracurricular.update', props.activity.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};

const deleteActivity = () => {
    if (confirm(`Yakin ingin menghapus kegiatan ekstrakurikuler "${props.activity.name}"? Data anggota dan riwayat sesi juga akan terhapus.`)) {
        router.delete(route('extracurricular.destroy', props.activity.id));
    }
};

const zoomPhoto = (url) => {
    zoomedPhotoUrl.value = url;
    showZoomPhotoModal.value = true;
};
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="`${activity.name} - Kelola Ekstrakurikuler`" />

        <div class="space-y-3.5 pb-24 sm:pb-16 max-w-5xl mx-auto px-1 sm:px-3">
            
            <!-- 1. Top Header: Back Button & Breadcrumbs & Actions -->
            <div class="flex items-center justify-between pt-1">
                <div class="flex items-center gap-2.5">
                    <Link
                        :href="route('extracurricular.index')"
                        class="w-9 h-9 rounded-2xl bg-white border border-slate-200/80 shadow-2xs flex items-center justify-center text-slate-700 hover:text-teal-700 hover:border-teal-200 transition-all active:scale-95"
                    >
                        <ArrowLeftIcon class="w-4 h-4 stroke-[2.4]" />
                    </Link>
                    <div class="flex items-center gap-1.5 text-xs font-semibold">
                        <Link :href="route('extracurricular.index')" class="text-slate-500 hover:text-slate-700 transition-colors">
                            Ekstrakurikuler
                        </Link>
                        <span class="text-slate-300">/</span>
                        <span class="text-teal-700 font-bold truncate max-w-[150px]">
                            {{ formattedActivityName }}
                        </span>
                    </div>
                </div>

                <!-- More Options Menu (⋮) -->
                <div class="relative">
                    <button
                        @click="showActionMenu = !showActionMenu"
                        type="button"
                        class="w-9 h-9 rounded-2xl bg-white border border-slate-200/80 shadow-2xs flex items-center justify-center text-slate-600 hover:text-slate-800 transition-all active:scale-95"
                    >
                        <EllipsisVerticalIcon class="w-4 h-4 stroke-[2.4]" />
                    </button>

                    <!-- Dropdown Menu -->
                    <div
                        v-if="showActionMenu"
                        @click.outside="showActionMenu = false"
                        class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-slate-100 py-1.5 z-40 animate-in fade-in zoom-in-95 duration-150"
                    >
                        <button
                            v-if="canManage"
                            @click="openEditModal"
                            type="button"
                            class="w-full text-left px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2 transition-colors"
                        >
                            <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                            <span>Edit Ekstrakurikuler</span>
                        </button>
                        <button
                            v-if="canManage || isInstructor"
                            @click="openNewSessionModal(); showActionMenu = false"
                            type="button"
                            class="w-full text-left px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2 transition-colors"
                        >
                            <CameraIcon class="w-3.5 h-3.5 text-teal-600" />
                            <span>Catat Sesi Baru</span>
                        </button>
                        <button
                            v-if="canManage || isInstructor"
                            @click="openAddMemberModal(); showActionMenu = false"
                            type="button"
                            class="w-full text-left px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2 transition-colors"
                        >
                            <UserPlusIcon class="w-3.5 h-3.5 text-teal-600" />
                            <span>Tambah Anggota Siswa</span>
                        </button>
                        <button
                            v-if="canManage"
                            @click="showAddCoachModal = true; showActionMenu = false"
                            type="button"
                            class="w-full text-left px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2 transition-colors"
                        >
                            <UserIcon class="w-3.5 h-3.5 text-teal-600" />
                            <span>Tugaskan Pelatih</span>
                        </button>
                        <div v-if="canManage" class="my-1 border-t border-slate-100"></div>
                        <button
                            v-if="canManage"
                            @click="deleteActivity(); showActionMenu = false"
                            type="button"
                            class="w-full text-left px-3.5 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 flex items-center gap-2 transition-colors"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            <span>Hapus Kegiatan</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Hero Overview Card (Identical Proportion to Reference Mockup) -->
            <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-100 shadow-sm space-y-4">
                
                <!-- Card Header Row -->
                <div class="flex items-start justify-between gap-2.5">
                    <div class="flex items-start gap-3.5 min-w-0">
                        <!-- Activity Icon Box -->
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-teal-50/80 border border-teal-100/70 flex items-center justify-center text-teal-600 shrink-0 overflow-hidden shadow-2xs">
                            <img
                                v-if="activity.cover_image_url"
                                :src="activity.cover_image_url"
                                class="w-full h-full object-cover"
                                alt="Cover"
                            />
                            <!-- Dedicated Athletic Runner SVG for Sport / Futsal matching Mockup -->
                            <svg
                                v-else-if="activity.category === 'olahraga' || activity.name.toLowerCase().includes('futsal') || activity.name.toLowerCase().includes('bola') || activity.name.toLowerCase().includes('basket') || activity.name.toLowerCase().includes('badminton') || activity.name.toLowerCase().includes('renang')"
                                class="w-10 h-10 sm:w-12 sm:h-12 text-teal-600"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.6"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <circle cx="16" cy="4.5" r="2.2" fill="currentColor" stroke="none" />
                                <path d="M7 9.5l3.5-1 3 2.5" />
                                <path d="M15.5 11l3.5.5-2 3.5" />
                                <path d="M13.5 11l-2 4.5" />
                                <path d="M11.5 15.5l2.5 2.5 2-.5" />
                                <path d="M9.5 16l-3 2v3.5" />
                            </svg>
                            <UserGroupIcon v-else class="w-9 h-9 text-teal-600 stroke-[1.8]" />
                        </div>

                        <!-- Badges & Title -->
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 mb-1">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100/90 text-slate-600">
                                    <BuildingOfficeIcon class="w-3.5 h-3.5 text-slate-400 stroke-[2]" />
                                    <span>{{ activity.unit?.name || 'SD Namira' }}</span>
                                </span>
                            </div>

                            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                                {{ formattedActivityName }}
                            </h1>
                        </div>
                    </div>

                    <!-- Right Column: Status Badge & Edit Button -->
                    <div class="flex flex-col items-end gap-2 shrink-0">
                        <span
                            class="px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1.5 border"
                            :class="activity.status === 'active' || !activity.status ? 'bg-emerald-50 text-emerald-600 border-emerald-100/80' : 'bg-slate-100 text-slate-500 border-slate-200'"
                        >
                            <span class="w-2 h-2 rounded-full" :class="activity.status === 'active' || !activity.status ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                            <span>{{ activity.status === 'inactive' ? 'Nonaktif' : 'Aktif' }}</span>
                        </span>

                        <button
                            v-if="canManage"
                            @click="openEditModal"
                            type="button"
                            class="px-3.5 py-1.5 rounded-xl bg-white border border-slate-200/90 text-slate-700 hover:bg-slate-50 text-xs font-semibold flex items-center gap-1.5 shadow-2xs transition-colors"
                        >
                            <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                            <span>Edit</span>
                        </button>
                    </div>
                </div>

                <!-- Description Text -->
                <p class="text-xs sm:text-sm text-slate-500 leading-relaxed font-normal">
                    {{ activity.description || 'Belum ada deskripsi rinci untuk kegiatan ekstrakurikuler ini.' }}
                </p>

                <!-- 3 Info Items in 3 Distinct Cards (Matching Reference Mockup) -->
                <div class="grid grid-cols-3 gap-2 sm:gap-2.5 pt-1">
                    <!-- 1. Jadwal Latihan -->
                    <div class="bg-slate-50/70 rounded-2xl p-2.5 sm:p-3 border border-slate-100/80 flex items-center gap-2 sm:gap-2.5">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0 border border-teal-100/60">
                            <CalendarDaysIcon class="w-5 h-5 stroke-[2]" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] sm:text-[11px] text-slate-400 font-medium leading-none mb-1">Jadwal</div>
                            <div class="text-xs sm:text-sm font-bold text-slate-800 leading-tight truncate">
                                {{ activity.day_of_week || 'Belum diatur' }}
                            </div>
                            <div class="text-[10px] sm:text-[11px] text-slate-400 font-medium leading-none mt-1 truncate">
                                {{ activity.start_time && activity.end_time ? `${formatTime(activity.start_time)} - ${formatTime(activity.end_time)}` : '-' }}
                            </div>
                        </div>
                    </div>

                    <!-- 2. Lokasi -->
                    <div class="bg-slate-50/70 rounded-2xl p-2.5 sm:p-3 border border-slate-100/80 flex items-center gap-2 sm:gap-2.5">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center shrink-0 border border-rose-100/60">
                            <MapPinIcon class="w-5 h-5 stroke-[2]" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] sm:text-[11px] text-slate-400 font-medium leading-none mb-1">Lokasi</div>
                            <div class="text-xs sm:text-sm font-bold text-slate-800 leading-tight truncate">
                                {{ locationTitle }}
                            </div>
                            <div class="text-[10px] sm:text-[11px] text-slate-400 font-medium leading-none mt-1 truncate">
                                {{ locationSubtitle }}
                            </div>
                        </div>
                    </div>

                    <!-- 3. Kuota Siswa -->
                    <div class="bg-slate-50/70 rounded-2xl p-2.5 sm:p-3 border border-slate-100/80 flex items-center gap-2 sm:gap-2.5">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0 border border-teal-100/60">
                            <UserGroupIcon class="w-5 h-5 stroke-[2]" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[10px] sm:text-[11px] text-slate-400 font-medium leading-none mb-1">Kuota</div>
                            <div class="text-xs sm:text-sm font-bold text-slate-800 leading-tight truncate">
                                {{ activity.active_members?.length || 0 }} / {{ activity.max_quota || 0 }}
                            </div>
                            <div class="flex items-center gap-1.5 mt-1.5">
                                <div class="flex-1 h-1.5 bg-slate-200/70 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-teal-600 rounded-full transition-all duration-300"
                                        :style="{ width: `${quotaPercentage}%` }"
                                    ></div>
                                </div>
                                <span class="text-[9px] sm:text-[10px] font-bold text-slate-500 shrink-0">{{ quotaPercentage }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pelatih Section (REAL DATA ONLY - NO DUMMY DATA) -->
                <div class="pt-3.5 border-t border-slate-100 flex items-center justify-between gap-3">
                    
                    <!-- IF COACH EXISTS IN DB -->
                    <template v-if="leadCoach">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-full bg-slate-100 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center">
                                <img
                                    v-if="leadCoach.user?.profile_photo || leadCoach.user?.avatar"
                                    :src="leadCoach.user.profile_photo || leadCoach.user.avatar"
                                    class="w-full h-full object-cover"
                                    alt="Pelatih"
                                />
                                <div v-else class="w-full h-full bg-teal-700 text-white font-bold text-xs flex items-center justify-center">
                                    {{ leadCoach.user?.name ? leadCoach.user.name.charAt(0) : 'P' }}
                                </div>
                            </div>

                            <div class="min-w-0">
                                <div class="text-[10px] sm:text-[11px] text-slate-400 font-medium leading-tight">Pelatih Utama</div>
                                <div class="text-sm sm:text-base font-bold text-slate-800 truncate leading-tight mt-0.5">
                                    {{ leadCoach.user?.name || leadCoach.name }}
                                </div>
                                <div class="text-[10px] sm:text-[11px] font-semibold text-teal-600 truncate">
                                    {{ leadCoach.role_title || 'Pelatih Utama' }}
                                </div>
                            </div>
                        </div>

                        <!-- Real Other Coaches (Render ONLY if other coaches actually exist in database) -->
                        <div v-if="otherCoaches.length > 0" class="flex items-center gap-1.5 shrink-0">
                            <div class="flex items-center -space-x-1.5">
                                <div
                                    v-for="(coach, cidx) in otherCoaches.slice(0, 3)"
                                    :key="coach.id"
                                    class="w-7 h-7 rounded-full border border-white bg-slate-200 overflow-hidden flex items-center justify-center text-[9px] font-bold text-slate-700 shadow-2xs"
                                    :title="coach.user?.name"
                                >
                                    <img v-if="coach.user?.profile_photo" :src="coach.user.profile_photo" class="w-full h-full object-cover" />
                                    <span v-else>{{ coach.user?.name ? coach.user.name.charAt(0) : (cidx + 1) }}</span>
                                </div>

                                <button
                                    v-if="canManage"
                                    @click="showAddCoachModal = true"
                                    type="button"
                                    class="w-7 h-7 rounded-full border border-white bg-slate-100 hover:bg-teal-50 text-slate-600 hover:text-teal-700 flex items-center justify-center text-xs font-bold transition-colors shadow-2xs"
                                    title="Tugaskan Pelatih Lainnya"
                                >
                                    +
                                </button>
                            </div>

                            <button
                                @click="activeTab = 'overview'"
                                type="button"
                                class="text-[11px] font-semibold text-slate-600 hover:text-teal-700 flex items-center gap-0.5 ml-0.5"
                            >
                                <span>+ {{ otherCoaches.length }} Pelatih Lainnya</span>
                                <ChevronRightIcon class="w-3 h-3 stroke-[2.2]" />
                            </button>
                        </div>

                        <!-- No other coaches, offer quick add assistant button if admin -->
                        <div v-else-if="canManage" class="shrink-0">
                            <button
                                @click="showAddCoachModal = true"
                                type="button"
                                class="px-3 py-1.5 rounded-xl text-slate-500 hover:text-teal-700 hover:bg-teal-50 text-xs font-medium border border-slate-200 transition-colors flex items-center gap-1"
                            >
                                <PlusIcon class="w-3 h-3 stroke-[2.2]" />
                                <span>Tambah Asisten</span>
                            </button>
                        </div>
                    </template>

                    <!-- IF NO COACH SET IN DB (NO DUMMY NAMES) -->
                    <template v-else>
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-full bg-slate-100/80 border border-slate-200/70 flex items-center justify-center text-slate-400 shrink-0">
                                <UserIcon class="w-5 h-5 stroke-[1.8]" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-[10px] sm:text-[11px] text-slate-400 font-medium leading-tight">Pelatih Utama</div>
                                <div class="text-sm sm:text-base font-bold text-slate-500 italic leading-tight mt-0.5">Belum diatur</div>
                            </div>
                        </div>

                        <button
                            v-if="canManage"
                            @click="showAddCoachModal = true"
                            type="button"
                            class="px-4 py-2 sm:px-5 sm:py-2.5 rounded-2xl bg-teal-50/70 hover:bg-teal-100/70 text-teal-700 font-bold text-xs sm:text-sm border border-teal-200/80 shadow-2xs transition-colors flex items-center gap-1.5 shrink-0"
                        >
                            <PlusIcon class="w-4 h-4 stroke-[2.4]" />
                            <span>Tugaskan</span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- 3. Navigation Tabs (Grid 4 Columns matching Reference Mockup) -->
            <div class="border-b border-slate-200/80">
                <div class="grid grid-cols-4 w-full">
                    <button
                        @click="activeTab = 'sessions'"
                        type="button"
                        class="flex flex-col items-center justify-center py-2.5 sm:py-3 relative transition-all group"
                        :class="activeTab === 'sessions' ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
                    >
                        <CalendarDaysIcon class="w-5 h-5 stroke-[2]" />
                        <span class="text-[10px] sm:text-xs leading-tight mt-1 text-center truncate w-full px-1" :class="activeTab === 'sessions' ? 'font-bold' : 'font-medium'">
                            Jurnal & Presensi
                        </span>
                        <span
                            v-if="activeTab === 'sessions'"
                            class="absolute bottom-0 inset-x-2 sm:inset-x-4 h-1 bg-teal-700 rounded-full"
                        ></span>
                    </button>

                    <button
                        @click="activeTab = 'members'"
                        type="button"
                        class="flex flex-col items-center justify-center py-2.5 sm:py-3 relative transition-all group"
                        :class="activeTab === 'members' ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
                    >
                        <UserGroupIcon class="w-5 h-5 stroke-[2]" />
                        <span class="text-[10px] sm:text-xs leading-tight mt-1 text-center truncate w-full px-1" :class="activeTab === 'members' ? 'font-bold' : 'font-medium'">
                            Anggota Siswa
                        </span>
                        <span
                            v-if="activeTab === 'members'"
                            class="absolute bottom-0 inset-x-2 sm:inset-x-4 h-1 bg-teal-700 rounded-full"
                        ></span>
                    </button>

                    <button
                        @click="activeTab = 'overview'"
                        type="button"
                        class="flex flex-col items-center justify-center py-2.5 sm:py-3 relative transition-all group"
                        :class="activeTab === 'overview' ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
                    >
                        <UserIcon class="w-5 h-5 stroke-[2]" />
                        <span class="text-[10px] sm:text-xs leading-tight mt-1 text-center truncate w-full px-1" :class="activeTab === 'overview' ? 'font-bold' : 'font-medium'">
                            Profil & Pelatih
                        </span>
                        <span
                            v-if="activeTab === 'overview'"
                            class="absolute bottom-0 inset-x-2 sm:inset-x-4 h-1 bg-teal-700 rounded-full"
                        ></span>
                    </button>

                    <button
                        @click="activeTab = 'grades'; initGradesForm()"
                        type="button"
                        class="flex flex-col items-center justify-center py-2.5 sm:py-3 relative transition-all group"
                        :class="activeTab === 'grades' ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
                    >
                        <ChartBarIcon class="w-5 h-5 stroke-[2]" />
                        <span class="text-[10px] sm:text-xs leading-tight mt-1 text-center truncate w-full px-1" :class="activeTab === 'grades' ? 'font-bold' : 'font-medium'">
                            Penilaian Rapor
                        </span>
                        <span
                            v-if="activeTab === 'grades'"
                            class="absolute bottom-0 inset-x-2 sm:inset-x-4 h-1 bg-teal-700 rounded-full"
                        ></span>
                    </button>
                </div>
            </div>

            <!-- 4. TAB 1: JURNAL & PRESENSI -->
            <div v-if="activeTab === 'sessions'" class="space-y-3.5">
                
                <!-- Section Header -->
                <div class="flex items-center justify-between gap-3 pt-0.5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-teal-50 border border-teal-100/60 text-teal-600 flex items-center justify-center shrink-0">
                            <CalendarDaysIcon class="w-4 h-4 stroke-[2]" />
                        </div>
                        <div>
                            <h2 class="text-xs sm:text-sm font-bold text-slate-800">Riwayat Latihan & Pertemuan</h2>
                            <p class="text-[10px] text-slate-400 leading-tight">Seluruh sesi latihan yang telah diselesaikan beserta dokumentasi dan absensi siswa.</p>
                        </div>
                    </div>

                    <button
                        v-if="canManage || isInstructor"
                        @click="openNewSessionModal"
                        type="button"
                        class="inline-flex items-center gap-1 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-semibold text-xs shadow-xs transition-all active:scale-95 shrink-0"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.4]" />
                        <span>Sesi Baru</span>
                    </button>
                </div>

                <!-- Empty Sessions State (Identical to reference mockup) -->
                <div
                    v-if="!activity.sessions || activity.sessions.length === 0"
                    class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-sm flex flex-col items-center justify-center text-center mt-1"
                >
                    <!-- Mint Cloud Background with Calendar & Dots Illustration -->
                    <div class="relative w-36 h-28 flex items-center justify-center mb-1">
                        <!-- Soft Mint Cloud / Blob -->
                        <div class="absolute inset-0 bg-[#E8F6F3] rounded-[42px] transform rotate-3"></div>
                        <div class="absolute inset-1.5 bg-teal-50/80 rounded-[38px] transform -rotate-2"></div>
                        
                        <!-- Floating playful accent dots / leaves -->
                        <div class="absolute top-4 left-3 w-2 h-2 rounded-full bg-teal-400/80"></div>
                        <div class="absolute bottom-4 right-4 w-2 h-2 rounded-full bg-teal-500/80"></div>
                        <div class="absolute top-3 right-6 w-2.5 h-1.5 rounded-full bg-emerald-400/70 rotate-45"></div>

                        <!-- Calendar Graphic Box -->
                        <div class="relative z-10 w-16 h-16 rounded-2xl bg-white border-2 border-teal-600 shadow-md shadow-teal-700/10 flex flex-col items-center justify-center p-1.5">
                            <!-- Ring binders -->
                            <div class="w-full flex items-center justify-around pb-1.5 border-b border-teal-100">
                                <span class="w-1.5 h-2 rounded-full bg-teal-700"></span>
                                <span class="w-1.5 h-2 rounded-full bg-teal-700"></span>
                            </div>
                            <!-- Days dot grid -->
                            <div class="grid grid-cols-4 gap-1 w-full pt-1.5 px-0.5">
                                <span class="w-1 h-1 rounded-full bg-teal-600 mx-auto"></span>
                                <span class="w-1 h-1 rounded-full bg-teal-600 mx-auto"></span>
                                <span class="w-1 h-1 rounded-full bg-teal-600 mx-auto"></span>
                                <span class="w-1 h-1 rounded-full bg-teal-600 mx-auto"></span>
                                <span class="w-1 h-1 rounded-full bg-teal-600 mx-auto"></span>
                                <span class="w-1 h-1 rounded-full bg-teal-600 mx-auto"></span>
                                <span class="w-1 h-1 rounded-full bg-teal-600 mx-auto"></span>
                                <span class="w-1 h-1 rounded-full bg-teal-600 mx-auto"></span>
                            </div>
                        </div>
                    </div>

                    <h3 class="font-bold text-slate-800 text-sm sm:text-base mt-2">Belum Ada Sesi Latihan</h3>
                    <p class="text-[11px] sm:text-xs text-slate-400 max-w-xs mt-1 leading-relaxed">
                        Pelatih dapat mencatat pertemuan, presensi siswa, dan mengunggah dokumentasi latihan.
                    </p>

                    <button
                        v-if="canManage || isInstructor"
                        @click="openNewSessionModal"
                        type="button"
                        class="mt-4 inline-flex items-center gap-1.5 px-6 py-2.5 rounded-2xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs shadow-md shadow-teal-700/20 transition-all active:scale-95"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.4]" />
                        <span>Buat Sesi Latihan Pertama</span>
                    </button>
                </div>

                <!-- Session Cards List (When sessions exist) -->
                <div v-else class="space-y-3">
                    <div
                        v-for="session in activity.sessions"
                        :key="session.id"
                        class="bg-white rounded-3xl border border-slate-100 shadow-sm p-4 sm:p-5 space-y-3"
                    >
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-2.5 border-b border-slate-100">
                            <div>
                                <div class="flex items-center gap-2 text-[11px] font-semibold text-slate-500">
                                    <CalendarDaysIcon class="w-3.5 h-3.5 text-teal-600" />
                                    <span>{{ new Date(session.date).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</span>
                                    <span>•</span>
                                    <span>{{ formatTime(session.start_time) }} - {{ formatTime(session.end_time) }} WIB</span>
                                </div>
                                <h3 class="font-bold text-base text-slate-800 mt-1">
                                    {{ session.topic }}
                                </h3>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Dipandu oleh: <span class="font-semibold text-slate-700">{{ session.instructor?.name || 'Pelatih' }}</span>
                                </p>
                            </div>

                            <!-- Attendance summary pills & Action -->
                            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap justify-between sm:justify-end">
                                <div class="flex items-center gap-1 flex-wrap">
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        {{ session.attendees_summary?.hadir || 0 }} Hadir
                                    </span>
                                    <span v-if="session.attendees_summary?.izin > 0" class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                        {{ session.attendees_summary.izin }} Izin
                                    </span>
                                    <span v-if="session.attendees_summary?.sakit > 0" class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        {{ session.attendees_summary.sakit }} Sakit
                                    </span>
                                    <span v-if="session.attendees_summary?.alpha > 0" class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                        {{ session.attendees_summary.alpha }} Alpha
                                    </span>
                                </div>

                                <button
                                    v-if="canManage || isInstructor"
                                    @click="deleteSession(session)"
                                    type="button"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors shrink-0"
                                    title="Hapus Sesi Latihan"
                                >
                                    <TrashIcon class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Notes if any -->
                        <div v-if="session.notes" class="text-xs text-slate-600 bg-slate-50 rounded-2xl p-2.5 border border-slate-100 leading-relaxed">
                            <span class="font-bold text-slate-800">Catatan Evaluasi:</span> {{ session.notes }}
                        </div>

                        <!-- Photos Gallery -->
                        <div v-if="session.photo_urls?.length > 0" class="space-y-1.5">
                            <div class="text-[11px] font-bold text-slate-700 flex items-center gap-1">
                                <PhotoIcon class="w-3.5 h-3.5 text-teal-600" />
                                <span>Dokumentasi Kegiatan ({{ session.photo_urls.length }} Foto)</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <div
                                    v-for="(photo, pidx) in session.photo_urls"
                                    :key="pidx"
                                    @click="zoomPhoto(photo)"
                                    class="relative h-24 rounded-2xl overflow-hidden border border-slate-200 cursor-pointer group bg-slate-100"
                                >
                                    <img :src="photo" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                    <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[11px] font-bold">
                                        Perbesar
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Student Attendance List Accordion / Preview -->
                        <details class="text-xs group">
                            <summary class="font-bold text-teal-700 cursor-pointer hover:underline select-none flex items-center gap-1 text-[11px]">
                                <span>Lihat Rincian Kehadiran Siswa</span>
                                <ChevronDownIcon class="w-3.5 h-3.5 transition-transform group-open:rotate-180" />
                            </summary>
                            <div class="mt-2.5 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-1.5 pt-2 border-t border-slate-100">
                                <div
                                    v-for="att in session.attendances"
                                    :key="att.id"
                                    class="p-2 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between gap-2"
                                >
                                    <div class="truncate">
                                        <div class="font-bold text-slate-800 text-[11px] truncate">{{ att.student?.full_name || 'Siswa' }}</div>
                                        <div v-if="att.notes" class="text-[9px] text-slate-500 italic truncate">{{ att.notes }}</div>
                                    </div>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider shrink-0"
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
            </div>

            <!-- 5. TAB 2: ANGGOTA SISWA -->
            <div v-else-if="activeTab === 'members'" class="space-y-3.5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-teal-50 border border-teal-100/60 text-teal-600 flex items-center justify-center shrink-0">
                            <UserGroupIcon class="w-4 h-4 stroke-[2]" />
                        </div>
                        <div>
                            <h2 class="text-xs sm:text-sm font-bold text-slate-800">Daftar Anggota Siswa</h2>
                            <p class="text-[10px] text-slate-400">
                                Terisi {{ activity.active_members?.length || 0 }} dari kuota {{ activity.max_quota }} siswa.
                            </p>
                        </div>
                    </div>

                    <button
                        v-if="canManage || isInstructor"
                        @click="openAddMemberModal"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs shadow-xs transition-all active:scale-95"
                    >
                        <UserPlusIcon class="w-3.5 h-3.5 stroke-[2.4]" />
                        <span>Tambah Anggota Siswa</span>
                    </button>
                </div>

                <!-- Search & Class Filter -->
                <div class="bg-white rounded-2xl p-2.5 border border-slate-100 shadow-2xs flex flex-col sm:flex-row gap-2.5 items-center justify-between">
                    <div class="relative w-full sm:w-80">
                        <MagnifyingGlassIcon class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="memberSearch"
                            type="text"
                            placeholder="Cari nama siswa atau NISN..."
                            class="w-full pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-teal-500 focus:border-teal-500"
                        />
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <label class="text-[11px] font-semibold text-slate-500 shrink-0">Filter Kelas:</label>
                        <select
                            v-model="memberClassFilter"
                            class="px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-teal-500 bg-white"
                        >
                            <option value="all">Semua Kelas</option>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Members Table / Cards -->
                <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                    <div v-if="filteredMembers.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/80 border-b border-slate-100 text-slate-500 uppercase tracking-wider font-extrabold text-[10px]">
                                <tr>
                                    <th class="py-2.5 px-3.5">Nama Siswa</th>
                                    <th class="py-2.5 px-3.5">NISN / NIS</th>
                                    <th class="py-2.5 px-3.5">Kelas</th>
                                    <th class="py-2.5 px-3.5">Tanggal Gabung</th>
                                    <th class="py-2.5 px-3.5">Status</th>
                                    <th v-if="canManage || isInstructor" class="py-2.5 px-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <tr v-for="member in filteredMembers" :key="member.id" class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-3 px-3.5 font-bold text-slate-900 flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-xs shrink-0">
                                            {{ member.student?.full_name?.charAt(0) || 'S' }}
                                        </div>
                                        <span>{{ member.student?.full_name }}</span>
                                    </td>
                                    <td class="py-3 px-3.5 font-mono text-slate-500 text-[11px]">
                                        {{ member.student?.nisn || member.student?.nis || '-' }}
                                    </td>
                                    <td class="py-3 px-3.5 font-semibold text-slate-800">
                                        {{ member.student?.classroom?.name || '-' }}
                                    </td>
                                    <td class="py-3 px-3.5 text-slate-500 text-[11px]">
                                        {{ member.joined_date || '-' }}
                                    </td>
                                    <td class="py-3 px-3.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800">
                                            {{ member.status }}
                                        </span>
                                    </td>
                                    <td v-if="canManage || isInstructor" class="py-3 px-3.5 text-right">
                                        <button
                                            @click="deleteMember(member)"
                                            type="button"
                                            class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                            title="Hapus dari Ekskul"
                                        >
                                            <TrashIcon class="w-3.5 h-3.5" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="p-6 text-center text-slate-400 text-xs">
                        Tidak ada siswa yang sesuai dengan filter pencarian.
                    </div>
                </div>
            </div>

            <!-- 6. TAB 3: PROFIL & PELATIH -->
            <div v-else-if="activeTab === 'overview'" class="space-y-4">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    
                    <!-- Left: Detail Ekskul -->
                    <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-100 shadow-sm p-5 space-y-3.5">
                        <h2 class="text-sm font-bold text-slate-800">Tentang Ekstrakurikuler</h2>
                        <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">
                            {{ activity.description || 'Belum ada deskripsi rinci untuk kegiatan ekstrakurikuler ini.' }}
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-3 border-t border-slate-100 text-xs">
                            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 space-y-0.5">
                                <span class="text-slate-400 text-[10px] font-semibold">Jadwal Rutin:</span>
                                <div class="font-bold text-slate-800">{{ activity.day_of_week ? `Setiap ${activity.day_of_week}, ${formatTime(activity.start_time)} - ${formatTime(activity.end_time)} WIB` : 'Belum diatur' }}</div>
                            </div>
                            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 space-y-0.5">
                                <span class="text-slate-400 text-[10px] font-semibold">Tempat / Ruangan:</span>
                                <div class="font-bold text-slate-800">{{ locationTitle }} ({{ locationSubtitle }})</div>
                            </div>
                            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 space-y-0.5">
                                <span class="text-slate-400 text-[10px] font-semibold">Batasan Gender:</span>
                                <div class="font-bold text-slate-800">
                                    {{ activity.gender_restriction === 'all' ? 'Semua Siswa' : (activity.gender_restriction === 'male_only' ? 'Khusus Putra' : 'Khusus Putri') }}
                                </div>
                            </div>
                            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 space-y-0.5">
                                <span class="text-slate-400 text-[10px] font-semibold">Kapasitas Maksimal:</span>
                                <div class="font-bold text-slate-800">{{ activity.max_quota }} Siswa</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Daftar Pelatih / Pembina (Real DB Data) -->
                    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 space-y-3.5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-sm font-bold text-slate-800">Pelatih & Pembina</h2>
                                <p class="text-[10px] text-slate-400">Pengampu resmi kegiatan.</p>
                            </div>

                            <button
                                v-if="canManage"
                                @click="showAddCoachModal = true"
                                type="button"
                                class="p-1.5 rounded-xl bg-teal-50 text-teal-800 hover:bg-teal-100 border border-teal-200 transition-colors"
                                title="Tugaskan / Buat Akun Pelatih"
                            >
                                <PlusIcon class="w-3.5 h-3.5 stroke-[2.4]" />
                            </button>
                        </div>

                        <div v-if="activity.instructors?.length > 0" class="space-y-2.5">
                            <div
                                v-for="inst in activity.instructors"
                                :key="inst.id"
                                class="p-3 rounded-2xl bg-slate-50/80 border border-slate-200/80 flex items-center justify-between gap-2.5"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-teal-700 text-white flex items-center justify-center font-bold text-xs shrink-0 overflow-hidden">
                                        <img v-if="inst.user?.profile_photo" :src="inst.user.profile_photo" class="w-full h-full object-cover" />
                                        <span v-else>{{ inst.user?.name?.charAt(0) || 'P' }}</span>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800 text-xs">{{ inst.user?.name }}</div>
                                        <div class="text-[10px] font-semibold text-teal-700">{{ inst.role_title }}</div>
                                        <div v-if="inst.institution" class="text-[9px] text-slate-400">{{ inst.institution }}</div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1">
                                    <a
                                        v-if="inst.user?.phone"
                                        :href="`https://wa.me/${inst.user.phone.replace(/[^0-9]/g, '')}`"
                                        target="_blank"
                                        class="p-1.5 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors"
                                        title="Chat WhatsApp"
                                    >
                                        <ChatBubbleLeftRightIcon class="w-3.5 h-3.5" />
                                    </a>
                                    <button
                                        v-if="canManage"
                                        @click="unassignCoach(inst)"
                                        type="button"
                                        class="p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                        title="Hapus Penugasan"
                                    >
                                        <TrashIcon class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-5 text-slate-400 text-xs">
                            Belum ada pelatih yang ditugaskan.
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7. TAB 4: PENILAIAN RAPOR SEMESTER -->
            <div v-else-if="activeTab === 'grades'" class="space-y-3.5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-teal-50 border border-teal-100/60 text-teal-600 flex items-center justify-center shrink-0">
                            <ChartBarIcon class="w-4 h-4 stroke-[2]" />
                        </div>
                        <div>
                            <h2 class="text-xs sm:text-sm font-bold text-slate-800">Penilaian Capaian Rapor Siswa</h2>
                            <p class="text-[10px] text-slate-400">
                                Masukkan predikat dan deskripsi naratif untuk dicetak pada Buku Rapor Semester.
                            </p>
                        </div>
                    </div>

                    <button
                        v-if="canManage || isInstructor"
                        @click="submitGrades"
                        :disabled="gradesForm.processing"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs shadow-xs transition-all active:scale-95 disabled:opacity-50"
                    >
                        <CheckBadgeIcon class="w-3.5 h-3.5 stroke-[2.2]" />
                        <span>Simpan Seluruh Nilai</span>
                    </button>
                </div>

                <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                    <div v-if="gradesForm.grades.length > 0" class="divide-y divide-slate-100">
                        <div
                            v-for="(item, idx) in gradesForm.grades"
                            :key="idx"
                            class="p-3.5 sm:p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 hover:bg-slate-50/50 transition-colors"
                        >
                            <div class="w-full md:w-1/4">
                                <div class="font-bold text-slate-800 text-xs">{{ item.student_name }}</div>
                                <div class="text-[10px] text-slate-400">{{ item.classroom_name }}</div>
                            </div>

                            <div class="w-full md:w-1/6">
                                <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Predikat</label>
                                <select
                                    v-model="item.predicate"
                                    @change="applyDescriptionTemplate(item)"
                                    class="w-full px-2.5 py-1 rounded-xl border border-slate-200 text-xs font-bold focus:ring-teal-500 bg-white"
                                >
                                    <option value="A">A - Sangat Baik</option>
                                    <option value="B">B - Baik</option>
                                    <option value="C">C - Cukup</option>
                                    <option value="D">D - Kurang</option>
                                </select>
                            </div>

                            <div class="flex-1 w-full">
                                <div class="flex items-center justify-between mb-0.5">
                                    <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Deskripsi Capaian Rapor</label>
                                    <button
                                        @click="applyDescriptionTemplate(item)"
                                        type="button"
                                        class="text-[9px] text-teal-700 font-bold hover:underline"
                                    >
                                        Gunakan Template
                                    </button>
                                </div>
                                <input
                                    v-model="item.description"
                                    type="text"
                                    placeholder="Deskripsi capaian narasi..."
                                    class="w-full px-2.5 py-1 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                        </div>
                    </div>

                    <div v-else class="p-8 text-center text-slate-400 text-xs">
                        Tidak ada siswa terdaftar untuk dinilai.
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL 1: EDIT EKSTRAKURIKULER (DENGAN 4-TIER LOCATION SELECTOR) -->
        <Teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div class="px-5 py-3.5 bg-slate-50/90 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-2xl bg-teal-100 text-teal-800 flex items-center justify-center font-bold">
                                <PencilSquareIcon class="w-4 h-4 stroke-[2.2]" />
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-sm">Edit Informasi Ekstrakurikuler</h3>
                                <p class="text-[11px] text-slate-500">Perbarui rincian kegiatan, jadwal, dan lokasi</p>
                            </div>
                        </div>
                        <button @click="showEditModal = false" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitEdit" class="p-5 space-y-3.5 max-h-[80vh] overflow-y-auto">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kegiatan *</label>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kategori *</label>
                                <select
                                    v-model="editForm.category"
                                    required
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500 bg-white"
                                >
                                    <option value="olahraga">Olahraga & Atletik</option>
                                    <option value="seni_budaya">Seni & Budaya</option>
                                    <option value="keagamaan">Keagamaan & Karakter</option>
                                    <option value="sains_teknologi">Sains & Teknologi</option>
                                    <option value="kepanduan">Kepanduan & Bela Diri</option>
                                    <option value="lainnya">Peminatan Lainnya</option>
                                </select>
                            </div>
                        </div>

                        <!-- Jadwal: Hari & Jam -->
                        <div class="grid grid-cols-3 gap-2.5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Hari Rutin *</label>
                                <select
                                    v-model="editForm.day_of_week"
                                    required
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500 bg-white"
                                >
                                    <option value="Senin">Senin</option>
                                    <option value="Selasa">Selasa</option>
                                    <option value="Rabu">Rabu</option>
                                    <option value="Kamis">Kamis</option>
                                    <option value="Jumat">Jumat</option>
                                    <option value="Sabtu">Sabtu</option>
                                    <option value="Minggu">Minggu</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jam Mulai *</label>
                                <input
                                    v-model="editForm.start_time"
                                    type="time"
                                    required
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jam Selesai *</label>
                                <input
                                    v-model="editForm.end_time"
                                    type="time"
                                    required
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                        </div>

                        <!-- 4-TIER LOCATION SELECTOR -->
                        <div class="p-3.5 rounded-2xl bg-teal-50/50 border border-teal-100 space-y-2.5">
                            <label class="block text-xs font-bold text-teal-900">
                                Lokasi Pelaksanaan Kegiatan *
                            </label>

                            <!-- Pilihan Kategori Lokasi -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <button
                                    type="button"
                                    @click="setEditLocationType('outdoor')"
                                    class="px-2 py-2 rounded-xl text-[11px] font-bold border transition-all text-center flex flex-col items-center gap-1"
                                    :class="editLocationType === 'outdoor' ? 'bg-teal-700 text-white border-teal-700 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                >
                                    <FlagIcon class="w-4 h-4" />
                                    <span>Outdoor</span>
                                </button>
                                <button
                                    type="button"
                                    @click="setEditLocationType('classroom')"
                                    class="px-2 py-2 rounded-xl text-[11px] font-bold border transition-all text-center flex flex-col items-center gap-1"
                                    :class="editLocationType === 'classroom' ? 'bg-teal-700 text-white border-teal-700 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                >
                                    <AcademicCapIcon class="w-4 h-4" />
                                    <span>Kelas Unit</span>
                                </button>
                                <button
                                    type="button"
                                    @click="setEditLocationType('sarpras')"
                                    class="px-2 py-2 rounded-xl text-[11px] font-bold border transition-all text-center flex flex-col items-center gap-1"
                                    :class="editLocationType === 'sarpras' ? 'bg-teal-700 text-white border-teal-700 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                >
                                    <BuildingOfficeIcon class="w-4 h-4" />
                                    <span>Sarpras</span>
                                </button>
                                <button
                                    type="button"
                                    @click="setEditLocationType('external')"
                                    class="px-2 py-2 rounded-xl text-[11px] font-bold border transition-all text-center flex flex-col items-center gap-1"
                                    :class="editLocationType === 'external' ? 'bg-teal-700 text-white border-teal-700 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                >
                                    <MapPinIcon class="w-4 h-4" />
                                    <span>Luar Kampus</span>
                                </button>
                            </div>

                            <!-- Opsi 1: Outdoor Presets -->
                            <div v-if="editLocationType === 'outdoor'" class="space-y-1.5">
                                <div class="flex flex-wrap gap-1.5">
                                    <button
                                        v-for="preset in outdoorPresets"
                                        :key="preset"
                                        type="button"
                                        @click="onSelectEditOutdoorPreset(preset)"
                                        class="px-2.5 py-1 rounded-lg text-xs font-medium border transition-colors"
                                        :class="editOutdoorPreset === preset ? 'bg-teal-600 text-white border-teal-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                    >
                                        {{ preset }}
                                    </button>
                                </div>
                            </div>

                            <!-- Opsi 2: Kelas Unit -->
                            <div v-else-if="editLocationType === 'classroom'">
                                <select
                                    v-model="editSelectedClassroomId"
                                    @change="onSelectEditClassroom"
                                    class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs bg-white focus:ring-teal-500"
                                >
                                    <option value="">-- Pilih Ruang Kelas Unit --</option>
                                    <option v-for="c in classrooms" :key="c.id" :value="c.id">
                                        Kelas {{ c.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Opsi 3: Ruang Sarpras -->
                            <div v-else-if="editLocationType === 'sarpras'">
                                <select
                                    v-model="editForm.room_id"
                                    @change="onSelectEditRoom"
                                    class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs bg-white focus:ring-teal-500"
                                >
                                    <option :value="null">-- Pilih Ruangan Sarpras --</option>
                                    <option v-for="r in rooms" :key="r.id" :value="r.id">
                                        {{ r.name }} {{ r.building ? `(${r.building})` : '' }}
                                    </option>
                                </select>
                            </div>

                            <!-- Opsi 4: Luar Sekolah -->
                            <div v-else-if="editLocationType === 'external'">
                                <input
                                    v-model="editForm.location_name"
                                    type="text"
                                    placeholder="Misal: Kolam Renang Tirta Indah, GOR Futsal..."
                                    class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs bg-white focus:ring-teal-500"
                                />
                            </div>
                        </div>

                        <!-- Kuota & Gender -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kapasitas Kuota *</label>
                                <input
                                    v-model.number="editForm.max_quota"
                                    type="number"
                                    min="1"
                                    max="500"
                                    required
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Batasan Gender *</label>
                                <select
                                    v-model="editForm.gender_restriction"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500 bg-white"
                                >
                                    <option value="all">Semua Siswa (Campur)</option>
                                    <option value="male_only">Khusus Putra (Ikhwan)</option>
                                    <option value="female_only">Khusus Putri (Akhwat)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Status & Mandatory -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Status Kegiatan *</label>
                                <select
                                    v-model="editForm.status"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500 bg-white"
                                >
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Nonaktif</option>
                                </select>
                            </div>
                            <div class="flex items-center pt-5">
                                <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                                    <input
                                        v-model="editForm.is_mandatory"
                                        type="checkbox"
                                        class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300"
                                    />
                                    <span>Ekstrakurikuler Wajib</span>
                                </label>
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Kegiatan</label>
                            <textarea
                                v-model="editForm.description"
                                rows="3"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                            ></textarea>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                            <button
                                @click="showEditModal = false"
                                type="button"
                                class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="px-5 py-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs shadow-xs disabled:opacity-50"
                            >
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- MODAL 2: BUKA SESI LATIHAN & PRESENSI BARU -->
        <Teleport to="body">
            <div v-if="showSessionModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-3xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div class="px-5 py-3.5 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-2xl bg-teal-100 text-teal-800 flex items-center justify-center font-bold">
                                <CameraIcon class="w-4 h-4 stroke-[2.2]" />
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">
                                    Catat Pertemuan Latihan & Presensi
                                </h3>
                                <p class="text-xs text-slate-500">{{ formattedActivityName }} - Sesi Kegiatan Lapangan</p>
                            </div>
                        </div>
                        <button @click="showSessionModal = false" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitSession" class="p-5 space-y-4 max-h-[78vh] overflow-y-auto">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Sesi *</label>
                                <input
                                    v-model="sessionForm.date"
                                    type="date"
                                    required
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jam Mulai *</label>
                                <input
                                    v-model="sessionForm.start_time"
                                    type="time"
                                    required
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jam Selesai *</label>
                                <input
                                    v-model="sessionForm.end_time"
                                    type="time"
                                    required
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Topik / Materi Latihan *</label>
                            <input
                                v-model="sessionForm.topic"
                                type="text"
                                required
                                placeholder="Misal: Latihan Passing Cepat, Gerak Dasar..."
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Evaluasi / Hambatan Latihan</label>
                            <textarea
                                v-model="sessionForm.notes"
                                rows="2"
                                placeholder="Catatan capaian siswa atau kondisi latihan hari ini..."
                                class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                            ></textarea>
                        </div>

                        <!-- Upload Foto Dokumentasi -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Foto Dokumentasi Lapangan (Otomatis Terkompresi)</label>
                            <div class="flex items-center gap-2.5">
                                <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-teal-50 text-teal-800 hover:bg-teal-100 font-bold text-xs border border-teal-200 transition-colors">
                                    <CameraIcon class="w-3.5 h-3.5" />
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

                            <div v-if="sessionPhotoPreviews.length > 0" class="grid grid-cols-3 sm:grid-cols-4 gap-2 pt-1.5">
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
                                        <XMarkIcon class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Presensi Siswa -->
                        <div class="space-y-2.5 pt-2 border-t border-slate-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-bold text-slate-800 text-xs uppercase tracking-wider">Presensi Kehadiran Siswa</h4>
                                    <p class="text-[10px] text-slate-400">Total {{ sessionForm.attendances.length }} siswa peserta.</p>
                                </div>
                                <button
                                    @click="markAllPresent"
                                    type="button"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-800 hover:bg-emerald-100 font-bold text-xs border border-emerald-200 transition-colors"
                                >
                                    <CheckBadgeIcon class="w-3.5 h-3.5" />
                                    <span>Tandai Semua Hadir</span>
                                </button>
                            </div>

                            <div class="max-h-56 overflow-y-auto space-y-2 divide-y divide-slate-100 pr-1">
                                <div
                                    v-for="att in sessionForm.attendances"
                                    :key="att.student_id"
                                    class="pt-1.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 text-xs"
                                >
                                    <div>
                                        <div class="font-bold text-slate-800 text-xs">{{ att.student_name }}</div>
                                        <div class="text-[10px] text-slate-400">{{ att.classroom_name }}</div>
                                    </div>

                                    <div class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            @click="att.status = 'hadir'"
                                            class="px-2 py-0.5 rounded-lg font-bold text-xs transition-colors border"
                                            :class="att.status === 'hadir' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-50 text-slate-600 border-slate-200'"
                                        >
                                            Hadir
                                        </button>
                                        <button
                                            type="button"
                                            @click="att.status = 'izin'"
                                            class="px-2 py-0.5 rounded-lg font-bold text-xs transition-colors border"
                                            :class="att.status === 'izin' ? 'bg-sky-600 text-white border-sky-600' : 'bg-slate-50 text-slate-600 border-slate-200'"
                                        >
                                            Izin
                                        </button>
                                        <button
                                            type="button"
                                            @click="att.status = 'sakit'"
                                            class="px-2 py-0.5 rounded-lg font-bold text-xs transition-colors border"
                                            :class="att.status === 'sakit' ? 'bg-amber-500 text-white border-amber-500' : 'bg-slate-50 text-slate-600 border-slate-200'"
                                        >
                                            Sakit
                                        </button>
                                        <button
                                            type="button"
                                            @click="att.status = 'alpha'"
                                            class="px-2 py-0.5 rounded-lg font-bold text-xs transition-colors border"
                                            :class="att.status === 'alpha' ? 'bg-rose-600 text-white border-rose-600' : 'bg-slate-50 text-slate-600 border-slate-200'"
                                        >
                                            Alpha
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- WhatsApp Notification Option -->
                        <div class="p-2.5 rounded-2xl bg-teal-50/70 border border-teal-200/60 flex items-center gap-2.5">
                            <input
                                v-model="sessionForm.send_wa"
                                type="checkbox"
                                id="send_wa_cb"
                                class="w-3.5 h-3.5 text-teal-600 rounded-sm border-slate-300 focus:ring-teal-500"
                            />
                            <label for="send_wa_cb" class="text-xs font-semibold text-slate-700 cursor-pointer">
                                Kirimkan ringkasan presensi melalui WhatsApp ke Orang Tua
                            </label>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                            <button
                                @click="showSessionModal = false"
                                type="button"
                                class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="sessionForm.processing || isCompressing"
                                class="px-5 py-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs shadow-xs disabled:opacity-50"
                            >
                                Simpan Sesi & Presensi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- MODAL 3: TAMBAH ANGGOTA SISWA -->
        <Teleport to="body">
            <div v-if="showAddMemberModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-slate-800 text-base">Pendaftaran Anggota Siswa</h3>
                            <p class="text-xs text-slate-500">
                                Sisa Kuota: <strong class="text-teal-700">{{ remainingQuota }}</strong> dari {{ activity.max_quota }} kursi
                            </p>
                        </div>
                        <button @click="showAddMemberModal = false" class="p-1.5 text-slate-400 hover:text-slate-600">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitMember" class="p-5 space-y-3.5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Filter Rombel / Kelas</label>
                                <select
                                    v-model="memberModalClassFilter"
                                    class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-teal-500 bg-white"
                                >
                                    <option value="all">Semua Kelas</option>
                                    <option v-for="c in classrooms" :key="c.id" :value="c.id">
                                        Kelas {{ c.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Pencarian Siswa</label>
                                <div class="relative">
                                    <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" />
                                    <input
                                        v-model="memberModalSearch"
                                        type="text"
                                        placeholder="Ketik nama atau NIS..."
                                        class="w-full pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl border border-slate-100 text-xs">
                            <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                <input
                                    type="checkbox"
                                    :checked="isAllModalStudentsSelected"
                                    :disabled="selectableStudentsInModal.length === 0"
                                    @change="toggleSelectAllStudents"
                                    class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300"
                                />
                                <span>Pilih Semua yang Tampil ({{ selectableStudentsInModal.length }})</span>
                            </label>

                            <span class="font-bold px-2 py-0.5 rounded-full text-[10px]" :class="memberForm.student_ids.length > 0 ? 'bg-teal-100 text-teal-800' : 'bg-slate-200 text-slate-600'">
                                {{ memberForm.student_ids.length }} Dipilih
                            </span>
                        </div>

                        <div class="max-h-56 overflow-y-auto space-y-1.5 border border-slate-100 rounded-2xl p-2 bg-slate-50/50">
                            <div
                                v-for="s in availableStudentsForModal"
                                :key="s.id"
                                @click="!enrolledStudentIds.has(s.id) && isGenderEligible(s.gender) && toggleStudentSelection(s.id)"
                                class="flex items-center justify-between p-2 rounded-xl border transition-all select-none"
                                :class="[
                                    enrolledStudentIds.has(s.id)
                                        ? 'bg-slate-100 border-slate-200 opacity-60 cursor-not-allowed'
                                        : !isGenderEligible(s.gender)
                                        ? 'bg-rose-50/50 border-rose-100 opacity-60 cursor-not-allowed'
                                        : memberForm.student_ids.includes(s.id)
                                        ? 'bg-teal-50 border-teal-300 text-teal-900 cursor-pointer shadow-xs'
                                        : 'bg-white border-slate-200/80 hover:border-slate-300 cursor-pointer text-slate-700'
                                ]"
                            >
                                <div class="flex items-center gap-2.5">
                                    <input
                                        type="checkbox"
                                        :value="s.id"
                                        :checked="memberForm.student_ids.includes(s.id)"
                                        :disabled="enrolledStudentIds.has(s.id) || !isGenderEligible(s.gender)"
                                        @click.stop="toggleStudentSelection(s.id)"
                                        class="w-3.5 h-3.5 rounded text-teal-600 focus:ring-teal-500 border-slate-300"
                                    />
                                    <div>
                                        <div class="font-bold text-xs flex items-center gap-1.5">
                                            <span>{{ s.full_name }}</span>
                                            <span 
                                                class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase"
                                                :class="s.gender === 'L' || s.gender === 'l' ? 'bg-sky-100 text-sky-700' : 'bg-pink-100 text-pink-700'"
                                            >
                                                {{ s.gender === 'L' || s.gender === 'l' ? 'L' : 'P' }}
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            NIS: {{ s.nis || '-' }} <span v-if="s.nisn">• NISN: {{ s.nisn }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <span v-if="enrolledStudentIds.has(s.id)" class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-200 text-slate-600">
                                        Sudah Anggota
                                    </span>
                                    <span v-else-if="!isGenderEligible(s.gender)" class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-rose-100 text-rose-700">
                                        Khusus {{ activity.gender_restriction === 'male_only' ? 'Ikhwan' : 'Akhwat' }}
                                    </span>
                                    <span v-else-if="memberForm.student_ids.includes(s.id)" class="px-2 py-0.5 rounded-full text-[9px] font-black bg-teal-200 text-teal-800 inline-flex items-center gap-1">
                                        <CheckIcon class="w-3 h-3 stroke-[3]" />
                                        <span>Terpilih</span>
                                    </span>
                                </div>
                            </div>

                            <div v-if="availableStudentsForModal.length === 0" class="py-6 text-center text-xs text-slate-400 font-bold">
                                Tidak ada siswa yang sesuai kriteria pencarian.
                            </div>
                        </div>

                        <div class="pt-2.5 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-xs text-slate-500 font-medium">
                                <span v-if="memberForm.student_ids.length > remainingQuota" class="text-amber-600 font-bold inline-flex items-center gap-1">
                                    <ExclamationTriangleIcon class="w-4 h-4 text-amber-500 shrink-0" />
                                    <span>Pilihan melebihi sisa kuota ({{ remainingQuota }} kursi).</span>
                                </span>
                                <span v-else>
                                    {{ memberForm.student_ids.length }} siswa akan didaftarkan.
                                </span>
                            </span>

                            <div class="flex items-center gap-2">
                                <button @click="showAddMemberModal = false" type="button" class="px-3.5 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    :disabled="memberForm.processing || memberForm.student_ids.length === 0"
                                    class="px-4 py-1.5 rounded-xl bg-teal-700 hover:bg-teal-800 disabled:opacity-50 text-white text-xs font-bold transition-all shadow-xs"
                                >
                                    Tambahkan {{ memberForm.student_ids.length ? `(${memberForm.student_ids.length})` : '' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- MODAL 4: PENUGASAN PELATIH -->
        <Teleport to="body">
            <div v-if="showAddCoachModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-800 text-base">Tugaskan / Buat Akun Pelatih</h3>
                        <button @click="showAddCoachModal = false" class="p-1.5 text-slate-400 hover:text-slate-600">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitCoach" class="p-5 space-y-3.5">
                        <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-2xl text-xs font-bold">
                            <button
                                type="button"
                                @click="coachForm.mode = 'existing'"
                                class="py-1.5 rounded-xl transition-all"
                                :class="coachForm.mode === 'existing' ? 'bg-white text-teal-800 shadow-xs' : 'text-slate-500'"
                            >
                                Dari Guru / Pengguna Ada
                            </button>
                            <button
                                type="button"
                                @click="coachForm.mode = 'create_new'"
                                class="py-1.5 rounded-xl transition-all"
                                :class="coachForm.mode === 'create_new' ? 'bg-white text-teal-800 shadow-xs' : 'text-slate-500'"
                            >
                                Buat Akun Pelatih Baru
                            </button>
                        </div>

                        <div v-if="coachForm.mode === 'existing'">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Guru / Pengguna *</label>
                            <select
                                v-model="coachForm.user_id"
                                required
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-teal-500 bg-white"
                            >
                                <option value="">-- Pilih Guru / Staf --</option>
                                <option v-for="c in availableInstructors" :key="c.id" :value="c.id">
                                    {{ c.name }} ({{ c.phone || 'No HP -' }})
                                </option>
                            </select>
                        </div>

                        <div v-else class="space-y-2.5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Pelatih *</label>
                                <input
                                    v-model="coachForm.name"
                                    type="text"
                                    required
                                    placeholder="Nama pelatih luar..."
                                    class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Email Akun Login *</label>
                                <input
                                    v-model="coachForm.email"
                                    type="email"
                                    required
                                    placeholder="pelatih@namira.school"
                                    class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp *</label>
                                    <input
                                        v-model="coachForm.phone"
                                        type="text"
                                        required
                                        placeholder="081234567890"
                                        class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Password Baru *</label>
                                    <input
                                        v-model="coachForm.password"
                                        type="password"
                                        required
                                        placeholder="Minimal 6 karakter"
                                        class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jabatan Penugasan</label>
                                <input
                                    v-model="coachForm.role_title"
                                    type="text"
                                    required
                                    placeholder="Pelatih Utama / Asisten"
                                    class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Asal Klub / Instansi</label>
                                <input
                                    v-model="coachForm.institution"
                                    type="text"
                                    placeholder="Sanggar Seni / Klub Futsal"
                                    class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-teal-500"
                                />
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                            <button @click="showAddCoachModal = false" type="button" class="px-4 py-1.5 rounded-xl border border-slate-200 text-xs font-bold">
                                Batal
                            </button>
                            <button type="submit" :disabled="coachForm.processing" class="px-5 py-1.5 rounded-xl bg-teal-700 text-white text-xs font-bold">
                                Tugaskan Pelatih
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- MODAL 5: ZOOM FOTO DOKUMENTASI -->
        <Teleport to="body">
            <div v-if="showZoomPhotoModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4" @click="showZoomPhotoModal = false">
                <div class="relative max-w-4xl max-h-[90vh] overflow-hidden rounded-2xl">
                    <img :src="zoomedPhotoUrl" class="w-full h-full object-contain max-h-[85vh] rounded-2xl" />
                    <button @click="showZoomPhotoModal = false" class="absolute top-3 right-3 p-2 rounded-full bg-black/60 text-white hover:bg-black">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
