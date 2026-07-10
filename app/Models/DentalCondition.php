<?php

namespace App\Models;

use App\Enums\DentalConditionGroup;
use App\Models\Concerns\GeneratesUniqueCode;
use Illuminate\Database\Eloquent\Model;

class DentalCondition extends Model
{
    use GeneratesUniqueCode;

    protected $fillable = [
        'code', 'name', 'group', 'description', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'group'      => DentalConditionGroup::class,
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public static function generateCode(): string
    {
        $last = static::max('id') ?? 0;

        return 'DC-'.str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function examinationConditions()
    {
        return $this->hasMany(DentalExaminationCondition::class, 'condition_id');
    }
}
