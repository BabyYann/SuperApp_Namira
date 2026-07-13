<script setup>
import { computed, ref, onMounted } from 'vue';

const props = defineProps({
    visible: {
        type: Boolean,
        default: true
    },
    size: {
        type: String,
        default: 'medium',
        validator: (value) => ['small', 'medium', 'large'].includes(value)
    },
    text: {
        type: String,
        default: ''
    },
    variant: {
        type: String,
        default: 'inline',
        validator: (value) => ['inline', 'button', 'card', 'table', 'overlay', 'fullscreen'].includes(value)
    },
    overlay: {
        type: Boolean,
        default: false
    }
});

// Detect prefers-reduced-motion
const prefersReducedMotion = ref(false);
onMounted(() => {
    const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    prefersReducedMotion.value = mediaQuery.matches;
    mediaQuery.addEventListener('change', (e) => {
        prefersReducedMotion.value = e.matches;
    });
});

// Classes mapping
const containerClass = computed(() => {
    if (props.variant === 'fullscreen') {
        return 'fixed inset-0 z-50 flex flex-col items-center justify-center bg-slate-950/80 backdrop-blur-md text-white';
    }
    if (props.variant === 'overlay' || props.overlay) {
        return 'absolute inset-0 z-40 flex flex-col items-center justify-center bg-slate-900/60 backdrop-blur-sm';
    }
    if (props.variant === 'card') {
        return 'w-full py-16 flex flex-col items-center justify-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm';
    }
    if (props.variant === 'table') {
        return 'w-full py-20 flex flex-col items-center justify-center bg-slate-50/50 dark:bg-slate-900/50';
    }
    if (props.variant === 'button') {
        return 'inline-flex items-center justify-center';
    }
    return 'inline-flex flex-col items-center justify-center';
});

// Dynamic Loader Size
const loaderSizeClass = computed(() => {
    if (props.size === 'small' || props.variant === 'button') return 'w-6 h-6';
    if (props.size === 'large' || props.variant === 'fullscreen') return 'w-16 h-16';
    return 'w-10 h-10'; // medium
});

// Text Styling
const textClass = computed(() => {
    if (props.variant === 'fullscreen') {
        return 'mt-6 text-sm font-bold tracking-widest text-slate-300 uppercase animate-pulse';
    }
    if (props.variant === 'button') {
        return 'hidden';
    }
    return 'mt-4 text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wider';
});
</script>

<template>
    <div v-if="visible" :class="containerClass" class="transition-all duration-300">
        <div class="flex flex-col items-center justify-center">
            <!-- Animated "N" Grid -->
            <div :class="[loaderSizeClass, 'relative flex items-center justify-center']">
                <!-- If system prefers-reduced-motion, show a simple pulse grid -->
                <div 
                    v-if="prefersReducedMotion" 
                    class="w-full h-full grid grid-cols-3 grid-rows-3 gap-0.5 animate-pulse"
                >
                    <div class="bg-namira-teal rounded-sm"></div>
                    <div></div>
                    <div class="bg-teal-500 rounded-sm"></div>
                    <div class="bg-namira-teal rounded-sm"></div>
                    <div class="bg-emerald-500 rounded-sm"></div>
                    <div></div>
                    <div class="bg-namira-teal rounded-sm"></div>
                    <div></div>
                    <div class="bg-namira-navy dark:bg-white rounded-sm"></div>
                </div>

                <!-- Else show the fluid 60fps GPU-Accelerated rotation animation -->
                <div v-else class="w-full h-full relative">
                    <!-- Box 1 (Top Left) -->
                    <div class="absolute w-[28%] h-[28%] rounded-sm bg-namira-teal left-[8%] top-[8%] anim-box-1"></div>
                    <!-- Box 2 (Bottom Left) -->
                    <div class="absolute w-[28%] h-[28%] rounded-sm bg-emerald-500 left-[8%] bottom-[8%] anim-box-2"></div>
                    <!-- Box 3 (Middle) -->
                    <div class="absolute w-[28%] h-[28%] rounded-sm bg-teal-500 left-[36%] top-[36%] anim-box-3"></div>
                    <!-- Box 4 (Top Right) -->
                    <div class="absolute w-[28%] h-[28%] rounded-sm bg-emerald-600 right-[8%] top-[8%] anim-box-4"></div>
                    <!-- Box 5 (Bottom Right) -->
                    <div class="absolute w-[28%] h-[28%] rounded-sm bg-namira-navy dark:bg-white right-[8%] bottom-[8%] anim-box-5"></div>
                </div>
            </div>

            <!-- Optional text descriptor -->
            <span v-if="text && variant !== 'button'" :class="textClass">
                {{ text }}
            </span>
        </div>
    </div>
</template>

<style scoped>
/* GPU Acceleration active for transform property */
.anim-box-1, .anim-box-2, .anim-box-3, .anim-box-4, .anim-box-5 {
    will-change: transform, opacity;
}

.anim-box-1 {
    animation: namira-box-1-move 1.6s cubic-bezier(0.25, 1, 0.5, 1) infinite;
}
.anim-box-2 {
    animation: namira-box-2-move 1.6s cubic-bezier(0.25, 1, 0.5, 1) infinite;
}
.anim-box-3 {
    animation: namira-box-3-move 1.6s cubic-bezier(0.25, 1, 0.5, 1) infinite;
}
.anim-box-4 {
    animation: namira-box-4-move 1.6s cubic-bezier(0.25, 1, 0.5, 1) infinite;
}
.anim-box-5 {
    animation: namira-box-5-move 1.6s cubic-bezier(0.25, 1, 0.5, 1) infinite;
}

/* Keyframes: Box 1 (Top Left) -> moves down-right to center, rotates, and expands back */
@keyframes namira-box-1-move {
    0%, 100% { transform: translate(0, 0) scale(1); opacity: 1; }
    25% { transform: translate(100%, 100%) scale(0.7); opacity: 0.8; }
    50% { transform: translate(100%, 100%) rotate(90deg) scale(0.7); opacity: 0.8; }
    75% { transform: translate(-10%, -10%) scale(1.1); opacity: 1; }
}

/* Keyframes: Box 2 (Bottom Left) -> moves up-right to center, rotates, and expands back */
@keyframes namira-box-2-move {
    0%, 100% { transform: translate(0, 0) scale(1); opacity: 1; }
    25% { transform: translate(100%, -100%) scale(0.7); opacity: 0.8; }
    50% { transform: translate(100%, -100%) rotate(90deg) scale(0.7); opacity: 0.8; }
    75% { transform: translate(-10%, 10%) scale(1.1); opacity: 1; }
}

/* Keyframes: Box 3 (Middle) -> shrinks, spins, and snaps back */
@keyframes namira-box-3-move {
    0%, 100% { transform: scale(1) rotate(0deg); opacity: 1; }
    25% { transform: scale(0.5) rotate(90deg); opacity: 0.7; }
    50% { transform: scale(0.5) rotate(180deg); opacity: 0.7; }
    75% { transform: scale(1.2) rotate(270deg); opacity: 1; }
}

/* Keyframes: Box 4 (Top Right) -> moves down-left to center, rotates, and expands back */
@keyframes namira-box-4-move {
    0%, 100% { transform: translate(0, 0) scale(1); opacity: 1; }
    25% { transform: translate(-100%, 100%) scale(0.7); opacity: 0.8; }
    50% { transform: translate(-100%, 100%) rotate(90deg) scale(0.7); opacity: 0.8; }
    75% { transform: translate(10%, -10%) scale(1.1); opacity: 1; }
}

/* Keyframes: Box 5 (Bottom Right) -> moves up-left to center, rotates, and expands back */
@keyframes namira-box-5-move {
    0%, 100% { transform: translate(0, 0) scale(1); opacity: 1; }
    25% { transform: translate(-100%, -100%) scale(0.7); opacity: 0.8; }
    50% { transform: translate(-100%, -100%) rotate(90deg) scale(0.7); opacity: 0.8; }
    75% { transform: translate(10%, 10%) scale(1.1); opacity: 1; }
}
</style>
