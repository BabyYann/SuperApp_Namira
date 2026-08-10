<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    UserIcon, PhotoIcon, HeartIcon, PhoneIcon, MapPinIcon, 
    SparklesIcon, ArrowLeftIcon, CheckCircleIcon 
} from '@heroicons/vue/24/outline';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const form = useForm({
    full_name: '',
    nickname: '',
    gender: 'L',
    dob: '',
    parent_name: '',
    parent_phone: '',
    address: '',
    photo: null,
    blood_type: '',
    allergies: '',
    special_conditions: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    routine_notes: '',
});

const photoPreview = ref(null);

const handlePhotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('daycare.children.store'));
};
</script>

<template>
    <Head title="Pendaftaran Ananda Daycare Baru" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('daycare.children.index')" class="p-2 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200">
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h2 class="font-extrabold text-2xl text-slate-800 leading-tight">Pendaftaran Ananda Daycare Baru</h2>
                    <p class="text-xs text-slate-500">Input informasi anak, foto, kontak ortu, dan catatan medis.</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <form @submit.prevent="submit" class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden p-8 space-y-8">
                
                <!-- Section 1: Profil Utama Anak -->
                <div>
                    <h3 class="text-base font-extrabold text-slate-800 mb-4 flex items-center gap-2">
                        <UserIcon class="w-5 h-5 text-amber-500" />
                        <span>Identitas Ananda</span>
                    </h3>

                    <div class="flex flex-col sm:flex-row gap-6 items-start">
                        <!-- Photo Upload Box -->
                        <div class="flex flex-col items-center gap-2 shrink-0">
                            <div class="w-32 h-32 rounded-2xl bg-slate-100 border-2 border-dashed border-slate-300 overflow-hidden relative flex items-center justify-center group">
                                <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover" />
                                <PhotoIcon v-else class="w-10 h-10 text-slate-300" />
                                <input type="file" @change="handlePhotoChange" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" />
                            </div>
                            <span class="text-[11px] font-bold text-slate-400">*Upload Foto Ananda</span>
                            <InputError :message="form.errors.photo" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 flex-1 w-full">
                            <div class="col-span-1 sm:col-span-2">
                                <InputLabel value="Nama Lengkap Anak" class="mb-1" />
                                <TextInput v-model="form.full_name" type="text" placeholder="Contoh: Ananda Raka Pratama" required class="w-full" />
                                <InputError :message="form.errors.full_name" />
                            </div>

                            <div>
                                <InputLabel value="Nama Panggilan" class="mb-1" />
                                <TextInput v-model="form.nickname" type="text" placeholder="Contoh: Raka" class="w-full" />
                            </div>

                            <div>
                                <InputLabel value="Jenis Kelamin" class="mb-1" />
                                <div class="flex gap-4 pt-2">
                                    <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-700">
                                        <input type="radio" v-model="form.gender" value="L" class="text-amber-600 focus:ring-amber-500" />
                                        <span>Laki-Laki</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-700">
                                        <input type="radio" v-model="form.gender" value="P" class="text-amber-600 focus:ring-amber-500" />
                                        <span>Perempuan</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <InputLabel value="Tanggal Lahir" class="mb-1" />
                                <TextInput v-model="form.dob" type="date" required class="w-full" />
                                <InputError :message="form.errors.dob" />
                            </div>

                            <div>
                                <InputLabel value="Golongan Darah" class="mb-1" />
                                <select v-model="form.blood_type" class="w-full rounded-xl border-slate-200 text-sm focus:border-amber-500 focus:ring-amber-500">
                                    <option value="">Tidak Diketahui</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="AB">AB</option>
                                    <option value="O">O</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="border-slate-100" />

                <!-- Section 2: Data Orang Tua / Wali Utama -->
                <div>
                    <h3 class="text-base font-extrabold text-slate-800 mb-4 flex items-center gap-2">
                        <PhoneIcon class="w-5 h-5 text-amber-500" />
                        <span>Data Orang Tua / Wali Utama</span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Nama Orang Tua / Wali Utama" class="mb-1" />
                            <TextInput v-model="form.parent_name" type="text" placeholder="Contoh: Bpk. Hendra Pratama" required class="w-full" />
                            <InputError :message="form.errors.parent_name" />
                        </div>

                        <div>
                            <InputLabel value="No. WhatsApp / Telepon Ortu" class="mb-1" />
                            <TextInput v-model="form.parent_phone" type="text" placeholder="Contoh: 081234567890" required class="w-full" />
                            <InputError :message="form.errors.parent_phone" />
                        </div>

                        <div class="col-span-1 sm:col-span-2">
                            <InputLabel value="Alamat Rumah" class="mb-1" />
                            <textarea v-model="form.address" rows="2" placeholder="Alamat domisili orang tua..." class="w-full rounded-xl border-slate-200 text-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
                        </div>
                    </div>
                </div>

                <hr class="border-slate-100" />

                <!-- Section 3: Catatan Pengasuhan & Medis -->
                <div>
                    <h3 class="text-base font-extrabold text-slate-800 mb-4 flex items-center gap-2">
                        <HeartIcon class="w-5 h-5 text-rose-500" />
                        <span>Catatan Kesehatan & Rutinitas Khusus</span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Alergi Makanan / Obat" class="mb-1" />
                            <textarea v-model="form.allergies" rows="2" placeholder="Misal: Alergi seafood, kacang tanah, atau obat paracetamol..." class="w-full rounded-xl border-slate-200 text-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
                        </div>

                        <div>
                            <InputLabel value="Kondisi Khusus / Riwayat Medis" class="mb-1" />
                            <textarea v-model="form.special_conditions" rows="2" placeholder="Misal: Asma ringan, gampang kejang saat demam tinggi..." class="w-full rounded-xl border-slate-200 text-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
                        </div>

                        <div class="col-span-1 sm:col-span-2">
                            <InputLabel value="Catatan Kebiasaan Rutinitas Anak" class="mb-1" />
                            <textarea v-model="form.routine_notes" rows="2" placeholder="Misal: Harus didengarkan musik pengantar tidur, minum susu pakai empeng..." class="w-full rounded-xl border-slate-200 text-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end gap-4 pt-4">
                    <Link :href="route('daycare.children.index')" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs">
                        Batal
                    </Link>
                    <PrimaryButton :disabled="form.processing" class="bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 border-none shadow-md">
                        Simpan Data Ananda
                    </PrimaryButton>
                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>
