<?php
/**
 * Security Patch Wave 3.3 - Verification Test Script
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
// CounselingSessionController: store
// -----------------------------------------------------------------------------
$c = readController('app/Modules/Counseling/Controllers/CounselingSessionController.php');

test('CounselingSessionController: store() has role/permission team verification for student_id', 
    str_contains($c, 'Student::findOrFail') 
    && str_contains($c, 'student->unit_id != session(\'active_unit_id\')') 
    && str_contains($c, 'abort(403, \'Akses Ditolak: Siswa berada pada unit lain.\')'));

test('CounselingSessionController: store() allows global admin bypass', 
    str_contains($c, "!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])"));

// -----------------------------------------------------------------------------
// Output results
// -----------------------------------------------------------------------------
echo PHP_EOL;
echo "==============================================================" . PHP_EOL;
echo "  SECURITY PATCH WAVE 3.3 - VERIFICATION RESULTS" . PHP_EOL;
echo "==============================================================" . PHP_EOL;

foreach ($results as [$status, $name, $detail]) {
    $icon = $status === 'PASS' ? '✓' : '✗';
    printf("  [%s] %s\n", $status, $name);
}

echo PHP_EOL;
$total = $pass + $fail;
echo "--------------------------------------------------------------" . PHP_EOL;
printf("  TOTAL: %d | PASS: %d | FAIL: %d | RATE: %.1f%%\n", $total, $pass, $fail, $total > 0 ? ($pass/$total)*100 : 0);
echo "--------------------------------------------------------------" . PHP_EOL;

if ($fail > 0) {
    echo PHP_EOL . "  WARNING: Some tests FAILED. Review store() method." . PHP_EOL;
    exit(1);
} else {
    echo PHP_EOL . "  SUCCESS: Wave 3.3 patch verified." . PHP_EOL;
    exit(0);
}
