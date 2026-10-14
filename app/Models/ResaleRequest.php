<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResaleRequest extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_NEW = 'جديد';
    public const STATUS_QUALIFICATION = 'تأهيل مالي';
    public const STATUS_PROJECTS_SENT = 'ترشيح مشروعات';
    public const STATUS_SITE_VISIT = 'معاينة موقع';
    public const STATUS_OFFER_NEGOTIATION = 'تفاوض وعرض سعر';
    public const STATUS_UNDER_CONTRACT = 'حجز وتوقيع';
    public const STATUS_CLOSED_WON = 'مغلق بنجاح';
    public const STATUS_CLOSED_LOST = 'مغلق دون نجاح';

    protected $fillable = [
        'client_id',
        'broker_id',
        'assigned_to',
        'source',
        'status',
        'purpose',
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
        return $this->hasOne(ResaleRequirement::class);
    }

    public function nominations()
    {
        return $this->hasMany(UnitNomination::class, 'resale_request_id');
    }

    public function dealOutcome()
    {
        return $this->hasOne(DealOutcome::class, 'resale_request_id');
    }

    public function activities()
    {
        return $this->hasMany(Activity::class, 'resale_request_id')->latest('occurred_at');
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class, 'resale_request_id')->latest('due_at');
    }
}
