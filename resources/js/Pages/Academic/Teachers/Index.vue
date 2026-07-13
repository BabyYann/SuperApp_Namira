<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useMediaQuery } from '@vueuse/core';
import MobileCard from '@/Components/MobileCard.vue';
import { 
    MagnifyingGlassIcon, FunnelIcon, ArrowPathIcon, PlusIcon, 
    UserIcon, UserGroupIcon, EnvelopeIcon, PhoneIcon, EyeIcon, 
    PencilSquareIcon, TrashIcon, CameraIcon, ExclamationTriangleIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    teachers: Object,
    stats: Object,
    filters: Object,
});

const searchQuery = ref(props.filters?.search || '');
const showFilter = ref(!!props.filters?.gender);
const genderFilter = ref(props.filters?.gender || null);
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
const performSearch = debounce((query, gender) => {
    isLoading.value = true;
    router.get(route('yayasan.teachers.index'), 
        { search: query, gender: gender }, 
        { 
            preserveState: true, 
            replace: true, 
            onFinish: () => isLoading.value = false 
        }
    );
}, 300);

watch(searchQuery, (value) => {
    performSearch(value, genderFilter.value);
});

const toggleFilter = () => {
    showFilter.value = !showFilter.value;
};

const setFilter = (gender) => {
    genderFilter.value = gender;
    isLoading.value = true;
    router.get(route('yayasan.teachers.index'), 
        { search: searchQuery.value, gender: gender }, 
        { 
            preserveState: true, 
            replace: true, 
             onFinish: () => isLoading.value = false 
        }
    );
};

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.gender = 'L';
    photoPreview.value = null;
    showModal.value = true;
};

const openEditModal = (teacher) => {
    isEditing.value = true;
    form.id = teacher.id;
    form.full_name = teacher.full_name;
    form.email = teacher.user?.email || '';
    form.nip = teacher.nip;
    form.gender = teacher.gender;
    form.phone = teacher.phone;
    form.photo = null;
    photoPreview.value = teacher.photo ? `/storage/${teacher.photo}` : null;
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
        router.post(route('yayasan.teachers.update', form.id), {
            _method: 'put',
            ...form.data(),
            photo: form.photo,
        }, { onSuccess: () => closeModal() });
    } else {
        form.post(route('yayasan.teachers.store'), { onSuccess: () => closeModal() });
    }
};

const confirmDelete = (teacher) => {
    itemToDelete.value = teacher;
    showDeleteConfirm.value = true;
};

const deleteItem = () => {
    deleteForm.delete(route('yayasan.teachers.destroy', itemToDelete.value.id), {
        onSuccess: () => {
            showDeleteConfirm.value = false;
            itemToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head title="Manajemen Guru" />

    <AuthenticatedLayout>
        <template #header>
                <!-- Top Row: Title & Description -->
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Database Guru
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Total Data: <span class="font-bold text-namira-teal">{{ teachers.total }} Guru</span>
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
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Cari Nama / NIP..." 
                            class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                        >
                    </div>

                    <!-- Filter Toggle -->
                    <button @click="toggleFilter" class="px-4 py-2.5 bg-white/50 backdrop-blur-sm border border-white/50 text-gray-600 rounded-2xl text-sm font-bold hover:bg-white hover:shadow-md transition-all flex items-center justify-center gap-2 active:scale-95 h-[46px]" :class="{'border-namira-teal text-namira-teal bg-teal-50/50': showFilter}">
                        <FunnelIcon class="w-5 h-5" />
                        <span class="hidden md:inline">Filter</span>
                    </button>

                    <!-- Add Button -->
                    <button 
                        @click="openCreateModal"
                        class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 whitespace-nowrap active:scale-95 h-[46px]"
                    >
                        <PlusIcon class="w-5 h-5" />
                        <span>Tambah Guru</span>
                    </button>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white/80 backdrop-blur-xl p-6 rounded-3xl border border-white/50 shadow-sm flex items-center gap-4 hover:shadow-md transition-all group">
                     <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl group-hover:scale-110 transition-transform">
                        <UserGroupIcon class="w-8 h-8" />
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Total Guru</div>
                        <div class="text-3xl font-extrabold text-gray-800">{{ stats.total }}</div>
                    </div>
                </div>
                <div class="bg-white/80 backdrop-blur-xl p-6 rounded-3xl border border-white/50 shadow-sm flex items-center gap-4 hover:shadow-md transition-all group">
                     <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl group-hover:scale-110 transition-transform">
                        <UserIcon class="w-8 h-8" />
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Laki-laki</div>
                        <div class="text-3xl font-extrabold text-gray-800">{{ stats.male }}</div>
                    </div>
                </div>
                <div class="bg-white/80 backdrop-blur-xl p-6 rounded-3xl border border-white/50 shadow-sm flex items-center gap-4 hover:shadow-md transition-all group">
                     <div class="p-3 bg-pink-50 text-pink-600 rounded-2xl group-hover:scale-110 transition-transform">
                        <UserIcon class="w-8 h-8" />
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Perempuan</div>
                        <div class="text-3xl font-extrabold text-gray-800">{{ stats.female }}</div>
                    </div>
                </div>
            </div>

            <!-- Filter Row (Conditionally Visible) -->
            <div v-if="showFilter" class="mb-6 px-6 py-4 bg-white/80 backdrop-blur-xl rounded-2xl border border-white/50 shadow-sm flex items-center gap-4 animate-fade-in-down">
                <span class="text-sm font-bold text-gray-600">Filter Gender:</span>
                <button 
                    @click="setFilter('L')"
                    class="px-4 py-1.5 rounded-xl text-sm font-bold transition-all border"
                    :class="genderFilter === 'L' ? 'bg-blue-50 text-blue-600 border-blue-200 shadow-sm' : 'bg-white/50 text-gray-500 border-transparent hover:bg-white hover:shadow-sm'"
                >
                    Laki-laki
                </button>
                <button 
                     @click="setFilter('P')"
                     class="px-4 py-1.5 rounded-xl text-sm font-bold transition-all border"
                     :class="genderFilter === 'P' ? 'bg-pink-50 text-pink-600 border-pink-200 shadow-sm' : 'bg-white/50 text-gray-500 border-transparent hover:bg-white hover:shadow-sm'"
                >
                    Perempuan
                </button>
                 <button 
                     v-if="genderFilter"
                     @click="setFilter(null)"
                     class="ml-auto text-xs text-red-500 hover:text-red-700 font-bold hover:underline"
                >
                    Reset Filter
                </button>
            </div>

            <!-- Mobile View (Cards) -->
            <div v-if="isMobile" class="space-y-4">
                <MobileCard 
                    v-for="teacher in teachers.data" 
                    :key="teacher.id"
                    :title="teacher.full_name"
                    :subtitle="teacher.nip || 'No NIP'"
                    :image="teacher.photo ? `/storage/${teacher.photo}` : null"
                    :status="teacher.gender === 'L' ? 'Laki-laki' : 'Perempuan'"
                    :statusColor="teacher.gender === 'L' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700'"
                >
                    <template #details>
                        <div class="flex flex-col gap-1 mt-1">
                            <span class="flex items-center gap-1">
                                <EnvelopeIcon class="w-3 h-3" />
                                {{ teacher.user?.email || '-' }}
                            </span>
                            <span class="flex items-center gap-1">
                                <PhoneIcon class="w-3 h-3" />
                                {{ teacher.phone || '-' }}
                            </span>
                        </div>
                    </template>
                    <template #actions>
                        <button @click="openEditModal(teacher)" class="text-namira-teal font-bold text-sm">Edit</button>
                        <button @click="confirmDelete(teacher)" class="text-red-500 font-bold text-sm">Hapus</button>
                    </template>
                </MobileCard>
                
                <!-- Empty State Mobile -->
                <div v-if="teachers.data.length === 0" class="text-center py-10 text-gray-400">
                    <p>Tidak ada data guru.</p>
                </div>

                <!-- Mobile Pagination -->
                 <div v-if="teachers.links.length > 3" class="flex justify-center mt-4">
                     <div class="flex gap-1">
                        <template v-for="(link, k) in teachers.links" :key="k">
                            <Link 
                                v-if="link.url" 
                                :href="link.url" 
                                v-html="link.label"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                                :class="link.active ? 'bg-namira-teal text-white shadow-md' : 'bg-white text-gray-500'"
                            />
                        </template>
                     </div>
                </div>
            </div>

            <!-- Main Content Container (Desktop Table) -->
            <div v-else class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <!-- Teacher Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-white/50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-5 font-extrabold tracking-wider">Nama Lengkap</th>
                                <th class="px-6 py-5 font-extrabold tracking-wider">NIP</th>
                                <th class="px-6 py-5 font-extrabold tracking-wider">L/P</th>
                                <th class="px-6 py-5 font-extrabold tracking-wider">Kontak</th>
                                <th class="px-6 py-5 font-extrabold tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <!-- Empty State -->
                            <tr v-if="teachers.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center p-8">
                                         <div class="bg-blue-50 p-6 rounded-full mb-4">
                                            <UserIcon class="w-12 h-12 text-blue-400" />
                                         </div>
                                        <p class="font-bold text-lg text-gray-800 mb-1">Belum ada data guru</p>
                                        <p class="text-sm text-gray-500 max-w-xs">Data guru masih kosong. Silakan tambahkan guru baru untuk sekolah ini.</p>
                                    </div>
                                </td>
                            </tr>

                            <tr v-for="teacher in teachers.data" :key="teacher.id" class="group hover:bg-teal-50/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-full bg-gray-100 flex-shrink-0 overflow-hidden border-2 border-white shadow-sm group-hover:border-namira-teal/50 transition-colors">
                                            <img v-if="teacher.photo" :src="`/storage/${teacher.photo}`" class="w-full h-full object-cover">
                                            <div v-else class="w-full h-full flex items-center justify-center text-xs font-bold text-gray-400">
                                                {{ teacher.full_name?.substring(0,2).toUpperCase() }}
                                            </div>
                                        </div>
                                        <div>
                                            <Link :href="route('yayasan.teachers.show', teacher.id)" class="font-bold text-gray-800 text-base group-hover:text-namira-teal transition-colors hover:underline">
                                                {{ teacher.full_name }}
                                            </Link>
                                            <div class="text-xs text-gray-500 flex items-center gap-1">
                                                <EnvelopeIcon class="w-3 h-3 opacity-70" />
                                                {{ teacher.user?.email || 'Belum ada email' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-mono text-xs font-bold text-gray-600 bg-white border border-gray-200 px-2.5 py-1.5 rounded-lg shadow-sm">{{ teacher.nip || '-' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 text-[10px] font-extrabold uppercase rounded-full border shadow-sm" 
                                          :class="teacher.gender === 'L' ? 'bg-blue-50 text-blue-600 border-blue-100' : 'bg-pink-50 text-pink-600 border-pink-100'">
                                        {{ teacher.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-600 font-mono text-xs">
                                    {{ teacher.phone || '-' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
                                        <Link :href="route('yayasan.teachers.show', teacher.id)" class="p-2 rounded-xl text-gray-400 hover:text-namira-teal hover:bg-teal-50 transition-colors border border-transparent hover:border-teal-100" title="Lihat Detail">
                                            <EyeIcon class="w-4 h-4" />
                                        </Link>
                                        <button @click="openEditModal(teacher)" class="p-2 rounded-xl text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition-colors border border-transparent hover:border-amber-100" title="Edit Data">
                                            <PencilSquareIcon class="w-4 h-4" />
                                        </button>
                                        <button @click="confirmDelete(teacher)" class="p-2 rounded-xl text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors border border-transparent hover:border-red-100" title="Hapus Guru">
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="teachers.links.length > 3" class="p-6 flex justify-center border-t border-gray-100 bg-white/50">
                     <div class="flex gap-1">
                        <template v-for="(link, k) in teachers.links" :key="k">
                            <Link 
                                v-if="link.url" 
                                :href="link.url" 
                                v-html="link.label"
                                class="px-4 py-2 border rounded-xl text-sm font-bold transition-all"
                                :class="link.active ? 'bg-namira-teal text-white border-namira-teal shadow-lg shadow-namira-teal/30' : 'bg-white text-gray-600 hover:bg-gray-50 border-gray-200'"
                            />
                             <span v-else v-html="link.label" class="px-4 py-2 text-gray-400 text-sm font-medium"></span>
                        </template>
                     </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal (Premium Glassmorphism) -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-8 border border-gray-100 overflow-hidden transform transition-all scale-100">
                         <!-- Decorative Shape -->
                        <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-namira-teal/10 to-blue-50 rounded-bl-full -mr-10 -mt-10 pointer-events-none"></div>

                        <h3 class="text-2xl font-bold text-gray-900 mb-8 pb-4 border-b border-gray-100 relative z-10 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-namira-teal/10 flex items-center justify-center text-namira-teal">
                                <PencilSquareIcon v-if="isEditing" class="w-6 h-6" />
                                <PlusIcon v-else class="w-6 h-6" />
                            </div>
                            {{ isEditing ? 'Edit Data Guru' : 'Registrasi Guru Baru' }}
                        </h3>

                        <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-6 relative z-10">
                            <!-- Photo Upload -->
                           <div class="md:col-span-2 flex justify-center mb-4">
                                <div class="relative group cursor-pointer text-center">
                                    <div class="w-32 h-32 mx-auto rounded-full bg-gray-50 border-4 border-white shadow-lg flex items-center justify-center overflow-hidden hover:border-namira-teal transition-all group-hover:shadow-xl">
                                        <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover">
                                        <div v-else class="flex flex-col items-center justify-center text-gray-400 group-hover:text-namira-teal transition-colors">
                                            <CameraIcon class="w-10 h-10 mb-1" />
                                            <span class="text-[10px] font-bold uppercase tracking-wide">Upload Foto</span>
                                        </div>
                                    </div>
                                    <div class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="text-white text-xs font-bold">Ubah Foto</span>
                                    </div>
                                    <input type="file" @change="handlePhotoUpload" class="absolute inset-0 opacity-0 cursor-pointer w-32 mx-auto h-32" accept="image/*">
                                    <div class="text-xs text-red-500 mt-2 font-bold" v-if="form.errors.photo">{{ form.errors.photo }}</div>
                                </div>
                           </div>

                            <div class="md:col-span-2">
                                <InputLabel for="full_name" value="Nama Lengkap *" class="text-sm font-bold text-gray-700" />
                                <TextInput id="full_name" v-model="form.full_name" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" required placeholder="Contoh: Budi Santoso, S.Pd" />
                                <InputError :message="form.errors.full_name" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="nip" value="NIP / NUPTK" class="text-sm font-bold text-gray-700" />
                                <TextInput id="nip" v-model="form.nip" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="Nomor Induk Pegawai" />
                                <InputError :message="form.errors.nip" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="gender" value="Jenis Kelamin *" class="text-sm font-bold text-gray-700" />
                                <select id="gender" v-model="form.gender" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-gray-700 bg-gray-50/50">
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                                <InputError :message="form.errors.gender" class="mt-1" />
                            </div>

                             <div class="md:col-span-2">
                                <InputLabel for="phone" value="Nomor HP / WA" class="text-sm font-bold text-gray-700" />
                                <div class="relative mt-1.5">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <span class="text-gray-500 text-base font-bold">+62</span>
                                    </div>
                                    <TextInput id="phone" v-model="form.phone" class="w-full h-12 pl-14 pr-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="812-3456-7890" />
                                </div>
                                <InputError :message="form.errors.phone" class="mt-1" />
                            </div>

                            <div class="md:col-span-2 border-t border-gray-100 pt-5">
                                <InputLabel for="email" value="Email (Akun Login) *" class="text-sm font-bold text-gray-700" />
                                <TextInput id="email" v-model="form.email" type="email" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50" :disabled="isEditing" required placeholder="guru@namira.school" />
                                <p class="text-xs text-gray-400 mt-2 flex items-center gap-1" v-if="!isEditing">
                                    <InformationCircleIcon class="w-4 h-4" />
                                    Password default akan menggunakan NIP atau 'guru123'
                                </p>
                                <InputError :message="form.errors.email" class="mt-1" />
                            </div>

                            <div class="md:col-span-2 flex justify-end gap-3 pt-6 border-t border-gray-50">
                                <button type="button" @click="closeModal" class="px-6 py-2.5 text-gray-500 font-bold hover:bg-gray-50 rounded-xl transition-colors">Batal</button>
                                <PrimaryButton :disabled="form.processing" class="rounded-xl px-8 shadow-lg shadow-namira-teal/20">{{ isEditing ? 'Simpan Perubahan' : 'Registrasi Guru' }}</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Modal (Premium) -->
        <Teleport to="body">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showDeleteConfirm = false"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-sm w-full p-8 text-center border border-gray-100 transform transition-all scale-100">
                         <div class="mx-auto flex h-20 w-20 flex-shrink-0 items-center justify-center rounded-full bg-red-50 mb-6 animate-pulse">
                             <ExclamationTriangleIcon class="h-10 w-10 text-red-600" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Hapus Data Guru?</h3>
                        <p class="text-gray-500 mb-8 leading-relaxed">Guru <span class="font-bold text-gray-800">"{{ itemToDelete?.full_name }}"</span> akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.</p>
                        <div class="flex justify-center gap-4">
                            <button @click="showDeleteConfirm = false" class="px-6 py-2.5 bg-white border border-gray-200 rounded-xl font-bold text-gray-600 hover:bg-gray-50 transition-colors">Batal</button>
                            <button @click="deleteItem" class="px-6 py-2.5 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 shadow-lg shadow-red-500/30 transition-all hover:scale-105">Hapus Permanen</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
