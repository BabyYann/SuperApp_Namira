<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    PlusIcon, AcademicCapIcon, PencilSquareIcon, TrashIcon, 
    ExclamationTriangleIcon, ExclamationCircleIcon 
} from '@heroicons/vue/24/outline';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    academicYears: Array,
});

// State
const showModal = ref(false);
const isEditing = ref(false);

const showDeleteConfirm = ref(false);
const showActivateConfirm = ref(false); // New state for Activation Modal
const itemToDelete = ref(null);
const itemToActivate = ref(null); // New state for item to activate

const form = useForm({
    id: null,
    name: '',
    semester: 'ganjil',
    is_active: false,
});

const deleteForm = useForm({});

// Computed
const sortedYears = computed(() => {
    return [...props.academicYears].sort((a, b) => b.name.localeCompare(a.name));
});

// Methods
const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.semester = 'ganjil';
    form.is_active = false;
    showModal.value = true;
};

const openEditModal = (year) => {
    isEditing.value = true;
    form.id = year.id;
    form.name = year.name;
    form.semester = year.semester;
    form.is_active = Boolean(year.is_active);
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('yayasan.academic-years.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('yayasan.academic-years.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

// Activation Logic (Updated to use Modal)
const confirmActivate = (year) => {
    if (year.is_active) return; // Already active, do nothing
    itemToActivate.value = year;
    showActivateConfirm.value = true;
};

const closeActivateModal = () => {
    showActivateConfirm.value = false;
    setTimeout(() => {
        itemToActivate.value = null;
    }, 300);
};

const activateYear = () => {
    if (!itemToActivate.value) return;
    router.post(route('yayasan.academic-years.activate', itemToActivate.value.id), {}, {
        onSuccess: () => closeActivateModal(),
        onFinish: () => closeActivateModal(),
    });
};

const confirmDelete = (year) => {
    itemToDelete.value = year;
    showDeleteConfirm.value = true;
};

const closeDeleteModal = () => {
    showDeleteConfirm.value = false;
    itemToDelete.value = null;
};

const deleteItem = () => {
    if (!itemToDelete.value) return;
    deleteForm.delete(route('yayasan.academic-years.destroy', itemToDelete.value.id), {
        onSuccess: () => closeDeleteModal(),
    });
};
</script>

<template>
    <Head title="Tahun Akademik" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4">
                <!-- Top Row: Title & Description -->
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Tahun Akademik
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Atur periode tahun ajaran dan semester aktif.</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6">
            <!-- Toolbar: Actions -->
            <div class="flex items-center justify-end">
                 <button 
                    @click="openCreateModal"
                    class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 active:scale-95 h-[46px]"
                >
                    <PlusIcon class="w-5 h-5" />
                    <span>Buat Tahun Baru</span>
                </button>
            </div>
            
            <!-- Active Year Card (Highlight) -->
            <div v-if="academicYears.find(y => y.is_active)" class="bg-gradient-to-r from-namira-teal to-teal-600 rounded-3xl p-8 text-white shadow-xl shadow-teal-900/10 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-10">
                     <AcademicCapIcon class="w-48 h-48" />
                </div>
                <div class="relative z-10">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider backdrop-blur-sm">Sedang Aktif</span>
                    </div>
                    <h3 class="text-4xl font-extrabold mb-1">
                        {{ academicYears.find(y => y.is_active).name }}
                    </h3>
                    <p class="text-xl font-medium opacity-90 capitalize">
                        Semester {{ academicYears.find(y => y.is_active).semester }}
                    </p>
                </div>
            </div>

            <!-- List Card -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-100 text-xs uppercase text-gray-500 font-extrabold tracking-wider">
                            <th class="p-6">Tahun Ajaran</th>
                            <th class="p-6">Semester</th>
                            <th class="p-6">Status</th>
                            <th class="p-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                         <tr v-if="academicYears.length === 0">
                            <td colspan="4" class="p-12 text-center text-gray-500">Belum ada data tahun akademik.</td>
                        </tr>
                        <tr v-for="year in sortedYears" :key="year.id" class="group hover:bg-gray-50 transition-colors">
                            <td class="p-6">
                                <span class="font-bold text-gray-900 text-lg">{{ year.name }}</span>
                            </td>
                            <td class="p-6">
                                <span class="capitalize px-3 py-1 rounded-lg text-sm font-medium border" 
                                    :class="year.semester === 'ganjil' ? 'bg-orange-50 text-orange-700 border-orange-100' : 'bg-blue-50 text-blue-700 border-blue-100'">
                                    {{ year.semester }}
                                </span>
                            </td>
                            <td class="p-6">
                                <button 
                                    @click="confirmActivate(year)"
                                    type="button"
                                    class="flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold transition-all relative z-20"
                                    :class="year.is_active 
                                        ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-500/20 cursor-default' 
                                        : 'bg-gray-100 text-gray-500 hover:bg-namira-teal hover:text-white cursor-pointer hover:shadow-lg hover:shadow-namira-teal/30'"
                                >
                                    <div class="w-2 h-2 rounded-full" :class="year.is_active ? 'bg-emerald-500 animate-pulse' : 'bg-current'"></div>
                                    {{ year.is_active ? 'AKTIF' : 'Set Aktif' }}
                                </button>
                            </td>
                            <td class="p-6 text-right">
                                <div class="flex justify-end gap-2 opacity-50 group-hover:opacity-100 transition-opacity">
                                    <button @click="openEditModal(year)" class="p-2 text-gray-400 hover:text-namira-teal hover:bg-teal-50 rounded-lg">
                                        <PencilSquareIcon class="w-5 h-5" />
                                    </button>
                                    <button @click="confirmDelete(year)" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg">
                                        <TrashIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create/Edit Form Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 border border-gray-100">
                        <h3 class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-namira-teal to-blue-600 mb-6">
                            {{ isEditing ? 'Edit Tahun Akademik' : 'Buat Tahun Akademik' }}
                        </h3>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <InputLabel for="name" value="Tahun Ajaran" />
                                <TextInput id="name" v-model="form.name" class="w-full mt-1" placeholder="Contoh: 2024/2025" required autofocus />
                                <InputError :message="form.errors.name" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="semester" value="Semester" />
                                <select id="semester" v-model="form.semester" class="w-full mt-1 border-gray-300 rounded-lg focus:ring-namira-teal focus:border-namira-teal">
                                    <option value="ganjil">Ganjil</option>
                                    <option value="genap">Genap</option>
                                </select>
                                <InputError :message="form.errors.semester" class="mt-1" />
                            </div>
                            <div class="flex justify-end gap-2 mt-6">
                                <button type="button" @click="closeModal" class="px-4 py-2 text-gray-500 font-medium hover:bg-gray-50 rounded-lg">Batal</button>
                                <PrimaryButton :disabled="form.processing">{{ isEditing ? 'Simpan Perubahan' : 'Buat Tahun Baru' }}</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Activation Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showActivateConfirm" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeActivateModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 text-center transform transition-all scale-100">
                         <div class="mx-auto flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 mb-4">
                            <ExclamationCircleIcon class="h-8 w-8 text-emerald-600" />
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Aktifkan Tahun Ajaran?</h3>
                        <p class="text-sm text-gray-500 mb-6">
                            Anda akan mengaktifkan: <br>
                            <span class="font-bold text-gray-900 text-lg block my-1">"{{ itemToActivate?.name }} - {{ itemToActivate?.semester }}"</span>
                            <span class="text-emerald-600 font-medium bg-emerald-50 px-2 py-0.5 rounded inline-block text-xs mt-1">Tahun ajaran sebelumnya akan di-nonaktifkan.</span>
                        </p>
                        <div class="flex justify-center gap-3">
                            <button @click="closeActivateModal" class="px-4 py-2 bg-white border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-50">Batal</button>
                            <button @click="activateYear" class="px-4 py-2 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 shadow-lg shadow-emerald-500/30">Ya, Aktifkan</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Modal -->
         <Teleport to="body">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeDeleteModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 text-center">
                         <div class="mx-auto flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full bg-red-100 mb-4">
                            <ExclamationTriangleIcon class="h-8 w-8 text-red-600" />
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Tahun Akademik?</h3>
                        <p class="text-sm text-gray-500 mb-6">Data "{{ itemToDelete?.name }} - {{ itemToDelete?.semester }}" akan dihapus permanen.</p>
                        <div class="flex justify-center gap-3">
                            <button @click="closeDeleteModal" class="px-4 py-2 bg-white border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-50">Batal</button>
                            <button @click="deleteItem" class="px-4 py-2 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 shadow-lg shadow-red-500/30">Hapus Sekarang</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>
