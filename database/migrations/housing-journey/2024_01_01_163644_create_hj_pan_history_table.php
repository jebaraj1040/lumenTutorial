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
        Schema::create('hj_pan_history', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('lead_id')->unsigned()->index();
            $table->string('quote_id');
            $table->string('pan');
            $table->string('url');
            $table->string('api_source');
            $table->string('api_type');
            $table->json('request');
            $table->json('response');
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('lead_id')->references('id')->on('hj_lead');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hj_pan_history');
    }
};
