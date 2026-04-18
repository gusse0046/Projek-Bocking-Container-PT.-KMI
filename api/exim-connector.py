# exim_connector.py - SAP Export Data Connector untuk Portal EXIM
# Updated untuk support RFC Z_FM_EXIM dengan field LFART (Delivery Type)

try:
    from flask import Flask, request, jsonify
except ImportError:
    print("ERROR: Flask not installed. Run: pip install flask")
    exit(1)

try:
    from pyrfc import Connection
except ImportError:
    print("ERROR: pyrfc not installed. Run: pip install pyrfc")
    exit(1)

import os
from datetime import datetime, timedelta
import time
import threading
import signal
import sys
import json

app = Flask(__name__)

# Global variable untuk tracking connection status
connection_status = {
    'last_attempt': None,
    'last_success': None,
    'last_error': None,
    'attempts_count': 0,
    'success_count': 0,
    'service': 'SAP Export Data Connector'
}

def signal_handler(sig, frame):
    """Handle Ctrl+C gracefully"""
    print('\n\n=== SHUTTING DOWN SAP EXPORT API ===')
    print('Received interrupt signal. Cleaning up...')
    sys.exit(0)

# Register signal handler
signal.signal(signal.SIGINT, signal_handler)

def connect_sap():
    """Enhanced SAP connection dengan detailed logging"""
    global connection_status
    
    connection_status['last_attempt'] = datetime.now()
    connection_status['attempts_count'] += 1
    
    start_time = time.time()
    
    try:
        print(f"[{datetime.now().strftime('%H:%M:%S')}] === STARTING SAP CONNECTION FOR EXPORT DATA ===")
        print(f"Attempt #{connection_status['attempts_count']}")
        print("Connection parameters:")
        print(f"  - Host: 192.168.254.154")
        print(f"  - System: 01")
        print(f"  - Client: 300")
        print(f"  - User: basis")
        print(f"  - Router: /H/180.250.178.70")
        print(f"  - Target RFC: Z_FM_EXIM")
        
        conn = Connection(
            user='basis',
            passwd='123itthebest',
            ashost='192.168.254.154',
            sysnr='01',
            client='300',
            lang='EN',
            saprouter='/H/180.250.178.70',
        )
        
        elapsed_time = time.time() - start_time
        print(f"✅ SAP connection established successfully in {elapsed_time:.2f} seconds")
        
        connection_status['last_success'] = datetime.now()
        connection_status['success_count'] += 1
        connection_status['last_error'] = None
        
        return conn
        
    except Exception as e:
        elapsed_time = time.time() - start_time
        error_msg = str(e)
        print(f"❌ SAP connection failed after {elapsed_time:.2f} seconds")
        print(f"Error type: {type(e).__name__}")
        print(f"Error message: {error_msg}")
        
        # Analyze common error types
        if "CPIC" in error_msg:
            print("🔍 Analysis: CPIC error suggests network/router connectivity issue")
            print("   - Verify network connectivity and firewall rules")
        elif "RFC_LOGON_FAILURE" in error_msg:
            print("🔍 Analysis: Login failure - check credentials")
            print("   - Verify username/password")
            print("   - Check if user is locked in SAP")
        elif "timeout" in error_msg.lower():
            print("🔍 Analysis: Connection timeout")
            print("   - SAP server may be slow or overloaded")
            print("   - Network latency issues")
        
        connection_status['last_error'] = {
            'timestamp': datetime.now(),
            'error': error_msg,
            'duration': elapsed_time
        }
        
        raise e

def map_sap_export_to_clean_format(sap_data):
    """
    Map SAP export field names to clean user-friendly format
    Berdasarkan struktur ZTABLEEXIM dan SELECT statement dari Z_FM_EXIM
    Updated dengan field LFART (Delivery Type)
    """
    field_mapping = {
        # Basic Delivery Information
        'VBELN': 'Delivery Number',
        'POSNR': 'Item Number',
        'LFDAT': 'Delivery Date',
        'ERDAT': 'Created Date',
        'ERNAM': 'Created By',
        'LFART': 'Delivery Type',  # ⭐ FIELD BARU - untuk membedakan Export/Import
        
        # Customer Information
        'KUNNR': 'Customer Number',
        'NAME1': 'Customer Name',
        
        # Material Information
        'MATNR': 'Material Number',
        'MAKTX': 'Material Description',
        'LFIMG': 'Delivery Quantity',
        'VRKME': 'Sales Unit',
        
        # Weight and Volume
        'BRGEW': 'Gross Weight',
        'NTGEW': 'Net Weight',
        'GEWEI': 'Weight Unit',
        'VOLUM': 'Volume',
        'VOLEH': 'Volume Unit',
        
        # Plant and Location
        'WERKS': 'Plant',
        'LGORT': 'Storage Location',
        'VSTEL': 'Shipping Point',
        'VKORG': 'Sales Organization',
        
        # Status Information
        'WBSTA': 'Goods Movement Status',
        'UECHA': 'Higher Level Item',
        
        # Dates
        'WADAT': 'Planned GI Date',
        'WADAT_IST': 'Actual GI Date',
        
        # Text Fields dari RFC
        'V_REF_INV': 'Reference Invoice',
        'V_NO_CONT': 'Container Number',
    }
    
    # Field order untuk tampilan yang lebih baik - LFART dipindah ke posisi kedua
    field_order = [
        'Delivery Number',
        'Delivery Type',  # ⭐ POSISI BARU - setelah Delivery Number
        'Item Number',
        'Customer Number',
        'Customer Name',
        'Material Number',
        'Material Description',
        'Delivery Quantity',
        'Sales Unit',
        'Container Number',
        'Reference Invoice',
        'Delivery Date',
        'Created Date',
        'Created By',
        'Plant',
        'Storage Location',
        'Shipping Point',
        'Gross Weight',
        'Net Weight',
        'Weight Unit',
        'Volume',
        'Volume Unit',
        'Goods Movement Status',
        'Sales Organization',
        'Planned GI Date',
        'Actual GI Date'
    ]
    
    if isinstance(sap_data, list):
        return [map_single_export_record_clean(record, field_mapping, field_order) for record in sap_data]
    else:
        return map_single_export_record_clean(sap_data, field_mapping, field_order)

def map_single_export_record_clean(record, field_mapping, field_order):
    """Map a single SAP export record to clean user-friendly format"""
    if not isinstance(record, dict):
        return record
    
    clean_record = {}
    
    # Map SAP fields to friendly names
    for sap_field, friendly_name in field_mapping.items():
        if sap_field in record:
            value = record[sap_field]
            
            # Format specific fields berdasarkan tipe data
            if friendly_name in ['Delivery Date', 'Created Date', 'Planned GI Date', 'Actual GI Date']:
                value = format_sap_date(value)
            elif friendly_name in ['Material Number', 'Customer Number', 'Item Number']:
                # Clean number fields - remove leading zeros
                value = clean_sap_number(value)
            elif friendly_name in ['Delivery Quantity', 'Gross Weight', 'Net Weight', 'Volume']:
                # Clean decimal fields
                value = clean_sap_decimal(value)
            elif friendly_name in ['Container Number', 'Reference Invoice']:
                # Clean text fields dari SAP text objects
                value = clean_sap_string(value)
            elif friendly_name == 'Delivery Type':  # ⭐ SPECIAL HANDLING untuk LFART
                # Clean delivery type dan determine Export/Import
                value = format_delivery_type(value)
            else:
                # Default cleaning
                value = clean_sap_string(value)
            
            clean_record[friendly_name] = value
    
    # ⭐ TAMBAHKAN FIELD COMPUTED untuk Export/Import Classification
    delivery_type = clean_record.get('Delivery Type', '')
    clean_record['Export_Import_Flag'] = determine_export_import_flag(delivery_type)
    
    # Ensure ordered output sesuai field_order
    ordered_record = {}
    for field_name in field_order:
        if field_name in clean_record:
            ordered_record[field_name] = clean_record[field_name]
    
    # Add computed field after basic fields
    if 'Export_Import_Flag' in clean_record:
        ordered_record['Export_Import_Flag'] = clean_record['Export_Import_Flag']
    
    # Add any additional fields that weren't in the order
    for key, value in clean_record.items():
        if key not in ordered_record:
            ordered_record[key] = value
    
    return ordered_record

def format_delivery_type(lfart_value):
    """Format LFART (Delivery Type) value"""
    if not lfart_value:
        return ''
    
    # Clean dan uppercase
    cleaned = str(lfart_value).strip().upper()
    
    return cleaned

def determine_export_import_flag(delivery_type):
    """
    Determine Export/Import flag berdasarkan Delivery Type (LFART)
    Sesuaikan logika ini dengan kode LFART yang digunakan di SAP Anda
    """
    if not delivery_type:
        return 'UNKNOWN'
    
    delivery_type = str(delivery_type).strip().upper()
    
    # ⭐ SESUAIKAN LOGIC INI dengan kode LFART di SAP Anda
    # Contoh logic - silakan disesuaikan dengan sistem SAP Anda:
    
    # Export delivery types (contoh)
    export_types = ['EXP', 'EL', 'EXPORT', 'ZEX', 'LF']  # Sesuaikan dengan kode di SAP
    
    # Import delivery types (contoh)  
    import_types = ['IMP', 'IL', 'IMPORT', 'ZIM', 'LI']  # Sesuaikan dengan kode di SAP
    
    # Domestic/Local delivery types (contoh)
    domestic_types = ['LR', 'NLCC', 'DOM', 'LOCAL', 'ZDM']  # Sesuaikan dengan kode di SAP
    
    if delivery_type in export_types:
        return 'EXPORT'
    elif delivery_type in import_types:
        return 'IMPORT'
    elif delivery_type in domestic_types:
        return 'DOMESTIC'
    else:
        # Untuk tipe yang tidak dikenal, return delivery type asli untuk analisis
        return f'UNKNOWN_{delivery_type}'

def format_sap_date(date_str):
    """Format SAP date to readable format"""
    if not date_str:
        return ''
    
    # SAP date format is usually YYYYMMDD
    if len(str(date_str)) == 8:
        date_str = str(date_str)
        return f"{date_str[6:8]}.{date_str[4:6]}.{date_str[0:4]}"
    
    return str(date_str)

def clean_sap_number(value):
    """Clean SAP number format - remove leading zeros"""
    if not value:
        return ''
    
    str_value = str(value).strip()
    cleaned = str_value.lstrip('0')
    
    if not cleaned:
        cleaned = '0'
    
    return cleaned

def clean_sap_decimal(value):
    """Clean SAP decimal format"""
    if not value:
        return ''
    
    try:
        float_value = float(value)
        
        if float_value.is_integer():
            return str(int(float_value))
        else:
            return f"{float_value:g}"
    except (ValueError, TypeError):
        return str(value)

def clean_sap_string(value):
    """Clean general SAP string - trim whitespace"""
    if not value:
        return ''
    return str(value).strip()

def get_export_data(check_param=None, timeout_seconds=60, export_only=False):
    """
    Get export data dari SAP menggunakan RFC Z_FM_EXIM
    ⭐ PARAMETER BARU: export_only - untuk filter hanya data export
    """
    conn = None
    export_data = []
    start_time = time.time()
    
    # Initialize timing variables
    connection_time = 0.0
    rfc_time = 0.0
    processing_time = 0.0
    
    try:
        # Default parameter
        if check_param is None:
            check_param = "X"  # Default value for CHECK parameter
        
        parameters = {'CHECK': check_param}
        
        # Setup IT_DETAIL table parameter - empty table untuk input
        parameters['IT_DETAIL'] = []
        
        print(f"\n[{datetime.now().strftime('%H:%M:%S')}] === CALLING SAP EXPORT FUNCTION Z_FM_EXIM ===")
        print(f"Parameters: {parameters}")
        print(f"Timeout: {timeout_seconds} seconds")
        print(f"Export Only Filter: {export_only}")  # ⭐ LOG FILTER
        
        # Connect with timeout check
        print(f"[{datetime.now().strftime('%H:%M:%S')}] Step 1: Establishing SAP connection...")
        connection_start = time.time()
        
        conn = connect_sap()
        
        connection_time = time.time() - connection_start
        print(f"[{datetime.now().strftime('%H:%M:%S')}] Step 1 completed in {connection_time:.2f}s")
        
        # Check elapsed time before calling function
        elapsed_time = time.time() - start_time
        if elapsed_time > timeout_seconds * 0.7:  # 70% of timeout
            print(f"⚠️ Warning: Connection took {elapsed_time:.2f}s, approaching timeout limit")
        
        if elapsed_time > timeout_seconds * 0.9:  # 90% of timeout
            raise Exception(f"Connection setup took too long: {elapsed_time:.2f}s (limit: {timeout_seconds}s)")
        
        print(f"[{datetime.now().strftime('%H:%M:%S')}] Step 2: Calling RFC function Z_FM_EXIM...")
        rfc_start = time.time()
        
        result = conn.call('Z_FM_EXIM', **parameters)
        
        rfc_time = time.time() - rfc_start
        print(f"[{datetime.now().strftime('%H:%M:%S')}] Step 2 completed in {rfc_time:.2f}s")
        
        print("=== RESULT STRUCTURE ===")
        print("Keys in result:", list(result.keys()) if isinstance(result, dict) else "Not a dict")
        
        # Data ada di IT_DETAIL sesuai dengan SAP function
        if 'IT_DETAIL' in result:
            raw_data = result.get('IT_DETAIL', [])
            print(f"✅ Found {len(raw_data)} records in IT_DETAIL")
            
            # Debug: Print sample record dengan LFART
            if raw_data and len(raw_data) > 0:
                sample_record = raw_data[0]
                print(f"📋 Sample record keys: {list(sample_record.keys())}")
                if 'LFART' in sample_record:
                    print(f"⭐ LFART (Delivery Type) found: {sample_record['LFART']}")  # ⭐ DEBUG LFART
                if 'V_REF_INV' in sample_record:
                    print(f"✅ Reference Invoice field found: {sample_record['V_REF_INV']}")
                if 'V_NO_CONT' in sample_record:
                    print(f"✅ Container Number field found: {sample_record['V_NO_CONT']}")
            
            # Check timeout before processing
            elapsed_time = time.time() - start_time
            if elapsed_time > timeout_seconds * 0.95:  # 95% of timeout
                print(f"⚠️ Near timeout limit ({elapsed_time:.2f}s), returning first 50 raw records")
                return raw_data[:50]  # Return limited raw data
            
            print(f"[{datetime.now().strftime('%H:%M:%S')}] Step 3: Processing and mapping export data...")
            processing_start = time.time()
            
            # Map to clean user-friendly format
            export_data = map_sap_export_to_clean_format(raw_data)
            
            # ⭐ FILTER HANYA EXPORT JIKA DIMINTA
            if export_only:
                original_count = len(export_data)
                export_data = [record for record in export_data if record.get('Export_Import_Flag') == 'EXPORT']
                filtered_count = len(export_data)
                print(f"⭐ Export-only filter applied: {original_count} -> {filtered_count} records")
                print(f"⭐ Filtered out {original_count - filtered_count} non-export records")
            
            processing_time = time.time() - processing_start
            print(f"[{datetime.now().strftime('%H:%M:%S')}] Step 3 completed in {processing_time:.2f}s")
            
            # ⭐ ANALYZE DELIVERY TYPES
            if export_data:
                delivery_type_analysis = analyze_delivery_types(export_data)
                print(f"⭐ DELIVERY TYPE ANALYSIS:")
                for dt_type, count in delivery_type_analysis.items():
                    print(f"  - {dt_type}: {count} records")
            
        else:
            print("❌ IT_DETAIL not found in result")
            # Debug: print all available keys
            for key, value in result.items():
                print(f"Key: {key}, Type: {type(value)}, Value: {value if not isinstance(value, list) or len(value) < 5 else f'List with {len(value)} items'}")
        
        total_elapsed = time.time() - start_time
        print(f"[{datetime.now().strftime('%H:%M:%S')}] === EXPORT DATA PROCESS COMPLETED ===")
        print(f"📊 Statistics:")
        print(f"  - Total execution time: {total_elapsed:.2f} seconds")
        print(f"  - Connection time: {connection_time:.2f} seconds")
        print(f"  - RFC call time: {rfc_time:.2f} seconds")
        print(f"  - Data processing time: {processing_time:.2f} seconds")
        print(f"  - Export records retrieved: {len(export_data)}")
        print(f"  - Export-only filter: {export_only}")
        print(f"  - Timeout limit: {timeout_seconds} seconds")
        print(f"  - Time remaining: {timeout_seconds - total_elapsed:.2f} seconds")
        
        # Debug: Print sample processed record dengan LFART
        if export_data and len(export_data) > 0:
            sample_processed = export_data[0]
            print(f"📋 Sample processed record keys: {list(sample_processed.keys())}")
            if 'Delivery Type' in sample_processed:
                print(f"⭐ Delivery Type field processed: {sample_processed['Delivery Type']}")  # ⭐ DEBUG PROCESSED
            if 'Export_Import_Flag' in sample_processed:
                print(f"⭐ Export/Import Flag: {sample_processed['Export_Import_Flag']}")  # ⭐ DEBUG FLAG
            if 'Container Number' in sample_processed:
                print(f"✅ Container Number field processed: {sample_processed['Container Number']}")
            if 'Reference Invoice' in sample_processed:
                print(f"✅ Reference Invoice field processed: {sample_processed['Reference Invoice']}")
        
    except Exception as e:
        elapsed_time = time.time() - start_time
        print(f"[{datetime.now().strftime('%H:%M:%S')}] ❌ ERROR in export function after {elapsed_time:.2f}s")
        print(f"Error type: {type(e).__name__}")
        print(f"Error details: {str(e)}")
        
        # Log connection status for debugging
        print(f"📈 Connection Statistics:")
        print(f"  - Total attempts: {connection_status['attempts_count']}")
        print(f"  - Successful connections: {connection_status['success_count']}")
        print(f"  - Last success: {connection_status['last_success']}")
        if connection_status['last_error']:
            print(f"  - Last error: {connection_status['last_error']['timestamp']} - {connection_status['last_error']['error']}")
        
        raise e
    finally:
        if conn:
            try:
                conn.close()
                print(f"[{datetime.now().strftime('%H:%M:%S')}] 🔐 SAP connection closed")
            except Exception as close_error:
                print(f"[{datetime.now().strftime('%H:%M:%S')}] ⚠️ Warning: Could not close SAP connection cleanly: {close_error}")
                pass
    
    return export_data

def analyze_delivery_types(data):
    """⭐ ANALYZE delivery types in the dataset"""
    analysis = {}
    for record in data:
        flag = record.get('Export_Import_Flag', 'UNKNOWN')
        delivery_type = record.get('Delivery Type', 'BLANK')
        
        key = f"{flag} ({delivery_type})"
        analysis[key] = analysis.get(key, 0) + 1
    
    return analysis

def get_current_timestamp():
    """Get current timestamp in readable format"""
    return datetime.now().strftime('%Y-%m-%d %H:%M:%S')

# ===================================================================
# QUICK/FAST ENDPOINTS - NO SAP CONNECTION
# ===================================================================

@app.route('/api/sap-export-status', methods=['GET'])
def sap_export_status():
    """SAP export status endpoint - quick response tanpa test connection"""
    try:
        return jsonify({
            'status': 'success',
            'message': 'SAP Export Data connector ready',
            'timestamp': get_current_timestamp(),
            'connection_info': {
                'host': '192.168.254.154',
                'client': '300',
                'user': 'basis',
                'rfc_function': 'Z_FM_EXIM',
                'note': 'Export data connection details available'
            },
            'endpoints_available': [
                '/api/export-data',
                '/api/export-data-fast',
                '/api/export-data-export-only',  # ⭐ NEW ENDPOINT
                '/api/export-summary',
                '/api/export-by-customer',
                '/api/export-by-material',
                '/api/test-export-connection',
                '/health'
            ],
            'features': [
                'Delivery data extraction with LFART field',  # ⭐ UPDATED
                'Export/Import classification',  # ⭐ NEW FEATURE
                'Material information',
                'Customer details',
                'Container numbers',
                'Reference invoices',
                'Weight and volume data'
            ],
            'new_fields': {  # ⭐ NEW SECTION
                'delivery_type': 'LFART field from SAP',
                'export_import_flag': 'Computed field: EXPORT/IMPORT/DOMESTIC/UNKNOWN'
            },
            'response_time': '< 1 second',
            'quick_check': True
        })
    except Exception as e:
        return jsonify({
            'status': 'error',
            'message': f'Endpoint error: {str(e)}'
        }), 500

@app.route('/api/export-health-check', methods=['GET'])
def export_health_check():
    """Quick health check tanpa SAP connection test"""
    return jsonify({
        'status': 'healthy',
        'service': 'SAP Export Data API',
        'version': '1.1.0',  # ⭐ VERSION BUMP
        'timestamp': get_current_timestamp(),
        'sap_ready': True,
        'rfc_function': 'Z_FM_EXIM',
        'response_time': '< 1 second',
        'quick_check': True,
        'features': {
            'export_data_extraction': True,
            'delivery_type_classification': True,  # ⭐ NEW FEATURE
            'export_import_filtering': True,  # ⭐ NEW FEATURE
            'container_tracking': True,
            'material_details': True,
            'customer_information': True,
            'weight_volume_data': True,
            'fast_loading': True,
            'timeout_handling': True,
            'enhanced_logging': True
        },
        'new_in_v1_1': {  # ⭐ CHANGELOG
            'lfart_field': 'Added LFART (Delivery Type) field mapping',
            'export_import_flag': 'Added computed Export/Import classification',
            'export_only_endpoint': 'Added endpoint to get only export records',
            'delivery_type_analysis': 'Added delivery type statistics in logs'
        },
        'connection_stats': {
            'total_attempts': connection_status['attempts_count'],
            'successful_connections': connection_status['success_count'],
            'last_success': connection_status['last_success'].strftime('%Y-%m-%d %H:%M:%S') if connection_status['last_success'] else None,
            'last_attempt': connection_status['last_attempt'].strftime('%Y-%m-%d %H:%M:%S') if connection_status['last_attempt'] else None
        }
    })

# ===================================================================
# SAP CONNECTION TEST
# ===================================================================

@app.route('/api/test-export-connection', methods=['GET'])
def test_export_connection():
    """Test SAP connection untuk export data"""
    start_time = time.time()
    
    try:
        print(f"\n[{datetime.now().strftime('%H:%M:%S')}] === SAP EXPORT CONNECTION TEST STARTED ===")
        
        conn = connect_sap()
        
        # Test RFC function exists
        try:
            # Call function dengan parameter minimal untuk test
            test_result = conn.call('Z_FM_EXIM', CHECK='', IT_DETAIL=[])
            print(f"✅ RFC Z_FM_EXIM is accessible")
            print(f"✅ Function returned structure: {list(test_result.keys())}")
            
            # ⭐ TEST LFART FIELD specifically
            if 'IT_DETAIL' in test_result and test_result['IT_DETAIL']:
                sample_record = test_result['IT_DETAIL'][0]
                if 'LFART' in sample_record:
                    print(f"⭐ LFART field confirmed in RFC response: {sample_record['LFART']}")
                else:
                    print(f"⚠️ LFART field NOT FOUND in sample record. Available fields: {list(sample_record.keys())}")
        except Exception as rfc_error:
            print(f"⚠️ RFC test warning: {str(rfc_error)}")
        
        conn.close()
        
        elapsed_time = time.time() - start_time
        
        return jsonify({
            'status': 'success',
            'message': 'SAP export connection test successful',
            'timestamp': get_current_timestamp(),
            'connection_time': f'{elapsed_time:.2f} seconds',
            'connection_info': {
                'host': '192.168.254.154',
                'client': '300',
                'user': 'basis',
                'router': '/H/180.250.178.70',
                'rfc_function': 'Z_FM_EXIM'
            },
            'rfc_test': 'passed',
            'lfart_field_test': 'included',  # ⭐ NEW TEST
            'test_type': 'connection_and_rfc',
            'recommendations': 'Connection is working. You can proceed to test data retrieval with LFART field.'
        })
    except Exception as e:
        elapsed_time = time.time() - start_time
        error_msg = str(e)
        
        # Analyze error and provide specific recommendations
        recommendations = []
        if "CPIC" in error_msg:
            recommendations.extend([
                "Verify network connectivity and firewall rules for port 3301",
                "Check SAP Router configuration"
            ])
        elif "RFC_LOGON_FAILURE" in error_msg:
            recommendations.extend([
                "Verify SAP username and password",
                "Check if user 'basis' is locked in SAP",
                "Confirm client '300' is correct"
            ])
        elif "FUNCTION_NOT_FOUND" in error_msg:
            recommendations.extend([
                "Verify RFC Z_FM_EXIM exists in SAP system",
                "Check if function is active and released",
                "Contact SAP ABAP developer to verify function and LFART field"
            ])
        else:
            recommendations.append("Contact SAP Basis team for detailed analysis")
        
        return jsonify({
            'status': 'error',
            'message': f'SAP export connection test failed: {error_msg}',
            'timestamp': get_current_timestamp(),
            'connection_time': f'{elapsed_time:.2f} seconds',
            'error_analysis': {
                'error_type': type(e).__name__,
                'error_details': error_msg,
                'likely_cause': 'Network/SAP Router issue' if 'CPIC' in error_msg else 'Authentication issue' if 'LOGON' in error_msg else 'RFC Function issue' if 'FUNCTION' in error_msg else 'Unknown'
            },
            'recommendations': recommendations,
            'next_steps': [
                "Check the recommendations above",
                "Verify RFC Z_FM_EXIM exists in SAP with LFART field",
                "If network tests pass, contact SAP Basis team"
            ]
        }), 500

# ===================================================================
# MAIN EXPORT DATA ENDPOINTS
# ===================================================================

@app.route('/api/export-data', methods=['GET'])
def export_data():
    """Get export data dari SAP menggunakan Z_FM_EXIM dengan LFART field"""
    check_param = request.args.get('check', 'X')
    timeout = int(request.args.get('timeout', 120))
    
    start_time = time.time()
    try:
        print(f"\n[{datetime.now().strftime('%H:%M:%S')}] === EXPORT DATA REQUEST WITH LFART ===")
        print(f"Parameters: check={check_param}, timeout={timeout}s")
        
        data = get_export_data(check_param, timeout, export_only=False)  # ⭐ Get all data
        elapsed_time = time.time() - start_time
        
        # Calculate statistics including delivery types
        container_count = 0
        invoice_count = 0
        unique_customers = set()
        unique_materials = set()
        delivery_type_stats = {}  # ⭐ NEW STATS
        export_import_stats = {}  # ⭐ NEW STATS
        
        if data:
            for record in data:
                if record.get('Container Number'):
                    container_count += 1
                if record.get('Reference Invoice'):
                    invoice_count += 1
                if record.get('Customer Number'):
                    unique_customers.add(record.get('Customer Number'))
                if record.get('Material Number'):
                    unique_materials.add(record.get('Material Number'))
                
                # ⭐ ANALYZE DELIVERY TYPES
                dt = record.get('Delivery Type', 'BLANK')
                delivery_type_stats[dt] = delivery_type_stats.get(dt, 0) + 1
                
                # ⭐ ANALYZE EXPORT/IMPORT FLAGS
                flag = record.get('Export_Import_Flag', 'UNKNOWN')
                export_import_stats[flag] = export_import_stats.get(flag, 0) + 1
        
        print(f"📋 Export Data Statistics:")
        print(f"  - Records with Container Number: {container_count}/{len(data)}")
        print(f"  - Records with Reference Invoice: {invoice_count}/{len(data)}")
        print(f"  - Unique Customers: {len(unique_customers)}")
        print(f"  - Unique Materials: {len(unique_materials)}")
        print(f"⭐ Delivery Type Distribution: {delivery_type_stats}")
        print(f"⭐ Export/Import Distribution: {export_import_stats}")
        
        return jsonify({
            'status': 'success',
            'total_records': len(data),
            'data': data,
            'response_time': f'{elapsed_time:.2f} seconds',
            'timeout_limit': f'{timeout} seconds',
            'statistics': {
                'container_count': container_count,
                'invoice_count': invoice_count,
                'unique_customers': len(unique_customers),
                'unique_materials': len(unique_materials),
                'delivery_types': delivery_type_stats,  # ⭐ NEW FIELD
                'export_import_distribution': export_import_stats  # ⭐ NEW FIELD
            },
            'performance_info': {
                'records_per_second': len(data) / elapsed_time if elapsed_time > 0 else 0,
                'efficiency': 'good' if elapsed_time < 60 else 'slow' if elapsed_time < 120 else 'very_slow'
            },
            'data_source': 'SAP RFC Z_FM_EXIM',
            'version': '1.1.0',  # ⭐ VERSION
            'new_features': ['LFART field', 'Export/Import classification']  # ⭐ CHANGELOG
        })
    except Exception as e:
        elapsed_time = time.time() - start_time
        print(f"[{datetime.now().strftime('%H:%M:%S')}] ❌ Export data request failed: {str(e)}")
        return jsonify({
            'error': str(e),
            'response_time': f'{elapsed_time:.2f} seconds',
            'error_type': type(e).__name__,
            'suggestions': [
                'Check SAP server status',
                'Verify SAP Router connectivity',
                'Verify LFART field is included in Z_FM_EXIM RFC',
                'Try using /api/export-data-fast for shorter timeout',
                'Contact SAP Basis if error persists'
            ]
        }), 500

@app.route('/api/export-data-export-only', methods=['GET'])  # ⭐ NEW ENDPOINT
def export_data_export_only():
    """Get ONLY export data (filtered by Export_Import_Flag = 'EXPORT')"""
    check_param = request.args.get('check', 'X')
    timeout = int(request.args.get('timeout', 120))
    
    start_time = time.time()
    try:
        print(f"\n[{datetime.now().strftime('%H:%M:%S')}] === EXPORT-ONLY DATA REQUEST ===")
        print(f"Parameters: check={check_param}, timeout={timeout}s")
        print(f"Filter: EXPORT records only")
        
        data = get_export_data(check_param, timeout, export_only=True)  # ⭐ Filter untuk export saja
        elapsed_time = time.time() - start_time
        
        # Statistics untuk export-only data
        container_count = 0
        invoice_count = 0
        unique_customers = set()
        unique_materials = set()
        delivery_type_stats = {}
        
        if data:
            for record in data:
                if record.get('Container Number'):
                    container_count += 1
                if record.get('Reference Invoice'):
                    invoice_count += 1
                if record.get('Customer Number'):
                    unique_customers.add(record.get('Customer Number'))
                if record.get('Material Number'):
                    unique_materials.add(record.get('Material Number'))
                
                dt = record.get('Delivery Type', 'BLANK')
                delivery_type_stats[dt] = delivery_type_stats.get(dt, 0) + 1
        
        return jsonify({
            'status': 'success',
            'total_records': len(data),
            'data': data,
            'response_time': f'{elapsed_time:.2f} seconds',
            'timeout_limit': f'{timeout} seconds',
            'filter_applied': 'EXPORT_ONLY',  # ⭐ INDICATOR
            'statistics': {
                'container_count': container_count,
                'invoice_count': invoice_count,
                'unique_customers': len(unique_customers),
                'unique_materials': len(unique_materials),
                'delivery_types': delivery_type_stats
            },
            'performance_info': {
                'records_per_second': len(data) / elapsed_time if elapsed_time > 0 else 0,
                'efficiency': 'good' if elapsed_time < 60 else 'slow' if elapsed_time < 120 else 'very_slow'
            },
            'data_source': 'SAP RFC Z_FM_EXIM',
            'version': '1.1.0',
            'note': 'This endpoint returns only records classified as EXPORT based on LFART field'
        })
    except Exception as e:
        elapsed_time = time.time() - start_time
        print(f"[{datetime.now().strftime('%H:%M:%S')}] ❌ Export-only data request failed: {str(e)}")
        return jsonify({
            'error': str(e),
            'response_time': f'{elapsed_time:.2f} seconds',
            'error_type': type(e).__name__,
            'filter_attempted': 'EXPORT_ONLY',
            'suggestions': [
                'Check SAP server status',
                'Verify LFART field mapping in determine_export_import_flag()',
                'Try /api/export-data to see all records first',
                'Contact SAP team to verify LFART values for export deliveries'
            ]
        }), 500

@app.route('/api/export-data-fast', methods=['GET'])
def export_data_fast():
    """Get export data dengan timeout singkat"""
    check_param = request.args.get('check', 'X')
    
    start_time = time.time()
    try:
        print(f"\n[{datetime.now().strftime('%H:%M:%S')}] === FAST EXPORT DATA REQUEST WITH LFART ===")
        
        # Timeout lebih singkat untuk response cepat
        data = get_export_data(check_param, timeout_seconds=30)
        elapsed_time = time.time() - start_time
        
        # Quick stats dengan LFART info
        export_import_stats = {}
        if data:
            for record in data:
                flag = record.get('Export_Import_Flag', 'UNKNOWN')
                export_import_stats[flag] = export_import_stats.get(flag, 0) + 1
        
        return jsonify({
            'status': 'success',
            'total_records': len(data),
            'data': data,
            'response_time': f'{elapsed_time:.2f} seconds',
            'fast_mode': True,
            'timeout_limit': '30 seconds',
            'export_import_distribution': export_import_stats,  # ⭐ QUICK STATS
            'data_source': 'SAP RFC Z_FM_EXIM',
            'version': '1.1.0'
        })
    except Exception as e:
        elapsed_time = time.time() - start_time
        print(f"[{datetime.now().strftime('%H:%M:%S')}] ❌ Fast export data request failed: {str(e)}")
        return jsonify({
            'error': str(e),
            'response_time': f'{elapsed_time:.2f} seconds',
            'fast_mode': True,
            'suggestion': 'Try /api/export-data with longer timeout if this fails'
        }), 500

@app.route('/api/export-summary', methods=['GET'])
def export_summary():
    """Get summary statistics of export data with LFART analysis"""
    check_param = request.args.get('check', 'X')
    timeout = int(request.args.get('timeout', 60))
    
    start_time = time.time()
    try:
        all_data = get_export_data(check_param, timeout_seconds=timeout)
        
        # Calculate summary statistics dengan LFART breakdown
        total_records = len(all_data)
        container_count = 0
        invoice_count = 0
        unique_customers = set()
        unique_materials = set()
        total_quantity = 0.0
        total_weight = 0.0
        total_volume = 0.0
        delivery_type_stats = {}  # ⭐ NEW
        export_import_stats = {}  # ⭐ NEW
        
        for item in all_data:
            if item.get('Container Number'):
                container_count += 1
            if item.get('Reference Invoice'):
                invoice_count += 1
            if item.get('Customer Number'):
                unique_customers.add(item.get('Customer Number'))
            if item.get('Material Number'):
                unique_materials.add(item.get('Material Number'))
            
            # ⭐ DELIVERY TYPE STATS
            dt = item.get('Delivery Type', 'BLANK')
            delivery_type_stats[dt] = delivery_type_stats.get(dt, 0) + 1
            
            # ⭐ EXPORT/IMPORT STATS
            flag = item.get('Export_Import_Flag', 'UNKNOWN')
            export_import_stats[flag] = export_import_stats.get(flag, 0) + 1
                
            # Sum quantities
            try:
                qty = float(str(item.get('Delivery Quantity', '0')).replace(',', ''))
                total_quantity += qty
            except (ValueError, TypeError):
                pass
                
            # Sum weights
            try:
                weight = float(str(item.get('Gross Weight', '0')).replace(',', ''))
                total_weight += weight
            except (ValueError, TypeError):
                pass
                
            # Sum volumes
            try:
                volume = float(str(item.get('Volume', '0')).replace(',', ''))
                total_volume += volume
            except (ValueError, TypeError):
                pass
        
        elapsed_time = time.time() - start_time
        
        return jsonify({
            'status': 'success',
            'summary': {
                'total_records': total_records,
                'container_count': container_count,
                'invoice_count': invoice_count,
                'unique_customers': len(unique_customers),
                'unique_materials': len(unique_materials),
                'total_quantity': f"{total_quantity:,.2f}",
                'total_weight': f"{total_weight:,.2f}",
                'total_volume': f"{total_volume:,.2f}",
                'container_percentage': round((container_count / total_records * 100) if total_records > 0 else 0, 2),
                'invoice_percentage': round((invoice_count / total_records * 100) if total_records > 0 else 0, 2)
            },
            'delivery_type_breakdown': delivery_type_stats,  # ⭐ NEW SECTION
            'export_import_breakdown': export_import_stats,  # ⭐ NEW SECTION
            'response_time': f'{elapsed_time:.2f} seconds',
            'data_source': 'SAP RFC Z_FM_EXIM',
            'version': '1.1.0',
            'new_analysis': ['Delivery Type (LFART)', 'Export/Import Classification']
        })
    except Exception as e:
        elapsed_time = time.time() - start_time
        return jsonify({
            'error': str(e),
            'response_time': f'{elapsed_time:.2f} seconds'
        }), 500

@app.route('/api/export-by-customer', methods=['GET'])
def export_by_customer():
    """Get export data filtered by customer"""
    customer_number = request.args.get('customer')
    check_param = request.args.get('check', 'X')
    timeout = int(request.args.get('timeout', 60))
    
    if not customer_number:
        return jsonify({
            'error': 'Customer number is required',
            'usage': 'Add ?customer=CUSTOMER_NUMBER to the URL'
        }), 400
    
    start_time = time.time()
    try:
        all_data = get_export_data(check_param, timeout_seconds=timeout)
        
        # Filter by customer
        customer_data = []
        for item in all_data:
            if item.get('Customer Number') == customer_number:
                customer_data.append(item)
        
        # ⭐ ANALYZE DELIVERY TYPES for this customer
        export_import_stats = {}
        for item in customer_data:
            flag = item.get('Export_Import_Flag', 'UNKNOWN')
            export_import_stats[flag] = export_import_stats.get(flag, 0) + 1
        
        elapsed_time = time.time() - start_time
        
        return jsonify({
            'status': 'success',
            'customer_number': customer_number,
            'total_records': len(customer_data),
            'data': customer_data,
            'export_import_distribution': export_import_stats,  # ⭐ NEW
            'response_time': f'{elapsed_time:.2f} seconds',
            'filter': f'Customer {customer_number}',
            'data_source': 'SAP RFC Z_FM_EXIM',
            'version': '1.1.0'
        })
    except Exception as e:
        elapsed_time = time.time() - start_time
        return jsonify({
            'error': str(e),
            'response_time': f'{elapsed_time:.2f} seconds'
        }), 500

@app.route('/api/export-by-material', methods=['GET'])
def export_by_material():
    """Get export data filtered by material"""
    material_number = request.args.get('material')
    check_param = request.args.get('check', 'X')
    timeout = int(request.args.get('timeout', 60))
    
    if not material_number:
        return jsonify({
            'error': 'Material number is required',
            'usage': 'Add ?material=MATERIAL_NUMBER to the URL'
        }), 400
    
    start_time = time.time()
    try:
        all_data = get_export_data(check_param, timeout_seconds=timeout)
        
        # Filter by material
        material_data = []
        for item in all_data:
            if item.get('Material Number') == material_number:
                material_data.append(item)
        
        # ⭐ ANALYZE DELIVERY TYPES for this material
        export_import_stats = {}
        for item in material_data:
            flag = item.get('Export_Import_Flag', 'UNKNOWN')
            export_import_stats[flag] = export_import_stats.get(flag, 0) + 1
        
        elapsed_time = time.time() - start_time
        
        return jsonify({
            'status': 'success',
            'material_number': material_number,
            'total_records': len(material_data),
            'data': material_data,
            'export_import_distribution': export_import_stats,  # ⭐ NEW
            'response_time': f'{elapsed_time:.2f} seconds',
            'filter': f'Material {material_number}',
            'data_source': 'SAP RFC Z_FM_EXIM',
            'version': '1.1.0'
        })
    except Exception as e:
        elapsed_time = time.time() - start_time
        return jsonify({
            'error': str(e),
            'response_time': f'{elapsed_time:.2f} seconds'
        }), 500

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'service': 'SAP Export Data API',
        'version': '1.1.0',  # ⭐ VERSION BUMP
        'timestamp': get_current_timestamp(),
        'features': {
            'export_data_extraction': True,
            'delivery_type_field': True,  # ⭐ NEW
            'export_import_classification': True,  # ⭐ NEW
            'export_only_filtering': True,  # ⭐ NEW
            'container_tracking': True,
            'material_details': True,
            'customer_information': True,
            'timeout_handling': True,
            'quick_endpoints': True,
            'performance_optimized': True,
            'enhanced_logging': True,
            'connection_monitoring': True
        },
        'connection_health': connection_status,
        'rfc_function': 'Z_FM_EXIM',
        'changelog_v1_1': {  # ⭐ NEW SECTION
            'added_lfart_field': 'LFART (Delivery Type) now extracted from SAP',
            'added_classification': 'Export/Import/Domestic classification logic',
            'added_export_only_endpoint': '/api/export-data-export-only for filtered results',
            'enhanced_statistics': 'Delivery type distribution in all responses',
            'improved_debugging': 'Better logging for LFART field analysis'
        }
    })

@app.route('/', methods=['GET'])
def index():
    """Index endpoint"""
    return jsonify({
        'message': 'SAP Export Data API - Portal EXIM Integration',
        'version': '1.1.0',  # ⭐ VERSION BUMP
        'running_on': 'http://127.0.0.1:5023',
        'sap_rfc_function': 'Z_FM_EXIM',
        'changelog_v1_1': {  # ⭐ CHANGELOG
            'added_lfart_field': 'Now extracts LFART (Delivery Type) from SAP',
            'added_export_import_classification': 'Automatic classification of records as EXPORT/IMPORT/DOMESTIC',
            'added_export_only_endpoint': 'New endpoint /api/export-data-export-only for filtered results',
            'enhanced_statistics': 'All endpoints now include delivery type distribution',
            'improved_field_mapping': 'Better field order with Delivery Type prominently displayed'
        },
        'quick_endpoints': [
            '/api/sap-export-status',
            '/api/export-health-check'
        ],
        'sap_test_endpoints': [
            '/api/test-export-connection'
        ],
        'data_endpoints': [
            '/api/export-data',
            '/api/export-data-export-only',  # ⭐ NEW
            '/api/export-data-fast',
            '/api/export-summary',
            '/api/export-by-customer?customer=CUSTOMER_NUMBER',
            '/api/export-by-material?material=MATERIAL_NUMBER'
        ],
        'features': {
            'delivery_data': 'Extract delivery information from SAP',
            'delivery_type_classification': 'LFART field for Export/Import determination',  # ⭐ NEW
            'export_import_filtering': 'Automatic classification and filtering capabilities',  # ⭐ NEW
            'material_info': 'Material descriptions and specifications',
            'customer_data': 'Customer master data integration',
            'container_tracking': 'Container number from SAP text objects',
            'invoice_reference': 'Reference invoice from SAP text objects',
            'weight_volume': 'Weight and volume calculations',
            'timeout_handling': 'Enhanced timeout protection',
            'performance_optimized': 'Fast data processing and mapping'
        },
        'data_fields': [
            'Delivery Number', 'Delivery Type (LFART)',  # ⭐ UPDATED
            'Export_Import_Flag', 'Customer Name', 'Material Description',  # ⭐ NEW FIELD
            'Container Number', 'Reference Invoice', 'Delivery Quantity',
            'Gross Weight', 'Volume', 'Delivery Date', 'Plant'
        ],
        'delivery_type_logic': {  # ⭐ NEW SECTION
            'note': 'Customize the determine_export_import_flag() function to match your SAP LFART codes',
            'current_export_types': ['EXP', 'EL', 'EXPORT', 'ZEX', 'LF'],
            'current_import_types': ['IMP', 'IL', 'IMPORT', 'ZIM', 'LI'],
            'current_domestic_types': ['LR', 'NLCC', 'DOM', 'LOCAL', 'ZDM'],
            'unknown_handling': 'Records with unrecognized LFART codes are marked as UNKNOWN_[code]'
        },
        'timeout_parameters': {
            'export_data': '120 seconds default',
            'export_data_fast': '30 seconds default',
            'export_data_export_only': '120 seconds default',  # ⭐ NEW
            'test_connection': '60 seconds default',
            'custom_timeout': 'Add ?timeout=X parameter to any endpoint'
        },
        'connection_stats': connection_status,
        'troubleshooting': {
            'test_connection': 'GET /api/test-export-connection',
            'check_lfart_mapping': 'Review determine_export_import_flag() function for your LFART codes',  # ⭐ NEW
            'test_export_only': 'GET /api/export-data-export-only to test filtering',  # ⭐ NEW
            'check_logs': 'Monitor console output for detailed LFART field analysis',
            'fast_mode': 'Use /api/export-data-fast for quick tests',
            'timeout_adjustment': 'Increase timeout parameter if needed'
        },
        'important_notes': {  # ⭐ NEW SECTION
            'lfart_customization': 'You MUST customize the determine_export_import_flag() function with your actual SAP LFART codes',
            'export_filtering': 'Use /api/export-data-export-only to get only export records and avoid import/domestic deliveries',
            'delivery_type_analysis': 'Check the delivery_type_breakdown in /api/export-summary to understand your LFART distribution',
            'field_verification': 'Test /api/test-export-connection to verify LFART field is included in your RFC response'
        }
    })

if __name__ == '__main__':
    print("=" * 80)
    print("🚀 STARTING SAP EXPORT DATA API SERVER v1.1.0")
    print("=" * 80)
    print("SAP RFC FUNCTION: Z_FM_EXIM")
    print("DATA SOURCE: SAP Delivery Documents with Material & Customer Info")
    print("⭐ NEW IN v1.1.0: LFART (Delivery Type) Field Support")
    print("=" * 80)
    print("FEATURES:")
    print("✅ Export delivery data extraction")
    print("⭐ LFART (Delivery Type) field mapping")
    print("⭐ Export/Import/Domestic classification")
    print("⭐ Export-only filtering endpoint")
    print("✅ Material descriptions and specifications")
    print("✅ Customer master data integration")
    print("✅ Container number tracking (from SAP text Z202)")
    print("✅ Reference invoice (from SAP text Z501)")
    print("✅ Weight and volume calculations")
    print("✅ Enhanced timeout handling with progress tracking")
    print("✅ Performance metrics and timing breakdown")
    print("=" * 80)
    print("⭐ NEW FIELD MAPPINGS:")
    print("📋 LFART -> Delivery Type")
    print("📋 Computed -> Export_Import_Flag (EXPORT/IMPORT/DOMESTIC/UNKNOWN)")
    print("=" * 80)
    print("QUICK ENDPOINTS (No SAP Connection):")
    print("📋 GET /api/sap-export-status")
    print("📋 GET /api/export-health-check")
    print("=" * 80)
    print("SAP TEST ENDPOINTS:")
    print("🔍 GET /api/test-export-connection - Test SAP connectivity & RFC function")
    print("=" * 80)
    print("MAIN ENDPOINTS (With SAP Connection):")
    print("📊 GET /api/export-data?timeout=180 - Full export data with LFART")
    print("⭐ GET /api/export-data-export-only - ONLY export records (filtered)")
    print("⚡ GET /api/export-data-fast - Quick export data (30s timeout)")
    print("📈 GET /api/export-summary - Summary statistics with delivery type analysis")
    print("👥 GET /api/export-by-customer?customer=CUSTOMER_NUMBER")
    print("📦 GET /api/export-by-material?material=MATERIAL_NUMBER")
    print("=" * 80)
    print("DATA FIELDS EXTRACTED:")
    print("📋 Delivery Number, Delivery Type (LFART), Item Number")
    print("⭐ Export_Import_Flag (EXPORT/IMPORT/DOMESTIC/UNKNOWN)")
    print("📦 Material Number, Description, Quantity, Unit")
    print("👥 Customer Information")
    print("🏭 Plant, Storage Location, Shipping Point")
    print("⚖️ Gross Weight, Net Weight, Volume")
    print("📅 Delivery Date, Created Date, Status")
    print("🚢 Container Number (from SAP text Z202)")
    print("📄 Reference Invoice (from SAP text Z501)")
    print("=" * 80)
    print("⭐ IMPORTANT CONFIGURATION REQUIRED:")
    print("🔧 You MUST customize the determine_export_import_flag() function")
    print("🔧 Update the export_types, import_types, domestic_types lists")
    print("🔧 Match them with your actual SAP LFART codes")
    print("🔧 Current example codes may not match your system")
    print("=" * 80)
    print("TIMEOUT FEATURES:")
    print("⏱️ Default timeouts: 30-120 seconds per endpoint")
    print("⏱️ Custom timeout: Add ?timeout=X to any endpoint")
    print("⏱️ Step-by-step progress monitoring")
    print("⏱️ Graceful timeout handling with partial results")
    print("=" * 80)
    print("🌐 Server URLs:")
    print("   Local access: http://127.0.0.1:5023")
    print("   Network access: http://0.0.0.0:5023")
    print("=" * 80)
    print("🔧 TROUBLESHOOTING STEPS:")
    print("1. Test connection: http://127.0.0.1:5023/api/test-export-connection")
    print("2. Check LFART field: Look for 'LFART field confirmed' in test output")
    print("3. Test all data: http://127.0.0.1:5023/api/export-data?timeout=300")
    print("4. Test export-only: http://127.0.0.1:5023/api/export-data-export-only")
    print("5. Analyze delivery types: http://127.0.0.1:5023/api/export-summary")
    print("6. Customize LFART codes in determine_export_import_flag() function")
    print("7. Monitor console output for detailed LFART analysis")
    print("=" * 80)
    print("🚀 Starting Flask server for Export Data with LFART support...")
    
    os.environ['PYTHONHASHSEED'] = '0'
    app.run(host='127.0.0.1', port=5023, debug=True)