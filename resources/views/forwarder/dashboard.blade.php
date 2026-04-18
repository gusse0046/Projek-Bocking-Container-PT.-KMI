<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Enhanced Forwarder Dashboard - Response System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0f5132;
            --primary-light: #1a5a3e;
            --secondary-color: #198754;
            --accent-color: #40826d;
            --success-color: #2d8659;
            --warning-color: #f8a900;
            --danger-color: #dc3545;
            --info-color: #0ea5e9;
            --card-bg: rgba(255, 255, 255, 0.98);
            --shadow-medium: 0 8px 30px rgba(15, 81, 50, 0.12);
            --gradient-primary: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        body {
            background: linear-gradient(135deg, #0f5132 0%, #1a5a3e 50%, #0a3d26 100%);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            color: #2d3748;
        }
        
        .dashboard-header {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-bottom: 3px solid var(--accent-color);
            padding: 1.5rem 0;
            box-shadow: var(--shadow-medium);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .page-title {
            color: var(--primary-color);
            font-weight: 800;
            margin: 0;
            font-size: 2.2rem;
        }
        
        .forwarder-info {
            background: var(--gradient-primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: var(--shadow-medium);
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: var(--shadow-medium);
            background: var(--card-bg);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(15, 81, 50, 0.15);
        }
        
        .card-header {
            background: var(--gradient-primary);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 1.75rem 2rem;
            font-weight: 700;
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2.5rem 2rem;
            text-align: center;
            transition: all 0.4s ease;
            cursor: pointer;
            height: 100%;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-medium);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover::before {
            transform: scaleX(1);
        }
        
        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 60px rgba(15, 81, 50, 0.2);
        }
        
        .stat-number {
            font-size: 3.5rem;
            font-weight: 900;
            color: var(--primary-color);
            margin-bottom: 0.75rem;
            line-height: 1;
        }
        
        .stat-label {
            color: #495057;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-subtitle {
            color: var(--accent-color);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* ENHANCED INSTRUCTION STYLING */
        .instruction-card {
            border: none;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 4px 15px rgba(15, 81, 50, 0.08);
            border-left: 5px solid transparent;
        }
        
        .instruction-card.export-type {
            border-left-color: #007bff;
            background: linear-gradient(135deg, rgba(0, 123, 255, 0.02), rgba(255, 255, 255, 0.98));
        }
        
        .instruction-card.import-type {
            border-left-color: #28a745;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.02), rgba(255, 255, 255, 0.98));
        }
        
        .instruction-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(15, 81, 50, 0.15);
            border-left-width: 6px;
        }
        
        .instruction-header {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 1.25rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .instruction-body {
            padding: 1.25rem;
        }
        
        .instruction-id {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary-color);
        }
        
        .instruction-meta {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        .type-badge {
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .type-export {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }
        
        .type-import {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
        }
        
        .priority-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .priority-urgent {
            background: var(--danger-color);
            color: white;
        }
        
        .priority-high {
            background: var(--warning-color);
            color: white;
        }
        
        .priority-normal {
            background: var(--info-color);
            color: white;
        }
        
        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-sent {
            background: linear-gradient(135deg, var(--info-color), #0284c7);
            color: white;
        }
        
        .status-received {
            background: linear-gradient(135deg, var(--warning-color), #fd7e14);
            color: white;
        }
        
        .status-pending {
            background: linear-gradient(135deg, var(--warning-color), #fd7e14);
            color: white;
        }
        
        /* ENHANCED RESPONSE FORMS */
        .response-section {
            background: rgba(15, 81, 50, 0.03);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 1rem;
            border: 1px solid rgba(15, 81, 50, 0.1);
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #dee2e6;
            padding: 0.75rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(64, 130, 109, 0.25);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .btn {
            border-radius: 10px;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.85rem;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(15, 81, 50, 0.3);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #52b788);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #fd7e14);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #c13855);
            color: white;
        }
        
        .btn-info {
            background: linear-gradient(135deg, var(--info-color), #0284c7);
            color: white;
        }
        
        /* MODAL ENHANCEMENTS */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: var(--shadow-medium);
        }
        
        .modal-header {
            background: var(--gradient-primary);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
            border: none;
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .modal-footer {
            padding: 1.5rem 2rem;
            border: none;
            background: rgba(248, 249, 250, 0.5);
        }
        
        /* NOTIFICATION BANNER */
        .notification-banner {
            background: var(--gradient-primary);
            color: white;
            padding: 1.25rem 2rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* LOADING SPINNER */
        .loading-spinner {
            width: 3rem;
            height: 3rem;
            border: 4px solid rgba(15, 81, 50, 0.1);
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
            color: var(--accent-color);
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .page-title {
                font-size: 1.8rem;
            }
            
            .stat-card {
                padding: 2rem 1.5rem;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
            
            .instruction-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        
        }

        /* Mini stats untuk cargo summary */
.stat-mini {
    padding: 0.5rem;
}

.stat-mini-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
}

.stat-mini-label {
    font-size: 0.8rem;
    color: #6c757d;
    text-transform: uppercase;
}

/* Read indicator */
.instruction-card.read {
    opacity: 0.8;
}

.instruction-card.read .instruction-header {
    background: rgba(108, 117, 125, 0.1);
}

/* PDF viewer container */
#pdfViewerContainer {
    min-height: 300px;
    background: #f8f9fa;
}
    </style>
</head>
<body>
    <!-- Header -->
    <div class="dashboard-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h1 class="page-title">
                        
                        Forwarder Portal
                        <span class="forwarder-info ms-3" id="forwarderInfo">
                            <i class="fas fa-building me-2"></i>
                            Loading...
                        </span>
                    </h1>
                    
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-info btn-sm" onclick="refreshInstructions()" title="Refresh Instructions">
                        <i class="fas fa-sync" id="refreshIcon"></i>
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-2"></i>{{ auth()->user()->name ?? 'User' }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user-cog me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="#" onclick="confirmLogout(event)">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden logout form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

    <!-- Main Content -->
    <div class="container-fluid py-4">
        <!-- New Instruction Notification -->
        <div id="notificationBanner" class="notification-banner" style="display: none;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <i class="fas fa-bell me-2"></i>
                    <strong>New Instruction Received!</strong>
                    <span id="notificationMessage"></span>
                </div>
                <button class="btn btn-sm btn-light" onclick="dismissNotification()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Enhanced Dashboard Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number" id="newInstructions">0</div>
                    <div class="stat-label">New Instructions</div>
                    <div class="stat-subtitle">
                        <i class="fas fa-inbox me-1"></i>
                        Need Response
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number" id="pendingResponse">0</div>
                    <div class="stat-label">Pending Response</div>
                    <div class="stat-subtitle">
                        <i class="fas fa-clock me-1"></i>
                        Awaiting Action
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number" id="awaitingApproval">0</div>
                    <div class="stat-label">Awaiting Approval</div>
                    <div class="stat-subtitle">
                        <i class="fas fa-hourglass-half me-1"></i>
                        Under Review
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number" id="completedBookings">0</div>
                    <div class="stat-label">Completed</div>
                    <div class="stat-subtitle">
                        <i class="fas fa-check-double me-1"></i>
                        This Month
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Instructions List -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-tasks me-3"></i>
                            <div>
                                
                                <span class="badge bg-danger" id="newInstructionsBadge">0 New</span>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-info btn-sm" onclick="refreshInstructions()">
                                <i class="fas fa-sync me-1"></i>Refresh
                            </button>
                            <button class="btn btn-success btn-sm" onclick="showCompletedInstructions()">
                                <i class="fas fa-history me-1"></i>Completed
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        
                        
                        <!-- Instructions Container -->
                        <div id="instructionsContainer">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h5>No New Instructions</h5>
                                <p>Waiting for instructions from Export/Import Portals...</p>
                                <small class="text-muted">Instructions will appear here automatically when received</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Export Response Modal -->
    <div class="modal fade" id="exportResponseModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-ship me-2"></i>Container Schedule Response
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-primary">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Export Workflow:</strong> Provide container schedule and send response back to Export Portal for approval.
                    </div>
                    
                    <form id="exportResponseForm" enctype="multipart/form-data">
                        <div id="exportInstructionSummary" class="mb-4">
                            <!-- Export instruction summary will be loaded here -->
                        </div>
                        
                        <div class="response-section">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-shipping-fast me-2"></i>Container Schedule Details
                            </h6>
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Container Available Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="container_available_date" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Container Type <span class="text-danger">*</span></label>
                                    <select class="form-select" name="container_type" required>
                                        <option value="">Select Container</option>
                                        <option value="20ft_standard">20ft Standard</option>
                                        <option value="40ft_standard">40ft Standard</option>
                                        <option value="40ft_hc">40ft High Cube</option>
                                        <option value="45ft_hc">45ft High Cube</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Pickup Time Window <span class="text-danger">*</span></label>
                                    <select class="form-select" name="pickup_time_window" required>
                                        <option value="">Select Time</option>
                                        <option value="08:00-10:00">08:00 - 10:00 AM</option>
                                        <option value="10:00-12:00">10:00 AM - 12:00 PM</option>
                                        <option value="13:00-15:00">01:00 - 03:00 PM</option>
                                        <option value="15:00-17:00">03:00 - 05:00 PM</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label">Container Location/Terminal <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="container_location" 
                                           placeholder="e.g., Terminal A, Bay 5" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Forwarder Contact Person <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="forwarder_contact" 
                                           placeholder="Name & Phone" required>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label">Upload Schedule Document</label>
                                    <input type="file" class="form-control" name="schedule_document" 
                                           accept=".pdf,.doc,.docx,.xlsx,.xls">
                                    <small class="text-muted">Container booking confirmation, schedule, etc.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Special Notes for Export Team</label>
                                    <textarea class="form-control" name="special_notes" rows="3" 
                                              placeholder="Any special requirements or important information..."></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="sendExportResponse()">
                        <i class="fas fa-paper-plane me-2"></i>Send Response to Export Portal
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Import Response Modal -->
    <div class="modal fade" id="importResponseModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-truck me-2"></i>Import Delivery Response
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Import Workflow:</strong> Provide customs clearance and delivery information to Import Portal.
                    </div>
                    
                    <form id="importResponseForm" enctype="multipart/form-data">
                        <div id="importInstructionSummary" class="mb-4">
                            <!-- Import instruction summary will be loaded here -->
                        </div>
                        
                        <div class="response-section">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-customs me-2"></i>Customs & Delivery Information
                            </h6>
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Clearance Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="clearance_date" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Customs Status <span class="text-danger">*</span></label>
                                    <select class="form-select" name="customs_status" required>
                                        <option value="">Select Status</option>
                                        <option value="Cleared">Cleared</option>
                                        <option value="In Process">In Process</option>
                                        <option value="Documentation Required">Documentation Required</option>
                                        <option value="Hold">Hold</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Delivery Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="delivery_date" required>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-4">
                                    <label class="form-label">Tracking Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="tracking_number" 
                                           placeholder="e.g., TRK-ACL-001" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Delivery Method <span class="text-danger">*</span></label>
                                    <select class="form-select" name="delivery_method" required>
                                        <option value="">Select Method</option>
                                        <option value="Standard Truck">Standard Truck</option>
                                        <option value="Container Truck">Container Truck</option>
                                        <option value="Express Delivery">Express Delivery</option>
                                        <option value="Special Handling">Special Handling</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Forwarder Contact <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="forwarder_contact" 
                                           placeholder="Name & Phone" required>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label">Delivery Address</label>
                                    <textarea class="form-control" name="delivery_address" rows="2" 
                                              placeholder="Complete delivery address..."></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Notes for Import Team</label>
                                    <textarea class="form-control" name="notes" rows="2" 
                                              placeholder="Any special notes or delivery instructions..."></textarea>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-12">
                                    <label class="form-label">Upload Delivery Documents</label>
                                    <input type="file" class="form-control" name="delivery_documents[]" 
                                           accept=".pdf,.doc,.docx,.xlsx,.xls" multiple>
                                    <small class="text-muted">Customs clearance docs, delivery receipts, etc.</small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="sendImportResponse()">
                        <i class="fas fa-paper-plane me-2"></i>Send Response to Import Portal
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Processing Modal -->
    <div class="modal fade" id="processingModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-5">
                    <div class="loading-spinner mb-4 mx-auto"></div>
                    <h5 id="processingMessage">Processing...</h5>
                    <div class="progress mt-3">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" id="progressBar" style="width: 0%"></div>
                    </div>
                    <p class="text-muted mt-2" id="processingSubMessage">Please wait...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-5">
                    <div class="text-success mb-4">
                        <i class="fas fa-check-circle" style="font-size: 5rem;"></i>
                    </div>
                    <h3 class="text-success mb-3">Response Sent Successfully!</h3>
                    <p class="text-muted" id="successMessage">Your response has been sent back to the Portal.</p>
                    <div class="mt-4">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                            <i class="fas fa-check me-2"></i>Continue
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Details Modal -->
<div class="modal fade" id="reviewDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-contract me-2"></i>Shipping Instruction Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="reviewDetailsContent">
                    <!-- Content akan di-load via JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="downloadPdfBtn" style="display: none;">
                    <i class="fas fa-download me-1"></i>Download PDF
                </button>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        'use strict';

        // Global variables
        let incomingInstructions = [];
        let currentInstruction = null;
        let eventSource = null;
        let forwarderInfo = null;

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🌲 Enhanced Forwarder Dashboard initializing...');
            
            loadForwarderInfo();
            setupRealTimeListener();
            loadPendingInstructions();
            
            // Set minimum dates
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="container_available_date"]').min = today;
            document.querySelector('input[name="clearance_date"]').min = today;
            document.querySelector('input[name="delivery_date"]').min = today;
            
            console.log('✅ Enhanced Forwarder Dashboard loaded successfully');
        });

        // Load forwarder information
        async function loadForwarderInfo() {
            try {
                const response = await fetch('/forwarder/info', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const result = await response.json();
                    forwarderInfo = result.forwarder;
                    updateForwarderInfo(result.forwarder);
                }
            } catch (error) {
                console.error('Error loading forwarder info:', error);
                document.getElementById('forwarderInfo').innerHTML = `
                    <i class="fas fa-exclamation-triangle me-2"></i>Error Loading Info
                `;
            }
        }

        function updateForwarderInfo(info) {
            document.getElementById('forwarderInfo').innerHTML = `
                <i class="fas fa-building me-2"></i>
                ${info.name} (${info.code})
            `;
            document.title = `${info.name} - Enhanced Forwarder Portal`;
        }

        // Load pending instructions
        // Load pending instructions
async function loadPendingInstructions() {
    try {
        console.log('Loading pending instructions...');
        
        const response = await fetch('/forwarder/pending-instructions', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        console.log('Response status:', response.status);
        
        if (response.ok) {
            const result = await response.json();
            console.log('API Response:', result);
            
            // PERBAIKAN: Ambil instructions dari response yang benar
            if (result.success && Array.isArray(result.instructions)) {
                incomingInstructions = result.instructions;
                console.log('Instructions loaded:', incomingInstructions.length);
            } else if (Array.isArray(result)) {
                // Fallback jika response langsung array
                incomingInstructions = result;
                console.log('Instructions loaded (fallback):', incomingInstructions.length);
            } else {
                console.warn('Unexpected response format:', result);
                incomingInstructions = [];
            }
            
            renderInstructions();
            updateStatistics();
        } else {
            console.error('API Error:', response.status, response.statusText);
            incomingInstructions = [];
            renderInstructions();
        }
    } catch (error) {
        console.error('Error loading pending instructions:', error);
        incomingInstructions = [];
        renderInstructions();
    }
}

        // Render instructions
        function renderInstructions() {
            const container = document.getElementById('instructionsContainer');
            
            if (incomingInstructions.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h5>No New Instructions</h5>
                        <p>Waiting for instructions from Export/Import Portals...</p>
                        <small class="text-muted">Instructions will appear here automatically when received</small>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = incomingInstructions.map(instruction => createInstructionCard(instruction)).join('');
        }

        // Create instruction card
        function createInstructionCard(instruction) {
            const typeClass = instruction.type === 'import' ? 'import-type' : 'export-type';
            const typeBadge = instruction.type === 'import' ? 'type-import' : 'type-export';
            const typeText = instruction.type === 'import' ? 'IMPORT' : 'EXPORT';
            const typeIcon = instruction.type === 'import' ? 'fas fa-truck' : 'fas fa-ship';
            
            return `
                <div class="instruction-card ${typeClass}">
                    <div class="instruction-header">
                        <div class="d-flex align-items-center flex-grow-1">
                            <div class="me-3">
                                <i class="${typeIcon}" style="font-size: 1.5rem; color: var(--accent-color);"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="instruction-id">${instruction.instruction_id}</div>
                                <div class="instruction-meta">
                                    Received: ${formatDate(instruction.created_at)} | 
                                    From: ${instruction.source_portal || instruction.type} Portal
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="type-badge ${typeBadge}">${typeText}</span>
                            <span class="priority-badge priority-${instruction.priority || 'normal'}">${(instruction.priority || 'normal').toUpperCase()}</span>
                            <span class="status-badge status-${instruction.status || 'sent'}">New</span>
                        </div>
                    </div>
                    
                    <div class="instruction-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                ${instruction.type === 'export' ? renderExportDetails(instruction) : renderImportDetails(instruction)}
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex flex-column gap-2 h-100 justify-content-center">
                                    <button class="btn btn-primary btn-sm" onclick="reviewInstruction('${instruction.instruction_id}')">
                                        <i class="fas fa-eye me-1"></i>Review Details
                                    </button>
                                    <button class="btn btn-success btn-sm" onclick="respondToInstruction('${instruction.instruction_id}', '${instruction.type}')">
                                        <i class="fas fa-reply me-1"></i>Provide Response
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderExportDetails(instruction) {
            return `
                <div class="row g-2">
                    <div class="col-6">
                        <strong>Pickup Location:</strong><br>
                        <span class="text-muted">${instruction.pickup_location || 'TBD'}</span>
                    </div>
                    <div class="col-6">
                        <strong>Expected Pickup:</strong><br>
                        <span class="text-muted">${instruction.expected_pickup_date || 'TBD'}</span>
                    </div>
                    <div class="col-6">
                        <strong>Container Type:</strong><br>
                        <span class="text-muted">${instruction.container_type || 'TBD'}</span>
                    </div>
                    <div class="col-6">
                        <strong>Total Volume:</strong><br>
                        <span class="text-muted">${instruction.total_volume || 0} CBM</span>
                    </div>
                </div>
            `;
        }

        function renderImportDetails(instruction) {
            return `
                <div class="row g-2">
                    <div class="col-6">
                        <strong>Delivery Address:</strong><br>
                        <span class="text-muted">${instruction.delivery_address || 'TBD'}</span>
                    </div>
                    <div class="col-6">
                        <strong>Expected Delivery:</strong><br>
                        <span class="text-muted">${instruction.expected_delivery_date || 'TBD'}</span>
                    </div>
                    <div class="col-6">
                        <strong>Total Value:</strong><br>
                        <span class="text-muted">${instruction.total_value || 0}</span>
                    </div>
                    <div class="col-6">
                        <strong>Customs Clearance:</strong><br>
                        <span class="text-muted">${instruction.customs_clearance || 'TBD'}</span>
                    </div>
                </div>
            `;
        }

        // TAMBAHKAN FUNGSI-FUNGSI INI (letakkan sebelum reviewInstruction)
function showLoading(show = true) {
    const loadingModal = document.getElementById('processingModal');
    if (show) {
        if (loadingModal) {
            const modal = new bootstrap.Modal(loadingModal);
            modal.show();
        }
    } else {
        const modalInstance = bootstrap.Modal.getInstance(loadingModal);
        if (modalInstance) {
            modalInstance.hide();
        }
    }
}

function buildInstructionDetailsHTML(instruction) {
    return `
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Instruction Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr><td><strong>Instruction ID:</strong></td><td>${instruction.instruction_id}</td></tr>
                            <tr><td><strong>Reference Invoice:</strong></td><td>${instruction.ref_invoice || 'N/A'}</td></tr>
                            <tr><td><strong>Type:</strong></td><td><span class="badge bg-info">${instruction.type?.toUpperCase() || 'EXPORT'}</span></td></tr>
                            <tr><td><strong>Priority:</strong></td><td><span class="badge bg-warning">${(instruction.priority || 'normal').toUpperCase()}</span></td></tr>
                            <tr><td><strong>Status:</strong></td><td><span class="badge bg-success">${instruction.status || 'NEW'}</span></td></tr>
                            <tr><td><strong>Received:</strong></td><td>${formatDateTime(instruction.received_at || instruction.created_at)}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-ship me-2"></i>Shipping Details</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr><td><strong>Pickup Location:</strong></td><td>${instruction.pickup_location || 'TBD'}</td></tr>
                            <tr><td><strong>Expected Pickup:</strong></td><td>${instruction.expected_pickup_date || 'TBD'}</td></tr>
                            <tr><td><strong>Container Type:</strong></td><td>${instruction.container_type || 'TBD'}</td></tr>
                            <tr><td><strong>Total Volume:</strong></td><td>${instruction.total_volume || 0} CBM</td></tr>
                            <tr><td><strong>Contact Person:</strong></td><td>${instruction.contact_person || 'TBD'}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return dateString;
    }
}

// TAMBAHAN FUNGSI - tidak mengubah yang sudah ada
function showLoading(show = true) {
    console.log(show ? 'Loading...' : 'Loading complete');
}

function setupPDFViewer(instruction) {
    const pdfViewer = document.getElementById('instructionPdfViewer');
    if (pdfViewer && instruction.pdf_available) {
        const viewUrl = `/forwarder/instruction/${instruction.instruction_id}/pdf/view`;
        pdfViewer.src = viewUrl;
        pdfViewer.style.display = 'block';
        
        pdfViewer.onload = function() {
            const loadingMsg = document.getElementById('pdfLoadingMessage');
            if (loadingMsg) loadingMsg.style.display = 'none';
        };
    }
}

function buildInstructionDetailsHTML(instruction) {
    return `
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Instruction Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr><td><strong>ID:</strong></td><td>${instruction.instruction_id}</td></tr>
                            <tr><td><strong>Invoice:</strong></td><td>${instruction.ref_invoice || 'N/A'}</td></tr>
                            <tr><td><strong>Type:</strong></td><td><span class="badge bg-info">${(instruction.type || 'export').toUpperCase()}</span></td></tr>
                            <tr><td><strong>Status:</strong></td><td><span class="badge bg-success">${instruction.status || 'NEW'}</span></td></tr>
                            <tr><td><strong>Pickup:</strong></td><td>${instruction.pickup_location || 'TBD'}</td></tr>
                            <tr><td><strong>Container:</strong></td><td>${instruction.container_type || 'TBD'}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-file-pdf me-2"></i>PDF Document</h6>
                    </div>
                    <div class="card-body p-0">
                        ${instruction.pdf_available ? 
                            `<iframe id="instructionPdfViewer" width="100%" height="400px" style="border: none;"></iframe>
                             <div id="pdfLoadingMessage" class="text-center p-3">Loading PDF...</div>` : 
                            `<div class="text-center p-4">
                                <i class="fas fa-file-times fa-3x text-muted mb-3"></i>
                                <p>No PDF Available</p>
                             </div>`
                        }
                    </div>
                </div>
            </div>
        </div>
    `;
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    try {
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
        });
    } catch (error) {
        return dateString;
    }
}

function reviewInstruction(instructionId) {
    const instruction = incomingInstructions.find(i => i.instruction_id === instructionId);
    if (!instruction) {
        showAlert('Instruction not found', 'danger');
        return;
    }
    
    try {
        // Build content
        const detailsHtml = buildInstructionDetailsHTML(instruction);
        document.getElementById('reviewDetailsContent').innerHTML = detailsHtml;
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('reviewDetailsModal'));
        modal.show();
        
        // Setup PDF setelah modal terbuka
        setTimeout(() => setupPDFViewer(instruction), 300);
        
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error loading details: ' + error.message, 'danger');
    }
}

        // Respond to instruction
        function respondToInstruction(instructionId, type) {
            const instruction = incomingInstructions.find(i => i.instruction_id === instructionId);
            if (!instruction) {
                showAlert('Instruction not found', 'danger');
                return;
            }
            
            currentInstruction = instruction;
            
            if (type === 'export') {
                showExportResponseModal();
            } else {
                showImportResponseModal();
            }
        }

        // Show export response modal
        function showExportResponseModal() {
            if (!currentInstruction) return;
            
            document.getElementById('exportInstructionSummary').innerHTML = createInstructionSummary(currentInstruction);
            const modal = new bootstrap.Modal(document.getElementById('exportResponseModal'));
            modal.show();
        }

        // Show import response modal
        function showImportResponseModal() {
            if (!currentInstruction) return;
            
            document.getElementById('importInstructionSummary').innerHTML = createInstructionSummary(currentInstruction);
            const modal = new bootstrap.Modal(document.getElementById('importResponseModal'));
            modal.show();
        }

        // Create instruction summary
        function createInstructionSummary(instruction) {
            return `
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            ${instruction.type.toUpperCase()} Instruction Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Instruction ID:</strong><br>
                                ${instruction.instruction_id}
                            </div>
                            <div class="col-md-3">
                                <strong>Priority:</strong><br>
                                <span class="priority-badge priority-${instruction.priority || 'normal'}">${(instruction.priority || 'normal').toUpperCase()}</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Contact:</strong><br>
                                ${instruction.contact_person || 'TBD'}
                            </div>
                            <div class="col-md-3">
                                <strong>Source Portal:</strong><br>
                                ${instruction.source_portal || instruction.type} Portal
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Send export response
        async function sendExportResponse() {
            if (!currentInstruction) {
                showAlert('No instruction selected', 'danger');
                return;
            }
            
            const form = document.getElementById('exportResponseForm');
            const formData = new FormData(form);
            
            // Validation
            const requiredFields = ['container_available_date', 'container_type', 'pickup_time_window', 'container_location', 'forwarder_contact'];
            for (const field of requiredFields) {
                if (!formData.get(field)) {
                    showAlert(`Please fill in the ${field.replace('_', ' ')} field`, 'warning');
                    return;
                }
            }
            
            // Add instruction ID
            formData.append('instruction_id', currentInstruction.instruction_id);
            
            // Close modal and show processing
            bootstrap.Modal.getInstance(document.getElementById('exportResponseModal')).hide();
            showProcessingModal('Sending container schedule response...', 'Preparing response for Export Portal');
            
            try {
                const response = await fetch('/forwarder/send-export-response', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const result = await response.json();
                
                hideProcessingModal();
                
                if (result.success) {
                    showSuccessModal(`✅ Container schedule sent successfully to Export Portal!`);
                    refreshInstructions();
                    form.reset();
                    currentInstruction = null;
                } else {
                    showAlert('Failed to send response: ' + result.error, 'danger');
                }
                
            } catch (error) {
                hideProcessingModal();
                showAlert('Error sending response: ' + error.message, 'danger');
            }
        }

        // Send import response
        async function sendImportResponse() {
            if (!currentInstruction) {
                showAlert('No instruction selected', 'danger');
                return;
            }
            
            const form = document.getElementById('importResponseForm');
            const formData = new FormData(form);
            
            // Validation
            const requiredFields = ['clearance_date', 'customs_status', 'delivery_date', 'tracking_number', 'delivery_method', 'forwarder_contact'];
            for (const field of requiredFields) {
                if (!formData.get(field)) {
                    showAlert(`Please fill in the ${field.replace('_', ' ')} field`, 'warning');
                    return;
                }
            }
            
            // Add instruction ID
            formData.append('instruction_id', currentInstruction.instruction_id);
            
            // Close modal and show processing
            bootstrap.Modal.getInstance(document.getElementById('importResponseModal')).hide();
            showProcessingModal('Sending delivery information...', 'Preparing response for Import Portal');
            
            try {
                const response = await fetch('/forwarder/send-import-response', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const result = await response.json();
                
                hideProcessingModal();
                
                if (result.success) {
                    showSuccessModal(`✅ Delivery information sent successfully to Import Portal!`);
                    refreshInstructions();
                    form.reset();
                    currentInstruction = null;
                } else {
                    showAlert('Failed to send response: ' + result.error, 'danger');
                }
                
            } catch (error) {
                hideProcessingModal();
                showAlert('Error sending response: ' + error.message, 'danger');
            }
        }

        // Update statistics
        function updateStatistics() {
            const newCount = incomingInstructions.filter(i => i.status === 'sent' || i.status === 'new').length;
            const pendingCount = incomingInstructions.filter(i => i.status === 'pending_response').length;
            
            document.getElementById('newInstructions').textContent = newCount;
            document.getElementById('pendingResponse').textContent = pendingCount;
            document.getElementById('newInstructionsBadge').textContent = `${newCount} New`;
        }

        // Refresh instructions
        async function refreshInstructions() {
            const refreshIcon = document.getElementById('refreshIcon');
            if (refreshIcon) {
                refreshIcon.classList.add('fa-spin');
            }
            
            await loadPendingInstructions();
            
            if (refreshIcon) {
                refreshIcon.classList.remove('fa-spin');
            }
            
            showAlert('Instructions refreshed successfully', 'success');
        }

        // Setup real-time listener
        function setupRealTimeListener() {
            // Implementation for real-time updates (similar to original)
        }

        // Utility functions
        function showProcessingModal(message, subMessage) {
            document.getElementById('processingMessage').textContent = message;
            document.getElementById('processingSubMessage').textContent = subMessage;
            document.getElementById('progressBar').style.width = '50%';
            
            const modal = new bootstrap.Modal(document.getElementById('processingModal'));
            modal.show();
        }

        function hideProcessingModal() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('processingModal'));
            if (modal) {
                modal.hide();
            }
        }

        function showSuccessModal(message) {
            document.getElementById('successMessage').textContent = message;
            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            modal.show();
        }

        function showAlert(message, type = 'info') {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 350px; max-width: 500px;';
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alert);
            
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function confirmLogout(event) {
            event.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                document.getElementById('logout-form').submit();
            }
        }

        function dismissNotification() {
            document.getElementById('notificationBanner').style.display = 'none';
        }

        function showCompletedInstructions() {
            showAlert('Completed instructions view - to be implemented', 'info');
        }

        // Build HTML untuk instruction details
function buildInstructionDetailsHTML(instruction) {
    return `
        <div class="row g-4">
            <!-- Left Column - Instruction Info -->
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Instruction Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr><td><strong>Instruction ID:</strong></td><td>${instruction.instruction_id}</td></tr>
                            <tr><td><strong>Reference Invoice:</strong></td><td>${instruction.ref_invoice || 'N/A'}</td></tr>
                            <tr><td><strong>Type:</strong></td><td><span class="badge bg-info">${instruction.type?.toUpperCase() || 'EXPORT'}</span></td></tr>
                            <tr><td><strong>Priority:</strong></td><td><span class="badge bg-${getPriorityColor(instruction.priority)}">${(instruction.priority || 'normal').toUpperCase()}</span></td></tr>
                            <tr><td><strong>Status:</strong></td><td><span class="badge bg-success">${instruction.status || 'NEW'}</span></td></tr>
                            <tr><td><strong>Received:</strong></td><td>${formatDateTime(instruction.received_at)}</td></tr>
                        </table>
                    </div>
                </div>
                
                <!-- Shipping Details -->
                <div class="card border-success mt-3">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-ship me-2"></i>Shipping Details</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr><td><strong>Pickup Location:</strong></td><td>${instruction.pickup_location || 'TBD'}</td></tr>
                            <tr><td><strong>Expected Pickup:</strong></td><td>${instruction.expected_pickup_date || 'TBD'}</td></tr>
                            <tr><td><strong>Container Type:</strong></td><td>${instruction.container_type || 'TBD'}</td></tr>
                            <tr><td><strong>Port Loading:</strong></td><td>${instruction.port_loading || 'TBD'}</td></tr>
                            <tr><td><strong>Port Destination:</strong></td><td>${instruction.port_destination || 'TBD'}</td></tr>
                            <tr><td><strong>Contact Person:</strong></td><td>${instruction.contact_person || 'TBD'}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - PDF Viewer -->
            <div class="col-md-6">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-file-pdf me-2"></i>PDF Document</h6>
                    </div>
                    <div class="card-body p-0">
                        <div id="pdfViewerContainer">
                            ${instruction.pdf_available ? 
                                `<iframe id="instructionPdfViewer" width="100%" height="500px" style="border: none;"></iframe>` : 
                                `<div class="text-center p-5">
                                    <i class="fas fa-file-times fa-3x text-muted mb-3"></i>
                                    <h6>No PDF Available</h6>
                                    <p class="text-muted">PDF document has not been generated yet</p>
                                </div>`
                            }
                        </div>
                    </div>
                </div>
                
                <!-- Cargo Summary -->
                <div class="card border-info mt-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Cargo Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="stat-mini">
                                    <div class="stat-mini-number">${instruction.total_volume || 0}</div>
                                    <div class="stat-mini-label">CBM</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-mini">
                                    <div class="stat-mini-number">${instruction.total_weight || 0}</div>
                                    <div class="stat-mini-label">KG</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-mini">
                                    <div class="stat-mini-number">${instruction.total_quantity || 0}</div>
                                    <div class="stat-mini-label">PCS</div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <p><strong>Primary Buyer:</strong> ${instruction.primary_buyer || 'N/A'}</p>
                        ${instruction.special_instructions ? `<p><strong>Special Instructions:</strong><br>${instruction.special_instructions}</p>` : ''}
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Setup PDF viewer
function setupPDFViewer(instruction) {
    if (instruction.pdf_available && instruction.pdf_filename) {
        const pdfViewer = document.getElementById('instructionPdfViewer');
        const downloadBtn = document.getElementById('downloadPdfBtn');
        
        if (pdfViewer) {
            // Gunakan route yang sudah ada di ForwarderController
            const viewUrl = `/forwarder/instruction/${instruction.instruction_id}/pdf/view`;
            pdfViewer.src = viewUrl;
            
            // Show download button
            if (downloadBtn) {
                downloadBtn.style.display = 'inline-block';
                downloadBtn.onclick = function() {
                    const downloadUrl = `/forwarder/instruction/${instruction.instruction_id}/pdf/download`;
                    window.open(downloadUrl, '_blank');
                };
            }
        }
    }
}

// Helper functions
function getPriorityColor(priority) {
    switch(priority?.toLowerCase()) {
        case 'urgent': return 'danger';
        case 'high': return 'warning';
        default: return 'info';
    }
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return dateString;
    }
}

function markInstructionAsRead(instructionId) {
    try {
        const forwarderCode = auth().user?.forwarder_code;
        const cacheKey = `notification_read_${forwarderCode}_${instructionId}`;
        localStorage.setItem(cacheKey, 'true');
        
        // Update UI untuk menandai sebagai read
        const instructionCard = document.querySelector(`[data-instruction-id="${instructionId}"]`);
        if (instructionCard) {
            instructionCard.classList.add('read');
        }
    } catch (error) {
        console.error('Error marking instruction as read:', error);
    }
}

        console.log('%c✅ Enhanced Forwarder Dashboard with Response System Loaded', 'color: #0f5132; font-weight: bold; font-size: 16px;');
    </script>
</body>
</html>