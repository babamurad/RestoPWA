<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One dictionary of order statuses and one history record per status change.
 *
 * 1. The old "cooking" becomes "preparing" (orders and their history).
 * 2. Every kanban move used to write two identical history records (one by
 *    OrderService, one by the Order model): the copies are removed, keeping
 *    the record that carries more details.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')->where('status', 'cooking')->update(['status' => 'preparing']);
        DB::table('order_status_history')->where('from_status', 'cooking')->update(['from_status' => 'preparing']);
        DB::table('order_status_history')->where('to_status', 'cooking')->update(['to_status' => 'preparing']);

        $kept = null;
        $delete = [];

        $rows = DB::table('order_status_history')
            ->select('id', 'order_id', 'from_status', 'to_status', 'metadata', 'created_at')
            ->orderBy('order_id')
            ->orderBy('created_at')
            ->orderBy('to_status')
            ->cursor();

        foreach ($rows as $row) {
            $isCopy = $kept !== null
                && $row->order_id === $kept->order_id
                && $row->from_status === $kept->from_status
                && $row->to_status === $kept->to_status
                && abs(strtotime((string) $row->created_at) - strtotime((string) $kept->created_at)) <= 1;

            if (! $isCopy) {
                $kept = $row;

                continue;
            }

            // Keep the richer record (the kanban one names the columns and who moved the order)
            if (strlen((string) $row->metadata) > strlen((string) $kept->metadata)) {
                $delete[] = $kept->id;
                $kept = $row;
            } else {
                $delete[] = $row->id;
            }
        }

        foreach (array_chunk($delete, 500) as $ids) {
            DB::table('order_status_history')->whereIn('id', $ids)->delete();
        }
    }

    public function down(): void
    {
        // Removed copies and the old status name are not restored
    }
};
