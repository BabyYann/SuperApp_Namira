<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MobileAppShell from '@/Layouts/MobileAppShell.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    ClipboardDocumentCheckIcon, PlusIcon, PhotoIcon, CameraIcon,
    TrashIcon, ClockIcon, MapPinIcon, CalendarIcon, FolderIcon,
    CheckCircleIcon, XMarkIcon, ExclamationCircleIcon, SparklesIcon,
    DocumentTextIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    todayActivities: Array,
    historyLogs: Array,
    stats: Object,
    selectedDate: String,
    categories: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const isMobile = computed(() => typeof window !== 'undefined' && window.innerWidth < 768);

// Modal state for adding activity log
const showModal = ref(false);
const photoPreviewUrl = ref(null);
const fileInputRef = ref(null);

const form = useForm({
    title: '',
    category: 'tugas_tambahan',
    activity_date: props.selectedDate || new Date().toISOString().split('T')[0],
    activity_time: new Date().toTimeString().split(' ')[0].substring(0, 5),
    description: '',
    photo: null,
    location_name: '',
});

const openCreateModal = () => {
    form.reset();
    form.activity_date = new Date().toISOString().split('T')[0];
    form.activity_time = new Date().toTimeString().split(' ')[0].substring(0, 5);
    photoPreviewUrl.value = null;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        photoPreviewUrl.value = URL.createObjectURL(file);
    }
};

const submitForm = () => {
    form.post(route('employee.activity-logs.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
            photoPreviewUrl.value = null;
        }
    });
};

// Delete confirmation
const itemToDelete = ref(null);
const showDeleteConfirm = ref(false);
const deleteForm = useForm({});

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteConfirm.value = true;
};

const executeDelete = () => {
    if (!itemToDelete.value) return;
    deleteForm.delete(route('employee.activity-logs.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirm.value = false;
            itemToDelete.value = null;
        }
    });
};

// Photo Lightbox modal
const activePhotoUrl = ref(null);
const openPhotoLightbox = (url) => {
    activePhotoUrl.value = url;
};

const categoryBadgeClass = (catKey) => {
    switch (catKey) {
        case 'kebersihan': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'keamanan': return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'pemeliharaan': return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'layanan_admin': return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'piket': return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        case 'tugas_tambahan': return 'bg-teal-50 text-teal-700 border-teal-200';
        default: return 'bg-slate-100 text-slate-700 border-slate-200';
    }
};
</script>

<template>
    <Head title="Giat Tugas & Logbook Harian" />

    <component :is="isMobile ? MobileAppShell : AuthenticatedLayout">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight flex items-center gap-2">
                        <ClipboardDocumentCheckIcon class="w-6 h-6 text-namira-teal" />
                        Giat Tugas & Logbook Harian
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Catat dan dokumentasikan seluruh kegiatan kerja harian Anda secara real-time.</p>
                </div>
                <button
                    @click="openCreateModal"
                    type="button"
                    class="px-4 py-2.5 bg-gradient-to-r from-teal-600 to-emerald-600 text-white rounded-xl font-bold shadow-md shadow-teal-600/20 hover:from-teal-700 hover:to-emerald-700 active:scale-95 transition-all text-xs sm:text-sm flex items-center justify-center gap-2"
                >
                    <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                    <span>Catat Giat Tugas</span>
                </button>
            </div>
        </template>

        <div class="py-4 sm:py-6 max-w-5xl mx-auto space-y-6 px-3 sm:px-6">

            <!-- Summary Progress Banner -->
            <div class="bg-gradient-to-br from-slate-900 via-teal-950 to-slate-900 text-white rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-xl border border-teal-800/30 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-teal-500/10 rounded-full blur-2xl pointer-events-none"></div>
                
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 relative z-10">
                    <div class="space-y-1">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-teal-500/20 border border-teal-400/30 text-teal-300 text-[11px] font-bold uppercase tracking-wider">
                            <SparklesIcon class="w-3.5 h-3.5" />
                            Logbook Pegawai
                        </div>
                        <h3 class="text-lg sm:text-xl font-black text-white">
                            Halo, {{ user.name }}
                        </h3>
                        <p class="text-xs text-teal-100/70">
                            Dokumentasikan setiap aktivitas dan tugas tambahan Anda hari ini.
                        </p>
                    </div>

                    <!-- Progress Counter -->
                    <div class="flex items-center gap-3 bg-white/10 backdrop-blur-md px-4 py-3 rounded-2xl border border-white/15 self-stretch sm:self-auto justify-between sm:justify-start">
                        <div>
                            <p class="text-[10px] text-teal-200 uppercase font-bold tracking-wider">Giat Hari Ini</p>
                            <p class="text-2xl font-black text-white leading-none mt-1">{{ stats?.total_today || 0 }} <span class="text-xs font-normal text-teal-200/80">Kegiatan</span></p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-teal-500/20 border border-teal-400/30 flex items-center justify-center text-teal-300">
                            <CheckCircleIcon class="w-6 h-6" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Activity Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                        <CalendarIcon class="w-5 h-5 text-teal-600" />
                        Giat Tugas Hari Ini ({{ selectedDate }})
                    </h3>
                    <span class="text-xs text-gray-500 font-medium">{{ todayActivities?.length || 0 }} dicatat</span>
                </div>

                <!-- Empty State -->
                <div v-if="!todayActivities || todayActivities.length === 0" class="bg-white rounded-2xl border border-dashed border-gray-200 p-8 text-center space-y-3">
                    <div class="w-12 h-12 rounded-full bg-teal-50 border border-teal-100 mx-auto flex items-center justify-center text-teal-600">
                        <ClipboardDocumentCheckIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="font-bold text-gray-700 text-sm">Belum Ada Giat Tugas Hari Ini</p>
                        <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">Selesai mengerjakan tugas tambahan, pembersihan, perbaikan, atau tugas piket? Dokumentasikan sekarang!</p>
                    </div>
                    <button
                        @click="openCreateModal"
                        type="button"
                        class="px-4 py-2 bg-teal-600 text-white text-xs font-bold rounded-xl hover:bg-teal-700 transition inline-flex items-center gap-1.5"
                    >
                        <PlusIcon class="w-4 h-4" />
                        <span>Catat Giat Sekarang</span>
                    </button>
                </div>

                <!-- Activity Cards Grid -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="item in todayActivities"
                        :key="item.id"
                        class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 hover:shadow-md transition-all space-y-3 flex flex-col justify-between"
                    >
                        <div class="space-y-2">
                            <!-- Category Badge & Time -->
                            <div class="flex items-center justify-between gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide border" :class="categoryBadgeClass(item.category)">
                                    {{ categories[item.category] || item.category }}
                                </span>
                                <span class="text-[11px] font-mono text-gray-400 flex items-center gap-1">
                                    <ClockIcon class="w-3.5 h-3.5" />
                                    {{ item.activity_time ? item.activity_time.substring(0, 5) : '-' }} WIB
                                </span>
                            </div>

                            <!-- Title & Description -->
                            <div>
                                <h4 class="font-bold text-gray-800 text-sm leading-snug">{{ item.title }}</h4>
                                <p v-if="item.description" class="text-xs text-gray-600 mt-1 line-clamp-2">{{ item.description }}</p>
                            </div>

                            <!-- Photo Documentation Thumbnail -->
                            <div v-if="item.photo_path" class="pt-1">
                                <div
                                    @click="openPhotoLightbox('/' + item.photo_path)"
                                    class="relative h-36 rounded-xl overflow-hidden border border-gray-200 group cursor-pointer bg-gray-50"
                                >
                                    <img :src="'/' + item.photo_path" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                    <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-semibold gap-1">
                                        <PhotoIcon class="w-4 h-4" />
                                        <span>Perbesar Bukti Foto</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer / Actions -->
                        <div class="pt-2 border-t border-gray-50 flex items-center justify-between text-xs">
                            <span v-if="item.location_name" class="text-[11px] text-gray-400 flex items-center gap-1">
                                <MapPinIcon class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                                {{ item.location_name }}
                            </span>
                            <span v-else class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1">
                                <CheckCircleIcon class="w-3.5 h-3.5" />
                                Terpublikasi
                            </span>

                            <button
                                @click="confirmDelete(item)"
                                type="button"
                                class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                title="Hapus Giat Ini"
                            >
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Section -->
            <div v-if="historyLogs && historyLogs.length > 0" class="space-y-4 pt-4 border-t border-gray-100">
                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <DocumentTextIcon class="w-5 h-5 text-gray-500" />
                    Riwayat Giat Tugas Terakhir
                </h3>

                <div class="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-50 shadow-sm overflow-hidden">
                    <div
                        v-for="item in historyLogs"
                        :key="item.id"
                        class="p-4 hover:bg-slate-50/60 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                    >
                        <div class="flex items-start gap-3">
                            <!-- Small Photo or Category Icon -->
                            <div
                                v-if="item.photo_path"
                                @click="openPhotoLightbox('/' + item.photo_path)"
                                class="w-12 h-12 rounded-xl overflow-hidden border border-gray-200 shrink-0 cursor-pointer"
                            >
                                <img :src="'/' + item.photo_path" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="w-12 h-12 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                                <FolderIcon class="w-5 h-5" />
                            </div>

                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-gray-800 text-sm">{{ item.title }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase border" :class="categoryBadgeClass(item.category)">
                                        {{ categories[item.category] || item.category }}
                                    </span>
                                </div>
                                <p v-if="item.description" class="text-xs text-gray-500 line-clamp-1">{{ item.description }}</p>
                                <p class="text-[11px] text-gray-400 font-mono">
                                    {{ item.activity_date }} • {{ item.activity_time ? item.activity_time.substring(0, 5) : '-' }} WIB
                                </p>
                            </div>
                        </div>

                        <button
                            @click="confirmDelete(item)"
                            type="button"
                            class="self-end sm:self-center p-1.5 text-gray-400 hover:text-rose-600 rounded-lg"
                        >
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <!-- CREATE MODAL / DRAWER -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-gray-100 transform transition-all">
                    
                    <!-- Header -->
                    <div class="px-6 py-4 bg-gradient-to-r from-slate-900 to-teal-950 text-white flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <ClipboardDocumentCheckIcon class="w-5 h-5 text-teal-400" />
                            <h3 class="font-bold text-base">Form Giat Tugas Harian</h3>
                        </div>
                        <button @click="closeModal" class="text-gray-400 hover:text-white p-1 rounded-lg">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Form Body -->
                    <form @submit.prevent="submitForm" class="p-6 space-y-4">
                        
                        <!-- Judul Kegiatan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Judul / Nama Kegiatan <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                placeholder="Contoh: Menata Buku Perpustakaan, Patroli Parkir..."
                                class="w-full rounded-xl border-gray-200 text-sm focus:border-teal-600 focus:ring-teal-500/20"
                            />
                            <p v-if="form.errors.title" class="text-xs text-rose-600 mt-1 font-medium">{{ form.errors.title }}</p>
                        </div>

                        <!-- Kategori & Waktu Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    Kategori <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="form.category"
                                    required
                                    class="w-full rounded-xl border-gray-200 text-sm focus:border-teal-600 focus:ring-teal-500/20"
                                >
                                    <option v-for="(label, key) in categories" :key="key" :value="key">
                                        {{ label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.category" class="text-xs text-rose-600 mt-1 font-medium">{{ form.errors.category }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    Jam Pelaksanaan
                                </label>
                                <input
                                    v-model="form.activity_time"
                                    type="time"
                                    class="w-full rounded-xl border-gray-200 text-sm focus:border-teal-600 focus:ring-teal-500/20"
                                />
                            </div>
                        </div>

                        <!-- Photo Input / Camera -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                Foto Bukti Dokumentasi
                            </label>
                            
                            <div class="flex items-center gap-3">
                                <div
                                    @click="$refs.fileInputRef.click()"
                                    class="relative w-full h-32 rounded-2xl border-2 border-dashed border-gray-300 hover:border-teal-500 transition-colors bg-slate-50 hover:bg-teal-50/20 flex flex-col items-center justify-center cursor-pointer group overflow-hidden"
                                >
                                    <img v-if="photoPreviewUrl" :src="photoPreviewUrl" class="w-full h-full object-cover" />
                                    <div v-else class="flex flex-col items-center justify-center text-gray-400 group-hover:text-teal-600">
                                        <CameraIcon class="w-8 h-8 stroke-[1.5]" />
                                        <span class="text-xs font-bold mt-1">Ambil Foto / Upload Galeri</span>
                                        <span class="text-[10px] text-gray-400 mt-0.5">Format JPG/PNG maks 5MB</span>
                                    </div>
                                </div>
                                <input
                                    ref="fileInputRef"
                                    type="file"
                                    accept="image/*"
                                    capture="environment"
                                    class="hidden"
                                    @change="handleFileChange"
                                />
                            </div>
                            <p v-if="form.errors.photo" class="text-xs text-rose-600 font-medium">{{ form.errors.photo }}</p>
                        </div>

                        <!-- Deskripsi / Catatan Tambahan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Deskripsi / Catatan Ringkas
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                placeholder="Tuliskan detail pekerjaan yang diselesaikan (opsional)..."
                                class="w-full rounded-xl border-gray-200 text-sm focus:border-teal-600 focus:ring-teal-500/20 resize-none"
                            ></textarea>
                            <p v-if="form.errors.description" class="text-xs text-rose-600 mt-1 font-medium">{{ form.errors.description }}</p>
                        </div>

                        <!-- Location Name -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Lokasi Pelaksanaan
                            </label>
                            <input
                                v-model="form.location_name"
                                type="text"
                                placeholder="Contoh: Gedung SD Lantai 2, Area Parkir..."
                                class="w-full rounded-xl border-gray-200 text-sm focus:border-teal-600 focus:ring-teal-500/20"
                            />
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-200 transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2 bg-teal-600 text-white rounded-xl text-xs font-bold hover:bg-teal-700 shadow-md shadow-teal-600/30 transition disabled:opacity-50 flex items-center gap-1.5"
                            >
                                <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan & Publikasikan' }}</span>
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </Teleport>

        <!-- DELETE CONFIRM MODAL -->
        <Teleport to="body">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-sm w-full p-6 text-center space-y-4 shadow-xl border border-gray-100">
                    <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 mx-auto flex items-center justify-center">
                        <ExclamationCircleIcon class="w-7 h-7" />
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 text-sm">Hapus Catatan Giat Tugas?</h4>
                        <p class="text-xs text-gray-500 mt-1">Data dan dokumentasi foto ini akan dihapus dari logbook pribadi Anda.</p>
                    </div>
                    <div class="flex items-center justify-center gap-2 pt-2">
                        <button @click="showDeleteConfirm = false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl text-xs font-bold">Batal</button>
                        <button @click="executeDelete" :disabled="deleteForm.processing" class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-bold">Ya, Hapus</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- LIGHTBOX PHOTO MODAL -->
        <Teleport to="body">
            <div v-if="activePhotoUrl" @click="activePhotoUrl = null" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4 cursor-pointer">
                <div class="relative max-w-3xl max-h-[85vh] overflow-hidden rounded-2xl shadow-2xl">
                    <img :src="activePhotoUrl" class="w-full h-full object-contain max-h-[85vh]" />
                    <button @click.stop="activePhotoUrl = null" class="absolute top-3 right-3 p-2 bg-black/50 text-white rounded-full hover:bg-black">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </Teleport>

    </component>
</template>
