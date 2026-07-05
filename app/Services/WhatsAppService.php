<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl = 'https://api.fonnte.com/send';
    private string $token;
    private string $countryCode;

    public function __construct()
    {
        $this->token       = config('services.fonnte.token', '');
        $this->countryCode = config('services.fonnte.country_code', '62');
    }

    // ── Core Send ────────────────────────────────────────────────────────
    public function send(string $phone, string $message): array
    {
        if (empty($this->token)) {
            Log::warning('[WhatsApp] Token tidak dikonfigurasi');
            return ['status' => false, 'message' => 'Token not configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($this->apiUrl, [
                'target'  => $this->formatPhone($phone),
                'message' => $message,
                'delay'   => 1,
                'type'    => 'text',
            ]);

            $result = $response->json();

            Log::info('[WhatsApp] Pesan terkirim', [
                'phone'  => $this->formatPhone($phone),
                'status' => $result['status'] ?? false,
            ]);

            return $result;
        } catch (\Throwable $e) {
            Log::error('[WhatsApp] Error: ' . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    // ── Template Messages ─────────────────────────────────────────────────
    public function sendSaleReceipt(string $phone, array $sale): array
    {
        $items = '';
        foreach ($sale['items'] as $i => $item) {
            $no       = $i + 1;
            $price    = 'Rp ' . number_format($item['unit_price'], 0, ',', '.');
            $subtotal = 'Rp ' . number_format($item['subtotal'], 0, ',', '.');
            $items   .= "{$no}. *{$item['product_name']}*\n";
            $items   .= "   {$item['quantity']} x {$price} = {$subtotal}\n";
        }

        $total  = 'Rp ' . number_format($sale['total_amount'],  0, ',', '.');
        $paid   = 'Rp ' . number_format($sale['paid_amount'],   0, ',', '.');
        $change = 'Rp ' . number_format($sale['change_amount'], 0, ',', '.');

        $message = "🧾 *STRUK PEMBELIAN*\n"
            . "━━━━━━━━━━━━━━━━\n"
            . "No. Transaksi : {$sale['code']}\n"
            . "Tanggal       : {$sale['date']}\n"
            . "Kasir         : {$sale['cashier']}\n\n"
            . "*Produk yang dibeli:*\n"
            . $items
            . "━━━━━━━━━━━━━━━━\n"
            . "Total    : {$total}\n"
            . "Bayar    : {$paid}\n"
            . "Kembali  : {$change}\n\n"
            . "✅ Terima kasih sudah berbelanja!\n"
            . "📍 _ARUKAS_";

        return $this->send($phone, $message);
    }

    public function sendLowStockAlert(string $phone, array $products): array
    {
        $list = '';
        foreach ($products as $p) {
            $list .= "• *{$p['name']}*\n";
            $list .= "  Stok: {$p['stock']} {$p['unit']} (Min: {$p['min_stock']})\n";
        }

        $message = "⚠️ *PERINGATAN STOK RENDAH*\n"
            . "━━━━━━━━━━━━━━━━\n"
            . "Produk berikut stoknya hampir habis:\n\n"
            . $list
            . "\nSegera lakukan pembelian!\n"
            . "📍 _ARUKAS_";

        return $this->send($phone, $message);
    }

    public function sendPaymentConfirmation(string $phone, array $data): array
    {
        $total   = 'Rp ' . number_format($data['total_amount'], 0, ',', '.');
        $message = "✅ *PEMBAYARAN BERHASIL*\n"
            . "━━━━━━━━━━━━━━━━\n"
            . "Transaksi : {$data['code']}\n"
            . "Total     : {$total}\n"
            . "Metode    : " . strtoupper($data['payment_method']) . "\n"
            . "Status    : *LUNAS*\n\n"
            . "Terima kasih! 🙏\n"
            . "📍 _ARUKAS_";

        return $this->send($phone, $message);
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = $this->countryCode . substr($phone, 1);
        } elseif (!str_starts_with($phone, $this->countryCode)) {
            $phone = $this->countryCode . $phone;
        }

        return $phone;
    }
}
