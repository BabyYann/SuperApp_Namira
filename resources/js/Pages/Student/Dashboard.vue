<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { onMounted, ref, computed, watch } from 'vue';
import { 
    ClockIcon, 
    CalendarDaysIcon, 
    RocketLaunchIcon,
    SparklesIcon,
    BoltIcon,
    MapPinIcon,
    TrophyIcon,
    ShieldCheckIcon,
    ShieldExclamationIcon,
    ArrowRightIcon,
    ComputerDesktopIcon,
    UserIcon,
    CheckCircleIcon,
    XCircleIcon,
    TruckIcon,
    BanknotesIcon,
    BookOpenIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    student: Object,
    activeBill: Object,
    schedule: Array,
    todayDate: String,
    counseling: Object,
    tasks: Object,
    attendance: Object,
    latestAchievement: Object,
    checkinToday: Object,   // { time, status } atau null
    schoolLocations: Array, // List lokasi sekolah [{ name, lat, lng, radius }]
    lastPickupTime: String, // Waktu request penjemputan terakhir
});

// ─── GPS Pickup Logic ───
const gpsStatus = ref('idle'); // idle | checking | ready | too_far | sent | error | cooldown
const pickupSent = ref(false);
const userCoords = ref(null);
const distanceMeters = ref(null);

const haversineDistance = (lat1, lng1, lat2, lng2) => {
    const R = 6371000;
    const φ1 = lat1 * Math.PI / 180, φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(Δφ/2)**2 + Math.cos(φ1)*Math.cos(φ2)*Math.sin(Δλ/2)**2;
    return Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)));
};

const nearestLocation = ref(null);
const cooldownSecondsRemaining = ref(0);
let cooldownTimer = null;

const startCooldownTimer = () => {
    if (!props.lastPickupTime) return;
    
    const lastTime = new Date(props.lastPickupTime).getTime();
    const calculateRemaining = () => {
        const now = new Date().getTime();
        const diffMs = now - lastTime;
        const diffSec = Math.floor(diffMs / 1000);
        const cooldownTotal = 5 * 60; // 5 menit
        const remaining = cooldownTotal - diffSec;
        
        if (remaining > 0) {
            cooldownSecondsRemaining.value = remaining;
            gpsStatus.value = 'cooldown';
        } else {
            cooldownSecondsRemaining.value = 0;
            if (gpsStatus.value === 'cooldown') {
                gpsStatus.value = 'idle';
            }
            clearInterval(cooldownTimer);
        }
    };
    
    calculateRemaining();
    clearInterval(cooldownTimer);
    cooldownTimer = setInterval(calculateRemaining, 1000);
};

const formattedCooldownTime = computed(() => {
    const minutes = Math.floor(cooldownSecondsRemaining.value / 60);
    const seconds = cooldownSecondsRemaining.value % 60;
    return `${minutes}m ${seconds}s`;
});

watch(() => props.lastPickupTime, () => {
    startCooldownTimer();
});

const checkGPS = () => {
    if (!navigator.geolocation) {
        gpsStatus.value = 'error';
        return;
    }
    gpsStatus.value = 'checking';
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            userCoords.value = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            
            let isInsideAnyLocation = false;
            let closestLoc = null;
            let closestDist = Infinity;

            props.schoolLocations.forEach((loc) => {
                const dist = haversineDistance(
                    pos.coords.latitude, pos.coords.longitude,
                    loc.lat, loc.lng
                );
                
                if (dist < closestDist) {
                    closestDist = dist;
                    closestLoc = loc;
                }

                if (dist <= loc.radius) {
                    isInsideAnyLocation = true;
                }
            });

            distanceMeters.value = closestDist;
            nearestLocation.value = closestLoc;
            gpsStatus.value = isInsideAnyLocation ? 'ready' : 'too_far';
        },
        () => { gpsStatus.value = 'error'; },
        { enableHighAccuracy: true, timeout: 10000 }
    );
};

const sendPickupRequest = async () => {
    if (gpsStatus.value !== 'ready' || pickupSent.value) return;
    try {
        await router.post(route('student.pickup.request'), {
            latitude: userCoords.value?.lat,
            longitude: userCoords.value?.lng,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                pickupSent.value = true;
                gpsStatus.value = 'sent';
            },
        });
    } catch (e) {
        gpsStatus.value = 'error';
    }
};

const currentTime = ref(new Date());
const currentScheduleIndex = computed(() => {
    const hour = currentTime.value.getHours();
    if (hour < 8) return 0;
    if (hour < 10) return 1;
    if (hour < 12) return 2;
    return -1;
});

const greeting = computed(() => {
    const hour = currentTime.value.getHours();
    if (hour < 11) return 'Selamat Pagi';
    if (hour < 15) return 'Selamat Siang';
    if (hour < 18) return 'Selamat Sore';
    return 'Selamat Malam';
});

onMounted(() => {
    setInterval(() => {
        currentTime.value = new Date();
    }, 60000);
    startCooldownTimer();
});
</script>

<template>
    <StudentLayout title="Portal Siswa">
        <div class="max-w-5xl mx-auto space-y-4 pb-20">
            
            <!-- 1. GREETING & HEADER BAR -->
            <div class="flex items-center justify-between bg-white rounded-3xl p-5 border border-slate-100 shadow-sm">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-teal-50 text-namira-teal border border-teal-100 uppercase tracking-wider">
                            {{ student?.unit?.name || 'SD Namira' }}
                        </span>
                        <span class="text-xs text-slate-400 font-medium">NIS: {{ student?.nis || '5251001' }}</span>
                    </div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-800 tracking-tight">
                        {{ greeting }}, {{ $page.props.auth.user.name.split(' ')[0] }}! 👋
                    </h1>
                </div>
                <div class="text-right hidden sm:block">
                    <p class="text-xs text-slate-400 font-medium">{{ todayDate }}</p>
                    <p class="text-xs font-bold text-teal-700 mt-0.5">Tahun Ajaran 2025/2026</p>
                </div>
            </div>

            <!-- 2. HERO LMS BANNER (COMPACT & SLEEK) -->
            <div class="relative rounded-3xl overflow-hidden bg-gradient-to-r from-teal-900 via-teal-800 to-slate-900 text-white p-6 shadow-md border border-teal-800/50">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 bg-white/10 text-teal-200 text-[10px] font-bold uppercase rounded-md tracking-wider border border-white/10 backdrop-blur-sm">
                                LMS E-Learning
                            </span>
                            <span v-if="tasks?.total > 0" class="text-xs text-teal-300 font-medium">
                                {{ tasks.completed }}/{{ tasks.total }} Tugas Selesai
                            </span>
                        </div>
                        <h2 class="text-lg md:text-xl font-extrabold text-white tracking-tight">
                            Ruang Belajar Digital Namira
                        </h2>
                        <p class="text-xs text-teal-100/80 max-w-lg">
                            Akses materi pelajaran interaktif, ikuti kelas virtual, dan kumpulkan tugas digital.
                        </p>
                    </div>
                    
                    <Link 
                        :href="route('lms.student.classrooms.index')" 
                        class="px-5 py-2.5 bg-white text-teal-900 hover:bg-teal-50 rounded-2xl font-bold text-xs shadow-sm transition-all active:scale-95 shrink-0 text-center flex items-center justify-center gap-2"
                    >
                        <ComputerDesktopIcon class="w-4 h-4 text-namira-teal" />
                        <span>Buka E-Learning &rarr;</span>
                    </Link>
                </div>
            </div>

            <!-- 3. QUICK STATUS GRID (2x2 COMPACT GRID) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                
                <!-- Card A: Presensi Gerbang -->
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Absensi Gerbang</span>
                        <div class="p-1.5 rounded-lg" :class="checkinToday?.status === 'hadir' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">
                            <CheckCircleIcon v-if="checkinToday?.status === 'hadir'" class="w-4 h-4" />
                            <ClockIcon v-else class="w-4 h-4" />
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-extrabold text-slate-800 leading-tight">
                            {{ checkinToday ? (checkinToday.status === 'hadir' ? 'Hadir' : 'Terlambat') : 'Belum Scan' }}
                        </p>
                        <p class="text-[11px] text-slate-400 font-medium">
                            {{ checkinToday ? checkinToday.time + ' WIB' : 'Pintu Gerbang' }}
                        </p>
                    </div>
                </div>

                <!-- Card B: Penjemputan GPS -->
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Penjemputan</span>
                        <div class="p-1.5 rounded-lg bg-blue-50 text-blue-600">
                            <TruckIcon class="w-4 h-4" />
                        </div>
                    </div>
                    <div>
                        <div v-if="gpsStatus === 'sent'" class="text-xs font-bold text-emerald-600">
                            Terkirim ke Sekolah
                        </div>
                        <div v-else-if="gpsStatus === 'ready'">
                            <button @click="sendPickupRequest" class="w-full py-1 bg-emerald-600 text-white rounded-lg text-[10px] font-bold">
                                Minta Penjemputan
                            </button>
                        </div>
                        <div v-else-if="gpsStatus === 'cooldown'" class="text-[11px] font-bold text-amber-600">
                            Sisa: {{ formattedCooldownTime }}
                        </div>
                        <div v-else>
                            <button @click="checkGPS" class="w-full py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[10px] font-bold transition-all">
                                Cek Lokasi GPS
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Card C: Status Disiplin -->
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Poin Disiplin</span>
                        <div class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600">
                            <ShieldCheckIcon class="w-4 h-4" />
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-extrabold text-emerald-700 leading-tight">
                            {{ counseling?.total_points || 0 }} Poin
                        </p>
                        <p class="text-[11px] text-slate-400 font-medium">Siswa Teladan</p>
                    </div>
                </div>

                <!-- Card D: Keuangan / Tagihan -->
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tagihan SPP</span>
                        <div class="p-1.5 rounded-lg" :class="activeBill ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600'">
                            <BanknotesIcon class="w-4 h-4" />
                        </div>
                    </div>
                    <div>
                        <p v-if="activeBill" class="text-xs font-extrabold text-amber-600 leading-tight line-clamp-1">
                            {{ activeBill.amount }}
                        </p>
                        <p v-else class="text-xs font-extrabold text-emerald-600 leading-tight">
                            Lunas & Terverifikasi
                        </p>
                        <Link :href="route('student.finance')" class="text-[11px] text-teal-700 font-bold hover:underline">
                            Lihat Rincian &rarr;
                        </Link>
                    </div>
                </div>

            </div>

            <!-- 4. TIMELINE & JADWAL KBM HARI INI -->
            <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="p-2 bg-teal-50 text-namira-teal rounded-xl">
                            <ClockIcon class="w-5 h-5" />
                        </div>
                        <h3 class="font-extrabold text-slate-800 text-base">Jadwal KBM Hari Ini</h3>
                    </div>
                    <span class="text-xs font-bold text-slate-400">{{ schedule.length }} Sesi Pembelajaran</span>
                </div>

                <div v-if="schedule.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div 
                        v-for="(item, index) in schedule" 
                        :key="index"
                        class="p-4 rounded-2xl border transition-all space-y-2"
                        :class="index === currentScheduleIndex ? 'bg-slate-900 text-white border-slate-800 shadow-md' : 'bg-slate-50 border-slate-100 text-slate-700'"
                    >
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-bold px-2 py-0.5 rounded-md" :class="index === currentScheduleIndex ? 'bg-teal-500/20 text-teal-200' : 'bg-white text-slate-500 border border-slate-200'">
                                {{ item.time }}
                            </span>
                            <span v-if="index === currentScheduleIndex" class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider">Sedang Berlangsung</span>
                        </div>
                        
                        <h4 class="font-extrabold text-sm leading-tight" :class="index === currentScheduleIndex ? 'text-white' : 'text-slate-800'">
                            {{ item.subject }}
                        </h4>
                        
                        <div class="flex items-center justify-between text-[11px] opacity-80 pt-1 border-t border-current/10">
                            <span class="flex items-center gap-1"><MapPinIcon class="w-3.5 h-3.5" /> {{ item.room }}</span>
                            <span class="flex items-center gap-1"><UserIcon class="w-3.5 h-3.5" /> {{ item.teacher }}</span>
                        </div>
                    </div>
                </div>

                <div v-else class="py-8 text-center border-2 border-dashed border-slate-100 rounded-2xl">
                    <p class="text-xs text-slate-400 font-bold">Tidak ada jadwal kegiatan belajar mengajar hari ini.</p>
                </div>
            </div>

            <!-- 5. TUGAS AKTIF & PRESTASI -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- Box Tugas LMS -->
                <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="font-extrabold text-slate-800 text-sm flex items-center gap-2">
                            <BookOpenIcon class="w-4 h-4 text-namira-teal" />
                            <span>Tugas Belajar Aktif</span>
                        </h4>
                        <span class="text-xs font-bold text-slate-400">{{ tasks.completed }}/{{ tasks.total }} Selesai</span>
                    </div>

                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div class="bg-namira-teal h-full rounded-full transition-all duration-700" :style="{ width: (tasks.total > 0 ? (tasks.completed/tasks.total*100) : 0) + '%' }"></div>
                    </div>

                    <p class="text-xs text-slate-500 font-medium">
                        {{ tasks.completed === tasks.total && tasks.total > 0 ? 'Semua tugas telah diselesaikan dengan baik!' : 'Periksa modul LMS untuk pengerjaan tugas tepat waktu.' }}
                    </p>
                </div>

                <!-- Box Prestasi / Counseling -->
                <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="font-extrabold text-slate-800 text-sm flex items-center gap-2">
                            <TrophyIcon class="w-4 h-4 text-amber-500" />
                            <span>Capaian Prestasi</span>
                        </h4>
                        <span v-if="latestAchievement" class="text-[10px] font-bold bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full border border-amber-200">
                            Tingkat {{ latestAchievement.level }}
                        </span>
                    </div>

                    <div v-if="latestAchievement">
                        <p class="text-xs font-bold text-slate-800">{{ latestAchievement.title }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Pertahankan prestasi membanggakan sekolah!</p>
                    </div>
                    <div v-else>
                        <p class="text-xs text-slate-600 font-medium">Belum ada catatan pelanggaran disiplin.</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Pertahankan sikap santun dan tertib di lingkungan sekolah.</p>
                    </div>
                </div>

            </div>

        </div>
    </StudentLayout>
</template>
