<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { BuildingOffice2Icon, CalendarDaysIcon, UserIcon, UsersIcon, ChartBarIcon, ClockIcon, AcademicCapIcon, BellAlertIcon } from '@heroicons/vue/24/outline';

defineProps({
    unitsCount: Number,
    studentsCount: Number,
    activeYear: Object,
    upcomingEvents: Array,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' });
};

const getDaysFromNow = (date) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const eventDate = new Date(date);
    eventDate.setHours(0, 0, 0, 0);
    const diff = Math.ceil((eventDate - today) / (1000 * 60 * 60 * 24));
    if (diff === 0) return 'Hari ini';
    if (diff === 1) return 'Besok';
    return `${diff} hari lagi`;
};

const eventTypeLabels = {
    'libur': 'Libur',
    'ujian': 'Ujian',
    'event': 'Event',
    'rapat': 'Rapat',
};
</script>

<template>
    <Head title="Yayasan Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                Dashboard Overview
            </h2>
            <p class="text-sm text-gray-500 mt-1">Welcome back, {{ $page.props.auth.user.name }}</p>
        </template>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Units Stats -->
            <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] transition-all duration-300 hover:-translate-y-1 hover:shadow-lg dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-namira-teal/10 transition-all duration-500 group-hover:scale-150"></div>
                <div class="relative z-10">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-namira-teal/10 text-namira-teal">
                        <BuildingOffice2Icon class="h-6 w-6" />
                    </div>
                    <h3 class="text-sm font-medium text-gray-500">Total Units</h3>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ unitsCount }}</p>
                </div>
            </div>

            <!-- Active Year Stats -->
             <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] transition-all duration-300 hover:-translate-y-1 hover:shadow-lg dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-namira-blue/10 transition-all duration-500 group-hover:scale-150"></div>
                <div class="relative z-10">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-namira-blue/10 text-namira-blue">
                         <CalendarDaysIcon class="h-6 w-6" />
                    </div>
                    <h3 class="text-sm font-medium text-gray-500">Active Academic Year</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ activeYear ? activeYear.name : 'NOT SET' }}
                        </p>
                        <span v-if="activeYear" class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                            {{ activeYear.semester.toUpperCase() }}
                        </span>
                    </div>
                </div>
            </div>

             <!-- Students Stats -->
             <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] transition-all duration-300 hover:-translate-y-1 hover:shadow-lg dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-orange-500/10 transition-all duration-500 group-hover:scale-150"></div>
                <div class="relative z-10">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-orange-500/10 text-orange-500">
                        <AcademicCapIcon class="h-6 w-6" />
                    </div>
                    <h3 class="text-sm font-medium text-gray-500">Total Siswa</h3>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ studentsCount }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Quick Actions -->
            <div class="lg:col-span-2 rounded-2xl bg-white p-6 shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Link :href="route('yayasan.users.index')" class="flex flex-col items-center gap-3 p-4 bg-gray-50 hover:bg-namira-teal/10 rounded-xl transition-colors group border border-gray-100 hover:border-namira-teal/30">
                        <div class="p-3 bg-indigo-100 rounded-xl text-indigo-600 group-hover:bg-namira-teal group-hover:text-white transition-colors">
                            <UsersIcon class="w-6 h-6" />
                        </div>
                        <span class="text-sm font-bold text-gray-700 group-hover:text-namira-teal">Manajemen User</span>
                    </Link>
                    <Link :href="route('yayasan.academic-years.index')" class="flex flex-col items-center gap-3 p-4 bg-gray-50 hover:bg-namira-teal/10 rounded-xl transition-colors group border border-gray-100 hover:border-namira-teal/30">
                        <div class="p-3 bg-amber-100 rounded-xl text-amber-600 group-hover:bg-namira-teal group-hover:text-white transition-colors">
                            <CalendarDaysIcon class="w-6 h-6" />
                        </div>
                        <span class="text-sm font-bold text-gray-700 group-hover:text-namira-teal">Tahun Akademik</span>
                    </Link>
                    <Link :href="route('yayasan.monitoring.index')" class="flex flex-col items-center gap-3 p-4 bg-gray-50 hover:bg-namira-teal/10 rounded-xl transition-colors group border border-gray-100 hover:border-namira-teal/30">
                        <div class="p-3 bg-emerald-100 rounded-xl text-emerald-600 group-hover:bg-namira-teal group-hover:text-white transition-colors">
                            <ChartBarIcon class="w-6 h-6" />
                        </div>
                        <span class="text-sm font-bold text-gray-700 group-hover:text-namira-teal">Monitoring</span>
                    </Link>
                    <Link :href="route('yayasan.attendance-data.index')" class="flex flex-col items-center gap-3 p-4 bg-gray-50 hover:bg-namira-teal/10 rounded-xl transition-colors group border border-gray-100 hover:border-namira-teal/30">
                        <div class="p-3 bg-rose-100 rounded-xl text-rose-600 group-hover:bg-namira-teal group-hover:text-white transition-colors">
                            <ClockIcon class="w-6 h-6" />
                        </div>
                        <span class="text-sm font-bold text-gray-700 group-hover:text-namira-teal">Data Absensi</span>
                    </Link>
                </div>
            </div>

            <!-- Upcoming Events Widget -->
            <div class="rounded-2xl bg-white p-6 shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <BellAlertIcon class="w-5 h-5 text-amber-500" />
                        Agenda Mendatang
                    </h3>
                    <Link :href="route('yayasan.holidays.index')" class="text-xs text-namira-teal hover:underline font-medium">
                        Lihat Semua →
                    </Link>
                </div>
                
                <div v-if="upcomingEvents && upcomingEvents.length > 0" class="space-y-3">
                    <div v-for="event in upcomingEvents" :key="event.id" class="flex items-start gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <div class="w-1 h-10 rounded-full flex-shrink-0" :style="{ backgroundColor: event.display_color }"></div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-sm text-gray-800 truncate">{{ event.description }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs text-gray-500">{{ formatDate(event.date) }}</span>
                                <span class="px-1.5 py-0.5 text-[10px] rounded font-bold" 
                                      :style="{ backgroundColor: event.display_color + '20', color: event.display_color }">
                                    {{ eventTypeLabels[event.event_type] || event.event_type }}
                                </span>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 whitespace-nowrap">{{ getDaysFromNow(event.date) }}</span>
                    </div>
                </div>
                
                <div v-else class="text-center py-8 text-gray-400">
                    <CalendarDaysIcon class="w-12 h-12 mx-auto mb-2 opacity-50" />
                    <p class="text-sm">Tidak ada agenda dalam 30 hari ke depan</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

