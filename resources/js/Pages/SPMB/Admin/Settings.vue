<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { 
    ArrowLeftIcon, 
    Cog6ToothIcon, 
    CheckCircleIcon, 
    UserPlusIcon, 
    TrashIcon, 
    QrCodeIcon, 
    PhotoIcon,
    ShieldCheckIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    setting: Object,
    unit: Object,
    teachers: Array,
    assignedPanitia: Array,
});

const settingForm = useForm({
    name: props.setting?.name || 'Pendaftaran Jalur Inden SD Namira',
    academic_year: props.setting?.academic_year || '2026/2027',
    quota: props.setting?.quota || 60,
    registration_fee: props.setting?.registration_fee || 250000,
    admission_fee_total: props.setting?.admission_fee_total || 8500000,
    min_down_payment_percentage: props.setting?.min_down_payment_percentage || 60,
    bank_name: props.setting?.bank_name || 'Bank Jatim',
    bank_account_number: props.setting?.bank_account_number || '',
    bank_account_holder: props.setting?.bank_account_holder || 'YAYASAN NAMIRA PROBOLINGGO',
    contact_whatsapp: props.setting?.contact_whatsapp || '082332922521',
    notes: props.setting?.notes || '',
    is_active: props.setting?.is_active ?? true,
    qris_image: null,
});

const qrisPreview = ref(props.setting?.qris_image_url || null);

const handleQrisUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        settingForm.qris_image = file;
        qrisPreview.value = URL.createObjectURL(file);
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
});

const assignPanitia = () => {
    if (!panitiaForm.user_id) {
        alert('Pilih guru terlebih dahulu.');
        return;
    }
    panitiaForm.post(route('spmb.admin.assign-panitia'), {
        preserveScroll: true,
        onSuccess: () => {
            panitiaForm.reset();
            alert('Guru berhasil ditugaskan sebagai Panitia SPMB.');
        }
    });
};

const removePanitia = (user) => {
    if (confirm(`Cabut wewenang Panitia SPMB untuk ${user.name}?`)) {
        router.delete(route('spmb.admin.remove-panitia', user.id));
    }
};
</script>

<template>
    <Head title="Pengaturan & Penugasan Panitia SPMB" />

    <AuthenticatedLayout>
        <div class="space-y-6 max-w-5xl mx-auto">
            
            <!-- Breadcrumbs -->
            <div>
                <Link 
                    :href="route('spmb.admin.index')"
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
                    Atur kuota, biaya formulir, QRIS Bank Jatim, serta delegasikan wewenang panitia kepada guru SD Namira.
                </p>
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
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Tahun Ajaran</label>
                                    <input v-model="settingForm.academic_year" type="text" placeholder="2026/2027" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-white text-xs">
                                </div>

                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Target Kuota Siswa</label>
                                    <input v-model="settingForm.quota" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-white text-xs">
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
                            </div>

                            <!-- Upload Gambar QRIS -->
                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-3">
                                <span class="font-bold text-slate-800 dark:text-slate-200 block">Gambar Kode QRIS Bank Jatim</span>
                                <div class="flex items-center gap-4">
                                    <div class="w-24 h-24 rounded-xl border border-dashed border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 flex items-center justify-center overflow-hidden shrink-0">
                                        <img v-if="qrisPreview" :src="qrisPreview" class="w-full h-full object-contain">
                                        <QrCodeIcon v-else class="w-8 h-8 text-slate-400" />
                                    </div>
                                    <div>
                                        <label class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 text-xs font-semibold text-slate-800 dark:text-white transition">
                                            <span>Pilih Foto QRIS</span>
                                            <input type="file" accept="image/*" class="hidden" @change="handleQrisUpload">
                                        </label>
                                        <span class="text-[11px] text-slate-400 block mt-1">Format: JPG atau PNG maksimal 3MB.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Pendaftaran Buka / Tutup -->
                            <div class="flex items-center gap-3 pt-2">
                                <input type="checkbox" id="isActive" v-model="settingForm.is_active" class="rounded text-emerald-600 focus:ring-emerald-500">
                                <label for="isActive" class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                    Pendaftaran Jalur Inden Sedang Dibuka untuk Umum
                                </label>
                            </div>

                            <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                                <button 
                                    type="submit" 
                                    :disabled="settingForm.processing"
                                    class="px-6 py-2.5 rounded-xl bg-[#064e3b] hover:bg-emerald-700 text-white font-bold text-xs shadow transition disabled:opacity-50"
                                >
                                    {{ settingForm.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
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
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Tugaskan Guru SD:</label>
                            <select v-model="panitiaForm.user_id" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-200">
                                <option value="" disabled>-- Pilih Ustadz / Ustadzah --</option>
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
                                <span>Tugaskan sebagai Panitia</span>
                            </button>
                        </div>

                        <!-- Daftar Panitia Aktif -->
                        <div class="space-y-2 pt-2">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block">Daftar Panitia Saat Ini:</span>
                            
                            <div v-if="assignedPanitia.length === 0" class="text-xs text-slate-400 italic py-2">
                                Belum ada guru yang ditugaskan khusus sebagai panitia.
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
    </AuthenticatedLayout>
</template>
