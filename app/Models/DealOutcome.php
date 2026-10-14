<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealOutcome extends Model
{
    use HasFactory;

    public const OUTCOME_WON = 'ناجحة';
    public const OUTCOME_LOST = 'غير ناجحة';

    protected $fillable = [
        'request_type',
        'rental_request_id',
        'resale_request_id',
        'unit_nomination_id',
        'broker_id',
        'outcome',
        'final_price',
        'currency',
        'commission_amount',
        'broker_commission',
        'loss_reason',
        'notes',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'final_price' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'broker_commission' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function isWon(): bool
    {
        return $this->outcome === self::OUTCOME_WON;
    }

    public function rentalRequest()
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function resaleRequest()
    {
        return $this->belongsTo(ResaleRequest::class);
    }

    public function nomination()
    {
        return $this->belongsTo(UnitNomination::class, 'unit_nomination_id');
    }

    public function broker()
    {
        return $this->belongsTo(Broker::class);
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function getClientAttribute(): ?Client
    {
        if ($this->rentalRequest) {
            return $this->rentalRequest->client;
        }
        if ($this->resaleRequest) {
            return $this->resaleRequest->client;
        }
        return null;
    }
}
