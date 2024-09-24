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
        Schema::create('microsite', function (Blueprint $table) {
            $table->id();
            $table->string('store_code', 100);
            $table->string('business_name', 90);
            $table->string('customer_name', 100);
            $table->string('mobile_number', 12);
            $table->string('pin_code', 10)->nullable();
            $table->string('loan_amount', 10);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('microsite');
    }
};
