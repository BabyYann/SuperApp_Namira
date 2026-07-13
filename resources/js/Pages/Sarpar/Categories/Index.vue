<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { 
    PlusIcon, PencilSquareIcon, TrashIcon, TagIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    categories: Array,
});

const showModal = ref(false);
const isEditing = ref(false);
const showDeleteConfirm = ref(false);
const itemToDelete = ref(null);

const form = useForm({
    id: null,
    name: '',
    code: '',
    description: '',
});

const deleteForm = useForm({});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    showModal.value = true;
};

const openEditModal = (category) => {
    isEditing.value = true;
    form.id = category.id;
    form.name = category.name;
    form.code = category.code;
    form.description = category.description || '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('sarpar.categories.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('sarpar.categories.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDelete = (category) => {
    itemToDelete.value = category;
    showDeleteConfirm.value = true;
};

const deleteItem = () => {
    if (!itemToDelete.value) return;
    deleteForm.delete(route('sarpar.categories.destroy', itemToDelete.value.id), {
        onSuccess: () => {
            showDeleteConfirm.value = false;
            itemToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Kategori Inventaris" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                    Kategori Inventaris
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Data kategori untuk pengelompokan barang inventaris
                </p>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto pb-20 space-y-6">
            <!-- Toolbar -->
            <div class="flex justify-end">
                <button 
                    @click="openCreateModal"
                    class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 active:scale-95"
                >
                    <PlusIcon class="w-5 h-5" />
                    <span>Tambah Kategori</span>
                </button>
            </div>

            <!-- Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="category in categories" :key="category.id" class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 p-6 hover:shadow-lg transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-gradient-to-br from-namira-teal/10 to-teal-100 text-namira-teal rounded-2xl">
                            <TagIcon class="w-6 h-6" />
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-mono font-bold text-gray-400 bg-gray-100 px-2 py-0.5 rounded">{{ category.code }}</span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800">{{ category.name }}</h3>
                            <p class="text-sm text-gray-500 line-clamp-2 mt-1">{{ category.description || '-' }}</p>
                            <div class="mt-3 text-xs text-gray-400">
                                {{ category.inventories_count || 0 }} item terdaftar
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-gray-100 opacity-0 group-hover:opacity-100 transition-all">
                        <button @click="openEditModal(category)" class="p-2 rounded-xl text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition-colors">
                            <PencilSquareIcon class="w-4 h-4" />
                        </button>
                        <button @click="confirmDelete(category)" class="p-2 rounded-xl text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors" :disabled="category.inventories_count > 0">
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="categories.length === 0" class="col-span-full bg-white/80 backdrop-blur-xl rounded-3xl p-12 text-center border border-white/50">
                    <TagIcon class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                    <h3 class="text-lg font-bold text-gray-800 mb-1">Belum ada kategori</h3>
                    <p class="text-sm text-gray-500">Buat kategori untuk mengelompokkan inventaris</p>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-8 border border-gray-100">
                        <h3 class="text-xl font-bold text-gray-900 mb-6">
                            {{ isEditing ? 'Edit Kategori' : 'Tambah Kategori' }}
                        </h3>

                        <form @submit.prevent="submit" class="space-y-5">
                            <div>
                                <InputLabel for="name" value="Nama Kategori" class="text-sm font-bold text-gray-700" />
                                <TextInput id="name" v-model="form.name" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="Contoh: Elektronik" required autofocus />
                                <InputError :message="form.errors.name" class="mt-1" />
                            </div>
                            
                            <div>
                                <InputLabel for="code" value="Kode (3-5 huruf)" class="text-sm font-bold text-gray-700" />
                                <TextInput id="code" v-model="form.code" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50 uppercase font-mono" placeholder="Contoh: ELK" maxlength="5" required />
                                <InputError :message="form.errors.code" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="description" value="Deskripsi" class="text-sm font-bold text-gray-700" />
                                <textarea id="description" v-model="form.description" rows="2" class="w-full mt-1.5 px-4 py-3 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" placeholder="Keterangan kategori (opsional)"></textarea>
                                <InputError :message="form.errors.description" class="mt-1" />
                            </div>

                            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-50">
                                <button type="button" @click="closeModal" class="px-5 py-2.5 text-gray-500 font-bold hover:bg-gray-50 rounded-xl">Batal</button>
                                <PrimaryButton :disabled="form.processing" class="rounded-xl px-6 shadow-lg shadow-namira-teal/30">{{ isEditing ? 'Simpan' : 'Tambah' }}</PrimaryButton>
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
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-50 mb-4">
                            <ExclamationTriangleIcon class="h-8 w-8 text-red-500" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Hapus Kategori?</h3>
                        <p class="text-sm text-gray-500 mb-6">Kategori "{{ itemToDelete?.name }}" akan dihapus.</p>
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
