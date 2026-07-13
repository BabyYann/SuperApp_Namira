<script setup>
defineProps({
    code: {
        type: String,
        required: true
    },
    message: {
        type: String,
        required: true
    }
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-medium border cursor-help text-left"
        :class="{
            'bg-rose-50 text-rose-700 border-rose-200': code === 'OUTSTANDING_BILL' || code === 'DISCIPLINARY_POINT',
            'bg-amber-50 text-amber-700 border-amber-200': code === 'BILL_WARNING' || code === 'BK_WARNING',
        }"
        :title="message"
    >
        <span
            class="w-1.5 h-1.5 rounded-full flex-shrink-0"
            :class="{
                'bg-rose-500': code === 'OUTSTANDING_BILL' || code === 'DISCIPLINARY_POINT',
                'bg-amber-500': code === 'BILL_WARNING' || code === 'BK_WARNING',
            }"
        ></span>
        {{ 
            code === 'OUTSTANDING_BILL' ? 'Outstanding Bill' : 
            code === 'DISCIPLINARY_POINT' ? 'BK Point' : 
            code === 'BILL_WARNING' ? 'Bill Warning' : 'BK Warning'
        }}
    </span>
</template>
