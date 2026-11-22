<script setup>
import QuranDialog from './QuranDialog.vue';
import { ExclamationTriangleIcon, TrashIcon } from '@heroicons/vue/24/outline';

defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Konfirmasi Tindakan' },
    message: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Lanjutkan' },
    cancelLabel: { type: String, default: 'Batal' },
    tone: { type: String, default: 'danger' }, // 'danger' | 'warning'
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
    <QuranDialog :show="show" variant="modal" max-width="sm" :closeable="!processing" @close="emit('close')">
        <div class="flex items-start gap-3.5 pt-1">
            <div
                class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                :class="tone === 'danger' ? 'bg-rose-50 text-rose-600 border border-rose-200/70' : 'bg-amber-50 text-amber-600 border border-amber-200/70'"
            >
                <TrashIcon v-if="tone === 'danger'" class="w-4 h-4" />
                <ExclamationTriangleIcon v-else class="w-4 h-4" />
            </div>
            <div class="min-w-0">
                <h3 class="text-[15px] font-semibold text-slate-900 leading-snug">{{ title }}</h3>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">{{ message }}</p>
            </div>
        </div>

        <template #footer>
            <button
                type="button"
                :disabled="processing"
                @click="emit('close')"
                class="h-8 px-3.5 rounded-lg text-xs font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition-colors disabled:opacity-50"
            >
                {{ cancelLabel }}
            </button>
            <button
                type="button"
                :disabled="processing"
                @click="emit('confirm')"
                class="h-8 px-3.5 rounded-lg text-xs font-medium text-white transition-all active:scale-[0.98] disabled:opacity-50 inline-flex items-center gap-1.5"
                :class="tone === 'danger' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-amber-600 hover:bg-amber-700'"
            >
                <span v-if="processing" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                <span>{{ confirmLabel }}</span>
            </button>
        </template>
    </QuranDialog>
</template>
