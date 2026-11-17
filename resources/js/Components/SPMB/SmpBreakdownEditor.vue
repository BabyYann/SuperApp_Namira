<script setup>
import { buildSmpBreakdownItems, sumBreakdownItems } from '@/Utils/spmbSmpBreakdown';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
    },
    applicant: {
        type: Object,
        required: true,
    },
    setting: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue', 'update:total']);

const rebuildFromToggles = (patch = {}) => {
    const next = {
        ...props.modelValue,
        ...patch,
    };
    const items = buildSmpBreakdownItems(props.applicant, props.setting, {
        withAlumniDiscount: next.withAlumniDiscount,
        withSiblingDiscount: next.withSiblingDiscount,
        withMahad: next.withMahad,
    });
    const updated = {
        ...next,
        items,
    };
    emit('update:modelValue', updated);
    emit('update:total', sumBreakdownItems(items));
};

const updateItemAmount = (index, rawVal) => {
    const items = (props.modelValue.items || []).map((item, i) => (
        i === index ? { ...item, amount: Math.max(0, Number(rawVal || 0)) } : item
    ));
    emit('update:modelValue', { ...props.modelValue, items });
    emit('update:total', sumBreakdownItems(items));
};

const updateItemSublabel = (index, val) => {
    const items = (props.modelValue.items || []).map((item, i) => (
        i === index ? { ...item, sublabel: val } : item
    ));
    emit('update:modelValue', { ...props.modelValue, items });
};
</script>

<template>
    <div class="rounded-2xl border border-indigo-200 dark:border-indigo-800 bg-indigo-50/50 dark:bg-indigo-950/25 p-3 space-y-2.5 text-[11px]">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <strong class="text-indigo-950 dark:text-indigo-200 block text-xs">
                    Rincian Kwitansi SMP (TA {{ setting?.academic_year || '2027/2028' }})
                </strong>
                <span class="text-[10px] text-slate-500 dark:text-slate-400">
                    Gender: {{ applicant?.gender === 'P' ? 'Putri (Seragam 1,83 jt)' : 'Putra (Seragam 1,525 jt)' }} &bull;
                    {{ applicant?.is_inden ? 'Jalur Inden (6 jt)' : 'Waiting List (7 jt)' }}
                </span>
            </div>
            <button
                type="button"
                @click="rebuildFromToggles()"
                class="px-2 py-1 rounded-lg bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300 font-bold text-[10px] hover:bg-indigo-50 transition"
            >
                Hitung Otomatis
            </button>
        </div>

        <!-- Quick Policy Toggles -->
        <div class="flex flex-wrap gap-3 pt-1 border-t border-indigo-200/70 dark:border-indigo-800/50">
            <label class="inline-flex items-center gap-1.5 cursor-pointer text-slate-700 dark:text-slate-300 font-semibold">
                <input
                    type="checkbox"
                    :checked="modelValue.withAlumniDiscount"
                    @change="rebuildFromToggles({ withAlumniDiscount: $event.target.checked })"
                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span>Alumni SD (-1 jt)</span>
            </label>
            <label class="inline-flex items-center gap-1.5 cursor-pointer text-slate-700 dark:text-slate-300 font-semibold">
                <input
                    type="checkbox"
                    :checked="modelValue.withSiblingDiscount"
                    @change="rebuildFromToggles({ withSiblingDiscount: $event.target.checked })"
                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span>Adik Kandung (-500 rb)</span>
            </label>
            <label class="inline-flex items-center gap-1.5 cursor-pointer text-slate-700 dark:text-slate-300 font-semibold">
                <input
                    type="checkbox"
                    :checked="modelValue.withMahad"
                    @change="rebuildFromToggles({ withMahad: $event.target.checked })"
                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span>Ma'had / Asrama (+1,65 jt)</span>
            </label>
        </div>

        <!-- 7 Component Rows -->
        <div class="max-h-48 overflow-y-auto space-y-1.5 pr-0.5">
            <div
                v-for="(item, idx) in modelValue.items"
                :key="item.key"
                class="grid grid-cols-12 gap-1.5 items-center bg-white dark:bg-slate-900/90 rounded-lg px-2 py-1.5 border border-slate-200 dark:border-slate-700"
            >
                <div class="col-span-4 font-bold text-[10px] text-slate-700 dark:text-slate-200 truncate" :title="item.label">
                    {{ idx + 1 }}. {{ item.label }}
                </div>
                <div class="col-span-4">
                    <input
                        type="text"
                        :value="item.sublabel"
                        @input="updateItemSublabel(idx, $event.target.value)"
                        placeholder="Ket."
                        class="w-full rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-1.5 py-0.5 text-[10px] text-slate-600 dark:text-slate-300"
                    />
                </div>
                <div class="col-span-4">
                    <input
                        type="number"
                        min="0"
                        :value="item.amount"
                        @input="updateItemAmount(idx, $event.target.value)"
                        class="w-full rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-1.5 py-0.5 text-[10px] text-right font-mono font-bold text-slate-900 dark:text-white"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
