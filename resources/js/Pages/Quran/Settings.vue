<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import QuranNav from './Components/QuranNav.vue';
import {
    CheckIcon,
    TrashIcon,
    ArrowUpTrayIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    unit: Object,
    eligibleUnits: Array,
    canManage: Boolean,
    isGlobalAdmin: Boolean,
    setting: Object,
    unitLogoUrl: String,
});

const cfg = props.setting?.ttd_config || {};

const form = useForm({
    unit_id: props.unit.id,
    school_name: props.setting?.school_name || props.unit.name,
    school_address: props.setting?.school_address || '',
    city_name: props.setting?.city_name || 'Kraksaan',
    kepala_sekolah: props.setting?.kepala_sekolah || '',
    kepala_tpq: props.setting?.kepala_tpq || '',
    kepala_tpq_title: props.setting?.kepala_tpq_title || `Kepala TPQ Ummi ${props.unit.name}`,
    lock_input_nilai: Boolean(props.setting?.lock_input_nilai),
    lock_cetak_rapor: Boolean(props.setting?.lock_cetak_rapor),
    allow_teacher_promote: props.setting?.allow_teacher_promote !== false,
    report_date: props.setting?.report_date ? String(props.setting.report_date).slice(0, 10) : '',
    ttd_kepala_tpq_file: null,
    ttd_kepala_sekolah_file: null,
    remove_ttd_kepala_tpq: false,
    remove_ttd_kepala_sekolah: false,
    ttd_tpq_width: cfg.tpq_width ?? 100,
    ttd_tpq_offset_x: cfg.tpq_offset_x ?? 0,
    ttd_tpq_offset_y: cfg.tpq_offset_y ?? 0,
    ttd_kepsek_width: cfg.kepsek_width ?? 100,
    ttd_kepsek_offset_x: cfg.kepsek_offset_x ?? 0,
    ttd_kepsek_offset_y: cfg.kepsek_offset_y ?? 0,
});

const tpqPreview = ref(props.setting?.ttd_kepala_tpq_url || null);
const kepsekPreview = ref(props.setting?.ttd_kepala_sekolah_url || null);

const onTpqFile = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    form.ttd_kepala_tpq_file = file;
    form.remove_ttd_kepala_tpq = false;
    tpqPreview.value = URL.createObjectURL(file);
};

const onKepsekFile = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    form.ttd_kepala_sekolah_file = file;
    form.remove_ttd_kepala_sekolah = false;
    kepsekPreview.value = URL.createObjectURL(file);
};

const clearTpq = () => {
    form.ttd_kepala_tpq_file = null;
    form.remove_ttd_kepala_tpq = true;
    tpqPreview.value = null;
};

const clearKepsek = () => {
    form.ttd_kepala_sekolah_file = null;
    form.remove_ttd_kepala_sekolah = true;
    kepsekPreview.value = null;
};

const submit = () => {
    form.post(route('quran.settings.update'), {
        forceFormData: true,
        preserveScroll: true,
    });
};

const accessToggles = [
    {
        field: 'lock_input_nilai',
        title: 'Kunci Input Nilai',
        onDesc: 'Terkunci — Guru tidak dapat mengubah nilai.',
        offDesc: 'Terbuka — Guru dapat mengisi dan mengubah nilai.',
        dangerOn: true,
    },
    {
        field: 'lock_cetak_rapor',
        title: 'Kunci Cetak Rapor',
        onDesc: 'Terkunci — Hanya Koordinator & Admin yang dapat mencetak.',
        offDesc: 'Terbuka — Guru pengampu dapat mencetak rapor siswanya.',
        dangerOn: true,
    },
    {
        field: 'allow_teacher_promote',
        title: 'Izinkan Guru Menaikkan Jilid',
        onDesc: 'Aktif — Guru pengampu dapat mencatat kenaikan jilid muridnya.',
        offDesc: 'Nonaktif — Hanya Koordinator & Admin yang dapat menaikkan jilid.',
        dangerOn: false,
    },
];
</script>

<template>
    <Head title="Pengaturan — Program Al-Qur'an" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto py-1 sm:py-2 pb-20 space-y-5">
            <QuranNav
                active-tab="settings"
                page-title="Pengaturan Rapor & Periode"
                :unit="unit"
                :eligible-units="eligibleUnits"
                :can-manage="canManage"
                :is-global-admin="isGlobalAdmin"
            >
                <template #actions>
                    <button
                        type="submit"
                        form="quran-settings-form"
                        :disabled="form.processing"
                        class="h-8 px-3.5 rounded-lg text-xs font-medium text-white bg-[#00796B] hover:bg-[#00695C] disabled:opacity-50 shadow-[0_1px_2px_rgba(0,121,107,0.25)] transition-all active:scale-[0.98] inline-flex items-center gap-1.5"
                    >
                        <span v-if="form.processing" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                        <CheckIcon v-else class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}</span>
                    </button>
                </template>
            </QuranNav>

            <form id="quran-settings-form" @submit.prevent="submit" class="space-y-5">
                <!-- 1. Kontrol Periode & Hak Akses Guru (Real Toggle Switches) -->
                <section class="bg-white rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-slate-100">
                        <h2 class="text-sm font-semibold text-slate-900">Kontrol Periode &amp; Akses Guru</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Kunci fitur guru setelah masa penilaian selesai. Koordinator &amp; Admin tetap memiliki akses penuh.</p>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <div
                            v-for="item in accessToggles"
                            :key="item.field"
                            class="px-5 py-3.5 flex items-center justify-between gap-4"
                        >
                            <div class="min-w-0">
                                <div class="text-[13px] font-medium text-slate-900">{{ item.title }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    {{ form[item.field] ? item.onDesc : item.offDesc }}
                                </div>
                            </div>

                            <button
                                type="button"
                                role="switch"
                                :aria-checked="form[item.field]"
                                @click="form[item.field] = !form[item.field]"
                                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-[cubic-bezier(0.16,1,0.3,1)] focus:outline-none focus:ring-2 focus:ring-[#00796B]/30 focus:ring-offset-1"
                                :class="form[item.field] ? (item.dangerOn ? 'bg-rose-600' : 'bg-[#00796B]') : 'bg-slate-200'"
                            >
                                <span
                                    class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow-xs transform transition-transform duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]"
                                    :class="form[item.field] ? 'translate-x-4' : 'translate-x-0'"
                                />
                            </button>
                        </div>
                    </div>
                </section>

                <!-- 2. Kop Rapor & Pejabat Penandatangan -->
                <section class="bg-white rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-slate-100">
                        <h2 class="text-sm font-semibold text-slate-900">Kop Rapor &amp; Identitas Penandatangan</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Informasi ini dicetak pada bagian header dan tanda tangan rapor F4.</p>
                    </div>

                    <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Nama Sekolah pada Kop Rapor <span class="text-rose-500">*</span></label>
                            <input v-model="form.school_name" required type="text" class="w-full h-9 rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Kota Tanggal Rapor</label>
                            <input v-model="form.city_name" type="text" placeholder="Kraksaan / Probolinggo" class="w-full h-9 rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Alamat Sekolah</label>
                            <input v-model="form.school_address" type="text" class="w-full h-9 rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Jabatan Penandatangan TPQ / Ummi</label>
                            <input v-model="form.kepala_tpq_title" type="text" placeholder="Kepala TPQ Ummi SD Namira" class="w-full h-9 rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Nama Kepala TPQ / Koordinator Ummi</label>
                            <input v-model="form.kepala_tpq" type="text" placeholder="Nur Halimah" class="w-full h-9 rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Nama Kepala Sekolah</label>
                            <input v-model="form.kepala_sekolah" type="text" placeholder="Astutik, S.Pd.I" class="w-full h-9 rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Tanggal Cetak Rapor (Kosong = Hari Ini)</label>
                            <input v-model="form.report_date" type="date" class="w-full h-9 rounded-lg border-slate-200 text-sm tabular-nums focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                        </div>
                    </div>
                </section>

                <!-- 3. Tanda Tangan Digital + Live Preview -->
                <section class="bg-white rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-slate-100">
                        <h2 class="text-sm font-semibold text-slate-900">Tanda Tangan Digital</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Unggah gambar PNG transparan dan sesuaikan ukuran atau posisinya.</p>
                    </div>

                    <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- TTD Kepala TPQ -->
                        <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-slate-800">TTD Kepala TPQ / Koordinator</span>
                                <div class="flex items-center gap-2">
                                    <label class="h-7 px-2.5 rounded-lg bg-white border border-slate-200 text-[11px] font-medium text-slate-700 hover:bg-slate-50 cursor-pointer inline-flex items-center gap-1 transition-colors">
                                        <ArrowUpTrayIcon class="w-3.5 h-3.5 text-slate-400" />
                                        <span>Unggah</span>
                                        <input type="file" @change="onTpqFile" accept="image/png,image/jpeg,image/webp" class="sr-only" />
                                    </label>
                                    <button v-if="tpqPreview" type="button" @click="clearTpq" class="h-7 px-2 rounded-lg text-[11px] font-medium text-rose-600 hover:bg-rose-50 inline-flex items-center gap-1 transition-colors">
                                        <TrashIcon class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg border border-slate-200/90 p-4 text-center text-xs relative overflow-hidden">
                                <p class="text-slate-500">Mengetahui,</p>
                                <p class="text-slate-700 font-medium">{{ form.kepala_tpq_title }}</p>
                                <div class="h-16 relative">
                                    <img
                                        v-if="tpqPreview"
                                        :src="tpqPreview"
                                        :style="{ width: `${form.ttd_tpq_width}px`, transform: `translate(calc(-50% + ${form.ttd_tpq_offset_x}px), ${form.ttd_tpq_offset_y}px)` }"
                                        class="absolute bottom-0 left-1/2 max-h-20 object-contain pointer-events-none"
                                    />
                                </div>
                                <div class="font-semibold text-slate-900 border-t border-slate-800 pt-1 w-44 mx-auto relative z-10">{{ form.kepala_tpq || '........................' }}</div>
                            </div>

                            <div class="grid grid-cols-3 gap-2.5 text-[11px] text-slate-500 tabular-nums">
                                <div><label class="block mb-1">Lebar {{ form.ttd_tpq_width }}px</label><input v-model="form.ttd_tpq_width" type="range" min="50" max="200" class="w-full accent-[#00796B]" /></div>
                                <div><label class="block mb-1">X {{ form.ttd_tpq_offset_x }}</label><input v-model="form.ttd_tpq_offset_x" type="range" min="-50" max="50" class="w-full accent-[#00796B]" /></div>
                                <div><label class="block mb-1">Y {{ form.ttd_tpq_offset_y }}</label><input v-model="form.ttd_tpq_offset_y" type="range" min="-40" max="40" class="w-full accent-[#00796B]" /></div>
                            </div>
                        </div>

                        <!-- TTD Kepala Sekolah -->
                        <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-slate-800">TTD Kepala Sekolah</span>
                                <div class="flex items-center gap-2">
                                    <label class="h-7 px-2.5 rounded-lg bg-white border border-slate-200 text-[11px] font-medium text-slate-700 hover:bg-slate-50 cursor-pointer inline-flex items-center gap-1 transition-colors">
                                        <ArrowUpTrayIcon class="w-3.5 h-3.5 text-slate-400" />
                                        <span>Unggah</span>
                                        <input type="file" @change="onKepsekFile" accept="image/png,image/jpeg,image/webp" class="sr-only" />
                                    </label>
                                    <button v-if="kepsekPreview" type="button" @click="clearKepsek" class="h-7 px-2 rounded-lg text-[11px] font-medium text-rose-600 hover:bg-rose-50 inline-flex items-center gap-1 transition-colors">
                                        <TrashIcon class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg border border-slate-200/90 p-4 text-center text-xs relative overflow-hidden">
                                <p class="text-slate-500">Mengetahui,</p>
                                <p class="text-slate-700 font-medium">Kepala Sekolah</p>
                                <div class="h-16 relative">
                                    <img
                                        v-if="kepsekPreview"
                                        :src="kepsekPreview"
                                        :style="{ width: `${form.ttd_kepsek_width}px`, transform: `translate(calc(-50% + ${form.ttd_kepsek_offset_x}px), ${form.ttd_kepsek_offset_y}px)` }"
                                        class="absolute bottom-0 left-1/2 max-h-20 object-contain pointer-events-none"
                                    />
                                </div>
                                <div class="font-semibold text-slate-900 border-t border-slate-800 pt-1 w-44 mx-auto relative z-10">{{ form.kepala_sekolah || '........................' }}</div>
                            </div>

                            <div class="grid grid-cols-3 gap-2.5 text-[11px] text-slate-500 tabular-nums">
                                <div><label class="block mb-1">Lebar {{ form.ttd_kepsek_width }}px</label><input v-model="form.ttd_kepsek_width" type="range" min="50" max="200" class="w-full accent-[#00796B]" /></div>
                                <div><label class="block mb-1">X {{ form.ttd_kepsek_offset_x }}</label><input v-model="form.ttd_kepsek_offset_x" type="range" min="-50" max="50" class="w-full accent-[#00796B]" /></div>
                                <div><label class="block mb-1">Y {{ form.ttd_kepsek_offset_y }}</label><input v-model="form.ttd_kepsek_offset_y" type="range" min="-40" max="40" class="w-full accent-[#00796B]" /></div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="h-9 px-5 rounded-lg bg-[#00796B] hover:bg-[#00695C] text-white text-xs font-medium shadow-[0_1px_2px_rgba(0,121,107,0.25)] disabled:opacity-50 transition-all active:scale-[0.98] inline-flex items-center gap-1.5"
                    >
                        <span v-if="form.processing" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                        <CheckIcon v-else class="w-4 h-4 stroke-[2.5]" />
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
