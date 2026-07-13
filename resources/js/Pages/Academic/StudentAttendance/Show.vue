<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, watch, computed } from 'vue';
import { 
    ChevronLeftIcon, ChevronRightIcon, CheckIcon, 
    ExclamationCircleIcon, ArrowPathIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    classroom: Object,
    students: Array,
    attendances: Object,
    date: String,
    history: Object, // { student_id: { A: 2, S: 1 } }
});

const form = useForm({
    attendances: [],
    date: props.date, // Include date in submission
});

// --- 1. Initialization & Reactivity ---
const initForm = () => {
    form.attendances = props.students.map(student => {
        const existing = props.attendances[student.id] || props.attendances[String(student.id)];
        return {
            student_id: student.id,
            status: existing ? existing.status : '', // Empty by default for "Mark Remaining" logic
            note: existing ? existing.note : '',
        };
    });
};

onMounted(() => {
    initForm();
});

watch(() => props.attendances, () => {
    initForm();
}, { deep: true });

// Sync form.date when navigating dates
watch(() => props.date, (newDate) => {
    form.date = newDate;
    initForm();
});

// --- 2. Scoreboard Logic ---
const summary = computed(() => {
    const stats = { H: 0, S: 0, I: 0, A: 0, Unset: 0 };
    form.attendances.forEach(item => {
        if (item.status && stats[item.status] !== undefined) {
            stats[item.status]++;
        } else {
            stats['Unset']++;
        }
    });
    return stats;
});

// --- 3. Date Navigation ---
const changeDate = (days) => {
    const newDate = new Date(props.date);
    newDate.setDate(newDate.getDate() + days);
    const formatted = newDate.toISOString().split('T')[0];
    
    router.get(route('yayasan.student-attendance.show', props.classroom.id), { 
        date: formatted 
    }, {
        preserveState: false,
        preserveScroll: true,
    });
};

// --- 4. Smart Actions ---
const markRemainingPresent = () => {
    form.attendances.forEach(item => {
        if (!item.status) {
            item.status = 'H';
        }
    });
};

const submit = () => {
    form.post(route('yayasan.student-attendance.store', props.classroom.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Toast handled by layout usually
        },
        onError: (errors) => {
            alert('Gagal menyimpan. Periksa koneksi atau inputan.');
        }
    });
};

// --- 5. UI Helpers ---
const statusColors = {
    'H': 'bg-emerald-100 text-emerald-700 border-emerald-200 ring-emerald-500',
    'S': 'bg-blue-100 text-blue-700 border-blue-200 ring-blue-500',
    'I': 'bg-amber-100 text-amber-700 border-amber-200 ring-amber-500',
    'A': 'bg-rose-100 text-rose-700 border-rose-200 ring-rose-500',
};

const rowColors = {
    'H': 'hover:bg-emerald-50/50',
    'S': 'bg-blue-50/30 hover:bg-blue-50/60',
    'I': 'bg-amber-50/30 hover:bg-amber-50/60',
    'A': 'bg-rose-50/30 hover:bg-rose-50/60',
};

const quickNotes = ['Sakit Demam', 'Izin Keluarga', 'Tanpa Keterangan', 'Terlambat'];

const addNote = (item, note) => {
    item.note = note;
};

const getHistoryWarning = (studentId) => {
    const record = props.history?.[studentId];
    if (!record) return null;
    
    const alpha = record.A || 0;
    const sakit = record.S || 0;
    const izin = record.I || 0;
    
    if (alpha >= 3) return { type: 'danger', text: `Waspada: ${alpha}x Alpha bulan ini!` };
    if ((sakit + izin) >= 5) return { type: 'warning', text: `Info: ${sakit+izin}x Absen bulan ini.` };
    return null;
};
</script>

<template>
    <Head :title="`Absensi ${classroom.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                <!-- Title & Date -->
                <div class="flex flex-col gap-1">
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Kelas {{ classroom.name }}</h2>
                    <div class="flex items-center gap-2 text-slate-500 text-sm">
                        <button @click="changeDate(-1)" class="hover:text-slate-800 transition-colors p-1 rounded-md hover:bg-slate-100">
                            <ChevronLeftIcon class="h-4 w-4" />
                        </button>
                        <span class="font-medium border-b border-dotted border-slate-400 cursor-help" title="Ganti Tanggal">
                            {{ new Date(date).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
                        </span>
                        <button @click="changeDate(1)" class="hover:text-slate-800 transition-colors p-1 rounded-md hover:bg-slate-100">
                            <ChevronRightIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Compact Stats Bar -->
                <div class="flex flex-wrap gap-3">
                    <div class="flex items-center gap-3 px-4 py-2 bg-white rounded-xl border border-slate-200 shadow-sm">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Hadir</span>
                            <span class="text-lg font-bold text-emerald-600 leading-none">{{ summary.H }}</span>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 font-bold text-xs">H</div>
                    </div>
                    <div class="flex items-center gap-3 px-4 py-2 bg-white rounded-xl border border-slate-200 shadow-sm">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sakit</span>
                            <span class="text-lg font-bold text-blue-600 leading-none">{{ summary.S }}</span>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold text-xs">S</div>
                    </div>
                    <div class="flex items-center gap-3 px-4 py-2 bg-white rounded-xl border border-slate-200 shadow-sm">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Izin</span>
                            <span class="text-lg font-bold text-amber-600 leading-none">{{ summary.I }}</span>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-amber-50 flex items-center justify-center text-amber-600 font-bold text-xs">I</div>
                    </div>
                    <div class="flex items-center gap-3 px-4 py-2 bg-white rounded-xl border border-slate-200 shadow-sm">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Alpha</span>
                            <span class="text-lg font-bold text-rose-600 leading-none">{{ summary.A }}</span>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-rose-50 flex items-center justify-center text-rose-600 font-bold text-xs">A</div>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                
                <!-- Toolbar -->
                <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/50">
                    <div class="flex items-center gap-4 text-sm text-slate-500">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                            Total: <span class="font-bold text-slate-800">{{ students.length }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                            Belum Absen: <span class="font-bold text-slate-800">{{ summary.Unset }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2 w-full sm:w-auto">
                        <button @click="markRemainingPresent" type="button" class="flex-1 sm:flex-none px-3 py-2 bg-white text-slate-700 text-xs font-bold rounded-lg border border-slate-300 hover:bg-slate-50 hover:border-slate-400 transition-all shadow-sm flex items-center justify-center gap-2">
                            <CheckIcon class="h-4 w-4 text-emerald-500" />
                            Isi Sisa Hadir
                        </button>
                        <button @click="submit" :disabled="form.processing" class="flex-1 sm:flex-none px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-lg shadow-md hover:bg-slate-800 transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                            <span v-if="!form.processing">Simpan Perubahan</span>
                            <span v-else>Menyimpan...</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-slate-700 font-bold uppercase text-[11px] tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 w-10 text-center">No</th>
                                <th class="px-4 py-3 w-14">Foto</th>
                                <th class="px-4 py-3">Nama Siswa</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr 
                                v-for="(item, index) in form.attendances" 
                                :key="item.student_id" 
                                class="transition-colors duration-200"
                                :class="item.status ? rowColors[item.status] : 'hover:bg-slate-50'"
                            >
                                <td class="px-4 py-3 text-center text-slate-400 font-mono text-xs">{{ index + 1 }}</td>
                                <td class="px-4 py-3">
                                    <img 
                                        :src="students.find(s => s.id === item.student_id)?.photo 
                                            ? `/storage/${students.find(s => s.id === item.student_id).photo}` 
                                            : `https://ui-avatars.com/api/?name=${encodeURIComponent(students.find(s => s.id === item.student_id)?.full_name || 'S')}&background=0d9488&color=fff&size=64`" 
                                        class="h-9 w-9 rounded-full object-cover border border-slate-200 shadow-sm"
                                        alt="Avatar"
                                    >
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-800 text-sm">
                                            {{ students.find(s => s.id === item.student_id)?.full_name }}
                                        </span>
                                        <!-- History Warning -->
                                        <div v-if="getHistoryWarning(item.student_id)" class="group relative">
                                            <ExclamationCircleIcon class="h-4 w-4 text-rose-500 cursor-help" />
                                            <div class="absolute left-full ml-2 top-1/2 -translate-y-1/2 w-48 bg-slate-800 text-white text-xs p-2 rounded shadow-lg opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-10">
                                                {{ getHistoryWarning(item.student_id).text }}
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-0.5">NIS: {{ students.find(s => s.id === item.student_id)?.nis || '-' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-center gap-1">
                                        <label v-for="code in ['H', 'S', 'I', 'A']" :key="code" class="cursor-pointer group">
                                            <input type="radio" v-model="item.status" :value="code" class="sr-only peer">
                                            <div 
                                                class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-[10px] border transition-all duration-200 shadow-sm"
                                                :class="[
                                                    item.status === code 
                                                        ? statusColors[code] + ' scale-105 ring-2 ring-offset-1' 
                                                        : 'bg-white border-slate-200 text-slate-400 hover:border-slate-300 hover:bg-slate-50'
                                                ]"
                                            >
                                                {{ code }}
                                            </div>
                                        </label>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="space-y-1.5">
                                        <input 
                                            v-model="item.note" 
                                            type="text" 
                                            placeholder="Catatan..." 
                                            class="w-full text-xs border-slate-200 rounded-lg focus:ring-slate-500 focus:border-slate-500 bg-white/50 py-1.5"
                                        >
                                        <!-- Quick Chips -->
                                        <div v-if="item.status && item.status !== 'H'" class="flex flex-wrap gap-1">
                                            <button 
                                                v-for="note in quickNotes" 
                                                :key="note"
                                                @click="addNote(item, note)"
                                                type="button"
                                                class="px-2 py-0.5 bg-slate-100 text-slate-500 text-[10px] rounded-full hover:bg-slate-200 transition-colors"
                                            >
                                                {{ note }}
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Mobile Floating Save Button -->
            <div class="fixed bottom-6 right-6 md:hidden z-50">
                <button @click="submit" :disabled="form.processing" class="h-14 w-14 bg-slate-900 text-white rounded-full shadow-xl flex items-center justify-center hover:scale-110 transition-transform active:scale-95">
                    <CheckIcon v-if="!form.processing" class="h-6 w-6" />
                    <ArrowPathIcon v-else class="animate-spin h-6 w-6 text-white" />
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
