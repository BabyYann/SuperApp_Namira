<script setup>
import { computed } from 'vue';
import { CalendarDaysIcon, SparklesIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: {
        type: String,
        required: true,
    },
    options: {
        type: Array,
        default: () => [],
    },
    accent: {
        type: String,
        default: 'emerald', // emerald | blue | amber
    },
    unitLabel: {
        type: String,
        default: 'Sekolah Islam Namira',
    },
    error: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const selectedOption = computed(() => {
    return (props.options || []).find((opt) => opt.value === props.modelValue) || null;
});

const theme = computed(() => {
    if (props.accent === 'blue') {
        return {
            tag: 'text-blue-700',
            activeCard: 'border-blue-600 bg-blue-50/70 ring-1 ring-blue-500 shadow-2xs',
            activeFutureCard: 'border-indigo-600 bg-indigo-50/70 ring-1 ring-indigo-500 shadow-2xs',
            activeBadge: 'bg-blue-700 text-white',
            futureBadge: 'bg-indigo-600 text-white',
            idleFutureBadge: 'bg-indigo-50 text-indigo-700 border border-indigo-200',
            idleCurrentBadge: 'bg-slate-100 text-slate-700 border border-slate-200',
            check: 'text-blue-700',
            futureCheck: 'text-indigo-700',
            notice: 'bg-indigo-50/80 border-indigo-200 text-indigo-950',
            noticeIcon: 'text-indigo-700',
        };
    }

    if (props.accent === 'amber') {
        return {
            tag: 'text-amber-800',
            activeCard: 'border-amber-600 bg-amber-50/70 ring-1 ring-amber-500 shadow-2xs',
            activeFutureCard: 'border-purple-600 bg-purple-50/70 ring-1 ring-purple-500 shadow-2xs',
            activeBadge: 'bg-amber-700 text-white',
            futureBadge: 'bg-purple-700 text-white',
            idleFutureBadge: 'bg-purple-50 text-purple-700 border border-purple-200',
            idleCurrentBadge: 'bg-slate-100 text-slate-700 border border-slate-200',
            check: 'text-amber-700',
            futureCheck: 'text-purple-700',
            notice: 'bg-purple-50/80 border-purple-200 text-purple-950',
            noticeIcon: 'text-purple-700',
        };
    }

    return {
        tag: 'text-emerald-700',
        activeCard: 'border-emerald-600 bg-emerald-50/70 ring-1 ring-emerald-500 shadow-2xs',
        activeFutureCard: 'border-purple-600 bg-purple-50/70 ring-1 ring-purple-500 shadow-2xs',
        activeBadge: 'bg-emerald-700 text-white',
        futureBadge: 'bg-purple-700 text-white',
        idleFutureBadge: 'bg-purple-50 text-purple-700 border border-purple-200',
        idleCurrentBadge: 'bg-slate-100 text-slate-700 border border-slate-200',
        check: 'text-emerald-700',
        futureCheck: 'text-purple-700',
        notice: 'bg-purple-50/80 border-purple-200 text-purple-950',
        noticeIcon: 'text-purple-700',
    };
});

const selectYear = (value) => {
    emit('update:modelValue', value);
};
</script>

<template>
    <div v-if="options && options.length > 0" class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-2xs space-y-4">
        <div class="border-b border-slate-100 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider flex items-center gap-1.5" :class="theme.tag">
                    <CalendarDaysIcon class="w-3.5 h-3.5" />
                    <span>Pilihan Tahun Ajaran Masuk</span>
                </span>
                <h2 class="text-sm font-bold text-slate-900 mt-0.5">
                    Pilih Tahun Ajaran Pendaftaran (Tahap Berjalan atau Inden Tahun Mendatang) *
                </h2>
            </div>
            <span
                v-if="options.length > 1"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-[10px] font-bold self-start sm:self-auto shrink-0"
            >
                <SparklesIcon class="w-3 h-3" />
                <span>Tersedia Jalur Inden Multi-Tahun</span>
            </span>
        </div>

        <div
            class="grid gap-2.5 sm:gap-3"
            :class="options.length <= 2 ? 'grid-cols-1 sm:grid-cols-2' : (options.length === 3 ? 'grid-cols-1 sm:grid-cols-3' : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4')"
        >
            <button
                v-for="opt in options"
                :key="opt.value"
                type="button"
                @click="selectYear(opt.value)"
                class="text-left p-3.5 rounded-xl border transition cursor-pointer flex flex-col justify-between gap-2.5 select-none"
                :class="modelValue === opt.value
                    ? (opt.is_future_inden ? theme.activeFutureCard : theme.activeCard)
                    : 'border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300'"
            >
                <div class="flex items-start justify-between gap-2">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wide"
                        :class="modelValue === opt.value
                            ? (opt.is_future_inden ? theme.futureBadge : theme.activeBadge)
                            : (opt.is_future_inden ? theme.idleFutureBadge : theme.idleCurrentBadge)"
                    >
                        {{ opt.badge }}
                    </span>
                    <CheckCircleIcon
                        v-if="modelValue === opt.value"
                        class="w-4 h-4 shrink-0"
                        :class="opt.is_future_inden ? theme.futureCheck : theme.check"
                    />
                </div>

                <div>
                    <strong class="block text-sm sm:text-base font-black text-slate-900 tracking-tight">
                        {{ opt.label }}
                    </strong>
                    <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                        {{ opt.description }}
                    </p>
                </div>
            </button>
        </div>

        <!-- Contextual Future Inden Notice -->
        <div
            v-if="selectedOption?.is_future_inden"
            class="rounded-xl border p-3.5 flex items-start gap-2.5 text-xs"
            :class="theme.notice"
        >
            <SparklesIcon class="w-4 h-4 shrink-0 mt-0.5" :class="theme.noticeIcon" />
            <div class="leading-relaxed text-[11px] sm:text-xs">
                <strong class="font-bold">Mode Jalur Inden Tahun Ajaran Mendatang ({{ selectedOption.label }}):</strong>
                Ananda akan langsung tercatat pada daftar prioritas <strong>Jalur Inden {{ unitLabel }} {{ selectedOption.label }}</strong>. Kursi diamankan lebih awal, dan panitia akan menginformasikan jadwal observasi/seleksi resmi menjelang tahun masuk {{ selectedOption.value }}.
            </div>
        </div>

        <p v-if="error" class="text-xs font-bold text-rose-600">
            {{ error }}
        </p>
    </div>
</template>
