<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { 
    MagnifyingGlassIcon, PlusIcon, UserGroupIcon, 
    EyeIcon, PencilSquareIcon, TrashIcon, CameraIcon, ArrowPathIcon
} from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useMediaQuery } from '@vueuse/core';
import MobileCard from '@/Components/MobileCard.vue';

const props = defineProps({
    staff: Object,
    stats: Object,
    filters: Object,
});

const searchQuery = ref(props.filters?.search || '');
const showModal = ref(false);
const isEditing = ref(false);
const showDeleteConfirm = ref(false);
const itemToDelete = ref(null);
const photoPreview = ref(null);
const isLoading = ref(false);
const isMobile = useMediaQuery('(max-width: 768px)');

const form = useForm({
    id: null,
    full_name: '',
    email: '',
    nip: '',
    gender: 'L',
    phone: '',
    position: '',
    photo: null,
});

const deleteForm = useForm({});

// Debounce Utility
const debounce = (func, wait) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
};

// Debounced Search Action
const performSearch = debounce((query) => {
    isLoading.value = true;
    router.get(route('yayasan.staff.index'), 
        { search: query }, 
        { 
            preserveState: true, 
            replace: true, 
             onFinish: () => isLoading.value = false 
        }
    );
}, 300);

watch(searchQuery, (value) => {
    performSearch(value);
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.gender = 'L';
    photoPreview.value = null;
    showModal.value = true;
};

const openEditModal = (staffMember) => {
    isEditing.value = true;
    form.id = staffMember.id;
    form.full_name = staffMember.full_name;
    form.email = staffMember.user?.email || '';
    form.nip = staffMember.nip;
    form.gender = staffMember.gender;
    form.phone = staffMember.phone;
    form.position = staffMember.position;
    form.photo = null;
    photoPreview.value = staffMember.photo ? `/storage/${staffMember.photo}` : null;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    photoPreview.value = null;
};

const handlePhotoUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    if (isEditing.value) {
        router.post(route('yayasan.staff.update', form.id), {
            _method: 'put',
            ...form.data(),
            photo: form.photo,
        }, { onSuccess: () => closeModal() });
    } else {
        form.post(route('yayasan.staff.store'), { onSuccess: () => closeModal() });
    }
};

const confirmDelete = (staffMember) => {
    itemToDelete.value = staffMember;
    showDeleteConfirm.value = true;
};

const deleteItem = () => {
    deleteForm.delete(route('yayasan.staff.destroy', itemToDelete.value.id), {
        onSuccess: () => {
            showDeleteConfirm.value = false;
            itemToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head title="Manajemen Staf" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                    Tenaga Kependidikan
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Total Data: <span class="font-bold text-namira-teal">{{ stats.total }} Staf</span>
                </p>
            </div>
        </template>

        <div class="py-4 md:py-6 max-w-7xl mx-auto space-y-5 md:space-y-6">

            <!-- 📱 MOBILE PWA VIEW (block md:hidden) -->
            <div class="block md:hidden -mx-4 -mt-4 space-y-4">
                <!-- Header Card Gradient -->
                <div class="bg-gradient-to-br from-[#009688] to-[#0f172a] px-4 pt-5 pb-6 text-white">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-[10px] font-extrabold tracking-widest uppercase text-teal-300">Kepegawaian</p>
                            <h1 class="text-xl font-black leading-tight">Tenaga Kependidikan</h1>
                        </div>
                        <button 
                            @click="openCreateModal"
                            class="px-3.5 py-2 bg-teal-500 hover:bg-teal-600 text-white font-extrabold text-xs rounded-xl shadow-lg flex items-center gap-1.5 active:scale-95 transition"
                        >
                            <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                            <span>Tambah Staf</span>
                        </button>
                    </div>

                    <!-- Quick Stats -->
                    <div class="grid grid-cols-2 gap-2 mt-4 text-center">
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-2xl px-3 py-2.5">
                            <p class="text-2xl font-black text-white leading-none">{{ stats.total || 0 }}</p>
                            <p class="text-[10px] text-teal-200 font-bold mt-1 uppercase">Total Staf</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-2xl px-3 py-2.5">
                            <p class="text-2xl font-black text-emerald-300 leading-none">{{ staff.total || staff.data.length }}</p>
                            <p class="text-[10px] text-emerald-200 font-bold mt-1 uppercase">Staf Aktif</p>
                        </div>
                    </div>
                </div>

                <!-- Search Controls -->
                <div class="px-4">
                    <div class="relative">
                        <MagnifyingGlassIcon v-if="!isLoading" class="w-4 h-4 absolute left-3 top-3.5 text-slate-400" />
                        <ArrowPathIcon v-else class="w-4 h-4 absolute left-3 top-3.5 animate-spin text-teal-600" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama, NIP, atau jabatan staf..."
                            class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:ring-teal-500 focus:border-teal-500 shadow-sm"
                        />
                    </div>
                </div>

                <!-- Staff Mobile Touch Cards -->
                <div class="px-4 space-y-3">
                    <div v-if="staff.data.length === 0" class="bg-white rounded-2xl p-8 text-center border border-slate-100 shadow-sm">
                        <UserGroupIcon class="w-10 h-10 mx-auto text-teal-300 mb-2" />
                        <p class="font-extrabold text-sm text-slate-800">Belum ada data staf</p>
                        <p class="text-xs text-slate-400 mt-1">Data tenaga kependidikan masih kosong.</p>
                    </div>

                    <div
                        v-for="s in staff.data"
                        :key="s.id"
                        class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm space-y-3 relative overflow-hidden"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-slate-100 flex-shrink-0 overflow-hidden border-2 border-white shadow-sm">
                                    <img v-if="s.photo" :src="`/storage/${s.photo}`" class="w-full h-full object-cover">
                                    <div v-else class="w-full h-full flex items-center justify-center text-xs font-black text-slate-400 bg-slate-100">
                                        {{ s.full_name?.substring(0,2).toUpperCase() }}
                                    </div>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-sm leading-tight">{{ s.full_name }}</h3>
                                    <p class="text-[11px] text-slate-500 font-medium mt-0.5">{{ s.user?.email || 'Belum ada email' }}</p>
                                </div>
                            </div>
                            <span v-if="s.position" class="px-2.5 py-1 text-[10px] font-black uppercase rounded-xl bg-purple-50 text-purple-700 border border-purple-100 shrink-0">
                                {{ s.position }}
                            </span>
                        </div>

                        <!-- Details Row: NIP & Phone -->
                        <div class="grid grid-cols-2 gap-2 bg-slate-50 p-2.5 rounded-xl text-xs border border-slate-100">
                            <div>
                                <span class="text-[9px] font-extrabold text-slate-400 uppercase block">NIP</span>
                                <span class="font-bold text-slate-800 text-[11px]">{{ s.nip || '-' }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-extrabold text-slate-400 uppercase block">Kontak</span>
                                <span class="font-bold text-slate-800 text-[11px]">{{ s.phone || '-' }}</span>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-1 flex items-center justify-end gap-2 border-t border-slate-100">
                            <Link :href="route('yayasan.staff.show', s.id)" class="px-3 py-1.5 rounded-xl text-xs font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 transition flex items-center gap-1">
                                <EyeIcon class="w-3.5 h-3.5" />
                                <span>Detail</span>
                            </Link>
                            <button @click="openEditModal(s)" class="px-3 py-1.5 rounded-xl text-xs font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 transition flex items-center gap-1">
                                <PencilSquareIcon class="w-3.5 h-3.5" />
                                <span>Edit</span>
                            </button>
                            <button @click="confirmDelete(s)" class="px-3 py-1.5 rounded-xl text-xs font-bold text-red-700 bg-red-50 hover:bg-red-100 transition flex items-center gap-1">
                                <TrashIcon class="w-3.5 h-3.5" />
                                <span>Hapus</span>
                            </button>
                        </div>
                    </div>

                    <!-- Pagination Mobile -->
                    <Pagination :links="staff.links" class="pt-2" />
                </div>
            </div>
            <!-- END MOBILE VIEW -->

            <!-- 🖥️ DESKTOP VIEW (hidden md:block) -->
            <div class="hidden md:block space-y-6">
                <!-- Toolbar -->
                <div class="flex flex-col md:flex-row items-center gap-4">
                     <!-- Search Bar -->
                     <div class="relative group flex-1 w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 group-focus-within:text-namira-teal transition-colors">
                                <MagnifyingGlassIcon v-if="!isLoading" class="w-5 h-5" />
                                <ArrowPathIcon v-else class="animate-spin h-5 w-5 text-namira-teal" />
                        </div>
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Cari Nama / NIP / Jabatan..." 
                            class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                        >
                    </div>

                    <!-- Add Button -->
                    <button 
                        @click="openCreateModal"
                        class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 whitespace-nowrap active:scale-95 h-[46px]"
                    >
                        <PlusIcon class="w-5 h-5" />
                        <span>Tambah Staf</span>
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                     <div class="bg-white/80 backdrop-blur-xl p-6 rounded-3xl border border-white/50 shadow-sm flex items-center gap-4 hover:shadow-md transition-all group">
                            <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl group-hover:scale-110 transition-transform">
                            <UserGroupIcon class="w-8 h-8" />
                        </div>
                        <div>
                            <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Total Staf</div>
                            <div class="text-3xl font-extrabold text-gray-800">{{ stats.total }}</div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Container (Desktop Table) -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-white/50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-5 font-extrabold tracking-wider">Nama & Email</th>
                                <th class="px-6 py-5 font-extrabold tracking-wider">Jabatan</th>
                                <th class="px-6 py-5 font-extrabold tracking-wider">NIP</th>
                                 <th class="px-6 py-5 font-extrabold tracking-wider">Kontak</th>
                                <th class="px-6 py-5 font-extrabold tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                             <tr v-if="staff.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <p class="font-bold text-lg text-gray-800 mb-1">Belum ada data</p>
                                    <p class="text-sm">Silakan tambahkan data staf baru.</p>
                                </td>
                            </tr>
                            <tr v-for="s in staff.data" :key="s.id" class="group hover:bg-teal-50/30 transition-colors">
                                 <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-full bg-gray-100 flex-shrink-0 overflow-hidden border-2 border-white shadow-sm group-hover:border-namira-teal/50 transition-colors">
                                            <img v-if="s.photo" :src="`/storage/${s.photo}`" class="w-full h-full object-cover">
                                            <div v-else class="w-full h-full flex items-center justify-center text-xs font-bold text-gray-400">
                                                {{ s.full_name?.substring(0,2).toUpperCase() }}
                                            </div>
                                        </div>
                                        <div>
                                            <Link :href="route('yayasan.staff.show', s.id)" class="font-bold text-gray-800 text-base group-hover:text-namira-teal transition-colors hover:underline">{{ s.full_name }}</Link>
                                            <div class="text-xs text-gray-500">{{ s.user?.email || 'No Email' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 bg-purple-50 text-purple-600 rounded-lg text-xs font-bold border border-purple-100">{{ s.position || '-' }}</span>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs font-bold text-gray-600">{{ s.nip || '-' }}</td>
                                 <td class="px-6 py-4 font-mono text-xs text-gray-600">{{ s.phone || '-' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all">
                                         <Link :href="route('yayasan.staff.show', s.id)" class="p-2 bg-teal-50 text-namira-teal rounded-lg hover:bg-namira-teal hover:text-white transition-colors shadow-sm" title="Lihat Detail">
                                            <EyeIcon class="w-4 h-4" />
                                         </Link>
                                        <button @click="openEditModal(s)" class="p-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-600 hover:text-white transition-colors shadow-sm" title="Edit Data">
                                            <PencilSquareIcon class="w-4 h-4" />
                                        </button>
                                        <button @click="confirmDelete(s)" class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition-colors shadow-sm" title="Hapus Staf">
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                     <!-- Pagination -->
                    <Pagination :links="staff.links" class="p-6 border-t border-gray-100 bg-white/50" />
                </div>
            </div>
            <!-- END DESKTOP VIEW -->
        </div>

         <!-- Create/Edit Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-8 border border-gray-100 overflow-hidden transform transition-all scale-100">
                        <h3 class="text-2xl font-bold text-gray-900 mb-8 pb-4 border-b border-gray-100">
                            {{ isEditing ? 'Edit Data Staf' : 'Registrasi Staf Baru' }}
                        </h3>

                        <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Photo Upload -->
                           <div class="md:col-span-2 flex justify-center mb-4">
                                <div class="relative group cursor-pointer text-center">
                                    <div class="w-32 h-32 mx-auto rounded-full bg-gray-50 border-4 border-white shadow-lg flex items-center justify-center overflow-hidden hover:border-namira-teal transition-all group-hover:shadow-xl">
                                        <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover">
                                        <div v-else class="flex flex-col items-center justify-center text-gray-400 group-hover:text-namira-teal transition-colors">
                                            <span class="text-[10px] font-bold uppercase tracking-wide">Upload Foto</span>
                                        </div>
                                    </div>
                                    <input type="file" @change="handlePhotoUpload" class="absolute inset-0 opacity-0 cursor-pointer w-32 mx-auto h-32" accept="image/*">
                                    <div class="text-xs text-red-500 mt-2 font-bold" v-if="form.errors.photo">{{ form.errors.photo }}</div>
                                </div>
                           </div>

                            <div class="md:col-span-2">
                                <InputLabel for="full_name" value="Nama Lengkap *" />
                                <TextInput id="full_name" v-model="form.full_name" class="w-full mt-1 border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" required placeholder="Nama Lengkap Staf" />
                                <InputError :message="form.errors.full_name" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="nip" value="NIP (Opsional)" />
                                <TextInput id="nip" v-model="form.nip" class="w-full mt-1 border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="Nomor Induk Pegawai" />
                                <InputError :message="form.errors.nip" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="position" value="Jabatan *" />
                                <TextInput id="position" v-model="form.position" class="w-full mt-1 border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="Contoh: Kepala TU" />
                                <InputError :message="form.errors.position" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="gender" value="Jenis Kelamin *" />
                                <select id="gender" v-model="form.gender" class="w-full mt-1 border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-gray-700 bg-gray-50/50">
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                                <InputError :message="form.errors.gender" class="mt-1" />
                            </div>

                             <div>
                                <InputLabel for="phone" value="Nomor HP" />
                                <TextInput id="phone" v-model="form.phone" class="w-full mt-1 border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="08..." />
                                <InputError :message="form.errors.phone" class="mt-1" />
                            </div>

                            <div class="md:col-span-2 border-t border-gray-100 pt-4">
                                <InputLabel for="email" value="Email (Akun Login) *" />
                                <TextInput id="email" v-model="form.email" type="email" class="w-full mt-1 border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50" :disabled="isEditing" required placeholder="staf@namira.school" />
                                <p class="text-[10px] text-gray-400 mt-2" v-if="!isEditing">
                                    Password default akan menggunakan NIP atau 'staf123'
                                </p>
                                <InputError :message="form.errors.email" class="mt-1" />
                            </div>

                            <div class="md:col-span-2 flex justify-end gap-3 pt-6 border-t border-gray-50">
                                <button type="button" @click="closeModal" class="px-6 py-2.5 text-gray-500 font-bold hover:bg-gray-50 rounded-xl transition-colors">Batal</button>
                                <PrimaryButton :disabled="form.processing" class="rounded-xl px-8 shadow-lg shadow-namira-teal/20">{{ isEditing ? 'Simpan Perubahan' : 'Registrasi Staf' }}</PrimaryButton>
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
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-sm w-full p-8 text-center border border-gray-100">
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Hapus Data Staf?</h3>
                        <p class="text-gray-500 mb-8 leading-relaxed">Staf <span class="font-bold text-gray-800">"{{ itemToDelete?.full_name }}"</span> akan dihapus permanen.</p>
                        <div class="flex justify-center gap-4">
                            <button @click="showDeleteConfirm = false" class="px-6 py-2.5 bg-white border border-gray-200 rounded-xl font-bold text-gray-600 hover:bg-gray-50">Batal</button>
                            <button @click="deleteItem" class="px-6 py-2.5 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 shadow-lg shadow-red-500/30">Hapus Permanen</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>
