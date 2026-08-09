<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$projects = \App\Models\Project::select('id','name','slug')->get();
foreach ($projects as $p) {
    echo $p->id . ' | ' . $p->name . ' | ' . $p->slug . "\n";
}
