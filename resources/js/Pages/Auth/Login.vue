<script setup>
import InputError from '@/Components/InputError.vue';
import NamiraLoader from '@/Components/NamiraLoader.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import gsap from 'gsap';
import { Motion, AnimatePresence } from 'motion-v';
import {
    EnvelopeIcon,
    LockClosedIcon,
    EyeIcon,
    EyeSlashIcon,
    IdentificationIcon,
    PhoneIcon,
    ArrowLeftIcon,
    ArrowRightIcon,
    AcademicCapIcon,
    UserGroupIcon,
    BookOpenIcon,
    ChatBubbleLeftRightIcon,
    MapPinIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
        default: true,
    },
    status: {
        type: String,
        default: '',
    },
    campusSlides: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    login: '',
    password: '',
    remember: true,
});

const showPassword = ref(false);

const isPhoneMode = computed(() => /^(\+?62|08)\d+$/.test(form.login.trim()));
const isNisMode = computed(() => /^\d{4,8}$/.test(form.login.trim()) && !isPhoneMode.value);

const detectedMode = computed(() => {
    if (isPhoneMode.value) {
        return {
            key: 'phone',
            label: 'Mode WhatsApp Orang Tua',
            badgeClass: 'bg-[#E6F7F1] text-[#007468] border-[#009688]/25',
        };
    }
    if (isNisMode.value) {
        return {
            key: 'nis',
            label: 'Mode NIS Siswa',
            badgeClass: 'bg-sky-50 text-sky-700 border-sky-200',
        };
    }
    return null;
});

const slides = computed(() => {
    if (props.campusSlides && props.campusSlides.length > 0) {
        return props.campusSlides;
    }
    return [
        { title: 'SD Namira Kraksaan', url: '/images/landing/banner-1.webp' },
        { title: 'SMP Namira Kota Probolinggo', url: '/images/landing/photo-smp.webp' },
        { title: 'TK, KB & Daycare Namira', url: '/images/landing/banner-3.webp' },
    ];
});

const activeSlide = ref(0);
let slideTimer = null;

const adminWaHref =
    'https://wa.me/6282332922521?text=' +
    encodeURIComponent("Assalamu'alaikum Admin Yayasan Namira, saya membutuhkan bantuan untuk masuk ke Portal Namira.");

const portalRoles = [
    {
        icon: AcademicCapIcon,
        title: 'Guru & Staf',
        desc: 'Jurnal kelas, presensi & akademik',
    },
    {
        icon: UserGroupIcon,
        title: 'Orang Tua',
        desc: 'SPMB, tagihan & perkembangan anak',
    },
    {
        icon: BookOpenIcon,
        title: 'Siswa',
        desc: 'Jadwal belajar, tugas & Metode Ummi',
    },
];

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const pageRoot = ref(null);
let gsapCtx = null;

onMounted(() => {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (slides.value.length > 1) {
        slideTimer = setInterval(() => {
            activeSlide.value = (activeSlide.value + 1) % slides.value.length;
        }, 5500);
    }

    if (!prefersReduced && pageRoot.value) {
        gsapCtx = gsap.context(() => {
            gsap.from('.login-stagger', {
                y: 14,
                opacity: 0,
                duration: 0.55,
                stagger: 0.06,
                ease: 'power3.out',
                clearProps: 'transform,opacity',
            });
        }, pageRoot.value);
    }
});

onUnmounted(() => {
    if (slideTimer) clearInterval(slideTimer);
    if (gsapCtx) gsapCtx.revert();
});
</script>

<template>
    <Head title="Masuk Portal — Yayasan Namira" />

    <Teleport to="body">
        <NamiraLoader
            :visible="form.processing"
            variant="fullscreen"
            text="Menghubungkan ke Server..."
        />
    </Teleport>

    <div
        ref="pageRoot"
        class="min-h-screen w-full bg-white text-slate-900 font-sans antialiased selection:bg-[#009688]/15 selection:text-[#006B60] grid grid-cols-1 lg:grid-cols-12"
    >
        <!-- =========================================================
             LEFT COLUMN — Crisp White Editorial Auth Workspace
        ========================================================== -->
        <div class="lg:col-span-6 xl:col-span-5 flex flex-col justify-between min-h-screen bg-white relative">
            <!-- Top Navigation Bar -->
            <header class="px-5 sm:px-8 pt-5 pb-4 sm:pt-7 flex items-center justify-between gap-4 border-b border-slate-100 lg:border-b-0">
                <Link href="/" class="flex items-center gap-2.5 shrink-0">
                    <div class="w-9 h-9 rounded-xl bg-white border border-slate-100 shadow-2xs p-1 flex items-center justify-center shrink-0 overflow-hidden">
                        <img
                            :src="$page.props.app_settings?.app_logo || '/images/landing/logo-yayasan.webp'"
                            alt="Logo Yayasan Namira"
                            class="w-full h-full object-contain"
                        />
                    </div>
                    <div class="flex items-baseline gap-1 select-none whitespace-nowrap">
                        <span class="font-namira text-xl text-[#1a4373] tracking-tight lowercase leading-none">namira</span>
                        <span class="font-school text-xs text-slate-500 tracking-wider uppercase font-extrabold leading-none">SCHOOL</span>
                    </div>
                </Link>

                <Link
                    href="/"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 min-h-[38px] rounded-xl border border-slate-200 hover:border-[#009688] hover:text-[#008C7E] text-slate-600 text-xs font-bold transition-colors"
                >
                    <ArrowLeftIcon class="w-3.5 h-3.5" />
                    <span>Beranda</span>
                </Link>
            </header>

            <!-- Subtle Mobile Soft Mint Top Accent Strip -->
            <div class="lg:hidden h-1.5 w-full bg-gradient-to-r from-[#009688]/15 via-[#009688]/40 to-[#009688]/15"></div>

            <!-- Centered Form Area -->
            <main class="flex-1 flex flex-col justify-center px-5 sm:px-10 py-8 sm:py-10">
                <div class="w-full max-w-[390px] mx-auto space-y-6">
                    <!-- Heading -->
                    <div class="login-stagger space-y-2">
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#EDF9F4] text-[#007468] text-[11px] font-bold">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#009688]"></span>
                            <span>Portal Terpadu Yayasan Namira</span>
                        </div>
                        <h1 class="text-2xl sm:text-[1.85rem] tracking-tight text-slate-950 leading-tight">
                            <span class="font-normal">Masuk ke </span>
                            <span class="font-extrabold">Akun Anda</span>
                        </h1>
                        <p class="text-xs sm:text-[13.5px] text-slate-500 leading-relaxed">
                            Satu akun untuk akses Guru, Staf Yayasan, Orang Tua, dan Siswa Sekolah Islam Namira.
                        </p>
                    </div>

                    <!-- Status Alert -->
                    <div
                        v-if="status"
                        class="login-stagger text-xs font-semibold text-[#006B60] bg-[#ECF9F4] px-4 py-3 rounded-xl border border-[#009688]/25"
                    >
                        {{ status }}
                    </div>

                    <!-- Login Form -->
                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Login Identifier Field -->
                        <div class="login-stagger space-y-1.5">
                            <div class="flex items-center justify-between gap-2 min-h-[20px]">
                                <label for="login" class="text-xs font-bold text-slate-700">
                                    Email, No. WhatsApp, atau NIS
                                </label>
                                <AnimatePresence mode="wait">
                                    <Motion
                                        v-if="detectedMode"
                                        :key="detectedMode.key"
                                        :initial="{ opacity: 0, scale: 0.92 }"
                                        :animate="{ opacity: 1, scale: 1 }"
                                        :exit="{ opacity: 0, scale: 0.92 }"
                                        :transition="{ duration: 0.16 }"
                                        class="text-[10px] font-extrabold px-2 py-0.5 rounded-full border"
                                        :class="detectedMode.badgeClass"
                                    >
                                        {{ detectedMode.label }}
                                    </Motion>
                                </AnimatePresence>
                            </div>

                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <PhoneIcon v-if="isPhoneMode" class="w-5 h-5 text-[#008C7E] transition-colors" />
                                    <IdentificationIcon v-else-if="isNisMode" class="w-5 h-5 text-sky-600 transition-colors" />
                                    <EnvelopeIcon v-else class="w-5 h-5 text-slate-400 transition-colors" />
                                </div>
                                <input
                                    id="login"
                                    v-model="form.login"
                                    type="text"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="nama@email.com / 08xxx / NIS"
                                    class="block w-full min-h-[48px] py-3 pl-11 pr-4 bg-[#F9FBFA] hover:bg-white focus:bg-white border border-slate-200 focus:border-[#009688] focus:ring-2 focus:ring-[#009688]/15 rounded-xl text-slate-900 placeholder:text-slate-400 font-medium text-[15px] sm:text-sm transition-all"
                                />
                            </div>
                            <InputError class="mt-1 text-xs text-rose-600" :message="form.errors.login" />
                        </div>

                        <!-- Password Field -->
                        <div class="login-stagger space-y-1.5">
                            <div class="flex items-center justify-between gap-2">
                                <label for="password" class="text-xs font-bold text-slate-700">
                                    Kata Sandi
                                </label>
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="text-xs font-bold text-[#008C7E] hover:text-[#006B60] transition-colors"
                                >
                                    Lupa kata sandi?
                                </Link>
                            </div>

                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <LockClosedIcon class="w-5 h-5" />
                                </div>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Masukkan kata sandi"
                                    class="block w-full min-h-[48px] py-3 pl-11 pr-12 bg-[#F9FBFA] hover:bg-white focus:bg-white border border-slate-200 focus:border-[#009688] focus:ring-2 focus:ring-[#009688]/15 rounded-xl text-slate-900 placeholder:text-slate-400 font-medium text-[15px] sm:text-sm transition-all"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                    class="absolute inset-y-0 right-0 w-11 flex items-center justify-center text-slate-400 hover:text-slate-700 transition-colors cursor-pointer"
                                >
                                    <EyeSlashIcon v-if="showPassword" class="w-5 h-5" />
                                    <EyeIcon v-else class="w-5 h-5" />
                                </button>
                            </div>
                            <InputError class="mt-1 text-xs text-rose-600" :message="form.errors.password" />
                        </div>

                        <!-- Remember Me -->
                        <div class="login-stagger flex items-center justify-between pt-0.5">
                            <label class="inline-flex items-center gap-2.5 cursor-pointer select-none">
                                <input
                                    v-model="form.remember"
                                    type="checkbox"
                                    class="w-4 h-4 rounded border-slate-300 text-[#009688] focus:ring-[#009688]/25 cursor-pointer"
                                />
                                <span class="text-xs font-medium text-slate-600">Tetap masuk di perangkat ini</span>
                            </label>
                        </div>

                        <!-- Submit CTA -->
                        <div class="login-stagger pt-1">
                            <Motion :while-tap="{ scale: 0.99 }">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full min-h-[48px] py-3 px-6 rounded-xl bg-[#009688] hover:bg-[#007D71] text-white text-sm font-bold shadow-xs transition-colors flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60"
                                >
                                    <span>{{ form.processing ? 'Memproses...' : 'Masuk Portal' }}</span>
                                    <ArrowRightIcon v-if="!form.processing" class="w-4 h-4" />
                                </button>
                            </Motion>
                        </div>
                    </form>

                    <!-- Helpful Real Links (No Dead # Links!) -->
                    <div class="login-stagger pt-4 border-t border-slate-100 space-y-3">
                        <div class="rounded-2xl bg-[#F4FAF8] border border-[#E0F2EB] p-3.5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-xs font-extrabold text-slate-900">Calon Siswa Baru?</div>
                                <div class="text-[11px] text-slate-600 mt-0.5">Daftar online &amp; jalur Inden via SPMB.</div>
                            </div>
                            <Link
                                :href="route('spmb.index')"
                                class="px-3 py-2 rounded-lg bg-white border border-[#009688]/25 text-[#007468] hover:bg-[#009688] hover:text-white text-[11px] font-bold transition-colors shrink-0"
                            >
                                Buka SPMB
                            </Link>
                        </div>

                        <div class="flex items-center justify-between text-xs text-slate-500 pt-0.5">
                            <span>Kendala masuk akun?</span>
                            <a
                                :href="adminWaHref"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 font-bold text-[#008C7E] hover:underline"
                            >
                                <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                <span>Hubungi Admin WA</span>
                            </a>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Clean Minimal Footer -->
            <footer class="px-5 sm:px-8 py-4 text-center lg:text-left text-[11px] text-slate-400">
                © {{ new Date().getFullYear() }} Yayasan Sekolah Islam Namira · Probolinggo
            </footer>
        </div>

        <!-- =========================================================
             RIGHT COLUMN (Desktop) — Real Namira Campus Visual Panel
        ========================================================== -->
        <aside class="hidden lg:flex lg:col-span-6 xl:col-span-7 p-4 xl:p-5 bg-white">
            <div class="relative w-full h-full rounded-[28px] overflow-hidden bg-slate-900 flex flex-col justify-between p-8 xl:p-11">
                <!-- Real Crossfading Campus Photos -->
                <div class="absolute inset-0 z-0">
                    <div
                        v-for="(slide, idx) in slides"
                        :key="idx"
                        class="absolute inset-0 transition-opacity duration-1000 ease-in-out"
                        :class="activeSlide === idx ? 'opacity-100 z-10' : 'opacity-0 z-0'"
                    >
                        <img
                            :src="slide.url"
                            :alt="slide.title"
                            class="w-full h-full object-cover transition-transform duration-[5500ms] ease-out"
                            :class="activeSlide === idx ? 'scale-100' : 'scale-105'"
                        />
                    </div>
                    <!-- Calm Dark-Teal Editorial Scrim -->
                    <div
                        class="absolute inset-0 z-20"
                        style="background: linear-gradient(180deg, rgba(7,38,46,0.38) 0%, rgba(7,38,46,0.45) 45%, rgba(7,38,46,0.92) 100%);"
                    ></div>
                </div>

                <!-- Top Badge: Active Real Campus Location -->
                <div class="relative z-30 flex items-center justify-between gap-4">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/14 backdrop-blur-md border border-white/20 text-xs font-semibold text-white">
                        <MapPinIcon class="w-3.5 h-3.5 text-emerald-300 shrink-0" />
                        <span>{{ slides[activeSlide]?.title || 'Sekolah Islam Namira' }}</span>
                    </div>

                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-black/25 backdrop-blur-xs text-[11px] font-semibold text-white/85">
                        <span>10+ Unit Pendidikan</span>
                    </div>
                </div>

                <!-- Bottom Editorial Statement + 3 Unified Access Role Cards -->
                <div class="relative z-30 space-y-6">
                    <div class="space-y-2.5 max-w-lg">
                        <h2 class="text-2xl xl:text-3xl tracking-tight text-white leading-snug">
                            <span class="font-normal text-white/90">Satu ekosistem digital untuk </span>
                            <span class="font-extrabold">mendampingi generasi Qur’ani.</span>
                        </h2>
                        <p class="text-xs xl:text-sm text-white/75 leading-relaxed">
                            Terintegrasi untuk seluruh unit Daycare, KB, TK, SD Kraksaan, SD Kota Probolinggo, SMP Namira, dan Pavlov Center.
                        </p>
                    </div>

                    <!-- 3 Minimal Translucent Role Pillars -->
                    <div class="grid grid-cols-3 gap-3">
                        <div
                            v-for="role in portalRoles"
                            :key="role.title"
                            class="rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 p-3.5 space-y-2"
                        >
                            <div class="w-8 h-8 rounded-lg bg-emerald-400/20 text-emerald-300 flex items-center justify-center">
                                <component :is="role.icon" class="w-4 h-4" />
                            </div>
                            <div>
                                <div class="text-xs font-extrabold text-white">{{ role.title }}</div>
                                <div class="text-[11px] text-white/70 leading-snug mt-0.5">{{ role.desc }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Minimal Slide Switcher Dots -->
                    <div v-if="slides.length > 1" class="flex items-center gap-2 pt-1">
                        <button
                            v-for="(_, idx) in slides"
                            :key="idx"
                            type="button"
                            @click="activeSlide = idx"
                            :aria-label="`Foto kampus ${idx + 1}`"
                            class="h-1.5 rounded-full transition-all cursor-pointer"
                            :class="activeSlide === idx ? 'w-8 bg-emerald-400' : 'w-2 bg-white/35 hover:bg-white/60'"
                        ></button>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</template>
