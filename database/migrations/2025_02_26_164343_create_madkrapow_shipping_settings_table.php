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
        Schema::create('madkrapow_shipping_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('standard_delivery_days')->default(3);
            $table->decimal('standard_delivery_cost', 8, 2)->default(5.00);
            $table->unsignedTinyInteger('express_delivery_days')->default(1);
            $table->decimal('express_delivery_cost', 8, 2)->default(12.00);
            $table->text('return_policy');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('madkrapow_shipping_settings');
    }
};
