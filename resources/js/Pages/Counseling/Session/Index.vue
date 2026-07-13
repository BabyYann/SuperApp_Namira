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

        <div class="py-6 max-w-7xl mx-auto pb-20 space-y-6">
            
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
                                            {{ session.status === 'Scheduled' ? '📅 Terjadwal' : (session.status === 'Completed' ? '✅ Selesai' : '❌ Batal') }}
                                        </span>
                                        <div v-if="session.violation" class="text-xs text-red-500 bg-red-50 px-2 py-0.5 rounded border border-red-100 truncate max-w-[150px]" title="Terkait Pelanggaran">
                                            ⚠️ {{ session.violation }}
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
                <Pagination :links="sessions.links" class="p-6 border-t border-gray-100" />
            </div>

        </div>
    </AuthenticatedLayout>
</template>
