<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { UserIcon, ShieldCheckIcon, ChevronUpDownIcon } from '@heroicons/vue/24/outline';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    units: Array,
    roles: Array,
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
    unit_id: '',
});

const submit = () => {
    form.post(route('yayasan.users.store'));
};
</script>

<template>
    <Head title="Tambah User Baru" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Buat User Baru
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Tambahkan pengguna baru ke dalam sistem.</p>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-6 max-w-4xl mx-auto pb-12">
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <form @submit.prevent="submit">
                    
                    <!-- Section: Account Info -->
                    <div class="p-8 border-b border-gray-100">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl shadow-sm">
                                <UserIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Informasi Dasar</h3>
                                <p class="text-sm text-gray-500">Lengkapi data pribadi dan kredensial login.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="name" value="Nama Lengkap" class="mb-2" />
                                <TextInput id="name" v-model="form.name" type="text" class="block w-full rounded-xl border-gray-200 bg-white/50 backdrop-blur-sm focus:border-namira-teal focus:ring-namira-teal transition-all shadow-sm" placeholder="Contoh: Budi Santoso" required autofocus />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="email" value="Email Address" class="mb-2" />
                                <TextInput id="email" v-model="form.email" type="email" class="block w-full rounded-xl border-gray-200 bg-white/50 backdrop-blur-sm focus:border-namira-teal focus:ring-namira-teal transition-all shadow-sm" placeholder="contoh@namira.school" required />
                                <InputError :message="form.errors.email" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="password" value="Password" class="mb-2" />
                                <TextInput id="password" v-model="form.password" type="password" class="block w-full rounded-xl border-gray-200 bg-white/50 backdrop-blur-sm focus:border-namira-teal focus:ring-namira-teal transition-all shadow-sm" required />
                                <InputError :message="form.errors.password" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="password_confirmation" value="Konfirmasi Password" class="mb-2" />
                                <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="block w-full rounded-xl border-gray-200 bg-white/50 backdrop-blur-sm focus:border-namira-teal focus:ring-namira-teal transition-all shadow-sm" required />
                            </div>
                        </div>
                    </div>

                    <!-- Section: Access Control -->
                    <div class="p-8 bg-gray-50/50 backdrop-blur-sm">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl shadow-sm">
                                <ShieldCheckIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Hak Akses & Unit</h3>
                                <p class="text-sm text-gray-500">Tentukan role dan unit penugasan user ini.</p>
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
                            <span v-else>Simpan User Baru</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
