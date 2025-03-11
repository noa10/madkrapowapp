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
        Schema::table('madkrapow_payments', function (Blueprint $table) {
            $table->json('transaction_details')->nullable()->after('reference_id')
                ->comment('JSON data containing transaction details from payment gateway');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('madkrapow_payments', function (Blueprint $table) {
            $table->dropColumn('transaction_details');
        });
    }
};
