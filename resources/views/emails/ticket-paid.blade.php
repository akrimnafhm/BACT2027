<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Tiket BACT</title>
</head>
<body style="margin:0;padding:0;background-color:#F4F5F7;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F4F5F7;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:24px 32px;border-bottom:1px solid #f3f4f6;">
                            <img src="{{ asset('images/logo.png') }}" alt="BACT 2027" style="height:48px;width:auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <div style="font-size:15px;line-height:1.7;color:#374151;">
                                {!! $bodyHtml !!}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;background-color:#FFFDF5;border-top:1px solid #f3f4f6;font-size:12px;color:#9ca3af;line-height:1.6;">
                            Email ini dikirim otomatis oleh sistem BACT 2027.<br>
                            Simposium Nasional Medis &amp; Kesehatan.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
