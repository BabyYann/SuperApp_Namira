<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    ArrowLeftIcon, PaperClipIcon, CheckCircleIcon, XCircleIcon, 
    ClockIcon, DocumentTextIcon, ChatBubbleBottomCenterTextIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    lmsClassroom: Object,
    assignment: Object,
    submissions: Array,
});

// Selected student for grading modal
const selectedSubmission = ref(null);
const showGradeModal = ref(false);

const gradeForm = useForm({
    grade: '',
    feedback: '',
});

const openGradeModal = (sub) => {
    selectedSubmission.value = sub;
    gradeForm.grade = sub.grade !== null ? sub.grade : '';
    gradeForm.feedback = sub.feedback || '';
    showGradeModal.value = true;
};

const submitGrade = () => {
    if (!selectedSubmission.value) return;
    
    gradeForm.post(route('lms.teacher.classrooms.assignments.submissions.grade', {
        classroom: props.lmsClassroom.id,
        assignment: props.assignment.id,
        submission: selectedSubmission.value.submission_id
    }), {
        onSuccess: () => {
            showGradeModal.value = false;
            selectedSubmission.value = null;
            gradeForm.reset();
        }
    });
};

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'submitted': return 'bg-emerald-50 text-emerald-700 border-emerald-100/50';
        case 'late': return 'bg-amber-50 text-amber-600 border-amber-100/50';
        case 'missing': return 'bg-red-50 text-red-600 border-red-100/50';
        case 'returned': return 'bg-indigo-50 text-indigo-700 border-indigo-100/50';
        default: return 'bg-gray-50 text-gray-400 border-gray-100';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'submitted': return 'Sudah Dikumpul';
        case 'late': return 'Terlambat';
        case 'missing': return 'Belum Mengumpul (Lewat)';
        case 'returned': return 'Sudah Dinilai';
        default: return 'Belum Mengumpul';
    }
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
    <Head :title="'Daftar Pengumpulan - ' + assignment.title" />

    <AuthenticatedLayout>
        <!-- Header -->
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('lms.teacher.classrooms.show', lmsClassroom.id)" class="p-2 bg-white dark:bg-gray-800 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition shadow-sm border border-gray-100 dark:border-gray-700">
                    <ArrowLeftIcon class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                </Link>
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Penilaian Tugas: {{ assignment.title }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Mata Pelajaran: {{ lmsClassroom.subject?.name }} | Batas Waktu: {{ formatDate(assignment.due_date) }}</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6">
            <!-- Student List & Grades Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/50">
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider">Nama Siswa</th>
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider">Status Pengumpulan</th>
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider">Tanggal Mengumpul</th>
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider">Berkas Lampiran</th>
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider">Nilai</th>
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="student in submissions" :key="student.student_id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="p-5">
                                    <div class="font-bold text-sm text-gray-900">{{ student.full_name }}</div>
                                    <div class="text-[10px] text-gray-400 font-semibold mt-0.5">NIS: {{ student.nis || '-' }}</div>
                                </td>
                                <td class="p-5">
                                    <span class="inline-flex px-2.5 py-1 text-[10px] font-bold uppercase rounded-lg border tracking-wide" :class="getStatusBadgeClass(student.status)">
                                        {{ getStatusLabel(student.status) }}
                                    </span>
                                </td>
                                <td class="p-5 text-xs text-gray-500 font-medium">
                                    {{ student.submitted_at ? formatDate(student.submitted_at) : '-' }}
                                </td>
                                <td class="p-5">
                                    <!-- Files list -->
                                    <div class="space-y-1">
                                        <div v-for="file in student.files" :key="file.id" class="flex items-center gap-1">
                                            <PaperClipIcon class="w-3.5 h-3.5 text-gray-400" />
                                            <a :href="'/' + file.file_path" target="_blank" class="text-xs text-emerald-700 hover:underline truncate max-w-[150px]">
                                                {{ file.file_name }}
                                            </a>
                                        </div>
                                        <span v-if="student.files.length === 0" class="text-xs text-gray-400 font-light">-</span>
                                    </div>
                                </td>
                                <td class="p-5">
                                    <span v-if="student.grade !== null" class="inline-flex items-center justify-center px-3 py-1 bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-bold rounded-lg shadow-sm">
                                        {{ student.grade }} / {{ assignment.max_score }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400 font-light">-</span>
                                </td>
                                <td class="p-5 text-right">
                                    <!-- Grading button -->
                                    <button 
                                        v-if="student.status === 'submitted' || student.status === 'late' || student.status === 'returned'"
                                        @click="openGradeModal(student)" 
                                        class="px-4 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all active:scale-95 shadow-md shadow-emerald-700/10"
                                    >
                                        {{ student.status === 'returned' ? 'Edit Nilai' : 'Beri Nilai' }}
                                    </button>
                                    <span v-else class="text-xs text-gray-400 font-light">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL: GRADE SUBMISSION -->
        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-4">
            <div v-if="showGradeModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 relative">
                    <h3 class="font-bold text-lg text-gray-900 border-b border-gray-50 pb-3 mb-5">Penilaian Jawaban Siswa</h3>
                    
                    <div class="bg-gray-50 p-4 rounded-2xl text-xs text-gray-600 mb-5 space-y-2.5">
                        <p>Siswa: <strong class="text-gray-800">{{ selectedSubmission?.full_name }}</strong></p>
                        <p v-if="selectedSubmission?.submission_text">Catatan Siswa: <span class="italic">"{{ selectedSubmission?.submission_text }}"</span></p>
                        <div v-if="selectedSubmission?.files.length > 0">
                            <span class="font-bold block mb-1">Berkas Lampiran:</span>
                            <div v-for="file in selectedSubmission.files" :key="file.id" class="flex items-center gap-1 mb-1">
                                <PaperClipIcon class="w-3.5 h-3.5 text-gray-400" />
                                <a :href="'/' + file.file_path" target="_blank" class="text-xs text-emerald-700 hover:underline">
                                    {{ file.file_name }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submitGrade" class="space-y-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Skor / Nilai (Maks {{ assignment.max_score }})</label>
                            <input 
                                v-model="gradeForm.grade" 
                                type="number"
                                min="0"
                                :max="assignment.max_score"
                                class="w-full border border-gray-200 rounded-xl p-3 text-sm focus:border-emerald-700 focus:ring focus:ring-emerald-700/10 transition"
                                required
                                placeholder="Masukkan nilai angka..."
                            />
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Masukan / Feedback Tambahan</label>
                            <textarea 
                                v-model="gradeForm.feedback" 
                                class="w-full border border-gray-200 rounded-xl p-3 text-sm focus:border-emerald-700 focus:ring focus:ring-emerald-700/10 transition"
                                rows="3"
                                placeholder="Berikan komentar untuk koreksi atau apresiasi..."
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-50 mt-6">
                            <button type="button" @click="showGradeModal = false" class="px-5 py-2 text-xs font-bold text-gray-500 hover:bg-gray-50 rounded-xl">Batal</button>
                            <button type="submit" class="px-5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-bold shadow-md shadow-emerald-700/20 text-xs uppercase tracking-wider" :disabled="gradeForm.processing">Simpan Nilai</button>
                        </div>
                    </form>
                </div>
            </div>
        </transition>
    </AuthenticatedLayout>
</template>
