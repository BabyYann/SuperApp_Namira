<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
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

// Clean Sonner-style floating flash toast
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
    router.get(route(routeNameMap[props.activeTab] || 'quran.index'), {
        unit_id: e.target.value,
        academic_year_id: props.activeYear?.id || undefined,
    }, { preserveScroll: true });
};

const onSwitchYear = (e) => {
    router.get(route(routeNameMap[props.activeTab] || 'quran.index'), {
        unit_id: props.isGlobalAdmin ? props.unit?.id : undefined,
        academic_year_id: e.target.value,
    }, { preserveScroll: true });
};
</script>

<template>
    <div class="mb-5">
        <!-- Floating Flash Toast (Sonner-style) -->
        <Teleport to="body">
            <Transition name="q-toast">
                <div
                    v-if="toast"
                    :key="toast.id"
                    class="fixed top-4 right-4 z-[120] max-w-sm w-[calc(100vw-2rem)] sm:w-auto pointer-events-auto"
                >
                    <div
                        class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl bg-white border shadow-lg shadow-slate-900/8 text-xs font-medium"
                        :class="toast.type === 'success' ? 'border-emerald-200 text-slate-800' : 'border-rose-200 text-rose-800'"
                    >
                        <CheckCircleIcon v-if="toast.type === 'success'" class="w-4 h-4 text-[#00796B] shrink-0" />
                        <ExclamationCircleIcon v-else class="w-4 h-4 text-rose-600 shrink-0" />
                        <span class="flex-1 leading-snug">{{ toast.text }}</span>
                        <button
                            type="button"
                            @click="toast = null"
                            class="p-0.5 rounded text-slate-400 hover:text-slate-600 transition-colors"
                        >
                            <XMarkIcon class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Compact Clean Workspace Header (Linear / Stripe Style) -->
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

            <!-- Right Controls: Context Switchers + Page Actions Slot -->
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

        <!-- Horizontal Underline Tab Rail -->
        <nav
            aria-label="Navigasi Program Al-Qur'an"
            class="flex items-center gap-1 overflow-x-auto scrollbar-none border-b border-slate-200/80 -mx-1 px-1"
        >
            <Link
                v-for="tab in tabs"
                :key="tab.key"
                :href="route(tab.route, queryParams)"
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
