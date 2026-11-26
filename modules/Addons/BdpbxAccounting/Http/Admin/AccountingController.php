<?php

namespace Modules\Addons\BdpbxAccounting\Http\Admin;

use App\Constants\Permissions;
use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Addons\BdpbxAccounting\Services\AccountingEngine;

/**
 * Extensions -> BDPBX Accounting.
 *
 * Per-server and combined figures for a chosen day, the run history, and the
 * one-click SuperAdmin PBX sign-in the Bdpbx module already provides. "Run
 * now" recalculates the day in place; the snapshot is what the page reads.
 */
class AccountingController extends Controller
{
    public function __construct(private readonly AccountingEngine $engine) {}

    public function index(Request $request)
    {
        $date = $this->engine->normaliseDate($request->query('date'));

        $stats = $this->engine->forDate($date);

        $combined = $stats->firstWhere('server_id', 0);
        $perServer = $stats->where('server_id', '!=', 0)->values();

        $servers = Server::where('type', 'bdpbx')->get(['id', 'name', 'hostname'])->keyBy('id');

        // The Bdpbx server module owns the SuperAdmin sign-in. It needs
        // manage_servers, which manage_services alone does not grant, so the
        // link is only offered to staff who can actually follow it.
        $ssoRoute = Route::has('admin.bdpbx.super-sso')
            && (bool) auth('admin')->user()?->hasPermission(Permissions::MANAGE_SERVERS);

        return view('bdpbxaccounting::admin.index', [
            'date' => $date,
            'combined' => $combined,
            'perServer' => $perServer,
            'servers' => $servers,
            'history' => $this->engine->dates(30),
            'ssoRoute' => $ssoRoute,
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        $date = $this->engine->normaliseDate($request->input('date'));
        $rows = $this->engine->run($date);

        return redirect()
            ->route('admin.extensions.bdpbx-accounting.index', ['date' => $date])
            ->with($rows === [] ? 'warning' : 'success', $rows === []
                ? 'No BDPBX products are configured to account for.'
                : "Accounting recalculated for {$date}.");
    }
}
