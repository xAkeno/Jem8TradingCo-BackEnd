<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddtocartTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('addtocart', function (Blueprint $table) {
            $table->increments('cart_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('products_id');
            $table->integer('quantity')->default(1);
            $table->double('total', 10, 2)->default(0);
            $table->enum('status', ['active', 'saved', 'removed'])->default('active');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('products_id')->references('product_id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addtocart');
    }
}
