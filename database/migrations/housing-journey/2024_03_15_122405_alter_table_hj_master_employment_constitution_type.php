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
        Schema::table('hj_master_employment_constitution_type', function (Blueprint $table) {
            $table->string('display_name')->after('handle')->nullable();
            $table->integer('order_id')->after('display_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hj_master_employment_constitution_type', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'order_id']);
        });
    }
};
