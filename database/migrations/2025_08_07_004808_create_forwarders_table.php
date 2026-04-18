<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateForwardersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('forwarders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            
            // Enhanced notification fields
            $table->json('emails')->nullable(); // Array of email addresses
            $table->string('primary_email')->nullable(); // Main email for notifications
            $table->json('whatsapp_numbers')->nullable(); // Array of WhatsApp numbers
            $table->string('primary_whatsapp')->nullable(); // Main WhatsApp for notifications
            
            // Buyer mappings and destination
            $table->json('buyers')->nullable(); // Array of buyer mappings
            $table->string('destination')->nullable();
            
            // Contact information
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            
            // Notification preferences
            $table->boolean('email_notifications_enabled')->default(true);
            $table->boolean('whatsapp_notifications_enabled')->default(false);
            $table->boolean('is_active')->default(true);
            
            // Additional fields for enhanced system
            $table->string('company_type')->nullable(); // e.g., 'freight_forwarder', 'shipping_line'
            $table->string('service_type')->nullable(); // e.g., 'LCL', 'FCL', 'both'
            $table->json('service_routes')->nullable(); // Array of routes they service
            
            $table->timestamps();
            
            // Add indexes for better performance
            $table->index('code');
            $table->index('primary_email');
            $table->index('is_active');
            $table->index('email_notifications_enabled');
            $table->index('whatsapp_notifications_enabled');
        });
        
        // Insert enhanced forwarder data based on Excel mapping
        $this->insertEnhancedForwarderData();
    }
    
    /**
     * Insert enhanced forwarder data with multiple emails and WhatsApp support
     */
    private function insertEnhancedForwarderData()
    {
        $forwardersData = [
            [
                'name' => 'PT. ATLANTIC CONTAINER LINE',
                'code' => 'ACL',
                'buyers' => json_encode([
                    'ETHAN ALLEN OPERATIONS INC', 
                    'ETHAN ALLEN OPERATIONS, INC.', 
                    'ETHAN ALLEN',
                    'ETHAN ALLEN OPERATIONS INC.',
                    'ETHAN ALLEN GLOBAL INC'
                ]),
                'emails' => json_encode([
                    'support.sub@aclindonesia.com',
                    'candra@aclindonesia.com', 
                    'operational.sub@aclindonesia.com',
                    'export.acl@aclindonesia.com'
                ]),
                'primary_email' => 'support.sub@aclindonesia.com',
                'whatsapp_numbers' => json_encode([
                    '+6281234567890', // Candra
                    '+6281234567891', // Support Team
                    '+6281234567892'  // Operations
                ]),
                'primary_whatsapp' => '+6281234567890',
                'destination' => 'USA - East Coast',
                'contact_person' => 'Candra / Support Team ACL',
                'phone' => '+62-21-2922-7800',
                'address' => 'Menara Batavia Lt. 12, Jl. KH. Mas Mansyur Kav. 126, Jakarta 10220',
                'email_notifications_enabled' => true,
                'whatsapp_notifications_enabled' => true,
                'company_type' => 'shipping_line',
                'service_type' => 'FCL',
                'service_routes' => json_encode(['Indonesia-USA East Coast', 'Indonesia-Canada']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'PT. ORIENT STAR SHIPPING',
                'code' => 'OSS',
                'buyers' => json_encode([
                    'VANGUARD FURNITURE', 
                    'VANGUARD FURNITURE LLC',
                    'VANGUARD FURNITURE COMPANY'
                ]),
                'emails' => json_encode([
                    'fitriya.sub@orientstargroup.com',
                    'dina.srg@orientstargroup.com',
                    'leonard.jkt@orientstargroup.com',
                    'berti_srg@orientstargroup.com',
                    'viskaloren.jkt@orientstargroup.com',
                    'operations.oss@orientstargroup.com'
                ]),
                'primary_email' => 'fitriya.sub@orientstargroup.com',
                'whatsapp_numbers' => json_encode([
                    '+6282345678901', // Fitriya
                    '+6282345678902', // Dina
                    '+6282345678903', // Leonard
                    '+6282345678904'  // Operations
                ]),
                'primary_whatsapp' => '+6282345678901',
                'destination' => 'USA - West Coast',
                'contact_person' => 'Fitriya / Dina OSS',
                'phone' => '+62-21-2922-5555',
                'address' => 'Graha Orient Star, Jl. Raya Pluit Selatan, Jakarta 14440',
                'email_notifications_enabled' => true,
                'whatsapp_notifications_enabled' => true,
                'company_type' => 'freight_forwarder',
                'service_type' => 'both',
                'service_routes' => json_encode(['Indonesia-USA West Coast', 'Indonesia-Australia']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'MAXIMOS GLOBAL LOGISTIK',
                'code' => 'MGL',
                'buyers' => json_encode([
                    'INDIAN INDUSTRIES DBA ESCALADE SPORTS',
                    'ESCALADE SPORTS',
                    'INDIAN INDUSTRIES',
                    'ESCALADE SPORTS LLC',
                    'INDIAN INDUSTRIES LLC'
                ]),
                'emails' => json_encode([
                    'maya.febrioletta@maximos.co.id',
                    'apri.permatasari@maximos.co.id',
                    'rika.triwidiati@maximos.co.id',
                    'lilly@escaladesports.cn',
                    'sales2@pawindo.com',
                    'export.mgl@maximos.co.id'
                ]),
                'primary_email' => 'maya.febrioletta@maximos.co.id',
                'whatsapp_numbers' => json_encode([
                    '+6283456789012', // Maya
                    '+6283456789013', // Apri
                    '+6283456789014', // Rika
                    '+6283456789015'  // Export Team
                ]),
                'primary_whatsapp' => '+6283456789012',
                'destination' => 'USA - Midwest',
                'contact_person' => 'Maya Febrioletta / Apri Permatasari',
                'phone' => '+62-21-2922-6666',
                'address' => 'Maximos Building, Jl. Kyai Tapa No. 1, Jakarta 11440',
                'email_notifications_enabled' => true,
                'whatsapp_notifications_enabled' => true,
                'company_type' => 'freight_forwarder',
                'service_type' => 'both',
                'service_routes' => json_encode(['Indonesia-USA Midwest', 'Indonesia-Europe']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'PT RSL LOGISTIC INDONESIA',
                'code' => 'RSL',
                'buyers' => json_encode([
                    'CENTURY FURNITURE', 
                    'CENTURY FURNITURE LLC',
                    'CENTURY FURNITURE COMPANY'
                ]),
                'emails' => json_encode([
                    'rizky.apriyansah.id@rslog.com',
                    'elinsah.simanjuntak.id@rslog.com',
                    'esther.ezera.id@rslog.com',
                    'csop_id@rslog.com',
                    'santoso.wibowo.id@rslog.com',
                    'sales6@pawindo.com',
                    'export.rsl@rslog.com'
                ]),
                'primary_email' => 'rizky.apriyansah.id@rslog.com',
                'whatsapp_numbers' => json_encode([
                    '+6284567890123', // Rizky
                    '+6284567890124', // Elinsah
                    '+6284567890125', // Esther
                    '+6284567890126'  // Customer Service
                ]),
                'primary_whatsapp' => '+6284567890123',
                'destination' => 'USA - Southeast',
                'contact_person' => 'Rizky Apriyansah / Elinsah Simanjuntak',
                'phone' => '+62-21-2922-7777',
                'address' => 'RSL Building, Jl. Pluit Raya No. 1, Jakarta 14440',
                'email_notifications_enabled' => true,
                'whatsapp_notifications_enabled' => true,
                'company_type' => 'freight_forwarder',
                'service_type' => 'FCL',
                'service_routes' => json_encode(['Indonesia-USA Southeast', 'Indonesia-South America']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'CNL LOGISTICS INDONESIA, PT',
                'code' => 'CNL',
                'buyers' => json_encode([
                    'CRATE & BARREL', 
                    'CRATE AND BARREL',
                    'CB2',
                    'CRATE & BARREL KIDS'
                ]),
                'emails' => json_encode([
                    'info@cnllogistics.com',
                    'operations@cnllogistics.com',
                    'export.cnl@cnllogistics.com',
                    'customer.service@cnllogistics.com'
                ]),
                'primary_email' => 'info@cnllogistics.com',
                'whatsapp_numbers' => json_encode([
                    '+6285678901234', // Operations
                    '+6285678901235', // Customer Service
                    '+6285678901236'  // Export Team
                ]),
                'primary_whatsapp' => '+6285678901234',
                'destination' => 'Multi-Destination',
                'contact_person' => 'CNL Operations Team',
                'phone' => '+62-21-2922-8888',
                'address' => 'CNL Building, Jl. Gajah Mada No. 1, Jakarta 10130',
                'email_notifications_enabled' => true,
                'whatsapp_notifications_enabled' => true,
                'company_type' => 'freight_forwarder',
                'service_type' => 'both',
                'service_routes' => json_encode(['Indonesia-USA', 'Indonesia-Europe', 'Indonesia-Asia']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'EVERGREEN SHIPPING AGENCY, PT',
                'code' => 'ESA',
                'buyers' => json_encode([
                    'WEST ELM', 
                    'POTTERY BARN',
                    'POTTERY BARN KIDS',
                    'WILLIAMS SONOMA'
                ]),
                'emails' => json_encode([
                    'info@evergreen-shipping.co.id',
                    'operations@evergreen-shipping.co.id',
                    'export.esa@evergreen-shipping.co.id',
                    'booking@evergreen-shipping.co.id'
                ]),
                'primary_email' => 'info@evergreen-shipping.co.id',
                'whatsapp_numbers' => json_encode([
                    '+6286789012345', // Operations
                    '+6286789012346', // Booking
                    '+6286789012347'  // Export Team
                ]),
                'primary_whatsapp' => '+6286789012345',
                'destination' => 'Multi-Destination',
                'contact_person' => 'Evergreen Operations Team',
                'phone' => '+62-21-2922-9999',
                'address' => 'Evergreen Building, Jl. Hayam Wuruk No. 1, Jakarta 11180',
                'email_notifications_enabled' => true,
                'whatsapp_notifications_enabled' => true,
                'company_type' => 'shipping_line',
                'service_type' => 'FCL',
                'service_routes' => json_encode(['Indonesia-USA', 'Indonesia-Europe', 'Indonesia-Middle East']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'EXPEDITORS INDONESIA, PT',
                'code' => 'EXP',
                'buyers' => json_encode([
                    'LULU AND GEORGIA', 
                    'LULU & GEORGIA',
                    'ARHAUS',
                    'ANTHROPOLOGIE'
                ]),
                'emails' => json_encode([
                    'info@expeditors.co.id',
                    'operations@expeditors.co.id',
                    'export.exp@expeditors.co.id',
                    'customer.service@expeditors.co.id'
                ]),
                'primary_email' => 'info@expeditors.co.id',
                'whatsapp_numbers' => json_encode([
                    '+6287890123456', // Operations
                    '+6287890123457', // Customer Service
                    '+6287890123458'  // Export Team
                ]),
                'primary_whatsapp' => '+6287890123456',
                'destination' => 'Multi-Destination',
                'contact_person' => 'Expeditors Operations Team',
                'phone' => '+62-21-2922-1010',
                'address' => 'Expeditors Building, Jl. Sudirman No. 1, Jakarta 12190',
                'email_notifications_enabled' => true,
                'whatsapp_notifications_enabled' => true,
                'company_type' => 'freight_forwarder',
                'service_type' => 'both',
                'service_routes' => json_encode(['Indonesia-USA', 'Indonesia-Canada', 'Indonesia-Australia']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];
        
        // Insert all forwarder data
        foreach ($forwardersData as $forwarder) {
            DB::table('forwarders')->insert($forwarder);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('forwarders');
    }
}