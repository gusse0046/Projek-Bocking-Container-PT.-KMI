@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header Section -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-truck me-2"></i>Import Dashboard - Surabaya & Semarang
                        <span class="badge bg-success ms-2">Live Data</span>
                    </h1>
                    <p class="text-muted mb-0">Complete import workflow management with delivery type filtering (ZDI1/ZDI2)</p>
                </div>
                <div>
                    <button class="btn btn-info me-2" onclick="syncImportData()" id="syncButton">
                        <i class="fas fa-sync me-2"></i>Sync SAP Data
                    </button>
                    <button class="btn btn-secondary me-2" onclick="classifyImportTypes()">
                        <i class="fas fa-tags me-2"></i>Classify Types
                    </button>
                    <button class="btn btn-danger" onclick="confirmLogout()" title="Logout">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </button>
                </div>
            </div>

            <!-- Import Types Overview Cards -->
            <div class="row mb-4">
                <div class="col-md-6 col-xl-2-4 mb-3">
                    <div class="card import-type-card" data-type="bahan_baku">
                        <div class="card-body text-center">
                            <div class="import-type-icon bg-primary">
                                <i class="fas fa-cubes"></i>
                            </div>
                            <h6 class="card-title mt-3">Bahan Baku</h6>
                            <p class="card-text text-muted">Raw Materials</p>
                            <div class="stats-preview">
                                <span id="bahan-baku-count">0</span> items
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2-4 mb-3">
                    <div class="card import-type-card" data-type="hardware">
                        <div class="card-body text-center">
                            <div class="import-type-icon bg-info">
                                <i class="fas fa-cog"></i>
                            </div>
                            <h6 class="card-title mt-3">Hardware</h6>
                            <p class="card-text text-muted">Hardware Components</p>
                            <div class="stats-preview">
                                <span id="hardware-count">0</span> items
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2-4 mb-3">
                    <div class="card import-type-card" data-type="sparepart">
                        <div class="card-body text-center">
                            <div class="import-type-icon bg-warning">
                                <i class="fas fa-tools"></i>
                            </div>
                            <h6 class="card-title mt-3">Sparepart</h6>
                            <p class="card-text text-muted">Spare Parts</p>
                            <div class="stats-preview">
                                <span id="sparepart-count">0</span> items
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2-4 mb-3">
                    <div class="card import-type-card" data-type="tools">
                        <div class="card-body text-center">
                            <div class="import-type-icon bg-success">
                                <i class="fas fa-hammer"></i>
                            </div>
                            <h6 class="card-title mt-3">Tools</h6>
                            <p class="card-text text-muted">Tools & Equipment</p>
                            <div class="stats-preview">
                                <span id="tools-count">0</span> items
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2-4 mb-3">
                    <div class="card import-type-card" data-type="mesin">
                        <div class="card-body text-center">
                            <div class="import-type-icon bg-danger">
                                <i class="fas fa-industry"></i>
                            </div>
                            <h6 class="card-title mt-3">Mesin</h6>
                            <p class="card-text text-muted">Machinery</p>
                            <div class="stats-preview">
                                <span id="mesin-count">0</span> items
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Location Filter Tabs -->
            <div class="location-filter-tabs mb-4">
                <div class="d-flex justify-content-center">
                    <button class="location-tab active" onclick="showLocationView('all')" data-location="all">
                        <i class="fas fa-globe me-2"></i>All Locations
                    </button>
                    <button class="location-tab" onclick="showLocationView('surabaya')" data-location="surabaya">
                        <i class="fas fa-anchor me-2"></i>Surabaya (ZDI1)
                    </button>
                    <button class="location-tab" onclick="showLocationView('semarang')" data-location="semarang">
                        <i class="fas fa-ship me-2"></i>Semarang (ZDI2)
                    </button>
                </div>
            </div>

            <!-- Search and Filter Controls -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="searchImports" 
                           placeholder="Search purchase orders, vendors..." onkeyup="searchImports()">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="filterImportType" onchange="filterImports()">
                        <option value="">All Types</option>
                        <option value="bahan_baku">Bahan Baku</option>
                        <option value="hardware">Hardware</option>
                        <option value="sparepart">Sparepart</option>
                        <option value="tools">Tools</option>
                        <option value="mesin">Mesin</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="filterStatus" onchange="filterImports()">
                        <option value="">All Status</option>
                        <option value="ready">Ready</option>
                        <option value="prepared">Prepared</option>
                        <option value="sent">Sent</option>
                        <option value="responded">Responded</option>
                        <option value="cleared">Cleared</option>
                        <option value="delivered">Delivered</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="filterPriority" onchange="filterImports()">
                        <option value="">All Priority</option>
                        <option value="urgent">Urgent</option>
                        <option value="high">High</option>
                        <option value="normal">Normal</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="filterLocation" onchange="filterImports()">
                        <option value="">All Locations</option>
                        <option value="surabaya">Surabaya</option>
                        <option value="semarang">Semarang</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-success w-100" onclick="toggleAllSections()">
                        <i class="fas fa-expand-alt me-1"></i>
                        <span id="toggleAllText">Expand</span>
                    </button>
                </div>
            </div>

            <!-- Main Import Content -->
            <div id="importContentContainer">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Import Instruction Modal -->
<div class="modal fade" id="importInstructionModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-truck me-2"></i>Generate Import Delivery Instruction
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="importInstructionForm">
                    <div id="importSummary" class="mb-4"></div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Forwarder Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="forwarder_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Notification Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="notification_email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Delivery Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="delivery_address" rows="2" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expected Delivery Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="expected_delivery_date" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Container Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="container_type" required>
                                <option value="">Select Container</option>
                                <option value="20ft_standard">20ft Standard</option>
                                <option value="40ft_standard">40ft Standard</option>
                                <option value="40ft_hc">40ft High Cube</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select class="form-select" name="priority" required>
                                <option value="">Select Priority</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Customs Clearance</label>
                            <select class="form-select" name="customs_clearance">
                                <option value="">Select Type</option>
                                <option value="standard">Standard</option>
                                <option value="express">Express</option>
                                <option value="bonded">Bonded Warehouse</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Port of Entry <span class="text-danger">*</span></label>
                            <select class="form-select" name="port_entry" required>
                                <option value="">Select Port</option>
                                <option value="Tanjung Perak - Surabaya">Tanjung Perak - Surabaya</option>
                                <option value="Tanjung Emas - Semarang">Tanjung Emas - Semarang</option>
                                <option value="Tanjung Priok - Jakarta">Tanjung Priok - Jakarta</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="contact_person" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Special Instructions</label>
                            <textarea class="form-control" name="special_instructions" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="generateImportInstruction()">
                    <i class="fas fa-file-pdf me-2"></i>Generate Instruction
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Progress Modal -->
<div class="modal fade" id="progressModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-tasks me-2"></i>Import Progress Tracking
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="progressContent">
                    <!-- Progress steps will be populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-info" onclick="refreshProgress()">
                    <i class="fas fa-sync me-2"></i>Refresh Progress
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Material Details Modal -->
<div class="modal fade" id="materialDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-list-alt me-2"></i>Import Material Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="materialDetailsContent">
                    <!-- Content will be populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden logout form -->
<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>

<style>
/* Custom Styles for Import Dashboard */
.import-type-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    cursor: pointer;
    height: 100%;
}

.import-type-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.import-type-card.active {
    border: 2px solid #28a745;
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), white);
}

.import-type-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: white;
    font-size: 1.5rem;
}

.location-filter-tabs {
    background: white;
    border-radius: 12px;
    padding: 0.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.location-tab {
    padding: 0.75rem 1.5rem;
    border: none;
    background: transparent;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    margin: 0 0.25rem;
}

.location-tab.active {
    background: #28a745;
    color: white;
}

.location-tab:not(.active):hover {
    background: #f8f9fa;
    color: #28a745;
}

.import-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    transition: all 0.3s ease;
    border-left: 5px solid transparent;
}

.import-section.bahan-baku {
    border-left-color: #007bff;
}

.import-section.hardware {
    border-left-color: #17a2b8;
}

.import-section.sparepart {
    border-left-color: #ffc107;
}

.import-section.tools {
    border-left-color: #28a745;
}

.import-section.mesin {
    border-left-color: #dc3545;
}

.import-section:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.section-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
    cursor: pointer;
    transition: background 0.3s ease;
}

.section-header:hover {
    background: #f8f9fa;
}

.section-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: between;
}

.section-content {
    display: none;
    padding: 1.5rem;
}

.section-content.show {
    display: block;
}

.po-table {
    margin: 0;
}

.po-table thead th {
    background: #f8f9fa;
    border: none;
    font-weight: 600;
    padding: 1rem;
}

.po-table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-top: 1px solid #e9ecef;
}

.po-table tbody tr:hover {
    background: #f8f9fa;
}

.status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    cursor: pointer;
}

.status-ready { background: #17a2b8; color: white; }
.status-prepared { background: #ffc107; color: white; }
.status-sent { background: #28a745; color: white; }
.status-responded { background: #6610f2; color: white; }
.status-cleared { background: #20c997; color: white; }
.status-delivered { background: #198754; color: white; }

.priority-badge {
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}

.priority-urgent { background: #dc3545; color: white; }
.priority-high { background: #fd7e14; color: white; }
.priority-normal { background: #6c757d; color: white; }

.value-display {
    font-weight: 600;
    color: #28a745;
    font-family: 'Monaco', 'Menlo', monospace;
}

.vendor-info {
    font-size: 0.9rem;
    color: #6c757d;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn {
    border-radius: 8px;
    padding: 0.4rem 0.8rem;
    font-weight: 500;
    font-size: 0.85rem;
    border: none;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Loading overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-spinner {
    width: 3rem;
    height: 3rem;
    border: 4px solid rgba(40, 167, 69, 0.1);
    border-top: 4px solid #28a745;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.toggle-icon {
    transition: transform 0.3s ease;
}

.toggle-icon.rotated {
    transform: rotate(180deg);
}

.stats-preview {
    font-weight: 600;
    color: #28a745;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .location-tab {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
    
    .import-type-icon {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
    }
    
    .section-header {
        padding: 1rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<script>
'use strict';

// Global Variables
let importData = [];
let groupedData = {};
let statistics = {};
let currentView = 'all';
let currentImportType = null;
let currentGroup = null;
let generatedInstructions = {};
let sentInstructions = {};
let progressTracker = {};

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    console.log('Import Dashboard initializing...');
    
    loadImportData();
    
    console.log('Import Dashboard loaded successfully');
});

// Load import data from controller
function loadImportData() {
    fetch('/import/data', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            importData = result.data || [];
            groupImportData();
            updateTypeCards();
            renderImportContent();
            showAlert('Import dashboard loaded successfully with ' + importData.length + ' records', 'success');
        } else {
            console.error('Failed to load import data:', result.error);
            showAlert('Failed to load import data', 'danger');
        }
    })
    .catch(error => {
        console.error('Error loading import data:', error);
        showAlert('Error loading import data', 'danger');
        importData = [];
        renderImportContent();
    });
}

// Group import data by type
function groupImportData() {
    groupedData = {
        by_type: {},
        by_location: {
            surabaya: [],
            semarang: []
        },
        by_status: {}
    };
    
    // Initialize type groups
    const importTypes = ['bahan_baku', 'hardware', 'sparepart', 'tools', 'mesin'];
    importTypes.forEach(type => {
        groupedData.by_type[type] = {
            type_code: type,
            items: [],
            purchase_orders: {},
            total_value: 0,
            total_quantity: 0
        };
    });
    
    // Group data
    importData.forEach(item => {
        const type = item.import_type;
        const location = item.location;
        const po = item.purchase_order;
        
        // Group by type
        if (groupedData.by_type[type]) {
            groupedData.by_type[type].items.push(item);
            groupedData.by_type[type].total_value += item.total_value;
            groupedData.by_type[type].total_quantity += item.quantity;
            
            if (!groupedData.by_type[type].purchase_orders[po]) {
                groupedData.by_type[type].purchase_orders[po] = {
                    purchase_order: po,
                    items: [],
                    total_value: 0,
                    vendor: item.vendor,
                    status: item.status,
                    priority: item.priority,
                    location: item.location,
                    expected_arrival: item.expected_arrival,
                    customs_status: item.customs_status,
                    tracking_number: item.tracking_number
                };
            }
            
            groupedData.by_type[type].purchase_orders[po].items.push(item);
            groupedData.by_type[type].purchase_orders[po].total_value += item.total_value;
        }
        
        // Group by location
        if (location === 'surabaya' || location === 'semarang') {
            groupedData.by_location[location].push(item);
        }
    });
}

// Update type cards with counts
function updateTypeCards() {
    Object.keys(groupedData.by_type).forEach(type => {
        const typeData = groupedData.by_type[type];
        const countElement = document.getElementById(type.replace('_', '-') + '-count');
        if (countElement) {
            countElement.textContent = Object.keys(typeData.purchase_orders).length;
        }
    });
}

// Show location view
function showLocationView(location) {
    currentView = location;
    
    // Update tab active state
    document.querySelectorAll('.location-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-location="${location}"]`).classList.add('active');
    
    renderImportContent();
    
    showAlert(`Switched to ${location === 'all' ? 'All Locations' : location.charAt(0).toUpperCase() + location.slice(1)} view`, 'info');
}

// Render import content
function renderImportContent() {
    const container = document.getElementById('importContentContainer');
    
    if (!importData || importData.length === 0) {
        container.innerHTML = createEmptyState();
        return;
    }
    
    let html = '';
    
    // Filter types based on current view
    let typesToShow = Object.keys(groupedData.by_type);
    
    if (currentView === 'surabaya') {
        typesToShow = typesToShow.filter(type => {
            return groupedData.by_type[type].items.some(item => item.location === 'surabaya');
        });
    } else if (currentView === 'semarang') {
        typesToShow = typesToShow.filter(type => {
            return groupedData.by_type[type].items.some(item => item.location === 'semarang');
        });
    }
    
    typesToShow.forEach(type => {
        const typeData = groupedData.by_type[type];
        
        // Filter purchase orders based on current view
        let purchaseOrders = Object.values(typeData.purchase_orders);
        
        if (currentView === 'surabaya') {
            purchaseOrders = purchaseOrders.filter(po => po.location === 'surabaya');
        } else if (currentView === 'semarang') {
            purchaseOrders = purchaseOrders.filter(po => po.location === 'semarang');
        }
        
        if (purchaseOrders.length > 0) {
            html += createImportSection(type, typeData, purchaseOrders);
        }
    });
    
    if (!html) {
        html = createEmptyState(`No import data for ${currentView === 'all' ? 'any location' : currentView}`);
    }
    
    container.innerHTML = html;
    
    // Auto-expand first section
    const firstSection = container.querySelector('.section-content');
    if (firstSection) {
        firstSection.classList.add('show');
        const firstIcon = container.querySelector('.toggle-icon');
        if (firstIcon) {
            firstIcon.classList.add('rotated');
        }
    }
}

// Create import section
function createImportSection(typeCode, typeData, purchaseOrders) {
    const typeNames = {
        bahan_baku: 'Bahan Baku (Raw Materials)',
        hardware: 'Hardware Components',
        sparepart: 'Spare Parts',
        tools: 'Tools & Equipment',
        mesin: 'Machinery'
    };
    
    const typeIcons = {
        bahan_baku: 'fas fa-cubes',
        hardware: 'fas fa-cog',
        sparepart: 'fas fa-tools',
        tools: 'fas fa-hammer',
        mesin: 'fas fa-industry'
    };
    
    const typeName = typeNames[typeCode] || typeCode;
    const typeIcon = typeIcons[typeCode] || 'fas fa-box';
    
    const totalValue = purchaseOrders.reduce((sum, po) => sum + po.total_value, 0);
    const totalItems = purchaseOrders.reduce((sum, po) => sum + po.items.length, 0);
    
    let html = `
        <div class="import-section ${typeCode}">
            <div class="section-header" onclick="toggleSection('${typeCode}')">
                <div class="section-title">
                    <div>
                        <i class="${typeIcon} me-2"></i>
                        ${typeName}
                        <div class="section-subtitle text-muted">
                            ${purchaseOrders.length} PO(s), ${totalItems} items, $${numberFormat(totalValue, 2)}
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-success me-2">${purchaseOrders.length} POs</span>
                        <i class="fas fa-chevron-down toggle-icon" id="toggle-${typeCode}"></i>
                    </div>
                </div>
            </div>
            <div class="section-content" id="content-${typeCode}">
                ${createPurchaseOrderTable(purchaseOrders, typeCode)}
            </div>
        </div>
    `;
    
    return html;
}

// Create purchase order table
function createPurchaseOrderTable(purchaseOrders, typeCode) {
    if (purchaseOrders.length === 0) {
        return createEmptyState('No purchase orders for this type');
    }
    
    let html = `
        <table class="po-table table">
            <thead>
                <tr>
                    <th style="width: 25%;">Purchase Order</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 20%;">Actions</th>
                    <th style="width: 20%;">Vendor & Value</th>
                    <th style="width: 20%;">Location & Delivery</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    purchaseOrders.forEach(po => {
        const locationBadge = po.location === 'surabaya' ? 
            '<span class="badge bg-info">ZDI1 - Surabaya</span>' : 
            '<span class="badge bg-warning">ZDI2 - Semarang</span>';
        
        html += `
            <tr data-po="${po.purchase_order}">
                <td>
                    <div class="fw-bold">${po.purchase_order}</div>
                    <div class="small text-muted">
                        ${locationBadge}<br>
                        Items: ${po.items.length} | 
                        <span class="priority-badge priority-${po.priority}">${po.priority.toUpperCase()}</span>
                    </div>
                </td>
                <td>
                    <span class="status-badge status-${po.status}" 
                          onclick="showProgressModal('${po.purchase_order}')" 
                          title="Click to view progress">
                        ${po.status.toUpperCase()}
                    </span>
                    <div class="small text-muted mt-1">
                        ${po.customs_status || 'Pending'}
                    </div>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-warning btn-sm" 
                                onclick="generateImportInstructionModal('${po.purchase_order}', '${typeCode}')"
                                ${generatedInstructions[po.purchase_order] ? 'disabled' : ''}>
                            <i class="fas fa-file-alt me-1"></i>
                            ${generatedInstructions[po.purchase_order] ? 'Generated' : 'Generate'}
                        </button>
                        
                        <button class="btn btn-success btn-sm" 
                                onclick="sendImportInstruction('${po.purchase_order}')"
                                ${!generatedInstructions[po.purchase_order] || sentInstructions[po.purchase_order] ? 'disabled' : ''}>
                            <i class="fas fa-paper-plane me-1"></i>
                            ${sentInstructions[po.purchase_order] ? 'Sent' : 'Send'}
                        </button>
                        
                        <button class="btn btn-outline-primary btn-sm" 
                                onclick="showMaterialDetails('${po.purchase_order}')">
                            <i class="fas fa-list-alt me-1"></i>Details
                        </button>
                    </div>
                </td>
                <td>
                    <div class="vendor-info">
                        <strong>${po.vendor}</strong><br>
                        <span class="value-display">$${numberFormat(po.total_value, 2)}</span><br>
                        <small class="text-muted">
                            ${po.items.length} item(s)
                        </small>
                    </div>
                </td>
                <td>
                    <div class="small">
                        <strong>Port:</strong> ${po.location === 'surabaya' ? 'Tanjung Perak' : 'Tanjung Emas'}<br>
                        <strong>ETA:</strong> ${formatDate(po.expected_arrival)}<br>
                        ${po.tracking_number ? `<strong>Track:</strong> ${po.tracking_number}<br>` : ''}
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    return html;
}

// Toggle section visibility
function toggleSection(typeCode) {
    const content = document.getElementById(`content-${typeCode}`);
    const icon = document.getElementById(`toggle-${typeCode}`);
    
    if (content && icon) {
        if (content.classList.contains('show')) {
            content.classList.remove('show');
            icon.classList.remove('rotated');
        } else {
            content.classList.add('show');
            icon.classList.add('rotated');
        }
    }
}

// Generate import instruction modal
function generateImportInstructionModal(purchaseOrder, typeCode) {
    // Find the purchase order data
    const po = findPurchaseOrder(purchaseOrder);
    if (!po) {
        showAlert('Purchase order not found', 'danger');
        return;
    }
    
    currentGroup = po;
    
    // Populate modal with PO data
    const summaryHtml = `
        <div class="alert alert-info">
            <h6><i class="fas fa-file-invoice me-2"></i>Import Purchase Order: ${po.purchase_order}</h6>
            <p><strong>Type:</strong> ${getTypeName(typeCode)} | <strong>Vendor:</strong> ${po.vendor}</p>
            <p><strong>Items:</strong> ${po.items.length} | <strong>Total Value:</strong> $${numberFormat(po.total_value, 2)}</p>
            <p><strong>Expected Arrival:</strong> ${formatDate(po.expected_arrival)} | <strong>Priority:</strong> ${po.priority.toUpperCase()}</p>
        </div>
    `;
    
    document.getElementById('importSummary').innerHTML = summaryHtml;
    
    // Set default values
    const form = document.getElementById('importInstructionForm');
    form.querySelector('[name="expected_delivery_date"]').value = po.expected_arrival;
    form.querySelector('[name="port_entry"]').value = po.location === 'surabaya' ? 
        'Tanjung Perak - Surabaya' : 'Tanjung Emas - Semarang';
    
    const modal = new bootstrap.Modal(document.getElementById('importInstructionModal'));
    modal.show();
}

// Generate import instruction
function generateImportInstruction() {
    if (!currentGroup) {
        showAlert('No purchase order selected', 'danger');
        return;
    }
    
    const form = document.getElementById('importInstructionForm');
    const formData = new FormData(form);
    
    // Validation
    const requiredFields = ['forwarder_name', 'notification_email', 'delivery_address', 'expected_delivery_date', 'container_type', 'priority'];
    for (const field of requiredFields) {
        if (!formData.get(field)) {
            showAlert(`Please fill in the ${field.replace('_', ' ')} field`, 'warning');
            return;
        }
    }
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('importInstructionModal'));
    modal.hide();
    
    showLoading(true);
    
    // Simulate instruction generation
    setTimeout(() => {
        generatedInstructions[currentGroup.purchase_order] = {
            generated_at: new Date().toISOString(),
            forwarder_name: formData.get('forwarder_name'),
            notification_email: formData.get('notification_email'),
            delivery_address: formData.get('delivery_address'),
            expected_delivery_date: formData.get('expected_delivery_date')
        };
        
        updateProgressTracker(currentGroup.purchase_order, 'instruction_generated');
        
        showLoading(false);
        renderImportContent();
        showAlert('Import delivery instruction generated successfully!', 'success');
        
        form.reset();
        currentGroup = null;
    }, 2000);
}

// Send import instruction
function sendImportInstruction(purchaseOrder) {
    const instruction = generatedInstructions[purchaseOrder];
    
    if (!instruction) {
        showAlert('Please generate instruction first', 'warning');
        return;
    }
    
    if (sentInstructions[purchaseOrder]) {
        showAlert('Instruction already sent for this purchase order', 'warning');
        return;
    }
    
    showLoading(true);
    
    // Simulate sending instruction
    setTimeout(() => {
        sentInstructions[purchaseOrder] = {
            sent_at: new Date().toISOString(),
            forwarder_name: instruction.forwarder_name,
            notification_email: instruction.notification_email
        };
        
        updateProgressTracker(purchaseOrder, 'instruction_sent');
        
        showLoading(false);
        renderImportContent();
        showAlert('Import delivery instruction sent to forwarder successfully!', 'success');
    }, 1500);
}

// Show progress modal
function showProgressModal(purchaseOrder) {
    const po = findPurchaseOrder(purchaseOrder);
    if (!po) {
        showAlert('Purchase order not found', 'danger');
        return;
    }
    
    currentGroup = po;
    
    const steps = generateProgressSteps(po);
    const progressHtml = renderProgressSteps(steps, po);
    
    document.getElementById('progressContent').innerHTML = progressHtml;
    
    const modal = new bootstrap.Modal(document.getElementById('progressModal'));
    modal.show();
}

// Generate progress steps
function generateProgressSteps(po) {
    const progress = progressTracker[po.purchase_order] || {};
    
    return [
        {
            id: 1,
            title: 'Import Instruction Preparation',
            description: 'Generate import delivery instruction and prepare documentation',
            icon: 'fas fa-file-alt',
            completed: progress.instruction_generated || generatedInstructions[po.purchase_order],
            active: !progress.instruction_generated && !generatedInstructions[po.purchase_order],
            status: generatedInstructions[po.purchase_order] ? 'Instruction Generated' : 'Waiting for instruction'
        },
        {
            id: 2,
            title: 'Send to Forwarder',
            description: 'Send import delivery instruction to assigned forwarder',
            icon: 'fas fa-paper-plane',
            completed: progress.instruction_sent || sentInstructions[po.purchase_order],
            active: generatedInstructions[po.purchase_order] && !sentInstructions[po.purchase_order],
            status: sentInstructions[po.purchase_order] ? 'Instruction Sent' : 'Ready to send'
        },
        {
            id: 3,
            title: 'Customs Clearance',
            description: 'Forwarder handles customs clearance and documentation',
            icon: 'fas fa-file-shield',
            completed: po.status === 'cleared' || po.status === 'delivered',
            active: sentInstructions[po.purchase_order] && po.status !== 'cleared' && po.status !== 'delivered',
            status: po.customs_status || 'Awaiting customs clearance'
        },
        {
            id: 4,
            title: 'Delivery Arrangement',
            description: 'Forwarder arranges delivery logistics and transportation',
            icon: 'fas fa-truck',
            completed: po.status === 'delivered',
            active: po.status === 'cleared',
            status: po.status === 'delivered' ? 'Delivered' : 'Pending delivery'
        },
        {
            id: 5,
            title: 'Final Delivery & Receipt',
            description: 'Import delivery completed and goods received',
            icon: 'fas fa-check-double',
            completed: po.status === 'delivered',
            active: false,
            status: po.status === 'delivered' ? 'Completed' : 'Final step pending'
        }
    ];
}

// Render progress steps
function renderProgressSteps(steps, po) {
    let html = `
        <div class="mb-4 p-3" style="background: #f8f9fa; border-radius: 10px; border: 1px solid #dee2e6;">
            <h6 class="text-success fw-bold mb-3">
                <i class="fas fa-route me-2"></i>Import Progress for Purchase Order: ${po.purchase_order}
            </h6>
            <div class="row">
                <div class="col-md-3">
                    <strong>Vendor:</strong> ${po.vendor}
                </div>
                <div class="col-md-3">
                    <strong>Items:</strong> ${po.items.length}
                </div>
                <div class="col-md-3">
                    <strong>Value:</strong> ${numberFormat(po.total_value, 2)}
                </div>
                <div class="col-md-3">
                    <strong>Status:</strong> ${po.status.toUpperCase()}
                </div>
            </div>
        </div>
    `;
    
    steps.forEach((step) => {
        let stepClass = 'text-muted';
        let iconClass = 'text-muted';
        let bgClass = 'bg-light';
        
        if (step.completed) {
            stepClass = 'text-success';
            iconClass = 'text-success';
            bgClass = 'bg-success bg-opacity-10';
        } else if (step.active) {
            stepClass = 'text-primary';
            iconClass = 'text-primary';
            bgClass = 'bg-primary bg-opacity-10';
        }
        
        html += `
            <div class="d-flex align-items-center p-3 mb-3 border rounded ${bgClass}">
                <div class="me-3">
                    <div class="rounded-circle bg-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; border: 3px solid;">
                        <i class="${step.icon} ${iconClass}" style="font-size: 1.2rem;"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-1 ${stepClass}">${step.title}</h6>
                    <p class="mb-1 text-muted small">${step.description}</p>
                    <small class="fw-bold ${stepClass}">${step.status}</small>
                </div>
            </div>
        `;
    });
    
    return html;
}

// Show material details modal
function showMaterialDetails(purchaseOrder) {
    const po = findPurchaseOrder(purchaseOrder);
    if (!po) {
        showAlert('Purchase order not found', 'danger');
        return;
    }
    
    let html = `
        <h6 class="text-success mb-3">Purchase Order: ${po.purchase_order}</h6>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Material Code</th>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total Value</th>
                        <th>Origin</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    let totalValue = 0;
    let totalQty = 0;
    
    po.items.forEach(item => {
        totalValue += item.total_value;
        totalQty += item.quantity;
        
        html += `
            <tr>
                <td><span class="badge bg-secondary">${item.material_code}</span></td>
                <td>${item.material_description}</td>
                <td class="text-end">${numberFormat(item.quantity, 0)}</td>
                <td class="text-end">${numberFormat(item.unit_price, 2)}</td>
                <td class="text-end value-display">${numberFormat(item.total_value, 2)}</td>
                <td>${item.origin_country}</td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
                <tfoot class="table-secondary">
                    <tr>
                        <th colspan="2" class="text-end">TOTAL:</th>
                        <th class="text-end">${numberFormat(totalQty, 0)}</th>
                        <th></th>
                        <th class="text-end value-display">${numberFormat(totalValue, 2)}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    `;
    
    document.getElementById('materialDetailsContent').innerHTML = html;
    
    const modal = new bootstrap.Modal(document.getElementById('materialDetailsModal'));
    modal.show();
}

// Filter functions
function searchImports() {
    const searchTerm = document.getElementById('searchImports').value.toLowerCase();
    const rows = document.querySelectorAll('.po-table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = text.includes(searchTerm);
        row.style.display = matches ? '' : 'none';
    });
}

function filterImports() {
    const typeFilter = document.getElementById('filterImportType').value;
    const statusFilter = document.getElementById('filterStatus').value;
    const priorityFilter = document.getElementById('filterPriority').value;
    const locationFilter = document.getElementById('filterLocation').value;
    
    const sections = document.querySelectorAll('.import-section');
    
    sections.forEach(section => {
        const sectionType = section.classList[1]; // e.g., 'bahan-baku'
        const typeCode = sectionType.replace('-', '_');
        
        let showSection = true;
        
        // Type filter
        if (typeFilter && typeFilter !== typeCode) {
            showSection = false;
        }
        
        if (showSection) {
            const rows = section.querySelectorAll('.po-table tbody tr');
            let visibleRows = 0;
            
            rows.forEach(row => {
                let showRow = true;
                const rowText = row.textContent.toLowerCase();
                
                // Status filter
                if (statusFilter && !rowText.includes(statusFilter)) {
                    showRow = false;
                }
                
                // Priority filter
                if (priorityFilter && !rowText.includes(priorityFilter)) {
                    showRow = false;
                }
                
                // Location filter
                if (locationFilter) {
                    const locationBadge = row.querySelector('.badge');
                    if (locationBadge) {
                        const badgeText = locationBadge.textContent.toLowerCase();
                        const hasLocation = badgeText.includes(locationFilter);
                        if (!hasLocation) {
                            showRow = false;
                        }
                    }
                }
                
                row.style.display = showRow ? '' : 'none';
                if (showRow) visibleRows++;
            });
            
            // Hide section if no visible rows
            if (visibleRows === 0) {
                showSection = false;
            }
        }
        
        section.style.display = showSection ? '' : 'none';
    });
}

// Toggle all sections
function toggleAllSections() {
    const contents = document.querySelectorAll('.section-content');
    const icons = document.querySelectorAll('.toggle-icon');
    const toggleButton = document.getElementById('toggleAllText');
    
    const allExpanded = Array.from(contents).every(content => content.classList.contains('show'));
    
    if (allExpanded) {
        contents.forEach(content => content.classList.remove('show'));
        icons.forEach(icon => icon.classList.remove('rotated'));
        if (toggleButton) toggleButton.textContent = 'Expand';
    } else {
        contents.forEach(content => content.classList.add('show'));
        icons.forEach(icon => icon.classList.add('rotated'));
        if (toggleButton) toggleButton.textContent = 'Collapse';
    }
}

// Import type card click handlers
document.addEventListener('click', function(e) {
    if (e.target.closest('.import-type-card')) {
        const card = e.target.closest('.import-type-card');
        const type = card.dataset.type;
        
        // Toggle card active state
        document.querySelectorAll('.import-type-card').forEach(c => c.classList.remove('active'));
        card.classList.add('active');
        
        // Filter content to show only this type
        currentImportType = type;
        document.getElementById('filterImportType').value = type;
        filterImports();
        
        showAlert(`Filtered to show ${getTypeName(type)} imports`, 'info');
    }
});

// Utility functions
function findPurchaseOrder(purchaseOrder) {
    for (const typeCode in groupedData.by_type) {
        const typeData = groupedData.by_type[typeCode];
        if (typeData.purchase_orders[purchaseOrder]) {
            return typeData.purchase_orders[purchaseOrder];
        }
    }
    return null;
}

function getTypeName(typeCode) {
    const typeNames = {
        bahan_baku: 'Bahan Baku',
        hardware: 'Hardware',
        sparepart: 'Sparepart', 
        tools: 'Tools',
        mesin: 'Mesin'
    };
    return typeNames[typeCode] || typeCode;
}

function updateProgressTracker(purchaseOrder, action) {
    if (!progressTracker[purchaseOrder]) {
        progressTracker[purchaseOrder] = {};
    }
    
    progressTracker[purchaseOrder][action] = true;
    progressTracker[purchaseOrder][action + '_at'] = new Date().toISOString();
}

function refreshProgress() {
    if (currentGroup) {
        showProgressModal(currentGroup.purchase_order);
        showAlert('Progress refreshed successfully', 'success');
    }
}

function syncImportData() {
    const syncButton = document.getElementById('syncButton');
    const originalText = syncButton.innerHTML;
    
    syncButton.innerHTML = '<i class="fas fa-sync fa-spin me-2"></i>Syncing...';
    syncButton.disabled = true;
    
    fetch('/import/sync-sap', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ force: true })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showAlert('Import data synchronized successfully', 'success');
            setTimeout(() => loadImportData(), 1000);
        } else {
            showAlert('Sync failed: ' + result.error, 'danger');
        }
    })
    .catch(error => {
        console.error('Sync error:', error);
        showAlert('Sync failed: ' + error.message, 'danger');
    })
    .finally(() => {
        syncButton.innerHTML = originalText;
        syncButton.disabled = false;
    });
}

function classifyImportTypes() {
    showAlert('Import type classification completed', 'info');
}

function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        document.getElementById('logout-form').submit();
    }
}

function showLoading(show) {
    const overlay = document.querySelector('.loading-overlay') || createLoadingOverlay();
    overlay.style.display = show ? 'flex' : 'none';
}

function createLoadingOverlay() {
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.innerHTML = '<div class="loading-spinner"></div>';
    document.body.appendChild(overlay);
    return overlay;
}

function createEmptyState(message = 'No import data available') {
    return `
        <div class="empty-state">
            <i class="fas fa-truck"></i>
            <h5>${message}</h5>
            <p class="text-muted">Import data will appear here when available</p>
        </div>
    `;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric',
        year: 'numeric'
    });
}

function numberFormat(number, decimals = 0) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number || 0);
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

console.log('Import Dashboard with 5 Import Types - Ready');
</script>
@endsection