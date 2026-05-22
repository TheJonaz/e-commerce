<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('slug');
            $table->json('name');
            $table->json('short_description')->nullable();
            $table->json('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('vat_rate', 5, 2)->default(25.00);
            $table->integer('stock')->nullable();
            $table->string('type')->default('physical');
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
