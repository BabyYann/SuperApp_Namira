<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import {
    UserGroupIcon,
    ArrowUpCircleIcon,
    PencilSquareIcon,
    DocumentTextIcon,
    BookOpenIcon,
    Cog6ToothIcon,
    BuildingOffice2Icon,
    CalendarDaysIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    activeTab: { type: String, required: true }, // index | students | assessments | reports | rubrics | settings
    unit: { type: Object, required: true },
    eligibleUnits: { type: Array, default: () => [] },
    academicYears: { type: Array, default: () => [] },
    activeYear: { type: Object, default: null },
    canManage: { type: Boolean, default: false },
    isGlobalAdmin: { type: Boolean, default: false },
});

const routeNameMap = {
    index: 'quran.index',
    students: 'quran.students.index',
    assessments: 'quran.assessments.index',
    reports: 'quran.reports.index',
    rubrics: 'quran.rubrics.index',
    settings: 'quran.settings.index',
};

const tabs = computed(() => {
    const base = [
        { key: 'index', label: 'Kelompok & Sesi', route: 'quran.index', icon: UserGroupIcon },
        { key: 'students', label: 'Tracking & Naik Jilid', route: 'quran.students.index', icon: ArrowUpCircleIcon },
        { key: 'assessments', label: 'Input Nilai', route: 'quran.assessments.index', icon: PencilSquareIcon },
        { key: 'reports', label: 'Cetak Rapor', route: 'quran.reports.index', icon: DocumentTextIcon },
    ];
    if (props.canManage) {
        base.push(
            { key: 'rubrics', label: 'Rubrik Materi', route: 'quran.rubrics.index', icon: BookOpenIcon },
            { key: 'settings', label: 'Pengaturan', route: 'quran.settings.index', icon: Cog6ToothIcon }
        );
    }
    return base;
});

const queryParams = computed(() => {
    const p = {};
    if (props.isGlobalAdmin && props.unit?.id) p.unit_id = props.unit.id;
    if (props.activeYear?.id) p.academic_year_id = props.activeYear.id;
    return p;
});

const onSwitchUnit = (e) => {
    const newUnitId = e.target.value;
    router.get(route(routeNameMap[props.activeTab] || 'quran.index'), {
        unit_id: newUnitId,
        academic_year_id: props.activeYear?.id || undefined,
    }, { preserveScroll: true });
};

const onSwitchYear = (e) => {
    const newYearId = e.target.value;
    router.get(route(routeNameMap[props.activeTab] || 'quran.index'), {
        unit_id: props.isGlobalAdmin ? props.unit?.id : undefined,
        academic_year_id: newYearId,
    }, { preserveScroll: true });
};
</script>

<template>
    <div class="space-y-4 mb-6">
        <!-- Hero Header -->
        <div class="bg-gradient-to-r from-[#1b4332] via-[#23533f] to-[#0f766e] rounded-3xl p-5 sm:p-6 text-white shadow-lg shadow-emerald-950/15 relative overflow-hidden">
            <div class="absolute -right-8 -top-8 w-44 h-44 rounded-full bg-amber-400/10 blur-2xl pointer-events-none"></div>
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 relative z-10">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-white/15 backdrop-blur-md border border-white/20 flex items-center justify-center shrink-0">
                        <BookOpenIcon class="w-6 h-6 text-amber-300" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-[10px] font-extrabold uppercase tracking-widest px-2.5 py-0.5 rounded-full bg-amber-400/20 text-amber-200 border border-amber-300/30">
                                Metode Ummi
                            </span>
                            <span class="text-xs text-emerald-100/80 font-medium">{{ unit?.name }}</span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight mt-0.5">
                            Program Al-Qur'an &amp; Rapor Ummi
                        </h1>
                    </div>
                </div>

                <!-- Switchers -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <div v-if="isGlobalAdmin && eligibleUnits.length > 1" class="flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl px-3 py-1.5">
                        <BuildingOffice2Icon class="w-4 h-4 text-amber-300 shrink-0" />
                        <select
                            :value="unit?.id"
                            @change="onSwitchUnit"
                            class="bg-transparent text-white text-xs font-bold border-0 focus:ring-0 py-0.5 pr-6 cursor-pointer"
                        >
                            <option v-for="u in eligibleUnits" :key="u.id" :value="u.id" class="text-slate-900">
                                {{ u.name }}
                            </option>
                        </select>
                    </div>

                    <div v-if="academicYears.length > 0 && activeYear" class="flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl px-3 py-1.5">
                        <CalendarDaysIcon class="w-4 h-4 text-amber-300 shrink-0" />
                        <select
                            :value="activeYear.id"
                            @change="onSwitchYear"
                            class="bg-transparent text-white text-xs font-bold border-0 focus:ring-0 py-0.5 pr-6 cursor-pointer"
                        >
                            <option v-for="y in academicYears" :key="y.id" :value="y.id" class="text-slate-900">
                                {{ y.name }} • {{ y.semester === 'ganjil' ? 'Sem. Ganjil' : 'Sem. Genap' }} {{ y.is_active ? '(Aktif)' : '' }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Module Sub Navigation Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
            <Link
                v-for="tab in tabs"
                :key="tab.key"
                :href="route(tab.route, queryParams)"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-bold whitespace-nowrap transition-all border shrink-0"
                :class="activeTab === tab.key
                    ? 'bg-[#1b4332] text-white border-[#1b4332] shadow-md shadow-emerald-950/15'
                    : 'bg-white text-slate-600 border-slate-200 hover:bg-emerald-50 hover:text-emerald-800 hover:border-emerald-200'"
            >
                <component :is="tab.icon" class="w-4 h-4" />
                <span>{{ tab.label }}</span>
            </Link>
        </div>
    </div>
</template>
