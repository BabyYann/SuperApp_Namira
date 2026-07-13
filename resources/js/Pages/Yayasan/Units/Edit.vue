<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    PencilSquareIcon, ClockIcon, ChevronDownIcon, BuildingLibraryIcon, 
    EnvelopeIcon, PhoneIcon, MapPinIcon, PhotoIcon, XMarkIcon 
} from '@heroicons/vue/24/outline';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    unit: Object,
    principals: Array,
});

const form = useForm({
    _method: 'PUT',
    name: props.unit.name,
    code: props.unit.code,
    category: props.unit.category,
    level: props.unit.level,
    email: props.unit.email || '',
    phone: props.unit.phone || '',
    address: props.unit.address || '',
    description: props.unit.description || '',
    logo: null,
    work_start_time: props.unit.work_start_time?.slice(0, 5) || '07:00',
    work_end_time: props.unit.work_end_time?.slice(0, 5) || '16:00',
    late_tolerance_minutes: props.unit.late_tolerance_minutes || 15,
    principal_id: props.unit.principal_id || null,
});

// Logo preview
const logoPreview = ref(props.unit.logo ? `/storage/${props.unit.logo}` : null);
const logoInput = ref(null);

const handleLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const removeLogo = () => {
    form.logo = null;
    logoPreview.value = null;
    if (logoInput.value) {
        logoInput.value.value = '';
    }
};

const submit = () => {
    form.post(route('yayasan.units.update', props.unit.id));
};
</script>

<template>
    <Head title="Edit Unit Pendidikan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                        Edit Unit Pendidikan
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Perbarui data informasi sekolah.</p>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-6 max-w-4xl mx-auto py-6">
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden relative">
                 <!-- Decorative Shape -->
                 <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-amber-100/50 to-orange-50 rounded-bl-full -mr-10 -mt-10 pointer-events-none"></div>

                <form @submit.prevent="submit" class="relative z-10" enctype="multipart/form-data">
                    
                    <!-- Section: Logo & Profile -->
                    <div class="p-8 border-b border-gray-100">
                        <div class="flex items-start gap-4 mb-8">
                            <div class="p-3 bg-purple-50 text-purple-600 rounded-2xl shadow-sm">
                                <PhotoIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Logo & Profil</h3>
                                <p class="text-sm text-gray-500">Atur logo dan deskripsi unit.</p>
                            </div>
                        </div>

                        <div class="flex flex-col md:flex-row gap-6">
                            <!-- Logo Upload -->
                            <div class="flex flex-col items-center gap-4">
                                <div class="w-32 h-32 rounded-2xl bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden relative group">
                                    <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-cover" />
                                    <BuildingLibraryIcon v-else class="w-12 h-12 text-gray-300" />
                                    
                                    <!-- Remove button -->
                                    <button 
                                        v-if="logoPreview" 
                                        type="button"
                                        @click="removeLogo" 
                                        class="absolute top-1 right-1 p-1 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        <XMarkIcon class="w-4 h-4" />
                                    </button>
                                </div>
                                <label class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-xl cursor-pointer transition-colors">
                                    <input ref="logoInput" type="file" accept="image/*" @change="handleLogoChange" class="hidden" />
                                    Upload Logo
                                </label>
                                <InputError :message="form.errors.logo" />
                            </div>

                            <!-- Description -->
                            <div class="flex-1">
                                <InputLabel for="description" value="Deskripsi Unit" class="mb-2" />
                                <textarea 
                                    id="description" 
                                    v-model="form.description" 
                                    rows="5"
                                    class="block w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring-namira-teal transition-colors bg-white/50 resize-none"
                                    placeholder="Tuliskan deskripsi singkat tentang unit ini..."
                                ></textarea>
                                <InputError :message="form.errors.description" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <!-- Section: Unit Info -->
                    <div class="p-8 border-b border-gray-100">
                        <div class="flex items-start gap-4 mb-8">
                            <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl shadow-sm">
                                <PencilSquareIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Identitas Unit</h3>
                                <p class="text-sm text-gray-500">Perubahan kode unit mungkin mempengaruhi data terkait.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-1 md:col-span-2">
                                <InputLabel for="name" value="Nama Sekolah / Unit" class="mb-2" />
                                <TextInput id="name" v-model="form.name" type="text" class="block w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring-namira-teal transition-colors bg-white/50" required />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="code" value="Kode Unit" class="mb-2" />
                                <TextInput id="code" v-model="form.code" type="text" class="block w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring-namira-teal transition-colors bg-white/50" required />
                                <InputError :message="form.errors.code" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="category" value="Jenjang Pendidikan" class="mb-2" />
                                <div class="relative">
                                    <select id="category" v-model="form.category" class="appearance-none block w-full pl-4 pr-10 py-2.5 text-base border-gray-200 focus:outline-none focus:ring-namira-teal focus:border-namira-teal sm:text-sm rounded-xl bg-white/50 transition-colors" required>
                                        <option value="TK">TK / PAUD</option>
                                        <option value="SD">SD</option>
                                        <option value="SMP">SMP</option>
                                        <option value="SMA">SMA</option>
                                        <option value="SMK">SMK</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-gray-500">
                                        <ChevronDownIcon class="h-4 w-4" />
                                    </div>
                                </div>
                                <InputError :message="form.errors.category" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="level" value="Tipe / Level" class="mb-2" />
                                <div class="relative">
                                    <select id="level" v-model="form.level" class="appearance-none block w-full pl-4 pr-10 py-2.5 text-base border-gray-200 focus:outline-none focus:ring-namira-teal focus:border-namira-teal sm:text-sm rounded-xl bg-white/50 transition-colors" required>
                                        <option value="Nasional">Nasional</option>
                                        <option value="Plus">Plus</option>
                                        <option value="Internasional">Internasional</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-gray-500">
                                        <ChevronDownIcon class="h-4 w-4" />
                                    </div>
                                </div>
                                <InputError :message="form.errors.level" class="mt-2" />
                            </div>

                            <div class="col-span-1 md:col-span-2">
                                <InputLabel for="principal_id" value="Kepala Sekolah" class="mb-2" />
                                <div class="relative">
                                    <select id="principal_id" v-model="form.principal_id" class="appearance-none block w-full pl-4 pr-10 py-2.5 text-base border-gray-200 focus:outline-none focus:ring-namira-teal focus:border-namira-teal sm:text-sm rounded-xl bg-white/50 transition-colors">
                                        <option :value="null">Pilih Kepala Sekolah...</option>
                                        <option v-for="principal in principals" :key="principal.id" :value="principal.id">
                                            {{ principal.name }} ({{ principal.email }})
                                        </option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-gray-500">
                                        <ChevronDownIcon class="h-4 w-4" />
                                    </div>
                                </div>
                                <InputError :message="form.errors.principal_id" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <!-- Section: Contact Info -->
                    <div class="p-8 border-b border-gray-100 bg-blue-50/30">
                        <div class="flex items-start gap-4 mb-8">
                            <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl shadow-sm">
                                <EnvelopeIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Kontak & Alamat</h3>
                                <p class="text-sm text-gray-500">Informasi kontak resmi unit.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="email" value="Email" class="mb-2" />
                                <TextInput id="email" v-model="form.email" type="email" class="block w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring-namira-teal transition-colors bg-white/50" placeholder="unit@namira.sch.id" />
                                <InputError :message="form.errors.email" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="phone" value="Telepon" class="mb-2" />
                                <TextInput id="phone" v-model="form.phone" type="text" class="block w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring-namira-teal transition-colors bg-white/50" placeholder="0274-123456" />
                                <InputError :message="form.errors.phone" class="mt-2" />
                            </div>
                            <div class="col-span-1 md:col-span-2">
                                <InputLabel for="address" value="Alamat Lengkap" class="mb-2" />
                                <textarea 
                                    id="address" 
                                    v-model="form.address" 
                                    rows="2"
                                    class="block w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring-namira-teal transition-colors bg-white/50 resize-none"
                                    placeholder="Jl. Contoh No. 123, Kota, Provinsi"
                                ></textarea>
                                <InputError :message="form.errors.address" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <!-- Section: Work Hours -->
                    <div class="p-8 border-b border-gray-100 bg-gray-50/30">
                        <div class="flex items-start gap-4 mb-8">
                            <div class="p-3 bg-teal-50 text-teal-600 rounded-2xl shadow-sm">
                                <ClockIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Jam Kerja & Absensi</h3>
                                <p class="text-sm text-gray-500">Aturan jam masuk dan pulang untuk karyawan di unit ini.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <InputLabel for="work_start_time" value="Jam Masuk" class="mb-2" />
                                <TextInput id="work_start_time" v-model="form.work_start_time" type="time" class="block w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring-namira-teal transition-colors bg-white/50" required />
                                <InputError :message="form.errors.work_start_time" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="work_end_time" value="Jam Pulang" class="mb-2" />
                                <TextInput id="work_end_time" v-model="form.work_end_time" type="time" class="block w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring-namira-teal transition-colors bg-white/50" required />
                                <InputError :message="form.errors.work_end_time" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="late_tolerance_minutes" value="Toleransi (Menit)" class="mb-2" />
                                <TextInput id="late_tolerance_minutes" v-model="form.late_tolerance_minutes" type="number" min="0" class="block w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring-namira-teal transition-colors bg-white/50" required />
                                <p class="text-xs text-gray-400 mt-1.5 font-medium">*Batas waktu sebelum dianggap terlambat.</p>
                                <InputError :message="form.errors.late_tolerance_minutes" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-end gap-3 px-8 py-6 bg-gray-50/50 border-t border-gray-100">
                        <Link :href="route('yayasan.units.index')" class="px-5 py-2.5 text-sm font-bold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors shadow-sm">
                            Batal
                        </Link>
                        <PrimaryButton :disabled="form.processing" class="px-8 py-2.5 bg-namira-teal hover:bg-teal-700 text-white font-bold rounded-xl shadow-lg shadow-namira-teal/30 transition-all focus:ring-2 focus:ring-offset-2 focus:ring-namira-teal border-0 active:scale-95">
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Simpan Perubahan</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

