<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Users Report</title>
    <style>
        @page {
            margin: 20mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .header {
            position: relative;
            padding: 10px 0;
            margin-bottom: 30px;
            height: 60px;
            border-bottom: 1px dotted #FF5600;
        }

        .logo {
            float: left;
            width: 60px;
            height: 60px;
            transform: translate(-50%, -50%);
        }

        .company-name {
            position: absolute;
            left: 50%;
            top: 30%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            font-weight: bold;
            color: #FF5600;
            text-align: center;
            white-space: nowrap;
            padding-bottom: 30px;
        }

        h1 {
            color: #FF5600;
            font-size: 20px;
            margin: 20px 0;
            text-align: center;
        }

        .summary {
            background-color: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #FF5600;
            border-radius: 4px;
        }

        .summary p {
            margin: 5px 0;
            font-size: 12px;
        }

        .summary strong {
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th {
            background-color: #FF5600;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }

        td {
            padding: 10px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Footer with page number */
        .footer {
            position: fixed;
            bottom: 10px;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        /* For PDF generators like dompdf */
        .pagenum:before {
            content: counter(page);
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <img src="{{ $logoPath }}" alt="Logo" class="logo">
        <div class="company-name">NEEMA GOSPEL CHOIR</div>
    </div>

    <h1>Users Report</h1>

    <!-- Summary -->
    <div class="summary">
        <p><strong>Generated:</strong> {{ $generated_at->format('Y-m-d H:i:s') }}</p>
        <p><strong>Date Range:</strong>
            @if ($summary['date_range']['start'])
                {{ $summary['date_range']['start'] }}
            @else
                All time
            @endif
            to
            @if ($summary['date_range']['end'])
                {{ $summary['date_range']['end'] }}
            @else
                Present
            @endif
        </p>
        <p><strong>Total Users:</strong> {{ $summary['total_users'] }}</p>
    </div>

    <!-- Users Table -->
    <table>
        <thead>
            <tr>
                <th>User ID#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Roles</th>
                <th>Status</th>
                <th>Registered Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $user->first_name }} {{ $user->surname }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->phone_number }}</td>
                    <td>{{ $user->roles->pluck('name')->join(', ') ?: 'No Role' }}</td>
                    <td>{{ ucfirst($user->status) }}</td>
                    <td>{{ $user->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Footer with page number -->
    <div class="footer">
        Page <span class="pagenum"></span><br> Generated at |
        {{ $generated_at->format('Y-m-d H:i:s') }}
    </div>
</body>

</html>
