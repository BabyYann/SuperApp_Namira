<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import NamiraLoader from '@/Components/NamiraLoader.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const loadingFullscreen = ref(false);
const loadingCard = ref(false);
const loadingTable = ref(false);
const loadingButton = ref(false);

const triggerFullscreen = () => {
    loadingFullscreen.value = true;
    setTimeout(() => {
        loadingFullscreen.value = false;
    }, 3000);
};

const triggerCard = () => {
    loadingCard.value = true;
    setTimeout(() => {
        loadingCard.value = false;
    }, 3000);
};

const triggerTable = () => {
    loadingTable.value = true;
    setTimeout(() => {
        loadingTable.value = false;
    }, 3000);
};

const triggerButton = () => {
    loadingButton.value = true;
    setTimeout(() => {
        loadingButton.value = false;
    }, 3000);
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200"
            >
                Dashboard
            </h2>
        </template>

        <!-- Fullscreen Loader Preview -->
        <NamiraLoader 
            :visible="loadingFullscreen" 
            variant="fullscreen" 
            text="Menyiapkan Ruang Kerja Anda..." 
        />

        <div class="py-12 space-y-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Welcome Card -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-3xl border border-gray-100 dark:bg-slate-900 dark:border-slate-800 p-8">
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-2">Selamat Datang di SuperApp Namira</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Dashboard Utama Yayasan Pendidikan Namira</p>
                </div>
            </div>

            <!-- Loader Design System Interactive Preview -->
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-3xl border border-gray-100 dark:bg-slate-900 dark:border-slate-800 p-8">
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-6 border-b border-gray-100 dark:border-slate-800 pb-3">
                        Namira Loading Design System Preview
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Left Panel: Interactive Triggers -->
                        <div class="space-y-6">
                            <h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Uji Interaktif</h5>
                            <div class="flex flex-wrap gap-3">
                                <!-- Trigger Fullscreen -->
                                <button 
                                    @click="triggerFullscreen"
                                    class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs transition-all active:scale-95 cursor-pointer shadow-sm"
                                >
                                    Uji Fullscreen (3s)
                                </button>

                                <!-- Trigger Card -->
                                <button 
                                    @click="triggerCard"
                                    class="px-4 py-2.5 bg-namira-teal hover:bg-teal-700 text-white font-bold rounded-xl text-xs transition-all active:scale-95 cursor-pointer shadow-sm"
                                >
                                    Uji Card Loader (3s)
                                </button>

                                <!-- Trigger Table -->
                                <button 
                                    @click="triggerTable"
                                    class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition-all active:scale-95 cursor-pointer shadow-sm"
                                >
                                    Uji Table Loader (3s)
                                </button>

                                <!-- Trigger Button Loading -->
                                <button 
                                    @click="triggerButton"
                                    :disabled="loadingButton"
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 disabled:bg-teal-600/70 text-white font-bold rounded-xl text-xs transition-all active:scale-95 cursor-pointer shadow-sm min-w-[130px]"
                                >
                                    <NamiraLoader :visible="loadingButton" variant="button" />
                                    <span>{{ loadingButton ? 'Memproses...' : 'Uji Button (3s)' }}</span>
                                </button>
                            </div>

                            <!-- Static Inline Preview -->
                            <div class="pt-6 border-t border-gray-100 dark:border-slate-800 space-y-3">
                                <h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Inline Loader</h5>
                                <div class="flex items-center gap-2 p-4 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-100 dark:border-slate-800/50">
                                    <NamiraLoader :visible="true" variant="inline" size="small" />
                                    <span class="text-xs text-gray-500 font-bold">Membaca file data presensi...</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Panel: Visual Areas -->
                        <div class="space-y-6">
                            <!-- Card Loading Preview -->
                            <div class="relative min-h-[160px] border border-gray-100 dark:border-slate-800 rounded-3xl overflow-hidden bg-slate-50/50 dark:bg-slate-900/30 flex items-center justify-center">
                                <NamiraLoader 
                                    :visible="loadingCard" 
                                    variant="card" 
                                    text="Memuat grafik analitik..." 
                                />
                                <div v-if="!loadingCard" class="text-center p-6">
                                    <p class="text-xs text-gray-400 font-bold">Tekan tombol "Uji Card Loader" untuk simulasi memuat panel</p>
                                </div>
                            </div>

                            <!-- Table Loading Preview -->
                            <div class="relative min-h-[160px] border border-gray-100 dark:border-slate-800 rounded-3xl overflow-hidden bg-slate-50/50 dark:bg-slate-900/30 flex items-center justify-center">
                                <NamiraLoader 
                                    :visible="loadingTable" 
                                    variant="table" 
                                    text="Mengambil baris data absensi..." 
                                />
                                <div v-if="!loadingTable" class="text-center p-6">
                                    <p class="text-xs text-gray-400 font-bold">Tekan tombol "Uji Table Loader" untuk simulasi memuat tabel data</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
