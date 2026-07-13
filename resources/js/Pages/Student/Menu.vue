<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { 
    UserCircleIcon,
    ArrowRightOnRectangleIcon,
    BanknotesIcon,
    CalendarIcon,
    HomeIcon,
    ShieldCheckIcon,
    ExclamationTriangleIcon,
    RocketLaunchIcon,
    ComputerDesktopIcon
} from '@heroicons/vue/24/outline';

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <StudentLayout title="Menu">
        <div class="space-y-6">
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="w-16 h-16 rounded-full bg-gray-100 overflow-hidden">
                    <img v-if="$page.props.auth.user.profile_photo_url" :src="$page.props.auth.user.profile_photo_url" class="w-full h-full object-cover">
                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400 font-bold text-xl">
                        {{ $page.props.auth.user.name.charAt(0) }}
                    </div>
                </div>
                <div>
                    <h2 class="font-bold text-lg text-gray-800">{{ $page.props.auth.user.name }}</h2>
                    <p class="text-sm text-gray-500">{{ $page.props.auth.user.email }}</p>
                    <Link :href="route('profile.edit')" class="text-xs text-namira-teal font-bold mt-1 flex items-center gap-1">
                        Edit Profil
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <Link :href="route('student.dashboard')" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center justify-center text-center gap-2 hover:bg-gray-50 transition-colors">
                    <div class="p-3 bg-teal-50 text-teal-600 rounded-xl">
                        <HomeIcon class="w-6 h-6" />
                    </div>
                    <span class="text-sm font-bold text-gray-700">Beranda</span>
                </Link>

                <Link :href="route('lms.student.classrooms.index')" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center justify-center text-center gap-2 hover:bg-gray-50 transition-colors">
                    <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                        <ComputerDesktopIcon class="w-6 h-6" />
                    </div>
                    <span class="text-sm font-bold text-gray-700">E-Learning (LMS)</span>
                </Link>

                <Link :href="route('student.academic')" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center justify-center text-center gap-2 hover:bg-gray-50 transition-colors">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                        <CalendarIcon class="w-6 h-6" />
                    </div>
                    <span class="text-sm font-bold text-gray-700">Akademik</span>
                </Link>

                <Link :href="route('student.finance')" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center justify-center text-center gap-2 hover:bg-gray-50 transition-colors">
                    <div class="p-3 bg-orange-50 text-orange-600 rounded-xl">
                        <BanknotesIcon class="w-6 h-6" />
                    </div>
                    <span class="text-sm font-bold text-gray-700">Keuangan</span>
                </Link>

                <Link :href="route('student.counseling')" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center justify-center text-center gap-2 hover:bg-gray-50 transition-colors">
                    <div class="p-3 bg-red-50 text-red-600 rounded-xl">
                        <ExclamationTriangleIcon class="w-6 h-6" />
                    </div>
                    <span class="text-sm font-bold text-gray-700">Kedisiplinan</span>
                </Link>

                <Link :href="route('student.productivity.index')" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center justify-center text-center gap-2 hover:bg-gray-50 transition-colors">
                    <div class="p-3 bg-pink-50 text-pink-600 rounded-xl">
                        <RocketLaunchIcon class="w-6 h-6" />
                    </div>
                    <span class="text-sm font-bold text-gray-700">Zona Produktif</span>
                </Link>

                <Link :href="route('profile.edit')" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center justify-center text-center gap-2 hover:bg-gray-50 transition-colors">
                    <div class="p-3 bg-purple-50 text-purple-600 rounded-xl">
                         <UserCircleIcon class="w-6 h-6" />
                    </div>
                    <span class="text-sm font-bold text-gray-700">Akun</span>
                </Link>
            </div>

            <form @submit.prevent="logout" class="block">
                <button type="submit" class="w-full bg-red-50 text-red-600 font-bold py-4 rounded-2xl flex items-center justify-center gap-2 hover:bg-red-100 transition-colors">
                    <ArrowRightOnRectangleIcon class="w-5 h-5" />
                    Keluar Aplikasi
                </button>
            </form>
            
            <p class="text-center text-xs text-gray-300 font-bold uppercase tracking-widest pt-4">
                Namira Portal v1.2.0
            </p>
        </div>
    </StudentLayout>
</template>
