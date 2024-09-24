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
        Schema::table('hj_lead', function (Blueprint $table) {
            $table->enum('customer_type', ['ETB', 'NTB'])->default('NTB')->after('sub_partner_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hj_lead', function (Blueprint $table) {
            $table->dropColumn('customer_type');
        });
    }
};
