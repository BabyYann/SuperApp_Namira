<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { 
    MagnifyingGlassIcon, ChevronDownIcon, ArrowPathIcon, PlusIcon,
    ExclamationTriangleIcon, UserIcon, EnvelopeIcon, PencilSquareIcon, TrashIcon
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    users: Object, // Changed from Array to Object for Pagination
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

// Watchers
watch(searchQuery, performSearch);
watch(roleFilter, performSearch);
watch(unitFilter, performSearch);

// Reset Filters
const resetFilters = () => {
    searchQuery.value = '';
    roleFilter.value = '';
    unitFilter.value = '';
    performSearch();
};

// Modal State
const showDeleteConfirm = ref(false);
const userIdToDelete = ref(null);
const userNameToDelete = ref('');

// Form for Delete Action (Inertia Way)
const deleteForm = useForm({});

// Open Modal
const confirmDelete = (user) => {
    userIdToDelete.value = user.id;
    userNameToDelete.value = user.name;
    showDeleteConfirm.value = true;
};

// Close Modal
const closeModal = () => {
    showDeleteConfirm.value = false;
    setTimeout(() => {
        userIdToDelete.value = null;
        userNameToDelete.value = '';
    }, 300);
};

// Execute Delete
const deleteUser = () => {
    if (!userIdToDelete.value) return;
    
    deleteForm.delete(route('yayasan.users.destroy', userIdToDelete.value), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
        onError: () => {
            closeModal();
            alert('Gagal menghapus user. Silakan coba lagi.');
        }
    });
};
</script>

<template>
    <Head title="Manajemen User" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4">
                <!-- Top Row: Title & Description -->
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Manajemen Pengguna
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Sistem kontrol akses terpusat untuk seluruh unit.</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6">
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
                        placeholder="Cari user..." 
                        class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                    >
                </div>

                <!-- Role Filter -->
                <div class="relative w-full md:w-48">
                    <select v-model="roleFilter" class="appearance-none w-full pl-4 pr-10 py-2.5 bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md cursor-pointer h-[46px]">
                        <option value="">Semua Role</option>
                        <option v-for="role in roles" :key="role" :value="role">{{ role.replace(/_/g, ' ').toUpperCase() }}</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-gray-500">
                        <ChevronDownIcon class="h-4 w-4" />
                    </div>
                </div>

                <!-- Unit Filter -->
                <div class="relative w-full md:w-48">
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

                <!-- Add User Button (Far Right) -->
                <Link :href="route('yayasan.users.create')" class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 active:scale-95 h-[46px] whitespace-nowrap">
                    <PlusIcon class="w-5 h-5" />
                    <span>Tambah User</span>
                </Link>
            </div>
            <!-- Data Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden flex flex-col min-h-[500px]">
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/50 border-b border-gray-100 text-xs uppercase text-gray-500 font-extrabold tracking-wider">
                                <th class="p-6 sticky left-0 bg-white/50 backdrop-blur-sm z-10">Profil Pengguna</th>
                                <th class="p-6">Role & Status</th>
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
                                            <!-- Real Status Indicator -->
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
                                    <div class="flex flex-wrap gap-2">
                                         <span v-for="role in user.roles" :key="role" 
                                            class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide border shadow-sm"
                                            :class="{
                                                'bg-blue-50 text-blue-700 border-blue-100': role === 'guru',
                                                'bg-indigo-50 text-indigo-700 border-indigo-100': role === 'yayasan' || role === 'super_admin_yayasan',
                                                'bg-emerald-50 text-emerald-700 border-emerald-100': role === 'siswa',
                                                'bg-orange-50 text-orange-700 border-orange-100': role === 'staf',
                                                'bg-gray-100 text-gray-600 border-gray-200': !['guru', 'yayasan', 'super_admin_yayasan', 'siswa', 'staf'].includes(role)
                                            }"
                                         >
                                            {{ role.replace(/_/g, ' ') }}
                                         </span>
                                    </div>
                                </td>
                                <td class="p-6 align-middle">
                                    <template v-if="user.units.length > 0">
                                        <div class="flex flex-wrap gap-2">
                                             <span v-for="unit in user.units" :key="unit" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-white text-gray-600 text-xs font-bold border border-gray-200 shadow-sm">
                                                <svg class="mr-1.5 h-2 w-2 text-namira-teal" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                                {{ unit }}
                                             </span>
                                        </div>
                                    </template>
                                    <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-slate-500 text-xs font-bold border border-slate-200">
                                        Global Access
                                    </span>
                                </td>
                                <td class="p-6 text-right sticky right-0 bg-white/80 group-hover:bg-teal-50/30 transition-colors z-10 align-middle backdrop-blur-sm">
                                    <div class="flex justify-end gap-2 opacity-80 group-hover:opacity-100 transition-opacity">
                                        <Link :href="route('yayasan.users.edit', user.id)" class="p-2.5 text-gray-400 hover:text-namira-teal hover:bg-teal-50 rounded-xl transition-all duration-200 border border-transparent hover:border-teal-100 hover:shadow-sm" title="Edit User">
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

        <!-- Custom Premium Delete Modal -->
        <Teleport to="body">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-sm w-full p-8 text-center border border-gray-100 transform transition-all scale-100">
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
                            <button @click="closeModal" class="px-6 py-2.5 bg-white border border-gray-200 rounded-xl font-bold text-gray-600 hover:bg-gray-50 transition-colors">Batal</button>
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
