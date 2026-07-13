<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { BuildingOfficeIcon, AcademicCapIcon } from '@heroicons/vue/24/outline';

defineProps({
    classrooms: Array,
});
</script>

<template>
    <Head title="Presensi Siswa" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">Presensi Siswa</h2>
            <p class="text-sm text-gray-500 mt-1">Pilih kelas perwalian Anda untuk melakukan absensi.</p>
        </template>

        <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="classroom in classrooms" :key="classroom.id" class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">{{ classroom.name }}</h3>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                                    {{ classroom.unit?.name || 'Unit' }}
                                </span>
                            </div>
                            <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500">
                                <BuildingOfficeIcon class="h-6 w-6" />
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <Link 
                                :href="route('yayasan.student-attendance.show', classroom.id)"
                                class="block w-full text-center px-4 py-2 bg-namira-teal text-white rounded-lg font-bold text-sm hover:bg-teal-700 transition-colors"
                            >
                                Input Absensi
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="classrooms.length === 0" class="col-span-full text-center py-16 bg-white rounded-3xl border border-gray-100 shadow-sm flex flex-col items-center justify-center">
                    <div class="bg-indigo-50 p-6 rounded-full mb-6">
                        <AcademicCapIcon class="w-16 h-16 text-indigo-400" />
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Belum Ada Kelas</h3>
                    <p class="text-gray-500 font-medium max-w-md mx-auto">Anda belum ditugaskan sebagai Wali Kelas untuk kelas manapun saat ini.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
