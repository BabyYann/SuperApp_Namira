<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    MagnifyingGlassIcon, PlusIcon, GlobeAltIcon, PencilSquareIcon, TrashIcon,
    ExclamationTriangleIcon, MapPinIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    destinations: Object,
    units: Array,
    filters: Object,
});

const searchQuery = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || '');
const unitFilter = ref(props.filters?.unit_id || '');

const isAdminView = computed(() => props.units && props.units.length > 1);

const applyFilters = () => {
    router.get(route('public-relations.university-destinations.index'), {
        search: searchQuery.value || undefined,
        type: typeFilter.value || undefined,
        unit_id: unitFilter.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

// Delete Logic
const showDeleteConfirm = ref(false);
const itemToDelete = ref(null);
const deleteForm = useForm({});

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteConfirm.value = true;
};

const closeModal = () => {
    showDeleteConfirm.value = false;
    setTimeout(() => { itemToDelete.value = null; }, 300);
};

const deleteItem = () => {
    if (!itemToDelete.value) return;
    deleteForm.delete(route('public-relations.university-destinations.destroy', itemToDelete.value.id), {
        onSuccess: () => closeModal(),
        onError: () => { closeModal(); alert('Gagal menghapus data.'); }
    });
};

// Flash message
const flash = computed(() => usePage?.props?.flash || {});

import { usePage } from '@inertiajs/vue3';
const page = usePage();
const successMessage = computed(() => page.props.flash?.success);

const typeLabel = (type) => {
    const map = { indonesia: 'Indonesia', overseas: 'Overseas', lokal: 'Lokal' };
    return map[type] || type;
};
const typeBadge = (type) => {
    const map = {
        indonesia: 'bg-blue-50 text-blue-700 border-blue-100',
        overseas: 'bg-purple-50 text-purple-700 border-purple-100',
        lokal: 'bg-green-50 text-green-700 border-green-100',
    };
    return map[type] || 'bg-gray-50 text-gray-700';
};
const visitTypeLabel = (vt) => vt === 'alumni' ? 'Alumni' : 'Kunjungan';
const formatDate = (d) => {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};
</script>

<template>
    <Head title="Destinasi Universitas" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight flex items-center gap-2">
                    <GlobeAltIcon class="w-6 h-6 text-namira-teal" />
                    Destinasi Universitas
                </h2>
                <p class="text-sm text-gray-500 mt-1">Kelola data kunjungan dan destinasi universitas unit sekolah.</p>
            </div>
        </template>

        <div class="py-4 md:py-6 max-w-7xl mx-auto space-y-5 md:space-y-6">

            <!-- Flash Success -->
            <transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="successMessage" class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 rounded-2xl px-5 py-3 text-sm font-medium shadow-sm">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    {{ successMessage }}
                </div>
            </transition>

            <!-- 1A. DESKTOP TOOLBAR (Unchanged Desktop Layout) -->
            <div class="hidden md:flex flex-row items-stretch gap-3">
                <!-- Search -->
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                        <MagnifyingGlassIcon class="w-5 h-5" />
                    </div>
                    <input
                        v-model="searchQuery"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Cari nama institusi atau kota..."
                        class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                    />
                </div>

                <!-- Filter Type -->
                <select v-model="typeFilter" @change="applyFilters" class="h-[46px] rounded-2xl border-white/50 bg-white/50 text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 shadow-sm px-3 pr-8">
                    <option value="">Semua Tipe</option>
                    <option value="indonesia">Indonesia</option>
                    <option value="overseas">Overseas</option>
                    <option value="lokal">Lokal</option>
                </select>

                <!-- Filter Unit (admin only) -->
                <select v-if="isAdminView" v-model="unitFilter" @change="applyFilters" class="h-[46px] rounded-2xl border-white/50 bg-white/50 text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 shadow-sm px-3 pr-8">
                    <option value="">Semua Unit</option>
                    <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                </select>

                <Link :href="route('public-relations.university-destinations.create')"
                    class="px-5 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 active:scale-95 h-[46px] whitespace-nowrap">
                    <PlusIcon class="w-5 h-5" />
                    <span>Tambah Destinasi</span>
                </Link>
            </div>

            <!-- 1B. MOBILE TOOLBAR (Executive Deep Namira Emerald Search & Add) -->
            <div class="block md:hidden bg-[#064e3b] text-white p-4 rounded-3xl border border-emerald-800/80 shadow-xl space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black tracking-widest text-teal-400 uppercase">Humas & Publikasi</span>
                        <h3 class="text-base font-extrabold text-white mt-0.5">Destinasi Kampus</h3>
                    </div>
                    <Link :href="route('public-relations.university-destinations.create')" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl text-xs font-extrabold flex items-center gap-1.5 shadow-md">
                        <PlusIcon class="w-4 h-4" />
                        <span>Tambah</span>
                    </Link>
                </div>
                <div class="relative w-full">
                    <input 
                        v-model="searchQuery"
                        @keyup.enter="applyFilters"
                        type="text" 
                        placeholder="Cari institusi / kota..." 
                        class="w-full bg-slate-800 border border-slate-700 text-white rounded-2xl text-xs font-bold p-3 focus:ring-teal-500 focus:border-teal-500"
                    >
                </div>
            </div>

            <!-- 2A. DESKTOP DATA TABLE (Unchanged Desktop Layout) -->
            <div class="hidden md:block bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/50 border-b border-gray-100 text-xs uppercase text-gray-500 font-extrabold tracking-wider">
                                <th class="p-5">No</th>
                                <th class="p-5">Unit</th>
                                <th class="p-5">Nama Institusi</th>
                                <th class="p-5">Kota / Negara</th>
                                <th class="p-5">Tipe</th>
                                <th class="p-5">Jenis</th>
                                <th class="p-5">Tgl. Kunjungan</th>
                                <th class="p-5">Status</th>
                                <th class="p-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="destinations.data.length === 0">
                                <td colspan="9" class="p-14 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                            <GlobeAltIcon class="w-10 h-10 text-gray-300" />
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900 mb-1">Belum ada data destinasi.</h3>
                                        <p class="text-sm">Mulai tambahkan destinasi universitas unit Anda.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="(item, index) in destinations.data" :key="'desk-'+item.id" class="hover:bg-teal-50/30 transition-colors group">
                                <td class="p-5 text-sm text-gray-400 font-mono">
                                    {{ (destinations.meta?.current_page - 1) * destinations.meta?.per_page + index + 1 }}
                                </td>
                                <td class="p-5 text-sm text-gray-600 font-medium">
                                    {{ item.unit?.name || '-' }}
                                </td>
                                <td class="p-5">
                                    <div class="flex items-center gap-2">
                                        <MapPinIcon class="w-4 h-4 text-namira-teal shrink-0" />
                                        <span class="font-bold text-gray-800 text-sm group-hover:text-namira-teal transition-colors">{{ item.name }}</span>
                                    </div>
                                </td>
                                <td class="p-5 text-sm text-gray-600">
                                    {{ item.city }}, {{ item.country }}
                                </td>
                                <td class="p-5">
                                    <span :class="typeBadge(item.type)" class="px-2.5 py-1 rounded-xl text-xs font-bold border shadow-sm">
                                        {{ typeLabel(item.type) }}
                                    </span>
                                </td>
                                <td class="p-5">
                                    <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-amber-50 text-amber-700 border border-amber-100 shadow-sm">
                                        {{ visitTypeLabel(item.visit_type) }}
                                    </span>
                                </td>
                                <td class="p-5 text-sm text-gray-600 font-mono">
                                    {{ formatDate(item.visit_date) }}
                                </td>
                                <td class="p-5">
                                    <span :class="item.is_active ? 'bg-green-50 text-green-700 border-green-100' : 'bg-gray-50 text-gray-500 border-gray-100'" class="px-2.5 py-1 rounded-xl text-xs font-bold border">
                                        {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="p-5 text-right">
                                    <div class="flex justify-end gap-1.5 opacity-0 group-hover:opacity-100 transition-all">
                                        <Link :href="route('public-relations.university-destinations.edit', item.id)" class="p-2 text-gray-400 hover:text-namira-teal hover:bg-teal-50 rounded-xl border border-transparent hover:border-teal-100" title="Edit">
                                            <PencilSquareIcon class="w-4 h-4" />
                                        </Link>
                                        <button @click="confirmDelete(item)" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl border border-transparent hover:border-red-100 cursor-pointer" title="Hapus">
                                            <TrashIcon class="w-4 h-4 pointer-events-none" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2B. MOBILE NATIVE DESTINATION CARDS -->
            <div class="grid md:hidden grid-cols-1 gap-3.5">
                <div v-if="destinations.data.length === 0" class="text-center py-12 bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                    <GlobeAltIcon class="w-12 h-12 text-slate-400 mx-auto mb-3" />
                    <h3 class="text-base font-bold text-slate-900 mb-1">Belum ada destinasi</h3>
                    <p class="text-xs text-slate-500">Mulai tambahkan destinasi universitas sekolah Anda.</p>
                </div>

                <div 
                    v-for="item in destinations.data" 
                    :key="'mob-'+item.id"
                    class="bg-white rounded-3xl p-4 border border-slate-200 shadow-sm flex flex-col space-y-3"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="text-[10px] font-black text-teal-700 bg-teal-50 px-2 py-0.5 rounded-lg border border-teal-100">
                                {{ item.unit?.name || 'Yayasan' }}
                            </span>
                            <h4 class="font-extrabold text-base text-slate-900 leading-snug mt-1 flex items-center gap-1.5">
                                <MapPinIcon class="w-4 h-4 text-teal-600 shrink-0" />
                                {{ item.name }}
                            </h4>
                            <p class="text-xs text-slate-500 font-medium mt-0.5 pl-5.5">{{ item.city }}, {{ item.country }}</p>
                        </div>

                        <span :class="typeBadge(item.type)" class="px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider border shrink-0">
                            {{ typeLabel(item.type) }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                        <span class="text-[11px] text-amber-700 font-bold bg-amber-50 px-2.5 py-0.5 rounded-lg border border-amber-100">
                            {{ visitTypeLabel(item.visit_type) }}
                        </span>
                        <div class="flex items-center gap-2">
                            <Link :href="route('public-relations.university-destinations.edit', item.id)" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-extrabold text-xs rounded-xl border border-slate-200 transition-all active:scale-95">
                                Edit
                            </Link>
                            <button @click="confirmDelete(item)" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-extrabold text-xs rounded-xl border border-rose-200 transition-all active:scale-95">
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="destinations.links && destinations.links.length > 3" class="mt-4 flex justify-center">
                <div class="flex gap-1">
                    <Link v-for="(link, k) in destinations.links" :key="k"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-4 py-2 rounded-lg border text-sm"
                        :class="[
                            link.active ? 'bg-namira-teal text-white border-namira-teal' : 'bg-white text-gray-500 hover:bg-gray-50',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showDeleteConfirm" class="fixed inset-0 z-[9999] overflow-y-auto" role="dialog" aria-modal="true">
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal"></div>
                    <div class="flex min-h-full items-center justify-center p-4 text-center">
                        <Transition
                            enter-active-class="transition ease-out duration-300"
                            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                            leave-active-class="transition ease-in duration-200"
                            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        >
                            <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl sm:w-full sm:max-w-md border border-gray-100 p-8">
                                <div class="mx-auto flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-red-50 mb-6 animate-pulse">
                                    <ExclamationTriangleIcon class="h-10 w-10 text-red-600" />
                                </div>
                                <div class="text-center">
                                    <h3 class="text-xl font-bold text-gray-900 mb-2">Hapus Destinasi?</h3>
                                    <p class="text-sm text-gray-500">Anda akan menghapus data <strong>{{ itemToDelete?.name }}</strong> secara permanen.</p>
                                </div>
                                <div class="mt-8 flex justify-center gap-3">
                                    <button type="button" @click="closeModal" :disabled="deleteForm.processing"
                                        class="inline-flex w-full justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:w-auto transition-all">
                                        Batal
                                    </button>
                                    <button type="button" @click="deleteItem" :disabled="deleteForm.processing"
                                        class="inline-flex w-full justify-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-red-500/30 hover:bg-red-700 sm:w-auto transition-all transform hover:scale-105">
                                        <span v-if="deleteForm.processing">Memproses...</span>
                                        <span v-else>Ya, Hapus</span>
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
