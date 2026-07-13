<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { 
    CalendarIcon, 
    ArrowLeftIcon,
    UserCircleIcon,
    ChatBubbleLeftRightIcon,
    ClockIcon,
    MapPinIcon,
    VideoCameraIcon,
    PencilSquareIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    students: Array,
    classrooms: Array,
});

const form = useForm({
    student_id: '',
    date: new Date().toISOString().slice(0, 10),
    time: '08:00',
    method: 'Offline',
    notes: '',
});

const selectedClassroom = ref('');

// Filter Students based on selected Classroom
const filteredStudents = computed(() => {
    if (!selectedClassroom.value) return [];
    return props.students.filter(s => s.classroom_id === selectedClassroom.value);
});

const submit = () => {
    form.post(route('counseling.sessions.store'), {
        onSuccess: () => {
            form.reset();
            selectedClassroom.value = '';
        }
    });
};

const methods = [
    { id: 'Offline', label: 'Tatap Muka (Offline)', icon: ChatBubbleLeftRightIcon },
    { id: 'Online', label: 'Daring (Online)', icon: VideoCameraIcon },
    { id: 'Home Visit', label: 'Kunjungan Rumah', icon: MapPinIcon },
];
</script>

<template>
    <Head title="Jadwalkan Konseling" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('counseling.sessions.index')" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-white/50 transition-colors">
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h2 class="font-bold text-xl text-slate-800 leading-tight">Buat Jadwal Konseling</h2>
                    <p class="text-sm text-slate-500">Jadwalkan pertemuan bimbingan dengan siswa.</p>
                </div>
            </div>
        </template>

        <div class="py-6 min-h-screen pb-20">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <form @submit.prevent="submit" class="bg-white/80 backdrop-blur-xl rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-white/50 overflow-hidden relative">
                     
                     <div class="p-8 md:p-10 space-y-8">

                        <!-- 1. Who? -->
                        <div class="space-y-5">
                             <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-indigo-200">1</div>
                                <h3 class="text-lg font-bold text-slate-700">Pilih Siswa</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pl-2 md:pl-12">
                                <!-- Classroom Select -->
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 ml-1 uppercase tracking-wider">Filter Kelas</label>
                                    <div class="relative">
                                        <select v-model="selectedClassroom" class="w-full rounded-2xl border-slate-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50 text-slate-700 font-bold py-3">
                                            <option value="" disabled>-- Pilih Kelas --</option>
                                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Student Select -->
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 ml-1 uppercase tracking-wider">Nama Siswa</label>
                                    <div class="relative">
                                        <select v-model="form.student_id" :disabled="!selectedClassroom" class="w-full rounded-2xl border-slate-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50 text-slate-700 font-bold py-3 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <option value="" disabled>{{ selectedClassroom ? '-- Pilih Siswa --' : '-- Pilih Kelas Dulu --' }}</option>
                                            <option v-for="s in filteredStudents" :key="s.id" :value="s.id">{{ s.label }}</option>
                                        </select>
                                    </div>
                                    <InputError :message="form.errors.student_id" class="ml-1" />
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-dashed border-slate-200"></div>

                        <!-- 2. When & How? -->
                         <div class="space-y-5">
                             <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-blue-400 to-cyan-500 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-cyan-200">2</div>
                                <h3 class="text-lg font-bold text-slate-700">Waktu & Metode</h3>
                            </div>

                            <div class="space-y-6 pl-2 md:pl-12">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Date -->
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-slate-500 ml-1 uppercase tracking-wider">Tanggal</label>
                                        <div class="relative">
                                            <input type="date" v-model="form.date" class="w-full rounded-2xl border-slate-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50 font-medium py-3">
                                        </div>
                                         <InputError :message="form.errors.date" />
                                    </div>

                                    <!-- Time -->
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-slate-500 ml-1 uppercase tracking-wider">Jam</label>
                                        <div class="relative">
                                            <input type="time" v-model="form.time" class="w-full rounded-2xl border-slate-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50 font-medium py-3">
                                        </div>
                                         <InputError :message="form.errors.time" />
                                    </div>
                                </div>

                                <!-- Method -->
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 ml-1 uppercase tracking-wider">Metode Konseling</label>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <button 
                                            v-for="m in methods" 
                                            :key="m.id"
                                            type="button"
                                            @click="form.method = m.id"
                                            class="py-3 px-4 rounded-xl border flex items-center justify-center gap-2 transition-all duration-200 font-bold text-sm"
                                            :class="form.method === m.id ? 'bg-purple-600 text-white border-purple-600 shadow-md transform scale-[1.02]' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'"
                                        >
                                            <component :is="m.icon" class="w-5 h-5" />
                                            {{ m.label }}
                                        </button>
                                    </div>
                                    <InputError :message="form.errors.method" />
                                </div>
                            </div>
                         </div>

                         <div class="border-t border-dashed border-slate-200"></div>

                         <!-- 3. Notes -->
                         <div class="space-y-5">
                             <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-teal-400 to-emerald-500 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-emerald-200">3</div>
                                <h3 class="text-lg font-bold text-slate-700">Catatan Awal (Opsional)</h3>
                            </div>

                            <div class="pl-2 md:pl-12">
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 ml-1 uppercase tracking-wider">Topik / Agenda</label>
                                    <div class="relative group">
                                         <div class="absolute top-3 left-4 pointer-events-none">
                                            <PencilSquareIcon class="w-5 h-5 text-gray-400" />
                                        </div>
                                        <textarea v-model="form.notes" rows="3" placeholder="Sebutkan topik yang akan dibahas..." class="w-full pl-12 rounded-2xl border-slate-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50 py-3 font-medium text-slate-700 placeholder:font-normal"></textarea>
                                    </div>
                                </div>
                            </div>
                         </div>

                    </div>

                    <!-- Footer -->
                    <div class="bg-white/50 backdrop-blur-md px-8 py-6 border-t border-slate-100 flex justify-end gap-3 md:px-10">
                        <Link :href="route('counseling.sessions.index')" class="px-6 py-3 rounded-2xl text-sm font-bold text-slate-500 hover:bg-slate-100 transition-colors">
                            Batal
                        </Link>
                        <button :disabled="form.processing" type="submit" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-sm font-bold rounded-2xl shadow-lg shadow-purple-500/30 hover:shadow-purple-500/50 hover:-translate-y-1 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                             <CalendarIcon class="w-5 h-5" />
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Jadwalkan Sesi</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
