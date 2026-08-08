<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email - BACT 2027</title>
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
                                <p>Halo, <strong>{{ $userName }}</strong>,</p>
                                <p>Untuk melanjutkan pembelian tiket atau pemesanan hotel, mohon verifikasi alamat email Anda terlebih dahulu.</p>
                                <p>Masukkan kode verifikasi berikut:</p>
                                <p style="text-align:center;margin:28px 0;">
                                    <span style="display:inline-block;font-size:30px;font-weight:800;letter-spacing:8px;color:#1f2937;background:#FFF8E7;border:2px dashed #E19404;border-radius:12px;padding:14px 28px;">{{ $code }}</span>
                                </p>
                                <p>Kode berlaku selama <strong>{{ $expiresInMinutes }} menit</strong>. Jika Anda tidak merasa meminta kode ini, abaikan email ini.</p>
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
