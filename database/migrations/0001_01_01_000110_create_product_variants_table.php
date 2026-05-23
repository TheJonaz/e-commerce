<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->json('options');
            $table->decimal('price', 12, 2);
            $table->integer('stock')->nullable();
            $table->unsignedInteger('weight_grams')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'is_active', 'position']);
            $table->index('sku');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('variant_id')->nullable()->after('product_id')
                ->constrained('product_variants')->nullOnDelete();
        });

        // Drop the old (cart_id, product_id) unique so the same product can live
        // in a single cart multiple times (one row per variant).
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'product_id']);
        });
        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(['cart_id', 'product_id', 'variant_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('variant_id')->nullable()->after('product_id')
                ->constrained('product_variants')->nullOnDelete();
            $table->json('variant_options_snapshot')->nullable()->after('sku_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
            $table->dropColumn(['variant_id', 'variant_options_snapshot']);
        });
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
            $table->dropColumn('variant_id');
        });
        Schema::dropIfExists('product_variants');
    }
};
