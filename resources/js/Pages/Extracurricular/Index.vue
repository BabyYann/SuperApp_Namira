<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    CalendarDaysIcon,
    ClockIcon,
    MapPinIcon,
    UserGroupIcon,
    AcademicCapIcon,
    UserIcon,
    XMarkIcon,
    PencilSquareIcon,
    TrashIcon,
    PhotoIcon,
    CheckCircleIcon,
    AdjustmentsHorizontalIcon,
    BuildingLibraryIcon,
    EllipsisVerticalIcon,
    ArrowRightIcon,
    CheckIcon,
    FlagIcon,
    BuildingOfficeIcon,
} from '@heroicons/vue/24/outline';
import { 
    HeartIcon as HeartIconSolid 
} from '@heroicons/vue/24/solid';
import { HeartIcon as HeartIconOutline } from '@heroicons/vue/24/outline';

const props = defineProps({
    activities: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    rooms: { type: Array, default: () => [] },
    classrooms: { type: Array, default: () => [] },
    availableInstructors: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    canManage: { type: Boolean, default: true },
    isGlobalAdmin: { type: Boolean, default: false },
    isCoach: { type: Boolean, default: false },
    activeYear: { type: Object, default: () => null },
    units: { type: Array, default: () => [] },
});

// Search & Filter State
const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || 'all');
const selectedDay = ref(props.filters.day || 'all');
const activeMenuId = ref(null);

// Favorites persisted in localStorage
const favorites = ref(new Set());
onMounted(() => {
    try {
        const stored = localStorage.getItem('namira_ekskul_favorites');
        if (stored) {
            favorites.value = new Set(JSON.parse(stored));
        }
    } catch (e) {
        console.error('Failed to load favorites', e);
    }

    const handleClickOutside = (e) => {
        if (!e.target.closest('.card-action-menu-container')) {
            activeMenuId.value = null;
        }
    };
    window.addEventListener('click', handleClickOutside);
    onUnmounted(() => window.removeEventListener('click', handleClickOutside));
});

const toggleFavorite = (id) => {
    if (favorites.value.has(id)) {
        favorites.value.delete(id);
    } else {
        favorites.value.add(id);
    }
    try {
        localStorage.setItem('namira_ekskul_favorites', JSON.stringify([...favorites.value]));
    } catch (e) {
        console.error('Failed to save favorites', e);
    }
};

const toggleActionMenu = (id) => {
    activeMenuId.value = activeMenuId.value === id ? null : id;
};

// Modals
const showModal = ref(false);
const isEditing = ref(false);
const editingActivityId = ref(null);
const isCompressing = ref(false);
const photoPreview = ref(null);
const photoSizeInfo = ref('');
const fileInput = ref(null);

const categories = [
    { key: 'all', label: 'Semua' },
    { key: 'olahraga', label: 'Olahraga' },
    { key: 'seni_budaya', label: 'Seni & Budaya' },
    { key: 'keagamaan', label: 'Keagamaan' },
    { key: 'sains_teknologi', label: 'Sains & Teknologi' },
    { key: 'kepanduan', label: 'Bela Diri' },
    { key: 'lainnya', label: 'Lainnya' },
];

const daysList = ['Semua Hari', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

const availableGradeLevels = [
    { value: '1', label: 'Kelas 1' },
    { value: '2', label: 'Kelas 2' },
    { value: '3', label: 'Kelas 3' },
    { value: '4', label: 'Kelas 4' },
    { value: '5', label: 'Kelas 5' },
    { value: '6', label: 'Kelas 6' },
];

const genderOptions = [
    { value: 'all', title: 'Semua Siswa', subtitle: 'Putra & Putri' },
    { value: 'male_only', title: 'Khusus Ikhwan', subtitle: 'Putra Saja' },
    { value: 'female_only', title: 'Khusus Akhwat', subtitle: 'Putri Saja' },
];

// Real Activities Source (No Dummy Data)
const allActivities = computed(() => {
    return (props.activities || []).map((item) => {
        let barColor = 'bg-[#00796B]';
        if (item.category === 'seni_budaya') barColor = 'bg-[#F59E0B]';
        else if (item.category === 'sains_teknologi') barColor = 'bg-[#3B82F6]';
        else if (item.category === 'kepanduan') barColor = 'bg-[#EF4444]';

        return {
            ...item,
            bar_color: barColor,
        };
    });
});

// Form
const form = useForm({
    unit_id: '',
    name: '',
    category: 'olahraga',
    day_of_week: 'Jumat',
    start_time: '14:30',
    end_time: '16:00',
    room_id: '',
    location_name: '',
    max_quota: 30,
    is_mandatory: false,
    gender_restriction: 'all',
    target_levels: ['1', '2', '3', '4', '5', '6'],
    description: '',
    cover_image: null,
    instructor_ids: [],
    status: 'active',
});

// Location Type Selector: 'outdoor' | 'classroom' | 'sarpras' | 'external'
const locationType = ref('outdoor');
const outdoorPreset = ref('Lapangan Utama');
const selectedClassroomId = ref('');

const outdoorPresets = [
    'Lapangan Utama',
    'Lapangan Futsal Outdoor',
    'Lapangan Basket Outdoor',
    'Halaman Upacara Sekolah',
    'Masjid / Musholla Sekolah',
    'Area Terbuka / Selasar',
];

const filteredClassrooms = computed(() => {
    const list = props.classrooms || [];
    if (!form.unit_id) return list;
    return list.filter(c => !c.unit_id || String(c.unit_id) === String(form.unit_id));
});

const filteredRooms = computed(() => {
    const list = props.rooms || [];
    if (!form.unit_id) return list;
    return list.filter(r => !r.unit_id || String(r.unit_id) === String(form.unit_id));
});

const setLocationType = (type) => {
    locationType.value = type;
    if (type === 'outdoor') {
        form.room_id = '';
        outdoorPreset.value = 'Lapangan Utama';
        form.location_name = 'Lapangan Utama';
    } else if (type === 'classroom') {
        form.room_id = '';
        if (filteredClassrooms.value.length > 0) {
            selectedClassroomId.value = filteredClassrooms.value[0].id;
            form.location_name = `Ruang Kelas ${filteredClassrooms.value[0].name}`;
        } else {
            selectedClassroomId.value = '';
            form.location_name = 'Ruang Kelas';
        }
    } else if (type === 'sarpras') {
        if (filteredRooms.value.length > 0) {
            form.room_id = filteredRooms.value[0].id;
            const r = filteredRooms.value[0];
            form.location_name = r.name + (r.building ? ` (${r.building})` : '');
        } else {
            form.room_id = '';
            form.location_name = '';
        }
    } else if (type === 'external') {
        form.room_id = '';
        form.location_name = '';
    }
};

const onSelectClassroom = (e) => {
    const clsId = e.target.value;
    selectedClassroomId.value = clsId;
    const cls = filteredClassrooms.value.find(c => String(c.id) === String(clsId));
    if (cls) {
        form.location_name = `Ruang Kelas ${cls.name}`;
    }
};

const onSelectRoom = (e) => {
    const rmId = e.target.value;
    form.room_id = rmId;
    const rm = filteredRooms.value.find(r => String(r.id) === String(rmId));
    if (rm) {
        form.location_name = rm.name + (rm.building ? ` (${rm.building})` : '');
    }
};

const onSelectOutdoorPreset = (val) => {
    outdoorPreset.value = val;
    if (val !== 'custom') {
        form.location_name = val;
    } else {
        form.location_name = '';
    }
};

// Filtered activities computed
const filteredActivities = computed(() => {
    return allActivities.value.filter((item) => {
        const matchesCategory = selectedCategory.value === 'all' || item.category === selectedCategory.value;
        const matchesDay = selectedDay.value === 'all' || selectedDay.value === 'Semua Hari' || item.day_of_week === selectedDay.value;
        const q = searchQuery.value.toLowerCase().trim();
        const matchesSearch = !q || 
            item.name.toLowerCase().includes(q) || 
            (item.description && item.description.toLowerCase().includes(q)) ||
            (item.location_name && item.location_name.toLowerCase().includes(q)) ||
            (item.instructors && item.instructors.some(i => i.user?.name?.toLowerCase().includes(q)));

        return matchesCategory && matchesDay && matchesSearch;
    });
});

// Client-side HTML5 Canvas Auto-Compression
const compressImage = (file) => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const maxWidth = 1280;
                const maxHeight = 1280;
                let width = img.width;
                let height = img.height;

                if (width > height) {
                    if (width > maxWidth) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = Math.round((width * maxHeight) / height);
                        height = maxHeight;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                const compressedBase64 = canvas.toDataURL('image/jpeg', 0.75);
                resolve(compressedBase64);
            };
            img.onerror = (err) => reject(err);
        };
        reader.onerror = (err) => reject(err);
    });
};

const handlePhotoSelect = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    isCompressing.value = true;
    try {
        const originalSizeKB = Math.round(file.size / 1024);
        const compressedBase64 = await compressImage(file);
        
        const compressedSizeKB = Math.round((compressedBase64.length * 3) / 4 / 1024);
        photoSizeInfo.value = `${compressedSizeKB} KB (dihemat dari ${originalSizeKB} KB)`;
        
        photoPreview.value = compressedBase64;
        form.cover_image = compressedBase64;
    } catch (err) {
        console.error('Image compression failed:', err);
        photoSizeInfo.value = 'Kompresi otomatis gagal, memakai file asli';
        form.cover_image = file;
    } finally {
        isCompressing.value = false;
    }
};

const removePhoto = () => {
    photoPreview.value = null;
    photoSizeInfo.value = '';
    form.cover_image = null;
    if (fileInput.value) fileInput.value.value = '';
};

const toggleGradeLevel = (val) => {
    const idx = form.target_levels.indexOf(val);
    if (idx > -1) {
        form.target_levels.splice(idx, 1);
    } else {
        form.target_levels.push(val);
    }
};

const selectAllGradeLevels = () => {
    form.target_levels = availableGradeLevels.map(g => g.value);
};

const resetGradeLevels = () => {
    form.target_levels = [];
};

const openCreateModal = () => {
    isEditing.value = false;
    editingActivityId.value = null;
    form.reset();
    form.clearErrors();
    photoPreview.value = null;
    photoSizeInfo.value = '';
    form.target_levels = ['1', '2', '3', '4', '5', '6'];
    form.status = 'active';
    if (props.units && props.units.length > 0) {
        form.unit_id = props.units.find(u => u.name.includes('SD'))?.id || props.units[0].id;
    }
    locationType.value = 'outdoor';
    outdoorPreset.value = 'Lapangan Utama';
    selectedClassroomId.value = '';
    form.room_id = '';
    form.location_name = 'Lapangan Utama';
    showModal.value = true;
};

const openEditModal = (activity) => {
    isEditing.value = true;
    editingActivityId.value = activity.id;
    activeMenuId.value = null;
    form.clearErrors();
    form.unit_id = activity.unit_id || '';
    form.name = activity.name;
    form.category = activity.category;
    form.day_of_week = activity.day_of_week || 'Jumat';
    form.start_time = activity.start_time || '14:30';
    form.end_time = activity.end_time || '16:00';
    form.room_id = activity.room_id || '';
    form.location_name = activity.location_name || '';
    form.max_quota = activity.max_quota || 30;
    form.is_mandatory = activity.is_mandatory || false;
    form.gender_restriction = activity.gender_restriction || 'all';
    form.target_levels = Array.isArray(activity.target_levels) ? activity.target_levels : ['1', '2', '3', '4', '5', '6'];
    form.description = activity.description || '';
    form.status = activity.status || 'active';
    form.cover_image = null;
    photoPreview.value = activity.cover_image_url || null;
    photoSizeInfo.value = activity.cover_image_url ? 'Foto sampul aktif' : '';

    // Initialize location type
    if (activity.room_id) {
        locationType.value = 'sarpras';
    } else if (activity.location_name) {
        const loc = activity.location_name;
        const matchClass = (props.classrooms || []).find(c => loc.toLowerCase().includes(c.name.toLowerCase()));
        if (matchClass || loc.toLowerCase().includes('kelas')) {
            locationType.value = 'classroom';
            selectedClassroomId.value = matchClass ? matchClass.id : '';
        } else if (outdoorPresets.includes(loc) || loc.toLowerCase().includes('lapangan') || loc.toLowerCase().includes('masjid') || loc.toLowerCase().includes('halaman') || loc.toLowerCase().includes('selasar')) {
            locationType.value = 'outdoor';
            outdoorPreset.value = outdoorPresets.includes(loc) ? loc : 'custom';
        } else {
            locationType.value = 'external';
        }
    } else {
        locationType.value = 'outdoor';
        outdoorPreset.value = 'Lapangan Utama';
        form.location_name = 'Lapangan Utama';
    }

    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        if (typeof editingActivityId.value === 'string' && editingActivityId.value.startsWith('mock-')) {
            showModal.value = false;
            return;
        }
        form.put(route('extracurricular.update', editingActivityId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('extracurricular.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const confirmDelete = (activity) => {
    activeMenuId.value = null;
    if (confirm(`Apakah Anda yakin ingin menghapus ekstrakurikuler "${activity.name}"? Data anggota dan riwayat presensi akan diarsipkan.`)) {
        if (typeof activity.id === 'string' && activity.id.startsWith('mock-')) {
            return;
        }
        router.delete(route('extracurricular.destroy', activity.id), {
            preserveScroll: true,
        });
    }
};

const getCategoryPillBadge = (cat) => {
    switch (cat) {
        case 'olahraga': return 'bg-[#D1F2D9] text-[#1B5E20]';
        case 'seni_budaya': return 'bg-[#FFE8CC] text-[#B76E00]';
        case 'sains_teknologi': return 'bg-[#D0E2FF] text-[#0043CE]';
        case 'kepanduan': return 'bg-[#FFD7D9] text-[#DA1E28]';
        case 'keagamaan': return 'bg-[#E0E7FF] text-[#3730A3]';
        default: return 'bg-slate-100 text-slate-800';
    }
};

const getCategoryLabel = (item) => {
    if (item.category === 'kepanduan') return 'Bela Diri';
    return item.category_label || 'Umum';
};

const getCoverImage = (item) => {
    if (item.cover_image_url) return item.cover_image_url;
    if (item.category === 'olahraga') return '/images/extracurricular/futsal_sd.jpg';
    if (item.category === 'seni_budaya') return '/images/extracurricular/tari_tradisional.jpg';
    if (item.category === 'sains_teknologi') return '/images/extracurricular/robotika.jpg';
    if (item.category === 'kepanduan') return '/images/extracurricular/taekwondo.jpg';
    return '/images/banners/extracurricular_hero.png';
};

const getQuotaPercentage = (item) => {
    if (!item.max_quota || item.max_quota <= 0) return 0;
    return Math.min(100, Math.round(((item.active_members_count || 0) / item.max_quota) * 100));
};

const getLinkDestination = (item) => {
    if (typeof item.id === 'number') {
        return route('extracurricular.show', item.id);
    }
    const firstReal = props.activities?.[0];
    return firstReal ? route('extracurricular.show', firstReal.id) : '#';
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Ekstrakurikuler" />

        <div class="space-y-4 max-w-5xl mx-auto px-2 sm:px-6 lg:px-8 py-2 sm:py-5">
            
            <!-- ========================================================================================= -->
            <!-- UNIFIED HERO SECTION: BANNER IS THE BACKGROUND BEHIND THE 4 STAT METRIC CARDS             -->
            <!-- (Pixel-Perfect Match with Reference Image 1)                                              -->
            <!-- ========================================================================================= -->
            <div class="relative overflow-hidden rounded-3xl border border-emerald-100/70 shadow-xs min-h-[195px] sm:min-h-[240px] flex flex-col justify-end p-2.5 sm:p-4 bg-[#E7F6F2]">
                
                <!-- Full-Bleed Banner Photo Background (Fitted Cleanly with Zero Text Clutter) -->
                <picture class="absolute inset-0 w-full h-full pointer-events-none select-none">
                    <source media="(min-width: 640px)" srcset="/images/banners/extracurricular_hero.webp" type="image/webp" />
                    <source media="(min-width: 640px)" srcset="/images/banners/extracurricular_hero.png" type="image/png" />
                    <img
                        src="/images/banners/extracurricular_kids.png"
                        alt="Banner Ekstrakurikuler"
                        class="w-full h-full object-cover object-top sm:object-center select-none pointer-events-none"
                        loading="eager"
                    />
                </picture>

                <!-- Soft Gradient Overlay at Bottom for Card Readability -->
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/15 via-transparent to-transparent pointer-events-none"></div>

                <!-- 4 Floating Stat Metric Cards Sitting at the Bottom of Banner Background -->
                <div class="relative z-10 grid grid-cols-4 gap-2 sm:gap-3">
                    <!-- Total Kegiatan -->
                    <div class="bg-white/95 backdrop-blur-xs rounded-2xl p-2 sm:p-3.5 border border-white/90 shadow-md shadow-emerald-950/10 flex flex-col justify-between hover:border-teal-200 transition-colors">
                        <div class="w-8 h-8 rounded-xl bg-teal-50 text-[#00796B] flex items-center justify-center border border-teal-100/60">
                            <UserGroupIcon class="w-4 h-4 stroke-[2.2]" />
                        </div>
                        <div class="text-base sm:text-xl font-black text-slate-800 mt-2 leading-none">
                            {{ stats.total_activities ?? 0 }}
                        </div>
                        <div class="text-[9px] sm:text-[11px] font-semibold text-slate-500 mt-1 leading-tight">
                            Total Kegiatan
                        </div>
                    </div>

                    <!-- Anggota Terdaftar -->
                    <div class="bg-white/95 backdrop-blur-xs rounded-2xl p-2 sm:p-3.5 border border-white/90 shadow-md shadow-emerald-950/10 flex flex-col justify-between hover:border-sky-200 transition-colors">
                        <div class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center border border-sky-100/60">
                            <AcademicCapIcon class="w-4 h-4 stroke-[2.2]" />
                        </div>
                        <div class="text-base sm:text-xl font-black text-slate-800 mt-2 leading-none">
                            {{ stats.total_members ?? 0 }}
                        </div>
                        <div class="text-[9px] sm:text-[11px] font-semibold text-slate-500 mt-1 leading-tight">
                            Anggota Terdaftar
                        </div>
                    </div>

                    <!-- Sesi Bulan Ini -->
                    <div class="bg-white/95 backdrop-blur-xs rounded-2xl p-2 sm:p-3.5 border border-white/90 shadow-md shadow-emerald-950/10 flex flex-col justify-between hover:border-amber-200 transition-colors">
                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100/60">
                            <CalendarDaysIcon class="w-4 h-4 stroke-[2.2]" />
                        </div>
                        <div class="text-base sm:text-xl font-black text-slate-800 mt-2 leading-none">
                            {{ stats.total_sessions_month ?? 0 }}
                        </div>
                        <div class="text-[9px] sm:text-[11px] font-semibold text-slate-500 mt-1 leading-tight">
                            Sesi Bulan Ini
                        </div>
                    </div>

                    <!-- Pelatih & Pembina -->
                    <div class="bg-white/95 backdrop-blur-xs rounded-2xl p-2 sm:p-3.5 border border-white/90 shadow-md shadow-emerald-950/10 flex flex-col justify-between hover:border-purple-200 transition-colors">
                        <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100/60">
                            <UserIcon class="w-4 h-4 stroke-[2.2]" />
                        </div>
                        <div class="text-base sm:text-xl font-black text-slate-800 mt-2 leading-none">
                            {{ stats.total_instructors ?? 0 }}
                        </div>
                        <div class="text-[9px] sm:text-[11px] font-semibold text-slate-500 mt-1 leading-tight">
                            Pelatih &amp; Pembina
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================================================= -->
            <!-- SEARCH, FILTER SLIDER & ADD BUTTON (Exact Match Reference Image 1)                        -->
            <!-- ========================================================================================= -->
            <div class="space-y-2.5 pt-1">
                <div class="flex items-center gap-2 sm:gap-2.5">
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-0">
                        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama ekskul, pelatih, atau lokasi..."
                            class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-[#00796B] focus:border-[#00796B] shadow-xs"
                        />
                    </div>

                    <!-- Filter Adjustments Slider Button -->
                    <button
                        @click="selectedCategory = 'all'; selectedDay = 'all'"
                        type="button"
                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-slate-50 shrink-0 shadow-xs transition-colors"
                        title="Reset Filter"
                    >
                        <AdjustmentsHorizontalIcon class="w-5 h-5" />
                    </button>

                    <!-- + Tambah Ekskul Baru Button -->
                    <button
                        v-if="canManage"
                        @click="openCreateModal"
                        type="button"
                        class="inline-flex items-center justify-center gap-1.5 px-3 sm:px-4 py-2 rounded-xl bg-[#00796B] hover:bg-[#00695C] text-white font-bold text-xs shrink-0 shadow-xs transition-all active:scale-95 whitespace-nowrap"
                    >
                        <PlusIcon class="w-4 h-4 stroke-[3]" />
                        <span>Tambah Ekskul Baru</span>
                    </button>
                </div>

                <!-- Horizontal Category Pills -->
                <div class="flex items-center gap-2 overflow-x-auto scrollbar-none py-0.5 select-none">
                    <button
                        v-for="cat in categories"
                        :key="cat.key"
                        @click="selectedCategory = cat.key"
                        type="button"
                        class="px-3.5 py-1.5 rounded-full text-xs whitespace-nowrap transition-all duration-150 shrink-0"
                        :class="selectedCategory === cat.key
                            ? 'bg-[#00796B] text-white font-bold shadow-xs'
                            : 'bg-white text-slate-600 border border-slate-200 font-medium hover:bg-slate-50'"
                    >
                        {{ cat.label }}
                    </button>
                </div>

                <!-- Horizontal Day Pills -->
                <div class="flex items-center gap-2 overflow-x-auto scrollbar-none py-0.5 select-none">
                    <button
                        v-for="day in daysList"
                        :key="day"
                        @click="selectedDay = day === 'Semua Hari' ? 'all' : day"
                        type="button"
                        class="px-3 py-1.5 rounded-full text-xs whitespace-nowrap transition-all duration-150 shrink-0"
                        :class="(selectedDay === day || (selectedDay === 'all' && day === 'Semua Hari'))
                            ? 'bg-[#00796B] text-white font-bold shadow-xs'
                            : 'bg-white text-slate-600 border border-slate-200 font-medium hover:bg-slate-50'"
                    >
                        {{ day }}
                    </button>
                </div>
            </div>

            <!-- ========================================================================================= -->
            <!-- 2-COLUMN ACTIVITY CARDS GRID ON MOBILE (Exact Match Reference Image 1)                     -->
            <!-- ========================================================================================= -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 pt-1">
                <div
                    v-for="item in filteredActivities"
                    :key="item.id"
                    class="bg-white rounded-2xl sm:rounded-3xl border border-slate-100 shadow-xs hover:shadow-md transition-all duration-200 overflow-hidden flex flex-col justify-between group"
                >
                    <!-- Cover Image Area -->
                    <div class="h-28 sm:h-36 relative overflow-hidden bg-slate-100 rounded-t-2xl">
                        <img
                            :src="getCoverImage(item)"
                            :alt="item.name"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        />

                        <!-- Category Pill (Top-Left) -->
                        <div class="absolute top-2 left-2 z-10">
                            <span class="px-2 py-0.5 rounded-full text-[9px] sm:text-[10px] font-bold shadow-xs backdrop-blur-xs" :class="getCategoryPillBadge(item.category)">
                                {{ getCategoryLabel(item) }}
                            </span>
                        </div>

                        <!-- Favorite Heart (Top-Right) -->
                        <button
                            @click.stop="toggleFavorite(item.id)"
                            type="button"
                            class="absolute top-2 right-2 w-6 h-6 rounded-full bg-white/80 backdrop-blur-xs flex items-center justify-center text-slate-400 hover:text-rose-500 transition-colors shadow-xs z-10"
                            :title="favorites.has(item.id) ? 'Favorit' : 'Tambah Favorit'"
                        >
                            <HeartIconSolid v-if="favorites.has(item.id)" class="w-3.5 h-3.5 text-rose-500" />
                            <HeartIconOutline v-else class="w-3.5 h-3.5 text-slate-500" />
                        </button>
                    </div>

                    <!-- Card Body -->
                    <div class="p-2.5 sm:p-3.5 flex-1 flex flex-col justify-between space-y-2">
                        <div class="space-y-1.5">
                            <!-- Title & 3-Dots -->
                            <div class="flex items-start justify-between gap-1">
                                <h3 class="font-bold text-xs sm:text-sm text-slate-800 line-clamp-1 leading-snug">
                                    {{ item.name }}
                                </h3>
                                
                                <div class="relative card-action-menu-container shrink-0">
                                    <button
                                        @click.stop="toggleActionMenu(item.id)"
                                        type="button"
                                        class="p-0.5 rounded text-slate-400 hover:text-slate-600 transition-colors"
                                    >
                                        <EllipsisVerticalIcon class="w-4 h-4" />
                                    </button>

                                    <!-- Action Menu -->
                                    <div
                                        v-if="activeMenuId === item.id"
                                        class="absolute right-0 top-full mt-1 w-36 bg-white rounded-xl shadow-xl border border-slate-100 py-1 z-30 animate-in fade-in zoom-in-95"
                                    >
                                        <button
                                            @click="openEditModal(item)"
                                            type="button"
                                            class="w-full px-3 py-1.5 text-left text-[11px] font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-1.5"
                                        >
                                            <PencilSquareIcon class="w-3.5 h-3.5 text-teal-600" />
                                            <span>Edit Ekskul</span>
                                        </button>
                                        <button
                                            v-if="canManage"
                                            @click="confirmDelete(item)"
                                            type="button"
                                            class="w-full px-3 py-1.5 text-left text-[11px] font-semibold text-rose-600 hover:bg-rose-50 flex items-center gap-1.5"
                                        >
                                            <TrashIcon class="w-3.5 h-3.5 text-rose-500" />
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Unit Name -->
                            <div class="flex items-center gap-1 text-[9px] sm:text-[10px] font-bold text-[#00796B]">
                                <BuildingLibraryIcon class="w-3 h-3 text-[#00796B]" />
                                <span>{{ item.unit?.name || 'Unit Sekolah' }}</span>
                            </div>

                            <!-- Jadwal Latihan -->
                            <div class="flex items-center gap-1 text-[8px] sm:text-[9px] text-slate-500 truncate">
                                <CalendarDaysIcon class="w-3 h-3 text-slate-400 shrink-0" />
                                <span class="truncate">
                                    Setiap {{ item.day_of_week }}, {{ item.start_time }} - {{ item.end_time }} WIB
                                </span>
                            </div>

                            <!-- Lokasi Ruangan -->
                            <div class="flex items-center gap-1 text-[8px] sm:text-[9px] text-slate-500 truncate">
                                <MapPinIcon class="w-3 h-3 text-slate-400 shrink-0" />
                                <span class="truncate">
                                    {{ item.room?.name || item.location_name || 'Belum diatur' }}
                                </span>
                            </div>

                            <!-- Coach Row & Quota Status (No Dummy Data) -->
                            <div class="flex items-center justify-between pt-1 text-[9px] sm:text-[10px]">
                                <!-- Left: Coach -->
                                <div class="flex items-center gap-1.5 min-w-0 flex-1">
                                    <template v-if="item.instructors && item.instructors.length > 0">
                                        <div class="w-5 h-5 rounded-full bg-teal-100 text-teal-800 overflow-hidden shrink-0 flex items-center justify-center text-[9px] font-bold">
                                            {{ item.instructors[0].user?.name ? item.instructors[0].user.name.charAt(0) : 'P' }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-800 truncate text-[9px]">
                                                {{ item.instructors[0].user?.name }}
                                            </div>
                                            <div class="text-[8px] text-slate-400 truncate">
                                                {{ item.instructors[0].role_title || 'Pelatih' }}
                                            </div>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="w-5 h-5 rounded-full bg-slate-100 text-slate-400 overflow-hidden shrink-0 flex items-center justify-center text-[9px]">
                                            <UserIcon class="w-3 h-3 stroke-[2]" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-medium text-slate-400 truncate text-[9px] italic">
                                                Belum diatur
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Right: Quota -->
                                <div class="text-right shrink-0">
                                    <div class="font-bold text-slate-800 text-[9px]">
                                        {{ item.active_members_count || 0 }}/{{ item.max_quota }}
                                    </div>
                                    <div class="text-[8px] text-slate-400">
                                        Sisa {{ Math.max(0, item.max_quota - (item.active_members_count || 0)) }} Kursi
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden mt-1">
                                <div
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="item.bar_color || 'bg-[#00796B]'"
                                    :style="{ width: `${getQuotaPercentage(item)}%` }"
                                ></div>
                            </div>
                        </div>

                        <!-- CTA Button: Kelola Kegiatan → -->
                        <div class="pt-1.5">
                            <Link
                                :href="getLinkDestination(item)"
                                class="w-full py-1.5 sm:py-2 rounded-xl bg-[#00796B] hover:bg-[#00695C] text-white font-bold text-[10px] sm:text-xs flex items-center justify-center gap-1 shadow-xs active:scale-95 transition-all"
                            >
                                <span>Kelola Kegiatan</span>
                                <ArrowRightIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State (No Activities in Database or Filter) -->
                <div
                    v-if="filteredActivities.length === 0"
                    class="col-span-full py-12 px-4 text-center bg-white rounded-3xl border border-slate-100 shadow-xs flex flex-col items-center justify-center"
                >
                    <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center mb-3">
                        <UserGroupIcon class="w-6 h-6 stroke-[1.8]" />
                    </div>
                    <h3 class="font-bold text-slate-800 text-sm">Belum Ada Ekstrakurikuler</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm">
                        {{ searchQuery || selectedCategory !== 'all' || selectedDay !== 'all' ? 'Tidak ada kegiatan yang sesuai dengan filter pencarian.' : 'Belum ada kegiatan ekstrakurikuler yang ditambahkan.' }}
                    </p>
                    <button
                        v-if="canManage && !searchQuery && selectedCategory === 'all' && selectedDay === 'all'"
                        @click="openCreateModal"
                        type="button"
                        class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#00796B] hover:bg-[#00695C] text-white font-bold text-xs shadow-xs transition-all active:scale-95"
                    >
                        <PlusIcon class="w-4 h-4" />
                        <span>Tambah Kegiatan Sekarang</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================================================= -->
        <!-- MODAL TAMBAH & EDIT EKSTRAKURIKULER (4 Segmented Numbered Sections)                         -->
        <!-- ========================================================================================= -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
                <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-2xl overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-200">
                    
                    <!-- Modal Header -->
                    <div class="px-6 py-4 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between shrink-0">
                        <div>
                            <h3 class="font-black text-slate-800 text-base sm:text-lg">
                                {{ isEditing ? 'Edit Ekstrakurikuler' : 'Tambah Ekstrakurikuler' }}
                            </h3>
                            <p class="text-xs text-slate-500">Lengkapi data kegiatan di bawah ini.</p>
                        </div>
                        <button
                            @click="showModal = false"
                            class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                        >
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Modal Body (4 Segmented Numbered Sections) -->
                    <form @submit.prevent="submitForm" class="p-6 space-y-7 overflow-y-auto flex-1 text-slate-800">
                        
                        <!-- ================= SECTION 1: INFORMASI DASAR ================= -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                                <div class="w-7 h-7 rounded-full bg-[#00796B] text-white flex items-center justify-center text-xs font-black shrink-0">
                                    1
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm sm:text-base leading-tight">Informasi Dasar</h4>
                                    <p class="text-[11px] text-slate-500">Masukkan informasi umum kegiatan.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Kegiatan *</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        placeholder="Contoh: Futsal SD"
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 placeholder:text-slate-400"
                                    />
                                    <div v-if="form.errors.name" class="text-rose-500 text-xs mt-1">{{ form.errors.name }}</div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Kategori Ekskul *</label>
                                    <select
                                        v-model="form.category"
                                        required
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                                    >
                                        <option value="olahraga">Olahraga</option>
                                        <option value="seni_budaya">Seni & Budaya</option>
                                        <option value="keagamaan">Keagamaan</option>
                                        <option value="sains_teknologi">Sains & Teknologi</option>
                                        <option value="kepanduan">Bela Diri</option>
                                        <option value="lainnya">Lainnya</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-slate-700">Deskripsi Kegiatan</label>
                                    <span class="text-[11px] text-slate-400">{{ (form.description || '').length }}/500</span>
                                </div>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    maxlength="500"
                                    placeholder="Jelaskan tentang kegiatan ini, target kemampuan, atau silabus singkat..."
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 placeholder:text-slate-400"
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Foto Cover</label>
                                
                                <div
                                    @click="fileInput?.click()"
                                    class="border-2 border-dashed border-slate-200 hover:border-teal-400 bg-slate-50/60 hover:bg-teal-50/20 rounded-2xl p-4 sm:p-5 text-center cursor-pointer transition-all group"
                                >
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="handlePhotoSelect"
                                    />

                                    <div v-if="photoPreview" class="flex flex-col sm:flex-row items-center gap-4 text-left">
                                        <div class="w-24 h-24 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 shrink-0">
                                            <img :src="photoPreview" alt="Cover Preview" class="w-full h-full object-cover" />
                                        </div>
                                        <div class="space-y-1 min-w-0 flex-1">
                                            <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                                <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                                                <span>Foto berhasil dipilih</span>
                                            </div>
                                            <p v-if="photoSizeInfo" class="text-[11px] text-emerald-700 font-medium">
                                                {{ photoSizeInfo }}
                                            </p>
                                            <div class="flex items-center gap-3 pt-1">
                                                <span class="text-xs text-teal-700 font-semibold hover:underline">Klik untuk ganti foto</span>
                                                <button
                                                    @click.stop="removePhoto"
                                                    type="button"
                                                    class="text-xs text-rose-500 font-semibold hover:underline"
                                                >
                                                    Hapus
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-else class="flex flex-col items-center justify-center py-2 space-y-1.5">
                                        <div class="w-10 h-10 rounded-full bg-teal-50 text-teal-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                                            <PhotoIcon class="w-5 h-5" />
                                        </div>
                                        <div class="text-xs font-bold text-slate-700">
                                            Klik untuk unggah foto <span class="font-normal text-slate-500">atau seret ke sini</span>
                                        </div>
                                        <div class="text-[11px] text-slate-400">
                                            PNG, JPG maks. 5 MB (otomatis terkompresi)
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ================= SECTION 2: UNIT & TARGET PESERTA ================= -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                                <div class="w-7 h-7 rounded-full bg-[#00796B] text-white flex items-center justify-center text-xs font-black shrink-0">
                                    2
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm sm:text-base leading-tight">Unit & Target Peserta</h4>
                                    <p class="text-[11px] text-slate-500">Tentukan unit sekolah dan sasaran peserta yang dapat bergabung.</p>
                                </div>
                            </div>

                            <div v-if="props.units && props.units.length > 0">
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Unit Sekolah *</label>
                                <div class="relative">
                                    <BuildingLibraryIcon class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                                    <select
                                        v-model="form.unit_id"
                                        required
                                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white font-medium"
                                    >
                                        <option v-for="u in units" :key="u.id" :value="u.id">
                                            {{ u.name }} ({{ u.code || 'Unit' }})
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-slate-700">Target Tingkat / Jenjang Kelas *</label>
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="selectAllGradeLevels"
                                            type="button"
                                            class="text-[11px] text-[#00796B] font-semibold hover:underline"
                                        >
                                            Pilih Semua
                                        </button>
                                        <span class="text-slate-300 text-xs">|</span>
                                        <button
                                            @click="resetGradeLevels"
                                            type="button"
                                            class="text-[11px] text-slate-500 font-semibold hover:underline"
                                        >
                                            Reset
                                        </button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                                    <button
                                        v-for="g in availableGradeLevels"
                                        :key="g.value"
                                        @click="toggleGradeLevel(g.value)"
                                        type="button"
                                        class="px-3 py-2 rounded-xl text-xs font-bold border transition-all flex items-center justify-center gap-1.5"
                                        :class="form.target_levels.includes(g.value)
                                            ? 'bg-teal-50 text-teal-800 border-teal-600 shadow-xs ring-1 ring-teal-500'
                                            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                    >
                                        <CheckIcon v-if="form.target_levels.includes(g.value)" class="w-3.5 h-3.5 stroke-[3] text-[#00796B]" />
                                        <span>{{ g.label }}</span>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Batasan Gender</label>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                    <button
                                        v-for="opt in genderOptions"
                                        :key="opt.value"
                                        @click="form.gender_restriction = opt.value"
                                        type="button"
                                        class="p-3 rounded-2xl border text-left transition-all"
                                        :class="form.gender_restriction === opt.value
                                            ? 'border-teal-600 bg-teal-50/70 ring-1 ring-teal-500 shadow-xs'
                                            : 'border-slate-200 bg-white hover:border-slate-300'"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold" :class="form.gender_restriction === opt.value ? 'text-teal-900' : 'text-slate-800'">
                                                {{ opt.title }}
                                            </span>
                                            <span
                                                class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0"
                                                :class="form.gender_restriction === opt.value ? 'border-teal-600 bg-teal-600' : 'border-slate-300'"
                                            >
                                                <span v-if="form.gender_restriction === opt.value" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">{{ opt.subtitle }}</div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ================= SECTION 3: JADWAL & LOKASI ================= -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                                <div class="w-7 h-7 rounded-full bg-[#00796B] text-white flex items-center justify-center text-xs font-black shrink-0">
                                    3
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm sm:text-base leading-tight">Jadwal & Lokasi</h4>
                                    <p class="text-[11px] text-slate-500">Atur jadwal latihan dan lokasi pelaksanaan.</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Hari Latihan *</label>
                                <select
                                    v-model="form.day_of_week"
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                                >
                                    <option value="Senin">Senin</option>
                                    <option value="Selasa">Selasa</option>
                                    <option value="Rabu">Rabu</option>
                                    <option value="Kamis">Kamis</option>
                                    <option value="Jumat">Jumat</option>
                                    <option value="Sabtu">Sabtu</option>
                                    <option value="Minggu">Minggu</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Jam Mulai *</label>
                                    <div class="relative">
                                        <ClockIcon class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                                        <input
                                            v-model="form.start_time"
                                            type="time"
                                            required
                                            class="w-full pl-10 pr-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Jam Selesai *</label>
                                    <div class="relative">
                                        <ClockIcon class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                                        <input
                                            v-model="form.end_time"
                                            type="time"
                                            required
                                            class="w-full pl-10 pr-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Pilihan Tipe Lokasi Kegiatan -->
                            <div class="space-y-2.5 pt-1">
                                <label class="block text-xs font-bold text-slate-700">Tipe Tempat / Lokasi Kegiatan *</label>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    <!-- 1. Lapangan / Outdoor Sekolah -->
                                    <button
                                        type="button"
                                        @click="setLocationType('outdoor')"
                                        class="p-2.5 rounded-2xl border text-center transition-all flex flex-col items-center gap-1.5"
                                        :class="locationType === 'outdoor'
                                            ? 'border-teal-600 bg-teal-50/80 text-teal-900 font-bold ring-1 ring-teal-500 shadow-xs'
                                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 font-medium'"
                                    >
                                        <FlagIcon class="w-5 h-5 text-teal-700" />
                                        <span class="text-[11px] sm:text-xs">Lapangan / Outdoor</span>
                                    </button>

                                    <!-- 2. Ruang Kelas Unit -->
                                    <button
                                        type="button"
                                        @click="setLocationType('classroom')"
                                        class="p-2.5 rounded-2xl border text-center transition-all flex flex-col items-center gap-1.5"
                                        :class="locationType === 'classroom'
                                            ? 'border-teal-600 bg-teal-50/80 text-teal-900 font-bold ring-1 ring-teal-500 shadow-xs'
                                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 font-medium'"
                                    >
                                        <AcademicCapIcon class="w-5 h-5 text-teal-700" />
                                        <span class="text-[11px] sm:text-xs">Ruang Kelas</span>
                                    </button>

                                    <!-- 3. Ruangan Sarpras -->
                                    <button
                                        type="button"
                                        @click="setLocationType('sarpras')"
                                        class="p-2.5 rounded-2xl border text-center transition-all flex flex-col items-center gap-1.5"
                                        :class="locationType === 'sarpras'
                                            ? 'border-teal-600 bg-teal-50/80 text-teal-900 font-bold ring-1 ring-teal-500 shadow-xs'
                                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 font-medium'"
                                    >
                                        <BuildingOfficeIcon class="w-5 h-5 text-teal-700" />
                                        <span class="text-[11px] sm:text-xs">Sarpras / Lab / Aula</span>
                                    </button>

                                    <!-- 4. Luar Sekolah / Eksternal -->
                                    <button
                                        type="button"
                                        @click="setLocationType('external')"
                                        class="p-2.5 rounded-2xl border text-center transition-all flex flex-col items-center gap-1.5"
                                        :class="locationType === 'external'
                                            ? 'border-teal-600 bg-teal-50/80 text-teal-900 font-bold ring-1 ring-teal-500 shadow-xs'
                                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 font-medium'"
                                    >
                                        <MapPinIcon class="w-5 h-5 text-teal-700" />
                                        <span class="text-[11px] sm:text-xs">Luar Sekolah</span>
                                    </button>
                                </div>

                                <!-- Dynamic Location Details Card -->
                                <div class="bg-slate-50/90 p-3.5 rounded-2xl border border-slate-200/80 space-y-2.5">
                                    <!-- A. Lapangan / Outdoor Presets -->
                                    <div v-if="locationType === 'outdoor'" class="space-y-2">
                                        <label class="block text-xs font-bold text-slate-700">Pilih Area Lapangan / Outdoor Sekolah</label>
                                        <div class="flex flex-wrap gap-1.5">
                                            <button
                                                v-for="preset in outdoorPresets"
                                                :key="preset"
                                                type="button"
                                                @click="onSelectOutdoorPreset(preset)"
                                                class="px-2.5 py-1.5 rounded-xl text-xs transition-all border font-medium"
                                                :class="outdoorPreset === preset && form.location_name === preset
                                                    ? 'bg-teal-700 text-white border-teal-700 font-bold shadow-xs'
                                                    : 'bg-white text-slate-700 border-slate-200 hover:border-teal-300'"
                                            >
                                                {{ preset }}
                                            </button>
                                            <button
                                                type="button"
                                                @click="onSelectOutdoorPreset('custom')"
                                                class="px-2.5 py-1.5 rounded-xl text-xs transition-all border font-medium"
                                                :class="outdoorPreset === 'custom'
                                                    ? 'bg-teal-700 text-white border-teal-700 font-bold shadow-xs'
                                                    : 'bg-white text-slate-700 border-slate-200 hover:border-teal-300'"
                                            >
                                                + Area Lainnya
                                            </button>
                                        </div>
                                        <div v-if="outdoorPreset === 'custom'" class="pt-1">
                                            <input
                                                v-model="form.location_name"
                                                type="text"
                                                placeholder="Contoh: Lapangan Rumput Samping / Selasar Barat"
                                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white placeholder:text-slate-400"
                                            />
                                        </div>
                                    </div>

                                    <!-- B. Ruang Kelas Dropdown -->
                                    <div v-else-if="locationType === 'classroom'" class="space-y-1.5">
                                        <label class="block text-xs font-bold text-slate-700">Pilih Ruang Kelas Pembelajaran</label>
                                        <select
                                            :value="selectedClassroomId"
                                            @change="onSelectClassroom"
                                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                                        >
                                            <option value="">-- Pilih Ruang Kelas --</option>
                                            <option v-for="cls in filteredClassrooms" :key="cls.id" :value="cls.id">
                                                Ruang Kelas {{ cls.name }} (Jenjang {{ cls.level }})
                                            </option>
                                        </select>
                                    </div>

                                    <!-- C. Ruang Sarpras Dropdown -->
                                    <div v-else-if="locationType === 'sarpras'" class="space-y-1.5">
                                        <label class="block text-xs font-bold text-slate-700">Pilih Ruangan / Fasilitas Sarpras</label>
                                        <select
                                            :value="form.room_id"
                                            @change="onSelectRoom"
                                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                                        >
                                            <option value="">-- Pilih Ruangan / Sarpras --</option>
                                            <option v-for="room in filteredRooms" :key="room.id" :value="room.id">
                                                {{ room.name }} ({{ room.building ? (room.building + (room.floor ? ' Lt.' + room.floor : '')) : 'Kampus' }})
                                            </option>
                                        </select>
                                    </div>

                                    <!-- D. Luar Sekolah Input -->
                                    <div v-else-if="locationType === 'external'" class="space-y-1.5">
                                        <label class="block text-xs font-bold text-slate-700">Nama Lokasi di Luar Sekolah / Tempat Eksternal</label>
                                        <input
                                            v-model="form.location_name"
                                            type="text"
                                            required
                                            placeholder="Contoh: Kolam Renang Tirta Indah / GOR Kota Kraksaan / Stadion"
                                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white placeholder:text-slate-400"
                                        />
                                    </div>

                                    <!-- Summary Banner of Final Location -->
                                    <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-xs">
                                        <span class="text-slate-500 font-medium">Lokasi Tampil:</span>
                                        <span class="font-bold text-teal-800 bg-teal-50 px-2.5 py-0.5 rounded-lg border border-teal-200 inline-flex items-center gap-1">
                                            <MapPinIcon class="w-3.5 h-3.5 text-teal-600" />
                                            <span>{{ form.location_name || 'Belum ditentukan' }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ================= SECTION 4: KUOTA & STATUS ================= -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                                <div class="w-7 h-7 rounded-full bg-[#00796B] text-white flex items-center justify-center text-xs font-black shrink-0">
                                    4
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm sm:text-base leading-tight">Kuota & Status</h4>
                                    <p class="text-[11px] text-slate-500">Tentukan kapasitas peserta dan status kegiatan.</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kuota Maksimal Siswa *</label>
                                <div class="relative max-w-xs">
                                    <input
                                        v-model.number="form.max_quota"
                                        type="number"
                                        min="1"
                                        max="500"
                                        required
                                        class="w-full px-3.5 py-2.5 pr-14 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 font-semibold text-slate-800"
                                    />
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">
                                        siswa
                                    </span>
                                </div>
                            </div>

                            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 flex items-center justify-between">
                                <div class="space-y-0.5">
                                    <div class="text-xs font-bold text-slate-800">Status Kegiatan</div>
                                    <div class="text-[11px] text-slate-500">
                                        Kegiatan aktif dapat menerima pendaftaran siswa baru.
                                    </div>
                                </div>

                                <button
                                    @click="form.status = form.status === 'active' ? 'inactive' : 'active'"
                                    type="button"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden"
                                    :class="form.status === 'active' ? 'bg-[#00796B]' : 'bg-slate-300'"
                                >
                                    <span
                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                        :class="form.status === 'active' ? 'translate-x-5' : 'translate-x-0'"
                                    />
                                </button>
                            </div>

                            <div class="p-3.5 rounded-2xl border border-slate-200 bg-white flex items-center gap-3">
                                <input
                                    v-model="form.is_mandatory"
                                    type="checkbox"
                                    id="modal_mandatory_cb"
                                    class="w-4 h-4 text-[#00796B] rounded-sm border-slate-300 focus:ring-teal-500"
                                />
                                <label for="modal_mandatory_cb" class="text-xs font-semibold text-slate-700 cursor-pointer">
                                    Wajib Diikuti Semua Siswa (seperti Pramuka)
                                </label>
                            </div>

                            <div v-if="!isEditing && availableInstructors && availableInstructors.length > 0">
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Tugaskan Pelatih Awal (Opsional)</label>
                                <select
                                    v-model="form.instructor_ids"
                                    multiple
                                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white h-24"
                                >
                                    <option v-for="coach in availableInstructors" :key="coach.id" :value="coach.id">
                                        {{ coach.name }} ({{ coach.phone || 'No HP -' }})
                                    </option>
                                </select>
                                <p class="text-[11px] text-slate-400 mt-1">Tahan tombol Ctrl untuk memilih lebih dari 1.</p>
                            </div>
                        </div>

                        <!-- Modal Actions Footer -->
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3 sticky bottom-0 bg-white pb-2">
                            <button
                                @click="showModal = false"
                                type="button"
                                class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs sm:text-sm"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing || isCompressing"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#00796B] hover:bg-[#00695C] text-white font-bold text-xs sm:text-sm shadow-xs transition-all disabled:opacity-50"
                            >
                                <span v-if="form.processing">Menyimpan...</span>
                                <span v-else>{{ isEditing ? 'Simpan Perubahan' : 'Simpan Kegiatan' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
