<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    destination: Object,
    units: Array,
});

const isEdit = computed(() => !!props.destination);
const page = usePage();
const currentUser = page.props.auth.user;

const form = useForm({
    unit_id: props.destination?.unit_id || currentUser.unit_id || '',
    name: props.destination?.name || '',
    city: props.destination?.city || '',
    country: props.destination?.country || 'Indonesia',
    type: props.destination?.type || 'indonesia',
    visit_type: props.destination?.visit_type || 'kunjungan',
    lat: props.destination?.lat !== null && props.destination?.lat !== undefined ? String(props.destination.lat) : '',
    lng: props.destination?.lng !== null && props.destination?.lng !== undefined ? String(props.destination.lng) : '',
    visit_date: props.destination?.visit_date ? String(props.destination.visit_date).substring(0, 10) : '',
    description: props.destination?.description || '',
    is_active: props.destination?.is_active !== undefined ? props.destination.is_active : true,
    _method: isEdit.value ? 'PUT' : 'POST',
});

// ─── Geocoding ───
const geoQuery = ref('');
const geoResults = ref([]);
const geoLoading = ref(false);
const showDropdown = ref(false);
let geoTimeout = null;

const searchGeo = async () => {
    const q = geoQuery.value.trim();
    if (!q) { geoResults.value = []; showDropdown.value = false; return; }
    geoLoading.value = true;
    showDropdown.value = false;
    try {
        const res = await fetch(
            `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&format=json&limit=5&addressdetails=1`,
            { headers: { 'Accept-Language': 'id,en' } }
        );
        const data = await res.json();
        geoResults.value = data;
        showDropdown.value = data.length > 0;
    } catch (e) {
        geoResults.value = [];
    } finally {
        geoLoading.value = false;
    }
};

const onGeoInput = () => {
    clearTimeout(geoTimeout);
    geoTimeout = setTimeout(searchGeo, 300);
};

const selectGeoResult = (result) => {
    form.lat = parseFloat(result.lat).toFixed(7);
    form.lng = parseFloat(result.lon).toFixed(7);

    // Extract city and country from address
    const addr = result.address || {};
    form.city = addr.city || addr.town || addr.village || addr.county || addr.state || '';
    form.country = addr.country || 'Indonesia';

    // Auto-detect type
    if (form.country.toLowerCase() === 'indonesia') {
        form.type = 'indonesia';
    } else {
        form.type = 'overseas';
    }

    // Update map
    updateMapMarker(parseFloat(result.lat), parseFloat(result.lon));

    geoQuery.value = result.display_name.substring(0, 60);
    showDropdown.value = false;
    geoResults.value = [];
};

// ─── Leaflet Map ───
const mapEl = ref(null);
let leafletMap = null;
let mapMarker = null;
let pickMode = ref(false);

const DEFAULT_CENTER = [-2.5, 118];
const DEFAULT_ZOOM = 4;

const makeIcon = () => L.divIcon({
    html: `<div style="width:16px;height:16px;background:#00A99D;border:2.5px solid #064e3b;border-radius:50%;box-shadow:0 0 0 5px rgba(0,169,157,0.25),0 0 14px rgba(0,169,157,0.5);"></div>`,
    className: '',
    iconSize: [16, 16],
    iconAnchor: [8, 8],
});

const initMap = () => {
    if (!mapEl.value) return;

    if (leafletMap) { leafletMap.remove(); leafletMap = null; mapMarker = null; }

    const hasCoords = form.lat && form.lng;
    const center = hasCoords ? [parseFloat(form.lat), parseFloat(form.lng)] : DEFAULT_CENTER;
    const zoom = hasCoords ? 12 : DEFAULT_ZOOM;

    leafletMap = L.map(mapEl.value, {
        center,
        zoom,
        zoomControl: true,
        scrollWheelZoom: false,
        attributionControl: false,
    });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '© CartoDB',
        maxZoom: 19,
    }).addTo(leafletMap);

    L.control.attribution({ prefix: false }).addTo(leafletMap);

    if (hasCoords) {
        mapMarker = L.marker(center, { icon: makeIcon(), draggable: true }).addTo(leafletMap);
        mapMarker.on('dragend', (e) => {
            const latlng = e.target.getLatLng();
            form.lat = latlng.lat.toFixed(7);
            form.lng = latlng.lng.toFixed(7);
        });
    }

    leafletMap.on('click', (e) => {
        if (!pickMode.value) return;
        const { lat, lng } = e.latlng;
        form.lat = lat.toFixed(7);
        form.lng = lng.toFixed(7);
        updateMapMarker(lat, lng);
        pickMode.value = false;
    });
};

const updateMapMarker = (lat, lng) => {
    if (!leafletMap) return;
    if (mapMarker) {
        mapMarker.setLatLng([lat, lng]);
    } else {
        mapMarker = L.marker([lat, lng], { icon: makeIcon(), draggable: true }).addTo(leafletMap);
        mapMarker.on('dragend', (e) => {
            const latlng = e.target.getLatLng();
            form.lat = latlng.lat.toFixed(7);
            form.lng = latlng.lng.toFixed(7);
        });
    }
    leafletMap.setView([lat, lng], Math.max(leafletMap.getZoom(), 10));
};

// Watch lat/lng changes from manual input
watch([() => form.lat, () => form.lng], ([newLat, newLng]) => {
    const lat = parseFloat(newLat);
    const lng = parseFloat(newLng);
    if (!isNaN(lat) && !isNaN(lng)) {
        updateMapMarker(lat, lng);
    }
});

onMounted(async () => {
    await nextTick();
    setTimeout(() => { initMap(); }, 100);
});

onUnmounted(() => {
    if (leafletMap) { leafletMap.remove(); leafletMap = null; }
});

// Auto detect type from country
watch(() => form.country, (val) => {
    if (!val) return;
    if (val.toLowerCase() === 'indonesia') {
        form.type = 'indonesia';
    } else {
        form.type = 'overseas';
    }
});

const charCount = computed(() => form.description.length);

const submit = () => {
    if (isEdit.value) {
        form.post(route('public-relations.university-destinations.update', props.destination.id));
    } else {
        form.post(route('public-relations.university-destinations.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Destinasi' : 'Tambah Destinasi'" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight">
                    {{ isEdit ? 'Edit Destinasi Universitas' : 'Tambah Destinasi Universitas' }}
                </h2>
                <p class="text-sm text-gray-500">Kelola data kunjungan dan destinasi universitas.</p>
            </div>
        </template>

        <div class="py-6 max-w-5xl mx-auto">
            <form @submit.prevent="submit" class="space-y-6">

                <!-- Card: Info Dasar -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-8 py-5 border-b border-gray-50 bg-gradient-to-r from-teal-50/50 to-white flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-namira-teal/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-namira-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Informasi Institusi</h3>
                    </div>
                    <div class="p-8 space-y-5">

                        <!-- Unit -->
                        <div v-if="units && units.length > 1">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Unit Sekolah <span class="text-red-500">*</span></label>
                            <select v-model="form.unit_id" class="w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm" required>
                                <option value="" disabled>Pilih Unit...</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                            </select>
                            <p v-if="form.errors.unit_id" class="text-xs text-red-600 mt-1">{{ form.errors.unit_id }}</p>
                        </div>

                        <!-- Nama Institusi -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Institusi / Universitas <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" placeholder="Contoh: Universitas Airlangga, Al-Azhar University..." required
                                class="w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm" />
                            <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                        </div>

                        <!-- Jenis Kunjungan & Tipe Wilayah -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Jenis Kunjungan <span class="text-red-500">*</span></label>
                                <select v-model="form.visit_type" class="w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm" required>
                                    <option value="kunjungan">Field Trip / Kunjungan Studi</option>
                                    <option value="alumni">Alumni Diterima di Sini</option>
                                </select>
                                <p v-if="form.errors.visit_type" class="text-xs text-red-600 mt-1">{{ form.errors.visit_type }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Tipe Wilayah <span class="text-red-500">*</span></label>
                                <select v-model="form.type" class="w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm" required>
                                    <option value="indonesia">Indonesia</option>
                                    <option value="overseas">Overseas (Luar Negeri)</option>
                                    <option value="lokal">Lokal / Regional</option>
                                </select>
                                <p v-if="form.errors.type" class="text-xs text-red-600 mt-1">{{ form.errors.type }}</p>
                            </div>
                        </div>

                        <!-- Tanggal Kunjungan & Status -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Tanggal Kunjungan</label>
                                <input v-model="form.visit_date" type="date"
                                    class="w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm" />
                                <p v-if="form.errors.visit_date" class="text-xs text-red-600 mt-1">{{ form.errors.visit_date }}</p>
                            </div>
                            <div class="flex flex-col justify-between">
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Status</label>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative">
                                        <input type="checkbox" v-model="form.is_active" class="sr-only" />
                                        <div :class="form.is_active ? 'bg-namira-teal' : 'bg-gray-200'" class="w-11 h-6 rounded-full transition-colors duration-200"></div>
                                        <div :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700">{{ form.is_active ? 'Aktif (tampil di peta)' : 'Non-Aktif (tersembunyi)' }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Deskripsi</label>
                            <textarea v-model="form.description" rows="3" maxlength="1000"
                                placeholder="Catatan singkat tentang kunjungan atau institusi ini..."
                                class="w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm resize-none"></textarea>
                            <p class="text-xs text-gray-400 mt-1 text-right">{{ charCount }}/1000</p>
                            <p v-if="form.errors.description" class="text-xs text-red-600 mt-1">{{ form.errors.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Card: Lokasi & Peta -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-8 py-5 border-b border-gray-50 bg-gradient-to-r from-blue-50/50 to-white flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-blue-500/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Lokasi & Peta</h3>
                    </div>
                    <div class="p-8 space-y-5">

                        <!-- Geocoding Search -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Cari Lokasi / Institusi Otomatis</label>
                            <p class="text-xs text-gray-400 mb-2">Ketik nama institusi atau kota lalu pilih dari hasil pencarian. Koordinat, kota, dan negara akan terisi otomatis.</p>
                            <div class="relative">
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <input v-model="geoQuery" @input="onGeoInput" @keyup.enter="searchGeo" type="text"
                                            placeholder="Contoh: Universitas Airlangga Surabaya, Al-Azhar Cairo..."
                                            class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-500/20 text-sm pr-10"
                                        />
                                        <div v-if="geoLoading" class="absolute inset-y-0 right-3 flex items-center">
                                            <svg class="animate-spin h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <button type="button" @click="searchGeo"
                                        class="px-4 py-2 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 transition-colors shrink-0">
                                        Cari
                                    </button>
                                </div>

                                <!-- Dropdown Results -->
                                <div v-if="showDropdown && geoResults.length > 0"
                                    class="absolute z-50 left-0 right-0 mt-1 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                                    <button v-for="(r, idx) in geoResults" :key="idx" type="button"
                                        @click="selectGeoResult(r)"
                                        class="w-full text-left px-4 py-3 hover:bg-blue-50 transition-colors border-b border-gray-50 last:border-0 flex items-start gap-3 group">
                                        <svg class="w-4 h-4 text-blue-400 group-hover:text-blue-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                        <span class="text-xs text-gray-700 leading-relaxed">{{ r.display_name.substring(0, 80) }}{{ r.display_name.length > 80 ? '...' : '' }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- City & Country -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kota <span class="text-red-500">*</span></label>
                                <input v-model="form.city" type="text" placeholder="Surabaya" required
                                    class="w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm" />
                                <p v-if="form.errors.city" class="text-xs text-red-600 mt-1">{{ form.errors.city }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Negara <span class="text-red-500">*</span></label>
                                <input v-model="form.country" type="text" placeholder="Indonesia" required
                                    class="w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm" />
                                <p v-if="form.errors.country" class="text-xs text-red-600 mt-1">{{ form.errors.country }}</p>
                            </div>
                        </div>

                        <!-- Lat & Lng -->
                        <div class="grid grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Latitude</label>
                                <input v-model="form.lat" type="text" placeholder="-7.2788"
                                    class="w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm font-mono" />
                                <p v-if="form.errors.lat" class="text-xs text-red-600 mt-1">{{ form.errors.lat }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Longitude</label>
                                <input v-model="form.lng" type="text" placeholder="112.7508"
                                    class="w-full rounded-xl border-gray-200 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-sm font-mono" />
                                <p v-if="form.errors.lng" class="text-xs text-red-600 mt-1">{{ form.errors.lng }}</p>
                            </div>
                        </div>

                        <!-- Pick on Map Mode -->
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-gray-500">Petunjuk: Seret marker atau klik tombol di bawah untuk memilih titik di peta.</p>
                            <button type="button" @click="pickMode = !pickMode"
                                :class="pickMode ? 'bg-blue-600 text-white ring-2 ring-blue-300' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                                {{ pickMode ? 'Klik pada peta...' : 'Pilih di Peta' }}
                            </button>
                        </div>

                        <!-- Leaflet Map -->
                        <div class="relative rounded-2xl overflow-hidden shadow-lg border border-gray-200" :class="pickMode ? 'ring-2 ring-blue-400' : ''">
                            <div ref="mapEl" style="height:280px;width:100%;"></div>
                            <div v-if="pickMode" class="absolute inset-0 flex items-center justify-center pointer-events-none z-[500]">
                                <div class="bg-blue-600/80 backdrop-blur-sm text-white text-sm font-bold px-5 py-2.5 rounded-full shadow-lg animate-pulse">
                                    Klik di peta untuk memilih koordinat
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 -mt-2">Peta akan otomatis bergerak saat Anda memilih lokasi dari pencarian.</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-2">
                    <Link :href="route('public-relations.university-destinations.index')"
                        class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition-colors">
                        Batal
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="px-6 py-2.5 bg-namira-teal text-white rounded-xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all disabled:opacity-50 flex items-center gap-2">
                        <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ form.processing ? 'Menyimpan...' : (isEdit ? 'Perbarui Destinasi' : 'Simpan Destinasi') }}
                    </button>
                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* Override Leaflet z-index inside form */
.leaflet-top,
.leaflet-bottom {
    z-index: 400 !important;
}
</style>
