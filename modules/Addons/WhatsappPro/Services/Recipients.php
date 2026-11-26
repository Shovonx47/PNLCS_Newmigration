<?php

namespace Modules\Addons\WhatsappPro\Services;

use App\Models\Client;
use App\Support\BdPhone;
use App\Support\NotificationVariables;

/**
 * Who a bulk message goes to.
 *
 * The legacy addon had five separate builders with near-identical SQL - all
 * clients, a product group, unpaid invoices, a pasted list, a CSV upload. They
 * return the same shape here, deduplicated by number, because the same person
 * in two groups must not get the same message twice.
 */
class Recipients
{
    /**
     * @return array<int, array{client_id: ?int, number: string}>
     */
    public static function allClients(): array
    {
        return self::fromClients(Client::query()->where('status', 'active')->get());
    }

    /**
     * @return array<int, array{client_id: ?int, number: string}>
     */
    public static function productGroup(int $groupId): array
    {
        $clients = Client::query()
            ->where('status', 'active')
            ->whereHas('services', fn ($q) => $q->whereHas('product', fn ($p) => $p->where('group_id', $groupId)))
            ->get();

        return self::fromClients($clients);
    }

    /**
     * Customers with an invoice that is not settled - the audience for a
     * "your bill is due" campaign.
     *
     * @return array<int, array{client_id: ?int, number: string}>
     */
    public static function unpaidInvoices(): array
    {
        $clients = Client::query()
            ->where('status', 'active')
            ->whereHas('invoices', fn ($q) => $q->whereIn('status', ['unpaid', 'overdue', 'partially_paid']))
            ->get();

        return self::fromClients($clients);
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, array{client_id: ?int, number: string}>
     */
    public static function clientIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return self::fromClients(Client::query()->whereIn('id', $ids)->get());
    }

    /**
     * A pasted list, or the contents of an uploaded CSV. One number per line;
     * the first comma-separated column is the number, so a CSV with names in
     * the second column works too.
     *
     * @return array<int, array{client_id: ?int, number: string}>
     */
    public static function fromCsv(string $csv): array
    {
        $out = [];
        $seen = [];

        foreach (preg_split('/\r\n|\r|\n/', $csv) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $first = trim((string) (explode(',', $line)[0] ?? ''));
            $number = BdPhone::gateway($first);

            // A header row ("number,name") normalises to nothing and is skipped.
            if ($number === '' || isset($seen[$number])) {
                continue;
            }

            $seen[$number] = true;
            $out[] = ['client_id' => null, 'number' => $number];
        }

        return $out;
    }

    /**
     * @return array<int, array{client_id: ?int, number: string}>
     */
    private static function fromClients(iterable $clients): array
    {
        $out = [];
        $seen = [];

        foreach ($clients as $client) {
            $number = BdPhone::gateway($client->phone_number, $client->phone_prefix);

            if ($number === '' || isset($seen[$number])) {
                continue;
            }

            $seen[$number] = true;
            $out[] = ['client_id' => $client->id, 'number' => $number];
        }

        return $out;
    }

    /**
     * Replace {firstname} and the rest for one recipient of a bulk send. The
     * client is loaded only when the row has one.
     *
     * @param  array{client_id: ?int, number: string}  $recipient
     */
    public static function renderFor(string $message, array $recipient): string
    {
        $client = $recipient['client_id'] ? Client::find($recipient['client_id']) : null;

        // A dunning campaign says {invoiceid}, {amount} and {invoiceurl}. The
        // oldest open invoice is the one the operator means; without one those
        // placeholders stay as they are rather than becoming an empty string.
        $invoice = $client?->invoices()
            ->whereIn('status', ['unpaid', 'overdue', 'partially_paid'])
            ->orderBy('due_date')
            ->first();

        return NotificationVariables::render(
            $message,
            NotificationVariables::build(['client' => $client, 'invoice' => $invoice])
        );
    }
}
