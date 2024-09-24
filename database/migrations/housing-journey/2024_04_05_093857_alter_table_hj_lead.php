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
            $table->boolean('is_otp_verified')->after('is_being_assisted')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hj_lead', function (Blueprint $table) {
            $table->dropColumn('is_otp_verified');
        });
    }
};
