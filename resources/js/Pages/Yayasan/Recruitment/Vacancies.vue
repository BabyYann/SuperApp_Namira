<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    BriefcaseIcon, PlusIcon, MagnifyingGlassIcon, FunnelIcon, 
    PencilSquareIcon, TrashIcon, UserGroupIcon, ClockIcon, 
    BuildingOfficeIcon, CheckCircleIcon, XCircleIcon, EyeIcon
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    vacancies: Object,
    units: Array,
    filters: Object,
    categories: Object,
    types: Object,
});

const search = ref(props.filters.search || '');
const selectedUnit = ref(props.filters.unit || 'all');
const selectedStatus = ref(props.filters.status || '');

const handleFilter = () => {
    router.get(route('yayasan.job-vacancies.index'), {
        search: search.value,
        unit: selectedUnit.value,
        status: selectedStatus.value,
    }, { preserveState: true, replace: true });
};

// Create / Edit Modal
const modalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    title: '',
    unit_id: '',
    category: 'teacher',
    type: 'full_time',
    quota: 1,
    deadline: '',
    description: '',
    requirements: '',
    status: 'open',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    modalOpen.value = true;
};

const openEditModal = (vacancy) => {
    isEditing.value = true;
    editingId.value = vacancy.id;
    form.title = vacancy.title;
    form.unit_id = vacancy.unit_id || '';
    form.category = vacancy.category;
    form.type = vacancy.type;
    form.quota = vacancy.quota;
    form.deadline = vacancy.deadline ? dayjs(vacancy.deadline).format('YYYY-MM-DD') : '';
    form.description = vacancy.description;
    form.requirements = vacancy.requirements;
    form.status = vacancy.status;
    modalOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('yayasan.job-vacancies.update', editingId.value), {
            onSuccess: () => {
                modalOpen.value = false;
            }
        });
    } else {
        form.post(route('yayasan.job-vacancies.store'), {
            onSuccess: () => {
                modalOpen.value = false;
            }
        });
    }
};

const deleteVacancy = (vacancy) => {
    if (!confirm(`Hapus lowongan "${vacancy.title}"?`)) return;
    router.delete(route('yayasan.job-vacancies.destroy', vacancy.id));
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'Tanpa Batas Waktu';
    return dayjs(dateStr).format('DD MMM YYYY');
};
</script>

<template>
    <Head title="Kelola Lowongan Karir" />

    <AuthenticatedLayout>
        <div class="py-6 max-w-7xl mx-auto space-y-6 px-4 sm:px-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="font-extrabold text-2xl text-gray-900 leading-tight flex items-center gap-2">
                        <BriefcaseIcon class="w-7 h-7 text-namira-teal" />
                        <span>Kelola Lowongan Kerja Karir</span>
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Tambah, buka/tutup, dan kelola kualifikasi lowongan posisi di Yayasan & Unit Sekolah.
                    </p>
                </div>
                <button 
                    @click="openCreateModal"
                    class="px-5 py-2.5 bg-gradient-to-r from-namira-teal to-teal-700 text-white font-bold rounded-2xl shadow-xs hover:shadow-md transition-all flex items-center gap-2 text-xs cursor-pointer"
                >
                    <PlusIcon class="w-4 h-4" />
                    <span>Tambah Lowongan Baru</span>
                </button>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white rounded-3xl border border-gray-150 p-4 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <FunnelIcon class="w-4 h-4 text-namira-teal" />
                    <span>Filter Lowongan</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full sm:w-auto">
                    <div class="relative">
                        <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 absolute left-3 top-3" />
                        <input 
                            v-model="search"
                            @keyup.enter="handleFilter"
                            type="text" 
                            placeholder="Cari judul lowongan..." 
                            class="w-full pl-9 rounded-xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                        />
                    </div>

                    <div>
                        <select 
                            v-model="selectedUnit"
                            @change="handleFilter"
                            class="w-full rounded-xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                        >
                            <option value="all">Semua Unit Sekolah</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                        </select>
                    </div>

                    <div>
                        <select 
                            v-model="selectedStatus"
                            @change="handleFilter"
                            class="w-full rounded-xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                        >
                            <option value="">Semua Status</option>
                            <option value="open">Dibuka (Open)</option>
                            <option value="closed">Ditutup (Closed)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Vacancies Table -->
            <div class="bg-white rounded-3xl shadow-xs border border-gray-150 overflow-hidden">
                <div class="p-6">
                    <div v-if="!vacancies.data || vacancies.data.length === 0" class="text-center py-16 text-gray-400 border-2 border-dashed border-gray-100 rounded-2xl bg-gray-50/50">
                        <BriefcaseIcon class="w-10 h-10 text-gray-300 mx-auto mb-2" />
                        <h4 class="text-base font-bold text-gray-800">Belum Ada Lowongan Kerja</h4>
                        <p class="text-xs text-gray-500 mt-1">Klik tombol 'Tambah Lowongan Baru' di atas untuk membuat lowongan pekerjaan pertama.</p>
                    </div>

                    <div v-else class="overflow-x-auto rounded-2xl border border-gray-150">
                        <table class="w-full text-xs text-left text-gray-600">
                            <thead class="text-xs text-gray-700 uppercase bg-slate-50 border-b border-gray-150 font-bold">
                                <tr>
                                    <th class="px-5 py-4">Judul Lowongan & Unit</th>
                                    <th class="px-5 py-4">Kategori & Tipe</th>
                                    <th class="px-5 py-4 text-center">Kuota & Pelamar</th>
                                    <th class="px-5 py-4">Batas Akhir</th>
                                    <th class="px-5 py-4 text-center">Status</th>
                                    <th class="px-5 py-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="v in vacancies.data" :key="v.id" class="hover:bg-slate-50/60 transition-colors">
                                    <td class="px-5 py-4">
                                        <div class="font-extrabold text-gray-900 text-sm leading-snug">{{ v.title }}</div>
                                        <div class="text-[10px] text-teal-700 font-semibold mt-0.5">{{ v.unit?.name || 'Yayasan Namira' }}</div>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="font-bold text-gray-800">{{ categories[v.category] || v.category }}</div>
                                        <div class="text-[10px] text-gray-400 font-medium">{{ types[v.type] || v.type }}</div>
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-100 rounded-full text-xs font-extrabold text-slate-800">
                                            <UserGroupIcon class="w-3.5 h-3.5 text-teal-600" />
                                            <span>{{ v.applicants_count }} / {{ v.quota }} Pelamar</span>
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 font-bold text-gray-800">
                                        {{ formatDate(v.deadline) }}
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <span :class="[
                                            'px-2.5 py-1 rounded-full text-[10px] font-black uppercase inline-block shadow-xs',
                                            v.status === 'open' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                                        ]">
                                            {{ v.status === 'open' ? 'Dibuka' : 'Ditutup' }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button 
                                                @click="openEditModal(v)"
                                                class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-colors cursor-pointer"
                                                title="Edit Lowongan"
                                            >
                                                <PencilSquareIcon class="w-4 h-4" />
                                            </button>
                                            <button 
                                                @click="deleteVacancy(v)"
                                                class="p-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl transition-colors cursor-pointer"
                                                title="Hapus Lowongan"
                                            >
                                                <TrashIcon class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-base font-extrabold text-gray-900">
                        {{ isEditing ? 'Edit Lowongan Kerja' : 'Tambah Lowongan Kerja Baru' }}
                    </h3>
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Judul Posisi Lowongan <span class="text-rose-500">*</span></label>
                            <input v-model="form.title" type="text" required placeholder="Contoh: Guru Bahasa Inggris SD" class="w-full rounded-2xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Unit Sekolah / Yayasan</label>
                            <select v-model="form.unit_id" class="w-full rounded-2xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20">
                                <option value="">Kantor Yayasan Namira</option>
                                <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Kategori Posisi <span class="text-rose-500">*</span></label>
                            <select v-model="form.category" class="w-full rounded-2xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20">
                                <option value="teacher">Tenaga Pendidik (Guru)</option>
                                <option value="staff">Tenaga Kependidikan (Staf)</option>
                                <option value="operational">Operasional & Sarpar</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Tipe Pekerjaan <span class="text-rose-500">*</span></label>
                            <select v-model="form.type" class="w-full rounded-2xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20">
                                <option value="full_time">Penuh Waktu (Full Time)</option>
                                <option value="part_time">Paruh Waktu (Part Time)</option>
                                <option value="contract">Kontrak</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Kuota Diterima (Jumlah Orang) <span class="text-rose-500">*</span></label>
                            <input v-model="form.quota" type="number" min="1" required class="w-full rounded-2xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Batas Akhir Pendaftaran (Deadline)</label>
                            <input v-model="form.deadline" type="date" class="w-full rounded-2xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Status Lowongan <span class="text-rose-500">*</span></label>
                        <select v-model="form.status" class="w-full rounded-2xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20">
                            <option value="open">Dibuka (Bisa dilamar pelamar)</option>
                            <option value="closed">Ditutup (Pendaftaran dikunci)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Deskripsi Pekerjaan <span class="text-rose-500">*</span></label>
                        <textarea v-model="form.description" rows="4" required placeholder="Tuliskan tugas & rincian pekerjaan..." class="w-full rounded-2xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Kualifikasi & Persyaratan <span class="text-rose-500">*</span></label>
                        <textarea v-model="form.requirements" rows="4" required placeholder="Tuliskan kualifikasi (minimal pendidikan, jurusan, sertifikat, dll)..." class="w-full rounded-2xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl text-xs font-bold transition-colors">Batal</button>
                        <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-namira-teal text-white hover:bg-teal-700 rounded-xl text-xs font-bold shadow-xs transition-colors cursor-pointer">
                            {{ isEditing ? 'Simpan Perubahan' : 'Publikasikan Lowongan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
