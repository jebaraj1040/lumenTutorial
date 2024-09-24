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
            $table->string('offer_amount')->after('bre2_loan_amount');
            $table->bigInteger('master_origin_product_id')->unsigned()->change();
            $table->foreign('master_origin_product_id')->references('id')->on('hj_master_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hj_application', function (Blueprint $table) {
            $table->dropColumn(['offer_amount']);
            $table->dropForeign(['master_origin_product_id']);
        });
    }
};
