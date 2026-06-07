<?php

namespace App\Services;

use App\Models\DentalService;
use App\Models\PriceList;

class PriceResolver
{
    public function resolve(DentalService $service, ?PriceList $priceList): int
    {
        if ($priceList) {
            $item = $priceList->items()->where('service_id', $service->id)->first();
            if ($item) {
                return (int) $item->unit_price;
            }
        }

        return (int) $service->selling_price;
    }
}
