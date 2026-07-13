<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { 
    MagnifyingGlassIcon, ArrowLeftIcon, UserGroupIcon, 
    AcademicCapIcon, PlusIcon, CheckIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    classroom: Object,
    availableStudents: Array,
    isHomeroomTeacher: Boolean,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const isTeacher = computed(() => user.value.is_teacher);

// State
const searchQuery = ref('');
const showAddModal = ref(false);
const availableSearch = ref('');
const selectedStudentIds = ref([]);

const addForm = useForm({
    student_ids: [],
});

// Computed
const filteredStudents = computed(() => {
    if (!searchQuery.value) return props.classroom.students;
    const lower = searchQuery.value.toLowerCase();
    return props.classroom.students.filter(s => 
        s.full_name.toLowerCase().includes(lower) || 
        (s.nis || '').includes(lower)
    );
});

const filteredAvailable = computed(() => {
    if (!availableSearch.value) return props.availableStudents;
    const lower = availableSearch.value.toLowerCase();
    return props.availableStudents.filter(s => 
        s.full_name.toLowerCase().includes(lower) || 
        (s.nis || '').includes(lower)
    );
});

const canManage = computed(() => {
    if (!isTeacher.value) return true; // Admin
    return props.isHomeroomTeacher;
});

// Methods
const openAddModal = () => {
    showAddModal.value = true;
    selectedStudentIds.value = [];
    availableSearch.value = '';
};

const closeAddModal = () => {
    showAddModal.value = false;
    selectedStudentIds.value = [];
};

const toggleSelection = (id) => {
    if (selectedStudentIds.value.includes(id)) {
        selectedStudentIds.value = selectedStudentIds.value.filter(sid => sid !== id);
    } else {
        selectedStudentIds.value.push(id);
    }
};

const addStudents = () => {
    if (selectedStudentIds.value.length === 0) return;
    addForm.student_ids = selectedStudentIds.value;
    addForm.post(route('yayasan.classrooms.add-student', props.classroom.id), {
        onSuccess: () => {
            closeAddModal();
        }
    });
};

const removeStudent = (student) => {
    if (confirm(`Keluarkan ${student.full_name} dari kelas ini?`)) {
        router.delete(route('yayasan.classrooms.remove-student', [props.classroom.id, student.id]));
    }
};
</script>

<template>
    <Head :title="`Kelas ${classroom.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('yayasan.classrooms.index')" class="p-2 rounded-xl hover:bg-white/50 text-gray-500 transition-colors">
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        {{ classroom.name }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Wali Kelas: <span class="font-bold text-namira-teal">{{ classroom.homeroom_teacher?.full_name || 'Belum ditentukan' }}</span> • Level: {{ classroom.level }}
                    </p>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6 px-4 sm:px-6 lg:px-8 pb-12">
            
            <!-- Left: Stats / Info -->
            <div class="col-span-1 lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white/80 backdrop-blur-xl p-6 rounded-3xl border border-white/50 shadow-sm flex items-center gap-4 hover:shadow-md transition-all">
                     <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl">
                        <UserGroupIcon class="w-8 h-8" />
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Total Siswa</div>
                        <div class="text-3xl font-extrabold text-gray-800">{{ classroom.students.length }}</div>
                    </div>
                </div>
                 <div class="bg-white/80 backdrop-blur-xl p-6 rounded-3xl border border-white/50 shadow-sm flex items-center gap-4 hover:shadow-md transition-all">
                     <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl">
                        <AcademicCapIcon class="w-8 h-8" />
                    </div>
                    <div>
                         <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Kapasitas</div>
                        <div class="text-3xl font-extrabold text-gray-800">Relax</div>
                    </div>
                </div>
            </div>

            <!-- Student List -->
            <div class="col-span-1 lg:col-span-3 bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
                    <h3 class="font-bold text-lg text-gray-800">Daftar Siswa</h3>
                    <div class="flex gap-3 w-full md:w-auto">
                        <div class="relative w-full md:w-64">
                            <input 
                                v-model="searchQuery"
                                type="text" 
                                placeholder="Cari nama siswa..." 
                                class="pl-10 pr-4 py-2 bg-white/50 border-white/50 rounded-xl text-sm w-full focus:border-namira-teal focus:ring-namira-teal/20 transition-all"
                            >
                            <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                        </div>
                        
                        <button 
                            v-if="canManage"
                            @click="openAddModal"
                            class="px-4 py-2 bg-namira-teal text-white rounded-xl font-bold text-sm shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all flex items-center gap-2 whitespace-nowrap active:scale-95"
                        >
                            <PlusIcon class="w-4 h-4" />
                            Tambah Siswa
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/50 text-xs uppercase text-gray-500 font-extrabold tracking-wider border-b border-gray-100">
                                <th class="p-5 w-16 text-center">No</th>
                                <th class="p-5">Foto</th>
                                <th class="p-5">Nama Lengkap</th>
                                <th class="p-5">NIS / NISN</th>
                                <th v-if="canManage" class="p-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="filteredStudents.length === 0">
                                <td :colspan="canManage ? 5 : 4" class="p-12 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <UserGroupIcon class="w-12 h-12 mb-3 opacity-50" />
                                        <p>Belum ada siswa di kelas ini.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="(student, index) in filteredStudents" :key="student.id" class="group hover:bg-teal-50/30 transition-colors">
                                <td class="p-5 text-center font-bold text-gray-400">{{ index + 1 }}</td>
                                <td class="p-5">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 overflow-hidden border-2 border-white shadow-sm">
                                        <img v-if="student.photo" :src="student.photo" class="w-full h-full object-cover">
                                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xs font-bold">
                                            {{ student.full_name.substring(0,2).toUpperCase() }}
                                        </div>
                                    </div>
                                </td>
                                <td class="p-5">
                                    <div class="font-bold text-gray-800">{{ student.full_name }}</div>
                                    <div class="text-xs text-gray-500 font-medium">{{ student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</div>
                                </td>
                                <td class="p-5">
                                    <span class="text-xs font-mono font-bold text-gray-600 bg-white border border-gray-200 px-2 py-1 rounded-lg">{{ student.nis || '-' }}</span>
                                </td>
                                <td v-if="canManage" class="p-5 text-right">
                                    <button 
                                        @click="removeStudent(student)"
                                        class="px-3 py-1.5 text-xs font-bold text-red-500 hover:bg-red-50 rounded-lg border border-transparent hover:border-red-100 transition-all opacity-0 group-hover:opacity-100"
                                    >
                                        Keluarkan
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Student Modal -->
        <Teleport to="body">
            <div v-if="showAddModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeAddModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-2xl w-full flex flex-col max-h-[85vh] transform transition-all scale-100 overflow-hidden">
                        <!-- Header -->
                        <div class="p-6 border-b border-gray-100 flex-none">
                            <h3 class="text-xl font-bold text-gray-900">Tambah Siswa ke Kelas</h3>
                            <p class="text-sm text-gray-500">Pilih satu atau lebih siswa untuk dimasukkan.</p>
                        </div>
                        
                        <!-- Body (Scrollable) -->
                        <div class="flex-1 overflow-y-auto p-6">
                            <div class="relative mb-4">
                                <input 
                                    v-model="availableSearch"
                                    type="text" 
                                    placeholder="Cari nama siswa..." 
                                    class="w-full pl-10 pr-4 py-3 border-gray-200 rounded-xl text-sm focus:border-namira-teal focus:ring-namira-teal/20 bg-gray-50/50"
                                    autofocus
                                >
                                <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            </div>

                            <div class="border border-gray-100 rounded-xl divide-y divide-gray-50 bg-white min-h-[200px]">
                                <div v-if="filteredAvailable.length === 0" class="p-12 text-center text-gray-400 text-sm">
                                    <div class="flex flex-col items-center">
                                        <AcademicCapIcon class="w-10 h-10 mb-2 opacity-50" />
                                        Tidak ada siswa tersedia (yang belum punya kelas).
                                    </div>
                                </div>
                                <div 
                                    v-for="student in filteredAvailable" 
                                    :key="student.id"
                                    @click="toggleSelection(student.id)"
                                    class="p-4 flex items-center justify-between cursor-pointer hover:bg-teal-50 transition-colors group"
                                    :class="{'bg-teal-50/50': selectedStudentIds.includes(student.id)}"
                                >
                                    <div class="flex items-center gap-4">
                                         <div 
                                            class="w-5 h-5 rounded border flex items-center justify-center transition-all"
                                            :class="selectedStudentIds.includes(student.id) ? 'bg-namira-teal border-namira-teal text-white' : 'border-gray-300 bg-white group-hover:border-namira-teal'"
                                         >
                                            <CheckIcon v-if="selectedStudentIds.includes(student.id)" class="w-3.5 h-3.5" />
                                         </div>
                                         <div class="flex items-center gap-3">
                                             <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500">
                                                {{ student.full_name.substring(0,2).toUpperCase() }}
                                             </div>
                                             <div>
                                                 <div class="font-bold text-sm text-gray-800">{{ student.full_name }}</div>
                                                 <div class="text-xs text-gray-500">{{ student.nis || 'No NIS' }}</div>
                                             </div>
                                         </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="p-6 border-t border-gray-50 bg-gray-50/50 flex-none flex justify-between items-center">
                            <div class="text-sm font-bold text-gray-500">
                                {{ selectedStudentIds.length }} Siswa dipilih
                            </div>
                            <div class="flex gap-3">
                                <button @click="closeAddModal" class="px-5 py-2.5 text-gray-500 font-bold hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
                                <PrimaryButton @click="addStudents" :disabled="selectedStudentIds.length === 0 || addForm.processing" class="rounded-xl px-6 py-2.5 shadow-lg shadow-namira-teal/30">
                                    Tambahkan
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>
