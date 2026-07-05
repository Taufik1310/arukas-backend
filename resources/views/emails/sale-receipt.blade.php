<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Struk Pembelian</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:'Helvetica Neue',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0"
          style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.07);">

          {{-- Header --}}
          <tr>
            <td style="background:linear-gradient(135deg,#2563eb,#4f46e5);padding:32px 40px;text-align:center;">
              <div style="font-size:28px;margin-bottom:8px;">🛒</div>
              <h1 style="color:#fff;margin:0;font-size:22px;font-weight:700;">ARUKAS</h1>
              <p style="color:rgba(255,255,255,0.8);margin:4px 0 0;font-size:13px;">Struk Pembelian Digital</p>
            </td>
          </tr>

          {{-- Content --}}
          <tr>
            <td style="padding:32px 40px;">

              {{-- Greeting --}}
              @if($transaction->customer_name)
              <p style="margin:0 0 24px;font-size:15px;color:#374151;">
                Halo, <strong>{{ $transaction->customer_name }}</strong>!<br/>
                Terima kasih sudah berbelanja di toko kami.
              </p>
              @else
              <p style="margin:0 0 24px;font-size:15px;color:#374151;">
                Terima kasih sudah berbelanja di toko kami!
              </p>
              @endif

              {{-- Transaction Info --}}
              <table width="100%" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:24px;">
                <tr style="background:#f9fafb;">
                  <td style="padding:10px 16px;font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">
                    Detail Transaksi
                  </td>
                </tr>
                <tr>
                  <td style="padding:16px;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="font-size:13px;color:#6b7280;padding-bottom:8px;">No. Transaksi</td>
                        <td style="font-size:13px;color:#111827;font-weight:600;text-align:right;padding-bottom:8px;">{{ $transaction->code }}</td>
                      </tr>
                      <tr>
                        <td style="font-size:13px;color:#6b7280;padding-bottom:8px;">Tanggal</td>
                        <td style="font-size:13px;color:#111827;text-align:right;padding-bottom:8px;">
                          {{ $transaction->created_at->format('d M Y, H:i') }} WIB
                        </td>
                      </tr>
                      <tr>
                        <td style="font-size:13px;color:#6b7280;">Kasir</td>
                        <td style="font-size:13px;color:#111827;text-align:right;">{{ $transaction->user->name ?? '-' }}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              {{-- Items --}}
              <table width="100%" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:24px;">
                <tr style="background:#f9fafb;">
                  <td style="padding:10px 16px;font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Produk</td>
                  <td style="padding:10px 16px;font-size:12px;font-weight:600;color:#6b7280;text-align:center;">Qty</td>
                  <td style="padding:10px 16px;font-size:12px;font-weight:600;color:#6b7280;text-align:right;">Subtotal</td>
                </tr>
                @foreach($transaction->items as $item)
                <tr style="border-top:1px solid #f3f4f6;">
                  <td style="padding:12px 16px;">
                    <div style="font-size:13px;font-weight:500;color:#111827;">{{ $item->product->name ?? 'Produk' }}</div>
                    <div style="font-size:12px;color:#9ca3af;margin-top:2px;">
                      Rp {{ number_format($item->unit_price, 0, ',', '.') }} / unit
                    </div>
                  </td>
                  <td style="padding:12px 16px;text-align:center;font-size:13px;color:#374151;">{{ $item->quantity }}</td>
                  <td style="padding:12px 16px;text-align:right;font-size:13px;font-weight:500;color:#111827;">
                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                  </td>
                </tr>
                @endforeach
              </table>

              {{-- Total --}}
              <table width="100%" style="border:2px solid #2563eb;border-radius:10px;overflow:hidden;margin-bottom:24px;background:#eff6ff;">
                <tr>
                  <td style="padding:16px 20px;">
                    <table width="100%">
                      @if($transaction->discount > 0)
                      <tr>
                        <td style="font-size:13px;color:#6b7280;padding-bottom:8px;">Subtotal</td>
                        <td style="font-size:13px;color:#374151;text-align:right;padding-bottom:8px;">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</td>
                      </tr>
                      <tr>
                        <td style="font-size:13px;color:#ef4444;padding-bottom:8px;">Diskon</td>
                        <td style="font-size:13px;color:#ef4444;text-align:right;padding-bottom:8px;">- Rp {{ number_format($transaction->discount, 0, ',', '.') }}</td>
                      </tr>
                      @endif
                      <tr>
                        <td style="font-size:16px;font-weight:700;color:#1d4ed8;">Total Pembayaran</td>
                        <td style="font-size:18px;font-weight:700;color:#1d4ed8;text-align:right;">
                          Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}
                        </td>
                      </tr>
                      <tr>
                        <td style="font-size:12px;color:#6b7280;padding-top:4px;">
                          Metode: {{ strtoupper($transaction->payment_method) }}
                        </td>
                        <td style="text-align:right;padding-top:4px;">
                          <span style="background:#d1fae5;color:#065f46;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;">
                            ✓ LUNAS
                          </span>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td style="background:#f9fafb;padding:24px 40px;text-align:center;border-top:1px solid #e5e7eb;">
              <p style="margin:0 0 8px;font-size:14px;color:#374151;font-weight:600;">Terima kasih telah berbelanja! 🙏</p>
              <p style="margin:0;font-size:12px;color:#9ca3af;">
                Email ini dikirim otomatis oleh ARUKAS.<br/>
                Simpan email ini sebagai bukti transaksi Anda.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>