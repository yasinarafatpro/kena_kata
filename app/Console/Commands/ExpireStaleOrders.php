<?php

namespace App\Console\Commands;

use App\Services\OrderService;
use Illuminate\Console\Command;

class ExpireStaleOrders extends Command
{
    protected $signature = 'orders:expire {--minutes=30}';
    protected $description = 'Cancel unpaid online-payment orders and release their stock';

    public function handle(OrderService $orders): int
    {
        $n = $orders->expireStale((int) $this->option('minutes'));
        $this->info("Expired {$n} order(s).");

        return self::SUCCESS;
    }
}
