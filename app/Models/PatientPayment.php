<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;

class PatientPayment extends Model
{
    protected $fillable = [
        'invoice_id', 'legacy_clinic_record_id', 'amount', 'method', 'payment_date', 'reference', 'notes', 'created_by', 'reverses_payment_id',
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

    /** The original payment this row reverses, if this row is itself a reversal entry. */
    public function reversalOf()
    {
        return $this->belongsTo(PatientPayment::class, 'reverses_payment_id');
    }

    /** The reversal entry that undoes this payment, if one has been recorded. */
    public function reversal()
    {
        return $this->hasOne(PatientPayment::class, 'reverses_payment_id');
    }
}
