<?php

use Modules\Addons\ForceClientActive\Services\ForceClientActiveService;

add_hook('DailyCronJob', 15, function () {
    try {
        app(ForceClientActiveService::class)->run();
    } catch (Throwable $e) {
        report($e);
    }
});
