<script setup>
import { watch, onUnmounted } from 'vue';
import { onKeyStroke } from '@vueuse/core';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: { type: Boolean, default: false },
    variant: { type: String, default: 'modal' }, // 'modal' | 'drawer' | 'sheet'
    maxWidth: { type: String, default: 'lg' },   // 'sm' | 'md' | 'lg' | 'xl' | '2xl'
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    closeable: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);

const close = () => {
    if (props.closeable) emit('close');
};

onKeyStroke('Escape', (e) => {
    if (props.show && props.closeable) {
        e.preventDefault();
        close();
    }
});

watch(() => props.show, (open) => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = open ? 'hidden' : '';
    }
}, { immediate: true });

onUnmounted(() => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});

const modalWidthClass = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
}[props.maxWidth] || 'max-w-lg';

const drawerWidthClass = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
}[props.maxWidth] || 'sm:max-w-lg';
</script>

<template>
    <Teleport to="body">
        <Transition name="q-backdrop">
            <div
                v-if="show"
                class="fixed inset-0 z-[100] bg-slate-950/40 backdrop-blur-[2px]"
                @click="close"
            />
        </Transition>

        <!-- Centered Modal -->
        <Transition v-if="variant === 'modal'" name="q-modal">
            <div
                v-if="show"
                class="fixed inset-0 z-[101] flex items-center justify-center p-4 pointer-events-none overflow-y-auto"
            >
                <div
                    role="dialog"
                    aria-modal="true"
                    :class="[
                        'pointer-events-auto relative w-full bg-white rounded-xl border border-slate-200/90 shadow-xl shadow-slate-900/10 flex flex-col max-h-[90vh] overflow-hidden',
                        modalWidthClass,
                    ]"
                >
                    <div
                        v-if="title || $slots.header"
                        class="flex items-start justify-between gap-4 px-5 py-4 border-b border-slate-100 shrink-0"
                    >
                        <slot name="header">
                            <div class="min-w-0">
                                <h3 class="text-[15px] font-semibold text-slate-900 tracking-tight truncate">
                                    {{ title }}
                                </h3>
                                <p v-if="subtitle" class="text-xs text-slate-500 mt-0.5 truncate">
                                    {{ subtitle }}
                                </p>
                            </div>
                        </slot>
                        <button
                            v-if="closeable"
                            type="button"
                            @click="close"
                            class="p-1.5 -mr-1.5 -mt-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors shrink-0"
                            aria-label="Tutup"
                        >
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-5 py-4">
                        <slot />
                    </div>

                    <div
                        v-if="$slots.footer"
                        class="px-5 py-3.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 shrink-0"
                    >
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Right Slide-Over Drawer (Desktop) / Bottom Sheet (Mobile) -->
        <Transition v-else-if="variant === 'drawer'" name="q-drawer">
            <div
                v-if="show"
                class="fixed inset-0 z-[101] flex justify-end items-end sm:items-stretch pointer-events-none"
            >
                <div
                    role="dialog"
                    aria-modal="true"
                    :class="[
                        'pointer-events-auto relative w-full bg-white shadow-2xl shadow-slate-900/15 flex flex-col overflow-hidden',
                        'max-h-[92vh] sm:max-h-full rounded-t-2xl sm:rounded-none sm:border-l border-slate-200/90',
                        drawerWidthClass,
                    ]"
                >
                    <!-- Mobile Drag Indicator -->
                    <div class="sm:hidden flex justify-center pt-2.5 pb-1 shrink-0">
                        <span class="w-9 h-1 rounded-full bg-slate-200" />
                    </div>

                    <div
                        v-if="title || $slots.header"
                        class="flex items-start justify-between gap-4 px-5 py-3.5 sm:py-4 border-b border-slate-100 shrink-0"
                    >
                        <slot name="header">
                            <div class="min-w-0">
                                <h3 class="text-[15px] font-semibold text-slate-900 tracking-tight truncate">
                                    {{ title }}
                                </h3>
                                <p v-if="subtitle" class="text-xs text-slate-500 mt-0.5 truncate">
                                    {{ subtitle }}
                                </p>
                            </div>
                        </slot>
                        <button
                            v-if="closeable"
                            type="button"
                            @click="close"
                            class="p-1.5 -mr-1.5 -mt-0.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors shrink-0"
                            aria-label="Tutup"
                        >
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        <slot />
                    </div>

                    <div
                        v-if="$slots.footer"
                        class="px-5 py-3.5 bg-white border-t border-slate-200/80 shrink-0"
                    >
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Bottom Sheet (Mobile Focused) -->
        <Transition v-else name="q-sheet">
            <div
                v-if="show"
                class="fixed inset-0 z-[101] flex items-end justify-center pointer-events-none"
            >
                <div
                    role="dialog"
                    aria-modal="true"
                    class="pointer-events-auto relative w-full max-w-xl max-h-[90vh] bg-white rounded-t-2xl border-t border-slate-200/90 shadow-2xl shadow-slate-900/20 flex flex-col overflow-hidden"
                >
                    <div class="flex justify-center pt-2.5 pb-1 shrink-0">
                        <span class="w-9 h-1 rounded-full bg-slate-200" />
                    </div>

                    <div
                        v-if="title || $slots.header"
                        class="flex items-start justify-between gap-3 px-5 py-3 border-b border-slate-100 shrink-0"
                    >
                        <slot name="header">
                            <div class="min-w-0">
                                <h3 class="text-[15px] font-semibold text-slate-900 tracking-tight truncate">
                                    {{ title }}
                                </h3>
                                <p v-if="subtitle" class="text-xs text-slate-500 mt-0.5 truncate">
                                    {{ subtitle }}
                                </p>
                            </div>
                        </slot>
                        <button
                            v-if="closeable"
                            type="button"
                            @click="close"
                            class="p-1.5 -mr-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors shrink-0"
                            aria-label="Tutup"
                        >
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        <slot />
                    </div>

                    <div
                        v-if="$slots.footer"
                        class="px-5 py-3.5 bg-white border-t border-slate-200/80 shrink-0"
                    >
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.q-backdrop-enter-active,
.q-backdrop-leave-active {
    transition: opacity 180ms cubic-bezier(0.16, 1, 0.3, 1);
}
.q-backdrop-enter-from,
.q-backdrop-leave-to {
    opacity: 0;
}

.q-modal-enter-active {
    transition: opacity 200ms cubic-bezier(0.16, 1, 0.3, 1), transform 220ms cubic-bezier(0.16, 1, 0.3, 1);
}
.q-modal-leave-active {
    transition: opacity 140ms ease-in, transform 140ms ease-in;
}
.q-modal-enter-from,
.q-modal-leave-to {
    opacity: 0;
    transform: translateY(8px) scale(0.97);
}

.q-drawer-enter-active {
    transition: transform 260ms cubic-bezier(0.16, 1, 0.3, 1), opacity 200ms ease-out;
}
.q-drawer-leave-active {
    transition: transform 180ms ease-in, opacity 160ms ease-in;
}
.q-drawer-enter-from,
.q-drawer-leave-to {
    opacity: 0;
    transform: translateY(24px);
}
@media (min-width: 640px) {
    .q-drawer-enter-from,
    .q-drawer-leave-to {
        opacity: 0.9;
        transform: translateX(100%);
    }
}

.q-sheet-enter-active {
    transition: transform 260ms cubic-bezier(0.16, 1, 0.3, 1), opacity 200ms ease-out;
}
.q-sheet-leave-active {
    transition: transform 180ms ease-in, opacity 160ms ease-in;
}
.q-sheet-enter-from,
.q-sheet-leave-to {
    opacity: 0;
    transform: translateY(100%);
}
</style>
