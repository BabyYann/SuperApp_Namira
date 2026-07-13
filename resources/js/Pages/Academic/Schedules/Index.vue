<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { 
    ChevronDownIcon, PlusIcon, BoltIcon, PrinterIcon, 
    ArrowPathIcon as RefreshIcon, TrashIcon, DocumentDuplicateIcon,
    CalendarIcon, PencilSquareIcon, ClockIcon, ExclamationTriangleIcon, ChevronUpDownIcon
} from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Dropdown from '@/Components/Dropdown.vue';

const props = defineProps({
    classrooms: Array,
    selectedClassroomId: Number,
    schedule: Object, // Grouped by day
    personalSchedule: Object, // Grouped by day (for teachers)
    isTeacher: Boolean,
    subjects: Array,
    teachers: Array,
});

const form = useForm({
    classroom_id: props.selectedClassroomId || '',
    subject_id: '',
    teacher_id: '',
    day: '',
    start_time: '',
    end_time: '',
});

const cloneForm = useForm({
    classroom_id: props.selectedClassroomId || '',
    from_day: '',
    to_day: '',
});

const selectedClassroom = ref(props.selectedClassroomId);
const showAddModal = ref(false);
const showCloneModal = ref(false);
const days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const isEditing = ref(false);
const editingId = ref(null);

// View Mode: 'personal' (Jadwal Saya) or 'class' (Cari Kelas)
// Default to 'personal' if teacher, otherwise 'class'
const viewMode = ref(props.isTeacher ? 'personal' : 'class');

watch(selectedClassroom, (newVal) => {
    if (newVal) {
        router.get(route('yayasan.schedules.index'), { classroom_id: newVal }, { preserveState: true, preserveScroll: true });
        form.classroom_id = newVal;
        cloneForm.classroom_id = newVal;
        // If user selects a class, switch to class view
        viewMode.value = 'class';
    }
});

const openAddModal = (item = null) => {
    if (!props.selectedClassroomId) {
        alert('Pilih kelas terlebih dahulu!');
        return;
    }
    
    form.clearErrors();

    if (item) {
        // Edit Mode
        isEditing.value = true;
        editingId.value = item.id;
        form.classroom_id = item.classroom_id;
        form.subject_id = item.subject_id;
        form.teacher_id = item.teacher_id;
        form.day = item.day;
        form.start_time = item.start_time;
        form.end_time = item.end_time;
    } else {
        // Create Mode
        isEditing.value = false;
        editingId.value = null;
        form.reset('subject_id', 'teacher_id', 'day', 'start_time', 'end_time');
        
        // Defensive Sync
        if (!form.classroom_id) {
             form.classroom_id = props.selectedClassroomId;
        }
    }
    showAddModal.value = true;
};

const openCloneModal = () => {
    if (!props.selectedClassroomId) {
        alert('Pilih kelas terlebih dahulu!');
        return;
    }
    cloneForm.reset('from_day', 'to_day');
    cloneForm.clearErrors();
    showCloneModal.value = true;
};

const submitSchedule = () => {
    if (isEditing.value) {
        form.put(route('yayasan.schedules.update', editingId.value), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                 showAddModal.value = false;
                 form.reset('subject_id', 'teacher_id', 'day', 'start_time', 'end_time');
                 isEditing.value = false;
            },
            onError: (errors) => {
                 showAddModal.value = true;
            }
        });
    } else {
        form.post(route('yayasan.schedules.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                 showAddModal.value = false;
                 form.reset('subject_id', 'teacher_id', 'day', 'start_time', 'end_time');
            },
            onError: (errors) => {
                showAddModal.value = true;
            }
        });
    }
};

const submitClone = () => {
    cloneForm.post(route('yayasan.schedules.clone'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
             showCloneModal.value = false;
             cloneForm.reset('from_day', 'to_day');
        },
        onError: () => {
            showCloneModal.value = true;
        }
    });
};

const resetSchedule = () => {
    if (!props.selectedClassroomId) return;
    if (confirm('Yakin ingin MENGHAPUS SEMUA jadwal kelas ini? Tindakan ini tidak dapat dibatalkan.')) {
        router.post(route('yayasan.schedules.reset'), { classroom_id: props.selectedClassroomId }, { preserveScroll: true });
    }
};

const deleteSchedule = (id) => {
    if (confirm('Hapus jadwal ini?')) {
        router.delete(route('yayasan.schedules.destroy', id), { preserveScroll: true });
    }
};

const printSchedule = () => {
    if (!props.selectedClassroomId) return;
    const url = route('yayasan.schedules.export-pdf', { classroom_id: props.selectedClassroomId });
    window.open(url, '_blank');
};

watch(() => props.selectedClassroomId, (newVal) => {
    if (newVal !== selectedClassroom.value) {
        selectedClassroom.value = newVal;
    }
});

const activeSchedule = computed(() => {
    if (viewMode.value === 'personal' && props.isTeacher) {
        return props.personalSchedule || {};
    }
    return props.schedule || {};
});

const getSchedulesForDay = (day) => {
    return activeSchedule.value[day] || [];
};

// Sort schedules by start_time
const sortedSchedules = (day) => {
    const list = getSchedulesForDay(day);
    if (!list || list.length === 0) return [];
    
    return list.sort((a, b) => {
        const timeA = a.start_time || '00:00';
        const timeB = b.start_time || '00:00';
        return timeA.localeCompare(timeB);
    });
};
</script>

<template>
    <Head title="Jadwal Pelajaran" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 print:hidden">
                <!-- Top Row: Title & Description -->
                <div>
                    <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400">
                        Jadwal Pelajaran
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ isTeacher ? 'Lihat jadwal mengajar Anda atau cari jadwal kelas lain.' : 'Atur jadwal pelajaran per kelas.' }}
                    </p>
                </div>
            </div>
        </template>
        
        <div class="py-6 max-w-7xl mx-auto space-y-6">
            <!-- Toolbar -->
            <div class="flex flex-col md:flex-row items-center gap-4 print:hidden">
                <!-- View Toggle for Teachers -->
                <div v-if="isTeacher" class="bg-gray-100 p-1 rounded-2xl flex items-center h-[46px]">
                    <button 
                        @click="viewMode = 'personal'"
                        class="px-4 py-2 text-xs font-bold rounded-xl transition-all h-full flex items-center"
                        :class="viewMode === 'personal' ? 'bg-white text-namira-teal shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Jadwal Saya
                    </button>
                    <button 
                        @click="viewMode = 'class'"
                        class="px-4 py-2 text-xs font-bold rounded-xl transition-all h-full flex items-center"
                        :class="viewMode === 'class' ? 'bg-white text-namira-teal shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Cari Kelas
                    </button>
                </div>

                <!-- Class Selector -->
                <div v-if="!isTeacher || viewMode === 'class'" class="relative flex-1 w-full md:w-auto">
                    <select 
                        v-model="selectedClassroom" 
                        class="appearance-none bg-white/50 backdrop-blur-sm border border-white/50 text-gray-700 text-sm rounded-2xl focus:ring-namira-teal focus:border-namira-teal block w-full pl-4 pr-10 py-2.5 shadow-sm hover:bg-white transition-all cursor-pointer h-[46px]"
                    >
                        <option :value="undefined" disabled>-- Pilih Kelas --</option>
                        <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">
                            {{ classroom.name }}
                        </option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                        <ChevronDownIcon class="h-4 w-4" />
                    </div>
                </div>

                <!-- Actions (Only for Admin/Yayasan) -->
                <template v-if="!isTeacher">
                    <!-- Smart Tools Dropdown -->
                    <Dropdown align="right" width="48" v-if="selectedClassroomId">
                        <template #trigger>
                            <button class="px-4 py-2.5 bg-white/50 backdrop-blur-sm border border-white/50 text-gray-600 rounded-2xl font-bold hover:bg-white flex items-center gap-2 transition-all shadow-sm h-[46px]">
                                <span>Aksi</span>
                                <BoltIcon class="h-4 w-4" />
                            </button>
                        </template>
                        <template #content>
                            <button @click="openCloneModal" class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 gap-2">
                                <DocumentDuplicateIcon class="w-4 h-4" />
                                Clone Jadwal
                            </button>
                                <button @click="printSchedule" class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 gap-2">
                                <PrinterIcon class="w-4 h-4" />
                                Cetak PDF
                            </button>
                            <div class="border-t border-gray-100"></div>
                            <button @click="resetSchedule" class="flex w-full items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 gap-2">
                                <TrashIcon class="w-4 h-4" />
                                Reset Jadwal
                            </button>
                        </template>
                    </Dropdown>

                    <button 
                        @click="openAddModal"
                        class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 transition-all flex items-center gap-2 transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed h-[46px]"
                        :class="{'opacity-50 cursor-not-allowed': !selectedClassroomId}"
                        :disabled="!selectedClassroomId"
                    >
                        <PlusIcon class="w-5 h-5" />
                        <span>Tambah</span>
                    </button>
                </template>
                
                <!-- Print Button for Teacher -->
                <button 
                    v-if="isTeacher && selectedClassroomId && viewMode === 'class'"
                    @click="printSchedule"
                    class="px-4 py-2.5 bg-white/50 backdrop-blur-sm border border-white/50 text-gray-600 rounded-2xl font-bold hover:bg-white flex items-center gap-2 transition-all shadow-sm h-[46px]"
                >
                    <PrinterIcon class="w-5 h-5" />
                    Cetak
                </button>
            </div>

            <!-- Print Header -->
            <div class="hidden print:block text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Jadwal Pelajaran</h1>
                <p class="text-xl text-gray-600 mt-2">
                    {{ viewMode === 'personal' && isTeacher ? 'Jadwal Mengajar Saya' : (classrooms.find(c => c.id === selectedClassroomId)?.name || 'Kelas') }}
                </p>
            </div>

            <div v-if="!selectedClassroomId && viewMode === 'class'" class="flex flex-col items-center justify-center min-h-[400px] text-center p-8">
                <div class="bg-white/50 backdrop-blur-xl p-6 rounded-full shadow-sm mb-6 animate-bounce-slow">
                    <div class="bg-indigo-50 p-6 rounded-full">
                            <CalendarIcon class="w-16 h-16 text-indigo-400" />
                    </div>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Pilih Kelas Terlebih Dahulu</h3>
                <p class="text-gray-500 max-w-md mx-auto">Silakan pilih kelas pada menu dropdown di atas untuk menampilkan jadwal pelajaran minggu ini.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 print:grid-cols-3 print:gap-6">
                <!-- Day Column -->
                <div v-for="day in days" :key="day" class="flex flex-col gap-3 break-inside-avoid">
                    <!-- Day Header -->
                    <div class="bg-white/80 backdrop-blur-xl p-3 rounded-xl shadow-sm border border-white/50 text-center sticky top-0 z-10 print:border-black print:shadow-none">
                        <h3 class="font-bold text-gray-800 text-lg">{{ day }}</h3>
                        <div class="h-1 w-8 bg-namira-teal rounded-full mx-auto mt-1 print:bg-black"></div>
                    </div>

                    <!-- Empty State for Day -->
                    <div v-if="sortedSchedules(day).length === 0" class="flex-1 bg-white/40 backdrop-blur-sm rounded-xl border-2 border-dashed border-white/50 flex flex-col items-center justify-center min-h-[120px] p-4 text-center group hover:bg-white/60 transition-colors print:hidden">
                        <div class="bg-gray-100 rounded-full p-2 mb-2 group-hover:scale-110 transition-transform">
                            <ClockIcon class="w-5 h-5 text-gray-400" />
                        </div>
                        <p class="text-xs font-bold text-gray-400">Tidak ada jadwal</p>
                        <p class="text-[10px] text-gray-400/80">Santai sejenak ☕</p>
                    </div>

                    <!-- Schedule Cards -->
                    <div 
                        v-for="item in sortedSchedules(day)" 
                        :key="item.id" 
                        class="bg-white/80 backdrop-blur-xl p-3 rounded-xl shadow-sm border border-white/50 hover:shadow-md transition-all relative group print:border-black print:shadow-none"
                        :class="{'ring-2 ring-namira-teal/20': isTeacher && viewMode === 'personal'}"
                    >
                        <!-- Time -->
                        <div class="flex items-center gap-2 mb-2">
                            <div class="px-2 py-0.5 bg-namira-teal/10 text-namira-teal text-[10px] font-bold rounded-lg border border-namira-teal/20 print:border-black print:text-black print:bg-transparent">
                                {{ item.start_time.substring(0, 5) }} - {{ item.end_time.substring(0, 5) }}
                            </div>
                            <!-- Show Class Name if in Personal Mode -->
                            <div v-if="viewMode === 'personal'" class="px-2 py-0.5 bg-amber-100 text-amber-700 text-[10px] font-bold rounded-lg border border-amber-200">
                                {{ item.classroom?.name }}
                            </div>
                        </div>

                        <!-- Subject -->
                        <h4 class="font-bold text-gray-800 text-sm leading-tight mb-1">{{ item.subject?.name }}</h4>
                        
                        <!-- Teacher (Only show if NOT in personal mode, or if we want to be explicit) -->
                        <div v-if="viewMode !== 'personal'" class="flex items-center gap-2 mt-2 pt-2 border-t border-gray-100 print:border-gray-200">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-[10px] font-bold text-gray-500 print:hidden">
                                {{ item.teacher?.full_name?.charAt(0) || '?' }}
                            </div>
                            <p class="text-xs text-gray-500 truncate">{{ item.teacher?.full_name }}</p>
                        </div>

                        <!-- Actions (Only for Admin/Yayasan) -->
                        <div v-if="!isTeacher" class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity print:hidden">
                            <button 
                                @click="openAddModal(item)"
                                class="p-1 text-gray-400 hover:text-namira-teal bg-white rounded shadow-sm"
                                title="Edit Jadwal"
                            >
                                <PencilSquareIcon class="w-3.5 h-3.5" />
                            </button>
                            <button 
                                @click="deleteSchedule(item.id)"
                                class="p-1 text-gray-400 hover:text-red-500 bg-white rounded shadow-sm"
                                title="Hapus Jadwal"
                            >
                                <TrashIcon class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal (Only for Admin) -->
        <Modal :show="showAddModal" @close="showAddModal = false" max-width="md">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">{{ isEditing ? 'Edit Jadwal Pelajaran' : 'Tambah Jadwal Pelajaran' }}</h2>
                
                <div class="space-y-4">
                    <!-- Day -->
                     <div>
                        <InputLabel for="day" value="Hari" />
                        <select id="day" v-model="form.day" class="w-full mt-1 h-12 px-4 border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-sm">
                            <option value="">-- Pilih Hari --</option>
                            <option v-for="day in days" :key="day" :value="day">{{ day }}</option>
                        </select>
                         <InputError :message="form.errors.day" class="mt-1" />
                    </div>

                    <!-- Time Range -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                             <InputLabel for="start_time" value="Jam Mulai" />
                             <input type="time" id="start_time" v-model="form.start_time" class="w-full mt-1 h-12 px-4 border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-sm" />
                        </div>
                        <div>
                             <InputLabel for="end_time" value="Jam Selesai" />
                             <input type="time" id="end_time" v-model="form.end_time" class="w-full mt-1 h-12 px-4 border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-sm" />
                             <InputError :message="form.errors.end_time" class="mt-1" />
                        </div>
                    </div>

                    <!-- Subject -->
                    <div>
                        <InputLabel for="subject" value="Mata Pelajaran" />
                        <select id="subject" v-model="form.subject_id" class="w-full mt-1 h-12 px-4 border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-sm">
                            <option value="">-- Pilih Mapel --</option>
                            <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                                {{ subject.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.subject_id" class="mt-1" />
                    </div>

                    <!-- Teacher -->
                    <div>
                        <InputLabel for="teacher" value="Guru Pengampu" />
                         <select id="teacher" v-model="form.teacher_id" class="w-full mt-1 h-12 px-4 border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-sm">
                            <option value="">-- Pilih Guru --</option>
                            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                                {{ teacher.full_name }}
                            </option>
                        </select>
                         <InputError :message="form.errors.teacher_id" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showAddModal = false">Batal</SecondaryButton>
                    <PrimaryButton @click="submitSchedule" :disabled="form.processing">
                        {{ isEditing ? 'Simpan Perubahan' : 'Simpan' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Clone Modal (Only for Admin) -->
        <Modal :show="showCloneModal" @close="showCloneModal = false" max-width="sm">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Clone Jadwal (Copy-Paste)</h2>
                <p class="text-sm text-gray-500 mb-4">Salin semua jadwal dari satu hari ke hari lain. Jadwal yang bentrok akan diabaikan.</p>

                <div class="space-y-4">
                     <div>
                        <InputLabel for="from_day" value="Dari Hari" />
                        <select id="from_day" v-model="cloneForm.from_day" class="w-full mt-1 h-12 px-4 border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-sm">
                            <option value="">-- Pilih Hari Asal --</option>
                            <option v-for="day in days" :key="day" :value="day">{{ day }}</option>
                        </select>
                        <InputError :message="cloneForm.errors.from_day" class="mt-1" />
                    </div>

                     <div>
                        <InputLabel for="to_day" value="Ke Hari" />
                        <select id="to_day" v-model="cloneForm.to_day" class="w-full mt-1 h-12 px-4 border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-sm">
                            <option value="">-- Pilih Hari Tujuan --</option>
                            <option v-for="day in days" :key="day" :value="day">{{ day }}</option>
                        </select>
                        <InputError :message="cloneForm.errors.to_day" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                     <SecondaryButton @click="showCloneModal = false">Batal</SecondaryButton>
                    <PrimaryButton @click="submitClone" :disabled="cloneForm.processing">Clone Sekarang</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
