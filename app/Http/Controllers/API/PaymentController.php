<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\SaleTransaction;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Midtrans\Config;
use Midtrans\Snap;

class PaymentController extends Controller
{
    public function createPayment(SaleTransaction $saleTransaction): JsonResponse
    {
        if ($saleTransaction->payment_status === 'paid') {
            return response()->json(['status' => false, 'message' => 'Transaksi sudah dibayar.'], 422);
        }

        Config::$serverKey    = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized  = true;
        Config::$is3ds        = true;

        $orderId = 'POS-' . $saleTransaction->code . '-' . time();

        $saleTransaction->load('items.product');

        $params = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $saleTransaction->total_amount,
            ],
            'item_details'     => $saleTransaction->items->map(fn($item) => [
                'id'       => (string) $item->product_id,
                'price'    => (int) $item->unit_price,
                'quantity' => $item->quantity,
                'name'     => substr($item->product->name, 0, 50),
            ])->all(),
            'customer_details' => [
                'first_name' => $saleTransaction->customer_name ?? 'Customer',
                'email'      => $saleTransaction->customer_email ?? 'customer@pos.local',
                'phone'      => $saleTransaction->customer_phone ?? '08000000000',
            ],
            'callbacks' => [
                'finish' => config('app.frontend_url') . '/pos/success?order=' . $orderId,
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            $saleTransaction->update([
                'midtrans_order_id' => $orderId,
                'midtrans_token'    => $snapToken,
            ]);

            return response()->json([
                'status'     => true,
                'snap_token' => $snapToken,
                'order_id'   => $orderId,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Gagal membuat pembayaran: ' . $e->getMessage()], 500);
        }
    }

    public function callback(Request $request): JsonResponse
    {
        $serverKey = config('midtrans.server_key');
        $hashed = hash('sha512',
            $request->order_id . $request->status_code . $request->gross_amount . $serverKey
        );

        if ($hashed !== $request->signature_key) {
            return response()->json(['message' => 'Invalid signature key.'], 403);
        }

        $sale = SaleTransaction::where('midtrans_order_id', $request->order_id)->first();
        if (!$sale) {
            return response()->json(['message' => 'Transaksi tidak ditemukan.'], 404);
        }

        $transStatus = $request->transaction_status;
        $fraudStatus = $request->fraud_status ?? 'accept';

        if (in_array($transStatus, ['capture', 'settlement']) && $fraudStatus === 'accept') {
            if ($sale->payment_status !== 'paid') {
                $sale->update(['payment_status' => 'paid', 'paid_amount' => $sale->total_amount]);

                // Kurangi stok jika belum
                foreach ($sale->items as $item) {
                    Product::where('id', $item->product_id)->decrement('stock', $item->quantity);
                }

                // Kirim notifikasi email
                if ($sale->customer_email) {
                    try {
                        Mail::to($sale->customer_email)->send(new \App\Mail\PaymentSuccessMail($sale));
                    } catch (\Exception $e) {}
                }

                // Kirim WhatsApp
                if ($sale->customer_phone) {
                    WhatsAppService::sendReceipt($sale);
                }

                ActivityLog::create([
                    'user_id'      => $sale->user_id,
                    'action'       => 'PAYMENT',
                    'description'  => "Pembayaran Midtrans untuk transaksi {$sale->code} berhasil.",
                    'subject_type' => 'SaleTransaction',
                    'subject_id'   => $sale->id,
                ]);
            }
        } elseif (in_array($transStatus, ['deny', 'expire', 'cancel'])) {
            $sale->update(['payment_status' => 'failed']);
        }

        return response()->json(['status' => 'OK']);
    }
}
