<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

try {
    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `news` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `events` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'upcoming'");
    echo "SUCCESS: News and Events status column altered to VARCHAR(50).";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage();
}
