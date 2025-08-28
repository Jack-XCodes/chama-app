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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('notification_type'); // Type of notification
            $table->boolean('in_app_enabled')->default(true); // Receive in-app notifications
            $table->boolean('email_enabled')->default(true); // Receive email notifications
            $table->boolean('digest_enabled')->default(false); // Receive digest emails
            $table->enum('digest_frequency', ['daily', 'weekly', 'monthly'])->default('weekly');
            $table->json('settings')->nullable(); // Additional settings for this notification type
            $table->timestamps();
            
            $table->unique(['user_id', 'notification_type']);
            $table->index(['user_id', 'in_app_enabled']);
            $table->index(['user_id', 'email_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
