<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$analytics = \Illuminate\Support\Facades\Cache::get('user_analytics_3');
echo json_encode($analytics['history']);
