<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'normalized_phone',
        'email',
        'nationality',
        'notes',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::saving(function (Client $client) {
            if ($client->phone) {
                $client->normalized_phone = static::normalizePhone($client->phone);
            }
        });
    }

    /**
     * تنقية وتوحيد رقم الهاتف لتسهيل البحث ومنع تكرار السجلات
     */
    public static function normalizePhone(?string $phone): string
    {
        if (!$phone) {
            return '';
        }

        // إزالة أي مسافات أو رموز غير رقمية باستثناء علامة +
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        // معالجة الأرقام المصرية كمثال شائع: تحويل 010/011/012 إلى 2010/2011/2012
        if (str_starts_with($cleaned, '01') && strlen($cleaned) === 11) {
            $cleaned = '2' . $cleaned;
        }

        return $cleaned;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rentalRequests()
    {
        return $this->hasMany(RentalRequest::class)->latest();
    }

    public function resaleRequests()
    {
        return $this->hasMany(ResaleRequest::class)->latest();
    }

    public function activities()
    {
        return $this->hasMany(Activity::class)->latest('occurred_at');
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class)->latest('due_at');
    }
}
