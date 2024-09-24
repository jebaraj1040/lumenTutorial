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
        Schema::create('hj_employment_detail', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('lead_id')->unsigned()->index();
            $table->string('quote_id', 12)->index();
            $table->bigInteger('employment_type_id')->unsigned()->index();
            $table->bigInteger('company_id')->unsigned()->index()->nullable();
            $table->string('company_name')->nullable();
            $table->bigInteger('constitution_type_id')->unsigned()->index()->nullable();
            $table->bigInteger('salary_mode_id')->unsigned()->index()->nullable();
            $table->string('net_monthly_salary', 25)->nullable();
            $table->string('monthly_emi', 25);
            $table->string('total_experience', 25)->nullable();
            $table->string('current_experience', 25)->nullable();
            $table->string('other_income')->nullable();
            $table->bigInteger('industry_segment_id')->unsigned()->index()->nullable();
            $table->bigInteger('industry_type_id')->unsigned()->index()->nullable();
            $table->string('net_monthly_sales')->nullable();
            $table->string('net_monthly_profit')->nullable();
            $table->string('gross_receipt')->nullable();
            $table->string('business_vintage')->nullable();
            $table->bigInteger('professional_type_id')->unsigned()->index()->nullable();
            $table->boolean('is_income_proof_document_available')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->foreign('lead_id')->references('id')->on('hj_lead');
            $table->foreign('industry_segment_id')->references('id')->on('hj_master_industry_segment');
            $table->foreign('industry_type_id')->references('id')->on('hj_master_industry_type');
            $table->foreign('employment_type_id')->references('id')->on('hj_master_employment_type');
            $table->foreign('company_id')->references('id')->on('hj_master_company');
            $table->foreign('constitution_type_id')->references('id')->on('hj_master_employment_constitution_type');
            $table->foreign('salary_mode_id')->references('id')->on('hj_master_employment_salary_mode');
            $table->foreign('professional_type_id')->references('id')->on('hj_master_professional_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hj_employment_detail');
    }
};
