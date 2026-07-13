<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    ArrowLeftIcon, EnvelopeIcon, BriefcaseIcon, PhoneIcon, 
    IdentificationIcon, ShieldCheckIcon, ClockIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    staff: Object,
    attendanceHistory: Object,
});

const activeTab = ref('personal');

const tabs = [
    { id: 'personal', label: 'Data Pribadi' },
    { id: 'attendance', label: 'Riwayat Absensi' },
];
</script>

<template>
    <Head :title="`Detail Staf - ${staff.full_name}`" />

    <AuthenticatedLayout>
        <div class="space-y-6 pb-12">
            <!-- Header / Breadcrumb -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">Detail Staf</h2>
                    <div class="flex items-center gap-2 text-sm text-gray-500 mt-1">
                        <Link :href="route('yayasan.dashboard')" class="hover:text-namira-teal transition-colors">Dashboard</Link>
                        <span>/</span>
                        <Link :href="route('yayasan.staff.index')" class="hover:text-namira-teal transition-colors">Staf</Link>
                        <span>/</span>
                        <span class="text-gray-900 font-medium">{{ staff.full_name }}</span>
                    </div>
                </div>
                <Link :href="route('yayasan.staff.index')" class="px-5 py-2.5 bg-white/80 backdrop-blur-sm border border-white/50 text-gray-600 rounded-2xl hover:bg-white hover:shadow-md font-bold text-sm transition-all active:scale-95 flex items-center gap-2">
                    <ArrowLeftIcon class="w-4 h-4" />
                    Kembali
                </Link>
            </div>

            <!-- Profile Overview Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl p-8 shadow-sm border border-white/50 flex flex-col md:flex-row gap-8 items-center md:items-start relative overflow-hidden">
                 <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-bl from-purple-500/10 to-indigo-50 rounded-bl-full -mr-16 -mt-16 pointer-events-none"></div>
                 
                 <!-- Avatar -->
                 <div class="relative z-10 group">
                    <div class="w-36 h-36 rounded-full border-4 border-white shadow-xl overflow-hidden bg-gray-100 flex-shrink-0 relative">
                        <img v-if="staff.photo" :src="`/storage/${staff.photo}`" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 text-4xl font-bold text-gray-400">
                            {{ staff.full_name.substring(0,2).toUpperCase() }}
                        </div>
                    </div>
                 </div>

                 <!-- Basic Info -->
                 <div class="relative z-10 flex-1 text-center md:text-left pt-2">
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-1">{{ staff.full_name }}</h1>
                    <p class="text-gray-500 font-medium flex items-center justify-center md:justify-start gap-2">
                        <EnvelopeIcon class="w-4 h-4 text-namira-teal" />
                        {{ staff.user?.email || 'Email belum diatur' }}
                    </p>
                    
                    <div class="flex flex-wrap justify-center md:justify-start gap-3 mt-6">
                         <div class="px-4 py-1.5 rounded-xl text-xs font-bold uppercase tracking-wide border shadow-sm" 
                             :class="staff.gender === 'L' ? 'bg-blue-50 text-blue-700 border-blue-100' : 'bg-pink-50 text-pink-700 border-pink-100'">
                            {{ staff.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}
                        </div>
                        <div class="px-4 py-1.5 bg-purple-50 text-purple-700 rounded-xl text-xs font-bold uppercase tracking-wide border border-purple-100 flex items-center gap-1">
                            <BriefcaseIcon class="w-3 h-3" />
                            {{ staff.position || 'Staf Umum' }}
                        </div>
                        <div class="px-4 py-1.5 bg-white text-gray-600 rounded-xl text-xs font-mono font-bold border border-gray-200 shadow-sm">
                            NIP: {{ staff.nip || '-' }}
                        </div>
                    </div>
                 </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200/50">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button 
                        v-for="tab in tabs" 
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all duration-300"
                        :class="activeTab === tab.id ? 'border-namira-teal text-namira-teal scale-105' : 'border-transparent text-gray-400 hover:text-gray-600 hover:border-gray-300'"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 p-8 min-h-[400px]">
                
                <!-- Personal Info Tab -->
                <div v-if="activeTab === 'personal'" class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8 animate-fade-in-up">
                    <div>
                         <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2 pb-2 border-b border-gray-100">
                             <div class="p-2 bg-purple-50 rounded-lg text-purple-600">
                                <IdentificationIcon class="w-5 h-5" />
                            </div>
                            Identitas Diri
                        </h3>
                        <dl class="space-y-6">
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Nama Lengkap</dt>
                                <dd class="text-base font-bold text-gray-900">{{ staff.full_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Nomor Induk Pegawai (NIP)</dt>
                                <dd class="text-base font-mono font-bold text-gray-700 bg-gray-50 px-3 py-1.5 rounded-lg inline-block border border-gray-200 shadow-sm">{{ staff.nip || '-' }}</dd>
                            </div>
                             <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Jabatan / Posisi</dt>
                                <dd class="text-base font-medium text-gray-700">{{ staff.position || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Nomor Telepon / WA</dt>
                                <dd class="text-base font-mono font-medium">
                                     <a v-if="staff.phone" :href="`https://wa.me/${staff.phone}`" target="_blank" class="text-namira-teal hover:underline flex items-center gap-2 bg-teal-50 px-3 py-1.5 rounded-lg w-fit border border-teal-100">
                                        <PhoneIcon class="w-4 h-4" />
                                        {{ staff.phone }}
                                     </a>
                                     <span v-else class="text-gray-400">-</span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                     <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2 pb-2 border-b border-gray-100">
                             <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                                <ShieldCheckIcon class="w-5 h-5" />
                            </div>
                            Akun & Keamanan
                        </h3>
                        <dl class="space-y-6">
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Status Akun</dt>
                                <dd>
                                    <span class="px-3 py-1 bg-green-50 text-green-600 rounded-lg text-xs font-bold border border-green-100 flex items-center w-fit gap-2">
                                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                        Aktif
                                    </span>
                                </dd>
                            </div>
                             <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Email Login</dt>
                                <dd class="text-base font-medium text-gray-700">{{ staff.user?.email || '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
                
                <!-- Attendance Tab -->
                <div v-if="activeTab === 'attendance'" class="animate-fade-in-up">
                    <div v-if="!attendanceHistory || attendanceHistory.length === 0" class="p-12 text-center text-gray-400">
                        <div class="flex justify-center mb-4 opacity-50">
                              <ClockIcon class="w-16 h-16" />
                        </div>
                        <h3 class="text-lg font-bold text-gray-600 mb-2">Belum Ada Data</h3>
                        <p class="text-sm">Belum ada riwayat absensi yang tercatat.</p>
                    </div>

                    <div v-else class="overflow-hidden rounded-xl border border-gray-100 shadow-sm">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50/50 border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-4 font-bold">Tanggal</th>
                                    <th class="px-6 py-4 font-bold">Jam Masuk</th>
                                    <th class="px-6 py-4 font-bold">Jam Pulang</th>
                                    <th class="px-6 py-4 font-bold">Status</th>
                                    <th class="px-6 py-4 font-bold">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="att in attendanceHistory" :key="att.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-gray-700 capitalize">
                                        {{ new Date(att.date).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                                    </td>
                                    <td class="px-6 py-4 font-mono text-namira-teal font-bold">
                                        {{ att.check_in_time ? att.check_in_time.substring(0, 5) : '-' }}
                                    </td>
                                    <td class="px-6 py-4 font-mono text-gray-600 font-bold">
                                        {{ att.check_out_time ? att.check_out_time.substring(0, 5) : '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span v-if="att.status === 'present'" class="px-2.5 py-1 rounded-lg bg-green-50 text-green-700 text-xs font-bold border border-green-100">Hadir</span>
                                        <span v-else-if="att.status === 'late'" class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 text-xs font-bold border border-amber-100">Telat</span>
                                        <span v-else-if="att.status === 'permit'" class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100">Izin</span>
                                        <span v-else-if="att.status === 'sick'" class="px-2.5 py-1 rounded-lg bg-purple-50 text-purple-700 text-xs font-bold border border-purple-100">Sakit</span>
                                        <span v-else class="px-2.5 py-1 rounded-lg bg-red-50 text-red-700 text-xs font-bold border border-red-100">Alpha</span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 italic">
                                        {{ att.note || '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.4s ease-out forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
