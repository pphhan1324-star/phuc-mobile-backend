<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu</title>
</head>

<body style="margin:0;background:#f4f4f4;font-family:Segoe UI, Arial, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:40px 0;">
        <tr>
            <td align="center">

                <table width="600" cellpadding="0" cellspacing="0"
                    style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 10px 25px rgba(0,0,0,0.06);">

                    <tr>
                        <td style="background:#1e1e1e;color:#ffffff;padding:30px;text-align:center;">
                            <h2 style="margin:0;font-weight:600;">Yêu cầu đặt lại mật khẩu</h2>
                            <p style="margin:8px 0 0;color:#cfcfcf;font-size:14px;">
                                Hệ thống bảo mật tài khoản
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:40px 35px;color:#333;font-size:15px;line-height:1.7;">

                            <p>Xin chào,</p>

                            <p>
                                Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.
                                Nhấn nút bên dưới để tạo mật khẩu mới.
                            </p>

                            <div style="text-align:center;margin:30px 0;">
                                <a href="{{ $resetLink }}" style="
background:#2c7be5;
color:#ffffff;
padding:14px 30px;
text-decoration:none;
border-radius:8px;
font-weight:600;
display:inline-block;
font-size:15px;
">
                                    Đặt lại mật khẩu
                                </a>
                            </div>

                            <p>
                                Liên kết này sẽ hết hạn sau <strong>60 phút</strong> vì lý do bảo mật.
                            </p>

                            <p>
                                Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email.
                                Tài khoản của bạn vẫn an toàn.
                            </p>

                            <hr style="border:none;border-top:1px solid #eeeeee;margin:30px 0;">

                            <p style="font-size:13px;color:#777;">
                                Nếu nút không hoạt động, sao chép và dán liên kết sau vào trình duyệt:
                            </p>

                            <p style="word-break:break-all;font-size:13px;color:#2c7be5;">
                                {{ $resetLink }}
                            </p>

                        </td>
                    </tr>

                    <tr>
                        <td style="background:#fafafa;padding:25px;text-align:center;font-size:12px;color:#888;">
                            © {{ date('Y') }} Nội thất cao cấp
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>