<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan | SuperApp Namira</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        'namira-teal': '#002824',
                    }
                }
            }
        }
    </script>
    <style>
        /* Eye Blinking Animation */
        @keyframes eyeBlink {
            0%, 90%, 100% { transform: scaleY(1); }
            95% { transform: scaleY(0.1); }
        }
        .animate-blink {
            animation: eyeBlink 5s infinite;
            transform-origin: center;
        }

        /* Compass Spinning Animation */
        @keyframes compassSpin {
            0% { transform: rotate(0deg); }
            30% { transform: rotate(180deg); }
            50% { transform: rotate(160deg); }
            80% { transform: rotate(360deg); }
            100% { transform: rotate(360deg); }
        }
        .animate-compass {
            animation: compassSpin 6s infinite ease-in-out;
            transform-origin: center;
        }

        /* Floating Animation */
        @keyframes mascotFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        .animate-float {
            animation: mascotFloat 4s infinite ease-in-out;
        }
    </style>
</head>
<body class="min-h-screen bg-[#F8FAFC] flex items-center justify-center p-6 relative overflow-hidden">
    
    <!-- Ambient Background Gradients -->
    <div class="absolute top-[-20%] right-[-10%] w-[600px] h-[600px] bg-blue-500/5 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-20%] left-[-10%] w-[500px] h-[500px] bg-teal-500/5 rounded-full blur-[100px] pointer-events-none"></div>

    <!-- Soft White Premium Card -->
    <main class="relative bg-white border border-slate-100 rounded-[2.5rem] p-8 sm:p-12 max-w-xl w-full text-center shadow-[0_20px_50px_rgba(0,0,0,0.03)] transition-all duration-300">
        
        <!-- Guard Mascot (Wira) illustration -->
        <div class="w-48 h-48 mx-auto mb-8 animate-float relative flex items-center justify-center">
            <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                <!-- Mascot Body (Friendly Guard Helmet) -->
                <circle cx="100" cy="100" r="60" fill="#E2E8F0" />
                <path d="M40 100C40 66.8629 66.8629 40 100 40C133.137 40 160 66.8629 160 100V120H40V100Z" fill="#334155" />
                <rect x="52" y="80" width="96" height="24" rx="8" fill="#1E293B" />
                
                <!-- Expressive Blinking Eyes -->
                <circle cx="80" cy="92" r="6" fill="#F8FAFC" class="animate-blink" />
                <circle cx="120" cy="92" r="6" fill="#F8FAFC" class="animate-blink" />
                <circle cx="80" cy="92" r="2.5" fill="#0F172A" class="animate-blink" />
                <circle cx="120" cy="92" r="2.5" fill="#0F172A" class="animate-blink" />
                
                <!-- Cheeks -->
                <circle cx="68" cy="104" r="5" fill="#FCA5A5" opacity="0.6" />
                <circle cx="132" cy="104" r="5" fill="#FCA5A5" opacity="0.6" />
                
                <!-- Compass / Map on hand -->
                <g class="animate-compass" transform="translate(100, 140)">
                    <!-- Outer Ring -->
                    <circle cx="0" cy="0" r="26" fill="#0284C7" />
                    <circle cx="0" cy="0" r="22" fill="#FFFBEB" />
                    <!-- Compass Needle -->
                    <path d="M0 -15L5 0L0 5L-5 0Z" fill="#EF4444" />
                    <path d="M0 15L5 0L0 -5L-5 0Z" fill="#38BDF8" />
                    <circle cx="0" cy="0" r="3" fill="#1E293B" />
                </g>
            </svg>
        </div>

        <!-- Error Code -->
        <span class="inline-flex px-4 py-1.5 bg-blue-50 border border-blue-100 text-blue-700 text-xs font-bold rounded-2xl uppercase tracking-wider mb-4 shadow-sm">
            Status: 404 Not Found
        </span>

        <!-- Description -->
        <h1 class="text-2xl font-black text-slate-800 tracking-tight leading-snug mb-3">
            Halaman Tidak Ditemukan
        </h1>
        <p class="text-slate-500 font-medium text-sm leading-relaxed max-w-md mx-auto mb-8">
            {{ $exception->getMessage() ?: 'Halaman yang Anda cari tidak ditemukan. Wira kehilangan jejak navigasi untuk tautan ini.' }}
        </p>

        <!-- Dynamic Action Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <button onclick="window.history.back()" class="w-full sm:w-auto px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold text-xs tracking-wider uppercase rounded-2xl transition-all active:scale-95 shadow-sm">
                &larr; Kembali
            </button>
            <a href="/dashboard" class="w-full sm:w-auto px-8 py-3 bg-blue-600 hover:bg-blue-750 text-white font-extrabold text-xs tracking-wider uppercase rounded-2xl transition-all active:scale-95 shadow-md shadow-blue-500/20">
                Ke Beranda Utama
            </a>
        </div>

    </main>

</body>
</html>
