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
        Schema::create('hj_document', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('lead_id')->unsigned()->index();
            $table->string('quote_id', 12)->index();
            $table->string('document_position_id', 10)->index();
            $table->bigInteger('master_document_id')->unsigned()->index();
            $table->bigInteger('master_document_type_id')->unsigned()->index();
            $table->enum('document_type_extension', ["pdf", "png", "jpeg", "jpg"]);
            $table->string('document_saved_location', 255);
            $table->string('document_file_name', 255);
            $table->string('document_encrypted_name', 255);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->foreign('lead_id')->references('id')->on('hj_lead');
            $table->foreign('master_document_id')->references('id')->on('hj_master_document');
            $table->foreign('master_document_type_id')->references('id')->on('hj_master_document_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hj_document');
    }
};
