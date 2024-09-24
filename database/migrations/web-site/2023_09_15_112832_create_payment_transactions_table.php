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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_log_id');
            $table->foreign('api_log_id')
                ->references('id')
                ->on('api_logs');
            $table->string('policy_no');
            $table->string('order_id');
            $table->enum('payment_gateway_name', ['Techprocess', 'Paytm'])->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_type', 10)->nullable();
            $table->string('frequency');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('mobile_number');
            $table->string('email_id', 255)->nullable();
            $table->boolean('active')->default(true);
            $table->integer('amount');
            $table->string('ifsc_code', 11)->nullable();
            $table->integer('debit_date')->nullable();
            $table->string('umrn')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('txn_status')->nullable();
            $table->string('txn_msg')->nullable();
            $table->string('txn_error_msg')->nullable();
            $table->string('txn_ref')->nullable();
            $table->string('txn_bankcd')->nullable();
            $table->timestamp('txn_tran_time')->nullable();
            $table->string('txn_rqst_token')->nullable();
            $table->string('txn_itc')->nullable();
            $table->string('txn_id')->nullable();
            $table->json('payment_gateway_response')->nullable();
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
        Schema::dropIfExists('payment_transactions');
    }
};
