<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, watch, computed } from 'vue';
import { 
    MagnifyingGlassIcon, 
    PlusIcon, 
    FunnelIcon,
    CalendarIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    UserCircleIcon,
    ChatBubbleLeftRightIcon,
    MapPinIcon,
    VideoCameraIcon
} from '@heroicons/vue/24/outline';
import Pagination from '@/Components/Pagination.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    sessions: Object,
    filters: Object,
    canCreate: Boolean,
});

const search = ref(props.filters.search || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const status = ref(props.filters.status || '');
const isLoading = ref(false);
const showFilter = ref(!!(props.filters.start_date || props.filters.end_date || props.filters.status));

const toggleFilter = () => {
    showFilter.value = !showFilter.value;
};

const handleSearch = () => {
    isLoading.value = true;
    router.get(route('counseling.sessions.index'), {
        search: search.value,
        start_date: startDate.value,
        end_date: endDate.value,
        status: status.value,
    }, { 
        preserveState: true, 
        replace: true,
        onFinish: () => isLoading.value = false
    });
};

watch(search, debounce(handleSearch, 500));
watch([startDate, endDate, status], handleSearch);

const resetFilter = () => {
    startDate.value = '';
    endDate.value = '';
    status.value = '';
    search.value = '';
    handleSearch();
};

const statusBadgeClass = (status) => {
    switch (status) {
        case 'Scheduled': return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'Completed': return 'bg-green-50 text-green-700 border-green-200';
        case 'Cancelled': return 'bg-red-50 text-red-700 border-red-200';
        default: return 'bg-gray-50 text-gray-700 border-gray-200';
    }
};

const methodIcon = (method) => {
    if (method === 'Online') return VideoCameraIcon;
    if (method === 'Home Visit') return MapPinIcon;
    return ChatBubbleLeftRightIcon; // Offline
};
</script>

<template>
    <Head title="Sesi Konseling" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                    Sesi Konseling & Coaching
                </h2>
                <p class="text-sm text-gray-500">
                    Jadwal dan riwayat bimbingan individual siswa.
                </p>
            </div>
        </template>

        <div class="py-4 md:py-6 max-w-7xl mx-auto space-y-5 md:space-y-6">

            <!-- 📱 MOBILE PWA VIEW (block md:hidden) -->
            <div class="block md:hidden -mx-4 -mt-4 space-y-4">
                <!-- Header Card Gradient -->
                <div class="bg-gradient-to-br from-[#009688] to-[#0f172a] px-4 pt-5 pb-6 text-white">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-[10px] font-extrabold tracking-widest uppercase text-teal-300">Bimbingan Konseling</p>
                            <h1 class="text-xl font-black leading-tight">Sesi & Coaching</h1>
                        </div>
                        <Link v-if="canCreate" :href="route('counseling.sessions.create')">
                            <button class="px-3.5 py-2 bg-purple-500 hover:bg-purple-600 text-white font-extrabold text-xs rounded-xl shadow-lg flex items-center gap-1.5 active:scale-95 transition">
                                <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                                <span>Buat Jadwal</span>
                            </button>
                        </Link>
                    </div>

                    <!-- Quick Stats -->
                    <div class="grid grid-cols-3 gap-2 mt-4 text-center">
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-2xl px-2.5 py-2">
                            <p class="text-xl font-black text-white leading-none">{{ sessions.total || 0 }}</p>
                            <p class="text-[9px] text-teal-200 font-bold mt-1 uppercase">Total Sesi</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-2xl px-2.5 py-2">
                            <p class="text-xl font-black text-blue-300 leading-none">
                                {{ sessions.data.filter(s => s.status === 'Scheduled').length }}
                            </p>
                            <p class="text-[9px] text-blue-200 font-bold mt-1 uppercase">Terjadwal</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-2xl px-2.5 py-2">
                            <p class="text-xl font-black text-emerald-300 leading-none">
                                {{ sessions.data.filter(s => s.status === 'Completed').length }}
                            </p>
                            <p class="text-[9px] text-emerald-200 font-bold mt-1 uppercase">Selesai</p>
                        </div>
                    </div>
                </div>

                <!-- Search & Filter Controls -->
                <div class="px-4 space-y-3">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-3.5 text-slate-400" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari nama siswa..."
                                class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:ring-teal-500 focus:border-teal-500 shadow-sm"
                            />
                        </div>
                        <button
                            @click="toggleFilter"
                            class="p-2.5 rounded-xl border text-slate-700 bg-white shadow-sm active:scale-95 transition"
                            :class="showFilter ? 'border-teal-500 text-teal-700 bg-teal-50/50' : 'border-slate-200'"
                        >
                            <FunnelIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Collapsible Filter -->
                    <div v-if="showFilter" class="bg-white border border-slate-200 rounded-2xl p-3.5 shadow-sm space-y-3 animate-fade-in-down text-xs">
                        <div>
                            <label class="block font-bold text-slate-500 text-[10px] uppercase mb-1">Status Sesi</label>
                            <select v-model="status" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold">
                                <option value="">Semua Status</option>
                                <option value="Scheduled">Dijadwalkan</option>
                                <option value="Completed">Selesai</option>
                                <option value="Cancelled">Dibatalkan</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block font-bold text-slate-500 text-[10px] uppercase mb-1">Dari</label>
                                <input type="date" v-model="startDate" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold" />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-500 text-[10px] uppercase mb-1">Sampai</label>
                                <input type="date" v-model="endDate" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold" />
                            </div>
                        </div>
                        <button @click="resetFilter" class="text-[11px] font-bold text-red-600 hover:underline block ml-auto">
                            Reset Filter
                        </button>
                    </div>
                </div>

                <!-- Mobile Session Touch Cards -->
                <div class="px-4 space-y-3">
                    <div v-if="sessions.data.length === 0" class="bg-white rounded-2xl p-8 text-center border border-slate-100 shadow-sm">
                        <ChatBubbleLeftRightIcon class="w-10 h-10 mx-auto text-purple-300 mb-2" />
                        <p class="font-extrabold text-sm text-slate-800">Belum ada sesi konseling</p>
                        <p class="text-xs text-slate-400 mt-1">Jadwal bimbingan siswa masih kosong.</p>
                    </div>

                    <div
                        v-for="session in sessions.data"
                        :key="session.id"
                        class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm space-y-3 relative overflow-hidden"
                    >
                        <!-- Top Row: Date/Time & Status -->
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                            <div class="flex items-center gap-2 text-xs font-black text-slate-700">
                                <CalendarIcon class="w-4 h-4 text-purple-600 shrink-0" />
                                <span>{{ new Date(session.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
                                <span class="text-slate-400">·</span>
                                <ClockIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                <span>{{ session.time }} WIB</span>
                            </div>
                            <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-black border uppercase tracking-wider', statusBadgeClass(session.status)]">
                                {{ session.status === 'Scheduled' ? 'Terjadwal' : (session.status === 'Completed' ? 'Selesai' : 'Batal') }}
                            </span>
                        </div>

                        <!-- Student & Method -->
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-purple-50 flex items-center justify-center text-purple-700 font-black text-xs shrink-0 border border-purple-100">
                                    {{ session.student_name?.charAt(0) || '?' }}
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-sm leading-tight">{{ session.student_name }}</h3>
                                    <p class="text-xs text-slate-400 font-semibold mt-0.5">{{ session.student_classroom }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 text-xs text-slate-600 bg-slate-50 px-2.5 py-1 rounded-xl border border-slate-100">
                                <component :is="methodIcon(session.method)" class="w-3.5 h-3.5 text-purple-600" />
                                <span class="font-bold text-[11px]">{{ session.method }}</span>
                            </div>
                        </div>

                        <!-- Violation Tag if present -->
                        <div v-if="session.violation" class="text-xs text-red-600 bg-red-50 p-2.5 rounded-xl border border-red-100 font-semibold flex items-center gap-1.5">
                            <ExclamationTriangleIcon class="w-4 h-4 text-red-500 shrink-0" />
                            <span class="truncate">Pelanggaran: {{ session.violation }}</span>
                        </div>

                        <!-- Footer: Counselor & Action -->
                        <div class="pt-2 flex items-center justify-between text-xs text-slate-500 border-t border-slate-100">
                            <span class="flex items-center gap-1 font-semibold text-[11px]">
                                <UserCircleIcon class="w-3.5 h-3.5 text-slate-400" />
                                {{ session.counselor_name }}
                            </span>
                            <Link v-if="session.can_action" :href="route('counseling.sessions.edit', session.id)" class="text-purple-600 hover:text-purple-800 font-extrabold text-xs bg-purple-50 hover:bg-purple-100 px-3 py-1.5 rounded-xl transition">
                                {{ session.status === 'Completed' ? 'Lihat/Edit' : 'Kelola Sesi' }}
                            </Link>
                            <span v-else class="text-xs text-slate-400 italic">Read Only</span>
                        </div>
                    </div>

                    <!-- Mobile Pagination -->
                    <Pagination :links="sessions.links" class="pt-2" />
                </div>
            </div>
            <!-- END MOBILE VIEW -->

            <!-- 🖥️ DESKTOP VIEW (hidden md:block) -->
            <div class="hidden md:block space-y-6">
                <!-- Toolbar -->
                <div class="flex flex-col md:flex-row items-center gap-4">
                    <!-- Search -->
                    <div class="relative group flex-1 w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 group-focus-within:text-namira-teal transition-colors">
                            <MagnifyingGlassIcon class="w-5 h-5" />
                        </div>
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Cari Siswa..." 
                            class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                        >
                    </div>

                    <!-- Toggle Filter -->
                    <button @click="toggleFilter" class="px-4 py-2.5 bg-white/50 backdrop-blur-sm border border-white/50 text-gray-600 rounded-2xl text-sm font-bold hover:bg-white hover:shadow-md transition-all flex items-center justify-center gap-2 active:scale-95 h-[46px]" :class="{'border-namira-teal text-namira-teal bg-teal-50/50': showFilter}">
                        <FunnelIcon class="w-5 h-5" />
                        <span class="hidden md:inline">Filter</span>
                    </button>

                    <!-- Create Button -->
                    <Link :href="route('counseling.sessions.create')">
                        <button 
                            class="px-6 py-2.5 bg-purple-600 text-white rounded-2xl font-bold shadow-lg shadow-purple-600/30 hover:bg-purple-700 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 whitespace-nowrap active:scale-95 h-[46px]"
                        >
                            <PlusIcon class="w-5 h-5" />
                            <span>Buat Jadwal</span>
                        </button>
                    </Link>
                </div>

                <!-- Filters -->
                <div v-if="showFilter" class="mb-6 px-6 py-4 bg-white/80 backdrop-blur-xl rounded-2xl border border-white/50 shadow-sm flex flex-wrap items-center gap-4 animate-fade-in-down">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-gray-600">Status:</span>
                        <select v-model="status" class="px-3 py-1.5 rounded-xl text-sm border-gray-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50">
                            <option value="">Semua</option>
                            <option value="Scheduled">Dijadwalkan</option>
                            <option value="Completed">Selesai</option>
                            <option value="Cancelled">Dibatalkan</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-gray-600">Tanggal:</span>
                        <input type="date" v-model="startDate" class="px-3 py-1.5 rounded-xl text-sm border-gray-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50">
                        <span class="text-gray-400">-</span>
                        <input type="date" v-model="endDate" class="px-3 py-1.5 rounded-xl text-sm border-gray-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50">
                    </div>
                    <button @click="resetFilter" class="ml-auto text-xs text-red-500 hover:text-red-700 font-bold hover:underline">
                        Reset
                    </button>
                </div>

                <!-- List / Table -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-500 uppercase bg-white/50 border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-5 font-extrabold tracking-wider">Jadwal</th>
                                    <th class="px-6 py-5 font-extrabold tracking-wider">Siswa</th>
                                    <th class="px-6 py-5 font-extrabold tracking-wider">Metode</th>
                                    <th class="px-6 py-5 font-extrabold tracking-wider">Status/Catatan</th>
                                    <th class="px-6 py-5 font-extrabold tracking-wider">Konselor</th>
                                    <th class="px-6 py-5 font-extrabold tracking-wider text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="session in sessions.data" :key="session.id" class="group hover:bg-purple-50/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-2 font-bold text-gray-700">
                                                <CalendarIcon class="w-4 h-4 text-purple-500" />
                                                {{ new Date(session.date).toLocaleDateString('id-ID', {day: 'numeric', month: 'short'}) }}
                                            </div>
                                            <div class="flex items-center gap-2 text-xs text-gray-500 mt-1 pl-6">
                                                <ClockIcon class="w-3.5 h-3.5" />
                                                {{ session.time }} WIB
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900 group-hover:text-purple-600 transition-colors">{{ session.student_name }}</div>
                                        <div class="text-xs text-slate-500">{{ session.student_classroom }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 text-gray-600">
                                            <component :is="methodIcon(session.method)" class="w-4 h-4" />
                                            <span>{{ session.method }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-2 items-start">
                                            <span :class="['px-2.5 py-1 rounded-lg border text-xs font-bold shadow-sm', statusBadgeClass(session.status)]">
                                                {{ session.status === 'Scheduled' ? 'Terjadwal' : (session.status === 'Completed' ? 'Selesai' : 'Batal') }}
                                            </span>
                                            <div v-if="session.violation" class="text-xs text-red-500 bg-red-50 px-2 py-0.5 rounded border border-red-100 truncate max-w-[150px]" title="Terkait Pelanggaran">
                                                Pelanggaran: {{ session.violation }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 text-xs text-slate-500">
                                            <UserCircleIcon class="w-4 h-4" />
                                            {{ session.counselor_name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <Link v-if="session.can_action" :href="route('counseling.sessions.edit', session.id)" class="text-purple-600 hover:text-purple-800 font-bold text-xs bg-purple-50 hover:bg-purple-100 px-3 py-1.5 rounded-xl transition-colors inline-block">
                                            {{ session.status === 'Completed' ? 'Lihat/Edit' : 'Kelola Sesi' }}
                                        </Link>
                                        <span v-else class="text-xs text-gray-400 italic">
                                            Read Only
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="sessions.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                        Belum ada jadwal sesi konseling.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <Pagination :links="sessions.links" class="p-6 border-t border-gray-100 bg-white/50" />
                </div>
            </div>
            <!-- END DESKTOP VIEW -->

        </div>
    </AuthenticatedLayout>
</template>
