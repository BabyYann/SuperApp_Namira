<script setup>
import { ref } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Bars3BottomLeftIcon, BellIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';

defineProps({
    user: Object,
});

const emit = defineEmits(['toggleSidebar']);
</script>

<template>
    <header class="sticky top-0 z-40 flex h-16 w-full items-center justify-between border-b border-white/50 bg-white/80 backdrop-blur-xl px-6 transition-all duration-300">
        <!-- Left: Toggle & Title -->
        <div class="flex items-center gap-4">
            <button @click="$emit('toggleSidebar')" class="rounded-lg p-2 text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-colors">
                <Bars3BottomLeftIcon class="h-5 w-5" />
            </button>
            
            <div class="h-6 w-px bg-slate-200 mx-2 hidden md:block"></div>

            <h2 class="text-sm font-bold text-slate-700 tracking-tight hidden md:block">
                Administration
            </h2>
        </div>

        <!-- Right: Actions -->
        <div class="flex items-center gap-3">
            <!-- Notification Bell -->
             <button class="relative rounded-xl p-2 text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-all group">
                 <span class="absolute top-2.5 right-2.5 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                <BellIcon class="h-5 w-5 group-hover:scale-105 transition-transform" />
             </button>

            <!-- User Dropdown -->
            <Dropdown align="right" width="48">
                <template #trigger>
                    <button
                        class="flex items-center gap-3 rounded-xl border border-transparent py-1.5 pl-1.5 pr-3 transition-all duration-200 hover:bg-slate-50 hover:border-slate-200 focus:outline-none"
                    >
                         <div class="h-8 w-8 rounded-lg overflow-hidden bg-gradient-to-br from-slate-800 to-slate-900 flex items-center justify-center text-white font-bold text-xs shadow-sm">
                            <img v-if="user.profile_photo_url && !user.profile_photo_url.includes('ui-avatars')" :src="user.profile_photo_url" class="w-full h-full object-cover">
                            <span v-else>{{ user.name.charAt(0) }}</span>
                         </div>
                         <div class="text-left hidden md:block">
                             <p class="text-xs font-bold text-slate-700 leading-none mb-0.5">{{ user.name }}</p>
                             <p class="text-[10px] text-slate-400 font-medium leading-none">{{ user.email }}</p>
                         </div>
                        <ChevronDownIcon class="ml-1 h-4 w-4 text-slate-400" />
                    </button>
                </template>

                <template #content>
                    <div class="px-4 py-3 border-b border-gray-100">
                        <p class="text-sm font-bold text-gray-900">Signed in as</p>
                        <p class="text-xs text-gray-500 truncate">{{ user.email }}</p>
                    </div>
                    <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                    <DropdownLink :href="route('logout')" method="post" as="button">
                        Log Out
                    </DropdownLink>
                </template>
            </Dropdown>
        </div>
    </header>
</template>
