<script setup>
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref, computed } from 'vue';
import { 
    ClockIcon, 
    BookOpenIcon, 
    AcademicCapIcon, 
    CheckCircleIcon,
    ExclamationCircleIcon,
    XCircleIcon,
    CalendarDaysIcon,
    PresentationChartLineIcon,
    Bars3CenterLeftIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    student: Object,
    schedule: Object,
    subjects: Array,
    attendance: Object,
    journals: Array, // New Prop
});

const activeTab = ref('jadwal'); // jadwal, jurnal, absensi, mapel
const activeDay = ref('Monday'); // Default

const daysMap = {
    'Monday': 'Senin', 'Tuesday': 'Selasa', 'Wednesday': 'Rabu', 
    'Thursday': 'Kamis', 'Friday': 'Jumat', 'Saturday': 'Sabtu', 'Sunday': 'Minggu'
};

const translateDay = (day) => daysMap[day] || day;

// Compute Timeline sorted by Day
const sortedDays = computed(() => {
    const order = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    const keys = Object.keys(props.schedule).sort((a, b) => {
        return order.indexOf(translateDay(a)) - order.indexOf(translateDay(b));
    });
    // Set default active day if available and not set
    if (keys.length > 0 && !keys.includes(activeDay.value)) {
        activeDay.value = keys[0];
    }
    return keys;
});
</script>

<template>
    <StudentLayout title="Akademik">
        <div class="space-y-6">
            
            <!-- Header Compact -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Akademik</h2>
                    <p class="text-gray-500 text-sm">Pusat informasi kegiatan belajarmu.</p>
                </div>
                <!-- Classroom Badge -->
                 <div class="bg-white px-4 py-2 rounded-xl shadow-sm border border-gray-100 flex items-center gap-3 self-start">
                     <div class="bg-namira-teal/10 p-2 rounded-lg text-namira-teal">
                        <AcademicCapIcon class="w-5 h-5" />
                     </div>
                     <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Kelas</p>
                        <p class="font-bold text-gray-800">{{ student?.classroom?.name || 'Belum Masuk Kelas' }}</p>
                     </div>
                </div>
            </div>

            <!-- Tab Navigation (Pill Style) -->
            <div class="flex flex-wrap gap-2 p-1.5 bg-gray-100/50 rounded-2xl border border-gray-100 overflow-x-auto">
                <button @click="activeTab = 'jadwal'" 
                    class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2"
                    :class="activeTab === 'jadwal' ? 'bg-white text-namira-teal shadow-md shadow-gray-100' : 'text-gray-500 hover:bg-white/50'">
                    <CalendarDaysIcon class="w-4 h-4" /> Jadwal
                </button>
                <button @click="activeTab = 'jurnal'" 
                    class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2"
                    :class="activeTab === 'jurnal' ? 'bg-white text-orange-600 shadow-md shadow-gray-100' : 'text-gray-500 hover:bg-white/50'">
                    <PresentationChartLineIcon class="w-4 h-4" /> Materi & Jurnal
                </button>
                <button @click="activeTab = 'absensi'" 
                    class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2"
                    :class="activeTab === 'absensi' ? 'bg-white text-blue-600 shadow-md shadow-gray-100' : 'text-gray-500 hover:bg-white/50'">
                    <CheckCircleIcon class="w-4 h-4" /> Rekap Absensi
                </button>
                <button @click="activeTab = 'mapel'" 
                    class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2"
                    :class="activeTab === 'mapel' ? 'bg-white text-purple-600 shadow-md shadow-gray-100' : 'text-gray-500 hover:bg-white/50'">
                    <BookOpenIcon class="w-4 h-4" /> Mata Pelajaran
                </button>
            </div>

            <!-- TAB CONTENT: JADWAL (ADMIN-STYLE GRID) -->
            <div v-show="activeTab === 'jadwal'" class="overflow-x-auto pb-4">
                <div class="min-w-[1000px] lg:min-w-0"> <!-- Force horizontal scroll on small screens if needed -->
                    <div class="grid grid-cols-6 gap-4">
                        <div v-for="dayName in sortedDays" :key="dayName" class="flex flex-col gap-3">
                            <!-- Day Header -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3 text-center mb-2">
                                <h3 class="font-bold text-gray-800 border-b-2 border-namira-teal inline-block pb-1">{{ translateDay(dayName) }}</h3>
                            </div>

                            <!-- Schedule Cards -->
                            <div v-for="item in schedule[dayName]" :key="item.id" 
                                class="bg-white rounded-xl shadow-sm border border-gray-100 p-3 hover:shadow-md transition-shadow relative group">
                                
                                <!-- Time Badge -->
                                <div class="inline-block bg-teal-50 text-namira-teal text-[10px] font-bold px-2 py-0.5 rounded-md mb-2 border border-teal-100">
                                    {{ item.start_time.substring(0,5) }} - {{ item.end_time.substring(0,5) }}
                                </div>

                                <!-- Subject -->
                                <h4 class="font-bold text-gray-800 text-xs text-pretty line-clamp-3 mb-2 min-h-[32px] leading-tight">
                                    {{ item.subject?.name }}
                                </h4>

                                <!-- Teacher -->
                                <div class="flex items-center gap-1.5 pt-2 border-t border-gray-50">
                                    <div class="w-4 h-4 rounded-full bg-gray-100 flex items-center justify-center text-[8px] font-bold text-gray-500 shrink-0">
                                        {{ item.teacher?.full_name?.charAt(0) || '?' }}
                                    </div>
                                    <p class="text-[10px] text-gray-500 truncate" :title="item.teacher?.full_name">
                                        {{ item.teacher?.full_name?.split(' ')[0] || '-' }}
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Empty State for Day -->
                            <div v-if="!schedule[dayName] || schedule[dayName].length === 0" class="h-24 rounded-xl border-2 border-dashed border-gray-100 flex items-center justify-center">
                                <span class="text-[10px] text-gray-300 font-medium">Libur</span>
                            </div>
                        </div>
                    </div>
                </div>
                 <div v-if="Object.keys(schedule).length === 0" class="text-center py-8 bg-white rounded-2xl border border-gray-100 border-dashed">
                    <p class="text-gray-400 text-sm font-medium">Belum ada jadwal.</p>
                </div>
            </div>

            <!-- TAB CONTENT: JURNAL (MATERI) -->
            <div v-show="activeTab === 'jurnal'" class="space-y-4">
                <div v-if="journals.length === 0" class="text-center py-12 bg-white rounded-3xl border border-gray-100 border-dashed">
                    <div class="w-16 h-16 bg-orange-50 rounded-full flex items-center justify-center mx-auto mb-4 text-orange-400">
                        <PresentationChartLineIcon class="w-8 h-8" />
                    </div>
                    <p class="text-gray-400 font-medium">Belum ada jurnal materi dari guru.</p>
                </div>

                <div v-for="journal in journals" :key="journal.id" class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <span class="text-xs font-bold text-orange-600 bg-orange-50 px-2 py-1 rounded-md mb-2 inline-block">
                                {{ journal.day }}, {{ journal.date }}
                            </span>
                            <h3 class="font-bold text-gray-800 text-lg">{{ journal.subject }}</h3>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-400">Guru</p>
                            <p class="text-xs font-bold text-gray-700">{{ journal.teacher }}</p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 mb-3">
                         <p class="text-xs text-gray-400 font-bold uppercase mb-1">Topik Pembelajaran</p>
                         <p class="text-sm text-gray-800 leading-relaxed font-medium">{{ journal.topic }}</p>
                    </div>

                    <div v-if="journal.objectives.length > 0" class="space-y-1">
                        <div v-for="(obj, idx) in journal.objectives" :key="idx" class="flex items-start gap-2">
                             <CheckCircleIcon class="w-4 h-4 text-green-500 mt-0.5 shrink-0" />
                             <p class="text-xs text-gray-600">{{ obj }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB CONTENT: ABSENSI STATS -->
            <div v-show="activeTab === 'absensi'">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                     <h3 class="font-bold text-lg text-gray-800 mb-6 flex items-center gap-2">
                        <CheckCircleIcon class="w-5 h-5 text-blue-600" />
                        Statistik Kehadiran Semester Ini
                    </h3>
                    
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                        <div class="text-center p-4 bg-green-50 rounded-2xl border border-green-100">
                            <p class="text-3xl font-bold text-green-600">{{ attendance.present }}</p>
                            <p class="text-xs font-bold uppercase text-green-700/60 mt-1">Hadir</p>
                        </div>
                        <div class="text-center p-4 bg-blue-50 rounded-2xl border border-blue-100">
                            <p class="text-3xl font-bold text-blue-600">{{ attendance.permission }}</p>
                            <p class="text-xs font-bold uppercase text-blue-700/60 mt-1">Izin</p>
                        </div>
                        <div class="text-center p-4 bg-yellow-50 rounded-2xl border border-yellow-100">
                            <p class="text-3xl font-bold text-yellow-600">{{ attendance.sick }}</p>
                            <p class="text-xs font-bold uppercase text-yellow-700/60 mt-1">Sakit</p>
                        </div>
                        <div class="text-center p-4 bg-red-50 rounded-2xl border border-red-100">
                            <p class="text-3xl font-bold text-red-600">{{ attendance.alpha }}</p>
                            <p class="text-xs font-bold uppercase text-red-700/60 mt-1">Alpha</p>
                        </div>
                    </div>

                    <!-- Progress Visualization (Simple) -->
                    <div class="relative pt-6 border-t border-gray-100">
                         <div class="flex items-center justify-between text-sm mb-2">
                            <span class="font-medium text-gray-500">Persentase Kehadiran</span>
                            <span class="font-bold text-gray-800">
                                {{ (attendance.present + attendance.sick + attendance.permission + attendance.alpha) > 0 
                                    ? Math.round((attendance.present / (attendance.present + attendance.sick + attendance.permission + attendance.alpha)) * 100) 
                                    : 0 }}%
                            </span>
                         </div>
                         <div class="h-3 w-full bg-gray-100 rounded-full overflow-hidden flex">
                             <div class="h-full bg-green-500 transition-all duration-1000" :style="`width: ${(attendance.present / (attendance.present + attendance.sick + attendance.permission + attendance.alpha)) * 100}%`"></div>
                             <div class="h-full bg-yellow-400" :style="`width: ${(attendance.sick / (attendance.present + attendance.sick + attendance.permission + attendance.alpha)) * 100}%`"></div>
                             <div class="h-full bg-blue-400" :style="`width: ${(attendance.permission / (attendance.present + attendance.sick + attendance.permission + attendance.alpha)) * 100}%`"></div>
                             <div class="h-full bg-red-500" :style="`width: ${(attendance.alpha / (attendance.present + attendance.sick + attendance.permission + attendance.alpha)) * 100}%`"></div>
                         </div>
                         <p class="text-center text-xs text-gray-400 mt-4">Hijau: Hadir • Kuning: Sakit • Biru: Izin • Merah: Alpha</p>
                    </div>
                </div>
            </div>

            <!-- TAB CONTENT: MAPEL -->
            <div v-show="activeTab === 'mapel'">
                 <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <div v-for="subject in subjects" :key="subject.id" class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:border-namira-teal/30 hover:bg-teal-50/10 transition-colors">
                        <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-lg border border-purple-100">
                             {{ subject.name.substring(0,1) }}
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800 line-clamp-1" :title="subject.name">{{ subject.name }}</h4>
                            <p class="text-xs text-gray-500 font-medium">{{ subject.code || 'Tanpa Kode' }}</p>
                        </div>
                    </div>
                 </div>
                 <div v-if="subjects.length === 0" class="text-center py-12">
                     <p class="text-gray-400">Belum ada mata pelajaran terdaftar.</p>
                 </div>
            </div>

        </div>
    </StudentLayout>
</template>
