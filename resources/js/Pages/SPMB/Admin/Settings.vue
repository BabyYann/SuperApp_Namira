<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });
import { 
    ArrowLeftIcon, 
    Cog6ToothIcon, 
    CheckCircleIcon, 
    UserPlusIcon, 
    TrashIcon, 
    QrCodeIcon, 
    PhotoIcon,
    ShieldCheckIcon,
    ArrowPathIcon,
    BuildingLibraryIcon,
    SparklesIcon,
    PlusIcon,
    LockClosedIcon,
    LockOpenIcon,
    LinkIcon
} from '@heroicons/vue/24/outline';
import { compressImageFile } from '@/Utils/imageCompressor';
import { toDateInputValue } from '@/Utils/dateHelper';

const props = defineProps({
    setting: Object,
    unit: Object,
    teachers: Array,
    assignedPanitia: Array,
    availableUnits: Array,
    isTkKb: {
        type: Boolean,
        default: false,
    },
    registrationUrl: {
        type: String,
        default: '',
    },
    resolvedFutureIndenYears: {
        type: Array,
        default: () => [],
    },
    resolvedClosedYears: {
        type: Array,
        default: () => [],
    },
    suggestedFutureYears: {
        type: Array,
        default: () => [],
    },
});

const settingForm = useForm({
    unit_id: props.unit?.id,
    name: props.setting?.name || 'Pendaftaran Murid Baru',
    academic_year: props.setting?.academic_year || '2027/2028',
    allow_future_inden: Boolean(props.setting?.allow_future_inden ?? true),
    inden_academic_years: [...(props.resolvedFutureIndenYears || [])],
    closed_academic_years: [...(props.resolvedClosedYears || [])],
    sync_academic_year_lists: true,
    quota: props.setting?.quota || 60,
    registration_fee: props.setting?.registration_fee || 250000,
    admission_fee_total: props.setting?.admission_fee_total || 8500000,
    min_down_payment_percentage: props.setting?.min_down_payment_percentage || 60,
    bank_name: props.setting?.bank_name || 'Bank Jatim',
    bank_account_number: props.setting?.bank_account_number || '',
    bank_account_holder: props.setting?.bank_account_holder || 'NAMIRA SCHOOL KOTA PROBOLINGGO',
    contact_whatsapp: props.setting?.contact_whatsapp || '082332922521',
    whatsapp_group_link: props.setting?.whatsapp_group_link || '',
    selection_date: toDateInputValue(props.setting?.selection_date),
    announcement_date: toDateInputValue(props.setting?.announcement_date),
    announcement_released: Boolean(props.setting?.announcement_released ?? false),
    notes: props.setting?.notes || '',
    is_active: Boolean(props.setting?.is_active ?? !props.isTkKb),
    qris_image: null,
});

const customFutureYear = ref('');

const candidateFutureYears = computed(() => {
    const m = String(settingForm.academic_year || '2027/2028').match(/^(\d{4})\/(\d{4})$/);
    const baseStart = m ? Number(m[1]) : 2027;
    const generated = [];
    for (let i = 1; i <= 5; i++) {
        generated.push(`${baseStart + i}/${baseStart + i + 1}`);
    }
    const merged = Array.from(new Set([...generated, ...(settingForm.inden_academic_years || [])]));
    return merged.filter((yr) => {
        const ym = String(yr).match(/^(\d{4})\/(\d{4})$/);
        return ym && Number(ym[1]) > baseStart && Number(ym[2]) === Number(ym[1]) + 1;
    }).sort();
});

const toggleFutureYear = (yr) => {
    const list = [...(settingForm.inden_academic_years || [])];
    const idx = list.indexOf(yr);
    if (idx >= 0) {
        list.splice(idx, 1);
    } else {
        list.push(yr);
    }
    list.sort();
    settingForm.inden_academic_years = list;
};

const allConfigurableYears = computed(() => {
    const primary = String(settingForm.academic_year || '2027/2028').trim();
    const futures = settingForm.allow_future_inden ? (settingForm.inden_academic_years || []) : [];
    return Array.from(new Set([primary, ...futures].filter(Boolean))).sort();
});

const openConfiguredYears = computed(() => {
    const closed = settingForm.closed_academic_years || [];
    return allConfigurableYears.value.filter((yr) => !closed.includes(yr));
});

const closedConfiguredYears = computed(() => {
    const closed = settingForm.closed_academic_years || [];
    return allConfigurableYears.value.filter((yr) => closed.includes(yr));
});

const toggleClosedYear = (yr) => {
    const list = [...(settingForm.closed_academic_years || [])];
    const idx = list.indexOf(yr);
    if (idx >= 0) {
        list.splice(idx, 1);
    } else {
        list.push(yr);
    }
    list.sort();
    settingForm.closed_academic_years = list;
};

const addCustomFutureYear = () => {
    const raw = customFutureYear.value.trim().replace('-', '/');
    const m = raw.match(/^(\d{4})\/(\d{4})$/);
    if (!m || Number(m[2]) !== Number(m[1]) + 1) {
        alert('Format tahun ajaran tidak valid. Gunakan format YYYY/YYYY berurutan, contoh: 2031/2032.');
        return;
    }
    const baseMatch = String(settingForm.academic_year || '2027/2028').match(/^(\d{4})\//);
    const baseStart = baseMatch ? Number(baseMatch[1]) : 2027;
    if (Number(m[1]) <= baseStart) {
        alert(`Tahun ajaran Inden harus lebih besar dari Tahun Ajaran aktif (${settingForm.academic_year}).`);
        return;
    }
    if (!settingForm.inden_academic_years.includes(raw)) {
        settingForm.inden_academic_years = [...settingForm.inden_academic_years, raw].sort();
    }
    customFutureYear.value = '';
};

const qrisPreview = ref(props.setting?.qris_image_url || null);
const isCompressing = ref(false);

const handleQrisUpload = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    isCompressing.value = true;
    try {
        const compressedFile = await compressImageFile(file, 800, 0.85);
        settingForm.qris_image = compressedFile;
        qrisPreview.value = URL.createObjectURL(compressedFile);
    } catch (err) {
        console.error('QRIS compression error:', err);
        settingForm.qris_image = file;
        qrisPreview.value = URL.createObjectURL(file);
    } finally {
        isCompressing.value = false;
    }
};

const submitSetting = () => {
    settingForm.post(route('spmb.admin.update-settings'), {
        preserveScroll: true,
        onSuccess: () => alert('Pengaturan SPMB berhasil disimpan.'),
    });
};

// Form Assign Panitia
const panitiaForm = useForm({
    user_id: '',
    unit_id: props.unit?.id,
});

watch(() => [props.unit?.id, props.setting?.id], () => {
    panitiaForm.unit_id = props.unit?.id;
    settingForm.unit_id = props.unit?.id;
    settingForm.name = props.setting?.name || 'Pendaftaran Murid Baru';
    settingForm.academic_year = props.setting?.academic_year || '2027/2028';
    settingForm.allow_future_inden = Boolean(props.setting?.allow_future_inden ?? true);
    settingForm.inden_academic_years = [...(props.resolvedFutureIndenYears || [])];
    settingForm.closed_academic_years = [...(props.resolvedClosedYears || [])];
    settingForm.sync_academic_year_lists = true;
    settingForm.quota = props.setting?.quota || 60;
    settingForm.registration_fee = props.setting?.registration_fee || 250000;
    settingForm.admission_fee_total = props.setting?.admission_fee_total || 8500000;
    settingForm.min_down_payment_percentage = props.setting?.min_down_payment_percentage || 60;
    settingForm.bank_name = props.setting?.bank_name || 'Bank Jatim';
    settingForm.bank_account_number = props.setting?.bank_account_number || '';
    settingForm.bank_account_holder = props.setting?.bank_account_holder || 'NAMIRA SCHOOL KOTA PROBOLINGGO';
    settingForm.contact_whatsapp = props.setting?.contact_whatsapp || '082332922521';
    settingForm.whatsapp_group_link = props.setting?.whatsapp_group_link || '';
    settingForm.selection_date = toDateInputValue(props.setting?.selection_date);
    settingForm.announcement_date = toDateInputValue(props.setting?.announcement_date);
    settingForm.announcement_released = Boolean(props.setting?.announcement_released ?? false);
    settingForm.notes = props.setting?.notes || '';
    settingForm.is_active = Boolean(props.setting?.is_active ?? !props.isTkKb);
    settingForm.qris_image = null;
    qrisPreview.value = props.setting?.qris_image_url || null;
});

const assignPanitia = () => {
    if (!panitiaForm.user_id) {
        alert(`Pilih guru ${props.unit?.name || 'unit'} terlebih dahulu.`);
        return;
    }
    panitiaForm.unit_id = props.unit?.id;
    panitiaForm.post(route('spmb.admin.assign-panitia'), {
        preserveScroll: true,
        onSuccess: () => {
            panitiaForm.reset('user_id');
            alert(`Guru berhasil ditugaskan sebagai Panitia SPMB ${props.unit?.name || ''}.`);
        }
    });
};

const removePanitia = (panitia) => {
    if (confirm(`Cabut wewenang Panitia SPMB ${props.unit?.name || ''} untuk ${panitia.name}?`)) {
        router.delete(route('spmb.admin.remove-panitia', panitia.id), {
            data: { unit_id: props.unit?.id },
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Pengaturan & Penugasan Panitia SPMB" />

    <div>
        <div class="space-y-6 max-w-5xl mx-auto">
            
            <!-- Breadcrumbs -->
            <div>
                <Link 
                    :href="route('spmb.admin.index', { unit_id: unit?.id })"
                    class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-emerald-600 transition"
                >
                    <ArrowLeftIcon class="w-4 h-4" />
                    <span>Kembali ke Data Pendaftar</span>
                </Link>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white mt-2 flex items-center gap-2.5">
                    <Cog6ToothIcon class="w-7 h-7 text-[#064e3b] dark:text-[#fbbf24]" />
                    <span>Pengaturan SPMB & Penugasan Panitia</span>
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Atur kuota, biaya formulir, rekening transfer/QRIS, serta delegasikan wewenang panitia kepada guru {{ unit?.name || 'unit' }}.
                </p>

                <!-- Unit Tabs Switcher (SD Kraksaan, SD Kota, SMP, TK/KB) -->
                <div v-if="availableUnits && availableUnits.length > 1" class="flex flex-wrap items-center gap-2 mt-4 bg-slate-100 dark:bg-slate-800/80 p-1.5 rounded-2xl w-fit border border-slate-200 dark:border-slate-700">
                    <Link 
                        v-for="u in availableUnits" 
                        :key="u.id"
                        :href="route('spmb.admin.settings', { unit_id: u.id })"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
                        :class="unit?.id === u.id 
                            ? 'bg-[#064e3b] text-white shadow-sm' 
                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white dark:hover:bg-slate-700'"
                    >
                        <BuildingLibraryIcon class="w-4 h-4 shrink-0" />
                        <span>{{ u.code === 'SD' ? 'SD Namira Kraksaan' : u.name }}</span>
                        <span
                            v-if="u.is_active === false"
                            class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase"
                            :class="unit?.id === u.id ? 'bg-amber-400 text-slate-950' : 'bg-amber-100 text-amber-800'"
                        >
                            Terkunci
                        </span>
                    </Link>
                </div>

                <!-- Direct Link & Public Lock Status Card -->
                <div
                    v-if="registrationUrl"
                    class="mt-4 rounded-2xl border p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
                    :class="settingForm.is_active
                        ? 'bg-emerald-50/70 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/60'
                        : 'bg-amber-50/90 dark:bg-amber-950/30 border-amber-300 dark:border-amber-800/70'"
                >
                    <div class="flex items-start gap-3 min-w-0">
                        <div
                            class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                            :class="settingForm.is_active ? 'bg-emerald-700 text-white' : 'bg-amber-500 text-slate-950'"
                        >
                            <LockOpenIcon v-if="settingForm.is_active" class="w-5 h-5" />
                            <LockClosedIcon v-else class="w-5 h-5" />
                        </div>
                        <div class="min-w-0">
                            <div class="font-extrabold text-slate-900 dark:text-white flex flex-wrap items-center gap-2">
                                <span>{{ unit?.name }}</span>
                                <span
                                    class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase"
                                    :class="settingForm.is_active ? 'bg-emerald-600 text-white' : 'bg-amber-400 text-slate-950'"
                                >
                                    {{ settingForm.is_active ? 'Aktif & Terbuka Publik' : 'Terkunci dari Publik (Siap Di-Unlock)' }}
                                </span>
                            </div>
                            <p class="text-[11px] font-mono text-slate-600 dark:text-slate-300 truncate mt-0.5">
                                {{ registrationUrl }}
                            </p>
                            <p v-if="isTkKb && !settingForm.is_active" class="text-[11px] text-amber-900 dark:text-amber-300 mt-1">
                                Sistem pendaftaran TK/KB sudah siap digunakan. Saat sudah siap diumumkan ke orang tua, cukup centang <strong>"Pendaftaran Dibuka untuk Umum"</strong> di bawah lalu klik Simpan.
                            </p>
                        </div>
                    </div>
                    <a
                        :href="registrationUrl"
                        target="_blank"
                        class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-50 text-slate-800 dark:text-slate-100 border border-slate-300 dark:border-slate-700 font-bold text-xs shrink-0 transition"
                    >
                        <LinkIcon class="w-4 h-4" />
                        <span>{{ settingForm.is_active ? 'Buka Formulir' : 'Pratinjau Form (Admin)' }}</span>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Kolom Kiri: Form Pengaturan Periode (2 cols) -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-7 shadow-sm space-y-5">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider pb-3 border-b border-slate-100 dark:border-slate-800">
                            Pengaturan Periode & Finansial
                        </h2>

                        <form @submit.prevent="submitSetting" class="space-y-4 text-xs">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Jalur Pendaftaran</label>
                                    <input v-model="settingForm.name" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-white text-xs">
                                </div>

                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Tahun Ajaran Aktif (Utama)</label>
                                    <input v-model="settingForm.academic_year" type="text" placeholder="2027/2028" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-white text-xs font-mono font-bold">
                                </div>

                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Target Kuota Siswa</label>
                                    <input v-model="settingForm.quota" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-white text-xs">
                                </div>

                                <!-- Multi-Year Future Inden Configurator -->
                                <div class="sm:col-span-2 p-4 rounded-2xl bg-purple-50/70 dark:bg-purple-950/25 border border-purple-200 dark:border-purple-800/60 space-y-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <span class="inline-flex items-center gap-1.5 font-extrabold text-purple-900 dark:text-purple-200 text-xs">
                                                <SparklesIcon class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                                                <span>Opsi Pendaftaran Inden Tahun Ajaran Mendatang (Multi-Tahun)</span>
                                            </span>
                                            <p class="text-[11px] text-purple-800/80 dark:text-purple-300/80 mt-0.5 leading-relaxed">
                                                Aktifkan agar orang tua leluasa mendaftar Inden lebih awal untuk tahun ajaran mendatang (misal 2028/2029, 2029/2030, dst.).
                                            </p>
                                        </div>
                                        <label class="inline-flex items-center gap-2 cursor-pointer shrink-0 pt-0.5">
                                            <input type="checkbox" v-model="settingForm.allow_future_inden" class="rounded text-purple-600 focus:ring-purple-500">
                                            <span class="font-bold text-purple-900 dark:text-purple-200">Aktifkan</span>
                                        </label>
                                    </div>

                                    <div v-if="settingForm.allow_future_inden" class="space-y-3 pt-2 border-t border-purple-200/70 dark:border-purple-800/50">
                                        <div>
                                            <span class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-2">
                                                Pilih Tahun Ajaran Mendatang yang Dibuka untuk Inden:
                                            </span>
                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    v-for="yr in candidateFutureYears"
                                                    :key="yr"
                                                    type="button"
                                                    @click="toggleFutureYear(yr)"
                                                    class="px-3 py-1.5 rounded-xl text-xs font-mono font-bold transition border inline-flex items-center gap-1.5"
                                                    :class="(settingForm.inden_academic_years || []).includes(yr)
                                                        ? 'bg-purple-700 text-white border-purple-700 shadow-2xs'
                                                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:border-purple-400'"
                                                >
                                                    <CheckCircleIcon v-if="(settingForm.inden_academic_years || []).includes(yr)" class="w-3.5 h-3.5 text-amber-300" />
                                                    <span>TA {{ yr }}</span>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                            <input
                                                v-model="customFutureYear"
                                                type="text"
                                                placeholder="Tambah TA lain (contoh: 2031/2032)"
                                                class="flex-1 bg-white dark:bg-slate-900 border border-purple-300 dark:border-purple-700 rounded-xl px-3 py-1.5 text-xs font-mono text-slate-900 dark:text-white"
                                                @keyup.enter.prevent="addCustomFutureYear"
                                            />
                                            <button
                                                type="button"
                                                @click="addCustomFutureYear"
                                                class="inline-flex items-center justify-center gap-1 px-3.5 py-1.5 rounded-xl bg-purple-700 hover:bg-purple-800 text-white text-xs font-bold transition"
                                            >
                                                <PlusIcon class="w-3.5 h-3.5" />
                                                <span>Tambah TA</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="pt-3 border-t border-purple-200/70 dark:border-purple-800/50 space-y-2">
                                        <div>
                                            <span class="block text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                                Status Kuota per Tahun Ajaran (Klik untuk Tutup / Tandai Kuota Penuh):
                                            </span>
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400">
                                                Tahun ajaran yang ditandai <strong>Penuh / Tutup</strong> akan dinonaktifkan pada formulir pendaftaran dan ditolak di server, sementara TA lainnya tetap terbuka.
                                            </p>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                v-for="yr in allConfigurableYears"
                                                :key="`close-${yr}`"
                                                type="button"
                                                @click="toggleClosedYear(yr)"
                                                class="px-3 py-1.5 rounded-xl text-xs font-mono font-bold transition border inline-flex items-center gap-1.5"
                                                :class="(settingForm.closed_academic_years || []).includes(yr)
                                                    ? 'bg-rose-600 text-white border-rose-600 shadow-2xs'
                                                    : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700 hover:border-rose-400'"
                                            >
                                                <LockClosedIcon v-if="(settingForm.closed_academic_years || []).includes(yr)" class="w-3.5 h-3.5" />
                                                <LockOpenIcon v-else class="w-3.5 h-3.5" />
                                                <span>TA {{ yr }}</span>
                                                <span class="text-[10px] font-sans uppercase px-1.5 py-0.5 rounded bg-black/10">
                                                    {{ (settingForm.closed_academic_years || []).includes(yr) ? 'Penuh/Tutup' : 'Dibuka' }}
                                                </span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="text-[11px] text-slate-600 dark:text-slate-400 space-y-1">
                                        <p>
                                            Pilihan <strong class="text-emerald-700 dark:text-emerald-400">Terbuka</strong> di formulir:
                                            <strong class="font-mono text-slate-900 dark:text-white">
                                                {{ openConfiguredYears.length > 0 ? openConfiguredYears.join(' • ') : 'Tidak ada (semua penuh)' }}
                                            </strong>
                                        </p>
                                        <p v-if="closedConfiguredYears.length > 0">
                                            Status <strong class="text-rose-600 dark:text-rose-400">Ditutup / Kuota Penuh</strong>:
                                            <strong class="font-mono text-rose-700 dark:text-rose-300">
                                                {{ closedConfiguredYears.join(' • ') }}
                                            </strong>
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Biaya Formulir / QRIS (Rp)</label>
                                    <input v-model="settingForm.registration_fee" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-white text-xs font-bold text-emerald-600">
                                </div>

                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Estimasi Total Biaya Masuk (Rp)</label>
                                    <input v-model="settingForm.admission_fee_total" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-white text-xs">
                                </div>

                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Persentase Termin 1 (%)</label>
                                    <input v-model="settingForm.min_down_payment_percentage" type="number" min="10" max="100" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-white text-xs">
                                </div>

                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">WhatsApp Bantuan Panitia</label>
                                    <input v-model="settingForm.contact_whatsapp" type="text" placeholder="082332922521" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-white text-xs">
                                </div>

                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Pelaksanaan Seleksi</label>
                                    <input v-model="settingForm.selection_date" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-white text-xs">
                                </div>

                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Jadwal Pengumuman</label>
                                    <input v-model="settingForm.announcement_date" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-white text-xs">
                                </div>

                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Bank Transfer</label>
                                    <input v-model="settingForm.bank_name" type="text" placeholder="Contoh: Bank Jatim" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-white text-xs">
                                </div>

                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Nomor Rekening Bank</label>
                                    <input v-model="settingForm.bank_account_number" type="text" placeholder="Contoh: 0291008899" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-white text-xs font-mono font-bold">
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Atas Nama Rekening Bank</label>
                                    <input v-model="settingForm.bank_account_holder" type="text" placeholder="Contoh: NAMIRA SCHOOL KOTA PROBOLINGGO" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-white text-xs font-bold">
                                </div>

                                <div class="sm:col-span-2 p-3.5 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 space-y-1.5">
                                    <label class="block font-bold text-emerald-900 dark:text-emerald-300">
                                        Link Undangan Grup WhatsApp Resmi Calon Murid ({{ unit?.name || 'Unit' }})
                                    </label>
                                    <input 
                                        v-model="settingForm.whatsapp_group_link" 
                                        type="url" 
                                        placeholder="https://chat.whatsapp.com/xxxxxxxxxxxxxxxxxxxxxx" 
                                        class="w-full bg-white dark:bg-slate-900 border border-emerald-300 dark:border-emerald-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-white text-xs font-medium focus:ring-emerald-500 focus:border-emerald-500"
                                    >
                                    <p class="text-[11px] text-emerald-800/80 dark:text-emerald-400/80 leading-relaxed">
                                        Jika diisi, tombol <strong>"Gabung Grup WhatsApp Resmi Orang Tua"</strong> akan otomatis tampil di halaman <strong>Portal Calon Siswa</strong> begitu orang tua selesai mendaftar (aman dari pemblokiran WA karena orang tua bergabung mandiri).
                                    </p>
                                    <p v-if="settingForm.errors.whatsapp_group_link" class="text-[11px] font-bold text-rose-600">
                                        {{ settingForm.errors.whatsapp_group_link }}
                                    </p>
                                </div>
                            </div>

                            <!-- Upload Gambar QRIS -->
                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-3">
                                <span class="font-bold text-slate-800 dark:text-slate-200 block">Gambar Kode QRIS Bank Jatim</span>
                                <div class="flex items-center gap-4">
                                    <div class="relative w-24 h-24 rounded-xl border border-dashed border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 flex items-center justify-center overflow-hidden shrink-0">
                                        <template v-if="qrisPreview">
                                            <img :src="qrisPreview" class="w-full h-full object-contain">
                                            <div v-if="isCompressing" class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center text-white">
                                                <ArrowPathIcon class="w-5 h-5 animate-spin text-teal-300 mb-0.5" />
                                                <span class="text-[9px] font-bold">Kompresi...</span>
                                            </div>
                                        </template>
                                        <div v-else-if="isCompressing" class="flex flex-col items-center justify-center text-teal-600">
                                            <ArrowPathIcon class="w-5 h-5 animate-spin mb-0.5" />
                                            <span class="text-[9px] font-bold">Kompresi...</span>
                                        </div>
                                        <QrCodeIcon v-else class="w-8 h-8 text-slate-400" />
                                    </div>
                                    <div>
                                        <label class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 text-xs font-semibold text-slate-800 dark:text-white transition" :class="{ 'opacity-60 cursor-not-allowed': isCompressing }">
                                            <span>{{ isCompressing ? 'Mengompres...' : 'Pilih Foto QRIS' }}</span>
                                            <input type="file" accept="image/*" class="hidden" @change="handleQrisUpload" :disabled="isCompressing">
                                        </label>
                                        <span class="text-[11px] text-slate-400 block mt-1">Format: JPG atau PNG (Maks 10MB, otomatis dioptimalkan).</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Pendaftaran Buka / Tutup -->
                            <div class="p-3.5 rounded-2xl border space-y-1" :class="settingForm.is_active ? 'bg-emerald-50/50 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-800/60' : 'bg-amber-50/70 border-amber-300 dark:bg-amber-950/25 dark:border-amber-800/60'">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" id="isActive" v-model="settingForm.is_active" class="rounded text-emerald-600 focus:ring-emerald-500">
                                    <label for="isActive" class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        {{ isTkKb ? `Buka / Unlock Pendaftaran Online ${unit?.name || 'TK/KB'} untuk Umum` : 'Pendaftaran Sedang Dibuka untuk Umum' }}
                                    </label>
                                </div>
                                <p v-if="isTkKb" class="text-[11px] text-slate-500 dark:text-slate-400 pl-6">
                                    Biarkan tidak dicentang jika pendaftaran TK/KB belum diumumkan ke publik. Centang lalu simpan kapan saja untuk langsung membuka tombol daftar online di halaman SPMB publik.
                                </p>
                            </div>

                            <!-- Rilis Pengumuman Resmi -->
                            <div class="pt-1 space-y-1">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" id="announcementReleased" v-model="settingForm.announcement_released" class="rounded text-indigo-600 focus:ring-indigo-500">
                                    <label for="announcementReleased" class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                        Rilis Resmi Pengumuman Kelulusan (Buka Akses Keputusan & SKL di Portal Orang Tua)
                                    </label>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 pl-6">
                                    Jika tidak dicentang dan tanggal pengumuman belum tiba (atau kosong), keputusan kelulusan, dokumen hasil evaluasi, dan SKL tetap terkunci di portal orang tua.
                                </p>
                            </div>

                            <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                                <button 
                                    type="submit" 
                                    :disabled="settingForm.processing || isCompressing"
                                    class="px-6 py-2.5 rounded-xl bg-[#064e3b] hover:bg-emerald-700 text-white font-bold text-xs shadow transition disabled:opacity-50"
                                >
                                    {{ settingForm.processing ? 'Menyimpan...' : (isCompressing ? 'Mengompresi Foto...' : 'Simpan Pengaturan') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Kolom Kanan: Penugasan Guru sebagai Panitia (1 col) -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
                        <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
                            <ShieldCheckIcon class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                                Panitia SPMB
                            </h2>
                        </div>

                        <p class="text-xs text-slate-500 leading-relaxed">
                            Guru yang ditugaskan sebagai panitia dapat memverifikasi berkas, memeriksa bukti QRIS, mengatur jadwal, dan menginput hasil observasi.
                        </p>

                        <!-- Form Penugasan -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-3">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Tugaskan Guru {{ unit?.name || 'Unit' }}:
                            </label>
                            <select v-model="panitiaForm.user_id" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-200">
                                <option value="" disabled>-- Pilih Ustadz / Ustadzah {{ unit?.name || '' }} --</option>
                                <option v-for="t in teachers" :key="t.id" :value="t.id">
                                    {{ t.name }} ({{ t.niy || '-' }})
                                </option>
                            </select>

                            <button 
                                type="button" 
                                @click="assignPanitia"
                                :disabled="panitiaForm.processing"
                                class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow transition disabled:opacity-50"
                            >
                                <UserPlusIcon class="w-4 h-4" />
                                <span>Tugaskan sebagai Panitia {{ unit?.name || '' }}</span>
                            </button>
                        </div>

                        <!-- Daftar Panitia Aktif -->
                        <div class="space-y-2 pt-2">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block">
                                Daftar Panitia Aktif ({{ unit?.name || 'Unit' }}):
                            </span>
                            
                            <div v-if="assignedPanitia.length === 0" class="text-xs text-slate-400 italic py-2">
                                Belum ada guru {{ unit?.name || 'unit' }} yang ditugaskan khusus sebagai panitia.
                            </div>

                            <div 
                                v-for="panitia in assignedPanitia" 
                                :key="panitia.id"
                                class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 text-xs"
                            >
                                <div>
                                    <strong class="text-slate-900 dark:text-white block">{{ panitia.name }}</strong>
                                    <span class="text-[11px] text-slate-400 block">{{ panitia.email }}</span>
                                </div>
                                <button 
                                    @click="removePanitia(panitia)"
                                    title="Cabut Penugasan"
                                    class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition"
                                >
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</template>
