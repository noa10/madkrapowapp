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
        Schema::create('madkrapow_cart_item', function (Blueprint $table) {
            $table->id('cart_item_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->timestamp('added_date')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('madkrapow_user')->onDelete('cascade');
            $table->foreign('product_id')->references('product_id')->on('madkrapow_product')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('madkrapow_cart_item');
    }
};
