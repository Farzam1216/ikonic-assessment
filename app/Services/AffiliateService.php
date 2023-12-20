<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {
        $this->apiService = $apiService;
    }

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
        // TODO: Complete this method

        try {
            //code...
            DB::transaction(function ($merchant, $commissionRate) {
                $discountCode = $this->apiService->createDiscountCode($merchant);

                $affiliate = Affiliate::create([
                    'user_id' => $merchant->user->id,
                    'merchant_id' => $merchant->id,
                    'commission_rate' => $commissionRate,
                    'discount_code' => $discountCode['code'],
                ]);

                Mail::to($affiliate->user)->send(new AffiliateCreated($affiliate));

                return $affiliate;
            });
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
