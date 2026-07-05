<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'user_id', 'customer_name', 'customer_phone', 'customer_email',
        'subtotal', 'discount', 'tax', 'total_amount', 'paid_amount', 'change_amount',
        'payment_method', 'midtrans_order_id', 'midtrans_token', 'payment_status', 'notes',
    ];

    protected $casts = [
        'subtotal'     => 'decimal:2',
        'discount'     => 'decimal:2',
        'tax'          => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount'  => 'decimal:2',
        'change_amount'=> 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 1:M — SaleTransaction hasMany SaleItems (RELASI WAJIB 2)
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
