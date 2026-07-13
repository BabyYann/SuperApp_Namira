<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { BuildingLibraryIcon, ClockIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    principals: Array,
});

const form = useForm({
    name: '',
    code: '',
    category: '',
    level: 'Nasional',
    work_start_time: '07:00',
    work_end_time: '16:00',
    late_tolerance_minutes: 15,
    principal_id: null,
});

const submit = () => {
    form.post(route('yayasan.units.store'));
};
</script>

<template>
    <Head title="Tambah Unit Baru" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                        Tambah Unit Pendidikan
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Daftarkan sekolah atau unit baru ke sistem yayasan.</p>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-6 max-w-4xl mx-auto py-6">
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden relative">
                 <!-- Decorative Shape -->
                 <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-namira-teal/10 to-blue-50 rounded-bl-full -mr-10 -mt-10 pointer-events-none"></div>

                <form @submit.prevent="submit" class="relative z-10">
                    
                    <!-- Section: Unit Info -->
                    <div class="p-8 border-b border-gray-100">
                        <div class="flex items-start gap-4 mb-8">
                            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl shadow-sm">
                                <BuildingLibraryIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Identitas Sekolah</h3>
                                <p class="text-sm text-gray-500">Informasi dasar mengenai unit pendidikan.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-1 md:col-span-2">
                                <InputLabel for="name" value="Nama Sekolah / Unit" class="mb-2" />
                                <TextInput id="name" v-model="form.name" type="text" class="block w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring-namira-teal transition-colors bg-white/50" placeholder="Contoh: SD Islam Namira" required autofocus />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="code" value="Kode Unit" class="mb-2" />
                                <TextInput id="code" v-model="form.code" type="text" class="block w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring-namira-teal transition-colors bg-white/50" placeholder="e.g. SD-01" required />
                                <p class="text-xs text-gray-400 mt-1.5 font-medium">*Maksimal 10 karakter. Harus unik.</p>
                                <InputError :message="form.errors.code" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="category" value="Jenjang Pendidikan" class="mb-2" />
                                <div class="relative">
                                    <select id="category" v-model="form.category" class="appearance-none block w-full pl-4 pr-10 py-2.5 text-base border-gray-200 focus:outline-none focus:ring-namira-teal focus:border-namira-teal sm:text-sm rounded-xl bg-white/50 transition-colors" required>
                                        <option value="">Pilih Jenjang...</option>
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
                                        <option value="Nasional">Nasional (Standard)</option>
                                        <option value="Plus">Plus (Agama/Unggulan)</option>
                                        <option value="Internasional">Internasional (Cambridge/IB)</option>
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
                            <span v-else>Simpan Unit Baru</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
