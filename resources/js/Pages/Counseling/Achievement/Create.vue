<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    ArrowLeftIcon, 
    TrophyIcon, 
    DocumentArrowUpIcon,
    CameraIcon,
    PhotoIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    XMarkIcon,
    ArrowPathIcon,
    UserIcon,
    CalendarIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

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

// Photo / Document State & Compression
const cameraInputRef = ref(null);
const galleryInputRef = ref(null);
const photoPreview = ref(null);
const isPdf = ref(false);
const fileName = ref('');
const photoSizeInfo = ref('');
const isCompressing = ref(false);

const compressImage = (file) => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;
                const maxDim = 1280;

                if (width > height && width > maxDim) {
                    height = Math.round((height * maxDim) / width);
                    width = maxDim;
                } else if (height > maxDim) {
                    width = Math.round((width * maxDim) / height);
                    height = maxDim;
                }

                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                const compressedDataUrl = canvas.toDataURL('image/jpeg', 0.72);

                const head = 'data:image/jpeg;base64,';
                const base64Length = compressedDataUrl.length - head.length;
                const sizeInBytes = Math.round((base64Length * 3) / 4);
                const sizeInKB = Math.round(sizeInBytes / 1024);
                photoSizeInfo.value = `${sizeInKB} KB`;

                resolve(compressedDataUrl);
            };
            img.onerror = (error) => reject(error);
        };
        reader.onerror = (error) => reject(error);
    });
};

const handleFileInput = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    fileName.value = file.name;

    // If PDF document
    if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
        isPdf.value = true;
        photoPreview.value = null;
        const sizeInKB = Math.round(file.size / 1024);
        photoSizeInfo.value = `${sizeInKB} KB`;
        form.proof_file = file;
        return;
    }

    // If Image: compress automatically
    isPdf.value = false;
    isCompressing.value = true;
    try {
        const compressedBase64 = await compressImage(file);
        photoPreview.value = compressedBase64;
        form.proof_file = compressedBase64;
    } catch (err) {
        console.error('Compress error:', err);
        form.proof_file = file;
        photoPreview.value = URL.createObjectURL(file);
        photoSizeInfo.value = `${Math.round(file.size / 1024)} KB`;
    } finally {
        isCompressing.value = false;
        if (event.target) event.target.value = '';
    }
};

const removeFile = () => {
    form.proof_file = null;
    photoPreview.value = null;
    isPdf.value = false;
    fileName.value = '';
    photoSizeInfo.value = '';
};

const submit = () => {
    if (!form.student_id) {
        Swal.fire({
            icon: 'warning',
            title: 'Pilih Siswa',
            text: 'Silakan pilih kelas dan siswa terlebih dahulu.',
            confirmButtonColor: '#00796B',
        });
        return;
    }

    if (!form.title) {
        Swal.fire({
            icon: 'warning',
            title: 'Isi Judul Prestasi',
            text: 'Harap masukkan nama / judul prestasi yang diraih siswa.',
            confirmButtonColor: '#00796B',
        });
        return;
    }

    form.post(route('counseling.achievements.store'), {
        forceFormData: true,
        preserveScroll: true,
        onError: (errors) => {
            const errorMsg = Object.values(errors).flat().join('<br>') || 'Terjadi kesalahan saat menyimpan prestasi.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                html: errorMsg,
                confirmButtonColor: '#00796B',
            });
        }
    });
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
                                <h3 class="text-lg font-bold text-slate-700">Bukti Sertifikat / Foto</h3>
                            </div>

                            <div class="pl-2 md:pl-12">
                                <!-- Hidden File Inputs -->
                                <input 
                                    ref="cameraInputRef" 
                                    type="file" 
                                    accept="image/*" 
                                    capture="environment" 
                                    class="hidden" 
                                    @change="handleFileInput"
                                />
                                <input 
                                    ref="galleryInputRef" 
                                    type="file" 
                                    accept="image/*,application/pdf" 
                                    class="hidden" 
                                    @change="handleFileInput"
                                />

                                <!-- Preview if Image Uploaded -->
                                <div v-if="photoPreview" class="relative rounded-3xl overflow-hidden border border-teal-500/30 bg-slate-900 aspect-video max-h-64 flex items-center justify-center group shadow-lg">
                                    <img :src="photoPreview" class="w-full h-full object-cover" alt="Bukti Prestasi" />
                                    
                                    <!-- Badge status kompresi -->
                                    <div class="absolute bottom-3 left-3 bg-black/75 backdrop-blur-xs text-white text-xs font-bold px-3 py-1 rounded-xl flex items-center gap-1.5 shadow">
                                        <CheckCircleIcon class="w-4 h-4 text-emerald-400 shrink-0" />
                                        <span>Terkompresi otomatis ({{ photoSizeInfo || 'Optimal' }})</span>
                                    </div>

                                    <button 
                                        type="button" 
                                        @click="removeFile" 
                                        class="absolute top-3 right-3 p-2 bg-rose-500 hover:bg-rose-600 text-white rounded-xl transition shadow active:scale-95" 
                                        title="Hapus Bukti"
                                    >
                                        <XMarkIcon class="w-5 h-5" />
                                    </button>
                                </div>

                                <!-- Preview if PDF Uploaded -->
                                <div v-else-if="isPdf && form.proof_file" class="flex items-center justify-between p-4 rounded-2xl bg-amber-50/70 border border-amber-200/80 shadow-xs">
                                    <div class="flex items-center gap-3">
                                        <div class="w-11 h-11 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                                            <DocumentTextIcon class="w-6 h-6" />
                                        </div>
                                        <div>
                                            <p class="font-bold text-sm text-slate-800 line-clamp-1">{{ fileName }}</p>
                                            <p class="text-xs text-slate-400 font-medium">Dokumen PDF ({{ photoSizeInfo }})</p>
                                        </div>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="removeFile" 
                                        class="p-2 text-rose-500 hover:bg-rose-100 rounded-xl transition active:scale-90"
                                        title="Hapus File"
                                    >
                                        <XMarkIcon class="w-5 h-5" />
                                    </button>
                                </div>

                                <!-- Upload Buttons if No File Selected -->
                                <div v-else class="space-y-3">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <!-- Kamera Button -->
                                        <button 
                                            type="button" 
                                            @click="cameraInputRef?.click()"
                                            class="flex items-center justify-center gap-2.5 p-4 rounded-2xl bg-white hover:bg-teal-50/80 border-2 border-dashed border-teal-300 text-teal-800 font-bold text-sm transition active:scale-95 shadow-2xs group"
                                        >
                                            <div class="p-2 bg-teal-100 text-teal-700 rounded-xl group-hover:scale-110 transition-transform">
                                                <CameraIcon class="w-5 h-5" />
                                            </div>
                                            <span>Ambil Foto (Kamera)</span>
                                        </button>

                                        <!-- Galeri / Dokumen Button -->
                                        <button 
                                            type="button" 
                                            @click="galleryInputRef?.click()"
                                            class="flex items-center justify-center gap-2.5 p-4 rounded-2xl bg-white hover:bg-blue-50/80 border-2 border-dashed border-blue-300 text-blue-800 font-bold text-sm transition active:scale-95 shadow-2xs group"
                                        >
                                            <div class="p-2 bg-blue-100 text-blue-700 rounded-xl group-hover:scale-110 transition-transform">
                                                <PhotoIcon class="w-5 h-5" />
                                            </div>
                                            <span>Pilih Galeri / PDF</span>
                                        </button>
                                    </div>

                                    <p class="text-xs text-slate-400 font-medium text-center">
                                        Format: Foto (JPG, PNG) atau Dokumen PDF. Foto otomatis dikompres agar ringan & anti gagal upload.
                                    </p>
                                </div>

                                <div v-if="isCompressing" class="flex items-center justify-center gap-2 text-xs font-bold text-amber-700 py-2">
                                    <ArrowPathIcon class="w-4 h-4 animate-spin" />
                                    <span>Sedang mengompresi foto otomatis...</span>
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
