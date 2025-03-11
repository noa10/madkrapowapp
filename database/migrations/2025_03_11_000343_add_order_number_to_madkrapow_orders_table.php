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
        Schema::table('madkrapow_orders', function (Blueprint $table) {
            // Add order_number column after status column
            $table->string('order_number')->nullable()->after('status')->unique()->comment('Unique customer-facing order number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('madkrapow_orders', function (Blueprint $table) {
            // Remove the column when rolling back
            $table->dropColumn('order_number');
        });
    }
};
