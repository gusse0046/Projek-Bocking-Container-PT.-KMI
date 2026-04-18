<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ExportData extends Model
{
    use HasFactory;

    protected $table = 'export_data';

    protected $fillable = [
        // Core delivery information
        'delivery',
        'no_item',
        'material',
        'description',
        'proforma_shipping_instruction',
        'buyer',
        'quantity',
        'volume',
        'weight',
        'export_destination',
        
        // SAP integration fields
        'container_number',
        'reference_invoice', 
        'plant',
        'shipping_point',
        'delivery_date',
        'created_date',
        'forwarder_code',
        'sap_synced_at',
        
        // Additional SAP fields
        'sap_delivery_status',
        'sap_customer_number',
        'sap_material_group',
        'sap_sales_unit',
        'sap_weight_unit',
        'sap_volume_unit',
        'sap_created_by',
        'sap_last_update',
        
        // Delivery Type Classification Fields
        'delivery_type',
        'delivery_classification',
        'operation_location',
        'route_destination',
        'business_unit'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'volume' => 'decimal:2',
        'weight' => 'decimal:2',
        'delivery_date' => 'date',
        'created_date' => 'date',
        'sap_synced_at' => 'datetime',
        'sap_last_update' => 'datetime'
    ];

    /**
     * Delivery Type Mapping berdasarkan gambar yang diberikan
     */
    const DELIVERY_TYPE_MAPPING = [
        // Export delivery types
        'EXPORT' => [
            'ZDO1' => 'KMT DO Export SBY',  // Surabaya Export
            'ZDO2' => 'KMT DO Export SMG'   // Semarang Export
        ],
        
        // Import delivery types
        'IMPORT' => [
            'ZDI1' => 'KMT DO Import SBY',  // Surabaya Import
            'ZDI2' => 'KMT DO Import SMG'   // Semarang Import
        ],
        
        // Local delivery types
        'LOCAL' => [
            'ZDL1' => 'KMT DO Local SBY',   // Surabaya Local
            'ZDL2' => 'KMT DO Local SMG'    // Semarang Local
        ],
        
        // Return delivery types
        'RETURN' => [
            'ZDR1' => 'KMT DO Return Exp.',  // Return Export
            'ZDR2' => 'KMT DO Return Local'  // Return Local
        ]
    ];

    /**
     * Location mapping berdasarkan delivery type
     */
    const LOCATION_MAPPING = [
        'SURABAYA' => ['ZDO1', 'ZDI1', 'ZDL1'],
        'SEMARANG' => ['ZDO2', 'ZDI2', 'ZDL2'],
        'RETURN' => ['ZDR1', 'ZDR2']
    ];

    /**
     * DYNAMIC: Extract 3-digit numeric prefix from reference invoice (runtime only)
     */
    public function extractNumericPrefix()
    {
        if (!$this->reference_invoice) {
            return null;
        }

        // Extract exactly 3 digits from the start of reference invoice
        if (preg_match('/^(\d{3})/', $this->reference_invoice, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * DYNAMIC: Get invoice group key for prefix grouping (runtime only)
     */
    public function getInvoiceGroupKey()
    {
        $location = $this->delivery_type === 'ZDO1' ? 'surabaya' : 
                   ($this->delivery_type === 'ZDO2' ? 'semarang' : 'unknown');
        
        $forwarderCode = $this->forwarder_code ?: 'UNASSIGNED';
        $numericPrefix = $this->extractNumericPrefix();
        
        return "{$location}_{$forwarderCode}_{$numericPrefix}";
    }

    /**
     * DYNAMIC: Check if this invoice can be combined with others (runtime check)
     */
    public function canBeCombined()
    {
        if (!$this->reference_invoice) {
            return false;
        }

        $numericPrefix = $this->extractNumericPrefix();
        if (!$numericPrefix) {
            return false;
        }

        // Dynamic check - count other invoices with same prefix, location, and forwarder
        $similarCount = self::where('id', '!=', $this->id)
            ->where('delivery_type', $this->delivery_type)
            ->where('forwarder_code', $this->forwarder_code)
            ->where('reference_invoice', 'LIKE', $numericPrefix . '%')
            ->count();

        return $similarCount > 0;
    }

    /**
     * DYNAMIC: Get all invoices in the same prefix group (runtime query)
     */
    public function getGroupInvoices()
    {
        $numericPrefix = $this->extractNumericPrefix();
        if (!$numericPrefix) {
            return collect([$this]);
        }

        return self::where('delivery_type', $this->delivery_type)
            ->where('forwarder_code', $this->forwarder_code)
            ->where('reference_invoice', 'LIKE', $numericPrefix . '%')
            ->orderBy('reference_invoice')
            ->get();
    }

    /**
     * DYNAMIC: Get combined display name for grouped invoices (runtime generation)
     */
    public function getCombinedDisplayName()
    {
        $groupInvoices = $this->getGroupInvoices();
        
        if ($groupInvoices->count() > 1) {
            $firstInvoice = $groupInvoices->first()->reference_invoice;
            $baseName = preg_replace('/-\d+$/', '', $firstInvoice); // Remove -1, -2, etc
            return "{$baseName} (Combined)";
        }

        return $this->reference_invoice;
    }

    /**
     * STATIC: Get group summary for this invoice (dynamic calculation)
     */
    public function getGroupSummary()
    {
        $groupInvoices = $this->getGroupInvoices();
        $isCombined = $groupInvoices->count() > 1;
        
        return [
            'is_combined' => $isCombined,
            'sub_count' => $groupInvoices->count(),
            'numeric_prefix' => $this->extractNumericPrefix(),
            'display_name' => $this->getCombinedDisplayName(),
            'total_volume' => $groupInvoices->sum('volume'),
            'total_weight' => $groupInvoices->sum('weight'),
            'total_quantity' => $groupInvoices->sum('quantity'),
            'total_items' => $groupInvoices->count(),
            'all_buyers' => $groupInvoices->pluck('buyer')->unique()->values()->toArray(),
            'primary_buyer' => $groupInvoices->first()->buyer,
            'invoices' => $groupInvoices->pluck('reference_invoice')->toArray()
        ];
    }

    /**
     * Scopes untuk filtering berdasarkan classification
     */
    public function scopeExportOnly(Builder $query): Builder
    {
        return $query->where('delivery_classification', 'EXPORT');
    }

    public function scopeImportOnly(Builder $query): Builder
    {
        return $query->where('delivery_classification', 'IMPORT');
    }

    public function scopeLocalOnly(Builder $query): Builder
    {
        return $query->where('delivery_classification', 'LOCAL');
    }

    /**
     * Scope untuk filtering berdasarkan lokasi
     */
    public function scopeSurabayaOnly(Builder $query): Builder
    {
        return $query->whereIn('delivery_type', self::LOCATION_MAPPING['SURABAYA']);
    }

    public function scopeSemarangOnly(Builder $query): Builder
    {
        return $query->whereIn('delivery_type', self::LOCATION_MAPPING['SEMARANG']);
    }

    /**
     * DYNAMIC: Scope for dynamic prefix grouping queries
     */
    public function scopeByNumericPrefix(Builder $query, string $prefix): Builder
    {
        return $query->where('reference_invoice', 'LIKE', $prefix . '%');
    }

    public function scopeForPrefixGrouping(Builder $query, string $deliveryType, string $forwarderCode, string $prefix): Builder
    {
        return $query->where('delivery_type', $deliveryType)
                    ->where('forwarder_code', $forwarderCode)
                    ->where('reference_invoice', 'LIKE', $prefix . '%');
    }

    /**
     * Scope untuk dashboard export
     */
    public function scopeForExportDashboard(Builder $query): Builder
    {
        return $query->where('delivery_classification', 'EXPORT')
                    ->whereIn('delivery_type', ['ZDO1', 'ZDO2']);
    }

    /**
     * Scope untuk dashboard import
     */
    public function scopeForImportDashboard(Builder $query): Builder
    {
        return $query->where('delivery_classification', 'IMPORT')
                    ->whereIn('delivery_type', ['ZDI1', 'ZDI2']);
    }

    /**
     * Scope untuk export Surabaya
     */
    public function scopeExportSurabaya(Builder $query): Builder
    {
        return $query->where('delivery_type', 'ZDO1')
                    ->where('delivery_classification', 'EXPORT');
    }

    /**
     * Scope untuk export Semarang
     */
    public function scopeExportSemarang(Builder $query): Builder
    {
        return $query->where('delivery_type', 'ZDO2')
                    ->where('delivery_classification', 'EXPORT');
    }

    /**
     * Scope untuk import Surabaya
     */
    public function scopeImportSurabaya(Builder $query): Builder
    {
        return $query->where('delivery_type', 'ZDI1')
                    ->where('delivery_classification', 'IMPORT');
    }

    /**
     * Scope untuk import Semarang
     */
    public function scopeImportSemarang(Builder $query): Builder
    {
        return $query->where('delivery_type', 'ZDI2')
                    ->where('delivery_classification', 'IMPORT');
    }

    /**
     * Get delivery type description
     */
    public function getDeliveryTypeDescriptionAttribute(): string
    {
        foreach (self::DELIVERY_TYPE_MAPPING as $classification => $types) {
            if (isset($types[$this->delivery_type])) {
                return $types[$this->delivery_type];
            }
        }
        return "Unknown ({$this->delivery_type})";
    }

    /**
     * Get location from delivery type
     */
    public function getLocationAttribute(): string
    {
        foreach (self::LOCATION_MAPPING as $location => $types) {
            if (in_array($this->delivery_type, $types)) {
                return $location;
            }
        }
        return 'UNKNOWN';
    }

    /**
     * Check if this is export record
     */
    public function isExport(): bool
    {
        return $this->delivery_classification === 'EXPORT' && 
               in_array($this->delivery_type, ['ZDO1', 'ZDO2']);
    }

    /**
     * Check if this is import record
     */
    public function isImport(): bool
    {
        return $this->delivery_classification === 'IMPORT' && 
               in_array($this->delivery_type, ['ZDI1', 'ZDI2']);
    }

    /**
     * Check if this is Surabaya operation
     */
    public function isSurabaya(): bool
    {
        return in_array($this->delivery_type, self::LOCATION_MAPPING['SURABAYA']);
    }

    /**
     * Check if this is Semarang operation
     */
    public function isSemarang(): bool
    {
        return in_array($this->delivery_type, self::LOCATION_MAPPING['SEMARANG']);
    }

    /**
     * Auto classify delivery type
     */
    public function autoClassifyDeliveryType(): bool
    {
        if (empty($this->delivery_type)) {
            return false;
        }

        $classification = 'UNKNOWN';
        $location = 'UNKNOWN';
        $businessUnit = null;
        $routeDestination = null;

        // Determine classification and location
        foreach (self::DELIVERY_TYPE_MAPPING as $type => $codes) {
            if (array_key_exists($this->delivery_type, $codes)) {
                $classification = $type;
                break;
            }
        }

        // Determine location
        foreach (self::LOCATION_MAPPING as $loc => $types) {
            if (in_array($this->delivery_type, $types)) {
                $location = $loc;
                break;
            }
        }

        // Set business unit and route
        switch ($classification) {
            case 'EXPORT':
                $businessUnit = 'EXPORT_DEPT';
                $routeDestination = $location === 'SURABAYA' ? 'Export via Surabaya Port' : 'Export via Semarang Port';
                break;
            case 'IMPORT':
                $businessUnit = 'IMPORT_DEPT';
                $routeDestination = $location === 'SURABAYA' ? 'Import via Surabaya Port' : 'Import via Semarang Port';
                break;
            case 'LOCAL':
                $businessUnit = 'LOCAL_SALES';
                $routeDestination = $location === 'SURABAYA' ? 'Local Distribution - Surabaya' : 'Local Distribution - Semarang';
                break;
            case 'RETURN':
                $businessUnit = 'LOGISTICS';
                $routeDestination = 'Return Processing';
                break;
        }

        return $this->update([
            'delivery_classification' => $classification,
            'operation_location' => $location,
            'business_unit' => $businessUnit,
            'route_destination' => $routeDestination
        ]);
    }

    /**
     * Relationships
     */
    public function forwarder()
    {
        return $this->belongsTo(Forwarder::class, 'forwarder_code', 'code');
    }

    /**
     * Get forwarder with complete contact details
     */
    public function getForwarderWithContactDetails()
    {
        $forwarder = $this->forwarder;
        
        if (!$forwarder) {
            return null;
        }

        // Parse JSON fields if needed
        $emails = $forwarder->emails;
        if (is_string($emails)) {
            $emails = json_decode($emails, true) ?? [];
        }

        $whatsappNumbers = $forwarder->whatsapp_numbers;
        if (is_string($whatsappNumbers)) {
            $whatsappNumbers = json_decode($whatsappNumbers, true) ?? [];
        }

        return [
            'forwarder' => $forwarder,
            'primary_email' => $forwarder->primary_email ?? ($emails[0] ?? null),
            'all_emails' => $emails,
            'cc_emails' => array_filter($emails, fn($email) => $email !== ($forwarder->primary_email ?? ($emails[0] ?? null))),
            'primary_whatsapp' => $forwarder->primary_whatsapp ?? ($whatsappNumbers[0] ?? null),
            'all_whatsapp' => $whatsappNumbers,
            'contact_person' => $forwarder->contact_person,
            'phone' => $forwarder->phone,
            'address' => $forwarder->address
        ];
    }

    /**
     * Model events
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-classify when saving
        static::saving(function ($exportData) {
            if ($exportData->isDirty('delivery_type') && $exportData->delivery_type) {
                $exportData->autoClassifyDeliveryType();
            }
        });

        // Update timestamp when updating
        static::updating(function ($exportData) {
            if ($exportData->isDirty() && !$exportData->isDirty('sap_last_update')) {
                $exportData->sap_last_update = now();
            }
        });
    }
}