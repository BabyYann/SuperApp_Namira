<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { PhotoIcon, ExclamationCircleIcon, ChatBubbleBottomCenterTextIcon } from '@heroicons/vue/24/outline';
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
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const submitWithStatus = (targetStatus) => {
    form.status = targetStatus;
    if (isEdit.value) {
        form.post(route('public-relations.news.update', props.news.id));
    } else {
        form.post(route('public-relations.news.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Berita' : 'Tambah Berita'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        {{ isEdit ? 'Edit Berita' : 'Tambah Berita' }}
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-4xl mx-auto space-y-6">

            <!-- Rejection Note Alert Banner -->
            <div v-if="news && news.status === 'rejected' && news.rejection_note" class="p-5 bg-rose-50 border border-rose-200 rounded-3xl shadow-sm space-y-2">
                <div class="flex items-center gap-2 text-rose-800 font-extrabold text-sm">
                    <ChatBubbleBottomCenterTextIcon class="w-5 h-5 text-rose-600" />
                    <span>Catatan Revisi dari Verifikator:</span>
                </div>
                <p class="text-sm text-rose-700 font-medium pl-7">{{ news.rejection_note }}</p>
                <p class="text-xs text-rose-500 pl-7">Silakan sesuaikan isi berita sesuai saran di atas, lalu klik <strong>"Ajukan Verifikasi Kembali"</strong>.</p>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <form @submit.prevent="submitWithStatus(form.status)" class="p-8 space-y-6">
                    
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">Judul Berita</label>
                        <input v-model="form.title" type="text" class="w-full rounded-xl border-gray-300 focus:border-namira-teal focus:ring focus:ring-namira-teal/20" required placeholder="Contoh: Kegiatan Porseni 2026">
                        <p v-if="form.errors.title" class="text-sm text-red-600 mt-1">{{ form.errors.title }}</p>
                    </div>

                    <!-- Content (Rich Text Editor) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Isi Berita</label>
                        <div class="border border-gray-300 rounded-xl overflow-hidden focus-within:border-namira-teal focus-within:ring focus-within:ring-namira-teal/20 transition-all bg-white">
                            <QuillEditor v-model:content="form.content" contentType="html" theme="snow" toolbar="full" class="min-h-[300px] text-base" placeholder="Tuliskan isi liputan berita secara lengkap di sini..." />
                        </div>
                        <p v-if="form.errors.content" class="text-sm text-red-600 mt-1">{{ form.errors.content }}</p>
                    </div>

                    <!-- Image Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto / Gambar Sampul</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl relative group" :class="{'border-namira-teal bg-teal-50/10': imagePreview}">
                            <div v-if="imagePreview" class="absolute inset-0 overflow-hidden rounded-xl">
                                <img :src="imagePreview" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <div class="space-y-1 text-center relative z-10 p-4 bg-white/80 rounded-lg backdrop-blur-sm">
                                <PhotoIcon class="mx-auto h-12 w-12 text-gray-400" />
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label class="relative cursor-pointer bg-white rounded-md font-medium text-namira-teal hover:text-teal-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-namira-teal">
                                        <span>Upload foto</span>
                                        <input type="file" class="sr-only" @change="handleImageChange" accept="image/*">
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG up to 2MB (Format WebP Otomatis)</p>
                            </div>
                        </div>
                        <p v-if="form.errors.image" class="text-sm text-red-600 mt-1">{{ form.errors.image }}</p>
                    </div>

                    <!-- Status Options based on Role -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status Publikasi</label>
                        
                        <div v-if="is_approver" class="space-y-2">
                            <select v-model="form.status" class="w-full rounded-xl border-gray-300 focus:border-namira-teal focus:ring focus:ring-namira-teal/20" required>
                                <option value="published">✅ Terbitkan Langsung (Published)</option>
                                <option value="pending">⏳ Menunggu Verifikasi (Pending)</option>
                                <option value="draft">📝 Simpan Sebagai Draft</option>
                            </select>
                        </div>
                        <div v-else class="p-4 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-800 space-y-1">
                            <p class="font-bold">ℹ️ Alur Publikasi Berita Humas:</p>
                            <p>Berita yang Anda simpan akan masuk ke status <strong>"Menunggu Verifikasi"</strong> terlebih dahulu untuk ditinjau oleh Verifikator (Pengawas/Kepala Sekolah/Yayasan) sebelum tayang di website publik.</p>
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
