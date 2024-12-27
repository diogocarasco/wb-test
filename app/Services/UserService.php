<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class UserService
{

    /**
     * Create a new user
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(array $user): User
    {

        try {

            if ($this->checkEmailAvailability($user['email'])) {
                throw new Exception('Email already exists');
            }

            $user = User::create([
                'email' => $user['email'],
                'name' => $user["name"],
                'password' => !isset($user['password'])? null : $user['password'],
                'type' => $user["type"],
            ]);

            Log::info('User created', ['user_id' => $user->id]);
            return $user;
        
        } catch (\Exception $e) {
            Log::error('User creation failed', ['error' => $e->getMessage()]);
            throw $e; 
        }
    }

    public function checkEmailAvailability(string $email): bool
    {
        return User::where('email', $email)->exists();
    }


}
