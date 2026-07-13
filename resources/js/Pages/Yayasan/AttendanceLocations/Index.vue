<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import debounce from 'lodash/debounce';
import Pagination from '@/Components/Pagination.vue';
import { 
    MagnifyingGlassIcon, PlusIcon, MapPinIcon, PencilSquareIcon, TrashIcon, MapIcon, BuildingOfficeIcon 
} from '@heroicons/vue/24/outline';

// --- PROPS ---
const props = defineProps({
    locations: Object,
    units: Array,
    filters: Object,
});

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
const previewLayer = ref(null); // For the "New Location" marker

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
    latitude: -7.75, // Default (Probolinggoish)
    longitude: 113.2,
    radius: 100,
    is_active: true,
});

// --- MAP LOGIC ---
const initMap = () => {
    if (!mapContainer.value) return;

    // Default Center: Probolinggo Alun-Alun (approx)
    const defaultCenter = [-7.754, 113.216];
    const initialCenter = props.locations.data.length > 0 
        ? [parseFloat(props.locations.data[0].latitude), parseFloat(props.locations.data[0].longitude)] 
        : defaultCenter;

    map.value = L.map(mapContainer.value).setView(initialCenter, 15);

    // Layers
    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    });

    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri'
    });

    // Add default layer
    osmLayer.addTo(map.value);

    // Layer Control
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
        const color = loc.is_active ? '#14b8a6' : '#94a3b8'; // Teal or Slate
        const lat = parseFloat(loc.latitude);
        const lng = parseFloat(loc.longitude);
        
        const marker = L.marker([lat, lng], { 
            opacity: loc.is_active ? 1 : 0.6 
        }).addTo(map.value);

        const circle = L.circle([lat, lng], {
            color: color,
            fillColor: color,
            fillOpacity: 0.2,
            radius: parseInt(loc.radius)
        }).addTo(map.value);

        // Click Marker -> Edit Mode
        marker.on('click', (e) => {
            L.DomEvent.stopPropagation(e); // Prevent map click
            startEdit(loc);
        });

        marker.bindTooltip(`${loc.name}`, { permanent: false, direction: 'top' });
        
        markers.value.push(marker);
        circles.value.push(circle);
    });
};

const handleMapClick = (lat, lng) => {
    // Always switch to CREATE mode on map click (unless already editing)
    if (viewState.value === VIEW_STATE.LIST) {
        startCreate(false); // Don't reset defaults, use clicked coords
    }
    
    // Update Form & Preview
    form.latitude = lat;
    form.longitude = lng;
    updatePreview();
};

const updatePreview = () => {
    if (previewLayer.value) {
        map.value.removeLayer(previewLayer.value);
    }

    const color = form.is_active ? '#f59e0b' : '#94a3b8'; // Orange for preview

    previewLayer.value = L.layerGroup([
        L.marker([form.latitude, form.longitude]),
        L.circle([form.latitude, form.longitude], {
            color: color,
            fillColor: color,
            fillOpacity: 0.3,
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
        
        // Update User Marker
        if (userMarker.value) map.value.removeLayer(userMarker.value);
        
        userMarker.value = L.circleMarker([latitude, longitude], {
            radius: 8,
            fillColor: '#3b82f6',
            color: '#fff',
            weight: 2,
            opacity: 1,
            fillOpacity: 0.8
        }).addTo(map.value).bindPopup(`Lokasi Saya (Akurasi: ${Math.round(accuracy)}m)`).openPopup();

        map.value.setView([latitude, longitude], 18);

        // If in Create/Edit mode, update form
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
                alert('Gagal mendeteksi lokasi. Silakan gunakan fitur "Cari Alamat" atau klik manual di peta.');
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
            
            map.value.setView([latitude, longitude], 16);
            
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

    if (resetCoords) {
        if (map.value) {
            const center = map.value.getCenter();
            form.latitude = center.lat;
            form.longitude = center.lng;
        }
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
    if (confirm('Hapus lokasi ini?')) {
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
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                        Lokasi Absensi
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Kelola titik lokasi (geofencing) untuk presensi digital.</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto flex flex-col lg:flex-row gap-6 h-[calc(100vh-180px)]">
            
            <!-- SIDEBAR: LIST MODE -->
            <div v-if="viewState === VIEW_STATE.LIST" class="w-full lg:w-1/3 bg-white rounded-3xl shadow-sm border border-gray-150 overflow-hidden flex flex-col transition-all">
                <div class="p-5 border-b border-gray-100 space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="font-bold text-lg text-gray-800">Daftar Lokasi</h3>
                        <span class="text-xs font-bold bg-namira-teal/10 text-namira-teal px-2.5 py-1 rounded-lg border border-namira-teal/20">{{ locations.total }}</span>
                    </div>
                    
                    <!-- Search & Filter -->
                    <div class="space-y-3">
                        <div class="relative group w-full">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 group-focus-within:text-namira-teal transition-colors">
                                <MagnifyingGlassIcon class="w-5 h-5" />
                            </div>
                            <input 
                                v-model="search" 
                                type="text" 
                                placeholder="Cari lokasi..." 
                                class="pl-10 pr-4 py-2 w-full bg-white border border-gray-200 rounded-xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm"
                            >
                        </div>
                        
                        <div class="flex gap-2">
                            <select v-model="unitFilter" class="w-full py-2 px-3 bg-white border border-gray-200 rounded-xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 shadow-sm cursor-pointer">
                                <option value="">Semua Unit</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                            </select>
                        </div>

                        <button 
                            @click="startCreate(true)" 
                            class="w-full px-4 py-2.5 bg-namira-teal text-white rounded-xl font-bold text-sm shadow-md hover:bg-teal-600 transition-all active:scale-95 flex items-center justify-center gap-2"
                        >
                            <PlusIcon class="w-4 h-4" />
                            Tambah Lokasi
                        </button>
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    <div v-for="loc in locations.data" :key="loc.id" 
                        class="p-4 rounded-2xl border cursor-pointer bg-white group relative transition-all hover:shadow-md"
                        :class="loc.is_active ? 'border-gray-150 hover:border-namira-teal' : 'border-slate-100 bg-slate-50/50 opacity-75'"
                        @click="map.setView([parseFloat(loc.latitude), parseFloat(loc.longitude)], 18)"
                    >
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-gray-800 text-sm">{{ loc.name }}</h4>
                                    <span v-if="!loc.is_active" class="px-1.5 py-0.5 bg-slate-200 text-slate-500 text-[9px] font-bold rounded uppercase">Non-Aktif</span>
                                </div>
                                <div class="flex items-center gap-1 mt-1 text-gray-400">
                                    <BuildingOfficeIcon class="w-3.5 h-3.5" />
                                    <span class="text-xs font-semibold">{{ loc.unit?.name }}</span>
                                </div>
                            </div>
                            <div class="flex gap-1 bg-white/90 p-1 rounded-xl shadow-sm border border-gray-100 absolute top-3 right-3 opacity-100 lg:opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click.stop="startEdit(loc)" class="text-blue-600 hover:bg-blue-50 p-1.5 rounded-lg transition-colors" title="Edit Lokasi">
                                    <PencilSquareIcon class="h-4 w-4" />
                                </button>
                                <button @click.stop="deleteLocation(loc.id)" class="text-red-600 hover:bg-red-50 p-1.5 rounded-lg transition-colors" title="Hapus Lokasi">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="locations.data.length === 0" class="text-center py-8 text-gray-400 text-sm">
                        Tidak ada lokasi ditemukan.
                    </div>
                </div>

                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    <Pagination :links="locations.links" />
                </div>
            </div>

            <!-- SIDEBAR: FORM MODE (CREATE/EDIT) -->
            <div v-else class="w-full lg:w-1/3 bg-white rounded-3xl shadow-sm border border-gray-150 overflow-hidden flex flex-col transition-all">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-base text-gray-800">{{ viewState === VIEW_STATE.EDIT ? 'Edit Lokasi Absensi' : 'Tambah Lokasi Baru' }}</h3>
                    <button @click="cancelForm" class="text-gray-400 hover:text-gray-600 text-xl font-medium">&times;</button>
                </div>
                
                <div class="p-6 space-y-5 flex-1 overflow-y-auto">
                    <!-- Unit Selection -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1.5">Unit Sekolah</label>
                        <select v-model="form.unit_id" class="w-full rounded-xl border-gray-250 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm">
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                        </select>
                        <div v-if="form.errors.unit_id" class="text-red-500 text-xs mt-1">{{ form.errors.unit_id }}</div>
                    </div>

                    <!-- Name -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1.5">Nama Lokasi</label>
                        <input v-model="form.name" type="text" class="w-full rounded-xl border-gray-250 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm" placeholder="Contoh: Gedung 1 Utama">
                        <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>

                    <!-- Coordinates (Read-only) -->
                    <div class="p-4 bg-blue-50/50 rounded-2xl border border-blue-100">
                        <p class="text-xs text-blue-600 font-bold mb-3 flex items-center gap-1.5">
                            <MapPinIcon class="h-4 w-4" />
                            Titik Koordinat Geofence
                        </p>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-400 text-[10px] font-bold uppercase">Latitude</span>
                                <div class="font-mono text-gray-800 font-bold mt-0.5">{{ typeof form.latitude === 'number' ? form.latitude.toFixed(6) : '-' }}</div>
                            </div>
                            <div>
                                <span class="text-gray-400 text-[10px] font-bold uppercase">Longitude</span>
                                <div class="font-mono text-gray-800 font-bold mt-0.5">{{ typeof form.longitude === 'number' ? form.longitude.toFixed(6) : '-' }}</div>
                            </div>
                        </div>
                        <p class="text-[10px] text-blue-500 font-semibold mt-3">💡 Klik pada area peta untuk menggeser koordinat.</p>
                    </div>

                    <!-- Radius -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1.5">Radius Geofence (Meter)</label>
                        <input v-model="form.radius" @input="updatePreview" type="number" class="w-full rounded-xl border-gray-250 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm">
                        <div v-if="form.errors.radius" class="text-red-500 text-xs mt-1">{{ form.errors.radius }}</div>
                    </div>
                    
                    <!-- Active Toggle -->
                    <div class="flex items-center justify-between p-3.5 bg-gray-50 rounded-2xl border border-gray-100">
                        <span class="text-xs font-bold text-gray-600 uppercase tracking-wide">Status Aktif</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-teal-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-namira-teal"></div>
                        </label>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                    <button @click="cancelForm" class="px-4 py-2 text-gray-600 font-bold hover:bg-gray-200 rounded-xl text-sm transition-colors">Batal</button>
                    <button @click="submit" :disabled="form.processing" class="px-6 py-2 bg-namira-teal text-white font-bold rounded-xl shadow-md hover:bg-teal-600 transition-colors text-sm">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                    </button>
                </div>
            </div>

            <!-- MAP AREA -->
            <div class="w-full lg:w-2/3 bg-white rounded-3xl shadow-sm border border-gray-150 overflow-hidden relative group">
                <div ref="mapContainer" class="w-full h-full z-0 cursor-crosshair"></div>
                
                <!-- Search Bar Overlay -->
                <div class="absolute top-4 left-4 right-16 z-[400] bg-white rounded-xl shadow-md border border-gray-200 p-1 flex gap-2">
                    <input 
                        v-model="mapSearchQuery" 
                        @keyup.enter="searchLocation"
                        type="text" 
                        placeholder="Cari alamat (misal: Alun-alun Probolinggo)..." 
                        class="w-full border-none text-sm focus:ring-0 rounded-xl pl-3"
                    >
                    <button @click="searchLocation" class="bg-namira-teal text-white p-2.5 rounded-xl hover:bg-teal-700 transition-colors">
                        <MagnifyingGlassIcon class="h-5 w-5" />
                    </button>
                </div>

                <!-- My Location Button -->
                <button 
                    @click="getCurrentLocation"
                    class="absolute bottom-4 right-4 z-[400] bg-white p-3 rounded-full shadow-lg border border-gray-200 hover:bg-gray-50 text-gray-600 transition-all active:scale-95"
                    title="Deteksi Lokasi Saya"
                >
                    <MapPinIcon class="h-5 w-5" />
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
