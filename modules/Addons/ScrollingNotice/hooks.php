<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Modules\Addons\ScrollingNotice\Models\ScrollingNotice;

add_hook('ClientAreaTopOutput', 5, function (array $vars) {
    try {
        if (! Schema::hasTable('scrolling_notices')) {
            return null;
        }

        $currentPath = '/'.ltrim((string) request()->getPathInfo(), '/');
        $currentUri = '/'.ltrim((string) request()->getRequestUri(), '/');

        $notices = ScrollingNotice::visibleNow(auth('web')->check())
            ->get()
            ->filter(function (ScrollingNotice $notice) use ($currentPath, $currentUri) {
                $rawTarget = trim((string) $notice->url_target);
                if ($rawTarget === '') {
                    return true;
                }

                // Relative targets act as WHMCS-style URL targeting so store-only
                // notices do not hijack unrelated pages such as Portal Home.
                $resolved = $notice->resolvedUrl();
                if ($resolved && str_starts_with($resolved, '/')) {
                    $targetPath = '/'.ltrim((string) (parse_url($resolved, PHP_URL_PATH) ?? $resolved), '/');
                    $rawNorm = '/'.ltrim($rawTarget, '/');

                    return $currentPath === $targetPath
                        || $currentUri === $rawNorm
                        || str_starts_with($currentPath.'/', $targetPath.'/');
                }

                return true;
            })
            ->take(5)
            ->values();

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
