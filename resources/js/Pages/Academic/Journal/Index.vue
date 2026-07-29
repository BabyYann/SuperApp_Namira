<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { CalendarIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    schedules: Array,
    date: String,
});

const selectedDate = ref(props.date);

watch(selectedDate, (newVal) => {
    router.get(route('yayasan.teaching-journal.index'), { date: newVal }, { preserveState: true, preserveScroll: true });
});

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'full' }).format(date);
};
</script>

<template>
    <Head title="Jurnal Mengajar" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4">
                <!-- Top Row: Title & Description -->
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Jurnal Mengajar
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Catat aktivitas mengajar harian Anda.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-4 md:py-6 max-w-7xl mx-auto space-y-5 md:space-y-6">
            
            <!-- 1A. DESKTOP TOOLBAR (Unchanged Desktop Layout) -->
            <div class="hidden md:flex items-center gap-4 flex-wrap">
                <div class="relative group w-auto">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                        <CalendarIcon class="w-5 h-5" />
                    </div>
                    <input 
                        type="date" 
                        v-model="selectedDate" 
                        class="pl-10 pr-4 py-2.5 w-64 bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm font-bold focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                    >
                </div>
                <!-- Date Display -->
                <div class="text-sm font-bold text-gray-600 bg-white/50 px-4 py-2.5 rounded-2xl border border-white/50 shadow-sm h-[46px] flex items-center">
                    {{ formatDate(date) }}
                </div>
                <!-- Export Button -->
                <a :href="route('yayasan.teaching-journal.export', { month: new Date(date).getMonth() + 1, year: new Date(date).getFullYear() })" class="px-4 py-2.5 bg-green-500 text-white rounded-2xl font-bold hover:bg-green-600 flex items-center gap-2 h-[46px] shadow-lg shadow-green-500/30 ml-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Rekap Bulan Ini</span>
                </a>
            </div>

            <!-- 1B. MOBILE TOOLBAR (Executive Deep Emerald Date Picker & Quick Actions) -->
            <div class="block md:hidden bg-[#064e3b] text-white p-4 rounded-3xl border border-emerald-800/80 shadow-xl space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black tracking-widest text-teal-400 uppercase">Jurnal Mengajar</span>
                        <h3 class="text-sm font-extrabold text-white mt-0.5">{{ formatDate(date) }}</h3>
                    </div>
                    <a :href="route('yayasan.teaching-journal.export', { month: new Date(date).getMonth() + 1, year: new Date(date).getFullYear() })" class="p-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl text-xs font-extrabold flex items-center gap-1.5 shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span>Rekap</span>
                    </a>
                </div>
                <div class="relative w-full">
                    <input 
                        type="date" 
                        v-model="selectedDate" 
                        class="w-full bg-slate-800 border border-slate-700 text-white rounded-2xl text-xs font-bold p-3 focus:ring-teal-500 focus:border-teal-500"
                    >
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="schedules.length === 0" class="text-center py-12 md:py-16 bg-white rounded-3xl border border-slate-200 md:border-gray-100 shadow-sm flex flex-col items-center justify-center p-6">
                <div class="bg-slate-50 md:bg-gray-50 p-5 md:p-6 rounded-full mb-4 md:mb-6">
                    <CalendarIcon class="w-12 h-12 md:w-16 md:h-16 text-slate-400" />
                </div>
                <h3 class="text-lg md:text-xl font-bold text-slate-900 mb-1">Libur Mengajar? 🎉</h3>
                <p class="text-xs md:text-sm text-slate-500 font-medium max-w-sm">Tidak ada jadwal mengajar pada tanggal ini. Silakan pilih tanggal lain di atas.</p>
            </div>

            <!-- 2A. DESKTOP SCHEDULE LIST (Unchanged Desktop Layout) -->
            <div v-else class="hidden md:grid grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="item in schedules" :key="'desk-'+item.id" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow relative overflow-hidden">
                    <div class="absolute left-0 top-0 bottom-0 w-1.5" :class="item.is_filled ? 'bg-green-500' : 'bg-red-500'"></div>
                    
                    <div class="pl-3">
                        <div class="flex justify-between items-start mb-2">
                            <span class="px-2 py-1 bg-gray-100 rounded-lg text-xs font-bold text-gray-600">
                                {{ item.start_time.substring(0, 5) }} - {{ item.end_time.substring(0, 5) }}
                            </span>
                            <span class="px-2 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider" 
                                :class="item.is_filled ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                                {{ item.is_filled ? 'SUDAH DIISI' : 'BELUM DIISI' }}
                            </span>
                        </div>

                        <h3 class="font-bold text-lg text-gray-900 leading-tight mb-1">{{ item.subject }}</h3>
                        <p class="text-sm text-gray-500 font-medium mb-4">{{ item.classroom }}</p>

                        <div class="pt-4 border-t border-gray-50">
                            <Link 
                                v-if="!item.is_filled"
                                :href="route('yayasan.teaching-journal.create', { schedule_id: item.id, date: date })" 
                                class="block w-full text-center py-2.5 bg-namira-teal text-white font-bold rounded-xl shadow-lg shadow-namira-teal/30 hover:bg-teal-600 transition-all active:scale-95"
                            >
                                Isi Jurnal
                            </Link>
                            <Link 
                                v-else 
                                :href="route('yayasan.teaching-journal.show', item.journal_id)"
                                class="block w-full text-center py-2.5 bg-white border-2 border-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-50 hover:border-gray-200 transition-all"
                            >
                                Lihat Laporan
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2B. MOBILE SCHEDULE LIST (Executive Mobile Native Cards) -->
            <div v-if="schedules.length > 0" class="grid md:hidden grid-cols-1 gap-3.5">
                <div 
                    v-for="item in schedules" 
                    :key="'mob-'+item.id" 
                    class="bg-white rounded-3xl p-4 border border-slate-200 shadow-sm relative overflow-hidden flex flex-col justify-between"
                >
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-3 py-1 bg-slate-100 rounded-xl text-xs font-black text-slate-700">
                            {{ item.start_time.substring(0, 5) }} - {{ item.end_time.substring(0, 5) }} WIB
                        </span>
                        <span 
                            class="px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider border"
                            :class="item.is_filled ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                        >
                            {{ item.is_filled ? '🟢 SUDAH DIISI' : '🔴 BELUM DIISI' }}
                        </span>
                    </div>

                    <div class="mb-4">
                        <h4 class="font-extrabold text-base text-slate-900 leading-snug">{{ item.subject }}</h4>
                        <p class="text-xs font-bold text-teal-700 mt-0.5">{{ item.classroom }}</p>
                    </div>

                    <Link 
                        v-if="!item.is_filled"
                        :href="route('yayasan.teaching-journal.create', { schedule_id: item.id, date: date })" 
                        class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-2xl shadow-md text-center block transition-all active:scale-95"
                    >
                        Isi Jurnal Mengajar
                    </Link>
                    <Link 
                        v-else 
                        :href="route('yayasan.teaching-journal.show', item.journal_id)"
                        class="w-full py-3 bg-slate-100 hover:bg-slate-200 text-slate-800 font-extrabold text-xs rounded-2xl border border-slate-200 text-center block transition-all active:scale-95"
                    >
                        Lihat Laporan Jurnal
                    </Link>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
