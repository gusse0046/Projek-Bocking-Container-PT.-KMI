<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Forwarder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'emails',
        'primary_email',
        'whatsapp_numbers',
        'primary_whatsapp',
        'buyers',
        'destination',
        'contact_person',
        'phone',
        'address',
        'email_notifications_enabled',
        'whatsapp_notifications_enabled',
        'is_active',
        'company_type',
        'service_type',
        'service_routes'
    ];

    protected $casts = [
        'emails' => 'array',
        'whatsapp_numbers' => 'array',
        'buyers' => 'array',
        'service_routes' => 'array',
        'email_notifications_enabled' => 'boolean',
        'whatsapp_notifications_enabled' => 'boolean',
        'is_active' => 'boolean'
    ];

    /**
     * Scope for active forwarders only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for forwarders with email notifications enabled
     */
    public function scopeEmailEnabled(Builder $query): Builder
    {
        return $query->where('email_notifications_enabled', true);
    }

    /**
     * Scope for operational forwarders
     */
    public function scopeOperational(Builder $query): Builder
    {
        return $query->where('is_active', true)
                    ->where('email_notifications_enabled', true);
    }

    /**
     * Get all notification emails for this forwarder
     */
    public function getAllNotificationEmails(): array
    {
        $emails = [];
        
        if ($this->emails && is_array($this->emails)) {
            $emails = array_merge($emails, $this->emails);
        }
        
        if ($this->primary_email && !in_array($this->primary_email, $emails)) {
            $emails[] = $this->primary_email;
        }
        
        return array_unique(array_filter($emails));
    }

    /**
     * Get primary notification email
     */
    public function getPrimaryNotificationEmail(): ?string
    {
        if ($this->primary_email) {
            return $this->primary_email;
        }
        
        $allEmails = $this->getAllNotificationEmails();
        return $allEmails[0] ?? null;
    }

    /**
     * Get CC emails (all emails except primary)
     */
    public function getCcEmails(): array
    {
        $allEmails = $this->getAllNotificationEmails();
        $primaryEmail = $this->getPrimaryNotificationEmail();
        
        return array_values(array_filter($allEmails, function($email) use ($primaryEmail) {
            return $email !== $primaryEmail;
        }));
    }

    /**
     * Get all WhatsApp numbers
     */
    public function getAllWhatsAppNumbers(): array
    {
        $numbers = [];
        
        if ($this->whatsapp_numbers && is_array($this->whatsapp_numbers)) {
            $numbers = array_merge($numbers, $this->whatsapp_numbers);
        }
        
        if ($this->primary_whatsapp && !in_array($this->primary_whatsapp, $numbers)) {
            $numbers[] = $this->primary_whatsapp;
        }
        
        return array_unique(array_filter($numbers));
    }

    /**
     * Get primary WhatsApp number
     */
    public function getPrimaryWhatsAppNumber(): ?string
    {
        if ($this->primary_whatsapp) {
            return $this->primary_whatsapp;
        }
        
        $allNumbers = $this->getAllWhatsAppNumbers();
        return $allNumbers[0] ?? null;
    }

    /**
     * Get secondary WhatsApp numbers
     */
    public function getSecondaryWhatsAppNumbers(): array
    {
        $allNumbers = $this->getAllWhatsAppNumbers();
        $primaryNumber = $this->getPrimaryWhatsAppNumber();
        
        return array_values(array_filter($allNumbers, function($number) use ($primaryNumber) {
            return $number !== $primaryNumber;
        }));
    }

    /**
     * Check if forwarder is operational
     */
    public function isOperational(): bool
    {
        return $this->is_active && 
               $this->email_notifications_enabled && 
               !empty($this->getPrimaryNotificationEmail());
    }

    /**
     * Check if buyer is assigned to this forwarder
     */
    public function handlesBuyer(string $buyer): bool
    {
        if (!$this->buyers || !is_array($this->buyers)) {
            return false;
        }
        
        // Check for exact match first
        if (in_array($buyer, $this->buyers)) {
            return true;
        }
        
        // Check for partial matches
        $buyerUpper = strtoupper($buyer);
        foreach ($this->buyers as $mappedBuyer) {
            $mappedBuyerUpper = strtoupper($mappedBuyer);
            if (strpos($buyerUpper, $mappedBuyerUpper) !== false || 
                strpos($mappedBuyerUpper, $buyerUpper) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get forwarder by buyer name with enhanced matching
     */
    public static function getByBuyer(string $buyer): ?self
    {
        // Try exact match first
        $forwarder = self::active()->whereJsonContains('buyers', $buyer)->first();
        if ($forwarder) {
            return $forwarder;
        }
        
        // Try partial matching
        $forwarders = self::active()->get();
        foreach ($forwarders as $forwarder) {
            if ($forwarder->handlesBuyer($buyer)) {
                return $forwarder;
            }
        }
        
        return null;
    }

    /**
     * Test buyer assignment for auto-assignment system
     */
    public static function testBuyerAssignment(string $buyer): array
    {
        try {
            $forwarder = self::getByBuyer($buyer);
            
            if ($forwarder) {
                return [
                    'buyer_name' => $buyer,
                    'forwarder_found' => true,
                    'forwarder_code' => $forwarder->code,
                    'forwarder_name' => $forwarder->name,
                    'all_emails' => $forwarder->getAllNotificationEmails(),
                    'primary_email' => $forwarder->getPrimaryNotificationEmail(),
                    'cc_emails' => $forwarder->getCcEmails(),
                    'all_whatsapp' => $forwarder->getAllWhatsAppNumbers(),
                    'primary_whatsapp' => $forwarder->getPrimaryWhatsAppNumber(),
                    'secondary_whatsapp' => $forwarder->getSecondaryWhatsAppNumbers(),
                    'contact_person' => $forwarder->contact_person,
                    'phone' => $forwarder->phone,
                    'is_operational' => $forwarder->isOperational(),
                    'assignment_type' => 'auto',
                    'tested_at' => now()->format('Y-m-d H:i:s')
                ];
            }
            
            return [
                'buyer_name' => $buyer,
                'forwarder_found' => false,
                'forwarder_code' => null,
                'forwarder_name' => null,
                'all_emails' => [],
                'primary_email' => null,
                'cc_emails' => [],
                'all_whatsapp' => [],
                'primary_whatsapp' => null,
                'secondary_whatsapp' => [],
                'contact_person' => null,
                'phone' => null,
                'is_operational' => false,
                'assignment_type' => 'manual_required',
                'tested_at' => now()->format('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            return [
                'buyer_name' => $buyer,
                'forwarder_found' => false,
                'assignment_type' => 'error',
                'error_message' => $e->getMessage(),
                'tested_at' => now()->format('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * Get all auto-assignable buyers
     */
    public static function getAutoAssignableBuyers(): array
    {
        return self::active()
                  ->whereNotNull('buyers')
                  ->get()
                  ->pluck('buyers')
                  ->flatten()
                  ->unique()
                  ->values()
                  ->toArray();
    }

    /**
     * Get unmapped buyers from export data
     */
    public static function getUnmappedBuyers(): array
    {
        $assignableBuyers = self::getAutoAssignableBuyers();
        $allBuyers = \App\Models\ExportData::distinct('buyer')
                                           ->whereNotNull('buyer')
                                           ->pluck('buyer')
                                           ->toArray();
        
        return array_diff($allBuyers, $assignableBuyers);
    }

    /**
     * Get mapping statistics
     */
    public static function getMappingStatistics(): array
    {
        $assignable = self::getAutoAssignableBuyers();
        $unmapped = self::getUnmappedBuyers();
        $totalForwarders = self::active()->count();
        $operationalForwarders = self::operational()->count();
        
        return [
            'total_forwarders' => $totalForwarders,
            'operational_forwarders' => $operationalForwarders,
            'auto_assignable_buyers' => count($assignable),
            'unmapped_buyers' => count($unmapped),
            'coverage_percentage' => count($assignable) > 0 
                ? round((count($assignable) / (count($assignable) + count($unmapped))) * 100, 2) 
                : 0,
            'operational_percentage' => $totalForwarders > 0 
                ? round(($operationalForwarders / $totalForwarders) * 100, 2) 
                : 0
        ];
    }

    /**
     * Get operational forwarders with contact details
     */
    public static function getOperationalForwarders(): array
    {
        return self::operational()
                  ->get()
                  ->map(function($forwarder) {
                      return [
                          'code' => $forwarder->code,
                          'name' => $forwarder->name,
                          'primary_email' => $forwarder->getPrimaryNotificationEmail(),
                          'all_emails' => $forwarder->getAllNotificationEmails(),
                          'primary_whatsapp' => $forwarder->getPrimaryWhatsAppNumber(),
                          'all_whatsapp' => $forwarder->getAllWhatsAppNumbers(),
                          'contact_person' => $forwarder->contact_person,
                          'phone' => $forwarder->phone,
                          'buyers' => $forwarder->buyers ?? [],
                          'destination' => $forwarder->destination,
                          'company_type' => $forwarder->company_type,
                          'service_type' => $forwarder->service_type
                      ];
                  })
                  ->toArray();
    }

    /**
     * Relationship with export data
     */
    public function exportData()
    {
        return $this->hasMany(ExportData::class, 'forwarder_code', 'code');
    }

    /**
     * Relationship with users (forwarder portal users)
     */
    public function users()
    {
        return $this->hasMany(User::class, 'forwarder_code', 'code');
    }

    /**
     * Get formatted contact information
     */
    public function getFormattedContactAttribute(): string
    {
        $contact = [];
        
        if ($this->contact_person) {
            $contact[] = $this->contact_person;
        }
        
        if ($this->phone) {
            $contact[] = $this->phone;
        }
        
        $primaryEmail = $this->getPrimaryNotificationEmail();
        if ($primaryEmail) {
            $contact[] = $primaryEmail;
        }
        
        return implode(' | ', $contact);
    }

    /**
     * Get buyer count
     */
    public function getBuyerCountAttribute(): int
    {
        return is_array($this->buyers) ? count($this->buyers) : 0;
    }

    /**
     * Get email count
     */
    public function getEmailCountAttribute(): int
    {
        return count($this->getAllNotificationEmails());
    }

    /**
     * Get WhatsApp count
     */
    public function getWhatsappCountAttribute(): int
    {
        return count($this->getAllWhatsAppNumbers());
    }
}