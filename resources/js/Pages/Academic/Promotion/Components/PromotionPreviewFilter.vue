<script setup>
import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    modelValue: {
        type: String,
        default: 'all'
    },
    searchQuery: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue', 'update:searchQuery']);
</script>

<template>
    <div class="bg-white border rounded-2xl p-4 shadow-sm mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <!-- Status Tabs -->
        <div class="flex flex-wrap gap-1 p-1 bg-gray-100/80 rounded-xl w-full md:w-auto">
            <button
                v-for="status in ['all', 'eligible', 'blocked', 'warning']"
                :key="status"
                @click="emit('update:modelValue', status)"
                type="button"
                class="px-4 py-2 text-xs font-semibold rounded-lg capitalize transition-all"
                :class="modelValue === status 
                    ? 'bg-white text-gray-900 shadow-sm shadow-gray-200/50' 
                    : 'text-gray-500 hover:text-gray-900'"
            >
                {{ status === 'all' ? 'Semua' : status }}
            </button>
        </div>

        <!-- Search Bar -->
        <div class="relative w-full md:w-72">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <MagnifyingGlassIcon class="h-4 w-4 text-gray-400" />
            </div>
            <input
                :value="searchQuery"
                @input="emit('update:searchQuery', $event.target.value)"
                type="text"
                placeholder="Cari Nama atau NIS..."
                class="block w-full pl-9 pr-4 py-2 text-sm border-gray-200 rounded-xl focus:border-namira-teal focus:ring-namira-teal bg-gray-50/50"
            />
        </div>
    </div>
</template>
