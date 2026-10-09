<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { 
    MagnifyingGlassIcon, FunnelIcon, ArrowPathIcon, ArrowUpTrayIcon, 
    PlusIcon, UserIcon, EnvelopeIcon, EyeIcon, PencilSquareIcon, 
    TrashIcon, CameraIcon, ExclamationTriangleIcon, CheckCircleIcon,
    DocumentTextIcon, ArrowPathRoundedSquareIcon,
    ChatBubbleLeftRightIcon, ArrowLeftIcon, AcademicCapIcon,
    SparklesIcon, BuildingOffice2Icon, XMarkIcon,
    AdjustmentsHorizontalIcon, ChevronRightIcon, UserGroupIcon, PhoneIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    students: Object, // Paginator
    classrooms: Array,
    academicYears: Array,
    activeYear: Object,
    filters: Object,
    canManage: {
        type: Boolean,
        default: false,
    },
    isTeacher: {
        type: Boolean,
        default: false,
    },
    isHomeroom: {
        type: Boolean,
        default: false,
    },
    homeroomClass: Object,
    activeScope: {
        type: String,
        default: 'all',
    },
    activeUnit: Object,
    totalUnitStudents: Number,
});

// State
const searchQuery = ref(props.filters?.search || '');
const genderFilter = ref(props.filters?.gender || '');
const classFilter = ref(props.filters?.classroom_id || '');
const yearFilter = ref(props.filters?.academic_year_id || '');
const activeScope = ref(props.activeScope || (props.isHomeroom ? 'homeroom' : 'all'));
const showFilter = ref(!!(props.filters?.gender || props.filters?.academic_year_id));

const showModal = ref(false);
const showImportModal = ref(false);
const showVaImportModal = ref(false);
const showExcelImportModal = ref(false);
const isEditing = ref(false);
const showDeleteConfirm = ref(false);
const itemToDelete = ref(null);
const photoPreview = ref(null);
const isLoading = ref(false);

const form = useForm({
    id: null,
    full_name: '',
    email: '',
    nis: '',
    nisn: '',
    va_number: '',
    gender: 'L',
    classroom_id: '',
    pob: '',
    dob: '',
    address: '',
    parent_name: '',
    parent_phone: '',
    guardian_name: '',
    guardian_phone: '',
    photo: null,
});

const importForm = useForm({
    file: null,
});

const vaImportForm = useForm({
    file: null,
});

const excelImportForm = useForm({
    file: null,
    classroom_id: '',
});

const deleteForm = useForm({});

// Format WA link
const formatWhatsappLink = (phone, studentName) => {
    if (!phone) return '#';
    let clean = String(phone).replace(/[^0-9]/g, '');
    if (clean.startsWith('0')) {
        clean = '62' + clean.slice(1);
    } else if (!clean.startsWith('62')) {
        clean = '62' + clean;
    }
    const text = encodeURIComponent(`Assalamu'alaikum Warahmatullahi Wabarakatuh, Yth. Orang Tua/Wali dari ananda ${studentName}.`);
    return `https://wa.me/${clean}?text=${text}`;
};

// Debounce Utility
const debounce = (func, wait) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
};

// Search & Filter Action
const performSearch = debounce(() => {
    isLoading.value = true;
    router.get(route('yayasan.students.index'), 
        { 
            search: searchQuery.value, 
            gender: genderFilter.value,
            classroom_id: activeScope.value === 'homeroom' && props.homeroomClass ? props.homeroomClass.id : classFilter.value,
            academic_year_id: yearFilter.value,
            scope: activeScope.value,
        }, 
        { 
            preserveState: true, 
            replace: true, 
            onFinish: () => isLoading.value = false 
        }
    );
}, 300);

watch([searchQuery, genderFilter, classFilter, yearFilter], () => {
    performSearch();
});

const switchScope = (scope) => {
    if (activeScope.value === scope) return;
    activeScope.value = scope;
    if (scope === 'homeroom' && props.homeroomClass) {
        classFilter.value = props.homeroomClass.id;
    } else {
        classFilter.value = '';
    }
    performSearch();
};

const setGenderFilter = (gender) => {
    if (genderFilter.value === gender) {
        genderFilter.value = '';
    } else {
        genderFilter.value = gender;
    }
};

const setClassFilter = (classId) => {
    if (classFilter.value === classId) {
        classFilter.value = '';
    } else {
        classFilter.value = classId;
    }
};

const showMobileFilterSheet = ref(false);

const goToDetail = (student) => {
    router.visit(route('yayasan.students.show', student.id));
};

const openWhatsApp = (student) => {
    const phone = student.parent_phone || student.guardian_phone;
    if (phone) {
        window.open(formatWhatsappLink(phone, student.full_name), '_blank');
    }
};

const clearSearch = () => {
    searchQuery.value = '';
};

const resetFilter = () => {
    genderFilter.value = '';
    classFilter.value = '';
    yearFilter.value = '';
    searchQuery.value = '';
    if (props.isHomeroom) {
        activeScope.value = 'homeroom';
    } else {
        activeScope.value = 'all';
    }
};

const hasActiveFilters = computed(() => {
    return Boolean(
        searchQuery.value || 
        genderFilter.value || 
        (activeScope.value === 'all' && classFilter.value) || 
        yearFilter.value
    );
});

// Modal Methods
const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.gender = 'L';
    photoPreview.value = null;
    showModal.value = true;
};

const openEditModal = (student) => {
    isEditing.value = true;
    form.id = student.id;
    form.full_name = student.full_name;
    form.email = student.user?.email || '';
    form.nis = student.nis;
    form.nisn = student.nisn;
    form.va_number = student.va_number || '';
    form.gender = student.gender;
    form.classroom_id = student.classroom_id || '';
    form.pob = student.pob || '';
    form.dob = student.dob || '';
    form.address = student.address || '';
    form.parent_name = student.parent_name || '';
    form.parent_phone = student.parent_phone || '';
    form.guardian_name = student.guardian_name || '';
    form.guardian_phone = student.guardian_phone || '';
    form.photo = null; 
    photoPreview.value = student.photo ? `/storage/${student.photo}` : null;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    photoPreview.value = null;
};

const handlePhotoUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    if (isEditing.value) {
        router.post(route('yayasan.students.update', form.id), {
            _method: 'put',
            ...form.data(),
            photo: form.photo,
        }, { onSuccess: () => closeModal() });
    } else {
        form.post(route('yayasan.students.store'), { onSuccess: () => closeModal() });
    }
};

const confirmDelete = (student) => {
    itemToDelete.value = student;
    showDeleteConfirm.value = true;
};

const deleteItem = () => {
    if (!itemToDelete.value) return;
    deleteForm.delete(route('yayasan.students.destroy', itemToDelete.value.id), {
        onSuccess: () => {
            showDeleteConfirm.value = false;
            itemToDelete.value = null;
        }
    });
};

// Import Methods
const openImportModal = () => {
    importForm.reset();
    showImportModal.value = true;
};

const handleImportFile = (e) => {
    importForm.file = e.target.files[0];
};

const submitImport = () => {
    router.post(route('yayasan.students.import'), {
        file: importForm.file
    }, {
        forceFormData: true,
        onSuccess: () => showImportModal.value = false,
    });
};

// VA Import Methods
const openVaImportModal = () => {
    vaImportForm.reset();
    showVaImportModal.value = true;
};

const handleVaImportFile = (e) => {
    vaImportForm.file = e.target.files[0];
};

const submitVaImport = () => {
    router.post(route('yayasan.students.import-va'), {
        file: vaImportForm.file
    }, {
        forceFormData: true,
        onSuccess: () => showVaImportModal.value = false,
    });
};

// Excel Import Methods
const openExcelImportModal = () => {
    excelImportForm.reset();
    showExcelImportModal.value = true;
};

const handleExcelImportFile = (e) => {
    excelImportForm.file = e.target.files[0];
};

const submitExcelImport = () => {
    router.post(route('yayasan.students.import-excel'), {
        file: excelImportForm.file,
        classroom_id: excelImportForm.classroom_id || null
    }, {
        forceFormData: true,
        onSuccess: () => showExcelImportModal.value = false,
    });
};
</script>

<template>
    <Head title="Manajemen Siswa" />

    <AuthenticatedLayout>
        <template #header>
                <!-- Top Row: Title & Description -->
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Database Siswa
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Total Data: <span class="font-bold text-namira-teal">{{ students.total }} Siswa</span>
                    </p>
                </div>
        </template>

        <div class="py-3 md:py-6 max-w-7xl mx-auto space-y-4 md:space-y-6">

            <!-- 📱 NATIVE MOBILE VIEW (block md:hidden) -->
            <div class="block md:hidden -mx-4 -mt-3 space-y-3.5 pb-8">
                <!-- 1. Top Bar & Header Navigation -->
                <div class="bg-white px-4 pt-3 pb-3 border-b border-slate-100 flex items-center justify-between shadow-2xs">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <Link 
                            :href="route('yayasan.dashboard')" 
                            class="w-10 h-10 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition-all active:scale-95 shrink-0"
                            title="Kembali ke Dashboard"
                        >
                            <ArrowLeftIcon class="w-5 h-5 stroke-[2.2]" />
                        </Link>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <h1 class="text-base font-black text-slate-900 leading-tight">Data Siswa</h1>
                                <span v-if="activeUnit" class="px-2 py-0.5 rounded-md bg-teal-50 text-teal-800 text-[10px] font-black border border-teal-200/60 uppercase">
                                    {{ activeUnit.code || activeUnit.name }}
                                </span>
                            </div>
                            <p class="text-[11px] font-semibold text-slate-400 mt-0.5">
                                Menampilkan <strong class="text-emerald-600 font-extrabold">{{ students.total || 0 }}</strong> Siswa
                            </p>
                        </div>
                    </div>

                    <!-- Add Button: STRICTLY GUARDED by canManage! -->
                    <button 
                        v-if="canManage"
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1 active:scale-95 transition-all shrink-0"
                    >
                        <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                        <span>Tambah</span>
                    </button>
                </div>

                <!-- 2. Walas Scope Switcher: Prioritas Kelas Bimbingan vs Semua Siswa Unit -->
                <div v-if="isHomeroom && homeroomClass" class="px-4">
                    <div class="bg-slate-100/90 p-1 rounded-2xl flex items-center gap-1 border border-slate-200/70 shadow-2xs">
                        <!-- Tab 1: Kelas Saya (Homeroom) -->
                        <button
                            @click="switchScope('homeroom')"
                            type="button"
                            class="flex-1 py-2 px-2.5 rounded-xl font-extrabold text-xs transition-all flex items-center justify-center gap-1.5 min-w-0 active:scale-95"
                            :class="activeScope === 'homeroom' 
                                ? 'bg-white text-teal-900 shadow-sm border border-slate-200/50' 
                                : 'text-slate-600 hover:text-slate-900'"
                        >
                            <SparklesIcon class="w-3.5 h-3.5 text-amber-500 shrink-0" />
                            <span class="truncate">Kelas Saya ({{ homeroomClass.name }})</span>
                            <span 
                                class="px-1.5 py-0.5 rounded-full text-[10px] font-black shrink-0 leading-none"
                                :class="activeScope === 'homeroom' ? 'bg-teal-50 text-teal-700 border border-teal-200/50' : 'bg-slate-200 text-slate-600'"
                            >
                                {{ homeroomClass.student_count }}
                            </span>
                        </button>

                        <!-- Tab 2: Semua Siswa di Unit -->
                        <button
                            @click="switchScope('all')"
                            type="button"
                            class="flex-1 py-2 px-2.5 rounded-xl font-extrabold text-xs transition-all flex items-center justify-center gap-1.5 min-w-0 active:scale-95"
                            :class="activeScope === 'all' 
                                ? 'bg-white text-teal-900 shadow-sm border border-slate-200/50' 
                                : 'text-slate-600 hover:text-slate-900'"
                        >
                            <BuildingOffice2Icon class="w-3.5 h-3.5 text-teal-700 shrink-0" />
                            <span class="truncate">Semua Siswa</span>
                            <span 
                                class="px-1.5 py-0.5 rounded-full text-[10px] font-black shrink-0 leading-none"
                                :class="activeScope === 'all' ? 'bg-teal-50 text-teal-700 border border-teal-200/50' : 'bg-slate-200 text-slate-600'"
                            >
                                {{ totalUnitStudents }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- 3. Search & Horizontal Quick Chip Filter Bar -->
                <div class="px-4 space-y-2.5">
                    <!-- Real-Time Search Bar with Filter Sliders Button -->
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <MagnifyingGlassIcon v-if="!isLoading" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                            <ArrowPathIcon v-else class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 animate-spin text-teal-600 pointer-events-none" />
                            
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari nama atau NIS siswa..."
                                class="w-full pl-10 pr-9 py-2.5 bg-white border border-slate-200/90 rounded-2xl text-xs font-semibold focus:ring-teal-500 focus:border-teal-500 shadow-2xs placeholder:text-slate-400 placeholder:font-normal"
                            />

                            <button 
                                v-if="searchQuery" 
                                @click="clearSearch"
                                type="button"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 rounded-full text-slate-400 hover:text-slate-600 transition"
                                title="Hapus pencarian"
                            >
                                <XMarkIcon class="w-4 h-4 stroke-[2.5]" />
                            </button>
                        </div>

                        <!-- Filter Sliders Button -->
                        <button 
                            @click="showMobileFilterSheet = true"
                            type="button"
                            class="w-10 h-10 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition active:scale-95 shrink-0 relative"
                            :class="{'bg-teal-50 text-teal-800 border border-teal-200': hasActiveFilters}"
                            title="Filter Lanjutan"
                        >
                            <AdjustmentsHorizontalIcon class="w-5 h-5 stroke-[2]" />
                            <span v-if="hasActiveFilters" class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-teal-600 rounded-full border-2 border-white"></span>
                        </button>
                    </div>

                    <!-- Horizontal Scrollable Filter Chips -->
                    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-0.5 text-xs">
                        <!-- Gender: Semua -->
                        <button 
                            @click="setGenderFilter('')"
                            type="button"
                            class="px-4 py-2 rounded-full font-black text-xs shrink-0 transition-all flex items-center gap-1.5 shadow-2xs border"
                            :class="!genderFilter 
                                ? 'bg-slate-900 text-white border-slate-900' 
                                : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                        >
                            <UserGroupIcon class="w-3.5 h-3.5" />
                            <span>Semua Gender</span>
                        </button>

                        <!-- Gender: Putra -->
                        <button 
                            @click="setGenderFilter('L')"
                            type="button"
                            class="px-4 py-2 rounded-full font-black text-xs shrink-0 transition-all flex items-center gap-1.5 shadow-2xs border"
                            :class="genderFilter === 'L' 
                                ? 'bg-sky-600 text-white border-sky-600' 
                                : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                        >
                            <span>👦</span>
                            <span>Laki-laki</span>
                        </button>

                        <!-- Gender: Putri -->
                        <button 
                            @click="setGenderFilter('P')"
                            type="button"
                            class="px-4 py-2 rounded-full font-black text-xs shrink-0 transition-all flex items-center gap-1.5 shadow-2xs border"
                            :class="genderFilter === 'P' 
                                ? 'bg-pink-600 text-white border-pink-600' 
                                : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                        >
                            <span>👧</span>
                            <span>Perempuan</span>
                        </button>

                        <!-- Divider if in all scope and classes available -->
                        <div v-if="activeScope === 'all' && classrooms.length > 0" class="h-4 w-px bg-slate-200 shrink-0 mx-0.5"></div>

                        <!-- Classroom Chips (Visible in 'all' scope) -->
                        <template v-if="activeScope === 'all'">
                            <button 
                                v-for="cls in classrooms" 
                                :key="cls.id"
                                @click="setClassFilter(cls.id)"
                                type="button"
                                class="px-3.5 py-1.5 rounded-full font-black text-xs shrink-0 transition-all border shadow-2xs"
                                :class="classFilter === cls.id 
                                    ? 'bg-teal-700 text-white border-teal-700' 
                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                            >
                                Kelas {{ cls.name }}
                            </button>
                        </template>

                        <!-- Reset Filter Button (if active) -->
                        <button 
                            v-if="hasActiveFilters"
                            @click="resetFilter"
                            type="button"
                            class="px-2 py-1 text-rose-600 font-black text-xs shrink-0 hover:underline ml-1"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <!-- 4. Student Mobile Touch Cards List (Matching Screenshot Exact) -->
                <div class="px-4 space-y-3 pt-1">
                    <!-- Empty State -->
                    <div v-if="students.data.length === 0" class="bg-white rounded-3xl p-8 text-center border border-slate-200/80 shadow-xs space-y-3">
                        <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center mx-auto">
                            <UserIcon class="w-6 h-6 stroke-[2]" />
                        </div>
                        <div>
                            <p class="font-black text-sm text-slate-800">Tidak ada data siswa</p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ searchQuery ? 'Tidak ada siswa yang cocok dengan kata kunci pencarian.' : 'Data siswa belum tersedia pada filter ini.' }}
                            </p>
                        </div>
                        <button 
                            v-if="hasActiveFilters"
                            @click="resetFilter"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-black transition active:scale-95"
                        >
                            <span>Reset Filter</span>
                        </button>
                    </div>

                    <!-- Student Cards -->
                    <div
                        v-for="student in students.data"
                        :key="student.id"
                        @click="goToDetail(student)"
                        class="bg-white rounded-2xl border border-slate-100 p-4 shadow-[0_2px_12px_rgba(0,0,0,0.03)] space-y-3 relative overflow-hidden transition-all hover:shadow-md hover:border-teal-200 active:scale-[0.99] cursor-pointer"
                    >
                        <!-- Top Row: Avatar + Name + Class & NIS + Gender Badge & Chevron -->
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <!-- Avatar Round with Gender Color (Pink for P, Sky for L) -->
                                <div 
                                    class="w-12 h-12 rounded-full flex-shrink-0 overflow-hidden flex items-center justify-center font-black text-sm shadow-2xs select-none"
                                    :class="student.gender === 'P' ? 'bg-pink-100/90 text-pink-600' : 'bg-sky-100/90 text-sky-700'"
                                >
                                    <img v-if="student.photo" :src="`/storage/${student.photo}`" :alt="student.full_name" class="w-full h-full object-cover">
                                    <span v-else>{{ student.full_name?.substring(0, 2).toUpperCase() }}</span>
                                </div>

                                <!-- Name, Class badge, NIS -->
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-black text-slate-900 text-sm leading-tight truncate uppercase tracking-tight">
                                        {{ student.full_name }}
                                    </h3>
                                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-teal-50 text-teal-800 text-[11px] font-black border border-teal-200/60">
                                            {{ student.classroom?.name || 'Belum Masuk Kelas' }}
                                        </span>
                                        <span class="text-xs text-slate-400 font-medium">
                                            NIS: <strong class="text-slate-600 font-bold">{{ student.nis || '-' }}</strong>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Side: Circular Gender Badge + Chevron Right -->
                            <div class="flex items-center gap-2 shrink-0">
                                <span 
                                    class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black border"
                                    :class="student.gender === 'P' ? 'bg-pink-50 text-pink-600 border-pink-200/70' : 'bg-sky-50 text-sky-700 border-sky-200/70'"
                                >
                                    {{ student.gender === 'P' ? 'P' : 'L' }}
                                </span>
                                <ChevronRightIcon class="w-4 h-4 text-slate-400" />
                            </div>
                        </div>

                        <!-- Bottom Row: Wali Kelas (Left) | Wali Murid / Kontak (Right) -->
                        <div class="pt-3 border-t border-slate-100/80 grid grid-cols-2 gap-2 text-xs">
                            <!-- Col 1: Wali Kelas -->
                            <div class="flex items-center gap-2 min-w-0">
                                <AcademicCapIcon class="w-4 h-4 text-teal-600 shrink-0" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider leading-none">Wali Kelas</p>
                                    <p class="text-xs font-bold text-slate-800 truncate mt-1" :title="student.classroom?.homeroom_teacher?.full_name || student.classroom?.homeroomTeacher?.full_name || 'Belum Ditentukan'">
                                        {{ student.classroom?.homeroom_teacher?.full_name || student.classroom?.homeroomTeacher?.full_name || student.classroom?.homeroom_teacher?.user?.name || (student.classroom ? 'Belum Ada' : 'Belum Masuk Kelas') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Col 2: Wali Murid / No HP -->
                            <div 
                                class="flex items-center gap-2 min-w-0 border-l border-slate-100 pl-3 transition-colors"
                                :class="student.parent_phone || student.guardian_phone ? 'hover:text-emerald-700' : ''"
                                @click.stop="openWhatsApp(student)"
                                :title="student.parent_phone || student.guardian_phone ? 'Hubungi via WhatsApp' : ''"
                            >
                                <UserGroupIcon class="w-4 h-4 text-slate-400 shrink-0" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider leading-none">Wali Murid</p>
                                    <p class="text-xs font-semibold text-slate-700 truncate mt-1">
                                        {{ student.parent_name || student.guardian_name || (student.parent_phone ? student.parent_phone : 'Belum diisi') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination Mobile -->
                    <Pagination :links="students.links" class="pt-2" />
                </div>

                <!-- 5. Mobile Filter Bottom Sheet Modal -->
                <div 
                    v-if="showMobileFilterSheet" 
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-xs transition-opacity"
                    @click.self="showMobileFilterSheet = false"
                >
                    <div class="w-full max-w-lg bg-white rounded-t-3xl sm:rounded-3xl p-5 shadow-2xl space-y-4 max-h-[85vh] overflow-y-auto animate-in slide-in-from-bottom duration-200">
                        <!-- Header Sheet -->
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <AdjustmentsHorizontalIcon class="w-5 h-5 text-teal-700" />
                                <h3 class="font-black text-slate-900 text-sm">Filter Data Siswa</h3>
                            </div>
                            <button 
                                @click="showMobileFilterSheet = false" 
                                type="button" 
                                class="p-1.5 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition"
                            >
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Filter: Cakupan Walas (If Walas) -->
                        <div v-if="isHomeroom && homeroomClass" class="space-y-2">
                            <label class="text-xs font-black text-slate-700 uppercase tracking-wider">Cakupan Data</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button 
                                    @click="switchScope('homeroom')"
                                    type="button"
                                    class="p-2.5 rounded-xl border text-xs font-bold text-left transition"
                                    :class="activeScope === 'homeroom' ? 'bg-teal-50 border-teal-500 text-teal-900 ring-1 ring-teal-500' : 'bg-slate-50 border-slate-200 text-slate-700'"
                                >
                                    <p class="font-black">Kelas Bimbingan</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">{{ homeroomClass.name }} ({{ homeroomClass.student_count }} Siswa)</p>
                                </button>
                                <button 
                                    @click="switchScope('all')"
                                    type="button"
                                    class="p-2.5 rounded-xl border text-xs font-bold text-left transition"
                                    :class="activeScope === 'all' ? 'bg-teal-50 border-teal-500 text-teal-900 ring-1 ring-teal-500' : 'bg-slate-50 border-slate-200 text-slate-700'"
                                >
                                    <p class="font-black">Semua Siswa</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Unit {{ activeUnit?.code || 'Sekolah' }} ({{ totalUnitStudents }} Siswa)</p>
                                </button>
                            </div>
                        </div>

                        <!-- Filter: Kelas (Visible when in 'all' scope) -->
                        <div v-if="activeScope === 'all' && classrooms.length > 0" class="space-y-2">
                            <label class="text-xs font-black text-slate-700 uppercase tracking-wider">Pilih Kelas</label>
                            <div class="flex flex-wrap gap-1.5 max-h-40 overflow-y-auto p-1">
                                <button 
                                    @click="classFilter = ''"
                                    type="button"
                                    class="px-3 py-1.5 rounded-xl text-xs font-black transition border"
                                    :class="!classFilter ? 'bg-teal-700 text-white border-teal-700' : 'bg-slate-50 text-slate-600 border-slate-200'"
                                >
                                    Semua Kelas
                                </button>
                                <button 
                                    v-for="cls in classrooms"
                                    :key="'sheet-'+cls.id"
                                    @click="classFilter = cls.id"
                                    type="button"
                                    class="px-3 py-1.5 rounded-xl text-xs font-black transition border"
                                    :class="classFilter === cls.id ? 'bg-teal-700 text-white border-teal-700' : 'bg-slate-50 text-slate-600 border-slate-200'"
                                >
                                    {{ cls.name }}
                                </button>
                            </div>
                        </div>

                        <!-- Filter: Tahun Ajaran -->
                        <div v-if="academicYears && academicYears.length > 0" class="space-y-2">
                            <label class="text-xs font-black text-slate-700 uppercase tracking-wider">Tahun Ajaran</label>
                            <select 
                                v-model="yearFilter" 
                                class="w-full px-3 py-2 rounded-xl text-xs font-bold bg-slate-50 border border-slate-200 text-slate-800 focus:ring-teal-500 focus:border-teal-500"
                            >
                                <option value="">Semua Tahun Ajaran</option>
                                <option v-for="yr in academicYears" :key="yr.id" :value="yr.id">
                                    {{ yr.name }} {{ yr.is_active ? '(Aktif)' : '' }}
                                </option>
                            </select>
                        </div>

                        <!-- Action Sheet Footer -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-3">
                            <button 
                                @click="resetFilter(); showMobileFilterSheet = false;"
                                type="button"
                                class="px-4 py-2 text-rose-600 hover:text-rose-700 text-xs font-black"
                            >
                                Reset Filter
                            </button>
                            <button 
                                @click="showMobileFilterSheet = false"
                                type="button"
                                class="px-5 py-2.5 bg-teal-700 hover:bg-teal-800 text-white rounded-xl text-xs font-black shadow-xs transition active:scale-95"
                            >
                                Terapkan Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END MOBILE VIEW -->

            <!-- 🖥️ DESKTOP VIEW (hidden md:block) -->
            <div class="hidden md:block space-y-6">
                <!-- Walas Scope Switcher (Desktop) -->
                <div v-if="isHomeroom && homeroomClass" class="bg-white/80 backdrop-blur-xl p-1.5 rounded-2xl border border-white/60 shadow-xs flex items-center gap-2 max-w-md">
                    <button
                        @click="switchScope('homeroom')"
                        type="button"
                        class="flex-1 py-2 px-3 rounded-xl font-extrabold text-xs transition-all flex items-center justify-center gap-2"
                        :class="activeScope === 'homeroom' 
                            ? 'bg-teal-700 text-white shadow-xs' 
                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                    >
                        <SparklesIcon class="w-4 h-4 text-amber-300" />
                        <span>Kelas Saya ({{ homeroomClass.name }})</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-white/20 text-white">
                            {{ homeroomClass.student_count }}
                        </span>
                    </button>
                    <button
                        @click="switchScope('all')"
                        type="button"
                        class="flex-1 py-2 px-3 rounded-xl font-extrabold text-xs transition-all flex items-center justify-center gap-2"
                        :class="activeScope === 'all' 
                            ? 'bg-teal-700 text-white shadow-xs' 
                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                    >
                        <BuildingOffice2Icon class="w-4 h-4 text-teal-300" />
                        <span>Semua Siswa</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-white/20 text-white">
                            {{ totalUnitStudents }}
                        </span>
                    </button>
                </div>

                <!-- Toolbar -->
                <div class="flex flex-col md:flex-row items-center gap-4">
                    <!-- Search Bar -->
                    <div class="relative group flex-1 w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 group-focus-within:text-namira-teal transition-colors">
                             <MagnifyingGlassIcon v-if="!isLoading" class="w-5 h-5" />
                             <ArrowPathIcon v-else class="animate-spin h-5 w-5 text-namira-teal" />
                        </div>
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Cari Nama / NIS..." 
                            class="pl-10 pr-4 py-2.5 w-full bg-white/50 backdrop-blur-sm border-white/50 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                        >
                    </div>

                    <!-- Filter Toggle -->
                    <button @click="toggleFilter" class="px-4 py-2.5 bg-white/50 backdrop-blur-sm border border-white/50 text-gray-600 rounded-2xl text-sm font-bold hover:bg-white hover:shadow-md transition-all flex items-center justify-center gap-2 active:scale-95 h-[46px]" :class="{'border-namira-teal text-namira-teal bg-teal-50/50': showFilter}">
                        <FunnelIcon class="w-5 h-5" />
                        <span class="hidden md:inline">Filter</span>
                    </button>

                    <!-- Import & Management Buttons (Only for canManage) -->
                    <template v-if="canManage">
                        <!-- Import VA Button -->
                        <button @click="openVaImportModal" class="px-4 py-2.5 bg-blue-50 text-blue-600 border border-blue-100 rounded-2xl text-sm font-bold hover:bg-blue-100 transition-all flex items-center gap-2 active:scale-95 h-[46px]" title="Update VA Massal">
                            <ArrowPathRoundedSquareIcon class="w-5 h-5" />
                            <span class="hidden md:inline">Update VA</span>
                        </button>

                        <!-- Import CSV Button -->
                        <button @click="openImportModal" class="px-4 py-2.5 bg-gray-50 text-gray-600 border border-gray-200 rounded-2xl text-sm font-bold hover:bg-gray-100 transition-all flex items-center gap-2 active:scale-95 h-[46px]" title="Import CSV">
                            <ArrowUpTrayIcon class="w-5 h-5" />
                            <span class="hidden md:inline">CSV</span>
                        </button>

                        <!-- Import Excel Button -->
                        <button @click="openExcelImportModal" class="px-4 py-2.5 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-2xl text-sm font-bold hover:bg-emerald-100 transition-all flex items-center gap-2 active:scale-95 h-[46px]" title="Import dari Excel">
                            <ArrowUpTrayIcon class="w-5 h-5" />
                            <span class="hidden md:inline">Excel</span>
                        </button>

                        <!-- Add Button -->
                        <button 
                            @click="openCreateModal"
                            class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 whitespace-nowrap active:scale-95 h-[46px]"
                        >
                            <PlusIcon class="w-5 h-5" />
                            <span>Tambah Siswa</span>
                        </button>
                    </template>
                </div>
                
                <div v-if="showFilter" class="mb-6 px-6 py-4 bg-white/80 backdrop-blur-xl rounded-2xl border border-white/50 shadow-sm flex flex-wrap items-center gap-4 animate-fade-in-down">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-gray-600">Tahun:</span>
                        <select v-model="yearFilter" class="px-3 py-1.5 rounded-xl text-sm border-gray-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50">
                            <option value="">Semua Tahun</option>
                            <option v-for="year in academicYears" :key="year.id" :value="year.id">
                                {{ year.name }} {{ year.is_active ? '(Aktif)' : '' }}
                            </option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-gray-600">Gender:</span>
                        <select v-model="genderFilter" class="px-3 py-1.5 rounded-xl text-sm border-gray-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50">
                            <option value="">Semua</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-gray-600">Kelas:</span>
                        <select v-model="classFilter" class="px-3 py-1.5 rounded-xl text-sm border-gray-200 focus:border-namira-teal focus:ring-namira-teal bg-white/50">
                            <option value="">Semua Kelas</option>
                            <option v-for="cls in classrooms" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                        </select>
                    </div>
                    <button @click="resetFilter" class="ml-auto text-xs text-red-500 hover:text-red-700 font-bold hover:underline">
                        Reset Filter
                    </button>
                </div>

                <!-- Main Content Container -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                    <!-- Student Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-500 uppercase bg-white/50 border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-5 font-extrabold tracking-wider">Siswa</th>
                                    <th class="px-6 py-5 font-extrabold tracking-wider">Identitas</th>
                                    <th class="px-6 py-5 font-extrabold tracking-wider">L/P</th>
                                    <th class="px-6 py-5 font-extrabold tracking-wider">Kelas</th>
                                    <th class="px-6 py-5 font-extrabold tracking-wider text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <!-- Empty State -->
                                <tr v-if="students.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center p-8">
                                             <div class="bg-indigo-50 p-6 rounded-full mb-4">
                                                <UserIcon class="w-12 h-12 text-indigo-400" />
                                             </div>
                                            <p class="font-bold text-lg text-gray-800 mb-1">Belum ada data siswa</p>
                                            <p class="text-sm text-gray-500 max-w-xs">Data siswa masih kosong. Silakan tambahkan siswa baru atau import dari file CSV.</p>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-for="student in students.data" :key="student.id" class="group hover:bg-teal-50/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-full bg-gray-100 flex-shrink-0 overflow-hidden border-2 border-white shadow-sm group-hover:border-namira-teal/50 transition-colors">
                                                <img v-if="student.photo" :src="`/storage/${student.photo}`" class="w-full h-full object-cover">
                                                <div v-else class="w-full h-full flex items-center justify-center text-xs font-bold text-gray-400">
                                                    {{ student.full_name?.substring(0,2).toUpperCase() }}
                                                </div>
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-800 text-base group-hover:text-namira-teal transition-colors">{{ student.full_name }}</div>
                                                <div class="text-xs text-gray-500 flex items-center gap-1">
                                                    <EnvelopeIcon class="w-3 h-3 opacity-70" />
                                                    {{ student.user?.email || 'Belum ada email' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-1">
                                            <div class="text-[10px] font-mono font-bold text-gray-500 uppercase">NIS: <span class="text-gray-700">{{ student.nis || '-' }}</span></div>
                                            <div class="text-[10px] font-mono font-bold text-gray-500 uppercase">NISN: <span class="text-gray-700">{{ student.nisn || '-' }}</span></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 text-[10px] font-extrabold uppercase rounded-full border shadow-sm" 
                                              :class="student.gender === 'L' ? 'bg-blue-50 text-blue-600 border-blue-100' : 'bg-pink-50 text-pink-600 border-pink-100'">
                                            {{ student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="student.classroom" class="flex flex-col">
                                             <span class="font-bold text-gray-700 text-xs bg-gray-100 px-2 py-1 rounded-lg w-fit border border-gray-200">{{ student.classroom.name }}</span>
                                             <span class="text-[11px] text-teal-700 font-semibold mt-1">
                                                 Wali: {{ student.classroom?.homeroom_teacher?.full_name || student.classroom?.homeroomTeacher?.full_name || '-' }}
                                             </span>
                                        </div>
                                        <span v-else class="text-xs text-gray-400 italic">Belum Masuk Kelas</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
                                            <Link :href="route('yayasan.students.show', student.id)" class="p-2 rounded-xl text-gray-400 hover:text-namira-teal hover:bg-teal-50 transition-colors border border-transparent hover:border-teal-100" title="Lihat Detail">
                                                <EyeIcon class="w-4 h-4" />
                                            </Link>
                                            <template v-if="canManage">
                                                <button @click="openEditModal(student)" class="p-2 rounded-xl text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition-colors border border-transparent hover:border-amber-100" title="Edit Data">
                                                    <PencilSquareIcon class="w-4 h-4" />
                                                </button>
                                                <button @click="confirmDelete(student)" class="p-2 rounded-xl text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors border border-transparent hover:border-red-100" title="Hapus Siswa">
                                                    <TrashIcon class="w-4 h-4" />
                                                </button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <Pagination :links="students.links" class="p-6 border-t border-gray-100 bg-white/50" />
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal (Premium) -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-8 border border-gray-100 overflow-hidden transform transition-all scale-100">
                         <!-- Decorative Shape -->
                        <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-namira-teal/10 to-blue-50 rounded-bl-full -mr-10 -mt-10 pointer-events-none"></div>

                        <h3 class="text-2xl font-bold text-gray-900 mb-8 pb-4 border-b border-gray-100 relative z-10 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-namira-teal/10 flex items-center justify-center text-namira-teal">
                                <PencilSquareIcon v-if="isEditing" class="w-6 h-6" />
                                <PlusIcon v-else class="w-6 h-6" />
                            </div>
                            {{ isEditing ? 'Edit Data Siswa' : 'Registrasi Siswa Baru' }}
                        </h3>

                        <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-6 relative z-10">
                            <!-- Photo Upload -->
                           <div class="md:col-span-2 flex justify-center mb-4">
                                <div class="relative group cursor-pointer text-center">
                                    <div class="w-32 h-32 mx-auto rounded-full bg-gray-50 border-4 border-white shadow-lg flex items-center justify-center overflow-hidden hover:border-namira-teal transition-all group-hover:shadow-xl">
                                        <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover">
                                        <div v-else class="flex flex-col items-center justify-center text-gray-400 group-hover:text-namira-teal transition-colors">
                                            <CameraIcon class="w-10 h-10 mb-1" />
                                            <span class="text-[10px] font-bold uppercase tracking-wide">Upload Foto</span>
                                        </div>
                                    </div>
                                    <div class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="text-white text-xs font-bold">Ubah Foto</span>
                                    </div>
                                    <input type="file" @change="handlePhotoUpload" class="absolute inset-0 opacity-0 cursor-pointer w-32 mx-auto h-32" accept="image/*">
                                    <div class="text-xs text-red-500 mt-2 font-bold" v-if="form.errors.photo">{{ form.errors.photo }}</div>
                                </div>
                           </div>

                            <div class="md:col-span-2">
                                <InputLabel for="full_name" value="Nama Lengkap *" class="text-sm font-bold text-gray-700" />
                                <TextInput id="full_name" v-model="form.full_name" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" required placeholder="Nama sesuai ijazah" />
                                <InputError :message="form.errors.full_name" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="gender" value="Jenis Kelamin *" class="text-sm font-bold text-gray-700" />
                                <select id="gender" v-model="form.gender" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-gray-700 bg-gray-50/50">
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                                <InputError :message="form.errors.gender" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="classroom" value="Kelas (Tahun Ini)" class="text-sm font-bold text-gray-700" />
                                <select id="classroom" v-model="form.classroom_id" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-gray-700 bg-gray-50/50">
                                    <option value="">-- Belum Masuk Kelas --</option>
                                    <option v-for="cls in classrooms" :key="cls.id" :value="cls.id">
                                        {{ cls.name }} (Lvl {{ cls.level }})
                                    </option>
                                </select>
                                <InputError :message="form.errors.classroom_id" class="mt-1" />
                            </div>

                             <div>
                                <InputLabel for="nis" value="NIS" class="text-sm font-bold text-gray-700" />
                                <TextInput id="nis" v-model="form.nis" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="Nomor Induk Sekolah" />
                                <InputError :message="form.errors.nis" class="mt-1" />
                            </div>

                             <div>
                                <InputLabel for="nisn" value="NISN" class="text-sm font-bold text-gray-700" />
                                <TextInput id="nisn" v-model="form.nisn" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50" placeholder="Nomor Induk Nasional" />
                                <InputError :message="form.errors.nisn" class="mt-1" />
                            </div>

                             <div class="md:col-span-2">
                                <InputLabel for="va_number" value="Nomor Virtual Account (VA)" class="text-sm font-bold text-gray-700" />
                                <TextInput id="va_number" v-model="form.va_number" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50/50 font-mono font-bold tracking-wider text-gray-700" placeholder="Contoh: 98812345678" />
                                <p class="text-xs text-gray-400 mt-1.5">Nomor VA dari Bank Jatim (Fixed/Statis) untuk integrasi pembayaran otomatis.</p>
                                <InputError :message="form.errors.va_number" class="mt-1" />
                            </div>

                            <div class="md:col-span-2">
                                <InputLabel for="address" value="Alamat Lengkap" class="text-sm font-bold text-gray-700" />
                                <textarea id="address" v-model="form.address" rows="2" class="w-full mt-1.5 px-4 py-3 text-base border-gray-200 rounded-xl focus:ring-namira-teal focus:border-namira-teal text-gray-700 bg-gray-50/50" placeholder="Alamat domisili saat ini"></textarea>
                                <InputError :message="form.errors.address" class="mt-1" />
                            </div>

                            <div class="md:col-span-2 border-t border-gray-100 pt-5">
                                <InputLabel for="email" value="Email (Akun Login) *" class="text-sm font-bold text-gray-700" />
                                <TextInput id="email" v-model="form.email" type="email" class="w-full mt-1.5 h-12 px-4 text-base border-gray-200 focus:border-namira-teal focus:ring-namira-teal rounded-xl bg-gray-50" :disabled="isEditing" required placeholder="email@siswa.com" />
                                <p class="text-xs text-gray-400 mt-2 flex items-center gap-1" v-if="!isEditing">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                      <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1-.244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd" />
                                    </svg>
                                    Password default akan menggunakan NIS atau 'siswa123'
                                </p>
                                <InputError :message="form.errors.email" class="mt-1" />
                            </div>

                            <div class="md:col-span-2 flex justify-end gap-3 pt-6 border-t border-gray-50">
                                <button type="button" @click="closeModal" class="px-6 py-2.5 text-gray-500 font-bold hover:bg-gray-50 rounded-xl transition-colors">Batal</button>
                                <PrimaryButton :disabled="form.processing" class="rounded-xl px-8 shadow-lg shadow-namira-teal/20">{{ isEditing ? 'Simpan Perubahan' : 'Registrasi Siswa' }}</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Import Modal -->
        <Teleport to="body">
            <div v-if="showImportModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showImportModal = false"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-lg w-full p-8 border border-gray-100 transform transition-all scale-100">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Import Data Siswa (CSV)</h3>
                        <p class="text-sm text-gray-500 mb-6">Upload file CSV dengan format: <br><code class="bg-gray-100 px-1 rounded">Nama, Email, NIS, Gender (L/P)</code></p>
                        
                        <form @submit.prevent="submitImport">
                            <div class="mb-6">
                                <label class="block w-full p-4 border-2 border-dashed border-gray-300 rounded-2xl text-center cursor-pointer hover:border-namira-teal hover:bg-teal-50/30 transition-all">
                                    <div v-if="!importForm.file" class="flex flex-col items-center">
                                        <ArrowUpTrayIcon class="w-8 h-8 text-gray-400 mb-2" />
                                        <span class="text-sm font-bold text-gray-600">Klik untuk pilih file CSV</span>
                                    </div>
                                    <div v-else class="flex items-center justify-center gap-2 text-namira-teal font-bold">
                                        <DocumentTextIcon class="w-5 h-5" />
                                        {{ importForm.file.name }}
                                    </div>
                                    <input type="file" class="hidden" accept=".csv, .txt" @change="handleImportFile">
                                </label>
                                <InputError :message="importForm.errors.file" class="mt-2" />
                            </div>

                            <div class="flex justify-end gap-3">
                                <button type="button" @click="showImportModal = false" class="px-5 py-2.5 text-gray-500 font-bold hover:bg-gray-50 rounded-xl transition-colors">Batal</button>
                                <PrimaryButton :disabled="importForm.processing" class="rounded-xl px-6 shadow-lg shadow-namira-teal/20">
                                    <span v-if="importForm.processing">Mengupload...</span>
                                    <span v-else>Import Data</span>
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Modal (Premium) -->
        <Teleport to="body">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showDeleteConfirm = false"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-sm w-full p-8 text-center border border-gray-100 transform transition-all scale-100">
                         <div class="mx-auto flex h-20 w-20 flex-shrink-0 items-center justify-center rounded-full bg-red-50 mb-6 animate-pulse">
                             <ExclamationTriangleIcon class="h-10 w-10 text-red-600" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Hapus Data Siswa?</h3>
                        <p class="text-gray-500 mb-8 leading-relaxed">Siswa <span class="font-bold text-gray-800">"{{ itemToDelete?.full_name }}"</span> akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.</p>
                        <div class="flex justify-center gap-4">
                            <button @click="showDeleteConfirm = false" class="px-6 py-2.5 bg-white border border-gray-200 rounded-xl font-bold text-gray-600 hover:bg-gray-50 transition-colors">Batal</button>
                            <button @click="deleteItem" class="px-6 py-2.5 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 shadow-lg shadow-red-500/30 transition-all hover:scale-105">Hapus Permanen</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Import VA Modal -->
        <Teleport to="body">
            <div v-if="showVaImportModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showVaImportModal = false"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-lg w-full p-8 border border-gray-100 transform transition-all scale-100">
                        <div class="flex items-center gap-3 mb-2">
                             <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                                <ArrowPathRoundedSquareIcon class="w-6 h-6" />
                             </div>
                             <h3 class="text-xl font-bold text-gray-900">Update Massal No. VA</h3>
                        </div>
                        <p class="text-sm text-gray-500 mb-6 pl-12 line-clamp-2">Upload file CSV berisi pasangan NIS dan Nomor VA untuk update data siswa secara massal.</p>
                        
                        <div class="bg-gray-50 rounded-xl p-4 mb-6 border border-gray-100 text-xs font-mono text-gray-600">
                            Format CSV: <span class="font-bold text-gray-900">NIS, VA_NUMBER</span>
                            <br>Contoh: <br>
                            1001, 988001001<br>
                            1002, 988001002
                        </div>

                        <form @submit.prevent="submitVaImport">
                            <div class="mb-6">
                                <label class="block w-full p-6 border-2 border-dashed border-gray-300 rounded-2xl text-center cursor-pointer hover:border-blue-500 hover:bg-blue-50/10 transition-all">
                                    <div v-if="!vaImportForm.file" class="flex flex-col items-center">
                                        <ArrowUpTrayIcon class="w-8 h-8 text-gray-400 mb-2" />
                                        <span class="text-sm font-bold text-gray-600">Pilih File CSV</span>
                                    </div>
                                    <div v-else class="flex items-center justify-center gap-2 text-blue-600 font-bold">
                                        <DocumentTextIcon class="w-5 h-5" />
                                        {{ vaImportForm.file.name }}
                                    </div>
                                    <input type="file" class="hidden" accept=".csv, .txt" @change="handleVaImportFile">
                                </label>
                                <InputError :message="vaImportForm.errors.file" class="mt-2" />
                            </div>

                            <div class="flex justify-end gap-3">
                                <button type="button" @click="showVaImportModal = false" class="px-5 py-2.5 text-gray-500 font-bold hover:bg-gray-50 rounded-xl transition-colors">Batal</button>
                                <PrimaryButton :disabled="vaImportForm.processing" class="rounded-xl px-6 bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-500/20">
                                    <span v-if="vaImportForm.processing">Mengupdate...</span>
                                    <span v-else>Update VA</span>
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Excel Import Modal -->
        <Teleport to="body">
            <div v-if="showExcelImportModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showExcelImportModal = false"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-lg w-full p-8 border border-gray-100 transform transition-all scale-100">
                        <div class="flex items-center gap-3 mb-2">
                             <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                                <ArrowUpTrayIcon class="w-6 h-6" />
                             </div>
                             <h3 class="text-xl font-bold text-gray-900">Import dari Excel</h3>
                        </div>
                        <p class="text-sm text-gray-500 mb-6 pl-12">Upload file Excel (.xlsx) - siswa akan otomatis masuk ke tahun ajaran aktif.</p>
                        
                        <div class="bg-gray-50 rounded-xl p-4 mb-6 border border-gray-100 text-xs font-mono text-gray-600">
                            Format: <span class="font-bold text-gray-900">Nama, Email, NIS, NISN, Gender (L/P)</span>
                        </div>

                        <form @submit.prevent="submitExcelImport">
                            <div class="mb-4">
                                <InputLabel value="Pilih Kelas (Opsional)" class="mb-2" />
                                <select v-model="excelImportForm.classroom_id" class="w-full px-3 py-2 rounded-xl text-sm border-gray-200 focus:border-namira-teal focus:ring-namira-teal">
                                    <option value="">-- Tanpa Kelas --</option>
                                    <option v-for="cls in classrooms" :key="cls.id" :value="cls.id">
                                        {{ cls.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="mb-6">
                                <label class="block w-full p-6 border-2 border-dashed border-gray-300 rounded-2xl text-center cursor-pointer hover:border-emerald-500 hover:bg-emerald-50/10 transition-all">
                                    <div v-if="!excelImportForm.file" class="flex flex-col items-center">
                                        <ArrowUpTrayIcon class="w-8 h-8 text-gray-400 mb-2" />
                                        <span class="text-sm font-bold text-gray-600">Pilih File Excel (.xlsx)</span>
                                    </div>
                                    <div v-else class="flex items-center justify-center gap-2 text-emerald-600 font-bold">
                                        <DocumentTextIcon class="w-5 h-5" />
                                        {{ excelImportForm.file.name }}
                                    </div>
                                    <input type="file" class="hidden" accept=".xlsx, .xls" @change="handleExcelImportFile">
                                </label>
                                <InputError :message="excelImportForm.errors.file" class="mt-2" />
                            </div>

                            <div class="flex justify-end gap-3">
                                <button type="button" @click="showExcelImportModal = false" class="px-5 py-2.5 text-gray-500 font-bold hover:bg-gray-50 rounded-xl transition-colors">Batal</button>
                                <PrimaryButton :disabled="excelImportForm.processing || !excelImportForm.file" class="rounded-xl px-6 shadow-lg shadow-namira-teal/20">
                                    <span v-if="excelImportForm.processing">Mengimport...</span>
                                    <span v-else>Import Siswa</span>
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
