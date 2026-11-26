<?php

namespace Modules\Addons\ForceClientActive\Services;

use App\Enums\ClientStatus;
use App\Models\ActivityLog;
use App\Models\AddonSetting;
use App\Models\Client;
use Modules\Addons\ForceClientActive\ForceClientActiveModule;
use Modules\Addons\ForceClientActive\Models\ForcedActiveClient;
use Modules\Addons\ForceClientActive\Models\ForcedActiveLog;

class ForceClientActiveService
{
    public function enforceAllEnabled(): bool
    {
        $val = (string) AddonSetting::getSetting(ForceClientActiveModule::NAME, 'enforce_all_clients', '0');

        return in_array(strtolower($val), ['1', 'on', 'true', 'yes'], true);
    }

    public function restoreInactiveEnabled(): bool
    {
        $val = (string) AddonSetting::getSetting(ForceClientActiveModule::NAME, 'restore_inactive_clients', '1');

        return in_array(strtolower($val), ['1', 'on', 'true', 'yes'], true);
    }

    /**
     * Restore inactive/closed clients to active status for flagged clients, automatic-inactivity clients, or globally.
     */
    public function run(): int
    {
        $query = Client::where('status', '!=', ClientStatus::Active->value);

        if (! $this->enforceAllEnabled()) {
            $ids = ForcedActiveClient::pluck('client_id')->map(fn ($v) => (int) $v)->all();
            $restoreInactive = $this->restoreInactiveEnabled();

            if ($ids === [] && ! $restoreInactive) {
                return 0;
            }

            $query->where(function ($q) use ($ids, $restoreInactive) {
                if ($ids !== []) {
                    $q->whereIn('id', $ids);
                }
                if ($restoreInactive) {
                    $q->orWhere('status', ClientStatus::Inactive->value);
                }
            });
        }

        $restored = 0;
        foreach ($query->get() as $client) {
            $prev = $client->status instanceof ClientStatus ? $client->status->value : (string) $client->status;
            $client->update(['status' => ClientStatus::Active->value]);

            ForcedActiveLog::create([
                'client_id' => $client->id,
                'previous_status' => $prev,
                'restored_at' => now(),
            ]);

            ActivityLog::log("ForceClientActive restored Client #{$client->id} from '{$prev}' to 'active'", 'system');
            $restored++;
        }

        return $restored;
    }
}
