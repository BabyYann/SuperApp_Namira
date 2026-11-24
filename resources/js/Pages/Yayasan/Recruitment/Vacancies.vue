<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import { compressImageFile } from '@/Utils/imageCompressor';
import {
    BriefcaseIcon,
    PlusIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    PencilSquareIcon,
    TrashIcon,
    UserGroupIcon,
    ClockIcon,
    BuildingOfficeIcon,
    CheckCircleIcon,
    XCircleIcon,
    PhotoIcon,
    XMarkIcon,
    ShareIcon,
    ClipboardDocumentCheckIcon,
    ClipboardIcon,
    ArrowTopRightOnSquareIcon,
    ArrowPathIcon,
    PhoneIcon,
    MapPinIcon,
    ExclamationTriangleIcon,
    LockClosedIcon,
    LockOpenIcon,
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    vacancies: Object,
    units: Array,
    stats: {
        type: Object,
        default: () => ({ total: 0, open: 0, closed: 0, applicants: 0 }),
    },
    isUnitScoped: {
        type: Boolean,
        default: false,
    },
    activeUnitId: {
        type: [Number, String],
        default: null,
    },
    activeUnitName: {
        type: String,
        default: null,
    },
    canWrite: {
        type: Boolean,
        default: true,
    },
    filters: Object,
    categories: Object,
    types: Object,
});

const search = ref(props.filters?.search || '');
const selectedUnit = ref(props.filters?.unit || 'all');
const selectedStatus = ref(props.filters?.status || '');
const copiedId = ref(null);
const lightboxPoster = ref(null);

const hasActiveFilter = computed(() => {
    return Boolean(search.value || (selectedStatus.value && selectedStatus.value !== '') || (!props.isUnitScoped && selectedUnit.value !== 'all'));
});

const handleFilter = () => {
    router.get(
        route('yayasan.job-vacancies.index'),
        {
            search: search.value || undefined,
            unit: props.isUnitScoped || selectedUnit.value === 'all' ? undefined : selectedUnit.value,
            status: selectedStatus.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    search.value = '';
    selectedUnit.value = 'all';
    selectedStatus.value = '';
    handleFilter();
};

// Create / Edit Modal
const modalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const posterPreview = ref(null);
const existingPosterUrl = ref(null);
const isCompressingPoster = ref(false);

const form = useForm({
    title: '',
    unit_id: props.isUnitScoped ? (props.activeUnitId || '') : '',
    poster: null,
    remove_poster: false,
    category: 'teacher',
    type: 'full_time',
    quota: 1,
    deadline: '',
    contact_whatsapp: '',
    placement_address: '',
    description: '',
    requirements: '',
    status: 'open',
});

const willHavePoster = computed(() => {
    return Boolean(form.poster || (!form.remove_poster && existingPosterUrl.value));
});

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    existingPosterUrl.value = null;
    posterPreview.value = null;
    form.reset();
    form.clearErrors();
    form.poster = null;
    form.remove_poster = false;
    if (props.isUnitScoped && props.activeUnitId) {
        form.unit_id = props.activeUnitId;
    }
    modalOpen.value = true;
};

const openEditModal = (vacancy) => {
    isEditing.value = true;
    editingId.value = vacancy.id;
    form.clearErrors();
    form.title = vacancy.title || '';
    form.unit_id = vacancy.unit_id || '';
    form.poster = null;
    form.remove_poster = false;
    form.category = vacancy.category || 'teacher';
    form.type = vacancy.type || 'full_time';
    form.quota = vacancy.quota || 1;
    form.deadline = vacancy.deadline ? dayjs(vacancy.deadline).format('YYYY-MM-DD') : '';
    form.contact_whatsapp = vacancy.contact_whatsapp || '';
    form.placement_address = vacancy.placement_address || '';
    form.description = vacancy.description || '';
    form.requirements = vacancy.requirements || '';
    form.status = vacancy.status || 'open';
    existingPosterUrl.value = vacancy.poster_url || null;
    posterPreview.value = vacancy.poster_url || null;
    modalOpen.value = true;
};

const onPosterSelected = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    form.remove_poster = false;
    try {
        isCompressingPoster.value = true;
        // Keep high clarity (1600px, 0.86) so poster text stays crisp while fast to upload on mobile
        const compressed = await compressImageFile(file, 1600, 0.86);
        form.poster = compressed;
        posterPreview.value = URL.createObjectURL(compressed);
    } catch (err) {
        console.warn('Poster compression fallback:', err);
        form.poster = file;
        posterPreview.value = URL.createObjectURL(file);
    } finally {
        isCompressingPoster.value = false;
    }
};

const removeSelectedPoster = () => {
    form.poster = null;
    posterPreview.value = null;
    if (existingPosterUrl.value) {
        form.remove_poster = true;
    }
};

const submitForm = () => {
    if (isEditing.value) {
        form.transform((data) => ({
            ...data,
            _method: 'put',
            remove_poster: data.remove_poster ? 1 : 0,
        })).post(route('yayasan.job-vacancies.update', editingId.value), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                modalOpen.value = false;
                form.reset('poster', 'remove_poster');
            },
        });
    } else {
        form.transform((data) => ({
            ...data,
            remove_poster: 0,
        })).post(route('yayasan.job-vacancies.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                modalOpen.value = false;
                form.reset();
            },
        });
    }
};

const toggleVacancyStatus = (vacancy) => {
    const actionText = vacancy.status === 'open' ? 'menutup' : 'membuka kembali';
    if (!confirm(`Yakin ingin ${actionText} lowongan "${vacancy.title}"?`)) return;
    router.patch(route('yayasan.job-vacancies.toggle-status', vacancy.id), {}, {
        preserveScroll: true,
    });
};

const deleteVacancy = (vacancy) => {
    if (!confirm(`Hapus permanen lowongan "${vacancy.title}" beserta seluruh data pelamarnya?`)) return;
    router.delete(route('yayasan.job-vacancies.destroy', vacancy.id), {
        preserveScroll: true,
    });
};

const getPublicUrl = (vacancy) => {
    try {
        return route('careers.show', vacancy.slug);
    } catch {
        return `${window.location.origin}/karir/${vacancy.slug}`;
    }
};

const copyPublicLink = async (vacancy) => {
    const url = getPublicUrl(vacancy);
    try {
        await navigator.clipboard.writeText(url);
        copiedId.value = vacancy.id;
        setTimeout(() => {
            if (copiedId.value === vacancy.id) copiedId.value = null;
        }, 2200);
    } catch {
        window.prompt('Salin tautan lowongan:', url);
    }
};

const getShareWaUrl = (vacancy) => {
    const unitName = vacancy.unit?.name || 'Yayasan Namira';
    const deadlineText = vacancy.deadline ? `\nBatas Pendaftaran: ${formatDate(vacancy.deadline)}` : '';
    const link = getPublicUrl(vacancy);
    const msg = `Assalamu'alaikum, sedang dibuka lowongan *${vacancy.title}* di *${unitName}*.${deadlineText}\n\nLihat poster, persyaratan, & daftar online di:\n${link}`;
    return `https://wa.me/?text=${encodeURIComponent(msg)}`;
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'Tanpa Batas Waktu';
    return dayjs(dateStr).format('DD MMM YYYY');
};

const isDeadlinePast = (dateStr) => {
    if (!dateStr) return false;
    return dayjs(dateStr).endOf('day').isBefore(dayjs());
};
</script>

<template>
    <Head title="Kelola Lowongan Karir" />

    <div>
        <div class="py-2 md:py-6 max-w-7xl mx-auto space-y-5 px-0 sm:px-4 md:px-6">
            <!-- Header + Navigation Tabs -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-4 sm:p-6 shadow-2xs space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-teal-50 text-teal-700 border border-teal-200/80 text-[10px] font-black uppercase tracking-wider">
                                <BriefcaseIcon class="w-3.5 h-3.5" />
                                <span>Rekrutmen &amp; Karir</span>
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
                            Kelola Lowongan Kerja
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                            <template v-if="isUnitScoped && activeUnitName">
                                Upload poster &amp; publikasikan lowongan kerja khusus unit <strong class="text-teal-700">{{ activeUnitName }}</strong> langsung dari HP atau komputer.
                            </template>
                            <template v-else>
                                Upload poster lowongan, buka/tutup pendaftaran, dan kelola posisi terbuka untuk seluruh Yayasan &amp; Unit Sekolah.
                            </template>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            href="/karir"
                            target="_blank"
                            class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl transition-all inline-flex items-center gap-1.5 text-xs"
                        >
                            <ArrowTopRightOnSquareIcon class="w-4 h-4 text-slate-600" />
                            <span>Portal Karir</span>
                        </a>
                        <button
                            v-if="canWrite"
                            type="button"
                            @click="openCreateModal"
                            class="flex-1 sm:flex-none justify-center px-4 sm:px-5 py-2.5 bg-gradient-to-r from-namira-teal to-teal-700 text-white font-extrabold rounded-2xl shadow-sm hover:shadow-md active:scale-95 transition-all inline-flex items-center gap-2 text-xs cursor-pointer"
                        >
                            <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                            <span>Tambah Lowongan</span>
                        </button>
                    </div>
                </div>

                <!-- Segmented Tab Switcher (Lowongan vs Pelamar) -->
                <div class="grid grid-cols-2 gap-1.5 bg-slate-100 p-1 rounded-2xl max-w-md">
                    <Link
                        :href="route('yayasan.job-vacancies.index')"
                        class="py-2 px-3 rounded-xl text-xs font-extrabold text-center bg-white text-teal-800 shadow-2xs flex items-center justify-center gap-1.5"
                    >
                        <BriefcaseIcon class="w-4 h-4" />
                        <span>Lowongan ({{ stats.total }})</span>
                    </Link>
                    <Link
                        :href="route('yayasan.applicants.index')"
                        class="py-2 px-3 rounded-xl text-xs font-bold text-center text-slate-600 hover:text-slate-900 transition flex items-center justify-center gap-1.5"
                    >
                        <UserGroupIcon class="w-4 h-4" />
                        <span>Pelamar ({{ stats.applicants }})</span>
                    </Link>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="p-3.5 sm:p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-bold flex items-center gap-2.5 shadow-2xs">
                <CheckCircleIcon class="w-5 h-5 text-emerald-600 shrink-0" />
                <span>{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="p-3.5 sm:p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs font-bold flex items-center gap-2.5 shadow-2xs">
                <ExclamationTriangleIcon class="w-5 h-5 text-rose-600 shrink-0" />
                <span>{{ $page.props.flash.error }}</span>
            </div>

            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <button
                    type="button"
                    @click="selectedStatus = ''; handleFilter()"
                    class="text-left bg-white rounded-2xl p-3.5 border transition-all shadow-2xs cursor-pointer"
                    :class="selectedStatus === '' ? 'border-teal-500 ring-1 ring-teal-500/30' : 'border-slate-200/80 hover:border-teal-300'"
                >
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Lowongan</div>
                    <div class="text-xl sm:text-2xl font-black text-slate-900 mt-0.5">{{ stats.total }}</div>
                </button>
                <button
                    type="button"
                    @click="selectedStatus = 'open'; handleFilter()"
                    class="text-left bg-white rounded-2xl p-3.5 border transition-all shadow-2xs cursor-pointer"
                    :class="selectedStatus === 'open' ? 'border-emerald-500 ring-1 ring-emerald-500/30' : 'border-slate-200/80 hover:border-emerald-300'"
                >
                    <div class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Sedang Dibuka</div>
                    <div class="text-xl sm:text-2xl font-black text-emerald-700 mt-0.5">{{ stats.open }}</div>
                </button>
                <button
                    type="button"
                    @click="selectedStatus = 'closed'; handleFilter()"
                    class="text-left bg-white rounded-2xl p-3.5 border transition-all shadow-2xs cursor-pointer"
                    :class="selectedStatus === 'closed' ? 'border-rose-500 ring-1 ring-rose-500/30' : 'border-slate-200/80 hover:border-rose-300'"
                >
                    <div class="text-[10px] font-bold text-rose-600 uppercase tracking-wider">Ditutup</div>
                    <div class="text-xl sm:text-2xl font-black text-rose-700 mt-0.5">{{ stats.closed }}</div>
                </button>
                <Link
                    :href="route('yayasan.applicants.index')"
                    class="text-left bg-white rounded-2xl p-3.5 border border-slate-200/80 hover:border-violet-400 transition-all shadow-2xs block"
                >
                    <div class="text-[10px] font-bold text-violet-600 uppercase tracking-wider">Total Pelamar</div>
                    <div class="text-xl sm:text-2xl font-black text-violet-700 mt-0.5">{{ stats.applicants }}</div>
                </Link>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-3.5 sm:p-4 shadow-2xs space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 text-[11px] font-extrabold text-slate-700 uppercase tracking-wider">
                        <FunnelIcon class="w-4 h-4 text-namira-teal" />
                        <span>Cari &amp; Filter Lowongan</span>
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

                <div class="grid grid-cols-1 gap-2.5" :class="isUnitScoped ? 'sm:grid-cols-2' : 'sm:grid-cols-3'">
                    <div class="relative">
                        <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
                        <input
                            v-model="search"
                            @keyup.enter="handleFilter"
                            type="text"
                            placeholder="Cari judul posisi lalu Enter..."
                            class="w-full pl-9 pr-3 py-2 rounded-xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                        />
                    </div>

                    <div v-if="!isUnitScoped">
                        <select
                            v-model="selectedUnit"
                            @change="handleFilter"
                            class="w-full py-2 rounded-xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                        >
                            <option value="all">Semua Unit Sekolah</option>
                            <option value="yayasan">Kantor Yayasan Namira</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                        </select>
                    </div>

                    <div>
                        <select
                            v-model="selectedStatus"
                            @change="handleFilter"
                            class="w-full py-2 rounded-xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                        >
                            <option value="">Semua Status</option>
                            <option value="open">Dibuka (Open)</option>
                            <option value="closed">Ditutup (Closed)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!vacancies.data || vacancies.data.length === 0" class="bg-white rounded-3xl border-2 border-dashed border-slate-200 p-10 sm:p-16 text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                    <BriefcaseIcon class="w-7 h-7" />
                </div>
                <h4 class="text-base font-extrabold text-slate-800">Belum Ada Lowongan Kerja</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                    Klik tombol <strong>Tambah Lowongan</strong> untuk mengunggah poster rekrutmen dan membuka pendaftaran online di Portal Karir Namira.
                </p>
                <div v-if="canWrite" class="pt-2">
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="px-5 py-2.5 bg-namira-teal hover:bg-teal-700 text-white font-bold rounded-2xl text-xs shadow-xs inline-flex items-center gap-1.5 cursor-pointer"
                    >
                        <PlusIcon class="w-4 h-4" />
                        <span>Upload Lowongan Pertama</span>
                    </button>
                </div>
            </div>

            <!-- MOBILE CARD LIST VIEW (< md) -->
            <div v-else class="space-y-3.5">
                <div class="md:hidden space-y-3">
                    <article
                        v-for="v in vacancies.data"
                        :key="`m-${v.id}`"
                        class="bg-white rounded-3xl border border-slate-200/80 shadow-2xs overflow-hidden"
                    >
                        <div class="p-4 space-y-3.5">
                            <!-- Top Row: Poster Thumbnail + Info -->
                            <div class="flex items-start gap-3">
                                <button
                                    v-if="v.poster_url"
                                    type="button"
                                    @click="lightboxPoster = { url: v.poster_url, title: v.title }"
                                    class="w-16 h-22 rounded-2xl overflow-hidden border border-slate-200 bg-slate-100 shrink-0 relative group cursor-pointer"
                                    title="Perbesar Poster"
                                >
                                    <img :src="v.poster_url" :alt="v.title" class="w-full h-full object-cover" />
                                </button>
                                <div
                                    v-else
                                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-50 to-blue-50 border border-teal-100 flex items-center justify-center shrink-0 text-teal-600"
                                >
                                    <BriefcaseIcon class="w-6 h-6" />
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[10px] font-extrabold text-teal-700 uppercase tracking-wide truncate">
                                            {{ v.unit?.name || 'Yayasan Namira' }}
                                        </span>
                                        <span
                                            :class="[
                                                'px-2 py-0.5 rounded-full text-[9px] font-black uppercase shrink-0',
                                                v.status === 'open' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800',
                                            ]"
                                        >
                                            {{ v.status === 'open' ? 'Dibuka' : 'Ditutup' }}
                                        </span>
                                    </div>
                                    <h3 class="font-black text-sm text-slate-900 leading-snug mt-0.5 break-words">
                                        {{ v.title }}
                                    </h3>
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-slate-500 mt-1.5">
                                        <span class="font-semibold text-slate-700">{{ categories[v.category] || v.category_label }}</span>
                                        <span>•</span>
                                        <span>{{ types[v.type] || v.type_label }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Metadata Strip -->
                            <div class="grid grid-cols-2 gap-2 bg-slate-50 rounded-2xl p-2.5 border border-slate-100 text-[11px]">
                                <Link
                                    :href="route('yayasan.applicants.index', { vacancy_id: v.id })"
                                    class="flex items-center gap-1.5 font-bold text-slate-800 hover:text-teal-700"
                                >
                                    <UserGroupIcon class="w-4 h-4 text-teal-600 shrink-0" />
                                    <span>{{ v.applicants_count }} / {{ v.quota }} Pelamar</span>
                                </Link>
                                <div
                                    class="flex items-center gap-1.5 font-bold justify-end"
                                    :class="isDeadlinePast(v.deadline) ? 'text-rose-600' : 'text-slate-700'"
                                >
                                    <ClockIcon class="w-3.5 h-3.5 shrink-0" />
                                    <span class="truncate">{{ formatDate(v.deadline) }}</span>
                                </div>
                            </div>

                            <!-- Quick Share & Public Preview Row -->
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="copyPublicLink(v)"
                                    class="flex-1 py-2 px-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold inline-flex items-center justify-center gap-1.5 transition cursor-pointer"
                                >
                                    <ClipboardDocumentCheckIcon v-if="copiedId === v.id" class="w-3.5 h-3.5 text-emerald-600" />
                                    <ClipboardIcon v-else class="w-3.5 h-3.5" />
                                    <span>{{ copiedId === v.id ? 'Tersalin!' : 'Salin Link' }}</span>
                                </button>
                                <a
                                    :href="getShareWaUrl(v)"
                                    target="_blank"
                                    class="flex-1 py-2 px-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-[11px] font-bold inline-flex items-center justify-center gap-1.5 transition"
                                >
                                    <ShareIcon class="w-3.5 h-3.5" />
                                    <span>Share WA</span>
                                </a>
                                <a
                                    v-if="v.status === 'open'"
                                    :href="getPublicUrl(v)"
                                    target="_blank"
                                    class="py-2 px-3 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 text-[11px] font-bold inline-flex items-center justify-center gap-1 transition"
                                    title="Lihat Halaman Publik"
                                >
                                    <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                                </a>
                            </div>

                            <!-- Admin Actions Row -->
                            <div v-if="canWrite" class="flex items-center gap-2 pt-2 border-t border-slate-100">
                                <button
                                    type="button"
                                    @click="toggleVacancyStatus(v)"
                                    class="flex-1 py-2 px-3 rounded-xl text-[11px] font-bold inline-flex items-center justify-center gap-1.5 border transition cursor-pointer"
                                    :class="v.status === 'open'
                                        ? 'bg-amber-50 border-amber-200 text-amber-800 hover:bg-amber-100'
                                        : 'bg-emerald-50 border-emerald-200 text-emerald-800 hover:bg-emerald-100'"
                                >
                                    <LockClosedIcon v-if="v.status === 'open'" class="w-3.5 h-3.5" />
                                    <LockOpenIcon v-else class="w-3.5 h-3.5" />
                                    <span>{{ v.status === 'open' ? 'Tutup' : 'Buka' }}</span>
                                </button>
                                <button
                                    type="button"
                                    @click="openEditModal(v)"
                                    class="flex-1 py-2 px-3 rounded-xl bg-slate-900 hover:bg-namira-teal text-white text-[11px] font-bold inline-flex items-center justify-center gap-1.5 transition cursor-pointer"
                                >
                                    <PencilSquareIcon class="w-3.5 h-3.5" />
                                    <span>Edit</span>
                                </button>
                                <button
                                    type="button"
                                    @click="deleteVacancy(v)"
                                    class="p-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 transition cursor-pointer"
                                    title="Hapus Lowongan"
                                >
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- DESKTOP TABLE VIEW (md+) -->
                <div class="hidden md:block bg-white rounded-3xl shadow-2xs border border-slate-200/80 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left text-slate-600">
                            <thead class="text-[11px] text-slate-600 uppercase bg-slate-50 border-b border-slate-200/80 font-extrabold">
                                <tr>
                                    <th class="px-5 py-4">Poster &amp; Lowongan</th>
                                    <th class="px-5 py-4">Kategori &amp; Tipe</th>
                                    <th class="px-5 py-4 text-center">Kuota &amp; Pelamar</th>
                                    <th class="px-5 py-4">Batas Akhir</th>
                                    <th class="px-5 py-4 text-center">Status</th>
                                    <th class="px-5 py-4 text-right">Aksi &amp; Bagikan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-for="v in vacancies.data" :key="v.id" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3.5">
                                            <button
                                                v-if="v.poster_url"
                                                type="button"
                                                @click="lightboxPoster = { url: v.poster_url, title: v.title }"
                                                class="w-12 h-16 rounded-xl overflow-hidden border border-slate-200 bg-slate-100 shrink-0 hover:ring-2 hover:ring-teal-500 transition cursor-pointer"
                                                title="Klik untuk perbesar poster"
                                            >
                                                <img :src="v.poster_url" :alt="v.title" class="w-full h-full object-cover" />
                                            </button>
                                            <div
                                                v-else
                                                class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center shrink-0 border border-slate-200/70"
                                                title="Belum ada poster"
                                            >
                                                <PhotoIcon class="w-5 h-5" />
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-extrabold text-slate-900 text-sm leading-snug">{{ v.title }}</div>
                                                <div class="text-[11px] text-teal-700 font-bold mt-0.5 flex items-center gap-1.5">
                                                    <span>{{ v.unit?.name || 'Yayasan Namira' }}</span>
                                                    <span v-if="v.poster_url" class="px-1.5 py-0.5 rounded bg-teal-50 text-teal-700 border border-teal-100 text-[9px] font-black uppercase">Poster</span>
                                                </div>
                                                <div v-if="v.contact_whatsapp" class="text-[10px] text-slate-400 font-mono mt-0.5">
                                                    WA: {{ v.contact_whatsapp }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="font-bold text-slate-800">{{ categories[v.category] || v.category_label }}</div>
                                        <div class="text-[11px] text-slate-400 font-medium">{{ types[v.type] || v.type_label }}</div>
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <Link
                                            :href="route('yayasan.applicants.index', { vacancy_id: v.id })"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-teal-50 hover:text-teal-800 rounded-full text-xs font-extrabold text-slate-800 transition"
                                        >
                                            <UserGroupIcon class="w-3.5 h-3.5 text-teal-600" />
                                            <span>{{ v.applicants_count }} / {{ v.quota }}</span>
                                        </Link>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="font-bold" :class="isDeadlinePast(v.deadline) ? 'text-rose-600' : 'text-slate-800'">
                                            {{ formatDate(v.deadline) }}
                                        </div>
                                        <div v-if="isDeadlinePast(v.deadline)" class="text-[10px] font-bold text-rose-500">Melewati batas</div>
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <button
                                            v-if="canWrite"
                                            type="button"
                                            @click="toggleVacancyStatus(v)"
                                            :class="[
                                                'px-3 py-1 rounded-full text-[10px] font-black uppercase inline-flex items-center gap-1 shadow-2xs transition cursor-pointer',
                                                v.status === 'open'
                                                    ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'
                                                    : 'bg-rose-100 text-rose-800 hover:bg-rose-200',
                                            ]"
                                            :title="v.status === 'open' ? 'Klik untuk tutup lowongan' : 'Klik untuk buka lowongan'"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full" :class="v.status === 'open' ? 'bg-emerald-600' : 'bg-rose-600'"></span>
                                            <span>{{ v.status === 'open' ? 'Dibuka' : 'Ditutup' }}</span>
                                        </button>
                                        <span
                                            v-else
                                            :class="[
                                                'px-2.5 py-1 rounded-full text-[10px] font-black uppercase inline-block',
                                                v.status === 'open' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800',
                                            ]"
                                        >
                                            {{ v.status === 'open' ? 'Dibuka' : 'Ditutup' }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                type="button"
                                                @click="copyPublicLink(v)"
                                                class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition cursor-pointer"
                                                :title="copiedId === v.id ? 'Tautan tersalin!' : 'Salin tautan publik'"
                                            >
                                                <ClipboardDocumentCheckIcon v-if="copiedId === v.id" class="w-4 h-4 text-emerald-600" />
                                                <ClipboardIcon v-else class="w-4 h-4" />
                                            </button>
                                            <a
                                                :href="getShareWaUrl(v)"
                                                target="_blank"
                                                class="p-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl transition"
                                                title="Bagikan ke WhatsApp"
                                            >
                                                <ShareIcon class="w-4 h-4" />
                                            </a>
                                            <a
                                                v-if="v.status === 'open'"
                                                :href="getPublicUrl(v)"
                                                target="_blank"
                                                class="p-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl transition"
                                                title="Buka halaman publik"
                                            >
                                                <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                                            </a>
                                            <template v-if="canWrite">
                                                <button
                                                    type="button"
                                                    @click="openEditModal(v)"
                                                    class="p-2 bg-slate-900 hover:bg-namira-teal text-white rounded-xl transition cursor-pointer"
                                                    title="Edit Lowongan"
                                                >
                                                    <PencilSquareIcon class="w-4 h-4" />
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="deleteVacancy(v)"
                                                    class="p-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl transition cursor-pointer"
                                                    title="Hapus Lowongan"
                                                >
                                                    <TrashIcon class="w-4 h-4" />
                                                </button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="vacancies.links && vacancies.links.length > 3" class="pt-2">
                    <Pagination :links="vacancies.links" />
                </div>
            </div>
        </div>

        <!-- CREATE / EDIT MODAL (Teleported above mobile nav bar) -->
        <Teleport to="body">
            <div v-if="modalOpen" class="fixed inset-0 z-[110] flex items-end sm:items-center justify-center bg-slate-900/70 backdrop-blur-xs sm:p-4">
                <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-2xl max-h-[92vh] flex flex-col overflow-hidden">
                    <!-- Sticky Modal Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 shrink-0 bg-white">
                        <div>
                            <h3 class="text-base font-black text-slate-900">
                                {{ isEditing ? 'Edit Lowongan Kerja' : 'Tambah Lowongan Kerja Baru' }}
                            </h3>
                            <p class="text-[11px] text-slate-500">
                                Upload poster rekrutmen &amp; lengkapi informasi posisi agar tampil menarik di Portal Karir.
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="modalOpen = false"
                            class="p-2 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 transition cursor-pointer"
                        >
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Scrollable Form Body -->
                    <form id="vacancy-form" @submit.prevent="submitForm" class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
                        <!-- Validation Errors Summary -->
                        <div v-if="Object.keys(form.errors).length > 0" class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800 space-y-1">
                            <strong class="block font-extrabold">Mohon periksa kembali isian berikut:</strong>
                            <ul class="list-disc list-inside space-y-0.5">
                                <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                            </ul>
                        </div>

                        <!-- Poster / Flyer Upload Section -->
                        <div class="p-4 rounded-2xl bg-gradient-to-br from-teal-50/70 to-slate-50 border border-teal-200/70 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <label class="block text-xs font-extrabold text-teal-950">
                                        Poster / Flyer Lowongan (Disarankan)
                                    </label>
                                    <p class="text-[11px] text-teal-800/80">
                                        Mendukung poster vertikal (Instagram/Story 2:3, 9:16), persegi, maupun landscape. Otomatis dioptimalkan untuk HP.
                                    </p>
                                </div>
                            </div>

                            <div v-if="posterPreview" class="space-y-2.5">
                                <div class="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-900/95 flex items-center justify-center p-2">
                                    <img :src="posterPreview" alt="Preview Poster Lowongan" class="max-h-64 w-auto object-contain rounded-xl shadow-md" />
                                    <div v-if="isCompressingPoster" class="absolute inset-0 bg-slate-900/70 flex flex-col items-center justify-center text-white gap-1">
                                        <ArrowPathIcon class="w-6 h-6 animate-spin text-teal-300" />
                                        <span class="text-xs font-bold">Mengoptimalkan poster...</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="flex-1 py-2 px-3 rounded-xl bg-white border border-slate-300 hover:bg-slate-50 text-slate-800 text-xs font-bold text-center cursor-pointer transition">
                                        <span>Ganti Poster</span>
                                        <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onPosterSelected" :disabled="isCompressingPoster" />
                                    </label>
                                    <button
                                        type="button"
                                        @click="removeSelectedPoster"
                                        class="py-2 px-3 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition cursor-pointer"
                                    >
                                        Hapus Poster
                                    </button>
                                </div>
                            </div>

                            <label
                                v-else
                                class="flex flex-col items-center justify-center gap-2 p-5 rounded-2xl border-2 border-dashed border-teal-300/80 bg-white/80 hover:bg-white transition cursor-pointer text-center"
                            >
                                <div class="w-11 h-11 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center">
                                    <ArrowPathIcon v-if="isCompressingPoster" class="w-6 h-6 animate-spin" />
                                    <PhotoIcon v-else class="w-6 h-6" />
                                </div>
                                <div>
                                    <span class="block text-xs font-extrabold text-slate-800">
                                        {{ isCompressingPoster ? 'Mengoptimalkan gambar...' : 'Ketuk untuk Upload Poster Lowongan' }}
                                    </span>
                                    <span class="block text-[11px] text-slate-500 mt-0.5">JPG, PNG, atau WEBP (Maks 10MB)</span>
                                </div>
                                <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onPosterSelected" :disabled="isCompressingPoster" />
                            </label>
                        </div>

                        <!-- Core Details Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 mb-1">Judul Posisi Lowongan <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    required
                                    placeholder="Contoh: Guru PJOK / Guru Kelas SD Namira Kraksaan"
                                    class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Unit Sekolah / Yayasan</label>
                                <div
                                    v-if="isUnitScoped"
                                    class="w-full px-3.5 py-2.5 rounded-2xl border border-teal-200 bg-teal-50/70 text-xs font-bold text-teal-800 flex items-center gap-2"
                                >
                                    <BuildingOfficeIcon class="w-4 h-4 text-teal-600 shrink-0" />
                                    <span class="truncate">{{ activeUnitName || 'Unit Anda' }}</span>
                                </div>
                                <select
                                    v-else
                                    v-model="form.unit_id"
                                    class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                                >
                                    <option value="">Kantor Yayasan Namira</option>
                                    <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Status Lowongan <span class="text-rose-500">*</span></label>
                                <select v-model="form.status" class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20">
                                    <option value="open">Dibuka (Bisa dilamar pelamar)</option>
                                    <option value="closed">Ditutup (Pendaftaran dikunci)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kategori Posisi <span class="text-rose-500">*</span></label>
                                <select v-model="form.category" class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20">
                                    <option value="teacher">Tenaga Pendidik (Guru)</option>
                                    <option value="staff">Tenaga Kependidikan (Staf)</option>
                                    <option value="operational">Operasional &amp; Sarpras</option>
                                    <option value="other">Lainnya</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tipe Pekerjaan <span class="text-rose-500">*</span></label>
                                <select v-model="form.type" class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20">
                                    <option value="full_time">Penuh Waktu (Full Time)</option>
                                    <option value="part_time">Paruh Waktu (Part Time)</option>
                                    <option value="contract">Kontrak</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kuota Kebutuhan (Orang) <span class="text-rose-500">*</span></label>
                                <input v-model="form.quota" type="number" min="1" max="500" required class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Batas Akhir Pendaftaran</label>
                                <input v-model="form.deadline" type="date" class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center gap-1">
                                    <PhoneIcon class="w-3.5 h-3.5 text-emerald-600" />
                                    <span>No. WhatsApp Admin / Humas Unit</span>
                                </label>
                                <input
                                    v-model="form.contact_whatsapp"
                                    type="tel"
                                    placeholder="Contoh: 082233232257"
                                    class="w-full rounded-2xl border-slate-200 text-xs font-mono font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center gap-1">
                                    <MapPinIcon class="w-3.5 h-3.5 text-teal-600" />
                                    <span>Lokasi / Pengiriman Berkas</span>
                                </label>
                                <input
                                    v-model="form.placement_address"
                                    type="text"
                                    placeholder="Contoh: SD Namira Kraksaan, Jl. Raya Kraksaan No. 100"
                                    class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Deskripsi Pekerjaan
                                <span v-if="!willHavePoster" class="text-rose-500">*</span>
                                <span v-else class="text-[10px] text-teal-700 font-semibold">(Opsional karena poster sudah diunggah)</span>
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                :required="!willHavePoster"
                                placeholder="Tuliskan pengantar singkat, tugas, atau informasi tambahan..."
                                class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Kualifikasi &amp; Persyaratan Berkas
                                <span v-if="!willHavePoster" class="text-rose-500">*</span>
                                <span v-else class="text-[10px] text-teal-700 font-semibold">(Opsional karena poster sudah diunggah)</span>
                            </label>
                            <textarea
                                v-model="form.requirements"
                                rows="4"
                                :required="!willHavePoster"
                                placeholder="Contoh: S1 PJOK/semua jurusan, komunikatif, usia maks 35 thn, melampirkan CV, Surat Lamaran, Surat Sehat, Foto 3x4, KK, Ijazah..."
                                class="w-full rounded-2xl border-slate-200 text-xs font-semibold focus:border-namira-teal focus:ring-namira-teal/20"
                            ></textarea>
                        </div>
                    </form>

                    <!-- Sticky Modal Footer -->
                    <div class="flex items-center justify-end gap-2.5 px-5 py-3.5 border-t border-slate-100 bg-slate-50/80 shrink-0">
                        <button
                            type="button"
                            @click="modalOpen = false"
                            class="px-4 py-2.5 border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 rounded-xl text-xs font-bold transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            form="vacancy-form"
                            :disabled="form.processing || isCompressingPoster"
                            class="px-5 py-2.5 bg-namira-teal hover:bg-teal-700 disabled:opacity-50 text-white rounded-xl text-xs font-extrabold shadow-xs transition cursor-pointer inline-flex items-center gap-1.5"
                        >
                            <ArrowPathIcon v-if="form.processing" class="w-4 h-4 animate-spin" />
                            <span>{{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Publikasikan Lowongan') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- FULL-SCREEN POSTER LIGHTBOX -->
        <Teleport to="body">
            <div
                v-if="lightboxPoster"
                class="fixed inset-0 z-[120] bg-slate-950/90 backdrop-blur-sm flex flex-col items-center justify-center p-4"
                @click.self="lightboxPoster = null"
            >
                <div class="w-full max-w-2xl flex items-center justify-between text-white mb-3">
                    <h4 class="font-extrabold text-sm truncate pr-4">{{ lightboxPoster.title }}</h4>
                    <button
                        type="button"
                        @click="lightboxPoster = null"
                        class="p-2 rounded-full bg-white/15 hover:bg-white/25 text-white transition cursor-pointer"
                    >
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
                <img
                    :src="lightboxPoster.url"
                    :alt="lightboxPoster.title"
                    class="max-h-[82vh] max-w-full object-contain rounded-2xl shadow-2xl border border-white/15"
                />
            </div>
        </Teleport>
    </div>
</template>
