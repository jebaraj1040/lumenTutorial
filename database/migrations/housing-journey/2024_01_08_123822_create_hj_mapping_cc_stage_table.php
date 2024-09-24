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
        Schema::create('hj_mapping_cc_stage', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('master_cc_stage_id')->unsigned()->index();
            $table->bigInteger('master_cc_sub_stage_id')->unsigned()->index();
            $table->foreign('master_cc_stage_id')->references('id')->on('hj_master_cc_stage');
            $table->foreign('master_cc_sub_stage_id')->references('id')->on('hj_master_cc_sub_stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hj_mapping_cc_stage');
    }
};
