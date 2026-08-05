<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Namira App') }}</title>

        <!-- SEO & Meta Tags -->
        <meta name="description" content="Yayasan Namira - Multi-unit School Management Platform (SD, SMP, TK, KB, Daycare). Pendidikan Islam Terpadu berkualitas tinggi.">
        <meta name="keywords" content="Yayasan Namira, Namira School, SD Namira, SMP Namira, TK Namira, KB Namira, Daycare Namira, Sekolah Islam Probolinggo">
        <meta name="author" content="Yayasan Namira">
        <link rel="canonical" href="{{ config('app.url', 'https://namiraschool.com') }}">
        <link rel="sitemap" type="application/xml" title="Sitemap" href="/sitemap.xml">

        <!-- Open Graph / Facebook / WhatsApp -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ config('app.url', 'https://namiraschool.com') }}">
        <meta property="og:title" content="Yayasan Namira - Sekolah Islam Terpadu">
        <meta property="og:description" content="Pendidikan Islam Terpadu berkualitas tinggi dari Daycare, KB, TK, SD, hingga SMP Namira.">
        <meta property="og:image" content="{{ asset('images/namira-foundation-logo.webp') }}">

        <!-- PWA, Favicon & Meta -->
        <link rel="icon" type="image/webp" href="/images/namira-foundation-logo.webp">
        <link rel="shortcut icon" href="/images/namira-foundation-logo.webp">
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#009688">
        <link rel="apple-touch-icon" href="/images/namira-foundation-logo.webp">

        <!-- Schema.org JSON-LD Structured Data for Google Sitelinks -->
        <script type="application/ld+json">
        {
          "@@context": "https://schema.org",
          "@@graph": [
            {
              "@@type": "EducationalOrganization",
              "@@id": "{{ config('app.url', 'https://namiraschool.com') }}/#organization",
              "name": "Yayasan Namira",
              "url": "{{ config('app.url', 'https://namiraschool.com') }}",
              "logo": "{{ asset('images/namira-foundation-logo.webp') }}",
              "description": "Yayasan Namira - Sekolah Islam Terpadu (SD, SMP, TK, KB, Daycare)",
              "sameAs": [
                "https://www.facebook.com/namiraschool",
                "https://www.instagram.com/namiraschool"
              ]
            },
            {
              "@@type": "WebSite",
              "@@id": "{{ config('app.url', 'https://namiraschool.com') }}/#website",
              "url": "{{ config('app.url', 'https://namiraschool.com') }}",
              "name": "Yayasan Namira School",
              "publisher": {
                "@@id": "{{ config('app.url', 'https://namiraschool.com') }}/#organization"
              }
            },
            {
              "@@type": "SiteNavigationElement",
              "name": ["Berita & Artikel", "Agenda & Kegiatan", "SD Namira", "SMP Namira", "TK Namira", "KB Namira"],
              "url": [
                "{{ config('app.url', 'https://namiraschool.com') }}/berita",
                "{{ config('app.url', 'https://namiraschool.com') }}/events",
                "{{ config('app.url', 'https://namiraschool.com') }}/unit/sd",
                "{{ config('app.url', 'https://namiraschool.com') }}/unit/smp",
                "{{ config('app.url', 'https://namiraschool.com') }}/unit/tk",
                "{{ config('app.url', 'https://namiraschool.com') }}/unit/kb"
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
