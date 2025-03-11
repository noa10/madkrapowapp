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
            $table->string('reference_id')->nullable()->after('payment_date')
                ->comment('External reference ID from payment gateway');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('madkrapow_payments', function (Blueprint $table) {
            $table->dropColumn('reference_id');
        });
    }
};
