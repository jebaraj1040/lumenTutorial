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
        Schema::table('hj_application', function (Blueprint $table) {
            $table->bigInteger('master_origin_product_id')->after('master_product_id')->unsigned()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hj_application', function (Blueprint $table) {
            $table->dropColumn('master_origin_product_id');
        });
    }
};
