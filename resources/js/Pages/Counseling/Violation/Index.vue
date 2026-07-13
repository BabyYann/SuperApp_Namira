<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, watch } from 'vue';
import { 
    MagnifyingGlassIcon, 
    PlusIcon, 
    FunnelIcon,
    PhotoIcon,
    TrashIcon,
    ExclamationTriangleIcon,
    ArrowPathIcon,
    CalendarIcon,
    UserCircleIcon
} from '@heroicons/vue/24/outline';
import Pagination from '@/Components/Pagination.vue';
import Swal from 'sweetalert2';
import debounce from 'lodash/debounce';

const props = defineProps({
    violations: Object,
    filters: Object,
    canDelete: Boolean,
});

const search = ref(props.filters.search || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const isLoading = ref(false);
const showFilter = ref(!!(props.filters.start_date || props.filters.end_date));

const toggleFilter = () => {
    showFilter.value = !showFilter.value;
};

const handleSearch = () => {
    isLoading.value = true;
    router.get(route('counseling.violations.index'), {
        search: search.value,
        start_date: startDate.value,
        end_date: endDate.value,
    }, { 
        preserveState: true, 
        replace: true,
        onFinish: () => isLoading.value = false
    });
};

// Debounce Search
watch(search, debounce((value) => {
    handleSearch();
}, 500));

watch([startDate, endDate], () => {
    handleSearch();
});

const confirmDelete = (id) => {
    if (!props.canDelete) return;

    Swal.fire({
        title: 'Hapus Laporan?',
        text: "Data poin siswa akan dikurangi.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('counseling.violations.destroy', id), {
                onSuccess: () => Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success')
            });
        }
    });
};

const resetFilter = () => {
    startDate.value = '';
    endDate.value = '';
    search.value = '';
    handleSearch();
};

const showLightbox = ref(false);
const activePhoto = ref(null);

const openLightbox = (photoUrl) => {
    if (!photoUrl) return;
    activePhoto.value = photoUrl;
    showLightbox.value = true;
};

const closeLightbox = () => {
    showLightbox.value = false;
    setTimeout(() => {
        activePhoto.value = null;
    }, 200); // Wait for transition
};
</script>

<template>
    <Head title="Rekap Pelanggaran" />

    <AuthenticatedLayout>
        <template #header>
             <div class="flex flex-col gap-1">
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                    Rekapitulasi Pelanggaran
                </h2>
                <p class="text-sm text-gray-500">
                    Total Kasus: <span class="font-bold text-red-600">{{ violations.total }} Laporan</span>
                </p>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto pb-20 space-y-6">
            
            <!-- Toolbar -->
            <div class="flex flex-col md:flex-row items-center gap-4">
                 <!-- Search Bar -->
                <div class="relative group flex-1 w-full">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 group-focus-within:text-namira-teal transition-colors">
                            <MagnifyingGlassIcon v-if="!isLoading" class="w-5 h-5" />
                            <ArrowPathIcon v-else class="animate-spin h-5 w-5 text-namira-teal" />
                    </div>
                    <input 
                        v-model="search"
                        type="text" 
                        placeholder="Cari Siswa / NIS / Kategori..." 
                        class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                    >
                </div>

                <!-- Filter Toggle -->
                <button @click="toggleFilter" class="px-4 py-2.5 bg-white/50 backdrop-blur-sm border border-white/50 text-gray-600 rounded-2xl text-sm font-bold hover:bg-white hover:shadow-md transition-all flex items-center justify-center gap-2 active:scale-95 h-[46px]" :class="{'border-namira-teal text-namira-teal bg-teal-50/50': showFilter}">
                    <FunnelIcon class="w-5 h-5" />
                    <span class="hidden md:inline">Filter Tanggal</span>
                </button>

                <Link :href="route('counseling.violations.create')">
                    <button 
                        class="px-6 py-2.5 bg-red-600 text-white rounded-2xl font-bold shadow-lg shadow-red-600/30 hover:bg-red-700 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 whitespace-nowrap active:scale-95 h-[46px]"
                    >
                        <PlusIcon class="w-5 h-5" />
                        <span>Catat Pelanggaran</span>
                    </button>
                </Link>
            </div>

            <!-- Filters (Conditionally Visible) -->
            <div v-if="showFilter" class="mb-6 px-6 py-4 bg-white/80 backdrop-blur-xl rounded-2xl border border-white/50 shadow-sm flex flex-wrap items-center gap-4 animate-fade-in-down">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-gray-600">Dari:</span>
                    <input type="date" v-model="startDate" class="px-3 py-1.5 rounded-xl text-sm border-gray-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50">
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-gray-600">Sampai:</span>
                    <input type="date" v-model="endDate" class="px-3 py-1.5 rounded-xl text-sm border-gray-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50">
                </div>
                <button @click="resetFilter" class="ml-auto text-xs text-red-500 hover:text-red-700 font-bold hover:underline">
                    Reset Filter
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-white/50 border-b border-gray-100">
                            <tr>
                                <th scope="col" class="px-6 py-5 font-extrabold tracking-wider">Tanggal</th>
                                <th scope="col" class="px-6 py-5 font-extrabold tracking-wider">Siswa</th>
                                <th scope="col" class="px-6 py-5 font-extrabold tracking-wider">Pelanggaran</th>
                                <th scope="col" class="px-6 py-5 font-extrabold tracking-wider">Poin</th>
                                <th scope="col" class="px-6 py-5 font-extrabold tracking-wider">Pelapor</th>
                                <th scope="col" class="px-6 py-5 font-extrabold tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="v in violations.data" :key="v.id" class="group hover:bg-teal-50/30 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-600">
                                    <div class="flex items-center gap-2">
                                        <CalendarIcon class="w-4 h-4 text-gray-400" />
                                        {{ new Date(v.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric'}) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-teal-50 flex items-center justify-center text-teal-700 font-bold text-xs ring-2 ring-white shadow-sm border border-teal-100">
                                            {{ v.student?.name?.charAt(0) || '?' }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900 group-hover:text-namira-teal transition-colors">{{ v.student?.name }}</div>
                                            <div class="text-xs text-slate-500 bg-gray-100 px-1.5 py-0.5 rounded-md inline-block mt-0.5 border border-gray-200">
                                                {{ v.student?.classroom || 'No Class' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="font-bold text-gray-700 flex items-center gap-2">
                                            {{ v.category?.name }}
                                            <button 
                                                v-if="v.photo_proof" 
                                                @click="openLightbox(v.photo_proof)"
                                                class="text-blue-500 hover:text-blue-700 transition-colors bg-blue-50 hover:bg-blue-100 p-1 rounded-full" 
                                                title="Lihat Bukti Foto"
                                            >
                                                <PhotoIcon class="w-4 h-4" />
                                            </button>
                                        </span>
                                        <div class="text-xs text-gray-500 italic truncate max-w-xs pl-2 border-l-2 border-gray-200">
                                            {{ v.description || 'Tidak ada keterangan.' }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 font-extrabold text-red-600 bg-red-50 px-2 py-1 rounded-lg border border-red-100 shadow-sm">
                                        <ExclamationTriangleIcon class="w-3 h-3" />
                                        +{{ v.points }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2 text-xs text-slate-500">
                                        <UserCircleIcon class="w-4 h-4 text-slate-400" />
                                        <span class="font-medium">{{ v.reporter_name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button 
                                        v-if="canDelete"
                                        @click="confirmDelete(v.id)" 
                                        class="p-2 rounded-xl text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors border border-transparent hover:border-red-100"
                                        title="Hapus Laporan"
                                    >
                                        <TrashIcon class="w-5 h-5" />
                                    </button>
                                     <span v-else class="text-xs text-gray-300 italic">No Access</span>
                                </td>
                            </tr>
                            <tr v-if="violations.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center p-8">
                                        <div class="bg-red-50 p-6 rounded-full mb-4">
                                            <ExclamationTriangleIcon class="w-12 h-12 text-red-300" />
                                        </div>
                                        <p class="font-bold text-lg text-gray-800 mb-1">Belum ada pelanggaran</p>
                                        <p class="text-sm text-gray-500">Data pelanggaran siswa masih kosong.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                 <Pagination :links="violations.links" class="p-6 border-t border-gray-100 bg-white/50" />
            </div>

        </div>



        <!-- Lightbox Modal -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div v-if="showLightbox" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" @click.self="closeLightbox">
                <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity" @click="closeLightbox"></div>

                <div class="relative bg-white rounded-2xl shadow-2xl overflow-hidden max-w-4xl w-full max-h-[90vh] flex flex-col">
                    <div class="flex items-center justify-between p-4 border-b border-gray-100">
                        <h3 class="font-bold text-gray-900">Bukti Foto</h3>
                        <button @click="closeLightbox" class="p-1 rounded-full hover:bg-gray-100 text-gray-500 transition-colors">
                            <span class="sr-only">Close</span>
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="p-4 bg-gray-50 flex-1 flex items-center justify-center overflow-auto">
                        <img :src="activePhoto" class="max-w-full max-h-[75vh] object-contain rounded-lg shadow-sm" alt="Bukti Pelanggaran" />
                    </div>
                    <div class="p-4 bg-white border-t border-gray-100 text-right">
                        <a :href="activePhoto" download target="_blank" class="text-sm font-bold text-namira-teal hover:underline inline-flex items-center gap-1">
                            Download Gambar
                        </a>
                    </div>
                </div>
            </div>
        </Transition>

    </AuthenticatedLayout>
</template>
