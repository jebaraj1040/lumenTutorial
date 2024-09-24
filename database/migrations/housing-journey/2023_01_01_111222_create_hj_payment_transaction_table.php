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
        Schema::create('hj_payment_transaction', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('lead_id')->unsigned()->index();
            $table->string('quote_id', 12)->index();
            $table->bigInteger('payment_gateway_id')->unsigned()->index();
            $table->string('payment_transaction_id');
            $table->string('digital_transaction_no');
            $table->string('gateway_transaction_id');
            $table->string('neft_payment_transaction_id');
            $table->string('bank_reference_no');
            $table->string('bank_name');
            $table->string('neft_bank_name');
            $table->string('neft_bank_ifsc_code');
            $table->string('neft_bank_beneficiary_name');
            $table->double('amount', 20, 2);
            $table->string('method');
            $table->string('mode');
            $table->string('gateway_status_code'); //
            $table->text('gateway_msg');
            $table->string('customer_var');
            $table->string('transaction_time');
            $table->string('transaction_type');
            $table->string('retrieval_reference_number');
            $table->enum('status', ['INIT', 'SUCCESS', 'FAILURE', 'CANCEL', 'PENDING', 'RETRY']);
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->string('path');
            $table->string('reason');
            $table->string('conversion_rate');
            $table->string('billed_amount');
            $table->string('sur_charge');
            $table->string('merchant_name');
            $table->string('merchant_id');
            $table->string('transaction_mode');
            $table->string('currency_code');
            $table->string('risk');
            $table->string('card_type')->nullable();
            $table->string('email_sent_at')->nullable();
            $table->string('email_sent_status')->nullable();
            $table->string('sms_sent_at')->nullable();
            $table->string('sms_sent_status')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->foreign('lead_id')->references('id')->on('hj_lead');
            $table->foreign('payment_gateway_id')->references('id')->on('hj_payment_gateway');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hj_payment_transaction');
    }
};
