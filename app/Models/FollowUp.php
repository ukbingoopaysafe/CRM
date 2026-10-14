<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'معلقة';
    public const STATUS_COMPLETED = 'مكتملة';
    public const STATUS_CANCELLED = 'ملغاة';

    protected $fillable = [
        'client_id',
        'request_type',
        'rental_request_id',
        'resale_request_id',
        'assigned_to',
        'due_at',
        'action_required',
        'notes',
        'status',
        'completed_at',
        'completion_notes',
        'completed_by',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->due_at->isPast();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING)->where('due_at', '<', now());
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING)->whereDate('due_at', today());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING)->where('due_at', '>', now());
    }

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

    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * إكمال المتابعة وتسجيل نشاط اختياري يشرح ما حدث
     */
    public function markCompleted(?string $notes = null, ?int $userId = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'completion_notes' => $notes,
            'completed_by' => $userId ?? auth()->id(),
        ]);

        if ($notes) {
            Activity::log(
                type: Activity::TYPE_CALL,
                description: 'إكمال متابعة: ' . $this->action_required . ' - النتيجة: ' . $notes,
                userId: $userId,
                clientId: $this->client_id,
                requestType: $this->request_type,
                requestId: $this->request_type === 'rental' ? $this->rental_request_id : $this->resale_request_id
            );
        }
    }
}
