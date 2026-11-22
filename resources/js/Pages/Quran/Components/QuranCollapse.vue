<script setup>
defineProps({
    open: { type: Boolean, default: false },
});
</script>

<template>
    <div
        class="grid transition-[grid-template-rows,opacity] duration-250 ease-[cubic-bezier(0.16,1,0.3,1)]"
        :class="open ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0 pointer-events-none'"
    >
        <div class="overflow-hidden">
            <slot />
        </div>
    </div>
</template>
