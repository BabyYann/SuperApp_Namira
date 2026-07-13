<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    ArrowLeftIcon, 
    ExclamationTriangleIcon, 
    ShieldCheckIcon,
    TrophyIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    student: Object,
    violations: Array,

    achievements: Array,
    sessions: Array,
    summary: Object,
});

const activeTab = ref('violations');
</script>

<template>
    <Head title="Kedisiplinan & Poin" />

    <div class="min-h-screen bg-[#F2F4F8] font-sans pb-20 relative overflow-hidden">
        
        <!-- Decorative Background Shapes (Responsive) -->
        <div class="absolute top-0 left-0 w-full h-[500px] bg-gradient-to-br from-namira-teal/10 via-blue-100/10 to-transparent pointer-events-none"></div>
        <div class="absolute -top-40 -right-40 w-[600px] h-[600px] bg-blue-200/20 rounded-full blur-3xl pointer-events-none opacity-50"></div>

        <!-- Main Container -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">

            <!-- Header -->
            <div class="mb-8 flex items-center gap-4 relative z-10">
                <Link :href="route('student.dashboard')" class="p-2.5 bg-white/80 backdrop-blur-xl rounded-xl shadow-sm border border-white/60 text-slate-600 hover:text-slate-900 transition-transform active:scale-95 group">
                    <ArrowLeftIcon class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Kedisiplinan & Prestasi</h1>
                    <p class="text-sm text-slate-500 font-medium">Monitoring poin dan rekam jejak prestasi Anda.</p>
                </div>
            </div>

            <!-- Responsive Grid Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 relative z-10">
                
                <!-- LEFT COLUMN: Status Card (Sticky on Desktop) -->
                <div class="lg:col-span-4 xl:col-span-3">
                    <div class="lg:sticky lg:top-24 space-y-6">
                        
                        <!-- Status Card -->
                        <div class="relative group">
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-200/30 to-teal-200/30 rounded-[2rem] blur-xl transform group-hover:scale-105 transition-transform duration-500 opacity-70"></div>
                            <div class="bg-white/80 backdrop-blur-2xl rounded-[2rem] p-8 shadow-[0_8px_32px_rgba(0,0,0,0.05)] border border-white/60 relative overflow-hidden text-center flex flex-col items-center">
                                
                                <!-- Icon Ring -->
                                <div class="w-24 h-24 rounded-full flex items-center justify-center mb-6 shadow-inner relative overflow-hidden" :class="summary.status_color.replace('text-', 'bg-').replace('bg-', 'bg-opacity-10 ')">
                                     <div class="absolute inset-0 opacity-20" :class="summary.status_color.replace('text-', 'bg-')"></div>
                                     <ShieldCheckIcon v-if="summary.status === 'AMAN'" class="w-12 h-12 relative z-10" :class="summary.status_color" />
                                     <ExclamationTriangleIcon v-else class="w-12 h-12 relative z-10" :class="summary.status_color" />
                                </div>
                                
                                <div class="space-y-1 mb-6">
                                    <h2 class="text-6xl font-black text-slate-800 tracking-tighter">{{ summary.total_points }}</h2>
                                    <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">Total Poin</p>
                                </div>
                                
                                <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full border shadow-sm transition-all hover:shadow-md" :class="summary.status_color">
                                    <span class="w-2.5 h-2.5 rounded-full animate-pulse" :class="summary.status_color.replace('text-', 'bg-')"></span>
                                    <span class="text-xs font-black tracking-wider">STATUS: {{ summary.status }}</span>
                                </div>

                                <p class="text-xs text-slate-400 mt-6 leading-relaxed px-4">
                                    "Jaga poin tetap rendah untuk menghindari sanksi akademik."
                                </p>
                            </div>
                        </div>

                        <!-- Mini Info (Desktop Only) -->
                        <div class="hidden lg:block bg-white/60 backdrop-blur-md rounded-2xl p-6 border border-white/60 shadow-sm">
                             <h3 class="text-sm font-bold text-slate-700 mb-3">Ketentuan Poin</h3>
                             <ul class="space-y-3">
                                 <li class="flex items-center gap-3 text-xs text-slate-500">
                                     <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                     <span>0-20 Poin: <b>Aman</b></span>
                                 </li>
                                 <li class="flex items-center gap-3 text-xs text-slate-500">
                                     <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                                     <span>21-50 Poin: <b>Waspada</b> (SP1)</span>
                                 </li>
                                 <li class="flex items-center gap-3 text-xs text-slate-500">
                                     <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                     <span>>50 Poin: <b>Bahaya</b> (SP2/3)</span>
                                 </li>
                             </ul>
                        </div>

                    </div>
                </div>

                <!-- RIGHT COLUMN: Content & Timeline -->
                <div class="lg:col-span-8 xl:col-span-9 space-y-6">
                    
                    <!-- Tabs -->
                    <div class="bg-white/60 backdrop-blur-md p-1.5 rounded-2xl flex border border-white/50 shadow-sm max-w-sm">
                        <button @click="activeTab = 'violations'" 
                            class="flex-1 py-2.5 text-xs font-bold rounded-xl transition-all"
                            :class="activeTab === 'violations' ? 'bg-white shadow-sm text-slate-800 ring-1 ring-black/5' : 'text-slate-400 hover:text-slate-600'">
                            Riwayat Pelanggaran
                        </button>
                        <button @click="activeTab = 'achievements'"
                            class="flex-1 py-2.5 text-xs font-bold rounded-xl transition-all"
                            :class="activeTab === 'achievements' ? 'bg-white shadow-sm text-yellow-600 ring-1 ring-black/5' : 'text-slate-400 hover:text-slate-600'">
                            Prestasi Siswa
                        </button>
                        <button @click="activeTab = 'sessions'"
                            class="flex-1 py-2.5 text-xs font-bold rounded-xl transition-all"
                            :class="activeTab === 'sessions' ? 'bg-white shadow-sm text-purple-600 ring-1 ring-black/5' : 'text-slate-400 hover:text-slate-600'">
                            Sesi Konseling
                        </button>
                    </div>

                    <!-- VIOLATIONS TAB -->
                    <div v-if="activeTab === 'violations'" class="bg-white/40 backdrop-blur-sm rounded-[2.5rem] p-6 sm:p-8 lg:p-10 border border-white/60 shadow-sm min-h-[500px] animate-in fade-in slide-in-from-bottom-4 duration-500">
                        
                        <div v-if="violations.length === 0" class="flex flex-col items-center justify-center py-20 text-center space-y-6">
                             <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center shadow-sm">
                                <ShieldCheckIcon class="w-12 h-12 text-teal-300" />
                             </div>
                             <div>
                                 <h3 class="text-xl font-bold text-slate-700">Catatan Bersih!</h3>
                                 <p class="text-sm text-slate-500 mt-2 max-w-xs mx-auto">Tidak ada pelanggaran yang tercatat. Good job, pertahankan sikap terpujimu!</p>
                             </div>
                        </div>

                        <div v-else class="space-y-8 relative pl-4">
                             <!-- Vertical Line -->
                             <div class="absolute left-[27px] top-6 bottom-6 w-0.5 bg-slate-200 rounded-full"></div>

                            <div v-for="(v, index) in violations" :key="v.id" class="relative pl-14 group">
                                <!-- Timeline Dot -->
                                <div class="absolute left-0 top-0 w-14 h-14 rounded-2xl bg-white border border-slate-100 shadow-sm flex items-center justify-center z-10 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3 group-hover:shadow-md">
                                    <span class="text-lg font-black" :class="v.points > 20 ? 'text-red-500' : 'text-teal-600'">{{ v.points }}</span>
                                    <span class="text-[8px] absolute bottom-1.5 font-bold text-slate-300 uppercase">Poin</span>
                                </div>
                                
                                <!-- Card Content -->
                                <div class="bg-white rounded-3xl p-6 shadow-[0_2px_20px_rgba(0,0,0,0.02)] border border-slate-100 group-hover:shadow-[0_8px_30px_rgba(0,0,0,0.04)] group-hover:-translate-y-1 transition-all duration-300">
                                     <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
                                        <div>
                                            <div class="flex items-center gap-3 mb-2">
                                                <span class="text-xs font-bold px-3 py-1 rounded-full bg-slate-100 text-slate-500 uppercase tracking-wider">{{ v.date }}</span>
                                                <span v-if="v.points > 20" class="text-[10px] font-black bg-red-100 text-red-600 px-2 py-0.5 rounded">HIGH PRIORITY</span>
                                            </div>
                                            <h3 class="text-lg font-bold text-slate-800">{{ v.category }}</h3>
                                        </div>
                                     </div>
                                     
                                     <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                         <p class="text-sm text-slate-600 leading-relaxed">
                                            "{{ v.description || 'Tidak ada notasi keterangan.' }}"
                                         </p>
                                     </div>

                                     <!-- Photo Proof Button -->
                                     <div v-if="v.photo_url" class="mt-4 flex justify-end">
                                         <a :href="v.photo_url" target="_blank" class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-200 hover:bg-slate-50 hover:text-namira-teal transition-all group/btn">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover/btn:-rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Lihat Bukti Foto
                                         </a>
                                     </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ACHIEVEMENTS TAB -->
                    <div v-else-if="activeTab === 'achievements'" class="bg-white/40 backdrop-blur-sm rounded-[2.5rem] p-6 sm:p-8 lg:p-10 border border-white/60 shadow-sm min-h-[500px] animate-in fade-in slide-in-from-bottom-4 duration-500">
                        
                        <div v-if="achievements.length === 0" class="flex flex-col items-center justify-center py-20 text-center space-y-6">
                             <div class="w-24 h-24 bg-yellow-50 rounded-full flex items-center justify-center shadow-sm border border-yellow-100">
                                <TrophyIcon class="w-12 h-12 text-yellow-500" />
                             </div>
                             <div>
                                 <h3 class="text-xl font-bold text-slate-700">Belum Ada Prestasi</h3>
                                 <p class="text-sm text-slate-500 mt-2 max-w-xs mx-auto">Ayo ukir prestasimu dan buat sekolah bangga! Semangat!</p>
                             </div>
                        </div>

                        <div v-else class="space-y-6">
                            <div v-for="(a, index) in achievements" :key="a.id" class="group relative">
                                <!-- Card Content -->
                                <div class="bg-white rounded-3xl p-6 shadow-[0_2px_20px_rgba(0,0,0,0.02)] border border-slate-100 group-hover:shadow-[0_8px_30px_rgba(0,0,0,0.04)] group-hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                                     
                                     <!-- Gold Accent -->
                                     <div class="absolute top-0 left-0 w-2 h-full bg-yellow-400"></div>

                                     <div class="pl-4">
                                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
                                            <div>
                                                <div class="flex items-center gap-3 mb-2">
                                                    <span class="text-xs font-bold px-3 py-1 rounded-full bg-slate-100 text-slate-500 uppercase tracking-wider">{{ a.date }}</span>
                                                    <span class="text-[10px] font-black bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded uppercase">{{ a.level }}</span>
                                                </div>
                                                <h3 class="text-lg font-bold text-slate-800">{{ a.title }}</h3>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <div class="w-10 h-10 rounded-full bg-yellow-50 flex items-center justify-center">
                                                    <TrophyIcon class="w-6 h-6 text-yellow-500" />
                                                </div>
                                            </div>
                                        </div>
                                         
                                        <div v-if="a.description" class="bg-slate-50 rounded-2xl p-4 border border-slate-100 mb-4">
                                             <p class="text-sm text-slate-600 leading-relaxed">
                                                "{{ a.description }}"
                                             </p>
                                        </div>

                                        <!-- Photo Proof Button -->
                                         <div v-if="a.proof_url" class="flex justify-end">
                                             <a :href="a.proof_url" target="_blank" class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-200 hover:bg-slate-50 hover:text-yellow-600 transition-all group/btn">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover/btn:-rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Lihat Bukti Foto
                                             </a>
                                         </div>
                                     </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SESSIONS TAB -->
                    <div v-else-if="activeTab === 'sessions'" class="bg-white/40 backdrop-blur-sm rounded-[2.5rem] p-6 sm:p-8 lg:p-10 border border-white/60 shadow-sm min-h-[500px] animate-in fade-in slide-in-from-bottom-4 duration-500">
                         <div v-if="sessions.length === 0" class="flex flex-col items-center justify-center py-20 text-center space-y-6">
                             <div class="w-24 h-24 bg-purple-50 rounded-full flex items-center justify-center shadow-sm border border-purple-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                             </div>
                             <div>
                                 <h3 class="text-xl font-bold text-slate-700">Tidak Ada Jadwal Konseling</h3>
                                 <p class="text-sm text-slate-500 mt-2 max-w-xs mx-auto">Kamu belum memiliki jadwal bimbingan atau konseling dalam waktu dekat.</p>
                             </div>
                        </div>

                        <div v-else class="space-y-4">
                            <div v-for="s in sessions" :key="s.id" class="bg-white rounded-3xl p-6 shadow-[0_2px_15px_rgba(0,0,0,0.02)] border border-slate-100 flex flex-col sm:flex-row gap-6 items-center sm:items-start group hover:scale-[1.01] transition-transform duration-300">
                                <!-- Date Badge -->
                                <div class="flex-shrink-0 flex flex-col items-center justify-center bg-purple-50 rounded-2xl w-20 h-20 border border-purple-100">
                                    <span class="text-xs font-bold text-purple-600 uppercase">{{ s.date.split(' ')[1] }}</span>
                                    <span class="text-2xl font-black text-slate-800">{{ s.date.split(' ')[0] }}</span>
                                    <span class="text-[10px] font-bold text-slate-400">{{ s.time }}</span>
                                </div>

                                <div class="flex-1 w-full">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="text-base font-bold text-slate-800">Sesi Konseling Individual</h3>
                                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border shadow-sm" :class="s.status === 'Completed' ? 'bg-green-50 text-green-600 border-green-100' : (s.status === 'Scheduled' ? 'bg-blue-50 text-blue-600 border-blue-100' : 'bg-red-50 text-red-600 border-red-100')">
                                            {{ s.status === 'Scheduled' ? 'Terjadwal' : (s.status === 'Completed' ? 'Selesai' : 'Batal') }}
                                        </span>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                                        <div class="flex items-center gap-3 bg-slate-50 p-2.5 rounded-xl border border-slate-100/50">
                                            <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center shadow-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-[10px] font-bold text-slate-400 uppercase">Konselor</p>
                                                <p class="text-xs font-bold text-slate-700">{{ s.counselor_name }}</p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3 bg-slate-50 p-2.5 rounded-xl border border-slate-100/50">
                                            <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center shadow-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-[10px] font-bold text-slate-400 uppercase">Metode</p>
                                                <p class="text-xs font-bold text-slate-700">{{ s.method }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</template>
