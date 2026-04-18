{{-- resources/views/emails/booking-request.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Container Booking Request - {{ $booking['instruction_id'] }}</title>
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
            background: linear-gradient(135deg, #28a745, #20c997);
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
            background: linear-gradient(90deg, #20c997, #17a2b8);
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
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            border: 2px solid #17a2b8;
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
        
        .booking-card {
            background: linear-gradient(135deg, #e8f8f5, #f0fdf4);
            border: 2px solid #28a745;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
            position: relative;
        }
        
        .booking-card::before {
            content: '📦';
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
            border-left: 4px solid #28a745;
            transition: transform 0.2s ease;
        }
        
        .info-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .info-label {
            font-weight: 700;
            color: #28a745;
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
        
        .booking-summary {
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border: 2px solid #28a745;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }
        
        .summary-title {
            color: #28a745;
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
            background: linear-gradient(135deg, #e8f8f5, #f0fdf4);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #28a745;
            transition: transform 0.2s ease;
        }
        
        .summary-item:hover {
            transform: scale(1.05);
        }
        
        .summary-number {
            font-size: 28px;
            font-weight: 900;
            color: #28a745;
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
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }
        
        .action-required h3 {
            color: #856404;
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
            border-left: 4px solid #ffc107;
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
            background: #ffc107;
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
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: linear-gradient(135deg, #20c997, #17a2b8);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .container-notice {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 2px solid #28a745;
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        
        .container-notice strong {
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
            color: #28a745;
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
        
        .booking-type-notice {
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
            @if($booking['priority'] === 'urgent')
                <div class="urgent-badge">🚨 URGENT</div>
            @endif
            <h1>📦 Container Booking Request</h1>
            <div class="company">{{ $company['name'] ?? 'PT. KAYU MEBEL INDONESIA' }}</div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Priority Notice -->
            <div class="priority-notice {{ $booking['priority'] }}">
                @if($booking['priority'] === 'urgent')
                    <strong>🚨 URGENT BOOKING REQUEST</strong>
                    <p>Immediate container schedule needed - Response required within 4 hours</p>
                @elseif($booking['priority'] === 'high')
                    <strong>⚡ HIGH PRIORITY BOOKING</strong>
                    <p>Fast container arrangement required - Response needed within 12 hours</p>
                @else
                    <strong>📦 CONTAINER BOOKING REQUEST</strong>
                    <p>Please provide container schedule within normal business hours</p>
                @endif
            </div>

            <!-- Greeting -->
            <div class="greeting">
                <strong>Dear {{ $forwarder->name ?? 'Forwarder Team' }},</strong>
            </div>
            
            <p>We are requesting container booking services for export shipment. Please review the booking details below and provide your container schedule and availability information.</p>

            <!-- Booking Type Notice -->
            <div class="booking-type-notice">
                <strong>📋 BOOKING REQUEST TYPE:</strong>
                This is a container booking request that requires your schedule response via Forwarder Portal.
            </div>

            <!-- Booking Information Card -->
            <div class="booking-card">
                <h3 style="margin-top: 0; color: #28a745;">📦 Booking Request Details</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Booking ID</div>
                        <div class="info-value">{{ $booking['instruction_id'] }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Priority Level</div>
                        <div class="info-value" style="color: {{ $booking['priority'] === 'urgent' ? '#dc3545' : ($booking['priority'] === 'high' ? '#fd7e14' : '#28a745') }}">
                            {{ strtoupper($booking['priority']) }}
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Expected Pickup Date</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($booking['expected_pickup_date'])->format('d M Y') }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Container Type Required</div>
                        <div class="info-value">{{ $booking['container_type'] ?? '40ft HC' }}</div>
                    </div>
                </div>
            </div>

            <!-- Shipment Summary -->
            <div class="booking-summary">
                <div class="summary-title">📊 SHIPMENT SUMMARY</div>
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="summary-number">{{ count($booking['export_data'] ?? []) }}</span>
                        <div class="summary-label">Export Items</div>
                    </div>
                    <div class="summary-item">
                        <span class="summary-number">{{ number_format($booking['total_volume'] ?? 0, 2) }}</span>
                        <div class="summary-label">Total CBM</div>
                    </div>
                    <div class="summary-item">
                        <span class="summary-number">{{ number_format($booking['total_weight'] ?? 0, 0) }}</span>
                        <div class="summary-label">Total KG</div>
                    </div>
                    <div class="summary-item">
                        <span class="summary-number">{{ number_format($booking['total_quantity'] ?? 0, 0) }}</span>
                        <div class="summary-label">Total Pieces</div>
                    </div>
                </div>
            </div>

            <!-- Shipping & Contact Details -->
            <h3 style="color: #28a745; margin-top: 30px;">🚛 Shipping & Contact Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Pickup Location</div>
                    <div class="info-value">{{ $booking['pickup_location'] ?? 'TBD' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Port of Loading</div>
                    <div class="info-value">{{ $booking['port_loading'] ?? 'Tanjung Perak - Surabaya' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Port of Destination</div>
                    <div class="info-value">{{ $booking['port_destination'] ?? 'LOS ANGELES' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Freight Payment</div>
                    <div class="info-value">{{ $booking['freight_payment'] ?? 'COLLECT' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Contact Person</div>
                    <div class="info-value">{{ $booking['contact_person'] ?? 'Export Team' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Requested By</div>
                    <div class="info-value">{{ $booking['sent_by'] ?? 'Export Department' }}<br><small>{{ $booking['sent_by_email'] ?? 'exim_3@pawindo.com' }}</small></div>
                </div>
            </div>

            <!-- Special Instructions -->
            @if(!empty($booking['special_instructions']))
            <div class="special-instructions">
                <h4>📝 SPECIAL INSTRUCTIONS</h4>
                <p>{{ $booking['special_instructions'] }}</p>
            </div>
            @endif

            <!-- Action Required Section -->
            <div class="action-required">
                <h3>🎯 REQUIRED ACTIONS</h3>
                <p><strong>Please complete the following steps:</strong></p>
                <ol class="action-steps">
                    <li><strong>Review</strong> the container booking requirements and shipment details</li>
                    <li><strong>Check</strong> container availability for the specified pickup date and type</li>
                    <li><strong>Prepare</strong> your container schedule and logistics arrangement</li>
                    <li><strong>Access</strong> your Forwarder Portal dashboard for response</li>
                    <li><strong>Provide</strong> container schedule, pickup time, and terminal information</li>
                    <li><strong>Upload</strong> any required container booking confirmation documents</li>
                    <li><strong>Submit</strong> your complete response through the portal system</li>
                </ol>
            </div>

            <!-- Dashboard Access -->
            <div class="dashboard-access">
                <p><strong>Access your forwarder portal to respond to this booking request:</strong></p>
                <a href="{{ config('app.url') }}/forwarder/dashboard" class="btn">
                    📋 Access Forwarder Portal
                </a>
                <p style="margin-top: 15px; font-size: 14px; color: #6c757d;">
                    Login with your forwarder credentials to view booking details and provide schedule response
                </p>
            </div>

            <!-- Container Notice -->
            <div class="container-notice">
                <strong>📦 CONTAINER BOOKING CONFIRMATION NEEDED</strong>
                <p>Please provide container schedule, terminal details, and pickup arrangements via your Forwarder Portal dashboard.</p>
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
            @if($booking['priority'] === 'urgent')
            <div class="priority-notice urgent">
                <strong>⏰ URGENT RESPONSE REQUIRED</strong>
                <p>This booking requires immediate processing. Please respond within 4 hours to avoid delays.</p>
            </div>
            @elseif($booking['priority'] === 'high')
            <div class="priority-notice high">
                <strong>⚡ FAST RESPONSE REQUIRED</strong>
                <p>This booking requires expedited processing. Please respond within 12 hours.</p>
            </div>
            @endif

            <!-- Real-time Dashboard Notice -->
            <div class="container-notice">
                <strong>🔔 REAL-TIME DASHBOARD NOTIFICATION</strong>
                <p>This booking request is now available in your Forwarder Portal dashboard for immediate action. You will receive real-time notifications for any updates.</p>
            </div>

            <!-- Closing -->
            <p style="margin-top: 30px;"><strong>Thank you for your partnership and prompt attention to this container booking request.</strong></p>
            
            <p style="margin-bottom: 0;">Best regards,<br>
            <strong>{{ $company['pic'] ?? 'EKA WIJAYA' }}</strong><br>
            Export Department<br>
            {{ $company['name'] ?? 'PT. KAYU MEBEL INDONESIA' }}</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <strong>Portal EXIM - Enhanced Booking System</strong>
            🌐 SAP-Integrated Export Management with Real-time Container Booking<br>
            📧 {{ $company['email'] ?? 'exim_3@pawindo.com' }} | 🌐 {{ config('app.url') }}<br><br>
            
            <em>This email contains a container booking request that requires your response.</em><br>
            <em>Please access your Forwarder Portal for container schedule submission and real-time updates.</em><br><br>
            
            <small>Generated on {{ now()->format('d M Y H:i') }} (UTC+7) | Booking ID: {{ $booking['instruction_id'] }}</small><br>
            <small>Enhanced Booking System v1.0 | Real-time Dashboard Integration Active</small>
        </div>
    </div>
</body>
</html>