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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id(); // BIGINT primary key
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); 
            $table->decimal('amount', 12, 2); 
            $table->enum('type', ['income', 'expense']); 
            $table->text('description'); 
            $table->string('proof')->nullable();
            $table->enum('status', ['pending', 'reconciled'])->default('pending'); // ENUM with default
            $table->json('tags')->nullable(); // JSON, nullable
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
