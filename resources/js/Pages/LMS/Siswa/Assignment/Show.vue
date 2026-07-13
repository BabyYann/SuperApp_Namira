<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import NamiraLoader from '@/Components/NamiraLoader.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    ArrowLeftIcon, CalendarDaysIcon, PaperClipIcon, CloudArrowUpIcon, 
    CheckCircleIcon, ExclamationTriangleIcon, ChatBubbleBottomCenterTextIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    lmsClassroom: Object,
    assignment: Object,
    submission: Object,
});

const fileInput = ref(null);
const filePreviewName = ref(null);

const form = useForm({
    submission_text: props.submission?.submission_text || '',
    file: null,
});

const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.file = file;
        filePreviewName.value = file.name;
    }
};

const submitAssignment = () => {
    form.post(route('lms.student.classrooms.assignments.submit', {
        classroom: props.lmsClassroom.id,
        assignment: props.assignment.id
    }), {
        onSuccess: () => {
            filePreviewName.value = null;
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
    <Head :title="'Tugas: ' + assignment.title" />

    <AuthenticatedLayout>
        <!-- Header -->
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('lms.student.classrooms.show', lmsClassroom.id)" class="p-2 bg-white dark:bg-gray-800 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition shadow-sm border border-gray-100 dark:border-gray-700">
                    <ArrowLeftIcon class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                </Link>
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Tugas: {{ assignment.title }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Mata Pelajaran: {{ lmsClassroom.subject?.name }} | Kelas: {{ lmsClassroom.classroom?.name }}</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Side: Assignment Instructions -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl p-6 sm:p-8 shadow-sm border border-white/50 space-y-5">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-50 pb-4">
                        <div class="flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-wider">
                            <CalendarDaysIcon class="w-4 h-4 text-amber-500" />
                            <span>Batas Waktu: <strong class="text-gray-800">{{ formatDate(assignment.due_date) }}</strong></span>
                        </div>
                        <span class="inline-flex px-3 py-1 bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-bold rounded-lg shadow-sm">
                            Skor Maksimal: {{ assignment.max_score }}
                        </span>
                    </div>

                    <div class="space-y-3">
                        <h4 class="text-sm font-bold text-gray-900">Petunjuk Tugas:</h4>
                        <p class="text-xs text-gray-600 leading-relaxed font-light whitespace-pre-wrap">
                            {{ assignment.description || 'Tidak ada petunjuk khusus.' }}
                        </p>
                    </div>
                </div>

                <!-- Teacher Grading Feedback -->
                <div v-if="submission && submission.status === 'returned'" class="bg-indigo-50/50 border border-indigo-100/50 rounded-3xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-2">
                        <CheckCircleIcon class="w-5 h-5 text-indigo-600" />
                        <h4 class="font-bold text-indigo-950 text-sm">Hasil Penilaian Guru</h4>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <span class="text-2xl font-black text-indigo-900 bg-white border border-indigo-100 px-4 py-2 rounded-2xl shadow-inner">
                            {{ submission.grade }} <span class="text-xs text-gray-400 font-semibold">/ {{ assignment.max_score }}</span>
                        </span>
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                            Dinilai oleh pengampu pada {{ formatDate(submission.graded_at) }}
                        </div>
                    </div>

                    <div v-if="submission.feedback" class="text-xs text-indigo-900/80 pl-4 border-l-2 border-indigo-300 italic">
                        "{{ submission.feedback }}"
                    </div>
                </div>
            </div>

            <!-- Right Side: Submission Box -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Submission Form Card -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl p-6 shadow-sm border border-white/50 space-y-6">
                    <div class="flex items-center justify-between border-b border-gray-50 pb-3">
                        <h3 class="font-bold text-sm text-gray-950">Tugas Anda</h3>
                        
                        <!-- Status Badge -->
                        <span v-if="submission" class="px-2.5 py-0.5 text-[9px] font-bold uppercase rounded border tracking-wider"
                              :class="[
                                  submission.status === 'submitted' ? 'bg-emerald-50 text-emerald-700 border-emerald-100/50' : '',
                                  submission.status === 'late' ? 'bg-amber-50 text-amber-600 border-amber-100/50' : '',
                                  submission.status === 'returned' ? 'bg-indigo-50 text-indigo-700 border-indigo-100/50' : '',
                              ]"
                        >
                            {{ submission.status === 'submitted' ? 'Diserahkan' : '' }}
                            {{ submission.status === 'late' ? 'Terlambat' : '' }}
                            {{ submission.status === 'returned' ? 'Dinilai' : '' }}
                        </span>
                        <span v-else class="px-2.5 py-0.5 text-[9px] font-bold uppercase rounded border border-gray-100 bg-gray-50 text-gray-400 tracking-wider">
                            Belum Diserahkan
                        </span>
                    </div>

                    <!-- Submission File list (If already submitted) -->
                    <div v-if="submission && submission.files.length > 0" class="space-y-3 p-4 bg-gray-50/70 border border-gray-100 rounded-2xl">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Berkas Terkirim:</span>
                        <div v-for="file in submission.files" :key="file.id" class="flex items-center gap-2">
                            <PaperClipIcon class="w-4 h-4 text-emerald-600 shrink-0" />
                            <a :href="'/' + file.file_path" target="_blank" class="text-xs text-emerald-800 font-bold hover:underline truncate flex-1">
                                {{ file.file_name }}
                            </a>
                        </div>
                    </div>

                    <!-- Submission Form (Hide if returned/graded) -->
                    <form v-if="!submission || submission.status !== 'returned'" @submit.prevent="submitAssignment" class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Catatan Tambahan (Opsional)</label>
                            <textarea 
                                v-model="form.submission_text"
                                class="w-full border border-gray-200 rounded-2xl p-3 text-xs focus:border-emerald-700 focus:ring focus:ring-emerald-700/10 transition shadow-inner"
                                rows="3"
                                placeholder="Tuliskan keterangan jika ada..."
                            ></textarea>
                        </div>

                        <!-- Upload Box -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Unggah Tugas</label>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 border border-dashed border-gray-200 rounded-2xl">
                                <label class="px-4 py-2 bg-white border border-gray-200 hover:border-emerald-700 hover:text-emerald-700 text-[10px] font-bold uppercase tracking-wider rounded-xl cursor-pointer active:scale-95 shadow-sm transition">
                                    Pilih Berkas
                                    <input 
                                        type="file" 
                                        class="hidden" 
                                        @change="handleFileSelect"
                                        ref="fileInput"
                                    />
                                </label>
                                <span class="text-xs text-gray-500 font-medium truncate max-w-[150px]">
                                    {{ filePreviewName || 'Belum ada berkas' }}
                                </span>
                            </div>
                        </div>

                        <button 
                            type="submit" 
                            class="w-full py-3 bg-emerald-700 hover:bg-emerald-800 disabled:bg-emerald-700/70 text-white rounded-2xl font-bold shadow-lg shadow-emerald-700/20 text-center flex items-center justify-center gap-2 transition-all active:scale-95 text-xs tracking-wider uppercase"
                            :disabled="form.processing"
                        >
                            <NamiraLoader :visible="form.processing" variant="button" />
                            <span>{{ form.processing ? 'Mengirim...' : (submission ? 'Kirim Ulang Tugas' : 'Serahkan Tugas') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
