<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { 
    CalendarIcon, 
    ArrowLeftIcon,
    ChatBubbleLeftRightIcon,
    CheckCircleIcon,
    ClockIcon,
    MapPinIcon,
    VideoCameraIcon,
    DocumentTextIcon,
    UserCircleIcon,
    TrashIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    session: Object,
});

const form = useForm({
    date: props.session.date,
    time: props.session.time,
    method: props.session.method,
    status: props.session.status,
    notes: props.session.notes || '',
    follow_up_action: props.session.follow_up_action || '',
});

const submit = () => {
    form.put(route('counseling.sessions.update', props.session.id), {
        onSuccess: () => {
             Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Data sesi konseling diperbarui',
                showConfirmButton: false,
                timer: 1500
            });
        }
    });
};

const confirmDelete = () => {
    Swal.fire({
        title: 'Hapus Sesi?',
        text: "Data jadwal ini akan dihapus permanen.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('counseling.sessions.destroy', props.session.id));
        }
    });
};

const methods = [
    { id: 'Offline', label: 'Offline', icon: ChatBubbleLeftRightIcon },
    { id: 'Online', label: 'Online', icon: VideoCameraIcon },
    { id: 'Home Visit', label: 'Home Visit', icon: MapPinIcon },
];

const statuses = [
    { id: 'Scheduled', label: 'Terjadwal', class: 'text-blue-600' },
    { id: 'Completed', label: 'Selesai', class: 'text-green-600' },
    { id: 'Cancelled', label: 'Dibatalkan', class: 'text-red-600' },
];
</script>

<template>
    <Head title="Kelola Sesi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('counseling.sessions.index')" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-white/50 transition-colors">
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h2 class="font-bold text-xl text-slate-800 leading-tight">Kelola Sesi Konseling</h2>
                    <p class="text-sm text-slate-500">Update status dan hasil konseling.</p>
                </div>
            </div>
        </template>

        <div class="py-6 min-h-screen pb-20">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Left Column: Session Info (Read/Edit Minor) -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Student Card -->
                        <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-lg border border-white/50 p-6 flex flex-col items-center text-center">
                            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-2xl shadow-indigo-200 shadow-lg mb-4">
                                {{ props.session.student_name.charAt(0) }}
                            </div>
                            <h3 class="font-bold text-lg text-slate-800">{{ props.session.student_name }}</h3>
                            <p class="text-sm text-slate-500 mb-4">{{ props.session.student_classroom }}</p>
                            
                            <div class="w-full border-t border-slate-100 my-2"></div>
                             
                            <div class="w-full text-left space-y-4 mt-2">
                                 <div>
                                    <label class="text-xs font-bold text-slate-400 uppercase">Waktu</label>
                                    <input type="date" v-model="form.date" class="w-full mt-1 rounded-xl border-slate-200 bg-white/50 text-sm font-bold text-slate-700">
                                    <input type="time" v-model="form.time" class="w-full mt-2 rounded-xl border-slate-200 bg-white/50 text-sm font-bold text-slate-700">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-400 uppercase">Metode</label>
                                    <select v-model="form.method" class="w-full mt-1 rounded-xl border-slate-200 bg-white/50 text-sm font-bold text-slate-700">
                                        <option v-for="m in methods" :key="m.id" :value="m.id">{{ m.label }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Status Card -->
                        <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-lg border border-white/50 p-6">
                            <h4 class="font-bold text-slate-700 border-b border-slate-100 pb-2 mb-4">Status Sesi</h4>
                            <div class="space-y-2">
                                <label v-for="s in statuses" :key="s.id" class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer hover:bg-slate-50 transition-all" :class="form.status === s.id ? 'border-purple-200 bg-purple-50' : 'border-slate-100'">
                                    <input type="radio" v-model="form.status" :value="s.id" class="text-purple-600 focus:ring-purple-500">
                                    <span class="font-bold text-sm" :class="s.class">{{ s.label }}</span>
                                </label>
                            </div>
                        </div>
                        
                         <button @click="confirmDelete" type="button" class="w-full py-3 text-red-500 font-bold text-sm bg-red-50 hover:bg-red-100 rounded-2xl transition-colors flex items-center justify-center gap-2">
                            <TrashIcon class="w-5 h-5" />
                            Hapus Sesi Ini
                        </button>
                    </div>

                    <!-- Right Column: Notes (The Meat) -->
                    <div class="lg:col-span-2">
                         <form @submit.prevent="submit" class="bg-white/80 backdrop-blur-xl rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-white/50 overflow-hidden relative min-h-[500px] flex flex-col">
                             <div class="p-8 flex-1 space-y-6">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="bg-purple-100 p-2 rounded-lg text-purple-600">
                                        <DocumentTextIcon class="w-6 h-6" />
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-800">Catatan Konseling</h3>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-bold text-slate-600 ml-1">Hasil Diskusi / Observasi</label>
                                    <textarea 
                                        v-model="form.notes" 
                                        rows="12" 
                                        placeholder="Tuliskan poin-poin penting pembicaraan, respon siswa, dan hasil observasi..." 
                                        class="w-full rounded-2xl border-slate-200 focus:border-purple-500 focus:ring-purple-500 bg-slate-50/50 p-4 text-slate-700 leading-relaxed font-medium placeholder:font-normal"
                                    ></textarea>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-bold text-slate-600 ml-1">Rencana Tindak Lanjut (Follow Up)</label>
                                    <textarea 
                                        v-model="form.follow_up_action" 
                                        rows="3" 
                                        placeholder="Apa langkah selanjutnya? (Misal: Pemanggilan Orang Tua, Konseling Lanjutan, dll)" 
                                        class="w-full rounded-2xl border-slate-200 focus:border-purple-500 focus:ring-purple-500 bg-slate-50/50 p-4 text-slate-700 font-medium placeholder:font-normal"
                                    ></textarea>
                                </div>
                             </div>

                             <div class="bg-slate-50 p-6 flex justify-end gap-4 border-t border-slate-100">
                                <Link :href="route('counseling.sessions.index')" class="px-6 py-3 rounded-2xl text-sm font-bold text-slate-500 hover:bg-slate-200 transition-colors">
                                    Kembali
                                </Link>
                                <button type="submit" :disabled="form.processing" class="px-8 py-3 bg-purple-600 text-white font-bold rounded-2xl shadow-lg shadow-purple-200 hover:bg-purple-700 hover:-translate-y-1 transition-all flex items-center gap-2">
                                    <CheckCircleIcon class="w-5 h-5" />
                                    Simpan Perubahan
                                </button>
                             </div>
                         </form>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
