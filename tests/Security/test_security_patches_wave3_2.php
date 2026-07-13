<?php
/**
 * Security Patch Wave 3.2 - Verification Test Script
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

// -----------------------------------------------------------------------------
// 1. UnitController: switch
// -----------------------------------------------------------------------------
$c = readController('app/Modules/Yayasan/Controllers/UnitController.php');
test('UnitController: switch() has role/team verification', 
    str_contains($c, 'model_has_roles') && str_contains($c, 'team_id') && str_contains($c, "public function switch"));

// -----------------------------------------------------------------------------
// 2. AttendanceLocationController: store, update, destroy
// -----------------------------------------------------------------------------
$c = readController('app/Modules/Yayasan/Controllers/AttendanceLocationController.php');
test('AttendanceLocationController: store() validates unit isolation', 
    (bool)preg_match('/public function store.*?unit_id.*?session\(\'active_unit_id\'\)/s', $c));

test('AttendanceLocationController: update() validates model and input unit isolation', 
    str_contains($c, 'attendanceLocation->unit_id != session(\'active_unit_id\')') 
    && str_contains($c, 'validated[\'unit_id\'] != session(\'active_unit_id\')'));

test('AttendanceLocationController: destroy() validates model unit isolation', 
    str_contains($c, 'attendanceLocation->unit_id != session(\'active_unit_id\')') && (bool)preg_match('/public function destroy.*?abort\(403/s', $c));

// -----------------------------------------------------------------------------
// 3. HolidayController: store, update, destroy
// -----------------------------------------------------------------------------
$c = readController('app/Modules/Yayasan/Controllers/HolidayController.php');
test('HolidayController: store() blocks teacher', 
    (bool)preg_match('/public function store.*?hasAnyRole\(\[\'super_admin_yayasan\', \'admin_yayasan\', \'admin_unit\', \'staff_yayasan\', \'staff_unit\'\]\)/s', $c));

test('HolidayController: store() restricts global event creation', 
    str_contains($c, 'targetUnitId === null') && (bool)preg_match('/public function store.*?abort\(403/s', $c));

test('HolidayController: update() blocks teacher', 
    (bool)preg_match('/public function update.*?hasAnyRole\(\[\'super_admin_yayasan\', \'admin_yayasan\', \'admin_unit\', \'staff_yayasan\', \'staff_unit\'\]\)/s', $c));

test('HolidayController: update() restricts global/cross-unit modification', 
    str_contains($c, 'holiday->unit_id === null || $holiday->unit_id != session(\'active_unit_id\')'));

test('HolidayController: update() prevents updating unit to null/other unit', 
    str_contains($c, 'targetUnitId === null || $targetUnitId != session(\'active_unit_id\')') && (bool)preg_match('/public function update.*?abort\(403/s', $c));

test('HolidayController: destroy() blocks teacher', 
    (bool)preg_match('/public function destroy.*?hasAnyRole\(\[\'super_admin_yayasan\', \'admin_yayasan\', \'admin_unit\', \'staff_yayasan\', \'staff_unit\'\]\)/s', $c));

test('HolidayController: destroy() restricts global/cross-unit deletion', 
    str_contains($c, 'holiday->unit_id === null || $holiday->unit_id != session(\'active_unit_id\')') && (bool)preg_match('/public function destroy.*?abort\(403/s', $c));

// -----------------------------------------------------------------------------
// 4. MonitoringController: index
// -----------------------------------------------------------------------------
$c = readController('app/Modules/Yayasan/Controllers/MonitoringController.php');
test('MonitoringController: index() has explicit role check', 
    (bool)preg_match('/public function index.*?hasAnyRole\(\[\'super_admin_yayasan\', \'admin_yayasan\', \'admin_unit\', \'staff_yayasan\', \'staff_unit\'\]\)/s', $c));

test('MonitoringController: index() forces active_unit_id for non-global admin', 
    str_contains($c, 'unitId = session(\'active_unit_id\')') && str_contains($c, 'auth()->user()->hasAnyRole([\'super_admin_yayasan\', \'admin_yayasan\'])'));

// -----------------------------------------------------------------------------
// 5. AcademicYearController: store, update, setAsActive, destroy
// -----------------------------------------------------------------------------
$c = readController('app/Modules/Yayasan/Controllers/AcademicYearController.php');
test('AcademicYearController: store() blocks admin_unit and teacher', 
    str_contains($c, 'Hanya pihak Yayasan yang dapat mengelola Tahun Akademik') 
    && !str_contains($c, "'admin_unit'"));

test('AcademicYearController: update() blocks admin_unit and teacher', 
    str_contains($c, 'Hanya pihak Yayasan yang dapat mengelola Tahun Akademik') 
    && (bool)preg_match('/public function update.*?abort\(403/s', $c));

test('AcademicYearController: setAsActive() blocks admin_unit and teacher', 
    str_contains($c, 'Hanya pihak Yayasan yang dapat mengubah Tahun Akademik aktif.') 
    && (bool)preg_match('/public function setAsActive.*?abort\(403/s', $c));

test('AcademicYearController: destroy() blocks admin_unit and teacher', 
    str_contains($c, 'Hanya pihak Yayasan yang dapat menghapus Tahun Akademik.') 
    && (bool)preg_match('/public function destroy.*?abort\(403/s', $c));


// -----------------------------------------------------------------------------
// Output results
// -----------------------------------------------------------------------------
echo PHP_EOL;
echo "==============================================================" . PHP_EOL;
echo "  SECURITY PATCH WAVE 3.2 - VERIFICATION RESULTS" . PHP_EOL;
echo "==============================================================" . PHP_EOL;

$prevController = '';
foreach ($results as [$status, $name, $detail]) {
    $controller = explode(':', $name)[0];
    if ($controller !== $prevController) {
        echo PHP_EOL . "  -- $controller --" . PHP_EOL;
        $prevController = $controller;
    }
    $icon = $status === 'PASS' ? '✓' : '✗';
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
    echo PHP_EOL . "  SUCCESS: All Wave 3.2 patches verified." . PHP_EOL;
    exit(0);
}
