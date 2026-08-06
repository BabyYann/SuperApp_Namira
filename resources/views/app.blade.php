<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Namira School') }}</title>

        <!-- SEO & Meta Tags -->
        <meta name="description" content="Namira School (Yayasan Namira Probolinggo) adalah Sekolah Islam Terpadu unggulan multi-unit (Daycare, PAUD, TK, SD, SMP). Membentuk generasi Muslim berakhlak mulia & berprestasi.">
        <meta name="keywords" content="Namira School, Yayasan Namira, SD Namira, SMP Namira, TK Namira, KB Namira, Daycare Namira, Sekolah Islam Terpadu Probolinggo, PPDB Namira">
        <meta name="author" content="Namira School">
        <meta name="application-name" content="Namira School">
        <link rel="canonical" href="https://namiraschool.com">
        <link rel="sitemap" type="application/xml" title="Sitemap" href="/sitemap.xml">

        <!-- Open Graph / Facebook / WhatsApp -->
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Namira School">
        <meta property="og:url" content="https://namiraschool.com">
        <meta property="og:title" content="Namira School - Sekolah Islam Terpadu Probolinggo">
        <meta property="og:description" content="Namira School (Yayasan Namira Probolinggo) - Pendidikan Islam Terpadu berkualitas tinggi dari Daycare, PAUD, TK, SD, hingga SMP.">
        <meta property="og:image" content="{{ asset('images/namira-foundation-logo.webp') }}">

        <!-- PWA, Favicon & Meta -->
        <link rel="icon" type="image/webp" href="/images/namira-foundation-logo.webp">
        <link rel="shortcut icon" href="/images/namira-foundation-logo.webp">
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#009688">
        <link rel="apple-touch-icon" href="/images/namira-foundation-logo.webp">

        <!-- Schema.org JSON-LD Structured Data for Google Search & Sitelinks -->
        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@graph": [
            {
              "@type": "EducationalOrganization",
              "@id": "https://namiraschool.com/#organization",
              "name": "Namira School",
              "alternateName": ["Yayasan Namira", "Yayasan Namira Probolinggo", "Namira School Probolinggo"],
              "url": "https://namiraschool.com",
              "logo": "https://namiraschool.com/images/namira-foundation-logo.webp",
              "image": "https://namiraschool.com/images/namira-foundation-logo.webp",
              "description": "Namira School (Yayasan Namira Probolinggo) adalah Sekolah Islam Terpadu unggulan multi-unit (Daycare, PAUD, TK, SD, SMP). Membentuk generasi Muslim berakhlak mulia & berprestasi.",
              "address": {
                "@type": "PostalAddress",
                "addressLocality": "Probolinggo",
                "addressRegion": "Jawa Timur",
                "addressCountry": "ID"
              },
              "sameAs": [
                "https://www.facebook.com/namiraschool",
                "https://www.instagram.com/namiraschool"
              ]
            },
            {
              "@type": "WebSite",
              "@id": "https://namiraschool.com/#website",
              "url": "https://namiraschool.com",
              "name": "Namira School",
              "alternateName": ["Yayasan Namira", "Namira School Probolinggo"],
              "publisher": {
                "@id": "https://namiraschool.com/#organization"
              }
            },
            {
              "@type": "ItemList",
              "@id": "https://namiraschool.com/#sitelinks",
              "name": "Navigasi Utama Namira School",
              "itemListElement": [
                {
                  "@type": "SiteNavigationElement",
                  "position": 1,
                  "name": "Pendaftaran & PPDB Online",
                  "description": "Informasi & pendaftaran siswa baru (PPDB) Sekolah Islam Terpadu Namira",
                  "url": "https://namiraschool.com/ppdb"
                },
                {
                  "@type": "SiteNavigationElement",
                  "position": 2,
                  "name": "Portal Karir & Rekrutmen",
                  "description": "Peluang karir dan rekrutmen guru serta staf kependidikan Namira School",
                  "url": "https://namiraschool.com/karir"
                },
                {
                  "@type": "SiteNavigationElement",
                  "position": 3,
                  "name": "Berita & Pengumuman",
                  "description": "Kabar berita, artikel, dan pengumuman terbaru Namira School",
                  "url": "https://namiraschool.com/berita"
                },
                {
                  "@type": "SiteNavigationElement",
                  "position": 4,
                  "name": "Agenda & Kegiatan",
                  "description": "Jadwal kegiatan, agenda sekolah, dan event resmi Namira School",
                  "url": "https://namiraschool.com/events"
                },
                {
                  "@type": "SiteNavigationElement",
                  "position": 5,
                  "name": "Testimoni & Prestasi",
                  "description": "Ulasan wali murid, alumni, dan pencapaian prestasi siswa Namira",
                  "url": "https://namiraschool.com/testimonials"
                }
              ]
            }
          ]
        }
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        
        <!-- Plus Jakarta Sans Font -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
        
        <style>
            body, * { font-family: 'Plus Jakarta Sans', sans-serif; }
        </style>

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
