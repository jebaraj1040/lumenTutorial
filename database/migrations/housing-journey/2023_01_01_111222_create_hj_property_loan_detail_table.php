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
        Schema::create('hj_property_loan_detail', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('lead_id')->unsigned()->index();
            $table->string('quote_id', 12)->index();
            $table->bigInteger('property_type_id')->unsigned()->index()->nullable();
            $table->bigInteger('existing_loan_provider')->unsigned()->index()->nullable();
            $table->string('age', 25)->nullable();
            $table->string('cost', 25)->nullable();
            $table->string('property_purchase_from', 25)->nullable();
            $table->string('project_type', 25)->nullable();
            $table->bigInteger('project_id')->unsigned()->index()->nullable();
            $table->boolean('is_property_identified')->default(false);
            $table->boolean('is_property_loan_free')->default(false);
            $table->bigInteger('property_purpose_id')->unsigned()->index()->nullable();
            $table->bigInteger('pincode_id')->unsigned()->index();
            $table->string('area');
            $table->string('city');
            $table->string('state');
            $table->string('original_loan_amount', 25)->nullable();
            $table->string('original_loan_tenure', 25)->nullable();
            $table->string('outstanding_loan_amount', 25)->nullable();
            $table->string('outstanding_loan_tenure', 25)->nullable();
            $table->string('monthly_installment_amount', 25)->nullable();
            $table->string('plot_cost')->nullable();
            $table->string('construction_cost')->nullable();
            $table->bigInteger('property_current_state_id')->unsigned()->index()->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->foreign('lead_id')->references('id')->on('hj_lead');
            $table->foreign('property_type_id')->references('id')->on('hj_master_property_type');
            $table->foreign('property_purpose_id')->references('id')->on('hj_master_property_purpose');
            $table->foreign('property_current_state_id')->references('id')->on('hj_master_property_current_state');
            $table->foreign('pincode_id')->references('id')->on('hj_master_pincode');
            $table->foreign('project_id')->references('id')->on('hj_master_project');
            $table->foreign('existing_loan_provider')->references('id')->on('hj_master_ifsc');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hj_property_loan_detail');
    }
};
