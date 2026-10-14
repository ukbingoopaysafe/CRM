<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientShowing extends Model
{
    use HasFactory;

    public const STATUS_SCHEDULED = 'مجدولة';
    public const STATUS_CONFIRMED = 'مؤكدة';
    public const STATUS_COMPLETED = 'مكتملة';
    public const STATUS_NO_SHOW = 'عدم حضور العميل';
    public const STATUS_CANCELLED = 'ملغاة';
    public const STATUS_RESCHEDULED = 'معاد جدولتها';

    protected $fillable = [
        'unit_nomination_id',
        'assigned_to',
        'scheduled_at',
        'status',
        'client_feedback',
        'objections',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function nomination()
    {
        return $this->belongsTo(UnitNomination::class, 'unit_nomination_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
