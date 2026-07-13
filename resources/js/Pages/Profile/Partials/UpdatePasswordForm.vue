<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { KeyIcon, LockClosedIcon, EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const showCurrentPassword = ref(false);
const showPassword = ref(false);
const showConfirmation = ref(false);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header class="mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <span class="w-1 h-6 bg-red-500 rounded-full"></span>
                Ubah Password
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Pastikan akun Anda menggunakan password yang panjang dan acak agar tetap aman.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
            <div>
                <InputLabel for="current_password" value="Password Saat Ini" />
                <div class="relative mt-1">
                     <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                        <KeyIcon class="h-5 w-5 text-gray-400" />
                    </div>
                    <TextInput
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        :type="showCurrentPassword ? 'text' : 'password'"
                        class="block w-full pl-10 pr-10 bg-gray-50 border-gray-200 focus:bg-white transition-colors"
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <button type="button" @click="showCurrentPassword = !showCurrentPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors z-10">
                        <EyeIcon v-if="!showCurrentPassword" class="h-5 w-5" />
                        <EyeSlashIcon v-else class="h-5 w-5" />
                    </button>
                </div>

                <InputError
                    :message="form.errors.current_password"
                    class="mt-2"
                />
            </div>

            <div>
                <InputLabel for="password" value="Password Baru" />
                <div class="relative mt-1">
                     <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                        <LockClosedIcon class="h-5 w-5 text-gray-400" />
                    </div>
                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        class="block w-full pl-10 pr-10 bg-gray-50 border-gray-200 focus:bg-white transition-colors"
                        autocomplete="new-password"
                        placeholder="••••••••"
                    />
                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors z-10">
                         <EyeIcon v-if="!showPassword" class="h-5 w-5" />
                        <EyeSlashIcon v-else class="h-5 w-5" />
                    </button>
                </div>

                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Konfirmasi Password Baru" />
                <div class="relative mt-1">
                     <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                        <LockClosedIcon class="h-5 w-5 text-gray-400" />
                    </div>
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="showConfirmation ? 'text' : 'password'"
                        class="block w-full pl-10 pr-10 bg-gray-50 border-gray-200 focus:bg-white transition-colors"
                        autocomplete="new-password"
                        placeholder="••••••••"
                    />
                    <button type="button" @click="showConfirmation = !showConfirmation" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors z-10">
                         <EyeIcon v-if="!showConfirmation" class="h-5 w-5" />
                        <EyeSlashIcon v-else class="h-5 w-5" />
                    </button>
                </div>

                <InputError
                    :message="form.errors.password_confirmation"
                    class="mt-2"
                />
            </div>

            <div class="flex items-center gap-4 pt-4 border-t border-gray-50">
                <PrimaryButton :disabled="form.processing" class="bg-gray-800 hover:bg-gray-900 px-6 py-2.5 rounded-xl shadow-lg shadow-gray-800/20 transition-all hover:scale-105 active:scale-95">
                    Update Password
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm font-bold text-gray-600 dark:text-gray-400 bg-gray-100 px-3 py-1 rounded-lg"
                    >
                        Tersimpan.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
