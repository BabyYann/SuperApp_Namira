# SuperApp Namira - Agent Instructions

## Project Identity
Multi-unit school management platform for Yayasan Namira (SD, SMP, SMA, plus non-formal units).
- **Stack**: Laravel 12 (PHP 8.4) + Vue 3 + Inertia.js + Tailwind CSS
- **Auth**: Laravel Breeze + Spatie Laravel Permission (RBAC with teams/multi-tenancy)
- **Database**: MySQL (`superapp_namira`)
- **Icons**: Heroicons v2 (`@heroicons/vue/24/outline`) — **NO EMOJIS as UI icons**
  - Use Heroicons for Vue components
  - Use inline SVG for Leaflet popups or HTML strings
  - Use descriptive text labels (Alumni, Kunjungan, Nasional, Internasional)
  - Use CSS-styled badges/pills with color
- **Alerts**: SweetAlert2
- **Map**: Leaflet.js

## Dev Commands
```bash
# Full dev server (artisan + vite + queue + pail)
composer dev

# Individual
php artisan serve          # Backend (http://127.0.0.1:8000)
npm run dev                # Vite HMR

# Build
npm run build              # Production frontend

# Lint / Format
npm run lint               # ESLint (resources/js/**/*.{js,vue})
./vendor/bin/pint          # Laravel Pint (PHP CS Fixer)

# Test (uses Pest, SQLite :memory:)
composer test              # Clears config cache then runs artisan test
php artisan test           # Run all tests
php artisan test --filter=TestName   # Single test
```

## Architecture: Modular Monolith
Backend code is organized by **domain** under `app/Modules/`, not by layer:
```
app/Modules/
  Academic/     Controllers, Models, Services, Requests, Exceptions
  Counseling/   Controllers, Models
  Employee/     Controllers, Models
  Finance/      Controllers, Models, Routes
  LMS/          Controllers/{Guru,Siswa}, Models
  PublicRelations/ Controllers
  Sarpar/       Controllers, Models, Services, Exports
  Student/      Controllers
  Yayasan/      Controllers, Models
```

**Service Layer Pattern**: Business logic goes in Services, not Controllers. Follow this pattern when adding new features.

## RBAC & Unit Scoping (Critical)

### Roles (Spatie Permission with teams=true)
Global roles: `super_admin_yayasan`, `admin_yayasan`, `staff_yayasan`
Unit-scoped roles: `admin_unit`, `staff_unit`, `teacher`, `wali_kelas`, `bk`, `siswa`, `koordinator_sarpar`, `finance`, etc.

### Unit Scope System
- Session key: `active_unit_id` — set by `CheckUnitScope` middleware
- Spatie team scope: `setPermissionsTeamId($unitId)` applied automatically for non-global roles
- **All data queries MUST be filtered by `unit_id`** — controllers must respect the active unit scope
- Super Admin can switch units via TopBar button (route: `yayasan.switch-unit`)

### Middleware Stack
| Middleware | Purpose |
|---|---|
| `CheckUnitScope` | Sets `active_unit_id` session + Spatie team scope |
| `CheckMaintenanceMode` | Blocks all users except `super_admin_yayasan` |
| `CheckFeatureEnabled` | Blocks module if feature flag is `'0'` (super_admin bypasses) |
| `EnsureStudentAccess` | Guards student portal routes |

### Feature Flags (from `system_settings` cache)
`feature_finance`, `feature_sarpar`, `feature_counseling`, `feature_academic`, `feature_employee`, `feature_student_login`
- Accessed via middleware: `->middleware('feature:feature_finance')`
- Super Admin always bypasses feature checks

## Frontend Structure
```
resources/js/
  Layouts/AuthenticatedLayout.vue    # Main layout (Sidebar + TopBar)
  Components/Dashboard/
    Sidebar.vue                       # Dynamic per role
    TopBar.vue                        # Avatar, notifications, unit switcher
  Pages/
    Yayasan/Settings/Index.vue        # Super Admin control center
    Academic/, Finance/, Employee/, Counseling/, Sarpar/, Student/, Public/
```

### Shared Inertia Props (available in all Vue pages)
`$page.props.auth.user`, `$page.props.app_settings` (cached system settings with feature flags)

## Key Gotchas
1. **Unit scope is mandatory**: Every query filtering data must use the active unit. Check `CheckUnitScope` middleware.
2. **Cache settings**: After changing `system_settings`, run `php artisan cache:clear`. Settings are cached forever via `Cache::rememberForever`.
3. **Storage link**: Run `php artisan storage:link` after setup for file uploads.
4. **Spatie teams**: `team_id` on `model_has_roles` = `unit_id`. Global roles have `team_id = NULL`.
5. **Route groups**: Most module routes are nested under `/yayasan/` prefix with role middleware.
6. **Pest tests**: Use SQLite `:memory:`. Feature tests get `RefreshDatabase` automatically.
7. **Student dashboard redirect**: `/dashboard` automatically redirects `siswa` role to `/student/dashboard`.
8. **Vite config**: Ignores `storage/framework/views/**` from file watching.

## Database Key Points
- `system_settings` table stores all global config (app name, logo, feature flags)
- `academic_years` has `is_active` flag — only one active at a time
- Finance uses VA number mapping: `students.va_number` links to bank transactions
- `class_promotions` tracks student year-over-year promotion with rollback support

## Code Style
- PHP: 4 spaces, PSR-12 (enforced by Pint)
- JS/Vue: 4 spaces, single quotes, Prettier with `prettier-plugin-organize-imports` + `prettier-plugin-tailwindcss`
- ESLint: `vue/multi-word-component-names: off`, `no-undef: off`

## Reference Docs
- `PROJECT_MEMORY.md` — Full schema, routes, and architecture reference
- `memory.md` — Detailed DB schema, middleware docs, and integration map
- `ADR.md` — Architecture Decision Records
- `knowledge-base/` — Domain analysis and business flow documentation
