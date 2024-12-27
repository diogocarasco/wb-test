<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     * Hint: Use the password field to store the API key.
     * Hint: Be sure to set the correct user type according to the constants in the User model.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['api_key'],
            'type' => User::TYPE_MERCHANT, 
        ]);

        $merchant =[
            'user_id' => $user->id,
            'display_name' => $data['name'],
            'domain' => $data['domain'],
        ];

        $result = $user->merchant()->create($merchant);

        return $result;
    }

    /**
     * Update the user
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
    {
        Merchant::where('id', $user->id)->update([
            'display_name' => $data['name'],
            'domain' => $data['domain'],
        ]);
    }

    /**
     * Find a merchant by their email.
     * Hint: You'll need to look up the user first.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            return null;
        }

        return $user->merchant;
    }

    /**
     * Pay out all of an affiliate's orders.
     * Hint: You'll need to dispatch the job for each unpaid order.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        $orders = Order::where('affiliate_id', $affiliate->id)
                    ->where('payout_status', Order::STATUS_UNPAID)  
                    ->get();

        foreach ($orders as $order) {
            PayoutOrderJob::dispatch($order);
        }
    }

    /**
     * Get useful order statistics for the merchant API.
     *
     * @param Carbon $from
     * @param Carbon $to
     * @return array{count: int, commission_owed: float, revenue: float}
     */
    public function getOrdersStatus(Carbon $from, Carbon $to): array{
        $stats = Order::whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(CASE WHEN status = ? THEN affiliate_commission ELSE 0 END) as commission_owed', [Order::STATUS_UNPAID])
            ->selectRaw('SUM(subtotal) as revenue')
            ->first()
            ->toArray();

        if (!$stats) {
            return [
                'count' => 0,
                'commission_owed' => 0,
                'revenue' => 0
            ];
        }

        return $stats;
    }
}
