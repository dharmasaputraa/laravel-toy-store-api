<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('sku')->unique();
            $table->string('name')->nullable();

            $table->decimal('price', 12, 2);
            $table->decimal('compare_price', 12, 2)->nullable();

            $table->integer('stock')->default(0);

            $table->json('attributes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
