<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { KeyIcon, LockClosedIcon, EyeIcon, EyeSlashIcon, CheckIcon } from '@heroicons/vue/24/outline';

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
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header class="mb-4">
            <h2 class="text-sm sm:text-base font-semibold text-slate-900">Ubah Password</h2>
            <p class="mt-0.5 text-xs text-slate-500">
                Gunakan password yang kuat dan unik untuk menjaga keamanan akun Anda.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="space-y-3.5 sm:space-y-4">
            <div>
                <InputLabel for="current_password" value="Password Saat Ini" class="text-xs font-medium text-slate-700" />
                <div class="relative mt-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                        <KeyIcon class="h-4 w-4 text-slate-400" />
                    </div>
                    <TextInput
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        :type="showCurrentPassword ? 'text' : 'password'"
                        class="block w-full h-10 sm:h-9 pl-9 pr-10 text-sm bg-white border-slate-200 rounded-xl sm:rounded-lg focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15 transition-colors"
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <button type="button" @click="showCurrentPassword = !showCurrentPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 z-10">
                        <EyeIcon v-if="!showCurrentPassword" class="h-4 w-4" />
                        <EyeSlashIcon v-else class="h-4 w-4" />
                    </button>
                </div>
                <InputError :message="form.errors.current_password" class="mt-1.5" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <InputLabel for="password" value="Password Baru" class="text-xs font-medium text-slate-700" />
                    <div class="relative mt-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                            <LockClosedIcon class="h-4 w-4 text-slate-400" />
                        </div>
                        <TextInput
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            class="block w-full h-10 sm:h-9 pl-9 pr-10 text-sm bg-white border-slate-200 rounded-xl sm:rounded-lg focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15 transition-colors"
                            autocomplete="new-password"
                            placeholder="••••••••"
                        />
                        <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 z-10">
                            <EyeIcon v-if="!showPassword" class="h-4 w-4" />
                            <EyeSlashIcon v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password" class="mt-1.5" />
                </div>

                <div>
                    <InputLabel for="password_confirmation" value="Konfirmasi Password Baru" class="text-xs font-medium text-slate-700" />
                    <div class="relative mt-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                            <LockClosedIcon class="h-4 w-4 text-slate-400" />
                        </div>
                        <TextInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :type="showConfirmation ? 'text' : 'password'"
                            class="block w-full h-10 sm:h-9 pl-9 pr-10 text-sm bg-white border-slate-200 rounded-xl sm:rounded-lg focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15 transition-colors"
                            autocomplete="new-password"
                            placeholder="••••••••"
                        />
                        <button type="button" @click="showConfirmation = !showConfirmation" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 z-10">
                            <EyeIcon v-if="!showConfirmation" class="h-4 w-4" />
                            <EyeSlashIcon v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password_confirmation" class="mt-1.5" />
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full sm:w-auto h-10 sm:h-9 px-4 rounded-xl sm:rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold sm:font-medium disabled:opacity-50 transition-all active:scale-[0.98] inline-flex items-center justify-center gap-1.5"
                >
                    <CheckIcon class="w-4 h-4 stroke-[2.5]" />
                    <span>Update Password</span>
                </button>

                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-xs font-medium text-[#00796B]">
                        Password berhasil diperbarui.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
