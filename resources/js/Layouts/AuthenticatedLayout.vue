<script setup>
import { ref, onMounted } from 'vue';
import Sidebar from '@/Components/Dashboard/Sidebar.vue';
import TopBar from '@/Components/Dashboard/TopBar.vue';
import { useMediaQuery } from '@vueuse/core';
import MobileAppShell from '@/Layouts/MobileAppShell.vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import Swal from 'sweetalert2';

window.Pusher = Pusher;

// Detect Mobile Screen (Tablet & Below)
const isMobile = useMediaQuery('(max-width: 768px)');

const isSidebarOpen = ref(true);

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

const page = usePage();

const loadFirebaseScripts = () => {
    return new Promise((resolve) => {
        if (window.firebase) {
            resolve();
            return;
        }

        const scriptApp = document.createElement('script');
        scriptApp.src = 'https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js';
        scriptApp.onload = () => {
            const scriptMsg = document.createElement('script');
            scriptMsg.src = 'https://www.gstatic.com/firebasejs/9.22.0/firebase-messaging-compat.js';
            scriptMsg.onload = resolve;
            document.head.appendChild(scriptMsg);
        };
        document.head.appendChild(scriptApp);
    });
};

const setupPushNotifications = async () => {
    const config = page.props.firebase;
    // Skip if configuration is not complete
    if (!config || !config.messagingSenderId) {
        console.warn('FCM Sender ID is missing. Push notifications skipped.');
        return;
    }

    try {
        await loadFirebaseScripts();

        // Initialize Firebase
        if (!firebase.apps.length) {
            firebase.initializeApp({
                apiKey: config.apiKey,
                authDomain: config.authDomain,
                projectId: config.projectId,
                storageBucket: config.storageBucket,
                messagingSenderId: config.messagingSenderId,
                appId: config.appId
            });
        }

        const messaging = firebase.messaging();

        // Register Service Worker explicitly from root domain scope
        const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js', {
            scope: '/'
        });

        // Request permission
        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
            // Retrieve token
            const token = await messaging.getToken({
                serviceWorkerRegistration: registration,
                vapidKey: config.vapidKey
            });

            if (token) {
                // Post token to backend
                await axios.post(route('push-tokens.store'), {
                    token: token,
                    device_type: 'web'
                });
                console.log('FCM Device Token registered successfully.');
            }
        } else {
            console.warn('Notification permission denied.');
        }
    } catch (error) {
        console.error('Error setting up FCM in layout:', error);
    }
};

const setupEcho = () => {
    // Check if configuration exists
    if (!import.meta.env.VITE_REVERB_APP_KEY) {
        console.warn('Reverb configuration missing. Echo listening disabled.');
        return;
    }

    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });

        // Listen for EmployeeCheckedIn event
        window.Echo.channel('attendance')
            .listen('EmployeeCheckedIn', (e) => {
                console.log('Real-time attendance broadcast received:', e);
                
                const currentUser = page.props.auth.user;
                if (!currentUser) return;

                const isAdmin = currentUser.roles.some(role => 
                    ['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'].includes(role)
                );

                if (isAdmin) {
                    const isGlobalAdmin = currentUser.roles.some(role => 
                        ['super_admin_yayasan', 'admin_yayasan'].includes(role)
                    );

                    const userUnitIds = currentUser.units ? currentUser.units.map(u => u.id) : [];
                    const matchesUnit = isGlobalAdmin || e.unit_ids.some(id => userUnitIds.includes(id));

                    if (matchesUnit) {
                        playChime();

                        Swal.fire({
                            title: e.type === 'check-in' ? 'Absensi Masuk Baru!' : 'Absensi Pulang Baru!',
                            text: `${e.employee_name} melakukan absensi (${e.status}) pada pukul ${e.time}.`,
                            icon: 'info',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 5000,
                            timerProgressBar: true,
                            background: '#ffffff',
                            color: '#1e293b',
                            customClass: {
                                popup: 'rounded-2xl border border-slate-100 shadow-xl'
                            }
                        });
                    }
                }
            });
    } catch (err) {
        console.error('Failed to set up Laravel Echo:', err);
    }
};

const playChime = () => {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, audioCtx.currentTime); 
        gain.gain.setValueAtTime(0.05, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.3);
        
        osc.start();
        osc.stop(audioCtx.currentTime + 0.3);
    } catch (e) {
        // Sound play block ignored
    }
};

onMounted(() => {
    if ('serviceWorker' in navigator && 'Notification' in window) {
        setupPushNotifications();
    }
    setupEcho();
});
</script>

<template>
    <!-- Mobile Layout (Native-Like PWA) -->
    <MobileAppShell v-if="isMobile">
        <slot />
    </MobileAppShell>

    <!-- Desktop Layout (Glassmorphism Sidebar) -->
    <div v-else class="min-h-screen bg-slate-50 font-sans text-gray-900 dark:bg-black dark:text-gray-100 relative selection:bg-teal-100 selection:text-teal-900">
        
        <!-- Ambient Glow / Mesh Gradient -->
        <div class="fixed top-0 left-0 w-[600px] h-[600px] bg-teal-400/20 rounded-full blur-[100px] -translate-x-1/2 -translate-y-1/2 pointer-events-none z-0"></div>
        <div class="fixed bottom-0 right-0 w-[600px] h-[600px] bg-violet-400/20 rounded-full blur-[100px] translate-x-1/3 translate-y-1/3 pointer-events-none z-0"></div>

        <!-- Sidebar -->
        <Sidebar :isSidebarOpen="isSidebarOpen" />

        <!-- Main Content Area -->
        <div 
            class="flex min-h-screen flex-col transition-all duration-300 ease-[cubic-bezier(0.25,0.8,0.25,1)] relative z-10"
            :class="{ 'ml-64': isSidebarOpen, 'ml-20': !isSidebarOpen }"
        >
            <!-- Top Navigation -->
            <TopBar :user="$page.props.auth.user" @toggleSidebar="toggleSidebar" />

            <!-- Page Content -->
            <main class="flex-1 p-6">
                 <!-- Animated Transition for Content -->
                    <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 animate-fade-in-up">
                         <!-- Page Header (Optional) -->
                        <header v-if="$slots.header" class="mb-8">
                             <div class="flex items-center justify-between">
                                <slot name="header" />
                             </div>
                        </header>

                        <slot />
                    </div>
            </main>
        </div>
        
        <!-- Overlay for Mobile (when sidebar is open but on small screens) -->
        <!-- Logic to handle mobile responsiveness specifically can be added here or in Sidebar CSS -->
    </div>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.5s ease-out forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
