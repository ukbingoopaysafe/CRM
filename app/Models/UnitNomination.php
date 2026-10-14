<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitNomination extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_RENTAL = 'rental';
    public const TYPE_RESALE = 'resale';

    public const REACTION_PENDING = 'قيد الانتظار';
    public const REACTION_INTERESTED = 'أبدى اهتماماً';
    public const REACTION_REJECTED = 'مرفوضة من العميل';

    protected $fillable = [
        'request_type',
        'rental_request_id',
        'resale_request_id',
        'unit_code',
        'title',
        'location',
        'property_type',
        'bedrooms',
        'price',
        'currency',
        'media_url',
        'client_reaction',
        'reaction_notes',
        'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'bedrooms' => 'integer',
    ];

    public function rentalRequest()
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function resaleRequest()
    {
        return $this->belongsTo(ResaleRequest::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inspections()
    {
        return $this->hasMany(UnitInspection::class)->latest('inspected_at');
    }

    public function latestInspection()
    {
        return $this->hasOne(UnitInspection::class)->latestOfMany('inspected_at');
    }

    public function showings()
    {
        return $this->hasMany(ClientShowing::class)->latest('scheduled_at');
    }

    /**
     * صمام أمان المعاينات: التحقق مما إذا كانت الوحدة صالحة ومناسبة لجدولة معاينة عميل
     */
    public function isSuitableForShowing(): bool
    {
        $latest = $this->latestInspection;
        return $latest !== null && $latest->result === UnitInspection::RESULT_SUITABLE;
    }
}
