<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('street');
            $table->string('street2')->nullable();
            $table->string('zip', 16);
            $table->string('city');
            $table->string('country', 2)->default('SE');
            $table->string('phone')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['customer_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
