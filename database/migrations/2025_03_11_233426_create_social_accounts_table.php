<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('social_accounts')) {
            Schema::create('social_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->foreign('user_id')
                      ->references('user_id')
                      ->on('madkrapow_users')
                      ->onDelete('cascade');
                $table->string('provider');
                $table->string('provider_user_id');
                $table->string('token')->nullable();
                $table->string('refresh_token')->nullable();
                $table->timestamp('token_expires_at')->nullable();
                $table->timestamps();
                
                // Create a unique index on provider and provider_user_id
                $table->unique(['provider', 'provider_user_id']);
            });
        } else {
            // Table already exists, so let's fix the foreign key constraint
            // First, check if the foreign key constraint exists
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = 'social_accounts'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                AND CONSTRAINT_NAME = 'social_accounts_user_id_foreign'
            ");
            
            // If constraint exists, drop it
            if (!empty($foreignKeys)) {
                Schema::table('social_accounts', function (Blueprint $table) {
                    $table->dropForeign('social_accounts_user_id_foreign');
                });
            }
            
            // Add the correct foreign key
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->foreign('user_id')
                      ->references('user_id')
                      ->on('madkrapow_users')
                      ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
