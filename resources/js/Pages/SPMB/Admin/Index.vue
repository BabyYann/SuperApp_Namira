<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { 
    MagnifyingGlassIcon, 
    FunnelIcon, 
    Cog6ToothIcon, 
    CheckCircleIcon,
    ClockIcon,
    CalendarDaysIcon,
    EyeIcon,
    ArrowDownTrayIcon,
    UserGroupIcon,
    SparklesIcon,
    ChatBubbleLeftRightIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    applicants: Object,
    stats: Object,
    filters: Object,
    unit: Object,
});

const search = ref(props.filters?.search || '');
const category = ref(props.filters?.category || 'all');
const status = ref(props.filters?.status || 'all');

let searchTimeout = null;

const applyFilters = () => {
    router.get(route('spmb.admin.index'), {
        search: search.value || undefined,
        category: category.value !== 'all' ? category.value : undefined,
        status: status.value !== 'all' ? status.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

watch([category, status], () => {
    applyFilters();
});

const getStatusBadgeClass = (s) => {
    return {
        'submitted': 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-300 dark:border-amber-700',
        'verified': 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border-blue-300 dark:border-blue-700',
        'scheduled': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300 border-indigo-300 dark:border-indigo-700',
        'evaluated': 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border-purple-300 dark:border-purple-700',
        'accepted': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700',
        'reserve': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950/60 dark:text-yellow-300 border-yellow-300 dark:border-yellow-700',
        'rejected': 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border-rose-300 dark:border-rose-700',
        'partial_paid': 'bg-teal-100 text-teal-800 dark:bg-teal-950/60 dark:text-teal-300 border-teal-300 dark:border-teal-700',
        'fully_paid': 'bg-green-100 text-green-800 dark:bg-green-950/60 dark:text-green-300 border-green-300 dark:border-green-700',
        'enrolled': 'bg-emerald-200 text-emerald-900 dark:bg-emerald-900/80 dark:text-emerald-200 border-emerald-400 font-bold',
    }[s] || 'bg-slate-100 text-slate-800 border-slate-300';
};
</script>

<template>
    <Head title="Data Pendaftar SPMB Inden SD Namira" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            
            <!-- Top Header & Action Buttons -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                        <UserGroupIcon class="w-7 h-7 text-[#064e3b] dark:text-[#fbbf24]" />
                        <span>Penerimaan Siswa Baru (SPMB Inden)</span>
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Kelola seleksi pendaftar, verifikasi pembayaran QRIS, jadwal observasi & psikotes, serta penetapan siswa aktif.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link 
                        :href="route('spmb.admin.settings')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition border border-slate-300 dark:border-slate-700"
                    >
                        <Cog6ToothIcon class="w-4 h-4 text-[#064e3b] dark:text-[#fbbf24]" />
                        <span>Pengaturan & Panitia</span>
                    </Link>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] font-semibold text-slate-500 block uppercase">Total Pendaftar</span>
                    <span class="text-2xl font-black text-slate-900 dark:text-white block mt-1">{{ stats.total }}</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 block uppercase">Alumni TK Namira</span>
                    <span class="text-2xl font-black text-emerald-700 dark:text-emerald-300 block mt-1">{{ stats.internal_tk }}</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 block uppercase">Pendaftar Umum</span>
                    <span class="text-2xl font-black text-blue-700 dark:text-blue-300 block mt-1">{{ stats.external_umum }}</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] font-semibold text-amber-600 dark:text-amber-400 block uppercase">Menunggu Cek QRIS</span>
                    <span class="text-2xl font-black text-amber-700 dark:text-amber-300 block mt-1">{{ stats.submitted }}</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] font-semibold text-purple-600 dark:text-purple-400 block uppercase">Lolos Seleksi</span>
                    <span class="text-2xl font-black text-purple-700 dark:text-purple-300 block mt-1">{{ stats.accepted }}</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 block uppercase">Resmi Siswa Aktif</span>
                    <span class="text-2xl font-black text-emerald-700 dark:text-emerald-300 block mt-1">{{ stats.enrolled }}</span>
                </div>
            </div>

            <!-- Filters & Search Toolbar -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row items-center justify-between gap-3">
                <div class="relative w-full md:w-80">
                    <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input 
                        v-model="search"
                        type="text" 
                        placeholder="Cari nama siswa, no. reg, no WA..."
                        class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-emerald-500 focus:border-emerald-500"
                    >
                </div>

                <div class="flex items-center gap-2.5 w-full md:w-auto">
                    <!-- Filter Kategori Asal -->
                    <select 
                        v-model="category"
                        class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-200 focus:ring-emerald-500"
                    >
                        <option value="all">Semua Kategori Asal</option>
                        <option value="internal_tk">Khusus Alumni TK Namira</option>
                        <option value="eksternal_umum">Pendaftar Luar (Umum)</option>
                    </select>

                    <!-- Filter Status -->
                    <select 
                        v-model="status"
                        class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-200 focus:ring-emerald-500"
                    >
                        <option value="all">Semua Status</option>
                        <option value="submitted">Menunggu Verifikasi QRIS</option>
                        <option value="verified">Terverifikasi (Siap Jadwal)</option>
                        <option value="scheduled">Jadwal Diterbitkan</option>
                        <option value="accepted">Diterima (Lulus)</option>
                        <option value="reserve">Cadangan</option>
                        <option value="rejected">Belum Diterima</option>
                        <option value="partial_paid">Termin 60%</option>
                        <option value="fully_paid">Lunas 100%</option>
                        <option value="enrolled">Resmi Siswa</option>
                    </select>
                </div>
            </div>

            <!-- Applicants Table -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 font-bold border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider">
                            <tr>
                                <th class="py-3.5 px-4">Calon Siswa</th>
                                <th class="py-3.5 px-4">No. Registrasi</th>
                                <th class="py-3.5 px-4">Asal Sekolah</th>
                                <th class="py-3.5 px-4">Orang Tua / Kontak</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Jadwal Observasi & Psikotes</th>
                                <th class="py-3.5 px-4 text-center">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <tr 
                                v-for="app in applicants.data" 
                                :key="app.id"
                                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition"
                            >
                                <!-- Siswa -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-11 rounded-lg bg-slate-100 dark:bg-slate-800 overflow-hidden shrink-0 border border-slate-200 dark:border-slate-700">
                                            <img v-if="app.photo_url" :src="app.photo_url" class="w-full h-full object-cover">
                                            <div v-else class="w-full h-full flex items-center justify-center text-[10px] text-slate-400">Foto</div>
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 dark:text-white block">{{ app.full_name }}</span>
                                            <span class="text-[11px] text-slate-400 block">
                                                {{ app.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}, {{ app.birth_place }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Reg Number -->
                                <td class="py-3.5 px-4">
                                    <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400">
                                        {{ app.registration_number }}
                                    </span>
                                </td>

                                <!-- Asal Sekolah -->
                                <td class="py-3.5 px-4">
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 block">
                                        {{ app.previous_school || '-' }}
                                    </span>
                                    <span 
                                        class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold mt-0.5"
                                        :class="app.category === 'internal_tk' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'"
                                    >
                                        {{ app.category === 'internal_tk' ? 'TK Namira' : 'Umum' }}
                                    </span>
                                </td>

                                <!-- Ortu & Kontak -->
                                <td class="py-3.5 px-4">
                                    <span class="text-slate-900 dark:text-white font-medium block">
                                        {{ app.father_name || app.mother_name }}
                                    </span>
                                    <span class="text-[11px] text-slate-500 font-mono block">
                                        {{ app.parent_phone }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-4">
                                    <span 
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border"
                                        :class="getStatusBadgeClass(app.status)"
                                    >
                                        {{ app.status_label }}
                                    </span>
                                </td>

                                <!-- Jadwal -->
                                <td class="py-3.5 px-4">
                                    <div v-if="app.observation_date" class="text-[11px] space-y-0.5">
                                        <div class="text-slate-700 dark:text-slate-300 font-medium">
                                            Obs: {{ app.observation_date }} ({{ app.observation_time || '-' }})
                                        </div>
                                        <div v-if="app.psychotest_date" class="text-amber-600 dark:text-amber-400 font-medium">
                                            Psi: {{ app.psychotest_date }} ({{ app.psychotest_time || '-' }})
                                        </div>
                                    </div>
                                    <span v-else class="text-[11px] text-slate-400 italic">Belum dijadwalkan</span>
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- WhatsApp Icon Button -->
                                        <a 
                                            :href="app.wa_link"
                                            target="_blank"
                                            title="Chat WhatsApp Orang Tua"
                                            class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 transition"
                                        >
                                            <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                        </a>

                                        <!-- Detail Button -->
                                        <Link 
                                            :href="route('spmb.admin.show', app.id)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-bold hover:bg-slate-800 transition"
                                        >
                                            <EyeIcon class="w-3.5 h-3.5" />
                                            <span>Periksa</span>
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="applicants.data.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                    Tidak ada data calon siswa yang sesuai dengan filter pencarian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="applicants.total > applicants.per_page" class="p-4 border-t border-slate-200 dark:border-slate-800">
                    <Pagination :links="applicants.links" />
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
