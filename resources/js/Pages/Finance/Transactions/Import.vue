<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ArrowLeftIcon, InformationCircleIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    accounts: Array, // Finance Accounts
});

const form = useForm({
    finance_account_id: '',
    file: null,
});

const isDragging = ref(false);

const handleFile = (e) => {
    e.preventDefault();
    isDragging.value = false;
    
    let droppedFiles = e.target.files || e.dataTransfer.files;
    
    if (droppedFiles.length > 0) {
        console.log('File selected:', droppedFiles[0]);
        form.file = droppedFiles[0];
    }
};

const handleDragOver = (e) => {
    e.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = (e) => {
    e.preventDefault();
    isDragging.value = false;
};

const handleDrop = (e) => {
    handleFile(e);
};

const submit = () => {
    if (!form.file) {
        alert('Pilih file terlebih dahulu!');
        return;
    }
    if (!form.finance_account_id) {
        alert('Pilih rekening tujuan!');
        return;
    }
    
    console.log('Submitting form...', form.data());
    
    form.post(route('yayasan.finance.transactions.import.process'), {
        forceFormData: true,
        onError: (errors) => {
            console.error('Inertia Error:', errors);
            alert('Upload Gagal: ' + JSON.stringify(errors));
        },
        onSuccess: () => {
             console.log('Inertia Success');
        },
        onFinish: () => {
            console.log('Inertia Finish');
        }
    });
};
</script>

<template>
    <Head title="Import Mutasi Bank" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                     <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                        Import Mutasi Bank (Auto Reconcile)
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Upload file CSV mutasi bank untuk pelunasan otomatis.</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-2xl mx-auto space-y-6">
            <!-- Toolbar -->
            <div class="flex justify-end">
                <Link :href="route('yayasan.finance.transactions.index')" class="text-gray-500 font-bold text-sm hover:text-gray-700 flex items-center gap-2 transition-colors px-4 py-2 rounded-xl hover:bg-gray-100">
                    <ArrowLeftIcon class="w-5 h-5" />
                    Kembali ke Riwayat
                </Link>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                <!-- Info Section -->
                <div class="bg-blue-50 p-4 rounded-2xl mb-6 flex gap-4 border border-blue-100">
                    <div class="shrink-0 text-blue-500">
                        <InformationCircleIcon class="w-6 h-6" />
                    </div>
                    <div class="text-sm text-blue-800">
                        <h4 class="font-bold mb-1">Cara Kerja Fitur Ini:</h4>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Sistem akan membaca kolom <strong>Deskripsi</strong> untuk mencari <strong>Nomor VA</strong> siswa.</li>
                            <li>Sistem mencari kolom <strong>Kredit</strong> (Uang Masuk) > 1000 rupiah.</li>
                            <li>Jika cocok, uang akan otomatis dipakai untuk membayar tagihan terlama (Waterfall).</li>
                            <li>Jika ada kelebihan bayar, akan disimpan sebagai <strong>Deposit (Excess Amount)</strong>.</li>
                        </ul>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="finance_account_id" value="Pilih Rekening Tujuan Upload *" />
                         <select id="finance_account_id" v-model="form.finance_account_id" class="w-full mt-1 border-gray-200 focus:ring-namira-teal focus:border-namira-teal rounded-xl bg-gray-50/50" required>
                             <option value="" disabled>Pilih Bank Tujuan...</option>
                             <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                 {{ acc.bank_name }} - {{ acc.account_number }} ({{ acc.account_name }})
                             </option>
                         </select>
                         <p class="text-xs text-gray-400 mt-1">Pilih ke rekening mana mutasi ini diupload.</p>
                    </div>

                    <div>
                         <InputLabel value="Upload File Mutasi (.csv, .txt)" class="mb-2" />
                         <label 
                            class="block w-full p-8 border-2 border-dashed rounded-2xl text-center cursor-pointer transition-all duration-200"
                            :class="isDragging ? 'border-namira-teal bg-teal-50 scale-[1.02]' : 'border-gray-300 bg-gray-50/50 hover:border-namira-teal hover:bg-teal-50/10'"
                            @dragover="handleDragOver"
                            @dragleave="handleDragLeave"
                            @drop="handleDrop"
                         >
                            <div v-if="!form.file" class="flex flex-col items-center pointer-events-none">
                                <DocumentTextIcon class="w-10 h-10 text-gray-400 mb-2 transition-colors" :class="isDragging ? 'text-namira-teal' : ''" />
                                <span class="text-sm font-bold text-gray-600">Klik atau Drag & Drop file CSV di sini</span>
                                <span class="text-xs text-gray-400 mt-1">Pastikan format sesuai standar Bank Jatim</span>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center gap-2 pointer-events-none">
                                <span class="text-namira-teal font-bold text-lg">{{ form.file.name }}</span>
                                <span class="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded">Klik atau Drop file lain untuk ganti</span>
                            </div>
                            <input type="file" class="hidden" accept=".csv, .txt" @change="handleFile">
                        </label>
                        <InputError :message="form.errors.file" class="mt-2" />
                    </div>

                    <div class="pt-6 border-t border-gray-100 flex justify-end">
                        <PrimaryButton :disabled="form.processing" class="px-8 py-3 text-base rounded-xl shadow-xl shadow-namira-teal/20 transition-all hover:scale-105 active:scale-95">
                            <span v-if="!form.processing">Proses Rekonsiliasi</span>
                            <span v-else>Sedang Mencocokkan...</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
