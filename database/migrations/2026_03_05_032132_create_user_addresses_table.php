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
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('accounts')
                ->onDelete('cascade');

            // address type
            $table->enum('type', ['personal', 'company'])->default('personal');

            // company fields (only used if type = company)
            $table->string('company_name')->nullable();
            $table->string('company_role')->nullable();
            $table->string('company_number')->nullable();
            $table->string('company_email')->nullable();

            // address details
            $table->string('street');
            $table->string('barangay')->nullable();
            $table->string('city');
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Philippines');

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};