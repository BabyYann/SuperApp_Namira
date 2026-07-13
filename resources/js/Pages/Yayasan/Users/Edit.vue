<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { UserIcon, LockClosedIcon, ChevronUpDownIcon, KeyIcon } from '@heroicons/vue/24/outline';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    user: Object,
    currentUnitId: Number,
    currentRole: String,
    units: Array,
    roles: Array,
});

const form = useForm({
    _method: 'PUT',
    name: props.user.name,
    email: props.user.email,
    role: props.currentRole,
    unit_id: props.currentUnitId,
});

const submit = () => {
    form.post(route('yayasan.users.update', props.user.id));
};

// Reset Password Form
const resetForm = useForm({
    reset_mode: 'email',
    password: '',
    password_confirmation: '',
});

const submitResetPassword = () => {
    resetForm.post(route('yayasan.users.reset-password', props.user.id), {
        onSuccess: () => {
            resetForm.reset('password', 'password_confirmation');
        },
    });
};
</script>


<template>
    <Head title="Edit User" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Edit User
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Perbarui data profil dan hak akses pengguna.</p>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-6 max-w-4xl mx-auto pb-12">
            <!-- Main Form Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <form @submit.prevent="submit">
                    <!-- Section: Account Info -->
                    <div class="p-8 border-b border-gray-100">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl shadow-sm">
                                <UserIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Informasi Akun</h3>
                                <p class="text-sm text-gray-500">Data dasar untuk login dan identifikasi pengguna.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="name" value="Nama Lengkap" class="mb-2" />
                                <TextInput id="name" v-model="form.name" type="text" class="block w-full rounded-xl border-gray-200 bg-white/50 backdrop-blur-sm focus:border-namira-teal focus:ring-namira-teal transition-all shadow-sm" required />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="email" value="Email Address" class="mb-2" />
                                <TextInput id="email" v-model="form.email" type="email" class="block w-full rounded-xl border-gray-200 bg-gray-50/50 text-gray-500 cursor-not-allowed shadow-inner" readonly />
                                <p class="text-xs text-gray-400 mt-1.5 flex items-center gap-1">
                                    <LockClosedIcon class="w-3 h-3" />
                                    Email tidak dapat diubah sembarangan.
                                </p>
                                <InputError :message="form.errors.email" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <!-- Section: Access Control -->
                    <div class="p-8 bg-gray-50/50 backdrop-blur-sm">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl shadow-sm">
                                <LockClosedIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Hak Akses & Unit</h3>
                                <p class="text-sm text-gray-500">Tentukan wewenang dan wilayah kerja pengguna ini.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="unit_id" value="Unit Penugasan" class="mb-2" />
                                <div class="relative">
                                    <select id="unit_id" v-model="form.unit_id" class="appearance-none block w-full pl-4 pr-10 py-3 text-base border-gray-200 focus:outline-none focus:ring-namira-teal focus:border-namira-teal sm:text-sm rounded-xl bg-white/50 backdrop-blur-sm transition-all shadow-sm">
                                        <option value="">Global / Yayasan (Super Admin)</option>
                                        <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                        <ChevronUpDownIcon class="h-4 w-4" />
                                    </div>
                                </div>
                                <InputError :message="form.errors.unit_id" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="role" value="Role" class="mb-2" />
                                <div class="relative">
                                    <select id="role" v-model="form.role" class="appearance-none block w-full pl-4 pr-10 py-3 text-base border-gray-200 focus:outline-none focus:ring-namira-teal focus:border-namira-teal sm:text-sm rounded-xl bg-white/50 backdrop-blur-sm transition-all shadow-sm" required>
                                        <option value="">Pilih Role...</option>
                                        <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                        <ChevronUpDownIcon class="h-4 w-4" />
                                    </div>
                                </div>
                                <InputError :message="form.errors.role" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-end gap-3 px-8 py-6 bg-gray-50/50 border-t border-gray-100">
                        <Link :href="route('yayasan.users.index')" class="px-6 py-2.5 text-sm font-bold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl transition-all shadow-sm">
                            Batal
                        </Link>
                        <PrimaryButton :disabled="form.processing" class="px-8 py-2.5 bg-namira-teal hover:bg-teal-700 text-white font-bold rounded-xl shadow-lg shadow-namira-teal/30 transition-all focus:ring-2 focus:ring-offset-2 focus:ring-namira-teal border-0 hover:-translate-y-0.5">
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Simpan Perubahan</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>

            <!-- Reset Password Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="p-8">
                    <div class="flex items-start gap-4 mb-6">
                        <div class="p-3 bg-red-50 text-red-600 rounded-2xl shadow-sm">
                            <KeyIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Reset Password</h3>
                            <p class="text-sm text-gray-500">Reset password user jika mereka lupa atau perlu di-reset.</p>
                        </div>
                    </div>

                    <form @submit.prevent="submitResetPassword" class="space-y-6">
                        <!-- Reset Mode Selection -->
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 p-4 border rounded-xl cursor-pointer transition-all"
                                   :class="resetForm.reset_mode === 'email' ? 'border-namira-teal bg-teal-50' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" v-model="resetForm.reset_mode" value="email" class="text-namira-teal focus:ring-namira-teal" />
                                <div>
                                    <p class="font-bold text-gray-800">Generate Otomatis & Kirim Email</p>
                                    <p class="text-sm text-gray-500">Sistem akan membuat password acak dan mengirimkannya ke email user.</p>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-4 border rounded-xl cursor-pointer transition-all"
                                   :class="resetForm.reset_mode === 'manual' ? 'border-namira-teal bg-teal-50' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" v-model="resetForm.reset_mode" value="manual" class="text-namira-teal focus:ring-namira-teal" />
                                <div>
                                    <p class="font-bold text-gray-800">Set Password Manual</p>
                                    <p class="text-sm text-gray-500">Anda tentukan password baru dan beritahu user secara langsung.</p>
                                </div>
                            </label>
                        </div>

                        <!-- Manual Password Fields (shown only when manual mode selected) -->
                        <div v-if="resetForm.reset_mode === 'manual'" class="space-y-4 p-4 bg-gray-50 rounded-xl">
                            <div>
                                <InputLabel for="new_password" value="Password Baru" class="mb-2" />
                                <TextInput id="new_password" v-model="resetForm.password" type="password" class="block w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring-namira-teal" placeholder="Minimal 8 karakter" />
                                <InputError :message="resetForm.errors.password" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="confirm_password" value="Konfirmasi Password" class="mb-2" />
                                <TextInput id="confirm_password" v-model="resetForm.password_confirmation" type="password" class="block w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring-namira-teal" placeholder="Ketik ulang password" />
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" :disabled="resetForm.processing"
                                    class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-lg shadow-red-500/30 transition-all focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50">
                                <span v-if="resetForm.processing">Memproses...</span>
                                <span v-else>Reset Password</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
