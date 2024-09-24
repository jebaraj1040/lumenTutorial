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
        Schema::table('hj_application', function (Blueprint $table) {
            $table->string('cc_quote_id', 12)->after('quote_id')->nullable();
            $table->string('session_auth_token')->after('is_bre_execute')->nullable();
            $table->string('auth_token')->after('is_bre_execute')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hj_application', function (Blueprint $table) {
            $table->dropColumn('cc_quote_id');
            $table->dropColumn('session_auth_token');
            $table->dropColumn('auth_token');
        });
    }
};
