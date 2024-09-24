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
        Schema::create('hj_lead', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mobile_number')->index();
            $table->boolean('is_applicant')->default(true);
            $table->bigInteger('pincode_id')->unsigned()->index();
            $table->boolean('is_being_assisted')->default(true);
            $table->string('partner_code')->nullable();
            $table->string('home_extension')->nullable();
            $table->string('sub_partner_code')->nullable();
            $table->boolean('is_agreed')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->foreign('pincode_id')->references('id')->on('hj_master_pincode');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hj_applicant_detail');
    }
};
