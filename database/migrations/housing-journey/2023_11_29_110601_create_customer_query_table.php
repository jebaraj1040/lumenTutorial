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
        Schema::create('customer_query', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email_id', 100);
            $table->string('subject');
            $table->string('mobile_number', 15)->index();
            $table->string('city', 100);
            $table->string('state', 100);
            $table->longText('feedback');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_query');
    }
};
