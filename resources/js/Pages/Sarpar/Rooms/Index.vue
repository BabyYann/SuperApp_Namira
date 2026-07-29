<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { 
    PlusIcon, PencilSquareIcon, TrashIcon, BuildingOffice2Icon,
    ExclamationTriangleIcon, AcademicCapIcon, CubeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    rooms: Array,
    classrooms: Array,
});

const showModal = ref(false);
const isEditing = ref(false);
const showDeleteConfirm = ref(false);
const itemToDelete = ref(null);
const activeTab = ref('all');

const form = useForm({
    id: null,
    name: '',
    building: '',
    floor: '',
    capacity: '',
    description: '',
});

const deleteForm = useForm({});

// Combined locations
const allLocations = computed(() => {
    const roomsList = props.rooms.map(r => ({...r, type: 'room', editable: true}));
    const classroomsList = props.classrooms.map(c => ({...c, type: 'classroom', editable: false}));
    
    if (activeTab.value === 'rooms') return roomsList;
    if (activeTab.value === 'classrooms') return classroomsList;
    return [...classroomsList, ...roomsList];
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    showModal.value = true;
};

const openEditModal = (room) => {
    if (!room.editable) return;
    isEditing.value = true;
    form.id = room.id;
    form.name = room.name;
    form.building = room.building || '';
    form.floor = room.floor || '';
    form.capacity = room.capacity || '';
    form.description = room.description || '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('sarpar.rooms.update', form.id), { onSuccess: () => closeModal() });
    } else {
        form.post(route('sarpar.rooms.store'), { onSuccess: () => closeModal() });
    }
};

const confirmDelete = (room) => {
    if (!room.editable) return;
    itemToDelete.value = room;
    showDeleteConfirm.value = true;
};

const deleteItem = () => {
    if (!itemToDelete.value) return;
    deleteForm.delete(route('sarpar.rooms.destroy', itemToDelete.value.id), {
        onSuccess: () => { showDeleteConfirm.value = false; itemToDelete.value = null; },
    });
};
</script>

<template>
    <Head title="Data Ruangan & Lokasi" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                    Data Ruangan & Lokasi
                </h2>
                <p class="text-sm text-gray-500 mt-1">Daftar kelas dan ruangan inventaris</p>
            </div>
        </template>

        <div class="py-4 md:py-6 max-w-7xl mx-auto pb-20 space-y-5 md:space-y-6">

            <!-- 📱 MOBILE PWA VIEW (block md:hidden) -->
            <div class="block md:hidden -mx-4 -mt-4 space-y-4">
                <!-- Header Card Gradient -->
                <div class="bg-gradient-to-br from-[#009688] to-[#0f172a] px-4 pt-5 pb-6 text-white">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-[10px] font-extrabold tracking-widest uppercase text-teal-300">Modul Sarpar</p>
                            <h1 class="text-xl font-black leading-tight">Data Ruangan & Lokasi</h1>
                        </div>
                        <button
                            @click="openCreateModal"
                            class="px-3.5 py-2 bg-teal-500 hover:bg-teal-600 text-white font-extrabold text-xs rounded-xl shadow-lg flex items-center gap-1.5 active:scale-95 transition"
                        >
                            <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                            <span>Tambah</span>
                        </button>
                    </div>

                    <!-- Quick Stats Grid (3 Columns) -->
                    <div class="grid grid-cols-3 gap-2 text-center mt-3">
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-2 py-2">
                            <p class="text-lg font-black text-white leading-none">{{ rooms.length + classrooms.length }}</p>
                            <p class="text-[8px] text-teal-200 font-bold mt-1 uppercase">Total Lokasi</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-2 py-2">
                            <p class="text-lg font-black text-blue-300 leading-none">{{ classrooms.length }}</p>
                            <p class="text-[8px] text-blue-200 font-bold mt-1 uppercase">Kelas</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-2 py-2">
                            <p class="text-lg font-black text-emerald-300 leading-none">{{ rooms.length }}</p>
                            <p class="text-[8px] text-emerald-200 font-bold mt-1 uppercase">Ruangan</p>
                        </div>
                    </div>
                </div>

                <!-- Mobile Tab Pills Selector -->
                <div class="px-4">
                    <div class="flex bg-slate-200/70 p-1 rounded-2xl gap-1 text-xs">
                        <button @click="activeTab = 'all'" :class="['flex-1 py-2 font-black rounded-xl transition', activeTab === 'all' ? 'bg-white text-teal-700 shadow-sm' : 'text-slate-600']">
                            Semua
                        </button>
                        <button @click="activeTab = 'classrooms'" :class="['flex-1 py-2 font-black rounded-xl transition', activeTab === 'classrooms' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-600']">
                            Kelas ({{ classrooms.length }})
                        </button>
                        <button @click="activeTab = 'rooms'" :class="['flex-1 py-2 font-black rounded-xl transition', activeTab === 'rooms' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600']">
                            Ruangan ({{ rooms.length }})
                        </button>
                    </div>
                </div>

                <!-- Rooms Mobile Touch Cards Grid -->
                <div class="px-4 space-y-3">
                    <div v-if="allLocations.length === 0" class="bg-white rounded-2xl p-8 text-center border border-slate-100 shadow-sm">
                        <BuildingOffice2Icon class="w-10 h-10 mx-auto text-teal-300 mb-2" />
                        <p class="font-extrabold text-sm text-slate-800">Belum ada ruangan</p>
                    </div>

                    <div
                        v-for="loc in allLocations"
                        :key="`mob-${loc.type}-${loc.id}`"
                        class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm space-y-3 relative overflow-hidden"
                        :class="loc.type === 'classroom' ? 'border-l-4 border-l-blue-500' : 'border-l-4 border-l-teal-500'"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div :class="['p-2.5 rounded-xl', loc.type === 'classroom' ? 'bg-blue-50 text-blue-600' : 'bg-teal-50 text-teal-600']">
                                    <AcademicCapIcon v-if="loc.type === 'classroom'" class="w-5 h-5" />
                                    <BuildingOffice2Icon v-else class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-sm leading-tight">{{ loc.name }}</h3>
                                    <span :class="['text-[10px] px-2 py-0.5 rounded-md font-extrabold inline-block mt-0.5', loc.type === 'classroom' ? 'bg-blue-100 text-blue-800' : 'bg-teal-100 text-teal-800']">
                                        {{ loc.type === 'classroom' ? 'Kelas' : 'Ruangan Sarpar' }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="loc.editable" class="flex gap-1">
                                <button @click="openEditModal(loc)" class="p-1.5 rounded-xl text-amber-600 bg-amber-50 active:scale-95">
                                    <PencilSquareIcon class="w-4 h-4" />
                                </button>
                                <button @click="confirmDelete(loc)" class="p-1.5 rounded-xl text-red-600 bg-red-50 active:scale-95">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 bg-slate-50 p-2.5 rounded-xl text-[11px] text-slate-600 border border-slate-100 text-center">
                            <div>
                                <span class="text-[9px] font-extrabold text-slate-400 uppercase block">Gedung</span>
                                <span class="font-bold text-slate-800">{{ loc.building || '-' }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-extrabold text-slate-400 uppercase block">Lantai</span>
                                <span class="font-bold text-slate-800">{{ loc.floor || '-' }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-extrabold text-slate-400 uppercase block">Item Inventaris</span>
                                <span class="font-bold text-teal-700 flex items-center justify-center gap-1">
                                    <CubeIcon class="w-3 h-3" /> {{ loc.inventories_count || 0 }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END MOBILE VIEW -->

            <!-- 🖥️ DESKTOP VIEW (hidden md:block) -->
            <div class="hidden md:block space-y-6">
                <!-- Toolbar -->
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <!-- Tabs -->
                    <div class="flex bg-white/80 rounded-2xl p-1 border border-white/50 shadow-sm">
                        <button @click="activeTab = 'all'" :class="['px-4 py-2 rounded-xl font-bold transition-all', activeTab === 'all' ? 'bg-namira-teal text-white' : 'text-gray-600 hover:bg-gray-100']">
                            Semua ({{ rooms.length + classrooms.length }})
                        </button>
                        <button @click="activeTab = 'classrooms'" :class="['px-4 py-2 rounded-xl font-bold transition-all flex items-center gap-1.5', activeTab === 'classrooms' ? 'bg-namira-teal text-white' : 'text-gray-600 hover:bg-gray-100']">
                            <AcademicCapIcon class="w-4 h-4" /> Kelas ({{ classrooms.length }})
                        </button>
                        <button @click="activeTab = 'rooms'" :class="['px-4 py-2 rounded-xl font-bold transition-all flex items-center gap-1.5', activeTab === 'rooms' ? 'bg-namira-teal text-white' : 'text-gray-600 hover:bg-gray-100']">
                            <BuildingOffice2Icon class="w-4 h-4" /> Ruangan ({{ rooms.length }})
                        </button>
                    </div>

                    <button @click="openCreateModal" class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                        <PlusIcon class="w-5 h-5" /><span>Tambah Ruangan</span>
                    </button>
                </div>

                <!-- Info Banner -->
                <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 flex items-center gap-3">
                    <AcademicCapIcon class="w-8 h-8 text-blue-500" />
                    <div>
                        <p class="font-bold text-blue-800">Kelas dari Modul Akademik</p>
                        <p class="text-sm text-blue-600">Data kelas otomatis tersinkronisasi. Untuk menambah kelas baru, gunakan menu Akademik.</p>
                    </div>
                </div>

                <!-- Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-if="allLocations.length === 0" class="col-span-full p-12 text-center text-gray-400 bg-white/80 rounded-3xl">
                        <BuildingOffice2Icon class="w-16 h-16 mx-auto mb-2 opacity-50" />
                        <p class="font-bold text-lg">Belum ada data</p>
                    </div>

                    <div v-for="loc in allLocations" :key="`${loc.type}-${loc.id}`" :class="['bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 p-5 hover:shadow-md transition-shadow', loc.type === 'classroom' ? 'border-l-4 border-l-blue-400' : 'border-l-4 border-l-teal-400']">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div :class="['p-3 rounded-2xl', loc.type === 'classroom' ? 'bg-blue-100' : 'bg-teal-100']">
                                    <AcademicCapIcon v-if="loc.type === 'classroom'" class="w-6 h-6 text-blue-600" />
                                    <BuildingOffice2Icon v-else class="w-6 h-6 text-teal-600" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800">{{ loc.name }}</h3>
                                    <span :class="['text-xs px-2 py-0.5 rounded-full font-bold inline-flex items-center gap-1', loc.type === 'classroom' ? 'bg-blue-100 text-blue-700' : 'bg-teal-100 text-teal-700']">
                                        <AcademicCapIcon v-if="loc.type === 'classroom'" class="w-3 h-3" />
                                        <BuildingOffice2Icon v-else class="w-3 h-3" />
                                        {{ loc.type === 'classroom' ? 'Kelas' : 'Ruangan' }}
                                    </span>
                                </div>
                            </div>
                            
                            <div v-if="loc.editable" class="flex gap-1">
                                <button @click="openEditModal(loc)" class="p-2 rounded-xl text-gray-400 hover:text-amber-600 hover:bg-amber-50">
                                    <PencilSquareIcon class="w-4 h-4" />
                                </button>
                                <button @click="confirmDelete(loc)" class="p-2 rounded-xl text-gray-400 hover:text-red-600 hover:bg-red-50">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 space-y-2 text-sm text-gray-600">
                            <div v-if="loc.building" class="flex items-center gap-2">
                                <span class="text-gray-400">Gedung:</span> {{ loc.building }}
                            </div>
                            <div v-if="loc.floor" class="flex items-center gap-2">
                                <span class="text-gray-400">Lantai:</span> {{ loc.floor }}
                            </div>
                            <div v-if="loc.capacity" class="flex items-center gap-2">
                                <span class="text-gray-400">Kapasitas:</span> {{ loc.capacity }} orang
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <CubeIcon class="w-4 h-4 text-gray-400" />
                                <span class="text-sm text-gray-600">{{ loc.inventories_count || 0 }} item inventaris</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END DESKTOP VIEW -->
        </div>

        <!-- Create/Edit Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-8 border border-gray-100">
                        <h3 class="text-xl font-bold text-gray-900 mb-6">{{ isEditing ? 'Edit Ruangan' : 'Tambah Ruangan' }}</h3>
                        <form @submit.prevent="submit" class="space-y-5">
                            <div>
                                <InputLabel value="Nama Ruangan *" class="text-sm font-bold text-gray-700" />
                                <TextInput v-model="form.name" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="Lab Komputer" required />
                                <InputError :message="form.errors.name" class="mt-1" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel value="Gedung" class="text-sm font-bold text-gray-700" />
                                    <TextInput v-model="form.building" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="Gedung A" />
                                </div>
                                <div>
                                    <InputLabel value="Lantai" class="text-sm font-bold text-gray-700" />
                                    <TextInput v-model="form.floor" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="2" />
                                </div>
                            </div>
                            <div>
                                <InputLabel value="Kapasitas" class="text-sm font-bold text-gray-700" />
                                <TextInput v-model="form.capacity" type="number" min="0" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="40" />
                            </div>
                            <div>
                                <InputLabel value="Deskripsi" class="text-sm font-bold text-gray-700" />
                                <textarea v-model="form.description" rows="2" class="w-full mt-1.5 px-4 py-3 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" placeholder="Keterangan (opsional)"></textarea>
                            </div>
                            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-50">
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
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-50 mb-4"><ExclamationTriangleIcon class="h-8 w-8 text-red-500" /></div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Hapus Ruangan?</h3>
                        <p class="text-sm text-gray-500 mb-6">"{{ itemToDelete?.name }}" akan dihapus.</p>
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
