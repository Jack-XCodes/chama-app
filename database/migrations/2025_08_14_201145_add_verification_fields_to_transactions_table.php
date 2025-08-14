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
        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('requires_verification')->default(false)->after('audit_trail');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null')->after('requires_verification');
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->text('verification_notes')->nullable()->after('verified_at');
            $table->string('reference_number')->nullable()->after('verification_notes');
            $table->date('transaction_date')->nullable()->after('reference_number');
            
            // Update status enum to include new verification statuses
            $table->dropColumn('status');
        });
        
        // Add the updated status column with new enum values
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected', 'requires_verification', 'verified'])
                ->default('pending')
                ->after('proof_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'requires_verification',
                'verified_by',
                'verified_at',
                'verification_notes',
                'reference_number',
                'transaction_date'
            ]);
            
            // Revert status enum to original values
            $table->dropColumn('status');
        });
        
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('proof_file');
        });
    }
};
