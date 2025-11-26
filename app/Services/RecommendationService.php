<?php

namespace App\Services;

use App\Models\Product;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Collection;

class RecommendationService
{
    /**
     * Get popular products based on total sales quantity
     */
    public function getPopularProducts(int $limit = 10): Collection
    {
        return Product::select('products.*')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->selectRaw('SUM(order_items.quantity) as total_sold')
            ->groupBy('products.id')
            ->orderBy('total_sold', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get "customers also bought" recommendations for a specific product
     */
    public function getCustomersAlsoBought(int $productId, int $limit = 5): Collection
    {
        // Find users who bought this product
        $userIds = OrderItem::where('product_id', $productId)
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->pluck('orders.user_id')
            ->unique();

        if ($userIds->isEmpty()) {
            return collect();
        }

        // Find other products bought by these users
        return Product::select('products.*')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.user_id', $userIds)
            ->where('products.id', '!=', $productId)
            ->selectRaw('COUNT(order_items.product_id) as frequency')
            ->groupBy('products.id')
            ->orderBy('frequency', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get personalized recommendations based on user's purchase history
     */
    public function getPersonalizedRecommendations(int $userId, int $limit = 10): Collection
    {
        // Get products the user has bought
        $userProductIds = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', $userId)
            ->pluck('order_items.product_id')
            ->unique();

        if ($userProductIds->isEmpty()) {
            // If no history, return popular products
            return $this->getPopularProducts($limit);
        }

        // Find users who bought similar products
        $similarUserIds = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('order_items.product_id', $userProductIds)
            ->where('orders.user_id', '!=', $userId)
            ->pluck('orders.user_id')
            ->unique();

        if ($similarUserIds->isEmpty()) {
            return $this->getPopularProducts($limit);
        }

        // Get products bought by similar users that the current user hasn't bought
        return Product::select('products.*')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.user_id', $similarUserIds)
            ->whereNotIn('products.id', $userProductIds)
            ->selectRaw('COUNT(order_items.product_id) as frequency')
            ->groupBy('products.id')
            ->orderBy('frequency', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get combined recommendations for a user
     */
    public function getRecommendationsForUser(int $userId, int $limit = 10): Collection
    {
        $personalized = $this->getPersonalizedRecommendations($userId, $limit);
        $popular = $this->getPopularProducts($limit);

        // Combine and remove duplicates, prioritizing personalized
        $combined = $personalized->merge($popular)->unique('id');

        return $combined->take($limit);
    }
}