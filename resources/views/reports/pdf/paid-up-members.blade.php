@extends('reports.pdf.layout')

@section('content')
    <!-- Summary Section -->
    <div class="section">
        <div class="section-title">Summary</div>
        
        <div class="summary-box">
            <table style="margin-bottom: 0;">
                <tr>
                    <td><strong>Total Members:</strong></td>
                    <td class="text-right">{{ $data['summary']['member_counts']['total'] }}</td>
                    <td><strong>Collection Efficiency:</strong></td>
                    <td class="text-right">{{ $data['summary']['financial_summary']['collection_efficiency'] }}%</td>
                </tr>
                <tr>
                    <td><strong>Current Members:</strong></td>
                    <td class="text-right status-current">{{ $data['summary']['member_counts']['current'] }} ({{ $data['summary']['member_counts']['current_percentage'] }}%)</td>
                    <td><strong>Total Expected:</strong></td>
                    <td class="text-right">{{ $data['summary']['financial_summary']['total_expected']['formatted'] }}</td>
                </tr>
                <tr>
                    <td><strong>Members in Arrears:</strong></td>
                    <td class="text-right status-arrears">{{ $data['summary']['member_counts']['arrears'] }} ({{ $data['summary']['member_counts']['arrears_percentage'] }}%)</td>
                    <td><strong>Total Collected:</strong></td>
                    <td class="text-right">{{ $data['summary']['financial_summary']['total_actual']['formatted'] }}</td>
                </tr>
                <tr>
                    <td><strong>Inactive Members:</strong></td>
                    <td class="text-right status-inactive">{{ $data['summary']['member_counts']['inactive'] }}</td>
                    <td><strong>Balance:</strong></td>
                    <td class="text-right {{ $data['summary']['financial_summary']['total_balance']['amount'] >= 0 ? 'positive' : 'negative' }}">{{ $data['summary']['financial_summary']['total_balance']['formatted'] }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Risk Analysis -->
    <div class="section">
        <div class="section-title">Risk Analysis</div>
        
        <table>
            <thead>
                <tr>
                    <th>Risk Level</th>
                    <th class="text-center">Count</th>
                    <th class="text-center">Percentage</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="risk-high">High Risk</td>
                    <td class="text-center">{{ $data['summary']['risk_analysis']['high_risk'] }}</td>
                    <td class="text-center">{{ $data['summary']['risk_analysis']['high_risk_percentage'] }}%</td>
                </tr>
                <tr>
                    <td class="risk-medium">Medium Risk</td>
                    <td class="text-center">{{ $data['summary']['risk_analysis']['medium_risk'] }}</td>
                    <td class="text-center">{{ round(($data['summary']['risk_analysis']['medium_risk'] / $data['summary']['member_counts']['total']) * 100, 1) }}%</td>
                </tr>
                <tr>
                    <td class="risk-low">Low Risk</td>
                    <td class="text-center">{{ $data['summary']['risk_analysis']['low_risk'] }}</td>
                    <td class="text-center">{{ round(($data['summary']['risk_analysis']['low_risk'] / $data['summary']['member_counts']['total']) * 100, 1) }}%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Current Members -->
    @if(!empty($data['current_members']))
    <div class="section">
        <div class="section-title">Current Members ({{ count($data['current_members']) }})</div>
        
        <table>
            <thead>
                <tr>
                    <th>Member</th>
                    <th class="text-right">Expected</th>
                    <th class="text-right">Actual</th>
                    <th class="text-right">Balance</th>
                    <th class="text-center">Compliance</th>
                    <th class="text-center">Risk</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['current_members'] as $member)
                <tr>
                    <td>{{ $member['member']['name'] }}</td>
                    <td class="amount">{{ $member['contributions']['expected_total']['formatted'] }}</td>
                    <td class="amount">{{ $member['contributions']['actual_total']['formatted'] }}</td>
                    <td class="amount {{ $member['balance']['status'] === 'credit' ? 'positive' : 'negative' }}">{{ $member['balance']['formatted'] }}</td>
                    <td class="text-center">{{ $member['compliance']['percentage'] }}%</td>
                    <td class="text-center risk-{{ $member['compliance']['risk_level'] }}">{{ ucfirst($member['compliance']['risk_level']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Members in Arrears -->
    @if(!empty($data['arrears_members']))
    <div class="section page-break">
        <div class="section-title">Members in Arrears ({{ count($data['arrears_members']) }})</div>
        
        <table>
            <thead>
                <tr>
                    <th>Member</th>
                    <th class="text-right">Expected</th>
                    <th class="text-right">Actual</th>
                    <th class="text-right">Balance</th>
                    <th class="text-center">Months Behind</th>
                    <th class="text-center">Risk</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['arrears_members'] as $member)
                <tr>
                    <td>{{ $member['member']['name'] }}</td>
                    <td class="amount">{{ $member['contributions']['expected_total']['formatted'] }}</td>
                    <td class="amount">{{ $member['contributions']['actual_total']['formatted'] }}</td>
                    <td class="amount negative">{{ $member['balance']['formatted'] }}</td>
                    <td class="text-center">{{ $member['compliance']['months_behind'] }}</td>
                    <td class="text-center risk-{{ $member['compliance']['risk_level'] }}">{{ ucfirst($member['compliance']['risk_level']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Aging Analysis -->
    <div class="section">
        <div class="section-title">Aging Analysis</div>
        
        <table>
            <thead>
                <tr>
                    <th>Period</th>
                    <th class="text-center">Count</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['aging_analysis'] as $period => $analysis)
                @php
                    $periodName = match($period) {
                        '1_month' => '1 Month Behind',
                        '2_months' => '2 Months Behind', 
                        '3_months' => '3 Months Behind',
                        'over_3_months' => 'Over 3 Months Behind',
                        default => ucfirst(str_replace('_', ' ', $period))
                    };
                @endphp
                <tr>
                    <td>{{ $periodName }}</td>
                    <td class="text-center">{{ $analysis['count'] }}</td>
                    <td class="amount negative">{{ $analysis['formatted_amount'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Inactive Members -->
    @if(!empty($data['inactive_members']))
    <div class="section">
        <div class="section-title">Inactive Members ({{ count($data['inactive_members']) }})</div>
        
        <table>
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Email</th>
                    <th class="text-right">Last Contribution</th>
                    <th class="text-center">Days Since Last</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['inactive_members'] as $member)
                <tr>
                    <td>{{ $member['member']['name'] }}</td>
                    <td>{{ $member['member']['email'] }}</td>
                    <td class="text-right">{{ $member['dates']['last_contribution'] ?? 'Never' }}</td>
                    <td class="text-center">{{ $member['dates']['days_since_last'] ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Contribution Settings -->
    <div class="section">
        <div class="section-title">Report Settings</div>
        <div class="summary-box">
            <div class="summary-item">
                <strong>Expected Monthly Contribution:</strong> {{ $data['contribution_settings']['expected_monthly_contribution']['formatted'] }}
            </div>
            <div class="summary-item">
                <strong>Grace Period:</strong> {{ $data['contribution_settings']['grace_period_days'] }} days
            </div>
        </div>
    </div>

    <!-- Recommendations -->
    <div class="section">
        <div class="section-title">Recommendations</div>
        <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px;">
            @if($data['summary']['member_counts']['arrears'] > 0)
            <p><strong>📋 Follow-up Required:</strong></p>
            <ul style="margin: 5px 0; padding-left: 20px;">
                <li>{{ $data['summary']['risk_analysis']['high_risk'] }} members require urgent follow-up</li>
                <li>{{ $data['summary']['risk_analysis']['medium_risk'] }} members need moderate attention</li>
                <li>Consider implementing payment reminders for overdue members</li>
            </ul>
            @endif
            
            @if($data['summary']['financial_summary']['collection_efficiency'] < 80)
            <p><strong>💰 Collection Efficiency:</strong> Current rate of {{ $data['summary']['financial_summary']['collection_efficiency'] }}% is below optimal. Consider reviewing contribution policies.</p>
            @endif
            
            @if($data['summary']['member_counts']['inactive'] > 0)
            <p><strong>👥 Member Engagement:</strong> {{ $data['summary']['member_counts']['inactive'] }} inactive members may need re-engagement strategies.</p>
            @endif
        </div>
    </div>
@endsection