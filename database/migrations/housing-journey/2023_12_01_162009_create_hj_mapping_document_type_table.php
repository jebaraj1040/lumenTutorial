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
        Schema::create('hj_mapping_document_type', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('master_document_type_id')->unsigned()->index();
            $table->bigInteger('master_document_id')->unsigned()->index();
            $table->foreign('master_document_type_id')->references('id')->on('hj_master_document_type');
            $table->foreign('master_document_id')->references('id')->on('hj_master_document');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hj_mapping_document_type');
    }
};
