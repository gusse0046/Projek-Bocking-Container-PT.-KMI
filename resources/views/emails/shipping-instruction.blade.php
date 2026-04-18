{{-- resources/views/emails/shipping-instruction.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Shipping Instruction - {{ $instruction['instruction_id'] }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        
        .email-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }
        
        .header {
            background: linear-gradient(135deg, #0f5132, #198754);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #40826d, #52b788);
        }
        
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .header .company {
            margin-top: 12px;
            font-size: 18px;
            opacity: 0.95;
            font-weight: 500;
        }
        
        .urgent-badge {
            position: absolute;
            top: 15px;
            right: 20px;
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .content {
            padding: 40px;
            background: white;
        }
        
        .priority-notice {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
        }
        
        .priority-notice.urgent {
            background: linear-gradient(135deg, #f8d7da, #fab1a0);
            border-color: #dc3545;
        }
        
        .priority-notice.high {
            background: linear-gradient(135deg, #fff3cd, #fdcb6e);
            border-color: #fd7e14;
        }
        
        .priority-notice strong {
            font-size: 20px;
            display: block;
            margin-bottom: 8px;
        }
        
        .greeting {
            font-size: 18px;
            margin-bottom: 25px;
            color: #2d3748;
        }
        
        .instruction-card {
            background: linear-gradient(135deg, #e8f5e8, #f0f8f0);
            border: 2px solid #0f5132;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
            position: relative;
        }
        
        .instruction-card::before {
            content: '📋';
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }
        
        .info-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 18px;
            border-left: 4px solid #0f5132;
            transition: transform 0.2s ease;
        }
        
        .info-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .info-label {
            font-weight: 700;
            color: #0f5132;
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 16px;
            color: #2d3748;
            font-weight: 600;
        }
        
        .shipment-summary {
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border: 2px solid #0f5132;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }
        
        .summary-title {
            color: #0f5132;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }
        
        .summary-item {
            background: linear-gradient(135deg, #e8f5e8, #f0f8f0);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #0f5132;
            transition: transform 0.2s ease;
        }
        
        .summary-item:hover {
            transform: scale(1.05);
        }
        
        .summary-number {
            font-size: 28px;
            font-weight: 900;
            color: #0f5132;
            display: block;
            margin-bottom: 8px;
            line-height: 1;
        }
        
        .summary-label {
            font-size: 13px;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .action-required {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 2px solid #2196f3;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }
        
        .action-required h3 {
            color: #1976d2;
            margin-top: 0;
            font-size: 20px;
            font-weight: 700;
        }
        
        .action-steps {
            list-style: none;
            padding: 0;
            margin: 15px 0;
        }
        
        .action-steps li {
            background: white;
            margin: 10px 0;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #2196f3;
            font-weight: 500;
            position: relative;
            padding-left: 50px;
        }
        
        .action-steps li::before {
            content: counter(step-counter);
            counter-increment: step-counter;
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: #2196f3;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
        }
        
        .action-steps {
            counter-reset: step-counter;
        }
        
        .dashboard-access {
            text-align: center;
            margin: 35px 0;
        }
        
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #0f5132, #198754);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(15, 81, 50, 0.3);
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: linear-gradient(135deg, #198754, #40826d);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(15, 81, 50, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .pdf-notice {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 2px solid #28a745;
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        
        .pdf-notice strong {
            color: #155724;
            font-size: 18px;
            display: block;
            margin-bottom: 10px;
        }
        
        .contact-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        
        .contact-title {
            color: #0f5132;
            font-weight: 700;
            font-size: 20px;
            margin-bottom: 20px;
        }
        
        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .contact-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .footer {
            background: linear-gradient(135deg, #2d3748, #4a5568);
            color: white;
            padding: 25px;
            text-align: center;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .footer strong {
            display: block;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .footer a {
            color: #81c784;
            text-decoration: none;
        }
        
        .footer a:hover {
            color: #a5d6a7;
            text-decoration: underline;
        }
        
        .copy-notice {
            background: linear-gradient(135deg, #e1f5fe, #b3e5fc);
            border: 2px solid #03a9f4;
            border-radius: 12px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
            font-weight: 600;
            color: #0277bd;
        }
        
        .special-instructions {
            background: linear-gradient(135deg, #fff8e1, #ffecb3);
            border: 2px solid #ffa726;
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .special-instructions h4 {
            color: #ef6c00;
            margin-top: 0;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .special-instructions p {
            margin: 0;
            font-weight: 500;
            color: #e65100;
        }

        /* Enhanced PDF notification styles */
        .pdf-attachment-notice {
            background: linear-gradient(135deg, #e8f5e8, #c3e6cb);
            border: 2px solid #0f5132;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            text-align: center;
            position: relative;
        }

        .pdf-icon {
            font-size: 3rem;
            color: #dc3545;
            margin-bottom: 15px;
        }

        .pdf-details {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            border: 1px solid #0f5132;
        }
        
        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 8px;
            }
            
            .content {
                padding: 25px;
            }
            
            .info-grid,
            .summary-grid {
                grid-template-columns: 1fr;
            }
            
            .urgent-badge {
                position: static;
                display: inline-block;
                margin-top: 15px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .summary-number {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            @if($instruction['priority'] === 'urgent')
                <div class="urgent-badge">🚨 URGENT</div>
            @endif
            <h1>🚢 Export Shipping Instruction</h1>
            <div class="company">{{ $company['name'] ?? 'PT. KAYU MEBEL INDONESIA' }}</div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Copy Notice (if this is a copy email) -->
            @if(isset($is_copy) && $is_copy)
            <div class="copy-notice">
                📋 <strong>COPY:</strong> This is a copy of the shipping instruction sent to the forwarder for your records.
            </div>
            @endif

            <!-- Priority Notice -->
            <div class="priority-notice {{ $instruction['priority'] }}">
                @if($instruction['priority'] === 'urgent')
                    <strong>🚨 URGENT SHIPPING INSTRUCTION</strong>
                    <p>Immediate action required - Response needed within 4 hours</p>
                @elseif($instruction['priority'] === 'high')
                    <strong>⚡ HIGH PRIORITY INSTRUCTION</strong>
                    <p>Fast processing required - Response needed within 12 hours</p>
                @else
                    <strong>📋 SHIPPING INSTRUCTION</strong>
                    <p>Please process within normal business hours</p>
                @endif
            </div>

            <!-- Greeting -->
            <div class="greeting">
                <strong>Dear {{ $forwarder->name ?? 'Forwarder Team' }},</strong>
            </div>
            
            <p>We have sent you a new export shipping instruction with PDF attachment that requires your immediate attention and response. Please review the details below and provide container schedule information.</p>

            <!-- Enhanced PDF Attachment Notice -->
            <div class="pdf-attachment-notice">
                <div class="pdf-icon">📄</div>
                <strong style="color: #0f5132; font-size: 20px;">SHIPPING INSTRUCTION PDF ATTACHED</strong>
                <div class="pdf-details">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; text-align: left;">
                        <div>
                            <strong>Document Type:</strong><br>
                            <span style="color: #0f5132;">Complete Shipping Instruction</span>
                        </div>
                        <div>
                            <strong>File Format:</strong><br>
                            <span style="color: #0f5132;">PDF (Ready to Print)</span>
                        </div>
                        <div>
                            <strong>Contains:</strong><br>
                            <span style="color: #0f5132;">All Item Details & Instructions</span>
                        </div>
                        <div>
                            <strong>Action Required:</strong><br>
                            <span style="color: #dc3545;">Download & Review</span>
                        </div>
                    </div>
                </div>
                <p style="margin-top: 15px; color: #155724;">
                    <strong>Please download the attached PDF for complete shipping instruction details.</strong>
                </p>
            </div>

            <!-- Instruction Information Card -->
            <div class="instruction-card">
                <h3 style="margin-top: 0; color: #0f5132;">📋 Instruction Details</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Instruction ID</div>
                        <div class="info-value">{{ $instruction['instruction_id'] }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Priority Level</div>
                        <div class="info-value" style="color: {{ $instruction['priority'] === 'urgent' ? '#dc3545' : ($instruction['priority'] === 'high' ? '#fd7e14' : '#28a745') }}">
                            {{ strtoupper($instruction['priority']) }}
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Expected Pickup Date</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($instruction['expected_pickup_date'])->format('d M Y') }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Container Type Required</div>
                        <div class="info-value">{{ $instruction['container_type'] ?? '40ft HC' }}</div>
                    </div>
                </div>
            </div>

            <!-- Shipment Summary -->
            <div class="shipment-summary">
                <div class="summary-title">📦 SHIPMENT SUMMARY</div>
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="summary-number">{{ count($instruction['export_data'] ?? []) }}</span>
                        <div class="summary-label">Export Items</div>
                    </div>
                    <div class="summary-item">
                        <span class="summary-number">{{ number_format($instruction['total_volume'] ?? 0, 2) }}</span>
                        <div class="summary-label">Total CBM</div>
                    </div>
                    <div class="summary-item">
                        <span class="summary-number">{{ number_format($instruction['total_weight'] ?? 0, 0) }}</span>
                        <div class="summary-label">Total KG</div>
                    </div>
                    <div class="summary-item">
                        <span class="summary-number">{{ number_format($instruction['total_quantity'] ?? 0, 0) }}</span>
                        <div class="summary-label">Total Pieces</div>
                    </div>
                </div>
            </div>

            <!-- Shipping & Contact Details -->
            <h3 style="color: #0f5132; margin-top: 30px;">🚛 Shipping & Contact Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Pickup Location</div>
                    <div class="info-value">{{ $instruction['pickup_location'] ?? 'TBD' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Port of Loading</div>
                    <div class="info-value">{{ $instruction['port_loading'] ?? 'Tanjung Perak - Surabaya' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Port of Destination</div>
                    <div class="info-value">{{ $instruction['port_destination'] ?? 'LOS ANGELES' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Freight Payment</div>
                    <div class="info-value">{{ $instruction['freight_payment'] ?? 'COLLECT' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Contact Person</div>
                    <div class="info-value">{{ $instruction['contact_person'] ?? 'Export Team' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Sent By</div>
                    <div class="info-value">{{ $instruction['sent_by'] ?? 'Export Department' }}<br><small>{{ $instruction['sent_by_email'] ?? 'exim_3@pawindo.com' }}</small></div>
                </div>
            </div>

            <!-- Special Instructions -->
            @if(!empty($instruction['special_instructions']))
            <div class="special-instructions">
                <h4>📝 SPECIAL INSTRUCTIONS</h4>
                <p>{{ $instruction['special_instructions'] }}</p>
            </div>
            @endif

            <!-- Action Required Section -->
            <div class="action-required">
                <h3>🎯 REQUIRED ACTIONS</h3>
                <p><strong>Please complete the following steps:</strong></p>
                <ol class="action-steps">
                    <li><strong>Download</strong> the attached PDF shipping instruction document</li>
                    <li><strong>Review</strong> all shipment details and requirements carefully</li>
                    <li><strong>Check</strong> container availability for the specified pickup date</li>
                    <li><strong>Access</strong> your Forwarder Portal dashboard for response</li>
                    <li><strong>Provide</strong> container schedule and pickup arrangement details</li>
                    <li><strong>Upload</strong> any required supporting documents via portal</li>
                    <li><strong>Submit</strong> your response through the portal system</li>
                </ol>
            </div>

            <!-- Dashboard Access -->
            <div class="dashboard-access">
                <p><strong>Access your forwarder portal to respond:</strong></p>
                <a href="{{ config('app.url') }}/forwarder/dashboard" class="btn">
                    🔐 Access Forwarder Portal
                </a>
                <p style="margin-top: 15px; font-size: 14px; color: #6c757d;">
                    Login with your forwarder credentials to view PDF and respond to this instruction
                </p>
            </div>

            <!-- Contact Information -->
            <div class="contact-section">
                <div class="contact-title">📞 CONTACT INFORMATION</div>
                <div class="contact-info">
                    <div class="contact-item">
                        <strong>{{ $company['name'] ?? 'PT. KAYU MEBEL INDONESIA' }}</strong><br>
                        Export Department
                    </div>
                    <div class="contact-item">
                        📧 <strong>Email:</strong><br>
                        {{ $company['email'] ?? 'exim_3@pawindo.com' }}
                    </div>
                    <div class="contact-item">
                        📱 <strong>Phone:</strong><br>
                        {{ $company['phone'] ?? '+62-31-8971234' }}
                    </div>
                    <div class="contact-item">
                        👤 <strong>Contact Person:</strong><br>
                        {{ $company['pic'] ?? 'EKA WIJAYA' }}
                    </div>
                </div>
            </div>

            <!-- Response Time Notice -->
            @if($instruction['priority'] === 'urgent')
            <div class="priority-notice urgent">
                <strong>⏰ URGENT RESPONSE REQUIRED</strong>
                <p>This shipment requires immediate processing. Please respond within 4 hours to avoid delays.</p>
            </div>
            @elseif($instruction['priority'] === 'high')
            <div class="priority-notice high">
                <strong>⚡ FAST RESPONSE REQUIRED</strong>
                <p>This shipment requires expedited processing. Please respond within 12 hours.</p>
            </div>
            @endif

            <!-- Real-time Dashboard Notice -->
            <div class="pdf-notice">
                <strong>🔔 REAL-TIME DASHBOARD NOTIFICATION</strong>
                <p>This instruction with PDF attachment is now available in your Forwarder Portal dashboard for immediate action. You will receive real-time notifications for any updates.</p>
            </div>

            <!-- Closing -->
            <p style="margin-top: 30px;"><strong>Thank you for your partnership and prompt attention to this shipping instruction.</strong></p>
            
            <p style="margin-bottom: 0;">Best regards,<br>
            <strong>{{ $company['pic'] ?? 'EKA WIJAYA' }}</strong><br>
            Export Department<br>
            {{ $company['name'] ?? 'PT. KAYU MEBEL INDONESIA' }}</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <strong>Portal EXIM - Enhanced PDF System</strong>
            🌐 SAP-Integrated Export Management with Real-time PDF Notifications<br>
            📧 {{ $company['email'] ?? 'exim_3@pawindo.com' }} | 🌐 {{ config('app.url') }}<br><br>
            
            <em>This email contains a PDF attachment with complete shipping instruction details.</em><br>
            <em>Please access your Forwarder Portal for response and real-time updates.</em><br><br>
            
            <small>Generated on {{ now()->format('d M Y H:i') }} (UTC+7) | Instruction ID: {{ $instruction['instruction_id'] }}</small><br>
            <small>Enhanced PDF System v1.0 | Real-time Dashboard Integration Active</small>
        </div>
    </div>
</body>
</html>