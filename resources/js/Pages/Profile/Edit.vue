<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import { 
    UserIcon, 
    BuildingOfficeIcon, 
    IdentificationIcon,
    ArrowTopRightOnSquareIcon,
    ShieldCheckIcon,
    ArrowRightOnRectangleIcon
} from '@heroicons/vue/24/outline';
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const logout = () => {
    router.post(route('logout'));
};

const handlePhotoUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    
    router.post(route('profile.photo.update'), {
        photo: file
    }, {
        forceFormData: true,
    });
};

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    unit_name: String,
});

const user = usePage().props.auth.user;

// Computed Properties for Display
const userInitials = computed(() => {
    return user.name
        .split(' ')
        .map(n => n[0])
        .join('')
        .substring(0, 2)
        .toUpperCase();
});

const userRole = computed(() => {
    if (user.roles && user.roles.length > 0) {
        // Handle both string array (new middleware) and object array (legacy)
        const role = typeof user.roles[0] === 'string' ? user.roles[0] : user.roles[0].name;
        return role.replace(/_/g, ' ').toUpperCase();
    }
    return 'USER';
});

const userUnit = computed(() => {
    return props.unit_name || 'Yayasan Namira';
});

// Domain Profile Links
const domainProfileRoute = computed(() => {
    // Check roles to determine if we should link to Teacher/Student/Staff profile
    const roles = user.roles?.map(r => typeof r === 'string' ? r : r.name) || [];
    
    if (roles.includes('teacher') || roles.includes('guru')) {
         // We might need to find the teacher record ID. 
         // For now, let's just show the button if they have the role, 
         // assuming we can eventually link it. 
         // If we don't have the ID handy in auth user, we might hide the direct link 
         // or link to the index.
         // BEST PRACTICE: If `user.teacher` relationship exists.
         // Checking if we can access it safely.
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
    const roles = user.roles?.map(r => typeof r === 'string' ? r : r.name) || [];
    if (roles.includes('teacher') || roles.includes('guru')) return 'Lihat Data Guru';
    if (roles.includes('student') || roles.includes('siswa')) return 'Lihat Data Akademik';
    if (roles.includes('staff')) return 'Lihat Data Pegawai';
    return 'Lihat Profil Detail';
});

// Dynamic Layout Logic
const layoutComponent = computed(() => {
    const roles = user.roles?.map(r => typeof r === 'string' ? r : r.name) || [];
    if (roles.includes('siswa') || roles.includes('student')) {
        return StudentLayout;
    }
    return AuthenticatedLayout;
});
</script>

<template>
    <Head title="Pengaturan Akun" />



    <component :is="layoutComponent" :title="layoutComponent === StudentLayout ? 'Profil Saya' : ''">
        <template #header v-if="layoutComponent !== StudentLayout">
            <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                Pengaturan Akun
            </h2>
            <p class="text-sm text-gray-500 mt-1">Kelola informasi profil, keamanan, dan preferensi akun Anda.</p>
        </template>
        
        <!-- Student Title -->
        <div v-if="layoutComponent === StudentLayout" class="mb-6">
            <h2 class="font-bold text-2xl text-gray-800">Pengaturan Akun</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola informasi profil dan keamanan akunmu.</p>
        </div>

        <div class="py-6 max-w-7xl mx-auto space-y-6">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                
                <!-- Left Column: Identity Card -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Profile Card -->
                    <div class="bg-white rounded-3xl p-6 shadow-xl shadow-gray-200/50 border border-gray-100 flex flex-col items-center text-center relative overflow-hidden">
                        <!-- Background Pattern -->
                        <div class="absolute top-0 w-full h-32 bg-gradient-to-br from-namira-teal/10 to-transparent -z-0"></div>
                        <div class="absolute top-[-50px] right-[-50px] w-32 h-32 bg-namira-teal/20 rounded-full blur-3xl"></div>

                        <!-- Avatar with Upload -->
                        <div class="relative w-32 h-32 mb-4 z-10 group">
                            <!-- Photo Display -->
                            <div v-if="$page.props.auth.user.profile_photo_url && !$page.props.auth.user.profile_photo_url.includes('ui-avatars')" class="w-full h-full rounded-2xl shadow-lg border-4 border-white overflow-hidden">
                                <img :src="$page.props.auth.user.profile_photo_url" class="w-full h-full object-cover">
                            </div>
                            <div v-else class="w-full h-full rounded-2xl shadow-lg border-4 border-white bg-gradient-to-br from-gray-800 to-gray-700 flex items-center justify-center text-white text-4xl font-bold">
                                {{ userInitials }}
                            </div>
                            
                            <!-- Upload Overlay (only for admin/staff, not siswa/guru) -->
                            <label v-if="!domainProfileRoute" class="absolute inset-0 bg-black/50 rounded-2xl flex items-center justify-center cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="text-white text-xs font-bold text-center px-2">Klik untuk<br>Ubah Foto</span>
                                <input type="file" class="hidden" accept="image/*" @change="handlePhotoUpload">
                            </label>
                            
                            <!-- Valid Checkmark -->
                            <div class="absolute -bottom-2 -right-2 bg-green-500 text-white p-1 rounded-full border-4 border-white shadow-sm">
                                <ShieldCheckIcon class="w-5 h-5" />
                            </div>
                        </div>

                        <!-- Name & Email -->
                        <h3 class="font-bold text-xl text-gray-800 relative z-10">{{ user.name }}</h3>
                        <p class="text-sm text-gray-500 mb-6 relative z-10">{{ user.email }}</p>

                        <!-- Information Chips -->
                        <div class="w-full flex flex-col gap-2 relative z-10">
                            <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 border border-gray-100">
                                <div class="p-2 bg-white rounded-lg shadow-sm text-namira-teal">
                                    <IdentificationIcon class="w-5 h-5" />
                                </div>
                                <div class="text-left">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Peran (Role)</p>
                                    <p class="text-xs font-bold text-gray-700">{{ userRole }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 border border-gray-100">
                                <div class="p-2 bg-white rounded-lg shadow-sm text-purple-600">
                                    <BuildingOfficeIcon class="w-5 h-5" />
                                </div>
                                <div class="text-left">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Unit</p>
                                    <p class="text-xs font-bold text-gray-700">{{ userUnit }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Link to Domain Profile (if applicable) -->
                        <div v-if="domainProfileRoute" class="w-full mt-6 pt-6 border-t border-gray-100">
                            <Link :href="domainProfileRoute" class="flex items-center justify-center gap-2 w-full py-3 bg-namira-teal text-white rounded-xl font-bold text-sm shadow-lg shadow-namira-teal/20 transition-all hover:shadow-xl hover:-translate-y-0.5">
                                {{ domainProfileLabel }}
                                <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                            </Link>
                        </div>

                        <!-- Logout Button (Mobile Friendly) -->
                        <div class="w-full mt-4 pt-4 border-t border-gray-100">
                            <form @submit.prevent="logout" class="w-full">
                                <button type="submit" class="flex items-center justify-center gap-2 w-full py-3 bg-red-50 text-red-600 border border-red-100 rounded-xl font-bold text-sm transition-all hover:bg-red-100 hover:shadow-md">
                                    <ArrowRightOnRectangleIcon class="w-4 h-4" />
                                    Keluar Aplikasi
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Settings Forms -->
                <div class="lg:col-span-2 space-y-8">
                    
                    <!-- Update Info -->
                    <div class="bg-white rounded-3xl p-8 shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-md transition-shadow">
                        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <UserIcon class="w-32 h-32" />
                        </div>
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                            class="relative z-10"
                        />
                    </div>

                    <!-- Update Password -->
                    <div class="bg-white rounded-3xl p-8 shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-md transition-shadow">
                         <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <ShieldCheckIcon class="w-32 h-32" />
                        </div>
                        <UpdatePasswordForm class="relative z-10" />
                    </div>



                </div>

            </div>

        </div>

    </component>
</template>
