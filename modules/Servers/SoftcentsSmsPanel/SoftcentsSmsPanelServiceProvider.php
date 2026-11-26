<?php

namespace Modules\Servers\SoftcentsSmsPanel;

use Illuminate\Support\ServiceProvider;

class SoftcentsSmsPanelServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'softcents_sms_panel');
    }
}
