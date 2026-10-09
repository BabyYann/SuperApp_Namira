<template>
    <Head title="Presensi Pegawai" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                Presensi Pegawai
            </h2>
        </template>

        <div class="py-4 md:py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4 md:space-y-5">
            
            <!-- MAIN TOP NAVIGATION: Presensi Saya vs Presensi Karyawan -->
            <div class="flex items-center justify-center p-1 bg-slate-100 rounded-2xl max-w-md mx-auto border border-slate-200 shadow-xs">
                <button
                    @click="setMainTab('personal')"
                    type="button"
                    class="flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer"
                    :class="mainTab === 'personal' ? 'bg-[#00584b] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                >
                    <UserIcon class="w-4 h-4" />
                    <span>Presensi Saya</span>
                </button>

                <button
                    @click="setMainTab('live')"
                    type="button"
                    class="flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer"
                    :class="mainTab === 'live' ? 'bg-[#00584b] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                >
                    <UserGroupIcon class="w-4 h-4" />
                    <span>Presensi Karyawan</span>
                </button>
            </div>

            <!-- ========================================== -->
            <!-- TAB 1: PRESENSI SAYA (MATCHES MOCKUP DESIGN) -->
            <!-- ========================================== -->
            <div v-if="mainTab === 'personal'" class="max-w-2xl mx-auto space-y-4 pb-28 sm:pb-8">

                <!-- CARD 1A: HERO HEADER BANNER WITH ILLUSTRATION BACKGROUND -->
                <div class="relative rounded-3xl overflow-hidden border border-emerald-100/70 shadow-md p-5 sm:p-6 flex flex-col justify-between">
                    <!-- Background Illustration -->
                    <img 
                        src="/images/attendance_header_bg.png" 
                        alt="Header Ilustrasi Sekolah" 
                        class="absolute inset-0 w-full h-full object-cover object-right pointer-events-none select-none"
                    />
                    
                    <!-- Soft Gradient Overlay for text contrast -->
                    <div class="absolute inset-0 bg-gradient-to-r from-[#e6f7f3]/95 via-white/85 to-white/20 sm:to-transparent pointer-events-none"></div>

                    <!-- Banner Content Layer -->
                    <div class="relative z-10 flex flex-col justify-between">
                        <!-- Top Row: Greeting & Frosted Glass Calendar Icon -->
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="text-[11px] font-extrabold tracking-widest text-[#00584b] uppercase">
                                    PRESENSI PEGAWAI
                                </span>
                                <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1 tracking-tight">
                                    Halo, {{ $page.props.auth.user.name }}
                                </h3>
                                <p class="text-xs sm:text-sm text-slate-600 font-medium mt-0.5">
                                    {{ todayFormatted }}
                                </p>

                                <!-- Status Pill Badge -->
                                <div class="mt-2.5">
                                    <div v-if="todayAttendance" class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-bold border shadow-xs bg-white/95 backdrop-blur-sm"
                                        :class="todayAttendance.approval_status === 'pending' 
                                            ? 'text-amber-700 border-amber-200' 
                                            : 'text-emerald-700 border-emerald-200'">
                                        <span class="w-2 h-2 rounded-full" :class="todayAttendance.approval_status === 'pending' ? 'bg-amber-500' : 'bg-emerald-500 animate-pulse'"></span>
                                        <span>{{ todayAttendance.check_in_time ? 'Sudah Masuk: ' + todayAttendance.check_in_time : 'Sudah Mengajukan' }}</span>
                                    </div>
                                    <div v-else class="inline-flex items-center gap-2 px-3.5 py-1 bg-white/95 backdrop-blur-sm border border-rose-200 shadow-xs text-rose-600 rounded-full text-xs font-bold">
                                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                        <span>Belum Absen Masuk</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Top-Right Frosted Glass Calendar Icon (Matching Reference Mockup) -->
                            <div class="w-13 h-13 sm:w-14 sm:h-14 rounded-2xl bg-white/45 backdrop-blur-md border border-white/70 shadow-sm flex items-center justify-center shrink-0">
                                <svg class="w-7 h-7 sm:w-8 sm:h-8 text-white/90 drop-shadow-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="3" ry="3"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                    <rect x="7" y="14" width="4" height="4" rx="1" fill="currentColor" fill-opacity="0.3"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Bottom Segmented Mode Tabs: WFO (Hadir) | Dinas Luar | Izin / Sakit -->
                        <div v-if="!todayAttendance" class="mt-5 sm:mt-6 bg-white/90 backdrop-blur-md border border-white/80 p-1.5 rounded-2xl flex items-center gap-1.5 shadow-sm">
                            <button
                                @click="activeTab = 'present'"
                                type="button"
                                class="flex-1 flex items-center justify-center gap-2 py-2.5 px-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer"
                                :class="activeTab === 'present' ? 'bg-[#00584b] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            >
                                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/>
                                    <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/>
                                    <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/>
                                    <path d="M10 6h4"/>
                                    <path d="M10 10h4"/>
                                    <path d="M10 14h4"/>
                                    <path d="M10 18h4"/>
                                </svg>
                                <span>WFO (Hadir)</span>
                            </button>
                            <button
                                @click="activeTab = 'business_trip'"
                                type="button"
                                class="flex-1 flex items-center justify-center gap-2 py-2.5 px-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer"
                                :class="activeTab === 'business_trip' ? 'bg-[#00584b] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            >
                                <svg class="w-4 h-4 shrink-0 rotate-45" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
                                </svg>
                                <span>Dinas Luar</span>
                            </button>
                            <button
                                @click="activeTab = 'permit'"
                                type="button"
                                class="flex-1 flex items-center justify-center gap-2 py-2.5 px-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer"
                                :class="activeTab === 'permit' ? 'bg-[#00584b] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            >
                                <DocumentPlusIcon class="w-4 h-4 shrink-0" />
                                <span>Izin / Sakit</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- CARD 1B: LOKASI PRESENSI BOX (Shown for WFO & Dinas Luar) -->
                <div v-show="activeTab !== 'permit'" class="bg-white rounded-3xl border border-gray-100 shadow-xs p-4 sm:p-5 space-y-4">
                    <!-- Location Header & Refresh Button -->
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-full bg-emerald-50 text-[#00584b] flex items-center justify-center shrink-0">
                                <MapPinIcon class="w-5 h-5 text-[#00584b]" />
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-sm font-bold text-gray-900">Lokasi presensi</h4>
                                <p class="text-xs text-gray-500 truncate">
                                    {{ nearestLocation ? (nearestLocation.name + (nearestLocation.address ? ', ' + nearestLocation.address : '')) : 'Mencari lokasi sekolah...' }}
                                </p>
                            </div>
                        </div>
                        
                        <button
                            @click="refreshLocation"
                            type="button"
                            :disabled="isLocating"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-emerald-800/40 text-[#00584b] text-xs font-bold hover:bg-emerald-50 active:scale-95 transition-all cursor-pointer shrink-0"
                        >
                            <ArrowPathIcon class="w-3.5 h-3.5" :class="isLocating ? 'animate-spin' : ''" />
                            <span>Perbarui lokasi</span>
                        </button>
                    </div>

                    <!-- Mini Leaflet Map -->
                    <div class="relative w-full h-40 sm:h-48 rounded-2xl overflow-hidden border border-slate-100 z-0">
                        <div id="map" ref="mapContainer" class="w-full h-full"></div>
                    </div>

                    <!-- Proximity Alert Banner -->
                    <div v-if="isWithinRadius" class="p-3 bg-emerald-50 border border-emerald-100 rounded-xl flex items-start gap-2.5 text-left">
                        <CheckCircleIcon class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-xs font-bold text-emerald-800">
                                Di dalam jangkauan ({{ nearestLocation?.name }})
                            </p>
                            <p class="text-[11px] text-emerald-600 mt-0.5">
                                Anda berada di area presensi dan siap untuk melakukan absensi.
                            </p>
                        </div>
                    </div>
                    <div v-else-if="activeTab === 'business_trip'" class="p-3 bg-sky-50 border border-sky-100 rounded-xl flex items-start gap-2.5 text-left">
                        <InformationCircleIcon class="w-5 h-5 text-sky-600 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-xs font-bold text-sky-800">
                                Lokasi Bebas (Dinas Luar)
                            </p>
                            <p class="text-[11px] text-sky-600 mt-0.5">
                                Presensi penugasan dinas di luar sekolah dengan melampirkan foto selfie & keterangan kegiatan.
                            </p>
                        </div>
                    </div>
                    <div v-else class="p-3 bg-rose-50 border border-rose-100 rounded-xl flex items-start gap-2.5 text-left">
                        <ExclamationCircleIcon class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-xs font-bold text-rose-700">
                                Di luar jangkauan (Jarak: {{ distanceToNearest ? distanceToNearest + ' m' : 'Menghitung...' }})
                            </p>
                            <p class="text-[11px] text-rose-600 mt-0.5">
                                Anda berada di luar area presensi sekolah.
                            </p>
                        </div>
                    </div>

                    <!-- Camera Viewfinder & Action Form (WFO / Dinas) -->
                    <div v-if="!todayAttendance" class="pt-2 border-t border-slate-100">
                        <!-- Note for Dinas Luar -->
                        <div v-if="activeTab === 'business_trip'" class="mb-3">
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Keterangan Kegiatan Dinas <span class="text-rose-500">*</span>
                            </label>
                            <textarea 
                                v-model="form.note" 
                                rows="2" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl text-xs p-2.5 focus:border-[#00584b] focus:ring-[#00584b] resize-none"
                                placeholder="Contoh: Mengikuti workshop kurikulum di dinas..."
                            ></textarea>
                        </div>

                        <!-- Camera View / Capture -->
                        <div v-if="isCameraOpen" class="relative w-full max-w-xs mx-auto aspect-[3/4] bg-slate-900 rounded-2xl overflow-hidden border-2 border-slate-700 shadow-lg flex items-center justify-center mb-3">
                            <video ref="videoRef" autoplay playsinline class="w-full h-full object-cover"></video>
                            <canvas ref="canvasRef" class="hidden"></canvas>
                            
                            <div class="absolute bottom-4 left-0 right-0 flex items-center justify-center gap-4">
                                <button 
                                    type="button" 
                                    @click="stopCamera" 
                                    class="px-3.5 py-1.5 bg-black/50 text-white rounded-xl text-xs font-semibold backdrop-blur-sm cursor-pointer hover:bg-black/70"
                                >
                                    Batal
                                </button>
                                <button 
                                    type="button" 
                                    @click="takePhoto" 
                                    class="w-14 h-14 bg-white rounded-full border-4 border-slate-300 flex items-center justify-center shadow-xl active:scale-95 transition-transform cursor-pointer"
                                >
                                    <div class="w-10 h-10 bg-rose-600 rounded-full"></div>
                                </button>
                            </div>
                        </div>

                        <!-- Photo Preview & Confirmation -->
                        <div v-else-if="photoPreview" class="w-full max-w-xs mx-auto space-y-3">
                            <div class="relative aspect-[3/4] bg-slate-900 rounded-2xl overflow-hidden border-2 border-[#00584b] shadow-md">
                                <img :src="photoPreview" class="w-full h-full object-cover" />
                            </div>
                            <div class="space-y-2">
                                <button 
                                    @click="submitCheckIn(activeTab)" 
                                    :disabled="form.processing || (activeTab === 'business_trip' && !form.note)" 
                                    class="w-full py-3.5 bg-[#00584b] hover:bg-[#00473c] text-white rounded-2xl font-bold text-sm sm:text-base shadow-md transition-all active:scale-95 disabled:opacity-50 cursor-pointer"
                                >
                                    {{ form.processing ? 'Mengirim...' : (activeTab === 'business_trip' ? 'Konfirmasi Dinas Luar' : 'Konfirmasi Hadir') }}
                                </button>
                                <button 
                                    @click="photoPreview = null; startCamera()" 
                                    type="button"
                                    class="w-full py-2 text-slate-500 text-xs font-bold hover:text-slate-800 transition-colors cursor-pointer text-center"
                                >
                                    Foto Ulang
                                </button>
                            </div>
                        </div>

                        <!-- Default Action Button (Before Camera Opened) -->
                        <div v-else>
                            <button 
                                @click="startCamera" 
                                :disabled="activeTab === 'present' && !isWithinRadius" 
                                :class="(activeTab === 'present' && !isWithinRadius) 
                                    ? 'opacity-60 cursor-not-allowed bg-slate-200 text-slate-400' 
                                    : 'bg-[#00584b] hover:bg-[#00473c] text-white shadow-md active:scale-95 cursor-pointer'" 
                                class="w-full py-3.5 rounded-2xl font-bold text-sm sm:text-base flex items-center justify-center gap-2 transition-all"
                            >
                                <CameraIcon class="h-5 w-5" />
                                <span>{{ activeTab === 'business_trip' ? 'Ambil Foto Bukti Dinas' : 'Ambil Foto & Absen' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Check Out Button (When already checked in) -->
                    <div v-else-if="todayAttendance && !todayAttendance.check_out_time && (todayAttendance.status === 'present' || todayAttendance.status === 'business_trip' || todayAttendance.status === 'late')" class="pt-2 border-t border-slate-100 space-y-2">
                        <button 
                            @click="submitCheckOut(todayAttendance.id)"
                            :disabled="!isWithinRadius && todayAttendance.status !== 'business_trip'"
                            class="w-full py-3.5 bg-rose-600 hover:bg-rose-700 text-white rounded-2xl font-bold text-sm sm:text-base shadow-md transition-all active:scale-95 disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2"
                        >
                            <span>Absen Pulang Sekarang</span>
                        </button>
                        <p v-if="todayAttendance.status !== 'business_trip' && !isWithinRadius" class="text-xs text-rose-600 font-semibold text-center">
                            Harus berada di lokasi kantor untuk Absen Pulang
                        </p>
                    </div>

                    <!-- Already Completed State -->
                    <div v-else-if="todayAttendance && todayAttendance.check_out_time" class="p-4 bg-emerald-50 text-emerald-800 rounded-2xl border border-emerald-100 font-bold flex items-center justify-center gap-3 w-full">
                        <div class="w-8 h-8 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                            <CheckIcon class="w-5 h-5 stroke-[2.5]" />
                        </div>
                        <span class="text-xs sm:text-sm">Absensi Hari Ini Telah Selesai</span>
                    </div>
                </div>

                <!-- CARD 1C: FORM IZIN / SAKIT (Shown when activeTab === 'permit') -->
                <div v-if="activeTab === 'permit' && !todayAttendance" class="bg-white rounded-3xl border border-gray-100 shadow-xs p-5 space-y-4">
                    <div class="flex items-center gap-2 text-purple-800 font-bold text-sm">
                        <ClipboardDocumentCheckIcon class="w-5 h-5" />
                        <span>Form Pengajuan Izin / Sakit</span>
                    </div>

                    <!-- Radio Type: Izin vs Sakit -->
                    <div class="flex gap-4 p-3 bg-purple-50/60 rounded-xl border border-purple-100">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-purple-900">
                            <input type="radio" value="permit" v-model="permitType" class="text-purple-600 focus:ring-purple-500">
                            <span>Izin</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-purple-900">
                            <input type="radio" value="sick" v-model="permitType" class="text-purple-600 focus:ring-purple-500">
                            <span>Sakit</span>
                        </label>
                    </div>

                    <!-- Keterangan / Alasan -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            Keterangan / Alasan <span class="text-rose-500">*</span>
                        </label>
                        <textarea 
                            v-model="form.note" 
                            rows="3" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl text-xs p-3 focus:border-purple-600 focus:ring-purple-600 resize-none"
                            placeholder="Jelaskan detail alasan ketidakhadiran..."
                        ></textarea>
                    </div>

                    <!-- Upload Bukti Dokumen -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            Upload Bukti (Surat Dokter / Dokumen Pendukung)
                        </label>
                        <input 
                            type="file" 
                            @change="e => form.document = e.target.files[0]" 
                            class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-purple-100 file:text-purple-700 hover:file:bg-purple-200 cursor-pointer"
                        />
                        <p v-if="form.document" class="text-xs text-purple-700 font-bold mt-1 truncate">
                            📎 {{ form.document.name }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        @click="submitCheckIn(permitType)" 
                        :disabled="form.processing || !form.note" 
                        class="w-full py-3.5 bg-purple-700 hover:bg-purple-800 text-white rounded-2xl font-bold text-sm sm:text-base shadow-md transition-all active:scale-95 disabled:opacity-50 cursor-pointer"
                    >
                        {{ form.processing ? 'Mengirim...' : 'Ajukan ' + (permitType === 'sick' ? 'Sakit' : 'Izin') }}
                    </button>
                </div>

                <!-- CARD 2: KALENDER ABSENSI SAYA -->
                <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-5 sm:p-6">
                    <!-- Calendar Header with Month Navigation -->
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                        <div class="flex items-center gap-2.5">
                            <CalendarDaysIcon class="w-6 h-6 text-gray-900" />
                            <div>
                                <h3 class="font-bold text-gray-900 text-base">Kalender Absensi Saya</h3>
                                <p class="text-xs text-gray-500 font-medium">
                                    {{ getMonthName(currentMonth) }} {{ currentYear }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button
                                @click="changeMonth(-1)"
                                type="button"
                                title="Bulan Sebelumnya"
                                class="w-8 h-8 rounded-xl border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50 active:scale-95 transition-all cursor-pointer"
                            >
                                <ChevronLeftIcon class="w-4 h-4" />
                            </button>
                            <button
                                @click="changeMonth(1)"
                                type="button"
                                title="Bulan Berikutnya"
                                class="w-8 h-8 rounded-xl border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50 active:scale-95 transition-all cursor-pointer"
                            >
                                <ChevronRightIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- 7-Column Day Header -->
                    <div class="grid grid-cols-7 text-center pt-3 pb-1">
                        <div 
                            v-for="day in ['MIN', 'SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB']" 
                            :key="day" 
                            class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-1"
                        >
                            {{ day }}
                        </div>
                    </div>

                    <!-- Calendar Numbers Grid -->
                    <div class="grid grid-cols-7 gap-y-2.5 sm:gap-y-3 text-center">
                        <!-- Trailing Days from Previous Month -->
                        <div 
                            v-for="d in prevMonthDays" 
                            :key="'prev-' + d" 
                            class="h-8 sm:h-9 flex items-center justify-center text-xs font-medium text-gray-300"
                        >
                            {{ d }}
                        </div>

                        <!-- Current Month Days -->
                        <div 
                            v-for="day in daysInMonth" 
                            :key="'cur-' + day" 
                            class="flex flex-col items-center justify-center"
                        >
                            <button
                                @click="showDayDetail(day)"
                                type="button"
                                class="relative w-8 h-8 sm:w-9 sm:h-9 flex flex-col items-center justify-center rounded-xl transition-all cursor-pointer"
                                :class="[
                                    isToday(day) 
                                        ? 'bg-[#00584b] text-white font-bold shadow-xs' 
                                        : 'text-gray-700 hover:bg-slate-100 font-semibold text-xs sm:text-sm'
                                ]"
                            >
                                <span>{{ day }}</span>
                                <!-- Status Indicator Dot -->
                                <span 
                                    v-if="getDayData(day)"
                                    class="absolute bottom-1 w-1.5 h-1.5 rounded-full"
                                    :class="isToday(day) ? 'bg-emerald-300' : getStatusDot(getDayData(day).status)"
                                ></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- CARD 3: RIWAYAT PRESENSI -->
                <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-5 sm:p-6">
                    <!-- History Header -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <DocumentTextIcon class="w-5 h-5 text-gray-900" />
                            <h3 class="font-bold text-gray-900 text-base">Riwayat Presensi</h3>
                        </div>

                        <button 
                            v-if="history && history.length > 5"
                            @click="showAllHistory = !showAllHistory" 
                            type="button" 
                            class="text-xs font-bold text-[#00584b] hover:underline flex items-center gap-0.5 cursor-pointer"
                        >
                            <span>{{ showAllHistory ? 'Tampilkan Lebih Sedikit' : 'Lihat Semua' }}</span>
                            <ChevronRightIcon class="w-3.5 h-3.5" :class="showAllHistory ? 'rotate-90' : ''" />
                        </button>
                    </div>

                    <!-- 4-Column Table Header -->
                    <div class="mt-3 bg-slate-50 rounded-xl px-3 sm:px-4 py-2.5 text-[10px] font-bold text-gray-500 uppercase tracking-wider grid grid-cols-4 items-center">
                        <div class="text-left">WAKTU</div>
                        <div class="text-center">JENIS</div>
                        <div class="text-center sm:text-left">LOKASI</div>
                        <div class="text-right">KETERANGAN</div>
                    </div>

                    <!-- Empty State (When no history this month) -->
                    <div v-if="!history || history.length === 0" class="py-8 text-center flex flex-col items-center justify-center">
                        <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center text-gray-400 mb-2">
                            <DocumentTextIcon class="w-6 h-6" />
                        </div>
                        <p class="font-bold text-xs sm:text-sm text-gray-800">
                            Belum ada riwayat presensi bulan ini
                        </p>
                        <p class="text-[11px] sm:text-xs text-gray-400 mt-0.5">
                            Lakukan presensi untuk melihat riwayat Anda di sini.
                        </p>
                    </div>

                    <!-- Filled Rows (When history exists) -->
                    <div v-else class="divide-y divide-slate-100 mt-1">
                        <div 
                            v-for="log in (showAllHistory ? history : history.slice(0, 5))" 
                            :key="log.id"
                            @click="showDayDetailFromLog(log)"
                            class="grid grid-cols-4 items-center px-2 sm:px-3 py-3 hover:bg-slate-50/70 rounded-xl transition-colors cursor-pointer text-xs"
                        >
                            <!-- Col 1: Waktu -->
                            <div class="min-w-0 pr-1 text-left">
                                <p class="font-semibold text-gray-800 truncate">{{ formatDateShort(log.date) }}</p>
                                <p class="font-mono text-[11px] text-gray-500">{{ log.check_in_time ? log.check_in_time.substring(0, 5) : '--:--' }}</p>
                            </div>

                            <!-- Col 2: Jenis -->
                            <div class="text-center pr-1">
                                <span 
                                    class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold border uppercase tracking-wider"
                                    :class="getTypeBadge(log.status).class"
                                >
                                    {{ getTypeBadge(log.status).label }}
                                </span>
                            </div>

                            <!-- Col 3: Lokasi -->
                            <div class="min-w-0 pr-1 text-center sm:text-left">
                                <p class="text-gray-600 truncate text-[11px]">
                                    {{ log.location?.name || nearestLocation?.name || 'Sekolah' }}
                                </p>
                            </div>

                            <!-- Col 4: Keterangan -->
                            <div class="text-right">
                                <span v-if="log.approval_status === 'approved'" class="text-emerald-700 font-bold text-[11px]">
                                    Disetujui
                                </span>
                                <span v-else-if="log.approval_status === 'pending'" class="text-amber-600 font-bold text-[11px]">
                                    Menunggu
                                </span>
                                <span v-else-if="log.approval_status === 'rejected'" class="text-rose-600 font-bold text-[11px]">
                                    Ditolak
                                </span>
                                <span v-else class="text-gray-400 text-[11px]">-</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- ========================================== -->
            <!-- END TAB 1: PRESENSI SAYA -->
            <!-- ========================================== -->

            <!-- ========================================== -->
            <!-- TAB 2: PANTAUAN HARI INI (Live Radar) -->
            <!-- ========================================== -->
            <div v-else-if="mainTab === 'live'">
                <LiveAttendanceRadar :data="liveAttendance" />
            </div>

        </div>

        <!-- Detail Modal for Calendar / History Click -->
        <div v-if="selectedDayData" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="selectedDayData = null">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden animate-fade-in-up">
                <div class="relative h-48 bg-gray-100 flex items-center justify-center">
                    <img v-if="selectedDayData.check_in_photo" :src="`/storage/${selectedDayData.check_in_photo}`" class="w-full h-full object-cover">
                    <span v-else class="text-gray-400 text-sm font-bold">Tidak ada foto selfie</span>
                    <button @click="selectedDayData = null" class="absolute top-2 right-2 bg-black/40 text-white rounded-full p-1.5 hover:bg-black/60 transition-colors cursor-pointer">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Detail Presensi</h3>
                            <p class="text-xs text-gray-500">{{ selectedDayData.date }}</p>
                        </div>
                        <span :class="['px-2.5 py-1 rounded-lg text-xs font-bold uppercase border', getTypeBadge(selectedDayData.status).class]">
                            {{ getTypeBadge(selectedDayData.status).label }}
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <p class="text-xs text-gray-500 font-bold uppercase mb-1">Masuk</p>
                            <p class="text-base font-mono font-bold text-gray-800">{{ selectedDayData.check_in_time || '--:--' }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <p class="text-xs text-gray-500 font-bold uppercase mb-1">Pulang</p>
                            <p class="text-base font-mono font-bold text-gray-800">{{ selectedDayData.check_out_time || '--:--' }}</p>
                        </div>
                    </div>

                    <div v-if="selectedDayData.late_minutes > 0" class="flex items-start gap-2 text-rose-600 bg-rose-50 p-3 rounded-xl border border-rose-100">
                        <ClockIcon class="w-5 h-5 flex-shrink-0" />
                        <div>
                            <p class="font-bold text-xs">Terlambat {{ selectedDayData.late_minutes }} Menit</p>
                            <p class="text-[11px] text-rose-500">Tingkatkan kedisiplinan waktu kehadiran.</p>
                        </div>
                    </div>

                    <div v-if="selectedDayData.note" class="text-xs text-gray-600 bg-gray-50 p-3 rounded-xl border border-gray-100 italic">
                        "{{ selectedDayData.note }}"
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LiveAttendanceRadar from './Partials/LiveAttendanceRadar.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import { useGeolocation } from '@vueuse/core';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import Swal from 'sweetalert2';
import { 
    ClipboardDocumentCheckIcon, CameraIcon, CheckIcon, XMarkIcon, ClockIcon, 
    CheckCircleIcon, UserIcon, UserGroupIcon, DocumentPlusIcon,
    CalendarDaysIcon, DocumentTextIcon, MapPinIcon, ArrowPathIcon, ChevronLeftIcon,
    ChevronRightIcon, ExclamationCircleIcon, InformationCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    todayAttendance: Object,
    history: Array,
    locations: Array,
    calendarData: Object,
    monthStats: Object,
    currentMonth: Number,
    currentYear: Number,
    liveAttendance: Object,
    initialTab: {
        type: String,
        default: 'personal',
    },
});

const getMainInitialTab = () => {
    // 1. Explicit initialTab prop from Inertia
    if (props.initialTab === 'live' || props.initialTab === 'radar') return 'live';
    if (['personal', 'present', 'business_trip', 'permit'].includes(props.initialTab)) return 'personal';

    // 2. URL query param if present
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (['live', 'radar'].includes(tab)) return 'live';
        if (['personal', 'present', 'business_trip', 'permit'].includes(tab)) return 'personal';
    }

    // 3. Default: Always 'personal' (Presensi Saya)
    return 'personal';
};

const mainTab = ref(getMainInitialTab()); // 'personal' | 'live'

const setMainTab = (tab) => {
    mainTab.value = tab;
    if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url.toString());
    }
};

const getInitialTab = () => {
    const tabFromProp = props.initialTab;
    if (['present', 'business_trip', 'permit'].includes(tabFromProp)) return tabFromProp;

    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (['present', 'business_trip', 'permit'].includes(tab)) return tab;
    }
    return 'present';
};

const activeTab = ref(getInitialTab()); // 'present' | 'business_trip' | 'permit'

// Watch props.initialTab when navigating client-side
watch(() => props.initialTab, (newTab) => {
    if (['live', 'radar'].includes(newTab)) {
        mainTab.value = 'live';
    } else {
        mainTab.value = 'personal';
        if (['present', 'business_trip', 'permit'].includes(newTab)) {
            activeTab.value = newTab;
        }
    }
});
const permitType = ref('permit'); // 'permit' | 'sick'
const selectedDayData = ref(null);
const showAllHistory = ref(false);
const isLocating = ref(false);

// Formatted today string e.g. "Jumat, 9 Oktober 2026"
const todayFormatted = computed(() => {
    return new Date().toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
});

// Calendar math
const daysInMonth = computed(() => {
    return new Date(props.currentYear, props.currentMonth, 0).getDate();
});

const daysInPrevMonth = computed(() => {
    return new Date(props.currentYear, props.currentMonth - 1, 0).getDate();
});

// Day of week of 1st day of month (0 = Sun, 1 = Mon, ..., 6 = Sat)
const firstDayOffset = computed(() => {
    return new Date(props.currentYear, props.currentMonth - 1, 1).getDay();
});

// Trailing days from previous month to populate row 1
const prevMonthDays = computed(() => {
    const offset = firstDayOffset.value;
    const totalDays = daysInPrevMonth.value;
    const days = [];
    for (let i = offset - 1; i >= 0; i--) {
        days.push(totalDays - i);
    }
    return days;
});

const getDayData = (day) => {
    const month = String(props.currentMonth).padStart(2, '0');
    const dayStr = String(day).padStart(2, '0');
    const dbDate = `${props.currentYear}-${month}-${dayStr}`;
    return props.calendarData ? props.calendarData[dbDate] : null;
};

const isToday = (day) => {
    const today = new Date();
    return today.getDate() === day && (today.getMonth() + 1) === props.currentMonth && today.getFullYear() === props.currentYear;
};

const showDayDetail = (day) => {
    const data = getDayData(day);
    if (data) selectedDayData.value = data;
};

const showDayDetailFromLog = (log) => {
    selectedDayData.value = log;
};

const getMonthName = (month) => {
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return months[month - 1];
};

const changeMonth = (direction) => {
    let newMonth = props.currentMonth + direction;
    let newYear = props.currentYear;
    if (newMonth < 1) {
        newMonth = 12;
        newYear -= 1;
    } else if (newMonth > 12) {
        newMonth = 1;
        newYear += 1;
    }
    router.get(route('employee.attendance.index'), {
        month: newMonth,
        year: newYear,
        tab: 'personal',
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getStatusDot = (status) => {
    const map = {
        'present': 'bg-emerald-500', 
        'late': 'bg-rose-500', 
        'business_trip': 'bg-indigo-500', 
        'sick': 'bg-sky-500', 
        'permit': 'bg-amber-500',
    };
    return map[status] || 'bg-gray-400';
};

const getTypeBadge = (status) => {
    const map = {
        'present': { label: 'Hadir', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
        'late': { label: 'Terlambat', class: 'bg-rose-50 text-rose-700 border-rose-200' },
        'business_trip': { label: 'Dinas Luar', class: 'bg-indigo-50 text-indigo-700 border-indigo-200' },
        'sick': { label: 'Sakit', class: 'bg-sky-50 text-sky-700 border-sky-200' },
        'permit': { label: 'Izin', class: 'bg-amber-50 text-amber-700 border-amber-200' },
        'cuti': { label: 'Cuti', class: 'bg-purple-50 text-purple-700 border-purple-200' },
    };
    return map[status] || { label: status, class: 'bg-gray-50 text-gray-700 border-gray-200' };
};

const formatDateShort = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
};

// Geolocation
const { coords, resume } = useGeolocation({ enableHighAccuracy: true });

// Map & Layer Refs
const mapContainer = ref(null);
const map = ref(null);
const userMarker = ref(null);
const polylineLayer = ref(null);
const distanceMarker = ref(null);

// Camera Refs
const videoRef = ref(null);
const canvasRef = ref(null);
const isCameraOpen = ref(false);
const photoPreview = ref(null);
const stream = ref(null);

// Form
const form = useForm({
    latitude: null,
    longitude: null,
    photo: null,
    type: 'present',
    note: null,
    document: null,
});

// State
const isWithinRadius = ref(false);
const nearestLocation = ref(null);
const distanceToNearest = ref(null);

// Initialize Map & Geolocation
onMounted(() => {
    initMap();
    resume();
});

watch(coords, (newCoords) => {
    if (newCoords.latitude && newCoords.longitude) {
        updateUserLocation(newCoords.latitude, newCoords.longitude);
    }
});

// Keep map responsive when tabs change
watch([mainTab, activeTab], async () => {
    if (mainTab.value === 'personal' && activeTab.value !== 'permit') {
        setTimeout(() => {
            if (map.value) {
                map.value.invalidateSize();
                if (coords.value?.latitude && coords.value?.longitude) {
                    updateMapPolyline(coords.value.latitude, coords.value.longitude);
                }
            }
        }, 150);
    }
});

const initMap = () => {
    if (!mapContainer.value) return;

    const defaultLat = props.locations && props.locations.length > 0 ? props.locations[0].latitude : -7.7569;
    const defaultLng = props.locations && props.locations.length > 0 ? props.locations[0].longitude : 113.2115;

    if (!map.value) {
        map.value = L.map(mapContainer.value, {
            zoomControl: false,
        }).setView([defaultLat, defaultLng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map.value);
    }

    // Add Office/School markers & radius circles
    props.locations.forEach(loc => {
        // Red circle area
        L.circle([loc.latitude, loc.longitude], {
            color: '#ef4444',
            fillColor: '#ef4444',
            fillOpacity: 0.12,
            radius: loc.radius || 100,
            weight: 1
        }).addTo(map.value);

        // Office pin marker
        const redPin = L.divIcon({
            className: 'custom-school-pin',
            html: `<div style="background-color: #ef4444; width: 28px; height: 28px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 4px 6px rgba(0,0,0,0.3);"><div style="width: 8px; height: 8px; background: white; border-radius: 50%; transform: rotate(45deg);"></div></div>`,
            iconSize: [28, 28],
            iconAnchor: [14, 28]
        });

        L.marker([loc.latitude, loc.longitude], { icon: redPin })
            .addTo(map.value)
            .bindPopup(`<b>${loc.name}</b><br>Radius: ${loc.radius}m`);
    });

    if (coords.value?.latitude && coords.value?.longitude) {
        updateUserLocation(coords.value.latitude, coords.value.longitude);
    }
};

const updateUserLocation = (lat, lng) => {
    if (!map.value) return;
    form.latitude = lat;
    form.longitude = lng;

    const userDotIcon = L.divIcon({
        className: 'custom-user-dot',
        html: `<div style="width: 16px; height: 16px; background-color: #0284c7; border: 3px solid white; border-radius: 50%; box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.25), 0 2px 5px rgba(0,0,0,0.3);"></div>`,
        iconSize: [16, 16],
        iconAnchor: [8, 8]
    });

    if (userMarker.value) {
        userMarker.value.setLatLng([lat, lng]);
    } else {
        userMarker.value = L.marker([lat, lng], { icon: userDotIcon })
            .addTo(map.value)
            .bindPopup("Lokasi Anda");
    }

    updateMapPolyline(lat, lng);
};

const updateMapPolyline = (userLat, userLng) => {
    if (!map.value) return;

    checkProximity(userLat, userLng);

    if (nearestLocation.value) {
        const officeLat = nearestLocation.value.latitude;
        const officeLng = nearestLocation.value.longitude;

        if (polylineLayer.value) {
            map.value.removeLayer(polylineLayer.value);
        }
        if (distanceMarker.value) {
            map.value.removeLayer(distanceMarker.value);
        }

        const latlngs = [
            [officeLat, officeLng],
            [userLat, userLng]
        ];

        polylineLayer.value = L.polyline(latlngs, {
            color: '#0284c7',
            weight: 2,
            dashArray: '6, 8',
            opacity: 0.85
        }).addTo(map.value);

        const distStr = distanceToNearest.value ? `Jarak: ${distanceToNearest.value} m` : 'Menghitung...';
        const midLat = (officeLat + userLat) / 2;
        const midLng = (officeLng + userLng) / 2;

        const badgeIcon = L.divIcon({
            className: 'custom-dist-badge',
            html: `<div style="background: rgba(255, 255, 255, 0.95); border: 1px solid #cbd5e1; border-radius: 9999px; padding: 2px 8px; font-size: 10px; font-weight: 700; color: #334155; box-shadow: 0 2px 4px rgba(0,0,0,0.1); white-space: nowrap; transform: translate(-50%, -50%); pointer-events: none;">${distStr}</div>`,
            iconSize: [0, 0]
        });

        distanceMarker.value = L.marker([midLat, midLng], { icon: badgeIcon }).addTo(map.value);

        const bounds = L.latLngBounds(latlngs);
        map.value.fitBounds(bounds, { padding: [30, 30], maxZoom: 16 });
    }
};

const checkProximity = (lat, lng) => {
    let minDistance = Infinity;
    let closest = null;
    let inRange = false;

    props.locations.forEach(loc => {
        const dist = getDistanceFromLatLonInM(lat, lng, loc.latitude, loc.longitude);
        if (dist < minDistance) {
            minDistance = dist;
            closest = loc;
        }
        if (dist <= loc.radius) inRange = true;
    });

    isWithinRadius.value = inRange;
    nearestLocation.value = closest;
    distanceToNearest.value = Math.round(minDistance);
};

const refreshLocation = () => {
    isLocating.value = true;
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                isLocating.value = false;
                const { latitude, longitude } = pos.coords;
                updateUserLocation(latitude, longitude);
                if (map.value) {
                    map.value.invalidateSize();
                }
            },
            (err) => {
                isLocating.value = false;
                console.warn('Geolocation refresh error:', err);
                resume();
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    } else {
        isLocating.value = false;
        resume();
    }
};

// Haversine formula
function getDistanceFromLatLonInM(lat1, lon1, lat2, lon2) {
    const R = 6371000; 
    const dLat = deg2rad(lat2 - lat1);
    const dLon = deg2rad(lon2 - lon1);
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}
function deg2rad(deg) { return deg * (Math.PI / 180); }

// Camera methods
const startCamera = async () => {
    isCameraOpen.value = true;
    try {
        stream.value = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
        if (videoRef.value) videoRef.value.srcObject = stream.value;
    } catch (err) { 
        alert("Gagal mengakses kamera. Pastikan izin kamera telah diberikan."); 
        isCameraOpen.value = false; 
    }
};

const takePhoto = () => {
    if (!videoRef.value || !canvasRef.value) return;
    const context = canvasRef.value.getContext('2d');
    canvasRef.value.width = videoRef.value.videoWidth;
    canvasRef.value.height = videoRef.value.videoHeight;
    context.drawImage(videoRef.value, 0, 0);
    const dataUrl = canvasRef.value.toDataURL('image/jpeg', 0.8);
    photoPreview.value = dataUrl;
    form.photo = dataUrl;
    stopCamera();
};

const stopCamera = () => {
    if (stream.value) stream.value.getTracks().forEach(track => track.stop());
    isCameraOpen.value = false;
};

// Check-in submission
const submitCheckIn = (type) => {
    form.type = type;

    if (coords.value?.latitude && coords.value?.longitude) {
        form.latitude = coords.value.latitude;
        form.longitude = coords.value.longitude;
    } else {
        form.latitude = null;
        form.longitude = null;
    }
    
    if (type === 'present' && !isWithinRadius.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Di Luar Jangkauan',
            text: 'Anda berada di luar radius lokasi absensi sekolah yang diizinkan.',
            confirmButtonColor: '#00584b',
        });
        return;
    }
    if ((type === 'present' || type === 'business_trip') && !form.photo) {
        Swal.fire({
            icon: 'warning',
            title: 'Foto Wajib',
            text: 'Silakan ambil foto selfie kehadiran terlebih dahulu.',
            confirmButtonColor: '#00584b',
        });
        return;
    }
    if ((type === 'permit' || type === 'sick' || type === 'business_trip') && !form.note) {
        Swal.fire({
            icon: 'warning',
            title: 'Keterangan Wajib',
            text: 'Harap tuliskan alasan/keterangan pengajuan Anda.',
            confirmButtonColor: '#00584b',
        });
        return;
    }
    
    const labelType = type === 'sick' ? 'Sakit' : type === 'permit' ? 'Izin' : type === 'business_trip' ? 'Dinas Luar' : 'Hadir';

    form.post(route('employee.attendance.check-in'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: type === 'present' ? 'Presensi masuk berhasil dicatat.' : `Pengajuan ${labelType} berhasil dikirim dan menunggu persetujuan.`,
                confirmButtonColor: '#00584b',
            });
            photoPreview.value = null;
            form.reset();
        },
        onError: (errors) => {
            const errorMsg = Object.values(errors).flat().join('<br>') || 'Gagal mengirim pengajuan. Periksa kembali form isian.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Mengajukan',
                html: errorMsg,
                confirmButtonColor: '#00584b',
            });
        }
    });
};

// Check-out submission
const submitCheckOut = (attendanceId) => {
    Swal.fire({
        title: 'Absen Pulang?',
        text: 'Pastikan pekerjaan hari ini sudah selesai.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Pulang',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#00584b',
    }).then((result) => {
        if (result.isConfirmed) {
            form.put(route('employee.attendance.check-out', attendanceId), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sampai Jumpa!',
                        text: 'Absen pulang berhasil dicatat.',
                        confirmButtonColor: '#00584b',
                    });
                },
                onError: (errors) => {
                    const errorMsg = Object.values(errors).flat().join('<br>') || 'Gagal melakukan absen pulang.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        html: errorMsg,
                        confirmButtonColor: '#00584b',
                    });
                }
            });
        }
    });
};
</script>

<style scoped>
:deep(.leaflet-pane) { z-index: 10; }
:deep(.leaflet-top), :deep(.leaflet-bottom) { z-index: 20; }
:deep(.custom-school-pin), :deep(.custom-user-dot), :deep(.custom-dist-badge) {
    background: transparent;
    border: none;
}
</style>
