<script setup>
import { router } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        default: () => [],
    },
});

const navigate = (url) => {
    if (!url) return;
    // Convert absolute URL to relative path to prevent HTTP/HTTPS mixed content issues on live server
    const relativeUrl = url.replace(/^https?:\/\/[^\/]+/, '');
    router.get(relativeUrl, {}, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <div v-if="links && links.length > 3" class="flex flex-wrap justify-center items-center -mb-1 gap-1">
        <template v-for="(link, key) in links" :key="key">
            <div 
                v-if="link.url === null" 
                class="mr-1 mb-1 px-4 py-2 text-sm font-medium leading-4 text-gray-400 border border-gray-200 rounded-xl bg-gray-50/50 select-none cursor-not-allowed" 
                v-html="link.label" 
            />
            <button 
                v-else 
                type="button"
                @click="navigate(link.url)"
                class="mr-1 mb-1 px-4 py-2 text-sm font-bold leading-4 border rounded-xl transition-all duration-200 active:scale-95 shadow-sm cursor-pointer" 
                :class="{ 
                    'bg-namira-teal text-white border-namira-teal shadow-namira-teal/20': link.active, 
                    'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 hover:border-gray-300': !link.active 
                }" 
                v-html="link.label" 
            />
        </template>
    </div>
</template>
