<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { PencilSquareIcon, TrashIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    journal: Object,
});

const showDeleteConfirm = ref(false);
const deleteForm = useForm({});

const deleteJournal = () => {
    deleteForm.delete(route('yayasan.teaching-journal.destroy', props.journal.id), {
        onSuccess: () => { showDeleteConfirm.value = false; },
    });
};

const formatDate = (dateString) => {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'full' }).format(new Date(dateString));
};

const getStatusColor = (status) => {
    const colors = {
        present: 'bg-green-100 text-green-700',
        sick: 'bg-yellow-100 text-yellow-700',
        permission: 'bg-blue-100 text-blue-700',
        alpha: 'bg-red-100 text-red-700',
        late: 'bg-orange-100 text-orange-700',
    };
    return colors[status] || 'bg-gray-100 text-gray-700';
};

const getStatusLabel = (status) => {
    const labels = {
        present: 'Hadir',
        sick: 'Sakit',
        permission: 'Izin',
        alpha: 'Alpa',
        late: 'Terlambat',
    };
    return labels[status] || status;
};
</script>

<template>
    <Head title="Detail Jurnal Mengajar" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">Detail Jurnal</h2>
                <div class="flex gap-2">
                    <Link :href="route('yayasan.teaching-journal.edit', journal.id)" class="px-4 py-2 bg-amber-100 text-amber-700 border border-amber-200 rounded-xl text-sm font-bold hover:bg-amber-200 transition-colors flex items-center gap-2">
                        <PencilSquareIcon class="w-4 h-4" />
                        Edit
                    </Link>
                    <button @click="showDeleteConfirm = true" class="px-4 py-2 bg-red-100 text-red-700 border border-red-200 rounded-xl text-sm font-bold hover:bg-red-200 transition-colors flex items-center gap-2">
                        <TrashIcon class="w-4 h-4" />
                        Hapus
                    </button>
                    <Link :href="route('yayasan.teaching-journal.index', { date: journal.date })" class="px-4 py-2 bg-white border border-gray-300 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50">
                        Kembali
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Header Info -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                    <div>
                        <h3 class="text-2xl font-black text-gray-800">{{ journal.subject.name }}</h3>
                        <p class="text-lg text-gray-600 font-medium">{{ journal.classroom.name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-gray-500">{{ formatDate(journal.date) }}</p>
                        <p class="text-sm font-bold text-namira-teal">{{ journal.start_time.substring(0,5) }} - {{ journal.end_time.substring(0,5) }}</p>
                    </div>
                </div>

                <!-- TPs -->
                <div class="mb-6">
                    <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-3">Tujuan Pembelajaran</h4>
                    <div class="space-y-2">
                        <div v-for="tp in journal.learning_objectives" :key="tp.id" class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 bg-namira-teal text-white text-[10px] font-bold rounded">{{ tp.code }}</span>
                                <span class="text-xs font-bold text-gray-500">{{ tp.chapter?.title }}</span>
                            </div>
                            <p class="text-sm text-gray-700">{{ tp.description }}</p>
                        </div>
                        <div v-if="journal.custom_theme" class="p-3 bg-blue-50 rounded-xl border border-blue-100">
                            <span class="text-xs font-bold text-blue-600 block mb-1">Tema Khusus / Ad-hoc</span>
                            <p class="text-sm text-gray-700">{{ journal.custom_theme }}</p>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="journal.notes" class="mb-6">
                    <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-2">Catatan / Refleksi</h4>
                    <p class="text-gray-700 text-sm leading-relaxed bg-yellow-50 p-4 rounded-xl border border-yellow-100">
                        {{ journal.notes }}
                    </p>
                </div>

                <!-- Photo -->
                <div v-if="journal.photo_path" class="mb-6">
                    <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-3">Bukti Kegiatan</h4>
                    <div class="rounded-xl overflow-hidden border border-gray-200">
                        <img :src="'/storage/' + journal.photo_path" alt="Bukti Kegiatan" class="w-full h-auto object-cover max-h-96">
                    </div>
                </div>
            </div>

            <!-- Attendance List -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-lg text-gray-800 mb-4">Presensi Siswa</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-3 rounded-tl-xl">Nama Siswa</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 rounded-tr-xl">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="att in journal.attendance" :key="att.id" class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ att.student.full_name }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold capitalize" :class="getStatusColor(att.status)">
                                        {{ getStatusLabel(att.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 italic">{{ att.note || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Delete Confirm Modal -->
        <Teleport to="body">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showDeleteConfirm = false"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 text-center border border-gray-100">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-50 mb-4">
                            <ExclamationTriangleIcon class="h-8 w-8 text-red-500" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Hapus Jurnal?</h3>
                        <p class="text-sm text-gray-500 mb-6">Jurnal ini akan dihapus permanen dan tidak bisa dikembalikan.</p>
                        <div class="flex justify-center gap-3">
                            <button @click="showDeleteConfirm = false" class="px-5 py-2.5 bg-white border border-gray-200 rounded-xl font-bold text-gray-600 hover:bg-gray-50">Batal</button>
                            <button @click="deleteJournal" :disabled="deleteForm.processing" class="px-5 py-2.5 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 shadow-lg shadow-red-500/30">Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
