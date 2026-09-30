<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serah Terima - {{ $booking->nomor_pesanan }}</title>
    <style>
        .no-print { display: none !important; }
    </style>
</head>
<body>
    <x-serah-terima :booking="$booking" :surat="$surat" />

    <div class="no-print" style="position:fixed;top:1rem;left:50%;transform:translateX(-50%);z-index:9999;text-align:center;font-family:sans-serif;background:#f1f5f9;border-bottom:1px solid #e2e8f0;border-radius:12px;padding:0.75rem 1.5rem;">
        <button onclick="window.print()" style="padding:0.5rem 1.5rem;font-size:1rem;font-weight:600;background:#2563eb;color:#fff;border:none;border-radius:8px;cursor:pointer;margin-right:0.5rem;">Cetak</button>
        <button onclick="window.close()" style="padding:0.5rem 1.5rem;font-size:1rem;font-weight:600;background:#e2e8f0;color:#475569;border:none;border-radius:8px;cursor:pointer;">Tutup</button>
    </div>

    <script>
        window.onload = function() { setTimeout(function() { window.print(); }, 500); };
        window.onafterprint = function() { window.close(); };
    </script>
</body>
</html>
