<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\MerchantService;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MerchantController extends Controller
{
    public function __construct(
        MerchantService $merchantService
    ) {}

    /**
     * Useful order statistics for the merchant API.
     * 
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        $from = Carbon::parse($request->input('from'));
        $to = Carbon::parse($request->input('to'));
        try {
                $stats = Order::whereBetween('created_at', [$from, $to])
                    ->selectRaw('COUNT(*) as count')
                    ->selectRaw('SUM(CASE WHEN payout_status = ? AND affiliate_id IS NOT NULL THEN commission_owed ELSE 0 END) as commissions_owed', [Order::STATUS_UNPAID])
                    ->selectRaw('SUM(subtotal) as revenue')
                    ->first()
                    ->toArray();
                if (!$stats) {
                    return response()->json([
                        'count' => 0,
                        'commission_owed' => 0,
                        'revenue' => 0
                    ]);
                }
        
                return new JsonResponse($stats);
        } catch (\Exception $e) { 
            echo $e->getMessage();
            Log::error("An error occurred when fetching order stats:". $e->getMessage());
        }    
    }
}
