<?php

namespace Modules\Addons\SoftcentsVoice\Http\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductGroup;
use App\Support\BdPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Addons\SoftcentsVoice\Models\VoiceEvent;
use Modules\Addons\SoftcentsVoice\Models\VoiceGroupOverride;
use Modules\Addons\SoftcentsVoice\Models\VoiceLog;
use Modules\Addons\SoftcentsVoice\Models\VoiceRetry;
use Modules\Addons\SoftcentsVoice\Models\VoiceSettings;
use Modules\Addons\SoftcentsVoice\Services\VoiceClient;

/**
 * Extensions -> Softcents Voice.
 *
 * The window and the pause switch first (they are what stops a 3am call), then
 * the balance and the recordings, then the 24 events with their audio ids, the
 * per-group overrides, and what was actually dialled.
 */
class VoiceAdminController extends Controller
{
    public function index()
    {
        $settings = VoiceSettings::current();
        $client = new VoiceClient($settings);

        $balance = $settings->configured()
            ? $client->balance()
            : ['success' => false, 'balance' => '—', 'error' => 'Add the API token to see the balance.'];

        $audios = $settings->configured() ? $client->audios() : [];

        return view('softcentsvoice::admin.index', [
            'settings' => $settings,
            'balance' => $balance,
            'audios' => $audios,
            'events' => VoiceEvent::orderBy('category')->orderBy('name')->get(),
            'overrides' => VoiceGroupOverride::orderBy('group_id')->get(),
            'groups' => ProductGroup::orderBy('name')->get(['id', 'name']),
            'logs' => VoiceLog::latest('id')->limit(40)->get(),
            'pendingRetries' => VoiceRetry::where('status', 'pending')->count(),
            'failedRetries' => VoiceRetry::where('status', 'failed')->count(),
            'sentToday' => VoiceLog::where('status', 'sent')->whereDate('created_at', today())->count(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'api_base_url' => ['nullable', 'url', 'max:150'],
            'caller_id' => ['nullable', 'string', 'max:30'],
            'calling_start' => ['required', 'date_format:H:i'],
            'calling_end' => ['required', 'date_format:H:i'],
            'timezone' => ['required', 'string', 'max:40'],
            'retry_interval_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $settings = VoiceSettings::current();

        $settings->fill([
            'api_base_url' => $validated['api_base_url'] ?? 'https://vsms.softcents.com/api',
            'caller_id' => $validated['caller_id'] ?? '376',
            'calling_start' => $validated['calling_start'],
            'calling_end' => $validated['calling_end'],
            'timezone' => $validated['timezone'],
            'retry_interval_minutes' => $validated['retry_interval_minutes'] ?? 15,
            'max_attempts' => $validated['max_attempts'] ?? 3,
            'master_paused' => $request->boolean('master_paused'),
            'retry_enabled' => $request->boolean('retry_enabled'),
        ]);

        if (trim((string) $request->input('api_token')) !== '') {
            $settings->api_token = (string) $request->input('api_token');
        }

        $settings->save();

        return back()->with('success', 'Voice settings saved.');
    }

    public function updateEvent(Request $request, VoiceEvent $event): RedirectResponse
    {
        $validated = $request->validate([
            'audio_id' => ['nullable', 'string', 'max:50'],
            'caller_id' => ['nullable', 'string', 'max:50'],
        ]);

        $event->update([
            'audio_id' => $validated['audio_id'] ?? '',
            'caller_id' => $validated['caller_id'] ?? '',
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        return back()->with('success', $event->name.' saved.');
    }

    public function storeOverride(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_id' => ['required', 'integer', 'exists:product_groups,id'],
            'event_key' => ['required', 'string', 'max:60'],
            'audio_id' => ['required', 'string', 'max:50'],
            'caller_id' => ['nullable', 'string', 'max:50'],
        ]);

        VoiceGroupOverride::updateOrCreate(
            ['group_id' => $validated['group_id'], 'event_key' => $validated['event_key']],
            ['audio_id' => $validated['audio_id'], 'caller_id' => $validated['caller_id'] ?? '', 'is_enabled' => true]
        );

        return back()->with('success', 'Group recording saved.');
    }

    public function destroyOverride(VoiceGroupOverride $override): RedirectResponse
    {
        $override->delete();

        return back()->with('success', 'Override removed.');
    }

    public function test(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:30'],
            'audio_id' => ['required', 'string', 'max:50'],
            'caller_id' => ['nullable', 'string', 'max:30'],
        ]);

        $settings = VoiceSettings::current();
        $caller = $validated['caller_id'] ?: $settings->caller_id;

        $result = (new VoiceClient($settings))->sendCall($validated['number'], $validated['audio_id'], $caller);

        VoiceLog::create([
            'phone' => BdPhone::gateway($validated['number']),
            'event_key' => 'manual_test',
            'audio_id' => $validated['audio_id'],
            'caller_id' => $caller,
            'call_id' => $result['call_id'] ?: null,
            'status' => $result['success'] ? 'sent' : 'failed',
            'reason' => $result['success'] ? 'Manual test call.' : mb_substr($result['error'], 0, 280),
        ]);

        return back()->with($result['success'] ? 'success' : 'error', $result['success'] ? 'Test call placed.' : 'The gateway refused: '.$result['error']);
    }
}
