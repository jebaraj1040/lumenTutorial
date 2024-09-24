<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hj_auction_bid_form_details', function (Blueprint $table) {
            $table->id();
            $table->string('project_number');
            $table->string('project_name');
            $table->integer('file_number');
            $table->string('pan_number');
            $table->string('name');
            $table->bigInteger('mobile_number');
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->integer('pincode')->nullable();
            $table->string('account_number');
            $table->string('ifsc_code');
            $table->string('bank_name');
            $table->string('branch_name');
            $table->string('property_item_number');
            $table->boolean('is_emd_remitted');
            $table->boolean('is_same_bank_details');
            $table->string('emd_account_number');
            $table->string('emd_ifsc_code');
            $table->string('emd_branch_name');
            $table->string('emd_bank_name');
            $table->boolean('consent');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hj_auction_bid_form_details');
    }
};
