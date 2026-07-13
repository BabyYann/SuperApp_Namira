<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { InboxIcon, PaperClipIcon, CameraIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    approvals: Array,
});

const form = useForm({
    status: null,
    reason: null,
});

const rejectModalOpen = ref(false);
const selectedAttendance = ref(null);
const rejectionReason = ref('');

const approve = (attendance) => {
    if (!confirm('Setujui pengajuan ini?')) return;
    
    form.status = 'approved';
    form.reason = null;
    form.put(route('yayasan.attendance-approvals.update', attendance.id));
};

const openRejectModal = (attendance) => {
    selectedAttendance.value = attendance;
    rejectionReason.value = '';
    rejectModalOpen.value = true;
};

const submitReject = () => {
    if (!rejectionReason.value) {
        alert('Mohon isi alasan penolakan.');
        return;
    }

    form.status = 'rejected';
    form.reason = rejectionReason.value;
    form.put(route('yayasan.attendance-approvals.update', selectedAttendance.value.id), {
        onSuccess: () => {
            rejectModalOpen.value = false;
        }
    });
};

const typeLabel = (type) => {
    const map = {
        'business_trip': 'Dinas Luar',
        'sick': 'Sakit',
        'permit': 'Izin',
        'present': 'Hadir', // Should not appear here usually
    };
    return map[type] || type;
};
</script>

<template>
    <Head title="Penyetujuan Absensi" />

    <AuthenticatedLayout>
        <div class="py-6 max-w-7xl mx-auto space-y-6">
            
            <!-- Header -->
             <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                     <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Penyetujuan Absensi
                    </h2>
                     <p class="text-sm text-gray-500 mt-1">
                        Daftar pengajuan izin/sakit/dinas yang menunggu persetujuan.
                    </p>
                </div>
                 <Link :href="route('yayasan.dashboard')" class="text-sm font-bold text-gray-500 hover:text-namira-teal transition-colors">
                    Kembali ke Dashboard
                </Link>
            </div>

            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-gray-800">Daftar Pengajuan (Pending)</h3>
                    </div>

                    <div v-if="approvals.length === 0" class="text-center py-12 text-gray-400 border-2 border-dashed border-gray-100 rounded-2xl bg-gray-50/50">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white shadow-sm mb-4">
                             <InboxIcon class="w-8 h-8 text-gray-300" />
                        </div>
                        <p class="font-medium text-sm">Tidak ada pengajuan yang perlu disetujui saat ini.</p>
                    </div>

                    <div v-else class="overflow-x-auto rounded-xl border border-gray-100">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50/50 border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-4 font-bold">Nama Pegawai</th>
                                    <th class="px-6 py-4 font-bold">Tanggal / Jenis</th>
                                    <th class="px-6 py-4 font-bold">Keterangan</th>
                                    <th class="px-6 py-4 font-bold">Bukti</th>
                                    <th class="px-6 py-4 font-bold text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="att in approvals" :key="att.id" class="bg-white hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-gray-900 whitespace-nowrap">
                                        {{ att.user.name }}
                                        <div class="text-xs text-gray-400 font-normal mt-0.5">{{ att.user.role }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-800">{{ att.date }}</div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold"
                                            :class="{
                                                'bg-blue-100 text-blue-800': att.status === 'business_trip',
                                                'bg-purple-100 text-purple-800': att.status === 'permit' || att.status === 'sick'
                                            }">
                                            {{ typeLabel(att.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 italic text-gray-600">
                                        "{{ att.note || '-' }}"
                                    </td>
                                    <td class="px-6 py-4">
                                        <a v-if="att.permit_file" :href="`/storage/${att.permit_file}`" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors font-bold text-xs">
                                            <PaperClipIcon class="w-3.5 h-3.5" />
                                            Lihat Dokumen
                                        </a>
                                         <a v-else-if="att.check_in_photo" :href="`/storage/${att.check_in_photo}`" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100 transition-colors font-bold text-xs">
                                            <CameraIcon class="w-3.5 h-3.5" />
                                            Lihat Foto
                                        </a>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="approve(att)" class="text-white bg-green-500 hover:bg-green-600 shadow-lg shadow-green-500/30 font-bold rounded-xl text-xs px-4 py-2 text-center transition-all hover:-translate-y-0.5 active:scale-95">
                                                Setujui
                                            </button>
                                            <button @click="openRejectModal(att)" class="text-white bg-red-500 hover:bg-red-600 shadow-lg shadow-red-500/30 font-bold rounded-xl text-xs px-4 py-2 text-center transition-all hover:-translate-y-0.5 active:scale-95">
                                                Tolak
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

        <!-- Rejection Modal -->
        <div v-if="rejectModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Alasan Penolakan</h3>
                <textarea 
                    v-model="rejectionReason" 
                    rows="4" 
                    placeholder="Tulis alasan penolakan di sini..."
                    class="w-full border-gray-300 rounded-xl shadow-sm focus:border-red-500 focus:ring-red-500"
                ></textarea>
                <div class="flex justify-end gap-3 mt-6">
                    <button @click="rejectModalOpen = false" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg font-medium transition-colors">Batal</button>
                    <button @click="submitReject" class="px-4 py-2 bg-red-600 text-white hover:bg-red-700 rounded-lg font-bold transition-colors">Konfirmasi Tolak</button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
