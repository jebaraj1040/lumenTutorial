<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hj_mapping_pan_applicant', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('lead_id')->unsigned()->index();
            $table->string('quote_id', 12)->index();
            $table->bigInteger('personal_detail_id')->unsigned()->index();
            $table->foreign('lead_id')->references('id')->on('hj_lead');
            $table->foreign('personal_detail_id')->references('id')->on('hj_personal_detail');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hj_mapping_pan_applicant');
    }
};
