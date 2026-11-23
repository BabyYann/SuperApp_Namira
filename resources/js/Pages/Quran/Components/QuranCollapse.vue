<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
});

// Lazy-mount slot contents on first open so closed accordions create zero DOM nodes
const hasOpened = ref(props.open);
watch(() => props.open, (val) => {
    if (val) hasOpened.value = true;
});
</script>

<template>
    <div
        class="grid transition-[grid-template-rows,opacity] duration-250 ease-[cubic-bezier(0.16,1,0.3,1)]"
        :class="open ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0 pointer-events-none'"
    >
        <div class="overflow-hidden">
            <slot v-if="hasOpened" />
        </div>
    </div>
</template>
