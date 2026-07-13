<script setup>
import { ref } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { 
    HomeIcon, 
    CalendarIcon, 
    BanknotesIcon, 
    QueueListIcon,
    Squares2X2Icon,
    ArrowRightOnRectangleIcon,
    UserCircleIcon,
    ChevronDownIcon,
    ExclamationTriangleIcon,
    RocketLaunchIcon,
    ComputerDesktopIcon
} from '@heroicons/vue/24/outline';
import { 
    HomeIcon as HomeIconSolid, 
    CalendarIcon as CalendarIconSolid, 
    BanknotesIcon as BanknotesIconSolid, 
    QueueListIcon as QueueListIconSolid,
    Squares2X2Icon as Squares2X2IconSolid,
    ExclamationTriangleIcon as ExclamationTriangleIconSolid,
    RocketLaunchIcon as RocketLaunchIconSolid,
    ComputerDesktopIcon as ComputerDesktopIconSolid
} from '@heroicons/vue/24/solid';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const props = defineProps({
    title: String,
});

const user = usePage().props.auth.user;

// Navigation Items
const navItems = [
    { name: 'Beranda', route: 'student.dashboard', icon: HomeIcon, activeIcon: HomeIconSolid },
    { name: 'E-Learning', route: 'lms.student.classrooms.index', icon: ComputerDesktopIcon, activeIcon: ComputerDesktopIconSolid },
    { name: 'Akademik', route: 'student.academic', icon: CalendarIcon, activeIcon: CalendarIconSolid },
    { name: 'Keuangan', route: 'student.finance', icon: BanknotesIcon, activeIcon: BanknotesIconSolid },
    { name: 'Disiplin', route: 'student.counseling', icon: ExclamationTriangleIcon, activeIcon: ExclamationTriangleIconSolid },
    { name: 'Produktif', route: 'student.productivity.index', icon: RocketLaunchIcon, activeIcon: RocketLaunchIconSolid },
];

const isActive = (routeName) => route().current(routeName);

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex flex-col">
        <Head :title="title" />

        <!-- DESKTOP TOP NAVIGATION -->
        <!-- Visible only on md screens and up -->
        <!-- DESKTOP TOP NAVIGATION -->
        <!-- Visible only on md screens and up -->
        <header class="hidden md:block sticky top-0 z-50 bg-[#002824]/95 backdrop-blur-xl border-b border-white/10 shadow-lg shadow-teal-900/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20 items-center">
                    
                    <!-- Left: Logo & Nav -->
                    <div class="flex items-center gap-12">
                        <!-- Logo -->
                        <Link :href="route('student.dashboard')" class="flex items-center gap-3 group">
                            <div class="relative transition-transform duration-300 group-hover:scale-110 drop-shadow-sm">
                                <ApplicationLogo class="h-10 w-auto brightness-0 invert" />
                            </div>
                            <div>
                                <h1 class="font-bold text-xl text-white tracking-tight leading-none group-hover:text-teal-200 transition-colors">Namira</h1>
                                <p class="text-[10px] uppercase font-bold text-teal-200 tracking-widest">School Foundation</p>
                            </div>
                        </Link>

                        <!-- Desktop Menu Links -->
                        <nav class="flex items-center gap-1">
                            <Link 
                                v-for="item in navItems" 
                                :key="item.name" 
                                :href="route(item.route)"
                                class="px-5 py-2.5 rounded-full text-sm font-bold transition-all duration-200 flex items-center gap-2"
                                :class="isActive(item.route) 
                                    ? 'bg-white text-teal-800 shadow-lg shadow-white/10 scale-105' 
                                    : 'text-teal-100/70 hover:bg-white/10 hover:text-white'"
                            >
                                <component :is="isActive(item.route) ? item.activeIcon : item.icon" class="w-5 h-5" />
                                {{ item.name }}
                            </Link>
                        </nav>
                    </div>

                    <!-- Right: User Menu -->
                    <div class="flex items-center gap-4">
                        <!-- User Dropdown -->
                        <div class="ml-3 relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-4 py-2 bg-white/10 border border-white/10 rounded-full text-sm leading-4 font-medium text-white hover:bg-white/20 focus:outline-none transition ease-in-out duration-150 shadow-sm gap-3 group"
                                    >
                                        <div class="text-right hidden lg:block">
                                            <p class="font-bold text-white leading-none group-hover:text-teal-200 transition-colors">{{ user.name }}</p>
                                            <p class="text-[10px] text-teal-200/80 font-bold uppercase tracking-wider mt-0.5">{{ user.email }}</p>
                                        </div>
                                        
                                        <div class="h-9 w-9 rounded-full bg-teal-800 overflow-hidden ring-2 ring-white/20 group-hover:ring-white/40 transition-all">
                                            <img v-if="$page.props.auth.user.profile_photo_url" :src="$page.props.auth.user.profile_photo_url" class="w-full h-full object-cover">
                                            <div v-else class="w-full h-full flex items-center justify-center text-white font-bold bg-teal-700 text-xs">
                                                {{ user.name.charAt(0) }}
                                            </div>
                                        </div>

                                        <ChevronDownIcon class="w-4 h-4 text-teal-200 group-hover:text-white transition-colors" />
                                    </button>
                                </template>

                                <template #content>
                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                        Kelola Akun
                                    </div>
                                    <DropdownLink :href="route('profile.edit')">
                                        <div class="flex items-center gap-2">
                                            <UserCircleIcon class="w-4 h-4" /> Profil Saya
                                        </div>
                                    </DropdownLink>
                                    <div class="border-t border-gray-100 my-1"></div>
                                    <DropdownLink :href="route('logout')" method="post" as="button">
                                        <div class="flex items-center gap-2 text-red-600">
                                            <ArrowRightOnRectangleIcon class="w-4 h-4" /> Keluar
                                        </div>
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                </div>
            </div>
        </header>

        <!-- MAIN LAYOUT -->
        <!-- Added top padding for mobile to account for header, but verify. Sticky top needs spacing. Desktop uses h-20 header. -->
        <main class="flex-1 w-full pb-24 md:pb-12 pt-6 md:pt-8 bg-gray-50 bg-[url('/pattern.svg')]">
             <!-- Mobile Header -->
            <header class="md:hidden sticky top-0 z-20 px-6 py-4 flex items-center justify-between mb-6">
                <div>
                     <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest">Portal Siswa</p>
                     <h1 class="font-bold text-xl text-gray-800">Namira School</h1>
                </div>
                <Link :href="route('profile.edit')" class="w-10 h-10 rounded-full bg-white shadow-sm border border-gray-100 overflow-hidden relative">
                     <img v-if="$page.props.auth.user.profile_photo_url" :src="$page.props.auth.user.profile_photo_url" class="w-full h-full object-cover">
                     <div v-else class="w-full h-full flex items-center justify-center text-gray-400 font-bold bg-gray-100">
                        {{ user.name.charAt(0) }}
                    </div>
                </Link>
            </header>

            <div class="px-4 sm:px-6 md:px-8 max-w-7xl mx-auto w-full">
                <slot />
            </div>
        </main>

        <!-- MOBILE BOTTOM NAVIGATION -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-[0_-4px_20px_rgba(0,0,0,0.05)] z-40 pb-safe rounded-t-3xl">
            <ul class="flex justify-around items-center h-[72px]">
                <li v-for="item in navItems.slice(0, 4)" :key="item.name" class="flex-1">
                    <Link 
                        :href="route(item.route)" 
                        class="flex flex-col items-center justify-center h-full w-full gap-1 transition-all active:scale-90"
                        :class="isActive(item.route) ? 'text-namira-teal' : 'text-gray-300 hover:text-gray-500'"
                    >
                        <div class="relative p-1.5 rounded-xl transition-all duration-300" :class="isActive(item.route) ? 'bg-namira-teal/10 -translate-y-1' : ''">
                            <component 
                                :is="isActive(item.route) ? item.activeIcon : item.icon" 
                                class="w-6 h-6"
                            />
                        </div>
                        <span class="text-[10px] font-bold tracking-tight" :class="isActive(item.route) ? 'opacity-100' : 'opacity-0 scale-0 hidden'">{{ item.name }}</span>
                    </Link>
                </li>
                 <li class="flex-1">
                    <Link 
                        :href="route('student.menu')" 
                        class="flex flex-col items-center justify-center h-full w-full gap-1 transition-all active:scale-90"
                         :class="isActive('student.menu') ? 'text-namira-teal' : 'text-gray-300 hover:text-gray-500'"
                    >
                        <div class="relative p-1.5 rounded-xl transition-all duration-300" :class="isActive('student.menu') ? 'bg-namira-teal/10 -translate-y-1' : ''">
                            <component 
                                :is="isActive('student.menu') ? Squares2X2IconSolid : Squares2X2Icon" 
                                class="w-6 h-6"
                            />
                        </div>
                        <span class="text-[10px] font-bold tracking-tight" :class="isActive('student.menu') ? 'opacity-100' : 'opacity-0 scale-0 hidden'">Lainnya</span>
                    </Link>
                </li>
            </ul>
        </nav>

    </div>
</template>

<style scoped>
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>
