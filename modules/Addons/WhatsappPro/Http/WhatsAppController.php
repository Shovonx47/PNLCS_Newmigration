<?php

namespace Modules\Addons\WhatsappPro\Http;

use App\Http\Controllers\Concerns\ResolvesClient;
use App\Http\Controllers\Controller;
use App\Support\BdPhone;
use Modules\Addons\WhatsappPro\Models\WaLog;
use Modules\Addons\WhatsappPro\Models\WaSettings;

/**
 * The customer's own messages. Only rows that belong to this customer are
 * listed, by client id or by the number on the account - never everything that
 * went out.
 */
class WhatsAppController extends Controller
{
    use ResolvesClient;

    public function index()
    {
        $client = $this->currentClient();
        abort_if($client === null, 403);

        $number = BdPhone::gateway($client->phone_number, $client->phone_prefix);
        $settings = WaSettings::current();

        $messages = WaLog::query()
            ->where(function ($q) use ($client, $number) {
                $q->where('client_id', $client->id);

                if ($number !== '') {
                    $q->orWhere('number', $number);
                }
            })
            ->orderByDesc('id')
            ->paginate(25);

        return view('whatsapppro::client.index', [
            'messages' => $messages,
            'number' => $number,
            'sender' => BdPhone::local($settings->sender),
            'enabled' => $settings->client_portal,
        ]);
    }
}
