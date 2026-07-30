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

    // --- Verbose Debug Logs ---
    console.group('[FCM Setup]');
    console.log('Firebase config from server:', {
        apiKey: config?.apiKey ? '✅ Set' : '❌ MISSING',
        authDomain: config?.authDomain ? '✅ Set' : '❌ MISSING',
        projectId: config?.projectId || '❌ MISSING',
        messagingSenderId: config?.messagingSenderId ? '✅ Set' : '❌ MISSING (CRITICAL)',
        appId: config?.appId ? '✅ Set' : '❌ MISSING',
        vapidKey: config?.vapidKey ? '✅ Set' : '❌ MISSING (CRITICAL for token)',
    });

    // Skip if configuration is not complete
    if (!config || !config.messagingSenderId) {
        console.warn('[FCM] FIREBASE_SENDER_ID is missing from .env - Push notifications DISABLED.');
        console.groupEnd();
        return;
    }

    if (!config.vapidKey) {
        console.warn('[FCM] FIREBASE_VAPID_KEY is missing from .env - Cannot retrieve FCM browser token. Push notifications will NOT work.');
        console.groupEnd();
        return;
    }

    try {
        console.log('[FCM] Loading Firebase scripts...');
        await loadFirebaseScripts();
        console.log('[FCM] Firebase scripts loaded.');

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
            console.log('[FCM] Firebase app initialized.');
        } else {
            console.log('[FCM] Firebase app already initialized.');
        }

        const messaging = firebase.messaging();

        // Register Service Worker explicitly from root domain scope
        console.log('[FCM] Registering service worker...');
        const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js', {
            scope: '/'
        });
        console.log('[FCM] Service worker registered:', registration.scope);

        // Request permission
        const permission = await Notification.requestPermission();
        console.log('[FCM] Notification permission:', permission);

        if (permission === 'granted') {
            console.log('[FCM] Retrieving FCM token with vapidKey...');
            const token = await messaging.getToken({
                serviceWorkerRegistration: registration,
                vapidKey: config.vapidKey
            });

            if (token) {
                console.log('[FCM] FCM Token obtained:', token.substring(0, 30) + '...');
                // Post token to backend
                await axios.post(route('push-tokens.store'), {
                    token: token,
                    device_type: 'web'
                });
                console.log('[FCM] ✅ Device token registered to backend successfully.');
            } else {
                console.warn('[FCM] ⚠️ No token returned from getToken(). Check VAPID key and Firebase project config.');
            }
        } else {
            console.warn('[FCM] ⚠️ Notification permission denied by user. Push notifications will NOT work.');
        }

        // Background message handler (when app is in foreground)
        messaging.onMessage((payload) => {
            console.log('[FCM] Foreground message received:', payload);
            const n = payload.notification || {};
            Swal.fire({
                icon: 'info',
                title: n.title || 'Notifikasi Baru',
                text: n.body || '',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
            });
        });
    } catch (error) {
        console.error('[FCM] Error setting up FCM:', error);
    }
    console.groupEnd();
};

const setupEcho = () => {
    // Use the live server's own hostname, not the VITE env value which gets
    // baked in at local build time (127.0.0.1 / localhost).
    const reverbHost = window.location.hostname;
    const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

    if (!reverbKey) {
        console.warn('[Echo] VITE_REVERB_APP_KEY missing. Echo listening disabled.');
        return;
    }

    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: reverbHost,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: location.protocol === 'https:',
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
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

        // Listen for StudentScannedAtGate
        window.Echo.channel('student-gate-scan')
            .listen('StudentScannedAtGate', (e) => {
                const currentUser = page.props.auth.user;
                if (!currentUser) return;
                const isAdmin = currentUser.roles.some(role => 
                    ['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'pengawas_yayasan', 'guru'].includes(role)
                );
                if (isAdmin) {
                    playChime();
                    Swal.fire({
                        title: `Scan Gerbang: ${e.student_name}`,
                        text: `Kelas ${e.classroom} tercatat ${e.status.toUpperCase()} pukul ${e.checkin_time}.`,
                        icon: e.status === 'terlambat' ? 'warning' : 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true,
                    });
                }
            });

        // Listen for TeachingJournalSubmitted
        window.Echo.channel('teaching-journals')
            .listen('TeachingJournalSubmitted', (e) => {
                const currentUser = page.props.auth.user;
                if (!currentUser) return;
                const isAdmin = currentUser.roles.some(role => 
                    ['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'pengawas_yayasan'].includes(role)
                );
                if (isAdmin) {
                    Swal.fire({
                        title: 'Jurnal Mengajar Terisi',
                        text: `${e.teacher_name} mengisi jurnal ${e.subject_name} di kelas ${e.classroom_name}.`,
                        icon: 'info',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000,
                    });
                }
            });

        // Listen for SarprasMaintenanceReported
        window.Echo.channel('sarpras-maintenance')
            .listen('SarprasMaintenanceReported', (e) => {
                const currentUser = page.props.auth.user;
                if (!currentUser) return;
                const isAdmin = currentUser.roles.some(role => 
                    ['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_sarpar'].includes(role)
                );
                if (isAdmin) {
                    playChime();
                    Swal.fire({
                        title: 'Laporan Sarpras Baru',
                        text: `${e.reporter_name} melaporkan ${e.item_name}: ${e.issue_description}`,
                        icon: 'warning',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 5000,
                    });
                }
            });

        // Listen for PaymentTransactionRecorded
        window.Echo.channel('finance-transactions')
            .listen('PaymentTransactionRecorded', (e) => {
                const currentUser = page.props.auth.user;
                if (!currentUser) return;
                const isAdmin = currentUser.roles.some(role => 
                    ['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'finance', 'staff_admin_keuangan'].includes(role)
                );
                if (isAdmin) {
                    playChime();
                    Swal.fire({
                        title: 'Pembayaran Diterima',
                        text: `${e.student_name}: Rp ${Number(e.amount).toLocaleString('id-ID')} (${e.payment_type}).`,
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000,
                    });
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
