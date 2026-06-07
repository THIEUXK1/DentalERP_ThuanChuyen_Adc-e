<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'branch_id', 'category', 'description', 'amount',
        'expense_date', 'payment_method', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => ExpenseCategory::class,
            'payment_method' => PaymentMethod::class,
            'expense_date' => 'date',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
