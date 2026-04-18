<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Instruction - {{ $instruction_id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-address {
            font-size: 10px;
            margin-bottom: 2px;
        }
        
        .document-info {
            margin: 15px 0;
        }
        
        .document-info table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .document-info td {
            padding: 3px 5px;
            vertical-align: top;
        }
        
        .document-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin: 20px 0;
            text-decoration: underline;
        }
        
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .content-table td {
            padding: 4px 8px;
            vertical-align: top;
            border: none;
        }
        
        .label {
            width: 150px;
            font-weight: normal;
        }
        
        .colon {
            width: 10px;
            text-align: center;
        }
        
        .value {
            font-weight: bold;
        }
        
        .address-block {
            border: 1px solid #000;
            padding: 8px;
            margin: 5px 0;
            min-height: 60px;
        }
        
        .footer-note {
            border: 2px solid #000;
            padding: 8px;
            margin: 15px 0;
            font-size: 10px;
            text-align: center;
        }
        
        .signature-block {
            margin-top: 30px;
            text-align: right;
        }
        
        .signature-line {
            margin-top: 60px;
            border-bottom: 1px solid #000;
            width: 200px;
            margin-left: auto;
        }
        
        .red-text {
            color: #FF0000;
            font-weight: bold;
        }
        
        .blue-text {
            color: #0000FF;
            font-weight: bold;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 10px;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
        }
        
        .items-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .center {
            text-align: center;
        }
        
        .right {
            text-align: right;
        }
        
        .combined-info {
            background-color: #e3f2fd;
            border: 1px solid #2196f3;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        
        .combined-badge {
            background-color: #2196f3;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 5px;
        }
        
        .sub-invoices-section {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 8px;
            margin: 8px 0;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <!-- Page 1: Main Shipping Instruction -->
    <div class="header">
        <div class="company-name">PT. KAYU MEBEL INDONESIA</div>
        <div class="company-address">JL. MANUNGGAL JATI, RT.007, RW.001</div>
        <div class="company-address">KEL. JATIKALANG, KEC KRIAN, SIDOARJO 61262, JAWA TIMUR</div>
        <div class="company-address">INDONESIA</div>
    </div>

    <div class="document-info">
        <table>
            <tr>
                <td style="width: 40%;">
                    <strong>Nomor :</strong> {{ $instruction_id ?? 'N/A' }}
                </td>
                <td style="width: 20%;"></td>
                <td style="width: 40%; text-align: right;">
                    <strong>Tanggal :</strong> {{ isset($generated_at) ? $generated_at->format('d-M-y') : date('d-M-y') }}<br>
                    <strong>Kepada Yth.</strong><br>
                    <strong>{{ $forwarder_name ?? 'FORWARDER NAME' }}</strong>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <strong>Perihal :</strong> Shipment for {{ $ref_invoice ?? 'INVOICE' }}
                    @if(isset($is_combined) && $is_combined)
                        <span class="combined-badge">COMBINED INVOICES</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div style="margin: 20px 0;">
        <strong>Dengan Hormat,</strong>
    </div>

    @if(isset($is_combined) && $is_combined && isset($sub_invoices) && $sub_invoices)
    <div class="combined-info">
        <div class="combined-badge">COMBINED GROUP - {{ count($sub_invoices) }} INVOICES</div>
        <div style="font-weight: bold;">Reference Invoices Included:</div>
        @foreach($sub_invoices as $subInvoice)
            <div style="margin: 2px 0;">• {{ $subInvoice['ref_invoice'] ?? 'N/A' }} ({{ $subInvoice['item_count'] ?? 0 }} items)</div>
        @endforeach
    </div>
    @endif

    <div style="margin: 15px 0;">
        Sehubungan dengan rencana export atas barang kami sebagai berikut :
    </div>

    <table class="content-table">
        <tr>
            <td class="label">Per Kapal</td>
            <td class="colon">:</td>
            <td class="value red-text">SEAFREIGHT</td>
            <td style="width: 100px;"></td>
            <td class="label">Tgl Stuffing / CRD</td>
            <td class="colon">:</td>
            <td class="value red-text">{{ isset($expected_pickup_date) ? date('d-M-y', strtotime($expected_pickup_date)) : date('d-M-y') }}</td>
        </tr>
        <tr>
            <td class="label">Nama Barang</td>
            <td class="colon">:</td>
            <td class="value">WOODEN FURNITURE</td>
            <td></td>
            <td class="label">P.O.# NO.</td>
            <td class="colon">:</td>
            <td class="value">{{ $ref_invoice ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah Barang</td>
            <td class="colon">:</td>
            <td class="value">{{ number_format($total_quantity ?? 0, 0) }} PCS</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td class="label">Bruto</td>
            <td class="colon">:</td>
            <td class="value">GW : {{ number_format($total_weight ?? 0, 1) }} KG</td>
            <td class="value">NW : {{ number_format(($total_weight ?? 0) * 0.85, 0) }} KG</td>
            <td class="value" colspan="3">MEASS : {{ number_format($total_volume ?? 0, 4) }} CBM</td>
        </tr>
        <tr>
            <td class="label">Pelabuhan Muat</td>
            <td class="colon">:</td>
            <td class="value">{{ $port_loading ?? 'PORT' }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td class="label">Pelabuhan Tujuan</td>
            <td class="colon">:</td>
            <td class="value blue-text">{{ $port_destination ?? 'DESTINATION' }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <table class="content-table" style="margin-top: 15px;">
        <tr>
            <td class="label">Pengirim</td>
            <td class="colon">:</td>
            <td>
                <div class="address-block">
                    <strong>PT. KAYU MEBEL INDONESIA</strong><br>
                    JL. MANUNGGAL JATI, RT.007, RW.001<br>
                    KEL. JATIKALANG, KEC KRIAN<br>
                    SIDOARJO 61262, JAWA TIMUR<br>
                    INDONESIA
                </div>
            </td>
        </tr>
        <tr>
            <td class="label">Consignee</td>
            <td class="colon">:</td>
            <td>
                <div class="address-block">
                    @php
                        $primaryBuyer = 'BUYER NAME';
                        if (isset($export_data) && $export_data && $export_data->first()) {
                            $primaryBuyer = $export_data->first()->buyer ?? 'BUYER NAME';
                        }
                        $buyerLines = explode(' ', $primaryBuyer);
                        $line1 = implode(' ', array_slice($buyerLines, 0, 2));
                        $line2 = implode(' ', array_slice($buyerLines, 2));
                    @endphp
                    <strong>{{ $line1 }}</strong><br>
                    {{ $line2 }}<br>
                    {{ $port_destination ?? 'DESTINATION' }}<br>
                    USA
                </div>
            </td>
        </tr>
    </table>

    <table class="content-table">
        <tr>
            <td class="label">Code Hs</td>
            <td class="colon">:</td>
            <td class="value">9403.60.90</td>
        </tr>
        <tr>
            <td class="label">cara pengapalan</td>
            <td class="colon">:</td>
            <td class="value red-text">{{ $container_type ?? '1 X 40 HC' }}</td>
        </tr>
        <tr>
            <td class="label">pembayaran Freight</td>
            <td class="colon">:</td>
            <td class="value">{{ $freight_payment ?? 'COLLECT' }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah Konosemen</td>
            <td class="colon">:</td>
            <td class="value">3 Original + 8 Copy</td>
        </tr>
    </table>

    <div style="margin: 20px 0;">
        Dengan ini kami mohon bantuan sepenuhnya untuk melaksanakan pengapalan<br>
        atas barang kami tsb. diatas.
    </div>

    <div class="footer-note">
        <strong>NOTE :</strong><br>
        <strong># MOHON DI PRIORITASKAN DEPO YANG BISA BUKA SAMPAI MALAM</strong>
    </div>

    <div style="margin: 20px 0;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;">
                    Alamat email : exim_3@pawindo.com
                </td>
                <td style="width: 50%; text-align: right;">
                    Hormat kami,<br><br><br>
                    <strong>EKA WIJAYA</strong>
                </td>
            </tr>
        </table>
    </div>

    <div class="page-break"></div>

    <!-- Page 2: Detailed Shipping Instruction with Items -->
    <div class="header">
        <div class="company-name" style="color: #008000;">PT. KAYU MEBEL INDONESIA</div>
        <div class="company-address">JL. MANUNGGAL JATI, RT.007, RW.001</div>
        <div class="company-address">KEL. JATIKALANG, KEC KRIAN, SIDOARJO 61262, JAWA TIMUR</div>
        <div class="company-address">INDONESIA</div>
        <hr style="border: 1px solid #000; margin: 10px 0;">
    </div>

    <div class="document-info">
        <table>
            <tr>
                <td style="width: 40%;">
                    <strong>Nomor :</strong> {{ $instruction_id ?? 'N/A' }}
                </td>
                <td style="width: 20%;"></td>
                <td style="width: 40%; text-align: right;">
                    <strong>Tanggal :</strong> {{ isset($generated_at) ? $generated_at->format('d-M-y') : date('d-M-y') }}<br>
                    <strong>Kepada Yth.</strong><br>
                    <strong>{{ $forwarder_name ?? 'FORWARDER NAME' }}</strong>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <strong>Perihal :</strong> Shipment for {{ $ref_invoice ?? 'INVOICE' }}
                    @if(isset($is_combined) && $is_combined)
                        <span class="combined-badge">COMBINED INVOICES</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="document-title">SHIPPING INSTRUCTION</div>

    <div style="margin: 15px 0;">
        <strong>Dengan Hormat,</strong>
    </div>

    @if(isset($is_combined) && $is_combined && isset($sub_invoices) && $sub_invoices)
    <div class="combined-info">
        <div class="combined-badge">NUMERIC PREFIX GROUPING - {{ count($sub_invoices) }} INVOICES</div>
        <div style="font-weight: bold;">This shipping instruction covers the following invoices:</div>
        @foreach($sub_invoices as $subInvoice)
            <div class="sub-invoices-section">
                <strong>{{ $subInvoice['ref_invoice'] ?? 'N/A' }}</strong> - 
                {{ $subInvoice['item_count'] ?? 0 }} items, 
                {{ number_format($subInvoice['total_weight'] ?? 0, 0) }} KG, 
                {{ number_format($subInvoice['total_quantity'] ?? 0, 0) }} PCS<br>
                <small>Buyers: {{ isset($subInvoice['buyers']) ? implode(', ', $subInvoice['buyers']) : 'N/A' }}</small>
            </div>
        @endforeach
    </div>
    @endif

    <div style="margin: 15px 0;">
        Sehubungan dengan rencana export atas barang kami sebagai berikut :
    </div>

    <table class="content-table">
        <tr>
            <td class="label">Per Kapal</td>
            <td class="colon">:</td>
            <td class="value red-text">SEAFREIGHT</td>
            <td style="width: 100px;"></td>
            <td class="label">Tgl Stuffing / CRD</td>
            <td class="colon">:</td>
            <td class="value red-text">{{ isset($expected_pickup_date) ? date('d-M-y', strtotime($expected_pickup_date)) : date('d-M-y') }}</td>
        </tr>
        <tr>
            <td class="label">Nama Barang</td>
            <td class="colon">:</td>
            <td class="value">WOODEN FURNITURE</td>
            <td></td>
            <td class="label">P.O.# NO.</td>
            <td class="colon">:</td>
            <td class="value">{{ $ref_invoice ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah Barang</td>
            <td class="colon">:</td>
            <td class="value">{{ number_format($total_quantity ?? 0, 0) }} PCS</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td class="label">Bruto</td>
            <td class="colon">:</td>
            <td class="value">GW : {{ number_format($total_weight ?? 0, 1) }} KG</td>
            <td class="value">NW : {{ number_format(($total_weight ?? 0) * 0.85, 0) }} KG</td>
            <td class="value" colspan="3">MEASS : {{ number_format($total_volume ?? 0, 4) }} CBM</td>
        </tr>
        <tr>
            <td class="label">Pelabuhan Muat</td>
            <td class="colon">:</td>
            <td class="value">{{ $port_loading ?? 'PORT' }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td class="label">Pelabuhan Tujuan</td>
            <td class="colon">:</td>
            <td class="value blue-text">{{ $port_destination ?? 'DESTINATION' }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <table class="content-table" style="margin-top: 15px;">
        <tr>
            <td class="label">Pengirim</td>
            <td class="colon">:</td>
            <td>
                <strong>PT. KAYU MEBEL INDONESIA</strong><br>
                JL. MANUNGGAL JATI, RT.007, RW.001<br>
                KEL. JATIKALANG, KEC KRIAN<br>
                SIDOARJO 61262, JAWA TIMUR<br>
                INDONESIA
            </td>
        </tr>
    </table>

    <table class="content-table">
        <tr>
            <td class="label">Consignee</td>
            <td class="colon">:</td>
            <td>
                @php
                    $buyerName = 'BUYER NAME';
                    if (isset($export_data) && $export_data && $export_data->first()) {
                        $buyerName = $export_data->first()->buyer ?? 'BUYER NAME';
                    }
                @endphp
                <strong>{{ $buyerName }}</strong><br>
                {{ $port_destination ?? 'DESTINATION' }}<br>
                USA
            </td>
        </tr>
    </table>

    <table class="content-table">
        <tr>
            <td class="label">Code Hs</td>
            <td class="colon">:</td>
            <td class="value">9403.60.90</td>
        </tr>
        <tr>
            <td class="label">cara pengapalan</td>
            <td class="colon">:</td>
            <td class="value red-text">{{ $container_type ?? '1 X 40 HC' }}</td>
        </tr>
        <tr>
            <td class="label">pembayaran Freight</td>
            <td class="colon">:</td>
            <td class="value">{{ $freight_payment ?? 'COLLECT' }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah Konosemen</td>
            <td class="colon">:</td>
            <td class="value">3 Original + 8 Copy</td>
        </tr>
    </table>

    <div style="margin: 20px 0;">
        Dengan ini kami mohon bantuan sepenuhnya untuk melaksanakan pengapalan<br>
        atas barang kami tsb. diatas.
    </div>

    <!-- Items Detail Table -->
    @if(isset($export_data) && $export_data && count($export_data) > 0)
    <div style="margin: 20px 0;">
        <strong>DETAIL ITEMS:</strong>
        <table class="items-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Delivery</th>
                    <th>Material</th>
                    <th>Description</th>
                    <th>Buyer</th>
                    <th>Qty</th>
                    <th>Weight (KG)</th>
                    <th>Ref Invoice</th>
                </tr>
            </thead>
            <tbody>
                @foreach($export_data as $index => $item)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $item->delivery ?? '' }}</td>
                    <td>{{ $item->material ?? '' }}</td>
                    <td>{{ $item->description ?? '' }}</td>
                    <td>{{ $item->buyer ?? '' }}</td>
                    <td class="right">{{ number_format($item->quantity ?? 0, 0) }}</td>
                    <td class="right">{{ number_format($item->weight ?? 0, 2) }}</td>
                    <td>{{ $item->reference_invoice ?? '' }}</td>
                </tr>
                @endforeach
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="5" class="center">TOTAL</td>
                    <td class="right">{{ number_format($total_quantity ?? 0, 0) }}</td>
                    <td class="right">{{ number_format($total_weight ?? 0, 2) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer-note">
        <strong>NOTE :</strong><br>
        <strong># MOHON DI PRIORITASKAN DEPO YANG BISA BUKA SAMPAI MALAM</strong>
    </div>

    <div style="margin: 20px 0;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;">
                    Alamat email : exim_3@pawindo.com
                </td>
                <td style="width: 50%; text-align: right;">
                    Hormat kami,<br><br><br>
                    <strong>EKA WIJAYA</strong>
                </td>
            </tr>
        </table>
    </div>

    @if(isset($special_instructions) && $special_instructions)
    <div style="margin: 20px 0; border: 1px solid #ccc; padding: 10px;">
        <strong>SPECIAL INSTRUCTIONS:</strong><br>
        {{ $special_instructions }}
    </div>
    @endif

    <!-- Footer Information -->
    <div style="margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 9px; color: #666;">
        <strong>Generated Information:</strong><br>
        Instruction ID: {{ $instruction_id ?? 'N/A' }}<br>
        Generated Date: {{ isset($generated_at) ? $generated_at->format('d-M-Y H:i:s') : date('d-M-Y H:i:s') }}<br>
        Generated by: {{ $generated_by ?? 'System' }}<br>
        Contact Person: {{ $contact_person ?? 'N/A' }}<br>
        @if(isset($is_combined) && $is_combined)
        Invoice Type: Combined Group ({{ isset($sub_invoices) ? count($sub_invoices) : 0 }} invoices)<br>
        @else
        Invoice Type: Single Invoice<br>
        @endif
        System: Portal EXIM - SAP Integrated PDF System with Numeric Prefix Grouping
    </div>
</body>
</html>