<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { WifiIcon, ArrowPathIcon, CheckCircleIcon } from '@heroicons/vue/24/solid';

const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);
const showReconnected = ref(false);
const isRetrying = ref(false);
let reconnectTimeout = null;

const handleOnline = () => {
    isOnline.value = true;
    showReconnected.value = true;
    if (reconnectTimeout) clearTimeout(reconnectTimeout);
    reconnectTimeout = setTimeout(() => {
        showReconnected.value = false;
    }, 4000);
};

const handleOffline = () => {
    isOnline.value = false;
    showReconnected.value = false;
};

const retryConnection = async () => {
    isRetrying.value = true;
    try {
        await fetch('/manifest.json?_t=' + Date.now(), { method: 'HEAD', cache: 'no-store' });
        handleOnline();
    } catch {
        isOnline.value = false;
    } finally {
        setTimeout(() => {
            isRetrying.value = false;
        }, 600);
    }
};

onMounted(() => {
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
});

onUnmounted(() => {
    window.removeEventListener('online', handleOnline);
    window.removeEventListener('offline', handleOffline);
    if (reconnectTimeout) clearTimeout(reconnectTimeout);
});
</script>

<template>
    <div class="fixed top-3 left-1/2 -translate-x-1/2 z-[9999] pointer-events-none px-4 w-full max-w-md transition-all duration-300">
        <!-- Offline Pill Banner -->
        <transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="-translate-y-4 opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="-translate-y-4 opacity-0 scale-95"
        >
            <div
                v-if="!isOnline"
                class="pointer-events-auto flex items-center justify-between gap-3 px-4 py-2.5 bg-slate-900/95 text-white backdrop-blur-md rounded-2xl shadow-2xl border border-amber-500/30 text-xs"
            >
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="relative flex h-2.5 w-2.5 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                    </span>
                    <div class="truncate">
                        <p class="font-bold text-amber-300 leading-tight">Mode Offline Aktif</p>
                        <p class="text-[11px] text-slate-300 truncate">Menampilkan data tersimpan dari cache</p>
                    </div>
                </div>

                <button
                    @click="retryConnection"
                    :disabled="isRetrying"
                    class="shrink-0 flex items-center gap-1.5 px-3 py-1 bg-amber-500/20 hover:bg-amber-500/30 active:scale-95 text-amber-300 hover:text-amber-200 border border-amber-500/40 rounded-xl font-bold transition text-[11px] cursor-pointer"
                >
                    <ArrowPathIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': isRetrying }" />
                    <span>{{ isRetrying ? 'Mengecek...' : 'Hubungkan' }}</span>
                </button>
            </div>
        </transition>

        <!-- Reconnected Success Pill -->
        <transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="-translate-y-4 opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transition ease-in duration-300"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="-translate-y-4 opacity-0 scale-95"
        >
            <div
                v-if="isOnline && showReconnected"
                class="pointer-events-auto flex items-center justify-center gap-2 px-4 py-2 bg-emerald-950/90 text-emerald-100 backdrop-blur-md rounded-2xl shadow-xl border border-emerald-500/40 text-xs"
            >
                <CheckCircleIcon class="w-4 h-4 text-emerald-400 shrink-0" />
                <span class="font-semibold text-[11.5px]">Koneksi internet kembali stabil</span>
            </div>
        </transition>
    </div>
</template>
