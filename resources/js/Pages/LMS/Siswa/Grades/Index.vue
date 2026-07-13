<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    TrophyIcon, AcademicCapIcon, UserIcon, CalendarDaysIcon, 
    CheckCircleIcon, ChatBubbleBottomCenterTextIcon, ChevronDownIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    gradesOverview: Array,
});

// Expandable accordion index
const expandedClassroomId = ref(null);

const toggleExpand = (classroomId) => {
    if (expandedClassroomId.value === classroomId) {
        expandedClassroomId.value = null;
    } else {
        expandedClassroomId.value = classroomId;
    }
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
        case 'missing': return 'Terlewat (Missing)';
        case 'returned': return 'Sudah Dinilai';
        default: return 'Belum Dikumpul';
    }
};
</script>

<template>
    <Head title="LMS - Nilai Akademik Saya" />

    <AuthenticatedLayout>
        <!-- Header -->
        <template #header>
            <div>
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                    LMS - Nilai Akademik Saya
                </h2>
                <p class="text-sm text-gray-500 mt-1">Daftar rekapitulasi nilai tugas dan masukan dari guru pengampu.</p>
            </div>
        </template>

        <div class="py-6 max-w-4xl mx-auto space-y-6">
            <!-- Grades Overview Cards (Accordion Style) -->
            <div v-for="item in gradesOverview" :key="item.classroom_id" class="bg-white/80 backdrop-blur-xl rounded-3xl border border-white/60 shadow-sm overflow-hidden transition-all duration-300">
                <!-- Accordion Header -->
                <button 
                    @click="toggleExpand(item.classroom_id)" 
                    class="w-full p-6 flex items-center justify-between text-left focus:outline-none hover:bg-gray-50/50 transition-colors"
                >
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-emerald-50 border border-emerald-100/50 text-emerald-700 rounded-2xl flex items-center justify-center shadow-inner shrink-0">
                            <AcademicCapIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-extrabold text-gray-900 text-base leading-tight">{{ item.subject_name }}</h3>
                            <p class="text-xs text-gray-400 font-semibold mt-0.5 uppercase tracking-wide">Guru: {{ item.teacher_name }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- Cumulative Average -->
                        <div class="text-right">
                            <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider block mb-0.5">Rata-Rata</span>
                            <span class="inline-flex px-3.5 py-1.5 text-sm font-black rounded-xl shadow-sm"
                                  :class="item.average !== '-' ? 'bg-indigo-50 border border-indigo-100 text-indigo-700' : 'bg-gray-50 text-gray-400'">
                                {{ item.average }}
                            </span>
                        </div>
                        <ChevronDownIcon class="w-4 h-4 text-gray-400 transition-transform duration-300" :class="{'rotate-180': expandedClassroomId === item.classroom_id}" />
                    </div>
                </button>

                <!-- Accordion Body -->
                <div v-show="expandedClassroomId === item.classroom_id" class="border-t border-gray-100/50 bg-gray-50/25 p-6 space-y-4">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 pb-2 mb-3">Detail Nilai Tugas</h4>
                    
                    <div v-for="assign in item.assignments" :key="assign.assignment_title" class="p-4 bg-white/70 border border-white/60 rounded-2xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <h5 class="text-sm font-bold text-gray-900 leading-snug">{{ assign.assignment_title }}</h5>
                            <div class="flex items-center gap-3 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                                <span class="flex items-center gap-1"><CalendarDaysIcon class="w-3.5 h-3.5 text-amber-500" /> Deadline: {{ assign.due_date }}</span>
                                <span class="inline-flex px-2 py-0.5 rounded border text-[9px]" :class="getStatusBadgeClass(assign.status)">
                                    {{ getStatusLabel(assign.status) }}
                                </span>
                            </div>
                        </div>

                        <!-- Grades & Feedback -->
                        <div class="flex flex-col md:items-end gap-2 shrink-0">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-400 font-bold uppercase">Nilai:</span>
                                <span v-if="assign.score !== null" class="inline-flex items-center justify-center px-3 py-1 bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-black rounded-lg shadow-sm">
                                    {{ assign.score }}
                                </span>
                                <span v-else class="text-xs text-gray-400 font-light">-</span>
                            </div>

                            <div v-if="assign.feedback" class="flex items-start gap-1.5 text-xs text-gray-500 italic max-w-sm">
                                <ChatBubbleBottomCenterTextIcon class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" />
                                <span>"{{ assign.feedback }}"</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="item.assignments.length === 0" class="text-center p-6 text-gray-400">
                        <p class="text-xs font-bold">Belum ada tugas yang diterbitkan untuk mata pelajaran ini.</p>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="gradesOverview.length === 0" class="bg-white/80 border border-gray-100 rounded-3xl p-10 text-center text-gray-400 shadow-sm">
                <TrophyIcon class="w-12 h-12 mx-auto opacity-30 mb-2" />
                <p class="text-sm font-semibold">Tidak ada kelas atau data nilai yang tercatat.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
