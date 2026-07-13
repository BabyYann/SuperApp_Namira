<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    BuildingLibraryIcon, EnvelopeIcon, PhoneIcon, MapPinIcon, 
    PencilSquareIcon, ArrowLeftIcon, UsersIcon, AcademicCapIcon, BuildingOffice2Icon
} from '@heroicons/vue/24/outline';

defineProps({
    unit: Object,
    stats: Object,
});
</script>

<template>
    <Head :title="unit.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('yayasan.units.index')" class="p-2 hover:bg-gray-100 rounded-xl transition-colors">
                    <ArrowLeftIcon class="w-5 h-5 text-gray-500" />
                </Link>
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                        Profil Unit
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Detail informasi satuan pendidikan.</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6">
            <!-- Profile Card -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Header with Logo -->
                <div class="relative bg-gradient-to-br from-namira-teal to-teal-700 p-8 text-white">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32 blur-3xl"></div>
                    
                    <div class="relative z-10 flex flex-col md:flex-row gap-6 items-start md:items-center">
                        <!-- Logo -->
                        <div class="w-24 h-24 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center overflow-hidden border-2 border-white/30 shadow-xl">
                            <img v-if="unit.logo" :src="`/storage/${unit.logo}`" class="w-full h-full object-cover" />
                            <BuildingLibraryIcon v-else class="w-12 h-12 text-white/70" />
                        </div>
                        
                        <!-- Info -->
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-3 mb-2">
                                <span class="px-3 py-1 bg-white/20 rounded-full text-xs font-bold uppercase tracking-wide">
                                    {{ unit.category }}
                                </span>
                                <span class="px-3 py-1 bg-white/20 rounded-full text-xs font-bold uppercase tracking-wide">
                                    {{ unit.level }}
                                </span>
                                <span class="px-3 py-1 bg-white/20 rounded-full text-xs font-mono uppercase">
                                    {{ unit.code }}
                                </span>
                            </div>
                            <h1 class="text-3xl font-extrabold mb-1">{{ unit.name }}</h1>
                            <p v-if="unit.description" class="text-white/80 text-sm max-w-2xl">{{ unit.description }}</p>
                        </div>

                        <!-- Edit Button -->
                        <Link :href="route('yayasan.units.edit', unit.id)" class="px-6 py-3 bg-white text-namira-teal rounded-2xl font-bold shadow-lg hover:shadow-xl transition-all hover:-translate-y-0.5 flex items-center gap-2">
                            <PencilSquareIcon class="w-5 h-5" />
                            Edit Unit
                        </Link>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 border-b border-gray-100">
                    <div class="p-6 text-center border-r border-gray-100">
                        <div class="flex justify-center mb-3">
                            <div class="p-3 bg-emerald-100 rounded-xl text-emerald-600">
                                <AcademicCapIcon class="w-6 h-6" />
                            </div>
                        </div>
                        <div class="text-3xl font-extrabold text-gray-900">{{ stats.students }}</div>
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-wide mt-1">Siswa</div>
                    </div>
                    <div class="p-6 text-center border-r border-gray-100">
                        <div class="flex justify-center mb-3">
                            <div class="p-3 bg-blue-100 rounded-xl text-blue-600">
                                <UsersIcon class="w-6 h-6" />
                            </div>
                        </div>
                        <div class="text-3xl font-extrabold text-gray-900">{{ stats.teachers }}</div>
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-wide mt-1">Guru</div>
                    </div>
                    <div class="p-6 text-center">
                        <div class="flex justify-center mb-3">
                            <div class="p-3 bg-amber-100 rounded-xl text-amber-600">
                                <BuildingOffice2Icon class="w-6 h-6" />
                            </div>
                        </div>
                        <div class="text-3xl font-extrabold text-gray-900">{{ stats.classrooms }}</div>
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-wide mt-1">Kelas</div>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="p-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-6">Informasi Kontak</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Email -->
                        <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-2xl">
                            <div class="p-3 bg-white rounded-xl shadow-sm text-gray-500">
                                <EnvelopeIcon class="w-5 h-5" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-1">Email</div>
                                <div class="text-gray-800 font-medium">{{ unit.email || '-' }}</div>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-2xl">
                            <div class="p-3 bg-white rounded-xl shadow-sm text-gray-500">
                                <PhoneIcon class="w-5 h-5" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-1">Telepon</div>
                                <div class="text-gray-800 font-medium">{{ unit.phone || '-' }}</div>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-2xl md:col-span-2">
                            <div class="p-3 bg-white rounded-xl shadow-sm text-gray-500">
                                <MapPinIcon class="w-5 h-5" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-1">Alamat</div>
                                <div class="text-gray-800 font-medium">{{ unit.address || '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kepala Sekolah -->
                <div class="px-8 pb-8 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-6">Kepala Sekolah</h3>
                    <div v-if="unit.principal" class="flex flex-col md:flex-row items-center md:items-start gap-6 p-6 bg-gradient-to-br from-gray-50 to-slate-100 rounded-3xl border border-gray-100/80 shadow-sm">
                        <!-- Foto Profil -->
                        <div class="w-20 h-20 rounded-2xl overflow-hidden border-2 border-white shadow-md flex-shrink-0 bg-white">
                            <img :src="unit.principal.profile_photo_url" class="w-full h-full object-cover" />
                        </div>
                        
                        <!-- Info Detail -->
                        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Nama Kepala Sekolah</div>
                                <div class="text-gray-800 font-extrabold text-base mt-0.5">{{ unit.principal.name }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">NIP / NUPTK</div>
                                <div class="text-gray-800 font-semibold text-sm mt-0.5">{{ unit.principal.teacher_profile?.nip || unit.principal.staff?.nip || '-' }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Email</div>
                                <div class="text-gray-800 font-semibold text-sm mt-0.5">{{ unit.principal.email }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Nomor Telepon</div>
                                <div class="text-gray-800 font-semibold text-sm mt-0.5">{{ unit.principal.teacher_profile?.phone || unit.principal.staff?.phone || '-' }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Status Jabatan</div>
                                <div class="mt-1 flex items-center gap-1.5">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        Aktif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="p-6 bg-gray-50 rounded-2xl border border-dashed border-gray-200 text-center text-gray-500 font-medium">
                        Belum ada Kepala Sekolah yang ditunjuk untuk unit ini.
                    </div>
                </div>

                <!-- Work Hours -->
                <div class="px-8 pb-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Jam Kerja</h3>
                    <div class="flex flex-wrap gap-4">
                        <div class="px-4 py-3 bg-namira-teal/10 rounded-xl border border-namira-teal/20">
                            <div class="text-xs text-namira-teal font-bold uppercase mb-1">Jam Masuk</div>
                            <div class="text-xl font-mono font-bold text-gray-900">{{ unit.work_start_time }}</div>
                        </div>
                        <div class="px-4 py-3 bg-orange-50 rounded-xl border border-orange-200">
                            <div class="text-xs text-orange-600 font-bold uppercase mb-1">Jam Pulang</div>
                            <div class="text-xl font-mono font-bold text-gray-900">{{ unit.work_end_time }}</div>
                        </div>
                        <div class="px-4 py-3 bg-rose-50 rounded-xl border border-rose-200">
                            <div class="text-xs text-rose-600 font-bold uppercase mb-1">Toleransi Terlambat</div>
                            <div class="text-xl font-mono font-bold text-gray-900">{{ unit.late_tolerance_minutes }} menit</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
