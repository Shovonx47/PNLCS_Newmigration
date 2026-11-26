<?php

namespace Modules\Addons\WhatsappPro\Models;

use App\Support\BdPhone;
use Illuminate\Database\Eloquent\Model;

/**
 * One row. The gateway API key is encrypted at rest; the legacy settings table
 * had it in plain text beside the sender number.
 */
class WaSettings extends Model
{
    protected $table = 'whatsapp_settings';

    protected $fillable = [
        'api_key', 'sender', 'api_domain', 'footer', 'admin_numbers', 'client_portal', 'max_retry', 'enabled',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'client_portal' => 'boolean',
            'enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        $row = static::query()->orderBy('id')->first();

        return $row ?: static::create([
            'api_key' => '',
            'sender' => '',
            'api_domain' => 'https://wpg.softcents.com',
            'footer' => '',
            'admin_numbers' => '',
            'client_portal' => true,
            'max_retry' => 3,
            'enabled' => true,
        ]);
    }

    public function configured(): bool
    {
        return trim((string) $this->api_key) !== '' && BdPhone::gateway($this->sender) !== '';
    }

    /** @return array<int, string> */
    public function adminNumberList(): array
    {
        return array_values(array_filter(array_map(
            fn ($n) => BdPhone::gateway(trim($n)),
            explode(',', (string) $this->admin_numbers)
        )));
    }
}
