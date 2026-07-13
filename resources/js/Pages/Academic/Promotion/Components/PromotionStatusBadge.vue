<script setup>
defineProps({
    status: {
        type: String,
        required: true
    }
});
</script>

<template>
    <span
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider border"
        :class="{
            'bg-emerald-50 text-emerald-700 border-emerald-200': status === 'eligible',
            'bg-rose-50 text-rose-700 border-rose-200': status === 'blocked',
            'bg-amber-50 text-amber-700 border-amber-200': status === 'warning',
        }"
    >
        {{ status === 'eligible' ? 'Eligible' : status === 'blocked' ? 'Blocked' : 'Warning' }}
    </span>
</template>
