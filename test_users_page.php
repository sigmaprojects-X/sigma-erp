<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cols = Illuminate\Support\Facades\Schema::getColumnListing('users');
echo "Columns in users: " . implode(', ', $cols) . "\n";

$gCols = Illuminate\Support\Facades\Schema::getColumnListing('user_groups');
echo "Columns in user_groups: " . implode(', ', $gCols) . "\n";
