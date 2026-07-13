<script setup>
import { ArrowLeftIcon, CheckIcon, ArrowDownTrayIcon } from '@heroicons/vue/20/solid';

defineProps({
    hasBlocked: {
        type: Boolean,
        required: true
    },
    processing: {
        type: Boolean,
        default: false
    },
    eligibleCount: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['back', 'promote', 'export']);
</script>

<template>
    <div>
        <div class="bg-white border rounded-2xl p-5 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <button 
                @click="emit('back')" 
                type="button"
                class="inline-flex items-center text-sm font-semibold text-gray-600 hover:text-gray-900"
            >
                <ArrowLeftIcon class="w-4 h-4 mr-2" />
                Kembali ke Pemilihan
            </button>

            <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto justify-end">
                <!-- Export Excel/CSV Button -->
                <button
                    @click="emit('export')"
                    type="button"
                    class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none w-full sm:w-auto"
                >
                    <ArrowDownTrayIcon class="h-4 w-4 mr-2 text-gray-500" />
                    Export Preview
                </button>

                <!-- Submit Button -->
                <button
                    v-if="!hasBlocked"
                    @click="emit('promote')"
                    :disabled="processing || eligibleCount === 0"
                    type="button"
                    class="inline-flex items-center justify-center px-5 py-2.5 bg-emerald-600 border border-transparent text-sm font-semibold rounded-xl text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-sm disabled:opacity-50 w-full sm:w-auto"
                >
                    <CheckIcon class="w-4 h-4 mr-2" />
                    Promote All Students
                </button>
                <button
                    v-else
                    @click="emit('promote')"
                    :disabled="processing || eligibleCount === 0"
                    type="button"
                    class="inline-flex items-center justify-center px-5 py-2.5 bg-namira-teal border border-transparent text-sm font-semibold rounded-xl text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-namira-teal shadow-sm disabled:opacity-50 w-full sm:w-auto"
                >
                    <CheckIcon class="w-4 h-4 mr-2" />
                    Promote Eligible Students
                </button>
            </div>
        </div>
        
        <!-- Warning Message if there are blocked students -->
        <div v-if="hasBlocked" class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-3">
            <span class="flex-shrink-0 inline-flex items-center justify-center p-1.5 bg-amber-100 rounded-lg text-amber-800">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </span>
            <div class="text-sm text-amber-700">
                Siswa yang tidak memenuhi syarat tidak akan dipromosikan.
            </div>
        </div>
    </div>
</template>
