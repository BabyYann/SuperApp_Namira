<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref } from 'vue';

const props = defineProps({
    student: Object,
    classrooms: Array,
});

const form = useForm({
    _method: 'PUT',
    full_name: props.student.full_name,
    nis: props.student.nis,
    nisn: props.student.nisn,
    gender: props.student.gender,
    classroom_id: props.student.classroom_id,
    photo: null,
});

const photoPreview = ref(props.student.photo ? `/storage/${props.student.photo}` : null);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('yayasan.students.update', props.student.id), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Edit Siswa" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                Edit Siswa
            </h2>
        </template>

        <div class="max-w-4xl mx-auto bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
            <form @submit.prevent="submit" class="space-y-6">
                
                <!-- Photo Upload -->
                 <div class="flex flex-col items-center mb-6">
                    <div class="w-24 h-24 rounded-full bg-gray-100 mb-3 overflow-hidden border-2 border-dashed border-gray-300 flex items-center justify-center">
                        <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover" />
                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                        </svg>
                    </div>
                    <label for="photo" class="cursor-pointer text-sm text-namira-teal font-medium hover:underline">
                        Ubah Foto
                        <input type="file" id="photo" class="hidden" @change="handleFileChange" accept="image/*" />
                    </label>
                    <InputError :message="form.errors.photo" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Full Name -->
                    <div class="md:col-span-2">
                        <InputLabel for="full_name" value="Nama Lengkap" />
                        <TextInput id="full_name" v-model="form.full_name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.full_name" class="mt-2" />
                    </div>

                    <!-- NIS -->
                    <div>
                        <InputLabel for="nis" value="NIS" />
                        <TextInput id="nis" v-model="form.nis" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.nis" class="mt-2" />
                    </div>

                    <!-- NISN -->
                    <div>
                        <InputLabel for="nisn" value="NISN" />
                        <TextInput id="nisn" v-model="form.nisn" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.nisn" class="mt-2" />
                    </div>

                    <!-- Classroom -->
                    <div>
                        <InputLabel for="classroom_id" value="Kelas" />
                        <select id="classroom_id" v-model="form.classroom_id" class="mt-1 block w-full h-12 px-4 border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl shadow-sm text-sm">
                            <option value="">-- Pilih Kelas --</option>
                            <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">
                                {{ classroom.name }}
                            </option>
                        </select>
                         <InputError :message="form.errors.classroom_id" class="mt-2" />
                    </div>

                    <!-- Gender -->
                    <div>
                        <InputLabel value="Jenis Kelamin" />
                        <div class="flex gap-4 mt-2">
                             <label class="flex items-center gap-2">
                                <input type="radio" v-model="form.gender" value="L" class="text-namira-teal focus:ring-namira-teal" />
                                <span>Laki-Laki</span>
                             </label>
                             <label class="flex items-center gap-2">
                                <input type="radio" v-model="form.gender" value="P" class="text-namira-teal focus:ring-namira-teal" />
                                <span>Perempuan</span>
                             </label>
                        </div>
                        <InputError :message="form.errors.gender" class="mt-2" />
                    </div>
                </div>

                 <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <Link :href="route('yayasan.students.index')" class="px-4 py-2 text-gray-600 hover:text-gray-900 transition-colors">
                        Batal
                    </Link>
                    <PrimaryButton :disabled="form.processing">
                        Simpan Perubahan
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
