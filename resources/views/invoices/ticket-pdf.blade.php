<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $booking->invoice_number }}</title>
    <style>
        @page {
        size: A4;
        margin: 18mm 18mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.5;
        }
        .invoice-page {
            padding: 18mm 20mm;
        }
        .header {
            border-bottom: 3px solid #E19404;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .brand h1 {
            font-size: 22px;
            color: #234661;
            letter-spacing: 0.5px;
        }
        .brand span {
            display: block;
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
        }
        .doc-title {
            text-align: right;
        }
        .doc-title h2 {
            font-size: 26px;
            color: #E19404;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        .doc-title p {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .meta-table td {
            padding: 4px 0;
            vertical-align: top;
        }
        .meta-table .label {
            color: #6b7280;
            width: 140px;
            font-size: 11px;
        }
        .meta-table .value {
            font-weight: 700;
            color: #111827;
        }
        .section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #9ca3af;
            margin-bottom: 8px;
        }
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items th {
            background: #234661;
            color: #ffffff;
            text-align: left;
            padding: 9px 10px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .items td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 12px;
        }
        .items td.amount { text-align: right; }
        .items .desc {
            font-weight: 700;
            color: #111827;
        }
        .total-box {
            width: 100%;
            margin-bottom: 24px;
        }
        .total-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .total-box td {
            padding: 6px 10px;
        }
        .total-box .grand {
            background: #FBE39D;
            border-top: 2px solid #E19404;
        }
        .total-box .grand td {
            font-size: 15px;
            font-weight: 800;
            color: #234661;
        }
        .status-paid {
            display: inline-block;
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
            padding: 4px 12px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 3px;
        }
        .footer {
            border-top: 1px solid #e5e7eb;
            margin-top: 32px;
            padding-top: 12px;
            font-size: 10px;
            color: #9ca3af;
            text-align: center;
        }
        .qr-note { font-size: 10px; color: #6b7280; margin-top: 6px; }
    </style>
</head>
<body>
    <div class="invoice-page">
        <div class="header">
            <table class="meta-table" style="margin-bottom:0;">
                <tr>
                    <td style="width:60%;">
                        <div class="brand">
                            <h1>BACT 2027</h1>
                            <span>Basic Advanced Course in Transfusion</span>
                        </div>
                    </td>
                    <td class="doc-title">
                        <h2>Invoice</h2>
                        <p>No. {{ $booking->invoice_number }}</p>
                    </td>
                </tr>
            </table>
        </div>

        <table class="meta-table">
            <tr>
                <td class="label">Tanggal Terbit</td>
                <td class="value">{{ ($booking->paid_at ?? $booking->created_at)->format('d M Y H:i') }}</td>
            </tr>
            <tr>
                <td class="label">Status</td>
                <td class="value"><span class="status-paid">Lunas</span></td>
            </tr>
        </table>

        <div class="section-title">Ditagihkan Kepada</div>
        <table class="meta-table">
            <tr>
                <td class="label">Nama</td>
                <td class="value">{{ $booking->name_with_title ?: $booking->full_name }}</td>
            </tr>
            <tr>
                <td class="label">Email</td>
                <td class="value">{{ $booking->gmail_account ?: $booking->user->email ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">WhatsApp</td>
                <td class="value">{{ $booking->whatsapp_number ?: $booking->user->phone_number ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Instansi</td>
                <td class="value">{{ $booking->institution_name ? $booking->institution_name . ' (' . $booking->institution_city . ')' : '-' }}</td>
            </tr>
        </table>

        <div class="section-title">Rincian Pembelian</div>
        <table class="items">
            <thead>
                <tr>
                    <th>Deskripsi</th>
                    <th style="text-align:center;">Qty</th>
                    <th style="text-align:right;">Harga</th>
                    <th style="text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="desc">{{ ($booking->ticket_name ? $booking->ticket_name . ' - ' : '') . $booking->ticket_category }}</td>
                    <td style="text-align:center;">1</td>
                    <td class="amount">Rp {{ number_format($booking->amount, 0, ',', '.') }}</td>
                    <td class="amount">Rp {{ number_format($booking->amount, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="total-box">
            <table>
                <tr>
                    <td style="width:70%;"></td>
                    <td>
                        <table>
                            <tr>
                                <td class="label" style="width:60%;">Subtotal</td>
                                <td class="value" style="text-align:right;">Rp {{ number_format($booking->amount, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="grand">
                                <td class="label" style="width:60%; font-weight:800;">Total</td>
                                <td style="text-align:right;">Rp {{ number_format($booking->amount, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <p class="qr-note">Kode Tiket: <b>{{ $booking->checkin_token }}</b> — gunakan untuk proses check-in pada hari-H.</p>
        <p class="qr-note">Invoice ini diterbitkan secara otomatis oleh sistem pendaftaran BACT 2027.</p>

        <div class="footer">
            Panitia BACT 2027 &bull; bactyogyakarta.com
        </div>
    </div>
</body>
</html>