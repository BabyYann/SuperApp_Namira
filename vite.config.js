import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: 'auto',
            workbox: {
                cleanupOutdatedCaches: true,
                globPatterns: ['**/*.{js,css,html,ico,png,svg,webp,woff2,ttf}'],
                runtimeCaching: [
                    {
                        // Cache Google Fonts & Bunny CDN Stylesheets
                        urlPattern: /^https:\/\/fonts\.(?:googleapis|bunny)\.net\/css.*/i,
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'google-fonts-stylesheets',
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        // Cache Webfonts
                        urlPattern: /^https:\/\/fonts\.(?:gstatic|bunny)\.net\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'webfonts-cache',
                            expiration: {
                                maxEntries: 30,
                                maxAgeSeconds: 60 * 60 * 24 * 365, // 1 year
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        // Cache Images, Logos, and Uploaded Media
                        urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp|ico)$/i,
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'images-cache',
                            expiration: {
                                maxEntries: 120,
                                maxAgeSeconds: 60 * 60 * 24 * 30, // 30 days
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        // Cache Local Storage / Public assets
                        urlPattern: /^\/(?:images|storage|build)\/.*/i,
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'static-assets-cache',
                            expiration: {
                                maxEntries: 100,
                                maxAgeSeconds: 60 * 60 * 24 * 30,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        // Offline Caching for Inertia Navigation & JSON data
                        urlPattern: ({ request, url }) => 
                            request.mode === 'navigate' || 
                            request.headers.get('X-Inertia') === 'true' || 
                            url.pathname.startsWith('/yayasan') ||
                            url.pathname.startsWith('/academic') ||
                            url.pathname.startsWith('/student') ||
                            url.pathname.startsWith('/attendance'),
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'pages-data-cache',
                            networkTimeoutSeconds: 3,
                            expiration: {
                                maxEntries: 50,
                                maxAgeSeconds: 60 * 60 * 24 * 7, // 7 days
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                ],
            },
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('leaflet')) {
                            return 'vendor-leaflet';
                        }
                        if (id.includes('chart.js') || id.includes('vue-chartjs')) {
                            return 'vendor-charts';
                        }
                        if (id.includes('quill') || id.includes('@vueup/vue-quill')) {
                            return 'vendor-quill';
                        }
                        if (id.includes('quagga2') || id.includes('jsqr') || id.includes('html5-qrcode') || id.includes('@zxing')) {
                            return 'vendor-scanner';
                        }
                        if (id.includes('@heroicons/vue') || id.includes('@vueuse/core') || id.includes('sweetalert2') || id.includes('dayjs') || id.includes('lodash')) {
                            return 'vendor-utils';
                        }
                    }
                },
            },
        },
        chunkSizeWarningLimit: 1000,
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
