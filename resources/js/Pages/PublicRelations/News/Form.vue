<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { PhotoIcon, ExclamationCircleIcon, ChatBubbleBottomCenterTextIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const props = defineProps({
    news: Object,
    units: Array,
    is_approver: Boolean,
});

const isEdit = computed(() => !!props.news);
const page = usePage();

const defaultStatus = computed(() => {
    if (props.news?.status) return props.news.status;
    return props.is_approver ? 'published' : 'pending';
});

const form = useForm({
    unit_id: props.news?.unit_id || props.units?.[0]?.id || page.props.session?.active_unit_id || '',
    title: props.news?.title || '',
    content: props.news?.content || '',
    status: defaultStatus.value,
    image: null,
    _method: isEdit.value ? 'PUT' : 'POST'
});

const imagePreview = ref(props.news?.image_path ? '/' + props.news.image_path : null);

const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const submitWithStatus = (targetStatus) => {
    form.status = targetStatus;
    submitForm();
};

const submitForm = () => {
    if (isEdit.value) {
        form.post(route('public-relations.news.update', props.news.id), {
            forceFormData: true,
        });
    } else {
        form.post(route('public-relations.news.store'), {
            forceFormData: true,
        });
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Berita' : 'Tambah Berita'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        {{ isEdit ? 'Edit Berita' : 'Tambah Berita Baru' }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Lengkapi informasi berita unit sekolah Anda.</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-4xl mx-auto">

            <!-- Rejection Note Alert Banner if status is rejected -->
            <div v-if="isEdit && news.status === 'rejected' && news.rejection_note" class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-start gap-3">
                <ExclamationCircleIcon class="w-6 h-6 text-rose-600 shrink-0 mt-0.5" />
                <div>
                    <h4 class="font-bold text-rose-800 text-sm">Catatan Revisi dari Verifikator</h4>
                    <p class="text-xs text-rose-700 mt-1">{{ news.rejection_note }}</p>
                    <p class="text-[11px] text-rose-500 mt-2 font-medium">Silakan perbaiki berita sesuai catatan di atas, lalu klik "Ajukan Verifikasi" untuk meninjau ulang.</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
                <form @submit.prevent="submitForm" class="space-y-6">
                    
                    <!-- Unit Selection -->
                    <div v-if="units.length > 1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Sekolah</label>
                        <select v-model="form.unit_id" class="w-full rounded-xl border-gray-300 focus:border-namira-teal focus:ring focus:ring-namira-teal/20" required>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                {{ unit.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.unit_id" class="text-red-500 text-xs mt-1">{{ form.errors.unit_id }}</div>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Judul Berita</label>
                        <input 
                            v-model="form.title" 
                            type="text" 
                            class="w-full rounded-xl border-gray-300 focus:border-namira-teal focus:ring focus:ring-namira-teal/20" 
                            placeholder="Masukkan judul berita..."
                            required
                        >
                        <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                    </div>

                    <!-- Image Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gambar Berita / Cover</label>
                        <div class="mt-1 flex items-center gap-4">
                            <div v-if="imagePreview" class="relative w-32 h-20 rounded-xl overflow-hidden border border-gray-200 shadow-sm">
                                <img :src="imagePreview" class="w-full h-full object-cover" />
                            </div>
                            <label class="cursor-pointer bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-700 px-4 py-2 rounded-xl text-sm font-medium transition-colors flex items-center gap-2">
                                <PhotoIcon class="w-5 h-5 text-gray-500" />
                                <span>Pilih Gambar</span>
                                <input type="file" @change="handleImageChange" accept="image/*" class="hidden" />
                            </label>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Format: JPG, PNG, WEBP. Maks 2MB. Otomatis dikompresi.</p>
                        <div v-if="form.errors.image" class="text-red-500 text-xs mt-1">{{ form.errors.image }}</div>
                    </div>

                    <!-- Content Editor -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Isi Berita</label>
                        <div class="bg-white rounded-xl border border-gray-300 overflow-hidden">
                            <QuillEditor 
                                v-model:content="form.content" 
                                contentType="html"
                                toolbar="essential" 
                                theme="snow"
                                class="min-h-[250px]"
                            />
                        </div>
                        <div v-if="form.errors.content" class="text-red-500 text-xs mt-1">{{ form.errors.content }}</div>
                    </div>

                    <!-- Status Options based on Role -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status Publikasi</label>
                        
                        <div v-if="is_approver" class="space-y-2">
                            <select v-model="form.status" class="w-full rounded-xl border-gray-300 focus:border-namira-teal focus:ring focus:ring-namira-teal/20" required>
                                <option value="published">Terbitkan Langsung (Published)</option>
                                <option value="pending">Menunggu Verifikasi (Pending)</option>
                                <option value="draft">Simpan Sebagai Draft</option>
                            </select>
                        </div>
                        <div v-else class="p-4 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-800 space-y-1">
                            <p class="font-bold flex items-center gap-1.5"><InformationCircleIcon class="w-4 h-4 text-amber-600" /> Alur Publikasi Berita Humas:</p>
                            <p>Berita yang Anda simpan akan masuk ke status <strong>"Menunggu Verifikasi"</strong> terlebih dahulu untuk ditinjau oleh Verifikator (Pengawas / Humas Yayasan) sebelum tayang di website publik.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 pt-6 border-t border-gray-100">
                        <Link :href="route('public-relations.news.index')" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition-colors">
                            Batal
                        </Link>

                        <button 
                            type="button" 
                            @click="submitWithStatus('draft')" 
                            :disabled="form.processing" 
                            class="px-5 py-2.5 bg-slate-700 text-white rounded-xl font-bold hover:bg-slate-800 transition-all disabled:opacity-50"
                        >
                            Simpan Draft
                        </button>

                        <button 
                            v-if="!is_approver" 
                            type="button" 
                            @click="submitWithStatus('pending')" 
                            :disabled="form.processing" 
                            class="px-5 py-2.5 bg-amber-500 text-white rounded-xl font-bold shadow-lg shadow-amber-500/30 hover:bg-amber-600 transition-all disabled:opacity-50"
                        >
                            {{ news?.status === 'rejected' ? 'Ajukan Verifikasi Kembali' : 'Ajukan Verifikasi' }}
                        </button>

                        <button 
                            v-if="is_approver" 
                            type="button" 
                            @click="submitWithStatus('published')" 
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
    min-height: 300px;
}
</style>
