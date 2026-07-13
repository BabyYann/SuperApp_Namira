<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    CubeIcon, ArchiveBoxIcon, ExclamationTriangleIcon, WrenchScrewdriverIcon,
    ClockIcon, ArrowPathIcon, ChartBarIcon, BanknotesIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    stats: Object,
    lowStockItems: Array,
    conditionStats: Object,
    categoryStats: Array,
    fundingStats: Array,
    statusStats: Object,
    pendingMaintenance: Array,
    activeLoans: Array,
    recentUsage: Array,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value || 0);
};

const formatCurrencyShort = (value) => {
    if (value >= 1000000000) {
        return 'Rp ' + (value / 1000000000).toFixed(1) + ' M';
    } else if (value >= 1000000) {
        return 'Rp ' + (value / 1000000).toFixed(1) + ' Jt';
    } else if (value >= 1000) {
        return 'Rp ' + (value / 1000).toFixed(0) + ' Rb';
    }
    return 'Rp ' + value;
};

const getConditionLabel = (key) => {
    const labels = { 'baik': 'Baik', 'rusak_ringan': 'Rusak Ringan', 'rusak_berat': 'Rusak Berat' };
    return labels[key] || key;
};
const getConditionColor = (key) => {
    const colors = { 'baik': 'bg-green-500', 'rusak_ringan': 'bg-amber-500', 'rusak_berat': 'bg-red-500' };
    return colors[key] || 'bg-gray-500';
};
</script>

<template>
    <Head title="Dashboard Sarpar" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                    Dashboard Sarana Prasarana
                </h2>
                <p class="text-sm text-gray-500 mt-1">Ringkasan data inventaris dan aktivitas</p>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto pb-20 space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-teal-500 to-teal-600 rounded-3xl p-5 text-white shadow-lg shadow-teal-500/30">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-white/20 rounded-2xl"><CubeIcon class="w-6 h-6" /></div>
                        <div>
                            <p class="text-3xl font-extrabold">{{ stats.totalItems }}</p>
                            <p class="text-sm text-teal-100">Total Item</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-3xl p-5 text-white shadow-lg shadow-blue-500/30">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-white/20 rounded-2xl"><BanknotesIcon class="w-6 h-6" /></div>
                        <div class="min-w-0">
                            <p class="text-lg font-extrabold truncate" :title="formatCurrency(stats.totalValue)">{{ formatCurrencyShort(stats.totalValue) }}</p>
                            <p class="text-sm text-blue-100">Nilai Aset</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-3xl p-5 text-white shadow-lg shadow-amber-500/30">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-white/20 rounded-2xl"><WrenchScrewdriverIcon class="w-6 h-6" /></div>
                        <div>
                            <p class="text-3xl font-extrabold">{{ stats.pendingMaintenanceCount }}</p>
                            <p class="text-sm text-amber-100">Perlu Perbaikan</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-3xl p-5 text-white shadow-lg shadow-red-500/30">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-white/20 rounded-2xl"><ExclamationTriangleIcon class="w-6 h-6" /></div>
                        <div>
                            <p class="text-3xl font-extrabold">{{ stats.lowStockCount }}</p>
                            <p class="text-sm text-red-100">Stok Menipis</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Type & Funding Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- By Type -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 p-6">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <ArchiveBoxIcon class="w-5 h-5 text-namira-teal" />Berdasarkan Jenis
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center p-3 bg-blue-50 rounded-xl">
                            <span class="font-medium text-gray-700">🏢 Aset Tetap</span>
                            <span class="font-bold text-blue-600">{{ stats.totalAssets }}</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-orange-50 rounded-xl">
                            <span class="font-medium text-gray-700">📦 Habis Pakai</span>
                            <span class="font-bold text-orange-600">{{ stats.totalConsumables }}</span>
                        </div>
                    </div>
                </div>

                <!-- By Funding -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 p-6">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <BanknotesIcon class="w-5 h-5 text-namira-teal" />Berdasarkan Sumber Dana
                    </h3>
                    <div class="space-y-3">
                        <div v-for="fund in fundingStats" :key="fund.funding_source" :class="['flex justify-between items-center p-3 rounded-xl', fund.funding_source === 'BOS' ? 'bg-purple-50' : 'bg-teal-50']">
                            <span class="font-medium text-gray-700">{{ fund.funding_source === 'BOS' ? '💜 Dana BOS' : '💚 Dana Yayasan' }}</span>
                            <div class="text-right">
                                <span :class="['font-bold', fund.funding_source === 'BOS' ? 'text-purple-600' : 'text-teal-600']">{{ fund.count }} item</span>
                                <p class="text-xs text-gray-500">{{ formatCurrency(fund.value) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- By Condition -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 p-6">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <ChartBarIcon class="w-5 h-5 text-namira-teal" />Berdasarkan Kondisi
                    </h3>
                    <div class="space-y-3">
                        <div v-for="(count, condition) in conditionStats" :key="condition" class="flex items-center gap-3">
                            <div :class="['w-3 h-3 rounded-full', getConditionColor(condition)]"></div>
                            <span class="flex-1 text-sm text-gray-700">{{ getConditionLabel(condition) }}</span>
                            <span class="font-bold text-gray-800">{{ count }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Stats -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 p-6">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <ChartBarIcon class="w-5 h-5 text-namira-teal" />Inventaris per Kategori
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <div v-for="cat in categoryStats" :key="cat.category" class="bg-gray-50 rounded-2xl p-4 text-center">
                        <p class="text-2xl font-extrabold text-namira-teal">{{ cat.count }}</p>
                        <p class="text-sm text-gray-600 truncate">{{ cat.category }}</p>
                    </div>
                </div>
            </div>

            <!-- Alerts Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Low Stock Alert -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-bold text-gray-800 flex items-center gap-2">
                            <ExclamationTriangleIcon class="w-5 h-5 text-red-500" />Stok Menipis
                        </h3>
                        <Link :href="route('sarpar.inventories.index', {item_type: 'consumable'})" class="text-sm text-namira-teal font-bold hover:underline">Lihat Semua</Link>
                    </div>
                    <div class="p-4 space-y-3 max-h-60 overflow-y-auto">
                        <div v-if="lowStockItems.length === 0" class="text-center text-gray-400 py-4">
                            <p>Tidak ada item dengan stok menipis</p>
                        </div>
                        <Link v-for="item in lowStockItems" :key="item.id" :href="route('sarpar.inventories.show', item.id)" class="flex items-center justify-between p-3 bg-red-50 rounded-xl hover:bg-red-100 transition-colors">
                            <div>
                                <p class="font-bold text-gray-800">{{ item.name }}</p>
                                <p class="text-xs text-gray-500">{{ item.category?.name }}</p>
                            </div>
                            <div class="text-right">
                                <span class="font-bold text-red-600">{{ item.quantity }}</span>
                                <span class="text-gray-400 text-sm"> / {{ item.min_stock }}</span>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Pending Maintenance -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-bold text-gray-800 flex items-center gap-2">
                            <WrenchScrewdriverIcon class="w-5 h-5 text-amber-500" />Perlu Perbaikan
                        </h3>
                        <Link :href="route('sarpar.maintenance.index')" class="text-sm text-namira-teal font-bold hover:underline">Lihat Semua</Link>
                    </div>
                    <div class="p-4 space-y-3 max-h-60 overflow-y-auto">
                        <div v-if="pendingMaintenance.length === 0" class="text-center text-gray-400 py-4">
                            <p>Tidak ada laporan kerusakan</p>
                        </div>
                        <div v-for="log in pendingMaintenance" :key="log.id" class="flex items-center justify-between p-3 bg-amber-50 rounded-xl">
                            <div>
                                <p class="font-bold text-gray-800">{{ log.inventory?.name }}</p>
                                <p class="text-xs text-gray-500">{{ log.issue }}</p>
                            </div>
                            <span :class="['px-2 py-1 text-xs font-bold rounded-lg', log.status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700']">{{ log.status }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Active Loans -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-bold text-gray-800 flex items-center gap-2">
                            <ClockIcon class="w-5 h-5 text-blue-500" />Peminjaman Aktif
                        </h3>
                        <Link :href="route('sarpar.loans.index')" class="text-sm text-namira-teal font-bold hover:underline">Lihat Semua</Link>
                    </div>
                    <div class="p-4 space-y-3 max-h-60 overflow-y-auto">
                        <div v-if="activeLoans.length === 0" class="text-center text-gray-400 py-4">
                            <p>Tidak ada peminjaman aktif</p>
                        </div>
                        <div v-for="loan in activeLoans" :key="loan.id" class="flex items-center justify-between p-3 bg-blue-50 rounded-xl">
                            <div>
                                <p class="font-bold text-gray-800">{{ loan.inventory?.name }}</p>
                                <p class="text-xs text-gray-500">{{ loan.borrower?.name }}</p>
                            </div>
                            <span class="text-xs text-gray-500">{{ loan.due_date ? new Date(loan.due_date).toLocaleDateString('id-ID') : '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Usage -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <h3 class="font-bold text-gray-800 flex items-center gap-2">
                            <ArrowPathIcon class="w-5 h-5 text-orange-500" />Penggunaan Terakhir
                        </h3>
                    </div>
                    <div class="p-4 space-y-3 max-h-60 overflow-y-auto">
                        <div v-if="recentUsage.length === 0" class="text-center text-gray-400 py-4">
                            <p>Belum ada penggunaan tercatat</p>
                        </div>
                        <div v-for="usage in recentUsage" :key="usage.id" class="flex items-center justify-between p-3 bg-orange-50 rounded-xl">
                            <div>
                                <p class="font-bold text-gray-800">{{ usage.inventory?.name }}</p>
                                <p class="text-xs text-gray-500">{{ usage.purpose || '-' }}</p>
                            </div>
                            <div class="text-right">
                                <span class="font-bold text-orange-600">-{{ usage.quantity_used }}</span>
                                <p class="text-xs text-gray-400">{{ new Date(usage.used_date).toLocaleDateString('id-ID') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 p-6">
                <h3 class="font-bold text-gray-800 mb-4">Aksi Cepat</h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <Link :href="route('sarpar.inventories.index')" class="p-4 bg-teal-50 rounded-2xl text-center hover:bg-teal-100 transition-colors">
                        <CubeIcon class="w-8 h-8 mx-auto text-teal-600" />
                        <p class="text-sm font-bold text-gray-700 mt-2">Data Inventaris</p>
                    </Link>
                    <Link :href="route('sarpar.rooms.index')" class="p-4 bg-blue-50 rounded-2xl text-center hover:bg-blue-100 transition-colors">
                        <svg class="w-8 h-8 mx-auto text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <p class="text-sm font-bold text-gray-700 mt-2">Data Ruangan</p>
                    </Link>
                    <Link :href="route('sarpar.maintenance.index')" class="p-4 bg-amber-50 rounded-2xl text-center hover:bg-amber-100 transition-colors">
                        <WrenchScrewdriverIcon class="w-8 h-8 mx-auto text-amber-600" />
                        <p class="text-sm font-bold text-gray-700 mt-2">Perawatan</p>
                    </Link>
                    <Link :href="route('sarpar.loans.index')" class="p-4 bg-purple-50 rounded-2xl text-center hover:bg-purple-100 transition-colors">
                        <ArrowPathIcon class="w-8 h-8 mx-auto text-purple-600" />
                        <p class="text-sm font-bold text-gray-700 mt-2">Peminjaman</p>
                    </Link>
                    <a :href="route('sarpar.inventories.export')" class="p-4 bg-green-50 rounded-2xl text-center hover:bg-green-100 transition-colors">
                        <svg class="w-8 h-8 mx-auto text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <p class="text-sm font-bold text-gray-700 mt-2">Export Excel</p>
                    </a>
                    <a :href="route('sarpar.inventories.export', {format: 'pdf'})" class="p-4 bg-red-50 rounded-2xl text-center hover:bg-red-100 transition-colors">
                        <svg class="w-8 h-8 mx-auto text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        <p class="text-sm font-bold text-gray-700 mt-2">Export PDF</p>
                    </a>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
