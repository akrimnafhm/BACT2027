<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $reservation->invoice_number }}</title>
    <style>
        @page { margin: 32px 36px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
                        <p>No. {{ $reservation->invoice_number }}</p>
                    </td>
                </tr>
            </table>
        </div>

        <table class="meta-table">
            <tr>
                <td class="label">Tanggal Terbit</td>
                <td class="value">{{ $reservation->created_at->format('d M Y H:i') }}</td>
            </tr>
            <tr>
                <td class="label">Kode Booking</td>
                <td class="value">{{ $reservation->booking_code }}</td>
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
                <td class="value">{{ $reservation->guest_name ?: $reservation->user->name ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Email</td>
                <td class="value">{{ $reservation->guest_email ?: $reservation->user->email ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">WhatsApp</td>
                <td class="value">{{ $reservation->guest_phone ?: $reservation->user->phone_number ?? '' }}</td>
            </tr>
        </table>

        <div class="section-title">Rincian Menginap</div>
        <table class="items">
            <thead>
                <tr>
                    <th>Deskripsi</th>
                    <th style="text-align:center;">Qty (Malam)</th>
                    <th style="text-align:right;">Harga/Malam</th>
                    <th style="text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="desc">{{ $reservation->hotelRoom->room_type ?? 'Kamar Hotel' }}</td>
                    <td style="text-align:center;">{{ $reservation->total_nights }}</td>
                    <td class="amount">Rp {{ number_format($reservation->total_price / max(1, $reservation->total_nights), 0, ',', '.') }}</td>
                    <td class="amount">Rp {{ number_format($reservation->total_price, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <table class="meta-table">
            <tr>
                <td class="label">Check-in</td>
                <td class="value">{{ $reservation->check_in->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="label">Check-out</td>
                <td class="value">{{ $reservation->check_out->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="label">Metode Pembayaran</td>
                <td class="value">{{ $reservation->payment_method ?: 'DOKU Payment Gateway' }}</td>
            </tr>
        </table>

        <div class="total-box">
            <table>
                <tr>
                    <td style="width:70%;"></td>
                    <td>
                        <table>
                            <tr>
                                <td class="label" style="width:60%;">Subtotal</td>
                                <td class="value" style="text-align:right;">Rp {{ number_format($reservation->total_price, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="grand">
                                <td class="label" style="width:60%; font-weight:800;">Total</td>
                                <td style="text-align:right;">Rp {{ number_format($reservation->total_price, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <p style="font-size:10px; color:#6b7280;">Invoice ini diterbitkan secara otomatis oleh sistem pendaftaran BACT 2027.</p>

        <div class="footer">
            Panitia BACT 2027 &bull; bactyogyakarta.com
        </div>
    </div>
</body>
</html>