<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user
        $user = User::first();
        if (!$user) {
            return; // No user to assign orders to
        }

        // Get some products
        $products = Product::take(5)->get();
        if ($products->isEmpty()) {
            return; // No products
        }

        $now = Carbon::now();

        // Create orders for current month to match test data
        // Month: $32,500 | 345 orders | +8.3%
        $this->createOrdersForPeriod($user, $products, $now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 345, 32500);

        // Create orders for previous month (for percentage change)
        $prevStart = $now->copy()->subMonth()->startOfMonth();
        $prevEnd = $now->copy()->subMonth()->endOfMonth();
        $prevOrders = 320; // Slightly less for positive change
        $prevRevenue = 30000; // Less than 32500 for +8.3%
        $this->createOrdersForPeriod($user, $products, $prevStart, $prevEnd, $prevOrders, $prevRevenue);

        // For week: $8,750 | 98 orders | +12.8%
        $this->createOrdersForPeriod($user, $products, $now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 98, 8750);

        // Previous week
        $prevWeekStart = $now->copy()->subWeek()->startOfWeek();
        $prevWeekEnd = $now->copy()->subWeek()->endOfWeek();
        $prevWeekOrders = 85;
        $prevWeekRevenue = 7750;
        $this->createOrdersForPeriod($user, $products, $prevWeekStart, $prevWeekEnd, $prevWeekOrders, $prevWeekRevenue);

        // For day: $1,250 | 15 orders | +5.2%
        $this->createOrdersForPeriod($user, $products, $now->copy()->startOfDay(), $now->copy()->endOfDay(), 15, 1250);

        // Previous day
        $prevDay = $now->copy()->subDay();
        $prevDayOrders = 14;
        $prevDayRevenue = 1187.50;
        $this->createOrdersForPeriod($user, $products, $prevDay->startOfDay(), $prevDay->endOfDay(), $prevDayOrders, $prevDayRevenue);
    }

    private function createOrdersForPeriod($user, $products, $startDate, $endDate, $numOrders, $totalRevenue)
    {
        $avgOrderValue = $totalRevenue / $numOrders;
        $periodDays = $startDate->diffInDays($endDate) + 1;

        for ($i = 0; $i < $numOrders; $i++) {
            $randomDate = $startDate->copy()->addDays(rand(0, $periodDays - 1));
            $orderTotal = rand($avgOrderValue * 0.8 * 100, $avgOrderValue * 1.2 * 100) / 100; // Vary around average

            $order = Order::create([
                'user_id' => $user->id,
                'total' => $orderTotal,
                'created_at' => $randomDate,
                'updated_at' => $randomDate,
            ]);

            // Add 1-3 items per order
            $numItems = rand(1, 3);
            $remainingTotal = $orderTotal;
            for ($j = 0; $j < $numItems; $j++) {
                $product = $products->random();
                $quantity = rand(1, 5);
                $price = $product->price;
                if ($j == $numItems - 1) {
                    $itemTotal = $remainingTotal;
                } else {
                    $itemTotal = $quantity * $price;
                    $remainingTotal -= $itemTotal;
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'created_at' => $randomDate,
                    'updated_at' => $randomDate,
                ]);
            }
        }
    }
}
