<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import NamiraLoader from '@/Components/NamiraLoader.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
        <Head title="Masuk" />

        <Teleport to="body">
            <NamiraLoader 
                :visible="form.processing" 
                variant="fullscreen" 
                text="Menghubungkan ke Server..." 
            />
        </Teleport>

        <div class="mb-6 animate-fade-in-up">
            <h2 class="text-2xl font-extrabold text-white tracking-tight">
                Login
            </h2>
        </div>

        <div v-if="status" class="mb-5 text-sm font-medium text-[#00A99D] bg-teal-950/30 p-3 rounded-xl border border-teal-800/40 flex items-center gap-2 animate-fade-in-up">
            <svg class="w-4 h-4 shrink-0 text-[#00A99D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>{{ status }}</span>
        </div>

        <form @submit.prevent="submit" class="space-y-5 animate-fade-in-up" style="animation-delay: 0.1s;">
            <div>
                <InputLabel for="login" value="Email / NIS" class="mb-1.5 text-xs font-bold text-slate-300 uppercase tracking-wider" />
                <div class="relative group">
                    <TextInput
                        id="login"
                        type="text"
                        class="block w-full py-3 pl-4 pr-11 bg-white border-0 focus:ring-2 focus:ring-teal-500 rounded-xl text-slate-900 placeholder-slate-400 font-medium shadow-sm"
                        v-model="form.login"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Email atau NIS"
                    />
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none transition-colors" :class="isNisMode ? 'text-teal-500' : 'text-slate-400'">
                        <svg v-if="isNisMode" class="w-5 h-5 transition-all duration-300 transform scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                        </svg>
                    </div>
                </div>
                <div v-if="isNisMode" class="mt-1.5">
                    <p class="text-[10px] text-teal-400 font-bold flex items-center gap-1">
                        Mode NIS Aktif
                    </p>
                </div>
                <InputError class="mt-1.5" :message="form.errors.login" />
            </div>

            <div>
                <InputLabel for="password" value="Password" class="mb-1.5 text-xs font-bold text-slate-300 uppercase tracking-wider" />
                <div class="relative">
                    <TextInput
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="block w-full py-3 pl-4 pr-11 bg-white border-0 focus:ring-2 focus:ring-teal-500 rounded-xl text-slate-900 placeholder-slate-400 font-medium shadow-sm"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="Password Anda"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-teal-600 transition-colors"
                    >
                        <svg v-if="!showPassword" class="w-5.5 h-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <svg v-else class="w-5.5 h-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                        </svg>
                    </button>
                </div>
                <InputError class="mt-1.5" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center cursor-pointer group">
                    <Checkbox name="remember" v-model:checked="form.remember" class="rounded border-slate-600 bg-slate-950/50 text-[#00A99D] focus:ring-[#00A99D]" />
                    <span class="ms-2 text-xs text-slate-300 group-hover:text-white transition-colors font-medium">Ingat Saya</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-xs font-bold text-teal-400 hover:text-teal-300 transition-colors"
                >
                    Lupa Password?
                </Link>
            </div>

            <div class="pt-1">
                <PrimaryButton
                    class="w-full justify-center py-3 text-sm font-bold bg-[#00A99D] hover:bg-[#008f85] active:bg-[#007068] text-white rounded-xl transition-all duration-200"
                    :class="{ 'opacity-70 cursor-wait': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="!form.processing">LOGIN</span>
                    <span v-else>MEMPROSES...</span>
                </PrimaryButton>
            </div>
        </form>
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
