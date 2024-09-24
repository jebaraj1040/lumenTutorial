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
        Schema::table('hj_payment_transaction', function (Blueprint $table) {
            $table->dropColumn(['card_type', 'email_sent_at', 'email_sent_status', 'sms_sent_at', 'sms_sent_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hj_payment_transaction', function (Blueprint $table) {
            $table->string('card_type')->after('risk');
            $table->string('email_sent_at')->after('card_type');
            $table->string('email_sent_status')->after('email_sent_at');
            $table->string('sms_sent_at')->after('email_sent_status');
            $table->string('sms_sent_status')->after('sms_sent_at');
        });
    }
};
