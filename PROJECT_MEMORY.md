# PROJECT MEMORY: SUPER APPS YAYASAN NAMIRA

## 🎯 Visi & Scope
Aplikasi terpusat untuk manajemen Yayasan Namira, mencakup unit Formal (PAUD, TK, SD, SMP) dan Non-Formal (Pavlov Center, Day Care).
**Sifat**: Single System, Scalable, Premium UX.

## 🧱 Tech Stack
- **Backend**: Laravel 12 (PHP 8.3+)
- **Frontend**: Vue 3 + Inertia.js
- **Styling**: Tailwind CSS (Premium Design System)
- **Database**: MySQL/MariaDB
- **Auth**: Laravel Breeze/Fortify + Spatie Permission

## 🏛️ Architecture Rules
1.  **Modular Monolith**: Folder `app/Modules/` untuk pemisahan domain (Akademik, Yayasan, dll).
2.  **Service Layer Pattern**: Logika bisnis di Service, bukan Controller.
3.  **Strict Typing**: PHP 8.3 features.
4.  **Security**: Granular RBAC (Role-Based Access Control) dengan Scope Unit.

## 📂 Module Structure (app/Modules/)
- Akademik/
- Absensi/
- NonFormal/
- Yayasan/
