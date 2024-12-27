<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;


class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {}

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {

        DB::beginTransaction();

        try {

            if ($this->checkEmailAvailability($email)) {
                throw new AffiliateCreateException('Email already in use');
            }

            $user = User::create([
                'email' => $email,
                'name' => $name,
                'type' => User::TYPE_AFFILIATE,
            ]);
        
            $discountCode = $this->apiService->createDiscountCode($merchant)['code'];

            $affiliate = Affiliate::create([
                'merchant_id' => $merchant->id,
                'user_id' => $user->id,
                'commission_rate' => $commissionRate,
                'discount_code' => $discountCode,
            ]);
        
            Mail::to($email)->send(new AffiliateCreated($affiliate));
        
            DB::commit();
        
            return $affiliate;
        
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e; 
        }
    }

    public function checkEmailAvailability(string $email): bool
    {
        return User::where('email', $email)->exists();
    }


}
