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
        Schema::create('website_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 25)->nullable();
            $table->bigInteger('mobile_number')->nullable();
            $table->string('email')->nullable();
            $table->bigInteger('pincode_id')->nullable();
            $table->integer('master_product_id')->nullable();
            $table->integer('loan_amount')->nullable();
            $table->string('source_page')->nullable();
            $table->boolean('is_assisted')->default(false)->nullable();
            $table->boolean('is_verified')->default(false)->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_medium')->nullable();
            $table->json('utm_params')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('partner_code')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_submissions');
    }
};
