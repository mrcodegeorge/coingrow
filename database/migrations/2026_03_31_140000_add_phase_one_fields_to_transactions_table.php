<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('category')->nullable()->after('type');
            $table->text('note')->nullable()->after('description');
            $table->json('tags')->nullable()->after('note');
            $table->foreignId('related_sub_account_id')
                ->nullable()
                ->after('sub_account_id')
                ->constrained('sub_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('related_sub_account_id');
            $table->dropColumn(['category', 'note', 'tags']);
        });
    }
};
