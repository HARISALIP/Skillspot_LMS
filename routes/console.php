<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\MarketplaceService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('marketplace:release-earnings', function () {
    $released = MarketplaceService::releaseAvailableEarnings();
    $this->info("Released {$released} marketplace earnings.");
})->purpose('Move marketplace earnings past the seven-day refund hold to available.');

Schedule::command('marketplace:release-earnings')->everyMinute()->withoutOverlapping();
