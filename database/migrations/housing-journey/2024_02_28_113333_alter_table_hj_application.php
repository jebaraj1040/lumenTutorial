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
            $table->string('disposition_status')->after('session_auth_token')->nullable();
            $table->string('disposition_sub_status')->after('disposition_status')->nullable();
            $table->timestamp('disposition_date')->after('disposition_sub_status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hj_application', function (Blueprint $table) {
            $table->dropColumn('disposition_status');
            $table->dropColumn('disposition_sub_status');
            $table->dropColumn('disposition_date');
        });
    }
};
