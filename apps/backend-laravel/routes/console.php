<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('autosync:about', function (): void {
    $this->info('AutoSync Hub Laravel backend');
})->purpose('Show project information');
