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
        Schema::create('google_chat', function (Blueprint $table) {
            $table->id();
            $table->string('store_code', 100);
            $table->string('business_name', 90);
            $table->string('user_name', 100);
            $table->string('phone_number', 12);
            $table->string('email_address', 50);
            $table->string('chatbot_option', 100);
            $table->string('tags', 100);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_chat');
    }
};
