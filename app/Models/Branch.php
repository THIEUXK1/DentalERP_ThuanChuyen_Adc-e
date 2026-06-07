<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Branch extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = ['code', 'name', 'address', 'phone', 'manager_id', 'is_active'];

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
        $last = static::withTrashed()->max('id') ?? 0;

        return 'CN-'.str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
