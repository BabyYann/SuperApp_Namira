<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import QuranNav from './Components/QuranNav.vue';
import axios from 'axios';
import {
    MagnifyingGlassIcon,
    ArrowUpCircleIcon,
    ClockIcon,
    XMarkIcon,
    ArrowsRightLeftIcon,
    CheckBadgeIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    unit: Object,
    eligibleUnits: Array,
    academicYears: Array,
    activeYear: Object,
    canManage: Boolean,
    isGlobalAdmin: Boolean,
    levels: Array,
    groups: Array,
    classrooms: Array,
    students: Array,
    filters: Object,
});

const classroomFilter = ref(props.filters?.classroom_id ?? 'all');
const levelFilter = ref(props.filters?.level_id ?? 'all');
const groupFilter = ref(props.filters?.group_id ?? 'all');
const searchQuery = ref(props.filters?.search ?? '');

const applyFilters = () => {
    router.get(route('quran.students.index'), {
        unit_id: props.isGlobalAdmin ? props.unit.id : undefined,
        academic_year_id: props.activeYear.id,
        classroom_id: classroomFilter.value,
        level_id: levelFilter.value,
        group_id: groupFilter.value,
        search: searchQuery.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

// ==================== PROMOTE / TRANSFER MODAL ====================
const showPromoteModal = ref(false);
const targetStudent = ref(null);
const selectedTargetLevelId = ref('all');

const promoteForm = useForm({
    unit_id: props.unit.id,
    academic_year_id: props.activeYear.id,
    target_group_id: '',
    change_type: 'promoted',
    changed_at: new Date().toISOString().slice(0, 10),
    exam_score: '',
    notes: '',
});

const openPromoteModal = (student) => {
    targetStudent.value = student;
    promoteForm.reset();
    promoteForm.unit_id = props.unit.id;
    promoteForm.academic_year_id = props.activeYear.id;
    promoteForm.changed_at = new Date().toISOString().slice(0, 10);

    if (!student.level_id) {
        promoteForm.change_type = 'initial';
        selectedTargetLevelId.value = 'all';
    } else {
        promoteForm.change_type = 'promoted';
        // Auto-suggest next level by sort_order
        const sortedLevels = [...props.levels].sort((a, b) => a.sort_order - b.sort_order);
        const curIdx = sortedLevels.findIndex(l => l.id === student.level_id);
        const nextLevel = curIdx >= 0 && curIdx + 1 < sortedLevels.length ? sortedLevels[curIdx + 1] : null;
        selectedTargetLevelId.value = nextLevel ? nextLevel.id : 'all';
    }

    // Auto-select first matching group if only 1
    const candidates = availableTargetGroups.value;
    promoteForm.target_group_id = candidates.length === 1 ? candidates[0].id : '';
    showPromoteModal.value = true;
};

const availableTargetGroups = computed(() => {
    return (props.groups || []).filter(g => {
        if (targetStudent.value && g.id === targetStudent.value.group_id) return false;
        if (selectedTargetLevelId.value !== 'all' && String(g.level_id) !== String(selectedTargetLevelId.value)) return false;
        return true;
    });
});

const onChangeTypeSwitch = (type) => {
    promoteForm.change_type = type;
    if (type === 'transferred' && targetStudent.value?.level_id) {
        selectedTargetLevelId.value = targetStudent.value.level_id;
    } else if (type === 'promoted' && targetStudent.value?.level_id) {
        const sortedLevels = [...props.levels].sort((a, b) => a.sort_order - b.sort_order);
        const curIdx = sortedLevels.findIndex(l => l.id === targetStudent.value.level_id);
        const nextLevel = curIdx >= 0 && curIdx + 1 < sortedLevels.length ? sortedLevels[curIdx + 1] : null;
        selectedTargetLevelId.value = nextLevel ? nextLevel.id : 'all';
    }
    promoteForm.target_group_id = '';
};

const submitPromote = () => {
    if (!targetStudent.value) return;
    promoteForm.post(route('quran.students.promote', targetStudent.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPromoteModal.value = false;
            targetStudent.value = null;
        },
    });
};

// ==================== HISTORY DRAWER / MODAL ====================
const showHistoryModal = ref(false);
const loadingHistory = ref(false);
const historyStudent = ref(null);
const historyRows = ref([]);

const openHistory = async (student) => {
    showHistoryModal.value = true;
    loadingHistory.value = true;
    historyStudent.value = student;
    historyRows.value = [];

    try {
        const res = await axios.get(route('quran.students.history', student.id), {
            params: { unit_id: props.isGlobalAdmin ? props.unit.id : undefined },
        });
        historyRows.value = res.data.histories || [];
    } catch (err) {
        console.error('Failed to load quran history:', err);
    } finally {
        loadingHistory.value = false;
    }
};

const changeTypeBadge = (type) => {
    if (type === 'promoted') return { text: 'Naik Jilid', cls: 'bg-emerald-100 text-emerald-800 border-emerald-200' };
    if (type === 'transferred') return { text: 'Pindah Kelompok', cls: 'bg-amber-100 text-amber-800 border-amber-200' };
    return { text: 'Penempatan Awal', cls: 'bg-blue-100 text-blue-800 border-blue-200' };
};
</script>

<template>
    <Head title="Tracking & Naik Jilid - Program Al-Qur'an" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <QuranNav
                active-tab="students"
                :unit="unit"
                :eligible-units="eligibleUnits"
                :academic-years="academicYears"
                :active-year="activeYear"
                :can-manage="canManage"
                :is-global-admin="isGlobalAdmin"
            />

            <!-- Filter Bar -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm mb-5 flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[200px]">
                    <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="searchQuery"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Cari nama siswa / NIS / NISN..."
                        class="w-full pl-9 pr-4 py-2 text-sm border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500"
                    />
                </div>
                <select v-model="classroomFilter" @change="applyFilters" class="py-2 px-3 text-sm border-slate-200 rounded-xl text-slate-700 font-medium">
                    <option value="all">Semua Kelas Formal</option>
                    <option v-for="c in classrooms" :key="c.id" :value="c.id">Kelas {{ c.name }}</option>
                </select>
                <select v-model="levelFilter" @change="applyFilters" class="py-2 px-3 text-sm border-slate-200 rounded-xl text-slate-700 font-medium">
                    <option value="all">Semua Jilid</option>
                    <option value="unplotted">Belum Diplot</option>
                    <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                </select>
                <select v-model="groupFilter" @change="applyFilters" class="py-2 px-3 text-sm border-slate-200 rounded-xl text-slate-700 font-medium">
                    <option value="all">Semua Kelompok</option>
                    <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
                </select>
                <button @click="applyFilters" class="px-4 py-2 rounded-xl bg-slate-800 text-white text-xs font-bold hover:bg-slate-900">
                    Cari
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <div class="text-xs font-extrabold uppercase tracking-wider text-slate-500">
                        Daftar Siswa &amp; Tracking Jilid ({{ students.length }} Siswa)
                    </div>
                    <div class="text-[11px] text-slate-400">Siswa dapat naik jilid beberapa kali dalam satu semester</div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-[11px] uppercase text-slate-500 bg-slate-50/50 border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-3 font-extrabold">Siswa &amp; Kelas</th>
                                <th class="px-4 py-3 font-extrabold">Jilid Saat Ini</th>
                                <th class="px-4 py-3 font-extrabold">Kelompok &amp; Sesi</th>
                                <th class="px-4 py-3 font-extrabold">Pengampu</th>
                                <th class="px-4 py-3 font-extrabold text-center">Progres Sem. Ini</th>
                                <th class="px-4 py-3 font-extrabold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="students.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-xs text-slate-400 italic">
                                    Tidak ada data siswa sesuai filter.
                                </td>
                            </tr>
                            <tr v-for="st in students" :key="st.id" class="hover:bg-emerald-50/20 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-800">{{ st.full_name }}</div>
                                    <div class="text-xs text-slate-500">Kelas {{ st.classroom_name }} <span v-if="st.nis">• {{ st.nis }}</span></div>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="px-2.5 py-1 rounded-full text-xs font-extrabold"
                                        :class="st.level_id ? 'bg-[#1b4332] text-white' : 'bg-slate-100 text-slate-500'"
                                    >
                                        {{ st.level_name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-700 text-xs">{{ st.group_name }}</div>
                                    <div v-if="st.session_name !== '-'" class="text-[11px] text-amber-700 font-medium">{{ st.session_name }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs font-semibold text-slate-700">
                                    {{ st.teacher_name }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="st.promotions_count > 0" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <CheckBadgeIcon class="w-3.5 h-3.5" />
                                        Naik {{ st.promotions_count }}×
                                    </span>
                                    <span v-else class="text-xs text-slate-400">-</span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            v-if="st.can_promote"
                                            @click="openPromoteModal(st)"
                                            class="px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 transition inline-flex items-center gap-1"
                                        >
                                            <ArrowUpCircleIcon class="w-3.5 h-3.5" />
                                            <span>{{ st.level_id ? 'Naik / Pindah' : 'Plot Kelompok' }}</span>
                                        </button>
                                        <button
                                            @click="openHistory(st)"
                                            class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition inline-flex items-center gap-1"
                                        >
                                            <ClockIcon class="w-3.5 h-3.5" />
                                            <span>Riwayat</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal: Naik Jilid / Pindah Kelompok -->
        <Teleport to="body">
            <div v-if="showPromoteModal && targetStudent" class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-y-auto">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showPromoteModal = false"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div>
                            <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Tracking Perjalanan Al-Qur'an</div>
                            <h3 class="text-lg font-extrabold text-slate-800">{{ targetStudent.full_name }}</h3>
                        </div>
                        <button @click="showPromoteModal = false" class="p-1 text-slate-400 hover:text-slate-600"><XMarkIcon class="w-5 h-5" /></button>
                    </div>

                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 mb-4 text-xs flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <span class="text-slate-400">Posisi Saat Ini:</span>
                            <strong class="ml-1 text-slate-800">{{ targetStudent.level_name }}</strong>
                            <span v-if="targetStudent.group_name !== '-'" class="text-slate-500"> ({{ targetStudent.group_name }})</span>
                        </div>
                        <div class="text-slate-500">Pengampu: <strong class="text-slate-700">{{ targetStudent.teacher_name }}</strong></div>
                    </div>

                    <form @submit.prevent="submitPromote" class="space-y-4">
                        <!-- Mode switch -->
                        <div v-if="targetStudent.level_id" class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="onChangeTypeSwitch('promoted')"
                                class="p-2.5 rounded-xl border text-xs font-bold flex items-center justify-center gap-1.5 transition"
                                :class="promoteForm.change_type === 'promoted' ? 'bg-emerald-50 border-emerald-400 text-emerald-800' : 'bg-white border-slate-200 text-slate-600'"
                            >
                                <ArrowUpCircleIcon class="w-4 h-4" />
                                <span>Naik Jilid (Lulus)</span>
                            </button>
                            <button
                                type="button"
                                @click="onChangeTypeSwitch('transferred')"
                                class="p-2.5 rounded-xl border text-xs font-bold flex items-center justify-center gap-1.5 transition"
                                :class="promoteForm.change_type === 'transferred' ? 'bg-amber-50 border-amber-400 text-amber-800' : 'bg-white border-slate-200 text-slate-600'"
                            >
                                <ArrowsRightLeftIcon class="w-4 h-4" />
                                <span>Pindah Kelompok/Guru</span>
                            </button>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Filter Jilid Tujuan</label>
                            <select v-model="selectedTargetLevelId" @change="promoteForm.target_group_id = ''" class="w-full rounded-xl border-slate-200 text-sm">
                                <option value="all">-- Tampilkan Semua Jilid --</option>
                                <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Kelompok &amp; Guru Pengampu Tujuan *</label>
                            <select v-model="promoteForm.target_group_id" required class="w-full rounded-xl border-slate-200 text-sm">
                                <option value="">-- Pilih Kelompok Tujuan --</option>
                                <option v-for="g in availableTargetGroups" :key="g.id" :value="g.id">
                                    [{{ g.level_name }}] {{ g.name }} — {{ g.teacher_name }} {{ g.session_name ? `(${g.session_name})` : '' }}
                                </option>
                            </select>
                            <p v-if="availableTargetGroups.length === 0" class="text-xs text-amber-600 mt-1">
                                Belum ada kelompok pada jilid tujuan ini. Buat kelompoknya terlebih dahulu di tab Kelompok &amp; Sesi, atau ubah filter jilid.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Kenaikan / Pindah *</label>
                                <input v-model="promoteForm.changed_at" required type="date" class="w-full rounded-xl border-slate-200 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nilai Tes / Munaqosyah</label>
                                <input v-model="promoteForm.exam_score" type="text" placeholder="Opsional, misal: A / 92" class="w-full rounded-xl border-slate-200 text-sm" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan (Opsional)</label>
                            <textarea v-model="promoteForm.notes" rows="2" placeholder="Misal: Lulus tes naik jilid dengan lancar" class="w-full rounded-xl border-slate-200 text-sm"></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button type="button" @click="showPromoteModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</button>
                            <button type="submit" :disabled="promoteForm.processing || !promoteForm.target_group_id" class="px-5 py-2 rounded-xl text-xs font-bold bg-[#1b4332] text-white hover:bg-[#143325] disabled:opacity-50">
                                Simpan &amp; Catat Riwayat
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal: Timeline Riwayat Jilid -->
        <Teleport to="body">
            <div v-if="showHistoryModal && historyStudent" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showHistoryModal = false"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl max-w-xl w-full max-h-[85vh] flex flex-col z-10 overflow-hidden">
                    <div class="px-6 py-4 bg-[#1b4332] text-white flex items-center justify-between">
                        <div>
                            <div class="text-[11px] font-bold text-amber-300 uppercase tracking-wider">Timeline Riwayat Kenaikan Jilid</div>
                            <h3 class="text-base font-extrabold">{{ historyStudent.full_name }} • Kelas {{ historyStudent.classroom_name }}</h3>
                        </div>
                        <button @click="showHistoryModal = false" class="p-1.5 rounded-lg bg-white/10 hover:bg-white/20"><XMarkIcon class="w-5 h-5" /></button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6">
                        <div v-if="loadingHistory" class="py-10 text-center text-xs text-slate-400">Memuat riwayat...</div>
                        <div v-else-if="historyRows.length === 0" class="py-10 text-center text-xs text-slate-400 italic">
                            Belum ada catatan perpindahan atau kenaikan jilid untuk siswa ini.
                        </div>
                        <ol v-else class="relative border-l-2 border-emerald-200 ml-3 space-y-5">
                            <li v-for="h in historyRows" :key="h.id" class="ml-5">
                                <span class="absolute -left-[9px] w-4 h-4 rounded-full bg-emerald-600 border-2 border-white shadow"></span>
                                <div class="bg-slate-50 rounded-2xl p-3.5 border border-slate-200/70">
                                    <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold border" :class="changeTypeBadge(h.change_type).cls">
                                            {{ changeTypeBadge(h.change_type).text }}
                                        </span>
                                        <span class="text-[11px] font-bold text-slate-500">{{ h.changed_at }} • {{ h.academic_year }}</span>
                                    </div>
                                    <div class="text-sm font-extrabold text-slate-800">
                                        <template v-if="h.from_level">
                                            <span class="text-slate-500">{{ h.from_level }}</span>
                                            <span class="mx-1.5 text-emerald-600">→</span>
                                            <span class="text-emerald-800">{{ h.to_level }}</span>
                                        </template>
                                        <template v-else>
                                            <span class="text-emerald-800">{{ h.to_level }}</span>
                                        </template>
                                    </div>
                                    <div class="text-xs text-slate-600 mt-1 space-y-0.5">
                                        <div v-if="h.to_group">Kelompok baru: <strong>{{ h.to_group }}</strong> <span v-if="h.to_teacher">({{ h.to_teacher }})</span></div>
                                        <div v-if="h.from_group" class="text-slate-400">Sebelumnya: {{ h.from_group }} <span v-if="h.from_teacher">({{ h.from_teacher }})</span></div>
                                        <div v-if="h.exam_score" class="text-emerald-700 font-bold">Nilai Tes: {{ h.exam_score }}</div>
                                        <div v-if="h.notes" class="italic text-slate-500">"{{ h.notes }}"</div>
                                        <div class="text-[10px] text-slate-400 pt-1">Dicatat oleh: {{ h.recorded_by }}</div>
                                    </div>
                                </div>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
