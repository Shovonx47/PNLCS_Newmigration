<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AdminLog;
use App\Models\Email;
use App\Models\GatewayLog;
use App\Models\ModuleLog;
use App\Models\TicketMailLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    /**
     * Main logs index — shows tabbed log viewer with activity logs by default.
     */
    public function index(Request $request): View
    {
        $type = $request->get('type', 'activity');

        $query = ActivityLog::query();

        if ($request->filled('user')) {
            $query->where('user', $request->user);
        }
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->orderBy('id', 'desc')->paginate(50);

        return view('admin.logs.index', compact('logs', 'type'));
    }

    /**
     * Gateway payment logs.
     */
    public function gateway(Request $request): View
    {
        $query = GatewayLog::query();

        if ($request->filled('gateway')) {
            $query->where('gateway', $request->gateway);
        }
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        $logs     = $query->orderBy('id', 'desc')->paginate(50);
        $gateways = GatewayLog::select('gateway')->distinct()->orderBy('gateway')->pluck('gateway');

        return view('admin.logs.gateway', compact('logs', 'gateways'));
    }

    /**
     * Module action logs.
     */
    public function module(Request $request): View
    {
        $query = ModuleLog::query();

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs    = $query->orderBy('id', 'desc')->paginate(50);
        $modules = ModuleLog::select('module')->distinct()->orderBy('module')->pluck('module');

        return view('admin.logs.module', compact('logs', 'modules'));
    }

    /**
     * Email delivery logs.
     */
    public function email(Request $request): View
    {
        $query = Email::with('client');

        if ($request->filled('status')) {
            if ($request->status === 'failed') {
                $query->where('failed', true);
            } elseif ($request->status === 'pending') {
                $query->where('pending', true)->where('failed', false);
            } elseif ($request->status === 'sent') {
                $query->where('pending', false)->where('failed', false);
            }
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('subject', 'like', '%' . $request->search . '%')
                  ->orWhere('to', 'like', '%' . $request->search . '%');
            });
        }

        $logs = $query->orderBy('id', 'desc')->paginate(50);

        return view('admin.logs.email', compact('logs'));
    }

    /**
     * Admin login & session log.
     */
    public function adminLog(Request $request): View
    {
        $query = AdminLog::query();

        if ($request->filled('username')) {
            $query->where('admin_username', 'like', '%'.trim((string) $request->input('username')).'%');
        }
        if ($request->filled('ip')) {
            $query->where('ip_address', 'like', '%'.trim((string) $request->input('ip')).'%');
        }
        if ($request->filled('date')) {
            $query->whereDate('login_time', trim((string) $request->input('date')));
        }

        $logs = $query->orderBy('id', 'desc')->paginate(50)->withQueryString();

        return view('admin.logs.admin', compact('logs'));
    }

    /**
     * Ticket mail import log.
     */
    public function ticketMail(Request $request): View
    {
        $query = TicketMailLog::query();

        if ($request->filled('status')) {
            $query->where('status', 'like', '%'.trim((string) $request->input('status')).'%');
        }
        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('subject', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('to', 'like', "%{$term}%");
            });
        }
        if ($request->filled('date')) {
            $query->whereRaw('DATE(COALESCE(date, created_at)) = ?', [trim((string) $request->input('date'))]);
        }

        $logs = $query->orderBy('id', 'desc')->paginate(50)->withQueryString();

        return view('admin.logs.ticket-mail', compact('logs'));
    }
}
