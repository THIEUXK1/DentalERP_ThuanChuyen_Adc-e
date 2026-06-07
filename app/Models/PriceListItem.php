<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceListItem extends Model
{
    protected $fillable = ['price_list_id', 'service_id', 'unit_price'];

    public function priceList()
    {
        return $this->belongsTo(PriceList::class);
    }

    public function service()
    {
        return $this->belongsTo(DentalService::class, 'service_id');
    }
}
