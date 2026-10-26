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
        <div class="py-2 md:py-6 max-w-4xl mx-auto space-y-2.5 md:space-y-6">

            <!-- Top Header & Navigation -->
            <div class="flex items-center justify-between gap-2 bg-white md:bg-transparent p-2.5 md:p-0 rounded-xl border border-slate-200 md:border-0 shadow-2xs md:shadow-none">
                <div class="flex items-center gap-2.5">
                    <Link 
                        :href="route('yayasan.teaching-journal.index')"
                        class="w-8 h-8 md:w-10 md:h-10 rounded-lg md:rounded-2xl bg-slate-100 md:bg-white hover:bg-slate-200 text-slate-700 flex items-center justify-center border border-slate-200/80 transition active:scale-95 shrink-0"
                        title="Kembali ke Jurnal"
                    >
                        <ChevronLeftIcon class="w-4 h-4 md:w-5 md:h-5 stroke-[2.5]" />
                    </Link>
                    <div>
                        <h1 class="text-xs sm:text-xl font-black text-slate-900 tracking-tight leading-tight">
                            Rekap Jurnal Mengajar
                        </h1>
                        <p class="text-[10px] sm:text-xs text-slate-500 font-medium leading-none mt-0.5">
                            {{ monthName }} • {{ journals.length }} Sesi KBM
                        </p>
                    </div>
                </div>

                <!-- Opsi Export HTML/PDF -->
                <a 
                    :href="route('yayasan.teaching-journal.export', { month: selectedMonth, year: selectedYear })"
                    class="px-2.5 py-1.5 md:px-3.5 md:py-2 rounded-lg md:rounded-2xl bg-slate-900 md:bg-slate-100 text-white md:text-slate-700 text-[10px] md:text-xs font-extrabold border border-slate-200 flex items-center gap-1 transition active:scale-95 shrink-0"
                    title="Unduh File Rekap"
                >
                    <ArrowDownTrayIcon class="w-3.5 h-3.5 md:w-4 md:h-4 stroke-[2.2]" />
                    <span>Cetak</span>
                </a>
            </div>

            <!-- Month Selector + Stats Strip Compact on Mobile -->
            <div class="bg-white rounded-xl md:rounded-3xl p-2.5 md:p-4 border border-slate-200 md:border-slate-100 shadow-2xs md:shadow-sm space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <button 
                        @click="changeMonth(-1)"
                        type="button" 
                        class="w-8 h-8 md:w-10 md:h-10 rounded-lg md:rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition active:scale-95 shrink-0"
                        title="Bulan Sebelumnya"
                    >
                        <ChevronLeftIcon class="w-4 h-4 stroke-[2.5]" />
                    </button>

                    <div class="flex items-center gap-1.5">
                        <select 
                            v-model="selectedMonth" 
                            @change="fetchRecap"
                            class="bg-slate-50 border border-slate-200 text-xs sm:text-sm font-black text-slate-800 rounded-lg md:rounded-2xl py-1.5 md:py-2 px-2.5 md:px-3 focus:ring-teal-500 focus:border-teal-500"
                        >
                            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>

                        <input 
                            type="number" 
                            v-model="selectedYear" 
                            @change="fetchRecap"
                            class="w-18 md:w-20 bg-slate-50 border border-slate-200 text-xs sm:text-sm font-black text-slate-800 rounded-lg md:rounded-2xl py-1.5 md:py-2 px-2 md:px-3 text-center focus:ring-teal-500 focus:border-teal-500"
                        />
                    </div>

                    <button 
                        @click="changeMonth(1)"
                        type="button" 
                        class="w-8 h-8 md:w-10 md:h-10 rounded-lg md:rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition active:scale-95 shrink-0"
                        title="Bulan Berikutnya"
                    >
                        <ChevronRightIcon class="w-4 h-4 stroke-[2.5]" />
                    </button>
                </div>

                <!-- Stats Ribbon Inline -->
                <div class="grid grid-cols-3 gap-1.5 md:gap-4 pt-2 border-t border-slate-100 text-center">
                    <div class="bg-slate-50 p-2 md:p-4 rounded-lg md:rounded-2xl border border-slate-100">
                        <p class="text-sm sm:text-2xl font-black text-blue-700 leading-none">
                            {{ stats?.teaching_days || 0 }}
                        </p>
                        <p class="text-[9px] sm:text-xs font-bold text-slate-500 mt-1">
                            Hari Mengajar
                        </p>
                    </div>

                    <div class="bg-slate-50 p-2 md:p-4 rounded-lg md:rounded-2xl border border-slate-100">
                        <p class="text-sm sm:text-2xl font-black text-amber-600 leading-none">
                            {{ stats?.total_journals || 0 }}
                        </p>
                        <p class="text-[9px] sm:text-xs font-bold text-slate-500 mt-1">
                            Total Jurnal
                        </p>
                    </div>

                    <div class="bg-slate-50 p-2 md:p-4 rounded-lg md:rounded-2xl border border-slate-100">
                        <p class="text-sm sm:text-2xl font-black text-purple-600 leading-none">
                            {{ stats?.avg_attendance_rate || 0 }}%
                        </p>
                        <p class="text-[9px] sm:text-xs font-bold text-slate-500 mt-1">
                            Kehadiran Siswa
                        </p>
                    </div>
                </div>
            </div>

            <!-- Journal List -->
            <div class="space-y-2">
                <!-- Empty State -->
                <div v-if="journals.length === 0" class="bg-white rounded-xl md:rounded-3xl border border-slate-200 md:border-slate-100 shadow-2xs p-6 text-center flex flex-col items-center justify-center">
                    <div class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center mb-2">
                        <BookOpenIcon class="w-5 h-5 stroke-[1.8]" />
                    </div>
                    <h4 class="font-black text-xs sm:text-sm text-slate-800">Belum Ada Jurnal di Bulan Ini</h4>
                    <p class="text-[11px] text-slate-500 max-w-xs mt-0.5">
                        Tidak ada riwayat pengisian jurnal mengajar pada periode {{ monthName }}.
                    </p>
                </div>

                <!-- Journal Micro-Cards -->
                <div 
                    v-for="j in journals" 
                    :key="j.id"
                    class="bg-white rounded-xl md:rounded-3xl p-2.5 md:p-5 border border-slate-200/90 md:border-slate-100 shadow-2xs md:shadow-sm hover:border-teal-200 transition-all space-y-1.5 relative overflow-hidden"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-800 font-extrabold text-[9px] md:text-[11px] rounded border border-emerald-200/60">
                                {{ j.classroom }}
                            </span>
                            <span class="text-[9px] md:text-xs font-bold text-slate-400 font-mono">
                                {{ j.start_time }} - {{ j.end_time }}
                            </span>
                        </div>
                        <span class="text-[10px] md:text-xs font-black text-slate-600 shrink-0">
                            {{ j.formatted_date }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-100">
                        <div class="min-w-0 flex-1">
                            <h4 class="text-xs md:text-base font-black text-slate-900 leading-tight truncate">
                                {{ j.subject }}
                            </h4>
                            <p v-if="j.custom_theme" class="text-[10px] md:text-xs text-teal-700 font-bold truncate mt-0.5">
                                Tema: {{ j.custom_theme }}
                            </p>
                            <div class="flex items-center gap-1.5 text-[10px] md:text-xs mt-0.5">
                                <span class="font-extrabold text-emerald-700">
                                    {{ j.attendance_summary.present }} Hadir
                                </span>
                                <span v-if="j.attendance_summary.sick > 0" class="text-amber-600 font-bold">
                                    • {{ j.attendance_summary.sick }}S
                                </span>
                                <span v-if="j.attendance_summary.permission > 0" class="text-blue-600 font-bold">
                                    • {{ j.attendance_summary.permission }}I
                                </span>
                                <span v-if="j.attendance_summary.alpha > 0" class="text-rose-600 font-bold">
                                    • {{ j.attendance_summary.alpha }}A
                                </span>
                            </div>
                        </div>

                        <Link 
                            :href="route('yayasan.teaching-journal.show', j.id)"
                            class="shrink-0 h-7 px-2.5 bg-slate-100 hover:bg-teal-50 text-slate-700 hover:text-teal-800 text-[10px] md:text-xs font-black rounded-lg border border-slate-200 inline-flex items-center gap-1"
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
