<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_request_id',
        'property_type',
        'bedrooms',
        'furnishing_status',
        'preferred_locations',
        'rental_duration',
        'move_in_date',
        'stated_budget_min',
        'stated_budget_max',
        'qualified_budget_min',
        'qualified_budget_max',
        'currency',
        'discussion_notes',
    ];

    protected $casts = [
        'move_in_date' => 'date',
        'stated_budget_min' => 'decimal:2',
        'stated_budget_max' => 'decimal:2',
        'qualified_budget_min' => 'decimal:2',
        'qualified_budget_max' => 'decimal:2',
    ];

    public function rentalRequest()
    {
        return $this->belongsTo(RentalRequest::class);
    }
}
