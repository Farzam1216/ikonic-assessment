<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {
        $this->affiliateService = $affiliateService;
    }

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
        // TODO: Complete this method

        try {
            //code...
            DB::transaction(function ($data) {
                $order = Order::find($data['order_id']);

                if (isset($order)) {
                    throw new Exception('Order Already Exists');
                }

                $user = User::where('email', $data['email'])->first();
                $merchant = Merchant::where('domain', $data['merchant_domain'])->first();
                $affiliate = Affiliate::where('user_id', $user['id'])->where('merchant_id', $merchant->id)->first();

                if (!isset($affiliate)) {
                    $affiliate =  $this->affiliateService->register($merchant, $data['customer_email'], $data['customer_name'], 5);
                }

                if (isset($affiliate->commission_rate)) {
                    $commissionRate = $data['subtotal_price'] * ($affiliate->commission_rate / 100);
                    Log::info("Commission rate " . $commissionRate);
                } else {
                    $commissionRate = null;
                }

                $data = [
                    'merchant_id' => $affiliate->merchant_id,
                    'affiliate_id' => $affiliate->id,
                    'subtotal' => $data['subtotal_price'],
                    'commission_owed' => $commissionRate,
                    'payout_status' => Order::STATUS_UNPAID,
                    'discount_code' => $data['discount_code']
                ];
                $order =  Order::create($data);
            });
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }
}
