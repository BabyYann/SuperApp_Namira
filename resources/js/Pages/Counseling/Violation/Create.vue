<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { 
    PhotoIcon, 
    CalendarIcon, 
    ChatBubbleBottomCenterTextIcon, 
    ExclamationTriangleIcon,
    XCircleIcon,
    ArrowLeftIcon,
    DocumentTextIcon,
    AcademicCapIcon,
    DocumentArrowUpIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    categories: Array,
    students: Array,
    classrooms: Array,
});

const form = useForm({
    student_id: '',
    violation_category_id: '',
    date: new Date().toISOString().slice(0, 10),
    description: '',
    photo: null,
});

const selectedClassroom = ref('');
const previewPhoto = ref(null);

// Filter Students based on selected Classroom
const filteredStudents = computed(() => {
    if (!selectedClassroom.value) return [];
    return props.students.filter(s => s.classroom_id === selectedClassroom.value);
});

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        previewPhoto.value = URL.createObjectURL(file);
    }
};

const removePhoto = () => {
    form.photo = null;
    previewPhoto.value = null;
};

const submit = () => {
    form.post(route('counseling.violations.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            selectedClassroom.value = ''; // Reset classroom filter
            previewPhoto.value = null;
        }
    });
};
</script>

<template>
    <Head title="Lapor Pelanggaran" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('counseling.violations.index')" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-white/50 transition-colors">
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h2 class="font-bold text-xl text-slate-800 leading-tight">Catat Pelanggaran Baru</h2>
                    <p class="text-sm text-slate-500">Laporkan ketidakdisiplinan siswa untuk tercatat di sistem.</p>
                </div>
            </div>
        </template>

        <div class="py-6 min-h-screen pb-20">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <form @submit.prevent="submit" class="bg-white/80 backdrop-blur-xl rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-white/50 overflow-hidden relative">
                     
                     <div class="p-8 md:p-10 space-y-8">

                        <!-- 1. Identity Section -->
                        <div class="space-y-5">
                             <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-rose-200">1</div>
                                <h3 class="text-lg font-bold text-slate-700">Identitas Pelanggar</h3>
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
                                    <label class="text-xs font-bold text-slate-500 ml-1 uppercase tracking-wider">Pilih Siswa</label>
                                    <div class="relative">
                                        <select v-model="form.student_id" :disabled="!selectedClassroom" class="w-full rounded-2xl border-slate-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50 text-slate-700 font-bold py-3 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <option value="" disabled>{{ selectedClassroom ? '-- Pilih Siswa --' : '-- Pilih Kelas Dulu --' }}</option>
                                            <option v-for="s in filteredStudents" :key="s.id" :value="s.id">{{ s.label }}</option>
                                        </select>
                                    </div>
                                    <p v-if="form.errors.student_id" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.student_id }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-dashed border-slate-200"></div>

                        <!-- 2. Violation Details -->
                         <div class="space-y-5">
                             <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-orange-400 to-amber-500 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-amber-200">2</div>
                                <h3 class="text-lg font-bold text-slate-700">Detail Pelanggaran</h3>
                            </div>

                            <div class="space-y-6 pl-2 md:pl-12">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Date -->
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-slate-500 ml-1 uppercase tracking-wider">Tanggal Kejadian</label>
                                        <div class="relative">
                                            <input type="date" v-model="form.date" class="w-full rounded-2xl border-slate-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50 font-medium py-3">
                                        </div>
                                         <InputError :message="form.errors.date" />
                                    </div>

                                    <!-- Category -->
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-slate-500 ml-1 uppercase tracking-wider">Jenis Pelanggaran</label>
                                        <select v-model="form.violation_category_id" class="w-full rounded-2xl border-slate-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50 font-bold py-3 text-slate-700">
                                            <option value="" disabled>-- Pilih Kategori --</option>
                                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                                {{ cat.name }} — ({{ cat.default_points }} Poin)
                                            </option>
                                        </select>
                                         <InputError :message="form.errors.violation_category_id" />
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 ml-1 uppercase tracking-wider">Kronologi / Keterangan (Opsional)</label>
                                    <div class="relative group">
                                         <div class="absolute top-3 left-4 pointer-events-none">
                                            <ChatBubbleBottomCenterTextIcon class="w-5 h-5 text-gray-400" />
                                        </div>
                                        <textarea v-model="form.description" rows="3" placeholder="Jelaskan detail kejadian secara singkat..." class="w-full pl-12 rounded-2xl border-slate-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50 py-3 font-medium text-slate-700 placeholder:font-normal"></textarea>
                                    </div>
                                    <InputError :message="form.errors.description" />
                                </div>
                            </div>
                         </div>

                         <div class="border-t border-dashed border-slate-200"></div>

                         <!-- 3. Evidence -->
                         <div class="space-y-5">
                             <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-slate-600 to-slate-800 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-slate-300">3</div>
                                <h3 class="text-lg font-bold text-slate-700">Bukti Foto</h3>
                            </div>

                            <div class="pl-2 md:pl-12">
                                <div class="flex flex-col md:flex-row gap-6">
                                     <!-- Preview -->
                                    <div v-if="previewPhoto" class="relative group shrink-0">
                                        <img :src="previewPhoto" class="w-32 h-32 object-cover rounded-2xl border-4 border-white shadow-md transform rotate-2 group-hover:rotate-0 transition-all duration-300" />
                                        <button @click="removePhoto" type="button" class="absolute -top-2 -right-2 bg-white rounded-full p-1 shadow-md text-red-500 hover:bg-red-50 transition-colors border border-gray-100">
                                            <XCircleIcon class="w-6 h-6" />
                                        </button>
                                    </div>

                                    <div class="w-full relative group">
                                        <input type="file" @change="handleFileChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/*">
                                        <div class="w-full border-2 border-dashed border-slate-200 rounded-3xl p-8 flex flex-col items-center justify-center bg-slate-50/50 group-hover:bg-red-50/50 group-hover:border-red-400 transition-all duration-300">
                                            <div class="bg-white p-4 rounded-full shadow-md mb-3 group-hover:scale-110 transition-transform">
                                                <PhotoIcon class="w-8 h-8 text-slate-400 group-hover:text-red-500" />
                                            </div>
                                            <p class="text-sm font-bold text-slate-600 group-hover:text-red-600 transition-colors" v-if="!form.photo">Klik untuk upload foto bukti</p>
                                            <p class="text-sm font-bold text-teal-600 flex items-center gap-2" v-else>
                                                <DocumentArrowUpIcon class="w-5 h-5" />
                                                {{ form.photo.name }}
                                            </p>
                                            <p class="text-xs text-slate-400 mt-1 font-medium">Format: PNG, JPG (Maks. 2MB)</p>
                                        </div>
                                    </div>
                                </div>
                                <InputError :message="form.errors.photo" class="mt-1" />
                            </div>
                         </div>

                    </div>

                    <!-- Footer -->
                    <div class="bg-white/50 backdrop-blur-md px-8 py-6 border-t border-slate-100 flex justify-end gap-3 md:px-10">
                        <Link :href="route('counseling.violations.index')" class="px-6 py-3 rounded-2xl text-sm font-bold text-slate-500 hover:bg-slate-100 transition-colors">
                            Batal
                        </Link>
                        <button :disabled="form.processing" type="submit" class="px-8 py-3 bg-gradient-to-r from-red-600 to-rose-600 text-white text-sm font-bold rounded-2xl shadow-lg shadow-red-500/30 hover:shadow-red-500/50 hover:-translate-y-1 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                             <ExclamationTriangleIcon class="w-5 h-5" />
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Simpan Laporan</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
