<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Peringatan Stok Rendah</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:'Helvetica Neue',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0"
          style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.07);">

          <tr>
            <td style="background:linear-gradient(135deg,#d97706,#ef4444);padding:32px 40px;text-align:center;">
              <div style="font-size:36px;margin-bottom:8px;">⚠️</div>
              <h1 style="color:#fff;margin:0;font-size:20px;font-weight:700;">Peringatan Stok Rendah</h1>
              <p style="color:rgba(255,255,255,0.85);margin:4px 0 0;font-size:13px;">
                {{ $products->count() }} produk perlu segera diisi ulang
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding:32px 40px;">
              <p style="margin:0 0 24px;font-size:14px;color:#374151;line-height:1.6;">
                Produk-produk berikut memiliki stok di bawah batas minimum yang ditetapkan.
                Segera lakukan pembelian untuk menghindari kehabisan stok.
              </p>

              <table width="100%" style="border-collapse:collapse;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
                <thead>
                  <tr style="background:#fef3c7;">
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#92400e;text-transform:uppercase;letter-spacing:0.5px;">Produk</th>
                    <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#92400e;text-transform:uppercase;letter-spacing:0.5px;">Stok</th>
                    <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#92400e;text-transform:uppercase;letter-spacing:0.5px;">Min. Stok</th>
                    <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#92400e;text-transform:uppercase;letter-spacing:0.5px;">Status</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($products as $product)
                  <tr style="border-top:1px solid #f3f4f6;{{ $loop->even ? 'background:#fafafa;' : '' }}">
                    <td style="padding:12px 16px;">
                      <div style="font-size:13px;font-weight:500;color:#111827;">{{ $product->name }}</div>
                      <div style="font-size:11px;color:#9ca3af;margin-top:2px;">{{ $product->code }}</div>
                    </td>
                    <td style="padding:12px 16px;text-align:center;">
                      <span style="background:#fee2e2;color:#991b1b;font-size:13px;font-weight:700;padding:3px 10px;border-radius:20px;">
                        {{ $product->stock }} {{ $product->unit }}
                      </span>
                    </td>
                    <td style="padding:12px 16px;text-align:center;font-size:13px;color:#6b7280;">
                      {{ $product->min_stock }} {{ $product->unit }}
                    </td>
                    <td style="padding:12px 16px;text-align:center;">
                      @if($product->stock == 0)
                        <span style="background:#fecaca;color:#7f1d1d;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;">HABIS</span>
                      @else
                        <span style="background:#fef3c7;color:#78350f;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;">RENDAH</span>
                      @endif
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </td>
          </tr>

          <tr>
            <td style="background:#f9fafb;padding:24px 40px;text-align:center;border-top:1px solid #e5e7eb;">
              <p style="margin:0;font-size:12px;color:#9ca3af;">
                Notifikasi otomatis dari ARUKAS.<br/>
                Periksa halaman <strong>Pembelian</strong> untuk membuat purchase order.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>