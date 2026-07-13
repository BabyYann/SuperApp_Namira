<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    MagnifyingGlassIcon, PlusIcon, BuildingLibraryIcon, PencilSquareIcon, TrashIcon, ExclamationTriangleIcon, EyeIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    units: Array,
});

// Search Logic
const searchQuery = ref('');
const filteredUnits = computed(() => {
    if (!searchQuery.value) return props.units;
    const lower = searchQuery.value.toLowerCase();
    return props.units.filter(unit => 
        unit.name.toLowerCase().includes(lower) || 
        (unit.code && unit.code.toLowerCase().includes(lower))
    );
});

// Delete Logic
const showDeleteConfirm = ref(false);
const unitToDelete = ref(null);
const deleteForm = useForm({});

const confirmDelete = (unit) => {
    unitToDelete.value = unit;
    showDeleteConfirm.value = true;
};

const closeModal = () => {
    showDeleteConfirm.value = false;
    setTimeout(() => {
        unitToDelete.value = null;
    }, 300);
};

const deleteUnit = () => {
    if (!unitToDelete.value) return;
    deleteForm.delete(route('yayasan.units.destroy', unitToDelete.value.id), {
        onSuccess: () => closeModal(),
        onError: () => {
            closeModal();
            alert('Gagal menghapus unit. Pastikan unit tidak memiliki data terkait.');
        }
    });
};
</script>

<template>
    <Head title="Satuan Pendidikan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4">
                <!-- Top Row: Title & Description -->
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Satuan Pendidikan
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Daftar unit sekolah dibawah naungan yayasan.</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6">
            <!-- Toolbar: Search & Actions -->
            <div class="flex flex-col md:flex-row items-center gap-4">
                <!-- Search Bar -->
                <div class="relative group flex-1 w-full">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 group-focus-within:text-namira-teal transition-colors">
                        <MagnifyingGlassIcon class="w-5 h-5" />
                    </div>
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Cari Unit / Kode..." 
                        class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                    >
                </div>

                <!-- Add Button -->
                <Link :href="route('yayasan.units.create')" class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 active:scale-95 h-[46px] whitespace-nowrap">
                    <PlusIcon class="w-5 h-5" />
                    <span>Tambah Unit</span>
                </Link>
            </div>

            <!-- Data Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/50 border-b border-gray-100 text-xs uppercase text-gray-500 font-extrabold tracking-wider">
                                <th class="p-6 sticky left-0 bg-white/50 backdrop-blur-sm z-10">Nama Unit</th>
                                <th class="p-6">Kode Unit</th>
                                <th class="p-6">Kategori & Level</th>
                                <th class="p-6 text-right sticky right-0 bg-white/50 backdrop-blur-sm z-10">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="filteredUnits.length === 0">
                                <td colspan="4" class="p-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                            <BuildingLibraryIcon class="w-10 h-10 text-gray-400" />
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900">Belum ada unit.</h3>
                                        <p class="text-sm">Silakan tambahkan unit pendidikan.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="unit in filteredUnits" :key="unit.id" class="hover:bg-teal-50/30 transition-colors group">
                                <td class="p-6 sticky left-0 bg-white/0 group-hover:bg-teal-50/0 transition-colors z-10">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm border border-gray-100 group-hover:scale-110 transition-transform duration-300 overflow-hidden bg-white">
                                            <img v-if="unit.logo" :src="`/storage/${unit.logo}`" class="w-full h-full object-cover" />
                                            <div v-else class="w-full h-full bg-gradient-to-br from-namira-teal/10 to-blue-50 text-namira-teal flex items-center justify-center font-bold text-xl">
                                                {{ unit.code ? unit.code.substring(0,2).toUpperCase() : 'UN' }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-800 text-lg group-hover:text-namira-teal transition-colors">{{ unit.name }}</div>
                                            <div class="text-xs text-gray-500">Official Unit</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-6 font-mono text-sm text-gray-600">
                                    <span class="bg-gray-100 px-2 py-1 rounded-lg border border-gray-200 font-bold">
                                        {{ unit.code || '-' }}
                                    </span>
                                </td>
                                <td class="p-6">
                                    <div class="flex flex-wrap gap-2">
                                        <span class="px-3 py-1 bg-purple-50 text-purple-700 rounded-xl text-xs font-bold uppercase tracking-wide border border-purple-100 shadow-sm">
                                            {{ unit.category || 'School' }}
                                        </span>
                                        <span class="px-3 py-1 bg-amber-50 text-amber-700 rounded-xl text-xs font-bold uppercase tracking-wide border border-amber-100 shadow-sm">
                                            {{ unit.level || 'General' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-6 text-right sticky right-0 bg-white/0 group-hover:bg-teal-50/0 transition-colors z-10">
                                    <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
                                        <Link :href="route('yayasan.units.show', unit.id)" class="p-2.5 text-gray-400 hover:text-blue-500 hover:bg-blue-50 rounded-xl transition-all duration-200 border border-transparent hover:border-blue-100" title="Lihat Profil">
                                            <EyeIcon class="w-5 h-5" />
                                        </Link>
                                        <Link :href="route('yayasan.units.edit', unit.id)" class="p-2.5 text-gray-400 hover:text-namira-teal hover:bg-teal-50 rounded-xl transition-all duration-200 border border-transparent hover:border-teal-100" title="Edit Unit">
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </Link>
                                        <button 
                                            @click="confirmDelete(unit)" 
                                            class="p-2.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all duration-200 border border-transparent hover:border-red-100 cursor-pointer" 
                                            title="Hapus Unit"
                                        >
                                            <TrashIcon class="w-5 h-5 pointer-events-none" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
                <div v-if="showDeleteConfirm" class="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

                    <div class="flex min-h-full items-center justify-center p-4 text-center">
                        <Transition
                            enter-active-class="transition ease-out duration-300"
                            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                            leave-active-class="transition ease-in duration-200"
                            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        >
                            <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:w-full sm:max-w-md border border-gray-100 p-8">
                                <div class="mx-auto flex h-20 w-20 flex-shrink-0 items-center justify-center rounded-full bg-red-50 mb-6 animate-pulse">
                                    <ExclamationTriangleIcon class="h-10 w-10 text-red-600" />
                                </div>
                                <div class="text-center">
                                    <h3 class="text-xl font-bold leading-6 text-gray-900 mb-2" id="modal-title">Hapus Unit Pendidikan?</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            Anda akan menghapus unit: <br>
                                            <span class="font-bold text-gray-900 text-lg block my-2">"{{ unitToDelete?.name }}"</span>
                                            <span class="text-red-600 font-bold bg-red-50 px-3 py-1 rounded-lg inline-block text-xs">PERINGATAN: Data kelas, siswa, dan guru terkait mungkin akan terpengaruh.</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-8 flex justify-center gap-3">
                                    <button 
                                        type="button" 
                                        class="inline-flex w-full justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:w-auto transition-all"
                                        @click="closeModal"
                                        :disabled="deleteForm.processing"
                                    >
                                        Batal
                                    </button>
                                    <button 
                                        type="button" 
                                        class="inline-flex w-full justify-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-red-500/30 hover:bg-red-700 sm:w-auto transition-all transform hover:scale-105"
                                        @click="deleteUnit"
                                        :disabled="deleteForm.processing"
                                    >
                                        <span v-if="deleteForm.processing">Memproses...</span>
                                        <span v-else>Ya, Hapus Unit</span>
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
