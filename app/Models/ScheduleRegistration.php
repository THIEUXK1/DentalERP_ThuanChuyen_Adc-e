<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleRegistration extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'code', 'patient_id', 'appointment_id', 'branch_id', 'doctor_id', 'dental_chair_id',
        'registration_date', 'visit_time', 'status', 'pending_since', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
            'pending_since'     => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $r) {
            if ($r->status === 'pending') {
                $r->pending_since = now();
            }
        });

        static::updating(function (self $r) {
            if ($r->isDirty('status')) {
                $r->pending_since = $r->status === 'pending' ? now() : null;
            }
        });
    }

    public function patient(): BelongsTo     { return $this->belongsTo(Patient::class); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function doctor(): BelongsTo      { return $this->belongsTo(Employee::class, 'doctor_id'); }
    public function chair(): BelongsTo    { return $this->belongsTo(DentalChair::class, 'dental_chair_id'); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function creator(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }

    public static function generateCode(): string
    {
        $date   = now()->format('Ymd');
        $prefix = "REG-{$date}-";
        $last   = static::withTrashed()->where('code', 'like', $prefix . '%')->max('code');
        $seq    = $last ? ((int) substr($last, -4)) + 1 : 1;
        $code   = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
        while (static::withTrashed()->where('code', $code)->exists()) {
            $seq++;
            $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
        }
        return $code;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'      => 'Đang chờ',
            'in_treatment' => 'Đang làm',
            'completed'    => 'Hoàn thành',
            'cancelled'    => 'Đã hủy',
            default        => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending'      => 'yellow',
            'in_treatment' => 'teal',
            'completed'    => 'green',
            'cancelled'    => 'red',
            default        => 'gray',
        };
    }
}
