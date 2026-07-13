<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Lupa Password" />

        <div class="mb-8 text-center animate-fade-in-up">
            <h2 class="text-2xl font-black font-heading text-white tracking-tight">Lupa Password?</h2>
            <p class="text-slate-300 text-sm font-medium mt-2 leading-relaxed">
                Tidak masalah! Masukkan email Anda dan kami akan mengirimkan link untuk membuat password baru.
            </p>
        </div>

        <div
            v-if="status"
            class="mb-6 text-sm font-medium text-[#00A99D] bg-teal-950/30 p-3 rounded-xl border border-teal-800/40 flex items-center gap-2 animate-fade-in-up"
        >
            <svg class="w-4 h-4 shrink-0 text-[#00A99D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>{{ status }}</span>
        </div>

        <form @submit.prevent="submit" class="space-y-5 relative z-10 animate-fade-in-up" style="animation-delay: 0.1s;">
            <div class="group">
                <InputLabel for="email" value="Alamat Email" class="mb-1.5 text-xs uppercase tracking-wider font-bold text-slate-300" />
                <div class="relative">
                    <TextInput
                        id="email"
                        type="email"
                        class="block w-full py-3.5 px-4 bg-white border-0 focus:ring-2 focus:ring-teal-500 rounded-xl font-medium text-slate-900 placeholder-slate-400 shadow-sm"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nama@namira.school"
                    />
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                        </svg>
                    </div>
                </div>
                <InputError class="mt-1.5" :message="form.errors.email" />
            </div>

            <div class="pt-2 space-y-4">
                <PrimaryButton
                    class="w-full justify-center py-3 text-sm font-bold bg-[#00A99D] hover:bg-[#008f85] active:bg-[#007068] text-white rounded-xl transition-all duration-200"
                    :class="{ 'opacity-75 cursor-wait': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="!form.processing">Kirim Link Reset</span>
                    <span v-else class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Mengirim...
                    </span>
                </PrimaryButton>

                <Link
                    :href="route('login')"
                    class="block text-center text-xs font-bold text-slate-300 hover:text-white transition-colors"
                >
                    ← Kembali ke Login
                </Link>
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
