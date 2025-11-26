<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $data['type'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #2563eb;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .summary-item {
            text-align: center;
            flex: 1;
        }
        .summary-item h3 {
            margin: 0 0 10px 0;
            color: #2563eb;
            font-size: 24px;
        }
        .summary-item p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #2563eb;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .chart-data {
            margin-top: 30px;
        }
        .chart-data h2 {
            color: #2563eb;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $data['type'] }}</h1>
        @if(isset($data['date']))
            <p>Date: {{ $data['date'] }}</p>
        @elseif(isset($data['start_date']) && isset($data['end_date']))
            <p>Period: {{ $data['start_date'] }} to {{ $data['end_date'] }}</p>
        @elseif(isset($data['month']) && isset($data['year']))
            <p>Month: {{ $data['month'] }}/{{ $data['year'] }}</p>
        @endif
        <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    </div>

    <div class="summary">
        <div class="summary-item">
            <h3>${{ number_format($data['total_revenue'], 2) }}</h3>
            <p>Total Revenue</p>
        </div>
        <div class="summary-item">
            <h3>{{ $data['total_orders'] }}</h3>
            <p>Total Orders</p>
        </div>
        <div class="summary-item">
            <h3>{{ $data['total_items'] }}</h3>
            <p>Total Items Sold</p>
        </div>
    </div>

    @if(isset($data['daily_breakdown']) && count($data['daily_breakdown']) > 0)
    <div class="chart-data">
        <h2>Daily Breakdown</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Revenue</th>
                    <th>Orders</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['daily_breakdown'] as $day)
                <tr>
                    <td>{{ $day['date'] }}</td>
                    <td>${{ number_format($day['revenue'], 2) }}</td>
                    <td>{{ $day['orders'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(isset($data['weekly_breakdown']) && count($data['weekly_breakdown']) > 0)
    <div class="chart-data">
        <h2>Weekly Breakdown</h2>
        <table>
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Week</th>
                    <th>Revenue</th>
                    <th>Orders</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['weekly_breakdown'] as $week)
                <tr>
                    <td>{{ $week['year'] }}</td>
                    <td>{{ $week['week'] }}</td>
                    <td>${{ number_format($week['revenue'], 2) }}</td>
                    <td>{{ $week['orders'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(isset($data['product_sales']) && count($data['product_sales']) > 0)
    <div class="chart-data">
        <h2>Product Sales</h2>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Total Quantity</th>
                    <th>Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['product_sales'] as $product)
                <tr>
                    <td>{{ $product['product_name'] }}</td>
                    <td>{{ $product['total_quantity'] }}</td>
                    <td>${{ number_format($product['total_revenue'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Report generated by Smart Cashier System</p>
    </div>
</body>
</html>