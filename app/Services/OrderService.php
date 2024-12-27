<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {}

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        DB::beginTransaction();
        try {

            $order = Order::where('external_order_id', $data['order_id'])->first();
            if ($order) {
                Log::info("Order with ID ".$data['order_id']." already exists. Skipping.");
                return;
            }
            
            $merchant = Merchant::where('domain', $data['merchant_domain'])->first();
            if (!$merchant) {  
                Log::error("Merchant with domain ".$data['merchant_domain']." not found.");
                return;
            } 
     
            try {
                $affiliate = $this->affiliateService->register($merchant, $data['customer_email'], $data['customer_name'], $merchant->default_commission_rate);
                $comissionRate = $affiliate->commission_rate;
            } catch (\Exception $e) {
                $affiliate = Affiliate::where('discount_code', $data['discount_code'])->first();
                $comissionRate = $affiliate->commission_rate;
            }

            $order = new Order([
                'external_order_id' => $data['order_id'],
                'subtotal' => $data['subtotal_price'],
                'discount_code' => $data['discount_code'],
                'customer_email' => $data['customer_email'],
                'commission_owed' => floatval($data['subtotal_price']) * $affiliate->commission_rate,
                'merchant_id' => $merchant->id,
                'affiliate_id' => $affiliate->id,
                'payout_status' => Order::STATUS_UNPAID
            ]);
    
            $order->save();
    
            $this->logCommission($order);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("An error occurred when processing order{$order}: ".$e->getMessage());
        }
    }

    /**
     * Log the commission for an order.
     *
     * @param  Order  $order
     * @return void
     */
    public function logCommission(Order $order)
    {
        Log::info("Commission of for order ".$order->order_id);
    }
}
