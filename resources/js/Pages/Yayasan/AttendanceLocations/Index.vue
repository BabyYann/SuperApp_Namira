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
    MagnifyingGlassIcon, PlusIcon, MapPinIcon, PencilSquareIcon, TrashIcon, MapIcon, BuildingOfficeIcon, XMarkIcon, CheckIcon, SparklesIcon, SignalIcon
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

// Custom Ultra-Modern SVG Pin Icon Generator with Pulsing Radar Ring
const createCustomPinIcon = (color = '#0d9488', isActive = true) => {
    const pulseHtml = isActive ? `
        <div class="absolute -inset-2 rounded-full bg-teal-400/30 animate-ping pointer-events-none"></div>
    ` : '';

    return L.divIcon({
        className: 'custom-leaflet-pin',
        html: `
            <div style="position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                ${pulseHtml}
                <div style="background: linear-gradient(135deg, ${color}, ${isActive ? '#0f766e' : '#475569'}); width: 34px; height: 34px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(13, 148, 136, 0.4); border: 2.5px solid #ffffff; transition: transform 0.3s ease;">
                    <div style="width: 10px; height: 10px; background-color: #ffffff; border-radius: 50%; box-shadow: inset 0 1px 2px rgba(0,0,0,0.3);"></div>
                </div>
            </div>
        `,
        iconSize: [34, 34],
        iconAnchor: [17, 34],
        popupAnchor: [0, -34],
    });
};

// --- STATE MANAGEMENT ---
const mainMapContainer = ref(null);
const mainMap = ref(null);
const mainMarkers = ref([]);
const mainCircles = ref([]);
const selectedLoc = ref(null);

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
        zoomControl: false
    }).setView(initialCenter, 16);

    // Zoom Control at bottom-left
    L.control.zoom({ position: 'bottomleft' }).addTo(mainMap.value);

    // Sleek CartoDB Voyager Light Tiles (Linear/Stripe Aesthetic)
    const voyagerLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; CartoDB & OpenStreetMap'
    });

    // High-Res Satellite View
    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri'
    });

    // Futuristic Dark Mode Tiles
    const darkLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; CartoDB Dark'
    });

    // Standard OpenStreetMap
    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    });

    voyagerLayer.addTo(mainMap.value);

    const baseMaps = {
        "✨ Modern Voyager": voyagerLayer,
        "🛰️ Satelit High-Res": satelliteLayer,
        "🌙 Cyber Dark": darkLayer,
        "🗺️ Peta Jalan Standard": osmLayer
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
        
        const pinIcon = createCustomPinIcon(color, loc.is_active);

        const marker = L.marker([lat, lng], { 
            icon: pinIcon,
            opacity: loc.is_active ? 1 : 0.75 
        }).addTo(mainMap.value);

        const circle = L.circle([lat, lng], {
            className: 'animated-geofence-radar',
            color: color,
            fillColor: color,
            fillOpacity: 0.18,
            weight: 2.5,
            radius: parseInt(loc.radius)
        }).addTo(mainMap.value);

        marker.on('click', (e) => {
            L.DomEvent.stopPropagation(e);
            selectedLoc.value = loc;
            focusLocation(loc);
        });

        marker.bindTooltip(`
            <div class="px-2 py-1 text-center">
                <div class="font-black text-slate-900 text-xs">${loc.name}</div>
                <div class="text-[10px] text-teal-700 font-bold">${loc.unit?.name || ''} • Radius ${loc.radius}m</div>
            </div>
        `, { permanent: false, direction: 'top', className: 'custom-tooltip-glass' });
        
        mainMarkers.value.push(marker);
        mainCircles.value.push(circle);
    });
};

const focusLocation = (loc) => {
    if (!mainMap.value) return;
    selectedLoc.value = loc;
    const lat = parseFloat(loc.latitude);
    const lng = parseFloat(loc.longitude);
    mainMap.value.flyTo([lat, lng], 18, { duration: 1.4, easeLinearity: 0.25 });
};

// --- MODAL FORM & MINI MAP LOGIC ---
const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.unit_id = props.units[0]?.id || '';
    form.is_active = true;

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
    modalMap.value = L.map(modalMapContainer.value, { zoomControl: false }).setView(coords, 17);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; CartoDB'
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
    const color = form.is_active ? '#f59e0b' : '#64748b';
    const pinIcon = createCustomPinIcon(color, form.is_active);

    modalMarker.value = L.marker(coords, { icon: pinIcon, draggable: true }).addTo(modalMap.value);
    modalCircle.value = L.circle(coords, {
        color: color,
        fillColor: color,
        fillOpacity: 0.22,
        weight: 2.5,
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
            mainMap.value.flyTo([latitude, longitude], 17, { duration: 1.5 });
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
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-black text-2xl bg-gradient-to-r from-teal-800 via-teal-600 to-emerald-600 bg-clip-text text-transparent leading-tight">
                            Geofence Presensi Center
                        </h2>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-teal-100 text-teal-800 border border-teal-200 shadow-sm animate-pulse">
                            <SignalIcon class="w-3 h-3 text-teal-600" />
                            GPS Live
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Pemetaan koordinat radar presensi digital sekolah terpusat.</p>
                </div>

                <div class="flex items-center gap-2">
                    <button 
                        @click="openCreateModal" 
                        class="px-4 py-2.5 bg-gradient-to-r from-namira-teal to-teal-600 text-white rounded-2xl font-black text-xs shadow-lg shadow-teal-500/25 hover:shadow-teal-500/40 hover:-translate-y-0.5 transition-all active:scale-95 flex items-center gap-2"
                    >
                        <PlusIcon class="w-4 h-4 stroke-[3]" />
                        <span>Tambah Titik Lokasi</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-4 max-w-[1600px] mx-auto px-2 md:px-4 flex flex-col lg:flex-row gap-5 h-[calc(100vh-155px)] min-h-[650px]">
            
            <!-- SIDEBAR: DAFTAR TITIK ABSENSI (Clean 380px) -->
            <div class="w-full lg:w-[380px] xl:w-[400px] bg-white/90 backdrop-blur-xl rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden flex flex-col transition-all flex-shrink-0">
                <div class="p-4 md:p-5 border-b border-slate-100 space-y-3.5 bg-slate-50/60">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="font-black text-base text-slate-900">Daftar Titik Absensi</h3>
                            <p class="text-[11px] text-slate-500">Klik item untuk fokus radar di peta</p>
                        </div>
                        <span class="text-xs font-black bg-gradient-to-r from-teal-500 to-emerald-600 text-white px-3 py-1 rounded-xl shadow-md shadow-teal-500/20">{{ locations.total }} Titik</span>
                    </div>
                    
                    <!-- Search & Filter Toolbar -->
                    <div class="space-y-2.5">
                        <div class="relative w-full">
                            <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-3 text-slate-400" />
                            <input 
                                v-model="search" 
                                type="text" 
                                placeholder="Cari nama lokasi..." 
                                class="pl-9 pr-3 py-2 w-full bg-white border border-slate-200 rounded-2xl text-xs font-semibold focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm"
                            >
                        </div>
                        
                        <div class="flex gap-2">
                            <select v-model="unitFilter" class="w-full py-2 px-3 bg-white border border-slate-200 rounded-2xl text-xs font-semibold focus:border-namira-teal focus:ring focus:ring-namira-teal/20 shadow-sm cursor-pointer">
                                <option value="">Semua Unit Sekolah</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Location Cards List -->
                <div class="flex-1 overflow-y-auto p-3 md:p-4 space-y-2.5">
                    <div v-for="loc in locations.data" :key="loc.id" 
                        class="p-4 rounded-2xl border cursor-pointer bg-white group relative transition-all duration-300 hover:shadow-lg hover:border-namira-teal"
                        :class="selectedLoc?.id === loc.id ? 'border-namira-teal ring-2 ring-namira-teal/20 bg-teal-50/20' : (loc.is_active ? 'border-slate-200/90' : 'border-slate-200 bg-slate-50/60 opacity-75')"
                        @click="focusLocation(loc)"
                    >
                        <div class="flex justify-between items-start">
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-extrabold text-slate-900 text-sm leading-tight group-hover:text-namira-teal transition-colors">{{ loc.name }}</h4>
                                    <span v-if="!loc.is_active" class="px-1.5 py-0.5 bg-slate-200 text-slate-600 text-[9px] font-extrabold rounded-md uppercase">Non-Aktif</span>
                                </div>
                                
                                <div class="flex items-center gap-2 text-xs text-slate-500">
                                    <span class="inline-flex items-center gap-1 font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-lg text-[11px]">
                                        <BuildingOfficeIcon class="w-3.5 h-3.5 text-teal-600" />
                                        {{ loc.unit?.name || 'Unit' }}
                                    </span>
                                    <span class="text-[11px] font-extrabold text-teal-800 bg-teal-50 px-2.5 py-0.5 rounded-lg border border-teal-200/80 shadow-xs">
                                        {{ loc.radius }}m
                                    </span>
                                </div>

                                <div class="text-[10px] font-mono text-slate-400 pt-0.5 flex items-center gap-1">
                                    <MapPinIcon class="w-3 h-3 text-slate-400" />
                                    {{ parseFloat(loc.latitude).toFixed(5) }}, {{ parseFloat(loc.longitude).toFixed(5) }}
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-1 bg-slate-50 p-1 rounded-xl shadow-xs border border-slate-200/80">
                                <button @click.stop="openEditModal(loc)" class="text-blue-600 hover:bg-blue-100/70 p-1.5 rounded-lg transition-colors" title="Edit Lokasi">
                                    <PencilSquareIcon class="h-4 w-4" />
                                </button>
                                <button @click.stop="deleteLocation(loc.id)" class="text-rose-600 hover:bg-rose-100/70 p-1.5 rounded-lg transition-colors" title="Hapus Lokasi">
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

            <!-- MAIN FULL MAP AREA (Ultra-Sleek) -->
            <div class="w-full flex-1 bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden relative group">
                <div ref="mainMapContainer" class="w-full h-full z-0 cursor-crosshair"></div>
                
                <!-- Address Search Overlay Bar (Compact Top-Left Search Bar) -->
                <div class="absolute top-3 left-3 w-[calc(100%-85px)] max-w-[250px] sm:max-w-[300px] z-[400] bg-white/95 backdrop-blur-md rounded-2xl shadow-xl border border-slate-200/90 p-1 flex gap-1 items-center">
                    <input 
                        v-model="mapSearchQuery" 
                        @keyup.enter="searchLocation"
                        type="text" 
                        placeholder="Cari alamat di peta..." 
                        class="w-full border-none text-xs font-semibold focus:ring-0 rounded-xl pl-2.5 bg-transparent placeholder:text-slate-400 min-w-0"
                    >
                    <button @click="searchLocation" class="bg-gradient-to-r from-namira-teal to-teal-600 text-white px-3 py-1.5 rounded-xl hover:shadow-md transition-all flex items-center gap-1 text-xs font-black shrink-0">
                        <MagnifyingGlassIcon class="h-3.5 w-3.5" />
                        <span class="hidden sm:inline">Cari</span>
                    </button>
                </div>

                <!-- Sleek Bottom-Right Radar HUD Overlay Widget -->
                <div class="absolute bottom-4 right-4 z-[400] bg-slate-900/85 backdrop-blur-xl text-white p-3 rounded-2xl border border-white/10 shadow-2xl space-y-1.5 max-w-[220px] pointer-events-none hidden sm:block">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                        <span class="text-[10px] font-black uppercase tracking-wider text-emerald-400">GPS Geofence System</span>
                    </div>
                    <p class="text-[11px] text-slate-300 font-semibold leading-tight">Presensi dikunci otomatis dalam radius zona aman.</p>
                </div>
            </div>
        </div>

        <!-- 🪟 POP-UP MODAL FORM (Dedicated Pop-up with Mini Map Picker) -->
        <Teleport to="body">
            <div v-if="showFormModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-md" @click="closeModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-6 border border-slate-200">
                        
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center font-bold shadow-md shadow-teal-500/10">
                                    <SparklesIcon class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-slate-900">{{ isEditing ? 'Edit Lokasi Presensi' : 'Tambah Lokasi Presensi Baru' }}</h3>
                                    <p class="text-xs text-slate-500">Atur unit, radius geofence, dan geser pin lokasi di peta</p>
                                </div>
                            </div>
                            <button @click="closeModal" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition-colors"><XMarkIcon class="w-6 h-6" /></button>
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
                                        <span class="text-xs font-black text-teal-800 bg-teal-50 px-2.5 py-0.5 rounded-lg border border-teal-200/80">{{ form.radius }} Meter</span>
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
                                        class="text-[11px] font-extrabold text-teal-700 bg-teal-50 hover:bg-teal-100 border border-teal-200 px-2.5 py-1 rounded-xl transition-colors flex items-center gap-1 shadow-xs"
                                    >
                                        <MapPinIcon class="w-3.5 h-3.5" />
                                        <span>GPS Saya</span>
                                    </button>
                                </div>

                                <div class="flex-1 min-h-[230px] rounded-2xl border border-slate-200 overflow-hidden relative shadow-inner">
                                    <div ref="modalMapContainer" class="w-full h-full min-h-[230px]"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                            <button type="button" @click="closeModal" class="px-4 py-2 text-slate-600 font-bold hover:bg-slate-100 rounded-xl text-xs transition-colors">Batal</button>
                            <button type="button" @click="submit" :disabled="form.processing" class="px-6 py-2.5 bg-gradient-to-r from-namira-teal to-teal-600 hover:from-teal-700 hover:to-teal-800 text-white font-black rounded-xl shadow-lg shadow-teal-500/25 text-xs transition-all active:scale-95 flex items-center gap-1.5">
                                <CheckIcon class="w-4 h-4 stroke-[3]" />
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

.custom-tooltip-glass {
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(8px) !important;
    border: 1px solid rgba(226, 232, 240, 0.8) !important;
    border-radius: 12px !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
}

@keyframes pulse-radar {
    0% {
        stroke-opacity: 0.8;
        stroke-width: 2.5px;
    }
    50% {
        stroke-opacity: 0.3;
        stroke-width: 4px;
    }
    100% {
        stroke-opacity: 0.8;
        stroke-width: 2.5px;
    }
}

.animated-geofence-radar {
    animation: pulse-radar 2.5s infinite ease-in-out;
}
</style>
