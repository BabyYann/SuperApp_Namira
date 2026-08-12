<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import debounce from 'lodash/debounce';
import Pagination from '@/Components/Pagination.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { 
    MagnifyingGlassIcon, PlusIcon, MapPinIcon, PencilSquareIcon, TrashIcon, MapIcon, BuildingOfficeIcon, XMarkIcon, CheckIcon
} from '@heroicons/vue/24/outline';

// --- PROPS ---
const props = defineProps({
    locations: Object,
    units: Array,
    filters: Object,
});

// Fix Leaflet Default Icon issue in Vite
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

// Custom SVG Pin Icon generator
const createCustomPinIcon = (color = '#0d9488') => {
    return L.divIcon({
        className: 'custom-leaflet-pin',
        html: `
            <div style="position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <div style="background-color: ${color}; width: 32px; height: 32px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.35); border: 2.5px solid #ffffff;">
                    <div style="width: 8px; height: 8px; background-color: #ffffff; border-radius: 50%;"></div>
                </div>
            </div>
        `,
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -32],
    });
};

// --- STATE MANAGEMENT ---
const mainMapContainer = ref(null);
const mainMap = ref(null);
const mainMarkers = ref([]);
const mainCircles = ref([]);

// Modal Map State
const showFormModal = ref(false);
const isEditing = ref(false);
const modalMapContainer = ref(null);
const modalMap = ref(null);
const modalMarker = ref(null);
const modalCircle = ref(null);

// --- FILTERS ---
const search = ref(props.filters?.search || '');
const unitFilter = ref(props.filters?.unit_id || '');

watch([search, unitFilter], debounce(() => {
    router.get(route('yayasan.attendance-locations.index'), {
        search: search.value,
        unit_id: unitFilter.value
    }, { preserveState: true, preserveScroll: true, replace: true });
}, 300));

// --- FORM ---
const form = useForm({
    id: null,
    unit_id: '',
    name: '',
    latitude: -7.754, // Default Probolinggo
    longitude: 113.216,
    radius: 100,
    is_active: true,
});

// --- MAIN MAP LOGIC ---
const initMainMap = () => {
    if (!mainMapContainer.value) return;

    const defaultCenter = [-7.754, 113.216];
    const initialCenter = props.locations.data.length > 0 
        ? [parseFloat(props.locations.data[0].latitude), parseFloat(props.locations.data[0].longitude)] 
        : defaultCenter;

    mainMap.value = L.map(mainMapContainer.value, {
        zoomControl: false // Custom position to avoid search bar overlap
    }).setView(initialCenter, 16);

    // Add Zoom Control at bottom-left
    L.control.zoom({ position: 'bottomleft' }).addTo(mainMap.value);

    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    });

    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri'
    });

    osmLayer.addTo(mainMap.value);

    const baseMaps = {
        "Peta Jalan": osmLayer,
        "Satelit (Gedung Asli)": satelliteLayer
    };

    L.control.layers(baseMaps, null, { position: 'topright' }).addTo(mainMap.value);

    renderMainLocations();
};

const renderMainLocations = () => {
    if (!mainMap.value) return;

    mainMarkers.value.forEach(m => mainMap.value.removeLayer(m));
    mainCircles.value.forEach(c => mainMap.value.removeLayer(c));
    mainMarkers.value = [];
    mainCircles.value = [];

    props.locations.data.forEach(loc => {
        const color = loc.is_active ? '#0d9488' : '#64748b';
        const lat = parseFloat(loc.latitude);
        const lng = parseFloat(loc.longitude);
        
        const pinIcon = createCustomPinIcon(color);

        const marker = L.marker([lat, lng], { 
            icon: pinIcon,
            opacity: loc.is_active ? 1 : 0.7 
        }).addTo(mainMap.value);

        const circle = L.circle([lat, lng], {
            color: color,
            fillColor: color,
            fillOpacity: 0.18,
            weight: 2,
            radius: parseInt(loc.radius)
        }).addTo(mainMap.value);

        marker.on('click', (e) => {
            L.DomEvent.stopPropagation(e);
            openEditModal(loc);
        });

        marker.bindTooltip(`<b>${loc.name}</b><br><span style="font-size:10px">${loc.unit?.name || ''} (${loc.radius}m)</span>`, { permanent: false, direction: 'top' });
        
        mainMarkers.value.push(marker);
        mainCircles.value.push(circle);
    });
};

const focusLocation = (loc) => {
    if (!mainMap.value) return;
    const lat = parseFloat(loc.latitude);
    const lng = parseFloat(loc.longitude);
    mainMap.value.flyTo([lat, lng], 18, { duration: 1.2 });
};

// --- MODAL FORM & MINI MAP LOGIC ---
const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.unit_id = props.units[0]?.id || '';
    form.is_active = true;

    // Use current main map center or default
    if (mainMap.value) {
        const center = mainMap.value.getCenter();
        form.latitude = center.lat;
        form.longitude = center.lng;
    }

    showFormModal.value = true;
    nextTick(() => {
        initModalMap();
    });
};

const openEditModal = (loc) => {
    isEditing.value = true;
    form.id = loc.id;
    form.unit_id = loc.unit_id;
    form.name = loc.name;
    form.latitude = parseFloat(loc.latitude);
    form.longitude = parseFloat(loc.longitude);
    form.radius = parseInt(loc.radius);
    form.is_active = Boolean(loc.is_active);

    showFormModal.value = true;
    nextTick(() => {
        initModalMap();
    });
};

const closeModal = () => {
    showFormModal.value = false;
    if (modalMap.value) {
        modalMap.value.remove();
        modalMap.value = null;
    }
};

const initModalMap = () => {
    if (!modalMapContainer.value) return;
    
    if (modalMap.value) {
        modalMap.value.remove();
        modalMap.value = null;
    }

    const coords = [form.latitude, form.longitude];
    modalMap.value = L.map(modalMapContainer.value).setView(coords, 17);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(modalMap.value);

    updateModalMapElements();

    modalMap.value.on('click', (e) => {
        form.latitude = e.latlng.lat;
        form.longitude = e.latlng.lng;
        updateModalMapElements();
    });
};

const updateModalMapElements = () => {
    if (!modalMap.value) return;

    if (modalMarker.value) modalMap.value.removeLayer(modalMarker.value);
    if (modalCircle.value) modalMap.value.removeLayer(modalCircle.value);

    const coords = [form.latitude, form.longitude];
    const color = form.is_active ? '#f59e0b' : '#64748b'; // Orange pin for modal picker
    const pinIcon = createCustomPinIcon(color);

    modalMarker.value = L.marker(coords, { icon: pinIcon, draggable: true }).addTo(modalMap.value);
    modalCircle.value = L.circle(coords, {
        color: color,
        fillColor: color,
        fillOpacity: 0.2,
        weight: 2,
        dashArray: '4, 4',
        radius: parseInt(form.radius || 100)
    }).addTo(modalMap.value);

    modalMarker.value.on('dragend', (e) => {
        const latlng = e.target.getLatLng();
        form.latitude = latlng.lat;
        form.longitude = latlng.lng;
        updateModalMapElements();
    });

    modalMap.value.panTo(coords);
};

// --- GEOLOCATION IN MODAL ---
const useCurrentLocationInModal = () => {
    if (!navigator.geolocation) {
        alert('Browser tidak mendukung Geolocation.');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            form.latitude = pos.coords.latitude;
            form.longitude = pos.coords.longitude;
            updateModalMapElements();
        },
        (err) => {
            alert('Gagal mengambil lokasi GPS saat ini.');
        },
        { enableHighAccuracy: true }
    );
};

// --- MAIN SEARCH LOCATION ---
const mapSearchQuery = ref('');
const searchLocation = async () => {
    if (!mapSearchQuery.value) return;
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(mapSearchQuery.value)}`);
        const data = await res.json();
        if (data && data.length > 0) {
            const { lat, lon } = data[0];
            const latitude = parseFloat(lat);
            const longitude = parseFloat(lon);
            mainMap.value.flyTo([latitude, longitude], 17);
        } else {
            alert('Lokasi tidak ditemukan.');
        }
    } catch (e) {
        alert('Gagal mencari lokasi.');
    }
};

// --- SUBMIT & DELETE ---
const submit = () => {
    const options = {
        onSuccess: () => {
            closeModal();
            renderMainLocations();
        }
    };

    if (isEditing.value) {
        form.put(route('yayasan.attendance-locations.update', form.id), options);
    } else {
        form.post(route('yayasan.attendance-locations.store'), options);
    }
};

const deleteLocation = (id) => {
    if (confirm('Hapus lokasi presensi ini?')) {
        router.delete(route('yayasan.attendance-locations.destroy', id), {
            onSuccess: () => renderMainLocations()
        });
    }
};

// --- LIFECYCLE ---
onMounted(() => {
    initMainMap();
});

onUnmounted(() => {
    if (mainMap.value) {
        mainMap.value.remove();
        mainMap.value = null;
    }
});
</script>

<template>
    <Head title="Lokasi Absensi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                    Lokasi Absensi (Geofencing)
                </h2>
                <p class="text-xs text-gray-500">Kelola titik koordinat GPS dan radius presensi sekolah terpusat.</p>
            </div>
        </template>

        <div class="py-4 max-w-[1600px] mx-auto px-2 md:px-4 flex flex-col lg:flex-row gap-5 h-[calc(100vh-150px)] min-h-[650px]">
            
            <!-- SIDEBAR: DAFTAR TITIK ABSENSI (Fixed width 380px, Never Cramped) -->
            <div class="w-full lg:w-[380px] xl:w-[400px] bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden flex flex-col transition-all flex-shrink-0">
                <div class="p-4 md:p-5 border-b border-slate-100 space-y-3.5 bg-slate-50/50">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-base text-slate-800">Daftar Titik Absensi</h3>
                            <p class="text-[11px] text-slate-500">Klik item untuk fokus ke lokasi peta</p>
                        </div>
                        <span class="text-xs font-black bg-teal-100 text-teal-700 px-2.5 py-1 rounded-xl border border-teal-200 shadow-sm">{{ locations.total }} Titik</span>
                    </div>
                    
                    <!-- Search & Filter Toolbar -->
                    <div class="space-y-2.5">
                        <div class="relative w-full">
                            <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-3 text-slate-400" />
                            <input 
                                v-model="search" 
                                type="text" 
                                placeholder="Cari nama lokasi..." 
                                class="pl-9 pr-3 py-2 w-full bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm"
                            >
                        </div>
                        
                        <div class="flex gap-2">
                            <select v-model="unitFilter" class="w-full py-2 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:border-namira-teal focus:ring focus:ring-namira-teal/20 shadow-sm cursor-pointer">
                                <option value="">Semua Unit Sekolah</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                            </select>
                        </div>

                        <!-- Open Modal Button -->
                        <button 
                            @click="openCreateModal" 
                            class="w-full px-4 py-2.5 bg-namira-teal text-white rounded-xl font-bold text-xs shadow-md hover:bg-teal-600 transition-all active:scale-95 flex items-center justify-center gap-2"
                        >
                            <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                            <span>Tambah Lokasi Baru</span>
                        </button>
                    </div>
                </div>
                
                <!-- Location Cards List -->
                <div class="flex-1 overflow-y-auto p-3 md:p-4 space-y-2.5">
                    <div v-for="loc in locations.data" :key="loc.id" 
                        class="p-3.5 rounded-2xl border cursor-pointer bg-white group relative transition-all hover:shadow-md hover:border-namira-teal"
                        :class="loc.is_active ? 'border-slate-200' : 'border-slate-200 bg-slate-50/60 opacity-75'"
                        @click="focusLocation(loc)"
                    >
                        <div class="flex justify-between items-start">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-slate-900 text-sm leading-tight">{{ loc.name }}</h4>
                                    <span v-if="!loc.is_active" class="px-1.5 py-0.5 bg-slate-200 text-slate-600 text-[9px] font-extrabold rounded uppercase">Non-Aktif</span>
                                </div>
                                
                                <div class="flex items-center gap-2 text-xs text-slate-500">
                                    <span class="inline-flex items-center gap-1 font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md text-[11px]">
                                        <BuildingOfficeIcon class="w-3 h-3 text-teal-600" />
                                        {{ loc.unit?.name || 'Unit' }}
                                    </span>
                                    <span class="text-[11px] font-bold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-md border border-teal-100">
                                        Radius: {{ loc.radius }}m
                                    </span>
                                </div>

                                <div class="text-[10px] font-mono text-slate-400 pt-0.5">
                                    GPS: {{ parseFloat(loc.latitude).toFixed(5) }}, {{ parseFloat(loc.longitude).toFixed(5) }}
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-1 bg-white p-1 rounded-xl shadow-sm border border-slate-200">
                                <button @click.stop="openEditModal(loc)" class="text-blue-600 hover:bg-blue-50 p-1.5 rounded-lg transition-colors" title="Edit Lokasi">
                                    <PencilSquareIcon class="h-4 w-4" />
                                </button>
                                <button @click.stop="deleteLocation(loc.id)" class="text-rose-600 hover:bg-rose-50 p-1.5 rounded-lg transition-colors" title="Hapus Lokasi">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="locations.data.length === 0" class="text-center py-12 text-slate-400 text-xs font-semibold">
                        <MapIcon class="w-10 h-10 mx-auto mb-2 opacity-40" />
                        Belum ada titik lokasi absensi.
                    </div>
                </div>

                <div class="p-3 border-t border-slate-100 bg-slate-50/50">
                    <Pagination :links="locations.links" />
                </div>
            </div>

            <!-- MAIN FULL MAP AREA -->
            <div class="w-full flex-1 bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden relative group">
                <div ref="mainMapContainer" class="w-full h-full z-0 cursor-crosshair"></div>
                
                <!-- Address Search Overlay Bar (Positioned Top-Left with Max Width) -->
                <div class="absolute top-3 left-3 w-[calc(100%-120px)] sm:w-80 md:w-96 z-[400] bg-white/95 backdrop-blur-md rounded-2xl shadow-xl border border-slate-200/80 p-1 flex gap-1.5">
                    <input 
                        v-model="mapSearchQuery" 
                        @keyup.enter="searchLocation"
                        type="text" 
                        placeholder="Cari alamat di peta..." 
                        class="w-full border-none text-xs font-semibold focus:ring-0 rounded-xl pl-3 bg-transparent"
                    >
                    <button @click="searchLocation" class="bg-namira-teal text-white px-3 py-1.5 rounded-xl hover:bg-teal-700 transition-colors flex items-center gap-1 text-xs font-bold shrink-0">
                        <MagnifyingGlassIcon class="h-3.5 w-3.5" />
                        <span>Cari</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 🪟 POP-UP MODAL FORM (OPSI 4: Dedicated Pop-up with Mini Map Picker) -->
        <Teleport to="body">
            <div v-if="showFormModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-6 border border-slate-200">
                        
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center font-bold">
                                    <MapPinIcon class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-extrabold text-slate-900">{{ isEditing ? 'Edit Lokasi Presensi' : 'Tambah Lokasi Presensi Baru' }}</h3>
                                    <p class="text-xs text-slate-500">Atur unit, radius geofence, dan geser pin lokasi di peta</p>
                                </div>
                            </div>
                            <button @click="closeModal" class="text-slate-400 hover:text-slate-600 p-1 rounded-xl"><XMarkIcon class="w-6 h-6" /></button>
                        </div>

                        <!-- Modal Form Content Grid (2 Columns) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 py-4">
                            
                            <!-- Left Column: Form Fields -->
                            <div class="space-y-4">
                                <div>
                                    <InputLabel value="Unit Sekolah *" class="text-xs font-extrabold text-slate-700" />
                                    <select v-model="form.unit_id" class="w-full mt-1 rounded-xl border-slate-200 text-xs font-semibold py-2.5 focus:ring-teal-500 focus:border-teal-500">
                                        <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                                    </select>
                                    <div v-if="form.errors.unit_id" class="text-rose-500 text-xs mt-1 font-bold">{{ form.errors.unit_id }}</div>
                                </div>

                                <div>
                                    <InputLabel value="Nama Lokasi *" class="text-xs font-extrabold text-slate-700" />
                                    <input v-model="form.name" type="text" placeholder="Contoh: Gedung Utama SD Namira" class="w-full mt-1 rounded-xl border-slate-200 text-xs font-semibold py-2.5 focus:ring-teal-500 focus:border-teal-500">
                                    <div v-if="form.errors.name" class="text-rose-500 text-xs mt-1 font-bold">{{ form.errors.name }}</div>
                                </div>

                                <div>
                                    <div class="flex justify-between items-center">
                                        <InputLabel value="Radius Geofence *" class="text-xs font-extrabold text-slate-700" />
                                        <span class="text-xs font-bold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-md border border-teal-100">{{ form.radius }} Meter</span>
                                    </div>
                                    <input 
                                        v-model="form.radius" 
                                        @input="updateModalMapElements" 
                                        type="range" 
                                        min="10" 
                                        max="500" 
                                        step="5"
                                        class="w-full mt-2 accent-teal-600 cursor-pointer"
                                    >
                                    <div class="flex justify-between text-[10px] text-slate-400 font-bold">
                                        <span>10m (Ketat)</span>
                                        <span>250m</span>
                                        <span>500m (Luas)</span>
                                    </div>
                                    <div v-if="form.errors.radius" class="text-rose-500 text-xs mt-1 font-bold">{{ form.errors.radius }}</div>
                                </div>

                                <!-- GPS Coordinates Box -->
                                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 text-xs space-y-1">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Koordinat Terpilih</span>
                                    <div class="font-mono text-slate-800 font-extrabold">
                                        Lat: {{ typeof form.latitude === 'number' ? form.latitude.toFixed(6) : '-' }}, 
                                        Lng: {{ typeof form.longitude === 'number' ? form.longitude.toFixed(6) : '-' }}
                                    </div>
                                </div>

                                <!-- Active Status Toggle -->
                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-200">
                                    <div>
                                        <span class="text-xs font-bold text-slate-800 block">Status Aktif</span>
                                        <span class="text-[10px] text-slate-400">Aktifkan untuk lokasi presensi</span>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="form.is_active" @change="updateModalMapElements" class="sr-only peer">
                                        <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-namira-teal"></div>
                                    </label>
                                </div>
                            </div>

                            <!-- Right Column: Interactive Mini-Map Picker -->
                            <div class="flex flex-col space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-700">Pilih Titik di Peta (Klik / Geser Pin)</span>
                                    <button 
                                        type="button"
                                        @click="useCurrentLocationInModal"
                                        class="text-[11px] font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 border border-teal-200 px-2 py-1 rounded-lg transition-colors flex items-center gap-1"
                                    >
                                        <MapPinIcon class="w-3.5 h-3.5" />
                                        <span>GPS Saya</span>
                                    </button>
                                </div>

                                <div class="flex-1 min-h-[220px] rounded-2xl border border-slate-200 overflow-hidden relative shadow-inner">
                                    <div ref="modalMapContainer" class="w-full h-full min-h-[220px]"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                            <button type="button" @click="closeModal" class="px-4 py-2 text-slate-600 font-bold hover:bg-slate-100 rounded-xl text-xs transition-colors">Batal</button>
                            <button type="button" @click="submit" :disabled="form.processing" class="px-6 py-2.5 bg-namira-teal hover:bg-teal-600 text-white font-extrabold rounded-xl shadow-lg shadow-teal-500/20 text-xs transition-all active:scale-95 flex items-center gap-1.5">
                                <CheckIcon class="w-4 h-4 stroke-[2.5]" />
                                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Lokasi Presensi' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>

<style>
.custom-leaflet-pin {
    background: none !important;
    border: none !important;
}
</style>
