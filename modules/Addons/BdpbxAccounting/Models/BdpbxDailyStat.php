<?php

namespace Modules\Addons\BdpbxAccounting\Models;

use Illuminate\Database\Eloquent\Model;

/** One day's figures for one PBX server (server_id 0 = all servers combined). */
class BdpbxDailyStat extends Model
{
    protected $table = 'bdpbx_daily_stats';

    protected $fillable = [
        'snapshot_date', 'server_id', 'server_name', 'server_hostname',
        'total_services', 'active_services', 'suspended_services', 'pending_services', 'cancelled_services',
        'active_mrr', 'paid_clients_month', 'paid_invoices_month', 'paid_amount_month', 'paid_today_amount',
        'due_clients', 'due_invoices', 'due_amount', 'due_details', 'services_details', 'last_run_at',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'active_mrr' => 'decimal:2',
        'paid_amount_month' => 'decimal:2',
        'paid_today_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'due_details' => 'array',
        'services_details' => 'array',
        'last_run_at' => 'datetime',
    ];

    public function isCombined(): bool
    {
        return (int) $this->server_id === 0;
    }
}
