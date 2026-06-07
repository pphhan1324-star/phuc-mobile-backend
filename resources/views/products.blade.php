<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách Người dùng</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #c29d59;
            --bg: #1a1a1a;
            --card: #262626;
            --text: #ffffff;
            --border: #333;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        h1 {
            font-size: 2rem;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 40px;
        }
        .container {
            max-width: 800px;
            width: 100%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card);
            border-radius: 10px;
            overflow: hidden;
        }
        thead tr {
            background-color: var(--primary);
            color: #000;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.1em;
        }
        th, td {
            padding: 14px 20px;
            text-align: left;
        }
        tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.2s;
        }
        tbody tr:last-child {
            border-bottom: none;
        }
        tbody tr:hover {
            background-color: #2f2f2f;
        }
        .id-badge {
            display: inline-block;
            background: var(--primary);
            color: #000;
            font-weight: 700;
            font-size: 0.8rem;
            padding: 2px 10px;
            border-radius: 20px;
        }
        .footer {
            margin-top: 40px;
            color: #555;
            font-size: 0.8rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="text-align: center;">Danh sách Người dùng</h1>
        <p class="subtitle" style="text-align: center;">
            <span style="color: #4ade80;">DB Connected</span> &nbsp;|&nbsp; Tổng: <strong>{{ $users->count() }}</strong> người dùng
        </p>
s
        <table>
            <thead>
                <tr>
                    <th style="text-align: center;">ID</th>
                    <th style="text-align: center;">Tên</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td style="text-align: center;"><span class="id-badge">#{{ $user->id }}</span></td>
                        <td style="text-align: center;">{{ $user->name }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="text-align: center; color: #888; padding: 30px;">
                            Chưa có người dùng nào trong hệ thống.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>