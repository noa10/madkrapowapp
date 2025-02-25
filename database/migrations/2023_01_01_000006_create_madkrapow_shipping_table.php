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
        Schema::create('madkrapow_shipping', function (Blueprint $table) {
            $table->id('shipping_id');
            $table->unsignedBigInteger('order_id');
            $table->timestamp('shipping_date')->nullable();
            $table->string('delivery_method');
            $table->string('status')->default('pending');
            $table->text('shipping_address');
            $table->timestamps();

            $table->foreign('order_id')->references('order_id')->on('madkrapow_order')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('madkrapow_shipping');
    }
};
