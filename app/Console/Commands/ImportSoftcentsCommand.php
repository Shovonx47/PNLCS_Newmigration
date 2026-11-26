<?php

namespace App\Console\Commands;

use App\Services\WhmcsImport\SoftcentsFullImporter;
use Illuminate\Console\Command;

class ImportSoftcentsCommand extends Command
{
    protected $signature = 'pnlcs:import-softcents
                            {--dry-run : Run full migration inside a rolled-back transaction without persisting changes}
                            {--force-passwords : Overwrite existing PNLCS user/admin passwords with default local passwords}';

    protected $description = 'Idempotently import all SoftCents WHMCS core records and mod_* addon tables from read-only billing_db (excluding Domain Reseller)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $forcePasswords = (bool) $this->option('force-passwords');

        $this->info($dryRun
            ? 'Running SoftCents WHMCS full import in DRY-RUN mode (zero database changes will be committed)...'
            : 'Running SoftCents WHMCS full import against billing_db...');

        $whmcs = SoftcentsFullImporter::connectBillingDb();
        $ccHash = SoftcentsFullImporter::readWhmcsEncryptionHash();

        if ($ccHash === '') {
            $this->warn('Warning: WHMCS $cc_encryption_hash was not found; encrypted passwords cannot be decrypted.');
        }

        $importer = new SoftcentsFullImporter(
            whmcs: $whmcs,
            ccEncryptionHash: $ccHash,
            dryRun: $dryRun,
            forcePasswords: $forcePasswords,
            logger: fn (string $line) => $this->line('  - '.$line),
        );

        $stats = $importer->run();

        $rows = [];
        foreach ($stats as $entity => $counts) {
            $rows[] = [$entity, $counts['source'], $counts['synced'], $counts['skipped']];
        }

        $this->newLine();
        $this->table(['Section', 'Source Rows', 'Synced', 'Skipped'], $rows);

        $this->info($dryRun
            ? 'Dry-run complete (transaction rolled back; pnlcs_db unchanged).'
            : 'SoftCents WHMCS full import complete!');

        return self::SUCCESS;
    }
}
