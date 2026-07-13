<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    MegaphoneIcon, BookOpenIcon, ClipboardDocumentIcon, ArrowLeftIcon, 
    UserIcon, CalendarDaysIcon, PaperClipIcon, VideoCameraIcon, LinkIcon,
    CheckCircleIcon, ExclamationTriangleIcon, TrophyIcon, UserGroupIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    lmsClassroom: Object,
    stream: Array,
});

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
        case 'late': return 'Terlambat Dikumpul';
        case 'missing': return 'Terlewat (Missing)';
        case 'returned': return 'Sudah Dinilai';
        default: return 'Belum Dikumpul';
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
    <Head :title="lmsClassroom.subject?.name" />

    <AuthenticatedLayout>
        <!-- Header -->
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('lms.student.classrooms.index')" class="p-2 bg-white dark:bg-gray-800 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition shadow-sm border border-gray-100 dark:border-gray-700">
                    <ArrowLeftIcon class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                </Link>
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        {{ lmsClassroom.subject?.name }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Kelas: {{ lmsClassroom.classroom?.name }} | Guru: {{ lmsClassroom.teacher?.full_name }}</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Left Info Panel -->
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
                    </ul>
                </div>
            </div>

            <!-- Stream Feed -->
            <div class="lg:col-span-3 space-y-4">
                <div v-for="item in stream" :key="item.type + '-' + item.id" class="bg-white/85 border border-white/60 rounded-3xl p-6 shadow-sm flex gap-4 hover:shadow-md transition-shadow relative">
                    <!-- Icon type -->
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
                                    {{ item.type === 'material' ? 'Materi Belajar' : '' }}
                                    {{ item.type === 'assignment' ? 'Tugas Acara' : '' }}
                                </span>
                                <h3 class="text-sm font-bold text-gray-900 mt-0.5 leading-snug">
                                    {{ item.type === 'announcement' ? 'Pengumuman Guru' : item.title }}
                                </h3>
                            </div>
                            <span class="text-[10px] text-gray-400 font-semibold">{{ formatDate(item.created_at) }}</span>
                        </div>

                        <!-- Text content -->
                        <p class="text-xs text-gray-600 leading-relaxed font-light whitespace-pre-wrap">
                            {{ item.type === 'announcement' ? item.content : item.description }}
                        </p>

                        <!-- Attachments (For Materials) -->
                        <div v-if="item.type === 'material' && item.files && item.files.length > 0" class="pt-2">
                            <div v-for="file in item.files" :key="file.id" class="inline-flex items-center gap-2 p-2 bg-gray-50 border border-gray-100 rounded-xl max-w-sm mr-2 mb-2">
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
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                                <span class="flex items-center gap-1"><CalendarDaysIcon class="w-3.5 h-3.5 text-amber-500" /> Batas Waktu: {{ formatDate(item.due_date) }}</span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border text-[9px]" :class="getStatusBadgeClass(item.submission_status)">
                                    {{ getStatusLabel(item.submission_status) }}
                                </span>
                                <span v-if="item.grade !== null" class="text-indigo-600 font-extrabold bg-indigo-50 border border-indigo-100 px-1.5 py-0.5 rounded">Nilai: {{ item.grade }}</span>
                            </div>

                            <Link :href="route('lms.student.classrooms.assignments.show', { classroom: lmsClassroom.id, assignment: item.id })" class="px-4 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg text-[10px] font-bold uppercase tracking-widest transition-colors shadow-md shadow-emerald-700/10">
                                {{ item.submission_status === 'not_submitted' ? 'Kerjakan Tugas' : 'Lihat Tugas' }} &rarr;
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty Feed -->
                <div v-if="stream.length === 0" class="bg-white/85 border border-white/60 rounded-3xl p-10 text-center text-gray-400 shadow-sm">
                    <MegaphoneIcon class="w-10 h-10 mx-auto opacity-30 mb-3" />
                    <p class="text-sm font-semibold">Belum Ada Aktivitas Kelas.</p>
                    <p class="text-xs mt-1">Belum ada pengumuman, materi, atau tugas dari guru pengampu.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
