<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview {{ $title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: #eef2f6;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .toolbar {
            background: #234661;
            color: #fff;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 18px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .18);
            z-index: 10;
        }
        .toolbar .brand {
            font-weight: 800;
            font-size: 16px;
            letter-spacing: .5px;
            line-height: 1.1;
        }
        .toolbar .brand span {
            display: block;
            font-size: 10px;
            font-weight: 400;
            color: #cbd5e1;
            letter-spacing: 0;
        }
        .toolbar .title {
            font-size: 13px;
            color: #e2e8f0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .toolbar .spacer { flex: 1; }
        .toolbar a.btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            padding: 9px 16px;
            border-radius: 8px;
            transition: .15s;
            white-space: nowrap;
        }
        .btn-back {
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, .35);
            background: transparent;
        }
        .btn-back:hover { background: rgba(255, 255, 255, .1); }
        .btn-download {
            background: #E19404;
            color: #fff;
        }
        .btn-download:hover { background: #c78403; }
        .toolbar a.btn svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }
        .pdf-wrap {
            flex: 1;
            padding: 18px;
            overflow: hidden;
        }
        .pdf-wrap iframe {
            width: 100%;
            height: 100%;
            border: 0;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .14);
        }

        @media (max-width: 640px) {
            .toolbar {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                padding: 12px 16px;
            }
            .toolbar .brand,
            .toolbar .title {
                text-align: center;
            }
            .toolbar .spacer { display: none; }
            .toolbar a.btn {
                justify-content: center;
                padding: 12px 16px;
            }
            .btn-back { order: 1; }
            .btn-download { order: 2; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div class="brand">BACT 2027<span>Basic Advanced Course in Transfusion</span></div>
        <div class="title">{{ $title }}</div>
        <div class="spacer"></div>
        <a class="btn btn-back" href="{{ $backUrl }}">
            &larr; Kembali
        </a>
        <a class="btn btn-download" href="{{ $downloadUrl }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Download PDF
        </a>
    </div>
    <div class="pdf-wrap">
        <iframe src="{{ $pdfUrl }}" title="Preview Invoice"></iframe>
    </div>
</body>
</html>