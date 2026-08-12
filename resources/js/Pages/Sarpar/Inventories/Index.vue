<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { 
    PlusIcon, PencilSquareIcon, TrashIcon, CubeIcon, MagnifyingGlassIcon,
    FunnelIcon, ExclamationTriangleIcon, EyeIcon, PhotoIcon, ArchiveBoxIcon, ChevronDownIcon,
    BuildingOfficeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    inventories: Object,
    categories: Array,
    rooms: Array,
    classrooms: Array,
    filters: Object,
});

const showModal = ref(false);
const isEditing = ref(false);
const showDeleteConfirm = ref(false);
const itemToDelete = ref(null);
const showFilters = ref(false);
const photoPreview = ref(null);
const showExportMenu = ref(false);

const searchQuery = ref(props.filters.search || '');
const filterCategory = ref(props.filters.category_id || '');
const filterSource = ref(props.filters.funding_source || '');
const filterType = ref(props.filters.item_type || '');
const filterStatus = ref(props.filters.status || '');
const filterCondition = ref(props.filters.condition || '');

const form = useForm({
    id: null,
    category_id: '',
    room_id: '',
    classroom_id: '',
    funding_source: 'YYS',
    item_type: 'asset',
    name: '',
    brand: '',
    model: '',
    year_acquired: new Date().getFullYear(),
    quantity: 1,
    min_stock: '',
    unit_price: '',
    condition: 'baik',
    photo: null,
    notes: '',
});

const deleteForm = useForm({});

const applyFilters = () => {
    router.get(route('sarpar.inventories.index'), {
        search: searchQuery.value || undefined,
        category_id: filterCategory.value || undefined,
        funding_source: filterSource.value || undefined,
        item_type: filterType.value || undefined,
        status: filterStatus.value || undefined,
        condition: filterCondition.value || undefined,
    }, { preserveState: true, replace: true });
};

let searchTimeout = null;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 500);
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.year_acquired = new Date().getFullYear();
    form.condition = 'baik';
    form.funding_source = 'YYS';
    form.item_type = 'asset';
    form.quantity = 1;
    photoPreview.value = null;
    showModal.value = true;
};

const openEditModal = (item) => {
    isEditing.value = true;
    form.id = item.id;
    form.category_id = item.category_id;
    form.room_id = item.room_id || '';
    form.classroom_id = item.classroom_id || '';
    form.item_type = item.item_type || 'asset';
    form.name = item.name;
    form.brand = item.brand || '';
    form.model = item.model || '';
    form.quantity = item.quantity;
    form.min_stock = item.min_stock || '';
    form.unit_price = item.unit_price || '';
    form.condition = item.condition;
    form.notes = item.notes || '';
    photoPreview.value = item.photo ? `/storage/${item.photo}` : null;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    photoPreview.value = null;
};

const handlePhotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    if (isEditing.value) {
        form.post(route('sarpar.inventories.update', form.id), {
            _method: 'PUT',
            forceFormData: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('sarpar.inventories.store'), {
            forceFormData: true,
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteConfirm.value = true;
};

const deleteItem = () => {
    if (!itemToDelete.value) return;
    deleteForm.delete(route('sarpar.inventories.destroy', itemToDelete.value.id), {
        onSuccess: () => { showDeleteConfirm.value = false; itemToDelete.value = null; },
    });
};

const getConditionBadge = (condition) => {
    const badges = {
        'baik': 'bg-green-100 text-green-700',
        'rusak_ringan': 'bg-amber-100 text-amber-700',
        'rusak_berat': 'bg-red-100 text-red-700',
    };
    return badges[condition] || 'bg-gray-100 text-gray-700';
};

const getStatusBadge = (status) => {
    const badges = {
        'tersedia': 'bg-green-100 text-green-700',
        'dipinjam': 'bg-blue-100 text-blue-700',
        'diperbaiki': 'bg-amber-100 text-amber-700',
        'dihapus': 'bg-gray-100 text-gray-500',
    };
    return badges[status] || 'bg-gray-100 text-gray-700';
};

const getSourceBadge = (source) => source === 'BOS' ? 'bg-purple-100 text-purple-700' : 'bg-teal-100 text-teal-700';
const getTypeBadge = (type) => type === 'consumable' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700';
</script>

<template>
    <Head title="Data Inventaris" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                    Data Inventaris
                </h2>
                <p class="text-sm text-gray-500 mt-1">Daftar barang inventaris sekolah</p>
            </div>
        </template>

        <div class="py-4 md:py-6 max-w-7xl mx-auto pb-20 space-y-5 md:space-y-6">

            <!-- 📱 MOBILE PWA VIEW (block md:hidden) -->
            <div class="block md:hidden -mx-4 -mt-4 space-y-4">
                <!-- Header Card Gradient -->
                <div class="bg-gradient-to-br from-[#009688] to-[#0f172a] px-4 pt-5 pb-6 text-white">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-[10px] font-extrabold tracking-widest uppercase text-teal-300">Data Master Sarpar</p>
                            <h1 class="text-xl font-black leading-tight">Data Inventaris</h1>
                        </div>
                        <button
                            @click="openCreateModal"
                            class="px-3 py-2 bg-teal-500 hover:bg-teal-600 text-white font-extrabold text-xs rounded-xl shadow-lg flex items-center gap-1.5 active:scale-95 transition"
                        >
                            <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                            <span>Tambah</span>
                        </button>
                    </div>

                    <!-- Quick Stats Cards (4 Column Grid) -->
                    <div class="grid grid-cols-4 gap-1.5 text-center mt-3">
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-2 py-2">
                            <p class="text-lg font-black text-white leading-none">{{ inventories.total || inventories.data.length }}</p>
                            <p class="text-[8px] text-teal-200 font-bold mt-1 uppercase">Total Item</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-2 py-2">
                            <p class="text-lg font-black text-blue-300 leading-none">
                                {{ inventories.data.filter(i => i.item_type === 'asset').length }}
                            </p>
                            <p class="text-[8px] text-blue-200 font-bold mt-1 uppercase">Aset Tetap</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-2 py-2">
                            <p class="text-lg font-black text-amber-300 leading-none">
                                {{ inventories.data.filter(i => i.item_type === 'consumable').length }}
                            </p>
                            <p class="text-[8px] text-amber-200 font-bold mt-1 uppercase">Habis Pakai</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-2 py-2">
                            <p class="text-lg font-black text-red-300 leading-none">
                                {{ inventories.data.filter(i => i.is_low_stock).length }}
                            </p>
                            <p class="text-[8px] text-red-200 font-bold mt-1 uppercase">Stok Menipis</p>
                        </div>
                    </div>
                </div>

                <!-- Search & Filters Toolbar -->
                <div class="px-4 space-y-2.5">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-3.5 text-slate-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari nama, kode, atau merk..."
                                class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:ring-teal-500 focus:border-teal-500 shadow-sm"
                            />
                        </div>
                        <button
                            @click="showFilters = !showFilters"
                            class="p-2.5 rounded-xl border text-slate-700 bg-white shadow-sm active:scale-95 transition shrink-0"
                            :class="showFilters ? 'border-teal-500 text-teal-700 bg-teal-50/50' : 'border-slate-200'"
                        >
                            <FunnelIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Collapsible Filter Panel -->
                    <div v-if="showFilters" class="bg-white border border-slate-200 rounded-2xl p-3.5 shadow-sm space-y-2.5 text-xs animate-fade-in-down">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block font-bold text-slate-500 text-[10px] uppercase mb-1">Kategori</label>
                                <select v-model="filterCategory" @change="applyFilters" class="w-full px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold">
                                    <option value="">Semua Kategori</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-500 text-[10px] uppercase mb-1">Jenis</label>
                                <select v-model="filterType" @change="applyFilters" class="w-full px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold">
                                    <option value="">Semua Jenis</option>
                                    <option value="asset">Aset Tetap</option>
                                    <option value="consumable">Habis Pakai</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block font-bold text-slate-500 text-[10px] uppercase mb-1">Status</label>
                                <select v-model="filterStatus" @change="applyFilters" class="w-full px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold">
                                    <option value="">Semua Status</option>
                                    <option value="tersedia">Tersedia</option>
                                    <option value="dipinjam">Dipinjam</option>
                                    <option value="diperbaiki">Diperbaiki</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-500 text-[10px] uppercase mb-1">Kondisi</label>
                                <select v-model="filterCondition" @change="applyFilters" class="w-full px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold">
                                    <option value="">Semua Kondisi</option>
                                    <option value="baik">Baik</option>
                                    <option value="rusak_ringan">Rusak Ringan</option>
                                    <option value="rusak_berat">Rusak Berat</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inventories Mobile Touch Cards List -->
                <div class="px-4 space-y-3">
                    <div v-if="inventories.data.length === 0" class="bg-white rounded-2xl p-8 text-center border border-slate-100 shadow-sm">
                        <CubeIcon class="w-10 h-10 mx-auto text-teal-300 mb-2" />
                        <p class="font-extrabold text-sm text-slate-800">Belum ada inventaris</p>
                        <p class="text-xs text-slate-400 mt-1">Data inventaris barang tidak ditemukan.</p>
                    </div>

                    <div
                        v-for="item in inventories.data"
                        :key="item.id"
                        class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm space-y-3 relative overflow-hidden"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-100 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                <img v-if="item.photo" :src="`/storage/${item.photo}`" class="w-full h-full object-cover" />
                                <PhotoIcon v-else class="w-6 h-6 text-slate-300" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-mono text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                                        {{ item.code }}
                                    </span>
                                    <span :class="['px-2 py-0.5 text-[9px] font-black uppercase rounded-lg border', getTypeBadge(item.item_type)]">
                                        {{ item.item_type === 'consumable' ? 'Habis Pakai' : 'Aset Tetap' }}
                                    </span>
                                </div>
                                <h3 class="font-extrabold text-slate-900 text-sm leading-tight mt-1 truncate">{{ item.name }}</h3>
                                <p class="text-[11px] text-slate-500 truncate">{{ item.brand ? item.brand + ' ' : '' }}{{ item.model || '' }}</p>
                            </div>
                        </div>

                        <!-- Details Row: Quantity & Condition & Status -->
                        <div class="grid grid-cols-3 gap-2 bg-slate-50 p-2.5 rounded-xl text-xs border border-slate-100 text-center">
                            <div>
                                <span class="text-[9px] font-extrabold text-slate-400 uppercase block">Stok</span>
                                <span class="font-black text-slate-800 text-xs flex items-center justify-center gap-1">
                                    {{ item.quantity }}
                                    <span v-if="item.is_low_stock" class="px-1 py-0.2 bg-red-100 text-red-600 text-[8px] font-black rounded uppercase">Low</span>
                                </span>
                            </div>
                            <div>
                                <span class="text-[9px] font-extrabold text-slate-400 uppercase block">Lokasi</span>
                                <span class="font-bold text-slate-700 text-[11px] truncate block">{{ item.location_name || '-' }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-extrabold text-slate-400 uppercase block">Status</span>
                                <span :class="['px-1.5 py-0.5 text-[9px] font-black uppercase rounded-lg border inline-block mt-0.5', getStatusBadge(item.status)]">
                                    {{ item.status }}
                                </span>
                            </div>
                        </div>

                        <!-- Touch Action Footer Buttons -->
                        <div class="pt-1 flex items-center justify-end gap-2 border-t border-slate-100">
                            <Link :href="route('sarpar.inventories.show', item.id)" class="px-3 py-1.5 rounded-xl text-xs font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 transition flex items-center gap-1">
                                <EyeIcon class="w-3.5 h-3.5" />
                                <span>Detail</span>
                            </Link>
                            <button @click="openEditModal(item)" class="px-3 py-1.5 rounded-xl text-xs font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 transition flex items-center gap-1">
                                <PencilSquareIcon class="w-3.5 h-3.5" />
                                <span>Edit</span>
                            </button>
                            <button @click="confirmDelete(item)" class="px-3 py-1.5 rounded-xl text-xs font-bold text-red-700 bg-red-50 hover:bg-red-100 transition flex items-center gap-1">
                                <TrashIcon class="w-3.5 h-3.5" />
                                <span>Hapus</span>
                            </button>
                        </div>
                    </div>

                    <!-- Mobile Pagination -->
                    <div v-if="inventories.last_page > 1" class="pt-2 flex justify-center gap-1">
                        <Link v-for="link in inventories.links" :key="link.label" :href="link.url || '#'" :class="['px-3 py-1.5 rounded-xl text-xs font-bold', link.active ? 'bg-teal-600 text-white' : 'bg-white text-slate-600 border border-slate-200']" v-html="link.label" />
                    </div>
                </div>
            </div>
            <!-- END MOBILE VIEW -->

            <!-- 🖥️ DESKTOP VIEW (hidden md:block) -->
            <div class="hidden md:block space-y-6">
                <!-- Toolbar -->
                <div class="flex flex-col md:flex-row items-center gap-4">
                    <div class="relative group flex-1 w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                            <MagnifyingGlassIcon class="w-5 h-5" />
                        </div>
                        <input v-model="searchQuery" type="text" placeholder="Cari nama, kode, atau merk..." class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 shadow-sm h-[46px]" />
                    </div>
                    
                    <button @click="showFilters = !showFilters" class="px-4 py-2.5 bg-white border border-gray-200 rounded-2xl text-gray-600 hover:bg-gray-50 flex items-center gap-2 h-[46px]">
                        <FunnelIcon class="w-5 h-5" />
                        <span>Filter</span>
                    </button>

                    <!-- Export Dropdown -->
                    <div class="relative">
                        <button @click="showExportMenu = !showExportMenu" class="px-4 py-2.5 bg-green-500 text-white rounded-2xl font-bold hover:bg-green-600 flex items-center gap-2 h-[46px] shadow-lg shadow-green-500/30">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span>Export</span>
                            <ChevronDownIcon class="w-4 h-4" />
                        </button>
                        <div v-if="showExportMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-50">
                            <a :href="route('sarpar.inventories.export')" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors" @click="showExportMenu = false">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <div>
                                    <p class="font-bold text-gray-800">Excel (CSV)</p>
                                    <p class="text-xs text-gray-500">Format spreadsheet</p>
                                </div>
                            </a>
                            <a :href="route('sarpar.inventories.export', {format: 'pdf'})" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors border-t border-gray-100" @click="showExportMenu = false">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                <div>
                                    <p class="font-bold text-gray-800">PDF</p>
                                    <p class="text-xs text-gray-500">Untuk cetak</p>
                                </div>
                            </a>
                        </div>
                    </div>

                    <button @click="openCreateModal" class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all flex items-center gap-2 active:scale-95 h-[46px]">
                        <PlusIcon class="w-5 h-5" /><span>Tambah Barang</span>
                    </button>
                </div>

                <!-- Filters Panel -->
                <div v-if="showFilters" class="bg-white/80 backdrop-blur-xl rounded-2xl p-4 border border-white/50 grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div>
                        <label class="text-xs font-bold text-gray-500 mb-1 block">Kategori</label>
                        <select v-model="filterCategory" @change="applyFilters" class="w-full border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-sm h-10">
                            <option value="">Semua</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 mb-1 block">Jenis</label>
                        <select v-model="filterType" @change="applyFilters" class="w-full border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-sm h-10">
                            <option value="">Semua</option>
                            <option value="asset">Aset Tetap</option>
                            <option value="consumable">Habis Pakai</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 mb-1 block">Sumber Dana</label>
                        <select v-model="filterSource" @change="applyFilters" class="w-full border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-sm h-10">
                            <option value="">Semua</option>
                            <option value="BOS">Dana BOS</option>
                            <option value="YYS">Dana Yayasan</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 mb-1 block">Status</label>
                        <select v-model="filterStatus" @change="applyFilters" class="w-full border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-sm h-10">
                            <option value="">Semua</option>
                            <option value="tersedia">Tersedia</option>
                            <option value="dipinjam">Dipinjam</option>
                            <option value="diperbaiki">Diperbaiki</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 mb-1 block">Kondisi</label>
                        <select v-model="filterCondition" @change="applyFilters" class="w-full border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-sm h-10">
                            <option value="">Semua</option>
                            <option value="baik">Baik</option>
                            <option value="rusak_ringan">Rusak Ringan</option>
                            <option value="rusak_berat">Rusak Berat</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-white/50 text-xs uppercase text-gray-500 font-extrabold tracking-wider border-b border-gray-100">
                                    <th class="p-4">Kode</th>
                                    <th class="p-4">Nama Barang</th>
                                    <th class="p-4">Jenis</th>
                                    <th class="p-4">Stok</th>
                                    <th class="p-4">Lokasi</th>
                                    <th class="p-4">Status</th>
                                    <th class="p-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="inventories.data.length === 0">
                                    <td colspan="7" class="p-12 text-center text-gray-400">
                                        <CubeIcon class="w-12 h-12 mx-auto mb-2 opacity-50" />
                                        <p class="font-bold">Belum ada data inventaris</p>
                                    </td>
                                </tr>
                                <tr v-for="item in inventories.data" :key="item.id" class="hover:bg-teal-50/30 transition-colors">
                                    <td class="p-4">
                                        <span class="font-mono text-xs font-bold text-gray-600 bg-gray-100 px-2 py-1 rounded">{{ item.code }}</span>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <img v-if="item.photo" :src="`/storage/${item.photo}`" class="w-10 h-10 rounded-lg object-cover" />
                                            <div v-else class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                                                <PhotoIcon class="w-5 h-5 text-gray-400" />
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-800">{{ item.name }}</div>
                                                <div class="text-xs text-gray-400">{{ item.brand }} {{ item.model }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <span :class="['px-2 py-1 text-xs font-bold rounded-lg', getTypeBadge(item.item_type)]">
                                            {{ item.item_type === 'consumable' ? 'Habis Pakai' : 'Aset Tetap' }}
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-gray-800">{{ item.quantity }}</span>
                                            <span v-if="item.is_low_stock" class="px-2 py-0.5 bg-red-100 text-red-600 text-xs font-bold rounded animate-pulse">Low</span>
                                        </div>
                                    </td>
                                    <td class="p-4 text-sm text-gray-600">{{ item.location_name }}</td>
                                    <td class="p-4"><span :class="['px-2 py-1 text-xs font-bold rounded-lg capitalize', getStatusBadge(item.status)]">{{ item.status }}</span></td>
                                    <td class="p-4">
                                        <div class="flex justify-end gap-2">
                                            <Link :href="route('sarpar.inventories.show', item.id)" class="p-2 rounded-xl text-gray-400 hover:text-namira-teal hover:bg-teal-50"><EyeIcon class="w-4 h-4" /></Link>
                                            <button @click="openEditModal(item)" class="p-2 rounded-xl text-gray-400 hover:text-amber-600 hover:bg-amber-50"><PencilSquareIcon class="w-4 h-4" /></button>
                                            <button @click="confirmDelete(item)" class="p-2 rounded-xl text-gray-400 hover:text-red-600 hover:bg-red-50"><TrashIcon class="w-4 h-4" /></button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="inventories.last_page > 1" class="p-4 border-t border-gray-100 flex justify-center gap-2">
                        <Link v-for="link in inventories.links" :key="link.label" :href="link.url || '#'" :class="['px-3 py-1.5 rounded-xl text-sm', link.active ? 'bg-namira-teal text-white' : 'text-gray-600 hover:bg-gray-100']" v-html="link.label" />
                    </div>
                </div>
            </div>
            <!-- END DESKTOP VIEW -->
        </div>

        <!-- Create/Edit Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-8 border border-gray-100 max-h-[90vh] overflow-y-auto">
                        <h3 class="text-xl font-bold text-gray-900 mb-6">{{ isEditing ? 'Edit Inventaris' : 'Tambah Inventaris' }}</h3>
                        <form @submit.prevent="submit" class="space-y-5">
                            
                            <!-- Photo Upload -->
                            <div>
                                <InputLabel :value="isEditing ? 'Foto (kosongkan jika tidak ingin mengubah)' : 'Foto Barang *'" class="text-sm font-bold text-gray-700" />
                                <div class="mt-2 flex items-center gap-4">
                                    <div v-if="photoPreview" class="relative">
                                        <img :src="photoPreview" class="w-24 h-24 rounded-xl object-cover" />
                                        <button type="button" @click="photoPreview = null; form.photo = null;" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 text-xs font-bold">×</button>
                                    </div>
                                    <div v-else class="w-24 h-24 border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center text-gray-400">
                                        <PhotoIcon class="w-8 h-8" />
                                    </div>
                                    <div>
                                        <label class="cursor-pointer px-4 py-2 bg-namira-teal/10 text-namira-teal font-bold rounded-xl hover:bg-namira-teal/20">
                                            Pilih Foto
                                            <input type="file" accept="image/*" @change="handlePhotoChange" class="hidden" />
                                        </label>
                                        <p class="text-xs text-gray-400 mt-1">Max 2MB, JPG/PNG</p>
                                    </div>
                                </div>
                                <InputError :message="form.errors.photo" class="mt-1" />
                            </div>

                            <!-- Item Type -->
                            <div class="grid grid-cols-2 gap-4">
                                <button type="button" @click="form.item_type = 'asset'" :class="['p-4 rounded-xl border-2 text-left transition-all', form.item_type === 'asset' ? 'border-namira-teal bg-teal-50' : 'border-gray-200 hover:border-gray-300']" :disabled="isEditing">
                                    <div class="text-2xl mb-1">🏢</div>
                                    <div class="font-bold text-gray-800">Aset Tetap</div>
                                    <div class="text-xs text-gray-500">Meja, Komputer, Proyektor</div>
                                </button>
                                <button type="button" @click="form.item_type = 'consumable'" :class="['p-4 rounded-xl border-2 text-left transition-all', form.item_type === 'consumable' ? 'border-orange-500 bg-orange-50' : 'border-gray-200 hover:border-gray-300']" :disabled="isEditing">
                                    <div class="text-2xl mb-1">📦</div>
                                    <div class="font-bold text-gray-800">Habis Pakai</div>
                                    <div class="text-xs text-gray-500">Obat, ATK, Tinta</div>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <InputLabel value="Sumber Dana *" class="text-sm font-bold text-gray-700" />
                                    <select v-model="form.funding_source" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" required :disabled="isEditing">
                                        <option value="YYS">Dana Yayasan</option>
                                        <option value="BOS">Dana BOS</option>
                                    </select>
                                    <InputError :message="form.errors.funding_source" class="mt-1" />
                                </div>
                                <div>
                                    <InputLabel value="Kategori *" class="text-sm font-bold text-gray-700" />
                                    <select v-model="form.category_id" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" required>
                                        <option value="">-- Pilih --</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                    </select>
                                    <InputError :message="form.errors.category_id" class="mt-1" />
                                </div>
                            </div>

                            <div>
                                <InputLabel value="Nama Barang *" class="text-sm font-bold text-gray-700" />
                                <TextInput v-model="form.name" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="Contoh: Paracetamol 500mg" required />
                                <InputError :message="form.errors.name" class="mt-1" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <InputLabel value="Merk" class="text-sm font-bold text-gray-700" />
                                    <TextInput v-model="form.brand" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="Nama merk" />
                                    <InputError :message="form.errors.brand" class="mt-1" />
                                </div>
                                <div>
                                    <InputLabel value="Model/Tipe" class="text-sm font-bold text-gray-700" />
                                    <TextInput v-model="form.model" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="Tipe/varian" />
                                    <InputError :message="form.errors.model" class="mt-1" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div v-if="!isEditing">
                                    <InputLabel value="Tahun Perolehan *" class="text-sm font-bold text-gray-700" />
                                    <TextInput v-model="form.year_acquired" type="number" min="2000" :max="new Date().getFullYear() + 1" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" required />
                                    <InputError :message="form.errors.year_acquired" class="mt-1" />
                                </div>
                                <div>
                                    <InputLabel value="Jumlah/Stok *" class="text-sm font-bold text-gray-700" />
                                    <TextInput v-model="form.quantity" type="number" min="1" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" required />
                                    <InputError :message="form.errors.quantity" class="mt-1" />
                                </div>
                                <div v-if="form.item_type === 'consumable'">
                                    <InputLabel value="Stok Minimum" class="text-sm font-bold text-gray-700" />
                                    <TextInput v-model="form.min_stock" type="number" min="0" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="Alert jika di bawah" />
                                    <InputError :message="form.errors.min_stock" class="mt-1" />
                                </div>
                                <div>
                                    <InputLabel value="Harga Satuan (Rp)" class="text-sm font-bold text-gray-700" />
                                    <TextInput v-model="form.unit_price" type="number" min="0" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="0" />
                                    <InputError :message="form.errors.unit_price" class="mt-1" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <InputLabel value="Kondisi *" class="text-sm font-bold text-gray-700" />
                                    <select v-model="form.condition" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50">
                                        <option value="baik">Baik</option>
                                        <option value="rusak_ringan">Rusak Ringan</option>
                                        <option value="rusak_berat">Rusak Berat</option>
                                    </select>
                                    <InputError :message="form.errors.condition" class="mt-1" />
                                </div>
                                <div>
                                    <InputLabel value="Lokasi" class="text-sm font-bold text-gray-700" />
                                    <select v-model="form.classroom_id" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50">
                                        <option value="">-- Pilih Kelas --</option>
                                        <optgroup label="Kelas">
                                            <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">{{ classroom.name }}</option>
                                        </optgroup>
                                    </select>
                                    <select v-model="form.room_id" class="w-full mt-2 h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50">
                                        <option value="">-- Atau Pilih Ruangan --</option>
                                        <optgroup label="Ruangan Lain">
                                            <option v-for="room in rooms" :key="room.id" :value="room.id">{{ room.name }}</option>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <InputLabel value="Catatan" class="text-sm font-bold text-gray-700" />
                                <textarea v-model="form.notes" rows="2" class="w-full mt-1.5 px-4 py-3 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" placeholder="Keterangan tambahan (opsional)"></textarea>
                                <InputError :message="form.errors.notes" class="mt-1" />
                            </div>

                            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-50">
                                <button type="button" @click="closeModal" class="px-5 py-2.5 text-gray-500 font-bold hover:bg-gray-50 rounded-xl">Batal</button>
                                <PrimaryButton :disabled="form.processing" class="rounded-xl px-6 shadow-lg shadow-namira-teal/30">{{ isEditing ? 'Simpan' : 'Tambah Barang' }}</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Modal -->
        <Teleport to="body">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showDeleteConfirm = false"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 text-center border border-gray-100">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-50 mb-4"><ExclamationTriangleIcon class="h-8 w-8 text-red-500" /></div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Hapus Inventaris?</h3>
                        <p class="text-sm text-gray-500 mb-6">"{{ itemToDelete?.name }}" akan dihapus permanen.</p>
                        <div class="flex justify-center gap-3">
                            <button @click="showDeleteConfirm = false" class="px-5 py-2.5 bg-white border border-gray-200 rounded-xl font-bold text-gray-600 hover:bg-gray-50">Batal</button>
                            <button @click="deleteItem" class="px-5 py-2.5 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 shadow-lg shadow-red-500/30">Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
