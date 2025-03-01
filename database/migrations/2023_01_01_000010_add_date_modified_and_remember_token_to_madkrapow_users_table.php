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
        Schema::table('madkrapow_users', function (Blueprint $table) {
            // Add date_modified column to track when a user record was last modified
            $table->timestamp('date_modified')->nullable();
            
            // Add remember_token for "remember me" functionality during login
            if (!Schema::hasColumn('madkrapow_users', 'remember_token')) {
                $table->rememberToken();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('madkrapow_users', function (Blueprint $table) {
            $table->dropColumn('date_modified');
            $table->dropRememberToken();
        });
    }
};