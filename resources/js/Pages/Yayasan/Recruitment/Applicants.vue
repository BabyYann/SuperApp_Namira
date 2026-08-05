<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    UserGroupIcon, MagnifyingGlassIcon, FunnelIcon, EyeIcon, 
    PaperClipIcon, CheckCircleIcon, XCircleIcon, ClockIcon, 
    ChatBubbleLeftRightIcon, BoltIcon, AcademicCapIcon, TrashIcon,
    PhoneIcon, EnvelopeIcon, MapPinIcon
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    applicants: Object,
    vacancies: Array,
    stats: Object,
    filters: Object,
    statuses: Object,
});

const search = ref(props.filters.search || '');
const selectedVacancy = ref(props.filters.vacancy_id || 'all');
const selectedStatus = ref(props.filters.status || 'all');

const handleFilter = () => {
    router.get(route('yayasan.applicants.index'), {
        search: search.value,
        vacancy_id: selectedVacancy.value,
        status: selectedStatus.value,
    }, { preserveState: true, replace: true });
};

// Detail Drawer / Modal
const detailModalOpen = ref(false);
const activeApplicant = ref(null);
const activePdfUrl = ref(null);

const openDetailModal = (applicant) => {
    activeApplicant.value = applicant;
    activePdfUrl.value = applicant.cv_path ? '/' + applicant.cv_path : null;
    detailModalOpen.value = true;
};

// Update Status Form
const statusForm = useForm({
    selection_status: '',
    selection_notes: '',
});

const updateApplicantStatus = (statusKey) => {
    if (!activeApplicant.value) return;
    statusForm.selection_status = statusKey;
    statusForm.selection_notes = activeApplicant.value.selection_notes || '';
    
    statusForm.put(route('yayasan.applicants.update-status', activeApplicant.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (activeApplicant.value) {
                activeApplicant.value.selection_status = statusKey;
            }
        }
    });
};

const convertToEmployee = (applicant) => {
    if (!confirm(`Rekrut ${applicant.name} menjadi pegawai resmi Yayasan Namira? Akun User dan Profil Pegawai akan otomatis dibuat.`)) return;
    
    router.post(route('yayasan.applicants.convert', applicant.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            detailModalOpen.value = false;
        }
    });
};

const deleteApplicant = (applicant) => {
    if (!confirm(`Hapus data berkas pelamar ${applicant.name}?`)) return;
    router.delete(route('yayasan.applicants.destroy', applicant.id));
};

const statusBadgeClass = (status) => {
    switch (status) {
        case 'pending': return 'bg-amber-100 text-amber-800';
        case 'shortlisted': return 'bg-blue-100 text-blue-800';
        case 'interview': return 'bg-purple-100 text-purple-800';
        case 'accepted': return 'bg-emerald-100 text-emerald-800';
        case 'rejected': return 'bg-rose-100 text-rose-800';
        default: return 'bg-slate-100 text-slate-700';
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return dayjs(dateStr).format('DD MMM YYYY');
};

const getWaLink = (phone, name, title) => {
    let cleanPhone = phone ? phone.replace(/[^0-9]/g, '') : '';
    if (cleanPhone.startsWith('0')) {
        cleanPhone = '62' + cleanPhone.substring(1);
    }
    const text = encodeURIComponent(`Halo Bpk/Ibu ${name}, kami dari tim HRD Yayasan Namira menginformasikan terkait lamaran Anda untuk posisi ${title}.`);
    return `https://wa.me/${cleanPhone}?text=${text}`;
};
</script>

<template>
    <Head title="Daftar Pelamar Kerja" />

    <AuthenticatedLayout>
        <div class="py-6 max-w-7xl mx-auto space-y-6 px-4 sm:px-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="font-extrabold text-2xl text-gray-900 leading-tight flex items-center gap-2">
                        <UserGroupIcon class="w-7 h-7 text-namira-teal" />
                        <span>Daftar Pelamar Kerja & Seleksi</span>
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Verifikasi berkas CV pelamar, ubah status seleksi, dan rekrut pelamar diterima menjadi pegawai.
                    </p>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-6 gap-3">
                <div @click="selectedStatus = 'all'; handleFilter()" class="bg-white rounded-2xl p-3.5 border border-gray-150 shadow-xs cursor-pointer hover:border-namira-teal transition-all">
                    <div class="text-[10px] font-bold text-gray-400 uppercase">Total Pelamar</div>
                    <div class="text-xl font-black text-gray-900 mt-0.5">{{ stats.total }}</div>
                </div>

                <div @click="selectedStatus = 'pending'; handleFilter()" class="bg-white rounded-2xl p-3.5 border border-gray-150 shadow-xs cursor-pointer hover:border-amber-500 transition-all">
                    <div class="text-[10px] font-bold text-amber-600 uppercase">Menunggu</div>
                    <div class="text-xl font-black text-amber-700 mt-0.5">{{ stats.pending }}</div>
                </div>

                <div @click="selectedStatus = 'shortlisted'; handleFilter()" class="bg-white rounded-2xl p-3.5 border border-gray-150 shadow-xs cursor-pointer hover:border-blue-500 transition-all">
                    <div class="text-[10px] font-bold text-blue-600 uppercase">Lolos Berkas</div>
                    <div class="text-xl font-black text-blue-700 mt-0.5">{{ stats.shortlisted }}</div>
                </div>

                <div @click="selectedStatus = 'interview'; handleFilter()" class="bg-white rounded-2xl p-3.5 border border-gray-150 shadow-xs cursor-pointer hover:border-purple-500 transition-all">
                    <div class="text-[10px] font-bold text-purple-600 uppercase">Wawancara</div>
                    <div class="text-xl font-black text-purple-700 mt-0.5">{{ stats.interview }}</div>
                </div>

                <div @click="selectedStatus = 'accepted'; handleFilter()" class="bg-white rounded-2xl p-3.5 border border-gray-150 shadow-xs cursor-pointer hover:border-emerald-500 transition-all">
                    <div class="text-[10px] font-bold text-emerald-600 uppercase">Diterima</div>
                    <div class="text-xl font-black text-emerald-700 mt-0.5">{{ stats.accepted }}</div>
                </div>

                <div @click="selectedStatus = 'rejected'; handleFilter()" class="bg-white rounded-2xl p-3.5 border border-gray-150 shadow-xs cursor-pointer hover:border-rose-500 transition-all">
                    <div class="text-[10px] font-bold text-rose-600 uppercase">Ditolak</div>
                    <div class="text-xl font-black text-rose-700 mt-0.5">{{ stats.rejected }}</div>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white rounded-3xl border border-gray-150 p-4 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <FunnelIcon class="w-4 h-4 text-namira-teal" />
                    <span>Filter Pelamar</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full sm:w-auto">
                    <div class="relative">
                        <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 absolute left-3 top-3" />
                        <input 
                            v-model="search"
                            @keyup.enter="handleFilter"
                            type="text" 
                            placeholder="Cari nama, email, kode..." 
                            class="w-full pl-9 rounded-xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                        />
                    </div>

                    <div>
                        <select 
                            v-model="selectedVacancy"
                            @change="handleFilter"
                            class="w-full rounded-xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                        >
                            <option value="all">Semua Posisi Lowongan</option>
                            <option v-for="v in vacancies" :key="v.id" :value="v.id">{{ v.title }}</option>
                        </select>
                    </div>

                    <div>
                        <select 
                            v-model="selectedStatus"
                            @change="handleFilter"
                            class="w-full rounded-xl border-gray-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                        >
                            <option value="all">Semua Status Seleksi</option>
                            <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-3xl shadow-xs border border-gray-150 overflow-hidden">
                <div class="p-6">
                    <div v-if="!applicants.data || applicants.data.length === 0" class="text-center py-16 text-gray-400 border-2 border-dashed border-gray-100 rounded-2xl bg-gray-50/50">
                        <UserGroupIcon class="w-10 h-10 text-gray-300 mx-auto mb-2" />
                        <h4 class="text-base font-bold text-gray-800">Belum Ada Pelamar Kerja</h4>
                        <p class="text-xs text-gray-500 mt-1">Belum ada data lamaran kerja yang masuk untuk kriteria ini.</p>
                    </div>

                    <div v-else class="overflow-x-auto rounded-2xl border border-gray-150">
                        <table class="w-full text-xs text-left text-gray-600">
                            <thead class="text-xs text-gray-700 uppercase bg-slate-50 border-b border-gray-150 font-bold">
                                <tr>
                                    <th class="px-5 py-4">Kode & Nama Pelamar</th>
                                    <th class="px-5 py-4">Posisi & Unit Dilamar</th>
                                    <th class="px-5 py-4">Pendidikan & Jurusan</th>
                                    <th class="px-5 py-4">Tgl Melamar</th>
                                    <th class="px-5 py-4 text-center">Status Seleksi</th>
                                    <th class="px-5 py-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="a in applicants.data" :key="a.id" class="hover:bg-slate-50/60 transition-colors">
                                    <!-- Applicant Info -->
                                    <td class="px-5 py-4">
                                        <div class="font-mono text-[10px] text-gray-400 font-bold">{{ a.applicant_code }}</div>
                                        <div class="font-extrabold text-gray-900 text-sm leading-snug">{{ a.name }}</div>
                                        <div class="text-[10px] text-gray-500 font-medium">{{ a.email }} • {{ a.phone }}</div>
                                    </td>

                                    <!-- Vacancy -->
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-gray-900">{{ a.vacancy?.title || '-' }}</div>
                                        <div class="text-[10px] text-teal-700 font-semibold">{{ a.vacancy?.unit?.name || 'Yayasan Namira' }}</div>
                                    </td>

                                    <!-- Education -->
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-gray-800">{{ a.last_education }} - {{ a.major }}</div>
                                        <div class="text-[10px] text-gray-500">{{ a.institution }} (IPK: {{ a.gpa || '-' }})</div>
                                    </td>

                                    <!-- Date -->
                                    <td class="px-5 py-4 font-bold text-gray-800">
                                        {{ formatDate(a.created_at) }}
                                    </td>

                                    <!-- Status -->
                                    <td class="px-5 py-4 text-center">
                                        <span :class="[
                                            'px-2.5 py-1 rounded-full text-[10px] font-black uppercase inline-block shadow-xs',
                                            statusBadgeClass(a.selection_status)
                                        ]">
                                            {{ a.status_label }}
                                        </span>
                                        <div v-if="a.converted_to_user_id" class="text-[9px] font-bold text-emerald-600 mt-1">
                                            ✓ Official Employee
                                        </div>
                                    </td>

                                    <!-- Action -->
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Review Drawer / Modal -->
                                            <button 
                                                @click="openDetailModal(a)"
                                                class="px-3 py-1.5 bg-slate-900 hover:bg-namira-teal text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1 cursor-pointer"
                                            >
                                                <EyeIcon class="w-3.5 h-3.5" />
                                                <span>Review</span>
                                            </button>

                                            <!-- WA Link -->
                                            <a 
                                                :href="getWaLink(a.phone, a.name, a.vacancy?.title)" 
                                                target="_blank"
                                                class="p-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl transition-colors cursor-pointer"
                                                title="Hubungi via WhatsApp"
                                            >
                                                <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                            </a>

                                            <!-- Delete -->
                                            <button 
                                                @click="deleteApplicant(a)"
                                                class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl transition-colors cursor-pointer"
                                                title="Hapus Pelamar"
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

        <!-- APPLICANT REVIEW & DOCUMENT PREVIEW MODAL -->
        <div v-if="detailModalOpen && activeApplicant" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-5xl p-6 space-y-6 max-h-[95vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                    <div>
                        <span class="text-xs font-bold text-gray-400 font-mono">Kode Lamaran: {{ activeApplicant.applicant_code }}</span>
                        <h3 class="text-lg font-black text-gray-900">Review Lamaran — {{ activeApplicant.name }}</h3>
                    </div>
                    <button @click="detailModalOpen = false" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left: Details & Actions -->
                    <div class="lg:col-span-1 space-y-4">
                        <!-- Applicant Info Card -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-gray-200 space-y-3">
                            <div class="flex items-center gap-3">
                                <img v-if="activeApplicant.photo_path" :src="'/' + activeApplicant.photo_path" class="w-12 h-12 rounded-full object-cover border border-gray-200" />
                                <div v-else class="w-12 h-12 rounded-full bg-teal-100 text-teal-700 font-bold flex items-center justify-center text-base">
                                    {{ activeApplicant.name?.charAt(0) }}
                                </div>
                                <div>
                                    <h4 class="font-extrabold text-gray-900 text-sm">{{ activeApplicant.name }}</h4>
                                    <div class="text-xs text-teal-700 font-bold">{{ activeApplicant.vacancy?.title }}</div>
                                </div>
                            </div>

                            <div class="space-y-1.5 text-xs text-gray-600 pt-2 border-t border-gray-200">
                                <div><strong>Email:</strong> {{ activeApplicant.email }}</div>
                                <div><strong>No. WA:</strong> {{ activeApplicant.phone }}</div>
                                <div><strong>Gender / TTL:</strong> {{ activeApplicant.gender === 'L' ? 'Laki-Laki' : 'Perempuan' }}, {{ activeApplicant.birth_place }}, {{ formatDate(activeApplicant.birth_date) }}</div>
                                <div><strong>Alamat:</strong> {{ activeApplicant.address }}</div>
                                <div><strong>Pendidikan:</strong> {{ activeApplicant.last_education }} {{ activeApplicant.major }} ({{ activeApplicant.institution }})</div>
                                <div><strong>IPK/Nilai:</strong> {{ activeApplicant.gpa || '-' }}</div>
                            </div>
                        </div>

                        <!-- Status Selector Buttons -->
                        <div class="bg-white p-4 rounded-2xl border border-gray-200 space-y-3">
                            <h4 class="text-xs font-bold text-gray-700 uppercase">Ubah Status Seleksi:</h4>
                            
                            <div class="grid grid-cols-2 gap-2">
                                <button 
                                    @click="updateApplicantStatus('shortlisted')" 
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors"
                                >
                                    Lolos Berkas
                                </button>
                                <button 
                                    @click="updateApplicantStatus('interview')" 
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold bg-purple-50 text-purple-700 hover:bg-purple-100 transition-colors"
                                >
                                    Undang Wawancara
                                </button>
                                <button 
                                    @click="updateApplicantStatus('accepted')" 
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors"
                                >
                                    Diterima
                                </button>
                                <button 
                                    @click="updateApplicantStatus('rejected')" 
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 transition-colors"
                                >
                                    Ditolak
                                </button>
                            </div>
                        </div>

                        <!-- Convert to Employee Button -->
                        <div v-if="activeApplicant.selection_status === 'accepted'" class="pt-2">
                            <button 
                                v-if="!activeApplicant.converted_to_user_id"
                                @click="convertToEmployee(activeApplicant)"
                                class="w-full py-3 bg-gradient-to-r from-emerald-600 to-teal-700 text-white font-extrabold rounded-2xl text-xs shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <BoltIcon class="w-4 h-4" />
                                <span>⚡ Rekrut Jadi Pegawai Resmi</span>
                            </button>
                            <div v-else class="p-3 bg-emerald-50 text-emerald-800 rounded-2xl text-xs font-bold text-center border border-emerald-200">
                                ✓ Telah Direkrut Jadi Pegawai
                            </div>
                        </div>

                        <!-- Attached Files Links -->
                        <div class="space-y-2">
                            <h4 class="text-xs font-bold text-gray-700 uppercase">Berkas Lampiran Pelamar:</h4>
                            
                            <div class="flex flex-col gap-2">
                                <button 
                                    v-if="activeApplicant.cv_path"
                                    @click="activePdfUrl = '/' + activeApplicant.cv_path"
                                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-xs font-bold text-slate-800 flex items-center justify-between transition-colors"
                                >
                                    <span class="flex items-center gap-1.5"><PaperClipIcon class="w-4 h-4 text-teal-600" /> File CV / Resume</span>
                                    <span class="text-[10px] text-teal-700">Preview</span>
                                </button>

                                <button 
                                    v-if="activeApplicant.cover_letter_path"
                                    @click="activePdfUrl = '/' + activeApplicant.cover_letter_path"
                                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-xs font-bold text-slate-800 flex items-center justify-between transition-colors"
                                >
                                    <span class="flex items-center gap-1.5"><PaperClipIcon class="w-4 h-4 text-teal-600" /> Surat Lamaran</span>
                                    <span class="text-[10px] text-teal-700">Preview</span>
                                </button>

                                <button 
                                    v-if="activeApplicant.certificate_path"
                                    @click="activePdfUrl = '/' + activeApplicant.certificate_path"
                                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-xs font-bold text-slate-800 flex items-center justify-between transition-colors"
                                >
                                    <span class="flex items-center gap-1.5"><PaperClipIcon class="w-4 h-4 text-teal-600" /> Ijazah & Transkrip</span>
                                    <span class="text-[10px] text-teal-700">Preview</span>
                                </button>

                                <button 
                                    v-if="activeApplicant.ktp_path"
                                    @click="activePdfUrl = '/' + activeApplicant.ktp_path"
                                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-xs font-bold text-slate-800 flex items-center justify-between transition-colors"
                                >
                                    <span class="flex items-center gap-1.5"><PaperClipIcon class="w-4 h-4 text-teal-600" /> Scan KTP</span>
                                    <span class="text-[10px] text-teal-700">Preview</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right: PDF / Document Preview Container -->
                    <div class="lg:col-span-2 bg-slate-900 rounded-2xl overflow-hidden min-h-[500px] flex flex-col">
                        <div class="p-3 bg-slate-800 text-white text-xs font-bold flex items-center justify-between">
                            <span>Pratinjau Berkas Dokumen (PDF Preview)</span>
                            <a v-if="activePdfUrl" :href="activePdfUrl" target="_blank" class="text-teal-400 hover:underline text-[11px]">Buka di Tab Baru ↗</a>
                        </div>

                        <div class="flex-1 bg-slate-800 flex items-center justify-center p-2">
                            <iframe v-if="activePdfUrl" :src="activePdfUrl" class="w-full h-full min-h-[500px] rounded-xl bg-white"></iframe>
                            <div v-else class="text-slate-400 text-xs text-center p-8">
                                Pilih salah satu berkas lampiran di sebelah kiri untuk menampilkan pratinjau PDF.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
