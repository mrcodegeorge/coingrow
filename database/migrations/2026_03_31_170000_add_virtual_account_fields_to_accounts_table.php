<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('account_number')->unique()->after('balance');
            $table->string('account_name')->after('account_number');
            $table->string('bank_name')->nullable()->after('account_name');
            $table->string('provider')->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropUnique(['account_number']);
            $table->dropColumn(['account_number', 'account_name', 'bank_name', 'provider']);
        });
    }
};
