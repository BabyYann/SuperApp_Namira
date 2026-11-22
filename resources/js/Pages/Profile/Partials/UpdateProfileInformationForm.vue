<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

import { Link, useForm, usePage } from '@inertiajs/vue3';
import { UserIcon, EnvelopeIcon, InformationCircleIcon, CheckIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
});

const user = usePage().props.auth.user;

const canEditSelf = computed(() => {
    const roles = user.roles?.map(r => (typeof r === 'string' ? r : r.name)) || [];
    return roles.some(r => ['super_admin_yayasan', 'admin_yayasan', 'admin_unit'].includes(r));
});

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header class="mb-4">
            <h2 class="text-sm sm:text-base font-semibold text-slate-900">Informasi Profil</h2>
            <p class="mt-0.5 text-xs text-slate-500">
                Data akun dan alamat email yang terdaftar di sistem sekolah.
            </p>
        </header>

        <!-- Compact Info Banner for Non-Admin Users -->
        <div v-if="!canEditSelf" class="mb-4 p-3 rounded-xl bg-amber-50/70 border border-amber-200/80 text-amber-900 text-xs flex items-start gap-2.5">
            <InformationCircleIcon class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
            <p class="leading-relaxed text-amber-800">
                <strong class="font-semibold">Data resmi sekolah:</strong> Nama, Email, dan Foto dikelola oleh Admin Unit / Yayasan. Hubungi admin bila perlu perbaikan.
            </p>
        </div>

        <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-3.5 sm:space-y-4">
            <div>
                <InputLabel for="name" value="Nama Lengkap" class="text-xs font-medium text-slate-700" />
                <div class="relative mt-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <UserIcon class="h-4 w-4 text-slate-400" />
                    </div>
                    <TextInput
                        id="name"
                        type="text"
                        class="block w-full h-10 sm:h-9 pl-9 text-sm rounded-xl sm:rounded-lg transition-colors focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15"
                        :class="!canEditSelf ? 'bg-slate-50 text-slate-600 border-slate-200 cursor-not-allowed' : 'bg-white border-slate-200'"
                        v-model="form.name"
                        :readonly="!canEditSelf"
                        required
                        autocomplete="name"
                        placeholder="Nama Lengkap"
                    />
                </div>
                <InputError class="mt-1.5" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Alamat Email" class="text-xs font-medium text-slate-700" />
                <div class="relative mt-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <EnvelopeIcon class="h-4 w-4 text-slate-400" />
                    </div>
                    <TextInput
                        id="email"
                        type="email"
                        class="block w-full h-10 sm:h-9 pl-9 text-sm rounded-xl sm:rounded-lg transition-colors focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15"
                        :class="!canEditSelf ? 'bg-slate-50 text-slate-600 border-slate-200 cursor-not-allowed' : 'bg-white border-slate-200'"
                        v-model="form.email"
                        :readonly="!canEditSelf"
                        required
                        autocomplete="username"
                        placeholder="email@sekolah.id"
                    />
                </div>
                <InputError class="mt-1.5" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="text-xs text-slate-600">
                <p>
                    Email Anda belum diverifikasi.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="underline font-medium text-[#00796B] hover:text-[#00695C]"
                    >
                        Kirim ulang verifikasi
                    </Link>
                </p>
                <div v-show="status === 'verification-link-sent'" class="mt-1.5 font-medium text-emerald-600">
                    Tautan verifikasi baru telah dikirim ke email Anda.
                </div>
            </div>

            <div v-if="canEditSelf" class="flex items-center gap-3 pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full sm:w-auto h-10 sm:h-9 px-4 rounded-xl sm:rounded-lg bg-[#00796B] hover:bg-[#00695C] text-white text-xs font-semibold sm:font-medium disabled:opacity-50 transition-all active:scale-[0.98] inline-flex items-center justify-center gap-1.5"
                >
                    <CheckIcon class="w-4 h-4 stroke-[2.5]" />
                    <span>Simpan Perubahan</span>
                </button>

                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-xs font-medium text-[#00796B]">
                        Tersimpan.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
