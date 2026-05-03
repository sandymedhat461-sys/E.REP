<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>E-Rep — Admin Report</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            margin: 0;
            padding: 20px;
            background: #ffffff;
        }

        .header {
            border-bottom: 3px solid #1a5276;
            padding-bottom: 14px;
            margin-bottom: 20px;
        }

        .logo {
            font-size: 22px;
            font-weight: bold;
            color: #1a5276;
            letter-spacing: 0.5px;
        }

        .gen-date {
            font-size: 10px;
            color: #555;
            margin-top: 6px;
        }

        h2 {
            font-size: 12px;
            color: #ffffff;
            background: #1a5276;
            padding: 8px 10px;
            margin: 18px 0 0 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 16px 0;
            font-size: 10px;
        }

        th {
            background: #e8f0f5;
            color: #1a5276;
            text-align: left;
            padding: 7px 8px;
            border: 1px solid #c5d9e8;
            font-weight: bold;
        }

        td {
            padding: 7px 8px;
            border: 1px solid #d0d0d0;
            vertical-align: top;
        }

        tr:nth-child(even) td {
            background: #fafafa;
        }

        .meta {
            margin-bottom: 16px;
        }

        .meta-row {
            margin-bottom: 4px;
        }

        .meta-label {
            font-weight: bold;
            color: #1a5276;
            display: inline-block;
            width: 140px;
        }

        .muted {
            color: #777;
            font-style: italic;
        }

        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            font-size: 9px;
            color: #888;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">E-Rep</div>
        <div class="gen-date">Report generated: {{ $generated_at->format('F j, Y \a\t g:i A') }}</div>
    </div>

    <div class="meta">
        <div class="meta-row"><span class="meta-label">Admin name</span> {{ $admin->full_name ?? $admin->email }}</div>
    </div>

    <h2>Summary</h2>
    <table>
        <thead>
            <tr>
                <th>Metric</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Doctors total</td>
                <td>{{ $total_doctors }}</td>
            </tr>
            <tr>
                <td>Reps total</td>
                <td>{{ $total_reps }}</td>
            </tr>
            <tr>
                <td>Companies total</td>
                <td>{{ $total_companies }}</td>
            </tr>
            <tr>
                <td>Drugs total</td>
                <td>{{ $total_drugs }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Meetings</h2>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Count</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total meetings</td>
                <td>{{ $total_meetings }}</td>
            </tr>
            <tr>
                <td>Completed meetings</td>
                <td>{{ $meetings_completed }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Drug samples</h2>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Count</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total samples</td>
                <td>{{ $total_samples }}</td>
            </tr>
            <tr>
                <td>Delivered samples</td>
                <td>{{ $samples_delivered }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Posts</h2>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Count</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total posts</td>
                <td>{{ $total_posts }}</td>
            </tr>
            <tr>
                <td>Reported posts</td>
                <td>{{ $reported_posts }}</td>
            </tr>
        </tbody>
    </table>

    <p class="footer">E-Rep confidential admin report — for authorized use only.</p>
</body>

</html>