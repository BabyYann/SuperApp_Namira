<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { 
    UserGroupIcon, PlusIcon, MagnifyingGlassIcon, PhoneIcon, 
    CalendarIcon, HeartIcon, SparklesIcon, CheckCircleIcon, ClockIcon 
} from '@heroicons/vue/24/outline';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    children: Object,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters.search || '');

watch(search, (value) => {
    router.get(
        route('daycare.children.index'),
        { search: value },
        { preserveState: true, replace: true }
    );
});
</script>

<template>
    <Head title="Data Ananda Daycare" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="font-extrabold text-2xl bg-gradient-to-r from-amber-600 to-orange-600 bg-clip-text text-transparent leading-tight flex items-center gap-2">
                        <UserGroupIcon class="w-8 h-8 text-amber-500" />
                        <span>Data Ananda Daycare</span>
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">Daftar anak terdaftar di unit pengasuhan daycare.</p>
                </div>

                <Link
                    :href="route('daycare.children.create')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-bold text-sm shadow-md shadow-amber-500/20 transition-all active:scale-95"
                >
                    <PlusIcon class="w-5 h-5 stroke-[2.5]" />
                    <span>Daftarkan Ananda Baru</span>
                </Link>
            </div>
        </template>

        <div class="py-6 space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Quick Stats Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-5 rounded-3xl bg-gradient-to-br from-amber-500 to-orange-500 text-white shadow-lg shadow-amber-500/20 relative overflow-hidden flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-100 mb-1">Total Ananda Daycare</p>
                        <h3 class="text-3xl font-black">{{ stats.total || 0 }} <span class="text-sm font-semibold opacity-90">Anak</span></h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white">
                        <HeartIcon class="w-7 h-7" />
                    </div>
                </div>

                <div class="p-5 rounded-3xl bg-white border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Hadir Hari Ini (Check-in)</p>
                        <h3 class="text-3xl font-black text-slate-800">{{ stats.checked_in_today || 0 }} <span class="text-sm font-semibold text-slate-400">Anak</span></h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center">
                        <CheckCircleIcon class="w-7 h-7" />
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-100 p-4 flex items-center justify-between gap-4">
                <div class="relative flex-1">
                    <MagnifyingGlassIcon class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input 
                        v-model="search"
                        type="text" 
                        placeholder="Cari nama ananda, NIS, atau nama orang tua..." 
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-amber-500 focus:ring-amber-500/20"
                    />
                </div>

                <Link 
                    :href="route('daycare.attendance.index')"
                    class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-2 transition-all"
                >
                    <ClockIcon class="w-4 h-4 text-slate-500" />
                    <span>Presensi & Handover</span>
                </Link>
            </div>

            <!-- Children Grid -->
            <div v-if="children.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <div 
                    v-for="child in children.data" 
                    :key="child.id"
                    class="bg-white rounded-3xl border border-slate-100 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between group"
                >
                    <div class="p-6">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-16 h-16 rounded-2xl bg-amber-50 border-2 border-amber-200 overflow-hidden shrink-0 flex items-center justify-center text-amber-700 font-black text-xl shadow-xs group-hover:scale-105 transition-transform">
                                <img v-if="child.photo" :src="`/storage/${child.photo}`" class="w-full h-full object-cover" />
                                <span v-else>{{ child.full_name.substring(0,2).toUpperCase() }}</span>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-base text-slate-800 truncate leading-tight group-hover:text-amber-600 transition-colors">
                                    {{ child.full_name }}
                                </h4>
                                <p v-if="child.daycare_profile?.nickname" class="text-xs text-amber-600 font-bold">
                                    "{{ child.daycare_profile.nickname }}"
                                </p>
                                <span class="inline-block mt-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                    NIS: {{ child.nis }}
                                </span>
                            </div>
                        </div>

                        <!-- Info Pills -->
                        <div class="space-y-2 text-xs text-slate-600">
                            <div class="flex items-center gap-2">
                                <PhoneIcon class="w-4 h-4 text-slate-400 shrink-0" />
                                <span class="truncate">Ortu: {{ child.parent_name }}</span>
                            </div>
                            <div v-if="child.daycare_profile?.allergies" class="flex items-center gap-2 text-rose-600 font-semibold">
                                <SparklesIcon class="w-4 h-4 text-rose-500 shrink-0" />
                                <span class="truncate">Alergi: {{ child.daycare_profile.allergies }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer Actions -->
                    <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            <span 
                                class="w-2.5 h-2.5 rounded-full"
                                :class="child.today_attendance?.check_in_time ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300'"
                            ></span>
                            <span class="text-[11px] font-bold" :class="child.today_attendance?.check_in_time ? 'text-emerald-700' : 'text-slate-400'">
                                {{ child.today_attendance?.check_in_time ? `Hadir ${child.today_attendance.check_in_time.substring(0,5)}` : 'Belum Check-in' }}
                            </span>
                        </div>

                        <Link
                            :href="route('daycare.children.show', child.id)"
                            class="text-xs font-extrabold text-amber-600 hover:text-amber-700 flex items-center gap-1"
                        >
                            <span>Detail</span>
                            <span>→</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-3xl border border-slate-100 p-12 text-center">
                <div class="w-16 h-16 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <UserGroupIcon class="w-8 h-8" />
                </div>
                <h3 class="font-extrabold text-lg text-slate-800 mb-1">Belum Ada Data Ananda Daycare</h3>
                <p class="text-sm text-slate-500 mb-6 max-w-sm mx-auto">Daftarkan anak daycare baru untuk mulai mengelola presensi, timeline harian, dan tumbuh kembang.</p>
                <Link
                    :href="route('daycare.children.create')"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm shadow-md"
                >
                    <PlusIcon class="w-5 h-5" />
                    <span>Daftarkan Ananda Baru</span>
                </Link>
            </div>

            <Pagination :links="children.links" />
        </div>
    </AuthenticatedLayout>
</template>
