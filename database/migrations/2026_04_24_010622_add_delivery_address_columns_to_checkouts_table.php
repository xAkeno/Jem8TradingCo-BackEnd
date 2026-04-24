<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkouts', function (Blueprint $table) {
            // Drop old single column if it exists
            if (Schema::hasColumn('checkouts', 'delivery_address')) {
                $table->dropColumn('delivery_address');
            }

            $table->string('delivery_street')->nullable()->after('special_instructions');
            $table->string('delivery_barangay')->nullable()->after('delivery_street');
            $table->string('delivery_city')->nullable()->after('delivery_barangay');
            $table->string('delivery_province')->nullable()->after('delivery_city');
            $table->string('delivery_zip')->nullable()->after('delivery_province');
            $table->string('delivery_country')->nullable()->default('Philippines')->after('delivery_zip');
        });
    }

    public function down(): void
    {
        Schema::table('checkouts', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_street',
                'delivery_barangay',
                'delivery_city',
                'delivery_province',
                'delivery_zip',
                'delivery_country',
            ]);
        });
    }
};
