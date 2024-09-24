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
        Schema::create('ppc_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_log_id');
            $table->foreign('api_log_id')
                ->references('id')
                ->on('api_logs');
            $table->string('email_id')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('cust_dob')->nullable();
            $table->string('financial_start_year')->nullable();
            $table->string('policy_number', 100);
            $table->string('ccp_id', 100);
            $table->string('message')->nullable();
            $table->integer('otp');
            $table->string('cust_mobile_no')->nullable();
            $table->string('url')->nullable();
            $table->enum('type', ['sms', 'email'])->default('sms');
            $table->timestamp('expiry_date')->useCurrent();
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
        Schema::dropIfExists('ppc_logs');
    }
};
