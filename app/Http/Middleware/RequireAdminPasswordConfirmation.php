<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminPasswordConfirmation
{
    /** WHMCS administrative re-authentication validity window (15 minutes). */
    public const TIMEOUT_SECONDS = 900;

    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();
        if (! $admin) {
            return redirect()->route('admin.login');
        }

        $confirmedAt = (int) $request->session()->get('admin_password_confirmed_at', 0);
        $isConfirmed = $confirmedAt > 0 && (time() - $confirmedAt) <= self::TIMEOUT_SECONDS;

        if ($isConfirmed) {
            return $next($request);
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            $request->session()->put('admin_password_confirm_intended', $request->fullUrl());

            return response()->view('admin.auth.confirm-password', [
                'pageTitle' => 'Administrators',
                'intendedUrl' => $request->fullUrl(),
            ]);
        }

        return redirect()
            ->route('admin.config.admins')
            ->withErrors(['password' => 'Please confirm your password to continue.']);
    }
}
