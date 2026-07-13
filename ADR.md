# Architecture Decision Records (ADR)

## ADR-001: Modular Monolith Architecture
**Status**: Accepted
**Context**: Aplikasi memiliki banyak domain (Akademik, Absensi, Yayasan, NonFormal) yang kompleks tetapi saling terkait. Microservices dianggap terlalu kompleks untuk stage ini.
**Decision**: Menggunakan struktur Modular Monolith. Code akan dikelompokkan berdasarkan domain bisnis di `app/Modules/` bukan hanya berdasarkan layer teknis (Controller, Model).
**Consequences**: Memerlukan disiplin tinggi dalam dependency antar module.

## ADR-002: Tech Stack & Frontend Strategy
**Status**: Accepted
**Context**: Need modern SPA feel but with robust backend integration and SEO capabilities (though internal app).
**Decision**: Laravel 12 + Inertia.js + Vue 3.
**Reason**: Inertia memberikan UX seperti SPA tanpa kompleksitas routing API terpisah.

## ADR-003: Granular RBAC & Scoping
**Status**: Accepted
**Context**: User bisa punya jabatan berbeda di unit berbeda (e.g., Guru di SD, Ortu di TK).
**Decision**: Menggunakan `spatie/laravel-permission` dengan custom logic untuk Multi-Tenancy/Scoping.
**Implementation**: Middleware akan mengecek `active_unit_scope` session.
