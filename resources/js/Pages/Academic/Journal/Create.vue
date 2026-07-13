<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { PlusIcon } from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    schedule: Object,
    date: String,
    classroom: Object,
    subject: Object,
    students: Array,
    existingChapters: Array,
    journal: Object, // Optional: For Edit Mode
});

const isEditing = computed(() => !!props.journal);

// Form Logic
const form = useForm({
    classroom_id: props.classroom.id,
    subject_id: props.subject.id,
    class_schedule_id: props.schedule?.id,
    date: props.date,
    start_time: props.schedule?.start_time || '07:00',
    end_time: props.schedule?.end_time || '08:00',
    selected_tps: [],
    new_tps: [], // Array of { code, description, chapter_title }
    custom_theme: '',
    attendance: props.students.map(s => ({
        student_id: s.id,
        status: 'present', // Default Hadir
        note: ''
    })),
    notes: '',
    photo: null,
    _method: 'POST', // Default
});

// Initialize for Edit Mode
onMounted(() => {
    if (isEditing.value) {
        form.class_schedule_id = props.journal.class_schedule_id;
        form.date = props.journal.date;
        form.start_time = props.journal.start_time;
        form.end_time = props.journal.end_time;
        form.custom_theme = props.journal.custom_theme;
        form.notes = props.journal.notes;
        form._method = 'PUT'; // For Laravel Resource Update

        // Map Attendance
        if (props.journal.attendance) {
            form.attendance = props.students.map(s => {
                const existing = props.journal.attendance.find(a => a.student_id === s.id);
                return {
                    student_id: s.id,
                    status: existing ? existing.status : 'present',
                    note: existing ? existing.note : ''
                };
            });
        }

        // Map TPs
        if (props.journal.learning_objectives) {
            form.selected_tps = props.journal.learning_objectives.map(tp => tp.id);
        }
    }
});

// JIT TP Logic
const showAddTpForm = ref(false);
const newTpForm = ref({
    chapter_title: '',
    code: '',
    description: ''
});

const addNewTp = () => {
    if (!newTpForm.value.code || !newTpForm.value.description || !newTpForm.value.chapter_title) return;
    
    form.new_tps.push({ ...newTpForm.value });
    
    // Automatically select it (visual feedback)
    // We can't really select it in 'selected_tps' because it has no ID yet.
    // We display it in a separate list.
    
    // Reset
    newTpForm.value = { chapter_title: '', code: '', description: '' };
    showAddTpForm.value = false;
};

const removeNewTp = (index) => {
    form.new_tps.splice(index, 1);
};

const submit = () => {
    if (isEditing.value) {
        form.post(route('yayasan.teaching-journal.update', props.journal.id), {
            forceFormData: true,
        });
    } else {
        form.post(route('yayasan.teaching-journal.store'), {
            forceFormData: true,
        });
    }
};

// Date formatter
const formatDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'full' }).format(date);
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Jurnal Mengajar' : 'Isi Jurnal Mengajar'" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-2xl text-gray-800 leading-tight">{{ isEditing ? 'Edit Jurnal Mengajar' : 'Jurnal Mengajar' }}</h2>
        </template>

        <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form @submit.prevent="submit" class="space-y-8">
                
                <!-- 1. Info Kelas -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm">1</span>
                        Informasi Kegiatan
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="bg-gray-50 p-4 rounded-2xl">
                            <p class="text-xs text-gray-400 font-bold uppercase">Tanggal</p>
                            <p class="text-lg font-bold text-gray-900">{{ formatDate(date) }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-2xl">
                            <p class="text-xs text-gray-400 font-bold uppercase">Kelas</p>
                            <p class="text-lg font-bold text-gray-900">{{ classroom.name }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-2xl md:col-span-2">
                            <p class="text-xs text-gray-400 font-bold uppercase">Mata Pelajaran</p>
                            <p class="text-lg font-bold text-gray-900">{{ subject.name }}</p>
                        </div>
                    </div>
                </div>

                <!-- 2. Materi Ajar (The Smart Part) -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center text-sm">2</span>
                        Materi / Tujuan Pembelajaran
                    </h3>
                    
                    <!-- Existing TPs -->
                    <div v-if="existingChapters.length > 0" class="space-y-6 mb-6">
                        <div v-for="chapter in existingChapters" :key="chapter.id" class="border rounded-2xl p-4 md:p-5 hover:border-teal-200 transition-colors">
                            <h4 class="font-bold text-gray-800 mb-3">{{ chapter.title }}</h4>
                            <div class="space-y-2">
                                <label v-for="tp in chapter.learning_objectives" :key="tp.id" class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" :value="tp.id" v-model="form.selected_tps" class="mt-1 rounded border-gray-300 text-teal-600 focus:ring-teal-500 w-5 h-5">
                                    <div class="flex-1">
                                        <span class="font-bold text-teal-700 text-sm bg-teal-50 px-2 py-0.5 rounded mr-2 group-hover:bg-teal-100 transition-colors">{{ tp.code }}</span>
                                        <span class="text-gray-700 text-sm group-hover:text-gray-900">{{ tp.description }}</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 mb-6">
                        <p class="text-gray-500">Belum ada data materi untuk mapel ini.</p>
                    </div>

                    <!-- New TPs (JIT) Display -->
                    <div v-if="form.new_tps.length > 0" class="mb-6 space-y-2">
                         <div v-for="(ntp, idx) in form.new_tps" :key="idx" class="flex items-center justify-between p-3 bg-green-50 rounded-xl border border-green-200">
                            <div class="flex items-center gap-3">
                                <span class="w-5 h-5 rounded-full bg-green-200 text-green-700 flex items-center justify-center text-xs">✓</span>
                                <div>
                                    <p class="text-xs font-bold text-green-800">{{ ntp.code }} - {{ ntp.chapter_title }} (Baru)</p>
                                    <p class="text-sm text-gray-700">{{ ntp.description }}</p>
                                </div>
                            </div>
                            <button type="button" @click="removeNewTp(idx)" class="text-red-400 hover:text-red-600 font-bold text-xs">Hapus</button>
                        </div>
                    </div>

                    <!-- Add New Button -->
                    <div v-if="!showAddTpForm">
                        <button type="button" @click="showAddTpForm = true" class="text-sm font-bold text-teal-600 hover:text-teal-700 flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-teal-50 transition-colors">
                            <PlusIcon class="w-5 h-5" />
                            Materi yang diajarkan belum ada? Tambah Baru
                        </button>
                    </div>

                    <!-- JIT Form -->
                    <div v-else class="bg-gray-50 p-4 rounded-2xl border border-gray-200 animate-in fade-in slide-in-from-top-4">
                        <h4 class="font-bold text-gray-800 mb-3 text-sm">Tambah Materi Baru (Otomatis Masuk Master)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <InputLabel value="Bab / Judul Materi" />
                                <input v-model="newTpForm.chapter_title" type="text" class="w-full mt-1 h-12 px-4 border-gray-200 rounded-xl text-sm" placeholder="Contoh: Bab 5 Statistika">
                            </div>
                            <div>
                                <InputLabel value="Kode TP" />
                                <input v-model="newTpForm.code" type="text" class="w-full mt-1 h-12 px-4 border-gray-200 rounded-xl text-sm" placeholder="Contoh: TP 5.1">
                            </div>
                            <div class="md:col-span-2">
                                <InputLabel value="Deskripsi Pembelajaran" />
                                <textarea v-model="newTpForm.description" rows="2" class="w-full mt-1 px-4 py-3 border-gray-200 rounded-xl text-sm resize-none" placeholder="Isi deskripsi..."></textarea>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showAddTpForm = false" class="text-gray-500 font-bold text-sm px-3 py-2">Batal</button>
                            <button type="button" @click="addNewTp" class="bg-teal-600 text-white font-bold text-sm px-4 py-2 rounded-lg shadow hover:bg-teal-700">Tambahkan</button>
                        </div>
                    </div>

                    <!-- Fallback -->
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <InputLabel value="Pokok Bahasan Khusus (Opsional)" />
                        <p class="text-xs text-gray-400 mb-2">Isi ini jika kegiatan adalah persiapan ujian, remedial, atau di luar TP.</p>
                        <TextInput v-model="form.custom_theme" class="w-full" placeholder="Contoh: Persiapan UTS Semester Ganjil" />
                    </div>
                </div>

                <!-- 3. Absensi Siswa -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-sm">3</span>
                        Presensi Siswa ({{ students.length }} Siswa)
                    </h3>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-500 uppercase font-bold text-xs">
                                <tr>
                                    <th class="px-4 py-3 text-left w-10">No</th>
                                    <th class="px-4 py-3 text-left">Nama Siswa</th>
                                    <th class="px-4 py-3 text-center">Status Kehadiran</th>
                                    <th class="px-4 py-3 text-left">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(att, idx) in form.attendance" :key="att.student_id" class="hover:bg-gray-50/50">
                                    <td class="px-4 py-3 text-center text-gray-400">{{ idx + 1 }}</td>
                                    <td class="px-4 py-3 font-bold text-gray-800">
                                        {{ students.find(s => s.id === att.student_id)?.name }}
                                        <div class="text-[10px] text-gray-400 font-normal">{{ students.find(s => s.id === att.student_id)?.nis }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-center flex-wrap gap-2">
                                            <!-- Hadir -->
                                            <label class="cursor-pointer">
                                                <input type="radio" value="present" v-model="att.status" class="hidden peer">
                                                <div class="px-3 py-2 rounded-xl text-xs font-bold border border-gray-200 text-gray-500 peer-checked:bg-green-500 peer-checked:text-white peer-checked:border-green-500 peer-checked:shadow-md transition-all">
                                                    Hadir
                                                </div>
                                            </label>
                                            <!-- Sakit -->
                                            <label class="cursor-pointer">
                                                <input type="radio" value="sick" v-model="att.status" class="hidden peer">
                                                <div class="px-3 py-2 rounded-xl text-xs font-bold border border-gray-200 text-gray-500 peer-checked:bg-yellow-500 peer-checked:text-white peer-checked:border-yellow-500 peer-checked:shadow-md transition-all">
                                                    Sakit
                                                </div>
                                            </label>
                                            <!-- Izin -->
                                            <label class="cursor-pointer">
                                                <input type="radio" value="permission" v-model="att.status" class="hidden peer">
                                                <div class="px-3 py-2 rounded-xl text-xs font-bold border border-gray-200 text-gray-500 peer-checked:bg-blue-500 peer-checked:text-white peer-checked:border-blue-500 peer-checked:shadow-md transition-all">
                                                    Izin
                                                </div>
                                            </label>
                                            <!-- Alpha -->
                                            <label class="cursor-pointer">
                                                <input type="radio" value="alpha" v-model="att.status" class="hidden peer">
                                                <div class="px-3 py-2 rounded-xl text-xs font-bold border border-gray-200 text-gray-500 peer-checked:bg-red-500 peer-checked:text-white peer-checked:border-red-500 peer-checked:shadow-md transition-all">
                                                    Alpha
                                                </div>
                                            </label>
                                            <!-- Late (Adding nice to have) -->
                                            <label class="cursor-pointer">
                                                <input type="radio" value="late" v-model="att.status" class="hidden peer">
                                                <div class="px-3 py-2 rounded-xl text-xs font-bold border border-gray-200 text-gray-500 peer-checked:bg-orange-500 peer-checked:text-white peer-checked:border-orange-500 peer-checked:shadow-md transition-all">
                                                    Telat
                                                </div>
                                            </label>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input v-model="att.note" type="text" placeholder="..." class="border-0 bg-transparent text-sm w-full focus:ring-0 border-b border-gray-200 focus:border-purple-500 px-0">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 4. Bukti & Catatan -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-sm">4</span>
                        Bukti & Catatan
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel value="Upload Foto Kegiatan *" />
                            <div class="mt-2 flex justify-center rounded-2xl border-2 border-dashed border-gray-300 p-6 hover:border-orange-400 transition-colors bg-gray-50">
                                <div class="text-center">
                                    <div class="flex text-sm text-gray-600">
                                        <label for="file-upload" class="relative cursor-pointer rounded-md font-bold text-orange-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-orange-500 focus-within:ring-offset-2 hover:text-orange-500">
                                            <span>Upload file</span>
                                            <input id="file-upload" name="file-upload" type="file" class="sr-only" @input="form.photo = $event.target.files[0]">
                                        </label>
                                        <p class="pl-1">atau drag and drop</p>
                                    </div>
                                    <p v-if="form.photo" class="text-xs font-bold text-green-600 mt-2">File terpilih: {{ form.photo.name }}</p>
                                    <p v-else class="text-xs text-gray-500">PNG, JPG, GIF up to 5MB</p>
                                </div>
                            </div>
                             <InputError :message="form.errors.photo_path" class="mt-1" />
                             <!-- Show Existing Photo -->
                             <div v-if="isEditing && journal.photo_path && !form.photo" class="mt-2 text-xs text-gray-500">
                                Foto saat ini: <a :href="'/storage/' + journal.photo_path" target="_blank" class="text-orange-600 hover:underline">Lihat Foto</a>
                             </div>
                        </div>
                        <div>
                            <InputLabel value="Catatan Tambahan (Jurnal)" />
                            <textarea v-model="form.notes" rows="4" class="w-full mt-2 px-4 py-3 border-gray-200 rounded-xl focus:border-orange-500 focus:ring-orange-500 text-sm" placeholder="Catatan khusus mengenai kegiatan belajar hari ini..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Action -->
                <div class="flex justify-end pt-4">
                    <PrimaryButton :disabled="form.processing" class="h-12 px-8 text-base rounded-xl bg-gray-900 hover:bg-gray-800 shadow-xl shadow-gray-400/20">
                        <span v-if="form.processing">Menyimpan...</span>
                        <span v-else>{{ isEditing ? 'Perbarui Laporan' : 'Simpan Laporan Jurnal' }}</span>
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
