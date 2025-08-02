<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. "admin", "member", "treasurer"
            $table->json('permissions')->nullable(); // Storing permissions as JSON array
            $table->timestamps();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
