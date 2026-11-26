<?php

namespace Modules\Servers\SoftcentsVsms;

use Illuminate\Support\ServiceProvider;

class SoftcentsVsmsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'softcents_vsms');
    }
}
