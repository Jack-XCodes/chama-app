<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id(); // BIGINT PK
            $table->string('name'); // e.g., "Meeting Minutes"
            $table->json('allowed_actions'); // e.g., ["upload", "view"]
            $table->enum('visibility', ['public', 'private', 'members']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
