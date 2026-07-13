<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { HomeIcon, CalendarIcon, AcademicCapIcon, UserIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const user = computed(() => page.props.auth.user);

// Navigation Items
const navItems = [
    {
        name: 'Home',
        route: 'dashboard',
        icon: HomeIcon,
        active: route().current('dashboard')
    },
    {
        name: 'Jadwal',
        route: 'yayasan.schedules.index',
        icon: CalendarIcon,
        active: route().current('yayasan.schedules.*')
    },
    {
        name: 'Akademik',
        route: 'yayasan.teachers.index', // Default entry point
        icon: AcademicCapIcon,
        active: route().current('yayasan.teachers.*') || route().current('yayasan.students.*') || route().current('yayasan.units.*')
    },
    {
        name: 'Akun',
        route: 'profile.edit',
        icon: UserIcon,
        active: route().current('profile.*')
    }
];
</script>

<template>
    <div class="min-h-screen bg-gray-50 pb-20">
        <!-- Top Bar (Minimalist) -->
        <header class="bg-white/80 backdrop-blur-xl border-b border-gray-100 sticky top-0 z-40 px-4 py-3 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <!-- Logo or Back Button Logic could go here -->
                <div class="w-8 h-8 rounded-full bg-namira-teal flex items-center justify-center text-white font-bold text-xs shadow-lg shadow-namira-teal/30">
                    N
                </div>
                <h1 class="font-bold text-gray-800 text-lg tracking-tight">SuperApp Namira</h1>
            </div>
            
            <!-- User Avatar (Small) -->
            <Link :href="route('profile.edit')" class="w-8 h-8 rounded-full bg-gray-200 overflow-hidden border border-white shadow-sm">
                <img 
                    v-if="user?.profile_photo_url" 
                    :src="user.profile_photo_url" 
                    class="w-full h-full object-cover" 
                    alt="User"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-gray-500 text-xs font-bold">
                    {{ user?.name?.charAt(0) || 'U' }}
                </div>
            </Link>
        </header>

        <!-- Main Content -->
        <main class="p-4">
            <slot />
        </main>

        <!-- Bottom Navigation Bar -->
        <nav class="fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-xl border-t border-gray-200 pb-safe pt-2 px-6 z-50 shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
            <div class="flex justify-between items-center max-w-md mx-auto">
                <Link 
                    v-for="item in navItems" 
                    :key="item.name"
                    :href="route(item.route)"
                    class="flex flex-col items-center gap-1 p-2 transition-all duration-300 group relative"
                    :class="item.active ? 'text-namira-teal' : 'text-gray-400 hover:text-gray-600'"
                >
                    <!-- Active Indicator -->
                    <div v-if="item.active" class="absolute -top-2 w-8 h-1 bg-namira-teal rounded-b-full shadow-[0_0_10px_rgba(20,184,166,0.5)]"></div>

                    <component 
                        :is="item.icon" 
                        class="w-6 h-6 transition-transform group-active:scale-90" 
                        :class="{'text-namira-teal': item.active}" 
                    />
                    <span class="text-[10px] font-bold">{{ item.name }}</span>
                </Link>
            </div>
        </nav>
    </div>
</template>

<style scoped>
/* Safe Area for iPhone Home Indicator */
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom, 20px);
}
</style>
