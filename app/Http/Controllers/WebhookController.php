<?php

namespace App\Http\Controllers;

use App\Services\AffiliateService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Pass the necessary data to the process order method
     * 
     * @param  Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->all();
        try {
            $this->orderService->processOrder($data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
            Log::error("An error occurred in the processOrder webhook:". $e->getMessage());
        }           
        return response()->json(['message' => 'Order processed']);
    }
}
