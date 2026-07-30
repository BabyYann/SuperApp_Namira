<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import {
    Bars3BottomLeftIcon, BellIcon, ChevronDownIcon,
    CheckCircleIcon, EnvelopeOpenIcon, CalendarIcon,
    MegaphoneIcon, ClipboardDocumentCheckIcon
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    user: Object,
});

const emit = defineEmits(['toggleSidebar']);

// Notifications state
const notifications = ref([]);
const unreadCount = ref(0);
const isOpen = ref(false);
const isLoading = ref(false);
let pollInterval = null;

const fetchNotifications = async () => {
    try {
        isLoading.value = true;
        const res = await axios.get('/api/notifications?per_page=10');
        notifications.value = res.data.data || [];
        unreadCount.value = notifications.value.filter(n => !n.is_read).length;
    } catch (e) {
        console.error('Failed to fetch notifications:', e);
    } finally {
        isLoading.value = false;
    }
};

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        fetchNotifications();
    }
};

const markAllRead = async () => {
    try {
        await axios.post('/api/notifications/read-all');
        notifications.value.forEach(n => n.is_read = true);
        unreadCount.value = 0;
    } catch (e) {
        console.error('Failed to mark all as read:', e);
    }
};

const markRead = async (item) => {
    if (!item.is_read) {
        try {
            await axios.post(`/api/notifications/${item.id}/read`);
            item.is_read = true;
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        } catch (e) {
            console.error('Failed to mark read:', e);
        }
    }
};

onMounted(() => {
    fetchNotifications();
    pollInterval = setInterval(fetchNotifications, 30000); // poll every 30s
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});
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
            
            <!-- Notification Bell with Dropdown -->
            <div class="relative">
                <button
                    @click="toggleDropdown"
                    type="button"
                    class="relative rounded-xl p-2 text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-all group"
                    title="Notifikasi"
                >
                    <span v-if="unreadCount > 0" class="absolute top-1.5 right-1.5 min-w-4 h-4 px-1 rounded-full bg-rose-500 text-white text-[9px] font-extrabold flex items-center justify-center ring-2 ring-white animate-pulse">
                        {{ unreadCount > 9 ? '9+' : unreadCount }}
                    </span>
                    <BellIcon class="h-5 w-5 group-hover:scale-105 transition-transform" />
                </button>

                <!-- Notification Dropdown Menu -->
                <div
                    v-if="isOpen"
                    class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white shadow-2xl border border-slate-100 py-2 z-50 animate-in fade-in zoom-in-95 duration-150"
                >
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <BellIcon class="w-4 h-4 text-teal-600" />
                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Notifikasi Sistem</span>
                            <span v-if="unreadCount > 0" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-600 border border-rose-100">
                                {{ unreadCount }} Baru
                            </span>
                        </div>
                        <button
                            v-if="unreadCount > 0"
                            @click="markAllRead"
                            type="button"
                            class="text-[11px] text-teal-600 font-bold hover:underline"
                        >
                            Tandai Semua Dibaca
                        </button>
                    </div>

                    <!-- Notification List -->
                    <div class="max-h-80 overflow-y-auto divide-y divide-slate-50">
                        <div v-if="isLoading" class="p-6 text-center text-xs text-slate-400">
                            Memuat notifikasi...
                        </div>
                        <div v-else-if="notifications.length === 0" class="p-6 text-center space-y-2">
                            <EnvelopeOpenIcon class="w-8 h-8 text-slate-300 mx-auto" />
                            <p class="text-xs text-slate-400 font-medium">Belum ada notifikasi baru.</p>
                        </div>
                        <div
                            v-else
                            v-for="item in notifications"
                            :key="item.id"
                            @click="markRead(item)"
                            class="p-3.5 hover:bg-slate-50 transition flex items-start gap-3 cursor-pointer"
                            :class="!item.is_read ? 'bg-teal-50/30' : ''"
                        >
                            <div class="w-8 h-8 rounded-xl shrink-0 flex items-center justify-center mt-0.5"
                                 :class="!item.is_read ? 'bg-teal-100 text-teal-700' : 'bg-slate-100 text-slate-500'">
                                <CalendarIcon v-if="item.type === 'public_relations'" class="w-4 h-4" />
                                <ClipboardDocumentCheckIcon v-else-if="item.type === 'employee'" class="w-4 h-4" />
                                <BellIcon v-else class="w-4 h-4" />
                            </div>

                            <div class="space-y-0.5 flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-1">
                                    <h4 class="text-xs font-bold text-slate-800 truncate" :class="!item.is_read ? 'text-teal-950 font-black' : ''">{{ item.title }}</h4>
                                    <span class="text-[10px] text-slate-400 shrink-0 font-mono">{{ item.created_at }}</span>
                                </div>
                                <p class="text-[11px] text-slate-600 line-clamp-2 leading-relaxed">{{ item.message }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="px-4 py-2 border-t border-slate-100 text-center bg-slate-50/50 rounded-b-2xl">
                        <span class="text-[10px] text-slate-400 font-medium">Terhubung dengan FCM Push Notification</span>
                    </div>
                </div>
            </div>

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
