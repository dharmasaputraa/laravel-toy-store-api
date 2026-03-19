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
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('provider_name'); // google, facebook, etc.
            $table->string('provider_id'); // ID from the OAuth provider
            $table->json('provider_data')->nullable(); // Additional provider data
            $table->timestamps();

            // Foreign key
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Unique constraint: one user can have only one account per provider
            $table->unique(['user_id', 'provider_name']);

            // Unique constraint: provider_id should be unique per provider
            $table->unique(['provider_name', 'provider_id']);

            // Indexes for faster queries
            $table->index(['provider_name', 'provider_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
