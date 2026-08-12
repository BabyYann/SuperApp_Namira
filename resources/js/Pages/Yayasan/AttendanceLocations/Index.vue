<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import debounce from 'lodash/debounce';
import Pagination from '@/Components/Pagination.vue';
import { 
    MagnifyingGlassIcon, PlusIcon, MapPinIcon, PencilSquareIcon, TrashIcon, MapIcon, BuildingOfficeIcon, XMarkIcon 
} from '@heroicons/vue/24/outline';

// --- PROPS ---
const props = defineProps({
    locations: Object,
    units: Array,
    filters: Object,
});

// Fix Leaflet Default Icon issue in Vite (Fallback)
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

// Custom SVG Pin Icon generator (Crisp, no broken images)
const createCustomPinIcon = (color = '#0d9488') => {
    return L.divIcon({
        className: 'custom-leaflet-pin',
        html: `
            <div style="position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <div style="background-color: ${color}; width: 30px; height: 30px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.35); border: 2.5px solid #ffffff;">
                    <div style="width: 8px; height: 8px; background-color: #ffffff; border-radius: 50%;"></div>
                </div>
            </div>
        `,
        iconSize: [30, 30],
        iconAnchor: [15, 30],
        popupAnchor: [0, -30],
    });
};

// --- STATE MANAGEMENT ---
const VIEW_STATE = {
    LIST: 'LIST',
    CREATE: 'CREATE',
    EDIT: 'EDIT'
};

const viewState = ref(VIEW_STATE.LIST);
const mapContainer = ref(null);
const map = ref(null);
const markers = ref([]);
const circles = ref([]);
const userMarker = ref(null);
const previewLayer = ref(null);

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

// --- MAP LOGIC ---
const initMap = () => {
    if (!mapContainer.value) return;

    const defaultCenter = [-7.754, 113.216];
    const initialCenter = props.locations.data.length > 0 
        ? [parseFloat(props.locations.data[0].latitude), parseFloat(props.locations.data[0].longitude)] 
        : defaultCenter;

    map.value = L.map(mapContainer.value).setView(initialCenter, 16);

    // Layers
    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    });

    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri'
    });

    osmLayer.addTo(map.value);

    const baseMaps = {
        "Peta Jalan": osmLayer,
        "Satelit (Gedung Asli)": satelliteLayer
    };

    L.control.layers(baseMaps).addTo(map.value);

    // GLOBAL CLICK LISTENER
    map.value.on('click', (e) => {
        handleMapClick(e.latlng.lat, e.latlng.lng);
    });

    renderLocations();
};

const renderLocations = () => {
    // Clear existing layers
    markers.value.forEach(m => map.value.removeLayer(m));
    circles.value.forEach(c => map.value.removeLayer(c));
    markers.value = [];
    circles.value = [];

    props.locations.data.forEach(loc => {
        const color = loc.is_active ? '#0d9488' : '#64748b'; // Teal or Slate
        const lat = parseFloat(loc.latitude);
        const lng = parseFloat(loc.longitude);
        
        const pinIcon = createCustomPinIcon(color);

        const marker = L.marker([lat, lng], { 
            icon: pinIcon,
            opacity: loc.is_active ? 1 : 0.7 
        }).addTo(map.value);

        const circle = L.circle([lat, lng], {
            color: color,
            fillColor: color,
            fillOpacity: 0.18,
            weight: 2,
            radius: parseInt(loc.radius)
        }).addTo(map.value);

        marker.on('click', (e) => {
            L.DomEvent.stopPropagation(e);
            startEdit(loc);
        });

        marker.bindTooltip(`<b>${loc.name}</b><br><span style="font-size:10px">${loc.unit?.name || ''} (${loc.radius}m)</span>`, { permanent: false, direction: 'top' });
        
        markers.value.push(marker);
        circles.value.push(circle);
    });
};

const handleMapClick = (lat, lng) => {
    if (viewState.value === VIEW_STATE.LIST) {
        startCreate(false);
    }
    
    form.latitude = lat;
    form.longitude = lng;
    updatePreview();
};

const updatePreview = () => {
    if (previewLayer.value) {
        map.value.removeLayer(previewLayer.value);
    }

    const color = form.is_active ? '#f59e0b' : '#64748b'; // Orange preview pin
    const previewIcon = createCustomPinIcon(color);

    previewLayer.value = L.layerGroup([
        L.marker([form.latitude, form.longitude], { icon: previewIcon }),
        L.circle([form.latitude, form.longitude], {
            color: color,
            fillColor: color,
            fillOpacity: 0.25,
            weight: 2,
            dashArray: '4, 4',
            radius: parseInt(form.radius || 100)
        })
    ]).addTo(map.value);
};

// --- GEOLOCATION ---
const getCurrentLocation = () => {
    if (!navigator.geolocation) {
        alert('Browser tidak mendukung Geolocation.');
        return;
    }

    const handleSuccess = (position) => {
        const { latitude, longitude, accuracy } = position.coords;
        
        if (userMarker.value) map.value.removeLayer(userMarker.value);
        
        userMarker.value = L.circleMarker([latitude, longitude], {
            radius: 9,
            fillColor: '#2563eb',
            color: '#ffffff',
            weight: 3,
            opacity: 1,
            fillOpacity: 0.9
        }).addTo(map.value).bindPopup(`<b>Lokasi Saya</b><br>Akurasi GPS: ${Math.round(accuracy)}m`).openPopup();

        map.value.setView([latitude, longitude], 18);

        if (viewState.value !== VIEW_STATE.LIST) {
            form.latitude = latitude;
            form.longitude = longitude;
            updatePreview();
        }
    };

    const handleError = (error) => {
        console.warn('GPS High Accuracy Failed, trying fallback...', error);
        navigator.geolocation.getCurrentPosition(
            handleSuccess,
            (err) => {
                console.error('Geolocation Failed:', err);
                alert('Gagal mendeteksi lokasi. Silakan gunakan pencarian alamat atau klik langsung di peta.');
            },
            { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 }
        );
    };

    navigator.geolocation.getCurrentPosition(handleSuccess, handleError, {
        enableHighAccuracy: true,
        timeout: 5000,
        maximumAge: 0
    });
};

// --- SEARCH LOCATION (NOMINATIM) ---
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
            
            map.value.setView([latitude, longitude], 17);
            
            if (viewState.value !== VIEW_STATE.LIST) {
                form.latitude = latitude;
                form.longitude = longitude;
                updatePreview();
            }
        } else {
            alert('Lokasi tidak ditemukan.');
        }
    } catch (e) {
        alert('Gagal mencari lokasi.');
    }
};

// --- ACTIONS ---
const startCreate = (resetCoords = true) => {
    viewState.value = VIEW_STATE.CREATE;
    form.reset();
    form.unit_id = props.units[0]?.id || '';
    form.is_active = true;

    if (resetCoords && map.value) {
        const center = map.value.getCenter();
        form.latitude = center.lat;
        form.longitude = center.lng;
    }
    updatePreview();
};

const startEdit = (loc) => {
    viewState.value = VIEW_STATE.EDIT;
    form.id = loc.id;
    form.unit_id = loc.unit_id;
    form.name = loc.name;
    form.latitude = parseFloat(loc.latitude);
    form.longitude = parseFloat(loc.longitude);
    form.radius = parseInt(loc.radius);
    form.is_active = Boolean(loc.is_active);
    
    updatePreview();
    map.value.setView([parseFloat(loc.latitude), parseFloat(loc.longitude)], 18);
};

const cancelForm = () => {
    viewState.value = VIEW_STATE.LIST;
    if (previewLayer.value) {
        map.value.removeLayer(previewLayer.value);
        previewLayer.value = null;
    }
    form.reset();
};

const submit = () => {
    const options = {
        onSuccess: () => {
            cancelForm();
            renderLocations();
        }
    };

    if (viewState.value === VIEW_STATE.EDIT) {
        form.put(route('yayasan.attendance-locations.update', form.id), options);
    } else {
        form.post(route('yayasan.attendance-locations.store'), options);
    }
};

const deleteLocation = (id) => {
    if (confirm('Hapus lokasi presensi ini?')) {
        router.delete(route('yayasan.attendance-locations.destroy', id), {
            onSuccess: () => renderLocations()
        });
    }
};

// --- LIFECYCLE ---
onMounted(() => {
    initMap();
});

onUnmounted(() => {
    if (map.value) {
        map.value.remove();
        map.value = null;
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
            
            <!-- SIDEBAR: LIST MODE (Wider Width 400px - 450px) -->
            <div v-if="viewState === VIEW_STATE.LIST" class="w-full lg:w-[420px] xl:w-[460px] bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden flex flex-col transition-all flex-shrink-0">
                <div class="p-4 md:p-5 border-b border-slate-100 space-y-3.5 bg-slate-50/50">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-base text-slate-800">Daftar Titik Absensi</h3>
                            <p class="text-[11px] text-slate-500">Klik item untuk melihat di peta</p>
                        </div>
                        <span class="text-xs font-black bg-teal-100 text-teal-700 px-2.5 py-1 rounded-xl border border-teal-200 shadow-sm">{{ locations.total }} Titik</span>
                    </div>
                    
                    <!-- Search & Filter -->
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

                        <button 
                            @click="startCreate(true)" 
                            class="w-full px-4 py-2.5 bg-namira-teal text-white rounded-xl font-bold text-xs shadow-md hover:bg-teal-600 transition-all active:scale-95 flex items-center justify-center gap-2"
                        >
                            <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                            <span>Tambah Lokasi Baru</span>
                        </button>
                    </div>
                </div>
                
                <!-- Scrollable Location List -->
                <div class="flex-1 overflow-y-auto p-3 md:p-4 space-y-2.5">
                    <div v-for="loc in locations.data" :key="loc.id" 
                        class="p-3.5 rounded-2xl border cursor-pointer bg-white group relative transition-all hover:shadow-md hover:-translate-y-0.5"
                        :class="loc.is_active ? 'border-slate-200 hover:border-namira-teal' : 'border-slate-200 bg-slate-50/60 opacity-75'"
                        @click="map.setView([parseFloat(loc.latitude), parseFloat(loc.longitude)], 18)"
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
                                <button @click.stop="startEdit(loc)" class="text-blue-600 hover:bg-blue-50 p-1.5 rounded-lg transition-colors" title="Edit Lokasi">
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

            <!-- SIDEBAR: FORM MODE (CREATE/EDIT) -->
            <div v-else class="w-full lg:w-[420px] xl:w-[460px] bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden flex flex-col transition-all flex-shrink-0">
                <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <div>
                        <h3 class="font-bold text-base text-slate-900">{{ viewState === VIEW_STATE.EDIT ? 'Edit Lokasi Absensi' : 'Tambah Lokasi Baru' }}</h3>
                        <p class="text-[11px] text-slate-500">Isi data atau klik di peta untuk atur koordinat</p>
                    </div>
                    <button @click="cancelForm" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-200 rounded-xl transition-colors">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
                
                <div class="p-5 space-y-4 flex-1 overflow-y-auto">
                    <!-- Unit Selection -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Unit Sekolah *</label>
                        <select v-model="form.unit_id" class="w-full rounded-xl border-slate-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-xs font-semibold py-2.5">
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                        </select>
                        <div v-if="form.errors.unit_id" class="text-rose-500 text-xs mt-1 font-bold">{{ form.errors.unit_id }}</div>
                    </div>

                    <!-- Name -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Nama Lokasi *</label>
                        <input v-model="form.name" type="text" class="w-full rounded-xl border-slate-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-xs font-semibold py-2.5" placeholder="Contoh: Gedung Utama SD Namira">
                        <div v-if="form.errors.name" class="text-rose-500 text-xs mt-1 font-bold">{{ form.errors.name }}</div>
                    </div>

                    <!-- Coordinates Card -->
                    <div class="p-4 bg-teal-50/60 rounded-2xl border border-teal-100 space-y-2">
                        <p class="text-xs text-teal-800 font-extrabold flex items-center gap-1.5">
                            <MapPinIcon class="h-4 w-4 text-teal-600" />
                            Koordinat GPS Geofence
                        </p>
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="bg-white p-2.5 rounded-xl border border-teal-100">
                                <span class="text-slate-400 text-[10px] font-bold uppercase block">Latitude</span>
                                <div class="font-mono text-slate-800 font-bold mt-0.5">{{ typeof form.latitude === 'number' ? form.latitude.toFixed(6) : '-' }}</div>
                            </div>
                            <div class="bg-white p-2.5 rounded-xl border border-teal-100">
                                <span class="text-slate-400 text-[10px] font-bold uppercase block">Longitude</span>
                                <div class="font-mono text-slate-800 font-bold mt-0.5">{{ typeof form.longitude === 'number' ? form.longitude.toFixed(6) : '-' }}</div>
                            </div>
                        </div>
                        <p class="text-[10.5px] text-teal-700 font-semibold pt-1">💡 Klik di peta untuk menggeser koordinat lokasi ini.</p>
                    </div>

                    <!-- Radius -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Radius Geofence (Meter) *</label>
                        <input v-model="form.radius" @input="updatePreview" type="number" min="10" max="1000" class="w-full rounded-xl border-slate-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-xs font-semibold py-2.5">
                        <p class="text-[10px] text-slate-400 mt-1">Jangkauan jarak toleransi absensi dari titik pusat (default 100m).</p>
                        <div v-if="form.errors.radius" class="text-rose-500 text-xs mt-1 font-bold">{{ form.errors.radius }}</div>
                    </div>
                    
                    <!-- Active Toggle -->
                    <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                        <div>
                            <span class="text-xs font-extrabold text-slate-800 uppercase tracking-wider block">Status Aktif</span>
                            <span class="text-[10px] text-slate-400">Aktifkan untuk presensi pegawai</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-teal-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-namira-teal"></div>
                        </label>
                    </div>
                </div>

                <div class="px-5 py-3.5 bg-slate-50 flex justify-end gap-2.5 border-t border-slate-100">
                    <button @click="cancelForm" class="px-4 py-2 text-slate-600 font-bold hover:bg-slate-200 rounded-xl text-xs transition-colors">Batal</button>
                    <button @click="submit" :disabled="form.processing" class="px-5 py-2 bg-namira-teal text-white font-extrabold rounded-xl shadow-md hover:bg-teal-600 transition-all text-xs active:scale-95">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Lokasi' }}
                    </button>
                </div>
            </div>

            <!-- MAP AREA (2/3 width or remaining flex space) -->
            <div class="w-full flex-1 bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden relative group">
                <div ref="mapContainer" class="w-full h-full z-0 cursor-crosshair"></div>
                
                <!-- Search Bar Overlay -->
                <div class="absolute top-4 left-4 right-16 z-[400] bg-white rounded-2xl shadow-lg border border-slate-200 p-1 flex gap-2">
                    <input 
                        v-model="mapSearchQuery" 
                        @keyup.enter="searchLocation"
                        type="text" 
                        placeholder="Cari alamat (misal: Alun-alun Probolinggo)..." 
                        class="w-full border-none text-xs font-semibold focus:ring-0 rounded-xl pl-3"
                    >
                    <button @click="searchLocation" class="bg-namira-teal text-white px-3.5 py-2 rounded-xl hover:bg-teal-700 transition-colors flex items-center gap-1 text-xs font-bold">
                        <MagnifyingGlassIcon class="h-4 w-4" />
                        <span>Cari</span>
                    </button>
                </div>

                <!-- My Location Button -->
                <button 
                    @click="getCurrentLocation"
                    class="absolute bottom-6 right-6 z-[400] bg-white p-3 rounded-2xl shadow-xl border border-slate-200 hover:bg-slate-50 text-slate-700 transition-all active:scale-95 flex items-center gap-2 text-xs font-bold"
                    title="Deteksi Lokasi Saya"
                >
                    <MapPinIcon class="h-5 w-5 text-teal-600" />
                    <span class="hidden sm:inline">Lokasi Saya</span>
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* Custom Leaflet Pin Marker Styles */
.custom-leaflet-pin {
    background: none !important;
    border: none !important;
}
</style>
