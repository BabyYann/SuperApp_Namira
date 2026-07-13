<?php
/**
 * Security Patch Wave 3 - Verification Test Script
 */

define('BASE_PATH', dirname(__DIR__, 2));

$pass = 0;
$fail = 0;
$results = [];

function test(string $name, bool $condition, string $detail = ''): void {
    global $pass, $fail, $results;
    if ($condition) { $pass++; $results[] = ['PASS', $name, $detail]; }
    else { $fail++; $results[] = ['FAIL', $name, $detail]; }
}

function readController(string $relativePath): string {
    $path = BASE_PATH . '/' . ltrim($relativePath, '/');
    return file_exists($path) ? file_get_contents($path) : '';
}

// 1. UnitController
$c = readController('app/Modules/Yayasan/Controllers/UnitController.php');
test('UnitController: show() has unit isolation', str_contains($c, 'abort(403') && str_contains($c, 'public function show'));
test('UnitController: update() has unit isolation', (bool)preg_match('/public function update.*?abort\(403/s', $c));
test('UnitController: destroy() has unit isolation', (bool)preg_match('/public function destroy.*?abort\(403/s', $c));
test('UnitController: switch() validates team membership', str_contains($c, 'model_has_roles') || str_contains($c, 'setPermissionsTeamId'));

// 2. AcademicYearController
$c = readController('app/Modules/Yayasan/Controllers/AcademicYearController.php');
test('AcademicYearController: store() blocks non-yayasan', str_contains($c, 'Hanya pihak Yayasan yang dapat mengelola Tahun Akademik'));
test('AcademicYearController: update() blocks non-yayasan', (bool)preg_match('/public function update.*?abort\(403/s', $c));
test('AcademicYearController: setAsActive() blocks non-yayasan', str_contains($c, 'mengubah Tahun Akademik aktif'));
test('AcademicYearController: destroy() blocks non-yayasan', (bool)preg_match('/public function destroy.*?abort\(403/s', $c));

// 3. AttendanceApprovalController
$c = readController('app/Modules/Yayasan/Controllers/AttendanceApprovalController.php');
test('AttendanceApprovalController: index() has role check', str_contains($c, 'persetujuan absensi'));
test('AttendanceApprovalController: update() has role check', str_contains($c, 'memproses persetujuan absensi'));
test('AttendanceApprovalController: update() has employee unit isolation', str_contains($c, 'model_has_roles') && str_contains($c, 'user_id'));

// 4. AttendanceDataController
$c = readController('app/Modules/Yayasan/Controllers/AttendanceDataController.php');
test('AttendanceDataController: index() blocks teachers', str_contains($c, 'data absensi karyawan'));
test('AttendanceDataController: index() enforces unit isolation', str_contains($c, 'data absensi unit lain'));
test('AttendanceDataController: export() blocks teachers', str_contains($c, 'mengekspor data absensi'));
test('AttendanceDataController: export() enforces unit isolation', str_contains($c, 'mengekspor data absensi unit lain'));
test('AttendanceDataController: exportPdf() blocks teachers', str_contains($c, 'mengekspor PDF absensi'));
test('AttendanceDataController: exportPdf() enforces unit isolation', str_contains($c, 'PDF absensi unit lain'));

// 5. AttendanceLocationController
$c = readController('app/Modules/Yayasan/Controllers/AttendanceLocationController.php');
test('AttendanceLocationController: store() has role check', str_contains($c, 'mengelola lokasi absensi'));
test('AttendanceLocationController: store() has unit isolation', str_contains($c, 'menambahkan lokasi absensi untuk unit lain'));
test('AttendanceLocationController: update() pre-isolation', str_contains($c, 'mengubah lokasi absensi unit lain'));
test('AttendanceLocationController: update() post-reassignment isolation', str_contains($c, 'memindahkan lokasi absensi ke unit lain'));
test('AttendanceLocationController: destroy() has isolation', str_contains($c, 'menghapus lokasi absensi unit lain'));

// 6. HolidayController
$c = readController('app/Modules/Yayasan/Controllers/HolidayController.php');
test('HolidayController: store() has role check', str_contains($c, 'mengelola kalender akademik'));
test('HolidayController: store() prevents global event creation', str_contains($c, 'hanya dapat menambahkan event untuk unit Anda'));
test('HolidayController: update() has isolation', str_contains($c, 'mengubah event global atau event unit lain'));
test('HolidayController: destroy() has isolation', str_contains($c, 'menghapus event global atau event unit lain'));

// 7. MonitoringController
$c = readController('app/Modules/Yayasan/Controllers/MonitoringController.php');
test('MonitoringController: non-global forced to active unit', str_contains($c, 'Non-global admin: always force to active unit'));
test('MonitoringController: global admin bypass', str_contains($c, 'Global admin: respect the input'));

// 8. ViolationCategoryController
$c = readController('app/Modules/Counseling/Controllers/ViolationCategoryController.php');
test('ViolationCategoryController: store() has role check', str_contains($c, 'Hanya Guru BK atau Admin yang dapat mengelola'));
test('ViolationCategoryController: update() has role check', (bool)preg_match('/public function update.*?Hanya Guru BK/s', $c));
test('ViolationCategoryController: update() has unit isolation', (bool)preg_match('/public function update.*?abort\(403\)/s', $c));
test('ViolationCategoryController: destroy() has role check', str_contains($c, 'Hanya Guru BK atau Admin yang dapat menghapus'));

// 9. AchievementController
$c = readController('app/Modules/Counseling/Controllers/AchievementController.php');
test('AchievementController: store() has student unit isolation', str_contains($c, 'Siswa tersebut berada di unit lain'));
test('AchievementController: global admin bypasses isolation', str_contains($c, "hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])"));

// 10. CounselingSessionController
$c = readController('app/Modules/Counseling/Controllers/CounselingSessionController.php');
test('CounselingSessionController: edit() has unit isolation', str_contains($c, 'Data sesi konseling ini berada di unit lain'));
test('CounselingSessionController: update() has unit isolation', (bool)preg_match('/public function update.*?Data sesi konseling/s', $c));
test('CounselingSessionController: destroy() has unit isolation', (bool)preg_match('/public function destroy.*?Data sesi konseling/s', $c));
test('CounselingSessionController: update() retains ownership check', (bool)preg_match('/public function update.*?counselor_id.*?Auth::id/s', $c));

// 11. EventController
$c = readController('app/Modules/PublicRelations/Controllers/EventController.php');
test('EventController: index() enforces unit scope', str_contains($c, 'For global admin: see all. Others: only see their unit'));
test('EventController: store() has unit isolation', str_contains($c, 'membuat acara untuk unit lain'));
test('EventController: update() pre-isolation check', str_contains($c, 'mengubah acara unit lain'));
test('EventController: update() post-reassignment isolation', str_contains($c, 'memindahkan acara ke unit lain'));
test('EventController: destroy() has unit isolation', str_contains($c, 'menghapus acara unit lain'));
test('EventController: uses session active_unit_id', substr_count($c, "session('active_unit_id')") >= 4);

// 12. NewsController
$c = readController('app/Modules/PublicRelations/Controllers/NewsController.php');
test('NewsController: store() has unit isolation', str_contains($c, 'membuat berita untuk unit lain'));
test('NewsController: edit() has unit isolation', str_contains($c, 'mengedit berita unit lain'));
test('NewsController: update() pre-isolation', str_contains($c, 'mengubah berita unit lain'));
test('NewsController: update() post-reassignment isolation', str_contains($c, 'memindahkan berita ke unit lain'));
test('NewsController: destroy() has unit isolation', str_contains($c, 'menghapus berita unit lain'));
test('NewsController: uses session active_unit_id', substr_count($c, "session('active_unit_id')") >= 4);

// 13. Sarpar CategoryController
$c = readController('app/Modules/Sarpar/Controllers/CategoryController.php');
test('Sarpar CategoryController: store() blocks non-yayasan', str_contains($c, 'mengelola kategori inventaris global'));
test('Sarpar CategoryController: update() blocks non-yayasan', (bool)preg_match('/public function update.*?mengelola kategori inventaris global/s', $c));
test('Sarpar CategoryController: destroy() blocks non-yayasan', str_contains($c, 'menghapus kategori inventaris global'));

// - Output --------------------------------------------------------------------
echo PHP_EOL;
echo "==============================================================" . PHP_EOL;
echo "  SECURITY PATCH WAVE 3 - VERIFICATION RESULTS" . PHP_EOL;
echo "==============================================================" . PHP_EOL;

$prevController = '';
foreach ($results as [$status, $name, $detail]) {
    $controller = explode(':', $name)[0];
    if ($controller !== $prevController) {
        echo PHP_EOL . "  -- $controller --" . PHP_EOL;
        $prevController = $controller;
    }
    $icon = $status === 'PASS' ? 'v' : 'X';
    $testLabel = trim(explode(':', $name, 2)[1] ?? $name);
    printf("  [%s] %s\n", $status, $testLabel);
}

echo PHP_EOL;
$total = $pass + $fail;
echo "--------------------------------------------------------------" . PHP_EOL;
printf("  TOTAL: %d | PASS: %d | FAIL: %d | RATE: %.1f%%\n", $total, $pass, $fail, $total > 0 ? ($pass/$total)*100 : 0);
echo "--------------------------------------------------------------" . PHP_EOL;

if ($fail > 0) {
    echo PHP_EOL . "  WARNING: Some tests FAILED. Review controllers above." . PHP_EOL;
    exit(1);
} else {
    echo PHP_EOL . "  SUCCESS: All Wave 3 patches verified." . PHP_EOL;
    exit(0);
}
