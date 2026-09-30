<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak - {{ $template->judul }}</title>
    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            color: #1e293b;
        }

        .print-wrapper {
            width: 794px;
            margin: 0 auto;
            padding: 120px;
            position: relative;
            min-height: 1123px;
        }

        .kop-surat-header {
            border-bottom: 1px solid #e2e8f0;
            background: #fff;
            padding: 1rem;
            width: 794px;
            margin: 0 auto;
        }

        .kop-surat-header img {
            display: block;
            width: 100%;
            height: auto;
        }

        .content {
            position: relative;
            z-index: 1;
        }

        .content::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url('/watermark-page');
            background-repeat: repeat-y;
            background-position: center;
            background-size: 554px 1123px;
            pointer-events: none;
            z-index: 0;
        }

        .content > * {
            position: relative;
            z-index: 1;
        }

        .content h1 { font-size: 1.875rem; font-weight: 800; color: #0f172a; margin-top: 2rem; }
        .content h2 { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-top: 1.5rem; }
        .content h3 { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-top: 1.25rem; }
        .content h4 { font-size: 1.125rem; font-weight: 600; color: #0f172a; margin-top: 1rem; }
        .content h5 { font-size: 1rem; font-weight: 600; color: #0f172a; margin-top: 0.75rem; }
        .content h6 { font-size: 0.875rem; font-weight: 600; color: #0f172a; margin-top: 0.75rem; }
        .content p { font-size: 0.875rem; line-height: 1.625; color: #334155; margin-top: 0.75rem; }
        .content p:first-child { margin-top: 0; }
        .content ul, .content ol { padding-left: 1.25rem; font-size: 0.875rem; color: #334155; margin-top: 0.75rem; }
        .content li { margin-top: 0.25rem; }
        .content hr { margin-top: 1.5rem; margin-bottom: 1.5rem; border: 0; border-top: 1px solid #e2e8f0; }
        .content blockquote { border-left: 4px solid #3b82f6; padding-left: 1rem; font-style: italic; color: #475569; margin-top: 0.75rem; }
        .content table { width: 100%; border-collapse: collapse; margin-top: 0.75rem; font-size: 0.875rem; }
        .content th, .content td { border: 1px solid #cbd5e1; padding: 0.5rem 0.75rem; text-align: left; }
        .content th { background: #f1f5f9; font-weight: 600; color: #0f172a; }
        .content a { color: #2563eb; text-decoration: underline; }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center;padding:1rem;font-family:sans-serif;background:#f1f5f9;border-bottom:1px solid #e2e8f0;">
        <button onclick="window.print()" style="padding:0.5rem 1.5rem;font-size:1rem;font-weight:600;background:#2563eb;color:#fff;border:none;border-radius:8px;cursor:pointer;margin-right:0.5rem;">
            Cetak
        </button>
        <button onclick="window.close()" style="padding:0.5rem 1.5rem;font-size:1rem;font-weight:600;background:#e2e8f0;color:#475569;border:none;border-radius:8px;cursor:pointer;">
            Tutup
        </button>
    </div>

    <div class="kop-surat-header">
        <img src="/storage/img/kop.png" alt="Kop Surat">
    </div>

    <div class="print-wrapper">
        <div class="content">
            {!! $template->konten !!}
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>