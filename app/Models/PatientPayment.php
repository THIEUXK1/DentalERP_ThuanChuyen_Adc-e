<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;

class PatientPayment extends Model
{
    protected $fillable = [
        'invoice_id', 'amount', 'method', 'payment_date', 'reference', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'payment_date' => 'date',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(PatientInvoice::class, 'invoice_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
