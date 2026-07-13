<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    ArrowLeftIcon, EnvelopeIcon, BuildingLibraryIcon, IdentificationIcon, 
    AcademicCapIcon, CreditCardIcon, UserIcon, PhoneIcon, CheckIcon, PlusIcon,
    ExclamationCircleIcon, CheckCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    student: Object,
});

const activeTab = ref('personal');

const tabs = [
    { id: 'personal', label: 'Data Pribadi' },
    { id: 'academic', label: 'Akademik' },
    { id: 'parents', label: 'Orang Tua & Wali' },
    { id: 'finance', label: 'Keuangan' },
];

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric'
    });
};
</script>

<template>
    <Head :title="`Detail Siswa - ${student.full_name}`" />

    <AuthenticatedLayout>
        <div class="space-y-6 pb-12">
            <!-- Header / Breadcrumb -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">Detail Peserta Didik</h2>
                    <div class="flex items-center gap-2 text-sm text-gray-500 mt-1">
                        <Link :href="route('yayasan.dashboard')" class="hover:text-namira-teal transition-colors">Dashboard</Link>
                        <span>/</span>
                        <Link :href="route('yayasan.students.index')" class="hover:text-namira-teal transition-colors">Siswa</Link>
                        <span>/</span>
                        <span class="text-gray-900 font-medium">{{ student.full_name }}</span>
                    </div>
                </div>
                <Link :href="route('yayasan.students.index')" class="px-5 py-2.5 bg-white/80 backdrop-blur-sm border border-white/50 text-gray-600 rounded-2xl hover:bg-white hover:shadow-md font-bold text-sm transition-all active:scale-95 flex items-center gap-2">
                    <ArrowLeftIcon class="w-4 h-4" />
                    Kembali
                </Link>
            </div>

            <!-- Profile Overview Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl p-8 shadow-sm border border-white/50 flex flex-col md:flex-row gap-8 items-center md:items-start relative overflow-hidden">
                 <!-- Decorative Background -->
                 <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-bl from-namira-teal/10 to-blue-50 rounded-bl-full -mr-16 -mt-16 pointer-events-none"></div>
                 
                 <!-- Avatar -->
                 <div class="relative z-10 group">
                    <div class="w-36 h-36 rounded-full border-4 border-white shadow-xl overflow-hidden bg-gray-100 flex-shrink-0 relative">
                        <img v-if="student.photo" :src="`/storage/${student.photo}`" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 text-4xl font-bold text-gray-400">
                            {{ student.full_name.substring(0,2).toUpperCase() }}
                        </div>
                    </div>
                    <div class="absolute bottom-2 right-2 w-8 h-8 bg-green-500 border-4 border-white rounded-full shadow-sm" title="Status Aktif"></div>
                 </div>

                 <!-- Basic Info -->
                 <div class="relative z-10 flex-1 text-center md:text-left pt-2">
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-1">{{ student.full_name }}</h1>
                    <p class="text-gray-500 font-medium flex items-center justify-center md:justify-start gap-2">
                        <EnvelopeIcon class="w-4 h-4 text-namira-teal" />
                        {{ student.user?.email || 'Email belum diatur' }}
                    </p>
                    
                    <div class="flex flex-wrap justify-center md:justify-start gap-3 mt-6">
                        <div class="px-4 py-1.5 rounded-xl text-xs font-bold uppercase tracking-wide border shadow-sm" 
                             :class="student.gender === 'L' ? 'bg-blue-50 text-blue-700 border-blue-100' : 'bg-pink-50 text-pink-700 border-pink-100'">
                            {{ student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}
                        </div>
                        <div v-if="student.classroom" class="px-4 py-1.5 bg-namira-teal/10 text-namira-teal rounded-xl text-xs font-bold uppercase tracking-wide border border-namira-teal/20 flex items-center gap-1">
                            <BuildingLibraryIcon class="w-3 h-3" />
                            {{ student.classroom.name }}
                        </div>
                        <div class="px-4 py-1.5 bg-white text-gray-600 rounded-xl text-xs font-mono font-bold border border-gray-200 shadow-sm">
                            NIS: {{ student.nis || '-' }}
                        </div>
                        <div class="px-4 py-1.5 bg-white text-gray-600 rounded-xl text-xs font-mono font-bold border border-gray-200 shadow-sm">
                            NISN: {{ student.nisn || '-' }}
                        </div>
                    </div>
                 </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200/50">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button 
                        v-for="tab in tabs" 
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all duration-300"
                        :class="activeTab === tab.id ? 'border-namira-teal text-namira-teal scale-105' : 'border-transparent text-gray-400 hover:text-gray-600 hover:border-gray-300'"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 p-8 min-h-[400px]">
                
                <!-- Personal Info -->
                <div v-if="activeTab === 'personal'" class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8 animate-fade-in-up">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2 pb-2 border-b border-gray-100">
                            <div class="p-2 bg-namira-teal/10 rounded-lg text-namira-teal">
                                <IdentificationIcon class="w-5 h-5" />
                            </div>
                            Identitas Diri
                        </h3>
                        <dl class="space-y-6">
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Nama Lengkap</dt>
                                <dd class="text-base font-bold text-gray-900">{{ student.full_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Tempat, Tanggal Lahir</dt>
                                <dd class="text-base font-medium text-gray-700">
                                    {{ student.pob || '-' }}, {{ formatDate(student.dob) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Jenis Kelamin</dt>
                                <dd class="text-base font-medium text-gray-700">
                                    {{ student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                </dd>
                            </div>
                             <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Alamat Lengkap</dt>
                                <dd class="text-base font-medium text-gray-700 leading-relaxed bg-gray-50 p-4 rounded-xl border border-gray-100">
                                    {{ student.address || '-' }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Academic Info -->
                <div v-if="activeTab === 'academic'" class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8 animate-fade-in-up">
                     <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2 pb-2 border-b border-gray-100">
                             <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                                 <AcademicCapIcon class="w-5 h-5" />
                            </div>
                            Data Akademik
                        </h3>
                         <dl class="space-y-6">
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Nomor Induk Siswa (NIS)</dt>
                                <dd class="text-base font-mono font-bold text-gray-700 bg-gray-50 px-3 py-1.5 rounded-lg inline-block border border-gray-200 shadow-sm">{{ student.nis || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">NISN</dt>
                                <dd class="text-base font-mono font-bold text-gray-700 bg-gray-50 px-3 py-1.5 rounded-lg inline-block border border-gray-200 shadow-sm">{{ student.nisn || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Nomor Virtual Account (VA)</dt>
                                <dd class="text-base font-mono font-bold text-namira-teal bg-teal-50 px-3 py-1.5 rounded-lg inline-block border border-teal-100 shadow-sm flex items-center gap-2 w-fit">
                                    <CreditCardIcon class="w-4 h-4" />
                                    {{ student.va_number || 'Belum Digenerate' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Kelas Saat Ini</dt>
                                <dd class="text-lg font-bold text-namira-teal">
                                    {{ student.classroom?.name || 'Belum Masuk Kelas' }}
                                </dd>
                            </div>
                             <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Tahun Masuk</dt>
                                <dd class="text-base font-medium text-gray-700">
                                    {{ new Date(student.created_at).getFullYear() }}
                                </dd>
                            </div>
                        </dl>
                     </div>
                </div>

                <!-- Parents Info -->
                <div v-if="activeTab === 'parents'" class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8 animate-fade-in-up">
                    <!-- Father / Parent -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2 pb-2 border-b border-gray-100">
                             <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                                 <UserIcon class="w-5 h-5" />
                            </div>
                            Orang Tua
                        </h3>
                        <dl class="space-y-6">
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Nama Orang Tua</dt>
                                <dd class="text-base font-bold text-gray-900">{{ student.parent_name || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Nomor Telepon / WA</dt>
                                <dd class="text-base font-mono font-medium">
                                     <a v-if="student.parent_phone" :href="`https://wa.me/${student.parent_phone}`" target="_blank" class="text-namira-teal hover:underline flex items-center gap-2 bg-teal-50 px-3 py-1.5 rounded-lg w-fit border border-teal-100">
                                        <PhoneIcon class="w-4 h-4" />
                                        {{ student.parent_phone }}
                                     </a>
                                     <span v-else class="text-gray-400">-</span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Guardian -->
                    <div v-if="student.guardian_name">
                        <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2 pb-2 border-b border-gray-100">
                             <div class="p-2 bg-amber-50 rounded-lg text-amber-600">
                                 <UserIcon class="w-5 h-5" />
                            </div>
                            Wali
                        </h3>
                         <dl class="space-y-6">
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Nama Wali</dt>
                                <dd class="text-base font-bold text-gray-900">{{ student.guardian_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Nomor Telepon / WA</dt>
                                <dd class="text-base font-mono font-medium">
                                     <a v-if="student.guardian_phone" :href="`https://wa.me/${student.guardian_phone}`" target="_blank" class="text-namira-teal hover:underline flex items-center gap-2 bg-teal-50 px-3 py-1.5 rounded-lg w-fit border border-teal-100">
                                        <PhoneIcon class="w-4 h-4" />
                                        {{ student.guardian_phone }}
                                     </a>
                                     <span v-else class="text-gray-400">-</span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Finance Tab -->
                <div v-if="activeTab === 'finance'" class="space-y-8 animate-fade-in-up">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-red-50 p-6 rounded-2xl border border-red-100 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-red-400 uppercase tracking-wider mb-1">Total Tunggakan</p>
                                <h3 class="text-2xl font-extrabold text-red-600">Rp {{ student.total_arrears?.toLocaleString('id-ID') || 0 }}</h3>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-red-500">
                                <ExclamationCircleIcon class="w-6 h-6" />
                            </div>
                        </div>
                        <div class="bg-green-50 p-6 rounded-2xl border border-green-100 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-green-400 uppercase tracking-wider mb-1">Total Terbayar (All Time)</p>
                                <h3 class="text-2xl font-extrabold text-green-600">Rp {{ student.total_paid?.toLocaleString('id-ID') || 0 }}</h3>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-500">
                                <CheckCircleIcon class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Unpaid Bills -->
                        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                                <h4 class="font-bold text-gray-800">Tagihan Belum Lunas</h4>
                                <span class="px-2 py-1 bg-red-100 text-red-600 rounded text-xs font-bold">{{ student.bills?.length || 0 }} Item</span>
                            </div>
                            <div class="divide-y divide-gray-50">
                                <div v-if="!student.bills?.length" class="p-8 text-center text-gray-400 text-sm">
                                    Tidak ada tagihan tertunggak. Alhamdulillah!
                                </div>
                                <div v-for="bill in student.bills" :key="bill.id" class="p-4 hover:bg-gray-50 transition-colors flex justify-between items-center">
                                    <div>
                                        <p class="font-bold text-gray-800 text-sm">{{ bill.finance_type?.name || bill.description }}</p>
                                        <p class="text-xs text-gray-500">{{ formatDate(bill.billing_date) }} • Jatuh Tempo: {{ formatDate(bill.due_date) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-red-600 text-sm">Rp {{ (bill.final_amount - bill.paid_amount).toLocaleString('id-ID') }}</p>
                                        <span class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded" :class="bill.paid_amount > 0 ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-500'">
                                            {{ bill.paid_amount > 0 ? 'Partial' : 'Unpaid' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Transactions -->
                        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                                <h4 class="font-bold text-gray-800">Riwayat Pembayaran</h4>
                                <span class="px-2 py-1 bg-teal-100 text-teal-600 rounded text-xs font-bold">Terakhir 10</span>
                            </div>
                            <div class="divide-y divide-gray-50">
                                <div v-if="!student.transactions?.length" class="p-8 text-center text-gray-400 text-sm">
                                    Belum ada riwayat transaksi.
                                </div>
                                <div v-for="trx in student.transactions" :key="trx.id" class="p-4 hover:bg-gray-50 transition-colors flex justify-between items-center">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-teal-50 flex items-center justify-center text-teal-600">
                                            <PlusIcon class="w-4 h-4" />
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-800 text-sm">{{ trx.transaction_code }}</p>
                                            <p class="text-xs text-gray-500">{{ formatDate(trx.transaction_date) }} • {{ trx.payment_method }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-teal-600 text-sm">+ Rp {{ trx.amount.toLocaleString('id-ID') }}</p>
                                        <span class="text-[10px] text-gray-400 uppercase">Sukses</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.4s ease-out forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
