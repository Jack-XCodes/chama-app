<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invites', function (Blueprint $table) {
            $table->id(); // BIGINT Primary Key
            $table->string('email')->unique(); // Invited user's email
            $table->string('token')->unique(); // Secure invite token
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade'); // Who invited

            $table->timestamp('accepted_at')->nullable(); // When invite was used
            $table->timestamp('expires_at')->nullable(); // Optional expiration
            $table->boolean('used')->default(false); // Whether invite was used

            $table->timestamps(); // created_at and updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invites');
    }
};
