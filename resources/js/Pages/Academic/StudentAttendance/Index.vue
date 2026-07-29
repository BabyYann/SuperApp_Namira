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

        <div class="py-4 md:py-6 max-w-7xl mx-auto space-y-5 md:space-y-6">
            
            <!-- MOBILE EXECUTIVE HEADER (Deep Namira Emerald) -->
            <div class="block md:hidden bg-[#064e3b] text-white p-5 rounded-3xl border border-emerald-800/80 shadow-xl">
                <span class="text-[10px] font-black tracking-widest text-teal-400 uppercase">Absensi Siswa Harian</span>
                <h3 class="text-xl font-extrabold text-white mt-0.5">Pilih Kelas Perwalian</h3>
                <p class="text-xs text-slate-400 font-medium mt-1">Pilih kelas di bawah ini untuk mengisi atau memperbarui kehadiran siswa.</p>
            </div>

            <!-- 1A. DESKTOP GRID (Unchanged Desktop Layout) -->
            <div class="hidden md:grid grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="classroom in classrooms" :key="'desk-'+classroom.id" class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition-shadow">
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

            <!-- 1B. MOBILE NATIVE CARDS -->
            <div class="grid md:hidden grid-cols-1 gap-3.5">
                <div 
                    v-for="classroom in classrooms" 
                    :key="'mob-'+classroom.id"
                    class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm flex flex-col justify-between"
                >
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-3 py-1 bg-teal-50 text-teal-800 rounded-xl text-xs font-black border border-teal-100">
                            {{ classroom.unit?.name || 'Unit Sekolah' }}
                        </span>
                        <div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-600">
                            <BuildingOfficeIcon class="h-4 w-4" />
                        </div>
                    </div>

                    <h3 class="text-xl font-extrabold text-slate-900 mb-4">{{ classroom.name }}</h3>

                    <Link 
                        :href="route('yayasan.student-attendance.show', classroom.id)"
                        class="w-full py-3.5 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-2xl shadow-md text-center block transition-all active:scale-95"
                    >
                        Input Presensi Siswa Kelas Ini
                    </Link>
                </div>

                <!-- Empty State Mobile -->
                <div v-if="classrooms.length === 0" class="text-center py-12 bg-white rounded-3xl border border-slate-200 shadow-sm flex flex-col items-center justify-center p-6">
                    <div class="bg-indigo-50 p-5 rounded-full mb-4">
                        <AcademicCapIcon class="w-12 h-12 text-indigo-400" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-1">Belum Ada Kelas</h3>
                    <p class="text-xs text-slate-500 max-w-xs">Anda belum ditugaskan sebagai Wali Kelas saat ini.</p>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
