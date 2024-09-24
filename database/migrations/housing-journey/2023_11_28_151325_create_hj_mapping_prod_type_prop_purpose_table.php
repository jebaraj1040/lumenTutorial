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
        Schema::create('hj_mapping_prod_type_prop_purpose', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('product_type_id')->unsigned()->index();
            $table->bigInteger('property_purpose_id')->unsigned()->index();
            $table->foreign('product_type_id')->references('id')->on('hj_master_product_type');
            $table->foreign('property_purpose_id')->references('id')->on('hj_master_property_purpose');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hj_mapping_prod_type_prop_purpose');
    }
};
