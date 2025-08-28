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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->boolean('is_urgent')->default(false);
            $table->json('attachments')->nullable(); // Store file paths and metadata
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('send_email')->default(true);
            $table->boolean('send_in_app')->default(true);
            $table->json('target_roles')->nullable(); // Which roles should receive this
            $table->json('target_users')->nullable(); // Specific users to target
            $table->integer('views_count')->default(0);
            $table->json('metadata')->nullable(); // Additional data
            $table->timestamps();
            
            $table->index(['is_published', 'published_at']);
            $table->index(['created_by', 'created_at']);
            $table->index(['priority', 'is_urgent']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
