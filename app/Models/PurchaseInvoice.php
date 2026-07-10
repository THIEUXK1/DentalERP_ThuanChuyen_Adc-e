<?php

namespace App\Models;

use App\Enums\PurchaseInvoiceStatus;
use App\Models\Concerns\GeneratesUniqueCode;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    use GeneratesUniqueCode;

    protected $fillable = [
        'code', 'supplier_id', 'branch_id', 'fund_account_id', 'invoice_date', 'due_date',
        'status', 'subtotal', 'vat_amount', 'total', 'paid_amount', 'payment_method', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status'       => PurchaseInvoiceStatus::class,
            'invoice_date' => 'date',
            'due_date'     => 'date',
        ];
    }

    public static function generateCode(): string
    {
        $last = static::max('id') ?? 0;
        return 'HD-MUA-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function amountDue(): int
    {
        return max(0, $this->total - $this->paid_amount);
    }

    public function supplier()    { return $this->belongsTo(Supplier::class); }
    public function branch()      { return $this->belongsTo(Branch::class); }
    public function fundAccount() { return $this->belongsTo(FundAccount::class); }
    public function items()       { return $this->hasMany(PurchaseInvoiceItem::class); }
    public function creator()     { return $this->belongsTo(User::class, 'created_by'); }
}
