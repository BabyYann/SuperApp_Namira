<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { 
    MagnifyingGlassIcon, PlusIcon, PencilSquareIcon, TrashIcon, 
    ExclamationTriangleIcon, BookOpenIcon, LightBulbIcon, XMarkIcon, ArrowPathIcon
} from '@heroicons/vue/24/outline';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    chapters: Object, // Paginator of Chapters (with TPs)
    subjects: Array,
    filters: Object,
    unit_level: String, // 'SD', 'SMP', 'SMA', 'TK', 'PAUD'
});

// ...

import { computed } from 'vue';

// Compute available grades based on Unit Level
const availableGrades = computed(() => {
    const level = props.unit_level?.toUpperCase();
    if (level === 'SD') return [1, 2, 3, 4, 5, 6];
    if (level === 'SMP') return [7, 8, 9];
    if (level === 'SMA' || level === 'SMK') return [10, 11, 12];
    if (level === 'TK' || level === 'PAUD') return ['TK A', 'TK B'];
    return [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]; // Fallback
});

// ... (inside template)


const searchQuery = ref(props.filters.search || '');
const subjectFilter = ref(props.filters.subject_id || '');
const showModal = ref(false);
const isEditing = ref(false);
const showDeleteConfirm = ref(false);
const itemToDelete = ref(null);
const isLoading = ref(false);

const form = useForm({
    id: null,
    subject_id: '',
    title: '',
    semester: '1',
    grade_level: '', // New Field
    objectives: [
        { code: '', description: '' }
    ],
});

const deleteForm = useForm({});

// Debounce Utility
const debounce = (func, wait) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
};

const performSearch = debounce((query, subjectId) => {
    isLoading.value = true;
    router.get(route('yayasan.learning-objectives.index'), 
        { search: query, subject_id: subjectId }, 
        { 
            preserveState: true, 
            replace: true, 
            onFinish: () => isLoading.value = false 
        }
    );
}, 300);

watch([searchQuery, subjectFilter], ([q, s]) => {
    performSearch(q, s);
});

// Auto-update first TP code if empty when typing title
watch(() => form.title, (newVal) => {
    if (!newVal) return;
    if (form.objectives.length === 1 && !form.objectives[0].description) {
         // Try to extract number
        const chapterMatch = newVal.match(/(\d+)/);
        if (chapterMatch) {
            form.objectives[0].code = `TP ${chapterMatch[0]}.1`;
        }
    }
});

// Helper to guess code
const getNextCode = () => {
    // Try to extract number from Chapter Title (e.g. "Bab 1" -> 1)
    const chapterMatch = form.title.match(/(\d+)/);
    const chapterNum = chapterMatch ? chapterMatch[0] : '?';
    
    // Count existing rows to determine suffix
    const nextSuffix = form.objectives.length + 1;
    
    return `TP ${chapterNum}.${nextSuffix}`;
};

// Dynamic Inputs
const addObjectiveRow = () => {
    form.objectives.push({ code: getNextCode(), description: '' });
};

const removeObjectiveRow = (index) => {
    if (form.objectives.length > 1) {
        form.objectives.splice(index, 1);
    }
};

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.semester = '1';
    form.grade_level = ''; // Reset
    form.objectives = [{ code: '', description: '' }]; // Reset loop
    if (subjectFilter.value) {
        form.subject_id = subjectFilter.value;
    }
    showModal.value = true;
};

const openEditModal = (chapter) => {
    isEditing.value = true;
    form.id = chapter.id;
    form.subject_id = chapter.subject_id;
    form.title = chapter.title;
    form.semester = chapter.semester;
    form.grade_level = chapter.grade_level; // Load existing
    
    // Map existing TPs or default to one empty row
    if (chapter.learning_objectives && chapter.learning_objectives.length > 0) {
        form.objectives = chapter.learning_objectives.map(tp => ({
            id: tp.id,
            code: tp.code,
            description: tp.description
        }));
    } else {
        form.objectives = [{ code: '', description: '' }];
    }
    
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('yayasan.learning-objectives.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('yayasan.learning-objectives.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDelete = (chapter) => {
    itemToDelete.value = chapter;
    showDeleteConfirm.value = true;
};

const closeDeleteModal = () => {
    showDeleteConfirm.value = false;
    itemToDelete.value = null;
};

const deleteItem = () => {
    if (!itemToDelete.value) return;
    deleteForm.delete(route('yayasan.learning-objectives.destroy', itemToDelete.value.id), {
        onSuccess: () => closeDeleteModal(),
    });
};
</script>

<template>
    <Head title="Tujuan Pembelajaran (TP)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4">
                <!-- Top Row: Title & Description -->
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Tujuan Pembelajaran
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Total Data: <span class="font-bold text-namira-teal">{{ chapters.total }} Bab</span>
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto">
            <div class="space-y-6">
                <!-- Toolbar -->
                <div class="flex flex-col md:flex-row items-center gap-4">
                    <!-- Search Bar -->
                    <div class="relative group flex-1 w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 group-focus-within:text-namira-teal transition-colors">
                            <MagnifyingGlassIcon v-if="!isLoading" class="w-5 h-5" />
                            <ArrowPathIcon v-else class="animate-spin h-5 w-5 text-namira-teal" />
                        </div>
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Cari Bab / TP..." 
                            class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                        >
                    </div>

                    <!-- Subject Filter -->
                    <select v-model="subjectFilter" class="px-4 py-2.5 bg-white/50 backdrop-blur-sm border border-white/50 text-gray-600 rounded-2xl text-sm font-bold focus:border-namira-teal focus:ring-namira-teal cursor-pointer shadow-sm hover:bg-white transition-all h-[46px] max-w-[200px]">
                        <option value="">Semua Mapel</option>
                        <option v-for="sub in subjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                    </select>

                    <!-- Add Button -->
                    <button 
                        @click="openCreateModal"
                        class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 whitespace-nowrap active:scale-95 h-[46px]"
                    >
                        <PlusIcon class="w-5 h-5" />
                        <span>Tambah Bab & TP</span>
                    </button>
                </div>
                <!-- Check if no data -->
                <div v-if="chapters.data.length === 0" class="text-center py-12 bg-white/80 backdrop-blur-xl rounded-3xl border border-white/50 shadow-sm">
                    <div class="flex flex-col items-center justify-center text-gray-400">
                        <BookOpenIcon class="w-16 h-16 mb-4 opacity-50" />
                        <p class="text-lg font-medium mb-2">Belum ada data Bab & TP.</p>
                        <button @click="openCreateModal" class="text-namira-teal font-bold hover:underline">Mulai Tambah Data</button>
                    </div>
                </div>

                <!-- Loop Chapters -->
                <div v-for="chapter in chapters.data" :key="chapter.id" class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden hover:shadow-md transition-all">
                    <!-- Chapter Header -->
                    <div class="bg-white/50 px-6 py-4 border-b border-white/50 flex justify-between items-center">
                        <div class="flex items-center gap-4">
                             <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-teal-50 to-teal-100 text-namira-teal flex items-center justify-center font-bold text-sm shadow-sm border border-white">
                                {{ chapter.subject?.code?.substring(0,3) || 'MP' }}
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 text-lg">{{ chapter.title }}</h3>
                                <p class="text-xs text-gray-500 flex items-center gap-2 mt-0.5">
                                    <span class="font-semibold text-gray-700">{{ chapter.subject?.name }}</span>
                                    <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                    <span v-if="chapter.grade_level" class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded-md font-bold text-[10px] border border-blue-100">Kelas {{ chapter.grade_level }}</span>
                                    <span v-if="chapter.grade_level" class="w-1 h-1 rounded-full bg-gray-300"></span>
                                    <span class="px-2 py-0.5 bg-purple-50 text-purple-600 rounded-md font-bold text-[10px] border border-purple-100">Semester {{ chapter.semester }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="openEditModal(chapter)" class="p-2 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-xl transition-all" title="Edit">
                                <PencilSquareIcon class="w-5 h-5" />
                            </button>
                            <button @click="confirmDelete(chapter)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all" title="Hapus">
                                <TrashIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <!-- TPs List -->
                    <div class="p-0">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50/50 border-b border-gray-100 text-xs text-gray-400 uppercase font-bold tracking-wider">
                                <tr>
                                    <th class="px-6 py-3 w-32">Kode</th>
                                    <th class="px-6 py-3">Deskripsi Tujuan Pembelajaran</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="tp in chapter.learning_objectives" :key="tp.id" class="hover:bg-teal-50/30 transition-colors">
                                    <td class="px-6 py-3 font-mono text-xs font-bold text-gray-600 w-32 align-top pt-4">
                                        <span class="bg-white border border-gray-200 px-2 py-1 rounded-lg shadow-sm text-namira-teal">{{ tp.code }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-gray-700 align-top leading-relaxed">
                                        {{ tp.description }}
                                    </td>
                                </tr>
                                <tr v-if="!chapter.learning_objectives?.length">
                                    <td colspan="2" class="px-6 py-8 text-center text-gray-400 text-xs italic">
                                        Belum ada detail TP untuk bab ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="chapters.links && chapters.links.length > 3" class="flex justify-center pt-4">
                     <div class="flex gap-1 bg-white/50 backdrop-blur-md p-1 rounded-xl border border-white/50 shadow-sm">
                        <template v-for="(link, k) in chapters.links" :key="k">
                            <Link 
                                v-if="link.url" 
                                :href="link.url" 
                                v-html="link.label"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                                :class="link.active ? 'bg-namira-teal text-white shadow-md' : 'text-gray-500 hover:bg-white hover:text-namira-teal'"
                            />
                             <span v-else v-html="link.label" class="px-3 py-1.5 text-gray-300 text-xs font-bold"></span>
                        </template>
                     </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-3xl w-full p-8 border border-gray-100 max-h-[90vh] flex flex-col transform transition-all scale-100">
                        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
                             <h3 class="text-2xl font-bold text-gray-900">
                                {{ isEditing ? 'Edit Bab & TP' : 'Tambah Bab & TP' }}
                            </h3>
                            <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <XMarkIcon class="w-6 h-6" />
                            </button>
                        </div>

                        <form @submit.prevent="submit" class="flex-1 overflow-y-auto pr-2 space-y-6 custom-scrollbar">
                            <!-- Helper Info -->
                            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 text-sm text-blue-700 flex gap-3 items-start">
                                <LightBulbIcon class="w-5 h-5 flex-shrink-0 mt-0.5" />
                                <div>
                                    <p class="font-bold">Tips Efisiensi:</p>
                                    <p>Anda bisa menambahkan banyak Tujuan Pembelajaran (TP) sekaligus dalam satu Bab. Pastikan kode TP unik (misal: TP 1.1, TP 1.2).</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div>
                                    <InputLabel for="subject" value="Mata Pelajaran *" />
                                    <select id="subject" v-model="form.subject_id" class="w-full mt-1 border-gray-300 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50">
                                        <option value="">-- Pilih Mapel --</option>
                                        <option v-for="sub in subjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                                    </select>
                                    <InputError :message="form.errors.subject_id" class="mt-1" />
                                </div>
                                <div>
                                    <InputLabel for="grade_level" value="Tingkat Kelas *" />
                                    <select id="grade_level" v-model="form.grade_level" class="w-full mt-1 border-gray-300 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" required>
                                        <option value="">-- Pilih Tingkat --</option>
                                        <option v-for="g in availableGrades" :key="g" :value="g">
                                            {{ typeof g === 'number' ? 'Kelas ' + g : g }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.grade_level" class="mt-1" />
                                </div>
                                <div>
                                    <InputLabel for="semester" value="Semester *" />
                                    <select id="semester" v-model="form.semester" class="w-full mt-1 border-gray-300 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50">
                                        <option value="1">Semester 1</option>
                                        <option value="2">Semester 2</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <InputLabel for="title" value="Judul Bab / Materi *" />
                                <TextInput id="title" v-model="form.title" class="w-full mt-1 font-bold text-lg" placeholder="Contoh: Bab 1 - Eksponen dan Logaritma" required />
                                <InputError :message="form.errors.title" class="mt-1" />
                            </div>

                            <!-- Dynamic TPs -->
                            <div class="space-y-4 pt-4 border-t border-gray-100">
                                <div class="flex justify-between items-center">
                                    <InputLabel value="Daftar Tujuan Pembelajaran" class="text-lg font-bold text-gray-800" />
                                    <button type="button" @click="addObjectiveRow" class="px-4 py-2 bg-namira-teal/10 text-namira-teal rounded-xl text-sm font-bold hover:bg-namira-teal/20 transition-colors flex items-center gap-2">
                                        <PlusIcon class="w-5 h-5" />
                                        Tambah TP Lain
                                    </button>
                                </div>

                                <div class="space-y-4">
                                    <div v-for="(obj, index) in form.objectives" :key="index" class="p-5 bg-gray-50 rounded-2xl border border-gray-200 relative group transition-all hover:border-namira-teal/50 hover:shadow-sm hover:bg-white">
                                        <!-- Remove Button (Top Right) -->
                                        <button 
                                            v-if="form.objectives.length > 1"
                                            type="button" 
                                            @click="removeObjectiveRow(index)" 
                                            class="absolute top-2 right-2 p-2 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-full transition-colors opacity-0 group-hover:opacity-100"
                                            title="Hapus Baris Ini"
                                        >
                                            <XMarkIcon class="w-5 h-5" />
                                        </button>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                            <!-- Kode TP -->
                                            <div class="md:col-span-3">
                                                <label :for="'code-'+index" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Kode TP</label>
                                                <input 
                                                    :id="'code-'+index"
                                                    v-model="obj.code" 
                                                    type="text" 
                                                    class="w-full border-gray-300 rounded-xl text-base font-bold text-gray-800 focus:border-namira-teal focus:ring-namira-teal py-3 px-4 bg-white"
                                                    placeholder="TP 1.1"
                                                    required
                                                >
                                            </div>

                                            <!-- Deskripsi -->
                                            <div class="md:col-span-9">
                                                <label :for="'desc-'+index" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Materi / Deskripsi</label>
                                                <textarea 
                                                    :id="'desc-'+index"
                                                    v-model="obj.description" 
                                                    rows="2" 
                                                    placeholder="Contoh: Peserta didik mampu memahami konsep..." 
                                                    class="w-full border-gray-300 rounded-xl text-base text-gray-800 focus:border-namira-teal focus:ring-namira-teal py-3 px-4 bg-white resize-none"
                                                    required
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <InputError :message="form.errors.objectives" class="mt-1" />
                            </div>

                            <div class="flex justify-between items-center pt-6 border-t border-gray-50 flex-shrink-0">
                                <span class="text-xs text-gray-400">Pastikan Kode TP unik.</span>
                                <div class="flex gap-3">
                                    <button type="button" @click="closeModal" class="px-5 py-2.5 text-gray-500 font-bold hover:bg-gray-50 rounded-xl transition-colors">Batal</button>
                                    <PrimaryButton :disabled="form.processing" class="rounded-xl px-6 py-2.5 shadow-lg shadow-namira-teal/30">{{ isEditing ? 'Simpan Perubahan' : 'Simpan Data' }}</PrimaryButton>
                                </div>
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
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Hapus Bab?</h3>
                        <p class="text-sm text-gray-500 mb-6">Bab <span class="font-bold text-gray-800">"{{ itemToDelete?.title }}"</span> dan semua TP di dalamnya akan dihapus permanen.</p>
                        <div class="flex justify-center gap-3">
                            <button @click="closeDeleteModal" class="px-5 py-2.5 bg-white border border-gray-200 rounded-xl font-bold text-gray-600 hover:bg-gray-50 transition-colors">Batal</button>
                            <button @click="deleteItem" class="px-5 py-2.5 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 shadow-lg shadow-red-500/30 transition-all">Hapus Permanen</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>
