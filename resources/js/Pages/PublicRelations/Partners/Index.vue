<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    MagnifyingGlassIcon, PlusIcon, BuildingOffice2Icon, PencilSquareIcon, TrashIcon, ExclamationTriangleIcon, LinkIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    partners: Object,
    filters: Object,
});

const searchForm = useForm({
    search: props.filters.search || '',
});

const search = () => {
    searchForm.get(route('public-relations.partners.index'), {
        preserveState: true,
        preserveScroll: true,
    });
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
    setTimeout(() => {
        itemToDelete.value = null;
    }, 300);
};

const deleteItem = () => {
    if (!itemToDelete.value) return;
    deleteForm.delete(route('public-relations.partners.destroy', itemToDelete.value.id), {
        onSuccess: () => closeModal(),
        onError: () => {
            closeModal();
            alert('Gagal menghapus mitra.');
        }
    });
};
</script>

<template>
    <Head title="Manajemen Mitra Kemitraan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Manajemen Mitra & Kerjasama (MOU)
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Kelola logo dan nama instansi partner untuk ditampilkan di halaman utama.</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6">
            <!-- Toolbar: Search & Actions -->
            <div class="flex flex-col md:flex-row items-center gap-4">
                <form @submit.prevent="search" class="relative group flex-1 w-full">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 group-focus-within:text-namira-teal transition-colors">
                        <MagnifyingGlassIcon class="w-5 h-5" />
                    </div>
                    <input 
                        v-model="searchForm.search"
                        type="text" 
                        placeholder="Cari nama instansi/mitra..." 
                        class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                    >
                </form>

                <Link :href="route('public-relations.partners.create')" class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 active:scale-95 h-[46px] whitespace-nowrap">
                    <PlusIcon class="w-5 h-5" />
                    <span>Tambah Mitra</span>
                </Link>
            </div>

            <!-- Data Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/75 dark:bg-gray-800/50">
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider">Logo</th>
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider">Nama Instansi / Mitra</th>
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider">Website URL</th>
                                <th class="p-5 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in partners.data" :key="item.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/25 transition-colors">
                                <td class="p-5">
                                    <div class="w-20 h-12 bg-slate-100 rounded-lg flex items-center justify-center p-1.5 overflow-hidden border border-gray-100">
                                        <img :src="'/' + item.logo_path" :alt="item.name" class="w-full h-full object-contain" />
                                    </div>
                                </td>
                                <td class="p-5">
                                    <div class="font-bold text-sm text-gray-900 dark:text-gray-100">{{ item.name }}</div>
                                </td>
                                <td class="p-5">
                                    <a v-if="item.website_url" :href="item.website_url" target="_blank" class="inline-flex items-center gap-1.5 text-xs text-namira-teal hover:underline font-medium">
                                        <LinkIcon class="w-3.5 h-3.5" />
                                        <span>Kunjungi Situs</span>
                                    </a>
                                    <span v-else class="text-xs text-gray-400 font-light">-</span>
                                </td>
                                <td class="p-5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('public-relations.partners.edit', item.id)" class="p-2 text-gray-400 hover:text-namira-teal hover:bg-namira-teal/10 rounded-xl transition-all">
                                            <PencilSquareIcon class="w-4 h-4" />
                                        </Link>
                                        <button @click="confirmDelete(item)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all">
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="partners.data.length === 0">
                                <td colspan="4" class="p-10 text-center text-gray-400">
                                    <BuildingOffice2Icon class="w-12 h-12 mx-auto opacity-50 mb-3" />
                                    <p class="text-sm font-semibold">Belum ada data mitra.</p>
                                    <p class="text-xs mt-1">Silakan tambahkan data mitra untuk memulai.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="partners.links && partners.links.length > 3" class="flex justify-between items-center bg-white/50 backdrop-blur-sm px-6 py-4 rounded-2xl border border-white/50 shadow-sm">
                <div class="text-xs text-gray-500 font-medium">
                    Menampilkan {{ partners.from || 0 }} sampai {{ partners.to || 0 }} dari {{ partners.total || 0 }} data
                </div>
                <div class="flex items-center gap-1">
                    <template v-for="(link, i) in partners.links" :key="i">
                        <div v-if="link.url === null" 
                             class="px-3.5 py-1.5 text-xs text-gray-400 font-medium cursor-not-allowed bg-white/20 rounded-lg"
                             v-html="link.label"></div>
                        <Link v-else 
                              :href="link.url"
                              class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all"
                              :class="link.active ? 'bg-namira-teal text-white shadow-md shadow-namira-teal/20' : 'bg-white hover:bg-gray-50 text-gray-700'"
                              v-html="link.label"></Link>
                    </template>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-4">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 overflow-y-auto bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 relative">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-red-50 rounded-2xl text-red-600">
                            <ExclamationTriangleIcon class="w-6 h-6" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-lg text-gray-900">Hapus Mitra</h3>
                            <p class="text-sm text-gray-500 mt-2">Apakah Anda yakin ingin menghapus mitra <strong class="text-gray-900">{{ itemToDelete?.name }}</strong>? Tindakan ini tidak dapat dibatalkan.</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                        <button @click="closeModal" class="px-5 py-2 text-xs font-bold text-gray-500 hover:bg-gray-50 rounded-xl transition-all">Batal</button>
                        <button @click="deleteItem" class="px-5 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 shadow-md shadow-red-600/20 rounded-xl transition-all" :disabled="deleteForm.processing">Hapus</button>
                    </div>
                </div>
            </div>
        </transition>
    </AuthenticatedLayout>
</template>
