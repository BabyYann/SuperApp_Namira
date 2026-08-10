<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    UserIcon, CalendarIcon, ClockIcon, PlusIcon, HeartIcon, 
    SparklesIcon, PhoneIcon, PhotoIcon, ArrowLeftIcon, 
    TrashIcon, CheckCircleIcon, ChartBarIcon, DocumentTextIcon,
    BuildingOfficeIcon, UserPlusIcon 
} from '@heroicons/vue/24/outline';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    student: Object,
    selectedDate: String,
    attendance: Object,
    logs: Array,
    growthRecords: Array,
    developmentalJournals: Array,
});

const activeTab = ref('timeline'); // timeline | pickups | growth | journals
const currentDate = ref(props.selectedDate);

const changeDate = (e) => {
    router.get(
        route('daycare.children.show', props.student.id),
        { date: e.target.value },
        { preserveState: true }
    );
};

// Modals
const showAddLogModal = ref(false);
const showAddPickupModal = ref(false);
const showAddGrowthModal = ref(false);
const showAddJournalModal = ref(false);

// Forms
const logForm = useForm({
    date: props.selectedDate,
    log_time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':'),
    category: 'activity',
    title: '',
    description: '',
    amount_ml: null,
    portion_eaten: '100%',
    photo: null,
});

const pickupForm = useForm({
    name: '',
    relationship: 'Pengasuh / Nanny',
    phone: '',
    photo: null,
});

const growthForm = useForm({
    measurement_date: new Date().toISOString().split('T')[0],
    weight_kg: '',
    height_cm: '',
    head_circumference_cm: '',
    notes: '',
});

const journalForm = useForm({
    period_month: new Date().toISOString().slice(0, 7),
    gross_motor: '',
    fine_motor: '',
    language_communication: '',
    cognitive: '',
    socio_emotional: '',
    independence: '',
    caregiver_summary: '',
    status: 'published',
});

const submitLog = () => {
    logForm.post(route('daycare.logs.store', props.student.id), {
        onSuccess: () => {
            showAddLogModal.value = false;
            logForm.reset('description', 'photo');
        }
    });
};

const submitPickup = () => {
    pickupForm.post(route('daycare.children.pickups.store', props.student.id), {
        onSuccess: () => {
            showAddPickupModal.value = false;
            pickupForm.reset();
        }
    });
};

const submitGrowth = () => {
    growthForm.post(route('daycare.growth.store', props.student.id), {
        onSuccess: () => {
            showAddGrowthModal.value = false;
            growthForm.reset();
        }
    });
};

const submitJournal = () => {
    journalForm.post(route('daycare.journals.store', props.student.id), {
        onSuccess: () => {
            showAddJournalModal.value = false;
            journalForm.reset();
        }
    });
};

const deleteLog = (logId) => {
    if (confirm('Hapus catatan timeline ini?')) {
        router.delete(route('daycare.logs.destroy', logId));
    }
};

const deletePickup = (pickupId) => {
    if (confirm('Hapus penjemput terotorisasi ini?')) {
        router.delete(route('daycare.children.pickups.destroy', pickupId));
    }
};

const categoryBadgeClass = (cat) => {
    const map = {
        meal: 'bg-amber-100 text-amber-800 border-amber-300',
        snack: 'bg-orange-100 text-orange-800 border-orange-300',
        milk: 'bg-sky-100 text-sky-800 border-sky-300',
        nap_start: 'bg-indigo-100 text-indigo-800 border-indigo-300',
        nap_end: 'bg-purple-100 text-purple-800 border-purple-300',
        diaper: 'bg-teal-100 text-teal-800 border-teal-300',
        activity: 'bg-emerald-100 text-emerald-800 border-emerald-300',
        mood: 'bg-pink-100 text-pink-800 border-pink-300',
        medication: 'bg-rose-100 text-rose-800 border-rose-300',
        incident: 'bg-red-100 text-red-800 border-red-300',
    };
    return map[cat] || 'bg-slate-100 text-slate-800';
};
</script>

<template>
    <Head :title="`Detail Ananda ${student.full_name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link :href="route('daycare.children.index')" class="p-2 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200">
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div>
                        <h2 class="font-extrabold text-2xl text-slate-800 leading-tight">
                            {{ student.full_name }}
                        </h2>
                        <p class="text-xs text-amber-600 font-bold">
                            NIS: {{ student.nis }} • Ortu: {{ student.parent_name }} ({{ student.parent_phone }})
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('daycare.reports.daily', [student.id, { date: currentDate }])"
                        target="_blank"
                        class="px-4 py-2.5 rounded-2xl bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold text-xs flex items-center gap-2 border border-amber-200 transition-all shadow-xs"
                    >
                        <DocumentTextIcon class="w-4 h-4 text-amber-600" />
                        <span>Daily Report Orang Tua</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- Child Profile Header Card -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 flex flex-col md:flex-row gap-6 items-start md:items-center justify-between">
                <div class="flex items-center gap-5">
                    <div class="w-20 h-20 rounded-2xl bg-amber-100 border-2 border-amber-300 overflow-hidden shrink-0 flex items-center justify-center text-amber-800 font-black text-2xl">
                        <img v-if="student.photo" :src="`/storage/${student.photo}`" class="w-full h-full object-cover" />
                        <span v-else>{{ student.full_name.substring(0, 2).toUpperCase() }}</span>
                    </div>

                    <div>
                        <h3 class="font-black text-xl text-slate-900 leading-tight">{{ student.full_name }}</h3>
                        <p v-if="student.daycare_profile?.nickname" class="text-xs text-amber-600 font-bold">
                            Panggilan: "{{ student.daycare_profile.nickname }}"
                        </p>
                        
                        <div class="flex flex-wrap gap-2 mt-2">
                            <span v-if="student.daycare_profile?.blood_type" class="px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 font-bold text-[10px] border border-rose-200">
                                Gol. Darah: {{ student.daycare_profile.blood_type }}
                            </span>
                            <span v-if="student.daycare_profile?.allergies" class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 font-bold text-[10px] border border-amber-200">
                                ⚠️ Alergi: {{ student.daycare_profile.allergies }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Today Attendance Badge Card -->
                <div class="w-full md:w-auto p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-6">
                    <div>
                        <p class="text-[10px] font-extrabold uppercase text-slate-400">Status Check-in Hari Ini</p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="w-3 h-3 rounded-full" :class="attendance?.check_in_time ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300'"></span>
                            <span class="font-black text-sm text-slate-800">
                                {{ attendance?.check_in_time ? `Jam ${attendance.check_in_time.substring(0,5)}` : 'Belum Check-in' }}
                            </span>
                        </div>
                        <p v-if="attendance?.check_in_temp" class="text-xs font-bold text-teal-600 mt-0.5">
                            Suhu Pagi: {{ attendance.check_in_temp }}°C ({{ attendance.check_in_condition }})
                        </p>
                    </div>

                    <Link :href="route('daycare.attendance.index')" class="text-xs font-bold text-amber-600 hover:text-amber-700">
                        Update →
                    </Link>
                </div>
            </div>

            <!-- Tab Navigation Bar -->
            <div class="flex border-b border-slate-200 space-x-8 overflow-x-auto scrollbar-hide">
                <button 
                    @click="activeTab = 'timeline'" 
                    class="pb-3 font-extrabold text-sm transition-all border-b-2 flex items-center gap-2 whitespace-nowrap"
                    :class="activeTab === 'timeline' ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                >
                    <ClockIcon class="w-5 h-5" />
                    <span>Timeline Harian (Care Log)</span>
                </button>

                <button 
                    @click="activeTab = 'pickups'" 
                    class="pb-3 font-extrabold text-sm transition-all border-b-2 flex items-center gap-2 whitespace-nowrap"
                    :class="activeTab === 'pickups' ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                >
                    <UserPlusIcon class="w-5 h-5" />
                    <span>Wali Penjemput Terotorisasi</span>
                </button>

                <button 
                    @click="activeTab = 'growth'" 
                    class="pb-3 font-extrabold text-sm transition-all border-b-2 flex items-center gap-2 whitespace-nowrap"
                    :class="activeTab === 'growth' ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                >
                    <ChartBarIcon class="w-5 h-5" />
                    <span>Grafik Pertumbuhan (BB/TB)</span>
                </button>

                <button 
                    @click="activeTab = 'journals'" 
                    class="pb-3 font-extrabold text-sm transition-all border-b-2 flex items-center gap-2 whitespace-nowrap"
                    :class="activeTab === 'journals' ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                >
                    <DocumentTextIcon class="w-5 h-5" />
                    <span>Jurnal Perkembangan Bulanan</span>
                </button>
            </div>

            <!-- TAB 1: TIMELINE CARE LOG -->
            <div v-if="activeTab === 'timeline'" class="space-y-6">
                <!-- Date Filter & Quick Add -->
                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-xs flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-3">
                        <CalendarIcon class="w-5 h-5 text-amber-500" />
                        <span class="text-xs font-bold text-slate-500">Pilih Tanggal Log:</span>
                        <input type="date" v-model="currentDate" @change="changeDate" class="rounded-xl border-slate-200 text-xs font-bold focus:border-amber-500 focus:ring-amber-500" />
                    </div>

                    <button 
                        @click="showAddLogModal = true"
                        class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-bold text-xs flex items-center gap-2 shadow-sm active:scale-95 transition-all"
                    >
                        <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                        <span>Catat Aktivitas Timeline</span>
                    </button>
                </div>

                <!-- Single-Feed Timeline -->
                <div v-if="logs.length > 0" class="relative pl-6 border-l-2 border-amber-200 space-y-6">
                    <div v-for="log in logs" :key="log.id" class="relative group">
                        <!-- Dot Indicator -->
                        <span class="absolute -left-[31px] top-1.5 w-4 h-4 rounded-full bg-amber-500 border-4 border-white shadow-xs"></span>
                        
                        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-start justify-between gap-4 hover:shadow-md transition-shadow">
                            <div class="space-y-1">
                                <div class="flex items-center gap-3">
                                    <span class="font-black text-sm text-slate-900">{{ log.log_time.substring(0,5) }}</span>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border" :class="categoryBadgeClass(log.category)">
                                        {{ log.title }}
                                    </span>
                                </div>

                                <p v-if="log.description" class="text-sm text-slate-700 font-medium whitespace-pre-line">{{ log.description }}</p>
                                
                                <div v-if="log.amount_ml || log.portion_eaten" class="flex gap-3 text-xs font-bold text-slate-500 pt-1">
                                    <span v-if="log.amount_ml" class="text-sky-600">Volume: {{ log.amount_ml }} ml</span>
                                    <span v-if="log.portion_eaten" class="text-amber-600">Porsi Habis: {{ log.portion_eaten }}</span>
                                </div>

                                <img v-if="log.photo" :src="`/storage/${log.photo}`" class="w-32 h-32 object-cover rounded-xl mt-3 border border-slate-200" />
                            </div>

                            <button @click="deleteLog(log.id)" class="text-slate-300 hover:text-rose-500 p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-white rounded-3xl p-12 text-center border border-slate-100">
                    <ClockIcon class="w-12 h-12 text-slate-300 mx-auto mb-3" />
                    <h4 class="font-extrabold text-base text-slate-700">Belum Ada Catatan Timeline Hari Ini</h4>
                    <p class="text-xs text-slate-400 mt-1 mb-4">Catat aktivitas makan, tidur, minum susu, dan kegiatan ananda sepanjang hari.</p>
                    <button @click="showAddLogModal = true" class="px-4 py-2.5 rounded-xl bg-amber-500 text-white font-bold text-xs">
                        + Tambah Catatan Sekarang
                    </button>
                </div>
            </div>

            <!-- TAB 2: WALI PENJEMPUT TEROTORISASI -->
            <div v-if="activeTab === 'pickups'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-extrabold text-lg text-slate-800">Daftar Penjemput Terotorisasi</h4>
                        <p class="text-xs text-slate-500">Hanya orang yang terdaftar di sini yang diizinkan membawa ananda pulang.</p>
                    </div>

                    <button @click="showAddPickupModal = true" class="px-4 py-2.5 rounded-xl bg-amber-500 text-white font-bold text-xs flex items-center gap-2">
                        <PlusIcon class="w-4 h-4" />
                        <span>Tambah Wali Penjemput</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    <div v-for="pickup in student.authorized_pickups" :key="pickup.id" class="bg-white rounded-3xl border border-slate-100 p-5 shadow-sm flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-amber-50 border border-amber-200 overflow-hidden shrink-0 flex items-center justify-center font-black text-amber-700 text-base">
                                <img v-if="pickup.photo" :src="`/storage/${pickup.photo}`" class="w-full h-full object-cover" />
                                <span v-else>{{ pickup.name.substring(0,2).toUpperCase() }}</span>
                            </div>
                            <div>
                                <h5 class="font-extrabold text-sm text-slate-800">{{ pickup.name }}</h5>
                                <p class="text-xs text-amber-600 font-bold">{{ pickup.relationship }}</p>
                                <p v-if="pickup.phone" class="text-[11px] text-slate-400 mt-0.5">{{ pickup.phone }}</p>
                            </div>
                        </div>

                        <button @click="deletePickup(pickup.id)" class="text-slate-300 hover:text-rose-500 p-2">
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- TAB 3: GRAFIK PERTUMBUHAN -->
            <div v-if="activeTab === 'growth'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-extrabold text-lg text-slate-800">Riwayat & Tren Pertumbuhan (BB, TB, LK)</h4>
                        <p class="text-xs text-slate-500">Pencatatan berkala berat badan, tinggi badan, dan lingkar kepala.</p>
                    </div>

                    <button @click="showAddGrowthModal = true" class="px-4 py-2.5 rounded-xl bg-amber-500 text-white font-bold text-xs flex items-center gap-2">
                        <PlusIcon class="w-4 h-4" />
                        <span>Input Pengukuran Baru</span>
                    </button>
                </div>

                <div v-if="growthRecords.length > 0" class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden p-6">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-extrabold uppercase tracking-wider">
                                <th class="pb-3">Tanggal Ukur</th>
                                <th class="pb-3">Berat (BB)</th>
                                <th class="pb-3">Tinggi (TB)</th>
                                <th class="pb-3">Lingkar Kepala</th>
                                <th class="pb-3">Catatan Petugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="g in growthRecords" :key="g.id" class="font-semibold text-slate-700">
                                <td class="py-3 font-extrabold text-slate-900">{{ g.measurement_date }}</td>
                                <td class="py-3 text-emerald-600 font-bold">{{ g.weight_kg }} kg</td>
                                <td class="py-3 text-sky-600 font-bold">{{ g.height_cm }} cm</td>
                                <td class="py-3 text-purple-600 font-bold">{{ g.head_circumference_cm ? `${g.head_circumference_cm} cm` : '-' }}</td>
                                <td class="py-3 text-slate-500">{{ g.notes || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="bg-white rounded-3xl p-12 text-center border border-slate-100">
                    <ChartBarIcon class="w-12 h-12 text-slate-300 mx-auto mb-3" />
                    <h4 class="font-extrabold text-base text-slate-700">Belum Ada Riwayat Pengukuran Pertumbuhan</h4>
                    <button @click="showAddGrowthModal = true" class="mt-4 px-4 py-2.5 rounded-xl bg-amber-500 text-white font-bold text-xs">
                        + Input Pengukuran Sekarang
                    </button>
                </div>
            </div>

            <!-- TAB 4: JURNAL BULANAN -->
            <div v-if="activeTab === 'journals'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-extrabold text-lg text-slate-800">Jurnal Perkembangan Bulanan</h4>
                        <p class="text-xs text-slate-500">Evaluasi aspek motorik, kognitif, bahasa, dan sosialisasi anak.</p>
                    </div>

                    <button @click="showAddJournalModal = true" class="px-4 py-2.5 rounded-xl bg-amber-500 text-white font-bold text-xs flex items-center gap-2">
                        <PlusIcon class="w-4 h-4" />
                        <span>Tulis Jurnal Bulanan</span>
                    </button>
                </div>

                <div v-if="developmentalJournals.length > 0" class="space-y-4">
                    <div v-for="j in developmentalJournals" :key="j.id" class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h5 class="font-black text-base text-slate-800">Periode Jurnal: {{ j.period_month }}</h5>
                            <span class="px-3 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700">
                                {{ j.status }}
                            </span>
                        </div>

                        <p v-if="j.caregiver_summary" class="text-sm font-semibold text-slate-700">
                            "{{ j.caregiver_summary }}"
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: ADD DAILY LOG -->
        <Modal :show="showAddLogModal" @close="showAddLogModal = false">
            <div class="p-6 space-y-6">
                <h3 class="font-black text-lg text-slate-800">Catat Timeline Harian (Care Log)</h3>
                
                <form @submit.prevent="submitLog" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Jam Log" />
                            <TextInput v-model="logForm.log_time" type="time" required class="w-full" />
                        </div>
                        <div>
                            <InputLabel value="Kategori Timeline" />
                            <select v-model="logForm.category" class="w-full rounded-xl border-slate-200 text-xs font-bold">
                                <option value="meal">🍚 Makan Utama</option>
                                <option value="snack">🍎 Camilan</option>
                                <option value="milk">🍼 Minum Susu / Air</option>
                                <option value="nap_start">😴 Mulai Tidur Siang</option>
                                <option value="nap_end">☀️ Bangun Tidur</option>
                                <option value="diaper">👶 Diaper / Toilet</option>
                                <option value="activity">🎨 Aktivitas & Bermain</option>
                                <option value="mood">😊 Kondisi / Mood</option>
                                <option value="medication">💊 Minum Obat</option>
                                <option value="incident">⚠️ Catatan Kejadian</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Judul Catatan (Opsional)" />
                        <TextInput v-model="logForm.title" type="text" placeholder="e.g. Makan Siang Habis / Bermain Balok" class="w-full" />
                    </div>

                    <div v-if="logForm.category === 'milk'">
                        <InputLabel value="Volume Susu (ml)" />
                        <TextInput v-model="logForm.amount_ml" type="number" placeholder="150" class="w-full" />
                    </div>

                    <div v-if="logForm.category === 'meal' || logForm.category === 'snack'">
                        <InputLabel value="Porsi Dimakan" />
                        <select v-model="logForm.portion_eaten" class="w-full rounded-xl border-slate-200 text-xs font-bold">
                            <option value="100%">100% (Habis Total)</option>
                            <option value="75%">75% (Sebagian Besar)</option>
                            <option value="50%">50% (Setengah Porsi)</option>
                            <option value="25%">25% (Sedikit)</option>
                        </select>
                    </div>

                    <div>
                        <InputLabel value="Deskripsi / Catatan Pengasuh" />
                        <textarea v-model="logForm.description" rows="3" placeholder="Tuliskan catatan aktivitas ananda secara detail..." class="w-full rounded-xl border-slate-200 text-xs font-medium"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="showAddLogModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold">Batal</button>
                        <PrimaryButton :disabled="logForm.processing">Simpan Log</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL: ADD PICKUP -->
        <Modal :show="showAddPickupModal" @close="showAddPickupModal = false">
            <div class="p-6 space-y-4">
                <h3 class="font-black text-lg text-slate-800">Tambah Wali Penjemput Terotorisasi</h3>

                <form @submit.prevent="submitPickup" class="space-y-4">
                    <div>
                        <InputLabel value="Nama Wali Penjemput" />
                        <TextInput v-model="pickupForm.name" type="text" placeholder="Contoh: Mbak Siti (Pengasuh)" required class="w-full" />
                    </div>

                    <div>
                        <InputLabel value="Hubungan / Peran" />
                        <select v-model="pickupForm.relationship" class="w-full rounded-xl border-slate-200 text-xs font-bold">
                            <option value="Ayah / Ibu">Ayah / Ibu Utama</option>
                            <option value="Pengasuh / Nanny">Pengasuh / Nanny Rumah</option>
                            <option value="Supir Keluarga">Supir Keluarga</option>
                            <option value="Paman / Bibi">Paman / Bibi</option>
                            <option value="Kakek / Nenek">Kakek / Nenek</option>
                        </select>
                    </div>

                    <div>
                        <InputLabel value="No. WhatsApp / HP" />
                        <TextInput v-model="pickupForm.phone" type="text" placeholder="081234567890" class="w-full" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="showAddPickupModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold">Batal</button>
                        <PrimaryButton :disabled="pickupForm.processing">Simpan Penjemput</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL: ADD GROWTH -->
        <Modal :show="showAddGrowthModal" @close="showAddGrowthModal = false">
            <div class="p-6 space-y-4">
                <h3 class="font-black text-lg text-slate-800">Pengukuran Pertumbuhan Ananda</h3>

                <form @submit.prevent="submitGrowth" class="space-y-4">
                    <div>
                        <InputLabel value="Tanggal Pengukuran" />
                        <TextInput v-model="growthForm.measurement_date" type="date" required class="w-full" />
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <InputLabel value="Berat (kg)" />
                            <TextInput v-model="growthForm.weight_kg" type="number" step="0.1" placeholder="10.5" required class="w-full" />
                        </div>
                        <div>
                            <InputLabel value="Tinggi (cm)" />
                            <TextInput v-model="growthForm.height_cm" type="number" step="0.1" placeholder="85.0" required class="w-full" />
                        </div>
                        <div>
                            <InputLabel value="Lingkar Kep. (cm)" />
                            <TextInput v-model="growthForm.head_circumference_cm" type="number" step="0.1" placeholder="46.0" class="w-full" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="showAddGrowthModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold">Batal</button>
                        <PrimaryButton :disabled="growthForm.processing">Simpan Pengukuran</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
