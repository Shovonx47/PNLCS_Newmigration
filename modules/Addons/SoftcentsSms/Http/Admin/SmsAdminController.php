<?php

namespace Modules\Addons\SoftcentsSms\Http\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Addons\SoftcentsSms\Models\SmsLog;
use Modules\Addons\SoftcentsSms\Models\SmsSetting;
use Modules\Addons\SoftcentsSms\Models\SmsTemplate;
use Modules\Addons\SoftcentsSms\Services\SmsClient;
use Modules\Addons\SoftcentsSms\Services\SmsDispatcher;

/**
 * Extensions -> Softcents SMS.
 *
 * One screen: credentials and the OTP gate, the live gateway balance, the
 * templates with their switches, a test send, and the last messages that went
 * out. A failed send is visible here instead of in a log file on the server,
 * which is where the legacy addon left it.
 */
class SmsAdminController extends Controller
{
    public function index()
    {
        $settings = SmsSetting::current();
        $balance = $settings->configured()
            ? (new SmsClient($settings))->balance()
            : ['success' => false, 'balance' => '—', 'error' => 'Add the client id, secret and sender id to see the balance.'];

        return view('softcentssms::admin.index', [
            'settings' => $settings,
            'balance' => $balance,
            'templates' => SmsTemplate::orderBy('type')->orderBy('name')->get(),
            'logs' => SmsLog::with('client')->latest('id')->limit(50)->get(),
            'sentToday' => SmsLog::where('status', 'sent')->whereDate('created_at', today())->count(),
            'failedToday' => SmsLog::where('status', 'failed')->whereDate('created_at', today())->count(),
            'adminCount' => $settings->adminNumberList() === [] ? 0 : count($settings->adminNumberList()),
            'clientCount' => Client::count(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['nullable', 'string', 'max:100'],
            'client_secret' => ['nullable', 'string', 'max:255'],
            'sender_id' => ['nullable', 'string', 'max:50'],
            'date_format' => ['nullable', 'string', 'max:20'],
            'admin_numbers' => ['nullable', 'string', 'max:500'],
            'admin_alerts' => ['nullable', 'boolean'],
            'otp_enabled' => ['nullable', 'boolean'],
            'otp_checkout_enabled' => ['nullable', 'boolean'],
            'otp_length' => ['nullable', 'integer', 'min:4', 'max:8'],
            'otp_expiry_minutes' => ['nullable', 'integer', 'min:1', 'max:60'],
            'otp_resend_cooldown' => ['nullable', 'integer', 'min:0', 'max:600'],
            'otp_max_per_hour' => ['nullable', 'integer', 'min:1', 'max:50'],
            'otp_template' => ['nullable', 'string', 'max:1000'],
        ]);

        $settings = SmsSetting::current();

        $settings->fill([
            'client_id' => $validated['client_id'] ?? '',
            'sender_id' => $validated['sender_id'] ?? '',
            'date_format' => $validated['date_format'] ?? 'd-m-Y',
            'admin_numbers' => $validated['admin_numbers'] ?? '',
            'admin_alerts' => $request->boolean('admin_alerts'),
            'otp_enabled' => $request->boolean('otp_enabled'),
            'otp_checkout_enabled' => $request->boolean('otp_checkout_enabled'),
            'otp_length' => $validated['otp_length'] ?? 6,
            'otp_expiry_minutes' => $validated['otp_expiry_minutes'] ?? 5,
            'otp_resend_cooldown' => $validated['otp_resend_cooldown'] ?? 60,
            'otp_max_per_hour' => $validated['otp_max_per_hour'] ?? 5,
            'otp_template' => $validated['otp_template'] ?? '',
        ]);

        // An empty secret field means "keep the one already stored": the form
        // never prints it back, so a blank box cannot wipe it by accident.
        if (trim((string) $request->input('client_secret')) !== '') {
            $settings->client_secret = (string) $request->input('client_secret');
        }

        $settings->save();

        return back()->with('success', 'SMS settings saved.');
    }

    public function updateTemplate(Request $request, SmsTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'template' => ['nullable', 'string', 'max:2000'],
            'active' => ['nullable', 'boolean'],
        ]);

        $template->update([
            'template' => $validated['template'] ?? '',
            'active' => $request->boolean('active'),
        ]);

        return back()->with('success', $template->name.' saved.');
    }

    public function test(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:600'],
        ]);

        $log = app(SmsDispatcher::class)->sendNow(
            $validated['number'],
            $validated['message'],
            null,
            'Test'
        );

        return back()->with(
            $log->status === 'sent' ? 'success' : 'error',
            $log->status === 'sent'
                ? 'Test SMS sent to '.$log->to_number.'.'
                : 'Test SMS failed: '.$log->error
        );
    }
}
