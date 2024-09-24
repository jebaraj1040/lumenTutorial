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
        Schema::create('bvn_calls', function (Blueprint $table) {
            $table->id();
            $table->string('store_code', 100);
            $table->string('business_name', 90);
            $table->string('customer_phone_number', 12);
            $table->string('bvn_call_status', 15);
            $table->text('recording_url');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bvn_calls');
    }
};
