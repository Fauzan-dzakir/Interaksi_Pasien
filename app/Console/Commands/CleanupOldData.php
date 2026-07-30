<?php

namespace App\Console\Commands;

use App\Models\DeliveryOrder;
use App\Models\ItemBatch;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupOldData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cssd:cleanup-old-data {--days=15 : The number of days to keep data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up or archive old delivery orders and item batches that have been completed for a long time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Starting cleanup for data older than {$days} days ({$cutoffDate->format('Y-m-d')})");

        // We want to delete DeliveryOrders that have been Completed for more than $days days.
        // And also item batches that are Retired or Superseded for more than $days days.
        // However, wait, what if an ItemBatch is InUse for 30 days? We don't delete active batches.
        // Only delete Completed orders.
        // Wait, deleting ItemBatch will also delete its events due to cascade (assuming cascade on delete, but let's check).
        
        DB::beginTransaction();
        try {
            // Delete old completed/cancelled orders
            $ordersDeleted = DeliveryOrder::whereIn('status', ['completed', 'cancelled'])
                ->where('updated_at', '<', $cutoffDate)
                ->delete();

            // Delete old inactive item batches
            $batchesDeleted = ItemBatch::whereIn('status', ['retired', 'lost'])
                ->where('updated_at', '<', $cutoffDate)
                ->delete();
                
            // Note: Superseded batches should ideally be kept for lineage tracing, but if they are too old, they could be removed.
            // Let's stick to retired, lost, and old orders for now.

            DB::commit();

            $this->info("Successfully deleted {$ordersDeleted} old delivery orders.");
            $this->info("Successfully deleted {$batchesDeleted} inactive item batches.");
            Log::info("CleanupOldData completed: deleted {$ordersDeleted} orders and {$batchesDeleted} batches.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('An error occurred during cleanup: ' . $e->getMessage());
            Log::error('CleanupOldData failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
