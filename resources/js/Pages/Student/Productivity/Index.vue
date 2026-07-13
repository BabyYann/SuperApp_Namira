<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { 
    ArrowLeftIcon, 
    CheckCircleIcon, 
    PlusIcon, 
    TrashIcon,
    PlayIcon,
    PauseIcon,
    ArrowPathIcon,
    SparklesIcon,
    CheckIcon
} from '@heroicons/vue/24/outline';
import confetti from 'canvas-confetti';

const props = defineProps({
    student: Object,
    tasks: Array,
});

// --- TO-DO LIST LOGIC ---
const newTask = ref('');

const addTask = () => {
    if (!newTask.value.trim()) return;

    router.post(route('student.tasks.store'), {
        title: newTask.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newTask.value = '';
            triggerConfetti(0.3); // Small burst
        }
    });
};

const toggleTask = (task) => {
    router.put(route('student.tasks.update', task.id), {
        is_completed: !task.is_completed
    }, {
        preserveScroll: true,
        onSuccess: () => {
            if (!task.is_completed) { // Trigger if marking AS complete
                triggerConfetti(1.2); // BIG burst
            }
        }
    });
};

const deleteTask = (task) => {
    if (!confirm('Apakah kamu yakin ingin menghapus target ini?')) return;

    router.delete(route('student.tasks.destroy', task.id), {
        preserveScroll: true,
    });
};

const triggerConfetti = (intensity = 1) => {
    confetti({
        particleCount: 100 * intensity,
        spread: 70,
        origin: { y: 0.6 }
    });
};


// --- FOCUS TIMER LOGIC ---
const timerMode = ref('focus'); // focus (25), break (5)
const timeLeft = ref(25 * 60);
const isActive = ref(false);
let timerInterval = null;

const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

const progress = computed(() => {
    const total = timerMode.value === 'focus' ? 25 * 60 : 5 * 60;
    const elapsed = total - timeLeft.value;
    return (elapsed / total) * 100;
});

const toggleTimer = () => {
    if (isActive.value) {
        clearInterval(timerInterval);
        isActive.value = false;
    } else {
        isActive.value = true;
        timerInterval = setInterval(() => {
            if (timeLeft.value > 0) {
                timeLeft.value--;
            } else {
                // Timer Finished
                clearInterval(timerInterval);
                isActive.value = false;
                triggerConfetti(2);
                new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3').play().catch(e=>{}); // Simple ding sound
                
                // Auto switch mode suggestion or just stop
                if (timerMode.value === 'focus') {
                    if(confirm('Fokus selesai! Mulai istirahat 5 menit?')) {
                        switchMode('break');
                        toggleTimer();
                    }
                } else {
                    alert('Istirahat selesai! Kembali fokus?');
                     switchMode('focus');
                }
            }
        }, 1000);
    }
};

const resetTimer = () => {
    clearInterval(timerInterval);
    isActive.value = false;
    timeLeft.value = timerMode.value === 'focus' ? 25 * 60 : 5 * 60;
};

const switchMode = (mode) => {
    timerMode.value = mode;
    resetTimer();
};

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

// Focus Mode (Fullscreen Overlay) logic could be added here, 
// but for now we keep it inline in the design.
const isFocusMode = ref(false);

const toggleFocusMode = () => {
  isFocusMode.value = !isFocusMode.value;
}

</script>

<template>
    <Head title="Zona Produktivitas" />

    <!-- FOCUS MODE OVERLAY -->
    <div v-if="isFocusMode" class="fixed inset-0 z-50 bg-slate-900 flex flex-col items-center justify-center text-white transition-all duration-500">
        <button @click="toggleFocusMode" class="absolute top-8 right-8 text-slate-400 hover:text-white transition-colors">
            <ArrowLeftIcon class="w-8 h-8" />
        </button>

        <div class="text-center space-y-8 animate-pulse-slow">
             <h2 class="text-2xl font-light tracking-[0.2em] uppercase text-slate-400">
                {{ timerMode === 'focus' ? 'DEEP FOCUS' : 'REST & RECOVER' }}
             </h2>
             <div class="text-9xl font-black font-mono tracking-tighter">
                {{ formatTime(timeLeft) }}
             </div>
             
             <button @click="toggleTimer" class="px-12 py-4 rounded-full bg-white text-slate-900 font-bold text-xl hover:scale-105 transition-transform">
                {{ isActive ? 'PAUSE' : 'START' }}
             </button>
        </div>
    </div>


    <div class="min-h-screen bg-[#F2F4F8] font-sans pb-20 relative overflow-hidden">
        
        <!-- Header -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 relative z-10">
            <div class="mb-8 flex items-center gap-4">
                <Link :href="route('student.dashboard')" class="p-2.5 bg-white/80 backdrop-blur-xl rounded-xl shadow-sm border border-white/60 text-slate-600 hover:text-slate-900 transition-transform active:scale-95 group">
                    <ArrowLeftIcon class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Zona Produktif 🚀</h1>
                    <p class="text-sm text-slate-500 font-medium">Bantu dirimu fokus dan selesaikan target hari ini.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- LEFT: TO-DO LIST -->
                <div class="bg-white/60 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white/60 shadow-sm min-h-[500px] flex flex-col">
                    <div class="flex items-center justify-between mb-6">
                         <div class="flex items-center gap-3">
                             <div class="p-2 bg-pink-100 text-pink-500 rounded-xl">
                                <CheckBadgeIcon class="w-6 h-6" /> <!-- Using generic Icon placehold if needed, but imported Sparkles -->
                                <SparklesIcon class="w-6 h-6" />
                             </div>
                             <h2 class="text-xl font-bold text-slate-700">Target Hari Ini</h2>
                         </div>
                         <span class="text-xs font-bold bg-white px-3 py-1 rounded-full text-slate-400 border">{{ tasks.filter(t => t.is_completed).length }}/{{ tasks.length }} Selesai</span>
                    </div>

                    <!-- Input -->
                    <div class="relative group mb-8">
                         <input 
                            v-model="newTask" 
                            @keydown.enter="addTask"
                            type="text" 
                            placeholder="Tulis targetmu (misal: Belajar MTK 1 jam)..." 
                            class="w-full pl-6 pr-14 py-4 rounded-2xl border-none bg-white shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:ring-2 focus:ring-pink-400 placeholder:text-slate-400 text-slate-700 font-medium transition-all"
                        >
                        <button @click="addTask" class="absolute right-2 top-2 bottom-2 aspect-square bg-pink-500 hover:bg-pink-600 text-white rounded-xl flex items-center justify-center transition-all shadow-lg shadow-pink-200">
                             <PlusIcon class="w-6 h-6" />
                        </button>
                    </div>

                    <!-- List -->
                    <div class="flex-1 space-y-3 overflow-y-auto max-h-[400px] pr-2 scrollbar-thin scrollbar-thumb-slate-200">
                        <div v-if="tasks.length === 0" class="text-center py-10 text-slate-400">
                            <p>Belum ada target.</p>
                            <p class="text-xs mt-1">Yuk tulis satu hal kecil yang ingin kamu capai!</p>
                        </div>

                        <div v-for="task in tasks" :key="task.id" 
                            class="group flex items-center gap-4 p-4 rounded-2xl bg-white border border-transparent transition-all duration-300 hover:border-pink-100 hover:shadow-sm"
                            :class="{'opacity-50 grayscale': task.is_completed}"
                        >
                            <button @click="toggleTask(task)" 
                                class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all duration-300"
                                :class="task.is_completed ? 'bg-pink-500 border-pink-500 text-white' : 'border-slate-300 hover:border-pink-400'"
                            >
                                <CheckIcon v-if="task.is_completed" class="w-4 h-4" />
                            </button>
                            
                            <span class="flex-1 font-medium text-slate-700" :class="{'line-through text-slate-400': task.is_completed}">
                                {{ task.title }}
                            </span>

                            <button @click="deleteTask(task)" class="text-slate-300 hover:text-red-400 transition-colors p-2" title="Hapus">
                                <TrashIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>


                <!-- RIGHT: FOCUS TIMER -->
                <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white relative overflow-hidden flex flex-col items-center justify-center min-h-[500px]">
                     <!-- Background Blob -->
                     <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500 rounded-full blur-[100px] opacity-20 animate-pulse"></div>
                     <div class="absolute bottom-0 left-0 w-64 h-64 bg-pink-500 rounded-full blur-[100px] opacity-20 animate-pulse delay-700"></div>

                     <!-- Mode Switcher -->
                     <div class="flex items-center bg-white/10 p-1 rounded-full mb-8 backdrop-blur-md relative z-10">
                         <button @click="switchMode('focus')" class="px-6 py-2 rounded-full text-sm font-bold transition-all" :class="timerMode === 'focus' ? 'bg-indigo-500 shadow-lg shadow-indigo-500/30' : 'text-slate-400 hover:text-white'">
                            Fokus 🔥
                         </button>
                         <button @click="switchMode('break')" class="px-6 py-2 rounded-full text-sm font-bold transition-all" :class="timerMode === 'break' ? 'bg-teal-500 shadow-lg shadow-teal-500/30' : 'text-slate-400 hover:text-white'">
                            Istirahat ☕
                         </button>
                     </div>

                     <!-- Circle Timer -->
                     <div class="relative w-72 h-72 flex items-center justify-center mb-10">
                         <!-- SVG Circle -->
                         <svg class="w-full h-full transform -rotate-90 drop-shadow-2xl">
                             <circle cx="144" cy="144" r="130" stroke="currentColor" stroke-width="8" fill="transparent" class="text-slate-800" />
                             <circle cx="144" cy="144" r="130" stroke="currentColor" stroke-width="8" fill="transparent" 
                                :class="timerMode === 'focus' ? 'text-indigo-500' : 'text-teal-500'"
                                :stroke-dasharray="2 * Math.PI * 130"
                                :stroke-dashoffset="2 * Math.PI * 130 * (1 - progress / 100)"
                                class="transition-all duration-1000 ease-linear"
                                stroke-linecap="round"
                             />
                         </svg>
                         
                         <div class="absolute inset-0 flex flex-col items-center justify-center">
                             <span class="text-6xl font-black font-mono tracking-tighter">{{ formatTime(timeLeft) }}</span>
                             <span class="text-sm font-bold text-slate-400 uppercase tracking-widest mt-2">{{ isActive ? 'Running...' : 'Paused' }}</span>
                         </div>
                     </div>

                     <!-- Controls -->
                     <div class="flex items-center gap-6 relative z-10">
                         <button @click="resetTimer" class="p-4 rounded-full bg-white/10 hover:bg-white/20 hover:scale-105 transition-all text-slate-300">
                             <ArrowPathIcon class="w-6 h-6" />
                         </button>
                         
                         <button @click="toggleTimer" class="w-20 h-20 rounded-full flex items-center justify-center bg-white text-slate-900 shadow-xl hover:scale-110 active:scale-95 transition-all group">
                             <PauseIcon v-if="isActive" class="w-8 h-8 group-hover:text-indigo-600" />
                             <PlayIcon v-else class="w-8 h-8 ml-1 group-hover:text-indigo-600" />
                         </button>

                         <!-- Focus Mode Toggle -->
                         <button @click="toggleFocusMode" class="p-4 rounded-full bg-white/10 hover:bg-white/20 hover:scale-105 transition-all text-slate-300" title="Full Screen Zen Mode">
                              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                              </svg>
                         </button>
                     </div>

                </div>

            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom Scrollbar for tasks */
.scrollbar-thin::-webkit-scrollbar {
  width: 6px;
}
.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 20px;
}

@keyframes pulse-slow {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}
.animate-pulse-slow {
    animation: pulse-slow 3s infinite;
}
</style>
