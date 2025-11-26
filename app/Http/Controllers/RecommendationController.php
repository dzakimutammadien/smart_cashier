<?php

namespace App\Http\Controllers;

use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    protected RecommendationService $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    /**
     * Get popular products
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $products = $this->recommendationService->getPopularProducts($limit);

        return response()->json([
            'success' => true,
            'data' => $products,
            'message' => 'Popular products retrieved successfully'
        ]);
    }

    /**
     * Get "customers also bought" recommendations for a product
     */
    public function customersAlsoBought(Request $request, int $productId): JsonResponse
    {
        $limit = $request->get('limit', 5);
        $products = $this->recommendationService->getCustomersAlsoBought($productId, $limit);

        return response()->json([
            'success' => true,
            'data' => $products,
            'message' => 'Customers also bought products retrieved successfully'
        ]);
    }

    /**
     * Get personalized recommendations for authenticated user
     */
    public function personalized(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $limit = $request->get('limit', 10);
        $products = $this->recommendationService->getPersonalizedRecommendations($user->id, $limit);

        return response()->json([
            'success' => true,
            'data' => $products,
            'message' => 'Personalized recommendations retrieved successfully'
        ]);
    }

    /**
     * Get combined recommendations for authenticated user
     */
    public function recommendations(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $limit = $request->get('limit', 10);
        $products = $this->recommendationService->getRecommendationsForUser($user->id, $limit);

        return response()->json([
            'success' => true,
            'data' => $products,
            'message' => 'Recommendations retrieved successfully'
        ]);
    }
}
