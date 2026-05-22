<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number')->unique();
            $table->string('email');
            $table->string('currency', 3)->default('SEK');

            $table->decimal('subtotal_excl_vat', 12, 2);
            $table->decimal('vat_total', 12, 2);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);

            $table->string('status')->default('pending');
            $table->string('payment_status')->default('unpaid');
            $table->string('shipping_status')->default('not_shipped');

            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('shipping_method')->nullable();

            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'placed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
