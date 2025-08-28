@extends('reports.pdf.layout')

@section('content')
    <!-- Assets Section -->
    <div class="section">
        <div class="section-title">Assets</div>
        
        <!-- Current Assets -->
        <div class="subsection-title">Current Assets</div>
        <table>
            @foreach($data['assets']['current_assets'] as $asset)
            <tr>
                <td class="indent">{{ $asset['name'] }}</td>
                <td class="amount">{{ $asset['formatted'] }}</td>
            </tr>
            @endforeach
            <tr class="subtotal-row">
                <td><strong>Total Current Assets</strong></td>
                <td class="amount"><strong>{{ $data['assets']['total_current_assets']['formatted'] }}</strong></td>
            </tr>
        </table>

        <!-- Non-Current Assets -->
        @if(!empty($data['assets']['non_current_assets']))
        <div class="subsection-title">Non-Current Assets</div>
        <table>
            @foreach($data['assets']['non_current_assets'] as $asset)
            <tr>
                <td class="indent">{{ $asset['name'] }}</td>
                <td class="amount">{{ $asset['formatted'] }}</td>
            </tr>
            @endforeach
            <tr class="subtotal-row">
                <td><strong>Total Non-Current Assets</strong></td>
                <td class="amount"><strong>{{ $data['assets']['total_non_current_assets']['formatted'] }}</strong></td>
            </tr>
        </table>
        @endif

        <!-- Total Assets -->
        <table>
            <tr class="total-row">
                <td><strong>TOTAL ASSETS</strong></td>
                <td class="amount"><strong>{{ $data['assets']['total_assets']['formatted'] }}</strong></td>
            </tr>
        </table>
    </div>

    <!-- Liabilities Section -->
    <div class="section">
        <div class="section-title">Liabilities</div>
        
        <!-- Current Liabilities -->
        <div class="subsection-title">Current Liabilities</div>
        <table>
            @forelse($data['liabilities']['current_liabilities'] as $liability)
            <tr>
                <td class="indent">{{ $liability['name'] }}</td>
                <td class="amount">{{ $liability['formatted'] }}</td>
            </tr>
            @empty
            <tr>
                <td class="indent">No current liabilities</td>
                <td class="amount">KES 0.00</td>
            </tr>
            @endforelse
            <tr class="subtotal-row">
                <td><strong>Total Current Liabilities</strong></td>
                <td class="amount"><strong>{{ $data['liabilities']['total_current_liabilities']['formatted'] }}</strong></td>
            </tr>
        </table>

        <!-- Total Liabilities -->
        <table>
            <tr class="total-row">
                <td><strong>TOTAL LIABILITIES</strong></td>
                <td class="amount"><strong>{{ $data['liabilities']['total_liabilities']['formatted'] }}</strong></td>
            </tr>
        </table>
    </div>

    <!-- Equity Section -->
    <div class="section">
        <div class="section-title">Equity</div>
        
        <table>
            @foreach($data['equity']['equity_items'] as $equity)
            <tr>
                <td class="indent">{{ $equity['name'] }}</td>
                <td class="amount">{{ $equity['formatted'] }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td><strong>TOTAL EQUITY</strong></td>
                <td class="amount"><strong>{{ $data['equity']['total_equity']['formatted'] }}</strong></td>
            </tr>
        </table>
    </div>

    <!-- Total Liabilities and Equity -->
    <div class="section">
        <table>
            <tr class="total-row">
                <td><strong>TOTAL LIABILITIES AND EQUITY</strong></td>
                <td class="amount"><strong>{{ $data['totals']['total_liabilities_and_equity']['formatted'] }}</strong></td>
            </tr>
        </table>

        @if(!$data['totals']['balanced'])
        <div style="color: #dc3545; font-weight: bold; margin-top: 10px;">
            ⚠️ Warning: Balance sheet does not balance. Difference: {{ $data['totals']['difference']['formatted'] }}
        </div>
        @else
        <div style="color: #28a745; font-weight: bold; margin-top: 10px;">
            ✅ Balance sheet is balanced.
        </div>
        @endif
    </div>

    <!-- Summary Information -->
    <div class="section">
        <div class="section-title">Report Information</div>
        <div class="summary-box">
            <div class="summary-item">
                <strong>Report Date:</strong> {{ $data['metadata']['generated_at'] }}<br>
                <strong>Period:</strong> {{ $data['metadata']['period']['days'] }} days
            </div>
            <div class="summary-item">
                <strong>Transaction Count:</strong> {{ $data['metadata']['transaction_count'] }}<br>
                <strong>Status:</strong> {{ $data['totals']['balanced'] ? 'Balanced' : 'Unbalanced' }}
            </div>
        </div>
    </div>
@endsection