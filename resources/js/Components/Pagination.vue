<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    links: {
        type: Array,
        default: () => [],
    },
    from: Number,
    to: Number,
    total: Number,
    perPage: Number,
    perPageOptions: {
        type: Array,
        default: () => [10, 25, 50, 100]
    }
});

const navigate = (url) => {
    if (!url) return;
    const relativeUrl = url.replace(/^https?:\/\/[^\/]+/, '');
    router.get(relativeUrl, {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const changePerPage = (e) => {
    const val = e.target.value;
    const urlObj = new URL(window.location.href);
    urlObj.searchParams.set('per_page', val);
    urlObj.searchParams.set('page', '1');
    const relativeUrl = urlObj.pathname + urlObj.search;
    router.get(relativeUrl, {}, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <!-- Info Text -->
        <div v-if="total !== undefined" class="text-xs text-gray-500 font-medium">
            Menampilkan <span class="font-bold text-gray-800">{{ from || 0 }}</span> - <span class="font-bold text-gray-800">{{ to || 0 }}</span> dari <span class="font-bold text-gray-800">{{ total || 0 }}</span> Data Guru
        </div>

        <!-- Page Buttons -->
        <div v-if="links && links.length > 3" class="flex flex-wrap justify-center items-center gap-1">
            <template v-for="(link, key) in links" :key="key">
                <div 
                    v-if="link.url === null" 
                    class="px-3.5 py-1.5 text-xs font-medium leading-4 text-gray-400 border border-gray-200 rounded-xl bg-gray-50/50 select-none cursor-not-allowed" 
                    v-html="link.label" 
                />
                <button 
                    v-else 
                    type="button"
                    @click="navigate(link.url)"
                    class="px-3.5 py-1.5 text-xs font-bold leading-4 border rounded-xl transition-all duration-200 active:scale-95 shadow-sm cursor-pointer" 
                    :class="{ 
                        'bg-namira-teal text-white border-namira-teal shadow-namira-teal/20': link.active, 
                        'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 hover:border-gray-300': !link.active 
                    }" 
                    v-html="link.label" 
                />
            </template>
        </div>

        <!-- Per Page Selector -->
        <div v-if="perPage" class="flex items-center gap-2 text-xs text-gray-500 font-medium">
            <span>Tampilkan</span>
            <select :value="perPage" @change="changePerPage" class="py-1 px-2 text-xs border-gray-200 rounded-lg focus:ring-namira-teal focus:border-namira-teal bg-white font-bold text-gray-700">
                <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
            </select>
            <span>per halaman</span>
        </div>
    </div>
</template>
