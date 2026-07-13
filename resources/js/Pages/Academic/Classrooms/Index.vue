<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { 
    MagnifyingGlassIcon, PlusIcon, ExclamationCircleIcon, 
    ArchiveBoxXMarkIcon, AcademicCapIcon, PencilSquareIcon, 
    TrashIcon, ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    classrooms: {
        type: Array,
        default: () => [],
    },
    teachers: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const isTeacher = computed(() => user.value.is_teacher);
const teacherId = computed(() => user.value.teacher_profile?.id);

// State
const searchQuery = ref('');
const showModal = ref(false);
const isEditing = ref(false);
const showDeleteConfirm = ref(false);
const itemToDelete = ref(null);

const form = useForm({
    id: null,
    name: '',
    level: '',
    homeroom_teacher_id: '',
});

const deleteForm = useForm({});

// Computed
const filteredClassrooms = computed(() => {
    const all = props.classrooms || [];
    if (!searchQuery.value) return all;
    
    const lower = searchQuery.value.toLowerCase();
    return all.filter(c => 
        (c.name || '').toLowerCase().includes(lower) || 
        (c.level || '').toLowerCase().includes(lower) ||
        (c.homeroom_teacher?.full_name || '').toLowerCase().includes(lower)
    );
});

// Methods
const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.level = ''; // Default select
    showModal.value = true;
};

const openEditModal = (classroom) => {
    isEditing.value = true;
    form.id = classroom.id;
    form.name = classroom.name;
    form.level = classroom.level;
    form.homeroom_teacher_id = classroom.homeroom_teacher_id;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('yayasan.classrooms.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('yayasan.classrooms.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDelete = (classroom) => {
    itemToDelete.value = classroom;
    showDeleteConfirm.value = true;
};

const closeDeleteModal = () => {
    showDeleteConfirm.value = false;
    itemToDelete.value = null;
};

const deleteItem = () => {
    if (!itemToDelete.value) return;
    deleteForm.delete(route('yayasan.classrooms.destroy', itemToDelete.value.id), {
        onSuccess: () => closeDeleteModal(),
    });
};

const canManageClass = (classroom) => {
    if (!isTeacher.value) return true; // Admin can manage all
    return classroom.homeroom_teacher_id === teacherId.value;
};
</script>

<template>
    <Head title="Manajemen Kelas" />

    <AuthenticatedLayout>
        <template #header>
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Manajemen Kelas
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Kelas bersifat permanen dan dapat digunakan untuk semua tahun ajaran
                    </p>
                </div>
        </template>

        <!-- Main Content -->
        <div class="py-6 max-w-7xl mx-auto pb-20 space-y-6">
            <!-- Toolbar -->
            <div class="flex flex-col md:flex-row items-center gap-4">
                    <!-- Search Bar -->
                    <div class="relative group flex-1 w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 group-focus-within:text-namira-teal transition-colors">
                            <MagnifyingGlassIcon class="w-5 h-5" />
                        </div>
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Cari Kelas..." 
                            class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                        >
                    </div>

                    <!-- Add Button -->
                    <button 
                        v-if="!isTeacher"
                        @click="openCreateModal"
                        class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 whitespace-nowrap active:scale-95 h-[46px]"
                    >
                        <PlusIcon class="w-5 h-5" />
                        <span>Buat Kelas</span>
                    </button>
            </div>



            <!-- Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                 <!-- Empty State -->
                 <div v-if="filteredClassrooms.length === 0" class="col-span-full bg-white/80 backdrop-blur-xl rounded-3xl p-12 text-center border border-white/50 shadow-sm">
                    <div class="flex flex-col items-center justify-center text-gray-400">
                        <ArchiveBoxXMarkIcon class="w-16 h-16 mb-4 opacity-50" />
                        <h3 class="text-lg font-bold text-gray-900">Belum ada data kelas.</h3>
                        <p class="text-sm">Silakan buat kelas baru untuk tahun ajaran aktif.</p>
                    </div>
                </div>

                <!-- Class Cards -->
                <div v-for="classroom in filteredClassrooms" :key="classroom.id" class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 p-6 hover:shadow-xl hover:shadow-namira-teal/10 hover:-translate-y-1 transition-all duration-300 group relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1.5 h-full bg-gradient-to-b from-namira-teal to-teal-600"></div>
                    
                    <div class="flex justify-between items-start mb-4 pl-2">
                        <div>
                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Kelas</div>
                            <Link :href="route('yayasan.classrooms.show', classroom.id)" class="text-2xl font-extrabold text-gray-800 tracking-tight hover:text-namira-teal transition-colors">
                                {{ classroom?.name }}
                            </Link>
                            <div class="mt-2">
                                <span class="inline-block px-2.5 py-1 rounded-lg bg-gray-100/80 text-gray-600 text-xs font-bold border border-gray-200">
                                    Level: {{ classroom?.level }}
                                </span>
                            </div>
                        </div>
                         <div class="p-3 bg-gradient-to-br from-blue-50 to-blue-100 text-blue-600 rounded-2xl shadow-inner">
                            <AcademicCapIcon class="w-6 h-6" />
                        </div>
                    </div>

                    <div class="border-t border-gray-100 my-4 pt-4 pl-2">
                        <div class="flex items-center gap-3">
                             <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-gray-500 font-bold text-sm border border-white shadow-sm">
                                {{ classroom?.homeroom_teacher?.full_name ? classroom.homeroom_teacher.full_name.charAt(0) : '?' }}
                             </div>
                             <div>
                                 <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Wali Kelas</div>
                                 <div class="text-sm font-bold text-gray-700 line-clamp-1">
                                    {{ classroom?.homeroom_teacher?.full_name || '-' }}
                                 </div>
                             </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 mt-4 pl-2 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-2 group-hover:translate-y-0">
                        <Link 
                            :href="route('yayasan.classrooms.show', classroom.id)"
                            class="w-full py-2.5 bg-namira-teal text-white rounded-xl font-bold text-sm text-center shadow-lg shadow-namira-teal/20 hover:bg-teal-600 transition-all active:scale-95"
                        >
                            {{ canManageClass(classroom) ? 'Kelola Siswa' : 'Lihat Detail' }}
                        </Link>
                        
                        <!-- Actions only for Admin or Homeroom Teacher -->
                        <div v-if="canManageClass(classroom)" class="flex justify-end gap-3 border-t border-gray-100 pt-3">
                            <button @click="openEditModal(classroom)" class="text-xs font-bold text-gray-500 hover:text-namira-teal flex items-center gap-1 transition-colors">
                                <PencilSquareIcon class="w-4 h-4" />
                                Edit
                            </button>
                            <!-- Only Admin can delete -->
                            <button v-if="!isTeacher" @click="confirmDelete(classroom)" class="text-xs font-bold text-gray-400 hover:text-red-600 flex items-center gap-1 transition-colors">
                                <TrashIcon class="w-4 h-4" />
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-8 border border-gray-100 transform transition-all scale-100">
                        <h3 class="text-xl font-bold text-gray-900 mb-1">
                            {{ isEditing ? 'Edit Kelas' : 'Buat Kelas Baru' }}
                        </h3>
                        <p class="text-sm text-gray-500 mb-6">Pastikan nama kelas sesuai dengan format baku.</p>

                        <form @submit.prevent="submit" class="space-y-5">
                            <div>
                                <InputLabel for="name" value="Nama Kelas" class="text-sm font-bold text-gray-700" />
                                <TextInput id="name" v-model="form.name" class="w-full mt-1.5 h-12 px-4 text-base font-bold border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="Contoh: 1 Ali Bin Abi Thalib" required autofocus />
                                <InputError :message="form.errors.name" class="mt-1" />
                            </div>
                            
                            <div>
                                <InputLabel for="level" value="Tingkat / Level" class="text-sm font-bold text-gray-700" />
                                <div class="relative mt-1.5">
                                    <select id="level" v-model="form.level" class="w-full h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" required>
                                        <option value="">Pilih Tingkat...</option>
                                        <option value="1">Level 1</option>
                                        <option value="2">Level 2</option>
                                        <option value="3">Level 3</option>
                                        <option value="4">Level 4</option>
                                        <option value="5">Level 5</option>
                                        <option value="6">Level 6</option>
                                        <option value="7">Level 7</option>
                                        <option value="8">Level 8</option>
                                        <option value="9">Level 9</option>
                                        <option value="10">Level 10</option>
                                        <option value="11">Level 11</option>
                                        <option value="12">Level 12</option>
                                        <option value="TK-A">TK A</option>
                                        <option value="TK-B">TK B</option>
                                        <option value="KB">Kelompok Bermain</option>
                                    </select>
                                </div>
                                <InputError :message="form.errors.level" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="teacher" value="Wali Kelas" class="text-sm font-bold text-gray-700" />
                                <select id="teacher" v-model="form.homeroom_teacher_id" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50">
                                    <option value="">-- Pilih Guru --</option>
                                    <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                                        {{ teacher.full_name }}
                                    </option>
                                </select>
                                <p class="text-xs text-gray-400 mt-1.5" v-if="teachers.length === 0">Belum ada data guru di unit ini.</p>
                                <InputError :message="form.homeroom_teacher_id" class="mt-1" />
                            </div>

                            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-50">
                                <button type="button" @click="closeModal" class="px-5 py-2.5 text-gray-500 font-bold hover:bg-gray-50 rounded-xl transition-colors">Batal</button>
                                <PrimaryButton :disabled="form.processing" class="rounded-xl px-6 py-2.5 shadow-lg shadow-namira-teal/30">{{ isEditing ? 'Simpan' : 'Buat Kelas' }}</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Modal -->
         <Teleport to="body">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeDeleteModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 text-center border border-gray-100 transform transition-all scale-100">
                         <div class="mx-auto flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full bg-red-50 mb-4">
                             <ExclamationTriangleIcon class="h-8 w-8 text-red-500" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Hapus Kelas?</h3>
                        <p class="text-sm text-gray-500 mb-6">Kelas <span class="font-bold text-gray-800">"{{ itemToDelete?.name }}"</span> akan dihapus selamanya. Siswa di dalamnya akan kehilangan kelas.</p>
                        <div class="flex justify-center gap-3">
                            <button @click="closeDeleteModal" class="px-5 py-2.5 bg-white border border-gray-200 rounded-xl font-bold text-gray-600 hover:bg-gray-50 transition-colors">Batal</button>
                            <button @click="deleteItem" class="px-5 py-2.5 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 shadow-lg shadow-red-500/30 transition-all">Hapus Sekarang</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>
