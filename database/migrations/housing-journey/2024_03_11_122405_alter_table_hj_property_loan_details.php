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
            $table->boolean('is_existing_property')->after('is_property_loan_free')->default(false);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hj_property_loan_detail', function (Blueprint $table) {
            $table->dropColumn(['is_existing_property']);
            $table->dropSoftDeletes();
        });
    }
};
