<template>
    <Head title="Presensi Pegawai" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                Presensi Pegawai
            </h2>
        </template>

        <div class="py-4 md:py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5 md:space-y-6">
            
            <!-- 1A. DESKTOP VIEW STATUS CARD (Unchanged Desktop Layout) -->
            <div class="hidden md:block bg-white/80 backdrop-blur-xl overflow-hidden shadow-sm rounded-3xl border border-white/50 p-6">
                <div class="flex justify-between items-center gap-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Halo, {{ $page.props.auth.user.name }}! 👋</h3>
                        <p class="text-gray-500 font-medium">{{ new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <div v-if="todayAttendance" class="px-4 py-2 rounded-2xl font-bold text-sm border border-white/50 shadow-sm"
                            :class="todayAttendance.approval_status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'">
                            {{ todayAttendance.check_in_time ? 'Sudah Masuk: ' + todayAttendance.check_in_time : 'Sudah Mengajukan' }}
                        </div>
                        <div v-else class="px-4 py-2 bg-gray-100 text-gray-500 rounded-2xl font-bold text-sm border border-gray-200">
                            Belum Absen Masuk
                        </div>

                        <div v-if="todayAttendance && todayAttendance.check_out_time" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-2xl font-bold text-sm border border-blue-200">
                            Sudah Pulang: {{ todayAttendance.check_out_time }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- 1B. MOBILE VIEW STATUS CARD (Executive Deep Emerald Mobile Layout) -->
            <div class="block md:hidden bg-[#064e3b] text-white rounded-3xl p-5 shadow-xl border border-emerald-800/80 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-32 h-32 rounded-full bg-teal-500/10 blur-xl pointer-events-none"></div>

                <div class="flex flex-col justify-between items-start gap-4 relative z-10">
                    <div>
                        <span class="text-[10px] font-black tracking-widest text-teal-400 uppercase">Presensi Pegawai</span>
                        <h3 class="text-xl font-extrabold text-white mt-0.5">Halo, {{ $page.props.auth.user.name }}</h3>
                        <p class="text-xs text-slate-400 font-medium mt-1">
                            {{ new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                        </p>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-2.5 w-full">
                        <div v-if="todayAttendance" class="px-3.5 py-2 rounded-2xl font-black text-xs border shadow-sm flex items-center gap-2"
                            :class="todayAttendance.approval_status === 'pending' ? 'bg-amber-950/80 text-amber-300 border-amber-800/60' : 'bg-emerald-950/80 text-emerald-300 border-emerald-800/60'">
                            <span class="w-2 h-2 rounded-full" :class="todayAttendance.approval_status === 'pending' ? 'bg-amber-400' : 'bg-emerald-400 animate-pulse'"></span>
                            <span>{{ todayAttendance.check_in_time ? 'Sudah Masuk: ' + todayAttendance.check_in_time : 'Sudah Mengajukan' }}</span>
                        </div>
                        <div v-else class="px-3.5 py-2 bg-slate-800 text-slate-300 rounded-2xl font-bold text-xs border border-slate-700/80 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span>Belum Absen Masuk</span>
                        </div>

                        <div v-if="todayAttendance && todayAttendance.check_out_time" class="px-3.5 py-2 bg-sky-950/80 text-sky-300 rounded-2xl font-bold text-xs border border-sky-800/60 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-sky-400"></span>
                            <span>Sudah Pulang: {{ todayAttendance.check_out_time }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2A. DESKTOP TABS NAVIGATION -->
            <div v-if="!todayAttendance" class="hidden md:flex space-x-2 bg-white/50 backdrop-blur-sm border border-white/50 p-1.5 rounded-2xl w-fit shadow-sm">
                <button @click="activeTab = 'present'" :class="activeTab === 'present' ? 'bg-namira-teal text-white shadow-md shadow-namira-teal/30' : 'text-gray-500 hover:text-gray-900 hover:bg-white/60'" class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all">WFO (Hadir)</button>
                <button @click="activeTab = 'business_trip'" :class="activeTab === 'business_trip' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-gray-500 hover:text-gray-900 hover:bg-white/60'" class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all">Dinas Luar</button>
                <button @click="activeTab = 'permit'" :class="activeTab === 'permit' ? 'bg-purple-600 text-white shadow-md shadow-purple-600/30' : 'text-gray-500 hover:text-gray-900 hover:bg-white/60'" class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all">Izin / Sakit</button>
            </div>

            <!-- 2B. MOBILE TABS NAVIGATION -->
            <div v-if="!todayAttendance" class="flex md:hidden bg-slate-200/80 p-1.5 rounded-2xl space-x-1.5 w-full shadow-inner">
                <button 
                    @click="activeTab = 'present'" 
                    :class="activeTab === 'present' ? 'bg-slate-900 text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'" 
                    class="px-4 py-2.5 rounded-xl text-xs font-extrabold transition-all flex-1 text-center"
                >
                    WFO (Hadir)
                </button>
                <button 
                    @click="activeTab = 'business_trip'" 
                    :class="activeTab === 'business_trip' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'" 
                    class="px-4 py-2.5 rounded-xl text-xs font-extrabold transition-all flex-1 text-center"
                >
                    Dinas Luar
                </button>
                <button 
                    @click="activeTab = 'permit'" 
                    :class="activeTab === 'permit' ? 'bg-purple-600 text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'" 
                    class="px-4 py-2.5 rounded-xl text-xs font-extrabold transition-all flex-1 text-center"
                >
                    Izin / Sakit
                </button>
            </div>

            <!-- Content Area -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">
                
                <!-- Helper/Info Section (Map or Form Info) -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm overflow-hidden border border-white/50 min-h-[350px] md:min-h-[400px] relative z-0 flex flex-col">
                    
                    <!-- WFO / Business Trip: Show Map -->
                    <div v-show="activeTab !== 'permit'" class="h-full w-full relative flex-1 min-h-[320px]">
                        <div id="map" ref="mapContainer" class="w-full h-full min-h-[320px]"></div>
                        <!-- Overlay Status -->
                        <div class="absolute bottom-3 left-3 right-3 md:bottom-4 md:left-4 md:right-4 bg-slate-900/95 text-white md:bg-white/90 md:text-gray-800 backdrop-blur-md p-3.5 md:p-4 rounded-2xl shadow-lg z-[1000] border border-slate-800 md:border-white/50">
                             <div class="flex items-center gap-3">
                                <div :class="isWithinRadius ? 'bg-emerald-400 md:bg-green-500 shadow-[0_0_10px_rgba(52,211,153,0.8)]' : 'bg-rose-500 shadow-[0_0_10px_rgba(239,68,68,0.5)]'" class="w-3 h-3 rounded-full animate-pulse"></div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[9px] md:text-[10px] text-slate-400 md:text-gray-400 font-bold uppercase tracking-wider mb-0.5">Status Lokasi</p>
                                    <p v-if="isWithinRadius" class="text-emerald-300 md:text-green-700 font-bold text-xs md:text-sm truncate">
                                        Di dalam jangkauan {{ nearestLocation?.name }}
                                    </p>
                                    <p v-else-if="activeTab === 'business_trip'" class="text-sky-300 md:text-blue-600 font-bold text-xs md:text-sm truncate">
                                        Lokasi Bebas (Dinas Luar)
                                    </p>
                                    <p v-else class="text-rose-300 md:text-red-600 font-bold text-xs md:text-sm truncate">
                                        Di luar jangkauan (Jarak: {{ distanceToNearest ? distanceToNearest + 'm' : 'Menghitung...' }})
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Permit: Info Upload -->
                    <div v-if="activeTab === 'permit'" class="h-full w-full flex flex-col items-center justify-center p-8 text-center bg-purple-50/50 backdrop-blur-sm">
                        <ClipboardDocumentCheckIcon class="w-20 h-20 md:w-24 md:h-24 text-purple-300 md:text-purple-200 mb-3 md:mb-4" />
                        <h3 class="text-lg md:text-xl font-bold text-purple-900 md:text-purple-800 mb-1 md:mb-2">Form Pengajuan Izin/Sakit</h3>
                        <p class="text-xs md:text-sm text-slate-500 md:text-gray-600 max-w-xs">Pastikan melampirkan bukti surat dokter atau dokumen pendukung lainnya.</p>
                    </div>
                </div>

                <!-- Input Action Section -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm p-6 md:p-8 border border-white/50 flex flex-col justify-center items-center text-center space-y-5 md:space-y-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-gray-100 to-transparent rounded-bl-[100px] opacity-50 -z-10"></div>
                    
                    <!-- Camera for WFO/Dinas -->
                    <div v-show="activeTab !== 'permit' && (!todayAttendance)" class="w-full">
                         <div v-if="isCameraOpen || photoPreview" class="relative w-full max-w-xs mx-auto aspect-[3/4] bg-slate-900 md:bg-gray-100 rounded-3xl overflow-hidden border-4 border-slate-900 md:border-white shadow-xl flex items-center justify-center mb-2 md:mb-4">
                            <video v-show="isCameraOpen" ref="videoRef" autoplay playsinline class="w-full h-full object-cover"></video>
                            <img v-if="photoPreview && !isCameraOpen" :src="photoPreview" class="w-full h-full object-cover" />
                            <canvas ref="canvasRef" class="hidden"></canvas>
                            
                            <button v-if="isCameraOpen" @click="takePhoto" class="absolute bottom-5 md:bottom-6 w-16 h-16 bg-white rounded-full border-4 border-slate-200 md:border-gray-100 flex items-center justify-center shadow-2xl active:scale-95 transition-transform hover:scale-105">
                                <div class="w-11 h-11 md:w-12 md:h-12 bg-rose-600 md:bg-red-500 rounded-full border-2 border-white"></div>
                            </button>
                        </div>
                    </div>

                    <!-- Form Inputs base on Tab -->
                    <div v-if="!todayAttendance" class="w-full max-w-xs space-y-4">
                        
                        <!-- Note Input (All non-WFO requires note) -->
                        <div v-if="activeTab !== 'present'" class="text-left">
                            <label class="block text-[11px] md:text-xs font-bold text-slate-500 md:text-gray-400 uppercase tracking-wider mb-1">Keterangan / Alasan</label>
                            <textarea v-model="form.note" rows="3" class="w-full bg-slate-50 md:bg-white/50 backdrop-blur-sm border-slate-200 md:border-white/50 rounded-2xl shadow-sm focus:border-namira-teal focus:ring-namira-teal resize-none p-3" placeholder="Jelaskan detail kegiatan..."></textarea>
                        </div>

                        <!-- Permit File Upload -->
                        <div v-if="activeTab === 'permit'" class="text-left space-y-3">
                             <div>
                                 <label class="block text-[11px] md:text-xs font-bold text-slate-500 md:text-gray-400 uppercase tracking-wider mb-1">Upload Bukti (Surat/Foto)</label>
                                 <input type="file" @change="e => form.document = e.target.files[0]" class="block w-full text-xs md:text-sm text-slate-500 md:text-gray-500
                                    file:mr-3 md:file:mr-4 file:py-2 md:file:py-2.5 file:px-3 md:file:px-4
                                    file:rounded-xl file:border-0
                                    file:text-xs file:font-bold
                                    file:bg-purple-100 md:file:bg-purple-50 file:text-purple-800 md:file:text-purple-700
                                    hover:file:bg-purple-200 md:hover:file:bg-purple-100
                                    transition-all
                                  "/>
                             </div>

                             <div class="flex gap-4 text-left bg-slate-50 md:bg-gray-50/50 p-3 rounded-2xl border border-slate-200 md:border-white/50">
                                <label class="flex items-center gap-2 cursor-pointer">
                                     <input type="radio" value="permit" v-model="permitType" class="text-purple-600 focus:ring-purple-500">
                                     <span class="text-xs md:text-sm font-bold text-slate-700 md:text-gray-700">Izin</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                     <input type="radio" value="sick" v-model="permitType" class="text-purple-600 focus:ring-purple-500">
                                     <span class="text-xs md:text-sm font-bold text-slate-700 md:text-gray-700">Sakit</span>
                                </label>
                             </div>
                        </div>

                        <!-- Submit Buttons -->
                        
                        <!-- WFO Button -->
                        <div v-if="activeTab === 'present'">
                            <button v-if="!photoPreview" @click="startCamera" :disabled="!isWithinRadius" :class="!isWithinRadius ? 'opacity-50 cursor-not-allowed bg-slate-300 md:bg-namira-teal text-slate-500 md:text-white' : 'bg-slate-900 md:bg-namira-teal hover:bg-slate-800 text-white shadow-lg md:shadow-namira-teal/30 active:scale-95 md:hover:-translate-y-1 md:hover:shadow-xl'" class="w-full py-3.5 md:py-4 rounded-2xl font-bold text-sm md:text-lg flex items-center justify-center gap-2 transition-all">
                                <CameraIcon class="h-5 w-5 md:h-6 md:w-6 text-teal-400 md:text-white" />
                                Ambil Foto & Absen
                            </button>
                            <div v-else class="space-y-2 md:space-y-3">
                                <button @click="submitCheckIn('present')" :disabled="form.processing" class="w-full py-3.5 md:py-4 bg-emerald-600 md:bg-green-600 hover:bg-emerald-700 text-white rounded-2xl font-bold text-sm md:text-lg shadow-md md:shadow-green-600/30 transition-all active:scale-95 md:hover:scale-105">
                                    {{ form.processing ? 'Mengirim...' : 'Konfirmasi Hadir' }}
                                </button>
                                <button @click="photoPreview = null; startCamera()" class="text-slate-500 text-xs md:text-sm font-bold hover:text-teal-700 transition-colors py-1">Foto Ulang</button>
                            </div>
                        </div>

                        <!-- Business Trip Button -->
                        <div v-if="activeTab === 'business_trip'">
                             <button v-if="!photoPreview" @click="startCamera" class="w-full py-3.5 md:py-4 bg-blue-600 text-white rounded-2xl font-bold text-sm md:text-lg shadow-md md:shadow-blue-600/30 flex items-center justify-center gap-2 transition-all active:scale-95 md:hover:-translate-y-1 md:hover:shadow-xl">
                                 <CameraIcon class="h-5 w-5 md:h-6 md:w-6" />
                                 Foto Bukti Dinas
                             </button>
                             <div v-else class="space-y-2 md:space-y-3">
                                <button @click="submitCheckIn('business_trip')" :disabled="form.processing || !form.note" class="w-full py-3.5 md:py-4 bg-blue-700 text-white rounded-2xl font-bold text-sm md:text-lg shadow-md md:shadow-blue-700/30 transition-all active:scale-95 md:hover:scale-105 disabled:opacity-50">
                                    {{ form.processing ? 'Mengirim...' : 'Konfirmasi Dinas Luar' }}
                                </button>
                                <button @click="photoPreview = null; startCamera()" class="text-slate-500 text-xs md:text-sm font-bold hover:text-blue-600 transition-colors py-1">Foto Ulang</button>
                             </div>
                        </div>

                        <!-- Permit Button -->
                        <div v-if="activeTab === 'permit'">
                             <button @click="submitCheckIn(permitType)" :disabled="form.processing || !form.note || !form.document" class="w-full py-3.5 md:py-4 bg-purple-600 text-white rounded-2xl font-bold text-sm md:text-lg shadow-md md:shadow-purple-600/30 transition-all active:scale-95 md:hover:scale-105 disabled:opacity-50">
                                {{ form.processing ? 'Mengirim...' : 'Ajukan Izin / Sakit' }}
                            </button>
                        </div>

                    </div>

                    <!-- Check Out Button (Showing logic when already checked in) -->
                    <div v-else-if="todayAttendance && !todayAttendance.check_out_time && (todayAttendance.status === 'present' || todayAttendance.status === 'business_trip' || todayAttendance.status === 'late')" class="w-full max-w-xs space-y-2">
                          <button 
                            @click="submitCheckOut(todayAttendance.id)"
                            :disabled="!isWithinRadius && todayAttendance.status !== 'business_trip'"
                            class="w-full py-3.5 md:py-4 bg-rose-600 md:bg-orange-500 text-white rounded-2xl font-bold text-sm md:text-lg shadow-md md:shadow-orange-500/30 transition-all active:scale-95 md:hover:scale-105 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Mengirim...' : 'Absen Pulang Sekarang' }}
                        </button>
                        <p v-if="todayAttendance.status !== 'business_trip' && !isWithinRadius" class="text-xs text-rose-600 md:text-red-500 font-bold">Harus berada di lokasi kantor untuk Absen Pulang</p>
                    </div>

                    <div v-else-if="todayAttendance" class="p-5 md:p-6 bg-emerald-50 md:bg-green-50 text-emerald-800 md:text-green-700 rounded-2xl border border-emerald-200 md:border-green-200 font-bold flex flex-col items-center w-full">
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-emerald-100 md:bg-green-100 text-emerald-700 rounded-full flex items-center justify-center mb-2">
                            <CheckIcon class="w-5 h-5 md:w-6 md:h-6 stroke-[2.5]" />
                        </div>
                        <span class="text-xs md:text-base">Absensi Hari Ini Telah Selesai</span>
                    </div>

                </div>
            </div>

            <!-- Personal Calendar -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                     <div>
                        <h3 class="font-bold text-gray-800 text-lg">Kalender Absensi Saya</h3>
                        <p class="text-sm text-gray-500 font-medium">{{ getMonthName(currentMonth) }} {{ currentYear }}</p>
                    </div>
                    <div class="flex gap-2">
                        <!-- Navigation Arrows could go here later -->
                    </div>
                </div>
                
                <div class="p-6">
                    <!-- Calendar Grid -->
                    <div class="grid grid-cols-7 text-center mb-2">
                         <div v-for="day in ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jumat', 'Sab']" :key="day" class="text-xs font-bold text-gray-400 uppercase py-2">
                            {{ day }}
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-7 gap-2">
                        <!-- Empty Cells for start offset -->
                        <div v-for="n in firstDayOffset" :key="'empty-'+n" class="p-2"></div>

                        <!-- Days -->
                        <div v-for="day in daysInMonth" :key="day" 
                            class="relative min-h-[80px] border rounded-xl p-2 flex flex-col items-start gap-1 transition-all cursor-pointer hover:shadow-md"
                            :class="[
                                getDayData(day) ? 'bg-white border-gray-200' : 'bg-gray-50/50 border-transparent',
                                isToday(day) ? 'ring-2 ring-namira-teal border-transparent' : ''
                            ]"
                            @click="showDayDetail(day)"
                        >
                            <span class="text-xs font-bold" :class="isToday(day) ? 'text-namira-teal' : 'text-gray-700'">{{ day }}</span>
                            
                            <!-- Status Badges -->
                            <div v-if="getDayData(day)" class="w-full flex flex-col gap-1 items-start">
                                <div class="w-full h-1.5 rounded-full" :class="getStatusColor(getDayData(day).status)"></div>
                                <div class="text-[10px] font-bold text-gray-600 truncate w-full text-left">
                                     {{ getDayData(day).check_in_time ? getDayData(day).check_in_time.substring(0,5) : getDayData(day).status }}
                                </div>
                                <div v-if="getDayData(day).late_minutes > 0" class="text-[9px] text-rose-600 font-bold">
                                    +{{ getDayData(day).late_minutes }}m
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail Modal -->
             <div v-if="selectedDayData" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="selectedDayData = null">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden animate-fade-in-up">
                    <div class="relative h-48 bg-gray-100 flex items-center justify-center">
                        <img v-if="selectedDayData.check_in_photo" :src="`/storage/${selectedDayData.check_in_photo}`" class="w-full h-full object-cover">
                         <span v-else class="text-gray-400 text-sm font-bold">Tidak ada foto</span>
                         <button @click="selectedDayData = null" class="absolute top-2 right-2 bg-black/30 text-white rounded-full p-1 hover:bg-black/50 transition-colors">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-center">
                            <h3 class="text-xl font-bold text-gray-900">Detail Absensi</h3>
                            <span :class="['px-2 py-1 rounded-lg text-xs font-bold uppercase', getStatusBg(selectedDayData.status)]">
                                {{ selectedDayData.status }}
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                <p class="text-xs text-gray-500 font-bold uppercase mb-1">Masuk</p>
                                <p class="text-lg font-mono font-bold text-gray-800">{{ selectedDayData.check_in_time || '--:--' }}</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                <p class="text-xs text-gray-500 font-bold uppercase mb-1">Pulang</p>
                                <p class="text-lg font-mono font-bold text-gray-800">{{ selectedDayData.check_out_time || '--:--' }}</p>
                            </div>
                        </div>

                         <div v-if="selectedDayData.late_minutes > 0" class="flex items-start gap-2 text-rose-600 bg-rose-50 p-3 rounded-xl border border-rose-100">
                            <ClockIcon class="w-5 h-5 flex-shrink-0" />
                            <div>
                                <p class="font-bold text-sm">Terlambat {{ selectedDayData.late_minutes }} Menit</p>
                                <p class="text-xs text-rose-500">Mohon lebih disiplin lagi ya!</p>
                            </div>
                        </div>

                        <div v-if="selectedDayData.note" class="text-sm text-gray-600 bg-gray-50 p-3 rounded-xl border border-gray-100 italic">
                            "{{ selectedDayData.note }}"
                        </div>
                    </div>
                </div>
             </div>

            <!-- History Table (Condensed) -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50 text-gray-800 font-bold uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3">Tanggal</th>
                                <th class="px-6 py-3">Jenis</th>
                                <th class="px-6 py-3">Masuk</th>
                                <th class="px-6 py-3">Pulang</th>
                                <th class="px-6 py-3">Approval</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="log in history" :key="log.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4">{{ log.date }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded text-xs font-bold uppercase"
                                        :class="{
                                            'bg-green-100 text-green-700': log.status === 'present',
                                            'bg-amber-100 text-amber-700': log.status === 'late',
                                            'bg-blue-100 text-blue-700': log.status === 'business_trip',
                                            'bg-purple-100 text-purple-700': log.status === 'sick' || log.status === 'permit'
                                        }"
                                    >
                                        {{ log.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono">{{ log.check_in_time || '-' }}</td>
                                <td class="px-6 py-4 font-mono">{{ log.check_out_time || '-' }}</td>
                                <td class="px-6 py-4">
                                     <span v-if="log.approval_status === 'approved'" class="text-green-600 font-bold flex items-center gap-1">
                                         <CheckCircleIcon class="w-4 h-4" />
                                         Disetujui
                                     </span>
                                     <span v-else-if="log.approval_status === 'pending'" class="text-yellow-600 font-bold flex items-center gap-1">
                                         <ClockIcon class="w-4 h-4" />
                                         Menunggu
                                     </span>
                                     <span v-else-if="log.approval_status === 'rejected'" class="text-red-600 font-bold flex items-center gap-1">
                                          <XCircleIcon class="w-4 h-4" />
                                          Ditolak
                                     </span>
                                    <span v-else class="text-gray-400 font-medium">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import { useGeolocation } from '@vueuse/core';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { 
    ClipboardDocumentCheckIcon, CameraIcon, CheckIcon, XMarkIcon, ClockIcon, 
    CheckCircleIcon, XCircleIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    todayAttendance: Object,
    history: Array,
    locations: Array,
    calendarData: Object,
    currentMonth: Number,
    currentYear: Number,
});

const activeTab = ref('present'); // present, business_trip, permit
const permitType = ref('permit'); // permit, sick
const selectedDayData = ref(null);

// Get number of days in current month
const daysInMonth = computed(() => {
    return new Date(props.currentYear, props.currentMonth, 0).getDate();
});

// Get day of week of the 1st of the month (0=Sun, 6=Sat)
const firstDayOffset = computed(() => {
    return new Date(props.currentYear, props.currentMonth - 1, 1).getDay();
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

const getMonthName = (month) => {
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return months[month - 1];
};

const getStatusColor = (status) => {
    const map = {
        'present': 'bg-emerald-500', 
        'late': 'bg-rose-500', 
        'business_trip': 'bg-indigo-500', 
        'sick': 'bg-blue-500', 
        'permit': 'bg-amber-500',
    };
    return map[status] || 'bg-gray-300';
};

const getStatusBg = (status) => {
    const map = {
        'present': 'bg-emerald-100 text-emerald-700', 
        'late': 'bg-rose-100 text-rose-700', 
        'business_trip': 'bg-indigo-100 text-indigo-700', 
        'sick': 'bg-blue-100 text-blue-700', 
        'permit': 'bg-amber-100 text-amber-700',
    };
    return map[status] || 'bg-gray-100 text-gray-700';
};

// Geolocation
const { coords, resume } = useGeolocation({ enableHighAccuracy: true });

// Map Refs
const mapContainer = ref(null);
const map = ref(null);
const userMarker = ref(null);
const locationCircles = ref([]);

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

// Initialize Map
onMounted(() => {
    // Only init map if not Permit tab (though mounted runs once, so just init)
    initMap();
    resume();
});

watch(coords, (newCoords) => {
    if (newCoords.latitude && newCoords.longitude) {
        updateUserLocation(newCoords.latitude, newCoords.longitude);
        checkProximity(newCoords.latitude, newCoords.longitude);
    }
});

const initMap = () => {
    if (!mapContainer.value) return;
    const defaultLat = -6.200000; 
    const defaultLng = 106.816666;
    map.value = L.map(mapContainer.value).setView([defaultLat, defaultLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map.value);

    // Add Circles
    props.locations.forEach(loc => {
        L.circle([loc.latitude, loc.longitude], {
            color: '#14b8a6', fillColor: '#14b8a6', fillOpacity: 0.2, radius: loc.radius
        }).addTo(map.value)
        .bindPopup(`<b>${loc.name}</b><br>Radius: ${loc.radius}m`);
    });
};

const updateUserLocation = (lat, lng) => {
    if (!map.value) return;
    form.latitude = lat;
    form.longitude = lng;

    if (userMarker.value) userMarker.value.setLatLng([lat, lng]);
    else userMarker.value = L.marker([lat, lng]).addTo(map.value).bindPopup("Lokasi Anda").openPopup();
    
    // Auto center only initially or if needed?
    // map.value.setView([lat, lng]); 
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

// Haversine
function getDistanceFromLatLonInM(lat1, lon1, lat2, lon2) {
    var R = 6371000; 
    var dLat = deg2rad(lat2 - lat1);
    var dLon = deg2rad(lon2 - lon1);
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}
function deg2rad(deg) { return deg * (Math.PI / 180); }

// Camera
const startCamera = async () => {
    isCameraOpen.value = true;
    try {
        stream.value = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
        if (videoRef.value) videoRef.value.srcObject = stream.value;
    } catch (err) { alert("Gagal akses kamera."); isCameraOpen.value = false; }
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

const submitCheckIn = (type) => {
    form.type = type;
    
    // Validation Logic
    if (type === 'present' && !isWithinRadius.value) {
        alert("Anda di luar jangkauan."); return;
    }
    if ((type === 'present' || type === 'business_trip') && !form.photo) {
        alert("Wajib ambil foto."); return;
    }
    
    form.post(route('employee.attendance.check-in'), {
        onSuccess: () => {
            photoPreview.value = null;
            form.reset();
        }
    });
};

const submitCheckOut = (attendanceId) => {
    if (!confirm("Absen Pulang sekarang?")) return;
    form.put(route('employee.attendance.check-out', attendanceId));
};
</script>
<style scoped>
:deep(.leaflet-pane) { z-index: 10; }
:deep(.leaflet-top), :deep(.leaflet-bottom) { z-index: 20; }
</style>
