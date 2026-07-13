<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, TrophyIcon, ClipboardDocumentListIcon } from '@heroicons/vue/24/outline';

defineProps({
    lmsClassroom: Object,
    assignments: Array,
    gradesMatrix: Array,
});
</script>

<template>
    <Head :title="'Buku Nilai - ' + lmsClassroom.subject?.name" />

    <AuthenticatedLayout>
        <!-- Header -->
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('lms.teacher.classrooms.show', lmsClassroom.id)" class="p-2 bg-white dark:bg-gray-800 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition shadow-sm border border-gray-100 dark:border-gray-700">
                    <ArrowLeftIcon class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                </Link>
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Buku Nilai Kelas (Gradebook)
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Kelas: {{ lmsClassroom.classroom?.name }} | Mata Pelajaran: {{ lmsClassroom.subject?.name }}</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6">
            <!-- Gradebook Table Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="p-6 border-b border-gray-100/50 flex items-center justify-between">
                    <h3 class="font-bold text-base text-gray-900 flex items-center gap-2">
                        <TrophyIcon class="w-5 h-5 text-amber-500" />
                        <span>Rekapitulasi Nilai Tugas Siswa</span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/50">
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider min-w-[200px]">Nama Siswa</th>
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider">NIS</th>
                                <!-- Assignment Columns -->
                                <th v-for="assign in assignments" :key="assign.id" class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider text-center min-w-[120px]">
                                    <div class="truncate max-w-[150px] mx-auto" :title="assign.title">
                                        {{ assign.title }}
                                    </div>
                                    <span class="text-[9px] text-gray-400 font-semibold block mt-0.5">(Max {{ assign.max_score }})</span>
                                </th>
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider text-center min-w-[100px] bg-slate-50/75">Rata-Rata</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="row in gradesMatrix" :key="row.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="p-5 font-bold text-sm text-gray-950">{{ row.name }}</td>
                                <td class="p-5 text-xs font-bold text-gray-500">{{ row.nis || '-' }}</td>
                                <!-- Cell grades -->
                                <td v-for="assign in assignments" :key="assign.id" class="p-5 text-center">
                                    <span v-if="row.grades[assign.id] !== null" class="inline-block px-2.5 py-1 bg-gray-50 border border-gray-100 text-gray-700 text-xs font-bold rounded-lg shadow-sm">
                                        {{ row.grades[assign.id] }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400 font-light">-</span>
                                </td>
                                <!-- Class Average Cell -->
                                <td class="p-5 text-center bg-slate-50/50">
                                    <span class="inline-flex px-3 py-1 text-xs font-extrabold rounded-lg shadow-sm"
                                          :class="row.average !== '-' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100/50' : 'bg-gray-50 text-gray-400'">
                                        {{ row.average }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="gradesMatrix.length === 0">
                                <td :colspan="3 + assignments.length" class="p-10 text-center text-gray-400">
                                    <ClipboardDocumentListIcon class="w-12 h-12 mx-auto opacity-30 mb-2" />
                                    <p class="text-xs font-bold">Belum ada data siswa atau tugas diterbitkan.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
