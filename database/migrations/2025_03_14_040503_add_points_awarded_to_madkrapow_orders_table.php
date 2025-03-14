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
            $table->boolean('points_awarded')->default(false)->after('status');
            $table->timestamp('points_awarded_at')->nullable()->after('points_awarded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('madkrapow_orders', function (Blueprint $table) {
            $table->dropColumn('points_awarded');
            $table->dropColumn('points_awarded_at');
        });
    }
};
