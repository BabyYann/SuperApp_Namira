<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ComputerDesktopIcon, BookOpenIcon, AcademicCapIcon, UserIcon } from '@heroicons/vue/24/outline';

defineProps({
    classrooms: Array,
});
</script>

<template>
    <Head title="LMS - Kelas Saya" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                    LMS - Kelas Saya
                </h2>
                <p class="text-sm text-gray-500 mt-1">Daftar kelas virtual Anda untuk tahun ajaran aktif.</p>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto">
            <!-- Grid Classrooms -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="item in classrooms" :key="item.id" class="group relative bg-white/80 backdrop-blur-xl border border-white/60 rounded-3xl p-6 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col justify-between min-h-[220px]">
                    <!-- Decorative background element -->
                    <div class="absolute -top-10 -right-10 w-24 h-24 bg-emerald-50 rounded-full group-hover:scale-150 transition-transform duration-500 opacity-50 z-0"></div>

                    <div class="relative z-10 space-y-4">
                        <!-- Unit Badge -->
                        <span class="inline-flex px-3 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-bold uppercase rounded-lg tracking-wider border border-emerald-100/50">
                            {{ item.classroom?.unit?.name || 'Namira School' }}
                        </span>

                        <div>
                            <!-- Subject Name -->
                            <h3 class="text-lg font-bold text-gray-900 group-hover:text-emerald-700 transition-colors leading-tight">
                                {{ item.subject?.name }}
                            </h3>
                            <!-- Class Code & Group -->
                            <p class="text-xs text-gray-400 mt-1 font-semibold tracking-wide uppercase">
                                Kode: {{ item.subject?.code || '-' }} | Kelompok: {{ item.subject?.group || '-' }}
                            </p>
                        </div>

                        <!-- Class Details -->
                        <div class="flex items-center gap-6 pt-3 border-t border-gray-50 text-gray-500 text-xs font-medium">
                            <div class="flex items-center gap-1.5">
                                <AcademicCapIcon class="w-4 h-4 text-emerald-600" />
                                <span>Kelas: <strong class="text-gray-800">{{ item.classroom?.name }}</strong></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <UserIcon class="w-4 h-4 text-amber-500" />
                                <span>Guru: <strong class="text-gray-800">{{ item.teacher?.full_name }}</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Enter Class Button -->
                    <div class="relative z-10 pt-6">
                        <Link :href="route('lms.student.classrooms.show', item.id)" class="w-full py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-2xl font-bold shadow-md shadow-emerald-700/20 text-center block transition-all active:scale-95 text-xs tracking-wider uppercase">
                            Masuk Kelas Belajar &rarr;
                        </Link>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="classrooms.length === 0" class="col-span-full max-w-xl mx-auto bg-white/80 border border-gray-100 rounded-3xl p-10 text-center shadow-sm">
                    <ComputerDesktopIcon class="w-12 h-12 mx-auto text-gray-300 mb-4" />
                    <h3 class="text-base font-bold text-gray-800 mb-1">Tidak Ada Kelas Terdaftar</h3>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        Anda belum terdaftar di kelas virtual mana pun untuk tahun ajaran aktif ini. Hubungi admin kurikulum untuk informasi lebih lanjut.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
