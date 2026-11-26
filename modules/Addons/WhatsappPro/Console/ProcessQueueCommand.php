<?php

namespace Modules\Addons\WhatsappPro\Console;

use Illuminate\Console\Command;
use Modules\Addons\WhatsappPro\Services\WaDispatcher;

class ProcessQueueCommand extends Command
{
    protected $signature = 'pnlcs:whatsapp-queue {--limit=100}';

    protected $description = 'Send the WhatsApp messages waiting in the queue';

    public function handle(WaDispatcher $dispatcher): int
    {
        $result = $dispatcher->processQueue((int) $this->option('limit'));

        $this->info("WhatsApp queue: {$result['sent']} sent, {$result['failed']} gave up.");

        return self::SUCCESS;
    }
}
