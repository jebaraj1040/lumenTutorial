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
        Schema::create('hj_mapping_applicant_relationship', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('lead_id')->unsigned()->index();
            $table->bigInteger('relationship_id')->unsigned()->index();
            $table->foreign('lead_id')->references('id')->on('hj_lead');
            $table->foreign('relationship_id')->references('id')->on('hj_master_relationship');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hj_mapping_applicant_relationship');
    }
};
