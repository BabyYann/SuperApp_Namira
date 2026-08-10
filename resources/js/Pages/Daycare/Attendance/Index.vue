<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    ClockIcon, CalendarIcon, CheckCircleIcon, UserIcon, 
    ArrowRightOnRectangleIcon, ArrowLeftOnRectangleIcon, SparklesIcon 
} from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    date: String,
    children: Array,
});

const currentDate = ref(props.date);

const changeDate = (e) => {
    router.get(route('daycare.attendance.index'), { date: e.target.value }, { preserveState: true });
};

// Modals
const showCheckInModal = ref(false);
const showCheckOutModal = ref(false);
const selectedChild = ref(null);

// Forms
const checkInForm = useForm({
    student_id: null,
    date: props.date,
    check_in_time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':'),
    dropped_off_by: '',
    check_in_temp: 36.5,
    check_in_condition: 'Sehat & Ceria',
    check_in_notes: '',
});

const checkOutForm = useForm({
    student_id: null,
    date: props.date,
    check_out_time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':'),
    picked_up_by: '',
    authorized_pickup_id: null,
    check_out_notes: '',
});

const openCheckIn = (child) => {
    selectedChild.value = child;
    checkInForm.student_id = child.id;
    checkInForm.dropped_off_by = child.parent_name || 'Orang Tua Utama';
    showCheckInModal.value = true;
};

const openCheckOut = (child) => {
    selectedChild.value = child;
    checkOutForm.student_id = child.id;
    checkOutForm.picked_up_by = child.parent_name || 'Orang Tua Utama';
    showCheckOutModal.value = true;
};

const submitCheckIn = () => {
    checkInForm.post(route('daycare.attendance.check-in'), {
        onSuccess: () => {
            showCheckInModal.value = false;
        }
    });
};

const submitCheckOut = () => {
    checkOutForm.post(route('daycare.attendance.check-out'), {
        onSuccess: () => {
            showCheckOutModal.value = false;
        }
    });
};
</script>

<template>
    <Head title="Presensi & Handover Daycare" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="font-extrabold text-2xl bg-gradient-to-r from-teal-600 to-emerald-600 bg-clip-text text-transparent leading-tight flex items-center gap-2">
                        <ClockIcon class="w-8 h-8 text-teal-600" />
                        <span>Presensi & Handover Ananda</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">Dashboard Check-in kedatangan pagi & Check-out kepulangan sore.</p>
                </div>

                <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-2xl border border-slate-200 shadow-xs">
                    <CalendarIcon class="w-4 h-4 text-teal-600" />
                    <input type="date" v-model="currentDate" @change="changeDate" class="border-none text-xs font-extrabold p-0 focus:ring-0 text-slate-800" />
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- Children Handover Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <div 
                    v-for="child in children" 
                    :key="child.id"
                    class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden p-6 flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-teal-50 border border-teal-200 overflow-hidden shrink-0 flex items-center justify-center text-teal-700 font-black text-lg">
                                <img v-if="child.photo" :src="`/storage/${child.photo}`" class="w-full h-full object-cover" />
                                <span v-else>{{ child.full_name.substring(0,2).toUpperCase() }}</span>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-sm text-slate-800 truncate">{{ child.full_name }}</h4>
                                <p class="text-[11px] text-slate-400">Ortu: {{ child.parent_name }}</p>
                            </div>
                        </div>

                        <!-- Check-in Info Box -->
                        <div class="p-3 rounded-2xl bg-slate-50 space-y-1.5 text-xs mb-4">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 font-bold text-[10px] uppercase">Kedatangan (Check-in)</span>
                                <span v-if="child.attendance?.check_in_time" class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                                    {{ child.attendance.check_in_time.substring(0,5) }}
                                </span>
                                <span v-else class="text-slate-400 font-bold text-[10px]">Belum</span>
                            </div>

                            <div v-if="child.attendance?.check_in_temp" class="text-slate-600 font-medium">
                                Suhu: <span class="font-extrabold text-teal-600">{{ child.attendance.check_in_temp }}°C</span> ({{ child.attendance.check_in_condition }})
                            </div>
                        </div>

                        <!-- Check-out Info Box -->
                        <div class="p-3 rounded-2xl bg-slate-50 space-y-1.5 text-xs mb-4">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 font-bold text-[10px] uppercase">Kepulangan (Check-out)</span>
                                <span v-if="child.attendance?.check_out_time" class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px]">
                                    {{ child.attendance.check_out_time.substring(0,5) }}
                                </span>
                                <span v-else class="text-slate-400 font-bold text-[10px]">Belum</span>
                            </div>
                        </div>
                    </div>

                    <!-- Handover Buttons -->
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                        <button 
                            @click="openCheckIn(child)"
                            class="px-3 py-2 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1"
                            :class="child.attendance?.check_in_time ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-emerald-500 text-white hover:bg-emerald-600 shadow-xs'"
                        >
                            <ArrowRightOnRectangleIcon class="w-4 h-4" />
                            <span>{{ child.attendance?.check_in_time ? 'Edit In' : 'Check-in' }}</span>
                        </button>

                        <button 
                            @click="openCheckOut(child)"
                            :disabled="!child.attendance?.check_in_time"
                            class="px-3 py-2 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1 disabled:opacity-40 disabled:cursor-not-allowed"
                            :class="child.attendance?.check_out_time ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-amber-500 text-white hover:bg-amber-600 shadow-xs'"
                        >
                            <ArrowLeftOnRectangleIcon class="w-4 h-4" />
                            <span>{{ child.attendance?.check_out_time ? 'Edit Out' : 'Check-out' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL CHECK-IN -->
        <Modal :show="showCheckInModal" @close="showCheckInModal = false">
            <div class="p-6 space-y-4">
                <h3 class="font-black text-lg text-slate-800">Check-in Kedatangan: {{ selectedChild?.full_name }}</h3>

                <form @submit.prevent="submitCheckIn" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Jam Kedatangan" />
                            <TextInput v-model="checkInForm.check_in_time" type="time" required class="w-full" />
                        </div>
                        <div>
                            <InputLabel value="Suhu Tubuh (°C)" />
                            <TextInput v-model="checkInForm.check_in_temp" type="number" step="0.1" placeholder="36.5" required class="w-full" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Diantar Oleh Siapa?" />
                        <TextInput v-model="checkInForm.dropped_off_by" type="text" placeholder="Bpk. Hendra (Ayah)" required class="w-full" />
                    </div>

                    <div>
                        <InputLabel value="Kondisi Fisik & Kesehatan Pagi Ini" />
                        <select v-model="checkInForm.check_in_condition" class="w-full rounded-xl border-slate-200 text-xs font-bold">
                            <option value="Sehat & Ceria">Sehat & Ceria</option>
                            <option value="Batuk / Pilek Ringan">Batuk / Pilek Ringan</option>
                            <option value="Demam Ringan">Demam Ringan</option>
                            <option value="Ada Lebam / Luka dari Rumah">Ada Lebam / Luka dari Rumah</option>
                            <option value="Kurang Tidur / Rewel">Kurang Tidur / Rewel</option>
                        </select>
                    </div>

                    <div>
                        <InputLabel value="Catatan Titipan / Instruksi Ortu" />
                        <textarea v-model="checkInForm.check_in_notes" rows="2" placeholder="Misal: Titip obat pusing jam 12.00, atau minta baju tidak dicuci..." class="w-full rounded-xl border-slate-200 text-xs"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="showCheckInModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold">Batal</button>
                        <PrimaryButton :disabled="checkInForm.processing">Simpan Check-in Pagi</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL CHECK-OUT -->
        <Modal :show="showCheckOutModal" @close="showCheckOutModal = false">
            <div class="p-6 space-y-4">
                <h3 class="font-black text-lg text-slate-800">Check-out Kepulangan: {{ selectedChild?.full_name }}</h3>

                <form @submit.prevent="submitCheckOut" class="space-y-4">
                    <div>
                        <InputLabel value="Jam Kepulangan" />
                        <TextInput v-model="checkOutForm.check_out_time" type="time" required class="w-full" />
                    </div>

                    <div>
                        <InputLabel value="Penjemput Terotorisasi" />
                        <select v-model="checkOutForm.authorized_pickup_id" class="w-full rounded-xl border-slate-200 text-xs font-bold">
                            <option :value="null">Pilih Penjemput Terdaftar...</option>
                            <option v-for="pickup in selectedChild?.authorized_pickups" :key="pickup.id" :value="pickup.id">
                                {{ pickup.name }} ({{ pickup.relationship }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <InputLabel value="Nama Penjemput (Jika Tidak Ada di Daftar)" />
                        <TextInput v-model="checkOutForm.picked_up_by" type="text" placeholder="Bpk. Hendra (Ayah)" required class="w-full" />
                    </div>

                    <div>
                        <InputLabel value="Catatan Serah Terima Kepulangan" />
                        <textarea v-model="checkOutForm.check_out_notes" rows="2" placeholder="Misal: Barang-barang sudah dimasukkan tas, anak pulang dalam kondisi bersih..." class="w-full rounded-xl border-slate-200 text-xs"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="showCheckOutModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold">Batal</button>
                        <PrimaryButton :disabled="checkOutForm.processing">Simpan Check-out Kepulangan</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
