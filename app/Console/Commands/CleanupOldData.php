<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupOldData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-old-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus data transaksi yang sudah lebih dari 2 tahun untuk menghemat penyimpanan.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $twoYearsAgo = \Carbon\Carbon::now()->subYears(2);
        
        $this->info("Cleaning up data older than {$twoYearsAgo->toDateString()}...");

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($twoYearsAgo) {
                // Delete sales older than 2 years
                $deletedSales = \App\Models\MPenjualan::where('tanggal', '<', $twoYearsAgo)->delete();
                $this->info("Deleted {$deletedSales} sales records.");

                // Delete monthly reports older than 2 years
                $deletedLaporan = \App\Models\MLaporan::where('tahun', '<', $twoYearsAgo->year)
                    ->orWhere(function($query) use ($twoYearsAgo) {
                        $query->where('tahun', $twoYearsAgo->year)
                              ->where('bulan', '<', $twoYearsAgo->month);
                    })->delete();
                $this->info("Deleted {$deletedLaporan} monthly report records.");
            });

            $this->info('Cleanup completed successfully.');
        } catch (\Exception $e) {
            $this->error("Error during cleanup: " . $e->getMessage());
        }
    }
}
