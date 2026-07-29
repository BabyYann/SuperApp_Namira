<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { BuildingOffice2Icon, ArrowLeftIcon, CloudArrowUpIcon, InformationCircleIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    partner: Object,
    units: Array,
    is_approver: Boolean,
});

const isEdit = !!props.partner;
const page = usePage();
const currentUser = page.props.auth.user;

const defaultApprovalStatus = computed(() => {
    if (props.partner?.approval_status) return props.partner.approval_status;
    return props.is_approver ? 'published' : 'pending';
});

const form = useForm({
    _method: isEdit ? 'PUT' : 'POST',
    unit_id: props.partner?.unit_id || props.units?.[0]?.id || page.props.session?.active_unit_id || '',
    name: props.partner?.name || '',
    website_url: props.partner?.website_url || '',
    approval_status: defaultApprovalStatus.value,
    logo: null,
});

const logoPreview = ref(props.partner?.logo_path ? '/' + props.partner.logo_path : null);

const handleLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const submitWithStatus = (targetStatus) => {
    form.approval_status = targetStatus;
    submitForm();
};

const submitForm = () => {
    if (isEdit) {
        form.post(route('public-relations.partners.update', props.partner.id));
    } else {
        form.post(route('public-relations.partners.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Mitra Kemitraan' : 'Tambah Mitra Baru'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('public-relations.partners.index')" class="p-2 bg-white dark:bg-gray-800 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition shadow-sm border border-gray-100 dark:border-gray-700">
                    <ArrowLeftIcon class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                </Link>
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        {{ isEdit ? 'Ubah Data Mitra' : 'Tambah Mitra Baru' }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ isEdit ? 'Perbarui informasi data instansi kerjasama.' : 'Daftarkan instansi/lembaga kerjasama baru.' }}
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-3xl mx-auto space-y-6">

            <!-- Rejection Note Alert Banner if status is rejected -->
            <div v-if="isEdit && partner.approval_status === 'rejected' && partner.rejection_note" class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-start gap-3">
                <ExclamationCircleIcon class="w-6 h-6 text-rose-600 shrink-0 mt-0.5" />
                <div>
                    <h4 class="font-bold text-rose-800 text-sm">Catatan Revisi dari Verifikator</h4>
                    <p class="text-xs text-rose-700 mt-1">{{ partner.rejection_note }}</p>
                    <p class="text-[11px] text-rose-500 mt-2 font-medium">Silakan perbaiki data mitra sesuai catatan di atas, lalu klik "Ajukan Verifikasi" untuk meninjau ulang.</p>
                </div>
            </div>

            <div class="bg-white/80 backdrop-blur-xl rounded-3xl p-6 sm:p-8 shadow-sm border border-white/50">
                <form @submit.prevent="submitForm" class="space-y-6">

                    <!-- Unit Selection (if multiple) -->
                    <div v-if="units && units.length > 1">
                        <InputLabel for="unit_id" value="Unit Sekolah *" class="mb-1.5 text-xs font-bold text-gray-700 uppercase tracking-wider" />
                        <select v-model="form.unit_id" class="w-full rounded-2xl border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/10 text-sm py-3 px-4" required>
                            <option value="" disabled>Pilih Unit...</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                        </select>
                        <InputError class="mt-1.5" :message="form.errors.unit_id" />
                    </div>

                    <!-- Name Input -->
                    <div>
                        <InputLabel for="name" value="Nama Instansi / Mitra *" class="mb-1.5 text-xs font-bold text-gray-700 uppercase tracking-wider" />
                        <div class="relative">
                            <TextInput 
                                id="name"
                                type="text"
                                class="mt-1 block w-full py-3 px-4 border border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/10 rounded-2xl text-sm"
                                v-model="form.name"
                                required
                                placeholder="Contoh: Universitas Indonesia"
                            />
                        </div>
                        <InputError class="mt-1.5" :message="form.errors.name" />
                    </div>

                    <!-- Website URL Input -->
                    <div>
                        <InputLabel for="website_url" value="Website URL (Opsional)" class="mb-1.5 text-xs font-bold text-gray-700 uppercase tracking-wider" />
                        <div class="relative">
                            <TextInput 
                                id="website_url"
                                type="url"
                                class="mt-1 block w-full py-3 px-4 border border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/10 rounded-2xl text-sm"
                                v-model="form.website_url"
                                placeholder="Contoh: https://ui.ac.id"
                            />
                        </div>
                        <InputError class="mt-1.5" :message="form.errors.website_url" />
                    </div>

                    <!-- Logo Upload -->
                    <div>
                        <InputLabel value="Logo Mitra / Instansi" class="mb-1.5 text-xs font-bold text-gray-700 uppercase tracking-wider" />
                        
                        <div class="flex flex-col sm:flex-row items-center gap-6 mt-1 p-4 bg-gray-50/70 border border-dashed border-gray-200 rounded-2xl">
                            <!-- Preview Box -->
                            <div class="w-32 h-20 bg-white rounded-xl flex items-center justify-center p-2 border border-gray-100 overflow-hidden shrink-0 shadow-inner">
                                <img v-if="logoPreview" :src="logoPreview" alt="Logo Preview" class="w-full h-full object-contain" />
                                <BuildingOffice2Icon v-else class="w-10 h-10 text-gray-300" />
                            </div>

                            <!-- File Inputs -->
                            <div class="flex-1 w-full text-center sm:text-left space-y-2">
                                <label class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-200 hover:border-namira-teal hover:text-namira-teal rounded-xl shadow-sm text-xs font-bold transition-all cursor-pointer active:scale-95">
                                    <CloudArrowUpIcon class="w-4 h-4" />
                                    <span>Pilih File Gambar</span>
                                    <input 
                                        type="file" 
                                        class="hidden" 
                                        accept="image/*"
                                        @change="handleLogoChange"
                                    />
                                </label>
                                <p class="text-[11px] text-gray-400">Direkomendasikan format PNG transparan atau JPG, ukuran maksimal 2MB.</p>
                            </div>
                        </div>
                        <InputError class="mt-1.5" :message="form.errors.logo" />
                    </div>

                    <!-- Status Options based on Role -->
                    <div>
                        <InputLabel value="Status Publikasi Verifikasi" class="mb-1.5 text-xs font-bold text-gray-700 uppercase tracking-wider" />
                        
                        <div v-if="is_approver" class="space-y-2">
                            <select v-model="form.approval_status" class="w-full rounded-2xl border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/10 text-sm py-3 px-4" required>
                                <option value="published">Terbitkan Langsung (Published)</option>
                                <option value="pending">Menunggu Verifikasi (Pending)</option>
                                <option value="draft">Simpan Sebagai Draft</option>
                            </select>
                        </div>
                        <div v-else class="p-4 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-800 space-y-1">
                            <p class="font-bold flex items-center gap-1.5"><InformationCircleIcon class="w-4 h-4 text-amber-600" /> Alur Publikasi Mitra Humas:</p>
                            <p>Data mitra yang Anda simpan akan masuk ke status <strong>"Menunggu Verifikasi"</strong> terlebih dahulu untuk ditinjau oleh Verifikator (Pengawas / Humas Yayasan) sebelum tayang di website publik.</p>
                        </div>
                    </div>

                    <!-- Submit Actions -->
                    <div class="flex flex-wrap items-center justify-end gap-3 pt-6 border-t border-gray-100">
                        <Link :href="route('public-relations.partners.index')" class="px-6 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-500 rounded-2xl font-bold text-xs transition-all">
                            Batal
                        </Link>

                        <button 
                            v-if="!is_approver"
                            type="button" 
                            @click="submitWithStatus('draft')" 
                            :disabled="form.processing" 
                            class="px-5 py-2.5 bg-slate-700 text-white rounded-2xl font-bold hover:bg-slate-800 transition-all text-xs disabled:opacity-50"
                        >
                            Simpan Draft
                        </button>

                        <button 
                            type="submit" 
                            class="px-8 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 active:scale-95 text-xs"
                            :disabled="form.processing"
                        >
                            <span v-if="!form.processing">{{ is_approver ? (isEdit ? 'Perbarui Mitra' : 'Simpan Mitra') : 'Ajukan Verifikasi' }}</span>
                            <span v-else class="flex items-center gap-1.5">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
