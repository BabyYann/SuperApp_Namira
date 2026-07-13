<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    MagnifyingGlassIcon, PlusIcon, NewspaperIcon, PencilSquareIcon, TrashIcon, ExclamationTriangleIcon, EyeIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    news: Object,
    filters: Object,
});

const searchForm = useForm({
    search: props.filters.search || '',
});

const search = () => {
    searchForm.get(route('public-relations.news.index'), {
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
    deleteForm.delete(route('public-relations.news.destroy', itemToDelete.value.id), {
        onSuccess: () => closeModal(),
        onError: () => {
            closeModal();
            alert('Gagal menghapus berita.');
        }
    });
};
</script>

<template>
    <Head title="Manajemen Berita" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Manajemen Berita
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Kelola berita dan liputan unit sekolah Anda.</p>
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
                        placeholder="Cari Judul Berita..." 
                        class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                    >
                </form>

                <Link :href="route('public-relations.news.create')" class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 active:scale-95 h-[46px] whitespace-nowrap">
                    <PlusIcon class="w-5 h-5" />
                    <span>Tambah Berita</span>
                </Link>
            </div>

            <!-- Data Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/50 border-b border-gray-100 text-xs uppercase text-gray-500 font-extrabold tracking-wider">
                                <th class="p-6">Judul Berita</th>
                                <th class="p-6">Unit</th>
                                <th class="p-6">Status</th>
                                <th class="p-6">Penulis</th>
                                <th class="p-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="news.data.length === 0">
                                <td colspan="5" class="p-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                            <NewspaperIcon class="w-10 h-10 text-gray-400" />
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900">Belum ada berita.</h3>
                                        <p class="text-sm">Silakan buat liputan berita pertama Anda.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="item in news.data" :key="item.id" class="hover:bg-teal-50/30 transition-colors group">
                                <td class="p-6">
                                    <div class="flex items-center gap-4">
                                        <img v-if="item.image_path" :src="'/' + item.image_path" class="w-16 h-12 object-cover rounded-lg border border-gray-200">
                                        <div v-else class="w-16 h-12 bg-gray-100 flex items-center justify-center rounded-lg border border-gray-200">
                                            <NewspaperIcon class="w-6 h-6 text-gray-400"/>
                                        </div>
                                        <div class="font-bold text-gray-800 text-sm md:text-base group-hover:text-namira-teal transition-colors line-clamp-2 max-w-xs">{{ item.title }}</div>
                                    </div>
                                </td>
                                <td class="p-6 text-sm text-gray-600">
                                    {{ item.unit?.name || 'Yayasan' }}
                                </td>
                                <td class="p-6">
                                    <span :class="item.status === 'published' ? 'bg-green-50 text-green-700 border-green-100' : 'bg-amber-50 text-amber-700 border-amber-100'" class="px-3 py-1 rounded-xl text-xs font-bold uppercase tracking-wide border shadow-sm">
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="p-6 text-sm text-gray-600">
                                    {{ item.author?.name || '-' }}
                                </td>
                                <td class="p-6 text-right">
                                    <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
                                        <Link :href="route('public-relations.news.edit', item.id)" class="p-2.5 text-gray-400 hover:text-namira-teal hover:bg-teal-50 rounded-xl transition-all duration-200 border border-transparent hover:border-teal-100" title="Edit Berita">
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </Link>
                                        <button 
                                            @click="confirmDelete(item)" 
                                            class="p-2.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all duration-200 border border-transparent hover:border-red-100 cursor-pointer" 
                                            title="Hapus Berita"
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
            
            <!-- Pagination -->
            <div v-if="news.links && news.links.length > 3" class="mt-4 flex justify-center">
                <div class="flex gap-1">
                    <Link v-for="(link, k) in news.links" :key="k" 
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
                                    <h3 class="text-xl font-bold leading-6 text-gray-900 mb-2" id="modal-title">Hapus Berita?</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            Anda akan menghapus berita ini permanen.
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
                                        @click="deleteItem"
                                        :disabled="deleteForm.processing"
                                    >
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
