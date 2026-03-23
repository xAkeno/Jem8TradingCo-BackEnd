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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id('activity_id');

            // Account (user who performed the action)
            $table->unsignedBigInteger('user_id');
            $table->string('user_name', 255);     // snapshot of name at time of action

            // Action details
            $table->string('action', 255);         // e.g. "Placed order", "Order Completed"
            $table->enum('category', [
                'orders',
                'stock',
                'account',
                'blogs',
                'payments',
                'backups',
                'other',
            ])->default('other');

            // Product info
            $table->string('product_name', 255)->nullable();
            $table->string('product_unique_code', 100)->nullable(); // e.g. ORDER-001

            // Payment
            $table->string('mode_of_payment', 100)->nullable();     // e.g. Cash, GCash, Card
            $table->decimal('amount', 10, 2)->nullable();            // e.g. $100

            // Extra details / description
            $table->text('description')->nullable();                  // e.g. "Organic Barley x3 Office Supplies x1"

            // Timestamps
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Indexes for fast filtering
            $table->index('category');
            $table->index('logged_at');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};