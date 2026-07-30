<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    AcademicCapIcon, UserGroupIcon, BanknotesIcon, BuildingOffice2Icon,
    ChartBarIcon, GlobeAltIcon, PrinterIcon, SparklesIcon, ChevronRightIcon,
    BuildingLibraryIcon, CheckBadgeIcon, ExclamationTriangleIcon, UserIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    activeUnits: Array,
    selectedUnitId: [String, Number],
    kpi: Object,
    topDestinations: Array,
    recentTransactions: Array,
    pembinaInfo: Object,
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
};

const handleUnitChange = (e) => {
    const unitId = e.target.value;
    router.get(route('yayasan.pembina.dashboard'), { unit_id: unitId }, { preserveState: true });
};

const printExecutiveReport = () => {
    window.print();
};
</script>

<template>
    <Head title="Executive Dashboard - Pembina Yayasan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-teal-50 border border-teal-200 text-teal-800 text-xs font-bold rounded-full tracking-wider uppercase mb-1">
                        <CheckBadgeIcon class="w-4 h-4 text-teal-600" /> Executive Board Portal
                    </span>
                    <h2 class="font-black text-2xl text-slate-800 leading-tight">
                        Executive Dashboard Pembina Yayasan
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Ringkasan eksekutif kinerja akademik, keuangan, SDM, dan aset Yayasan Namira.
                    </p>
                </div>

                <!-- Unit Switcher & Export -->
                <div class="flex items-center gap-3">
                    <select 
                        :value="selectedUnitId || ''" 
                        @change="handleUnitChange"
                        class="px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs font-bold text-slate-700 shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all"
                    >
                        <option value="">-- Semua Unit Sekolah (Consolidated) --</option>
                        <option v-for="unit in activeUnits" :key="unit.id" :value="unit.id">
                            {{ unit.name }}
                        </option>
                    </select>

                    <button 
                        @click="printExecutiveReport"
                        class="px-4 py-2.5 bg-namira-teal hover:bg-teal-700 text-white rounded-2xl font-bold text-xs shadow-md shadow-teal-700/20 flex items-center gap-2 transition-all active:scale-95 shrink-0"
                    >
                        <PrinterIcon class="w-4 h-4" />
                        <span>Cetak Laporan PDF</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-8 print:py-0 print:max-w-none">
            <!-- Welcome Hero Banner for Pembina -->
            <div class="relative bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-900 rounded-3xl p-6 md:p-8 text-white shadow-xl overflow-hidden print:bg-white print:text-slate-900 print:shadow-none print:border print:border-slate-200">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-2 max-w-2xl">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 bg-white/10 backdrop-blur-md rounded-lg text-[10px] font-bold tracking-widest uppercase text-teal-200 border border-white/10">
                                Selamat Datang
                            </span>
                        </div>
                        <h3 class="text-2xl md:text-3xl font-extrabold tracking-tight">
                            {{ pembinaInfo?.name || 'Nabila Faza, S.E' }}
                        </h3>
                        <p class="text-xs md:text-sm text-teal-100/90 leading-relaxed font-normal">
                            Portal monitoring eksekutif terintegrasi Yayasan Namira. Pantau perkembangan seluruh satuan pendidikan (SD, SMP, SMA) secara konsolidasi real-time.
                        </p>
                    </div>

                    <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-4 md:min-w-[220px] space-y-2 text-center md:text-left print:hidden">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-teal-200 block">Status Akun Eksekutif</span>
                        <div class="font-bold text-sm text-white flex items-center gap-2 justify-center md:justify-start">
                            <UserIcon class="w-4 h-4 text-emerald-300" />
                            <span>{{ pembinaInfo?.role || 'Pembina Yayasan' }}</span>
                        </div>
                        <span class="text-[10px] text-teal-100/70 block">{{ pembinaInfo?.email }}</span>
                    </div>
                </div>
            </div>

            <!-- Top Row: 4 Executive KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Card 1: Total Siswa -->
                <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm hover:shadow-lg transition-all duration-300 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Siswa Aktif</span>
                        <div class="w-10 h-10 bg-teal-50 text-teal-700 rounded-2xl flex items-center justify-center border border-teal-100">
                            <AcademicCapIcon class="w-5 h-5" />
                        </div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-slate-900 tracking-tight">{{ kpi?.totalStudents || 0 }}</div>
                        <p class="text-[11px] text-slate-400 font-semibold mt-1">Siswa terdaftar di unit aktif</p>
                    </div>
                    <div class="pt-3 border-t border-slate-50 space-y-1.5">
                        <div v-for="unit in kpi?.studentsPerUnit" :key="unit.unit_id" class="flex justify-between items-center text-xs">
                            <span class="text-slate-500 font-medium">{{ unit.unit_name }}</span>
                            <span class="font-bold text-slate-800">{{ unit.count }} Siswa</span>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Tenaga Pendidik & SDM -->
                <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm hover:shadow-lg transition-all duration-300 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total SDM / Pegawai</span>
                        <div class="w-10 h-10 bg-emerald-50 text-emerald-700 rounded-2xl flex items-center justify-center border border-emerald-100">
                            <UserGroupIcon class="w-5 h-5" />
                        </div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-slate-900 tracking-tight">{{ (kpi?.totalTeachers || 0) + (kpi?.totalStaff || 0) }}</div>
                        <p class="text-[11px] text-slate-400 font-semibold mt-1">Pengajar & tenaga kependidikan</p>
                    </div>
                    <div class="pt-3 border-t border-slate-50 space-y-2">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-slate-500 font-medium">Guru & Pengajar:</span>
                            <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md">{{ kpi?.totalTeachers || 0 }} Orang</span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-slate-500 font-medium">Staf & Administrasi:</span>
                            <span class="font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded-md">{{ kpi?.totalStaff || 0 }} Orang</span>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Keuangan & SPP -->
                <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm hover:shadow-lg transition-all duration-300 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Kelancaran SPP</span>
                        <div class="w-10 h-10 bg-indigo-50 text-indigo-700 rounded-2xl flex items-center justify-center border border-indigo-100">
                            <BanknotesIcon class="w-5 h-5" />
                        </div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-indigo-950 tracking-tight">{{ kpi?.paymentPercentage || 100 }}%</div>
                        <p class="text-[11px] text-slate-400 font-semibold mt-1">Persentase kelancaran pembayaran</p>
                    </div>
                    <div class="pt-3 border-t border-slate-50 space-y-1.5">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-slate-500 font-medium">Terkumpul:</span>
                            <span class="font-extrabold text-emerald-700">{{ formatCurrency(kpi?.totalPaidBills) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-slate-500 font-medium">Tunggakan:</span>
                            <span class="font-bold text-amber-600">{{ formatCurrency(kpi?.totalUnpaidBills) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Total Aset Sarpar -->
                <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm hover:shadow-lg transition-all duration-300 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Nilai Aset & Sarpar</span>
                        <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center border border-amber-100">
                            <BuildingOffice2Icon class="w-5 h-5" />
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900 tracking-tight truncate">{{ formatCurrency(kpi?.totalAssetValue) }}</div>
                        <p class="text-[11px] text-slate-400 font-semibold mt-1">Estimasi akumulasi nilai aset</p>
                    </div>
                    <div class="pt-3 border-t border-slate-50 flex items-center justify-between text-xs">
                        <span class="text-slate-500 font-medium">Total Item Barang:</span>
                        <span class="font-bold text-amber-700 bg-amber-50 px-2.5 py-0.5 rounded-lg border border-amber-100">{{ kpi?.totalAssetCount || 0 }} Unit</span>
                    </div>
                </div>
            </div>

            <!-- Middle Section: Financial Summary & University Alumni Destinations -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left 2 Cols: Financial & Attendance Summary -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-6">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div>
                                <h4 class="font-extrabold text-slate-800 text-base">Rekapitulasi Keuangan & Kas Yayasan</h4>
                                <p class="text-xs text-slate-400 mt-0.5">Ringkasan transaksi arus kas dan pembayaran SPP siswa.</p>
                            </div>
                            <span class="px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-xl border border-emerald-100">
                                Realtime Sync
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl space-y-2">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Penerimaan SPP</span>
                                <div class="text-xl font-black text-emerald-700">{{ formatCurrency(kpi?.totalPaidBills) }}</div>
                                <p class="text-[11px] text-slate-500">Telah diverifikasi oleh bagian keuangan</p>
                            </div>

                            <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl space-y-2">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sisa Tunggakan SPP</span>
                                <div class="text-xl font-black text-amber-600">{{ formatCurrency(kpi?.totalUnpaidBills) }}</div>
                                <p class="text-[11px] text-slate-500">Dalam pemantauan wali kelas & keuangan</p>
                            </div>
                        </div>

                        <!-- Progress Bar for SPP Realization -->
                        <div class="space-y-2 pt-2">
                            <div class="flex justify-between text-xs font-bold">
                                <span class="text-slate-700">Tingkat Realisasi Pembayaran SPP</span>
                                <span class="text-teal-700">{{ kpi?.paymentPercentage || 100 }}%</span>
                            </div>
                            <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden p-0.5">
                                <div 
                                    class="h-full bg-gradient-to-r from-teal-600 to-emerald-500 rounded-full transition-all duration-1000"
                                    :style="{ width: (kpi?.paymentPercentage || 100) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right 1 Col: University Alumni & Destinations -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2">
                                <GlobeAltIcon class="w-5 h-5 text-teal-600" />
                                <h4 class="font-extrabold text-slate-800 text-sm">Destinasi Alumni & Kunjungan</h4>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div 
                                v-for="dest in topDestinations" 
                                :key="dest.id"
                                class="p-3 bg-slate-50/80 hover:bg-slate-100 border border-slate-100 rounded-2xl transition-colors flex items-center justify-between gap-3"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 bg-teal-100 text-teal-800 text-[9px] font-bold uppercase rounded-md">
                                            {{ dest.visit_type === 'alumni' ? 'Alumni' : 'Kunjungan' }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-semibold uppercase">{{ dest.type }}</span>
                                    </div>
                                    <h5 class="text-xs font-bold text-slate-800 truncate mt-1">{{ dest.name }}</h5>
                                    <p class="text-[10px] text-slate-400 truncate">{{ dest.city }}, {{ dest.country }}</p>
                                </div>
                                <span class="text-[10px] font-bold text-slate-400 shrink-0">{{ dest.unit?.name || 'Yayasan' }}</span>
                            </div>

                            <div v-if="!topDestinations || topDestinations.length === 0" class="text-center py-6 text-slate-400">
                                <BuildingLibraryIcon class="w-8 h-8 mx-auto opacity-30 mb-2" />
                                <p class="text-xs font-semibold">Belum ada data destinasi universitas terdaftar.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
