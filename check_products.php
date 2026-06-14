<?php

use App\Models\Product;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

$products = Product::all();
echo 'Total Products: '.count($products)."\n";
foreach ($products as $p) {
    echo '- '.$p->name.' (Stock: '.$p->stock.', ID: '.$p->id.")\n";
}
