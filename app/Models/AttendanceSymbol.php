<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSymbol extends Model
{
    protected $fillable = [
        'code', 'label', 'color',
        'is_paid', 'counts_as_workday', 'counts_as_leave',
        'counts_as_unpaid_leave', 'counts_as_overtime',
        'default_paid_workday', 'active',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'counts_as_workday' => 'boolean',
            'counts_as_leave' => 'boolean',
            'counts_as_unpaid_leave' => 'boolean',
            'counts_as_overtime' => 'boolean',
            'default_paid_workday' => 'float',
            'active' => 'boolean',
        ];
    }

    public function displayCode(): string
    {
        return $this->code === 'O' ? 'Ô' : $this->code;
    }

    public static function activeMap(): array
    {
        return static::where('active', true)->get()
            ->keyBy('code')
            ->map(fn ($s) => [
                'code' => $s->code,
                'label' => $s->label,
                'color' => $s->color,
                'display' => $s->displayCode(),
                'paid_workday' => $s->default_paid_workday,
                'is_overtime' => $s->counts_as_overtime,
                'is_unpaid_leave' => $s->counts_as_unpaid_leave,
            ])->toArray();
    }
}
