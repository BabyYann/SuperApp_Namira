<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import QuranNav from './Components/QuranNav.vue';
import {
    PrinterIcon,
    DocumentArrowDownIcon,
    MagnifyingGlassIcon,
    LockClosedIcon,
    CheckCircleIcon,
    ClockIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    unit: Object,
    eligibleUnits: Array,
    academicYears: Array,
    activeYear: Object,
    semester: String,
    canManage: Boolean,
    isGlobalAdmin: Boolean,
    isLocked: Boolean,
    groups: Array,
    levels: Array,
    classrooms: Array,
    teachers: Array,
    students: Array,
    filters: Object,
});

const groupFilter = ref(props.filters?.group_id ?? 'all');
const levelFilter = ref(props.filters?.level_id ?? 'all');
const teacherFilter = ref(props.filters?.teacher_id ?? 'all');
const classroomFilter = ref(props.filters?.classroom_id ?? 'all');
const searchQuery = ref(props.filters?.search ?? '');
const onlyReady = ref(false);
const selectedIds = ref([]);

const applyFilters = (semOverride = null) => {
    router.get(route('quran.reports.index'), {
        unit_id: props.isGlobalAdmin ? props.unit.id : undefined,
        academic_year_id: props.activeYear.id,
        semester: semOverride ?? props.semester,
        group_id: groupFilter.value,
        level_id: levelFilter.value,
        teacher_id: teacherFilter.value,
        classroom_id: classroomFilter.value,
        search: searchQuery.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const visibleStudents = computed(() => {
    if (!onlyReady.value) return props.students || [];
    return (props.students || []).filter(s => s.is_ready);
});

const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedIds.value = visibleStudents.value.map(s => s.student_id);
    } else {
        selectedIds.value = [];
    }
};

const openReportUrl = (routeName, ids) => {
    if (!ids || ids.length === 0) return;
    const params = new URLSearchParams();
    if (props.isGlobalAdmin) params.set('unit_id', props.unit.id);
    params.set('academic_year_id', props.activeYear.id);
    params.set('semester', props.semester);
    params.set('student_ids', ids.join(','));
    const url = `${route(routeName)}?${params.toString()}`;
    window.open(url, '_blank');
};
</script>

<template>
    <Head title="Cetak Rapor Al-Qur'an (Ummi)" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <QuranNav
                active-tab="reports"
                :unit="unit"
                :eligible-units="eligibleUnits"
                :academic-years="academicYears"
                :active-year="activeYear"
                :can-manage="canManage"
                :is-global-admin="isGlobalAdmin"
            />

            <!-- Locked Notice -->
            <div v-if="isLocked" class="bg-amber-50 border-2 border-amber-200 rounded-3xl p-8 text-center">
                <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center mx-auto mb-3">
                    <LockClosedIcon class="w-7 h-7" />
                </div>
                <h3 class="text-lg font-extrabold text-amber-900">Fitur Cetak Rapor Sedang Ditutup</h3>
                <p class="text-sm text-amber-800/80 max-w-md mx-auto mt-1">
                    Koordinator Al-Qur'an / Admin belum membuka akses cetak rapor untuk guru.
                </p>
            </div>

            <template v-else>
                <!-- Filter Bar -->
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm mb-5 space-y-3">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <div class="relative flex-1 min-w-[200px]">
                            <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                v-model="searchQuery"
                                @keyup.enter="applyFilters()"
                                type="text"
                                placeholder="Cari nama siswa / NISN..."
                                class="w-full pl-9 pr-4 py-2 text-sm border-slate-200 rounded-xl"
                            />
                        </div>

                        <select v-model="groupFilter" @change="applyFilters()" class="py-2 px-3 text-sm border-slate-200 rounded-xl text-slate-700">
                            <option value="all">Semua Kelompok</option>
                            <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }} ({{ g.level_name }})</option>
                        </select>

                        <select v-model="levelFilter" @change="applyFilters()" class="py-2 px-3 text-sm border-slate-200 rounded-xl text-slate-700">
                            <option value="all">Semua Jilid</option>
                            <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                        </select>

                        <select v-if="canManage" v-model="teacherFilter" @change="applyFilters()" class="py-2 px-3 text-sm border-slate-200 rounded-xl text-slate-700">
                            <option value="all">Semua Pengampu</option>
                            <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>

                        <select v-model="classroomFilter" @change="applyFilters()" class="py-2 px-3 text-sm border-slate-200 rounded-xl text-slate-700">
                            <option value="all">Semua Kelas</option>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">Kelas {{ c.name }}</option>
                        </select>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="inline-flex items-center gap-1 bg-slate-100 p-1 rounded-xl">
                                <button @click="applyFilters('ganjil')" class="px-3 py-1 rounded-lg text-xs font-bold" :class="semester === 'ganjil' ? 'bg-amber-500 text-white shadow' : 'text-slate-600'">Sem. Ganjil</button>
                                <button @click="applyFilters('genap')" class="px-3 py-1 rounded-lg text-xs font-bold" :class="semester === 'genap' ? 'bg-amber-500 text-white shadow' : 'text-slate-600'">Sem. Genap</button>
                            </div>
                            <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                                <input type="checkbox" v-model="onlyReady" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span>Hanya yg sudah dinilai</span>
                            </label>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                @click="openReportUrl('quran.reports.print', selectedIds)"
                                :disabled="selectedIds.length === 0"
                                class="px-4 py-2 rounded-xl bg-[#1b4332] text-white text-xs font-bold hover:bg-[#143325] disabled:opacity-40 inline-flex items-center gap-1.5 shadow-sm"
                            >
                                <PrinterIcon class="w-4 h-4" />
                                <span>Print Massal F4 ({{ selectedIds.length }})</span>
                            </button>
                            <button
                                @click="openReportUrl('quran.reports.pdf', selectedIds)"
                                :disabled="selectedIds.length === 0"
                                class="px-4 py-2 rounded-xl bg-amber-500 text-white text-xs font-bold hover:bg-amber-600 disabled:opacity-40 inline-flex items-center gap-1.5 shadow-sm"
                            >
                                <DocumentArrowDownIcon class="w-4 h-4" />
                                <span>Unduh PDF F4 ({{ selectedIds.length }})</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-[11px] uppercase text-slate-500 bg-slate-50 border-b border-slate-200/80">
                                <tr>
                                    <th class="px-4 py-3 w-10">
                                        <input
                                            type="checkbox"
                                            @change="toggleSelectAll"
                                            :checked="visibleStudents.length > 0 && selectedIds.length === visibleStudents.length"
                                            class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                        />
                                    </th>
                                    <th class="px-4 py-3 font-extrabold">Siswa &amp; Kelas</th>
                                    <th class="px-4 py-3 font-extrabold">Jilid Rapor</th>
                                    <th class="px-4 py-3 font-extrabold">Kelompok &amp; Pengampu</th>
                                    <th class="px-4 py-3 font-extrabold">Status Penilaian</th>
                                    <th class="px-4 py-3 font-extrabold text-right">Cetak Satuan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="visibleStudents.length === 0">
                                    <td colspan="6" class="px-4 py-12 text-center text-xs text-slate-400 italic">
                                        Belum ada siswa yang sesuai filter.
                                    </td>
                                </tr>
                                <tr v-for="st in visibleStudents" :key="st.student_id" class="hover:bg-emerald-50/20">
                                    <td class="px-4 py-3">
                                        <input
                                            type="checkbox"
                                            :value="st.student_id"
                                            v-model="selectedIds"
                                            class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-800">{{ st.full_name }}</div>
                                        <div class="text-xs text-slate-500">Kelas {{ st.classroom_name }} <span v-if="st.nisn">• NISN {{ st.nisn }}</span></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-[#1b4332] text-white">
                                            {{ st.level_name }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <div class="font-bold text-slate-700">{{ st.group_name }}</div>
                                        <div class="text-slate-500">{{ st.teacher_name }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span v-if="st.is_ready" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <CheckCircleIcon class="w-3.5 h-3.5" />
                                            Siap ({{ st.assessed_count }} indikator)
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500">
                                            <ClockIcon class="w-3.5 h-3.5" />
                                            Belum Dinilai
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button
                                                @click="openReportUrl('quran.reports.print', [st.student_id])"
                                                class="px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 inline-flex items-center gap-1"
                                            >
                                                <PrinterIcon class="w-3.5 h-3.5" />
                                                <span>Print</span>
                                            </button>
                                            <button
                                                @click="openReportUrl('quran.reports.pdf', [st.student_id])"
                                                class="px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 inline-flex items-center gap-1"
                                            >
                                                <DocumentArrowDownIcon class="w-3.5 h-3.5" />
                                                <span>PDF</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
