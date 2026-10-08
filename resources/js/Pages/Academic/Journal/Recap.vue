<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { 
    ChevronLeftIcon,
    ChevronRightIcon,
    CalendarDaysIcon,
    BookOpenIcon,
    DocumentTextIcon,
    UserGroupIcon,
    CheckCircleIcon,
    ClockIcon,
    ArrowDownTrayIcon,
    EyeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    journals: Array,
    month: [Number, String],
    year: [Number, String],
    monthName: String,
    stats: Object,
    isTeacher: Boolean,
    hasAdminRole: Boolean,
});

const selectedMonth = ref(Number(props.month));
const selectedYear = ref(Number(props.year));

const months = [
    { value: 1, label: 'Januari' },
    { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' },
    { value: 4, label: 'April' },
    { value: 5, label: 'Mei' },
    { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' },
    { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' },
    { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' },
    { value: 12, label: 'Desember' },
];

const changeMonth = (delta) => {
    let m = selectedMonth.value + delta;
    let y = selectedYear.value;
    if (m > 12) {
        m = 1;
        y += 1;
    } else if (m < 1) {
        m = 12;
        y -= 1;
    }
    selectedMonth.value = m;
    selectedYear.value = y;
    fetchRecap();
};

const fetchRecap = () => {
    router.get(route('yayasan.teaching-journal.recap'), {
        month: selectedMonth.value,
        year: selectedYear.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Rekap Jurnal - ${monthName}`" />

    <AuthenticatedLayout>
        <div class="py-4 md:py-6 max-w-4xl mx-auto space-y-4 md:space-y-6">

            <!-- Top Header & Navigation -->
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Link 
                        :href="route('yayasan.teaching-journal.index')"
                        class="w-10 h-10 rounded-2xl bg-white hover:bg-slate-100 text-slate-700 flex items-center justify-center border border-slate-200/80 shadow-2xs transition active:scale-95 shrink-0"
                        title="Kembali ke Jurnal"
                    >
                        <ChevronLeftIcon class="w-5 h-5 stroke-[2.5]" />
                    </Link>
                    <div>
                        <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight leading-tight">
                            Rekap Jurnal Mengajar
                        </h1>
                        <p class="text-xs text-slate-500 font-medium">
                            {{ monthName }}
                        </p>
                    </div>
                </div>

                <!-- Opsi Export HTML/PDF -->
                <a 
                    :href="route('yayasan.teaching-journal.export', { month: selectedMonth, year: selectedYear })"
                    class="px-3.5 py-2 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold border border-slate-200 flex items-center gap-1.5 transition active:scale-95 shadow-2xs shrink-0"
                    title="Unduh File Rekap"
                >
                    <ArrowDownTrayIcon class="w-4 h-4 stroke-[2.2]" />
                    <span class="hidden sm:inline">Cetak Rekap</span>
                </a>
            </div>

            <!-- Month Selector Card -->
            <div class="bg-white rounded-3xl p-3 sm:p-4 border border-slate-100 shadow-sm flex items-center justify-between gap-2">
                <button 
                    @click="changeMonth(-1)"
                    type="button" 
                    class="w-10 h-10 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition active:scale-95 shrink-0"
                    title="Bulan Sebelumnya"
                >
                    <ChevronLeftIcon class="w-4 h-4 stroke-[2.5]" />
                </button>

                <div class="flex items-center gap-2">
                    <select 
                        v-model="selectedMonth" 
                        @change="fetchRecap"
                        class="bg-slate-50 border border-slate-200 text-xs sm:text-sm font-black text-slate-800 rounded-2xl py-2 px-3 focus:ring-teal-500 focus:border-teal-500"
                    >
                        <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                    </select>

                    <input 
                        type="number" 
                        v-model="selectedYear" 
                        @change="fetchRecap"
                        class="w-20 bg-slate-50 border border-slate-200 text-xs sm:text-sm font-black text-slate-800 rounded-2xl py-2 px-3 text-center focus:ring-teal-500 focus:border-teal-500"
                    />
                </div>

                <button 
                    @click="changeMonth(1)"
                    type="button" 
                    class="w-10 h-10 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition active:scale-95 shrink-0"
                    title="Bulan Berikutnya"
                >
                    <ChevronRightIcon class="w-4 h-4 stroke-[2.5]" />
                </button>
            </div>

            <!-- Stats Ribbon -->
            <div class="grid grid-cols-3 gap-2.5 sm:gap-4">
                <div class="bg-white p-3 sm:p-4 rounded-3xl border border-slate-100 shadow-sm flex flex-col items-center justify-center text-center">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-1.5">
                        <DocumentTextIcon class="w-4 h-4 stroke-[2.2]" />
                    </div>
                    <p class="text-lg sm:text-2xl font-black text-slate-900 leading-none">
                        {{ stats?.teaching_days || 0 }}
                    </p>
                    <p class="text-[10px] sm:text-xs font-bold text-slate-500 mt-1">
                        Hari Mengajar
                    </p>
                </div>

                <div class="bg-white p-3 sm:p-4 rounded-3xl border border-slate-100 shadow-sm flex flex-col items-center justify-center text-center">
                    <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mb-1.5">
                        <BookOpenIcon class="w-4 h-4 stroke-[2.2]" />
                    </div>
                    <p class="text-lg sm:text-2xl font-black text-slate-900 leading-none">
                        {{ stats?.total_journals || 0 }}
                    </p>
                    <p class="text-[10px] sm:text-xs font-bold text-slate-500 mt-1">
                        Total Jurnal
                    </p>
                </div>

                <div class="bg-white p-3 sm:p-4 rounded-3xl border border-slate-100 shadow-sm flex flex-col items-center justify-center text-center">
                    <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center mb-1.5">
                        <UserGroupIcon class="w-4 h-4 stroke-[2.2]" />
                    </div>
                    <p class="text-lg sm:text-2xl font-black text-slate-900 leading-none">
                        {{ stats?.avg_attendance_rate || 0 }}%
                    </p>
                    <p class="text-[10px] sm:text-xs font-bold text-slate-500 mt-1">
                        Kehadiran Siswa
                    </p>
                </div>
            </div>

            <!-- Journal List -->
            <div class="space-y-3">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider px-1">
                    Daftar Aktivitas KBM ({{ journals.length }} Sesi)
                </h3>

                <!-- Empty State -->
                <div v-if="journals.length === 0" class="bg-white rounded-3xl border border-slate-100 shadow-sm p-8 text-center flex flex-col items-center justify-center">
                    <div class="w-16 h-16 rounded-3xl bg-slate-50 text-slate-400 flex items-center justify-center mb-3">
                        <BookOpenIcon class="w-8 h-8 stroke-[1.8]" />
                    </div>
                    <h4 class="font-black text-sm text-slate-800">Belum Ada Jurnal di Bulan Ini</h4>
                    <p class="text-xs text-slate-500 max-w-xs mt-1">
                        Tidak ada riwayat pengisian jurnal mengajar pada periode {{ monthName }}.
                    </p>
                </div>

                <!-- Journal Cards -->
                <div 
                    v-for="j in journals" 
                    :key="j.id"
                    class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-100 shadow-sm hover:border-teal-200 transition-all flex flex-col gap-3 relative overflow-hidden"
                >
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 bg-emerald-50 text-emerald-800 font-extrabold text-[11px] rounded-xl border border-emerald-200/60">
                                {{ j.classroom }}
                            </span>
                            <span class="text-xs font-bold text-slate-400 flex items-center gap-1">
                                <ClockIcon class="w-3.5 h-3.5" />
                                {{ j.start_time }} - {{ j.end_time }} WIB
                            </span>
                        </div>
                        <span class="text-xs font-black text-slate-600">
                            {{ j.formatted_date }}
                        </span>
                    </div>

                    <div>
                        <h4 class="text-base font-black text-slate-900 leading-snug">
                            {{ j.subject }}
                        </h4>
                        <p v-if="j.custom_theme" class="text-xs text-teal-700 font-bold mt-1">
                            Tema: {{ j.custom_theme }}
                        </p>
                        <div v-if="j.learning_objectives && j.learning_objectives.length > 0" class="mt-1 flex flex-wrap gap-1">
                            <span 
                                v-for="(tp, idx) in j.learning_objectives" 
                                :key="idx"
                                class="text-[10px] font-bold bg-slate-100 text-slate-600 px-2 py-0.5 rounded-lg"
                            >
                                {{ tp.code }}: {{ tp.description }}
                            </span>
                        </div>
                    </div>

                    <div class="pt-2.5 border-t border-slate-100 flex items-center justify-between gap-2">
                        <!-- Presensi Summary -->
                        <div class="flex items-center gap-1.5 text-xs">
                            <span class="font-extrabold text-emerald-700">
                                {{ j.attendance_summary.present }} Hadir
                            </span>
                            <span v-if="j.attendance_summary.sick > 0" class="text-amber-600 font-bold">
                                • {{ j.attendance_summary.sick }} Sakit
                            </span>
                            <span v-if="j.attendance_summary.permission > 0" class="text-blue-600 font-bold">
                                • {{ j.attendance_summary.permission }} Izin
                            </span>
                            <span v-if="j.attendance_summary.alpha > 0" class="text-rose-600 font-bold">
                                • {{ j.attendance_summary.alpha }} Alpa
                            </span>
                        </div>

                        <!-- Action Link -->
                        <Link 
                            :href="route('yayasan.teaching-journal.show', j.id)"
                            class="px-3 py-1.5 bg-slate-50 hover:bg-teal-50 text-slate-700 hover:text-teal-800 text-xs font-black rounded-xl border border-slate-200 hover:border-teal-200 transition active:scale-95 flex items-center gap-1"
                        >
                            <EyeIcon class="w-3.5 h-3.5 text-teal-600" />
                            <span>Detail</span>
                        </Link>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
