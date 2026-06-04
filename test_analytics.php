<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::find(3);
$request = new \Illuminate\Http\Request();
$request->setUserResolver(function() use ($user) { return $user; });

$controller = new \App\Http\Controllers\Api\TryoutController();
$response = $controller->getUserAnalytics($request);

echo json_encode($response);
