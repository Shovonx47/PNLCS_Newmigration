<?php

namespace Modules\Addons\SoftcentsSms\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Addons\SoftcentsSms\Services\OtpService;

/**
 * The three calls the registration page makes. Guests only: a signed-in
 * customer is not opening an account.
 */
class OtpController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    public function send(Request $request): JsonResponse
    {
        abort_if(auth()->check(), 403);

        $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'prefix' => ['nullable', 'string', 'max:6'],
        ]);

        return response()->json($this->otp->start((string) $request->input('phone'), (string) $request->input('prefix', '')))
            ->header('Cache-Control', 'no-store');
    }

    public function verify(Request $request): JsonResponse
    {
        abort_if(auth()->check(), 403);

        $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'prefix' => ['nullable', 'string', 'max:6'],
            'otp' => ['required', 'string', 'max:10'],
        ]);

        return response()->json($this->otp->verify(
            (string) $request->input('phone'),
            (string) $request->input('otp'),
            (string) $request->input('prefix', '')
        ))->header('Cache-Control', 'no-store');
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'enabled' => $this->otp->enabled(),
            'verified_phone' => $this->otp->verifiedPhone(),
        ])->header('Cache-Control', 'no-store');
    }
}
