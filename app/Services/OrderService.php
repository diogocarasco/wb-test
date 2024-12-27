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
        $order = Order::firstOrNew(['external_order_id' => $data['order_id']]);
        if ($order->exists) {
            Log::info("Order with ID {$data['order_id']} already exists. Skipping.");
            return;
        }

        $merchant = Merchant::where('domain', $data['merchant_domain'])->first();
        //$affiliate = $this->affiliateService->register($merchant, $data['customer_email'], $data['customer_name'],0.1);
        $affiliate = Affiliate::first();
        $commission = floatval($data['subtotal_price']) * $affiliate->commission_rate;
        $order = new Order([
            'external_order_id' => $data['order_id'],
            'subtotal' => $data['subtotal_price'],
            'discount_code' => $data['discount_code'],
            'customer_email' => $data['customer_email'],
            'commission_owed' => $commission,
            'merchant_id' => $merchant->id,
            'affiliate_id' => $affiliate->id,
            'payout_status' => Order::STATUS_UNPAID
        ]);

        $order->save();

        $this->logCommission($order);
    }

    /**
     * Log the commission for an order.
     *
     * @param  Order  $order
     * @return void
     */
    public function logCommission(Order $order)
    {
        $commissionRate = $order->merchant->commission_rate;
        $commissionAmount = $order->subtotal * $commissionRate;

        $order->commission_owed = $commissionAmount;
        $order->save();

        Log::info("Commission of ". $commissionAmount. " for order ".$order->order_id);
    }
}
