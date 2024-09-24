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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_log_id');
            $table->foreign('api_log_id')
                ->references('id')
                ->on('api_logs');
            $table->string('activity')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email_id')->nullable();
            $table->string('dob')->nullable();
            $table->string('age')->nullable();
            $table->string('gender')->nullable();
            $table->string('preferred_language')->nullable();
            $table->string('is_smoker')->nullable();
            $table->string('location')->nullable();
            $table->string('occupation')->nullable();
            $table->string('insure_for')->nullable();
            $table->string('annual_income')->nullable();
            $table->string('policy_number')->nullable();
            $table->string('process')->nullable();
            $table->string('source')->nullable();
            $table->string('plan_id')->nullable();
            $table->string('plan_name')->nullable();
            $table->string('campaign')->nullable();
            $table->string('campaign_id')->nullable();
            $table->string('campaign_type')->nullable();
            $table->string('channel')->nullable();
            $table->string('otp')->nullable();
            $table->string('product_type')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_params')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('campaign_url')->nullable();
            $table->boolean('is_agreed')->default(false)->nullable();
            $table->string('application_currently_on')->nullable();
            $table->string('application_id')->nullable();
            $table->string('application_live_status')->nullable();
            $table->string('application_live_status_reason')->nullable();
            $table->string('basic_premium')->nullable();
            $table->string('application_status')->nullable();
            $table->string('benefit_option')->nullable();
            $table->string('ch_lead_stage')->nullable();
            $table->string('comments')->nullable();
            $table->string('currently_pending')->nullable();
            $table->string('current_page')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_online_status')->nullable();
            $table->string('date_created')->nullable();
            $table->string('frequency')->nullable();
            $table->string('gst')->nullable();
            $table->string('keyword')->nullable();
            $table->string('proposer_fname')->nullable();
            $table->string('proposer_mname')->nullable();
            $table->string('proposer_lname')->nullable();
            $table->string('proposer_gender')->nullable();
            $table->string('proposer_larelation')->nullable();
            $table->string('proposer_relation')->nullable();
            $table->string('proposal_number')->nullable();
            $table->string('last_visited')->nullable();
            $table->string('lead_profile_view')->nullable();
            $table->string('paymentstatus')->nullable();
            $table->string('quote_id')->nullable();
            $table->string('referrer')->nullable();
            $table->string('riders')->nullable();
            $table->string('rmid')->nullable();
            $table->string('session_id')->nullable();
            $table->string('sum_assured')->nullable();
            $table->string('tenure')->nullable();
            $table->string('total_premium')->nullable();
            $table->string('financial_year')->nullable();
            $table->string('trackers')->nullable();
            $table->string('user_type')->nullable();
            $table->string('complaint_type')->nullable();
            $table->string('complaint_description')->nullable();
            $table->string('complaint_details')->nullable();
            $table->string('file')->nullable();
            $table->string('file_extension')->nullable();
            $table->string('interaction_received_from')->nullable();
            $table->string('source_of_complaint')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('is_available')->nullable();
            $table->string('consent')->nullable();
            $table->string('language_code')->nullable();
            $table->enum('is_otp_sent', [0, 1])->default(0);
            $table->enum('is_otp_verified', [0, 1])->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leads');
    }
};
