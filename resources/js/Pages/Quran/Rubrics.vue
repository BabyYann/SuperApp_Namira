<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import QuranNav from './Components/QuranNav.vue';
import {
    PlusIcon,
    TrashIcon,
    PencilSquareIcon,
    CheckIcon,
    XMarkIcon,
    BookOpenIcon,
    AcademicCapIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    unit: Object,
    eligibleUnits: Array,
    canManage: Boolean,
    isGlobalAdmin: Boolean,
    levels: Array,
    categories: Object,
});

const selectedLevelId = ref(props.levels?.[0]?.id || null);

const selectedLevel = computed(() => {
    return (props.levels || []).find(l => l.id === selectedLevelId.value) || props.levels?.[0] || null;
});

const itemsByCategory = (cat) => {
    if (!selectedLevel.value) return [];
    return (selectedLevel.value.rubric_items || []).filter(i => i.category === cat);
};

// Add Level modal
const showLevelModal = ref(false);
const levelForm = useForm({
    unit_id: props.unit.id,
    name: '',
    code: '',
    sort_order: (props.levels?.length || 0) + 1,
});

const openLevelModal = () => {
    levelForm.reset();
    levelForm.unit_id = props.unit.id;
    levelForm.sort_order = (props.levels?.length || 0) + 1;
    showLevelModal.value = true;
};

const submitLevel = () => {
    levelForm.post(route('quran.rubrics.levels.store'), {
        preserveScroll: true,
        onSuccess: () => { showLevelModal.value = false; },
    });
};

// Add Rubric Item inline per category
const newTitles = ref({
    materi: '',
    surah_pendek: '',
    doa_harian: '',
    hadist: '',
});

const addItem = (category) => {
    const title = (newTitles.value[category] || '').trim();
    if (!title || !selectedLevel.value) return;

    router.post(route('quran.rubrics.items.store'), {
        unit_id: props.unit.id,
        level_id: selectedLevel.value.id,
        category,
        title,
    }, {
        preserveScroll: true,
        onSuccess: () => { newTitles.value[category] = ''; },
    });
};

// Inline edit rubric item
const editingItemId = ref(null);
const editingTitle = ref('');

const startEditItem = (item) => {
    editingItemId.value = item.id;
    editingTitle.value = item.title;
};

const saveEditItem = (item) => {
    const title = editingTitle.value.trim();
    if (!title) return;
    router.put(route('quran.rubrics.items.update', item.id), {
        unit_id: props.unit.id,
        title,
    }, {
        preserveScroll: true,
        onSuccess: () => { editingItemId.value = null; },
    });
};

const deleteItem = (item) => {
    if (!confirm(`Hapus item rubrik "${item.title}"?`)) return;
    router.delete(route('quran.rubrics.items.destroy', item.id), {
        data: { unit_id: props.unit.id },
        preserveScroll: true,
    });
};

const sections = [
    { key: 'materi', title: 'Materi Pokok', subtitle: 'Standar penilaian bacaan / teori', accent: 'emerald', dot: 'bg-emerald-600' },
    { key: 'surah_pendek', title: 'Surah Pendek', subtitle: 'Hafalan surat Al-Qur\'an', accent: 'amber', dot: 'bg-amber-500' },
    { key: 'doa_harian', title: 'Doa Harian', subtitle: 'Hafalan doa sehari-hari', accent: 'blue', dot: 'bg-blue-500' },
    { key: 'hadist', title: 'Hadist', subtitle: 'Hafalan hadist pilihan', accent: 'purple', dot: 'bg-purple-500' },
];
</script>

<template>
    <Head title="Rubrik Materi Ummi - Program Al-Qur'an" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <QuranNav
                active-tab="rubrics"
                :unit="unit"
                :eligible-units="eligibleUnits"
                :can-manage="canManage"
                :is-global-admin="isGlobalAdmin"
            />

            <!-- Standar Penilaian Banner -->
            <div class="bg-emerald-50/70 border border-emerald-200/80 rounded-3xl p-4 sm:p-5 mb-6">
                <div class="text-xs font-extrabold uppercase tracking-wider text-emerald-800 mb-3">Petunjuk Penilaian Standar Metode Ummi</div>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="bg-white rounded-2xl p-3 border border-emerald-100 flex items-center gap-3">
                        <span class="w-9 h-9 rounded-xl bg-emerald-600 text-white font-black flex items-center justify-center shrink-0">A</span>
                        <div class="text-xs leading-tight"><strong class="text-slate-800 block">91 – 95</strong><span class="text-slate-500">Sangat Lancar / Tartil</span></div>
                    </div>
                    <div class="bg-white rounded-2xl p-3 border border-blue-100 flex items-center gap-3">
                        <span class="w-9 h-9 rounded-xl bg-blue-600 text-white font-black flex items-center justify-center shrink-0">B</span>
                        <div class="text-xs leading-tight"><strong class="text-slate-800 block">81 – 90</strong><span class="text-slate-500">Lancar / Cukup Tartil</span></div>
                    </div>
                    <div class="bg-white rounded-2xl p-3 border border-amber-100 flex items-center gap-3">
                        <span class="w-9 h-9 rounded-xl bg-amber-500 text-white font-black flex items-center justify-center shrink-0">C</span>
                        <div class="text-xs leading-tight"><strong class="text-slate-800 block">71 – 80</strong><span class="text-slate-500">Kurang Lancar</span></div>
                    </div>
                    <div class="bg-white rounded-2xl p-3 border border-red-100 flex items-center gap-3">
                        <span class="w-9 h-9 rounded-xl bg-red-600 text-white font-black flex items-center justify-center shrink-0">D</span>
                        <div class="text-xs leading-tight"><strong class="text-slate-800 block">65 – 70</strong><span class="text-slate-500">Belum Lancar / Ulang</span></div>
                    </div>
                </div>
            </div>

            <!-- Level Tabs + Add Level -->
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="lvl in levels"
                        :key="lvl.id"
                        @click="selectedLevelId = lvl.id"
                        class="px-4 py-2 rounded-xl text-xs font-extrabold border transition"
                        :class="selectedLevel && selectedLevel.id === lvl.id
                            ? 'bg-[#1b4332] text-white border-[#1b4332] shadow-md'
                            : 'bg-white text-slate-600 border-slate-200 hover:bg-emerald-50'"
                    >
                        {{ lvl.name }}
                    </button>
                </div>

                <button
                    v-if="canManage"
                    @click="openLevelModal"
                    class="px-4 py-2 rounded-xl bg-slate-800 text-white text-xs font-bold hover:bg-slate-900 inline-flex items-center gap-1.5"
                >
                    <PlusIcon class="w-4 h-4" />
                    <span>Tambah Jilid / Tingkat</span>
                </button>
            </div>

            <!-- 4 Rubric Category Cards -->
            <div v-if="selectedLevel" class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div
                    v-for="sec in sections"
                    :key="sec.key"
                    class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm flex flex-col"
                >
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full" :class="sec.dot"></span>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800">{{ sec.title }} — {{ selectedLevel.name }}</h3>
                                <p class="text-[11px] text-slate-500">{{ sec.subtitle }}</p>
                            </div>
                        </div>
                        <span class="text-xs font-black text-slate-400">{{ itemsByCategory(sec.key).length }} item</span>
                    </div>

                    <ul class="space-y-2 flex-1 mb-4">
                        <li
                            v-for="(item, idx) in itemsByCategory(sec.key)"
                            :key="item.id"
                            class="flex items-center justify-between gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-100 group"
                        >
                            <div class="flex items-center gap-2.5 flex-1 min-w-0">
                                <span class="text-[11px] font-bold text-slate-400 w-5 text-right shrink-0">{{ idx + 1 }}.</span>
                                <template v-if="editingItemId === item.id">
                                    <input
                                        v-model="editingTitle"
                                        @keyup.enter="saveEditItem(item)"
                                        type="text"
                                        class="flex-1 text-xs rounded-lg border-emerald-300 py-1 px-2"
                                    />
                                    <button @click="saveEditItem(item)" class="p-1 text-emerald-600 hover:bg-emerald-50 rounded"><CheckIcon class="w-4 h-4" /></button>
                                    <button @click="editingItemId = null" class="p-1 text-slate-400 hover:bg-slate-200 rounded"><XMarkIcon class="w-4 h-4" /></button>
                                </template>
                                <span v-else class="text-xs font-semibold text-slate-700 truncate">{{ item.title }}</span>
                            </div>
                            <div v-if="canManage && editingItemId !== item.id" class="flex items-center gap-1 shrink-0">
                                <button @click="startEditItem(item)" class="p-1 text-slate-400 hover:text-amber-600 rounded" title="Edit">
                                    <PencilSquareIcon class="w-4 h-4" />
                                </button>
                                <button @click="deleteItem(item)" class="p-1 text-slate-400 hover:text-red-600 rounded" title="Hapus">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </li>
                        <li v-if="itemsByCategory(sec.key).length === 0" class="text-center py-4 text-xs text-slate-400 italic">
                            Belum ada item {{ sec.title.toLowerCase() }}.
                        </li>
                    </ul>

                    <!-- Quick Add Input -->
                    <form v-if="canManage" @submit.prevent="addItem(sec.key)" class="flex gap-2 pt-3 border-t border-slate-100">
                        <input
                            v-model="newTitles[sec.key]"
                            type="text"
                            :placeholder="`Tambah ${sec.title.toLowerCase()} baru...`"
                            class="flex-1 text-xs rounded-xl border-slate-200 py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500"
                        />
                        <button type="submit" class="px-3.5 py-2 rounded-xl bg-[#1b4332] text-white text-xs font-bold hover:bg-[#143325] inline-flex items-center gap-1 shrink-0">
                            <PlusIcon class="w-4 h-4" />
                            <span>Tambah</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal: Tambah Jilid -->
        <Teleport to="body">
            <div v-if="showLevelModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showLevelModal = false"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-base font-extrabold text-slate-800">Tambah Jilid / Tingkat Baru</h3>
                        <button @click="showLevelModal = false" class="p-1 text-slate-400"><XMarkIcon class="w-5 h-5" /></button>
                    </div>
                    <form @submit.prevent="submitLevel" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Jilid / Tingkat *</label>
                            <input v-model="levelForm.name" required type="text" placeholder="Contoh: Pra-TK / Tahsin / Jilid 1" class="w-full rounded-xl border-slate-200 text-sm" />
                            <p v-if="levelForm.errors.name" class="text-xs text-red-600 mt-1">{{ levelForm.errors.name }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kode Singkat</label>
                                <input v-model="levelForm.code" type="text" placeholder="PTK / J1" class="w-full rounded-xl border-slate-200 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Urutan Jenjang</label>
                                <input v-model="levelForm.sort_order" type="number" min="1" max="99" class="w-full rounded-xl border-slate-200 text-sm" />
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button type="button" @click="showLevelModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</button>
                            <button type="submit" :disabled="levelForm.processing" class="px-5 py-2 rounded-xl text-xs font-bold bg-[#1b4332] text-white">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
