<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Modules\Addons\ScrollingNotice\Models\ScrollingNotice;

add_hook('ClientAreaTopOutput', 5, function (array $vars) {
    try {
        if (! Schema::hasTable('scrolling_notices')) {
            return null;
        }

        $notices = ScrollingNotice::visibleNow(auth('web')->check())->limit(5)->get();
        if ($notices->isEmpty()) {
            return null;
        }

        return View::make('scrollingnotice::bar', [
            'notices' => $notices,
        ])->render();
    } catch (Throwable $e) {
        report($e);

        return null;
    }
});
