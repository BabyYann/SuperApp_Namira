<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { 
    MagnifyingGlassIcon, ChevronDownIcon, ArrowPathIcon, PlusIcon,
    ExclamationTriangleIcon, UserIcon, EnvelopeIcon, PencilSquareIcon, TrashIcon,
    KeyIcon, LockClosedIcon, XMarkIcon, BuildingOfficeIcon
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import Pagination from '@/Components/Pagination.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    users: Object,
    units: Array,
    roles: Array,
    filters: Object,
});

// Filter State
const searchQuery = ref(props.filters?.search || '');
const roleFilter = ref(props.filters?.role || '');
const unitFilter = ref(props.filters?.unit_id || '');

// Debounced Search & Filter
const performSearch = debounce(() => {
    router.get(route('yayasan.users.index'), { 
        search: searchQuery.value,
        role: roleFilter.value,
        unit_id: unitFilter.value
    }, { 
        preserveState: true, 
        preserveScroll: true,
        replace: true 
    });
}, 300);

// Watchers for user input
watch(searchQuery, performSearch);
watch(roleFilter, performSearch);
watch(unitFilter, performSearch);

// Sync filters when props change
watch(() => props.filters, (newFilters) => {
    if (newFilters) {
        if (newFilters.search !== undefined) searchQuery.value = newFilters.search || '';
        if (newFilters.role !== undefined) roleFilter.value = newFilters.role || '';
        if (newFilters.unit_id !== undefined) unitFilter.value = newFilters.unit_id || '';
    }
}, { deep: true });

// Reset Filters
const resetFilters = () => {
    searchQuery.value = '';
    roleFilter.value = '';
    unitFilter.value = '';
    performSearch();
};

// Role Badges & Formatter
const getRoleBadgeStyle = (role) => {
    const map = {
        'super_admin_yayasan': 'bg-purple-100 text-purple-800 border-purple-200',
        'admin_yayasan': 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'pembina_yayasan': 'bg-violet-100 text-violet-800 border-violet-200',
        'pengawas_yayasan': 'bg-fuchsia-100 text-fuchsia-800 border-fuchsia-200',
        'staff_yayasan': 'bg-slate-100 text-slate-800 border-slate-200',
        'humas_yayasan': 'bg-pink-100 text-pink-800 border-pink-200',
        'kepala_sekolah': 'bg-teal-100 text-teal-800 border-teal-200',
        'admin_unit': 'bg-blue-100 text-blue-800 border-blue-200',
        'koordinator_kurikulum': 'bg-sky-100 text-sky-800 border-sky-200',
        'koordinator_sarpar': 'bg-cyan-100 text-cyan-800 border-cyan-200',
        'staff_unit': 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'teacher': 'bg-amber-100 text-amber-800 border-amber-200',
        'siswa': 'bg-green-100 text-green-800 border-green-200',
        'orang_tua': 'bg-orange-100 text-orange-800 border-orange-200',
    };
    return map[role] || 'bg-gray-100 text-gray-700 border-gray-200';
};

const formatRoleName = (role) => {
    const names = {
        'super_admin_yayasan': 'Super Admin',
        'admin_yayasan': 'Admin Yayasan',
        'pembina_yayasan': 'Pembina Yayasan',
        'pengawas_yayasan': 'Pengawas Yayasan',
        'staff_yayasan': 'Staf Yayasan',
        'humas_yayasan': 'Humas Yayasan',
        'kepala_sekolah': 'Kepala Sekolah',
        'admin_unit': 'Admin Unit',
        'koordinator_kurikulum': 'Koordinator Kurikulum',
        'koordinator_sarpar': 'Koordinator Sarpras',
        'staff_unit': 'Staf Unit',
        'teacher': 'Guru',
        'siswa': 'Siswa',
        'orang_tua': 'Orang Tua / Wali',
    };
    return names[role] || role.replace(/_/g, ' ').toUpperCase();
};

// Modal State Delete
const showDeleteConfirm = ref(false);
const userIdToDelete = ref(null);
const userNameToDelete = ref('');
const deleteForm = useForm({});

const confirmDelete = (user) => {
    userIdToDelete.value = user.id;
    userNameToDelete.value = user.name;
    showDeleteConfirm.value = true;
};

const closeDeleteModal = () => {
    showDeleteConfirm.value = false;
    setTimeout(() => {
        userIdToDelete.value = null;
        userNameToDelete.value = '';
    }, 300);
};

const deleteUser = () => {
    if (!userIdToDelete.value) return;
    deleteForm.delete(route('yayasan.users.destroy', userIdToDelete.value), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
        onError: () => closeDeleteModal()
    });
};

// Modal State Reset Password
const showResetPasswordModal = ref(false);
const selectedUserForReset = ref(null);
const resetForm = useForm({
    reset_mode: 'manual', // 'manual' or 'email'
    password: '',
    password_confirmation: '',
});

const openResetPasswordModal = (user) => {
    selectedUserForReset.value = user;
    resetForm.reset();
    resetForm.reset_mode = 'manual';
    showResetPasswordModal.value = true;
};

const closeResetPasswordModal = () => {
    showResetPasswordModal.value = false;
    setTimeout(() => {
        selectedUserForReset.value = null;
        resetForm.reset();
    }, 300);
};

const submitResetPassword = () => {
    if (!selectedUserForReset.value) return;
    resetForm.post(route('yayasan.users.reset-password', selectedUserForReset.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeResetPasswordModal();
        }
    });
};
</script>

<template>
    <Head title="Manajemen Pengguna" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Manajemen Pengguna
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Sistem kontrol akun & hak akses terpusat Sekolah Namira.</p>
                </div>
            </div>
        </template>

        <div class="py-4 md:py-6 max-w-7xl mx-auto space-y-5 md:space-y-6 pb-20">

            <!-- 📱 MOBILE PWA VIEW (block md:hidden) -->
            <div class="block md:hidden -mx-4 -mt-4 space-y-4">
                <!-- Header Card Gradient -->
                <div class="bg-gradient-to-br from-[#009688] to-[#0f172a] px-4 pt-5 pb-6 text-white">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-[10px] font-extrabold tracking-widest uppercase text-teal-300">Hak Akses Sistem</p>
                            <h1 class="text-xl font-black leading-tight">Manajemen User</h1>
                        </div>
                        <Link
                            :href="route('yayasan.users.create')"
                            class="px-3 py-2 bg-teal-500 hover:bg-teal-600 text-white font-extrabold text-xs rounded-xl shadow-lg flex items-center gap-1.5 active:scale-95 transition"
                        >
                            <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                            <span>Tambah</span>
                        </Link>
                    </div>

                    <!-- Quick Stats Cards -->
                    <div class="grid grid-cols-2 gap-2 text-center mt-3">
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-3 py-2">
                            <p class="text-xl font-black text-white leading-none">{{ users.total || users.data.length }}</p>
                            <p class="text-[9px] text-teal-200 font-bold mt-1 uppercase">Total Akun Terdaftar</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-3 py-2">
                            <p class="text-xl font-black text-teal-300 leading-none">
                                {{ users.data.filter(u => u.is_active).length }}
                            </p>
                            <p class="text-[9px] text-teal-200 font-bold mt-1 uppercase">Akun Verifikasi</p>
                        </div>
                    </div>
                </div>

                <!-- Search & Filters Toolbar Mobile -->
                <div class="px-4 space-y-2.5">
                    <div class="relative">
                        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-3.5 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama, email, NIP, NIS..."
                            class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:ring-teal-500 focus:border-teal-500 shadow-sm"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <select v-model="roleFilter" class="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-2 text-xs font-semibold text-slate-700 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">Semua Role</option>
                            <option v-for="r in roles" :key="r" :value="r">{{ formatRoleName(r) }}</option>
                        </select>
                        <select v-model="unitFilter" class="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-2 text-xs font-semibold text-slate-700 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">Semua Unit</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Mobile User Cards List -->
                <div class="px-4 space-y-3">
                    <div v-if="users.data.length === 0" class="bg-white rounded-2xl p-6 text-center text-slate-400 border border-slate-200">
                        <UserIcon class="w-12 h-12 mx-auto mb-2 opacity-50" />
                        <p class="font-bold text-sm">Tidak ada pengguna ditemukan</p>
                    </div>

                    <div v-for="user in users.data" :key="user.id" class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm space-y-3">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-slate-800 text-white font-black text-base flex items-center justify-center shadow-md">
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm leading-snug">{{ user.name }}</h4>
                                    <p class="text-[11px] text-slate-500 flex items-center gap-1">
                                        <EnvelopeIcon class="w-3 h-3" />
                                        {{ user.email }}
                                    </p>
                                </div>
                            </div>
                            <span :class="['px-2 py-0.5 text-[9px] font-extrabold rounded-md uppercase tracking-wider', user.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500']">
                                {{ user.is_active ? 'Aktif' : 'Unverified' }}
                            </span>
                        </div>

                        <!-- Roles Badges -->
                        <div class="flex flex-wrap gap-1 pt-1 border-t border-slate-100">
                            <span
                                v-for="role in user.roles"
                                :key="role"
                                :class="['px-2 py-0.5 rounded-full text-[9px] font-bold border', getRoleBadgeStyle(role)]"
                            >
                                {{ formatRoleName(role) }}
                            </span>
                            <span v-if="user.units.length > 0" class="text-[9px] text-slate-400 font-bold self-center ml-auto">
                                📍 {{ user.units.join(', ') }}
                            </span>
                            <span v-else class="text-[9px] text-teal-600 font-bold self-center ml-auto">
                                🌐 Global Yayasan
                            </span>
                        </div>

                        <!-- Actions Mobile -->
                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button @click="openResetPasswordModal(user)" class="px-2.5 py-1.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold flex items-center gap-1">
                                <KeyIcon class="w-3.5 h-3.5" />
                                <span>Reset Pass</span>
                            </button>
                            <Link :href="route('yayasan.users.edit', user.id)" class="px-2.5 py-1.5 bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold flex items-center gap-1">
                                <PencilSquareIcon class="w-3.5 h-3.5" />
                                <span>Edit</span>
                            </Link>
                            <button @click="confirmDelete(user)" class="p-1.5 text-rose-600 bg-rose-50 border border-rose-200 rounded-lg">
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END MOBILE VIEW -->

            <!-- 🖥️ DESKTOP VIEW (hidden md:block) -->
            <div class="hidden md:block space-y-6">
                <!-- Toolbar: Filters & Actions -->
                <div class="flex flex-col md:flex-row items-center gap-4">
                    <!-- Search Bar -->
                    <div class="relative group flex-1 w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 group-focus-within:text-namira-teal transition-colors">
                            <MagnifyingGlassIcon class="w-5 h-5" />
                        </div>
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Cari pengguna berdasarkan nama, email, NIP, atau NIS..." 
                            class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                        >
                    </div>

                    <!-- Role Filter -->
                    <div class="relative w-full md:w-56">
                        <select v-model="roleFilter" class="appearance-none w-full pl-4 pr-10 py-2.5 bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md cursor-pointer h-[46px]">
                            <option value="">Semua Role</option>
                            <option v-for="role in roles" :key="role" :value="role">{{ formatRoleName(role) }}</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-gray-500">
                            <ChevronDownIcon class="h-4 w-4" />
                        </div>
                    </div>

                    <!-- Unit Filter -->
                    <div class="relative w-full md:w-52">
                        <select v-model="unitFilter" class="appearance-none w-full pl-4 pr-10 py-2.5 bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md cursor-pointer h-[46px]">
                            <option value="">Semua Unit</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-gray-500">
                            <ChevronDownIcon class="h-4 w-4" />
                        </div>
                    </div>
                    
                    <!-- Reset Button -->
                    <button 
                        v-if="searchQuery || roleFilter || unitFilter"
                        @click="resetFilters"
                        class="px-4 py-2.5 bg-white/50 text-gray-500 hover:text-gray-700 rounded-2xl border border-white/50 hover:bg-white hover:shadow-sm transition-all h-[46px] flex items-center justify-center"
                        title="Reset Filter"
                    >
                        <ArrowPathIcon class="w-5 h-5" />
                    </button>

                    <!-- Add User Button -->
                    <Link :href="route('yayasan.users.create')" class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 active:scale-95 h-[46px] whitespace-nowrap">
                        <PlusIcon class="w-5 h-5" />
                        <span>Tambah User</span>
                    </Link>
                </div>

                <!-- Data Card Desktop -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden flex flex-col min-h-[500px]">
                    <div class="overflow-x-auto flex-1">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-white/50 border-b border-gray-100 text-xs uppercase text-gray-500 font-extrabold tracking-wider">
                                    <th class="p-6 sticky left-0 bg-white/50 backdrop-blur-sm z-10">Profil Pengguna</th>
                                    <th class="p-6">Role & Peran</th>
                                    <th class="p-6">Unit Penugasan</th>
                                    <th class="p-6 text-right sticky right-0 bg-white/50 backdrop-blur-sm z-10">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="users.data.length === 0" class="bg-transparent">
                                    <td colspan="4" class="p-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-400">
                                            <UserIcon class="w-16 h-16 mb-4 opacity-50" />
                                            <h3 class="text-lg font-bold text-gray-900">Tidak ada user ditemukan.</h3>
                                            <p class="text-sm">Coba ubah filter atau kata kunci pencarian.</p>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-teal-50/30 transition-colors group">
                                    <td class="p-6 sticky left-0 bg-white/80 group-hover:bg-teal-50/30 transition-colors z-10 backdrop-blur-sm">
                                        <div class="flex items-center gap-4">
                                            <div class="relative">
                                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-namira-teal to-teal-600 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-namira-teal/20">
                                                    {{ user.name.charAt(0).toUpperCase() }}
                                                </div>
                                                <div 
                                                    class="absolute -bottom-1 -right-1 w-4 h-4 border-2 border-white rounded-full"
                                                    :class="user.is_active ? 'bg-green-500' : 'bg-gray-300'"
                                                    :title="user.is_active ? 'Aktif' : 'Belum Verifikasi'"
                                                ></div>
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-900 text-base mb-0.5">{{ user.name }}</div>
                                                <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                                    <EnvelopeIcon class="w-3.5 h-3.5 opacity-70" />
                                                    {{ user.email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-6 align-middle">
                                        <div class="flex flex-wrap gap-1.5">
                                             <span 
                                                v-for="role in user.roles" 
                                                :key="role" 
                                                :class="['px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide border shadow-sm', getRoleBadgeStyle(role)]"
                                             >
                                                {{ formatRoleName(role) }}
                                             </span>
                                        </div>
                                    </td>
                                    <td class="p-6 align-middle">
                                        <template v-if="user.units.length > 0">
                                            <div class="flex flex-wrap gap-2">
                                                 <span v-for="unit in user.units" :key="unit" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-white text-gray-600 text-xs font-bold border border-gray-200 shadow-sm">
                                                    <BuildingOfficeIcon class="mr-1.5 h-3 w-3 text-namira-teal" />
                                                    {{ unit }}
                                                 </span>
                                            </div>
                                        </template>
                                        <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-teal-50 text-teal-700 text-xs font-bold border border-teal-200">
                                            🌐 Akses Global Yayasan
                                        </span>
                                    </td>
                                    <td class="p-6 text-right sticky right-0 bg-white/80 group-hover:bg-teal-50/30 transition-colors z-10 align-middle backdrop-blur-sm">
                                        <div class="flex justify-end gap-1.5 opacity-80 group-hover:opacity-100 transition-opacity">
                                            <button
                                                type="button"
                                                @click="openResetPasswordModal(user)"
                                                class="p-2.5 text-amber-600 hover:bg-amber-50 rounded-xl transition-all duration-200 border border-transparent hover:border-amber-200 hover:shadow-sm"
                                                title="Reset Password Pengguna"
                                            >
                                                <KeyIcon class="w-5 h-5" />
                                            </button>
                                            <Link 
                                                :href="route('yayasan.users.edit', user.id)" 
                                                class="p-2.5 text-gray-400 hover:text-namira-teal hover:bg-teal-50 rounded-xl transition-all duration-200 border border-transparent hover:border-teal-100 hover:shadow-sm" 
                                                title="Edit User"
                                            >
                                                <PencilSquareIcon class="w-5 h-5" />
                                            </Link>
                                            <button 
                                                type="button" 
                                                @click="confirmDelete(user)" 
                                                class="p-2.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all duration-200 border border-transparent hover:border-red-100 hover:shadow-sm cursor-pointer" 
                                                title="Hapus User"
                                            >
                                                <TrashIcon class="w-5 h-5 pointer-events-none" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="p-6 border-t border-gray-100 bg-white/50 backdrop-blur-sm flex justify-center">
                        <Pagination :links="users.links" />
                    </div>
                </div>
            </div>
            <!-- END DESKTOP VIEW -->
        </div>

        <!-- 🔑 Reset Password Modal -->
        <Teleport to="body">
            <div v-if="showResetPasswordModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeResetPasswordModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 border border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center font-bold">
                                    <KeyIcon class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">Reset Password Pengguna</h3>
                                    <p class="text-xs text-gray-500">{{ selectedUserForReset?.name }} ({{ selectedUserForReset?.email }})</p>
                                </div>
                            </div>
                            <button @click="closeResetPasswordModal" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="w-6 h-6" /></button>
                        </div>

                        <form @submit.prevent="submitResetPassword" class="space-y-4">
                            <div>
                                <InputLabel value="Metode Reset Password *" class="text-xs font-bold text-gray-700" />
                                <div class="grid grid-cols-2 gap-2 mt-1">
                                    <button
                                        type="button"
                                        @click="resetForm.reset_mode = 'manual'"
                                        :class="['py-2.5 px-3 rounded-xl border text-xs font-bold transition-all text-center', resetForm.reset_mode === 'manual' ? 'bg-amber-50 border-amber-400 text-amber-800' : 'bg-slate-50 border-slate-200 text-slate-600']"
                                    >
                                        🔑 Set Password Baru (Manual)
                                    </button>
                                    <button
                                        type="button"
                                        @click="resetForm.reset_mode = 'email'"
                                        :class="['py-2.5 px-3 rounded-xl border text-xs font-bold transition-all text-center', resetForm.reset_mode === 'email' ? 'bg-amber-50 border-amber-400 text-amber-800' : 'bg-slate-50 border-slate-200 text-slate-600']"
                                    >
                                        📧 Kirim Email Password Acak
                                    </button>
                                </div>
                            </div>

                            <!-- Manual Password Fields -->
                            <div v-if="resetForm.reset_mode === 'manual'" class="space-y-3 pt-2">
                                <div>
                                    <InputLabel value="Password Baru (min 8 karakter) *" class="text-xs font-bold text-gray-700" />
                                    <input
                                        v-model="resetForm.password"
                                        type="password"
                                        required
                                        placeholder="Masukkan password baru..."
                                        class="w-full mt-1 h-11 px-3 border-gray-200 rounded-xl text-sm focus:ring-amber-500 focus:border-amber-500"
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Konfirmasi Password Baru *" class="text-xs font-bold text-gray-700" />
                                    <input
                                        v-model="resetForm.password_confirmation"
                                        type="password"
                                        required
                                        placeholder="Ulangi password baru..."
                                        class="w-full mt-1 h-11 px-3 border-gray-200 rounded-xl text-sm focus:ring-amber-500 focus:border-amber-500"
                                    />
                                </div>
                            </div>

                            <div v-else class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800 leading-relaxed">
                                System akan membuat password acak baru secara otomatis dan mengirimkannya langsung ke alamat email <strong>{{ selectedUserForReset?.email }}</strong>.
                            </div>

                            <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                                <button type="button" @click="closeResetPasswordModal" class="px-4 py-2 text-slate-600 font-bold hover:bg-slate-100 rounded-xl text-sm">Batal</button>
                                <button type="submit" :disabled="resetForm.processing" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-sm shadow-lg shadow-amber-500/30">
                                    {{ resetForm.processing ? 'Mereset...' : 'Simpan & Reset Password' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Modal -->
        <Teleport to="body">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeDeleteModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-sm w-full p-8 text-center border border-gray-100">
                         <div class="mx-auto flex h-20 w-20 flex-shrink-0 items-center justify-center rounded-full bg-red-50 mb-6 animate-pulse">
                            <ExclamationTriangleIcon class="h-10 w-10 text-red-600" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Hapus Pengguna?</h3>
                        <p class="text-gray-500 mb-8 leading-relaxed">
                            Anda akan menghapus user: <br>
                            <span class="font-bold text-gray-900 text-lg block my-1">"{{ userNameToDelete }}"</span>
                            <span class="text-red-600 font-medium bg-red-50 px-2 py-0.5 rounded inline-block text-xs">Data yang dihapus tidak dapat dikembalikan.</span>
                        </p>
                        <div class="flex justify-center gap-4">
                            <button @click="closeDeleteModal" class="px-6 py-2.5 bg-white border border-gray-200 rounded-xl font-bold text-gray-600 hover:bg-gray-50 transition-colors">Batal</button>
                            <button @click="deleteUser" :disabled="deleteForm.processing" class="px-6 py-2.5 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 shadow-lg shadow-red-500/30 transition-all hover:scale-105 flex items-center gap-2">
                                <span v-if="deleteForm.processing" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                                {{ deleteForm.processing ? 'Memproses...' : 'Hapus Permanen' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
