# SuperApp Namira Project Rules

## ATURAN WAJIB: DILARANG MENGGUNAKAN EMOJI SEBAGAI ICON

Emoji (contoh: pencil, school, globe) TIDAK BOLEH digunakan sebagai elemen visual di UI.

Gunakan sebagai pengganti:
- Heroicons dari @heroicons/vue/24/outline untuk komponen Vue
- SVG inline untuk Leaflet popup atau HTML string
- Label teks deskriptif (Alumni, Kunjungan, Nasional, Internasional)
- CSS-styled badge/pill dengan warna

## Tech Stack
- Laravel + Inertia.js + Vue 3 + Tailwind CSS
- Icon: Heroicons (@heroicons/vue/24/outline)
- Map: Leaflet.js
- Build: npm run build
- Unit isolation: session('active_unit_id')
