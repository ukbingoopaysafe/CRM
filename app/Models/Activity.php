<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    public const TYPE_CALL = 'مكالمة هاتفية';
    public const TYPE_WHATSAPP = 'رسالة واتساب';
    public const TYPE_MEETING = 'اجتماع';
    public const TYPE_SHOWING = 'معاينة ميدانية';
    public const TYPE_INSPECTION = 'فحص وحدة';
    public const TYPE_NOMINATION = 'ترشيح خيارات';
    public const TYPE_STATUS_CHANGE = 'تحديث حالة';
    public const TYPE_DEAL_CLOSED = 'إغلاق صفقة';
    public const TYPE_NOTE = 'ملاحظة عامة';

    protected $fillable = [
        'client_id',
        'request_type',
        'rental_request_id',
        'resale_request_id',
        'user_id',
        'type',
        'description',
        'result',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function rentalRequest()
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function resaleRequest()
    {
        return $this->belongsTo(ResaleRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * تسجيل نشاط سريع بسهولة من أي مكان بالنظام
     */
    public static function log(
        string $type,
        string $description,
        ?int $userId = null,
        ?int $clientId = null,
        ?string $requestType = null,
        ?int $requestId = null,
        ?string $result = null
    ): self {
        return self::create([
            'client_id' => $clientId,
            'request_type' => $requestType,
            'rental_request_id' => $requestType === 'rental' ? $requestId : null,
            'resale_request_id' => $requestType === 'resale' ? $requestId : null,
            'user_id' => $userId ?? auth()->id(),
            'type' => $type,
            'description' => $description,
            'result' => $result,
            'occurred_at' => now(),
        ]);
    }
}
