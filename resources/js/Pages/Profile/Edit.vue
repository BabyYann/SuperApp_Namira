<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import {
    BuildingOfficeIcon,
    IdentificationIcon,
    ArrowTopRightOnSquareIcon,
    ShieldCheckIcon,
    ArrowRightOnRectangleIcon,
    CameraIcon,
    UserIcon,
    KeyIcon,
} from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';
import { compressImageFile } from '@/Utils/imageCompressor';

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    unit_name: String,
});

const user = usePage().props.auth.user;
const isUploadingPhoto = ref(false);
const mobileSection = ref('info'); // 'info' | 'password'

const logout = () => {
    router.post(route('logout'));
};

const handlePhotoUpload = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    try {
        isUploadingPhoto.value = true;
        const compressed = await compressImageFile(file, 800, 0.8);
        router.post(route('profile.photo.update'), { photo: compressed }, {
            forceFormData: true,
            onFinish: () => { isUploadingPhoto.value = false; },
        });
    } catch (err) {
        console.error('Kompresi avatar gagal:', err);
        router.post(route('profile.photo.update'), { photo: file }, {
            forceFormData: true,
            onFinish: () => { isUploadingPhoto.value = false; },
        });
    }
};

const userInitials = computed(() => {
    return (user.name || 'U')
        .split(' ')
        .map(n => n[0])
        .join('')
        .substring(0, 2)
        .toUpperCase();
});

const roleLabelMap = {
    super_admin_yayasan: 'Super Admin Yayasan',
    admin_yayasan: 'Admin Yayasan',
    pembina_yayasan: 'Pembina Yayasan',
    pengawas_yayasan: 'Pengawas Yayasan',
    admin_unit: 'Admin Unit',
    kepala_sekolah: 'Kepala Sekolah',
    koordinator_kurikulum: 'Koordinator Kurikulum',
    koordinator_kesiswaan: 'Koordinator Kesiswaan',
    koordinator_sarpar: 'Koordinator Sarpras',
    koordinator_alquran: "Koordinator Al-Qur'an",
    guru_alquran: "Guru Al-Qur'an",
    wali_kelas: 'Wali Kelas',
    teacher: 'Guru',
    guru: 'Guru',
    humas_unit: 'Humas Unit',
    humas_yayasan: 'Humas Yayasan',
    panitia_spmb: 'Panitia SPMB',
    finance: 'Keuangan',
    staff_admin_keuangan: 'Admin Keuangan',
    bk: 'Guru BK',
    siswa: 'Siswa',
    student: 'Siswa',
};

const normalizedRoles = computed(() => {
    const raw = user.roles?.map(r => (typeof r === 'string' ? r : r.name)) || [];
    return [...new Set(raw)];
});

const userRoleLabels = computed(() => {
    if (!normalizedRoles.value.length) return ['Pengguna'];
    return normalizedRoles.value.map(r => roleLabelMap[r] || r.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()));
});

const userUnit = computed(() => props.unit_name || 'Yayasan Namira');

const hasCustomPhoto = computed(() => {
    return Boolean(user.profile_photo_url && !user.profile_photo_url.includes('ui-avatars'));
});

const domainProfileRoute = computed(() => {
    const roles = normalizedRoles.value;
    if (roles.some(r => ['teacher', 'guru', 'guru_alquran', 'koordinator_alquran'].includes(r))) {
        if (user.teacher?.id) return route('yayasan.teachers.show', user.teacher.id);
    }
    if (roles.includes('student') || roles.includes('siswa')) {
        return route('student.academic');
    }
    if (roles.includes('staff') || roles.includes('karyawan')) {
        if (user.staff?.id) return route('employee.staff.show', user.staff.id);
    }
    return null;
});

const domainProfileLabel = computed(() => {
    const roles = normalizedRoles.value;
    if (roles.some(r => ['teacher', 'guru', 'guru_alquran', 'koordinator_alquran'].includes(r))) return 'Data Guru';
    if (roles.includes('student') || roles.includes('siswa')) return 'Data Akademik';
    if (roles.includes('staff')) return 'Data Pegawai';
    return 'Profil Detail';
});

const layoutComponent = computed(() => {
    const roles = normalizedRoles.value;
    if (roles.includes('siswa') || roles.includes('student')) {
        return StudentLayout;
    }
    return AuthenticatedLayout;
});

const canEditSelf = computed(() => {
    return normalizedRoles.value.some(r => ['super_admin_yayasan', 'admin_yayasan', 'admin_unit'].includes(r));
});
</script>

<template>
    <Head title="Pengaturan Akun" />

    <component :is="layoutComponent" :title="layoutComponent === StudentLayout ? 'Profil Saya' : ''">
        <div class="max-w-5xl mx-auto py-1 sm:py-2 pb-24 lg:pb-8 space-y-4 lg:space-y-5">
            <!-- Compact Top Header (Desktop & Student Layout) -->
            <div class="hidden lg:flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold tracking-tight text-slate-900">Pengaturan Akun</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Kelola informasi profil, keamanan, dan preferensi akun Anda.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-6 items-start">
                <!-- ==================== IDENTITY CARD (COMPACT HORIZONTAL ON MOBILE, SIDEBAR ON DESKTOP) ==================== -->
                <div class="lg:col-span-4">
                    <div class="bg-white rounded-2xl lg:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] p-4 lg:p-5">
                        <!-- Top Row: Horizontal on Mobile, Centered Vertical on Desktop -->
                        <div class="flex items-center gap-3.5 lg:flex-col lg:text-center">
                            <!-- Avatar -->
                            <div class="relative w-14 h-14 lg:w-24 lg:h-24 shrink-0 group">
                                <div v-if="hasCustomPhoto" class="w-full h-full rounded-2xl border border-slate-200 overflow-hidden bg-slate-100">
                                    <img :src="user.profile_photo_url" :alt="user.name" class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="w-full h-full rounded-2xl bg-[#00796B] flex items-center justify-center text-white text-lg lg:text-2xl font-semibold">
                                    {{ userInitials }}
                                </div>

                                <div v-if="isUploadingPhoto" class="absolute inset-0 bg-slate-900/60 rounded-2xl flex items-center justify-center z-20">
                                    <span class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                </div>
                                <label
                                    v-else-if="canEditSelf"
                                    class="absolute -bottom-1 -right-1 lg:inset-0 lg:bottom-auto lg:right-auto w-6 h-6 lg:w-full lg:h-full rounded-full lg:rounded-2xl bg-slate-900/90 lg:bg-black/50 text-white flex items-center justify-center cursor-pointer lg:opacity-0 lg:group-hover:opacity-100 transition-opacity z-20 ring-2 ring-white lg:ring-0"
                                    title="Ubah Foto Profil"
                                >
                                    <CameraIcon class="w-3.5 h-3.5 lg:w-5 lg:h-5" />
                                    <input type="file" class="hidden" accept="image/*" @change="handlePhotoUpload" />
                                </label>
                                <div v-else class="hidden lg:flex absolute -bottom-1.5 -right-1.5 bg-emerald-500 text-white p-1 rounded-full ring-2 ring-white">
                                    <ShieldCheckIcon class="w-3.5 h-3.5" />
                                </div>
                            </div>

                            <!-- Name, Email & Compact Mobile Pills -->
                            <div class="min-w-0 flex-1 lg:w-full">
                                <h2 class="text-[15px] lg:text-base font-semibold text-slate-900 truncate leading-snug">
                                    {{ user.name }}
                                </h2>
                                <p class="text-xs text-slate-500 truncate mt-0.5">{{ user.email }}</p>

                                <!-- Mobile Inline Role & Unit Chips (Zero vertical bloat!) -->
                                <div class="flex flex-wrap items-center gap-1 mt-2 lg:hidden">
                                    <span
                                        v-for="role in userRoleLabels.slice(0, 2)"
                                        :key="role"
                                        class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-teal-50 text-[#00796B] border border-teal-200/70"
                                    >
                                        {{ role }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-600 truncate max-w-[150px]">
                                        {{ userUnit }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Role & Unit Info Rows -->
                        <div class="hidden lg:block mt-5 pt-4 border-t border-slate-100 space-y-2.5 text-left">
                            <div class="flex items-start gap-2.5 px-3 py-2.5 rounded-lg bg-slate-50 border border-slate-100">
                                <IdentificationIcon class="w-4 h-4 text-[#00796B] mt-0.5 shrink-0" />
                                <div class="min-w-0">
                                    <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Peran</div>
                                    <div class="text-xs font-medium text-slate-800 mt-0.5">{{ userRoleLabels.join(', ') }}</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5 px-3 py-2.5 rounded-lg bg-slate-50 border border-slate-100">
                                <BuildingOfficeIcon class="w-4 h-4 text-slate-500 mt-0.5 shrink-0" />
                                <div class="min-w-0">
                                    <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Unit</div>
                                    <div class="text-xs font-medium text-slate-800 mt-0.5">{{ userUnit }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions Row (Compact Side-by-Side on Mobile, Stacked on Desktop) -->
                        <div class="mt-3.5 pt-3 border-t border-slate-100 flex lg:flex-col gap-2">
                            <Link
                                v-if="domainProfileRoute"
                                :href="domainProfileRoute"
                                class="flex-1 h-9 px-3 rounded-xl lg:rounded-lg bg-[#00796B] hover:bg-[#00695C] text-white text-xs font-medium transition-all active:scale-[0.98] inline-flex items-center justify-center gap-1.5"
                            >
                                <span>{{ domainProfileLabel }}</span>
                                <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                            </Link>

                            <form @submit.prevent="logout" :class="domainProfileRoute ? 'flex-1 lg:w-full' : 'w-full'">
                                <button
                                    type="submit"
                                    class="w-full h-9 px-3 rounded-xl lg:rounded-lg bg-rose-50 hover:bg-rose-100/80 text-rose-600 border border-rose-200/70 text-xs font-medium transition-all active:scale-[0.98] inline-flex items-center justify-center gap-1.5"
                                >
                                    <ArrowRightOnRectangleIcon class="w-3.5 h-3.5" />
                                    <span>Keluar</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ==================== FORMS COLUMN ==================== -->
                <div class="lg:col-span-8 space-y-4">
                    <!-- Mobile Compact Segmented Switch (Data Akun vs Keamanan) -->
                    <div class="lg:hidden grid grid-cols-2 p-1 rounded-xl bg-slate-200/70 gap-1">
                        <button
                            type="button"
                            @click="mobileSection = 'info'"
                            class="h-9 rounded-lg text-xs font-semibold inline-flex items-center justify-center gap-1.5 transition-all"
                            :class="mobileSection === 'info' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600'"
                        >
                            <UserIcon class="w-3.5 h-3.5" :class="mobileSection === 'info' ? 'text-[#00796B]' : 'text-slate-400'" />
                            <span>Data Akun</span>
                        </button>
                        <button
                            type="button"
                            @click="mobileSection = 'password'"
                            class="h-9 rounded-lg text-xs font-semibold inline-flex items-center justify-center gap-1.5 transition-all"
                            :class="mobileSection === 'password' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600'"
                        >
                            <KeyIcon class="w-3.5 h-3.5" :class="mobileSection === 'password' ? 'text-[#00796B]' : 'text-slate-400'" />
                            <span>Ubah Password</span>
                        </button>
                    </div>

                    <!-- Update Profile Info Card -->
                    <div
                        class="bg-white rounded-2xl lg:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] p-4 sm:p-5 lg:p-6"
                        :class="mobileSection === 'info' ? 'block' : 'hidden lg:block'"
                    >
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                        />
                    </div>

                    <!-- Update Password Card -->
                    <div
                        class="bg-white rounded-2xl lg:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] p-4 sm:p-5 lg:p-6"
                        :class="mobileSection === 'password' ? 'block' : 'hidden lg:block'"
                    >
                        <UpdatePasswordForm />
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
