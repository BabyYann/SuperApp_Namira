<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { 
    Cog6ToothIcon, BuildingLibraryIcon, PhoneIcon,
    MagnifyingGlassIcon, UserIcon, ShieldCheckIcon,
    BoltIcon, WrenchScrewdriverIcon, ExclamationTriangleIcon,
    AcademicCapIcon, BanknotesIcon, UserGroupIcon,
    ChatBubbleLeftRightIcon, BuildingOffice2Icon, IdentificationIcon,
    CheckCircleIcon, SwatchIcon, AtSymbolIcon, MapPinIcon, ArrowUpTrayIcon,
    ClipboardDocumentListIcon, ClockIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
    settings: Object,
    users: Array,
    availableRoles: Array,
    activityLogs: Array,
    filters: Object,
});

const activeTab = ref('identity');
const searchQuery = ref(props.filters?.search || '');
const isLoadingSearch = ref(false);

const form = useForm({
    app_name: props.settings?.app_name || '',
    contact_email: props.settings?.contact_email || '',
    contact_phone: props.settings?.contact_phone || '',
    address: props.settings?.address || '',
    maintenance_message: props.settings?.maintenance_message || '',
    waha_url: props.settings?.waha_url || '',
    waha_api_key: props.settings?.waha_api_key || '',
    waha_session: props.settings?.waha_session || '',
    app_logo: null,
    app_favicon: null,
});

const logoPreview = ref(props.settings?.app_logo || null);

const handleLogoUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.app_logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const saveGlobalSettings = () => {
    form.post(route('yayasan.settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success', title: 'Tersimpan',
                text: 'Pengaturan global berhasil diperbarui.',
                timer: 1500, showConfirmButton: false, toast: true, position: 'top-end'
            });
        }
    });
};

// Debounced User Search
let searchTimeout;
watch(searchQuery, (value) => {
    clearTimeout(searchTimeout);
    if (value.length < 3 && value.length > 0) return;
    isLoadingSearch.value = true;
    searchTimeout = setTimeout(() => {
        router.get(route('yayasan.settings.index'), { search: value }, {
            preserveState: true, replace: true, preserveScroll: true,
            onFinish: () => isLoadingSearch.value = false
        });
    }, 500);
});

const toggleRole = (userId, roleName, currentStatus) => {
    const newStatus = !currentStatus;
    router.post(route('yayasan.settings.toggle-role'), {
        user_id: userId, role: roleName, status: newStatus ? 1 : 0
    }, {
        preserveScroll: true, preserveState: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: newStatus ? 'Akses Diberikan' : 'Akses Dicabut',
                text: `Hak akses ${roleName} telah diperbarui.`,
                timer: 1500, showConfirmButton: false, toast: true, position: 'top-end'
            });
        }
    });
};

const hasRole = (userRoles, roleName) => userRoles.includes(roleName);

// Feature Toggles - initialize from server props
const featureToggles = ref({
    maintenance_mode: props.settings?.maintenance_mode === '1',
    feature_finance: props.settings?.feature_finance !== '0',
    feature_sarpar: props.settings?.feature_sarpar !== '0',
    feature_counseling: props.settings?.feature_counseling !== '0',
    feature_academic: props.settings?.feature_academic !== '0',
    feature_employee: props.settings?.feature_employee !== '0',
    feature_student_login: props.settings?.feature_student_login !== '0',
});

const toggleFeature = (key, label, isDanger = false) => {
    const newValue = !featureToggles.value[key];
    const confirmText = newValue
        ? `${label} akan diaktifkan kembali.`
        : (isDanger
            ? `⚠️ ${label} akan DINONAKTIFKAN! Semua pengguna tidak dapat mengaksesnya.`
            : `${label} akan dinonaktifkan sementara.`);

    Swal.fire({
        title: newValue ? `Aktifkan ${label}?` : `Nonaktifkan ${label}?`,
        text: confirmText,
        icon: !newValue && isDanger ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: newValue ? '#0d9488' : '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: newValue ? 'Ya, Aktifkan!' : 'Ya, Nonaktifkan!',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            featureToggles.value[key] = newValue;
            router.post(route('yayasan.settings.toggle-feature'), {
                key, value: newValue ? '1' : '0'
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: newValue ? 'success' : 'info',
                        title: newValue ? 'Diaktifkan!' : 'Dinonaktifkan!',
                        text: `${label} berhasil ${newValue ? 'diaktifkan' : 'dinonaktifkan'}.`,
                        timer: 1800, showConfirmButton: false, toast: true, position: 'top-end'
                    });
                },
                onError: () => { featureToggles.value[key] = !newValue; } // revert on error
            });
        }
    });
};

// --- WAHA QUEUE MANAGEMENT STATE & HANDLERS ---
const qStats = ref({ pending: 0, sending: 0, sent: 0, failed: 0, cancelled: 0, is_paused: false });
const qList = ref({ data: [], current_page: 1, last_page: 1, total: 0 });
const qStatusFilter = ref('');
const qSearchQuery = ref('');
const loadingQStats = ref(false);
const loadingQList = ref(false);

const fetchQueueStats = async () => {
    loadingQStats.value = true;
    try {
        const res = await axios.get(route('yayasan.settings.waha-queue.stats'));
        qStats.value = res.data;
    } catch (e) {
        console.error(e);
    } finally {
        loadingQStats.value = false;
    }
};

const fetchQueueList = async (page = 1) => {
    loadingQList.value = true;
    try {
        const res = await axios.get(route('yayasan.settings.waha-queue.list'), {
            params: {
                page,
                status: qStatusFilter.value,
                search: qSearchQuery.value
            }
        });
        qList.value = res.data;
    } catch (e) {
        console.error(e);
    } finally {
        loadingQList.value = false;
    }
};

const toggleQueueStatus = async () => {
    const nextStatus = qStats.value.is_paused ? 'active' : 'paused';
    try {
        await axios.post(route('yayasan.settings.waha-queue.toggle'), { status: nextStatus });
        fetchQueueStats();
        Swal.fire({
            icon: 'success',
            title: nextStatus === 'active' ? 'Pengiriman Dilanjutkan' : 'Pengiriman Dijeda',
            text: `Antrean WhatsApp berhasil ${nextStatus === 'active' ? 'dilanjutkan' : 'dijeda'}.`,
            timer: 1500, showConfirmButton: false, toast: true, position: 'top-end'
        });
    } catch (e) {
        console.error(e);
    }
};

const cancelMessage = async (id) => {
    try {
        await axios.post(route('yayasan.settings.waha-queue.cancel'), { id });
        fetchQueueStats();
        fetchQueueList(qList.value.current_page);
        Swal.fire({
            icon: 'success', title: 'Dibatalkan', text: 'Pesan berhasil dibatalkan dari antrean.',
            timer: 1500, showConfirmButton: false, toast: true, position: 'top-end'
        });
    } catch (e) {
        console.error(e);
    }
};

const retryMessage = async (id) => {
    try {
        await axios.post(route('yayasan.settings.waha-queue.retry'), { id });
        fetchQueueStats();
        fetchQueueList(qList.value.current_page);
        Swal.fire({
            icon: 'success', title: 'Diantrekan', text: 'Pesan berhasil diantrekan kembali.',
            timer: 1500, showConfirmButton: false, toast: true, position: 'top-end'
        });
    } catch (e) {
        console.error(e);
    }
};

watch(activeTab, (newTab) => {
    if (newTab === 'whatsapp') {
        fetchQueueStats();
        fetchQueueList();
    }
});

let qSearchTimeout;
watch(qSearchQuery, () => {
    clearTimeout(qSearchTimeout);
    qSearchTimeout = setTimeout(() => {
        fetchQueueList();
    }, 500);
});

watch(qStatusFilter, () => {
    fetchQueueList();
});
</script>

<template>
    <Head title="Pusat Kontrol & Pengaturan" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight flex items-center gap-2">
                    <Cog6ToothIcon class="w-7 h-7 text-teal-600" />
                    Pusat Kontrol Super Admin
                </h2>
                <p class="text-sm text-gray-500 mt-1">Identitas sistem, kontrol fitur & modul, dan manajemen hak akses.</p>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto pb-24 space-y-6">

            <!-- Tabs Navigation (Pill style) -->
            <div class="flex gap-1 bg-gray-100 p-1.5 rounded-2xl w-fit shadow-inner">
                <button @click="activeTab = 'identity'"
                    class="px-5 py-2.5 rounded-xl font-bold text-sm transition-all duration-200 flex items-center gap-2"
                    :class="activeTab === 'identity' ? 'bg-white shadow-sm text-teal-700' : 'text-gray-500 hover:text-gray-700'">
                    <BuildingLibraryIcon class="w-4 h-4" /> Identitas
                </button>
                <button @click="activeTab = 'system'"
                    class="px-5 py-2.5 rounded-xl font-bold text-sm transition-all duration-200 flex items-center gap-2"
                    :class="activeTab === 'system' ? 'bg-white shadow-sm text-teal-700' : 'text-gray-500 hover:text-gray-700'">
                    <BoltIcon class="w-4 h-4" /> Kontrol Fitur
                </button>
                <button @click="activeTab = 'access'"
                    class="px-5 py-2.5 rounded-xl font-bold text-sm transition-all duration-200 flex items-center gap-2"
                    :class="activeTab === 'access' ? 'bg-white shadow-sm text-teal-700' : 'text-gray-500 hover:text-gray-700'">
                    <ShieldCheckIcon class="w-4 h-4" /> Hak Akses
                </button>
                <button @click="activeTab = 'whatsapp'"
                    class="px-5 py-2.5 rounded-xl font-bold text-sm transition-all duration-200 flex items-center gap-2"
                    :class="activeTab === 'whatsapp' ? 'bg-white shadow-sm text-teal-700' : 'text-gray-500 hover:text-gray-700'">
                    <ChatBubbleLeftRightIcon class="w-4 h-4" /> WhatsApp Gateway
                </button>
                <button @click="activeTab = 'logs'"
                    class="px-5 py-2.5 rounded-xl font-bold text-sm transition-all duration-200 flex items-center gap-2"
                    :class="activeTab === 'logs' ? 'bg-white shadow-sm text-teal-700' : 'text-gray-500 hover:text-gray-700'">
                    <ClipboardDocumentListIcon class="w-4 h-4" /> Log Aktivitas
                </button>
            </div>

            <!-- ============================= -->
            <!-- Tab 1: Identity & Branding    -->
            <!-- ============================= -->
            <div v-show="activeTab === 'identity'">
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 p-8">
                    <form @submit.prevent="saveGlobalSettings" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-6">
                            <h3 class="text-lg font-bold text-gray-800 border-b pb-3 flex items-center gap-2">
                                <span class="p-1.5 bg-violet-50 rounded-lg">
                                    <SwatchIcon class="w-5 h-5 text-violet-600" />
                                </span>
                                Branding Utama
                            </h3>
                            <div class="flex flex-col items-center p-6 border-2 border-dashed border-gray-200 rounded-2xl bg-gray-50/50 hover:bg-gray-50 transition-colors">
                                <div class="w-32 h-32 mb-4 rounded-xl overflow-hidden border shadow-sm bg-white flex items-center justify-center">
                                    <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-contain p-2" />
                                    <BuildingLibraryIcon v-else class="w-12 h-12 text-gray-300" />
                                </div>
                                <label class="cursor-pointer bg-white border border-gray-200 px-4 py-2 rounded-lg text-sm font-bold text-gray-600 shadow-sm hover:shadow-md transition-all flex items-center gap-2">
                                    <ArrowUpTrayIcon class="w-4 h-4" />
                                    Ganti Logo Yayasan
                                    <input type="file" class="hidden" accept="image/*" @change="handleLogoUpload">
                                </label>
                                <p class="text-xs text-gray-400 mt-2">Gunakan format PNG transparan untuk hasil terbaik.</p>
                            </div>
                            <div>
                                <InputLabel for="app_name" value="Nama Aplikasi / Yayasan" />
                                <TextInput id="app_name" v-model="form.app_name" class="w-full mt-1" />
                            </div>
                        </div>
                        <div class="space-y-6">
                            <h3 class="text-lg font-bold text-gray-800 border-b pb-3 flex items-center gap-2">
                                <span class="p-1.5 bg-blue-50 rounded-lg">
                                    <PhoneIcon class="w-5 h-5 text-blue-600" />
                                </span>
                                Informasi Kontak
                            </h3>
                            <div>
                                <InputLabel for="contact_email" value="Email Resmi" />
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <AtSymbolIcon class="w-5 h-5" />
                                    </div>
                                    <TextInput id="contact_email" v-model="form.contact_email" type="email" class="w-full pl-10" />
                                </div>
                            </div>
                            <div>
                                <InputLabel for="contact_phone" value="Nomor Telepon / WA" />
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <PhoneIcon class="w-5 h-5" />
                                    </div>
                                    <TextInput id="contact_phone" v-model="form.contact_phone" class="w-full pl-10" />
                                </div>
                            </div>
                            <div>
                                <InputLabel for="address" value="Alamat Lengkap" />
                                <div class="relative mt-1">
                                    <div class="absolute top-3 left-0 pl-3 flex items-start pointer-events-none text-gray-400">
                                        <MapPinIcon class="w-5 h-5" />
                                    </div>
                                    <textarea id="address" v-model="form.address" rows="3"
                                        class="w-full pl-10 border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-xl shadow-sm">
                                    </textarea>
                                </div>
                            </div>
                            <div class="flex justify-end pt-2">
                                <PrimaryButton :disabled="form.processing" class="px-8 py-3 rounded-xl">
                                    Simpan Pengaturan
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ================================= -->
            <!-- Tab 2: System & Feature Controls  -->
            <!-- ================================= -->
            <div v-show="activeTab === 'system'" class="space-y-6">

                <!-- Maintenance Mode — Hero Card -->
                <div class="rounded-3xl border-2 overflow-hidden transition-all duration-500"
                    :class="featureToggles.maintenance_mode ? 'border-red-300 bg-red-50' : 'border-gray-100 bg-white'">
                    <div class="p-6 flex items-start gap-5">
                        <div class="p-3 rounded-2xl flex-shrink-0 transition-colors"
                            :class="featureToggles.maintenance_mode ? 'bg-red-100' : 'bg-amber-50'">
                            <WrenchScrewdriverIcon class="w-8 h-8"
                                :class="featureToggles.maintenance_mode ? 'text-red-600' : 'text-amber-500'" />
                        </div>
                        <div class="flex-1">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-xl font-extrabold"
                                        :class="featureToggles.maintenance_mode ? 'text-red-700' : 'text-gray-900'">
                                        Mode Maintenance
                                        <span v-if="featureToggles.maintenance_mode"
                                            class="ml-2 text-xs font-bold bg-red-600 text-white px-3 py-0.5 rounded-full animate-pulse">
                                            ● AKTIF
                                        </span>
                                    </h3>
                                    <p class="text-sm mt-1"
                                        :class="featureToggles.maintenance_mode ? 'text-red-600' : 'text-gray-500'">
                                        Saat diaktifkan, <strong>semua pengguna kecuali Super Admin</strong> akan melihat halaman maintenance. Gunakan saat update sistem atau perbaikan besar.
                                    </p>
                                </div>
                                <!-- Large Toggle -->
                                <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 mt-1">
                                    <input type="checkbox" class="sr-only peer"
                                        :checked="featureToggles.maintenance_mode"
                                        @change="toggleFeature('maintenance_mode', 'Mode Maintenance', true)">
                                    <div class="w-16 h-8 bg-gray-200 rounded-full peer peer-checked:after:translate-x-8 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:rounded-full after:h-7 after:w-7 after:transition-all peer-checked:bg-red-500 shadow-inner transition-colors duration-300"></div>
                                </label>
                            </div>
                            <!-- Message editor (only shown when active) -->
                            <div v-if="featureToggles.maintenance_mode"
                                class="mt-4 p-4 bg-red-100/50 rounded-2xl border border-red-200 space-y-2">
                                <InputLabel value="Pesan yang ditampilkan ke pengguna:" class="!text-red-700 !font-bold" />
                                <textarea v-model="form.maintenance_message" rows="2"
                                    class="w-full border-red-200 focus:border-red-400 focus:ring-red-300 rounded-xl shadow-sm bg-white text-sm"></textarea>
                                <button type="button" @click="saveGlobalSettings"
                                    class="px-4 py-1.5 bg-red-600 text-white rounded-lg text-sm font-bold hover:bg-red-700 transition-colors">
                                    Simpan Pesan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature Module Toggles -->
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <BoltIcon class="w-5 h-5 text-teal-600" />
                        Kontrol Modul Fitur
                        <span class="text-xs font-normal text-gray-400">— klik saklar untuk mengaktifkan/menonaktifkan modul</span>
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="feature in [
                            { key: 'feature_academic',      label: 'Modul Akademik',         desc: 'Kelas, guru, siswa, jadwal, jurnal.',           icon: AcademicCapIcon,       color: 'text-violet-600',  bg: 'bg-violet-50',  danger: true  },
                            { key: 'feature_finance',       label: 'Modul Keuangan',         desc: 'Tagihan, pembayaran, laporan tunggakan.',       icon: BanknotesIcon,         color: 'text-emerald-600', bg: 'bg-emerald-50', danger: false },
                            { key: 'feature_employee',      label: 'Modul Kepegawaian',      desc: 'Data staf, presensi, dan penggajian.',          icon: UserGroupIcon,         color: 'text-blue-600',    bg: 'bg-blue-50',    danger: false },
                            { key: 'feature_counseling',    label: 'Modul BK',               desc: 'Konseling, pelanggaran, dan prestasi.',         icon: ChatBubbleLeftRightIcon, color: 'text-amber-600', bg: 'bg-amber-50',   danger: false },
                            { key: 'feature_sarpar',        label: 'Modul Sarana Prasarana', desc: 'Inventaris, ruangan, perawatan, peminjaman.',   icon: BuildingOffice2Icon,   color: 'text-cyan-600',    bg: 'bg-cyan-50',    danger: false },
                            { key: 'feature_student_login', label: 'Login Siswa',            desc: 'Akses portal siswa ke dalam aplikasi.',         icon: IdentificationIcon,    color: 'text-rose-600',    bg: 'bg-rose-50',    danger: true  },
                        ]" :key="feature.key"
                            class="bg-white rounded-2xl p-5 border-2 transition-all duration-300 shadow-sm hover:shadow-md group"
                            :class="featureToggles[feature.key] ? 'border-gray-100' : 'border-red-200 bg-red-50/60'">

                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3">
                                    <!-- Professional Icon with colored background -->
                                    <div class="p-2.5 rounded-xl flex-shrink-0 transition-colors"
                                        :class="featureToggles[feature.key] ? feature.bg : 'bg-red-100'">
                                        <component :is="feature.icon"
                                            class="w-5 h-5 transition-colors"
                                            :class="featureToggles[feature.key] ? feature.color : 'text-red-500'" />
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900 leading-tight text-sm">{{ feature.label }}</h4>
                                        <p class="text-xs text-gray-400 mt-0.5 leading-snug">{{ feature.desc }}</p>
                                    </div>
                                </div>
                                <!-- Toggle Switch -->
                                <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 mt-0.5">
                                    <input type="checkbox" class="sr-only peer"
                                        :checked="featureToggles[feature.key]"
                                        @change="toggleFeature(feature.key, feature.label, feature.danger)">
                                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-500 shadow-inner transition-colors duration-300"></div>
                                </label>
                            </div>

                            <!-- Status Badge when OFF -->
                            <div v-if="!featureToggles[feature.key]"
                                class="mt-3 flex items-center gap-1.5 text-red-500 text-xs font-semibold">
                                <ExclamationTriangleIcon class="w-3.5 h-3.5 flex-shrink-0" />
                                Modul ini sedang nonaktif
                            </div>
                            <!-- Status Badge when ON -->
                            <div v-else
                                class="mt-3 flex items-center gap-1.5 text-gray-400 text-xs">
                                <CheckCircleIcon class="w-3.5 h-3.5 flex-shrink-0 text-teal-400" />
                                Aktif & berjalan normal
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================================= -->
            <!-- Tab 3: Quick Role Access Manager  -->
            <!-- ================================= -->
            <div v-show="activeTab === 'access'">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-3xl mb-6 border border-blue-100 flex items-start gap-4">
                    <ShieldCheckIcon class="w-8 h-8 text-blue-500 mt-1 flex-shrink-0" />
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Manajemen Hak Akses Super Cepat</h3>
                        <p class="text-gray-600 text-sm mt-1">
                            Cari nama pegawai dan aktifkan/nonaktifkan jabatannya menggunakan saklar. Perubahan langsung tersimpan secara <em>real-time</em>.
                        </p>
                    </div>
                </div>

                <!-- Search -->
                <div class="relative mb-6">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <MagnifyingGlassIcon v-if="!isLoadingSearch" class="w-6 h-6 text-gray-400" />
                        <div v-else class="w-6 h-6 border-2 border-teal-500 border-t-transparent rounded-full animate-spin"></div>
                    </div>
                    <input v-model="searchQuery" type="text"
                        placeholder="Ketik nama pegawai atau email untuk mencari..."
                        class="w-full pl-12 pr-4 py-4 rounded-2xl border-gray-200 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 text-lg transition-all">
                </div>

                <!-- Results -->
                <div v-if="searchQuery.length >= 3" class="space-y-4">
                    <div v-if="users.length === 0" class="text-center py-10 bg-white rounded-3xl border border-gray-100 shadow-sm">
                        <UserIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                        <h3 class="text-lg font-bold text-gray-800">Tidak Ditemukan</h3>
                        <p class="text-gray-500 text-sm">Tidak ada pengguna dengan nama atau email tersebut.</p>
                    </div>

                    <div v-for="user in users" :key="user.id"
                        class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex flex-col lg:flex-row gap-6 items-start lg:items-center">
                            <div class="flex items-center gap-4 w-full lg:w-1/3">
                                <img :src="user.profile_photo_url" class="w-14 h-14 rounded-full border-2 border-gray-100 object-cover" />
                                <div>
                                    <h4 class="font-bold text-gray-900 text-lg leading-tight">{{ user.name }}</h4>
                                    <p class="text-sm text-gray-500">{{ user.email }}</p>
                                    <span class="text-xs font-bold text-teal-600 bg-teal-50 px-2 py-0.5 rounded-md border border-teal-100 mt-1 inline-block">
                                        {{ user.roles.length }} Hak Akses Aktif
                                    </span>
                                </div>
                            </div>
                            <div class="w-full lg:w-2/3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 border-t lg:border-t-0 lg:border-l border-gray-100 pt-4 lg:pt-0 lg:pl-6">
                                <div v-for="role in availableRoles" :key="role.name"
                                    class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 border border-transparent hover:border-gray-100 transition-all">
                                    <span class="text-sm font-bold text-gray-700">{{ role.label }}</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer"
                                            :checked="hasRole(user.roles, role.name)"
                                            @change="toggleRole(user.id, role.name, hasRole(user.roles, role.name))">
                                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-500 shadow-inner"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else-if="searchQuery.length > 0" class="text-center py-10 text-gray-400 font-bold">
                    Ketik minimal 3 huruf untuk mulai mencari...
                </div>
                <div v-else class="text-center py-12 bg-gray-50 rounded-3xl border border-dashed border-gray-200">
                    <MagnifyingGlassIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                    <h3 class="text-lg font-bold text-gray-600">Mulai Pencarian</h3>
                    <p class="text-gray-400 text-sm">Cari berdasarkan nama atau email untuk mengelola hak akses.</p>
                </div>
            </div>

            <!-- ================================= -->
            <!-- Tab: WhatsApp Gateway Config      -->
            <!-- ================================= -->
            <div v-show="activeTab === 'whatsapp'" class="space-y-6">
                <!-- WAHA Config & Control Panel -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Config Panel (Col 1-2) -->
                    <div class="lg:col-span-2 bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 p-8 space-y-6">
                        <div class="border-b pb-3 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="p-1.5 bg-teal-50 rounded-lg text-teal-600">
                                    <ChatBubbleLeftRightIcon class="w-5 h-5" />
                                </span>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800">Konfigurasi Server WAHA</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Atur endpoint koneksi WhatsApp HTTP API (WAHA) lokal maupun server sekolah.</p>
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="saveGlobalSettings" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <InputLabel for="waha_url" value="WAHA Server URL" />
                                    <TextInput id="waha_url" v-model="form.waha_url" placeholder="http://localhost:3000" class="w-full mt-1" />
                                    <p class="text-[10px] text-gray-400 mt-1">Gunakan alamat IP lokal jika di-host di komputer lain (contoh: http://192.168.1.50:3000).</p>
                                </div>

                                <div>
                                    <InputLabel for="waha_session" value="Nama Sesi WAHA (Session Name)" />
                                    <TextInput id="waha_session" v-model="form.waha_session" placeholder="namira1" class="w-full mt-1" />
                                    <p class="text-[10px] text-gray-400 mt-1">Sesuai nama sesi di dashboard WAHA Docker Anda.</p>
                                </div>
                            </div>

                            <div class="space-y-4 flex flex-col justify-between">
                                <div>
                                    <InputLabel for="waha_api_key" value="WAHA API Key (Kunci Pengaman)" />
                                    <TextInput id="waha_api_key" v-model="form.waha_api_key" type="password" placeholder="Masukkan Kunci API..." class="w-full mt-1" />
                                    <p class="text-[10px] text-gray-400 mt-1">Kunci API pengaman yang disalin dari terminal/dashboard WAHA.</p>
                                </div>

                                <div class="flex justify-end pt-2">
                                    <PrimaryButton :disabled="form.processing" class="px-8 py-3 rounded-xl w-full sm:w-auto">
                                        Simpan Pengaturan
                                    </PrimaryButton>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Queue Control Card (Col 3) -->
                    <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 p-8 flex flex-col justify-between space-y-6">
                        <div class="border-b pb-3">
                            <h3 class="text-lg font-bold text-gray-800">Status Antrean Global</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Kendalikan alur pengiriman WhatsApp sistem secara langsung.</p>
                        </div>

                        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Status Antrean</span>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-3 h-3 rounded-full" :class="qStats.is_paused ? 'bg-amber-500' : 'bg-emerald-500 animate-pulse'"></div>
                                    <span class="text-sm font-bold" :class="qStats.is_paused ? 'text-amber-600' : 'text-emerald-600'">
                                        {{ qStats.is_paused ? 'DIJEDA (PAUSED)' : 'AKTIF / BERJALAN' }}
                                    </span>
                                </div>
                            </div>

                            <p class="text-xs text-gray-500 leading-relaxed">
                                {{ qStats.is_paused 
                                    ? 'Pengiriman antrean dijeda sementara. Semua pesan baru akan disimpan di antrean dengan status pending.' 
                                    : 'Worker sedang aktif mendistribusikan antrean pesan secara berurutan dengan human delay (400-900ms) dan backoff.' }}
                            </p>
                        </div>

                        <div>
                            <button @click="toggleQueueStatus"
                                class="w-full py-3.5 rounded-2xl font-bold text-sm transition-all duration-200 border flex items-center justify-center gap-2"
                                :class="qStats.is_paused 
                                    ? 'bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100 shadow-sm' 
                                    : 'bg-amber-50 border-amber-200 text-amber-700 hover:bg-amber-100 shadow-sm'">
                                <component :is="qStats.is_paused ? CheckCircleIcon : ClockIcon" class="w-5 h-5" />
                                {{ qStats.is_paused ? 'LANJUTKAN PENGIRIMAN (RESUME)' : 'JEDA PENGIRIMAN (PAUSE)' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- WhatsApp Queue Monitor Section -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden space-y-6 p-6">
                    <div class="border-b pb-3 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Daftar Antrean & Monitor Pengiriman</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Daftar pesan otomatis (absensi siswa, kuitansi transaksi) yang diproses antrean.</p>
                        </div>
                        <button @click="fetchQueueStats(); fetchQueueList()" class="px-4 py-2 bg-slate-50 hover:bg-slate-100 text-slate-600 text-xs font-bold rounded-lg border border-slate-200 flex items-center gap-1.5 transition-colors">
                            <ArrowUpTrayIcon class="w-3.5 h-3.5 rotate-180" /> Segarkan Data
                        </button>
                    </div>

                    <!-- Statistics Summary Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 text-center">
                            <span class="text-xs text-gray-400 font-bold block mb-1">Menunggu (Pending)</span>
                            <span class="text-2xl font-black text-gray-600">{{ qStats.pending }}</span>
                        </div>
                        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 text-center">
                            <span class="text-xs text-blue-400 font-bold block mb-1">Mengirim (Sending)</span>
                            <span class="text-2xl font-black text-blue-600">{{ qStats.sending }}</span>
                        </div>
                        <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 text-center">
                            <span class="text-xs text-emerald-400 font-bold block mb-1">Sukses (Sent)</span>
                            <span class="text-2xl font-black text-emerald-600">{{ qStats.sent }}</span>
                        </div>
                        <div class="bg-rose-50 border border-rose-100 rounded-2xl p-4 text-center">
                            <span class="text-xs text-rose-400 font-bold block mb-1">Gagal (Failed)</span>
                            <span class="text-2xl font-black text-rose-600">{{ qStats.failed }}</span>
                        </div>
                        <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 text-center">
                            <span class="text-xs text-amber-400 font-bold block mb-1">Dibatalkan</span>
                            <span class="text-2xl font-black text-amber-600">{{ qStats.cancelled }}</span>
                        </div>
                    </div>

                    <!-- Filters & Search -->
                    <div class="flex flex-col sm:flex-row gap-4 items-center">
                        <div class="w-full sm:w-1/3">
                            <select v-model="qStatusFilter" class="w-full rounded-xl border-gray-200 text-sm focus:border-teal-500 focus:ring-teal-200">
                                <option value="">Semua Status</option>
                                <option value="pending">Menunggu (Pending)</option>
                                <option value="sending">Mengirim (Sending)</option>
                                <option value="sent">Sukses Terkirim (Sent)</option>
                                <option value="failed">Gagal (Failed)</option>
                                <option value="cancelled">Dibatalkan (Cancelled)</option>
                            </select>
                        </div>
                        <div class="w-full sm:w-2/3 relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <MagnifyingGlassIcon class="w-4 h-4" />
                            </div>
                            <input v-model="qSearchQuery" type="text" placeholder="Cari nomor HP atau isi pesan..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border-gray-200 text-sm focus:border-teal-500 focus:ring-teal-200" />
                        </div>
                    </div>

                    <!-- Queue Table -->
                    <div class="border border-gray-100 rounded-2xl overflow-hidden bg-white shadow-inner">
                        <div v-if="loadingQList" class="p-12 text-center text-gray-500 flex flex-col items-center justify-center">
                            <div class="w-10 h-10 border-4 border-teal-500 border-t-transparent rounded-full animate-spin mb-3"></div>
                            <p class="text-sm font-bold">Memuat antrean pesan...</p>
                        </div>
                        <div v-else-if="qList.data.length === 0" class="p-12 text-center text-gray-500">
                            <ChatBubbleLeftRightIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                            <p class="text-sm font-bold">Tidak ada pesan dalam antrean.</p>
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        <th class="p-4 w-16">ID</th>
                                        <th class="p-4 w-28">Penerima (HP)</th>
                                        <th class="p-4">Isi Pesan</th>
                                        <th class="p-4 w-32">Status</th>
                                        <th class="p-4 w-44">Keterangan / Percobaan</th>
                                        <th class="p-4 w-24 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 text-sm text-gray-700">
                                    <tr v-for="item in qList.data" :key="item.id" class="hover:bg-slate-50/50 transition-colors">
                                        <td class="p-4 font-mono font-bold text-xs text-gray-400">#{{ item.id }}</td>
                                        <td class="p-4 font-bold text-gray-900">{{ item.phone }}</td>
                                        <td class="p-4 max-w-xs md:max-w-md truncate" :title="item.message">{{ item.message }}</td>
                                        <td class="p-4">
                                            <span v-if="item.status === 'sent'" class="inline-flex items-center gap-1 text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 px-2 py-0.5 rounded-md">
                                                <CheckCircleIcon class="w-3.5 h-3.5 text-emerald-500" /> TERKIRIM
                                            </span>
                                            <span v-else-if="item.status === 'sending'" class="inline-flex items-center gap-1.5 text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100 px-2 py-0.5 rounded-md">
                                                <div class="w-2.5 h-2.5 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div> MENGIRIM
                                            </span>
                                            <span v-else-if="item.status === 'failed'" class="inline-flex items-center gap-1 text-xs font-bold bg-rose-50 text-rose-700 border border-rose-100 px-2 py-0.5 rounded-md">
                                                <ExclamationTriangleIcon class="w-3.5 h-3.5 text-rose-500" /> GAGAL
                                            </span>
                                            <span v-else-if="item.status === 'cancelled'" class="inline-flex items-center gap-1 text-xs font-bold bg-gray-50 text-gray-500 border border-gray-200 px-2 py-0.5 rounded-md">
                                                DIBATALKAN
                                            </span>
                                            <span v-else class="inline-flex items-center gap-1 text-xs font-bold bg-slate-50 text-slate-600 border border-slate-200 px-2 py-0.5 rounded-md">
                                                MENUNGGU
                                            </span>
                                        </td>
                                        <td class="p-4 text-xs">
                                            <div v-if="item.status === 'failed'" class="text-rose-500 leading-tight" :title="item.error_message">
                                                {{ item.error_message ? (item.error_message.length > 50 ? item.error_message.substring(0, 50) + '...' : item.error_message) : 'Gagal terkirim' }}
                                            </div>
                                            <div v-else-if="item.status === 'pending' && item.retry_count > 0" class="text-amber-500 leading-tight">
                                                Penundaan (Retry #{{ item.retry_count }})<br/>
                                                <span class="text-[10px] text-gray-400">Next: {{ item.next_attempt_at ? new Date(item.next_attempt_at).toLocaleTimeString() : '-' }}</span>
                                            </div>
                                            <div v-else class="text-gray-400">
                                                {{ item.status === 'sent' ? 'Selesai' : 'Belum dicoba' }}
                                            </div>
                                        </td>
                                        <td class="p-4 text-right space-x-1 whitespace-nowrap">
                                            <!-- Action: Cancel (for pending/failed) -->
                                            <button v-if="['pending', 'failed'].includes(item.status)" @click="cancelMessage(item.id)" class="text-xs bg-slate-50 hover:bg-rose-50 border border-slate-200 hover:border-rose-200 text-slate-600 hover:text-rose-600 px-2.5 py-1 rounded-md transition-colors" title="Batalkan pesan">
                                                Batal
                                            </button>
                                            <!-- Action: Retry (for failed/cancelled) -->
                                            <button v-if="['failed', 'cancelled'].includes(item.status)" @click="retryMessage(item.id)" class="text-xs bg-teal-50 hover:bg-teal-100 border border-teal-200 text-teal-700 px-2.5 py-1 rounded-md transition-colors" title="Kirim ulang">
                                                Retry
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="qList.last_page > 1" class="flex justify-between items-center pt-2">
                        <span class="text-xs text-gray-500">
                            Menampilkan halaman <strong>{{ qList.current_page }}</strong> dari <strong>{{ qList.last_page }}</strong> (Total: {{ qList.total }} data)
                        </span>
                        <div class="flex gap-1">
                            <button :disabled="qList.current_page === 1" @click="fetchQueueList(qList.current_page - 1)" class="px-3 py-1.5 rounded-lg border text-xs font-bold transition-all" :class="qList.current_page === 1 ? 'bg-slate-50 text-gray-300 border-gray-100 cursor-not-allowed' : 'bg-white hover:bg-slate-50 border-gray-200 text-gray-600'">
                                Sebelum
                            </button>
                            <button :disabled="qList.current_page === qList.last_page" @click="fetchQueueList(qList.current_page + 1)" class="px-3 py-1.5 rounded-lg border text-xs font-bold transition-all" :class="qList.current_page === qList.last_page ? 'bg-slate-50 text-gray-300 border-gray-100 cursor-not-allowed' : 'bg-white hover:bg-slate-50 border-gray-200 text-gray-600'">
                                Sesudah
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================================= -->
            <!-- Tab 4: Activity Logs              -->
            <!-- ================================= -->
            <div v-show="activeTab === 'logs'">
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                                <ClipboardDocumentListIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Riwayat Aktivitas</h3>
                                <p class="text-sm text-gray-500">20 aktivitas terakhir yang dilakukan oleh admin</p>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="!activityLogs || activityLogs.length === 0" class="p-12 text-center text-gray-500">
                        <ClockIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                        <p>Belum ada log aktivitas yang tercatat.</p>
                    </div>

                    <div v-else class="divide-y divide-gray-100">
                        <div v-for="log in activityLogs" :key="log.id" class="p-5 hover:bg-gray-50 transition-colors flex items-start gap-4">
                            <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold flex-shrink-0 border border-slate-200">
                                {{ log.causer_name.substring(0, 1) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900 font-medium">
                                    <span class="font-bold text-slate-800">{{ log.causer_name }}</span>
                                    {{ log.description }}
                                </p>
                                <div class="mt-1 flex items-center gap-4 text-xs text-gray-500">
                                    <span class="flex items-center gap-1" :title="log.created_at_full">
                                        <ClockIcon class="w-3.5 h-3.5" />
                                        {{ log.created_at }}
                                    </span>
                                </div>
                                <!-- Metadata preview -->
                                <div v-if="log.properties" class="mt-2 text-[11px] font-mono bg-slate-50 text-slate-600 px-3 py-2 rounded-lg border border-slate-100 overflow-x-auto max-w-full">
                                    {{ JSON.stringify(log.properties) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
