<script setup>
import { Head } from '@inertiajs/vue3';
import { 
    HeartIcon, ClockIcon, SparklesIcon, CalendarIcon, 
    CheckCircleIcon, PhotoIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    student: Object,
    date: String,
    attendance: Object,
    logs: Array,
    summary: Object,
});

const formattedDate = new Date(props.date).toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
});
</script>

<template>
    <Head :title="`Daily Report Ananda ${student.full_name}`" />

    <div class="min-h-screen bg-gradient-to-b from-amber-50/50 via-orange-50/30 to-white py-8 px-4 sm:px-6">
        <div class="max-w-xl mx-auto space-y-6">
            
            <!-- Header Card -->
            <div class="bg-white rounded-3xl p-6 border border-amber-100 shadow-lg shadow-amber-500/5 relative overflow-hidden text-center space-y-3">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-amber-400 to-orange-400 text-white font-black text-2xl mx-auto shadow-md overflow-hidden flex items-center justify-center border-2 border-white">
                    <img v-if="student.photo" :src="`/storage/${student.photo}`" class="w-full h-full object-cover" />
                    <span v-else>{{ student.full_name.substring(0, 2).toUpperCase() }}</span>
                </div>

                <div>
                    <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-800 font-extrabold text-[10px] uppercase tracking-wider">
                        Daily Report Pengasuhan
                    </span>
                    <h1 class="font-black text-2xl text-slate-800 mt-2">{{ student.full_name }}</h1>
                    <p class="text-xs font-bold text-amber-600 flex items-center justify-center gap-1 mt-0.5">
                        <CalendarIcon class="w-4 h-4" />
                        <span>{{ formattedDate }}</span>
                    </p>
                </div>
            </div>

            <!-- Attendance & Vital Summary Badges Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white p-4 rounded-2xl border border-slate-100 text-center shadow-xs">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Hadir Pagi</p>
                    <p class="text-sm font-black text-slate-800 mt-1">
                        {{ attendance?.check_in_time ? attendance.check_in_time.substring(0,5) : '-' }}
                    </p>
                    <span v-if="attendance?.check_in_temp" class="text-[10px] font-bold text-teal-600">
                        {{ attendance.check_in_temp }}°C
                    </span>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-100 text-center shadow-xs">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Pulang Sore</p>
                    <p class="text-sm font-black text-slate-800 mt-1">
                        {{ attendance?.check_out_time ? attendance.check_out_time.substring(0,5) : '-' }}
                    </p>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-100 text-center shadow-xs">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Tidur Siang</p>
                    <p class="text-sm font-black text-purple-600 mt-1">
                        {{ summary.total_nap_minutes > 0 ? `${summary.total_nap_minutes} Menit` : '-' }}
                    </p>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-100 text-center shadow-xs">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Total Susu</p>
                    <p class="text-sm font-black text-sky-600 mt-1">
                        {{ summary.milk_total_ml > 0 ? `${summary.milk_total_ml} ml` : '-' }}
                    </p>
                </div>
            </div>

            <!-- Single-Feed Care Timeline -->
            <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
                <h3 class="font-extrabold text-base text-slate-800 flex items-center gap-2">
                    <SparklesIcon class="w-5 h-5 text-amber-500" />
                    <span>Timeline Aktivitas Harian</span>
                </h3>

                <div v-if="logs.length > 0" class="relative pl-5 border-l-2 border-amber-200 space-y-5 pt-2">
                    <div v-for="log in logs" :key="log.id" class="relative">
                        <span class="absolute -left-[27px] top-1 w-3.5 h-3.5 rounded-full bg-amber-500 border-2 border-white shadow-xs"></span>
                        
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-black text-xs text-slate-900">{{ log.log_time.substring(0,5) }}</span>
                                <span class="font-extrabold text-xs text-amber-700">— {{ log.title }}</span>
                            </div>
                            <p v-if="log.description" class="text-xs text-slate-600 font-medium leading-relaxed">{{ log.description }}</p>
                            
                            <div v-if="log.amount_ml || log.portion_eaten" class="flex gap-2 text-[11px] font-bold text-slate-500">
                                <span v-if="log.amount_ml" class="text-sky-600">Volume: {{ log.amount_ml }} ml</span>
                                <span v-if="log.portion_eaten" class="text-amber-600">Porsi: {{ log.portion_eaten }}</span>
                            </div>

                            <img v-if="log.photo" :src="`/storage/${log.photo}`" class="w-full max-h-60 object-cover rounded-2xl mt-2 border border-slate-100" />
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-8 text-slate-400 text-xs">
                    Belum ada catatan aktivitas untuk hari ini.
                </div>
            </div>

            <!-- Footer Message -->
            <div class="text-center text-xs text-slate-400 font-bold py-4">
                ❤️ SuperApp Namira — Daycare & Care Log Engine
            </div>

        </div>
    </div>
</template>
