<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->foreignId('source_sub_account_id')->nullable()->constrained('sub_accounts')->nullOnDelete();
            $table->foreignId('destination_sub_account_id')->nullable()->constrained('sub_accounts')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('frequency');
            $table->timestamp('next_run_at');
            $table->boolean('active')->default(true);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_transactions');
    }
};
