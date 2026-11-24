<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import {
    BriefcaseIcon,
    UserGroupIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    EyeIcon,
    PaperClipIcon,
    CheckCircleIcon,
    ChatBubbleLeftRightIcon,
    BoltIcon,
    TrashIcon,
    XMarkIcon,
    BuildingOfficeIcon,
    ArrowTopRightOnSquareIcon,
    DocumentTextIcon,
    ExclamationTriangleIcon,
    PhotoIcon,
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    applicants: Object,
    vacancies: Array,
    stats: Object,
    canReviewApplicants: {
        type: Boolean,
        default: true,
    },
    canManageApplicants: {
        type: Boolean,
        default: false,
    },
    isUnitScoped: {
        type: Boolean,
        default: false,
    },
    activeUnitName: {
        type: String,
        default: null,
    },
    filters: Object,
    statuses: Object,
});

const search = ref(props.filters?.search || '');
const selectedVacancy = ref(props.filters?.vacancy_id || 'all');
const selectedStatus = ref(props.filters?.status || 'all');

const hasActiveFilter = computed(() => {
    return Boolean(search.value || selectedVacancy.value !== 'all' || selectedStatus.value !== 'all');
});

const handleFilter = () => {
    router.get(
        route('yayasan.applicants.index'),
        {
            search: search.value || undefined,
            vacancy_id: selectedVacancy.value === 'all' ? undefined : selectedVacancy.value,
            status: selectedStatus.value === 'all' ? undefined : selectedStatus.value,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    search.value = '';
    selectedVacancy.value = 'all';
    selectedStatus.value = 'all';
    handleFilter();
};

// Detail Review Modal
const detailModalOpen = ref(false);
const activeApplicant = ref(null);
const activeDoc = ref(null); // { label, url, isImage }
const selectionNotesInput = ref('');

const isImageUrl = (url) => {
    if (!url) return false;
    return /\.(jpg|jpeg|png|webp|gif)(\?.*)?$/i.test(url);
};

const getApplicantDocuments = (applicant) => {
    if (!applicant) return [];
    const docs = [
        { key: 'cv', label: 'CV / Resume', url: applicant.cv_url },
        { key: 'cover_letter', label: 'Surat Lamaran', url: applicant.cover_letter_url },
        { key: 'certificate', label: 'Ijazah & Transkrip', url: applicant.certificate_url },
        { key: 'health_cert', label: 'Surat Keterangan Sehat', url: applicant.health_cert_url },
        { key: 'family_card', label: 'Kartu Keluarga (KK)', url: applicant.family_card_url },
        { key: 'ktp', label: 'Scan KTP', url: applicant.ktp_url },
        { key: 'photo', label: 'Pas Foto 3x4', url: applicant.photo_url },
    ];
    return docs
        .filter((d) => Boolean(d.url))
        .map((d) => ({ ...d, isImage: isImageUrl(d.url) }));
};

const activeApplicantDocs = computed(() => getApplicantDocuments(activeApplicant.value));

const openDetailModal = (applicant) => {
    activeApplicant.value = applicant;
    selectionNotesInput.value = applicant.selection_notes || '';
    const docs = getApplicantDocuments(applicant);
    activeDoc.value = docs.length > 0 ? docs[0] : null;
    detailModalOpen.value = true;
};

// Update Status Form
const statusForm = useForm({
    selection_status: '',
    selection_notes: '',
});

const updateApplicantStatus = (statusKey) => {
    if (!activeApplicant.value || !props.canReviewApplicants) return;
    statusForm.selection_status = statusKey;
    statusForm.selection_notes = selectionNotesInput.value || '';

    statusForm.put(route('yayasan.applicants.update-status', activeApplicant.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (activeApplicant.value) {
                activeApplicant.value.selection_status = statusKey;
                activeApplicant.value.selection_notes = selectionNotesInput.value || '';
                activeApplicant.value.status_label = props.statuses?.[statusKey] || statusKey;
            }
        },
    });
};

const convertToEmployee = (applicant) => {
    if (!confirm(`Rekrut ${applicant.name} menjadi pegawai resmi Yayasan Namira? Akun User dan Profil Pegawai akan otomatis dibuat.`)) return;

    router.post(
        route('yayasan.applicants.convert', applicant.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                detailModalOpen.value = false;
            },
        }
    );
};

const deleteApplicant = (applicant) => {
    if (!confirm(`Hapus permanen data berkas pelamar ${applicant.name}?`)) return;
    router.delete(route('yayasan.applicants.destroy', applicant.id), {
        preserveScroll: true,
    });
};

const statusBadgeClass = (status) => {
    switch (status) {
        case 'pending': return 'bg-amber-100 text-amber-800 border border-amber-200';
        case 'shortlisted': return 'bg-blue-100 text-blue-800 border border-blue-200';
        case 'interview': return 'bg-purple-100 text-purple-800 border border-purple-200';
        case 'accepted': return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
        case 'rejected': return 'bg-rose-100 text-rose-800 border border-rose-200';
        default: return 'bg-slate-100 text-slate-700 border border-slate-200';
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return dayjs(dateStr).format('DD MMM YYYY');
};

const getWaLink = (applicant) => {
    let cleanPhone = applicant?.phone ? String(applicant.phone).replace(/[^0-9]/g, '') : '';
    if (cleanPhone.startsWith('0')) {
        cleanPhone = '62' + cleanPhone.substring(1);
    }
    const unitName = applicant?.vacancy?.unit?.name || 'Yayasan Namira';
    const title = applicant?.vacancy?.title || 'lowongan kerja';
    let msg = `Assalamu'alaikum Bpk/Ibu ${applicant?.name}, kami dari tim rekrutmen ${unitName} menghubungi terkait lamaran Anda untuk posisi ${title}.`;
    if (applicant?.selection_status === 'interview') {
        msg = `Assalamu'alaikum Bpk/Ibu ${applicant?.name}, selamat! Berkas lamaran Anda untuk posisi ${title} di ${unitName} lolos ke tahap wawancara. Mohon konfirmasi kehadiran Anda.`;
    } else if (applicant?.selection_status === 'accepted') {
        msg = `Assalamu'alaikum Bpk/Ibu ${applicant?.name}, Alhamdulillah Anda dinyatakan DITERIMA untuk posisi ${title} di ${unitName}. Mohon konfirmasi untuk tahap selanjutnya.`;
    }
    return `https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`;
};
</script>

<template>
    <Head title="Daftar Pelamar Kerja" />

    <div>
        <div class="py-2 md:py-6 max-w-7xl mx-auto space-y-5 px-0 sm:px-4 md:px-6">
            <!-- Header + Tab Switcher -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-4 sm:p-6 shadow-2xs space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-violet-50 text-violet-700 border border-violet-200/80 text-[10px] font-black uppercase tracking-wider">
                                <UserGroupIcon class="w-3.5 h-3.5" />
                                <span>Seleksi Pelamar</span>
                            </span>
                            <span
                                v-if="isUnitScoped && activeUnitName"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-extrabold"
                            >
                                <BuildingOfficeIcon class="w-3 h-3" />
                                <span>{{ activeUnitName }}</span>
                            </span>
                        </div>
                        <h2 class="font-black text-xl sm:text-2xl text-slate-900 leading-tight mt-2">
                            Daftar Pelamar Kerja &amp; Seleksi
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                            <template v-if="isUnitScoped && activeUnitName">
                                Tinjau berkas pelamar, catat hasil seleksi, dan hubungi kandidat untuk lowongan <strong class="text-teal-700">{{ activeUnitName }}</strong>.
                            </template>
                            <template v-else>
                                Verifikasi berkas lamaran, perbarui status seleksi, hubungi via WhatsApp, dan rekrut kandidat diterima menjadi pegawai.
                            </template>
                        </p>
                    </div>

                    <Link
                        :href="route('yayasan.job-vacancies.index')"
                        class="px-4 py-2.5 bg-slate-900 hover:bg-namira-teal text-white font-bold rounded-2xl transition-all inline-flex items-center justify-center gap-2 text-xs self-start md:self-auto"
                    >
                        <BriefcaseIcon class="w-4 h-4" />
                        <span>Kelola Lowongan</span>
                    </Link>
                </div>

                <!-- Segmented Tab Switcher -->
                <div class="grid grid-cols-2 gap-1.5 bg-slate-100 p-1 rounded-2xl max-w-md">
                    <Link
                        :href="route('yayasan.job-vacancies.index')"
                        class="py-2 px-3 rounded-xl text-xs font-bold text-center text-slate-600 hover:text-slate-900 transition flex items-center justify-center gap-1.5"
                    >
                        <BriefcaseIcon class="w-4 h-4" />
                        <span>Lowongan</span>
                    </Link>
                    <Link
                        :href="route('yayasan.applicants.index')"
                        class="py-2 px-3 rounded-xl text-xs font-extrabold text-center bg-white text-violet-800 shadow-2xs flex items-center justify-center gap-1.5"
                    >
                        <UserGroupIcon class="w-4 h-4" />
                        <span>Pelamar ({{ stats?.total || 0 }})</span>
                    </Link>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="p-3.5 sm:p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-bold flex items-center gap-2.5 shadow-2xs">
                <CheckCircleIcon class="w-5 h-5 text-emerald-600 shrink-0" />
                <span>{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error || $page.props.flash?.warning" class="p-3.5 sm:p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-bold flex items-center gap-2.5 shadow-2xs">
                <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 shrink-0" />
                <span>{{ $page.props.flash.error || $page.props.flash.warning }}</span>
            </div>

            <!-- Interactive Stats Bar -->
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-2.5">
                <button
                    type="button"
                    @click="selectedStatus = 'all'; handleFilter()"
                    class="text-left bg-white rounded-2xl p-3 border shadow-2xs cursor-pointer transition-all"
                    :class="selectedStatus === 'all' ? 'border-teal-500 ring-1 ring-teal-500/30' : 'border-slate-200/80 hover:border-teal-300'"
                >
                    <div class="text-[9px] sm:text-[10px] font-bold text-slate-400 uppercase truncate">Total</div>
                    <div class="text-lg sm:text-xl font-black text-slate-900 mt-0.5">{{ stats?.total || 0 }}</div>
                </button>
                <button
                    type="button"
                    @click="selectedStatus = 'pending'; handleFilter()"
                    class="text-left bg-white rounded-2xl p-3 border shadow-2xs cursor-pointer transition-all"
                    :class="selectedStatus === 'pending' ? 'border-amber-500 ring-1 ring-amber-500/30' : 'border-slate-200/80 hover:border-amber-300'"
                >
                    <div class="text-[9px] sm:text-[10px] font-bold text-amber-600 uppercase truncate">Menunggu</div>
                    <div class="text-lg sm:text-xl font-black text-amber-700 mt-0.5">{{ stats?.pending || 0 }}</div>
                </button>
                <button
                    type="button"
                    @click="selectedStatus = 'shortlisted'; handleFilter()"
                    class="text-left bg-white rounded-2xl p-3 border shadow-2xs cursor-pointer transition-all"
                    :class="selectedStatus === 'shortlisted' ? 'border-blue-500 ring-1 ring-blue-500/30' : 'border-slate-200/80 hover:border-blue-300'"
                >
                    <div class="text-[9px] sm:text-[10px] font-bold text-blue-600 uppercase truncate">Lolos Berkas</div>
                    <div class="text-lg sm:text-xl font-black text-blue-700 mt-0.5">{{ stats?.shortlisted || 0 }}</div>
                </button>
                <button
                    type="button"
                    @click="selectedStatus = 'interview'; handleFilter()"
                    class="text-left bg-white rounded-2xl p-3 border shadow-2xs cursor-pointer transition-all"
                    :class="selectedStatus === 'interview' ? 'border-purple-500 ring-1 ring-purple-500/30' : 'border-slate-200/80 hover:border-purple-300'"
                >
                    <div class="text-[9px] sm:text-[10px] font-bold text-purple-600 uppercase truncate">Wawancara</div>
                    <div class="text-lg sm:text-xl font-black text-purple-700 mt-0.5">{{ stats?.interview || 0 }}</div>
                </button>
                <button
                    type="button"
                    @click="selectedStatus = 'accepted'; handleFilter()"
                    class="text-left bg-white rounded-2xl p-3 border shadow-2xs cursor-pointer transition-all"
                    :class="selectedStatus === 'accepted' ? 'border-emerald-500 ring-1 ring-emerald-500/30' : 'border-slate-200/80 hover:border-emerald-300'"
                >
                    <div class="text-[9px] sm:text-[10px] font-bold text-emerald-600 uppercase truncate">Diterima</div>
                    <div class="text-lg sm:text-xl font-black text-emerald-700 mt-0.5">{{ stats?.accepted || 0 }}</div>
                </button>
                <button
                    type="button"
                    @click="selectedStatus = 'rejected'; handleFilter()"
                    class="text-left bg-white rounded-2xl p-3 border shadow-2xs cursor-pointer transition-all"
                    :class="selectedStatus === 'rejected' ? 'border-rose-500 ring-1 ring-rose-500/30' : 'border-slate-200/80 hover:border-rose-300'"
                >
                    <div class="text-[9px] sm:text-[10px] font-bold text-rose-600 uppercase truncate">Ditolak</div>
                    <div class="text-lg sm:text-xl font-black text-rose-700 mt-0.5">{{ stats?.rejected || 0 }}</div>
                </button>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-3.5 sm:p-4 shadow-2xs space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 text-[11px] font-extrabold text-slate-700 uppercase tracking-wider">
                        <FunnelIcon class="w-4 h-4 text-namira-teal" />
                        <span>Cari &amp; Filter Pelamar</span>
                    </div>
                    <button
                        v-if="hasActiveFilter"
                        type="button"
                        @click="resetFilters"
                        class="text-[11px] font-bold text-rose-600 hover:underline cursor-pointer"
                    >
                        Reset Filter
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <div class="relative">
                        <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
                        <input
                            v-model="search"
                            @keyup.enter="handleFilter"
                            type="text"
                            placeholder="Cari nama, jurusan, kode..."
                            class="w-full pl-9 pr-3 py-2 rounded-xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                        />
                    </div>

                    <div>
                        <select
                            v-model="selectedVacancy"
                            @change="handleFilter"
                            class="w-full py-2 rounded-xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                        >
                            <option value="all">Semua Posisi Lowongan</option>
                            <option v-for="v in vacancies" :key="v.id" :value="v.id">{{ v.title }}</option>
                        </select>
                    </div>

                    <div>
                        <select
                            v-model="selectedStatus"
                            @change="handleFilter"
                            class="w-full py-2 rounded-xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                        >
                            <option value="all">Semua Status Seleksi</option>
                            <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!applicants.data || applicants.data.length === 0" class="bg-white rounded-3xl border-2 border-dashed border-slate-200 p-10 sm:p-16 text-center space-y-2.5">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                    <UserGroupIcon class="w-7 h-7" />
                </div>
                <h4 class="text-base font-extrabold text-slate-800">Belum Ada Pelamar Kerja</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    Belum ada lamaran masuk yang cocok dengan filter ini. Bagikan tautan lowongan ke media sosial atau WhatsApp agar kandidat dapat melamar online.
                </p>
            </div>

            <!-- CONTENT LISTS -->
            <div v-else class="space-y-3.5">
                <!-- MOBILE CARD VIEW (< md) -->
                <div class="md:hidden space-y-3">
                    <article
                        v-for="a in applicants.data"
                        :key="`ma-${a.id}`"
                        class="bg-white rounded-3xl border border-slate-200/80 shadow-2xs p-4 space-y-3"
                    >
                        <div class="flex items-start gap-3">
                            <img
                                v-if="a.photo_url"
                                :src="a.photo_url"
                                :alt="a.name"
                                class="w-12 h-12 rounded-2xl object-cover border border-slate-200 shrink-0"
                            />
                            <div
                                v-else
                                class="w-12 h-12 rounded-2xl bg-gradient-to-br from-teal-100 to-emerald-100 text-teal-800 font-black flex items-center justify-center text-base shrink-0"
                            >
                                {{ a.name?.charAt(0) }}
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-mono text-[10px] text-slate-400 font-bold truncate">{{ a.applicant_code }}</span>
                                    <span :class="['px-2 py-0.5 rounded-full text-[9px] font-black uppercase shrink-0', statusBadgeClass(a.selection_status)]">
                                        {{ a.status_label || statuses[a.selection_status] || a.selection_status }}
                                    </span>
                                </div>
                                <h3 class="font-black text-sm text-slate-900 leading-snug mt-0.5 truncate">{{ a.name }}</h3>
                                <div class="text-[11px] font-bold text-teal-700 truncate">
                                    {{ a.vacancy?.title || '-' }}
                                </div>
                                <div class="text-[10px] text-slate-500 mt-0.5 truncate">
                                    {{ a.last_education }} {{ a.major }} • {{ a.institution }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-[11px] bg-slate-50 rounded-2xl px-3 py-2 border border-slate-100 text-slate-600">
                            <span class="font-mono font-semibold">{{ a.phone }}</span>
                            <span class="font-bold text-slate-700">{{ formatDate(a.created_at) }}</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="openDetailModal(a)"
                                class="flex-1 py-2.5 px-3 bg-slate-900 hover:bg-namira-teal text-white rounded-xl text-xs font-extrabold inline-flex items-center justify-center gap-1.5 transition cursor-pointer"
                            >
                                <EyeIcon class="w-4 h-4" />
                                <span>Review Berkas ({{ getApplicantDocuments(a).length }})</span>
                            </button>
                            <a
                                :href="getWaLink(a)"
                                target="_blank"
                                class="py-2.5 px-3 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-bold inline-flex items-center gap-1 transition"
                                title="Hubungi via WhatsApp"
                            >
                                <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                <span>WA</span>
                            </a>
                            <button
                                v-if="canManageApplicants"
                                type="button"
                                @click="deleteApplicant(a)"
                                class="p-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl transition cursor-pointer"
                                title="Hapus Pelamar"
                            >
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </article>
                </div>

                <!-- DESKTOP TABLE VIEW (md+) -->
                <div class="hidden md:block bg-white rounded-3xl shadow-2xs border border-slate-200/80 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left text-slate-600">
                            <thead class="text-[11px] text-slate-600 uppercase bg-slate-50 border-b border-slate-200/80 font-extrabold">
                                <tr>
                                    <th class="px-5 py-4">Kode &amp; Nama Pelamar</th>
                                    <th class="px-5 py-4">Posisi &amp; Unit</th>
                                    <th class="px-5 py-4">Pendidikan &amp; Jurusan</th>
                                    <th class="px-5 py-4">Tgl Melamar</th>
                                    <th class="px-5 py-4 text-center">Status Seleksi</th>
                                    <th class="px-5 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-for="a in applicants.data" :key="a.id" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <img
                                                v-if="a.photo_url"
                                                :src="a.photo_url"
                                                :alt="a.name"
                                                class="w-10 h-10 rounded-xl object-cover border border-slate-200 shrink-0"
                                            />
                                            <div v-else class="w-10 h-10 rounded-xl bg-teal-100 text-teal-800 font-black flex items-center justify-center text-sm shrink-0">
                                                {{ a.name?.charAt(0) }}
                                            </div>
                                            <div>
                                                <div class="font-mono text-[10px] text-slate-400 font-bold">{{ a.applicant_code }}</div>
                                                <div class="font-extrabold text-slate-900 text-sm leading-snug">{{ a.name }}</div>
                                                <div class="text-[10px] text-slate-500 font-medium">{{ a.email }} • {{ a.phone }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="font-bold text-slate-900">{{ a.vacancy?.title || '-' }}</div>
                                        <div class="text-[10px] text-teal-700 font-bold">{{ a.vacancy?.unit?.name || 'Yayasan Namira' }}</div>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="font-bold text-slate-800">{{ a.last_education }} - {{ a.major }}</div>
                                        <div class="text-[10px] text-slate-500">{{ a.institution }} (IPK: {{ a.gpa || '-' }})</div>
                                    </td>

                                    <td class="px-5 py-4 font-bold text-slate-800">
                                        {{ formatDate(a.created_at) }}
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <span :class="['px-2.5 py-1 rounded-full text-[10px] font-black uppercase inline-block', statusBadgeClass(a.selection_status)]">
                                            {{ a.status_label || statuses[a.selection_status] || a.selection_status }}
                                        </span>
                                        <div v-if="a.converted_to_user_id" class="text-[9px] font-extrabold text-emerald-600 mt-1">
                                            ✓ Sudah Jadi Pegawai
                                        </div>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <button
                                                type="button"
                                                @click="openDetailModal(a)"
                                                class="px-3 py-1.5 bg-slate-900 hover:bg-namira-teal text-white rounded-xl text-xs font-bold transition shadow-2xs inline-flex items-center gap-1 cursor-pointer"
                                            >
                                                <EyeIcon class="w-3.5 h-3.5" />
                                                <span>Review</span>
                                            </button>
                                            <a
                                                :href="getWaLink(a)"
                                                target="_blank"
                                                class="p-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl transition"
                                                title="Hubungi via WhatsApp"
                                            >
                                                <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                            </a>
                                            <button
                                                v-if="canManageApplicants"
                                                type="button"
                                                @click="deleteApplicant(a)"
                                                class="p-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl transition cursor-pointer"
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

                <!-- Pagination -->
                <div v-if="applicants.links && applicants.links.length > 3" class="pt-2">
                    <Pagination :links="applicants.links" />
                </div>
            </div>
        </div>

        <!-- APPLICANT REVIEW & DOCUMENT PREVIEW MODAL (Teleported above mobile nav) -->
        <Teleport to="body">
            <div v-if="detailModalOpen && activeApplicant" class="fixed inset-0 z-[110] flex items-end sm:items-center justify-center bg-slate-900/75 backdrop-blur-xs sm:p-4">
                <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-5xl max-h-[94vh] flex flex-col overflow-hidden">
                    <!-- Sticky Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 shrink-0 bg-white">
                        <div class="min-w-0 pr-3">
                            <span class="text-[11px] font-bold text-slate-400 font-mono block">{{ activeApplicant.applicant_code }}</span>
                            <h3 class="text-base sm:text-lg font-black text-slate-900 truncate">
                                Review Lamaran — {{ activeApplicant.name }}
                            </h3>
                        </div>
                        <button
                            type="button"
                            @click="detailModalOpen = false"
                            class="p-2 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 transition cursor-pointer shrink-0"
                        >
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Scrollable Body -->
                    <div class="flex-1 overflow-y-auto p-5">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            <!-- Left Column: Biodata, Status & Attachments -->
                            <div class="lg:col-span-1 space-y-4">
                                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-3">
                                    <div class="flex items-center gap-3">
                                        <img
                                            v-if="activeApplicant.photo_url"
                                            :src="activeApplicant.photo_url"
                                            class="w-14 h-14 rounded-2xl object-cover border border-slate-200 shrink-0"
                                        />
                                        <div v-else class="w-14 h-14 rounded-2xl bg-teal-100 text-teal-800 font-black flex items-center justify-center text-lg shrink-0">
                                            {{ activeApplicant.name?.charAt(0) }}
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="font-extrabold text-slate-900 text-sm truncate">{{ activeApplicant.name }}</h4>
                                            <div class="text-xs text-teal-700 font-bold truncate">{{ activeApplicant.vacancy?.title }}</div>
                                            <span :class="['mt-1 px-2 py-0.5 rounded-full text-[9px] font-black uppercase inline-block', statusBadgeClass(activeApplicant.selection_status)]">
                                                {{ activeApplicant.status_label || statuses[activeApplicant.selection_status] }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="space-y-1.5 text-xs text-slate-600 pt-2.5 border-t border-slate-200">
                                        <div><strong>Email:</strong> {{ activeApplicant.email }}</div>
                                        <div><strong>No. WA:</strong> <span class="font-mono font-bold text-slate-800">{{ activeApplicant.phone }}</span></div>
                                        <div><strong>Gender / TTL:</strong> {{ activeApplicant.gender === 'L' ? 'Laki-Laki' : 'Perempuan' }}, {{ activeApplicant.birth_place }}, {{ formatDate(activeApplicant.birth_date) }}</div>
                                        <div><strong>Alamat:</strong> {{ activeApplicant.address }}</div>
                                        <div><strong>Pendidikan:</strong> {{ activeApplicant.last_education }} {{ activeApplicant.major }} ({{ activeApplicant.institution }})</div>
                                        <div><strong>IPK / Nilai:</strong> {{ activeApplicant.gpa || '-' }}</div>
                                        <div v-if="activeApplicant.notes" class="pt-1.5 border-t border-slate-200">
                                            <strong>Catatan Pelamar:</strong>
                                            <p class="text-[11px] text-slate-600 mt-0.5 whitespace-pre-line">{{ activeApplicant.notes }}</p>
                                        </div>
                                    </div>

                                    <a
                                        :href="getWaLink(activeApplicant)"
                                        target="_blank"
                                        class="w-full py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-extrabold inline-flex items-center justify-center gap-2 transition"
                                    >
                                        <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                        <span>Hubungi Pelamar via WhatsApp</span>
                                    </a>
                                </div>

                                <!-- Review Status & Notes -->
                                <div v-if="canReviewApplicants" class="bg-white p-4 rounded-2xl border border-slate-200 space-y-3">
                                    <h4 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">Keputusan &amp; Catatan Seleksi</h4>

                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Catatan HRD / Jadwal Wawancara</label>
                                        <textarea
                                            v-model="selectionNotesInput"
                                            rows="2"
                                            placeholder="Contoh: Jadwal microteaching Kamis 10.00 WIB di SD Namira Kraksaan..."
                                            class="w-full rounded-xl border-slate-200 text-xs focus:border-namira-teal focus:ring-namira-teal/20"
                                        ></textarea>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <button
                                            type="button"
                                            :disabled="statusForm.processing"
                                            @click="updateApplicantStatus('shortlisted')"
                                            class="px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer border"
                                            :class="activeApplicant.selection_status === 'shortlisted'
                                                ? 'bg-blue-600 text-white border-blue-600'
                                                : 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100'"
                                        >
                                            Lolos Berkas
                                        </button>
                                        <button
                                            type="button"
                                            :disabled="statusForm.processing"
                                            @click="updateApplicantStatus('interview')"
                                            class="px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer border"
                                            :class="activeApplicant.selection_status === 'interview'
                                                ? 'bg-purple-600 text-white border-purple-600'
                                                : 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100'"
                                        >
                                            Wawancara
                                        </button>
                                        <button
                                            type="button"
                                            :disabled="statusForm.processing"
                                            @click="updateApplicantStatus('accepted')"
                                            class="px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer border"
                                            :class="activeApplicant.selection_status === 'accepted'
                                                ? 'bg-emerald-600 text-white border-emerald-600'
                                                : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'"
                                        >
                                            Diterima
                                        </button>
                                        <button
                                            type="button"
                                            :disabled="statusForm.processing"
                                            @click="updateApplicantStatus('rejected')"
                                            class="px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer border"
                                            :class="activeApplicant.selection_status === 'rejected'
                                                ? 'bg-rose-600 text-white border-rose-600'
                                                : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100'"
                                        >
                                            Ditolak
                                        </button>
                                    </div>
                                </div>

                                <!-- Convert to Employee Button -->
                                <div v-if="canManageApplicants && activeApplicant.selection_status === 'accepted'">
                                    <button
                                        v-if="!activeApplicant.converted_to_user_id"
                                        type="button"
                                        @click="convertToEmployee(activeApplicant)"
                                        class="w-full py-3 bg-gradient-to-r from-emerald-600 to-teal-700 text-white font-extrabold rounded-2xl text-xs shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer"
                                    >
                                        <BoltIcon class="w-4 h-4" />
                                        <span>Rekrut Jadi Pegawai Resmi</span>
                                    </button>
                                    <div v-else class="p-3 bg-emerald-50 text-emerald-800 rounded-2xl text-xs font-bold text-center border border-emerald-200">
                                        ✓ Telah Direkrut Menjadi Pegawai
                                    </div>
                                </div>

                                <!-- Attached Files List -->
                                <div class="space-y-2">
                                    <h4 class="text-xs font-extrabold text-slate-700 uppercase tracking-wider">
                                        Berkas Lampiran ({{ activeApplicantDocs.length }})
                                    </h4>

                                    <div v-if="activeApplicantDocs.length === 0" class="p-3 rounded-xl bg-slate-50 border border-dashed border-slate-200 text-xs text-slate-400 text-center">
                                        Tidak ada berkas yang dilampirkan.
                                    </div>

                                    <div v-else class="flex flex-col gap-1.5">
                                        <button
                                            v-for="doc in activeApplicantDocs"
                                            :key="doc.key"
                                            type="button"
                                            @click="activeDoc = doc"
                                            class="px-3 py-2.5 rounded-xl text-xs font-bold flex items-center justify-between transition cursor-pointer border"
                                            :class="activeDoc?.key === doc.key
                                                ? 'bg-teal-50 border-teal-500 text-teal-900'
                                                : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                                        >
                                            <span class="flex items-center gap-2 truncate">
                                                <PhotoIcon v-if="doc.isImage" class="w-4 h-4 text-teal-600 shrink-0" />
                                                <PaperClipIcon v-else class="w-4 h-4 text-teal-600 shrink-0" />
                                                <span class="truncate">{{ doc.label }}</span>
                                            </span>
                                            <span class="text-[10px] font-extrabold text-teal-700 shrink-0 ml-2">Lihat</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Smart Document / Image Preview -->
                            <div class="lg:col-span-2 bg-slate-900 rounded-2xl overflow-hidden min-h-[380px] sm:min-h-[520px] flex flex-col border border-slate-800">
                                <div class="px-4 py-3 bg-slate-800 text-white text-xs font-bold flex items-center justify-between gap-2">
                                    <span class="flex items-center gap-1.5 truncate">
                                        <DocumentTextIcon class="w-4 h-4 text-teal-400 shrink-0" />
                                        <span class="truncate">{{ activeDoc ? `Pratinjau: ${activeDoc.label}` : 'Pratinjau Berkas Dokumen' }}</span>
                                    </span>
                                    <a
                                        v-if="activeDoc?.url"
                                        :href="activeDoc.url"
                                        target="_blank"
                                        class="px-2.5 py-1 rounded-lg bg-teal-600 hover:bg-teal-500 text-white text-[11px] font-bold inline-flex items-center gap-1 shrink-0 transition"
                                    >
                                        <span>Buka / Unduh</span>
                                        <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                                    </a>
                                </div>

                                <div class="flex-1 bg-slate-900/80 flex items-center justify-center p-3">
                                    <template v-if="activeDoc?.url">
                                        <img
                                            v-if="activeDoc.isImage"
                                            :src="activeDoc.url"
                                            :alt="activeDoc.label"
                                            class="max-h-[520px] max-w-full object-contain rounded-xl shadow-lg bg-white"
                                        />
                                        <iframe
                                            v-else
                                            :src="activeDoc.url"
                                            class="w-full h-full min-h-[420px] sm:min-h-[520px] rounded-xl bg-white"
                                        ></iframe>
                                    </template>
                                    <div v-else class="text-slate-400 text-xs text-center p-8">
                                        Pilih salah satu berkas lampiran untuk melihat pratinjau dokumen.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
