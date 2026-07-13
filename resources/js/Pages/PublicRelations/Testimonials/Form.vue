<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { ChatBubbleLeftRightIcon, UserIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    testimonial: Object,
    units: Array,
});

const isEdit = computed(() => !!props.testimonial);
const page = usePage();
const currentUser = page.props.auth.user;

const form = useForm({
    unit_id: props.testimonial?.unit_id || currentUser.unit_id || '',
    name: props.testimonial?.name || '',
    role_or_title: props.testimonial?.role_or_title || '',
    quote: props.testimonial?.quote || '',
    photo: null,
    is_active: props.testimonial?.is_active !== undefined ? !!props.testimonial.is_active : true,
    _method: isEdit.value ? 'PUT' : 'POST',
});

const photoPreviewUrl = ref(props.testimonial?.photo_path ? '/' + props.testimonial.photo_path : null);

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        photoPreviewUrl.value = URL.createObjectURL(file);
    }
};

const charCount = computed(() => form.quote.length);

const submit = () => {
    if (isEdit.value) {
        form.post(route('public-relations.testimonials.update', props.testimonial.id));
    } else {
        form.post(route('public-relations.testimonials.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Testimoni' : 'Tambah Testimoni'" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight flex items-center gap-2">
                    <ChatBubbleLeftRightIcon class="w-6 h-6 text-namira-teal" />
                    {{ isEdit ? 'Edit Testimoni' : 'Tambah Testimoni' }}
                </h2>
                <p class="text-sm text-gray-500">Kelola testimoni wali murid, alumni, tokoh masyarakat, dan pihak lainnya.</p>
            </div>
        </template>

        <div class="py-6 max-w-3xl mx-auto">
            <form @submit.prevent="submit" class="space-y-6">

                <!-- Card Form -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-8 py-5 border-b border-gray-50 bg-gradient-to-r from-teal-50/50 to-white flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-namira-teal/10 flex items-center justify-center">
                            <UserIcon class="w-4 h-4 text-namira-teal" />
                        </div>
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Form Testimoni</h3>
                    </div>
                    <div class="p-8 space-y-6">

                        <!-- Photo Profile Preview & Selector -->
                        <div class="flex flex-col items-center gap-3 pb-4 border-b border-gray-50">
                            <label class="block text-sm font-semibold text-gray-700 text-center">Foto Profil</label>
                            <div class="relative w-28 h-28 rounded-full overflow-hidden border-2 border-dashed border-gray-300 hover:border-namira-teal transition-colors flex items-center justify-center bg-gray-50 group shadow-inner">
                                <img v-if="photoPreviewUrl" :src="photoPreviewUrl" class="w-full h-full object-cover" />
                                <div v-else class="flex flex-col items-center justify-center text-gray-400">
                                    <UserIcon class="w-8 h-8" />
                                    <span class="text-[10px] mt-1 font-semibold">Pilih Foto</span>
                                </div>
                                <!-- Hover Overlay -->
                                <label for="photo-upload" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-semibold cursor-pointer transition-opacity">
                                    Ubah Foto
                                </label>
                            </div>
                            <input id="photo-upload" type="file" class="hidden" accept="image/*" @change="onFileChange" />
                            <p class="text-[11px] text-gray-500">Maksimal file size 2MB (JPG, PNG, JPEG)</p>
                            <p v-if="form.errors.photo" class="text-xs text-red-600 -mt-1 font-medium">{{ form.errors.photo }}</p>
                        </div>

                        <!-- Unit Sekolah (Only if admin / multiple units exist) -->
                        <div v-if="units && units.length > 1">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Unit Sekolah <span class="text-red-500">*</span></label>
                            <select v-model="form.unit_id" class="w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm" required>
                                <option value="" disabled>Pilih Unit...</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                            </select>
                            <p v-if="form.errors.unit_id" class="text-xs text-red-600 mt-1">{{ form.errors.unit_id }}</p>
                        </div>
                        <input v-else type="hidden" v-model="form.unit_id" />

                        <!-- Nama Pemberi Testimoni -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Pemberi Testimoni <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" placeholder="Contoh: Ibu Ratna, Bapak Budi..." required
                                class="w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm" />
                            <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                        </div>

                        <!-- Peran / Jabatan -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Peran / Jabatan <span class="text-red-500">*</span></label>
                            <input v-model="form.role_or_title" type="text" placeholder="Contoh: Wali Murid SMP, Alumni SD, Tokoh Masyarakat..." required
                                class="w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm" />
                            <p v-if="form.errors.role_or_title" class="text-xs text-red-600 mt-1">{{ form.errors.role_or_title }}</p>
                        </div>

                        <!-- Kutipan / Quote -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kutipan / Quote <span class="text-red-500">*</span></label>
                            <textarea v-model="form.quote" rows="4" maxlength="1000" required
                                placeholder="Tuliskan isi kutipan testimoni di sini..."
                                class="w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm resize-none"></textarea>
                            <div class="flex justify-between items-center mt-1">
                                <p v-if="form.errors.quote" class="text-xs text-red-600">{{ form.errors.quote }}</p>
                                <div v-else></div>
                                <p class="text-xs text-gray-400 font-mono">{{ charCount }}/1000</p>
                            </div>
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
                                <span class="text-sm font-medium text-gray-700">{{ form.is_active ? 'Aktif (ditampilkan di halaman depan)' : 'Non-Aktif (disembunyikan)' }}</span>
                            </label>
                        </div>

                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3">
                    <Link :href="route('public-relations.testimonials.index')"
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
                        {{ form.processing ? 'Menyimpan...' : (isEdit ? 'Perbarui Testimoni' : 'Simpan Testimoni') }}
                    </button>
                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>
