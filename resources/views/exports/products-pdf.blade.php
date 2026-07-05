<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Produk</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; } /* Ukuran font dikecilkan agar muat banyak kolom */
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; }
        .header p { margin: 5px 0; color: #555; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge-active { color: #166534; font-weight: bold; }
        .badge-inactive { color: #991b1b; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Laporan Data Produk</h2>
        <p>Tanggal Cetak: {{ $generated }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" width="3%">No</th>
                <th width="10%">Kode</th>
                <th width="18%">Nama Produk</th>
                <th width="12%">Kategori</th>
                <th class="text-center" width="5%">Stok</th>
                <th class="text-center" width="7%">Min Stok</th>
                <th class="text-right" width="10%">Harga Beli</th>
                <th class="text-right" width="10%">Harga Jual</th>
                <th class="text-center" width="6%">Satuan</th>
                <th class="text-center" width="7%">Status</th>
                <th width="12%">Barcode</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $index => $product)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $product->code ?? '-' }}</td>
                <td>{{ $product->name }}</td>
                <td>{{ $product->category->name ?? '-' }}</td>
                <td class="text-center">{{ $product->stock }}</td>
                <td class="text-center">{{ $product->min_stock ?? 0 }}</td>
                <td class="text-right">Rp {{ number_format($product->purchase_price ?? 0, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($product->sale_price ?? 0, 0, ',', '.') }}</td>
                <td class="text-center">{{ $product->unit ?? '-' }}</td>
                <td class="text-center">
                    @if($product->is_active)
                        <span class="badge-active">Aktif</span>
                    @else
                        <span class="badge-inactive">Nonaktif</span>
                    @endif
                </td>
                <td>{{ $product->barcode ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>