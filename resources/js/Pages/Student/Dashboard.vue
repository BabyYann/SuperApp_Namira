<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { onMounted, ref, computed, watch } from 'vue';
import { 
    ClockIcon, 
    CalendarDaysIcon, 
    RocketLaunchIcon,
    ArrowTrendingUpIcon,
    SparklesIcon,
    BoltIcon,
    MapPinIcon,
    TrophyIcon,
    ShieldExclamationIcon,
    ArrowRightIcon,
    ComputerDesktopIcon,
    UserIcon,
    CheckCircleIcon,
    XCircleIcon,
    TruckIcon,
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
const gpsStatus = ref('idle'); // idle | checking | ready | too_far | sent | error
const pickupSent = ref(false);
const userCoords = ref(null);
const distanceMeters = ref(null);

// Hitung jarak Haversine (meter)
const haversineDistance = (lat1, lng1, lat2, lng2) => {
    const R = 6371000;
    const φ1 = lat1 * Math.PI / 180, φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(Δφ/2)**2 + Math.cos(φ1)*Math.cos(φ2)*Math.sin(Δλ/2)**2;
    return Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)));
};

const nearestLocation = ref(null);

// ─── Cooldown / Spam Protection Logic ───
const cooldownSecondsRemaining = ref(0);
let cooldownTimer = null;

const startCooldownTimer = () => {
    if (!props.lastPickupTime) return;
    
    const lastTime = new Date(props.lastPickupTime).getTime();
    const calculateRemaining = () => {
        const now = new Date().getTime();
        const diffMs = now - lastTime;
        const diffSec = Math.floor(diffMs / 1000);
        const cooldownTotal = 5 * 60; // 5 menit = 300 detik
        const remaining = cooldownTotal - diffSec;
        
        if (remaining > 0) {
            cooldownSecondsRemaining.value = remaining;
            gpsStatus.value = 'cooldown'; // Ubah status ke cooldown
        } else {
            cooldownSecondsRemaining.value = 0;
            if (gpsStatus.value === 'cooldown') {
                gpsStatus.value = 'idle'; // Kembalikan ke idle
            }
            clearInterval(cooldownTimer);
        }
    };
    
    calculateRemaining();
    clearInterval(cooldownTimer);
    cooldownTimer = setInterval(calculateRemaining, 1000);
};

// Format sisa waktu: "4m 12s"
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

                // Cek jika berada di dalam radius lokasi ini
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


// --- Logic for "Live" Timeline ---
const currentTime = ref(new Date());
const currentScheduleIndex = computed(() => {
    // Mocking logic to find active schedule based on time strings "07:30 - 08:30"
    // Ideally this parsing happens in backend or robustly here.
    // For visual demo, let's assume index 0 if morning, etc.
    const hour = currentTime.value.getHours();
    if (hour < 8) return 0;
    if (hour < 10) return 1;
    if (hour < 12) return 2;
    return -1; // Done
});

// Calculate greeting
const greeting = computed(() => {
    const hour = currentTime.value.getHours();
    if (hour < 11) return 'Selamat Pagi';
    if (hour < 15) return 'Selamat Siang';
    if (hour < 18) return 'Selamat Sore';
    return 'Selamat Malam';
});


// Initials Helper
const getInitials = (name) => {
    if (!name) return 'S';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
};

onMounted(() => {
    // Update time every minute
    setInterval(() => {
        currentTime.value = new Date();
    }, 60000);

    // Jalankan timer cooldown
    startCooldownTimer();
});
</script>

<template>
    <StudentLayout title="Beranda">
        <div class="max-w-7xl mx-auto pb-24 relative">
            
            <!-- Global Background Ambience -->
            <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10">
                <div class="absolute top-[-20%] right-[-10%] w-[600px] h-[600px] bg-namira-teal/10 rounded-full blur-[120px] animate-pulse-slow"></div>
                <div class="absolute bottom-[-20%] left-[-10%] w-[500px] h-[500px] bg-purple-500/10 rounded-full blur-[100px] animate-pulse-slower"></div>
            </div>

            <!-- 1. Header & Personal Assistant Widget -->
            <header class="mb-10 animate-in fade-in slide-in-from-top-4 duration-700">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            {{ todayDate }}
                        </p>
                        <h1 class="text-4xl font-black text-slate-800 tracking-tight leading-tight">
                            {{ greeting }}, <span class="bg-gradient-to-r from-namira-teal to-teal-600 bg-clip-text text-transparent">{{ $page.props.auth.user.name.split(' ')[0] }}</span>!
                        </h1>
                        <p class="text-slate-500 font-medium mt-1">Siap mencetak prestasi hari ini?</p>
                    </div>

                    <!-- "What's Next" / Smart Notification -->
                    <div class="relative group cursor-pointer">
                        <div class="absolute inset-0 bg-gradient-to-r from-pink-500 to-rose-500 rounded-2xl blur opacity-20 group-hover:opacity-40 transition-opacity duration-500"></div>
                        <div class="relative bg-white/60 backdrop-blur-md border border-white/50 p-4 rounded-2xl shadow-sm flex items-center gap-4 min-w-[300px] hover:scale-[1.02] transition-transform">
                            <div class="p-3 bg-pink-50 text-pink-600 rounded-xl">
                                <BoltIcon v-if="activeBill" class="w-6 h-6 animate-bounce-slow" />
                                <SparklesIcon v-else class="w-6 h-6" />
                            </div>
                            <div>
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">PENGINGAT PINTAR</h4>
                                <p v-if="activeBill" class="text-sm font-bold text-slate-800 leading-tight">Tagihan belum lunas: <span class="text-pink-600">{{ activeBill.amount }}</span></p>
                                <p v-else class="text-sm font-bold text-slate-800 leading-tight">Tidak ada tanggungan. Fokus belajar!</p>
                            </div>
                            <ArrowRightIcon v-if="activeBill" class="w-4 h-4 text-slate-400 ml-auto" />
                        </div>
                    </div>
                </div>
            </header>

            <!-- 1.5. LMS E-Learning Banner -->
            <section class="mb-10 relative overflow-hidden group">
                <!-- Background Gradient Blur -->
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-800 to-teal-950 rounded-[2rem] shadow-xl"></div>
                <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-emerald-500 rounded-full blur-[60px] opacity-20 group-hover:scale-125 transition-transform duration-500"></div>

                <div class="relative z-10 p-8 sm:p-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-2">
                        <span class="inline-flex px-3 py-1 bg-white/10 text-emerald-200 text-[10px] font-bold uppercase rounded-lg tracking-wider border border-white/5 backdrop-blur-md">
                            Portal Pembelajaran Digital
                        </span>
                        <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight leading-tight">
                            Namira E-Learning (LMS)
                        </h2>
                        <p class="text-sm text-emerald-100/70 max-w-xl leading-relaxed">
                            Akses materi belajar interaktif, ikuti kelas virtual terbaru, kumpulkan tugas secara digital, dan pantau perkembangan nilai akademik Anda.
                        </p>
                    </div>
                    <Link :href="route('lms.student.classrooms.index')" class="px-6 py-3 bg-white text-emerald-950 hover:bg-emerald-50 rounded-2xl font-extrabold text-xs tracking-wider uppercase shadow-md transition-all active:scale-95 shrink-0 text-center flex items-center justify-center gap-2">
                        <ComputerDesktopIcon class="w-4 h-4 text-emerald-800" />
                        <span>Masuk Kelas Belajar &rarr;</span>
                    </Link>
                </div>
            </section>

            <!-- 2. Main Dashboard Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- LEFT COLUMN: Timeline & Schedule (Mobile: Order 2, Desktop: Order 1) -->
                <div class="lg:col-span-8 space-y-8 order-2 lg:order-1">
                    
                    <!-- Timeline Schedule Section -->
                    <section class="bg-white/60 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white/60 shadow-xl shadow-slate-200/50 relative overflow-hidden">
                         <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-teal-50 text-namira-teal rounded-xl">
                                    <ClockIcon class="w-6 h-6" />
                                </div>
                                <h3 class="font-black text-slate-800 text-lg">Timeline Belajar</h3>
                            </div>
                            <div class="px-3 py-1 bg-teal-50 text-namira-teal rounded-full text-[10px] font-black uppercase tracking-widest border border-teal-100">
                                {{ schedule.length }} Sesi
                            </div>
                         </div>

                        <!-- Vertical Grid Container (No Scroll) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                             <div 
                                v-for="(item, index) in schedule" 
                                :key="index"
                                class="p-5 rounded-3xl border transition-all duration-300 relative group"
                                :class="[
                                    index === currentScheduleIndex 
                                        ? 'bg-slate-900 text-white shadow-2xl scale-[1.02] border-slate-700 ring-4 ring-slate-900/10 z-10' 
                                        : 'bg-white border-slate-100 hover:border-teal-200 hover:shadow-lg text-slate-600'
                                ]"
                            >
                                <div class="flex justify-between items-start mb-4">
                                    <span class="text-xs font-black px-2 py-1 rounded-lg" 
                                        :class="index === currentScheduleIndex ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500'">
                                        {{ item.time }}
                                    </span>
                                    <div v-if="index === currentScheduleIndex" class="w-2 h-2 rounded-full bg-green-400 animate-ping"></div>
                                </div>
                                
                                <h4 class="text-lg font-black leading-tight mb-2" :class="index === currentScheduleIndex ? 'text-white' : 'text-slate-800'">
                                    {{ item.subject }}
                                </h4>
                                
                                <div class="flex items-center gap-4 text-xs font-medium opacity-80">
                                    <span class="flex items-center gap-1.5"><MapPinIcon class="w-3 h-3" /> {{ item.room }}</span>
                                    <span class="flex items-center gap-1.5"><UserIcon class="w-3.5 h-3.5" /> {{ item.teacher }}</span>
                                </div>

                                <!-- Progress Bar if Active -->
                                <div v-if="index === currentScheduleIndex" class="absolute bottom-0 left-0 right-0 h-1.5 bg-slate-800 rounded-b-3xl overflow-hidden">
                                    <div class="h-full bg-namira-teal animate-progress w-[40%]"></div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-if="schedule.length === 0" class="col-span-full py-10 text-center border-2 border-dashed border-slate-200 rounded-3xl">
                                <p class="text-slate-400 font-bold">Tidak ada jadwal KBM hari ini.</p>
                            </div>
                        </div>
                    </section>

                    <!-- Stats & Productivity Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Productivity Card -->
                        <div class="p-6 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-[2rem] text-white shadow-lg shadow-indigo-200 relative overflow-hidden group">
                            <div class="absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform duration-500">
                                <RocketLaunchIcon class="w-32 h-32" />
                            </div>
                            <h4 class="text-[10px] font-black uppercase tracking-widest opacity-70 mb-2">Zona Produktif</h4>
                            <div class="flex items-baseline gap-2 mb-4">
                                <span class="text-4xl font-black">{{ tasks.completed }}</span>
                                <span class="text-lg font-bold opacity-50">/ {{ tasks.total }} Selesai</span>
                            </div>
                            <div class="w-full bg-white/20 h-2 rounded-full overflow-hidden">
                                <div class="bg-white h-full rounded-full transition-all duration-1000" :style="{ width: (tasks.total > 0 ? (tasks.completed/tasks.total*100) : 0) + '%' }"></div>
                            </div>
                            <p class="text-[10px] font-bold mt-4 opacity-80">
                                {{ tasks.completed === tasks.total && tasks.total > 0 ? 'Sempurna!' : 'Ayo selesaikan tugasmu!' }}
                            </p>
                        </div>
                        
                        <!-- Attendance Mini Card -->
                        <div class="p-6 bg-white rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                            <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Kehadiran Bulan Ini</h4>
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-full border-4 border-namira-teal flex items-center justify-center text-xl font-black text-slate-800">
                                    {{ attendance.present }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">Hadir Tepat Waktu</p>
                                    <p class="text-xs text-slate-500">Pertahankan rajinmu!</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- RIGHT COLUMN: Profile & Achievements (Mobile: Order 1, Desktop: Order 2) -->
                <div class="lg:col-span-4 space-y-6 order-1 lg:order-2">
                    
                    <!-- Profile Card -->
                    <div class="bg-white rounded-[2.5rem] p-2 shadow-xl shadow-slate-200/50 border border-slate-100">
                        <div class="relative bg-slate-900 rounded-[2rem] p-8 text-white overflow-hidden text-center group">
                            <!-- Animated BG -->
                            <div class="absolute inset-0 opacity-20 group-hover:opacity-30 transition-opacity">
                                <div class="absolute top-[-50px] right-[-50px] w-40 h-40 bg-pink-500 rounded-full blur-[60px]"></div>
                                <div class="absolute bottom-[-50px] left-[-50px] w-40 h-40 bg-teal-500 rounded-full blur-[60px]"></div>
                            </div>

                            <div class="relative z-10 flex flex-col items-center">
                                <div class="w-24 h-24 rounded-full border-4 border-white/10 shadow-lg overflow-hidden mb-4 relative">
                                    <img v-if="$page.props.auth.user.profile_photo_url" :src="$page.props.auth.user.profile_photo_url" class="w-full h-full object-cover">
                                    <div v-else class="w-full h-full bg-slate-800 flex items-center justify-center text-2xl font-black">{{ getInitials($page.props.auth.user.name) }}</div>
                                </div>
                                <h2 class="text-lg font-black tracking-tight">{{ student?.full_name || $page.props.auth.user.name }}</h2>
                                <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-6">{{ student?.nis || 'ID: ???' }}</p>
                                
                                <Link :href="route('profile.edit')" class="px-6 py-2 bg-white text-slate-900 rounded-full text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition-colors">
                                    Edit Profil
                                </Link>
                        </div>
                    </div>
                </div>

                <!-- ─── CHECK-IN GERBANG CARD ─── -->
                    <div class="rounded-[2rem] overflow-hidden border shadow-sm"
                        :class="checkinToday ? (checkinToday.status === 'hadir' ? 'bg-green-50 border-green-200' : 'bg-amber-50 border-amber-200') : 'bg-slate-50 border-slate-200'"
                    >
                        <div class="p-5 flex items-center gap-4">
                            <div class="p-3 rounded-2xl"
                                :class="checkinToday ? (checkinToday.status === 'hadir' ? 'bg-green-100 text-green-600' : 'bg-amber-100 text-amber-600') : 'bg-slate-200 text-slate-400'"
                            >
                                <CheckCircleIcon v-if="checkinToday?.status === 'hadir'" class="w-7 h-7" />
                                <XCircleIcon v-else-if="checkinToday?.status === 'terlambat'" class="w-7 h-7" />
                                <ClockIcon class="w-7 h-7" />
                            </div>
                            <div class="flex-1">
                                <p class="text-[10px] font-black uppercase tracking-widest"
                                    :class="checkinToday ? (checkinToday.status === 'hadir' ? 'text-green-500' : 'text-amber-500') : 'text-slate-400'"
                                >Absensi Gerbang Hari Ini</p>
                                <p v-if="checkinToday" class="text-lg font-black"
                                    :class="checkinToday.status === 'hadir' ? 'text-green-800' : 'text-amber-800'"
                                >
                                    {{ checkinToday.status === 'hadir' ? 'Hadir' : 'Terlambat' }}
                                    <span class="text-sm font-bold opacity-70 ml-1">· {{ checkinToday.time }} WIB</span>
                                </p>
                                <p v-else class="text-sm font-bold text-slate-500">Belum tercatat hari ini</p>
                            </div>
                        </div>
                    </div>

                    <!-- ─── TOMBOL JEMPUT GPS ─── -->
                    <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden">
                        <div class="p-5">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
                                    <TruckIcon class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Penjemputan</p>
                                    <h3 class="font-black text-slate-800 text-sm leading-tight">Tombol Jemput</h3>
                                </div>
                            </div>

                            <!-- Sent State -->
                            <div v-if="gpsStatus === 'sent'" class="text-center py-4">
                                <CheckCircleIcon class="w-12 h-12 mx-auto text-green-500 mb-2" />
                                <p class="font-black text-green-700 text-sm">Permintaan Terkirim!</p>
                                <p class="text-xs text-slate-500 mt-1">Sekolah sudah menerima notifikasi penjemputan.</p>
                            </div>

                            <!-- Cooldown State -->
                            <div v-else-if="gpsStatus === 'cooldown'" class="text-center py-2">
                                <div class="px-3 py-3 bg-amber-50 border border-amber-200 rounded-xl mb-3 text-amber-800">
                                    <p class="font-bold text-sm flex items-center justify-center gap-1.5">
                                        <ClockIcon class="w-4 h-4 text-amber-600" />
                                        Cooldown Aktif
                                    </p>
                                    <p class="text-xs mt-1">Anda sudah mengirim permintaan penjemputan.</p>
                                    <p class="text-xs font-black mt-2 bg-amber-100/50 inline-block px-3.5 py-1 rounded-lg">
                                        Kirim lagi dalam: {{ formattedCooldownTime }}
                                    </p>
                                </div>
                                <p class="text-[10px] text-slate-400">Untuk mencegah penjemputan ganda/spam di sekolah.</p>
                            </div>

                            <!-- Idle State -->
                            <div v-else-if="gpsStatus === 'idle'" class="text-center">
                                <p class="text-xs text-slate-500 leading-relaxed mb-4">
                                    Tekan tombol di bawah untuk memverifikasi lokasi Anda, kemudian kirim notifikasi penjemputan ke sekolah.
                                </p>
                                <button @click="checkGPS"
                                    class="w-full py-3 bg-blue-500 hover:bg-blue-600 active:scale-95 text-white font-extrabold rounded-2xl text-xs uppercase tracking-wider transition-all shadow-md shadow-blue-200 flex items-center justify-center gap-1.5"
                                >
                                    <MapPinIcon class="w-4 h-4" />
                                    Cek Lokasi Saya
                                </button>
                            </div>

                            <!-- Checking GPS -->
                            <div v-else-if="gpsStatus === 'checking'" class="text-center py-4">
                                <div class="flex justify-center mb-2">
                                    <svg class="animate-spin w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                </div>
                                <p class="text-xs text-slate-500 font-medium">Mendeteksi lokasi GPS...</p>
                            </div>

                             <!-- Ready (dalam radius) -->
                             <div v-else-if="gpsStatus === 'ready'">
                                 <div class="flex items-center gap-2 mb-3 px-3 py-2 bg-green-50 border border-green-200 rounded-xl">
                                     <span class="text-green-500 font-bold text-xs flex items-center gap-1.5">
                                         <CheckCircleIcon class="w-4 h-4 text-green-500" />
                                         Anda berada {{ distanceMeters }}m dari {{ nearestLocation?.name || 'sekolah' }}
                                     </span>
                                 </div>
                                 <button @click="sendPickupRequest"
                                     class="w-full py-3.5 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 active:scale-95 text-white font-extrabold rounded-2xl text-sm uppercase tracking-wider transition-all shadow-lg shadow-green-200 flex items-center justify-center gap-2"
                                 >
                                     <TruckIcon class="w-5 h-5" />
                                     Jemput Anak Saya!
                                 </button>
                             </div>

                             <!-- Too Far -->
                             <div v-else-if="gpsStatus === 'too_far'" class="text-center">
                                 <div class="px-3 py-2 bg-red-50 border border-red-200 rounded-xl mb-3">
                                     <p class="text-red-600 font-bold text-xs flex items-center justify-center gap-1.5">
                                         <MapPinIcon class="w-4 h-4 text-red-500" />
                                         Anda masih {{ distanceMeters }}m dari {{ nearestLocation?.name || 'sekolah' }}
                                     </p>
                                     <p class="text-red-400 text-[10px] mt-0.5">
                                         Tombol aktif dalam radius {{ nearestLocation?.radius || 500 }}m
                                     </p>
                                 </div>
                                 <button @click="checkGPS" class="text-xs text-blue-500 font-bold underline flex items-center justify-center gap-1 mx-auto">
                                     <ArrowPathIcon class="w-3.5 h-3.5" />
                                     Perbarui Lokasi
                                 </button>
                             </div>

                             <!-- GPS Error -->
                             <div v-else-if="gpsStatus === 'error'" class="text-center py-2">
                                 <p class="text-xs text-red-500 font-bold flex items-center justify-center gap-1.5">
                                     <XCircleIcon class="w-4 h-4 text-red-500" />
                                     Tidak dapat mengakses GPS
                                 </p>
                                 <p class="text-[10px] text-slate-400 mt-1 mb-3">Pastikan izin lokasi sudah diaktifkan di browser Anda.</p>
                                 <button @click="checkGPS" class="text-xs text-blue-500 font-bold underline flex items-center justify-center gap-1 mx-auto">
                                     <ArrowPathIcon class="w-3.5 h-3.5" />
                                     Coba Lagi
                                 </button>
                            </div>
                        </div>
                    </div>

                    <!-- Achievements / Counseling -->
                    <div class="relative group">

                        <div class="absolute inset-x-4 top-2 bottom-0 bg-gradient-to-b from-yellow-400 to-amber-600 rounded-[2.5rem] blur opacity-40 translate-y-2 group-hover:translate-y-4 transition-all duration-500"></div>
                        <div class="relative bg-white rounded-[2.5rem] p-6 border border-slate-100 shadow-lg overflow-hidden">
                            
                            <!-- If Achievement Exists -->
                            <div v-if="latestAchievement" class="text-center">
                                <div class="w-20 h-20 mx-auto bg-gradient-to-br from-yellow-100 to-amber-200 rounded-full flex items-center justify-center mb-4 shadow-inner">
                                    <TrophyIcon class="w-10 h-10 text-amber-600" />
                                </div>
                                <h3 class="font-black text-slate-800 leading-tight mb-1">{{ latestAchievement.title }}</h3>
                                <div class="inline-block px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-[10px] font-black uppercase tracking-widest mb-4">
                                    Tingkat {{ latestAchievement.level }}
                                </div>
                                <p class="text-xs text-slate-400 font-medium leading-relaxed">Selamat! Pertahankan prestasimu untuk membanggakan sekolah.</p>
                            </div>

                            <!-- If No Achievement, Show Discipline Status (Neutral) -->
                            <div v-else class="text-center">
                                <div class="inline-flex items-center justify-center p-3 rounded-2xl bg-slate-50 mb-4">
                                    <ShieldExclamationIcon class="w-8 h-8 text-slate-400" />
                                </div>
                                <h3 class="font-bold text-slate-800 text-sm mb-1">Status Disiplin</h3>
                                <p class="text-2xl font-black" :class="counseling.status.color">{{ counseling.total_points }} Poin</p>
                                <p class="text-xs text-slate-400 mt-2">Jaga poin tetap 0 untuk menjadi siswa teladan!</p>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

        </div>
    </StudentLayout>
</template>

<style scoped>
/* Custom Animations */
@keyframes progress {
    from { width: 0%; }
    to { width: 40%; } /* Just a simulated progress */
}
.animate-progress {
    animation: progress 2s ease-out forwards;
}

@keyframes pulse-slow {
    0%, 100% { opacity: 0.5; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.1); }
}
.animate-pulse-slow {
    animation: pulse-slow 6s infinite ease-in-out;
}
.animate-pulse-slower {
    animation: pulse-slow 10s infinite ease-in-out reverse;
}
.animate-bounce-slow {
    animation: bounce 3s infinite;
}

/* Scrollbar Hide */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
