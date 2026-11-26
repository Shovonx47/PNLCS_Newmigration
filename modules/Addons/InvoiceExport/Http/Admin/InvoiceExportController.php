<?php

namespace Modules\Addons\InvoiceExport\Http\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class InvoiceExportController extends Controller
{
    public function index()
    {
        return view('invoiceexport::admin.index', [
            'clients' => Client::orderBy('first_name')->limit(300)->get(['id', 'first_name', 'last_name', 'email']),
            'totalInvoices' => Invoice::count(),
        ]);
    }

    public function download(Request $request, InvoicePdfService $pdfService)
    {
        $v = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'status' => 'nullable|in:all,paid,unpaid,overdue,cancelled,refunded',
            'client_id' => 'nullable|integer|exists:clients,id',
            'limit' => 'nullable|integer|min:1|max:300',
        ]);

        $limit = max(1, min(300, (int) ($v['limit'] ?? 100)));

        $invoices = Invoice::with(['client', 'items'])
            ->when(! empty($v['date_from']), fn ($q) => $q->whereDate('date', '>=', $v['date_from']))
            ->when(! empty($v['date_to']), fn ($q) => $q->whereDate('date', '<=', $v['date_to']))
            ->when(! empty($v['status']) && $v['status'] !== 'all', fn ($q) => $q->where('status', $v['status']))
            ->when(! empty($v['client_id']), fn ($q) => $q->where('client_id', (int) $v['client_id']))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        if ($invoices->isEmpty()) {
            return back()->with('error', 'No invoices matched the selected export criteria.');
        }

        $exportDir = storage_path('app/private/exports');
        File::ensureDirectoryExists($exportDir);
        $zipPath = $exportDir.DIRECTORY_SEPARATOR.'invoices-'.Str::uuid()->toString().'.zip';

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Unable to initialize temporary export archive.');
        }

        $csvStream = fopen('php://temp', 'r+');
        fputcsv($csvStream, ['ID', 'Invoice Number', 'Client Name', 'Email', 'Date', 'Due Date', 'Status', 'Subtotal', 'Tax', 'Credit', 'Total', 'PDF File']);

        foreach ($invoices as $invoice) {
            $safeNum = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) ($invoice->invoice_num ?: $invoice->id)) ?: (string) $invoice->id;
            $pdfEntryName = 'INV-'.$safeNum.'-'.$invoice->id.'.pdf';

            $pdfBytes = $pdfService->generate($invoice)->output();
            $zip->addFromString($pdfEntryName, $pdfBytes);

            $clientName = $invoice->client ? trim($invoice->client->first_name.' '.$invoice->client->last_name) : '';
            fputcsv($csvStream, [
                $invoice->id,
                self::csvSafe((string) $invoice->invoice_num),
                self::csvSafe($clientName),
                self::csvSafe((string) ($invoice->client?->email ?? '')),
                $invoice->date?->toDateString() ?? '',
                $invoice->due_date?->toDateString() ?? '',
                self::csvSafe((string) $invoice->status),
                number_format((float) $invoice->subtotal, 2, '.', ''),
                number_format((float) $invoice->tax, 2, '.', ''),
                number_format((float) $invoice->credit, 2, '.', ''),
                number_format((float) $invoice->total, 2, '.', ''),
                $pdfEntryName,
            ]);
        }

        rewind($csvStream);
        $zip->addFromString('index.csv', (string) stream_get_contents($csvStream));
        fclose($csvStream);
        $zip->close();

        $downloadName = 'invoices-export-'.now()->format('Y-m-d-His').'.zip';

        return response()->download($zipPath, $downloadName, [
            'Content-Type' => 'application/zip',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, private',
        ])->deleteFileAfterSend(true);
    }

    private static function csvSafe(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
