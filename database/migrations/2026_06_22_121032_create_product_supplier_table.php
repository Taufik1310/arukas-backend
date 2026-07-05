<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Many-to-Many: products <-> suppliers
        Schema::create('product_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'supplier_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('product_supplier');
    }
};
