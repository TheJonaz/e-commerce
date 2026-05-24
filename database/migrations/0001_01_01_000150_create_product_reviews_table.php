<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->unsignedTinyInteger('rating'); // 1..5
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('is_verified_purchase')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'is_published', 'created_at']);
            $table->index(['customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
