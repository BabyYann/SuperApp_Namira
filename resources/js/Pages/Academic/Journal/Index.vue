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

        <div class="py-6 max-w-7xl mx-auto space-y-6">
            <!-- Toolbar: Date Picker -->
            <div class="flex items-center gap-4 flex-wrap">
                <div class="relative group w-full md:w-auto">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                        <CalendarIcon class="w-5 h-5" />
                    </div>
                    <input 
                        type="date" 
                        v-model="selectedDate" 
                        class="pl-10 pr-4 py-2.5 w-full md:w-64 bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm font-bold focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
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

            <!-- Schedule List -->
            <div v-if="schedules.length === 0" class="text-center py-16 bg-white rounded-3xl border border-gray-100 shadow-sm flex flex-col items-center justify-center">
                <div class="bg-gray-50 p-6 rounded-full mb-6">
                    <CalendarIcon class="w-16 h-16 text-gray-400" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Libur Mengajar? 🎉</h3>
                <p class="text-gray-500 font-medium">Tidak ada jadwal mengajar pada tanggal ini. Silakan pilih tanggal lain.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="item in schedules" :key="item.id" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow relative overflow-hidden">
                    <!-- Status Indicator Strip -->
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

        </div>
    </AuthenticatedLayout>
</template>
