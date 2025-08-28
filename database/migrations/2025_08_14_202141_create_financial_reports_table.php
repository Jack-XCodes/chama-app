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
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['balance_sheet', 'profit_loss', 'cash_flow', 'member_statement', 'paid_up_members']);
            $table->date('start_date');
            $table->date('end_date');
            $table->json('report_data'); // Store calculated report data
            $table->json('metadata')->nullable(); // Additional metadata
            $table->enum('status', ['generating', 'completed', 'failed'])->default('generating');
            $table->string('file_path')->nullable(); // Path to generated file
            $table->string('export_format')->nullable(); // pdf, excel, csv
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('generated_at')->nullable();
            $table->bigInteger('file_size')->nullable(); // File size in bytes
            $table->string('hash')->nullable(); // File hash for integrity
            $table->timestamps();
            
            $table->index(['type', 'start_date', 'end_date']);
            $table->index(['generated_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_reports');
    }
};
