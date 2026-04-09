<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1f2937; margin: 0; padding: 20px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .subtitle { color: #6b7280; font-size: 11px; margin-bottom: 24px; }
        h2 { font-size: 14px; margin: 20px 0 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #f3f4f6; text-align: left; padding: 6px 10px; font-size: 11px; text-transform: uppercase; color: #6b7280; }
        td { padding: 6px 10px; border-bottom: 1px solid #f3f4f6; }
        .text-right { text-align: right; }
        .no-data { color: #9ca3af; font-style: italic; padding: 12px 0; }
        .footer { margin-top: 32px; font-size: 10px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <h1>Laporan Penjualan</h1>
    <p class="subtitle">
        Periode: {{ $from->format('d/m/Y') }} &ndash; {{ $to->format('d/m/Y') }}
        &nbsp;&bull;&nbsp; Dicetak: {{ now()->format('d/m/Y H:i') }}
    </p>

    <h2>Produk Terlaris</h2>
    @if($topProducts->isEmpty())
        <p class="no-data">Tidak ada data untuk periode yang dipilih</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Produk</th>
                    <th class="text-right">Unit Terjual</th>
                    <th class="text-right">Total Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topProducts as $i => $product)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $product->product_name }}</td>
                        <td class="text-right">{{ number_format($product->units_sold) }}</td>
                        <td class="text-right">Rp {{ number_format((float) $product->total_revenue, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Performa per Kategori</h2>
    @if($categoryPerformance->isEmpty())
        <p class="no-data">Tidak ada data untuk periode yang dipilih</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th class="text-right">Unit Terjual</th>
                    <th class="text-right">Total Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categoryPerformance as $cat)
                    <tr>
                        <td>{{ $cat->category_name }}</td>
                        <td class="text-right">{{ number_format($cat->units_sold) }}</td>
                        <td class="text-right">Rp {{ number_format((float) $cat->total_revenue, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">POS Retail &mdash; Laporan dibuat otomatis oleh sistem</div>
</body>
</html>
