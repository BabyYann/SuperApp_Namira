<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { AcademicCapIcon, ArrowRightIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import axios from 'axios';

// Import child components
import PromotionPreviewSummary from './Components/PromotionPreviewSummary.vue';
import PromotionPreviewTable from './Components/PromotionPreviewTable.vue';
import PromotionPreviewFilter from './Components/PromotionPreviewFilter.vue';
import PromotionActionBar from './Components/PromotionActionBar.vue';

const props = defineProps({
    academicYears: Array,
    activeYear: Object,
    classrooms: Array,
    statusOptions: Object,
});

// Wizard State
const currentStep = ref(1); // 1 = Selection, 2 = Preview Dashboard
const selectedFromYear = ref(null);
const selectedToYear = ref(null);
const selectedFromClassroom = ref(null);
const selectedToClassroom = ref(null);
const students = ref([]);
const summary = ref({
    total_students: 0,
    eligible_students: 0,
    blocked_students: 0,
    warning_students: 0,
    blocked_by_bill: 0,
    blocked_by_bk: 0,
    blocked_multiple_reason: 0,
});
const previewLoading = ref(false);

// Filters and Search
const statusFilter = ref('all');
const searchQuery = ref('');

// Form
const form = useForm({
    from_classroom_id: null,
    from_academic_year_id: null,
    to_classroom_id: null,
    to_academic_year_id: null,
    promotions: [],
});

// Same Year Validation
const isSameYear = computed(() => {
    return selectedFromYear.value && selectedToYear.value && selectedFromYear.value === selectedToYear.value;
});

// Filter & Search Logic
const filteredStudents = computed(() => {
    return students.value.filter(s => {
        // Status filter
        if (statusFilter.value !== 'all' && s.status !== statusFilter.value) {
            return false;
        }
        // Search query
        if (searchQuery.value.trim() !== '') {
            const query = searchQuery.value.toLowerCase();
            const nameMatch = s.nama.toLowerCase().includes(query);
            const nisMatch = s.nis.toLowerCase().includes(query);
            return nameMatch || nisMatch;
        }
        return true;
    });
});

// Check if any student in the rombel is blocked
const hasBlockedStudents = computed(() => {
    return summary.value.blocked_students > 0;
});

// Fetch preview data from backend
const fetchPreview = async () => {
    if (!selectedFromClassroom.value || !selectedFromYear.value || !selectedToYear.value) return;
    
    previewLoading.value = true;
    try {
        const response = await axios.post(route('yayasan.promotion.preview'), {
            from_classroom_id: selectedFromClassroom.value,
            from_academic_year_id: selectedFromYear.value,
            to_classroom_id: selectedToClassroom.value,
            to_academic_year_id: selectedToYear.value,
        });
        
        students.value = response.data.students || [];
        summary.value = response.data.summary || {
            total_students: 0,
            eligible_students: 0,
            blocked_students: 0,
            warning_students: 0,
            blocked_by_bill: 0,
            blocked_by_bk: 0,
            blocked_multiple_reason: 0,
        };
        
        form.from_classroom_id = selectedFromClassroom.value;
        form.from_academic_year_id = selectedFromYear.value;
        form.to_classroom_id = selectedToClassroom.value;
        form.to_academic_year_id = selectedToYear.value;
        
        currentStep.value = 2; // Transition to Preview Dashboard
    } catch (error) {
        console.error('Failed to load preview:', error);
    }
    previewLoading.value = false;
};

// Handle promote submission
const handlePromote = () => {
    form.promotions = students.value.map(s => {
        return {
            student_id: s.student_id,
            status: s.status === 'blocked' ? 'tinggal' : 'naik',
            notes: s.status === 'blocked' ? 'Ditangguhkan karena SPP/BK' : ''
        };
    });

    form.post(route('yayasan.promotion.store'), {
        onSuccess: () => {
            currentStep.value = 1;
            students.value = [];
        }
    });
};

// Handle Export CSV
const handleExport = () => {
    const params = new URLSearchParams({
        from_classroom_id: selectedFromClassroom.value,
        from_academic_year_id: selectedFromYear.value,
        to_classroom_id: selectedToClassroom.value || '',
        to_academic_year_id: selectedToYear.value,
    });
    window.location.href = route('yayasan.promotion.export-preview') + '?' + params.toString();
};

const handleBack = () => {
    currentStep.value = 1;
};
</script>

<template>
    <Head title="Kenaikan Kelas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-3">
                        <AcademicCapIcon class="w-8 h-8 text-namira-teal" />
                        Kenaikan Kelas
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Proses kenaikan/kelulusan siswa ke tahun ajaran baru</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- STEP 1: SELECT YEARS & CLASSROOM -->
            <div v-if="currentStep === 1" class="bg-white rounded-2xl shadow-sm border p-8 max-w-4xl mx-auto">
                <h3 class="font-bold text-lg mb-6 text-gray-800">Pilih Kelas dan Tahun Ajaran</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- From Classroom -->
                    <div>
                        <InputLabel value="Dari Kelas" />
                        <select v-model="selectedFromClassroom" class="w-full mt-2 h-12 px-4 border-gray-200 rounded-xl text-sm focus:border-namira-teal focus:ring-namira-teal">
                            <option :value="null">-- Pilih Kelas Asal --</option>
                            <option v-for="cls in classrooms" :key="cls.id" :value="cls.id">
                                {{ cls.name }} ({{ cls.students_count }} siswa)
                            </option>
                        </select>
                    </div>
                    
                    <!-- To Classroom -->
                    <div>
                        <InputLabel value="Ke Kelas" />
                        <select v-model="selectedToClassroom" class="w-full mt-2 h-12 px-4 border-gray-200 rounded-xl text-sm focus:border-namira-teal focus:ring-namira-teal">
                            <option :value="null">-- Pilih Kelas Tujuan --</option>
                            <option v-for="cls in classrooms" :key="cls.id" :value="cls.id">
                                {{ cls.name }}
                            </option>
                        </select>
                    </div>
                    
                    <!-- From Year -->
                    <div>
                        <InputLabel value="Dari Tahun Ajaran" />
                        <select v-model="selectedFromYear" class="w-full mt-2 h-12 px-4 border-gray-200 rounded-xl text-sm focus:border-namira-teal focus:ring-namira-teal">
                            <option :value="null">-- Pilih Tahun Asal --</option>
                            <option v-for="year in academicYears" :key="year.id" :value="year.id">
                                {{ year.name }} {{ year.is_active ? '(Aktif)' : '' }} - {{ year.student_count }} siswa
                            </option>
                        </select>
                    </div>

                    <!-- To Year -->
                    <div>
                        <InputLabel value="Ke Tahun Ajaran" />
                        <select v-model="selectedToYear" class="w-full mt-2 h-12 px-4 border-gray-200 rounded-xl text-sm focus:border-namira-teal focus:ring-namira-teal">
                            <option :value="null">-- Pilih Tahun Tujuan --</option>
                            <option v-for="year in academicYears" :key="year.id" :value="year.id">
                                {{ year.name }}
                            </option>
                        </select>
                        
                        <!-- Warning if same year -->
                        <div v-if="isSameYear" class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-2">
                            <ExclamationTriangleIcon class="w-5 h-5 text-amber-500 flex-shrink-0" />
                            <div class="text-sm text-amber-700">
                                <strong>Perhatian:</strong> Tahun asal dan tujuan tidak boleh sama.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-8">
                    <PrimaryButton 
                        @click="fetchPreview" 
                        :disabled="!selectedFromClassroom || !selectedFromYear || !selectedToYear || isSameYear || previewLoading"
                        class="px-6"
                    >
                        <span v-if="previewLoading">Loading...</span>
                        <span v-else>Preview <ArrowRightIcon class="w-4 h-4 ml-2" /></span>
                    </PrimaryButton>
                </div>
            </div>

            <!-- STEP 2: PREVIEW DASHBOARD -->
            <div v-else>
                <!-- Summary Dashboard Card -->
                <PromotionPreviewSummary :summary="summary" />

                <!-- Filters & Search -->
                <PromotionPreviewFilter 
                    v-model="statusFilter" 
                    v-model:searchQuery="searchQuery" 
                />

                <!-- Table Student -->
                <PromotionPreviewTable :students="filteredStudents" />

                <!-- Action Bar -->
                <PromotionActionBar 
                    :has-blocked="hasBlockedStudents" 
                    :processing="form.processing" 
                    :eligible-count="summary.eligible_students" 
                    @back="handleBack" 
                    @promote="handlePromote" 
                    @export="handleExport" 
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
