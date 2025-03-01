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
        // Add google_id to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('email');
        });

        // Add order_date to madkrapow_orders table
        Schema::table('madkrapow_orders', function (Blueprint $table) {
            $table->timestamp('order_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove google_id from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google_id');
        });

        // Remove order_date from madkrapow_orders table
        Schema::table('madkrapow_orders', function (Blueprint $table) {
            $table->dropColumn('order_date');
        });
    }
};
