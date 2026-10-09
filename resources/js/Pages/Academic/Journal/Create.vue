<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { 
    ArrowLeftIcon,
    CalendarDaysIcon,
    UserGroupIcon,
    BookOpenIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    PlusIcon,
    CheckIcon,
    DocumentTextIcon,
    CameraIcon,
    PhotoIcon,
    XMarkIcon,
    ArrowPathIcon,
    UsersIcon,
    BookmarkSquareIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const props = defineProps({
    schedule: Object,
    date: String,
    classroom: Object,
    subject: Object,
    students: { type: Array, default: () => [] },
    existingChapters: { type: Array, default: () => [] },
    journal: Object, // Optional: For Edit Mode
    teacherSchedules: { type: Array, default: () => [] },
});

const isEditing = computed(() => !!props.journal);

// Active Stepper Step (1: Informasi, 2: Materi, 3: Presensi)
const currentStep = ref(1);

const scrollToSection = (sectionId, stepNumber) => {
    currentStep.value = stepNumber;
    const el = document.getElementById(sectionId);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

// Form Logic
const form = useForm({
    classroom_id: props.classroom?.id || '',
    subject_id: props.subject?.id || '',
    class_schedule_id: props.schedule?.id || '',
    date: props.date || new Date().toISOString().split('T')[0],
    start_time: props.schedule?.start_time || '07:00',
    end_time: props.schedule?.end_time || '08:00',
    selected_tps: [],
    new_tps: [], // Array of { code, description, chapter_title }
    custom_theme: '',
    attendance: props.students.map(s => ({
        student_id: s.id,
        status: s.default_status || 'present',
        note: s.default_note || ''
    })),
    notes: '',
    photo: null,
    status: 'submitted', // 'draft' or 'submitted'
    _method: 'POST',
});

// Chapters Accordion State (all open by default)
const openChapters = ref({});
const initOpenChapters = () => {
    props.existingChapters.forEach((ch, idx) => {
        openChapters.value[ch.id] = true;
    });
};
initOpenChapters();

const toggleChapter = (chapterId) => {
    openChapters.value[chapterId] = !openChapters.value[chapterId];
};

// Toggle Learning Objective (TP)
const toggleTp = (tpId) => {
    const idx = form.selected_tps.indexOf(tpId);
    if (idx > -1) {
        form.selected_tps.splice(idx, 1);
    } else {
        form.selected_tps.push(tpId);
    }
};

// Photo & Compression Logic
const photoPreview = ref(null);
const isCompressing = ref(false);
const showPhotoSection = ref(false);

const compressImage = (file) => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;
                const maxDim = 1600;

                if (width > height && width > maxDim) {
                    height = Math.round((height * maxDim) / width);
                    width = maxDim;
                } else if (height > maxDim) {
                    width = Math.round((width * maxDim) / height);
                    height = maxDim;
                }

                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                const compressedDataUrl = canvas.toDataURL('image/jpeg', 0.8);
                resolve(compressedDataUrl);
            };
            img.onerror = (error) => reject(error);
        };
        reader.onerror = (error) => reject(error);
    });
};

const handlePhotoInput = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    isCompressing.value = true;
    try {
        const compressedBase64 = await compressImage(file);
        photoPreview.value = compressedBase64;
        form.photo = compressedBase64;
        showPhotoSection.value = true;
    } catch (err) {
        console.error('Compress error:', err);
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
        showPhotoSection.value = true;
    } finally {
        isCompressing.value = false;
    }
};

const removePhoto = () => {
    form.photo = null;
    photoPreview.value = null;
};

// Select All Present Shortcut
const selectAllPresent = () => {
    form.attendance.forEach(att => {
        att.status = 'present';
    });
};

// Attendance Summary Counts
const attendanceCounts = computed(() => {
    const counts = { present: 0, sick: 0, permission: 0, alpha: 0, late: 0 };
    if (form.attendance) {
        form.attendance.forEach(att => {
            if (counts[att.status] !== undefined) {
                counts[att.status]++;
            }
        });
    }
    return counts;
});

// Student Note Modal State
const showStudentNoteModal = ref(false);
const activeStudentForNote = ref(null);
const activeAttendanceForNote = ref(null);
const tempStudentNote = ref('');

const openStudentNoteModal = (att, student) => {
    activeStudentForNote.value = student;
    activeAttendanceForNote.value = att;
    tempStudentNote.value = att.note || '';
    showStudentNoteModal.value = true;
};

const saveStudentNote = () => {
    if (activeAttendanceForNote.value) {
        activeAttendanceForNote.value.note = tempStudentNote.value;
    }
    showStudentNoteModal.value = false;
};

const setQuickNote = (preset) => {
    tempStudentNote.value = preset;
};

// JIT Add TP Modal State
const showAddTpModal = ref(false);
const newTpForm = ref({
    chapter_title: '',
    code: '',
    description: ''
});

const openAddTpModal = () => {
    newTpForm.value = {
        chapter_title: props.existingChapters[0]?.title || 'Bab 1. Materi Baru',
        code: `TP ${(props.classroom?.level || '1')}.${(props.existingChapters.reduce((acc, c) => acc + (c.learning_objectives?.length || 0), 0) + 1)}`,
        description: ''
    };
    showAddTpModal.value = true;
};

const addNewTp = () => {
    if (!newTpForm.value.code || !newTpForm.value.description || !newTpForm.value.chapter_title) {
        Swal.fire({
            icon: 'warning',
            title: 'Lengkapi Data',
            text: 'Harap isi bab, kode TP, dan deskripsi tujuan pembelajaran.',
            confirmButtonColor: '#00796B',
        });
        return;
    }
    
    form.new_tps.push({ ...newTpForm.value });
    showAddTpModal.value = false;
};

const removeNewTp = (index) => {
    form.new_tps.splice(index, 1);
};

// Switch Schedule / Class / Subject from Row 2 & 3
const onScheduleSelect = (event) => {
    const selectedSchedId = event.target.value;
    if (selectedSchedId && selectedSchedId !== form.class_schedule_id) {
        router.get(route('yayasan.teaching-journal.create'), {
            schedule_id: selectedSchedId,
            date: form.date,
        }, { preserveScroll: true });
    }
};

// Switch Date from Row 1
const onDateChange = (event) => {
    const newDate = event.target.value;
    if (newDate && newDate !== form.date) {
        form.date = newDate;
        router.get(route('yayasan.teaching-journal.create'), {
            schedule_id: form.class_schedule_id,
            date: newDate,
        }, { preserveScroll: true });
    }
};

// Initialize for Edit Mode or Pre-populated Journal
onMounted(() => {
    if (props.journal) {
        form.class_schedule_id = props.journal.class_schedule_id;
        form.date = props.journal.date ? props.journal.date.substring(0, 10) : props.date;
        form.start_time = props.journal.start_time || form.start_time;
        form.end_time = props.journal.end_time || form.end_time;
        form.custom_theme = props.journal.custom_theme || '';
        form.notes = props.journal.notes || '';
        form.status = props.journal.status || 'submitted';
        form._method = 'PUT';

        if (props.journal.photo_path) {
            showPhotoSection.value = true;
        }

        // Map Attendance
        if (props.journal.attendance && props.journal.attendance.length > 0) {
            form.attendance = props.students.map(s => {
                const existing = props.journal.attendance.find(a => a.student_id === s.id);
                return {
                    student_id: s.id,
                    status: existing ? existing.status : (s.default_status || 'present'),
                    note: existing ? existing.note : (s.default_note || '')
                };
            });
        }

        // Map TPs
        if (props.journal.learning_objectives) {
            form.selected_tps = props.journal.learning_objectives.map(tp => tp.id);
        }
    }
});

// Submit Form (Draft or Submitted)
const submitAs = (targetStatus) => {
    form.status = targetStatus;

    const endpoint = isEditing.value 
        ? route('yayasan.teaching-journal.update', props.journal.id) 
        : route('yayasan.teaching-journal.store');

    form.post(endpoint, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: targetStatus === 'draft' ? 'Draft Disimpan!' : 'Jurnal Tersimpan!',
                text: targetStatus === 'draft' 
                    ? 'Jurnal berhasil disimpan sebagai draf. Anda dapat melanjutkannya nanti.' 
                    : (isEditing.value ? 'Jurnal mengajar berhasil diperbarui.' : 'Jurnal mengajar dan presensi siswa berhasil dicatat.'),
                confirmButtonColor: '#00796B',
            });
        },
        onError: (errors) => {
            const errorMsg = Object.values(errors).flat().join('<br>') || 'Terjadi kesalahan saat menyimpan jurnal.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                html: errorMsg,
                confirmButtonColor: '#00796B',
            });
        }
    });
};

// Date Formatter: "Selasa, 06 Oktober 2026"
const formatIndonesianDate = (dateString) => {
    if (!dateString) return '-';
    try {
        const parts = dateString.split('-');
        const dateObj = new Date(parts[0], parts[1] - 1, parts[2]);
        const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const monthNames = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        const dayName = dayNames[dateObj.getDay()];
        const dayNum = String(dateObj.getDate()).padStart(2, '0');
        const monthName = monthNames[dateObj.getMonth()];
        const year = dateObj.getFullYear();
        return `${dayName}, ${dayNum} ${monthName} ${year}`;
    } catch {
        return dateString;
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Jurnal Mengajar' : 'Jurnal Mengajar'" />

    <AuthenticatedLayout>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-4 sm:py-6 pb-28 space-y-5">
            
            <!-- TOP HEADER ROW: Back Button + Title + Subtitle -->
            <div class="flex items-center gap-3.5 pt-1">
                <Link 
                    :href="route('yayasan.teaching-journal.index', { date: form.date })" 
                    class="p-2 rounded-2xl bg-white border border-slate-200/80 hover:bg-slate-50 text-slate-700 shadow-2xs transition active:scale-95"
                    title="Kembali ke Jurnal Mengajar"
                >
                    <ArrowLeftIcon class="w-5 h-5 stroke-[2.2]" />
                </Link>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-tight">
                        {{ isEditing ? 'Edit Jurnal Mengajar' : 'Jurnal Mengajar' }}
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 font-medium">
                        Catat kegiatan belajar mengajar harian Anda
                    </p>
                </div>
            </div>

            <!-- STEPPER BAR (1: Informasi — 2: Materi — 3: Presensi) -->
            <div class="flex items-center justify-center gap-2 sm:gap-4 py-2 select-none">
                <!-- Step 1: Informasi -->
                <button 
                    type="button" 
                    @click="scrollToSection('card-informasi', 1)"
                    class="flex items-center gap-2 group cursor-pointer focus:outline-none"
                >
                    <span 
                        :class="[
                            'w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs transition-all',
                            currentStep === 1 
                                ? 'bg-[#00796B] text-white ring-4 ring-teal-100 shadow-xs' 
                                : 'bg-[#00796B]/80 text-white'
                        ]"
                    >
                        1
                    </span>
                    <span class="text-xs sm:text-sm font-bold text-slate-800">
                        Informasi
                    </span>
                </button>

                <!-- Divider 1 -->
                <div class="h-[2px] w-8 sm:w-16 bg-slate-200 rounded-full"></div>

                <!-- Step 2: Materi -->
                <button 
                    type="button" 
                    @click="scrollToSection('card-materi', 2)"
                    class="flex items-center gap-2 group cursor-pointer focus:outline-none"
                >
                    <span 
                        :class="[
                            'w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs transition-all',
                            currentStep === 2 
                                ? 'bg-[#00796B] text-white ring-4 ring-teal-100 shadow-xs' 
                                : 'bg-slate-200 text-slate-600'
                        ]"
                    >
                        2
                    </span>
                    <span 
                        :class="[
                            'text-xs sm:text-sm font-bold transition-colors',
                            currentStep === 2 ? 'text-slate-900' : 'text-slate-500'
                        ]"
                    >
                        Materi
                    </span>
                </button>

                <!-- Divider 2 -->
                <div class="h-[2px] w-8 sm:w-16 bg-slate-200 rounded-full"></div>

                <!-- Step 3: Presensi -->
                <button 
                    type="button" 
                    @click="scrollToSection('card-presensi', 3)"
                    class="flex items-center gap-2 group cursor-pointer focus:outline-none"
                >
                    <span 
                        :class="[
                            'w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs transition-all',
                            currentStep === 3 
                                ? 'bg-[#00796B] text-white ring-4 ring-teal-100 shadow-xs' 
                                : 'bg-slate-200 text-slate-600'
                        ]"
                    >
                        3
                    </span>
                    <span 
                        :class="[
                            'text-xs sm:text-sm font-bold transition-colors',
                            currentStep === 3 ? 'text-slate-900' : 'text-slate-500'
                        ]"
                    >
                        Presensi
                    </span>
                </button>
            </div>

            <!-- FORM START -->
            <form @submit.prevent="submitAs('submitted')" class="space-y-5">

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- SECTION 1: INFORMASI PEMBELAJARAN CARD                   -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div id="card-informasi" class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs space-y-4">
                    <!-- Section Header -->
                    <div class="flex items-start gap-3">
                        <span class="w-7 h-7 rounded-full bg-[#00796B] text-white flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                            1
                        </span>
                        <div>
                            <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight leading-snug">
                                Informasi Pembelajaran
                            </h2>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">
                                Lengkapi informasi dasar kegiatan belajar mengajar.
                            </p>
                        </div>
                    </div>

                    <!-- 3 Rows Container -->
                    <div class="space-y-2.5 pt-1">
                        <!-- Row 1: Tanggal -->
                        <div class="flex items-center justify-between p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 bg-white hover:border-teal-500/40 transition gap-3 relative group">
                            <div class="flex items-center gap-3 text-slate-700">
                                <CalendarDaysIcon class="w-5 h-5 text-slate-500 shrink-0" />
                                <span class="font-bold text-xs sm:text-sm">Tanggal</span>
                            </div>

                            <div class="relative flex items-center">
                                <input 
                                    type="date" 
                                    :value="form.date"
                                    @change="onDateChange"
                                    class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10"
                                />
                                <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm font-bold text-slate-800 group-hover:bg-teal-50/60 group-hover:border-teal-300 transition">
                                    <span>{{ formatIndonesianDate(form.date) }}</span>
                                    <CalendarDaysIcon class="w-4 h-4 text-slate-500" />
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: Kelas -->
                        <div class="flex items-center justify-between p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 bg-white hover:border-teal-500/40 transition gap-3 relative">
                            <div class="flex items-center gap-3 text-slate-700">
                                <UserGroupIcon class="w-5 h-5 text-slate-500 shrink-0" />
                                <span class="font-bold text-xs sm:text-sm">Kelas</span>
                            </div>

                            <div class="relative min-w-[120px]">
                                <!-- Select dropdown if teacher has multiple schedules -->
                                <select 
                                    v-if="teacherSchedules.length > 0"
                                    :value="form.class_schedule_id"
                                    @change="onScheduleSelect"
                                    class="w-full appearance-none px-3.5 py-1.5 pr-8 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm font-black text-slate-800 focus:ring-teal-500 focus:border-teal-500 cursor-pointer"
                                >
                                    <option 
                                        v-for="s in teacherSchedules" 
                                        :key="s.id" 
                                        :value="s.id"
                                    >
                                        {{ s.classroom_name }} ({{ s.subject_name }})
                                    </option>
                                </select>
                                <div v-else class="flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm font-black text-slate-800">
                                    <span>{{ classroom?.name || '-' }}</span>
                                    <ChevronDownIcon class="w-4 h-4 text-slate-400" />
                                </div>
                                <ChevronDownIcon v-if="teacherSchedules.length > 0" class="w-4 h-4 text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                            </div>
                        </div>

                        <!-- Row 3: Mata Pelajaran -->
                        <div class="flex items-center justify-between p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 bg-white hover:border-teal-500/40 transition gap-3 relative">
                            <div class="flex items-center gap-3 text-slate-700">
                                <BookOpenIcon class="w-5 h-5 text-slate-500 shrink-0" />
                                <span class="font-bold text-xs sm:text-sm">Mata Pelajaran</span>
                            </div>

                            <div class="relative min-w-[150px] max-w-[220px]">
                                <select 
                                    v-if="teacherSchedules.length > 0"
                                    :value="form.class_schedule_id"
                                    @change="onScheduleSelect"
                                    class="w-full appearance-none px-3.5 py-1.5 pr-8 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm font-black text-slate-800 focus:ring-teal-500 focus:border-teal-500 cursor-pointer truncate"
                                >
                                    <option 
                                        v-for="s in teacherSchedules" 
                                        :key="s.id" 
                                        :value="s.id"
                                    >
                                        {{ s.subject_name }}
                                    </option>
                                </select>
                                <div v-else class="flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm font-black text-slate-800 truncate">
                                    <span class="truncate">{{ subject?.name || '-' }}</span>
                                    <ChevronDownIcon class="w-4 h-4 text-slate-400 shrink-0" />
                                </div>
                                <ChevronDownIcon v-if="teacherSchedules.length > 0" class="w-4 h-4 text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- SECTION 2: TUJUAN & MATERI PEMBELAJARAN CARD             -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div id="card-materi" class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs space-y-4">
                    <!-- Section Header with + Tambah Materi Button -->
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-full bg-[#00796B] text-white flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                                2
                            </span>
                            <div>
                                <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight leading-snug">
                                    Tujuan & Materi Pembelajaran
                                </h2>
                                <p class="text-xs text-slate-500 font-medium mt-0.5">
                                    Pilih tujuan pembelajaran dan materi yang digunakan.
                                </p>
                            </div>
                        </div>

                        <!-- Button Tambah Materi (Pill button with teal accent) -->
                        <button
                            type="button"
                            @click="openAddTpModal"
                            class="px-3 sm:px-3.5 py-2 rounded-xl bg-teal-50 hover:bg-teal-100 text-[#00796B] font-extrabold text-xs border border-teal-200/80 flex items-center gap-1.5 transition active:scale-95 shrink-0"
                        >
                            <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                            <span>Tambah Materi</span>
                        </button>
                    </div>

                    <!-- Chapters & TPs Accordion List -->
                    <div v-if="existingChapters.length > 0" class="space-y-3 pt-1">
                        <div 
                            v-for="(chapter, cIdx) in existingChapters" 
                            :key="chapter.id" 
                            class="rounded-2xl border border-slate-200/80 bg-slate-50/50 overflow-hidden transition-all"
                        >
                            <!-- Accordion Header -->
                            <button
                                type="button"
                                @click="toggleChapter(chapter.id)"
                                class="w-full px-4 py-3 flex items-center justify-between font-black text-xs sm:text-sm text-slate-800 hover:bg-slate-100/70 transition text-left select-none"
                            >
                                <span class="pr-2">{{ chapter.title || `Bab ${cIdx + 1}. Materi Pembelajaran` }}</span>
                                <component 
                                    :is="openChapters[chapter.id] ? ChevronUpIcon : ChevronDownIcon" 
                                    class="w-4 h-4 text-slate-500 shrink-0" 
                                />
                            </button>

                            <!-- Accordion Content: Checkbox List of TPs -->
                            <div 
                                v-show="openChapters[chapter.id]" 
                                class="p-3.5 sm:p-4 bg-white border-t border-slate-200/60 space-y-3"
                            >
                                <div 
                                    v-for="tp in chapter.learning_objectives" 
                                    :key="tp.id"
                                    @click="toggleTp(tp.id)"
                                    class="flex items-start gap-3.5 cursor-pointer select-none group"
                                >
                                    <!-- Custom Teal Checkbox -->
                                    <div 
                                        :class="[
                                            'w-5 h-5 rounded-md flex items-center justify-center shrink-0 mt-0.5 transition-all',
                                            form.selected_tps.includes(tp.id)
                                                ? 'bg-[#00796B] text-white shadow-xs'
                                                : 'border-2 border-slate-300 bg-white group-hover:border-teal-500'
                                        ]"
                                    >
                                        <CheckIcon v-if="form.selected_tps.includes(tp.id)" class="w-3.5 h-3.5 stroke-[3]" />
                                    </div>

                                    <!-- TP Code & Description -->
                                    <div class="flex-1 flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-3">
                                        <span class="font-black text-xs sm:text-sm text-slate-900 shrink-0 min-w-[55px]">
                                            {{ tp.code }}
                                        </span>
                                        <span class="text-xs sm:text-sm text-slate-600 font-medium leading-relaxed">
                                            {{ tp.description }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fallback if No Chapters -->
                    <div v-else class="text-center py-6 px-4 bg-slate-50 rounded-2xl border border-dashed border-slate-200 space-y-2">
                        <BookOpenIcon class="w-8 h-8 mx-auto text-slate-400 opacity-60" />
                        <p class="text-xs text-slate-500 font-medium">Belum ada master materi pembelajaran untuk mata pelajaran ini.</p>
                        <button
                            type="button"
                            @click="openAddTpModal"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-teal-50 text-[#00796B] font-bold text-xs hover:bg-teal-100 transition"
                        >
                            <PlusIcon class="w-3.5 h-3.5" />
                            <span>Buat Materi Baru Sekarang</span>
                        </button>
                    </div>

                    <!-- Newly Added TPs (JIT) Display -->
                    <div v-if="form.new_tps.length > 0" class="space-y-2 pt-1">
                        <div 
                            v-for="(ntp, idx) in form.new_tps" 
                            :key="idx" 
                            class="flex items-start justify-between p-3.5 bg-teal-50/70 rounded-2xl border border-teal-200/80 gap-3"
                        >
                            <div class="flex items-start gap-3">
                                <span class="w-5 h-5 rounded-md bg-[#00796B] text-white flex items-center justify-center text-xs shrink-0 mt-0.5">
                                    <CheckIcon class="w-3.5 h-3.5 stroke-[3]" />
                                </span>
                                <div>
                                    <p class="text-xs font-black text-teal-950">
                                        {{ ntp.code }} &bull; {{ ntp.chapter_title }} <span class="text-[10px] text-teal-700 font-bold uppercase">(Baru)</span>
                                    </p>
                                    <p class="text-xs text-slate-700 mt-0.5 leading-relaxed font-medium">
                                        {{ ntp.description }}
                                    </p>
                                </div>
                            </div>
                            <button 
                                type="button" 
                                @click="removeNewTp(idx)" 
                                class="text-rose-500 hover:text-rose-700 text-xs font-bold p-1 transition"
                                title="Hapus materi"
                            >
                                <XMarkIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Catatan (Opsional) Row with Icon & Textarea -->
                    <div class="rounded-2xl border border-slate-200/80 p-3.5 sm:p-4 bg-white space-y-1.5">
                        <div class="flex items-center gap-2 text-slate-500">
                            <DocumentTextIcon class="w-4 h-4 text-slate-400 shrink-0" />
                            <span class="text-xs font-bold">Catatan (Opsional)</span>
                        </div>
                        <textarea 
                            v-model="form.notes" 
                            rows="2" 
                            class="w-full text-xs sm:text-sm text-slate-800 border-0 p-0 focus:ring-0 placeholder:text-slate-400 font-medium resize-none leading-relaxed" 
                            placeholder="Materi disampaikan dengan demonstrasi langsung dan latihan praktik di komputer sekolah."
                        ></textarea>
                    </div>

                    <!-- Optional Compact Photo Trigger -->
                    <div class="pt-1">
                        <div v-if="!showPhotoSection && !photoPreview && (!isEditing || !journal?.photo_path)">
                            <button
                                type="button"
                                @click="showPhotoSection = true"
                                class="text-xs font-bold text-slate-500 hover:text-teal-700 flex items-center gap-2 transition"
                            >
                                <CameraIcon class="w-4 h-4 text-slate-400" />
                                <span>+ Lampirkan Foto Dokumentasi Kelas (Opsional)</span>
                            </button>
                        </div>

                        <!-- Photo Section if active -->
                        <div v-else class="rounded-2xl border border-slate-200/80 p-3.5 bg-slate-50/50 space-y-3">
                            <div class="flex items-center justify-between text-xs font-bold text-slate-700">
                                <span class="flex items-center gap-1.5">
                                    <CameraIcon class="w-4 h-4 text-teal-600" />
                                    Foto Dokumentasi Kelas
                                </span>
                                <button type="button" @click="showPhotoSection = false" class="text-slate-400 hover:text-slate-600">
                                    <XMarkIcon class="w-4 h-4" />
                                </button>
                            </div>

                            <!-- Photo Preview -->
                            <div v-if="photoPreview || (isEditing && journal?.photo_path && !photoPreview)" class="relative rounded-xl overflow-hidden border border-teal-500/40 bg-slate-900 aspect-video max-h-48 flex items-center justify-center">
                                <img :src="photoPreview || ('/storage/' + journal.photo_path)" class="w-full h-full object-cover" />
                                <button type="button" @click="removePhoto" class="absolute top-2 right-2 p-1.5 bg-black/60 hover:bg-black/80 text-white rounded-full transition shadow">
                                    <XMarkIcon class="w-4 h-4" />
                                </button>
                            </div>

                            <!-- Buttons Capture / Upload -->
                            <div v-else class="grid grid-cols-2 gap-2.5">
                                <label class="flex items-center justify-center gap-2 p-3 bg-white hover:bg-teal-50 border border-slate-200 hover:border-teal-300 rounded-xl cursor-pointer transition active:scale-95 text-xs font-bold text-slate-700">
                                    <CameraIcon class="w-4 h-4 text-teal-600" />
                                    <span>Kamera</span>
                                    <input type="file" accept="image/*" capture="environment" class="hidden" @change="handlePhotoInput">
                                </label>
                                <label class="flex items-center justify-center gap-2 p-3 bg-white hover:bg-teal-50 border border-slate-200 hover:border-teal-300 rounded-xl cursor-pointer transition active:scale-95 text-xs font-bold text-slate-700">
                                    <PhotoIcon class="w-4 h-4 text-slate-600" />
                                    <span>Galeri</span>
                                    <input type="file" accept="image/*" class="hidden" @change="handlePhotoInput">
                                </label>
                            </div>

                            <div v-if="isCompressing" class="flex items-center justify-center gap-2 text-xs font-bold text-amber-700">
                                <ArrowPathIcon class="w-3.5 h-3.5 animate-spin" />
                                <span>Mengompresi foto...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- SECTION 3: PRESENSI SISWA CARD                           -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div id="card-presensi" class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs space-y-4">
                    <!-- Section Header with Pilih Semua Hadir Button -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-full bg-[#00796B] text-white flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                                3
                            </span>
                            <div>
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight leading-snug">
                                        Presensi Siswa
                                    </h2>
                                    <!-- Status Summary Badges -->
                                    <div class="flex items-center gap-1.5 flex-wrap text-[11px] font-black">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-teal-50 text-[#00796B] border border-teal-200/60" title="Hadir">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#00796B]"></span>
                                            H: {{ attendanceCounts.present }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200/60" title="Sakit">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            S: {{ attendanceCounts.sick }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200/60" title="Izin">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                            I: {{ attendanceCounts.permission }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 border border-rose-200/60" title="Alpa">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            A: {{ attendanceCounts.alpha }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-orange-50 text-orange-700 border border-orange-200/60" title="Telat">
                                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                                            T: {{ attendanceCounts.late }}
                                        </span>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-500 font-medium mt-1">
                                    Pilih status kehadiran untuk {{ students.length }} siswa kelas {{ classroom?.name || '-' }}.
                                    <span class="hidden sm:inline text-slate-400 font-semibold ml-1">
                                        (<strong class="text-teal-700">H</strong>: Hadir, <strong class="text-amber-600">S</strong>: Sakit, <strong class="text-blue-600">I</strong>: Izin, <strong class="text-rose-600">A</strong>: Alpa, <strong class="text-orange-600">T</strong>: Telat)
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Button Pilih Semua Hadir -->
                        <button
                            type="button"
                            @click="selectAllPresent"
                            class="px-3 sm:px-3.5 py-2 rounded-xl bg-teal-50 hover:bg-teal-100 text-[#00796B] font-extrabold text-xs border border-teal-200/80 flex items-center justify-center gap-1.5 transition active:scale-95 shrink-0 self-start sm:self-auto"
                        >
                            <UsersIcon class="w-4 h-4 stroke-[2.2]" />
                            <span>Pilih Semua Hadir</span>
                        </button>
                    </div>

                    <!-- Attendance Table -->
                    <div class="overflow-x-auto pt-1 -mx-2 sm:mx-0">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                                    <th class="py-2.5 px-2 text-center w-8">No</th>
                                    <th class="py-2.5 px-3 min-w-[130px]">Nama Siswa</th>
                                    <th class="py-2.5 px-2 sm:px-3 text-center min-w-[150px] sm:min-w-[180px]">Status</th>
                                    <th class="py-2.5 px-2 text-center w-12">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr 
                                    v-for="(att, idx) in form.attendance" 
                                    :key="att.student_id" 
                                    class="hover:bg-slate-50/60 transition-colors"
                                >
                                    <!-- No -->
                                    <td class="py-3 px-2 text-center text-xs font-bold text-slate-400">
                                        {{ idx + 1 }}
                                    </td>

                                    <!-- Nama Siswa -->
                                    <td class="py-3 px-3">
                                        <p class="font-black text-xs sm:text-sm text-slate-800 leading-tight">
                                            {{ students.find(s => s.id === att.student_id)?.name }}
                                        </p>
                                        <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                            <span class="text-[10px] text-slate-400 font-mono">
                                                NIS: {{ students.find(s => s.id === att.student_id)?.nis || '-' }}
                                            </span>
                                            <span 
                                                v-if="students.find(s => s.id === att.student_id)?.source_badge" 
                                                class="inline-flex items-center px-1.5 py-0.2 rounded-md text-[9px] font-bold bg-teal-50 text-teal-700 border border-teal-200/60"
                                            >
                                                {{ students.find(s => s.id === att.student_id)?.source_badge }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Status Kehadiran (Pill Toggle Buttons H S I A T) -->
                                    <td class="py-3 px-2 sm:px-3 text-center">
                                        <div class="inline-flex items-center justify-center gap-1 sm:gap-1.5">
                                            <!-- Hadir (H) -->
                                            <button
                                                type="button"
                                                @click="att.status = 'present'"
                                                title="Hadir (H)"
                                                :class="[
                                                    'w-7 h-7 sm:w-8 sm:h-8 inline-flex items-center justify-center text-xs rounded-lg font-black transition active:scale-95',
                                                    att.status === 'present'
                                                        ? 'bg-[#00796B] text-white shadow-xs ring-1 ring-teal-700'
                                                        : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/70 font-semibold'
                                                ]"
                                            >
                                                H
                                            </button>

                                            <!-- Sakit (S) -->
                                            <button
                                                type="button"
                                                @click="att.status = 'sick'"
                                                title="Sakit (S)"
                                                :class="[
                                                    'w-7 h-7 sm:w-8 sm:h-8 inline-flex items-center justify-center text-xs rounded-lg font-black transition active:scale-95',
                                                    att.status === 'sick'
                                                        ? 'bg-amber-500 text-white shadow-xs ring-1 ring-amber-600'
                                                        : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/70 font-semibold'
                                                ]"
                                            >
                                                S
                                            </button>

                                            <!-- Izin (I) -->
                                            <button
                                                type="button"
                                                @click="att.status = 'permission'"
                                                title="Izin (I)"
                                                :class="[
                                                    'w-7 h-7 sm:w-8 sm:h-8 inline-flex items-center justify-center text-xs rounded-lg font-black transition active:scale-95',
                                                    att.status === 'permission'
                                                        ? 'bg-blue-500 text-white shadow-xs ring-1 ring-blue-600'
                                                        : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/70 font-semibold'
                                                ]"
                                            >
                                                I
                                            </button>

                                            <!-- Alpa (A) -->
                                            <button
                                                type="button"
                                                @click="att.status = 'alpha'"
                                                title="Alpa (A)"
                                                :class="[
                                                    'w-7 h-7 sm:w-8 sm:h-8 inline-flex items-center justify-center text-xs rounded-lg font-black transition active:scale-95',
                                                    att.status === 'alpha'
                                                        ? 'bg-rose-500 text-white shadow-xs ring-1 ring-rose-600'
                                                        : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/70 font-semibold'
                                                ]"
                                            >
                                                A
                                            </button>

                                            <!-- Telat (T) -->
                                            <button
                                                type="button"
                                                @click="att.status = 'late'"
                                                title="Telat (T)"
                                                :class="[
                                                    'w-7 h-7 sm:w-8 sm:h-8 inline-flex items-center justify-center text-xs rounded-lg font-black transition active:scale-95',
                                                    att.status === 'late'
                                                        ? 'bg-orange-500 text-white shadow-xs ring-1 ring-orange-600'
                                                        : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/70 font-semibold'
                                                ]"
                                            >
                                                T
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Catatan Icon Button -->
                                    <td class="py-3 px-2 text-center">
                                        <button
                                            type="button"
                                            @click="openStudentNoteModal(att, students.find(s => s.id === att.student_id))"
                                            :class="[
                                                'p-1.5 rounded-lg transition active:scale-90',
                                                att.note 
                                                    ? 'text-teal-700 bg-teal-50 border border-teal-200/80 shadow-2xs' 
                                                    : 'text-slate-400 hover:text-slate-600 hover:bg-slate-100 border border-transparent'
                                            ]"
                                            :title="att.note ? `Catatan: ${att.note}` : 'Tambah catatan siswa'"
                                        >
                                            <DocumentTextIcon class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- STICKY BOTTOM ACTION BAR (Simpan Draft & Simpan Jurnal)  -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div class="sticky bottom-16 md:bottom-4 z-30 pt-3">
                    <div class="bg-white/95 backdrop-blur-md p-3 sm:p-4 rounded-3xl border border-slate-200/90 shadow-xl shadow-slate-900/10 flex items-center gap-3">
                        <!-- Button 1: Simpan Draft -->
                        <button
                            type="button"
                            @click="submitAs('draft')"
                            :disabled="form.processing"
                            class="flex-1 py-3.5 px-4 bg-slate-100 hover:bg-slate-200/90 text-slate-700 font-bold rounded-2xl flex items-center justify-center gap-2 text-xs sm:text-sm border border-slate-200/80 transition active:scale-95 shadow-2xs disabled:opacity-50"
                        >
                            <BookmarkSquareIcon class="w-4 h-4 text-slate-500" />
                            <span>Simpan Draft</span>
                        </button>

                        <!-- Button 2: Simpan Jurnal -->
                        <button
                            type="button"
                            @click="submitAs('submitted')"
                            :disabled="form.processing"
                            class="flex-1 py-3.5 px-4 bg-[#00695c] hover:bg-[#004d40] text-white font-extrabold rounded-2xl flex items-center justify-center gap-2 text-xs sm:text-sm shadow-md shadow-teal-900/15 transition active:scale-95 disabled:opacity-50"
                        >
                            <span v-if="form.processing" class="flex items-center gap-2">
                                <ArrowPathIcon class="w-4 h-4 animate-spin" />
                                Menyimpan...
                            </span>
                            <span v-else class="flex items-center gap-2">
                                <CheckCircleIcon class="w-4 h-4" />
                                {{ isEditing ? 'Perbarui Jurnal' : 'Simpan Jurnal' }}
                            </span>
                        </button>
                    </div>
                </div>

            </form>
            <!-- FORM END -->

        </div>

        <!-- ════════════════════════════════════════════════════════ -->
        <!-- MODAL: TAMBAH MATERI / TUJUAN PEMBELAJARAN (JIT)        -->
        <!-- ════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="showAddTpModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="showAddTpModal = false"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-7 border border-slate-100 space-y-4 animate-in fade-in zoom-in-95">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg font-black text-slate-900">Tambah Materi Pembelajaran</h3>
                                <p class="text-xs text-slate-500 font-medium mt-0.5">Materi baru akan otomatis disimpan ke master mata pelajaran.</p>
                            </div>
                            <button @click="showAddTpModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <div class="space-y-3.5 pt-1">
                            <div>
                                <label class="text-xs font-bold text-slate-700 block mb-1">Bab / Judul Materi *</label>
                                <input 
                                    v-model="newTpForm.chapter_title" 
                                    type="text" 
                                    class="w-full h-11 px-3.5 border-slate-200 rounded-xl text-xs sm:text-sm focus:ring-[#00796B] focus:border-[#00796B]" 
                                    placeholder="Contoh: Bab 1. Pengenalan Komputer dan Perangkatnya"
                                />
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-700 block mb-1">Kode TP (Tujuan Pembelajaran) *</label>
                                <input 
                                    v-model="newTpForm.code" 
                                    type="text" 
                                    class="w-full h-11 px-3.5 border-slate-200 rounded-xl text-xs sm:text-sm focus:ring-[#00796B] focus:border-[#00796B]" 
                                    placeholder="Contoh: TP 4.1"
                                />
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-700 block mb-1">Deskripsi Tujuan Pembelajaran *</label>
                                <textarea 
                                    v-model="newTpForm.description" 
                                    rows="3" 
                                    class="w-full p-3 border-slate-200 rounded-xl text-xs sm:text-sm focus:ring-[#00796B] focus:border-[#00796B] resize-none" 
                                    placeholder="Contoh: Siswa mampu mengenali bagian-bagian utama komputer dan fungsinya."
                                ></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="showAddTpModal = false" 
                                class="px-4 py-2.5 text-slate-500 hover:bg-slate-100 font-bold text-xs rounded-xl transition"
                            >
                                Batal
                            </button>
                            <button 
                                type="button" 
                                @click="addNewTp" 
                                class="px-5 py-2.5 bg-[#00796B] hover:bg-[#00695c] text-white font-extrabold text-xs rounded-xl shadow-md transition active:scale-95"
                            >
                                Tambahkan Materi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ════════════════════════════════════════════════════════ -->
        <!-- MODAL: CATATAN SISWA KHUSUS                              -->
        <!-- ════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="showStudentNoteModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="showStudentNoteModal = false"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 sm:p-7 border border-slate-100 space-y-4 animate-in fade-in zoom-in-95">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-base sm:text-lg font-black text-slate-900">Catatan Kehadiran Siswa</h3>
                                <p class="text-xs text-teal-800 font-bold mt-0.5">{{ activeStudentForNote?.name || 'Siswa' }}</p>
                            </div>
                            <button @click="showStudentNoteModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Quick Presets -->
                        <div class="space-y-1.5 pt-1">
                            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Pilihan Cepat:</label>
                            <div class="flex flex-wrap gap-1.5">
                                <button 
                                    v-for="preset in ['Izin ke UKS', 'Dispen Lomba', 'Sakit Kepala', 'Izin Acara Keluarga', 'Terlambat']" 
                                    :key="preset"
                                    type="button"
                                    @click="setQuickNote(preset)"
                                    class="px-2.5 py-1 bg-slate-50 hover:bg-teal-50 hover:text-teal-800 hover:border-teal-300 text-slate-600 rounded-lg text-xs font-semibold border border-slate-200/80 transition"
                                >
                                    {{ preset }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-slate-700 block mb-1">Keterangan / Alasan:</label>
                            <textarea 
                                v-model="tempStudentNote" 
                                rows="3" 
                                class="w-full p-3 border-slate-200 rounded-xl text-xs sm:text-sm focus:ring-[#00796B] focus:border-[#00796B] resize-none" 
                                placeholder="Tuliskan keterangan detail..."
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-2.5 pt-2 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="showStudentNoteModal = false" 
                                class="px-4 py-2 text-slate-500 hover:bg-slate-100 font-bold text-xs rounded-xl transition"
                            >
                                Batal
                            </button>
                            <button 
                                type="button" 
                                @click="saveStudentNote" 
                                class="px-5 py-2 bg-[#00796B] hover:bg-[#00695c] text-white font-extrabold text-xs rounded-xl shadow-md transition active:scale-95"
                            >
                                Simpan Catatan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>
