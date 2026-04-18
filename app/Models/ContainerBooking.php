<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ContainerBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'shipping_instruction_id',
        'instruction_id',
        'forwarder_code',
        'container_type',
        'pickup_date',
        'pickup_time',
        'status',
        'driver_name',
        'driver_phone',
        'truck_number',
        'special_instructions',
        'status_notes',
        'completion_notes',
        'driver_assigned_at',
        'completed_at'
    ];

    protected $casts = [
        'pickup_date' => 'date',
        'driver_assigned_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    // Status constants
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_READY = 'ready';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Container type constants
    const TYPE_20FT = '20ft';
    const TYPE_40FT = '40ft';
    const TYPE_40FT_HC = '40ft_hc';
    const TYPE_45FT = '45ft';

    public static function getStatusOptions()
    {
        return [
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_READY => 'Ready for Pickup',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled'
        ];
    }

    public static function getContainerTypeOptions()
    {
        return [
            self::TYPE_20FT => '20ft Standard',
            self::TYPE_40FT => '40ft Standard',
            self::TYPE_40FT_HC => '40ft High Cube',
            self::TYPE_45FT => '45ft High Cube'
        ];
    }

    // Relationships
    public function shippingInstruction()
    {
        return $this->belongsTo(ShippingInstruction::class);
    }

    public function forwarder()
    {
        return $this->belongsTo(Forwarder::class, 'forwarder_code', 'code');
    }

    // Scopes
    public function scopeByForwarder($query, $forwarderCode)
    {
        return $query->where('forwarder_code', $forwarderCode);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('pickup_date', today());
    }

    public function scopeUpcoming($query, $days = 7)
    {
        return $query->whereBetween('pickup_date', [today(), today()->addDays($days)]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('pickup_date', '<', today())
                    ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    // Accessors
    public function getFormattedStatusAttribute()
    {
        $statuses = self::getStatusOptions();
        return $statuses[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getFormattedContainerTypeAttribute()
    {
        $types = self::getContainerTypeOptions();
        return $types[$this->container_type] ?? $this->container_type;
    }

    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case self::STATUS_SCHEDULED:
                return 'info';
            case self::STATUS_CONFIRMED:
                return 'primary';
            case self::STATUS_READY:
                return 'warning';
            case self::STATUS_IN_PROGRESS:
                return 'warning';
            case self::STATUS_COMPLETED:
                return 'success';
            case self::STATUS_CANCELLED:
                return 'danger';
            default:
                return 'secondary';
        }
    }

    public function getIsOverdueAttribute()
    {
        return $this->pickup_date->isPast() && 
               !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function getIsToday()
    {
        return $this->pickup_date->isToday();
    }

    public function getIsTomorrow()
    {
        return $this->pickup_date->isTomorrow();
    }

    public function getDaysUntilPickupAttribute()
    {
        return today()->diffInDays($this->pickup_date, false);
    }

    public function getFormattedPickupDateTimeAttribute()
    {
        return $this->pickup_date->format('M j, Y') . ' at ' . 
               Carbon::createFromFormat('H:i', $this->pickup_time)->format('g:i A');
    }

    public function getHasDriverAttribute()
    {
        return !empty($this->driver_name) && !empty($this->driver_phone);
    }

    // Methods
    public function assignDriver($driverName, $driverPhone, $truckNumber = null)
    {
        $this->update([
            'driver_name' => $driverName,
            'driver_phone' => $driverPhone,
            'truck_number' => $truckNumber,
            'driver_assigned_at' => now()
        ]);

        // Fire driver assigned event
        event(new \App\Events\DriverAssigned($this));
    }

    public function updateStatus($newStatus, $notes = null)
    {
        $oldStatus = $this->status;
        
        $updateData = [
            'status' => $newStatus,
            'status_notes' => $notes
        ];

        if ($newStatus === self::STATUS_COMPLETED) {
            $updateData['completed_at'] = now();
        }

        $this->update($updateData);

        // Update related shipping instruction status
        if ($newStatus === self::STATUS_READY) {
            $this->shippingInstruction->updateStatus(ShippingInstruction::STATUS_CONTAINER_READY);
        } elseif ($newStatus === self::STATUS_COMPLETED) {
            $this->shippingInstruction->updateStatus(ShippingInstruction::STATUS_COMPLETED);
        }

        // Fire status change event
        event(new \App\Events\ContainerBookingStatusChanged($this, $oldStatus, $newStatus));
    }

    public function canBeModified()
    {
        return !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function canBeCancelled()
    {
        return $this->status !== self::STATUS_COMPLETED && 
               $this->pickup_date->isFuture();
    }

    public function getEstimatedDuration()
    {
        // Estimate based on container type
        switch ($this->container_type) {
            case self::TYPE_20FT:
                return '2-3 hours';
            case self::TYPE_40FT:
            case self::TYPE_40FT_HC:
                return '3-4 hours';
            case self::TYPE_45FT:
                return '4-5 hours';
            default:
                return '2-4 hours';
        }
    }

    public function getContainerCapacity()
    {
        switch ($this->container_type) {
            case self::TYPE_20FT:
                return ['volume' => 33, 'weight' => 25000];
            case self::TYPE_40FT:
                return ['volume' => 67, 'weight' => 26500];
            case self::TYPE_40FT_HC:
                return ['volume' => 76, 'weight' => 26500];
            case self::TYPE_45FT:
                return ['volume' => 85, 'weight' => 28000];
            default:
                return ['volume' => 33, 'weight' => 25000];
        }
    }

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->booking_id) {
                $model->booking_id = self::generateBookingId();
            }
        });
    }

    public static function generateBookingId()
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        
        return "BK-{$year}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    // Static methods for statistics
    public static function getMonthlyStats($forwarderCode = null)
    {
        $query = self::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
        
        if ($forwarderCode) {
            $query->where('forwarder_code', $forwarderCode);
        }

        return [
            'total' => $query->count(),
            'completed' => $query->where('status', self::STATUS_COMPLETED)->count(),
            'pending' => $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED])->count(),
            'cancelled' => $query->where('status', self::STATUS_CANCELLED)->count()
        ];
    }

    public static function getUpcomingPickups($forwarderCode = null, $days = 7)
    {
        $query = self::whereBetween('pickup_date', [today(), today()->addDays($days)])
                    ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED])
                    ->orderBy('pickup_date')
                    ->orderBy('pickup_time');

        if ($forwarderCode) {
            $query->where('forwarder_code', $forwarderCode);
        }

        return $query->get();
    }
}