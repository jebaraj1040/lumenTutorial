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
        Schema::create('hj_application', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('lead_id')->unsigned()->index();
            $table->string('name', 64)->nullable();
            $table->string('quote_id', 12)->index();
            $table->string('digital_transaction_no', 20)->nullable();
            $table->string('payment_transaction_id', 20)->nullable();
            $table->string('mobile_number')->index();
            $table->bigInteger('master_product_id')->unsigned()->index();
            $table->bigInteger('master_product_step_id')->unsigned()->index();
            $table->integer('previous_impression_id');
            $table->integer('current_impression_id');
            $table->string('cibil_score');
            $table->string('cc_token');
            $table->string('loan_amount');
            $table->string('bre1_loan_amount');
            $table->string('bre1_updated_loan_amount');
            $table->string('bre2_loan_amount');
            $table->boolean('is_purchased')->default(false);
            $table->boolean('is_paid')->default(false);
            $table->boolean('is_bre_execute')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->foreign('lead_id')->references('id')->on('hj_lead');
            $table->foreign('master_product_id')->references('id')->on('hj_master_product');
            $table->foreign('master_product_step_id')->references('id')->on('hj_master_product_step');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hj_application');
    }
};
