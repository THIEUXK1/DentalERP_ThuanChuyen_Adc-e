<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PriceList extends Model
{
    use LogsActivity;

    protected $fillable = ['code', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->dontSubmitEmptyLogs();
    }

    public static function generateCode(): string
    {
        $last = static::max('id') ?? 0;

        return 'BG-'.str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function items()
    {
        return $this->hasMany(PriceListItem::class);
    }
}
