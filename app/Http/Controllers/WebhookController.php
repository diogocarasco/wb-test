<?php

namespace App\Http\Controllers;

use App\Services\AffiliateService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
        if (!$this->validateRequestData($data)) {
            return response()->json(['error' => 'Invalid data'], 400);
        }

        try { 
            $this->orderService->processOrder($data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
            Log::error("An error occurred in the processOrder webhook:". $e->getMessage());
        }           
        return response()->json(['message' => 'Order processed']);
    }

    public function validateRequestData(array $data) :bool{

        $validator = Validator::make($data, [
            'order_id' => 'required|string',
            'subtotal_price' => 'required|numeric',
            'merchant_domain' => 'required|string',
            'discount_code' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return false;
        }
        return true;
    }

}