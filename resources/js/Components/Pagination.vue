<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap justify-center -mb-1 gap-1">
        <template v-for="(link, key) in links" :key="key">
            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-2 text-sm leading-4 text-gray-400 border rounded-xl" v-html="link.label" />
            <Link v-else 
                class="mr-1 mb-1 px-4 py-2 text-sm leading-4 border rounded-xl hover:bg-white focus:border-namira-teal focus:text-namira-teal transition-colors" 
                :class="{ 'bg-namira-teal text-white hover:bg-namira-teal': link.active, 'bg-white text-gray-700': !link.active }" 
                :href="link.url" 
                v-html="link.label" 
            />
        </template>
    </div>
</template>
