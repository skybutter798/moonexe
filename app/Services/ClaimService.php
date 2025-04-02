<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class ClaimService
{

    public function calculate($order)
    {

        $baseClaim = $order->earning / 2;

        return [
            'base' => $baseClaim,
        ];
    }
}