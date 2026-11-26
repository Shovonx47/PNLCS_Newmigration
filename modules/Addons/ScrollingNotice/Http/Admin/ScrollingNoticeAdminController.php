<?php

namespace Modules\Addons\ScrollingNotice\Http\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Addons\ScrollingNotice\Models\ScrollingNotice;

class ScrollingNoticeAdminController extends Controller
{
    public function index()
    {
        $notices = ScrollingNotice::orderByDesc('priority')->orderByDesc('id')->get();

        return view('scrollingnotice::admin.index', compact('notices'));
    }

    public function store(Request $request)
    {
        ScrollingNotice::create($this->validated($request));

        return back()->with('success', 'Scrolling notice created.');
    }

    public function update(Request $request, ScrollingNotice $notice)
    {
        $notice->update($this->validated($request));

        return back()->with('success', 'Scrolling notice updated.');
    }

    public function toggle(ScrollingNotice $notice)
    {
        $notice->update(['is_active' => ! $notice->is_active]);

        return back()->with('success', 'Scrolling notice status updated.');
    }

    public function destroy(ScrollingNotice $notice)
    {
        $notice->delete();

        return back()->with('success', 'Scrolling notice deleted.');
    }

    private function validated(Request $request): array
    {
        $v = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:2000',
            'priority' => 'nullable|integer|min:-100|max:1000',
            'url_target' => 'nullable|string|max:512',
            'start_time' => 'nullable|date',
            'expiry_time' => 'nullable|date',
            'login_status' => 'required|in:all,logged_in,logged_out',
            'coupon_code' => 'nullable|string|max:64',
            'scroll_speed' => 'nullable|integer|min:1|max:20',
        ]);

        $url = trim((string) ($v['url_target'] ?? ''));
        if ($url !== '') {
            $isRelative = str_starts_with($url, '/') && ! str_starts_with($url, '//');
            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
            if (! $isRelative && ! in_array($scheme, ['https', 'http'], true)) {
                throw ValidationException::withMessages([
                    'url_target' => 'Target URL must start with / or https://.',
                ]);
            }
        }

        return [
            'title' => $v['title'] ?? null,
            'content' => trim($v['content']),
            'priority' => (int) ($v['priority'] ?? 0),
            'url_target' => $url !== '' ? $url : null,
            'start_time' => $v['start_time'] ?: null,
            'expiry_time' => $v['expiry_time'] ?: null,
            'is_active' => $request->boolean('is_active', true),
            'login_status' => $v['login_status'],
            'coupon_enabled' => $request->boolean('coupon_enabled') && trim((string) ($v['coupon_code'] ?? '')) !== '',
            'coupon_code' => trim((string) ($v['coupon_code'] ?? '')) ?: null,
            'scroll_speed' => max(1, min(20, (int) ($v['scroll_speed'] ?? 5))),
        ];
    }
}
