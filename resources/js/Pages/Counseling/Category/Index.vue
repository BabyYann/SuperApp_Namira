<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';
import { 
    MagnifyingGlassIcon, 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon,
    ExclamationTriangleIcon,
    TableCellsIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import Pagination from '@/Components/Pagination.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    categories: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const showingModal = ref(false);
const editingCategory = ref(null);
const isLoading = ref(false);

const form = useForm({
    name: '',
    type: 'ringan',
    default_points: '',
});

const handleSearch = () => {
    isLoading.value = true;
    form.get(route('counseling.categories.index'), {
        search: search.value,
        preserveState: true,
        onFinish: () => isLoading.value = false
    });
};

const openModal = (category = null) => {
    editingCategory.value = category;
    if (category) {
        form.name = category.name;
        form.type = category.type;
        form.default_points = category.default_points;
    } else {
        form.reset();
        form.type = 'ringan';
    }
    showingModal.value = true;
};

const submit = () => {
    if (editingCategory.value) {
        form.put(route('counseling.categories.update', editingCategory.value.id), {
            onSuccess: () => {
                showingModal.value = false;
                Swal.fire('Berhasil', 'Kategori diperbarui', 'success');
            },
        });
    } else {
        form.post(route('counseling.categories.store'), {
            onSuccess: () => {
                showingModal.value = false;
                Swal.fire('Berhasil', 'Kategori ditambahkan', 'success');
            },
        });
    }
};

const confirmDelete = (category) => {
    Swal.fire({
        title: 'Hapus Kategori?',
        text: "Data yang sudah dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.delete(route('counseling.categories.destroy', category.id), {
                onSuccess: () => Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success')
            });
        }
    });
};

const getTypeColor = (type) => {
    switch(type) {
        case 'berat': return 'bg-red-50 text-red-700 border-red-100 ring-1 ring-red-200';
        case 'sedang': return 'bg-orange-50 text-orange-700 border-orange-100 ring-1 ring-orange-200';
        default: return 'bg-green-50 text-green-700 border-green-100 ring-1 ring-green-200';
    }
};
</script>

<template>
    <Head title="Kategori Pelanggaran" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                    Master Kategori Pelanggaran
                </h2>
                <p class="text-sm text-gray-500">
                    Total Kategori: <span class="font-bold text-namira-teal">{{ categories.total }} Jenis</span>
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
                        @keyup.enter="handleSearch"
                        type="text" 
                        placeholder="Cari kategori (misal: merokok)..." 
                        class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                    >
                </div>

                <div class="flex-none">
                     <button 
                        @click="openModal()"
                        class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 whitespace-nowrap active:scale-95 h-[46px]"
                    >
                        <PlusIcon class="w-5 h-5" />
                        <span>Tambah Kategori</span>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-500 uppercase bg-white/50 border-b border-gray-100">
                        <tr>
                            <th scope="col" class="px-6 py-5 font-extrabold tracking-wider">Nama Pelanggaran</th>
                            <th scope="col" class="px-6 py-5 font-extrabold tracking-wider">Jenis</th>
                            <th scope="col" class="px-6 py-5 font-extrabold tracking-wider">Poin Default</th>
                            <th scope="col" class="px-6 py-5 font-extrabold tracking-wider text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="cat in categories.data" :key="cat.id" class="group hover:bg-teal-50/30 transition-colors">
                            <td class="px-6 py-4 font-bold text-gray-800 group-hover:text-namira-teal transition-colors">{{ cat.name }}</td>
                            <td class="px-6 py-4">
                                <span :class="getTypeColor(cat.type)" class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider shadow-sm">
                                    {{ cat.type }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1.5 font-bold text-gray-700">
                                    <ExclamationTriangleIcon class="w-4 h-4 text-orange-400" />
                                    {{ cat.default_points }} Poin
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
                                    <button @click="openModal(cat)" class="p-2 rounded-xl text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition-colors border border-transparent hover:border-amber-100" title="Edit">
                                        <PencilSquareIcon class="w-4 h-4" />
                                    </button>
                                    <button @click="confirmDelete(cat)" class="p-2 rounded-xl text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors border border-transparent hover:border-red-100" title="Hapus">
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="categories.data.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center p-8">
                                    <div class="bg-gray-50 p-6 rounded-full mb-4">
                                        <TableCellsIcon class="w-12 h-12 text-gray-300" />
                                    </div>
                                    <p class="font-bold text-lg text-gray-800">Belum ada kategori</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <Pagination :links="categories.links" class="p-6 border-t border-gray-100 bg-white/50" />
            </div>

        </div>

        <!-- Modal Form -->
        <Modal :show="showingModal" @close="showingModal = false">
            <div class="p-8">
                <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                    <div class="w-10 h-10 rounded-xl bg-namira-teal/10 flex items-center justify-center text-namira-teal">
                        <PencilSquareIcon v-if="editingCategory" class="w-6 h-6" />
                        <PlusIcon v-else class="w-6 h-6" />
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">
                        {{ editingCategory ? 'Edit Kategori' : 'Tambah Kategori Baru' }}
                    </h2>
                </div>
                
                <div class="space-y-6">
                    <div>
                        <InputLabel value="Nama Pelanggaran" />
                        <TextInput v-model="form.name" class="w-full mt-1 border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="Contoh: Terlambat Masuk Sekolah" />
                        <InputError :message="form.errors.name" />
                    </div>
                    
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <InputLabel value="Tingkat Pelanggaran" />
                            <select v-model="form.type" class="w-full mt-1 border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50">
                                <option value="ringan">Ringan (Mild)</option>
                                <option value="sedang">Sedang (Moderate)</option>
                                <option value="berat">Berat (Severe)</option>
                            </select>
                            <InputError :message="form.errors.type" />
                        </div>
                        <div>
                            <InputLabel value="Poin (Bobot)" />
                            <div class="relative mt-1">
                                <TextInput v-model="form.default_points" type="number" class="w-full border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50 pr-12" />
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400 font-bold text-xs">
                                    PTS
                                </div>
                            </div>
                            <InputError :message="form.errors.default_points" />
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-3 pt-6 border-t border-gray-50">
                    <button @click="showingModal = false" class="px-6 py-2.5 text-gray-500 font-bold hover:bg-gray-50 rounded-xl transition-colors">Batal</button>
                    <PrimaryButton @click="submit" :disabled="form.processing" class="rounded-xl px-8 shadow-lg shadow-namira-teal/20">Simpan Data</PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
