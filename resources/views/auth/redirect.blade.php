<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đang chuyển hướng sang Google...</title>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: #f4f7f6;
            color: #333;
        }

        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        p {
            font-size: 14px;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <div class="loader"></div>
    <p>Đang kết nối với Google...</p>
    <script>
        // Chuyển hướng sang Google sau khi trang đã load
        window.location.href = "{!! $url !!}";
    </script>
</body>

</html>