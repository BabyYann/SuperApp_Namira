<script setup>
defineProps({
    summary: {
        type: Object,
        required: true
    }
});
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Students -->
        <div class="bg-white border rounded-2xl p-5 shadow-sm">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Siswa</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-bold text-gray-900">{{ summary.total_students }}</span>
                <span class="text-xs text-gray-400">Siswa Rombel</span>
            </div>
        </div>

        <!-- Eligible -->
        <div class="bg-white border border-emerald-100 rounded-2xl p-5 shadow-sm bg-gradient-to-br from-white to-emerald-50/20">
            <div class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Memenuhi Syarat (Eligible)</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-bold text-emerald-700">{{ summary.eligible_students }}</span>
                <span class="text-xs text-emerald-600/70">Siap Promosi</span>
            </div>
        </div>

        <!-- Blocked -->
        <div class="bg-white border border-rose-100 rounded-2xl p-5 shadow-sm bg-gradient-to-br from-white to-rose-50/20">
            <div class="text-xs font-semibold text-rose-700 uppercase tracking-wider">Ditangguhkan (Blocked)</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-bold text-rose-700">{{ summary.blocked_students }}</span>
                <span class="text-xs text-rose-600/70" v-if="summary.blocked_multiple_reason > 0">
                    {{ summary.blocked_multiple_reason }} Multi-sanksi
                </span>
                <span class="text-xs text-rose-600/70" v-else>Butuh Verifikasi</span>
            </div>
            <!-- Sub-breakdown if blocked exist -->
            <div class="mt-3 pt-3 border-t border-rose-100/50 flex justify-between text-xs text-rose-600" v-if="summary.blocked_students > 0">
                <span>SPP: {{ summary.blocked_by_bill }}</span>
                <span>BK: {{ summary.blocked_by_bk }}</span>
            </div>
        </div>

        <!-- Warning -->
        <div class="bg-white border border-amber-100 rounded-2xl p-5 shadow-sm bg-gradient-to-br from-white to-amber-50/20">
            <div class="text-xs font-semibold text-amber-700 uppercase tracking-wider">Peringatan (Warning)</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-bold text-amber-700">{{ summary.warning_students }}</span>
                <span class="text-xs text-amber-600/70">Perlu Dipantau</span>
            </div>
        </div>
    </div>
</template>
