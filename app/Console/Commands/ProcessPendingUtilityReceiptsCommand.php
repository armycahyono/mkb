<?php

namespace App\Console\Commands;

use App\Jobs\ProcessUtilityReceiptOcrJob;
use App\Models\UtilityReceipt;
use App\Services\UtilityReceiptOcrService;
use Illuminate\Console\Command;

class ProcessPendingUtilityReceiptsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'utilitas:process-pending 
                            {--limit=10 : Jumlah maksimal struk yang diproses} 
                            {--retry-failed : Sertakan juga struk yang sebelumnya gagal}
                            {--sync : Jalankan secara sinkron tanpa queue}';

    /**
     * The command description.
     */
    protected $description = 'Proses pemindaian OCR struk utilitas yang masih berstatus pending/gagal di latar belakang';

    /**
     * Execute the console command.
     */
    public function handle(UtilityReceiptOcrService $ocrService): int
    {
        $limit = (int) $this->option('limit') ?: 10;
        $statuses = ['pending'];

        if ($this->option('retry-failed')) {
            $statuses[] = 'failed';
        }

        $receipts = UtilityReceipt::whereIn('ocr_status', $statuses)
            ->whereNotNull('receipt_file')
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();

        if ($receipts->isEmpty()) {
            $this->info('Tidak ada struk utilitas berstatus pending.');

            return Command::SUCCESS;
        }

        $this->info("Menemukan {$receipts->count()} struk utilitas yang siap diproses...");

        foreach ($receipts as $receipt) {
            $this->line("Memproses Struk #{$receipt->id} ({$receipt->receipt_file})...");

            if ($this->option('sync')) {
                (new ProcessUtilityReceiptOcrJob($receipt))->handle($ocrService);
                $receipt->refresh();
                $this->info(" -> Status: {$receipt->ocr_status} | Total: Rp ".number_format((float) $receipt->total_amount, 0, ',', '.'));
            } else {
                ProcessUtilityReceiptOcrJob::dispatch($receipt);
                $this->info(' -> Job dikirim ke queue (Antrean)');
            }
        }

        $this->info('Selesai memproses antrean struk utilitas.');

        return Command::SUCCESS;
    }
}
