<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { PhotoIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    banner: Object,
});

const isEdit = computed(() => !!props.banner);

const form = useForm({
    title: props.banner?.title || '',
    image: null,
    order_weight: props.banner?.order_weight !== undefined ? props.banner.order_weight : 0,
    is_active: props.banner?.is_active !== undefined ? !!props.banner.is_active : true,
    _method: isEdit.value ? 'PUT' : 'POST',
});

const imagePreviewUrl = ref(props.banner?.image_path ? '/' + props.banner.image_path : null);

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        // Validate client-side size (3MB = 3 * 1024 * 1024 bytes)
        if (file.size > 3 * 1024 * 1024) {
            alert('Ukuran file foto melebihi batas maksimal 3MB.');
            return;
        }
        form.image = file;
        imagePreviewUrl.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    if (isEdit.value) {
        form.post(route('public-relations.banners.update', props.banner.id));
    } else {
        form.post(route('public-relations.banners.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Banner Slider' : 'Tambah Banner Slider'" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight flex items-center gap-2">
                    <PhotoIcon class="w-6 h-6 text-namira-teal" />
                    {{ isEdit ? 'Edit Banner Slider' : 'Tambah Banner Slider' }}
                </h2>
                <p class="text-sm text-gray-500">Kelola foto banner utama yang tampil sebagai slider di halaman depan website.</p>
            </div>
        </template>

        <div class="py-6 max-w-3xl mx-auto">
            <form @submit.prevent="submit" class="space-y-6">

                <!-- Card Form -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-8 py-5 border-b border-gray-50 bg-gradient-to-r from-teal-50/50 to-white flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-namira-teal/10 flex items-center justify-center">
                            <PhotoIcon class="w-4 h-4 text-namira-teal" />
                        </div>
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Form Banner</h3>
                    </div>
                    <div class="p-8 space-y-6">

                        <!-- Photo Banner Preview & Selector -->
                        <div class="flex flex-col gap-3 pb-4 border-b border-gray-50">
                            <label class="block text-sm font-semibold text-gray-700">Foto Banner <span v-if="!isEdit" class="text-red-500">*</span></label>
                            
                            <div class="relative w-full aspect-[21/9] md:aspect-[16/6] rounded-2xl overflow-hidden border-2 border-dashed border-gray-300 hover:border-namira-teal transition-colors flex items-center justify-center bg-gray-50 group shadow-inner">
                                <img v-if="imagePreviewUrl" :src="imagePreviewUrl" class="w-full h-full object-cover" />
                                <div v-else class="flex flex-col items-center justify-center text-gray-400 p-4">
                                    <PhotoIcon class="w-12 h-12 mb-2 stroke-1" />
                                    <span class="text-sm font-semibold text-gray-700">Pilih File Foto Banner</span>
                                    <span class="text-xs text-gray-500 mt-1 text-center">Rekomendasi aspek rasio landscape / hero style (16:9 / 21:9)</span>
                                </div>
                                
                                <!-- Hover Overlay -->
                                <label for="image-upload" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center text-white cursor-pointer transition-opacity">
                                    <PhotoIcon class="w-8 h-8 mb-1" />
                                    <span class="text-sm font-semibold">Unggah Foto Baru</span>
                                </label>
                            </div>
                            
                            <input id="image-upload" type="file" class="hidden" accept="image/*" @change="onFileChange" />
                            <div class="flex justify-between items-center text-[11px] text-gray-500">
                                <span>Format file: JPG, JPEG, PNG. Maksimal ukuran file 3MB.</span>
                            </div>
                            <p v-if="form.errors.image" class="text-xs text-red-600 font-medium">{{ form.errors.image }}</p>
                        </div>

                        <!-- Judul Banner (Optional) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Judul / Caption Banner <span class="text-xs font-normal text-gray-400">(Opsional)</span></label>
                            <input v-model="form.title" type="text" placeholder="Masukkan judul banner atau caption singkat..."
                                class="w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm" />
                            <p v-if="form.errors.title" class="text-xs text-red-600 mt-1">{{ form.errors.title }}</p>
                        </div>

                        <!-- Bobot Urutan -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Bobot Urutan <span class="text-red-500">*</span></label>
                            <input v-model.number="form.order_weight" type="number" min="0" required
                                class="w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm font-mono" />
                            <p class="text-[11px] text-gray-500 mt-1">Angka lebih kecil akan diurutkan terlebih dahulu di slider.</p>
                            <p v-if="form.errors.order_weight" class="text-xs text-red-600 mt-1">{{ form.errors.order_weight }}</p>
                        </div>

                        <!-- Status Aktif -->
                        <div class="flex flex-col gap-2 pt-2">
                            <label class="block text-sm font-semibold text-gray-700">Status Aktif</label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" v-model="form.is_active" class="sr-only" />
                                    <div :class="form.is_active ? 'bg-namira-teal' : 'bg-gray-200'" class="w-11 h-6 rounded-full transition-colors duration-200"></div>
                                    <div :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-700">{{ form.is_active ? 'Aktif (ditampilkan di slider depan)' : 'Non-Aktif (disembunyikan)' }}</span>
                            </label>
                            <p v-if="form.errors.is_active" class="text-xs text-red-600 mt-1 font-medium">{{ form.errors.is_active }}</p>
                        </div>

                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3">
                    <Link :href="route('public-relations.banners.index')"
                        class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition-colors text-sm flex items-center gap-1.5">
                        <ArrowLeftIcon class="w-4 h-4" />
                        Batal
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="px-6 py-2.5 bg-namira-teal text-white rounded-xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all disabled:opacity-50 flex items-center gap-2 text-sm">
                        <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ form.processing ? 'Menyimpan...' : (isEdit ? 'Perbarui Banner' : 'Simpan Banner') }}
                    </button>
                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>
