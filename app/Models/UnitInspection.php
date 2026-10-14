<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitInspection extends Model
{
    use HasFactory;

    public const RESULT_SUITABLE = 'مناسبة';
    public const RESULT_UNSUITABLE = 'غير مناسبة';

    protected $fillable = [
        'unit_nomination_id',
        'inspected_by',
        'inspected_at',
        'result',
        'is_matched_reality',
        'condition_rating',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'inspected_at' => 'datetime',
        'is_matched_reality' => 'boolean',
    ];

    public function nomination()
    {
        return $this->belongsTo(UnitNomination::class, 'unit_nomination_id');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function isSuitable(): bool
    {
        return $this->result === self::RESULT_SUITABLE;
    }
}
