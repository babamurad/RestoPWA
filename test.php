<?php
require 'vendor/autoload.php'; 
$app = require_once 'bootstrap/app.php'; 
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); 
echo json_encode(\DB::table('restaurants')->limit(5)->get(['id', 'vendor_id']));
