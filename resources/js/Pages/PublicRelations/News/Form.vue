<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { PhotoIcon } from '@heroicons/vue/24/outline';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

// Global CSS fix for Quill borders to match Tailwind
import { onMounted } from 'vue';

const props = defineProps({
    news: Object,
    units: Array,
});

const isEdit = computed(() => !!props.news);
const page = usePage();
const currentUser = page.props.auth.user;

const form = useForm({
    unit_id: props.news?.unit_id || currentUser.unit_id || '',
    title: props.news?.title || '',
    content: props.news?.content || '',
    status: props.news?.status || 'published',
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

const submit = () => {
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

        <div class="py-6 max-w-4xl mx-auto">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <form @submit.prevent="submit" class="p-8 space-y-6">
                    
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
                                <p class="text-xs text-gray-500">PNG, JPG up to 2MB</p>
                            </div>
                        </div>
                        <p v-if="form.errors.image" class="text-sm text-red-600 mt-1">{{ form.errors.image }}</p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status Publikasi</label>
                        <select v-model="form.status" class="w-full rounded-xl border-gray-300 focus:border-namira-teal focus:ring focus:ring-namira-teal/20" required>
                            <option value="published">Langsung Tayang (Published)</option>
                            <option value="draft">Simpan Sebagai Draft</option>
                        </select>
                        <p v-if="form.errors.status" class="text-sm text-red-600 mt-1">{{ form.errors.status }}</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-100">
                        <Link :href="route('public-relations.news.index')" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition-colors">
                            Batal
                        </Link>
                        <button type="submit" :disabled="form.processing" class="px-5 py-2.5 bg-namira-teal text-white rounded-xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all disabled:opacity-50">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Berita' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* Override Quill default borders to match Tailwind */
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
