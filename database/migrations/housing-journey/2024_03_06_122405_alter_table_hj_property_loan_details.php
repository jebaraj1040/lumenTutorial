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
        Schema::table('hj_property_loan_detail', function (Blueprint $table) {
            $table->string('existing_loan_provider_name')->after('existing_loan_provider')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hj_property_loan_detail', function (Blueprint $table) {
            $table->dropColumn(['existing_loan_provider_name']);
        });
    }
};
