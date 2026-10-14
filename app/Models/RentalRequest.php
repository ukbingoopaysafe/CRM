<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalRequest extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_NEW = 'جديد';
    public const STATUS_ACTIVE = 'نشط';
    public const STATUS_CONTACTED = 'تم التواصل';
    public const STATUS_SEARCHING = 'جارٍ البحث';
    public const STATUS_OPTIONS_SENT = 'تم إرسال خيارات';
    public const STATUS_SHOWING_SCHEDULED = 'معاينة مجدولة';
    public const STATUS_NEGOTIATING = 'قيد التفاوض';
    public const STATUS_ON_HOLD = 'معلق';
    public const STATUS_CLOSED_WON = 'مغلق بنجاح';
    public const STATUS_CLOSED_LOST = 'مغلق دون نجاح';

    protected $fillable = [
        'client_id',
        'broker_id',
        'assigned_to',
        'source',
        'status',
        'family_status',
        'notes',
    ];

    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_CLOSED_WON, self::STATUS_CLOSED_LOST]);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function broker()
    {
        return $this->belongsTo(Broker::class);
    }

    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function requirements()
    {
        return $this->hasOne(RentalRequirement::class);
    }

    public function nominations()
    {
        return $this->hasMany(UnitNomination::class, 'rental_request_id');
    }

    public function dealOutcome()
    {
        return $this->hasOne(DealOutcome::class, 'rental_request_id');
    }

    public function activities()
    {
        return $this->hasMany(Activity::class, 'rental_request_id')->latest('occurred_at');
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class, 'rental_request_id')->latest('due_at');
    }
}
