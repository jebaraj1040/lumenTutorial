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
        Schema::create('hj_personal_detail', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('lead_id')->unsigned()->index();
            $table->string('quote_id', 12)->index();
            $table->string('full_name', 25);
            $table->string('dob');
            $table->enum('gender', ['Male', 'Female']);
            $table->string('email')->nullable();
            $table->string('pan')->index();
            $table->boolean('is_property_identified')->default(false);
            $table->integer('loan_amount');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->foreign('lead_id')->references('id')->on('hj_lead');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hj_personal_detail');
    }
};
