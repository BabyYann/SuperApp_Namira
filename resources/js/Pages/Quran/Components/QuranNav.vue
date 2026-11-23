<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import QuranDialog from './QuranDialog.vue';
import {
    UserGroupIcon,
    ArrowUpCircleIcon,
    PencilSquareIcon,
    DocumentTextIcon,
    BookOpenIcon,
    Cog6ToothIcon,
    BuildingOffice2Icon,
    CalendarDaysIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    XMarkIcon,
    ChevronDownIcon,
    CheckIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    activeTab: { type: String, required: true }, // index | students | assessments | reports | rubrics | settings
    unit: { type: Object, required: true },
    eligibleUnits: { type: Array, default: () => [] },
    academicYears: { type: Array, default: () => [] },
    activeYear: { type: Object, default: null },
    canManage: { type: Boolean, default: false },
    isGlobalAdmin: { type: Boolean, default: false },
    pageTitle: { type: String, default: "Program Al-Qur'an" },
});

const page = usePage();

// Clean Sonner-style floating flash toast (below MobileAppShell header on mobile)
const toast = ref(null);
let toastTimer = null;

const triggerToast = (type, text) => {
    if (!text) return;
    toast.value = { type, text, id: Date.now() };
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.value = null;
    }, 3800);
};

watch(
    () => [page.props.flash?.success, page.props.flash?.error],
    ([success, error]) => {
        if (success) triggerToast('success', success);
        else if (error) triggerToast('error', error);
    },
    { immediate: true }
);

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
        { key: 'index', label: 'Kelompok & Sesi', mobileLabel: 'Kelompok', route: 'quran.index', icon: UserGroupIcon },
        { key: 'students', label: 'Tracking & Naik Jilid', mobileLabel: 'Tracking', route: 'quran.students.index', icon: ArrowUpCircleIcon },
        { key: 'assessments', label: 'Input Nilai', mobileLabel: 'Nilai', route: 'quran.assessments.index', icon: PencilSquareIcon },
        { key: 'reports', label: 'Cetak Rapor', mobileLabel: 'Rapor', route: 'quran.reports.index', icon: DocumentTextIcon },
    ];
    if (props.canManage) {
        base.push(
            { key: 'rubrics', label: 'Rubrik Materi', mobileLabel: 'Rubrik', route: 'quran.rubrics.index', icon: BookOpenIcon },
            { key: 'settings', label: 'Pengaturan', mobileLabel: 'Atur', route: 'quran.settings.index', icon: Cog6ToothIcon }
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

const switchUnitById = (newUnitId) => {
    router.get(route(routeNameMap[props.activeTab] || 'quran.index'), {
        unit_id: newUnitId,
        academic_year_id: props.activeYear?.id || undefined,
    }, { preserveScroll: true });
};

const switchYearById = (newYearId) => {
    router.get(route(routeNameMap[props.activeTab] || 'quran.index'), {
        unit_id: props.isGlobalAdmin ? props.unit?.id : undefined,
        academic_year_id: newYearId,
    }, { preserveScroll: true });
};

const onSwitchUnit = (e) => switchUnitById(e.target.value);
const onSwitchYear = (e) => switchYearById(e.target.value);

// Mobile context sheet (Unit & Academic Year picker) + auto-center active pill
const showContextSheet = ref(false);
const mobileRailRef = ref(null);
const hasContextSwitchers = computed(() => {
    return (props.isGlobalAdmin && props.eligibleUnits.length > 1) || (props.academicYears.length > 0 && props.activeYear);
});

const mobileContextSummary = computed(() => {
    const parts = [];
    if (props.isGlobalAdmin && props.unit?.name) parts.push(props.unit.name);
    if (props.activeYear) {
        parts.push(`${props.activeYear.name} (${props.activeYear.semester === 'ganjil' ? 'Ganjil' : 'Genap'})`);
    }
    return parts.join(' · ') || props.unit?.name || '';
});

const centerActiveMobileTab = async () => {
    await nextTick();
    const container = mobileRailRef.value;
    if (!container) return;
    const activeEl = container.querySelector('[data-active="true"]');
    if (activeEl && typeof activeEl.scrollIntoView === 'function') {
        activeEl.scrollIntoView({ behavior: 'auto', block: 'nearest', inline: 'center' });
    }
};

onMounted(() => {
    centerActiveMobileTab();

    // Flush Quran prefetch cache automatically whenever any mutating request starts
    if (!window.__namiraQuranCacheInvalidator) {
        window.__namiraQuranCacheInvalidator = router.on('start', (event) => {
            const method = (event?.detail?.visit?.method || 'get').toLowerCase();
            if (method !== 'get' && typeof router.flushByCacheTags === 'function') {
                router.flushByCacheTags('quran');
            }
        });
    }
});
watch(() => props.activeTab, centerActiveMobileTab);
</script>

<template>
    <div class="mb-4 md:mb-5">
        <!-- Floating Flash Toast (clears MobileAppShell header on mobile) -->
        <Teleport to="body">
            <Transition name="q-toast">
                <div
                    v-if="toast"
                    :key="toast.id"
                    class="fixed top-[72px] md:top-4 right-4 left-4 md:left-auto z-[120] md:max-w-sm pointer-events-auto"
                >
                    <div
                        class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl bg-white border shadow-lg shadow-slate-900/10 text-xs font-medium"
                        :class="toast.type === 'success' ? 'border-emerald-200 text-slate-800' : 'border-rose-200 text-rose-800'"
                    >
                        <CheckCircleIcon v-if="toast.type === 'success'" class="w-4 h-4 text-[#00796B] shrink-0" />
                        <ExclamationCircleIcon v-else class="w-4 h-4 text-rose-600 shrink-0" />
                        <span class="flex-1 leading-snug">{{ toast.text }}</span>
                        <button
                            type="button"
                            @click="toast = null"
                            class="p-1 rounded text-slate-400 hover:text-slate-600 transition-colors"
                        >
                            <XMarkIcon class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ==================== DESKTOP HEADER & TAB RAIL (100% PRESERVED) ==================== -->
        <div class="hidden md:block">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between pb-3.5">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <span class="inline-flex items-center gap-1.5 font-medium text-[#00796B]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#00796B]" />
                            Metode Ummi
                        </span>
                        <span class="text-slate-300">/</span>
                        <span class="truncate">{{ unit?.name }}</span>
                    </div>
                    <h1 class="text-lg sm:text-xl font-semibold tracking-tight text-slate-900 mt-0.5">
                        {{ pageTitle }}
                    </h1>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div
                        v-if="isGlobalAdmin && eligibleUnits.length > 1"
                        class="relative inline-flex items-center"
                    >
                        <BuildingOffice2Icon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 pointer-events-none" />
                        <select
                            :value="unit?.id"
                            @change="onSwitchUnit"
                            aria-label="Pilih Unit"
                            class="h-8 pl-8 pr-7 text-xs font-medium text-slate-700 bg-white border border-slate-200/90 rounded-lg shadow-[0_1px_2px_rgba(15,23,42,0.03)] hover:border-slate-300 focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15 transition-colors cursor-pointer"
                        >
                            <option v-for="u in eligibleUnits" :key="u.id" :value="u.id">
                                {{ u.name }}
                            </option>
                        </select>
                    </div>

                    <div
                        v-if="academicYears.length > 0 && activeYear"
                        class="relative inline-flex items-center"
                    >
                        <CalendarDaysIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 pointer-events-none" />
                        <select
                            :value="activeYear.id"
                            @change="onSwitchYear"
                            aria-label="Pilih Tahun Ajaran"
                            class="h-8 pl-8 pr-7 text-xs font-medium text-slate-700 bg-white border border-slate-200/90 rounded-lg shadow-[0_1px_2px_rgba(15,23,42,0.03)] hover:border-slate-300 focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15 transition-colors cursor-pointer"
                        >
                            <option v-for="y in academicYears" :key="y.id" :value="y.id">
                                {{ y.name }} · {{ y.semester === 'ganjil' ? 'Ganjil' : 'Genap' }}{{ y.is_active ? ' (Aktif)' : '' }}
                            </option>
                        </select>
                    </div>

                    <slot name="actions" />
                </div>
            </div>

            <nav
                aria-label="Navigasi Program Al-Qur'an"
                class="flex items-center gap-1 overflow-x-auto scrollbar-none border-b border-slate-200/80 -mx-1 px-1"
            >
                <Link
                    v-for="tab in tabs"
                    :key="tab.key"
                    :href="route(tab.route, queryParams)"
                    prefetch
                    :cache-for="['15s', '45s']"
                    cache-tags="quran"
                    class="group relative inline-flex items-center gap-1.5 px-3 py-2.5 text-[13px] font-medium whitespace-nowrap shrink-0 transition-colors"
                    :class="activeTab === tab.key
                        ? 'text-[#00796B]'
                        : 'text-slate-500 hover:text-slate-800'"
                >
                    <component
                        :is="tab.icon"
                        class="w-4 h-4 shrink-0 transition-colors"
                        :class="activeTab === tab.key ? 'text-[#00796B]' : 'text-slate-400 group-hover:text-slate-600'"
                    />
                    <span>{{ tab.label }}</span>
                    <span
                        class="absolute inset-x-2 -bottom-px h-[2px] rounded-full transition-all duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]"
                        :class="activeTab === tab.key ? 'bg-[#00796B] opacity-100 scale-x-100' : 'bg-slate-300 opacity-0 scale-x-50 group-hover:opacity-40 group-hover:scale-x-75'"
                    />
                </Link>
            </nav>
        </div>

        <!-- ==================== MOBILE NATIVE APP HEADER & STICKY PILL RAIL (< md) ==================== -->
        <div class="md:hidden space-y-2.5">
            <!-- Tight Single-Row Header -->
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0 flex-1">
                    <h1 class="text-[17px] font-semibold tracking-tight text-slate-900 leading-tight truncate">
                        {{ pageTitle }}
                    </h1>
                    <button
                        v-if="hasContextSwitchers"
                        type="button"
                        @click="showContextSheet = true"
                        class="mt-0.5 inline-flex items-center gap-1 text-[11px] font-medium text-[#00796B] active:opacity-70 max-w-full"
                    >
                        <CalendarDaysIcon class="w-3 h-3 shrink-0" />
                        <span class="truncate">{{ mobileContextSummary }}</span>
                        <ChevronDownIcon class="w-3 h-3 shrink-0" />
                    </button>
                    <p v-else class="mt-0.5 text-[11px] text-slate-500 truncate">
                        Metode Ummi · {{ unit?.name }}
                    </p>
                </div>

                <!-- Optional Mobile Action Slot (fallback to desktop actions if mobile-actions not passed) -->
                <div v-if="$slots['mobile-actions']" class="flex items-center gap-1.5 shrink-0">
                    <slot name="mobile-actions" />
                </div>
            </div>

            <!-- Sticky Auto-Centering Mobile Pill Nav Rail -->
            <nav
                ref="mobileRailRef"
                aria-label="Navigasi Al-Qur'an Mobile"
                class="sticky top-[62px] z-30 -mx-4 px-4 py-2 bg-slate-50/92 backdrop-blur-md border-y border-slate-200/70 flex items-center gap-1.5 overflow-x-auto scrollbar-none"
            >
                <Link
                    v-for="tab in tabs"
                    :key="'m-' + tab.key"
                    :href="route(tab.route, queryParams)"
                    prefetch
                    :cache-for="['15s', '45s']"
                    cache-tags="quran"
                    :data-active="activeTab === tab.key ? 'true' : 'false'"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-full text-xs font-medium whitespace-nowrap shrink-0 transition-all active:scale-95 border"
                    :class="activeTab === tab.key
                        ? 'bg-[#00796B] text-white border-[#00796B] shadow-xs'
                        : 'bg-white text-slate-600 border-slate-200/80'"
                >
                    <component
                        :is="tab.icon"
                        class="w-3.5 h-3.5 shrink-0"
                        :class="activeTab === tab.key ? 'text-white' : 'text-slate-400'"
                    />
                    <span>{{ tab.mobileLabel }}</span>
                </Link>
            </nav>
        </div>

        <!-- Mobile Context Switcher Bottom Sheet (Unit & Tahun Ajaran) -->
        <QuranDialog
            :show="showContextSheet"
            variant="sheet"
            title="Pilih Periode & Unit"
            subtitle="Ganti tahun ajaran atau unit sekolah aktif."
            @close="showContextSheet = false"
        >
            <div class="p-4 space-y-5">
                <div v-if="isGlobalAdmin && eligibleUnits.length > 1" class="space-y-2">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Unit Sekolah</div>
                    <div class="divide-y divide-slate-100 rounded-xl border border-slate-200/80 overflow-hidden bg-white">
                        <button
                            v-for="u in eligibleUnits"
                            :key="u.id"
                            type="button"
                            @click="() => { showContextSheet = false; switchUnitById(u.id); }"
                            class="w-full px-3.5 py-3 flex items-center justify-between text-left text-sm transition-colors active:bg-slate-50"
                            :class="String(unit?.id) === String(u.id) ? 'text-[#00796B] font-semibold bg-teal-50/40' : 'text-slate-700 font-medium'"
                        >
                            <span>{{ u.name }}</span>
                            <CheckIcon v-if="String(unit?.id) === String(u.id)" class="w-4 h-4 text-[#00796B]" />
                        </button>
                    </div>
                </div>

                <div v-if="academicYears.length > 0 && activeYear" class="space-y-2">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tahun Ajaran</div>
                    <div class="divide-y divide-slate-100 rounded-xl border border-slate-200/80 overflow-hidden bg-white">
                        <button
                            v-for="y in academicYears"
                            :key="y.id"
                            type="button"
                            @click="() => { showContextSheet = false; switchYearById(y.id); }"
                            class="w-full px-3.5 py-3 flex items-center justify-between text-left text-sm transition-colors active:bg-slate-50"
                            :class="String(activeYear.id) === String(y.id) ? 'text-[#00796B] font-semibold bg-teal-50/40' : 'text-slate-700 font-medium'"
                        >
                            <span>
                                {{ y.name }} · {{ y.semester === 'ganjil' ? 'Semester Ganjil' : 'Semester Genap' }}
                                <span v-if="y.is_active" class="ml-1 text-[11px] text-[#00796B]">(Aktif)</span>
                            </span>
                            <CheckIcon v-if="String(activeYear.id) === String(y.id)" class="w-4 h-4 text-[#00796B]" />
                        </button>
                    </div>
                </div>
            </div>
        </QuranDialog>
    </div>
</template>

<style scoped>
.q-toast-enter-active {
    transition: opacity 220ms cubic-bezier(0.16, 1, 0.3, 1), transform 240ms cubic-bezier(0.16, 1, 0.3, 1);
}
.q-toast-leave-active {
    transition: opacity 150ms ease-in, transform 150ms ease-in;
}
.q-toast-enter-from {
    opacity: 0;
    transform: translateY(-10px) scale(0.96);
}
.q-toast-leave-to {
    opacity: 0;
    transform: translateY(-6px) scale(0.98);
}
</style>
