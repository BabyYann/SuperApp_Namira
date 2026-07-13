<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    types: Array,
    classrooms: Array,
    // session: Object // injected automatically usually
});

const form = useForm({
    finance_type_id: '',
    billing_period: new Date().toISOString().slice(0, 10), // Today YYYY-MM-DD
    due_date: '',
    target_type: 'class', // class, student, all
    classroom_id: '',
    student_id: '', // Not implemented in UI properly yet (need Search), let's stick to Class/All for now
    description: '',
});

// Auto-fill due date (e.g. +10 days)
const updateDueDate = () => {
    if (form.billing_period) {
        const date = new Date(form.billing_period);
        date.setDate(date.getDate() + 10);
        form.due_date = date.toISOString().slice(0, 10);
        
        // Auto fill description based on selected type
        const selectedType = props.types.find(t => t.id === form.finance_type_id);
        if (selectedType) {
            const periodDate = new Date(form.billing_period);
            const monthName = periodDate.toLocaleString('id-ID', { month: 'long', year: 'numeric' });
            form.description = `${selectedType.name} - ${monthName}`;
        }
    }
};

const submit = () => {
    form.post(route('yayasan.finance.bills.store'));
};
</script>

<template>
    <Head title="Generate Tagihan Massal" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                        Generate Tagihan Massal
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Buat tagihan untuk satu kelas atau angkatan sekaligus.</p>
                </div>
                <Link :href="route('yayasan.finance.bills.index')" class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-600 shadow-sm hover:bg-gray-50 transition-all">
                    Kembali
                </Link>
            </div>
        </template>

        <div class="py-6 max-w-2xl mx-auto">
             <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    
                    <!-- 1. Pilih Jenis Tagihan -->
                    <div class="space-y-4 pt-2 pb-6 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center text-xs">1</span>
                            Jenis & Periode
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <InputLabel for="finance_type_id" value="Jenis Tagihan (Pos Bayar) *" />
                                <select id="finance_type_id" v-model="form.finance_type_id" @change="updateDueDate" class="w-full mt-1 border-gray-300 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-gray-50/50" required>
                                    <option value="" disabled>Pilih Jenis Tagihan...</option>
                                    <option v-for="type in types" :key="type.id" :value="type.id">
                                        {{ type.name }} (Rp {{ new Intl.NumberFormat('id-ID').format(type.amount) }})
                                    </option>
                                </select>
                                <InputError :message="form.errors.finance_type_id" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="billing_period" value="Tanggal Tagihan *" />
                                <input type="date" id="billing_period" v-model="form.billing_period" @change="updateDueDate" class="w-full mt-1 border-gray-300 rounded-xl focus:ring-namira-teal focus:border-namira-teal" required>
                                <InputError :message="form.errors.billing_period" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="due_date" value="Jatuh Tempo *" />
                                <input type="date" id="due_date" v-model="form.due_date" class="w-full mt-1 border-gray-300 rounded-xl focus:ring-namira-teal focus:border-namira-teal" required>
                                <InputError :message="form.errors.due_date" class="mt-1" />
                            </div>
                            
                            <div class="md:col-span-2">
                                <InputLabel for="description" value="Keterangan Tagihan *" />
                                <TextInput id="description" v-model="form.description" class="w-full mt-1 font-medium" placeholder="Contoh: SPP Januari 2025" required />
                                <InputError :message="form.errors.description" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- 2. Target Siswa -->
                    <div class="space-y-4 pt-2">
                        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center text-xs">2</span>
                            Target Penerima
                        </h3>
                        
                        <div>
                            <InputLabel value="Kirim Tagihan Kepada:" class="mb-2" />
                            <div class="grid grid-cols-2 gap-4">
                                <label class="cursor-pointer border-2 rounded-xl p-4 flex items-center gap-3 hover:bg-gray-50 transition-all" :class="form.target_type === 'class' ? 'border-namira-teal bg-teal-50/30' : 'border-gray-200'">
                                    <input type="radio" v-model="form.target_type" value="class" class="text-namira-teal focus:ring-namira-teal">
                                    <span class="font-bold text-gray-700">Per Kelas</span>
                                </label>
                                <!-- All Students option disabled for safety/complexity for now unless needed -->
                                <!-- 
                                <label class="cursor-pointer border-2 rounded-xl p-4 flex items-center gap-3 hover:bg-gray-50 transition-all" :class="form.target_type === 'all' ? 'border-namira-teal bg-teal-50/30' : 'border-gray-200'">
                                    <input type="radio" v-model="form.target_type" value="all" class="text-namira-teal focus:ring-namira-teal">
                                    <span class="font-bold text-gray-700">Satu Sekolah (Semua)</span>
                                </label>
                                -->
                            </div>
                        </div>

                        <div v-if="form.target_type === 'class'" class="animate-in fade-in slide-in-from-top-2">
                            <InputLabel for="classroom_id" value="Pilih Kelas *" />
                            <select id="classroom_id" v-model="form.classroom_id" class="w-full mt-1 border-gray-300 rounded-xl focus:ring-namira-teal focus:border-namira-teal bg-white font-bold text-gray-700" required>
                                <option value="" disabled>Pilih Kelas...</option>
                                <option v-for="cls in classrooms" :key="cls.id" :value="cls.id">
                                    {{ cls.name }}
                                </option>
                            </select>
                             <InputError :message="form.errors.classroom_id" class="mt-1" />
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-100 flex justify-end">
                        <PrimaryButton :disabled="form.processing" class="px-8 py-3 text-base rounded-xl shadow-xl shadow-namira-teal/20">
                            <span v-if="!form.processing">Generate Tagihan</span>
                            <span v-else>Memproses...</span>
                        </PrimaryButton>
                    </div>

                </form>
             </div>
        </div>
    </AuthenticatedLayout>
</template>
