<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    tag: { type: String, default: 'Sarana & Prasarana' },
});
</script>

<template>
    <div class="relative overflow-hidden rounded-3xl border border-teal-500/20 shadow-lg shadow-teal-950/5 mb-5 md:mb-6 select-none">
        <!-- Banner Background (WebP with PNG Fallback) -->
        <picture class="absolute inset-0 w-full h-full pointer-events-none">
            <source srcset="/images/sarpar/banner.webp" type="image/webp">
            <img 
                src="/images/sarpar/banner.png" 
                alt="Banner Sarana & Prasarana" 
                class="w-full h-full object-cover object-right"
                loading="eager"
            />
        </picture>

        <!-- Dynamic Responsive Gradient Overlay: Dark teal on text area, softly exposing warehouse scene on right -->
        <div class="absolute inset-0 bg-gradient-to-r from-teal-950/92 via-teal-900/65 to-transparent pointer-events-none"></div>

        <!-- Inner Content (Z-10) -->
        <div class="relative z-10 p-5 sm:p-6 md:p-7 flex flex-col md:flex-row md:items-center justify-between gap-4 text-white">
            <div class="max-w-xl space-y-1.5">
                <!-- Tag Pill -->
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/15 backdrop-blur-md border border-white/20 text-[11px] font-black uppercase tracking-wider text-teal-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-400 animate-pulse"></span>
                    <span>{{ tag }}</span>
                </div>

                <!-- Page Title -->
                <h1 class="text-xl sm:text-2xl md:text-3xl font-black tracking-tight text-white drop-shadow-xs">
                    {{ title }}
                </h1>

                <!-- Subtitle -->
                <p v-if="subtitle" class="text-xs sm:text-sm text-teal-100/90 leading-relaxed font-medium line-clamp-2">
                    {{ subtitle }}
                </p>
            </div>

            <!-- Optional Actions / Shortcut Buttons Slot -->
            <div v-if="$slots.actions" class="flex items-center gap-2.5 flex-wrap shrink-0">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>
