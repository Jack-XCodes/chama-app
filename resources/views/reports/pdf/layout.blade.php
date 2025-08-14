<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['title'] ?? 'Financial Report' }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #8B4513;
            padding-bottom: 20px;
        }
        
        .organization-name {
            font-size: 24px;
            font-weight: bold;
            color: #8B4513;
            margin-bottom: 5px;
        }
        
        .organization-details {
            font-size: 10px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .report-period {
            font-size: 12px;
            color: #666;
        }
        
        .content {
            margin-bottom: 30px;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #8B4513;
            margin-bottom: 10px;
            text-transform: uppercase;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .subsection-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #555;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #8B4513;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .amount {
            font-family: 'Courier New', monospace;
            text-align: right;
        }
        
        .total-row {
            font-weight: bold;
            border-top: 2px solid #8B4513;
            background-color: #f8f9fa;
        }
        
        .subtotal-row {
            font-weight: bold;
            border-top: 1px solid #8B4513;
        }
        
        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .positive {
            color: #28a745;
        }
        
        .negative {
            color: #dc3545;
        }
        
        .status-current {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-arrears {
            color: #dc3545;
            font-weight: bold;
        }
        
        .status-inactive {
            color: #6c757d;
            font-weight: bold;
        }
        
        .risk-low {
            color: #28a745;
        }
        
        .risk-medium {
            color: #ffc107;
        }
        
        .risk-high {
            color: #dc3545;
        }
        
        .summary-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .summary-item {
            display: inline-block;
            width: 48%;
            margin-bottom: 10px;
        }
        
        .indent {
            padding-left: 20px;
        }
        
        .no-border {
            border: none;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="organization-name">{{ $organizationInfo['name'] }}</div>
        <div class="organization-details">
            {{ $organizationInfo['address'] }}<br>
            Phone: {{ $organizationInfo['phone'] }} | Email: {{ $organizationInfo['email'] }}<br>
            Website: {{ $organizationInfo['website'] }}
        </div>
        <div class="report-title">{{ $data['title'] }}</div>
        <div class="report-period">
            Period: {{ $report->start_date->format('F j, Y') }} to {{ $report->end_date->format('F j, Y') }}
        </div>
    </div>

    <!-- Content -->
    <div class="content">
        @yield('content')
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>
            Generated on {{ $generatedAt->format('F j, Y \a\t g:i A') }} by {{ $report->generator->name }}<br>
            Report ID: {{ $report->id }} | Page <span class="pagenum"></span>
        </div>
    </div>
</body>
</html>