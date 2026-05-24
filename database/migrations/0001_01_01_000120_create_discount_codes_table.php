<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->string('type'); // 'percent' or 'fixed'
            $table->decimal('value', 12, 2);
            $table->decimal('min_order_amount', 12, 2)->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('times_used')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('valid_until');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->string('discount_code')->nullable()->after('currency');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('discount_code')->nullable()->after('discount_total');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('discount_code');
        });
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('discount_code');
        });
        Schema::dropIfExists('discount_codes');
    }
};
