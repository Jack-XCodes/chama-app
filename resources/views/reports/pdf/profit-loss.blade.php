@extends('reports.pdf.layout')

@section('content')
    <!-- Income Section -->
    <div class="section">
        <div class="section-title">Income</div>
        
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="text-right">Amount</th>
                    <th class="text-center">Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['income']['categories'] as $category)
                <tr>
                    <td>{{ $category['category'] }}</td>
                    <td class="amount">{{ $category['formatted'] }}</td>
                    <td class="text-center">{{ $category['count'] }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td><strong>Total Income</strong></td>
                    <td class="amount"><strong>{{ $data['income']['total_income']['formatted'] }}</strong></td>
                    <td class="text-center"><strong>{{ $data['income']['categories']->sum('count') }}</strong></td>
                </tr>
            </tbody>
        </table>

        <!-- Income Breakdown -->
        <div class="summary-box">
            <div class="summary-item">
                <strong>Member Contributions:</strong> {{ $data['income']['breakdown']['member_contributions']['formatted'] }}
            </div>
            <div class="summary-item">
                <strong>Other Income:</strong> {{ $data['income']['breakdown']['other_income']['formatted'] }}
            </div>
        </div>
    </div>

    <!-- Expenses Section -->
    <div class="section">
        <div class="section-title">Expenses</div>
        
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="text-right">Amount</th>
                    <th class="text-center">Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['expenses']['categories'] as $category)
                <tr>
                    <td>{{ $category['category'] }}</td>
                    <td class="amount">{{ $category['formatted'] }}</td>
                    <td class="text-center">{{ $category['count'] }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td><strong>Total Expenses</strong></td>
                    <td class="amount"><strong>{{ $data['expenses']['total_expenses']['formatted'] }}</strong></td>
                    <td class="text-center"><strong>{{ $data['expenses']['categories']->sum('count') }}</strong></td>
                </tr>
            </tbody>
        </table>

        <!-- Expense Breakdown -->
        <div class="summary-box">
            <div class="summary-item">
                <strong>Operating Expenses:</strong> {{ $data['expenses']['breakdown']['operating_expenses']['formatted'] }}
            </div>
            <div class="summary-item">
                <strong>Bank Charges:</strong> {{ $data['expenses']['breakdown']['bank_charges']['formatted'] }}
            </div>
        </div>
    </div>

    <!-- Net Result Section -->
    <div class="section">
        <div class="section-title">Net Result</div>
        
        <table>
            <tr>
                <td><strong>Total Income</strong></td>
                <td class="amount positive"><strong>{{ $data['income']['total_income']['formatted'] }}</strong></td>
            </tr>
            <tr>
                <td><strong>Total Expenses</strong></td>
                <td class="amount negative"><strong>({{ $data['expenses']['total_expenses']['formatted'] }})</strong></td>
            </tr>
            <tr class="total-row">
                <td><strong>{{ $data['net_result']['result_text'] }}</strong></td>
                <td class="amount {{ $data['net_result']['is_profit'] ? 'positive' : 'negative' }}">
                    <strong>{{ $data['net_result']['net_amount']['formatted'] }}</strong>
                </td>
            </tr>
        </table>

        <!-- Performance Metrics -->
        <div class="summary-box">
            <div class="summary-item">
                <strong>Profit Margin:</strong> {{ $data['net_result']['margin_percentage'] }}%
            </div>
            <div class="summary-item">
                <strong>Result Type:</strong> 
                <span class="{{ $data['net_result']['is_profit'] ? 'positive' : 'negative' }}">
                    {{ $data['net_result']['is_profit'] ? 'Profit' : 'Loss' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Summary Information -->
    <div class="section">
        <div class="section-title">Report Summary</div>
        <div class="summary-box">
            <div class="summary-item">
                <strong>Report Period:</strong> {{ $data['metadata']['period']['days'] }} days<br>
                <strong>Total Transactions:</strong> {{ $data['metadata']['transaction_count'] }}
            </div>
            <div class="summary-item">
                <strong>Income Categories:</strong> {{ count($data['income']['categories']) }}<br>
                <strong>Expense Categories:</strong> {{ count($data['expenses']['categories']) }}
            </div>
        </div>
    </div>

    @if($data['net_result']['is_profit'])
    <div style="background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; color: #155724;">
        <strong>💰 Profitable Period:</strong> The organization generated a profit of {{ $data['net_result']['net_amount']['formatted'] }} during this period.
    </div>
    @else
    <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; color: #721c24;">
        <strong>⚠️ Loss Period:</strong> The organization incurred a loss of {{ $data['net_result']['net_amount']['formatted'] }} during this period.
    </div>
    @endif
@endsection