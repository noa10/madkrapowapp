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
<<<<<<<< Updated upstream:database/migrations/2025_02_27_080544_add_order_date_to_madkrapow_orders_table.php
        Schema::table('madkrapow_orders', function (Blueprint $table) {
            $table->timestamp('order_date')->nullable();
========
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('email');
>>>>>>>> Stashed changes:database/migrations/2025_02_28_155339_add_google_id_to_users_table.php
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
<<<<<<<< Updated upstream:database/migrations/2025_02_27_080544_add_order_date_to_madkrapow_orders_table.php
        Schema::table('madkrapow_orders', function (Blueprint $table) {
            $table->dropColumn('order_date');
========
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google_id');
>>>>>>>> Stashed changes:database/migrations/2025_02_28_155339_add_google_id_to_users_table.php
        });
    }
};
