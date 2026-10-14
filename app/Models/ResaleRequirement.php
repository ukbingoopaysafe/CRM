<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResaleRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'resale_request_id',
        'property_type',
        'bedrooms',
        'preferred_locations',
        'payment_method',
        'total_budget',
        'down_payment_max',
        'installment_capacity',
        'installment_frequency',
        'installment_years',
        'delivery_target',
        'finishing_level',
        'currency',
        'financial_notes',
    ];

    protected $casts = [
        'total_budget' => 'decimal:2',
        'down_payment_max' => 'decimal:2',
        'installment_capacity' => 'decimal:2',
        'installment_years' => 'integer',
        'bedrooms' => 'integer',
    ];

    public function resaleRequest()
    {
        return $this->belongsTo(ResaleRequest::class);
    }
}
