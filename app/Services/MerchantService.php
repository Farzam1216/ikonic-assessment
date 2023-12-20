<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
        // TODO: Complete this method
        try {
            //code...
            DB::transaction(function ($data) {

                if (User::where('email', $data['email'])->exists()) {
                    throw new Exception('Email already exist. Please Enter a unique email');
                }

                if (Merchant::where('domain', $data['domain'])->exists()) {
                    throw new Exception('Domain already exist. Please Enter a unique domain');
                }

                $data = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'type' => User::TYPE_MERCHANT,
                    'password' => Hash::make('password'),
                ];

                $user = User::create($data);

                $merchantdata = [
                    'user_id' =>  $user->id,
                    'domain' => $data['domain'],
                    'display_name' => $data['name'],

                ];

                $merchant = Merchant::create($merchantdata);
                return $merchant;
            });
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    /**
     * Update the user
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
    {
        // TODO: Complete this method
        try {
            //code...
            DB::transaction(function ($data, $user) {

                if (User::where('email', $data['email'])->where('id', '!=', $user->id)->exists()) {
                    throw new Exception('Email already exist. Please Enter a unique email');
                }

                if (Merchant::where('domain', $data['domain'])->where('id', $user->merchant->id)->exists()) {
                    throw new Exception('Domain already exist. Please Enter a unique domain');
                }

                $data = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    // 'type' => User::TYPE_MERCHANT,
                    'password' => Hash::make('password'),
                ];

                $user = User::create($data);

                $merchantdata = [
                    'domain' => $data['domain'],
                    'display_name' => $data['name'],
                ];

                $merchant = $user->merchant::update($merchantdata);
                return $merchant;
            });
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
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
        // TODO: Complete this method
        $user = User::where('email', $email)->first();
        $merchant = $user->merchant ?? null;
        return $merchant;
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
        // TODO: Complete this method
        try {
            //code...
            DB::transaction(function ($affiliate) {
                $unpaidOrders = $affiliate->orders()->where('payout_status', Order::STATUS_UNPAID)->get();
                foreach ($unpaidOrders as $order) {
                    PayoutOrderJob::dispatch($order);
                }
            });
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }
}
