<script setup>
defineProps({
    title: String,
    subtitle: String,
    image: String,
    status: String,
    statusColor: {
        type: String,
        default: 'bg-gray-100 text-gray-600'
    }
});
</script>

<template>
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-3 active:scale-[0.98] transition-transform duration-200">
        <div class="flex items-start gap-4">
            <!-- Image / Avatar -->
            <div v-if="image" class="flex-shrink-0">
                <img :src="image" class="w-12 h-12 rounded-full object-cover border border-gray-100 shadow-sm" alt="Avatar">
            </div>
            <div v-else class="flex-shrink-0 w-12 h-12 rounded-full bg-namira-teal/10 flex items-center justify-center text-namira-teal font-bold text-lg">
                {{ title?.charAt(0) || '?' }}
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <div class="flex justify-between items-start">
                    <h3 class="font-bold text-gray-900 truncate pr-2">{{ title }}</h3>
                    <span v-if="status" class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider" :class="statusColor">
                        {{ status }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 truncate mt-0.5">{{ subtitle }}</p>
                
                <!-- Extra Slots -->
                <div class="mt-2 text-xs text-gray-400">
                    <slot name="details" />
                </div>
            </div>
        </div>

        <!-- Actions Footer -->
        <div v-if="$slots.actions" class="mt-4 pt-3 border-t border-gray-50 flex justify-end gap-3">
            <slot name="actions" />
        </div>
    </div>
</template>
