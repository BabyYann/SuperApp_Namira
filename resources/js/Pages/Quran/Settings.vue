<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import QuranNav from './Components/QuranNav.vue';
import {
    CheckCircleIcon,
    LockClosedIcon,
    PhotoIcon,
    TrashIcon,
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
</script>

<template>
    <Head title="Pengaturan Rapor Ummi - Program Al-Qur'an" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
            <QuranNav
                active-tab="settings"
                :unit="unit"
                :eligible-units="eligibleUnits"
                :can-manage="canManage"
                :is-global-admin="isGlobalAdmin"
            />

            <form @submit.prevent="submit" class="space-y-6">
                <!-- 1. Kunci Periode & Hak Akses Guru -->
                <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center gap-2.5 pb-3 mb-4 border-b border-slate-100">
                        <LockClosedIcon class="w-5 h-5 text-amber-600" />
                        <div>
                            <h2 class="text-base font-extrabold text-slate-800">Kontrol Periode &amp; Akses Guru Al-Qur'an</h2>
                            <p class="text-xs text-slate-500">Kunci fitur untuk guru saat masa penilaian selesai (Koordinator &amp; Admin tetap bisa mengakses).</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <label class="flex items-start gap-3 p-4 rounded-2xl border cursor-pointer transition"
                               :class="form.lock_input_nilai ? 'bg-red-50/70 border-red-200' : 'bg-emerald-50/50 border-emerald-200'">
                            <input type="checkbox" v-model="form.lock_input_nilai" class="mt-0.5 rounded text-red-600 focus:ring-red-500" />
                            <div>
                                <div class="text-xs font-extrabold text-slate-800">Kunci Input Nilai</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    {{ form.lock_input_nilai ? 'Terkunci: Guru tidak bisa ubah nilai' : 'Terbuka: Guru dapat mengisi nilai' }}
                                </div>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-4 rounded-2xl border cursor-pointer transition"
                               :class="form.lock_cetak_rapor ? 'bg-red-50/70 border-red-200' : 'bg-emerald-50/50 border-emerald-200'">
                            <input type="checkbox" v-model="form.lock_cetak_rapor" class="mt-0.5 rounded text-red-600 focus:ring-red-500" />
                            <div>
                                <div class="text-xs font-extrabold text-slate-800">Kunci Cetak Rapor</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    {{ form.lock_cetak_rapor ? 'Terkunci: Hanya Koordinator/Admin' : 'Terbuka: Guru dapat mencetak rapor' }}
                                </div>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-4 rounded-2xl border cursor-pointer transition"
                               :class="form.allow_teacher_promote ? 'bg-emerald-50/50 border-emerald-200' : 'bg-slate-50 border-slate-200'">
                            <input type="checkbox" v-model="form.allow_teacher_promote" class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500" />
                            <div>
                                <div class="text-xs font-extrabold text-slate-800">Izinkan Guru Naikkan Jilid</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    {{ form.allow_teacher_promote ? 'Guru dapat menaikkan jilid muridnya' : 'Hanya Koordinator/Admin yang boleh' }}
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 2. Identitas Kop & Pejabat Penandatangan -->
                <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
                    <h2 class="text-base font-extrabold text-slate-800 pb-3 mb-4 border-b border-slate-100">Kop Rapor &amp; Identitas Penandatangan</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Sekolah pada Kop Rapor *</label>
                            <input v-model="form.school_name" required type="text" class="w-full rounded-xl border-slate-200 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Kota Tanggal Rapor</label>
                            <input v-model="form.city_name" type="text" placeholder="Kraksaan / Probolinggo" class="w-full rounded-xl border-slate-200 text-sm" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Sekolah</label>
                            <input v-model="form.school_address" type="text" class="w-full rounded-xl border-slate-200 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Jabatan Penandatangan TPQ / Ummi</label>
                            <input v-model="form.kepala_tpq_title" type="text" placeholder="Kepala TPQ Ummi SD Namira" class="w-full rounded-xl border-slate-200 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kepala TPQ / Koordinator Ummi</label>
                            <input v-model="form.kepala_tpq" type="text" placeholder="Nur Halimah" class="w-full rounded-xl border-slate-200 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kepala Sekolah</label>
                            <input v-model="form.kepala_sekolah" type="text" placeholder="Astutik, S.Pd.I" class="w-full rounded-xl border-slate-200 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Cetak Rapor (Kosongkan = Hari Ini)</label>
                            <input v-model="form.report_date" type="date" class="w-full rounded-xl border-slate-200 text-sm" />
                        </div>
                    </div>
                </div>

                <!-- 3. Tanda Tangan Digital + Live Preview -->
                <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
                    <h2 class="text-base font-extrabold text-slate-800 pb-3 mb-4 border-b border-slate-100">Tanda Tangan Digital (PNG Transparan)</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- TTD Kepala TPQ -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-slate-800">TTD Kepala TPQ / Koordinator</span>
                                <button v-if="tpqPreview" type="button" @click="clearTpq" class="text-xs text-red-600 hover:underline inline-flex items-center gap-1">
                                    <TrashIcon class="w-3.5 h-3.5" /> Hapus
                                </button>
                            </div>
                            <input type="file" @change="onTpqFile" accept="image/png,image/jpeg,image/webp" class="block w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-100 file:text-emerald-800 file:font-bold" />
                            <div class="grid grid-cols-3 gap-2 text-[11px]">
                                <div><label class="text-slate-500">Lebar ({{ form.ttd_tpq_width }}px)</label><input v-model="form.ttd_tpq_width" type="range" min="50" max="200" class="w-full" /></div>
                                <div><label class="text-slate-500">Geser X ({{ form.ttd_tpq_offset_x }})</label><input v-model="form.ttd_tpq_offset_x" type="range" min="-50" max="50" class="w-full" /></div>
                                <div><label class="text-slate-500">Geser Y ({{ form.ttd_tpq_offset_y }})</label><input v-model="form.ttd_tpq_offset_y" type="range" min="-40" max="40" class="w-full" /></div>
                            </div>
                            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center text-xs relative overflow-hidden">
                                <p class="text-slate-600">Mengetahui,</p>
                                <p class="text-slate-700 font-semibold">{{ form.kepala_tpq_title }}</p>
                                <div class="h-16 relative">
                                    <img v-if="tpqPreview" :src="tpqPreview" :style="{ width: `${form.ttd_tpq_width}px`, transform: `translate(calc(-50% + ${form.ttd_tpq_offset_x}px), ${form.ttd_tpq_offset_y}px)` }" class="absolute bottom-0 left-1/2 max-h-20 object-contain pointer-events-none" />
                                </div>
                                <div class="font-bold border-t border-slate-800 pt-1 w-44 mx-auto relative z-10">{{ form.kepala_tpq || '........................' }}</div>
                            </div>
                        </div>

                        <!-- TTD Kepala Sekolah -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-slate-800">TTD Kepala Sekolah</span>
                                <button v-if="kepsekPreview" type="button" @click="clearKepsek" class="text-xs text-red-600 hover:underline inline-flex items-center gap-1">
                                    <TrashIcon class="w-3.5 h-3.5" /> Hapus
                                </button>
                            </div>
                            <input type="file" @change="onKepsekFile" accept="image/png,image/jpeg,image/webp" class="block w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-100 file:text-emerald-800 file:font-bold" />
                            <div class="grid grid-cols-3 gap-2 text-[11px]">
                                <div><label class="text-slate-500">Lebar ({{ form.ttd_kepsek_width }}px)</label><input v-model="form.ttd_kepsek_width" type="range" min="50" max="200" class="w-full" /></div>
                                <div><label class="text-slate-500">Geser X ({{ form.ttd_kepsek_offset_x }})</label><input v-model="form.ttd_kepsek_offset_x" type="range" min="-50" max="50" class="w-full" /></div>
                                <div><label class="text-slate-500">Geser Y ({{ form.ttd_kepsek_offset_y }})</label><input v-model="form.ttd_kepsek_offset_y" type="range" min="-40" max="40" class="w-full" /></div>
                            </div>
                            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center text-xs relative overflow-hidden">
                                <p class="text-slate-600">Mengetahui,</p>
                                <p class="text-slate-700 font-semibold">Kepala Sekolah</p>
                                <div class="h-16 relative">
                                    <img v-if="kepsekPreview" :src="kepsekPreview" :style="{ width: `${form.ttd_kepsek_width}px`, transform: `translate(calc(-50% + ${form.ttd_kepsek_offset_x}px), ${form.ttd_kepsek_offset_y}px)` }" class="absolute bottom-0 left-1/2 max-h-20 object-contain pointer-events-none" />
                                </div>
                                <div class="font-bold border-t border-slate-800 pt-1 w-44 mx-auto relative z-10">{{ form.kepala_sekolah || '........................' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" :disabled="form.processing" class="px-6 py-3 rounded-2xl bg-[#1b4332] text-white text-sm font-bold hover:bg-[#143325] shadow-lg shadow-emerald-950/15 disabled:opacity-50 inline-flex items-center gap-2">
                        <CheckCircleIcon class="w-5 h-5" />
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan Rapor' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
