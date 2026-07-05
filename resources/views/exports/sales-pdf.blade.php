<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; margin: 0; padding: 0; }
        .container { width: 100%; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; padding: 0; }
        .header p { margin: 5px 0 0; color: #555; }
        .summary-box { border: 1px solid #ccc; padding: 10px; margin-bottom: 20px; background-color: #f9f9f9; width: 48%; float: left; }
        .summary-box.right { float: right; }
        .summary-box h3 { margin-top: 0; font-size: 13px; }
        .summary-box p { margin: 3px 0; }
        .table-container { clear: both; width: 100%; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; vertical-align: top; }
        th { background-color: #eee; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; background-color: #f0f0f0; }
        .badge-paid { color: #166534; font-weight: bold; }
        .badge-pending { color: #854d0e; font-weight: bold; }
        .badge-failed { color: #991b1b; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>Laporan Penjualan</h2>
        <p>Periode: {{ $start }} s/d {{ $end }}</p>
        <p>Halaman ini dicetak pada: {{ $generated }}</p>
    </div>

    @if(isset($summary))
    <div class="summary-box">
        <h3>Ringkasan Umum (Status: Paid)</h3>
        <p>Total Transaksi: <strong>{{ number_format($summary->total_transaksi, 0, ',', '.') }}</strong></p>
        <p>Total Pendapatan: <strong>Rp {{ number_format($summary->total_pendapatan, 0, ',', '.') }}</strong></p>
        <p>Total Diskon: <strong>Rp {{ number_format($summary->total_diskon, 0, ',', '.') }}</strong></p>
    </div>
    @endif

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th class="text-center" width="5%">No</th>
                    <th width="15%">Kode</th>
                    <th width="12%">Tanggal</th>
                    <th width="15%">Kasir</th>
                    <th width="18%">Pelanggan</th>
                    <th class="text-right" width="10%">Subtotal</th>
                    <th class="text-right" width="8%">Diskon</th>
                    <th class="text-right" width="10%">Total</th>
                    <th class="text-center" width="8%">Metode</th>
                    <th class="text-center" width="8%">Status</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalSubtotal = 0;
                    $totalDiscount = 0;
                    $totalAmount = 0;
                @endphp
                @foreach($sales as $index => $sale)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $sale->code }}</td>
                    <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $sale->user->name ?? '-' }}</td>
                    <td>{{ $sale->customer_name ?? ($sale->customer->name ?? '-') }}</td>
                    <td class="text-right">Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($sale->discount, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $sale->payment_method }}</td>
                    <td class="text-center">
                        @if($sale->payment_status === 'paid')
                            <span class="badge-paid">Paid</span>
                        @elseif($sale->payment_status === 'pending')
                            <span class="badge-pending">Pending</span>
                        @else
                            <span class="badge-failed">Failed</span>
                        @endif
                    </td>
                </tr>
                @php
                    $totalSubtotal += $sale->subtotal;
                    $totalDiscount += $sale->discount;
                    $totalAmount += $sale->total_amount;
                @endphp
                @endforeach
                <tr class="total-row">
                    <td colspan="5" class="text-right">TOTAL</td>
                    <td class="text-right">Rp {{ number_format($totalSubtotal, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($totalDiscount, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>