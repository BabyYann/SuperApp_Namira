<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { PhotoIcon, ChatBubbleBottomCenterTextIcon, InformationCircleIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const props = defineProps({
    event: Object,
    units: Array,
    is_approver: Boolean,
});

const isEdit = computed(() => !!props.event);
const page = usePage();

const formatDateForInput = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toISOString().slice(0, 16);
};

const defaultApprovalStatus = computed(() => {
    if (props.event?.approval_status) return props.event.approval_status;
    return props.is_approver ? 'published' : 'pending';
});

const form = useForm({
    unit_id: props.event?.unit_id || props.units?.[0]?.id || page.props.session?.active_unit_id || '',
    title: props.event?.title || '',
    content: props.event?.content || '',
    start_date: formatDateForInput(props.event?.start_date),
    end_date: formatDateForInput(props.event?.end_date),
    location: props.event?.location || '',
    status: props.event?.status || 'upcoming',
    approval_status: defaultApprovalStatus.value,
    banner: null,
    _method: isEdit.value ? 'PUT' : 'POST'
});

const bannerPreview = ref(props.event?.banner_path ? `/${props.event.banner_path}` : null);

const handleBannerChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.banner = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            bannerPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const submitWithApprovalStatus = (targetApprovalStatus) => {
    form.approval_status = targetApprovalStatus;
    if (isEdit.value) {
        form.post(route('public-relations.events.update', props.event.id));
    } else {
        form.post(route('public-relations.events.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Agenda Acara' : 'Tambah Agenda Acara Baru'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        {{ isEdit ? 'Edit Agenda Acara' : 'Tambah Agenda Acara Baru' }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Lengkapi rincian agenda dan lokasi acara unit sekolah Anda.</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-4xl mx-auto space-y-6">

            <!-- Rejection Note Alert Banner -->
            <div v-if="event && event.approval_status === 'rejected' && event.rejection_note" class="p-5 bg-rose-50 border border-rose-200 rounded-3xl shadow-sm space-y-2">
                <div class="flex items-center gap-2 text-rose-800 font-extrabold text-sm">
                    <ChatBubbleBottomCenterTextIcon class="w-5 h-5 text-rose-600" />
                    <span>Catatan Revisi dari Verifikator:</span>
                </div>
                <p class="text-sm text-rose-700 font-medium pl-7">{{ event.rejection_note }}</p>
                <p class="text-xs text-rose-500 pl-7">Silakan sesuaikan agenda sesuai saran di atas, lalu klik <strong>"Ajukan Verifikasi Kembali"</strong>.</p>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <form @submit.prevent="submitWithApprovalStatus(form.approval_status)" class="p-8 space-y-6">
                    
                    <!-- Unit Selection (if multiple) -->
                    <div v-if="units.length > 1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Sekolah</label>
                        <select v-model="form.unit_id" class="w-full rounded-xl border-gray-300 focus:border-namira-teal focus:ring focus:ring-namira-teal/20" required>
                            <option value="" disabled>Pilih Unit...</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                        </select>
                        <p v-if="form.errors.unit_id" class="text-sm text-red-600 mt-1">{{ form.errors.unit_id }}</p>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama / Judul Acara</label>
                        <input v-model="form.title" type="text" class="w-full rounded-xl border-gray-300 focus:border-namira-teal focus:ring focus:ring-namira-teal/20" required placeholder="Contoh: Open House & Pentas Seni 2026">
                        <p v-if="form.errors.title" class="text-sm text-red-600 mt-1">{{ form.errors.title }}</p>
                    </div>

                    <!-- Dates & Location -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Mulai</label>
                            <input v-model="form.start_date" type="datetime-local" class="w-full rounded-xl border-gray-300 focus:border-namira-teal focus:ring focus:ring-namira-teal/20" required>
                            <p v-if="form.errors.start_date" class="text-sm text-red-600 mt-1">{{ form.errors.start_date }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Selesai</label>
                            <input v-model="form.end_date" type="datetime-local" class="w-full rounded-xl border-gray-300 focus:border-namira-teal focus:ring focus:ring-namira-teal/20" required>
                            <p v-if="form.errors.end_date" class="text-sm text-red-600 mt-1">{{ form.errors.end_date }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                            <input v-model="form.location" type="text" class="w-full rounded-xl border-gray-300 focus:border-namira-teal focus:ring focus:ring-namira-teal/20" required placeholder="Contoh: Aula Utama Namira">
                            <p v-if="form.errors.location" class="text-sm text-red-600 mt-1">{{ form.errors.location }}</p>
                        </div>
                    </div>

                    <!-- Content (Rich Text Editor) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi / Detail Acara</label>
                        <div class="border border-gray-300 rounded-xl overflow-hidden focus-within:border-namira-teal focus-within:ring focus-within:ring-namira-teal/20 transition-all bg-white">
                            <QuillEditor v-model:content="form.content" contentType="html" theme="snow" toolbar="full" class="min-h-[250px] text-base" placeholder="Tuliskan rincian susunan acara, persyaratan, dll..." />
                        </div>
                        <p v-if="form.errors.content" class="text-sm text-red-600 mt-1">{{ form.errors.content }}</p>
                    </div>

                    <!-- Banner Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Banner / Pamphlet Acara</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl relative group" :class="{'border-namira-teal bg-teal-50/10': bannerPreview}">
                            <div v-if="bannerPreview" class="absolute inset-0 overflow-hidden rounded-xl">
                                <img :src="bannerPreview" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <div class="space-y-1 text-center relative z-10 p-4 bg-white/80 rounded-lg backdrop-blur-sm">
                                <PhotoIcon class="mx-auto h-12 w-12 text-gray-400" />
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label class="relative cursor-pointer bg-white rounded-md font-medium text-namira-teal hover:text-teal-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-namira-teal">
                                        <span>Upload banner</span>
                                        <input type="file" class="sr-only" @change="handleBannerChange" accept="image/*">
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG up to 2MB (Format WebP Otomatis)</p>
                            </div>
                        </div>
                        <p v-if="form.errors.banner" class="text-sm text-red-600 mt-1">{{ form.errors.banner }}</p>
                    </div>

                    <!-- Event Status (Upcoming / Ongoing / Completed / Cancelled) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status Pelaksanaan Acara</label>
                        <select v-model="form.status" class="w-full rounded-xl border-gray-300 focus:border-namira-teal focus:ring focus:ring-namira-teal/20" required>
                            <option value="upcoming">Akan Datang (Upcoming)</option>
                            <option value="ongoing">Sedang Berlangsung (Ongoing)</option>
                            <option value="completed">Selesai (Completed)</option>
                            <option value="cancelled">Dibatalkan (Cancelled)</option>
                        </select>
                        <p v-if="form.errors.status" class="text-sm text-red-600 mt-1">{{ form.errors.status }}</p>
                    </div>

                    <!-- Status Options for Approval -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status Verifikasi Publikasi</label>
                        
                        <div v-if="is_approver" class="space-y-2">
                            <select v-model="form.approval_status" class="w-full rounded-xl border-gray-300 focus:border-namira-teal focus:ring focus:ring-namira-teal/20" required>
                                <option value="published">Terbitkan Langsung (Published)</option>
                                <option value="pending">Menunggu Verifikasi (Pending)</option>
                                <option value="draft">Simpan Sebagai Draft</option>
                            </select>
                        </div>
                        <div v-else class="p-4 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-800 space-y-1">
                            <p class="font-bold flex items-center gap-1.5"><InformationCircleIcon class="w-4 h-4 text-amber-600" /> Alur Publikasi Acara Humas:</p>
                            <p>Acara yang disubmit akan masuk status <strong>"Menunggu Verifikasi"</strong> untuk ditinjau oleh Verifikator (Pengawas / Humas Yayasan) sebelum rilis di kalender publik.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 pt-6 border-t border-gray-100">
                        <Link :href="route('public-relations.events.index')" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition-colors">
                            Batal
                        </Link>

                        <button 
                            type="button" 
                            @click="submitWithApprovalStatus('draft')" 
                            :disabled="form.processing" 
                            class="px-5 py-2.5 bg-slate-700 text-white rounded-xl font-bold hover:bg-slate-800 transition-all disabled:opacity-50"
                        >
                            Simpan Draft
                        </button>

                        <button 
                            v-if="!is_approver" 
                            type="button" 
                            @click="submitWithApprovalStatus('pending')" 
                            :disabled="form.processing" 
                            class="px-5 py-2.5 bg-amber-500 text-white rounded-xl font-bold shadow-lg shadow-amber-500/30 hover:bg-amber-600 transition-all disabled:opacity-50"
                        >
                            {{ event?.approval_status === 'rejected' ? 'Ajukan Verifikasi Kembali' : 'Ajukan Verifikasi' }}
                        </button>

                        <button 
                            v-if="is_approver" 
                            type="button" 
                            @click="submitWithApprovalStatus('published')" 
                            :disabled="form.processing" 
                            class="px-5 py-2.5 bg-namira-teal text-white rounded-xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 transition-all disabled:opacity-50"
                        >
                            Terbitkan Langsung
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.ql-toolbar.ql-snow {
    border: none !important;
    border-bottom: 1px solid #e5e7eb !important;
    background-color: #f9fafb;
    border-radius: 0.75rem 0.75rem 0 0;
}
.ql-container.ql-snow {
    border: none !important;
    font-size: 1rem !important;
    font-family: inherit !important;
}
.ql-editor {
    min-height: 200px;
}
</style>
