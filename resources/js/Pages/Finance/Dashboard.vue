<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { BanknotesIcon } from '@heroicons/vue/24/outline';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

const props = defineProps({
    stats: Object,
    chart: Object,
    recent_transactions: Array,
});

const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (date) => new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const chartData = {
  labels: props.chart.labels,
  datasets: [
    {
      label: 'Pemasukan Bulanan',
      backgroundColor: (ctx) => {
        const canvas = ctx.chart.ctx;
        const gradient = canvas.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(13, 148, 136, 0.5)'); // Teal-600
        gradient.addColorStop(1, 'rgba(13, 148, 136, 0.0)');
        return gradient;
      },
      borderColor: '#0d9488', // Teal-600
      pointBackgroundColor: '#fff',
      pointBorderColor: '#0d9488',
      pointHoverBackgroundColor: '#0d9488',
      pointHoverBorderColor: '#fff',
      borderWidth: 3,
      data: props.chart.data,
      fill: true,
      tension: 0.4, 
    }
  ]
};

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
        backgroundColor: 'rgba(255, 255, 255, 0.9)',
        titleColor: '#1f2937',
        bodyColor: '#1f2937',
        borderColor: '#e5e7eb',
        borderWidth: 1,
        padding: 10,
        callbacks: {
            label: function(context) {
                let label = context.dataset.label || '';
                if (label) {
                    label += ': ';
                }
                if (context.parsed.y !== null) {
                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(context.parsed.y);
                }
                return label;
            }
        }
    }
  },
  scales: {
    y: {
        grid: { color: '#f3f4f6' },
        ticks: { callback: function(value) { return 'Rp ' + (value/1000000) + 'jt'; } }
    },
    x: {
        grid: { display: false }
    }
  }
};
</script>

<template>
    <Head title="Keuangan Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-2xl bg-gradient-to-r from-teal-800 to-teal-500 bg-clip-text text-transparent leading-tight">
                Ringkasan Keuangan
            </h2>
            <p class="text-sm text-gray-500 mt-1">Pantau arus kas dan kesehatan finansial sekolah.</p>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6">
            
            <!-- Bento Grid Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Total Tagihan -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-50 rounded-full group-hover:scale-110 transition-transform"></div>
                    <div class="relative z-10">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Tagihan (Semua)</p>
                        <h3 class="text-2xl font-bold text-gray-800">{{ formatCurrency(stats.total_bills) }}</h3>
                    </div>
                </div>

                <!-- Sudah Bayar -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-green-50 rounded-full group-hover:scale-110 transition-transform"></div>
                    <div class="relative z-10">
                        <p class="text-sm font-medium text-gray-500 mb-1">Sudah Diterima</p>
                        <h3 class="text-2xl font-bold text-green-600">{{ formatCurrency(stats.total_paid) }}</h3>
                    </div>
                </div>

                <!-- Tunggakan -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-50 rounded-full group-hover:scale-110 transition-transform"></div>
                    <div class="relative z-10">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Tunggakan</p>
                        <h3 class="text-2xl font-bold text-red-600">{{ formatCurrency(stats.total_arrears) }}</h3>
                        <p class="text-xs text-red-400 mt-1">Wajib ditagih!</p>
                    </div>
                </div>

                <!-- Pemasukan Bulan Ini -->
                <div class="bg-gradient-to-br from-teal-500 to-teal-700 p-6 rounded-3xl shadow-lg text-white relative overflow-hidden">
                    <div class="relative z-10">
                        <p class="text-sm font-medium text-teal-100 mb-1">Pemasukan Bulan Ini</p>
                        <h3 class="text-2xl font-bold">{{ formatCurrency(stats.income_this_month) }}</h3>
                        <p class="text-xs text-teal-200 mt-1">{{ chart.labels[chart.labels.length - 1] }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Chart Area -->
                <div class="lg:col-span-2 bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-gray-800">Tren Pemasukan (12 Bulan)</h3>
                        <span class="px-2 py-1 bg-teal-50 text-teal-700 text-xs font-bold rounded-lg border border-teal-100">Realized Income</span>
                    </div>
                    <div class="h-80 w-full">
                        <Line :data="chartData" :options="chartOptions" />
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-4">Transaksi Terakhir</h3>
                    <div class="space-y-4">
                        <div v-if="recent_transactions.length === 0" class="text-center text-gray-400 py-8 text-sm">
                            Belum ada transaksi.
                        </div>
                        <div v-for="trx in recent_transactions" :key="trx.id" class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 transition-colors border border-transparent hover:border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-teal-100 flex items-center justify-center text-teal-600">
                                    <BanknotesIcon class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-800">{{ trx.student?.name }}</p>
                                    <p class="text-xs text-gray-500">{{ trx.student?.classroom?.name ?? 'Unknown' }} • {{ formatDate(trx.transaction_date) }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-teal-600">+{{ formatCurrency(trx.amount) }}</p>
                                <p class="text-[10px] text-gray-400 uppercase">{{ trx.payment_method }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-50 text-center">
                        <a :href="route('yayasan.finance.transactions.index')" class="text-xs font-bold text-teal-600 hover:text-teal-800">Lihat Semua Transaksi &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
