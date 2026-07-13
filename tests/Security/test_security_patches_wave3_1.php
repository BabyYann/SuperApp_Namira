<?php
/**
 * Security Patch Wave 3.1 - Verification Test Script
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
// 1. UnitController Audit
// -----------------------------------------------------------------------------
$c = readController('app/Modules/Yayasan/Controllers/UnitController.php');

test('UnitController: show() has explicit role check', 
    (bool)preg_match('/public function show.*?hasAnyRole\(\[\'super_admin_yayasan\', \'admin_yayasan\', \'admin_unit\'/s', $c));

test('UnitController: show() has unit isolation validation', 
    (bool)preg_match('/public function show.*?session\(\'active_unit_id\'\)/s', $c));

test('UnitController: edit() has explicit role check', 
    (bool)preg_match('/public function edit.*?hasAnyRole\(\[\'super_admin_yayasan\', \'admin_yayasan\', \'admin_unit\'/s', $c));

test('UnitController: edit() has unit isolation validation', 
    (bool)preg_match('/public function edit.*?session\(\'active_unit_id\'\)/s', $c));

test('UnitController: update() has explicit role check', 
    (bool)preg_match('/public function update.*?hasAnyRole\(\[\'super_admin_yayasan\', \'admin_yayasan\', \'admin_unit\'/s', $c));

test('UnitController: update() has unit isolation validation', 
    (bool)preg_match('/public function update.*?session\(\'active_unit_id\'\)/s', $c));

test('UnitController: destroy() limits to global admin only', 
    (bool)preg_match('/public function destroy.*?hasAnyRole\(\[\'super_admin_yayasan\', \'admin_yayasan\'\]\).*?abort\(403/s', $c));

// -----------------------------------------------------------------------------
// 2. AttendanceApprovalController Audit
// -----------------------------------------------------------------------------
$c = readController('app/Modules/Yayasan/Controllers/AttendanceApprovalController.php');

test('AttendanceApprovalController: index() has explicit role check', 
    (bool)preg_match('/public function index.*?hasAnyRole\(\[\'super_admin_yayasan\', \'admin_yayasan\', \'admin_unit\'/s', $c));

test('AttendanceApprovalController: index() allows global admin query bypass', 
    str_contains($c, "if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']))") 
    && str_contains($c, 'model_has_roles.team_id = ?'));

test('AttendanceApprovalController: update() has explicit role check', 
    (bool)preg_match('/public function update.*?hasAnyRole\(\[\'super_admin_yayasan\', \'admin_yayasan\', \'admin_unit\'/s', $c));

test('AttendanceApprovalController: update() has employee unit isolation', 
    str_contains($c, 'model_has_roles') && str_contains($c, 'attendance->user_id') && str_contains($c, "session('active_unit_id')"));

// -----------------------------------------------------------------------------
// 3. AttendanceDataController Audit
// -----------------------------------------------------------------------------
$c = readController('app/Modules/Yayasan/Controllers/AttendanceDataController.php');

test('AttendanceDataController: index() has explicit role check', 
    (bool)preg_match('/public function index.*?hasAnyRole\(\[\'super_admin_yayasan\', \'admin_yayasan\', \'admin_unit\'/s', $c));

test('AttendanceDataController: index() uses filled() for unit_id input precision', 
    str_contains($c, "filled('unit_id')") && str_contains($c, "session('active_unit_id')"));

test('AttendanceDataController: index() has unit isolation validation', 
    str_contains($c, 'unitId != session(\'active_unit_id\')') && str_contains($c, 'abort(403'));

test('AttendanceDataController: export() has explicit role check', 
    (bool)preg_match('/public function export.*?hasAnyRole\(\[\'super_admin_yayasan\', \'admin_yayasan\', \'admin_unit\'/s', $c));

test('AttendanceDataController: export() has unit isolation validation', 
    str_contains($c, 'unitId != session(\'active_unit_id\')') && (bool)preg_match('/public function export.*?abort\(403/s', $c));

test('AttendanceDataController: exportPdf() has explicit role check', 
    (bool)preg_match('/public function exportPdf.*?hasAnyRole\(\[\'super_admin_yayasan\', \'admin_yayasan\', \'admin_unit\'/s', $c));

test('AttendanceDataController: exportPdf() has unit isolation validation', 
    str_contains($c, 'unitId != session(\'active_unit_id\')') && (bool)preg_match('/public function exportPdf.*?abort\(403/s', $c));

// -----------------------------------------------------------------------------
// Output results
// -----------------------------------------------------------------------------
echo PHP_EOL;
echo "==============================================================" . PHP_EOL;
echo "  SECURITY PATCH WAVE 3.1 - VERIFICATION RESULTS" . PHP_EOL;
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
    echo PHP_EOL . "  SUCCESS: All Wave 3.1 patches verified." . PHP_EOL;
    exit(0);
}
