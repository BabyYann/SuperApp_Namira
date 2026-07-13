<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    testimonials: {
        type: Array,
        default: () => []
    }
});

const testimonialsList = computed(() => {
    if (props.testimonials && props.testimonials.length > 0) {
        return props.testimonials.map(t => ({
            name: t.name,
            quote: t.quote,
            photo: t.photo_path ? '/' + t.photo_path : '/images/landing/banner-1.jpg',
            role: t.role_or_title + (t.unit?.name ? ' • ' + t.unit.name : ''),
        }));
    }
    // Static Fallback
    return [
        { name: 'Ibu Ratna', quote: "Kami bangga menyekolahkan anak kami di sini. Sistem pendidikannya sangat terpadu dan guru-gurunya profesional.", photo: '/images/landing/banner-1.webp', role: 'Wali Murid • SD Namira' },
        { name: 'Bapak Budi', quote: "Karakter islami anak saya terbentuk sangat baik sejak masuk Namira. Fasilitasnya juga sangat mendukung.", photo: '/images/landing/banner-2.webp', role: 'Wali Murid • SMP Namira' },
        { name: 'Ananda', quote: "Lulusan Namira sangat berdaya saing. Bekal agama dan ilmu umum yang seimbang sangat bermanfaat.", photo: '/images/landing/banner-3.webp', role: 'Alumni' },
        { name: 'Dr. Faisal', quote: "Kurikulum Namira sangat adaptif dengan perkembangan zaman tanpa meninggalkan nilai-nilai dasar keislaman. Sangat direkomendasikan.", photo: '/images/landing/banner-1.webp', role: 'Akademisi' },
        { name: 'Bunda Sarah', quote: "Sejak TK sampai sekarang SMP, anak saya selalu di Namira. Perkembangan karakternya luar biasa.", photo: '/images/landing/banner-2.webp', role: 'Wali Murid' },
    ];
});
</script>

<template>
    <Head title="Testimoni - Yayasan Namira" />
    
    <div class="min-h-screen bg-[#f8f9fa] font-sans text-gray-900 selection:bg-[#fbbf24] selection:text-[#064e3b]">
        <!-- Navbar -->
        <header class="bg-[#064e3b] py-4 shadow-md sticky top-0 z-50">
            <div class="max-w-[1400px] mx-auto px-4 flex justify-between items-center">
                <Link href="/#testimonials" class="text-[#fbbf24] hover:text-white transition flex items-center gap-2 text-sm font-bold uppercase tracking-widest">
                    <ArrowLeftIcon class="w-4 h-4" /> Kembali ke Beranda
                </Link>
                <div class="text-white font-bold text-lg tracking-widest uppercase">
                    YAYASAN NAMIRA
                </div>
            </div>
        </header>

        <main class="max-w-[1400px] mx-auto px-4 py-16">
            <div class="text-center mb-16">
                <h1 class="text-4xl md:text-5xl font-bold text-[#064e3b] mb-4">Apa Kata Mereka?</h1>
                <p class="text-gray-500 max-w-2xl mx-auto">Kisah dan pengalaman berharga dari para orang tua, alumni, dan tokoh masyarakat tentang Yayasan Namira.</p>
                <div class="w-24 h-[2px] bg-[#fbbf24] mx-auto mt-8"></div>
            </div>

            <!-- Grid Testimoni -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div v-for="(testi, index) in testimonialsList" :key="index" class="bg-white rounded-3xl p-8 shadow-lg border border-gray-100 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 flex flex-col h-full relative">
                    <!-- Quote Mark Icon -->
                    <div class="absolute top-6 right-8 text-8xl text-gray-100 font-serif leading-none opacity-50 select-none">"</div>
                    
                    <div class="flex items-center gap-4 mb-6 relative z-10">
                        <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-[#fbbf24] shadow-md">
                            <img :src="testi.photo" class="w-full h-full object-cover" loading="lazy" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-[#064e3b] leading-tight">{{ testi.name }}</h3>
                            <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">{{ testi.role }}</p>
                        </div>
                    </div>
                    
                    <p class="text-gray-600 font-light text-lg leading-relaxed italic relative z-10 flex-grow">
                        "{{ testi.quote }}"
                    </p>
                </div>
            </div>
        </main>
    </div>
</template>
