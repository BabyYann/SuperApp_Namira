<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    MagnifyingGlassIcon, PlusIcon, PhotoIcon, PencilSquareIcon, TrashIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    banners: Object,
    filters: Object,
});

const searchQuery = ref(props.filters?.search || '');

const applyFilters = () => {
    router.get(route('public-relations.banners.index'), {
        search: searchQuery.value || undefined,
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
    deleteForm.delete(route('public-relations.banners.destroy', itemToDelete.value.id), {
        onSuccess: () => closeModal(),
        onError: () => { closeModal(); alert('Gagal menghapus data.'); }
    });
};

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);

const currentPage = computed(() => props.banners.current_page || props.banners.meta?.current_page || 1);
const perPage = computed(() => props.banners.per_page || props.banners.meta?.per_page || 15);
</script>

<template>
    <Head title="Kelola Hero Banner Slider" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight flex items-center gap-2">
                    <PhotoIcon class="w-6 h-6 text-namira-teal" />
                    Hero Banner Slider
                </h2>
                <p class="text-sm text-gray-500 mt-1">Kelola foto banner utama yang tampil sebagai slider di halaman depan website (maksimal 10 aktif).</p>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6">

            <!-- Flash Success -->
            <transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="successMessage" class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 rounded-2xl px-5 py-3 text-sm font-medium shadow-sm">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    {{ successMessage }}
                </div>
            </transition>

            <!-- Toolbar -->
            <div class="flex flex-col md:flex-row items-stretch gap-3">
                <!-- Search -->
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                        <MagnifyingGlassIcon class="w-5 h-5" />
                    </div>
                    <input
                        v-model="searchQuery"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Cari judul banner..."
                        class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                    />
                </div>

                <Link :href="route('public-relations.banners.create')"
                    class="px-5 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 active:scale-95 h-[46px] whitespace-nowrap">
                    <PlusIcon class="w-5 h-5" />
                    <span>Tambah Banner</span>
                </Link>
            </div>

            <!-- Data Table -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/50 border-b border-gray-100 text-xs uppercase text-gray-500 font-extrabold tracking-wider">
                                <th class="p-5">No</th>
                                <th class="p-5">Thumbnail</th>
                                <th class="p-5">Nama / Judul</th>
                                <th class="p-5">Bobot Urutan</th>
                                <th class="p-5">Status Aktif</th>
                                <th class="p-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <!-- Empty State -->
                            <tr v-if="banners.data.length === 0">
                                <td colspan="6" class="p-14 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                            <PhotoIcon class="w-10 h-10 text-gray-300" />
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900 mb-1">Belum ada data banner.</h3>
                                        <p class="text-sm">Mulai tambahkan banner slider untuk halaman depan.</p>
                                    </div>
                                </td>
                            </tr>

                            <!-- Rows -->
                            <tr v-for="(item, index) in banners.data" :key="item.id" class="hover:bg-teal-50/30 transition-colors group">
                                <td class="p-5 text-sm text-gray-400 font-mono">
                                    {{ (currentPage - 1) * perPage + index + 1 }}
                                </td>
                                <td class="p-5">
                                    <div class="w-32 h-18 rounded-lg overflow-hidden bg-slate-100 border border-gray-100 shrink-0 flex items-center justify-center relative shadow-sm">
                                        <img v-if="item.image_path" :src="'/' + item.image_path" class="w-full h-full object-cover" />
                                        <PhotoIcon v-else class="w-6 h-6 text-gray-400" />
                                    </div>
                                </td>
                                <td class="p-5">
                                    <span class="font-bold text-gray-800 text-sm group-hover:text-namira-teal transition-colors line-clamp-1 max-w-[300px]">
                                        {{ item.title || 'Banner #' + item.id }}
                                    </span>
                                </td>
                                <td class="p-5 text-sm text-gray-600 font-semibold font-mono">
                                    {{ item.order_weight }}
                                </td>
                                <td class="p-5">
                                    <span :class="item.is_active ? 'bg-green-50 text-green-700 border-green-100' : 'bg-gray-50 text-gray-500 border-gray-100'"
                                        class="px-3 py-1 rounded-xl text-xs font-bold border shadow-sm">
                                        {{ item.is_active ? 'Aktif' : 'Non-Aktif' }}
                                    </span>
                                </td>
                                <td class="p-5 text-right">
                                    <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
                                        <Link :href="route('public-relations.banners.edit', item.id)"
                                            class="p-2.5 text-gray-400 hover:text-namira-teal hover:bg-teal-50 rounded-xl transition-all duration-200 border border-transparent hover:border-teal-100"
                                            title="Edit">
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </Link>
                                        <button @click="confirmDelete(item)"
                                            class="p-2.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all duration-200 border border-transparent hover:border-red-100 cursor-pointer"
                                            title="Hapus">
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
            <div v-if="banners.links && banners.links.length > 3" class="mt-4 flex justify-center">
                <div class="flex gap-1">
                    <Link v-for="(link, k) in banners.links" :key="k"
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
                                    <h3 class="text-xl font-bold text-gray-900 mb-2">Hapus Banner?</h3>
                                    <p class="text-sm text-gray-500">Anda akan menghapus banner <strong>{{ itemToDelete?.title || 'Banner #' + itemToDelete?.id }}</strong> secara permanen.</p>
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
