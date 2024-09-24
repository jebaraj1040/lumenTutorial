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
        Schema::table('leads', function (Blueprint $table) {
            $table->string('account_number', 10)->after('language_code')->nullable();
            $table->string('account_type', 10)->after('account_number')->nullable();
            $table->date('start_date')->after('account_type')->nullable();
            $table->date('end_date')->after('start_date')->nullable();
            $table->enum('is_active', [0, 1])->after('end_date')->default(0);
            $table->text('amount')->after('is_active')->nullable();
            $table->string('ifsc', 11)->after('amount')->nullable();
            $table->integer('debit_date')->after('ifsc')->nullable();
            $table->text('umrn')->after('debit_date')->nullable();
            $table->string('transaction_id', 50)->after('umrn')->nullable();
            $table->string('account_holder_name', 100)->after('transaction_id')->nullable();
            $table->string('txn_status', 10)->after('account_holder_name')->nullable();
            $table->text('txn_msg')->after('txn_status')->nullable();
            $table->text('txn_error_msg')->after('txn_msg')->nullable();
            $table->text('txn_ref')->after('txn_error_msg')->nullable();
            $table->text('txn_bankcd')->after('txn_ref')->nullable();
            $table->timestamp('txn_tran_time')->after('txn_bankcd')->nullable();
            $table->text('txn_rqst_token')->after('txn_tran_time')->nullable();
            $table->string('txn_itc', 50)->after('txn_rqst_token')->nullable();
            $table->string('txn_id', 50)->after('txn_itc')->nullable();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['account_number',  'account_type', 'start_date', 'end_date', 'is_active', 'amount', 'ifsc', 'debit_date', 'umrn', 'transaction_id', 'account_holder_name', 'txn_status', 'txn_msg', 'txn_ref', 'txn_bankcd', 'txn_tran_time', 'txn_rqst_token', 'txn_itc', 'txn_id']);
        });
    }
};
