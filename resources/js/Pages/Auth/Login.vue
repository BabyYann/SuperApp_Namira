<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import NamiraLoader from '@/Components/NamiraLoader.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { 
    EnvelopeIcon, 
    LockClosedIcon, 
    EyeIcon, 
    EyeSlashIcon, 
    IdentificationIcon
} from '@heroicons/vue/24/outline';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const isNisMode = computed(() => /^\d+$/.test(form.login));

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk Portal - Namira" />

        <Teleport to="body">
            <NamiraLoader 
                :visible="form.processing" 
                variant="fullscreen" 
                text="Menghubungkan ke Server..." 
            />
        </Teleport>

        <!-- TOP CENTERED LOGO & TITLE (Matching Reference Image) -->
        <div class="flex flex-col items-center text-center mb-6">
            <div class="w-20 h-20 mb-3 flex items-center justify-center drop-shadow-lg">
                <ApplicationLogo class="w-full h-full object-contain" />
            </div>
            <h1 class="text-3xl font-black text-white tracking-tight leading-none mb-1.5">
                Namira
            </h1>
            <p class="text-sm font-medium text-slate-200">
                Welcome Back
            </p>
        </div>

        <!-- WHITE FLOATING CARD (Matching Reference Image) -->
        <div class="bg-white rounded-[28px] p-6 sm:p-8 shadow-2xl border border-slate-100/90 text-slate-800">
            
            <div v-if="status" class="mb-5 text-xs font-semibold text-teal-800 bg-teal-50 p-3 rounded-2xl border border-teal-200 flex items-center gap-2">
                <span>{{ status }}</span>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- EMAIL / NIS FIELD -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <InputLabel for="login" value="Email" class="text-xs font-bold text-slate-700" />
                        <span v-if="isNisMode" class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-teal-100 text-teal-800 border border-teal-200">
                            Mode NIS Siswa
                        </span>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <IdentificationIcon v-if="isNisMode" class="w-5 h-5 text-teal-600" />
                            <EnvelopeIcon v-else class="w-5 h-5" />
                        </div>
                        <input
                            id="login"
                            type="text"
                            class="block w-full py-3.5 pl-11 pr-4 bg-slate-50/70 border border-slate-300 focus:border-[#00695c] focus:ring-2 focus:ring-[#00695c]/20 rounded-2xl text-slate-800 placeholder-slate-400 font-medium text-sm transition-all"
                            v-model="form.login"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="nama@email.com"
                        />
                    </div>
                    <InputError class="mt-1.5 text-xs text-rose-600" :message="form.errors.login" />
                </div>

                <!-- KATA SANDI FIELD -->
                <div>
                    <InputLabel for="password" value="Kata Sandi" class="mb-1.5 text-xs font-bold text-slate-700" />
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <LockClosedIcon class="w-5 h-5" />
                        </div>
                        <input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            class="block w-full py-3.5 pl-11 pr-11 bg-slate-50/70 border border-slate-300 focus:border-[#00695c] focus:ring-2 focus:ring-[#00695c]/20 rounded-2xl text-slate-800 placeholder-slate-400 font-medium text-sm transition-all"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="Masukkan kata sandi"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 transition-colors p-1"
                        >
                            <EyeSlashIcon v-if="showPassword" class="w-5 h-5" />
                            <EyeIcon v-else class="w-5 h-5" />
                        </button>
                    </div>
                    <InputError class="mt-1.5 text-xs text-rose-600" :message="form.errors.password" />
                </div>

                <!-- INGAT SAYA & LUPA KATA SANDI -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center cursor-pointer group select-none">
                        <Checkbox name="remember" v-model:checked="form.remember" class="rounded border-slate-300 bg-white text-[#00695c] focus:ring-[#00695c]" />
                        <span class="ms-2 text-xs font-semibold text-slate-600 group-hover:text-slate-900 transition-colors">Ingat Saya</span>
                    </label>

                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs font-bold text-[#00695c] hover:text-teal-700 transition-colors"
                    >
                        Lupa Kata Sandi?
                    </Link>
                </div>

                <!-- TOMBOL MASUK -->
                <div class="pt-2">
                    <button
                        type="submit"
                        class="w-full py-3.5 px-6 text-sm font-bold bg-[#00695c] hover:bg-[#005b50] active:scale-[0.98] text-white rounded-full shadow-md transition-all duration-200 flex items-center justify-center gap-2"
                        :class="{ 'opacity-70 cursor-wait': form.processing }"
                        :disabled="form.processing"
                    >
                        <span>{{ form.processing ? 'Memproses...' : 'Masuk' }}</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- BOTTOM FOOTER (Matching Reference Image) -->
        <div class="mt-6 text-center space-y-1.5">
            <p class="text-xs text-slate-200 font-medium">
                Butuh bantuan? <a href="#" class="text-teal-300 font-bold hover:underline">Hubungi Administrator</a>
            </p>
            <p class="text-[11px] text-slate-300 font-normal">
                <a href="#" class="hover:underline">Kebijakan Privasi</a> &bull; <a href="#" class="hover:underline">Ketentuan Layanan</a>
            </p>
            <p class="text-[10px] text-slate-400 font-semibold tracking-wider pt-1">
                Versi 1.0.0
            </p>
        </div>
    </GuestLayout>
</template>

<style scoped>
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-fade-in-up {
    animation: fade-in-up 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
