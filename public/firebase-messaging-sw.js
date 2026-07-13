importScripts('https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.22.0/firebase-messaging-compat.js');

// Initialize Firebase in the Service Worker with full config
const firebaseConfig = {
  apiKey: "AIzaSyAawseDsW6nzHD-uSQYUrYOglaXTyyfo0I",
  authDomain: "notif-namira.firebaseapp.com",
  projectId: "notif-namira",
  storageBucket: "notif-namira.firebasestorage.app",
  messagingSenderId: "520189261944",
  appId: "1:520189261944:web:89989a53bb42c493e5958d"
};

firebase.initializeApp(firebaseConfig);

let messaging;
try {
  messaging = firebase.messaging();
} catch (e) {
  console.warn('FCM Messaging initialization skipped inside Service Worker: ', e);
}

// Background push notification listener
self.addEventListener('push', function(event) {
  if (event.data) {
    try {
      const payload = event.data.json();
      
      // If notification payload exists, show browser notification
      if (payload && (payload.notification || (payload.data && payload.data.notification))) {
        const notification = payload.notification || JSON.parse(payload.data.notification);
        const title = notification.title || 'SuperApp Namira';
        const options = {
          body: notification.body || '',
          icon: '/images/logo.png',
          badge: '/favicon.ico',
          data: payload.data || {}
        };
        
        event.waitUntil(self.registration.showNotification(title, options));
      }
    } catch (err) {
      console.error('Error handling push event: ', err);
    }
  }
});
