<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    MegaphoneIcon, DocumentTextIcon, ClipboardDocumentIcon, ArrowLeftIcon, 
    UserIcon, UserGroupIcon, CalendarDaysIcon, PlusIcon, PaperClipIcon, 
    VideoCameraIcon, LinkIcon, BookOpenIcon, TrophyIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    lmsClassroom: Object,
    stream: Array,
    students: Array,
});

const activeTab = ref('stream'); // 'stream' or 'students'

// Forms
const annForm = useForm({
    content: '',
});

const materialForm = useForm({
    title: '',
    description: '',
    status: 'published',
    file: null,
    file_url: '',
});

const assignmentForm = useForm({
    title: '',
    description: '',
    due_date: '',
    max_score: 100,
    status: 'published',
});

// Modals
const showMaterialModal = ref(false);
const showAssignmentModal = ref(false);

const postAnnouncement = () => {
    annForm.post(route('lms.teacher.classrooms.announcements.store', props.lmsClassroom.id), {
        onSuccess: () => {
            annForm.reset();
        }
    });
};

const handleMaterialFile = (e) => {
    materialForm.file = e.target.files[0];
};

const postMaterial = () => {
    materialForm.post(route('lms.teacher.classrooms.materials.store', props.lmsClassroom.id), {
        onSuccess: () => {
            showMaterialModal.value = false;
            materialForm.reset();
        }
    });
};

const postAssignment = () => {
    assignmentForm.post(route('lms.teacher.classrooms.assignments.store', props.lmsClassroom.id), {
        onSuccess: () => {
            showAssignmentModal.value = false;
            assignmentForm.reset();
        }
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head :title="lmsClassroom.subject?.name + ' - ' + lmsClassroom.classroom?.name" />

    <AuthenticatedLayout>
        <!-- Header -->
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('lms.teacher.classrooms.index')" class="p-2 bg-white dark:bg-gray-800 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition shadow-sm border border-gray-100 dark:border-gray-700">
                    <ArrowLeftIcon class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                </Link>
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        {{ lmsClassroom.subject?.name }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Kelas Virtual: {{ lmsClassroom.classroom?.name }} | Pengajar: {{ lmsClassroom.teacher?.full_name }}</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6">
            <!-- Tabs Menu -->
            <div class="flex justify-between items-center bg-white/80 backdrop-blur-xl px-4 py-3 rounded-2xl border border-white/60 shadow-sm">
                <div class="flex items-center gap-2">
                    <button 
                        @click="activeTab = 'stream'"
                        :class="['px-5 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all', activeTab === 'stream' ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50']"
                    >
                        Forum & Linimasa
                    </button>
                    <button 
                        @click="activeTab = 'students'"
                        :class="['px-5 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all', activeTab === 'students' ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50']"
                    >
                        Daftar Siswa ({{ students.length }})
                    </button>
                </div>

                <div>
                    <Link :href="route('lms.teacher.classrooms.gradebook', lmsClassroom.id)" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-md shadow-amber-500/20 flex items-center gap-1.5 active:scale-95">
                        <TrophyIcon class="w-4 h-4" />
                        <span>Buku Nilai (Gradebook)</span>
                    </Link>
                </div>
            </div>

            <!-- TAB 1: STREAM -->
            <div v-if="activeTab === 'stream'" class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Left Widgets (LMS Info) -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white/85 border border-white/60 rounded-3xl p-6 shadow-sm">
                        <h4 class="font-bold text-gray-900 mb-4 text-sm border-b border-gray-50 pb-2">Informasi Kelas</h4>
                        <ul class="space-y-3.5 text-xs text-gray-500 font-medium">
                            <li class="flex items-center gap-2">
                                <BookOpenIcon class="w-4 h-4 text-emerald-600 shrink-0" />
                                <span>Mata Pelajaran: <strong class="text-gray-800">{{ lmsClassroom.subject?.name }}</strong></span>
                            </li>
                            <li class="flex items-center gap-2">
                                <UserIcon class="w-4 h-4 text-amber-500 shrink-0" />
                                <span>Guru Pengampu: <strong class="text-gray-800">{{ lmsClassroom.teacher?.full_name }}</strong></span>
                            </li>
                            <li class="flex items-center gap-2">
                                <UserGroupIcon class="w-4 h-4 text-indigo-500 shrink-0" />
                                <span>Siswa Terdaftar: <strong class="text-gray-800">{{ students.length }} Orang</strong></span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Main Feed -->
                <div class="lg:col-span-3 space-y-6">
                    <!-- Posting Box -->
                    <div class="bg-white/85 border border-white/60 rounded-3xl p-5 shadow-sm space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-xs uppercase shadow-inner">
                                G
                            </div>
                            <span class="text-xs font-bold text-gray-700">Posting pengumuman kelas baru...</span>
                        </div>
                        <form @submit.prevent="postAnnouncement" class="space-y-3">
                            <textarea 
                                v-model="annForm.content" 
                                placeholder="Bagikan pengumuman atau instruksi pembelajaran terbaru ke seluruh siswa kelas..."
                                rows="3"
                                class="w-full border border-gray-100 rounded-2xl p-4 text-sm focus:border-emerald-700 focus:ring focus:ring-emerald-700/10 transition shadow-inner"
                                required
                            ></textarea>
                            <div class="flex justify-between items-center pt-2">
                                <!-- Extra Action Links -->
                                <div class="flex gap-2">
                                    <button type="button" @click="showMaterialModal = true" class="px-4 py-2 border border-emerald-100 text-emerald-700 rounded-xl hover:bg-emerald-50 font-bold text-xs transition flex items-center gap-1.5 active:scale-95">
                                        <PlusIcon class="w-4 h-4" />
                                        <span>Materi Baru</span>
                                    </button>
                                    <button type="button" @click="showAssignmentModal = true" class="px-4 py-2 border border-amber-100 text-amber-600 rounded-xl hover:bg-amber-50 font-bold text-xs transition flex items-center gap-1.5 active:scale-95">
                                        <PlusIcon class="w-4 h-4" />
                                        <span>Tugas Baru</span>
                                    </button>
                                </div>

                                <button type="submit" class="px-6 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-bold shadow-md shadow-emerald-700/20 transition text-xs uppercase tracking-wider active:scale-95" :disabled="annForm.processing">
                                    Posting
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Stream List -->
                    <div class="space-y-4">
                        <div v-for="item in stream" :key="item.type + '-' + item.id" class="bg-white/85 border border-white/60 rounded-3xl p-6 shadow-sm flex gap-4 hover:shadow-md transition-shadow relative">
                            <!-- Stream Type Icon -->
                            <div class="shrink-0 w-11 h-11 rounded-2xl flex items-center justify-center shadow-sm"
                                 :class="[
                                     item.type === 'announcement' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100/50' : '',
                                     item.type === 'material' ? 'bg-indigo-50 text-indigo-700 border border-indigo-100/50' : '',
                                     item.type === 'assignment' ? 'bg-amber-50 text-amber-600 border border-amber-100/50' : ''
                                 ]"
                            >
                                <MegaphoneIcon v-if="item.type === 'announcement'" class="w-5 h-5" />
                                <BookOpenIcon v-else-if="item.type === 'material'" class="w-5 h-5" />
                                <ClipboardDocumentIcon v-else-if="item.type === 'assignment'" class="w-5 h-5" />
                            </div>

                            <!-- Stream Item Content -->
                            <div class="flex-1 min-w-0 space-y-2">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">
                                            {{ item.type === 'announcement' ? 'Pengumuman' : '' }}
                                            {{ item.type === 'material' ? 'Materi Pelajaran' : '' }}
                                            {{ item.type === 'assignment' ? 'Tugas Siswa' : '' }}
                                        </span>
                                        <h3 class="text-sm font-bold text-gray-900 mt-0.5 leading-snug">
                                            {{ item.type === 'announcement' ? 'Pengumuman Guru' : item.title }}
                                        </h3>
                                    </div>
                                    <span class="text-[10px] text-gray-400 font-semibold">{{ formatDate(item.created_at) }}</span>
                                </div>

                                <!-- Text Body -->
                                <p class="text-xs text-gray-600 leading-relaxed font-light whitespace-pre-wrap">
                                    {{ item.type === 'announcement' ? item.content : item.description }}
                                </p>

                                <!-- Attachments (For Materials) -->
                                <div v-if="item.type === 'material' && item.files && item.files.length > 0" class="pt-2">
                                    <div v-for="file in item.files" :key="file.id" class="inline-flex items-center gap-2 p-2 bg-gray-50 border border-gray-100 rounded-xl max-w-sm mr-2 mb-2">
                                        <!-- Attachment type icon -->
                                        <PaperClipIcon v-if="file.file_type !== 'youtube' && file.file_type !== 'link'" class="w-4 h-4 text-gray-400 shrink-0" />
                                        <VideoCameraIcon v-else-if="file.file_type === 'youtube'" class="w-4 h-4 text-red-500 shrink-0" />
                                        <LinkIcon v-else class="w-4 h-4 text-teal-500 shrink-0" />

                                        <a :href="file.file_path.startsWith('http') ? file.file_path : '/' + file.file_path" target="_blank" class="text-xs text-gray-700 font-bold hover:text-emerald-700 truncate max-w-[200px]">
                                            {{ file.file_name }}
                                        </a>
                                    </div>
                                </div>

                                <!-- Meta Info (For Assignments) -->
                                <div v-if="item.type === 'assignment'" class="pt-3 border-t border-gray-50 flex items-center justify-between">
                                    <div class="flex items-center gap-4 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                                        <span class="flex items-center gap-1"><CalendarDaysIcon class="w-3.5 h-3.5 text-amber-500" /> Deadline: {{ formatDate(item.due_date) }}</span>
                                        <span>Max Skor: {{ item.max_score }}</span>
                                    </div>

                                    <Link :href="route('lms.teacher.classrooms.assignments.submissions', { classroom: lmsClassroom.id, assignment: item.id })" class="px-4 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg text-[10px] font-bold uppercase tracking-widest transition-colors">
                                        Nilai Pengumpulan &rarr;
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Empty Feed -->
                        <div v-if="stream.length === 0" class="bg-white/85 border border-white/60 rounded-3xl p-10 text-center text-gray-400 shadow-sm">
                            <MegaphoneIcon class="w-10 h-10 mx-auto opacity-30 mb-3" />
                            <p class="text-sm font-semibold">Linimasa Kosong.</p>
                            <p class="text-xs mt-1">Gunakan formulir di atas untuk membagikan info atau materi belajar perdana.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: STUDENTS -->
            <div v-else-if="activeTab === 'students'" class="bg-white/85 border border-white/60 rounded-3xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/50">
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider">NIS</th>
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider">Nama Lengkap</th>
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider">Gender</th>
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider">User Account Email</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="student in students" :key="student.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="p-5 text-xs font-bold text-gray-700">{{ student.nis || '-' }}</td>
                                <td class="p-5 text-sm font-bold text-gray-900">{{ student.full_name }}</td>
                                <td class="p-5 text-xs text-gray-500">{{ student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                                <td class="p-5 text-xs text-gray-500 font-medium">{{ student.user?.email || '-' }}</td>
                            </tr>
                            <tr v-if="students.length === 0">
                                <td colspan="4" class="p-10 text-center text-gray-400">
                                    <UserGroupIcon class="w-10 h-10 mx-auto opacity-30 mb-2" />
                                    <p class="text-xs font-bold">Belum ada siswa yang masuk ke kelas ini.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL: ADD MATERIAL -->
        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-4">
            <div v-if="showMaterialModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 relative">
                    <h3 class="font-bold text-lg text-gray-900 border-b border-gray-50 pb-3 mb-5">Unggah Materi Pelajaran Baru</h3>
                    
                    <form @submit.prevent="postMaterial" class="space-y-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Judul Materi</label>
                            <input 
                                v-model="materialForm.title" 
                                type="text"
                                class="w-full border border-gray-200 rounded-xl p-3 text-sm focus:border-emerald-700 focus:ring focus:ring-emerald-700/10 transition"
                                required
                                placeholder="Contoh: Pengenalan Aljabar Linear"
                            />
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Deskripsi / Arahan</label>
                            <textarea 
                                v-model="materialForm.description" 
                                class="w-full border border-gray-200 rounded-xl p-3 text-sm focus:border-emerald-700 focus:ring focus:ring-emerald-700/10 transition"
                                rows="3"
                                placeholder="Jelaskan isi materi atau instruksi belajar singkat..."
                            ></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Lampirkan Berkas</label>
                                <input 
                                    type="file" 
                                    @change="handleMaterialFile"
                                    class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Atau Link / YouTube URL</label>
                                <input 
                                    v-model="materialForm.file_url" 
                                    type="url"
                                    class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:border-emerald-700 focus:ring focus:ring-emerald-700/10 transition"
                                    placeholder="https://..."
                                />
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-50 mt-6">
                            <button type="button" @click="showMaterialModal = false" class="px-5 py-2 text-xs font-bold text-gray-500 hover:bg-gray-50 rounded-xl">Batal</button>
                            <button type="submit" class="px-5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-bold shadow-md shadow-emerald-700/20 text-xs uppercase tracking-wider" :disabled="materialForm.processing">Unggah Materi</button>
                        </div>
                    </form>
                </div>
            </div>
        </transition>

        <!-- MODAL: ADD ASSIGNMENT -->
        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-4">
            <div v-if="showAssignmentModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 relative">
                    <h3 class="font-bold text-lg text-gray-900 border-b border-gray-50 pb-3 mb-5">Terbitkan Tugas Baru</h3>
                    
                    <form @submit.prevent="postAssignment" class="space-y-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Judul Tugas</label>
                            <input 
                                v-model="assignmentForm.title" 
                                type="text"
                                class="w-full border border-gray-200 rounded-xl p-3 text-sm focus:border-emerald-700 focus:ring focus:ring-emerald-700/10 transition"
                                required
                                placeholder="Contoh: Latihan Perkalian Halaman 30"
                            />
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Deskripsi & Petunjuk Pengerjaan</label>
                            <textarea 
                                v-model="assignmentForm.description" 
                                class="w-full border border-gray-200 rounded-xl p-3 text-sm focus:border-emerald-700 focus:ring focus:ring-emerald-700/10 transition"
                                rows="3"
                                placeholder="Tuliskan petunjuk pengerjaan tugas secara rinci..."
                            ></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Batas Waktu (Deadline)</label>
                                <input 
                                    v-model="assignmentForm.due_date" 
                                    type="datetime-local"
                                    class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:border-emerald-700 focus:ring focus:ring-emerald-700/10 transition"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Skor Maksimal</label>
                                <input 
                                    v-model="assignmentForm.max_score" 
                                    type="number"
                                    min="0"
                                    max="100"
                                    class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:border-emerald-700 focus:ring focus:ring-emerald-700/10 transition"
                                    required
                                />
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-50 mt-6">
                            <button type="button" @click="showAssignmentModal = false" class="px-5 py-2 text-xs font-bold text-gray-500 hover:bg-gray-50 rounded-xl">Batal</button>
                            <button type="submit" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold shadow-md shadow-amber-500/20 text-xs uppercase tracking-wider" :disabled="assignmentForm.processing">Terbitkan Tugas</button>
                        </div>
                    </form>
                </div>
            </div>
        </transition>
    </AuthenticatedLayout>
</template>
