<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    PlusIcon, CalendarDaysIcon, CalendarIcon, XMarkIcon, PencilSquareIcon, 
    BuildingOffice2Icon, ChevronDownIcon, ChevronLeftIcon, ChevronRightIcon,
    ClockIcon, TrashIcon
} from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    holidays: Array,
    units: Array,
    eventTypes: Object,
    filters: Object,
});

const isModalOpen = ref(false);
const editingHoliday = ref(null);

const form = useForm({
    date: new Date().toISOString().substr(0, 10),
    description: '',
    unit_id: '',
    is_recurring: false,
    event_type: 'libur',
    color: '',
    start_time: '',
    end_time: '',
});

// Calendar State
const currentYear = ref(props.filters?.year || new Date().getFullYear());
const currentMonth = ref(props.filters?.month || new Date().getMonth() + 1);

const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// Calendar Generation
const calendarDays = computed(() => {
    const year = currentYear.value;
    const month = currentMonth.value;
    const firstDay = new Date(year, month - 1, 1);
    const lastDay = new Date(year, month, 0);
    const daysInMonth = lastDay.getDate();
    const startDayOfWeek = firstDay.getDay(); // 0 = Sunday
    
    const days = [];
    
    // Previous month padding
    for (let i = 0; i < startDayOfWeek; i++) {
        days.push({ day: null, events: [] });
    }
    
    // Current month days
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const events = props.holidays.filter(h => h.date.startsWith(dateStr));
        days.push({ 
            day: d, 
            date: dateStr,
            events,
            isToday: dateStr === new Date().toISOString().split('T')[0],
            isWeekend: new Date(year, month - 1, d).getDay() === 0 || new Date(year, month - 1, d).getDay() === 6
        });
    }
    
    return days;
});

const changeMonth = (delta) => {
    let newMonth = currentMonth.value + delta;
    let newYear = currentYear.value;
    
    if (newMonth < 1) {
        newMonth = 12;
        newYear--;
    } else if (newMonth > 12) {
        newMonth = 1;
        newYear++;
    }
    
    router.get(route('yayasan.holidays.index'), {
        year: newYear,
        month: newMonth,
    }, { preserveState: true });
};

const openModal = (date = null) => {
    form.reset();
    editingHoliday.value = null;
    if (date) {
        form.date = date;
    }
    isModalOpen.value = true;
};

const editHoliday = (holiday) => {
    editingHoliday.value = holiday;
    form.date = holiday.date.split('T')[0];
    form.description = holiday.description;
    form.unit_id = holiday.unit_id || '';
    form.is_recurring = holiday.is_recurring;
    form.event_type = holiday.event_type || 'libur';
    form.color = holiday.color || '';
    form.start_time = holiday.start_time || '';
    form.end_time = holiday.end_time || '';
    isModalOpen.value = true;
};

const submit = () => {
    if (editingHoliday.value) {
        form.put(route('yayasan.holidays.update', editingHoliday.value.id), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
                editingHoliday.value = null;
            },
        });
    } else {
        form.post(route('yayasan.holidays.store'), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
};

const deleteHoliday = (id) => {
    if (confirm('Hapus event ini?')) {
        router.delete(route('yayasan.holidays.destroy', id));
    }
};

const getEventTypeColor = (type) => {
    return {
        'libur': 'bg-red-500',
        'ujian': 'bg-amber-500',
        'event': 'bg-blue-500',
        'rapat': 'bg-purple-500',
    }[type] || 'bg-gray-500';
};

const getEventTypeBadge = (type) => {
    return {
        'libur': 'bg-red-100 text-red-700 border-red-200',
        'ujian': 'bg-amber-100 text-amber-700 border-amber-200',
        'event': 'bg-blue-100 text-blue-700 border-blue-200',
        'rapat': 'bg-purple-100 text-purple-700 border-purple-200',
    }[type] || 'bg-gray-100 text-gray-700';
};
</script>

<template>
    <Head title="Kalender Akademik" />

    <AuthenticatedLayout>
        <div class="py-6 max-w-7xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight flex items-center gap-3">
                        <CalendarDaysIcon class="w-8 h-8 text-namira-teal" />
                        Kalender Akademik
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Kelola hari libur, ujian, event, dan rapat
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a :href="route('yayasan.holidays.export-ical', { year: currentYear })" 
                       class="px-4 py-2.5 bg-white text-gray-600 border border-gray-200 rounded-2xl font-bold hover:bg-gray-50 transition-all flex items-center gap-2"
                       title="Export ke iCal (.ics)">
                        <CalendarIcon class="h-5 w-5" />
                        <span class="hidden md:inline">Export iCal</span>
                    </a>
                    <button @click="openModal()" class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 transition-all flex items-center gap-2">
                        <PlusIcon class="h-5 w-5" />
                        Tambah Event
                    </button>
                </div>
            </div>

            <!-- Legend -->
            <div class="flex flex-wrap gap-3 bg-white rounded-2xl p-4 border shadow-sm">
                <span v-for="(label, key) in eventTypes" :key="key" class="flex items-center gap-2 text-sm">
                    <span class="w-3 h-3 rounded-full" :class="getEventTypeColor(key)"></span>
                    {{ label }}
                </span>
            </div>

            <!-- Calendar Navigation -->
            <div class="flex items-center justify-between bg-white rounded-2xl p-4 border shadow-sm">
                <button @click="changeMonth(-1)" class="p-2 hover:bg-gray-100 rounded-xl transition-colors">
                    <ChevronLeftIcon class="w-5 h-5" />
                </button>
                <h3 class="text-xl font-bold text-gray-800">
                    {{ monthNames[currentMonth - 1] }} {{ currentYear }}
                </h3>
                <button @click="changeMonth(1)" class="p-2 hover:bg-gray-100 rounded-xl transition-colors">
                    <ChevronRightIcon class="w-5 h-5" />
                </button>
            </div>

            <!-- Calendar Grid -->
            <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
                <!-- Day Headers -->
                <div class="grid grid-cols-7 border-b bg-gray-50">
                    <div v-for="day in ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']" :key="day" 
                         class="p-3 text-center text-xs font-bold uppercase text-gray-500"
                         :class="day === 'Min' || day === 'Sab' ? 'text-red-400' : ''">
                        {{ day }}
                    </div>
                </div>
                
                <!-- Calendar Days -->
                <div class="grid grid-cols-7">
                    <div v-for="(cell, idx) in calendarDays" :key="idx" 
                         class="min-h-[100px] border-r border-b p-2 cursor-pointer hover:bg-gray-50 transition-colors"
                         :class="{ 
                             'bg-gray-50/50': !cell.day,
                             'bg-red-50/30': cell.isWeekend && cell.day,
                             'ring-2 ring-inset ring-namira-teal': cell.isToday 
                         }"
                         @click="cell.day && openModal(cell.date)">
                        
                        <div v-if="cell.day" class="flex flex-col h-full">
                            <span class="text-sm font-bold mb-1" 
                                  :class="cell.isToday ? 'text-namira-teal' : cell.isWeekend ? 'text-red-400' : 'text-gray-600'">
                                {{ cell.day }}
                            </span>
                            
                            <!-- Events -->
                            <div class="flex-1 space-y-1 overflow-y-auto max-h-[60px]">
                                <div v-for="event in cell.events" :key="event.id" 
                                     class="text-[10px] px-1.5 py-0.5 rounded truncate cursor-pointer hover:opacity-80"
                                     :style="{ backgroundColor: event.display_color || event.color || '#6b7280', color: 'white' }"
                                     :title="event.description"
                                     @click.stop="editHoliday(event)">
                                    {{ event.description }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event List (Alternative View) -->
            <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
                <div class="p-4 border-b bg-gray-50 font-bold text-gray-700">
                    Daftar Event Bulan Ini ({{ holidays.length }})
                </div>
                <div v-if="holidays.length === 0" class="p-8 text-center text-gray-400">
                    Tidak ada event di bulan ini
                </div>
                <div v-else class="divide-y">
                    <div v-for="h in holidays" :key="h.id" class="p-4 flex items-center justify-between hover:bg-gray-50">
                        <div class="flex items-center gap-4">
                            <div class="w-2 h-10 rounded-full" :class="getEventTypeColor(h.event_type)"></div>
                            <div>
                                <div class="font-bold text-gray-800">{{ h.description }}</div>
                                <div class="text-xs text-gray-500 flex items-center gap-3">
                                    <span>{{ new Date(h.date).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' }) }}</span>
                                    <span v-if="h.start_time" class="flex items-center gap-1">
                                        <ClockIcon class="w-3 h-3" />
                                        {{ h.start_time }} - {{ h.end_time || '?' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 rounded-full text-xs font-bold border" :class="getEventTypeBadge(h.event_type)">
                                {{ eventTypes[h.event_type] }}
                            </span>
                            <button @click="editHoliday(h)" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg">
                                <PencilSquareIcon class="w-4 h-4" />
                            </button>
                            <button @click="deleteHoliday(h.id)" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg">
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Modal :show="isModalOpen" @close="isModalOpen = false" max-width="lg">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-teal-50 rounded-xl">
                        <CalendarIcon class="h-6 w-6 text-namira-teal" />
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">
                        {{ editingHoliday ? 'Edit Event' : 'Tambah Event' }}
                    </h3>
                </div>
                
                <div class="space-y-4">
                    <!-- Event Type -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Jenis Event</label>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="(label, key) in eventTypes" :key="key" type="button"
                                    @click="form.event_type = key"
                                    class="px-4 py-2 rounded-xl text-sm font-bold border transition-all"
                                    :class="form.event_type === key ? getEventTypeBadge(key) + ' ring-2 ring-offset-1' : 'bg-gray-50 text-gray-600 border-gray-200'">
                                {{ label }}
                            </button>
                        </div>
                    </div>

                    <!-- Date -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Tanggal</label>
                        <input type="date" v-model="form.date" class="w-full h-12 px-4 border-gray-200 rounded-xl text-sm">
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Keterangan</label>
                        <input type="text" v-model="form.description" placeholder="Contoh: UTS Semester Ganjil" 
                               class="w-full h-12 px-4 border-gray-200 rounded-xl text-sm">
                    </div>

                    <!-- Time (Optional) -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Jam Mulai (Opsional)</label>
                            <input type="time" v-model="form.start_time" class="w-full h-12 px-4 border-gray-200 rounded-xl text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Jam Selesai</label>
                            <input type="time" v-model="form.end_time" class="w-full h-12 px-4 border-gray-200 rounded-xl text-sm">
                        </div>
                    </div>

                    <!-- Unit -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Unit (Lingkup)</label>
                        <select v-model="form.unit_id" class="w-full h-12 px-4 border-gray-200 rounded-xl text-sm">
                            <option value="">GLOBAL (Semua Unit)</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>

                    <!-- Recurring -->
                    <label class="flex items-center gap-3 p-4 bg-indigo-50/50 rounded-xl border border-indigo-100 cursor-pointer">
                        <input type="checkbox" v-model="form.is_recurring" class="h-5 w-5 rounded text-namira-teal">
                        <div>
                            <span class="font-bold text-gray-700">Berulang Tiap Tahun</span>
                            <span class="block text-xs text-gray-500">Contoh: 17 Agustus, Hari Natal</span>
                        </div>
                    </label>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button @click="isModalOpen = false" class="px-6 py-2.5 bg-gray-100 text-gray-600 rounded-xl font-bold hover:bg-gray-200">
                        Batal
                    </button>
                    <button @click="submit" :disabled="form.processing" class="px-6 py-2.5 bg-namira-teal text-white rounded-xl font-bold hover:bg-teal-600">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
