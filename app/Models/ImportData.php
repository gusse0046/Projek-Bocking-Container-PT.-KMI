<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportData extends Model
{
    use HasFactory;

    protected $table = 'import_data';

    protected $fillable = [
        'purchase_order',
        'vendor',
        'material_code',
        'material_description',
        'quantity',
        'value',
        'origin_country',
        'expected_arrival',
        'status'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'value' => 'decimal:2',
        'expected_arrival' => 'date'
    ];

    /**
     * Scope for specific status
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending imports
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get total value for multiple records
     */
    public static function getTotalValue($ids)
    {
        return self::whereIn('id', $ids)->sum('value');
    }

    /**
     * Format value for display
     */
    public function getFormattedValueAttribute()
    {
        return '$' . number_format($this->value, 2);
    }

    /**
     * Get priority based on value and date
     */
    public function getPriorityAttribute()
    {
        if ($this->value > 50000) {
            return 'high';
        }
        
        if ($this->expected_arrival <= now()->addDays(7)) {
            return 'urgent';
        }
        
        return 'normal';
    }

    /**
     * Check if import is overdue
     */
    public function getIsOverdueAttribute()
    {
        return $this->expected_arrival < now() && $this->status === 'pending';
    }
}