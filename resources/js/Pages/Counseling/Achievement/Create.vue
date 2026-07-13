<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    ArrowLeftIcon, 
    TrophyIcon, 
    DocumentArrowUpIcon,
    CameraIcon,
    UserIcon,
    CalendarIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    classrooms: Array,
    students: Array,
});

const form = useForm({
    date: new Date().toISOString().split('T')[0],
    student_id: '',
    title: '',
    level: 'Sekolah',
    description: '',
    proof_file: null,
});

const selectedClassroom = ref('');
const filteredStudents = computed(() => {
    if (!selectedClassroom.value) return [];
    return props.students.filter(s => s.classroom_id === selectedClassroom.value);
});

const submit = () => {
    form.post(route('counseling.achievements.store'));
};
</script>

<template>
    <Head title="Input Prestasi Siswa" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('counseling.achievements.index')" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-white/50 transition-colors">
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h2 class="font-bold text-xl text-slate-800 leading-tight">Input Prestasi Baru</h2>
                    <p class="text-sm text-slate-500">Catat pencapaian siswa untuk database rekam jejak.</p>
                </div>
            </div>
        </template>

        <div class="py-6 min-h-screen pb-20">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <form @submit.prevent="submit" class="bg-white/80 backdrop-blur-xl rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-white/50 overflow-hidden relative">
                    
                    <div class="p-8 md:p-10 space-y-8">

                        <!-- 1. Who? (Student Selection) -->
                        <div class="space-y-5">
                             <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-namira-teal to-teal-600 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-teal-200">1</div>
                                <h3 class="text-lg font-bold text-slate-700">Identitas Siswa</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pl-2 md:pl-12">
                                <!-- Classroom Select -->
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 ml-1 uppercase tracking-wider">Pilih Kelas</label>
                                    <div class="relative">
                                        <select v-model="selectedClassroom" class="w-full rounded-2xl border-slate-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50 text-slate-700 font-bold py-3">
                                            <option value="" disabled>-- Pilih Kelas --</option>
                                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Student Select -->
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 ml-1 uppercase tracking-wider">Pilih Siswa</label>
                                    <div class="relative">
                                        <select v-model="form.student_id" :disabled="!selectedClassroom" class="w-full rounded-2xl border-slate-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50 text-slate-700 font-bold py-3 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <option value="" disabled>-- Pilih Siswa --</option>
                                            <option v-for="s in filteredStudents" :key="s.id" :value="s.id">{{ s.label }}</option>
                                        </select>
                                        <UserIcon class="w-5 h-5 text-gray-400 absolute right-8 top-3.5 pointer-events-none" />
                                    </div>
                                    <p v-if="form.errors.student_id" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.student_id }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-dashed border-slate-200"></div>

                        <!-- 2. What? (Achievement Details) -->
                         <div class="space-y-5">
                             <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-orange-200">2</div>
                                <h3 class="text-lg font-bold text-slate-700">Detail Prestasi</h3>
                            </div>

                            <div class="space-y-6 pl-2 md:pl-12">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Date -->
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-slate-500 ml-1 uppercase tracking-wider">Tanggal</label>
                                        <div class="relative">
                                            <input type="date" v-model="form.date" class="w-full rounded-2xl border-slate-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50 font-medium py-3">
                                        </div>
                                    </div>

                                    <!-- Level -->
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-slate-500 ml-1 uppercase tracking-wider">Tingkat</label>
                                        <select v-model="form.level" class="w-full rounded-2xl border-slate-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50 font-bold py-3 text-slate-700">
                                            <option value="Sekolah">Sekolah / Internal</option>
                                            <option value="Kecamatan">Kecamatan</option>
                                            <option value="Kabupaten/Kota">Kabupaten / Kota</option>
                                            <option value="Provinsi">Provinsi</option>
                                            <option value="Nasional">Nasional</option>
                                            <option value="Internasional">Internasional</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Title -->
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 ml-1 uppercase tracking-wider">Judul Prestasi / Juara</label>
                                    <div class="relative">
                                        <input type="text" v-model="form.title" placeholder="Contoh: Juara 1 Lomba Pidato Bahasa Inggris" class="w-full rounded-2xl border-slate-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50 py-3 pl-11 font-bold text-slate-700 placeholder:font-normal">
                                        <TrophyIcon class="w-5 h-5 text-yellow-500 absolute left-4 top-3.5" />
                                    </div>
                                    <p v-if="form.errors.title" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.title }}</p>
                                </div>

                                <!-- Description -->
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 ml-1 uppercase tracking-wider">Keterangan Tambahan (Opsional)</label>
                                    <textarea v-model="form.description" rows="3" placeholder="Ceritakan detail prestasi, penyelenggara, atau catatan lain..." class="w-full rounded-2xl border-slate-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50 py-3 font-medium text-slate-700"></textarea>
                                </div>
                            </div>
                         </div>

                         <div class="border-t border-dashed border-slate-200"></div>

                         <!-- 3. Proof -->
                         <div class="space-y-5">
                             <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-blue-200">3</div>
                                <h3 class="text-lg font-bold text-slate-700">Bukti Foto</h3>
                            </div>

                            <div class="pl-2 md:pl-12">
                                <div class="relative group">
                                    <input type="file" @input="form.proof_file = $event.target.files[0]" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/*">
                                    <div class="w-full border-2 border-dashed border-slate-200 rounded-3xl p-8 flex flex-col items-center justify-center bg-slate-50/50 group-hover:bg-blue-50/50 group-hover:border-blue-400 transition-all duration-300">
                                        <div class="bg-white p-4 rounded-full shadow-md mb-3 group-hover:scale-110 transition-transform">
                                            <CameraIcon class="w-8 h-8 text-slate-400 group-hover:text-blue-500" />
                                        </div>
                                        <p class="text-sm font-bold text-slate-600 group-hover:text-blue-600 transition-colors" v-if="!form.proof_file">Klik untuk upload foto piala/sertifikat</p>
                                        <p class="text-sm font-bold text-teal-600 flex items-center gap-2" v-else>
                                            <DocumentArrowUpIcon class="w-5 h-5" />
                                            {{ form.proof_file.name }}
                                        </p>
                                        <p class="text-xs text-slate-400 mt-1 font-medium">Format: PNG, JPG (Maks. 2MB)</p>
                                    </div>
                                </div>
                                <p v-if="form.errors.proof_file" class="text-xs text-red-500 font-bold ml-1 mt-1">{{ form.errors.proof_file }}</p>
                            </div>
                         </div>

                    </div>

                    <!-- Footer -->
                    <div class="bg-white/50 backdrop-blur-md px-8 py-6 border-t border-slate-100 flex justify-end gap-3 md:px-10">
                        <Link :href="route('counseling.achievements.index')" class="px-6 py-3 rounded-2xl text-sm font-bold text-slate-500 hover:bg-slate-100 transition-colors">
                            Batal
                        </Link>
                        <button :disabled="form.processing" type="submit" class="px-8 py-3 bg-gradient-to-r from-namira-teal to-teal-600 text-white text-sm font-bold rounded-2xl shadow-lg shadow-namira-teal/30 hover:shadow-namira-teal/50 hover:-translate-y-1 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Simpan Prestasi</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
