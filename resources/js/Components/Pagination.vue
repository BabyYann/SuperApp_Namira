<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    links: {
        type: [Array, Object],
        default: () => [],
    },
});

const formattedLinks = computed(() => {
    if (Array.isArray(props.links)) {
        return props.links;
    }
    if (props.links && Array.isArray(props.links.links)) {
        return props.links.links;
    }
    if (props.links && props.links.meta && Array.isArray(props.links.meta.links)) {
        return props.links.meta.links;
    }
    return [];
});

const navigate = (url) => {
    if (!url) return;
    const relativeUrl = url.replace(/^https?:\/\/[^\/]+/, '');
    router.get(relativeUrl, {}, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <div v-if="formattedLinks.length > 1" class="flex flex-wrap justify-center items-center gap-1.5">
        <template v-for="(link, key) in formattedLinks" :key="key">
            <div 
                v-if="!link.url" 
                class="px-3.5 py-2 text-xs font-semibold text-slate-400 border border-slate-200/80 rounded-xl bg-slate-50/60 select-none cursor-not-allowed" 
                v-html="link.label" 
            />
            <button 
                v-else 
                type="button"
                @click="navigate(link.url)"
                class="px-3.5 py-2 text-xs font-bold border rounded-xl transition-all duration-150 active:scale-95 shadow-sm cursor-pointer" 
                :class="{ 
                    'bg-namira-teal text-white border-namira-teal shadow-namira-teal/30 ring-2 ring-namira-teal/20': link.active, 
                    'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 hover:border-slate-300': !link.active 
                }" 
                v-html="link.label" 
            />
        </template>
    </div>
</template>
