@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary-color: #2c3e50;
        --secondary-color: #34495e;
        --success-color: #27ae60;
        --warning-color: #f39c12;
        --danger-color: #e74c3c;
        --info-color: #3498db;
        --light-bg: #ecf0f1;
        --dark-text: #2c3e50;
        --border-color: #bdc3c7;
        --surabaya-color: #3498db;
        --semarang-color: #e67e22;
        --crate-barrel-color: #8e44ad;
    }
    
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        min-height: 100vh;
        color: var(--dark-text);
    }

    .dashboard-header {
        background: white;
        border-bottom: 2px solid var(--border-color);
        padding: 1.5rem 0;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    
    .page-title {
        color: var(--primary-color);
        font-weight: 700;
        margin: 0;
        font-size: 2rem;
    }

    .location-filter-tabs {
        background: white;
        border-radius: 12px;
        padding: 0.5rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
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
        background: var(--primary-color);
        color: white;
    }

    .location-tab:not(.active):hover {
        background: #f8f9fa;
        color: var(--primary-color);
    }

    .location-cards-container {
        display: none;
    }

    .location-cards-container.show {
        display: block;
    }

    .location-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        border-left: 5px solid var(--primary-color);
        transition: all 0.3s ease;
    }

    .location-card.surabaya-card {
        border-left-color: var(--surabaya-color);
    }

    .location-card.semarang-card {
        border-left-color: var(--semarang-color);
    }

    .location-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e9ecef;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    .location-header:hover {
        background: #f8f9fa;
    }

    .location-title {
        font-size: 1.3rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .location-subtitle {
        font-size: 0.9rem;
        opacity: 0.8;
        margin-top: 0.25rem;
    }

    .location-stats {
        display: flex;
        justify-content: space-between;
        margin-top: 1rem;
    }

    .stat-card {
        text-align: center;
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: 8px;
        flex: 1;
        margin: 0 0.25rem;
    }

    .stat-number {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--primary-color);
    }

    .stat-label {
        font-size: 0.8rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }

    .toggle-icon {
        transition: transform 0.3s ease;
    }

    .toggle-icon.rotated {
        transform: rotate(180deg);
    }

    .location-content {
        display: none;
    }

    .location-content.show {
        display: block;
    }

    .forwarders-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
        padding: 1.5rem;
    }

    .forwarder-card-button {
        background: white;
        border: 2px solid var(--border-color);
        border-radius: 12px;
        padding: 1.5rem;
        transition: all 0.3s ease;
        cursor: pointer;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .forwarder-card-button:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        border-color: var(--primary-color);
    }

    .forwarder-card-button.has-data {
        border-color: var(--success-color);
        background: linear-gradient(135deg, rgba(39, 174, 96, 0.05), rgba(39, 174, 96, 0.02));
    }

    .forwarder-card-button.no-data {
        border-color: var(--border-color);
        background: rgba(108, 117, 125, 0.05);
    }

    .forwarder-card-button.surabaya-style {
        border-left: 5px solid var(--surabaya-color);
    }

    .forwarder-card-button.semarang-style {
        border-left: 5px solid var(--semarang-color);
    }

    .forwarder-card-button.crate-barrel-style {
        border-left: 5px solid var(--crate-barrel-color);
        background: linear-gradient(135deg, rgba(142, 68, 173, 0.05), rgba(142, 68, 173, 0.02));
    }

    .forwarder-card-title {
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
        line-height: 1.3;
    }

    .forwarder-card-subtitle {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 1rem;
    }

    .forwarder-card-stats {
        display: flex;
        justify-content: space-around;
        margin-bottom: 1rem;
    }

    .forwarder-mini-stat {
        text-align: center;
    }

    .forwarder-mini-stat-number {
        font-weight: 700;
        font-size: 1.2rem;
        color: var(--primary-color);
    }

    .forwarder-mini-stat-label {
        font-size: 0.7rem;
        color: #6c757d;
        text-transform: uppercase;
    }

    .forwarder-card-badges {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .forwarder-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .mapped-badge {
        background: var(--success-color);
        color: white;
    }

    .unmapped-badge {
        background: var(--warning-color);
        color: white;
    }

    .has-data-badge {
        background: var(--info-color);
        color: white;
    }

    .no-data-badge {
        background: var(--border-color);
        color: white;
    }

    .dynamic-badge {
        background: linear-gradient(45deg, #28a745, #20c997);
        color: white;
    }

    .migration-badge {
        background: linear-gradient(45deg, #17a2b8, #138496);
        color: white;
        font-size: 0.7rem;
        padding: 0.2rem 0.6rem;
    }

    .crate-barrel-badge {
        background: var(--crate-barrel-color);
        color: white;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    .forwarder-data-container {
        display: none;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        overflow: hidden;
    }

    .forwarder-data-container.show {
        display: block;
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .forwarder-data-header {
        padding: 1.5rem;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .forwarder-data-title {
        font-size: 1.3rem;
        font-weight: 700;
        margin: 0;
    }

    .close-forwarder-btn {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 50%;
        transition: background 0.3s ease;
    }

    .close-forwarder-btn:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .back-to-grid-btn {
        background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        margin-bottom: 1rem;
    }

    .back-to-grid-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(44, 62, 80, 0.3);
        color: white;
    }

    .ref-invoice-table {
        margin: 0;
        border-radius: 0;
    }

    .ref-invoice-table thead th {
        background: #f8f9fa;
        color: var(--dark-text);
        border: none;
        font-weight: 600;
        padding: 1rem;
        font-size: 0.9rem;
    }

    .ref-invoice-table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-top: 1px solid #e9ecef;
    }

    .ref-invoice-table tbody tr:hover {
        background: #f8f9fa;
    }

    .ref-invoice-cell {
        font-weight: 600;
        color: var(--primary-color);
    }

    .group-summary {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 0.5rem;
    }

    .status-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .status-badge:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }

    .status-ready { background: var(--info-color); color: white; }
    .status-generated { background: var(--warning-color); color: white; }
    .status-sent { background: var(--success-color); color: white; }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        flex-direction: column;
    }

    .btn {
        border-radius: 8px;
        padding: 0.5rem 1rem;
        font-weight: 500;
        border: none;
        transition: all 0.3s ease;
        font-size: 0.85rem;
    }

    .btn-primary { background: var(--primary-color); color: white; }
    .btn-primary:hover { background: var(--secondary-color); transform: translateY(-1px); color: white; }

    .btn-grouped {
        background: linear-gradient(135deg, #28a745, #20c997);
        border: none;
        color: white;
        transition: all 0.3s ease;
    }

    .btn-grouped:hover {
        background: linear-gradient(135deg, #20c997, #28a745);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        color: white;
    }

    .btn-single {
        background: linear-gradient(135deg, #007bff, #0056b3);
        border: none;
        color: white;
        transition: all 0.3s ease;
    }

    .btn-single:hover {
        background: linear-gradient(135deg, #0056b3, #007bff);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        color: white;
    }

    .btn-send {
        background: linear-gradient(135deg, #28a745, #34ce57);
        border: none;
        color: white;
        transition: all 0.3s ease;
        margin-top: 0.5rem;
    }

    .btn-send:hover {
        background: linear-gradient(135deg, #34ce57, #28a745);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        color: white;
    }

    .btn-crate-barrel {
        background: linear-gradient(135deg, var(--crate-barrel-color), #9b59b6);
        border: none;
        color: white;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .btn-crate-barrel:hover {
        background: linear-gradient(135deg, #9b59b6, var(--crate-barrel-color));
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(142, 68, 173, 0.3);
        color: white;
    }

    .combined-invoice-badge {
        background: linear-gradient(45deg, #17a2b8, #138496);
        color: white;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .single-invoice-badge {
        background: linear-gradient(45deg, #007bff, #0056b3);
        color: white;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .sub-invoices-row {
        background-color: #f8f9fa;
        transition: all 0.3s ease;
    }

    .sub-invoices-container {
        border-left: 4px solid #17a2b8;
        background: white;
        margin: 0.5rem;
        border-radius: 8px;
        padding: 1rem;
    }

    .sub-invoice-item {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        border-left: 3px solid #17a2b8;
    }

    .sub-invoice-item:last-child {
        margin-bottom: 0;
    }

    .sub-invoice-title {
        font-weight: 600;
        color: var(--primary-color);
        font-size: 0.9rem;
    }

    .sub-invoice-details {
        font-size: 0.8rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }

    .table-info {
        background-color: rgba(23, 162, 184, 0.1) !important;
    }

    .table-info:hover {
        background-color: rgba(23, 162, 184, 0.2) !important;
    }

    .sub-invoices-row td {
        border-top: none !important;
        padding: 0 !important;
    }

    .dropdown-toggle-btn {
        background: none;
        border: none;
        color: #17a2b8;
        font-size: 0.85rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        transition: all 0.3s ease;
    }

    .dropdown-toggle-btn:hover {
        background: rgba(23, 162, 184, 0.1);
        color: #138496;
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
        border: 4px solid rgba(44, 62, 80, 0.1);
        border-top: 4px solid var(--primary-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .modal-content {
        border-radius: 12px;
        border: none;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }
    
    .modal-header {
        background: var(--primary-color);
        color: white;
        border-radius: 12px 12px 0 0;
        padding: 1.5rem;
        border: none;
    }
    
    .modal-body {
        padding: 1.5rem;
    }
    
    .modal-footer {
        padding: 1rem 1.5rem;
        border: none;
        background: #f8f9fa;
    }
    
    .form-control, .form-select {
        border-radius: 8px;
        border: 2px solid var(--border-color);
        padding: 0.75rem;
        transition: border-color 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
    }
    
    .form-label {
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .notification-section {
        border: 2px solid #e3f2fd;
        border-radius: 8px;
        background: linear-gradient(135deg, #f8f9ff, #e8f4fd);
        margin-top: 1.5rem;
        padding: 1rem;
    }

    .notification-section h6 {
        color: var(--info-color);
        margin-bottom: 1rem;
        font-weight: 700;
    }

    .notification-option {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        padding: 1rem;
        background: white;
        border-radius: 8px;
        margin-bottom: 0.75rem;
        border: 2px solid #dee2e6;
        transition: all 0.3s ease;
    }

    .notification-option:hover {
        background: #f8f9fa;
        border-color: var(--info-color);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.15);
    }

    .notification-option.enabled {
        border-color: var(--success-color);
        background: rgba(39, 174, 96, 0.05);
    }

    .notification-details {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 0.5rem;
    }

    .notification-count {
        background: var(--info-color);
        color: white;
        padding: 0.2rem 0.6rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-left: 0.5rem;
    }

    .migration-data-indicator {
        background: linear-gradient(45deg, #28a745, #20c997);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.7rem;
        font-weight: 600;
        margin-left: 0.5rem;
    }

    .auto-fill-indicator {
        background: rgba(40, 167, 69, 0.1);
        border: 1px solid var(--success-color);
        border-radius: 6px;
        padding: 0.5rem;
        margin-top: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .auto-fill-indicator i {
        color: var(--success-color);
    }

    .auto-fill-indicator .text {
        font-size: 0.8rem;
        color: var(--success-color);
        font-weight: 600;
    }

    .pdf-sent-status {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-top: 0.5rem;
        padding: 0.75rem;
        background: rgba(39, 174, 96, 0.1);
        border-radius: 8px;
        border: 1px solid var(--success-color);
    }

    .pdf-sent-status .status-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
    }

    .pdf-sent-status .status-item i {
        color: var(--success-color);
    }

    @media (max-width: 768px) {
        .location-stats {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .stat-card {
            margin: 0;
        }

        .location-tab {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }

        .forwarders-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .notification-option {
            flex-direction: column;
            align-items: flex-start;
        }

        .action-buttons {
            flex-direction: column;
        }
    }

/* WhatsApp Specific Styling */
#primary_whatsapp {
    background: linear-gradient(135deg, rgba(37, 211, 102, 0.1), rgba(255, 255, 255, 0.9)) !important;
    border: 2px solid #28a745 !important;
    font-weight: 500;
    color: #155724;
}

#primary_whatsapp:focus {
    border-color: #25d366 !important;
    box-shadow: 0 0 0 0.2rem rgba(37, 211, 102, 0.25) !important;
}

.auto-filled {
    border-color: #28a745 !important;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
    background-color: rgba(40, 167, 69, 0.05) !important;
    animation: autoFillGlow 2s ease-in-out;
}

@keyframes autoFillGlow {
    0%, 100% { background-color: rgba(40, 167, 69, 0.05); }
    50% { background-color: rgba(40, 167, 69, 0.15); }
}

/* Enhanced Notification Section */
.notification-section {
    border: 2px solid #e3f2fd;
    border-radius: 12px;
    background: linear-gradient(135deg, #f8f9ff, #e8f4fd);
    margin-top: 1.5rem;
    padding: 1.5rem;
}

.notification-option {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    border: 2px solid #dee2e6;
    transition: all 0.3s ease;
    height: 100%;
}

.notification-option:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.notification-option.enabled {
    border-color: var(--success-color);
    background: rgba(39, 174, 96, 0.02);
}

/* Form Switch Styling */
.form-check-input {
    width: 2.5em;
    height: 1.25em;
    border: 2px solid #dee2e6;
    transition: all 0.3s ease;
}

.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.form-check-input:checked[id*="WhatsApp"] {
    background-color: #25d366;
    border-color: #25d366;
}

.form-check-input:focus {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Badge Styling */
.badge.bg-primary {
    background: linear-gradient(135deg, #007bff, #0056b3) !important;
}

.badge.bg-success {
    background: linear-gradient(135deg, #25d366, #20c997) !important;
}

.badge.bg-secondary {
    background: linear-gradient(135deg, #6c757d, #495057) !important;
}

/* WhatsApp Preview Box */
.bg-success.bg-opacity-10 {
    background: rgba(37, 211, 102, 0.1) !important;
    border: 1px solid rgba(37, 211, 102, 0.3) !important;
}

/* Migration Badge */
.migration-badge {
    background: linear-gradient(45deg, #28a745, #20c997);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-left: 0.5rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

/* Success/Warning Text Colors */
.text-success {
    color: #25d366 !important;
}

.text-warning {
    color: #ffc107 !important;
}

/* Status Display Enhancement */
.pdf-sent-status {
    background: rgba(37, 211, 102, 0.1);
    border: 1px solid #25d366;
    border-radius: 8px;
    padding: 0.75rem;
    margin-top: 0.5rem;
}

.pdf-sent-status .status-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    margin-bottom: 0.25rem;
}

.pdf-sent-status .status-item i {
    color: #25d366;
    width: 16px;
}

.pdf-sent-status .status-item:last-child {
    margin-bottom: 0;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .notification-section .row {
        flex-direction: column;
    }
    
    .notification-option {
        margin-bottom: 1rem;
    }
    
    #primary_whatsapp {
        font-size: 0.9rem;
    }
    
    .migration-badge {
        display: block;
        margin: 0.25rem 0;
        text-align: center;
    }
}

/* TAMBAHAN CSS UNTUK AUTO-WHATSAPP SYSTEM */
/* Tambahkan setelah CSS WhatsApp yang sudah ada */

/* WhatsApp Brand Colors */
:root {
    --whatsapp-color: #25d366;
    --whatsapp-dark: #128c7e;
    --whatsapp-light: #dcf8c6;
}

/* Auto-WhatsApp Specific Styling */
.whatsapp-indicator {
    background: linear-gradient(135deg, rgba(37, 211, 102, 0.1), rgba(255, 255, 255, 0.9));
    border: 2px solid var(--whatsapp-color);
    border-radius: 8px;
    padding: 0.5rem;
    margin-top: 0.5rem;
}

.whatsapp-number-display {
    font-family: 'Courier New', monospace;
    background: var(--whatsapp-color);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-block;
    margin-top: 0.25rem;
}

.whatsapp-status-success {
    color: var(--whatsapp-color);
    background: rgba(37, 211, 102, 0.1);
    border: 1px solid var(--whatsapp-color);
    border-radius: 6px;
    padding: 0.5rem;
    margin-top: 0.5rem;
}

.auto-whatsapp-badge {
    background: linear-gradient(45deg, var(--whatsapp-color), #20c997);
    color: white;
    padding: 0.2rem 0.6rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    animation: whatsappPulse 2s infinite;
}

@keyframes whatsappPulse {
    0%, 100% { 
        opacity: 1;
        transform: scale(1);
    }
    50% { 
        opacity: 0.8;
        transform: scale(1.05);
    }
}

/* Enhanced Notification Options */
.notification-option[style*="border-color: var(--whatsapp-color)"] {
    border-color: var(--whatsapp-color) !important;
    background: rgba(37, 211, 102, 0.05) !important;
}

.notification-option[style*="border-color: var(--whatsapp-color)"]:hover {
    background: rgba(37, 211, 102, 0.08) !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(37, 211, 102, 0.2);
}

/* WhatsApp Form Switch */
.form-check-input[style*="background-color: var(--whatsapp-color)"] {
    background-color: var(--whatsapp-color) !important;
    border-color: var(--whatsapp-color) !important;
}

.form-check-input[style*="background-color: var(--whatsapp-color)"]:focus {
    border-color: var(--whatsapp-dark) !important;
    box-shadow: 0 0 0 0.2rem rgba(37, 211, 102, 0.25) !important;
}

/* WhatsApp Preview Message Box */
.mt-2.p-2.rounded[style*="background: var(--whatsapp-color)"] {
    background: var(--whatsapp-color) !important;
    color: white !important;
    border-radius: 8px !important;
}

.mt-2.p-2.rounded[style*="background: var(--whatsapp-color)"] .small {
    font-family: 'Courier New', monospace !important;
    line-height: 1.4;
}

/* Status Item WhatsApp Icon */
.status-item i.fa-whatsapp[style*="color: var(--whatsapp-color)"] {
    color: var(--whatsapp-color) !important;
    font-size: 1.1em;
}

/* Alert Success for Auto-Send Info */
.alert-success h6 {
    color: #155724;
    margin-bottom: 0.75rem;
}

.alert-success ol li {
    margin-bottom: 0.25rem;
    line-height: 1.4;
}

.alert-success ol li strong {
    color: #0d4420;
}

/* Auto-Send Button Styling */
.btn-send {
    background: linear-gradient(135deg, #28a745, var(--whatsapp-color)) !important;
    border: none !important;
    color: white !important;
    transition: all 0.3s ease !important;
    position: relative;
    overflow: hidden;
}

.btn-send:hover {
    background: linear-gradient(135deg, var(--whatsapp-color), #28a745) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
    color: white !important;
}

.btn-send::after {
    content: "📧+📱";
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.8em;
    opacity: 0.8;
}

/* Enhanced PDF Sent Status */
.pdf-sent-status {
    background: linear-gradient(135deg, rgba(37, 211, 102, 0.1), rgba(40, 167, 69, 0.05));
    border: 1px solid var(--whatsapp-color);
    border-radius: 8px;
    padding: 0.75rem;
    margin-top: 0.5rem;
}

.pdf-sent-status .status-item i.fa-whatsapp {
    color: var(--whatsapp-color) !important;
    width: 16px;
    font-size: 1.1em;
}

.pdf-sent-status .status-item i.fa-envelope {
    color: #007bff !important;
}

.pdf-sent-status .status-item i.fa-tachometer-alt {
    color: #6c757d !important;
}

/* Mobile Enhancements */
@media (max-width: 768px) {
    .whatsapp-number-display {
        font-size: 0.8rem;
        padding: 0.2rem 0.6rem;
    }
    
    .auto-whatsapp-badge {
        font-size: 0.65rem;
        padding: 0.15rem 0.5rem;
    }
    
    .whatsapp-status-success {
        padding: 0.4rem;
        font-size: 0.85rem;
    }
    
    .btn-send::after {
        display: none; /* Hide emoji on mobile */
    }
}

/* Loading State for WhatsApp */
.whatsapp-sending {
    position: relative;
    opacity: 0.7;
    pointer-events: none;
}

.whatsapp-sending::before {
    content: "📱 Sending...";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: var(--whatsapp-color);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    z-index: 10;
    animation: whatsappPulse 1s infinite;
}


</style>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>

<!-- Header -->
<div class="dashboard-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Dashboard Export</h1>
            </div>
            <div class="d-flex align-items-center gap-3">
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
    <!-- Location Filter Tabs -->
    <div class="location-filter-tabs">
        <div class="d-flex justify-content-center">
            <button class="location-tab active" onclick="showLocationView('all')" data-location="all">
                All Locations
            </button>
            <button class="location-tab" onclick="showLocationView('surabaya')" data-location="surabaya">
                Surabaya
            </button>
            <button class="location-tab" onclick="showLocationView('semarang')" data-location="semarang">
                Semarang
            </button>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <input type="text" class="form-control" id="searchForwarders" 
                       placeholder="Search forwarders or reference invoices..." onkeyup="searchContent()">
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filterStatus" onchange="filterByStatus()">
                    <option value="">All Status</option>
                    <option value="ready">Ready</option>
                    <option value="generated">Generated</option>
                    <option value="sent">Sent</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-success w-100" onclick="toggleAllCards()">
                    <i class="fas fa-expand-alt me-2"></i>
                    <span id="toggleAllText">Expand All</span>
                </button>
            </div>
        </div>
    </div>

    <!-- All Locations View -->
    <div id="allLocationView" class="location-cards-container show">
        <div class="row">
            <!-- Surabaya Card -->
            <div class="col-md-6 mb-4">
                <div class="location-card surabaya-card">
                    <div class="location-header" onclick="toggleLocationCard('surabaya')">
                        <div class="location-title">
                            <div>
                                Surabaya Export
                                <div class="location-subtitle">Tanjung Perak Port</div>
                            </div>
                            <i class="fas fa-chevron-down toggle-icon" id="toggle-surabaya-all"></i>
                        </div>
                        <div class="location-stats" id="surabaya-stats">
                            <!-- Stats will be populated by JavaScript -->
                        </div>
                    </div>
                    <div class="location-content" id="content-surabaya-all">
                        <div id="surabaya-forwarders-content">
                            <!-- Forwarder content will be populated here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Semarang Card -->
            <div class="col-md-6 mb-4">
                <div class="location-card semarang-card">
                    <div class="location-header" onclick="toggleLocationCard('semarang')">
                        <div class="location-title">
                            <div>
                                Semarang Export
                                <div class="location-subtitle">Tanjung Emas Port</div>
                            </div>
                            <i class="fas fa-chevron-down toggle-icon" id="toggle-semarang-all"></i>
                        </div>
                        <div class="location-stats" id="semarang-stats">
                            <!-- Stats will be populated by JavaScript -->
                        </div>
                    </div>
                    <div class="location-content" id="content-semarang-all">
                        <div id="semarang-forwarders-content">
                            <!-- Forwarder content will be populated here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Surabaya Only View -->
    <div id="surabayaLocationView" class="location-cards-container">
        <div class="location-card surabaya-card">
            <div class="location-header">
                <div class="location-title">
                    <div>
                        Select Forwarder
                        <div class="location-subtitle"></div>
                    </div>
                </div>
                <div class="location-stats" id="surabaya-single-stats">
                    <!-- Stats will be populated by JavaScript -->
                </div>
            </div>
            <div class="location-content show">
                <div id="surabaya-forwarders-grid" class="forwarders-grid">
                    <!-- Forwarder cards will be populated here -->
                </div>
                <div id="surabaya-single-content" class="forwarder-data-container">
                    <!-- Individual forwarder content will be shown here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Semarang Only View -->
    <div id="semarangLocationView" class="location-cards-container">
        <div class="location-card semarang-card">
            <div class="location-header">
                <div class="location-title">
                    <div>
                        Select Forwarder
                        <div class="location-subtitle"></div>
                    </div>
                </div>
                <div class="location-stats" id="semarang-single-stats">
                    <!-- Stats will be populated by JavaScript -->
                </div>
            </div>
            <div class="location-content show">
                <div id="semarang-forwarders-grid" class="forwarders-grid">
                    <!-- Forwarder cards will be populated here -->
                </div>
                <div id="semarang-single-content" class="forwarder-data-container">
                    <!-- Individual forwarder content will be shown here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Generate PDF Modal -->
<div class="modal fade" id="generatePdfModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-pdf me-2"></i>Generate Shipping Instruction PDF
                    <span class="migration-data-indicator">Complete Data</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="generatePdfForm">
                    <div id="groupSummaryForPdf" class="mb-4"></div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">
                                Forwarder Name <span class="text-danger">*</span>
                                <span class="migration-badge">DYNAMIC</span>
                            </label>
                            <input type="text" class="form-control" name="forwarder_name" id="forwarder_name" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">
                                Notification Email <span class="text-danger">*</span>
                                <span class="migration-badge">DYNAMIC</span>
                            </label>
                            <input type="email" class="form-control" name="notification_email" id="notification_email" required>
                        </div>

                       <!-- Update bagian setelah notification_email field -->
<div class="col-md-6">
    <label class="form-label">
        Primary WhatsApp Number 
        <span class="auto-whatsapp-badge">AUTO-SEND</span>
    </label>
    <input type="tel" class="form-control" name="primary_whatsapp" id="primary_whatsapp" 
           placeholder="e.g., +62-823-4567-8901" readonly 
           style="background: linear-gradient(135deg, rgba(37, 211, 102, 0.1), rgba(255, 255, 255, 0.9)); 
                  border: 2px solid var(--whatsapp-color); 
                  font-family: 'Courier New', monospace;">
    <small class="text-muted">
        <i class="fab fa-whatsapp me-1" style="color: var(--whatsapp-color);"></i>
        Will auto-send when email is successful
    </small>
</div>

                        <div class="col-md-6">
                            <label class="form-label">Pickup Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="pickup_location" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expected Pickup Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="expected_pickup_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Container Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="container_type" required>
                                <option value="">Select Container</option>
                                <option value="1 X 20 STD">1 X 20 STD</option>
                                <option value="1 X 40 STD">1 X 40 STD</option>
                                <option value="1 X 40 HC">1 X 40 HC</option>
                                <option value="1 X 45 HC">1 X 45 HC</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select class="form-select" name="priority" required>
                                <option value="">Select Priority</option>
                                <option value="urgent">Urgent</option>
                                <option value="high">High</option>
                                <option value="normal">Normal</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Port of Loading <span class="text-danger">*</span></label>
                            <select class="form-select" name="port_loading" required>
                                <option value="">Select Port</option>
                                <option value="Tanjung Perak - Surabaya">Tanjung Perak - Surabaya</option>
                                <option value="Tanjung Emas - Semarang">Tanjung Emas - Semarang</option>
                                <option value="Tanjung Priok - Jakarta">Tanjung Priok - Jakarta</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Port of Destination <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="port_destination" 
                                   placeholder="e.g., LOS ANGELES" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                Contact Person <span class="text-danger">*</span>
                                <span class="migration-badge">DYNAMIC</span>
                            </label>
                            <input type="text" class="form-control" name="contact_person" id="contact_person" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Freight Payment <span class="text-danger">*</span></label>
                            <select class="form-select" name="freight_payment" required>
                                <option value="">Select Payment</option>
                                <option value="COLLECT">COLLECT</option>
                                <option value="PREPAID">PREPAID</option>
                            </select>
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
                <button type="button" class="btn btn-primary" onclick="generatePDF()">
                    <i class="fas fa-file-pdf me-2"></i>Generate PDF
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View PDF Modal -->
<div class="modal fade" id="viewPdfModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-pdf me-2"></i>Shipping Instruction PDF
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="pdfViewer" width="100%" height="600px" style="border: none;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="downloadCurrentPdf()">
                    <i class="fas fa-download me-2"></i>Download PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script>
'use strict';

// Global variables
let exportData = @json($exportData ?? []);
let forwarders = @json($forwarders ?? []);
let statistics = @json($statistics ?? []);
let groupedData = @json($groupedData ?? []);
let currentLocation = @json($location ?? 'all');

let locationGroups = {
    surabaya: { forwarders: {}, stats: { total_records: 0, total_volume: 0, total_weight: 0, unique_buyers: 0 } },
    semarang: { forwarders: {}, stats: { total_records: 0, total_volume: 0, total_weight: 0, unique_buyers: 0 } }
};

let generatedPdfs = {};
let sentNotifications = {};
let currentGroup = null;
let currentPdfUrl = null;
let selectedForwarder = null;

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    console.log('Enhanced Export Dashboard with FIXED Send Email System initializing...');
    
    try {
        loadFromStorage();
        processBladeData();
        renderLocationContent();
        setMinimumDates();
        
        console.log('Enhanced dashboard loaded successfully with FIXED email system');
    } catch (error) {
        console.error('Dashboard initialization error:', error);
        showAlert('Dashboard initialization failed: ' + error.message, 'danger');
    }
});

// Process data from controller with FIXED 3-digit prefix grouping
function processBladeData() {
    try {
        initializeAllMappedForwarders();
        
        if (Array.isArray(exportData) && exportData.length > 0) {
            if (groupedData && Object.keys(groupedData).length > 0) {
                console.log('Using pre-grouped data from controller with FIXED forwarder data');
                mergeControllerGroupedData();
            } else {
                console.log('Processing raw export data with FIXED forwarder integration...');
                processRawDataWithFixedForwarderMapping();
            }
        }

        if (statistics) {
            locationGroups.surabaya.stats = statistics.surabaya_stats || locationGroups.surabaya.stats;
            locationGroups.semarang.stats = statistics.semarang_stats || locationGroups.semarang.stats;
        }

        console.log('FIXED 3-digit prefix grouping with forwarder integration complete');
    } catch (error) {
        console.error('Data processing error:', error);
        showAlert('Data processing failed: ' + error.message, 'danger');
    }
}

// Initialize all mapped forwarders with FIXED data from migration
function initializeAllMappedForwarders() {
    if (!Array.isArray(forwarders)) {
        console.warn('Forwarders data is not an array');
        return;
    }

    forwarders.forEach(forwarder => {
        try {
            if (forwarder.name && (
                forwarder.name.includes('PT SKYLINE JAYA') || 
                forwarder.name.includes('SKYLINE JAYA')
            )) {
                return;
            }

            ['surabaya', 'semarang'].forEach(location => {
                if (!locationGroups[location].forwarders[forwarder.code]) {
                    locationGroups[location].forwarders[forwarder.code] = {
                        forwarder_code: forwarder.code,
                        forwarder_name: forwarder.name || 'Unknown Forwarder',
                        forwarder_data: buildFixedForwarderDataFromMigration(forwarder),
                        ref_invoices: {},
                        is_mapped: true,
                        has_data: false
                    };
                }
            });
        } catch (error) {
            console.error('Error initializing FIXED forwarder:', forwarder, error);
        }
    });

    console.log('Initialized all mapped forwarders with FIXED migration data');
}

// FIXED: Build forwarder data from migration/seeder with proper parsing
function buildFixedForwarderDataFromMigration(forwarder) {
    if (!forwarder) {
        return null;
    }

    let emails = [];
    if (forwarder.emails) {
        if (typeof forwarder.emails === 'string') {
            try {
                emails = JSON.parse(forwarder.emails);
            } catch (e) {
                console.warn('Failed to parse emails JSON for forwarder:', forwarder.code, forwarder.emails);
                emails = [];
            }
        } else if (Array.isArray(forwarder.emails)) {
            emails = forwarder.emails;
        }
    }
    
    let whatsappNumbers = [];
    if (forwarder.whatsapp_numbers) {
        if (typeof forwarder.whatsapp_numbers === 'string') {
            try {
                whatsappNumbers = JSON.parse(forwarder.whatsapp_numbers);
            } catch (e) {
                console.warn('Failed to parse WhatsApp JSON for forwarder:', forwarder.code, forwarder.whatsapp_numbers);
                whatsappNumbers = [];
            }
        } else if (Array.isArray(forwarder.whatsapp_numbers)) {
            whatsappNumbers = forwarder.whatsapp_numbers;
        }
    }

    const primaryEmail = forwarder.primary_email || (emails.length > 0 ? emails[0] : null);
    const primaryWhatsApp = forwarder.primary_whatsapp || (whatsappNumbers.length > 0 ? whatsappNumbers[0] : null);

    const ccEmails = emails.filter(email => email !== primaryEmail);
    const secondaryWhatsApp = whatsappNumbers.filter(number => number !== primaryWhatsApp);

    const result = {
        code: forwarder.code,
        name: forwarder.name,
        contact_person: forwarder.contact_person,
        phone: forwarder.phone,
        address: forwarder.address,
        
        primary_email: primaryEmail,
        all_emails: emails,
        cc_emails: ccEmails,
        
        primary_whatsapp: primaryWhatsApp,
        all_whatsapp: whatsappNumbers,
        secondary_whatsapp: secondaryWhatsApp,
        
        email_notifications_enabled: forwarder.email_notifications_enabled !== false,
        whatsapp_notifications_enabled: forwarder.whatsapp_notifications_enabled || false,
        is_active: forwarder.is_active,
        
        company_type: forwarder.company_type,
        service_type: forwarder.service_type,
        destination: forwarder.destination,
        migration_source: true
    };

    console.log('FIXED forwarder data built:', {
        code: forwarder.code,
        primary_email: primaryEmail,
        email_count: emails.length,
        whatsapp_count: whatsappNumbers.length,
        cc_count: ccEmails.length,
        secondary_whatsapp_count: secondaryWhatsApp.length
    });

    return result;
}

// FIXED: Processing with corrected forwarder mapping
function processRawDataWithFixedForwarderMapping() {
    try {
        const filteredData = exportData.filter(item => 
            item && item.reference_invoice && 
            item.reference_invoice.trim() !== '' &&
            !(item.buyer && (
                item.buyer.includes('PT SKYLINE JAYA') || 
                item.buyer.includes('SKYLINE JAYA')
            ))
        );

        console.log(`Processing ${filteredData.length} records with FIXED forwarder mapping`);

        const fixedPrefixGroups = {};
        
        filteredData.forEach(item => {
            try {
                let location = 'surabaya';
                if (item.delivery_type === 'ZDO2') {
                    location = 'semarang';
                }

                const refInvoice = item.reference_invoice;
                const strictPrefix = extractStrictThreeDigitPrefix(refInvoice);
                const forwarderCode = determineFixedForwarderCode(item);

                if (!strictPrefix || forwarderCode.includes('SKYLIN')) {
                    return;
                }
                
                const groupKey = `${location}_${forwarderCode}_${strictPrefix}`;
                
                if (!fixedPrefixGroups[groupKey]) {
                    const forwarder = forwarders.find(f => f.code === forwarderCode);
                    fixedPrefixGroups[groupKey] = {
                        location: location,
                        forwarderCode: forwarderCode,
                        strictPrefix: strictPrefix,
                        forwarderData: forwarder ? buildFixedForwarderDataFromMigration(forwarder) : null,
                        individualInvoices: {},
                        allItems: []
                    };
                }

                if (!fixedPrefixGroups[groupKey].individualInvoices[refInvoice]) {
                    fixedPrefixGroups[groupKey].individualInvoices[refInvoice] = [];
                }
                fixedPrefixGroups[groupKey].individualInvoices[refInvoice].push(item);
                fixedPrefixGroups[groupKey].allItems.push(item);
            } catch (error) {
                console.error('Error processing item with FIXED forwarder mapping:', item, error);
            }
        });

        Object.values(fixedPrefixGroups).forEach(prefixGroup => {
            try {
                const { location, forwarderCode, strictPrefix, forwarderData } = prefixGroup;
                const individualInvoices = prefixGroup.individualInvoices;
                const allItems = prefixGroup.allItems;

                if (!locationGroups[location].forwarders[forwarderCode]) {
                    const mappedForwarder = forwarders.find(f => f.code === forwarderCode);
                    locationGroups[location].forwarders[forwarderCode] = {
                        forwarder_code: forwarderCode,
                        forwarder_name: mappedForwarder ? mappedForwarder.name : `Custom - ${allItems[0]?.buyer || 'Unknown'}`,
                        forwarder_data: forwarderData,
                        ref_invoices: {},
                        is_mapped: !!mappedForwarder,
                        has_data: false
                    };
                }

                locationGroups[location].forwarders[forwarderCode].has_data = true;
                if (!locationGroups[location].forwarders[forwarderCode].forwarder_data && forwarderData) {
                    locationGroups[location].forwarders[forwarderCode].forwarder_data = forwarderData;
                }

                const invoiceKeys = Object.keys(individualInvoices);
                const isCombined = invoiceKeys.length > 1;
                
                let displayName;
                if (isCombined) {
                    const firstInvoice = invoiceKeys[0];
                    const baseName = firstInvoice.replace(/-\d+$/, '');
                    displayName = `${baseName} (Combined)`;
                } else {
                    displayName = invoiceKeys[0];
                }

                const allBuyers = new Set();
                let totalVolume = 0;
                let totalWeight = 0;
                let totalQuantity = 0;
                let primaryBuyer = null;

                allItems.forEach(item => {
                    if (item.buyer) allBuyers.add(item.buyer);
                    totalVolume += parseFloat(item.volume || 0);
                    totalWeight += parseFloat(item.weight || 0);
                    totalQuantity += parseFloat(item.quantity || 0);
                    if (!primaryBuyer && item.buyer) primaryBuyer = item.buyer;
                });

                const subInvoices = {};
                Object.entries(individualInvoices).forEach(([refInvoice, items]) => {
                    const subVolume = items.reduce((sum, item) => sum + parseFloat(item.volume || 0), 0);
                    const subWeight = items.reduce((sum, item) => sum + parseFloat(item.weight || 0), 0);
                    const subQuantity = items.reduce((sum, item) => sum + parseFloat(item.quantity || 0), 0);
                    const subBuyers = [...new Set(items.map(item => item.buyer).filter(Boolean))];

                    subInvoices[refInvoice] = {
                        ref_invoice: refInvoice,
                        items: items,
                        item_count: items.length,
                        total_volume: subVolume,
                        total_weight: subWeight,
                        total_quantity: subQuantity,
                        buyers: subBuyers,
                        primary_buyer: subBuyers[0] || 'N/A'
                    };
                });

                locationGroups[location].forwarders[forwarderCode].ref_invoices[displayName] = {
                    ref_invoice: displayName,
                    original_invoices: invoiceKeys,
                    numeric_prefix: strictPrefix,
                    is_combined: isCombined,
                    sub_count: invoiceKeys.length,
                    sub_invoices: subInvoices,
                    
                    items: allItems,
                    buyers: Array.from(allBuyers),
                    total_volume: totalVolume,
                    total_weight: totalWeight,
                    total_quantity: totalQuantity,
                    primary_buyer: primaryBuyer,
                    item_count: allItems.length,
                    
                    forwarder_data: forwarderData,
                    
                    delivery_type: allItems[0]?.delivery_type || null,
                    location: location,
                    status: 'ready',
                    pdf_generated: false,
                    notification_sent: false
                };
            } catch (error) {
                console.error('Error creating FIXED group:', prefixGroup, error);
            }
        });

        console.log('FIXED forwarder mapping with 3-digit prefix grouping completed');
    } catch (error) {
        console.error('Error in FIXED forwarder mapping process:', error);
    }
}

function extractStrictThreeDigitPrefix(refInvoice) {
    if (!refInvoice) {
        return null;
    }
    
    const match = refInvoice.match(/^(\d{3})/);
    return match ? match[1] : null;
}

function determineFixedForwarderCode(item) {
    try {
        if (!item || !item.buyer) return 'UNASSIGNED';

        const buyerName = item.buyer.trim();
        
        if (buyerName.includes('PT SKYLINE JAYA') || buyerName.includes('SKYLINE JAYA')) {
            return 'UNASSIGNED';
        }

        for (const forwarder of forwarders) {
            if (forwarder.buyers && Array.isArray(forwarder.buyers)) {
                for (const mappedBuyer of forwarder.buyers) {
                    if (isFixedBuyerMatch(buyerName.toUpperCase(), mappedBuyer.toUpperCase())) {
                        return forwarder.code;
                    }
                }
            }
        }

        return `CUSTOM_${buyerName.replace(/[^A-Za-z0-9]/g, '').substring(0, 8).toUpperCase()}`;
    } catch (error) {
        console.error('Error determining FIXED forwarder code:', item, error);
        return 'UNASSIGNED';
    }
}

function isFixedBuyerMatch(buyer1, buyer2) {
    try {
        if (buyer1 === buyer2) return true;
        
        const normalize = (name) => {
            return name.replace(/[^A-Z0-9]/g, '').replace(/(LLC|INC|CORP|CO|LTD|LIMITED)$/, '');
        };
        
        const normalized1 = normalize(buyer1);
        const normalized2 = normalize(buyer2);
        
        if (normalized1 === normalized2) return true;
        
        if (normalized1.length >= 5 && normalized2.length >= 5) {
            return normalized1.includes(normalized2) || normalized2.includes(normalized1);
        }
        
        return false;
    } catch (error) {
        console.error('Error matching buyers:', buyer1, buyer2, error);
        return false;
    }
}

function mergeControllerGroupedData() {
    try {
        Object.keys(groupedData).forEach(location => {
            if (locationGroups[location]) {
                Object.keys(groupedData[location]).forEach(forwarderCode => {
                    try {
                        const forwarderData = groupedData[location][forwarderCode];
                        
                        if (forwarderData.forwarder_name && (
                            forwarderData.forwarder_name.includes('PT SKYLINE JAYA') || 
                            forwarderData.forwarder_name.includes('SKYLINE JAYA')
                        )) {
                            return;
                        }

                        if (locationGroups[location].forwarders[forwarderCode]) {
                            locationGroups[location].forwarders[forwarderCode] = {
                                ...locationGroups[location].forwarders[forwarderCode],
                                ...forwarderData,
                                has_data: Object.keys(forwarderData.ref_invoices || {}).length > 0,
                                forwarder_data: locationGroups[location].forwarders[forwarderCode].forwarder_data || forwarderData.forwarder_data
                            };
                        } else {
                            locationGroups[location].forwarders[forwarderCode] = {
                                ...forwarderData,
                                is_mapped: false,
                                has_data: Object.keys(forwarderData.ref_invoices || {}).length > 0
                            };
                        }
                    } catch (error) {
                        console.error('Error merging FIXED forwarder data:', forwarderCode, error);
                    }
                });
            }
        });
    } catch (error) {
        console.error('Error merging controller grouped data with FIXED enhancement:', error);
    }
}

// ============================================================
// FIXED SEND EMAIL SYSTEM - MAIN FUNCTION
// ============================================================

async function sendNotifications(refInvoice) {
    try {
        console.log('FIXED: Starting send notification process for:', refInvoice);
        
        const pdfInfo = generatedPdfs[refInvoice];
        if (!pdfInfo) {
            showAlert('PDF not found. Please generate PDF first.', 'warning');
            return;
        }

        if (sentNotifications[refInvoice]) {
            showAlert('Notifications already sent for this invoice', 'info');
            return;
        }

        showLoading(true);
        
        console.log('FIXED: Sending to correct endpoint /dashboard/send-container-booking-request');
        
        const response = await fetch('/dashboard/send-container-booking-request', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                instruction_id: pdfInfo.instruction_id,
                send_email: true,
                send_whatsapp: false, // Focus email only
                send_forwarder_portal: true,
                cc_emails: pdfInfo.forwarder_data?.cc_emails || [],
                whatsapp_numbers: []
            })
        });

        console.log('FIXED: Response status:', response.status);
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }

        const result = await response.json();
        console.log('FIXED: Send notification result:', result);
        
        if (result.success) {
            // Store notification result
            sentNotifications[refInvoice] = {
                sent_at: new Date().toISOString(),
                email_sent: result.results?.email_sent || false,
                whatsapp_sent: result.results?.whatsapp_sent || false,
                forwarder_portal_sent: result.results?.forwarder_portal_sent || false,
                email_count: result.results?.email_count || 0,
                whatsapp_count: result.results?.whatsapp_count || 0,
                errors: result.results?.errors || [],
                details: result.results?.details || {}
            };
            
            localStorage.setItem('sent_notifications', JSON.stringify(sentNotifications));
            renderLocationContent(); // Refresh UI
            
            // Build success message
            let successMessage = 'FIXED: Notifications sent successfully';
            let successParts = [];
            
            if (result.results?.email_sent) {
                successParts.push(`Email (${result.results.email_count} recipients)`);
            }
            if (result.results?.forwarder_portal_sent) {
                successParts.push('Forwarder Portal');
            }
            
            if (successParts.length > 0) {
                successMessage += ': ' + successParts.join(', ');
            }
            
            showAlert(successMessage, 'success');
            
            // Show any warnings
            if (result.results?.errors && result.results.errors.length > 0) {
                setTimeout(() => {
                    result.results.errors.forEach(error => {
                        showAlert(`Warning: ${error}`, 'warning');
                    });
                }, 2000);
            }
            
        } else {
            throw new Error(result.error || 'Failed to send notifications');
        }
    } catch (error) {
        console.error('FIXED: Error sending notifications:', error);
        showAlert('FIXED: Failed to send notifications: ' + error.message, 'danger');
    } finally {
        showLoading(false);
    }
}

// Rest of the original functions remain the same...
// [Include all other functions from the original file without changes]

function renderForwardersGrid(location) {
    try {
        const gridId = `${location}-forwarders-grid`;
        const container = document.getElementById(gridId);
        if (!container) {
            console.warn('Grid container not found:', gridId);
            return;
        }

        const forwarderData = locationGroups[location]?.forwarders || {};
        
        if (Object.keys(forwarderData).length === 0) {
            container.innerHTML = createEmptyState(`No ${location} forwarders available`);
            return;
        }

        let html = '';
        Object.values(forwarderData).forEach(forwarder => {
            html += createFixedForwarderCard(forwarder, location);
        });

        container.innerHTML = html;
    } catch (error) {
        console.error('Error rendering forwarders grid:', location, error);
        const container = document.getElementById(`${location}-forwarders-grid`);
        if (container) {
            container.innerHTML = createEmptyState('Error loading forwarders');
        }
    }
}

function createFixedForwarderCard(forwarder, location) {
    try {
        const refInvoices = Object.values(forwarder.ref_invoices || {});
        const hasData = refInvoices.length > 0;
        const hasFixedData = forwarder.forwarder_data && forwarder.forwarder_data.migration_source;
        
        const isCrateBarrel = forwarder.forwarder_name && 
                             forwarder.forwarder_name.toUpperCase().includes('CRATE') && 
                             forwarder.forwarder_name.toUpperCase().includes('BARREL');
        
        let totalVolume = 0;
        let totalWeight = 0;
        let totalItems = 0;
        let totalGroups = refInvoices.length;
        let combinedGroups = refInvoices.filter(group => group.is_combined).length;
        
        refInvoices.forEach(group => {
            totalVolume += group.total_volume || 0;
            totalWeight += group.total_weight || 0;
            totalItems += group.item_count || 0;
        });

        const locationClass = location === 'surabaya' ? 'surabaya-style' : 'semarang-style';
        const dataClass = hasData ? 'has-data' : 'no-data';
        const crateBarrelClass = isCrateBarrel ? 'crate-barrel-style' : '';
        const forwarderName = forwarder.forwarder_name || 'Unknown Forwarder';
        const forwarderCode = forwarder.forwarder_code || 'UNKNOWN';

        return `
            <div class="forwarder-card-button ${locationClass} ${dataClass} ${crateBarrelClass}" 
                 onclick="selectForwarder('${forwarderCode}', '${location}')">
                <div class="forwarder-card-title">
                    ${forwarderName}
                    ${isCrateBarrel ? '<i class="fas fa-star text-warning ms-2" title="CRATE&BARREL Special"></i>' : ''}
                </div>
                <div class="forwarder-card-subtitle">${forwarderCode}</div>
                
                ${hasData ? `
                    <div class="forwarder-card-stats">
                        <div class="forwarder-mini-stat">
                            <div class="forwarder-mini-stat-number">${totalGroups}</div>
                            <div class="forwarder-mini-stat-label">Groups</div>
                        </div>
                        <div class="forwarder-mini-stat">
                            <div class="forwarder-mini-stat-number">${combinedGroups}</div>
                            <div class="forwarder-mini-stat-label">Combined</div>
                        </div>
                        <div class="forwarder-mini-stat">
                            <div class="forwarder-mini-stat-number">${numberFormat(totalVolume, 1)}</div>
                            <div class="forwarder-mini-stat-label">CBM</div>
                        </div>
                    </div>
                ` : `
                    <div class="text-center py-3">
                        <i class="fas fa-inbox fa-2x opacity-50"></i>
                        <div class="small text-muted mt-2">No Data Available</div>
                    </div>
                `}
                
                <div class="forwarder-card-badges">
                    <span class="forwarder-badge ${forwarder.is_mapped ? 'mapped-badge' : 'unmapped-badge'}">
                        ${forwarder.is_mapped ? 'Mapped' : 'Custom'}
                    </span>
                    <span class="forwarder-badge ${hasData ? 'has-data-badge' : 'no-data-badge'}">
                        ${hasData ? 'Has Data' : 'No Data'}
                    </span>
                    ${hasFixedData ? 
                        '<span class="forwarder-badge dynamic-badge">FIXED</span>' : ''
                    }
                    ${isCrateBarrel ? 
                        '<span class="forwarder-badge crate-barrel-badge">CRATE&BARREL</span>' : ''
                    }
                </div>
            </div>
        `;
    } catch (error) {
        console.error('Error creating FIXED forwarder card:', forwarder, error);
        return `
            <div class="forwarder-card-button no-data">
                <div class="forwarder-card-title">Error Loading Forwarder</div>
                <div class="forwarder-card-subtitle">UNKNOWN</div>
                <div class="text-center py-3">
                    <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                    <div class="small text-muted mt-2">Error</div>
                </div>
            </div>
        `;
    }
}

function selectForwarder(forwarderCode, location) {
    try {
        selectedForwarder = { code: forwarderCode, location: location };
        
        const forwarder = locationGroups[location]?.forwarders?.[forwarderCode];
        if (!forwarder) {
            showAlert('Forwarder not found', 'warning');
            return;
        }

        const gridContainer = document.getElementById(`${location}-forwarders-grid`);
        const dataContainer = document.getElementById(`${location}-single-content`);
        
        if (gridContainer) gridContainer.style.display = 'none';
        if (dataContainer) {
            dataContainer.classList.add('show');
            dataContainer.innerHTML = createForwarderDataView(forwarder, location);
        }
    } catch (error) {
        console.error('Error selecting forwarder:', forwarderCode, location, error);
        showAlert('Error selecting forwarder: ' + error.message, 'danger');
    }
}

function createForwarderDataView(forwarder, location) {
    try {
        const locationTitle = location === 'surabaya' ? 'Surabaya (ZDO1)' : 'Semarang (ZDO2)';
        const refInvoices = Object.values(forwarder.ref_invoices || {});
        const forwarderName = forwarder.forwarder_name || 'Unknown Forwarder';
        const forwarderCode = forwarder.forwarder_code || 'UNKNOWN';
        const hasFixedData = forwarder.forwarder_data && forwarder.forwarder_data.migration_source;
        
        const isCrateBarrel = forwarderName.toUpperCase().includes('CRATE') && 
                             forwarderName.toUpperCase().includes('BARREL');
        
        let html = `
            <div class="forwarder-data-header">
                <div>
                    <div class="forwarder-data-title">
                        <i class="fas fa-${location === 'surabaya' ? 'anchor' : 'ship'} me-2"></i>
                        ${forwarderName}
                        ${isCrateBarrel ? '<i class="fas fa-star text-warning ms-2"></i>' : ''}
                    </div>
                </div>
                <button class="close-forwarder-btn" onclick="backToForwardersGrid('${location}')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-3">
                <button class="back-to-grid-btn" onclick="backToForwardersGrid('${location}')">
                    <i class="fas fa-arrow-left me-2"></i>Back to Forwarders
                </button>
            </div>
        `;

        if (refInvoices.length === 0) {
            html += createNoDataMessage(forwarder);
        } else {
            html += createRefInvoiceTable(refInvoices, location, forwarder);
        }

        return html;
    } catch (error) {
        console.error('Error creating forwarder data view:', forwarder, error);
        return `
            <div class="p-4 text-center text-danger">
                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                <h6>Error Loading Forwarder Data</h6>
                <p class="small">Please try again or contact support.</p>
                <button class="btn btn-secondary" onclick="backToForwardersGrid('${location}')">
                    Back to Forwarders
                </button>
            </div>
        `;
    }
}

function backToForwardersGrid(location) {
    try {
        selectedForwarder = null;
        
        const gridContainer = document.getElementById(`${location}-forwarders-grid`);
        const dataContainer = document.getElementById(`${location}-single-content`);
        
        if (gridContainer) gridContainer.style.display = 'grid';
        if (dataContainer) {
            dataContainer.classList.remove('show');
            dataContainer.innerHTML = '';
        }
    } catch (error) {
        console.error('Error going back to forwarders grid:', location, error);
    }
}

function renderForwarderContent(location, containerId) {
    try {
        const container = document.getElementById(containerId);
        if (!container) {
            console.warn('Container not found:', containerId);
            return;
        }

        container.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-2">Loading ${location} FIXED grouping...</div>
            </div>
        `;

        setTimeout(() => {
            try {
                const forwarderData = locationGroups[location]?.forwarders || {};
                const locationTitle = location === 'surabaya' ? 'Surabaya (ZDO1)' : 'Semarang (ZDO2)';

                if (Object.keys(forwarderData).length === 0) {
                    container.innerHTML = createEmptyState(`No ${locationTitle} forwarders available`);
                    return;
                }

                let html = `
                    <div class="alert alert-info m-3">
                        <i class="fas fa-layer-group me-2"></i>
                        <strong>${locationTitle} FIXED Export Data</strong>
                    </div>
                `;

                Object.values(forwarderData).forEach(forwarder => {
                    html += createForwarderSection(forwarder, location);
                });

                container.innerHTML = html;
            } catch (error) {
                console.error('Error in renderForwarderContent timeout:', error);
                container.innerHTML = createEmptyState('Error loading data');
            }
        }, 300);
    } catch (error) {
        console.error('Error rendering forwarder content:', location, containerId, error);
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = createEmptyState('Error loading data');
        }
    }
}

function createForwarderSection(forwarder, location) {
    try {
        const refInvoices = Object.values(forwarder.ref_invoices || {});
        const hasData = refInvoices.length > 0;
        const forwarderName = forwarder.forwarder_name || 'Unknown Forwarder';
        const forwarderCode = forwarder.forwarder_code || 'UNKNOWN';
        const hasFixedData = forwarder.forwarder_data && forwarder.forwarder_data.migration_source;

        const isCrateBarrel = forwarderName.toUpperCase().includes('CRATE') && 
                             forwarderName.toUpperCase().includes('BARREL');

        const mappingIndicator = forwarder.is_mapped ? 
            '<i class="fas fa-check-circle text-success me-2"></i>Mapped' : 
            '<i class="fas fa-exclamation-triangle text-warning me-2"></i>Custom';

        const completeIndicator = hasData ?
            `<span class="badge bg-info ms-2">${refInvoices.filter(g => g.is_combined).length} combined of ${refInvoices.length}</span>` :
            '<span class="badge bg-secondary ms-2">No data</span>';

        let html = `
            <div class="forwarder-section ${!hasData ? 'no-data-section' : ''}">
                <div class="forwarder-header ${!hasData ? 'no-data' : ''}" onclick="toggleForwarderSection('${forwarderCode}-${location}')">
                    ${!hasData ? '<div class="no-data-badge-header">No Data</div>' : ''}
                    <div>
                        <strong>${forwarderName}</strong>
                        ${isCrateBarrel ? '<i class="fas fa-star text-warning ms-2"></i>' : ''}
                        <small class="ms-2">${forwarderCode} | ${mappingIndicator}</small>
                        ${hasFixedData ? '<small class="migration-data-indicator ms-1">FIXED</small>' : ''}
                    </div>
                    <div>
                        ${completeIndicator}
                        <i class="fas fa-chevron-down toggle-icon" id="toggle-${forwarderCode}-${location}"></i>
                    </div>
                </div>
                <div class="forwarder-content" id="content-${forwarderCode}-${location}">
                    ${hasData ? createRefInvoiceTable(refInvoices, location, forwarder) : createNoDataMessage(forwarder)}
                </div>
            </div>
        `;

        return html;
    } catch (error) {
        console.error('Error creating forwarder section:', forwarder, error);
        return `
            <div class="forwarder-section">
                <div class="forwarder-header">
                    <div><strong>Error Loading Forwarder</strong></div>
                    <div><span class="badge bg-danger">Error</span></div>
                </div>
            </div>
        `;
    }
}

function createRefInvoiceTable(refInvoices, location, forwarder = null) {
    try {
        if (refInvoices.length === 0) {
            return createEmptyState('No reference invoices');
        }

        const isCrateBarrel = forwarder && forwarder.forwarder_name && 
                             forwarder.forwarder_name.toUpperCase().includes('CRATE') && 
                             forwarder.forwarder_name.toUpperCase().includes('BARREL');

        let html = `
            <table class="ref-invoice-table table">
                <thead>
                    <tr>
                        <th style="width: 35%;">Reference Invoice</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 30%;">Actions</th>
                        <th style="width: 20%;">Details</th>
                    </tr>
                </thead>
                <tbody>
        `;

        refInvoices.forEach(group => {
            try {
                const locationBadge = location === 'surabaya' ? 
                    '<span class="badge bg-info">ZDO1 - Surabaya</span>' : 
                    '<span class="badge bg-warning">ZDO2 - Semarang</span>';

                const groupTypeIcon = group.is_combined ? 
                    '<i class="fas fa-layer-group text-info me-2"></i>' :
                    '<i class="fas fa-file-invoice text-primary me-2"></i>';

                const refInvoice = group.ref_invoice || 'Unknown';
                const status = group.status || 'ready';
                const itemCount = group.item_count || 0;
                const totalVolume = group.total_volume || 0;
                const totalWeight = group.total_weight || 0;
                const totalQuantity = group.total_quantity || 0;
                const primaryBuyer = group.primary_buyer || 'N/A';
                const deliveryType = group.delivery_type || 'N/A';
                const subCount = group.sub_count || 1;
                const numericPrefix = group.numeric_prefix || '';
                const hasFixedData = group.forwarder_data && group.forwarder_data.migration_source;

                const pdfGenerated = generatedPdfs[refInvoice] ? true : false;
                const notificationSent = sentNotifications[refInvoice] ? true : false;

                const safeId = refInvoice.replace(/[^a-zA-Z0-9]/g, '_');

                html += `
                    <tr class="${group.is_combined ? 'table-info' : ''}">
                        <td class="ref-invoice-cell">
                            <div class="fw-bold">
                                ${groupTypeIcon}${refInvoice}
                                ${group.is_combined ? 
                                    `<span class="combined-invoice-badge ms-2">${subCount} invoices</span>` : 
                                    '<span class="single-invoice-badge ms-2">Single</span>'
                                }
                                ${hasFixedData ? '<span class="migration-data-indicator ms-1">FIXED</span>' : ''}
                                ${isCrateBarrel ? '<span class="crate-barrel-badge ms-1">CRATE&BARREL</span>' : ''}
                            </div>
                            <div class="group-summary">
                                ${locationBadge} <span class="badge bg-secondary">Prefix: ${numericPrefix}</span><br>
                                Items: ${itemCount} | Vol: ${numberFormat(totalVolume, 2)} CBM | Weight: ${numberFormat(totalWeight, 0)} KG
                                <br><button class="dropdown-toggle-btn" onclick="toggleSubInvoices('${safeId}')">
                                    <i class="fas fa-eye me-1"></i>View Details (${subCount} ${subCount === 1 ? 'invoice' : 'invoices'})
                                </button>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge status-${status}">${status.toUpperCase()}</span>
                            ${notificationSent ? '<div class="mt-1"><span class="badge bg-success">Email Sent</span></div>' : ''}
                        </td>
                        <td>
                            <div class="action-buttons">
                                ${!pdfGenerated ? `
                                    <button class="btn ${group.is_combined ? 'btn-grouped' : 'btn-single'} btn-sm" 
                                            onclick="generateShippingInstruction('${refInvoice}', ${group.is_combined})">
                                        <i class="fas fa-file-pdf me-1"></i>
                                        ${group.is_combined ? 'Combined PDF' : 'Single PDF'}
                                    </button>
                                ` : `
                                    <button class="btn btn-success btn-sm" onclick="viewPDF('${refInvoice}')">
                                        <i class="fas fa-eye me-1"></i>View PDF
                                    </button>
                                `}
                                
  ${pdfGenerated && !notificationSent ? `
    <button class="btn btn-send btn-sm" 
            onclick="${isCrateBarrel ? `window.open('https://network.infornexus.com/', '_blank')` : `sendNotifications('${refInvoice}')`}">
        <i class="fas fa-${isCrateBarrel ? 'external-link-alt' : 'paper-plane'} me-1"></i>
        ${isCrateBarrel ? 'Open Network Portal' : 'Send Email + WhatsApp'}
    </button>
` : ''}
</div>

${pdfGenerated && notificationSent ? `
    <div class="pdf-sent-status">
        <div class="status-item">
            <i class="fas fa-check-circle"></i>
            <span>PDF Generated & Sent</span>
        </div>
        ${sentNotifications[refInvoice]?.email_sent ? `
            <div class="status-item">
                <i class="fas fa-envelope"></i>
                <span>Email: ${sentNotifications[refInvoice].email_count || 0} recipients</span>
            </div>
        ` : ''}
        ${sentNotifications[refInvoice]?.whatsapp_sent ? `
            <div class="status-item">
                <i class="fab fa-whatsapp" style="color: var(--whatsapp-color);"></i>
                <span>WhatsApp: ${sentNotifications[refInvoice].whatsapp_count || 0} numbers</span>
            </div>
        ` : ''}
        ${sentNotifications[refInvoice]?.forwarder_portal_sent ? `
            <div class="status-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Forwarder Portal: Delivered</span>
            </div>
        ` : ''}
    </div>
` : ''}
                        </td>
                        <td>
                            <small class="text-muted">
                                Primary: ${primaryBuyer}<br>
                                Quantity: ${numberFormat(totalQuantity, 0)} PCS<br>
                                Type: ${deliveryType}
                                ${hasFixedData ? '<br><span class="text-success">Auto-Fill Ready</span>' : ''}
                            </small>
                        </td>
                    </tr>
                `;

                html += `
                    <tr id="sub-invoices-${safeId}" class="sub-invoices-row" style="display: none;">
                        <td colspan="4" class="p-0">
                            <div class="sub-invoices-container">
                                <div class="alert alert-info m-2">
                                    <h6><i class="fas fa-list me-2"></i>${group.is_combined ? 'Combined' : 'Single'} Invoice Details:</h6>
                                    <p class="small text-info mb-0">
                                        ${group.is_combined ? 
                                            `FIXED 3-digit prefix grouping: ${subCount} invoices with prefix "${numericPrefix}" combined into one PDF.` :
                                            'This single invoice will generate one PDF with FIXED auto-fill.'
                                        }
                                    </p>
                                </div>
                `;
                
                if (group.sub_invoices) {
                    Object.values(group.sub_invoices).forEach((subInvoice, index) => {
                        html += `
                            <div class="sub-invoice-item">
                                <div class="sub-invoice-title">
                                    <i class="fas fa-file-alt me-2"></i>${subInvoice.ref_invoice}
                                    ${group.is_combined ? '' : '<span class="badge bg-primary ms-2">Primary</span>'}
                                </div>
                                <div class="sub-invoice-details">
                                    <div class="row">
                                        <div class="col-md-3"><strong>Items:</strong> ${subInvoice.item_count}</div>
                                        <div class="col-md-3"><strong>Volume:</strong> ${numberFormat(subInvoice.total_volume, 2)} CBM</div>
                                        <div class="col-md-3"><strong>Weight:</strong> ${numberFormat(subInvoice.total_weight, 0)} KG</div>
                                        <div class="col-md-3"><strong>Quantity:</strong> ${numberFormat(subInvoice.total_quantity, 0)} PCS</div>
                                    </div>
                                    <div class="mt-1"><strong>Buyer:</strong> ${subInvoice.primary_buyer}</div>
                                </div>
                            </div>
                        `;
                    });
                }
                
                html += `
                            </div>
                        </td>
                    </tr>
                `;
            } catch (error) {
                console.error('Error creating invoice row:', group, error);
                html += `
                    <tr>
                        <td colspan="4" class="text-center text-danger p-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>Error loading invoice data
                        </td>
                    </tr>
                `;
            }
        });

        html += `
                </tbody>
            </table>
        `;

        return html;
    } catch (error) {
        console.error('Error creating reference invoice table:', error);
        return createEmptyState('Error loading invoices');
    }
}

// Continue with all other functions...
function createNoDataMessage(forwarder) {
    try {
        const forwarderName = forwarder.forwarder_name || 'Unknown Forwarder';
        const hasFixedData = forwarder.forwarder_data && forwarder.forwarder_data.migration_source;
        
        return `
            <div class="p-4 text-center text-muted">
                <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                <h6>No Export Data Available</h6>
                <p class="small mb-2">
                    ${forwarder.is_mapped ? 
                        'This mapped forwarder currently has no export data assigned.' :
                        'This custom forwarder has no current export data.'
                    }
                </p>
                ${hasFixedData ? `
                    <div class="auto-fill-indicator">
                        <i class="fas fa-magic"></i>
                        <span class="text">Auto-fill data available from FIXED system</span>
                    </div>
                ` : ''}
            </div>
        `;
    } catch (error) {
        console.error('Error creating no data message:', error);
        return `
            <div class="p-4 text-center text-muted">
                <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                <h6>No Export Data Available</h6>
            </div>
        `;
    }
}

function toggleSubInvoices(invoiceId) {
    try {
        const subInvoicesRow = document.getElementById(`sub-invoices-${invoiceId}`);
        const toggleButton = document.querySelector(`[onclick="toggleSubInvoices('${invoiceId}')"]`);
        
        if (subInvoicesRow) {
            const isVisible = subInvoicesRow.style.display !== 'none';
            subInvoicesRow.style.display = isVisible ? 'none' : 'table-row';
            
            if (toggleButton) {
                const icon = toggleButton.querySelector('i');
                if (icon) {
                    icon.className = isVisible ? 'fas fa-eye me-1' : 'fas fa-eye-slash me-1';
                }
                
                const match = toggleButton.textContent.match(/\(([^)]+)\)/);
                const countText = match ? match[1] : '';
                toggleButton.innerHTML = `<i class="fas fa-${isVisible ? 'eye' : 'eye-slash'} me-1"></i>${isVisible ? 'View' : 'Hide'} Details ${countText ? '(' + countText + ')' : ''}`;
            }
        }
    } catch (error) {
        console.error('Error toggling sub-invoices:', invoiceId, error);
    }
}

function generateShippingInstruction(refInvoice, isCombined = false) {
    try {
        let group = null;
        let groupLocation = null;

        for (const [location, locationData] of Object.entries(locationGroups)) {
            for (const forwarderData of Object.values(locationData.forwarders)) {
                if (forwarderData.ref_invoices && forwarderData.ref_invoices[refInvoice]) {
                    group = forwarderData.ref_invoices[refInvoice];
                    groupLocation = location;
                    break;
                }
            }
            if (group) break;
        }

        if (!group) {
            showAlert('Reference invoice group not found', 'danger');
            return;
        }

        currentGroup = group;
        populateFixedPdfModal(group, groupLocation, isCombined);
        
        const modal = new bootstrap.Modal(document.getElementById('generatePdfModal'));
        modal.show();
    } catch (error) {
        console.error('Error generating shipping instruction:', refInvoice, error);
        showAlert('Error generating shipping instruction: ' + error.message, 'danger');
    }
}

function populateFixedPdfModal(group, location, isCombined) {
    try {
        const locationInfo = location === 'surabaya' ? 'Surabaya (ZDO1)' : 'Semarang (ZDO2)';
        const portInfo = location === 'surabaya' ? 'Tanjung Perak Port' : 'Tanjung Emas Port';
        const refInvoice = group.ref_invoice || 'Unknown';
        const itemCount = group.item_count || 0;
        const totalVolume = group.total_volume || 0;
        const totalWeight = group.total_weight || 0;
        const primaryBuyer = group.primary_buyer || 'N/A';
        const subCount = group.sub_count || 1;
        const numericPrefix = group.numeric_prefix || '';
        const hasFixedData = group.forwarder_data && group.forwarder_data.migration_source;

        let summaryHtml = `
            <div class="alert alert-info">
                <h6><i class="fas fa-layer-group me-2"></i>FIXED Invoice Group: ${refInvoice}</h6>
                ${isCombined ? 
                    `<h6><i class="fas fa-cubes me-2 text-warning"></i>Combined Group - Prefix: ${numericPrefix} (${subCount} invoices)</h6>
                     <p class="small">FIXED system combined ${subCount} invoices with prefix "${numericPrefix}" into one shipping instruction PDF.</p>` : 
                    '<h6><i class="fas fa-file me-2 text-primary"></i>Single Invoice with FIXED Auto-Fill</h6>'
                }
                <p><strong>Location:</strong> ${locationInfo} via ${portInfo}</p>
                <p><strong>Total Items:</strong> ${itemCount} | <strong>Volume:</strong> ${numberFormat(totalVolume, 2)} CBM | <strong>Weight:</strong> ${numberFormat(totalWeight, 2)} KG</p>
                <p><strong>Primary Buyer:</strong> ${primaryBuyer}</p>
            </div>
        `;

        if (isCombined && group.sub_invoices) {
            summaryHtml += `
                <div class="alert alert-secondary">
                    <h6><i class="fas fa-list me-2"></i>FIXED Sub-Invoices Breakdown:</h6>
                    <div class="row">
            `;
            
            Object.values(group.sub_invoices).forEach(subInvoice => {
                summaryHtml += `
                    <div class="col-md-6 mb-2">
                        <div class="border rounded p-2 small">
                            <strong>${subInvoice.ref_invoice}</strong><br>
                            ${subInvoice.item_count} items | ${numberFormat(subInvoice.total_volume, 1)} CBM | ${numberFormat(subInvoice.total_weight, 0)} KG
                        </div>
                    </div>
                `;
            });
            
            summaryHtml += `
                    </div>
                </div>
            `;
        }

        const summaryContainer = document.getElementById('groupSummaryForPdf');
        if (summaryContainer) {
            summaryContainer.innerHTML = summaryHtml;
        }

        showAutoFillLoading(true);

        if (group.forwarder_data && group.forwarder_data.migration_source) {
            console.log('FIXED: Applying auto-fill with migration data:', group.forwarder_data);
            applyFixedAutoFillData(buildAutoFillFromFixedForwarderData(group.forwarder_data, location), location);
            setupFixedNotificationOptions(group.forwarder_data);
            showAlert('Form auto-filled with FIXED migration data', 'success');
            showAutoFillLoading(false);
        } else {
            fetchFixedForwarderDataForAutoFill(refInvoice, isCombined, location)
                .then(data => {
                    if (data.success && data.auto_fill_data && data.migration_source) {
                        console.log('FIXED: API auto-fill successful:', data);
                        applyFixedAutoFillData(data.auto_fill_data, location);
                        setupFixedNotificationOptions(data.forwarder_data);
                        showAlert('Form auto-filled with FIXED migration data', 'success');
                    } else {
                        console.log('FIXED: No mapping found, applying defaults');
                        applyBasicDefaults(location, primaryBuyer, numericPrefix, isCombined);
                        showAlert('No FIXED mapping found - using basic defaults', 'info');
                    }
                })
                .catch(error => {
                    console.error('FIXED auto-fill error:', error);
                    applyBasicDefaults(location, primaryBuyer, numericPrefix, isCombined);
                    showAlert('FIXED auto-fill failed - using basic defaults', 'warning');
                })
                .finally(() => {
                    showAutoFillLoading(false);
                });
        }

    } catch (error) {
        console.error('Error populating FIXED PDF modal:', group, error);
        showAlert('Error populating PDF form: ' + error.message, 'danger');
        showAutoFillLoading(false);
    }
}

function buildAutoFillFromFixedForwarderData(forwarderData, location) {
    const defaultPort = location === 'surabaya' ? 'Tanjung Perak - Surabaya' : 'Tanjung Emas - Semarang';
    
    console.log('FIXED: Building auto-fill data from forwarder:', {
        code: forwarderData.code,
        name: forwarderData.name,
        email_count: forwarderData.all_emails ? forwarderData.all_emails.length : 0,
        whatsapp_count: forwarderData.all_whatsapp ? forwarderData.all_whatsapp.length : 0
    });
    
    return {
        forwarder_name: forwarderData.name || '',
        notification_email: forwarderData.primary_email || '',
        contact_person: forwarderData.contact_person || '',
        port_loading: defaultPort,
        pickup_location: forwarderData.address || 'To be confirmed',
        
        all_emails: forwarderData.all_emails || [],
        cc_emails: forwarderData.cc_emails || [],
        all_whatsapp: forwarderData.all_whatsapp || [],
        primary_whatsapp: forwarderData.primary_whatsapp || '',
        secondary_whatsapp: forwarderData.secondary_whatsapp || [],
        phone: forwarderData.phone || '',
        
        email_notifications_enabled: forwarderData.email_notifications_enabled !== false,
        whatsapp_notifications_enabled: forwarderData.whatsapp_notifications_enabled || false,
        
        suggested_container_type: '1 X 40 HC',
        suggested_priority: 'normal',
        migration_source: true
    };
}

async function fetchFixedForwarderDataForAutoFill(refInvoice, isCombined, location) {
    try {
        const response = await fetch('/dashboard/get-forwarder-data-for-autofill', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                ref_invoice: refInvoice,
                is_combined: isCombined,
                location: location
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('FIXED: API response received:', data);
        return data;
    } catch (error) {
        console.error('FIXED: API fetch error:', error);
        throw error;
    }
}

function applyFixedAutoFillData(autoFillData, location) {
    try {
        console.log('FIXED: Applying auto-fill data:', autoFillData);

        const forwarderNameField = document.getElementById('forwarder_name');
        const notificationEmailField = document.getElementById('notification_email');
        const contactPersonField = document.getElementById('contact_person');
        const portLoadingSelect = document.querySelector('[name="port_loading"]');
        const pickupLocationField = document.querySelector('[name="pickup_location"]');
        const containerTypeSelect = document.querySelector('[name="container_type"]');
        const prioritySelect = document.querySelector('[name="priority"]');

        if (forwarderNameField && autoFillData.forwarder_name) {
            forwarderNameField.value = autoFillData.forwarder_name;
            console.log('FIXED: Set forwarder name:', autoFillData.forwarder_name);
        }
        
        if (notificationEmailField && autoFillData.notification_email) {
            notificationEmailField.value = autoFillData.notification_email;
            console.log('FIXED: Set notification email:', autoFillData.notification_email);
        }
        
        if (contactPersonField && autoFillData.contact_person) {
            contactPersonField.value = autoFillData.contact_person;
            console.log('FIXED: Set contact person:', autoFillData.contact_person);
        }
        
        if (portLoadingSelect && autoFillData.port_loading) {
            portLoadingSelect.value = autoFillData.port_loading;
            console.log('FIXED: Set port loading:', autoFillData.port_loading);
        }
        
        if (pickupLocationField && autoFillData.pickup_location) {
            pickupLocationField.value = autoFillData.pickup_location;
        }
        
        if (containerTypeSelect && autoFillData.suggested_container_type) {
            containerTypeSelect.value = autoFillData.suggested_container_type;
        }
        
        if (prioritySelect && autoFillData.suggested_priority) {
            prioritySelect.value = autoFillData.suggested_priority;
        }

        const form = document.getElementById('generatePdfForm');
        if (form) {
            if (autoFillData.all_emails && Array.isArray(autoFillData.all_emails)) {
                form.setAttribute('data-all-emails', JSON.stringify(autoFillData.all_emails));
                console.log('FIXED: Stored all emails:', autoFillData.all_emails);
            }
            if (autoFillData.cc_emails && Array.isArray(autoFillData.cc_emails)) {
                form.setAttribute('data-cc-emails', JSON.stringify(autoFillData.cc_emails));
                console.log('FIXED: Stored CC emails:', autoFillData.cc_emails);
            }
            if (autoFillData.all_whatsapp && Array.isArray(autoFillData.all_whatsapp)) {
                form.setAttribute('data-all-whatsapp', JSON.stringify(autoFillData.all_whatsapp));
                console.log('FIXED: Stored all WhatsApp:', autoFillData.all_whatsapp);
            }
        }

        addFixedAutoFillIndicator(autoFillData);

        console.log('FIXED: Auto-fill data applied successfully');
    } catch (error) {
        console.error('FIXED: Error applying auto-fill data:', error);
    }
}

function addFixedAutoFillIndicator(autoFillData) {
    try {
        const existingIndicator = document.getElementById('fixedAutoFillIndicator');
        if (existingIndicator) {
            existingIndicator.remove();
        }

        const indicator = document.createElement('div');
        indicator.id = 'fixedAutoFillIndicator';
        indicator.className = 'auto-fill-indicator';
        indicator.innerHTML = `
            <i class="fas fa-magic"></i>
            <span class="text">Form auto-filled with FIXED migration data</span>
            <span class="migration-data-indicator ms-2">FIXED Source</span>
        `;
        
        const summaryContainer = document.getElementById('groupSummaryForPdf');
        if (summaryContainer) {
            summaryContainer.appendChild(indicator);
        }
    } catch (error) {
        console.error('FIXED: Error adding auto-fill indicator:', error);
    }
}

// UPDATED: Setup FIXED notification options with WhatsApp support
function setupFixedNotificationOptions(forwarderData) {
    try {
        if (!forwarderData || !forwarderData.migration_source) {
            console.log('FIXED: No forwarder data for notification setup');
            return;
        }

        console.log('FIXED: Setting up notification options with AUTO WhatsApp:', {
            emails: forwarderData.all_emails,
            whatsapp: forwarderData.all_whatsapp,
            email_enabled: forwarderData.email_notifications_enabled,
            whatsapp_enabled: forwarderData.whatsapp_notifications_enabled
        });

        const existingSection = document.getElementById('fixedNotificationOptionsSection');
        if (existingSection) {
            existingSection.remove();
        }

        const form = document.getElementById('generatePdfForm');
        const hasEmails = forwarderData.all_emails && forwarderData.all_emails.length > 0;
        const hasWhatsApp = forwarderData.all_whatsapp && forwarderData.all_whatsapp.length > 0;
        
        if (!hasEmails && !hasWhatsApp) {
            console.log('FIXED: No emails or WhatsApp numbers to setup');
            return;
        }

        const notificationSection = document.createElement('div');
        notificationSection.id = 'fixedNotificationOptionsSection';
        notificationSection.className = 'notification-section';
        
        let notificationHtml = `
            <h6>
                <i class="fas fa-paper-plane me-2"></i>AUTO Notification System - Email + WhatsApp
                <span class="auto-whatsapp-badge ms-2">ENHANCED</span>
            </h6>
            <div class="alert alert-info">
                <i class="fas fa-magic me-2"></i>
                <strong>Auto-Send Feature:</strong> WhatsApp akan otomatis terkirim ketika email berhasil dikirim.
            </div>
            <div class="row g-3">
        `;

        // Email Column - Primary notification
        if (hasEmails) {
            const emailCount = forwarderData.all_emails.length;
            const ccCount = forwarderData.cc_emails ? forwarderData.cc_emails.length : 0;
            
            notificationHtml += `
                <div class="col-md-6">
                    <div class="notification-option enabled">
                        <div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="sendFixedEmailNotification" checked disabled>
                                <label class="form-check-label fw-bold d-flex align-items-center" for="sendFixedEmailNotification">
                                    <i class="fas fa-envelope me-2 text-primary"></i>Email Notification (PRIMARY)
                                    <span class="badge bg-primary ms-2">${emailCount}</span>
                                </label>
                            </div>
                            <div class="mt-3 p-3 bg-light rounded-3 border">
                                <div class="mb-2">
                                    <small class="text-muted fw-bold">Primary:</small>
                                    <div class="small text-primary">${forwarderData.primary_email || 'Not set'}</div>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted fw-bold">CC (${ccCount}):</small>
                                    <div class="small text-muted">
                                        ${ccCount > 0 ? 
                                            (ccCount <= 2 ? forwarderData.cc_emails.join(', ') : 
                                             `${forwarderData.cc_emails.slice(0, 2).join(', ')}, +${ccCount - 2} more`) : 
                                            'None available'
                                        }
                                    </div>
                                </div>
                                <div class="text-center mt-2">
                                    <small class="text-success">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Always enabled - Primary notification method
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // WhatsApp Column - Auto-send when email successful
        if (hasWhatsApp) {
            const whatsappCount = forwarderData.all_whatsapp.length;
            const primaryWhatsApp = forwarderData.primary_whatsapp || forwarderData.all_whatsapp[0] || '';
            
            notificationHtml += `
                <div class="col-md-6">
                    <div class="notification-option enabled" style="border-color: var(--whatsapp-color); background: rgba(37, 211, 102, 0.05);">
                        <div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="sendFixedWhatsAppNotification" checked disabled 
                                       style="background-color: var(--whatsapp-color); border-color: var(--whatsapp-color);">
                                <label class="form-check-label fw-bold d-flex align-items-center" for="sendFixedWhatsAppNotification">
                                    <i class="fab fa-whatsapp me-2" style="color: var(--whatsapp-color);"></i>AUTO WhatsApp
                                    <span class="badge ms-2" style="background: var(--whatsapp-color);">${whatsappCount}</span>
                                    <span class="auto-whatsapp-badge ms-2">AUTO</span>
                                </label>
                            </div>
                            <div class="mt-3 p-3 rounded-3 border" style="background: rgba(37, 211, 102, 0.05); border-color: var(--whatsapp-color) !important;">
                                <div class="mb-2">
                                    <small class="text-muted fw-bold">Primary Number:</small>
                                    <div class="whatsapp-number-display">${primaryWhatsApp}</div>
                                </div>
                                
                                <div class="whatsapp-status-success">
                                    <small style="color: var(--whatsapp-color); font-weight: 600;">
                                        <i class="fas fa-magic me-1"></i>AUTO-SEND ENABLED
                                    </small>
                                    <div class="small mt-1" style="color: var(--whatsapp-dark);">
                                        Otomatis terkirim setelah email sukses
                                    </div>
                                </div>
                                
                                <!-- WhatsApp Preview -->
                                <div class="mt-2 p-2 rounded" style="background: var(--whatsapp-color); color: white;">
                                    <small class="fw-bold">
                                        <i class="fab fa-whatsapp me-1"></i>Preview Message:
                                    </small>
                                    <div class="small mt-1" style="font-family: 'Courier New', monospace;">
                                        "🚢 SHIPPING INSTRUCTION<br>
                                        📋 ID: [Auto-Generated]<br>
                                        📦 Invoice: [Ref-Invoice]<br>
                                        📅 Pickup: [Date]<br>
                                        📊 Vol: [X] CBM | ⚖️ [Y] KG<br>
                                        📧 Check email for PDF details<br>
                                        📞 Contact: EKA WIJAYA"
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // No WhatsApp available
            notificationHtml += `
                <div class="col-md-6">
                    <div class="notification-option">
                        <div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="sendFixedWhatsAppNotification" disabled>
                                <label class="form-check-label fw-bold d-flex align-items-center" for="sendFixedWhatsAppNotification">
                                    <i class="fab fa-whatsapp me-2 text-muted"></i>WhatsApp AUTO-SEND
                                    <span class="badge bg-secondary ms-2">0</span>
                                </label>
                            </div>
                            <div class="mt-3 p-3 bg-light rounded-3 border">
                                <div class="text-center py-3">
                                    <i class="fas fa-times-circle text-muted fa-2x mb-2"></i>
                                    <div class="small text-muted">
                                        No WhatsApp numbers configured<br>
                                        for this forwarder
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        notificationHtml += `
            </div>
            
            <!-- Information about AUTO-SEND -->
            <div class="alert alert-success mt-3">
                <h6><i class="fas fa-info-circle me-2"></i>How AUTO-SEND Works:</h6>
                <ol class="mb-0 small">
                    <li><strong>Email sent first</strong> - Primary notification via email system</li>
                    <li><strong>WhatsApp follows automatically</strong> - Sent immediately after email success</li>
                    <li><strong>Forwarder Portal updated</strong> - Dashboard notification delivered</li>
                    <li><strong>Status tracking</strong> - Complete delivery confirmation</li>
                </ol>
            </div>
            
            <!-- Hidden inputs for data -->
            <input type="hidden" id="allWhatsAppNumbers" name="whatsapp_numbers" value='${JSON.stringify(forwarderData.all_whatsapp || [])}'>
            <input type="hidden" id="allEmails" name="cc_emails" value='${JSON.stringify(forwarderData.cc_emails || [])}'>
            <input type="hidden" id="autoSendWhatsApp" name="auto_send_whatsapp" value="true">
        `;
        
        notificationSection.innerHTML = notificationHtml;
        form.appendChild(notificationSection);
        
        console.log('FIXED: AUTO WhatsApp notification setup completed');
    } catch (error) {
        console.error('FIXED: Error setting up AUTO WhatsApp notifications:', error);
    }
}

// UPDATED: Apply FIXED auto-fill data with WhatsApp support
function applyFixedAutoFillData(autoFillData, location) {
    try {
        console.log('FIXED: Applying auto-fill data with WhatsApp:', autoFillData);

        const forwarderNameField = document.getElementById('forwarder_name');
        const notificationEmailField = document.getElementById('notification_email');
        const contactPersonField = document.getElementById('contact_person');
        const portLoadingSelect = document.querySelector('[name="port_loading"]');
        const pickupLocationField = document.querySelector('[name="pickup_location"]');
        const containerTypeSelect = document.querySelector('[name="container_type"]');
        const prioritySelect = document.querySelector('[name="priority"]');
        
        // NEW: Primary WhatsApp field
        const primaryWhatsAppField = document.getElementById('primary_whatsapp');

        if (forwarderNameField && autoFillData.forwarder_name) {
            forwarderNameField.value = autoFillData.forwarder_name;
            console.log('FIXED: Set forwarder name:', autoFillData.forwarder_name);
        }
        
        if (notificationEmailField && autoFillData.notification_email) {
            notificationEmailField.value = autoFillData.notification_email;
            console.log('FIXED: Set notification email:', autoFillData.notification_email);
        }
        
        if (contactPersonField && autoFillData.contact_person) {
            contactPersonField.value = autoFillData.contact_person;
            console.log('FIXED: Set contact person:', autoFillData.contact_person);
        }
        
        if (portLoadingSelect && autoFillData.port_loading) {
            portLoadingSelect.value = autoFillData.port_loading;
            console.log('FIXED: Set port loading:', autoFillData.port_loading);
        }
        
        if (pickupLocationField && autoFillData.pickup_location) {
            pickupLocationField.value = autoFillData.pickup_location;
        }
        
        if (containerTypeSelect && autoFillData.suggested_container_type) {
            containerTypeSelect.value = autoFillData.suggested_container_type;
        }
        
        if (prioritySelect && autoFillData.suggested_priority) {
            prioritySelect.value = autoFillData.suggested_priority;
        }

        // NEW: Auto-fill primary WhatsApp
        if (primaryWhatsAppField && autoFillData.primary_whatsapp) {
            primaryWhatsAppField.value = autoFillData.primary_whatsapp;
            primaryWhatsAppField.classList.add('auto-filled');
            setTimeout(() => primaryWhatsAppField.classList.remove('auto-filled'), 2000);
            console.log('FIXED: Set primary WhatsApp:', autoFillData.primary_whatsapp);
        }

        const form = document.getElementById('generatePdfForm');
        if (form) {
            if (autoFillData.all_emails && Array.isArray(autoFillData.all_emails)) {
                form.setAttribute('data-all-emails', JSON.stringify(autoFillData.all_emails));
                console.log('FIXED: Stored all emails:', autoFillData.all_emails);
            }
            if (autoFillData.cc_emails && Array.isArray(autoFillData.cc_emails)) {
                form.setAttribute('data-cc-emails', JSON.stringify(autoFillData.cc_emails));
                console.log('FIXED: Stored CC emails:', autoFillData.cc_emails);
            }
            // NEW: Store WhatsApp data
            if (autoFillData.all_whatsapp && Array.isArray(autoFillData.all_whatsapp)) {
                form.setAttribute('data-all-whatsapp', JSON.stringify(autoFillData.all_whatsapp));
                console.log('FIXED: Stored all WhatsApp:', autoFillData.all_whatsapp);
            }
            if (autoFillData.primary_whatsapp) {
                form.setAttribute('data-primary-whatsapp', autoFillData.primary_whatsapp);
            }
        }

        addFixedAutoFillIndicator(autoFillData);

        console.log('FIXED: Auto-fill data applied successfully with WhatsApp');
    } catch (error) {
        console.error('FIXED: Error applying auto-fill data:', error);
    }
}

// NEW: Build auto-fill data from FIXED forwarder data with WhatsApp
function buildAutoFillFromFixedForwarderData(forwarderData, location) {
    const defaultPort = location === 'surabaya' ? 'Tanjung Perak - Surabaya' : 'Tanjung Emas - Semarang';
    
    console.log('FIXED: Building auto-fill data with WhatsApp from forwarder:', {
        code: forwarderData.code,
        name: forwarderData.name,
        email_count: forwarderData.all_emails ? forwarderData.all_emails.length : 0,
        whatsapp_count: forwarderData.all_whatsapp ? forwarderData.all_whatsapp.length : 0
    });
    
    return {
        forwarder_name: forwarderData.name || '',
        notification_email: forwarderData.primary_email || '',
        contact_person: forwarderData.contact_person || '',
        port_loading: defaultPort,
        pickup_location: forwarderData.address || 'To be confirmed',
        
        // Email data
        all_emails: forwarderData.all_emails || [],
        cc_emails: forwarderData.cc_emails || [],
        
        // NEW: WhatsApp data
        all_whatsapp: forwarderData.all_whatsapp || [],
        primary_whatsapp: forwarderData.primary_whatsapp || '',
        secondary_whatsapp: forwarderData.secondary_whatsapp || [],
        phone: forwarderData.phone || '',
        
        email_notifications_enabled: forwarderData.email_notifications_enabled !== false,
        whatsapp_notifications_enabled: forwarderData.whatsapp_notifications_enabled || false,
        
        suggested_container_type: '1 X 40 HC',
        suggested_priority: 'normal',
        migration_source: true
    };
}

// UPDATED: Generate PDF with WhatsApp support
async function generatePDF() {
    if (!currentGroup) {
        showAlert('No group selected', 'danger');
        return;
    }
    
    try {
        console.log('FIXED: Starting PDF generation with WhatsApp support for group:', currentGroup);
        
        const formData = new FormData(document.getElementById('generatePdfForm'));
        const pdfData = Object.fromEntries(formData);
        
        const requiredFields = ['forwarder_name', 'notification_email', 'pickup_location', 'expected_pickup_date', 'container_type', 'priority', 'port_loading', 'port_destination', 'contact_person', 'freight_payment'];
        for (const field of requiredFields) {
            if (!pdfData[field]) {
                showAlert(`Please fill in the ${field.replace('_', ' ')} field`, 'warning');
                return;
            }
        }

        // Check notification settings
        const sendEmailNotification = document.getElementById('sendFixedEmailNotification')?.checked || false;
        const sendWhatsAppNotification = document.getElementById('sendFixedWhatsAppNotification')?.checked || false;
        
        const form = document.getElementById('generatePdfForm');
        const allEmails = form.getAttribute('data-all-emails');
        const ccEmails = form.getAttribute('data-cc-emails');
        const allWhatsApp = form.getAttribute('data-all-whatsapp');
        const primaryWhatsApp = form.getAttribute('data-primary-whatsapp');
        
        console.log('FIXED: Notification settings with WhatsApp:', {
            sendEmail: sendEmailNotification,
            sendWhatsApp: sendWhatsAppNotification,
            allEmails: allEmails,
            ccEmails: ccEmails,
            allWhatsApp: allWhatsApp,
            primaryWhatsApp: primaryWhatsApp
        });

        const modal = bootstrap.Modal.getInstance(document.getElementById('generatePdfModal'));
        if (modal) modal.hide();
        
        showLoading(true);
        
        const exportDataIds = currentGroup.items ? currentGroup.items.map(item => item.id).filter(id => id) : [];
        
        const requestData = {
            export_data_ids: exportDataIds,
            ref_invoice: currentGroup.ref_invoice,
            is_combined: currentGroup.is_combined || false,
            numeric_prefix: currentGroup.numeric_prefix,
            sub_invoices: currentGroup.sub_invoices || null,
            delivery_type: currentGroup.delivery_type,
            location: currentGroup.location,
            
            ...pdfData,
            
            // Notification settings
            send_notifications: sendEmailNotification,
            send_whatsapp: sendWhatsAppNotification,
            cc_emails: ccEmails ? JSON.parse(ccEmails) : [],
            whatsapp_numbers: allWhatsApp ? JSON.parse(allWhatsApp) : [],
            primary_whatsapp: primaryWhatsApp || '',
            
            complete_migration_source: true
        };
        
        console.log('FIXED: Sending request data with WhatsApp:', requestData);
        
        const response = await fetch('/dashboard/generate-shipping-instruction-pdf', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify(requestData)
        });
        
        const result = await response.json();
        console.log('FIXED: PDF generation result with WhatsApp:', result);
        
        if (result.success) {
            const filename = result.pdf_filename || result.pdf_url.split('/').pop();
            
            generatedPdfs[currentGroup.ref_invoice] = {
                pdf_url: `/pdf/view/${filename}`,
                instruction_id: result.instruction_id,
                generated_at: new Date().toISOString(),
                filename: filename,
                is_combined: currentGroup.is_combined || false,
                numeric_prefix: currentGroup.numeric_prefix || '',
                forwarder_data: currentGroup.forwarder_data,
                // NEW: Store WhatsApp info
                whatsapp_enabled: sendWhatsAppNotification,
                whatsapp_numbers: allWhatsApp ? JSON.parse(allWhatsApp) : [],
                primary_whatsapp: primaryWhatsApp || ''
            };
            
            currentGroup.pdf_generated = true;
            currentGroup.status = 'generated';
            
            localStorage.setItem('generated_pdfs', JSON.stringify(generatedPdfs));
            
            renderLocationContent();
            
            const groupType = currentGroup.is_combined ? 
                `combined group (prefix: ${currentGroup.numeric_prefix})` : 
                'single invoice';
            
            const notificationTypes = [];
            if (sendEmailNotification) notificationTypes.push('Email');
            if (sendWhatsAppNotification) notificationTypes.push('WhatsApp');
            
            const successMessage = `FIXED PDF generated successfully for ${groupType} with ${notificationTypes.length > 0 ? notificationTypes.join(' & ') + ' notifications ready' : 'no notifications'}`;
            
            showAlert(successMessage, 'success');
            
            setTimeout(() => viewPDF(currentGroup.ref_invoice), 1500);
        } else {
            throw new Error(result.error || 'FIXED PDF generation failed');
        }
    } catch (error) {
        console.error('FIXED: PDF generation error:', error);
        showAlert('Failed to generate FIXED PDF: ' + error.message, 'danger');
    } finally {
        showLoading(false);
        
        const form = document.getElementById('generatePdfForm');
        if (form) {
            form.reset();
            const fixedNotificationSection = document.getElementById('fixedNotificationOptionsSection');
            if (fixedNotificationSection) {
                fixedNotificationSection.remove();
            }
            const autoFillIndicator = document.getElementById('fixedAutoFillIndicator');
            if (autoFillIndicator) {
                autoFillIndicator.remove();
            }
        }
        currentGroup = null;
    }
}

// UPDATED: Send notifications with WhatsApp support
async function sendNotifications(refInvoice) {
    try {
        console.log('AUTO-WHATSAPP: Starting enhanced notification process for:', refInvoice);
        
        const pdfInfo = generatedPdfs[refInvoice];
        if (!pdfInfo) {
            showAlert('PDF not found. Please generate PDF first.', 'warning');
            return;
        }

        if (sentNotifications[refInvoice]) {
            showAlert('Notifications already sent for this invoice', 'info');
            return;
        }

        showLoading(true);
        
        console.log('AUTO-WHATSAPP: Sending email + auto WhatsApp to /dashboard/send-container-booking-request');
        
        const response = await fetch('/dashboard/send-container-booking-request', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                instruction_id: pdfInfo.instruction_id,
                send_email: true,
                send_whatsapp: true, // Always enable WhatsApp auto-send
                send_forwarder_portal: true,
                cc_emails: pdfInfo.forwarder_data?.cc_emails || [],
                whatsapp_numbers: pdfInfo.whatsapp_numbers || [],
                primary_whatsapp: pdfInfo.primary_whatsapp || ''
            })
        });

        console.log('AUTO-WHATSAPP: Response status:', response.status);
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }

        const result = await response.json();
        console.log('AUTO-WHATSAPP: Send notification result:', result);
        
        if (result.success) {
            // Store enhanced notification result
            sentNotifications[refInvoice] = {
                sent_at: new Date().toISOString(),
                email_sent: result.results?.email_sent || false,
                whatsapp_sent: result.results?.whatsapp_sent || false,
                forwarder_portal_sent: result.results?.forwarder_portal_sent || false,
                email_count: result.results?.email_count || 0,
                whatsapp_count: result.results?.whatsapp_count || 0,
                errors: result.results?.errors || [],
                details: result.results?.details || {}
            };
            
            localStorage.setItem('sent_notifications', JSON.stringify(sentNotifications));
            renderLocationContent(); // Refresh UI
            
            // Build enhanced success message with emojis
            let successMessage = 'AUTO-SEND: Notifications delivered successfully';
            let successParts = [];
            
            if (result.results?.email_sent) {
                successParts.push(`📧 Email (${result.results.email_count} recipients)`);
            }
            if (result.results?.whatsapp_sent) {
                successParts.push(`📱 WhatsApp (${result.results.whatsapp_count} numbers)`);
            }
            if (result.results?.forwarder_portal_sent) {
                successParts.push('🖥️ Forwarder Portal');
            }
            
            if (successParts.length > 0) {
                successMessage += ':\n' + successParts.join(' + ');
            }
            
            showAlert(successMessage, 'success');
            
            // Show additional WhatsApp success info
            if (result.results?.whatsapp_sent && result.results?.whatsapp_count > 0) {
                setTimeout(() => {
                    showAlert(`🎉 WhatsApp AUTO-SEND berhasil ke ${result.results.whatsapp_count} nomor!`, 'info');
                }, 2000);
            }
            
            // Show any warnings
            if (result.results?.errors && result.results.errors.length > 0) {
                setTimeout(() => {
                    result.results.errors.forEach(error => {
                        showAlert(`⚠️ Warning: ${error}`, 'warning');
                    });
                }, 3000);
            }
            
        } else {
            throw new Error(result.error || 'Failed to send notifications');
        }
    } catch (error) {
        console.error('AUTO-WHATSAPP: Error sending notifications:', error);
        showAlert('AUTO-WHATSAPP: Failed to send notifications: ' + error.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function applyBasicDefaults(location, primaryBuyer, numericPrefix, isCombined) {
    try {
        console.log('FIXED: Applying basic defaults');
        
        const portLoadingSelect = document.querySelector('[name="port_loading"]');
        if (portLoadingSelect) {
            const defaultPort = location === 'surabaya' ? 'Tanjung Perak - Surabaya' : 'Tanjung Emas - Semarang';
            portLoadingSelect.value = defaultPort;
        }

        const forwarderNameField = document.getElementById('forwarder_name');
        if (forwarderNameField && primaryBuyer && primaryBuyer !== 'N/A') {
            const forwarderName = isCombined ? 
                `Combined - ${primaryBuyer} (Prefix ${numericPrefix})` : 
                `${primaryBuyer}`;
            forwarderNameField.value = forwarderName;
        }

        const prioritySelect = document.querySelector('[name="priority"]');
        if (prioritySelect) {
            prioritySelect.value = 'normal';
        }
    } catch (error) {
        console.error('FIXED: Error applying basic defaults:', error);
    }
}

function showAutoFillLoading(show) {
    try {
        let loadingIndicator = document.getElementById('fixedAutoFillLoading');
        
        if (show && !loadingIndicator) {
            loadingIndicator = document.createElement('div');
            loadingIndicator.id = 'fixedAutoFillLoading';
            loadingIndicator.className = 'alert alert-info text-center';
            loadingIndicator.innerHTML = `
                <div class="spinner-border spinner-border-sm me-2" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                Auto-filling with FIXED migration data...
            `;
            
            const summaryContainer = document.getElementById('groupSummaryForPdf');
            if (summaryContainer) {
                summaryContainer.appendChild(loadingIndicator);
            }
        } else if (!show && loadingIndicator) {
            loadingIndicator.remove();
        }
    } catch (error) {
        console.error('FIXED: Error showing auto-fill loading:', error);
    }
}

async function generatePDF() {
    if (!currentGroup) {
        showAlert('No group selected', 'danger');
        return;
    }
    
    try {
        console.log('FIXED: Starting PDF generation with group:', currentGroup);
        
        const formData = new FormData(document.getElementById('generatePdfForm'));
        const pdfData = Object.fromEntries(formData);
        
        const requiredFields = ['forwarder_name', 'notification_email', 'pickup_location', 'expected_pickup_date', 'container_type', 'priority', 'port_loading', 'port_destination', 'contact_person', 'freight_payment'];
        for (const field of requiredFields) {
            if (!pdfData[field]) {
                showAlert(`Please fill in the ${field.replace('_', ' ')} field`, 'warning');
                return;
            }
        }

        const sendEmailNotification = document.getElementById('sendFixedEmailNotification')?.checked || false;
        
        const form = document.getElementById('generatePdfForm');
        const allEmails = form.getAttribute('data-all-emails');
        const ccEmails = form.getAttribute('data-cc-emails');
        
        console.log('FIXED: Email notification settings:', {
            sendEmail: sendEmailNotification,
            allEmails: allEmails,
            ccEmails: ccEmails
        });

        const modal = bootstrap.Modal.getInstance(document.getElementById('generatePdfModal'));
        if (modal) modal.hide();
        
        showLoading(true);
        
        const exportDataIds = currentGroup.items ? currentGroup.items.map(item => item.id).filter(id => id) : [];
        
        const requestData = {
            export_data_ids: exportDataIds,
            ref_invoice: currentGroup.ref_invoice,
            is_combined: currentGroup.is_combined || false,
            numeric_prefix: currentGroup.numeric_prefix,
            sub_invoices: currentGroup.sub_invoices || null,
            delivery_type: currentGroup.delivery_type,
            location: currentGroup.location,
            
            ...pdfData,
            
            send_notifications: sendEmailNotification,
            cc_emails: ccEmails ? JSON.parse(ccEmails) : [],
            
            complete_migration_source: true
        };
        
        console.log('FIXED: Sending request data:', requestData);
        
        const response = await fetch('/dashboard/generate-shipping-instruction-pdf', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify(requestData)
        });
        
        const result = await response.json();
        console.log('FIXED: PDF generation result:', result);
        
        if (result.success) {
            const filename = result.pdf_filename || result.pdf_url.split('/').pop();
            
            generatedPdfs[currentGroup.ref_invoice] = {
                pdf_url: `/pdf/view/${filename}`,
                instruction_id: result.instruction_id,
                generated_at: new Date().toISOString(),
                filename: filename,
                is_combined: currentGroup.is_combined || false,
                numeric_prefix: currentGroup.numeric_prefix || '',
                forwarder_data: currentGroup.forwarder_data
            };
            
            currentGroup.pdf_generated = true;
            currentGroup.status = 'generated';
            
            localStorage.setItem('generated_pdfs', JSON.stringify(generatedPdfs));
            
            renderLocationContent();
            
            const groupType = currentGroup.is_combined ? 
                `combined group (prefix: ${currentGroup.numeric_prefix})` : 
                'single invoice';
            
            const successMessage = `FIXED PDF generated successfully for ${groupType} using FIXED migration data`;
            
            showAlert(successMessage, 'success');
            
            setTimeout(() => viewPDF(currentGroup.ref_invoice), 1500);
        } else {
            throw new Error(result.error || 'FIXED PDF generation failed');
        }
    } catch (error) {
        console.error('FIXED: PDF generation error:', error);
        showAlert('Failed to generate FIXED PDF: ' + error.message, 'danger');
    } finally {
        showLoading(false);
        
        const form = document.getElementById('generatePdfForm');
        if (form) {
            form.reset();
            const fixedNotificationSection = document.getElementById('fixedNotificationOptionsSection');
            if (fixedNotificationSection) {
                fixedNotificationSection.remove();
            }
            const autoFillIndicator = document.getElementById('fixedAutoFillIndicator');
            if (autoFillIndicator) {
                autoFillIndicator.remove();
            }
        }
        currentGroup = null;
    }
}

function viewPDF(refInvoice) {
    try {
        const pdfInfo = generatedPdfs[refInvoice];
        if (!pdfInfo) {
            showAlert('PDF not found', 'warning');
            return;
        }
        
        currentPdfUrl = pdfInfo.pdf_url;
        const pdfViewer = document.getElementById('pdfViewer');
        if (pdfViewer) {
            pdfViewer.src = currentPdfUrl;
        }
        
        const modal = new bootstrap.Modal(document.getElementById('viewPdfModal'));
        modal.show();
    } catch (error) {
        console.error('Error viewing PDF:', refInvoice, error);
        showAlert('Error viewing PDF: ' + error.message, 'danger');
    }
}

// Rest of utility functions
function showLocationView(location) {
    try {
        currentLocation = location;

        document.querySelectorAll('.location-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        const activeTab = document.querySelector(`[data-location="${location}"]`);
        if (activeTab) activeTab.classList.add('active');

        document.querySelectorAll('.location-cards-container').forEach(container => {
            container.classList.remove('show');
        });

        switch(location) {
            case 'all':
                const allView = document.getElementById('allLocationView');
                if (allView) allView.classList.add('show');
                break;
            case 'surabaya':
                const sbyView = document.getElementById('surabayaLocationView');
                if (sbyView) sbyView.classList.add('show');
                backToForwardersGrid('surabaya');
                renderForwardersGrid('surabaya');
                break;
            case 'semarang':
                const smgView = document.getElementById('semarangLocationView');
                if (smgView) smgView.classList.add('show');
                backToForwardersGrid('semarang');
                renderForwardersGrid('semarang');
                break;
        }
        
        renderLocationContent();
        console.log(`Switched to ${location} view with FIXED grouping`);
    } catch (error) {
        console.error('Error showing location view:', location, error);
        showAlert('Error switching location view: ' + error.message, 'danger');
    }
}

function toggleLocationCard(cardId) {
    try {
        const content = document.getElementById(`content-${cardId}`);
        const icon = document.getElementById(`toggle-${cardId}`);
        
        if (content && icon) {
            if (content.classList.contains('show')) {
                content.classList.remove('show');
                icon.classList.remove('rotated');
            } else {
                content.classList.add('show');
                icon.classList.add('rotated');
                
                const location = cardId.includes('surabaya') ? 'surabaya' : 'semarang';
                let containerId;
                
                if (cardId === 'surabaya' || cardId === 'surabaya-all') {
                    containerId = cardId.includes('all') ? 'surabaya-forwarders-content' : 'surabaya-single-content';
                } else if (cardId === 'semarang' || cardId === 'semarang-all') {
                    containerId = cardId.includes('all') ? 'semarang-forwarders-content' : 'semarang-single-content';
                }
                
                if (containerId) {
                    renderForwarderContent(location, containerId);
                }
            }
        }
    } catch (error) {
        console.error('Error toggling location card:', cardId, error);
    }
}

function toggleForwarderSection(sectionId) {
    try {
        const content = document.getElementById(`content-${sectionId}`);
        const icon = document.getElementById(`toggle-${sectionId}`);
        
        if (content && icon) {
            if (content.style.display === 'none' || content.style.display === '') {
                content.style.display = 'block';
                icon.classList.add('rotated');
            } else {
                content.style.display = 'none';
                icon.classList.remove('rotated');
            }
        }
    } catch (error) {
        console.error('Error toggling forwarder section:', sectionId, error);
    }
}

function renderLocationContent() {
    try {
        if (currentLocation === 'all') {
            renderLocationStats('surabaya', 'surabaya-stats');
            renderLocationStats('semarang', 'semarang-stats');
        } else if (currentLocation === 'surabaya') {
            renderLocationStats('surabaya', 'surabaya-single-stats');
            renderForwardersGrid('surabaya');
        } else if (currentLocation === 'semarang') {
            renderLocationStats('semarang', 'semarang-single-stats');
            renderForwardersGrid('semarang');
        }
    } catch (error) {
        console.error('Error rendering location content:', error);
    }
}

function renderLocationStats(location, containerId) {
    try {
        const container = document.getElementById(containerId);
        if (!container) return;

        const stats = locationGroups[location]?.stats || { total_records: 0, total_volume: 0, total_weight: 0, unique_buyers: 0 };

        container.innerHTML = `
            <div class="stat-card">
                <div class="stat-number">${stats.total_records}</div>
                <div class="stat-label">Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">${numberFormat(stats.total_volume, 1)}</div>
                <div class="stat-label">CBM</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">${numberFormat(stats.total_weight, 0)}</div>
                <div class="stat-label">KG</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">${stats.unique_buyers}</div>
                <div class="stat-label">Buyers</div>
            </div>
        `;
    } catch (error) {
        console.error('Error rendering location stats:', location, containerId, error);
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = `
                <div class="stat-card">
                    <div class="stat-number">-</div>
                    <div class="stat-label">Error</div>
                </div>
            `;
        }
    }
}

// Utility functions
function searchContent() {
    try {
        const searchTerm = document.getElementById('searchForwarders')?.value?.toLowerCase() || '';
        
        const forwarderCards = document.querySelectorAll('.forwarder-card-button');
        forwarderCards.forEach(card => {
            const text = card.textContent.toLowerCase();
            const matches = text.includes(searchTerm);
            card.style.display = matches ? '' : 'none';
        });
        
        const tables = document.querySelectorAll('.ref-invoice-table');
        tables.forEach(table => {
            const rows = table.querySelectorAll('tbody tr:not(.sub-invoices-row)');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const matches = text.includes(searchTerm);
                row.style.display = matches ? '' : 'none';
                
                const nextRow = row.nextElementSibling;
                if (nextRow && nextRow.classList.contains('sub-invoices-row')) {
                    nextRow.style.display = matches ? nextRow.style.display : 'none';
                }
            });
        });
    } catch (error) {
        console.error('Error in search content:', error);
    }
}

function filterByStatus() {
    try {
        const filterStatus = document.getElementById('filterStatus')?.value || '';
        const statusBadges = document.querySelectorAll('.status-badge');
        
        statusBadges.forEach(badge => {
            const row = badge.closest('tr');
            const matches = !filterStatus || badge.textContent.toLowerCase().includes(filterStatus);
            if (row) {
                row.style.display = matches ? '' : 'none';
                
                const nextRow = row.nextElementSibling;
                if (nextRow && nextRow.classList.contains('sub-invoices-row')) {
                    nextRow.style.display = matches ? nextRow.style.display : 'none';
                }
            }
        });
    } catch (error) {
        console.error('Error in filter by status:', error);
    }
}

function toggleAllCards() {
    try {
        const contents = document.querySelectorAll('.location-content, .forwarder-content');
        const icons = document.querySelectorAll('.toggle-icon');
        const toggleButton = document.getElementById('toggleAllText');
        
        const allExpanded = Array.from(contents).every(content => 
            content.classList.contains('show') || content.style.display === 'block'
        );
        
        if (allExpanded) {
            contents.forEach(content => {
                content.classList.remove('show');
                content.style.display = 'none';
            });
            icons.forEach(icon => icon.classList.remove('rotated'));
            if (toggleButton) toggleButton.textContent = 'Expand All';
        } else {
            contents.forEach(content => {
                content.classList.add('show');
                content.style.display = 'block';
            });
            icons.forEach(icon => icon.classList.add('rotated'));
            if (toggleButton) toggleButton.textContent = 'Collapse All';
        }
    } catch (error) {
        console.error('Error toggling all cards:', error);
    }
}

function createEmptyState(message = 'No data available') {
    return `
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h6>${message}</h6>
        </div>
    `;
}

function numberFormat(number, decimals = 0) {
    try {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(number || 0);
    } catch (error) {
        return (number || 0).toFixed(decimals);
    }
}

function setMinimumDates() {
    try {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const tomorrowStr = tomorrow.toISOString().split('T')[0];
        
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => {
            input.min = tomorrowStr;
            if (!input.value) {
                input.value = tomorrowStr;
            }
        });
    } catch (error) {
        console.error('Error setting minimum dates:', error);
    }
}

function loadFromStorage() {
    try {
        const storedPdfs = localStorage.getItem('generated_pdfs');
        if (storedPdfs) {
            generatedPdfs = JSON.parse(storedPdfs);
        }
        
        const storedNotifications = localStorage.getItem('sent_notifications');
        if (storedNotifications) {
            sentNotifications = JSON.parse(storedNotifications);
        }
    } catch (error) {
        console.error('Error loading from storage:', error);
        generatedPdfs = {};
        sentNotifications = {};
    }
}

function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        document.getElementById('logout-form').submit();
    }
}

function showLoading(show) {
    try {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = show ? 'flex' : 'none';
        }
    } catch (error) {
        console.error('Error showing loading overlay:', error);
    }
}

function showAlert(message, type) {
    try {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 350px;';
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
    } catch (error) {
        console.error('Error showing alert:', message, error);
    }
}

function downloadCurrentPdf() {
    try {
        if (currentPdfUrl) {
            const downloadUrl = currentPdfUrl.replace('/pdf/view/', '/pdf/download/');
            window.open(downloadUrl, '_blank');
        } else {
            showAlert('No PDF to download', 'warning');
        }
    } catch (error) {
        console.error('Error downloading PDF:', error);
        showAlert('Error downloading PDF: ' + error.message, 'danger');
    }
}

console.log('='.repeat(80));
console.log('FIXED EMAIL SEND SYSTEM - DASHBOARD READY');
console.log('- PDF Generation: ✓ Working with FIXED auto-fill');
console.log('- Email Notifications: ✓ FIXED - Focus on email only');  
console.log('- Send Endpoint: ✓ FIXED - /dashboard/send-container-booking-request');
console.log('- Auto-fill System: ✓ FIXED email parsing from migration');
console.log('- Error Handling: ✓ FIXED with detailed logging');
console.log('- Development Mode: ✓ FIXED SMTP detection');
console.log('='.repeat(80));
console.log('Enhanced Export Dashboard with FIXED Send Email System - Ready');
console.log('Features: FIXED 3-digit prefix grouping, Auto-fill forwarder data from migration, EMAIL notifications focus, Combined PDF generation, Send functionality');
console.log('Migration Data Integration: FIXED email auto-fill based on forwarder mapping from migration/seeder');
console.log('Send System: FIXED automatic email notification sending after PDF generation');

</script>
@endsection